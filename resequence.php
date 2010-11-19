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

require_once("lib_test_harness/class.db_connection.phi");

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
class resequence extends db_connection
{  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Instance variables.
  //
  ////////////////////////////////////////////////////////////////////////////
  var $m_engines;
  var $m_testsuites;
  var $m_counts;

  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor.
  //
  ////////////////////////////////////////////////////////////////////////////
  function resequence() 
  {
    parent::db_connection();

    $sql = "SELECT DISTINCT `engine` FROM `useragents` WHERE `engine`!='' ORDER BY `engine`";
    $r = $this->query($sql);
    if (! $r->is_false()) {
      $db_engines = $r->fetch_table();
      foreach ($db_engines as $db_engine) {
        $this->m_engines[] = $db_engine['engine'];
      }
    }
    $this->m_engine_count = count($this->m_engines);

    $sql = "SELECT DISTINCT `testsuite` FROM `testsuites` WHERE `active`!='0' ORDER BY `testsuite`";
    $r = $this->query($sql);
    if (! $r->is_false()) {
      $db_testsuites = $r->fetch_table();
      foreach ($db_testsuites as $db_testsuite) {
        $this->m_testsuites[] = $db_testsuite['testsuite'];
      }
    }
  }

  function _process_testcase($testcase_id, $engine_results, $optional)
  {
    $pass_count = 0;
    $test_invalid = false;
    
    foreach ($this->m_engines as $engine) {
      if (array_key_exists($engine, $engine_results['count'])) {
        $pass      = $engine_results['pass'][$engine];
        $invalid   = $engine_results['invalid'][$engine];
        if (0 < $pass) {
          $pass_count++;
        }
        if (0 < $invalid) {
          $test_invalid = true;
        }
      }
    }
    
    foreach ($this->m_engines as $engine) {
      $engine_passes = false;
      $engine_count  = 0;
      if (array_key_exists($engine, $engine_results['count'])) {
        $engine_count = $engine_results['count'][$engine];
        $pass = $engine_results['pass'][$engine];
        if (0 < $pass) {
          $engine_passes = true;
        }
      }
      
      if ($test_invalid) {
        $count = $engine_count + 1000000;
      }
      else {
        if ($engine_passes) {
          $count = $engine_count;
        }
        else {
          $count = 0;
          if ($pass_count < 2) {
            $count -= 4;
          }
          if (false == $optional) {
            $count -= 2;
          }
          if (0 == $engine_count) {
            $count -= 1;
          }
        }
      }
    
      $this->m_counts[$engine][$testcase_id] = ($count + 16) + ($testcase_id / 1000000);
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
    foreach ($this->m_testsuites as $testsuite) {
print "Querying for results for {$testsuite}\n";      
      $sql  = "SELECT id, engine, flags, SUM(pass) as pass, ";
      $sql .= "SUM(fail) as fail, SUM(uncertain) as uncertain, ";
      $sql .= "SUM(invalid) as invalid, SUM(na) as na, ";
      $sql .= "COUNT(pass + fail + uncertain + invalid + na) as count ";
      $sql .= "FROM (";
      
      $sql .= "SELECT testcases.id, testcases.testsuite, ";
      $sql .= "testcases.flags, ";
      $sql .= "useragents.engine, testcases.active, ";
      $sql .= "result='pass' AS pass, ";
      $sql .= "result='fail' AS fail, result='uncertain' AS uncertain, ";
      $sql .= "result='invalid' AS invalid, result='na' AS na ";
      $sql .= "FROM testcases LEFT JOIN (results, useragents) ";
      $sql .= "ON (testcases.id=results.testcase_id AND results.useragent_id=useragents.id)) as t ";
      
      $sql .= "WHERE t.testsuite='{$testsuite}' ";
      $sql .= "AND t.active='1' ";
      $sql .= "GROUP BY id, engine";
//print $sql;      
      $r = $this->query($sql);
      
      if (! $r->is_false()) {
        $data = $r->fetch_table();
        
        if ($data) {
print "Processing results\n";      
          unset ($this->m_counts);
          $last_testcase_id = -1;
          foreach ($data as $result) {
            $testcase_id = $result['id'];
            if ($testcase_id != $last_testcase_id) {
              if (-1 != $last_testcase_id) {
                $this->_process_testcase($last_testcase_id, $engine_results, $optional);
              }
              unset ($engine_results);
            }
            $flags       = $result['flags'];
            $optional = (FALSE !== stripos($flags, 'may')) || (FALSE !== stripos($flags, 'should'));
            $engine = $result['engine'];
            $engine_results['pass'][$engine]      = $result['pass'];
            $engine_results['fail'][$engine]      = $result['fail'];
            $engine_results['uncertain'][$engine] = $result['uncertain'];
            $engine_results['invalid'][$engine]   = $result['invalid'];
            $engine_results['na'][$engine]        = $result['na'];
            $engine_results['count'][$engine]     = $result['count'];
            
            $last_testcase_id = $testcase_id;
          }
          $this->_process_testcase($testcase_id, $engine_results, $optional);
          
          foreach ($this->m_engines as $engine) {
print "Storing sequence for {$engine}\n";      
            $engine_counts = $this->m_counts[$engine];
            asort($engine_counts);
            $sequence = 0;
            foreach ($engine_counts as $testcase_id => $count) {
              $sequence++;
              
              $sql  = "INSERT INTO testsequence (engine, testcase_id, sequence) ";
              $sql .= "VALUES ('{$engine}', '{$testcase_id}', '{$sequence}') ";
              $sql .= "ON DUPLICATE KEY UPDATE sequence='{$sequence}'";
              
              $this->query($sql);
            }
          }
        }
      }
    }
  }
}

$worker = new resequence();
$worker->rebuild();

?>