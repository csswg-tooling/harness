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
  

class GrandfatherImport extends CmdLineWorker
{  
  protected $mDiffs;
  protected $mOldTestCaseRevision;
  protected $mNewTestCaseRevision;
  
  function __construct() 
  {
    parent::__construct();
    
  }
  

  protected function _addTestCase($testCaseName, $testCaseId, $testCaseData)
  {
    parent::_addTestCase($testCaseName, $testCaseId, $testCaseData);

    $revision = intval($testCaseData['revision']);
    
    $this->$mNewTestCaseRevision[$testCaseId] = $revision;
  }


  protected function _loadTestCases($testSuiteName)
  {
    unset ($this->$mNewTestCaseRevision);
    $this->$mNewTestCaseRevision = array();
    
    return parent::_loadTestCases($testSuiteName);
  }


  function initFor($testSuiteName)
  {
    $this->_loadTestCases($testSuiteName);
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
        $testCaseName = $this->_getFileName($fields[1]);
        $this->mDiffs[$testCaseName] = ('identical' == end($fields));
      }
      else {
        if ("Only" == $fields[0]) {
          $testCaseName = $this->_getFileName(end($fields));
          $this->mDiffs[$testCaseName] = FALSE;
        }
        else {
          exit("ERROR: unknown diff file format\n");
        }
      }
    }
  }


  function loadOldRevisionInfo($manifest)
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
        $this->mOldTestCaseRevision[$testCaseId] = $revision;
      }
      else {
        die("Unknown testcase: {$testCaseName}\n");
      }
    }
  }


  // XXX does not handle changes in support files
  function grandfather()
  {
    // check test cases for changes
    
    foreach ($this->mTestCaseIds as $testCaseName => $testCaseId) {
      if (isset($this->mDiffs[$testCaseName])) {
        $identical = $this->mDiffs[$testCaseName];
      }
      else {
        $identical = FALSE;
        echo "No diff data for {$testSuiteName}:{$testCaseName}\n";
      }
      
      if (0 != $testCaseId) {
        if ($identical) {
          XXX check for all references identical
        
          $oldRevision = XXX;
          $newRevision = XXX;
        
          $sql  = "UPDATE `revisions` ";
          $sql .= "SET `equal_revision` = '{$oldRevision}' ";
          $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
          $sql .= "AND `revision` = '{$newRevision}' ";
          $this->query($sql);
        }
      }
      else {
        exit("ERROR: unknown testcase '{$testCaseName}'\n");
      }
    }

    // look for changed references
    $this->_loadReferences();
    
    foreach ($this->mReferences as $referenceData) {
      $reference = $referenceData['reference'];
      $testCaseId = $referenceData['testcase_id'];
      
      if (isset($this->mDiffs[$reference])) {
        $identical = $this->mDiffs[$reference];
      }
      else {
        $identical = FALSE;
        echo "No diff data for reference {$testSuiteName}:{$reference}\n";
      }
      // XXX this handles added references, but does not detect removed references
      
      if (0 != $testCaseId) {
      }
      else {
        exit("ERROR: unknown reference '{$reference}'\n");
      }
    }
  }
}

$worker = new GrandfatherImport();

$worker->initFor("CSS21_DEV");

$worker->loadDiffs("out.diff");
$worker->loadOldRevisionInfo("testinfo.data");

$worker->grandfather();
  
?>