############################################################################################
# This module serves as an intermediate layer between the database and User module objects
#
# Written: May 25, 2007, by Marina Olhovsky
# Last modified: March 26, 2008
############################################################################################

import MySQLdb
import utils

from exception import *

from laboratory import Laboratory
from user import User

from general_handler import GeneralHandler
from mapper import UserCategoryMapper

class UserHandler(GeneralHandler):

	def __init__(self, db, cursor):
		super(UserHandler, self).__init__(db, cursor)

	
	# Fetch all the details for the user identified by uid and wrap them in a User object
	# Return: User instance
	def getUserByID(self, uid):

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
	
		db = self.db			# make local copies of global variables for easy access
		cursor = self.cursor
		
		newUser = None
		
		# ATT'N: Removed 'user status=ACTIVE' clause, since this function may be called to retrieve a project owner, who may be gone, but his/her packet is still active

		#print "SELECT u.username, u.firstname, u.lastname, u.description, c.category, u.labID, l.lab_name, u.email, u.password FROM Users_tbl u, LabInfo_tbl l, UserCategories_tbl c WHERE u.userID=" + `uid` + " AND u.labID=l.labID AND c.categoryID=u.category AND l.status='ACTIVE'"
	
		cursor.execute("SELECT u.username, u.firstname, u.lastname, u.description, c.category, u.labID, l.lab_name, u.email, u.password FROM Users_tbl u, LabInfo_tbl l, UserCategories_tbl c WHERE u.userID=" + `uid` + " AND u.labID=l.labID AND c.categoryID=u.category AND l.status='ACTIVE'")
		
		result = cursor.fetchone()
		
		if result:
			username = result[0]
			firstname = result[1]
			lastname = result[2]
			description = result[3]
			category = result[4]
			labID = int(result[5])
			labname = result[6]
			email = result[7]
			password = result[8]
			
			newLab = Laboratory(labID, labname)
			newUser = User(uid, username, firstname, lastname, description, newLab, category, email, password)
		
		return newUser

	
	# Fetch all the details for the user based on the user's full name - maps to 'description' column
	# Return: User instance
	def getUserByDescription(self, descr):
	
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT u.userID, u.username, u.firstname, u.lastname, c.category, u.labID, l.lab_name, u.email, u.password FROM Users_tbl u, LabInfo_tbl l, UserCategories_tbl c WHERE u.description=" + `descr` + " AND u.labID=l.labID AND c.categoryID=u.category AND l.status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			uid = int(result[0])
			username = result[1]
			firstname = result[2]
			lastname = result[3]
			category = result[4]
			labID = int(result[5])
			labname = result[6]
			email = result[7]
			password = result[8]
			
			newLab = Laboratory(labID, labname)
			newUser = User(uid, username, firstname, lastname, descr, newLab, category, email, password)
			
			return newUser
		
		else:
			# Nov. 15/07: Retry, just select userID and category and labID - other attributes may be omitted - for ACTIVE users only
			cursor.execute("SELECT u.userID, c.category FROM Users_tbl u, UserCategories_tbl c WHERE u.description=" + `descr` + " AND c.categoryID=u.category AND u.status='ACTIVE'")
			result = cursor.fetchone()
			
			if result:
				uid = int(result[0])
				category = result[1]
				newUser = User(uid, "", "", "", descr, None, category, "", "")
				
				return newUser

	
	# Fetch userID of the user identified by username, REGARDLESS OF STATUS
	def findUserIDByUsername(self, username):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT userID FROM Users_tbl WHERE username=" + `username`)
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
			
		return 0
	
	
	# June 1, 2010
	def findUserIDByDescription(self, userdesc):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT userID FROM Users_tbl WHERE description=" + `userdesc`)
		result = cursor.fetchone()
		
		if result:
			return int(result[0])
			
		return 0


	## June 1, 2010
	#def findUserIDByEmail(self, email):
		
		#db = self.db
		#cursor = self.cursor
		
		#cursor.execute("SELECT userID FROM Users_tbl WHERE email=" + `email`)
		#result = cursor.fetchone()
		
		#if result:
			#return int(result[0])
			
		#return 0

	
	"""
	# Perform a match against a portion of the user's first name, last name or full name
	# Used in user search page
	def findUsersByKeyword(self, keyword):
	
		db = self.db
		cursor = self.cursor
		
		users = []	# list of search results, in case multiple users match keyword
			
		cursor.execute("SELECT userID FROM Users_tbl WHERE firstName LIKE '%" + keyword + "%' OR lastName LIKE '%" + keyword + "%' OR description LIKE '%" + keyword + "%'")
		results = cursor.fetchall()
		
		if results:
			for result in results:
				userID = int(result[0])
				users.append(userID)
				
		return users
	"""
		
	
	# Insert a new entry into the User table
	# Column values: function arguments
	# Return: the ID of the newly inserted User table entry
	#
	# Error checking: Usernames must be unique; hence, a MySQL duplicate entry error is raised when a username is entered on the creation page that already exists in Users_tbl.  This scenario may occur if an admin wishes to restore a DEP user.  Thus, need to check for status, and if the new username already exists in the table with DEP status, reactivate status and overwrite entry with new column values
	def insertUser(self, username, firstname, lastname, description, category, email, password, labID):

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		db = self.db
		cursor = self.cursor
		
		if not self.existsUsername(username, 'ACTIVE') and not self.existsUsername(username, 'DEP'):
			cursor.execute("INSERT INTO Users_tbl(username, firstname, lastname, description, category, email, password, labID) VALUES(" + `username` + ", " + `firstname` + ", " + `lastname` + ", " + `description` + ", " + `category` + ", " + `email` + ", MD5(" + `password` + "), " + `labID` + ")")
			userID = int(db.insert_id())
			return userID
		
		#elif not self.existsUsername(username, 'ACTIVE') and self.existsUsername(username, 'DEP'):
		#	raise DeletedUserException("The username provided exists in the system but is inactive")
					
		else:
			raise DuplicateUsernameException("The username provided already exists in the system")
		
		
	# Check if a username exists in the database
	# 'status' is one of 'ACTIVE' or 'DEP'
	def existsUsername(self, username, status):
		
		db = self.db
		cursor = self.cursor
	
		cursor.execute("SELECT userID FROM Users_tbl WHERE username=" + `username` + " AND status=" + `status`)
		result = cursor.fetchone()
		
		if result:
			return True
		else:
			return False
		
		
	# Verify that a user identified 'userID' exists and is active in the system
	def existsUser(self, userID):
		
		db = self.db
		cursor = self.cursor
	
		cursor.execute("SELECT * FROM Users_tbl WHERE userID=" + `userID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return True
		else:
			return False
			
		
	# Revoke OpenFreezer access for the user identified by uid by setting the appropriate entry in Users_tbl to status='DEP'
	def deleteUser(self, uid):
	
		db = self.db
		cursor = self.cursor
	
		cursor.execute("UPDATE Users_tbl SET status='DEP' WHERE userID=" + `uid`)		
	
	"""
	# Reactivate deleted user - likely during User creation
	# Change status to ACTIVE and overwrite old attributes
	def restoreUser(self, username):
	
		db = self.db
		cursor = self.cursor
	"""		
			

	# Find the username of the user identified by uid
	def findUsername(self, uid):
		
		db = self.db			# make local copies of global variables for easy access
		cursor = self.cursor
		
		username = ""
		
		cursor.execute("SELECT username FROM Users_tbl WHERE userID=" + `uid` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			username = result[0]
			
		return username
			

	# Find the first name of the user identified by uid
	def findFirstname(self, uid):
		
		db = self.db			# make local copies of global variables for easy access
		cursor = self.cursor
		
		firstname = ""

		cursor.execute("SELECT firstname FROM Users_tbl WHERE userID=" + `uid` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			firstname = result[0]
			
		return firstname


	# Find the last name of the user identified by uid
	def findLastname(self, uid):
		
		db = self.db			# make local copies of global variables for easy access
		cursor = self.cursor
		
		lastname = ""
		
		cursor.execute("SELECT lastname FROM Users_tbl WHERE userID=" + `uid` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			lastname = result[0]
			
		return lastname


	# Find user's full name
	def findDescription(self, uid):

		db = self.db
		cursor = self.cursor
		
		description = ""
		
		cursor.execute("SELECT description FROM Users_tbl WHERE userID=" + `uid` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			description = result[0]
			
		return description

	
	# Find the category of the user identified by uid
	def findCategory(self, uid):
		
		db = self.db			# make local copies of global variables for easy access
		cursor = self.cursor
		
		category = ""
		
		cursor.execute("SELECT c.category FROM Users_tbl u, UserCategories_tbl c WHERE u.userID=" + `uid` + " AND u.category=c.categoryID AND u.status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			category = result[0]
			
		return category


	# Find the lab ID of the user identified by uid
	def findLabID(self, uid):
		
		db = self.db
		cursor = self.cursor
		
		labID = 0

		cursor.execute("SELECT labID FROM Users_tbl WHERE userID=" + `uid` + " AND status='ACTIVE'")
		result = cursor.fetchone()	# a member can only belong to one lab

		if result:
			labID = int(result[0])

		return labID


	def findStatus(self, uid):

		db = self.db
		cursor = self.cursor		
		
		cursor.execute("SELECT status FROM Users_tbl WHERE userID=" + `uid`)
		result = cursor.fetchone()

		if result:
			return result[0]
			
		return None


	# March 26/08
	def findEmail(self, uid):
		
		db = self.db
		cursor = self.cursor		
		
		cursor.execute("SELECT email FROM Users_tbl WHERE userID=" + `uid`)
		result = cursor.fetchone()

		if result:
			return result[0]
			
		return None
	

	# find a list of all properties and return them in the form of a dictionary
	def findAllUserProperties(self, uid):
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
	
		db = self.db
		cursor = self.cursor
		
		properties = {}
		
		properties["username"] = self.findUsername(uid)
		properties["firstName"] = self.findFirstname(uid)
		properties["lastName"] = self.findLastname(uid)
		properties["labID"] = self.findLabID(uid)
		properties["category"] = self.findCategory(uid)
		properties["status"] = self.findStatus(uid)
		
		return properties


	# Fetch the list of all users who belong to 'category'
	# Input: category = one of 4 possible entries in UserCategories_tbl: Admin, Creator, Writer, Reader
	# oper - SQL comparison operator; by default it's '=', but could be '>=', '<' or '>' - depending on whether calling function needs exact category match (e.g. find all Writers or those who have **at least** writing privileges)
	# Optional: may restrict search to members in a specific lab
	# Return: list of User instances
	def findAllMembersInCategory(self, category, active, oper = '=', labID = 0):
		
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		ucMapper = UserCategoryMapper(db, cursor)		# for category name/id mapping
		category_Name_ID_Map = ucMapper.mapCategoryNameToID()

		members = []	# list of User **objects**
		
		# When we include 'status=ACTIVE' restriction in the query, a DEP owner of an active project is not shown in project view.  However, we should not add DEP members to a project.
		# Therefore, the calling function should specify whether it wants to restrict query by status.
		# If 'active' parameter is True, add 'status=ACTIVE' clause
		# In any case, **remember to fill in 'category' column for DEP users before the launch**
		
		if labID == 0:
			if active:
				cursor.execute("SELECT userID, firstname, lastname, description FROM Users_tbl u, UserCategories_tbl c WHERE c.categoryID " + oper + " " + `category_Name_ID_Map[category]` + " AND c.categoryID=u.category AND u.firstname <> '' AND u.lastname <> '' AND u.description <>'' AND u.status='ACTIVE'")
			else:
				cursor.execute("SELECT userID, firstname, lastname, description FROM Users_tbl u, UserCategories_tbl c WHERE c.categoryID " + oper + " " + `category_Name_ID_Map[category]` + " AND c.categoryID=u.category AND u.firstname <> '' AND u.lastname <> '' AND u.description <>''")
		else:
			if active:
				cursor.execute("SELECT u.userID, u.firstname, u.lastname, u.description, l.lab_name FROM Users_tbl u, UserCategories_tbl c, LabInfo_tbl l WHERE c.categoryID " + oper + " " + `category_Name_ID_Map[category]` + " AND c.categoryID=u.category AND u.firstname <> '' AND u.lastname <> '' AND u.labID=" + `labID` + " AND l.labID=u.labID AND u.status='ACTIVE'")
			else:
				cursor.execute("SELECT u.userID, u.firstname, u.lastname, u.description, l.lab_name FROM Users_tbl u, UserCategories_tbl c, LabInfo_tbl l WHERE c.categoryID " + oper + " " + `category_Name_ID_Map[category]` + " AND c.categoryID=u.category AND u.firstname <> '' AND u.lastname <> '' AND u.description <>'' AND u.labID=" + `labID` + " AND l.labID=u.labID")
			
		results = cursor.fetchall()
		
		for result in results:
		
			userID = int(result[0])
			firstName = result[1]
			lastName = result[2]
			description = result[3]
			
			tmpLab = Laboratory(labID)
			
			if len(result) == 5:
				labName = result[4]
				tmpLab.setName(labName)
				
			# create a User object
			tmpUser = User(userID, "", firstName, lastName, description, tmpLab, category, "", "")
			members.append(tmpUser)
		

		return members

	# Change the value of an existing property (username, first name, last name, etc.)
	# propName corresponds to an actual column name in Users_tbl - hence, column name is determined dynamically
	# Do NOT check status='ACTIVE', as this procedure call is now used to restore DEP users
	def setUserPropertyValue(self, uid, propName, propValue):

		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		ucMapper = UserCategoryMapper(db, cursor)
		category_Name_ID_Map = ucMapper.mapCategoryNameToID()

		if propName == "password":
			cursor.execute("UPDATE Users_tbl SET " + propName + " = MD5(" + `propValue` + ") WHERE userID=" + `uid`)
			
		elif propName == "username":
			
			# Watch out again for duplicate username entries
			
			if not self.existsUsername(propValue, 'ACTIVE') and not self.existsUsername(propValue, 'DEP'):
				cursor.execute("UPDATE Users_tbl SET " + propName + " = " + `propValue` + " WHERE userID=" + `uid`)

			elif not self.existsUsername(propValue, 'ACTIVE') and self.existsUsername(propValue, 'DEP'):
				raise DeletedUserException("The username provided exists in the system but is inactive")

			else:
				raise DuplicateUsernameException("The username provided already exists in the system")
				
		elif propName == "category":
			cursor.execute("UPDATE Users_tbl SET " + propName + " = " + `category_Name_ID_Map[propValue]` + " WHERE userID=" + `uid`)

		else:
			cursor.execute("UPDATE Users_tbl SET " + propName + " = " + `propValue` + " WHERE userID=" + `uid`)

	
	# Update a list of user details (called at user modification view)
	# newProps - dictionary of (property (=column) name, property value) tuples
	def updateUserProperties(self, uid, newProps):
		
		db = self.db
		cursor = self.cursor
		
		# Fetch old user info, compare against new values, and either insert, change or delete
		userProps = User.getUserProperties()
		
		oldProps = self.findAllUserProperties(uid)
		
		for propName in userProps:
			
			if oldProps.has_key(propName) :
				oldPropVal = oldProps[propName]
			else:
				# property does not exist (wouldn't happen too often, since most properties are required to be filled in)
				oldPropVal = ""

			if newProps.has_key(propName):
				newPropVal = newProps[propName]
				
				# Use the same procedure call for all 3 operations, Insert, Update and Delete alike - since a user entry already exists in the database, all changes to it are performed through UPDATE query
				if (oldPropVal != newPropVal) or (oldPropVal == ""):
					self.setUserPropertyValue(uid, propName, newPropVal)


	# Find out what sections of the website a user is allowed to view based on his/her login privileges
	def getAllowedSections(self, cat):
		
		db = self.db
		cursor = self.cursor
		
		allowedSections = []		# contains names of website sections the user is allowed to view
		
		cursor.execute("SELECT section FROM UserPermission_tbl p, SecuredPages_tbl s WHERE p.categoryID>=" + `cat` + " AND p.pageID=s.pageID AND s.status='ACTIVE' AND p.status='ACTIVE'")
		results = cursor.fetchall()

		for result in results:
			section = result[0]
			allowedSections.append(section)
			
		return allowedSections
