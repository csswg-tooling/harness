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

require_once("lib/DBConnection.php");

/**
 * Wrapper class for format data
 */
class Format extends DBConnection
{
  protected $mInfo;


  /**
   * Load array of all known users
   */
  static function GetFormatsFor(TestSuite $testSuite)
  {
    $formats = array();
    
    $testSuiteFormats = $testSuite->getFormats();
    
    $sql  = "SELECT * ";
    $sql .= "FROM `formats` ";
    $sql .= "ORDER BY `format` ";

    $db = new DBConnection();
    $r = $db->query($sql);

    while ($data = $r->fetchRow()) {
      $format = $data['format'];
      
      if (in_array($format, $testSuiteFormats)) {
        $formats[$format] = new Format($data);
      }
    }
    return $formats;
  }


  function __construct($data)
  {
    parent::__construct();
    
    if (is_array($data)) {
      $this->mInfo = $data;
    }
    elseif (is_string($data)) {
      $format = $this->encode($data);
      
      $sql  = "SELECT * ";
      $sql .= "FROM `formats` ";
      $sql .= "WHERE `format` = '{$format}' ";
    
      $r = $this->query($sql);
      
      $this->mInfo = $r->fetchRow();
    }
  }
  

  protected function _isValid()
  {
    return (is_array($this->mInfo) && array_key_exists('format', $this->mInfo));
  }
  
  
  function getName()
  {
    if ($this->_isValid()) {
      return $this->mInfo['format'];
    }
    return FALSE;
  }
  
  
  /**
   * Get title for format
   *
   * @return string|null title of format
   */
  function getTitle()
  {
    if ($this->_isValid()) {
      return $this->mInfo['title'];
    }
    return null;
  }


  /**
   * Get extension for format
   *
   * @return string|null extension of format
   */
  function getPath()
  {
    if ($this->_isValid()) {
      return $this->mInfo['path'];
    }
    return null;
  }

  /**
   * Get extension for format
   *
   * @return string|null extension of format
   */
  function getExtension()
  {
    if ($this->_isValid()) {
      return $this->mInfo['extension'];
    }
    return null;
  }


  /**
   * Test if a given format is valid for test flags
   *
   * @param array $flagArray
   * @return bool
   */
  function validForFlags($flagArray)
  {
    if ($this->_isValid()) {
      $filter = $this->mInfo['filter'];
      if (in_array($filter, $flagArray)) {
        return FALSE;
      }
      return TRUE;
    }
    return FALSE;
  }

}

?>