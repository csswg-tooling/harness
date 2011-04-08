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
require_once('lib/Results.php');
require_once('lib/Flags.php');

/**
 * This class regenerates the testsequence table, maintaining an ordering 
 * of testcases per testsuite, per engine in order of least number of
 * results per testcase
 *
 * This is meant to be run from by a periodic cron job or on the command line
 */
class Resequence extends CmdLineWorker
{
  protected $mTestSuites;
  protected $mEngineNames;
  protected $mCounts;

  function __construct() 
  {
    parent::__construct();

    $this->mTestSuites = new TestSuites();
  }
  

  protected function _processTestcase($testCaseId, $engineResults, $optional, $index)
  {
    $passCount = 0;
    $testInvalid = FALSE;
    
    foreach ($this->mEngineNames as $engineName) {
      if ($engineResults && array_key_exists($engineName, $engineResults)) {
        $pass      = $engineResults[$engineName]['pass'];
        $invalid   = $engineResults[$engineName]['invalid'];
        if (0 < $pass) {
          $passCount++;
        }
        if (0 < $invalid) {
          $testInvalid = TRUE;
        }
      }
    }
    
    foreach ($this->mEngineNames as $engineName) {
      $enginePasses = FALSE;
      $engineCount  = 0;
      if ($engineResults && array_key_exists($engineName, $engineResults)) {
        $engineCount = ((array_key_exists('count', $engineResults[$engineName])) ? $engineResults[$engineName]['count'] : 0);
        $pass = ((array_key_exists('pass', $engineResults[$engineName])) ? $engineResults[$engineName]['pass'] : 0);
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
    
      $this->mCounts[$engineName][$testCaseId] = ($count + 8) + ($index / 10000000);
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
      if ($testSuite->isLocked()) {
        continue;
      }
    
      $testSuiteName = $testSuite->getName();
       
      unset ($this->mCounts);
      
      print "Loading results for {$testSuiteName}\n";      

      $results = new Results($testSuite);

      print "Processing results\n";
      
      $this->mEngineNames = $results->getEngineNames();
      $this->mEngineNames[] = '-no-data-';  // magic engine name for engines with no result data
      $testCases = $results->getTestCases();
      
      $index = 0;
      foreach ($testCases as $testCaseId => $testCaseData) {
        $index++;

        $flags = new Flags($testCaseData['flags']);
        $optional = $testSuite->testIsOptional($flags);

        $engineResults = $results->getResultCountsFor($testCaseId);
        
        $this->_processTestCase($testCaseId, $engineResults, $optional, $index);
      }
      
      $testSuiteName = $this->encode($testSuiteName, TESTSEQUENCE_MAX_TESTSUITE);
      
      foreach ($this->mEngineNames as $engineName) {
        print "Storing sequence for {$engineName}\n";
        
        $engineCounts = $this->mCounts[$engineName];
        asort($engineCounts);
        $engineName = $this->encode($engineName, TESTSEQUENCE_MAX_ENGINE);
        $sequence = -1;
        foreach ($engineCounts as $testCaseId => $count) {
          $sequence++;
          
          $sql  = "INSERT INTO `testsequence` ";
          $sql .= "(`testsuite`, `engine`, `testcase_id`, `sequence`) ";
          $sql .= "VALUES ('{$testSuiteName}', '{$engineName}', '{$testCaseId}', '{$sequence}') ";
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