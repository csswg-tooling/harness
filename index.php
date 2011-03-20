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

require_once("pages/WelcomePage.php");

/**
 * Override welcome page to provide CSS specific info
 */
class CSSWelcomePage extends WelcomePage
{  
  function __construct() 
  {
    parent::__construct();

  }  
  
  
  function writeBodyHeader()
  {
    parent::writeBodyHeader();

    $this->addElement('p', null, "This is a development version of a test harness for conducting CSS conformance " .
                                 "testing using the CSS 2.1 Conformance Test Suite.");

    $this->openElement('p', null, FALSE);
    $this->addTextContent("More information about the CSS 2.1 Conformance Test Suite can be found on the ");
    $this->addHyperLink('http://wiki.csswg.org/test', null, "CSS Working Group Wiki");
    $this->addTextContent('.');
    $this->closeElement('p');

    $this->addElement('hr');
  }

  function writeBodyContent()
  {
    parent::writeBodyContent();

    $this->addElement('p', null, "Please make sure your client is configured to:");
    $this->openElement('ul');
    $this->addElement('li', null, "Default black text on a white background.");
    $this->addElement('li', null, "No minimum font size.");
    $this->addElement('li', null, "Print background colors and images.");
    $this->closeElement('ul');

    $this->openElement('p', null, FALSE);
    $this->addElement('strong', null, "Note");
    $this->addTextContent(" that ");
    $this->addElement('em', null, "many");
    $this->addTextContent(" of the tests require the ");
    $this->addHyperLink('http://www.w3.org/Style/CSS/Test/Fonts/Ahem/', null, "Ahem font to be installed");
    $this->addTextContent(". Some of the font-related tests also require ");
    $this->addHyperLink('http://www.w3.org/Style/CSS/Test/Fonts/Overview', null, "special fonts");
    $this->addTextContent(". Without the proper fonts installed, results are of no value.");
    $this->closeElement('p');

    $this->addElement('p', null, "Some tests have additional requirements, which will be noted by the harness interface.");
  }
}

$page = new CSSWelcomePage();
$page->write();

?>