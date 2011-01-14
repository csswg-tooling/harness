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

require_once('lib/DBConnection.php');

/**
 * Gather and report test results per engine, computing CR Exit Criteria
 *
 */
class Results extends DBConnection
{
  protected $mEngines;
  protected $mEngineCount;                    // number of engines
  protected $mTestSuiteName;
  protected $mTestCases;
  protected $mResults;

  protected $mDisplayFilter;                  // bitflag to supress rows: 1=pass, 2=fail, 4=uncertain, 8=invalid, 0x10=optional
  
  protected $mTestCaseRequiredCount;          // number of required tests
  protected $mTestCaseRequiredPassCount;      // number of required tests with 2 or more passes
  protected $mTestCaseOptionalCount;          // number of optional tests ('may' or 'should')
  protected $mTestCaseOptionalPassCount;      // number of optional tests with 2 or more passes
  protected $mTestCaseInvalidCount;           // number of tests reported as invalid
  protected $mTestCaseNeededCount;            // number of required, valid tests that do not have 2 or more passes
  protected $mTestCaseNeedMoreResults;        // number of needed tests that might pass but need more results
  protected $mTestCaseTooManyFails;           // number of needed tests that have fails blocking exit criteria
  protected $mTestCaseNeededCountPerEngine;   // number of needed results per engine
    

  function __construct($testSuiteName, $testCaseName, $testGroupName,
                       $engine, $engineVersion, $platform, 
                       $grouping, $modified, $filter)
  {
    parent::__construct();
    
    $this->mTestSuiteName = $testSuiteName;
    $this->mDisplayFilter = $filter;

    $sql = "SELECT DISTINCT `engine` FROM `useragents` WHERE `engine` != '' ORDER BY `engine`";
    $r = $this->query($sql);
    while ($dbEngine = $r->fetchRow()) {
      $this->mEngines[] = $dbEngine['engine'];
      $this->mTestCaseNeededCountPerEngine[$dbEngine['engine']] = 0;
    }
    $this->mEngineCount = count($this->mEngines);
    
    $testSuiteName = $this->encode($testSuiteName, TESTCASES_MAX_TESTSUITE);

    $sql  = "SELECT DISTINCT `testcase`, `flags` ";
    $sql .= "FROM `testcases` ";
    $sql .= "WHERE `testsuite` LIKE '{$testSuiteName}' ";
    $sql .= "AND `active` = '1' ";
    if ($testCaseName) {
      $testCaseName = $this->encode($testCaseName, TESTCASES_MAX_TESTCASE);
      $sql .= "AND `testcase` LIKE '{$testCaseName}' ";
    }
    elseif ($testGroupName) {
      $testGroupName = $this->encode($testGroupName, TESTCASES_MAX_TESTGROUP);
      $sql .= "AND `testgroup` LIKE '{$testGroupName}' ";
    }
    $sql .= "ORDER BY `testcase` ";
    
    $r = $this->query($sql);
    if ($r->succeeded()) {
      $this->mTestCases = $r->fetchTable();
    }
    
    $sql  = "SELECT `testcases`.`testcase`, `useragents`.`engine`, `results`.`result` ";
    $sql .= "FROM `results` INNER JOIN (`testcases`, `useragents`) ";
    $sql .= "ON `results`.`testcase_id` = `testcases`.`id` ";
    $sql .= "AND `results`.`useragent_id` = `useragents`.`id` ";
    $sql .= "WHERE `testcases`.`testsuite` LIKE '{$testSuiteName}' ";
    $sql .= "AND `testcases`.`active` = '1' AND `results`.`ignore` = '0' ";
    $sql .= "AND `results`.`result` != 'na' ";
    if ($modified) {
      $modified = $this->encode($modified);
      $sql .= "AND `result`.`modified` <= '{$modified}' ";
    }  
    if ($engine) {
      $engine = $this->encode($engine, USERAGENTS_MAX_ENGINE);
      $sql .= "AND `useragents`.`engine` = '{$engine}' ";
      if ($engineVersion) {
        $engineVersion = $this->encode($engineVersion, USERAGENTS_MAX_ENGINE_VERSION);
        $sql .= "AND `useragents`.`engine_version` = '{$engineVersion}' ";
      }
      if ($platform) {
        $platform = $this->encode($platform, USERAGENTS_MAX_PLATFORM);
        $sql .= "AND `useragents`.`platform` = '{$platform}' ";
      }
    }

    $r = $this->query($sql);
    while ($resultData = $r->fetchRow()) {
      $engine   = $resultData['engine'];
      if ('' != $engine) {
        $testCase = $resultData['testcase'];
        $result   = $resultData['result'];
        $this->mResults[$testCase][$engine][] = $result;
      }
    }
  }
  
  function _generateRow($indent, $testcase, $engineResults, $optional, $spiderTrap)
  {
    $row  = "<td>";
    $row .= $spiderTrap->getTrapLink();
    $row .= "<a href='details?s={$this->mTestSuiteName}&c={$testcase}' target='details'>{$testcase}</a></td>";

    $testInvalid  = FALSE;
    $passCount    = 0;
    $failCount    = 0;
    foreach ($this->mEngines as $engine) {
      $engineMissing[$engine] = TRUE;
      if (array_key_exists($engine, $engineResults)) {
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
        $row .= "<a href='details?s={$this->mTestSuiteName}&c={$testcase}&e={$engine}' target='details'>";
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
          if ($failCount < ($this->mEngineCount - 1)) {
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
      echo $indent . "  <tr class='{$class}'>{$row}</tr>\n";
    }
  }

  /**
   * Write HTML for a table displaying result data
   * @param string $indent Indent before HTML output
   * @param SpiderTrap $spiderTrap Spider Trap to use for generating bait links
   */
  function write($indent, $spiderTrap)
  {
    if ($this->mTestCases && $this->mResults) {
      echo $indent . "<table>\n";
      echo $indent . "  <tr>\n";
      echo $indent . "    <th>Testcase</th>\n";
      foreach ($this->mEngines as $engine) {
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
  
      foreach ($this->mTestCases as $testCaseData) {
        $testCase   = $testCaseData['testcase'];
        $flags      = $testCaseData['flags'];

        $optional = (FALSE !== stripos($flags, 'may')) || (FALSE !== stripos($flags, 'should'));
        
        unset($engineResults);
        $engineResults[''] = 0;
        if (array_key_exists($testCase, $this->mResults)) {
          foreach ($this->mResults[$testCase] as $engine => $engineData) {
            $engineResults[$engine]['count'] = 0;
            foreach ($engineData as $result) {
              $engineResults[$engine]['count']++;
              if (array_key_exists($result, $engineResults[$engine])) {
                $engineResults[$engine][$result]++;
              }
              else {
                $engineResults[$engine][$result] = 1;
              }
            }
          }
        }

        $this->_generateRow($indent, $testCase, $engineResults, $optional, $spiderTrap);
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
}

?>