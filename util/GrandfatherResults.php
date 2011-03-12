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
 * This class copies test results fom one testsuite to another
 *  The intended usage is when a new version of a testuite is posted
 *  and not all tests have changed. 
 *  
 *  To use this class, set a value of '1' in the grandfather field for
 *  all testcases where results should be copied, then call copyRresults
 *  with the relevant test suites.
 *
 *  The copy function resets the grandfather field to '2' to avoid multiple
 *  copies of results. 
 */
class GrandfatherResults extends DBConnection
{  

  function __construct() 
  {
    parent::__construct();
  }
  
  /**
   * Copy test results from one suite to another.
   * 
   * id of source result is preserved in original_id
   */
  function copyResults($fromSuite, $toSuite)
  {
    $sql = "SELECT `id`, `testcase`, `revision` FROM `testcases` WHERE `testsuite` = '{$toSuite}' AND `active` = '1' ";
    $testCasesResult = $this->query($sql);
    
    while ($dbData = $testCasesResult->fetchRow()) {
      $newTestcaseId = $dbData['id'];
      $testCase = $dbData['testcase'];
      $newTestCaseRevision = $dbData['revision'];
      
      echo "Copy results from {$testCase} ";
      
      $sql  = "SELECT `id`, `revision` FROM `testcases` ";
      $sql .= "WHERE `testcase` = '{$testCase}' AND `testsuite` = '{$fromSuite}' ";
      $sql .= "LIMIT 1";
      $r = $this->query($sql);

      if (! $r->succeeded()) {
        echo "not in new suite\n";
      }
      else {
        $dbData = $r->fetchRow();
        $oldTestcaseId = $dbData['id'];
        $oldTestCaseRevision = $dbData['revision'];
        
        if ($newTestCaseRevision == $oldTestCaseRevision) {
          echo "{$oldTestcaseId}:{$oldTestCaseRevision} to {$newTestcaseId}:{$newTestCaseRevision}\n";
          
          $sql  = "SELECT `id`, `revision`, `useragent_id`, `source`, `result`, `modified` FROM `results` ";
          $sql .= "WHERE `testcase_id` = '{$oldTestcaseId}' ";
          $sql .= "AND `result` != 'na' ";
          $sql .= "AND `ignore` = '0' ";
          $sql .= "AND `revision` = '{$newTestCaseRevision}' ";
          $r = $this->query($sql);

          if ($r->succeeded()) {
            while ($resultData = $r->fetchRow()) {
              $resultId     = $resultData['id'];
              $useragentId  = $resultData['useragent_id'];
              $source       = $resultData['source'];
              $result       = $resultData['result'];
              $modified     = $resultData['modified'];
              $revision     = $resultData['revision'];
              
              echo "  {$useragentId} {$source} {$result} {$modified}\n";
              
              $source = $this->encode($source, RESULTS_MAX_SOURCE);
              
              $sql  = "INSERT INTO `results` (`testcase_id`, `revision`, `useragent_id`, `source`, `original_id`, `result`, `modified`) VALUES ";
              $sql .= "('{$newTestcaseId}', '{$revision}', '{$useragentId}', '{$source}', '{$resultId}', '{$result}', '{$modified}')";

              $this->query($sql);
            }
          }
          
          $sql  = "UPDATE `testcases` SET `grandfather` = '2' WHERE `id` = '{$newTestcaseId}'";
          $this->query($sql);      
        }
        else {
          echo "test changed\n";
        }
      }
    }
  }
}

$worker = new GrandfatherResults();

$worker->copyResults('CSS21_HTML_RC5', 'CSS21_HTML');
$worker->copyResults('CSS21_XHTML_RC5', 'CSS21_XHTML');

?>
