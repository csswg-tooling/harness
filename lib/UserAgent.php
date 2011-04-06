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

require_once("lib/DBConnection.php");

/**
 * Wrapper class for information about a particular User Agent 
 */
class UserAgent extends DBConnection
{
  protected $mInfo;
  protected $mActualUA;


  /**
   * Load array of all known user agents
   */
  static function GetAllUserAgents()
  {
    $userAgents = array();
    
    $sql  = "SELECT * ";
    $sql .= "FROM `useragents` ";
    $sql .= "ORDER BY `useragent` ";

    $db = new DBConnection();
    $r = $db->query($sql);

    while ($data = $r->fetchRow()) {
      $id = $data['id'];
      $userAgents[$id] = new UserAgent($data);
    }
    return $userAgents;
  }
  
  
  /**
   * Sorting callback functions
   */
  static function CompareUAString(UserAgent $a, UserAgent $b)
  {
    return strnatcasecmp($a->getUAString(), $b->getUAString());
  }

  static function CompareEngine(UserAgent $a, UserAgent $b)
  {
    return strnatcasecmp($a->getEngine() . $a->getEngineVersion(), $b->getEngine() . $b->getEngineVersion());
  }

  static function CompareBrowser(UserAgent $a, UserAgent $b)
  {
    return strnatcasecmp($a->getBrowser() . $a->getBrowserVersion(), $b->getBrowser() . $b->getBrowserVersion());
  }

  static function CompareDescription(UserAgent $a, UserAgent $b)
  {
    return strnatcasecmp($a->getDescription(), $b->getDescription());
  }


  /**
   * @param int,string,array
   */
  function __construct($data = FALSE) 
  {
    parent::__construct();
    
    if ($data) {
      if (is_integer($data)) {
        $this->mInfo = $this->_queryById($data);
      }
      elseif (is_string($data)) {
        $this->mInfo = $this->_queryByString($data);
        
        if (! $this->mInfo) {
          $this->mInfo = $this->_parseUAString($data);
        }
      }
      elseif (is_array($data)) {
        $this->mInfo = $data;
      }
    }
    
    if (isset($this->mInfo)) {  // passed a valid UA id or string
      if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
        $uaString = $_SERVER['HTTP_USER_AGENT'];
        if ($uaString != $this->getUAString()) {  // and it's not the actual UA
          $this->mActualUA = new UserAgent(); // capture actual UA info
        }
      }
    }
    else {  // determine UA from server
      if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
        $uaString = $_SERVER['HTTP_USER_AGENT'];
        $this->mInfo = $this->_queryByString($uaString);
        
        if (! isset($this->mInfo)) {
          $this->mInfo = $this->_parseUAString($uaString);
        }
      }
    }
  }


  /**
   * Lookup UA info by numeric ID
   *
   * @param int $id database ID
   * @return array
   */
  protected function _queryById($id) 
  {
    $id = intval($id);
    $sql  = "SELECT * ";
    $sql .= "FROM `useragents` ";
    $sql .= "WHERE id = '{$id}' ";
    $sql .= "LIMIT 1";

    $r = $this->query($sql);

    if ($r->succeeded()) {
      $data = $r->fetchRow();
      if ($data) {
        return $data;
      }
    }
    return null;
  }


  /**
   * Lookup UA info by full User Agent string
   *
   * @param string $uaString User Agent String
   * @return array
   */
  protected function _queryByString($uaString) 
  {
    $sql  = "SELECT * ";
    $sql .= "FROM `useragents` ";
    $sql .= "WHERE `useragent` = '" . $this->encode($uaString, USERAGENTS_MAX_USERAGENT) . "' ";
    $sql .= "LIMIT 1 ";

    $r = $this->query($sql);

    if ($r->succeeded()) {
      $data = $r->fetchRow();
      if ($data) {
        return $data;
      }
    }
    return null;
  }


  /**
   * Break product and version info into parts
   *
   * @param string $product
   * @return string|array
   */
  protected function _splitUAProductVersion($product)
  {
    if (('(' == $product[0]) && (')' == substr($product, -1))) {
      return substr($product, 1, strlen($product) - 2);
    }
    $slash = strpos($product, '/');
    if ((FALSE === $slash) || (($slash + 1) == strlen($product))) {
      return $product;
    }
    $version = substr($product, $slash + 1);
    $product = substr($product, 0, $slash);
    return compact("product", "version");
  }
  
  /**
   * Break UA comment into sub strings
   *
   * @param string $uaComment comment section of UA string
   *   without enclosing '(' ')'
   * @return string|array
   */
  protected function _explodeUAComment($uaComment)
  {
    if (FALSE === strpos($uaComment, ';')) {
      return $uaComment;
    }
    return preg_split('/;(\s)+/', $uaComment);
  }

  /**
   * Helper function to break User Agent string into component parts
   *
   * Elements in returned array will be either strings or array
   * Array component is pair of either 'product' and 'comment'
   * or 'product' and 'version'.
   *
   * In the product/comment pair, the product may be a string or
   * product/version array. The comment may be a string or array 
   * of strings.
   * 
   * @param string $uaString User Agent string as reported by UA
   * @return array
   */
  protected function _explodeUAString($uaString)
  {
    $uaString = str_replace(') (', '; ', $uaString);  // collapse sequential comments (from Uzbl browser)
    
    $index = -1;
    $count = strlen($uaString);
    
    $result = array();
    
    $start = 0;
    while (++$index < $count) {
      if (' ' == $uaString[$index]) {
        $product = $this->_splitUAProductVersion(substr($uaString, $start, $index - $start));
        while ((++$index < $count) && (' ' == $uaString[$index])) ;
        $start = $index;
        $comment = '';
        if (($index < $count) && ('(' == $uaString[$index])) { // grab comment
          $start++;
          $level = 0;
          while (++$index < $count) {
            if ('(' == $uaString[$index]) {
              $level++;
            }
            if (')' == $uaString[$index]) {
              if (0 == $level) {
                break;
              }
              $level--;
            }
          }
          $comment = substr($uaString, $start, $index - $start);
          while ((++$index < $count) && (' ' == $uaString[$index])) ;
          $start = $index;
          $index--;
        }
        if ('' == $comment) {
          $result[] = $product;
        }
        else {
          $comment = $this->_explodeUAComment($comment);
          $result[] = compact("product", "comment");
        }
      }
    }
    if ($start < $index) {
      $result[] = $this->_splitUAProductVersion(substr($uaString, $start, $index - $start));
    }
    return $result;
  }
  
  protected function _parsePlatform($platformString, &$platform, &$platformVersion)
  {
    if ((0 === stripos($platformString, 'Linux')) || 
        (0 === stripos($platformString, 'Unix')) ||
        (0 === stripos($platformString, 'Android')) ||
        (0 === stripos($platformString, 'Windows')) ||
        (0 === stripos($platformString, 'Macintosh')) ||
        (0 === stripos($platformString, 'Chromium')) ||
        (0 === stripos($platformString, 'OpenBSD')) ||
        (0 === stripos($platformString, 'FreeBSD')) ||
        (0 === stripos($platformString, 'WebOS'))) {
      $spaceIndex = strpos($platformString, ' ');
      if (FALSE === $spaceIndex) {
        $platform = $platformString;
      }
      else {
        $platform = substr($platformString, 0, $spaceIndex);
        $platformVersion = substr($platformString, $spaceIndex + 1);
      }
    }
    if ((FALSE !== stripos($platformString, 'Mac OS X')) && ('iOS' != $platform)) {
      $platform = 'Mac OS X';
      $spaceIndex = strpos($platformString, ' ');
      $platformVersion = substr($platformString, 0, $spaceIndex) . substr($platformString, $spaceIndex + 9);
      $platformVersion = str_replace('_', '.', $platformVersion);
    }
    if ((0 === stripos($platformString, 'iPad')) ||
        (0 === stripos($platformString, 'iPhone')) ||
        (0 === stripos($platformString, 'iPod'))) {
      $platform = 'iOS';
    }
    if (FALSE !== stripos($platformString, ' like Mac OS X')) {
      $osIndex = stripos($platformString, 'OS ');
      if (FALSE !== $osIndex) {
        $platformVersion = substr($platformString, $osIndex + 3, -14);
        $platformVersion = str_replace('_', '.', $platformVersion);
      }
    }
  }
  
  /**
   * Determine Browser, Browser Version, Engine, Engine Version and Platform
   * from User Agent string
   */
  protected function _parseUAString($uaString) 
  {
    $result['id'] = null;
    $result['useragent'] = $uaString;
    
    $uaData = $this->_explodeUAString($uaString);
    
    $browser = '';
    $browserVersion = '';
    $engine = '';
    $engineVersion = '';
    $platform = '';
    
    // find browser
    $product = $uaData[0];
    $version = '';
    $comment = '';
    if (is_array($product)) {
      extract($product);
      if (is_array($product)) {
        extract($product);
      }
    }
    if ('mozilla' == strtolower($product)) {    // we have to go looking... search comments
      if (is_array($comment)) {
        foreach ($comment as $commentChunk) {
          if (0 === stripos($commentChunk, 'MSIE')) {
            $browser = 'Internet Explorer';
            $browserVersion = substr($commentChunk, 5);
            $engine = 'Trident';
          }
          if (0 === stripos($commentChunk, 'Trident')) {
            $product = $this->_splitUAProductVersion($commentChunk);
            if (is_array($product)) {
              extract($product);
              $engineVersion = $version;
            }
            $engine = $product;
          }
          if (0 === stripos($commentChunk, 'Konqueror')) {
            $product = $this->_splitUAProductVersion($commentChunk);
            if (is_array($product)) {
              extract($product);
              $browserVersion = $version;
            }
            $browser = $product;
          }
          if (0 === stripos($commentChunk, 'Googlebot')) {
            $product = $this->_splitUAProductVersion($commentChunk);
            if (is_array($product)) {
              extract($product);
              $browserVersion = $version;
            }
            $browser = $product;
          }
        }
      }
    }
    else {
      $browser = $product;
      $browserVersion = $version;
    }
    
    // find platform in initial comment
    if (is_array($comment)) {
      foreach ($comment as $commentChunk) {
        $this->_parsePlatform($commentChunk, $platform, $platformVersion);
      }
    }
    else {
      $this->_parsePlatform($comment, $platform, $platformVersion);
    }
    
    // find engine and possibly browser
    $mobile = FALSE;
    foreach ($uaData as $uaChunk) {
      $comment = '';
      $version = '';
      if (is_array($uaChunk)) {
        extract($uaChunk);
        if (is_array($product)) {
          extract($product);
        }
      }
      else {
        $product = $uaChunk;
      }
      if (0 === stripos($product, 'Mobile')) {
        $mobile = TRUE;
      }
      if ((0 === stripos($product, 'Amaya')) ||
          (0 === stripos($product, 'AppleWebKit')) ||
          (0 === stripos($product, 'WebKit')) ||
          (0 === stripos($product, 'Gecko')) ||
          (0 === stripos($product, 'KHTML')) ||
          (0 === stripos($product, 'Presto')) ||
          (0 === stripos($product, 'Prince')) ||
          (0 === stripos($product, 'Trident')) ||
          (0 === stripos($product, 'WebToPDF'))) {
        $engine = $product;
        $engineVersion = $version;
      }
      if ('' == $browser) {
        if ((0 === stripos($product, 'Firefox')) ||
            (0 === stripos($product, 'Chrome')) ||
            (0 === stripos($product, 'rekonq'))) {
          $browser = $product;
          $browserVersion = $version;
        }
      }
      if ('Internet Explorer' == $browser) {  // browsers that pretend to be IE
        if (0 === stripos($product, 'TallComponents')) {
          $engine = 'WebToPDF';
          $browser = 'WebToPDF.NET';
          $browserVersion = $version;
          $platform = 'Windows';
        }
      }
      if (0 === stripos($product, 'Version')) {
        $browserVersion = $version;
      }
      if ((0 === stripos($product, 'Midori')) ||
          (0 === stripos($product, 'Iceweasel')) ||
          (0 === stripos($product, 'WebToPDF.NET'))) {
        $browser = $product;
        $browserVersion = $version;
      }
    }
    
    // no browser yet, take last product
    if ('' == $browser) {
      if ('Mobile' == $product) {
        $product = 'Safari';
      }
      if ($mobile) {
        $browser = 'Mobile ';
      }
      $browser .= $product;
    }
    if ('' == $browserVersion) {
      $browserVersion = $version;
    }
    
    // general cleanups
    if ('' == $engine) {
      if (0 === stripos($browser, 'Firefox')) {
        $engine = 'Gecko';
      }
      if (0 === stripos($browser, 'Uzbl')) {
        $engine = 'WebKit';
      }
    }
    if ((0 === stripos($engine, 'AppleWebKit')) ||
        (0 === stripos($engine, 'KHTML')) ||        // Not enough difference between KHTML and WebKit, aggregate results
        (0 === stripos($engine, 'WebKitGTK'))) {
      $engine = 'WebKit';
    }
    if (0 === stripos($engine, 'WebToPDF')) {
      $engine = 'WebToPDF';
    }
    if (0 === stripos($browser, 'Firefox')) {
      $browser = 'Firefox';
    }

    if (! $engine) {
      $engine = $browser;
      $engineVersion = $browserVersion;
    }
    
    $result['engine'] = $engine;
    $result['engine_version'] = $engineVersion;
    $result['browser'] = $browser;
    $result['browser_version'] = $browserVersion;
    $result['platform'] = $platform;
    $result['platform_version'] = $platformVersion;
    return $result;
  }

  /**
   * Write info into database if not loaded from there
   */
  function update()
  {
    if ((! isset($this->mInfo['id'])) && (isset($this->mInfo['useragent']))) {
      $sql  = "INSERT INTO `useragents` ";
      $sql .= "(`useragent`, `engine`, `engine_version`, `browser`, `browser_version`, `platform`, `platform_version`) ";
      $sql .= "VALUES (";
      $sql .= "'" . $this->encode($this->mInfo['useragent'], USERAGENTS_MAX_USERAGENT) . "',";
      $sql .= "'" . $this->encode($this->mInfo['engine'], USERAGENTS_MAX_ENGINE) . "',";
      $sql .= "'" . $this->encode($this->mInfo['engine_version'], USERAGENTS_MAX_ENGINE_VERSION) . "',";
      $sql .= "'" . $this->encode($this->mInfo['browser'], USERAGENTS_MAX_BROWSER) . "',";
      $sql .= "'" . $this->encode($this->mInfo['browser_version'], USERAGENTS_MAX_BROWSER_VERSION) . "',";
      $sql .= "'" . $this->encode($this->mInfo['platform'], USERAGENTS_MAX_PLATFORM) . "'";
      $sql .= "'" . $this->encode($this->mInfo['platform_version'], USERAGENTS_MAX_PLATFORM_VERSION) . "'";
      $sql .= ")";
      $r = $this->query($sql);
      $this->mInfo['id'] = $this->lastInsertId();
    }
  }
  

  /**
   * Reset info about the user agent in the database
   *
   * Useful when the parsing algorithm has been updated
   */
  function reparse()
  {
    $id = $this->getId();
    if (0 < $id) {
      $ua = $this->_parseUAString($this->getUAString());
      $ua['id'] = $id;

      $sql  = "UPDATE `useragents` SET ";
      $sql .= "`engine` = '" . $this->encode($ua['engine'], USERAGENTS_MAX_ENGINE) . "', ";
      $sql .= "`engine_version` = '" . $this->encode($ua['engine_version'], USERAGENTS_MAX_ENGINE_VERSION) . "', ";
      $sql .= "`browser` = '" . $this->encode($ua['browser'], USERAGENTS_MAX_BROWSER) . "', ";
      $sql .= "`browser_version` = '" . $this->encode($ua['browser_version'], USERAGENTS_MAX_BROWSER_VERSION) . "', ";
      $sql .= "`platform` = '" . $this->encode($ua['platform'], USERAGENTS_MAX_PLATFORM) . "', ";
      $sql .= "`platform_version` = '" . $this->encode($ua['platform_version'], USERAGENTS_MAX_PLATFORM_VERSION) . "' ";
      $sql .= "WHERE `id` = '{$id}' ";
      $r = $this->query($sql);

      $this->mInfo = $ua;
    }
  }
  

  /**
   * Get human readable description of the user agent
   */
  function getDescription()
  {
    if ($this->getBrowser()) {
      $description = $this->getBrowser();
      
      if ($this->getBrowserVersion()) {
        $description .= ' ' . $this->getBrowserVersion();
      }
      
      if ($this->getEngine() && ($this->getEngine() != $this->getBrowser())) {
        $version = '';
        if ($this->getEngineVersion()) {
          $version .= ' ' . $this->getEngineVersion();
        }
        $description .= " ({$this->getEngine()}{$version})";
      }

      if ($this->getPlatform()) {
        $description .= ' on ' . $this->getPlatform();
        if ($this->getPlatformVersion()) {
          $description .= ' ' . $this->getPlatformVersion();
        }
      }
    }
    else {
      $description = 'unknown';
    }
    
    return $description;
  }


  function getId()
  {
    return intval($this->mInfo['id']);
  }

  function getUAString()
  {
    return $this->mInfo['useragent'];
  }

  function getEngine()
  {
    return $this->mInfo['engine'];
  }

  function getEngineVersion()
  {
    return $this->mInfo['engine_version'];
  }

  function getBrowser()
  {
    return $this->mInfo['browser'];
  }

  function getBrowserVersion()
  {
    return $this->mInfo['browser_version'];
  }

  function getPlatform()
  {
    return $this->mInfo['platform'];
  }
  
  function getPlatformVersion()
  {
    return $this->mInfo['platform_version'];
  }
  
  function isActualUA()
  {
    return (null == $this->mActualUA);
  }
  
  function getActualUA()
  {
    if ($this->mActualUA) {
      return $this->mActualUA;
    }
    return $this;
  }

}

?>