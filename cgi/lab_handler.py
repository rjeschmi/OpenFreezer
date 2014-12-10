#################################################################################################
# This module serves as an intermediate layer between the database and Laboratory module objects
#
# Written: May 25, 2007, by Marina Olhovsky
# Last modified: July 5, 2007
#################################################################################################

import MySQLdb
import utils

from general_handler import GeneralHandler
from comment_handler import CommentHandler
from user_handler import UserHandler

from laboratory import Laboratory
from user import User

from exception import *
from mapper import UserCategoryMapper

class LabHandler(GeneralHandler):
	
	def __init__(self, db, cursor):
		super(LabHandler, self).__init__(db, cursor)


	# Insert lab details into the database and return the new lab ID
	def insertLab(self, labName, labDescr, access, address, labHead, labCode):
		
		db = self.db
		cursor = self.cursor
		
		if not self.existsLabCode(0, labCode):
			
			cursor.execute("INSERT INTO LabInfo_tbl(lab_name, description, default_access_level, location, lab_head, labCode) VALUES(" + `labName` + ", " + `labDescr` + ", " + `address` + ", " + `access` + ", " + `labHead` + ", " + `labCode` + ")")
			labID = int(db.insert_id())
		
		else:
			raise DuplicateLabCodeException()
			
		return labID
		

	def existsLab(self, labName, labHead, labCode):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT * FROM LabInfo_tbl WHERE lab_name=" + `labName` + " AND lab_head=" + `labHead` + " AND labCode=" + `labCode` + " AND status='ACTIVE'")
		results = cursor.fetchall()
		
		if results:
			return True
		
		return False
		
	
	# labID: Either 0 or actual lab ID - differentiate between creation and modification; 0 means we're on creation view and just want to check if the code is taken.  Otherwise compare the labID and labcode together, make sure you're not assigning another lab's code to this lab during modification.
	def existsLabCode(self, labID, labCode):
		
		db = self.db
		cursor = self.cursor
		
		if labID == 0:
			cursor.execute("SELECT * FROM LabInfo_tbl WHERE labCode=" + `labCode` + " AND status='ACTIVE'")
		else:
			cursor.execute("SELECT * FROM LabInfo_tbl WHERE labID <> " + `labID` + " AND labCode=" + `labCode` + " AND status='ACTIVE'")
			
		results = cursor.fetchall()
		
		if results:
			return True
		
		return False


	# Fetch all information about the lab identified by labID, and return a Lab object
	def findLabByID(self, labID):
		
		db = self.db
		cursor = self.cursor
		
		ucMapper = UserCategoryMapper(db, cursor)
		category_ID_Name_Map = ucMapper.mapCategoryIDToName()
		
		cursor.execute("SELECT lab_name, description, default_access_level, location, lab_head, labCode FROM LabInfo_tbl WHERE labID=" + `labID` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			labName = result[0]
			labDescr = result[1]
			accessLevel = int(result[2])
			address = result[3]
			labHead = result[4]
			labCode = result[5].upper()

			newLab = Laboratory(labID, labName, labDescr, category_ID_Name_Map[accessLevel], address, labHead, labCode)

			return newLab

	# Check if a member exists in a given lab
	def existsMember(self, labID, memberID):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT * FROM Users_tbl WHERE labID=" + `labID` + " AND userID=" + `memberID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
				
		if result:
			return True
		else:
			return False
		
	
	# Fetch the list of all laboratories that use OpenFreezer
	# Edited July 3/07: Add 'accessLevel' parameter to restrict project access
	# accessLevel: String (e.g. Reader, Writer)
	# return a dictionary containing (labID, labName) tuples
	def findAllLabs(self, accessLevel="", oper="="):
		
		db = self.db
		cursor = self.cursor
		
		ucMapper = UserCategoryMapper(db, cursor)
		category_Name_ID_Map = ucMapper.mapCategoryNameToID()
		
		labs = {}	# labID, labName
		
		if accessLevel != "":
			cursor.execute("SELECT labID, lab_name FROM LabInfo_tbl l, UserCategories_tbl c WHERE c.categoryID " + oper + `category_Name_ID_Map[accessLevel]` + " AND l.default_access_level=c.categoryID AND l.status='ACTIVE' ORDER BY lab_name")
		else:
			cursor.execute("SELECT labID, lab_name FROM LabInfo_tbl WHERE status='ACTIVE'")
			
		results = cursor.fetchall()
		
		for result in results:
			labID = int(result[0])
			labName = result[1]
			
			labs[labID] = labName
			
		return labs


	# Find the name of the lab identified by labID
	def findLabName(self, labID):
		
		db = self.db
		cursor = self.cursor
		
		labName = ""
		
		cursor.execute("SELECT lab_name FROM LabInfo_tbl WHERE labID=" + `labID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			labName = result[0]
		
		return labName


	# Opposite: Find the ID of a lab, given its name
	def findLabID(self, labName):
		
		db = self.db
		cursor = self.cursor
		
		labID = 0

		cursor.execute("SELECT labID FROM LabInfo_tbl WHERE lab_name=" + `labName` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			labID = int(result[0])
		
		return labID


	# Fetch the 2-letter unique code identifier for the lab (used in plate creation but defined here since it's stored in LabInfo_tbl)
	def findLabCode(self, labID):
	
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT labCode FROM LabInfo_tbl WHERE labID=" + `labID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			return result[0].upper()
		
		return ""
	
	
	# Find the members of this laboratory
	# Returns a list of User instances
	def findMembers(self, labID):
	
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
	
		db = self.db
		cursor = self.cursor
		
		tmpLab = self.findLabByID(labID)
		members = []

		cursor.execute("SELECT userID, username, firstname, lastname, description, category, email FROM Users_tbl WHERE labID=" + `labID` + " AND status='ACTIVE' AND length(description) > 0 ORDER BY description")
		results = cursor.fetchall()

		for result in results:
			memberID = int(result[0])
			username = result[1]
			firstName = result[2]
			lastName = result[3]
			description = result[4]
			category = result[5]
			email = result[6]
			
			tmpUser = User(memberID, username, firstName, lastName, description, tmpLab, category, email)
			members.append(tmpUser)

		return members


	# Find what default access level (Reader, Writer, Admin) members of this lab have to OpenFreezer
	def findDefaultAccessLevel(self, labID):
	
		db = self.db
		cursor = self.cursor
		
		defaultAccessLevel = ""
		
		cursor.execute("SELECT default_access_level FROM LabInfo_tbl WHERE labID=" + `labID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			defaultAccessLevel = int(result[0])
		
		return defaultAccessLevel


	# Find the address ('location' column) of labID
	def findLabAddress(self, labID):
	
		db = self.db
		cursor = self.cursor

		location = ""
		
		cursor.execute("SELECT location FROM LabInfo_tbl WHERE labID=" + `labID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			location = result[0]
			
		return location
		
		
	def findLabHead(self, labID):
		
		db = self.db
		cursor = self.cursor

		labHead = ""
		
		cursor.execute("SELECT lab_head FROM LabInfo_tbl WHERE labID=" + `labID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			labHead = result[0]
			
		return labHead
		

	####################################################
	# UPDATE FUNCTIONS
	####################################################
	
	# Set the name of the lab identified by labID to newName
	def setLabName(self, labID, newName):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE LabInfo_tbl SET lab_name=" + `newName` + " WHERE labID=" + `labID` + " AND status='ACTIVE'")
		
	
	# Set the name of the lab head for the lab identified by labID
	def setLabHead(self, labID, newLabHead):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE LabInfo_tbl SET lab_head=" + `newLabHead` + " WHERE labID=" + `labID` + " AND status='ACTIVE'")
		
	
	def setLabCode(self, labID, newLabCode):
	
		db = self.db
		cursor = self.cursor
		
		if not self.existsLabCode(labID, newLabCode):
			cursor.execute("UPDATE LabInfo_tbl SET labCode=" + `newLabCode` + " WHERE labID=" + `labID` + " AND status='ACTIVE'")
		else:
			raise DuplicateLabCodeException()
		
		
	# Change the description of labID
	def setLabDescription(self, labID, newDescr):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE LabInfo_tbl SET description=" + `newDescr` + " WHERE labID=" + `labID` + " AND status='ACTIVE'")
		
	
	# Change the default access level for labID
	# newAccLev is an INTEGER categoryID that needs to be mapped to its text value
	def setLabAccessLevel(self, labID, newAccLev):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE LabInfo_tbl SET default_access_level=" + `newAccLev` + " WHERE labID=" + `labID` + " AND status='ACTIVE'")


	def setLocation(self, labID, newLocn):		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE LabInfo_tbl SET location=" + `newLocn` + " WHERE labID=" + `labID` + " AND status='ACTIVE'")

		
	def deleteLab(self, labID):
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE LabInfo_tbl SET status='DEP' WHERE labID=" + `labID` + " AND status='ACTIVE'")


	# Delete a member - Complete deletion, set status to DEP
	def deleteMember(self, labID, memberID):
		db = self.db
		cursor = self.cursor
		
		uHandler = UserHandler(db, cursor)
		uHandler.deleteUser(memberID)

		#cursor.execute("UPDATE Users_tbl SET labID='0' WHERE labID=" + `labID` + " AND userID=" + `memberID` + " AND status='ACTIVE'")


	# Delete all members from this lab - see comments for deleteMember
	def deleteAllMembers(self, labID):		
		db = self.db
		cursor = self.cursor
		
		uHandler = UserHandler(db, cursor)
		
		members = self.findMembers(labID)
		
		for mem in members:
			memID = mem.getUserID()	
			uHandler.deleteUser(memID)
			
		#cursor.execute("UPDATE Users_tbl SET labID='0' WHERE labID=" + `labID` + " AND status='ACTIVE'")
		
		
	# Add a member to a lab
	# Assume member exists in the system (an ACTIVE entry exists in Users_tbl, its labID needs to be set to 'labID' argument value)
	# However, verify that the member exists just in case
	def addLabMember(self, labID, memberID):
	
		db = self.db
		cursor = self.cursor
		
		uHandler = UserHandler(db, cursor)
		
		if uHandler.existsUser(memberID):
			cursor.execute("UPDATE Users_tbl SET labID=" + `labID` + " WHERE userID=" + `memberID` + " AND status='ACTIVE'")
				
	
	# This function is called upon exit from Modify view to update the list of existing lab members - mainly to remove members from that list
	# newMembers: list of user IDs, BUT they arrive as STRINGS!!!!!!!!!!  Must be cast to INT
	def updateLabMembers(self, labID, newMembers):
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		db = self.db
		cursor = self.cursor
		
		uHandler = UserHandler(db, cursor)
			
		# Find out which members in old members list are not in new members list and delete them	
		oldMembers = self.findMembers(labID)
				
		# fetch the IDs of members in oldMembers (a list of User objects)
		oldMemIDs = []
		
		for m in oldMembers:
			oldMemIDs.append(m.getUserID())
				
		# Cast each element in newMembers to INT
		newMemIDs = []
		
		for n in newMembers:
			newMemIDs.append(int(n))
		
		memDel = utils.diff(oldMemIDs, newMemIDs)
		
		for memID in memDel:
			#self.deleteMember(labID, memID)
			uHandler.deleteUser(memID)
