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
  protected $mArgData;
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
  static function ConditionInput(Array $input = null)
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
  
  
  function __construct(Array $args = null)
  {
    parent::__construct();
    
    $this->mShouldCache = FALSE;
    
    $this->mArgData = self::ConditionInput($args);
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
      if (0 == strcasecmp('DateTime', $class)) {
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
    if (isset($this->mArgData[$field])) {
      return $this->_instantiateData($class, $this->mArgData[$field]);
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
    if (isset($this->mArgData[$field])) {
      return $this->_instantiateData($class, $this->mArgData[$field]);
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
   * Generate error text in HTML
   */
  function writeHTMLError()
  {
    parent::writeHTMLError();
    if (Config::IsDebugMode()) {
      if (0 < count($this->mArgData)) {
        $this->openElement('p');
        $this->addTextContent('Args: ');
        $this->openElement('pre', null, FALSE);
        $this->addTextContent(print_r($this->mArgData, TRUE));
        $this->closeElement('pre');
        $this->closeElement('p');
      }
      
      if (0 < count($this->mGetData)) {
        $this->openElement('p');
        $this->addTextContent('Get: ');
        $this->openElement('pre', null, FALSE);
        $this->addTextContent(print_r($this->mGetData, TRUE));
        $this->closeElement('pre');
        $this->closeElement('p');
      }
      
      if (0 < count($this->mPostData)) {
        $this->openElement('p');
        $this->addTextContent('Post: ');
        $this->openElement('pre', null, FALSE);
        $this->addTextContent(print_r($this->mPostData, TRUE));
        $this->closeElement('pre');
        $this->closeElement('p');
      }

      if (0 < count($this->mCookieData)) {
        $this->openElement('p');
        $this->addTextContent('Cookie: ');
        $this->openElement('pre', null, FALSE);
        $this->addTextContent(print_r($this->mCookieData, TRUE));
        $this->closeElement('pre');
        $this->closeElement('p');
      }
    }
  }
  
  
  /**
   * Generate error text in plain text
   */
  function writePlainTextError()
  {
    parent::writePlainTextError();

    if (Config::IsDebugMode()) {
      if (0 < count($this->mArgData)) {
        $this->_write('Args: ' . print_r($this->mArgData, TRUE) . "\n");
      }
      
      if (0 < count($this->mGetData)) {
        $this->_write('Get: ' . print_r($this->mGetData, TRUE) . "\n");
      }
      
      if (0 < count($this->mPostData)) {
        $this->_write('Post: ' . print_r($this->mPostData, TRUE) . "\n");
      }

      if (0 < count($this->mCookieData)) {
        $this->_write('Cookie: ' . print_r($this->mCookieData, TRUE) . "\n");
      }
    }
  }  
  
  
}

?>