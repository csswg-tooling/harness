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
require_once("lib/Groups.php");
require_once("lib/TestCases.php");


/**
 * Class for generateing the page to select which tests will be run
 */
class TestSuitePage extends HarnessPage
{  
  protected $mTestGroups;
  protected $mTestCases;
  protected $mSubmitData;


  function __construct() 
  {
    parent::__construct();

    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      $this->triggerClientError($msg, E_USER_ERROR);
    }

    $this->mTestGroups = new Groups($this->mTestSuite->getName());

    $this->mTestCases = new TestCases($this->mTestSuite->getName());

    $this->mSubmitData['s'] = $this->mTestSuite->getName();
    $this->mSubmitData['u'] = $this->mUserAgent->getId();

  }  
  
  
  function getNavURIs()
  {
    $uris = parent::getNavURIs();
    
    $uris = parent::getNavURIs();
    
    if ($this->mTestSuite) {
      $title = "Enter Data";
      $uri = '';
      $uris[] = compact('title', 'uri');
    }
    return $uris;
  }
  
  
  function writeTestGroupSelect($indent = '')
  {
    $testGroups = $this->mTestGroups->getGroupData();
    
    echo $indent . "<select name='g'>\n";

    foreach ($testGroups as $groupData) {
      $groupName = Page::Encode($groupData['testgroup']);
      $groupTitle = Page::Encode($groupData['title']);
      echo $indent . "  <option value='{$groupName}'>{$groupTitle}</option>\n";
    }
    
    echo $indent . "</select>\n";
  }
  
  
  function writeTestCaseSelect($indent = '')
  {
    $testCases = $this->mTestCases->getTestCaseData();
    
    echo $indent . "<select name='c' style='width: 25em'>\n";

    foreach ($testCases as $testCaseData) {
      $testCase = Page::Encode($testCaseData['testcase']);
      $testCaseTitle = Page::Encode($testCaseData['title']);
      echo $indent . "  <option value='{$testCase}'>{$testCaseTitle}</option>\n";
    }
    
    echo $indent . "</select>\n";
  }


  function writeHiddenFormData($indent = '')
  {
    foreach($this->mSubmitData as $opt => $value) {
      $opt = Page::Encode($opt);
      $value = Page::Encode($value);
      echo $indent . "<input type='hidden' name='{$opt}' value='{$value}'>\n";
    }
  }
  
  function writeOrderSelect($indent = '')
  {
    echo $indent . "<select name=o>\n";
    echo $indent . "  <option selected value='1'>with least tested cases first</option>\n";
		echo $indent . "  <option value='0'>in order</option>\n";
		echo $indent . "</select>\n";
  }

	function writeBodyContent($indent = '') {
    echo $indent . "<p>\n";
    echo $indent . "  You are about the enter test result data for the following user agent:\n";
    
    $uaString = Page::Encode($this->mUserAgent->getUAString());
    $uaDescription = Page::Encode($this->mUserAgent->getDescription());
    echo $indent . "  <abbr title='{$uaString}'>{$uaDescription}</abbr>\n";
    echo $indent . "</p>\n";
    
    // XXX add UI to select other user agent
    
    $testSuiteTitle = Page::Encode($this->mTestSuite->getTitle());
    echo $indent . "<p>\n";
    echo $indent . "  The {$testSuiteTitle} test suite contains {$this->mTestCases->getCount()} test cases.\n";
    echo $indent . "  You can stop running tests at any time without causing trouble.\n";
    echo $indent . "</p>\n";
    
    echo $indent . "<p>You can test:</p>\n";
    echo $indent . "<ul>\n";
    
    echo $indent . "  <li>\n";
    echo $indent . "    <form action='testcase' method='get'>\n";
    $this->writeHiddenFormData($indent . '      ');
    echo $indent . "      <strong>The full test suite:</strong>\n";
    $this->writeOrderSelect($indent . '      ');
		echo $indent . "      <input type='submit' value='Start'>\n";
    echo $indent . "    </form>\n";
    echo $indent . "  </li>\n";
    
    if (0 < $this->mTestGroups->getCount()) {
      echo $indent . "  <li>\n";
      echo $indent . "    <form action='testcase' method='get'>\n";
      $this->writeHiddenFormData($indent . '      ');
      echo $indent . "      A group of test cases:\n";
      $this->writeTestGroupSelect($indent . '      ');
      $this->writeOrderSelect($indent . '      ');
      echo $indent . "      <input type='submit' value='Start'>\n";
      echo $indent . "    </form>\n";
      echo $indent . "  </li>\n";
    }

    echo $indent . "  <li>\n";
    echo $indent . "    <form action='testcase' method='get'>\n";
    $this->writeHiddenFormData($indent . '      ');
    echo $indent . "      A single test case:\n";
    echo $this->writeTestCaseSelect($indent . '      ');
		echo $indent . "      <input type='submit' value='Start'>\n";
    echo $indent . "    </form>\n";
    echo $indent . "  </li>\n";

    echo $indent . "</ul>\n";
    
    echo $indent . "<p>\n";
    echo $indent . "  <strong>Note:</strong> The harness presents each test case embedded within a page\n";
    echo $indent . "  that contains information about the test and a form to submit results.\n";
    echo $indent . "  This page is not a part of the original test case and any influence it may have\n";
    echo $indent . "  on the test should be ignored. Links are provided to see the test case and any\n";
    echo $indent . "  reference pages in a separate window if desired.\n";
    echo $indent . "</p>\n";
    echo $indent . "<p>\n";
    echo $indent . "  If there is any doubt about the result of a test, please press the\n";
    echo $indent . "  &quot;Cannot Tell&quot; button.\n";
    echo $indent . "</p>\n";
    echo $indent . "<p>\n";
    echo $indent . "  If listed requirements for a test cannot be met, please press the\n";
    echo $indent . "  &quot;Skip&quot; button.\n";
    echo $indent . "</p>\n";
	}
}

?>