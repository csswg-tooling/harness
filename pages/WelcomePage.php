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


  static function GetPageKey()
  {
    return 'home';
  }

  function __construct(Array $args = null, Array $pathComponents = null) 
  {
    parent::__construct($args, $pathComponents);

    $this->mTestSuites = new TestSuites();
  }
  
  
  function writeTestSuites($showUnlocked = TRUE, $showLocked = TRUE)
  {
    $testSuites = $this->mTestSuites->getTestSuites();
    
    if ($testSuites) {
      $this->openElement('dl');

      foreach ($testSuites as $testSuite) {
        if (($testSuite->isLocked() && $showLocked) || 
            ((! $testSuite->isLocked()) && $showUnlocked)) {
          unset($args);
          $args['s'] = $testSuite->getName();
          $args['u'] = $this->mUserAgent->getId();

          $reviewURI = $this->buildPageURI('review', $args);

          $this->openElement('dt', null, FALSE);
          $this->addHyperLink($testSuite->getHomeURI(), null, $testSuite->getTitle());
          $this->addTextContent(' (');
          if (! $testSuite->isLocked()) {
            $enterURI = $this->buildPageURI('testsuite', $args);
            $this->addHyperLink($enterURI, null, "Enter Data");
            $this->addTextContent(', ');
          }
          $this->addHyperLink($reviewURI, null, "Review Results");
          $this->addTextContent(')');
          $this->closeElement('dt');

          $this->addElement('dd', null, $testSuite->getDescription());
        }
      }
      $this->closeElement('dl');
    }
  }
  

  function writeBodyContent()
  {
    if (0 < $this->mTestSuites->getCount()) {

      if (0 < ($this->mTestSuites->getCount() - $this->mTestSuites->getLockedCount())) {
        $this->addElement('p', null, "You can provide test data or review " .
                                     "the testing results for the following test suites:");

        $this->writeTestSuites(TRUE, FALSE);
      }
      if (0 < $this->mTestSuites->getLockedCount()) {
        $this->addElement('p', null, "You can review the testing results for the following locked test suites:");

        $this->writeTestSuites(FALSE, TRUE);
      }
    }
    else {
      $this->addElement('p', null, "** No Test Suites Defined. **");
    }
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

    $mailArgs['subject'] = 'W3C Conformance Test Harness';
    $this->openElement('span', null, FALSE);  // turn off source formatting
    $this->addTextContent("It was developed by ");
    $this->addHyperLink('http://www.w3.org/People/Dom/', null, "Dominique Hazael-Massieux");
    $this->addTextContent(" ");
    $this->addEmailHyperLink('dom@w3.org', null, $mailArgs);
    $this->addTextContent(', ');
    $this->addEmailHyperLink('david.berfanger@hp.com', 'David M. Berfanger', $mailArgs);
    $this->addTextContent(" and ");
    $this->addEmailHyperLink('peter.linss@hp.com', 'Peter Linss', $mailArgs);
    $this->closeElement('span');
    $this->closeElement('small');
    $this->closeElement('p');

    parent::writeBodyFooter();
  }
}

?>