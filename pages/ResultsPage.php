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


require_once('lib/ResultsBasedPage.php');
require_once('lib/Engine.php');
require_once('lib/Sections.php');
require_once('lib/Specification.php');


/**
 * Class for generating the page of result data
 */
class ResultsPage extends ResultsBasedPage
{  
  protected $mDisplayLinks;
  protected $mDisplayFilter;                  // bitflag to supress rows: 1=pass, 2=fail, 4=uncertain, 8=invalid, 0x10=optional
  protected $mModified;                       // modified date for results
  protected $mGroup;                          // spec section id
  protected $mOrdering;                       // display ordering
  
  protected $mSpecification;
  protected $mSections;
  protected $mTestCaseCounted;                // array of test case ids already counted for stats
  
  protected $mTestCaseRequiredCount;          // number of required tests
  protected $mTestCaseRequiredPassCount;      // number of required tests with 2 or more passes
  protected $mTestCaseOptionalCount;          // number of optional tests ('may' or 'should')
  protected $mTestCaseOptionalPassCount;      // number of optional tests with 2 or more passes
  protected $mTestCaseInvalidCount;           // number of tests reported as invalid
  protected $mTestCaseNeededCount;            // number of required, valid tests that do not have 2 or more passes
  protected $mTestCaseNeedMoreResults;        // number of needed tests that might pass but need more results
  protected $mTestCaseTooManyFails;           // number of needed tests that have fails blocking exit criteria
  protected $mTestCaseEngineNeededCount;      // number of needed results per engine
  protected $mTestCaseEnginePassCount;        // number of passes per engine
  protected $mTestCaseEngineResultCount;      // number of results per engine

  /**
   * Expected URL paramaters:
   * 's' Test Suite Name
   * 'c' Test Case Name
   * 'g' Spec Section Id (optional)
   * 't' Report type (override 'c' & 'g', 0 = entire suite, 1 = group, 2 = one test)
   * 'f' Result filter (array or bitfield)
   * 'm' Modified date (optional, only results before date)
   * 'e' Engine (optional, filter results for this engine)
   * 'v' Engine Version (optional)
   * 'p' Platform
   * 'o' Ordering (optional) 0 = one list, 1 = group by section
   */
  function __construct(Array $args = null) 
  {
    parent::__construct($args);

    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      trigger_error($msg, E_USER_WARNING);
    }
    
    $this->mDisplayLinks = TRUE;

    $filter = $this->_getData('f');
    if (is_array($filter)) {
      $filterValue = 0;
      foreach ($filter as $value) {
        $filterValue = $filterValue | intval($value);
      }
      $this->mDisplayFilter = $filterValue;
    }
    else {
      $this->mDisplayFilter = intval($filter);
    }

    $this->mModified = $this->_getData('m', 'DateTime');
    $this->mGroup = intval($this->_getData('g'));
    $this->mOrdering = intval($this->_getData('o'));
    if ($this->_getData('c')) {
      $this->mOrdering = 0;
    }
    
    $this->mTestCaseCounted = array();
  }
  
  
  function getPageTitle()
  {
    $title = parent::getPageTitle();
    return "{$title} Results";
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
      
      $title = "Results";
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


  function _generateRow($testCaseName, $testCaseId, $optional, $getStats, $section)
  {
    $engineResults = $this->mResults->getResultCountsFor($testCaseId);
    $componentTestIds = $this->mResults->getComponentTestsFor($testCaseId);
  
    $hasResults   = FALSE;
    $testInvalid  = FALSE;
    $passCount    = 0;
    $failCount    = 0;
    
    $cells = array();
    foreach ($this->mResults->getEngineNames() as $engineName) {
      $engineMissing[$engineName] = TRUE;
      if ($engineResults && (0 < $engineResults[$engineName]['count'])) {
        $hasResults = TRUE;
        $pass      = $engineResults[$engineName]['pass'];
        $fail      = $engineResults[$engineName]['fail'];
        $uncertain = $engineResults[$engineName]['uncertain'];
        $invalid   = $engineResults[$engineName]['invalid'];

        if ($getStats) {
          $this->mTestCaseEngineResultCount[$engineName]++;
        }
        $class = '';
        if (0 < $pass) {
          $passCount++;
          $engineMissing[$engineName] = FALSE;
          if ($getStats) {
            $this->mTestCaseEnginePassCount[$engineName]++;
          }
          $class .= 'pass ';
        }
        if (0 < $fail) {
          if (0 == $pass) {
            $failCount++;
          }
          $engineMissing[$engineName] = FALSE;
          $class .= 'fail ';
        }
        if (0 < $uncertain) {
          $class .= 'uncertain ';
        }
        if (0 < $invalid) {
          $class .= 'invalid ';
          $testInvalid = TRUE;
        }

        $content  = ((0 < $pass) ? $pass : '.') . ' / ';
        $content .= ((0 < $fail) ? $fail : '.') . ' / ';
        $content .= ((0 < $uncertain) ? $uncertain : '.');
        
        $cells[] = array($engineName, $class, $section, $content); 
      }
      else {
        $cells[] = '';
      }
    }
    
    $display = TRUE;
    $class = '';
    if ($testInvalid) {
      $class .= 'invalid ';
      $display = (($this->mDisplayFilter & 0x8) == 0);
      if ($getStats) {
        $this->mTestCaseInvalidCount++;
      }
    }
    else {
      if ($optional) {
        $class .= 'optional ';
        $display = $display & (($this->mDisplayFilter & 0x10) == 0);
        if ($getStats) {
          $this->mTestCaseOptionalCount++;
        }
      }
      else {
        if ($getStats) {
          $this->mTestCaseRequiredCount++;
        }
      }
      $allComponentsPass = FALSE;
      if (($passCount < 2) && $componentTestIds) {
        // look for all components passed
        $componentTestPassCount = 0;
        foreach ($componentTestIds as $componentTestId) {
          $componentResults = $this->mResults->getResultCountsFor($componentTestId);
          $componentPassCount = 0;
          foreach ($componentResults as $componentEngine => $componentEngineResults) {
            if ((0 == $componentEngineResults['invalid']) && 
                (0 < $componentEngineResults['pass'])) {
              $componentPassCount++;
            }
          }
          if (1 < $componentPassCount) {
            $componentTestPassCount++;
          }
          else {
            break;
          }
        }
        if ($componentTestPassCount == count($componentTestIds)) {
          $allComponentsPass = TRUE;
        }
      }
      if ((1 < $passCount) || ($allComponentsPass)) { // passed
        $class .= 'pass';
        if ($passCount < 2) {
          $class .= ' bycomponents';
        }
        $display = $display & (($this->mDisplayFilter & 0x01) == 0);
        if ($getStats) {
          if ($optional) {
            $this->mTestCaseOptionalPassCount++;
          } else {
            $this->mTestCaseRequiredPassCount++;
          }
        }
      }
      else {
        if (! $optional) {
          if ($getStats) {
            $this->mTestCaseNeededCount++;
          }
          if ($failCount < ($this->mResults->getEngineCount() - 1)) {
            $class .= 'uncertain';
            $display = $display & (($this->mDisplayFilter & 0x04) == 0);
            if ($getStats) {
              $this->mTestCaseNeedMoreResults++;
              foreach ($engineMissing as $engineName => $missing) {
                if ($missing) {
                  $this->mTestCaseEngineNeededCount[$engineName]++;
                }
              }
            }
          }
          else {
            $class .= 'fail';
            $display = $display & (($this->mDisplayFilter & 0x02) == 0);
            if ($getStats) {
              $this->mTestCaseTooManyFails++;
            }
          }
        }
      }
    }
    if ($display) {
      $this->openElement('tr', array('class' => $class));
      
      $this->_generateTestCaseCell($testCaseName, $hasResults);
      
      foreach ($cells as $cell) {
        if (is_array($cell)) {
          list($engineName, $class, $section, $content) = $cell;
          $this->_generateResultCell($testCaseName, $engineName, $class, $section, $content);
        }
        else {
          $this->addElement('td', null, $cell, FALSE);
        }
      }
      $this->closeElement('tr');
      return TRUE;
    }
    return FALSE;
  }
  
  
  function _generateTestCaseCell($testCaseName, $hasResults)
  {
    $this->openElement('td', null, FALSE);
    
    if ($this->mDisplayLinks) {
      $this->addSpiderTrap();

      $args['s'] = $this->mTestSuite->getName();
      $args['c'] = $testCaseName;
      $args['u'] = $this->mUserAgent->getId();
      if ($hasResults) {
        if ($this->mModified) {
          $args['m'] = $this->mModified;
        }
        $uri = $this->buildURI(DETAILS_PAGE_URI, $args);
      }
      else {
        $uri = $this->buildURI(TESTCASE_PAGE_URI, $args);
      }
      
      $this->addHyperLink($uri, array('name' => $testCaseName), $testCaseName);
    }
    else {
      $this->addElement('a', array('name' => $testCaseName), $testCaseName);
    }
    $this->closeElement('td');
  }
  
  
  function _generateResultCell($testCaseName, $engineName, $class, $section, $content)
  {
    if ($this->mDisplayLinks) {
      $args['s'] = $this->mTestSuite->getName();
      $args['c'] = $testCaseName;
      $args['e'] = $engineName;
      $args['u'] = $this->mUserAgent->getId();
      if ($this->mModified) {
        $args['m'] = $this->mModified;
      }
      $uri = $this->buildURI(DETAILS_PAGE_URI, $args);
      
      $this->openElement('td', array('class' => $class), FALSE);
      $this->addHyperLink($uri, null, $content, FALSE);
      $this->closeElement('td');
    }
    else {
      $this->addElement('td', array('class' => $class), $content, FALSE);
    }
  }

  
  function writeRow($testCaseData, $section = null)
  {
    $testCaseId   = intval($testCaseData['id']);
    
    $needStats = (! array_key_exists($testCaseId, $this->mTestCaseCounted));
    $this->mTestCaseCounted[$testCaseId] = TRUE;

    $testCaseName = $testCaseData['testcase'];
    
    $flags = new Flags($testCaseData['flags']);
    $optional = $this->mTestSuite->testIsOptional($flags);

    return $this->_generateRow($testCaseName, $testCaseId, $optional, $needStats, $section);
  }
  
  
  function writeGroupRows($specLinkId)
  {
    if (0 < $specLinkId) {
      $sectionData = $this->mSections->getSectionData($specLinkId);
      $testCount = intval($sectionData['test_count']);

      if (0 < $testCount) {
        $this->_beginBuffering();
        $hadOutput = FALSE;
        
        $this->openElement('tbody', array('id' => "s{$sectionData['section']}"));
        $this->openElement('tr');
        $this->openElement('th', array('colspan' => ($this->mResults->getEngineCount() + 1), 'scope' => 'rowgroup'));
        $specURI = $this->mSpecification->getBaseURI() . $sectionData['uri'];
        $this->addHyperLink($specURI, null, "{$sectionData['section']}: {$sectionData['title']}");
        $this->closeElement('th');
        $this->closeElement('tr');

        $testCasesIds = $this->mSections->getTestCaseIdsFor($specLinkId);
        
        foreach ($testCasesIds as $testCaseId) {
          if ($this->writeRow($this->mResults->getTestCaseData($testCaseId), $sectionData['section'])) {
            $hadOutput = TRUE;
          }
        }
        
        $this->closeElement('tbody');
        $this->_endBuffering(! $hadOutput);
      }
    }
  
    $subSections = $this->mSections->getSubSectionData($specLinkId);
    if ($subSections) {
      foreach ($subSections as $subSectionId => $sectionData) {
        $testCount = intval($sectionData['test_count']);
        if ((0 < $testCount) || (0 < $this->mSections->getSubSectionCount($subSectionId))) {
          $this->writeGroupRows($subSectionId);
        }
      }
    }
  }
  
  function writeResultTable()
  {
    $engines = Engine::GetAllEngines();
    
    $this->openElement('table');

    $this->openElement('thead');
    $this->openElement('tr');
    $this->addElement('th', null, 'Testcase');
    foreach ($this->mResults->getEngineNames() as $engineName) {
      $this->addElement('th', array('class' => 'engine'), $engines[$engineName]->getTitle());
    }
    $this->closeElement('tr');
    $this->closeElement('thead');
    
    if (0 == $this->mOrdering) {
      $this->openElement('tbody');
      $testCases = $this->mResults->getTestCases();
      foreach ($testCases as $testCaseData) {
        $this->writeRow($testCaseData);
      }
      $this->closeElement('tbody');
    }
    else {
      $this->mSections = new Sections($this->mTestSuite, TRUE);
      $this->mSpecification = new Specification($this->mTestSuite);
      $this->writeGroupRows($this->mGroup);
    }
    
    $testCount = $this->mResults->getTestCaseCount();
    $this->openElement('tfoot');
    $this->openElement('tr');
    $this->addElement('th', null, 'Passed');
    foreach ($this->mResults->getEngineNames() as $engineName) {
      $passPercentage = round(($this->mTestCaseEnginePassCount[$engineName] / $testCount) * 100.0, 2);
      $this->addElement('th', null, "{$passPercentage}%");
    }
    $this->closeElement('tr');
    
    $this->openElement('tr');
    $this->addElement('th', null, 'Coverage');
    foreach ($this->mResults->getEngineNames() as $engineName) {
      $coveragePercentage = round(($this->mTestCaseEngineResultCount[$engineName] / $testCount) * 100.0, 2);
      $this->addElement('th', null, "{$coveragePercentage}%");
    }
    $this->closeElement('tr');
    $this->closeElement('tfoot');
    
    $this->closeElement('table');
  }
  
  
  function resetStats()
  {
    $this->mTestCaseRequiredCount = 0;
    $this->mTestCaseRequiredPassCount = 0;
    $this->mTestCaseOptionalCount = 0;
    $this->mTestCaseOptionalPassCount = 0;
    $this->mTestCaseInvalidCount = 0;
    $this->mTestCaseNeededCount = 0;
    $this->mTestCaseNeedMoreResults = 0;
    $this->mTestCaseTooManyFails = 0;
    
    $this->mTestCaseEngineNeededCount = array();
    $this->mTestCaseEnginePassCount = array();
    $this->mTestCaseEngineResultCount = array();

    foreach ($this->mResults->getEngineNames() as $engineName) {
      $this->mTestCaseEngineNeededCount[$engineName] = 0;
      $this->mTestCaseEnginePassCount[$engineName] = 0;
      $this->mTestCaseEngineResultCount[$engineName] = 0;
    }
  }
  
  
  function writeSummary()
  {
    $engines = Engine::GetAllEngines();
    
    $this->addElement('p', null, "{$this->mTestCaseRequiredPassCount} of {$this->mTestCaseRequiredCount} required tests meet CR exit criteria.");
    if (0 < $this->mTestCaseOptionalCount) {
      $this->addElement('p', null, "{$this->mTestCaseOptionalPassCount} of {$this->mTestCaseOptionalCount} optional tests meet CR exit criteria.");
    }
    if (0 < $this->mTestCaseInvalidCount) {
      if (1 == $this->mTestCaseInvalidCount) {
        $this->addElement('p', null, "1 test reported as invalid.");
      }
      else {
        $this->addElement('p', null, "{$this->mTestCaseInvalidCount} tests reported as invalid.");
      }
    }
    if (0 < $this->mTestCaseNeededCount) {
      if (1 == $this->mTestCaseNeededCount) {
        $this->addElement('p', null, "1 required test is considered valid and does not meet CR exit criteria.");
      }
      else {
        $this->addElement('p', null, "{$this->mTestCaseNeededCount} required tests are considered valid and do not meet CR exit criteria.");
      }
      if (1 == $this->mTestCaseTooManyFails) {
        $this->addElement('p', null, "1 required test has blocking failures.");
      }
      else {
        $this->addElement('p', null, "{$this->mTestCaseTooManyFails} required tests have blocking failures.");
      }
      if (1 == $this->mTestCaseNeedMoreResults) {
        $this->addElement('p', null, "1 required test might pass but lacks data.");
      }
      else {
        $this->addElement('p', null, "{$this->mTestCaseNeedMoreResults} required tests might pass but lack data.");
      }
      if (0 < $this->mTestCaseNeedMoreResults) {
        $this->addElement('p', null, "Additional results needed from:");
        $this->openElement('ul');
        foreach ($this->mTestCaseEngineNeededCount as $engineName => $count) {
          if (0 < $count) {
            $this->addElement('li', null, "{$engines[$engineName]->getTitle()}: $count");
          }
        }
        $this->closeElement('ul');
      }
    }
    else {
      $this->addElement('p', null, "CR exit criteria have been met.");
    }
  }
  
  
  /**
   * Write HTML for a table displaying result data
   */
  function writeBodyContent()
  {
    $this->loadResults();
    
    if ($this->mResults->getResultCount()) {
      $this->resetStats();    
      
      $this->writeResultTable();
      
      $this->writeSummary();
    }
    else {
      $this->addElement('p', null, "No results entered matching this query.");
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
    $this->addElement('td', null, 'two or more passes');
    $this->closeElement('tr');
    
    $this->openElement('tr', array('class' => 'pass bycomponents'));
    $this->addElement('td', null, '* combo test passed by component tests');
    $this->closeElement('tr');
    
    $this->openElement('tr', array('class' => 'fail'));
    $this->addElement('td', null, 'blocking failures');
    $this->closeElement('tr');
    
    $this->openElement('tr', array('class' => 'uncertain'));
    $this->addElement('td', null, 'not enough results');
    $this->closeElement('tr');
    
    $this->openElement('tr', array('class' => 'invalid'));
    $this->addElement('td', null, 'reported as invalid');
    $this->closeElement('tr');
    
    $this->openElement('tr', array('class' => 'optional'));
    $this->addElement('td', null, 'not passing, but optional');
    $this->closeElement('tr');
    
    $this->closeElement('tbody');
    $this->closeElement('table');
    
    $this->openElement('table', array('class' => 'legend'));

    $this->openElement('thead');
    $this->openElement('tr');
    $this->addElement('th', null, 'Result color codes');
    $this->closeElement('tr');
    $this->closeElement('thead');
    
    $this->openElement('tbody');
    $this->openElement('tr');
    $this->addElement('td', array('class' => 'pass'), 'all results pass');
    $this->closeElement('tr');
    
    $this->openElement('tr');
    $this->addElement('td', array('class' => 'pass fail'), 'pass reported, but also other results');
    $this->closeElement('tr');
    
    $this->openElement('tr');
    $this->addElement('td', array('class' => 'fail'), 'all results fail');
    $this->closeElement('tr');
    
    $this->openElement('tr');
    $this->addElement('td', array('class' => 'fail uncertain'), 'fail reported, but also other results');
    $this->closeElement('tr');
    
    $this->openElement('tr');
    $this->addElement('td', array('class' => 'uncertain'), 'all results uncertain');
    $this->closeElement('tr');
    
    $this->openElement('tr');
    $this->addElement('td', array('class' => 'invalid'), 'reported as invalid');
    $this->closeElement('tr');
    
    $this->openElement('tr');
    $this->addElement('td', null, '# pass / # fail / # uncertain');
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