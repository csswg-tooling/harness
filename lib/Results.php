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
require_once('lib/TestCases.php');

require_once('modules/testsuite/TestSuite.php');

require_once('modules/useragent/Engine.php');
require_once('modules/useragent/UserAgentDB.php');


/**
 * Gather and report test results per engine, computing CR Exit Criteria
 *
 */
class Results extends HarnessDBConnection
{
  protected $mEngines;
  protected $mTestCases;
  protected $mComponentTestIds;
  protected $mResults;
  protected $mResultCount;


  function __construct(TestSuite $testSuite, TestCase $testCase = null,
                       Specification $spec = null, SpecificationAnchor $anchor = null,
                       DateTime $modified = null,
                       $engineName = null, $engineVersion = null, 
                       $browserName = null, $browserVersion = null, 
                       $platformName = null, $platformVersion = null)
  {
    parent::__construct();
    
    if ((! $modified) && $testSuite->getLockDateTime()) {
      $modified = $testSuite->getLockDateTime();
    }
    
    $this->mEngines = array();
    $this->mTestCases = array();
    $this->mComponentTests = array();
    $this->mResults = array();
    $this->mResultCount = 0;

    $testSuiteName = $this->encode($testSuite->getName(), 'suite_tests.test_suite');
    $searchTestCaseId = ($testCase ? $testCase->getId() : null);
    
    $userAgentDB = UserAgentDBConnection::GetDBName();

    $engines = Engine::GetAllEngines();

    // load revision equivalencies
    $sql  = "SELECT `testcase_id`, `revision`, `equal_revision` ";
    $sql .= "FROM `revisions` ";
    $sql .= "WHERE `equal_revision` != 0 ";
    $sql .= "ORDER BY `date` ";
    
    $r = $this->query($sql);
    $testCaseEqualRevisions = array();
    while ($revisionData = $r->fetchRow()) {
      $testCaseId     = intval($revisionData['testcase_id']);
      $revision       = $revisionData['revision'];
      $equalRevision  = $revisionData['equal_revision'];
      
      $testCaseEqualRevisions[$testCaseId][$revision] = $equalRevision;
    }
    
    if ($testCase) {
      $this->mTestCases[$testCase->getId()] = $testCase;
    }
    else {
      $testCases = new TestCases($testSuite, $spec, $anchor);
      $this->mTestCases = $testCases->getTestCases();
    }
    
    
    $currentComboId = 0;
    foreach ($this->mTestCases as $testCaseId => $testCase) {
      $revision = $testCase->getRevision();
      $testCaseRevisions[$testCaseId][$revision] = TRUE;

      // find equal revisions 
      if (array_key_exists($testCaseId, $testCaseEqualRevisions)) {
        $equalRevisions = $testCaseEqualRevisions[$testCaseId];

        while (array_key_exists($revision, $equalRevisions)) {
          $revision = $equalRevisions[$revision];
          $testCaseRevisions[$testCaseId][$revision] = TRUE;
        }
      }
      
      // look for combo/component relationship
      $flags = $testCase->getFlags();
      if ($flags->hasFlag('combo')) {
        $currentComboId = $testCaseId;
        $currentComboName = $testCase->getName();
      }
      else {
        if ($currentComboId) {
          $testCaseName = $testCase->getName();
          if (substr($testCaseName, 0, strlen($currentComboName)) == $currentComboName) {
            $this->mComponentTests[$currentComboId][] = $testCase;
          }
          else {
            $currentComboId = 0;
            $currentComboName = null;
          }
        }
      }
    }
    
    // load results
    $sql  = "SELECT DISTINCT `results`.`id`, `results`.`testcase_id`, ";
    $sql .= "  `results`.`revision`, `results`.`result`,  ";
    $sql .= "  `{$userAgentDB}`.`user_agents`.`engine` ";
    $sql .= "FROM `results` ";
    $sql .= "INNER JOIN (`{$userAgentDB}`.`user_agents`, `suite_tests`, `test_spec_links`) ";
    $sql .= "  ON `results`.`user_agent_id` = `{$userAgentDB}`.`user_agents`.`id` ";
    $sql .= "  AND `results`.`testcase_id` = `suite_tests`.`testcase_id` ";
    $sql .= "  AND `results`.`testcase_id` = `test_spec_links`.`testcase_id` ";
    $sql .= "WHERE `suite_tests`.`test_suite` = '{$testSuiteName}' ";
    $sql .= "  AND `results`.`ignore` = '0' ";
    $sql .= "  AND `results`.`result` != 'na' ";
    if ($searchTestCaseId) {
      $sql .= "  AND `results`.`testcase_id` = '{$searchTestCaseId}' ";
    }
    elseif ($spec) {
      $specName = $this->encode($spec->getName(), 'test_spec_links.spec');
      $sql .= "  AND `test_spec_links`.`spec` = '{$specName}' ";
      if ($anchor) {
        $parentName = $this->encode($anchor->getParentName(), 'test_spec_links.parent_name');
        $anchorName = $this->encode($anchor->getName(), 'test_spec_links.anchor_name');
        $sql .= "  AND `test_spec_links`.`parent_name` = '{$parentName}' ";
        $sql .= "  AND `test_spec_links`.`anchor_name` = '{$anchorName}' ";
      }
    }
    if ($modified) {
      $modified = $this->encodeDateTime($modified);
      $sql .= "  AND `results`.`modified` <= '{$modified}' ";
    }  
    if ($engineName) {
      $engineName = $this->encode($engineName, 'user_agents.engine');
      $sql .= "  AND `{$userAgentDB}`.`user_agents`.`engine` = '{$engineName}' ";
      if ($engineVersion) {
        $engineVersion = $this->encode($engineVersion, 'user_agents.engine_version');
        $sql .= "  AND `{$userAgentDB}`.`user_agents`.`engine_version` = '{$engineVersion}' ";
      }
    }
    if ($browserName) {
      $browserName = $this->encode($browserName, 'user_agents.browser');
      $sql .= "  AND `{$userAgentDB}`.`user_agents`.`browser` = '{$browserName}' ";
      if ($browserVersion) {
        $browserVersion = $this->encode($browserVersion, 'user_agents.browser_version');
        $sql .= "  AND `{$userAgentDB}`.`user_agents`.`browser_version` = '{$browserVersion}' ";
      }
    }
    if ($platformName) {
      $platformName = $this->encode($platformName, 'user_agents.platform');
      $sql .= "  AND `{$userAgentDB}`.`user_agents`.`platform` = '{$platformName}' ";
      if ($platformVersion) {
        $platformVersion = $this->encode($platformVersion, 'user_agents.platform_version');
        $sql .= "  AND `{$userAgentDB}`.`user_agents`.`platform_version` = '{$platformVersion}' ";
      }
    }
    $sql .= "ORDER BY `results`.`testcase_id` ";
    $r = $this->query($sql);

    $engineResults = array();
    while ($resultData = $r->fetchRow()) {
      $testCaseId = intval($resultData['testcase_id']);
      $revision   = $resultData['revision'];
      
      if (array_key_exists($revision, $testCaseRevisions[$testCaseId])) {
        $engineName = strtolower($resultData['engine']);
        if ('' != $engineName) {
          $engineResults[$engineName] = TRUE;
          $resultId = intval($resultData['id']);
          $result   = $resultData['result'];
          $this->mResults[$testCaseId][$engineName][$resultId] = $result;
          $this->mResultCount++;
        }
      }
    }
    
    foreach ($engines as $engineName => $engine) {
      if (array_key_exists($engineName, $engineResults)) {
        $this->mEngines[$engineName] = $engine;
      }
    }
  }
  
  
  function getEngineCount()
  {
    return count($this->mEngines);
  }
  
  function getEngines()
  {
    return $this->mEngines;
  }


  /**
   * Get test case data
   *
   * Data contains keys: id, testcase, flags, revision

   *
   * @return array keyed by test case id
   */
  function getTestCases()
  {
    return $this->mTestCases;
  }
  
  function getTestCaseCount()
  {
    return count($this->mTestCases);
  }
  
  
  /**
   * Get total result count
   *
   * @return int
   */
  function getResultCount()
  {
    return $this->mResultCount;
  }
  
  
  /**
   * Get result data per engine for test case
   *
   * @param int $testCaseId
   * @return array of result keyed by engine,resultId
   */
  function getResultsFor(TestCase $testCase)
  {
    $testCaseId = $testCase->getId();
    if (array_key_exists($testCaseId, $this->mResults)) {
      return $this->mResults[$testCaseId];
    }
    return FALSE;
  }
  
  
  /**
   * Get result counts per engine
   *
   * Count data contains keys: count, pass, fail, uncertain, invalid
   *
   * @param int $testCaseId
   * @return array of result counts keyed by engine,result
   */
  function getResultCountsFor(TestCase $testCase)
  {
    $testCaseId = $testCase->getId();
    if (array_key_exists($testCaseId, $this->mResults)) {
      $engineResults = array();
      foreach ($this->mEngines as $engineName => $engine) {
        $engineResults[$engineName]['count'] = 0;
        $engineResults[$engineName]['pass'] = 0;
        $engineResults[$engineName]['fail'] = 0;
        $engineResults[$engineName]['uncertain'] = 0;
        $engineResults[$engineName]['invalid'] = 0;
        
        if (array_key_exists($engineName, $this->mResults[$testCaseId])) {
          $engineData = $this->mResults[$testCaseId][$engineName];

          foreach ($engineData as $result) {
            $engineResults[$engineName]['count']++;
            $engineResults[$engineName][$result]++;
          }
        }
      }
      return $engineResults;
    }
    return FALSE;
  }
  
  
  /**
   * Get component tests for combo test
   * 
   * @param int test case id
   * @return array|FALSE array of component test ids
   */
  function getComponentTestsFor(TestCase $testCase)
  {
    $testCaseId = $testCase->getId();
    if (array_key_exists($testCaseId, $this->mComponentTests)) {
      return $this->mComponentTests[$testCaseId];
    }
    return FALSE;
  }
  

}

?>