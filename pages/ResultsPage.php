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


/**
 * Class for generating the page of result data
 */
class ResultsPage extends HarnessPage
{  
  protected $mResults;

  protected $mDisplayFilter;                  // bitflag to supress rows: 1=pass, 2=fail, 4=uncertain, 8=invalid, 0x10=optional
  protected $mModified;                       // modified date for results
  
  protected $mTestCaseRequiredCount;          // number of required tests
  protected $mTestCaseRequiredPassCount;      // number of required tests with 2 or more passes
  protected $mTestCaseOptionalCount;          // number of optional tests ('may' or 'should')
  protected $mTestCaseOptionalPassCount;      // number of optional tests with 2 or more passes
  protected $mTestCaseInvalidCount;           // number of tests reported as invalid
  protected $mTestCaseNeededCount;            // number of required, valid tests that do not have 2 or more passes
  protected $mTestCaseNeedMoreResults;        // number of needed tests that might pass but need more results
  protected $mTestCaseTooManyFails;           // number of needed tests that have fails blocking exit criteria
  protected $mTestCaseNeededCountPerEngine;   // number of needed results per engine

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

    $this->mModified = $this->_getData('m');
    $grouping = $this->_getData('x');
    $engine = $this->_getData('e');
    $engineVersion = $this->_getData('v');
    $platform = $this->_getData('p');

    $this->mResults = 
      new Results($this->mTestSuite, $testCaseName, $specLinkId,
                  $engine, $engineVersion, $platform, 
                  $this->mModified);

    foreach ($this->mResults->getEngines() as $engine) {
      $this->mTestCaseNeededCountPerEngine[$engine] = 0;
    }
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
  function writeHeadStyle($indent = '')
  {  
    parent::writeHeadStyle($indent);

    echo $indent . "<link rel='stylesheet' href='report.css' type='text/css'>\n";
  }


  function _generateRow($indent, $testCaseName, $engineResults, $optional)
  {
    $hasResults   = FALSE;
    $testInvalid  = FALSE;
    $passCount    = 0;
    $failCount    = 0;
    $row = '';
    foreach ($this->mResults->getEngines() as $engine) {
      $engineMissing[$engine] = TRUE;
      if ($engineResults && array_key_exists($engine, $engineResults)) {
        $hasResults = TRUE;
        $pass      = ((array_key_exists('pass', $engineResults[$engine])) ? $engineResults[$engine]['pass'] : 0);
        $fail      = ((array_key_exists('fail', $engineResults[$engine])) ? $engineResults[$engine]['fail'] : 0);
        $uncertain = ((array_key_exists('uncertain', $engineResults[$engine])) ? $engineResults[$engine]['uncertain'] : 0);
        $invalid   = ((array_key_exists('invalid', $engineResults[$engine])) ? $engineResults[$engine]['invalid'] : 0);
        $na        = ((array_key_exists('na', $engineResults[$engine])) ? $engineResults[$engine]['na'] : 0);
        $class = '';
        if (0 < $pass) {
          $passCount++;
          $engineMissing[$engine] = FALSE;
          $class .= 'pass ';
        }
        if (0 < $fail) {
          if (0 == $pass) {
            $failCount++;
          }
          $engineMissing[$engine] = FALSE;
          $class .= 'fail ';
        }
        if (0 < $uncertain) {
          $class .= 'uncertain ';
        }
        if (0 < $invalid) {
          $class .= 'invalid ';
          $testInvalid = TRUE;
        }
        if (0 < $na) {
          $class .= 'na ';
        }
        $row .= "<td class='{$class}'>";
        $args['s'] = $this->mTestSuite->getName();
        $args['c'] = $testCaseName;
        $args['e'] = $engine;
        $args['u'] = $this->mUserAgent->getId();
        if ($this->mModified) {
          $args['m'] = $this->mModified;
        }
        $detailsURI = $this->encodeURI(DETAILS_PAGE_URI, $args);
        $row .= "<a href='{$detailsURI}' target='details'>";
        $row .= ((0 < $pass) ? $pass : '.') . '&nbsp;/&nbsp;';
        $row .= ((0 < $fail) ? $fail : '.') . '&nbsp;/&nbsp;';
        $row .= ((0 < $uncertain) ? $uncertain : '.');
        $row .= "</a>";
        $row .= "</td>";
      }
      else {
        $row .= "<td>&nbsp;</td>";
      }
    }
    $display = TRUE;
    $class = '';
    if ($testInvalid) {
      $class .= 'invalid ';
      $display = (($this->mDisplayFilter & 0x8) == 0);
      $this->mTestCaseInvalidCount++;
    }
    else {
      if ($optional) {
        $class .= 'optional ';
        $display = $display & (($this->mDisplayFilter & 0x10) == 0);
        $this->mTestCaseOptionalCount++;
      }
      else {
        $this->mTestCaseRequiredCount++;
      }
      if (1 < $passCount) {
        $class .= 'pass';
        $display = $display & (($this->mDisplayFilter & 0x01) == 0);
        if ($optional) {
          $this->mTestCaseOptionalPassCount++;
        } else {
          $this->mTestCaseRequiredPassCount++;
        }
      }
      else {
        if ((! $testInvalid) && (! $optional)) {
          $this->mTestCaseNeededCount++;
          if ($failCount < ($this->mResults->getEngineCount() - 1)) {
            $class .= 'uncertain';
            $display = $display & (($this->mDisplayFilter & 0x04) == 0);
            $this->mTestCaseNeedMoreResults++;
            foreach ($engineMissing as $engine => $missing) {
              if ($missing) {
                $this->mTestCaseNeededCountPerEngine[$engine]++;
              }
            }
          }
          else {
            $class .= 'fail';
            $display = $display & (($this->mDisplayFilter & 0x02) == 0);
            $this->mTestCaseTooManyFails++;
          }
        }
      }
    }
    if ($display) {
      echo $indent . "  <tr class='{$class}'>";
      echo "<td>{$this->mSpiderTrap->getTrapLink()}";
      unset($args);
      $args['s'] = $this->mTestSuite->getName();
      $args['c'] = $testCaseName;
      $args['u'] = $this->mUserAgent->getId();
      if ($hasResults) {
        if ($this->mModified) {
          $args['m'] = $this->mModified;
        }
        $uri = $this->encodeURI(DETAILS_PAGE_URI, $args);
        $uriTarget = " target='details'";
      }
      else {
        $uri = $this->encodeURI(TESTCASE_PAGE_URI, $args);
        $uriTarget = '';
      }
      echo "<a href='{$uri}'{$uriTarget}>{$testCaseName}</a></td>";
      echo "{$row}</tr>\n";
    }
  }


  /**
   * Write HTML for a table displaying result data
   */
  function writeBodyContent($indent = '')
  {
    if ($this->mResults->getResultCount()) {
    
      echo $indent . "<table>\n";
      echo $indent . "  <tr>\n";
      echo $indent . "    <th>Testcase</th>\n";
      foreach ($this->mResults->getEngines() as $engine) {
        $engine = self::Encode($engine);
        echo $indent . "    <th>{$engine}</th>\n";
      }
      echo $indent . "  </tr>\n";
      
      $this->mTestCaseRequiredCount = 0;
      $this->mTestCaseRequiredPassCount = 0;
      $this->mTestCaseOptionalCount = 0;
      $this->mTestCaseOptionalPassCount = 0;
      $this->mTestCaseInvalidCount = 0;
      $this->mTestCaseNeededCount = 0;
      $this->mTestCaseNeedMoreResults = 0;
      $this->mTestCaseTooManyFails = 0;
  
      $testCases = $this->mResults->getTestCases();
      foreach ($testCases as $testCaseData) {
        $testCaseId   = $testCaseData['id'];
        $testCaseName = $testCaseData['testcase'];

        $flags = new Flags($testCaseData['flags']);
        $optional = $flags->isOptional();
        
        $engineResults = $this->mResults->getResultCountsFor($testCaseId);

        $this->_generateRow($indent, $testCaseName, $engineResults, $optional);
      }
      
      echo $indent . "</table>\n";
      echo $indent . "<p>{$this->mTestCaseRequiredPassCount} of {$this->mTestCaseRequiredCount} required tests meet exit criteria.</p>\n";
      echo $indent . "<p>{$this->mTestCaseOptionalPassCount} of {$this->mTestCaseOptionalCount} optional tests meet exit criteria.</p>\n";
      echo $indent . "<p>{$this->mTestCaseInvalidCount} tests reported as invalid.</p>\n";
      echo $indent . "<p>{$this->mTestCaseNeededCount} required tests are considered valid and do not meet exit criteria.</p>\n";
      if (0 < $this->mTestCaseNeededCount) {
        echo $indent . "<p>{$this->mTestCaseTooManyFails} required tests have blocking failures.</p>\n";
        echo $indent . "<p>{$this->mTestCaseNeedMoreResults} required tests might pass but lack data.</p>\n";
        if (0 < $this->mTestCaseNeedMoreResults) {
          echo $indent . "<p>Additional results needed from:</p>\n";
          echo $indent . "<ul>\n";
          foreach ($this->mTestCaseNeededCountPerEngine as $engine => $count) {
            if (0 < $count) {
              echo $indent . "  <li>{$engine}: $count</li>\n";
            }
          }
          echo $indent . "</ul>\n";
        }
      }
      else {
        echo $indent . "<p>Exit criteria have been met.</p>\n";
      }
    }
    else {
      echo $indent . "<p>No results entered matching this query.</p>\n";
    }
  }

  
  function writeBodyFooter($indent = '')
  {
    echo $indent . "<h2>Legend</h2>\n";
    echo $indent . "<table class='legend'>\n";
    echo $indent . "  <tr><th>Row color codes</tr>\n";
    echo $indent . "  <tr class='pass'><td>two or more passes</tr>\n";
    echo $indent . "  <tr class='fail'><td>blocking failures</tr>\n";
    echo $indent . "  <tr class='uncertain'><td>not enough results</tr>\n";
    echo $indent . "  <tr class='invalid'><td>reported as invalid</tr>\n";
    echo $indent . "  <tr class='optional'><td>not passing, but optional</tr>\n";
    echo $indent . "</table>\n";

    echo $indent . "<table class='legend'>\n";
    echo $indent . "  <tr><th>Result color codes</tr>\n";
    echo $indent . "  <tr><td class='pass'>all results pass</tr>\n";
    echo $indent . "  <tr><td class='pass fail'>pass reported, but also other results</tr>\n";
    echo $indent . "  <tr><td class='fail'>all results fail</tr>\n";
    echo $indent . "  <tr><td class='fail uncertain'>fail reported, but also other results</tr>\n";
    echo $indent . "  <tr><td class='uncertain'>all results uncertain</tr>\n";
    echo $indent . "  <tr><td class='invalid'>reported as invalid</tr>\n";
    echo $indent . "  <tr><td># pass / # fail / # uncertian</tr>\n";
    echo $indent . "</table>\n";

    parent::writeBodyFooter($indent);
  }
}

?>