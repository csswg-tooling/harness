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

require_once('lib/CmdLineWorker.php');
require_once('lib/TestSuite.php');

/**
 * This class exports the results database into a csv file
 *
 * This is meant to be run from by a periodic cron job or on the command line
 */
class ExportResults extends CmdLineWorker
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
    $testSuite = new TestSuite($testSuiteName);
    
    if ($testSuite->isValid()) {
    
      $outFile = fopen($outPath, "wb");
      
      if ($outFile) {
        fwrite($outFile, "testcase,result,format,date,source,engine,useragent\n");
        
        $sql  = "SELECT `testsuite`, `format` ";
        $sql .= "FROM `testsuites` ";
        
        $r = $this->query($sql);
        while ($data = $r->fetchRow()) {
          $formats[$data['testsuite']] = $data['format'];
        }

        $testSuiteQuery = $testSuite->getSequenceQuery();
    
        $sql  = "SELECT `testcases`.`testcase`, `results`.`result`, `testcases`.`testsuite`, ";
        $sql .= "`results`.`modified`, `results`.`source`, `useragents`.`engine`, ";
        $sql .= "`useragents`.`useragent` ";
        $sql .= "FROM `results` INNER JOIN (`testcases`, `useragents`) ";
        $sql .= "ON `results`.`testcase_id` = `testcases`.`id` ";
        $sql .= "AND `results`.`useragent_id` = `useragents`.`id` ";
        $sql .= "WHERE `testcases`.`testsuite` LIKE '{$testSuiteQuery}' ";
        $sql .= "AND `testcases`.`active` = '1' ";
        $sql .= "AND `results`.`ignore` = '0' ";
        $sql .= "AND `results`.`result` != 'na' ";
        $sql .= "AND `results`.`revision` = `testcases`.`revision` ";
        $sql .= "ORDER BY `results`.`modified` ";

        $r = $this->query($sql);
        
        while ($data = $r->fetchRow()) {
          $testCaseName     = $this->_encode($data['testcase']);
          $result           = $this->_encode($data['result']);
          $testFormat       = $this->_encode($formats[$data['testsuite']]);
          $date             = $this->_encode($data['modified']);
          $source           = $this->_encode($data['source']);
          $engine           = $this->_encode($data['engine']);
          $userAgentString  = $this->_encode($data['useragent']);

          fwrite($outFile, "{$testCaseName},{$result},{$testFormat},{$date},{$source},${engine},{$userAgentString}\n");
          
        }
        
        fclose($outFile);
      }
    }
  }
}

$worker = new ExportResults();

$testSuiteName  = $argv[1];
$outPath        = $argv[2];

if ($testSuiteName && $outPath) {
  $worker->export($testSuiteName, $outPath);
}
else {
  $worker->usage();
}

?>