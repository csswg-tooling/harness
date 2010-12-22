<?php
/*******************************************************************************
 *
 *  Copyright © 2010 Hewlett-Packard Development Company, L.P. 
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

define('COMMAND_LINE', TRUE);

require_once('lib/DBConnection.php');

/**
 * This class regenerates the testsequence table, maintaining an ordering 
 * of testcases per testsuite, per engine in order of least number of
 * results per testcase
 *
 * This is meant to be run from by a periodic cron job or on the command line
 */
class Resequence extends DBConnection
{
  var $mEngines;
  var $mTestSuites;
  var $mCounts;
  var $mTestCases;
  var $mTestCaseOptional;
  var $mResults;

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

    $sql  = "SELECT DISTINCT `testsuite`, `sequence_query` ";
    $sql .= "FROM `testsuites` ";
    $sql .= "WHERE `active` = '1' ";
    $sql .= "ORDER BY `testsuite` ";
    $r = $this->query($sql);
    while ($dbTestSuite = $r->fetchRow()) {
      $this->mTestSuites[$dbTestSuite['testsuite']] = $dbTestSuite['sequence_query'];
    }
  }
  
  protected function _loadTestCases($testSuite)
  {
    unset ($this->mTestCases);
    unset ($this->mTestCaseOptional);
    
    $sql  = "SELECT `id`, `testcase`, `flags` ";
    $sql .= "FROM `testcases` ";
    $sql .= "WHERE `testsuite` = '{$testSuite}' ";
    $sql .= "AND `active` = '1' ";
    
    $r = $this->query($sql);
    while ($testCaseData = $r->fetchRow()) {
      $testCase   = $testCaseData['testcase'];
      $testCaseId = $testCaseData['id'];
      $flags      = $testCaseData['flags'];

      $optional = (FALSE !== stripos($flags, 'may')) || (FALSE !== stripos($flags, 'should'));
      
      $this->mTestCases[$testCase] = $testCaseId;
      $this->mTestCaseOptional[$testCaseId] = $optional;
    }
  }
  
  protected function _loadResults($testSuiteQuery)
  {
    unset ($this->mResults);

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
        $testCase = $resultData['testcase'];
        $result   = $resultData['result'];

        $this->mResults[$testCase][$engine][] = $result;
      }
    }
  }


  protected function _processTestcase($testCaseId, $testCase, $engineResults, $optional)
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
    
      $this->mCounts[$engine][$testCase] = ($count + 8) + ($testCaseId / 10000000);
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
    foreach ($this->mTestSuites as $testSuite => $sequenceQuery) {
      unset ($r);
      unset ($data);
      unset ($this->mCounts);
      
      print "Loading test cases for {$testSuite}\n";      

      $this->_loadTestCases($testSuite);

      print "Querying for results {$sequenceQuery}\n";
      
      $this->_loadResults($sequenceQuery);

      print "Processing results\n";
      
      foreach ($this->mTestCases as $testCase => $testCaseId) {
        $optional = $this->mTestCaseOptional[$testCaseId];
        
        unset ($engineResults);
        $engineResults[''] = 0;
        if (array_key_exists($testCase, $this->mResults)) {
          foreach ($this->mResults[$testCase] as $engine => $engineData) {
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
        
        $this->_processTestCase($testCaseId, $testCase, $engineResults, $optional);
      }
          
      foreach ($this->mEngines as $engine) {
        print "Storing sequence for {$engine}\n";
        
        $engineCounts = $this->mCounts[$engine];
        asort($engineCounts);
        $sequence = 0;
        foreach ($engineCounts as $testCase => $count) {
          $sequence++;
          
          $testCaseId = $this->mTestCases[$testCase];
          
          $sql  = "INSERT INTO testsequence (engine, testcase_id, sequence) ";
          $sql .= "VALUES ('{$engine}', '{$testCaseId}', '{$sequence}') ";
          $sql .= "ON DUPLICATE KEY UPDATE sequence='{$sequence}' ";

          $this->query($sql);
        }
      }
    }
  }
}

$worker = new Resequence();
$worker->rebuild();

?>