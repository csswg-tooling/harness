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
    var $m_suspects;
    var $m_banned;
    
    ////////////////////////////////////////////////////////////////////////////
    //
    //  Constructor.
    //
    ////////////////////////////////////////////////////////////////////////////
    function process_honey() 
    {
      parent::db_connection();
      
      $sql  = "SELECT `ip_address` FROM `honeypot` ";
      $sql .= "WHERE `banned`=0 AND `visit_count`>1";
      $r = $this->query($sql);
      if (! $r->is_false()) {
        $this->m_suspects = $r->fetch_table();
      }
      
      $sql  = "SELECT `ip_address` FROM `honeypot` ";
      $sql .= "WHERE `banned`=1 AND `last_action`<DATE_SUB(NOW(), INTERVAL 7 DAY)";
      $r = $this->query($sql);
      if (! $r->is_false()) {
        $this->m_banned = $r->fetch_table();
      }
    }
    
    function process()
    {
      foreach ($this->m_suspects as $suspect) {
        $ip_address = $suspect['ip_address'];
        if ($ip_address != '::1') {
          $command = "iptables -I INPUT -s {$ip_address} -j DROP";
          exec($command);
          $sql = "UPDATE `honeypot` SET `banned`=1 WHERE `ip_address`='{$ip_address}'";
          $this->query($sql);
        }
      }

      foreach ($this->m_banned as $banned) {
        $ip_address = $banned['ip_address'];
        if ($ip_address != '::1') {
          $command = "iptables --delete INPUT -s {$ip_address} -j DROP";
          exec($command);
          $sql = "UPDATE `honeypot` SET banned=0, visit_count=0 WHERE `ip_address`='{$ip_address}'";
          $this->query($sql);
        }
      }
    }
  }
  
  $worker = new process_honey();
  $worker->process();
  
?>