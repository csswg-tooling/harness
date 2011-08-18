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
require_once("lib/TestSuite.php");
require_once("lib/UserAgent.php");

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


  protected function _rewriteURI($baseURI, Array &$queryArgs = null) {
    if (Config::Get('uri.page', 'home') == $baseURI) {
      $baseURI = '';
      $this->_appendURI($baseURI, 'u', $queryArgs, 'ua');
    }
    elseif (Config::Get('uri.page', 'testsuite') == $baseURI) {
      $baseURI = '';
      $this->_appendURI($baseURI, 's', $queryArgs, 'suite');
      $this->_appendURI($baseURI, 'u', $queryArgs, 'ua');
    }
    elseif (Config::Get('uri.page', 'select_ua') == $baseURI) {
      $baseURI = '';
      $this->_appendURI($baseURI, 's', $queryArgs, 'agent');
    }
    elseif (Config::Get('uri.page', 'testcase') == $baseURI) {
      $baseURI = '';
      $this->_appendURI($baseURI, 's', $queryArgs, 'test');
      if (! $this->_appendURI($baseURI, 'c', $queryArgs, 'single')) {
        $this->_appendURI($baseURI, 'sec', $queryArgs, 'section');
        $this->_appendURIBool($baseURI, 'o', $queryArgs, 'alpha', 0);
        $this->_appendURI($baseURI, 'i', $queryArgs);
      }
      unset($queryArgs['o']);
      $this->_appendURI($baseURI, 'ref', $queryArgs, 'ref');
      $this->_appendURI($baseURI, 'f', $queryArgs, 'format');
      $this->_appendURI($baseURI, 'fl', $queryArgs, 'flag');
      $this->_appendURI($baseURI, 'u', $queryArgs, 'ua');
    }
    elseif (Config::Get('uri.page', 'success') == $baseURI) {
      $baseURI = '';
      $this->_appendURI($baseURI, 's', $queryArgs, 'done');
      $this->_appendURI($baseURI, 'u', $queryArgs, 'ua');
    }
    elseif (Config::Get('uri.page', 'review') == $baseURI) {
      $baseURI = '';
      $this->_appendURI($baseURI, 's', $queryArgs, 'review');
      $this->_appendURI($baseURI, 'u', $queryArgs, 'ua');
    }
    elseif (Config::Get('uri.page', 'results') == $baseURI) {
      $baseURI = '';
      $this->_appendURI($baseURI, 's', $queryArgs, 'results');
      $this->_appendURIBool($baseURI, 'o', $queryArgs, 'grouped');
      if (! $this->_appendURI($baseURI, 'c', $queryArgs)) {
        $this->_appendURI($baseURI, 'sec', $queryArgs, 'section');
      }
      $this->_appendURI($baseURI, 'f', $queryArgs, 'filter');
      $this->_appendURI($baseURI, 'm', $queryArgs, 'date');
      $this->_appendURI($baseURI, 'e', $queryArgs, 'engine');
      $this->_appendURI($baseURI, 'v', $queryArgs, 'engine_version');
      $this->_appendURI($baseURI, 'b', $queryArgs, 'browser');
      $this->_appendURI($baseURI, 'bv', $queryArgs, 'browser_version');
      $this->_appendURI($baseURI, 'p', $queryArgs, 'platform');
      $this->_appendURI($baseURI, 'pv', $queryArgs, 'platform_version');
      $this->_appendURI($baseURI, 'u', $queryArgs, 'ua');
    }
    elseif (Config::Get('uri.page', 'details') == $baseURI) {
      $baseURI = '';
      $this->_appendURI($baseURI, 's', $queryArgs, 'details');
      $this->_appendURIBool($baseURI, 'o', $queryArgs, 'grouped');
      if (! $this->_appendURI($baseURI, 'c', $queryArgs)) {
        $this->_appendURI($baseURI, 'sec', $queryArgs, 'section');
      }
      $this->_appendURI($baseURI, 'm', $queryArgs, 'date');
      $this->_appendURI($baseURI, 'e', $queryArgs, 'engine');
      $this->_appendURI($baseURI, 'v', $queryArgs, 'engine_version');
      $this->_appendURI($baseURI, 'b', $queryArgs, 'browser');
      $this->_appendURI($baseURI, 'bv', $queryArgs, 'browser_version');
      $this->_appendURI($baseURI, 'p', $queryArgs, 'platform');
      $this->_appendURI($baseURI, 'pv', $queryArgs, 'platform_version');
      $this->_appendURI($baseURI, 'u', $queryArgs, 'ua');
    }
    return $baseURI;
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