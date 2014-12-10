import MySQLdb

import re, string, types

from string import *

from general_handler import *
from sequence_handler import *
from comment_handler import CommentHandler
from reagent import Reagent, Vector, Insert, Oligo, CellLine
from mapper import ReagentPropertyMapper, ReagentAssociationMapper, ReagentTypeMapper
from exception import *

import utils
from sequence_feature import SequenceFeature
from sequence import *
from location_database_handler import LocationHandler

import Bio
from Bio.Seq import Seq
#from Bio import Enzyme
from Bio.Restriction import *

##################################################################################################################
# This module contains classes that map Reagent objects to corresponding database entities in OpenFreezer
# All SQL queries are performed by these classes, thereby encapsulating the database layer and separating it from the object model

# Written by: Marina Olhovsky
# Last modified: March 19, 2010
##################################################################################################################

###################################################################
# ReagentHandler class
# Written October 12, 2006, by Marina Olhovsky
# Last modified: August 7, 2008
###################################################################

# Top-level abstraction for ReagentHandler hierarchy; extends GeneralHandler
class ReagentHandler(GeneralHandler):
	"Top-level abstraction for ReagentHandler hierarchy"

	def __init__(self, db, cursor):
		super(ReagentHandler, self).__init__(db, cursor)

	# Create a Reagent object matching an internal db reagent ID (rID parameter)
	def createReagent(self, rID):
		
		db = self.db
		cursor = self.cursor
		
		rTypeID = self.findReagentTypeID(rID)	# fetch the type of this reagent
		rGroup = self.findReagentGroup(rID)	# fetch the reagent's group ID
		
		rMapper = ReagentTypeMapper(db, cursor)
		rType_ID_Name_Map = rMapper.mapTypeIDName()

		# Create an appropriate type of reagent based on type ID
		if rTypeID == 1:
			reagent = Vector(rGroup)
		elif rTypeID == 2:
			reagent = Insert(rGroup)
		elif rTypeID == 3:
			reagent = Oligo(rGroup)
		elif rTypeID == 4:
			reagent = CellLine(rGroup)
		else:
			# Updated April 27/09: Adding ability to create new reagent types
			#return		# removed April 27/09
			reagent = Reagent(rGroup, rType_ID_Name_Map[rTypeID])
		
		# fetch the properties of this reagent and assign them to the newly created instance
		propDict = self.findAllReagentPropertiesByName(rID)
		reagent.setProperties(propDict)
		
		# same for associations
		assocDict = self.findAllReagentAssociationsByName(rID)
		reagent.setAssociations(assocDict)
		
		# oct. 23/08: - UPDATED FEB. 2/09, REVERT IF NOT WORKING
		#seqFeatureNames = reagent.getSequenceFeatures()
		#features = self.findAllReagentFeatures(reagent, rID, seqFeatureNames)
		features = self.findAllReagentFeatures(reagent, rID)
		
		reagent.setFeatures(features)
		#print `features`
		
		return reagent
		
	# Update Feb. 2/09 - why need seqFeatureNames parameter if passing 'reagent' and can get its features through it?
	#def findAllReagentFeatures(self, reagent, rID, seqFeatureNames):
	def findAllReagentFeatures(self, reagent, rID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		#seqFeatureNames = reagent.getSequenceFeatures()	# added Feb. 2/09 - Removed Sept. 10/09, see fix below

		mapper = ReagentPropertyMapper(db, cursor)
		pHandler = ReagentPropertyHandler(db, cursor)
		rtPropHandler = ReagentTypePropertyHandler(db, cursor)
		
		prop_Name_ID_Map = mapper.mapPropNameID()	# (prop name, prop id)
		prop_ID_Name_Map = mapper.mapPropIDName()	# (prop id, prop name)
		
		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()
		
		# Update Sept. 10/09: No longer can rely on preset list - must retrieve database values according to reagent type
		rTypeID = self.findReagentTypeID(rID)
		
		seqFeatureNames = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, prop_Category_Name_ID_Map["DNA Sequence Features"])
		
		desciptorMap = reagent.getFeatureDescriptors()
		
		sf_list = ""
		
		features = []		# resulting list
		
		if len(seqFeatureNames) == 0:
			return features
		
		for sf in seqFeatureNames:
			# update July 2/09
			sfID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[sf], prop_Category_Name_ID_Map["DNA Sequence Features"])
			#sfID = prop_Name_ID_Map[sf]
			
			if len(sf_list) == 0:
				sf_list += `sfID`
			else:
				sf_list += ", " + `sfID`
		
		# Update March 8, 2010: changed selection to "...AND p.propertyID IN (" + sf_list + ") ...", NOT t.propertyID!!!!!!
		cursor.execute("SELECT t.propertyID, propertyValue, startPos, endPos, direction, descriptor FROM ReagentPropList_tbl p, ReagentPropertyCategories_tbl c, ReagentPropType_tbl t WHERE reagentID= " + `rID` + " AND p.propertyID=propCatID AND t.propertyID=c.propID AND p.propertyID IN (" + sf_list + ") AND p.status='ACTIVE' AND t.status='ACTIVE' AND c.status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			fID = int(result[0])
			fName = prop_ID_Name_Map[fID]
			fVal = result[1]
			fStart = int(result[2])
			fEnd = int(result[3])
			fDir = result[4]
			
			if desciptorMap.has_key(fName):
				fDescrType = desciptorMap[fName]
			else:
				fDescrType = ""
				
			fDescr = result[5]
			
			f = SequenceFeature(fName, fVal, fStart, fEnd, fDir, fDescrType, fDescr)
			features.append(f)
			
		return features
	
	
	# Added March 30/07: Create a new database Reagent entry of the given type
	# Input: rType: STRING, verbal reagent type name, e.g. Vector, Oligo, etc.
	# Output: database ID of new reagent entry
	def createNewReagent(self, rType):
	
		db = self.db
		cursor = self.cursor	# for easy access
		
		tHandler = ReagentTypeHandler(db, cursor)
		
		'''
		MGC_START = 50000	# constant, lowest group ID assigned to MGC clones
		
		tHandler = ReagentTypeHandler(db, cursor)
		tMapper = ReagentTypeMapper(db, cursor)
		typeMap = tMapper.mapTypeNameID()
		'''
		
		newGroupID = -1
		newReagentID = -1
		
		# find the type ID of rType
		rTypeID = tHandler.findReagentTypeID(rType)
		
		# Sept. 4/07: At the moment MGC clones are parsed into the system from an external source; therefore, assume non-MGC group ID is required
		newGroupID = self.findNextGroupID(rTypeID, False)
		
		cursor.execute("INSERT INTO Reagents_tbl(reagentTypeID, groupID) VALUES(" + `rTypeID` + ", " + `newGroupID` + ")")
		newReagentID = int(db.insert_id())
		
		return newReagentID
		
		
	# Find the next highest group ID for the given reagent type
	# If isMGC == True, this reagent is an MGC clone; its group ID is >= 50,000
	
	# November 19, 2009: IMPORTANT: Now, that we allow reagent deletion, if a reagent, e.g. V1, has been deleted and its status set to DEP, CANNOT select '1' again as the next ACTIVE group ID, because, essentially, V1 may not be re-used.
	def findNextGroupID(self, rTypeID, isMGC=False):
		
		db = self.db
		cursor = self.cursor	# for easy access
		
		tHandler = ReagentTypeHandler(db, cursor)
		tMapper = ReagentTypeMapper(db, cursor)
		typeMap = tMapper.mapTypeNameID()
		
		# Remember group IDs > 50000 for MGC clones - if the new reagent is a Vector or an Insert, find the highest current group ID LESS THAN 50,000 - cutoff for MGC clones.
		MGC_START = 50000	# constant, lowest group ID assigned to MGC clones
		
		# Jan. 27, 2011: Opensourcing, sending empty database - MAX(groupID) will return null and cause an error
		cursor.execute("SELECT COUNT(reagentID) FROM Reagents_tbl WHERE reagentTypeID=" + `rTypeID`)
		result = cursor.fetchone()

		if int(result[0]) == 0:
			return 1

		newGroupID = -1
		
		if rTypeID == typeMap["Vector"] or rTypeID == typeMap["Insert"]:
			
			# select highest non-MGC group ID - Update Nov. 20/09: DO NOT REUSE IDs, i.e. DON'T CHECK STATUS='ACTIVE'
			#cursor.execute("SELECT MAX(groupID) FROM Reagents_tbl WHERE reagentTypeID=" + `rTypeID` + " AND groupID < " + `MGC_START` + " AND status='ACTIVE'")
			
			cursor.execute("SELECT MAX(groupID) FROM Reagents_tbl WHERE reagentTypeID=" + `rTypeID` + " AND groupID < " + `MGC_START`)
			result = cursor.fetchone()
			
			if result:
				newGroupID = int(result[0]) + 1		# next available group ID
			else:
				# This is the case where non-MGC group IDs have reached 50,000.  In this case, just assign the next highest group ID, without regard to MGC
				
				#cursor.execute("SELECT MAX(groupID) FROM Reagents_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
				# Update Nov. 20/09: DO NOT REUSE IDs, i.e. DON'T CHECK STATUS='ACTIVE'
				cursor.execute("SELECT MAX(groupID) FROM Reagents_tbl WHERE reagentTypeID=" + `rTypeID`)
				result = cursor.fetchone()	
				newGroupID = int(result[0]) + 1
		
		#else:	# removed April 27/09
		
		elif rTypeID == typeMap["Oligo"] or rTypeID == typeMap["CellLine"]:		# replaced April 27/09
			# For Oligos and Cell Lines, there's no MGC restriction - just select the highest group ID
			# But Dec. 7/09 - same restriction as above - don't reuse!
			#cursor.execute("SELECT MAX(groupID) FROM Reagents_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
			cursor.execute("SELECT MAX(groupID) FROM Reagents_tbl WHERE reagentTypeID=" + `rTypeID`)
			result = cursor.fetchone()	
			newGroupID = int(result[0]) + 1
		
		# April 27/09: New reagent types - keep separate from VICO for now
		else:
			# Dec. 7/09 - same restriction - don't reuse IDs!
			#cursor.execute("SELECT MAX(groupID) FROM Reagents_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
			cursor.execute("SELECT MAX(groupID) FROM Reagents_tbl WHERE reagentTypeID=" + `rTypeID`)
			result = cursor.fetchone()
			
			if result and result[0]:
				newGroupID = int(result[0]) + 1
				
			else:
				# happens when user has just created a new reagent type and wants to add the first reagent of its kind
				newGroupID = 1

		#print "Content-type:text/html"
		#print
		#print `newGroupID`
		
		return newGroupID

	
	# Retrieve reagent type **ID** from the database
	# Return: INTEGER
	def findReagentTypeID(self, rID):
	
		db = self.db
		cursor = self.cursor	# for easy access
		rTypeID = 0
		
		cursor.execute("SELECT reagentTypeID FROM Reagents_tbl WHERE reagentID=" + `rID` + " AND status='ACTIVE'")
		reagentResult = cursor.fetchone()		# better not be > 1!
		
		if reagentResult:
			rTypeID = int(reagentResult[0])
		
		return rTypeID
		
		
	# Feb. 11, 2010
	def findReagentPrefix(self, rID):
		db = self.db
		cursor = self.cursor	# for easy access
		
		rTypeID = self.findReagentTypeID(rID)
		
		cursor.execute("SELECT reagent_prefix FROM ReagentType_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		prefix = ""
		
		if result:
			prefix = result[0].strip()
			
		return prefix
		
		
	# Retrieve reagent group ID from the database
	def findReagentGroup(self, rID):
	
		db = self.db
		cursor = self.cursor	# for easy access
		rGroup = 0
		
		cursor.execute("SELECT groupID FROM Reagents_tbl WHERE reagentID=" + `rID` + " AND status='ACTIVE'")
		reagentResult = cursor.fetchone()		# better not be > 1!
		
		if reagentResult:
			rGroup = int(reagentResult[0])

		return rGroup
	
	
	# Transform internal database ID 'rID' into human-readable form (V123)
	# Return: String
	def convertDatabaseToReagentID(self, rID):
		
		db = self.db
		cursor = self.cursor
				
		reagentID = ""
		
		cursor.execute("SELECT t.reagent_prefix, r.groupID FROM Reagents_tbl r, ReagentType_tbl t WHERE r.reagentID=" + `rID` + " AND r.reagentTypeID=t.reagentTypeID AND r.status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			prefix = result[0]
			groupID = int(result[1])
			reagentID = prefix.upper() + `groupID`
		
		return reagentID

	
	# Equal and opposite: Find the internal database ID of alphanumeric 'lims_id' (e.g. V1 --> return '1')
	# Return: INT, internal database ID of lims_id
	def convertReagentToDatabaseID(self, lims_id):
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO	
		#print lims_id
		
		db = self.db
		cursor = self.cursor
		
		rID = -1
		
		## Get all prefixes from db
		#prefixes = []
		
		#cursor.execute("SELECT reagent_prefix FROM ReagentType_tbl WHERE status='ACTIVE'")
		#results = cursor.fetchall()
		
		#for result in results:
			#prefixes.append(result[0])
		
		# lims_id consists of a letter corresponding to one of 4 available reagent prefixes, followed by a numeric indexing portion
		# May 15/08: However, the LIMS ID could just be the empty string - in which case, return -1
		if len(lims_id) == 0:
			return -1
		
		try:
			assert len(lims_id) > 1		# must contain AT LEAST ONE alpha character followed by digit
			
			# Aug. 11/09: THE MOMENT OF TRUTH: NOW WITH FLEXIBLE PREFIXES NO LONGER LIMITED TO A SINGLE CHARACTER!!!!!
			#prefix = lims_id[0].upper()		# removed Aug. 12/09
			
			# Changed Aug. 12/09: Split LIMS ID into alpha and numeric portions
			m = re.match('[a-zA-Z]+', lims_id)
			
			if m:
				prefix = m.group()
			else:
				raise ReagentDoesNotExistException("This reagent ID does not exist in the database")
			
			#print "PREFIX " + `prefix`
			#print "group should be " + lims_id[len(prefix):]
			
			try:
				#assert prefix in prefixes
				
				try:
					groupID = int(lims_id[len(prefix):])
					#print groupID
					assert groupID > 0
					
					#print "SELECT r.reagentID FROM Reagents_tbl r, ReagentType_tbl t WHERE t.reagent_prefix=" + `prefix` + " AND r.groupID=" + `groupID` + " AND r.reagentTypeID=t.reagentTypeID AND r.status='ACTIVE'"
					
					cursor.execute("SELECT r.reagentID FROM Reagents_tbl r, ReagentType_tbl t WHERE t.reagent_prefix=" + `prefix` + " AND r.groupID=" + `groupID` + " AND r.reagentTypeID=t.reagentTypeID AND r.status='ACTIVE'")
					result = cursor.fetchone()
					
					if result:
						rID = int(result[0])
					else:
						raise ReagentDoesNotExistException("This reagent ID does not exist in the database")
				
				except ValueError:
					raise ReagentDoesNotExistException("This reagent ID does not exist in the database")
					
				except AssertionError:
					raise ReagentDoesNotExistException("This reagent ID does not exist in the database")
					
				# added April 30/08 - In EXTREME cases, if user provides an unreasonable ID with enormously long group index that is too large to cast to INT, the query would break with OperationalError - Unknown column '111111111111111111...L'
				except MySQLdb.OperationalError:
					raise ReagentDoesNotExistException("This reagent ID does not exist in the database")
				
			except AssertionError:
				raise ReagentDoesNotExistException("This reagent ID does not exist in the database")

		except AssertionError:
			raise ReagentDoesNotExistException("This reagent ID does not exist in the database")
			
		return rID


	# Return the value of a simple reagent property - i.e. not sequence, comments or packet (and not checkbox properties either)
	def findSimplePropertyValue(self, rID, propID):
		
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		cursor.execute("SELECT `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`=" + `rID` + " AND `propertyID`=" + `propID` + " AND `status`='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0]
			
		return None
	

	# Here, return the numerical index to a different table
	# Used to retrieve sequence, comments, packet
	def findIndexPropertyValue(self, rID, propID):
	
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		cursor.execute("SELECT `propertyValue` FROM `ReagentPropList_tbl` WHERE `propertyID`=" + `propID` + " AND `reagentID`=" + `rID` + " AND `status`='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			try:
				return int(result[0])
			except ValueError:
				return -1
			
		return -1
	
	
	# March 13/08 - For properties with multiple values (such as features), check if a particular value exists
	# Updated April 23/08 - include positions in query, account for features with multiple values
	def existsPropertyValue(self, rID, propID, propVal, pStart=0, pEnd=0, tmpDescr="", tmpDir='forward'):
		
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access

		#print "Content-type:text/html"
		#print
		#print "SELECT `propListID` FROM `ReagentPropList_tbl` WHERE `reagentID`=" + `rID` + " AND `propertyID`=" + `propID` + " AND propertyValue=" + `propVal` + " AND startPos=" + `pStart` + " AND endPos=" + `pEnd` + " AND `status`='ACTIVE'"

		# Update June 3, 2010: This function CAN and should be used to check for features too, BUT where positions are not known and defaulted to 0, don't tell the query to look for start and end positions 0!!!!
		if pStart != 0 and pEnd != 0:	# june 3, 2010
			cursor.execute("SELECT `propListID` FROM `ReagentPropList_tbl` WHERE `reagentID`=" + `rID` + " AND `propertyID`=" + `propID` + " AND propertyValue=" + `propVal` + " AND startPos=" + `pStart` + " AND endPos=" + `pEnd` + " AND descriptor=" + `tmpDescr` + " AND direction=" + `tmpDir` + " AND `status`='ACTIVE'")
		else:
			# assume positions are not known so don't check for them at all (June 3, 2010)
			# also, just checked and ccdB is stored in REVERSE orientation for many parents.  So don't include description or direction in this query at all - just the property name (an example is when checking for the presence of ccdB in Gateway parent destination vectors - it is a feature but all we know is its type and name - selectable marker ccdB, no positions or direction)
			cursor.execute("SELECT `propListID` FROM `ReagentPropList_tbl` WHERE `reagentID`=" + `rID` + " AND `propertyID`=" + `propID` + " AND propertyValue=" + `propVal` + " AND `status`='ACTIVE'")

		result = cursor.fetchall()	# retrieve all values

		if result:
			return 1
		else:
			return 0
		
		
	# Fetch a list of all property values of a specific reagent in the form of (property **NAME**, property value) tuples (e.g. ('type of insert', 'ORF'), etc.)
	# Modified July 2/09: propertyID column in ReagentPropList_tbl no longer corresponds to propertyID column in ReagentPropType_tbl but to propCatID column in ReagentPropertyCategories_tbl, i.e. property in conjunction with its category (because now, with a module to add new reagent types, property names may be shared between categories)
	def findAllReagentPropertiesByName(self, rID):
	
		cursor = self.cursor	# for easy access
		propDict = {}
		
		cursor.execute("SELECT t.propertyName, p.propertyValue FROM ReagentPropList_tbl p, ReagentPropertyCategories_tbl c, ReagentPropType_tbl t WHERE p.reagentID=" + `rID` + " AND c.propCatID = p.propertyID AND c.propID=t.propertyID AND p.status='ACTIVE' AND c.status='ACTIVE' AND t.status='ACTIVE'")
		reagentPropResultSet = cursor.fetchall()
			
		if reagentPropResultSet:
			for result in reagentPropResultSet:
				propName = result[0]
				propVal = result[1]
				propDict[propName] = propVal
				
		return propDict
		
		
	# Fetch a list of properties of a specific reagent in the form of (property **ID**, property value) tuples (e.g. ('48', 'ORF'), etc.)
	def findAllReagentPropertiesByID(self, rID):
	
		db = self.db			# for easy access
		cursor = self.cursor	# for easy access
		propDict = {}
		
		cursor.execute("SELECT propertyID, propertyValue FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND status='ACTIVE'")
		reagentPropResultSet = cursor.fetchall()
			
		if reagentPropResultSet:
			for result in reagentPropResultSet:
				propID = int(result[0])
				propVal = result[1]
				propDict[propID] = propVal
				
		return propDict


	# May 17, 2010
	def findAllReagentPropertyIDsByCategory(self, rID, categoryID):
	
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		propDict = {}
		
		#print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		propHandler = ReagentPropertyHandler(db, cursor)
		mapper = ReagentPropertyMapper(db, cursor)

		prop_Name_ID_Map = mapper.mapPropNameID()	# (prop name, prop id)
		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()
	
		cursor.execute("SELECT p.propertyID, p.propertyValue FROM ReagentPropList_tbl p, ReagentPropertyCategories_tbl  c WHERE p.reagentID=" + `rID` + " AND p.status='ACTIVE' AND p.propertyID=c.propCatID AND c.categoryID=" + `categoryID` + " AND c.status='ACTIVE'")

		#print "SELECT p.propertyID, p.propertyValue FROM ReagentPropList_tbl p, ReagentPropertyCategories_tbl  c WHERE p.reagentID=" + `rID` + " AND p.status='ACTIVE' AND p.propertyID=c.propCatID AND c.categoryID=" + `categoryID` + " AND c.status='ACTIVE'"
		
		reagentPropResultSet = cursor.fetchall()

		if reagentPropResultSet:
			for result in reagentPropResultSet:
				propID = int(result[0])
				propVal = result[1]
				propDict[propID] = propVal
		
		#print `propDict`
		return propDict
	
	
	# Retrieve the DNA sequence of a given reagent:
	def findDNASequenceKey(self, rID):
	
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		propHandler = ReagentPropertyHandler(db, cursor)
		mapper = ReagentPropertyMapper(db, cursor)

		prop_Name_ID_Map = mapper.mapPropNameID()	# (prop name, prop id)
		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()
		
		#seqPropID = propHandler.findPropID("sequence")
		seqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])
		seqID = self.findIndexPropertyValue(rID, seqPropID)
		
		return seqID
	
	
	def findRNASequenceKey(self, rID):
	
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		propHandler = ReagentPropertyHandler(db, cursor)
		mapper = ReagentPropertyMapper(db, cursor)

		prop_Name_ID_Map = mapper.mapPropNameID()	# (prop name, prop id)
		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()
		
		#seqPropID = propHandler.findPropID("sequence")
		seqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["rna sequence"], prop_Category_Name_ID_Map["RNA Sequence"])
		seqID = self.findIndexPropertyValue(rID, seqPropID)
		
		return seqID

	# Retrieve the PROTEIN sequence of a given reagent:
	# Oct. 27/09: Differentiate between protein translation of a DNA sequence and an actual protein sequence of a reagent
	def findProteinSequenceKey(self, rID, isProtein=False):

		#print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		propHandler = ReagentPropertyHandler(db, cursor)
		mapper = ReagentPropertyMapper(db, cursor)

		prop_Name_ID_Map = mapper.mapPropNameID()	# (prop name, prop id)
		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()
		
		# Update July 2/09
		#seqPropID = propHandler.findPropID("protein sequence")
		
		# Oct. 27/09
		if not isProtein:
			seqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein translation"], prop_Category_Name_ID_Map["DNA Sequence"])
		else:
			seqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein sequence"], prop_Category_Name_ID_Map["Protein Sequence"])
		
		#print seqID
		seqID = self.findIndexPropertyValue(rID, seqPropID)
		
		return seqID

	
	# Written March 30/07 by Marina
	# Assign properties to a new reagent at creation
	# Parameters: propDict = (propID, propValue)
	# July 2/09: "propID" in dictionary keys now refers to a property in conjunction with its category - PropertyCategories_tbl->propCatID column value
	def addReagentProperties(self, rID, propDict):
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print						# DITTO
		#print `propDict`

		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		# Various handlers used in implementation
		dnaHandler = DNAHandler(db, cursor)
		commHandler = CommentHandler(db, cursor)
		protHandler = ProteinHandler(db, cursor)
		iHandler = InsertHandler(db, cursor)		# feb. 26/08
		pHandler = ReagentPropertyHandler(db, cursor)
		rnaHandler = RNAHandler(db, cursor)	# Oct. 22/09

		# Rather than look up the ID of each individual property, prepare a map of (prop name, prop id) up front -- improves performance (March 14/07)
		mapper = ReagentPropertyMapper(db, cursor)
		
		prop_Name_ID_Map = mapper.mapPropNameID()	# (prop name, prop id)
		prop_ID_Name_Map = mapper.mapPropIDName()	# (prop id, prop name)
		
		reagent = self.createReagent(rID)
		
		rTypeMapper = ReagentTypeMapper(db, cursor)
		typeMap = rTypeMapper.mapTypeIDName()

		rTypeID = self.findReagentTypeID(rID)
		rType = typeMap[rTypeID]
		
		# Fetch a list of the reagent's CURRENT properties
		oldProps = reagent.getProperties()

		rtpHandler = ReagentTypePropertyHandler(db, cursor)
		
		# Fetch this reagent's checkbox property names
		checkboxPropNames = reagent.getCheckboxProps()

		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()

		#print "Content-type:text/html"
		#print
		#print `propDict`
		#print `checkboxPropNames`

		# map checkbox prop names to their IDs
		checkboxProps = {}			# will store (checkboxPropID, checkboxPropName)

		# 'if' added April 2/07 by Marina, since not all reagent types have checkbox properties (Oligo doesn't)
		if checkboxPropNames:
			for c in checkboxPropNames:
				
				# July 2/09: Need category to fetch ID, but hard to find out here.  Hard-code selectable marker and alternate ID for now
				if c == 'alternate id':
					tmp_cat = "External Identifiers"
					
				elif c == 'selectable marker':
					if rTypeID != 4:
						tmp_cat = "Classifiers"
					else:
						tmp_cat = "Growth Properties"
					
				cPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[c], prop_Category_Name_ID_Map[tmp_cat])
				
				#print c
				#print cPropID
				#cPropID = prop_Name_ID_Map[c]		# removed July 2/09
				checkboxProps[cPropID] = c
		
		# Combine regular properties with checkbox
		allProps = utils.merge(propDict.keys(), checkboxProps.keys())
		
		#print `propDict`
		#print `allProps`

		# Compare the set of ALL properties for this reagent to POST values and add, update or delete accordingly
		
		# July 14/09: propID now is essentially propCatID - a property together with its category
		for propID in allProps:
			#print pHandler.findReagentPropertyInCategory(propID)
			#print propID
			
			#if propID <= 0:
				#continue
			
			propName = prop_ID_Name_Map[pHandler.findReagentPropertyInCategory(propID)]	# replaced July 14/09
			
			# property could be empty
			if propDict.has_key(propID):
				propVal = propDict[propID]
				
				#if len(propVal) > 0:		# added April 30/09 and removed July 14/09 - len() doesn't work well with integer values
				if propVal:			# replaced July 14/09
					#print "What is this " + propName
					
					# special properties - Update Jan. 22, 2010: compute MW, Tm and GC for sequences, if they are attributes of this reagent type
					if propName == "sequence":
						#print propVal
	
						#if len(propVal) > 0:	# removed April 30/09
						
						# Store both DNA and protein sequences if available
						newSeqID = dnaHandler.getSequenceID(propVal)
						self.addReagentProperty(rID, propID, newSeqID)
						
						# Jan. 25, 2010
						mwPropID =  pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["DNA Sequence"])
						
						#print mwPropID
						
						#if rtpHandler.existsReagentTypeAttribute(rTypeID, mwPropID):
						mw = str(float(dnaHandler.calculateMW(propVal)))
						#print mw
						#self.addReagentProperty(rID, mwPropID, mw)
						dnaHandler.updateMolecularWeight(newSeqID, mw)

						# For Inserts, translate DNA sequence and store the translated protein sequence
						if rType == 'Insert':
							
							# July 2/09
							#ocPropID = prop_Name_ID_Map["open/closed"]
							ocPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["open/closed"], prop_Category_Name_ID_Map["Classifiers"])
							
							if propDict.has_key(ocPropID):
								openClosed = propDict[ocPropID]
	
								# June 1/07: IF OPEN/CLOSED IS BLANK, CHECK IF INSERT TYPE IS CDNA W/ UTRs!!!!!
								if len(openClosed) == 0:
									# July 2/09
									#insertTypePropID = prop_Name_ID_Map["type of insert"]
									insertTypePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["type of insert"], prop_Category_Name_ID_Map["Classifiers"])
		
									insertType = propDict[insertTypePropID]
									
									if insertType.lower() == 'cdna with utrs':
										openClosed = "special cdna with utrs"
	
							# Feb. 26/08: Change: Translating only cDNA sequence now
							newProtSeq = iHandler.translateInsertCDNA(rID, openClosed)	# added feb 26/08
							#newProtSeq = protHandler.translateAll(newSeqID, openClosed)	# removed feb 26/08
							#newProtSeq = protHandler.translateAll(newSeq, openClosed)	# feb 26/08 ?????
							newProtSeqID = protHandler.getSequenceID(newProtSeq)
							
							# July 2/09
							protSeqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein translation"], prop_Category_Name_ID_Map["DNA Sequence"])
						
							self.addReagentProperty(rID, protSeqPropID, newProtSeqID)
						else:
							# Aug. 10/09: For reagent types other than Insert, translate sequence from start to end and choose the longest ORF
							newProt = protHandler.translateAllFrames(utils.squeeze(propVal))

							if newProt:
								newProtSeq = newProt.getSequence()
								
								if len(newProtSeq) > 0:
									#print newProtSeq
									newProtSeqID = protHandler.getSequenceID(newProt)
									self.updateProteinSequence(rID, newProtSeqID)
									
									# Jan. 25/10: update MW
									protMW = protHandler.calculatePeptideMass(newProtSeq)
									protHandler.updateMolecularWeight(newProtSeqID, protMW)
							else:
								protSeqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein translation"], prop_Category_Name_ID_Map["DNA Sequence"])
								self.deleteReagentProperty(rID, protSeqPropID)
						
						# Jan. 22, 2010: compute Tm - safe to treat as a property of the reagent
						tmPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["melting temperature"], prop_Category_Name_ID_Map["DNA Sequence"])
						
						if rtpHandler.existsReagentTypeAttribute(rTypeID, tmPropID):
							tm = str(float(dnaHandler.calculateTm(propVal)))
							#print tm
							self.addReagentProperty(rID, tmPropID, tm)


						# June 25, 2010: GC content
						gcPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["gc content"], prop_Category_Name_ID_Map["DNA Sequence"])
						
						if rtpHandler.existsReagentTypeAttribute(rTypeID, gcPropID):
							dnaGC = dnaHandler.calculateGC(propVal)
							self.addReagentProperty(rID, gcPropID, dnaGC)
						

					# Aug. 13/09
					elif propName == "protein sequence":
						if len(propVal) > 0:
							protSeq = ProteinSequence(propVal)
							
							# Jan. 25/10
							protMW = protHandler.calculatePeptideMass(propVal)
							protSeq.setMW(protMW)
							
							# Store both DNA and protein sequences if available
							newSeqID = protHandler.getSequenceID(protSeq)
							self.addReagentProperty(rID, propID, newSeqID)

							# May 6, 2011: Clarification - updateMolecularWeight() updates Sequences_tbl.  Need an extra function call to update ReagentPropList_tbl (see below)
							#protHandler.updateMolecularWeight(newSeqID, protMW)

							# May 6, 2011: Update ReagentPropList_tbl
							mwPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["Protein Sequence"])
							self.addReagentProperty(rID, mwPropID, protMW)

					elif propName == "rna sequence":
						
						rnaSeq = RNASequence(propVal)
						
						# Jan. 25/10
						rnaMW = rnaHandler.calculateMW_RNA(propVal)
						rnaSeq.setMW(rnaMW)
						
						# Store both DNA and protein sequences if available
						newSeqID = rnaHandler.getSequenceID(rnaSeq)
						self.addReagentProperty(rID, propID, newSeqID)

					# Comments
					elif commHandler.isComment(propID):
						#print propID
						# Reference to GeneralComments_tbl
						self.addComment(rID, propID, propVal)
						
					else:
						#print propID
						#print propVal
						self.addReagentProperty(rID, propID, propVal)
		
	
	# March 28/08
	def deleteReagentFeatures(self, rID, isProtein=False, isRNA=False):
		
		#print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
	
		db = self.db				# for easy access
		cursor = self.cursor			# for easy access
		
		# May 26/09: Handlers and mappers
		pHandler = ReagentPropertyHandler(db, cursor)
		rtHandler = ReagentTypeHandler(db, cursor)
		mapper = ReagentPropertyMapper(db, cursor)
		rtPropHandler = ReagentTypePropertyHandler(db, cursor)		# Aug. 31/09
		
		# May 26/09
		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()
		prop_Category_Alias_ID_Map = mapper.mapPropCategoryAliasID()
		prop_Name_ID_Map = mapper.mapPropNameID()	# (prop name, prop id)
		
		# May 26/09
		rTypeID = self.findReagentTypeID(rID)
		#print rTypeID
		reagent = self.createReagent(rID)
		
		# May 26/09: don't mess with Insert and Vectors!!  Also, here rTypeID is an INTEGER, as opposed to update.py where form values are of type TEXT
		# July 10/09: no, let's try to generalize
		#if rTypeID != 1 and rTypeID != 2:
		
		if isProtein:
			features = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, prop_Category_Name_ID_Map["Protein Sequence Features"])
		elif isRNA:
			features = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, prop_Category_Name_ID_Map["RNA Sequence Features"])
		else:
			features = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, prop_Category_Name_ID_Map["DNA Sequence Features"])
			
		#else:	# May 15/09 - keep existing code for Inserts and Vectors
			#features = Reagent.getSequenceFeatures()
			#print `features`
			
		#print `features`
		
		for f in features:
			#print f
			
			# july 10/09
			#fID = prop_Name_ID_Map[f]
			if isProtein:
				fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[f], prop_Category_Name_ID_Map["Protein Sequence Features"])
			elif isRNA:
				fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[f], prop_Category_Name_ID_Map["RNA Sequence Features"])
			else:
				fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[f], prop_Category_Name_ID_Map["DNA Sequence Features"])
		
			if self.existsProperty(rID, fID):
				self.deleteReagentProperty(rID, fID)
			

	# Written March 12, 2008
	# Input: seqFeatures - list of Feature OBJECTS
	# Modified March 28/08: Delete and re-insert values
	# Corrected April 23/08: Don't delete on creation
	# Updated Aug. 7/08: Added positions to differentiate between multiple features with the same name and type
	def updateReagentSequenceFeatures(self, rID, seqFeatures):
		
		db = self.db				# for easy access
		cursor = self.cursor			# for easy access
		
		mapper = ReagentPropertyMapper(db, cursor)
		pHandler = ReagentPropertyHandler(db, cursor)
		
		prop_Name_ID_Map = mapper.mapPropNameID()	# (prop name, prop id)
		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()
		
		tmpFeatureDict = {}				# (featureID, [list of values])
		
		# DEBUG
		#print "Content-type:text/html"
		#print
		
		#for f in seqFeatures:
			#fType = f.getFeatureType()
			#fName = f.getFeatureName()
			#fStart = f.getFeatureStartPos()
			#fEnd = f.getFeatureEndPos()
			
			#print fType
			#print `fName`
			#print `fStart`
			#print `fEnd`
		
		self.deleteReagentFeatures(rID)			# March 28/08
		
		for f in seqFeatures:
			# Get the name/value of each feature (retrieve from object), MAKE A LIST and pass to changePropertyValue function
			fType = f.getFeatureType()
			#print fType
			fName = f.getFeatureName()
			#print fName
			
			# update July 2/09
			#fID = prop_Name_ID_Map[fType]
			fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
			#print `fID`
			fStart = f.getFeatureStartPos()
			#print `fStart`
			fEnd = f.getFeatureEndPos()
			#print `fEnd`
			
			# added Dec. 9/08, at launch
			if (fEnd-fStart) >= 10:
				if not self.existsPropertyValue(rID, fID, fName, fStart, fEnd):
					self.addReagentProperty(rID, fID, fName, fStart, fEnd)
		
			# March 17/08: Update feature descriptor if not empty
			fDescr = f.getFeatureDescrName()
			#print fDescr
			
			if len(fDescr) > 0:
				# Updated Aug. 7/08: Again, differentiate multiple features with the same name and descriptor by position
				self.setReagentFeatureDescriptor(rID, fID, fName, fStart, fEnd, fDescr)
				
			# March 17/08: Update feature direction
			fDir = f.getFeatureDirection()
			#print "Direction ??? " + `fDir`
			
			# There are only 2 direction values: default forward and reverse.  Only need to update if it's 'reverse'
			if fDir == 'reverse':
				# Updated Aug. 7/08: Here too, differentiate multiple features with the same name and descriptor by position
				self.updateFeatureDirection(rID, fID, fName, fStart, fEnd, fDir)
					
	
	# March 17/08: Set the value of column 'direction' in ReagentPropList_tbl to 'fDir' for the given feature value
	# Updated Aug. 7/08: Added 'featureStart' and 'featureEnd' arguments to differentiate between multiple features with the same name
	def updateFeatureDirection(self, rID, featureID, featureValue, featureStart, featureEnd, featureDirection):
		
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		if self.existsProperty(rID, featureID):
			cursor.execute("UPDATE ReagentPropList_tbl SET direction=" + `featureDirection` + " WHERE reagentID=" + `rID` + " AND propertyID=" + `featureID` + " AND propertyValue=" + `featureValue` + " AND startPos=" + `featureStart` + " AND endPos=" + `featureEnd` + " AND status='ACTIVE'")

	
	# March 17/08: Set the value of column 'descriptor' in ReagentPropList_tbl to 'descriptor' for the given feature value
	# Updated Aug. 7/08: Added 'featureStart' and 'featureEnd' arguments to differentiate between multiple features with the same name
	def setReagentFeatureDescriptor(self, rID, featureID, featureValue, featureStart, featureEnd, featureDescriptor):
		
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		if self.existsProperty(rID, featureID):
			cursor.execute("UPDATE ReagentPropList_tbl SET descriptor=" + `featureDescriptor` + " WHERE reagentID=" + `rID` + " AND propertyID=" + `featureID` + " AND propertyValue=" + `featureValue` + " AND startPos=" + `featureStart` + " AND endPos=" + `featureEnd` + " AND status='ACTIVE'")


	# May 17, 2010: Need a modification of updateReagentProperties() function, as reagent categories are edited separately from e.o.
	# Instead of comparing input against ALL properties, only compare to properties WITHIN THE GIVEN CATEGORY
	def updateReagentPropertiesInCategory(self, rID, categoryID, propDict):

		#print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
	
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		# Various handlers used in implementation
		dnaHandler = DNAHandler(db, cursor)
		commHandler = CommentHandler(db, cursor)
		protHandler = ProteinHandler(db, cursor)
		iHandler = InsertHandler(db, cursor)		# feb. 26/08
		rnaHandler = RNAHandler(db, cursor)		# Oct. 22/09

		# Instead of looking up the ID of each individual property, prepare a map of (prop name, prop id) up front -- improves performance (March 14/07)
		propHandler = ReagentPropertyHandler(db, cursor)
		mapper = ReagentPropertyMapper(db, cursor)
		
		prop_Name_ID_Map = mapper.mapPropNameID()	# (prop name, prop id)
		prop_ID_Name_Map = mapper.mapPropIDName()	# (prop id, prop name)
		
		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()
		
		reagent = self.createReagent(rID)
		
		rTypeMapper = ReagentTypeMapper(db, cursor)
		typeMap = rTypeMapper.mapTypeIDName()

		rTypeID = self.findReagentTypeID(rID)
		rType = typeMap[rTypeID]
		
		rtPropHandler = ReagentTypePropertyHandler(db, cursor)

		oldProps = self.findAllReagentPropertyIDsByCategory(rID, categoryID)
		#print `oldProps`
		#print `propDict`
		
		seqAttrs = DNASequence.getDNASequenceAttributes()
		
		allCategoryAttributes = rtPropHandler.findReagentTypeAttributeIDsByCategory(rTypeID, categoryID)
	
		for attrID in allCategoryAttributes:
			#print "Attr " + `attrID`
			propID = rtPropHandler.findReagentTypeAttributePropertyID(attrID)
			#print "prop id " + `propID`
			propName = propHandler.findPropName(propID)
			#print propName
			
			if propDict.has_key(propID):
				propVal = propDict[propID]
				#print propVal
				
				# Now need to differentiate between simple and composite properties involving cross-table reference (such as sequence, comments, etc)
				
				# Sequence
				if propName == "sequence":
					
					if len(propVal) > 0:
					
						# assign the new sequence input value to the reagent
						newSeqID = dnaHandler.getSequenceID(propVal)
						#print `newSeqID`
						self.updateDNASequence(rID, newSeqID)
						
						# Jan. 25/10: Molecular weight
						mwPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["DNA Sequence"])
						
						if rtPropHandler.existsReagentTypeAttribute(rTypeID, mwPropID):
							dnaMolWeight = dnaHandler.calculateMW(propVal)
							dnaHandler.updateMolecularWeight(newSeqID, dnaMolWeight)
						
						# Jan. 25/10: Melting temperature
						tmPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["melting temperature"], prop_Category_Name_ID_Map["DNA Sequence"])
						
						if rtPropHandler.existsReagentTypeAttribute(rTypeID, tmPropID):
							dnaTm = dnaHandler.calculateTm(propVal)
							self.changePropertyValue(rID, tmPropID, dnaTm)
						
						# June 23, 2010: Recompute GC content upon sequence modificaion
						gcPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["gc content"], prop_Category_Name_ID_Map["DNA Sequence"])
						
						if rtPropHandler.existsReagentTypeAttribute(rTypeID, gcPropID):
							gc_cont = dnaHandler.calculateGC(propVal)
							#print gc_cont
							self.changePropertyValue(rID, gcPropID, gc_cont)
						
						# Update protein sequence accordingly - FOR INSERTS ONLY
						if rType == 'Insert':
							# Must first find out the insert's open/closed property value; if this value is empty, sequence translation is not possible
							
							# Modified July 2/09
							ocPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["open/closed"], prop_Category_Name_ID_Map["Classifiers"])
	
							if propDict.has_key(ocPropID):
								openClosed = propDict[ocPropID]
								#print `openClosed`
								
								# June 1/07: IF OPEN/CLOSED IS BLANK, CHECK IF INSERT TYPE IS CDNA W/ UTRs!!!!!
								if len(openClosed) == 0:
									insertTypePropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["type of insert"], prop_Category_Name_ID_Map["Classifiers"])
		
									insertType = propDict[insertTypePropID]
	
									if insertType.lower() == 'cdna with utrs':
										openClosed = "special cdna with utrs"
							# May 19, 2011
							else:
								openClosed = self.findSimplePropertyValue(rID, ocPropID)
								
								if not openClosed or len(openClosed) == 0:
									insertTypePropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["type of insert"], prop_Category_Name_ID_Map["Classifiers"])
		
									if propDict.has_key(insertTypePropID):
										insertType = propDict[insertTypePropID]
									else:
										insertType = self.findSimplePropertyValue(rID, insertTypePropID)
	
									if insertType.lower() == 'cdna with utrs':
										openClosed = "special cdna with utrs"
									
							# Feb. 26/08: Change: Translating only cDNA sequence now
							newProtSeq = iHandler.translateInsertCDNA(rID, openClosed)	# added feb 26/08
							
							newProtSeqID = protHandler.getSequenceID(newProtSeq)
							#print newProtSeqID
							self.updateProteinSequence(rID, newProtSeqID)
							
							# June 1/07: Do NOT delete old sequence, as it may be shared by other reagents!!!!!!!!!!!!!!!!!!
						else:
							# Aug. 10/09: For reagent types other than Insert, translate sequence from start to end and choose the longest ORF
							#print utils.squeeze(propVal)
							
							# Jan. 25/10: but only translate if feasible, i.e. if this reagent type should have a protein translation
							protTransPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein translation"], prop_Category_Name_ID_Map["DNA Sequence"])
							
							if rtPropHandler.existsReagentTypeAttribute(rTypeID, protTransPropID):
								newProt = protHandler.translateAllFrames(utils.squeeze(propVal))
								
								if newProt:
									newProtSeq = newProt.getSequence()
									#print newProtSeq
									
									if len(newProtSeq) > 0:
										#print newProtSeq
										newProtSeqID = protHandler.getSequenceID(newProt)
										self.updateProteinSequence(rID, newProtSeqID)
								else:
									protSeqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein translation"], prop_Category_Name_ID_Map["DNA Sequence"])
									self.deleteReagentProperty(rID, protSeqPropID)
					else:
						# Delete both DNA and protein sequences
						self.deleteReagentProperty(rID, propID)
						
						# Update July 2/09
						protSeqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein translation"], prop_Category_Name_ID_Map["DNA Sequence"])
						self.deleteReagentProperty(rID, protSeqPropID)

				# Aug. 13/09
				elif propName == "protein sequence":
					protSeq = ProteinSequence(propVal)
					
					# Jan. 25/10
					protMW = protHandler.calculatePeptideMass(propVal)
					#print protMW

					protSeq.setMW(protMW)
					
					newSeqID = protHandler.getSequenceID(protSeq)
					
					#if oldProps.has_key(propName):
					if oldProps.has_key(propID):
						if newSeqID and newSeqID > 0:
							#if oldProps[propName] != newSeqID:
							if oldProps[propID] != newSeqID:
								self.changePropertyValue(rID, propID, newSeqID)
						else:
							self.deleteReagentProperty(rID, propID)
					else:
						self.addReagentProperty(rID, propID, newSeqID)
					
					# Jan. 25/10: Update MW separately for this sequence (in case it hasn't been previously stored) - somewhat redundant but more complete
					# May 6, 2011: Clarification - updateMolecularWeight() updates Sequences_tbl.  Need an extra function call to update ReagentPropList_tbl (see below)
					protHandler.updateMolecularWeight(newSeqID, protMW)

					# May 6, 2011: Update ReagentPropList_tbl
					mwPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["Protein Sequence"])
					self.changePropertyValue(rID, mwPropID, protMW)
					
				elif propName == "rna sequence":
					rnaSeq = RNASequence(propVal)
					newSeqID = rnaHandler.getSequenceID(rnaSeq)
					
					# Jan. 25/10
					rnaMW = rnaHandler.calculateMW_RNA(propVal)
					rnaSeq.setMW(rnaMW)
					
					#if oldProps.has_key(propName):
					if oldProps.has_key(propID):
						if newSeqID and newSeqID > 0:
							#if oldProps[propName] != newSeqID:
							if oldProps[propID] != newSeqID:
								self.changePropertyValue(rID, propID, newSeqID)
						else:
							self.deleteReagentProperty(rID, propID)
					else:
						self.addReagentProperty(rID, propID, newSeqID)
					
					# Jan. 25/10: Update MW separately for this sequence (in case it hasn't been previously stored) - somewhat redundant but more complete
					rnaHandler.updateMolecularWeight(newSeqID, rnaMW)
					
				# Correction May 17, 2010: Develop a generic solution
				## March 19/09: Store multiple accessions
				#elif propName == "accession number" or propName == 'alternate id' or propName == 'selectable marker':
					## delete and re-insert
					#self.deleteReagentProperty(rID, propID)
					
					##print propName
					
					##print propID
					##print `propVal`
					
					#if utils.isList(propVal):
						#accs = propVal
					#else:
						#accs = propVal.split(",")
					
					##print `accs`
					
					#for acc in accs:
						#if len(acc) > 0:
							#self.addReagentProperty(rID, propID, acc.strip())
	
				# Comments
				elif commHandler.isComment(propID):
					#print propID
					# Reference to GeneralComments_tbl
					self.updateComments(rID, propID, propDict[propID])
	
				else:
					# May 10, 2010: Multiples
					#print propName
					
					#rtAttrID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propID)
					#print rtAttrID
					
					if rtPropHandler.isMultiple(attrID):
						#print "multiple " + propName
						self.deleteReagentProperty(rID, propID)
						
						if propVal != "":
							self.addReagentProperty(rID, propID, propVal)
					else:
						#print "Single! " + propName
						
						# CHECK IF PROPERTY EXISTS!!!!!!!!!!!!
						#if oldProps.has_key(propName):
						if oldProps.has_key(propID):
							#print propName + " exists"
							
							# May 2, 2011: Account for autocomputed integer properties, such as MW, Tm, length
							if propVal:
								try:
									propVal = int(propVal)

									if oldProps[propID] != propVal:
										# Non-empty, change
										#print "should change " + propVal
										self.changePropertyValue(rID, propID, propVal)
									
								except (TypeError, ValueError):
									# property exists, either update or delete depending on its value
									#if propVal and len(propVal) > 0:
									if len(propVal) > 0:
										#print "but not here??" + propName
										if oldProps[propID] != propVal:
											# Non-empty, change
											#print "should change " + propVal
											self.changePropertyValue(rID, propID, propVal)
										
										# otherwise don't do anything!!!!!!!!!!!!!!
								
									else:
										#print "yes " + propName
										# Empty property, delete
										self.deleteReagentProperty(rID, propID)

							# May 11, 2011: not sure about this, must have been a reason I didn't include this in the first place...
							else:
								# delete
								self.deleteReagentProperty(rID, propID)
						else:
							# does not exist, insert if not empty
							#print propName + " does not exist, insert if not empty"
							if propVal != "":
								self.addReagentProperty(rID, propID, propVal)
			
			else:
				# May 17/10: within this category can delete if empty
				# This property did not arrive in the list of POST variables; hence, it should be deleted
				#print "Deleting " + propName
				
				# May 14, 2010: EXCEPT automatically computed properties, such as protein translation, MW, Tm - they're not passed in dictionary but should not be deleted!!
				if propName not in seqAttrs:
					self.deleteReagentProperty(rID, propID)


	# Update a set of reagent properties to new values that are given as a list of {propertyID, propertyValue} tuples
	# Central usage: save modified reagent properties on return from the "Modify" view
	def updateReagentProperties(self, rID, propDict):
		
		#print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `propDict`
		
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		# Various handlers used in implementation
		dnaHandler = DNAHandler(db, cursor)
		commHandler = CommentHandler(db, cursor)
		protHandler = ProteinHandler(db, cursor)
		iHandler = InsertHandler(db, cursor)		# feb. 26/08
		rnaHandler = RNAHandler(db, cursor)		# Oct. 22/09

		# Instead of looking up the ID of each individual property, prepare a map of (prop name, prop id) up front -- improves performance (March 14/07)
		propHandler = ReagentPropertyHandler(db, cursor)
		mapper = ReagentPropertyMapper(db, cursor)
		
		prop_Name_ID_Map = mapper.mapPropNameID()	# (prop name, prop id)
		prop_ID_Name_Map = mapper.mapPropIDName()	# (prop id, prop name)
		
		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()
		
		reagent = self.createReagent(rID)
		
		rTypeMapper = ReagentTypeMapper(db, cursor)
		typeMap = rTypeMapper.mapTypeIDName()

		rTypeID = self.findReagentTypeID(rID)
		rType = typeMap[rTypeID]
		
		rtPropHandler = ReagentTypePropertyHandler(db, cursor)

		# Fetch a list of the reagent's CURRENT properties
		# Update Dec. 16/09: with shared property names across categories cannot rely on reagent.getProperties() that returns a list of names.  Need a list of propCatIDs, where each property is linked to a category
		#oldProps = reagent.getProperties()
		oldProps = self.findAllReagentPropertiesByID(rID)
		#print `oldProps`
		
		allAttributes = rtPropHandler.findAllReagentTypeAttributes(rTypeID)
		
		# Compare the set of ALL properties for this reagent to POST values and add, update or delete accordingly
		for attrID in allAttributes:
			propID = rtPropHandler.findReagentTypeAttributePropertyID(attrID)
			#print propID
			propName = propHandler.findPropName(propID)
			#print propName
			
			# determine whether the property is to be changed or deleted
			if propDict.has_key(propID):
				# change
				propVal = propDict[propID]

				#print propName
				#print propVal
				
				# Now need to differentiate between simple and composite properties involving cross-table reference (such as sequence, comments, etc)
				
				# Sequence
				if propName == "sequence":
					
					if len(propVal) > 0:
					
						# assign the new sequence input value to the reagent
						newSeqID = dnaHandler.getSequenceID(propVal)
						#print `newSeqID`
						self.updateDNASequence(rID, newSeqID)
						
						# Jan. 25/10: Molecular weight
						mwPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["DNA Sequence"])
						
						if rtPropHandler.existsReagentTypeAttribute(rTypeID, mwPropID):
							dnaMolWeight = dnaHandler.calculateMW(propVal)
							dnaHandler.updateMolecularWeight(newSeqID, dnaMolWeight)
						
						# Jan. 25/10: Melting temperature
						tmPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["melting temperature"], prop_Category_Name_ID_Map["DNA Sequence"])
						
						if rtPropHandler.existsReagentTypeAttribute(rTypeID, tmPropID):
							dnaTm = dnaHandler.calculateTm(propVal)
							self.changePropertyValue(rID, tmPropID, dnaTm)
						
						# June 25, 2010: GC content
						gcPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["gc content"], prop_Category_Name_ID_Map["DNA Sequence"])
						
						if rtPropHandler.existsReagentTypeAttribute(rTypeID, gcPropID):
							dnaGC = dnaHandler.calculateGC(propVal)
							self.changePropertyValue(rID, tmPropID, dnaGC)
						
						# Update protein sequence accordingly - FOR INSERTS ONLY
						if rType == 'Insert':
							# Must first find out the insert's open/closed property value; if this value is empty, sequence translation is not possible
							
							# Modified July 2/09
							#ocPropID = prop_Name_ID_Map["open/closed"]
							ocPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["open/closed"], prop_Category_Name_ID_Map["Classifiers"])
	
							if propDict.has_key(ocPropID):
								openClosed = propDict[ocPropID]
								#print `openClosed`
								
								# June 1/07: IF OPEN/CLOSED IS BLANK, CHECK IF INSERT TYPE IS CDNA W/ UTRs!!!!!
								if len(openClosed) == 0:
									
									# July 2/09
									#insertTypePropID = prop_Name_ID_Map["type of insert"]
									insertTypePropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["type of insert"], prop_Category_Name_ID_Map["Classifiers"])
		
									insertType = propDict[insertTypePropID]
	
									if insertType.lower() == 'cdna with utrs':
										openClosed = "special cdna with utrs"
	
							# Feb. 26/08: Change: Translating only cDNA sequence now
							#newProtSeq = protHandler.translateAll(newSeqID, openClosed)	# removed feb 26/08
							newProtSeq = iHandler.translateInsertCDNA(rID, openClosed)	# added feb 26/08
							#print newProtSeq
							newProtSeqID = protHandler.getSequenceID(newProtSeq)
							self.updateProteinSequence(rID, newProtSeqID)
							
							# June 1/07: Do NOT delete old sequence, as it may be shared by other reagents!!!!!!!!!!!!!!!!!!
						else:
							# Aug. 10/09: For reagent types other than Insert, translate sequence from start to end and choose the longest ORF
							#print utils.squeeze(propVal)
							
							# Jan. 25/10: but only translate if feasible, i.e. if this reagent type should have a protein translation
							protTransPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein translation"], prop_Category_Name_ID_Map["DNA Sequence"])
							
							if rtPropHandler.existsReagentTypeAttribute(rTypeID, protTransPropID):
								newProt = protHandler.translateAllFrames(utils.squeeze(propVal))
								
								if newProt:
									newProtSeq = newProt.getSequence()
									#print newProtSeq
									
									if len(newProtSeq) > 0:
										#print newProtSeq
										newProtSeqID = protHandler.getSequenceID(newProt)
										self.updateProteinSequence(rID, newProtSeqID)
								else:
									protSeqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein translation"], prop_Category_Name_ID_Map["DNA Sequence"])
									self.deleteReagentProperty(rID, protSeqPropID)
					else:
						# Delete both DNA and protein sequences
						self.deleteReagentProperty(rID, propID)
						
						# Update July 2/09
						protSeqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein translation"], prop_Category_Name_ID_Map["DNA Sequence"])
						self.deleteReagentProperty(rID, protSeqPropID)
				
				# Aug. 13/09
				elif propName == "protein sequence":
					protSeq = ProteinSequence(propVal)
					
					# Jan. 25/10
					protMW = protHandler.calculatePeptideMass(propVal)
					protSeq.setMW(protMW)
					
					newSeqID = protHandler.getSequenceID(protSeq)
					
					#if oldProps.has_key(propName):
					if oldProps.has_key(propID):
						if newSeqID and newSeqID > 0:
							#if oldProps[propName] != newSeqID:
							if oldProps[propID] != newSeqID:
								self.changePropertyValue(rID, propID, newSeqID)
						else:
							self.deleteReagentProperty(rID, propID)
					else:
						self.addReagentProperty(rID, propID, newSeqID)
					
					# Jan. 25/10: Update MW separately for this sequence (in case it hasn't been previously stored) - somewhat redundant but more complete
					protHandler.updateMolecularWeight(newSeqID, protMW)
					
				elif propName == "rna sequence":
					rnaSeq = RNASequence(propVal)
					newSeqID = rnaHandler.getSequenceID(rnaSeq)
					
					# Jan. 25/10 - REMOVED APRIL 14, 2011, Karen said don't compute RNA MW automatically
					#rnaMW = rnaHandler.calculateMW_RNA(propVal)
					#print rnaMW
					#rnaSeq.setMW(rnaMW)
					
					#if oldProps.has_key(propName):
					if oldProps.has_key(propID):
						if newSeqID and newSeqID > 0:
							#if oldProps[propName] != newSeqID:
							if oldProps[propID] != newSeqID:
								self.changePropertyValue(rID, propID, newSeqID)
						else:
							self.deleteReagentProperty(rID, propID)
					else:
						self.addReagentProperty(rID, propID, newSeqID)
					
					# April 14, 2011: don't do this
					## Jan. 25/10: Update MW separately for this sequence (in case it hasn't been previously stored) - somewhat redundant but more complete
					#rnaHandler.updateMolecularWeight(newSeqID, rnaMW)

					## April 12, 2011: change MW **property** - Removed April 14, 2011
					#mwPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["RNA Sequence"])
					#self.changePropertyValue(rID, mwPropID, rnaMW)
					
				# Correction May 17, 2010: Develop a generic solution
				## March 19/09: Store multiple accessions
				#elif propName == "accession number" or propName == 'alternate id' or propName == 'selectable marker':
					## delete and re-insert
					#self.deleteReagentProperty(rID, propID)
					
					##print propName
					
					##print propID
					##print `propVal`
					
					#if utils.isList(propVal):
						#accs = propVal
					#else:
						#accs = propVal.split(",")
					
					##print `accs`
					
					#for acc in accs:
						#if len(acc) > 0:
							#self.addReagentProperty(rID, propID, acc.strip())
	
				# Comments
				elif commHandler.isComment(propID):
					#print propID
					# Reference to GeneralComments_tbl
					self.updateComments(rID, propID, propDict[propID])
	
				else:
					# May 10, 2010: Multiples
					#print propName
					
					rtAttrID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propID)
					#print rtAttrID
					if rtPropHandler.isMultiple(rtAttrID):
						#print "well, yeah " + propName
						self.deleteReagentProperty(rID, propID)
						
						if propVal != "":
							self.addReagentProperty(rID, propID, propVal)
					else:
						#print "Content-type:text/html"
						#print

						# CHECK IF PROPERTY EXISTS!!!!!!!!!!!!
						#if oldProps.has_key(propName):
						if oldProps.has_key(propID):
							#print propName + " exists"
						
							# property exists, either update or delete depending on its value
							if propVal and len(propVal) > 0:
								if oldProps[propID] != propVal:
									# Non-empty, change
									#print "should change " + propVal

									self.changePropertyValue(rID, propID, propVal)
								
								# otherwise don't do anything!!!!!!!!!!!!!!
							else:
								# Empty property, delete
								self.deleteReagentProperty(rID, propID)
						else:
							# does not exist, insert if not empty
							#print propName + " does not exist, insert if not empty"
							if propVal != "":
								self.addReagentProperty(rID, propID, propVal)
			
			# May 15, 2010: NO!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			# THIS DELETES ANY PROPERTY IN ANY CATEGORY THAT WAS NOT FILLED IN.  WRONG!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			
			# May 13, 2011: Yes, but now the only time this function is used is to update Cell Line properties from a parent change - which means the old form where properties are not passed in categories.  Try putting this back and see if it causes problems (so far so good)

			# May 20, 2011: Yes, it does cause problems - because this function is not only used for Cell Lines, but to save the last step of multi-step creation for Vectors and Inserts!

			else:
				# This property did not arrive in the list of POST variables; hence, it should be deleted
				#print "WHY HERE?? " + propName
				self.deleteReagentProperty(rID, propID)
	
	
	# April 7/08: Recompute sequence feature positions if sequence is modified
	# updated Oct. 15/08 - use findAllReagentPropertiesByName() instead of SQL query
	def updateFeaturePositions(self, rID, oldSeq, newSeq, isProtein=False, isRNA=False):
		
		#print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		db = self.db
		cursor = self.cursor
		
		reagent = self.createReagent(rID)
		
		# Removed July 27/09
		'''
		# oct. 23/08
		#allFeatures = reagent.getAllFeatures()
		
		#print `allFeatures`
		
		#seqFeatures = reagent.getSequenceFeatures()
		#singleFeatues = reagent.getSingleFeatures()
		
		#features = seqFeatures + singleFeatues
		'''
		
		# changed July 27/09
		allFeatures = self.findReagentSequenceFeatures(rID, isProtein, isRNA)
		#print `allFeatures`
		
		#print `allFeatures`
		
		sHandler = DNAHandler(db, cursor)
		propHandler = ReagentPropertyHandler(db, cursor)
		mapper = ReagentPropertyMapper(db, cursor)

		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()
		
		# april 14/08
		enzDict = utils.join(sHandler.sitesDict, sHandler.gatewayDict)
		
		# april 21/08 - include LoxP
		enzDict = utils.join(enzDict, sHandler.recombDict)
		
		# add 'None'
		enzDict['None'] = ""
		
		#print `enzDict`
		
		prop_Name_ID_Map = mapper.mapPropNameID()	# (prop name, prop id)
		tmpFeatureDict = {}				# (featureID, [list of values])
		
		# Length of old and new sequence
		oldSeqLen = len(oldSeq)
		newSeqLen = len(newSeq)
		
		#allProps = self.findAllReagentPropertiesByName(rID)	# Oct. 15/08, removed July 27/09
		
		# updated Oct. 23/08
		for f in allFeatures:
			featureType = f.getFeatureType()
			#print featureType
			
			# July 2/09
			#featureID = prop_Name_ID_Map[featureType]
			if isProtein:
				featureID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[featureType.lower()], prop_Category_Name_ID_Map["Protein Sequence Features"])
			
			elif isRNA:
				featureID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[featureType.lower()], prop_Category_Name_ID_Map["RNA Sequence Features"])
				
			else:
				featureID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[featureType.lower()], prop_Category_Name_ID_Map["DNA Sequence Features"])
			
			featureValue = f.getFeatureName()
			featureStartPos = f.getFeatureStartPos()
			featureEndPos = f.getFeatureEndPos()
			featureDirection = f.getFeatureDirection()
			
			#featureDescriptorType = f.getFeatureDescriptorType()
			featureDescriptor = f.getFeatureDescrName()
			
			#featureDescriptor = f.getFeatureDescriptor()
			
			#print "Feature " + `featureType` + ", " + featureValue + ", " + `featureStartPos` + "-" + `featureEndPos` + ", direction " + featureDirection
			
			if featureType.lower() == "5' cloning site" or featureType.lower() == "3' cloning site":
				#if enzDict.has_key(featureValue.lower()):	# WHY LOWERCAASE ENZYME **NAME**???!!!!!!!!!
				if enzDict.has_key(featureValue):		# correction Oct. 1, 2010
					fSeq = enzDict[featureValue].lower()
				elif sHandler.isHybrid(utils.make_array(featureValue)):
					fSeq = sHandler.hybridSeq(utils.make_array(featureValue)).lower()
				else:
					#fSeq = ""	# no, removed Oct. 1, 2010 - can get infinite remapping
					
					# Instead: Oct 1, 2010
					print "Content-type:text/html"
					print
					print "Unknown cloning site: " + `featureValue`
					
					#print `enzDict`
			else:
				# July 27/09: If old sequence was empty naturally don't adjust anything
				#if len(oldSeq) > 0:
				fSeq = oldSeq[featureStartPos-1:featureEndPos].lower()
				#else:
					#return
			
			#print "old feature seq " + fSeq + "!!"
			
			# April 18/08 - when sequence is deleted all features should be deleted too
			if newSeqLen == 0:
				self.deleteReagentFeature(rID, featureID)
			else:
				# April 8/08: Look for feature on the new sequence AND return ALL occurrences as opposed to just the first one
				# Minimum feature length cutoff 10 nts
				
				if featureType.lower() != "5' cloning site" and featureType.lower() != "3' cloning site":
					# Delete old feature value first - KEEP DESCRIPTORS!!!!
					fDescr = self.findReagentFeatureDescriptor(rID, featureID, featureValue)
					#print fDescr
					
					# April 21/08 - Check feature length and only remap features that are over 10 nts long
					fLength = featureEndPos - featureStartPos + 1
					
					if fLength >= 10:
						self.deleteReagentFeature(rID, featureID, featureValue, featureStartPos, featureEndPos, featureDirection, featureDescriptor)
					
						# Now find all occurrences of this feature on the new sequence and record them
						# (april 21/08: don't need this check anymore)
						#if featureID != prop_Name_ID_Map["5' linker"] and featureID != prop_Name_ID_Map["3' linker"]:
					
						numOccur = newSeq.count(fSeq)
						nextStart = 0
						i = 0
						
						#print "Feature " + `featureID` + " found " + `numOccur` + " times"
						
						while i < numOccur:
							#print "Counter " + `i`
							#print "Feature " + `featureID` + ": " + fSeq
							
							if newSeq.find(fSeq, nextStart) >= 0:
								newFStart = newSeq.index(fSeq, nextStart) + 1
								#print " New start " + `newFStart`
								newFEnd = newFStart+len(fSeq)-1
								#print " New end " + `newFEnd`
								nextStart = newFEnd + 1
								#print `nextStart`
								
								# Dec. 17/09: Check for existence.  Reason: e.g. LoxP on V50059: occurs twice in the sequence, at pos. 14-47 and 1802-1835.  If this check not done, each instance will be saved twice - i.e. will have 4 table rows, 2 that say 'Loxp 14-47' and 2 that say 'LoxP 1802-1835'
								if not self.existsFeature(rID, featureID, featureValue, newFStart, newFEnd, featureDirection, featureDescriptor):
								
									# July 27/09: Need to include orientation in arguments list; otherwise, everything gets reset to forward
									self.addReagentProperty(rID, featureID, featureValue, newFStart, newFEnd, featureDirection)
									
									# put the descriptor back if applicable
									if fDescr:
										self.setReagentFeatureDescriptor(rID, featureID, featureValue, newFStart, newFEnd, featureDescriptor)
							else:
								self.deleteReagentFeature(rID, featureID, featureValue, featureStartPos, featureEndPos, featureDirection, featureDescriptor)
				
							# increment loop counter
							i += 1
					else:
						# April 21/08: Keep feature, delete positions
						self.deletePropertyPosition(rID, featureID, "startPos", featureValue)
						self.deletePropertyPosition(rID, featureID, "endPos", featureValue)
	
	# Added June 8/08
	# Find the given cloning sites are present on a sequence (by string search) - save positions if found
	def updateSitePositions(self, rID, newSeq, five_prime_site, three_prime_site):
		
		#print "Content-type:text/html"
		#print
		
		db = self.db
		cursor = self.cursor

		dnaHandler = DNAHandler(db, cursor)
		rHandler = ReagentHandler(db, cursor)
		propMapper = ReagentPropertyMapper(db, cursor)

		pHandler = ReagentPropertyHandler(db, cursor)

		prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()

		enzDict = utils.join(dnaHandler.sitesDict, dnaHandler.gatewayDict)
		enzDict = utils.join(enzDict, dnaHandler.recombDict)		# add LoxP
		enzDict['None'] = ""						# add 'None'
		
		prop_Name_ID_Map = propMapper.mapPropNameID()			# (prop name, prop id)
		
		fpSitePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
		
		tpSitePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
		if five_prime_site:
			fpcs = utils.make_array(five_prime_site)
		else:
			self.deleteReagentProperty(rID, fpSitePropID)
			return
			
		if three_prime_site:
			tpcs = utils.make_array(three_prime_site)
		else:
			self.deleteReagentProperty(rID, tpSitePropID)
			return
		
		if dnaHandler.isHybrid(fpcs):
			fp_seq = dnaHandler.hybridSeq(fpcs).lower()

		else:
			if len(five_prime_site) > 0:
				fp_seq = enzDict[five_prime_site].lower()
			else:
				fp_seq = ""

		#print fp_seq

		if dnaHandler.isHybrid(tpcs):
			tp_seq = dnaHandler.hybridSeq(tpcs).lower()
			
		else:
			if len(three_prime_site) > 0:
				tp_seq = enzDict[three_prime_site].lower()
			else:
				tp_seq = ""

		# Start positions from 1 to make them human-readable
		if len(fp_seq) > 0 and newSeq.lower().find(fp_seq) >= 0:
			five_start_pos = newSeq.lower().index(fp_seq) + 1
			five_end_pos = five_start_pos + len(fp_seq) - 1
		else:
			# Dec. 17/08: If sites are not found, use BioPython to look for degenerate sites with variable sequences
			tmpSeq = Bio.Seq.Seq(newSeq)
			
			# Jan. 6/09
			if dnaHandler.enzDict.has_key(five_prime_site):
				fp_cs = dnaHandler.enzDict[five_prime_site]
				
				if len(fp_cs.search(tmpSeq)) > 0:
					five_start_pos = fp_cs.search(tmpSeq)[0] - len(fp_cs.ovhgseq) + 1
					five_end_pos = five_start_pos + len(fp_seq) - 1
					
					if five_start_pos == 0:
						five_start_pos = 0
						five_end_pos = 0
				else:
					five_start_pos = 0
					five_end_pos = 0
			# Jan. 6/09
			else:
				five_start_pos = 0
				five_end_pos = 0
			
		# Insert new values for sites
		rHandler.deleteReagentProperty(rID, fpSitePropID)
		rHandler.addReagentProperty(rID, fpSitePropID, five_prime_site, five_start_pos, five_end_pos)
		
		# 3' site: Regular search, no need to reverse complement
		# July 15/08: For LoxP, since there are 2, start searching for 3' site from end of sequence
		if three_prime_site == 'LoxP':
			if newSeq.lower().rfind(tp_seq) >= 0:
				three_start_pos = newSeq.lower().rindex(tp_seq) + 1
				three_end_pos = three_start_pos + len(tp_seq) - 1
			else:
				three_start_pos = 0
				three_end_pos = 0
		else:	
			if len(tp_seq) > 0 and newSeq.lower().rfind(tp_seq) >= 0:
				three_start_pos = newSeq.lower().rindex(tp_seq) + 1
				three_end_pos = three_start_pos + len(tp_seq) - 1
			else:
				# Dec. 17/08: If sites are not found, use BioPython to look for degenerate sites with variable sequences
				tmpSeq = Bio.Seq.Seq(newSeq)
				
				# Jan. 6/09
				if dnaHandler.enzDict.has_key(three_prime_site):
					tp_cs = dnaHandler.enzDict[three_prime_site]
					
					if len(tp_cs.search(tmpSeq)) > 0:
						three_start_pos = tp_cs.search(tmpSeq)[len(tp_cs.search(tmpSeq))-1] - len(tp_cs.ovhgseq) + 1
						three_end_pos = three_start_pos + len(tp_seq) - 1
						
						if three_start_pos == 0:
							three_start_pos = 0
							three_end_pos = 0
					else:
						three_start_pos = 0
						three_end_pos = 0
				# Jan. 6/09
				else:
					three_start_pos = 0
					three_end_pos = 0

		self.deleteReagentProperty(rID, tpSitePropID)
		self.addReagentProperty(rID, tpSitePropID, three_prime_site, three_start_pos, three_end_pos)	


	# Set the start/end value of a reagent property
	#
	# propID: the ID of the property to set position for (e.g0. propID '1' => "5' linker"; '14' => "5' cloning site", etc.)
	# posType: one of "startPos" or "endPos"
	# position: actual INT value of property start or end
	#
	# Updated March 13/08: Added propVal argument, to account for multiple property values (e.g. when updating features).  May be left blank by default if the property identified by propID has only one value
	def setPropertyPosition(self, rID, propID, posType, position, propVal=""):
		#print "Content-type:text/html"
		#print
		
		db = self.db
		cursor = self.cursor
		
		if len(propVal) == 0:
			if self.existsProperty(rID, propID):
				cursor.execute("UPDATE ReagentPropList_tbl SET " + posType + "=" + `position` + " WHERE reagentID=" + `rID` + " AND propertyID=" + `propID`)
			else:
				cursor.execute("INSERT INTO ReagentPropList_tbl(reagentID, propertyID, " + posType + ") VALUES(" + `rID` + ", " + `propID` + ", " + `position` + ")")
		else:
			if self.existsPropertyValue(rID, propID, propVal):
				cursor.execute("UPDATE ReagentPropList_tbl SET " + posType + "=" + `position` + " WHERE reagentID=" + `rID` + " AND propertyID=" + `propID` + " AND propertyValue=" + `propVal` + " AND status='ACTIVE'")
			else:
				cursor.execute("INSERT INTO ReagentPropList_tbl(reagentID, propertyID, propertyValue, " + posType + ") VALUES(" + `rID` + ", " + `propID` + ", " + `propVal` + ", " + `position` + " AND status='ACTIVE')")

	
	# Set the given property position to 0
	# propID: the ID of the property to delete position for (e.g. propID '1' => "5' linker"; '14' => "5' cloning site", etc.)
	# posType: one of "startPos" or "endPos"
	# Updated April 21/08: added propVal argument for features
	def deletePropertyPosition(self, rID, propID, posType, propVal=""):
		
		db = self.db
		cursor = self.cursor
		
		if len(propVal) == 0:
			cursor.execute("UPDATE ReagentPropList_tbl SET " + posType + "=DEFAULT WHERE reagentID=" + `rID` + " AND propertyID=" + `propID`)
		else:
			cursor.execute("UPDATE ReagentPropList_tbl SET " + posType + "=DEFAULT WHERE reagentID=" + `rID` + " AND propertyID=" + `propID` + " AND propertyValue=" + `propVal`)


	# Feb. 26/08: Find feature start position
	# Updated April 7/08: Aadded fVal argument - for features with multiple values
	# FEB. 4/09: CAUTION: FEATURES WITH IDENTICAL VALUES MAY **ALSO** HAVE MULTIPLE POSITIONS, i.e. A FEATURE MAY OCCUR MORE THAN ONCE IN A SEQUENCE - e.g. a Vector sequence may have > 1 splice donor!  This function should return a LIST - fix at the earliest!!!  Ditto for endPos
	def findReagentFeatureStart(self, rID, fID, fVal=""):
		
		db = self.db
		cursor = self.cursor
		
		if len(fVal) != 0:
			#print "SELECT startPos FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND propertyID=" + `fID` + " AND propertyValue=" + `fVal` + " AND status='ACTIVE'"
			
			cursor.execute("SELECT startPos FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND propertyID=" + `fID` + " AND propertyValue=" + `fVal` + " AND status='ACTIVE'")
			result = cursor.fetchone()
			
			if result:
				return int(result[0])
		else:
			cursor.execute("SELECT startPos FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND propertyID=" + `fID` + " AND status='ACTIVE'")
			result = cursor.fetchone()
			
			if result:
				return int(result[0])
			
		return 0


	# Feb. 26/08: Find feature end position
	# Updated April 7/08: See findReagentFeatureStart comments
	# FEB. 4/09: CAUTION: FEATURES WITH IDENTICAL VALUES MAY **ALSO** HAVE MULTIPLE POSITIONS, i.e. A FEATURE MAY OCCUR MORE THAN ONCE IN A SEQUENCE - e.g. a Vector sequence may have > 1 splice donor!  This function should return a LIST - fix at the earliest!!!  Ditto for startPos
	def findReagentFeatureEnd(self, rID, fID, fVal=""):
		
		db = self.db
		cursor = self.cursor
		
		if len(fVal) != 0:
			cursor.execute("SELECT endPos FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND propertyID=" + `fID` + " AND propertyValue=" + `fVal` + " AND status='ACTIVE'")
			result = cursor.fetchone()
			
			if result:
				return int(result[0])
		else:
			cursor.execute("SELECT endPos FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND propertyID=" + `fID` + " AND status='ACTIVE'")
			result = cursor.fetchone()
			
			if result:
				return int(result[0])
			
		return 0
	
	
	# April 10/08
	def findReagentFeatureDescriptor(self, rID, fID, fVal=""):
		
		db = self.db
		cursor = self.cursor

		#print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!		
		#print
		
		if len(fVal) != 0:
			#print "SELECT descriptor FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND propertyID=" + `fID` + " AND propertyValue=" + `fVal` + " AND status='ACTIVE'"
			
			cursor.execute("SELECT descriptor FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND propertyID=" + `fID` + " AND propertyValue=" + `fVal` + " AND status='ACTIVE'")
			result = cursor.fetchone()
			
			if result:
				return result[0]
		else:
			cursor.execute("SELECT descriptor FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND propertyID=" + `fID` + " AND status='ACTIVE'")
			result = cursor.fetchone()
			
			if result:
				return result[0]

		return 0
	
	
	# April 16/08: Find ALL the values of a current feature
	# Similar in action to findSimplePropertyValue, except return a list of values instead of a single value
	def findReagentFeatureValues(self, rID, featureID):
		
		db = self.db
		cursor = self.cursor
		
		fVals = []
		
		cursor.execute("SELECT propertyValue FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND propertyID=" + `featureID` + " AND propertyValue != '' AND status='ACTIVE'")
		
		f_results = cursor.fetchall()
		
		for fRes in f_results:
			fVal = fRes[0]
			fVals.append(fVal)
		
		return fVals
		
		
	# April 16/08: Get all the features of a given reagent (sequence features - tag type, polyA, origin, etc.)
	# Return: list of Feature OBJECTS
	def findReagentSequenceFeatures(self, rID, isProtein=False, isRNA=False):
		
		db = self.db
		cursor = self.cursor

		tmpReagent = self.createReagent(rID)
		
		#rFeatures = tmpReagent.getSequenceFeatures()	# list of feature names - Removed Sept. 10/09, see fix below
		fDescr = tmpReagent.getFeatureDescriptors()

		mapper = ReagentPropertyMapper(db, cursor)
		pHandler = ReagentPropertyHandler(db, cursor)
		rtPropHandler = ReagentTypePropertyHandler(db, cursor)
		
		prop_Name_ID_Map = mapper.mapPropNameID()	# (prop name, prop id)
		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()
		
		# Update Sept. 10/09: No longer can rely on preset list - must retrieve database values according to reagent type
		rTypeID = self.findReagentTypeID(rID)
		
		if isProtein:
			rFeatures = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, prop_Category_Name_ID_Map["Protein Sequence Features"])
			fTypeCat = "Protein Sequence Features"
			
		elif isRNA:
			rFeatures = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, prop_Category_Name_ID_Map["RNA Sequence Features"])
			fTypeCat = "RNA Sequence Features"
		else:
			rFeatures = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, prop_Category_Name_ID_Map["DNA Sequence Features"])
			fTypeCat = "DNA Sequence Features"
		
		features = []					# list of Feature OBJECTS
		
		#print "Content-type:text/html"
		#print
		
		for rf in rFeatures:
			#print rf
			
			#featureID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[rf.lower()], prop_Category_Name_ID_Map["DNA Sequence Features"])

			featureID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[rf.lower()], prop_Category_Name_ID_Map[fTypeCat])
			#print "?? " + rf + `featureID`
			
			#print "SELECT propertyValue, startPos, endPos, direction, descriptor FROM ReagentPropList_tbl p WHERE p.reagentID=" + `rID` + " AND p.propertyID=" + `featureID` + " AND p.status='ACTIVE'"
			
			cursor.execute("SELECT propertyValue, startPos, endPos, direction, descriptor FROM ReagentPropList_tbl p WHERE p.reagentID=" + `rID` + " AND p.propertyID=" + `featureID` + " AND p.status='ACTIVE'")
			
			f_results = cursor.fetchall()
			
			for fRes in f_results:
				
				fVal = fRes[0]
				fStart = int(fRes[1])
				fEnd = int(fRes[2])
				fDir = fRes[3]
				
				#print fVal
				#print `fDescr`
				#print fDir
				
				if fDescr.has_key(rf):
					#print "Feature " + rf + ", "
					#print `fRes`
					
					if len(fRes) > 4 and fRes[4]:
						fdType = fDescr[rf]
						#print fdType
						fdName = fRes[4]
						#print fdName
					else:
						fdType = None
						fdName = None
						
					tmpFeature = SequenceFeature(rf, fVal, fStart, fEnd, fDir, fdType, fdName)
				
				else:
					tmpFeature = SequenceFeature(rf, fVal, fStart, fEnd, fDir)
				
				#print `tmpFeature`
				
				#print "Feature " + rf + ": " + fVal + ", start " + `fStart` + ", end " + `fEnd` + ", direction " + fDir + ", descriptor " + `tmpFeature.getFeatureDescrName()`
				
				features.append(tmpFeature)
		
		#print `features`
		return features
	
	
	# Set the orientation (direction) of a reagent property to the value of propDirection - either 'forward' or 'reverse'
	# (using the term 'direction' to distinguish between global sequence orientation, even though the concept is the same)
	# Input: propDirection - STRING, one of 'forward' or 'reverse' - in LOWERCASE
	def setPropertyDirection(self, rID, propID, propDirection):
		
		db = self.db
		cursor = self.cursor
		
		if self.existsProperty(rID, propID):
			cursor.execute("UPDATE ReagentPropList_tbl SET direction=" + `propDirection` + " WHERE  reagentID=" + `rID` + " AND propertyID=" + `propID`)
		else:
			cursor.execute("INSERT INTO ReagentPropList_tbl(reagentID, propertyID, direction) VALUES(" + `rID` + ", " + `propID` + ", " + `propDirection` + ")")
			

	# Check if a property exists for this reagent:
	def existsProperty(self, rID, propID):
		
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access

		cursor.execute("SELECT `propListID` FROM `ReagentPropList_tbl` WHERE `reagentID`=" + `rID` + " AND `propertyID`=" + `propID` + " AND `status`='ACTIVE'")
		result = cursor.fetchall()	# e.g. for checkbox properties - there are multiple values

		if result:
			return 1
		else:
			return 0

	# Dec. 17/09
	def existsFeature(self, rID, fID, fVal, fStart, fEnd, fDir, fDescr=None):
		
		#print "Content-type:text/html"
		#print
		#print "SELECT propListID FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND propertyID=" + `fID` + " AND propertyValue=" + `fVal` + " AND startPos=" + `fStart` + " AND endPos=" + `fEnd` + " AND direction=" + `fDir` + " AND descriptor=" + `fDescr` + " AND status='ACTIVE'"
		
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access

		if fDescr:
			cursor.execute("SELECT propListID FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND propertyID=" + `fID` + " AND propertyValue=" + `fVal` + " AND startPos=" + `fStart` + " AND endPos=" + `fEnd` + " AND direction=" + `fDir` + " AND descriptor=" + `fDescr` + " AND status='ACTIVE'")
		else:
			cursor.execute("SELECT propListID FROM ReagentPropList_tbl WHERE reagentID=" + `rID` + " AND propertyID=" + `fID` + " AND propertyValue=" + `fVal` + " AND startPos=" + `fStart` + " AND endPos=" + `fEnd` + " AND direction=" + `fDir` + " AND status='ACTIVE'")
			
		results = cursor.fetchall()
		
		if len(results) == 0:
			return False
		
		#elif len(results) > 0:
			#for result in resuls:
				#if not result:
					#return False
		
		return True
	
	
	# Store a new property value for this reagent
	# Updated Feb. 12/08: Record start and end positions if applicable
	# June 9/08: Added 'pDir' parameter representing orientation
	# propID == propCatID
	def addReagentProperty(self, rID, propID, propVal, pStart=0, pEnd=0, pDir="forward"):
		
		#print "Content-type:text/html"
		#print
		#print "Inserting "
		#print propID
		#print propVal
		#print `pStart`
		#print `pEnd`
		#print `pDir`

		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		if utils.isList(propVal):	# checkboxes
			for v in propVal:
				if len(v) > 0:
					# May 30/08: Sometimes during preprocessing empty position values may end up < 0. Catch here and remedy
					if pStart < 0:
						query = "INSERT INTO ReagentPropList_tbl(reagentID, propertyID, propertyValue, startPos, endPos, direction) VALUES(" + `rID` + ", " + `propID` + ", " + `v` + ", '0', " + `pEnd` + ", " + `pDir` + ")"
					elif pEnd < 0:
						query = "INSERT INTO ReagentPropList_tbl(reagentID, propertyID, propertyValue, startPos, endPos, direction) VALUES(" + `rID` + ", " + `propID` + ", " + `v` + ", " + `pStart` + ", '0', " + ", " + `pDir` + ")"
					else:
						query = "INSERT INTO ReagentPropList_tbl(reagentID, propertyID, propertyValue, startPos, endPos, direction) VALUES(" + `rID` + ", " + `propID` + ", " + `v` + ", " + `pStart` + ", " + `pEnd` + ", " + `pDir` + ")"
						
					cursor.execute(query)	# needs to be executed FOR EVERY VALUE IN LIST
		else:
			# May 30/08: Sometimes during preprocessing empty position values may end up < 0. Catch here and remedy
			if pStart < 0:
				query = "INSERT INTO ReagentPropList_tbl(reagentID, propertyID, propertyValue, startPos, endPos, direction) VALUES(" + `rID` + ", " + `propID` + ", " + `propVal` +  ", '0', " + `pEnd` + ", " + `pDir` + ")"
			elif pEnd < 0:
				query = "INSERT INTO ReagentPropList_tbl(reagentID, propertyID, propertyValue, startPos, endPos, direction) VALUES(" + `rID` + ", " + `propID` + ", " + `propVal` +  ", " + `pStart` + ", '0', " + `pDir` + ")"
			else:
				# Update June 24, 2010: Do not use backticks, as they distort floating-point precision
				if type(propVal) == types.FloatType:
					query = "INSERT INTO ReagentPropList_tbl(reagentID, propertyID, propertyValue, startPos, endPos, direction) VALUES(" + `rID` + ", " + `propID` + ", '" + str(propVal) +  "', " + `pStart` + ", " + `pEnd` + ", " + `pDir` + ")"
				else:
					query = "INSERT INTO ReagentPropList_tbl(reagentID, propertyID, propertyValue, startPos, endPos, direction) VALUES(" + `rID` + ", " + `propID` + ", " + `propVal` +  ", " + `pStart` + ", " + `pEnd` + ", " + `pDir` + ")"
		
			#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
			#print					# DITTO
			#print query
	
			cursor.execute(query)
	
	
	# Delete (deprecate) reagent FEATURE - account for multiple values
	# Written April 8, 2008 
	def deleteReagentFeature(self, rID, fID, fVal = "", fStart=0, fEnd=0, fDir='forward', fDescr=""):
		
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		if len(fVal) == 0:
			cursor.execute("UPDATE `ReagentPropList_tbl` SET `status`='DEP' WHERE `reagentID`=" + `rID` + " AND `propertyID`=" + `fID` + " AND `status`='ACTIVE'")
			
		else:
			if fDescr and len(fDescr) > 0:
				cursor.execute("UPDATE `ReagentPropList_tbl` SET `status`='DEP' WHERE `reagentID`=" + `rID` + " AND `propertyID`=" + `fID` + " AND propertyValue=" + `fVal` + " AND startPos=" + `fStart` + " AND endPos=" + `fEnd` + " AND descriptor=" + `fDescr` + " AND direction=" + `fDir` + " AND `status`='ACTIVE'")
			else:			
				cursor.execute("UPDATE `ReagentPropList_tbl` SET `status`='DEP' WHERE `reagentID`=" + `rID` + " AND `propertyID`=" + `fID` + " AND propertyValue=" + `fVal` + " AND startPos=" + `fStart` + " AND endPos=" + `fEnd` + " AND direction=" + `fDir` + " AND `status`='ACTIVE'")

		
	# Delete (deprecate) a specific property for this reagent
	def deleteReagentProperty(self, rID, propID):
		
		#print "Content-type:text/html"
		#print
		#print "UPDATE `ReagentPropList_tbl` SET `status`='DEP' WHERE `reagentID`=" + `rID` + " AND `propertyID`=" + `propID` + " AND `status`='ACTIVE'"
		
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		cursor.execute("UPDATE `ReagentPropList_tbl` SET `status`='DEP' WHERE `reagentID`=" + `rID` + " AND `propertyID`=" + `propID` + " AND `status`='ACTIVE'")
		
	
	# Insert a new set of properties into the database for the given reagent ID
	def insertReagentProperties(self, rID, propDict):
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		#print "Content-type:text/html"
		#print
		
		for propID in propDict.keys():
			propVal = propDict[propID]
			#print "INSERT INTO `ReagentPropList_tbl`(`reagentID`, `propertyID`, `propertyValue`) VALUES(" + `rID` + ", " + `propID` + ", " + `propVal` + ")"
			cursor.execute("INSERT INTO `ReagentPropList_tbl`(`reagentID`, `propertyID`, `propertyValue`) VALUES(" + `rID` + ", " + `propID` + ", " + `propVal` + ")")
	
	
	# Update the value of one property of the given reagent in ReagentPropList_tbl
	def changePropertyValue(self, rID, propID, propVal):
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print propID
		
		# added check for existence here again Jan. 25/10
		if self.existsProperty(rID, propID):
			# For multiple property values == checkboxes - don't UPDATE, too complicated - just delete old values and insert new ones
			if utils.isList(propVal):
				self.deleteReagentProperty(rID, propID)
				self.addReagentProperty(rID, propID, propVal)
			else:
				# otherwise, for simple properties just change their value
				#pVal = ""
				
				# June 23, 2010: Backticks ruin the set floating-point precision, discard rounding:
				# e.g. 33.33 is converted to 33.32999999999
				#
				#if type(propVal) == types.FloatType:
					#pVal = str(propVal)
					#query = "UPDATE ReagentPropList_tbl SET propertyValue='" + pVal + "' WHERE reagentID=" + `rID` + " AND propertyID=" + `propID` + " AND status='ACTIVE'"
					#cursor.execute(query)
				#else:

				#print "UPDATE ReagentPropList_tbl SET propertyValue='" + str(propVal).replace("'", "\\'") + "' WHERE reagentID=" + `rID` + " AND propertyID=" + `propID` + " AND status='ACTIVE'"

				cursor.execute("UPDATE ReagentPropList_tbl SET propertyValue='" + str(propVal).replace("'", "\\'") + "' WHERE reagentID=" + `rID` + " AND propertyID=" + `propID` + " AND status='ACTIVE'")
		else:
			self.addReagentProperty(rID, propID, propVal)

	# Set reagent's DNA sequence property value to seqID argument if seqID > 0; otherwise delete
	def updateDNASequence(self, rid, newSeqID):
		
		db = self.db
		cursor = self.cursor
		
		propHandler = ReagentPropertyHandler(db, cursor)
		mapper = ReagentPropertyMapper(db, cursor)
		
		prop_Category_Name_ID_Map = mapper.mapPropCategoryNameID()
		prop_Name_ID_Map = mapper.mapPropNameID()			# (prop name, prop id)
		
		#seqPropID = propHandler.findPropID("sequence")
		seqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])

		if newSeqID > 0:
			# Check if a DNA sequence existed for this reagent in the first place	
			oldSeq_id = self.findIndexPropertyValue(rid, seqPropID)
			
			if oldSeq_id > 0:
				# there is a DNA sequence for this reagent
				
				# check if it is identical to translated value
				if oldSeq_id != newSeqID:
				
					# Not identical, change (overwrite old sequence ID in ReagentPropList_tbl)
					self.changePropertyValue(rid, seqPropID, newSeqID)
					
				# otherwise don't do anything
				
			else:
				# No DNA sequence had been stored for this reagent at all.  Insert new value:
				self.insertReagentProperties(rid, {seqPropID:newSeqID})
		else:
			# Delete the sequence for this reagent
			self.deleteReagentProperty(rid, seqPropID)
		
		
	# Set reagent's protein sequence property value to seqID argument if seqID > 0; otherwise delete
	def updateProteinSequence(self, rid, newProtSeqID):
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"
		#print
		
		propHandler = ReagentPropertyHandler(db, cursor)
		propMapper = ReagentPropertyMapper(db, cursor)

		prop_Name_ID_Map = propMapper.mapPropNameID()			# (prop name, prop id)
		
		# changes made July 2/09
		prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()
		prop_Category_ID_Name_Map = propMapper.mapPropCategoryNameID()
		
		protSeqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein translation"], prop_Category_Name_ID_Map["DNA Sequence"])
		
		#print protSeqPropID
		
		if newProtSeqID > 0:
		
			# Check if a protein sequence existed for this reagent in the first place
			oldProtSeq_id = self.findIndexPropertyValue(rid, protSeqPropID)
			#print oldProtSeq_id
		
			if oldProtSeq_id > 0:
				# there is a protein sequence for this reagent
				
				# check if it is identical to translated value
				if oldProtSeq_id != newProtSeqID:
					# Not identical, change (overwrite old sequence ID in ReagentPropList_tbl)
					self.changePropertyValue(rid, protSeqPropID, newProtSeqID)

				# otherwise don't do anything

			else:
				# No protein sequence has been stored for this reagent at all.  Insert translated value:
				#print "ok, inserting?"
				self.insertReagentProperties(rid, {protSeqPropID:newProtSeqID})
		else:
			#print "and now deleting again"
			# Delete protein sequence for this reagent
			self.deleteReagentProperty(rid, protSeqPropID)

		#self.updatePeptideMass(rid, newProtSeqID)

	
	'''
	# Jan. 22, 2010
	def updatePeptideMass(self, rID, newProtSeqID):
		
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"
		#print
		
		propHandler = ReagentPropertyHandler(db, cursor)
		propMapper = ReagentPropertyMapper(db, cursor)

		prop_Name_ID_Map = propMapper.mapPropNameID()			# (prop name, prop id)
		
		# changes made July 2/09
		prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()
		prop_Category_ID_Name_Map = propMapper.mapPropCategoryNameID()
		
		protSeqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein translation"], prop_Category_Name_ID_Map["DNA Sequence"])
		
		# Jan. 22, 2010: calculate MW of translated protein sequence
		protHandler = ProteinHandler(db, cursor)
		
		peptideMassPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["Protein Sequence"])
		
		#print "peptideMassPropID " + `peptideMassPropID`
		#print protSeqPropID
		
		# Jan. 22, 2010: get the original sequence to compute its MW
		newProtSeq = protHandler.findSequenceByID(newProtSeqID)
		
		peptideMass = protHandler.calculatePeptideMass(newProtSeq)
		
		#print newProtSeq
		#print "peptideMass " + `peptideMass`
		
		if not self.existsProperty(rID, peptideMassPropID):
			# Jan. 22, 2010: store MW
			self.addReagentProperty(rID, peptideMassPropID, peptideMass)
			
		elif len(newProtSeq) == 0:
			self.deleteReagentProperty(rID, peptideMassPropID)
			
		else:
			self.changePropertyValue(rID, peptideMassPropID, peptideMass)
	'''	
	
	# March 30/07, Marina: Add new comment value for the given reagent
	def addComment(self, rID, commPropID, comment):

		db = self.db
		cursor = self.cursor

		commHandler = CommentHandler(db, cursor)

		# Create a Comment table entry

		# May 31/07: Select a CommentLinkID first - always 1 for reagent
		commLinkID = commHandler.findCommentLinkID('Reagent')
		newCommID = commHandler.insertComment(commLinkID, comment)

		# store comment ID as a property
		self.addReagentProperty(rID, commPropID, newCommID)


	# Update experimental/verificaiton comment or description for this reagent
	# DO NOT CHANGE COMMENT VALUE IN GeneralComments_tbl!! ONLY update ReagentPropList_tbl - change the comment ID for reagent identified by rID
	def updateComments(self, rID, commPropID, comment):

		#print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO

		db = self.db
		cursor = self.cursor
		commHandler = CommentHandler(db, cursor)

		newCommID = -1

		# First, check if the new comment exists in OpenFreezer
		if utils.isList(comment):
			for c in comment:
				#self.updateComments(rID, commPropID, c)
				newCommID = commHandler.existsComment(1, c)

				if newCommID < 0:
					# does not exist, insert

					# Select a CommentLinkID first - always 1 for reagent
					commLinkID = commHandler.findCommentLinkID('Reagent')
					newCommID = commHandler.insertComment(commLinkID, c)

				# Now check if the reagent has a comment assigned to it and, if yes, whether that comment is identical to the new value
				if self.existsProperty(rID, commPropID):

					oldCommID = self.findIndexPropertyValue(rID, commPropID)
					oldCommentVal = commHandler.findCommentByID(oldCommID)

					#if oldCommentVal != c:
					self.changePropertyValue(rID, commPropID, newCommID)

					# otherwise they're identical - don't do anything
				else:
					
					# This reagent doesn't have a comment at all, insert new value
					self.addReagentProperty(rID, commPropID, newCommID)
		else:
			newCommID = commHandler.existsComment(1, comment)
			
			if newCommID < 0:
				# does not exist, insert

				# Select a CommentLinkID first - always 1 for reagent
				commLinkID = commHandler.findCommentLinkID('Reagent')
				newCommID = commHandler.insertComment(commLinkID, comment)

			# Now check if the reagent has a comment assigned to it and, if yes, whether that comment is identical to the new value
			if self.existsProperty(rID, commPropID):
				
				oldCommID = self.findIndexPropertyValue(rID, commPropID)
				
				## Check added May 6/08 - comment could exist but still be empty
				#if oldCommID < 0:
					## change empty comment to argument value
					#self.changePropertyValue(rID, commPropID, newCommID)
				#else:
				oldCommentVal = commHandler.findCommentByID(oldCommID)

				if oldCommentVal != comment:
					self.changePropertyValue(rID, commPropID, newCommID)

				# otherwise they're identical - don't do anything
			else:
				# This reagent doesn't have a comment at all, insert new value
				self.addReagentProperty(rID, commPropID, newCommID)


	# Add associations to a new reagent at creation
	def addReagentAssociations(self, rID, assocPropsDict):

		db = self.db
		cursor = self.cursor

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print "2: " + `assocPropsDict`

		assocMapper = ReagentAssociationMapper(db, cursor)
		aHandler = ReagentAssociationHandler(db, cursor)
		rtMapper = ReagentTypeMapper(db, cursor)
		rtaHandler = ReagentTypeAssociationHandler(db, cursor)

		assoc_Alias_ID_Map = assocMapper.mapAssocAliasID()
		assoc_Alias_Name_Map = assocMapper.mapAssocAliasName()	# (assocAlias, assocName)
		assoc_Name_ID_Map = assocMapper.mapAssocNameID()	# (assocName, assocID)
		assoc_ID_Name_Map = assocMapper.mapAssocIDName()	# (assocID, assocName)

		#print `assoc_ID_Name_Map`

		rType_Name_ID_Map = rtMapper.mapTypeNameID()
		reagent = self.createReagent(rID)

		# Added Sept. 11/07: get association type
		rType = reagent.getType()
		rTypeID = rType_Name_ID_Map[rType]

		currAssocTypes = reagent.getAssociationTypes()		# associations for the given reagent type (as the form input contains hidden association fields for reagent types other than the one being created)

		# Create association (Association_tbl entry) to assign properties to it IFF not done already (depends on reagent type)
		if aHandler.findReagentAssociationID(rID) <= 0:

			# Create new association   
			# Find ATypeID (returned by getCloningMethod() function)
			if not reagent.getCloningMethod():

				# No ATypeID assigned yet, find it based on reagent type
				aTypeID = rtaHandler.findAssociationByReagentType(rTypeID)
			else:
				aTypeID = reagent.getCloningMethod()

			# added Jan. 25, 2010: have to account for reagent types w/o parents now
			if aTypeID > 0:
				assocID = self.createReagentAssociation(rID, aTypeID)
	
				for assocPropAlias in assocPropsDict.keys():
					#print assocPropsDict[assocPropAlias]
					newAssocVal = assocPropsDict[assocPropAlias]
					#print len(newAssocVal)
					assocPropName = assoc_Alias_Name_Map[assocPropAlias]
					assocPropID = assoc_Name_ID_Map[assocPropName]
				
					if len(newAssocVal) > 0:  
						self.addAssociationValue(rID, assocPropID, self.convertReagentToDatabaseID(newAssocVal), assocID)
		else:

			# otherwise, DELETE old values before inserting new ones - May 5/08
			assocID = aHandler.findReagentAssociationID(rID)

			for assocPropID in assocPropsDict.keys():

				# Aug. 25/09: can now save multiple parents for new reagent types
				#newAssocVal = assocPropsDict[assocPropAlias]

				if utils.isList(assocPropsDict[assocPropID]):
					assocList = assocPropsDict[assocPropID]
					
					assocPropName = assoc_ID_Name_Map[assocPropID]
					self.deleteReagentAssociationProp(rID, assocPropID)	# May 5/08
					
					for newAssocVal in assocList:
					
						if len(newAssocVal) > 0:
							self.addAssociationValue(rID, assocPropID, self.convertReagentToDatabaseID(newAssocVal), assocID)
				else:
					newAssocVal = assocPropsDict[assocPropID]

					assocPropName = assoc_ID_Name_Map[assocPropID]

					if len(newAssocVal) > 0:
						self.deleteReagentAssociationProp(rID, assocPropID)	# May 5/08
						self.addAssociationValue(rID, assocPropID, self.convertReagentToDatabaseID(newAssocVal), assocID)


	# Update parent/child associations of a reagent (primarily parent)
	# Parameters: assocPropsDict: (assocPropID, value)
	def updateReagentAssociations(self, rID, assocPropsDict):

		db = self.db
		cursor = self.cursor

		# DEBUGGING
		#print "Content-type:text/html"
		#print
		#print `assocPropsDict`

		aHandler = ReagentAssociationHandler(db, cursor)
		assocMapper = ReagentAssociationMapper(db, cursor)
		rtaHandler = ReagentTypeAssociationHandler(db, cursor)

		assoc_Alias_ID_Map = assocMapper.mapAssocAliasID()
		assoc_Alias_Name_Map = assocMapper.mapAssocAliasName()	# (assocAlias, assocName)
		assoc_Name_ID_Map = assocMapper.mapAssocNameID()	# (assocName, assocID)
		assoc_ID_Name_Map = assocMapper.mapAssocIDName()

		reagent = self.createReagent(rID)		
		oldAssoc = reagent.getAssociations()			# (assocName, assocValue)

		#print `oldAssoc`

		rType = reagent.getType()

		# Dec. 9/09: removed 'Oligo'
		#if rType == 'Vector' or rType == 'Insert' or rType == 'Oligo' or rType == 'CellLine':
		if rType == 'Vector' or rType == 'Insert' or rType == 'CellLine':

			for assocPropID in assocPropsDict.keys():
				#print assocPropID
				assocPropName = assoc_ID_Name_Map[assocPropID]

				# Feb. 11/10: this IS a list, after all (e.g. 2 parent vectors for a new reagent type)
				newAssocVals = assocPropsDict[assocPropID]

				#newAssocVal = assocPropsDict[assocPropID]
				#print newAssocVal
				
				if not utils.isList(newAssocVals):
					newAssocVals = utils.toArray(newAssocVals)

				for newAssocVal in newAssocVals:
					
					if len(newAssocVal) > 0:
						# Association value is not empty
						if oldAssoc.has_key(assocPropName):
							
							oldAssocVal = self.convertDatabaseToReagentID(oldAssoc[assocPropName])
							#print oldAssocVal
	
							# check if value is the same
							if oldAssocVal != newAssocVal:
	
								# check still redundant (feb. 16/10)
								## check if new value is empty, in which case it should be deleted
								#if len(newAssocVal) > 0:
									#print newAssocVal
	
								# non-empty, update
								rAssocType = reagent.getCloningMethod()

								if utils.isList(newAssocVal):
									for tmpAssocVal in newAssocVal:
										self.updateAssociationValue(rID, rAssocType, assocPropID, self.convertReagentToDatabaseID(tmpAssocVal))
								else:
									self.updateAssociationValue(rID, rAssocType, assocPropID, self.convertReagentToDatabaseID(newAssocVal))
									
								#else:
									## delete
									#self.deleteReagentAssociationProp(rID, assocPropID)
	
							# don't do anything if old and new values are identical
	
						else:
							# Association didn't exist before, insert if not empty
							if len(newAssocVal) > 0:
	
								# Here need to do a bit of preliminary work: determine ATypeID from reagent type
								# Since this function currently only applies to Inserts, the code is straightforward
								rTypeID = self.findReagentTypeID(rID)
	
								# Updated August 23/07
								if rTypeID == 2:
									aTypeID = rtaHandler.findAssociationByReagentType(rTypeID)
								else:
									aTypeID = reagent.getCloningMethod()
	
								# Aug 24/07: First check if an ASSOCIATION (an entry in Association_tbl) exists for this reagent, only there are no properties associated with it (no entries for this assID in AssocProp_tbl)
								assocID = aHandler.findReagentAssociationID(rID)
	
								if assocID <= 0:
									# only in this case create a new association for this reagent
									assocID = self.createReagentAssociation(rID, aTypeID)
								
								self.addAssociationValue(rID, assocPropID, self.convertReagentToDatabaseID(newAssocVal), assocID)
							else:
								# Delete
								self.deleteReagentAssociationProp(rID, assocPropID)
					else:
						self.deleteReagentAssociationProp(rID, assocPropID)
		else:
			# delete old values and create new association
			for assocName in oldAssoc.keys():
				assocPropID = assoc_Name_ID_Map[assocName]
				self.deleteReagentAssociationProp(rID, assocPropID)

			self.addReagentAssociations(rID, assocPropsDict)


	# Add a new reagent ASSOCIATION - i.e. a NEW ENTRY IN ASSOCIATION_TBL
	def createReagentAssociation(self, rID, aTypeID):

		db = self.db
		cursor = self.cursor

		aHandler = ReagentAssociationHandler(db, cursor)
		#aTypeID = aHandler.findReagentAssociationType(rID)

		assocID = -1

		# Jan. 25/10: removed assertion, dealing with reagent types w/o parents now
		#try:
			#assert aTypeID > 0

		cursor.execute("INSERT INTO Association_tbl(reagentID, ATypeID) VALUES(" + `rID` + ", " + `aTypeID` + ")")
		assocID = int(db.insert_id())

		#except AssertionError:
			#print "Content-type:text/html"
			#print
			#print "Error: Invalid ATypeID: must be greater than 0"

		return assocID


	# Add a new association VALUE for the given reagent (e.g. if an Insert had oligos filled in but not IPV, it already has an entry in Association_tbl, just need to add IPV value to list of assocProps for that Insert)
	# Creating a new entry in **AssocProp_tbl**
	# Updated August 24/07: Add assocID argument; may be empty
	def addAssociationValue(self, rID, assocPropID, newAssocValue, assocID=0):

		db = self.db
		cursor = self.cursor

		if assocID == 0:
			aHandler = ReagentAssociationHandler(db, cursor)
			assocID = aHandler.findReagentAssociationID(rID)

		try:
			assert assocID > 0
			#print "INSERT INTO AssocProp_tbl(assID, APropertyID, propertyValue) VALUES(" + `assocID` + ", " + `assocPropID` + ", " + `newAssocValue` + ")"

			cursor.execute("INSERT INTO AssocProp_tbl(assID, APropertyID, propertyValue) VALUES(" + `assocID` + ", " + `assocPropID` + ", " + `newAssocValue` + ")")

		except AssertionError:
			# shouldn't happen
			print "Content-type:text/html"
			print
			print "This shouldn't happen!"


	# Update the value of an existing reagent association
	def updateAssociationValue(self, rID, rAssocType, assocPropID, newAssocValue):

		db = self.db
		cursor = self.cursor

		aHandler = ReagentAssociationHandler(db, cursor)
		assocID = aHandler.findReagentAssociationID(rID)

		try:
			assert assocID > 0
			cursor.execute("UPDATE AssocProp_tbl SET propertyValue=" + `newAssocValue` + " WHERE assID=" + `assocID` + " AND APropertyID=" + `assocPropID` + " AND status='ACTIVE'")

		except AssertionError:

			# This is probably the case where the reagent has no associations and user wants to add parents thru Modify view.  Create the association first, then assign values to it
			assID = self.createReagentAssociation(rID, rAssocType)
			self.addAssociationValue(rID, assocPropID, newAssocValue)


	# Delete the given association for a reagent
	def deleteReagentAssociationProp(self, rID, assocPropID):

		db = self.db
		cursor = self.cursor

		aHandler = ReagentAssociationHandler(db, cursor)

		# Updated Nov. 27/07: For Inserts there is more than one assID per rID, for different ATypeIDs: 4 for "Insert Oligos" and 6 for IPV - hence, for Inserts should also include assocPropID in assID selection
		#assocID = aHandler.findReagentAssociationID(rID)
		assocID = aHandler.findSpeReagentAssociationPropertyID(rID, assocPropID)

		if assocID > 0:
			aHandler.deleteReagentAssociationPropertyValue(assocID, assocPropID)

		# otherwise don't have to do anything; nothing to delete if the association didn't exist in the first place


	###################################################################################################################################
	# Find parent/child values of the reagent identified by rID
	# Returns: dictionary containing (assocPropType**ID**, assocValue) for this reagent
	# e.g. rID = '13' (represents V13); assocPropTypeID representing 'Parent Vector' = 1; parent vector of V13 is V26 (rID 26)
	# Then the entry representing this association in the resulting dictionary is (1, 26)
	#######################################################################################################################################
	def findAllReagentAssociationsByID(self, rID):

		db = self.db
		cursor = self.cursor

		aHandler = ReagentAssociationHandler(db, cursor)
		assocID = aHandler.findReagentAssociationID(rID)

		assocDict = {}

		try:
			assert assocID > 0
			cursor.execute("SELECT APropertyID, propertyValue FROM AssocProp_tbl WHERE assID=" + `assocID` + " AND status='ACTIVE'")
			results = cursor.fetchall()

			for result in results:

				assocTypeID = int(result[0])
				assocValue = int(result[1])

				assocDict[assocTypeID] = assocValue 

		except AssertionError:

			# Need better exception handling, change this
			print "Content-type:text/html"
			print
			print "AssertionError: Reagent " + `rID` + " has no associations"

		return assocDict


	#######################################################################################################################################
	# Find parent/child values of the reagent identified by rID, where the association type (parent vector, insert, etc) is in human-readable form
	# The association values are the **internal database reagent IDs** (reagentID column)
	# e.g. rID = '13' (represents V13); assocPropTypeID representing 'Parent Vector' = 1; parent vector of V13 is V26 (rID 26)
	# Then the entry representing this association in the resulting dictionary is ('parent vector', 26)
	#######################################################################################################################################
	def findAllReagentAssociationsByName(self, rID):

		db = self.db
		cursor = self.cursor

		aHandler = ReagentAssociationHandler(db, cursor)
		assocID = aHandler.findReagentAssociationID(rID)

		assocDict = {}

		if assocID > 0:
			cursor.execute("SELECT t.APropName, p.propertyValue FROM Assoc_Prop_Type_tbl t, AssocProp_tbl p WHERE p.assID=" + `assocID` + " AND p.APropertyID=t.APropertyID AND p.status='ACTIVE' AND t.status='ACTIVE'")
			results = cursor.fetchall()

			for result in results:
				assocType = result[0]
				assocValue = int(result[1])

				assocDict[assocType] = assocValue

		# otherwise the reagent may just not have any associations - that's fine

		return assocDict


	# Nov. 5/08, updated June 8/09 - deprecate reagent ID, its properties, features, DNA/protein sequences and associations
	def deleteReagent(self, rID):
		db = self.db
		cursor = self.cursor

		aHandler = ReagentAssociationHandler(db, cursor)
		lHandler = LocationHandler(db, cursor)

		# Disallow deletion if there are active preps for this reagent
		expID = self.findExperimentID(rID)

		if not self.hasPreps(expID):
			# ok, delete
			assocID = aHandler.findReagentAssociationID(rID)

			# delete (deprecate) all assoc props 
			aHandler.deleteAllAssociationPropertyValues(assocID)

			# delete (deprecate) all associations (Association_tbl entry) for this rID
			self.deleteReagentAssociations(rID)

			self.deleteReagentProperties(rID)

			# delete reagent itself
			cursor.execute("UPDATE Reagents_tbl SET status='DEP' WHERE reagentID=" + `rID` + " AND status='ACTIVE'")

			# Delete Locations
			lHandler.deleteLocation(expID)

			return 1
		else:
			return 0


	def hasPreps(self, expID):
		db = self.db
		cursor = self.cursor

		lHandler = LocationHandler(db, cursor)

		isolates = lHandler.findIsolates(expID)

		for isolateID in isolates:
			preps = lHandler.findPreps(isolateID)

			if len(preps) > 0:
				return True

		return False


	def findExperimentID(self, rID):
		db = self.db
		cursor = self.cursor

		cursor.execute("SELECT expID FROM Experiment_tbl WHERE reagentID=" + `rID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			return int(result[0])

		return 0


	# June 8/09
	def deleteReagentProperties(self, rID):
		db = self.db
		cursor = self.cursor

		# delete reagent properties
		cursor.execute("UPDATE ReagentPropList_tbl SET status='DEP' WHERE reagentID=" + `rID` + " AND status='ACTIVE'")


	# June 8/09
	def deleteReagentAssociations(self, rID):
		db = self.db
		cursor = self.cursor

		cursor.execute("UPDATE Association_tbl SET status='DEP' WHERE reagentID=" + `rID` + " AND status='ACTIVE'")


###################################################################
# InsertHandler class
# Written October 12, 2006, by Marina Olhovsky
# Last modified: February 13, 2008
###################################################################

# Inherits from ReagentHandler
# Contains handler methods specifically for reagents of type Insert
class InsertHandler(ReagentHandler):
	"Contains handler methods specifically for reagents of type Insert"

	#def __init__(self, rID, db, cursor):
		#super(InsertHandler, self).__init__(db, cursor)
		#self.__rID = rID

	# Retrieve the type of a specific insert ('type of insert' property)
	def findTypeOfInsert(self, rID):

		db = self.db
		cursor = self.cursor

		propHandler = ReagentPropertyHandler(db, cursor)
		itPropID = propHandler.findPropID("type of insert")

		iType = ""

		cursor.execute("SELECT `propertyValue` FROM `ReagentPropList_tbl` WHERE `propertyID`=" + `itPropID` + " AND `reagentID`=" + `rID` + " AND `status`='ACTIVE'")
		result = cursor.fetchone()  

		if result:
			iType = result[0].strip().lower()

		return iType


	# Retrieve the open/closed status of rID
	def findOpenClosed(self, rID):

		db = self.db
		cursor = self.cursor

		propHandler = ReagentPropertyHandler(db, cursor)
		ocPropID = propHandler.findPropID("open/closed")

		openClosed = ""

		cursor.execute("SELECT `propertyValue` FROM `ReagentPropList_tbl` WHERE `propertyID`=" + `ocPropID` + " AND `reagentID`=" + `rID`)	
		result = cursor.fetchone()  

		if result:
			openClosed = result[0].strip().lower()

		return openClosed

	# Find the Sense Oligo for this Insert
	# Return: Oligo ID
	def findSenseOligoID(self, rID):

		db = self.db
		cursor = self.cursor

		senseOligoID = -1

		raHandler = ReagentAssociationHandler(db, cursor)
		assocID = raHandler.findReagentAssociationID(rID)

		aHandler = AssociationHandler(db, cursor)
		senseOligoPropID = aHandler.findAssocPropID("sense oligo")
		senseOligoID = aHandler.findAssocPropValue(assocID, senseOligoPropID)

		return senseOligoID

	# The same only for Antisense Oligo
	def findAntisenseOligoID(self, rID):

		db = self.db
		cursor = self.cursor

		antisenseOligoID = -1

		raHandler = ReagentAssociationHandler(db, cursor)
		assocID = raHandler.findReagentAssociationID(rID)

		aHandler = AssociationHandler(db, cursor)
		antisenseOligoPropID = aHandler.findAssocPropID("antisense oligo")
		antisenseOligoID = aHandler.findAssocPropValue(assocID, antisenseOligoPropID)

		return antisenseOligoID


	# Find the Insert Parent Vector of the Insert identified by rID
	# Return: IPV ID             
	def findInsertParentVectorID(self, rID):

		db = self.db
		cursor = self.cursor

		raHandler = ReagentAssociationHandler(db, cursor)
		aHandler = AssociationHandler(db, cursor)

		ipvID = -1

		assocID = raHandler.findReagentAssociationID(rID)
		ipvPropID = aHandler.findAssocPropID("insert parent vector id")
		ipvID = aHandler.findAssocPropValue(assocID, ipvPropID)

		return ipvID


	# Feb. 26/08: Find cDNA start position on Insert sequence
	# It's is an Insert-specific property, handy to have - so no harm adding this function here
	def findCDNAStart(self, rID):

		db = self.db
		cursor = self.cursor

		propHandler = ReagentPropertyHandler(db, cursor)
		propMapper = ReagentPropertyMapper(db, cursor)

		prop_Name_ID_Map = propMapper.mapPropNameID()			# (prop name, prop id)

		# changes made July 2/09    
		prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()

		#cdnaPropID = propHandler.findPropID("cdna insert")	# removed July 2/09
		cdnaPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
		cdnaStart = self.findReagentFeatureStart(rID, cdnaPropID)

		return cdnaStart


	# Feb. 26/08: Find cDNA end position on Insert sequence
	# cDNA is an Insert-specific property, handy to have - so no harm adding this function here
	def findCDNAEnd(self, rID):

		db = self.db
		cursor = self.cursor

		propHandler = ReagentPropertyHandler(db, cursor)
		propMapper = ReagentPropertyMapper(db, cursor)

		prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)
		prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()

		#cdnaPropID = propHandler.findPropID("cdna insert")
		cdnaPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])

		cdnaEnd = self.findReagentFeatureEnd(rID, cdnaPropID)
		return cdnaEnd


	# Feb. 26/08: Translate only the cDNA portion of a reagent's Insert sequence
	def translateInsertCDNA(self, rID, openClosed):

		db = self.db
		cursor = self.cursor

		dnaHandler = DNAHandler(db, cursor)   
		protHandler = ProteinHandler(db, cursor)
		propHandler = ReagentPropertyHandler(db, cursor)
		
		propHandler = ReagentPropertyHandler(db, cursor)
		propMapper = ReagentPropertyMapper(db, cursor)

		prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)
		prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()

		# June 5/08: Same strategy for locating positions - string search on original sequence
		# Fetch the original Insert sequence
		#seqPropID = propHandler.findPropID("sequence")
		
		seqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])

		seqID = self.findIndexPropertyValue(rID, seqPropID)
		sequence = dnaHandler.findSequenceByID(seqID)

		#print "Content-type:text/html"
		#print
		#print sequence

		# Translate only the cDNA portion
		cdnaSeq = self.findInsertCDNASequence(rID)
		#print cdnaSeq
		newProtSeq = protHandler.translateAll(cdnaSeq, openClosed)

		if newProtSeq:
			#print newProtSeq.getSequence()

			cdnaStart = self.findCDNAStart(rID)
			cdnaEnd = self.findCDNAEnd(rID)

			newStart = cdnaStart + newProtSeq.getSeqStart() - 1
			#print `newStart`
			#print `len(newProtSeq.getSequence())`
			newEnd = newStart + len(newProtSeq.getSequence())*3 - 1
			#print `newEnd`

			# added June 5/08
			#reverseDNA = newProtSeq.getReverseDNA()
			#print reverseDNA

			#protSeq = newProtSeq.getSequence()

			#cdnaStart = sequence.lower().find(reverseDNA.lower()) + 1
			#print "start " + `cdnaStart`

			#if cdnaStart < 0:
				## not found
				#cdnaStart = 0
				#cdnaEnd = 0
			#else:
				#cdnaEnd = cdnaStart + len(protSeq)*3 - 1

			#print "End " + `cdnaEnd`

			newProtSeq.setSeqStart(newStart)
			newProtSeq.setSeqEnd(newEnd)
			
			# Jan. 22, 2010: get MW
			mw = protHandler.calculatePeptideMass(newProtSeq.getSequence())
			newProtSeq.setMW(mw)

		return newProtSeq


	# Feb. 26/08: Find the cDNA portion of a reagent's Insert sequence
	def findInsertCDNASequence(self, rID):

		db = self.db
		cursor = self.cursor

		cdnaSeq = ""

		propHandler = ReagentPropertyHandler(db, cursor)
		sHandler = SequenceHandler(db, cursor)

		# Fetch the entire Insert sequence
		#seqPropID = propHandler.findPropID("sequence")
		propMapper = ReagentPropertyMapper(db, cursor)

		prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)
		prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()

		seqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])

		seqID = self.findIndexPropertyValue(rID, seqPropID)
		sequence = sHandler.findSequenceByID(seqID)

		cdnaStart = self.findCDNAStart(rID)
		cdnaEnd = self.findCDNAEnd(rID)

		#print "Content-type:text/html"
		#print

		#print "cdna start " + `cdnaStart`
		#print "cdna end " + `cdnaEnd`

		# 'start' can be 0 but 'end' cannot
		if cdnaEnd > 0:
			cdnaSeq = sequence[cdnaStart-1:cdnaEnd]

		return cdnaSeq


	# April 21/08: Update protein translation if cDNA boundaries are modified
	def updateInsertProteinSequence(self, rID, insertType=None, openClosed=None):

		db = self.db
		cursor = self.cursor

		propHandler = ReagentPropertyHandler(db, cursor)
		sHandler = SequenceHandler(db, cursor)
		protHandler = ProteinHandler(db, cursor)
		propMapper = ReagentPropertyMapper(db, cursor)

		prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)
		prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()

		# July 2/09
		ocPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["open/closed"], prop_Category_Name_ID_Map["Classifiers"])
		insertTypePropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["type of insert"], prop_Category_Name_ID_Map["Classifiers"])

		#ocPropID = prop_Name_ID_Map["open/closed"]		# removed July 2/09
		#insertTypePropID = prop_Name_ID_Map["type of insert"]	# removed July 2/09

		if not insertType:
			insertType = self.findSimplePropertyValue(rID, insertTypePropID)

		if not openClosed:
			openClosed = self.findSimplePropertyValue(rID, ocPropID)

		if insertType and insertType.lower() == 'cdna with utrs':
			openClosed = "special cdna with utrs"

		# Feb. 26/08: Change: Translating only cDNA sequence now
		newProtSeq = self.translateInsertCDNA(rID, openClosed)	# added feb 26/08
		newProtSeqID = protHandler.getSequenceID(newProtSeq)
		#print `newProtSeqID`

		self.updateProteinSequence(rID, newProtSeqID)


###################################################################
# OligoHandler class
# Written July 13, 2009, by Marina Olhovsky
# Last modified March 19, 2010
###################################################################

# Inherits from ReagentHandler
# Contains handler methods specifically for reagents of type Oligo
class OligoHandler(ReagentHandler):
	"Contains handler methods specifically for reagents of type Oligo"

	__base_mw_table = {'A':313.209, 'C':289.184, 'G':329.208, 'T':304.196}
	
	def calculateMW(self, sequence):
		if len(sequence) == 0:
			return None
		
		sequence = utils.squeeze(sequence).strip().upper()
		
		num_a = string.count(sequence, 'A')
		num_c = string.count(sequence, 'C')
		num_g = string.count(sequence, 'G')
		num_t = string.count(sequence, 'T')
		
		a_weight = float(num_a * self.__base_mw_table['A'])
		c_weight = float(num_c * self.__base_mw_table['C'])
		g_weight = float(num_g * self.__base_mw_table['G'])
		t_weight = float(num_t * self.__base_mw_table['T'])
		
		base_weight = float(a_weight) + float(c_weight) + float(g_weight) + float(t_weight)
		
		mw = base_weight - 61.964
		
		return utils.trunc(mw, 3)


	def calculateTm(self, sequence):
		if len(sequence) == 0:
			return None
		
		#sequence = utils.squeeze(sequence).strip().upper()	# don't use this; squeeze() needs an update to filter out \r
		
		tmp_seq = ""
		
		for char in sequence.upper():
			if char == 'A' or char == 'C' or char == 'G' or char == 'T':
				tmp_seq += char
				
		sequence = tmp_seq
			
		#sequence = sequence.replace(" ", "")
		#sequence = sequence.replace("\r", "")
		#sequence = sequence.replace("\n", "")
		#sequence = sequence.replace("\t", "")
		
		#sequence = sequence.upper()
		
		seqLen = float(len(sequence))
		
		num_a = float(string.count(sequence, 'A'))
		#print `num_a` + "A"
		num_c = float(string.count(sequence, 'C'))
		#print `num_c` + "C"
		num_g = float(string.count(sequence, 'G'))
		#print `num_g` + "G"
		num_t = float(string.count(sequence, 'T'))
		#print `num_t` + "T"
		
		if seqLen < 14.0:
			temp = 2 * (num_a + num_t) + 4 * (num_g + num_c)
		else:
			temp = 64.9 + 41.0 * (num_g + num_c - 16.4) / seqLen

		#print temp

		# Truncate the string to 2 significant digits BUT round first!!! (e.g. 69.61900000000 => 69.62; w.o. calling round() first the answer is 69.61)
		return utils.trunc(round(temp, 2), 2)
		
		
	def findOligoType(self, oligoID):
		
		db = self.db
		cursor = self.cursor

		propHandler = ReagentPropertyHandler(db, cursor)
		propMapper = ReagentPropertyMapper(db, cursor)

		prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)
		prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()

		oTypePropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["oligo type"], prop_Category_Name_ID_Map["General Properties"])
		
		oligoType = self.findSimplePropertyValue(oligoID, oTypePropID)
		
		return oligoType
		
	
	def isSenseOligo(self, oligoID):
		
		oType = self.findOligoType(oligoID)
		
		if oType:
			return oType.lower() == "sense"
		else:
			return False
	
	
	def isAntiSenseOligo(self, oligoID):
		oType = self.findOligoType(oligoID)
		
		if oType:
			return oType.lower() == "antisense"
		else:
			return False
	
