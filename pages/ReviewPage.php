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


  function __construct(Array $args = null) 
  {
    parent::__construct($args);

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
    $script .= "  var resultForm = document.getElementById('result_form');";
    $script .= "  resultForm.sec.disabled = false;\n";
    $script .= "  resultForm.c.disabled = false;\n";
    $script .= "  resultForm.o.disabled = false;\n";
    $script .= "}\n";
    $script .= "function filterTypes() {\n";
    $script .= "  var resultForm = document.getElementById('result_form');";
    $script .= "  if (resultForm.t[0].checked) {\n";
    $script .= "    resultForm.sec.disabled = true;\n";
    $script .= "    resultForm.c.disabled = true;\n";
    $script .= "  }\n";
    $script .= "  if (resultForm.t[1].checked) {\n";
    $script .= "    resultForm.sec.disabled = false;\n";
    $script .= "    resultForm.c.disabled = true;\n";
    $script .= "  }\n";
    $script .= "  if (resultForm.t[2].checked) {\n";
    $script .= "    resultForm.sec.disabled = true;\n";
    $script .= "    resultForm.c.disabled = false;\n";
    $script .= "    resultForm.o.disabled = true;\n";
    $script .= "  }\n";
    $script .= "  return true;\n";
    $script .= "}\n";

    $this->addScriptElement($script);
  }
  
  
  function writeSectionOptions($parentId = 0)
  {
    $data = $this->mSections->getSubSectionData($parentId);
    foreach ($data as $sectionData) {
      $id = $sectionData['id'];
      $sectionName = $sectionData['section'];
      $testCount = $sectionData['test_count'];
      $subSectionCount = $this->mSections->getSubSectionCount($id);
      if ((1 != $subSectionCount) || (0 < $testCount)) {
        $this->addOptionElement($sectionName, null, "{$sectionName}: {$sectionData['title']}");
      }
      if (0 < $subSectionCount) {
        $this->writeSectionOptions($id);
      }
    }
  }


  function writeSectionSelect()
  {
    $this->openSelectElement('sec', array('style' => 'width: 25em',
                                          'onchange' => 'document.getElementById("result_form").t[1].checked = true'));
    $this->writeSectionOptions();
    $this->closeElement('select');
  }
  
  
  function writeTestCaseSelect()
  {
    $testCases = $this->mTestCases->getTestCaseData();
    
    $this->openSelectElement('c', array('style' => 'width: 25em',
                                        'onchange' => 'document.getElementById("result_form").t[2].checked = true'));

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
    
    $this->openFormElement($this->buildConfigURI(Config::Get('server', 'rewrite_urls') ? 'page.load_results' : 'page.results'), 
                           'get', 'result_form', array('onSubmit' => 'return filterTypes();'));

    $this->openElement('p');
    
    $this->writeHiddenFormControls(TRUE);
    
    $this->addInputElement('radio', 't', 0, 't0', array('checked' => TRUE));
    $this->addLabelElement('t0', ' The full test suite');
    $this->addElement('br');
    
    if (0 < $this->mSections->getSubSectionCount()) {
      $this->addInputElement('radio', 't', 1, 't1');
      $this->addLabelElement('t1', ' A section of the specification: ');
      $this->writeSectionSelect();
      $this->addElement('br');
    }
    else {  // write dummy controls so script still works
      $this->openElement('span', array('style' => 'display: none'));
      $this->addInputElement('radio', 't', 1);
      $this->addInputElement('hidden', 'sec', '');
      $this->closeElement('span');
    }
    
    $this->addInputElement('radio', 't', 2, 't2');
    $this->addLabelElement('t2', ' A single test case: ');
    $this->writeTestCaseSelect();
    $this->addElement('br');
    
    $this->closeElement('p');
    
    if (0 < $this->mSections->getSubSectionCount()) {
      $this->openElement('p');
      $this->addTextContent('Options:');
      $this->addElement('br');
      $this->addInputElement('checkbox', 'o', 1, 'o1');
      $this->addLabelElement('o1', ' Group by specification section');
      $this->closeElement('p');
    }

    $this->openElement('p');
    $this->addTextContent('Do not display tests that:');
    $this->addElement('br');

    $this->addInputElement('checkbox', 'f[]', 1, 'f1');
    $this->addLabelElement('f1', ' Meet exit criteria');
    $this->addElement('br');
    
    $this->addInputElement('checkbox', 'f[]', 2, 'f2');
    $this->addLabelElement('f2', ' Have blocking failures');
    $this->addElement('br');
    
    $this->addInputElement('checkbox', 'f[]', 4, 'f4');
    $this->addLabelElement('f4', ' Lack sufficient data');
    $this->addElement('br');
    
    $this->addInputElement('checkbox', 'f[]', 8, 'f8');
    $this->addLabelElement('f8', ' Have been reported as invalid');
    $this->addElement('br');
    
    $this->addInputElement('checkbox', 'f[]', 16, 'f16');
    $this->addLabelElement('f16', ' Are not required');
    $this->addElement('br');
        
    $this->closeElement('p');

    $this->addInputElement('submit', null, 'Go');

    $this->closeElement('form');

  }
}

?>