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
require_once("lib/Result.php");
require_once("lib/Format.php");
require_once("lib/UserAgent.php");
require_once("lib/User.php");


/**
 * Class for generating the page for inspecting results for
 * individual tests
 */
class DetailsPage extends HarnessPage
{  
  protected $mResults;


  /**
   * Expected URL paramaters:
   * 's' Test Suite Name
   * 'c' Test Case Name
   * 'g' Spec Section Id (optional)
   * 't' Report type (override 'c' & 'g', 0 = entire suite, 1 = group, 2 = one test)
   * 'o' Display order (currently unused)
   * 'm' Modified date (only results before date)
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

    $testCaseName = $this->_getData('c');
    $specLinkId = intval($this->_getData('g'));

    if ($this->_getData('t')) {
      $type = intval($this->_getData('t'));
      switch ($type) {
        case 0: $specLinkId = 0;        // whole suite
        case 1: $testCaseName = null;   // test group
        case 2: break;                  // individual test case
      }
    }

    $order = intval($this->_getData('o'));
    $modified = $this->_getData('m', 'DateTime');
    
    $engine = $this->_getData('e');
    $engineVersion = $this->_getData('v');

    $platform = $this->_getData('p');

    $this->mResults = 
      new Results($this->mTestSuite, $testCaseName, $specLinkId,
                  $engine, $engineVersion, $platform, $modified);
  }
  
  
  function getPageTitle()
  {
    $title = parent::getPageTitle();
    return "{$title} Result Details";
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
    if (0 == $this->mResults->getResultCount()) {
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

      $testSuiteName  = $this->mTestSuite->getName();
      
      $formats = Format::GetFormatsFor($this->mTestSuite);
      $userAgents = UserAgent::GetAllUserAgents();
      
      $testCases = $this->mResults->getTestCases();
      foreach ($testCases as $testCaseId => $testCaseData) {
        $engineResults = $this->mResults->getResultsFor($testCaseId);
        
        if ($engineResults) {
          ksort($engineResults);
          
          $testCaseName   = $testCaseData['testcase'];
          
          foreach ($engineResults as $engine => $engineResultData) {
            asort($engineResultData);
            
            foreach ($engineResultData as  $resultId => $resultValue) {
              $resultValue = self::Encode($resultValue);
            
              echo $indent . "  <tr class='{$resultValue}'>\n";

              $result = new Result($resultId);
              
              $userAgent = $userAgents[$result->getUserAgentId()];
              $sourceId = $result->getSourceId();
              if ($sourceId) {
                $user = new User($sourceId);
                $source = self::Encode($user->getName());
              }
              else {
                $source = '';
              }

              $format         = self::Encode($formats[$result->getFormatName()]->getTitle());
              $date           = self::Encode($result->getDate());
              $uaString       = self::Encode($userAgent->getUAString());
              $uaDescription  = self::Encode($userAgent->getDescription());
              
              echo $indent . "    <td>";
              echo $this->mSpiderTrap->getTrapLink();
              $args['s'] = $testSuiteName;
              $args['c'] = $testCaseName;
              $args['f'] = $result->getFormatName();
              $args['u'] = $this->mUserAgent->getId();

              $uri = $this->encodeURI(TESTCASE_PAGE_URI, $args);
              echo "<a href='{$uri}'>" . self::Encode($testCaseName) . "</a></td>\n";

              echo $indent . "    <td>{$format}</td>\n";
              echo $indent . "    <td>{$resultValue}</td>\n";
              echo $indent . "    <td><abbr title='{$uaString}'>{$uaDescription}</abbr></td>\n";
              echo $indent . "    <td>{$date}</td>\n";
              echo $indent . "    <td>{$source}</td>\n";

              echo $indent . "  </tr>\n";
            }
          }
        }
      }
      echo $indent . "</table>\n";
    }
  }
}

?>