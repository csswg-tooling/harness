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
 
require_once("lib/DBConnection.php");

/**
 * Common utility functions for command line worker classes
 */
class CmdLineWorker extends DBConnection
{  

  function __construct() 
  {
    Config::SetDebugMode(TRUE);
    set_error_handler(array(&$this, 'errorHandler'));
    
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
   * Callback function to capture PHP generated errors
   *
   * Use trigger_error to invoke an error condition, set errorType to:
   *   E_USER_NOTICE - minor error due to bad client input
   *   E_USER_WARNING - major error due to bad client input
   *   E_USER_ERROR - problem at server (like failed sql query)
   */
  function errorHandler($errorType, $errorString, $errorFile, $errorLine, $errorContext)
  {
    switch ($errorType) {
      case E_USER_NOTICE:
      case E_NOTICE:
        $errorType = 'NOTICE: ';
        break;
      case E_WARNING:
        $errorType = 'WARNING: ';
        break;
      case E_USER_WARNING:
      default:
        $errorType = 'ERROR: ';
    }
    
    if (! $errorString) {
      $errorString = 'Unknown Error';
    }
    
    global $argv;
    
    echo "{$errorType}{$errorString}\n";
    echo "File: {$errorFile}\n";
    echo "Line: {$this->mErrorLine}\n";
    echo "Context: " . print_r($this->mErrorContext, TRUE) . "\n";
    echo "Args: " . print_r($argv, TRUE) . "\n";

    die();
  }  
}

?>