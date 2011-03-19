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
  function __construct() 
  {
    parent::__construct();

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
  function writeHeadStyle($indent = '')
  {
    parent::writeHeadStyle($indent);
    
    echo $indent . "<link rel='stylesheet' href='test.css' type='text/css'>\n";

    if ($this->mUserAgent) {
      $actualUA = $this->mUserAgent->getActualUA();
      $actualEngine = strtolower($actualUA->getEngine());
      
      echo $indent . "<link rel='stylesheet' href='test_{$actualEngine}.css' type='text/css'>\n";
    }
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
        $title = self::Encode($title);
        if ($assertion) {
          $title = "<abbr title='" . self::Encode($assertion) . "'>{$title}</abbr>";
        }
        echo $indent . "  {$title}\n";
      }
      elseif ($assertion) {
        $assertion = self::Encode($assertion);
        echo $indent . "  Assertion: {$assertion}\n";
      }
      if ($specURIs && (0 < count($specURIs))) {
        echo $indent . "  <span class='speclink'>(";
        $index = -1;
        foreach ($specURIs as $specURI) {
          $index++;
          extract($specURI);
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
            $title = "<abbr title='" . self::Encode($title) . "'>{$section}</abbr>";
          }
          else {
            $title = $section;
          }
          if (0 < $index) {
            echo ",\n{$indent}  ";
          }
          echo "<a href='" . self::Encode($uri) . "' target='spec'>{$title}</a>";
        }
        echo ")</span>\n";
      }
      echo $indent . "</{$element}>\n";
    }
  }
  
  
  function writeTestLinks($indent = '', $element = "h3", $attrs = "class='testname'")
  {
    echo $indent . "<{$element}" . ($attrs ? " {$attrs}>\n" : ">\n");
    
    $testName = self::Encode($this->mTestCase->getTestCaseName());
    $testURI = self::Encode($this->mTestCase->getURI($this->mFormat));
    echo $indent . "  Test Case: <a href='{$testURI}' target='test_case'>{$testName}</a>\n";
    
    if ($this->mTestCase->isReferenceTest()) {
      $refTests = $this->mTestCase->getReferences($this->mFormat);
      if ($refTests) {
        foreach ($refTests as $refTest) {
          $refName  = self::Encode($refTest['reference']);
          $refType  = self::Encode($refTest['type']);
          $refURI   = self::Encode($this->mTestCase->getReferenceURI($refTest['reference'], $this->mFormat));

          echo $indent . "  {$refType} <a href='{$refURI}' target='reference'>{$refName}</a>\n";
        }
      }
    }
    
    $args['s'] = $this->mTestSuite->getName();
    $args['c'] = $this->mTestCase->getTestCaseName();
    $args['u'] = $this->mUserAgent->getId();
    if ($this->mTestSuite->isLocked()) {
      $args['m'] = $this->mTestSuite->getLockDateTime();
    }

    $detailsURI = $this->encodeURI(DETAILS_PAGE_URI, $args);
    echo $indent . "  <span class='resultlink'>(<a href='{$detailsURI}'>Results</a>)</span>\n";
    
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
  
  
  function writeReferenceAndFormatTabs($indent = '')
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
        echo $indent . "<div class='tabbar ref'>\n";
      }
      else {
        echo $indent . "<div class='tabbar'>\n";
      }
      
      if (isset($refTests)) {
        echo $indent . "  <span class='tabgroup references'>\n";
        if (! $this->mRefName) {
          echo $indent . "    <span class='tab active'><a>Test Case</a></span>\n";
        }
        else {
          $args = $this->mGetData;
          unset($args['ref']);
          $uri = $this->encodeURI(TESTCASE_PAGE_URI, $args);
          echo $indent . "    <span class='tab'><a href='{$uri}'>Test Case</a></span>\n";
        }
        foreach ($refTests as $refTest) {
          $refName = $refTest['reference'];
          $refType = self::Encode($refTest['type']);
          if ($refName == $this->mRefName) {
            echo $indent . "    <span class='tab active'><a>{$refType} Reference Page</a></span>\n";
          }
          else {
            $args = $this->mGetData;
            $args['ref'] = $refName;
            $uri = $this->encodeURI(TESTCASE_PAGE_URI, $args);
            echo $indent . "    <span class='tab'><a href='{$uri}'>";
            echo               "{$refType} Reference Page";
            echo               "</a></span>\n";
          }
        }
        echo $indent . "  </span>\n";
      }
      
      if (isset($refTests)) {
        if (! $this->mRefName) {
          $plural = ((1 < count($refTests)) ? 's' : '');
          echo $indent . "  <p class='instruct'>This page must be compared to the Reference Page{$plural}\n";
        }
        else {
          $not = (('!=' == $this->mTestCase->getReferenceType($this->mRefName, $this->mFormat)) ? 'NOT ' : '');
          echo $indent . "  <p class='instruct'>This page must {$not}match the Test Case\n";
        }
        $indent .= '  ';
      }

      if (1 < count($suiteFormats)) {
        echo $indent . "  <span class='tabgroup format'>\n";

        $testFormats = $this->mTestCase->getFormats();

        foreach ($suiteFormats as $formatName => $format) {
          $formatTitle = self::Encode($format->getTitle());
          
          if ($formatName == $this->mFormat) {
            $class = 'tab active';
            if ($this->mDesiredFormat && ($formatName != $this->mDesiredFormat)) {
              $class .= ' other';
            }
            echo $indent . "    <span class='{$class}'><a>{$formatTitle}</a></span>\n";
          }
          else {
            if (in_array($formatName, $testFormats)) {
              $args = $this->mGetData;
              $args['f'] = $formatName;
              if ($this->mRefName) {
                $args['ref'] = $this->mRefName; 
              }
              $uri = $this->encodeURI(TESTCASE_PAGE_URI, $args);

              echo $indent . "    <span class='tab'><a href='{$uri}'>";
              echo               "{$formatTitle}";
              echo               "</a></span>\n";
            }
            else {
              echo $indent . "    <span class='tab disabled'><a>{$formatTitle}</a></span>";
            }
          }
        }
        echo $indent . "  </span>\n";
      }
      
      if (isset($refTests)) {
        $indent = substr($indent, 0, -2);
        echo $indent . "  </p>\n";
      }

      echo $indent . "</div>\n";
    }
  }
  

  function writeTest($indent = '')
  {
    echo $indent . "<div class='test'>\n";
    echo $indent . "  <p>\n";
    $refURI = $this->mTestCase->getReferenceURI($this->mRefName, $this->mFormat);
    if ($refURI) {
      echo $indent . "    <object data='{$refURI}' type='text/html'>\n";
      echo $indent . "      <a href='{$refURI}' target='reference'>\n";
      echo $indent . "        Show reference\n";
      echo $indent . "      </a>\n";
      echo $indent . "    </object>\n";
    }
    else {
      $uri = $this->mTestCase->getURI($this->mFormat);
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
    $this->writeHiddenFormControls($indent . '    ');
    $locked = (($this->mTestSuite->isLocked()) ? " disabled" : '');
    echo $indent . "    <input type='submit' name='result' value='Pass [1]' accesskey='1'{$locked}>\n";
    echo $indent . "    <input type='submit' name='result' value='Fail [2]' accesskey='2'{$locked}>\n";
    echo $indent . "    <input type='submit' name='result' value='Cannot tell [3]' accesskey='3'{$locked}>\n";
    echo $indent . "    <input type='submit' name='result' value='Skip [4]' accesskey='4'>\n";
    echo $indent . "  </p>\n";
    echo $indent . "</form>\n";
    
  }
  
  
  function writeUserAgent($indent = '')
  {
    if ($this->mUserAgent) {
      $uaString = self::Encode($this->mUserAgent->getUAString());
      $description = self::Encode($this->mUserAgent->getDescription());

      echo $indent . "<p class='ua'>\n";
      
      if ($this->mUserAgent->isActualUA()) {
        echo $indent . "  Testing: \n";
        echo $indent . "  <abbr title='{$uaString}'>{$description}</abbr>\n";
      }
      else {
        echo $indent . "  Entering results for: \n";
        echo $indent . "  <abbr class='other' title='{$uaString}'>{$description}</abbr>\n";

        $args = $this->mGetData;
        unset($args['u']);
        $uri = $this->encodeURI(TESTCASE_PAGE_URI, $args);
        echo $indent . " <a href='{$uri}'>(Reset)</a>\n";
      }
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
    $this->writeReferenceAndFormatTabs($indent);

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