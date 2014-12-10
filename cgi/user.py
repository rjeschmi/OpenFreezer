###################################################################################################
# This module represents inidividual users of OpenFreezer
#
# Written: May 25, 2007, by Marina Olhovsky
# Last modified: August 8, 2007
###################################################################################################
class User:

	__userID = 0
	__username = ""
	__firstName = ""
	__lastName = ""
	__description = ""		# usually a full name - 'Firstname LastName' - e.g. John Smith
	__lab = None			# a Laboratory instance
	__category = ""			# plain text string: Admin, Creator, Writer, Reader
	__email = ""
	__passwd = ""
	__status = 'ACTIVE'
	__projects_readonly = []	# list of Project Class **INSTANCES** representing projects the user has READ-ONLY access to
	__projects_write = []		# list of Project Class **INSTANCES** representing projects the user has WRITE access to
	

	# Plain list of mutable User object attributes (details that can be changed)
	# userID is immutable; once assigned it cannot be changed; the rest of the information can be modified in the database
	__userProperties = ["username", "firstname", "lastname", "description", "labID", "category", "email", "password", "status"]

	##################################
	# Constructors
	##################################

	# Complete initialization of all object variables
	def __init__(self, uid, uname = "", fName = "", lName ="", descr="", lab = None, category = "", email = "", pw = "", projects_read = [], projects_write = []):
		self.__userID = uid
		self.__username = uname
		self.__firstName = fName
		self.__lastName = lName
		self.__description = descr
		self.__lab = lab
		self.__category = category
		self.__email = email
		self.__passwd = pw
		self.__projects_readonly = projects_read
		self.__projects_write = projects_write
		self.__status = 'ACTIVE'


	############################
	# Assignment methods
	############################

	def setUserID(self, uid):
		self.__userID = uid


	def setUsername(self, uname):
		self.__username = uname


	def setFirstName(self, fName):
		self.__firstName = fName


	def setLastName(self, lName):
		self.__lastName = lName

	
	def setDescription(self, descr):
		self.__description = descr
	
	def setLab(self, lab):
		self.__lab = lab


	def setCategory(self, category):
		self.__category = category


	def setEmail(self, email):
		self.__email = email
		
		
	def setPasswd(self, pw):
		self.__passwd = pw

		
	def setReadProjects(self, readProjects):
		self.__projects_readonly = readProjects


	def setWriteProjects(self, writeProjects):
		self.__projects_write = writeProjects


	def setStatus(self, status):
		self.__status = status
		
		
	def addProject(self, project, projectType):
		if projectType == 'read':
			self.__projects_readonly.append(project)
		else:
			self.__projects_write.append(project)
			

	############################
	# Access methods
	############################

	def getUserID(self):
		return int(self.__userID)
	

	def getUsername(self):
		return self.__username
	
	
	def getFirstName(self):
		return self.__firstName
		
		
	def getLastName(self):
		return self.__lastName


	def getDescription(self):
		return self.__description
		

	# Utility function, return "Firstname Lastname" if 'description' is unavailable
	def getFullName(self):
	
		if self.__description == "":
			return self.getFirstName() + " " + self.getLastName()
		else:
			return self.__description

	def getLab(self):
		return self.__lab
		
		
	def getCategory(self):
		return self.__category


	def getEmail(self):
		return self.__email


	def getPassword(self):
		return self.__passwd


	def getStatus(self):
		return self.__status
		
			
	def getReadProjects(self):
		return self.__projects_readonly


	def getWriteProjects(self):
		return self.__projects_write
		
		
	# A static (class) method, called to fetch a list of general User attributes
	@classmethod
	def getUserProperties(self):
		return self.__userProperties
