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
require_once('lib/StatusCache.php');


class EngineInfo
{
  public  $title;
  public  $name;

  function _getElementName()  { return 'engineinfo'; }
}

class EngineResponse
{
  public  $index;
  public  $passCount;
  public  $failCount;
  
  function _getElementName()  { return 'engine'; }
}

class SectionResponse
{
  public  $anchorName;
  public  $section;
  public  $testCount;
  public  $engines;
  
  function _getElementName()  { return 'section'; }
}

class Response
{
  public  $testURI;
  public  $resultsURI;
  public  $detailsURI;
  public  $rewriteURIs;
  public  $clientEngineName;
  public  $engines;
  public  $sections;
  
  function _getElementName()  { return 'status'; }
}


/**
 * Class for responding to result status queries
 */
class StatusQueryPage extends HarnessPage
{
  protected $mRequestValid;
  protected $mSpecURIName;
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
        if ('/' == substr($specURIParts['path'], -1)) {
          $this->mSpecURIName = '';
        }
        else {
          $this->mSpecURIName = basename($specURIParts['path']);
        }
        if (('' == $this->mSpecURIName) || (0 === stripos($this->mSpecURIName, 'index.'))) {
          $this->mSectionId = 0;
        }
        else {
          $this->mSectionId = $this->mSections->findSectionIdForURI($this->mSpecURIName);
          
          if (! $this->mSectionId) {
            trigger_error('Not a valid specification url');
          }
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
    if (! $this->mResults) {
      $this->mResults = new Results($this->mTestSuite, null, $this->mSectionId);
/*  XXX at some point enable the other fields, but handle them in the cache
      $modified         = $this->_getData('m', 'DateTime');
      $engineName       = $this->_getData('e');
      $engineVersion    = $this->_getData('v');
      $browserName      = $this->_getData('b');
      $browserVersion   = $this->_getData('bv');
      $platformName     = $this->_getData('p');
      $platformVersion  = $this->_getData('pv');
      
      $this->mResults = 
        new Results($this->mTestSuite, null, $this->mSectionId, 
                    $modified,
                    $engineName, $engineVersion, 
                    $browserName, $browserVersion, 
                    $platformName, $platformVersion);
*/                    
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
        if (FALSE !== stripos($accept, 'application/xml')) {
          $this->mRequestValid = TRUE;
          return 'application/xml';
        }
      }
      if ('trident' == $this->mUserAgent->getEngineName()) {  // IE8 can't send proper accept headers
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
    $sectionURI = $sectionData['uri'];
    if (FALSE !== strpos($sectionURI, '#')) {
      $fragId = substr(strstr($sectionURI, '#'), 1);
      $sectionURI = strstr($sectionURI, '#', TRUE);
    }
    else {
      $fragId = '';
    }

    $results = array();

    if ($forceRecurse || (0 < $testCount) || (1 < $this->mSections->getSubSectionCount($sectionId))) {
      $testCaseIds = $this->mSections->getTestCaseIdsFor($sectionId, TRUE);

      $sectionResponse = new SectionResponse();
      $sectionResponse->anchorName = $fragId;
      $sectionResponse->section = $sectionData['section'];
      $sectionResponse->testCount = (($testCaseIds) ? count($testCaseIds) : 0);
      $sectionResponse->engines = array();

      $clientEngineName = $this->mUserAgent->getEngineName();
      
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

        $index = 0;
        foreach ($this->mResults->getEngineNames() as $engineName) {
          $engineResponse = new EngineResponse();
          $engineResponse->index = $index++;
          $engineResponse->passCount = (array_key_exists($engineName, $enginePassCounts) ? $enginePassCounts[$engineName] : 0);
          $engineResponse->failCount = (array_key_exists($engineName, $engineFailCounts) ? $engineFailCounts[$engineName] : 0);
          if (0 < ($engineResponse->passCount + $engineResponse->failCount)) {
            $sectionResponse->engines[] = $engineResponse;
          }
        }
      }
      
      $results[] = $sectionResponse;
    }
    
    $subSections = $this->mSections->getSubSectionData($sectionId);
    if ($subSections) {
      foreach ($subSections as $subSectionId => $sectionData) {
        $subSectionURI = $sectionData['uri'];
        if (FALSE !== strpos($subSectionURI, '#')) {
          $subSectionURI = strstr($subSectionURI, '#', TRUE);
        }
        $testCount = intval($sectionData['test_count']);
        if (($sectionURI == $subSectionURI) && 
            ((0 < $testCount) || (0 < $this->mSections->getSubSectionCount($subSectionId)))) {
          $results = array_merge($results, $this->getResultsForSection($subSectionId));
        }
      }
    }
    
    return $results;
  }
  
  
  function generateResponse()
  {
    $response = StatusCache::GetResultsForSection($this->mTestSuite, $this->mSectionId);

    if (! $response) {
      $this->loadResults();
      
      if ($this->mResults) {
        $response = new Response();
        $response->engines = array();
      
        foreach ($this->mResults->getEngineNames() as $engineName) {
          $engineInfo = new EngineInfo();
          $engineInfo->title = $this->mEngines[$engineName]->getTitle();
          $engineInfo->name = $engineName;
          $response->engines[] = $engineInfo;
        }
        $response->sections = $this->getResultsForSection($this->mSectionId, TRUE);
        StatusCache::SetResultsForSection($this->mTestSuite, $this->mSectionId, $response);
      }
    }
    
    if ($response) {
      $args['s'] = $this->mTestSuite->getName();
      $args['o'] = 1;

      $response->testURI = $this->buildConfigURI('page.testcase', $args, null, TRUE);
      $response->resultsURI = $this->buildConfigURI('page.results', $args, null, TRUE);
      $response->detailsURI = $this->buildConfigURI('page.details', $args, null, TRUE);
      $response->rewriteURIs = Config::Get('server.rewrite_urls');
      $response->clientEngineName = $this->mUserAgent->getEngineName();
      return $response;
    }
    return null;
  }
  
  
  function writeJSON()
  {
    $response = null;
    if ($this->mRequestValid) {
      $response = $this->generateResponse();
    }
    $this->_write(json_encode($response));
  }
  
  
  function writeXML()
  {
    $response = null;
    if ($this->mRequestValid) {
      $response = $this->generateResponse();
    }
    
    $this->addPI('xml', array('version' => '1.0', 'encoding' => $this->mEncoding));
    $this->xmlEncode('status', $response);
  }
  
  
  function writeBodyContent()
  {
    $this->addElement('p', null, 'The resource at this URI is intended to be used with JSON aware clients.');
  }

}

?>