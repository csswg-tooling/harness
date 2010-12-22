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
//  ir_import.php
//
//  Adapted from Mobile Test Harness [1]
//
//    File: resequence.php
//      Lines: 103-142
//
//  where herein specific contents provided by the original harness have
//  been adapted for CSS2.1 conformance testing. Separately, controls have
//  been added to allow entering data for user agents other than the one
//  accessing the harness, and the means by which test presentation order
//  is provided have been altered. Separately, the ability to request
//  only those tests in a particular named group has been added.
//
// [1] http://dev.w3.org/cvsweb/2007/mobile-test-harness/
//
//////////////////////////////////////////////////////////////////////////////// 

require_once("lib/DBConnection.php");

////////////////////////////////////////////////////////////////////////////////
//
//  class ir_import
//
//  This class imports data from implementation reports
//
//  This is meant to be run on the command line
//
////////////////////////////////////////////////////////////////////////////////
class ir_import extends DBConnection
{  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Instance variables.
  //
  ////////////////////////////////////////////////////////////////////////////

  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor.
  //
  ////////////////////////////////////////////////////////////////////////////
  function __construct() 
  {
    parent::_construct();

  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  Read data from ir_import table, lookup testcase id and update
  //
  ////////////////////////////////////////////////////////////////////////////
  function import()
  {
    $sql =  "SELECT id, testsuite, testcase FROM testcases";
    $r = $this->query($sql);
    
    while ($result = $r->fetchRow()) {
      $testcase_id = $result['id'];
      $test_suite  = $result['testsuite'];
      $test_case   = $result['testcase'];
      
      $id[$test_suite][$test_case] = $testcase_id;
    }
    
    $sql =  "SELECT * FROM ir_import WHERE testcase_id='0'";
    
    $r = $this->query($sql);
    while ($result = $r->fetchRow()) {
      $test_suite   = $result['testsuite'];
      $test_case    = $result['testcase'];
      
      print "{$test_suite} / {$test_case} = ";
      $testcase_id = $id[$test_suite][$test_case];
      print "{$testcase_id}\n";        
      if (0 < $testcase_id) {
        $sql  = "UPDATE ir_import SET testcase_id='{$testcase_id}' ";
        $sql .= "WHERE testsuite='{$test_suite}' AND testcase='{$test_case}'";
        
        $this->query($sql);
      }
    }
    
    
/*    
    $sql =  "SELECT * FROM ir_import WHERE testcase_id='0'";
    
    $r = $this->query($sql);
    $data = $r->fetchTable();
    
    foreach ($data as $result) {
      $test_suite   = $result['testsuite'];
      $test_case    = $result['testcase'];
      $useragent_id = $result['useragent_id'];
      $source       = $result['source'];
      $modified     = $result['modified'];
      
print "{$test_suite} / {$test_case} = ";
      $sql  = "SELECT id FROM testcases ";
      $sql .= "WHERE testsuite='{$test_suite}' AND testcase='{$test_case}'";
      
      $r = $this->query($sql);
      if ($r->succeeded()) {
        $testcase_id = $r->fetchField(0, 'id');
print "{$testcase_id}\n";        
        if (0 < $testcase_id) {
          $sql  = "UPDATE ir_import SET testcase_id='{$testcase_id}' ";
          $sql .= "WHERE testsuite='{$test_suite}' AND testcase='{$test_case}'";
          
          $this->query($sql);
        }
      }
    }
*/
    
/*
 
 INSERT INTO results (testcase_id, useragent_id, source, result, modified) 
 SELECT testcase_id, useragent_id, source, result, modified FROM ir_import

 UPDATE ir_import, testcases SET ir_import.testcase_id=testcases.id 
 WHERE ir_import.testsuite=testcases.testsuite AND ir_import.testcase=testcases.testcase
 
 UPDATE ir_import LEFT JOIN testcases ON ir_import.testsuite=testcases.testsuite AND ir_import.testcase=testcases.testcase
 SET testcase_id=id 
 */
  }
}

$worker = new ir_import();
$worker->import();

?>