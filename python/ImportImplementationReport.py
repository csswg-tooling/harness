#!/usr/bin/env python
# coding=utf-8
#
#  Copyright © 2015 Hewlett-Packard Development Company, L.P.
#
#  This work is distributed under the W3C® Software License [1]
#  in the hope that it will be useful, but WITHOUT ANY
#  WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
#
#  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231
#

import os
import collections
import datetime

from harness import db, testcases

from core import utils, users

# add process module to the system path
utils.addModulePath('process', 'testsuite', 'useragent')
from process import systemprocess
from testsuite import testsuite
from useragent import useragent

ResultData = collections.namedtuple('ResultData', ('testcase', 'format', 'revision', 'result', 'comment'))

class Importer(db.HarnessDBConnection):
    @classmethod
    def GetTableNames(cls):
        names = super(Importer, cls).GetTableNames()
        return names + ['revisions', 'results', 'status_cache']

    def __init__(self, ui, testSuite, dateTime, user, ua):
        db.HarnessDBConnection.__init__(self)
        self.ui = ui
        self.testSuite = testSuite
        self.dateTime = dateTime
        self.user = user
        self.ua = ua
        self.suiteTestcases = testcases.Testcases(testSuite.getName())
        self.testcases = testcases.Testcases(testSuite.getName(), True)
        
    def __del__(self):
        self.testSuite.close()
        self.ua.close()
        self.suiteTestcases.close()
        self.testcases.close()
        del self.testSuite
        del self.ua
        del self.suiteTestcases
        del self.testcases

    def lockTables(self):
        dbClasses = (Importer, testcases.Testcases, testsuite.TestSuites, useragent.UserAgent)
        db.HarnessDBConnection.StartTransaction(dbClasses)
        db.HarnessDBConnection.WriteLockTables(dbClasses)
    
    def unlockTables(self):
        dbClasses = (Importer, testcases.Testcases, testsuite.TestSuites, useragent.UserAgent)
        db.HarnessDBConnection.UnlockTables(dbClasses)
        db.HarnessDBConnection.Commit(dbClasses)
        

    def _loadReport(self, filePath):
        allowedResults = set(['pass', 'fail', 'uncertain', 'invalid'])
        formatNames = { 'html4': 'html4', 'html': 'html5', 'xhtml1': 'xhtml1' }
        with open(filePath) as reportFile:
            header = reportFile.readline()
            lineNumber = 1
            while (header and header.strip().startswith('#')):
                header = reportFile.readline()
                lineNumber += 1
            header = header.strip()
            if ((not header) or 
                (("testname\tresult\tcomment" != header) and 
                 ("testname\trevision\tresult\tcomment" != header))):
                self.ui.status("Unknown report format\n")
                return None
            haveRevision = ("testname\trevision\tresult\tcomment" == header)
            fields = utils.splitAndStrip(header.strip('\n'), '\t')
            report = []
            for line in reportFile.readlines():
                lineNumber += 1
                line = line.strip()

                if (line.startswith('#')):
                    continue

                if (haveRevision):
                    testcasePath, revision, result, comment = (line + '\t\t\t').split('\t')[0:4]
                else:
                    testcasePath, result, comment = (line + '\t\t').split('\t')[0:3]
                    revision = None

                formatPath, testcaseName = os.path.split(testcasePath.lower())
                testcaseName, testcaseExtension = os.path.splitext(testcaseName)

                if (formatPath not in formatNames):
                    self.ui.status('Unknown format: ', formatPath, ' on line ', lineNumber, '\n')
                    return None

                formatName = formatNames[formatPath]

                if (revision):
                    testcase = self.testcases.getTestcase(testcaseName, revision)
                    if (not testcase):
                        if (self.suiteTestcases.getTestcase(testcaseName)):
                            self.ui.status('Unknown revision: ', revision, ' for testcase: ', testcaseName, ' on line ', lineNumber, '\n')
                        else:
                            self.ui.status('Unknown testcase: ', testcaseName, ' on line ', lineNumber, '\n')
                        return None
                else:
                    testcase = self.suiteTestcases.getTestcase(testcaseName)
                    if (not testcase):
                        self.ui.status('Unknown testcase: ', testcaseName, ' on line ', lineNumber, '\n')
                        return None
                    if (self.dateTime < testcase.getAddedDateTime()):
                        self.ui.status('Testcase: ', testcaseName, ' not present in test suite on date, line number ', lineNumber, '\n')
                        return None
                    if (self.dateTime < testcase.getDateTime()):    # revision not available on date
                        testcaseRevisions = self.testcases.getTestcases(testcaseName)
                        lastRevision = None
                        for revision in testcaseRevisions:
                            if (self.dateTime < testcaseRevisions[revision].getDateTime()):
                                break
                            lastRevision = revision
                        revision = lastRevision
                        testcase = testcaseRevisions[revision]
                    else:
                        revision = testcase.getRevision()

                format = self.testSuite.getFormats().get(formatName)
                if (not format):
                    self.ui.status('Format ', formatName, ' not available in test suite on line ', lineNumber, '\n')
                    return None

                if (not format.validForFlags(testcase.getFlags())):
                    self.ui.status('Testcase ', testcaseName, ' not available in format ', formatName, ' on line ', lineNumber, '\n')
                    return None

                result = result.lower()
                if (('?' == result) or ('na' == result)):
                    continue
                if (result not in allowedResults):
                    self.ui.status('Unknown result ', result, ' on line ', lineNumber, '\n')
                    return None

                report.append(ResultData(testcase, format, revision, result, comment))
            return report
        self.ui.status("Unable to read report\n")
        return None


    def importResults(self, reportFilePath):
        report = self._loadReport(reportFilePath)
        if (not report):
            return False

        self.lockTables()

        for data in report:
            self.ui.debug('Adding result ', data.result, ' for ', data.format.getName(), '/', data.testcase.getName(), '\n')
            self.execute("UPDATE `results` "
                         "SET `ignore` = 1 "
                         "WHERE `testcase_id` = %s "
                         "  AND `revision` = %s "
                         "  AND `format` = %s "
                         "  AND `user_agent_id` = %s "
                         "  AND `user_id` = %s "
                         "  AND  `modified` = %s ",
                         (data.testcase.getId(), data.revision, data.format.getName(), self.ua.getId(), self.user.getId(), self.dateTime)).close()
            self.execute("INSERT INTO `results` "
                         "  (`testcase_id`, `revision`, `format`, `user_agent_id`, `user_id`, "
                         "   `user_user_agent_id`, `result`, `comment`, `modified`) "
                         "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s) ",
                         (data.testcase.getId(), data.revision, data.format.getName(), self.ua.getId(), self.user.getId(),
                          self.ua.getId(), data.result, data.comment, self.dateTime)).close()
    
        self.ui.status(len(report), ' test results imported\n')

        self.execute("TRUNCATE TABLE `status_cache`").close()

        self.unlockTables()
        return True


class ImportImplementationReport(systemprocess.SystemProcess):

    def _getUsage(self):
        return "usage: %prog [options] report_file test_suite_name iso_date_utc user_name ua_string "

    def _lockName(self):
        return 'testcase'

    def run(self):
        self.exitCode = 1
        if (len(self.args) < 5):
            self.ui.status(self._getUsage(), "\n")
            return
        
        reportFilePath, testSuiteName, dateTimeString, userName, uaString = self.args[0:5]
        
        testSuites = testsuite.TestSuites()
        testSuite = testSuites.getTestSuite(testSuiteName)
        if (not testSuite):
            self.ui.status("Unknown test suite\n")
            return
        
        ua = useragent.UserAgent.GetUserAgent(uaString)
        if (not ua):
            self.ui.status("Unknown user agent\n")
            return

        user = users.User(userName = userName)
        if (not user.getId()):
            self.ui.status("Unknown user\n")
            return

        try:
            dateTime = datetime.datetime.strptime(dateTimeString, '%Y-%m-%dT%H:%M:%S')
        except:
            self.ui.status("Invalid date format\nExpecting: YYYY-MM-DDTHH:MM:SS\n")
            return

        importer = Importer(self.ui, testSuite, dateTime, user, ua)
        if (importer.importResults(reportFilePath)):
            self.exitCode = 0
        importer.close()
        del importer



if __name__ == "__main__":      # called from the command line
    ImportImplementationReport.Run()

