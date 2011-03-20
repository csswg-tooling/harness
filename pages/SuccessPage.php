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

  function __construct() 
  {
    parent::__construct();

    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      trigger_error($msg, E_USER_WARNING);
    }
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
      
      $title = "Success";
      $uri = '';
      $uris[] = compact('title', 'uri');
    }
    return $uris;
  }


  function writeBodyContent()
  {
    $this->addElement('p', null, "Thank you for providing test result data for the " .
                                 $this->mTestSuite->getTitle());

    $args['u'] = $this->mUserAgent->getId();
    $homeURI = $this->buildURI('./', $args);

    $args['s'] = $this->mTestSuite->getName();
    $reviewURI = $this->buildURI(REVIEW_PAGE_URI, $args);
    $enterURI = $this->buildURI(TESTSUITE_PAGE_URI, $args);

    $this->openElement('p', null, FALSE);
    $this->addTextContent("You can ");
    $this->addHyperLink($enterURI, null, "enter additional data");
    $this->addTextContent(", ");
    $this->addHyperLink($reviewURI, null, "review results");
    $this->addTextContent(", or access other test suites from the ");
    $this->addHyperLink($homeURI, null, "harness welcome page");
    $this->addTextContent(".");
    $this->closeElement('p');
  }
}

?>