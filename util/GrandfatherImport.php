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
  //  GrandfatherImport.php
  //
  //////////////////////////////////////////////////////////////////////////////// 
  
  require_once("lib_test_harness/class.DBConnection.phi");
  
  ////////////////////////////////////////////////////////////////////////////////
  //
  //  class GrandfatherImport
  //
  //  Import grandfather data from diff file
  //
  ////////////////////////////////////////////////////////////////////////////////
  class GrandfatherImport extends DBConnection
  {  
    ////////////////////////////////////////////////////////////////////////////
    //
    //  Instance variables.
    //
    ////////////////////////////////////////////////////////////////////////////
    var $mTestCases;
    var $mDiffs;
    
    ////////////////////////////////////////////////////////////////////////////
    //
    //  Constructor.
    //
    ////////////////////////////////////////////////////////////////////////////
    function __construct() 
    {
      parent::__construct();
      
    }
    
    function loadTestCases($testSuite)
    {
      $sql  = "SELECT id, testcase FROM testcases ";
      $sql .= "WHERE testsuite='{$testSuite}' ";
      
      $r = $this->query($sql);
      
      $testCases = $r->fetch_table();
      
      foreach ($testCases as $testCase) {
        $this->mTestCases[$testCase['testcase']] = $testCase['id'];
      }
    }
    
    function _getFileName($path)
    {
      $pathInfo = pathinfo($path);
      
      if (isset($pathInfo['filename'])) { // PHP 5.2+
        return $pathInfo['filename'];
      }
      return basename($pathInfo['basename'], '.' . $pathInfo['extension']);
    }

    
    function loadDiffs($diffFile)
    {
      $data = file($diffFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Data is expected in standard diff -qr output, ie:      
//Files 20101027/html4/absolute-replaced-width-004.htm and 20101210/html4/absolute-replaced-width-004.htm differ
//Files 20101027/html4/absolute-replaced-width-006.htm and 20101210/html4/absolute-replaced-width-006.htm are identical
//Only in 20101027/html4/: absolute-replaced-width-007.htm
      
      foreach ($data as $record) {
        $fields = explode(' ', $record);
        if ("Files" == $fields[0]) {
          $testCase = $this->_getFileName($fields[1]);
          $this->mDiffs[$testCase] = ('identical' == end($fields));
        }
        else {
          if ("Only" == $fields[0]) {
            $testCase = $this->_getFileName(end($fields));
            $this->mDiffs[$testCase] = FALSE;
          }
          else {
            exit("ERROR: unknown diff file format\n");
          }
        }
      }
    }
    
    function grandfather($testSuite)
    {
      $this->loadTestCases($testSuite);
      
      foreach ($this->mTestCases as $testCase => $testCaseID) {
        if (isset($this->mDiffs[$testCase])) {
          $identical = $this->mDiffs[$testCase];
        }
        else {
          $identical = FALSE;
          echo "No data for {$testSuite}:{$testCase}\n";
        }
        
        if (0 != $testCaseID) {
          if ($identical) {
            $sql  = "UPDATE `testcases` ";
            $sql .= "SET `grandfather` = '1', `modified` = `modified` ";
            $sql .= "WHERE id = '{$testCaseID}'";
          }
          else {
            $sql  = "UPDATE `testcases` ";
            $sql .= "SET `grandfather` = '0', `testgroup` = 'changed', `modified` = `modified` ";
            $sql .= "WHERE id = '{$testCaseID}'";
          }
          $this->query($sql);
        }
        else {
          exit("ERROR: unknown testcase '{$testCase}'\n");
        }
      }
    }
  }
  
  $worker = new GrandfatherImport();

  $worker->loadDiffs("out.diff");
  $worker->grandfather("CSS21_HTML_RC4");
  $worker->grandfather("CSS21_XHTML_RC4");
  
?>