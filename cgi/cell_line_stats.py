#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()

import MySQLdb
import sys
import string

from database_conn import DatabaseConn

from mapper import ReagentPropertyMapper, ReagentAssociationMapper
from general_handler import *
from reagent_handler import ReagentHandler, InsertHandler
from sequence_handler import DNAHandler, ProteinHandler
from comment_handler import CommentHandler
#from system_set_handler import SystemSetHandler
from sequence_handler import SequenceHandler
from reagent import *

from location_database_handler import LocationHandler

import utils

from user_handler import UserHandler
from project_database_handler import ProjectDatabaseHandler
from session import Session
from exception import *

dbConn = DatabaseConn()
db = dbConn.databaseConnect()

cursor = db.cursor()
hostname = dbConn.getHostname()
root_path = dbConn.getRootDir()

dnaHandler = DNAHandler(db, cursor)
protHandler = ProteinHandler(db, cursor)
rHandler = ReagentHandler(db, cursor)
pHandler = ReagentPropertyHandler(db, cursor)
rtPropHandler = ReagentTypePropertyHandler(db, cursor)
lHandler = LocationHandler(db, cursor)

propMapper = ReagentPropertyMapper(db, cursor)
rtMapper = ReagentTypeMapper(db, cursor)

reagentType_Name_ID_Map = rtMapper.mapTypeNameID()

prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)
prop_ID_Name_Map = propMapper.mapPropIDName()		# (prop id, prop name)

prop_Name_Descr_Map = propMapper.mapPropNameDescription()
prop_ID_Descr_Map = propMapper.mapPropIDDescription()

prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()

def main():
	
	#print "Content-type:text/html"
	#print

	clTypeID = reagentType_Name_ID_Map["CellLine"]

	# list of propNames
	bhPropNames = rtPropHandler.findReagentTypeAttributeNamesByCategory(clTypeID, prop_Category_Name_ID_Map["Biosafety"])

	bhPropNames.sort()

	content = ""

	# Print in this order
	content += "Cell Line ID\tName\tRisk Group\tContainment Level\tKnown Pathogens\tSafety Notes\tContainer\tWells\tTotal Vials\tStorage Location\tShelf\tRack\tRow\tColumn\n"

	namePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["name"], prop_Category_Name_ID_Map["General Properties"])
	#print namePropID

	# get all cell lines - remember, table column value is 'CellLine', not 'Cell Line'!!!
	cursor.execute("SELECT reagentID FROM Reagents_tbl r, ReagentType_tbl t WHERE r.reagentTypeID=" + `clTypeID` + " AND r.reagentTypeID=t.reagentTypeID AND r.status='ACTIVE' AND t.status='ACTIVE'")
	cell_line_results = cursor.fetchall()

	for cl_res in cell_line_results:
		cellLineID = int(cl_res[0])
		#print cellLineID

		cellLine_id = rHandler.convertDatabaseToReagentID(cellLineID)
		#print cellLine_id

		# name, storage (location + storage type), container, well
		clName = rHandler.findSimplePropertyValue(cellLineID, namePropID)
		#print clName

		# Hard-code
		riskGroupPropCatID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["risk group"], prop_Category_Name_ID_Map["Biosafety"])
		riskGroup_prop_val = rHandler.findSimplePropertyValue(cellLineID, riskGroupPropCatID)

		if not riskGroup_prop_val:
			riskGroup_prop_val = ""
	
		contLevelPropCatID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["containment level"], prop_Category_Name_ID_Map["Biosafety"])
		contLevel_prop_val = rHandler.findSimplePropertyValue(cellLineID, contLevelPropCatID)

		if not contLevel_prop_val:
			contLevel_prop_val = ""

		pathogensPropCatID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["known pathogens"], prop_Category_Name_ID_Map["Biosafety"])
		pathogens_prop_val = rHandler.findSimplePropertyValue(cellLineID, pathogensPropCatID)

		if not pathogens_prop_val:
			pathogens_prop_val = ""

		safetyNotesPropCatID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["safety notes"], prop_Category_Name_ID_Map["Biosafety"])
		safety_prop_val = rHandler.findSimplePropertyValue(cellLineID, safetyNotesPropCatID)

		if not safety_prop_val:
			safety_prop_val = ""

		# Location
		expID = lHandler.findExperimentByReagentID(cellLineID)
		isolates = lHandler.findIsolates(expID)

		total_vials = 0
		wells = ""

		wellContDict = {}

		for isoID in isolates:
			preps = lHandler.findPreps(isoID)
			
			for prepID in preps:
				total_vials += 1

				wellID = lHandler.findWellID(prepID)
				containerID = lHandler.findContainerByWell(wellID)

				if wellContDict.has_key(containerID):
					cont_wells = wellContDict[containerID]
				else:
					cont_wells = []

				cont_wells.append(wellID)
				wellContDict[containerID] = cont_wells

		for containerID in wellContDict.keys():
			containerName = lHandler.findContainerName(containerID)
			contStorageName = lHandler.findContainerStorageTypeName(containerID)

			if not contStorageName:
				contStorageName = ""

			contShelf = lHandler.findContainerShelf(containerID)
			contRack = lHandler.findContainerRack(containerID)
			contRow = lHandler.findContainerRow(containerID)
			contColumn = lHandler.findContainerColumn(containerID)
			
			all_wells = wellContDict[containerID]

			storage_location = contStorageName + '\t' + `contShelf` + '\t' + `contRack` + '\t' + `contRow` + '\t' + `contColumn`

			total_vials = 0
			wells = ""

			for wellID in all_wells:
				well_coords = lHandler.findWellCoordinates(wellID)
				
				# Output: Plate Name\tWells separated by comma\tTotal vials\tStorage		
				wells += well_coords + ','
				total_vials += 1

			location_content = containerName + '\t' + wells + '\t' + `total_vials`

			content += cellLine_id + '\t'

			if clName:
				content += clName + '\t'
			else:
				content += '\t'

			content += riskGroup_prop_val + '\t'
			content += contLevel_prop_val + '\t'		
			content += pathogens_prop_val + '\t'		
			content += safety_prop_val + '\t'

			content += location_content + '\t'
			content += storage_location + '\n'
		
	fname = "Cell_Line_Biosafety_Statistics.tsv"

	print "Content-type: application/octet-stream"
	print "Content-Disposition: attachment; name=" + fname
	print
	print content
	
main()