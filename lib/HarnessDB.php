<?php
/*******************************************************************************
 *
 *  Copyright © 2014 Hewlett-Packard Development Company, L.P.
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

require_once('core/DBConnection.php');
require_once('core/DBSchema.php');
require_once('core/DBEntity.php');
require_once('core/Config.php');

require_once('modules/specification/Specification.php');
require_once('modules/specification/SpecificationAnchor.php');

require_once('modules/testsuite/TestSuite.php');
require_once('modules/testsuite/TestFormat.php');

require_once('modules/process/ShellProcess.php');

class HarnessDBSchema extends DBSchema
{
  function __construct() {

  $this->mTables['references'] = <<<'EOT'
  `testcase_id` int(11) unsigned NOT NULL,
  `revision` char(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `reference` varchar(255) NOT NULL,
  `type` enum('==','!=') NOT NULL,
  `group` int(11) unsigned NOT NULL,
  `sequence` int(11) unsigned NOT NULL,
  PRIMARY KEY (`testcase_id`, `revision`, `reference`(191), `group`)
EOT;

  $this->mTables['reference_pages'] = <<<'EOT'
  `testcase_id` int(11) unsigned NOT NULL,
  `test_suite` varchar(31) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `format` varchar(15) NOT NULL,
  `uri` varchar(255) NOT NULL,
  PRIMARY KEY (`testcase_id`, `test_suite`, `reference`(191), `format`)
EOT;

  $this->mTables['results'] = <<<'EOT'
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `testcase_id` int(11) unsigned NOT NULL,
  `revision` char(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `format` varchar(15) NOT NULL,
  `user_agent_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `user_user_agent_id` int(11) unsigned NOT NULL DEFAULT '0',
  `result` enum('pass','fail','uncertain','na','invalid') NOT NULL,
  `pass_count` int(11) unsigned DEFAULT 0,
  `fail_count` int(11) unsigned DEFAULT 0,
  `comment` varchar(63) DEFAULT NULL,
  `ignore` int(1) unsigned NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_agent_id` (`user_agent_id`),
  KEY `testcase_id` (`testcase_id`),
  KEY `revision` (`revision`)
EOT;

  $this->mTables['revisions'] = <<<'EOT'
  `testcase_id` int(11) unsigned NOT NULL,
  `revision` char(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `equal_revision` varchar(40) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`testcase_id`, `revision`),
  KEY `testcase_id` (`testcase_id`),
  KEY `revision` (`revision`)
EOT;

  $this->mTables['test_help_links'] = <<<'EOT'
  `testcase_id` int(11) unsigned NOT NULL,
  `revision` char(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `uri` varchar(511) NOT NULL,
  `sequence` int(11) unsigned NOT NULL,
  KEY `testcase_id` (`testcase_id`),
  KEY `revision` (`revision`),
  KEY `uri` (`uri`)
EOT;

  $this->mTables['test_spec_links'] = <<<'EOT'
  `testcase_id` int(11) unsigned NOT NULL,
  `test_suite` varchar(31) NOT NULL,
  `spec` varchar(63) NOT NULL,
  `spec_type` enum('official','draft') NOT NULL,
  `parent_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `anchor_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `type` enum('direct','group') NOT NULL,
  `sequence` int(11) unsigned NOT NULL,
  PRIMARY KEY (`testcase_id`,`test_suite`,`spec`,`spec_type`,`parent_name`(191),`anchor_name`(191)),
  KEY `testcase_id` (`testcase_id`),
  KEY `spec` (`spec`),
  KEY `anchor_name` (`anchor_name`)
EOT;

  $this->mTables['spec_sync_data'] = <<<'EOT'
  `spec` varchar(63) NOT NULL,
  `sync_date` timestamp NOT NULL DEFAULT 0,
  PRIMARY KEY (`spec`)
EOT;

  $this->mTables['status_cache'] = <<<'EOT'
  `test_suite` varchar(31) NOT NULL,
  `spec` varchar(63) NOT NULL,
  `spec_type` enum('official','draft') NOT NULL,
  `parent_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `anchor_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `data` longblob,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`test_suite`,`spec`,`spec_type`,`parent_name`(191),`anchor_name`(191))
EOT;

  $this->mTables['suite_tests'] = <<<'EOT'
  `test_suite` varchar(31) NOT NULL,
  `testcase_id` int(11) unsigned NOT NULL,
  `revision` char(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  PRIMARY KEY (`test_suite`,`testcase_id`),
  KEY `test_suite` (`test_suite`),
  KEY `testcase_id` (`testcase_id`)
  KEY `revision` (`revision`)
EOT;

  $this->mTables['testcases'] = <<<'EOT'
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `revision` char(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `testcase` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `flags` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `assertion` varchar(1023) DEFAULT NULL,
  `credits` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`,`revision`)
EOT;

  $this->mTables['test_pages'] = <<<'EOT'
  `testcase_id` int(11) unsigned NOT NULL,
  `test_suite` varchar(31) NOT NULL,
  `format` varchar(15) NOT NULL,
  `uri` varchar(255) NOT NULL,
  PRIMARY KEY (`testcase_id`,`test_suite`,`format`)
EOT;

  $this->mTables['test_sequence'] = <<<'EOT'
  `test_suite` varchar(31) NOT NULL,
  `engine` varchar(15) NOT NULL,
  `testcase_id` int(11) unsigned NOT NULL,
  `sequence` int(11) unsigned NOT NULL,
  PRIMARY KEY (`test_suite`,`engine`,`testcase_id`)
EOT;

  }


  function getDBConfigKey()
  {
    return null;
  }

  function getSchemaVersion()
  {
    return 6;
  }
  
  function getSchemaGeneration()
  {
    return 2;
  }
  
  function installSchema(DBConnection $db)
  {
    if ($this->_getTableExists($db, 'statuscache')) {  // upgrade from pre-schema db
      return $this->upgradeSchema($db, 0);
    }
    return parent::installSchema($db);
  }

  
  function upgradeSchema(DBConnection $db, $fromVersion)
  {
    $db->query("START TRANSACTION");
    switch ($fromVersion) {
      case 0:
        $this->_renameTable($db, 'statuscache', 'status_cache');
        $this->_renameTable($db, 'suitetests', 'suite_tests');
        $this->_renameTable($db, 'testpages', 'test_pages');
        $this->_renameTable($db, 'testsequence', 'test_sequence');
        
        $sql  = "ALTER TABLE `results` ";
        $sql .= "  DROP INDEX `useragent_id`, ";
        $sql .= "  CHANGE `useragent_id` `user_agent_id` int(11) unsigned NOT NULL, ";
        $sql .= "  CHANGE `source_id` `user_id` int(11) unsigned NOT NULL, ";
        $sql .= "  CHANGE `source_useragent_id` `user_user_agent_id` int(11) unsigned NOT NULL, ";
        $sql .= "  ADD INDEX (`user_agent_id`) ";
        $db->query($sql);
        
        $this->_createTable($db, 'test_help_links');
        
        $sql  = "INSERT INTO `test_help_links` ";
        $sql .= "  (`testcase_id`, `revision`, `uri`, `sequence`) ";
        $sql .= "SELECT DISTINCT `speclinks`.`testcase_id`, `suite_tests`.`revision`, ";
        $sql .= "  CONCAT(`specifications`.`base_uri`, `sections`.`uri`) AS `uri`, `speclinks`.`sequence` ";
        $sql .= "FROM `speclinks` ";
        $sql .= "LEFT JOIN (`suite_tests`, `specifications`, `sections`) ";
        $sql .= "  ON `speclinks`.`testcase_id` = `suite_tests`.`testcase_id` ";
        $sql .= "  AND `speclinks`.`section_id` = `sections`.`id` ";
        $sql .= "  AND `sections`.`spec` = `specifications`.`spec` ";
        $sql .= "WHERE `speclinks`.`group` = 0 ";
        $db->query($sql);

        $this->_createTable($db, 'test_spec_links');
        $this->_createTable($db, 'spec_sync_data');
        $this->_dropTable($db, 'speclinks');
        ShellProcess::ExecuteFor(new User('sytem'), static::_GetInstallPath(), 'python/SynchronizeSpecLinks.py');

        $this->_emptyTable($db, 'status_cache');
        
        $sql  = "ALTER TABLE `status_cache` ";
        $sql .= "  DROP PRIMARY KEY, ";
        $sql .= "  CHANGE `testsuite`  `test_suite` varchar(31) NOT NULL, ";
        $sql .= "  ADD `spec` varchar(63) NOT NULL AFTER `test_suite`, ";
        $sql .= "  ADD `spec_type` enum('official','draft') NOT NULL AFTER `spec`, ";
        $sql .= "  ADD `parent_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `spec_type`, ";
        $sql .= "  ADD `anchor_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `parent_name`, ";
        $sql .= "  DROP `section_id`, ";
        $sql .= "  ADD PRIMARY KEY (`test_suite`,`spec`,`spec_type`,`parent_name`(191),`anchor_name`(191)) ";
        $db->query($sql);
        
        $sql  = "ALTER TABLE `suite_tests` ";
        $sql .= "  DROP PRIMARY KEY, ";
        $sql .= "  DROP INDEX `testsuite`, ";
        $sql .= "  CHANGE `testsuite` `test_suite` varchar(31) NOT NULL, ";
        $sql .= "  ADD PRIMARY KEY (`test_suite`, `testcase_id`), ";
        $sql .= "  ADD INDEX (`test_suite`) ";
        $db->query($sql);
        
        $sql  = "ALTER TABLE `test_sequence` ";
        $sql .= "  DROP PRIMARY KEY, ";
        $sql .= "  CHANGE `testsuite` `test_suite` varchar(31) NOT NULL, ";
        $sql .= "  ADD PRIMARY KEY (`test_suite`,`engine`,`testcase_id`) ";
        $db->query($sql);
        
        $this->_dropTable($db, 'flags');
        $this->_dropTable($db, 'formats');
        $this->_dropTable($db, 'sections');
        $this->_dropTable($db, 'sources');
        $this->_dropTable($db, 'specifications');
        $this->_dropTable($db, 'spidertrap');
        $this->_dropTable($db, 'testsuites');
        $this->_dropTable($db, 'useragents');
      
        // fall through intentional
      case 1:
        $sql  = "ALTER TABLE `references` ";
        $sql .= "  DROP KEY `testcase_id`, ";
        $sql .= "  ADD `revision` CHAR(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL AFTER `testcase_id`, ";
        $sql .= "  ADD KEY (`testcase_id`, `revision`) ";
        $db->query($sql);

        $sql  = "ALTER TABLE `results` ";
        $sql .= "  CHANGE `revision` `revision` char(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL, ";
        $sql .= "  ADD KEY (`revision`) ";
        $db->query($sql);

        $sql  = "ALTER TABLE `revisions` ";
        $sql .= "  CHANGE `revision` `revision` char(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL, ";
        $sql .= "  ADD KEY (`revision`) ";
        $db->query($sql);

        $sql  = "ALTER TABLE `suite_tests` ";
        $sql .= "  CHANGE `revision` `revision` char(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL, ";
        $sql .= "  ADD KEY (`revision`) ";
        $db->query($sql);

        $this->_createTable($db, 'testcases', 'new_testcases');
        
        $sql  = "INSERT INTO `new_testcases` ";
        $sql .= "  (`id`, `revision`, `testcase`, `title`, `flags`, `assertion`, `credits`) ";
        $sql .= "SELECT `testcases`.`id`, `suite_tests`.`revision`, `testcases`.`testcase`, `testcases`.`title`, ";
        $sql .= "  `testcases`.`flags`, `testcases`.`assertion`, `testcases`.`credits` ";
        $sql .= "FROM `testcases` ";
        $sql .= "LEFT JOIN `suite_tests` ";
        $sql .= "  ON `testcases`.`id` = `suite_tests`.`testcase_id` ";
        $sql .= "ON DUPLICATE KEY UPDATE `testcase` = `testcases`.`testcase`";
        $db->query($sql);

        $this->_dropTable($db, 'testcases');
        $this->_renameTable($db, 'new_testcases', 'testcases');

        $sql  = "ALTER TABLE `test_pages` ";
        $sql .= "  DROP PRIMARY KEY, ";
        $sql .= "  ADD `revision` CHAR(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL AFTER `testcase_id`, ";
        $sql .= "  ADD PRIMARY KEY (`testcase_id`, `revision`, `format`) ";
        $db->query($sql);

        $sql  = "UPDATE `references` ";
        $sql .= "LEFT JOIN `testcases` ";
        $sql .= "  ON `references`.`testcase_id` = `testcases`.`id` ";
        $sql .= "SET `references`.`revision` = `testcases`.`revision` ";
        $db->query($sql);
        
        $sql  = "UPDATE `test_pages` ";
        $sql .= "LEFT JOIN `testcases` ";
        $sql .= "  ON `test_pages`.`testcase_id` = `testcases`.`id` ";
        $sql .= "SET `test_pages`.`revision` = `testcases`.`revision` ";
        $db->query($sql);
        
        // fall through intentional
      case 2:
        $this->_createTable($db, 'test_pages', 'new_test_pages');
        
        $sql  = "INSERT INTO `new_test_pages` ";
        $sql .= "  (`testcase_id`, `test_suite`, `format`, `uri`) ";
        $sql .= "SELECT `test_pages`.`testcase_id`, `suite_tests`.`test_suite`, `test_pages`.`format`, `test_pages`.`uri` ";
        $sql .= "FROM `test_pages` ";
        $sql .= "INNER JOIN `suite_tests` ";
        $sql .= "  ON `test_pages`.`testcase_id` = `suite_tests`.`testcase_id` ";
        $db->query($sql);

        $this->_dropTable($db, 'test_pages');
        $this->_renameTable($db, 'new_test_pages', 'test_pages');

        $this->_createTable($db, 'reference_pages');

        $sql  = "INSERT INTO `reference_pages` ";
        $sql .= "  (`testcase_id`, `test_suite`, `reference`, `format`, `uri`) ";
        $sql .= "SELECT DISTINCT `references`.`testcase_id`, `suite_tests`.`test_suite`, `references`.`reference`, ";
        $sql .= "  `references`.`format`, `references`.`uri` ";
        $sql .= "FROM `references` ";
        $sql .= "INNER JOIN `suite_tests` ";
        $sql .= "  ON `references`.`testcase_id` = `suite_tests`.`testcase_id` ";
        $db->query($sql);
        
        $sql  = "ALTER TABLE `references` ";
        $sql .= "DROP `format`, ";
        $sql .= "DROP `uri` ";
        $db->query($sql);
        
        $this->_createTable($db, 'references', 'new_references');
        
        $sql  = "INSERT INTO `new_references` ";
        $sql .= "  (`testcase_id`, `revision`, `reference`, `type`) ";
        $sql .= "SELECT DISTINCT `testcase_id`, `revision`, `reference`, `type` ";
        $sql .= "FROM `references` ";
        $db->query($sql);
        
        $this->_dropTable($db, 'references');
        $this->_renameTable($db, 'new_references', 'references');

        $this->_createTable($db, 'revisions', 'new_revisions');
        
        $sql  = "INSERT INTO `new_revisions` ";
        $sql .= "  (`testcase_id`, `revision`, `equal_revision`, `date`) ";
        $sql .= "SELECT DISTINCT `testcase_id`, `revision`, `equal_revision`, `date` ";
        $sql .= "FROM `revisions` ";
        $db->query($sql);
        
        $this->_dropTable($db, 'revisions');
        $this->_renameTable($db, 'new_revisions', 'revisions');
        
        // fall through intentional
      case 3:
        $sql  = "ALTER TABLE `testcases` ";
        $sql .= "  CHANGE `testcase` `testcase` varchar(255) NOT NULL ";
        $db->query($sql);
      
        // fall through intentional
      case 4:
        $sql  = "ALTER TABLE `results` ";
        $sql .= "  ADD `pass_count` int(11) unsigned DEFAULT 0 AFTER `result`, ";
        $sql .= "  ADD `fail_count` int(11) unsigned DEFAULT 0 AFTER `pass_count` ";
        $db->query($sql);

        // fall through intentional
      case 5:
        $sql  = "ALTER TABLE `references` ";
        $sql .= "  DROP PRIMARY KEY, ";
        $sql .= "  ADD PRIMARY KEY (`testcase_id`, `revision`, `reference`(191), `group`) ";
        $db->query($sql);

        // fall through intentional
    }

    $db->query("COMMIT");
    return parent::upgradeSchema($db, $fromVersion);
  }
    
}


class HarnessDBConnection extends DBConnection
{
  static function GetDBSchema()
  {
    return new HarnessDBSchema();
  }
}

class HarnessDBEntity extends DBEntity
{
  static function GetDBSchema()
  {
    return new HarnessDBSchema();
  }
}
