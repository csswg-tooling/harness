<?php
/*******************************************************************************
 *
 *  Copyright © 2010-2011 Hewlett-Packard Development Company, L.P. 
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

require_once("lib/CmdLineWorker.php");
require_once("lib/Page.php");
require_once("lib/Format.php");
require_once("lib/TestSuite.php");

/**
 * Import test case data from manifest file
 *
 * Safe to run multiple times to update existing tests
 */
class TestCaseImport extends CmdLineWorker
{  
  protected $mTestCaseRevisions;
  protected $mSpecLinkIds;
  protected $mSpecLinkParentIds;
  protected $mTestCaseIsActive;

  function __construct() 
  {
    parent::__construct();

    $this->mTestCaseIsActive = array();
  }


  function usage()
  {
    echo "USAGE: php TestCaseImport.php manifestfile testsuite\n";
  }
  
  
  protected function _addTestCase($testCaseName, $testCaseId, $testCaseData)
  {
    parent::_addTestCase($testCaseName, $testCaseId, $testCaseData);
    
    $this->mTestCaseRevisions[$testCaseId] = intval($testCaseData['last_revision']);
  }

  protected function _loadTestCases($testSuiteName = '')
  {
    unset ($this->mTestCaseRevisions);
    $this->mTestCaseRevisions = array();
    
    return parent::_loadTestCases($testSuiteName);
  }
  
  
  protected function _loadSpecLinkIDs($spec)
  {
    $this->mSpecLinkIds = array();
    $this->mSpecLinkParentIds = array();
    
    $spec = $this->encode($spec, SPECLINKS_MAX_SPEC);
    
    $sql  = "SELECT * ";
    $sql .= "FROM `speclinks` ";
    $sql .= "WHERE `spec` = '{$spec}' ";
    
    $r = $this->query($sql);
    while ($specLinkData = $r->fetchRow()) {
      $specLinkId = intval($specLinkData['id']);
      $parentId   = intval($specLinkData['parent_id']);
      $uri        = $specLinkData['uri'];
      
      $this->mSpecLinkIds[$uri] = $specLinkId;
      $this->mSpecLinkParentIds[$specLinkId] = $parentId;
    }
  }
  
  
  protected function _addSpecLink($specLinkId, $specLinkURI)
  {
    $this->mSpecLinkIds[$specLinkURI] = $specLinkId;
  }
  
  
  protected function _getSpecLinkId($specURI)
  {
    if (array_key_exists($specURI, $this->mSpecLinkIds)) {
      return $this->mSpecLinkIds[$specURI];
    }
    return FALSE;
  }
  
  
  protected function _getSpecLinkParentId($specLinkId)
  {
    if (array_key_exists($specLinkId, $this->mSpecLinkParentIds)) {
      return $this->mSpecLinkParentIds[$specLinkId];
    }
    return FALSE;
  }
  
  
  function import($manifest, $testSuiteName)
  {
    echo "Loading testcases from: {$testSuiteName}\n";
    $this->_loadTestCases($testSuiteName);
    
    $testSuite = new TestSuite($testSuiteName);
    $formats = Format::GetFormatsFor($testSuite);
    
    $this->_loadSpecLinkIDs($testSuite->getSpecName());
    
    $testSuiteName = $this->encode($testSuiteName, SUITETESTS_MAX_TESTSUITE);

    echo "Reading source file: {$manifest}\n";
    $data = file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ((! $data) || (count($data) < 2)) {
      die("missing or empty manifest file\n");
    }
    
    $now = $this->getNow(); // set all new revision dates to time import started
    
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
      
      $testCaseId = $this->_getTestCaseId($testCaseName);
      $title      = Page::Decode($title);
      $assertion  = Page::Decode($assertion);
      $credits    = Page::Decode($credits);
      
      $flagArray = $this->_explodeTrimAndFilter(',', $flagString);
      if ($references) {
        $flagArray[] = 'reftest';
      }
      $flagString = implode(',', $flagArray);

      $testCaseName   = $this->encode($testCaseName, TESTCASES_MAX_TESTCASE);
      $title      = $this->encode($title, TESTCASES_MAX_TITLE);
      $assertion  = $this->encode($assertion, TESTCASES_MAX_ASSERTION);
      $credits    = $this->encode($credits, TESTCASES_MAX_CREDITS);
      $flagString = $this->encode($flagString);
      $revision   = intval($revision);

      // testcases
      if (0 < $testCaseId) {
        $sql  = "UPDATE `testcases` ";
        $sql .= "SET `last_revision` = '{$revision}', ";
        $sql .= "`title` = '{$title}', ";
        $sql .= "`flags` = '{$flagString}', ";
        $sql .= "`assertion` = '{$assertion}', ";
        $sql .= "`credits` = '{$credits}' ";
        $sql .= "WHERE `id` = '{$testCaseId}' ";
        
        $r = $this->query($sql);
        if (! $r->succeeded()) {
          die("Update failed {$testCaseName}:{$testCaseId}\n");
        }
        
        if ($this->mTestCaseRevisions[$testCaseId] != $revision) {
          $sql  = "INSERT INTO `revisions` ";
          $sql .= "(`testcase_id`, `revision`, `date`) ";
          $sql .= "VALUES ('{$testCaseId}', '{$revision}', '{$now}') ";

          $this->query($sql);
        }
      }
      else {
        $sql  = "INSERT INTO `testcases` (`testcase`, `last_revision`, `title`, `flags`, `assertion`, `credits`) ";
        $sql .= "VALUES ('{$testCaseName}', '{$revision}', '{$title}', '{$flagString}', '{$assertion}', '{$credits}');";
        
        $r = $this->query($sql);
        
        if ($r->succeeded()) {
          $testCaseId = $this->lastInsertId();
          echo "Inserted {$testCaseName}:{$testCaseId}\n";
        }
        else {
          die("Insert failed: {$testCaseName}\n");
        }
        
        $sql  = "INSERT INTO `revisions` ";
        $sql .= "(`testcase_id`, `revision`, `date`) ";
        $sql .= "VALUES ('{$testCaseId}', '{$revision}', '{$now}') ";

        $this->query($sql);
      }

      // verify flags
      $sql  = "SELECT `flags` ";
      $sql .= "FROM `testcases` ";
      $sql .= "WHERE `id` = '{$testCaseId}' ";
      
      $r = $this->query($sql);
      $storedFlags = $r->fetchField(0);
      
      $dbFlagArray = $this->_explodeTrimAndFilter(',', $storedFlags);

      $diff = array_diff($flagArray, $dbFlagArray);
      if (0 < count($diff)) {
        foreach ($diff as $flag) {
          echo "Flag not stored in database: '{$flag}'\n";
        }
        die("Need to update database schema to support flag(s).\n");
      }

      // testpages
      $sql  = "DELETE FROM `testpages` ";
      $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
      
      $this->query($sql);

      foreach ($formats as $format) {
        if ($format->validForFlags($flagArray)) {
          $uri = $this->_combinePath($format->getPath(), $testCaseName, $format->getExtension());
          
          $formatName = $this->encode($format->getName());
          $uri = $this->encode($uri, TESTPAGES_MAX_URI);
          
          $sql  = "INSERT INTO `testpages` (`testcase_id`, `format`, `uri`) ";
          $sql .= "VALUES ('{$testCaseId}', '{$formatName}', '{$uri}') ";
          
          $this->query($sql);
        }
      }

      // references
      $sql  = "DELETE FROM `references` ";
      $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
      
      $this->query($sql);

      $referenceArray = $this->_explodeTrimAndFilter(',', $references);
            
      foreach ($referenceArray as $referencePath) {
      
        if ('!' === $referencePath[0]) {
          $referencePath = substr($referencePath, 1);
          $referenceType = '!=';
        }
        else {
          $referenceType = '==';
        }
        $referenceName = $this->_getFileName($referencePath);
        
        $referenceName = $this->encode($referenceName, REFERENCES_MAX_REFERENCE);
        $referenceType = $this->encode($referenceType);

        foreach ($formats as $format) {
          if ($format->validForFlags($flagArray)) {
            $referenceURI = $this->_combinePath($format->getPath(), $referencePath, $format->getExtension());

            $formatName = $this->encode($format->getName());
            $referenceURI = $this->encode($referenceURI, REFERENCES_MAX_URI);

            $sql  = "INSERT INTO `references` ";
            $sql .= "(`testcase_id`, `format`, `reference`, `uri`, `type`) ";
            $sql .= "VALUES ('{$testCaseId}', '{$formatName}', '{$referenceName}', '{$referenceURI}', '{$referenceType}') ";
            
            $this->query($sql);
          }
        }
      }

      // update links
      $sql  = "DELETE FROM `testlinks` ";
      $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
      
      $this->query($sql);
      
      $linkArray = $this->_explodeTrimAndFilter(',', $links);
      $usedSpecLinkIds = array();
      foreach ($linkArray as $specLinkURI) {
        $specLinkId = $this->_getSpecLinkId($specLinkURI);
        
        if (FALSE === $specLinkId) {
          echo "Adding new spec link: '{$specLinkURI}'\n";
          $specLinkURI = $this->encode($specLinkURI, SPECLINKS_MAX_URI);
          
          $sql  = "INSERT INTO `speclinks` ";
          $sql .= "(`uri`) ";
          $sql .= "VALUES ('{$specLinkURI}') ";
          
          $this->query($sql);
        
          $specLinkId = $this->lastInsertId();
          $this->_addSpecLink($specLinkId, $specLinkURI);
        }
        
        $sql  = "INSERT INTO `testlinks` ";
        $sql .= "(`testcase_id`, `speclink_id`, `group`) ";
        $sql .= "VALUES ('{$testCaseId}', '{$specLinkId}', 0) ";
        
        $this->query($sql);
        
        $usedSpecLinkIds[$specLinkId] = TRUE;
      }
      
      // add parent spec links for grouping
      foreach ($linkArray as $specLinkURI) {
        $specLinkId = $this->_getSpecLinkId($specLinkURI);
        
        while ($specLinkId = $this->_getSpecLinkParentId($specLinkId)) {
          if (! isset($usedSpecLinkIds[$specLinkId])) {
            $sql  = "INSERT INTO `testlinks` ";
            $sql .= "(`testcase_id`, `speclink_id`, `group`) ";
            $sql .= "VALUES ('{$testCaseId}', '{$specLinkId}', 1) ";
            
            $this->query($sql);
            $usedSpecLinkIds[$specLinkId] = TRUE;
          }
          else {
            break;
          }
        }
      }

      // suitetests
      $sql  = "INSERT INTO `suitetests` ";
      $sql .= "(`testsuite`, `testcase_id`, `revision`) ";
      $sql .= "VALUES ('{$testSuiteName}', '{$testCaseId}', '{$revision}') ";
      $sql .= "ON DUPLICATE KEY UPDATE `revision` = '{$revision}' ";
      
      $this->query($sql);
      
      $this->mTestCaseIsActive[$testCaseId] = TRUE;
      
    }
    
    // delete old tests
    foreach ($this->mTestCaseIds as $testCaseName => $testCaseId) {
      if (! isset($this->mTestCaseIsActive[$testCaseId])) {
        $sql  = "DELETE FROM `suitetests` ";
        $sql .= "WHERE `testsuite` = '{$testSuiteName}' ";
        $sql .= "AND `testcase_id` = '{$testCaseId}' ";
        
        $this->query($sql);
        echo "Deactivated {$testCaseName}:{$testCaseId}\n";
      }
    }
  }
}

$worker = new TestCaseImport();

$manifestPath   = $worker->_getArg(1);
$testSuiteName  = $worker->_getArg(2);

if ($manifestPath && $testSuiteName) {
  $worker->import($manifestPath, $testSuiteName);
}
else {
  $worker->usage();
}

?>