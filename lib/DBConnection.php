<?php
/*******************************************************************************
 *
 *  Copyright © 2008-2010 Hewlett-Packard Development Company, L.P. 
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


require_once('Config.php');
require_once('DBResult.php');

/**
 * Manage connection to the MySQL database
 */
class DBConnection
{
  var $mDatabaseLink;
  
  /**
   * Connect to the database
   *
   * Gets database host, user and password from config file
   *
   * Connections will be reused so it is safe to create multiple 
   * connection instances
   */
  function __construct()
  {  
    $this->mDatabaseLink = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
    mysql_query('use '.DB_NAME.';', $this->mDatabaseLink);
  }

  /**
   * Test for functional connection to database
   *
   * @return bool
   */
  function isConnected()
  {
    if ($this->mDatabaseLink) {
      return TRUE;
    }
    return FALSE;
  }
  
  /**
   * Execute a database query
   *
   * @param string $queryString
   * @return DBResultSet
   */
  function query($queryString) 
  {
    return new DBResult(mysql_query($queryString, $this->mDatabaseLink));
  }
  
  /**
   * Get id of last insert
   */
  function lastInsertId()
  {
    return mysql_insert_id($this->mDatabaseLink);
  }
  
  /**
   * Get number of rows affected by last operation
   */
  function affectedRowCount()
  {
    return mysql_affected_rows($this->mDatabaseLink);
  }
  
  /**
   * Make a string safe for SQL queries
   */
  function encode($string, $maxLength = 0)
  {
    assert('is_numeric($maxLength)');
    
    if (0 < $maxLength) {
      assert('strlen($string) <= $maxLength');
      return mysql_real_escape_string(substr(trim($string), 0, $maxLength), $this->mDatabaseLink);
    }
    return mysql_real_escape_string($string, $this->mDatabaseLink);
  }
  
}

?>