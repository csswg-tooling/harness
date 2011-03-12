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
  protected $mResults;
  protected $mResultCount;


  function __construct($testSuite, $testCaseName = null, $specLinkId = null,
                       $engine = null, $engineVersion = null, $platform = null, 
                       $modified = null)
  {
    parent::__construct();
    
    // load engine list
    $sql  = "SELECT DISTINCT `engine` ";
    $sql .= "FROM `useragents` ";
    $sql .= "WHERE `engine` != '' ";
    $sql .= "ORDER BY `engine`";
    
    $r = $this->query($sql);
    while ($dbEngine = $r->fetchRow()) {
      $this->mEngines[] = $dbEngine['engine'];
    }

    // load revision equivalencies
    $sql  = "SELECT `testcase_id`, `revision`, `equal_revision` ";
    $sql .= "FROM `revisions` ";
    $sql .= "WHERE `equal_revision` != 0 ";
    $sql .= "ORDER BY `date` ";
    
    $r = $this->query($sql);
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
      $this->mModified = $modified;
      $modified = $this->encode($modified);
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

    $this->mResultCount = 0;
    $r = $this->query($sql);

    while ($resultData = $r->fetchRow()) {
      $testCaseId = intval($resultData['testcase_id']);
      $revision   = intval($resultData['revision']);
      
      if (array_key_exists($revision, $testCaseRevisions[$testCaseId])) {
        $engine   = $resultData['engine'];
        if ('' != $engine) {
          $resultId = intval($resultData['id']);
          $result   = $resultData['result'];
          $this->mResults[$testCaseId][$engine][$resultId] = $result;
          $this->mResultCount++;
        }
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
   * @return array of result data keyed by engine
   */
  function getResultCountsFor($testCaseId)
  {
    if (array_key_exists($testCaseId, $this->mResults)) {
      $engineResults = array();
      foreach ($this->mResults[$testCaseId] as $engine => $engineData) {
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
      return $engineResults;
    }
    return FALSE;
  }
}

?>