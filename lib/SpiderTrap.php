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

require_once('lib/Config.php');
require_once('lib/DBConnection.php');
require_once('lib/DynamicPage.php');  

class SpiderTrap
{
  protected $mDB;
  
  protected $mSequence;
  protected $mPageQuery;
  protected $mPageURI;

  
  function __construct()
  {
    $this->mSequence = 0;
    if (array_key_exists('QUERY_STRING', $_SERVER)) {
      $this->mPageQuery = $_SERVER['QUERY_STRING'];
    }
    if (array_key_exists('REQUEST_URI', $_SERVER)) {
      $this->mPageURI = $_SERVER['REQUEST_URI'];
    }
  }
  

  /**
   * Add spider trap link to page
   *
   * @param Page web page
   */
  function addTrapLinkTo(Page $page)
  {
    $largeNumber = mt_rand(1000000, 9999999999);
    if ($this->mPageQuery) {
      parse_str($this->mPageQuery, $args);
      $args = DynamicPage::ConditionInput($args);
    }
    $args['seq'] = ++$this->mSequence;
    $args['uid'] = $largeNumber;
    $uri = $page->_buildURI(SPIDER_TRAP_URI, $args);
    
    $content = "{$this->mSequence}-{$largeNumber}";
    $page->addHyperLink($uri, array('class' => 'report'), $content);
  }


  /**
   * Bring DBConnection online lazily
   */
  protected function _getDB()
  {
    if (! isset($this->mDB)) {
      $this->mDB = new DBConnection();
    }
    return $this->mDB;
  }
  
  
  /**
   * Capture data about a visit to the spider trap
   */
  function recordVisit()
  {
    $db = $this->_getDB();

    $ipAddress = $db->encode(Page::GetClientIP(), SPIDERTRAP_MAX_IP);
    $userAgent = $db->encode($_SERVER['HTTP_USER_AGENT'], SPIDERTRAP_MAX_USER_AGENT);
    $pageURI = $db->encode($this->mPageURI, SPIDERTRAP_MAX_URI);

    $sql  = "INSERT INTO `spidertrap` (`ip_address`, `user_agent`, `last_uri`, `visit_count`, `first_visit`) ";
    $sql .= "VALUES ('{$ipAddress}', '{$userAgent}', '{$pageURI}', '1', NOW()) ";
    $sql .= "ON DUPLICATE KEY UPDATE `user_agent` = '{$userAgent}', `last_uri` = '{$pageURI}', `visit_count` = visit_count + 1";
    
    $db->query($sql);
  }
  
}

?>