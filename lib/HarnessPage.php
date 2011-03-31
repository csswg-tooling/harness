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

require_once("lib/DynamicPage.php");
require_once("lib/SpiderTrap.php");
require_once("lib/TestSuite.php");
require_once("lib/UserAgent.php");
require_once("lib/User.php");

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
    $this->mUser = new User();
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
    // XXX if mod_rewrite, remove 's' arg and convert to path
    if ($this->mUserAgent->isActualUA()) {  // XXX also work with UA cookies here
      unset ($queryArgs['u']);
    }
    return parent::buildURI($baseURI, $queryArgs, $fragId);
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
    $uri = $this->buildURI("./", $args);

    return array(compact('title', 'uri'));
  }


  /**
   * Generate <style> element
   */
  function writeHeadStyle()
  {
    $this->addStyleElement('a.report { display: none; }'); // ensure spider trap links are hidden

    $this->addStyleSheetLink('base.css');
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
      $this->openElement($elementName, $attrs);
    
      $index = -1;
      $last = (count($navURIs) - 1);
      foreach ($navURIs as $navURI) {
        $index++;
        extract($navURI); // uri, title
        if ($index < $last) {
          $this->addHyperLink($uri, null, $title);
          $this->addTextContent(' &raquo; ', FALSE);
        }
        else {
          $this->addTextContent($title);
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
    
    $this->mSpiderTrap->addTrapLinkTo($this);
    $this->writeLargeW3CLogo();

    $this->writeNavLinks();
    $this->writeContentTitle();
    
    $this->closeElement('div');
  }

  /**
   * Generate error version of page
   */
  function writeBodyError()
  {
    if (isset($this->mSpiderTrap)) {
      $this->mSpiderTrap->addTrapLinkTo($this);
    }
    
    parent::writeBodyError();
    
    if (isset($this->mSpiderTrap)) {
      $this->mSpiderTrap->addTrapLinkTo($this);
    }
  }
  

  /**
   * Generate footer section of <body>
   */
  function writeBodyFooter()
  {
    $contactName = CONTACT_NAME;
    $contactURI = CONTACT_URI;
    if ($this->mTestSuite) {
      $contactName = $this->mTestSuite->getContactName();
      $contactURI = $this->mTestSuite->getContactURI();
    }
  
    $this->addElement('hr');

    $this->openElement('address');
    $this->addTextContent('Please send comments, questions, and error reports to ');
    $this->addHyperLink($contactURI, null, $contactName);
    $this->closeElement('address');
    
    $this->mSpiderTrap->addTrapLinkTo($this);
  }
}

?>