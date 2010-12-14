
--
-- Database schema for CSS Conformance Test Harness
--

-- --------------------------------------------------------

--
-- Table structure for table `flags`
--

CREATE TABLE IF NOT EXISTS `flags` (
  `flag` enum('ahem','dom','font','history','HTMLonly','image','interact','invalid','namespace','nonHTML','may','paged','reftest','should','scroll','svg','96dpi') collate utf8_bin NOT NULL default 'ahem',
  `description` longtext collate utf8_bin NOT NULL,
  PRIMARY KEY  (`flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `reftests`
--

CREATE TABLE IF NOT EXISTS `reftests` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `testcase_id` int(11) unsigned NOT NULL,
  `reference` varchar(255) collate utf8_bin NOT NULL,
  `uri` varchar(255) collate utf8_bin NOT NULL,
  `type` enum('==','!=') collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE IF NOT EXISTS `results` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `testcase_id` int(11) unsigned NOT NULL default '0',
  `useragent_id` int(11) unsigned NOT NULL default '0',
  `source` varchar(16) collate utf8_bin default NULL,
  `original_id` int(11) unsigned default NULL,
  `result` enum('pass','fail','uncertain','na','invalid') collate utf8_bin NOT NULL default 'na',
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `useragent_id` (`useragent_id`),
  KEY `testcase_id` (`testcase_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `spidertrap`
--

CREATE TABLE IF NOT EXISTS `spidertrap` (
  `ip_address` varchar(15) collate utf8_bin NOT NULL,
  `user_agent` varchar(255) collate utf8_bin default NULL,
  `last_query` varchar(255) collate utf8_bin default NULL,
  `visit_count` int(11) NOT NULL default '0',
  `banned` tinyint(1) NOT NULL default '0',
  `first_visit` timestamp NULL default NULL,
  `last_action` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `testcases`
--

CREATE TABLE IF NOT EXISTS `testcases` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `uri` varchar(255) collate utf8_bin NOT NULL default '',
  `testsuite` varchar(32) collate utf8_bin NOT NULL default '',
  `testcase` varchar(64) collate utf8_bin NOT NULL default '',
  `title` varchar(255) collate utf8_bin default NULL,
  `flags` set('ahem','dom','font','history','HTMLonly','image','interact','invalid','namespace','nonHTML','may','paged','reftest','should','scroll','svg','96dpi') collate utf8_bin default NULL,
  `assertion` varchar(255) collate utf8_bin default NULL,
  `testgroup` varchar(32) collate utf8_bin default NULL,
  `grandfather` tinyint(1) NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '1',
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `testgroups`
--

CREATE TABLE IF NOT EXISTS `testgroups` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `testgroup` varchar(32) collate utf8_bin NOT NULL default '',
  `title` varchar(255) collate utf8_bin NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `testlinks`
--

CREATE TABLE IF NOT EXISTS `testlinks` (
  `testcase_id` int(11) unsigned NOT NULL,
  `title` varchar(255) collate utf8_bin default NULL,
  `uri` varchar(255) collate utf8_bin default NULL,
  KEY `testcase_id` (`testcase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `testsequence`
--

CREATE TABLE IF NOT EXISTS `testsequence` (
  `engine` varchar(16) collate utf8_bin NOT NULL default '',
  `testcase_id` int(11) unsigned NOT NULL,
  `sequence` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`engine`,`testcase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `testsuites`
--

CREATE TABLE IF NOT EXISTS `testsuites` (
  `testsuite` varchar(32) collate utf8_bin NOT NULL default '',
  `base_uri` varchar(255) collate utf8_bin default NULL,
  `home_uri` varchar(64) collate utf8_bin default NULL,
  `spec_uri` varchar(255) collate utf8_bin default NULL,
  `title` varchar(255) collate utf8_bin default NULL,
  `active` tinyint(1) NOT NULL default '1',
  `description` longtext collate utf8_bin,
  `sequence_query` varchar(32) collate utf8_bin default NULL,
  PRIMARY KEY  (`testsuite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `useragents`
--

CREATE TABLE IF NOT EXISTS `useragents` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `useragent` varchar(255) collate utf8_bin NOT NULL default '',
  `engine` varchar(16) collate utf8_bin default NULL,
  `engine_version` varchar(16) collate utf8_bin default NULL,
  `browser` varchar(32) collate utf8_bin default NULL,
  `browser_version` varchar(16) collate utf8_bin default NULL,
  `platform` varchar(32) collate utf8_bin default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- ---------------------------------------------------------

