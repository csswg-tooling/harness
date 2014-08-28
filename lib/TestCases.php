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
 
require_once('lib/HarnessDB.php');
require_once('lib/TestCase.php');

require_once('modules/specification/SpecificationDB.php');
require_once('modules/specification/Specification.php');
require_once('modules/specification/SpecificationAnchor.php');

/**
 * Encapsulate data about full ilst of test cases in a suite
 */
class TestCases extends HarnessDBConnection
{
  protected $mTestCasesById;
  protected $mTestCasesByName;
  protected $mQueryTime;


  function __construct(TestSuite $testSuite,
                       Specification $spec = null, SpecificationAnchor $anchor = null, $anchorGroups = TRUE,
                       $flag = null,
                       UserAgent $agentOrder = null)
  {
    parent::__construct();
    
    $startTime = microtime(TRUE);

    $testSuiteName = $this->encode($testSuite->getName(), 'test_spec_links.test_suite');
    $engineName = ($agentOrder ? $this->_getSequenceEngine($testSuite, $agentOrder) : FALSE);
    
    $this->mTestCasesById = array();
    $this->mTestCasesByName = array();
    
    $sql  = "SELECT `testcases`.* ";
    $sql .= "FROM `testcases` ";
    $sql .= "LEFT JOIN (`suite_tests`) ";
    $sql .= "  ON `testcases`.`id` = `suite_tests`.`testcase_id` ";
    $sql .= "  AND `testcases`.`revision` = `suite_tests`.`revision` ";
    if ($spec) {
      $specName = $this->encode($spec->getName(), 'test_spec_links.spec');
      $sql .= "LEFT JOIN (`test_spec_links`) ";
      $sql .= "  ON `testcases`.`id` = `test_spec_links`.`testcase_id` ";
    }
    if ($engineName) {
      $sql .= "LEFT JOIN (`test_sequence`) ";
      $sql .= "  ON `testcases`.`id` = `test_sequence`.`testcase_id` ";
    }
    $sql .= "WHERE `suite_tests`.`test_suite` = '{$testSuiteName}' ";
    if ($spec) {
      $sql .= "  AND `test_spec_links`.`spec` = '{$specName}' ";
      if ($anchor) {
        $parentName = $this->encode($anchor->getParentName(), 'test_spec_links.parent_name');
        $anchorName = $this->encode($anchor->getName(), 'test_spec_links.anchor_name');
        $sql .= "  AND `test_spec_links`.`parent_name` = '{$parentName}' ";
        $sql .= "  AND `test_spec_links`.`anchor_name` = '{$anchorName}' ";
        if (! $anchorGroups) {
          $sql .= "  AND `test_spec_links`.`type` = 'direct' ";
        }
      }
    }
    if ($engineName) {
      $sql .= "  AND `test_sequence`.`test_suite` = '{$testSuiteName}' ";
      $sql .= "  AND `test_sequence`.`engine` = '{$engineName}' ";
    }
    if ($flag) {
      if ('!' === $flag[0]) {
        $flag = substr($flag, 1);
        $compare = 'NOT LIKE';
      }
      else {
        $compare = 'LIKE';
      }
      $flag = $this->encode($flag, 'testcases.flags');
      $sql .= "  AND `testcases`.`flags` {$compare} '%,{$flag},%' ";
    }
    if ($engineName) {
      $sql .= "ORDER BY `test_sequence`.`sequence`, `testcases`.`testcase` ";
    }
    else {
      $sql .= "ORDER BY `testcases`.`testcase` ";
    }

    $r = $this->query($sql);

    while ($testCaseData = $r->fetchRow()) {
      $testCaseId = intval($testCaseData['id']);
      $testCaseName = $testCaseData['testcase'];
      $testCase = new TestCase($testSuite, $testCaseData);
      $this->mTestCasesById[$testCaseId] = $testCase;
      $this->mTestCasesByName[$testCaseName] = $testCase;
    }

    $this->mQueryTime = (microtime(TRUE) - $startTime);
  }


  protected function _getSequenceEngine(TestSuite $testSuite, UserAgent $userAgent)
  {
    $testSuiteName = $this->encode($testSuite->getName(), 'test_sequence.test_suite');
    $engineName = $this->encode($userAgent->getEngineName(), 'test_sequence.engine');
    
    // check if engine is sequenced
    $sql  = "SELECT * ";
    $sql .= "FROM `test_sequence` ";
    $sql .= "WHERE `engine` = '{$engineName}' ";
    $sql .= "  AND `test_suite` = '{$testSuiteName}' ";
    $sql .= "LIMIT 0, 1";
    $r = $this->query($sql);

    if (0 == $r->rowCount()) {  // try magic engine name
      $engineName = $this->encode('-no-data-', 'test_sequence.engine');
      
      $sql  = "SELECT * ";
      $sql .= "FROM `test_sequence` ";
      $sql .= "WHERE `engine` = '{$engineName}' ";
      $sql .= "  AND `test_suite` = '{$testSuiteName}' ";
      $sql .= "LIMIT 0, 1";
      $r = $this->query($sql);

      if (0 == $r->rowCount()) {
        return FALSE;
      }
    }
    return $engineName;
  }
  

  function getQueryTime()
  {
    return $this->mQueryTime;
  }


  function getCount()
  {
    if ($this->mTestCasesById) {
      return count($this->mTestCasesById);
    }
    return 0;
  }


  function getTestCases()
  {
    if ($this->mTestCasesById) {
      return $this->mTestCasesById;
    }
    return array();
  }
  
  function getFirstTestCase()
  {
    if ($this->mTestCasesById) {
      return reset($this->mTestCasesById);
    }
    return null;
  }
  
  function getTestCase($testCaseName)
  {
    if ($this->mTestCasesByName && array_key_exists($testCaseName, $this->mTestCasesByName)) {
      return $this->mTestCasesByName[$testCaseName];
    }
    return null;
  }
  
  function getTestCaseById($testCaseId)
  {
    if ($this->mTestCasesById && array_key_exists($testCaseId, $this->mTestCasesById)) {
      return $this->mTestCasesById[$testCaseId];
    }
    return null;
  }
  
  /**
   * Get index data for testacse in query
   * returns array of: index, count, first, prev, next, last
   *
   */
  function getIndexData(TestCase $testCase = null)
  {
    if ($this->mTestCasesById) {
      $count = count($this->mTestCasesById);
      $lastTestCase = end($this->mTestCasesById);
      $firstTestCase = reset($this->mTestCasesById);
      if ($testCase) {
        $testCaseName = $testCase->getName();
        $index = 0;
        $prevTestCase = null;
        while (FALSE !== ($testCase = current($this->mTestCasesById))) {
          if ($testCase->getName() == $testCaseName) {
            $nextTestCase = next($this->mTestCasesById);
            return array('index' => $index, 'count' => $count,
                         'first' => $firstTestCase, 'prev' => $prevTestCase,
                         'next' => $nextTestCase, 'last' => $lastTestCase);
          }
          $prevTestCase = $testCase;
          $index++;
          next($this->mTestCasesById);
        }
      }
      return array('index' => -1, 'count' => $count,
                   'first' => $firstTestCase, 'prev' => null,
                   'next' => $firstTestCase, 'last' => $lastTestCase);
    }
    return null;
  }

}

?>