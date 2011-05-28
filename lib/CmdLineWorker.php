<?php
/*******************************************************************************
 *
 *  Copyright © 2010-2011 Hewlett-Packard Development Company, L.P. 
 *
 *  This work is distributed under the W3C® Software License [1] 
 *  in the hope that it will be useful, but WITHOUT ANY 
 *  WARRANTY; without even the implied warranty of 
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 *
 *  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
 *
 ******************************************************************************/
 
define('COMMAND_LINE', TRUE);

require_once("lib/DBConnection.php");

/**
 * Common utility functions for command line worker classes
 */
class CmdLineWorker extends DBConnection
{  

  function __construct() 
  {
    parent::__construct();
    
  }


  /**
   * Get command line argument
   *
   * @param int index
   * @return string|FALSE
   */
  function _getArg($index)
  {
    global $argv;

    $index = intval($index);
    if (array_key_exists($index, $argv)) {
      return $argv[$index];
    }
    return FALSE;
  }
  
  
  function _warning($message)
  {
    fprintf(STDERR, "WARNING: {$message}\n");
  }
  
  
  /**
   * Get only file name part of a path (without extension)
   *
   * @param string path
   * @return string filename
   */
  protected function _getFileName($path)
  {
    $pathInfo = pathinfo($path);
    
    if (isset($pathInfo['filename'])) { // PHP 5.2+
      return $pathInfo['filename'];
    }
    return basename($pathInfo['basename'], '.' . $pathInfo['extension']);
  }
  
  
  protected function _combinePath($path, $fileName, $extension = '')
  {
    if ((0 < strlen($path)) && ('/' != substr($path, -1, 1))) {
      $path .= '/';
    }
    if ((0 < strlen($extension)) && ('.' != substr($extension, 0, 1))) {
      $extension = '.' . $extension;
    }
    return "{$path}{$fileName}{$extension}";
  }

  
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