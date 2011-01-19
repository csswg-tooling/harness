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
    $this->mPageQuery = $_SERVER['QUERY_STRING'];
    $this->mPageURI = $_SERVER['REQUEST_URI'];
  }
  

  function getTrapLink()
  {
    $largeNumber = mt_rand(1000000, 9999999999);
    if ($this->mPageQuery) {
      parse_str($this->mPageQuery, $args);
      $args = DynamicPage::ConditionInput($args);
    }
    $args['seq'] = ++$this->mSequence;
    $args['uid'] = $largeNumber;
    $uri = Page::EncodeURI(SPIDER_TRAP_URI, $args);
    $link = "<a href='{$uri}' class='report'>{$this->mSequence}-{$largeNumber}</a>";
    return $link;
  }


  /**
   * Write HTML for a link to the spider trap
   */
  function writeTrapLink($indent = '')
  {
    $link = $this->getTrapLink();
    echo $indent . $link . "\n";
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