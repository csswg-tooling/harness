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

/**
 * This class is an example of how to modify the UI of any harness page
 *
 * Simply subclass the page you wish to modify adding the word 'Local' to 
 * the beginning of the class name. Then safe the file anywhere in the PHP
 * include path or in harness/local with the file name 'OriginalClass.local.php'
 *
 */
class LocalWelcomePage extends WelcomePage
{  
  function __construct() 
  {
    parent::__construct();

  }  
  
  
  function writeBodyHeader()
  {
    parent::writeBodyHeader();

    $this->addElement('p', null, "Here is some extra text to add to the page header.");

    $this->addElement('hr');
  }

  function writeBodyContent()
  {
    parent::writeBodyContent();

    $this->addElement('p', null, "Here is some extra text added to the page body.");
  }
}

?>