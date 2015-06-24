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

import datetime

import db
from core import utils


class Testcase(db.HarnessDBConnection):

    def __init__(self, data = None):
        db.HarnessDBConnection.__init__(self)
        self.data = data if data else {}

    def getId(self):
        return self.data.get('id')
    
    def getName(self):
        return self.data.get('testcase')

    def getRevision(self):
        return self.data.get('revision')

    def getFlags(self):
        if ('flags' in self.data):
            return set(utils.splitStripAndFilter(',', self.data['flags']))
        return set()

    def getDateTime(self):
        if ('date' in self.data):
            return datetime.datetime.strptime(self.data['date'], '%Y-%m-%d %H:%M:%S')
        return None

    def getAddedDateTime(self):
        if ('date_added' in self.data):
            return datetime.datetime.strptime(self.data['date_added'], '%Y-%m-%d %H:%M:%S')
        return None
