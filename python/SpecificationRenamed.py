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

class SpecificationRenamed(systemprocess.SystemProcess):

    def _getUsage(self):
        return "usage: %prog [options] OldSpecName NewSpecName "


    def run(self):
        if (1 < len(self.args)):
            oldSpecName, newSpecName = self.args[0:2]
            harnessDB = db.HarnessDBConnection()

            harnessDB.execute("UPDATE `test_spec_links` "
                              "SET `spec` = %s "
                              "WHERE `spec` = %s ",
                              (newSpecName, oldSpecName)).close()

            harnessDB.execute("UPDATE `status_cache` "
                              "SET `spec` = %s "
                              "WHERE `spec` = %s ",
                              (newSpecName, oldSpecName)).close()

            harnessDB.execute("UPDATE `spec_sync_data` "
                              "SET `spec` = %s "
                              "WHERE `spec` = %s ",
                              (newSpecName, oldSpecName)).close()


if __name__ == "__main__":      # called from the command line
    SpecificationRenamed.Run()

