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
require_once('lib/TestCase.php');

/**
 * Gather and report test results per engine, computing CR Exit Criteria
 *
 */
class Results extends DBConnection
{
  protected $mEngines;
  protected $mTestCases;
  protected $mComponentTestIds;
  protected $mResults;
  protected $mResultCount;


  function __construct(TestSuite $testSuite, $testCaseName = null, $specLinkId = null,
                       $engine = null, $engineVersion = null, $platform = null, 
                       DateTime $modified = null)
  {
    parent::__construct();
    
    if ((! $modified) && $testSuite->isLocked()) {
      $modified = $testSuite->getLockDateTime();
    }
    
    // load engine list
    $sql  = "SELECT DISTINCT `engine` ";
    $sql .= "FROM `useragents` ";
    $sql .= "WHERE `engine` != '' ";
    $sql .= "ORDER BY `engine`";
    
    $r = $this->query($sql);
    
    $engines = array();
    while ($dbEngine = $r->fetchRow()) {
      $engines[] = $dbEngine['engine'];
    }

    // load revision equivalencies
    $sql  = "SELECT `testcase_id`, `revision`, `equal_revision` ";
    $sql .= "FROM `revisions` ";
    $sql .= "WHERE `equal_revision` != 0 ";
    $sql .= "ORDER BY `date` ";
    
    $r = $this->query($sql);
    $testCaseEqualRevisions = array();
    while ($revisionData = $r->fetchRow()) {
      $testCaseId     = intval($revisionData['testcase_id']);
      $revision       = intval($revisionData['revision']);
      $equalRevision  = intval($revisionData['equal_revision']);
      
      $testCaseEqualRevisions[$testCaseId][$revision] = $equalRevision;
    }

    $testSuiteName = $this->encode($testSuite->getName(), SUITETESTS_MAX_TESTSUITE);
    $searchTestCaseId = TestCase::GetTestCaseIdFor($testCaseName);

    // load testcases
    $sql  = "SELECT DISTINCT `testcases`.`id`, `testcases`.`testcase`, ";
    $sql .= "`testcases`.`flags`, `suitetests`.`revision` ";
    $sql .= "FROM `testcases` ";
    $sql .= "LEFT JOIN (`suitetests`, `testlinks`) ";
    $sql .= "ON `testcases`.`id` = `suitetests`.`testcase_id` ";
    $sql .= "AND `testcases`.`id` = `testlinks`.`testcase_id` ";
    $sql .= "WHERE `suitetests`.`testsuite` = '{$testSuiteName}' ";
    if ($searchTestCaseId) {
      $sql .= "AND `testcases`.`id` = '{$searchTestCaseId}' ";
    }
    elseif ($specLinkId) {
      $sql .= "AND `speclink_id` = '{$specLinkId}' ";
    }
    $sql .= "ORDER BY `testcase` ";

    $r = $this->query($sql);
    
    $currentComboId = 0;
    $this->mTestCases = array();
    $this->mComponentTestIds = array();
    while ($testCaseData = $r->fetchRow()) {
      $testCaseId = intval($testCaseData['id']);
      $revision   = intval($testCaseData['revision']);
      
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
      $flags = new Flags($testCaseData['flags']);
      if ($flags->hasFlag('combo')) {
        $currentComboId = $testCaseId;
        $currentComboName = $testCaseData['testcase'];
      }
      else {
        if ($currentComboId) {
          $testCaseName = $testCaseData['testcase'];
          if (substr($testCaseName, 0, strlen($currentComboName)) == $currentComboName) {
            $this->mComponentTestIds[$currentComboId][] = $testCaseId;
          }
          else {
            $currentComboId = 0;
            $currentComboName = null;
          }
        }
      }
    
      $this->mTestCases[$testCaseId] = $testCaseData;
    }
    
    // load results
    $sql  = "SELECT DISTINCT `results`.`id`, `results`.`testcase_id`, ";
    $sql .= "`results`.`revision`, `results`.`result`,  ";
    $sql .= "`useragents`.`engine` ";
    $sql .= "FROM `results` INNER JOIN (`useragents`, `suitetests`, `testlinks`) ";
    $sql .= "ON `results`.`useragent_id` = `useragents`.`id` ";
    $sql .= "AND `results`.`testcase_id` = `suitetests`.`testcase_id` ";
    $sql .= "AND `results`.`testcase_id` = `testlinks`.`testcase_id` ";
    $sql .= "WHERE `suitetests`.`testsuite` = '{$testSuiteName}' ";
    $sql .= "AND `results`.`ignore` = '0' ";
    $sql .= "AND `results`.`result` != 'na' ";
    if ($searchTestCaseId) {
      $sql .= "AND `results`.`testcase_id` = '{$searchTestCaseId}' ";
    }
    elseif ($specLinkId) {
      $sql .= "AND `testlinks`.`speclink_id` = '{$specLinkId}' ";
    }
    if ($modified) {
      $modified->setTimeZone(new DateTimeZone(SERVER_TIME_ZONE));
      $modified = $this->encode($modified->format('Y-m-d H:i:s'));
      $sql .= "AND `results`.`modified` <= '{$modified}' ";
    }  
    if ($engine) {
      $engine = $this->encode($engine, USERAGENTS_MAX_ENGINE);
      $sql .= "AND `useragents`.`engine` = '{$engine}' ";
      if ($engineVersion) {
        $engineVersion = $this->encode($engineVersion, USERAGENTS_MAX_ENGINE_VERSION);
        $sql .= "AND `useragents`.`engine_version` = '{$engineVersion}' ";
      }
    }
    if ($platform) {
      $platform = $this->encode($platform, USERAGENTS_MAX_PLATFORM);
      $sql .= "AND `useragents`.`platform` = '{$platform}' ";
    }
    $sql .= "ORDER BY `results`.`testcase_id` ";

    $r = $this->query($sql);

    $engineResults = array();
    $this->mResultCount = 0;
    $this->mResults = array();
    while ($resultData = $r->fetchRow()) {
      $testCaseId = intval($resultData['testcase_id']);
      $revision   = intval($resultData['revision']);
      
      if (array_key_exists($revision, $testCaseRevisions[$testCaseId])) {
        $engine = $resultData['engine'];
        if ('' != $engine) {
          $engineResults[$engine] = TRUE;
          $resultId = intval($resultData['id']);
          $result   = $resultData['result'];
          $this->mResults[$testCaseId][$engine][$resultId] = $result;
          $this->mResultCount++;
        }
      }
    }
    
    $this->mEngines = array();
    foreach ($engines as $engine) {
      if (array_key_exists($engine, $engineResults)) {
        $this->mEngines[] = $engine;
      }
    }
  }
  
  
  function getEngineCount()
  {
    if ($this->mEngines) {
      return count($this->mEngines);
    }
    return 0;
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
  function getResultsFor($testCaseId)
  {
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
  function getResultCountsFor($testCaseId)
  {
    if (array_key_exists($testCaseId, $this->mResults)) {
      $engineResults = array();
      foreach ($this->mEngines as $engine) {
        $engineResults[$engine]['count'] = 0;
        $engineResults[$engine]['pass'] = 0;
        $engineResults[$engine]['fail'] = 0;
        $engineResults[$engine]['uncertain'] = 0;
        $engineResults[$engine]['invalid'] = 0;
        $engineResults[$engine]['na'] = 0;
        
        if (array_key_exists($engine, $this->mResults[$testCaseId])) {
          $engineData = $this->mResults[$testCaseId][$engine];

          foreach ($engineData as $result) {
            $engineResults[$engine]['count']++;
            $engineResults[$engine][$result]++;
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
  function getComponentTestsFor($testCaseId)
  {
    if (array_key_exists($testCaseId, $this->mComponentTestIds)) {
      return $this->mComponentTestIds[$testCaseId];
    }
    return FALSE;
  }
  

}

?>