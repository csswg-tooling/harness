<?php
////////////////////////////////////////////////////////////////////////////////
//
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
//  useragent.php
//
//  In the Mobile Test Harness [1] user agents where identified by full
//  user-agent strings. For the CSS2.1 test harness there is a desire to
//  allow consolodated results for user-agent strings representing the
//  same browser type, version number and/or platform (OS).
//
//  Herein functionallity is provided whereby a user-agent string can be
//  posted for comparison to a database of known user-agent strings.
//  If the user-agent string is known, then a unique id number that
//  identifies the user-agent within the context of the test harness is
//  retrieved and used in redirecting the user back to testsuite.php.
//  If the user-agent is not in the database, then the provided string
//  is parsed to obtain a reasonable guess for the browser, version, and 
//  platform information. This parsed information is stored in the database
//  and a newly generated id number ascotiated with the new user agent
//  is retrieved and used.
//
// [1] http://dev.w3.org/cvsweb/2007/mobile-test-harness/
//
//////////////////////////////////////////////////////////////////////////////// 

require_once("./lib_css2.1_harness/class.css_page.phi");
require_once("./lib_css2.1_harness/class.user_agent.phi");

////////////////////////////////////////////////////////////////////////////////
//
//  class request_user_agent
//
//  A class for accepting a posted request for a new user agent, inserting 
//  the new string into the database if not already there and redirecting the 
//  user back to testsuite.php where the requested user agent will be used.
//
////////////////////////////////////////////////////////////////////////////////
class request_user_agent extends css_page
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
  //  must identify a testsuite. This testsuite is not checked for validity.
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
  function request_user_agent() 
  {
    parent::css_page();
    $this->m_page_title  = 'W3C CSS 2.1 Conformance Test Harness ';
    $this->m_page_title .= '(Precessing Request)';
    $this->m_content_title = 'W3C CSS 2.1 Conformance Test Harness';
    
    $this->m_new_uri = 'testsuite';
    
    if(isset($_POST['s'])) {
      $this->m_new_uri .= '?s=' . $_POST['s'];
      if(isset($_POST['u'])) {
        $ua = new user_agent($_POST['u']);
        $ua->update();
        $this->m_new_uri .= '&u=' . $ua->get_id();
      } else {
        $ua = new user_agent();
        $ua->update();
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
    echo $indent . '    You have requested to provide results for a ';
    echo 'particular user agent string.'."\n";
    echo $indent . '  </p>'."\n";
    echo $indent . '  <p>'."\n";
    echo $indent . '    We have processed your request and you should have ';
    echo 'been redirected'."\n";
    echo $indent . '<a href="' . $this->m_new_uri . '">here</a>.'."\n";
    echo $indent . '  </p>'."\n";

  }
}

$page = new request_user_agent();
$page -> write();

?>
