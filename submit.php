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
//  submit.php
//
//  Adapted from Mobile Test Harness [1]
//
//    File: harness.php
//      Lines: 43-81
//
//  where herein specific functionalities for submitting test results to the
//  database and for advancing the harness to subsequent test cases has
//  been moved to this separate page. Posts to the page are processed
//  and the harness interface is then redirected to an appropriate response
//  page. Further, we support data entry for user agents other than the
//  one currently accessing the page.
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
//  class submit_results_page
//
//  A class for accepting a posted submissions of test results, inserting 
//  the results into the database and redirecting the user to an appropriate
//  response page, namely the next test case or a status page indicating
//  either failure in submitting the results or the completion of the set
//  of requested test cases.
//
////////////////////////////////////////////////////////////////////////////////
class submit_results_page extends css_page
{  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Instance variables.
  //
  ////////////////////////////////////////////////////////////////////////////
  var $m_new_uri;

  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor.
  //
  //  The parameters posted to a page generated by an object of this class
  //  must identify a result, a test suite, a test case, and a presentation
  //  type.
  //
  //  If these requirements are not met, then no additional processing
  //  occurs, and an error is generated.
  //
  //  If no test suite is identified then any provided, then no additional
  //  processing occurs, and the user is redirected to testsuite.php with
  //  no additional parameters, which will generate an error.
  //
  //  The parameters posted to a page generated by an object of this class
  //  should also identify an alternate user agent for which data is to be
  //  entered.
  //
  //  If no alternate user agent is identified then the user is directed
  //  back to testsuite.php?s=[testsuite].
  //
  //  Otherwise the provided user-agent string is processed as described
  //  previously.
  //
  //  All other posted parameters are ignored and consequently dropped
  //  from the subsequent redirection.
  //
  ////////////////////////////////////////////////////////////////////////////
  function submit_results_page() 
  {
    parent::css_page();
    
    $this->m_page_title  = 'W3C CSS 2.1 Conformance Test Harness ';
    $this->m_page_title .= '(Precessing Submission)';
    $this->m_content_title = 'W3C CSS 2.1 Conformance Test Harness';

    if(isset($_POST['result'])) {
      switch (strtolower(substr($_POST['result'],0,4))) {
        case 'pass':
          $response = 'pass';
          break;
        case 'fail':
          $response = 'fail';
          break;
        case 'cann':
          $response = 'uncertain';
          break;
        case 'skip':
          $response = '';
          break;
        default:
          $msg = 'Invalid response submitted.';
          $this->trigger_client_error($msg, E_USER_ERROR);
      }
    } else {
      $msg = 'No response submitted.';
      $this->trigger_client_error($msg, E_USER_ERROR);
    }
    
    if(isset($_POST['s'])) {
      $suite = new test_suite($_POST['s']);
    } else {
      $msg = 'No test suite identified.';
      $this->trigger_client_error($msg, E_USER_ERROR);
    }

    if( isset($_POST['c']) ) {
      $case_id = $_POST['c'];
    } else {
      $msg = 'No test case identified.';
      $this->trigger_client_error($msg, E_USER_ERROR);
    }

    if( isset($_POST['type']) ) {
      $type = $_POST['type'];
    } else {
      $msg = 'Invalid post data.';
      $this->trigger_client_error($msg, E_USER_ERROR);
    }

    if(isset($_POST['r'])) {
      $rank = $_POST['r'];
    } else {
      $rank = 0;
    }

    if(isset($_POST['o'])) {
      $order = $_POST['o'];
    } else {
      $order = 0;
    }

    if(isset($_POST['m'])) {
      $modified = $_POST['m'];
    } else {
      $modified = null;
    }

    if(isset($_POST['u'])) {
      $ua = new user_agent($_POST['u']);
    } else {
      $ua = new user_agent();
    }

    switch($type) {
      case 0:
        $select = null;
        break;
      case 1:
        if( isset($_POST['g']) ) {
          $select = $_POST['g'];
        } else {
          $msg = 'Required group id not provided.';
          $this->trigger_client_error($msg, E_USER_ERROR);
        }
        break;
      case 2:
        $select = $case_id;
        break;
      default:
    }

    $case = new test_case
      ( $suite->get_name()
      , $select
      , $type
      , $ua->get_ua_string()
      , $modified
      , $order 
      , $rank
      );

    if( $case->get_test_case() != $case_id ) {
      $msg = 'Posted data is invalid.';
      $this->trigger_client_error($msg, E_USER_ERROR);
    }

    if ($response != '') {
      $case->submit($ua->get_ua_string(), $response);
     }

    $next_rank = $case->get_rank();
    if($next_rank <= 0) {
      $next_rank = 2;
    } else {
      $next_rank = $next_rank + 1;
    }
    $count = $case->get_count();
    
    if( ($type==2) || ($next_rank > $count) ) {
      $this->m_new_uri  = 'http://test.csswg.org/testharness/';
      $this->m_new_uri .= 'success.php';
    } else {
      $this->m_new_uri  = 'http://test.csswg.org/testharness/';
      $this->m_new_uri .= 'testcase.php';
      $this->m_new_uri .= '?s=' . $suite->get_name();

      if($type==1) {
          $this->m_new_uri .= '&g=' . $select;
      }
      $this->m_new_uri .= '&r=' . $next_rank;
      if($order==1) {
        $this->m_new_uri .= '&o=' . $order;
        if(isset($_POST['m'])) {
          $this->m_new_uri .= '&m=' . $case->get_timestamp();
        }
      }
      if(isset($_POST['u'])) {
        $this->m_new_uri .= '&u=' . $ua->get_id();
      }
    }
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_http_headers()
  // 
  ////////////////////////////////////////////////////////////////////////////
  function write_http_headers()
  {
    $s  = 'Location: ';
    $s .= $this->m_new_uri;
    header($s);
    parent::write_http_headers();
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  // write_body_content()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_content($indent = '')
  {
    echo $indent . '  <p>'."\n";
    echo $indent . '    You have submitted results data for a ';
    echo 'particular test case.'."\n";
    echo $indent . '  </p>'."\n";
    echo $indent . '  <p>'."\n";
    echo $indent . '    We have processed your submission and you should have ';
    echo 'been redirected'."\n";
    echo $indent . '<a href="' . $this->m_new_uri . '">here</a>.'."\n";
    echo $indent . '  </p>'."\n";
  }
}

$page = new submit_results_page();
$page -> write();

?>
