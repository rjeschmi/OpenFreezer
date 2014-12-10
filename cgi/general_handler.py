import MySQLdb
import sys
import string
import random
import utils

from mapper import ReagentTypeMapper
import graphics_handler

#from system_set_handler import SystemSetHandler

##################################################################################################################
# Top-level abstraction for classes that map objects to corresponding database entities in OpenFreezer
# All SQL queries are performed by these classes, thereby encapsulating the database layer and separating it from the object model

# Written by: Marina Olhovsky
# Last modified: June 15, 2009
##################################################################################################################

##############################################################################
# GeneralHandler class
##############################################################################

# Superclass of all other handlers (e.g. ReagentHandler, SequenceHandler, etc)
# October 12, 2006, Marina Olhovsky
class GeneralHandler(object):
	"Superclass of all other handlers"
	
	# Constructor
	def __init__(self, db, cursor):
	
		self.db = db
		self.cursor = cursor


##############################################################################
# ReagentPropertyHandler class
# Descendant of GeneralHandler; specifically handles reagent properties
#
# Written March 14/07 by Marina
##############################################################################
class ReagentPropertyHandler(GeneralHandler):
	"Descendant of GeneralHandler - performs various utility functions to handle reagent properties"
	
	# Get the database ID of a given property
	def findPropID(self, propName, case_sensitive=False):
	
		db = self.db
		cursor = self.cursor
		
		if case_sensitive:
			cursor.execute("SELECT `propertyID` FROM `ReagentPropType_tbl` WHERE `propertyName`=" + `propName` + " COLLATE latin1_general_cs AND status='ACTIVE'")
		else:
			cursor.execute("SELECT propertyID FROM ReagentPropType_tbl WHERE propertyName=" + `propName` + " AND status='ACTIVE'")
		
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		else:
			return -1
		
		
	# Get the database ID of a given property by DESCRIPTION
	def findPropIDByDescription(self, propDescr, case_sensitive=False):
	
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO

		if case_sensitive:
			#####################################################################################################################
			# Oct. 30/09: The COLLATE statement *** MUST *** be adjacent to the search term
			#
			# Correct: "WHERE propertyDesc='abc' COLLATE latin1_general_cs AND status='ACTIVE'"
			# Incorrect: "WHERE propertyDesc='abc' AND status='ACTIVE' COLLATE latin1_general_cs" (returns case-insensitive match)
			#
			#####################################################################################################################
			cursor.execute("SELECT propertyID FROM ReagentPropType_tbl WHERE propertyDesc=" + `propDescr` + " COLLATE latin1_general_cs AND status='ACTIVE'")
		else:
			cursor.execute("SELECT propertyID FROM ReagentPropType_tbl WHERE propertyDesc=" + `propDescr` + " AND status='ACTIVE'")
			#print "SELECT propertyID FROM ReagentPropType_tbl WHERE propertyDesc=" + `propDescr` + " AND status='ACTIVE'"
			
		result = cursor.fetchone()
		
		if result:
			#print propDescr
			#print int(result[0])
			return int(result[0])
		else:
			return -1
	
	
	# June 22/09
	def findPropertyIDFromAlias(self, pAlias, case_sensitive=False):
		
		db = self.db
		cursor = self.cursor
		
		if case_sensitive:
			cursor.execute("SELECT `propertyID` FROM `ReagentPropType_tbl` WHERE propertyAlias=" + `pAlias` + " COLLATE latin1_general_cs AND status='ACTIVE'")
		else:
			cursor.execute("SELECT propertyID FROM ReagentPropType_tbl WHERE propertyAlias=" + `pAlias` + " AND status='ACTIVE'")
			
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		else:
			return -1
	
	# Oct. 31/09
	def findPropertyDescriptionFromAlias(self, pAlias, case_sensitive=False):
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO

		db = self.db
		cursor = self.cursor
		
		if case_sensitive:
			cursor.execute("SELECT propertyDesc FROM ReagentPropType_tbl WHERE propertyAlias=" + `pAlias` + " COLLATE latin1_general_cs AND status='ACTIVE'")
		else:
			#print "SELECT propertyDesc FROM ReagentPropType_tbl WHERE propertyAlias=" + `pAlias` + " AND status='ACTIVE'"
			cursor.execute("SELECT propertyDesc FROM ReagentPropType_tbl WHERE propertyAlias=" + `pAlias` + " AND status='ACTIVE'")
			
		result = cursor.fetchone()
		
		if result:
			return result[0]
		else:
			return None
		
		
	# Nov. 2/09: Equal and opposite: find alias based on description
	def findPropertyAliasFromDescription(self, pDesc, case_sensitive=False):
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO

		db = self.db
		cursor = self.cursor
		
		if case_sensitive:
			cursor.execute("SELECT propertyAlias FROM ReagentPropType_tbl WHERE propertyDesc=" + `pDescr` + " COLLATE latin1_general_cs AND status='ACTIVE'")
		else:
			cursor.execute("SELECT propertyAlias FROM ReagentPropType_tbl WHERE propertyDesc=" + `pDesc` + " AND status='ACTIVE'")
			
		result = cursor.fetchone()
		
		if result:
			return result[0]
		else:
			return None
	
	
	# Aug. 11/09: check if there are properties that belong to a category
	def isEmpty(self, categoryID):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT propID FROM ReagentPropertyCategories_tbl WHERE categoryID=" + `categoryID` + " AND status='ACTIVE'")
		results = cursor.fetchall()
		
		if not results or len(results) == 0:
			return True
		
		return False


	# June 15/09: find IDs of all properties in a given category
	def findPropertiesByCategory(self, pCategoryID):
		
		db = self.db
		cursor = self.cursor
		
		propIDs = []		# list of property IDs
		
		cursor.execute("SELECT propertyID FROM ReagentPropType_tbl p, ReagentPropertyCategories_tbl c WHERE c.categoryID=" + `pCategoryID` + " AND c.propID=p.propertyID AND p.status='ACTIVE' AND c.status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			propID = int(result[0])
			propIDs.append(propID)
			
		return propIDs
	
	
	# Update July 22/09: Given a property ID within a specific category, find the property name
	def findPropName(self, propID):
	
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT propertyName FROM ReagentPropType_tbl p, ReagentPropertyCategories_tbl pc WHERE p.propertyID=pc.propID AND pc.propCatID=" + `propID` + " AND p.status='ACTIVE' AND pc.status='ACTIVE'")
		result = cursor.fetchone()
	
		if result:
			return result[0]
	
	# Oct. 31/09
	def findPropNameByDescription(self, descr, case_sensitive=False):
		db = self.db
		cursor = self.cursor
		
		if case_sensitive:
			cursor.execute("SELECT propertyName FROM ReagentPropType_tbl p WHERE p.propertyDesc=" + `descr` + " COLLATE latin1_general_cs AND p.status='ACTIVE'")
		else:
			cursor.execute("SELECT propertyName FROM ReagentPropType_tbl p WHERE p.propertyDesc=" + `descr` + " AND p.status='ACTIVE'")

		result = cursor.fetchone()
	
		if result:
			return result[0]

		return None

	# April 15, 2009: Find property description from its database ID
	# input: propID - Int
	# return: String - property description (corresponds to propertyDesc column in ReagentPropType_tbl, e.g. 'Type of Insert', 'polyA Tail', etc.)
	def findPropertyDescription(self, propID):
		db = self.db
		cursor = self.cursor
		
		pDescr = None

		cursor.execute("SELECT `propertyDesc` FROM `ReagentPropType_tbl` WHERE `propertyID`=" + `propID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
	
		if result:
			return result[0]

		return pDescr


	# Nov. 3/09: Plain check if this propName is stored in the propertyName column of ReagentPropType_tbl
	def existsPropertyName(self, propName, case_sensitive=False):
		
		db = self.db
		cursor = self.cursor
		
		if not case_sensitive:
			cursor.execute("SELECT * FROM ReagentPropType_tbl WHERE propertyName=" + `propName` + " AND status='ACTIVE'")
		else:
			cursor.execute("SELECT * FROM ReagentPropType_tbl WHERE propertyName=" + `propName` + " COLLATE latin1_general_cs AND status='ACTIVE'")
		
		result = cursor.fetchone ()	# should only be one
		
		if result:
			return True
		else:
			return False
		

	# June 17/09
	def existsPropertyInCategory(self, newPropID, categoryID):
		
		db = self.db
		cursor = self.cursor
		
		#cursor.execute("SELECT propCatID FROM ReagentPropertyCategories_tbl WHERE propID=" + `newPropID` + " AND categoryID=" + `categoryID` + " AND status='ACTIVE'")
		#result = cursor.fetchone()
		
		#if result:
			#return True
		
		#return False
		
		return (self.findReagentPropertyInCategoryID(newPropID, categoryID) > 0)
		
	
	def getAllFeatureColors(self):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT DISTINCT(propertyColor) FROM ReagentPropType_tbl WHERE status='ACTIVE' AND propertyColor!='NULL'")
		props = cursor.fetchall()
		
		fColors = []
		
		for prop in props:
			fColor = prop[0]
			fColors.append(fColor)
		
		return fColors
		
		
	# April 3, 2009: Add a new property type
	# Return: new database property ID
	# Modified Jan. 26, 2010
	def addReagentPropertyType(self, propertyName, propertyAlias, propertyDescription, isSeqFeature=False):
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		db = self.db
		cursor = self.cursor
		
		# Jan. 26/10: get a list of all feature colours in the system
		featureColors = self.getAllFeatureColors()
		#print `featureColors`
		fColors = utils.diff(graphics_handler.getAllColors(), featureColors)
		#print `fColors`
		
		if '0xdda0dd' in fColors:
			fColors.remove('0xdda0dd')
		
		# Jan. 26/10: Generate a new colour if the property is a features (for the vector map)
		if isSeqFeature:
			rand = random.randrange(0, len(fColors))
			nextColor = fColors[rand]
			#print nextColor
			#nextColor = float.hex(rand)
			
			#nextColor = graphics_handler.getNextColor(featureColors)
			
			#print "INSERT INTO ReagentPropType_tbl(propertyName, propertyAlias, propertyDesc, propertyColor) VALUES(" + `propertyName` + ", " + `propertyAlias` + ", " + `propertyDescription` + ", " + `nextColor` + ")"
		
			cursor.execute("INSERT INTO ReagentPropType_tbl(propertyName, propertyAlias, propertyDesc, propertyColor) VALUES(" + `propertyName` + ", " + `propertyAlias` + ", " + `propertyDescription` + ", " + `nextColor` + ")")
		else:
			cursor.execute("INSERT INTO ReagentPropType_tbl(propertyName, propertyAlias, propertyDesc) VALUES(" + `propertyName` + ", " + `propertyAlias` + ", " + `propertyDescription` + ")")
		
		newPropID = int(db.insert_id())
		
		return newPropID
	
	
	def addReagentPropertyToCategory(self, newPropID, categoryID):
		db = self.db
		cursor = self.cursor
		
		cursor.execute("INSERT INTO ReagentPropertyCategories_tbl(propID, categoryID) VALUES(" + `newPropID` + ", " + `categoryID` + ")")
		return int(db.insert_id())
	

	# April 11, 2009: Check if property identified by pType is stored in ReagentPropType_tbl
	# input: pType - String, representing an actual property type (e.g. "Selectable Marker")
	def existsReagentPropertyType(self, pType, case_sensitive=False):
		db = self.db
		cursor = self.cursor
		
		if self.findPropID(pType, case_sensitive) > 0:
			return True
		else:
			return False
	
	# June 19/09
	def existsReagentPropertyName(self, pName):
	
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT propertyID FROM ReagentPropType_tbl WHERE propertyName=" + `pName` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return True
		
		return False
	
	
	# June 19/09
	def existsReagentPropertyAlias(self, pAlias):
	
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT propertyID FROM ReagentPropType_tbl WHERE propertyAlias=" + `pAlias` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return True
		
		return False
	
	
	# June 19/09
	# Since we are now dealing with case-sensitive property names shared across categories and reagent types, cannot always rely on Python dictionaries due to case sensitivity.  Do a direct selection from the database
	# Correction July 27/09: Return multiple values
	def findReagentPropertyCategoryByDescription(self, desc):
		
		db = self.db
		cursor = self.cursor
		
		categories = []
		
		cursor.execute("SELECT propertyID FROM ReagentPropType_tbl WHERE propertyDesc=" + `desc` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			propID = int(result[0])
			
			cursor.execute("SELECT categoryID FROM ReagentPropertyCategories_tbl WHERE propID=" + `propID` + " AND status='ACTIVE'")
			results = cursor.fetchall()
		
			for result in results:
				categories.append(int(result[0]))
			
		return categories
	
	
	# June 19/09
	# Returns the value of "propCatID" column in ReagentPropertyCategories_tbl - the composite identifier of a property within a certain category
	def findReagentPropertyInCategoryID(self, propID, categoryID):
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print
		#print "SELECT propCatID FROM ReagentPropertyCategories_tbl WHERE propID=" + `propID` + " AND categoryID=" + `categoryID` + " AND status='ACTIVE'"
		
		cursor.execute("SELECT propCatID FROM ReagentPropertyCategories_tbl WHERE propID=" + `propID` + " AND categoryID=" + `categoryID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		
		return -1
	
	
	# June 19/09
	def existsReagentPropertyDescription(self, desc, case_sensitive=False):
		db = self.db
		cursor = self.cursor
		
		if case_sensitive:
			cursor.execute("SELECT propertyID FROM ReagentPropType_tbl WHERE propertyDesc=" + `desc` + " COLLATE latin1_general_cs AND status='ACTIVE'")
		else:
			cursor.execute("SELECT propertyID FROM ReagentPropType_tbl WHERE propertyDesc=" + `desc` + " AND status='ACTIVE'")
			
		result = cursor.fetchone()
		
		if result:
			return True
		
		return False
	
		
	# June 3/09
	# Returns a list of category Names (e.g. 'General Properties', 'Sequence Features', etc.)
	def findAllReagentPropertyCategories(self):
		
		db = self.db
		cursor = self.cursor
		
		categories = []
		
		cursor.execute("SELECT propertyCategoryName FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()

		for result in results:
			categories.append(result[0])
			
		return categories
	
	
	# June 23/09
	# Returns categoryID from propCatID
	def findReagentPropertyCategory(self, propCatID):
		db = self.db
		cursor = self.cursor
		
		pCategory = 0

		cursor.execute("SELECT categoryID FROM ReagentPropertyCategories_tbl WHERE propCatID=" + `propCatID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			pCategory = int(result[0])
		
		return pCategory
	

	def findReagentPropertyInCategory(self, propCatID):
		db = self.db
		cursor = self.cursor
		
		propID = 0
		
		cursor.execute("SELECT propID FROM ReagentPropertyCategories_tbl WHERE propCatID=" + `propCatID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			propID = int(result[0])
		
		return propID
	
		
	# June 19/09
	def existsReagentPropertyInCategory(self, propID, categoryID):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT propCatID FROM ReagentPropertyCategories_tbl WHERE propID=" + `propID` + " AND categoryID=" + `categoryID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return True
		
		return False
		
	
	def existsReagentPropertyCategory(self, category):
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT * FROM ReagentPropTypeCategories_tbl WHERE propertyCategoryName=" + `category` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			return True
		else:
			return False


	def addReagentPropertyCategory(self, categoryName, categoryAlias):
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT MAX(ordering) FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE' AND ordering < " + `sys.maxint`)
		result = cursor.fetchone()
		ordering = int(result[0]) + 1
		
		cursor.execute("INSERT INTO ReagentPropTypeCategories_tbl(propertyCategoryName, propertyCategoryAlias, ordering) VALUES(" + `categoryName` + ", " + `categoryAlias` + ", " + `ordering` + ")")
		newPropID = int(db.insert_id())
		
		return newPropID
	
	# June 3/09
	def deleteReagentPropertyCategory(self, pCategory):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE ReagentPropTypeCategories_tbl SET status='DEP' WHERE propertyCategoryID=" + `pCategory` + " AND status='ACTIVE'")
		
		
	# June 3/09: Delete ReagentPropType_tbl entry
	def deleteReagentProperty(self, pID):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE ReagentPropType_tbl SET status='DEP' where propertyID=" + `pID` + " AND status='ACTIVE'")
		
		
	def deleteReagentPropertyInCategory(self, attrID):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE ReagentPropertyCategories_tbl SET status='DEP' where propCatID=" + `attrID` + " AND status='ACTIVE'")
		
		
	# July 27/09
	def setPropertyOrdering(self, propID, ordering):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE ReagentPropType_tbl SET ordering=" + `ordering` + " WHERE propertyID=" + `propID` + " AND status='ACTIVE'")
	
#################################################################################################################################
# General Association Handler class to handle various **general** association queries, not attached to any particular reagent
# Written March 27, 2007 by Marina
#################################################################################################################################
class AssociationHandler(GeneralHandler):
	"Handles various Association queries not attached to a particular reagent"
	
	# Find the internal database ID of human-readable 'assocProp'
	# e.g. 'assocProp' = 'vector parent id'; the result is '2' - the value of APropertyID column in Assoc_Prop_Type_tbl
	# Output: INT
	def findAssocPropID(self, assocProp):
	
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT APropertyID FROM Assoc_Prop_Type_tbl WHERE APropName=" + `assocProp` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
			
		return -1

		
	# Get ATypeID of 'ATypeName'
	# ATypeName can be one of 'INSERT' or 'LOXP', and it represents either 'non-recombination' or 'recombination' vector type
	# Returns: int ATypeID (1 corresponds to non-recombination INSERT vector type; 2 - LOXP, recombination vector)
	# Input: aTypeName: string
	# Output: aTypeID: int
	def findATypeID(self, aTypeName):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT ATypeID FROM AssocType_tbl WHERE association=" + `aTypeName` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])

		return -1


	# Find the value of association identified by assocPropID
	# E.g. if assocPropID = '2', which corresponds to 'parent vector id', the result is a parent vector LIMS ID
	def findAssocPropValue(self, assocID, assocPropID):
	
		db = self.db
		cursor = self.cursor
	
		cursor.execute("SELECT propertyValue FROM AssocProp_tbl WHERE assID=" + `assocID` + " AND APropertyID=" + `assocPropID` + " AND status='ACTIVE'")
		result = cursor.fetchone()	# one-to-one property name to value mapping
		
		if result:
			return int(result[0])
			
		return -1
	

	# Nov. 5/08: Get a list of ALL values identified by assocID
	# Returns: dictionary of (assocPropID, assocPropValue) tuples, where assocPropID represents an association property type (e.g. 1 => Insert ID) and assocPropValue is an integer corresponding to reagent ID
	def findAssocPropValues(self, assocID):
	
		db = self.db
		cursor = self.cursor
		
		assocDict = {}
	
		cursor.execute("SELECT APropertyID, propertyValue FROM AssocProp_tbl WHERE assID=" + `assocID` + " AND status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			if result:
				assocPropID = int(result[0])
				assocPropVal = int(result[1])
				assocDict[assocPropID] = assocPropVal
			
		return assocDict
	
	
	# Aug. 11/09
	def createAssociation(self, assocName):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("INSERT INTO AssocType_tbl(association) VALUES(" + `assocName` + ")")
		assocID = int(db.insert_id())
		
		return assocID
	
	
	# Aug. 27/09: Delete (deprecate) a row in Assoc_Prop_Type_tbl identified by assocPropID
	# Input: assocPropID => INT, corresponds to APropertyID column value
	def deleteAssociationProperty(self, assocPropID):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE Assoc_Prop_Type_tbl SET status='DEP' where APropertyID=" + `assocPropID` + " AND status='ACTIVE'")
		
		
	# Aug. 27/09: Delete (deprecate) a row in AssocType_tbl, identified by aTypeID
	# Input: aTypeID => INT, corresponds to the value of ATypeID column in AssocType_tbl
	def deleteAssociation(self, aTypeID):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE AssocType_tbl SET status='DEP' where ATypeID=" + `aTypeID` + " AND status='ACTIVE'")
		
		
	# Jan. 28, 2010: remove a reagent type as parent of other types (e.g. if Insert is a parent of Vector and Insert reagent type is deleted, delete Insert as Vector parent
	def deleteReagentTypeAssociations(self, rTypeID):
		db = self.db
		cursor = self.cursor
		
		# If rTypeID = 3, deletes the following rows: (removes 'Oligo' (rtype 3) as parent of all other reagent types)
		# reagentTypeID	| APropName | alias | description | parentTypeID |
		# +-------------+---------------+----------------------------+--------------+
		#     2 | sense oligo | sense_oligo    | Sense Oligo ID       | 3 |
		#     2 | antisense oligo | antisense_oligo | Antisense Oligo ID   | 3 |
		#     1 | vector parent oligo | vector_parent_oligo | Sense Oligo ID   | 3 |
		
		cursor.execute("UPDATE Assoc_Prop_Type_tbl SET status='DEP' where assocTypeID=" + `rTypeID`)
	
	
##############################################################################
# ReagentAssociationHandler class
# Descendant of GeneralHandler; handles reagent associations
#
# Written March 16/07 by Marina
##############################################################################
class ReagentAssociationHandler(GeneralHandler):
	"Contains functions to fetch internal database IDs for Reagent associations"

	# Fetch the assID of rID from Association_tbl
	# Return: assID: INT
	def findReagentAssociationID(self, rID):

		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT assID FROM Association_tbl WHERE reagentID=" + `rID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			assocID = int(result[0])
			return assocID
			
		return -1


	# Nov. 27/07: For reagents that have more than one association type specify which association is requested
	# E.g. Inserts - can have ATypeID 4, corresponding to 'Insert Oligos' in AssocType_tbl, and ATypeID 6, corresponding to 'Insert Parent Vector' in AssocType_tbl
	#
	# Extra parameter assocPropID - corresponds to APropertyID column in Assoc_Prop_Type_tbl 
	# (e.g. '4' => 'insert parent vector id', 5 => 'sense oligo', 6 => 'antisense oligo', etc.)
	def findSpeReagentAssociationPropertyID(self, rID, assocPropID):

		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT a.assID FROM Association_tbl a, AssocProp_tbl p WHERE a.reagentID=" + `rID` + " AND p.APropertyID=" + `assocPropID` + "  AND p.assID=a.assID AND a.status='ACTIVE' AND p.status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			assocID = int(result[0])
			return assocID
			
		return -1
	
		
	# Find the Association type of reagent identified by rID 
	# Return: the value of ATypeID field in Association_tbl
	def findReagentAssociationType(self, rID):
	
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT ATypeID FROM Association_tbl WHERE reagentID=" + `rID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
			
		return -1

	# June 8/09 - Delete a specific parent/child value matching assocPropID
	def deleteReagentAssociationPropertyValue(self, assocID, assocPropID):
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE AssocProp_tbl SET status='DEP' WHERE assID=" + `assocID` + " AND APropertyID=" + `assocPropID` + " AND status='ACTIVE'")


	# June 8/09 - Delete ALL associations for a specific reagent (identified by assocID)
	def deleteAllAssociationPropertyValues(self, assocID):
		db = self.db
		cursor = self.cursor
		
		# deprecate associaion properties (AssocProp_tbl entries)
		cursor.execute("UPDATE AssocProp_tbl SET status='DEP' WHERE assID=" + `assocID` + " AND status='ACTIVE'")


##############################################################################
# ReagentTypeAssociationHandler class
# Descendant of GeneralHandler; handles reagent TYPE associations
#
# Written August 11, 2009 by Marina
##############################################################################
class ReagentTypeAssociationHandler(GeneralHandler):
	
	# Aug. 11/09, update Nov. 20/09 - save parent type
	def addReagentTypeAssociationPropertyValue(self, rTypeID, assocName, assocAlias, description, pAssocType, hierarchy="PARENT"):
		db = self.db
		cursor = self.cursor
		
		if not self.existsAssociationProperty(rTypeID, assocName, assocAlias, description, hierarchy):
			cursor.execute("INSERT INTO Assoc_Prop_Type_tbl(reagentTypeID, APropName, hierarchy, alias, description, assocTypeID) VALUES(" + `rTypeID` + ", " + `assocName` + ", " + `hierarchy` + ", " + `assocAlias` + ", " + `description` + ", " + `pAssocType` + ")")
		
		
	# Aug. 11/09 - Returns a dictionary of {APropertyID, description} tuples
	def getReagentTypeAssociations(self, rTypeID):
		db = self.db
		cursor = self.cursor

		assocDict = {}		# APropertyID, APropName

		cursor.execute("SELECT APropertyID, description FROM Assoc_Prop_Type_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			aPropID = int(result[0])
			aPropName = result[1]
			
			assocDict[aPropID] = aPropName
			
		return assocDict


	# Aug. 11/09
	def existsAssociationProperty(self, rTypeID, assocName, assocAlias, description, hierarchy):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT APropertyID FROM Assoc_Prop_Type_tbl WHERE reagentTypeID=" + `rTypeID` + " AND APropName=" + `assocName` + " AND alias=" + `assocAlias` + " AND description=" + `description` + " AND hierarchy=" + `hierarchy` + " AND status='ACTIVE'")
		results = cursor.fetchall()
		
		if not results or len(results) == 0:
			return False
		
		return True


	# Determine what a reagent's ATypeID should be according to its type
	# E.g. Inserts can only be of ATypeID 4 == "Insert Oligos"
	# Only applies to Inserts at the moment, its being the only reagent type using Python code for modification
	def findAssociationByReagentType(self, rTypeID):
		db = self.db
		cursor = self.cursor
		
		rMapper = ReagentTypeMapper(db, cursor)
		aHandler = AssociationHandler(db, cursor)
		
		aTypeID = -1

		rType_ID_Name_Map = rMapper.mapTypeIDName()
		rType_Name_ID_Map = rMapper.mapTypeNameID()
		
		if rTypeID == rType_Name_ID_Map["Insert"]:
			aTypeID = aHandler.findATypeID("Insert Oligos")
			
		elif rTypeID == rType_Name_ID_Map["CellLine"]:
			# Cell Line
			aTypeID = aHandler.findATypeID("CellLine Stable")
			
		elif rTypeID != rType_Name_ID_Map["Oligo"] and rTypeID != rType_Name_ID_Map["Vector"]:
			aTypeID = aHandler.findATypeID(rType_ID_Name_Map[rTypeID])
		
		# No need for Oligos; for Vectors it's more complicated since there are 3 different assoc types, so will determine them at another place, during a form action script

		return aTypeID
	
	
	# Aug. 27/09: Select all associations (currently parents) of the reagent type identified by rTypeID
	# Differs from getReagentTypeAssociations in that the dictionary values returned by this function are **names**
	# Return: dictionary of (APropertyID, propertyName) tuples, e.g. '1' -> 'insert id'
	def findReagentTypeAssocProps(self, rTypeID):
		db = self.db
		cursor = self.cursor
		
		rMapper = ReagentTypeMapper(db, cursor)
		aHandler = AssociationHandler(db, cursor)
		
		assocProps = {}
		
		# don't bother with hierarchy now; everything is 'PARENT' at this point --> in our current context, 'associations' = 'parents'
		cursor.execute("SELECT APropertyID, APropName FROM Assoc_Prop_Type_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			aPropID = int(result[0])
			aPropName = result[1]
			
			assocProps[aPropID] = aPropName
		
		return assocProps
		
		
	# Nov. 20/09
	def deleteReagentTypeAssociationProperties(self, rTypeID):
		db = self.db
		cursor = self.cursor
	
		# Update Assoc_Prop_Type_tbl only
		cursor.execute("UPDATE Assoc_Prop_Type_tbl SET status='DEP' WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
		

	# Nov. 20/09
	def updateParents(self, rTypeID, parentList):
		self.deleteReagentTypeAssociationProperties(rTypeID)
		self.addReagentTypeAssociationProperties(rTypeID, parentList)
		
		
	# Nov. 20/09
	def addReagentTypeAssociationProperties(self, rTypeID, parentList):
		db = self.db
		cursor = self.cursor
	
		aMapper = AssociationMapper(db, cursor)
		
		assoc_Name_Alias_Map = aMapper.mapAssocNameAlias()
		assoc_Name_Desc_Map = aMapper.mapAssocNameDescription()
		assoc_ID_ParentTypeID_Map = aMapper.mapAssocIDParentType()
		assoc_Name_ID_Map = aMapper.mapAssocNameID()
		
		for pAssocName in parentList:
			pAssocAlias = assoc_Name_Alias_Map(pAssocName)
			pAssocDesc = assoc_Name_Desc_Map[pAssocName]
			pAssocID = assoc_Name_ID_Map[pAssocName]
			pAssocType = assoc_ID_ParentTypeID_Map[pAssocID]
			
			self.addReagentTypeAssociationPropertyValue(rTypeID, pAssocName, pAssocAlias, pAssocDesc, pAssocType)
	
	
	# Nov. 20/09: Find assocTypeID column values in Assoc_Prop_Type_tbl - i.e. return the TYPE of reagent that maps to this assocPropID
	# e.g. 'vector parent id' => assocTypeID 1 (Vector)
	def findAssocParentType(self, rTypeID, assocPropID):
		db = self.db
		cursor = self.cursor
	
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print "SELECT assocTypeID FROM Assoc_Prop_Type_tbl WHERE APropertyID=" + `assocPropID` + " AND status='ACTIVE'"
	
		cursor.execute("SELECT assocTypeID FROM Assoc_Prop_Type_tbl WHERE APropertyID=" + `assocPropID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		
		return -1


	def isUsedReagentTypeAssociation(self, rTypeID, pTypeID):
		db = self.db
		cursor = self.cursor
	
		cursor.execute("SELECT p.propListID FROM Assoc_Prop_Type_tbl a, AssocProp_tbl p WHERE a.reagentTypeID=" + `rTypeID` + " AND a.assocTypeID=" + `pTypeID` + " AND p.APropertyID=a.APropertyID AND a.status='ACTIVE' AND p.status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			if int(result[0]):
				return True
		
		return False


	def findParentAssocType(self, rTypeID, pTypeID):
		db = self.db
		cursor = self.cursor
	
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print
		#print "SELECT APropertyID FROM Assoc_Prop_Type_tbl WHERE assocTypeID=" + `pTypeID` + " AND reagentTypeID= " + `rTypeID` + " AND status='ACTIVE'"

		cursor.execute("SELECT APropertyID FROM Assoc_Prop_Type_tbl WHERE assocTypeID=" + `pTypeID` + " AND reagentTypeID= " + `rTypeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])

		return -1


	# identical to findReagentTypeAssocProps, only returns a list of (APropertyID, assocTypeID) tuples - i.e. identifies the parent types of this reagent type
	# (E.g. Vector and Oligo are Insert parent types; Cell Line and Vector are Cell Line parent types)
	def findReagentAssociationParentTypes(self, rTypeID):
		
		db = self.db
		cursor = self.cursor
	
		rMapper = ReagentTypeMapper(db, cursor)
		aHandler = AssociationHandler(db, cursor)
		
		assocProps = {}
		
		# don't bother with hierarchy now; everything is 'PARENT' at this point --> in our current context, 'associations' = 'parents'
		cursor.execute("SELECT APropertyID, assocTypeID FROM Assoc_Prop_Type_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			aPropID = int(result[0])
			assocTypeID = int(result[1])
			
			assocProps[aPropID] = assocTypeID
		
		return assocProps
		
	
##############################################################################
# ReagentTypeHandler class
# Descendant of GeneralHandler; handles reagent types
#
# Written March 30/07 by Marina
##############################################################################
class ReagentTypeHandler(GeneralHandler):
	"Handles queries related to reagent type"

	# Fetch the type name that corresponds to rTypeID
	# E.g. rTypeID = 4; type name = "Cell Line"
	#
	# Input: rTypeID: INT, corresponds to reagentTypeID in ReagentType_tbl
	# Returns: rTypeName: STRING, corresponds to reagentTypeName in ReagentType_tbl
	def findReagentType(self, rTypeID):
		
		db = self.db
		cursor = self.cursor
	
		cursor.execute("SELECT reagentTypeName FROM ReagentType_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0]
		
		return None


	# Reverse action: Fetch the type **ID** that corresponds to rTypeName
	# E.g. rTypeName = "Cell Line"; rTypeID = 4
	#
	# Input: rTypeName: STRING, corresponds to reagentTypeName in ReagentType_tbl
	# Returns: rTypeID: INT, corresponds to reagentTypeID in ReagentType_tbl
	def findReagentTypeID(self, rTypeName):
		
		db = self.db
		cursor = self.cursor
	
		cursor.execute("SELECT reagentTypeID FROM ReagentType_tbl WHERE reagentTypeName=" + `rTypeName` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		
		return -1
	
	
	# June 3/09
	def findReagentTypePrefix(self, rTypeName):
		
		db = self.db
		cursor = self.cursor
	
		cursor.execute("SELECT reagent_prefix FROM ReagentType_tbl WHERE reagentTypeName=" + `rTypeName` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0]
		
		return None
	
	
	# April 3/09: Add a new reagent type
	def addReagentType(self, rTypeName, rPrefix):
		
		db = self.db
		cursor = self.cursor
	
		cursor.execute("INSERT INTO ReagentType_tbl(reagentTypeName, reagent_prefix) VALUES(" + `rTypeName` + ", " + `rPrefix` + ")")
		rTypeID = int(db.insert_id())
		
		return rTypeID
	
	
	def setReagentTypeName(self, rTypeID, rTypeName):
		
		db = self.db
		cursor = self.cursor
	
		if self.existsReagentTypeID(rTypeID):
			cursor.execute("UPDATE ReagentType_tbl SET reagentTypeName=" + `rTypeName` + " WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
	
	
	def existsReagentTypeID(self, rTypeID):
		
		db = self.db
		cursor = self.cursor
	
		cursor.execute("SELECT * FROM ReagentType_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return True
		
		return False
		
	
	def setReagentTypePrefix(self, rTypeID, rTypePrefix):
		
		db = self.db
		cursor = self.cursor
	
		if self.existsReagentTypeID(rTypeID):
			cursor.execute("UPDATE ReagentType_tbl SET reagent_prefix=" + `rTypePrefix` + " WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
	
	# April 8/09: Check if a reagent type exists
	def existsReagentType(self, rTypeName, rPrefix):
		
		db = self.db
		cursor = self.cursor
	
		cursor.execute("SELECT reagentTypeID FROM ReagentType_tbl WHERE reagentTypeName=" + `rTypeName` + " AND reagent_prefix=" + `rPrefix` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return True
		else:
			return False
		
	
	# June 3/09: Check if there are reagents of this type in the database
	def isEmpty(self, rTypeID):
		
		db = self.db
		cursor = self.cursor
	
		cursor.execute("SELECT count(reagentID) FROM Reagents_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			rCount = int(result[0])
			
			if rCount == 0:
				return True
			else:
				return False
		else:
			return True
		

	# June 3/09: delete reagent type IFF no reagents have been created for it!! 
	def deleteReagentType(self, rTypeID):

		db = self.db
		cursor = self.cursor
	
		rTypeName = self.findReagentType(rTypeID)
		rTypePrefix = self.findReagentTypePrefix(rTypeName)
		
		# Can place a check here, only this function is invoked from one place only - reagent type detailed view (fresh after creation or from searching), by pressing the 'Delete' button - which is disabled if there are reagents of this type in the database.  With this function being a standalone procedure, whose task is simple deletion of a row in ReagentType_tbl, not placing any additional checks here; it is the responsibility of the referring page.
		
		cursor.execute("UPDATE ReagentType_tbl SET status='DEP' where reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")


######################################################################################
# ReagentTypePropertyHandler class
# Descendant of GeneralHandler; handles queries related specifically to reagent type 
# properties/attributes
#
# Written August 31/09 by Marina
######################################################################################
class ReagentTypePropertyHandler(GeneralHandler):
	"Handles queries related specifically to reagent type properties/attributes"
	
	# May 7/09
	def findReagentTypeAttributeNamesByCategory(self, rTypeID, pCategoryID):
		db = self.db
		cursor = self.cursor
		
		rTypeAttributeNames = []
		
		cursor.execute("SELECT p.propertyName FROM ReagentTypeAttributes_tbl t, ReagentPropType_tbl p, ReagentPropertyCategories_tbl c WHERE t.reagentTypeID=" + `rTypeID` + " AND t.propertyTypeID=c.propCatID AND c.categoryID=" + `pCategoryID` + " AND c.propID=p.propertyID AND t.status='ACTIVE' AND p.status='ACTIVE' AND c.status='ACTIVE'")
		results = cursor.fetchall()

		for result in results:
			rTypeAttributeNames.append(result[0])
			
		return rTypeAttributeNames
		
		
	# May 13, 2010: if have attribute names, why not have attribute IDs?
	def findReagentTypeAttributeIDsByCategory(self, rTypeID, pCategoryID):
		db = self.db
		cursor = self.cursor
		
		rTypeAttributeIDs = []
		
		#print "Content-type:text/html"
		#print
		#print "SELECT t.propertyTypeID FROM ReagentTypeAttributes_tbl t, ReagentPropType_tbl p, ReagentPropertyCategories_tbl c WHERE t.reagentTypeID=" + `rTypeID` + " AND t.propertyTypeID=c.propCatID AND c.categoryID=" + `pCategoryID` + " AND c.propID=p.propertyID AND t.status='ACTIVE' AND p.status='ACTIVE' AND c.status='ACTIVE'"
		
		cursor.execute("SELECT t.reagentTypePropertyID FROM ReagentTypeAttributes_tbl t, ReagentPropType_tbl p, ReagentPropertyCategories_tbl c WHERE t.reagentTypeID=" + `rTypeID` + " AND t.propertyTypeID=c.propCatID AND c.categoryID=" + `pCategoryID` + " AND c.propID=p.propertyID AND t.status='ACTIVE' AND p.status='ACTIVE' AND c.status='ACTIVE'")
		results = cursor.fetchall()

		for result in results:
			rTypeAttributeIDs.append(int(result[0]))
			
		return rTypeAttributeIDs


	# Correction June 22/09: This returns a dictionary of {attributeID => propCatID}
	def findAllReagentTypeAttributes(self, rTypeID):
		
		db = self.db
		cursor = self.cursor
		
		rTypeAttributeIDs = {}
		
		#print "Content-type:text/html"
		#print
		#print "SELECT reagentTypePropertyID, propertyTypeID FROM ReagentTypeAttributes_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'"
		
		cursor.execute("SELECT reagentTypePropertyID, propertyTypeID FROM ReagentTypeAttributes_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
		results = cursor.fetchall()

		for result in results:
			rTypeAttributeIDs[int(result[0])] = int(result[1])
			
		return rTypeAttributeIDs
	
	
	# April 11, 2009: Add a property to set of properties for a given reagent type
	# Correction June 19/09: Use the composite key propCatID of ReagentPropertyCategories_tbl - represents a property in conjunction with a category
	#def addReagentTypeAttribute(self, rTypeID, propTypeID, categoryID):
	def addReagentTypeAttribute(self, rTypeID, propCatID):
		db = self.db
		cursor = self.cursor
		
		cursor.execute("INSERT INTO ReagentTypeAttributes_tbl(reagentTypeID, propertyTypeID) VALUES(" + `rTypeID` + ", " + `propCatID` + ")")
		newReagentTypePropID = int(db.insert_id())
		
		return newReagentTypePropID
	

	# July 28/09: Given the values of reagentTypeID and propertyTypeID columns in ReagentTypeAttributes_tbl, return the value of reagentTypePropertyID (the primary key)
	def findReagentTypeAttributeID(self, rTypeID, propID):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT reagentTypePropertyID FROM ReagentTypeAttributes_tbl WHERE reagentTypeID=" + `rTypeID` + " AND propertyTypeID=" + `propID` + " AND status='ACTIVE'")
		result = cursor.fetchone()	# one-to-one mapping of reagent type to a property within a specific category
		
		if result:
			return int(result[0])
		
		return -1
	
	# April 11, 2009: Check if a property exists in ReagentTypeAttributes_tbl, i.e. if a given property is associated with a given reagent type
	#
	# input: rTypeID - datbase identifier for a given reagent type (e.g. '1' -> 'Vector)
	#	 pTypeID - datbase identifier for a given property type (e.g. '1' -> 'Name')
	#
	# Return: True or False
	# Correction June 19/09: Properties are now used in conjunction with their categories; therefore, use a composite key "propCatID" in query
	#def existsReagentTypeAttribute(self, rTypeID, pTypeID):
	def existsReagentTypeAttribute(self, rTypeID, propCatID):
		db = self.db
		cursor = self.cursor
		
		#cursor.execute("SELECT * FROM ReagentTypeAttributes_tbl WHERE reagentTypeID=" + `rTypeID` + " AND propertyTypeID=" + `pTypeID` + " AND status='ACTIVE'")
		cursor.execute("SELECT * FROM ReagentTypeAttributes_tbl WHERE reagentTypeID=" + `rTypeID` + " AND propertyTypeID=" + `propCatID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			return True
		else:
			return False
	
	# June 3/09
	def deleteReagentTypeAttribute(self, rTypeID, pID):
		
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print "UPDATE ReagentTypeAttributes_tbl SET status='DEP' WHERE reagentTypeID=" + `rTypeID` + " AND propertyTypeID=" + `pID` + " AND status='ACTIVE'"
		
		cursor.execute("UPDATE ReagentTypeAttributes_tbl SET status='DEP' WHERE reagentTypeID=" + `rTypeID` + " AND propertyTypeID=" + `pID` + " AND status='ACTIVE'")
	
	
	# June 14, 2010
	def isDropdown(self, rTypeAttrID):
	
		if self.getAttributeInputFormat(rTypeAttrID) == "predefined":
			return True
		else:
			return False
		
	
	# Sept. 4/09
	def getAttributeInputFormat(self, rTypeAttrID):
		
		db = self.db
		cursor = self.cursor
		
		sHandler = SystemSetHandler(db, cursor)
		
		# Change June 14, 2010: Now that we're allowing blank dropdown lists if 'Other' is checked, need to explore all possibilities: a) is_customizeable? b) is_multiple? c) has values?  If the property has been declared a hyperlink, assume freetext (dropdowns cannot be hyperlinked unless in error; similarly, freetext fields cannot be made multiple/customizeable)
		if self.isHyperlink(rTypeAttrID):
			return "freetext"
		else:
			if self.isCustomizeable(rTypeAttrID) or self.isMultiple(rTypeAttrID):
				return "predefined"
			else:
				# This may still be a dropdown, single and non-customizeable
				# check if values have been set for it
				# For completeness and consistency, use findReagentTypeAttributeSetValues() here, not findReagentTypeAttributeSetIDs()
				# (remember: attributeSetID should be deleted when going from dropdown to freetext!)
				if len(sHandler.findReagentTypeAttributeSetValues(rTypeAttrID)) > 0:
					return "predefined"
				else:
					return "freetext"


	# Sept. 10/09: Check if property propID is used by reagents of type rTypeID (i.e. if values of this property have been assigned to reagents of this type) - needed for reagent type modification
	def propertyUsedForReagentType(self, rTypeID, propID, isDescriptor=False):
		db = self.db
		cursor = self.cursor
		
		if isDescriptor:
			cursor.execute("SELECT COUNT(p.propListID) FROM ReagentPropList_tbl p, Reagents_tbl r WHERE p.reagentID=r.reagentID AND r.reagentTypeID=" + `rTypeID` + " AND p.propertyID=" + `propID` + " AND p.descriptor != '' AND p.descriptor != 'NULL' AND p.status='ACTIVE' AND r.status='ACTIVE'")
			
		
		cursor.execute("SELECT COUNT(p.propListID) FROM ReagentPropList_tbl p, Reagents_tbl r WHERE r.reagentTypeID=" + `rTypeID` + " AND r.reagentID=p.reagentID AND p.propertyID=" + `propID` + " AND r.status='ACTIVE' AND p.status='ACTIVE'")
		
		#results = cursor.fetchall()
		result = cursor.fetchone()
		
		if result:
			if int(result[0]) > 0:
				return True	# property is used
		
		#return len(results) > 0
		
		return False	# not used
	
	# Oct. 15/09 - propID is propCatID
	def isUsedProperty(self, rTypeID, propID, isDescriptor=False):
		
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print
		
		# removed Jan. 21, 2010: queries should be different but they are identical here!
		#if isDescriptor:
			#cursor.execute("SELECT COUNT(p.propListID) FROM ReagentPropList_tbl p, Reagents_tbl r WHERE r.reagentTypeID=" + `rTypeID` + " AND p.reagentID=r.reagentID AND p.propertyID=" + `propID` + " AND p.status='ACTIVE' AND r.status='ACTIVE'")
			
		#else:
		
		cursor.execute("SELECT COUNT(p.propListID) FROM ReagentPropList_tbl p, Reagents_tbl r WHERE r.reagentTypeID=" + `rTypeID` + " AND p.reagentID=r.reagentID AND p.propertyID=" + `propID` + " AND p.status='ACTIVE' AND r.status='ACTIVE'")

		result = cursor.fetchone()
		
		if result:
			if int(result[0]) > 0:
				return True
		
		return False


	# Jan. 29/10 - check if actual property value is used
	# propID == propCatID (property in category)
	def existsPropertyValue(self, rTypeID, propID, propVal, isDescriptor=False):
		
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO

		if isDescriptor:
			cursor.execute("SELECT COUNT(p.propListID) FROM ReagentPropList_tbl p, Reagents_tbl r WHERE r.reagentTypeID=" + `rTypeID` + " AND p.reagentID=r.reagentID AND p.propertyID=" + `propID` + " AND p.descriptor LIKE '%" + propVal + "%' AND p.status='ACTIVE' AND r.status='ACTIVE'")
			
		else:
			#print "SELECT COUNT(p.propListID) FROM ReagentPropList_tbl p, Reagents_tbl r WHERE r.reagentTypeID=" + `rTypeID` + " AND p.reagentID=r.reagentID AND p.propertyID=" + `propID` + " AND p.propertyValue LIKE '%" + propVal + "%' AND p.status='ACTIVE' AND r.status='ACTIVE'"
			
			cursor.execute("SELECT COUNT(p.propListID) FROM ReagentPropList_tbl p, Reagents_tbl r WHERE r.reagentTypeID=" + `rTypeID` + " AND p.reagentID=r.reagentID AND p.propertyID=" + `propID` + " AND p.propertyValue LIKE '%" + propVal + "%' AND p.status='ACTIVE' AND r.status='ACTIVE'")

		result = cursor.fetchone()
		
		if result:
			if int(result[0]) > 0:
				return True
		
		return False

	# Nov. 16/09: Given reagentTypePropertyID column value, return the value of propertyTypeID column
	def findReagentTypeAttributePropertyID(self, rTypeAttrID):
		
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"
		#print
		#print "SELECT propertyTypeID FROM ReagentTypeAttributes_tbl WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'"
		
		cursor.execute("SELECT propertyTypeID FROM ReagentTypeAttributes_tbl WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		
		return -1
	
	
	# April 9, 2010
	def getReagentTypePropertyOrdering(self, rTypeID, propCatID):
		
		db = self.db
		cursor = self.cursor
	
		if rTypeID and rTypeID > 0 and propCatID and propCatID > 0:
			cursor.execute("SELECT ordering FROM ReagentTypeAttributes_tbl WHERE reagentTypeID=" + `rTypeID` + " AND propertyTypeID=" + `propCatID` + " AND status='ACTIVE'")
			
			#print "Content-type:text/html"
			#print
			#print "SELECT ordering FROM ReagentTypeAttributes_tbl WHERE reagentTypeID=" + `rTypeID` + " AND propertyTypeID=" + `propCatID` + " AND status='ACTIVE'"
			
			result = cursor.fetchone()
			
			if result:
				return int(result[0])
		
		return -1

	# April 9, 2010
	def setReagentTypeAttributeOrdering(self, rTypeAttrID, pOrder):
		
		db = self.db
		cursor = self.cursor
	
		# status='ACTIVE' check might be redundant but still
		cursor.execute("UPDATE ReagentTypeAttributes_tbl SET ordering=" + `pOrder` + " WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'")
		
		
	# April 29, 2010
	def makeMultiple(self, rTypeAttrID):
	
		db = self.db
		cursor = self.cursor
	
		cursor.execute("UPDATE ReagentTypeAttributes_tbl SET is_multiple='YES' WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'")
		
	# April 29, 2010
	def makeSingle(self, rTypeAttrID):
	
		db = self.db
		cursor = self.cursor
	
		cursor.execute("UPDATE ReagentTypeAttributes_tbl SET is_multiple='NO' WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'")
		
		
	def makeCustomizeable(self, rTypeAttrID):
	
		db = self.db
		cursor = self.cursor
	
		cursor.execute("UPDATE ReagentTypeAttributes_tbl SET is_customizeable='YES' WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'")


	def removeCustomizeable(self, rTypeAttrID):
	
		db = self.db
		cursor = self.cursor
	
		cursor.execute("UPDATE ReagentTypeAttributes_tbl SET is_customizeable='NO' WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'")
		
		
	# April 29, 2010
	def makeHyperlink(self, rTypeAttrID):
	
		db = self.db
		cursor = self.cursor
	
		cursor.execute("UPDATE ReagentTypeAttributes_tbl SET is_hyperlink='YES' WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'")


	# April 29, 2010
	def removeHyperlink(self, rTypeAttrID):
	
		db = self.db
		cursor = self.cursor
	
		cursor.execute("UPDATE ReagentTypeAttributes_tbl SET is_hyperlink='NO' WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'")
	
	# April 29, 2010
	def isMultiple(self, rTypeAttrID):

		db = self.db
		cursor = self.cursor
	
		cursor.execute("SELECT is_multiple FROM ReagentTypeAttributes_tbl WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'")
		
		result = cursor.fetchone()
		
		if result[0] == 'YES':
			return True
		else:
			return False
	
	
	# April 29, 2010
	def isHyperlink(self, rTypeAttrID):

		db = self.db
		cursor = self.cursor
	
		#print "Content-type:text/html"
		#print
		#print "SELECT is_hyperlink FROM ReagentTypeAttributes_tbl WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'"
		
		cursor.execute("SELECT is_hyperlink FROM ReagentTypeAttributes_tbl WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'")
		
		result = cursor.fetchone()
		#print result[0]
		
		if result[0] == 'YES':
			return True
		else:
			return False
	
	# May 11, 2010
	def isCustomizeable(self, rTypeAttrID):

		db = self.db
		cursor = self.cursor
	
		#print "Content-type:text/html"
		#print
		#print "SELECT is_hyperlink FROM ReagentTypeAttributes_tbl WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'"
		
		cursor.execute("SELECT is_customizeable FROM ReagentTypeAttributes_tbl WHERE reagentTypePropertyID=" + `rTypeAttrID` + " AND status='ACTIVE'")
		
		result = cursor.fetchone()
		#print result[0]
		
		if result[0] == 'YES':
			return True
		else:
			return False
	
	
######################################################################################
# UserCategoryHandler class
# Descendant of GeneralHandler; handles User and Lab categories and access privileges
#
# Written July 3/07 by Marina
######################################################################################
class UserCategoryHandler(GeneralHandler):
	"Handles queries related to User and Lab categories and access privileges"
	
	# Fetch all category names - Reader, Writer, Creator, Admin
	# Return: dictionary of (categoryID, category) tuples (e.g. ('1', 'Admin')
	def findAllCategories(self):
		
		db = self.db
		cursor = self.cursor
		
		categories = {}		# dictionary of (categoryID, category) tuples
		
		cursor.execute("SELECT categoryID, category FROM UserCategories_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			catID = int(result[0])
			category = result[1]
			
			categories[catID] = category
			
		return categories


###################################################################################################################################
# SystemSetHandler class
# Subclass of GeneralHandler class
# Written November 2, 2006, by Marina Olhovsky; migrated to general_handler.py module October 2009 (previously was a separate module)
#
# Note on table structure change Nov. 16/09:
# - removed groupName column from System_Set_Groups_tbl
# - replaced groupDesc column values by CONCAT category + property (e.g. 'General Properties Name')
# - propertyIDLink column is now a foreign key referencing ReagentPropertyCategories_tbl(propCatID)
#
# (the purpose is to enable dropdown list value separation across reagent types)
###################################################################################################################################
class SystemSetHandler(GeneralHandler):

	"This class handles reagent properties in OpenFreezer that are shown as dropdown lists on various views"

	def __init__(self, db, cursor):
		self.db = db
		self.cursor = cursor


	# Nov. 10/09
	def existsReagentTypeAttributeSetValue(self, rTypeAttrID, ssetID):
		
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"
		#print
		#print "SELECT reagentTypeAttributeSetID FROM ReagentTypeAttribute_Set_tbl WHERE reagentTypeAttributeID=" + `rTypeAttrID` + " AND ssetID=" + `ssetID` + " AND status='ACTIVE'"
		
		cursor.execute("SELECT reagentTypeAttributeSetID FROM ReagentTypeAttribute_Set_tbl WHERE reagentTypeAttributeID=" + `rTypeAttrID` + " AND ssetID=" + `ssetID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			if int(result[0]) > 0:
				return True
			
		return False


	# Nov. 10/09 - THIS DOES NOT DELETE ACTUAL System_Set_Group_tbl OR System_Set_tbl VALUES -- ONLY DELETES ReagentTypeAttribute_Set_tbl ROWS!!!!!!
	def deleteReagentTypeAttributeSetValues(self, rTypeAttrID):
		
		db = self.db
		cursor = self.cursor
		
		# multiple dropdown values for one attribute ID
		cursor.execute("UPDATE ReagentTypeAttribute_Set_tbl SET status='DEP' WHERE reagentTypeAttributeID=" + `rTypeAttrID` + " AND status='ACTIVE'")
	

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
			return int(db.insert_id())
		else:
			return self.findSetValueID(setGroupID, entityVal)


	# Nov. 10/09
	def findSetValueID(self, setGroupID, entityVal):
		db = self.db
		cursor = self.cursor
	
		cursor.execute("SELECT ssetID FROM System_Set_tbl WHERE ssetGroupID=" + `setGroupID` + " AND entityName=" + `entityVal` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		
		return -1
	
	
	# Nov. 9/09
	def existsSetGroup(self, propCatID):
		db = self.db
		cursor = self.cursor
		
		return self.findPropSetGroupID(propCatID) > 0
	
	
	# Nov. 9/09
	def findPropSetGroupID(self, propCatID):
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"
		#print
		#print "SELECT ssetGroupID FROM System_Set_Groups_tbl WHERE propertyIDLink=" + `propCatID` + " AND status='ACTIVE'"
		
		cursor.execute("SELECT ssetGroupID FROM System_Set_Groups_tbl WHERE propertyIDLink=" + `propCatID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
		
		return -1
	
		
	# Nov. 9/09
	def addReagentTypeAttributeSetEntry(self, rTypeAttrID, ssetID):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("INSERT INTO ReagentTypeAttribute_Set_tbl(reagentTypeAttributeID, ssetID) VALUES(" + `rTypeAttrID` + ", " + `ssetID` + ")")
		
		return int(db.insert_id())
		
	
	# Nov. 9/09: Make an enty in System_Set_Groups_tbl for propCatID
	# Return the new ssetGroupID
	def addSetGroupID(self, propCatID, groupDesc):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("INSERT INTO System_Set_Groups_tbl(propertyIDLink, groupDesc) VALUES(" + `propCatID` + ", " + `groupDesc` + ")")
		
		return int(db.insert_id())
	
	
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
		
		cursor.execute("SELECT DISTINCT(s.entityName) FROM System_Set_tbl s, System_Set_Groups_tbl g WHERE g.ssetGroupID=" + `ssetGroupID` + " AND g.ssetGroupID=s.ssetGroupID AND g.status='ACTIVE' AND s.status='ACTIVE' ORDER BY s.entityName")
		results = cursor.fetchall()
		
		for result in results:
			setVal = result[0]
			setValues.append(setVal)
			
		return setValues

	
	# Nov. 16/09: This is an auxiliary function; it performs the same query as findAllSetValues, but returns actual ssetID column values
	def findAllSetIDs(self, ssetGroupID):
		
		db = self.db
		cursor = self.cursor
		
		setIDs = []
		
		#cursor.execute("SELECT s.entityName FROM System_Set_tbl s, System_Set_Groups_tbl g WHERE g.groupName=" + `ssetGroupName` + " AND g.ssetGroupID=s.ssetGroupID AND g.status='ACTIVE' AND s.status='ACTIVE' ORDER BY s.entityName")
		
		cursor.execute("SELECT DISTINCT(s.ssetID) FROM System_Set_tbl s, System_Set_Groups_tbl g WHERE g.ssetGroupID=" + `ssetGroupID` + " AND g.ssetGroupID=s.ssetGroupID AND g.status='ACTIVE' AND s.status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			setID = int(result[0])
			setIDs.append(setID)
			
		return setIDs
		
	
	def findAllPropSetValues(self, propCatID):
		
		db = self.db
		cursor = self.cursor
		
		setValues = []
		
		ssetGroupID = self.findPropSetGroupID(propCatID)
		setValues = self.findAllSetValues(ssetGroupID)
		
		return setValues


	# Oct. 13/09: Find set values for a particular property in a particular category assigned to a particular reagent
	# i.e. output a subset of values returned by findAllPropSetValues() function above
	# Updated Nov. 16/09
	def findReagentTypeAttributeSetValues(self, attrID):
		
		db = self.db
		cursor = self.cursor
		
		setValues = []
		
		# Nov. 16/09
		setIDs = self.findReagentTypeAttributeSetIDs(attrID)
		
		#cursor.execute("SELECT ssetID FROM ReagentTypeAttribute_Set_tbl WHERE reagentTypeAttributeID=" + `attrID` + " AND status='ACTIVE'")
		#results = cursor.fetchall()
		
		#for result in results:
		for ssetID in setIDs:
			#ssetID = int(result[0])
			setVal = self.findSetValue(ssetID)

			if setVal:
				setValues.append(setVal)
	
		return setValues
			
			
	# Nov. 16/09: Auxiliary function - performs the same query as findReagentTypeAttributeSetValues, but returns ssetID column values for a given attrID
	def findReagentTypeAttributeSetIDs(self, attrID):
		
		#print "SELECT ssetID FROM ReagentTypeAttribute_Set_tbl WHERE reagentTypeAttributeID=" + `attrID` + " AND status='ACTIVE'"
		
		db = self.db
		cursor = self.cursor
		
		setIDs = []
		
		cursor.execute("SELECT ssetID FROM ReagentTypeAttribute_Set_tbl WHERE reagentTypeAttributeID=" + `attrID` + " AND status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			ssetID = int(result[0])
			
			if ssetID:
				setIDs.append(ssetID)
	
		#print setIDs
		return setIDs


	# Nov. 10/09
	def findSetValue(self, ssetID):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT DISTINCT(entityName) FROM System_Set_tbl WHERE ssetID =" + `ssetID` + " AND status='ACTIVE' ORDER BY entityName")
		result = cursor.fetchone()
		
		if result:
			return result[0]
		
		return None
	
	
	# Nov. 16/09
	def deleteSetValue(self, ssetID):
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print "UPDATE System_Set_tbl SET status='DEP' WHERE ssetID=" + `ssetID` + " AND status='ACTIVE'"
		
		cursor.execute("UPDATE System_Set_tbl SET status='DEP' WHERE ssetID=" + `ssetID` + " AND status='ACTIVE'")
		
	
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


	# Oct. 15/09 - propID is propCatID
	def isUsedSetValue(self, rTypeID, propID, setValue, isDescriptor=False):
		
		db = self.db
		cursor = self.cursor
		
		if isDescriptor:
			cursor.execute("SELECT COUNT(p.propListID) FROM ReagentPropList_tbl p, Reagents_tbl r WHERE r.reagentTypeID=" + `rTypeID` + " AND p.reagentID=r.reagentID AND p.propertyID=" + `propID` + " AND p.descriptor=" + `setValue` + " AND p.status='ACTIVE' AND r.status='ACTIVE'")
			
		else:
			cursor.execute("SELECT COUNT(p.propListID) FROM ReagentPropList_tbl p, Reagents_tbl r WHERE r.reagentTypeID=" + `rTypeID` + " AND p.reagentID=r.reagentID AND p.propertyID=" + `propID` + " AND p.propertyValue=" + `setValue` + " AND p.status='ACTIVE' AND r.status='ACTIVE'")
			
		result = cursor.fetchone()
		
		if result:
			if int(result[0]) > 0:
				return True
		
		return False