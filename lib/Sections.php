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

require_once('lib/HarnessDB.php');

require_once('modules/testsuite/TestSuite.php');

require_once('modules/specification/SpecificationDB.php');
require_once('modules/specification/Specification.php');
require_once('modules/specification/SpecificationAnchor.php');

/**
 * Sorting callback helper
 */
class SectionSorter
{
  protected $mAnchors;
  
  function __construct($anchors)
  {
    $this->mAnchors = $anchors;
  }
  
  function compare($a, $b)
  {
    return SpecificationAnchor::CompareAnchorName($this->mAnchors[$a], $this->mAnchors[$b]);
  }
};


/**
 * Encapsulate data about test groups
 */
class Sections extends HarnessDBConnection
{
  protected $mSections;
  protected $mTestCaseIds;
  protected $mPrimaryAnchors;



  function __construct(TestSuite $testSuite, $loadTestCaseIds = FALSE)
  {
    parent::__construct();

    $startTime = microtime(TRUE);

    $specDBName = SpecificationDBConnection::GetDBName();

    $testSuiteName = $this->encode($testSuite->getName(), 'suitetests.test_suite');
    $specs = $testSuite->getSpecifications();
    $specNames = array();
    foreach ($specs as $spec) {
      $specNames[] = $spec->getName();
    }
    $specSearchSQL = $this->_getMultiSearchSQL("`{$specDBName}`.`spec_anchors`.`spec`", $specNames);
    
    $sql  = "SELECT `{$specDBName}`.`spec_anchors`.*, ";
    $sql .= "SUM(IF(`test_spec_links`.`type`='direct',1,0)) as `link_count` ";
    $sql .= "FROM `test_spec_links` ";
    $sql .= "INNER JOIN (`{$specDBName}`.`spec_anchors`) ";
    $sql .= "  ON `{$specDBName}`.`spec_anchors`.`spec` = `test_spec_links`.`spec` ";
    $sql .= "  AND `{$specDBName}`.`spec_anchors`.`parent_name` = `test_spec_links`.`parent_name` ";
    $sql .= "  AND `{$specDBName}`.`spec_anchors`.`name` = `test_spec_links`.`anchor_name` ";
    $sql .= "WHERE {$specSearchSQL} ";
    $sql .= "  AND `{$specDBName}`.`spec_anchors`.`structure` = 'section' ";
    $sql .= "  AND `test_spec_links`.`test_suite` = '{$testSuiteName}' ";
    $sql .= "GROUP BY `{$specDBName}`.`spec_anchors`.`parent_name`, `{$specDBName}`.`spec_anchors`.`name` ";
    $sql .= "ORDER BY `{$specDBName}`.`spec_anchors`.`spec`, ";
    $sql .= "  `{$specDBName}`.`spec_anchors`.`parent_name`, `{$specDBName}`.`spec_anchors`.`name`, ";
    $sql .= "  `{$specDBName}`.`spec_anchors`.`spec_type` ";

    $r = $this->query($sql);

    $this->mSections = array();
    while ($anchorData = $r->fetchRow()) {
      $specName = $anchorData['spec'];
      $anchorName = $anchorData['name'];
      $parentName = $anchorData['parent_name'];
    
      $anchorData['link_count'] = intval($anchorData['link_count']);

      $this->mSections[$specName][$parentName][$anchorName] = new SpecificationAnchor($anchorData);
    }
    
    foreach ($this->mSections as $specName => $sections) {
      foreach ($sections as $parentName => $subSections) {
        $sorter = new SectionSorter($subSections);
        uksort($subSections, array($sorter, 'compare'));
        $this->mSections[$specName][$parentName] = $subSections;
      }
    }
    
    if ($loadTestCaseIds) {
      $specSearchSQL = $this->_getMultiSearchSQL("`test_spec_links`.`spec`", $specNames);

      $sql  = "SELECT `test_spec_links`.* ";
      $sql .= "FROM `test_spec_links` ";
      $sql .= "INNER JOIN (`testcases`) ";
      $sql .= "  ON `test_spec_links`.`testcase_id` = `testcases`.`id` ";
      $sql .= "WHERE {$specSearchSQL} ";
      $sql .= "  AND `test_spec_links`.`test_suite` = '{$testSuiteName}' ";
      $sql .= "  AND `test_spec_links`.`type` = 'direct' ";
      $sql .= "ORDER BY `testcases`.`testcase` ";

      $r = $this->query($sql);
      
      while ($testCaseData = $r->fetchRow()) {
        $specName = $testCaseData['spec'];
        $parentName = $testCaseData['parent_name'];
        $anchorName = $testCaseData['anchor_name'];
        $testCaseId = intval($testCaseData['testcase_id']);
        
        if ((! array_key_exists($parentName, $this->mSections[$specName])) ||
            (! array_key_exists($anchorName, $this->mSections[$specName][$parentName]))) {  // ensure links to sections
          $anchorName = $parentName;
          $parentName = SpecificationAnchor::GetAnchorParentName($parentName);
        }
        
        $this->mTestCaseIds[$specName][$parentName][$anchorName][] = $testCaseId;
        if (0 == intval($testCaseData['sequence'])) {
          $this->mPrimaryAnchors[$testCaseId] = $this->mSections[$specName][$parentName][$anchorName];
        }
      }
    }
    $this->mQueryTime = (microtime(TRUE) - $startTime);
  }


  function getQueryTime()
  {
    return $this->mQueryTime;
  }


  function getSpecifications()
  {
    $specs = array();
    foreach ($this->mSections as $specName => $sections) {
      $specs[$specName] = Specification::GetSpecificationByName($specName);
    }
    return $specs;
  }
  

/*
  function getSection(SpecificationAnchor $section)
  {
    $specName = $section->getSpecName();
    foreach ($this->mSections[$specName] as $parentName => $subSections) {
      if (array_key_exists($sectionId, $subSections)) {
        return $subSections[$sectionId];
      }
    }
    return FALSE;
  }
*/

  function getSubSectionCount(Specification $spec, SpecificationAnchor $parent = null)
  {
    $specName = $spec->getName();
    $parentName = ($parent ? $parent->getName() : null);
    if ($this->mSections && array_key_exists($specName, $this->mSections) &&
        array_key_exists($parentName, $this->mSections[$specName])) {
      return count($this->mSections[$specName][$parentName]);
    }
    return 0;
  }
  
  
  function getSubSections(Specification $spec, SpecificationAnchor $parent = null)
  {
    $specName = $spec->getName();
    $parentName = ($parent ? $parent->getName() : null);
    if ($this->mSections && array_key_exists($specName, $this->mSections) &&
        array_key_exists($parentName, $this->mSections[$specName])) {
      return $this->mSections[$specName][$parentName];
    }
    return FALSE;
  }
  
  
  function getTestCaseIdsFor(Specification $spec, SpecificationAnchor $section = null, $recursive = FALSE)
  {
    $testCaseIds = array();
    if ($section) {
      $specName = $section->getSpecName();
      $parentName = $section->getParentName();
      $anchorName = $section->getName();

      if ($this->mTestCaseIds && array_key_exists($specName, $this->mTestCaseIds) &&
          array_key_exists($parentName, $this->mTestCaseIds[$specName]) &&
          array_key_exists($anchorName, $this->mTestCaseIds[$specName][$parentName])) {
        $testCaseIds = array_unique($this->mTestCaseIds[$specName][$parentName][$anchorName]);
      }
    }
    if ($recursive) {
      $subSections = $this->getSubSections($spec, $section);
      if ($subSections) {
        foreach ($subSections as $subSection) {
          $subSectionTestIds = $this->getTestCaseIdsFor($spec, $subSection, TRUE);
          if (0 < count($subSectionTestIds)) {
            $testCaseIds = array_unique(array_merge($testCaseIds, $subSectionTestIds));
          }
        }
      }
    }
    return $testCaseIds;
  }
  
  
  function getPrimarySectionFor(TestCase $testCase)
  {
    $testCaseId = $testCase->getId();
    if ($this->mPrimaryAnchors && array_key_exists($testCaseId, $this->mPrimaryAnchors)) {
      return $this->mPrimaryAnchors[$testCaseId];
    }
    return FALSE;
  }
  
  
  function findSectionForURI($uri)
  {
    $spec = Specification::GetSpecificationByURI($uri);
    if ($spec) {
      $specName = $spec->getName();
      if (array_key_exists($specName, $this->mSections)) {
        $anchorURI = $spec->getAnchorURI($uri);
        foreach ($this->mSections[$specName] as $parentName => $subSections) {
          foreach ($subSections as $sectionName => $anchor) {
            if ($anchor->getAnchorURI() == $anchorURI) {
              return $anchor;
            }
          }
        }
      }
    }
    return FALSE;
  }
  
}

?>