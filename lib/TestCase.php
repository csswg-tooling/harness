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

require_once('core/DBConnection.php');
require_once('lib/Flags.php');
require_once('lib/StatusCache.php');


/**
 * Wrapper class for information about a particular test case 
 * when bound to a given test suite
 */
class TestCase extends DBConnection
{
  protected $mTestSuite;
  protected $mInfo;
  protected $mFlags;
  protected $mURIs;
  protected $mReferences;
  protected $mSpecURIs;


  static function GetTestCaseIdFor($testCaseName)
  {
    if ($testCaseName) {
      $db = new DBConnection();

      $testCaseName = $db->encode($testCaseName, 'testcases.testcase');
      
      $sql  = "SELECT `id` ";
      $sql .= "FROM `testcases` ";
      $sql .= "WHERE `testcase` = '{$testCaseName}' ";
      
      $r = $db->query($sql);
      $testCaseId = intval($r->fetchField(0, 'id'));
      
      if ($testCaseId) {
        return $testCaseId;
      }
    }
    return FALSE;
  }
  
  
  function __construct(TestSuite $testSuite = null, $testCaseId = 0)
  {
    parent::__construct();
    
    if ($testSuite && (0 < $testCaseId)) {
      $this->mTestSuite = $testSuite;
      $this->mInfo = $this->_selectCaseById($testCaseId);
    }

    if ($this->isValid()) {
      $this->mFlags = new Flags($this->mInfo['flags'], TRUE);
    }
  }
  
  function load(TestSuite $testSuite, $testCaseName, $sectionId,
                UserAgent $userAgent, $order, $index, $flag = null)
  {
    if ($index < 0) {
      $index = 0;
    }
    
    $this->mTestSuite = $testSuite;

    if ($testCaseName) {  // load specific test case
      $this->mInfo = $this->_selectCaseByName($testCaseName);
    }
    elseif ($sectionId) { // load test from spec section
      $this->mInfo = $this->_selectCaseFromSection($sectionId, $userAgent, $order, $index, $flag);
      
    }
    else { // load test from suite
      $this->mInfo = $this->_selectCaseFromSuite($userAgent, $order, $index, $flag);
    }

    if ($this->isValid()) {
      $this->mFlags = new Flags($this->mInfo['flags'], TRUE);
    }
  }
  
  
  protected function _loadReferences()
  {
    if ((null == $this->mReferences) && $this->isReferenceTest()) {
      $testCaseId = $this->getId();
      
      $sql  = "SELECT * ";
      $sql .= "FROM `references` ";
      $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
      
      $formatNames = $this->mTestSuite->getFormatNames();
      
      $r = $this->query($sql);
      while ($referenceData = $r->fetchRow()) {
        $formatName = $referenceData['format'];
        if (Format::FormatNameInArray($formatName, $formatNames)) {
          $this->mReferences[] = $referenceData;
        }
      }
    }
    return (null != $this->mReferences);
  }
  
  
  protected function _loadURIs()
  {
    if (null == $this->mURIs) {
      $testCaseId = $this->getId();
      
      $sql  = "SELECT `format`, `uri` ";
      $sql .= "FROM `testpages` ";
      $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
      
      $formatNames = $this->mTestSuite->getFormatNames();

      $r = $this->query($sql);
      while ($uriData = $r->fetchRow()) {
        $formatName = $uriData['format'];
        
        if (Format::FormatNameInArray($formatName, $formatNames)) {
          $uri = $uriData['uri'];
        
          $this->mURIs[strtolower($formatName)] = $uri;
        }
      }
    }
    return (null != $this->mURIs);
  }
  
  
  protected function _loadSpecURIs()
  {
    if (null == $this->mSpecURIs) {
      $testCaseId = $this->getId();
      
      $specName = $this->encode($this->mTestSuite->getSpecName(), 'speclinks.spec');

      $sql  = "SELECT `speclinks`.`spec`, `speclinks`.`title`, `speclinks`.`section`, `speclinks`.`uri`, ";
      $sql .= "`specifications`.`base_uri` AS `spec_uri`, ";
      $sql .= "`specifications`.`title` AS `spec_title` ";
      $sql .= "FROM `speclinks` ";
      $sql .= "LEFT JOIN (`testlinks`, `specifications`) ";
      $sql .= "ON `speclinks`.`id` = `testlinks`.`speclink_id` ";
      $sql .= "AND `speclinks`.`spec` = `specifications`.`spec` ";
      $sql .= "WHERE `testlinks`.`testcase_id` = '{$testCaseId}' ";
      $sql .= "AND `speclinks`.`spec` = '{$specName}' ";
      $sql .= "AND `testlinks`.`group` = 0 ";
      $sql .= "ORDER BY `testlinks`.`sequence` ";
      
      $r = $this->query($sql);
      while ($specLink = $r->fetchRow()) {
        $spec       = $specLink['spec'];
        $specTitle  = $specLink['spec_title'];
        $title      = $specLink['title'];
        $section    = $specLink['section'];
        $uri        = $this->_combinePath($specLink['spec_uri'], $specLink['uri']);
        
        $this->mSpecURIs[] = compact('spec', 'specTitle', 'title', 'section', 'uri');
      }
    }
  }


  function getSpecSectionIds()
  {
    $sectionIds = array();
    
    $testCaseId = $this->getId();
    $specName = $this->encode($this->mTestSuite->getSpecName(), 'speclinks.spec');

    $sql  = "SELECT `speclinks`.`id` ";
    $sql .= "FROM `speclinks` ";
    $sql .= "LEFT JOIN (`testlinks`, `specifications`) ";
    $sql .= "ON `speclinks`.`id` = `testlinks`.`speclink_id` ";
    $sql .= "AND `speclinks`.`spec` = `specifications`.`spec` ";
    $sql .= "WHERE `testlinks`.`testcase_id` = '{$testCaseId}' ";
    $sql .= "AND `speclinks`.`spec` = '{$specName}' ";
    
    $r = $this->query($sql);
    while ($specLinkData = $r->fetchRow()) {
      $sectionIds[] = intval($specLinkData['id']);
    }
    return $sectionIds;
  }


  /**
   * Count number of test cases in suite
   */
  function countCasesInSuite($flag = null)
  {
    $testSuiteName = $this->encode($this->mTestSuite->getName(), 'suitetests.testsuite');
    
    $sql  = "SELECT COUNT(*) AS `count` ";
    $sql .= "FROM `suitetests` ";
    if ($flag) {
      $sql .= "LEFT JOIN `testcases` ";
      $sql .= "ON `suitetests`.`testcase_id` = `testcases`.`id` ";
    }
    $sql .= "WHERE `testsuite` = '{$testSuiteName}' ";
    if ($flag) {
      if ('!' === $flag[0]) {
        $flag = substr($flag, 1);
        $compare = 'NOT LIKE';
      }
      else {
        $compare = 'LIKE';
      }
      $flag = $this->encode($flag, 'testcases.flags');
      $sql .= "AND `testcases`.`flags` {$compare} '%,{$flag},%' ";
    }
    $sql .= "LIMIT 1";
    
    $r = $this->query($sql);
    
    $count = $r->fetchField(0, 'count');
    
    if (FALSE === $count) {
      $msg = 'Unable to access information about test cases.';
      trigger_error($msg, E_USER_WARNING);
    }

    return $count;
  }


  protected function _getSequenceEngine(UserAgent $userAgent)
  {
    $testSuiteName = $this->encode($this->mTestSuite->getName(), 'testsequence.testsuite');
    $engineName = $this->encode($userAgent->getEngineName(), 'testsequence.engine');
    
    // check if engine is sequenced
    $sql  = "SELECT * FROM `testsequence` ";
    $sql .= "WHERE `engine` = '{$engineName}' ";
    $sql .= "AND `testsuite` = '{$testSuiteName}' ";
    $sql .= "LIMIT 0, 1";
    $r = $this->query($sql);

    if (0 == $r->rowCount()) {  // try magic engine name
      $engineName = $this->encode('-no-data-', 'testsequence.engine');
      
      $sql  = "SELECT * FROM `testsequence` ";
      $sql .= "WHERE `engine` = '{$engineName}' ";
      $sql .= "AND `testsuite` = '{$testSuiteName}' ";
      $sql .= "LIMIT 0, 1";
      $r = $this->query($sql);

      if (0 == $r->rowCount()) {
        return FALSE;
      }
    }
    return $engineName;
  }
  
  /**
   * Load data about a test case based on index within suite
   */
  protected function _selectCaseFromSuite(UserAgent $userAgent, $order, $index, $flag = null)
  {
    $testSuiteName = $this->encode($this->mTestSuite->getName(), 'suitetests.testsuite');
    $engineName = FALSE;
    if (1 == $order) {
      $engineName = $this->_getSequenceEngine($userAgent);
    }
    $index = intval($index);
    
    // Select case ordered by sequence table
    $sql  = "SELECT `testcases`.`id`, `testcases`.`testcase`, ";
    $sql .= "`testcases`.`title`, `testcases`.`assertion`, ";
    $sql .= "`testcases`.`flags`, `testcases`.`credits`, ";
    $sql .= "`suitetests`.`revision` ";
    $sql .= "FROM (`testcases` ";
    $sql .= "LEFT JOIN `suitetests` ";
    $sql .= "ON `testcases`.`id` = `suitetests`.`testcase_id` ";
    if ($engineName) {
      $sql .= "LEFT JOIN `testsequence` ON `testcases`.`id` = `testsequence`.`testcase_id` ";
    }
    $sql .= ") ";
    $sql .= "WHERE (`suitetests`.`testsuite` = '{$testSuiteName}' ";
    if ($flag) {
      if ('!' === $flag[0]) {
        $flag = substr($flag, 1);
        $compare = 'NOT LIKE';
      }
      else {
        $compare = 'LIKE';
      }
      $flag = $this->encode($flag, 'testcases.flags');
      $sql .= "AND `testcases`.`flags` {$compare} '%,{$flag},%' ";
    }
    if ($engineName) {
      $sql .= "AND `testsequence`.`engine` = '{$engineName}' ";
      $sql .= "AND `testsequence`.`testsuite` = '{$testSuiteName}' ";
    }
    $sql .= ") GROUP BY `id` ";
    if ($engineName) {
      $sql .= "ORDER BY `testsequence`.`sequence`, `testcases`.`testcase` ";
    }
    else {
      $sql .= "ORDER BY `testcases`.`testcase` ";
    }
    $sql .= "LIMIT {$index}, 1";

    $r = $this->query($sql);

    if (! $r->succeeded()) {
      $msg = 'Unable to access information about test cases.';
      trigger_error($msg, E_USER_ERROR);
    }

    $data = $r->fetchRow();
    
    return $data;
  }


  /**
   * Count number of test cases in a particular section
   */
  function countCasesInSection($sectionId, $flag = null)
  {
    $testSuiteName = $this->encode($this->mTestSuite->getName(), 'suitetests.testsuite');
    $sectionId = intval($sectionId);
    
    $sql  = "SELECT COUNT(*) AS `count` ";
    $sql .= "FROM `suitetests` ";
    $sql .= "LEFT JOIN (`testlinks`, `testcases`) ";
    $sql .= "ON `suitetests`.`testcase_id` = `testlinks`.`testcase_id` ";
    $sql .= "AND `suitetests`.`testcase_id` = `testcases`.`id` ";
    $sql .= "WHERE `suitetests`.`testsuite` = '{$testSuiteName}' ";
    $sql .= "AND `testlinks`.`speclink_id` = '{$sectionId}' ";
    if ($flag) {
      if ('!' === $flag[0]) {
        $flag = substr($flag, 1);
        $compare = 'NOT LIKE';
      }
      else {
        $compare = 'LIKE';
      }
      $flag = $this->encode($flag, 'testcases.flags');
      $sql .= "AND `testcases`.`flags` {$compare} '%,{$flag},%' ";
    }
    $sql .= "LIMIT 1";

    $r = $this->query($sql);
    
    $count = $r->fetchField(0, 'count');
    
    if (FALSE === $count) {
      $msg = 'Unable to access information about test cases.';
      trigger_error($msg, E_USER_ERROR);
    }

    return $count;
  }

  /**
   * Load data about test case from section by index
   */
  protected function _selectCaseFromSection($sectionId, UserAgent $userAgent, $order, $index, $flag = null)
  {
    $testSuiteName = $this->encode($this->mTestSuite->getName(), 'suitetests.testsuite');
    $engineName = FALSE;
    if (1 == $order) {
      $engineName = $this->_getSequenceEngine($userAgent);
    }
    $sectionId = intval($sectionId);
    $index = intval($index);
    
    // Select case ordered by sequence table
    $sql  = "SELECT `testcases`.`id`, `testcases`.`testcase`, ";
    $sql .= "`testcases`.`title`, `testcases`.`assertion`, ";
    $sql .= "`testcases`.`flags`, `testcases`.`credits`, ";
    $sql .= "`suitetests`.`revision` ";
    $sql .= "FROM (`testcases` ";
    $sql .= "LEFT JOIN (`suitetests`, `testlinks`) ";
    $sql .= "ON `testcases`.`id` = `suitetests`.`testcase_id` ";
    $sql .= "AND `testcases`.`id` = `testlinks`.`testcase_id` ";
    if ($engineName) {
      $sql .= "LEFT JOIN `testsequence` ON `testcases`.`id` = `testsequence`.`testcase_id` ";
    }
    $sql .= ") ";
    $sql .= "WHERE (`suitetests`.`testsuite` = '{$testSuiteName}' ";
    if ($flag) {
      if ('!' === $flag[0]) {
        $flag = substr($flag, 1);
        $compare = 'NOT LIKE';
      }
      else {
        $compare = 'LIKE';
      }
      $flag = $this->encode($flag, 'testcases.flags');
      $sql .= "AND `testcases`.`flags` {$compare} '%,{$flag},%' ";
    }
    if ($engineName) {
      $sql .= "AND `testsequence`.`engine` = '{$engineName}' ";
      $sql .= "AND `testsequence`.`testsuite` = '{$testSuiteName}' ";
    }
    $sql .= "AND `testlinks`.`speclink_id` = '{$sectionId}' ";
    $sql .= ") GROUP BY `id` ";
    if ($engineName) {
      $sql .= "ORDER BY `testsequence`.`sequence`, `testcases`.`testcase` ";
    }
    else {
      $sql .= "ORDER BY `testcases`.`testcase` ";
    }
    $sql .= "LIMIT {$index}, 1";

    $r = $this->query($sql);

    if (! $r->succeeded()) {
      $msg = 'Unable to access information about test cases.';
      trigger_error($msg, E_USER_ERROR);
    }

    $data = $r->fetchRow();
    
    return $data;
  }


  /**
   * Load data about a test case by id
   */
  protected function _selectCaseById($testCaseId)
  {
    $testSuiteName = $this->encode($this->mTestSuite->getName(), 'suitetests.testsuite');
    $testCaseId = intval($testCaseId);
    
    $sql  = "SELECT `testcases`.`id`, `testcases`.`testcase`, ";
    $sql .= "`testcases`.`title`, `testcases`.`assertion`, ";
    $sql .= "`testcases`.`flags`, `testcases`.`credits`, ";
    $sql .= "`suitetests`.`revision` ";
    $sql .= "FROM (`testcases` ";
    $sql .= "LEFT JOIN `suitetests` ";
    $sql .= "ON `testcases`.`id` = `suitetests`.`testcase_id`";
    $sql .= ") ";
    $sql .= "WHERE `testcases`.`id` = '{$testCaseId}' ";
    $sql .= "AND `suitetests`.`testsuite` = '{$testSuiteName}' ";
    $sql .= "LIMIT 1";

    $r = $this->query($sql);

    if (! $r->succeeded()) {
      $msg = 'Unable to access information about test cases.';
      trigger_error($msg, E_USER_ERROR);
    }

    $data = $r->fetchRow();
    
    return $data;
  }


  /**
   * Load data about a test case by name
   */
  protected function _selectCaseByName($testCaseName)
  {
    $testSuiteName = $this->encode($this->mTestSuite->getName(), 'suitetests.testsuite');
    $testCaseName = $this->encode($testCaseName, 'testcases.testcase');

    $sql  = "SELECT `testcases`.`id`, `testcases`.`testcase`, ";
    $sql .= "`testcases`.`title`, `testcases`.`assertion`, ";
    $sql .= "`testcases`.`flags`, `testcases`.`credits`, ";
    $sql .= "`suitetests`.`revision` ";
    $sql .= "FROM (`testcases` ";
    $sql .= "LEFT JOIN `suitetests` ";
    $sql .= "ON `testcases`.`id` = `suitetests`.`testcase_id` ";
    $sql .= ") ";
    $sql .= "WHERE `testcases`.`testcase` = '{$testCaseName}' ";
    $sql .= "AND `suitetests`.`testsuite` = '{$testSuiteName}' ";
    $sql .= "LIMIT 1";

    $r = $this->query($sql);

    if (! $r->succeeded()) {
      $msg = 'Unable to access information about test cases.';
      trigger_error($msg, E_USER_ERROR);
    }

    $data = $r->fetchRow();
    
    return $data;
  }


  function getIndex($sectionId, UserAgent $userAgent, $order, $flag = null)
  {
    $testSuiteName = $this->encode($this->mTestSuite->getName(), 'testsequence.testsuite');
    $engineName = FALSE;
    if (1 == $order) {
      $engineName = $this->_getSequenceEngine($userAgent);
    }
    $sectionId = intval($sectionId);
    $testCaseName = $this->getTestCaseName();
    
    // Yuck, there really should be a way to query this from MySQL, but I don't know how...
    // so we query for all testcases in the group, and search
    $sql  = "SELECT `testcases`.`testcase` ";
    $sql .= "FROM (`testcases` ";
    $sql .= "LEFT JOIN `suitetests` ON `testcases`.`id` = `suitetests`.`testcase_id` ";
    if ($sectionId) {
      $sql .= "LEFT JOIN `testlinks`ON `testcases`.`id` = `testlinks`.`testcase_id` ";
    }
    if ($engineName) {
      $sql .= "LEFT JOIN `testsequence` ON `testcases`.`id` = `testsequence`.`testcase_id` ";
    }
    $sql .= ") ";
    $sql .= "WHERE (`suitetests`.`testsuite` = '{$testSuiteName}' ";
    if ($flag) {
      if ('!' === $flag[0]) {
        $flag = substr($flag, 1);
        $compare = 'NOT LIKE';
      }
      else {
        $compare = 'LIKE';
      }
      $flag = $this->encode($flag, 'testcases.flags');
      $sql .= "AND `testcases`.`flags` {$compare} '%,{$flag},%' ";
    }
    if ($sectionId) {
      $sql .= "AND `testlinks`.`speclink_id` = '{$sectionId}' ";
    }
    if ($engineName) {
      $sql .= "AND `testsequence`.`engine` = '{$engineName}' ";
      $sql .= "AND `testsequence`.`testsuite` = '{$testSuiteName}' ";
    }
    $sql .= ") GROUP BY `id` ";
    if ($engineName) {
      $sql .= "ORDER BY `testsequence`.`sequence`, `testcases`.`testcase` ";
    }
    else {
      $sql .= "ORDER BY `testcases`.`testcase` ";
    }
    $r = $this->query($sql);
    $index = 0;
    while ($data = $r->fetchRow()) {
      if ($data['testcase'] == $testCaseName) {
        return $index;
      }
      $index++;
    }
    return FALSE;
  }
  
  
  /**
   * Store test result
   */
  function submitResult(UserAgent $userAgent, User $user, Format $format, $result)
  {
    if ($this->isValid()) {
      $sql  = "INSERT INTO `results` ";
      $sql .= "(`testcase_id`, `revision`, `format`, `useragent_id`, `source_id`, `source_useragent_id`, `result`) ";
      $sql .= "VALUES (";
      $sql .= "'" . $this->getId() . "',";
      $sql .= "'" . $this->encode($this->getRevision(), 'results.revision') . "',";
      $sql .= "'" . $this->encode($format->getName(), 'results.format') . "', ";
      $sql .= "'" . $userAgent->getId() . "',";
      $sql .= "'" . $user->getId() . "',";
      $sql .= "'" . $userAgent->getActualUA()->getId() . "',";
      $sql .= "'" . $this->encode(strtolower($result)) . "'";
      $sql .= ")";
      
      $r = $this->query($sql);

      if (! $r->succeeded()) {
        $msg = 'Operation Failed. We were unable to record your submission.';
        trigger_error($msg, E_USER_ERROR);
      }
      
      StatusCache::FlushAllResults(); // need to flush cache for all sections in all test suites that include this test, faster to flush them all
    }
    return FALSE;
  }


  function isValid()
  {
    return ($this->mInfo && array_key_exists('id', $this->mInfo) && (0 < $this->mInfo['id']));
  }


  function getId()
  {
    if ($this->isValid()) {
      return intval($this->mInfo['id']);
    }
    return FALSE;
  }
  
  
  function getFormatNames()
  {
    if ($this->isValid()) {
      if ($this->_loadURIs()) {
        $suiteFormatNames = $this->mTestSuite->getFormatNames();

        $formatNames = array();
        foreach ($suiteFormatNames as $formatName) {
          if (array_key_exists(strtolower($formatName), $this->mURIs)) {
            $formatNames[] = $formatName; // take names from test suite since we normalize names in array key
          }
        }
        return $formatNames;
      }
    }
    return FALSE;
  }
  

  function getURI($formatName)
  {
    if ($this->isValid()) {
      $formatName = strtolower($formatName);
      if ($this->_loadURIs() && array_key_exists($formatName, $this->mURIs)) {
        return $this->_combinePath($this->mTestSuite->getBaseURI(), $this->mURIs[$formatName]);
      }
    }
    return FALSE;
  }
  

  function getTestCaseName()
  {
    if ($this->isValid()) {
      return $this->mInfo['testcase'];
    }
    return FALSE;
  }


  function getRevision()
  {
    if ($this->isValid()) {
      return $this->mInfo['revision'];
    }
    return FALSE;
  }


  function getTitle()
  {
    if ($this->isValid()) {
      return $this->mInfo['title'];
    }
    return FALSE;
  }


  function getAssertion()
  {
    if ($this->isValid()) {
      return $this->mInfo['assertion'];
    }
    return FALSE;
  }


  function isReferenceTest()
  {
    if ($this->isValid()) {
      return $this->mFlags->hasFlag('reftest');
    }
    return FALSE;
  }


  /**
   * Get Reference data
   *
   * @return FALSE|array
   */
  function getReferences($formatName)
  {
    if ($this->isValid()) {
      if ($this->_loadReferences()) {
        $references = array();
        foreach ($this->mReferences as $referenceData) {
          if (0 == strcasecmp($referenceData['format'], $formatName)) {
            $references[] = $referenceData;
          }
        }
        if (0 < count($references)) {
          return $references;
        }
      }
    }
    return FALSE;
  }
  

  function getReferenceURI($refName, $formatName)
  {
    if ($this->isValid() && ($refName) && ($formatName)) {
      if ($this->_loadReferences()) {
        foreach ($this->mReferences as $referenceData) {
          if ((0 == strcasecmp($referenceData['reference'], $refName)) && 
              (0 == strcasecmp($referenceData['format'], $formatName))) {
            return $this->_combinePath($this->mTestSuite->getBaseURI(), $referenceData['uri']);
          }
        }
      }
    }
    return FALSE;
  }


  /**
   * Get type of reference
   * @param int $refId
   * @return string ('==' or '!=')
   */
  function getReferenceType($refName, $formatName)
  {
    if ($this->isValid() && ($refName) && ($formatName)) {
      if ($this->_loadReferences()) {
        foreach ($this->mReferences as $referenceData) {
          if ((0 == strcasecmp($referenceData['reference'], $refName)) && 
              (0 == strcasecmp($referenceData['format'], $formatName))) {
            return $referenceData['type'];
          }
        }
      }
    }
    return FALSE;
  }
  
  
  function getFlags()
  {
    if ($this->isValid()) {
      return $this->mFlags;
    }
    return FALSE;
  }
  
  
  function hasFlag($flag)
  {
    if ($this->isValid()) {
      return $this->mFlags->hasFlag($flag);
    }
    return FALSE;
  }
  

  function getSpecURIs()
  {
    if ($this->isValid()) {
      $this->_loadSpecURIs();
      return $this->mSpecURIs;
    }
    return FALSE;
  }  
  
  
  function getCredits()
  {
    if ($this->isValid()) {
      return $this->mInfo['credits'];
    }
    return FALSE;
  }

}

?>