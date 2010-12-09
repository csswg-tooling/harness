
--
-- Database schema for CSS 2.1 conformance test harness
--

-- --------------------------------------------------------

--
-- Table structure for table `flags`
--

CREATE TABLE IF NOT EXISTS `flags` (
  `flag` enum('ahem','dom','font','history','HTMLonly','image','interact','invalid','namespace','nonHTML','may','paged','reftest','should','scroll','svg','96dpi') NOT NULL default 'ahem',
  `description` longtext NOT NULL,
  PRIMARY KEY  (`flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE IF NOT EXISTS `results` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `testcase_id` int(11) unsigned NOT NULL default '0',
  `useragent_id` int(11) unsigned NOT NULL default '0',
  `source` varchar(16) NULL default '',
  `original_id` int(11) unsigned NULL,
  `result` enum('pass','fail','uncertain','na','invalid') NOT NULL default 'na',
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
  KEY `useragent_id` (`useragent_id`),
  KEY `testcase_id` (`testcase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `testcases`
--

CREATE TABLE IF NOT EXISTS `testcases` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `uri` varchar(255) NOT NULL default '',
  `testsuite` varchar(32) NOT NULL default '',
  `testcase` varchar(64) NOT NULL default '',
  `title` varchar(255) default NULL,
  `flags` set('ahem','dom','font','history','HTMLonly','image','interact','invalid','namespace','nonHTML','may','paged','reftest','should','scroll','svg','96dpi') default NULL,
  `assertion` varchar(255) default NULL,
  `testgroup` varchar(32) default NULL,
  `grandfather` tinyint(1) NOT NULL default '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `testgroups`
--

CREATE TABLE IF NOT EXISTS `testgroups` (
  `testgroup` varchar(32) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`testgroup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `testsuites`
--

CREATE TABLE IF NOT EXISTS `testsuites` (
  `testsuite` varchar(32) NOT NULL default '',
  `uri` varchar(255) default NULL,
  `title` varchar(255) default NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `description` longtext,
  PRIMARY KEY  (`testsuite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `useragents`
--

CREATE TABLE IF NOT EXISTS `useragents` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `useragent` varchar(255) NOT NULL default '',
  `engine` varchar(16) default NULL,
  `engine_version` varchar(16) default NULL,
  `browser` varchar(32) default NULL,
  `browser_version` varchar(16) default NULL,
  `platform` varchar(32) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

-- -- Table structure for table `testsequence`
-- 
CREATE TABLE `testsequence` (
 `engine` varchar(16) NOT NULL default '',
 `testcase_id` int(11) unsigned NOT NULL,
 `sequence` int(11) unsigned NOT NULL,
 PRIMARY KEY  (`engine`,`testcase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;



-- --------------------------------------------------------

--
-- Table structure for table `ir_import`
--

CREATE TABLE IF NOT EXISTS `ir_import` (
  `testcase_id` int(11) unsigned NOT NULL,
  `testsuite` varchar(32) NOT NULL,
  `testcase` varchar(64) NOT NULL,
  `useragent_id` int(11) unsigned NOT NULL,
  `source` varchar(16) COLLATE NULL,
  `result` enum('pass','fail','uncertain','na','invalid') NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- --------------------------------------------------------

-- 
-- Table structure for table `reftests`
-- 

CREATE TABLE `reftests` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `testcase_id` int(11) unsigned NOT NULL,
  `reference` varchar(64) NOT NULL default '',
  `uri` varchar(255) NOT NULL,
  `type` enum('==','!=') NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `honeypot`
--

CREATE TABLE `honeypot` (
 `ip_address` varchar(15) collate utf8_bin NOT NULL,
 `user_agent` varchar(255) collate utf8_bin default NULL,
 `last_query` varchar(255) collate utf8_bin default NULL,
 `visit_count` int(11) NOT NULL default '0',
 `banned` tinyint(1) NOT NULL default '0',
 `first_visit` timestamp NULL default NULL,
 `last_action` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
 PRIMARY KEY  (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- ---------------------------------------------------------
--
-- Useful SQL Qeuries
--

-- Set testcase ID in ir_import table
-- UPDATE ir_import INNER JOIN testcases ON ir_import.testsuite=testcases.testsuite AND ir_import.testcase=testcases.testcase SET ir_import.testcase_id=testcases.id, ir_import.modified=ir_import.modified WHERE ir_import.testcase_id='0';

-- Insert ir_import into results
-- INSERT INTO results (testcase_id, useragent_id, source, result, modified) SELECT testcase_id, useragent_id, source, result, modified FROM ir_import;

-- Insert reftests_import into reftests
-- INSERT INTO reftests (testcase_id, reference, uri, type) SELECT testcase_id, reference, uri, type FROM reftests_import;

-- Set reftest flags
-- UPDATE testcases, reftests SET flags=CONCAT(flags,',reftest'), modified=modified WHERE testcases.id=reftests.testcase_id;

