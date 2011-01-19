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

/**
 * A class for generating the page that presents a test
 * case and the UI to submit results
 */
class TestCasePage extends HarnessPage
{  
  protected $mTestCase;
  protected $mSubmitData;
  protected $mRefId;
  protected $mIndex;
  protected $mCount;


  /**
   * Expected URL paramaters:
   * 's' Test Suite Name
   * 'c' Test Case Name (optional)
   * 'g' Test Group Name (optional)
   * 'r' Index of test case
   * 'o' Test ordering
   * 'm' Modified date (only results before date)
   */
  function __construct() 
  {
    parent::__construct();

    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      $this->triggerClientError($msg, E_USER_ERROR);
    }

    $testCaseName = $this->_getData('c');
    $testGroupName = $this->_getData('g');

    $this->mIndex = intval($this->_getData('r'));
    $order = intval($this->_getData('o'));
    $modified = $this->_getData('m');

    $this->mTestCase = new TestCase();
    $this->mTestCase->load($this->mTestSuite->getName(), $testCaseName, $testGroupName,
                           $this->mUserAgent, $modified, $order, $this->mIndex);
                           
    if (! $this->mTestCase->isValid()) {
      $msg = 'No test case identified.';
      $this->triggerClientError($msg, E_USER_ERROR);
    }
                   
    if ($testCaseName) {
      $this->mCount = 1;
    }
    elseif ($testGroupName) {
      $this->mCount = $this->mTestCase->countCasesInGroup($this->mTestSuite->getName(), $testGroupName);
    }
    else {
      $this->mCount = $this->mTestCase->countCasesInSuite($this->mTestSuite->getName());
    }

    $this->mSubmitData = $this->mGetData;
    $this->mSubmitData['c'] = $this->mTestCase->getTestCaseName();
    $this->mSubmitData['cid'] = $this->mTestCase->getId();
    if ($this->mIndex < ($this->mCount - 1)) {
      $this->mSubmitData['next'] = ($this->mIndex + 1);
    }
    else {
      $this->mSubmitData['next'] = 0;
    }
    if (isset($this->mSubmitData['ref'])) {
      unset($this->mSubmitData['ref']);
    }

    $this->mRefId = intval($this->_getData('ref'));
    if (FALSE === $this->mTestCase->getReferenceURI($this->mRefId)) {
      $this->mRefId = 0;
    }
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
      
      $title = "Test Case";
      $uri = '';
      $uris[] = compact('title', 'uri');
    }
    return $uris;
  }

  
  /**
   * Generate <style> element
   */
  function writeHeadStyle($indent = '')
  {
    parent::writeHeadStyle($indent);
    
    echo $indent . "<link rel='stylesheet' href='test.css' type='text/css'>\n";
    
    $actualUA = $this->mUserAgent->getActualUA();
    $actualEngine = strtolower($actualUA->getEngine());
    
    echo $indent . "<link rel='stylesheet' href='test_{$actualEngine}.css' type='text/css'>\n";
  }


  function writeTestTitle($indent = '', $element = "h2", $attrs = "class='title'")
  {
    $title = $this->mTestCase->getTitle();
    $assertion = $this->mTestCase->getAssertion();
    $specURIs = $this->mTestCase->getSpecURIs();
    
    if ((1 < $this->mCount) || $title || $assertion || $specURIs) {
      echo $indent . "<{$element}" . ($attrs ? " {$attrs}>\n" : ">\n");
      if (1 < $this->mCount) {
        $index = $this->mIndex + 1;
        echo $indent . "  Test {$index} of {$this->mCount}" . ($title ? ":\n" : "\n");
      }
      if ($title) {
        $title = Page::Encode($title);
        if ($assertion) {
          $title = "<abbr title='" . Page::Encode($assertion) . "'>{$title}</abbr>";
        }
        echo $indent . "  {$title}\n";
      }
      elseif ($assertion) {
        $assertion = Page::Encode($assertion);
        echo $indent . "  Assertion: {$assertion}\n";
      }
      if ($specURIs && (0 < count($specURIs))) {
        echo $indent . "  (";
        $index = -1;
        foreach ($specURIs as $specURI) {
          $index++;
          extract($specURI);
          if ($title) {
            $title = "<abbr title='" . Page::Encode($title) . "'>Spec</abbr>";
          }
          else {
            $title = "Spec";
          }
          if (0 < $index) {
            echo ",\n{$indent}  ";
          }
          echo "<a href='{$uri}' target='spec'>{$title}</a>";
        }
        echo ")\n";
      }
      echo $indent . "</{$element}>\n";
    }
  }
  
  
  function writeTestLinks($indent = '', $element = "h3", $attrs = "class='testname'")
  {
    echo $indent . "<{$element}" . ($attrs ? " {$attrs}>\n" : ">\n");
    
    $testName = Page::Encode($this->mTestCase->getTestCaseName());
    echo $indent . "  Test Case: <a href='{$this->mTestCase->getURI()}' target='test_case'>{$testName}</a>\n";
    
    if ($this->mTestCase->isReferenceTest()) {
      $refTests = $this->mTestCase->getReferences();
      if ($refTests) {
        foreach ($refTests as $refTest) {
          $refId    = $refTest['id'];
          $refName  = Page::Encode($refTest['reference']);
          $refType  = Page::Encode($this->mTestCase->getReferenceType($refId));
          $refURI   = $this->mTestCase->getReferenceURI($refId);
          echo $indent . "  {$refType} <a href='{$refURI}' target='reference'>{$refName}</a>\n";
        }
      }
    }
    
    echo $indent . "</{$element}>\n";
  }
  
  function writeTestFlags($indent = '', $element = "p", $attrs = "class='notes'")
  {
    $flagDescriptions = $this->mTestCase->getFlags()->getDescriptions();
    if ($flagDescriptions && (0 < count($flagDescriptions))) {
      echo $indent . "<{$element}" . ($attrs ? " {$attrs}>\n" : ">\n");
      foreach ($flagDescriptions as $flag => $description) {
        echo $indent . "  <span>{$description}</span>\n";
      }
      echo $indent . "</{$element}>\n";
    }
  }
  
  
  function writeFlagTests($indent = '', $element = "div", $attrs = "class='prerequisites'")
  {
    $flags = $this->mTestCase->getFlags();
    
    if ($flags) {
      $tests = $flags->getTests();
      if ($tests && (0 < count($tests))) {
        echo $indent . "<{$element}" . ($attrs ? " {$attrs}>\n" : ">\n");
        foreach ($tests as $flag => $test) {
          echo $indent . "  {$test}\n";
        }
        echo $indent . "</{$element}>\n";
      }
    }
  }
  
  
  function writeReferenceTestTabs($indent = '')
  {
    if ($this->mTestCase->isReferenceTest()) {
      $refTests = $this->mTestCase->getReferences();
      
      if ($refTests && count($refTests)) {
        echo $indent . "<div class='tabbar'>\n";
        echo $indent . "  <ul>\n";
        if (0 == $this->mRefId) {
          echo $indent . "    <li class='reftab active'><a>Test Case</a></li>\n";
        }
        else {
          $args = $this->mGetData;
          unset($args['ref']);
          $uri = Page::EncodeURI(TESTCASE_PAGE_URI, $args);
          echo $indent . "    <li class='reftab'><a href='{$uri}'>Test Case</a></li>\n";
        }
        foreach ($refTests as $refTest) {
          $refId = $refTest['id'];
          $refType = Page::Encode($refTest['type']);
          if ($refId == $this->mRefId) {
            echo $indent . "    <li class='reftab active'><a>{$refType} Reference Page</a></li>\n";
          }
          else {
            $args = $this->mGetData;
            $args['ref'] = $refId;
            $uri = Page::EncodeURI(TESTCASE_PAGE_URI, $args);
            echo $indent . "    <li class='reftab'><a href='{$uri}'>";
            echo               "{$refType} Reference Page";
            echo               "</a></li>\n";
          }
        }
        echo $indent . "  </ul>\n";

        if (0 == $this->mRefId) {
          $plural = ((1 < count($refTests)) ? 's' : '');
          echo $indent . "  <p class='instruct'>This page must be compared to the Reference Page{$plural}</p>\n";
        }
        else {
          $not = (('!=' == $this->mTestCase->getReferenceType($this->mRefId)) ? 'NOT ' : '');
          echo $indent . "  <p class='instruct'>This page must {$not}match the Test Case</p>\n";
        }
        echo $indent . "</div>\n";
      }
    }
  }
  

  function writeTest($indent = '')
  {
    echo $indent . "<div class='test'>\n";
    echo $indent . "  <p>\n";
    $refURI = $this->mTestCase->getReferenceURI($this->mRefId);
    if ($refURI) {
      echo $indent . "    <object data='{$refURI}' type='text/html'>\n";
      echo $indent . "      <a href='{$refURI}' target='reference'>\n";
      echo $indent . "        Show reference\n";
      echo $indent . "      </a>\n";
      echo $indent . "    </object>\n";
    }
    else {
      $uri = $this->mTestCase->getURI();
      echo $indent . "    <object data='{$uri}' type='text/html'>\n";
      echo $indent . "      <a href='{$uri}' target='test_case'>\n";
      echo $indent . "        Run test\n";
      echo $indent . "      </a>\n";
      echo $indent . "    </object>\n";
    }
    echo $indent . "  </p>\n";
    echo $indent . "</div>\n";
  }
  
  
  function writeSubmitForm($indent = '')
  {
    echo $indent . "<form name='eval' action='" . SUBMIT_PAGE_URI . "' method='post'>\n";
    echo $indent . "  <p class='buttons'>\n";
    foreach($this->mSubmitData as $opt => $value) {
      $opt = Page::Encode($opt);
      $value = Page::Encode($value);
      echo $indent . "    <input type='hidden' name='{$opt}' value='{$value}'>\n";
    }
    echo $indent . "    <input type='submit' name='result' value='Pass [1]' accesskey='1'>\n";
    echo $indent . "    <input type='submit' name='result' value='Fail [2]' accesskey='2'>\n";
    echo $indent . "    <input type='submit' name='result' value='Cannot tell [3]' accesskey='3'>\n";
    echo $indent . "    <input type='submit' name='result' value='Skip [4]' accesskey='4'>\n";
    echo $indent . "  </p>\n";
    echo $indent . "</form>\n";
  }
  
  
  function writeUserAgent($indent = '')
  {
    if ($this->mUserAgent) {
      $uaString = Page::Encode($this->mUserAgent->getUAString());
      $description = Page::Encode($this->mUserAgent->getDescription());
    
      echo $indent . "<p class='ua'>\n";
      echo $indent . "  Testing\n";
      echo $indent . "  <abbr title='{$uaString}'>\n";
      echo $indent . "    {$description}\n";
      echo $indent . "  </abbr>\n";
      echo $indent . "</p>\n";
    }
  }
  

  function writeBodyHeader($indent = '')
  {
    echo $indent . "<div class='header'>\n";
    
    $this->mSpiderTrap->writeTrapLink($indent . '  ');

    $this->writeSmallW3CLogo($indent . '  ');
    
    $this->writeNavLinks($indent . '  ');
    
    $this->writeContentTitle($indent . '  ', "h1", "class='suite'");

    $this->writeTestTitle($indent . '  ');

    $this->writeTestLinks($indent . '  ');

    $this->writeTestFlags($indent . '  ');
    
    $this->writeFlagTests($indent . '  ');

    echo $indent . "</div>\n";
  }


  function writeBodyContent($indent = '')
  {
    $this->writeReferenceTestTabs($indent);

    $this->writeTest($indent);
  }


  function writeBodyFooter($indent = '')
  {
    echo $indent . "<div class='footer'>\n";

    $this->writeSubmitForm($indent . '  ');
    
    $this->writeUserAgent($indent . '  ');

    $this->mSpiderTrap->writeTrapLink($indent . '  ');

    echo $indent . "</div>\n";
  }
}

?>