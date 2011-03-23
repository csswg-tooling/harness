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
require_once("lib/TestSuite.php");
require_once("lib/UserAgent.php");
require_once("lib/Sections.php");
require_once("lib/TestCases.php");


/**
 * Class for generateing the page to select which tests will be run
 */
class TestSuitePage extends HarnessPage
{  
  protected $mSections;
  protected $mTestCases;


  function __construct() 
  {
    parent::__construct();

    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      trigger_error($msg, E_USER_WARNING);
    }

    $this->mSections = new Sections($this->mTestSuite);

    $this->mTestCases = new TestCases($this->mTestSuite);

    $this->mSubmitData['s'] = $this->mTestSuite->getName();
    if (! $this->mUserAgent->isActualUA()) {
      $this->mSubmitData['u'] = $this->mUserAgent->getId();
    }
  }  
  
  
  function getNavURIs()
  {
    $uris = parent::getNavURIs();
    
    if ($this->mTestSuite) {
      $title = "Enter Data";
      $uri = '';
      $uris[] = compact('title', 'uri');
    }
    return $uris;
  }
  
  
  /**
   * Generate <style> element
   */
  function writeHeadStyle()
  {
    parent::writeHeadStyle();
    
    $this->addStyleSheetLink('suite.css');
  }


  function writeTestSuiteForm()
  {
    $this->openFormElement(TESTCASE_PAGE_URI);
    $this->writeHiddenFormControls();
    $this->addElement('strong', null, "The full test suite: ");
    $this->writeOrderSelect();
    $this->addInputElement('submit', null, 'Start');
    $this->closeElement('form');
  }
  
  
  function writeSectionOptions($parentId = 0)
  {
    $data = $this->mSections->getSectionData($parentId);
    foreach ($data as $sectionData) {
      $id = $sectionData['id'];
      $testCount = $sectionData['test_count'];
      $subSectionCount = $this->mSections->getCount($id);
      if ((1 != $subSectionCount) || (0 < $testCount)) {
        $this->addOptionElement($id, null, "{$sectionData['section']}: {$sectionData['title']}");
      }
      if (0 < $subSectionCount) {
        $this->writeSectionOptions($id);
      }
    }
  }


  function writeSectionSelect()
  {
    $this->openSelectElement('g', array('style' => 'width: 25em'));
    $this->writeSectionOptions();
    $this->closeElement('select');
  }
  
  
  function writeSectionForm($title)
  {
    $this->openFormElement(TESTCASE_PAGE_URI);
    $this->writeHiddenFormControls();
    $this->addTextContent($title);
    $this->writeSectionSelect();
    $this->writeOrderSelect();
    $this->addInputElement('submit', null, 'Start');
    $this->closeElement('form');
  }
  

  function writeTestCaseSelect()
  {
    $testCases = $this->mTestCases->getTestCaseData();
    
    $this->openSelectElement('c', array('style' => 'width: 25em'));

    foreach ($testCases as $testCaseData) {
      $testCaseName = $testCaseData['testcase'];
      
      $this->addOptionElement($testCaseName, null,
                              "{$testCaseName}: {$testCaseData['title']}");
    }

    $this->closeElement('select');
  }
  
  
  function writeTestCaseForm($title)
  {
    $this->openFormElement(TESTCASE_PAGE_URI);
    $this->writeHiddenFormControls();
    $this->addTextContent($title);
    $this->writeTestCaseSelect();
    $this->addInputElement('submit', null, 'Start');
    $this->closeElement('form');
  }


  function writeOrderSelect()
  {
    $this->openSelectElement('o');

    $this->addOptionElement(1, array('selected' => TRUE), 'in most needed order');
    $this->addOptionElement(0, null, 'in alphabetical order');
    
    $this->closeElement('select');
  }

	function writeBodyContent() {
    $this->openElement('p', array('class' => 'ua'));
    $this->addTextContent("You are about the enter test result data for the following user agent:");
    
    if ($this->mUserAgent->isActualUA()) {
      $this->addAbbrElement($this->mUserAgent->getUAString(), null, $this->mUserAgent->getDescription());

      $args = $this->mGetData;
      $uri = $this->buildURI(SELECT_UA_PAGE_URI, $args);
      
      $this->openElement('span', null, FALSE);
      $this->addTextContent('(');
      $this->addHyperLink($uri, null, 'Other');
      $this->addTextContent(')');
      $this->closeElement('span');
    }
    else {
      $this->addAbbrElement($this->mUserAgent->getUAString(),
                            array('class' => 'other'), 
                            $this->mUserAgent->getDescription());

      $args = $this->mGetData;
      unset($args['u']);
      $uri = $this->buildURI(TESTSUITE_PAGE_URI, $args);
      $this->openElement('span', null, FALSE);
      $this->addTextContent('(');
      $this->addHyperLink($uri, null, 'Reset');
      $this->addTextContent(')');
      $this->closeElement('span');
    }
    $this->closeElement('p');

    $this->addElement('p', null, 
                      "The {$this->mTestSuite->getTitle()} test suite contains {$this->mTestCases->getCount()} test cases. " .
                      "You can stop running tests at any time without causing trouble.");

    $this->addElement('p', null, "You can test:");
    $this->openElement('ul');
    
    $this->openElement('li');
    $this->writeTestSuiteForm();
    $this->closeElement('li');
    
    if (0 < $this->mSections->getCount()) {
      $this->openElement('li');
      $this->writeSectionForm("A section of the specification: ");
      $this->closeElement('li');
    }

    $this->openElement('li');
    $this->writeTestCaseForm("A single test case: ");
    $this->closeElement('li');

    $this->closeElement('ul');
    
    $this->openElement('p');
    $this->addElement('strong', null, "Note: ");
    $this->addTextContent("The harness presents each test case embedded within a page " .
                          "that contains information about the test and a form to submit results. " .
                          "This page is not a part of the original test case and any influence it may have " .
                          "on the test should be ignored. Links are provided to see the test case and any " .
                          "reference pages in a separate window if desired.");
    $this->closeElement('p');

    $this->addElement('p', null,
                      'If there is any doubt about the result of a test, please press the "Cannot Tell" button.');

    $this->addElement('p', null,
                      'If listed requirements for a test cannot be met, please press the "Skip" button.');
	}
}

?>