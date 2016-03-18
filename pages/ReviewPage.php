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



/**
 * A class for generating the page to select how to report results
 */
class ReviewPage extends HarnessPage
{
  protected $mSections;
  protected $mTestCases;

  protected $mResultsURI;


  static function GetPageKey()
  {
    return 'review';
  }

  function __construct(Array $args = null, Array $pathComponents = null)
  {
    parent::__construct($args, $pathComponents);

    $this->mSections = new Sections($this->mTestSuite);

    $this->mTestCases = new TestCases($this->mTestSuite);

    $this->mSubmitData['suite'] = $this->mTestSuite->getName();
    if (! $this->mUserAgent->isActualUA()) {
      $this->mSubmitData['ua'] = $this->mUserAgent->getId();
    }

    $this->mResultsURI = null;
    if (('Go' == $this->_postData('action')) || (1 == $this->mTestCases->getCount())) {
      $args['suite'] = $this->mTestSuite->getName();
      $args['testcase'] = $this->_postData('testcase');
      $args['section'] = $this->_postData('section');

      if (null !== $this->_postData('type')) {
        $type = intval($this->_postData('type'));
        switch ($type) {
          case 0: unset($args['section']);  // whole suite
          case 1: unset($args['testcase']); // test group
                  break;
          case 2: unset($args['section']);  // individual test case
                  break;
        }
      }

      $filter = $this->_postData('filter');
      if (is_array($filter)) {
        $filterValue = 0;
        foreach ($filter as $value) {
          $filterValue = $filterValue | intval($value);
        }
        $args['filter'] = $filterValue;
      }
      $args['order'] = $this->_postData('order');

      $this->mResultsURI = $this->buildPageURI('results', $args);
    }

  }

  function getRedirectURI()
  {
    return $this->mResultsURI;
  }

  function getPageTitle()
  {
    $title = parent::getPageTitle();
    return "{$title} Results";
  }


  function getNavURIs()
  {
    $uris = parent::getNavURIs();

    $title = "Review Results";
    $uri = '';
    $uris[] = compact('title', 'uri');

    return $uris;
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
    $this->openSelectElement('section', array('style' => 'width: 25em',
                                              'onchange' => 'document.getElementById("result_form").type[1].checked = true'));
    $this->writeSectionOptions($spec);
    $this->closeElement('select');
  }


  function writeTestCaseSelect()
  {
    $testCases = $this->mTestCases->getTestCases();

    if (1 < count($testCases)) {
      $this->openSelectElement('testcase', array('style' => 'width: 25em',
                                                 'onchange' => 'document.getElementById("result_form").type[2].checked = true'));

      foreach ($testCases as $testCase) {
        $testCaseName = $testCase->getName();

        $this->addOptionElement($testCaseName, null,
                                "{$testCaseName}: {$testCase->getTitle()}");
      }

      $this->closeElement('select');
    }
    else {
      $testCaseData = reset($testCases);
      $this->addTextContent("{$testCase->getName()}: {$testCase->getTitle()}");
    }
  }


  function writeTestControls()
  {
    $this->addElement('p', null,
                      "The {$this->mTestSuite->getTitle()} contains {$this->mTestCases->getCount()} test cases.");

    $this->openFormElement('', 'post', 'result_form');

    $this->openElement('p');
    $this->addTextContent("You can choose to review:");
    $this->addElement('br');

    $this->writeHiddenFormControls(TRUE);

    $this->addInputElement('radio', 'type', 0, 'type0', array('checked' => TRUE));
    $this->addLabelElement('type0', ' The full test suite');
    $this->addElement('br');

    $specs = $this->mSections->getSpecifications();
    $sectionCount = 0;
    foreach ($specs as $specName => $spec) {
      $sectionCount++;
      if (0 < $this->mSections->getSubSectionCount($spec)) {
        $sectionCount++;
        $this->addInputElement('radio', 'type', 1, 'type1');
        $specName = ((1 < count($specs)) ? ' ' . $spec->getTitle() : '');
        $this->addLabelElement('type1', " A section of the specification{$specName}: ");
        $this->writeSectionSelect($spec);
        $this->addElement('br');
      }
    }

    $this->addInputElement('radio', 'type', 2, 'type2');
    $this->addLabelElement('type2', ' A single test case: ');
    $this->writeTestCaseSelect();
    $this->closeElement('p');

    if (1 < $sectionCount) {
      $this->openElement('p');
      $this->addTextContent('Options:');
      $this->addElement('br');
      $this->addInputElement('checkbox', 'order', 1, 'order1', array('checked' => TRUE));
      $this->addLabelElement('order1', ' Group by specification section');
      $this->closeElement('p');
    }

    $this->writeFilterControls();

    $this->addInputElement('submit', 'action', 'Go', 'submit');

    $this->closeElement('form');
  }

  function writeFilterControls()
  {
    $this->openElement('p');

    $this->addTextContent('Do not display tests that:');
    $this->addElement('br');

    $this->addInputElement('checkbox', 'filter[]', 1, 'filter1');
    $this->addLabelElement('filter1', ' Meet exit criteria');
    $this->addElement('br');

    $this->addInputElement('checkbox', 'filter[]', 2, 'filter2');
    $this->addLabelElement('filter2', ' Have blocking failures');
    $this->addElement('br');

    $this->addInputElement('checkbox', 'filter[]', 4, 'filter4');
    $this->addLabelElement('filter4', ' Lack sufficient data');
    $this->addElement('br');

    $this->addInputElement('checkbox', 'filter[]', 8, 'filter8');
    $this->addLabelElement('filter8', ' Have been reported as invalid');
    $this->addElement('br');

    $this->addInputElement('checkbox', 'filter[]', 16, 'filter16');
    $this->addLabelElement('filter16', ' Are not required');
    $this->addElement('br');

    $this->closeElement('p');
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
      $this->writeTestControls();
    }

    $this->closeElement('div');
  }
}

?>