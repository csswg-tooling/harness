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
 * Wrapper class for flag data
 */
class Flags extends DBConnection
{
  protected $mFlags;
  protected $mDescriptions;
  protected $mTests;


  function __construct($flagsStr, $loadData = FALSE) 
  {
    parent::__construct();

    $this->mFlags = array();
    $flagArray = explode(',', $flagsStr);
    foreach ($flagArray as $flag) {
      $flag = trim($flag);
      if ($flag) {
        $this->mFlags[$flag] = $flag;
      }
    }
    if ($loadData) {
      $this->_loadData();
    }
  }
  
  
  protected function _loadData()
  {
    $sql  = "SELECT `flag`, `description`, ";
    $sql .= "`set_test`, `unset_test` ";
    $sql .= "FROM `flags` ";

    $r = $this->query($sql);
    while ($flagData = $r->fetchRow()) {
      $flag = $flagData['flag'];
      if (array_key_exists($flag, $this->mFlags)) {
        $this->mDescriptions[$flag] = $flagData['description'];
        $test = $flagData['set_test'];
      }
      else {
        $test = $flagData['unset_test'];
      }
      if ($test) {
        $this->mTests[$flag] = $test;
      }
    }
  }
  

  /**
   * Test for presence of particular flag
   */
  function hasFlag($flag)
  {
    if (is_array($this->mFlags)) {
      return array_key_exists($flag, $this->mFlags);
    }
    return FALSE;
  }
  
  
  /**
   * Get array of flag descriptions
   *
   * @return array|null descriptions of all set flags, keyed by flag
   */
  function getDescriptions()
  {
    return $this->mDescriptions;
  }


  /**
   * Get array of flag tests
   *
   * @return array|null all available tests keyed by flag
   */
  function getTests()
  {
    return $this->mTests;
  }
  
  
  function addFlag($flag, $loadData = FALSE)
  {
    $this->mFlags[$flag] = $flag;
    
    if ($loadData) {
      $this->_loadData();
    }
  }
  
  
  function removeFlag($flag)
  {
    unset($this->mFlags[$flag]);
    unset($this->mDescriptions[$flag]);
    unset($this->mTests[$flag]);
  }
  
  
  function getFlagString()
  {
    uksort($this->mFlags, 'strnatcasecmp');
    return implode(',', $this->mFlags);
  }
}

?>