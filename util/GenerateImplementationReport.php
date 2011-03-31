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

require_once('lib/CmdLineWorker.php');
require_once('lib/TestSuite.php');
require_once('lib/Results.php');
require_once('pages/ResultsPage.php');
require_once('pages/DetailsPage.php');

  
/**
 * Class for generating simplified results table
 */
class IR_ResultsPage extends ResultsPage
{
  
  function __construct(Array $args = null)
  {
    parent::__construct($args);
    
    $this->mDisplayLinks = FALSE;
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
  }
  
  
  function getPageTitle()
  {
    $engine = $this->_getData('e');
    
    $title = parent::getPageTitle();
    if ($engine) {
      return "{$title} for {$engine}";
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
  }
}

  
class IR_UserAgentPage extends ResultsBasedPage
{  
  function __construct(Array $args = null)
  {
    parent::__construct($args);
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
      
      $userAgentIds = array();
      $testCases = $this->mResults->getTestCases();
      foreach ($testCases as $testCaseId => $testCaseData) {
        $engineResults = $this->mResults->getResultsFor($testCaseId);
        
        if ($engineResults) {
          foreach ($engineResults as $engine => $engineResultData) {
            foreach ($engineResultData as  $resultId => $resultValue) {
              $result = new Result($resultId);
              $userAgentIds[$engine][$result->getUserAgentId()] = TRUE;
            }
          }
        }
      }
      
      $userAgents = UserAgent::GetAllUserAgents();
      
      $this->openElement('ul');
      foreach ($this->mResults->getEngines() as $engine) {
        if (array_key_exists($engine, $userAgentIds)) {
          $this->addElement('li', null, $engine);
          $this->openElement('ul');
          $engineUserAgentIds = $userAgentIds[$engine];
          /// XXX sort ?
          foreach ($engineUserAgentIds as $userAgentId => $bool) {
            $userAgent = $userAgents[$userAgentId];
            $this->openElement('li');
            $this->addAbbrElement($userAgent->getUAString(), null, $userAgent->getDescription());
            $this->closeElement('li');
          }
          $this->closeElement('ul');
        }
        
      }
      $this->closeElement('ul');
    }
  }
  
  
  function writeBodyFooter()
  {
  }  
}
  

/**
 * This class exports the results database into a csv file
 *
 * This is meant to be run from by a periodic cron job or on the command line
 */
class GenerateImplementationReport extends CmdLineWorker
{
  
  function __construct() 
  {
    parent::__construct();
    
  }
  
  function usage()
  {
    echo "USAGE: php GenerateImplementationReport.php testsuite [output path]\n";
  }
  
  
  /**
   * Generate implementation report pages
   *
   */
  function generate($testSuiteName, $outputPath)
  {
    $testSuite = new TestSuite($testSuiteName);
    if ($outputPath) {
      mkdir($outputPath, 0777, TRUE);
    }
    else {
      $outputPath = '';
    }
    
    if ($testSuite->isValid()) {
      
      echo "Loading results for {$testSuiteName}\n";
      
      $results = new Results($testSuite);
      
      $args['s'] = $testSuiteName;
      
      echo "Generating results page\n";
      
      $resultsPage = new IR_ResultsPage($args);
      $resultsPage->setResults($results);
      $resultsPage->write($this->_combinePath($outputPath, 'results.html'));
      
      
      echo "Generating User Agent Page\n";
      
      $uaPage = new IR_UserAgentPage($args);
      $uaPage->setResults($results);
      $uaPage->write($this->_combinePath($outputPath, 'useragents.html'));

      
      $engines = $results->getEngines();
      foreach ($engines as $engine) {
        echo "Generating details page for {$engine}\n";
        
        $args['e'] = $engine;
        $detailsPage = new IR_DetailsPage($args);
        $detailsPage->setResults($results);
        $detailsPage->write($this->_combinePath($outputPath, strtolower("details_{$engine}.html")));
      }
      
      // XXX copy stylesheets
      // XXX generate index page
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