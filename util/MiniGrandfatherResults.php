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
class MiniGrandfatherResults extends DBConnection
{  

  function __construct() 
  {
    parent::__construct();
  }
  
  XXXX Update to set equal_revision in revisions table instead of copy results
  
  /**
   * Copy test results from one suite to another.
   * 
   * id of source result is preserved in original_id
   */
  function copyResults($testSuiteName, $testCaseNames, $oldRevision, $newRevision)
  {
    $testSuiteName = $this->encode($testSuiteName);
    
    foreach ($testCaseNames as $testCaseName) {
      $testCaseName = $this->encode($testCaseName);
      $sql  = "SELECT `id` ";
      $sql .= "FROM `testcases` ";
      $sql .= "WHERE `testsuite` = '{$testSuiteName}' AND `testcase` = '{$testCaseName}'";
      $r = $this->query($sql);
      
      $testCaseIds[] = intval($r->fetchField(0, 'id'));
    }
    
    foreach ($testCaseIds as $testCaseId) {
      echo "{$testCaseId}:{$oldRevision} to {$newRevision}\n";

      $sql  = "SELECT `id`, `revision`, `useragent_id`, `source`, `source_useragent_id`, `result`, `modified` ";
      $sql .= "FROM `results` ";
      $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
      $sql .= "AND `result` != 'na' ";
      $sql .= "AND `ignore` = '0' ";
      $sql .= "AND `revision` = '{$oldRevision}' ";
      $r = $this->query($sql);

      while ($resultData = $r->fetchRow()) {
        $resultId           = $resultData['id'];
        $useragentId        = $resultData['useragent_id'];
        $source             = $resultData['source'];
        $sourceUserAgentId  = $resultData['source_useragent_id'];
        $result             = $resultData['result'];
        $modified           = $resultData['modified'];

        $source = $this->encode($source);

        echo "  {$useragentId} {$source} {$result} {$modified}\n";
        
        $sql  = "INSERT INTO `results` (`testcase_id`, `revision`, `useragent_id`, `source`, `source_useragent_id`, `original_id`, `result`, `modified`) ";
        $sql .= "VALUES ('{$testCaseId}', '{$newRevision}', '{$useragentId}', '{$source}', '{$sourceUserAgentId}', '{$resultId}', '{$result}', '{$modified}')";

        $this->query($sql);
      }
    }
  }
}


$worker = new MiniGrandfatherResults();

$testCases = array(
  'list-style-position-001',
  'list-style-position-002',
  'list-style-position-003',
  'list-style-position-005',
  'list-style-position-006',
  'list-style-position-007',
  'list-style-position-008',
  'list-style-position-009',
  'list-style-position-010',
  'list-style-position-011',
  'list-style-position-012',
  'list-style-position-013',
  'list-style-position-014',
  'list-style-position-015',
  'list-style-position-016',
  'list-style-position-017',
);

$worker->copyResults('CSS21_HTML', $testCases, 1345, 1987);
$worker->copyResults('CSS21_XHTML', $testCases, 1345, 1987);

$testCases = array(
  'page-grammar-001',
  'page-grammar-002'
);

$worker->copyResults('CSS21_HTML', $testCases, 1813, 1987);
$worker->copyResults('CSS21_XHTML', $testCases, 1813, 1987);

?>
