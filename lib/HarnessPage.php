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


class HarnessURIConverter extends URIConverter
{
  /**
   * Convert a rewritten URI path back into baseURI and args array
   * Subclasses override to handle specific pages
   */
  function pathToArgs(Array &$components, Array &$queryArgs)
  {
    $pageKey = null;

    $base = array_shift($components);
    switch ($base) {
      case null:
        $pageKey = 'home';
        break;
        
      case 'ua':
        $pageKey = 'home';
        $this->_addQueryArg($queryArgs, 'u', $components);
        break;

      case 'suite':
        $pageKey = 'testsuite';
        $this->_addQueryArg($queryArgs, 's', $components);
        if ('ua' == array_shift($components)) {
          $this->_addQueryArg($queryArgs, 'u', $components);
        }
        break;
        
      case 'agent':
        $pageKey = 'select_ua';
        $this->_addQueryArg($queryArgs, 's', $components);
        break;
        
      case 'test':
        $pageKey = 'testcase';
        $this->_addQueryArg($queryArgs, 's', $components);
        $queryArgs['o'] = 1;
        $next = array_shift($components);
        if ('single' == $next) {
          $this->_addQueryArg($queryArgs, 'c', $components);
        }
        else {
          if ('section' == $next) {
            $this->_addQueryArg($queryArgs, 'sec', $components);
            $next = array_shift($components);
          }
          if (('alpha' == $next) || ('all' == $next)) {
            $queryArgs['o'] = 0;
            $next = array_shift($components);
          }
          if (('ref' == $next) || ('format' == $next) || ('flag' == $next) || ('ua' == $next)) {
            array_unshift($components, $next);
          }
          else {
            $queryArgs['i'] = $next;
          }
        }
        while ($key = array_shift($components)) {
          switch ($key) {
            case 'ref':     $this->_addQueryArg($queryArgs, 'ref', $components); break;
            case 'format':  $this->_addQueryArg($queryArgs, 'f', $components); break;
            case 'flag':    $this->_addQueryArg($queryArgs, 'fl', $components); break;
            case 'ua':      $this->_addQueryArg($queryArgs, 'u', $components); break;
          }
        }
        break;
        
      case 'done':
        $pageKey = 'success';
        $this->_addQueryArg($queryArgs, 's', $components);
        if ('ua' == array_shift($components)) {
          $this->_addQueryArg($queryArgs, 'u', $components);
        }
        break;
        
      case 'review':
        $pageKey = 'review';
        $this->_addQueryArg($queryArgs, 's', $components);
        if ('ua' == array_shift($components)) {
          $this->_addQueryArg($queryArgs, 'u', $components);
        }
        break;
        
      case 'results':
        $pageKey = 'results';
        $this->_addQueryArg($queryArgs, 's', $components);
        $next = array_shift($components);
        if ('grouped' == $next) {
          $queryArgs['o'] = 1;
          $next = array_shift($components);
        }
        if ('section' == $next) {
          $this->_addQueryArg($queryArgs, 'sec', $components);
        }
        else {
          array_unshift($components, $next);
        }
        while ($key = array_shift($components)) {
          switch ($key) {
            case 'filter':            $this->_addQueryArg($queryArgs, 'f', $components); break;
            case 'date':              $this->_addQueryArg($queryArgs, 'm', $components); break;
            case 'engine':            $this->_addQueryArg($queryArgs, 'e', $components); break;
            case 'engine_version':    $this->_addQueryArg($queryArgs, 'v', $components); break;
            case 'browser':           $this->_addQueryArg($queryArgs, 'b', $components); break;
            case 'browser_version':   $this->_addQueryArg($queryArgs, 'bv', $components); break;
            case 'platform':          $this->_addQueryArg($queryArgs, 'p', $components); break;
            case 'platform_version':  $this->_addQueryArg($queryArgs, 'pv', $components); break;
            case 'ua':                $this->_addQueryArg($queryArgs, 'u', $components); break;
            default: $queryArgs['c'] = $key;
          }
        }
        break;
    
      case 'details':
        $pageKey = 'details';
        $this->_addQueryArg($queryArgs, 's', $components);
        $next = array_shift($components);
        if ('grouped' == $next) {
          $queryArgs['o'] = 1;
          $next = array_shift($components);
        }
        if ('section' == $next) {
          $this->_addQueryArg($queryArgs, 'sec', $components);
        }
        else {
          array_unshift($components, $next);
        }
        while ($key = array_shift($components)) {
          switch ($key) {
            case 'date':              $this->_addQueryArg($queryArgs, 'm', $components); break;
            case 'engine':            $this->_addQueryArg($queryArgs, 'e', $components); break;
            case 'engine_version':    $this->_addQueryArg($queryArgs, 'v', $components); break;
            case 'browser':           $this->_addQueryArg($queryArgs, 'b', $components); break;
            case 'browser_version':   $this->_addQueryArg($queryArgs, 'bv', $components); break;
            case 'platform':          $this->_addQueryArg($queryArgs, 'p', $components); break;
            case 'platform_version':  $this->_addQueryArg($queryArgs, 'pv', $components); break;
            case 'ua':                $this->_addQueryArg($queryArgs, 'u', $components); break;
            default: $queryArgs['c'] = $key;
          }
        }
        break;
        
      case 'status':
        $pageKey = 'status_query';
        break;
        
      case 'report':
        $pageKey = 'spider_trap';
        break;

      default:
        array_unshift($components, $base);
    }

    return $pageKey;
  }

  /**
   * Convert query arguments to URI path when rewriting is on
   * Subclasses override to handle specific pages
   *
   * @param string base uri
   * @param array associative array of aurguments
   * @return string uri
   */
  function argsToPath($pageKey, Array &$queryArgs) {
    $uriPath = '';
    
    $keys = explode('.', $pageKey);
    switch (array_shift($keys)) {
      case '':
      case 'home':
        $this->_appendURI($uriPath, 'u', $queryArgs, 'ua');
        break;
        
      case 'testsuite':
        $this->_appendURI($uriPath, 's', $queryArgs, 'suite');
        $this->_appendURI($uriPath, 'u', $queryArgs, 'ua');
        break;
        
      case 'select_ua':
        $this->_appendURI($uriPath, 's', $queryArgs, 'agent');
        break;
        
      case 'testcase':
        $this->_appendURI($uriPath, 's', $queryArgs, 'test');
        if (! $this->_appendURI($uriPath, 'c', $queryArgs, 'single')) {
          $this->_appendURI($uriPath, 'sec', $queryArgs, 'section');
          $this->_appendURIBool($uriPath, 'o', $queryArgs, 'alpha', 0);
          $this->_appendURI($uriPath, 'i', $queryArgs);
        }
        unset($queryArgs['o']);
        $this->_appendURI($uriPath, 'ref', $queryArgs, 'ref');
        $this->_appendURI($uriPath, 'f', $queryArgs, 'format');
        $this->_appendURI($uriPath, 'fl', $queryArgs, 'flag');
        $this->_appendURI($uriPath, 'u', $queryArgs, 'ua');
        break;
        
      case 'success':
        $this->_appendURI($uriPath, 's', $queryArgs, 'done');
        $this->_appendURI($uriPath, 'u', $queryArgs, 'ua');
        break;
  
      case 'review':
        $this->_appendURI($uriPath, 's', $queryArgs, 'review');
        $this->_appendURI($uriPath, 'u', $queryArgs, 'ua');
        break;
        
      case 'results':
        $this->_appendURI($uriPath, 's', $queryArgs, 'results');
        $this->_appendURIBool($uriPath, 'o', $queryArgs, 'grouped');
        if (! $this->_appendURI($uriPath, 'c', $queryArgs)) {
          $this->_appendURI($uriPath, 'sec', $queryArgs, 'section');
        }
        $this->_appendURI($uriPath, 'f', $queryArgs, 'filter');
        $this->_appendURI($uriPath, 'm', $queryArgs, 'date');
        $this->_appendURI($uriPath, 'e', $queryArgs, 'engine');
        $this->_appendURI($uriPath, 'v', $queryArgs, 'engine_version');
        $this->_appendURI($uriPath, 'b', $queryArgs, 'browser');
        $this->_appendURI($uriPath, 'bv', $queryArgs, 'browser_version');
        $this->_appendURI($uriPath, 'p', $queryArgs, 'platform');
        $this->_appendURI($uriPath, 'pv', $queryArgs, 'platform_version');
        $this->_appendURI($uriPath, 'u', $queryArgs, 'ua');
        break;
        
      case 'details':
        $this->_appendURI($uriPath, 's', $queryArgs, 'details');
        $this->_appendURIBool($uriPath, 'o', $queryArgs, 'grouped');
        if (! $this->_appendURI($uriPath, 'c', $queryArgs)) {
          $this->_appendURI($uriPath, 'sec', $queryArgs, 'section');
        }
        $this->_appendURI($uriPath, 'm', $queryArgs, 'date');
        $this->_appendURI($uriPath, 'e', $queryArgs, 'engine');
        $this->_appendURI($uriPath, 'v', $queryArgs, 'engine_version');
        $this->_appendURI($uriPath, 'b', $queryArgs, 'browser');
        $this->_appendURI($uriPath, 'bv', $queryArgs, 'browser_version');
        $this->_appendURI($uriPath, 'p', $queryArgs, 'platform');
        $this->_appendURI($uriPath, 'pv', $queryArgs, 'platform_version');
        $this->_appendURI($uriPath, 'u', $queryArgs, 'ua');
        break;
        
      case 'status_query':
        $uriPath .= 'status/';
        break;
        
      case 'spider_trap':
        $uriPath .= 'report/';
        break;
        
      default:
        trigger_error('Unknown page key');
    }

    return $uriPath;
  }
}


/**
 * Provide functionality specific to test harness pages
 */
class HarnessPage extends DynamicPage
{
  protected static  $gURIConverter = null;
  
  protected $mSpiderTrap;
  
  protected $mTestSuite;
  protected $mUserAgent;
  protected $mUser;

  
  static function GetURIConverter()
  {
    if (! static::$gURIConverter) {
      static::$gURIConverter = new HarnessURIConverter();
    }
    return static::$gURIConverter;
  }

  static function ExternalPageURI($baseURI)
  {
    return self::_MatchURIConnectionSecurity(self::_BuildPageURI($baseURI, null, null, TRUE));
  }
  
  static function ExternalConfigURI($pageKey)
  {
    return self::_MatchURIConnectionSecurity(self::_BuildConfigURI($pageKey, null, null, TRUE));
  }

  function __construct(Array $args = null, Array $pathComponents = null)
  {
    parent::__construct($args, $pathComponents);
    
    $this->mSpiderTrap = new SpiderTrap();
    
    $this->mTestSuite = $this->_requestData('s', 'TestSuite');

    $this->mUserAgent = new UserAgent(intval($this->_requestData('u')));

    if (FALSE !== $this->_requestData('logout')) {
      $httpUserName = null;
      if (array_key_exists('PHP_AUTH_USER', $_SERVER)) {  // if HTTP authentication is on, use that
        $httpUserName = $_SERVER['PHP_AUTH_USER'];
      }
      $this->mUser = new User($httpUserName, null, $this->_cookieData('uid'), $this->_cookieData('key'));
      $this->mUser->didAccess();
    }
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
   * Helper function to build URI to page within system
   *
   * @param string page config key
   * @param array associative array of aurguments
   * @param string fragment identifier
   * @return string URL encoded
   */
  function buildPageURI($pageKey, Array $queryArgs = null, $fragId = null, $absolute = FALSE)
  {
    if ($this->mUserAgent->isActualUA()) {  // XXX also work with UA cookies here
      unset ($queryArgs['u']);
    }
    return parent::buildPageURI($pageKey, $queryArgs, $fragId, $absolute);
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
    $uri = $this->buildPageURI('home', $args);

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