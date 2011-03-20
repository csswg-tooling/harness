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
 * This base class encapsulates the writing of basic HTML pages.
 */
class Page
{
  protected $mShouldCache;
  private   $mElementStack;
  private   $mFormatStack;
  private   $mFormatCount;
  

  /**
   * Static helper function to encode data safely for HTML output
   */ 
  static function Encode($string)
  {
    return htmlentities($string, ENT_QUOTES, 'UTF-8');
  }

  
  /**
   * Static helper function to decode HTML entities to UTF-8
   */ 
  static function Decode($string)
  {
    return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
  }


  /**
   * Static helper function to convert arg values to string
   * 
   * @param array argument array
   * @param bool shorten date values
   * @return array
   */
  static function _ConvertArgs(Array $args, $shortDate = FALSE)
  {
    $stringArgs = array();
    
    foreach ($args as $key => $value) {
      if (is_array($value)) {
        $value = self::_ConvertArgs($value, $shortDate);
      }
      elseif (is_object($value)) {
        if (is_a($value, 'DateTime')) {
          $value->setTimeZone(new DateTimeZone('UTC'));
          $value = $value->format(DateTime::W3C);
          if ($shortDate) {
            if ('+00:00' == substr($value, -6)) {
              $value = substr($value, 0, -6);
              if ('T00:00:00' == substr($value, -9)) {
                $value = substr($value, -9);
              }
            }
          }
        }
        else {
          $value = strval($value);
        }
      }
      elseif (is_bool($value)) {
        if ($value) {
          $value = null;
        }
        else {
          continue;
        }
      }
      $stringArgs[$key] = $value;
    }
    return $stringArgs;
  }


  /**
   * Static helper function to convert args to string and encode as a url query
   * 
   * @param array
   * @return array
   */
  static function _BuildQuery(Array $queryArgs)
  {
    $args = self::_ConvertArgs($queryArgs, TRUE);

    if (defined('PHP_QUERY_RFC3986')) {
      $query = http_build_query($args, 'var_', '&', PHP_QUERY_RFC3986);
    }
    else {
      $query = http_build_query($args, 'var_', '&');
    }
    return $query;
  }
  
  
  /**
   * Static helper function to build URI with query string
   * Instances of this class should use $this-buildURI instead
   * 
   * @param string base uri
   * @param array associative array of aurguments
   * @param string fragment identifier
   * @return string URL encoded
   */
  static function _BuildURI($baseURI, Array $queryArgs, $fragId = null)
  {
    $hashIndex = strpos($baseURI, '#');
    if (FALSE !== $hashIndex) { // remove existing fragId
      if (null == $fragId) {
        $fragId = substr($baseURI, $hashIndex + 1);
      }
      $baseURI = substr($baseURI, 0, $hashIndex);
    }
    if (0 < strlen($fragId)) {
      if ('#' != substr($fragId, 0, 1)) {
        $fragId = '#' . rawurlencode($fragId);
      }
      else {
        $fragId = '#' . rawurlencode(substr(1, $fragId));
      }
    }
    
    if (0 < count($queryArgs)) {
      $query = self::_BuildQuery($queryArgs);
      if ('?' != substr($baseURI, -1, 1)) {
        $query = '?' . $query;
      }
    }
    else {
      $query = '';
    }
    return $baseURI . $query . $fragId;
  }
  
  
  /**
   * Get IP address of client
   */
  static function GetClientIP()
  {
    if (! empty($_SERVER['REMOTE_ADDR'])) {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    return $ip;
  }
  

  
  function __construct() 
  {
    $this->mShouldCache = TRUE;
    $this->mElementStack = array();
    $this->mFormatStack = array();
    $this->mFormatCount = 0;
  }  
  

  /**
   * Helper function to build URI with query string
   * 
   * @param string base uri
   * @param array associative array of aurguments
   * @param string fragment identifier
   * @return string URL encoded
   */
  function buildURI($baseURI, Array $queryArgs, $fragId = null)
  {
    return self::_BuildURI($baseURI, $queryArgs, $fragId);
  }
  

  /**
   * Output page data with optional formatting
   */
  protected function _writeLine($output, $indent, $break)
  {
    if ($indent) {
      $indent = str_repeat('  ', count($this->mElementStack));
    }
    else {
      $indent = '';
    }
    if ($break) {
      $break = "\n";
    }
    else {
      $break = '';
    }
    echo $indent . $output . $break;
  }
  
  
  /**
   * Build element tag contents
   *
   * @param string elementName
   * @param array attribute key/value pairs
   * @return string
   */
  protected function _buildElement($elementName, Array $attrs = null)
  {
    $elementName = self::Encode($elementName);
    
    if ($attrs) {
      $attrs = self::_ConvertArgs($attrs);
      $output = $elementName;
      foreach ($attrs as $name => $value) {
        $name = self::Encode($name);
        if (is_null($value)) {
          $output .= ' ' . $name;
        }
        else {
          $value = self::Encode($value);
          $output .= " {$name}='{$value}'";
        }
      }
    }
    else {
      $output = $elementName;
    }
    return $output;
  }
  
  
  /**
   * Determing if page source formatting is on in the current context
   */
  protected function _formattingOn()
  {
    return ((0 == $this->mFormatCount) && defined('DEBUG_MODE') && DEBUG_MODE);
  }
  
  
  /**
   * Push element and formatting state onto stack
   */
  protected function _pushState($elementName, $format)
  {
    array_push($this->mElementStack, $elementName);
    array_push($this->mFormatStack, $format);
    if (! $format) {
      $this->mFormatCount++;
    }
  }
  
  
  /**
   * Pop element and formatting form stack
   */
  protected function _popState($elementName)
  {
    $lastElementName = array_pop($this->mElementStack);
    if (! $lastElementName) {
      trigger_error('missing open element', E_USER_WARNING);
    }
    if ($elementName && ($elementName != $lastElementName)) {
      trigger_error('mismatched close element', E_USER_WARNING);
    }
    
    $format = array_pop($this->mFormatStack);
    if (! $format) {
      $this->mFormatCount--;
    }
    return $lastElementName;
  }
  
  
  /**
   * Add element to page
   *
   * @param string element name
   * @param array attribute array
   * @param string element content
   * @param bool encode content
   * @param bool format source within element
   */
  function addElement($elementName, Array $attrs = null, $content = null, $encode = TRUE, $mayFormat = TRUE)
  {
    if (strlen($content) < 80) {
      $mayFormat = FALSE;
    }

    $outerFormat = $this->_formattingOn();
    $innerFormat = ($outerFormat && $mayFormat);
    
    $element = $this->_buildElement($elementName, $attrs);
    if (isset($content)) {
      $this->_writeLine("<{$element}>", $outerFormat, $innerFormat);
      $this->_pushState($elementName, $mayFormat);

      if ($encode) {
        $this->_writeLine(self::Encode($content), $innerFormat, $innerFormat);
      }
      else {
        $this->_writeLine($content, $innerFormat, $innerFormat);
      }
      $this->_popState($elementName);

      $elementName = self::Encode($elementName);
      $this->_writeLine("</{$elementName}>", $innerFormat, $outerFormat);
    }
    else {
      $this->_writeLine("<{$element}>", $outerFormat, $outerFormat);
    }
  }
  
  
  /**
   * Open element
   *
   * @param string element name
   * @param array attribute array
   * @param bool format source within element
   */
  function openElement($elementName, Array $attrs = null, $mayFormat = TRUE)
  {
    $element = $this->_buildElement($elementName, $attrs);
    
    $outerFormat = $this->_formattingOn();
    $innerFormat = ($outerFormat && $mayFormat);
    
    $this->_writeLine("<{$element}>", $outerFormat, $innerFormat);
    $this->_pushState($elementName, $mayFormat);
  }
  
  /**
   * Add text content
   *
   * @param string content
   * @param bool encode content
   * @param bool format source within element
   */
  function addTextContent($content, $encode = TRUE, $mayFormat = TRUE)
  {
    $format = ($this->_formattingOn() && $mayFormat);
    
    if ($encode) {
      $this->_writeLine(self::Encode($content), $format, $format);
    }
    else {
      $this->_writeLine($content, $format, $format);
    }
  }
  
  
  /**
   * Add comment
   *
   * @param string comment text
   * @param bool format source within comment
   */
  function addComment($comment, $mayFormat = TRUE)
  {
    $outerFormat = $this->_formattingOn();
    $innerFormat = ($outerFormat && $mayFormat);
    
    $this->_writeLine('<!-- ', $outerFormat, $innerFormat);
    $this->_writeLine($comment, $innerFormat, $innerFormat);
    $this->_writeLine(' -->', $innerFormat, $outerFormat);
  }
  
  
  /**
   * Close element
   *
   * @param string optional element name (for debugging)
   */
  function closeElement($elementName = null)
  {
    $innerFormat = $this->_formattingOn();
    $openElement = $this->_popState($elementName);
    $outerFormat = $this->_formattingOn();

    $elementName = self::Encode($openElement);
    $this->_writeLine("</{$openElement}>", $innerFormat, $outerFormat);
  }


  /**
   * Shortcut to add style element
   *
   * @param string stylesheet data
   * @param string type
   * @param array attribute array
   */
  function addStyleElement($styleSheet, $type = 'text/css', Array $attrs = null)
  {
    $attrs['type'] = $type;
    $this->openElement('style', $attrs, FALSE);
    $this->addComment($styleSheet);
    $this->closeElement('style');
  }
  
  
  /**
   * Shortcut to add style sheet link
   *
   * @param string uri
   * @param array attribute array
   */
  function addStyleSheetLink($uri, Array $attrs = null)
  {
    $attrs['rel'] = 'stylesheet';
    $attrs['href'] = $uri;
    $attrs['type'] = 'text/css';
    $this->addElement('link', $attrs);
  }
  
  
  /**
   * Shortcut to add script element
   *
   * @param string script content
   * @param string type
   * @param array attribute array
   */
  function addScriptElement($script, $type = 'text/javascript', Array $attrs = null)
  {
    $outerFormat = $this->_formattingOn();

    $attrs['type'] = $type;
    $this->openElement('script', $attrs);
    $this->_writeLine("<!--\n", FALSE, FALSE);
    $this->_writeLine($script, FALSE, FALSE);
    $this->_writeLine("\n// -->", FALSE, $outerFormat);
    $this->closeElement('script');
  }
  
  
  /**
   * Shortcut to add hyperlink
   *
   * @param string uri
   * @param array attribute array
   * @param string content string
   * @param bool encode content
   */
  function addHyperLink($uri, Array $attrs = null, $content = null, $encode = TRUE)
  {
    $attrs['href'] = $uri;
    $this->addElement('a', $attrs, $content, $encode);
  }
  
  
  /**
   * Shortcut to open form element
   *
   * @param string uri
   * @param string method
   * @param string name
   * @param array attribute array
   */
  function openFormElement($uri, $method = 'get', $name = null, Array $attrs = null)
  {
    $attrs['action'] = $uri;
    $attrs['method'] = $method;
    if ($name) {
      $attrs['name'] = $name;
    }
    $this->openElement('form', $attrs);
  }
  
  
  /**
   * Shortcut to add input element
   *
   * @param string type
   * @param string name
   * @param string|int|bool value
   * @param array attribute array
   */
  function addInputElement($type, $name = null, $value = null, Array $attrs = null)
  {
    $attrs['type'] = $type;
    if (! is_null($name)) {
      $attrs['name'] = $name;
    }
    if (! is_null($value)) {
      $attrs['value'] = $value;
    }
    $this->addElement('input', $attrs);
  }
  
  
  /**
   * Shortcut to open select element
   *
   * @param string name
   * @param array attribute array
   */
  function openSelectElement($name, Array $attrs = null)
  {
    $attrs['name'] = $name;
    $this->openElement('select', $attrs);
  }
  
  
  /**
   * Shortcut to add option element
   *
   * @param string|int|bool value
   * @param array attribute array
   * @param string content
   * @param bool encode content
   */
  function addOptionElement($value, Array $attrs = null, $content = null, $encode = TRUE)
  {
    $attrs['value'] = $value;
    $this->addElement('option', $attrs, $content, $encode);
  }
  
  
  /**
   * Shortcut to add abbr element
   *
   * @param string title
   * @param array attribute array
   * @param string content
   * @param bool encode content
   */
  function addAbbrElement($title, Array $attrs = null, $content = null, $encode = TRUE)
  {
    $attrs['title'] = $title;
    $this->addElement('abbr', $attrs, $content, $encode);
  }
  
  
  /**
   * Write hidden form controls for mSubmitData
   */
  function writeHiddenFormControls($shortDate = FALSE, $arrayName = NULL, $args = NULL)
  {
    if (! $args) {
      $args = self::_ConvertArgs($this->mSubmitData, $shortDate);
    }
    
    foreach($args as $key => $value) {
      if ($arrayName) {
        $key = "{$arrayName}[{$key}]";
      }
      if (is_array($value)) {
        $this->writeHiddenFormControls($shortDate, $key, $value);
      }
      else {
        $this->addInputElement('hidden', $key, $value);
      }
    }
  }

  
  /**
   * Override to set title for page
   */
  function getPageTitle()
  {
    return null;
  }
  
  
  /**
   * Override to set a different content title from the page title
   */
  function getContentTitle()
  {
    return $this->getPageTitle();
  }
  
  
  /**
   * Override to provide titles and URIs for navigation links
   *
   * @return array of compact($title, $uri)
   */
  function getNavURIs()
  {
    $title = "Home";
    $uri = "./";
    return array(compact('title', 'uri'));
  }
  
  
  /**
   * Override to set a base href for the page
   *
   * @return string URI value
   */
  function getBaseURI()
  {
    return null;
  }
  
  
  /**
   * Override to have this page redirect to another page
   */
  function getRedirectURI()
  {
    return null;
  }
  

  /**
   * Generate HTTP headers and write HTML output for this page
   */
  function write()
  {
    $this->writeHTTPHeaders();
    $this->writeHTML();
  }
  
  
  /**
   * Generate any needed HTTP headers.
   * Redirects and cache control are autoamtically handled.
   * Subclasses may override to provide additional headers.
   */
  function writeHTTPHeaders()
  {
    $redirectURI = $this->getRedirectURI();
    if ($redirectURI) {
      $redirect = "Location: {$redirectURI}";
      header($redirect);
    }
    if (! $this->mShouldCache) {
      header("Cache-Control: max-age=0");
    }
  }


  /**
   * Generate Doctype
   *
   * Defaults to HTML4.01 strict
   */
  function writeDoctype()
  {
    $this->_writeLine('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">', FALSE, $this->_formattingOn());
  }
  
  
  /**
   * Generate HTML for the entire page.
   * Subclasses should generally overeide more specific methods
   * to generate HTML for sections of the page.
   */
  function writeHTML()
  {
    $this->writeDoctype();
    
    $this->openElement('html', array('lang' => 'en'));
    $this->writeHTMLHead();
    $this->writeHTMLBody();
    $this->closeElement('html');
  }
  

  /**
   * Generate HTML <head>.
   * Subclasses should override specific methods, but may override
   * to replace entire head.
   */
  function writeHTMLHead()
  {
    $this->openElement('head');
    $this->writeHeadBase();
    $this->writeHeadMetas();
    $this->writeHeadTitle();
    $this->writeHeadStyle();
    $this->writeHeadLinks();
    $this->writeHeadScript();
    $this->closeElement('head');
  }


  /**
   * Generate <base> element if needed
   * Subclasses should override getBaseURI to provide a URI
   */
  function writeHeadBase()
  {
    $baseURI = $this->getBaseURI();
    if ($baseURI) {
      $this->addElement('base', array('href' => $baseURI));
    }
  }


  /**
   * Generate <title> element if needed
   * Subclasses should override getPageTitle to provide a title
   */
  function writeHeadTitle()
  {
    $title = $this->getPageTitle();
    if ($title) {
      $this->addElement('title', null, $title);
    }
  }


  /**
   * Generate <meta> elements(s)
   */
  function writeHeadMetas()
  {
    $args['http-equiv'] = 'Content-Type';
    $args['content'] = 'text/html; charset=utf-8';
    $this->addElement('meta', $args);
  }


  /**
   * Generate <style> element(s)
   */
  function writeHeadStyle()
  {  
  }
  

  /**
   * Generate <link> element(s)
   */
  function writeHeadLinks()
  {
  }
  
  /**
   * Generate <script> element(s)
   */
  function writeHeadScript()
  {
  }


  /**
   * Generate <body>
   *
   * Subclasses should override Header, Content or Footer methods
   */
  function writeHTMLBody()
  {
    $this->openElement('body');
    $this->writeBodyHeader();
    $this->writeBodyContent();
    $this->writeBodyFooter();
    $this->closeElement('body');
  }


  /**
   * Generate header section of html <body>
   */
  function writeBodyHeader()
  {
  }


  /**
   * Generate main content section of html <body>
   */
  function writeBodyContent()
  {
  }


  /**
   * Generate footer section of html <body>
   */
  function writeBodyFooter()
  {
  }
}

?>