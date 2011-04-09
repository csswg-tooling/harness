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


require_once("lib/ResultsBasedPage.php");
require_once("lib/Result.php");
require_once("lib/Format.php");


/**
 * Class for generating the page for inspecting results for
 * individual tests
 */
class DetailsPage extends ResultsBasedPage
{  
  protected $mDisplayLinks;
  protected $mEngine;
  protected $mUserAgents;


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
  function __construct(Array $args = null) 
  {
    parent::__construct($args);

    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      trigger_error($msg, E_USER_WARNING);
    }
    
    $this->mDisplayLinks = TRUE;

    $this->mEngine = $this->_getData('e');
    
    $order = intval($this->_getData('o'));
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

  function _compareResults(Result $a, Result $b)
  {
    $resultOrder = array('pass' => '0', 'fail' => '1', 'uncertain' => '2', 'invalid' => '3');
  
    $userAgentA = $this->mUserAgents[$a->getUserAgentId()];
    $userAgentB = $this->mUserAgents[$b->getUserAgentId()];
    
    return strnatcasecmp($resultOrder[$a->getResult()] . $userAgentA->getDescription() . $a->getDate(), 
                         $resultOrder[$b->getResult()] . $userAgentB->getDescription() . $b->getDate());
  }
  
  /**
   * Output details table
   */
  function writeBodyContent()
  {
    $this->loadResults();
    
    if (0 == $this->mResults->getResultCount()) {
      $this->addElement('p', null, 'No results entered matching this query.');
    } 
    else {
      $this->openElement('table');

      $this->openElement('thead');
      $this->openElement('tr');
      $this->addElement('th', null, 'Test Case');
      $this->addElement('th', null, 'Format');
      $this->addElement('th', null, 'Result');
      $this->addElement('th', null, 'User Agent');
      $this->addElement('th', null, 'Date');
      $this->addElement('th', null, 'Source');
      $this->closeElement('tr');
      $this->closeElement('thead');

      $this->openElement('tbody');

      $testSuiteName  = $this->mTestSuite->getName();
      
      $formats = Format::GetFormatsFor($this->mTestSuite);
      $this->mUserAgents = UserAgent::GetAllUserAgents();
      
      $testCases = $this->mResults->getTestCases();
      foreach ($testCases as $testCaseId => $testCaseData) {
        $engineResults = $this->mResults->getResultsFor($testCaseId);
        
        if ($engineResults) {
          ksort($engineResults);
          
          $testCaseName   = $testCaseData['testcase'];
          
          foreach ($engineResults as $engine => $engineResultData) {
            if ((! $this->mEngine) || (0 == strcasecmp($engine, $this->mEngine))) {
              $results = array();
              foreach ($engineResultData as $resultId => $resultValue) {
                $results[] = new Result($resultId);
              }
              uasort($results, array($this, '_compareResults'));
              
              foreach ($results as $result) {
                $resultValue = $result->getResult();

                $this->openElement('tr', array('class' => $resultValue));

                $userAgent = $this->mUserAgents[$result->getUserAgentId()];
                $sourceId = $result->getSourceId();
                if ($sourceId) {
                  $user = new User($sourceId);
                  $source = $user->getName();
                }
                else {
                  $source = '';
                }

                $this->openElement('td');
                
                if ($this->mDisplayLinks) {
                  $this->addSpiderTrap();
                
                  $args['s'] = $testSuiteName;
                  $args['c'] = $testCaseName;
                  $args['f'] = $result->getFormatName();
                  $args['u'] = $this->mUserAgent->getId();
                  $uri = $this->buildURI(TESTCASE_PAGE_URI, $args);
                  
                  $this->addHyperLink($uri, array('name' => $testCaseName), $testCaseName);
                }
                else {
                  $this->addElement('a', array('name' => $testCaseName), $testCaseName);
                }
                $this->closeElement('td');

                $this->addElement('td', null, $formats[strtolower($result->getFormatName())]->getTitle());
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
      }
      $this->closeElement('tbody');
      $this->closeElement('table');
    }
  }
  
  function writeLedgend()
  {
    $this->addElement('h2', null, 'Legend');
    
    $this->openElement('table', array('class' => 'legend'));
    
    $this->openElement('thead');
    $this->openElement('tr');
    $this->addElement('th', null, 'Row color codes');
    $this->closeElement('tr');
    $this->closeElement('thead');
    
    $this->openElement('tbody');

    $this->openElement('tr', array('class' => 'pass'));
    $this->addElement('td', null, 'test passed');
    $this->closeElement('tr');
    
    $this->openElement('tr', array('class' => 'fail'));
    $this->addElement('td', null, 'test failed');
    $this->closeElement('tr');
    
    $this->openElement('tr', array('class' => 'uncertain'));
    $this->addElement('td', null, 'result uncertain');
    $this->closeElement('tr');
    
    $this->openElement('tr', array('class' => 'invalid'));
    $this->addElement('td', null, 'reported as invalid');
    $this->closeElement('tr');
    
    $this->closeElement('tbody');
    $this->closeElement('table');
  }
  
  
  function writeBodyFooter()
  {
    $this->writeLedgend();
    
    parent::writeBodyFooter();
  }  
}

?>