<?php
////////////////////////////////////////////////////////////////////////////////
//
//  Copyright © 2010 World Wide Web Consortium, 
//  (Massachusetts Institute of Technology, European Research 
//  Consortium for Informatics and Mathematics, Keio 
//  University). All Rights Reserved. 
//  Copyright © 2010 Hewlett-Packard Development Company, L.P. 
// 
//  This work is distributed under the W3C¬ Software License 
//  [1] in the hope that it will be useful, but WITHOUT ANY 
//  WARRANTY; without even the implied warranty of 
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// 
//  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
//
//////////////////////////////////////////////////////////////////////////////// 

//////////////////////////////////////////////////////////////////////////////// 
//
//  grandfather.php
//
//  Adapted from Mobile Test Harness [1]
//
//    File: grandfather.php
//      Lines: 103-142
//
//  where herein specific contents provided by the original harness have
//  been adapted for CSS2.1 conformance testing. Separately, controls have
//  been added to allow entering data for user agents other than the one
//  accessing the harness, and the means by which test presentation order
//  is provided have been altered. Separately, the ability to request
//  only those tests in a particular named group has been added.
//
// [1] http://dev.w3.org/cvsweb/2007/mobile-test-harness/
//
//////////////////////////////////////////////////////////////////////////////// 

require_once("./lib_test_harness/class.DBConnection.phi");

////////////////////////////////////////////////////////////////////////////////
//
//  class grandfather
//
//  This class copies test results fom one testsuite to another
//  The intended usage is when a new version of a testuite is posted
//  and not all tests have changed. 
//  
//  To use this class, set a value of '1' in the grandfather field for
//  all testcases where results should be copied, then call copyRresults
//  with the relevant test suites.
//
//  The copy function resets the grandfather field to '2' to avoid multiple
//  copies of results. 
//  
////////////////////////////////////////////////////////////////////////////////
class GrandfatherResults extends DBConnection
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
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Copy test results from one suite to another.
  //
  //  id of source result is preserved in original_id
  //
  ////////////////////////////////////////////////////////////////////////////
  function copyResults($fromSuite, $toSuite)
  {
    $sql = "SELECT `id`, `testcase` FROM `testcases` WHERE `testsuite` = '{$toSuite}' AND `grandfather` = '1'";
    $r = $this->query($sql);
    $dbList = $r->fetch_table(); 
    foreach ($dbList as $dbData) {
      $newTestcaseId = $dbData['id'];
      $testCase = $dbData['testcase'];
      
      echo "Copy results from {$testCase} ";
      
      $sql  = "SELECT `id` FROM `testcases` ";
      $sql .= "WHERE `testcase` = '{$testCase}' AND `testsuite` = '{$fromSuite}' ";
      $sql .= "LIMIT 1";
      $r = $this->query($sql);
      
      if ($r->is_false()) {
        echo "not in new suite\n";
      }
      else {
        $testCaseList = $r->fetch_table();
        $oldTestcaseId = $testCaseList[0]['id'];
        
        echo "{$oldTestcaseId} to {$newTestcaseId}\n";
        
        $sql  = "SELECT `id`, `useragent_id`, `source`, `result`, `modified` FROM `results` ";
        $sql .= "WHERE `testcase_id` = '{$oldTestcaseId}' ";
        $sql .= "AND `result` != 'na'";
        $r = $this->query($sql);

        if (! $r->is_false()) {
          $resultList = $r->fetch_table();
          foreach ($resultList as $resultData) {
            $resultId     = $resultData['id'];
            $useragentId  = $resultData['useragent_id'];
            $source       = $resultData['source'];
            $result       = $resultData['result'];
            $modified     = $resultData['modified'];
            
            echo "  {$useragentId} {$source} {$result} {$modified}\n";
            
            $sql  = "INSERT INTO results (testcase_id, useragent_id, source, original_id, result, modified) VALUES ";
            $sql .= "('{$newTestcaseId}', '{$useragentId}', '{$source}', '{$resultId}', '{$result}', '{$modified}')";

            $this->query($sql);
          }
        }
      }
      
      $sql  = "UPDATE testcases SET grandfather='2' WHERE id='{$newTestcaseId}'";
      $this->query($sql);      
    }
  }
}

$worker = new GrandfatherResults();

$worker->copyResults('CSS21_HTML_RC3', 'CSS21_HTML_RC4');
$worker->copyResults('CSS21_XHTML_RC3', 'CSS21_XHTML_RC4');

?>
