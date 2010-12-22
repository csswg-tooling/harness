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

require_once("lib/HarnessPage.php");
require_once("lib/TestSuite.php");
require_once("lib/Groups.php");
require_once("lib/TestCases.php");

////////////////////////////////////////////////////////////////////////////////
//
//  class testsuite_page
//
//  A class for generating the page for selecting how tests will be presented
//  for inspection.
//
////////////////////////////////////////////////////////////////////////////////
class ReviewPage extends HarnessPage
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
  function __construct() 
  {
    parent::__construct();

    if(isset($_GET['s'])) {
      $this->m_test_suite = new test_suite($_GET['s']);
    } else {
      $msg = 'No test suite identified.';
      $this->trigger_client_error($msg, E_USER_ERROR);
    }

    $this->m_test_groups = new test_groups(
      $this->m_test_suite->get_name());

    $this->m_test_cases = new test_cases(
      $this->m_test_suite->get_name());
  }  
  
  function getPageTitle()
  {
    $title = parent::getPageTitle();
    return "{$title} Results";
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_head_script()
  //
  //  This <script> element provides script available to the document
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_head_script($indent = '')
  {
    echo $indent . "<script type='text/javascript'>\n";
    
    echo $indent . "  onunload=function() {\n";
    echo $indent . "    document.result_form.g.disabled = false;\n";
    echo $indent . "    document.result_form.c.disabled = false;\n";
    echo $indent . "  }\n";
    echo $indent . "  function filterTypes() {\n";
    echo $indent . "    if (document.result_form.t[0].checked) {\n";
    echo $indent . "      document.result_form.g.disabled = true;\n";
    echo $indent . "      document.result_form.c.disabled = true;\n";
    echo $indent . "    }\n";
    echo $indent . "    if (document.result_form.t[1].checked) {\n";
    echo $indent . "      document.result_form.g.disabled = false;\n";
    echo $indent . "      document.result_form.c.disabled = true;\n";
    echo $indent . "    }\n";
    echo $indent . "    if (document.result_form.t[2].checked) {\n";
    echo $indent . "      document.result_form.g.disabled = true;\n";
    echo $indent . "      document.result_form.c.disabled = false;\n";
    echo $indent . "    }\n";
    echo $indent . "    return true;\n";
    echo $indent . "  }\n";
    
    echo $indent . "</script>\n";;  
  }
  
  
  ////////////////////////////////////////////////////////////////////////////
  //
  // write_body_content()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_content($indent = '')
  {

    echo $indent . "<p>\n";
    echo $indent . "  The " . $this->m_test_suite->get_title() . " test suite contains ";
    echo              $this->m_test_cases->get_count() . " test cases. \n";
    echo $indent . "  You can choose to review:\n";
    echo $indent . "</p>\n";

    echo $indent . "<form action='results' method='get' name='result_form' onSubmit='return filterTypes();'>\n";
    echo $indent . "  <input type='hidden' name='s' value='" .$this->m_test_suite->get_name() . "' />\n";
    echo $indent . "  <p>\n";
    echo $indent . "    <input type='radio' name='t' value='0' checked />\n";
    echo $indent . "    The full test suite<br />\n";
    
    if (0 < $this->m_test_groups->get_count()) {
      echo $indent . "    <input type='radio' name='t' value='1' />\n";
      echo $indent . "    A group of test cases: \n";
      $this->m_test_groups->write($indent);
      
      echo $indent . "  <br />\n";
    }
    
    echo $indent . "    <input type='radio' name='t' value='2' />\n";
    echo $indent . "    A single test case:\n";
    $this->m_test_cases->write($indent);
    echo $indent . "    <br />\n";
    echo $indent . "  </p>\n";

    echo $indent . "  <p>\n";
    echo $indent . "    Do not display tests that:<br />\n";
    echo $indent . "    <input type='checkbox' name='f[]' value='1'> Meet exit criteria<br />\n";
    echo $indent . "    <input type='checkbox' name='f[]' value='2'> Have blocking failures<br />\n";
    echo $indent . "    <input type='checkbox' name='f[]' value='4'> Lack sufficient data<br />\n";
    echo $indent . "    <input type='checkbox' name='f[]' value='8'> Have been reported as invalid<br />\n";
    echo $indent . "    <input type='checkbox' name='f[]' value='16'> Are not required<br />\n";
    echo $indent . "  </p>\n";

    echo $indent . "  <input type='submit' value='Go' />\n";
    echo $indent . "</form>\n";

  }
}

$page = new ReviewPage();
$page->write();

?>