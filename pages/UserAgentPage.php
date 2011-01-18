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
 * A page to select a different user agent for entering test results
 */
class UserAgentPage extends HarnessPage
{  
  protected $mNewURI;


  function __construct() 
  {
    parent::__construct();
    
    $this->$mNewURI = './';
    
    // XXX need to accept user agent string and initialize mUserAgent with it
    
    if ($this->mTestSuite) {
      $query['s'] = $this->mTestSuite->getName();
      
      $this->mUserAgent->update();
      $query['u'] = $this->mUserAgent->getId();

      $this->mNewURI = 'testsuite?' . http_build_query($query, 'var_');
    }

  }  


  /**
   * Redirect 
   */
  function getRedirectURI()
  {
    return $this->$mNewURI;
  }


  function writeBodyContent($indent = '')
  {

    echo $indent . "<p>\n";
    echo $indent . "  You have requested to provide results for the following user agent:\n";
    
    $uaString = Page::Encode($this->mUserAgent->getUAString());
    $uaDescription = Page::Encode($this->mUserAgent->getDescription());
    echo $indent . "  <abbr title='{$uaString}'>{$uaDescription}</abbr>\n";
    echo $indent . "</p>\n";

    echo $indent . "<p>\n";
    echo $indent . "  We have processed your request and you should have been redirected \n";
    echo $indent . "  <a href='{$this->$mNewURI}'>here</a>.\n";
    echo $indent . "</p>\n";

  }
}

?>