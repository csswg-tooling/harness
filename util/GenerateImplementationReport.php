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

require_once('lib/HarnessCmdLineWorker.php');
require_once('lib/Results.php');
require_once('pages/ResultsPage.php');
require_once('pages/DetailsPage.php');


require_once('modules/testsuite/TestSuite.php');
require_once('modules/testsuite/TestFormat.php');
require_once('modules/specification/Specification.php');
require_once('modules/useragent/Engine.php');

define('UA_THRESHOLD', 10);
define('INCLUDE_ID', FALSE);
  
/**
 * Class for generating simplified results table
 */
class IR_ResultsPage extends ResultsPage
{
  
  function _initPage()
  {
    parent::_initPage();
    
    $this->mDisplayLinks = FALSE;
    $this->mSpiderTrap = null;
  }
  
  
  function writeHeadStyle()
  {
    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.base'));
    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.report'));
    
//    $this->addStyleSheetLink('http://www.w3.org/StyleSheets/activity.css');
    $this->addStyleElement('li:hover { background: #FFC; }');
  }


  function getStats()
  {
    return array('testCount' => $this->mTestCaseRequiredCount, 
                 'passCount' => $this->mTestCaseRequiredPassCount,
                 'invalidCount' => $this->mTestCaseInvalidCount,
                 'optionalCount' => $this->mTestCaseOptionalCount,
                 'optionalPassCount' => $this->mTestCaseOptionalPassCount);
  }
  
  
  function writeBodyHeader()
  {
    $this->openElement('div', array('class' => 'header'));
    
    $this->writeLargeW3CLogo();
    
    $this->writeContentTitle();
    
    $this->closeElement('div');
  }
  
  
  function _generateResultCell(TestCase $testCase, $engineName, $class, $section, $content)
  {
    $uri = "details_{$engineName}.html#" . (($section) ? "s{$section->getName()}_{$testCase->getName()}" : $testCase->getName());
    
    $this->openElement('td', array('class' => $class), FALSE);
    $this->addHyperLink($uri, null, $content, FALSE);
    $this->closeElement('td');
  }
  
  
}
  

/**
 * Class for generating simplified page of result data details
 */
class IR_DetailsPage extends DetailsPage
{
  
  function _initPage()
  {
    parent::_initPage();
    
    $this->mDisplayLinks = FALSE;
    $this->mSpiderTrap = null;
  }
  
  
  function writeHeadStyle()
  {
    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.base'));
    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.report'));
    
//    $this->addStyleSheetLink('http://www.w3.org/StyleSheets/activity.css');
    $this->addStyleElement('li:hover { background: #FFC; }');
  }


  function getPageTitle()
  {
    $engineName = $this->_getData('engine');
    
    $title = parent::getPageTitle();
    if ($engineName) {
      $engine = new Engine($engineName);
      if ($engine->getTitle()) {
        return "{$title} for {$engine->getTitle()}";
      }
    }
    return $title;
  }
  
  
  function writeBodyHeader()
  {
    $this->openElement('div', array('class' => 'header'));
    
    $this->writeLargeW3CLogo();
    
    $this->writeContentTitle();
    
    $this->closeElement('div');
  }
  
  
}

  
class IR_UserAgentPage extends ResultsBasedPage
{
  protected $mUserAgents;
  protected $mUserAgentResultCounts;
  
  function _initPage()
  {
    parent::_initPage();

    $this->mSpiderTrap = null;
  }
  
  
  function writeHeadStyle()
  {
//    $this->addStyleSheetLink('http://www.w3.org/StyleSheets/activity.css');
    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.base'));
    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.report'));
    
    $this->addStyleElement('li:hover { background: #FFC; }');
  }


  function setUserAgents($userAgents, $userAgentResultCounts)
  {
    $this->mUserAgents = $userAgents;
    $this->mUserAgentResultCounts = $userAgentResultCounts;
  }
  
  
  function getPageTitle()
  {
    $title = parent::getPageTitle();
    return "{$title} List of User Agents Tested";
  }
  
  
  function writeBodyHeader()
  {
    $this->openElement('div', array('class' => 'header'));
    
    $this->writeLargeW3CLogo();
    
    $this->writeContentTitle();
    
    $this->closeElement('div');
  }
  
  
  /**
   * Output user agent list
   */
  function writeBodyContent()
  {
    if (0 == $this->mResults->getResultCount()) {
      $this->addElement('p', null, 'No results entered matching this query.');
    } 
    else {
      $this->openElement('ul');
      foreach ($this->mUserAgents as $engineName => $engineUserAgents) {
        uasort($engineUserAgents, array('UserAgent', 'CompareDescription'));
        $this->addElement('li', null, $engineUserAgents[0]->getEngineTitle());
        $this->openElement('ul');
        foreach ($engineUserAgents as $userAgent) {
          $this->openElement('li');
          $this->addAbbrElement($userAgent->getUAString(), null, $userAgent->getDescription());
          $count = $this->mUserAgentResultCounts[$userAgent->getId()];
          if (1 < $count) {
            $this->addTextContent(" - {$count} results.");
          }
          else {
            $this->addTextContent(' - 1 result.');
          }
          $this->closeElement('li');
        }
        $this->closeElement('ul');
      }
      $this->closeElement('ul');
    }
  }
  
  
  function writeBodyFooter()
  {
    if (INCLUDE_ID) {
      $this->addElement('hr');
      $this->addElement('address', null, '$Id$');
    }
  }
}


class IR_IndexPage extends HarnessPage
{
  protected $mSpec;
  protected $mSpecStatus;
  protected $mUserAgents;
  protected $mStats;
  protected $mEngines;
  
  function _initPage()
  {
    parent::_initPage();
    
    $this->mSpiderTrap = null;
    $this->mSpecStatus = 'DEV';
  }
  
  
  function setUserAgents($userAgents, $userAgentResultCounts)
  {
    $this->mUserAgents = $userAgents;
    $this->mUserAgentResultCounts = $userAgentResultCounts;
  }
  
  function setSpecStatus($specStatus)
  {
    if ($specStatus) {
      $this->mSpecStatus = $specStatus;
    }
  }
  
  function setSpec(Specification $spec)
  {
    $this->mSpec = $spec;
  }
  
  function setStats(Array $stats)
  {
    $this->mStats = $stats;
  }
  
  function setEngines(Array $engines)
  {
    $this->mEngines = $engines;
  }
  
  function getPageTitle()
  {
    $title = $this->mSpec->getTitle();
    $desc = $this->mSpec->getDescription();
    return "{$desc} ({$title}) Implementation Report";
  }
  
  
  function writeHeadStyle()
  {
    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.base'));
    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.report'));
    
//    $this->addStyleSheetLink('http://www.w3.org/StyleSheets/activity.css');
    $this->addStyleElement('li:hover { background: #FFC; }');
  }


  function writeHTMLBody()
  {
    $this->openElement('body');
    $this->openElement('div', array('id' => 'Contents'));
    $this->writeBodyHeader();
    $this->writeBodyContent();
    $this->closeElement('div');
    $this->writeBodyFooter();
    $this->closeElement('body');
  }

  function writeBodyHeader()
  {
    $this->openElement('h1');
    $this->writeLargeW3CLogo();
    $this->addElement('br');
    $this->addTextContent($this->getPageTitle());
    $this->closeElement('h1');
  }


  function countBrowsers()
  {
    $browserNames = array();
    
    foreach ($this->mUserAgents as $engineUserAgents) {
      foreach ($engineUserAgents as $userAgent) {
        $browserNames[$userAgent->getBrowserName()] = TRUE;
      }
    }

    return count($browserNames);
  }
  
  
  function countPlatforms()
  {
    $platformNames = array();
    
    foreach ($this->mUserAgents as $engineUserAgents) {
      foreach ($engineUserAgents as $userAgent) {
        $platformName = $userAgent->getPlatformName();
        if ($platformName) {
          $platformNames[$platformName] = TRUE;
        }
      }
    }

    return count($platformNames);
  }
  
  
  /**
   * Output user agent list
   */
  function writeBodyContent()
  {
    extract($this->mStats);
    
    $now = new DateTime('now', new DateTimeZone(Config::Get('server', 'time_zone')));
    
    $totalCount = $testCount + $optionalCount + $invalidCount;
    $formats = $this->mTestSuite->getFormats(); 

    $this->addElement('h2', array('id' => 'intro'), 'Introduction');
    
    if ('CR' == $this->mSpecStatus) {
      $this->openElement('p');
      $this->addTextContent('This report was prepared to document the passing of the Candidate Recommendation exit criteria for the ');
      $this->addHyperlink($this->mSpec->getFullHomeURI(), null, $this->mSpec->getDescription());
      $this->addTextContent(" ({$this->mSpec->getTitle()}) specification.");
      $this->closeElement('p');
    }
    else {
      $this->openElement('p', null, FALSE);
      $this->addTextContent('This report was prepared to document the current implementation status of the ');
      $this->addHyperlink($this->mSpec->getFullHomeURI(), null, $this->mSpec->getDescription());
      $this->addTextContent(" ({$this->mSpec->getTitle()}) specification as of {$now->format('j F Y')}");
      $this->addTextContent(' and is based on current test results available in the ');
      $this->addHyperlink($this->buildPageURI('home', null, null, TRUE), null, 'W3C Conformance Test Harness');
      $this->addTextContent('.');
      $this->closeElement('p');
    }

    $this->addElement('h2', array('id' => 'impls'), 'Implementations');
    
    $osCount = $this->countPlatforms();
    
    $this->openElement('p');
    $osText = ((1 < $osCount) ? " across " . $this->_NumberToText($osCount) . " operating systems" : '');
    if (1 < count($this->mEngines)) {
      $engineCountText = $this->_NumberToText(count($this->mEngines));
      $engineText = ", built from {$engineCountText} rendering implementations,";
    }
    else {
      $engineText = '';
    }
    if (1 != $this->countBrowsers()) {
      $browserCountText = $this->_NumberToText($this->countBrowsers());
      $this->addTextContent(ucfirst("{$browserCountText} user agents{$engineText} were tested{$osText}."));
    }
    else {
      $this->addTextContent("One user agent{$engineText} was tested{$osText}.");
    }
    $this->closeElement('p');
    if (count($this->mUserAgents, COUNT_RECURSIVE) <= UA_THRESHOLD) {
      $this->openElement('ul');
      foreach ($this->mUserAgents as $engineName => $engineUserAgents) {
        uasort($engineUserAgents, array('UserAgent', 'CompareDescription'));
        $this->addElement('li', null, $engineUserAgents[0]->getEngineTitle());
        $this->openElement('ul');
        foreach ($engineUserAgents as $userAgent) {
          $this->openElement('li');
          $this->addAbbrElement($userAgent->getUAString(), null, $userAgent->getDescription());
          $count = $this->mUserAgentResultCounts[$userAgent->getId()];
          if (1 < $count) {
            $this->addTextContent(" - {$count} results.");
          }
          else {
            $this->addTextContent(' - 1 result.');
          }
          $this->closeElement('li');
        }
        $this->closeElement('ul');
      }
      $this->closeElement('ul');
    }
    else {
      $this->openElement('ul');
      $this->openElement('li');
      $this->addHyperlink('useragents.html', null, 'Complete list of user agents tested');
      $this->closeElement('li');
      $this->closeElement('ul');
    }

    $this->addElement('h2', array('id' => 'tests'), 'Tests');
    
    $this->openElement('p', null, FALSE);
    $this->addTextContent('The ');
    $this->addHyperlink($this->mTestSuite->getURI(), null, $this->mTestSuite->getBuildDateTime()->format('j F Y'));
    $this->addTextContent(" revision of the {$this->mSpec->getTitle()} test suite was used.");
    if (1 == $totalCount) {
      if (0 == $invalidCount) {
        if (0 == $optionalCount) {
          $this->addTextContent(' The test suite consists of 1 test.');
        }
        else {
          $this->addTextContent(' The test suite consists of 1 test, which tests for optional behavior.');
        }
      }
      else {
        $this->addTextContent(' The test suite consists of 1 test, which is considered invalid.');
      }
    }
    else {
      if (0 < $optionalCount) {
        if (1 == $optionalCount) {
          $optionalText = ', 1 of which tests for optional behavior';
        }
        else {
          $optionalText = ", {$optionalCount} of which test for optional behavior";
        }
      }
      else {
        $optionalText = '';
      }
      if (0 < $invalidCount) {
        if (0 < $optionalCount) {
          $join = ', and';
        }
        else {
          $join = ',';
        }
        if (1 == $invalidCount) {
          $invalidText = "{$join} 1 of which is considered invalid";
        }
        else {
          $invalidText = "{$join} {$invalidCount} of which are considered invalid";
        }
      }
      else {
        $invalidText = '';
      }
      $this->addTextContent(" The test suite consists of {$totalCount} tests{$optionalText}{$invalidText}.");
    }
    $this->closeElement('p');

    if (1 < count($formats)) {
      $this->addElement('p', null, 'These tests are available in several host language variants:');
      $this->openElement('ul');
      foreach ($formats as $format) {
        $this->openElement('li');
        $this->addHyperlink($this->mTestSuite->getURI() . $format->getHomeURI(), null, $format->getDescription());
        $this->closeElement('li');
      }
      $this->closeElement('ul');
      $this->addElement('p', null, 'Some tests only apply to certain language variants; the results for each tested implementation are reported for each tested language variant. Also, not all tests are applicable to each implementation; for example, a browser which implements HTML but not XHTML is not tested for XHTML.');
    }

    $this->addElement('h2', array('id' => 'results'), 'Results');
   
    if (0 == $testCount) {
      $this->addElement('p', null, 'No valid tests are present in this test suite.');
    }
    else {
      if (1 == $testCount) {
        if (0 < $passCount) {
          $this->addElement('p', null, 'In summary, the results show that the test was passed by at least two of the tested implementations.');
        }
        else {
          $this->addElement('p', null, 'In summary, the results show that the test was not passed by at least two of the tested implementations.');
        }
      }
      else {
        $validTestText = ((0 < $invalidCount) ? ' valid' : '');
        $testCountText = (($passCount == $testCount) ? "all {$testCount}{$validTestText}" : "{$passCount} of {$testCount}{$validTestText}");
        if (0 < $optionalCount) {
          $requiredText = ' for required behavior';
          if (0 < $optionalPassCount) {
            if (1 == $optionalCount) {
              $optionalText = ' In addition, the 1 test for optional behavior was passed by at least two of the tested implementations.';
            }
            else {
              if ($optionalPassCount == $optionalCount) {
                if (2 == $optionalCount) {
                  $optionalCountText = 'both';
                }
                else {
                  $optionalCountText = "all {$optionalCount}";
                }
              }
              else {
                $optionalCountText = "{$optionalPassCount} of {$optionalCount}";
              }
              $optionalText = " In addition, {$optionalCountText} tests for optional behavior were passed by at least two of the tested implementations.";
            }
          }
          else {
            $optionalText = ' No tests for optional behavior were passed by at least two of the tested implementations.';
          }
        }
        else {
          $requiredText = '';
          $optionalText = '';
        }
        $this->addElement('p', null, "In summary, the results show that {$testCountText} tests{$requiredText} were passed by at least two of the tested implementations.{$optionalText}");
      }
    }
    
    $this->openElement('p', null, FALSE);
    $this->addTextContent('Results were gathered from implementation reports submitted by user agent vendors as well as the general public via the ');
    $this->addHyperlink($this->buildPageURI('home', null, null, TRUE), null, 'W3C Conformance Test Harness');
    $this->addTextContent('.');
    $this->closeElement('p');
    
    $this->openElement('ul');
    $this->openElement('li');
    $this->addHyperlink('results.html', null, 'Summary table of test result counts per rendering implementation');
    $this->closeElement('li');
    $this->addElement('li', null, 'Detailed results for each rendering implementation');
    $this->openElement('ul');
    foreach ($this->mEngines as $engineName => $engine) {
      $this->openElement('li');
      $this->addHyperlink(strtolower("details_{$engineName}.html"), null, $engine->getTitle());
      $this->closeElement('li');
    }
    $this->closeElement('ul');
    $this->closeElement('ul');
  }
  
  
  function writeBodyFooter()
  {
    if (INCLUDE_ID) {
      $this->addElement('hr');
      $this->addElement('address', null, '$Id$');
    }
  }  
}


/**
 * This class generates a formal implementation report for the test suite
 *
 */
class GenerateImplementationReport extends HarnessCmdLineWorker
{
  protected $mResults;
  protected $mUserAgents;
  protected $mUserAgentResultCounts;
  
  function __construct() 
  {
    parent::__construct();
    
  }
  
  function usage()
  {
    echo "USAGE: php GenerateImplementationReport.php testsuite [output path] [CR|DEV] [spec name]\n";
  }
  
  
  function findUserAgents()
  {
    $this->mUserAgents = array();
    $this->mUserAgentResultCounts = array();
    
    $userAgentIds = array();
    
    $testCases = $this->mResults->getTestCases();
    foreach ($testCases as $testCaseId => $testCase) {
      $engineResults = $this->mResults->getResultsFor($testCase);
      
      if ($engineResults) {
        foreach ($engineResults as $engineName => $engineResultData) {
          foreach ($engineResultData as  $resultId => $resultValue) {
            $result = new Result($resultId);
            $userAgentId = $result->getUserAgentId();
            $userAgentIds[$engineName][$userAgentId] = TRUE;
            if (array_key_exists($userAgentId, $this->mUserAgentResultCounts)) {
              $this->mUserAgentResultCounts[$userAgentId] += 1;
            }
            else {
              $this->mUserAgentResultCounts[$userAgentId] = 1;
            }
          }
        }
      }
    }
    
    $allUserAgents = UserAgent::GetAllUserAgents();
    
    foreach ($this->mResults->getEngines() as $engineName => $engine) {
      if (array_key_exists($engineName, $userAgentIds)) {
        $engineUserAgentIds = $userAgentIds[$engineName];
        foreach ($engineUserAgentIds as $userAgentId => $bool) {
          $userAgent = $allUserAgents[$userAgentId];
          $this->mUserAgents[$engineName][] = $userAgent;
        }
      }
    }
  }
  
  
  /**
   * Generate implementation report pages
   *
   */
  function generate($testSuiteName, $outputPath, $specStatus, $specName)
  {
    $testSuite = @new TestSuite($testSuiteName);
    
    if ($testSuite->isValid()) {
      
      if ($outputPath) {
        if (! file_exists($outputPath)) {
          mkdir($outputPath, 0777, TRUE);
        }
      }
      else {
        $outputPath = '';
      }

      echo "Loading results for {$testSuiteName}\n";
      
      $spec = null;
      if ($specName) {
        $spec = Specification::GetSpecificationByName($specName);
      }
      if (! $spec) {
        $spec = reset($testSuite->getSpecifications());
      }
      
      $this->mResults = new Results($testSuite, null, $spec);
      
      $args['suite'] = $testSuiteName;
      $args['order'] = 1; // section ordering
      $args['spec'] = $spec->getName();
      
      echo "Generating results page\n";
      
      $resultsPage = new IR_ResultsPage($args);
      $resultsPage->setResults($this->mResults);
      $resultsPage->write($this->_CombinePath($outputPath, 'results.html'));
      
      echo "Finding User Agents\n";

      $this->findUserAgents();
      
      echo "Generating index page\n";
      
      $indexPage = new IR_IndexPage($args);
      $indexPage->setSpecStatus($specStatus);
      $indexPage->setSpec($spec);
      $indexPage->setStats($resultsPage->getStats());
      $indexPage->setEngines($this->mResults->getEngines());
      $indexPage->setUserAgents($this->mUserAgents, $this->mUserAgentResultCounts);
      $indexPage->write($this->_CombinePath($outputPath, 'index.html'));

      if (UA_THRESHOLD < count($this->mUserAgents, COUNT_RECURSIVE)) {
        echo "Generating User Agent page\n";
        
        $uaPage = new IR_UserAgentPage($args);
        $uaPage->setResults($this->mResults);
        $uaPage->setUserAgents($this->mUserAgents, $this->mUserAgentResultCounts);
        $uaPage->write($this->_CombinePath($outputPath, 'useragents.html'));
      }

      $engines = $this->mResults->getEngines();
      foreach ($engines as $engineName => $engine) {
        echo "Generating details page for {$engineName}\n";
        
        $args['engine'] = $engineName;
        $detailsPage = new IR_DetailsPage($args);
        $detailsPage->setResults($this->mResults);
        $detailsPage->write($this->_CombinePath($outputPath, strtolower("details_{$engineName}.html")));
      }
      
      // copy stylesheets
      if ($outputPath) {
        $this->copyFile(Config::Get('uri.stylesheet', 'base'), $this->_CombinePath($outputPath, Config::Get('uri.stylesheet', 'base')));
        $this->copyFile(Config::Get('uri.stylesheet', 'report'), $this->_CombinePath($outputPath, Config::Get('uri.stylesheet', 'report')));
      }
    }
    else {
      echo "Unknown test suite\n";
    }
  }
}

$worker = new GenerateImplementationReport();

$testSuiteName  = $worker->_getArg(1);
$outputPath     = $worker->_getArg(2);
$specStatus     = $worker->_getArg(3);
$specName       = $worker->_getArg(4);

if ($testSuiteName) {
  $worker->generate($testSuiteName, $outputPath, $specStatus, $specName);
}
else {
  $worker->usage();
}

?>