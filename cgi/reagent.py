import utils

##################################################################
# This module represents objects of type Reagent in OpenFreezer
# Written by: Marina Olhovsky
# Last modified: March 17, 2008
##################################################################

#############################################################
# Reagent Class
#############################################################
# Top-level abstraction for this module's object hierarchy
class Reagent:
	
	# Instance variables
	__id = 0		# corresponds to groupID in Reagents_tbl
	__dnaSeq = None
	__properties = {}	# list of (property NAME, property value) tuples (database IDs have NOTHING to do with Reagent objects)
	__associations = {}	# list of (association type name, associated reagent) tuples

	__type = ""		# New April 12, 2009: Reagent will now serve as a template for addition of new reagent types
	
	__cloning_method = 0	# May 7/09
	
	# April 10/08: List of names of general (intro) properties, common to all reagent types
	# Updated May 6/08: added 'reagent source' and 'restriction on use'
	__generalProps = ["name", "status", "packet id", "verification", "verification comments", "description", "comments", "reagent source", "restrictions on use"]
	
	# May 14/09 - Features that are common to all
	__sequenceFeatures = ["tag", "promoter", "selectable marker", "polya", "origin of replication", "miscellaneous", "intron", "cleavage site", "restriction site", "transcription terminator", "5' cloning site", "3' cloning site", "cdna insert"]
	
	# Feature descriptors - only two for now
	__featureDescriptors = {"tag":"tag position", "promoter":"expression system"}
	
	__classifiers = ["species", "cloning method", "protocol", "developmental stage", "morphology"]
	
	__assoc_types = []
	
	# April 28/09
	__checkboxProps = []
	
	# added Oct. 23/08 - a list of actual SequenceFeature OBJECTS
	__allFeatures = []
	
	# Default constructor - initialize all variables to their default values
	def __init__(self):
		self.__id = 0
		self.__dnaSeq = None
		self.__properties = {}
		self.__type = ""
		self.__cloning_method = 0	# May 7/09

	# Overloaded constructor: Create a reagent of the given type with the given group ID
	def __init__(self, lims_id, rType=""):
		self.__id = lims_id
		self.__type = rType
	
	############################
	# Assignment methods
	############################
	
	# Assign a set of properties to the reagent
	# Parameters: propDict = list of (property NAME, property value) tuples
	def setProperties(self, propDict):
		self.__properties = propDict
	
	
	# Sets this reagent's sequence to the given value
	# Input: seq - an OBJECT of type DNASequence 
	def setDNASequence(self, seq):
		self.__dnaSeq = seq


	# Input: assocDict: contains (assoc type name, associated reagent human-readable OpenFreezer ID) tuples
	def setAssociations(self, assocDict):
		self.__associations = assocDict
		
		
	# oct. 23/08
	def setFeatures(self, features):
		self.__allFeatures = features

	# May 7/09
	def setCloningMethod(self, cm):
		self.__cloning_method = cm
		
	############################
	# Access methods
	############################
	
	# return properties dictionary
	def getProperties(self):
		return self.__properties
		
	# return associations dictionary
	def getAssociations(self):
		return self.__associations
	
	# get nucleotide sequence
	def getDNASequence(self):
		return self.__dnaSeq
	
	# April 10/08: Return general properties
	def getGeneralProperties(self):
		return self.__generalProps

	@classmethod
	def getReagentGeneralProperties(Reagent):
		return Reagent.__generalProps
	
	# April 27/09
	@classmethod
	def getSequenceFeatures(Reagent):
		return Reagent.__sequenceFeatures

	# April 28/09
	@classmethod
	def getFeatureDescriptors(Reagent):
		return Reagent.__featureDescriptors
	
	# April 28/09
	def getCheckboxProps(self):
		return self.__checkboxProps

	# oct. 23/08
	def getAllFeatures(self):
		return self.__allFeatures
	
	# Aug. 11/09
	def getType(self):
		return self.__type
	
	# Aug. 11/09
	def getAssociationTypes(self):
		return self.__assoc_types

	def getCloningMethod(self):
		return self.__cloning_method
	
	def getClassifiers(self):
		return self.__classifiers
	

#############################################################
# Vector Class
# Written March 30/07 by Marina
#############################################################
class Vector(Reagent):

	#############################################################
	# Vector-speecific attributes
	#############################################################

	# Simple properties
	__peptideSeq = None		# ProteinSequence object
	__restrictionSites = []
	
	# April 28/08: A simple string to describe the vector subtype; corresponds to 'nonrecomb', 'recomb', etc.
	# **NOT** THE SAME AS cloning_method!!!!!
	__subtype = ""
	
	#############################################################
	# Vector-specific parent-child associations
	# One or all of the following may be empty
	#############################################################
	
	__parentVector = None		# Vector object
	__parentInsert = None		# Insert object
	__parentInsertVector = None	# Vector object
	
	###################################################################################
	# April 21/08: Override general reagent properties with a list specific to Vector
	###################################################################################
	
	# removed May 6/08 - a better way would be to inherit __generalProps common to all (name, status, project, etc.) from Reagent, and define own set of generic properties specific to Vector (e.g. vector type)	
	#__generalProps = ["name", "status", "packet id", "verification", "verification comments", "description", "comments", "vector type", "reagent source", "restriction on use"]
	
	# added May 6/08
	__vectorProps = ["vector type"]

	__assocTypes = ["insert id", "vector parent id", "parent insert vector"]
	
	# August 23/07
	__cloning_method = ""	# For simplicity, an INTEGER, representing the reagent type; what is meant is 'recombination', 'non-recombination', 'novel', but will set to actual database values, such as '1'->'Insert', '2'->'Basic', '3'->'Loxp', etc.
	
	########################################################################################################
	# Special properties that have multiple values (represented graphically as checkboxes, hence the name)
	# For Vectors: antibiotic resistance => Changed to 'selectable marker' Feb. 27/08
	# June 11/08: Removed - no longer a checkbox property
	########################################################################################################
	#__checkboxProps = ["antibiotic resistance"]
	#__checkboxProps = ["selectable marker"]		# removed June 11/08
	__checkboxProps = []
	
	# April 16/08, updated Sept. 18/08 - added features from Karen's latest list
	__sequenceFeatures = ["tag", "promoter", "selectable marker", "polya", "origin of replication", "miscellaneous", "intron", "cleavage site", "restriction site", "transcription terminator", "5' cloning site", "3' cloning site", "cdna insert"]

	__featureDescriptors = {"tag":"tag position", "promoter":"expression system"}
	__singleValueFeatures = ["5' cloning site", "3' cloning site", "5' linker", "3' linker"]
	__classifiers = []
	
	#######################################################################################################################################
	# A dictionary containing association names and their corresponding values (e.g. 'parent vector'-->'V1', 'sense oligo'-->'O12', etc.)
	#######################################################################################################################################
	__associations = {}

	# will add children later; now only concerned with parents for modification
	
	# Overloaded constructor
	def __init__(self, lims_id):
	
		self.__id = lims_id
		
		# Vector-specific properties, not inherited from parent
		self.__peptideSeq = None
		self.__restrictionSites = []

		# Vector associations
		self.__parentVector = None
		self.__parentInsert = None
		self.__parentInsertVector = None
		
		self.__associations["insert id"] = self.__parentInsert
		self.__associations["parent vector id"] = self.__parentVector
		self.__associations["parent insert vector"] = self.__parentInsertVector


	###########################################################################################
	# Assignment methods - Set the value of the appropriate attibute to the function argument 
	###########################################################################################

	# Input: pSeq: PeptideSequence object
	def setProteinSequence(self, pSeq):
		self.__peptideSeq = pSeq

	# Input: pv: Vector instance
	def setParentVector(self, pv):
		self.__parentVector = pv
		
	# Input: piv: Vector instance
	def setParentInsertVector(self, piv):
		self.__parentInsertVector = piv

	# Input: pi: Insert instance
	def setInsert(self, pi):
		self.__parentInsert = pi

	# Input: pi: Insert instance
	def setSenseOligo(self, pi):
		self.__parentInsert = pi

	def setRestrictionSites(self, sites):
		self.__restrictionSites = sites

	def setCloningMethod(self, cm):
		self.__cloning_method = cm

	# Added April 28/08
	def setSubtype(self, subtype):
		self.__subtype = subtype
		
	def setClassifiers(self, classifiers):
		self.__classifiers = classifiers
	
	####################################################################################
	# Access methods - Return the value of each attribute
	####################################################################################

	# Return the translated protein sequence
	def getPeptideSequence(self):
		return self.__peptideSeq
	
	# Return the list of constant Insert properties
	def getCheckboxProps(self):
		return self.__checkboxProps

	def getParentVector(self):
		return self.__parentVector

	def getInsert(self):
		return self.__parentInsert

	def getParentInsertVector(self):
		return self.__parentInsertVector

	def getAssociationTypes(self):
		return self.__assocTypes

	def getRestrictionSites(self):
		return self.__restrictionSites

	def getCloningMethod(self):
		return self.__cloning_method
	
	# Redefined in each reagent type class
	def getType(self):
		return "Vector"

	# Modified June 30/08: Made class method
	@classmethod
	def getSequenceFeatures(Vector):
		return Vector.__sequenceFeatures

	# April 16/08
	# Returns a dictionary of ('feature', 'descriptor') tuples
	@classmethod
	def getFeatureDescriptors(Vector):
		return Vector.__featureDescriptors
	
	# Removed June 30/08 - temporarily, see if other modules are affected
	#def getSingleFeatures(self):
		#return self.__singleValueFeatures
		
	# Modified June 30/08: Made class method
	@classmethod
	def getSingleFeatures(Vector):
		return Vector.__singleValueFeatures
		
	# April 21/08: Override general reagent method
	def getGeneralProperties(self):
		return Vector.getVectorGeneralProperties()
	
	# Sept. 9/09
	def getClassifiers(self):
		return Vector.getVectorClassifiers()

	# May 5/08
	@classmethod
	def getVectorGeneralProperties(Vector):
		genProps = Reagent.getReagentGeneralProperties()
		vProps = Vector.__vectorProps
		
		for vp in vProps:
			genProps.append(vp)
			
		return genProps

	# Sept. 9/09
	@classmethod
	def getVectorClassifiers(Vector):
		#return Reagent.getClassifiers()
		return Vector.__classifiers

	# April 28/08
	def getSubtype(self):
		return self.__subtype
	
#############################################################
# Insert Class
#############################################################
# Represents reagents of type Insert in OpenFreezer
# Subclass of Reagent class
class Insert(Reagent):
	
	#############################################################
	# Insert-speecific attributes
	#############################################################

	# Simple properties
	__peptideSeq = None		# ProteinSequence object
	
	__insertParentVector = None
	__senseOligo = None		# Oligo object
	__antisenseOligo = None		# Oligo object
	
	
	# Special properties that have multiple values (in earlier versions were represented graphically as checkboxes, hence the name)
	# Currently only one for Inserts: alternate ID; its values include IMAGE, Kazusa, RIKEN, ADDGENE, HIP, etc.
	__checkboxProps = ["alternate id"]
	
	# New March 6/08: Features - can have multiple values for the same feature type
	# (placing in Insert subclass rather than Reagents since features are not shared by all reagent types - MAY BE SUBJECT TO CHANGE
	__singleValueFeatures = ["5' cloning site", "3' cloning site", "5' linker", "3' linker"]
	
	__sequenceFeatures = ["tag", "promoter", "selectable marker", "polya", "origin of replication", "miscellaneous", "intron", "cleavage site", "restriction site", "transcription terminator", "5' cloning site", "3' cloning site", "cdna insert"]
	
	__featureDescriptors = {"tag":"tag position", "promoter":"expression system"}
	
	# April 10/08: Annotations
	__annotations = ["accession number", "entrez gene id", "ensembl gene id", "official gene symbol", "alternate id"]
	
	# April 10/08: Classifiers
	__classifiers = ["type of insert", "open/closed", "species", "insert cloning method"]
	
	# August 23/07
	__cloning_method = ""		# An INTEGER that corresponds to ATypeID column values in AssocType_tbl
	
	#############################################################
	# List of parent/child associations
	#############################################################
	
	# Insert parents are reagents that served in the preparation of this Insert - an Vector, referred to as 'Insert Parent Vector ID', and two Oligos, Sense and Antisense, used as primers
	# Preserving the word "id" in 'insert parent vector id' for consistency with the original HTML, PHP and database
	__assocTypes = ["insert parent vector id", "sense oligo", "antisense oligo"]

	# A dictionary containing association names and their corresponding values (e.g. 'parent vector'-->'V1', 'sense oligo'-->'O12', etc.)
	__associations = {}

	# will add children later; now only concerned with parents for modification
	
	# Overloaded constructor
	def __init__(self, lims_id):
		self.__id = lims_id
		
		# Insert-specific properties, not inherited from parent
		self.__peptideSeq = None
		
		# Insert associations
		self.__senseOligo = None
		self.__antisenseOligo = None
		self.__insertParentVector = None
		
		self.__associations["insert parent vector id"] = self.__insertParentVector
		self.__associations["sense oligo"] = self.__senseOligo
		self.__associations["antisense oligo"] = self.__antisenseOligo		


	###########################################################################################
	# Assignment methods - Set the value of the appropriate attibute to the function argument 
	###########################################################################################

	# Input: pSeq: PeptideSequence object
	def setProteinSequence(self, pSeq):
		self.__peptideSeq = pSeq

	# Input: ipv: Vector instance
	def setInsertParentVector(self, ipv):
		self.__insertParentVector = ipv

	# Input: sense: Oligo instance
	def setSenseOligo(self, sense):
		self.__senseOligo = sense

	# Input: antisense: Oligo instance
	def setAntisenseOligo(self, antisense):
		self.__antisenseOligo = antisense

	def setCloningMethod(self, cm):
		self.__cloning_method = cm

	####################################################################################
	# Access methods - Return the value of each attribute
	####################################################################################

	# Return the translated protein sequence
	def getPeptideSequence(self):
		return self.__peptideSeq
	
	# Return the list of constant Insert properties
	def getCheckboxProps(self):
		return self.__checkboxProps

	def getInsertParentVector(self):
		return self.__insertParentVector

	def getSenseOligo(self):
		return self.__senseOligo

	def getAntisenseOligo(self):
		return self.__antisenseOligo

	def getAssociationTypes(self):
		return self.__assocTypes

	def getCloningMethod(self):
		return self.__cloning_method
	
	def getType(self):
		return "Insert"
	
	# March 6/08, updated April 27/09 - making class method
	@classmethod
	def getSequenceFeatures(Insert):
		return Insert.__sequenceFeatures
	
	# Returns a dictionary of ('feature', 'descriptor') tuples - May 14/09: Making class method
	@classmethod
	def getFeatureDescriptors(Insert):
		return Insert.__featureDescriptors
	
	# March 18/08
	def getSingleFeatures(self):
		return self.__singleValueFeatures
	
	# April 10/08
	def getAnnotations(self):
		return self.__annotations
	
	# April 10/08
	def getClassifiers(self):
		return self.__classifiers
	
	# May 13/08
	@classmethod
	def getInsertGeneralProperties(Insert):
		genProps = Reagent.getReagentGeneralProperties()
		iProps = Insert.__annotations + Insert.__classifiers + Insert.__checkboxProps
		
		for ip in iProps:
			genProps.append(ip)
			
		return genProps


#############################################################
# Oligo Class
#############################################################
# Represents reagents of type Oligo in OpenFreezer
# Subclass of Reagent class
class Oligo(Reagent):
	
	#############################################################	
	# Instance variables
	#############################################################
	
	__sense = False		# Boolean value indicating whether this oligo is Sense or Antisense
	
	# Special Oligo properties
	__constProps = [""]
	
	__sequenceFeatures = []
	__featureDescriptors = {}
	__singleValueFeatures = []
	__annotations = []
	__classifiers = []
	
	__cloning_method = ""		# no AssocType_tbl entries for Oligos
	
	# Zero-argument constructor (need to rewrite for elegant inheritance)
	def __init__(self, lims_id=0, sense=False):
		self.__id = lims_id
		self.__dnaSeq = None
		self.__properties = {}	# list of (property ID, property value) tuples
		self.__sense = sense
		
	def isSense(self):
		return self.__sense
		
	def setSenseValue(self, sense):
		self.__sense = sense

	def setCloningMethod(self, cm):
		self.__cloning_method = cm

	# There are no checkbox properties for Oligos, but need to add function definition for consistency and have it return null
	def getCheckboxProps(self):
		return None
	
	def getType(self):
		return "Oligo"
	
	@classmethod
	def getSequenceFeatures(Oligo):
		return Oligo.__sequenceFeatures
	
	# Returns a dictionary of ('feature', 'descriptor') tuples
	def getFeatureDescriptors(self):
		return self.__featureDescriptors
	
	# March 18/08
	def getSingleFeatures(self):
		return self.__singleValueFeatures
	
	# April 10/08
	def getAnnotations(self):
		return self.__annotations
	
	# April 10/08
	def getClassifiers(self):
		return self.__classifiers
	
	def getCloningMethod(self):
		return self.__cloning_method
	
	# the rest is inherited from parent class


#############################################################
# CellLine Class
#############################################################
class CellLine(Reagent):

	# Cell-Line specific properties
	__stable = False;			# boolean indicating whether the cell line is parent or stable

	# Cell Line associations: Parent Vector and Parent Cell Line (applies to Stable Cell Lines only)
	__assocTypes = ["cell line parent vector id", "parent cell line id"]

	# A dictionary containing association names and their corresponding values (e.g. 'parent vector'-->'V1', 'sense oligo'-->'O12', etc.)
	__associations = {}
	
	__parentVector = None
	__parentCellLine = None

	# will add children later; now only concerned with parents for creation and modification

	# Checkbox properties: alternate id and resistance marker
	__checkboxProps = ["alternate id", "selectable marker"]

	__cloning_method = ""		# corresponds to 5 => 'Cell Line Stable'
	
	# Nov. 11/08 - empty lists for now
	#__sequenceFeatures = ["selectable marker"]	# removed Dec. 8/08
	__sequenceFeatures = []				# added Dec. 8/08
	__featureDescriptors = {}
	__singleValueFeatures = []
	__annotations = []
	__classifiers = []
	
	# Overloaded constructor
	def __init__(self, lims_id):
		self.__id = lims_id
	
		# CellLine-specific properties, not inherited from parent
		self.__peptideSeq = None
		
		# Associations
		self.__parentVector = None
		self.__parentCellLine = None

		self.__associations["vector id"] = self.__parentVector
		self.__associations["cell line id"] = self.__parentCellLine

	###########################################################################################
	# Assignment methods - Set the value of the appropriate attibute to the function argument 
	###########################################################################################

	# Input: pv: Vector instance
	def setParentVector(self, pv):
		self.__parentVector = pv

	# Input: cellLine: CellLine instance
	def setParentCellLine(self, cellLine):
		self.__parentCellLine = cellLine

	def setCloningMethod(self, cm):
		self.__cloning_method = cm
		
	####################################################################################
	# Access methods - Return the value of each attribute
	####################################################################################
	
	# Return the list of constant Insert properties
	def getCheckboxProps(self):
		return self.__checkboxProps

	def getParentVector(self):
		return self.__parentVector

	def getParentCellLine(self):
		return self.__parentCellLine

	def getAssociationTypes(self):
		return self.__assocTypes
	
	def getCloningMethod(self):
		return self.__cloning_method

	def getType(self):
		return "CellLine"
	
	@classmethod
	def getSequenceFeatures(CellLine):
		return CellLine.__sequenceFeatures
	
	# Returns a dictionary of ('feature', 'descriptor') tuples
	def getFeatureDescriptors(self):
		return self.__featureDescriptors
	
	# March 18/08
	def getSingleFeatures(self):
		return self.__singleValueFeatures
	
	# April 10/08
	def getAnnotations(self):
		return self.__annotations
	
	# April 10/08
	def getClassifiers(self):
		return self.__classifiers
	