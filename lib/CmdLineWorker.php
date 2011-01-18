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
 
define('COMMAND_LINE', TRUE);

require_once("lib/DBConnection.php");

/**
 * Common utility functions for command line worker classes
 */
class CmdLineWorker extends DBConnection
{  
  protected $mTestCaseIds;
  protected $mReferences;


  function __construct() 
  {
    parent::__construct();
    
  }


  protected function _loadTestCases($testSuiteName)
  {
    unset ($this->mTestCaseIds);
    $this->mTestCaseIds = array();
    
    $testSuiteName = $this->encode($testSuiteName, TESTCASES_MAX_TESTSUITE);
    
    $sql  = "SELECT `id`, `testcase` ";
    $sql .= "FROM `testcases` ";
    $sql .= "WHERE `testsuite` = '{$testSuiteName}' ";
    
    $r = $this->query($sql);
    while ($testCaseData = $r->fetchRow()) {
      $testCaseName = $testCaseData['testcase'];
      $testCaseId   = $testCaseData['id'];
      
      $this->mTestCaseIds[$testCaseName] = $testCaseId;
    }
  }
  

  protected function _getTestCaseId($testCaseName)
  {
    if (array_key_exists($testCaseName, $this->mTestCaseIds)) {
      return $this->mTestCaseIds[$testCaseName];
    }
    return FALSE;
  }


  protected function _loadReferences($testSuiteName)
  {
    unset($this->mReferences);
  
    $sql  = "SELECT `references`.`id`, `references`.`testcase_id`, ";
    $sql .= "`references`.`reference`, `references`.`uri`, `references`.`type` ";
    $sql .= "FROM `references` INNER JOIN `testcases` ";
    $sql .= "ON `references`.`testcase_id` = `testcases`.`id` ";
    $sql .= "WHERE `testcases`.`testsuite` = '{$testSuiteName}' ";
    
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
  

  protected function _getFileName($path)
  {
    $pathInfo = pathinfo($path);
    
    if (isset($pathInfo['filename'])) { // PHP 5.2+
      return $pathInfo['filename'];
    }
    return basename($pathInfo['basename'], '.' . $pathInfo['extension']);
  }
  
  
  protected function _combinePath($path, $fileName, $extension = '')
  {
    if ((0 < strlen($path)) && ('/' != substr($path, -1, 1))) {
      $path .= '/';
    }
    if ((0 < strlen($extension)) && ('.' != substr($extension, 0, 1))) {
      $extension = '.' . $extension;
    }
    return "{$path}{$fileName}{$extension}";
  }

  
  protected function _explodeAndTrim($delimiter, $string)
  {
    $result = array();
    
    $array = explode($delimiter, $string);
    foreach($array as $field) {
      $result[] = trim($field);
    }
    
    return $result;
  }


  protected function _explodeTrimAndFilter($delimiter, $string)
  {
    $result = array();
    
    $array = explode($delimiter, $string);
    foreach($array as $field) {
      $field = trim($field);
      if ($field) {
        $result[] = $field;
      }
    }
    
    return $result;
  }
}

?>