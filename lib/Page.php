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


/**
 * This base class encapsulates the writing of basic HTML pages.
 */
class Page
{
  protected $mShouldCache;  // reset in subclass as needed before write
  private   $mWriteXML;
  protected $mContentType;  // override _determineContentType to set 
  protected $mEncoding;     // override _determineEncoding to set
  
  protected $mErrorIsClient;
  protected $mErrorType;
  protected $mErrorMessage;
  protected $mErrorFile;
  protected $mErrorLine;
  protected $mErrorContext;
  
  private   $mElementStack;
  private   $mFormatStack;
  private   $mFormatCount;
  private   $mPIList;
  private   $mBufferStack;
  
  private   $mOutputFile;


  /**
   * Static helper function to decode HTML entities
   */ 
  static function Decode($string, $encoding = 'utf-8')
  {
    return html_entity_decode($string, ENT_QUOTES, $encoding);
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
              if (0 == strcasecmp('T00:00:00', substr($value, -9))) {
                $value = substr($value, -9);
              }
            }
          }
        }
        else {
          $value = strval($value);
        }
      }
      $stringArgs[strtolower($key)] = $value;
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
   * Instances of this class should use $this->buildURI instead
   * 
   * @param string base uri
   * @param array associative array of aurguments
   * @param string fragment identifier
   * @return string URL encoded
   */
  static function _BuildURI($baseURI, Array $queryArgs = null, $fragId = null, $absolute = FALSE)
  {
    if (null === $fragId) {
      $fragId = rawurldecode(substr(strstr($baseURI, '#'), 1));
    }
    if (FALSE !== strpos($baseURI, '#')) {
      $baseURI = strstr($baseURI, '#', TRUE); // remove existing frag id
    }
    if (FALSE !== strpos($baseURI, '?')) {
      $baseURI = strstr($baseURI, '?', TRUE); // remove existing query
    }

    if (0 < strlen($fragId)) {
      if ('#' != substr($fragId, 0, 1)) {
        $fragId = '#' . rawurlencode($fragId);  // XXX encode?
      }
      else {
        $fragId = '#' . rawurlencode(substr(1, $fragId));
      }
    }
    
    if (is_array($queryArgs) && (0 < count($queryArgs))) {
      $query = '?' . self::_BuildQuery($queryArgs);
    }
    else {
      $query = '';
    }
    
    $abs = '';
    if ($absolute) {
      if (empty($_SERVER['HTTP_HOST'])) {
        $abs = HARNESS_BASE_URI;
      }
      else {
        $abs = 'http://' . $_SERVER['HTTP_HOST'];
        if (! empty($_SERVER['PHP_SELF'])) {
          $abs .= dirname($_SERVER['PHP_SELF']);
          if (substr($abs, -1) != '/') {
            $abs .= '/';
          }
        }
      }
    }
    
    return $abs . $baseURI . $query . $fragId;
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
    elseif (! empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    else {
      $ip = FALSE;
    }
    return $ip;
  }
  

  
  function __construct() 
  {
    $this->mShouldCache = TRUE;
    $this->mWriteXML = TRUE;
    $this->mContentType = 'text/html';
    $this->mEncoding = 'utf-8';
    
    $this->mElementStack = array();
    $this->mFormatStack = array();
    $this->mFormatCount = 0;
    $this->mPIList = array();
    $this->mBufferStack = array();
    
    $this->mErrorIsClient = FALSE;
    $this->mErrorType = null;
    $this->mErrorMessage = null;
    $this->mErrorFile = null;
    $this->mErrorLine = null;
    $this->mErrorContext = null;
    set_error_handler(array(&$this, 'errorHandler'));
  }
  
  
  /**
   * Helper function to encode data safely for XML/HTML output
   */ 
  function encode($string)
  {
    if ($this->mWriteXML) {
      // XXX should convert other chars to numeric entiries or leave as is
      return htmlspecialchars($string, ENT_QUOTES, $this->mEncoding);
    }
    return htmlentities($string, ENT_QUOTES, $this->mEncoding);
  }
  
  
  /**
   * Helper function to covert string entirely into numeric entity references
   * Useful for hiding email addresses from spam harvesters
   */
  function obfuscate($string)
  {
    $output = '';
    $count = strlen($string);
    for ($index = 0; $index < $count; $index++) {
      $output .= '&#' . ord($string[$index]) . ';';
    }
    return $output;
  }

  
  /**
   * Helper function to build URI with query string
   * 
   * @param string base uri
   * @param array associative array of aurguments
   * @param string fragment identifier
   * @return string URL encoded
   */
  function buildURI($baseURI, Array $queryArgs = null, $fragId = null, $absolute = FALSE)
  {
    return self::_BuildURI($baseURI, $queryArgs, $fragId, $absolute);
  }
  
  
  /**
   * Begin output buffering context
   */
  protected function _beginBuffering()
  {
    $this->mBufferStack[] = '';
  }
  

  /**
   * End output buffering context
   * Outputs PIs if last buffer
   *
   * @param bool optionally abort buffered output
   * @return string buffered data
   */
  protected function _endBuffering($abort = FALSE)
  {
    $buffer = array_pop($this->mBufferStack);

    if (0 == count($this->mBufferStack)) {
      foreach ($this->mPIList as $pi) {
        $this->_writeLine("<?{$pi} ?" . '>', FALSE, $this->_formattingOn());
      }
    }
    if (! $abort) {
      $this->_write($buffer);
    }
    return $buffer;
  }
  

  /**
   * Output page data
   */
  protected function _write($output)
  {
    if (0 < count($this->mBufferStack)) {
      $buffer = array_pop($this->mBufferStack) . $output;
      $this->mBufferStack[] = $buffer;
    }
    else {
      if ($this->mOutputFile) {
        fwrite($this->mOutputFile, $output);
      }
      else {
        echo $output;
      }
    }
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
    $this->_write($indent . $output . $break);
  }
  

  protected function _buildAttrs($arrayName, Array $attrs, $encodeValues = TRUE)
  {
    $output = '';
    
    foreach ($attrs as $name => $value) {
      if ($arrayName) {
        $name = $arrayName . '[' . $this->encode($name) . ']';
      }
      else {
        $name = $this->encode($name);
      }
      if (is_null($value)) {
        if ($this->mWriteXML) {
          if ($arrayName) {
            $baseName = strstr($name, '[', TRUE);
            $output .= " {$name}='{$baseName}'";
          }
          else {
            $output .= " {$name}='{$name}'";
          }
        }
        else {
          $output .= ' ' . $name;
        }
      }
      elseif (is_array($value)) {
        $output .= $this->_buildAttrs($name, $value, $encodeAttrs);
      }
      elseif (is_bool($value)) {
        if ($value) {
          if ($this->mWriteXML) {
            if ($arrayName) {
              $baseName = strstr($name, '[', TRUE);
              $output .= " {$name}='{$baseName}'";
            }
            else {
              $output .= " {$name}='{$name}'";
            }
          }
          else {
            $output .= ' ' . $name;
          }
        }
      }
      else {
        if ($encodeValues) {
          $value = $this->encode($value);
        }
        $output .= " {$name}='{$value}'";
        
        if ($this->mWriteXML && ('lang' == $name)) {
          $output .= " xml:lang='{$value}'";
        }
      }
    }
    
    return $output;
  }
  
  /**
   * Build element tag contents
   *
   * @param string elementName
   * @param array attribute key/value pairs
   * @return string
   */
  protected function _buildElement($elementName, Array $attrs = null, $encodeAttrs = TRUE)
  {
    $elementName = $this->encode($elementName);
    
    if ($attrs) {
      $attrs = self::_ConvertArgs($attrs);
      $output = $elementName . $this->_buildAttrs(null, $attrs, $encodeAttrs);
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
   * Add processing instruction to page
   *
   * @param string pi name
   * @param array attribute array
   */
  function addPI($piName, Array $attrs = null)
  {
    assert('$this->mWriteXML');
    
    if ($this->mWriteXML) {
      if ((0 == count($this->mBufferStack)) && (0 == count($this->mElementStack))) {
        $pi = $this->_buildElement($piName, $attrs);
        $this->_writeLine("<?{$pi} ?" . '>', FALSE, $this->_formattingOn());
      }
      else {
        $this->mPIList[] = $this->_buildElement($piName, $attrs);
      }
    }
  }
  

  /**
   * Add element to page
   *
   * @param string element name
   * @param array attribute array
   * @param string element content
   * @param bool encode content
   * @param bool format source within element
   * @param bool encode attribute values
   */
  function addElement($elementName, Array $attrs = null, $content = null,
                      $encodeContent = TRUE, $mayFormat = TRUE, $encodeAttrs = TRUE)
  {
    if (strlen($content) < 80) {
      $mayFormat = FALSE;
    }

    $outerFormat = $this->_formattingOn();
    $innerFormat = ($outerFormat && $mayFormat);
    
    $element = $this->_buildElement($elementName, $attrs, $encodeAttrs);
    if (isset($content)) {
      $this->_writeLine("<{$element}>", $outerFormat, $innerFormat);
      $this->_pushState($elementName, $mayFormat);

      if ($encodeContent) {
        $this->_writeLine($this->encode($content), $innerFormat, $innerFormat);
      }
      else {
        $this->_writeLine($content, $innerFormat, $innerFormat);
      }
      $this->_popState($elementName);

      $elementName = $this->encode($elementName);
      $this->_writeLine("</{$elementName}>", $innerFormat, $outerFormat);
    }
    else {
      if ($this->mWriteXML) {
        $this->_writeLine("<{$element} />", $outerFormat, $outerFormat);
      }
      else {
        $this->_writeLine("<{$element}>", $outerFormat, $outerFormat);
      }
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
      $this->_writeLine($this->encode($content), $format, $format);
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
   * Add CDATA
   *
   * @param string cdata content
   * @param bool format source within cdata
   */
  function addCDATA($content, $mayFormat = TRUE)
  {
    $outerFormat = $this->_formattingOn();
    $innerFormat = ($outerFormat && $mayFormat);
    
    $this->_writeLine('<![CDATA[', $outerFormat, $innerFormat);
    $this->_writeLine($content, $innerFormat, $innerFormat);
    $this->_writeLine(']]>', $innerFormat, $outerFormat);
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

    $elementName = $this->encode($openElement);
    $this->_writeLine("</{$openElement}>", $innerFormat, $outerFormat);
  }


  /**
   * Encode a variable as XML
   */
  function xmlEncode($name, $value)
  {
    if (is_null($value)) {
      $this->addElement($name);
    }
    elseif (is_bool($value)) {
      $this->addElement($name, null, ($value) ? 'true' : 'false', FALSE, FALSE);
    }
    elseif (is_string($value) || is_numeric($value)) {
      $this->addElement($name, null, $value, TRUE, FALSE);
    }
    elseif (is_array($value)) {
      if (is_string($name)) {
        $this->openElement($name);
      }
      foreach ($value as $key => $value) {
        $this->xmlEncode($key, $value);
      }
      if (is_string($name)) {
        $this->closeElement($name);
      }
    }
    elseif (is_object($value)) {
      $reflect = new ReflectionClass($value);
      if (method_exists($value, '_getElementName')) {
        $elementName = $value->_getElementName();
      }
      else {
        $elementName = get_class($value);
      }
      $this->openElement($elementName);
      $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
      foreach ($properties as $property) {
        $this->xmlEncode($property->getName(), $property->getValue($value));
      }
      $this->closeElement($elementName);
    }
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
    if ($this->mWriteXML) {
      $this->addCDATA($styleSheet);
    }
    else {
      $this->addComment($styleSheet);
    }
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
    $attrs['href'] = $uri;
    $attrs['type'] = 'text/css';
    if ($this->mWriteXML) {
      $this->addPI('xml-stylesheet', $attrs);
    }
    else {
      $attrs['rel'] = 'stylesheet';
      $this->addElement('link', $attrs);
    }
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
    if ($this->mWriteXML) {
      $this->_writeLine("//<![CDATA[\n", FALSE, FALSE);
      $this->_writeLine($script, FALSE, FALSE);
      $this->_writeLine("\n//]]>", FALSE, $outerFormat);
    }
    else {
      $this->_writeLine("<!--\n", FALSE, FALSE);
      $this->_writeLine($script, FALSE, FALSE);
      $this->_writeLine("\n// -->", FALSE, $outerFormat);
    }
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
   * Shortcut to add obfuscated email hyperlink
   */
  function addEmailHyperLink($address, $name = null, Array $args = null)
  {
    $attrs['href'] = $this->obfuscate("mailto:{$address}");
    if (is_array($args) && (0 < count($args))) {
      $args = self::_ConvertArgs($args, TRUE);
      $argStr = '';
      foreach ($args as $key => $value) {
        if ($argStr) {
          $argStr .= '&amp;';
        }
        if (('cc' == $key) || ('bcc' == $key)) {
          $value = $this->obfuscate($value);
        }
        $argStr .= "{$key}={$value}";
      }
      $attrs['href'] .= '?' . $argStr;
    }
    if ($name) {
      $content = $name . ' ' . $this->obfuscate("<{$address}>");
    }
    else {
      $content = $this->obfuscate("<{$address}>");
    }
    $this->addElement('a', $attrs, $content, FALSE, FALSE, FALSE);
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
      $attrs['id'] = $name;
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
  function addInputElement($type, $name = null, $value = null, $id = null, Array $attrs = null)
  {
    $attrs['type'] = $type;
    if (! is_null($name)) {
      $attrs['name'] = $name;
    }
    if (! is_null($value)) {
      $attrs['value'] = $value;
    }
    if (! is_null($id)) {
      $attrs['id'] = $id;
    }
    $this->addElement('input', $attrs);
  }
  
  
  /**
   * Shortcut to add label element
   *
   * @param string id of element label is attached to
   * @param string content
   * @param array attribute array
   */
  function addLabelElement($for, $content, Array $attrs = null)
  {
    $attrs['for'] = $for;
    $this->addElement('label', $attrs, $content);
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
   * Send an HTTP header if it is safe to do so
   */
  function sendHTTPHeader($header, $value = null)
  {
    if ((! headers_sent()) && (! $this->mOutputFile)) {
      if ($value) {
        $header = "{$header}: {$value}";
      }
      header($header);
    }
  }
  

  /**
   * Determine output format from client accept headers or file extension
   */
  protected function _determineContentType($filePath = null)
  {
    $contentType = $this->mContentType;
    
    if ($filePath) {
      $pathInfo = pathinfo($filePath);
      $extension = strtolower($pathInfo['extension']);
      
      $contentType = 'text/plain';
      if (('html' == $extension) || ('htm' == $extension)) {
        $contentType = 'text/html';
      }
      elseif ('xml' == $extention) {
        $contentType = 'application/xml';
      }
      elseif (('xht' == $extension) || ('xhtml' == $extension)) {
        $contentType = 'application/xhtml+xml';
      }
    }
    else {
      if (array_key_exists('HTTP_ACCEPT', $_SERVER)) {
        $accept = $_SERVER['HTTP_ACCEPT'];
        
        if (FALSE !== stripos($accept, 'text/plain')) {
          $contentType = 'text/plain';
        }
        if (FALSE !== stripos($accept, 'application/xml')) {
          $contentType = 'application/xml';
        }
        if (FALSE !== stripos($accept, 'text/html')) {
          $contentType = 'text/html';
        }

        if (FALSE !== stripos($accept, 'application/xhtml+xml')) {
//          $contentType = 'application/xhtml+xml';     XXX disable to to wierd FF bug with optgroups
          if (preg_match('/application\/xhtml\+xml;q=0(\.[1-9]+)/i', $accept, $matches)) {
            $xhtmlQ = floatval($matches[1]);
            if (preg_match('/text\/html;q=0(\.[1-9]+)/i', $accept, $matches)) {
              $htmlQ = floatval($matches[1]);
              if ($xhtmlQ < $htmlQ) {
                $contentType = 'text/html';
              }
            }
          }
        }
      }
    }
    
    return $contentType;
  }
  
  protected function _determineEncoding($filePath = null)
  {
    // XXX TODO: check accept headers...
    return 'utf-8';
  }
  
  
  /**
   * Generate HTTP headers and write HTML output for this page
   */
  function write($filePath = null)
  {
    if ($filePath) {
      $this->mOutputFile = fopen($filePath, "wb");
    }
    
    $this->mContentType = $this->_determineContentType($filePath);
    $this->mEncoding = $this->_determineEncoding($filePath);
    
    if (! $this->mOutputFile) {
      $this->writeHTTPHeaders();
    }
    
    switch (strtolower($this->mContentType)) {
      case 'text/html':
        $this->mWriteXML = FALSE;
      case 'application/xhtml+xml':
        $this->writeHTML();
        break;
      case 'application/xml':
        $this->writeXML();
        break;
      case 'application/json':
        $this->writeJSON();
        break;
      case 'text/plain':
        $this->writePlainText();
        break;
    }
    
    if ($this->mOutputFile) {
      fclose($this->mOutputFile);
      $this->mOutputFile = null;
    }
  }
  
  
  /**
   * Generate any needed HTTP headers.
   * Redirects, cache control and content-type are autoamtically handled.
   * Subclasses may override to provide additional headers.
   */
  function writeHTTPHeaders()
  {
    $redirectURI = $this->getRedirectURI();
    if ($redirectURI) {
      $this->sendHTTPHeader('Location', $redirectURI);
    }
    
    if (! $this->mShouldCache) {
      $this->sendHTTPHeader('Cache-Control', 'max-age=0');
    }
    
    $this->sendHTTPHeader('Content-Type', "{$this->mContentType}; charset={$this->mEncoding}");
    $this->sendHTTPHeader('Vary', 'Accept');
  }


  /**
   * Generate Doctype
   *
   * Defaults to HTML4.01 strict
   */
  function writeDoctype()
  {
    if ($this->mWriteXML) {
      $this->addPI('xml', array('version' => '1.0', 'encoding' => $this->mEncoding));
      $this->_writeLine('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">', FALSE, $this->_formattingOn());
    }
    else {
      $this->_writeLine('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">', FALSE, $this->_formattingOn());
    }
  }
  

  /**
   * Generate HTML for the entire page.
   * Subclasses should generally overeide more specific methods
   * to generate HTML for sections of the page.
   */
  function writeHTML()
  {
    if ($this->mWriteXML) {
      $attrs['xmlns'] = 'http://www.w3.org/1999/xhtml';
      $this->_beginBuffering();
    }

    $this->writeDoctype();

    $attrs['lang'] = 'en';
    $this->openElement('html', $attrs);

    $this->writeHTMLHead();
    if ($this->mWriteXML) {
      $this->_endBuffering();
    }
    $this->writeHTMLBody();
    
    $this->closeElement('html');
  }
  
  
  /**
   * Generate XML page response
   * Subclasses override to provide data
   */
  function writeXML()
  {
  }
  
  
  /**
   * Generate JSON for the page response
   * Subclasses override to provide data
   */
  function writeJSON()
  {
  }
  
  
  /**
   * Generate plain text content for page
   * Subclasses override to provide data
   */
  function writePlainText()
  {
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
    $args['content'] = "{$this->mContentType}; charset={$this->mEncoding}";
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
        $this->mErrorType = 'NOTICE: ';
        break;
      case E_WARNING:
        $this->mErrorType = 'WARNING: ';
        break;
      case E_USER_WARNING:
        $this->mErrorIsClient = TRUE;
      default:
        $this->mErrorType = 'ERROR: ';
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
        $this->sendHTTPHeader('HTTP/1.1 400 Bad Request');
      } 
      else {
        $this->sendHTTPHeader('HTTP/1.1 500 Internal Server Error');
      }
    }

    if (0 != strcasecmp('application/json', $this->mContentType)) {
      $this->mContentType = 'text/html'; //XXX debug
    }

    $writingHTML = FALSE;
    $writingMarkup = FALSE;
    switch (strtolower($this->mContentType)) {
      case 'text/html':
      case 'application/xhtml+xml':
        $writingHTML = TRUE;
      case 'application/xml':
        $writingMarkup = TRUE;
        break;
    }

    if ($writingHTML) {
      if (! in_array('html', $this->mElementStack)) {
        while (0 < count($this->mElementStack)) {
          $this->closeElement();
        }
        $this->openElement('html');
      }
      if (! in_array('body', $this->mElementStack)) {
        while (1 < count($this->mElementStack)) {
          $this->closeElement();
        }
        $this->openElement('body');
      }
      while (2 < count($this->mElementStack)) {
        $this->closeElement();
      }
    }
    else if ($writingMarkup) {
      while (0 < count($this->mElementStack)) {
        $this->closeElement();
      }
      $this->openElement('html');
      $this->openElement('body');
    }
    
    while (0 < count($this->mBufferStack)) {
      $this->_endBuffering();
    }

    if ($writingMarkup) {
      $this->writeHTMLError();
    }
    else {
      if (! headers_sent()) {
        $this->mContentType = 'text/plain';
        $this->writeHTTPHeaders();
      }
      if (0 != strcasecmp('application/json', $this->mContentType)) {
        $this->writePlainTextError();
      }
    }
    $error = $this->_endBuffering();
    
    if ($writingMarkup) {
      while (0 < count($this->mElementStack)) {
        $this->closeElement();
      }
    }
    
    if ($this->mOutputFile) {
      fclose($this->mOutputFile);
      $this->mOutputFile = null;
      $this->writePlainTextError(); 
    }
    die();
  }


  /**
   * Generate error text in HTML
   */
  function writeHTMLError()
  {
    if ($this->mErrorType) {
      $this->openElement('p');
      $this->addElement('strong', null, $this->mErrorType);
      if ($this->mErrorMessage) {
        $this->addTextContent($this->mErrorMessage, FALSE);
      }
      $this->closeElement('p');
    } 
    else {
      if ($this->mErrorMessage) {
        $this->addElement('p', null, $this->mErrorMessage, FALSE);
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
        $this->openElement('pre', null, FALSE);
        $this->addTextContent(print_r($this->mErrorContext, TRUE));
        $this->closeElement('pre');
        $this->closeElement('p');
      }
      
      // XXX factor this back into dynamic page, add mArgData
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
    if ($this->mErrorType) {
      $this->_write($this->mErrorType);
      if ($this->mErrorMessage) {
        $this->_write($this->mErrorMessage);
      }
      $this->_write("\n");
    } 
    else {
      if ($this->mErrorMessage) {
        $this->_writeLine($this->mErrorMessage, FALSE, TRUE);
      }
    }
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
      if ($this->mErrorFile) {
        $this->_write("File: {$this->mErrorFile}\n");
      }
      if ($this->mErrorLine) {
        $this->_write("Line: {$this->mErrorLine}\n");
      }
      if ($this->mErrorContext) {
        $this->_write('Context: ' . print_r($this->mErrorContext, TRUE) . "\n");
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