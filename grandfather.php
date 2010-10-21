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
//  grandfather.php
//
//  Adapted from Mobile Test Harness [1]
//
//    File: grandfather.php
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
//  class grandfather
//
//  This class copies test results fom one testsuite to another
//  The intended usage is when a new version of a testuite is posted
//  and not all tests have changed. 
//  
//  To use this class, set a value of '1' in the grandfather field for
//  all testcases where results should be copied, then call copy_results
//  with the relevant test suites.
//
//  The copy function resets the grandfather field to '2' to avoid multiple
//  copies of results. 
//  
//  The copy function is also limited to copy results from 1000 testcases
//  at a time to avoid timeout issues. Run the script repeatedly if more than
//  1000 testcases are present.
//
////////////////////////////////////////////////////////////////////////////////
class grandfather extends css_page
{  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Instance variables.
  //
  ////////////////////////////////////////////////////////////////////////////


  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor.
  //
  ////////////////////////////////////////////////////////////////////////////
  function grandfather() 
  {
    parent::css_page();

  }
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Copy test results from one suite to another.
  //
  //  id of source result is preserved in original_id
  //
  ////////////////////////////////////////////////////////////////////////////
  function copy_results($from_suite, $to_suite)
  {
    $db = new db_connection();

    echo "<table>";
    $sql = "SELECT id, testcase FROM testcases WHERE testsuite='{$to_suite}' AND grandfather='1' LIMIT 1000";
    $r = $db->query($sql);
    $db_list = $r->fetch_table(); 
    foreach ($db_list as $db_data) {
      $new_testcase_id = $db_data['id'];
      $testcase = $db_data['testcase'];
      
      echo "<tr><td>" . $new_testcase_id . "<td colspan='999'>" . $testcase;
      
      $sql  = "SELECT id FROM testcases ";
      $sql .= "WHERE testcases.testcase='{$testcase}' AND testcases.testsuite='{$from_suite}' ";
      $sql .= "LIMIT 1";
      $r = $db->query($sql);
      if (! $r->is_false()) {
        $testcase_list = $r->fetch_table();
        $old_testcase_id = $testcase_list[0]['id'];
        
        $sql  = "SELECT id, useragent_id, source, result, modified FROM results ";
        $sql .= "WHERE testcase_id='{$old_testcase_id}'";
        //print "<td>" . $sql;      
        $r = $db->query($sql);
        if (! $r->is_false()) {
          $result_list = $r->fetch_table();
          foreach ($result_list as $result_data) {
            $result_id    = $result_data['id'];
            $useragent_id = $result_data['useragent_id'];
            $source       = $result_data['source'];
            $result       = $result_data['result'];
            $modified     = $result_data['modified'];
            
            echo "<tr><td>&nbsp;<td>" . $useragent_id . "<td>" . $source . "<td>" . $result . "<td>" . $modified;
            
            $sql  = "INSERT INTO results (testcase_id, useragent_id, source, original_id, result, modified) VALUES ";
            $sql .= "('{$new_testcase_id}', '{$useragent_id}', '{$source}', '{$result_id}', '{$result}', '{$modified}')";
            //print "<td>" . $sql;        
            $db->query($sql);
          }
        }
      }
      
      $sql  = "UPDATE testcases SET grandfather='2' WHERE id='{$new_testcase_id}'";
      //print "<td>". $sql;      
      $db->query($sql);      
    }
    echo "</table>";
  }
  
  ////////////////////////////////////////////////////////////////////////////
  //
  // write_body_content()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_content($indent = '') {

    $this->copy_results('CSS21_HTML_RC1', 'CSS21_HTML_RC2');
    $this->copy_results('CSS21_XHTML_RC1', 'CSS21_XHTML_RC2');

  }
}

$page = new grandfather();
$page -> write();

?>
