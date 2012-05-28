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

require_once('core/DynamicPage.php');
require_once('core/SpiderTrap.php');
require_once('core/User.php');
require_once('lib/TestSuite.php');
require_once('lib/UserAgent.php');

/**
 * Provide functionality specific to test harness pages
 */
class HarnessPage extends DynamicPage
{
  protected $mSpiderTrap;
  
  protected $mTestSuite;
  protected $mUserAgent;
  protected $mUser;


  function __construct(Array $args = null)
  {
    parent::__construct($args);
    
    $this->mSpiderTrap = new SpiderTrap();
    
    $this->mTestSuite = $this->_requestData('s', 'TestSuite');
    $this->mUserAgent = new UserAgent(intval($this->_requestData('u')));
    $this->mUser = new User(null, $this->_cookieData('uid'), $this->_cookieData('key'));
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
    if ($this->mUserAgent->isActualUA()) {  // XXX also work with UA cookies here
      unset ($queryArgs['u']);
    }
    return parent::buildURI($baseURI, $queryArgs, $fragId, $absolute);
  }

  /**
   * Helper function to build URI with query string
   * This version looks up the uri from the Config system
   * 
   * @param string base uri config key
   * @param array associative array of aurguments
   * @param string fragment identifier
   * @return string URL encoded
   */
  function buildConfigURI($baseURIKey, Array $queryArgs = null, $fragId = null, $absolute = FALSE)
  {
    if ($this->mUserAgent->isActualUA()) {  // XXX also work with UA cookies here
      unset ($queryArgs['u']);
    }
    return parent::buildConfigURI($baseURIKey, $queryArgs, $fragId, $absolute);
  }


  /**
   * Convert a rewritten URI path back into baseURI and args array
   * Subclasses override to handle specific pages
   */
  protected static function _ConvertURIPathToArgs(Array $components, Array &$queryArgs)
  {
    $base = array_shift($components);
    switch ($base) {
      case 'ua':
        $baseURI = Config::Get('uri.page', 'home');
        static::_AddQueryArg($queryArgs, 'u', $components);
        break;

      case 'suite':
        $baseURI = Config::Get('uri.page', 'testsuite');
        static::_AddQueryArg($queryArgs, 's', $components);
        if ('ua' == array_shift($components)) {
          static::_AddQueryArg($queryArgs, 'u', $components);
        }
        break;
        
      case 'agent':
        $baseURI = Config::Get('uri.page', 'select_ua');
        static::_AddQueryArg($queryArgs, 'u', $components);
        break;
        
      case 'test':
        $baseURI = Config::Get('uri.page', 'testcase');
        static::_AddQueryArg($queryArgs, 's', $components);
        $queryArgs['o'] = 1;
        $next = array_shift($components);
        if ('single' == $next) {
          static::_AddQueryArg($queryArgs, 'c', $components);
        }
        else {
          if ('section' == $next) {
            static::_AddQueryArg($queryArgs, 'sec', $components);
            $next = array_shift($components);
          }
          if (('alpha' == $next) || ('all' == $next)) {
            $queryArgs['o'] = 0;
            $next = array_shift($components);
          }
          $queryArgs['i'] = $next;
        }
        while ($key = array_shift($components)) {
          switch ($key) {
            case 'ref':     static::_AddQueryArg($queryArgs, 'ref', $components); break;
            case 'format':  static::_AddQueryArg($queryArgs, 'f', $components); break;
            case 'flag':    static::_AddQueryArg($queryArgs, 'fl', $components); break;
            case 'ua':      static::_AddQueryArg($queryArgs, 'u', $components); break;
          }
        }
        break;
        
      case 'done':
        $baseURI = Config::Get('uri.page', 'success');
        static::_AddQueryArg($queryArgs, 's', $components);
        if ('ua' == array_shift($components)) {
          static::_AddQueryArg($queryArgs, 'u', $components);
        }
        break;
        
      case 'review':
        $baseURI = Config::Get('uri.page', 'review');
        static::_AddQueryArg($queryArgs, 's', $components);
        if ('ua' == array_shift($components)) {
          static::_AddQueryArg($queryArgs, 'u', $components);
        }
        break;
        
      case 'results':
        $baseURI = Config::Get('uri.page', 'results');
        static::_AddQueryArg($queryArgs, 's', $components);
        $next = array_shift($components);
        if ('grouped' == $next) {
          $queryArgs['o'] = 1;
          $next = array_shift($components);
        }
        if ('section' == $next) {
          static::_AddQueryArg($queryArgs, 'sec', $components);
        }
        else {
          array_unshift($componentes, $next);
        }
        while ($key = array_shift($components)) {
          switch ($key) {
            case 'filter':            static::_AddQueryArg($queryArgs, 'f', $components); break;
            case 'date':              static::_AddQueryArg($queryArgs, 'm', $components); break;
            case 'engine':            static::_AddQueryArg($queryArgs, 'e', $components); break;
            case 'engine_version':    static::_AddQueryArg($queryArgs, 'v', $components); break;
            case 'browser':           static::_AddQueryArg($queryArgs, 'b', $components); break;
            case 'browser_version':   static::_AddQueryArg($queryArgs, 'bv', $components); break;
            case 'platform':          static::_AddQueryArg($queryArgs, 'p', $components); break;
            case 'platform_version':  static::_AddQueryArg($queryArgs, 'pv', $components); break;
            case 'ua':                static::_AddQueryArg($queryArgs, 'u', $components); break;
            default: $queryArgs['c'] = $key;
          }
        }
        break;
    
      case 'details':
        $baseURI = Config::Get('uri.page', 'details');
        static::_AddQueryArg($queryArgs, 's', $components);
        $next = array_shift($components);
        if ('grouped' == $next) {
          $queryArgs['o'] = 1;
          $next = array_shift($components);
        }
        if ('section' == $next) {
          static::_AddQueryArg($queryArgs, 'sec', $components);
        }
        else {
          array_unshift($componentes, $next);
        }
        while ($key = array_shift($components)) {
          switch ($key) {
            case 'date':              static::_AddQueryArg($queryArgs, 'm', $components); break;
            case 'engine':            static::_AddQueryArg($queryArgs, 'e', $components); break;
            case 'engine_version':    static::_AddQueryArg($queryArgs, 'v', $components); break;
            case 'browser':           static::_AddQueryArg($queryArgs, 'b', $components); break;
            case 'browser_version':   static::_AddQueryArg($queryArgs, 'bv', $components); break;
            case 'platform':          static::_AddQueryArg($queryArgs, 'p', $components); break;
            case 'platform_version':  static::_AddQueryArg($queryArgs, 'pv', $components); break;
            case 'ua':                static::_AddQueryArg($queryArgs, 'u', $components); break;
            default: $queryArgs['c'] = $key;
          }
        }
        break;
    }
    
    return $baseURI;
  }

  /**
   * Convert query arguments to URI path when rewriting is on
   * Subclasses override to handle specific pages
   *
   * @param string base uri
   * @param array associative array of aurguments
   * @return string uri
   */
  protected static function _ConvertURIArgsToPath($baseURI, Array &$queryArgs = null) {
    $uriPath = '';
    if (('' == $baseURI) || (Config::Get('uri.page', 'home') == $baseURI)) {
      static::_AppendURI($uriPath, 'u', $queryArgs, 'ua');
    }
    elseif (Config::Get('uri.page', 'testsuite') == $baseURI) {
      static::_AppendURI($uriPath, 's', $queryArgs, 'suite');
      static::_AppendURI($uriPath, 'u', $queryArgs, 'ua');
    }
    elseif (Config::Get('uri.page', 'select_ua') == $baseURI) {
      static::_AppendURI($uriPath, 's', $queryArgs, 'agent');
    }
    elseif (Config::Get('uri.page', 'testcase') == $baseURI) {
      static::_AppendURI($uriPath, 's', $queryArgs, 'test');
      if (! static::_AppendURI($uriPath, 'c', $queryArgs, 'single')) {
        static::_AppendURI($uriPath, 'sec', $queryArgs, 'section');
        static::_AppendURIBool($uriPath, 'o', $queryArgs, 'alpha', 0);
        static::_AppendURI($uriPath, 'i', $queryArgs);
      }
      unset($queryArgs['o']);
      static::_AppendURI($uriPath, 'ref', $queryArgs, 'ref');
      static::_AppendURI($uriPath, 'f', $queryArgs, 'format');
      static::_AppendURI($uriPath, 'fl', $queryArgs, 'flag');
      static::_AppendURI($uriPath, 'u', $queryArgs, 'ua');
    }
    elseif (Config::Get('uri.page', 'success') == $baseURI) {
      static::_AppendURI($uriPath, 's', $queryArgs, 'done');
      static::_AppendURI($uriPath, 'u', $queryArgs, 'ua');
    }
    elseif (Config::Get('uri.page', 'review') == $baseURI) {
      static::_AppendURI($uriPath, 's', $queryArgs, 'review');
      static::_AppendURI($uriPath, 'u', $queryArgs, 'ua');
    }
    elseif (Config::Get('uri.page', 'results') == $baseURI) {
      static::_AppendURI($uriPath, 's', $queryArgs, 'results');
      static::_AppendURIBool($uriPath, 'o', $queryArgs, 'grouped');
      if (! static::_AppendURI($uriPath, 'c', $queryArgs)) {
        static::_AppendURI($uriPath, 'sec', $queryArgs, 'section');
      }
      static::_AppendURI($uriPath, 'f', $queryArgs, 'filter');
      static::_AppendURI($uriPath, 'm', $queryArgs, 'date');
      static::_AppendURI($uriPath, 'e', $queryArgs, 'engine');
      static::_AppendURI($uriPath, 'v', $queryArgs, 'engine_version');
      static::_AppendURI($uriPath, 'b', $queryArgs, 'browser');
      static::_AppendURI($uriPath, 'bv', $queryArgs, 'browser_version');
      static::_AppendURI($uriPath, 'p', $queryArgs, 'platform');
      static::_AppendURI($uriPath, 'pv', $queryArgs, 'platform_version');
      static::_AppendURI($uriPath, 'u', $queryArgs, 'ua');
    }
    elseif (Config::Get('uri.page', 'details') == $baseURI) {
      static::_AppendURI($uriPath, 's', $queryArgs, 'details');
      static::_AppendURIBool($uriPath, 'o', $queryArgs, 'grouped');
      if (! static::_AppendURI($uriPath, 'c', $queryArgs)) {
        static::_AppendURI($uriPath, 'sec', $queryArgs, 'section');
      }
      static::_AppendURI($uriPath, 'm', $queryArgs, 'date');
      static::_AppendURI($uriPath, 'e', $queryArgs, 'engine');
      static::_AppendURI($uriPath, 'v', $queryArgs, 'engine_version');
      static::_AppendURI($uriPath, 'b', $queryArgs, 'browser');
      static::_AppendURI($uriPath, 'bv', $queryArgs, 'browser_version');
      static::_AppendURI($uriPath, 'p', $queryArgs, 'platform');
      static::_AppendURI($uriPath, 'pv', $queryArgs, 'platform_version');
      static::_AppendURI($uriPath, 'u', $queryArgs, 'ua');
    }
    else {
      $uriPath = $baseURI;
    }
    return $uriPath;
  }


  function getPageTitle()
  {
    if ($this->mTestSuite) {
      return $this->mTestSuite->getTitle();
    }
    return "W3C Conformance Test Harness";
  }
  
  
  /**
   * Override to provide titles and URIs for navigation links
   *
   * @return array of compact($title, $uri)
   */
  function getNavURIs()
  {
    $args['u'] = $this->mUserAgent->getId();
    
    $title = "Home";
    $uri = $this->buildConfigURI('page.home', $args);

    return array(compact('title', 'uri'));
  }


  /**
   * Generate <style> element
   */
  function writeHeadStyle()
  {
    if ($this->mSpiderTrap) {
      $this->addStyleElement('a.report { display: none; }'); // ensure spider trap links are hidden
    }

    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.base'));
  }
  
  
  /**
   * Add spider trap link to page if enabled
   */
  function addSpiderTrap()
  {
    if ($this->mSpiderTrap) {
      $this->mSpiderTrap->addTrapLinkTo($this);
    }
  }
  
  
  function writeLargeW3CLogo()
  {
    $attrs['class'] = 'logo';
    $attrs['href'] = 'http://www.w3.org/';
    $attrs['rel'] = 'home';
    $this->openElement('a', $attrs);

    unset($attrs);
    $attrs['alt'] = 'W3C';
    $attrs['height'] = 48;
    $attrs['width'] = 315;
    $attrs['src'] = 'http://www.w3.org/Icons/w3c_main';
    $this->addElement('img', $attrs);

    $this->closeElement('a');
  }
  
  
  function writeSmallW3CLogo()
  {
    $attrs['class'] = 'logo';
    $attrs['href'] = 'http://www.w3.org/';
    $attrs['rel'] = 'home';
    $this->openElement('a', $attrs);

    unset($attrs);
    $attrs['alt'] = 'W3C';
    $attrs['height'] = 48;
    $attrs['width'] = 72;
    $attrs['src'] = 'http://www.w3.org/Icons/w3c_home';
    $this->addElement('img', $attrs);

    $this->closeElement('a');
  }
  
  
  function writeNavLinks($elementName = 'p', $class = 'nav', Array $attrs = null)
  {
    $navURIs = $this->getNavURIs();
    if ($navURIs && (1 < count($navURIs))) {
      if ($class) {
        $attrs['class'] = $class;
      }
      $this->openElement($elementName, $attrs, FALSE);
    
      $index = -1;
      $last = (count($navURIs) - 1);
      foreach ($navURIs as $navURI) {
        $index++;
        extract($navURI); // uri, title
        if ($index < $last) {
          $this->addHyperLink($uri, null, $title);
          $this->addTextContent('&nbsp;&raquo; ', FALSE);
        }
        else {
          $this->addElement('a', null, $title);
        }
      }
      $this->closeElement($elementName);
    }
  }
  

  function writeContentTitle($elementName = 'h1', Array $attrs = null)
  {
    $title = $this->getContentTitle();
    
    if ($title) {
      $this->addElement($elementName, $attrs, $title);
    }
  }


  /**
   * Generate header section of <body>
   */
  function writeBodyHeader()
  {
    $this->openElement('div', array('class' => 'header'));
    
    $this->addSpiderTrap();
    
    $this->writeLargeW3CLogo();

    $this->writeNavLinks();
    $this->writeContentTitle();
    
    $this->closeElement('div');
  }


  /**
   * Generate footer section of <body>
   */
  function writeBodyFooter()
  {
    $contactName = '';
    $contactURI = '';
    
    if ($this->mTestSuite) {
      $contactName = $this->mTestSuite->getContactName();
      $contactURI = $this->mTestSuite->getContactURI();
    }
    
    if (! $contactName) {
      $contactName = Config::Get('contact', 'name');
    }
    if (! $contactURI) {
      $contactURI = Config::Get('contact', 'uri');
      if ((! $contactURI) && isset($_SERVER['SERVER_ADMIN'])) {
        $contactURI = 'mailto:' . $_SERVER['SERVER_ADMIN'];
      }
    }
  
    $this->addElement('hr');

    $this->openElement('address');
    if ($contactName && $contactURI) {
      $this->addTextContent('Please send comments, questions, and error reports to ');

      if (0 === stripos($contactURI, 'mailto:')) {
        $mailArgs['subject'] = 'W3C Conformance Test Harness';
        $this->addEmailHyperLink($contactURI, $contactName, $mailArgs);
      }
      else {
        $this->addHyperLink($contactURI, null, $contactName);
      }
    }
    $this->addSpiderTrap();
    $this->closeElement('address');
  }
}

?>