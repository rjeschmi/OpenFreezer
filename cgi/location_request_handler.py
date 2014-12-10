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
from well import Well

from location_database_handler import LocationHandler
from location_type_database_handler import LocationTypeHandler
from reagent_handler import ReagentHandler
from exception import ReagentDoesNotExistException

from mapper import PrepPropertyMapper, ContainerTypeMapper

##################################################################################################################
# Contains functions to handle requests for creation, modification and deletion of containers or container types
#
# Written Sept. 18, 2007, by Marina Olhovsky
# Last modified: Sept. 18, 2007
##################################################################################################################
class LocationRequestHandler:
	__db = None
	__cursor = None
	__hostname = ""
	
	##########################################################
	# Constructor
	##########################################################
	def __init__(self):
	
		dbConn = DatabaseConn()
		db = dbConn.databaseConnect()
		cursor = db.cursor()
		hostname = dbConn.getHostname()

		self.__db = db
		self.__cursor = cursor
		self.__hostname = hostname
		
		
	##########################################################
	# Override parent method
	##########################################################
	def handle(self):
		
		db = self.__db
		cursor = self.__cursor
		
		#print "Content-type:text/html"
		#print
		
		form = cgi.FieldStorage(keep_blank_values="True")
		#print `form`
	
		if form.has_key("cont_cont_create_button"):
			self.createContainer(form)
			
		elif form.has_key("cont_modify_button"):
			self.modify_container(form)
			
		elif form.has_key("delete_container"):
			self.deleteContainer(form)
			
		elif form.has_key("edit_cont_location"):
			self.editContainerLocation(form)
		
		elif form.has_key("save_cont_storage"):
			self.updateContainerStorage(form)
		
		elif form.has_key("validate_reagent_id"):
			self.validateReagentID(form)

		# June 6, 2011
		elif form.has_key("update_attribute"):
			self.updateAttribute(form)

		# June 8, 2011
		elif form.has_key("export_plate"):
			self.exportPlate(form)

		cursor.close()
		db.close()
		
		
	# Store container name, type, size and description arriving from POST and assign a barcode (new feature)
	def createContainer(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
	
		lHandler = LocationHandler(db, cursor)
		
		# some form values may be blank, so initialize as empty
		contName = ""
		contDesc = ""
		
		if form.has_key("cont_cont_name_field"):
			contName = form.getvalue("cont_cont_name_field")
		
		if form.has_key("cont_cont_desc_field"):
			contDesc = form.getvalue("cont_cont_desc_field")
			
		# container type and size are selected from a list, so they can't be empty
		contTypeID = form.getvalue("cont_cont_group_selection")
		contSize = form.getvalue("cont_cont_type_selection")
		
		# laboratory - always selected by default
		contLab = form.getvalue("labs")
		
		# Location - fridge, freezer, LN tank, etc.
		storage_type = form.getvalue("storage_type")
		storage_name = form.getvalue("storage_name")
		cont_row = form.getvalue("cont_row")
		cont_col = form.getvalue("cont_col")
		cont_rack = form.getvalue("cont_rack")
		cont_shelf = form.getvalue("cont_shelf")
		storage_address = form.getvalue("storage_address")
		
		newContID = lHandler.insertContainer(contTypeID, contSize, contName, contDesc, contLab, storage_type, storage_name, storage_address, cont_shelf, cont_rack, cont_row, cont_col)

		utils.redirect(hostname + "Location.php?View=6&Sub=3&Mod=" + `newContID`)
	
	
	# Jan. 3, 2010: Plain redirect to Storage page in Modify mode
	def editContainerLocation(self, form):
		
		hostname = self.__hostname
		contID = int(form.getvalue("cont_id_hidden"))
		
		utils.redirect(hostname + "Location.php?View=3&Sub=1&Mod=" + `contID`)
	
	# Jan. 3, 2010: Update location details
	def updateContainerStorage(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		lHandler = LocationHandler(db, cursor)
		
		#print "Content-type:text/html"
		#print
		#print `form`
		
		contID = int(form.getvalue("cont_id_hidden"))
		storage_type = form.getvalue("storage_type")
		storage_name = form.getvalue("storage_name")
		cont_row = form.getvalue("cont_row")
		cont_col = form.getvalue("cont_col")
		cont_rack = form.getvalue("cont_rack")
		cont_shelf = form.getvalue("cont_shelf")
		storage_address = form.getvalue("storage_address")
		
		lHandler.updateContainerLocation(contID, storage_type, storage_name, storage_address, cont_shelf, cont_rack, cont_row, cont_col)
		
		utils.redirect(hostname + "Location.php?View=3&Mod=" + `contID`)
	
	
	def modify_container(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
	
		lHandler = LocationHandler(db, cursor)
		
		contID = int(form.getvalue("cont_id_hidden"))
		
		# some form values may be blank, so initialize as empty
		contName = ""
		contDesc = ""
		
		if form.has_key("cont_name_field"):
			contName = form.getvalue("cont_name_field")
		
		if form.has_key("cont_desc_field"):
			contDesc = form.getvalue("cont_desc_field")
			
		# container type and size are selected from a list, so they can't be empty
		contType = form.getvalue("cont_group_selection")
		contSize = form.getvalue("cont_size_selection")
		
		# laboratory - always selected by default
		contLab = int(form.getvalue("labs"))
		
		# Location - fridge, freezer, LN tank, etc.
		storage_type = form.getvalue("storage_type")
		storage_name = form.getvalue("storage_name")
		cont_row = form.getvalue("cont_row")
		cont_col = form.getvalue("cont_col")
		cont_rack = form.getvalue("cont_rack")
		cont_shelf = form.getvalue("cont_shelf")
		storage_address = form.getvalue("storage_address")
		
		lHandler.updateContainerInfo(contID, contType, contSize, contName, contDesc, contLab, storage_type, storage_name, storage_address, cont_shelf, cont_rack, cont_row, cont_col)
		
		utils.redirect(hostname + "Location.php?View=6&Sub=3&Mod=" + `contID`)
	
	
	def deleteContainer(self, form):
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
	
		lHandler = LocationHandler(db, cursor)
		
		if form.has_key("containerID"):
			containerID = int(form.getvalue("containerID"))
			delStatus = lHandler.deleteContainer(containerID)
			utils.redirect(hostname + "Location.php?View=5&Del=1")
	
	
	def validateReagentID(self, form):
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
	
		rHandler = ReagentHandler(db, cursor)
		
		print "Content-type:text/html"
		print
		
		tmp_rids = form.getlist("rID")
		#tmp_rid = form.getvalue("rID").strip()
		#print `tmp_rids`
		
		for tmp_rid in tmp_rids:
			try:
				tmpReagentID = rHandler.convertReagentToDatabaseID(tmp_rid.strip())
			except ReagentDoesNotExistException:
				i = ReagentDoesNotExistException("Reagent does not exist in database")
				print "ErrCode=" + `i.err_code()` + "&rID=" + tmp_rid
				break


	def updateAttribute(self, form):
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname

		lHandler = LocationHandler(db, cursor)
		prepPropMapper = PrepPropertyMapper(db, cursor)
		prepProp_Name_ID_Map = prepPropMapper.mapPrepPropNameToID()
		
		print "Content-type:text/html"
		print
		
		# Update the given attribute for each well selected
		wells_str = form.getvalue("wells")	# it's a comma-delimited string, count as one value

		contID = int(form.getvalue("contID"))
		propName = form.getvalue("propName")
		propVal = form.getvalue("propVal")

		if prepProp_Name_ID_Map.has_key(propName):
			prepElemID = prepProp_Name_ID_Map[propName]
		else:
			prepElemID = 0

		'''
		if form.has_key("propID"):
			try:
				prepElemID = int(form.getvalue("propID"))

			except ValueError:
				prepElemID = 0
		'''
		
		# wells are provided as a comma-delimited list of 'row|col' values
		wells = wells_str.split(",")

		for well in wells:
			coords = well.split('|')

			wellRow = coords[0]
			wellCol = coords[1]

			wellID = lHandler.findWellIDByCoordinates(contID, wellRow, wellCol)
			#print wellID

			# get its prep
			prepID = lHandler.findPrepIDInWell(wellID)

			# Differentiate between update of prep element or reference/comments
			if prepElemID > 0:
				lHandler.updatePrepPropertyValue(prepID, prepElemID, propVal)
			else:
				if propName.lower() == 'reference':
					lHandler.updatePrepReference(prepID, propVal)

				elif propName.lower() == 'comments':
					lHandler.updatePrepComments(prepID, propVal)

		#utils.redirect(hostname + "Location.php?View=2&Mod=" + `contID`)
		print hostname + "Location.php?View=2&Mod=" + `contID`


	# June 8, 2011
	def exportPlate(self, form):
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname

		lHandler = LocationHandler(db, cursor)
		ltHandler = LocationTypeHandler(db, cursor)
		rHandler = ReagentHandler(db, cursor)

		prepPropMapper = PrepPropertyMapper(db, cursor)
		cTypeMapper = ContainerTypeMapper(db, cursor)

		prepProp_Name_ID_Map = prepPropMapper.mapPrepPropNameToID()
		prepProp_ID_Name_Map = prepPropMapper.mapPrepPropIDToName()

		contType_Name_ID_Map = cTypeMapper.mapContainerTypeNameToID()
		
		contID = int(form.getvalue("contID"))
		contName = lHandler.findContainerName(contID)
		contArray = contName.split(" ")
		fname = string.join(contArray, "_") + ".csv"

		wells = lHandler.findContainerWells(contID)
		content = ""

		# debug
		#print "Content-type:text/html"
		#print

		contType = lHandler.findContainerType(contID)
		contTypeID = contType_Name_ID_Map[contType]
		contProps = ltHandler.findContainerTypeProperties(contTypeID)	# array of names
		
		iso_active = ltHandler.isIsoActive(contTypeID)

		if iso_active == 'YES':
			isoActive = True
		else:
			isoActive = False

		#print `contProps`

		# Header
		if isoActive:
			content = "Well,OpenFreezer ID,Selected Isolate,Flag,Reference,Comments,"
		else:
			content = "Well,OpenFreezer ID,Flag,Reference,Comments,"

		contPropIDs_sorted = []

		for cPropName in contProps:
			cPropID = prepProp_Name_ID_Map[cPropName]
			contPropIDs_sorted.append(cPropID)

		contPropIDs_sorted.sort()

		for cPropID in contPropIDs_sorted:
			cPropName = prepProp_ID_Name_Map[cPropID]
			content += cPropName + ','

		content += '\n'

		numRows = lHandler.getNumRows(contID)
		numCols = lHandler.getNumCols(contID)

		plate = {}

		for well in wells:
			wellRowNum = well.getWellRowNumber()
			#print wellRowNum

			if plate.has_key(wellRowNum):
				plate[wellRowNum].append(well)
			else:
				tmp_ar = []
				tmp_ar.append(well)
				plate[wellRowNum] = tmp_ar

		plate.keys().sort()

		for rowNum in range(1, numRows+1):
			if plate.has_key(rowNum):
				wellRow = plate[rowNum]
				wellRowNum = rowNum
				wellRowLetter = Well.convertRowNumberToChar(wellRowNum)

				wellRow.sort()

				#print "ROW " + `rowNum`
				
				for colNum in range(1, numCols+1):
					for well in wellRow:
						wellID = well.getWellID()
						#print wellID
						wellCol = well.getWellColumn()

						if wellCol == colNum:

							#print "COLUMN " + `wellCol`

							wellCoords = wellRowLetter + ":" + `wellCol`
							
							# get prep, reference, comments, flag
							prepID = lHandler.findPrepIDInWell(wellID)
							#print prepID
							prepRef = lHandler.findPrepReference(prepID)
							#print prepRef

							if not prepRef:
								prepRef = ""

							prepComms = lHandler.findPrepComments(prepID)
							#print prepComms

							if not prepComms:
								prepComms = ""

							prepFlag = lHandler.findPrepFlag(prepID)
							#print prepFlag

							# get isolate number, selected isolate
							isoID = lHandler.findPrepIsolateID(prepID)
							isoNum = lHandler.findIsolateNumber(isoID)
							isoSelected = lHandler.isSelectedIsolate(isoID)

							# get prep property values
							prepPropValues = lHandler.findAllPrepProperties(prepID)

							# find the LIMS ID of the reagent stored in this well
							expID = lHandler.findExperimentIDByIsolate(isoID)
							rID = lHandler.findReagentIDByExperiment(expID)
							limsID = rHandler.convertDatabaseToReagentID(rID)

							# Output: Well , Reagent-isolate , Selected , Flag , Reference , Comments , Attributes [name1 , name2 , name3....]
							if isoActive:
								content += wellCoords + ',' + limsID + "-" + `isoNum` + ',' + isoSelected + ',' + prepFlag + ',' + prepRef + ',' + prepComms + ","
							else:
								content += wellCoords + ',' + limsID + ',' + prepFlag + ',' + prepRef + ',' + prepComms + ","

							prepPropIDs_sorted = {}

							prepProps = {}

							for pProp in prepPropValues:
								pName = pProp.getPropName()
								pVal = pProp.getPropValue()
								prepProps[pName] = pVal

							prepProps.keys().sort()		# might not be necessary but still
								
							for cPropID in contPropIDs_sorted:
								cPropName = prepProp_ID_Name_Map[cPropID]

								if prepProps.has_key(cPropName):
									pVal = prepProps[cPropName]
									content += pVal + ','
								else:
									content += ','									

							content += '\n'
		
		print "Content-type: application/octet-stream"
		print "Content-Disposition: attachment; name=" + fname
		print

		print content
				
	
##########################################################
# Central callable function
##########################################################
def main():

	lReqHandler = LocationRequestHandler()
	lReqHandler.handle()

main()