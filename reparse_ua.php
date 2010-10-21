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
//  reparse_ua.php
//
//  Adapted from Mobile Test Harness [1]
//
//    File: reparse_ua.php
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
require_once("./lib_css2.1_harness/class.user_agent.phi");
//require_once("./lib_css2.1_harness/class.test_groups.phi");
//require_once("./lib_css2.1_harness/class.test_cases.phi");

////////////////////////////////////////////////////////////////////////////////
//
//  class reparse_ua
//
//  This class reparses the useragent string and updates the browser and 
//  engine data. Used when the useragent parsing algorithm changes.
//
////////////////////////////////////////////////////////////////////////////////
class reparse_ua extends css_page
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
  function reparse_ua() 
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
    
    echo $indent . "<table>";
    $sql = "SELECT id FROM useragents";
    $r = $db->query($sql);
    $db_list = $r->fetch_table(); 
    foreach ($db_list as $db_data) {
      $ua_id = $db_data['id'];
      
      $ua = new user_agent($ua_id);
      echo $indent . "  <tr><td>" . $ua_id . "<td colspan='999'>" . $ua->get_ua_string();
      echo $indent . "  <tr><td>&nbsp;<td>" . $ua->get_engine();
      echo "<td>" . $ua->get_engine_version();
      echo "<td>" . $ua->get_browser();
      echo "<td>" . $ua->get_browser_version();
      echo "<td>" . $ua->get_platform();
      $ua->reparse();
      echo $indent . "  <tr><td>&nbsp;<td>" . $ua->get_engine();
      echo "<td>" . $ua->get_engine_version();
      echo "<td>" . $ua->get_browser();
      echo "<td>" . $ua->get_browser_version();
      echo "<td>" . $ua->get_platform();
    }
    echo $indent . "</table>";
    
  }
}

$page = new reparse_ua();
$page -> write();

?>
