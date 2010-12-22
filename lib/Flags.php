<?php
////////////////////////////////////////////////////////////////////////////////
//
//  Copyright © 2008 Hewlett-Packard Development Company, L.P. 
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
//  class.test_flags.phi
//
//  Provides functionalities for acessing, storing, and displaying specific
//  requirements for individual test cases.
//
//////////////////////////////////////////////////////////////////////////////// 

require_once("lib/DBConnection.php");

////////////////////////////////////////////////////////////////////////////////
//
//  class test_flags
//
//  test_flags is a concrete DBConnection taylored for storing a list 
//  of client requirements for a particular test case.
//
////////////////////////////////////////////////////////////////////////////////
class test_flags extends DBConnection
{
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Instance variables.
  //
  ////////////////////////////////////////////////////////////////////////////
  var $m_flags;

  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor
  //
  //  Query the database for information about the client requirements
  //  of a particular test case. Store the results.
  //
  ////////////////////////////////////////////////////////////////////////////
  function __construct($test_suite, $test_case) 
  {
    parent::__construct();

    $sql  = "SELECT flag, description ";
    $sql .= "FROM testcases ";
    $sql .= "LEFT JOIN flags ";
    $sql .= "ON FIND_IN_SET(flags.flag, testcases.flags) ";
    $sql .= "WHERE testsuite='{$test_suite}' ";
    $sql .= "AND testcase='{$test_case}' ";

    $r = $this->query($sql);
    while ($flag = $r->fetchRow()) {
      $this->m_flags[$flag['flag']] = $flag['description'];
    }
  }
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  has_flag
  //
  ////////////////////////////////////////////////////////////////////////////
  function has_flag($flag)
  {
    return array_key_exists($flag, $this->m_flags);
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write
  //
  ////////////////////////////////////////////////////////////////////////////
  function write($indent)
  {
    if ($this->m_flags && count($this->m_flags)) {
      echo $indent . "<p class='notes'>\n";
      foreach($this->m_flags as $flag => $description) {
        echo $indent . "  {$description}\n";
      }
      echo $indent . "</p>\n";
    }
  }

}

?>