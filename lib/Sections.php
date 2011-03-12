<?php
/*******************************************************************************
 *
 *  Copyright © 2011 Hewlett-Packard Development Company, L.P. 
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
class Sections extends DBConnection
{
  protected $mSections;


  function __construct($testSuite, $parentId = 0)
  {
    parent::__construct();

    $testSuiteName = $this->encode($testSuite->getName(), SUITETESTS_MAX_TESTSUITE);
    $specName = $this->encode($testSuite->getSpecName(), SPECLINKS_MAX_SPEC);
    
    $sql  = "SELECT DISTINCT `speclinks`.`id`, ";
    $sql .= "`speclinks`.`section`, `speclinks`.`title`, ";
    $sql .= "`speclinks`.`uri` ";
    $sql .= "FROM `speclinks` ";
    $sql .= "LEFT JOIN (`testlinks`, `suitetests`) ";
    $sql .= "ON `speclinks`.`id` = `testlinks`.`speclink_id` ";
    $sql .= "AND `testlinks`.`testcase_id` = `suitetests`.`testcase_id` ";
    $sql .= "WHERE `suitetests`.`testsuite` = '{$testSuiteName}' ";
    $sql .= "AND `speclinks`.`spec` = '{$specName}' ";
    $sql .= "AND `speclinks`.`parent_id` = '{$parentId}' ";
    $sql .= "ORDER BY `speclinks`.`id` ";
    
    $r = $this->query($sql);
    
    if (! $r->succeeded()) {
      $msg = 'Unable to obtain list of sections.';
      trigger_error($msg, E_USER_ERROR);
    }
    
    $this->mSections = $r->fetchTable();
  }

  function getCount()
  {
    if ($this->mSections) {
      return count($this->mSections);
    }
    return 0;
  }
  
  
  function getSectionData()
  {
    if ($this->mSections) {
      return $this->mSections;
    }
    return FALSE;
  }
  
}

?>