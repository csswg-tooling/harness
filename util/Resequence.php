<?php
/*******************************************************************************
 *
 *  Copyright © 2010-2011 Hewlett-Packard Development Company, L.P. 
 *
 *  This work is distributed under the W3C® Software License [1] 
 *  in the hope that it will be useful, but WITHOUT ANY 
 *  WARRANTY; without even the implied warranty of 
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 *
 *  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
 *
 *  Adapted from the Mobile Test Harness
 *  Copyright © 2007 World Wide Web Consortium
 *  http://dev.w3.org/cvsweb/2007/mobile-test-harness/
 * 
 ******************************************************************************/

require_once('lib/CmdLineWorker.php');
require_once('lib/TestSuites.php');
require_once('lib/TestSuite.php');

/**
 * This class regenerates the testsequence table, maintaining an ordering 
 * of testcases per testsuite, per engine in order of least number of
 * results per testcase
 *
 * This is meant to be run from by a periodic cron job or on the command line
 */
class Resequence extends CmdLineWorker
{
  protected $mEngines;
  protected $mTestSuites;
  protected $mCounts;
  protected $mTestCaseOptional;
  protected $mResults;

  function __construct() 
  {
    parent::__construct();

    $sql  = "SELECT DISTINCT `engine` ";
    $sql .= "FROM `useragents` ";
    $sql .= "WHERE `engine` != '' ";
    $sql .= "ORDER BY `engine` ";
    $r = $this->query($sql);
    while ($dbEngine = $r->fetchRow()) {
      $this->mEngines[] = $dbEngine['engine'];
    }

    $this->mTestSuites = new TestSuites();
  }
  
  protected function _loadTestCases($testSuiteName)
  {
    unset ($this->mTestCaseIds);
    unset ($this->mTestCaseOptional);

    $testSuiteName = $this->encode($testSuiteName, TESTCASES_MAX_TESTSUITE);
    
    $sql  = "SELECT `id`, `testcase`, `flags` ";
    $sql .= "FROM `testcases` ";
    $sql .= "WHERE `testsuite` = '{$testSuiteName}' ";
    $sql .= "AND `active` = '1' ";
    
    $r = $this->query($sql);
    while ($testCaseData = $r->fetchRow()) {
      $testCaseName = $testCaseData['testcase'];
      $testCaseId   = $testCaseData['id'];
      $flags        = $testCaseData['flags'];

      $optional = (FALSE !== stripos($flags, 'may')) || (FALSE !== stripos($flags, 'should'));
      
      $this->mTestCaseIds[$testCaseName] = $testCaseId;
      $this->mTestCaseOptional[$testCaseId] = $optional;
    }
  }
  
  protected function _loadResults($testSuiteQuery)
  {
    unset ($this->mResults);

    $testSuiteQuery = $this->encode($testSuiteQuery);

    $sql  = "SELECT `testcases`.`testcase`, `useragents`.`engine`, `results`.`result` ";
    $sql .= "FROM `results` INNER JOIN (`testcases`, `useragents`) ";
    $sql .= "ON `results`.`testcase_id` = `testcases`.`id` ";
    $sql .= "AND `results`.`useragent_id` = `useragents`.`id` ";
    $sql .= "WHERE `testcases`.`testsuite` LIKE '{$testSuiteQuery}' ";
    $sql .= "AND `testcases`.`active` = '1' ";
    $sql .= "AND `results`.`ignore` = '0' ";
    $sql .= "AND `results`.`result` != 'na' ";

    $r = $this->query($sql);
    while ($resultData = $r->fetchRow()) {
      $engine   = $resultData['engine'];
      if ('' != $engine) {
        $testCaseName = $resultData['testcase'];
        $result       = $resultData['result'];

        $this->mResults[$testCaseName][$engine][] = $result;
      }
    }
  }


  protected function _processTestcase($testCaseId, $testCaseName, $engineResults, $optional)
  {
    $passCount = 0;
    $testInvalid = FALSE;
    
    foreach ($this->mEngines as $engine) {
      if (array_key_exists($engine, $engineResults)) {
        $pass      = ((array_key_exists('pass', $engineResults[$engine])) ? $engineResults[$engine]['pass'] : 0);
        $invalid   = ((array_key_exists('invalid', $engineResults[$engine])) ? $engineResults[$engine]['invalid'] : 0);
        if (0 < $pass) {
          $passCount++;
        }
        if (0 < $invalid) {
          $testInvalid = TRUE;
        }
      }
    }
    
    foreach ($this->mEngines as $engine) {
      $enginePasses = FALSE;
      $engineCount  = 0;
      if (array_key_exists($engine, $engineResults)) {
        $engineCount = ((array_key_exists('count', $engineResults[$engine])) ? $engineResults[$engine]['count'] : 0);
        $pass = ((array_key_exists('pass', $engineResults[$engine])) ? $engineResults[$engine]['pass'] : 0);
        if (0 < $pass) {
          $enginePasses = TRUE;
        }
      }
      
      if ($testInvalid) {
        $count = $engineCount + 10000000;
      }
      else {
        if ($enginePasses) {
          $count = $engineCount;
        }
        else {
          $count = 0;
          if ($passCount < 2) {
            $count -= 4;
          }
          if (FALSE == $optional) {
            $count -= 2;
          }
          if (0 == $engineCount) {
            $count -= 1;
          }
        }
      }
    
      $this->mCounts[$engine][$testCaseName] = ($count + 8) + ($testCaseId / 10000000);
    }
  }
  
  /**
   *
   * Build or update testsequence table based on test result count per engine
   *
   * Sequence is:
   *   1) required tests with no results for engine and 0 or 1 passes for other engines    (-7)
   *   2) required tests with no passes for engine and 0 or 1 passes for other engines     (-6)
   *   3) optional tests with no results for engine and 0 or 1 passes for other engines    (-5)
   *   4) optional tests with no passes for engine and 0 or 1 passes for other engines     (-4)
   *   5) required tests with no results for engine and 2 or more passes for other engines (-3)
   *   6) required tests with no passes for engine and 2 or more passes for other engines  (-2)
   *   7) optional tests with no results for engine and 2 or more passes for other engines (-1)
   *   8) optional tests with no passes for engine and 2 or more passes for other engines  ( 0)
   *   9) tests with pass results                                                          (count)                        
   *   10) invalid tests                                                                   (count+1000000)
   *
   */
  function rebuild()
  {
    foreach ($this->mTestSuites->getTestSuites() as $testSuite) {
      $testSuiteName = $testSuite->getName();
      $sequenceQuery = $testSuite->getSequenceQuery();
       
      unset ($r);
      unset ($data);
      unset ($this->mCounts);
      
      print "Loading test cases for {$testSuiteName}\n";      

      $this->_loadTestCases($testSuiteName);

      print "Querying for results {$sequenceQuery}\n";
      
      $this->_loadResults($sequenceQuery);

      print "Processing results\n";
      
      foreach ($this->mTestCaseIds as $testCaseName => $testCaseId) {
        $optional = $this->mTestCaseOptional[$testCaseId];
        
        unset ($engineResults);
        $engineResults[''] = 0;
        if (array_key_exists($testCaseName, $this->mResults)) {
          foreach ($this->mResults[$testCaseName] as $engine => $engineData) {
            $engineResults[$engine]['count'] = 0;
            foreach ($engineData as $result) {
              $engineResults[$engine]['count']++;
              if (array_key_exists($result, $engineResults[$engine])) {
                $engineResults[$engine][$result]++;
              }
              else {
                $engineResults[$engine][$result] = 1;
              }
            }
          }
        }
        
        $this->_processTestCase($testCaseId, $testCaseName, $engineResults, $optional);
      }
          
      foreach ($this->mEngines as $engine) {
        print "Storing sequence for {$engine}\n";
        
        $engineCounts = $this->mCounts[$engine];
        asort($engineCounts);
        $engine = $this->encode($engine, TESTSEQUENCE_MAX_ENGINE);
        $sequence = -1;
        foreach ($engineCounts as $testCaseName => $count) {
          $sequence++;
          
          $testCaseId = $this->_getTestCaseId($testCaseName);
          
          $sql  = "INSERT INTO `testsequence` (`engine`, `testcase_id`, `sequence`) ";
          $sql .= "VALUES ('{$engine}', '{$testCaseId}', '{$sequence}') ";
          $sql .= "ON DUPLICATE KEY UPDATE `sequence` = '{$sequence}' ";

          $this->query($sql);
        }
      }
    }
  }
}

$worker = new Resequence();
$worker->rebuild();

?>