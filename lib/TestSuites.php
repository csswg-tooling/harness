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

require_once('lib/HarnessDB.php');

require_once('modules/testsuite/TestSuite.php');

/**
 * Class to encapsulate data about all test suites
 */
class TestSuites extends HarnessDBConnection
{
  protected $mTestSuites;
  protected $mTestCounts;


  function __construct()
  {
    parent::__construct();

    $testSuiteDB = TestSuite::GetDBName();

    $sql  = "SELECT `suite_tests`.`test_suite`, COUNT(*) as `test_count` ";
    $sql .= "FROM `suite_tests` ";
    $sql .= "LEFT JOIN `{$testSuiteDB}`.`test_suites` ";
    $sql .= "  ON `suite_tests`.`test_suite` = `{$testSuiteDB}`.`test_suites`.`test_suite` ";
    $sql .= "WHERE `{$testSuiteDB}`.`test_suites`.`active` = '1' ";
    $sql .= "GROUP BY `suite_tests`.`test_suite` ";
    $sql .= "ORDER BY `{$testSuiteDB}`.`test_suites`.`lock_date`, `{$testSuiteDB}`.`test_suites`.`test_suite` ";

    $r = $this->query($sql);

    while ($data = $r->fetchRow()) {
      $testSuiteName = mb_strtolower($data['test_suite']);
      $this->mTestSuites[$testSuiteName] = TestSuite::GetTestSuiteByName($testSuiteName);
      $this->mTestCounts[$testSuiteName] = intval($data['test_count']);
    }
  }



  function getCount()
  {
    if ($this->mTestSuites) {
      return count($this->mTestSuites);
    }
    return 0;
  }


  function getLockedCount()
  {
    $count = 0;
    if ($this->mTestSuites) {
      foreach ($this->mTestSuites as $testSuite) {
        if ($testSuite->getLockDateTime()) {
          $count++;
        }
      }
    }
    return $count;
  }


  function getTestSuites()
  {
    return $this->mTestSuites;
  }

  function getTestCount(TestSuite $testSuite)
  {
    $testSuiteName = mb_strtolower($testSuite->getName());
    if ($this->mTestCounts && array_key_exists($testSuiteName, $this->mTestCounts)) {
      return $this->mTestCounts[$testSuiteName];
    }
    return FALSE;
  }

}

?>