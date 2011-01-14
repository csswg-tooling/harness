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
require_once("lib/Groups.php");
require_once("lib/TestCases.php");


/**
 * A class for generating the page to select how to report results
 */
class ReviewPage extends HarnessPage
{  
  protected $mTestGroups;
  protected $mTestCases;


  function __construct() 
  {
    parent::__construct();

    if (! $this->mTestSuite) {
      $msg = 'No test suite identified.';
      $this->triggerClientError($msg, E_USER_ERROR);
    }

    $this->mTestGroups = new Groups($this->mTestSuite->getName());

    $this->mTestCases = new TestCases($this->mTestSuite->getName());
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
  function writeHeadScript($indent = '')
  {
    echo $indent . "<script type='text/javascript'>\n";
    
    echo $indent . "  onunload=function() {\n";
    echo $indent . "    document.result_form.g.disabled = false;\n";
    echo $indent . "    document.result_form.c.disabled = false;\n";
    echo $indent . "  }\n";
    echo $indent . "  function filterTypes() {\n";
    echo $indent . "    if (document.result_form.t[0].checked) {\n";
    echo $indent . "      document.result_form.g.disabled = true;\n";
    echo $indent . "      document.result_form.c.disabled = true;\n";
    echo $indent . "    }\n";
    echo $indent . "    if (document.result_form.t[1].checked) {\n";
    echo $indent . "      document.result_form.g.disabled = false;\n";
    echo $indent . "      document.result_form.c.disabled = true;\n";
    echo $indent . "    }\n";
    echo $indent . "    if (document.result_form.t[2].checked) {\n";
    echo $indent . "      document.result_form.g.disabled = true;\n";
    echo $indent . "      document.result_form.c.disabled = false;\n";
    echo $indent . "    }\n";
    echo $indent . "    return true;\n";
    echo $indent . "  }\n";
    
    echo $indent . "</script>\n";;  
  }
  
  
  function writeTestGroupSelect($indent = '')
  {
    $testGroups = $this->mTestGroups->getGroupData();
    
    echo $indent . "<select name='g'>\n";

    foreach ($testGroups as $groupData) {
      $groupName = Page::Encode($groupData['testgroup']);
      $groupTitle = Page::Encode($groupData['title']);
      echo $indent . "  <option value='{$groupName}'>{$groupTitle}</option>\n";
    }
    
    echo $indent . "</select>\n";
  }
  
  
  function writeTestCaseSelect($indent = '')
  {
    $testCases = $this->mTestCases->getTestCaseData();
    
    echo $indent . "<select name='c' style='width: 25em'>\n";

    foreach ($testCases as $testCaseData) {
      $testCase = Page::Encode($testCaseData['testcase']);
      $testCaseTitle = Page::Encode($testCaseData['title']);
      echo $indent . "  <option value='{$testCase}'>{$testCaseTitle}</option>\n";
    }
    
    echo $indent . "</select>\n";
  }
  

  function writeBodyContent($indent = '')
  {

    echo $indent . "<p>\n";
    echo $indent . "  The " . Page::Encode($this->mTestSuite->getTitle()) . " test suite contains ";
    echo              $this->mTestCases->getCount() . " test cases. \n";
    echo $indent . "  You can choose to review:\n";
    echo $indent . "</p>\n";

    echo $indent . "<form action='results' method='get' name='result_form' onSubmit='return filterTypes();'>\n";
    echo $indent . "  <input type='hidden' name='s' value='" . Page::Encode($this->mTestSuite->getName()) . "' />\n";
    echo $indent . "  <p>\n";
    echo $indent . "    <input type='radio' name='t' value='0' checked />\n";
    echo $indent . "    The full test suite<br />\n";
    
    if (0 < $this->mTestGroups->getCount()) {
      echo $indent . "    <input type='radio' name='t' value='1' />\n";
      echo $indent . "    A group of test cases: \n";
      $this->writeTestGroupSelect($indent . '    ');
      echo $indent . "    <br />\n";
    }
    else {  // write dummy controls so script still works
      echo $indent . "    <span style='display: none'>\n";
      echo $indent . "      <input type='radio' name='t' value='1' />\n";
      echo $indent . "      <input type='hidden' name='g' value='' />\n";
      echo $indent . "    </span>\n";
    }
    
    echo $indent . "    <input type='radio' name='t' value='2' />\n";
    echo $indent . "    A single test case:\n";
    $this->writeTestCaseSelect($indent . '    ');
    echo $indent . "    <br />\n";
    echo $indent . "  </p>\n";

    echo $indent . "  <p>\n";
    echo $indent . "    Do not display tests that:<br />\n";
    echo $indent . "    <input type='checkbox' name='f[]' value='1'> Meet exit criteria<br />\n";
    echo $indent . "    <input type='checkbox' name='f[]' value='2'> Have blocking failures<br />\n";
    echo $indent . "    <input type='checkbox' name='f[]' value='4'> Lack sufficient data<br />\n";
    echo $indent . "    <input type='checkbox' name='f[]' value='8'> Have been reported as invalid<br />\n";
    echo $indent . "    <input type='checkbox' name='f[]' value='16'> Are not required<br />\n";
    echo $indent . "  </p>\n";

    echo $indent . "  <input type='submit' value='Go' />\n";
    echo $indent . "</form>\n";

  }
}

?>