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
    
    $results = array();
    $comments = array();
    
    // verify data set before import
    echo "Verifying data\n";
    $count = 0;
    foreach ($data as $record) {
      $record = trim($record);
      if (0 !== strpos($record, '#')) { // comment
        if (0 == $count++) {
          if ("testname\tresult" == substr($record, 0, 15)) {
            continue;
          }
        }
        
        list ($testCasePath, $result, $comment) = explode("\t", $record . "\t\t");
        
        if ($testCasePath && $result) {
          $result = strtolower(trim($result));
          if ('?' == $result) {
            $result = 'uncertain';
          }
          if ('skip' == substr($result, 0, 4)) {
            continue;
          }
          
          if (! in_array($result, $validResults)) {
            die("Invalid result: '{$result}'\n");
          }
        
          // XXX check for html/xhtml path - format support...
          $testCaseName = trim($this->_getFileName($testCasePath));
          $testCaseId = $this->_getTestCaseId($testCaseName);
          
          if (! $testCaseId) {
            die("Unknown test case: '{$testCaseName}'.\n");
          }
          
          $comment = trim($comment);
          
          $results[$testCaseId] = $result;
          if (0 < strlen($comment)) {
            $comments[$testCaseId] = $comment;
          }
        }
      }
    }

    // import results
    echo "Importing results\n";
    foreach ($results as $testCaseId => $result) {
          
      // XXX store comment if present (use comment for actual UA?)
      
      $sql  = "INSERT INTO `results` ";
      if ($modified) {
        $sql .= "(`testcase_id`, `useragent_id`, `source`, `result`, `modified`) ";
        $sql .= "VALUES ('{$testCaseId}', '{$userAgentId}', '$source', '{$result}', '{$modified}')";  
      }
      else {
        $sql .= "(`testcase_id`, `useragent_id`, `source`, `result`) ";
        $sql .= "VALUES ('{$testCaseId}', '{$userAgentId}', '$source', '{$result}')";  
      }

      $r = $this->query($sql);
      if (! $r->succeeded()) {
        die("failed to store result [{$sql}]\n");
      }
    }
  }
}

$worker = new ImplementationReportImport();

$worker->import("TallComponents.data", "CSS21_XHTML_RC5", "Mozilla/5.0 (compatible; MSIE 8.0) TallComponents/1.0", "TallComponents", "2011-01-20 05:28:00");

?>