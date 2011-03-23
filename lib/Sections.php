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


  function __construct(TestSuite $testSuite)
  {
    parent::__construct();

    $testSuiteName = $this->encode($testSuite->getName(), SUITETESTS_MAX_TESTSUITE);
    $specName = $this->encode($testSuite->getSpecName(), SPECLINKS_MAX_SPEC);
    
    $sql  = "SELECT `speclinks`.`id`, `speclinks`.`parent_id`, ";
    $sql .= "`speclinks`.`section`, `speclinks`.`title`, ";
    $sql .= "`speclinks`.`uri`, COUNT(*) as `group_count` ";
    $sql .= "FROM `speclinks` ";
    $sql .= "LEFT JOIN (`testlinks`, `suitetests`) ";
    $sql .= "ON `speclinks`.`id` = `testlinks`.`speclink_id` ";
    $sql .= "AND `testlinks`.`testcase_id` = `suitetests`.`testcase_id` ";
    $sql .= "WHERE `suitetests`.`testsuite` = '{$testSuiteName}' ";
    $sql .= "AND `speclinks`.`spec` = '{$specName}' ";
    $sql .= "GROUP BY `speclinks`.`id` ";
    $sql .= "ORDER BY `speclinks`.`id` ";
    
    $r = $this->query($sql);
    
    if (! $r->succeeded()) {
      $msg = 'Unable to obtain list of sections.';
      trigger_error($msg, E_USER_ERROR);
    }
    
    while ($sectionData = $r->fetchRow()) {
      $id = intval($sectionData['id']);
      $parentId = intval($sectionData['parent_id']);
    
      $sectionData['id'] = $id;
      $sectionData['parent_id'] = $parentId;
      $sectionData['group_count'] = intval($sectionData['group_count']);
      $sectionData['test_count'] = 0;

      $this->mSections[$parentId][$id] = $sectionData;
    }

    // get test counts of sections that have direct test links (not group)
    $sql  = "SELECT `speclinks`.`id`, `speclinks`.`parent_id`, ";
    $sql .= "COUNT(*) as `test_count` ";
    $sql .= "FROM `speclinks` ";
    $sql .= "LEFT JOIN (`testlinks`, `suitetests`) ";
    $sql .= "ON `speclinks`.`id` = `testlinks`.`speclink_id` ";
    $sql .= "AND `testlinks`.`testcase_id` = `suitetests`.`testcase_id` ";
    $sql .= "WHERE `suitetests`.`testsuite` = '{$testSuiteName}' ";
    $sql .= "AND `speclinks`.`spec` = '{$specName}' ";
    $sql .= "AND `testlinks`.`group` = 0 ";
    $sql .= "GROUP BY `speclinks`.`id` ";
    $sql .= "ORDER BY `speclinks`.`id` ";
    
    $r = $this->query($sql);
    
    if (! $r->succeeded()) {
      $msg = 'Unable to obtain list of sections.';
      trigger_error($msg, E_USER_ERROR);
    }
    
    while ($sectionData = $r->fetchRow()) {
      $id = intval($sectionData['id']);
      $parentId = intval($sectionData['parent_id']);
      $testCount = intval($sectionData['test_count']);

      $this->mSections[$parentId][$id]['test_count'] = $testCount;
    }
  }


  function getCount($parentId = 0)
  {
    if ($this->mSections && array_key_exists($parentId, $this->mSections)) {
      return count($this->mSections[$parentId]);
    }
    return 0;
  }
  
  
  function getSectionData($parentId = 0)
  {
    if ($this->mSections && array_key_exists($parentId, $this->mSections)) {
      return $this->mSections[$parentId];
    }
    return FALSE;
  }
  
}

?>