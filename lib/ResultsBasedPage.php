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
  protected $mResults;
  
  /**
   * Expected URL paramaters:
   * 's' Test Suite Name
   * 'c' Test Case Name
   * 'g' Spec Section Id (optional)
   * 'sec' Spec Section Name (optional)
   * 't' Report type (override 'c' & 'g', 0 = entire suite, 1 = group, 2 = one test)
   * 'm' Modified date (optional, only results before date)
   * 'e' Engine (optional, filter results for this engine)
   * 'v' Engine Version (optional)
   * 'p' Platform
   */
  function __construct(Array $args = null, Array $pathComponents = null) 
  {
    parent::__construct($args, $pathComponents);
    
  }
  
  
  function setResults(Results $results)
  {
    $this->mResults = $results;
  }
  
  
  function loadResults()
  {
    if (! $this->mResults) {
      $testCaseName = $this->_getData('c');
      $sectionId = intval($this->_getData('g'));
      $sectionName = $this->_getData('sec');
      if ((0 == $sectionId) && ($sectionName)) {
        $sectionId = Sections::GetSectionIdFor($this->mTestSuite, $sectionName);
      }
      
      if ($this->_getData('t')) {
        $type = intval($this->_getData('t'));
        switch ($type) {
          case 0: $sectionId = 0;         // whole suite
          case 1: $testCaseName = null;   // test group
          case 2: break;                  // individual test case
        }
      }
      
      $modified         = $this->_getData('m', 'DateTime');
      $engineName       = $this->_getData('e');
      $engineVersion    = $this->_getData('v');
      $browserName      = $this->_getData('b');
      $browserVersion   = $this->_getData('bv');
      $platformName     = $this->_getData('p');
      $platformVersion  = $this->_getData('pv');
      
      $this->mResults = 
        new Results($this->mTestSuite, $testCaseName, $sectionId, 
                    $modified,
                    $engineName, $engineVersion, 
                    $browserName, $browserVersion, 
                    $platformName, $platformVersion);
    }
  }
}

?>