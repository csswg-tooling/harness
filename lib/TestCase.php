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

require_once("lib/DBConnection.php");
require_once("lib/Flags.php");


/**
 * Wrapper class for information about a particular test case 
 */
class TestCase extends DBConnection
{
  protected $mInfo;
  protected $mFlags;
  protected $mReferences;
  protected $mSpecURIs;


  function __construct($testCaseId = 0)
  {
    parent::__construct();
    
    if (0 < $testCaseId) {
      $this->mInfo = $this->_selectCaseById($testCaseId);
    }

    if ($this->isValid()) {
      $this->mFlags = new Flags($this->mInfo['flags']);
    }
  }
  
  function load($testSuiteName, $testCaseName, $testGroupName,
                $userAgent, $modified, $order, $index)
  {
    if ($index < 0) {
      $index = 0;
    }

    if ($testCaseName) {  // load specific test case
      $this->mInfo = $this->_selectCaseByName($testSuiteName, $testCaseName);
    }
    elseif ($testGroupName) { // load test from group
      $this->mInfo = $this->_selectCaseFromGroup($testSuiteName, $testGroupName,
                                                 $userAgent, $modified, $order, $index);
      
    }
    else { // load test from suite
      $this->mInfo = $this->_selectCaseFromSuite($testSuiteName, 
                                                 $userAgent, $modified, $order, $index);
    }

    if ($this->isValid()) {
      $this->mFlags = new Flags($this->mInfo['flags']);
    }
  }
  
  
  protected function _loadReferences()
  {
    if ((null == $this->mReferences) && $this->isReferenceTest()) {
      $testCaseId = $this->getId();
      
      $sql  = "SELECT `id`, `reference`, `uri`, `type` ";
      $sql .= "FROM `references` ";
      $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
      
      $r = $this->query($sql);
      if ($r->succeeded()) {
        $this->mReferences = $r->fetchTable();
      }
    }
    return (null != $this->mReferences);
  }
  
  
  protected function _loadSpecURIs()
  {
    if (null == $this->mSpecURIs) {
      $testCaseId = $this->getId();
      
      $sql  = "SELECT `title`, `uri` ";
      $sql .= "FROM `testlinks` ";
      $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
      
      $r = $this->query($sql);
      while ($specURI = $r->fetchRow()) {
        $uri = $this->mInfo['spec_uri'] . $specURI['uri'];    // XXX check for relative uri first
        $title = $specURI['title'];
        $this->mSpecURIs[] = compact('title', 'uri');
      }
    }
  }


  /**
   * Count number of test cases in suite
   */
  function countCasesInSuite($testSuiteName)
  {
    $sql  = "SELECT COUNT(*) AS `count` ";
    $sql .= "FROM `testcases` ";
    $sql .= "WHERE `testsuite` = '" . $this->encode($testSuiteName, TESTCASES_MAX_TESTSUITE) . "' ";
    $sql .= "AND `active` = '1' ";
    $sql .= "LIMIT 1";
    
    $r = $this->query($sql);
    
    $count = $r->fetchField(0, 'count');
    
    if (FALSE === $count) {
      $msg = 'Unable to access information about test cases.';
      trigger_error($msg, E_USER_WARNING);
    }

    return $count;
  }


  /**
   * Load data about a test case based on index within suite
   */
  protected function _selectCaseFromSuite($testSuiteName, $userAgent, $modified, $order, $index)
  {
    $engine = $userAgent->getEngine();
    $index = intval($index);
    
    if (1 == $order) {  // if engine isn't sequenced, use normal ordering
      $sql  = "SELECT * FROM `testsequence` INNER JOIN `testcases` ";
      $sql .= "ON `testsequence`.`testcase_id` = `testcases`.`id` ";
      $sql .= "WHERE `engine` = '" . $this->encode($engine, TESTSEQUENCE_MAX_ENGINE) . "' ";
      $sql .= "AND `testsuite` = '" . $this->encode($testSuiteName, TESTCASES_MAX_TESTSUITE) . "' ";
      $sql .= "LIMIT 0, 1";

      $r = $this->query($sql);
      if (0 == $r->rowCount()) {
        $order = 0;
      }
    }
    
    // Select case ordered by sequence table
    $sql  = "SELECT `testcases`.`id`, `testcases`.`uri`, `testcases`.`testsuite`, ";
    $sql .= "`testcases`.`testgroup`, `testcases`.`testcase`, ";
    $sql .= "`testcases`.`revision`, ";
    $sql .= "`testcases`.`title`, `testcases`.`assertion`, ";
    $sql .= "`testcases`.`flags`, `testcases`.`credits`, ";
    $sql .= "`testsuites`.`base_uri`, `testsuites`.`spec_uri`, ";
    $sql .= "`testsuites`.`locked` ";
    $sql .= "FROM (`testcases` LEFT JOIN `testsuites` ON `testcases`.`testsuite` = `testsuites`.`testsuite` ";
    if (1 == $order) {
      $sql .= "LEFT JOIN `testsequence` ON `testcases`.`id` = `testsequence`.`testcase_id` ";
    }
    $sql .= ") ";
    $sql .= "WHERE (`testcases`.`testsuite` = '" . $this->encode($testSuiteName, TESTCASES_MAX_TESTSUITE) . "' ";
    if (1 == $order) {
      $sql .= "AND `testsequence`.`engine` = '" . $this->encode($engine, TESTSEQUENCE_MAX_ENGINE) . "' ";
    }
    if ($modified) {
      $sql .= "AND `modified` <= '" . $this->encode($modified) . "' ";
    }
    $sql .= "AND `testcases`.`active` = '1' ";
    $sql .= ") GROUP BY `id` ";
    if (1 == $order) {
      $sql .= "ORDER BY `sequence`, `id` ";
    }
    else {
      $sql .= "ORDER BY `id` ";
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
   * Count number of test cases in a particular group
   */
  function countCasesInGroup($testSuiteName, $testGroupName)
  {
    $sql  = "SELECT COUNT(*) AS `count` ";
    $sql .= "FROM `testcases` ";
    $sql .= "WHERE `testcases`.`testsuite` = '" . $this->encode($testSuiteName, TESTCASES_MAX_TESTSUITE) . "' ";
    $sql .= "AND `testcases`.`testgroup` = '" . $this->encode($testGroupName, TESTCASES_MAX_TESTGROUP) . "' ";
    $sql .= "AND `testcases`.`active` = '1' ";
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
   * Load data about test case from group by index
   */
  protected function _selectCaseFromGroup($testSuiteName, $testGroupName,
                                          $userAgent, $modified, $order, $index)
  {
    $engine = $userAgent->getEngine();
    $index = intval($index);
    
    if (1 == $order) {  // if engine isn't sequenced, use normal ordering
      $sql  = "SELECT * FROM `testsequence` INNER JOIN `testcases` ";
      $sql .= "ON `testsequence`.`testcase_id` = `testcases`.`id` ";
      $sql .= "WHERE `engine` = '" . $this->encode($engine, TESTSEQUENCE_MAX_ENGINE) . "' ";
      $sql .= "AND `testsuite` = '" . $this->encode($testSuiteName, TESTCASES_MAX_TESTSUITE) . "' ";
      $sql .= "LIMIT 0, 1";

      $r = $this->query($sql);
      if (0 == $r->rowCount()) {
        $order = 0;
      }
    }

    // Select case ordered by sequence table
    $sql  = "SELECT `testcases`.`id`, `testcases`.`uri`, `testcases`.`testsuite`, ";
    $sql .= "`testcases`.`testgroup`, `testcases`.`testcase`, ";
    $sql .= "`testcases`.`revision`, ";
    $sql .= "`testcases`.`title`, `testcases`.`assertion`, ";
    $sql .= "`testcases`.`flags`, `testcases`.`credits`, ";
    $sql .= "`testsuites`.`base_uri`, `testsuites`.`spec_uri`, ";
    $sql .= "`testsuites`.`locked` ";
    $sql .= "FROM (`testcases` LEFT JOIN `testsuites` ";
    $sql .= "ON `testcases`.`testsuite` = `testsuites`.`testsuite` ";
    if (1 == $order) {
      $sql .= "LEFT JOIN `testsequence` ON `testcases`.`id` = `testsequence`.`testcase_id` ";
    }
    $sql .= ") ";
    $sql .= "WHERE (`testcases`.`testsuite` = '" . $this->encode($testSuiteName, TESTCASES_MAX_TESTSUITE) . "' ";
    if (1 == $order) {
      $sql .= "AND `testsequence`.`engine` = '" . $this->encode($engine, TESTSEQUENCE_MAX_ENGINE) . "' ";
    }
    $sql .= "AND `testcases`.`testgroup` = '" . $this->encode($testGroupName, TESTCASES_MAX_TESTGROUP) . "' ";
    if ($modified) {
      $sql .= "AND `modified` <= '" . $this->encode($modified) . "' ";
    }
    $sql .= "AND `testcases`.`active` = '1' ";
    $sql .= ") GROUP BY `id` ";
    if (1 == $order) {
      $sql .= "ORDER BY `sequence`, `id` ";
    }
    else {
      $sql .= "ORDER BY `id` ";
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
    $testCaseId = intval($testCaseId);
    
    $sql  = "SELECT `testcases`.`id`, `testcases`.`uri`, `testcases`.`testsuite`, ";
    $sql .= "`testcases`.`testgroup`, `testcases`.`testcase`, ";
    $sql .= "`testcases`.`revision`, ";
    $sql .= "`testcases`.`title`, `testcases`.`assertion`, ";
    $sql .= "`testcases`.`flags`, `testcases`.`credits`, ";
    $sql .= "`testsuites`.`base_uri`, `testsuites`.`spec_uri`, ";
    $sql .= "`testsuites`.`locked` ";
    $sql .= "FROM (`testcases` LEFT JOIN `testsuites` ";
    $sql .= "ON `testcases`.`testsuite` = `testsuites`.`testsuite`) ";
    $sql .= "WHERE `testcases`.`id` = '{$testCaseId}' ";
    $sql .= "AND `testcases`.`active` = '1' ";
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
  protected function _selectCaseByName($testSuiteName, $testCaseName)
  {
    $sql  = "SELECT `testcases`.`id`, `testcases`.`uri`, `testcases`.`testsuite`, ";
    $sql .= "`testcases`.`testgroup`, `testcases`.`testcase`, ";
    $sql .= "`testcases`.`revision`, ";
    $sql .= "`testcases`.`title`, `testcases`.`assertion`, ";
    $sql .= "`testcases`.`flags`, `testcases`.`credits`, ";
    $sql .= "`testsuites`.`base_uri`, `testsuites`.`spec_uri`, ";
    $sql .= "`testsuites`.`locked` ";
    $sql .= "FROM (`testcases` LEFT JOIN `testsuites` ";
    $sql .= "ON `testcases`.`testsuite` = `testsuites`.`testsuite`) ";
    $sql .= "WHERE `testcases`.`testsuite` = '" . $this->encode($testSuiteName, TESTCASES_MAX_TESTSUITE) . "' ";
    $sql .= "AND `testcases`.`testcase` = '" . $this->encode($testCaseName, TESTCASES_MAX_TESTCASE) . "' ";
    $sql .= "AND `testcases`.`active` = '1' ";
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
   * Store test result
   */
  function submitResult($userAgent, $source, $result)
  {
    if ($this->isValid() && (! $this->isLocked())) {
      $sql  = "INSERT INTO `results` ";
      $sql .= "(`testcase_id`, `revision`, `useragent_id`, `source`, `result`) ";
      $sql .= "VALUES (";
      $sql .= "'" . $this->getId() . "',";
      $sql .= "'" . $this->getRevision() . "',";
      $sql .= "'" . $userAgent->getId() . "',";
      $sql .= "'" . $this->encode($source, RESULTS_MAX_SOURCE) . "',";
      $sql .= "'" . $this->encode($result) . "'";
      $sql .= ")";
      
      $r = $this->query($sql);

      if (! $r->succeeded()) {
        $msg = 'Operation Failed. We were unable to record you submission.';
        trigger_error($msg, E_USER_ERROR);
      }
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
      return $this->mInfo['id'];
    }
    return FALSE;
  }
  
  
  function isLocked()
  {
    if ($this->isValid()) {
      return (0 != intval($this->mInfo['locked']));
    }
    return TRUE;
  }


  function getURI()
  {
    if ($this->isValid()) {
      return $this->mInfo['base_uri'] . $this->mInfo['uri'];
    }
    return FALSE;
  }

  function getBaseURI()
  {
    if ($this->isValid()) {
      return $this->mInfo['base_uri'];
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


  function getTestSuiteName()
  {
    if ($this->isValid()) {
      return $this->mInfo['testsuite'];
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
  function getReferences()
  {
    if ($this->isValid()) {
      if ($this->_loadReferences()) {
        return $this->mReferences;
      }
    }
    return FALSE;
  }
  

  function getReferenceURI($refId)
  {
    if ($this->isValid() && (0 < $refId)) {
      if ($this->_loadReferences()) {
        foreach ($this->mReferences as $reference) {
          if ($reference['id'] == $refId) {
            return $this->mInfo['base_uri'] . $reference['uri'];
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
  function getReferenceType($refId)
  {
    if ($this->isValid() && (0 < $refId)) {
      if ($this->_loadReferences()) {
        foreach ($this->mReferences as $reference) {
          if ($reference['id'] == $refId) {
            return $reference['type'];
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