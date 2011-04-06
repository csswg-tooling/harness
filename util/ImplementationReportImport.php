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
  protected $mTestCaseRevision;


  function __construct() 
  {
    parent::__construct();
    
  }
  
  
  protected function _addTestCase($testCaseName, $testCaseId, $testCaseData)
  {
    parent::_addTestCase($testCaseName, $testCaseId, $testCaseData);

    $revision = intval($testCaseData['revision']);
    
    $this->mTestCaseRevision[$testCaseId] = $revision;
  }


  protected function _loadTestCases($testSuiteName)
  {
    unset ($this->mTestCaseRevision);
    $this->mTestCaseRevision = array();
    
    return parent::_loadTestCases($testSuiteName);
  }


  function initFor($testSuiteName)
  {
    $this->_loadTestCases($testSuiteName);
  }


  function loadRevisionInfo($manifest)
  {
    echo "Reading source file: {$manifest}\n";
    $data = file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ((! $data) || (count($data) < 2)) {
      die("missing or empty manifest file\n");
    }
    
    $count = 0;
    foreach ($data as $record) {
      if (0 == $count++) {
        if ("id\treferences\ttitle\tflags\tlinks\trevision\tcredits\tassertion" == $record) {
          continue;
        }
        die("ERROR: unknown format\n");
      }
      list ($testCaseName, $references, $title, $flagString, $links, $revision, $credits, $assertion) = explode("\t", $record);
      
      $testCaseId = $this->_getTestCaseId($testCaseName);
      
      if ($testCaseId) {
        $this->mTestCaseRevision[$testCaseId] = $revision;
      }
      else {
        die("Unknown testcase: {$testCaseName}\n");
      }
    }
  }
  
  
  function import($reportFileName, $defaultFormat, $userAgentString, $source, $modified = null)
  {
    $userAgent = new UserAgent($userAgentString);
    $userAgent->update(); // force UA string into database and load Id
    $userAgentId = $userAgent->getId();

    $user = new User($source);
    $user->update();
    $sourceId = user->getId();

    $validResults = array('pass', 'fail', 'uncertain', 'na', 'invalid');
    $validFormats = array('html4', 'xhtml1');
    
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
        
          $format = pathinfo($testCasePath, PATHINFO_DIRNAME);
          if (! $format) {
            $format = $defaultFormat;
          }
          if (! in_array($format, $validFormats)) {
            die("Invalid format: '{$format}'\n");
          }
          
          $testCaseName = trim($this->_getFileName($testCasePath));
          $testCaseId = $this->_getTestCaseId($testCaseName);
          
          if (! $testCaseId) {
            die("Unknown test case: '{$testCaseName}'.\n");
          }
          
          $comment = trim($comment);
          
          $results[$testCaseId] = $result;
          $formats[$testCaseId] = $format;
          if (0 < strlen($comment)) {
            $comments[$testCaseId] = $this->encode($comment, RESULTS_MAX_COMMENT);  // encode now to test length
          }
        }
      }
    }

    // import results
    echo "Importing results\n";
    foreach ($results as $testCaseId => $result) {
          
      $revision = $this->mTestCaseRevision[$testCaseId];
      $format = $formats[$testCaseId];
      
      $sql  = "INSERT INTO `results` ";
      if (array_key_exists($testCaseId, $comments)) {
        $comment = $comments[$testCaseId];
        if ($modified) {
          $sql .= "(`testcase_id`, `revision`, `format`, ";
          $sql .= "`useragent_id`, `source_id`, `source_useragent_id`, ";
          $sql .= "`result`, `comment`, `modified`) ";
          $sql .= "VALUES ('{$testCaseId}', '{$revision}', '{$format}', ";
          $sql .= "'{$userAgentId}', '$sourceId', '{$userAgentId}', ";
          $sql .= "'{$result}', '{$comment}', '{$modified}')";  
        }
        else {
          $sql .= "(`testcase_id`, `revision`, `format`, ";
          $sql .= "`useragent_id`, `source_id`, `source_useragent_id`, ";
          $sql .= "`result`, `comment`) ";
          $sql .= "VALUES ('{$testCaseId}', '{$revision}', '{$format}', ";
          $sql .= "'{$userAgentId}', '$sourceId', '{$userAgentId}', ";
          $sql .= "'{$result}', '{$comment}')";  
        }
      }
      else {
        if ($modified) {
          $sql .= "(`testcase_id`, `revision`, `format`, ";
          $sql .= "`useragent_id`, `source_id`, `source_useragent_id`, ";
          $sql .= "`result`, `modified`) ";
          $sql .= "VALUES ('{$testCaseId}', '{$revision}', '{$format}', ";
          $sql .= "'{$userAgentId}', '$sourceId', '{$userAgentId}', ";
          $sql .= "'{$result}', '{$modified}')";  
        }
        else {
          $sql .= "(`testcase_id`, `revision`, `format`, ";
          $sql .= "`useragent_id`, `source_id`, `source_useragent_id`, ";
          $sql .= "`result`) ";
          $sql .= "VALUES ('{$testCaseId}', '{$revision}', '{$format}', ";
          $sql .= "'{$userAgentId}', '$sourceId', '{$userAgentId}', ";
          $sql .= "'{$result}')";  
        }
      }

      $r = $this->query($sql);
      if (! $r->succeeded()) {
        die("failed to store result [{$sql}]\n");
      }
    }
  }
}

$worker = new ImplementationReportImport();

$worker->initFor('CSS21');

$worker->loadRevisionInfo('testinfo.data');

$worker->import('implementation-report-WebToPDF.NETv1.0.3.6.data', 'html4',
                'Mozilla/5.0 (compatible; MSIE 8.0) TallComponents/1.0 WebToPDF/1.0.3.6 WebToPDF.NET/1.0.3.6', 
                'TallComponents', 
                '2011-02-21 04:23:00');

?>