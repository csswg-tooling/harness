
--
-- Database schema for CSS Conformance Test Harness
--

-- --------------------------------------------------------

--
-- Table structure for table `flags`
--

CREATE TABLE IF NOT EXISTS `flags` (
  `flag` varchar(15) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL,
  `set_test` varchar(255) DEFAULT NULL,
  `unset_test` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `formats`
--

CREATE TABLE IF NOT EXISTS `formats` (
  `format` varchar(15) NOT NULL DEFAULT 'html4',
  `title` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `home_uri` varchar(63) DEFAULT NULL,
  `path` varchar(63) DEFAULT NULL,
  `extension` varchar(15) DEFAULT NULL,
  `filter` varchar(127) DEFAULT NULL,
  PRIMARY KEY (`format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `references`
--

CREATE TABLE IF NOT EXISTS `references` (
  `testcase_id` int(11) unsigned NOT NULL,
  `format` varchar(15) NOT NULL DEFAULT 'html4',
  `reference` varchar(255) NOT NULL,
  `uri` varchar(255) NOT NULL,
  `type` enum('==','!=') NOT NULL,
  KEY `testcase_id` (`testcase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE IF NOT EXISTS `results` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `testcase_id` int(11) unsigned NOT NULL DEFAULT '0',
  `revision` varchar(40) NOT NULL DEFAULT '0',
  `format` varchar(15) NOT NULL DEFAULT 'html4',
  `useragent_id` int(11) unsigned NOT NULL DEFAULT '0',
  `source_id` int(11) unsigned NOT NULL DEFAULT '0',
  `source_useragent_id` int(11) unsigned NOT NULL DEFAULT '0',
  `result` enum('pass','fail','uncertain','na','invalid') NOT NULL DEFAULT 'na',
  `comment` varchar(63) DEFAULT NULL,
  `ignore` int(1) unsigned NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `useragent_id` (`useragent_id`),
  KEY `testcase_id` (`testcase_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `revisions`
--

CREATE TABLE IF NOT EXISTS `revisions` (
  `testcase_id` int(11) unsigned NOT NULL DEFAULT '0',
  `revision` varchar(40) NOT NULL DEFAULT '0',
  `equal_revision` varchar(40) NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `testcase_id` (`testcase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sources`
--

CREATE TABLE IF NOT EXISTS `sources` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(63) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `specifications`
--

CREATE TABLE IF NOT EXISTS `specifications` (
  `spec` varchar(31) NOT NULL,
  `title` varchar(31) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `base_uri` varchar(255) DEFAULT NULL,
  `home_uri` varchar(63) DEFAULT NULL,
  PRIMARY KEY (`spec`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `speclinks`
--

CREATE TABLE IF NOT EXISTS `speclinks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0',
  `spec` varchar(31) DEFAULT NULL,
  `section` varchar(31) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `uri` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `spidertrap`
--

CREATE TABLE IF NOT EXISTS `spidertrap` (
  `ip_address` varchar(39) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `last_uri` varchar(255) DEFAULT NULL,
  `visit_count` int(11) unsigned NOT NULL DEFAULT '0',
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `first_visit` timestamp NULL DEFAULT NULL,
  `last_action` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `suitetests`
--

CREATE TABLE IF NOT EXISTS `suitetests` (
  `testsuite` varchar(31) NOT NULL DEFAULT '',
  `testcase_id` int(11) unsigned NOT NULL DEFAULT '0',
  `revision` varchar(40) NOT NULL DEFAULT '0',
  PRIMARY KEY (`testsuite`,`testcase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `testcases`
--

CREATE TABLE IF NOT EXISTS `testcases` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `testcase` varchar(63) NOT NULL DEFAULT '',
  `last_revision` varchar(40) NOT NULL DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `flags` varchar(255) DEFAULT NULL,
  `assertion` varchar(1023) DEFAULT NULL,
  `credits` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `testlinks`
--

CREATE TABLE IF NOT EXISTS `testlinks` (
  `testcase_id` int(11) unsigned NOT NULL,
  `speclink_id` int(11) unsigned NOT NULL,
  `sequence` int(11) unsigned NOT NULL DEFAULT '0',
  `group` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`testcase_id`,`speclink_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `testpages`
--

CREATE TABLE IF NOT EXISTS `testpages` (
  `testcase_id` int(11) unsigned NOT NULL,
  `format` varchar(15) NOT NULL DEFAULT 'html4',
  `uri` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`testcase_id`,`format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `testsequence`
--

CREATE TABLE IF NOT EXISTS `testsequence` (
  `testsuite` varchar(31) NOT NULL DEFAULT '',
  `engine` varchar(15) NOT NULL DEFAULT '',
  `testcase_id` int(11) unsigned NOT NULL,
  `sequence` int(11) unsigned NOT NULL,
  PRIMARY KEY (`testsuite`,`engine`,`testcase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `testsuites`
--

CREATE TABLE IF NOT EXISTS `testsuites` (
  `testsuite` varchar(31) NOT NULL DEFAULT '',
  `base_uri` varchar(255) DEFAULT NULL,
  `home_uri` varchar(63) DEFAULT NULL,
  `spec` varchar(31) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `formats` varchar(127) NOT NULL DEFAULT 'html4,xhtml1',
  `optional_flags` varchar(127) DEFAULT NULL,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `locked` timestamp NULL DEFAULT NULL,
  `description` longtext,
  `contact_name` varchar(63) DEFAULT NULL,
  `contact_uri` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`testsuite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `useragents`
--

CREATE TABLE IF NOT EXISTS `useragents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `useragent` varchar(255) NOT NULL DEFAULT '',
  `engine` varchar(15) DEFAULT NULL,
  `engine_version` varchar(15) DEFAULT NULL,
  `browser` varchar(31) DEFAULT NULL,
  `browser_version` varchar(15) DEFAULT NULL,
  `platform` varchar(31) DEFAULT NULL,
  `platform_version` varchar(31) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


-- --------------------------------------------------------
-- --------------------------------------------------------
-- --------------------------------------------------------


INSERT INTO `flags` (`flag`, `description`, `set_test`, `unset_test`) VALUES
('32bit', 'This test assumes a <abbr title=''-2147483648 to 2147483647''>32-bit signed integer</abbr> is used to store values.', NULL, NULL),
('96dpi', 'This test assumes a display calibrated to 96dpi.', NULL, NULL),
('ahem', 'This test requires the <a href="http://www.w3.org/Style/CSS/Test/Ahem/">Ahem font</a> to be installed.', NULL, NULL),
('animated', 'This test is animated in its final state.', NULL, NULL),
('combo', 'This test is a combination of child tests.', NULL, NULL),
('dom', 'This test requires support for JavaScript and the <abbr title="Document Object Model">DOM</abbr>.', NULL, NULL),
('font', 'This test requires a specific font to be installed, possibly one of <a href="http://www.w3.org/Style/CSS/Test/Fonts/Overview">these</a>.', NULL, NULL),
('history', 'This test requires support for a session history.', NULL, NULL),
('HTMLonly', 'This test is specific to HTML.', NULL, NULL),
('http', 'This test requires HTTP headers.', NULL, NULL),
('image', 'This test requires raster image support.', NULL, NULL),
('interact', 'This test requires user interaction.', NULL, NULL),
('invalid', 'This test contains invalid CSS in order to test error-handling.', NULL, NULL),
('may', 'This test tests for preferred, but optional behavior.', NULL, NULL),
('may21', 'This test tests for preferred, but optional behavior.', NULL, NULL),
('namespace', 'This test requires support for XML namespaces.', NULL, NULL),
('nonHTML', 'This test is valid only for formats other than HTML (such as XHTML).', NULL, NULL),
('paged', 'This test is only valid for paged media such as print.', NULL, NULL),
('reftest', 'This test must be compared to one or more reference pages.', NULL, NULL),
('scroll', 'This test is only valid for continuous (scrolling) media.', NULL, NULL),
('should', 'This test tests for recommended, but not required behavior.', NULL, NULL),
('svg', 'This test requires support for SVG.', NULL, NULL),
('userstyle', 'This test requires a user style sheet to be applied.', '<p id=''user-stylesheet-indication'' class=''userstyle''>A user style sheet is applied.</p>', '<p id=''user-stylesheet-indication'' class=''nouserstyle''>A user style sheet is applied. Please remove it.</p>');



INSERT INTO `formats` (`format`, `title`, `description`, `home_uri`, `path`, `extension`, `filter`) VALUES
('html4', 'HTML 4', 'HTML 4.01', 'html4/toc.html', 'html4', 'htm', 'nonHTML'),
('xhtml1', 'XHTML 1.1', 'XHTML 1.1', 'xhtml1/toc.xht', 'xhtml1', 'xht', 'HTMLonly'),
('xhtml1print', 'XHTML-Print', 'XHTML 1.1 for Printers', 'xhtml1print/toc.xht', 'xhtml1print', 'xht', 'HTMLonly');

-- --------------------------------------------------------
