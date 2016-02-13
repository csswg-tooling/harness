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

require_once('lib/Harness.php');
require_once('lib/TestCases.php');
require_once('lib/Results.php');

require_once('modules/testsuite/TestSuite.php');
require_once('modules/testsuite/TestFormat.php');
require_once('modules/specification/Specification.php');
require_once('modules/specification/SpecificationAnchor.php');
require_once('modules/useragent/UserAgent.php');
require_once('modules/useragent/Engine.php');

/**
 * A class for generating the page that presents a test
 * case and the UI to submit results
 */
class TestCasePage extends HarnessPage
{
  protected $mTestCase;
  protected $mIndexData;
  protected $mFormat;
  protected $mDesiredFormat;
  protected $mRefName;
  protected $mSpec;
  protected $mSection;
  protected $mResults;


  static function GetPageKey()
  {
    return 'testcase';
  }

  /**
   * Expected URL paramaters:
   * 'suite' Test Suite Name
   *
   * Optional URL paramaters
   * 'testcase' Test Case Name - test only this test
   * 'spec' Specification
   * 'section' Spec Section Name
   * 'index' Test Case Name - find this test in the group
   * 'order' Test ordering - 0 = alphabetical, 1 = sequenced
   * 'format' Desired format of test
   * 'flag' Flag - only display tests with this flag
   * 'reference' Name of reference
   */
  function _initPage()
  {
    parent::_initPage();

    if ($this->mTestSuite && $this->mTestSuite->isValid()) {
      $testCaseName = $this->_getData('testcase');

      $this->mSpec = null;
      $this->mSection = null;

      if ($testCaseName) {
        $this->mTestCase = TestCase::GetTestCase($this->mTestSuite, $testCaseName);
        $this->mIndexData = array('index' => 0, 'count' => 1,
                                  'first' => $this->mTestCase, 'prev' => null,
                                  'next' => null, 'last' => $this->mTestCase);
      }
      else {
        $specName = $this->_getData('spec');
        if ($specName) {
          $this->mSpec = Specification::GetSpecificationByName($specName);
        }

        $sectionName = $this->_getData('section');
        if ($sectionName) {
          if (! $this->mSpec) {
            $this->mSpec = reset($this->mTestSuite->getSpecifications());
          }
          $this->mSection = SpecificationAnchor::GetSectionFor($this->mSpec, $sectionName);
        }

        $flag = $this->_getData('flag');
        $orderAgent = ($this->_getData('order') ? $this->mUserAgent : null);
        $testCases = new TestCases($this->mTestSuite, $this->mSpec, $this->mSection, TRUE, $flag, $orderAgent);

        $testCaseName = $this->_getData('index');
        if ($testCaseName) {
          $this->mTestCase = $testCases->getTestCase($testCaseName);
        }
        else {
          $this->mTestCase = $testCases->getFirstTestCase();
        }

        $this->mIndexData = $testCases->getIndexData($this->mTestCase);
      }

      $formatName = mb_strtolower($this->_getData('format'));
      $suiteFormats = $this->mTestSuite->getFormats();
      $testFormats = ($this->mTestCase ? $this->mTestCase->getFormats() : $suiteFormats);

      if (array_key_exists($formatName, $suiteFormats)) {
        $this->mDesiredFormat = $suiteFormats[$formatName];

        if (array_key_exists($formatName, $testFormats)) {
          $this->mFormat = $testFormats[$formatName];
        }
        else {
          $this->mFormat = reset($testFormats);
        }
      }
      else {
        $this->mFormat = reset($testFormats);
      }

      if ($this->mTestCase) {
        $this->mSubmitData = $this->_uriData();
        $this->mSubmitData['testcase'] = $this->mTestCase->getName();
        $this->mSubmitData['testcaseid'] = $this->mTestCase->getId();
        $this->mSubmitData['spec'] = ($this->mSpec ? $this->mSpec->getName() : null);
        $this->mSubmitData['section'] = ($this->mSection ? $this->mSection->getName() : null);
        $this->mSubmitData['format'] = $this->mFormat->getName();
        if ($this->mDesiredFormat) {
          $this->mSubmitData['desiredformat'] = $this->mDesiredFormat->getName();
        }
        if ($this->mIndexData['next']) {
          $this->mSubmitData['next'] = $this->mIndexData['next']->getName();
        }
        if (isset($this->mSubmitData['reference'])) {
          unset($this->mSubmitData['reference']);
        }
      }

      $this->mRefName = $this->_getData('reference');
      if ((! $this->mTestCase) || (FALSE === $this->mTestCase->getReferenceURI($this->mRefName, $this->mFormat))) {
        $this->mRefName = null;
      }

      $this->mResults = ($this->mTestCase ? new Results($this->mTestSuite, $this->mTestCase) : null);
    }
  }

  function getRedirectURI()
  {
    if ($result = $this->_postData('result')) {
      switch (mb_strtolower(mb_substr($result, 0, 4))) {
        case 'pass':
          $result = 'pass';
          break;
        case 'fail':
          $result = 'fail';
          break;
        case 'cann':
          $result = 'uncertain';
          break;
        case 'skip':
          $result = null;
          break;
        default:
          return null;
      }
      if ($result && (! $this->mTestSuite->getLockDateTime())) {
        $this->mUser->update();
        $this->mUserAgent->update();
        $passCount = $this->_postData('pass_count');
        $failCount = $this->_postData('fail_count');
        $this->mTestCase->submitResult($this->mUserAgent, $this->mUser, $this->mFormat, $result, $passCount, $failCount);
      }

      $nextTestcaseName = $this->_postData('next');

      $args['suite'] = $this->mTestSuite->getName();
      $args['ua'] = $this->mUserAgent->getId();
      if ($nextTestcaseName) {
        $args['index'] = $nextTestcaseName;
        $args['format'] = ($this->mDesiredFormat ? $this->mDesiredFormat->getName() : null);
        $args['spec'] = ($this->mSpec ? $this->mSpec->getName() : null);
        $args['section'] = ($this->mSection ? $this->mSection->getName() : null);
        $args['flag'] = $this->_postData('flag');
        $args['order'] = $this->_postData('order');

        return $this->buildPageURI('testcase', $args);
      }
      else {
        return $this->buildPageURI('success', $args);
      }
    }
    return null;
  }

  function getNavURIs()
  {
    $uris = parent::getNavURIs();

    $title = "Run Tests";
    $args['suite'] = ($this->mTestSuite ? $this->mTestSuite->getName() : '');
    $args['ua'] = $this->mUserAgent->getId();

    $uri = $this->buildPageURI('testsuite', $args);
    $uris[] = compact('title', 'uri');

    $title = "Test Case";
    $uri = '';
    $uris[] = compact('title', 'uri');

    return $uris;
  }


  /**
   * Generate <style> element
   */
  function writeHeadStyle()
  {
    parent::writeHeadStyle();

    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.test'));

/*
    if ($this->mUserAgent) {
      $actualUA = $this->mUserAgent->getActualUA();
      $actualEngineName = mb_strtolower($actualUA->getEngineName());

      $this->addStyleSheetLink($this->buildURI(sprintf(Config::Get('uri.stylesheet', 'test_engine'), $actualEngineName)));
    }
*/
  }


  function writeHeadScript()
  {
    parent::writeHeadScript();

    $this->addScriptElementInline(Config::Get('uri.script', 'testcase'), 'text/javascript', null, null);
  }


  function getPageTitle()
  {
    $title = parent::getPageTitle();
    if ($this->mSection) {
      return "{$title} - Section {$this->mSection->getName()}";
    }
    return $title;
  }


  function writeTestTitle($elementName = 'div', $class = 'title', $attrs = null)
  {
    if ($this->mTestCase) {
      $title = $this->mTestCase->getTitle();
      $assertion = $this->mTestCase->getAssertion();

      $attrs['class'] = $class;
      $this->openElement($elementName, $attrs, FALSE);

      if (0 < $this->mIndexData['count']) {
        $index = $this->mIndexData['index'] + 1;
        $this->addTextContent("Test {$index} of {$this->mIndexData['count']}" . ($title ? ': ' : ''));
      }
      if ($title) {
        $this->addTextContent('&ldquo;', FALSE);
        if ($assertion) {
          $this->addElement('span', array('title' => $assertion), $title);
        }
        else {
          $this->addTextContent($title);
        }
        $this->addTextContent('&rdquo;', FALSE);
      }
      elseif ($assertion) {
        $this->addTextContent("Assertion: {$assertion}");
      }

      $this->closeElement($elementName);
    }
  }

  function writeSpecLinks($elementName = 'div', $class = 'speclink', $attrs = null)
  {
    if ($this->mTestCase) {
      $anchors = $this->mTestCase->getSpecAnchors();
      $attrs['class'] = $class;
      $this->openElement($elementName, $attrs, FALSE);

      if ($anchors && (0 < count($anchors))) {
        $this->addTextContent('Testing: ');
        $index = -1;
        foreach ($anchors as $specAnchors) {
          $index++;
          if (0 < $index) {
            $this->addTextContent(', ');
          }

          $anchor = reset($specAnchors);
          $spec = $anchor->getSpec();

          if ('section' == $anchor->getStructure()) {
            $sectionName = $anchor->getName();
            $anchorName = null;
          }
          else {
            $sectionName = $anchor->getParentName();
            $anchorName = $anchor->getName();
          }
          $anchorText = "{$spec->getTitle()} \xC2\xA7 {$sectionName}";
          if ($anchorName) {
            $anchorText .= " {$anchor->getDisplaySymbol()} " . ($anchor->getTitle() ? $anchor->getTitle() : $anchorName);
          }
          $attrs['target'] = 'spec';
          if ('draft' == $anchor->getSpecType()) {
            $attrs['class'] = 'draft';
            $attrs['title'] = 'Only present in draft';
          }
          $this->addHyperLink($anchor->getURI($spec), $attrs, $anchorText);

          if (1 < count($specAnchors)) {
            $anchor = next($specAnchors);
            if ('section' == $anchor->getStructure()) {
              $draftSectionName = $anchor->getName();
              $draftAnchorName = null;
            }
            else {
              $draftSectionName = $anchor->getParentName();
              $draftAnchorName = $anchor->getName();
            }
            if (($sectionName != $draftSectionName) || ($anchorName != $draftAnchorName)) {
              $this->addTextContent(' (');
              if (($sectionName == $draftSectionName) && ($anchorName) && ($draftAnchorName)) {
                $anchorText = "{$anchor->getDisplaySymbol()} " . ($anchor->getTitle() ? $anchor->getTitle() : $draftAnchorName);
              }
              else {
                $anchorText = "\xC2\xA7 {$draftSectionName}";
                if ($draftAnchorName) {
                  $anchorText .= " {$anchor->getDisplaySymbol()} " . ($anchor->getTitle() ? $anchor->getTitle() : $draftAnchorName);
                }
              }
              $this->addHyperLink($anchor->getURI($spec),
                                  array('target' => 'spec',
                                        'class' => 'draft',
                                        'title' => 'Link target moved in draft'),
                                  $anchorText);
              $this->addTextContent(')');
            }
          }
        }
      }
      $this->closeElement($elementName);
    }
  }


  function writeTestLinks($elementName = 'div', $class = 'testname', $attrs = null)
  {
    $attrs['class'] = $class;
    $this->openElement($elementName, $attrs);

    $this->addTextContent("Test Case: ");
    $this->addHyperLink($this->mTestCase->getURI($this->mFormat),
                        array('target' => 'test_case'),
                        $this->mTestCase->getName());

    if ($this->mTestCase->isReferenceTest()) {
      $refGroups = $this->mTestCase->getReferenceNames($this->mFormat);
      if ($refGroups) {
        $index = -1;
        foreach ($refGroups as $refNames) {
          $index++;
          if (1 < count($refGroups)) {
            $this->addTextContent("(");
          }
          foreach ($refNames as $refName) {
            $refType = $this->mTestCase->getReferenceType($refName, $this->mFormat);
            $this->addTextContent(" {$refType} ");
            $this->addHyperLink($this->mTestCase->getReferenceURI($refName, $this->mFormat),
                                array('target' => 'reference'),
                                $refName);
          }
          if (1 < count($refGroups)) {
            $this->addTextContent(") ");
            if ($index < (count($refGroups) - 1)) {
              $this->addTextContent(" or ");
            }
          }
        }
      }
    }

    $this->closeElement($elementName);
  }


  function writeShepherdLink($elementName = 'div', $class = 'shepherd', $attrs = null)
  {
    $args['repo'] = $this->mTestSuite->getRepositoryName();
    $args['testcase'] = $this->mTestCase->getName();
    $shepherdURI = Harness::GetShepherdURI($args);
    if ($shepherdURI) {
      $attrs['class'] = $class;
      $this->openElement($elementName, $attrs, FALSE);

      $attrs['class'] = 'button';
      $attrs['target'] = 'shepherd';
      $this->addHyperLink($shepherdURI, $attrs, 'Report Issue');

      $this->closeElement($elementName);
    }
  }

  function writeTestFlags($elementName = 'div', $class = 'notes', $attrs = null)
  {
    $flags = $this->mTestCase->getFlags();
    if ($flags && (0 < $flags->getCount())) {
      $attrs['class'] = $class;
      $this->openElement($elementName, $attrs);
      foreach ($flags->getFlags() as $flagName => $flag) {
        $this->addElement('span', null, $flag->getHTMLDescription() . ' ', FALSE);
      }
      $this->closeElement($elementName);
    }
  }


  function writeFlagTests($elementName = 'span', $class = 'prerequisites', $attrs = null)
  {
    $allFlags = TestFlag::GetAllFlags();
    $flags = $this->mTestCase->getFlags();

    if ($allFlags && (0 < count($allFlags))) {
      $attrs['class'] = $class;
      $this->openElement($elementName, $attrs);
      foreach ($allFlags as $flagName => $flag) {
        if ($flags->hasFlag($flagName)) {
          $this->addTextContent($flag->getSetTest(), FALSE);
        }
        else {
          $this->addTextContent($flag->getUnsetTest(), FALSE);
        }
      }
      $this->closeElement($elementName);
    }
  }


  function writeReferenceTabs()
  {
    if ($this->mTestCase->isReferenceTest()) {
      $refGroups = $this->mTestCase->getReferenceNames($this->mFormat);
      if ((FALSE === $refGroups) || (0 == count($refGroups))) {
        unset($refGroups);
      }
    }

    if (isset($refGroups)) {
      $refCount = 0;
      foreach ($refGroups as $refNames) {
        $refCount += count($refNames);
      }

      $this->openElement('div', array('class' => 'tab_bar'));

      $this->openElement('div', array('class' => 'tab_group', 'id' => 'reference_tabs'));

      $this->openElement('span', array('class' => 'tabs'));
      if (! $this->mRefName) {
        $this->openElement('span', array('class' => 'tab active'));
        $this->addElement('a', null, 'Test Case');
        $this->closeElement('span');
      }
      else {
        $args = $this->_uriData();
        unset($args['reference']);
        $uri = $this->buildPageURI('testcase', $args);

        $this->openElement('span', array('class' => 'tab'));
        $this->addHyperLink($uri, null, 'Test Case');
        $this->closeElement('span');
      }
      $groupIndex = -1;
      foreach ($refGroups as $refNames) {
        $groupIndex++;
        foreach ($refNames as $refName) {
          $refType = $this->mTestCase->getReferenceType($refName, $this->mFormat);
          if (0 == strcasecmp($refName, $this->mRefName)) {
            $this->openElement('span', array('class' => 'tab active', 'data-ref' => "{$refName}-{$groupIndex}"));
            $this->addElement('a', null, "{$refType} Reference Page");
            $this->closeElement('span');
          }
          else {
            $args = $this->_uriData();
            $args['reference'] = $refName;
            $uri = $this->buildPageURI('testcase', $args);

            $this->openElement('span', array('class' => 'tab', 'data-ref' => "{$refName}-{$groupIndex}"));
            $this->addHyperLink($uri, null, "{$refType} Reference Page");
            $this->closeElement('span');
          }
        }
        if ($groupIndex < (count($refGroups) - 1)) {
          $this->addElement('span', array('class' => 'or'), "or");
        }
      }
      $this->closeElement('span'); // .tabs

      $this->openElement('div', array('class' => 'tab_foot'));
      $plural = ((1 < $refCount) ? 's' : '');
      $class = ((! $this->mRefName) ? ' active' : '');
      $this->addElement('p', array('class' => 'instruct' . $class), "This page must be compared to the Reference Page{$plural}");

      $groupIndex = -1;
      foreach ($refGroups as $refNames) {
        $groupIndex++;
        foreach ($refNames as $refName) {
          $not = (('!=' == $this->mTestCase->getReferenceType($refName, $this->mFormat)) ? 'NOT ' : '');
          $class = ((0 == strcasecmp($refName, $this->mRefName)) ? ' active' : '');
          $this->addElement('p', array('class' => 'instruct' . $class, 'data-ref' => "{$refName}-{$groupIndex}"),
                            "This page must {$not}match the Test Case");
        }
      }
      $this->closeElement('div'); // .tab_foot

      $this->closeElement('div'); // .tab_group

      $this->closeElement('div'); // .tabBar
    }
  }

  function writeFormatControls()
  {
    $suiteFormats = $this->mTestSuite->getFormats();

    if (1 < count($suiteFormats)) {
      $this->openElement('span', array('class' => 'format'));

      $this->addTextContent('Format: ');
      $testFormats = $this->mTestCase->getFormats();

      foreach ($suiteFormats as $formatName => $format) {
        $class = 'stateButton';
        if (array_key_exists($formatName, $testFormats)) {
          if (0 == strcasecmp($formatName, $this->mFormat->getName())) {
            $class .= ' active';
            if ($this->mDesiredFormat && (0 != strcasecmp($formatName, $this->mDesiredFormat->getName()))) {
              $class .= ' other';
            }
          }

          $args = $this->_uriData();
          $args['format'] = $formatName;
          $uri = $this->buildPageURI('testcase', $args);
          $this->addHyperLink($uri, array('class' => $class), $format->getTitle());
        }
        else {
          $this->addElement('a', array('class' => 'stateButton disabled'), $format->getTitle());
        }
      }
      $this->closeElement('span');
    }
  }


  function writeResults()
  {
    if ($this->mResults && (0 < $this->mResults->getResultCount())) {
      $engines = $this->mResults->getEngines();
      $counts = $this->mResults->getResultCountsFor($this->mTestCase);

      $args['suite'] = $this->mTestSuite->getName();
      $args['testcase'] = $this->mTestCase->getName();
      $args['ua'] = $this->mUserAgent->getId();

      $this->openElement('div', array('class' => 'results'), FALSE);

      $detailsURI = $this->buildPageURI('details', $args);
      $this->addHyperLink($detailsURI, null, 'Results:');
      $this->addTextContent(' ');

      foreach ($engines as $engineName => $engine) {
        $class = 'engine';
        if (0 < $counts[$engineName]['uncertain']) {
          $class .= ' uncertain';
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
        $args['engine'] = $engineName;
        $this->addHyperLink($this->buildPageURI('details', $args),
                            array('class' => $class), $engine->getTitle());
      }
      $this->closeElement('div');
    }
  }


  function _writeTest($uri, $attrs, $target, $linkText)
  {
    if (static::_IsConnectionSecure()) {
      $uri = static::_ReplaceURIScheme($uri, 'https', 443);
    }
    $attrs['data'] = $uri;
    $attrs['type'] = $this->mFormat->getMimeType();
    $this->openElement('object', $attrs);
    $this->addHyperLink($uri, array('target' => $target), $linkText);
    $this->closeElement('object');
  }

  function writeTest()
  {
    $this->openElement('div', array('class' => 'test_view'));
    $this->openElement('div', array('class' => 'test_wrapper', 'id' => 'test_wrapper'));

    if ($this->mTestCase->isReferenceTest()) {
      $refGroups = $this->mTestCase->getReferenceNames($this->mFormat);

      $class = ((! $this->mRefName) ? 'active' : '');
      $this->_writeTest($this->mTestCase->getURI($this->mFormat), array('class' => $class), 'test_case', 'Run Test');

      $groupIndex = -1;
      foreach ($refGroups as $refNames) {
        $groupIndex++;
        foreach ($refNames as $refName) {
          $class = ((0 == strcasecmp($refName, $this->mRefName)) ? ' active' : '');
          $this->_writeTest($this->mTestCase->getReferenceURI($refName, $this->mFormat),
                            array('class' => $class, 'data-ref' => "{$refName}-{$groupIndex}"),
                            'reference', 'Show Reference');
        }
      }
    }
    else {
      $this->_writeTest($this->mTestCase->getURI($this->mFormat), array('class' => 'active'), 'test_case', 'Run Test');
    }

    $this->closeElement('div');
    $this->closeElement('div');
  }


  function writeSubmitForm()
  {
    $this->openFormElement($this->buildPageURI(null, $this->_uriData()), 'post', 'eval');
    $this->openElement('div', array('class' => 'buttons'));
    $this->writeHiddenFormControls();

    $this->addInputElement('hidden', 'pass_count', 0, 'pass_count');
    $this->addInputElement('hidden', 'fail_count', 0, 'fail_count');

    $locked = (null != $this->mTestSuite->getLockDateTime());
    $this->addInputElement('submit', 'result', 'Pass [1]', 'button_pass', array('accesskey' => '1', 'disabled' => $locked));
    $this->addTextContent(' ');
    $this->addInputElement('submit', 'result', 'Fail [2]', 'button_fail', array('accesskey' => '2', 'disabled' => $locked));
    $this->addTextContent(' ');
    $this->addInputElement('submit', 'result', 'Cannot tell [3]', 'button_cannot', array('accesskey' => '3', 'disabled' => $locked));
    $this->addTextContent(' ');
    $this->addInputElement('submit', 'result', 'Skip [4]', 'button_skip', array('accesskey' => '4'));

    $this->closeElement('div');
    $this->closeElement('form');
  }

  function writeUserAgent()
  {
    if ($this->mUserAgent) {
      $uaString = $this->mUserAgent->getUAString();
      $description = $this->mUserAgent->getDescription();

      $this->openElement('div', array('class' => 'ua'));

      if ($this->mUserAgent->isActualUA()) {
        $this->addTextContent("Testing: ");
        $this->addAbbrElement($uaString, null, $description);
      }
      else {
        $this->addTextContent("Entering results for: ");
        $this->addAbbrElement($uaString, array('class' => 'other'), $description);

        $args = $this->_uriData();
        unset($args['ua']);
        $uri = $this->buildPageURI('testcase', $args);
        $this->openElement('span', null, FALSE);
        $this->addTextContent(' (');
        $this->addHyperLink($uri, null, "Reset");
        $this->addTextContent(')');
        $this->closeElement('span');
      }
      $this->closeElement('div');
    }
  }


  function writeContentTitle($elementName = 'h1', Array $attrs = null)
  {
    if (! $this->inMaintenance()) {
      $this->writeResults();
    }

    parent::writeContentTitle($elementName, $attrs);
  }


  function writeTestInfo()
  {
    $this->openElement('div', array('class' => 'testinfo'));

    $this->writeFormatControls();

    $this->writeTestTitle();

    $this->writeTestLinks();

    $this->writeSpecLinks();

    $this->writeShepherdLink();

    $this->writeTestFlags();

    $this->writeFlagTests();

    $this->closeElement('div');
  }


  function writeBodyContent()
  {
    $this->openElement('div', array('class' => 'body'));

    if ($this->mTestCase) {
      $this->openElement('div', array('class' => 'body_inner'));

      $this->writeTestInfo();

      $this->writeReferenceTabs();

      $this->writeTest();

      $this->closeElement('div');
    }
    else {
      if ($this->mTestSuite && $this->mTestSuite->isValid()) {
        $this->addElement('p', null, 'Unknown test case.');
      }
      else {
        $this->addElement('p', null, 'Unknown test suite.');
      }
    }

    $this->closeElement('div');
  }


  function writeBodyFooter()
  {
    if ($this->mTestCase) {
      $this->openElement('div', array('class' => 'footer'));

      if (! $this->inMaintenance()) {
        $this->writeSubmitForm();

        $this->writeUserAgent();
      }

      $this->addSpiderTrap();

      $this->closeElement('div');
    }
    else {
      parent::writeBodyFooter();
    }
  }
}

?>