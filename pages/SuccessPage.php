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
require_once("lib/TestSuites.php");


/**
 * Class for generating the success page after entering all results
 */
class SuccessPage extends HarnessPage
{

  static function GetPageKey()
  {
    return 'success';
  }

  function __construct(Array $args = null, Array $pathComponents = null)
  {
    parent::__construct($args, $pathComponents);

  }


  function getNavURIs()
  {
    $uris = parent::getNavURIs();
    
    if ($this->mTestSuite) {
      $title = "Enter Data";
      $args['suite'] = $this->mTestSuite->getName();
      $args['ua'] = $this->mUserAgent->getId();

      $uri = $this->buildPageURI('testsuite', $args);
      $uris[] = compact('title', 'uri');
      
      $title = "Success";
      $uri = '';
      $uris[] = compact('title', 'uri');
    }
    return $uris;
  }


  function writeBodyContent()
  {
    if ($this->mTestSuite) {
      $this->openElement('div', array('class' => 'body'));
      
      $this->addElement('p', null, "Thank you for providing test result data for the " .
                                   $this->mTestSuite->getTitle());

      $args['ua'] = $this->mUserAgent->getId();
      $homeURI = $this->buildPageURI('home', $args);

      $args['suite'] = $this->mTestSuite->getName();
      $reviewURI = $this->buildPageURI('review', $args);
      $enterURI = $this->buildPageURI('testsuite', $args);

      $this->openElement('p', null, FALSE);
      $this->addTextContent("You can ");
      $this->addHyperLink($enterURI, null, "enter additional data");
      $this->addTextContent(", ");
      $this->addHyperLink($reviewURI, null, "review results");
      $this->addTextContent(", or access other test suites from the ");
      $this->addHyperLink($homeURI, null, "harness home page");
      $this->addTextContent(".");
      $this->closeElement('p');

      $this->closeElement('div');
    }
  }
}

?>