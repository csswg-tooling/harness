<?php
/*******************************************************************************
 *
 *  Copyright © 2010-2011 Hewlett-Packard Development Company, L.P. 
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

require_once("lib/CmdLineWorker.php");

class ReftestImport extends CmdLineWorker
{  

  function __construct() 
  {
    parent::__construct();
    
  }
  
  
  function import($manifest, $testSuiteName, $baseURI)
  {
    $this->_loadTestCases($testSuiteName);
    $this->_loadReferences($testSuiteName);
    
    $data = file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($data as $record) {
      list ($type, $testCaseName, $referencePath) = explode(' ', $record);
      
      $testCaseName = $this->_getFileName($testCaseName);
      $uri = $this->_combinePath($baseURI, $referencePath);
      $referenceName = $this->_getFileName($referencePath);
      $testCaseId = $this->_getTestCaseId($testCaseName);
      
      $this->encode($referenceName, REFERENCES_MAX_REFERENCE);
      $this->encode($uri, REFERENCES_MAX_URI);
      if (('==' != $type) && ('!=' != $type)) {
        die("ERROR: bad type {$type}\n");
      }
      
      if ($testCaseId) {
        $testCaseHasReference[$testCaseId] = TRUE;
        
        $referenceId = $this->_getReferenceId($testCaseId, $referenceName);
        if ($referenceId) { // existing reference, update it
          $sql  = "UPDATE `references` ";
          $sql .= "SET `uri` = '{$uri}', `type` = '{$type}' ";
          $sql .= "WHERE `id` = '{$referenceId}' ";
          
          $this->query($sql);
          
          $usedReferenceIds[$referenceId] = TRUE;
        }
        else {  // new reference
          $sql  = "INSERT INTO `references` (`testcase_id`, `reference`, `uri`, `type`) ";
          $sql .= "VALUES ('{$testCaseId}', '{$referenceName}', '{$uri}', '{$type}');";
          
          $this->query($sql);
          
          $sql  = "UPDATE `testcases` ";
          $sql .= "SET `flags` = CONCAT(`flags`, ',reftest'), `modified` = `modified` ";
          $sql .= "WHERE `id` = {$testCaseId} ";
          
          $this->query($sql);
        }
      }
      else {
        echo "ERROR: unknown testcase '{$testCaseName}'\n";
      }
    }
    
    // find old references no longer used and delete them
    foreach ($this->mReferences as $referenceData) {
      $referenceId = $referenceData['id'];
      
      if (! isset($usedReferenceIds[$referenceId])) {
        $sql  = "DELETE FROM `references` ";
        $sql .= "WHERE `id` = '{$referenceId}' ";
        
        $this->query($sql);
      }
    }
    
    // clear reftest flags for tests that no longer have references
    foreach ($this->mTestCaseIds as $testCaseId) {
      if (! isset($testCaseHasReference[$testCaseId])) {
        $sql  = "UPDATE `testacses` ";
        $sql .= "SET `flags` = REPLACE(`flags`, 'reftest', ''), `modified` = `modified` ";
        $sql .= "WHERE `id` = {$testCaseId} ";
        
        $this->query($sql);
      }
    }
  }
}

$worker = new ReftestImport();

$worker->import("reftest_html.list", "CSS21_HTML_RC5", "html4/");
$worker->import("reftest_xhtml.list", "CSS21_XHTML_RC5", "xhtml1/");

?>