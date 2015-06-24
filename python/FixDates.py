#!/usr/bin/env python
# coding=utf-8

import sys, os, re, datetime, collections

from harness import db, testcases
from core import utils

import HTMLParser

# add process module to the system path
utils.addModulePath('process', 'testsuite')
from process import systemprocess, events
from testsuite import testsuite


ReferenceData = collections.namedtuple('ReferenceData', ('name', 'type', 'path'))

def _print(string):
    sys.stdout.write(string)
    sys.stdout.flush()

def _loadManifest(filePath):
    with open(filePath) as manifestFile:
        header = manifestFile.readline()
        if ("id\treferences\ttitle\tflags\tlinks\trevision\tcredits\tassertion\n" != header):
            _print("\nUnknown manifest format\n")
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
    return None


if __name__ == "__main__":      # called from the command line
    harness = db.HarnessDBConnection()

    testSuites = testsuite.TestSuites()
    testcases = testcases.Testcases()

    harness.execute("UPDATE `suite_tests` "
                    "SET `date_added` = 0 ").close()

    for dirName, dirs, files in os.walk('/sites/test.csswg.org/manifests'):
        testSuiteName = os.path.basename(dirName)
        if (testSuites.getTestSuite(testSuiteName)):
            _print("\n" + testSuiteName + "\n")
            added = False
            for fileName in sorted(files):
                match = re.match('testinfo-([0-9]{4})-([0-9]{2})-([0-9]{2}).data', fileName)
                if (match):
                    if (added):
                        _print("\n")
                        added = False
                    _print("\rProcessing: " + fileName)
                    filePath = os.path.join(dirName, fileName)
                    stat = os.stat(filePath)
                    dateTime = datetime.datetime.utcfromtimestamp(stat.st_mtime)
                    manifest = _loadManifest(filePath)
                    if (not manifest):
                        continue 
                    for data in manifest:
                        testPath = data['id']
                        testcaseName = os.path.basename(testPath)
                        if (not testcaseName):
                            continue
                        revision = data['revision']
                        testcase = testcases.getTestcase(testcaseName, revision)
                        if (not testcase):
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
                            
                            _print("\nAdding testcase: " + testcaseName + " revision: " + revision)
                            added = True
                            testcase = testcases.addTestcase(testPath, revision, references, data['title'], flags, links,
                                                             credits, data['assertion'], dateTime)
                            continue

                        harness.execute("UPDATE `suite_tests` "
                                        "SET `date_added` = %s "
                                        "WHERE `test_suite` = %s "
                                        "  AND `testcase_id` = %s "
                                        "  AND `revision` = %s "
                                        "  AND `date_added` = 0 ",
                                        (dateTime, testSuiteName, testcase.getId(), testcase.getRevision())).close() 

                        harness.execute("UPDATE `revisions` "
                                        "SET `date` = %s "
                                        "WHERE `testcase_id` = %s "
                                        "  AND `revision` = %s "
                                        "  AND `date` > %s ",
                                        (dateTime, testcase.getId(), testcase.getRevision(), dateTime)).close()
            _print("\n")
    harness.close()