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


/**
 * Example minimum local configuration file
 *
 * Edit values as appropriate for your install and save file as Config.local.php
 * somewhere in your PHP include path (or in the harness/lib directory)
 */
 
//Config::SetDebugMode(TRUE);

/**
 * Database configuration
 */
Config::Set('db.host', 'localhost');
Config::Set('db.user', 'testharness');
Config::Set('db.password', 'password');
Config::Set('db.database', 'testharness');


/**
 * Server info
 */
Config::Set('server.time_zone' , 'America/Los_Angeles');
Config::Set('server.rewrite_urls', TRUE); // if mod_rewrite rules in force
Config::Set('server.install_uri', 'http://test.csswg.org/harness');


/**
 * Contact Info
 */
Config::Set('contact.uri', 'http://lists.w3.org/Archives/Public/public-css-testsuite');
Config::Set('contact.name', 'public-css-testsuite@w3.org');
 
?>
