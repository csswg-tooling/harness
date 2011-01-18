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
require_once("lib/TestCase.php");
require_once("lib/UserAgent.php");

/**
 * This page accepts results posted for a test case
 * If successful, it will redirect to either the next
 * test in the sequence, or a success page
 */
class SubmitPage extends HarnessPage
{  
  protected $mNewURI;

  /**
   * Mandatory paramaters:
   * 's'   Test Suite
   * 'cid' Id of Test Case
   * 'c'   Name of Test Case (to verify proper id)
   * 'result' The result
   *
   * Optional paramaters:
   * 'next' Index of next test, done if 0 or absent
   * 'g'    Name of test group (if running through a group)
   * 'o'    Order of tests is sequence
   * 'm'    Modified time of tests to run
   * 'u'    User Agent Id
   */
  function __construct() 
  {
    parent::__construct();
    
    $result = $this->_postData('result');
    switch (strtolower(substr($result, 0, 4))) {
      case 'pass':
        $result = 'pass';
        break;
      case 'fail':
        $result = 'fail';
        break;
      case 'cann':
        $result = 'uncertain';
        break;
      case 'skip':
        $result = null;
        break;
      default:
        $msg = 'Invalid response submitted.';
        $this->triggerClientError($msg, E_USER_ERROR);
    }
    
    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      $this->triggerClientError($msg, E_USER_ERROR);
    }

    $this->mUserAgent->update();
    
    $testCaseId = $this->_postData('cid');
    $testCaseName = $this->_postData('c');
    $testGroupName = $this->_postData('g');
    
    $order = intval($this->_postData('o'));
    $modified = $this->_postData('m');

    $testCase = new TestCase($testCaseId);

    if ((0 == $testCaseId) || 
        ($testCase->getTestCaseName() != $testCaseName) ||
        ($testCase->getTestSuiteName() != $this->mTestSuite->getName())) {
      $msg = 'Posted data is invalid.';
      $this->triggerClientError($msg, E_USER_ERROR);
    }

    if ($result) {
      $testCase->submitResult($this->mUserAgent, Page::GetClientIP(), $result);
    }
     
    $nextIndex = $this->_postData('next');
    
    if (0 < $nextIndex) {
      $query['s'] = $this->mTestSuite->getName();
      if ($testGroupName) {
        $query['g'] = $testGroupName;
      }
      $query['r'] = $nextIndex;
      if (0 < $order) {
        $query['o'] = $order;
      }
      if ($modified) {
        $query['m'] = $modified;
      }
      $query['u'] = $this->mUserAgent->getId();
      $this->mNewURI = "testcase?" . http_build_query($query, 'var_');
    }
    else {
      $query['s'] = $this->mTestSuite->getName();
      $query['u'] = $this->mUserAgent->getId();
      $this->mNewURI = "success?" . http_build_query($query, 'var_');
    }
  }


  function getRedirectURI()
  {
    return $this->mNewURI;
  }


  function writeBodyContent($indent = '')
  {
    echo $indent . "<p>\n";
    echo $indent . "  You have submitted results data for a particular test case.\n";
    echo $indent . "</p>\n";
    echo $indent . "<p>\n";
    echo $indent . "  We have processed your submission and you should have been redirected \n";
    echo $indent . "  <a href='" . Page::Encode($this->mNewURI) . "'>here</a>.\n";
    echo $indent . "</p>\n";
  }
}

?>