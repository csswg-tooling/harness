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

from core import db

class HarnessDBSchema(db.DBSchema):
    def getSchemaVersion(self):
        return 7

    def getSchemaGeneration(self):
        return 2

class HarnessDBConnection(db.DBConnection):
    @classmethod
    def GetDBSchema(cls):
        return HarnessDBSchema()

