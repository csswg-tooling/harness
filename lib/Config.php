<?php
/*******************************************************************************
 *
 *  Copyright © 2007 World Wide Web Consortium
 *  Copyright © 2008-2011 Hewlett-Packard Development Company, L.P. 
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

define('DEBUG_MODE', TRUE);

/**
 * Database configuration
 */
include_once('DBConfig.php');
//define('DB_HOST', 'localhost');
//define('DB_USER', '');
//define('DB_PASSWORD', '');
//define('DB_NAME', '');


/**
 * Server info
 */
define('SERVER_TIME_ZONE' , 'America/Los_Angeles');
define('REWRITE_ON', TRUE); // if mod_rewrite rules in force

/**
 * Contact info
 */
define('CONTACT_URI', 'http://lists.w3.org/Archives/Public/public-css-testsuite');
define('CONTACT_NAME', 'public-css-testsuite@w3.org');


/**
 * URIs used within the harness
 */
define('HARNESS_BASE_URI', 'http://test.csswg.org/harness/');
define('HARNESS_INSTALL_URI', '/harness');
define('HOME_PAGE_URI', './');
define('TESTSUITE_PAGE_URI', 'testsuite');
define('START_PAGE_URI', 'teststart');
define('TESTCASE_PAGE_URI', 'testcase');
define('SELECT_UA_PAGE_URI', 'useragent');
define('SET_UA_PAGE_URI', 'setuseragent');
define('REVIEW_PAGE_URI', 'review');
define('LOAD_RESULTS_PAGE_URI', 'loadresults');
define('RESULTS_PAGE_URI', 'results');
define('DETAILS_PAGE_URI', 'details');
define('SUBMIT_PAGE_URI', 'submit');
define('SUCCESS_PAGE_URI', 'success');
define('STATUS_QUERY_URI', 'status.php');

/**
 * Stylesheet URIs used within the harness
 */
define('ANNOTATION_STYLESHEET_URI', 'stylesheets/annotate.css');
define('BASE_STYLESHEET_URI', 'stylesheets/base.css');
define('REPORT_STYLESHEET_URI', 'stylesheets/report.css');
define('TEST_STYLESHEET_URI', 'stylesheets/testcase.css');
define('TEST_ENGINE_STYLESHEET_URI', 'stylesheets/test_%s.css');
define('TESTSUITE_STYLESHEET_URI', 'stylesheets/testsuite.css');


/**
 * Spider trap config
 *
 * URI to use for spider trap
 * Number of visits to trigger ban (within test period)
 * Period to test for spider behavior (days)
 * Period to ban activity (days)
 * Shell commands to ban & release spiders
 * Post process command to run after ban/release
 */
define('SPIDER_TRAP_URI', 'report');
define('SPIDER_BAN_THRESHOLD', 2);
define('SPIDER_TEST_PERIOD', 3);
define('SPIDER_BAN_PERIOD', 7);
define('SPIDER_BAN_COMMAND', '/sbin/iptables -I INPUT -s {ip} -j DROP');
define('SPIDER_RELEASE_COMMAND', '/sbin/iptables --delete INPUT -s {ip} -j DROP');
define('SPIDER_POST_PROCESS_COMMAND', '/sbin/iptables-save > /etc/firewall.conf');


/**
 * Max field lengths for database tables
 */
define('REFERENCES_MAX_FORMAT', 15);
define('REFERENCES_MAX_REFERENCE', 255);
define('REFERENCES_MAX_URI', 255);

define('RESULTS_MAX_REVISION', 40);
define('RESULTS_MAX_FORMAT', 15);
define('RESULTS_MAX_COMMENT', 63);

define('SOURCES_MAX_SOURCE', 63);

define('FORMATS_MAX_FORMAT', 15);
define('FORMATS_MAX_TITLE', 255);
define('FORMATS_MAX_DESCRIPTION', 255);
define('FORMATS_MAX_HOME_URI', 255);

define('SPIDERTRAP_MAX_IP', 39);
define('SPIDERTRAP_MAX_USER_AGENT', 255);
define('SPIDERTRAP_MAX_URI', 255);

define('SUITETESTS_MAX_TESTSUITE', 31);
define('SUITETESTS_MAX_REVISION', 40);

define('TESTCASES_MAX_TESTCASE', 63);
define('TESTCASES_MAX_LAST_REVISION', 40);
define('TESTCASES_MAX_TITLE', 255);
define('TESTCASES_MAX_ASSERTION', 1023);
define('TESTCASES_MAX_CREDITS', 255); // XXX break out into table

define('TESTPAGES_MAX_FORMAT', 15);
define('TESTPAGES_MAX_URI', 255);

define('REVISIONS_MAX_REVISION', 40);
define('REVISIONS_MAX_EQUAL_REVISION', 40);

define('SPECIFICATIONS_MAX_SPEC', 31);
define('SPECIFICATIONS_MAX_TITLE', 31);
define('SPECIFICATIONS_MAX_DESCRIPTION', 255);
define('SPECIFICATIONS_MAX_BASE_URI', 255);
define('SPECIFICATIONS_MAX_HOME_URI', 63);

define('SPECLINKS_MAX_SPEC', 31);
define('SPECLINKS_MAX_SECTION', 31);
define('SPECLINKS_MAX_TITLE', 255);
define('SPECLINKS_MAX_URI', 255);

define('TESTSEQUENCE_MAX_TESTSUITE', 31);
define('TESTSEQUENCE_MAX_ENGINE', 15);

define('TESTSUITES_MAX_TESTSUITE', 31);
define('TESTSUITES_MAX_BASE_URI', 255);
define('TESTSUITES_MAX_HOME_URI', 63);
define('TESTSUITES_MAX_SPEC_URI', 255);
define('TESTSUITES_MAX_SPEC', 31);
define('TESTSUITES_MAX_TITLE', 255);
define('TESTSUITES_MAX_FORMATS', 127);
define('TESTSUITES_MAX_CONTACT_NAME', 63);
define('TESTSUITES_MAX_CONTACT_URI', 255);

define('USERAGENTS_MAX_USERAGENT', 255);
define('USERAGENTS_MAX_ENGINE', 15);
define('USERAGENTS_MAX_ENGINE_VERSION', 15);
define('USERAGENTS_MAX_BROWSER', 31);
define('USERAGENTS_MAX_BROWSER_VERSION', 15);
define('USERAGENTS_MAX_PLATFORM', 31);
define('USERAGENTS_MAX_PLATFORM_VERSION', 31);


/**
 * Debug / Command line setup
 */
if ((defined('DEBUG_MODE') && DEBUG_MODE) || 
    (defined('COMMAND_LINE') && COMMAND_LINE)) {
  error_reporting(E_ALL | E_STRICT);
  assert_options(ASSERT_ACTIVE,     1);
  assert_options(ASSERT_WARNING,    1);
  assert_options(ASSERT_BAIL,       1);
  assert_options(ASSERT_QUIET_EVAL, 0);
  assert_options(ASSERT_CALLBACK,   null);
}
else {
  assert_options(ASSERT_ACTIVE,     0);
  assert_options(ASSERT_WARNING,    0);
  assert_options(ASSERT_BAIL,       0);
  assert_options(ASSERT_QUIET_EVAL, 0);
  assert_options(ASSERT_CALLBACK,   null);
}


?>
