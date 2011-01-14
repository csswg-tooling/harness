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
define('DB_HOST', '');
define('DB_USER', 'testharness');
define('DB_PASSWORD', 'r3B7xzXyPJZJ8pS7');
define('DB_NAME', 'testharness');


/**
 * Contact info
 */
define('CONTACT_URI', 'http://lists.w3.org/Archives/Public/public-css-testsuite');
define('CONTACT_NAME', 'public-css-testsuite@w3.org');

/**
 * Spider trap config
 *
 * URL to use for spider trap
 * Number of visits to trigger ban (within test period)
 * Period to test for spider behavior (days)
 * Period to ban activity (days)
 * Shell commands to ban & release spiders
 * Post process command to run after ban/release
 */
define('SPIDER_TRAP_URL', 'report');
define('SPIDER_BAN_THRESHOLD', 2);
define('SPIDER_TEST_PERIOD', 3);
define('SPIDER_BAN_PERIOD', 7);
define('SPIDER_BAN_COMMAND', '/sbin/iptables -I INPUT -s {ip} -j DROP');
define('SPIDER_RELEASE_COMMAND', '/sbin/iptables --delete INPUT -s {ip} -j DROP');
define('SPIDER_POST_PROCESS_COMMAND', '/sbin/iptables-save > /etc/firewall.conf');


/**
 * Max field lengths for database tables
 */
define('RESULTS_MAX_SOURCE', 16);

define('SPIDERTRAP_MAX_IP', 15);
define('SPIDERTRAP_MAX_USER_AGENT', 255);
define('SPIDERTRAP_MAX_URI', 255);

define('TESTCASES_MAX_URI', 255);
define('TESTCASES_MAX_TESTSUITE', 32);
define('TESTCASES_MAX_TESTCASE', 64);
define('TESTCASES_MAX_TITLE', 255);
define('TESTCASES_MAX_ASSERTION', 1023);
define('TESTCASES_MAX_TESTGROUP', 32);

define('TESTLINKS_MAX_TITLE', 255);
define('TESTLINKS_MAX_URI', 255);

define('TESTSEQUENCE_MAX_ENGINE', 16);

define('TESTSUITES_MAX_TESTSUITE', 32);
define('TESTSUITES_MAX_BASE_URI', 255);
define('TESTSUITES_MAX_HOME_URI', 64);
define('TESTSUITES_MAX_SPEC_URI', 255);
define('TESTSUITES_MAX_TITLE', 255);
define('TESTSUITES_MAX_SEQUENCE_QUERY', 32);

define('USERAGENTS_MAX_USERAGENT', 255);
define('USERAGENTS_MAX_ENGINE', 16);
define('USERAGENTS_MAX_ENGINE_VERSION', 16);
define('USERAGENTS_MAX_BROWSER', 32);
define('USERAGENTS_MAX_BROWSER_VERSION', 16);
define('USERAGENTS_MAX_PLATFORM', 32);


/**
 * Debug / Command line setup
 */
if ((defined('DEBUG_MODE') && DEBUG_MODE) || 
    (defined('COMMAND_LINE') && COMMAND_LINE)) {
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