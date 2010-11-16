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

require_once("./lib_css2.1_harness/class.css_page.phi");
require_once("./lib_test_harness/class.db_connection.phi");
//require_once("./lib_css2.1_harness/class.test_suite.phi");
//require_once("./lib_css2.1_harness/class.user_agent.phi");
//require_once("./lib_css2.1_harness/class.test_groups.phi");
//require_once("./lib_css2.1_harness/class.test_cases.phi");

////////////////////////////////////////////////////////////////////////////////
//
//  class resequence
//
//  This class regenerates the testsequence table, maintaining an ordering 
//  of testcases per testsuite, per engine in order of least number of
//  results per testcase
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

  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor.
  //
  ////////////////////////////////////////////////////////////////////////////
  function resequence() 
  {
    parent::db_connection();

    $sql = "SELECT DISTINCT `engine` FROM `useragents` ORDER BY `engine`";
    $r = $this->query($sql);
    if (! $r->is_false()) {
      $db_engines = $r->fetch_table();
      foreach ($db_engines as $db_engine) {
        $this->m_engines[] = $db_engine['engine'];
      }
    }

    $sql = "SELECT DISTINCT `testsuite` FROM `testsuites` WHERE `active`!='0' ORDER BY `testsuite`";
    $r = $this->query($sql);
    if (! $r->is_false()) {
      $db_testsuites = $r->fetch_table();
      foreach ($db_testsuites as $db_testsuite) {
        $this->m_testsuites[] = $db_testsuite['testsuite'];
      }
    }

  }
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Copy test results from one suite to another.
  //
  //  id of source result is preserved in original_id
  //
  ////////////////////////////////////////////////////////////////////////////
  function rebuild()
  {
    foreach ($this->m_testsuites as $testsuite) {
      foreach ($this->m_engines as $engine) {
        $sql  = "SELECT testcases.id, count FROM testcases LEFT JOIN ( ";
        $sql .= "SELECT results.testcase_id, COUNT(results.result) AS count ";
        $sql .= "FROM results LEFT JOIN useragents ";
        $sql .= "ON results.useragent_id=useragents.id ";
        $sql .= "WHERE engine='{$engine}' ";
        $sql .= "GROUP BY results.testcase_id, useragents.engine ";
        $sql .= ") AS t1 ";
        $sql .= "ON testcases.id=t1.testcase_id ";
        $sql .= "WHERE testcases.testsuite='{$testsuite}' ";
        $sql .= "AND testcases.active!='0' ";
        $sql .= "ORDER BY count, id ";
        
        $r = $this->query($sql);
        if (! $r->is_false()) {
          $result_counts = $r->fetch_table();
          $sequence = 0;
          foreach ($result_counts as $result_count) {
            $sequence++;
            $testcase_id = $result_count['id'];

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

$worker = new resequence();
$worker->rebuild();

?>