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
require_once('lib/TestSuite.php');
require_once('lib/Specification.php');
require_once('lib/Results.php');
require_once('lib/Engine.php');
require_once('pages/ResultsPage.php');
require_once('pages/DetailsPage.php');


define('UA_THRESHOLD', 10);
  
/**
 * Class for generating simplified results table
 */
class IR_ResultsPage extends ResultsPage
{
  
  function __construct(Array $args = null)
  {
    parent::__construct($args);
    
    $this->mDisplayLinks = FALSE;
    $this->mSpiderTrap = null;
  }
  
  
  function getStats()
  {
    return array('testCount' => $this->mTestCaseRequiredCount, 
                 'passCount' => $this->mTestCaseRequiredPassCount,
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
  
  
  function _generateResultCell($testCaseName, $engineName, $class, $section, $content)
  {
    $uri = "details_{$engineName}.html#" . (($section) ? "s{$section}_{$testCaseName}" : $testCaseName);
    
    $this->openElement('td', array('class' => $class), FALSE);
    $this->addHyperLink($uri, null, $content, FALSE);
    $this->closeElement('td');
  }
  
  
  function writeBodyFooter()
  {
    $this->writeLedgend();

    $this->addElement('hr');
    $this->addElement('address', null, '$Id$');
  }
}
  

/**
 * Class for generating simplified page of result data details
 */
class IR_DetailsPage extends DetailsPage
{
  
  function __construct(Array $args = null)
  {
    parent::__construct($args);
    
    $this->mDisplayLinks = FALSE;
    $this->mSpiderTrap = null;
  }
  
  
  function getPageTitle()
  {
    $engineName = $this->_getData('e');
    
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
  
  
  function writeBodyFooter()
  {
    $this->writeLedgend();

    $this->addElement('hr');
    $this->addElement('address', null, '$Id$');
  }
}

  
class IR_UserAgentPage extends ResultsBasedPage
{
  protected $mUserAgents;
  protected $mUserAgentResultCounts;
  
  function __construct(Array $args = null)
  {
    parent::__construct($args);

    $this->mSpiderTrap = null;
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
    $this->addElement('hr');
    $this->addElement('address', null, '$Id$');
  }
}


class IR_IndexPage extends HarnessPage
{
  protected $mSpec;
  protected $mUserAgents;
  protected $mStats;
  protected $mEngineNames;
  
  function __construct(Array $args = null)
  {
    parent::__construct($args);
    
    $this->mSpiderTrap = null;
    $this->mSpec = new Specification($this->mTestSuite);
  }
  
  
  function setUserAgents($userAgents, $userAgentResultCounts)
  {
    $this->mUserAgents = $userAgents;
    $this->mUserAgentResultCounts = $userAgentResultCounts;
  }
  
  function setStats(Array $stats)
  {
    $this->mStats = $stats;
  }
  
  function setEngineNames(Array $engineNames)
  {
    $this->mEngineNames = $engineNames;
  }
  
  function getPageTitle()
  {
    $title = $this->mSpec->getTitle();
    $desc = $this->mSpec->getDescription();
    return "{$desc} ({$title}) Implementation Report";
  }
  
  
  function writeHeadStyle()
  {
    parent::writeHeadStyle();
    
    $this->addStyleSheetLink('http://www.w3.org/StyleSheets/activity.css');
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
  
  
  function numberToText($number)
  {
    $numbers = array('zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
                     'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen');
    $tens = array('', 'ten', 'twenty', 'thirty', 'fourty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety');
    $zillions = array(1000000000 => 'billion', 1000000 => 'million', 1000 => 'thousand');
    
    $text = '';
    if ($number < 0) {
      $text = 'negative ';
      $number = abs($number);
    }
    
    foreach ($zillions as $value => $name) {
      if ($value <= $number) {
        $text .= $this->numberToText(intval($number / $value)) . ' ' . $name . ' ';
        $number = $number % $value;
      }
    }
    if (99 < $number) {
      $text .= $numbers[intval($number / 100)] . ' hundred ';
      $number = $number % 100;
    }
    if (19 < $number) {
      $text .= $tens[intval($number / 10)] . ' ';
      $number = $number % 10;
    }
    if ((0 < $number) || ($text = '')) {
      $text .= $numbers[$number];
    }
    
    return rtrim($text);
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
    
    $totalCount = $testCount + $optionalCount;
    $formats = Format::GetFormatsFor($this->mTestSuite);

    $this->addElement('h2', array('id' => 'intro'), 'Introduction');
    
    $this->openElement('p');
    $this->addTextContent('This report was prepared to document the passing of the Candidate Recommendation exit criteria for the ');
    $this->addHyperlink($this->mSpec->getHomeURI(), null, $this->mSpec->getDescription());
    $this->addTextContent(" ({$this->mSpec->getTitle()}) specification.");
    $this->closeElement('p');

    $this->addElement('h2', array('id' => 'impls'), 'Implementations');
    
    $browserCountText = $this->numberToText($this->countBrowsers());
    $engineCountText = $this->numberToText(count($this->mEngineNames));
    $osCount = $this->countPlatforms();
    
    $this->openElement('p');
    $osText = ((1 < $osCount) ? " across " . $this->numberToText($osCount) . " operating systems" : '');
    $this->addTextContent(ucfirst("{$browserCountText} user agents, built from {$engineCountText} rendering implementations, were tested{$osText}."));
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
    $this->addHyperlink($this->mTestSuite->getHomeURI(), null, $this->mTestSuite->getDateTime()->format('j F Y'));
    $this->addTextContent(" revision of the {$this->mSpec->getTitle()} test suite was used.");
    if (1 == $totalCount) {
      if (0 == $optionalCount) {
        $this->addTextContent(' The test suite consists of 1 test.');
      }
      else {
        $this->addTextContent(' The test suite consists of 1 test, which tests for optional behavior.');
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
      $this->addTextContent(" The test suite consists of {$totalCount} tests{$optionalText}.");
    }
    $this->closeElement('p');

    if (1 < count($formats)) {
      $this->addElement('p', null, 'These tests are available in several host language variants:');
      $this->openElement('ul');
      foreach ($formats as $format) {
        $this->openElement('li');
        $this->addHyperlink($this->mTestSuite->getBaseURI() . $format->getHomeURI(), null, $format->getDescription());
        $this->closeElement('li');
      }
      $this->closeElement('ul');
      $this->addElement('p', null, 'Some tests only apply to certain language variants; the results for each tested implementation are reported for each tested language variant. Also, not all tests are applicable to each implementation; for example, a browser which implements HTML but not XHTML is not tested for XHTML.');
    }

    $this->addElement('h2', array('id' => 'results'), 'Results');
   
    if (1 == $testCount) {
      if (0 < $passCount) {
        $this->addElement('p', null, 'In summary, the results show that the test was passed by at least two of the tested implementations.');
      }
      else {
        $this->addElement('p', null, 'In summary, the results show that the test was not passed by at least two of the tested implementations.');
      }
    }
    else {
      $testCountText = (($passCount == $testCount) ? "all {$testCount}" : "{$passCount} of {$testCount}");
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
    
    $this->openElement('p', null, FALSE);
    $this->addTextContent('Results were gathered from implementation reports submitted by user agent vendors as well as the general public via the ');
    $this->addHyperlink($this->buildConfigURI('page.home', null, null, TRUE), null, 'W3C Conformance Test Harness');
    $this->addTextContent('.');
    $this->closeElement('p');
    
    $this->openElement('ul');
    $this->openElement('li');
    $this->addHyperlink('results.html', null, 'Summary table of test result counts per rendering implementation');
    $this->closeElement('li');
    $this->addElement('li', null, 'Detailed results for each rendering implementation');
    $this->openElement('ul');
    foreach ($this->mEngineNames as $engineName) {
      $engine = new Engine($engineName);
      $this->openElement('li');
      $this->addHyperlink(strtolower("details_{$engineName}.html"), null, $engine->getTitle());
      $this->closeElement('li');
    }
    $this->closeElement('ul');
    $this->closeElement('ul');
  }
  
  
  function writeBodyFooter()
  {
    $this->addElement('hr');
    $this->addElement('address', null, '$Id$');
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
    echo "USAGE: php GenerateImplementationReport.php testsuite [output path]\n";
  }
  
  
  function findUserAgents()
  {
    $this->mUserAgents = array();
    $this->mUserAgentResultCounts = array();
    
    $userAgentIds = array();
    
    $testCases = $this->mResults->getTestCases();
    foreach ($testCases as $testCaseId => $testCaseData) {
      $engineResults = $this->mResults->getResultsFor($testCaseId);
      
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
    
    foreach ($this->mResults->getEngineNames() as $engineName) {
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
  function generate($testSuiteName, $outputPath)
  {
    $testSuite = new TestSuite($testSuiteName);
    if ($outputPath) {
      if (! file_exists($outputPath)) {
        mkdir($outputPath, 0777, TRUE);
      }
    }
    else {
      $outputPath = '';
    }
    
    if ($testSuite->isValid()) {
      
      echo "Loading results for {$testSuiteName}\n";
      
      $this->mResults = new Results($testSuite);
      
      $args['s'] = $testSuiteName;
      $args['o'] = 1; // section ordering
      
      echo "Generating results page\n";
      
      $resultsPage = new IR_ResultsPage($args);
      $resultsPage->setResults($this->mResults);
      $resultsPage->write($this->_combinePath($outputPath, 'results.html'));
      
      echo "Finding User Agents\n";

      $this->findUserAgents();
      
      echo "Generating index page\n";
      
      $indexPage = new IR_IndexPage($args);
      $indexPage->setStats($resultsPage->getStats());
      $indexPage->setEngineNames($this->mResults->getEngineNames());
      $indexPage->setUserAgents($this->mUserAgents, $this->mUserAgentResultCounts);
      $indexPage->write($this->_combinePath($outputPath, 'index.html'));

      if (UA_THRESHOLD < count($this->mUserAgents, COUNT_RECURSIVE)) {
        echo "Generating User Agent page\n";
        
        $uaPage = new IR_UserAgentPage($args);
        $uaPage->setResults($this->mResults);
        $uaPage->setUserAgents($this->mUserAgents, $this->mUserAgentResultCounts);
        $uaPage->write($this->_combinePath($outputPath, 'useragents.html'));
      }

      $engineNames = $this->mResults->getEngineNames();
      foreach ($engineNames as $engineName) {
        echo "Generating details page for {$engineName}\n";
        
        $args['e'] = $engineName;
        $detailsPage = new IR_DetailsPage($args);
        $detailsPage->setResults($this->mResults);
        $detailsPage->write($this->_combinePath($outputPath, strtolower("details_{$engineName}.html")));
      }
      
      // copy stylesheets
      if ($outputPath) {
        $this->copyFile(Config::Get('uri.stylesheet.base'), $this->_combinePath($outputPath, Config::Get('uri.stylesheet.base')));
        $this->copyFile(Config::Get('uri.stylesheet.report'), $this->_combinePath($outputPath, Config::Get('uri.stylesheet.report')));
      }
    }
  }
}

$worker = new GenerateImplementationReport();

$testSuiteName  = $worker->_getArg(1);
$outputPath     = $worker->_getArg(2);

if ($testSuiteName) {
  $worker->generate($testSuiteName, $outputPath);
}
else {
  $worker->usage();
}

?>