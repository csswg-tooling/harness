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
  
  
  function writeTestSuites()
  {
    $testSuites = $this->mTestSuites->getTestSuites();
    
    if ($testSuites) {
      $this->openElement('dl');

      foreach($testSuites as $testSuite) {

        unset($args);
        $args['s'] = $testSuite->getName();
        $args['u'] = $this->mUserAgent->getId();

        $reviewURI = $this->buildURI(REVIEW_PAGE_URI, $args);
        $enterURI = $this->buildURI(TESTSUITE_PAGE_URI, $args);

        $this->openElement('dt', null, FALSE);
        $this->addHyperLink($testSuite->getHomeURI(), null, $testSuite->getTitle());
        $this->addTextContent(' (');
        if (! $testSuite->isLocked()) {
          $this->addHyperLink($enterURI, null, "Enter Data");
          $this->addTextContent(', ');
        }
        $this->addHyperLink($reviewURI, null, "Review Results");
        $this->addTextContent(')');
        $this->closeElement('dt');

        $this->addElement('dd', null, $testSuite->getDescription());
      }
      $this->closeElement('dl');
    }
    else {
      $this->addElement('p', null, "** No Test Suites Defined. **");
    }
  }
  

  function writeBodyContent()
  {
    $this->addElement('p', null, "Currently, you can provide test data or review " .
                                 "the testing results for the following test suites:");

    $this->writeTestSuites();

  }
  
  
  function writeBodyFooter()
  {
    $this->openElement('p');
    $this->openElement('small');
    $this->addTextContent("This W3C Conformance Test Harness was adapted from the ");
    $this->addHyperLink('http://www.w3.org/2007/03/mth/harness', null, "Mobile Test Harness");
    $this->addTextContent(" by the ");
    $this->addHyperLink('http://www.w3.org/Style/CSS/', null, "CSS WG (Cascading Style Sheets Working Group)");
    $this->addTextContent(" to provide navigation and result recording controls for efficiently assessing " .
                          "browser-based test cases, allowing anyone to easily submit pass/fail " .
                          "data in conformance testing.");
    $this->addTextContent("It was developed by ");
    $this->addHyperLink('http://www.w3.org/People/Dom/', null, "Dominique Hazael-Massieux");
    $this->addTextContent(" (dom&nbsp;@w3.org), David M. Berfanger (david.berfanger&nbsp;@hp.com) and ".
                          "Peter Linss (peter.linss&nbsp;@hp.com)", FALSE);
    $this->closeElement('small');
    $this->closeElement('p');

    parent::writeBodyFooter();
  }
}

?>