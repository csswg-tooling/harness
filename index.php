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
//  index.php
//
//  Adapted from Mobile Test Harness [1]
//
//    File: harness.php
//      Lines: 21-35
//
//  where herein specific contents provided by the original harness have
//  been adapted for CSS2.1 conformance testing. Separately, in the original
//  harness the individual test suites where coded statically. Herein the
//  table of contents of the individual test suites is generated dynamically
//  via a database query.
//
// [1] http://dev.w3.org/cvsweb/2007/mobile-test-harness/
//
//////////////////////////////////////////////////////////////////////////////// 

require_once("./lib_css2.1_harness/class.css_page.phi");
require_once("./lib_css2.1_harness/class.test_suites.phi");

////////////////////////////////////////////////////////////////////////////////
//
//  class welcome_page
//
//  A class for generating the welcome page for a test harness
//
////////////////////////////////////////////////////////////////////////////////
class WelcomePage extends css_page
{  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Instance variables.
  //
  ////////////////////////////////////////////////////////////////////////////
  var $mTestSuites;

  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor.
  //
  ////////////////////////////////////////////////////////////////////////////
  function __construct() 
  {
    parent::__construct();

    $this->m_page_title = 'W3C CSS Conformance Test Harness';
    
    $this->m_content_title = 'W3C CSS Conformance Test Harness';
    
    $this->mTestSuites = new test_suites();
  }  
  
  ////////////////////////////////////////////////////////////////////////////
  //
  // write_body_content()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_content($indent = '')
  {  
    echo $indent . "<p>\n";
    echo $indent . "  This is a development version of a test harness for conducting CSS conformance\n";
    echo $indent . "  testing using the ";
    echo             "<a href='http://www.w3.org/Style/CSS/Test/CSS2.1/current/'>";
    echo             "CSS 2.1 Conformance Test Suite</a>.\n";
    echo $indent . "</p>\n";
    echo $indent . "<p>\n";
    echo $indent . "  Currently, you can provide test data or review ";
    echo             "the testing results for the following test suites:\n";
    echo $indent . "</p>\n";

    $this->mTestSuites->write($indent);

    echo $indent . "<p>Please make sure your client is configured to:</p>\n";
    echo $indent . "<ul>\n";
    echo $indent . "  <li>Default black text on a white background.\n";
    echo $indent . "  <li>No minimum font size.\n";
    echo $indent . "  <li>Print background colors and images.\n";
    echo $indent . "</ul>\n";

    echo $indent . "<p>\n";
    echo $indent . "  <b>Note</b> that <em>many</em> of the tests require the ";
    echo             "<a href='http://www.w3.org/Style/CSS/Test/Fonts/Ahem/'>";
    echo             "Ahem font to be installed</a>.\n";
    echo $indent . "  Some of the font-related tests also require ";
    echo             "<a href='http://www.w3.org/Style/CSS/Test/Fonts/'>special fonts</a>.\n";
    echo $indent . "  Without the proper fonts installed, results are of no value.\n";
    echo $indent . "</p>\n";
    echo $indent . "<p>\n";
    echo $indent . "  Some tests have additional requirements, which will be noted by the harness interface.\n";
    echo $indent . "</p>\n";

    echo $indent . "<p><small>\n";
    echo $indent . "  This W3C CSS Conformance Test Harness was adapted from the\n";
    echo $indent . "  <a href='http://www.w3.org/2007/03/mth/harness'>Mobile Test Harness</a>\n";
    echo $indent . "  by the <a href='http://www.w3.org/Style/CSS/'>CSS WG (Cascading Style Sheets Working Group)</a>\n";
    echo $indent . "  to provide navigation and results recording controls for efficiently assessing\n";
    echo $indent . "  browser-based CSS test cases, allowing anyone to easily submit pass/fail\n";
    echo $indent . "  data in CSS conformance testing.\n";
    echo $indent . "  It was developed by <a href='/People/Dom/'>Dominique Hazael-Massieux</a> (dom&nbsp;@w3.org),\n";
    echo $indent . "  David M. Berfanger (david.berfanger&nbsp;@hp.com) and\n";
    echo $indent . "  Peter Linss (peter.linss&nbsp;@hp.com)\n";
    echo $indent . "</small></p>\n";
  }
}

$page = new WelcomePage();
$page->write();

?>
