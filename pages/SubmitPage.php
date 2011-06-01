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

require_once('lib/HarnessPage.php');
require_once('lib/TestSuite.php');
require_once('lib/TestCase.php');
require_once('lib/UserAgent.php');
require_once('lib/Format.php');

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
   * 's'      Test Suite
   * 'cid'    Id of Test Case
   * 'c'      Name of Test Case (to verify proper id)
   * 'f'      Format of test case
   * 'df'     User's preferred format of test case
   * 'result' The result
   *
   * Optional paramaters:
   * 'next' Index of next test, done if 0 or absent
   * 'g'    Sepc section Id
   * 'sec'  Spec section name
   * 'o'    Order of tests is by sequence table
   * 'fl'   Filter tests by flag
   * 'u'    User Agent Id
   */
  function __construct(Array $args = null) 
  {
    parent::__construct($args);
    
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
        trigger_error($msg, E_USER_WARNING);
    }
    
    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      trigger_error($msg, E_USER_WARNING);
    }

    $this->mUserAgent->update();
    
    $testCaseId = $this->_postData('cid');
    $testCaseName = $this->_postData('c');
    $formatName = $this->_postData('f');
    $desiredFormatName = $this->_postData('df');

    $sectionId = intval($this->_postData('g'));
    $sectionName = $this->_postData('sec');
    $order = intval($this->_postData('o'));

    $testCase = new TestCase($this->mTestSuite, $testCaseId);
    $format = new Format($formatName);

    if ((0 == $testCaseId) || 
        (0 != strcasecmp($testCase->getTestCaseName(), $testCaseName)) ||
        (! Format::FormatNameInArray($format->getName(), $testCase->getFormatNames()))) {
      $msg = 'Posted data is invalid.';
      trigger_error($msg, E_USER_WARNING);
    }

    if ($result && (! $this->mTestSuite->isLocked())) {
      $this->mUser->update();
      $testCase->submitResult($this->mUserAgent, $this->mUser, $format, $result);
    }
     
    $nextIndex = $this->_postData('next');
    
    $args['s'] = $this->mTestSuite->getName();
    $args['u'] = $this->mUserAgent->getId();
    if (0 <= $nextIndex) {
      if ($desiredFormatName) {
        $args['f'] = $desiredFormatName;
      }
      if ($sectionName) {
        $args['sec'] = $sectionName;
      }
      else {
        if ($sectionId) {
          $args['g'] = $sectionId;
        }
      }
      $flag = $this->_postData('fl');
      if ($flag) {
        $args['fl'] = $flag;
      }
      $nextTestCase = new TestCase();
      $nextTestCase->load($this->mTestSuite, null, $sectionId,
                          $this->mUserAgent, $order, $nextIndex, $flag);
      $args['i'] = $nextTestCase->getTestCaseName();
      if (0 < $order) {
        $args['o'] = $order;
      }
      $this->mNewURI = $this->buildConfigURI('page.testcase', $args);
    }
    else {
      $this->mNewURI = $this->buildConfigURI('page.success', $args);
    }
  }


  function getRedirectURI()
  {
    return $this->mNewURI;
  }


  function writeBodyContent()
  {
    $this->addElement('p', null, "You have submitted result data for a particular test case.");

    $this->openElement('p', null, FALSE);
    $this->addTextContent("We have processed your submission and you should have been redirected ");
    $this->addHyperLink($this->mNewURI, null, "here");
    $this->addTextContent(".");
    $this->closeElement('p');
  }
}

?>