<?php
/*******************************************************************************
 *
 *  Copyright © 2010 Hewlett-Packard Development Company, L.P. 
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

require_once('Config.php');
require_once('DBConnection.php');
require_once('Page.php');  

class SpiderTrap
{
  var $mDB;
  
  var $mSequence;
  var $mPageQuery;
  var $mPageURI;

  
  function __construct()
  {
    $this->mSequence = 0;
    $this->mPageQuery = $_SERVER['QUERY_STRING'];
    $this->mPageURI = $_SERVER['REQUEST_URI'];
  }
  

  function getLink()
  {
    $largeNumber = mt_rand(1000000, 9999999999);
    if ($this->mPageQuery) {
      parse_str($this->mPageQuery, $query);
      $query['seq'] = ++$this->mSequence;
      $query['uid'] = $largeNumber;
      $queryStr = http_build_query($query, 'var_');
    }
    else {
      $queryStr  = 'seq=' . ++$this->mSequence;
      $queryStr .= "&uid={$largeNumber}";
    }
    $queryStr = Page::Encode($queryStr);
    $link = "<a href='" . SPIDER_TRAP_URL . "?{$queryStr}' class='report'>{$this->mSequence}-{$largeNumber}</a>";
    return $link;
  }

  /**
   * Write HTML for a link to the spider trap
   */
  function generateLink($indent = '')
  {
    $link = $this->getLink();
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
  
  protected function _getClientIP()
  {
    if (! empty($_SERVER['REMOTE_ADDR'])) {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    return $ip;
  }
  

  
  /**
   * Capture data about a visit to the spider trap
   */
  function recordVisit()
  {
    $db = $this->_getDB();

    $ipAddress = $db->encode($this->_getClientIP(), 15);
    $userAgent = $db->encode($_SERVER['HTTP_USER_AGENT'], 255);
    $pageURI = $db->encode($this->mPageURI, 255);

    $sql  = "INSERT INTO `spidertrap` (`ip_address`, `user_agent`, `last_uri`, `visit_count`, `first_visit`) ";
    $sql .= "VALUES ('{$ipAddress}', '{$userAgent}', '{$pageURI}', '1', NOW()) ";
    $sql .= "ON DUPLICATE KEY UPDATE `user_agent` = '{$userAgent}', `last_uri` = '{$pageURI}', `visit_count` = visit_count + 1";
    
    $db->query($sql);
  }
  
}

?>