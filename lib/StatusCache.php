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


require_once('lib/DBConnection.php');


/**
 * Class for responding to result status queries
 */
class StatusCache
{

  static function GetResultsForSection(TestSuite $testSuite, $sectionId)
  {
    $db = new DBConnection();
    
    $testSuiteName = $db->encode($testSuite->getName(), 'statuscache.testsuite');
    $sectionId = intval($sectionId);

    $sql  = "SELECT `data` ";
    $sql .= "FROM `statuscache` ";
    $sql .= "WHERE `testsuite` = '{$testSuiteName}' AND `section_id` = '{$sectionId}' ";
    $r = $db->query($sql);
    $dataString = $r->fetchField(0, 'data');
    
    if ($dataString) {
      $data = json_decode($dataString);
      return $data;
    }
    return FALSE;
  }
  
  
  static function SetResultsForSection(TestSuite $testSuite, $sectionId, $results)
  {
    $db = new DBConnection();
    
    $testSuiteName = $db->encode($testSuite->getName(), 'statuscache.testsuite');
    $sectionId = intval($sectionId);
    $data = $db->encode(json_encode($results));
    
    $sql  = "INSERT INTO `statuscache` ";
    $sql .= "(`testsuite`, `section_id`, `data`) ";
    $sql .= "VALUES ('{$testSuiteName}', '{$sectionId}', '{$data}') ";
    $sql .= "ON DUPLICATE KEY UPDATE `data` = '{$data}' ";
    
    $db->query($sql);
  }
  
  
  static function FlushResultsForSection(TestSuite $testSuite, $sectionId)
  {
    $db = new DBConnection();
    
    $testSuiteName = $db->encode($testSuite->getName(), 'statuscache.testsuite');
    $sectionId = intval($sectionId);

    $sql  = "DELETE FROM `statuscache` ";
    $sql .= "WHERE `testsuite` = '{$testSuiteName}' AND `section_id` = '{$sectionId}' ";
    
    $db->query($sql);
  }


  static function FlushResultsForTestSuite(TestSuite $testSuite)
  {
    $db = new DBConnection();
    
    $testSuiteName = $db->encode($testSuite->getName(), 'statuscache.testsuite');

    $sql  = "DELETE FROM `statuscache` ";
    $sql .= "WHERE `testsuite` = '{$testSuiteName}' ";
    
    $db->query($sql);
  }
  
  
  static function FlushAllResults()
  {
    $db = new DBConnection();
    
    $sql  = "TRUNCATE TABLE `statuscache` ";
    
    $db->query($sql);
  }
}  

?>