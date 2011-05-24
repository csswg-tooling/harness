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

require_once('lib/CmdLineWorker.php');
require_once('lib/Page.php');
require_once('lib/Format.php');
require_once('lib/TestSuite.php');
require_once('lib/NormalizedTest.php');

/**
 * Import test case data from manifest file
 *
 * Safe to run multiple times to update existing tests
 */
class TestCaseImport extends CmdLineWorker
{  
  protected $mTestCaseRevisionInSuite;
  protected $mTestCaseRevisions;
  protected $mSpecLinkIds;
  protected $mSpecLinkParentIds;
  protected $mTestCaseIsActive;
  protected $mNewSuitePath;
  protected $mOldSuitePath;

  function __construct() 
  {
    parent::__construct();

    $this->mTestCaseIsActive = array();
    
    $this->mOldSuitePath = $this->_getArg(4);
    if ($this->mOldSuitePath) {
      $this->mNewSuitePath = $this->_getArg(3);
    }
  }


  function usage()
  {
    echo "USAGE: php TestCaseImport.php manifestFile testSuite [newSuitePath oldSuitePath]\n";
  }
  
  
  protected function _addTestCase($testCaseName, $testCaseId, $testCaseData)
  {
    parent::_addTestCase($testCaseName, $testCaseId, $testCaseData);
    
    $this->mTestCaseRevisionInSuite[$testCaseId] = $testCaseData['last_revision'];
  }

  protected function _loadTestCases($testSuiteName = '')
  {
    unset ($this->mTestCaseRevisionInSuite);
    $this->mTestCaseRevisionInSuite = array();
    
    return parent::_loadTestCases($testSuiteName);
  }
  
  
  protected function _loadTestCaseRevisions($testSuiteName)
  {
    $testSuiteName = $this->encode($testSuiteName, SUITETESTS_MAX_TESTSUITE);
    
    $sql  = "SELECT `testcase_id`, `revision` ";
    $sql .= "FROM `suitetests` ";
    $sql .= "WHERE `testsuite` = '{$testSuiteName}' ";

    $r = $this->query($sql);
    while ($testCaseData = $r->fetchRow()) {
      $testCaseId = intval($testCaseData['testcase_id']);
      
      $this->mTestCaseRevisionInSuite[$testCaseId] = $testCaseData['revision'];
    }
    
    $this->mTestCaseRevisions = array();
    
    $sql  = "SELECT `testcase_id`, `revision` ";
    $sql .= "FROM `revisions` ";
    $sql .= "ORDER BY `testcase_id`, `date` ";
    
    $r = $this->query($sql);
    while ($revisionData = $r->fetchRow()) {
      $testCaseId = intval($revisionData['testcase_id']);
      $revision   = $revisionData['revision'];
      
      if (array_key_exists($testCaseId, $this->mTestCaseRevisions) && 
          in_array($revision, $this->mTestCaseRevisions[$testCaseId])) {
        die("Multiple entries for revision {$revision} for {$testCaseId}\n");
      }
      $this->mTestCaseRevisions[$testCaseId][] = $revision;
    }
  }
  
  
  protected function _loadSpecLinkIds($spec)
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
    $testSuite = new TestSuite($testSuiteName);
    if (! $testSuite->isValid()) {
      exit;
    }
    $formats = Format::GetFormatsFor($testSuite);
    
    echo "Loading testcases\n";
    $this->_loadTestCases();
    $this->_loadTestCaseRevisions($testSuiteName);
    
    $this->_loadSpecLinkIds($testSuite->getSpecName());
    
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

      $referenceArray = $this->_explodeTrimAndFilter(',', $references);
            
      $title        = $this->encode($title, TESTCASES_MAX_TITLE);
      $assertion    = $this->encode($assertion, TESTCASES_MAX_ASSERTION);
      $credits      = $this->encode($credits, TESTCASES_MAX_CREDITS);
      $flagString   = $this->encode($flagString);

      // testcases
      if (0 < $testCaseId) {  // we already have this testcase, update as needed
      
        $newRevision = FALSE;
        if ($this->mTestCaseRevisionInSuite[$testCaseId] != $revision) {  // not the same revision
          if (in_array($revision, $this->mTestCaseRevisions[$testCaseId])) {
            echo "Set {$testCaseName}:{$testCaseId} to existing revision {$revision}\n";
          }
          else {
            // this is a new revision
            $newRevision = $revision;

            // if available, compare revisions
            $compared = FALSE;
            $matches = TRUE;
            if ($this->mNewSuitePath) {
              foreach ($formats as $format) {
                if ($matches && $format->validForFlags($flagArray)) {
                  $compared = TRUE;
                  $testPath = $this->_combinePath($format->getPath(), $testCaseName, $format->getExtension());
                  
                  // XXX use stored url path for old test
                      
                  $newTest = new NormalizedTest($this->_combinePath($this->mNewSuitePath, $testPath));
                  $oldTest = new NormalizedTest($this->_combinePath($this->mOldSuitePath, $testPath));
                  
                  $matches = ($newTest->getContent() == $oldTest->getContent());
                  
                  // compare references as well if present
                  foreach ($referenceArray as $referencePath) {
                    if ($matches) {
                      if ('!' == $referencePath[0]) {
                        $referencePath = $substr($referencePath, 1);
                      }
                      $referencePath = $this->_combinePath($format->getPath(), $referencePath, $format->getExtension());

                      $newReference = new NormalizedTest($this->_combinePath($this->mNewSuitePath, $referencePath));
                      $oldReference = new NormalizedTest($this->_combinePath($this->mOldSuitePath, $referencePath));
                      
                      $matches = ($newReference->getContent() == $oldReference->getContent());
                    }
                  }
                  // XXX and other dependent files
                }
              }
            }
            
            if ($compared && $matches) {
              $revisions = $this->mTestCaseRevisions[$testCaseId];
              $lastRevision = $revisions[count($revisions) - 1];
              
              $revisionSql = $this->encode($revision, REVISIONS_MAX_REVISION);
              $lastRevisionSql = $this->encode($lastRevision, REVISIONS_MAX_EQUAL_REVISION);
              
              $sql  = "INSERT INTO `revisions` ";
              $sql .= "(`testcase_id`, `revision`, `equal_revision`, `date`) ";
              $sql .= "VALUES ('{$testCaseId}', '{$revisionSql}', '{$lastRevisionSql}', '{$now}') ";

              $this->query($sql);

              echo "Updated {$testCaseName}:{$testCaseId} to revision {$revision} = {$lastRevision}\n";

              $prevRevisionIndex = array_search($this->mTestCaseRevisionInSuite[$testCaseId], $revisions);
              
              for ($index = $prevRevisionIndex; $index < (count($revisions) - 1); $index++) { // if revisions between last and new, chain equality
                echo "-- Set exising revision {$newRevision} equal to {$oldRevision}\n";

                $newRevision = $this->encode($revisions[$index + 1], REVISIONS_MAX_REVISION);
                $oldRevision = $this->encode($revisions[$index], REVISIONS_MAX_EQUAL_REVISION);
                
                $sql  = "UPDATE `revisions` ";
                $sql .= "SET `equal_revision` = '{$oldRevision}' ";
                $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
                $sql .= "AND `revision` = '{$newRevision}' ";
                
                $this->query($sql);
              }
            }
            else {
              $revisionSql = $this->encode($revision, REVISIONS_MAX_REVISION);
              $sql  = "INSERT INTO `revisions` ";
              $sql .= "(`testcase_id`, `revision`, `date`) ";
              $sql .= "VALUES ('{$testCaseId}', '{$revisionSql}', '{$now}') ";

              $this->query($sql);
              
              echo "Updated {$testCaseName}:{$testCaseId} to revision {$revision}\n";
            }
          }
        }

        $sql  = "UPDATE `testcases` ";
        $sql .= "SET `title` = '{$title}', ";
        if ($newRevision) {
          $sql .= "`last_revision` = '{$newRevision}', ";
        }
        $sql .= "`flags` = '{$flagString}', ";
        $sql .= "`assertion` = '{$assertion}', ";
        $sql .= "`credits` = '{$credits}' ";
        $sql .= "WHERE `id` = '{$testCaseId}' ";
        
        $r = $this->query($sql);
        if (! $r->succeeded()) {
          die("Update failed {$testCaseName}:{$testCaseId}\n");
        }
      }
      else {
        $testCaseNameSql = $this->encode($testCaseName, TESTCASES_MAX_TESTCASE);
        $revisionSql = $this->encode($revision, TESTCASES_MAX_LAST_REVISION);
        
        $sql  = "INSERT INTO `testcases` (`testcase`, `last_revision`, `title`, `flags`, `assertion`, `credits`) ";
        $sql .= "VALUES ('{$testCaseNameSql}', '{$revisionSql}', '{$title}', '{$flagString}', '{$assertion}', '{$credits}');";
        
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
        $sql .= "VALUES ('{$testCaseId}', '{$revisionSql}', '{$now}') ";

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
      // XXX testcase may live in multiple suites - other suites may have more formats...
      
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
      // XXX formats? see above
      
      $this->query($sql);

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
      // XXX only delete links for spec tested from this suite
      
      $this->query($sql);
      
      $linkArray = $this->_explodeTrimAndFilter(',', $links);
      if (0 == count($linkArray)) {
        $this->_warning("Test {$testCaseName} does not have any spec links");
      }
      $usedSpecLinkIds = array();
      $sequence = -1;
      foreach ($linkArray as $specLinkURI) {
        $sequence++;
        $specLinkId = $this->_getSpecLinkId($specLinkURI);
        
        if (FALSE === $specLinkId) {
          echo "Adding new spec link: '{$specLinkURI}'\n";
          $specLinkURI = $this->encode($specLinkURI, SPECLINKS_MAX_URI);
          $spec = $this->encode($testSuite->getSpecName(), SPECLINKS_MAX_SPEC);

          
          $sql  = "INSERT INTO `speclinks` ";
          $sql .= "(`spec`, `uri`) ";
          $sql .= "VALUES ('{$spec}', '{$specLinkURI}') ";
          
          $this->query($sql);
        
          $specLinkId = $this->lastInsertId();
          $this->_addSpecLink($specLinkId, $specLinkURI);
        }
        
        $sql  = "INSERT INTO `testlinks` ";
        $sql .= "(`testcase_id`, `speclink_id`, `sequence`, `group`) ";
        $sql .= "VALUES ('{$testCaseId}', '{$specLinkId}', '{$sequence}', 0) ";
        
        $this->query($sql);
        
        $usedSpecLinkIds[$specLinkId] = TRUE;
      }
      
      // add parent spec links for grouping
      $sequence = -1;
      foreach ($linkArray as $specLinkURI) {
        $sequence++;
        $specLinkId = $this->_getSpecLinkId($specLinkURI);
        
        while ($specLinkId = $this->_getSpecLinkParentId($specLinkId)) {
          if (! isset($usedSpecLinkIds[$specLinkId])) {
            $sql  = "INSERT INTO `testlinks` ";
            $sql .= "(`testcase_id`, `speclink_id`, `sequence`, `group`) ";
            $sql .= "VALUES ('{$testCaseId}', '{$specLinkId}', '{$sequence}', 1) ";
            
            $this->query($sql);
            $usedSpecLinkIds[$specLinkId] = TRUE;
          }
          else {
            break;
          }
        }
      }

      // suitetests
      $revisionSql = $this->encode($revision, SUITETESTS_MAX_REVISION);
      $sql  = "INSERT INTO `suitetests` ";
      $sql .= "(`testsuite`, `testcase_id`, `revision`) ";
      $sql .= "VALUES ('{$testSuiteName}', '{$testCaseId}', '{$revisionSql}') ";
      $sql .= "ON DUPLICATE KEY UPDATE `revision` = '{$revisionSql}' ";
      
      $this->query($sql);
      
      $this->mTestCaseIsActive[$testCaseId] = TRUE;
      
    }
    
    // delete old tests
    echo "Loading testcases from: {$testSuiteName}\n";
    $this->_loadTestCases($testSuiteName);

    foreach ($this->mTestCaseIds as $testCaseName => $testCaseId) {
      if (! isset($this->mTestCaseIsActive[$testCaseId])) {
        $sql  = "DELETE FROM `suitetests` ";
        $sql .= "WHERE `testsuite` = '{$testSuiteName}' ";
        $sql .= "AND `testcase_id` = '{$testCaseId}' ";
        
        $this->query($sql);
        echo "Deactivated {$testCaseName}:{$testCaseId}\n";
      }
    }
    
    // update test suite date
    $sql  = "UPDATE `testsuites` ";
    $sql .= "SET `date` = '{$now}' ";
    $sql .= "WHERE `testsuite` = '{$testSuiteName}' ";
    $this->query($sql);
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