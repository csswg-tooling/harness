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
require_once("lib/Sections.php");
require_once("lib/TestCases.php");


/**
 * A class for generating the page to select how to report results
 */
class ReviewPage extends HarnessPage
{  
  protected $mSections;
  protected $mTestCases;


  function __construct() 
  {
    parent::__construct();

    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      trigger_error($msg, E_USER_WARNING);
    }

    $this->mSections = new Sections($this->mTestSuite);

    $this->mTestCases = new TestCases($this->mTestSuite);
    
    $this->mSubmitData['s'] = $this->mTestSuite->getName();
    if (! $this->mUserAgent->isActualUA()) {
      $this->mSubmitData['u'] = $this->mUserAgent->getId();
    }
    
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


  /**
   * Generate <script> element
   */
  function writeHeadScript()
  {
    $script  = "onunload=function() {\n";
    $script .= "  document.result_form.g.disabled = false;\n";
    $script .= "  document.result_form.c.disabled = false;\n";
    $script .= "}\n";
    $script .= "function filterTypes() {\n";
    $script .= "  if (document.result_form.t[0].checked) {\n";
    $script .= "    document.result_form.g.disabled = true;\n";
    $script .= "    document.result_form.c.disabled = true;\n";
    $script .= "  }\n";
    $script .= "  if (document.result_form.t[1].checked) {\n";
    $script .= "    document.result_form.g.disabled = false;\n";
    $script .= "    document.result_form.c.disabled = true;\n";
    $script .= "  }\n";
    $script .= "  if (document.result_form.t[2].checked) {\n";
    $script .= "    document.result_form.g.disabled = true;\n";
    $script .= "    document.result_form.c.disabled = false;\n";
    $script .= "  }\n";
    $script .= "  return true;\n";
    $script .= "}\n";

    $this->addScriptElement($script);
  }
  
  
  function writeSectionOptions($parentId = 0)
  {
    $data = $this->mSections->getSectionData($parentId);
    foreach ($data as $sectionData) {
      $id = $sectionData['id'];
      $subSectionCount = $this->mSections->getCount($id);
      if (1 != $subSectionCount) {
        $this->addOptionElement($id, null, "{$sectionData['section']}: {$sectionData['title']}");
      }
      if (0 < $subSectionCount) {
        $this->writeSectionOptions($id);
      }
    }
  }


  function writeSectionSelect()
  {
    $sections = $this->mSections->getSectionData();
    
    $this->openSelectElement('g', array('style' => 'width: 25em',
                                        'onchange' => 'document.result_form.t[1].checked = true'));
    $this->writeSectionOptions();
    $this->closeElement('select');
  }
  
  
  function writeTestCaseSelect()
  {
    $testCases = $this->mTestCases->getTestCaseData();
    
    $this->openSelectElement('c', array('style' => 'width: 25em',
                                        'onchange' => 'document.result_form.t[2].checked = true'));

    foreach ($testCases as $testCaseData) {
      $testCaseName = $testCaseData['testcase'];
      
      $this->addOptionElement($testCaseName, null,
                              "{$testCaseName}: {$testCaseData['title']}");
    }

    $this->closeElement('select');
  }
  

  function writeBodyContent()
  {
    $this->openElement('p');
    $this->addTextContent("The {$this->mTestSuite->getTitle()} test suite contains ");
    $this->addTextContent($this->mTestCases->getCount() . " test cases.");
    $this->addTextContent("You can choose to review:");
    $this->closeElement('p');
    
    $this->openFormElement(RESULTS_PAGE_URI, 'get', 'result_form', array('onSubmit' => 'return filterTypes();'));

    $this->writeHiddenFormControls(TRUE);
    
    $this->openElement('p');
    
    $this->addInputElement('radio', 't', 0, array('checked' => TRUE));
    $this->addTextContent(' The full test suite');
    $this->addElement('br');
    
    if (0 < $this->mSections->getCount()) {
      $this->addInputElement('radio', 't', 1);
      $this->addTextContent(' A section of the specification: ');
      $this->writeSectionSelect();
      $this->addElement('br');
    }
    else {  // write dummy controls so script still works
      $this->openElement('span', array('style' => 'display: none'));
      $this->addInputElement('radio', 't', 1);
      $this->addInputElement('hidden', 'g', '');
      $this->closeElement('span');
    }
    
    $this->addInputElement('radio', 't', 2);
    $this->addTextContent(' A single test case: ');
    $this->writeTestCaseSelect();
    $this->addElement('br');
    
    $this->closeElement('p');

    $this->openElement('p');
    $this->addTextContent('Do not display tests that:');
    $this->addElement('br');

    $this->addInputElement('checkbox', 'f[]', 1);
    $this->addTextContent(' Meet exit criteria');
    $this->addElement('br');
    
    $this->addInputElement('checkbox', 'f[]', 2);
    $this->addTextContent(' Have blocking failures');
    $this->addElement('br');
    
    $this->addInputElement('checkbox', 'f[]', 4);
    $this->addTextContent(' Lack sufficient data');
    $this->addElement('br');
    
    $this->addInputElement('checkbox', 'f[]', 8);
    $this->addTextContent(' Have been reported as invalid');
    $this->addElement('br');
    
    $this->addInputElement('checkbox', 'f[]', 16);
    $this->addTextContent(' Are not required');
    $this->addElement('br');
        
    $this->closeElement('p');

    $this->addInputElement('submit', null, 'Go');

    $this->closeElement('form');

  }
}

?>