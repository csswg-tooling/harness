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
 
require_once('core/DBConnection.php');


/**
 * Encapsulate data about full ilst of test cases in a suite
 */
class TestCases extends DBConnection
{
  protected $mTestCases;


  function __construct(TestSuite $testSuite, $sectionId = 0, $group = TRUE)
  {
    parent::__construct();
    
    $testSuiteName = $this->encode($testSuite->getName(), 'suitetests.testsuite');
    
    $sql  = "SELECT `testcases`.`id`, `testcases`.`testcase`, `testcases`.`title` ";
    $sql .= "FROM `testcases` ";
    if (0 < $sectionId) {
      $sql .= "LEFT JOIN (`suitetests`, `testlinks`) ";
      $sql .= "ON `testcases`.`id` = `suitetests`.`testcase_id` ";
      $sql .= "AND `testcases`.`id` = `testlinks`.`testcase_id` ";
      $sql .= "WHERE `suitetests`.`testsuite` = '{$testSuiteName}' ";
      $sql .= "AND `testlinks`.`speclink_id` = '{$sectionId}' ";
      if (! $group) {
        $sql .= "AND `testlinks`.`group` = 0 ";
      }
    }
    else {
      $sql .= "LEFT JOIN `suitetests` ";
      $sql .= "ON `testcases`.`id` = `suitetests`.`testcase_id` ";
      $sql .= "WHERE `suitetests`.`testsuite` = '{$testSuiteName}' ";
    }
    $sql .= "ORDER BY `testcases`.`testcase` ";

    $r = $this->query($sql);

    while ($testCaseData = $r->fetchRow()) {
      $testCaseId = intval($testCaseData['id']);
      $this->mTestCases[$testCaseId] = $testCaseData;
    }

    if (! $this->mTestCases) {
      $msg = 'Unable to obtain list of test cases.';
      trigger_error($msg, E_USER_ERROR);
    }
  }


  function getCount()
  {
    if ($this->mTestCases) {
      return count($this->mTestCases);
    }
    return 0;
  }


  function getTestCaseData()
  {
    if ($this->mTestCases) {
      return $this->mTestCases;
    }
    return FALSE;
  }

}

?>