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
 * A class for gererating the welcome page of the test harness
 */
class WelcomePage extends HarnessPage
{  
  protected $mTestSuites;


  static function GetPageKey()
  {
    return 'home';
  }

  function _initPage()
  {
    parent::_initPage();

    $this->mTestSuites = new TestSuites();
  }
  
  
  /**
   * Generate <style> element
   */
  function writeHeadStyle()
  {
    parent::writeHeadStyle();
    
    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.welcome'));
  }


  function _writeTestSuites($showUnlocked = TRUE)
  {
    $testSuites = $this->mTestSuites->getTestSuites();
    
    if ($testSuites) {
      $this->openElement('table', array('class' => 'list'));
      
      if ($showUnlocked) {
        $this->addElement('caption', null,
                          'You can run tests or review the testing results for the following test suites:');
      }
      else {
        $this->addElement('caption', null,
                          'You can review the testing results for the following locked test suites:');
      }

      $this->openElement('thead');
      $this->openElement('tr');
      $this->addElement('th', null, 'Test Suite');
      $this->addElement('th', null, 'Test Count');
      if ($showUnlocked) {
        $this->addElement('th', null, '');
      }
      $this->addElement('th', null, '');
      $this->closeElement('tr');
      $this->closeElement('thead');

      $this->openElement('tbody');
      
      foreach ($testSuites as $testSuite) {
        if (($testSuite->getLockDateTime() && (! $showUnlocked)) ||
            ((! $testSuite->getLockDateTime()) && $showUnlocked)) {
          $this->openElement('tr');
          
          $this->openElement('td', array('title' => $testSuite->getDescription()));
          $this->addHyperLink($testSuite->getURI(), null, $testSuite->getTitle());
          $this->closeElement('td');
          $this->addElement('td', null, $this->mTestSuites->getTestCount($testSuite));

          $args['suite'] = $testSuite->getName();
          $args['ua'] = $this->mUserAgent->getId();

          if (! $testSuite->getLockDateTime()) {
            $this->openElement('td');
            $this->addHyperLink($this->buildPageURI('testsuite', $args), null, 'Run Tests');
            $this->closeElement('td');
          }
          $this->openElement('td');
          $this->addHyperLink($this->buildPageURI('review', $args), null, 'Review Results');
          $this->closeElement('td');
          
          $this->closeElement('tr');
        }
      }
      
      $this->closeElement('tbody');
      $this->closeElement('table');
    }
  }
  
  
  function writeTestSuites()
  {
    if (0 < $this->mTestSuites->getCount()) {

      if (0 < ($this->mTestSuites->getCount() - $this->mTestSuites->getLockedCount())) {
        $this->_writeTestSuites(TRUE);
      }
      if (0 < $this->mTestSuites->getLockedCount()) {
        $this->_writeTestSuites(FALSE);
      }
    }
    else {
      $this->addElement('p', null, "** No Test Suites Defined. **");
    }
  }


  function writeBodyContent()
  {
    $this->openElement('div', array('class' => 'body'));

    $this->writeTestSuites();

    $this->closeElement('div');
  }
  
}

?>