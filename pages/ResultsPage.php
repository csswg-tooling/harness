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
require_once('lib/Sections.php');

require_once('modules/useragent/Engine.php');
require_once('modules/specification/Specification.php');
require_once('modules/specification/SpecificationAnchor.php');


/**
 * Class for generating the page of result data
 */
class ResultsPage extends ResultsBasedPage
{  
  protected $mDisplayLinks;
  protected $mDisplayFilter;                  // bitflag to supress rows: 1=pass, 2=fail, 4=uncertain, 8=invalid, 0x10=optional
  protected $mOrdering;                       // display ordering
  protected $mOptionalFlags;
  
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

  static function GetPageKey()
  {
    return 'results';
  }

  /**
   * Additional URL paramaters:
   * 'filter' Result filter (array or bitfield)
   * 'order' Ordering (optional) 0 = one list, 1 = group by section
   */
  function _initPage()
  {
    parent::_initPage();

    $this->mDisplayLinks = TRUE;

    $filter = $this->_getData('filter');
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

    $this->mOrdering = intval($this->_getData('order'));
    if ($this->mTestCase) {
      $this->mOrdering = 0;
    }
    
    if ($this->mTestSuite && $this->mTestSuite->isValid()) {
      $this->mOptionalFlags = $this->mTestSuite->getOptionalFlags();
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
      $args['suite'] = $this->mTestSuite->getName();
      $args['ua'] = $this->mUserAgent->getId();

      $uri = $this->buildPageURI('review', $args);
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

    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.report'));
  }


  function _generateRow(TestCase $testCase, $optional, $getStats, $section, $isPrimarySection)
  {
    $engineResults = $this->mResults->getResultCountsFor($testCase);
    $componentTests = $this->mResults->getComponentTestsFor($testCase);
  
    $hasResults   = FALSE;
    $testInvalid  = FALSE;
    $passCount    = 0;
    $failCount    = 0;
    
    $cells = array();
    foreach ($this->mResults->getEngines() as $engineName => $engine) {
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
        
        $cells[] = array($engineName, $class, $content);
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
      if (($passCount < 2) && $componentTests) {
        // look for all components passed
        $componentTestPassCount = 0;
        foreach ($componentTests as $componentTestId => $componentTest) {
          $componentResults = $this->mResults->getResultCountsFor($componentTest);
          if (! $componentResults) {
            break;
          }
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
        if ($componentTestPassCount == count($componentTests)) {
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
      
      $this->_generateTestCaseCell($testCase, $section, $hasResults, $isPrimarySection);
      
      foreach ($cells as $cell) {
        if (is_array($cell)) {
          list($engineName, $class, $content) = $cell;
          $this->_generateResultCell($testCase, $engineName, $class, $section, $content);
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
  
  
  function _copyArgs(Array $source, Array &$destination, Array $filterKeys)
  {
    foreach ($filterKeys as $key) {
      if (array_key_exists($key, $source)) {
        $destination[$key] = $source[$key];
      }
    }
  }
  
  
  function _generateTestCaseCell(TestCase $testCase, $section, $hasResults, $isPrimarySection)
  {
    $attrs = null;
    if ($isPrimarySection) {
      $attrs['class'] = 'primary';
    }
    $this->openElement('td', $attrs, FALSE);
    
    if ($section) {
      $anchor = array('name' => "s{$section->getName()}_{$testCase->getName()}");
    }
    else {
      $anchor = array('name' => $testCase->getName());
    }
    if ($this->mDisplayLinks) {
      $this->addSpiderTrap();

      $args['suite'] = $this->mTestSuite->getName();
      $args['testcase'] = $testCase->getName();
      $args['ua'] = $this->mUserAgent->getId();
      if ($hasResults) {
        $this->_copyArgs($this->_uriData(), $args, array('modified', 'engine', 'version', 'browser', 'browser_version', 'platform', 'platform_version'));
        $uri = $this->buildPageURI('details', $args);
      }
      else {
        $uri = $this->buildPageURI('testcase', $args);
      }
      
      $this->addHyperLink($uri, $anchor, $testCase->getName());
    }
    else {
      $this->addElement('a', $anchor, $testCase->getName());
    }
    $this->closeElement('td');
  }
  
  
  function _generateResultCell(TestCase $testCase, $engineName, $class, $section, $content)
  {
    if ($this->mDisplayLinks) {
      $args['suite'] = $this->mTestSuite->getName();
      $args['testcase'] = $testCase->getName();
      $args['engine'] = $engineName;
      $args['ua'] = $this->mUserAgent->getId();
      $this->_copyArgs($this->_uriData(), $args, array('modified', 'version', 'browser', 'browser_version', 'platform', 'platform_version'));
      $uri = $this->buildPageURI('details', $args);
      
      $this->openElement('td', array('class' => $class), FALSE);
      $this->addHyperLink($uri, null, $content, FALSE);
      $this->closeElement('td');
    }
    else {
      $this->addElement('td', array('class' => $class), $content, FALSE);
    }
  }

  
  function writeRow(TestCase $testCase, SpecificationAnchor $section = null, $isPrimarySection = FALSE)
  {
    $testCaseId   = $testCase->getId();
    
    $needStats = (! array_key_exists($testCaseId, $this->mTestCaseCounted));
    $this->mTestCaseCounted[$testCaseId] = TRUE;

    $optional = $testCase->isOptional($this->mOptionalFlags);

    return $this->_generateRow($testCase, $optional, $needStats, $section, $isPrimarySection);
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
        $this->openElement('th', array('colspan' => ($this->mResults->getEngineCount() + 1), 'scope' => 'rowgroup'));
        $specURI = $section->getURI($spec);
        $this->addHyperLink($specURI, null, "{$section->getName()}: {$section->getTitle()}");
        $this->closeElement('th');
        $this->closeElement('tr');

        $testCases = $this->mResults->getTestCases();
        
        foreach ($testCaseIds as $testCaseId) {
          $testCase = $testCases[$testCaseId];
          $isPrimarySection = ($this->mSections->getPrimarySectionFor($testCase) == $section);
          if ($this->writeRow($testCase, $section, $isPrimarySection)) {
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
    $this->addElement('th', array('colspan' => ($this->mResults->getEngineCount() + 1)), $spec->getDescription());
    $this->closeElement('tr');
    $this->closeElement('tbody');
  }
  
  
  function writeResultTable()
  {
    $this->openElement('table');

    $this->openElement('thead');
    $this->openElement('tr');
    $this->addElement('th', null, 'Testcase');
    foreach ($this->mResults->getEngines() as $engineName => $engine) {
      $this->addElement('th', array('class' => 'engine'), $engine->getTitle());
    }
    $this->closeElement('tr');
    $this->closeElement('thead');
    
    if ((0 == $this->mOrdering) || ($this->mTestCase)) {
      $this->openElement('tbody');
      $testCases = $this->mResults->getTestCases();
      foreach ($testCases as $testCase) {
        $this->writeRow($testCase);
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
    
    $testCount = $this->mResults->getTestCaseCount();
    $this->openElement('tfoot');
    $this->openElement('tr');
    $this->addElement('th', null, 'Passed');
    foreach ($this->mResults->getEngines() as $engineName => $engine) {
      $passPercentage = round(($this->mTestCaseEnginePassCount[$engineName] / $testCount) * 100.0, 2);
      $this->addElement('th', null, "{$passPercentage}%");
    }
    $this->closeElement('tr');
    
    $this->openElement('tr');
    $this->addElement('th', null, 'Coverage');
    foreach ($this->mResults->getEngines() as $engineName => $engine) {
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

    foreach ($this->mResults->getEngines() as $engineName => $engine) {
      $this->mTestCaseEngineNeededCount[$engineName] = 0;
      $this->mTestCaseEnginePassCount[$engineName] = 0;
      $this->mTestCaseEngineResultCount[$engineName] = 0;
    }
  }
  
  
  function writeSummary()
  {
    $engines = $this->mResults->getEngines();
    
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
    
    $this->resetStats();
    
    $this->openElement('div', array('class' => 'body'));
    
    if ($this->mResults->getResultCount()) {
      $this->writeResultTable();
      
      $this->writeSummary();

      $this->writeLedgend();
    }
    else {
      $testCases = $this->mResults->getTestCases();
      foreach ($testCases as $testCaseId => $testCase) {
        if ($testCase->isOptional($this->mOptionalFlags)) {
          $this->mTestCaseOptionalCount++;
        }
        else {
          $this->mTestCaseRequiredCount++;
        }
      }

      $this->mTestCaseNeededCount = $this->mTestCaseRequiredCount;
      $this->mTestCaseNeedMoreResults = $this->mTestCaseNeededCount;
      
      foreach ($this->mResults->getEngines() as $engineName => $engine) {
        $this->mTestCaseEngineNeededCount[$engineName] = $this->mTestCaseNeededCount;
      }

      $this->addElement('p', null, "No results entered matching this query.");
    }
    
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
  
  
}

?>