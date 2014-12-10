import string
import MySQLdb

from general_handler import *

################################################################################
# Module system_set_handler
# An interface to System_Set_tbl and System_Set_Groups_tbl in OpenFreezer
#
# This module performs SQL queries to deal specifically with dropdown lists in
# reagent creation and detailed views
#
# Written November 2, 2006 by Marina Olhovsky
#
# Last modified: July 15, 2009
#####################################################################################################################

#####################################################################################################################
# SystemSetHandler class
# Subclass of GeneralHandler class
# Written November 2, 2006, by Marina Olhovsky
#
# Note on table structure change July 15/09:
# - removed groupType and groupName columns from System_Set_Groups_tbl
# - renamed propertyIDLink column to reagentTypeAttributeID
# - reagentTypeAttributeID column is a foreign key referencing ReagentTypeAttributes_tbl(reagentTypePropertyID)
#   (the purpose is to enable dropdown list value separation across reagent types)
#####################################################################################################################
class SystemSetHandler(GeneralHandler):

	"This class handles reagent properties in OpenFreezer that are shown as dropdown lists on various views"

	def __init__(self, db, cursor):
		self.db = db
		self.cursor = cursor

	
	# July 28/09: System_Set_Groups_tbl structure has been modified: Since we are now assigning certain properties and values to specific reagent types and the actual dropdown values may thus differ, the propertyIDLink column has been changed -- it is now called "reagentTypeAttributeID" and is a foreign key referencing ReagentTypeAttributeID column in ReagentTypeAttributes_tbl.  In other words, each dropdown is now associated with a particular property in a particular category for a particular reagent type (i.e. Oligo origin general != Vector origin feature != Insert origin feature != Insert origin classifier != Cell Line origin general)
	def updateSet(self, rTypeAttrID, comments, entityVal):
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		db = self.db
		cursor = self.cursor
		
		setGroupID = self.findSetGroupID(rTypeAttrID)
		
		if setGroupID < 0:
			setGroupID = self.addSetGroup(rTypeAttrID, comments)
			
		#print setGroupID
			
		self.addSetValue(setGroupID, entityVal)
	
	
	# July 28/09: Add property to list of dropdowns
	def addSetGroup(self, rTypeAttrID, comments):
		
		db = self.db
		cursor = self.cursor
		
		ssetGroupID = -1

		cursor.execute("INSERT INTO System_Set_Groups_tbl(reagentTypeAttributeID, comments) VALUES(" + `rTypeAttrID` + ", " + `comments` + ")")
		ssetGroupID = int(db.insert_id())
		
		return ssetGroupID
		
	
	# July 28/09: Check whether ssetGroupID exists for this rTypeAttrID; create if doesn't
	def findSetGroupID(self, rTypeAttrID):
	
		db = self.db
		cursor = self.cursor

		cursor.execute("SELECT ssetGroupID FROM System_Set_Groups_tbl WHERE reagentTypeAttributeID=" + `rTypeAttrID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		
		return -1
		

	# Add propVal to the appropriate dropdown list
	# Input:
	#   setGroupID = ssetGroupID column value in System_Set_tbl
	#   entityVal = actual set value
	# Return: new ssetGroupID
	def addSetValue(self, setGroupID, entityVal):
		db = self.db
		cursor = self.cursor
		
		pHandler = ReagentPropertyHandler(db, cursor)		# April 13/09
		
		if not self.existsSetEntry(setGroupID, entityVal):
			cursor.execute("INSERT INTO `System_Set_tbl`(`ssetGroupID`, `entityName`, `entityDesc`) VALUES(" + `setGroupID` + ", " + `entityVal` + ", " + `entityVal` + ")")


	# Checks if the given set entry exists in System_Set_Groups_tbl
	def existsSetEntry(self, ssetGroupID, entityVal):
		db = self.db
		cursor = self.cursor
		
		#cursor.execute("SELECT * FROM System_Set_Groups_tbl s1, System_Set_tbl s2 WHERE s1.groupDesc=" + `entityName` + " AND s1.ssetGroupID=s2.ssetGroupID AND s2.entityName=" + `entityVal` + " AND s1.status='ACTIVE' AND s2.status='ACTIVE'")
		
		cursor.execute("SELECT * FROM System_Set_tbl WHERE ssetGroupID=" + `ssetGroupID` + " AND entityName=" + `entityVal` + " AND status='ACTIVE'")
		result = cursor.fetchall()
		
		if not result:
			return 0	# the entity does NOT exist
		else:
			return 1
		
		
	# Find a set of all values for a particular property (e.g. fetch all available Species, Insert Types, etc.)
	# ssetGroupName: STRING, name of the property (Type of Insert, Species, etc.) - corresponds to 'groupName' column in System_Set_Groups_tbl
	# Modified August 4/09
	def findAllSetValues(self, ssetGroupID):
		
		db = self.db
		cursor = self.cursor
		
		setValues = []
		
		#cursor.execute("SELECT s.entityName FROM System_Set_tbl s, System_Set_Groups_tbl g WHERE g.groupName=" + `ssetGroupName` + " AND g.ssetGroupID=s.ssetGroupID AND g.status='ACTIVE' AND s.status='ACTIVE' ORDER BY s.entityName")
		cursor.execute("SELECT DISTINCT(s.entityName) FROM System_Set_tbl s, System_Set_Groups_tbl g WHERE g.ssetGroupID=s.ssetGroupID AND g.status='ACTIVE' AND s.status='ACTIVE' ORDER BY s.entityName")
		results = cursor.fetchall()
		
		for result in results:
			setVal = result[0]
			setValues.append(setVal)
			
		return setValues


	# Aug. 4/09: Find all dropdown set values for the attribute identified by propID in the given category for ALL reagent types currently existing in LIMS
	def findAllPropSetValues(self, propID, categoryID):
		
		db = self.db
		cursor = self.cursor
		
		setValues = []
		
		cursor.execute("SELECT DISTINCT(propCatID) FROM ReagentPropertyCategories_tbl WHERE propID=" + `propID` + " AND categoryID= " + `categoryID` + " AND status='ACTIVE'")
		results = cursor.fetchall()
		
		propCatIDs = []
		
		for result in results:
			propCatID = int(result[0])
			propCatIDs.append("'" + `propCatID` + "'")
		
		if len(propCatIDs) > 0:
			propCatIDList = "(" + string.join(propCatIDs, ", ") + ")"
			
			print "SELECT DISTINCT(reagentTypePropertyID) FROM ReagentTypeAttributes_tbl WHERE propertyTypeID IN " + propCatIDList + " AND status='ACTIVE'"
			
			cursor.execute("SELECT DISTINCT(reagentTypePropertyID) FROM ReagentTypeAttributes_tbl WHERE propertyTypeID IN " + propCatIDList + " AND status='ACTIVE'")
			results = cursor.fetchall()
			
			rTypeAttrIDs = []
			
			for result in results:
				rTypeAttrID = int(result[0])
				rTypeAttrIDs.append("'" + `rTypeAttrID` + "'")
				
			if len(rTypeAttrIDs) > 0:
				rTypeAttrIDList = "(" + string.join(rTypeAttrIDs, ", ") + ")"
				
				cursor.execute("SELECT DISTINCT(ssetGroupID) FROM System_Set_Groups_tbl WHERE reagentTypeAttributeID IN " + rTypeAttrIDList + " AND status='ACTIVE'")
				results = cursor.fetchall()
				
				ssetGroupIDs = []
				
				for result in results:
					ssetGroupID = int(result[0])
					ssetGroupIDs.append("'" + `ssetGroupID` + "'")
				
				#print `ssetGroupIDs`
				
				if len(ssetGroupIDs) > 0:
					ssetGroupIDList = "(" + string.join(ssetGroupIDs, ", ") + ")"
					
					#print ssetGroupIDList
					#print "SELECT DISTINCT(s.entityName) FROM System_Set_tbl WHERE ssetGroupID IN " + ssetGroupIDList + " AND status='ACTIVE' ORDER BY s.entityName"
					
					cursor.execute("SELECT DISTINCT(entityName) FROM System_Set_tbl WHERE ssetGroupID IN " + ssetGroupIDList + " AND status='ACTIVE' ORDER BY entityName")
					results = cursor.fetchall()
					
					for result in results:
						setVal = result[0]
						setValues.append(setVal)
						
		return setValues

	
	# June 3/09
	def deleteSetValues(self, ssetGroupID):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE System_Set_tbl SET status='DEP' WHERE ssetGroupID=" + `ssetGroupID` + " AND status='ACTIVE'")
	
	
	# Aug. 5/09
	def deleteSetGroup(self, setGroupID):
	
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE System_Set_Groups_tbl SET status='DEP' WHERE ssetGroupID=" + `setGroupID` + " AND status='ACTIVE'")
