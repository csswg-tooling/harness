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
class SelectUserAgentPage extends HarnessPage
{  
  protected $mSubmitData;


  function __construct() 
  {
    parent::__construct();
    
    $this->mSubmitData = $this->mGetData;
    unset($this->mSubmitData['u']);

  }  


  function getNavURIs()
  {
    $uris = parent::getNavURIs();
    
    if ($this->mTestSuite) {
      $title = "Enter Data";
      $args['s'] = $this->mTestSuite->getName();
      $args['u'] = $this->mUserAgent->getId();
      $uri = Page::BuildURI(TESTSUITE_PAGE_URI, $args);
      $uris[] = compact('title', 'uri');
      
      $title = "Select User Agent";
      $uri = '';
      $uris[] = compact('title', 'uri');
    }
    return $uris;
  }

  
  function writeHiddenFormControls($indent = '')
  {
    foreach($this->mSubmitData as $opt => $value) {
      $opt = Page::Encode($opt);
      $value = Page::Encode($value);
      echo $indent . "<input type='hidden' name='{$opt}' value='{$value}'>\n";
    }
  }

  function writeBodyContent($indent = '')
  {

    echo $indent . "<p>\n";
    echo $indent . "  This page allows you to enter test results for a user agent ";
    echo             "other than the one you are currently using.\n";
    echo $indent . "</p>\n";
    
    echo $indent . "<p>\n";
    echo $indent . "  This capability is intended ONLY for entering results for user agents ";
    echo             "that are not capable of using the test harness, such as non-interactive ";
    echo             "page converters. If the other user agent is capable of running the harness ";
    echo             "please use it instead.\n";
    echo $indent . "</p>\n";
    
    echo $indent . "<p>\n";
    echo $indent . "  When doing this, you must NOT rely on the rendering of test or reference ";
    echo             "pages in the harness, but only on results as observed in the other user agent.\n";
    echo $indent . "</p>\n";
    
    $userAgents = UserAgent::GetAllUserAgents();

    if (0 < count($userAgents)) {
      echo $indent . "<p>\n";
      echo $indent . "  You may select from one of the following known user agents, ";
      echo             "or enter a custom user agent string below:\n";
      echo $indent . "</p>\n";

      echo $indent . "<form action='" . TESTSUITE_PAGE_URI . "' method='get'>\n";
      $this->writeHiddenFormControls($indent . "  ");

      echo $indent . "  <select name='u' size='10' style='width: 80%'>\n";
      $actualUAId = $this->mUserAgent->getActualUA()->getId();
      foreach ($userAgents as $userAgent) {
        $uaId = $userAgent->getId();
        $uaString = Page::Encode($userAgent->getUAString());
        if ($uaId == $actualUAId) {
          $selected = 'selected ';
        }
        else {
          $selected = '';
        }
        echo $indent . "    <option {$selected}value='{$uaId}'>{$uaString}</option>\n";
      }
      echo $indent . "  </select>\n";
      
      echo $indent . "  <input type='submit' value='Select'>\n";
      echo $indent . "</form>\n";
    }
    else {
      echo $indent . "<p>\n";
      echo $indent . "  You may enter a custom user agent string below:\n";
      echo $indent . "</p>\n";
    }
    
    echo $indent . "<form action='" . SET_UA_PAGE_URI . "' method='post'>\n";
    $this->writeHiddenFormControls($indent . "  ");

    echo $indent . "  <p>\n";
    echo $indent . "    Custom User Agent String: \n";
    echo $indent . "    <input type='text' name='ua' size='80'>\n";
    echo $indent . "    <input type='submit' value='Enter'>\n";
    echo $indent . "  </p>\n";
    
    echo $indent . "</form>\n";
  }
}

?>