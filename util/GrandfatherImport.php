<?php
/*******************************************************************************
 *
 *  Copyright © 2010 Hewlett-Packard Development Company, L.P. 
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
  

class GrandfatherImport extends DBConnection
{  
  protected $mTestCases;
  protected $mReferences;
  protected $mDiffs;
  
  function __construct() 
  {
    parent::__construct();
    
  }
  
  protected function _loadTestCases($testSuite)
  {
    $sql  = "SELECT `id`, `testcase` FROM `testcases` ";
    $sql .= "WHERE `testsuite` = '{$testSuite}' ";
    
    $r = $this->query($sql);
    
    while ($testCase = $r->fetchRow()) {
      $this->mTestCases[$testCase['testcase']] = $testCase['id'];
    }
  }
  
  protected function _loadReferences($testSuite)
  {
    $sql  = "SELECT `references`.`testcase_id`, `references`.`reference` ";
    $sql .= "FROM `references` INNER JOIN `testcases` ";
    $sql .= "ON `references`.`testcase_id` = `testcases`.`id` ";
    $sql .= "WHERE `testcases`.`testsuite` = '{$testSuite}' ";
    
    $r = $this->query($sql);
    
    $this->mReferences = $r->fetchTable();
  }
  
  protected function _getFileName($path)
  {
    $pathInfo = pathinfo($path);
    
    if (isset($pathInfo['filename'])) { // PHP 5.2+
      return $pathInfo['filename'];
    }
    return basename($pathInfo['basename'], '.' . $pathInfo['extension']);
  }

  
  function loadDiffs($diffFile)
  {
    $data = file($diffFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Data is expected in standard diff -qr output, ie:      
//Files 20101027/html4/absolute-replaced-width-004.htm and 20101210/html4/absolute-replaced-width-004.htm differ
//Files 20101027/html4/absolute-replaced-width-006.htm and 20101210/html4/absolute-replaced-width-006.htm are identical
//Only in 20101027/html4/: absolute-replaced-width-007.htm
    
    foreach ($data as $record) {
      $fields = explode(' ', $record);
      if ("Files" == $fields[0]) {
        $testCase = $this->_getFileName($fields[1]);
        $this->mDiffs[$testCase] = ('identical' == end($fields));
      }
      else {
        if ("Only" == $fields[0]) {
          $testCase = $this->_getFileName(end($fields));
          $this->mDiffs[$testCase] = FALSE;
        }
        else {
          exit("ERROR: unknown diff file format\n");
        }
      }
    }
  }
  
  function grandfather($testSuite)
  {
/*  
    $this->_loadTestCases($testSuite);
    
    foreach ($this->mTestCases as $testCase => $testCaseId) {
      if (isset($this->mDiffs[$testCase])) {
        $identical = $this->mDiffs[$testCase];
      }
      else {
        $identical = FALSE;
        echo "No data for {$testSuite}:{$testCase}\n";
      }
      
      if (0 != $testCaseId) {
        if ($identical) {
          $sql  = "UPDATE `testcases` ";
          $sql .= "SET `grandfather` = '1', `modified` = `modified` ";
          $sql .= "WHERE `id` = '{$testCaseId}'";
        }
        else {
          $sql  = "UPDATE `testcases` ";
          $sql .= "SET `grandfather` = '0', `testgroup` = 'changed', `modified` = `modified` ";
          $sql .= "WHERE `id` = '{$testCaseId}'";
        }
        $this->query($sql);
      }
      else {
        exit("ERROR: unknown testcase '{$testCase}'\n");
      }
    }
*/

    // look for changed references
    $this->_loadReferences($testSuite);
    
    foreach ($this->mReferences as $referenceData) {
      $reference = $referenceData['reference'];
      $testCaseId = $referenceData['testcase_id'];
      
      if (isset($this->mDiffs[$reference])) {
        $identical = $this->mDiffs[$reference];
      }
      else {
        $identical = FALSE;
        echo "No data for {$testSuite}:{$reference}\n";
      }
      
      if (0 != $testCaseId) {
        if (! $identical) {
          echo "{$reference} changed\n";
          $sql  = "UPDATE `testcases` ";
          $sql .= "SET `grandfather` = '0', `testgroup` = 'changed', `modified` = `modified` ";
          $sql .= "WHERE `id` = '{$testCaseId}'";
          
          $this->query($sql);
          
          $sql  = "UPDATE `results` ";
          $sql .= "SET `ignore` = '1' ";
          $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
          $this->query($sql);
          $count = $this->affectedRowCount();
          if (0 < $count) {
            echo "{$count} results removed\n";
          }
        }
      }
      else {
        exit("ERROR: unknown reference '{$reference}'\n");
      }
    }
  }
}

$worker = new GrandfatherImport();

$worker->loadDiffs("out.diff");
$worker->grandfather("CSS21_HTML_RC4");
$worker->grandfather("CSS21_XHTML_RC4");
  
?>