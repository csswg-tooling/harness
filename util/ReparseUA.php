<?php
/*******************************************************************************
 *
 *  Copyright © 2010-2011 Hewlett-Packard Development Company, L.P. 
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


/**
 * This class reparses the useragent string and updates the browser and 
 * engine data. Used when the useragent parsing algorithm changes.
 */
class ReparseUA extends DBConnection
{  
  function __construct() 
  {
    parent::__construct();

  }
  
  function reparse() 
  {

    $sql = "SELECT `id` FROM `useragents` ";
    $r = $this->query($sql);

    while ($dbData = $r->fetchRow()) {
      $uaId = $dbData['id'];
      
      $ua = new UserAgent(intval($uaId));
      
      $oldDescription = $ua->getDescription();
      $ua->reparse();
      $newDescription = $ua->getDescription();
      
      if ($oldDescription != $newDescription) {
        echo "{$uaId}: {$ua->getUAString()}\n";
        echo "  was: {$oldDescription}\n";
        echo "  now: {$newDescription}\n";
      }
    }
  }
}

$worker = new ReparseUA();
$worker->reparse();

?>