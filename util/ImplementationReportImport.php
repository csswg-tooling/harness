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
 

require_once("lib/HarnessCmdLineWorker.php");
require_once("lib/UserAgent.php");
require_once("lib/User.php");
require_once("lib/TestSuite.php");

/**
 * Import results from implementation report files
 */
class ImplementationReportImport extends HarnessCmdLineWorker
{
  protected $mTestSuite;
  protected $mTestCaseRevision;
  protected $mTestCaseFlags;


  function __construct() 
  {
    parent::__construct();
    
  }
  
  
  protected function _addTestCase($testCaseName, $testCaseId, $testCaseData)
  {
    parent::_addTestCase($testCaseName, $testCaseId, $testCaseData);

    $revision = $testCaseData['revision'];
    $flagString = $testCaseData['flags'];
    
    $this->mTestCaseRevision[$testCaseId] = $revision;
    $this->mTestCaseFlags[$testCaseId] = $flagString;
  }


  protected function _loadTestCases($testSuiteName = '')
  {
    unset ($this->mTestCaseRevision);
    $this->mTestCaseRevision = array();
    
    return parent::_loadTestCases($testSuiteName);
  }


  function initFor($testSuiteName)
  {
    $this->mTestSuite = new TestSuite($testSuiteName);
    if (! $this->mTestSuite->isValid()) {
      die("Unknown test suite\n");
    }
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
  
  
  function loadRevisionsOnDate($date)
  {
    foreach ($this->mTestCaseIds as $testCaseName => $testCaseId) {
      $sql  = "SELECT `revision`, `date` ";
      $sql .= "FROM `revisions` ";
      $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
      $sql .= "ORDER BY `date` ";
      
      $r = $this->query($sql);
      $revision = 0;
      while ($revisionData = $r->fetchRow()) {
        $revisionDate = $revisionData['date'];
        if ($date < $revisionDate) {
          break;
        }
        $revision = $revisionData['revision'];
      }
      if (0 == $revision) {
        $this->mTestCaseIds[$testCaseName] = FALSE; // test did not exist on that date, forget it
        unset($this->mTestCaseRevision[$testCaseId]);
        unset($this->mTestCaseFlags[$testCaseId]);
      }
      else {
        $this->mTestCaseRevision[$testCaseId] = $revision;
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
    $sourceId = $user->getId();

    $validResults = array('pass', 'fail', 'uncertain', 'na', 'invalid');
    $validFormats = $this->mTestSuite->getFormatNames();
    
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
          if (0 == strcasecmp('skip', substr($result, 0, 4))) {
            continue;
          }
          
          if (! in_array($result, $validResults)) {
            die("Invalid result: '{$result}'\n");
          }
        
          $format = pathinfo($testCasePath, PATHINFO_DIRNAME);
          if ((! $format) || ('.' == $format)) {
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
          
          $results[$testCaseId][$format] = $result;
          if (0 < strlen($comment)) {
            $comments[$testCaseId][$format] = $this->encode($comment, 'results.comment');  // encode now to test length
          }
        }
      }
    }

    // import results
    echo "Importing results\n";
    foreach ($results as $testCaseId => $formatResults) {
      $revision = $this->encode($this->mTestCaseRevision[$testCaseId], 'results.revision');
      foreach ($formatResults as $format => $result) {
        $format = $this->encode($format, 'results.format');
        
        $sql  = "INSERT INTO `results` ";
        if (array_key_exists($testCaseId, $comments)) {
          $comment = $comments[$testCaseId];  // pre-encoded
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
}

$worker = new ImplementationReportImport();

$testSuiteName  = 'CSS21_RC6';
$reportFileName = 'IE9_20110323_ImplementationReport.txt';
$defaultFormat  = 'html4';
// XXX get from report file?
$uaString       = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0;)';
$source         = 'Microsoft';
$date           = '2011-03-23 12:00:00';

$worker->initFor('CSS21_RC6');

//$worker->loadRevisionInfo('testinfo.data');
$worker->loadRevisionsOnDate($date);

$worker->import($reportFileName, $defaultFormat, $uaString, $source, $date);

?>