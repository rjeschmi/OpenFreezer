##############################################################################################
# This module serves as an intermediate layer between the database and Packet module objects
#
# Written: May 25, 2007, by Marina Olhovsky
# Last modified: July 5, 2007
##############################################################################################

import MySQLdb
import utils

from general_handler import GeneralHandler, ReagentPropertyHandler
from comment_handler import CommentHandler
from user_handler import UserHandler

from packet import Packet
from laboratory import Laboratory
from user import User

from exception import *

class ProjectDatabaseHandler(GeneralHandler):

	def __init__(self, db, cursor):
		super(ProjectDatabaseHandler, self).__init__(db, cursor)


	# Fetch all info on project identified by packetID
	def findPacket(self, packetID):
		
		db = self.db
		cursor = self.cursor
		
		uHandler = UserHandler(db, cursor)
		
		newPacket = None

		cursor.execute("SELECT ownerID, packetName, is_private, comment FROM Packets_tbl p, GeneralComments_tbl c WHERE packetID=" + `packetID` + " AND p.packetDescription = c.commentID AND p.status='ACTIVE' AND c.status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			ownerID = int(result[0])
			packetOwner = uHandler.getUserByID(ownerID)
			
			packetName = result[1]
			
			# private or public
			accessType = result[2]
			
			# Value TRUE or FALSE is returned as a STRING, convert to Boolean
			if accessType == 'TRUE':
				isPrivate = True
			else:
				isPrivate = False

			packetDescr = result[3]

			packetReaders = self.findProjectMembers(packetID, 'Reader')
			packetWriters = self.findProjectMembers(packetID, 'Writer')

			newPacket = Packet(packetID, packetName, packetDescr, packetOwner, isPrivate, packetReaders, packetWriters)

		return newPacket
		

	# Returns: INT, the value of column packetDescription in Packtes_tbl, refers to commentID in GeneralComments_tbl
	def findPacketDescriptionID(self, packetID):
		db = self.db
		cursor = self.cursor
		
		descrCommID = -1
		
		cursor.execute("SELECT packetDescription FROM Packets_tbl WHERE packetID=" + `packetID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			descrCommID = int(result[0])
			
		return descrCommID
			

	# Returns: STRING, the actual text description of this project
	def findPacketDescription(self, packetID):
		db = self.db
		cursor = self.cursor
		
		commHandler = CommentHandler(db, cursor)
		
		descrCommID = self.findPacketDescriptionID(packetID)
		description = commHandler.findCommentByID(descrCommID)
		
		return description
		

	# Make an entry in Packets_tbl for a new Project
	# Modified June 20/07: Take as arguments a Project instance
	def insertPacket(self, packet):
	
		db = self.db
		cursor = self.cursor
	
		# extract all attributes from 'packet'
		projectID = packet.getNumber()
		
		# Owner is a User instance.  Get his user ID
		owner = packet.getOwner()
		packetOwner = owner.getUserID()		
		
		packetName = packet.getName()
		packetDescription = packet.getDescription()	# may be empty
		
		packetReaders = packet.getReaders()		# list of User instances
		packetWriters = packet.getWriters()
		
		# Create a Comment table entry for Project description
		commHandler = CommentHandler(db, cursor)

		# select 'Packet' commentLinkID
		packetCommLinkID = commHandler.findCommentLinkID('Packet')
		descrCommID = commHandler.insertComment(packetCommLinkID, packetDescription)

		# Private or public
		#isPrivate = packet.isPrivate()
		
		# convert Boolean values to text, not stored otherwise
		if packet.isPrivate() == False:
			isPrivate = 'FALSE'
		else:
			isPrivate = 'TRUE'
			
		cursor.execute("INSERT INTO Packets_tbl(packetID, ownerID, packetName, packetDescription, is_private) VALUES(" + `projectID` + ", " + `packetOwner` + ", " + `packetName` + ", " + `descrCommID` + ", " + `isPrivate` + ")")
		packetID = int(db.insert_id())
		
		self.insertPacketReaders(packetID, packetReaders)
		self.insertPacketWriters(packetID, packetWriters)
		
		return packetID
		

	# Update project information with new values
	def updatePacket(self, projectID, ownerID, projectName, projectDescr, isPrivate, projectReaderIDs, projectWriterIDs):

		db = self.db
		cursor = self.cursor

		commHandler = CommentHandler(db, cursor)

		# simple: name and owner
		cursor.execute("UPDATE Packets_tbl SET ownerID=" + `ownerID` + " WHERE packetID=" + `projectID`)
		cursor.execute("UPDATE Packets_tbl SET packetName=" + `projectName` + " WHERE packetID=" + `projectID`)
		
		# private or public
		# again, convert Boolean values to text; otherwise they're not stored
		if isPrivate == False:
			isPrivate = 'FALSE'
		else:
			isPrivate = 'TRUE'
			
		cursor.execute("UPDATE Packets_tbl SET is_private=" + `isPrivate` + " WHERE packetID=" + `projectID`)

		# description
		packetCommLinkID = commHandler.findCommentLinkID('Packet')
		packetDescr = commHandler.findCommentID(projectDescr, packetCommLinkID)
		cursor.execute("UPDATE Packets_tbl SET packetDescription=" + `packetDescr` + " WHERE packetID=" + `projectID`)

		# members
		self.updateProjectMembers(projectID, projectReaderIDs, 'Reader')
		self.updateProjectMembers(projectID, projectWriterIDs, 'Writer')


	# Is a project empty? i.e. no reagents associated with it
	def isEmpty(self, pID):
		
		db = self.db
		cursor = self.cursor
		
		rpHandler = ReagentPropertyHandler(db, cursor)
		projectPropID = rpHandler.findPropID("packet id")
		
		cursor.execute("SELECT COUNT(reagentID) FROM ReagentPropList_tbl WHERE propertyID=" + `projectPropID` + " AND propertyValue=" + `pID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			numReagents = int(result[0])
			return numReagents == 0
		else:
			return True


	def isPrivate(self, pID):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT is_private FROM Packets_tbl WHERE packetID=" + `pID` + " AND status='ACTIVE'")
		result = cursor.fetchone()
		
		if result[0] == 'TRUE':
			return True
		else:
			return False


	# Input: pID: INT, packet ID
	def deleteProject(self, pID):
		
		db = self.db
		cursor = self.cursor
		
		commHandler = CommentHandler(db, cursor)

		# CHECK THAT THERE ARE NO REAGENTS ASSOCIATED WITH THIS PROJECT!!!
		if self.isEmpty(pID):
		
			# update Packets, Comments and ProjectMembers tables
			cursor.execute("UPDATE Packets_tbl SET status='DEP' WHERE packetID=" + `pID` + " AND status='ACTIVE'")

			# find description
			descrID = self.findPacketDescriptionID(pID)
			commHandler.deleteComment(descrID)

			# delete members
			self.deleteAllProjectMembers(pID)
			return True
		else:
			return False


	# Find members of project identified by 'projectID' that function as role' in this project
	# 'role': Reader/Writer
	# Return: List of User **objects**
	def findProjectMembers(self, projectID, role):

		db = self.db
		cursor = self.cursor

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		uHandler = UserHandler(db, cursor)
		
		members = []
		
		# include status='ACTIVE' clause; otherwise a DEP member might be selected causing an error
		cursor.execute("SELECT p.memberID, u.firstname, u.lastname, u.description, u.category, u.labID, l.lab_name, l.description FROM ProjectMembers_tbl p, Users_tbl u, LabInfo_tbl l WHERE p.packetID=" + `projectID` + " AND p.role=" + `role` + " AND u.userID=p.memberID AND l.labID=u.labID AND p.status='ACTIVE' AND u.status='ACTIVE' AND l.status='ACTIVE'")
		results = cursor.fetchall()
				
		for result in results:
			
			memberID = int(result[0])
			
			# removed, more efficient and accurate to rewrite query here and include 'status=ACTIVE' clause
			#member = uHandler.getUserByID(memberID)
			
			memberFirstName = result[1]
			memberLastName = result[2]
			memberFullName = result[3]
			memberCategory = int(result[4])
			memberLabID = result[5]
			
			labName = result[6]
			labDescr = result[7]
			tempLab = Laboratory(memberLabID, labName, labDescr)
			
			member = User(memberID, "", memberFirstName, memberLastName, memberFullName, tempLab, memberCategory)
			
			members.append(member)
		
		return members
		
	
	# Given a member ID, find all his/her projects
	# Return list of Project **OBJECTS**
	# Updated Sept. 5/07: 'role' can be one of 'Reader', 'Writer', or OWNER
	def findMemberProjects(self, memberID, role):
		
		db = self.db
		cursor = self.cursor
		
		projects = []
		
		if role.lower() == 'owner':
			cursor.execute("SELECT p.packetID, p.packetName, p.ownerID, u.lastName FROM Packets_tbl p, Users_tbl u WHERE p.ownerID =" + `memberID` + " AND u.userID=p.ownerID AND p.status='ACTIVE'")	# project owner cannot be DEP in this case, since it's the user currently logged in, but some of his/her projects may very well be DEP - so only select ACTIVE projects for the current user
		else:
			# Omit clause 'u.status=ACTIVE', since project owner may be DEP but his packet is active
			cursor.execute("SELECT p.packetID, p.packetName, p.ownerID, u.lastName FROM ProjectMembers_tbl m, Packets_tbl p, Users_tbl u WHERE m.memberID=" + `memberID` + " AND m.role=" + `role` + " AND m.packetID=p.packetID AND p.ownerID=u.userID AND m.status='ACTIVE' AND p.status='ACTIVE'")
			
		results = cursor.fetchall()

		for result in results:
		
			packetID = int(result[0])
			packetName = result[1]
			ownerID = int(result[2])
			ownerLastName = result[3]
			
			tmpOwner = User(ownerID, "", "", ownerLastName, "", None, "", "", "", [], [])
			
			project = Packet(packetID, packetName, "", tmpOwner)
			projects.append(project)

		return projects

	
	# Assign a list of project IDs to a member
	# projects: linear list of INTEGER IDs
	# role: one of 'Reader' or 'Writer'
	def insertMemberProjects(self, memberID, projects, role):
	
		db = self.db
		cursor = self.cursor
	
		for pID in projects:
			if not self.existsProjectMember(pID, memberID, role):
				self.addProjectMember(pID, memberID, role)
			
	
	# Make an entry in ProjectMembers_tbl for projectID and each element in projectReaders, indicating 'Reader' for 'category'
	def insertPacketReaders(self, projectID, projectReaders):
	
		db = self.db
		cursor = self.cursor
				
		for reader in projectReaders:
			if not self.existsProjectMember(projectID, reader.getUserID(), 'Reader'):
				cursor.execute("INSERT INTO ProjectMembers_tbl(packetID, memberID, role) VALUES(" + `projectID` + ", " + `reader.getUserID()` + ", 'Reader')")


	# Make an entry in ProjectMembers_tbl for projectID and each element in projectWriters, storing 'category' = 'Writer'
	def insertPacketWriters(self, projectID, projectWriters):
	
		db = self.db
		cursor = self.cursor
				
		for writer in projectWriters:
			if not self.existsProjectMember(projectID, writer.getUserID(), 'Writer'):
				cursor.execute("INSERT INTO ProjectMembers_tbl(packetID, memberID, role) VALUES(" + `projectID` + ", " + `writer.getUserID()` + ", 'Writer')")


	# Delete (DEP) old members and insert new values
	# members: a list of INT user IDs
	def updateProjectMembers(self, projectID, members, role):

		db = self.db
		cursor = self.cursor

		# Simply delete (DEP) old members and insert new values
		self.deleteProjectMembers(projectID, role)
		
		for member in members:
			self.addProjectMember(projectID, member, role)
			
	
	# Update the list of projects for a particular user
	# DEP old project values and insert new ones according to 'role'
	# newProjects: list of INTEGER project IDs
	def updateUserProjects(self, userID, newProjects, role):
	
		db = self.db
		cursor = self.cursor
		
		self.deleteMemberProjects(userID, role)
		self.insertMemberProjects(userID, newProjects, role)
	
			
	# Add a member to a project
	# member: an INT user ID
	def addProjectMember(self, projectID, member, role):
	
		db = self.db
		cursor = self.cursor
	
		cursor.execute("INSERT INTO ProjectMembers_tbl(packetID, memberID, role) VALUES(" + `projectID` + ", " + `member` + ", " + `role` + ")")
	

	# Delete a project member
	# 'role' parameter required, since a member may have more than one role in a project
	def deleteMember(self, pID, memberID, role):
		db = self.db
		cursor = self.cursor
	
		cursor.execute("UPDATE ProjectMembers_tbl SET status='DEP' WHERE packetID=" + `pID` + " AND memberID=" + `memberID` + " AND role=" + `role` + " AND status='ACTIVE'")


	# Delete all members in a specific role (reader/writer)
	def deleteProjectMembers(self, pID, role):
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE ProjectMembers_tbl SET status='DEP' WHERE packetID=" + `pID` + " AND role=" + `role` + " AND status='ACTIVE'")


	# Delete all projects that a member has 'role' access to (e.g. delete all readonly projects for user A)
	# Useful when modifying member details
	def deleteMemberProjects(self, memberID, role):
		db = self.db
		cursor = self.cursor
	
		cursor.execute("UPDATE ProjectMembers_tbl SET status='DEP' WHERE memberID=" + `memberID` + " AND status='ACTIVE' AND role=" + `role`)


	# Delete ALL members of the given project
	def deleteAllProjectMembers(self, pID):
		db = self.db
		cursor = self.cursor
	
		cursor.execute("UPDATE ProjectMembers_tbl SET status='DEP' WHERE packetID=" + `pID` + " AND status='ACTIVE'")


	# This is useful when removing a user from the system - revoke his access to all projects
	def deleteMemberFromllProjects(self, memberID):
		
		db = self.db
		cursor = self.cursor
	
		cursor.execute("UPDATE ProjectMembers_tbl SET status='DEP' WHERE memberID=" + `memberID` + " AND status='ACTIVE'")
		

	# Updated June 20/07: check if current member has already been assigned to this project **in this role**
	# Can one member have two distinct access types to a project (e.g. be BOTH reader and writer??)  
	# Might occur by mistake; no big deal if the member was supposed to be a writer and gets assigned reader access; readers, however, should not be assigned writer access, unless their access is explicitly changed and they get promoted from reader to writer.  
	# This check should be performed by the validation function DURING PROJECT MODIFICATION
	def existsProjectMember(self, projectID, memberID, role):
		
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT * FROM ProjectMembers_tbl WHERE packetID=" + `projectID` + " AND memberID=" + `memberID` + " AND role=" + `role` + " AND status='ACTIVE'")
		results = cursor.fetchall()
	
		if results:
			return True
		else:
			return False


	# Go over the list of writers and return names of those who have previously been given read-only access to the project
	def validateWriters(self, projectID, writersList):
		
		db = self.db
		cursor = self.cursor
		
		readers = []	# list of users in writersList who already have read access to this project
		
		for writer in writersList:
			if self.existsProjectMember(projectID, writer, 'reader'):
				readers.append(writer)
		return readers
		
	
	# Select all projects in the database
	# Return a list of Project objects
	# Modified August 12/07: add parameter isPrivate; allows selection of all projects with a specific access level (public or private)
	def findAllProjects(self, isPrivate=""):
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO

		db = self.db
		cursor = self.cursor
		
		uHandler = UserHandler(db, cursor)
		
		projects = []
		
		if isPrivate == "":
			cursor.execute("SELECT packetID, ownerID, packetName, packetDescription FROM Packets_tbl WHERE status='ACTIVE'")
			results = cursor.fetchall()
			
			for result in results:
				packetID = int(result[0])
				ownerID = int(result[1])
				packetName = result[2]
				packetDescr = result[2]
				
				packetOwner = uHandler.getUserByID(ownerID)
				newPacket = Packet(packetID, packetName, packetDescr, packetOwner)
				
				projects.append(newPacket)
		else:
			cursor.execute("SELECT packetID, ownerID, packetName, packetDescription FROM Packets_tbl WHERE is_private=" + `isPrivate` + " AND status='ACTIVE'")
			results = cursor.fetchall()
			
			for result in results:
				packetID = int(result[0])
				ownerID = int(result[1])
				packetName = result[2]
				packetDescr = result[2]
				
				packetOwner = uHandler.getUserByID(ownerID)
				newPacket = Packet(packetID, packetName, packetDescr, packetOwner)
				
				projects.append(newPacket)

		return projects

	# Match a keyword in project name
	def matchProjectKeyword(self, keywd, multiple=False):
		
		db = self.db
		cursor = self.cursor
		
		packets = {}
		
		if not multiple:
			# one-keyword search
			cursor.execute("SELECT packetID, lastname, packetName FROM Packets_tbl p, Users_tbl u WHERE p.ownerID=u.userID AND p.status='ACTIVE' AND packetName LIKE '%" + keywd.strip() + "%'")
			
			results = cursor.fetchall()
			
			for result in results:
				packetID = int(result[0])
				owner = result[1]
				packetName = result[2]
				
				pText = `packetID` + ": " + owner + ": " + packetName
				
				packets[packetID] = pText
				
		return packets