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


require_once('lib/HarnessPage.php');
require_once('lib/Results.php');
require_once('lib/Sections.php');


/**
 * Class for generating the page of result data
 */
class ResultsBasedPage extends HarnessPage
{
  protected $mTestCase;
  protected $mSpec;
  protected $mSection;
  protected $mModifiedDateTime;
  protected $mEngineName;
  protected $mEngineVersion;
  protected $mBrowserName;
  protected $mBrowserVersion;
  protected $mPlatformName;
  protected $mPlatformVersion;
  protected $mResults;

  /**
   * Expected URL paramaters:
   * 'suite' Test Suite Name
   *
   * Optional URL paramaters
   * 'testcase' Test Case Name - test only this test
   * 'spec' Specification
   * 'section' Spec Section Name
   * 'index' Test Case Name - find this test in the group
   * 'type' Report type (override 'testcase' & 'section', 0 = entire suite, 1 = group, 2 = one test)
   * 'modified' Modified date (optional, only results before date)
   * 'engine' Engine (optional, filter results for this engine)
   * 'version' Engine Version (optional)
   * 'platform' Platform
   */
  function _initPage()
  {
    parent::_initPage();

    $this->mModifiedDateTime = $this->_getData('modified', 'DateTime');
    $this->mEngineName       = $this->_getData('engine');
    $this->mEngineVersion    = $this->_getData('version');
    $this->mBrowserName      = $this->_getData('browser');
    $this->mBrowserVersion   = $this->_getData('browser_version');
    $this->mPlatformName     = $this->_getData('platform');
    $this->mPlatformVersion  = $this->_getData('platform_version');

    set_time_limit(3600);
  }


  function setResults(Results $results)
  {
    $this->mResults = $results;
  }


  function loadResults()
  {
    if ($this->mTestSuite && $this->mTestSuite->isValid() && (! $this->mResults)) {
      $testCaseName = $this->_getData('testcase');
      $specName = $this->_getData('spec');
      $sectionName = $this->_getData('section');
      if ($this->_getData('type')) {
        $type = intval($this->_getData('type'));
        switch ($type) {
          case 0: $specName = null;       // whole suite
          case 1: $testCaseName = null;   // test group
          case 2: break;                  // individual test case
        }
      }

      if ($testCaseName) {
        $this->mTestCase = TestCase::GetTestCase($this->mTestSuite, $testCaseName);
      }
      if ($specName) {
        $this->mSpec = Specification::GetSpecificationByName($specName);
      }
      if ($sectionName) {
        if (! $this->mSpec) {
          $this->mSpec = array_first($this->mTestSuite->getSpecifications());
        }
        $this->mSection = SpecificationAnchor::GetSectionFor($this->mSpec, $sectionName);
      }

      $this->mResults =
        new Results($this->mTestSuite, $this->mTestCase,
                    $this->mSpec, $this->mSection,
                    $this->mModifiedDateTime,
                    $this->mEngineName, $this->mEngineVersion,
                    $this->mBrowserName, $this->mBrowserVersion,
                    $this->mPlatformName, $this->mPlatformVersion);
    }
  }
}

?>