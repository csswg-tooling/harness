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


require_once("lib/HarnessPage.php");
require_once("lib/TestSuites.php");


/**
 * A class for gererating the welcome page of the test harness
 */
class WelcomePage extends HarnessPage
{  
  protected $mTestSuites;


  function __construct() 
  {
    parent::__construct();

    $this->mTestSuites = new TestSuites();
  }
  
  
  function writeTestSuites($indent = '')
  {
    echo $indent . "<dl>\n";

    $testSuites = $this->mTestSuites->getTestSuites();
    
    foreach($testSuites as $testSuite) {
      $args['s'] = $testSuite->getName();
      $reviewURI = Page::EncodeURI(REVIEW_PAGE_URI, $args);
      
      if ($this->_getData('u')) {
        $args['u'] = $this->_getData('u');
      }
      $enterURI = Page::EncodeURI(TESTSUITE_PAGE_URI, $args);

      $homeURI = Page::Encode($testSuite->getHomeURI());
      $title = Page::Encode($testSuite->getTitle());
      $description = Page::Encode($testSuite->getDescription());
      
      echo $indent . "  <dt>\n";
      echo $indent . "    <a href='{$homeURI}'>{$title}</a>\n";
      echo $indent . "    (<a href='{$enterURI}'>Enter Data</a>,\n";
      echo $indent . "    <a href='{$reviewURI}'>Review Results</a>)\n";
      echo $indent . "  </dt>\n";
      echo $indent . "  <dd>\n";
      echo $indent . "    {$description}\n";
      echo $indent . "  </dd>\n";
    }
    echo $indent . "</dl>\n";
  }
  

  function writeBodyContent($indent = '')
  {  
    echo $indent . "<p>\n";
    echo $indent . "  Currently, you can provide test data or review ";
    echo             "the testing results for the following test suites:\n";
    echo $indent . "</p>\n";

    $this->writeTestSuites($indent);

  }
  
  
  function writeBodyFooter($indent = '')
  {
    echo $indent . "<p><small>\n";
    echo $indent . "  This W3C Conformance Test Harness was adapted from the\n";
    echo $indent . "  <a href='http://www.w3.org/2007/03/mth/harness'>Mobile Test Harness</a>\n";
    echo $indent . "  by the <a href='http://www.w3.org/Style/CSS/'>CSS WG (Cascading Style Sheets Working Group)</a>\n";
    echo $indent . "  to provide navigation and result recording controls for efficiently assessing\n";
    echo $indent . "  browser-based test cases, allowing anyone to easily submit pass/fail\n";
    echo $indent . "  data in conformance testing.\n";
    echo $indent . "  It was developed by <a href='/People/Dom/'>Dominique Hazael-Massieux</a> (dom&nbsp;@w3.org),\n";
    echo $indent . "  David M. Berfanger (david.berfanger&nbsp;@hp.com) and\n";
    echo $indent . "  Peter Linss (peter.linss&nbsp;@hp.com)\n";
    echo $indent . "</small></p>\n";

    parent::writeBodyFooter($indent);
  }
}

?>