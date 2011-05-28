<?php
/*******************************************************************************
 *
 *  Copyright © 2011 Hewlett-Packard Development Company, L.P. 
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
 

require_once('lib/HarnessCmdLineWorker.php');


/**
 * Import specification links
 */
class SpecLinkImport extends HarnessCmdLineWorker
{
  protected $mSpecLinkURIIds;
  protected $mSpecLinkSectionIds;
  protected $mSpecLinkParentIds;
  protected $mSpecLinkURIs;
  protected $mSpecLinkTitles;


  function __construct() 
  {
    parent::__construct();
  }
  

  function usage()
  {
    echo "USAGE: php SpecLinkImport.php manifestFile spec\n";
  }


  protected function _getSpecURI($spec)
  {
    $spec = $this->encode($spec, SPECIFICATIONS_MAX_SPEC);
    
    $sql  = "SELECT `base_uri` ";
    $sql .= "FROM `specifications` ";
    $sql .= "WHERE `spec` = '{$spec}' ";

    $r = $this->query($sql);
    $specURI = $r->fetchField(0, 'base_uri');
    
    return $specURI;
  }
  
  
  protected function _loadSpecLinks($spec)
  {
    $this->mSpecLinkURIIds = array();
    $this->mSpecLinkSectionIds = array();
    $this->mSpecLinkParentIds = array();
    $this->mSpecLinkURIs = array();
    $this->mSpecLinkTitles = array();
    
    $spec = $this->encode($spec, SPECLINKS_MAX_SPEC);
    
    $sql  = "SELECT * ";
    $sql .= "FROM `speclinks` ";
    $sql .= "WHERE `spec` = '{$spec}' ";
    $r = $this->query($sql);

    while ($specLinkData = $r->fetchRow()) {
      $specLinkId = intval($specLinkData['id']);
      $parentId   = intval($specLinkData['parent_id']);
      $section    = $specLinkData['section'];
      $title      = $specLinkData['title'];
      $uri        = $specLinkData['uri'];
      
      $this->_addSpecLink($specLinkId, $parentId, $section, $title, $uri);
    }
  }
  
  
  protected function _getSpecLinkId($specURI)
  {
    if (array_key_exists($specURI, $this->mSpecLinkURIIds)) {
      return $this->mSpecLinkURIIds[$specURI];
    }
    return FALSE;
  }
  
  protected function _getSpecLinkParentId($specLinkId)
  {
    if (array_key_exists($specLinkId, $this->mSpecLinkParentIds)) {
      return $this->mSpecLinkParentIds[$specLinkId];
    }
    return FALSE;
  }
  
  protected function _getSpecLinkSectionId($section)
  {
    if (array_key_exists($section, $this->mSpecLinkSectionIds)) {
      return $this->mSpecLinkSectionIds[$section];
    }
    return FALSE;
  }
  
  protected function _getSpecLinkTitle($specLinkId)
  {
    if (array_key_exists($specLinkId, $this->mSpecLinkTitles)) {
      return $this->mSpecLinkTitles[$specLinkId];
    }
    return FALSE;
  }
  
  
  protected function _addSpecLink($specLinkId, $parentId, $section, $title, $uri)
  {
    $this->mSpecLinkURIIds[$uri] = $specLinkId;
    $this->mSpecLinkSectionIds[$section] = $specLinkId;
    $this->mSpecLinkParentIds[$specLinkId] = $parentId;
    $this->mSpecLinkTitles[$specLinkId] = $title;
    $this->mSpecLinkURIs[$specLinkId] = $uri;
  }
  
  
  protected function _getParentSection($section)
  {
    $sections = explode('.', $section);
    if (1 < count($sections)) {
      array_pop($sections);
      return implode('.', $sections);
    }
    return FALSE;
  }
  
  
  
  function import($manifest, $spec)
  {
    echo "Importing spec links from: '{$manifest}' for {$spec}\n";
    
    $specURI = $this->_getSpecURI($spec);
    
    if (! $specURI) {
      echo "ERROR: Unknown specification '{$spec}'\n";
      exit;
    }
    
    $this->_loadSpecLinks($spec);

    $spec = $this->encode($spec, SPECLINKS_MAX_SPEC);
    
    $data = file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($data as $record) {
      list ($uri, $section, $title) = $this->_explodeAndTrim("\t", $record, 3);
      
      if ($specURI == substr($uri, 0, strlen($specURI))) {
        $uri = substr($uri, strlen($specURI));
      }
      else {
        echo "ERROR: Spec link does not match base URI: {$uri}\n";
        exit;
      }
      
      $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');

      $specLinkId = $this->_getSpecLinkId($uri);

      $parentSection = $this->_getParentSection($section);
      if ($parentSection) {
        $parentId = $this->_getSpecLinkSectionId($parentSection);
        if (! $parentId) {
          echo "ERROR: Unknown parent section '{$parentSection}' for '{$section}'\n";
          exit;
        }
      }
      else {
        $parentId = 0;
      }
      
      if ($specLinkId) {
        if ($parentId != $this->_getSpecLinkParentId($specLinkId)) {
          echo "ERROR: Spec link parent id changed, need to remap test links\n";
          exit;
        }
        if ($specLinkId != $this->_getSpecLinkSectionId($section)) {
          echo "ERROR: Spec link section id changed, need to remap test links\n";
          exit;
        }
        
        if ($title != $this->_getSpecLinkTitle($specLinkId)) {
          echo "Updated section {$section}: '{$title}'\n";

          $title = $this->encode($title, SPECLINKS_MAX_TITLE);

          $sql  = "UPDATE `speclinks` ";
          $sql .= "SET `title` = '{$title}' ";
          $sql .= "WHERE `id` = '{$specLinkId}' ";
          $this->query($sql);
        }
      }
      else {
        $section  = $this->encode($section, SPECLINKS_MAX_SECTION);
        $title    = $this->encode($title, SPECLINKS_MAX_TITLE);
        $uri      = $this->encode($uri, SPECLINKS_MAX_URI);
        
        $sql  = "INSERT INTO `speclinks` ";
        $sql .= "(`parent_id`, `spec`, `section`, `title`, `uri`) ";
        $sql .= "VALUES ('{$parentId}', '{$spec}', '{$section}', '{$title}', '{$uri}')";
        $this->query($sql);
        
        $specLinkId = $this->lastInsertId();
        
        $this->_addSpecLink($specLinkId, $parentId, $section, $title, $uri);
        
        echo "Added section {$section}: {$uri}\n";
      }
    }
  }
}

$worker = new SpecLinkImport();

$manifestPath = $worker->_getArg(1);
$specName     = $worker->_getArg(2);

if ($manifestPath && $specName) {
  $worker->import($manifestPath, $specName);
}
else {
  $worker->usage();
}

?>