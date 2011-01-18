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
 

require_once("lib/CmdLineWorker.php");
require_once("lib/UserAgent.php");

/**
 * Import results from implementation report files
 */
class ImplementationReportImport extends CmdLineWorker
{  


  function __construct() 
  {
    parent::__construct();
    
  }


  function import($reportFileName, $testSuiteName, $userAgentString, $source, $modified)
  {
    $userAgent = new UserAgent($userAgentString);
    $userAgent->update(); // force UA string into database and load Id
    $userAgentId = $userAgent->getId();
    
    $this->_loadTestCases($testSuiteName);
    $source = $this->encode($source, RESULTS_MAX_SOURCE);


    $validResults = array("pass", "fail", "uncertain", "na", "invalid");
    
    $data = file($reportFileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    // verify data set before import
    foreach ($data as $record) {
      $record = trim($record);
      if (0 !== strpos($record, '#')) { // comment
        list ($testCasePath, $result) = explode("\t", $record . "\t");
        
        if ($testCasePath && $result) {
          $result = strtolower(trim($result));
          
          if (! in_array($result, $validResults)) {
            die("Invalid result: '{$result}'\n");
          }
        
          $testCaseName = trim(pathinfo($testCasePath, PATHINFO_BASENAME));
          $testCaseId = $this->_getTestCaseId($testCaseName);
          
          if (! $testCaseId) {
            die("Unknown test case: '{$testCaseName}'.\n");
          }
        }
      }
    }

    // import results
    foreach ($data as $record) {
      $record = trim($record);
      if (0 !== strpos($record, '#')) { // comment
        list ($testCasePath, $result, $comment) = explode("\t", $record . "\t\t");
        
        if ($testCasePath && $result) {
          $result = strtolower(trim($result));
          $testCaseName = trim(pathinfo($testCasePath, PATHINFO_BASENAME));
          $testCaseId = $this->_getTestCaseId($testCaseName);
          
          // XXX store comment if present
          $sql  = "INSERT INTO `results` ";
          if ($modified) {
            $sql .= "(`testcase_id`, `useragent_id`, `source`, `result`, `modified`) ";
            $sql .= "VALUES ('{$testCaseId}', '{$userAgentId}', '$source', '{$result}', '{$modified}')";  
          }
          else {
            $sql .= "(`testcase_id`, `useragent_id`, `source`, `result`) ";
            $sql .= "VALUES ('{$testCaseId}', '{$userAgentId}', '$source', '{$result}')";  
          }
  
          $this->query($sql);
        }
      }
    }
  }
}

$worker = new ImplementationReportImport();

$worker->import("prince.data", "CSS21_HTML_RC5", "Prince/7.1 (http://www.princexml.com; Linux i686)", "ms2ger@gmail.com", "2011-01-15 12:01:00");

?>