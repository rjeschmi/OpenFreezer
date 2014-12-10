#!/usr/local/bin/python

# python modules
import cgi
import cgitb; cgitb.enable()

import SocketServer
from SocketServer import BaseRequestHandler

import MySQLdb

import os
import tempfile
import stat
import sys
import string

# Custom modules
import utils

from database_conn import DatabaseConn
#from session import Session

from container import Container
from general_handler import GeneralHandler
from lab_handler import LabHandler

from mapper import *

#################################################################################
# Top-level abstraction for LocationHandler hierarchy; extends GeneralHandler
#
# Written September 18, 2007 by Marina Olhovsky
# Last modified: Sept. 18/07
#################################################################################

class LocationTypeHandler(GeneralHandler):
	"Database handler functions for Container Types (e.g. MiniPrep Plates, Liquid Nitrogen Containers, etc.)"

	def __init__(self, db, cursor):
		super(LocationTypeHandler, self).__init__(db, cursor)



	# Jan. 3/09
	def findContainerTypeProperties(self, contTypeID):
	
		db = self.db
		cursor = self.cursor	# for easy access
		
		contTypeAttrs = []
		
		cursor.execute("SELECT p.propertyName FROM PrepElemTypes_tbl p, ContainerTypeAttributes_tbl c WHERE c.containerTypeID=" + `contTypeID` + " AND c.containerTypeAttributeID=p.elementTypeID AND c.status='ACTIVE' and p.status='ACTIVE'")
		
		results = cursor.fetchall()
		
		for result in results:
			contTypeAttrs.append(result[0])
			
		return contTypeAttrs
		

	# Find the internal database ID of container type identified by contType
	# E.g. contType = 'Vector', contTypeID = '1'
	# Input: contType: STRING
	# Returns: INT, corresponds to contGroupID column value in ContainerGroup_tbl
	def containerTypeToID(self, contType):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT contGroupID FROM ContainerGroup_tbl WHERE contGroupName=" + `contType` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			contTypeID = int(result[0])
			return contTypeID
		
		return 0
	
	
	# Dec. 21/09 - Create container type - simply make an entry in ContainerGroup_tbl
	def insertContainerType(self, contGroupName, isoActive, contGroupCode):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("INSERT INTO ContainerGroup_tbl(contGroupName, isolate_active, contGroupCode) VALUES(" + `contGroupName` + ", " + `isoActive` + ", " + `contGroupCode` + ")")
		
		return int(db.insert_id())
		
	
	# Get the 2-letter code matching cTypeID (e.g. 'VE' for Vectors (cTypeID=1), 'GS' for Glycerol Stock (cTypeID=2), etc.)
	# Returns the value of contGroupCode column in ContainerGroup_tbl
	def findContainerTypeCode(self, cTypeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT contGroupCode FROM ContainerGroup_tbl WHERE contGroupID=" + `cTypeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0]
		
		return ""
	

	def findContainerTypeName(self, cTypeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT contGroupName FROM ContainerGroup_tbl WHERE contGroupID=" + `cTypeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0]
		
		return ""
	
	
	# Find the internal database ID of an entry in ContainerTypeID_tbl that corresponds to contSize
	# E.g. contSize = '96-well plate'; result: '1'
	# Input: contSize: STRING, verbal description of available container types in OpenFreezer
	# Returns: INT, value of contTypeID column in ContainerTypeID_tbl
	def containerSizeToID(self, contSize):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT contTypeID FROM ContainerTypeID_tbl WHERE containerName=" + `contSize` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			contSizeID = int(result[0])
			return contSizeID
		
		return 0

	
	# Find the actual number of wells/slots in the container; do it by multiplying the number of columns by the number of rows
	def findNumContainerSamples(self, contSizeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		numCols = self.findNumCols(contSizeID)
		numRows = self.findNumRows(contSizeID)
		
		numWells = numCols * numRows
		
		return numWells
		
	
	# Find the number of columns in a container
	def findNumCols(self, contSizeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		numCols = 0
		
		cursor.execute("SELECT maxCol FROM ContainerTypeID_tbl WHERE contTypeID=" + `contSizeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			numCols = int(result[0])
			
		return numCols
		
		
	# Find the number of rows in a container
	def findNumRows(self, contSizeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		numRows = 0
		
		cursor.execute("SELECT maxRow FROM ContainerTypeID_tbl WHERE contTypeID=" + `contSizeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			numRows = int(result[0])
			
		return numRows


	# Finds if this container type allows storing different isolates of the same reagent/prep
	# Input: contTypeID: INT, corresponds to contGroupID column in ContainerGroup_tbl
	# Returns: YES or NO, depending on whether the container type is isolate active or not
	def isIsoActive(self, contTypeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		isoActive = ""
		
		cursor.execute("SELECT isolate_active FROM ContainerGroup_tbl WHERE contGroupID=" + `contTypeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:	# which better be!
			isoActive = result[0]
			
		return isoActive
		
		
	# Jan. 13, 2010
	def updateContainerType(self, contTypeID, contGroupName, contGroupCode, isoActive, newContTypeFeatures, reagentTypes=[]):
		
		#print "Content-type:text/html"
		#print
		#print "New features: " + `newContTypeFeatures`
		#print `reagentTypes`
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		pMapper = PrepPropertyMapper(db, cursor)
		
		prepProp_ID_Name_Map = pMapper.mapPrepPropIDToName()
		prepProp_Name_ID_Map = pMapper.mapPrepPropNameToID()
		
		# Get old values and compare.  If different, update container type name, container type code and attributes
		cursor.execute("UPDATE ContainerGroup_tbl SET contGroupName=" + `contGroupName` + " WHERE contGroupID=" + `contTypeID` + " AND status='ACTIVE'")
		
		cursor.execute("UPDATE ContainerGroup_tbl SET contGroupCode=" + `contGroupCode` + " WHERE contGroupID=" + `contTypeID` + " AND status='ACTIVE'")
		
		# Feb. 16/10: update isolate active state if available
		if isoActive.upper() != self.findIsolateActive(contTypeID):
			cursor.execute("UPDATE ContainerGroup_tbl SET isolate_active=" + `isoActive.upper()` + " WHERE contGroupID=" + `contTypeID` + " AND status='ACTIVE'")
		
		# grab features and add if required
		currContTypeFeatures = self.findContainerTypeProperties(contTypeID)
	
		#print "Current features: " + `currContTypeFeatures`
		
		# delete old and add new
		for curr_fName in currContTypeFeatures:
			cfID = prepProp_Name_ID_Map[curr_fName]
			
			if not curr_fName in newContTypeFeatures:
				self.deleteContainerTypeFeature(contTypeID, cfID)

		for fName in newContTypeFeatures:
			#if prepProp_Name_ID_Map.has_key(fName):
				#fID = prepProp_Name_ID_Map[fName]
				
			if not fName in currContTypeFeatures:
				self.addContainerTypeFeature(contTypeID, fName)
			#else:
				#newPropID = self.addPrepProperty(fName)
				#self.addContainerTypeFeature(contTypeID, fName)
		
		self.updateContainerReagentTypes(contTypeID, reagentTypes)
		
	
	# Feb. 16/10: find the isolate active state of a container type
	def findIsolateActive(self, contTypeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT isolate_active FROM ContainerGroup_tbl WHERE contGroupID=" + `contTypeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:	# should be
			return result[0]
		
		return "NO"
		
	
	def updateContainerReagentTypes(self, contTypeID, reagentTypes):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		# June 22, 2010: Delete and re-insert
		self.deleteContainerReagentTypes(contTypeID)
		
		#currReagentTypes = self.getContainerReagentTypes(contTypeID)	# list of rTypeIDs
		
		for rType in reagentTypes:
			#if rType not in currReagentTypes:
			self.addContainerReagentType(contTypeID, rType)
		
	
	def deleteContainerReagentTypes(self, contTypeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE ContainerReagentTypes_tbl SET status='DEP' WHERE contTypeID=" + `contTypeID` + " AND status='ACTIVE'")
		
	
	def addContainerReagentType(self, contTypeID, rTypeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("INSERT INTO ContainerReagentTypes_tbl(contTypeID, reagentTypeID) VALUES(" + `contTypeID` + ", " + `rTypeID` + ")")
		
		
	
	def getContainerReagentTypes(self, contTypeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		rTypes = []
		
		cursor.execute("SELECT reagentTypeID FROM ContainerReagentTypes_tbl WHERE contTypeID=" + `contTypeID` + " AND status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			rTypeID = int(result[0])
			rTypes.append(rTypeID)
			
		return rTypes

	
	# Jan. 14, 2010: Adding a new attribute to the list (like 'Other')
	def addPrepProperty(self, propName):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("INSERT INTO PrepElemTypes_tbl(propertyName, PrepElementDesc) VALUES(" + `propName` + ", " + `propName` + ")")
		return int(db.insert_id())

	
	# Jan. 14, 2009
	def deleteContainerTypeFeature(self, contTypeID, cfID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE ContainerTypeAttributes_tbl SET status='DEP' WHERE containerTypeID=" + `contTypeID` + " AND containerTypeAttributeID=" + `cfID` + " AND status='ACTIVE'")


	# Jan. 13, 2010
	def addContainerTypeFeature(self, contTypeID, propName):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		pMapper = PrepPropertyMapper(db, cursor)
		
		prepProp_Name_ID_Map = pMapper.mapPrepPropNameToID()
		
		if prepProp_Name_ID_Map.has_key(propName):
			propID = prepProp_Name_ID_Map[propName]
		else:
			propID = self.addPrepProperty(propName)
		
		cursor.execute("INSERT INTO ContainerTypeAttributes_tbl(containerTypeID, containerTypeAttributeID) VALUES(" + `contTypeID` + ", " + `propID` + ")")
	
	
	# Jan. 14, 2010: fList is a list of feature IDs
	def addContainerTypeFeatures(self, contTypeID, fList):
		db = self.db
		cursor = self.cursor	# for easy access
		
		for fID in fList:
			self.addContainerTypeFeature(contTypeID, fID)
	
	# Jan. 12, 2010
	def deleteContainerType(self, contTypeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		# delete container type and attributes - deprecate entries in ContainerGroup_tbl and ContainerTypeAttributes_tbl
		cursor.execute("UPDATE ContainerTypeAttributes_tbl SET status='DEP' WHERE containerTypeID=" + `contTypeID`)
		cursor.execute("UPDATE ContainerGroup_tbl SET status='DEP' WHERE contGroupID=" + `contTypeID`)
		
		# Feb. 9/10: delete links to reagent types allowed for this container
		cursor.execute("UPDATE ContainerReagentTypes_tbl SET status='DEP' WHERE contTypeID=" + `contTypeID`)
		
		
	# Feb. 25/10: delete association between container type and reagent type
	# delete links to reagent types allowed for this container
	# Used at reagent type deletion
	def deleteContainerReagentType(self, rTypeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		## Feb. 9/10: delete links to reagent types allowed for this container
		cursor.execute("UPDATE ContainerReagentTypes_tbl SET status='DEP' WHERE reagentTypeID=" + `rTypeID`)