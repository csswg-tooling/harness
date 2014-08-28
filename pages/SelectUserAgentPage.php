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


require_once('lib/HarnessPage.php');
require_once('modules/useragent/UserAgent.php');

/**
 * A page to select a different user agent for entering test results
 */
class SelectUserAgentPage extends HarnessPage
{
  protected $mRedirectURI;
  

  static function GetPageKey()
  {
    return 'select_ua';
  }


  function __construct(Array $args = null, Array $pathComponents = null)
  {
    parent::__construct($args, $pathComponents);
    
    $this->mSubmitData = $this->_uriData();
    unset($this->mSubmitData['ua']);

    $this->mRedirectURI = null;
    
    if ($action = $this->_postData('action')) {
      if ('Enter' == $action) {
        $uaString = $this->_postData('ua');
        if ($uaString) {
          $this->mUserAgent = new UserAgent($uaString);
          $this->mUserAgent->update();
        }
      }
      
      $args['suite'] = $this->mTestSuite->getName();
      $args['ua'] = $this->mUserAgent->getId();

      $this->mRedirectURI = $this->buildPageURI('testsuite', $args);
    }
  }  

  function getRedirectURI()
  {
    return $this->mRedirectURI;
  }

  function getNavURIs()
  {
    $uris = parent::getNavURIs();
    
    if ($this->mTestSuite && $this->mTestSuite->isValid()) {
      $title = "Enter Data";
      $args['suite'] = $this->mTestSuite->getName();
      $args['ua'] = $this->mUserAgent->getId();

      $uri = $this->buildPageURI('testsuite', $args);
      $uris[] = compact('title', 'uri');
    }
      
    $title = "Select User Agent";
    $uri = '';
    $uris[] = compact('title', 'uri');

    return $uris;
  }

  
  protected function _splitByEngine($userAgents)
  {
    foreach ($userAgents as $userAgent) {
      $engineTitle = $userAgent->getEngineTitle();
      $engines[$engineTitle][] = $userAgent;
    }
    uksort($engines, 'strnatcasecmp');
    return $engines;
  }
  
  protected function _splitByBrowser($userAgents)
  {
    foreach ($userAgents as $userAgent) {
      $browserTitle = $userAgent->getBrowserTitle();
      $browserVersion = $userAgent->getBrowserVersion();
      if (0 < strlen($browserVersion)) {
        $browserTitle .= ' ' . $browserVersion;
      }
      $browsers[$browserTitle][] = $userAgent;
    }
    uksort($browsers, 'strnatcasecmp');
    return $browsers;
  }
  
  protected function _splitByPlatform($userAgents)
  {
    foreach ($userAgents as $userAgent) {
      $platformTitle = $userAgent->getPlatformTitle();
      if (0 == strlen($platformTitle)) {
        $platformTitle = "Unknown";
      }
      $platforms[$platformTitle][] = $userAgent;
    }
    uksort($platforms, 'strnatcasecmp');
    return $platforms;
  }

  function writeBodyContent()
  {
    $this->openElement('div', array('class' => 'body'));

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
    uasort($userAgents, array('UserAgent', 'CompareUAString'));

    if (0 < count($userAgents)) {
      $userAgents = $this->_splitByEngine($userAgents);
      foreach ($userAgents as $engineTitle => $agentsByEngine) {
        $userAgents[$engineTitle] = $this->_splitByBrowser($agentsByEngine);
      }
      foreach ($userAgents as $engineTitle => $agentsByEngine) {
        foreach ($agentsByEngine as $browser => $agentsByBrowser) {
          $userAgents[$engineTitle][$browser] = $this->_splitByPlatform($agentsByBrowser);
        }
      }
    
      $this->addElement('p', null, 
                        "You may select from one of the following known user agents, " .
                        "or enter a custom user agent string below:");

      $this->openFormElement($this->buildPageURI(null, $this->_uriData()), 'post');
      $this->writeHiddenFormControls();

      $attrs['size'] = 10;
      $attrs['style'] = 'width: 80%';
      $this->openSelectElement('ua', $attrs);
      
      foreach ($userAgents as $engineTitle => $agentsByEngine) {
        $this->openElement('optgroup', array('label' => $engineTitle));
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

      $this->addTextContent(' ');
      $this->addInputElement('submit', 'action', 'Select');
      
      $this->closeElement('form');
    }
    else {
      $this->addElement('p', null, "You may enter a custom user agent string below:");
    }

    $this->openElement('p');
    $this->openFormElement($this->buildPageURI(null, $this->_uriData()), 'post');
    $this->writeHiddenFormControls();
    $this->addLabelElement('uatext', 'Custom User Agent String: ');
    $this->addInputElement('text', 'ua', null, 'uatext', array('size' => 80));
    $this->addTextContent(' ');
    $this->addInputElement('submit', 'action', 'Enter');
    $this->closeElement('form');

    $this->openFormElement($this->buildPageURI(null, $this->_uriData()), 'post');
    $this->writeHiddenFormControls();
    $this->addTextContent(' ');
    $this->addInputElement('submit', 'action', 'Cancel');
    $this->closeElement('form');
    $this->closeElement('p');

    $this->closeElement('div');
  }
}

?>