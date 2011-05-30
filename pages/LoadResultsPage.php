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
require_once('lib/Sections.php');
require_once('lib/TestCase.php');

/**
 * A page to convert a result query to path based URL
 */
class LoadResultsPage extends HarnessPage
{  
  protected $mNewURI;


  /**
   * Mandatory paramaters:
   * 's' Test Suite
   * 't' Report type (override 'c' & 'g', 0 = entire suite, 1 = group, 2 = one test)
   *
   * Optional paramaters:
   * 'c' Test Case Name
   * 'g' Spec Section Id
   * 'sec' Spec Section Name
   * 'f' Result filter (array or bitfield)
   * 'm' Modified date (only results before date)
   * 'e' Engine (optional, filter results for this engine)
   * 'v' Engine Version
   * 'p' Platform
   * 'o' Ordering 0 = one list, 1 = group by section
   */
  function __construct(Array $args = null) 
  {
    parent::__construct($args);
    
    $args = $this->mGetData;

    $testCaseName = $this->_getData('c');
    $sectionId = intval($this->_getData('g'));
    $sectionName = $this->_getData('sec');
    if ((0 == $sectionId) && ($sectionName)) {
      $sectionId = Sections::GetSectionIdFor($this->mTestSuite, $sectionName);
    }
    
    if ($this->_getData('t')) {
      $type = intval($this->_getData('t'));
      switch ($type) {
        case 0: unset($args['g']);   // whole suite
        case 1: unset($args['c']);   // test group
        case 2: break;               // individual test case
      }
    }
    unset($args['t']);

    $filter = $this->_getData('f');
    if (is_array($filter)) {
      $filterValue = 0;
      foreach ($filter as $value) {
        $filterValue = $filterValue | intval($value);
      }
      $args['f'] = $filterValue;
    }
    
    if ($this->mTestSuite && $this->mTestSuite->isValid()) {
      $this->mNewURI = $this->buildConfigURI('page.results', $args);
    }
    else {
      $this->mNewURI = $this->buildConfigURI('page.home');
    }
  }  


  /**
   * Redirect 
   */
  function getRedirectURI()
  {
    return $this->mNewURI;
  }


  function writeBodyContent()
  {
    $this->openElement('p', null, FALSE);
    $this->addTextContent("We have processed your request and you should have been redirected ");
    $this->addHyperLink($this->mNewURI, null, "here");
    $this->addTextContent(".");
    $this->closeElement('p');

  }
}

?>