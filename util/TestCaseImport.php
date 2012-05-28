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

require_once('lib/HarnessCmdLineWorker.php');
require_once('core/Page.php');
require_once('lib/Format.php');
require_once('lib/Flags.php');
require_once('lib/TestSuite.php');
require_once('lib/NormalizedTest.php');
require_once('lib/StatusCache.php');

/**
 * Import test case data from manifest file
 *
 * Safe to run multiple times to update existing tests
 */
class TestCaseImport extends HarnessCmdLineWorker
{  
  protected $mTestCaseRevisionInSuite;
  protected $mTestCaseRevisions;
  protected $mSectionIds;
  protected $mSectionParentIds;
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
    $testSuiteName = $this->encode($testSuiteName, 'suitetests.testsuite');
    
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
  
  
  protected function _loadSectionIds($spec)
  {
    $this->mSectionIds = array();
    $this->mSectionParentIds = array();
    
    $spec = $this->encode($spec, 'sections.spec');
    
    $sql  = "SELECT * ";
    $sql .= "FROM `sections` ";
    $sql .= "WHERE `spec` = '{$spec}' ";
    
    $r = $this->query($sql);
    while ($sectionData = $r->fetchRow()) {
      $sectionId  = intval($sectionData['id']);
      $parentId   = intval($sectionData['parent_id']);
      $uri        = $sectionData['uri'];
      
      $this->mSectionIds[$uri] = $sectionId;
      $this->mSectionParentIds[$sectionId] = $parentId;
    }
  }
  
  
  protected function _addSection($sectionId, $sectionURI)
  {
    $this->mSectionIds[$sectionURI] = $sectionId;
  }
  
  
  protected function _getSectionId($specURI)
  {
    if (array_key_exists($specURI, $this->mSectionIds)) {
      return $this->mSectionIds[$specURI];
    }
    return FALSE;
  }
  
  
  protected function _getSectionParentId($sectionId)
  {
    if (array_key_exists($sectionId, $this->mSectionParentIds)) {
      return $this->mSectionParentIds[$sectionId];
    }
    return FALSE;
  }
  
  
  protected function _getTestCasePath($testCaseId, Format $format)
  {
    $formatName = $this->encode($format->getName(), 'testpages.format');
    
    $sql  = "SELECT `uri` ";
    $sql .= "FROM `testpages` ";
    $sql .= "WHERE `testcase_id` = '$testCaseId' AND `format` = '{$formatName}' ";
    
    $r = $this->query($sql);
    return $r->fetchField(0);
  }
  
  
  protected function _getReferencePath($testCaseId, Format $format, $referenceName)
  {
    $formatName = $this->encode($format->getName(), 'references.format');
    $referenceName = $this->encode($referenceName, 'references.reference');
    
    $sql  = "SELECT `uri` ";
    $sql .= "FROM `references` ";
    $sql .= "WHERE `testcase_id` = '$testCaseId' AND `format` = '{$formatName}' ";
    $sql .= "AND `reference` = '{$referenceName}' ";
    
    $r = $this->query($sql);
    return $r->fetchField(0);
  }
  
  
  function import($manifest, $testSuiteName)
  {
    $testSuite = new TestSuite($testSuiteName);
    if (! $testSuite->isValid()) {
      exit;
    }
    $formats = Format::GetFormatsFor($testSuite);
    
    $testsChanged = FALSE;
    
    echo "Loading testcases\n";
    $this->_loadTestCases();
    $this->_loadTestCaseRevisions($testSuiteName);
    
    $this->_loadSectionIds($testSuite->getSpecName());
    
    $testSuiteName = $this->encode($testSuiteName, 'suitetests.testsuite');

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
      list ($testCasePath, $references, $title, $flagString, $links, $revision, $credits, $assertion) = explode("\t", $record);
      
      $testCaseName = basename($testCasePath);
      
      $testCaseId = $this->_getTestCaseId($testCaseName);
      $title      = Page::Decode($title);
      $assertion  = Page::Decode($assertion);
      $credits    = Page::Decode($credits);
      
      $flags = new Flags($flagString);
      if ($references) {
        $flags->addFlag('reftest');
      }
      $flagString = $flags->getFlagString();

      $referenceArray = $this->_ExplodeTrimAndFilter(',', $references);
            
      $title        = $this->encode($title, 'testcases.title');
      $assertion    = $this->encode($assertion, 'testcases.assertion');
      $credits      = $this->encode($credits, 'testcases.credits');
      $flagString   = $this->encode($flagString, 'testcases.flags');

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
                if ($matches && $format->validForFlags($flags)) {
                  $compared = TRUE;
                  $newTestPath = $this->_CombinePath($format->getPath(), $testCaseName, $format->getExtension());
                  $oldTestPath = $this->_getTestCasePath($testCaseId, $format);
                  
                  $newTest = new NormalizedTest($this->_CombinePath($this->mNewSuitePath, $newTestPath));
                  $oldTest = new NormalizedTest($this->_CombinePath($this->mOldSuitePath, $oldTestPath));
                  
                  $matches = ($newTest->getContent() == $oldTest->getContent());
                  
                  // compare references as well if present
                  foreach ($referenceArray as $referencePath) {
                    if ($matches) {
                      if ('!' == $referencePath[0]) {
                        $referencePath = substr($referencePath, 1);
                      }
                      $referenceName = basename($referencePath);
                      $newReferencePath = $this->_CombinePath($format->getPath(), $referencePath, $format->getExtension());
                      $oldReferencePath = $this->_getReferencePath($testCaseId, $format, $referenceName);

                      $newReference = new NormalizedTest($this->_CombinePath($this->mNewSuitePath, $newReferencePath));
                      $oldReference = new NormalizedTest($this->_CombinePath($this->mOldSuitePath, $oldReferencePath));
                      
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
              
              $revisionSql = $this->encode($revision, 'revisions.revision');
              $lastRevisionSql = $this->encode($lastRevision, 'revisions.equal_revision');
              
              $sql  = "INSERT INTO `revisions` ";
              $sql .= "(`testcase_id`, `revision`, `equal_revision`, `date`) ";
              $sql .= "VALUES ('{$testCaseId}', '{$revisionSql}', '{$lastRevisionSql}', '{$now}') ";

              $this->query($sql);

              echo "Updated {$testCaseName}:{$testCaseId} to revision {$revision} = {$lastRevision}\n";

              $prevRevisionIndex = array_search($this->mTestCaseRevisionInSuite[$testCaseId], $revisions);
              
              for ($index = $prevRevisionIndex; $index < (count($revisions) - 1); $index++) { // if revisions between last and new, chain equality
                echo "-- Set exising revision {$newRevision} equal to {$oldRevision}\n";

                $newRevision = $this->encode($revisions[$index + 1], 'revisions.revision');
                $oldRevision = $this->encode($revisions[$index], 'revisions.equal_revision');
                
                $sql  = "UPDATE `revisions` ";
                $sql .= "SET `equal_revision` = '{$oldRevision}' ";
                $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
                $sql .= "AND `revision` = '{$newRevision}' ";
                
                $this->query($sql);
              }
            }
            else {
              $revisionSql = $this->encode($revision, 'revisions.revision');
              $sql  = "INSERT INTO `revisions` ";
              $sql .= "(`testcase_id`, `revision`, `date`) ";
              $sql .= "VALUES ('{$testCaseId}', '{$revisionSql}', '{$now}') ";

              $this->query($sql);
              
              echo "Updated {$testCaseName}:{$testCaseId} to revision {$revision}\n";
              $testsChanged = TRUE;
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
        $testCaseNameSql = $this->encode($testCaseName, 'testcases.testcase');
        $revisionSql = $this->encode($revision, 'testcases.last_revision');
        
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
        $testsChanged = TRUE;
      }

      // testpages - update page paths for this suite's formats, leave other formats alone?
      //           - other suites may share this test in different formats, what about overlap if path changes?
      foreach ($formats as $format) {
        if ($format->validForFlags($flags)) {
          $uri = $this->_CombinePath($format->getPath(), $testCasePath, $format->getExtension());
          
          $formatName = $this->encode($format->getName(), 'testpages.format');
          $uri = $this->encode($uri, 'testpages.uri');
          
          $sql  = "INSERT INTO `testpages` (`testcase_id`, `format`, `uri`) ";
          $sql .= "VALUES ('{$testCaseId}', '{$formatName}', '{$uri}') ";
          $sql .= "ON DUPLICATE KEY UPDATE `uri` = '{$uri}' ";
          
          $this->query($sql);
        }
      }

      // references - update references for this suite's formats - see note about testpages
      foreach ($formats as $format) {
        if ($format->validForFlags($flags)) {
          $formatName = $this->encode($format->getName(), 'references.format');
          $sql  = "DELETE FROM `references` ";
          $sql .= "WHERE `testcase_id` = '{$testCaseId}' AND `format` = '{$formatName}' ";
          $this->query($sql);
        }
      }
      // XXX if test gains a reference, but is otherwise unchanged, should it rev? I think so, but the reference may have an older revision so it may not get caught by the current system
      
      foreach ($referenceArray as $referencePath) {
      
        if ('!' === $referencePath[0]) {
          $referencePath = substr($referencePath, 1);
          $referenceType = '!=';
        }
        else {
          $referenceType = '==';
        }
        $referenceName = basename($referencePath);
        
        $referenceName = $this->encode($referenceName, 'references.reference');
        $referenceType = $this->encode($referenceType);

        foreach ($formats as $format) {
          if ($format->validForFlags($flags)) {
            $referenceURI = $this->_CombinePath($format->getPath(), $referencePath, $format->getExtension());

            $formatName = $this->encode($format->getName(), 'references.format');
            $referenceURI = $this->encode($referenceURI, 'references.uri');

            $sql  = "INSERT INTO `references` ";
            $sql .= "(`testcase_id`, `format`, `reference`, `uri`, `type`) ";
            $sql .= "VALUES ('{$testCaseId}', '{$formatName}', '{$referenceName}', '{$referenceURI}', '{$referenceType}') ";
            
            $this->query($sql);
          }
        }
      }

      // update links
      $sql  = "DELETE FROM `speclinks` ";
      $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
      // XXX only delete links for spec tested from this suite
      
      $this->query($sql);
      
      $linkArray = $this->_ExplodeTrimAndFilter(',', $links);
      if (0 == count($linkArray)) {
        $this->_warning("Test {$testCaseName} does not have any spec links");
      }
      $usedSectionIds = array();
      $sequence = -1;
      foreach ($linkArray as $sectionURI) {
        $sequence++;
        $sectionId = $this->_getSectionId($sectionURI);
        
        if (FALSE === $sectionId) {
          echo "Adding new spec link: '{$sectionURI}'\n";
          $sectionURI = $this->encode($sectionURI, 'sections.uri');
          $spec = $this->encode($testSuite->getSpecName(), 'sections.spec');

          
          $sql  = "INSERT INTO `sections` ";
          $sql .= "(`spec`, `uri`) ";
          $sql .= "VALUES ('{$spec}', '{$sectionURI}') ";
          
          $this->query($sql);
        
          $sectionId = $this->lastInsertId();
          $this->_addSection($sectionId, $sectionURI);
        }
        
        $sql  = "INSERT INTO `speclinks` ";
        $sql .= "(`testcase_id`, `section_id`, `sequence`, `group`) ";
        $sql .= "VALUES ('{$testCaseId}', '{$sectionId}', '{$sequence}', 0) ";
        
        $this->query($sql);
        
        $usedSectionIds[$sectionId] = TRUE;
      }
      
      // add parent spec links for grouping
      $sequence = -1;
      foreach ($linkArray as $sectionURI) {
        $sequence++;
        $sectionId = $this->_getSectionId($sectionURI);
        
        while ($sectionId = $this->_getSectionParentId($sectionId)) {
          if (! isset($usedSectionIds[$sectionId])) {
            $sql  = "INSERT INTO `speclinks` ";
            $sql .= "(`testcase_id`, `section_id`, `sequence`, `group`) ";
            $sql .= "VALUES ('{$testCaseId}', '{$sectionId}', '{$sequence}', 1) ";
            
            $this->query($sql);
            $usedSectionIds[$sectionId] = TRUE;
          }
          else {
            break;
          }
        }
      }

      // suitetests
      $revisionSql = $this->encode($revision, 'suitetests.revision');
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
        $testsChanged = TRUE;
      }
    }
    
    // update test suite date
    $sql  = "UPDATE `testsuites` ";
    $sql .= "SET `date` = '{$now}' ";
    $sql .= "WHERE `testsuite` = '{$testSuiteName}' ";
    $this->query($sql);
    
    if ($testsChanged) {  // if added, removed or modified a test, flush the cache
      StatusCache::FlushResultsForTestSuite($testSuite);
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