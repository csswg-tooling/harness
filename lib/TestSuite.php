<?php
/*******************************************************************************
 *
 *  Copyright © 2008-2011 Hewlett-Packard Development Company, L.P. 
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


require_once("lib/DBConnection.php");

/**
 * Wrapper class for information about a particular test suite
 */
class TestSuite extends DBConnection
{
  protected $mInfo;


  /**
   * Construct TestSuite object
   *
   * @param string  $testSuiteName  Suite name
   */
  function __construct($testSuiteName) 
  {
    parent::__construct();
    
    if ($testSuiteName) {
      $testSuiteQuery = $this->encode($testSuiteName, TESTSUITES_MAX_TESTSUITE);
      
      $sql  = "SELECT * FROM `testsuites` ";
      $sql .= "WHERE `testsuite` = '{$testSuiteQuery}' ";
      $sql .= "LIMIT 1";
    
      $r = $this->query($sql);

      $this->mInfo = $r->fetchRow();
      if (! ($this->mInfo)) {
        $msg = "Unable to obtain information about test suite: '{$testSuiteName}'";
        trigger_error($msg, E_USER_WARNING);
      }
    }
  }
  
  
  /**
   * Determine if valid test suite data has been loaded
   *
   * @return bool
   */
  function isValid()
  {
    return (null != $this->mInfo);
  }
  

  /**
   * Get name of Test Suite
   *
   * @return string
   */
  function getName()
  {
    return $this->mInfo['testsuite'];
  }


  /**
   * Get title of test suite
   *
   * @return string
   */
  function getTitle()
  {
    return $this->mInfo['title'];
  }
  
  
  function getDescription()
  {
    return $this->mInfo['description'];
  }
  
  function getBaseURI()
  {
    return $this->mInfo['base_uri'];
  }

  function getHomeURI()
  {
    return $this->mInfo['base_uri'] . $this->mInfo['home_uri'];
  }

  function getSpecURI()
  {
    return $this->mInfo['spec_uri'];
  }
  
  function getContactName()
  {
    return $this->mInfo['contact_name'];
  }

  function getContactURI()
  {
    return $this->mInfo['contact_uri'];
  }

  function getFormat()
  {
    return $this->mInfo['format'];
  }

  function getSequenceQuery()
  {
    return $this->mInfo['sequence_query'];
  }

}

?>