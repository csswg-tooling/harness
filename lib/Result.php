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
 
require_once('lib/HarnessDB.php');


/**
 * Class to load result details
 */
class Result extends HarnessDBEntity
{

  protected function _queryById($id)
  {
    $sql  = "SELECT * ";
    $sql .= "FROM `results` ";
    $sql .= "WHERE `id` = {$id} ";

    $r = $this->query($sql);

    $data = $r->fetchRow();
    return $data;
  }


  /**
   * Determine if valid result data has been loaded
   *
   * @return bool
   */
  function isValid()
  {
    return (0 < $this->getId());
  }
  

  /**
   * Get result info
   *
   * @return string
   */
  function getId()
  {
    return $this->_getIntValue('id');
  }
  
  function getTestCaseId()
  {
    return $this->_getIntValue('testcase_id');
  }
  
  function getRevision()
  {
    return $this->_getStrValue('revision');
  }
  
  function getFormatName()
  {
    return $this->_getStrValue('format');
  }
  
  function getUserAgentId()
  {
    return $this->_getIntValue('user_agent_id');
  }
  
  function getUserId()
  {
    return $this->_getIntValue('user_id');
  }
  
  function getUserUserAgentId()
  {
    return $this->_getIntValue('user_user_agent_id');
  }
  
  function getResult()
  {
    return $this->_getStrValue('result');
  }
  
  function getPassCount()
  {
    return $this->_getIntValue('pass_count');
  }
  
  function getFailCount()
  {
    return $this->_getIntValue('fail_count');
  }
  
  function getComment()
  {
    return $this->_getStrValue('comment');
  }
  
  function getIgnore()
  {
    return $this->_getIntValue('ignore');
  }
  
  function getDateTime()
  {
    return $this->_getDateTimeValue('modified');
  }
  
  function ignore($comment = null)
  {
    $sql  = "UPDATE `results` ";
    $sql .= "SET `ignore` = 1, `modified` = `modified` ";
    if ($comment) {
      $this->encode($comment);
      $sql .= ", `comment` = {$comment} ";
    }
    $sql .= "WHERE `id` = {$this->getId()} ";
    $this->query($sql);

    $this->_setIntValue('ignore', 1);
  }
}

?>