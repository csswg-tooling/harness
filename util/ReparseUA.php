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

define('COMMAND_LINE', TRUE);

  
require_once("lib/DBConnection.php");
require_once("lib/UserAgent.php");

////////////////////////////////////////////////////////////////////////////////
//
//  class reparse_ua
//
//  This class reparses the useragent string and updates the browser and 
//  engine data. Used when the useragent parsing algorithm changes.
//
////////////////////////////////////////////////////////////////////////////////
class ReparseUA extends DBConnection
{  
  function __construct() 
  {
    parent::__construct();

  }
  
  function reparse() 
  {

    $sql = "SELECT id FROM useragents";
    $r = $this->query($sql);
    while ($dbData = $r->fetchRow()) {
      $uaId = $dbData['id'];
      
      $ua = new user_agent($uaId);
// XXXX rewrite for command line      
      echo $indent . "  <tr><td>{$uaId}<td colspan='999'>" . $ua->get_ua_string();
      echo $indent . "  <tr><td>&nbsp;<td>" . $ua->get_engine();
      echo "<td>" . $ua->get_engine_version();
      echo "<td>" . $ua->get_browser();
      echo "<td>" . $ua->get_browser_version();
      echo "<td>" . $ua->get_platform();
      $ua->reparse();
      echo $indent . "  <tr><td>&nbsp;<td>" . $ua->get_engine();
      echo "<td>" . $ua->get_engine_version();
      echo "<td>" . $ua->get_browser();
      echo "<td>" . $ua->get_browser_version();
      echo "<td>" . $ua->get_platform();
    }
    echo $indent . "</table>";
    
  }
}

$worker = new ReparseUA();
$worker->reparse();

?>