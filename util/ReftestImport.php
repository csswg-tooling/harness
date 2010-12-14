<?php
  ////////////////////////////////////////////////////////////////////////////////
  //
  //  Copyright © 2010 World Wide Web Consortium, 
  //  (Massachusetts Institute of Technology, European Research 
  //  Consortium for Informatics and Mathematics, Keio 
  //  University). All Rights Reserved. 
  //  Copyright © 2010 Hewlett-Packard Development Company, L.P. 
  // 
  //  This work is distributed under the W3C¬ Software License 
  //  [1] in the hope that it will be useful, but WITHOUT ANY 
  //  WARRANTY; without even the implied warranty of 
  //  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
  // 
  //  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
  //
  //////////////////////////////////////////////////////////////////////////////// 
  
  //////////////////////////////////////////////////////////////////////////////// 
  //
  //  ReftestImport.php
  //
  //////////////////////////////////////////////////////////////////////////////// 
  
  require_once("lib_test_harness/class.DBConnection.phi");
  
  ////////////////////////////////////////////////////////////////////////////////
  //
  //  class ReftestImport
  //
  //  Import reftest data from manifest file
  //
  ////////////////////////////////////////////////////////////////////////////////
  class ReftestImport extends DBConnection
  {  
    ////////////////////////////////////////////////////////////////////////////
    //
    //  Instance variables.
    //
    ////////////////////////////////////////////////////////////////////////////
    var $mTestCases;
    
    ////////////////////////////////////////////////////////////////////////////
    //
    //  Constructor.
    //
    ////////////////////////////////////////////////////////////////////////////
    function ReftestImport() 
    {
      parent::__construct();
      
    }
    
    protected function _loadTestCases($testSuite)
    {
      $sql  = "SELECT `id`, `testcase` FROM `testcases` ";
      $sql .= "WHERE `testsuite` = '{$testSuite}' ";
      
      $r = $this->query($sql);
      
      $testCases = $r->fetch_table();
      
      unset($this->mTestCases);
      
      foreach ($testCases as $testCase) {
        $this->mTestCases[$testCase['testcase']] = $testCase['id'];
      }
    }
    
    protected function _getFileName($path)
    {
      $pathInfo = pathinfo($path);
      
      if (isset($pathInfo['filename'])) { // PHP 5.2+
        return $pathInfo['filename'];
      }
      return basename($pathInfo['basename'], '.' . $pathInfo['extension']);
    }
    
    protected function _validate($value, $maxLength)
    {
      if ($maxLength < strlen($value)) {
        die("ERROR: data too long for database: '{$value}'");
      }
      if (FALSE !== strpos($value, "'")) {
        die("ERROR: data contains single quote: '{$value}'");
      }
    }
    
    
    function import($manifest, $testSuite, $baseURI)
    {
      $this->_loadTestCases($testSuite);
      
      $data = file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      
      foreach ($data as $record) {
        list ($type, $testCase, $reference) = explode(' ', $record);
        
        $testCase = $this->_getFileName($testCase);
        $uri = $baseURI . $reference;
        $reference = $this->_getFileName($reference);
        $testCaseID = $this->mTestCases[$testCase];
        
        $this->_validate($reference, 255);
        $this->_validate($uri, 255);
        if (('==' != $type) && ('!=' != $type)) {
          die("ERROR: bad type {$type}\n");
        }
        
        if (0 != $testCaseID) {
          $sql  = "INSERT INTO `reftests` (`testcase_id`, `reference`, `uri`, `type`) ";
          $sql .= "VALUES ('{$testCaseID}', '{$reference}', '{$uri}', '{$type}');";
          
          $this->query($sql);
          
          $sql  = "UPDATE `testcases` ";
          $sql .= "SET `flags` = CONCAT(`flags`,',reftest'), `modified` = `modified` ";
          $sql .= "WHERE `id` = {$testCaseID} ";
          
          $this->query($sql);
        }
        else {
          echo "ERROR: unknown testcase '{$testCase}'\n";
        }
        
      }
    }
  }
  
  $worker = new ReftestImport();

  $worker->import("reftest_html.list", "CSS21_HTML_RC4", "html4/");
  $worker->import("reftest_xhtml.list", "CSS21_XHTML_RC4", "xhtml1/");
  
?>