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

require_once('lib/HarnessDB.php');

require_once('lib/StatusCache.php');

require_once('modules/testsuite/TestFlags.php');
require_once('modules/testsuite/TestFormat.php');


/**
 * Wrapper class for information about a particular test case 
 * when bound to a given test suite
 */
class TestCase extends HarnessDBEntity
{
  protected $mTestSuite;
  protected $mFlags;
  protected $mURIs;
  protected $mReferences;
  protected $mSpecAnchors;


  static function GetTestCase(TestSuite $testSuite, $testCaseName)
  {
    if ($testCaseName) {
      $db = new TestCase();

      $testCaseName = $db->encode($testCaseName, 'testcases.testcase');
      $testSuiteName = $db->encode($testSuite->getName(), 'suite_tests.test_suite');
      
      $sql  = "SELECT `testcases`.* ";
      $sql .= "FROM `testcases` ";
      $sql .= "LEFT JOIN `suite_tests` ";
      $sql .= "  ON `testcases`.`id` = `suite_tests`.`testcase_id` ";
      $sql .= "  AND `testcases`.`revision` = `suite_tests`.`revision` ";
      $sql .= "WHERE `testcases`.`testcase` = '{$testCaseName}' ";
      $sql .= "  AND `suite_tests`.`test_suite` = '{$testSuiteName}' ";
      $sql .= "LIMIT 1";

      $r = $db->query($sql);
      $data = $r->fetchRow();
      
      if ($data) {
        return new TestCase($testSuite, $data);
      }
    }
    return null;
  }
  
  
  function __construct(TestSuite $testSuite = null, $data = null)
  {
    $this->mTestSuite = $testSuite;
    
    parent::__construct($data);
  }


  /**
   * Load data about a test case by id
   */
  protected function _queryById($testCaseId)
  {
    $testSuiteName = $this->encode($this->mTestSuite->getName(), 'suite_tests.test_suite');
    $testCaseId = intval($testCaseId);
    
    $sql  = "SELECT `testcases`.* ";
    $sql .= "FROM `testcases` ";
    $sql .= "LEFT JOIN `suite_tests` ";
    $sql .= "  ON `testcases`.`id` = `suite_tests`.`testcase_id`";
    $sql .= "  AND `testcases`.`revision` = `suite_tests`.`revision` ";
    $sql .= "WHERE `testcases`.`id` = '{$testCaseId}' ";
    $sql .= "  AND `suite_tests`.`test_suite` = '{$testSuiteName}' ";
    $sql .= "LIMIT 1";

    $r = $this->query($sql);
    $data = $r->fetchRow();

    return $data;
  }


  protected function _loadReferences()
  {
    if ((null == $this->mReferences) && $this->isReferenceTest()) {
      $testCaseId = $this->getId();
      $testSuiteName = $this->encode($this->mTestSuite->getName(), 'refernce_pages.test_suite');
      $revision = $this->encode($this->getRevision());
      
      $sql  = "SELECT * ";
      $sql .= "FROM `references` ";
      $sql .= "LEFT JOIN `reference_pages` ";
      $sql .= "  ON `references`.`testcase_id` = `reference_pages`.`testcase_id` ";
      $sql .= "  AND `references`.`reference` = `reference_pages`.`reference` ";
      $sql .= "WHERE `references`.`testcase_id` = '{$testCaseId}' ";
      $sql .= "  AND `test_suite` = '{$testSuiteName}' ";
      $sql .= "  AND `revision` = '{$revision}' ";
      $sql .= "ORDER BY `group`, `sequence` ";
      
      $suiteFormats = $this->mTestSuite->getFormats();
      
      $r = $this->query($sql);
      while ($referenceData = $r->fetchRow()) {
        $formatName = $referenceData['format'];
        if (array_key_exists(strtolower($formatName), $suiteFormats)) {
          $groupIndex = intval($referenceData['group']);
          $this->mReferences[$groupIndex][] = $referenceData;
        }
      }
    }
    return (null != $this->mReferences);
  }
  
  
  protected function _loadURIs()
  {
    if (null == $this->mURIs) {
      $this->mURIs = array();
      
      $testCaseId = $this->getId();
      $testSuiteName = $this->encode($this->mTestSuite->getName(), 'test_pages.test_suite');
      
      $sql  = "SELECT `format`, `uri` ";
      $sql .= "FROM `test_pages` ";
      $sql .= "WHERE `testcase_id` = '{$testCaseId}' ";
      $sql .= "  AND `test_suite` = '{$testSuiteName}' ";
      
      $suiteFormats = $this->mTestSuite->getFormats();

      $r = $this->query($sql);
      while ($uriData = $r->fetchRow()) {
        $formatName = $uriData['format'];
        
        if (array_key_exists(strtolower($formatName), $suiteFormats)) {
          $uri = $uriData['uri'];
        
          $this->mURIs[strtolower($formatName)] = $uri;
        }
      }
    }
    return $this->mURIs;
  }
  
  
  protected function _loadHelpLinks()
  {
    if (null == $this->mSpecAnchors) {
      $this->mSpecAnchors = array();
      
      $testCaseId = $this->getId();
      $revision = $this->encode($this->getRevision());
      $testSuiteName = $this->encode($this->mTestSuite->getName(), 'suite_tests.test_suite');
      
      $sql  = "SELECT * ";
      $sql .= "FROM `test_help_links` ";
      $sql .= "INNER JOIN `suite_tests` ";
      $sql .= "  ON `test_help_links`.`testcase_id` = `suite_tests`.`testcase_id` ";
      $sql .= "  AND `suite_tests`.`revision` = `test_help_links`.`revision` ";
      $sql .= "WHERE `test_help_links`.`testcase_id` = {$testCaseId} ";
      $sql .= "  AND `suite_tests`.`test_suite` = '{$testSuiteName}' ";
      $sql .= "ORDER BY `sequence` ";
      $r = $this->query($sql);
      
      while ($data = $r->fetchRow()) {
        $uri = $data['uri'];
        $spec = Specification::GetSpecificationByURI($uri);
        if ($spec && $this->mTestSuite->hasSpecification($spec)) {
          $anchors = SpecificationAnchor::GetAnchorsForURI($spec, $uri);
          if ($anchors) {
            $this->mSpecAnchors[] = $anchors;
          }
        }
      }
    }
  }


  /**
   * Store test result
   */
  function submitResult(UserAgent $userAgent, User $user, TestFormat $format, $result, $passCount, $failCount)
  {
    if ($this->isValid() && $userAgent->getId()) {
      $sql  = "INSERT INTO `results` ";
      $sql .= "(`testcase_id`, `revision`, `format`, `user_agent_id`, `user_id`, `user_user_agent_id`, `result`, `pass_count`, `fail_count`) ";
      $sql .= "VALUES (";
      $sql .= "'" . $this->getId() . "',";
      $sql .= "'" . $this->encode($this->getRevision(), 'results.revision') . "',";
      $sql .= "'" . $this->encode($format->getName(), 'results.format') . "', ";
      $sql .= "'" . $userAgent->getId() . "',";
      $sql .= "'" . $user->getId() . "',";
      $sql .= "'" . $userAgent->getActualUA()->getId() . "',";
      $sql .= "'" . $this->encode(strtolower($result)) . "',";
      $sql .= intval($passCount) . ", " . intval($failCount);
      $sql .= ")";
      
      $r = $this->query($sql);

      if (! $r->succeeded()) {
        $msg = 'Operation Failed. We were unable to record your submission.';
        trigger_error($msg, E_USER_ERROR);
      }
      
      StatusCache::FlushAllResults(); // need to flush cache for all sections in all test suites that include this test, faster to flush them all
    }
    return FALSE;
  }


  function isValid()
  {
    return ($this->mInfo && array_key_exists('id', $this->mInfo) && (0 < $this->mInfo['id']));
  }


  function getId()
  {
    if ($this->isValid()) {
      return intval($this->mInfo['id']);
    }
    return FALSE;
  }
  
  
  function getFormats()
  {
    if ($this->isValid()) {
      if ($this->_loadURIs()) {
        $suiteFormats = $this->mTestSuite->getFormats();

        $formats = array();
        foreach ($suiteFormats as $formatName => $format) {
          if (array_key_exists($formatName, $this->mURIs)) {
            $formats[$formatName] = $format;
          }
        }
        return $formats;
      }
    }
    return FALSE;
  }
  

  function getURI(TestFormat $format)
  {
    if ($this->isValid()) {
      $formatName = strtolower($format->getName());
      if ($this->_loadURIs() && array_key_exists($formatName, $this->mURIs)) {
        return $this->_CombinePath($this->mTestSuite->getURI(), $this->mURIs[$formatName]);
      }
    }
    return FALSE;
  }
  

  function getName()
  {
    return $this->_getStrValue('testcase');
  }


  function getRevision()
  {
    return $this->_getStrValue('revision');
  }


  function getTitle()
  {
    return $this->_getStrValue('title');
  }


  function getAssertion()
  {
    return $this->_getStrValue('assertion');
  }


  function isReferenceTest()
  {
    $flags = $this->getFlags();
    if ($flags) {
      return $flags->hasFlag('reftest');
    }
    return FALSE;
  }


  /**
   * Get names of References
   *
   * @return FALSE|array
   */
  function getReferenceNames(TestFormat $format)
  {
    if ($this->isValid()) {
      if ($this->_loadReferences()) {
        $formatName = $format->getName();
        $references = array();
        foreach ($this->mReferences as $referenceGroup) {
          $group = array();
          foreach ($referenceGroup as $referenceData) {
            if (0 == strcasecmp($referenceData['format'], $formatName)) {
              $group[] = $referenceData['reference'];
            }
          }
          if ($group) {
            $references[] = $group;
          }
        }
        if (0 < count($references)) {
          return $references;
        }
      }
    }
    return array();
  }
  

  function getReferenceURI($refName, TestFormat $format)
  {
    if ($this->isValid() && ($refName)) {
      if ($this->_loadReferences()) {
        $formatName = $format->getName();
        foreach ($this->mReferences as $referenceGroup) {
          foreach ($referenceGroup as $referenceData) {
            if ((0 == strcasecmp($referenceData['reference'], $refName)) && 
                (0 == strcasecmp($referenceData['format'], $formatName))) {
              return $this->_CombinePath($this->mTestSuite->getURI(), $referenceData['uri']);
            }
          }
        }
      }
    }
    return FALSE;
  }


  /**
   * Get type of reference
   * @param int $refId
   * @return string ('==' or '!=')
   */
  function getReferenceType($refName, TestFormat $format)
  {
    if ($this->isValid() && ($refName)) {
      if ($this->_loadReferences()) {
        $formatName = $format->getName();
        foreach ($this->mReferences as $referenceGroup) {
          foreach ($referenceGroup as $referenceData) {
            if ((0 == strcasecmp($referenceData['reference'], $refName)) && 
                (0 == strcasecmp($referenceData['format'], $formatName))) {
              return $referenceData['type'];
            }
          }
        }
      }
    }
    return FALSE;
  }
  
  
  function getFlags()
  {
    if ($this->isValid()) {
      if (! $this->mFlags) {
        $flagNames = $this->_ExplodeTrimAndFilter(',', $this->_getStrValue('flags'));
        $this->mFlags = new TestFlags($flagNames);
      }
      return $this->mFlags;
    }
    return FALSE;
  }
  
  
  function hasFlag($flag)
  {
    $flags = $this->getFlags();
    if ($flags) {
      return $flags->hasFlag($flag->getName());
    }
    return FALSE;
  }
  

  function isOptional(TestFlags $optionalFlags)
  {
    foreach ($optionalFlags->getFlags() as $flagName => $flag) {
      if ($this->hasFlag($flag)) {
        return TRUE;
      }
    }
    return FALSE;
  }
  
  
  function getSpecAnchors()
  {
    if ($this->isValid()) {
      $this->_loadHelpLinks();
      return $this->mSpecAnchors;
    }
    return FALSE;
  }  
  
  
  function getCredits()
  {
    return $this->_getStrValue('credits');
  }

}

?>