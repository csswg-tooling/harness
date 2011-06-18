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
 ******************************************************************************/
 

require_once('lib/HarnessCmdLineWorker.php');
require_once('core/IPAddress.php');


/**
 * Migrate users from sources table to user table
 */
class MigrateUsers extends HarnessCmdLineWorker
{
  function __construct() 
  {
    parent::__construct();
  }
  

  function migrate()
  {
    echo "Migrating Users\n";
    
    $fromDB = new DBConnection();
    $toDB = new DBConnection('users');

    $sql  = "SELECT * ";
    $sql .= "FROM `sources` ";

    $r = $fromDB->query($sql);
    
    while ($sourceData = $r->fetchRow()) {
      $sourceId = intval($sourceData['id']);
      $source = $sourceData['source'];
      
      $ipAddress = new IPAddress($source);
      if ($ipAddress->isValid()) {
        $ipString = $toDB->encode($ipAddress->getIPv6String(), 'users.ip_address');
        
        $sql  = "INSERT INTO `users` ";
        $sql .= "(`ip_address`) ";
        $sql .= "VALUES ('{$ipAddress->getIPv6String()}') ";
      }
      else {
        $userName = $toDB->encode(strtolower($source), 'users.user');
        $fullName = $toDB->encode($source, 'users.full_name');
        
        $sql  = "INSERT INTO `users` ";
        $sql .= "(`user`, `full_name`) ";
        $sql .= "VALUES ('{$userName}', '{$fullName}') ";
      }
      $toDB->query($sql);
      $userIdMap[$sourceId] = $toDB->lastInsertId();
    }

    echo "Updating results\n";
    
    $sql  = "SELECT `id`, `source_id` ";
    $sql .= "FROM `results` ";
    
    $r = $fromDB->query($sql);
    
    while ($resultData = $r->fetchRow()) {
      $resultId = intval($resultData['id']);
      $oldSourceId = intval($resultData['source_id']);
      
      if (0 < $oldSourceId) {
        $newSourceId = $userIdMap[$oldSourceId];
        
        $sql  = "UPDATE `results` ";
        $sql .= "SET `source_id` = {$newSourceId}, `modified`=`modified` ";
        $sql .= "WHERE `id` = {$resultId} ";
        $fromDB->query($sql);
      }
    }
  }
}

$worker = new MigrateUsers();

$worker->migrate();

?>