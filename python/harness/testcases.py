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

import os, collections

import db
from testcase import Testcase

class Testcases(db.HarnessDBConnection):
    @classmethod
    def GetTableNames(cls):
        names = super(Testcases, cls).GetTableNames()
        return names + ['testcases', 'references', 'test_help_links', 'revisions']

    def __init__(self, testSuiteName = None, allRevisions = False):
        db.HarnessDBConnection.__init__(self)
        if (testSuiteName):
            if (allRevisions):
                cursor = self.query("SELECT * "
                                    "FROM `testcases` "
                                    "LEFT JOIN (`suite_tests`, `revisions`) "
                                    "  ON `testcases`.`id` = `suite_tests`.`testcase_id` "
                                    "  AND `testcases`.`id` = `revisions`.`testcase_id` "
                                    "  AND `testcases`.`revision` = `revisions`.`revision` "
                                    "WHERE `suite_tests`.`test_suite` = %s "
                                    "ORDER BY `testcase`, `revisions`.`date` ",
                                    (testSuiteName, ))
            else:
                cursor = self.query("SELECT * "
                                    "FROM `testcases` "
                                    "LEFT JOIN `suite_tests` "
                                    "  ON `testcases`.`id` = `suite_tests`.`testcase_id` "
                                    "  AND `testcases`.`revision` = `suite_tests`.`revision` "
                                    "WHERE `suite_tests`.`test_suite` = %s "
                                    "ORDER BY `testcase` ",
                                    (testSuiteName, ))
        else:
            cursor = self.query("SELECT * "
                                "FROM `testcases` "
                                "ORDER BY `testcase` ")

        self.testcasesById = {}
        self.testcasesByName = {}

        for data in cursor:
            testcase = Testcase(data = data)
            self._addTestcase(testcase)
        cursor.close()
        

    def _addTestcase(self, testcase):
        id = testcase.getId()
        name = testcase.getName().lower()
        revision = testcase.getRevision()
        if (id not in self.testcasesById):
            self.testcasesById[id] = {}
        self.testcasesById[id][revision] = testcase
        if (name not in self.testcasesByName):
            self.testcasesByName[name] = collections.OrderedDict()
        self.testcasesByName[name][revision] = testcase
    
    
    def getTestcases(self, name):
        return self.testcasesByName.get(name.lower())


    def getTestcase(self, name, revision = None):
        name = name.lower()
        if (name in self.testcasesByName):
            if (revision):
                return self.testcasesByName[name].get(revision)
            return self.testcasesByName[name].itervalues().next()
        return None


    def addTestcase(self, path, revision, references, title, flags, links, credits, assertion, date):
        name = os.path.basename(path).lower()
        flagString = (',' + ','.join(flags) + ',') if (flags) else ''
        creditString = ','.join(credits)
        if (name in self.testcasesByName):
            if (revision in self.testcasesByName[name]):
                testcase = self.testcasesByName[name][revision]
                if (True): # XXX update metadata
                    id = testcase.getId()
                    self.query("UPDATE `testcases` "
                               "SET `title` = %s, `flags` = %s, `assertion` = %s, `credits` = %s "
                               "WHERE `id` = %s AND `revision` = %s ",
                               (title, flagString, assertion, creditString, id, revision)).close()
                    self.query("DELETE FROM `references` "
                               "WHERE `testcase_id` = %s AND `revision` = %s ",
                               (id, revision)).close()
                    self.query("DELETE FROM `test_help_links` "
                               "WHERE `testcase_id` = %s AND `revision` = %s ",
                               (id, revision)).close()
                else:
                    return testcase
            else:
                testcase = self.testcasesByName[name].itervalues().next()
                id = testcase.getId()
                self.query("INSERT INTO `testcases` "
                           "  (`id`, `revision`, `testcase`, `title`, `flags`, `assertion`, `credits`) "
                           "VALUES (%s, %s, %s, %s, %s, %s, %s) ",
                           (id, revision, name, title, flagString, assertion, creditString)).close()
        else:
            cursor = self.query("INSERT INTO `testcases` "
                       "  (`revision`, `testcase`, `title`, `flags`, `assertion`, `credits`) "
                       "VALUES (%s, %s, %s, %s, %s, %s) ",
                       (revision, name, title, flagString, assertion, creditString))
            id = cursor.lastrowid
            cursor.close()
        data = { 'id': id, 'revision': revision, 'testcase': name, 'title': title, 'flags': flagString,
                 'assertion': assertion, 'credits': creditString }
        testcase = Testcase(data = data)
        self._addTestcase(testcase)

        self.query("INSERT INTO `revisions` "
                   "  (`testcase_id`, `revision`, `equal_revision`, `date`) "
                   "VALUES (%s, %s, %s, %s) "
                   "ON DUPLICATE KEY UPDATE `date` = `date` ",
                   (id, revision, 0, date)).close()

        groupIndex = -1
        for referenceGroup in references:
            groupIndex += 1
            sequence = -1
            for reference in referenceGroup:
                sequence += 1
                self.query("INSERT INTO `references` "
                           "  (`testcase_id`, `revision`, `reference`, `type`, `group`, `sequence`) "
                           "VALUES (%s, %s, %s, %s, %s, %s) "
                           "ON DUPLICATE KEY UPDATE `type` = %s, `sequence` = %s ",
                           (id, revision, reference.name, reference.type, groupIndex, sequence, reference.type, sequence)).close()

        sequence = -1
        for link in links:
            sequence += 1
            self.query("INSERT INTO `test_help_links` "
                       "  (`testcase_id`, `revision`, `uri`, `sequence`) "
                       "VALUES (%s, %s, %s, %s) ",
                       (id, revision, link, sequence)).close()

        return testcase



