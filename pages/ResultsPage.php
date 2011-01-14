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


require_once("lib/HarnessPage.php");
require_once("lib/Results.php");


/**
 * Class for generating the page of result data
 */
class ResultsPage extends HarnessPage
{  
  protected $mResults;


  /**
   * Expected URL paramaters:
   * 's' Test Suite Name
   * 'c' Test Case Name
   * 'g' Test Group Name
   * 'f' Result filter (array or bitfield)
   * 'm' Modified date (only results before date)
   * 'x' Display grouping (currently unused)
   * 'e' Engine (filter results for this engine)
   * 'v' Engine Version
   * 'p' Platform
   */
  function __construct() 
  {
    parent::__construct();

/* XXX temp until format support landed
    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      $this->triggerClientError($msg, E_USER_ERROR);
    }

    $testSuiteName = $this->mTestSuite->getName();
*/    
    $testSuiteName = $this->_getData('s');  // XXX temp until format support landed
    $testCaseName = $this->_getData('c');
    $testGroupName = $this->_getData('g');

    if ($this->_getData('t')) {
      $type = intval($this->_getData('t'));
      switch ($type) {
        case 0: $testGroupName = null;  // whole suite
        case 1: $testCaseName = null;   // test group
        case 2: break;                  // individual test case
      }
    }

    $filter = $this->_getData('f');
    if (is_array($filter)) {
      $filterValue = 0;
      foreach ($filter as $value) {
        $filterValue = $filterValue | intval($value);
      }
      $filter = $filterValue;
    }
    else {
      $filter = intval($filter);
    }

    $modified = $this->_getData('m');
    $grouping = $this->_getData('x');
    $engine = $this->_getData('e');
    $engineVersion = $this->_getData('v');
    $platform = $this->_getData('p');

    $this->mResults = 
      new Results($testSuiteName, $testCaseName, $testGroupName,
                  $engine, $engineVersion, $platform, 
                  $grouping, $modified, $filter);

  }
  
  
  function getPageTitle()
  {
    $title = parent::getPageTitle();
    return "{$title} Results";
  }
  

  function getNavURIs()
  {
    $uris = parent::getNavURIs();
    
{//XXX format support    if ($this->mTestSuite) {
      $title = "Review Results";
      $query['s'] = $this->_getData('s');//XXX temp nutil format support - $this->mTestSuite->getName();
      $uri = "review?" . http_build_query($query, 'var_');
      $uris[] = compact('title', 'uri');
      
      $title = "Results";
      $uri = '';
      $uris[] = compact('title', 'uri');
    }
    return $uris;
  }

  
  /**
   * Generate <style> element
   */
  function writeHeadStyle($indent = '')
  {  
    parent::writeHeadStyle($indent);

    echo $indent . "<link rel='stylesheet' href='report.css' type='text/css'>\n";
  }


  function writeBodyContent($indent = '')
  { // XXX need to move write code to this class
    $this->mResults->write($indent, $this->mSpiderTrap);
  }
  
  
  function writeBodyFooter($indent = '')
  {
    echo $indent . "<h2>Legend</h2>\n";
    echo $indent . "<table class='legend'>\n";
    echo $indent . "  <tr><th>Row color codes</tr>\n";
    echo $indent . "  <tr class='pass'><td>two or more passes</tr>\n";
    echo $indent . "  <tr class='fail'><td>blocking failures</tr>\n";
    echo $indent . "  <tr class='uncertain'><td>not enough results</tr>\n";
    echo $indent . "  <tr class='invalid'><td>reported as invalid</tr>\n";
    echo $indent . "  <tr class='optional'><td>not passing, but optional</tr>\n";
    echo $indent . "</table>\n";

    echo $indent . "<table class='legend'>\n";
    echo $indent . "  <tr><th>Result color codes</tr>\n";
    echo $indent . "  <tr><td class='pass'>all results pass</tr>\n";
    echo $indent . "  <tr><td class='pass fail'>pass reported, but also other results</tr>\n";
    echo $indent . "  <tr><td class='fail'>all results fail</tr>\n";
    echo $indent . "  <tr><td class='fail uncertain'>fail reported, but also other results</tr>\n";
    echo $indent . "  <tr><td class='uncertain'>all results uncertain</tr>\n";
    echo $indent . "  <tr><td class='invalid'>reported as invalid</tr>\n";
    echo $indent . "  <tr><td># pass / # fail / # uncertian</tr>\n";
    echo $indent . "</table>\n";

    parent::writeBodyFooter($indent);
  }
}

?>