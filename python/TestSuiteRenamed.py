#!/usr/bin/env python
# coding=utf-8
#
#  Copyright © 2014 Hewlett-Packard Development Company, L.P.
#
#  This work is distributed under the W3C® Software License [1]
#  in the hope that it will be useful, but WITHOUT ANY
#  WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
#
#  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231
#

from harness import db

from core import utils
# add process module to the system path
utils.addModulePath('process')
from process import systemprocess

class TestSuiteRenamed(systemprocess.SystemProcess):

    def _getUsage(self):
        return "usage: %prog [options] OldSuiteName NewSuiteName "


    def run(self):
        if (1 < len(self.args)):
            oldSuiteName, newSuiteName = self.args[0:2]
            harnessDB = db.HarnessDBConnection()

            harnessDB.execute("UPDATE `status_cache` "
                              "SET `test_suite` = %s "
                              "WHERE `test_suite` = %s ",
                              (newSuiteName, oldSuiteName)).close()

            harnessDB.execute("UPDATE `suite_tests` "
                           "SET `test_suite` = %s "
                           "WHERE `test_suite` = %s ",
                           (newSuiteName, oldSuiteName)).close()

            harnessDB.execute("UPDATE `test_pages` "
                           "SET `test_suite` = %s "
                           "WHERE `test_suite` = %s ",
                           (newSuiteName, oldSuiteName)).close()

            harnessDB.execute("UPDATE `reference_pages` "
                           "SET `test_suite` = %s "
                           "WHERE `test_suite` = %s ",
                           (newSuiteName, oldSuiteName)).close()

            harnessDB.execute("UPDATE `test_sequence` "
                           "SET `test_suite` = %s "
                           "WHERE `test_suite` = %s ",
                           (newSuiteName, oldSuiteName)).close()

            harnessDB.execute("UPDATE `test_spec_links` "
                           "SET `test_suite` = %s "
                           "WHERE `test_suite` = %s ",
                           (newSuiteName, oldSuiteName)).close()


if __name__ == "__main__":      # called from the command line
    TestSuiteRenamed.Run()

