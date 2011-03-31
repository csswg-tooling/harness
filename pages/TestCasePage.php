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
require_once("lib/TestSuite.php");
require_once("lib/TestCase.php");
require_once("lib/UserAgent.php");
require_once("lib/Format.php");

/**
 * A class for generating the page that presents a test
 * case and the UI to submit results
 */
class TestCasePage extends HarnessPage
{  
  protected $mTestCase;
  protected $mIndex;
  protected $mCount;
  protected $mFormat;
  protected $mDesiredFormat;
  protected $mRefName;


  /**
   * Expected URL paramaters:
   * 's' Test Suite Name
   * 'c' Test Case Name (optional)
   * 'g' Spec Section Id (optional)
   * 'r' Index of test case
   * 'o' Test ordering
   * 'f' Format of test
   * 'ref' Name of reference
   * 'm' Modified date (only results before date)
   */
  function __construct(Array $args = null) 
  {
    parent::__construct($args);

    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      trigger_error($msg, E_USER_WARNING);
    }

    $testCaseName = $this->_getData('c');
    $specLinkId = intval($this->_getData('g'));

    $this->mIndex = intval($this->_getData('r'));
    $order = intval($this->_getData('o'));
    
    $format = $this->_getData('f');
    
    $this->mTestCase = new TestCase();
    $this->mTestCase->load($this->mTestSuite, $testCaseName, $specLinkId,
                           $this->mUserAgent, $order, $this->mIndex);
                           
    if (! $this->mTestCase->isValid()) {
      $msg = 'No test case identified.';
      trigger_error($msg, E_USER_WARNING);
    }
                   
    $suiteFormats = $this->mTestSuite->getFormats();
    $testFormats = $this->mTestCase->getFormats();
    
    if (in_array($format, $suiteFormats)) {
      $this->mDesiredFormat = $format;
      
      if (in_array($format, $testFormats)) {
        $this->mFormat = $format;
      }
      else {
        $this->mFormat = $testFormats[0];
      }
    }
    else {
      $this->mFormat = $testFormats[0];
    }
    
    if ($testCaseName) {
      $this->mCount = 1;
    }
    elseif ($specLinkId) {
      $this->mCount = $this->mTestCase->countCasesInSection($specLinkId);
    }
    else {
      $this->mCount = $this->mTestCase->countCasesInSuite();
    }

    $this->mSubmitData = $this->mGetData;
    $this->mSubmitData['c'] = $this->mTestCase->getTestCaseName();
    $this->mSubmitData['cid'] = $this->mTestCase->getId();
    $this->mSubmitData['f'] = $this->mFormat;
    if ($this->mDesiredFormat) {
      $this->mSubmitData['df'] = $this->mDesiredFormat;
    }
    if ($this->mIndex < ($this->mCount - 1)) {
      $this->mSubmitData['next'] = ($this->mIndex + 1);
    }
    else {
      $this->mSubmitData['next'] = 0;
    }
    if (isset($this->mSubmitData['ref'])) {
      unset($this->mSubmitData['ref']);
    }

    $this->mRefName = $this->_getData('ref');
    if (FALSE === $this->mTestCase->getReferenceURI($this->mRefName, $this->mFormat)) {
      $this->mRefName = null;
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
    
    $this->addStyleSheetLink('test.css');

    if ($this->mUserAgent) {
      $actualUA = $this->mUserAgent->getActualUA();
      $actualEngine = strtolower($actualUA->getEngine());
      
      $this->addStyleSheetLink("test_{$actualEngine}.css");
    }
  }


  function writeTestTitle($elementName = 'h2', $class = 'title', $attrs = null)
  {
    $title = $this->mTestCase->getTitle();
    $assertion = $this->mTestCase->getAssertion();
    $specURIs = $this->mTestCase->getSpecURIs();
    
    if ((1 < $this->mCount) || $title || $assertion || $specURIs) {
      $attrs['class'] = $class;
      $this->openElement($elementName, $attrs);
      
      if (1 < $this->mCount) {
        $index = $this->mIndex + 1;
        $this->addTextContent("Test {$index} of {$this->mCount}" . ($title ? ':' : ''));
      }
      if ($title) {
        if ($assertion) {
          $this->addAbbrElement($assertion, null, $title);
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
            $section = self::Encode($specTitle) . ' &sect; ' . self::Encode($section);
          }
          else {
            $section = self::Encode($specTitle);
          }
          if ($title) {
            $this->addAbbrElement($title, null, $section, FALSE);
          }
          else {
            $this->addTextContent($section);
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
    $this->addHyperLink($this->mTestCase->getURI($this->mFormat), 
                        array('target' => 'test_case'), 
                        $this->mTestCase->getTestCaseName());
    
    if ($this->mTestCase->isReferenceTest()) {
      $refTests = $this->mTestCase->getReferences($this->mFormat);
      if ($refTests) {
        foreach ($refTests as $refTest) {
          $this->addTextContent(" {$refTest['type']} ");
          $this->addHyperLink($this->mTestCase->getReferenceURI($refTest['reference'], $this->mFormat), 
                              array('target' => 'reference'), 
                              $refTest['reference']);
        }
      }
    }
    
    $args['s'] = $this->mTestSuite->getName();
    $args['c'] = $this->mTestCase->getTestCaseName();
    $args['u'] = $this->mUserAgent->getId();
    $detailsURI = $this->buildURI(DETAILS_PAGE_URI, $args);

    $this->openElement('span', null, FALSE);
    $this->addTextContent(' (');
    $this->addHyperLink($detailsURI, null, 'Results');
    $this->addTextContent(')');
    $this->closeElement('span');
    
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
      $refTests = $this->mTestCase->getReferences($this->mFormat);
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
          $uri = $this->buildURI(TESTCASE_PAGE_URI, $args);
          
          $this->openElement('span', array('class' => 'tab'));
          $this->addHyperLink($uri, null, 'Test Case');
          $this->closeElement('span');
        }
        foreach ($refTests as $refTest) {
          $refName = $refTest['reference'];
          $refType = $refTest['type'];
          if ($refName == $this->mRefName) {
            $this->openElement('span', array('class' => 'tab active'));
            $this->addElement('a', null, "{$refType} Reference Page");
            $this->closeElement('span');
          }
          else {
            $args = $this->mGetData;
            $args['ref'] = $refName;
            $uri = $this->buildURI(TESTCASE_PAGE_URI, $args);
            
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
          $not = (('!=' == $this->mTestCase->getReferenceType($this->mRefName, $this->mFormat)) ? 'NOT ' : '');
          $this->openElement('p', array('class' => 'instruct'));
          $this->addTextContent("This page must {$not}match the Test Case");
        }
      }

      if (1 < count($suiteFormats)) {
        $this->openElement('span', array('class' => 'tabgroup format'));

        $testFormats = $this->mTestCase->getFormats();

        foreach ($suiteFormats as $formatName => $format) {
          $formatTitle = $format->getTitle();
          
          if ($formatName == $this->mFormat) {
            $class = 'tab active';
            if ($this->mDesiredFormat && ($formatName != $this->mDesiredFormat)) {
              $class .= ' other';
            }
            $this->openElement('span', array('class' => $class));
            $this->addElement('a', null, $formatTitle);
            $this->closeElement('span');
          }
          else {
            if (in_array($formatName, $testFormats)) {
              $args = $this->mGetData;
              $args['f'] = $formatName;
              if ($this->mRefName) {
                $args['ref'] = $this->mRefName; 
              }
              $uri = $this->buildURI(TESTCASE_PAGE_URI, $args);

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
  

  function writeTest()
  {
    $this->openElement('div', array('class' => 'test'));
    $this->openElement('p');
    $refURI = $this->mTestCase->getReferenceURI($this->mRefName, $this->mFormat);
    if ($refURI) {
      $this->openElement('object', array('data' => $refURI, 'type' => 'text/html'));
      $this->addHyperLink($refURI, array('target' => 'reference'), "Show reference");
      $this->closeElement('object');
    }
    else {
      $uri = $this->mTestCase->getURI($this->mFormat);
      $this->openElement('object', array('data' => $uri, 'type' => 'text/html'));
      $this->addHyperLink($uri, array('target' => 'test_case'), "Run test");
      $this->closeElement('object');
    }
    $this->closeElement('p');
    $this->closeElement('div');
  }
  
  
  function writeSubmitForm()
  {
    $this->openFormElement(SUBMIT_PAGE_URI, 'post', 'eval');
    $this->openElement('p', array('class' => 'buttons'));
    $this->writeHiddenFormControls();
    
    $locked = $this->mTestSuite->isLocked();
    $this->addInputElement('submit', 'result', 'Pass [1]', array('accesskey' => '1', 'disabled' => $locked));
    $this->addInputElement('submit', 'result', 'Fail [2]', array('accesskey' => '2', 'disabled' => $locked));
    $this->addInputElement('submit', 'result', 'Cannot tell [3]', array('accesskey' => '3', 'disabled' => $locked));
    $this->addInputElement('submit', 'result', 'Skip [4]', array('accesskey' => '4'));
    
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
        $uri = $this->buildURI(TESTCASE_PAGE_URI, $args);
        $this->openElement('span', null, FALSE);
        $this->addTextContent('(');
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
    
    $this->mSpiderTrap->addTrapLinkTo($this);

    $this->writeSmallW3CLogo();
    
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

    $this->mSpiderTrap->addTrapLinkTo($this);

    $this->closeElement('div');
  }
}

?>