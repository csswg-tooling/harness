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
   * Static helper function to build URI with query string
   * 
   * @param string base uri
   * @param array associative array of aurguments
   * @param string fragment identifier
   * @return string URL encoded
   */
  static function BuildURI($baseURI, $queryArgs, $fragId = null)
  {
    $hashIndex = strpos($baseURI, '#');
    if (FALSE !== $hashIndex) { // remove existing fragId
      if (null == $fragId) {
        $fragId = substr($baseURI, $hashIndex + 1);
      }
      $baseURI = substr($baseURI, 0, $hashIndex);
    }
    if ((0 < strlen($fragId)) && ('#' != substr($fragId, 0, 1))) {
      $fragId = '#' . $fragId;
    }
    
    if (0 < count($queryArgs)) {
      $query = http_build_query($queryArgs, 'var_');
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
   * Static helper function to build URI with query string
   * result is encoded ready for HTML output
   * 
   * @param string base uri
   * @param array associative array of aurguments
   * @param string fragment identifier
   * @return string URL+HTML encoded
   */
  static function EncodeURI($baseURI, $queryArgs, $fragId = null)
  {
    return Page::Encode(Page::BuildURI($baseURI, $queryArgs, $fragId));
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
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">' . "\n";
  }
  
  
  /**
   * Generate HTML for the entire page.
   * Subclasses should generally overeide more specific methods
   * to generate HTML for sections of the page.
   */
  function writeHTML()
  {
    $this->writeDoctype();
    
    echo "<html lang='en'>\n";
    $this->writeHTMLHead('  ');
    $this->writeHTMLBody('  ');
    echo "</html>";
  }
  

  /**
   * Generate HTML <head>.
   * Subclasses should override specific methods, but may override
   * to replace entire head.
   */
  function writeHTMLHead($indent = '')
  {
    echo $indent . "<head>\n";
    $this->writeHeadBase($indent . '  ');
    $this->writeHeadMetas($indent . '  ');
    $this->writeHeadTitle($indent . '  ');
    $this->writeHeadStyle($indent . '  ');
    $this->writeHeadLinks($indent . '  ');
    $this->writeHeadScript($indent . '  ');
    echo $indent . "</head>\n";
  }


  /**
   * Generate <base> element if needed
   * Subclasses should override getBaseURI to provide a URI
   */
  function writeHeadBase($indent = '')
  {
    $baseURI = $this->getBaseURI();
    if ($baseURI) {
      $baseURI = Page::Encode($baseURI);
      echo $indent . "<base href='{$baseURI}'>\n";
    }
  }


  /**
   * Generate <title> element if needed
   * Subclasses should override getPageTitle to provide a title
   */
  function writeHeadTitle($indent = '')
  {
    $title = $this->getPageTitle();
    if ($title) {
      $title = Page::Encode($title);
      echo $indent . "<title>{$title}</title>\n";
    }
  }

  /**
   * Generate <meta> elements(s)
   */
  function writeHeadMetas($indent = '')
  {
    echo $indent . "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>\n";
  }

  /**
   * Generate <style> element(s)
   */
  function writeHeadStyle($indent = '')
  {  
  }
  

  /**
   * Generate <link> element(s)
   */
  function writeHeadLinks($indent = '')
  {
  }
  
  /**
   * Generate <script> element(s)
   */
  function writeHeadScript($indent = '')
  {
  }


  /**
   * Generate <body>
   *
   * Subclasses should override Header, Content or Footer methods
   */
  function writeHTMLBody($indent = '')
  {
    echo $indent . "<body>\n";
    $this->writeBodyHeader($indent . '  ');
    $this->writeBodyContent($indent . '  ');
    $this->writeBodyFooter($indent . '  ');
    echo $indent . "</body>\n";
  }

  /**
   * Generate header section of html <body>
   */
  function writeBodyHeader($indent = '')
  {
  }

  /**
   * Generate main content section of html <body>
   */
  function writeBodyContent($indent = '')
  {
  }

  /**
   * Generate footer section of html <body>
   */
  function writeBodyFooter($indent = '')
  {
  }
}

?>