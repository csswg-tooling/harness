<?php
/*******************************************************************************
 *
 *  Copyright © 2008-2011 Hewlett-Packard Development Company, L.P. 
 *
 *  This work is distributed under the W3C® Software License [1] 
 *  in the hope that it will be useful, but WITHOUT ANY 
 *  WARRANTY; without even the implied warranty of 
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 *
 *  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
 *
 *  Adapted from the Mobile Test Harness
 *  Copyright © 2007 World Wide Web Consortium
 *  http://dev.w3.org/cvsweb/2007/mobile-test-harness/
 * 
 ******************************************************************************/

require_once("pages/WelcomePage.php");

/**
 * Override welcome page to provide CSS specific info
 */
class CSSWelcomePage extends WelcomePage
{  
  function __construct() 
  {
    parent::__construct();

  }  
  
  
  function writeBodyHeader($indent = '')
  {
    parent::writeBodyHeader($indent);

    echo $indent . "<p>\n";
    echo $indent . "  This is a development version of a test harness for conducting CSS conformance\n";
    echo $indent . "  testing using the CSS 2.1 Conformance Test Suite.\n";
    echo $indent . "</p>\n";
    
    echo $indent . "<p>\n";
    echo $indent . "  More information about the CSS 2.1 Conformance Test Suite can be found on the\n";
    echo $indent . "  <a href='http://wiki.csswg.org/test'>\n";
    echo $indent . "    CSS Working Group Wiki\n";
    echo $indent . "  </a>.\n";
    echo $indent . "</p>\n";
    echo $indent . "<hr>\n";
  }

  function writeBodyContent($indent = '')
  {
    parent::writeBodyContent($indent);

    echo $indent . "<p>Please make sure your client is configured to:</p>\n";
    echo $indent . "<ul>\n";
    echo $indent . "  <li>Default black text on a white background.\n";
    echo $indent . "  <li>No minimum font size.\n";
    echo $indent . "  <li>Print background colors and images.\n";
    echo $indent . "</ul>\n";

    echo $indent . "<p>\n";
    echo $indent . "  <strong>Note</strong> that <em>many</em> of the tests require the ";
    echo             "<a href='http://www.w3.org/Style/CSS/Test/Fonts/Ahem/'>";
    echo             "Ahem font to be installed</a>.\n";
    echo $indent . "  Some of the font-related tests also require ";
    echo             "<a href='http://www.w3.org/Style/CSS/Test/Fonts/Overview'>special fonts</a>.\n";
    echo $indent . "  Without the proper fonts installed, results are of no value.\n";
    echo $indent . "</p>\n";
    echo $indent . "<p>\n";
    echo $indent . "  Some tests have additional requirements, which will be noted by the harness interface.\n";
    echo $indent . "</p>\n";
  }
}

$page = new CSSWelcomePage();
$page->write();

?>