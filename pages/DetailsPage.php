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
require_once("lib/Results.php");
require_once("lib/Result.php");
require_once("lib/Format.php");
require_once("lib/UserAgent.php");
require_once("lib/User.php");


/**
 * Class for generating the page for inspecting results for
 * individual tests
 */
class DetailsPage extends HarnessPage
{  
  protected $mResults;


  /**
   * Expected URL paramaters:
   * 's' Test Suite Name
   * 'c' Test Case Name
   * 'g' Spec Section Id (optional)
   * 't' Report type (override 'c' & 'g', 0 = entire suite, 1 = group, 2 = one test)
   * 'o' Display order (currently unused)
   * 'm' Modified date (only results before date)
   * 'e' Engine (filter results for this engine)
   * 'v' Engine Version
   * 'p' Platform
   */
  function __construct() 
  {
    parent::__construct();

    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      trigger_error($msg, E_USER_WARNING);
    }

    $testCaseName = $this->_getData('c');
    $specLinkId = intval($this->_getData('g'));

    if ($this->_getData('t')) {
      $type = intval($this->_getData('t'));
      switch ($type) {
        case 0: $specLinkId = 0;        // whole suite
        case 1: $testCaseName = null;   // test group
        case 2: break;                  // individual test case
      }
    }

    $order = intval($this->_getData('o'));
    $modified = $this->_getData('m', 'DateTime');
    
    $engine = $this->_getData('e');
    $engineVersion = $this->_getData('v');

    $platform = $this->_getData('p');

    $this->mResults = 
      new Results($this->mTestSuite, $testCaseName, $specLinkId,
                  $engine, $engineVersion, $platform, $modified);
  }
  
  
  function getPageTitle()
  {
    $title = parent::getPageTitle();
    return "{$title} Result Details";
  }
  
  
  function getNavURIs()
  {
    $uris = parent::getNavURIs();
    
    if ($this->mTestSuite) {
      $title = "Review Results";
      $args['s'] = $this->mTestSuite->getName();
      $args['u'] = $this->mUserAgent->getId();

      $uri = $this->buildURI(REVIEW_PAGE_URI, $args);
      $uris[] = compact('title', 'uri');
      
      $title = "Details";
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
    
    $this->addStyleSheetLink('report.css');
  }


  /**
   * Output details table
   */
  function writeBodyContent()
  {
    if (0 == $this->mResults->getResultCount()) {
      $this->addElement('p', null, 'No results entered matching this query.');
    } 
    else {
      $this->openElement('table');
      $this->openElement('tr');
      $this->addElement('th', null, 'Test Case');
      $this->addElement('th', null, 'Format');
      $this->addElement('th', null, 'Result');
      $this->addElement('th', null, 'User Agent');
      $this->addElement('th', null, 'Date');
      $this->addElement('th', null, 'Source');
      $this->closeElement('tr');

      $testSuiteName  = $this->mTestSuite->getName();
      
      $formats = Format::GetFormatsFor($this->mTestSuite);
      $userAgents = UserAgent::GetAllUserAgents();
      
      $testCases = $this->mResults->getTestCases();
      foreach ($testCases as $testCaseId => $testCaseData) {
        $engineResults = $this->mResults->getResultsFor($testCaseId);
        
        if ($engineResults) {
          ksort($engineResults);
          
          $testCaseName   = $testCaseData['testcase'];
          
          foreach ($engineResults as $engine => $engineResultData) {
            asort($engineResultData);
            
            foreach ($engineResultData as  $resultId => $resultValue) {
              $this->openElement('tr', array('class' => $resultValue));

              $result = new Result($resultId);
              
              $userAgent = $userAgents[$result->getUserAgentId()];
              $sourceId = $result->getSourceId();
              if ($sourceId) {
                $user = new User($sourceId);
                $source = $user->getName();
              }
              else {
                $source = '';
              }

              $this->openElement('td');
              
              $this->mSpiderTrap->addTrapLinkTo($this);
              
              $args['s'] = $testSuiteName;
              $args['c'] = $testCaseName;
              $args['f'] = $result->getFormatName();
              $args['u'] = $this->mUserAgent->getId();
              $uri = $this->buildURI(TESTCASE_PAGE_URI, $args);
              
              $this->addHyperLink($uri, null, $testCaseName);
              $this->closeElement('td');

              $this->addElement('td', null, $formats[$result->getFormatName()]->getTitle());
              $this->addElement('td', null, $resultValue);
              $this->openElement('td');
              $this->addAbbrElement($userAgent->getUAString(), null, $userAgent->getDescription());
              $this->closeElement('td');
              $this->addElement('td', null, $result->getDate());
              $this->addElement('td', null, $source);

              $this->closeElement('tr');
            }
          }
        }
      }
      $this->closeElement('table');
    }
  }
}

?>