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
require_once("lib/ResultDetails.php");


/**
 * Class for generating the page for inspecting results for
 * individual tests
 */
class DetailsPage extends HarnessPage
{  
  protected $mResultsTable;


  /**
   * Expected URL paramaters:
   * 's' Test Suite Name
   * 'c' Test Case Name
   * 'g' Test Group Name
   * 'o' Display order (currently unused)
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

    $order = intval($this->_getData('o'));
    $modified = $this->_getData('m');
    
    $grouping = $this->_getData('x');

    $engine = $this->_getData('e');
    $engineVersion = $this->_getData('v');

    $platform = $this->_getData('p');

    $this->mResultsTable = 
      new ResultDetails($testSuiteName, $testCaseName, $testGroupName, 
                        $engine, $engineVersion, $platform,
                        $grouping, $modified, $order);
  }
  
  
  function getPageTitle()
  {
    $title = parent::getPageTitle();
    return "{$title} Results";
  }
  
  
  function getNavURIs()
  {
    $uris = parent::getNavURIs();
    
    if ($this->mTestSuite) {
      $title = "Review Results";
      $query['s'] = $this->mTestSuite->getName();
      $uri = "review?" . http_build_query($query, 'var_');
      $uris[] = compact('title', 'uri');
      
      $title = "Details";
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


  /**
   * Output details table
   */
  function writeBodyContent($indent = '')
  {
    $data = $this->mResultsTable->getData();

    if (! $data) {
      echo $indent . "<p>No results entered matching this query.</p>\n";
    } 
    else {
      echo $indent . "<table>\n";
      echo $indent . "  <tr>\n";
      foreach($data[0] as $key => $value) {
        echo $indent . "    <th>" . Page::Encode($key) . "</th>\n";
      }
      echo $indent . "  </tr>\n";

      foreach($data as $result) {

        echo $indent . "  <tr class='" . Page::Encode($result['Result']) . "'>\n";
        foreach ($result as $key => $value) {
          if ('Testcase' == $key) {
            echo $indent . "    <td>";
            echo $this->mSpiderTrap->getTrapLink();
            $query['s'] = $this->_getData('s');//XXX temp until format support landed - $this->mTestSuite->getName();
            $query['c'] = $value;
            $query['u'] = $this->mUserAgent->getId();
            $queryStr = Page::Encode(http_build_query($query, 'var_'));
            echo "<a href='testcase?{$queryStr}'>" . Page::Encode($value) . "</a></td>\n";

          }
          else {
            echo $indent . "    <td>" . Page::Encode($value) . "</td>\n";
          }
        }
        echo $indent . "  </tr>\n";
      }
      echo $indent . "</table>\n";
    }
  }
}

?>