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
  protected $mCookieData;
  protected $mSubmitData;
  
  protected $mCookiesFunctional;
    
  /**
   * Static function to condition get or post input
   *
   * sets all keys to lower case and removes any slashes from 
   * "magic quotes"
   *
   * @param array
   * @return array
   */
  static function ConditionInput(Array $input)
  {
    $output = array();
    if (is_array($input) && (0 < count($input))) {
      if (get_magic_quotes_gpc()) {
        foreach ($input as $key => $value) {
          if (is_array($value)) {
            $output[strtolower($key)] = self::ConditionInput($value);
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
    
    $this->mGetData = self::ConditionInput($_GET);
    $this->mPostData = self::ConditionInput($_POST);
    $this->mCookieData = self::ConditionInput($_COOKIE);
    
    $this->mSubmitData = array();

    $this->mCookiesFunctional = (null !== $this->_cookieData('crumbs'));
    // set a test cookie to check if cookies work (can't tell till next load)
//    $this->_setCookie('crumbs', 'test cookie'); 
  }  


  protected function _instantiateData($class, $arg)
  {
    if ($class) {
      if ('DateTime' == $class) {
        return new DateTime($arg, new DateTimeZone('UTC'));
      }
      return new $class($arg);
    }
    return $arg;
  }
  
  protected function _getData($field, $class = null)
  {
    if (isset($this->mGetData[$field])) {
      return $this->_instantiateData($class, $this->mGetData[$field]);
    }
    return null;
  }
  
  
  protected function _postData($field, $class = null)
  {
    if (isset($this->mPostData[$field])) {
      return $this->_instantiateData($class, $this->mPostData[$field]);
    }
    return null;
  }
  
  
  protected function _cookieData($field, $class = null)
  {
    if (isset($this->mCookieData[$field])) {
      return $this->_instantiateData($class, $this->mCookieData[$field]);
    }
    return null;
  }
  
  
  protected function _requestData($field, $class = null)
  {
    if (isset($this->mGetData[$field])) {
      return $this->_instantiateData($class, $this->mGetData[$field]);
    }
    if (isset($this->mPostData[$field])) {
      return $this->_instantiateData($class, $this->mPostData[$field]);
    }
    if (isset($this->mCookieData[$field])) {
      return $this->_instantiateData($class, $this->mCookieData[$field]);
    }
    return null;
  }
  
  
  /**
   * Set a cookie in the client's browser
   *
   * @param string $name cookie name
   * @param $value cookie data
   * @param int $duration cookie lifetime in seconds
   */
  protected function _setCookie($name, $value = null, $duration = 0)
  {
    if ($value) {
      if (0 < $duration) {
        setcookie($name, $value, time() + $duration);
      }
      else {  // set session cookie
        setcookie($name, $value, 0);
      }
    }
    else {  // clear cookie
      setcookie($name, '', 0);
    }
  }
  

  /**
   * Callback function to capture PHP generated errors
   *
   * Use trigger_error to invoke an error condition, set errorType to:
   *   E_USER_NOTICE - minor error due ot bad client input
   *   E_USER_WARNING - major error due to bad client input
   *   E_USER_ERROR - problem at server (like failed sql query)
   */
  function errorHandler($errorType, $errorString, $errorFile, $errorLine, $errorContext)
  {
    switch ($errorType) {
      case E_USER_NOTICE:
        $this->mErrorIsClient = TRUE;
      case E_NOTICE:
        $this->mErrorType = 'NOTICE:';
        break;
      case E_WARNING:
        $this->mErrorType = 'WARNING:';
        break;
      case E_USER_WARNING:
        $this->mErrorIsClient = TRUE;
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
   * Generate html <body>
   *
   * Overridden to insert error text if present
   */
  function writeHTMLBody()
  {
    $this->openElement('body');
    if (null == $this->mErrorType) {
      $this->writeBodyHeader();
      $this->writeBodyContent();
      $this->writeBodyFooter();
    }
    else {
      $this->writeBodyError();
    }
    $this->closeElement('body');
  }


  /**
   * Generate error text
   */
  function writeBodyError()
  {
    if ($this->mErrorType) {
      $this->openElement('p');
      $this->addElement('strong', null, $this->mErrorType);
      if ($this->mErrorMessage) {
        $this->addTextContent($this->mErrorMessage);
      }
      $this->closeElement('p');
    } 
    else {
      if ($this->mErrorMessage) {
        $this->addElement('p', null, $this->mErrorMessage);
      }
    }
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
      if ($this->mErrorFile) {
        $this->addElement('p', null, "File: {$this->mErrorFile}");
      }
      if ($this->mErrorLine) {
        $this->addElement('p', null, "Line: {$this->mErrorLine}");
      }
      if ($this->mErrorContext) {
        $this->openElement('p');
        $this->addTextContent('Context: ');
        $this->openElement('pre');
        print_r($this->mErrorContext);
        $this->closeElement('pre');
        $this->closeElement('p');
      }
      
      if (0 < count($this->mGetData)) {
        $this->openElement('p');
        $this->addTextContent('Get: ');
        $this->openElement('pre');
        print_r($this->mGetData);
        $this->closeElement('pre');
        $this->closeElement('p');
      }
      
      if (0 < count($this->mPostData)) {
        $this->openElement('p');
        $this->addTextContent('Post: ');
        $this->openElement('pre');
        print_r($this->mPostData);
        $this->closeElement('pre');
        $this->closeElement('p');
      }

      if (0 < count($this->mCookieData)) {
        $this->openElement('p');
        $this->addTextContent('Cookie: ');
        $this->openElement('pre');
        print_r($this->mCookieData);
        $this->closeElement('pre');
        $this->closeElement('p');
      }
    }
  }
}

?>