##############################################################
# This module represents packets, or projects, in OpenFreezer 
#
# Written: May 25, 2007, by Marina Olhovsky
# Last modified: July 16, 2007
##############################################################
class Packet:

	__number = 0
	__name = ""
	__description = ""
	__owner = None			# a User instance
	__isEmpty = True		# are there reagents in this project?
	__isPrivate = False		# private or public?
	
	# added June 20/07 - lists of User instances
	__readers = []
	__writers = []
	
	
	# Constructor
	def __init__(self, num = 0, name = "", descr = "", owner = "", isPrivate = False, readers=[], writers=[]):
		self.__number = int(num)
		self.__name = name
		self.__description = descr
		self.__owner = owner
		self.__isEmpty = True		# make empty by default, can change later
		self.__isPrivate = isPrivate
		self.__readers = readers
		self.__writers = writers
		
	
	############################
	# Assignment methods
	############################

	def setNumber(self, num):
		self.__number = num
		
		
	def setName(self, name):
		self.__name = name
		
		
	def setDescription(self, descr):
		self.__description = descr
		
		
	def setOwner(self, owner):
		self.__owner = owner


	def setReaders(self, readers):
		self.__readers = readers
		
		
	def setWriters(self, writers):
		self.__writers = writers


	# isPriv: Boolean, either True or False
	def setPrivate(self, isPriv):
		self.__isPrivate = privateOrPublic


	############################
	# Access methods
	############################

	def getNumber(self):
		return self.__number
		
		
	def getName(self):
		return self.__name
		
		
	def getDescription(self):
		return self.__description
		
		
	def getOwner(self):
		return self.__owner

	def getReaders(self):
		return self.__readers
		
	def getWriters(self):
		return self.__writers


	################################
	# Various functions
	################################
	
	# Add 'member' to the list of readers for this project
	# 'member': a User instance
	def addReader(self, member):
		self.__readers.append(member)
	
	
	# Add 'member' as a writer to this project
	# member: a User instance
	def addWriter(self, member):
		self.__writers.append(member)
		
		
	# Add 'member' to this project in the given category
	# member: User
	# category: String, one of 'reader' or 'writer'
	def addMember(self, member, category):
		
		if category == 'reader':
			self.addReader(member)
			
		elif category == 'writer':
			self.addWriter(member)

	def isEmpty(self):
		return len(self.__writers) == 0 and len(self.__readers) == 0
		
		
	def isPrivate(self):
		return self.__isPrivate
