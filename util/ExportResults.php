<?php
/*******************************************************************************
 *
 *  Copyright © 2010-2011 Hewlett-Packard Development Company, L.P. 
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
require_once('lib/Results.php');
require_once('lib/Result.php');
require_once('lib/Format.php');
require_once('lib/UserAgent.php');
require_once('core/User.php');

/**
 * This class exports the results database into a csv file
 *
 * This is meant to be run from by a periodic cron job or on the command line
 */
class ExportResults extends HarnessCmdLineWorker
{

  function __construct() 
  {
    parent::__construct();

  }
  
  function usage()
  {
    echo "USAGE: php ExportResults.php testsuite outputfile\n";
  }
  
  
  protected function _encode($string)
  {
    if ((FALSE !== strpos($string, ',')) || (FALSE !== strpos($string, '"'))) {
      $string = '"' . str_replace('"', '""', $string) . '"';
    }
    return $string;
  }
  
  
  /**
   * Dump test results into a csv file
   *
   */
  function export($testSuiteName, $outPath)
  {
    $testSuite = @new TestSuite($testSuiteName);
    
    if ($testSuite->isValid()) {
    
      $outFile = fopen($outPath, "wb");
      
      if ($outFile) {
        fwrite($outFile, "testcase,result,format,date,source,engine,useragent\n");
        
        $formats = Format::GetFormatsFor($testSuite);
        $userAgents = UserAgent::GetAllUserAgents();
        $users = User::GetAllUsers();

        echo "Loading results for {$testSuiteName}\n";

        $results = new Results($testSuite);
        
        echo "Exporting\n";
        
        $testCases = $results->getTestCases();
        foreach ($testCases as $testCaseId => $testCaseData) {
          $engineResults = $results->getResultsFor($testCaseId);
          
          if ($engineResults) {
            ksort($engineResults);
            
            $testCaseName = $testCaseData['testcase'];
            
            foreach ($engineResults as $engine => $engineResultData) {
              asort($engineResultData);
              
              foreach ($engineResultData as  $resultId => $resultValue) {
                $result = new Result($resultId);
                $userAgent = $userAgents[$result->getUserAgentId()];
                $sourceId = $result->getSourceId();
              
                $testCaseName     = $this->_encode($testCaseName);
                $resultValue      = $this->_encode($resultValue);
                $testFormat       = $this->_encode($formats[$result->getFormatName()]->getTitle());
                $date             = $this->_encode($result->getDate());
                $engine           = $this->_encode($userAgent->getEngineTitle());
                $userAgentString  = $this->_encode($userAgent->getUAString());
                if ($sourceId) {
                  $user = $users[$sourceId];
                  $source = $this->_encode($user->getName());
                }
                else {
                  $source = '';
                }

                fwrite($outFile, "{$testCaseName},{$resultValue},{$testFormat},{$date},{$source},${engine},{$userAgentString}\n");
              }
            }
          }
        }
        
        fclose($outFile);
      }
    }
    else {
      echo "Unknown test suite\n";
    }
  }
}

$worker = new ExportResults();

$testSuiteName  = $worker->_getArg(1);
$outPath        = $worker->_getArg(2);

if ($testSuiteName && $outPath) {
  $worker->export($testSuiteName, $outPath);
}
else {
  $worker->usage();
}

?>