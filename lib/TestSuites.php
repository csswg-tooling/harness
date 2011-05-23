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
 * Class to encapsulate data about all test suites
 */
class TestSuites extends DBConnection
{
  protected $mTestSuites;


  function __construct() 
  {
    parent::__construct();
    
    $sql  = "SELECT DISTINCT `suitetests`.`testsuite` ";
    $sql .= "FROM `suitetests` LEFT JOIN `testsuites` ";
    $sql .= "ON `suitetests`.`testsuite` = `testsuites`.`testsuite` ";
    $sql .= "WHERE `testsuites`.`active` = '1' ";
    $sql .= "ORDER BY `testsuites`.`locked`, `testsuites`.`testsuite` ";
    
    $r = $this->query($sql);
    
    while ($testSuite = $r->fetchRow()) {
      $this->mTestSuites[] = new TestSuite($testSuite['testsuite']);
    }
  }



  function getCount()
  {
    if ($this->mTestSuites) {
      return count($this->mTestSuites);
    }
    return 0;
  }
  
  
  function getTestSuites()
  {
    return $this->mTestSuites;
  }

}

?>