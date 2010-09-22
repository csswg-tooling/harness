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

require_once("./lib_css2.1_harness/class.css_page.phi");
require_once("./lib_css2.1_harness/class.test_suites.phi");

////////////////////////////////////////////////////////////////////////////////
//
//	class success_page
//
//	A class for generating the welcome page for a test harness
//
////////////////////////////////////////////////////////////////////////////////
class success_page extends css_page
{	
	////////////////////////////////////////////////////////////////////////////
	//
	//	Constructor.
	//
	////////////////////////////////////////////////////////////////////////////
	function welcome_page() 
	{
		parent::css_page();

		$this->m_page_title = 'W3C CSS 2.1 Conformance Test Harness';
		
		$this->m_content_title = 'W3C CSS 2.1 Conformance Test Harness';

		// $this->m_resource_id 
		// 	= '$Id: success.php,v 1.1 2008/08/05 15:38:44 dom Exp $';		
	}	
	
	////////////////////////////////////////////////////////////////////////////
	//
	// write_body_content()
	//
	////////////////////////////////////////////////////////////////////////////
	function write_body_content($indent = '')
	{	
		echo $indent . '<p>'."\n";
		echo $indent . '  Thank you for providing data ';
		echo 'for conducting CSS 2.1 conformance'."\n";
		echo $indent . '  testing using the ';
		echo '<a href="http://www.w3.org/Style/CSS/Test/CSS2.1/current/">';
		echo 'CSS 2.1'."\n";
		echo $indent . '  Conformance Test Suite</a>.'."\n";
		echo $indent . '</p>'."\n";

		echo $indent . '<p>'."\n";
		echo $indent . '  You can submit additional results and access ';
		echo 'collected results from the '."\n";
		echo $indent . '  <a href="index.php">'."\n";
		echo $indent . '    harness welcome page'."\n";
		echo $indent . '  </a>.'."\n";
		echo $indent . '</p>'."\n";

	}
}

$page = new success_page();
$page -> write();

?>
