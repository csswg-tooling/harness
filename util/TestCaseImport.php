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

////////////////////////////////////////////////////////////////////////////////
//
//  class TestCaseImport
//
//  Import test case data from manifest file
//
////////////////////////////////////////////////////////////////////////////////
class TestCaseImport extends DBConnection
{  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Instance variables.
  //
  ////////////////////////////////////////////////////////////////////////////
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor.
  //
  ////////////////////////////////////////////////////////////////////////////
  function __construct() 
  {
    parent::__construct();
    
  }
  
  function import($manifest, $testSuite, $baseURI, $extension, $skipFlag, $modified)
  {
    $testSuite = $this->encode($testSuite, 32);
    
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
      
      $testCase   = $this->encode($testCase, 64);
      $title      = $this->encode($title, 255);
      $assertion  = $this->encode($assertion, 255);
      $uri        = $this->encode("{$baseURI}{$testCase}.{$extension}", 255);
      $flags      = $this->encode($flags);
      
      $sql  = "INSERT INTO `testcases` (`uri`, `testsuite`, `testcase`, `title`, `flags`, `assertion`, `active`, `modified`) ";
      $sql .= "VALUES ('{$uri}', '{$testSuite}', '{$testCase}', '{$title}', '{$flags}', '{$assertion}', '{$active}', '{$modified}');";
      
      $this->query($sql);
      
      $testCaseId = $this->lastInsertId();

      if (0 < $testCaseId) {
        $linkArray = explode(',', $links);
        foreach ($linkArray as $link) {
          if (0 < strlen($link)) {
            $link = $this->encode($link, 255);
            
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