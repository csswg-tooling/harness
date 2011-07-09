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
 * Import specification sections
 */
class SectionImport extends HarnessCmdLineWorker
{
  protected $mSectionURIIds;
  protected $mSectionIds;
  protected $mSectionParentIds;
  protected $mSectionURIs;
  protected $mSectionTitles;


  function __construct() 
  {
    parent::__construct();
  }
  

  function usage()
  {
    echo "USAGE: php SectionImport.php manifestFile spec\n";
  }


  protected function _getSpecURI($spec)
  {
    $spec = $this->encode($spec, 'specifications.spec');
    
    $sql  = "SELECT `base_uri` ";
    $sql .= "FROM `specifications` ";
    $sql .= "WHERE `spec` = '{$spec}' ";

    $r = $this->query($sql);
    $specURI = $r->fetchField(0);
    
    return $specURI;
  }
  
  
  protected function _loadSections($spec)
  {
    $this->mSectionURIIds = array();
    $this->mSectionIds = array();
    $this->mSectionParentIds = array();
    $this->mSectionURIs = array();
    $this->mSectionTitles = array();
    
    $spec = $this->encode($spec, 'sections.spec');
    
    $sql  = "SELECT * ";
    $sql .= "FROM `sections` ";
    $sql .= "WHERE `spec` = '{$spec}' ";
    $r = $this->query($sql);

    while ($sectionData = $r->fetchRow()) {
      $sectionId  = intval($sectionData['id']);
      $parentId   = intval($sectionData['parent_id']);
      $section    = $sectionData['section'];
      $title      = $sectionData['title'];
      $uri        = $sectionData['uri'];
      
      $this->_addSection($sectionId, $parentId, $section, $title, $uri);
    }
  }
  
  
  protected function _getSectionURIId($specURI)
  {
    if (array_key_exists($specURI, $this->mSectionURIIds)) {
      return $this->mSectionURIIds[$specURI];
    }
    return FALSE;
  }
  
  protected function _getSectionParentId($sectionId)
  {
    if (array_key_exists($sectionId, $this->mSectionParentIds)) {
      return $this->mSectionParentIds[$sectionId];
    }
    return FALSE;
  }
  
  protected function _getSectionId($section)
  {
    if (array_key_exists($section, $this->mSectionIds)) {
      return $this->mSectionIds[$section];
    }
    return FALSE;
  }
  
  protected function _getSectionTitle($sectionId)
  {
    if (array_key_exists($sectionId, $this->mSectionTitles)) {
      return $this->mSectionTitles[$sectionId];
    }
    return FALSE;
  }
  
  
  protected function _addSection($sectionId, $parentId, $section, $title, $uri)
  {
    $this->mSectionURIIds[$uri] = $sectionId;
    $this->mSectionIds[$section] = $sectionId;
    $this->mSectionParentIds[$sectionId] = $parentId;
    $this->mSectionTitles[$sectionId] = $title;
    $this->mSectionURIs[$sectionId] = $uri;
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
    
    $this->_loadSections($spec);

    $spec = $this->encode($spec, 'sections.spec');
    
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

      $sectionId = $this->_getSectionURIId($uri);

      $parentSection = $this->_getParentSection($section);
      if ($parentSection) {
        $parentId = $this->_getSectionId($parentSection);
        if (! $parentId) {
          echo "ERROR: Unknown parent section '{$parentSection}' for '{$section}'\n";
          exit;
        }
      }
      else {
        $parentId = 0;
      }
      
      if ($sectionId) {
        if ($parentId != $this->_getSectionParentId($sectionId)) {
          echo "ERROR: Spec link parent id changed, need to remap test links\n";
          exit;
        }
        if ($sectionId != $this->_getSectionId($section)) {
          echo "ERROR: Spec link section id changed, need to remap test links\n";
          exit;
        }
        
        if ($title != $this->_getSectionTitle($sectionId)) {
          echo "Updated section {$section}: '{$title}'\n";

          $title = $this->encode($title, 'sections.title');

          $sql  = "UPDATE `sections` ";
          $sql .= "SET `title` = '{$title}' ";
          $sql .= "WHERE `id` = '{$sectionId}' ";
          $this->query($sql);
        }
      }
      else {
        $section  = $this->encode($section, 'sections.section');
        $title    = $this->encode($title, 'sections.title');
        $uri      = $this->encode($uri, 'sections.uri');
        
        $sql  = "INSERT INTO `sections` ";
        $sql .= "(`parent_id`, `spec`, `section`, `title`, `uri`) ";
        $sql .= "VALUES ('{$parentId}', '{$spec}', '{$section}', '{$title}', '{$uri}')";
        $this->query($sql);
        
        $sectionId = $this->lastInsertId();
        
        $this->_addSection($sectionId, $parentId, $section, $title, $uri);
        
        echo "Added section {$section}: {$uri}\n";
      }
    }
  }
}

$worker = new SectionImport();

$manifestPath = $worker->_getArg(1);
$specName     = $worker->_getArg(2);

if ($manifestPath && $specName) {
  $worker->import($manifestPath, $specName);
}
else {
  $worker->usage();
}

?>