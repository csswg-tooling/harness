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
 * Class to load result details
 */
class ResultDetails extends DBConnection
{
  protected $mData;


  function __construct($testSuiteQuery, $testCaseName, $testGroupName,
                       $engine, $engineVersion, $platform, 
                       $grouping, $modified, $order)
  {
    parent::__construct();
    
    $sql  = "SELECT `testcase`, `testsuite`, `result`, ";
    $sql .= "`results`.`modified` AS `date`, `source`, `results`.`useragent_id` ";
    $sql .= "FROM `testcases` LEFT JOIN (`results`, `useragents`) ";
    $sql .= "ON (`testcases`.`id` = `results`.`testcase_id` AND `results`.`useragent_id` = `useragents`.`id`) ";
    $sql .= "WHERE `testcases`.`testsuite` LIKE '" . $this->encode($testSuiteQuery, TESTCASES_MAX_TESTSUITE) . "' ";
    $sql .= "AND `testcases`.`active` = '1' ";
    $sql .= "AND `results`.`ignore` = '0' ";
    $sql .= "AND `results`.`revision` = `testcases`.`revision` ";
    
    if ($testCaseName) {
      $sql .= "AND `testcases`.`testcase` = '" . $this->encode($testCaseName, TESTCASES_MAX_TESTCASE) . "' ";
    }
    elseif ($testGroupName) {
      $sql .= "AND `testcases`.`testgroup` = '" . $this->encode($testGroupName, TESTCASES_MAX_TESTGROUP) . "' ";
    }
    
    if ($modified) {
      $sql .= "AND `results`.`modified` <= '" . $this->encode($modified) . "' ";
    }  
     
    if ($engine) {
      $sql .= "AND `engine` = '" . $this->encode($engine, USERAGENTS_MAX_ENGINE) . "' ";
      if ($engineVersion) {
        $sql .= "AND `engine_version` = '" . $this->encode($engineVersion, USERAGENTS_MAX_ENGINE_VERSION) . "' ";
      }
    }
    if ($platform) {
      $sql .= "AND `platform` = '" . $this->encode($platform, USERAGENTS_MAX_PLATFORM) . "' ";
    }
    $sql .= "ORDER BY `testcase`, `testsuite`, `result`, `engine`, `engine_version`, `browser`, `browser_version`, `date` ";

//print $sql;
    $r = $this->query($sql);

    if (! $r->succeeded()) {
      $msg = 'Unable to obtain results.';
      trigger_error($msg, E_USER_ERROR);
    }

    $this->mData = $r->fetchTable();
  }


  function getData()
  {
    return $this->mData;
  }
}

?>