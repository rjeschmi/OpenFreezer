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
from well import Well					# June 8, 2011
from prep import PrepProperty				# June 9, 2011
from general_handler import GeneralHandler
from lab_handler import LabHandler

from location_type_database_handler import LocationTypeHandler

#################################################################################
# Top-level abstraction for LocationHandler hierarchy; extends GeneralHandler
#
# Written September 18, 2007 by Marina Olhovsky
# Last modified: Sept. 18/07
#################################################################################

class LocationHandler(GeneralHandler):
	"Database handler functions to update information related to Containers"
	
	def __init__(self, db, cursor):
		super(LocationHandler, self).__init__(db, cursor)

	
	####################################################################################################################################
	# Insert a new Container_tbl entry into the database; also generate a barcode for that container
	#
	# Input: cLab: INT, represents **internal lab database ID**
	# Return: new container database ID
	#
	# Note: Table and column naming conventions are not always obvious, for historical reasons.  Original names assigned to Container-related tables have been preserved in subsequent releases, but their meaning has since changed.  Current Container-related representations are:
	#
	# ContainerGroup_tbl: 
	# 	Describes the type of reagents stored in the container, e.g. 'Vector', 'Insert', 'Oligo', 'Cell Lines'.  One exception is 'Glycerol Stock', which stores Vectors, but has been made into a separate container type category for easy viewing.
	#
	# ContainerTypeID_tbl: 
	# 	Describes container **sizes** - e.g. '96-well plate', '81-slot box', '100-slot box', etc.
	#
	#
	# At the time this function was written, table structure has been modified as follows:
	#
	# 'isolate_active' column has been removed from Container_tbl and added to ContainerGroup_tbl
	# 'barcode' column was added to Container_tbl, to serve as as unique container identifier for OpenFreezer users
	# 'storage_type' is an INT, representing locationID column in LocationTypes_tbl
	#
	# Written Sept. 18/07, by Marina Olhovsky
	# Last modified: Jan. 3, 2010 by Marina
	#
	####################################################################################################################################
	def insertContainer(self, contTypeID, cSize, cName, cDesc, cLab, storage_type, storage_name, storage_address, cont_shelf, cont_rack, cont_row, cont_col):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		ltHandler = LocationTypeHandler(db, cursor)
		
		# find 'container type' and 'container size' foreign keys
		#contTypeID = ltHandler.containerTypeToID(cType)
		cType = ltHandler.findContainerTypeName(contTypeID)
		contSizeID = ltHandler.containerSizeToID(cSize)
		
		# find the next available number in this container group to assign to the new container
		contNum = self.findNextContainerNumberInGroup(contTypeID)
		
		# check if this is an isolate active container
		isoActive = ltHandler.isIsoActive(contTypeID)

		# generate barcode
		bcNum = self.findNextContainerBarcodeNumber(contTypeID, contSizeID, cLab)
		barcode = self.generateBarcode(contTypeID, contSizeID, bcNum, cLab)
		
		cursor.execute("INSERT INTO Container_tbl(contGroupID, contTypeID, contGroupCount, name, container_desc, labID, barcode, location, locationName, address, shelf, rack, row_number, col_number) VALUES(" + `contTypeID` + ", " + `contSizeID` + ", " + `contNum` + ", " + `cName` + ", " + `cDesc` + ", " + `cLab` + ", " + `barcode` + ", " + `storage_type` + ", " + `storage_name` + ", " + `storage_address` + ", " + `cont_shelf` + ", " + `cont_rack` + ", " + `cont_row` + ", " + `cont_col` + ")")
		
		newContID = int(db.insert_id())
		
		# Store container-specific properties
		nRows = ltHandler.findNumRows(contSizeID)
		nCols = ltHandler.findNumCols(contSizeID)
		
		container = Container(cType, cSize, nRows, nCols, cName, cLab, cDesc)
		
		#props = container.getContainerProperties()
		props = ltHandler.findContainerTypeProperties(contTypeID)
		
		#print "Content-type:text/html"
		#print
		#print storage_type
		
		#print `props`
		self.insertContainerProperties(newContID, props)
		
		return newContID
		
	
	# Find the type of container contID
	# Returns: STRING, value of column contGroupName in ContainerGroup_tbl, which can be easily matched to groupID with the help of containerTypeToID function if necessary
	def findContainerType(self, contID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT contGroupName FROM ContainerGroup_tbl g, Container_tbl c WHERE c.containerID=" + `contID` + " AND c.contGroupID=g.contGroupID AND c.status='ACTIVE' AND g.status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0]
			
		return ""
		

	def findLocationTypeID(self, storage_type):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT locationTypeID FROM LocationTypes_tbl WHERE locationTypeName=" + `storage_type` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		
		return -1
		
	
	# Dec. 21/09
	# contFeatures is a list of property IDs
	def addContainerTypeFeatures(self, contTypeID, contFeatures):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		for elTypeID in contFeatures:
			cursor.execute("INSERT INTO ContainerTypeAttributes_tbl(containerTypeID, containerTypeAttributeID) VALUES(" + `contTypeID` + ", " + `elTypeID` + ")")
	
		
	# Insert container-specific properties
	def insertContainerProperties(self, contID, props):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		for pName in props:
			elTypeID = self.getPrepElementTypeID(pName)
			cursor.execute("INSERT INTO Prep_Req_tbl(prepElementTypeID, containerID, requirement) VALUES(" + `elTypeID` + ", " + `contID` + ", 'REQ')")
	
	
	# Fetch the internal database IDs of container-specific properties, such as 'Method ID', 'Bacteria Strain', etc.
	def getPrepElementTypeID(self, pName):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT elementTypeID FROM PrepElemTypes_tbl WHERE propertyName=" + `pName` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		
	
	# Find the name of the given container
	def findContainerName(self, contID):
	
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT name FROM Container_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0]
		
		return ""
		
		
	# Find container description
	def findContainerDescription(self, contID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT container_desc FROM Container_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0]
		
		return ""
		
	
	# Find the container's serial number in its group
	# Returns: contGroupCount value
	def findContainerNumber(self, contID):
		
		db = self.db
		cursor = self.cursor	# for easy access
	
		cursor.execute("SELECT contGroupCount FROM Container_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			contNum = int(result[0])
			
		return contNum
		
	
	# Find the highest number of containers of the given type
	def findNextContainerNumberInGroup(self, cTypeID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		# initialize count to 1
		nextContNum = 1
		
		cursor.execute("SELECT MAX(contGroupCount) FROM Container_tbl WHERE contGroupID=" + `cTypeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			if result[0]:
				nextContNum = int(result[0]) + 1
			else:
				nextContNum = 1
		else:
			nextContNum = 1
			
		return nextContNum
		
	
	# Find the lab ID of the given container
	def findContainerLabID(self, contID):
	
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT labID FROM Container_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		
		return 0
		
		
	############################################################################################################################
	# Generate a barcode for a plate with the given parameters
	#
	# A barcode has the following format:
	# 	2-letter lab code (e.g. PW, WR, GN, DN, etc.)
	# 	Container size (number of wells/slots in the container - e.g. 96, 81, 100, etc.)
	# 	2 letters representing container type (VE, GS, IN, OL, CL)
	# 	Container number - integer - incremented by 1 every time you have the same reagent type (e.g., Vector), the same lab (eg Pawson), and the same number of samples (e.g., 96)
	#
	# Input:
	# 	cType: INT, internal database identifier representing the container category (Vector, Insert, Glycerol Stock, etc.)
	# 	cSize: INT, internal database identifier representing the container type (96-well plate, 81-slot box, etc.)
	# 	contNum: INT, the serial number of the container within that category
	# 	cLab: INT, the internal database identifier of the lab that owns the container
	#
	# Output: STRING
	############################################################################################################################
	def generateBarcode(self, cTypeID, cSizeID, contNum, cLab):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		# Get the lab code that corresponds to lab ID
		lHandler = LabHandler(db, cursor)
		labCode = lHandler.findLabCode(cLab)
		
		ltHandler = LocationTypeHandler(db, cursor)
		
		# Find the container size
		contSize = ltHandler.findNumContainerSamples(cSizeID)
		
		# Find the container group code
		contTypeCode = ltHandler.findContainerTypeCode(cTypeID)

		# Produce a string barcode
		barcode = labCode + `contSize` + contTypeCode + `contNum`
		
		return barcode
	
	
	# Find the highest number of containers of the given type, size and lab
	def findNextContainerBarcodeNumber(self, cTypeID, cSizeID, cLab):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		nextNum = 0
		
		# Select all containers in the given category
		cursor.execute("SELECT COUNT(containerID) FROM Container_tbl WHERE contGroupID=" + `cTypeID` + " AND contTypeID=" + `cSizeID` + " AND labID=" + `cLab` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		return int(result[0]) + 1
		
	
	# Find the barcode value of a container
	def findContainerBarcode(self, contID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT barcode FROM Container_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0]
		
		return ""
	
	
	# Update container properties
	# Input:
	# contType: STRING
	# contSize: STRING
	def updateContainerInfo(self, contID, contType, contSize, contName, contDesc, contLab, storage_type, storage_name, storage_address, cont_shelf, cont_rack, cont_row, cont_col):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		ltHandler = LocationTypeHandler(db, cursor)
		
		contTypeID = ltHandler.containerTypeToID(contType)
		contSizeID = ltHandler.containerSizeToID(contSize)
		
		# Compare old container properties to new values and only update those that have actually changed
		oldContType = self.findContainerType(contID)
		oldContSize = self.findContainerSize(contID)
		oldContName = self.findContainerName(contID)
		oldContDesc = self.findContainerDescription(contID)
		oldLabID = self.findContainerLabID(contID)
		oldBarcode = self.findContainerBarcode(contID)
		

		# Update barcode IFF container type, lab or size were changed!!!!
		if oldBarcode == '' or oldContType != contType or oldContSize != contSize or oldLabID != contLab:

			# only in this case recompute barcode
			contNum = self.findNextContainerBarcodeNumber(contTypeID, contSizeID, contLab)
			
			newBarcode = self.generateBarcode(contTypeID, contSizeID, contNum, contLab)
			self.updateContainerBarcode(contID, newBarcode)
		
		# Update the rest of the container properties if old and new values differ:
		if contType != oldContType:
			self.updateContainerType(contID, contType)
		
		if contSize != oldContSize:
			self.updateContainerSize(contID, contSize)
		
		if contName != oldContName:
			self.updateContainerName(contID, contName)
	
		if contDesc != oldContDesc:
			self.updateContainerDescription(contID, contDesc)
		
		if contLab != oldLabID:
			self.updateContainerLab(contID, contLab)
	
		# NO.  Update Container page is different from Update Location.  This function is used to update container details - name, description, lab.  If user wants to move a container to a different fridge, they do so through Modify Location - that's a differnet function
		#self.updateContainerLocation(contID, storage_type, storage_name, cont_shelf, cont_rack, cont_row, cont_col)
		
	
	
	# Find the container type and size (96-well plate, etc.)
	def findContainerSize(self, contID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT t.containerName FROM Container_tbl c, ContainerTypeID_tbl t WHERE c.containerID=" + `contID` + " AND c.contTypeID=t.contTypeID AND c.status='ACTIVE' AND t.status='ACTIVE'")
		
		result = cursor.fetchone()
		
		if result:
			return result[0]
			
	def updateContainerLocation(self, contID, storage_type, storage_name, storage_address, cont_shelf, cont_rack, cont_row, cont_col):
		
		oldStorageType = self.findContainerStorageTypeID(contID)
		oldStorageName = self.findContainerStorageTypeName(contID)
		oldStorageAddress = self.findContainerStorageAddress(contID)
		oldShelf = self.findContainerShelf(contID)
		oldRack = self.findContainerRack(contID)
		oldRow = self.findContainerRow(contID)
		oldColumn = self.findContainerColumn(contID)
		
		if oldStorageType != storage_type:
			self.updateContainerStorageType(contID, storage_type)
			
		if oldStorageName != storage_name:
			self.updateContainerStorageName(contID, storage_name)
	
		if oldStorageAddress != storage_address:
			self.updateContainerStorageLocation(contID, storage_address)
	
		if oldShelf!= cont_shelf:
			self.updateContainerShelf(contID, cont_shelf)
			
		if oldRack != cont_rack:
			self.updateContainerRack(contID, cont_rack)
	
		if oldRow != cont_row:
			self.updateContainerRow(contID, cont_row)
	
		if oldColumn != cont_col:
			self.updateContainerColumn(contID, cont_col)
			
		
	# Jan. 6, 2010: Returns INT corresponding to locationTypeID column value in Container_tbl
	def findContainerStorageTypeID(self, contID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT location FROM Container_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		
		return 0
		
	
	# Jan. 6, 2010
	def findContainerStorageTypeName(self, contID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT locationName FROM Container_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0]
		
		return None
	
	
	# Jan. 6, 2010
	def findContainerStorageAddress(self, contID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT address FROM Container_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0]
		
		return None
	
	
	# Jan. 6, 2010
	def findContainerShelf(self, contID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT shelf FROM Container_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			try:
				return int(result[0])
			except TypeError:
				return 0
		
		return 0
	
	
	# Jan. 6, 2010
	def findContainerRack(self, contID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT rack FROM Container_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			try:
				return int(result[0])
			except TypeError:
				return 0
		
		return 0
	
	
	# Jan. 6, 2010
	def findContainerRow(self, contID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT row_number FROM Container_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			try:
				return int(result[0])
			except TypeError:
				return 0
		
		return 0
	
	
	# Jan. 6, 2010
	def findContainerColumn(self, contID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT col_number FROM Container_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			try:
				return int(result[0])
			except TypeError:
				return 0
		
		
		return 0
	
	def updateContainerStorageType(self, contID, storageTypeID):	
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Container_tbl SET location=" + `storageTypeID` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		
	
	def updateContainerStorageName(self, contID, storageName):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Container_tbl SET locationName=" + `storageName` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
	
	
	def updateContainerStorageLocation(self, contID, storage_address):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Container_tbl SET address=" + `storage_address` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
	
	
	def updateContainerShelf(self, contID, shelf):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Container_tbl SET shelf=" + `shelf` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
	
	
	def updateContainerRack(self, contID, rack):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Container_tbl SET rack=" + `rack` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
	
	
	def updateContainerRow(self, contID, row):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Container_tbl SET row_number=" + `row` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
	
	
	def updateContainerColumn(self, contID, column):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Container_tbl SET col_number=" + `column` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
	
	
	# Delete a particular container property (set corresponding column value to blank)
	def deleteContainerProperty(self, contID, propColName):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Container_tbl SET " + propColName + " = '' WHERE containerID=" + `contID` + " AND status='ACTIVE'")
	
	
	# When container group is changed (an empty container is moved from Vectors to Inserts), update corresponding properties for this container type
	def updateContainerProperties(self, contID, cType):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		# Clear current properties first
		cursor.execute("UPDATE Prep_Req_tbl SET status='DEP' WHERE containerID='" + `contID` + "' AND status='ACTIVE'")
		
		container = Container(cType)
		props = container.getContainerProperties()
		
		self.insertContainerProperties(contID, props)
		
	
	# Change the type of a container
	# Input: contType - STRING, verbal container type representation
	def updateContainerType(self, contID, contType):
		
		db = self.db
		cursor = self.cursor	# for easy access
	
		ltHandler = LocationTypeHandler(db, cursor)
		
		contTypeID = ltHandler.containerTypeToID(contType)

		# container number needs to be updated too, since the container was moved into a new group
		contNum = self.findNextContainerNumberInGroup(contTypeID)
		cursor.execute("UPDATE Container_tbl SET contGroupCount=" + `contNum` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")

		# AND container-type specific properties need to be changed too
		self.updateContainerProperties(contID, contType)
		
		# Finally change container type
		cursor.execute("UPDATE Container_tbl SET contGroupID=" + `contTypeID` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		
		
	## Update the isolate active state of a container
	#def updateIsoActive(self, contID, newIsoActive):
		
		#db = self.db
		#cursor = self.cursor	# for easy access
		
		
	# Change the size of a container
	def updateContainerSize(self, contID, contSize):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		ltHandler = LocationTypeHandler(db, cursor)
		
		contSizeID = ltHandler.containerSizeToID(contSize)
		
		cursor.execute("UPDATE Container_tbl SET contTypeID=" + `contSizeID` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		
	
	# Change the name of a container
	def updateContainerName(self, contID, contName):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Container_tbl SET name=" + `contName` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		
		
	# Change container description
	def updateContainerDescription(self, contID, contDesc):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Container_tbl SET container_desc=" + `contDesc` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
	
	
	# Change container's lab ID
	def updateContainerLab(self, contID, contLab):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Container_tbl SET labID=" + `contLab` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		
		
	# Change container's barcode
	def updateContainerBarcode(self, contID, barcode):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Container_tbl SET barcode=" + `barcode` + " WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		
	
	# New Feb. 12/09: Add list of container features
	def addContainerFeatures(self, contID, features):
		db = self.db
		cursor = self.cursor	# for easy access
		
		#print "Content-type:text/html"
		#print
		#print `newContFeatures`
		
		for f in features:
			#print "SELECT elementTypeID FROM PrepElemTypes_tbl WHERE propertyName=" + `f`
			cursor.execute("SELECT elementTypeID FROM PrepElemTypes_tbl WHERE propertyName=" + `f`)
			result = cursor.fetchone()
			
			if result:
				fNamePropID = int(result[0])
			else:
				# add this feature
				cursor.execute("INSERT INTO PrepElemTypes_tbl(propertyName, PrepElementDesc) VALUES(" + `f` + ", " + `f` + ")")
				fNamePropID = int(db.insert_id())
				
			cursor.execute("INSERT INTO Prep_Req_tbl(`prepElementTypeID`, `containerID`, `requirement`) VALUES(" + `fNamePropID` + ", " + `contID` + ", 'REQ')")
			
			
	# June 8/09: Delete location of prep identified by expID
	# Multi-step process:
	#
	# (->rID) delete from Experiment_tbl (->expID)
	# 	(->expID) delete from Isolate_tbl (->isolate_pk)
	# 		(->isolate_pk) delete from Prep_tbl (->wellID, ->prepID)
	# 			(->wellID) delete from Wells_tbl
	# 			(->prepID) delete from PrepElementProp_tbl
	def deleteLocation(self, expID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		isolates = self.findIsolates(expID)
		
		for isolateID in isolates: 
			preps = self.findPreps(isolateID)
			
			for prepID in preps:
				wellID = self.findWellID(prepID)
				
				self.deleteWell(wellID)
				self.deletePrepProperties(prepID)
				self.deletePrep(prepID)
				self.deleteIsolate(isolateID)
				self.deleteExperiment(expID)
			
	
	def findIsolates(self, expID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		isolates = []
		
		cursor.execute("SELECT isolate_pk FROM Isolate_tbl WHERE expID=" + `expID` + " AND status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			isolates.append(int(result[0]))
			
		return isolates
		
	
	def findPreps(self, isolateID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		preps = []
		
		cursor.execute("SELECT prepID FROM Prep_tbl WHERE isolate_pk =" + `isolateID` + " AND status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			preps.append(int(result[0]))
			
		return preps
		
		
	def findWellID(self, prepID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		wellID = 0
		
		cursor.execute("SELECT wellID FROM Prep_tbl WHERE prepID=" + `prepID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			wellID = int(result[0])
			
		return wellID
		
	
	def deleteWell(self, wellID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Wells_tbl SET status='DEP' WHERE wellID=" + `wellID` + " AND status='ACTIVE'")
		
	
	def deletePrep(self, prepID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Prep_tbl SET status='DEP' WHERE prepID=" + `prepID` + " AND status='ACTIVE'")
		
	
	def deletePrepProperties(self, prepID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE PrepElementProp_tbl SET status='DEP' WHERE prepID=" + `prepID` + " AND status='ACTIVE'")
		
	
	def deleteIsolate(self, isolateID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Isolate_tbl SET status='DEP' WHERE isolate_pk=" + `isolateID` + " AND status='ACTIVE'")
		
	
	def deleteExperiment(self, expID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Experiment_tbl SET status='DEP' WHERE expID=" + `expID` + " AND status='ACTIVE'")
		
		
	# Jan. 3, 2010 - Delete an actual plate
	def deleteContainer(self, containerID):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		# Need to delete:
		# - a container
		# - its properties
		
		cursor.execute("UPDATE Prep_Req_tbl SET status='DEP' WHERE containerID=" + `containerID` + " AND status='ACTIVE'")
		cursor.execute("UPDATE Container_tbl SET status='DEP' WHERE containerID=" + `containerID` + " AND status='ACTIVE'")
	
	# Feb. 18/10
	def deleteExperimentID(self, rID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		# plain and simple - when a reagent is deleted, delete the Experiment_tbl entry for it
		cursor.execute("UPDATE Experiment_tbl SET status='DEP' WHERE reagentID=" + `rID` + " AND status='ACTIVE'")


	####################################################################################################################################
	# Written June 6, 2011
	#
	# Return the internal database ID of a well in the given container identified by the given coordinates (row and column numbers)
	#
	# wellRow: INT
	# wellCol: INT
	# contID: INT (internal database ID of the container that contains this well)
	####################################################################################################################################
	def findWellIDByCoordinates(self, contID, wellRow, wellCol):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT wellID FROM Wells_tbl WHERE containerID=" + `contID` + " AND  wellRow=" + `wellRow` + " AND wellCol=" + `wellCol` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		else:
			return 0


	# June 6, 2011
	def findPrepIDInWell(self, wellID):
		db = self.db
		cursor = self.cursor	# for easy access

		cursor.execute("SELECT prepID FROM Prep_tbl WHERE wellID=" + `wellID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			return int(result[0])
		else:
			return 0


	# June 6, 2011
	def updatePrepPropertyValue(self, prepID, elementTypeID, propValue):
		db = self.db
		cursor = self.cursor	# for easy access
		
		if self.existsPrepProperty(prepID, elementTypeID):
			cursor.execute("UPDATE PrepElementProp_tbl SET value=" + `propValue` + " WHERE prepID=" + `prepID` + " AND elementTypeID=" + `elementTypeID` + " AND status='ACTIVE'")
		else:
			self.addPrepPropertyValue(prepID, elementTypeID, propValue)


	# June 6, 2011
	def existsPrepProperty(self, prepID, elementTypeID):
		db = self.db
		cursor = self.cursor	# for easy access

		cursor.execute("SELECT * FROM PrepElementProp_tbl WHERE prepID=" + `prepID` + " AND elementTypeID=" + `elementTypeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			return True
		else:
			return False		


	# June 6, 2011
	def addPrepPropertyValue(self, prepID, elementTypeID, propValue):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("INSERT INTO PrepElementProp_tbl(prepID, elementTypeID, value) VALUES(" + `prepID` + ", " + `elementTypeID` + ", " + `propValue` + ")")


	# June 6, 2011
	def updatePrepComments(self, prepID, commVal):
		db = self.db
		cursor = self.cursor	# for easy access

		cursor.execute("UPDATE Prep_tbl SET comments=" + `commVal` + " WHERE prepID=" + `prepID` + " AND status='ACTIVE'")
		

	# June 6, 2011
	def updatePrepReference(self, prepID, refVal):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("UPDATE Prep_tbl SET refAvailID=" + `refVal` + " WHERE prepID=" + `prepID` + " AND status='ACTIVE'")


	# June 8, 2011
	# Return an array of wells in the container identified by contID
	def findContainerWells(self, contID):
		db = self.db
		cursor = self.cursor	# for easy access

		wells = []
		
		cursor.execute("SELECT wellID, wellRow, wellCol FROM Wells_tbl WHERE containerID=" + `contID` + " AND status='ACTIVE'")
		results = cursor.fetchall()

		for result in results:
			wellID = int(result[0])
			wellRow = int(result[1])
			wellCol = int(result[2])

			tmpWell = Well(wellID, wellRow, wellCol, contID)
			wells.append(tmpWell)

		return wells


	# June 9, 2011
	def findPrepReference(self, prepID):
		db = self.db
		cursor = self.cursor	# for easy access

		cursor.execute("SELECT refAvailID FROM Prep_tbl WHERE prepID=" + `prepID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			return result[0]
		else:
			return ""
			

	# June 9, 2011
	def findPrepComments(self, prepID):
		db = self.db
		cursor = self.cursor	# for easy access

		cursor.execute("SELECT comments FROM Prep_tbl WHERE prepID=" + `prepID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			return result[0]
		else:
			return ""


	# June 9, 2011
	def findPrepIsolateID(self, prepID):
		db = self.db
		cursor = self.cursor	# for easy access

		cursor.execute("SELECT isolate_pk FROM Prep_tbl WHERE prepID=" + `prepID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			return int(result[0])
		else:
			return -1

	
	# June 9, 2011
	def findPrepFlag(self, prepID):
		db = self.db
		cursor = self.cursor	# for easy access

		cursor.execute("SELECT flag FROM Prep_tbl WHERE prepID=" + `prepID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		flag = 'NO'

		if result:
			if result[0].upper() == 'YES':
				flag = 'YES'
				
		return flag


	##############################################################################
	# June 9, 2011
	#
	# Input: isolateID - INT, corresponds to Isolate_tbl.isolate_pk column value
	# Return: Isolate_tbl.isolateNumber column value
	#
	##############################################################################
	def findIsolateNumber(self, isolateID):
		db = self.db
		cursor = self.cursor	# for easy access

		cursor.execute("SELECT isolateNumber FROM Isolate_tbl WHERE isolate_pk=" + `isolateID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			return int(result[0])
		else:
			return 0


	# June 9, 2011
	def isSelectedIsolate(self, isolateID):
		db = self.db
		cursor = self.cursor	# for easy access

		cursor.execute("SELECT beingUsed FROM Isolate_tbl WHERE isolate_pk=" + `isolateID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0]

		return 'NO'


	# June 9, 2011
	# Return an array of PrepProperty OBJECTS
	def findAllPrepProperties(self, prepID):
		db = self.db
		cursor = self.cursor	# for easy access

		prepProps = []
		
		#print "Content-type:text/html"
		#print
		#print "SELECT t.elementTypeID, t.propertyName, p.value FROM PrepElemTypes_tbl t, PrepElementProp_tbl p WHERE p.prepID=" + `prepID` + " AND p.elementTypeID=t.elementTypeID AND p.status='ACTIVE' AND t.status='ACTIVE'"

		cursor.execute("SELECT t.elementTypeID, t.propertyName, p.value FROM PrepElemTypes_tbl t, PrepElementProp_tbl p WHERE p.prepID=" + `prepID` + " AND p.elementTypeID=t.elementTypeID AND p.status='ACTIVE' AND t.status='ACTIVE'")

		results = cursor.fetchall()

		for result in results:
			propID = int(result[0])
			#print propID
			propName = result[1]
			#print propName
			propValue = result[2]
			#print propValue

			tmpPrepProperty = PrepProperty(propID, propName, propValue)

			prepProps.append(tmpPrepProperty)

		#print `prepProps`
		return prepProps


	# June 9, 2011
	def findExperimentIDByIsolate(self, isolateID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		cursor.execute("SELECT expID FROM Isolate_tbl WHERE isolate_pk=" + `isolateID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			return int(result[0])
		else:
			return -1

	# June 9, 2011
	def findReagentIDByExperiment(self, expID):
		db = self.db
		cursor = self.cursor	# for easy access
	
		cursor.execute("SELECT reagentID FROM Experiment_tbl WHERE expID=" + `expID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			return int(result[0])
		else:
			return 0

	# June 9, 2011
	def getNumRows(self, contID):
		db = self.db
		cursor = self.cursor	# for easy access
	
		contSize = self.findContainerSize(contID)
		cursor.execute("SELECT maxRow FROM ContainerTypeID_tbl WHERE containerName=" + `contSize`)
		result = cursor.fetchone()

		return int(result[0])


	# June 9, 2011
	def getNumCols(self, contID):
		db = self.db
		cursor = self.cursor	# for easy access
	
		contSize = self.findContainerSize(contID)
		cursor.execute("SELECT maxCol FROM ContainerTypeID_tbl WHERE containerName=" + `contSize`)
		result = cursor.fetchone()

		return int(result[0])

		
	# June 13, 2011: Equal and opposite of findReagentIDByExperiment - given a reagent ID, find its expID
	# Return: expID
	def findExperimentByReagentID(self, rID):
		db = self.db
		cursor = self.cursor	# for easy access
	
		cursor.execute("SELECT expID FROM Experiment_tbl WHERE reagentID=" + `rID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			return int(result[0])
		else:
			return 0


	# June 13, 2011
	def findContainerByWell(self, wellID):
		db = self.db
		cursor = self.cursor	# for easy access

		cursor.execute("SELECT containerID FROM Wells_tbl WHERE wellID=" + `wellID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		return int(result[0])


	# June 13, 2011
	def findWellRowNumber(self, wellID):
		db = self.db
		cursor = self.cursor	# for easy access
	
		cursor.execute("SELECT wellRow FROM Wells_tbl WHERE wellID=" + `wellID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		return int(result[0])


	# June 13, 2011
	def findWellColumn(self, wellID):
		db = self.db
		cursor = self.cursor	# for easy access
	
		cursor.execute("SELECT wellCol FROM Wells_tbl WHERE wellID=" + `wellID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		return int(result[0])


	# June 13, 2011: Convert well ID to A:1
	def findWellCoordinates(self, wellID):
		db = self.db
		cursor = self.cursor	# for easy access

		wellRowNum = self.findWellRowNumber(wellID)
		wellCol = self.findWellColumn(wellID)
		wellRowLetter = Well.convertRowNumberToChar(wellRowNum)
		wellCoords = wellRowLetter + ":" + `wellCol`

		return wellCoords