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

require_once('core/SystemDelegate.php');

require_once('lib/HarnessURIConverter.php');
require_once('lib/HarnessDB.php');
require_once('lib/HarnessPage.php');

require_once('modules/process/Events.php');



/**
 * Provide functionality specific to test harness pages
 */
class Harness extends SystemDelegate
{

  static function ExternalPageURI($baseURI)
  {
    return HarnessPage::_MatchURIConnectionSecurity(HarnessPage::_BuildPageURI($baseURI, null, null, TRUE));
  }
  
  static function ExternalConfigURI($pageKey)
  {
    return HarnessPage::_MatchURIConnectionSecurity(HarnessPage::_BuildConfigURI($pageKey, null, null, TRUE));
  }


  function __construct()
  {
    parent::__construct();
   
    Events::RegisterEventHook('harness', 'spec-resync', 'python/SynchronizeSpecLinks.py');
    Events::RegisterEventHook('harness', 'spec-rename', 'python/SpecificationRenamed.py');
    Events::RegisterEventHook('harness', 'spec-delete', 'python/SpecificationDeleted.py');
    
    Events::RegisterEventHook('harness', 'suite-rename', 'python/TestSuiteRenamed.py');
    Events::RegisterEventHook('harness', 'suite-delete', 'python/TestSuiteDeleted.py');
    Events::RegisterEventHook('harness', 'suite-resync', 'python/SynchronizeSpecLinks.py');
    
  }


  function constructURIConverter()
  {
    return new HarnessURIConverter();
  }
 

  function addStyleSheetsTo(SystemPage $page)
  {
    parent::addStyleSheetsTo($page);
    
//    $page->addStyleSheetLink($page->buildConfigURI('stylesheet.harness'));
  }


  function getDatabases()
  {
    $databases = parent::getDatabases();
    
    $databases[] = new HarnessDBConnection();

    return $databases;
  }

  static function GetShepherdURI(Array $args = null)
  {
    $uri = Config::Get('system', 'shepherd');
    if ($uri) {
      if ($args && array_key_exists('testcase', $args)) {
        $uri = static::_CombinePath($uri, 'testcase');
        if (array_key_exists('repo', $args)) {
          $uri = static::_CombinePath($uri, $args['repo'] . '::');
        }
        $uri = static::_CombinePath($uri, $args['testcase'] . '/');
      }
      
      return $uri;
    }
    return null;
  }
}

?>