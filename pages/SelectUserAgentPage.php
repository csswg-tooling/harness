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

      $uri = $this->buildURI(TESTSUITE_PAGE_URI, $args);
      $uris[] = compact('title', 'uri');
      
      $title = "Select User Agent";
      $uri = '';
      $uris[] = compact('title', 'uri');
    }
    return $uris;
  }

  
  protected function _splitByEngine($userAgents)
  {
    foreach ($userAgents as $userAgent) {
      $engine = $userAgent->getEngine();
      if (0 == strlen($engine)) {
        $engine = "Other";
      }
      $engines[$engine][] = $userAgent;
    }
    ksort($engines);
    return $engines;
  }
  
  protected function _splitByBrowser($userAgents)
  {
    foreach ($userAgents as $userAgent) {
      $browser = $userAgent->getBrowser();
      $browserVersion = $userAgent->getBrowserVersion();
      if (0 < strlen($browserVersion)) {
        $browser .= ' ' . $browserVersion;
      }
      $browsers[$browser][] = $userAgent;
    }
    ksort($browsers);
    return $browsers;
  }
  
  protected function _splitByPlatform($userAgents)
  {
    foreach ($userAgents as $userAgent) {
      $platform = $userAgent->getPlatform();
      if (0 == strlen($platform)) {
        $platform = "Unknown";
      }
      $platforms[$platform][] = $userAgent;
    }
    ksort($platforms);
    return $platforms;
  }

  function writeBodyContent()
  {
    $this->addElement('p', null, 
                      "This page allows you to enter test results for a user agent " . 
                      "other than the one you are currently using.");

    $this->addElement('p', null, 
                      "This capability is intended ONLY for entering results for user agents " .
                      "that are not capable of using the test harness, such as non-interactive " .
                      "page converters. If the other user agent is capable of running the harness, " .
                      "please use it instead.");

    $this->addElement('p', null, 
                      "When doing this, you must NOT rely on the rendering of test or reference " .
                      "pages in the harness, but only on results as observed in the other user agent.");
    
    $userAgents = UserAgent::GetAllUserAgents();

    if (0 < count($userAgents)) {
      $userAgents = $this->_splitByEngine($userAgents);
      foreach ($userAgents as $engine => $agentsByEngine) {
        $userAgents[$engine] = $this->_splitByBrowser($agentsByEngine);
      }
      foreach ($userAgents as $engine => $agentsByEngine) {
        foreach ($agentsByEngine as $browser => $agentsByBrowser) {
          $userAgents[$engine][$browser] = $this->_splitByPlatform($agentsByBrowser);
        }
      }
    
      $this->addElement('p', null, 
                        "You may select from one of the following known user agents, " .
                        "or enter a custom user agent string below:");

      $this->openFormElement(TESTSUITE_PAGE_URI);
      $this->writeHiddenFormControls();

      $attrs['size'] = 10;
      $attrs['style'] = 'width: 80%';
      $this->openSelectElement('u', $attrs);
      
      foreach ($userAgents as $engine => $agentsByEngine) {
        $this->openElement('optgroup', array('label' => $engine));
        foreach ($agentsByEngine as $browser => $agentsByBrowser) {
          foreach ($agentsByBrowser as $platform => $agentsByPlatform) {
            foreach ($agentsByPlatform as $userAgent) {
              $this->addOptionElement($userAgent->getId(), 
                                      array('selected' => $userAgent->isActualUA()),
                                      "{$browser} - {$platform} - {$userAgent->getUAString()}");
            }
          }
        }
        $this->closeElement('optgroup');
      }
      $this->closeElement('select');

      $this->addInputElement('submit', null, 'Select');
      
      $this->closeElement('form');
    }
    else {
      $this->addElement('p', null, "You may enter a custom user agent string below:");
    }

    $this->openElement('p');
    $this->openFormElement(SET_UA_PAGE_URI, 'post');
    $this->writeHiddenFormControls();
    $this->addTextContent("Custom User Agent String: ");
    $this->addInputElement('text', 'ua', null, array('size' => 80));
    $this->addInputElement('submit', 'action', 'Enter');
    $this->closeElement('form');

    $this->openFormElement(TESTSUITE_PAGE_URI);
    $this->writeHiddenFormControls();
    $this->addInputElement('submit', null, 'Cancel');
    $this->closeElement('form');
    $this->closeElement('p');
  }
}

?>