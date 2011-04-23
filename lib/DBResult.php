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


/**
 * Encapsulate a MySQL result resource handle and provide an interface
 *
 * DBResults are generally constructed by DBConnections in response to a query
 */
class DBResult
{
  protected $mResult;
  
  /**
   * Store MySQL result resource or bool value
   */
  function __construct($result) 
  {
    $this->mResult = $result;
  }
  
  /**
   * Test if query succeeded
   *
   * @return bool
   */
  function succeeded()
  {
    if ($this->mResult) {
      return TRUE;
    }
    return FALSE;
  }
  
  
  /**
   * Get number of rows in result
   *
   * @return bool|int Number of rows in result or FALSE
   */
  function rowCount()
  {
    if (is_resource($this->mResult)) {
      return mysql_num_rows($this->mResult);
    }
    return FALSE;
  }
  
  /**
   * Set internal data pointer to specific row
   *
   * @return bool
   */
  function seekRow($rowIndex)
  {
    if (is_resource($this->mResult)) {
      return mysql_data_seek($this->mResult, $rowIndex);
    }
    return FALSE;
  }

  /**
   * Get a single field
   *
   * @param int $rowIndex
   * @param int|string $field
   * @return bool|string
   */
  function fetchField($rowIndex, $field = 0)
  {
    if (is_resource($this->mResult) && ($rowIndex < $this->rowCount())) {
      return mysql_result($this->mResult, $rowIndex, $field);
    }
    return FALSE;
  }

  
  /**
   * Returns a single row as an associative array (field names are keys)
   * Moves internal data pointer ahead to next row
   *
   * @return array|bool
   */
  function fetchRow()
  {
    if (is_resource($this->mResult)) {
      return mysql_fetch_assoc($this->mResult);
    }
    return FALSE;
  }

  
  /**
   * Returns a single row as a numerically indexed array
   * Moves internal data pointer ahead to next row
   *
   * @return array|bool
   */
  function fetchRowArray()
  {
    if (is_resource($this->mResult)) {
      return mysql_fetch_row($this->mResult);
    }
    return FALSE;
  }
  

  /**
   * Returns an entire result set as an array of associative arrays
   *
   * @return array|bool
   */
  function fetchTable()
  {
    if (is_resource($this->mResult)) {
      $table = array();
      $rowCount = mysql_num_rows($this->mResult);
      while (0 < $rowCount--) {
        $table[] = mysql_fetch_assoc($this->mResult);
      }
      return $table;
    }
    return FALSE;
  }


  /**
   * Returns single field from result set as an array
   *
   * @param int|string $index Can be numeric index or associative key
   * @return array|bool
   */
  function fetchColumn($index)
  {
    if (is_resource($this->mResult)) {
      $column = array();

      $rowCount = mysql_num_rows($this->mResult);
      if (is_int($field)) {
        while (0 < $rowCount--) {
          $row = mysql_fetch_row($this->mResult);
          $column[] = $row[$index]; 
        }  
      }
      else {
        while (0 < $rowCount--) {
          $row = mysql_fetch_assoc($this->mResult);
          $column[] = $row[$index]; 
        }  
      }
      return $column;
    }
    return FALSE;
  }

}
?>