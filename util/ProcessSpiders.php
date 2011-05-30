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

require_once('lib/CmdLineWorker.php');
  
/**
 * Utility script to check the spider trap and ban offenders at the firewall
 *
 * This is meant to be run frequently from a periodic cron job
 * Once per minute or so
 */
class ProcessSpiders extends CmdLineWorker
{
  protected $mOffenders;
  protected $mBanned;
  
  protected $mBanThreshold;
  protected $mTestPeriod;
  protected $mBanPeriod;
  

  function __construct() 
  {
    parent::__construct();

    $this->mBanThreshold  = intval(Config::Get('spider.ban_threshold'));
    $this->mTestPeriod    = intval(Config::Get('spider.test_period'));
    $this->mBanPeriod     = intval(Config::Get('spider.ban_period'));
    
    // remove suspects under trigger count for test period
    $sql  = "DELETE FROM `spidertrap` ";
    $sql .= "WHERE `banned` = 0 AND ";
    $sql .= "0 < `visit_count` AND `visit_count` < {$this->mBanThreshold} AND ";
    $sql .= "`last_action` < DATE_SUB(NOW(), INTERVAL {$this->mTestPeriod} DAY) ";
    $r = $this->query($sql);
    
    // find offenders needing to be banned
    $sql  = "SELECT `ip_address` FROM `spidertrap` ";
    $sql .= "WHERE `banned` = 0 AND {$this->mBanThreshold} <= `visit_count` ";
    $r = $this->query($sql);
    if ($r->succeeded()) {
      $this->mOffenders = $r->fetchTable();
    }
    
    // find banned offenders due for release
    $sql  = "SELECT `ip_address` FROM `spidertrap` ";
    $sql .= "WHERE `banned` = 1 AND ";
    $sql .= "`last_action` < DATE_SUB(NOW(), INTERVAL {$this->mBanPeriod} DAY) ";
    $r = $this->query($sql);
    if ($r->succeeded()) {
      $this->mBanned = $r->fetchTable();
    }
  }
  
  function process()
  {
    $changedRules = FALSE;
    
    foreach ($this->mOffenders as $offender) {
      $ipAddress = $offender['ip_address'];
      if (($ipAddress != '::1') && ($ipAddress != '127.0.0.1')) {
        $command = str_replace('{ip}', $ipAddress, Config::Get('spider.ban_command'));
        exec($command);
        $sql = "UPDATE `spidertrap` SET `banned` = 1 WHERE `ip_address` = '{$ipAddress}' ";
        $this->query($sql);
        $changedRules = TRUE;
      }
    }

    foreach ($this->mBanned as $banned) {
      $ipAddress = $banned['ip_address'];
      if (($ipAddress != '::1') && ($ipAddress != '127.0.0.1')) {
        $command = str_replace('{ip}', $ipAddress, Config::Get('spider.release_command'));
        exec($command);
        $sql = "UPDATE `spidertrap` SET `banned` = 0, `visit_count` = 0 WHERE `ip_address` = '{$ipAddress}' ";
        $this->query($sql);
        $changedRules = TRUE;
      }
    }
    
    if ($changedRules) {
      $postProcess = Config::Get('spider.post_process_command');
      if ($postProcess) {
        exec($postProcess);
      }
    }
  }
}

$worker = new ProcessSpiders();
$worker->process();
  
?>