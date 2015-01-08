<?php
/*******************************************************************************
 *
 *  Copyright © 2014 Hewlett-Packard Development Company, L.P.
 *
 *  This work is distributed under the W3C® Software License [1] 
 *  in the hope that it will be useful, but WITHOUT ANY 
 *  WARRANTY; without even the implied warranty of 
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 *
 *  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
 *
 ******************************************************************************/


require_once('core/pages/APIPage.php');

require_once('lib/Results.php');
require_once('lib/Sections.php');

require_once('modules/testsuite/TestSuite.php');
require_once('modules/useragent/UserAgent.php');
require_once('modules/useragent/Engine.php');
require_once('modules/specification/Specification.php');
require_once('modules/specification/SpecificationAnchor.php');


/**
 * Class for fetching result status information
 */
class StatusAPI extends APIPage
{
  static function GetPageKey()
  {
    return 'api.status';
  }
  
  protected $mTestSuite;
  protected $mUserAgent;
  protected $mSpec;
  protected $mSpecType;
  protected $mSection;
  protected $mSections;
  protected $mResults;
  
  /**
   * Expected URL paramaters:
   * 
   */
  function _initPage()
  {
    parent::_initPage();

    $this->mTestSuite = $this->_requestData('suite', 'TestSuite');
    $this->mUserAgent = new UserAgent(intval($this->_requestData('ua')));
    
    if ($this->mTestSuite && $this->mTestSuite->isValid()) {
      $specName = $this->_requestData('spec');
      if ($specName) {
        $spec = Specification::GetSpecificationByName($specName);
        if ($spec && $this->mTestSuite->hasSpecification($spec)) {
          $this->mSpec = $spec;
        }
      }
      else {
        $this->mSpec = reset($this->mTestSuite->getSpecifications());
      }
      
      
      if ($this->mSpec) {
        $this->mSpecType = $this->_requestData('type');
        if (! $this->mSpecType) {
          $this->mSpecType = 'draft';  // XXX maybe default to 'official' once TR starts using?
        }
        $this->mSections = new Sections($this->mTestSuite, TRUE, $this->mSpecType);

        $specURI = $this->_requestData('uri');
        if ($specURI) {
          $specURIName = $this->_getURIFileName($specURI);
          $specHomeURI = $this->_getURIFileName($this->mSpec->getHomeURI());
          if (('' == $specURIName) || 
              ($specHomeURI == $specURIName) || 
              (('' == $specHomeURI) && (0 === stripos($specURIName, 'index.')))) {
            $this->mSection = null;
          }
          else {
            $sectionURI = $this->_CombinePath($this->mSpec->getBaseURI(), $specURIName);

            $this->mSection = $this->mSections->findSectionForURI($sectionURI);
          }
        }
      }
    }
  }
  
  
  protected function _getURIFileName($uri)
  {
    $uriPath = $this->_ParseURI($uri, PHP_URL_PATH);
    if ('/' == substr($uriPath, -1)) {
      return '';
    }
    return basename($uriPath);
  }
  
  
  
  function secureRequired()
  {
    return FALSE;
  }
  
  function crossOriginAllowed()
  {
    return TRUE;
  }

  function getAPIName()
  {
    return 'status';
  }
  
  function getURITemplate()
  {
    return '{suite,spec,uri}';
  }
  
  function getOverview()
  {
    return 'Write This...';
  }

  function getArguments()
  {
    $args['suite'] = $this->_defineArgument('param/suite', '<string>', 'name of test suite');
    $args['spec'] = $this->_defineArgument('param/spec', '<string>', 'short name of spec', 'optional');
    $args['uri'] = $this->_defineArgument('param/uri', '<string>', 'uri of spec');
    
    return $args;
  }
  
  function getReturnValues()
  {
    $statusData['title'] = '<string>';
    $statusData['description'] = '<string>';
    $statusData['heading'] = '+<string>';
    $statusData['build_date'] = '<iso-date UTC>';
    $statusData['lock_date'] = '+<iso-date UTC>';
    $statusData['test_uri'] = '<string>';
    $statusData['results_uri'] = '<string>';
    $statusData['details_uri'] = '<string>';
    $statusData['client_engine'] = '<string>';
    $statusData['is_index'] = '+<bool>';
    $statusData['engine_titles'] = '{_<engine-name>: <string>}';
    $statusData['sections'] = '{_<section-name>: <section-data>}';
    $values[] = $statusData;

    $sectionData['anchor_name'] = '<string>';
    $sectionData['section_name'] = '<string>';
    $sectionData['test_count'] = '<int>';
    $sectionData['results'] = '{_<engine-name>: <engine-data>}';
    $values['section-data'] = $sectionData;
    
    $engineData['pass_count'] = '<int>';
    $engineData['fail_count'] = '<int>';
    $values['engine-data'] = $engineData;
    
    return $values;
  }
    
  
  function processCall($version)
  {
    if ('GET' == $this->_getRequestMethod()) {
      if ($this->mSpec) {
        $response['title'] = $this->mTestSuite->getTitle();
        $this->_addField($response, 'description',  $this->mTestSuite->getDescription());
        $this->_addField($response, 'heading', $this->mTestSuite->getAnnotationTitle());
        $this->_addField($response, 'build_date', $this->_dateTimeValue($this->mTestSuite->getBuildDateTime()));
        $this->_addField($response, 'lock_date', $this->_dateTimeValue($this->mTestSuite->getLockDateTime()));
        
        $args['suite'] = $this->mTestSuite->getName();
        $args['order'] = TRUE;
        $this->_addField($response, 'test_uri', $this->buildPageURI('testcase', $args, null, TRUE));
        $this->_addField($response, 'results_uri', $this->buildPageURI('results', $args, null, TRUE));
        $this->_addField($response, 'details_uri', $this->buildPageURI('details', $args, null, TRUE));
        
        $this->_addField($response, 'client_engine', $this->mUserAgent->getEngineTitle());
        $this->_addField($response, 'is_index', (null == $this->mSection));
        
        
        $resultData = StatusCache::GetResultsForSection($this->mTestSuite, $this->mSpec, $this->mSpecType, $this->mSection);
        if (! $resultData) {
          $this->mResults = new Results($this->mTestSuite, null, $this->mSpec, $this->mSection);
          
          $resultData['engines'] = $this->_getEngineTitles();
          $resultData['sections'] = $this->_getSectionData($this->mSection, TRUE);
          StatusCache::SetResultsForSection($this->mTestSuite, $this->mSpec, $this->mSpecType, $this->mSection, $resultData);
        }
        
        $this->_addField($response, 'engine_titles', $resultData['engines']);
        $this->_addField($response, 'sections', $resultData['sections']);
        
        return $response;
      }
      
    }
    elseif ('POST' == $this->_getRequestMethod()) {
/*
      if (XXX && $this->mUser->hasRole('admin')) {

      }
*/      
    }
    return null;
  }
  

  protected function _getEngineTitles()
  {
    $engines = array();
    
    foreach ($this->mResults->getEngines() as $engineName => $engine) {
      $engines['_' . $engineName] = $engine->getTitle();
    }
    return $engines;
  }
  
  protected function _getSectionData(SpecificationAnchor $section = null, $forceResults = FALSE)
  {
    $sectionURI = '';
    $fragId = '';
    if ($section) {
      $sectionURI = $section->getAnchorURI();
      if (FALSE !== strpos($sectionURI, '#')) {
        $fragId = substr(strstr($sectionURI, '#'), 1);
        $sectionURI = strstr($sectionURI, '#', TRUE);
      }
    }
    
    $results = array();
    $testCases = $this->mResults->getTestCases();
    $engines = $this->mResults->getEngines();
    $testCaseIds = $this->mSections->getTestCaseIdsFor($this->mSpec, $section, TRUE);
    
    if ($forceResults || (0 < count($testCaseIds))) {
      $sectionData = array();
      $this->_addField($sectionData, 'anchor_name', $fragId);
      $this->_addField($sectionData, 'section_name', ($section ? $section->getName() : ''));
      $sectionData['test_count'] = ($testCaseIds ? count($testCaseIds) : 0);
      
      $engineResults = array();
      if ($testCaseIds) {
        foreach ($engines as $engineName => $engine) {
          $enginePassCount[$engineName] = 0;
          $engineFailCount[$engineName] = 0;
        }
        foreach ($testCaseIds as $testCaseId) {
          $engineResultCounts = $this->mResults->getResultCountsFor($testCases[$testCaseId]);
          if ($engineResultCounts) {
            foreach ($engineResultCounts as $engineName => $resultCounts) {
              if (0 < $resultCounts['pass']) {
                $enginePassCount[$engineName]++;
              }
              elseif (0 < $resultCounts['fail']) {
                $engineFailCount[$engineName]++;
              }
            }
          }
        }
        
        foreach ($engines as $engineName => $engine) {
          $engineData = array();
          $engineData['pass_count'] = (array_key_exists($engineName, $enginePassCount) ? $enginePassCount[$engineName] : 0);
          $engineData['fail_count'] = (array_key_exists($engineName, $engineFailCount) ? $engineFailCount[$engineName] : 0);
          
          if (0 < ($engineData['pass_count'] + $engineData['fail_count'])) {
            $engineResults['_' . $engineName] = $engineData;
          }
        }
      }
      
      $this->_addField($sectionData, 'results', $engineResults);
      
      $results['_' . $fragId] = $sectionData;
    }
    
    $subSections = $this->mSections->getSubSections($this->mSpec, $section);
    if ($subSections) {
      foreach ($subSections as $subSection) {
        $subSectionURI = $subSection->getAnchorURI();
        if (FALSE !== strpos($subSectionURI, '#')) {
          $subSectionURI = strstr($subSectionURI, '#', TRUE);
        }
        if (($sectionURI == $subSectionURI) &&
            ((0 < count($this->mSections->getTestCaseIdsFor($this->mSpec, $subSection))) ||
             (0 < $this->mSections->getSubSectionCount($this->mSpec, $subSection)))) {
          $results = array_merge($results, $this->_getSectionData($subSection));
        }
      }
    }
    return $results;
  }

}

?>