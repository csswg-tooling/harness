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


require_once('core/DBConnection.php');
require_once('lib/TestSuite.php');

/**
 * Wrapper class for information about a particular specification
 */
class Specification extends DBConnection
{
  protected $mInfo;


  /**
   * Construct Specification object
   *
   * @param string  $testSuiteName  Suite name
   */
  function __construct(TestSuite $testSuite) 
  {
    parent::__construct();

    $specName = $testSuite->getSpecName();
    if ($specName) {
      $specQuery = $this->encode($specName, 'specifications.spec');
      
      $sql  = "SELECT * FROM `specifications` ";
      $sql .= "WHERE `spec` = '{$specQuery}' ";
      $sql .= "LIMIT 1";
    
      $r = $this->query($sql);

      $this->mInfo = $r->fetchRow();
      if (! ($this->mInfo)) {
        $msg = "Unable to obtain information about specification: '{$specName}'";
        trigger_error($msg, E_USER_WARNING);
      }
    }
  }
  
  
  /**
   * Determine if valid specification data has been loaded
   *
   * @return bool
   */
  function isValid()
  {
    return ($this->mInfo && array_key_exists('spec', $this->mInfo));
  }
  

  /**
   * Get name of specification
   *
   * @return string
   */
  function getName()
  {
    return $this->mInfo['spec'];
  }


  /**
   * Get title of specification
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
    return $this->_combinePath($this->mInfo['base_uri'], $this->mInfo['home_uri']);
  }

}

?>