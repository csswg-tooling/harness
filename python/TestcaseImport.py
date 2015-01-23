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

import os
import collections

from harness import db, testcases

from core import utils

import HTMLParser

# add process module to the system path
utils.addModulePath('process', 'testsuite')
from process import systemprocess, events
from testsuite import testsuite


ReferenceData = collections.namedtuple('ReferenceData', ('name', 'type', 'path'))

class Importer(db.HarnessDBConnection):
    @classmethod
    def GetTableNames(cls):
        names = super(Importer, cls).GetTableNames()
        return names + ['suite_tests', 'status_cache', 'test_spec_links', 'test_pages', 'reference_pages', 'test_sequence']

    def __init__(self, ui):
        db.HarnessDBConnection.__init__(self)
        self.ui = ui
        self.testcases = testcases.Testcases()
        
    def __del__(self):
        self.testcases.close()
        del self.testcases

    def lockTables(self):
        dbClasses = (Importer, testcases.Testcases, testsuite.TestSuites)
        db.HarnessDBConnection.StartTransaction(dbClasses)
        db.HarnessDBConnection.WriteLockTables(dbClasses)
    
    def unlockTables(self):
        dbClasses = (Importer, testcases.Testcases, testsuite.TestSuites)
        db.HarnessDBConnection.UnlockTables(dbClasses)
        db.HarnessDBConnection.Commit(dbClasses)
        
        
    def joinPath(self, path, name, extension):  # for uris, so hardcode '/'
        if (path and ('/' != path[-1]) and name and ('/' != name[0])):
            path += '/'
        if (extension and ('.' != extension[0])):
            extension = '.' + extension
        return path + name + extension
        
    
    def importTests(self, testSuite, manifest):
        self.lockTables()
    
        importDate = self.getNow()
        testSuiteName = testSuite.getName()

        self.query("DELETE FROM `suite_tests` "
                   "WHERE `test_suite` = %s ",
                   (testSuiteName, )).close()

        self.query("DELETE FROM `status_cache` "
                   "WHERE `test_suite` = %s ",
                   (testSuiteName, )).close()

        self.query("DELETE FROM `test_spec_links` "
                   "WHERE `test_suite` = %s ",
                   (testSuiteName, )).close()

        self.query("DELETE FROM `test_pages` "
                   "WHERE `test_suite` = %s ",
                   (testSuiteName, )).close()

        self.query("DELETE FROM `reference_pages` "
                   "WHERE `test_suite` = %s ",
                   (testSuiteName, )).close()

        self.query("DELETE FROM `test_sequence` "
                   "WHERE `test_suite` = %s ",
                   (testSuiteName, )).close()

        suiteFormats = testSuite.getFormats()

        for data in manifest:
            testPath = data['id']
            testcaseName = os.path.basename(testPath)
            revision = data['revision']
            flags = set(utils.splitStripAndFilter(',', data['flags']))
            referenceGroups = utils.splitStripAndFilter(';', data['references'])
            references = []
            for referenceGroup in referenceGroups:
                referencePaths = utils.splitStripAndFilter(',', referenceGroup)
                groupData = []
                for referencePath in referencePaths:
                    flags.add('reftest')
                    type = '=='
                    if ('!' == referencePath[0]):
                        type = '!='
                        referencePath = referencePath[1:]
                    groupData.append(ReferenceData(os.path.basename(referencePath).lower(), type, referencePath))
                if (groupData):
                    references.append(groupData)
        
            links = utils.splitStripAndFilter(',', data['links'])
            credits = utils.splitStripAndFilter(',', data['credits'])
            
            self.ui.note("Adding testcase: ", testcaseName, " revision: ", revision, "\n")
            testcase = self.testcases.addTestcase(testPath, revision, references, data['title'], flags, links,
                                                  credits, data['assertion'], importDate)

            self.query("INSERT INTO `suite_tests` "
                       "  (`test_suite`, `testcase_id`, `revision`) "
                       "VALUES (%s, %s, %s) ",
                       (testSuiteName, testcase.getId(), revision)).close()

            for formatName in suiteFormats:
                format = suiteFormats[formatName]
                if (format.validForFlags(flags)):
                    uri = self.joinPath(format.getPath(), testPath, format.getExtension())
                    self.query("INSERT INTO `test_pages` "
                               "  (`testcase_id`, `test_suite`, `format`, `uri`) "
                               "VALUES (%s, %s, %s, %s) ",
                               (testcase.getId(), testSuiteName, formatName, uri)).close()

                    for referenceGroup in references:
                        for reference in referenceGroup:
                            uri = self.joinPath(format.getPath(), reference.path, format.getExtension())
                            self.query("INSERT INTO `reference_pages` "
                                       "  (`testcase_id`, `test_suite`, `reference`, `format`, `uri`) "
                                       "VALUES (%s, %s, %s, %s, %s) "
                                       "ON DUPLICATE KEY UPDATE `uri` = %s ",
                                       (testcase.getId(), testSuiteName, reference.name, formatName, uri, uri)).close()
        
        self.execute("DELETE FROM `status_cache` "
                     "WHERE `test_suite` = %s ",
                     (testSuiteName, )).close()

        testSuite.setBuildDate(importDate)
        
        self.unlockTables()


class TestcaseImport(systemprocess.SystemProcess):

    def _getUsage(self):
        return "usage: %prog [options] manifest_file test_suite_name [new_suite_path old_suite_path] "

    def _lockName(self):
        return 'testcase'

    def _loadManifest(self, filePath):
        with open(filePath) as manifestFile:
            header = manifestFile.readline()
            if ("id\treferences\ttitle\tflags\tlinks\trevision\tcredits\tassertion\n" != header):
                self.ui.status("Unknown manifest format\n")
                return None
            fields = header.strip('\n').split('\t')
            manifest = []
            _htmlparser = HTMLParser.HTMLParser()
            for line in manifestFile.readlines():
                data = dict(zip(fields, line.strip('\n').split('\t')))
                data['title'] = _htmlparser.unescape(data['title'])
                data['assertion'] = _htmlparser.unescape(data['assertion'])
                data['credits'] = _htmlparser.unescape(data['credits'])
                manifest.append(data)
            return manifest
        self.ui.manifest("Unable to read manifest\n")
        return None


    def run(self):
        if (len(self.args) < 2):
            self.ui.status(self._getUsage(), "\n")
            return
        
        manifestFilePath, testSuiteName = self.args[0:2]
        
        testSuites = testsuite.TestSuites()
        testSuite = testSuites.getTestSuite(testSuiteName)
        if (not testSuite):
            self.ui.status("Unknown test suite\n")
            return
        
        manifest = self._loadManifest(manifestFilePath)
        if (manifest is None):
            return

        importer = Importer(self.ui)
        importer.importTests(testSuite, manifest)
        importer.close()
        del importer

        events.Events.QueueEvent('suite-resync', options = { '--force': True }, arguments = testSuite.getSpecNames())



if __name__ == "__main__":      # called from the command line
    TestcaseImport.Run()

