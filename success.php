<?php
////////////////////////////////////////////////////////////////////////////////
//
//  Copyright © 2007 World Wide Web Consortium, 
//  (Massachusetts Institute of Technology, European Research 
//  Consortium for Informatics and Mathematics, Keio 
//  University). All Rights Reserved. 
//  Copyright © 2008 Hewlett-Packard Development Company, L.P. 
// 
//  This work is distributed under the W3CÂ Software License 
//  [1] in the hope that it will be useful, but WITHOUT ANY 
//  WARRANTY; without even the implied warranty of 
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// 
//  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
//
//////////////////////////////////////////////////////////////////////////////// 

//////////////////////////////////////////////////////////////////////////////// 
//
//  testcase.php
//
//  Adapted from Mobile Test Harness [1]
//
//    File: harness.php
//      Lines: 151-170
//
//  where herein specific contents provided by the original harness have
//  been adapted for CSS2.1 conformance testing.
//
// [1] http://dev.w3.org/cvsweb/2007/mobile-test-harness/
//
//////////////////////////////////////////////////////////////////////////////// 

require_once("lib/HarnessPage.php");
require_once("lib/TestSuites.php");

////////////////////////////////////////////////////////////////////////////////
//
//	class success_page
//
//	A class for generating the welcome page for a test harness
//
////////////////////////////////////////////////////////////////////////////////
class SuccessPage extends HarnessPage
{	
	////////////////////////////////////////////////////////////////////////////
	//
	//	Constructor.
	//
	////////////////////////////////////////////////////////////////////////////
	function __construct() 
	{
		parent::__construct();

	}	
	
	////////////////////////////////////////////////////////////////////////////
	//
	// write_body_content()
	//
	////////////////////////////////////////////////////////////////////////////
	function write_body_content($indent = '')
	{ // XXX link to submit more results, link to review results
      // XXX factor getting test suite to base class
      echo $indent . "<p>\n";
		echo $indent . "  Thank you for providing data for conducting conformance\n";
		echo $indent . "  testing using the ";
		echo '<a href="http://www.w3.org/Style/CSS/Test/CSS2.1/">';
		echo 'CSS 2.1'."\n";
		echo $indent . '  Conformance Test Suite</a>.'."\n";
		echo $indent . '</p>'."\n";

		echo $indent . '<p>'."\n";
		echo $indent . '  You can submit additional results and access ';
		echo 'collected results from the '."\n";
		echo $indent . '  <a href="./">'."\n";
		echo $indent . '    harness welcome page'."\n";
		echo $indent . '  </a>.'."\n";
		echo $indent . '</p>'."\n";

	}
}

$page = new SuccessPage();
$page->write();

?>