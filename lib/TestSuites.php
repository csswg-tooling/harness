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
//  class.test_suites.phi
//
//  In the Mobile Test Harness [1] listings of available test suites are hard
//  coded in harness.php. Herein herein we provide an object class to replace
//  such hardcoded lists with ones that are dynamically generated from the
//  results of a database query.
//
// [1] http://dev.w3.org/cvsweb/2007/mobile-test-harness/
//
//////////////////////////////////////////////////////////////////////////////// 

require_once("lib/DBConnection.php");

////////////////////////////////////////////////////////////////////////////////
//
//  class test_suites
//
//  test_suites is a concrete DBConnection class taylored for storing the 
//  table of contents of available test suites.
//
////////////////////////////////////////////////////////////////////////////////
class test_suites extends DBConnection
{
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Instance variables.
  //
  ////////////////////////////////////////////////////////////////////////////
  var $m_toc;

  ////////////////////////////////////////////////////////////////////////////
  //
  //  Constructor
  //
  //  Query the database for a list of available test suites which includes
  //  information about each suite required for generating a desired 
  //  human readable listing.
  //
  ////////////////////////////////////////////////////////////////////////////
  function __construct() 
  {
    parent::__construct();
    
    $sql  = "SELECT DISTINCT `testcases`.`testsuite`, `testsuites`.`base_uri`, ";
    $sql .= "`testsuites`.`home_uri`, `testsuites`.`spec_uri`, ";
    $sql .= "`testsuites`.`title`, `testsuites`.`description` ";
    $sql .= "FROM `testcases` LEFT JOIN `testsuites` ";
    $sql .= "ON `testcases`.`testsuite` = `testsuites`.`testsuite` ";
    $sql .= "WHERE `testsuites`.`active` = '1';";
    
    $r = $this->query($sql);
    
    if (! $r->succeeded()) {
      $msg = 'Unable to obtain list of test suites.';
      trigger_error($msg, E_USER_ERROR);
    }
    
    $this->m_toc = $r->fetchTable();

    if(!($this->m_toc)) {
      $msg = 'Unable to obtain list of test suites.';
      trigger_error($msg, E_USER_ERROR);
    }

  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  get_count
  //
  //  Return the number of test suites available.
  //
  ////////////////////////////////////////////////////////////////////////////
  function get_count()
  {
    return count($this->m_toc);
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write
  //
  //  Write HTML for a human readable listing of available test suites.
  //
  ////////////////////////////////////////////////////////////////////////////
  function write($indent)
  {
    echo "\n";

    echo $indent . "<dl>\n";

    foreach($this->m_toc as $testSuite) {
      echo $indent . "  <dt>\n";
      echo $indent . "    <a href='{$testSuite['base_uri']}{$testSuite['home_uri']}'>{$testSuite['title']}</a>\n";
      echo $indent . "    (<a href='testsuite?s={$testSuite['testsuite']}'>Enter Data</a>,\n";
      echo $indent . "    <a href='review?s={$testSuite['testsuite']}'>Review Results</a>)\n";
      echo $indent . "  </dt>\n";
      echo $indent . "  <dd>\n";
      echo $indent . "    {$testSuite['description']}\n";
      echo $indent . "  </dd>\n";
    }
    echo $indent . "</dl>\n";

    echo "\n";
  }

}
?>
