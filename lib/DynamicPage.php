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

require_once("lib/Page.php");

/**
 * This class encapsulates common functionality of dynamically generated pages.
 * This includes processing page input and reporting of errors
 */
class DynamicPage extends Page
{
  protected $mErrorIsClient;
  protected $mErrorType;
  protected $mErrorMessage;
  protected $mErrorFile;
  protected $mErrorLine;
  protected $mErrorContext;
  
  protected $mGetData;
  protected $mPostData;
  protected $mRequestData;    // union of GET, POST and COOKIES
  
  /**
   * Static function to condition get or post input
   *
   * sets all keys to lower case and removes any slashes from 
   * "magic quotes"
   *
   * @param array
   * @return array
   */
  static function _ConditionInput($input)
  {
    $output = array();
    if (is_array($input) && (0 < count($input))) {
      if (get_magic_quotes_gpc()) {
        foreach ($input as $key => $value) {
          if (is_array($value)) {
            $output[strtolower($key)] = DynamicPage::_ConditionInput($value);
          }
          else {
            $output[strtolower($key)] = stripslashes($value);
          }
        }
      }
      else {
        $output = array_change_key_case($input, CASE_LOWER);
      }
    }
    return $output;
  }
  
  
  function __construct() 
  {
    parent::__construct();
    
    $this->mShouldCache = FALSE;
    
    $this->mErrorIsClient = FALSE;
    $this->mErrorType = null;
    $this->mErrorMessage = null;
    $this->mErrorFile = null;
    $this->mErrorLine = null;
    $this->mErrorContext = null;
    set_error_handler(array(&$this, 'errorHandler'));
    
    $this->mGetData = DynamicPage::_ConditionInput($_GET);
    $this->mPostData = DynamicPage::_ConditionInput($_POST);
    $this->mRequestData = DynamicPage::_ConditionInput($_REQUEST);

  }  


  protected function _getData($field)
  {
    if (isset($this->mGetData[$field])) {
      return $this->mGetData[$field];
    }
    return null;
  }
  
  
  protected function _postData($field)
  {
    if (isset($this->mPostData[$field])) {
      return $this->mPostData[$field];
    }
    return null;
  }
  
  
  protected function _requestData($field)
  {
    if (isset($this->mRequestData[$field])) {
      return $this->mRequestData[$field];
    }
    return null;
  }
  
  
  /**
   * Callback function to capture PHP generated errors
   */
  function errorHandler($errorNumber, $errorString, $errorFile, $errorLine, $errorContext)
  {
    switch ($errorNumber) {
      case E_WARNING:
      case E_USER_WARNING:
        $this->mErrorType = 'WARNING:';
        break;
      case E_NOTICE:
      case E_USER_NOTICE:
        $this->mErrorType = 'NOTICE:';
        break;
      default:
        $this->mErrorType = 'ERROR:';
    }
    
    if (! $errorString) {
      $errorString = 'Unknown Error';
    }
    
    $this->mErrorMessage = $errorString;
    
    $this->mErrorFile = $errorFile;
    $this->mErrorLine = $errorLine;
    $this->mErrorContext = $errorContext;

    if (! headers_sent()) {
      if ($this->mErrorIsClient) {
        header('HTTP/1.1 400 Bad Request');
      } 
      else {
        header('HTTP/1.1 500 Internal Server Error');
      }
    }

    $this->write();
    
    die();
  }


  /**
   * Trigger error due to problem at server
   */
  function triggerServerError($errorMessage, $errorType = E_USER_ERROR)
  {
    trigger_error($errorMessage, $errorType);
  }


  /**
   * Trigger error due to bad input from client
   */
  function triggerClientError($errorMessage, $errorType = E_USER_ERROR)
  {
    $this->mErrorIsClient = TRUE;
    trigger_error($errorMessage, $errorType);
  }


  /**
   * Generate html <body>
   *
   * Overridden to insert error text if present
   */
  function writeHTMLBody($indent = '')
  {
    echo $indent . "<body>\n";
    if (null == $this->mErrorType) {
      $this->writeBodyHeader($indent . '  ');
      $this->writeBodyContent($indent . '  ');
      $this->writeBodyFooter($indent . '  ');
    }
    else {
      $this->writeBodyError($indent . '  ');
    }
    echo $indent . "</body>\n";
  }


  /**
   * Generate error text
   */
  function writeBodyError($indent = '')
  {
    if ($this->mErrorType) {
      echo $indent . "<p>\n";
      echo $indent . "  <strong>{$this->mErrorType}</strong>\n";
      if ($this->mErrorMessage) {
        echo $indent . "  " . Page::Encode($this->mErrorMessage) . "\n";
      }
      echo $indent . "</p>\n";
    } 
    else {
      if ($this->mErrorMessage) {
        echo $indent . "<p>" . Page::Encode($this->mErrorMessage) . "</p>\n";
      }
    }
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
      if ($this->mErrorFile) {
        echo $indent . "<p>File: " . Page::Encode($this->mErrorFile) . "</p>\n";
      }
      if ($this->mErrorLine) {
        echo $indent . "<p>Line: " . Page::Encode($this->mErrorLine) . "</p>\n";
      }
      if ($this->mErrorContext) {
        echo $indent . "<p>Context: \n";
        echo $indent . "  ";
        print_r($this->mErrorContext);
        echo           "\n";
        echo $indent . "</p>\n";
      }
    }
  }
}

?>