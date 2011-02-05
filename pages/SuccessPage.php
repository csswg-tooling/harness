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


  function writeBodyContent($indent = '')
  {
    echo $indent . "<p>\n";
    echo $indent . "  Thank you for providing test result data for the\n";
    echo $indent . "  " . self::Encode($this->mTestSuite->getTitle()) . "\n";
    echo $indent . "</p>\n";

    $args['u'] = $this->mUserAgent->getId();
    $homeURI = $this->encodeURI('./', $args);

    $args['s'] = $this->mTestSuite->getName();
    $reviewURI = $this->encodeURI(REVIEW_PAGE_URI, $args);
    $enterURI = $this->encodeURI(TESTSUITE_PAGE_URI, $args);

    echo $indent . "<p>\n";
    echo $indent . "  You can <a href='{$enterURI}'>enter additional data</a>, \n";
    echo $indent . "  <a href='{$reviewURI}'>review results</a>, or \n";
    echo $indent . "  access other test suites from the\n";
    echo $indent . "  <a href='{$homeURI}'>harness welcome page</a>.\n";
    echo $indent . "</p>\n";

  }
}

?>