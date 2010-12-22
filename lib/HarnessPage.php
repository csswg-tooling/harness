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
//  class.css_page.phi
//
//  Adapted from Mobile Test Harness [1]
//
//    File: mthlib.phi
//      Function: WriteHTMLTop()
//      Function: WriteHTMLFoot()
//
//  where herein specific contents provided by these functions which are common
//  to pages across the test harness are provided and where other
//  specific functionalities are deferred for implementation by subsequent
//  derived classes.
//
// [1] http://dev.w3.org/cvsweb/2007/mobile-test-harness/
//
//////////////////////////////////////////////////////////////////////////////// 

require_once("lib/DynamicPage.php");
require_once("lib/SpiderTrap.php");

////////////////////////////////////////////////////////////////////////////////
//
//  class css_page
//
//  This derived class provided specific HTML content common to HTML pages
//  produced for the test harness. Subclasses of this class will add 
//  functionality needed by specific pages.
//
////////////////////////////////////////////////////////////////////////////////
class HarnessPage extends DynamicPage
{
  protected $mSpiderTrap;
// XXX  test suite
// XXX  user agent

  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor.
  //
  ////////////////////////////////////////////////////////////////////////////
  function __construct() 
  {
    parent::__construct();
    
    $this->mSpiderTrap = new SpiderTrap();
  }  
  
  function getPageTitle()
  {
//XXX if ($this->mTestSuite) {...}
    return "W3C Conformance Test Harness";
  }
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_head_style()
  //
  //  The <style> tag defines a style in a document.
  //
  //  Note: The style element goes in the head section.
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_head_style($indent = '')
  {
    echo $indent . "<style type='text/css'>\n";
    echo $indent . "  @import url(http://www.w3.org/StyleSheets/TR/base.css);\n";
    echo $indent . "  @import url(http://www.w3.org/Style/CSS/Test/CSS2.1/current/indices.css);\n";
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
    echo $indent . "<p>\n";
    
    $this->mSpiderTrap->generateLink($indent . '  ');
    
    echo $indent . "  <a class='logo' href='http://www.w3.org/' rel='home'>\n";
    echo $indent . "    <img alt='W3C' height='48' width='315' src='http://www.w3.org/Icons/w3c_main'>\n";
    echo $indent . "  </a>\n";
    echo $indent . "</p>\n";
    echo $indent . "<h1>\n";
    echo $indent . "  <a href='http://csswg.org/test'>\n";
    echo $indent . "    CSS Test Suite Project\n";
    echo $indent . "  </a>\n";
    echo $indent . "</h1>\n";
    echo $indent . "<hr>\n";
    echo $indent . "<h1>\n";
    echo $indent . "  " . $this->getContentTitle() . "\n";
    echo $indent . "</h1>\n\n";
    
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  // write_body_footer()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_footer($indent = '')
  {
    echo "\n";
    
    echo $indent . "<hr />\n";


    echo $indent . "<address>\n";
    echo $indent . "  Please send comments, questions, and error reports to\n";
    echo $indent . "  <a href='http://lists.w3.org/Archives/Public/public-css-testsuite'>public-css-testsuite@w3.org</a>.\n";    
    echo $indent . "</address>\n";

    $this->mSpiderTrap->generateLink($indent);
  }
}

?>