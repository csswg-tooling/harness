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
 * Class to load result details
 */
class Result extends DBConnection
{
  protected $mInfo;


  function __construct($resultId)
  {
    parent::__construct();
    
    $sql  = "SELECT * ";
    $sql .= "FROM `results` ";
    $sql .= "WHERE `id` = '{$resultId}' ";

    $r = $this->query($sql);

    $this->mInfo = $r->fetchRow();
  }


  /**
   * Determine if valid result data has been loaded
   *
   * @return bool
   */
  function isValid()
  {
    return ($this->mInfo && array_key_exists('id', $this->mInfo) && (0 < $this->mInfo['id']));
  }
  

  /**
   * Get result info
   *
   * @return string
   */
  function getId()
  {
    if ($this->isValid()) {
      return intval($this->mInfo['id']);
    }
    return FALSE;
  }
  
  function getTestCaseId()
  {
    if ($this->isValid()) {
      return intval($this->mInfo['testcase_id']);
    }
    return FALSE;
  }
  
  function getRevision()
  {
    if ($this->isValid()) {
      return intval($this->mInfo['revision']);
    }
    return FALSE;
  }
  
  function getFormatName()
  {
    if ($this->isValid()) {
      return $this->mInfo['format'];
    }
    return FALSE;
  }
  
  function getUserAgentId()
  {
    if ($this->isValid()) {
      return intval($this->mInfo['useragent_id']);
    }
    return FALSE;
  }
  
  function getSourceId()
  {
    if ($this->isValid()) {
      return intval($this->mInfo['source_id']);
    }
    return FALSE;
  }
  
  function getSourceUserAgentId()
  {
    if ($this->isValid()) {
      return intval($this->mInfo['source_useragent_id']);
    }
    return FALSE;
  }
  
  function getResult()
  {
    if ($this->isValid()) {
      return $this->mInfo['result'];
    }
    return FALSE;
  }
  
  function getComment()
  {
    if ($this->isValid()) {
      return $this->mInfo['comment'];
    }
    return FALSE;
  }
  
  function getIgnore()
  {
    if ($this->isValid()) {
      return intval($this->mInfo['ignore']);
    }
    return FALSE;
  }
  
  function getDate()
  {
    if ($this->isValid()) {
      return $this->mInfo['modified'];
    }
    return FALSE;
  }
  
}

?>