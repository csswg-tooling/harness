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
require_once("lib/Page.php");

/**
 * Import test case data from manifest file
 *
 * Safe to run multiple times to update existing tests
 */
class TestCaseImport extends CmdLineWorker
{  
  protected $mTestCaseActive;

  function __construct() 
  {
    parent::__construct();

    $this->mTestCaseActive = array();
  }

    
  function import($manifest, $testSuiteName, $baseURI, $extension, $skipFlag)
  {
    echo "Loading testcases from: {$testSuiteName}\n";
    $this->_loadTestCases($testSuiteName);
    
    $testSuiteName = $this->encode($testSuiteName, TESTCASES_MAX_TESTSUITE);
    
    echo "Reading source file: {$manifest}\n";
    $data = file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    echo "Storing test data\n";
    $count = 0;
    foreach ($data as $record) {
      if (0 == $count++) {
        if ("id\treferences\ttitle\tflags\tlinks\trevision\tcredits\tassertion" == $record) {
          continue;
        }
        die("ERROR: unknown format\n");
      }
      list ($testCase, $references, $title, $flagString, $links, $revision, $credits, $assertion) = explode("\t", $record);
      
      $active = 1;
      $flagArray = $this->_explodeTrimAndFilter(',', $flagString);
      if ((0 < count($flagArray)) && (FALSE !== array_search($skipFlag, $flagArray))) {
        $active = 0;
      }
      if ($references) {
        $flagArray[] = 'reftest';
      }
      $flagString = implode(',', $flagArray);
      
      $uri = $this->_combinePath($baseURI, $testCase, $extension);
      
      $testCaseId = $this->_getTestCaseId($testCase);
      
      $title      = Page::Decode($title);
      $assertion  = Page::Decode($assertion);
      $credits    = Page::Decode($credits);

      $testCase   = $this->encode($testCase, TESTCASES_MAX_TESTCASE);
      $title      = $this->encode($title, TESTCASES_MAX_TITLE);
      $assertion  = $this->encode($assertion, TESTCASES_MAX_ASSERTION);
      $credits    = $this->encode($credits, TESTCASES_MAX_CREDITS);
      $uri        = $this->encode($uri, TESTCASES_MAX_URI);
      $flagString = $this->encode($flagString);
      $revision   = intval($revision);

      if (0 < $testCaseId) {
        $sql  = "UPDATE `testcases` ";
        $sql .= "SET `uri` = '{$uri}', ";
        $sql .= "`revision` = '{$revision}', ";
        $sql .= "`title` = '{$title}', ";
        $sql .= "`flags` = '{$flagString}', ";
        $sql .= "`assertion` = '{$assertion}', ";
        $sql .= "`credits` = '{$credits}', ";
        $sql .= "`active` = '{$active}' ";
        $sql .= "WHERE `id` = '{$testCaseId}' ";
        
        $r = $this->query($sql);
        if (! $r->succeeded()) {
          die("Update failed {$testCase}:{$testCaseId}\n");
        }
      }
      else {
        $sql  = "INSERT INTO `testcases` (`uri`, `testsuite`, `testcase`, `revision`, `title`, `flags`, `assertion`, `credits`, `active`) ";
        $sql .= "VALUES ('{$uri}', '{$testSuiteName}', '{$testCase}', '{$revision}', '{$title}', '{$flagString}', '{$assertion}', '{$credits}', '{$active}');";
        
        $r = $this->query($sql);
        
        if ($r->succeeded()) {
          $testCaseId = $this->lastInsertId();
          echo "Inserted {$testCase}:{$testCaseId}\n";
        }
        else {
          die("Insert failed: {$testCase}\n");
        }
      }

      if (0 < $testCaseId) {
        $this->mTestCaseActive[$testCaseId] = TRUE;
        
        // verify flags
        $sql  = "SELECT `flags` ";
        $sql .= "FROM `testcases` ";
        $sql .= "WHERE `id` = '{$testCaseId}' ";
        
        $r = $this->query($sql);
        $storedFlags = $r->fetchField(0);
        
        $dbFlagArray = $this->_explodeTrimAndFilter(',', $storedFlags);

        $diff = array_diff($flagArray, $dbFlagArray);
        if (0 < count($diff)) {
          foreach ($diff as $flag) {
            echo "Flag not stored in database: '{$flag}'\n";
          }
          die("Need to update database schema to support flag(s).\n");
        }

        // update links
        $sql  = "DELETE FROM `testlinks` ";
        $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
        
        $this->query($sql);
      
        $linkArray = $this->_explodeTrimAndFilter(',', $links);
        foreach ($linkArray as $link) {
          $link = $this->encode($link, TESTLINKS_MAX_URI);
          
          $sql  = "INSERT INTO `testlinks` (`testcase_id`, `uri`) ";
          $sql .= "VALUES ('{$testCaseId}', '{$link}') ";

          $this->query($sql);
        }
      }
      else {
        exit("ERROR: insert failed\n");
      }
    }
    
    foreach ($this->mTestCaseIds as $testCase => $testCaseId) {
      if (! isset($this->mTestCaseActive[$testCaseId])) {
        $sql  = "UPDATE `testcases` ";
        $sql .= "SET `active` = '0', ";
        $sql .= "`modified` = `modified` ";
        $sql .= "WHERE `id` = '{$testCaseId}' ";
        
        $this->query($sql);
        echo "Deactivated {$testCase}:{$testCaseId}\n";
      }
    }
  }
}

$worker = new TestCaseImport();

$worker->import("/sites/test.csswg.org/suites/css2.1/nightly-unstable/testinfo.data", "CSS21_HTML", "html4/", "htm", "nonHTML");
$worker->import("/sites/test.csswg.org/suites/css2.1/nightly-unstable/testinfo.data", "CSS21_XHTML", "xhtml1/", "xht", "HTMLonly");

?>