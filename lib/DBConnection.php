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


require_once('lib/Config.php');
require_once('lib/DBResult.php');

/**
 * Manage connection to the MySQL database
 */
class DBConnection
{
  protected $mDatabaseLink;
  
  
  /**
   * Connect to the database
   *
   * Gets database host, user and password from config file
   *
   * By default connections will be reused so it is safe to create multiple 
   * connection instances
   */
  function __construct($dbName = '', $newLink = FALSE)
  {  
    $this->mDatabaseLink = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD, $newLink);
    
    if (0 == strlen($dbName)) {
      $dbName = DB_NAME;
    }
    if ($this->isConnected()) {
      mysql_query("use `{$dbName}`;", $this->mDatabaseLink);
    }
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
    $result = mysql_query($queryString, $this->mDatabaseLink);
    
    if ((defined('DEBUG_MODE') && DEBUG_MODE) || 
        (defined('COMMAND_LINE') && COMMAND_LINE)) {
      if (FALSE === $result) {
        echo ($this->getErrorString() . "\n");
      }
    }
    return new DBResult($result);
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
   * Get string representation of last error
   */
  function getErrorString()
  {
    return mysql_error($this->mDatabaseLink);
  }
  
  
  /**
   * Get current timestamp from server
   */
  function getNow()
  {
    $sql  = "SELECT CURRENT_TIMESTAMP";
    $r = $this->query($sql);
  
    return $r->fetchField(0, 'CURRENT_TIMESTAMP');
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
    return mysql_real_escape_string(trim($string), $this->mDatabaseLink);
  }
  
  
  /**
   * Utility function, split a string into an array and 
   * trim leading and trailing spaces from components
   *
   * @param string delimiter
   * @param string string to split
   * @param int|bool optional component limit count, more components will be unsplit in last entry
   * @return array
   */
  protected function _explodeAndTrim($delimiter, $string, $limit = FALSE)
  {
    $result = array();
    
    if (FALSE !== $limit) {
      $array = explode($delimiter, $string, $limit);
    }
    else {
      $array = explode($delimiter, $string);
    }
    foreach($array as $field) {
      $result[] = trim($field);
    }
    
    return $result;
  }


  /**
   * Utility function, split a string into an array and 
   * trim leading and trailing spaces from components
   * exclude empty components from result
   *
   * @param string delimiter
   * @param string string to split
   * @param int|bool optional component limit count, more components will be unsplit in last entry
   * @return array
   */
  protected function _explodeTrimAndFilter($delimiter, $string, $limit = FALSE)
  {
    $result = array();
    
    if (FALSE !== $limit) {
      $array = explode($delimiter, $string, $limit);
    }
    else {
      $array = explode($delimiter, $string);
    }
    foreach($array as $field) {
      $field = trim($field);
      if ($field) {
        $result[] = $field;
      }
    }
    
    return $result;
  }
  
}

?>