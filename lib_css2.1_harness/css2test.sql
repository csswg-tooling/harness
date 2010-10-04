
--
-- Database schema for CSS 2.1 conformance test harness
--

-- --------------------------------------------------------

--
-- Table structure for table `flags`
--

CREATE TABLE IF NOT EXISTS `flags` (
  `flag` enum('ahem','dom','font','history','HTMLonly','image','interact','invalid','namespace','nonHTML','may','paged','should','scroll','svg','96dpi') NOT NULL default 'ahem',
  `description` longtext NOT NULL,
  PRIMARY KEY  (`flag`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE IF NOT EXISTS `results` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `testcase_id` int(11) unsigned NOT NULL default '0',
  `testsuite` varchar(255) NOT NULL default '',
  `testcase` varchar(255) NOT NULL default '',
  `useragent` varchar(255) NOT NULL default '',
  `useragent_id` int(11) unsigned NOT NULL default '0',
  `client_ip` varchar(15) NULL default '',
  `result` enum('pass','fail','uncertain','na') NOT NULL default 'pass',
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `testcases`
--

CREATE TABLE IF NOT EXISTS `testcases` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `uri` varchar(255) NOT NULL default '',
  `testsuite` varchar(255) NOT NULL default '',
  `testcase` varchar(255) NOT NULL default '',
  `title` varchar(255) default NULL,
  `flags` set('ahem','dom','font','history','HTMLonly','image','interact','invalid','namespace','nonHTML','may','paged','should','scroll','svg','96dpi') default NULL,
  `assertion` varchar(255) default NULL,
  `testgroup` varchar(255) default NULL,
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `testgroups`
--

CREATE TABLE IF NOT EXISTS `testgroups` (
  `testgroup` varchar(32) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`testgroup`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `testsuites`
--

CREATE TABLE IF NOT EXISTS `testsuites` (
  `testsuite` varchar(255) NOT NULL default '',
  `uri` varchar(255) default NULL,
  `title` varchar(255) default NULL,
  `description` longtext,
  PRIMARY KEY  (`testsuite`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `useragents`
--

CREATE TABLE IF NOT EXISTS `useragents` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `useragent` varchar(255) NOT NULL default '',
  `engine` varchar(255) default NULL,
  `engine_version` varchar(255) default NULL,
  `browser` varchar(255) default NULL,
  `browser_version` varchar(255) default NULL,
  `platform` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ;
