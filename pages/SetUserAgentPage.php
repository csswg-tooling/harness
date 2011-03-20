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


require_once("lib/HarnessPage.php");
require_once("lib/UserAgent.php");

/**
 * A page to set a different user agent for entering test results
 */
class SetUserAgentPage extends HarnessPage
{  
  protected $mNewURI;


  function __construct() 
  {
    parent::__construct();
    
    if ('Enter' == $this->_postData('action')) {
      $uaString = $this->_postData('ua');
      if ($uaString) {
        $this->mUserAgent = new UserAgent($uaString);
        $this->mUserAgent->update();
      }
    }
    
    $this->mNewURI = './';
    
    if ($this->mTestSuite) {
      $args['s'] = $this->mTestSuite->getName();
      
      $this->mUserAgent->update();

      $args['u'] = $this->mUserAgent->getId();

      $this->mNewURI = $this->buildURI(TESTSUITE_PAGE_URI, $args);
    }

  }  


  /**
   * Redirect 
   */
  function getRedirectURI()
  {
    return $this->mNewURI;
  }


  function writeBodyContent()
  {
    $this->openElement('p');
    $this->addTextContent("You have requested to provide results for the following user agent: ");
    $this->addAbbrElement($this->mUserAgent->getUAString(), null, $this->mUserAgent->getDescription());
    $this->closeElement('p');

    $this->openElement('p', null, FALSE);
    $this->addTextContent("We have processed your request and you should have been redirected ");
    $this->addHyperLink($this->mNewURI, null, "here");
    $this->addTextContent(".");
    $this->closeElement('p');

  }
}

?>