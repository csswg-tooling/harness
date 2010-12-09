<?php
  ////////////////////////////////////////////////////////////////////////////////
  //
  //  Copyright © 2010 World Wide Web Consortium, 
  //  (Massachusetts Institute of Technology, European Research 
  //  Consortium for Informatics and Mathematics, Keio 
  //  University). All Rights Reserved. 
  //  Copyright © 2010 Hewlett-Packard Development Company, L.P. 
  // 
  //  This work is distributed under the W3C¬ Software License 
  //  [1] in the hope that it will be useful, but WITHOUT ANY 
  //  WARRANTY; without even the implied warranty of 
  //  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
  // 
  //  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
  //
  //////////////////////////////////////////////////////////////////////////////// 
  
  //////////////////////////////////////////////////////////////////////////////// 
  //
  //  process_honey.php
  //
  //////////////////////////////////////////////////////////////////////////////// 
  
  require_once("lib_test_harness/class.db_connection.phi");
  
  ////////////////////////////////////////////////////////////////////////////////
  //
  //  class process_honey
  //
  //  This class checks the honeypot and bans offenders at the firewall
  //  Bans are released after 7 days
  //
  //  This is meant to be run from by a periodic cron job 
  //
  ////////////////////////////////////////////////////////////////////////////////
  class process_honey extends db_connection
  {  
    ////////////////////////////////////////////////////////////////////////////
    //
    //  Instance variables.
    //
    ////////////////////////////////////////////////////////////////////////////
    var $m_offenders;
    var $m_banned;
    
    var $m_ban_threshold;
    var $m_test_period;
    var $m_ban_period;
    
    ////////////////////////////////////////////////////////////////////////////
    //
    //  Constructor.
    //
    ////////////////////////////////////////////////////////////////////////////
    function process_honey() 
    {
      parent::db_connection();

      $this->m_ban_threshold  = 2;
      $this->m_test_period    = 3;
      $this->m_ban_period     = 7;
      
      // remove suspects under trigger count for test period
      $sql  = "DELETE FROM `honeypot` ";
      $sql .= "WHERE `banned` = 0 AND ";
      $sql .= "0 < `visit_count` AND `visit_count` < {$this->m_ban_threshold} AND ";
      $sql .= "`last_action` < DATE_SUB(NOW(), INTERVAL {$this->m_test_period} DAY)";
      $r = $this->query($sql);
      
      // find offenders needing to be banned
      $sql  = "SELECT `ip_address` FROM `honeypot` ";
      $sql .= "WHERE `banned` = 0 AND {$this->m_ban_threshold} <= `visit_count` ";
      $r = $this->query($sql);
      if (! $r->is_false()) {
        $this->m_offenders = $r->fetch_table();
      }
      
      // find banned offenders due for release
      $sql  = "SELECT `ip_address` FROM `honeypot` ";
      $sql .= "WHERE `banned` = 1 AND ";
      $sql .= "`last_action` < DATE_SUB(NOW(), INTERVAL {$this->m_ban_period} DAY)";
      $r = $this->query($sql);
      if (! $r->is_false()) {
        $this->m_banned = $r->fetch_table();
      }
    }
    
    function process()
    {
      foreach ($this->m_offenders as $offender) {
        $ip_address = $offender['ip_address'];
        if (($ip_address != '::1') && ($ip_address != '127.0.0.1')) {
          $command = "/sbin/iptables -I INPUT -s {$ip_address} -j DROP";
          exec($command);
          $sql = "UPDATE `honeypot` SET `banned` = 1 WHERE `ip_address` = '{$ip_address}'";
          $this->query($sql);
        }
      }

      foreach ($this->m_banned as $banned) {
        $ip_address = $banned['ip_address'];
        if (($ip_address != '::1') && ($ip_address != '127.0.0.1')) {
          $command = "/sbin/iptables --delete INPUT -s {$ip_address} -j DROP";
          exec($command);
          $sql = "UPDATE `honeypot` SET `banned` = 0, `visit_count` = 0 WHERE `ip_address` = '{$ip_address}'";
          $this->query($sql);
        }
      }
    }
  }
  
  $worker = new process_honey();
  $worker->process();
  
?>