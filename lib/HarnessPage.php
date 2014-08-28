<?php
/*******************************************************************************
 *
 *  Copyright © 2008-2014 Hewlett-Packard Development Company, L.P. 
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

require_once('core/SystemPage.php');

require_once('modules/testsuite/TestSuite.php');
require_once('modules/useragent/UserAgent.php');


/**
 * Provide functionality specific to test harness pages
 */
class HarnessPage extends SystemPage
{

  protected $mTestSuite;
  protected $mUserAgent;

  
  protected function _initPage()
  {
    parent::_initPage();
    
    $this->mTestSuite = $this->_requestData('suite', 'TestSuite');
    $this->mUserAgent = new UserAgent(intval($this->_requestData('ua')));
  }
  
  
  function writeLargeW3CLogo()
  {
    $attrs['class'] = 'logo';
    $attrs['href'] = 'http://www.w3.org/';
    $attrs['rel'] = 'home';
    $this->openElement('a', $attrs);
    
    unset($attrs);
    $attrs['alt'] = 'W3C';
    $attrs['src'] = 'http://www.w3.org/Icons/w3c_main';
    $this->addElement('img', $attrs);
    
    $this->closeElement('a');
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
      unset ($queryArgs['ua']);
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
      unset ($queryArgs['ua']);
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
      unset ($queryArgs['ua']);
    }
    return parent::buildConfigURI($baseURIKey, $queryArgs, $fragId, $absolute);
  }


  function getPageTitle()
  {
    if ($this->mTestSuite && $this->mTestSuite->isValid()) {
      return $this->mTestSuite->getTitle();
    }
    return parent::getPageTitle();
  }
  
  
  /**
   * Override to provide titles and URIs for navigation links
   *
   * @return array of compact($title, $uri)
   */
  function getNavURIs()
  {
    $args['ua'] = $this->mUserAgent->getId();
    
    $title = "Home";
    $uri = $this->buildPageURI('home', $args);

    return array(compact('title', 'uri'));
  }



}

?>