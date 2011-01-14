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
 
require_once("lib/DBConnection.php");


/**
 * Encapsulate data about full ilst of test cases in a suite
 */
class TestCases extends DBConnection
{
  protected $mTestCases;


  function __construct($testSuite) 
  {
    parent::__construct();
    
    $testSuite = $this->encode($testSuite, TESTCASES_MAX_TESTSUITE);
    
    $sql  = "SELECT `id`, `testcase`, `title` ";
    $sql .= "FROM `testcases` ";
    $sql .= "WHERE `testsuite` = '{$testSuite}' ";
    $sql .= "AND `active` = '1' ";
    
    $r = $this->query($sql);

    $this->mTestCases = $r->fetchTable();

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