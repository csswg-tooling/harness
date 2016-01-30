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

class TestFormatRenamed(systemprocess.SystemProcess):

    def _getUsage(self):
        return "usage: %prog [options] OldFormatName NewFormatName "


    def run(self):
        if (len(self.args)):
            oldFormatName, newFormatName = self.args[0:2]
            harnessDB = db.HarnessDBConnection()

            harnessDB.execute("UPDATE `reference_pages` "
                              "SET `format` = %s "
                              "WHERE `format` = %s ",
                              (newFormatName, oldFormatName)).close()

            harnessDB.execute("UPDATE `test_pages` "
                              "SET `format` = %s "
                              "WHERE `format` = %s ",
                              (newFormatName, oldFormatName)).close()




if __name__ == "__main__":      # called from the command line
    TestFormatRenamed.Run()

