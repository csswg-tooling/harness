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

define('COMMAND_LINE', TRUE);

require_once("lib/DBConnection.php");

/**
 * Import test case data from manifest file
 *
 * Safe to run multiple times to update existing tests
 */
class TestCaseImport extends DBConnection
{  
  protected $mTestCaseIds;

  function __construct() 
  {
    parent::__construct();

  }

  protected function _loadTestCases($testSuite)
  {
    $testSuite = $this->encode($testSuite, TESTCASES_MAX_TESTSUITE);
    
    $sql  = "SELECT `id`, `testcase` ";
    $sql .= "FROM `testcases` ";
    $sql .= "WHERE `testsuite` = '{$testSuite}' ";
    
    $r = $this->query($sql);
    while ($testCase = $r->fetchRow()) {
      $this->mTestCaseIds[$testCase['testcase']] = $testCase['id'];
    }
  }
    
  // XXX add verification that flag values are legal in the DB
  // XXX add code to detect deleted test cases and deactivate them
  
  function import($manifest, $testSuite, $baseURI, $extension, $skipFlag, $modified)
  {
    $this->_loadTestCases($testSuite);

    $testSuite = $this->encode($testSuite, TESTCASES_MAX_TESTSUITE);
    
    $data = file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    $count = 0;
    foreach ($data as $record) {
      if (0 == $count++) {
        if ("id\treferences\ttitle\tflags\tlinks\tassertion" == $record) {
          continue;
        }
        die("ERROR: unknown format\n");
      }
      list ($testCase, $references, $title, $flags, $links, $assertion) = explode("\t", $record);
      
      $active = 1;
      $flagArray = explode(',', $flags);
      if ((0 < count($flagArray)) && (FALSE !== array_search($skipFlag, $flagArray))) {
        $active = 0;
      }
      if ($references) {
        $flagArray[] = 'reftest';
      }
      $flags = implode(',', $flagArray);
      
      if ((0 < strlen($baseURI)) && ('/' != substr($baseURI, -1, 1))) {
        $baseURI .= '/';
      }
      if ((0 < strlen($extension)) && ('.' != substr($extension, 0, 1))) {
        $extension = '.' . $extension;
      }
      $uri = "{$baseURI}{$testCase}{$extension}";
      
      $testCaseId = $this->mTestCaseIds[$testCase];
      
      $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
      $assertion = html_entity_decode($assertion, ENT_QUOTES, 'UTF-8');

      $testCase   = $this->encode($testCase, TESTCASES_MAX_TESTCASE);
      $title      = $this->encode($title, TESTCASES_MAX_TITLE);
      $assertion  = $this->encode($assertion, TESTCASES_MAX_ASSERTION);
      $uri        = $this->encode($uri, TESTCASES_MAX_URI);
      $flags      = $this->encode($flags);

      if (0 < $testCaseId) {
        $sql  = "UPDATE `testcases` ";
        $sql .= "SET `uri` = '{$uri}', ";
        $sql .= "`title` = '{$title}', ";
//XXX TEMP        $sql .= "`flags` = '{$flags}', ";
        $sql .= "`assertion` = '{$assertion}', ";
        $sql .= "`active` = '{$active}', ";
        $sql .= "`modified` = '{$modified}' ";
        $sql .= "WHERE `id` = '{$testCaseId}' ";
        
        $this->query($sql);
      }
      else {
        $sql  = "INSERT INTO `testcases` (`uri`, `testsuite`, `testcase`, `title`, `flags`, `assertion`, `active`, `modified`) ";
        $sql .= "VALUES ('{$uri}', '{$testSuite}', '{$testCase}', '{$title}', '{$flags}', '{$assertion}', '{$active}', '{$modified}');";
        
        $this->query($sql);
        
        $testCaseId = $this->lastInsertId();
      }

      if (0 < $testCaseId) {
        $sql  = "DELETE FROM `testlinks` ";
        $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
        
        $this->query($sql);
      
        $linkArray = explode(',', $links);
        foreach ($linkArray as $link) {
          if (0 < strlen($link)) {
            $link = $this->encode($link, TESTLINKS_MAX_URI);
            
            $sql  = "INSERT INTO `testlinks` (`testcase_id`, `uri`) ";
            $sql .= "VALUES ('{$testCaseId}', '{$link}') ";

            $this->query($sql);
          }
        }
      }
      else {
        exit("ERROR: insert failed\n");
      }
    }
  }
}

$worker = new TestCaseImport();

$worker->import("testinfo.data", "CSS21_HTML_RC4", "html4/", "htm", "nonHTML", "2010-12-10 16:29:00");
$worker->import("testinfo.data", "CSS21_XHTML_RC4", "xhtml1/", "xht", "HTMLOnly", "2010-12-10 16:29:00");

?>