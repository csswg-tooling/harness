<?php
/*******************************************************************************
 *
 *  Copyright © 2011-2014 Hewlett-Packard Development Company, L.P.
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
require_once('modules/specification/Specification.php');
require_once('modules/specification/SpecificationAnchor.php');

/**
 * Class for caching result status queries
 */
class StatusCache
{

  static function GetResultsForSection(TestSuite $testSuite, Specification $spec, SpecificationAnchor $section = null)
  {
    $db = new HarnessDBConnection();
    
    $testSuiteName = $db->encode($testSuite->getName(), 'status_cache.test_suite');
    $specName = $db->encode($spec->getName(), 'status_cache.spec');
    if ($section) {
      $specType = $db->encode($section->getSpecType());
      $parentName = $db->encode($section->getParentName(), 'status_cache.parent_name');
      $anchorName = $db->encode($section->getName(), 'status_cache.anchor_name');
    }
    else {
      $specType = 'official';
      $parentName = '';
      $anchorName = '';
    }
    
    $sql  = "SELECT `data` ";
    $sql .= "FROM `status_cache` ";
    $sql .= "WHERE `test_suite` = '{$testSuiteName}'  ";
    $sql .= "  AND `spec` = '{$specName}' ";
    $sql .= "  AND `spec_type` = '{$specType}' ";
    $sql .= "  AND `parent_name` = '{$parentName}' ";
    $sql .= "  AND `anchor_name` = '{$anchorName}' ";
    $r = $db->query($sql);
    $dataString = $r->fetchField(0);
    
    if ($dataString) {
      $data = json_decode($dataString, TRUE);
      return $data;
    }
    return FALSE;
  }
  
  
  static function SetResultsForSection(TestSuite $testSuite, Specification $spec, SpecificationAnchor $section = null, $results = null)
  {
    $db = new HarnessDBConnection();
    
    $testSuiteName = $db->encode($testSuite->getName(), 'status_cache.test_suite');
    $specName = $db->encode($spec->getName(), 'status_cache.spec');
    if ($section) {
      $specType = $db->encode($section->getSpecType());
      $parentName = $db->encode($section->getParentName(), 'status_cache.parent_name');
      $anchorName = $db->encode($section->getName(), 'status_cache.anchor_name');
    }
    else {
      $specType = 'official';
      $parentName = '';
      $anchorName = '';
    }
    
    $data = $db->encode(json_encode($results));
    
    $sql  = "INSERT INTO `status_cache` ";
    $sql .= "  (`test_suite`, `spec`, `spec_type`, `parent_name`, `anchor_name`, `data`) ";
    $sql .= "VALUES ('{$testSuiteName}', '{$specName}', '{$specType}', '$parentName', '{$anchorName}', '{$data}') ";
    $sql .= "ON DUPLICATE KEY UPDATE `data` = '{$data}' ";
    
    $db->query($sql);
  }
  
  
  static function FlushResultsForTestSuite(TestSuite $testSuite)
  {
    $db = new HarnessDBConnection();
    
    $testSuiteName = $db->encode($testSuite->getName(), 'status_cache.test_suite');

    $sql  = "DELETE FROM `status_cache` ";
    $sql .= "WHERE `test_suite` = '{$testSuiteName}' ";
    
    $db->query($sql);
  }
  
  
  static function FlushAllResults()
  {
    $db = new HarnessDBConnection();
    
    $sql  = "TRUNCATE TABLE `status_cache` ";
    
    $db->query($sql);
  }
}  

?>