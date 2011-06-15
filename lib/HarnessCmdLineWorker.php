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
 
require_once('core/CmdLineWorker.php');

/**
 * Common utility functions for harness command line worker classes
 */
class HarnessCmdLineWorker extends CmdLineWorker
{  
  protected $mTestCaseIds;
  protected $mReferences;


  function __construct() 
  {
    parent::__construct();
    
  }

  
  /**
   * Subclass hook to store additional test case data
   */
  protected function _addTestCase($testCaseName, $testCaseId, $testCaseData)
  {
    $this->mTestCaseIds[$testCaseName] = $testCaseId;
  }

  protected function _loadTestCases($testSuiteName = '')
  {
    unset ($this->mTestCaseIds);
    $this->mTestCaseIds = array();
    
    if ($testSuiteName) {
      $testSuiteName = $this->encode($testSuiteName, 'suitetests.testsuite');
      
      $sql  = "SELECT * ";
      $sql .= "FROM `testcases` ";
      $sql .= "LEFT JOIN `suitetests` ";
      $sql .= "ON `testcases`.`id` = `suitetests`.`testcase_id` ";
      $sql .= "WHERE `suitetests`.`testsuite` = '{$testSuiteName}' ";
      $sql .= "ORDER BY `testcase` ";
    }
    else {
      $sql  = "SELECT * ";
      $sql .= "FROM `testcases` ";
      $sql .= "ORDER BY `testcase` ";
    }
    
    $r = $this->query($sql);
    while ($testCaseData = $r->fetchRow()) {
      $testCaseName = $testCaseData['testcase'];
      $testCaseId   = intval($testCaseData['id']);
      
      $this->_addTestCase($testCaseName, $testCaseId, $testCaseData);
    }
  }
  

  protected function _getTestCaseId($testCaseName)
  {
    if (array_key_exists($testCaseName, $this->mTestCaseIds)) {
      return $this->mTestCaseIds[$testCaseName];
    }
    return FALSE;
  }


  protected function _loadReferences()
  {
    unset($this->mReferences);
  
    $sql  = "SELECT * ";
    $sql .= "FROM `references` ";
    
    $r = $this->query($sql);
    
    $this->mReferences = $r->fetchTable();
  }
  
  
  protected function _getReferencesFor($testCaseId)
  {
    $result = array();
    
    foreach($this->mReferences as $referenceData) {
      if ($referenceData['testcase_id'] == $testCaseId) {
        $result[] = $referenceData;
      }
    }
    
    return $result;
  }
  
  
  protected function _getReferenceId($testCaseId, $referenceName)
  {
    foreach($this->mReferences as $referenceData) {
      if (($referenceData['testcase_id'] == $testCaseId) && 
          ($referenceData['reference'] == $referenceName)) {
        return $referenceData['id'];
      }
    }
    return FALSE;
  }
}

?>