<?php
/*******************************************************************************
 *
 *  Copyright © 2011 Hewlett-Packard Development Company, L.P. 
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


require_once('lib/HarnessPage.php');
require_once('lib/Sections.php');
require_once('lib/TestCase.php');

/**
 * A page to convert test index to test name
 */
class StartTestPage extends HarnessPage
{  
  protected $mNewURI;


  /**
   * Mandatory paramaters:
   * 's'    Test Suite
   *
   * Optional paramaters:
   * 'c'    Name of Test Case
   * 'g'    Sepc section Id
   * 'sec'  Spec section name
   * 'o'    Order of tests is sequence table
   * 'u'    User Agent Id
   */
  function __construct(Array $args = null) 
  {
    parent::__construct($args);
    
    $args = $this->mGetData;
    
    if ((! $this->_getData('c')) && (! $this->_getData('i'))) { // find first test in suite or section
      $order = $this->_getData('o');
      $sectionId = intval($this->_getData('g'));
      $sectionName = $this->_getData('sec');
      if ((0 == $sectionId) && ($sectionName)) {
        $sectionId = Sections::GetSectionIdFor($this->mTestSuite, $sectionName);
      }
      
      $testCase = new TestCase();
      $testCase->load($this->mTestSuite, null, $sectionId, $this->mUserAgent, $order, 0);
                             
      $args['i'] = $testCase->getTestCaseName();
    }
    
    if ($this->mTestSuite && $this->mTestSuite->isValid()) {
      $this->mNewURI = $this->buildConfigURI('page.testcase', $args);
    }
    else {
      $this->mNewURI = $this->buildConfigURI('page.home');
    }
  }  


  /**
   * Redirect 
   */
  function getRedirectURI()
  {
    return $this->mNewURI;
  }


  function writeBodyContent()
  {
    $this->openElement('p', null, FALSE);
    $this->addTextContent("We have processed your request and you should have been redirected ");
    $this->addHyperLink($this->mNewURI, null, "here");
    $this->addTextContent(".");
    $this->closeElement('p');

  }
}

?>