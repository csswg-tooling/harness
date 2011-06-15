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

require_once("core/DBConnection.php");


/**
 * Encapsulate data about test groups
 */
class Sections extends DBConnection
{
  protected $mSections;
  protected $mTestCaseIds;
  protected $mPrimarySectionIds;


  static function GetSectionIdFor(TestSuite $testSuite, $sectionName)
  {
    if ($testSuite->isValid() && $sectionName) {
      $db = new DBConnection();

      $specName = $db->encode($testSuite->getSpecName(), 'speclinks.spec');
      $sectionName = $db->encode($sectionName, 'speclinks.section');
      
      $sql  = "SELECT `id` ";
      $sql .= "FROM `speclinks` ";
      $sql .= "WHERE `spec` = '{$specName}' AND `section` = '{$sectionName}' ";
      
      $r = $db->query($sql);
      $sectionId = intval($r->fetchField(0, 'id'));
      
      if ($sectionId) {
        return $sectionId;
      }
    }
    return FALSE;
  }
  
  
  static function GetSectionNameFor(TestSuite $testSuite, $sectionId)
  {
    if ($testSuite->isValid() && $sectionId) {
      $db = new DBConnection();

      $specName = $db->encode($testSuite->getSpecName(), 'speclinks.spec');
      
      $sql  = "SELECT `section` ";
      $sql .= "FROM `speclinks` ";
      $sql .= "WHERE `spec` = '{$specName}' AND `id` = '{$sectionId}' ";
      
      $r = $db->query($sql);
      $sectionName = $r->fetchField(0, 'section');
      
      if ($sectionName) {
        return $sectionName;
      }
    }
    return FALSE;
  }
  
  
  function __construct(TestSuite $testSuite, $loadTestCaseIds = FALSE)
  {
    parent::__construct();

    $testSuiteName = $this->encode($testSuite->getName(), 'suitetests.testsuite');
    $specName = $this->encode($testSuite->getSpecName(), 'speclinks.spec');
    
    $sql  = "SELECT `speclinks`.`id`, `speclinks`.`parent_id`, ";
    $sql .= "`speclinks`.`section`, `speclinks`.`title`, ";
    $sql .= "`speclinks`.`uri`, ";
    $sql .= "SUM(IF(`testlinks`.`group`=0,1,0)) as `test_count` ";
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
      $sectionData['test_count'] = intval($sectionData['test_count']);

      $this->mSections[$parentId][$id] = $sectionData;
    }
    
    if ($loadTestCaseIds) {
      $sql  = "SELECT `testcases`.`id`, `testlinks`.`speclink_id`, ";
      $sql .= "`testlinks`.`sequence` ";
      $sql .= "FROM `testcases` ";
      $sql .= "LEFT JOIN (`suitetests`, `testlinks`, `speclinks`) ";
      $sql .= "ON `testcases`.`id` = `suitetests`.`testcase_id` ";
      $sql .= "AND `testcases`.`id` = `testlinks`.`testcase_id` ";
      $sql .= "AND `speclinks`.`id` = `testlinks`.`speclink_id` ";
      $sql .= "WHERE `suitetests`.`testsuite` = '{$testSuiteName}' ";
      $sql .= "AND `testlinks`.`group` = 0 ";
      $sql .= "AND `speclinks`.`spec` = '{$specName}' ";
      $sql .= "ORDER BY `testcases`.`testcase` ";

      $r = $this->query($sql);
      
      while ($testCaseData = $r->fetchRow()) {
        $sectionId = intval($testCaseData['speclink_id']);
        $testCaseId = intval($testCaseData['id']);
        $this->mTestCaseIds[$sectionId][] = $testCaseId;
        if (0 == intval($testCaseData['sequence'])) {
          $this->mPrimarySectionIds[$testCaseId] = $sectionId;
        }
      }
    }
  }


  function getSectionData($sectionId)
  {
    foreach ($this->mSections as $parentId => $subSections) {
      if (array_key_exists($sectionId, $subSections)) {
        return $subSections[$sectionId];
      }
    }
    return FALSE;
  }
  

  function getSubSectionCount($parentId = 0)
  {
    if ($this->mSections && array_key_exists($parentId, $this->mSections)) {
      return count($this->mSections[$parentId]);
    }
    return 0;
  }
  
  
  function getSubSectionData($parentId = 0)
  {
    if ($this->mSections && array_key_exists($parentId, $this->mSections)) {
      return $this->mSections[$parentId];
    }
    return FALSE;
  }
  
  
  function getTestCaseIdsFor($sectionId, $recursive = FALSE)
  {
    $testCaseIds = array();
    if ($this->mTestCaseIds && array_key_exists($sectionId, $this->mTestCaseIds)) {
      $testCaseIds = $this->mTestCaseIds[$sectionId];
    }
    if ($recursive) {
      $subSections = $this->getSubSectionData($sectionId);
      if ($subSections) {
        foreach ($subSections as $subSectionId => $subSectionData) {
          $subSectionTestIds = $this->getTestCaseIdsFor($subSectionData['id'], TRUE);
          if ($subSectionTestIds) {
            $testCaseIds = array_unique(array_merge($testCaseIds, $subSectionTestIds));
          }
        }
      }
    }
    return ((0 < count($testCaseIds)) ? $testCaseIds : FALSE);
  }
  
  
  function getPrimarySectionFor($testCaseId)
  {
    if ($this->mPrimarySectionIds && array_key_exists($testCaseId, $this->mPrimarySectionIds)) {
      return $this->mPrimarySectionIds[$testCaseId];
    }
    return FALSE;
  }
  
  
  function findSectionIdForURI($uri)
  {
    foreach ($this->mSections as $parentId => $subSections) {
      foreach ($subSections as $sectionId => $sectionData) {
        if ($sectionData['uri'] == $uri) {
          return $sectionId;
        }
      }
    }
    return FALSE;
  }
  
}

?>