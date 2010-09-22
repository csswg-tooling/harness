<?php
////////////////////////////////////////////////////////////////////////////////
//
//  Copyright � 2007 World Wide Web Consortium, 
//  (Massachusetts Institute of Technology, European Research 
//  Consortium for Informatics and Mathematics, Keio 
//  University). All Rights Reserved. 
//  Copyright � 2008 Hewlett-Packard Development Company, L.P. 
// 
//  This work is distributed under the W3C� Software License 
//  [1] in the hope that it will be useful, but WITHOUT ANY 
//  WARRANTY; without even the implied warranty of 
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// 
//  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
//
//////////////////////////////////////////////////////////////////////////////// 

//////////////////////////////////////////////////////////////////////////////// 
//
//  review.php
//
//  Adapted from Mobile Test Harness [1]
//
//    File: results.php
//      Lines: 23-29
//
//  where herein specific contents provided by the original harness have
//  been adapted for CSS2.1 conformance testing. Separately, controls have
//  been added to request only those tests in a particular named group or
//  only for a single test case.
//
//  Currently, this file represents a place holder for a work in progress.
//  Additional requested functionality includes the following:
//
//  1) Interface controls for generating reports based on various parameters.
//     URLs to these reports should be short and clean so they can be passed
//     around in blogs/IM/email etc.
//
//  2) Report pass/fail scores for the whole test suite with various cross
//     tabulations and consolidations.
//
//  3) Report consolidated results for various user agent strings under
//     one category name. E.g. consolidate results for all UA strings that
//     represent Opera 9.25 Beta 1 regardless of OS and localization.
//
//  4) Report consolidated pass/fail scores for individual named groups
//     of tests.
//
//  5) Prettier reports.
//
// [1] http://dev.w3.org/cvsweb/2007/mobile-test-harness/
//
//////////////////////////////////////////////////////////////////////////////// 

require_once("./lib_css2.1_harness/class.css_page.phi");
require_once("./lib_css2.1_harness/class.test_suite.phi");
require_once("./lib_css2.1_harness/class.test_groups.phi");
require_once("./lib_css2.1_harness/class.test_cases.phi");

////////////////////////////////////////////////////////////////////////////////
//
//  class testsuite_page
//
//  A class for generating the page for selecting how tests will be presented
//  for inspection.
//
////////////////////////////////////////////////////////////////////////////////
class review_page extends css_page
{  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Instance variables.
  //
  ////////////////////////////////////////////////////////////////////////////
  var $m_test_suite;
  var $m_test_groups;
  var $m_test_cases;

  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor.
  //
  //  The URL accessing the page generated by an object of this class must
  //  identify a valid testsuite:
  //
  //    review.php?s=[testsuite]
  //
  //  If no test suite is identified or if the identified test suite is
  //  not valid, then an error is generated.
  //
  //  All other URL parameters are ignored.
  //
  ////////////////////////////////////////////////////////////////////////////
  function review_page() 
  {
    parent::css_page();

    if(isset($_GET['s'])) {
      $this->m_test_suite = new test_suite($_GET['s']);
    } else {
      $msg = 'No test suite identified.';
      $this->trigger_client_error($msg, E_USER_ERROR);
    }

    $this->m_page_title = $this->m_test_suite->get_title() . 
      ' CSS 2.1 Test Suite';
    
    $this->m_content_title = $this->m_test_suite->get_title() . 
      ' Test Suite for CSS 2.1 Conformance Testing';

    $this->m_test_groups = new test_groups(
      $this->m_test_suite->get_name());

    $this->m_test_cases = new test_cases(
      $this->m_test_suite->get_name());

    // $this->m_resource_id 
    //   = '$Id: review.php,v 1.2 2008/08/12 18:26:59 dberfang Exp $';    
  }  
  
  ////////////////////////////////////////////////////////////////////////////
  //
  // write_body_content()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_content($indent = '')
  {

    echo $indent . '<p>'."\n";
    echo $indent . '  The ';
    echo $this->m_test_suite->get_title();
    echo ' test suite contains ';
    echo $this->m_test_cases->get_count();
    echo ' test cases. '."\n";
    echo 'You can choose to review:'."\n";
    echo $indent . '</p>'."\n";
    echo $indent . '<ul>'."\n";
    echo $indent . '<li>'."\n";
    echo $indent . '  <form action="results.php" method="get">'."\n";

    echo $indent . '    <input type="hidden" name="s" value="';
    echo $this->m_test_suite->get_name();
    echo '" />';
    echo $indent . '  The full test suite '."\n";
    echo $indent . '  <select name="o" style="width: 15em">'."\n";
    echo $indent . '    <option selected="selected" value="0">';
    echo 'in order</option>'."\n";
    echo $indent . '    <option value="1">with least tested cases first';
    echo '</option>'."\n";
    echo $indent . '  </select>'."\n";
    echo $indent . '  <input type="submit" value="Go" />'."\n";
    echo $indent . '  </form>'."\n";
    
    echo $indent . '</li>'."\n";
    echo $indent . '<li>'."\n";
    
    echo $indent . '  <form action="results.php" method="get">'."\n";
    echo $indent . '    <input type="hidden" name="s" value="';
    echo $this->m_test_suite->get_name();
    echo '" />';
    echo $indent . '  A group of test cases: '."\n";
    $this->m_test_groups->write($indent);
    echo $indent . '  <select name="o" style="width: 15em">'."\n";
    echo $indent . '    <option selected="selected" value="0">';
    echo 'in order</option>'."\n";
    echo $indent . '    <option value="1">with least tested cases ';
    echo 'first</option>'."\n";
    echo $indent . '  </select>'."\n";
    echo $indent . '  <input type="submit" value="Go" />'."\n";
    echo $indent . '  </form>'."\n";
    
    echo $indent . '</li>'."\n";
    echo $indent . '<li>'."\n";
    
    echo $indent . '  <form action="results.php" method="get">'."\n";
    echo $indent . '    <input type="hidden" name="s" value="';
    echo $this->m_test_suite->get_name();
    echo '" />';
    echo $indent . '  A single test case:'."\n";
    $this->m_test_cases->write($indent);
    echo $indent . '  <input type="submit" value="Go" />'."\n";
    echo $indent . '  </form>'."\n";
    
    echo $indent . '</li>'."\n";
    echo $indent . '</ul>'."\n";

  }
}

$page = new review_page();
$page -> write();

?>
