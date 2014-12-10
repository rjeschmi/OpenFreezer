import math

##################################################################
# This module represents Sequence Feature objects
# Written by: Marina Olhovsky on March 12, 2008
# Last modified: July 11, 2008
##################################################################
class SequenceFeature:
	
	# Instance variables
	__fType = ""
	__fName = ""
	__fStart = 0
	__fEnd = 0
	__fDirection = 'forward'	# added March 17/08
	__fSize = 0			# added July 11/08
	
	# Formerly another reagent property that in the new design is used in conjunction with a feature to describe it
	# e.g. 'Tag Position' is now a descriptor for 'Tag Type', and 'Expression System' is the descriptor for 'Promoter'
	__fDescrType = ""		# type of the descriptor (tag position or expression system)
	__fDescrName = ""		# actual descriptor value ('N-terminus' or 'Bacteria')

	# Default constructor - initialize all variables to their default values
	def __init__(self):
		self.__fType = ""
		self.__fName = ""
		self.__fStart = 0
		self.__fEnd = 0
		self.__fSize = 0
		self.__fDescrType = ""
		self.__fDescrName = ""
		self.__fDirection = 'forward'
		

	# Overloaded constructor: Create a feature of the given type, with a given name, start and end positions
	def __init__(self, fType, fName="", fStart=0, fEnd=0, fDir='forward', fDescrType="", fDescrName=""):
		self.__fType = fType
		self.__fName = fName
		self.__fStart = fStart
		self.__fEnd = fEnd
		self.__fDirection = fDir
		self.__fDescrType = fDescrType
		self.__fDescrName = fDescrName
		self.__fSize = math.fabs(fEnd-fStart)
		

	################################################
	# Assignment methods
	################################################

	def setFeatureType(self, fType):
		self.__fType = fType

	def setFeatureName(self, fName):
		self.__fName = fName

	def setFeatureStartPos(self, fStart):
		self.__fStart = fStart

	def setFeatureEndPos(self, fEnd):
		self.__fEnd = fEnd
		
	def setFeatureDescrType(self, fDescrType):
		self.__fDescrType = fDescrType
		
	def setFeatureDescrName(self, fDescrName):
		self.__fDescrName = fDescrName
		
	def setFeatureDirection(self, fDir):
		self.__fDirection = fDir
		
	def setFeatureSize(self, fSize):
		self.__fSize = fSize

	###############################################
	# Access methods
	###############################################

	def getFeatureType(self):
		return self.__fType

	def getFeatureName(self):
		return self.__fName

	def getFeatureStartPos(self):
		return self.__fStart

	def getFeatureEndPos(self):
		return self.__fEnd
	
	def getFeatureDescrType(self):
		return self.__fDescrType
	
	def getFeatureDescrName(self):
		return self.__fDescrName
	
	def getFeatureDirection(self):
		return self.__fDirection
	
	def getFeatureSize(self):
		return self.__fSize