#################################################################################################
# This module represents laboratories that utilize OpenFreezer as their LIMS system
#
# Written: May 25, 2007, by Marina Olhovsky
# Last modified: July 5, 2007
#################################################################################################
class Laboratory:

	__id = 0
	__name = ""
	__description = ""
	__default_access_level = "Reader"
	__address = ""
	__labHead = ""
	__labCode = ""
	__members = []		# list of User instances
	
	
	##################################
	# Constructor
	##################################
	
	# Initialize all instance variables - some may be blank by default
	def __init__(self, lID=0, name="", descr="", accLev="", addr="", lab_head="", lab_code = "", members=[]):
		self.__id = lID
		self.__name = name
		self.__description = descr
		self.__default_access_level = accLev
		self.__address = addr
		self.__labHead = lab_head
		self.__labCode = lab_code
		self.__members = members


	############################
	# Assignment methods
	############################

	def setName(self, name):
		self.__name = name
		
		
	def setDescription(self, descr):
		self.__description = descr
		
		
	def setAddress(self, addr):
		self.__address = addr
				
	
	def setDefaultAccessLevel(self, accLev):
		self.__default_access_level = accLev
	
	def setLabHead(self, labHead):
		self.__labHead = labHead
		
	def setLabCode(self, labCode):
		self.__labCode = labCode
		
	def setMembers(self, members):
		self.__members = members
		

	############################
	# Access methods
	############################

	def getName(self):
		return self.__name
		
		
	def getDescription(self):
		return self.__description


	def getAddress(self):
		return self.__address


	def getID(self):
		return int(self.__id)
	
	
	def getDefaultAccessLevel(self):
		return self.__default_access_level
	
	
	def getLabHead(self):
		return self.__labHead
	
	
	def getLabCode(self):
		return self.__labCode
	
	
	def getMembers(self):
		return self.__members
