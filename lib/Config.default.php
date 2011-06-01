<?php
/*******************************************************************************
 *
 *  Copyright © 2011 Hewlett-Packard Development Company, L.P. 
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


/**
 * System Info
 */
Config::SetDebugMode(FALSE);


/**
 * Database configuration
 */
Config::Set('db.host', 'localhost');
Config::Set('db.user', 'testharness');
Config::Set('db.password', '');
Config::Set('db.database', 'testharness');


/**
 * Server info
 */
Config::Set('server.time_zone' , 'America/Los_Angeles');
Config::Set('server.rewrite_urls', TRUE); // if mod_rewrite rules in force
Config::Set('server.install_uri', 'http://test.w3.org/harness');


/**
 * Contact info
 */
if (empty($_SERVER['SERVER_ADMIN'])) {
  Config::Set('contact.uri', 'mailto:webmaster@example.com');
}
else {
  Config::Set('contact.uri', 'mailto:' . $_SERVER['SERVER_ADMIN']);
}
Config::Set('contact.name', 'the server administrator');


/**
 * URIs used within the harness
 */
Config::Set('uri.page.home', './');
Config::Set('uri.page.testsuite', 'testsuite');
Config::Set('uri.page.start', 'teststart');
Config::Set('uri.page.testcase', 'testcase');
Config::Set('uri.page.select_ua', 'useragent');
Config::Set('uri.page.set_ua', 'setuseragent');
Config::Set('uri.page.review', 'reviewresults');
Config::Set('uri.page.load_results', 'loadresults');
Config::Set('uri.page.results', 'resulttable');
Config::Set('uri.page.details', 'detailtable');
Config::Set('uri.page.submit', 'submit');
Config::Set('uri.page.success', 'success');
Config::Set('uri.page.status_query', 'status.php');
Config::Set('uri.page.spider_trap', 'report');

/**
 * Stylesheet URIs used within the harness
 */
Config::Set('uri.stylesheet.annotation', 'stylesheets/annotate.css');
Config::Set('uri.stylesheet.base', 'stylesheets/base.css');
Config::Set('uri.stylesheet.report', 'stylesheets/report.css');
Config::Set('uri.stylesheet.test', 'stylesheets/testcase.css');
Config::Set('uri.stylesheet.test_engine', 'stylesheets/test_%s.css');
Config::Set('uri.stylesheet.testsuite', 'stylesheets/testsuite.css');

/**
 * Image URIs used within the harness
 */
Config::Set('uri.image.please_help', 'img/please_help_32.png');


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
Config::Set('spider.ban_threshold', 2);
Config::Set('spider.test_period', 3);
Config::Set('spider.ban_period', 7);
Config::Set('spider.ban_command', '/sbin/iptables -I INPUT -s {ip} -j DROP');
Config::Set('spider.release_command', '/sbin/iptables --delete INPUT -s {ip} -j DROP');
Config::Set('spider.post_process_command', '/sbin/iptables-save > /etc/firewall.conf');


/**
 * Max field lengths for database tables
 */
Config::Set('db.max.flags.flag', 15);
Config::Set('db.max.flags.description', 255);
Config::Set('db.max.flags.set_test', 255);
Config::Set('db.max.flags.unset_test', 255);

Config::Set('db.max.formats.format', 15);
Config::Set('db.max.formats.title', 255);
Config::Set('db.max.formats.description', 255);
Config::Set('db.max.formats.home_uri', 255);
Config::Set('db.max.formats.filter', 127);

Config::Set('db.max.references.format', 15);
Config::Set('db.max.references.reference', 255);
Config::Set('db.max.references.uri', 255);

Config::Set('db.max.results.revision', 40);
Config::Set('db.max.results.format', 15);
Config::Set('db.max.results.comment', 63);

Config::Set('db.max.revisions.revision', 40);
Config::Set('db.max.revisions.equal_revision', 40);

Config::Set('db.max.sources.source', 63);

Config::Set('db.max.specifications.spec', 31);
Config::Set('db.max.specifications.title', 31);
Config::Set('db.max.specifications.description', 255);
Config::Set('db.max.specifications.base_uri', 255);
Config::Set('db.max.specifications.home_uri', 63);

Config::Set('db.max.speclinks.spec', 31);
Config::Set('db.max.speclinks.section', 31);
Config::Set('db.max.speclinks.title', 255);
Config::Set('db.max.speclinks.uri', 255);

Config::Set('db.max.statuscache.testsuite', 31);

Config::Set('db.max.spidertrap.ip', 39);
Config::Set('db.max.spidertrap.user_agent', 255);
Config::Set('db.max.spidertrap.uri', 255);

Config::Set('db.max.suitetests.testsuite', 31);
Config::Set('db.max.suitetests.revision', 40);

Config::Set('db.max.testcases.testcase', 63);
Config::Set('db.max.testcases.last_revision', 40);
Config::Set('db.max.testcases.title', 255);
Config::Set('db.max.testcases.flags', 255);
Config::Set('db.max.testcases.assertion', 1023);
Config::Set('db.max.testcases.credits', 255); // XXX break out into table

Config::Set('db.max.testpages.format', 15);
Config::Set('db.max.testpages.uri', 255);

Config::Set('db.max.testsequence.testsuite', 31);
Config::Set('db.max.testsequence.engine', 15);

Config::Set('db.max.testsuites.testsuite', 31);
Config::Set('db.max.testsuites.base_uri', 255);
Config::Set('db.max.testsuites.home_uri', 63);
Config::Set('db.max.testsuites.spec_uri', 255);
Config::Set('db.max.testsuites.spec', 31);
Config::Set('db.max.testsuites.title', 255);
Config::Set('db.max.testsuites.formats', 127);
Config::Set('db.max.testsuites.optional_flags', 127);
Config::Set('db.max.testsuites.contact_name', 63);
Config::Set('db.max.testsuites.contact_uri', 255);

Config::Set('db.max.useragents.useragent', 255);
Config::Set('db.max.useragents.engine', 15);
Config::Set('db.max.useragents.engine_version', 15);
Config::Set('db.max.useragents.browser', 31);
Config::Set('db.max.useragents.browser_version', 15);
Config::Set('db.max.useragents.platform', 31);
Config::Set('db.max.useragents.platform_version', 31);

?>