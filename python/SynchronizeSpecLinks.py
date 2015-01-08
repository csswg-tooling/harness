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

import sys
import os
import datetime

from core import config, userinterface, utils
from harness import db


# add specification and process module to the system path
utils.addModulePath('process', 'specification', 'testsuite')

from process import systemprocess
from specification import specifications
from testsuite import db as testsuitedb
from testsuite import testsuite


class SynchronizeSpecLinks(db.HarnessDBConnection):
    @classmethod
    def GetTableNames(cls):
        names = super(SynchronizeSpecLinks, cls).GetTableNames()
        return names + ['spec_sync_data', 'test_help_links', 'test_spec_links', 'suite_tests', 'status_cache']

    def __init__(self, ui):
        db.HarnessDBConnection.__init__(self)
        
        self.mUI = ui
        self.mSpecs = specifications.Specifications()
        self.mTestSuites = testsuite.TestSuites()
        self.mSyncDates = None
        self.mSpecs.preloadData()
        self.mTestSuites.preloadData()

    def __del__(self):
        self.mSpecs.close()
        del self.mSpecs
        self.mTestSuites.close()
        del self.mTestSuites
        
    def lockTables(self):
        dbClasses = (SynchronizeSpecLinks, )
        db.HarnessDBConnection.StartTransaction(dbClasses)
        db.HarnessDBConnection.WriteLockTables(dbClasses)
    
    def unlockTables(self):
        dbClasses = (SynchronizeSpecLinks, )
        db.HarnessDBConnection.UnlockTables(dbClasses)
        db.HarnessDBConnection.Commit(dbClasses)

    def _loadSyncDates(self):
        if (None == self.mSyncDates):
            self.mSyncDates = dict()
            cursor = self.query("SELECT * "
                                "FROM `spec_sync_data` ")
            for data in cursor:
                self.mSyncDates[data['spec'].lower()] = data['sync_date']
            cursor.close()

    def _getSyncDateTime(self, specName):
        self._loadSyncDates()
        return self.mSyncDates.get(specName.lower())
  
    def setSyncDateTime(self, specName, syncDateTime):
        if (syncDateTime):
            self._loadSyncDates()
            self.mSyncDates[specName.lower()] = syncDateTime
            self.execute("INSERT INTO `spec_sync_data` "
                         "  (`spec`, `sync_date`) "
                         "VALUES (%s, %s) "
                         "ON DUPLICATE KEY UPDATE `sync_date` = %s ",
                         (specName, unicode(syncDateTime), unicode(syncDateTime))).close()
  
    def _synchronizeSpec(self, specName, force):
        specSyncDate = self.mSpecs.getSyncDateTime(specName)
        if (specSyncDate):
            syncDate = self._getSyncDateTime(specName)
            if (syncDate and (syncDate == specSyncDate) and (not force)):
                self.mUI.status("Spec ", specName, " links in sync\n")
                return
        else:
            self.mUI.status("Spec ", specName, " never synchronized\n")
            return
    
        self.mUI.status("Updating testcase specification links for ", specName, "\n")

        # delete spec links
        self.mUI.debug("Deleting existing spec links\n")
        self.execute("DELETE FROM `test_spec_links` "
                     "WHERE `spec` = %s ",
                     (specName, )).close()
      
        class UsedAnchors(object):
            def __init__(self):
                self.mAnchors = dict()
            def add(self, specName, parentName, anchorName):
                if (specName not in self.mAnchors):
                    self.mAnchors[specName] = dict()
                if (parentName not in self.mAnchors[specName]):
                    self.mAnchors[specName][parentName] = set()
                self.mAnchors[specName][parentName].add(anchorName)
            def isUsed(self, specName, parentName, anchorName):
                if ((specName in self.mAnchors) and (parentName in self.mAnchors[specName])):
                    return (anchorName in self.mAnchors[specName][parentName])
                return False


        specURI = self.mSpecs.getSpecURI(specName)
        draftURI = self.mSpecs.getDraftURI(specName)

        if (specURI or draftURI):
            # look for known anchors and link
            self.mUI.debug("Linking tests\n")
            sql = ("SELECT `test_help_links`.*, `suite_tests`.`test_suite` "
                   "FROM `test_help_links` "
                   "INNER JOIN (`suite_tests`) "
                   "  ON `test_help_links`.`testcase_id` = `suite_tests`.`testcase_id` "
                   "  AND `test_help_links`.`revision` = `suite_tests`.`revision` ")
            if (specURI):
                if (draftURI):
                    cursor = self.query(sql +
                                        "  AND (`test_help_links`.`uri` LIKE %s OR `test_help_links`.`uri` LIKE %s) ",
                                        (specURI + '%', draftURI + '%'))
                else:
                    cursor = self.query(sql +
                                        "  AND `test_help_links`.`uri` LIKE %s ",
                                        (specURI + '%', ))
            else:
                cursor = self.query(sql +
                                    "  AND `test_help_links`.`uri` LIKE %s ",
                                    (draftURI + '%', ))

            usedAnchors = dict()
            testcaseLinks = dict()
            for data in cursor:
                testcaseId = data['testcase_id']
                testSuite = self.mTestSuites.getTestSuite(data['test_suite'])
                if (not testSuite.getActive()):
                    continue
                testSuiteName = intern(data['test_suite'].encode('utf-8'))
                specURI = data['uri']
                linkSequence = data['sequence']
          
                if (testcaseId not in usedAnchors):
                    usedAnchors[testcaseId] = dict()
                    testcaseLinks[testcaseId] = dict()
                if (testSuiteName not in usedAnchors[testcaseId]):
                    usedAnchors[testcaseId][testSuiteName] = UsedAnchors()
                    testcaseLinks[testcaseId][testSuiteName] = []
              
                anchorSpecName, anchorData = self.mSpecs.getAnchorData(specURI)
                if ((anchorSpecName == specName) and (specName in testSuite.getSpecNames())):
                    for specType in ('official', 'draft'):
                        if (anchorData and (specType in anchorData)):
                            parentName, anchorName, structure = anchorData[specType]
                            if (anchorName and
                                (not usedAnchors[testcaseId][testSuiteName].isUsed(specName, parentName, anchorName))):
                                testcaseLinks[testcaseId][testSuiteName].append((specType, parentName, linkSequence, structure))
                                usedAnchors[testcaseId][testSuiteName].add(specName, parentName, anchorName)
                                self.execute("INSERT INTO `test_spec_links` "
                                             "  (`testcase_id`, `test_suite`, `spec`, `spec_type`, `parent_name`, `anchor_name`, `type`, `sequence`) "
                                             "VALUES (%s, %s, %s, %s, %s, %s, %s, %s) ",
                                             (testcaseId, testSuiteName, specName, specType, parentName if parentName else '', anchorName,
                                              'direct', linkSequence)).close()
            cursor.close()

            # add parent spec links
            self.mUI.debug("Creating group links\n")
            for testcaseId in testcaseLinks:
                for testSuiteName in testcaseLinks[testcaseId]:
                    for specType, parentName, linkSequence, structure in testcaseLinks[testcaseId][testSuiteName]:
                        anchorName = parentName
                        parentName = self.mSpecs.getAnchorParentName(parentName)
                        linkType = 'group' if ('section' == structure) else 'section'
                        while (anchorName and (not usedAnchors[testcaseId][testSuiteName].isUsed(specName, parentName, anchorName))):
                            usedAnchors[testcaseId][testSuiteName].add(specName, parentName, anchorName)
                            self.execute("INSERT INTO `test_spec_links` "
                                         "  (`testcase_id`, `test_suite`, `spec`, `spec_type`, `parent_name`, `anchor_name`, `type`, `sequence`) "
                                         "VALUES (%s, %s, %s, %s, %s, %s, %s, %s) ",
                                         (testcaseId, testSuiteName, specName, specType, parentName if parentName else '', anchorName,
                                          linkType, linkSequence)).close()
                            anchorName = parentName
                            parentName = self.mSpecs.getAnchorParentName(parentName)
                            linkType = 'group'

        self.setSyncDateTime(specName, self.mSpecs.getSyncDateTime(specName))
        self.execute("DELETE FROM `status_cache` "
                     "WHERE `spec` = %s ",
                     (specName, )).close()


    def synchronizeSpec(self, specName, force):
        self.lockTables()

        if (self.mSpecs.getSpecURI(specName) or self.mSpecs.getDraftURI(specName)):
            self._synchronizeSpec(specName, force)
        else:
            self.mUI.warn("ERROR: Unknown specification: ", specName, "\n")
      
        self.unlockTables()
  
  
    def synchronizeAll(self, force):
        self.lockTables()
        specNames = self.mSpecs.getAllSpecNames()
        specNames.sort()
        for specName in specNames:
            self._synchronizeSpec(specName, force)
      
        self.unlockTables()


class SyncProcess(systemprocess.SystemProcess):
    def _getUsage(self):
        return "usage: %prog [options] [SpecName]"

    def _addOptions(self, parser):
        systemprocess.SystemProcess._addOptions(self, parser)
        parser.add_option("-f", "--force",
                          action = "store_true", dest = "force", default = False,
                          help = "sync even if not modified")

    def _lockName(self):
        return 'testcase'
  
    def run(self):
        sync = SynchronizeSpecLinks(self.ui)

        if (len(self.args)):
            for specName in self.args:
                sync.synchronizeSpec(specName, self.options.force)
        else:
            sync.synchronizeAll(self.options.force)
        sync.close()
        del sync


if __name__ == "__main__":      # called from the command line
    SyncProcess.Run()


