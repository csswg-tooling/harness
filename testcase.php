<?php
////////////////////////////////////////////////////////////////////////////////
//
//  Copyright © 2007 World Wide Web Consortium, 
//  (Massachusetts Institute of Technology, European Research 
//  Consortium for Informatics and Mathematics, Keio 
//  University). All Rights Reserved. 
//  Copyright © 2008 Hewlett-Packard Development Company, L.P. 
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
//  testcase.php
//
//  Adapted from Mobile Test Harness [1]
//
//    File: harness.php
//      Lines: 176-238
//
//  where herein specific contents provided by the original harness have
//  been adapted for CSS2.1 conformance testing.
//
//  Separately,
//
//    1) The css test is referenced by URL rather than being fed through
//       separate PHP processing. (This avoids tampering iwth the HTTP
//       headers that normally get served up with the tests.)
//
//    2) The harness uses <object> to contain the test and pass/fail butons
//       are on the containing page tather than inside the test file. (This
//       format is good for desktop browsers).
//
//    3) The harness provided links targeted at a new window to open the test
//       where the pass/fail buttons remain only on the page containing the 
//       link rather than inside the test file. (This format is necessary 
//       for print).
//
//    4) The harness provides confirmation of the identity of the user agent
//       for which results data is being provided.
//
//    5) The harness lists extra meta information about the test in addition
//       to the pass/fail buttons; e.g. the test ID, test title, any
//       requirements documented in the test, etc.
//
// [1] http://dev.w3.org/cvsweb/2007/mobile-test-harness/
//
//////////////////////////////////////////////////////////////////////////////// 

require_once("./lib_css2.1_harness/class.css_page.phi");
require_once("./lib_css2.1_harness/class.test_suite.phi");
require_once("./lib_css2.1_harness/class.test_case.phi");
require_once("./lib_css2.1_harness/class.user_agent.phi");

////////////////////////////////////////////////////////////////////////////////
//
//  class testcase_page
//
//  A class for generating the page for inspecting and entering data for
//  individual tests.
//
////////////////////////////////////////////////////////////////////////////////
class testcase_page extends css_page
{  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Instance variables.
  //
  ////////////////////////////////////////////////////////////////////////////
  var $m_test_case;
  var $m_user_agent;
  var $m_post_data;
  var $m_ref_id;

  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor.
  //
  //  The URL accessing the page generated by an object of this class must
  //  identify a valid testsuite:
  //
  //    testcase.php?s=[testsuite]
  //
  //  If no test suite is identified or if the identified test suite is
  //  not valid, then an error is generated.
  //
  //  The URL accessing the page generated by an object of this class must
  //  also identify either implicitly or explicitly the particular test
  //  case of interest within the identified test suite. The test case
  //  can be identified explicitly by providing the test case id:
  //
  //     testsuite.php?s=[testsuite]&c=[testcase]
  //
  //  Alternatively, the test case can be identified by its position within
  //  the full list of test cases or within a named sub-group of test
  //  cases:
  //
  //     testsuite.php?s=[testsuite]&r=[rank]
  //     testsuite.php?s=[testsuite]&g=[testgroup]&r=[rank]
  //
  //  For test cases identified by position with a list of test cases,
  //  modifiers can be provided specifying that the provided rank 
  //  is referenced to the list after it is sorted by ascending 
  //  number of responses available for that test. In this case, 
  //  a timestamp can also be provided such that the sorting
  //  takes into account only those responses entered on or before
  //  this time:
  //
  //     testsuite.php?s=[testsuite]&r=[rank]&o=1
  //     testsuite.php?s=[testsuite]&r=[rank]&o=1&m=[timestamp]
  //     testsuite.php?s=[testsuite]&g=[testgroup]&r=[rank]&o=1
  //     etc.
  //
  //  If modifiers are not provided, default values are assumed. These
  //  default values can also be provided explicitly.
  //
  //  Finally, The URL accessing the page generated by an object of this 
  //  class may also identify an alternate user agent for which data is
  //  to be entered:
  //
  //     testsuite.php?s=[testsuite]&c=[testcase]&u=[id]
  //     testsuite.php?s=[testsuite]&r=[rank]&u=[user-agent string]
  //     etc.
  //
  //  If no alternate user agent is identified or if the identified user
  //  agent is not valid, then the user agent string of the browser accessing
  //  the harness is used.
  //
  //  All other URL parameters are ignored.
  //
  ////////////////////////////////////////////////////////////////////////////
  function testcase_page() 
  {
    parent::css_page();

    if(isset($_GET['s'])) {
      $suite = new test_suite($_GET['s']);
    } else {
      $msg = 'No test suite identified.';
      $this->trigger_client_error($msg, E_USER_ERROR);
    }

    if( isset($_GET['c']) ) {
      $select = $_GET['c'];
      $type = 2;
    } elseif ( isset($_GET['g']) ) {
      $select = $_GET['g'];
      $type = 1;
    } else {
      $select = '';
      $type = 0;
    }

    if(isset($_GET['r'])) {
      $rank = intval($_GET['r']);
    } else {
      $rank = 0;
    }

    if(isset($_GET['o'])) {
      $order = $_GET['o'];
    } else {
      $order = 0;
    }

    if(isset($_GET['m'])) {
      $modified = $_GET['m'];
    } else {
      $modified = '';
    }

    if(isset($_GET['u'])) {
      $this->m_user_agent = new user_agent($_GET['u']);
    } else {
      $this->m_user_agent = new user_agent();
    }

    $this->m_test_case = new test_case
      ( $suite->get_name()
      , $select
      , $type
      , $this->m_user_agent
      , $modified
      , $order 
      , $rank);

    $this->m_post_data = $_GET;
    $this->m_post_data['type'] = $type;
    $this->m_post_data['c'] = $this->m_test_case->get_test_case();
    if (isset($this->m_post_data['ref'])) {
      unset($this->m_post_data['ref']);
    }

    if (isset($_GET['ref'])) {
      $this->m_ref_id = intval($_GET['ref']);
    } else {
      $this->m_ref_id = 0;
    }


    $this->m_page_title = $this->m_test_case->get_title_suite() . 
      ' CSS 2.1 Test Suite';
    
    $this->m_content_title = 'CSS 2.1 Conformance Test Suite (' .
      $this->m_test_case->get_title_suite() . ')';

    // $this->m_resource_id 
    //   = '$Id: testcase.php,v 1.3 2008/09/03 18:55:53 dberfang Exp $';    
  }  
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_head_style()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_head_style($indent = '')
  {  // XXX-pl why not base.css?
    echo $indent . "<style type='text/css'>\n";
    echo $indent . "  @import url(http://www.w3.org/Style/CSS/Test/CSS2.1/current/indices.css);\n";
    echo $indent . "  @import url(harness.css);\n";
    echo $indent . "  a.report {display:none;}\n";
    echo $indent . "</style>\n";  
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_body_header()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_header($indent = '')
  {
    echo $indent . '<div class="header">'."\n";
    echo $indent . '  <p>'."\n";
    echo $indent . '    <span class="logo">'."\n";
    echo $indent . '      <a href="http://www.w3.org/" ';
    echo 'rel="home">'."\n";
    echo $indent . '        <img alt="W3C" height="48" width="72" ';
    echo 'src="http://www.w3.org/Icons/w3c_home">'."\n";
    echo $indent . '      </a>'."\n";
    echo $indent . '    </span>'."\n";
    
    echo $indent . '    <span class="suite">'."\n";
    echo $indent . '      <a href="testsuite?s=';
    echo $this->m_test_case->get_test_suite();
    echo '">'."\n";
    echo $indent . '        ' . $this->m_content_title . "\n";
    echo $indent . '      </a>'."\n";
    echo $indent . '    </span>'."\n";

    echo $indent . '    <span class="id">'."\n";
    echo $indent . '      Test'."\n";
    if($this->m_test_case->get_count() > 1) {
      echo $indent . '      (';
      echo $this->m_test_case->get_rank();
      echo ' of ';
      echo $this->m_test_case->get_count();
      echo ')'."\n";
    }
    echo $indent . "      <a href='" . $this->m_test_case->get_uri() . "' target='test_case'>";
    echo $this->m_test_case->get_test_case() . "</a>\n";
    
    if ($this->m_test_case->is_reference_test()) {
      $ref_tests = $this->m_test_case->get_references();
      
      foreach ($ref_tests as $ref_test) {
        $ref_name = $ref_test['reference'];
        $ref_type = $ref_test['type'];
        $ref_uri  = $this->m_test_case->getBaseURI() . $ref_test['uri'];
        echo $indent . "      {$ref_type} <a href='{$ref_uri}' target='reference'>{$ref_name}</a>\n";
      }
    }
    echo $indent . '    </span>'."\n";
    
    echo $indent . '  </p>'."\n";
    echo $indent . '  <h1>'."\n";
    echo $indent . '    ' . $this->m_test_case->get_title() . "\n";
    echo $indent . '  </h1>'."\n";
    
    $this->m_test_case->m_flags->write($indent . '  ');

    echo $indent . '</div>'."\n";

  }

  ////////////////////////////////////////////////////////////////////////////
  //
  // write_body_content()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_content($indent = '')
  {
  
    
    if ($this->m_test_case->is_reference_test()) {  // write ref test UI
      $ref_tests = $this->m_test_case->get_references();
      
      if (count($ref_tests)) {
        echo $indent . "<ul class='tabbar'>\n";
        if (0 == $this->m_ref_id) {
          echo $indent . "  <li class='reftab active'><a>Test Case</a></li>\n";
        }
        else {
          $query = $_GET;
          unset($query['ref']);
          $query_str = http_build_query($query, 'var_');
          echo $indent . "  <li class='reftab'><a href='testcase?{$query_str}'>Test Case</a></li>\n";
        }
        foreach ($ref_tests as $ref_test) {
          $ref_id = $ref_test['id'];
          $ref_type = $ref_test['type'];
          if ($ref_id == $this->m_ref_id) {
            echo $indent . "  <li class='reftab active'><a>{$ref_type} Reference Page</a></li>\n";
          }
          else {
            $query = $_GET;
            $query['ref'] = $ref_id;
            $query_str = http_build_query($query, 'var_');
            echo $indent . "  <li class='reftab'><a href='testcase?{$query_str}'>";
            echo              "{$ref_type} Reference Page";
            echo              "</a></li>\n";
          }
        }
        echo $indent . "</ul>\n";

        if (0 == $this->m_ref_id) {
          $plural = ((1 < count($ref_tests)) ? 's' : '');
          echo $indent . "<p class='instruct'>This page must be compared to the Reference Page{$plural}</p>\n";
        }
        else {
          $not = (('!=' == $this->m_test_case->get_reference_type($this->m_ref_id)) ? 'NOT ' : '');
          echo $indent . "<p class='instruct'>This page must {$not}match the Test Case</p>\n";
        }
      }
    }

    echo $indent . "<div class='test'>\n";
    echo $indent . "  <p>\n";
    $this->m_test_case->write($indent . '    ', $this->m_ref_id);
    echo $indent . '  </p>'."\n";
    echo $indent . '</div>'."\n";

    echo $indent . '<form name="eval" action="submit" method="post">'."\n";
    echo $indent . '  <p class="buttons">'."\n";
    foreach($this->m_post_data as $opt => $value) {
      echo $indent . "    <input type='hidden' name='{$opt}' value='{$value}'>\n";
    }
    echo $indent . '    <input type="submit" name="result" value="Pass [1]" accesskey="1" />'."\n";
    echo $indent . '    <input type="submit" name="result" value="Fail [2]" accesskey="2" />'."\n";
    echo $indent . '    <input type="submit" name="result" value="Cannot tell [3]" accesskey="3" />'."\n";
    echo $indent . '    <input type="submit" name="result" value="Skip [4]" accesskey="4" />'."\n";
    echo $indent . '  </p>'."\n";
    echo $indent . '  <p class="ua">'."\n";
    echo $indent . '    Testing'."\n";
    echo $indent . '    <abbr title="';
    echo $this->m_user_agent->get_ua_string();
    echo '">'."\n";
    $this->m_user_agent->write($indent . '      ');
    echo "\n";
    echo $indent . '    </abbr>'."\n";
    echo $indent . '  </p>'."\n";
    echo $indent . '</form>'."\n";
    echo "\n";
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  // write_body_footer()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_footer($indent = '')
  {
  }
}

$page = new testcase_page();
$page -> write();

?>
