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

class TestSuiteDeleted(systemprocess.SystemProcess):

    def _getUsage(self):
        return "usage: %prog [options] TestSuiteName "


    def run(self):
        if (len(self.args)):
            suiteName = self.args[0]
            harnessDB = db.HarnessDBConnection()

            harnessDB.execute("DELETE FROM `status_cache` "
                              "WHERE `test_suite` = %s ",
                              (suiteName, )).close()

            harnessDB.execute("DELETE FROM `suite_tests` "
                              "WHERE `test_suite` = %s ",
                              (suiteName, )).close()

            harnessDB.execute("DELETE FROM `test_pages` "
                              "WHERE `test_suite` = %s ",
                              (suiteName, )).close()

            harnessDB.execute("DELETE FROM `reference_pages` "
                              "WHERE `test_suite` = %s ",
                              (suiteName, )).close()

            harnessDB.execute("DELETE FROM `test_sequence` "
                              "WHERE `test_suite` = %s ",
                              (suiteName, )).close()

            harnessDB.execute("DELETE FROM `test_spec_links` "
                              "WHERE `test_suite` = %s ",
                              (suiteName, )).close()


if __name__ == "__main__":      # called from the command line
    TestSuiteDeleted.Run()

