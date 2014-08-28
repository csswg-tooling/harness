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
require_once("lib/Sections.php");

require_once("modules/testsuite/TestFormat.php");
require_once("modules/specification/Specification.php");
require_once("modules/specification/SpecificationAnchor.php");


/**
 * Class for generating the page for inspecting results for
 * individual tests
 */
class DetailsPage extends ResultsBasedPage
{  
  protected $mDisplayLinks;
  protected $mUserAgents;
  protected $mFormats;
  protected $mUsers;
  protected $mSections;
  protected $mOrdering;


  static function GetPageKey()
  {
    return 'details';
  }

  /**
   * Additional URL paramaters:
   * 'o' Ordering (optional)
   */
  function _initPage()
  {
    parent::_initPage();

    $this->mDisplayLinks = TRUE;

    $this->mOrdering = intval($this->_getData('order'));
    if ($this->mTestCase) {
      $this->mOrdering = 0;
    }
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
      $args['suite'] = $this->mTestSuite->getName();
      $args['ua'] = $this->mUserAgent->getId();

      $uri = $this->buildPageURI('review', $args);
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
    
    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.report'));
  }

  function _compareResults(Result $a, Result $b)
  {
    $resultOrder = array('pass' => '0', 'fail' => '1', 'uncertain' => '2', 'invalid' => '3');
  
    $userAgentA = $this->mUserAgents[$a->getUserAgentId()];
    $userAgentB = $this->mUserAgents[$b->getUserAgentId()];
    
    return strnatcasecmp($resultOrder[$a->getResult()] . $userAgentA->getDescription() . $a->getDateTime()->format('c'), 
                         $resultOrder[$b->getResult()] . $userAgentB->getDescription() . $b->getDateTime()->format('c'));
  }
  
  
  function _writeResultsFor(TestCase $testCase, SpecificationAnchor $section = null, $isPrimarySection = FALSE)
  {
    $haveResults = FALSE;
    $engineResults = $this->mResults->getResultsFor($testCase);
    
    if ($engineResults) {
      ksort($engineResults);
      
      $testCaseName = $testCase->getName();
      if ($section) {
        $anchor = array('name' => "s{$section->getName()}_{$testCase->getName()}");
      }
      else {
        $anchor = array('name' => $testCaseName);
      }
      
      foreach ($engineResults as $engineName => $engineResultData) {
        if ((! $this->mEngineName) || (0 == strcasecmp($engineName, $this->mEngineName))) {
          $results = array();
          foreach ($engineResultData as $resultId => $resultValue) {
            $results[] = new Result($resultId);
          }
          uasort($results, array($this, '_compareResults'));
          
          foreach ($results as $result) {
            $haveResults = TRUE;
            $resultValue = $result->getResult();

            $this->openElement('tr', array('class' => $resultValue));

            $userAgent = $this->mUserAgents[$result->getUserAgentId()];
            $userId = $result->getUserId();
            if ($userId) {
              $user = $this->mUsers[$userId];
              $source = $user->getFullName();
              if (! $source) {
                $ipAddress = $user->getIPAddress();
                if ($ipAddress->isValid()) {
                  $source = (($ipAddress->isIPv6()) ? $ipAddress->getIPv6String() : $ipAddress->getIPv4String());
                }
              }
            }
            else {
              $source = '';
            }


            $attrs = null;
            if ($isPrimarySection) {
              $attrs['class'] = 'primary';
            }
            $this->openElement('td', $attrs, FALSE);
            
            if ($this->mDisplayLinks) {
              $this->addSpiderTrap();
            
              $args['suite'] = $this->mTestSuite->getName();
              $args['testcase'] = $testCaseName;
              $args['format'] = $result->getFormatName();
              $args['ua'] = $this->mUserAgent->getId();
              $uri = $this->buildPageURI('testcase', $args);
              
              $this->addHyperLink($uri, $anchor, $testCaseName);
            }
            else {
              if ($anchor) {
                $this->addElement('a', $anchor, $testCaseName);
              }
              else {
                $this->addTextContent($testCaseName);
              }
            }
            $this->closeElement('td');

            $this->addElement('td', null, $this->mFormats[$result->getFormatName()]->getTitle());
            if (0 < ($result->getPassCount() + $result->getFailCount())) {
              $resultString = '';
              if ((('pass' != $resultValue) && ('fail' != $resultValue)) || 
                  (('pass' == $resultValue) && (0 < $result->getFailCount())) ||
                  (('fail' == $resultValue) && (0 < $result->getPassCount()))) {
                $resultString .= "{$resultValue} (";
              }
              if (0 < $result->getPassCount()) {
                $resultString .= "{$result->getPassCount()} pass";
                if (0 < $result->getFailCount()) {
                  $resultString .= ", ";
                }
              }
              if (0 < $result->getFailCount()) {
                $resultString .= "{$result->getFailCount()} fail";
              }
              if ((('pass' != $resultValue) && ('fail' != $resultValue)) || 
                  (('pass' == $resultValue) && (0 < $result->getFailCount())) ||
                  (('fail' == $resultValue) && (0 < $result->getPassCount()))) {
                $resultString .= ")";
              }
            }
            else {
              $resultString = $resultValue;
            }
            $this->addElement('td', null, $resultString);
            $this->openElement('td');
            $this->addAbbrElement($userAgent->getUAString(), null, $userAgent->getDescription());
            $this->closeElement('td');
            $this->addElement('td', null, $this->_getDateTimeString($result->getDateTime()));
            $this->addElement('td', null, $source);

            $this->closeElement('tr');
            $anchor = null; // only first row gets anchor
          }
        }
      }
    }
    return $haveResults;
  }
  

  function writeSectionRows(Specification $spec, SpecificationAnchor $section = null)
  {
    if ($section) {
      $testCaseIds = $this->mSections->getTestCaseIdsFor($spec, $section);

      if (0 < count($testCaseIds)) {
        $this->_beginBuffering();
        $hadOutput = FALSE;
        
        $this->openElement('tbody', array('id' => "s{$section->getName()}"));
        $this->openElement('tr');
        $this->openElement('th', array('colspan' => 6, 'scope' => 'rowgroup'));
        $specURI = $section->getURI($spec);
        $this->addHyperLink($specURI, null, "{$section->getName()}: {$section->getTitle()}");
        $this->closeElement('th');
        $this->closeElement('tr');

        $testCases = $this->mResults->getTestCases();
        
        foreach ($testCaseIds as $testCaseId) {
          $testCase = $testCases[$testCaseId];
          $isPrimarySection = ($this->mSections->getPrimarySectionFor($testCase) == $section);
          if ($this->_writeResultsFor($testCase, $section, $isPrimarySection)) {
            $hadOutput = TRUE;
          }
        }
        
        $this->closeElement('tbody');
        $this->_endBuffering(! $hadOutput);
      }
    }
  
    $subSections = $this->mSections->getSubSections($spec, $section);
    if ($subSections) {
      foreach ($subSections as $subSection) {
        $testCaseIds = $this->mSections->getTestCaseIdsFor($spec, $subSection);
        if ((0 < count($testCaseIds)) || (0 < $this->mSections->getSubSectionCount($spec, $subSection))) {
          $this->writeSectionRows($spec, $subSection);
        }
      }
    }
  }


  function writeSpecificationHeader(Specification $spec)
  {
    $this->openElement('tbody');
    $this->openElement('tr');
    $this->addElement('th', array('colspan' => 6), $spec->getDescription());
    $this->closeElement('tr');
    $this->closeElement('tbody');
  }
  
  
  /**
   * Output details table
   */
  function writeBodyContent()
  {
    $this->openElement('div', array('class' => 'body'));
    
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


      $this->mFormats = TestFormat::GetAllFormats();
      $this->mUserAgents = UserAgent::GetAllUserAgents();
      $this->mUsers = User::GetAllUsers();
      
      $testCases = $this->mResults->getTestCases();

      if (0 == $this->mOrdering) {
        $this->openElement('tbody');
        foreach ($testCases as $testCase) {
          $this->_writeResultsFor($testCase);
        }
        $this->closeElement('tbody');
      }
      else {
        $this->mSections = new Sections($this->mTestSuite, TRUE);
        if ($this->mSection) {
          $this->writeSectionRows($this->mSpec, $this->mSection);
        }
        elseif ($this->mSpec) {
          $this->writeSectionRows($this->mSpec);
        }
        else {
          $specs = $this->mSections->getSpecifications();
          foreach ($specs as $spec) {
            if (1 < count($specs)) {
              $this->writeSpecificationHeader($spec);
            }
            $this->writeSectionRows($spec);
          }
        }
      }
      
      $this->closeElement('table');
    }
    $this->writeLedgend();

    $this->closeElement('div');
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
    parent::writeBodyFooter();
  }  
}

?>