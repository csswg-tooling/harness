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
 

define('COMMAND_LINE', TRUE);

require_once("lib/DBConnection.php");


/**
 * Translate from old DB schems to V2.0
 */
class Translate
{
  protected $mOldDBName;
  protected $mNewDBName;
  protected $mOldDB;
  protected $mNewDB;
  
  protected $mOldReferences;
  protected $mOldSpecLinks;

  protected $mNewTestCaseIds;
  protected $mNewTestCaseRevisions;
  protected $mNewTestCaseRevisionEqual;
  protected $mNewSpecLinkIds;
  protected $mNewSpecLinkParentIds;


  function __construct() 
  {
    $this->mOldDBName = "testharness";
    $this->mNewDBName = "testharness2";
    $this->mOldDB = new DBConnection($this->mOldDBName, TRUE);
    $this->mNewDB = new DBConnection($this->mNewDBName, TRUE);

    $this->mNewTestCaseIds = array();
    $this->mNewTestCaseRevisions = array();
    $this->mNewTestCaseRevisionEqual = array();
  }
  
  
  protected function _copyTable($tableName)
  {
    echo "Copying table: `{$tableName}`\n";
    
    $sql  = "TRUNCATE TABLE `{$tableName}` ";
    
    $this->mNewDB->query($sql);
  
    $sql  = "INSERT INTO `{$tableName}` ";
    $sql .= "SELECT * ";
    $sql .= "FROM `{$this->mOldDBName}`.`{$tableName}` ";

    $this->mNewDB->query($sql);
  }
  
  
  protected function _clearTable($tableName)
  {
    echo "Clearing table: `{$tableName}`\n";
    
    $sql  = "TRUNCATE TABLE `{$tableName}` ";
    
    $this->mNewDB->query($sql);
  }
  
  protected function _importSpec($spec, $specURI, $specTitle, $specDescription)
  {
    $spec = $this->mNewDB->encode($spec, SPECIFICATIONS_MAX_SPEC);
    $specURI = $this->mNewDB->encode($specURI, SPECIFICATIONS_MAX_BASE_URI);
    $specTitle = $this->mNewDB->encode($specTitle, SPECIFICATIONS_MAX_TITLE);
    $specDescription = $this->mNewDB->encode($specDescription, SPECIFICATIONS_MAX_DESCRIPTION);
    
    $sql  = "INSERT INTO `specifications` ";
    $sql .= "(`spec`, `title`, `description`, `base_uri`) ";
    $sql .= "VALUES ('{$spec}', '{$specTitle}', '{$specDescription}', '{$specURI}') ";
    $sql .= "ON DUPLICATE KEY UPDATE `title` = '{$specTitle}', ";
    $sql .= "`base_uri` = '{$specURI}', `description` = '{$specDescription}' ";

    $this->mNewDB->query($sql);
  }
  
  
  protected function _getNewSpecURI($spec)
  {
    $spec = $this->mNewDB->encode($spec, SPECIFICATIONS_MAX_SPEC);
    
    $sql  = "SELECT `base_uri` ";
    $sql .= "FROM `specifications` ";
    $sql .= "WHERE `spec` = '{$spec}' ";

    $r = $this->mNewDB->query($sql);
    $specURI = $r->fetchField(0, 'uri');
    
    return $specURI;
  }
  
  
  protected function _importSpecLinks($manifest, $spec)
  {
    echo "Importing spec links from: '{$manifest}'\n";
    
    $specURI = $this->_getNewSpecURI($spec);
    
    $sql  = "TRUNCATE TABLE `speclinks` ";
    
    $this->mNewDB->query($sql);

    $spec = $this->mNewDB->encode($spec, SPECLINKS_MAX_SPEC);
    
    $data = file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $keyMap = array();
    foreach ($data as $record) {
      list ($key, $uri, $section, $title) = explode(' ', $record, 4);
      
      if ($specURI == substr($uri, 0, strlen($specURI))) {
        $uri = substr($uri, strlen($specURI));
      }
      else {
        echo "ERROR: Spec link does not match base URI: {$uri}\n";
      }

      $keyLen = strlen($key);
      if (2 < $keyLen) {
        $parentKey = substr($key, 0, ((0 == ($keyLen % 2)) ? -2 : -3));
        $parentId = $keyMap[$parentKey];
      }
      else {
        $parentId = 0;
      }
      
      $section  = $this->mNewDB->encode($section, SPECLINKS_MAX_SECTION);
      $title    = $this->mNewDB->encode($title, SPECLINKS_MAX_TITLE);
      $uri      = $this->mNewDB->encode($uri, SPECLINKS_MAX_URI);
      
      $sql  = "INSERT INTO `speclinks` ";
      $sql .= "(`parent_id`, `spec`, `section`, `title`, `uri`) ";
      $sql .= "VALUES ('{$parentId}', '{$spec}', '{$section}', '{$title}', '{$uri}')";
      
      $this->mNewDB->query($sql);
      
      $id = $this->mNewDB->lastInsertId();
      $keyMap[$key] = $id;
    }
  }
  
  protected function _addNewTestCase($testCaseName, $testCaseId, $revision, $date)
  {
    if (array_key_exists($testCaseName, $this->mNewTestCaseIds)) {
      die("Test case already translated\n");
    }
    
    $this->mNewTestCaseIds[$testCaseName] = $testCaseId;
    $this->mNewTestCaseRevisions[$testCaseId][$date] = $revision;
  }
  
  protected function _addNewTestCaseRevision($testCaseId, $revision, $equalRevision, $date)
  {
    $this->mNewTestCaseRevisions[$testCaseId][$date] = $revision;
    krsort($this->mNewTestCaseRevisions[$testCaseId]);
    if ($equalRevision) {
      $this->mNewTestCaseRevisionEqual[$testCaseId][$revision] = $equalRevision;
    }
  }
  
  protected function _getNewTestCaseId($testCaseName)
  {
    if (array_key_exists($testCaseName, $this->mNewTestCaseIds)) {
      return $this->mNewTestCaseIds[$testCaseName];
    }
    return FALSE;
  }

  protected function _getNewTestCaseRevision($testCaseId)
  {
    if (array_key_exists($testCaseId, $this->mNewTestCaseRevisions)) {
      return reset($this->mNewTestCaseRevisions[$testCaseId]);
    }
    return FALSE;
  }
  
  protected function _newTestCaseHasRevision($testCaseId, $revision)
  {
    $revisions = $this->mNewTestCaseRevisions[$testCaseId];
    return in_array($revision, $revisions);
  }
  
  protected function _newTestCaseRevisionEqual($testCaseId, $revision, $oldRevision)
  {
    if (array_key_exists($testCaseId, $this->mNewTestCaseRevisionEqual)) {
      $revisions = $this->mNewTestCaseRevisionEqual[$testCaseId];
      if (array_key_exists($revision, $revisions)) {
        return $revisions[$revision] == $oldRevision;
      }
    }
    return FALSE;
  }
    
  protected function _addNewTestCaseRevisionEqual($testCaseId, $revision, $equalRevision)
  {
    $this->mNewTestCaseRevisionEqual[$testCaseId][$revision] = $equalRevision;
  }
    

  protected function _loadOldReferences()
  {
    $sql  = "SELECT * ";
    $sql .= "FROM `references` ";
    
    $r = $this->mOldDB->query($sql);
    while ($referenceData = $r->fetchRow()) {
      $testCaseId   = intval($referenceData['testcase_id']);
      
      $this->mOldReferences[$testCaseId][] = $referenceData;
    }
  }
  
  
  protected function _getOldReferencesFor($testCaseId)
  {
    if (array_key_exists($testCaseId, $this->mOldReferences)) {
      return $this->mOldReferences[$testCaseId];
    }
    return array();
  }
  
  
  protected function _loadOldSpecLinks()
  {
    $sql  = "SELECT `testcase_id`, `uri` ";
    $sql .= "FROM `testlinks` ";
    
    $r = $this->mOldDB->query($sql);
    
    while ($specLinkData = $r->fetchRow()) {
      $testCaseId = intval($specLinkData['testcase_id']);
      $uri        = $specLinkData['uri'];
      
      $this->mOldSpecLinks[$testCaseId][] = 'http://www.w3.org/TR/CSS21/' . $uri;
    }
  }
  
  
  protected function _getOldSpecLinksFor($testCaseId)
  {
    if (array_key_exists($testCaseId, $this->mOldSpecLinks)) {
      return $this->mOldSpecLinks[$testCaseId];
    }
    return array();
  }

  
  protected function _loadNewSpecLinkIDs($spec)
  {
    $specURI = $this->_getNewSpecURI($spec);

    $this->mNewSpecLinkIds = array();
    $this->mNewSpecLinkParentIds = array();
    
    $spec = $this->mNewDB->encode($spec, SPECLINKS_MAX_SPEC);
    
    $sql  = "SELECT * ";
    $sql .= "FROM `speclinks` ";
    $sql .= "WHERE `spec` = '{$spec}' ";
    
    $r = $this->mNewDB->query($sql);
    while ($specLinkData = $r->fetchRow()) {
      $specLinkId = intval($specLinkData['id']);
      $parentId   = intval($specLinkData['parent_id']);
      $uri        = $specURI . $specLinkData['uri'];
      
      $this->mNewSpecLinkIds[$uri] = $specLinkId;
      $this->mNewSpecLinkParentIds[$specLinkId] = $parentId;
    }
  }
  
  
  protected function _addNewSpecLink($specLinkId, $specLinkURI)
  {
    $this->mNewSpecLinkIds[$specLinkURI] = $specLinkId;
  }
  
  
  protected function _getNewSpecLinkId($specURI)
  {
    if (array_key_exists($specURI, $this->mNewSpecLinkIds)) {
      return $this->mNewSpecLinkIds[$specURI];
    }
    return FALSE;
  }
  
  
  protected function _getNewSpecLinkParentId($specLinkId)
  {
    if (array_key_exists($specLinkId, $this->mNewSpecLinkParentIds)) {
      return $this->mNewSpecLinkParentIds[$specLinkId];
    }
    return FALSE;
  }
  
  
  protected function _explodeTrimAndFilter($delimiter, $string)
  {
    $result = array();
    
    $array = explode($delimiter, $string);
    foreach($array as $field) {
      $field = trim($field);
      if ($field) {
        $result[] = $field;
      }
    }
    
    return $result;
  }
  
  
  protected function _translateTestCases($testSuiteName, $format, $respectGrandfather = TRUE)
  {
    // copy testsuites + testcases + refrences + testlinks -> testcases . revisions . testpages . references . speclinks . testlinks
    echo ("Copying test cases from {$testSuiteName}\n");
    
    $testSuiteName = $this->mOldDB->encode($testSuiteName);
    $format = $this->mNewDB->encode($format);
    
    $sql  = "SELECT * ";
    $sql .= "FROM `testcases` ";
    $sql .= "WHERE `testsuite` = '{$testSuiteName}' ";
    $sql .= "ORDER BY `testcase` ";
    
    $testCaseQueryResult = $this->mOldDB->query($sql);
    while ($testCaseData = $testCaseQueryResult->fetchRow()) {
      $oldTestCaseId  = intval($testCaseData['id']);
      $testCaseName   = $testCaseData['testcase'];
      $uri            = $testCaseData['uri'];
      $revision       = intval($testCaseData['revision']);
      $title          = $testCaseData['title'];
      $flagString     = $testCaseData['flags'];
      $assertion      = $testCaseData['assertion'];
      $credits        = $testCaseData['credits'];
      $grandfather    = intval($testCaseData['grandfather']);
      $active         = intval($testCaseData['active']);
      $modified       = $testCaseData['modified'];
      
//      $flagArray = $this->_explodeTrimAndFilter(',', $flagString);    XXX verify flags vs format and active
      
      $newTestCaseId = $this->_getNewTestCaseId($testCaseName);
      
      $testCaseName = $this->mNewDB->encode($testCaseName, TESTCASES_MAX_TESTCASE);
      $title        = $this->mNewDB->encode($title, TESTCASES_MAX_TITLE);
      $flagString   = $this->mNewDB->encode($flagString);
      $assertion    = $this->mNewDB->encode($assertion, TESTCASES_MAX_ASSERTION);
      $credits      = $this->mNewDB->encode($credits, TESTCASES_MAX_CREDITS);
      
      $uri          = $this->mNewDB->encode($uri, TESTPAGES_MAX_URI);

      // testcases
      if ($newTestCaseId) {
        $sql  = "UPDATE `testcases` ";
        $sql .= "SET `last_revision` = '{$revision}', ";
        $sql .= "`title` = '{$title}', ";
        $sql .= "`flags` = '{$flagString}', ";
        $sql .= "`assertion` = '{$assertion}', ";
        $sql .= "`credits` = '{$credits}' ";
        $sql .= "WHERE `id` = '{$newTestCaseId}' ";
        
        $this->mNewDB->query($sql);

        // revisions
        $prevRevision = $this->_getNewTestCaseRevision($newTestCaseId);
        if ($prevRevision != $revision) {
          
          if ($respectGrandfather && $grandfather) {
            $equalRevision = $prevRevision;
          }
          else {
            $equalRevision = 0;
          }
          
          $this->_addNewTestCaseRevision($newTestCaseId, $revision, $equalRevision, $modified);
          
          $sql  = "INSERT INTO `revisions` ";
          $sql .= "(`testcase_id`, `revision`, `equal_revision`, `date`) ";
          $sql .= "VALUES ('{$newTestCaseId}', '{$revision}', '{$equalRevision}', '{$modified}') ";

          $this->mNewDB->query($sql);
        }
      }
      else {
        $sql  = "INSERT INTO `testcases` ";
        $sql .= "(`testcase`, `last_revision`, `title`, `flags`, `assertion`, `credits`) ";
        $sql .= "VALUES ('{$testCaseName}', '{$revision}', '{$title}', '{$flagString}', '{$assertion}', '{$credits}') ";
        
        $this->mNewDB->query($sql);
        $newTestCaseId = $this->mNewDB->lastInsertId();

        // revisions
        $sql  = "INSERT INTO `revisions` ";
        $sql .= "(`testcase_id`, `revision`, `equal_revision`, `date`) ";
        $sql .= "VALUES ('{$newTestCaseId}', '{$revision}', 0, '{$modified}') ";

        $this->mNewDB->query($sql);

        $this->_addNewTestCase($testCaseName, $newTestCaseId, $revision, $modified);
      }
      
      
      // testpages
      if ($active) {
        $sql  = "INSERT INTO `testpages` (`testcase_id`, `format`, `uri`) ";
        $sql .= "VALUES ('{$newTestCaseId}', '{$format}', '{$uri}') ";
        $sql .= "ON DUPLICATE KEY UPDATE `uri` = '{$uri}' ";

        $this->mNewDB->query($sql);
      }
      else {
        $sql  = "DELETE FROM `testpages` ";
        $sql .= "WHERE `testcase_id` = '{$newTestCaseId}' ";
        $sql .= "AND `format` = '{$format}' ";
        
        $this->mNewDB->query($sql);
      }

      // references
      $sql  = "DELETE FROM `references` ";
      $sql .= "WHERE `testcase_id` = '{$newTestCaseId}' ";
      $sql .= "AND `format` = '{$format}' ";
      
      $this->mNewDB->query($sql);

      $oldReferences = $this->_getOldReferencesFor($oldTestCaseId);
      
      foreach ($oldReferences as $referenceData) {
        $referenceName = $referenceData['reference'];
        $referenceURI  = $referenceData['uri'];
        $referenceType = $referenceData['type'];
        
        $referenceName = $this->mNewDB->encode($referenceName, REFERENCES_MAX_REFERENCE);
        $referenceURI  = $this->mNewDB->encode($referenceURI, REFERENCES_MAX_URI);
        $referenceType = $this->mNewDB->encode($referenceType);
        
        $sql  = "INSERT INTO `references` ";
        $sql .= "(`testcase_id`, `format`, `reference`, `uri`, `type`) ";
        $sql .= "VALUES ('{$newTestCaseId}', '{$format}', '{$referenceName}', '{$referenceURI}', '{$referenceType}') ";
        
        $this->mNewDB->query($sql);
      }

      // links
      $specLinks = $this->_getOldSpecLinksFor($oldTestCaseId);

      foreach ($specLinks as $specLinkURI) {
        $specLinkId = $this->_getNewSpecLinkId($specLinkURI);
        
        if (FALSE === $specLinkId) {
          echo "Adding new spec link: '{$specLinkURI}'\n";
          $specLinkURI = $this->mNewDB->encode($specLinkURI, SPECLINKS_MAX_URI);
          
          $sql  = "INSERT INTO `speclinks` ";
          $sql .= "(`uri`) ";
          $sql .= "VALUES ('{$specLinkURI}') ";
          
          $this->mNewDB->query($sql);
        
          $specLinkId = $this->mNewDB->lastInsertId();
          $this->_addNewSpecLink($specLinkId, $specLinkURI);
        }
      }
      
      // testlinks
      $sql  = "DELETE FROM `testlinks` ";
      $sql .= "WHERE `testcase_id` = '{$newTestCaseId}' ";
      
      $this->mNewDB->query($sql);
      
      $usedSpecLinkIds = array();
      foreach ($specLinks as $specLinkURI) {
        $specLinkId = $this->_getNewSpecLinkId($specLinkURI);
        if (! $specLinkId) {
          die("Unknown spec link");
        }
        
        $sql  = "INSERT INTO `testlinks` ";
        $sql .= "(`testcase_id`, `speclink_id`, `group`) ";
        $sql .= "VALUES ('{$newTestCaseId}', '{$specLinkId}', 0) ";
        
        $this->mNewDB->query($sql);
        
        $usedSpecLinkIds[$specLinkId] = TRUE;
      }

      // add parent spec links for grouping
      foreach ($specLinks as $specLinkURI) {
        $specLinkId = $this->_getNewSpecLinkId($specLinkURI);
        
        while ($specLinkId = $this->_getNewSpecLinkParentId($specLinkId)) {
          if (! isset($usedSpecLinkIds[$specLinkId])) {
            $sql  = "INSERT INTO `testlinks` ";
            $sql .= "(`testcase_id`, `speclink_id`, `group`) ";
            $sql .= "VALUES ('{$newTestCaseId}', '{$specLinkId}', 1) ";
            
            $this->mNewDB->query($sql);
            $usedSpecLinkIds[$specLinkId] = TRUE;
          }
          else {
            break;
          }
        }
      }
    }
  }
  
  
  protected function _translateTestSuite($oldTestSuiteName, $newTestSuiteName)
  {
    // copy testsuites + testcases -> suitetests
    
    echo ("Copying test case list from {$oldTestSuiteName} to {$newTestSuiteName}\n");
    
    $oldTestSuiteName = $this->mOldDB->encode($oldTestSuiteName);
    $newTestSuiteName = $this->mNewDB->encode($newTestSuiteName);
    
    $sql  = "DELETE FROM `suitetests` ";
    $sql .= "WHERE `testsuite` = '{$newTestSuiteName}' ";
    
    $this->mNewDB->query($sql);
    
    $sql  = "SELECT DISTINCT `testcase`, `revision` ";
    $sql .= "FROM `testcases` ";
    $sql .= "WHERE `testsuite` LIKE '{$oldTestSuiteName}' ";
    $sql .= "AND `active` = 1 ";
    $sql .= "ORDER BY `testcase` ";
    
    $testCaseQueryResult = $this->mOldDB->query($sql);
    
    while ($testCaseData = $testCaseQueryResult->fetchRow()) {
      $testCaseName   = $testCaseData['testcase'];
      $revision       = intval($testCaseData['revision']);
      
      $newTestCaseId = $this->_getNewTestCaseId($testCaseName);
      
      $sql  = "INSERT INTO `suitetests` ";
      $sql .= "(`testsuite`, `testcase_id`, `revision`) ";
      $sql .= "VALUES ('{$newTestSuiteName}', '{$newTestCaseId}', '{$revision}') ";
      
      $this->mNewDB->query($sql);
    }
  }


  protected function _importRevisions($manifest, $modified)
  {
    echo "Reading source file: {$manifest}\n";
    $data = file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ((! $data) || (count($data) < 2)) {
      die("missing or empty manifest file\n");
    }
    
    echo "Storing test data\n";
    $count = 0;
    foreach ($data as $record) {
      if (0 == $count++) {
        if ("id\treferences\ttitle\tflags\tlinks\trevision\tcredits\tassertion" == $record) {
          continue;
        }
        die("ERROR: unknown format\n");
      }
      list ($testCaseName, $references, $title, $flagString, $links, $revision, $credits, $assertion) = explode("\t", $record);
      
      $newTestCaseId = $this->_getNewTestCaseId($testCaseName);

      $testCaseName = $this->mNewDB->encode($testCaseName, TESTCASES_MAX_TESTCASE);
      $revision     = intval($revision);

      // testcases
      if (0 < $newTestCaseId) {
        $prevRevision = $this->_getNewTestCaseRevision($newTestCaseId);

        if ($prevRevision != $revision) {
          echo "Adding revision {$revision} for {$testCaseName}\n";
          
          $sql  = "UPDATE `testcases` ";
          $sql .= "SET `last_revision` = '{$revision}' ";
          $sql .= "WHERE `id` = '{$newTestCaseId}' ";
          
          $r = $this->mNewDB->query($sql);

          $this->_addNewTestCaseRevision($newTestCaseId, $revision, 0, $modified);

          $sql  = "INSERT INTO `revisions` ";
          $sql .= "(`testcase_id`, `revision`, `equal_revision`, `date`) ";
          $sql .= "VALUES ('{$newTestCaseId}', '{$revision}', 0, '{$modified}') ";

          $this->mNewDB->query($sql);
        }
      }
      else {
        echo "Adding testcase {$testCaseName}:{$revision}\n";
          
        $sql  = "INSERT INTO `testcases` (`testcase`, `last_revision`) ";
        $sql .= "VALUES ('{$testCaseName}', '{$revision}');";
        
        $r = $this->mNewDB->query($sql);
        
        if ($r->succeeded()) {
          $newTestCaseId = $this->mNewDB->lastInsertId();
        }
        else {
          die("Insert failed: {$testCaseName}\n");
        }

        $this->_addNewTestCase($testCaseName, $newTestCaseId, $revision, $modified);
        
        $sql  = "INSERT INTO `revisions` ";
        $sql .= "(`testcase_id`, `revision`, `equal_revision`, `date`) ";
        $sql .= "VALUES ('{$newTestCaseId}', '{$revision}', 0, '{$modified}') ";

        $this->mNewDB->query($sql);
      }
    }
  }
  

  protected function _translateResults()
  {
    // copy results -> results + sources

    $sql  = "TRUNCATE TABLE `sources` ";
    $this->mNewDB->query($sql);
    
    $sql  = "TRUNCATE TABLE `results` ";
    $this->mNewDB->query($sql);
    
    $sql  = "SELECT `testsuite`, `format` ";
    $sql .= "FROM `testsuites` ";
    
    $r = $this->mOldDB->query($sql);
    while ($testSuiteData = $r->fetchRow()) {
      $testSuiteName  = $testSuiteData['testsuite'];
      $format         = $testSuiteData['format'];
      
      $testSuiteFormat[$testSuiteName] = $this->mNewDB->encode($format);
    }
    
    echo ("Loading results\n");
    
    $sql  = "SELECT `testcases`.`testcase`, `testcases`.`testsuite`, ";
    $sql .= "`results`.`id`, ";
    $sql .= "`results`.`revision`, `results`.`useragent_id`, ";
    $sql .= "`results`.`source`, `results`.`source_useragent_id`, ";
    $sql .= "`results`.`original_id`, `results`.`result`, ";
    $sql .= "`results`.`ignore`, `results`.`modified` ";
    $sql .= "FROM `results` INNER JOIN `testcases` ";
    $sql .= "ON `results`.`testcase_id` = `testcases`.`id` ";
    $sql .= "ORDER BY `results`.`id` ";
   
    $r = $this->mOldDB->query($sql); 

    echo ("Copying results\n");
    
    $oldResultRevisions = array();
    $newResultIds = array();
    $sourceIds = array();
    
    while ($resultData = $r->fetchRow()) {
      $testCaseName       = $resultData['testcase'];
      $testSuiteName      = $resultData['testsuite'];
      $oldResultId        = intval($resultData['id']);
      $revision           = intval($resultData['revision']);
      $userAgentId        = intval($resultData['useragent_id']);
      $source             = $resultData['source'];
      $sourceUserAgentId  = intval($resultData['source_useragent_id']);
      $oldOriginalId      = intval($resultData['original_id']);
      $result             = $resultData['result'];
      $ignore             = intval($resultData['ignore']);
      $modified           = $resultData['modified'];
      
      $format = $testSuiteFormat[$testSuiteName];
      
      $newTestCaseId = $this->_getNewTestCaseId($testCaseName);
      
      $oldResultRevisions[$oldResultId] = $revision;
      

      if (! $this->_newTestCaseHasRevision($newTestCaseId, $revision)) {  // result for unknown revision
        if ($oldOriginalId) { // grandfathered result
          $originalRevision = $oldResultRevisions[$oldOriginalId];
        }
        else {
          $originalRevision = 0;
        }
        echo "Adding test case revision {$revision}={$originalRevision} for {$testCaseName}({$newTestCaseId})\n";
        
        $this->_addNewTestCaseRevision($newTestCaseId, $revision, $originalRevision, $modified);
        
        $sql  = "INSERT INTO `revisions` ";
        $sql .= "(`testcase_id`, `revision`, `equal_revision`, `date`) ";
        $sql .= "VALUES ('{$newTestCaseId}', '{$revision}', '{$originalRevision}', '{$modified}') ";

        $this->mNewDB->query($sql);
      }

      if ($oldOriginalId) { // grandfathered result
        // only keep if revision != original revision
        if (! array_key_exists($oldOriginalId, $oldResultRevisions)) {
          die("Unknown result original id\n");
        }
        
        $newOriginalId = $newResultIds[$oldOriginalId];
        if (! $newOriginalId) {
          die("Result original id not translated\n");
        }

        $originalRevision = $oldResultRevisions[$oldOriginalId];
        if ($originalRevision != $revision) {
          // update equal_revision map, result may have been entered for nightly that we lack revision info for
          if ($this->_newTestCaseHasRevision($newTestCaseId, $revision)) {
            if ($this->_newTestCaseHasRevision($newTestCaseId, $originalRevision)) {
              if (! $this->_newTestCaseRevisionEqual($newTestCaseId, $revision, $originalRevision)) {
                echo "Updating revision equal map {$revision}={$originalRevision} for {$testCaseName}({$newTestCaseId})\n";

                // safety check
                $sql  = "SELECT `equal_revision` ";
                $sql .= "FROM `revisions` ";
                $sql .= "WHERE `testcase_id` = '{$newTestCaseId}' ";
                $sql .= "AND `revision` = '{$revision}' ";
                
                $revisionResult = $this->mNewDB->query($sql);
                $oldEqualRevision = intval($revisionResult->fetchField(0, 'equal_revision'));
                if ($oldEqualRevision) {
                  die("Already has equal revision {$oldEqualRevision}\n");
                }
                
                $sql  = "UPDATE `revisions` ";
                $sql .= "SET `equal_revision` = '{$originalRevision}' ";
                $sql .= "WHERE `testcase_id` = '{$newTestCaseId}' ";
                $sql .= "AND `revision` = '{$revision}' ";
                
                $this->mNewDB->query($sql);
                
                $this->_addNewTestCaseRevisionEqual($newTestCaseId, $revision, $originalRevision);
              }
            }
            else {
              die("Unknown original revision {$originalRevision} for {$testCaseName}({$newTestCaseId})\n");
            }
          }
          else {
            die("Unknown revision {$revision} for {$testCaseName}({$newTestCaseId})\n");
          }
        }
        
        // we don't need this result anymore
        $newResultIds[$oldResultId] = $newOriginalId;

        // but, check for differences and update
        if ($ignore != $newIgnores[$newOriginalId]) {
          echo "Updating ignore {$ignore}({$newIgnores[$newOriginalId]}) {$oldResultId}->{$oldOriginalId} for {$newOriginalId} due to change in grandfathered\n";
          
          if (0 == $ignore) {
            die("Change from ignored to not? Shouldn't happen.\n");
          }
          $sql  = "UPDATE `results` ";
          $sql .= "SET `ignore` = '{$ignore}', `modified` = `modified` ";
          $sql .= "WHERE `id` = '{$newOriginalId}' ";
          $this->mNewDB->query($sql);
          $newIgnores[$newOriginalId] = $ignore;
        }
        if ($result != $newResults[$newOriginalId]) {
          echo "Updating result {$result}({$newResults[$newOriginalId]}) {$oldResultId}->{$oldOriginalId} for {$newOriginalId} due to change in grandfathered\n";
          
          $sql  = "UPDATE `results` ";
          $sql .= "SET `result` = '{$result}', `modified` = `modified` ";
          $sql .= "WHERE `id` = '{$newOriginalId}' ";
          $this->mNewDB->query($sql);
          $newResults[$newOriginalId] = $result;
        }

        //XXX verify newOriginalId == same test case
      }
      else {
        // lookup or register source
        if ($source) {
          if (array_key_exists($source, $sourceIds)) {
            $sourceId = $sourceIds[$source];
            if (! $sourceId) {
              die("Unknown source\n");
            }
          }
          else {
            $encodedSource = $this->mNewDB->encode($source, SOURCES_MAX_SOURCE);
            
            $sql  = "INSERT INTO `sources` ";
            $sql .= "(`source`) ";
            $sql .= "VALUES ('{$encodedSource}') ";
            
            $this->mNewDB->query($sql);
            
            $sourceId = $this->mNewDB->lastInsertId();
            $sourceIds[$source] = $sourceId;
          }
        }
        else {
          $sourceId = 0;
        }
        
        $sql  = "INSERT INTO `results` ";
        $sql .= "(`testcase_id`, `revision`, `format`, `useragent_id`, ";
        $sql .= "`source_id`, `source_useragent_id`, ";
        $sql .= "`result`, `ignore`, `modified`) ";
        $sql .= "VALUES ('{$newTestCaseId}', '{$revision}', '{$format}', '{$userAgentId}', ";
        $sql .= "'{$sourceId}', '{$sourceUserAgentId}', ";
        $sql .= "'{$result}', '{$ignore}', '{$modified}') ";
        
        $this->mNewDB->query($sql);
        
        $newResultId = $this->mNewDB->lastInsertId();
        $newResultIds[$oldResultId] = $newResultId;
        $newIgnores[$newResultId] = $ignore;
        $newResults[$newResultId] = $result;
      }
    }
    
  }
  
  function translate()
  {
    $this->_copyTable('flags');
    $this->_copyTable('spidertrap');
    $this->_copyTable('useragents');

    $this->_importSpec('CSS21', 'http://www.w3.org/TR/CSS21/', 'CSS 2.1', 'Cascading Style Sheets Level 2 Revision 1');
    $this->_importSpecLinks('sections.dat', 'CSS21');

    $this->_loadOldReferences();
    $this->_loadOldSpecLinks();

    $this->_loadNewSpecLinkIDs('CSS21');
    
    // copy testcases + references + testlinks
    $this->_clearTable('testcases');
    $this->_clearTable('revisions');
    $this->_clearTable('testpages');
    $this->_clearTable('references');
    $this->_clearTable('testlinks');

    $this->_translateTestCases('CSS21_HTML_RC1', 'html4');
    $this->_translateTestCases('CSS21_XHTML_RC1', 'xhtml1');
    
    $this->_translateTestCases('CSS21_HTML_RC2', 'html4');
    $this->_translateTestCases('CSS21_XHTML_RC2', 'xhtml1');
    
    $this->_translateTestCases('CSS21_HTML_RC3', 'html4');
    $this->_translateTestCases('CSS21_XHTML_RC3', 'xhtml1');

    $this->_translateTestCases('CSS21_HTML_RC4', 'html4');
    $this->_translateTestCases('CSS21_XHTML_RC4', 'xhtml1');
    
    $this->_translateTestCases('CSS21_HTML_RC5', 'html4');
    $this->_translateTestCases('CSS21_XHTML_RC5', 'xhtml1');
    
    $this->_importRevisions('testinfo.data_0131', '2011-01-31');

    $this->_importRevisions('testinfo.data_0201', '2011-02-01');
    $this->_importRevisions('testinfo.data_0202', '2011-02-02');
    $this->_importRevisions('testinfo.data_0203', '2011-02-03');
    $this->_importRevisions('testinfo.data_0204', '2011-02-04');
    $this->_importRevisions('testinfo.data_0205', '2011-02-05');

    $this->_importRevisions('testinfo.data_0208', '2011-02-08');

    $this->_importRevisions('testinfo.data_0218', '2011-02-18');

    $this->_importRevisions('testinfo.data_0303', '2011-03-03');
    $this->_importRevisions('testinfo.data_0304', '2011-03-04');

    $this->_importRevisions('testinfo.data_0308', '2011-03-08');

    $this->_importRevisions('testinfo.data_0310', '2011-03-10');
        
    $this->_translateTestCases('CSS21_HTML', 'html4', FALSE);
    $this->_translateTestCases('CSS21_XHTML', 'xhtml1', FALSE);
    
    // copy testsuites
    $this->_translateTestSuite('CSS21_%HTML_RC1', 'CSS21_RC1');
    $this->_translateTestSuite('CSS21_%HTML_RC2', 'CSS21_RC2');
    $this->_translateTestSuite('CSS21_%HTML_RC3', 'CSS21_RC3');
    $this->_translateTestSuite('CSS21_%HTML_RC4', 'CSS21_RC4');
    $this->_translateTestSuite('CSS21_%HTML_RC5', 'CSS21_RC5');
    $this->_translateTestSuite('CSS21_%HTML', 'CSS21');

    // copy results
    $this->_translateResults();

  }
}

$worker = new Translate();

$worker->translate();

?>