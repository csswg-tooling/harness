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
  //  FixFlags.php
  //
  //////////////////////////////////////////////////////////////////////////////// 
  
  require_once("lib_test_harness/class.DBConnection.phi");
  
  ////////////////////////////////////////////////////////////////////////////////
  //
  //  class FixFlags
  //
  //  Import test case flags from manifest file
  //
  ////////////////////////////////////////////////////////////////////////////////
  class FixFlags extends DBConnection
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
    function __construct() 
    {
      parent::__construct();
      
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
    
    function import($manifest, $testSuite)
    {
      $this->_validate($testSuite, 32);
      
      echo "Loading test cases\n";
      
      $this->_loadTestCases($testSuite);
      
      echo "Loading manifest\n";
      
      $data = file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      
      echo "Updating flags\n";
      
      $count = 0;
      foreach ($data as $record) {
        if (0 == $count++) {
          if ("id\treferences\ttitle\tflags\tlinks\tassertion" == $record) {
            continue;
          }
          die("ERROR: unknown format\n");
        }
        list ($testCase, $references, $title, $flags, $links, $assertion) = explode("\t", $record);
        
        $testCaseId = $this->mTestCases[$testCase];
        
        $sql  = "UPDATE `testcases` SET `flags` = '{$flags}', `modified` = `modified` ";
        $sql .= "WHERE `id` = '{$testCaseId}' ";
        
        $this->query($sql);
      }
    }
  }
  
  $worker = new FixFlags();

  $worker->import("testinfo.data", "CSS21_HTML_RC4");
  $worker->import("testinfo.data", "CSS21_XHTML_RC4");
  
?>