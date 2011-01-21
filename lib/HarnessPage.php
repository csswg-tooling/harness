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

/**
 * Provide functionality specific to test harness pages
 */
class HarnessPage extends DynamicPage
{
  protected $mSpiderTrap;
  
  protected $mTestSuite;
  protected $mUserAgent;


  function __construct() 
  {
    parent::__construct();
    
    $this->mSpiderTrap = new SpiderTrap();
    
    if ($testSuiteName = $this->_requestData('s')) {
      $this->mTestSuite = new TestSuite($testSuiteName);
      if (! $this->mTestSuite->isValid()) {
        $this->mTestSuite = null;
      }
    }
    
    $this->mUserAgent = new UserAgent(intval($this->_requestData('u')));
  }  
  
  function getPageTitle()
  {
    if ($this->mTestSuite) {
      return $this->mTestSuite->getTitle();
    }
    return "W3C Conformance Test Harness";
  }
  
  
  /**
   * Generate <style> element
   */
  function writeHeadStyle($indent = '')
  {
    echo $indent . "<style type='text/css'>\n";
    echo $indent . "  a.report { display: none; }\n";
    echo $indent . "  p.nav { font-size: smaller; }\n";
    echo $indent . "</style>\n";  
    echo $indent . "<link rel='stylesheet' href='http://www.w3.org/StyleSheets/TR/base.css' type='text/css'>\n";
  }
  
  
  function writeLargeW3CLogo($indent = '')
  {
    echo $indent . "<a class='logo' href='http://www.w3.org/' rel='home'>\n";
    echo $indent . "  <img alt='W3C' height='48' width='315' src='http://www.w3.org/Icons/w3c_main'>\n";
    echo $indent . "</a>\n";
  }
  
  
  function writeSmallW3CLogo($indent = '')
  {
    echo $indent . "<a class='logo' href='http://www.w3.org/' rel='home'>\n";
    echo $indent . "  <img alt='W3C' height='48' width='72' src='http://www.w3.org/Icons/w3c_home'>\n";
    echo $indent . "</a>\n";
  }
  
  
  function writeNavLinks($indent = '', $element = "p", $attrs = "class='nav'")
  {
    $navURIs = $this->getNavURIs();
    if ($navURIs && (1 < count($navURIs))) {
      echo $indent . "<{$element}" . ($attrs ? " {$attrs}>\n" : ">\n");
    
      $index = -1;
      $last = (count($navURIs) - 1);
      foreach ($navURIs as $navURI) {
        $index++;
        extract($navURI);
        $uri = Page::Encode($uri);
        $title = Page::Encode($title);
        if ($index < $last) {
          echo $indent . "  <a href='{$uri}'>{$title}</a> &raquo; \n";
        }
        else {
          echo $indent . "  $title\n";
        }
      }
      echo $indent . "</{$element}>\n";
    }
  }
  
  function writeContentTitle($indent = '', $element = "h1", $attrs = "")
  {
    $title = $this->getContentTitle();
    
    if ($title) {
      echo $indent . "<{$element}" . ($attrs ? " {$attrs}>\n" : ">\n");
      echo $indent . "  {$this->getContentTitle()}\n";
      echo $indent . "</{$element}>\n";
    }
  }

  /**
   * Generate header section of <body>
   */
  function writeBodyHeader($indent = '')
  {
    echo $indent . "<div class='header'>\n";
    
    $this->mSpiderTrap->writeTrapLink($indent . '  ');
    $this->writeLargeW3CLogo($indent . '  ');

    $this->writeNavLinks($indent . '  ');
    $this->writeContentTitle($indent . '  ');
    
    echo $indent . "</div>\n";
  }

  /**
   * Generate error version of page
   */
  function writeBodyError($indent = '')
  {
    $this->mSpiderTrap->writeTrapLink($indent);
    
    parent::writeBodyError($indent);
    
    $this->mSpiderTrap->writeTrapLink($indent);
  }
  

  /**
   * Generate footer section of <body>
   */
  function writeBodyFooter($indent = '')
  {
    $contactName = CONTACT_NAME;
    $contactURI = CONTACT_URI;
    if ($this->mTestSuite) {
      $contactName = $this->mTestSuite->getContactName();
      $contactURI = $this->mTestSuite->getContactURI();
    }
    $contactName = Page::Encode($contactName);
    $contactURI = Page::Encode($contactURI);
  
    echo $indent . "<hr />\n";

    echo $indent . "<address>\n";
    echo $indent . "  Please send comments, questions, and error reports to\n";
    echo $indent . "  <a href='{$contactURI}'>{$contactName}</a>.\n";    
    echo $indent . "</address>\n";
    
    $this->mSpiderTrap->writeTrapLink($indent);
  }
}

?>