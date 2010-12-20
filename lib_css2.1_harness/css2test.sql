
--
-- Database schema for CSS Conformance Test Harness
--

-- --------------------------------------------------------

--
-- Table structure for table `flags`
--

CREATE TABLE IF NOT EXISTS `flags` (
  `flag` enum('ahem','animated','dom','font','history','http','HTMLonly','image','interact','invalid','namespace','nonHTML','may','paged','reftest','should','scroll','svg','userstyle','96dpi') NOT NULL default 'ahem',
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `reftests`
--

CREATE TABLE IF NOT EXISTS `reftests` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `testcase_id` int(11) unsigned NOT NULL,
  `reference` varchar(255) NOT NULL,
  `uri` varchar(255) NOT NULL,
  `type` enum('==','!=') NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE IF NOT EXISTS `results` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `testcase_id` int(11) unsigned NOT NULL default '0',
  `useragent_id` int(11) unsigned NOT NULL default '0',
  `source` varchar(16) default NULL,
  `original_id` int(11) unsigned default NULL,
  `result` enum('pass','fail','uncertain','na','invalid') NOT NULL default 'na',
  `ignore` int(1) unsigned NOT NULL,
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `useragent_id` (`useragent_id`),
  KEY `testcase_id` (`testcase_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `spidertrap`
--

CREATE TABLE IF NOT EXISTS `spidertrap` (
  `ip_address` varchar(15) NOT NULL,
  `user_agent` varchar(255) default NULL,
  `last_uri` varchar(255) default NULL,
  `visit_count` int(11) NOT NULL default '0',
  `banned` tinyint(1) NOT NULL default '0',
  `first_visit` timestamp NULL default NULL,
  `last_action` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `flags` set('ahem','animated','dom','font','history','http','HTMLonly','image','interact','invalid','namespace','nonHTML','may','paged','reftest','should','scroll','svg','userstyle','96dpi') default NULL,
  `assertion` varchar(255) default NULL,
  `testgroup` varchar(32) default NULL,
  `grandfather` tinyint(1) NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '1',
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `testgroups`
--

CREATE TABLE IF NOT EXISTS `testgroups` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `testgroup` varchar(32) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `testlinks`
--

CREATE TABLE IF NOT EXISTS `testlinks` (
  `testcase_id` int(11) unsigned NOT NULL,
  `title` varchar(255) default NULL,
  `uri` varchar(255) default NULL,
  KEY `testcase_id` (`testcase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `testsequence`
--

CREATE TABLE IF NOT EXISTS `testsequence` (
  `engine` varchar(16) NOT NULL default '',
  `testcase_id` int(11) unsigned NOT NULL,
  `sequence` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`engine`,`testcase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `testsuites`
--

CREATE TABLE IF NOT EXISTS `testsuites` (
  `testsuite` varchar(32) NOT NULL default '',
  `base_uri` varchar(255) default NULL,
  `home_uri` varchar(64) default NULL,
  `spec_uri` varchar(255) default NULL,
  `title` varchar(255) default NULL,
  `active` tinyint(1) NOT NULL default '1',
  `description` longtext,
  `sequence_query` varchar(32) default NULL,
  PRIMARY KEY  (`testsuite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
