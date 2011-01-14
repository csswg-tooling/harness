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
 * Encapsulate data about test groups
 */
class Groups extends DBConnection
{
  protected $mGroups;


  function __construct($testSuite) 
  {
    parent::__construct();
    
    $testSuite = $this->encode($testSuite, TESTCASES_MAX_TESTSUITE);
    
    $sql  = "SELECT DISTINCT `testcases`.`testgroup`, `testgroups`.`title` ";
    $sql .= "FROM `testcases` LEFT JOIN `testgroups` ";
    $sql .= "ON `testcases`.`testgroup` = `testgroups`.`testgroup` ";
    $sql .= "WHERE `testcases`.`testsuite` = '{$testSuite}'";
    
    $r = $this->query($sql);
    
    if (! $r->succeeded()) {
      $msg = 'Unable to obtain list of test groups.';
      trigger_error($msg, E_USER_ERROR);
    }
    
    while ($testGroup = $r->fetchRow()) {
      if ($testGroup['testgroup'] != '') {
        $this->mGroups[] = $testGroup;
      }
    }
  }

  function getCount()
  {
    if ($this->mGroups) {
      return count($this->mGroups);
    }
    return 0;
  }
  
  
  function getGroupData()
  {
    if ($this->mGroups) {
      return $this->mGroups;
    }
    return FALSE;
  }
  
}

?>