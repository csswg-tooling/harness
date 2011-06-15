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

/**
 * Wrapper class for engine data
 * 
 * Since there isn't an engine table yet, fake it by getting data from useragents
 */
class Engine extends DBConnection
{
  protected $mInfo;


  /**
   * Load array of all known user engines
   */
  static function GetAllEngines()
  {
    $engines = array();
    
    $sql  = "SELECT DISTINCT `engine` ";
    $sql .= "FROM `useragents` ";
    $sql .= "ORDER BY `engine` ";

    $db = new DBConnection();
    $r = $db->query($sql);

    while ($data = $r->fetchRow()) {
      $engineName = strtolower($data['engine']);
      if ($engineName) {
        $data['title'] = $data['engine'];
        $data['engine'] = $engineName;
        $engines[$engineName] = new Engine($data);
      }
    }
    return $engines;
  }



  function __construct($data)
  {
    parent::__construct();
    
    if (is_array($data)) {
      $this->mInfo = $data;
    }
    elseif (is_string($data)) {
      $engineName = $this->encode($data);
      
      $sql  = "SELECT `engine` ";
      $sql .= "FROM `useragents` ";
      $sql .= "WHERE `engine` = '{$engineName}' ";
      $sql .= "LIMIT 0, 1 ";
    
      $r = $this->query($sql);
      
      $this->mInfo = $r->fetchRow();
      if (is_array($this->mInfo)) {
        $engineName = strtolower($this->mInfo['engine']);
        $this->mInfo['title'] = $this->mInfo['engine'];
        $this->mInfo['engine'] = $engineName;
      }
    }
  }
  

  protected function _isValid()
  {
    return (is_array($this->mInfo) && array_key_exists('engine', $this->mInfo));
  }
  
  
  /**
   * Get name of engine
   *
   * normalized (lower case) name suitable for db queries and array keys
   */
  function getName()
  {
    if ($this->_isValid()) {
      return $this->mInfo['engine'];
    }
    return FALSE;
  }
  
  
  /**
   * Get title for engine
   *
   * @return string|null title of engine
   */
  function getTitle()
  {
    if ($this->_isValid()) {
      return $this->mInfo['title'];
    }
    return null;
  }

}

?>