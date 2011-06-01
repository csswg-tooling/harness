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
require_once('lib/TestSuite.php');
require_once('lib/TestCase.php');
require_once('lib/UserAgent.php');
require_once('lib/Format.php');
require_once('lib/Results.php');
require_once('lib/Engine.php');
require_once('lib/Sections.php');

/**
 * A class for generating the page that presents a test
 * case and the UI to submit results
 */
class TestCasePage extends HarnessPage
{  
  protected $mTestCase;
  protected $mIndex;
  protected $mCount;
  protected $mFormatName;
  protected $mDesiredFormatName;
  protected $mRefName;
  protected $mSectionName;
  protected $mHasResults;


  /**
   * Expected URL paramaters:
   * 's' Test Suite Name
   *
   * Optional URL paramaters
   * 'c' Test Case Name - test only this test
   * 'g' Spec Section Id
   * 'sec' Spec Section Name
   * 'r' Index of test case
   * 'i' Test Case Name - find this test in the group
   * 'o' Test ordering - 0 = alphabetical, 1 = sequenced
   * 'f' Desired format of test
   * 'fl' Flag - only display tests with this flag
   * 'ref' Name of reference
   */
  function __construct(Array $args = null) 
  {
    parent::__construct($args);

    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      trigger_error($msg, E_USER_WARNING);
    }

    $testCaseName = $this->_getData('c');
    $sectionId = intval($this->_getData('g'));
    $this->mSectionName = $this->_getData('sec');
    if ((0 == $sectionId) && ($this->mSectionName)) {
      $sectionId = Sections::GetSectionIdFor($this->mTestSuite, $this->mSectionName);
    }
    if ($sectionId) {
      $this->mSectionName = Sections::GetSectionNameFor($this->mTestSuite, $sectionId);
    }
    
    $order = intval($this->_getData('o'));
    
    $this->mCount = -1;
    if ($testCaseName) {
      $this->mCount = 0;
    }
    else {
      $testCaseName = $this->_getData('i');
    }
    $this->mIndex = intval($this->_getData('r'));
    
    $formatName = $this->_getData('f');
    $flag = $this->_getData('fl');
    
    $this->mTestCase = new TestCase();
    $this->mTestCase->load($this->mTestSuite, $testCaseName, $sectionId,
                           $this->mUserAgent, $order, $this->mIndex, $flag);
                           
    if (! $this->mTestCase->isValid()) {
      $msg = 'No test case identified.';
      trigger_error($msg, E_USER_WARNING);
    }
    
    if (0 == $this->mIndex) {
      $this->mIndex = $this->mTestCase->getIndex($sectionId, $this->mUserAgent, $order, $flag);
      if (FALSE === $this->mIndex) {  // given a testcase and a section, but test isn't in that section
        $this->mIndex = -1;
      }
    }
                   
    $suiteFormatNames = $this->mTestSuite->getFormatNames();
    $testFormatNames = $this->mTestCase->getFormatNames();
    
    if (Format::FormatNameInArray($formatName, $suiteFormatNames)) {
      $this->mDesiredFormatName = $formatName;
      
      if (Format::FormatNameInArray($formatName, $testFormatNames)) {
        $this->mFormatName = $formatName;
      }
      else {
        $this->mFormatName = $testFormatNames[0];
      }
    }
    else {
      $this->mFormatName = $testFormatNames[0];
    }
    
    if (-1 == $this->mCount) {
      if ($sectionId) {
        $this->mCount = $this->mTestCase->countCasesInSection($sectionId, $flag);
      }
      else {
        $this->mCount = $this->mTestCase->countCasesInSuite($flag);
      }
    }

    $this->mSubmitData = $this->mGetData;
    $this->mSubmitData['c'] = $this->mTestCase->getTestCaseName();
    $this->mSubmitData['g'] = $sectionId;
    $this->mSubmitData['sec'] = $this->mSectionName;
    $this->mSubmitData['cid'] = $this->mTestCase->getId();
    $this->mSubmitData['f'] = $this->mFormatName;
    if ($this->mDesiredFormatName) {
      $this->mSubmitData['df'] = $this->mDesiredFormatName;
    }
    if ($this->mIndex < ($this->mCount - 1)) {
      $this->mSubmitData['next'] = ($this->mIndex + 1);
    }
    else {
      $this->mSubmitData['next'] = -1;
    }
    if (isset($this->mSubmitData['ref'])) {
      unset($this->mSubmitData['ref']);
    }

    $this->mRefName = $this->_getData('ref');
    if (FALSE === $this->mTestCase->getReferenceURI($this->mRefName, $this->mFormatName)) {
      $this->mRefName = null;
    }
    
    $this->mHasResults = FALSE;
  }
  
  
  function getNavURIs()
  {
    $uris = parent::getNavURIs();
    
    if ($this->mTestSuite) {
      $title = "Enter Data";
      $args['s'] = $this->mTestSuite->getName();
      $args['u'] = $this->mUserAgent->getId();

      $uri = $this->buildConfigURI('page.testsuite', $args);
      $uris[] = compact('title', 'uri');
      
      $title = "Test Case";
      $uri = '';
      $uris[] = compact('title', 'uri');
    }
    return $uris;
  }

  
  /**
   * Generate <style> element
   */
  function writeHeadStyle()
  {
    parent::writeHeadStyle();
    
    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.test'));

    if ($this->mUserAgent) {
      $actualUA = $this->mUserAgent->getActualUA();
      $actualEngineName = strtolower($actualUA->getEngineName());
      
      $this->addStyleSheetLink($this->buildURI(sprintf(Config::Get('uri.stylesheet.test_engine'), $actualEngineName)));
    }
  }


  function getPageTitle()
  {
    $title = parent::getPageTitle();
    if ($this->mSectionName) {
      return "{$title} - Section {$this->mSectionName}";
    }
    return $title;
  }


  function writeTestTitle($elementName = 'h2', $class = 'title', $attrs = null)
  {
    $title = $this->mTestCase->getTitle();
    $assertion = $this->mTestCase->getAssertion();
    $specURIs = $this->mTestCase->getSpecURIs();
    
    if ((0 < $this->mCount) || $title || $assertion || $specURIs) {
      $attrs['class'] = $class;
      $this->openElement($elementName, $attrs);
      
      if (0 < $this->mCount) {
        $index = $this->mIndex + 1;
        $this->addTextContent("Test {$index} of {$this->mCount}" . ($title ? ': ' : ''));
      }
      if ($title) {
        if ($assertion) {
          $this->addElement('span', array('title' => $assertion), $title);
        }
        else {
          $this->addTextContent($title);
        }
      }
      elseif ($assertion) {
        $this->addTextContent("Assertion: {$assertion}");
      }
      if ($specURIs && (0 < count($specURIs))) {
        $this->openElement('span', array('class' => 'speclink'), FALSE);
        $this->addTextContent(' (');
        $index = -1;
        foreach ($specURIs as $specURI) {
          $index++;
          extract($specURI);  // $specTitle, $section, $title, $uri

          if (0 < $index) {  
            $this->addTextContent(', ');
          }
          $this->openElement('a', array('href' => $uri, 'target' => 'spec'));
          
          if (! $specTitle) {
            $specTitle = "Spec";
          }
          if ($section) {
            $section = $this->encode($specTitle) . ' &sect; ' . $this->encode($section);
          }
          else {
            $section = $this->encode($specTitle);
          }
          if ($title) {
            $this->addElement('span', array('title' => $title), $section, FALSE);
          }
          else {
            $this->addTextContent($section, FALSE);
          }
          $this->closeElement('a');
        }
        $this->addTextContent(')');
        $this->closeElement('span');
      }
      $this->closeElement($elementName);
    }
  }
  
  
  function writeTestLinks($elementName = 'h3', $class = 'testname', $attrs = null)
  {
    $attrs['class'] = $class;
    $this->openElement($elementName, $attrs);
    
    $this->addTextContent("Test Case: ");
    $this->addHyperLink($this->mTestCase->getURI($this->mFormatName), 
                        array('target' => 'test_case'), 
                        $this->mTestCase->getTestCaseName());
    
    if ($this->mTestCase->isReferenceTest()) {
      $refTests = $this->mTestCase->getReferences($this->mFormatName);
      if ($refTests) {
        foreach ($refTests as $refTest) {
          $this->addTextContent(" {$refTest['type']} ");
          $this->addHyperLink($this->mTestCase->getReferenceURI($refTest['reference'], $this->mFormatName), 
                              array('target' => 'reference'), 
                              $refTest['reference']);
        }
      }
    }
    
    if ($this->mHasResults) {
      $args['s'] = $this->mTestSuite->getName();
      $args['c'] = $this->mTestCase->getTestCaseName();
      $args['u'] = $this->mUserAgent->getId();
      $detailsURI = $this->buildConfigURI('page.details', $args);

      $this->openElement('span', null, FALSE);
      $this->addTextContent(' (');
      $this->addHyperLink($detailsURI, null, 'Results');
      $this->addTextContent(')');
      $this->closeElement('span');
    }
    
    $this->closeElement($elementName);
  }
  
  
  function writeTestFlags($elementName = 'p', $class = 'notes', $attrs = null)
  {
    $flagDescriptions = $this->mTestCase->getFlags()->getDescriptions();
    if ($flagDescriptions && (0 < count($flagDescriptions))) {
      $attrs['class'] = $class;
      $this->openElement($elementName, $attrs);
      foreach ($flagDescriptions as $flag => $description) {
        $this->addElement('span', null, $description, FALSE);
      }
      $this->closeElement($elementName);
    }
  }
  
  
  function writeFlagTests($elementName = 'div', $class = 'prerequisites', $attrs = null)
  {
    $flags = $this->mTestCase->getFlags();
    
    if ($flags) {
      $tests = $flags->getTests();
      if ($tests && (0 < count($tests))) {
        $attrs['class'] = $class;
        $this->openElement($elementName, $attrs);
        foreach ($tests as $flag => $test) {
          $this->addTextContent($test, FALSE);
        }
        $this->closeElement($elementName);
      }
    }
  }
  
  
  function writeReferenceAndFormatTabs()
  {
    $suiteFormats = Format::GetFormatsFor($this->mTestSuite);

    if ($this->mTestCase->isReferenceTest()) {
      $refTests = $this->mTestCase->getReferences($this->mFormatName);
      if ((FALSE === $refTests) || (0 == count($refTests))) {
        unset($refTests);
      }
    }
    
    if (isset($refTests) || (1 < count($suiteFormats))) {
      if (isset($refTests)) {
        $attrs['class'] = 'tabbar ref';
      }
      else {
        $attrs['class'] = 'tabbar';
      }
      $this->openElement('div', $attrs);
      
      if (isset($refTests)) {
        $this->openElement('span', array('class' => 'tabgroup references'));
        if (! $this->mRefName) {
          $this->openElement('span', array('class' => 'tab active'));
          $this->addElement('a', null, 'Test Case');
          $this->closeElement('span');
        }
        else {
          $args = $this->mGetData;
          unset($args['ref']);
          $uri = $this->buildConfigURI('page.testcase', $args);
          
          $this->openElement('span', array('class' => 'tab'));
          $this->addHyperLink($uri, null, 'Test Case');
          $this->closeElement('span');
        }
        foreach ($refTests as $refTest) {
          $refName = $refTest['reference'];
          $refType = $refTest['type'];
          if (0 == strcasecmp($refName, $this->mRefName)) {
            $this->openElement('span', array('class' => 'tab active'));
            $this->addElement('a', null, "{$refType} Reference Page");
            $this->closeElement('span');
          }
          else {
            $args = $this->mGetData;
            $args['ref'] = $refName;
            $uri = $this->buildConfigURI('page.testcase', $args);
            
            $this->openElement('span', array('class' => 'tab'));
            $this->addHyperLink($uri, null, "{$refType} Reference Page");
            $this->closeElement('span');
          }
        }
        $this->closeElement('span');
      }
      
      if (isset($refTests)) {
        if (! $this->mRefName) {
          $plural = ((1 < count($refTests)) ? 's' : '');
          $this->openElement('p', array('class' => 'instruct'));
          $this->addTextContent("This page must be compared to the Reference Page{$plural}");
        }
        else {
          $not = (('!=' == $this->mTestCase->getReferenceType($this->mRefName, $this->mFormatName)) ? 'NOT ' : '');
          $this->openElement('p', array('class' => 'instruct'));
          $this->addTextContent("This page must {$not}match the Test Case");
        }
      }

      if (1 < count($suiteFormats)) {
        $this->openElement('span', array('class' => 'tabgroup format'));

        $testFormatNames = $this->mTestCase->getFormatNames();

        foreach ($suiteFormats as $formatName => $format) {
          $formatTitle = $format->getTitle();
          
          if (0 == strcasecmp($formatName, $this->mFormatName)) {
            $class = 'tab active';
            if ($this->mDesiredFormatName && (0 != strcasecmp($formatName, $this->mDesiredFormatName))) {
              $class .= ' other';
            }
            $this->openElement('span', array('class' => $class));
            $this->addElement('a', null, $formatTitle);
            $this->closeElement('span');
          }
          else {
            if (Format::FormatNameInArray($formatName, $testFormatNames)) {
              $args = $this->mGetData;
              $args['f'] = $formatName;
              if ($this->mRefName) {
                $args['ref'] = $this->mRefName; 
              }
              $uri = $this->buildConfigURI('page.testcase', $args);

              $this->openElement('span', array('class' => 'tab'));
              $this->addHyperLink($uri, null, $formatTitle);
              $this->closeElement('span');
            }
            else {
              $this->openElement('span', array('class' => 'tab disabled'));
              $this->addElement('a', null, $formatTitle);
              $this->closeElement('span');
            }
          }
        }
        $this->closeElement('span');
      }
      
      if (isset($refTests)) {
        $this->closeElement('p');
      }

      $this->closeElement('div');
    }
  }
  

  function writeResults()
  {
    $results = new Results($this->mTestSuite, $this->mTestCase->getTestCaseName());
    $engines = Engine::GetAllEngines();
    
    $engineNames = $results->getEngineNames();
    if (0 < count($engineNames)) {
      $this->mHasResults = TRUE;
      $counts = $results->getResultCountsFor($this->mTestCase->getId());
      
      $args['s'] = $this->mTestSuite->getName();
      $args['c'] = $this->mTestCase->getTestCaseName();
      $args['u'] = $this->mUserAgent->getId();

      $this->openElement('div', array('class' => 'results'), FALSE);
      foreach ($engineNames as $engineName) {
        $class = '';
        if (0 < $counts[$engineName]['uncertain']) {
          $class = 'uncertain';
        }
        if (0 < $counts[$engineName]['fail']) {
          $class .= ' fail';
        }
        if (0 < $counts[$engineName]['pass']) {
          $class .= ' pass';
        }
        if (0 < $counts[$engineName]['invalid']) {
          $class = 'invalid';
        }
        if (0 == strcasecmp($engineName, $this->mUserAgent->getEngineName())) {
          $class .= ' active';
        }
        $args['e'] = $engineName;
        $this->addHyperLink($this->buildConfigURI('page.details', $args), 
                            array('class' => $class), $engines[$engineName]->getTitle());
      }
      $this->closeElement('div');
    }
  }


  function writeTest()
  {
    $this->openElement('div', array('class' => 'test'));
    $this->openElement('p');
    $refURI = $this->mTestCase->getReferenceURI($this->mRefName, $this->mFormatName);
    if ($refURI) {
      $this->openElement('object', array('data' => $refURI, 'type' => 'text/html'));
      $this->addHyperLink($refURI, array('target' => 'reference'), "Show reference");
      $this->closeElement('object');
    }
    else {
      $uri = $this->mTestCase->getURI($this->mFormatName);
      $this->openElement('object', array('data' => $uri, 'type' => 'text/html'));
      $this->addHyperLink($uri, array('target' => 'test_case'), "Run test");
      $this->closeElement('object');
    }
    $this->closeElement('p');
    $this->closeElement('div');
  }
  
  
  function writeSubmitForm()
  {
    $this->openFormElement($this->buildConfigURI('page.submit'), 'post', 'eval');
    $this->openElement('p', array('class' => 'buttons'));
    $this->writeHiddenFormControls();
    
    $locked = $this->mTestSuite->isLocked();
    $this->addInputElement('submit', 'result', 'Pass [1]', null, array('accesskey' => '1', 'disabled' => $locked));
    $this->addTextContent(' ');
    $this->addInputElement('submit', 'result', 'Fail [2]', null, array('accesskey' => '2', 'disabled' => $locked));
    $this->addTextContent(' ');
    $this->addInputElement('submit', 'result', 'Cannot tell [3]', null, array('accesskey' => '3', 'disabled' => $locked));
    $this->addTextContent(' ');
    $this->addInputElement('submit', 'result', 'Skip [4]', null, array('accesskey' => '4'));
    
    $this->closeElement('p');
    $this->closeElement('form');
  }
  
  
  function writeUserAgent()
  {
    if ($this->mUserAgent) {
      $uaString = $this->mUserAgent->getUAString();
      $description = $this->mUserAgent->getDescription();

      $this->openElement('p', array('class' => 'ua'));
      
      if ($this->mUserAgent->isActualUA()) {
        $this->addTextContent("Testing: ");
        $this->addAbbrElement($uaString, null, $description);
      }
      else {
        $this->addTextContent("Entering results for: ");
        $this->addAbbrElement($uaString, array('class' => 'other'), $description);

        $args = $this->mGetData;
        unset($args['u']);
        $uri = $this->buildConfigURI('page.testcase', $args);
        $this->openElement('span', null, FALSE);
        $this->addTextContent(' (');
        $this->addHyperLink($uri, null, "Reset");
        $this->addTextContent(')');
        $this->closeElement('span');
      }
      $this->closeElement('p');
    }
  }
  

  function writeBodyHeader()
  {
    $this->openElement('div', array('class' => 'header'));
    
    $this->addSpiderTrap();

    $this->writeSmallW3CLogo();
    
    $this->writeResults();

    $this->writeNavLinks();
    
    $this->writeContentTitle('h1', array('class' => 'suite'));

    $this->writeTestTitle();

    $this->writeTestLinks();

    $this->writeTestFlags();
    
    $this->writeFlagTests();

    $this->closeElement('div');
  }


  function writeBodyContent()
  {
    $this->writeReferenceAndFormatTabs();

    $this->writeTest();
  }
  

  function writeBodyFooter()
  {
    $this->openElement('div', array('class' => 'footer'));

    $this->writeSubmitForm();
    
    $this->writeUserAgent();

    $this->addSpiderTrap();

    $this->closeElement('div');
  }
}

?>