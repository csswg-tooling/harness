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
require_once('core/IPAddress.php');

/**
 * Wrapper class for information about a particular User Agent 
 */
class User extends DBConnection
{
  protected $mInfo;


  /**
   * Load array of all known users
   */
  static function GetAllUsers()
  {
    $users = array();
    
    $sql  = "SELECT * ";
    $sql .= "FROM `users` ";
    $sql .= "ORDER BY `id` ";

    $db = new DBConnection('users');
    $r = $db->query($sql);

    while ($data = $r->fetchRow()) {
      $userId = intval($data['id']);
      
      $users[$userId] = new User($data);
    }
    return $users;
  }




  /**
   * @param int,string,array
   */
  function __construct($data = FALSE) 
  {
    parent::__construct('users');
    
    if ($data) {
      if (is_integer($data)) {
        $this->mInfo = $this->_queryById($data);
      }
      elseif (is_string($data)) {
        $this->mInfo = $this->_queryByString($data);
        
        if (! $this->mInfo) {
          $this->mInfo['user'] = $data;
        }
      }
      elseif (is_object($data) && is_a($data, 'IPAddress')) {
        $this->mInfo = $this->_queryByIPAddress($data);

        if (! $this->mInfo) {
          $this->mInfo['ip_address'] = $data->getIPv6String();
        }
      }
      elseif (is_array($data)) {
        $this->mInfo = $data;
      }
    }
    
    if (! isset($this->mInfo)) {  // passed a valid source id or string
      $ipAddress = IPAddress::GetClientIP();
      
      $this->mInfo = $this->_queryByIPAddress($ipAddress);
    }
  }


  /**
   * Lookup user info by numeric ID
   *
   * @param int $id database ID
   * @return array
   */
  protected function _queryById($id) 
  {
    $id = intval($id);
    $sql  = "SELECT * ";
    $sql .= "FROM `users` ";
    $sql .= "WHERE id = '{$id}' ";
    $sql .= "LIMIT 1";

    $r = $this->query($sql);

    if ($r->succeeded()) {
      $data = $r->fetchRow();
      if ($data) {
        return $data;
      }
    }
    return null;
  }


  /**
   * Lookup user info by source string
   *
   * @param string $source Source String
   * @return array
   */
  protected function _queryByString($userName) 
  {
    $userName = $this->encode($userName, 'users.user');
    
    $sql  = "SELECT * ";
    $sql .= "FROM `users` ";
    $sql .= "WHERE `user` = '{$userName}' ";
    $sql .= "LIMIT 1 ";

    $r = $this->query($sql);

    if ($r->succeeded()) {
      $data = $r->fetchRow();
      if ($data) {
        return $data;
      }
    }
    return null;
  }


  /**
   * Lookup user info by IP address
   *
   * @param IPAddress $ipAddress IP Address
   * @return array
   */
  protected function _queryByIPAddress(IPAddress $ipAddress) 
  {
    $ipString = $this->encode($ipAddress->getIPv6String(), 'users.ip_address');
    
    $sql  = "SELECT * ";
    $sql .= "FROM `users` ";
    $sql .= "WHERE `ip_address` = '{$ipString}' ";
    $sql .= "LIMIT 1 ";

    $r = $this->query($sql);

    if ($r->succeeded()) {
      $data = $r->fetchRow();
      if ($data) {
        return $data;
      }
    }
    return null;
  }


  /**
   * Write info into database if not loaded from there
   */
  function update()
  {
    if ((! isset($this->mInfo['id'])) && (isset($this->mInfo['ip_address']))) {
      $ipString = $this->encode($this->mInfo['ip_address'], 'users.ip_address');
      
      $sql  = "INSERT INTO `users` ";
      $sql .= "(`ip_address`) ";
      $sql .= "VALUES ('{$ipString}')";
      $r = $this->query($sql);
      $this->mInfo['id'] = $this->lastInsertId();
    }
  }
    

  function getId()
  {
    return intval($this->mInfo['id']);
  }

  function getName()
  {
    return $this->mInfo['user'];
  }
  
  function getFullName()
  {
    return $this->mInfo['full_name'];
  }
  
  function getIPAddress()
  {
    return new IPAddress($this->mInfo['ip_address']);
  }

}

?>