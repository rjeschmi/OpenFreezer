import MySQLdb
##################################################################################################################
# Module Mapper
# Creates hashtable associations between various table fields
#
# Written by: Marina Olhovsky
# Last modified: June 30, 2008
##################################################################################################################


##################################################################################################################
# Superclass (abstract)
##################################################################################################################
class Mapper(object):

	# Constructor
	def __init__(self, db, cursor):
	
		self.db = db
		self.cursor = cursor
		
		
##################################################################################################################
# ReagentPropertyMapper - Maps reagent property types, names and database IDs to each other 
##################################################################################################################
class ReagentPropertyMapper(Mapper):
	"Maps reagent property types, names and database IDs to each other"

	# Map ALL property names to their database IDs
	# Return: propMap: (propName, propID) tuples dictionary
	def mapPropNameID(self):
	
		db = self.db
		cursor = self.cursor
		
		propMap = {}
		
		cursor.execute("SELECT `propertyID`, `propertyName` FROM `ReagentPropType_tbl` WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			pID = int(result[0])
			pName = result[1]
			propMap[pName] = pID
			
		return propMap
		
		
	# Equal and opposite of mapPropNameID: Map ALL property **IDs** to their names
	# Return: propMap: (propID, propName) tuples dictionary
	def mapPropIDName(self):
	
		db = self.db
		cursor = self.cursor
		
		propMap = {}
		
		cursor.execute("SELECT `propertyID`, `propertyName` FROM `ReagentPropType_tbl` WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			pID = int(result[0])
			pName = result[1]
			propMap[pID] = pName
			
		return propMap
				
	
	# Map database property aliases to their property IDs
	# Return: (alias, propID) dictionary
	def mapPropAliasID(self):
	
		db = self.db
		cursor = self.cursor
		
		aliasMap = {}
		
		cursor.execute("SELECT `propertyAlias`, `propertyID` FROM `ReagentPropType_tbl` WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			alias = result[0]
			propID = int(result[1])
			aliasMap[alias] = propID
			
		return aliasMap


	# Map database property IDs to their aliases 
	# Return: (propID, alias) dictionary
	def mapPropIDAlias(self):
	
		db = self.db
		cursor = self.cursor
		
		aliasMap = {}
		
		cursor.execute("SELECT `propertyAlias`, `propertyID` FROM `ReagentPropType_tbl` WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			alias = result[0]
			propID = int(result[1])
			aliasMap[propID] = alias
			
		return aliasMap


	# Map property names to their database aliases
	# Return: (propName, alias) dictionary
	def mapPropNameAlias(self):

		db = self.db
		cursor = self.cursor
		
		aliasMap = {}
		
		cursor.execute("SELECT `propertyAlias`, `propertyName` FROM `ReagentPropType_tbl` WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			alias = result[0]
			propName = result[1]
			aliasMap[propName] = alias
			
		return aliasMap


	# Map aliases to full property names
	# Return: (alias, propName) dictionary
	def mapPropAliasName(self):
		
		db = self.db
		cursor = self.cursor
		
		aliasMap = {}
		
		cursor.execute("SELECT `propertyAlias`, `propertyName` FROM `ReagentPropType_tbl` WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			alias = result[0]
			propName = result[1]
			aliasMap[alias] = propName
			
		return aliasMap


	def mapPropNameDescription(self):
		
		db = self.db
		cursor = self.cursor
		
		descrMap = {}
		
		cursor.execute("SELECT propertyName, propertyDesc FROM ReagentPropType_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			propName = result[0]
			propDesc = result[1]
			
			descrMap[propName] = propDesc
			
		return descrMap


	def mapPropIDDescription(self):
		
		db = self.db
		cursor = self.cursor
		
		prop_ID_Desc_Map = {}
		
		cursor.execute("SELECT propertyID, propertyDesc FROM ReagentPropType_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			propID = int(result[0])
			propDesc = result[1]
			
			prop_ID_Desc_Map[propID] = propDesc
			
		return prop_ID_Desc_Map


	# April 20, 2009
	# Map aliases to property descriptions
	# Return: (alias, propDesc) dictionary
	def mapPropAliasDescription(self):
		
		db = self.db
		cursor = self.cursor
		
		aliasMap = {}
		
		cursor.execute("SELECT propertyAlias, propertyDesc FROM ReagentPropType_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			alias = result[0]
			propDesc = result[1]
			aliasMap[alias] = propDesc
			
		return aliasMap
		
		
	# April 20, 2009
	# Map property descriptions to aliases
	# Return: (propDesc, alias) dictionary
	def mapPropDescAlias(self):
		
		db = self.db
		cursor = self.cursor
		
		aliasMap = {}
		
		cursor.execute("SELECT propertyAlias, propertyDesc FROM ReagentPropType_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			propDesc = result[1]
			alias = result[0]
			aliasMap[propDesc] = alias
			
		return aliasMap
	
	# Oct. 27/09
	def mapPropDescName(self):
		
		db = self.db
		cursor = self.cursor
		
		nameMap = {}
		
		cursor.execute("SELECT propertyDesc, propertyName FROM ReagentPropType_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			propDesc = result[0]
			propName = result[1]
			nameMap[propDesc] = propName
			
		return nameMap
		
		
	# Oct. 29/09
	def mapPropDescID(self):

		db = self.db
		cursor = self.cursor
		
		nameMap = {}
		
		cursor.execute("SELECT propertyDesc, propertyID FROM ReagentPropType_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			propDesc = result[0]
			propID = int(result[1])
			nameMap[propDesc] = propID
			
		return nameMap
		
		
	# Added June 30/08: Map feature names to their colors for graphic representation
	def mapFeatureNameColor(self):
		db = self.db
		cursor = self.cursor
		
		featureColorMap = {}
		
		# Query updated Jan. 27/10 - don't rely on colour, select DNA SEQUENCE FEATURES
		prop_Category_Name_ID_Map = self.mapPropCategoryNameID()
		
		featureCategoryID = prop_Category_Name_ID_Map["DNA Sequence Features"]
		
		cursor.execute("SELECT p.propertyName, p.propertyColor FROM ReagentPropType_tbl p, ReagentPropertyCategories_tbl pc WHERE p.status='ACTIVE' AND pc.status='ACTIVE' AND p.propertyColor IS NOT NULL AND pc.categoryID=" + `featureCategoryID` + " AND p.propertyID=pc.propID")
		
		results = cursor.fetchall()
		
		for result in results:
			featureName = result[0]
			featureColor = result[1]
			
			featureColorMap[featureName] = featureColor
		
		return featureColorMap
		
	
	# April 24/09: Map property category names to their IDs
	def mapPropCategoryNameID(self):
		db = self.db
		cursor = self.cursor
		
		categoryMap = {}
		
		cursor.execute("SELECT propertyCategoryID, propertyCategoryName FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			categoryID = int(result[0])
			categoryName = result[1]
			
			categoryMap[categoryName] = categoryID
			
		return categoryMap
		

	# April 24/09: Map property category names to their aliases
	def mapPropCategoryNameAlias(self):
		db = self.db
		cursor = self.cursor
		
		categoryMap = {}
		
		cursor.execute("SELECT propertyCategoryName, propertyCategoryAlias FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			categoryName = result[0]
			categoryAlias = result[1]
			
			categoryMap[categoryName] = categoryAlias
			
		return categoryMap


	# April 24/09: Map property category IDs to their names
	def mapPropCategoryIDName(self):
		db = self.db
		cursor = self.cursor
		
		categoryMap = {}
		
		cursor.execute("SELECT propertyCategoryID, propertyCategoryName FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			categoryID = int(result[0])
			categoryName = result[1]
			
			categoryMap[categoryID] = categoryName
			
		return categoryMap
	
	
	# April 24/09: Map property category IDs to their aliases
	def mapPropCategoryIDAlias(self):
		db = self.db
		cursor = self.cursor
		
		categoryMap = {}
		
		cursor.execute("SELECT propertyCategoryID, propertyCategoryAlias FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			categoryID = int(result[0])
			categoryAlias = result[1]
			
			categoryMap[categoryID] = categoryAlias
			
		return categoryMap
		
	
	# April 24/09: Map property category aliases to their IDs
	def mapPropCategoryAliasID(self):
		db = self.db
		cursor = self.cursor
		
		categoryMap = {}
		
		cursor.execute("SELECT propertyCategoryID, propertyCategoryAlias FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			categoryID = int(result[0])
			categoryAlias = result[1]
			
			categoryMap[categoryAlias] = categoryID
			
		return categoryMap
			
	
	# April 24/09: Map property category aliases to their names
	def mapPropCategoryAliasName(self):
		db = self.db
		cursor = self.cursor
		
		categoryMap = {}
		
		cursor.execute("SELECT propertyCategoryName, propertyCategoryAlias FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			categoryName = result[0]
			categoryAlias = result[1]
			
			categoryMap[categoryAlias] = categoryName
			
		return categoryMap
		
		
	# July 28/09, update April 14, 2010
	def mapPropIDOrdering(self):
		
		#print "Content-type:text/html"
		#print
		
		db = self.db
		cursor = self.cursor
		
		propOrderingDict = {}
		
		prop_ID_Name_Map = self.mapPropIDName()
		
		for propID in prop_ID_Name_Map.keys():
			#print propID
			cursor.execute("SELECT ordering FROM ReagentPropType_tbl WHERE propertyID=" + `propID` + " AND status='ACTIVE'")
			result = cursor.fetchone()
			
			if result:
				ordering = int(result[0])
				propOrderingDict[propID] = ordering
				
		return propOrderingDict


	# Nov. 19/09: Equal and opposite - return a map of (order, id)
	# Just flip the dictionary returned by mapPropIDOrdering() function
	def mapPropOrderingID(self):
		db = self.db
		cursor = self.cursor
		
		propOrderingDict = {}
		
		prop_ID_Order_Map = self.mapPropIDOrdering()
		
		for propID in prop_ID_Order_Map.keys():
			pOrder = prop_ID_Order_Map[propID]
			propOrderingDict[pOrder] = propID

		return propOrderingDict


	# July 28/09
	def mapPropCategoryIDOrdering(self):
		db = self.db
		cursor = self.cursor
		
		propCategoryOrderingDict = {}
		
		propCategory_ID_Name_Map = self.mapPropCategoryIDName()
		
		for categoryID in propCategory_ID_Name_Map.keys():
			cursor.execute("SELECT ordering FROM ReagentPropTypeCategories_tbl WHERE propertyCategoryID=" + `categoryID` + " AND status='ACTIVE'")
			result = cursor.fetchone()
			
			if result:
				ordering = int(result[0])
				propCategoryOrderingDict[categoryID] = ordering
				
		return propCategoryOrderingDict


##################################################################################################################
# ReagentAssociationMapper - Creates a map of reagent ASSOCIATION types, names and IDs
##################################################################################################################
class ReagentAssociationMapper(Mapper):
		
	#################################################################################################################################################################
	# The functions below are analogous to same-name functions in ReagentPropertyMapper, except they deal with reagent ASSOCIATION types, IDs and properties
	#################################################################################################################################################################
	
	# Map ALL reagent association property names to their database IDs in **Assoc_Prop_tbl**
	# Return: propMap: (propName, propID) tuples dictionary
	def mapAssocNameID(self):
	
		db = self.db
		cursor = self.cursor
		
		propMap = {}
		
		cursor.execute("SELECT `APropertyID`, `APropName` FROM `Assoc_Prop_Type_tbl` WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			pID = int(result[0])
			pName = result[1]
			propMap[pName] = pID
			
		return propMap
		
		
	# Equal and opposite of mapPropNameID: Map ALL property **IDs** to their names
	# Return: propMap: (propID, propName) tuples dictionary
	def mapAssocIDName(self):
	
		db = self.db
		cursor = self.cursor
		
		propMap = {}
		
		cursor.execute("SELECT `APropertyID`, `APropName` FROM `Assoc_Prop_Type_tbl` WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			pID = int(result[0])
			pName = result[1]
			propMap[pID] = pName
			
		return propMap
			
	
	# Map database property aliases to their property IDs
	# Return: (alias, propID) dictionary
	def mapAssocAliasID(self):
	
		db = self.db
		cursor = self.cursor
		
		aliasMap = {}
		
		cursor.execute("SELECT `alias`, `APropertyID` FROM `Assoc_Prop_Type_tbl` WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			alias = result[0]
			propID = int(result[1])
			aliasMap[alias] = propID
			
		return aliasMap


	# Map database property IDs to their aliases 
	# Return: (propID, alias) dictionary
	def mapAssocIDAlias(self):
	
		db = self.db
		cursor = self.cursor
		
		aliasMap = {}
		
		cursor.execute("SELECT `alias`, `APropertyID` FROM `Assoc_Prop_Type_tbl` WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			alias = result[0]
			propID = int(result[1])
			aliasMap[propID] = alias
			
		return aliasMap


	# Map property names to their database aliases
	# Return: (propName, alias) dictionary
	def mapAssocNameAlias(self):

		db = self.db
		cursor = self.cursor
		
		aliasMap = {}
		
		cursor.execute("SELECT `alias`, `APropName` FROM `Assoc_Prop_Type_tbl` WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			alias = result[0]
			propName = result[1]
			aliasMap[propName] = alias
			
		return aliasMap


	# Map aliases to full property names
	# Return: (alias, propName) dictionary
	def mapAssocAliasName(self):
		
		db = self.db
		cursor = self.cursor
		
		aliasMap = {}
		
		cursor.execute("SELECT `alias`, `APropName` FROM `Assoc_Prop_Type_tbl` WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			alias = result[0]
			propName = result[1]
			aliasMap[alias] = propName
			
		return aliasMap
		
		
	def mapAssocNameDescription(self):
	
		db = self.db
		cursor = self.cursor
		
		propMap = {}
		
		cursor.execute("SELECT APropName, description FROM Assoc_Prop_Type_tbl WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			pName = result[0]
			pDescr = result[1]
			
			propMap[pName] = pDescr
			
		return propMap
		
	
	# Note Feb. 11/10: remember, this function returns a map of (parent type ID, assocPropID) tuples, not the other way around as the name suggests
	def mapAssocIDParentType(self):
	
		db = self.db
		cursor = self.cursor
		
		propMap = {}
		
		cursor.execute("SELECT APropertyID, assocTypeID FROM Assoc_Prop_Type_tbl WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			assocPropID = int(result[0])
			pTypeID = int(result[1])
			
			propMap[pTypeID] = assocPropID
			
		return propMap
	
	
	# Feb. 11/10: equal and opposite of mapAssocIDParentType, returns a dictionary of (assocPropID, parent type ID) tuples
	def mapParentTypeAssocID(self):
	
		db = self.db
		cursor = self.cursor
		
		propMap = {}
		
		cursor.execute("SELECT APropertyID, assocTypeID FROM Assoc_Prop_Type_tbl WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			assocPropID = int(result[0])
			pTypeID = int(result[1])
			
			propMap[assocPropID] = pTypeID
			
		return propMap
	
	
	
	# Creates a map between reagent association type name and ID, i.e. links 'ATypeID' and 'association' columns of AssocType_tbl
	# e.g. '6' --> "Insert Parent Vector"
	def mapAssocTypeIDName(self):
		
		db = self.db
		cursor = self.cursor
		
		assocTypeMap = {}
		
		cursor.execute("SELECT ATypeID, association FROM AssocType_tbl WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			assocTypeID = int(result[0])
			assocTypeName = result[1]
			
			assocTypeMap[assocTypeID] = assocTypeName
			
		return assocTypeMap
		
		
	# Returns: (assocTypeName, assocTypeID) dictionary
	def mapAssocTypeNameID(self):
		
		db = self.db
		cursor = self.cursor
		
		assocTypeMap = {}
		
		cursor.execute("SELECT ATypeID, association FROM AssocType_tbl WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			assocTypeID = int(result[0])
			assocTypeName = result[1]
			
			assocTypeMap[assocTypeName] = assocTypeID
			
		return assocTypeMap
			
	
	# Map APropName to ATypeID	
	# Returns a dictionary containing (Assoc_Prop_Type_tbl.APropertyID, AssocType_tbl.ATypeID) tuples
	# This is needed when a new association needs to be created for a reagent, but its ATypeID is not given and needs to be determined based on APropName provided
	# E.g. if an Insert was created without any associations, and now user wants to assign oligos and IPV to this Insert, s/he passes APropertyID '5' ("sense oligo id") to association creation function
	# The resulting association will then be assigned an ATypeID of 4 (Insert Oligos)
	# These associations, however, need to be explicitly hard-coded -- they cannot be determined automatically
	# This matching is only necessary for Inserts and Cell Lines - not needed for Vectors and Oligos
	def mapAssocPropType(self):
		
		db = self.db
		cursor = self.cursor
		
		# (association, ATypeID) from AssocType_tbl
		assocTypeMap = self.mapAssocTypeNameID()
		
		# (APropName, APropertyID) from Assoc_Prop_Type_tbl
		assocPropMap = self.mapAssocNameID()
		
		# Resulting (APropertyID, ATypeID) map
		# The keys are unique and can be traced back to only one reagent type
		assocPropTypeMap = {}
		
		# Inserts
		assocPropTypeMap[assocPropMap["insert parent vector id"]] = assocTypeMap["Insert Parent Vector"]
		assocPropTypeMap[assocPropMap["sense oligo"]] = assocTypeMap["Insert Oligos"]
		assocPropTypeMap[assocPropMap["antisense oligo"]] = assocTypeMap["Insert Oligos"]
		
		# Cell Lines
		assocPropTypeMap[assocPropMap["cell line parent vector id"]] = assocTypeMap["CellLine Stable"]
		assocPropTypeMap[assocPropMap["parent cell line id"]] = assocTypeMap["CellLine Stable"]

		return assocPropTypeMap
		

#########################################################################################################
# Handler mapping between reagent type id, prefix and name
#########################################################################################################
class ReagentTypeMapper(Mapper):
	"Maps associations between reagent types, prefixes and internal database IDs"
	
	# Maps reagent type ID to its prefix {1:V, 2:I, 3:O, 4:C}
	def mapTypeIDPrefix(self):
		
		db = self.db
		cursor = self.cursor
		
		typeMap = {}
		
		cursor.execute("SELECT reagentTypeID, reagent_prefix FROM ReagentType_tbl WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			rTypeID = int(result[0])
			prefix = result[1]
			typeMap[rTypeID] = prefix
		
		return typeMap
		
		
	# Conversely, maps reagent prefix to its ID
	def mapTypePrefixID(self):
		
		db = self.db
		cursor = self.cursor
		
		typeMap = {}
		
		cursor.execute("SELECT reagentTypeID, reagent_prefix FROM ReagentType_tbl WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			rTypeID = int(result[0])
			prefix = result[1]
			typeMap[prefix] = rTypeID
		
		return typeMap
			
	
	# Maps reagent type ID to its name (1:Vector, 2:Insert, 3:Oligo, 4:Cell Line)
	def mapTypeIDName(self):
	
		db = self.db
		cursor = self.cursor
		
		typeMap = {}
		
		cursor.execute("SELECT reagentTypeID, reagentTypeName FROM ReagentType_tbl WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			rTypeID = int(result[0])
			rTypeName = result[1]
			typeMap[rTypeID] = rTypeName
		
		return typeMap
		
	
	# Maps reagent type name to its ID
	def mapTypeNameID(self):

		db = self.db
		cursor = self.cursor
		
		typeMap = {}
		
		cursor.execute("SELECT reagentTypeID, reagentTypeName FROM ReagentType_tbl WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			rTypeID = int(result[0])
			rTypeName = result[1]
			typeMap[rTypeName] = rTypeID
		
		return typeMap
		
	
	# Maps reagent type names to prefixes: {Vector:'V', Insert:'I', Oligo:'O', Cell Line:'C'}
	def mapTypeNamePrefix(self):
		
		db = self.db
		cursor = self.cursor
		
		typeMap = {}
		
		cursor.execute("SELECT reagentTypeName, reagent_prefix FROM ReagentType_tbl WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			rTypeName = result[0]
			prefix = result[1]
			typeMap[rTypeName] = prefix
		
		return typeMap
		
		
	# Maps reagent type prefixes to names
	def mapTypePrefixName(self):
		
		db = self.db
		cursor = self.cursor
		
		typeMap = {}
		
		cursor.execute("SELECT reagent_prefix, reagentTypeName FROM ReagentType_tbl WHERE `status`='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			rTypeName = result[0]
			prefix = result[1]
			typeMap[prefix] = rTypeName
		
		return typeMap


	# March 26/07, Marina: Map the given reagent type to its association property aliases
	# E.g. if type is Vector, its assoc prop aliases are insert_id, parent_vector_id, insert_parent_vector_id
	def mapTypeAssocAlias(self, rTypeID):
	
		db = self.db
		cursor = self.cursor
		
		typeAssocAliasMap = {}
		
		cursor.execute("SELECT alias FROM Assoc_Prop_Type_tbl WHERE reagentTypeID=" + `rTypeID` + " AND status='ACTIVE'")
		results = cursor.fetchall()

		for result in results:
			typeAssocAliasMap[rTypeID] = result[0]

		return typeAssocAliasMap


#########################################################################################################
# User Category Mapper
# Maps associations between user categories (access privileges) - their names and internal IDs
#########################################################################################################
class UserCategoryMapper(Mapper):

	# Returns (categoryID, categoryName) dictionary
	# E.g. (4, 'Guest')
	def mapCategoryIDToName(self):
		
		db = self.db
		cursor = self.cursor
		
		categoryIDNameMap = {}
		
		cursor.execute("SELECT categoryID, category FROM UserCategories_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			catID = int(result[0])
			catName = result[1]
		
			categoryIDNameMap[catID] = catName
			
		return categoryIDNameMap


	# Returns (category, categoryID) map
	# E.g. ('Admin', 1)
	def mapCategoryNameToID(self):
		
		db = self.db
		cursor = self.cursor
		
		categoryNameIDMap = {}
		
		cursor.execute("SELECT category, categoryID FROM UserCategories_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			catName = result[0]
			catID = int(result[1])
			
			categoryNameIDMap[catName] = catID
			
		return categoryNameIDMap



#########################################################################################################
# January 13, 2010
# Maps associations between Prep properties
#########################################################################################################
class PrepPropertyMapper(Mapper):
	
	def mapPrepPropIDToName(self):
		
		db = self.db
		cursor = self.cursor
		
		prepPropMap = {}
		
		cursor.execute("SELECT elementTypeID, propertyName FROM PrepElemTypes_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			prepPropID = int(result[0])
			prepPropName = result[1]
			
			prepPropMap[prepPropID] = prepPropName
			
		return prepPropMap
		
		
	def mapPrepPropNameToID(self):
		
		db = self.db
		cursor = self.cursor
		
		prepPropMap = {}
		
		cursor.execute("SELECT elementTypeID, propertyName FROM PrepElemTypes_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			prepPropID = int(result[0])
			prepPropName = result[1]
			
			prepPropMap[prepPropName] = prepPropID
			
		return prepPropMap


	def mapPrepPropNameToDescr(self):
		
		db = self.db
		cursor = self.cursor
		
		prepPropMap = {}
		
		cursor.execute("SELECT propertyName, PrepElementDesc FROM PrepElemTypes_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			prepPropName = result[0]
			prepPropDescr = result[1]
			
			prepPropMap[prepPropName] = prepPropDescr
			
		return prepPropMap
		
# August 20, 2010
class SystemModuleMapper(Mapper):
	
	def mapPageNameLink(self):
		
		db = self.db
		cursor = self.cursor
		
		nameLinkMap = {}
		
		cursor.execute("SELECT section, baseURL FROM SecuredPages_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()
		
		for result in results:
			section = result[0]
			link = result[1]
			
			nameLinkMap[section] = link
			
		return nameLinkMap


# June 9, 2011
class ContainerTypeMapper(Mapper):

	def mapContainerTypeNameToID(self):
		db = self.db
		cursor = self.cursor
		
		contTypeNameIDMap = {}

		cursor.execute("SELECT contGroupID, contGroupName FROM ContainerGroup_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()

		for result in results:
			contGroupID = int(result[0])
			contGroupName = result[1]

			contTypeNameIDMap[contGroupName] = contGroupID

		return contTypeNameIDMap


	def mapContainerTypeIDToName(self):
		db = self.db
		cursor = self.cursor
		
		contTypeIDNameMap = {}

		cursor.execute("SELECT contGroupID, contGroupName FROM ContainerGroup_tbl WHERE status='ACTIVE'")
		results = cursor.fetchall()

		for result in results:
			contGroupID = int(result[0])
			contGroupName = result[1]

			contTypeIDNameMap[contGroupID] = contGroupName

		return contTypeIDNameMap