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


/**
 * This page is meant to only be fetched by bad robots via the spider trap link
 *
 * When installing the harness, take care to exclude this file by robots.txt
 */
class SpiderTrapPage extends HarnessPage
{  
  function __construct(Array $args = null) 
  {
    parent::__construct($args);
    
    $this->mSpiderTrap->recordVisit();
  }
  

  function writeBodyContent()
  {
    $this->addElement('p', null, "Downloading content from this web site via automated means is expressly forbidden. " .
                                 "Your visit has been logged.");

    $this->addElement('p', null, "Subsequent visits by automated agents will result in " .
                                 "all visitors from your IP address being banned from this site.");
  }
}


?>