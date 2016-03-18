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
require_once("lib/Sections.php");
require_once("lib/TestCases.php");
require_once("lib/TestCase.php");

require_once("modules/testsuite/TestSuite.php");
require_once("modules/useragent/UserAgent.php");


/**
 * Class for generateing the page to select which tests will be run
 */
class TestSuitePage extends HarnessPage
{
  protected $mSections;
  protected $mTestCases;

  static function GetPageKey()
  {
    return 'testsuite';
  }


  protected function _initPage()
  {
    parent::_initPage();

    if ($this->mTestSuite) {
      $this->mSections = new Sections($this->mTestSuite);

      $spec = null;
      $section = null;
      if ($this->_postData('spec')) {
        $spec = Specification::GetSpecificationByName($this->_postData('spec'));
      }
      if ($this->_postData('section')) {
        if (! $spec) {
          $spec = array_first($this->mTestSuite->getSpecifications());
        }
        $section = SpecificationAnchor::GetSectionFor($spec, $this->_postData('section'));
      }
      $flag = $this->_postData('flag');
      $orderAgent = ($this->_postData('order') ? $this->mUserAgent : null);
      $this->mTestCases = new TestCases($this->mTestSuite, $spec, $section, TRUE, $flag, $orderAgent);

      $this->mSubmitData['suite'] = $this->mTestSuite->getName();
    }

    if (! $this->mUserAgent->isActualUA()) {
      $this->mSubmitData['ua'] = $this->mUserAgent->getId();
    }

  }

  function getRedirectURI()
  {
    if ('Start' == $this->_postData('action')) {
      $args['suite'] = $this->mTestSuite->getName();
      $args['ua'] = $this->mUserAgent->getId();
      $args['flag'] = $this->_requestData('flag');

      if ($this->_postData('testcase')) {
        $args['testcase'] = $this->_postData('testcase');
      }
      else {
        $args['spec'] = $this->_postData('spec');
        $args['section'] = $this->_postData('section');
        $args['order'] = $this->_postData('order');
        $args['index'] = $this->mTestCases->getFirstTestCase()->getName();
      }

      return $this->buildPageURI('testcase', $args);
    }
    return null;
  }


  function getNavURIs()
  {
    $uris = parent::getNavURIs();

    if ($this->mTestSuite) {
      $title = "Run Tests";
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

    $this->addStyleSheetLink($this->buildConfigURI('stylesheet.testsuite'));
  }


  function writeHeadScript()
  {
    parent::writeHeadScript();

    $this->addScriptElementInline(Config::Get('uri.script', 'testsuite'), 'text/javascript', null, null);
  }

  function writeTestSuiteForm()
  {
    $this->openFormElement('', 'post');
    $this->writeHiddenFormControls();
    $this->addElement('strong', null, "The full test suite: ");
    $this->writeOrderSelect();
    $this->addTextContent(' ');
    $this->addInputElement('submit', 'action', 'Start');
    $this->closeElement('form');
  }


  function writeSectionOptions(Specification $spec, SpecificationAnchor $parent = null)
  {
    $sections = $this->mSections->getSubSections($spec, $parent);
    foreach ($sections as $section) {
      $sectionName = $section->getName();
      $testCount = $section->getLinkCount();
      $subSectionCount = $this->mSections->getSubSectionCount($spec, $section);
      if ((1 != $subSectionCount) || (0 < $testCount)) {
        $this->addOptionElement($sectionName, null, $section->getSectionTitle());
      }
      if (0 < $subSectionCount) {
        $this->writeSectionOptions($spec, $section);
      }
    }
  }


  function writeSectionSelect(Specification $spec)
  {
    $this->openSelectElement('section', array('style' => 'width: 25em'));
    $this->writeSectionOptions($spec);
    $this->closeElement('select');
  }


  function writeSectionForm($title)
  {
    $this->addTextContent($title);
    $specs = $this->mSections->getSpecifications();
    foreach ($specs as $specName => $spec) {
      if (1 < count($specs)) {
        $this->openElement('p');
        $this->addTextContent($spec->getTitle());
        $this->mSubmitData['spec'] = $specName;
      }
      $this->openFormElement('', 'post');
      $this->writeHiddenFormControls();
      $this->writeSectionSelect($spec);
      $this->addTextContent(' ');
      $this->writeOrderSelect();
      $this->addTextContent(' ');
      $this->addInputElement('submit', 'action', 'Start');
      $this->closeElement('form');
      if (1 < count($specs)) {
        $this->closeElement('p');
      }
    }
  }


  function writeTestCaseSelect()
  {
    $testCases = $this->mTestCases->getTestCases();

    if (1 < count($testCases)) {
      $this->openSelectElement('testcase', array('style' => 'width: 25em'));

      foreach ($testCases as $testCase) {
        $testCaseName = $testCase->getName();

        $this->addOptionElement($testCaseName, null,
                                "{$testCaseName}: {$testCase->getTitle()}");
      }

      $this->closeElement('select');
    }
    else {
      $testCase = reset($testCases);
      $this->addTextContent("{$testCase->getName()}: {$testCase->getTitle()}");
    }
  }


  function writeTestCaseForm($title)
  {
    $this->openFormElement('', 'post');
    $this->writeHiddenFormControls();
    $this->addTextContent($title);
    $this->writeTestCaseSelect();
    $this->addTextContent(' ');
    $this->addInputElement('submit', 'action', 'Start');
    $this->closeElement('form');
  }


  function writeOrderSelect()
  {
    $this->openSelectElement('order');

    $this->addOptionElement(1, array('selected' => TRUE), 'in most needed order');
    $this->addOptionElement(0, null, 'in alphabetical order');

    $this->closeElement('select');
  }


  function writeUASelect()
  {
    $this->openElement('p', array('class' => 'ua'));
    $this->addTextContent("You are about run tests for the following user agent: ");

    if ($this->mUserAgent->isActualUA()) {
      $this->addAbbrElement($this->mUserAgent->getUAString(), null, $this->mUserAgent->getDescription());

      $args = $this->_uriData();
      $uri = $this->buildPageURI('select_ua', $args);

      $this->openElement('span', null, FALSE);
      $this->addTextContent(' (');
      $this->addHyperLink($uri, null, 'Change');
      $this->addTextContent(')');
      $this->closeElement('span');
    }
    else {
      $this->addAbbrElement($this->mUserAgent->getUAString(),
                            array('class' => 'other'),
                            $this->mUserAgent->getDescription());

      $args = $this->_uriData();
      unset($args['ua']);
      $uri = $this->buildPageURI('testsuite', $args);
      $this->openElement('span', null, FALSE);
      $this->addTextContent(' (');
      $this->addHyperLink($uri, null, 'Reset');
      $this->addTextContent(')');
      $this->closeElement('span');
    }
    $this->closeElement('p');
  }

  function writeTestControls()
  {
    if (1 < $this->mTestCases->getCount()) {
      $this->addElement('p', null,
                        "The {$this->mTestSuite->getTitle()} contains {$this->mTestCases->getCount()} test cases. " .
                        "You can stop running tests at any time without causing trouble.");

      $this->addElement('p', null, "You can test:");
      $this->openElement('ul');

      $this->openElement('li');
      $this->writeTestSuiteForm();
      $this->closeElement('li');

      if (0 < count($this->mSections->getSpecifications())) {
        $this->openElement('li');
        $this->writeSectionForm("A section of the specification: ");
        $this->closeElement('li');
      }

      $this->openElement('li');
      $this->writeTestCaseForm("A single test case: ");
      $this->closeElement('li');

      $this->closeElement('ul');
    }
    else {
      $this->addElement('p', null, "The {$this->mTestSuite->getTitle()} contains 1 test case.");

      $this->writeTestCaseForm('');
    }

    $this->addElement('span', array('id' => 'extra_controls'), '');
  }


  function writeUploadLink()
  {
    if ($this->mUser->hasRole('tester')) {
      $this->openElement('div', null, FALSE);
      $this->addTextContent("Alternatively results can be imported from an ");
      $this->addHyperLink($this->_CombinePath($this->mTestSuite->getURI(), 'implementation-report-TEMPLATE.data'), null, 'implementation report');
      $this->addTextContent(": ");
      $args['suite'] = $this->mTestSuite->getName();
      $args['ua'] = $this->mUserAgent->getId();
      $uri = $this->buildPageURI('upload_results', $args);
      $this->openFormElement($uri, 'GET');
      $this->addInputElement('submit', null, 'Batch Upload');
      $this->closeElement('form');
      $this->closeElement('div');
    }
  }


  function writeTestingNotes()
  {
    $this->openElement('p');
    $this->addElement('strong', null, "Note: ");
    $this->addTextContent("The harness presents each test case embedded within a page " .
                          "that contains information about the test and a form to submit results. " .
                          "This page is not a part of the original test case and any influence it may have " .
                          "on the test should be ignored. Links are provided to see the test case and any " .
                          "reference pages in a separate window if desired.");
    $this->closeElement('p');

    if (Config::Get('system', 'shepherd')) {
      $this->addElement('p', null,
                        'If a the test does not appear to be functioning correctly, please press the "Report Issue" button.');
    }

    $this->addElement('p', null,
                      'If there is any doubt about the result of a test, please press the "Cannot Tell" button.');
    $this->addElement('p', null,
                      'If listed requirements for a test cannot be met, please press the "Skip" button.');
  }


  function writeBodyContent()
  {
    $this->openElement('div', array('class' => 'body'));

    if ((! $this->mTestSuite) || (! $this->mTestSuite->isValid())) {
      $this->addElement('p', null, 'Unknown test suite.');
    }
    elseif (! $this->mTestCases->getCount()) {
      $this->addElement('p', null, "The {$this->mTestSuite->getTitle()} does not contain any test cases. ");
    }
    else {
      $this->writeUASelect();

      $this->writeTestControls();

      $this->writeUploadLink();

      $this->writeTestingNotes();
    }

    if (Config::IsDebugMode()) {
      if ($this->mSections) {
        $this->addElement('span', null, 'Sections: ' . $this->mSections->getQueryTime());
      }
      if ($this->mTestCases) {
        $this->addElement('span', null, 'Testcases: ' . $this->mTestCases->getQueryTime());
      }
    }

    $this->closeElement('div');
  }
}

?>