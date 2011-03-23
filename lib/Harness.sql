
--
-- Database schema for CSS Conformance Test Harness
--

-- --------------------------------------------------------


--
-- Table structure for table `flags`
--

CREATE TABLE IF NOT EXISTS `flags` (
  `flag` enum('ahem','animated','combo','dom','font','history','http','HTMLonly','image','interact','invalid','namespace','nonHTML','may','may21','paged','reftest','should','scroll','svg','userstyle','32bit','96dpi') NOT NULL default 'ahem',
  `description` varchar(255) NOT NULL,
  `set_test` varchar(255) default NULL,
  `unset_test` varchar(255) default NULL,
  PRIMARY KEY  (`flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `formats`
--

CREATE TABLE IF NOT EXISTS `formats` (
  `format` enum('html4','xhtml1') NOT NULL default 'html4',
  `title` varchar(255) NOT NULL,
  `path` varchar(63) default NULL,
  `extension` varchar(15) default NULL,
  `filter` enum('HTMLonly','nonHTML') default NULL,
  PRIMARY KEY  (`format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `formats` VALUES('html4', 'HTML 4', 'html4', 'htm', 'nonHTML');
INSERT INTO `formats` VALUES('xhtml1', 'XHTML 1.1', 'xhtml1', 'xht', 'HTMLonly');

-- --------------------------------------------------------

--
-- Table structure for table `references`
--

CREATE TABLE IF NOT EXISTS `references` (
  `testcase_id` int(11) unsigned NOT NULL,
  `format` enum('html4','xhtml1') NOT NULL default 'html4',
  `reference` varchar(255) NOT NULL,
  `uri` varchar(255) NOT NULL,
  `type` enum('==','!=') NOT NULL,
  KEY `testcase_id` (`testcase_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE IF NOT EXISTS `results` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `testcase_id` int(11) unsigned NOT NULL default '0',
  `revision` int(11) unsigned NOT NULL default '0',
  `format` enum('html4','xhtml1') NOT NULL default 'html4',
  `useragent_id` int(11) unsigned NOT NULL default '0',
  `source_id` int(11) unsigned NOT NULL default '0',
  `source_useragent_id` int(11) unsigned NOT NULL default '0',
  `result` enum('pass','fail','uncertain','na','invalid') NOT NULL default 'na',
  `comment` varchar(63) default NULL,
  `ignore` int(1) unsigned NOT NULL,
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `useragent_id` (`useragent_id`),
  KEY `testcase_id` (`testcase_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8  ;

-- --------------------------------------------------------

--
-- Table structure for table `sources`
--

CREATE TABLE IF NOT EXISTS `sources` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `source` varchar(31) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8  ;

-- --------------------------------------------------------

--
-- Table structure for table `spidertrap`
--

CREATE TABLE IF NOT EXISTS `spidertrap` (
  `ip_address` varchar(15) NOT NULL,
  `user_agent` varchar(255) default NULL,
  `last_uri` varchar(255) default NULL,
  `visit_count` int(11) unsigned NOT NULL default '0',
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
  `testcase` varchar(63) NOT NULL default '',
  `last_revision` int(11) unsigned NOT NULL default '0',
  `title` varchar(255) default NULL,
  `flags` set('ahem','animated','combo','dom','font','history','http','HTMLonly','image','interact','invalid','namespace','nonHTML','may','may21','paged','reftest','should','scroll','svg','userstyle','32bit','96dpi') default NULL,
  `assertion` varchar(1023) default NULL,
  `credits` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `revisions`
--

CREATE TABLE IF NOT EXISTS `revisions` (
  `testcase_id` int(11) unsigned NOT NULL DEFAULT '0',
  `revision` int(11) unsigned NOT NULL DEFAULT '0',
  `equal_revision` int(11) NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `testcase_id` (`testcase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `suitetests`
--

CREATE TABLE IF NOT EXISTS `suitetests` (
  `testsuite` varchar(31) NOT NULL default '',
  `testcase_id` int(11) unsigned NOT NULL default 0,
  `revision` int(11) unsigned NOT NULL default 0,
  PRIMARY KEY  (`testsuite`,`testcase_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `testlinks`
--
CREATE TABLE IF NOT EXISTS `testlinks` (
  `testcase_id` int(11) unsigned NOT NULL,
  `speclink_id` int(11) unsigned NOT NULL,
  `group` tinyint(1) unsigned NOT NULL default 0,
  PRIMARY KEY (`testcase_id`,`speclink_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `speclinks`
--

CREATE TABLE IF NOT EXISTS `speclinks` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `parent_id` int(11) unsigned NOT NULL default 0,
  `spec` varchar(31) NULL,
  `section` varchar(31) NULL,
  `title` varchar(255) NULL,
  `uri` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `testpages`
--

CREATE TABLE IF NOT EXISTS `testpages` (
  `testcase_id` int(11) unsigned NOT NULL,
  `format` enum('html4','xhtml1') NOT NULL default 'html4',
  `uri` varchar(255) NOT NULL default '',
  PRIMARY KEY (`testcase_id`,`format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `testsequence`
--

CREATE TABLE IF NOT EXISTS `testsequence` (
  `testsuite` varchar(31) NOT NULL default '',
  `engine` varchar(15) NOT NULL default '',
  `testcase_id` int(11) unsigned NOT NULL,
  `sequence` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`testsuite`,`engine`,`testcase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `specifications`
--

CREATE TABLE IF NOT EXISTS `specifications` (
  `spec` varchar(31) NOT NULL,
  `title` varchar(31) default NULL,
  `description` varchar(255) default NULL,
  `uri` varchar(255) default NULL,
  PRIMARY KEY  (`spec`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `testsuites`
--

CREATE TABLE IF NOT EXISTS `testsuites` (
  `testsuite` varchar(31) NOT NULL default '',
  `base_uri` varchar(255) default NULL,
  `home_uri` varchar(63) default NULL,
  `spec` varchar(31) default NULL,
  `title` varchar(255) default NULL,
  `formats` set('html4','xhtml1') NOT NULL default 'html4,xhtml1',
  `optional_flags` set('ahem','animated','combo','dom','font','history','http','HTMLonly','image','interact','invalid','namespace','nonHTML','may','may21','paged','reftest','should','scroll','svg','userstyle','32bit','96dpi') default NULL,
  `active` tinyint(1) unsigned NOT NULL default '1',
  `locked` timestamp NULL default NULL,
  `description` longtext,
  `contact_name` varchar(63) default NULL,
  `contact_uri` varchar(255) default NULL,
  PRIMARY KEY  (`testsuite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `testsuites`
--

INSERT INTO `testsuites` VALUES('CSS21', 'http://test.csswg.org/suites/css2.1/nightly-unstable/', '', 'CSS21', 'CSS 2.1 Test Suite', 'html4,xhtml1', 1, 0, 'CSS 2.1 Test Suite, Nightly Build.', 'public-css-testsuite@w3.org', 'http://lists.w3.org/Archives/Public/public-css-testsuite');
INSERT INTO `testsuites` VALUES('CSS21_RC1', 'http://test.csswg.org/suites/css2.1/20100917/', '', 'CSS21', 'CSS 2.1 Test Suite RC1', 'html4,xhtml1', 0, 0, 'CSS 2.1 Test Suite, Release Candidate 1.', 'public-css-testsuite@w3.org', 'http://lists.w3.org/Archives/Public/public-css-testsuite');
INSERT INTO `testsuites` VALUES('CSS21_RC2', 'http://test.csswg.org/suites/css2.1/20101001/', '', 'CSS21', 'CSS 2.1 Test Suite RC2', 'html4,xhtml1', 0, 0, 'CSS 2.1 Test Suite, Release Candidate 2.', 'public-css-testsuite@w3.org', 'http://lists.w3.org/Archives/Public/public-css-testsuite');
INSERT INTO `testsuites` VALUES('CSS21_RC3', 'http://test.csswg.org/suites/css2.1/20101027/', '', 'CSS21', 'CSS 2.1 Test Suite RC3', 'html4,xhtml1', 0, 0, 'CSS 2.1 Test Suite, Release Candidate 3.', 'public-css-testsuite@w3.org', 'http://lists.w3.org/Archives/Public/public-css-testsuite');
INSERT INTO `testsuites` VALUES('CSS21_RC4', 'http://test.csswg.org/suites/css2.1/20101210/', '', 'CSS21', 'CSS 2.1 Test Suite RC4', 'html4,xhtml1', 0, 0, 'CSS 2.1 Test Suite, Release Candidate 4.', 'public-css-testsuite@w3.org', 'http://lists.w3.org/Archives/Public/public-css-testsuite');
INSERT INTO `testsuites` VALUES('CSS21_RC5', 'http://test.csswg.org/suites/css2.1/20110111/', '', 'CSS21', 'CSS 2.1 Test Suite RC5', 'html4,xhtml1', 0, 0, 'CSS 2.1 Test Suite, Release Candidate 5.', 'public-css-testsuite@w3.org', 'http://lists.w3.org/Archives/Public/public-css-testsuite');

-- --------------------------------------------------------

--
-- Table structure for table `useragents`
--

CREATE TABLE IF NOT EXISTS `useragents` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `useragent` varchar(255) NOT NULL default '',
  `engine` varchar(15) default NULL,
  `engine_version` varchar(15) default NULL,
  `browser` varchar(31) default NULL,
  `browser_version` varchar(15) default NULL,
  `platform` varchar(31) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8  ;


