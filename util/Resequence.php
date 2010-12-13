<?php
////////////////////////////////////////////////////////////////////////////////
//
//  Copyright © 2010 World Wide Web Consortium, 
//  (Massachusetts Institute of Technology, European Research 
//  Consortium for Informatics and Mathematics, Keio 
//  University). All Rights Reserved. 
//  Copyright © 2010 Hewlett-Packard Development Company, L.P. 
// 
//  This work is distributed under the W3C¬ Software License 
//  [1] in the hope that it will be useful, but WITHOUT ANY 
//  WARRANTY; without even the implied warranty of 
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// 
//  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
//
//////////////////////////////////////////////////////////////////////////////// 

//////////////////////////////////////////////////////////////////////////////// 
//
//  resequence.php
//
//  Adapted from Mobile Test Harness [1]
//
//    File: resequence.php
//      Lines: 103-142
//
//  where herein specific contents provided by the original harness have
//  been adapted for CSS2.1 conformance testing. Separately, controls have
//  been added to allow entering data for user agents other than the one
//  accessing the harness, and the means by which test presentation order
//  is provided have been altered. Separately, the ability to request
//  only those tests in a particular named group has been added.
//
// [1] http://dev.w3.org/cvsweb/2007/mobile-test-harness/
//
//////////////////////////////////////////////////////////////////////////////// 

require_once("lib_test_harness/class.DBConnection.phi");

////////////////////////////////////////////////////////////////////////////////
//
//  class resequence
//
//  This class regenerates the testsequence table, maintaining an ordering 
//  of testcases per testsuite, per engine in order of least number of
//  results per testcase
//
//  This is meant to be run from by a periodic cron job or on the command line
//
////////////////////////////////////////////////////////////////////////////////
class Resequence extends DBConnection
{  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Instance variables.
  //
  ////////////////////////////////////////////////////////////////////////////
  var $mEngines;
  var $mTestSuites;
  var $mCounts;
  var $mTestCases;

  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor.
  //
  ////////////////////////////////////////////////////////////////////////////
  function __construct() 
  {
    parent::__construct();

    $sql = "SELECT DISTINCT `engine` FROM `useragents` WHERE `engine`!='' ORDER BY `engine`";
    $r = $this->query($sql);
    if (! $r->is_false()) {
      $dbEngines = $r->fetch_table();
      foreach ($dbEngines as $dbEngine) {
        $this->mEngines[] = $dbEngine['engine'];
      }
    }

    $sql = "SELECT DISTINCT `testsuite`, `sequence_query` FROM `testsuites` WHERE `active`!='0' ORDER BY `testsuite`";
    $r = $this->query($sql);
    if (! $r->is_false()) {
      $dbTestSuites = $r->fetch_table();
      foreach ($dbTestSuites as $dbTestSuite) {
        $this->mTestSuites[$dbTestSuite['testsuite']] = $dbTestSuite['sequence_query'];
      }
    }
  }
  
  protected function _loadTestCases($testSuite)
  {
    $sql  = "SELECT `id`, `testcase` FROM `testcases` ";
    $sql .= "WHERE `testsuite` = '{$testSuite}' ";
    
    $r = $this->query($sql);
    
    $testCases = $r->fetch_table();
    
    unset($this->mTestCases);
    
    foreach ($testCases as $testCase) {
      $this->mTestCases[$testCase['testcase']] = $testCase['id'];
    }
  }


  protected function _processTestcase($testCaseId, $testCase, $engineResults, $optional)
  {
    $passCount = 0;
    $testInvalid = FALSE;
    
    foreach ($this->mEngines as $engine) {
      if (array_key_exists($engine, $engineResults['count'])) {
        $pass      = $engineResults['pass'][$engine];
        $invalid   = $engineResults['invalid'][$engine];
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
      if (array_key_exists($engine, $engineResults['count'])) {
        $engineCount = $engineResults['count'][$engine];
        $pass = $engineResults['pass'][$engine];
        if (0 < $pass) {
          $enginePasses = TRUE;
        }
      }
      
      if ($testInvalid) {
        $count = $engineCount + 1000000;
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
    
      $this->mCounts[$engine][$testCase] = ($count + 16) + ($testCaseId / 1000000);
    }
  }
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Build or update testsequence table based on test result count per engine
  //
  //  Sequence is:
  //    1) required tests with no results for engine and 0 or 1 passes for other engines    (-7)
  //    2) required tests with no passes for engine and 0 or 1 passes for other engines     (-6)
  //    3) optional tests with no results for engine and 0 or 1 passes for other engines    (-5)
  //    4) optional tests with no passes for engine and 0 or 1 passes for other engines     (-4)
  //    5) required tests with no results for engine and 2 or more passes for other engines (-3)
  //    6) required tests with no passes for engine and 2 or more passes for other engines  (-2)
  //    7) optional tests with no results for engine and 2 or more passes for other engines (-1)
  //    8) optional tests with no passes for engine and 2 or more passes for other engines  ( 0)
  //    9) tests with pass results                                                          (count)                        
  //    10) invalid tests                                                                   (count+1000000)
  //
  ////////////////////////////////////////////////////////////////////////////
  function rebuild()
  {
    foreach ($this->mTestSuites as $testSuite => $sequenceQuery) {
      unset ($r);
      unset ($data);
      unset ($this->mCounts);
      unset ($this->mTestCases);
      
print "Querying for results for {$testSuite} ({$sequenceQuery})\n";      
      $sql  = "SELECT id, testcase, engine, flags, SUM(pass) as pass, ";
      $sql .= "SUM(fail) as fail, SUM(uncertain) as uncertain, ";
      $sql .= "SUM(invalid) as invalid, SUM(na) as na, ";
      $sql .= "COUNT(pass + fail + uncertain + invalid + na) as count ";
      $sql .= "FROM (";
      
      $sql .= "SELECT testcases.id, testcases.testsuite, ";
      $sql .= "testcases.testcase, testcases.flags, ";
      $sql .= "useragents.engine, testcases.active, ";
      $sql .= "result='pass' AS pass, ";
      $sql .= "result='fail' AS fail, result='uncertain' AS uncertain, ";
      $sql .= "result='invalid' AS invalid, result='na' AS na ";
      $sql .= "FROM testcases LEFT JOIN (results, useragents) ";
      $sql .= "ON (testcases.id=results.testcase_id AND results.useragent_id=useragents.id)) as t ";
      
      $sql .= "WHERE t.testsuite LIKE '{$sequenceQuery}' ";
      $sql .= "AND t.active='1' ";
      $sql .= "GROUP BY testcase, engine";
//print $sql;      
      $r = $this->query($sql);
      
      if (! $r->is_false()) {
        $data = $r->fetch_table();
        
        if ($data) {
print "Processing results\n";      
          $lastTestCaseId = -1;
          $lastTestCase   = '';
          foreach ($data as $result) {
            $testCaseId = $result['id'];
            $testCase   = $result['testcase'];
            if ($testCase != $lastTestCase) {
              if (-1 != $lastTestCaseId) {
                $this->_processTestcase($lastTestCaseId, $lastTestCase, $engineResults, $optional);
              }
              unset ($engineResults);
            }
            $flags    = $result['flags'];
            $optional = (FALSE !== stripos($flags, 'may')) || (FALSE !== stripos($flags, 'should'));
            $engine   = $result['engine'];
            $engineResults['pass'][$engine]       = $result['pass'];
            $engineResults['fail'][$engine]       = $result['fail'];
            $engineResults['uncertain'][$engine]  = $result['uncertain'];
            $engineResults['invalid'][$engine]    = $result['invalid'];
            $engineResults['na'][$engine]         = $result['na'];
            $engineResults['count'][$engine]      = $result['count'];
            
            $lastTestCaseId = $testCaseId;
            $lastTestCase   = $testCase;
          }
          $this->_processTestcase($testCaseId, $testCase, $engineResults, $optional);
          
          $this->_loadTestCases($testSuite);
          
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
              $sql .= "ON DUPLICATE KEY UPDATE sequence='{$sequence}'";

              $this->query($sql);
            }
          }
        }
      }
    }
  }
}

$worker = new Resequence();
$worker->rebuild();

?>