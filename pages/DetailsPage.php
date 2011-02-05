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

    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      trigger_error($msg, E_USER_WARNING);
    }

    $testSuiteQuery = $this->mTestSuite->getSequenceQuery();  // XXX temp until format support
    $testCaseName = $this->_getData('c');
    $testGroupName = $this->_getData('g');

    $order = intval($this->_getData('o'));
    $modified = $this->_getData('m');
    
    $grouping = $this->_getData('x');

    $engine = $this->_getData('e');
    $engineVersion = $this->_getData('v');

    $platform = $this->_getData('p');

    $this->mResultsTable = 
      new ResultDetails($testSuiteQuery, $testCaseName, $testGroupName, 
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
      $args['s'] = $this->mTestSuite->getName();
      $args['u'] = $this->mUserAgent->getId();

      $uri = $this->buildURI(REVIEW_PAGE_URI, $args);
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
      echo $indent . "    <th>Test Case</th>\n";
      echo $indent . "    <th>Format</th>\n";
      echo $indent . "    <th>Result</th>\n";
      echo $indent . "    <th>User Agent</th>\n";
      echo $indent . "    <th>Date</th>\n";
      echo $indent . "    <th>Source</th>\n";
      echo $indent . "  </tr>\n";

      foreach($data as $resultData) {

        echo $indent . "  <tr class='" . self::Encode($resultData['result']) . "'>\n";
        $testSuiteName  = $resultData['testsuite']; // XXX temp until format support landed - $this->mTestSuite->getName();
        $testCaseName   = $resultData['testcase'];
        $userAgentId    = $resultData['useragent_id'];
        
        $userAgent = new UserAgent(intval($userAgentId));
        $testSuite = new TestSuite($testSuiteName);

        $result         = self::Encode($resultData['result']);
        $date           = self::Encode($resultData['date']);
        $source         = self::Encode($resultData['source']);
        $uaString       = self::Encode($userAgent->getUAString());
        $uaDescription  = self::Encode($userAgent->getDescription());
        
        echo $indent . "    <td>";
        echo $this->mSpiderTrap->getTrapLink();
        $args['s'] = $testSuiteName;
        $args['c'] = $testCaseName;
        $args['u'] = $this->mUserAgent->getId();

        $uri = $this->encodeURI(TESTCASE_PAGE_URI, $args);
        echo "<a href='{$uri}'>" . self::Encode($testCaseName) . "</a></td>\n";

        echo $indent . "    <td>" . self::Encode($testSuite->getFormat()) . "</td>\n";
        echo $indent . "    <td>{$result}</td>\n";
        echo $indent . "    <td><abbr title='{$uaString}'>{$uaDescription}<abbr></td>\n";
        echo $indent . "    <td>{$date}</td>\n";
        echo $indent . "    <td>{$source}</td>\n";

        echo $indent . "  </tr>\n";
      }
      echo $indent . "</table>\n";
    }
  }
}

?>