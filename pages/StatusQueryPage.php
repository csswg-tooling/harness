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
require_once('lib/Engine.php');
require_once('lib/Results.php');


class EngineResponse
{
  public  $title;
  public  $passCount;
  public  $failCount;
  public  $detailsURI;
}

class ResultResponse
{
  public  $anchorName;
  public  $testCount;
  public  $engines;
  public  $testURI;
  public  $needData;
}


/**
 * Class for responding to result status queries
 */
class StatusQueryPage extends HarnessPage
{
  protected $mRequestValid;
  protected $mSectionId;
  protected $mSections;
  protected $mResults;
  protected $mEngines;
  
  
  /**
   * Expected URL paramaters:
   * 's' Test Suite Name
   * 'x' URI of specification section
   * 'm' Modified date (optional, only results before date)
   * 'e' Engine (optional, filter results for this engine)
   * 'v' Engine Version (optional)
   * 'p' Platform
   */
  function __construct(Array $args = null) 
  {
    parent::__construct($args);
    
    $this->mResultValid = FALSE;
    
    if ($this->mTestSuite && $this->mTestSuite->isValid()) {
      $this->mEngines = Engine::GetAllEngines();
      
      $this->mSections = new Sections($this->mTestSuite, TRUE);
      $specURI = $this->_getData('x');
      
      if ($specURI) {
        $specURIParts = parse_url($specURI);
        $specURIName = basename($specURIParts['path']);
      
        $this->mSectionId = $this->mSections->findSectionIdForURI($specURIName);
        
        if (! $this->mSectionId) {
          trigger_error('Not a valid specification url');
        }
      }
    }
  }
  
  
  function setResults(Results $results)
  {
    $this->mResults = $results;
  }
  
  
  function loadResults()
  {
    if ((! $this->mResults) && ($this->mSectionId)) {
      $engine = $this->_getData('e');
      $engineVersion = $this->_getData('v');
      $platform = $this->_getData('p');
      $modified = $this->_getData('m', 'DateTime');
      
      $this->mResults = 
        new Results($this->mTestSuite, null, $this->mSectionId,
                    $engine, $engineVersion, $platform, 
                    $modified);
    }
  }
  
  
  protected function _determineContentType($filePath = null)
  {
    if ($this->mTestSuite && $this->mTestSuite->isValid()) {
      // XXX check for origin header to restrict to w3.org servers...
      
      if (array_key_exists('HTTP_ACCEPT', $_SERVER)) {
        $accept = $_SERVER['HTTP_ACCEPT'];
        
        if (FALSE !== stripos($accept, 'application/json')) {
          $this->mRequestValid = TRUE;
          return 'application/json';
        }
      }
      if ('trident' == $this->mUserAgent->getEngineName()) {  // IE8 can't sent proper accept headers
        $this->mRequestValid = TRUE;
        return 'application/json';
      }
    }
    return parent::_determineContentType($filePath);
  }
  
  
  function writeHTTPHeaders()
  {
    parent::writeHTTPHeaders();

    if ($this->mRequestValid) {
      $this->sendHTTPHeader('Access-Control-Allow-Origin', '*');
    }
  }


  function getResultsForSection($sectionId, $forceRecurse = FALSE)
  {
    $sectionData = $this->mSections->getSectionData($sectionId);
    $testCount = intval($sectionData['test_count']);

    $results = array();

    if ($forceRecurse || (0 < $testCount) || (1 < $this->mSections->getSubSectionCount($sectionId))) {
      if (FALSE !== strpos($sectionData['uri'], '#')) {
        $fragId = substr(strstr($sectionData['uri'], '#'), 1);
      }
      else {
        $fragId = '';
      }
      
      $testCaseIds = $this->mSections->getTestCaseIdsFor($sectionId, TRUE);

      $args['s'] = $this->mTestSuite->getName();
      $args['g'] = $sectionId;
      $args['o'] = 1;
      $testURI = $this->buildURI(TESTCASE_PAGE_URI, $args, null, TRUE);

      $result = new ResultResponse();
      $result->anchorName = $fragId;
      $result->testCount = (($testCaseIds) ? count($testCaseIds) : 0);
      $result->testURI = $testURI;
      $result->engines = array();

      $clientEngineName = $this->mUserAgent->getEngineName();
      $result->needData = $result->testCount;
      
      if ((0 < $this->mResults->getEngineCount()) && ($testCaseIds)) {
        foreach ($this->mResults->getEngineNames() as $engineName) {
          $enginePassCounts[$engineName] = 0;
          $engineFailCounts[$engineName] = 0;
        }
        foreach ($testCaseIds as $testCaseId) {
          $engineCounts = $this->mResults->getResultCountsFor($testCaseId);
          if ($engineCounts) {
            foreach ($engineCounts as $engineName => $resultCounts) {
              if (0 < $resultCounts['pass']) {
                $enginePassCounts[$engineName]++;
              }
              elseif (0 < $resultCounts['fail']) {
                $engineFailCounts[$engineName]++;
              }
            }
          }
        }

        foreach ($this->mResults->getEngineNames() as $engineName) {
//          $args['e'] = $engineName;
          $engineResponse = new EngineResponse();
          $engineResponse->title = $this->mEngines[$engineName]->getTitle();
          $engineResponse->passCount = (array_key_exists($engineName, $enginePassCounts) ? $enginePassCounts[$engineName] : 0);
          $engineResponse->failCount = (array_key_exists($engineName, $engineFailCounts) ? $engineFailCounts[$engineName] : 0);
//          $engineResponse->detailsURI = $this->buildURI(DETAILS_PAGE_URI, $args, null, TRUE);
          $engineResponse->detailsURI = $this->buildURI(RESULTS_PAGE_URI, $args, null, TRUE);
          $result->engines[] = $engineResponse;
          
          if ($engineName == $clientEngineName) {
            $result->needData = ($result->testCount - ($engineResponse->passCount + $engineResponse->failCount));
          }
        }
      }
      
      $results[] = $result;
    }
    
    $subSections = $this->mSections->getSubSectionData($sectionId);
    if ($subSections) {
      foreach ($subSections as $subSectionId => $sectionData) {
        $testCount = intval($sectionData['test_count']);
        if ((0 < $testCount) || (0 < $this->mSections->getSubSectionCount($subSectionId))) {
          $results = array_merge($results, $this->getResultsForSection($subSectionId));
        }
      }
    }
    
    return $results;
  }
  
  
  function writeJSON()
  {
    $this->loadResults();
    
    if ($this->mResults) {
      $results = $this->getResultsForSection($this->mSectionId, TRUE);
      $this->_write(json_encode($results));
    }
  }
  
  
  function writeBodyContent()
  {
    $this->addElement('p', null, 'The resource at this URI is intended to be used with JSON aware clients.');
  }

}

?>