<?php
  ////////////////////////////////////////////////////////////////////////////////
  //
  //  Copyright © 2010 Hewlett-Packard Development Company, L.P. 
  // 
  //  This work is distributed under the W3C¬ Software License 
  //  [1] in the hope that it will be useful, but WITHOUT ANY 
  //  WARRANTY; without even the implied warranty of 
  //  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
  // 
  //  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
  //
  //////////////////////////////////////////////////////////////////////////////// 
  
  //////////////////////////////////////////////////////////////////////////////// 
  //
  //  report.php
  //
  //  this page is meant to only be fetched by bad robots via the spider trap link
  //
  //  When installing the harness, take care to exclude this file by robots.txt
  //
  //////////////////////////////////////////////////////////////////////////////// 
  
  require_once("./lib_css2.1_harness/class.css_page.phi");
  require_once("./lib_test_harness/class.SpiderTrap.phi");
  
  ////////////////////////////////////////////////////////////////////////////////
  //
  //  class SpiderTrapPage
  //
  ////////////////////////////////////////////////////////////////////////////////
  class SpiderTrapPage extends css_page
  {  
    
    
    ////////////////////////////////////////////////////////////////////////////
    //
    //  Constructor.
    //
    ////////////////////////////////////////////////////////////////////////////
    function __construct() 
    {
      parent::css_page();
      
      $this->m_page_title  = 'W3C CSS Conformance Test Harness ';
      $this->m_content_title = 'W3C CSS Conformance Test Harness';

      $this->mSpiderTrap->recordVisit();
    }
    
    ////////////////////////////////////////////////////////////////////////////
    //
    // write_body_content()
    //
    ////////////////////////////////////////////////////////////////////////////
    function write_body_content($indent = '')
    {
      echo $indent . "  <p>\n";
      echo $indent . "    Downloading content from this web site via automated ";
      echo               "means is expressly forbidden. Your visit has been logged.\n";
      echo $indent . "  </p>\n";
      echo $indent . "  <p>\n";
      echo $indent . "    Subsequent visits by automated agents will result in ";
      echo               "all visitors from your IP address being banned from this site.\n";
      echo $indent . "  </p>\n";
    }
  }
  
  $page = new SpiderTrapPage();
  $page->write();
  
?>