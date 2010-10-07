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
//  populate_ua.php
//
//  Adapted from Mobile Test Harness [1]
//
//    File: populate_ua.php
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
//require_once("./lib_css2.1_harness/class.test_suite.phi");
require_once("./lib_css2.1_harness/class.user_agent.phi");
//require_once("./lib_css2.1_harness/class.test_groups.phi");
//require_once("./lib_css2.1_harness/class.test_cases.phi");

////////////////////////////////////////////////////////////////////////////////
//
//  class testsuite_page
//
//  A class for generating a page for selecting how tests in a particular 
//  test suite will be presented for inspection. The page includes allowing
//  results to be entered for the user agent accessing the harness or for
//  a user provided user agent. The page further includes requesting
//  to see all of the test cases in the suite, only those test cases in a
//  particular named group, or individual cases. Requests can also be made
//  that successive test cases be presented in the order that they are listed
//  or sorted according to how many response have been entered for the user 
//  agent in question.
//
////////////////////////////////////////////////////////////////////////////////
class populate_ua_page extends css_page
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
  //  The URL accessing the page generated by an object of this class must
  //  identify a valid testsuite:
  //
  //    testsuite.php?s=[testsuite]
  //
  //  If no test suite is identified or if the identified test suite is
  //  not valid, then an error is generated.
  //
  //  The URL accessing the page generated by an object of this class may
  //  also identify an alternate user agent for which data is to be entered:
  //
  //     testsuite.php?s=[testsuite]&u=[id]
  //     testsuite.php?s=[testsuite]&u=[user-agent string]
  //
  //  If no alternate user agent is identified or if the identified user
  //  agent is not valid, then the user agent string of the browser accessing
  //  the harness is used.
  //
  //  All other URL parameters are ignored.
  //
  ////////////////////////////////////////////////////////////////////////////
  function populate_ua_page() 
  {
    parent::css_page();

  }  
  
  ////////////////////////////////////////////////////////////////////////////
  //
  // write_body_content()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_content($indent = '') {

    $db = new db_connection();

    // update results ua ids
    echo "<table>";
    $sql = "SELECT id, useragent FROM results WHERE useragent_id='0' LIMIT 5000";
    $r = $db->query($sql);
    $db_list = $r->fetch_table(); 
    foreach ($db_list as $db_data) {
      $ua = new user_agent($db_data['useragent']);
        
      echo "<tr><td colspan='999'>" . $db_data['useragent'];

      echo "<tr><td>&nbsp;<td>";
      $ua->write();

      $ua->update();
        
      $sql = "UPDATE results SET useragent_id='{$ua->get_id()}', modified=modified WHERE id='{$db_data['id']}'";
      $db->query($sql);
    }
    echo "</table>";

    // update results testcase_ids
    echo "<table>";
    $sql = "SELECT id, testsuite, testcase FROM results WHERE testcase_id='0' LIMIT 5000";
    $r = $db->query($sql);
    $db_list = $r->fetch_table(); 
    foreach ($db_list as $db_data) {
      $sql = "SELECT id FROM testcases WHERE testsuite='{$db_data['testsuite']}' AND testcase='{$db_data['testcase']}'";
      $r = $db->query($sql);
      $testcase = $r->fetch_table();
      $testcase_id = $testcase[0]['id'];
      echo "<tr><td>" . $db_data['testsuite'] . "<td>" . $db_data['testcase'];
      echo "<td>" . $testcase_id;
      $sql = "UPDATE results SET testcase_id='{$testcase_id}', modified=modified WHERE id='{$db_data['id']}'";
      $db->query($sql);
    }
    echo "</table>";

    // Verify UA
    echo "<table>";
    $sql = "SELECT DISTINCT useragent, useragent_id FROM results";
    $r = $db->query($sql);
    $db_list = $r->fetch_table(); 
    foreach ($db_list as $db_data) {
      $ua = new user_agent($db_data['useragent_id']);
        
      if ($ua->get_ua_string() != $db_data['useragent']) {
        echo "<tr><td>ERROR<td>{$db_data['useragent_id']}<td>{$db_data['useragent']}";
      }
      else {
//        echo "<tr><td>&nbsp;<td>{$db_data['useragent_id']}<td>{$db_data['useragent']}";
      }
    }
    echo "</table>";
    
    // Verify testcase id
    echo "<table>";
    $sql = "SELECT DISTINCT testsuite, testcase, testcase_id FROM results WHERE testcase_id!='0'";
    $r = $db->query($sql);
    $db_list = $r->fetch_table(); 
    foreach ($db_list as $db_data) {
      $sql = "SELECT testsuite, testcase FROM testcases WHERE id='{$db_data['testcase_id']}'";
      $r = $db->query($sql);
      $testcase = $r->fetch_table();
        
      if (($testcase[0]['testsuite'] != $db_data['testsuite']) || 
          ($testcase[0]['testcase'] != $db_data['testcase'])) {
        echo "<tr><td>ERROR<td>{$db_data['testcase_id']}<td>{$db_data['testsuite']}<td>{$db_data['testcase']}";
      }
      else {
//        echo "<tr><td>&nbsp;<td>{$db_data['testcase_id']}<td>{$db_data['testsuite']}<td>{$db_data['testcase']}";
      }
    }
    echo "</table>";
    
    
  }
}

$page = new populate_ua_page();
$page -> write();

?>
