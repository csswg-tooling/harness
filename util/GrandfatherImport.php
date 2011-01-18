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
  
  function __construct() 
  {
    parent::__construct();
    
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
  
  
  // XXX does not handle changes in support files
  function grandfather($testSuiteName)
  {
    // check test cases for changes
    $this->_loadTestCases($testSuiteName);
    
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
        exit("ERROR: unknown testcase '{$testCaseName}'\n");
      }
    }

    // look for changed references
    $this->_loadReferences($testSuiteName);
    
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
        if (! $identical) {
          echo "reference '{$reference}' changed\n";
          $sql  = "UPDATE `testcases` ";
          $sql .= "SET `grandfather` = '0', `testgroup` = 'changed', `modified` = `modified` ";
          $sql .= "WHERE `id` = '{$testCaseId}'";
          
          $this->query($sql);
          
          // remove previously grandfathered results
          // normally not needed, but was once used to fixup a grandfather process where reference diffs were omitted
          $sql  = "UPDATE `results` ";
          $sql .= "SET `ignore` = '1', `modified` = `modified` ";
          $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
          $sql .= "AND `original_id` != '0' ";
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
$worker->grandfather("CSS21_HTML_RC5");
$worker->grandfather("CSS21_XHTML_RC5");
  
?>