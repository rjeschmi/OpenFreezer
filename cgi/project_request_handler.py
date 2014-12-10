#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()

import SocketServer
from SocketServer import BaseRequestHandler

import stat
import MySQLdb

import os
import sys
import string

# Custom modules
import utils

from database_conn import DatabaseConn
from exception import *

from packet import Packet
from user import User

from project_database_handler import ProjectDatabaseHandler
from user_handler import UserHandler

from project_output import ProjectOutputClass

from session import Session

#####################################################################################
# Contains functions to handle project creation, modification and deletion requests
# Extends abstract class BaseRequestHandler
#
# Written June 22, 2007, by Marina Olhovsky
# Last modified: August 20, 2007
#####################################################################################
#class ProjectRequestHandler(BaseRequestHandler):
class ProjectRequestHandler:
	__db = None
	__cursor = None
	__hostname = ""
	
	##########################################################
	# Constructor
	##########################################################
	def __init__(self):
	
		dbConn = DatabaseConn()
		db = dbConn.databaseConnect()
		cursor = db.cursor()
		hostname = dbConn.getHostname()

		self.__db = db
		self.__cursor = cursor
		self.__hostname = hostname
		
		
	##########################################################
	# Override parent method
	##########################################################
	def handle(self):
		
		db = self.__db
		cursor = self.__cursor
		
		form = cgi.FieldStorage(keep_blank_values="True")
		
		uHandler = UserHandler(db, cursor)
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		if form.has_key("username"):
			# store the user ID for use throughout the session; add to other views in addition to create in PHP
			currUname = form.getvalue("username")
			currUser = uHandler.getUserByDescription(currUname)
			Session.setUser(currUser)
				
		elif form.has_key("curr_user_id"):
			currUID = form.getvalue("curr_user_id")
			currUser = uHandler.getUserByID(currUID)
			Session.setUser(currUser)		
		
		if form.has_key("create_project"):
			self.createProject(form)
		
		elif form.has_key("modify_project"):
			self.modifyProject(form)
		
		elif form.has_key("save_project"):
			self.saveProject(form)
		
		elif form.has_key("cancel_project"):
			self.cancelModification(form)

		elif form.has_key("delete_project"):
			self.deleteProject(form)
			
		elif form.has_key("view_project"):
			self.printProjectInfo(form)
			
		elif form.has_key("view_packet"):
			# go to project view from User detailed view
			self.viewPacket(form)
			
		# Oct. 12, 2010
		elif form.has_key("search_project_by_keyword"):
			self.findPacket(form)

		cursor.close()
		db.close()
		
	##########################################################
	# Process Project (Packet) creation request
	##########################################################
	def createProject(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		# Handlers
		pHandler = ProjectDatabaseHandler(db, cursor)
		uHandler = UserHandler(db, cursor)

		# Get form values
		projectID = form.getvalue("packetID")
		ownerID = form.getvalue("packetOwner")

		# get owner's name
		packetOwner = uHandler.getUserByID(ownerID)

		packetName = form.getvalue("packetName")
		packetDescription = form.getvalue("packetDescription")
		
		# private or public
		if form.getvalue("private_or_public") == "public":
			isPrivate = False
		else:
			isPrivate = True
		
		# Lists of project readers & editors
		# These are lists of INTEGER USER IDs!!!!!
		# A User instance needs to be created for each!!!!!!!
		projectReaderIDs = form.getlist("readersTargetList")
		projectWriterIDs = form.getlist("writersTargetList")

		projectReaders = []
		projectWriters = []

		for rID in projectReaderIDs:
			tmpReader = uHandler.getUserByID(rID)
			projectReaders.append(tmpReader)
		
		for wID in projectWriterIDs:
			tmpWriter = uHandler.getUserByID(wID)
			
			# Now check if the user is an OpenFreezer writer - otherwise cannot be made Writer on a project
			if tmpWriter.getCategory() != 'Reader':
				projectWriters.append(tmpWriter)
			
		newProject = Packet(projectID, packetName, packetDescription, packetOwner, isPrivate, projectReaders, projectWriters)
		packetID = pHandler.insertPacket(newProject)		# new project is empty by default

		self.showProjectDetails('view', newProject)
		

	####################################################################
	# Redirect to Modify view - output project details in 'modify' mode
	####################################################################
	def modifyProject(self, form):

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		# Handlers
		pHandler = ProjectDatabaseHandler(db, cursor)
		uHandler = UserHandler(db, cursor)
		
		# Get project ID from form
		projectID = form.getvalue("packetID")
		ownerID = form.getvalue("packetOwner")

		# get owner's name
		packetOwner = uHandler.getUserByID(ownerID)

		packetName = form.getvalue("packetName")
		packetDescription = form.getvalue("packetDescription")
	
		# access type:
		accessType = form.getvalue("private_or_public")
		
		if accessType == 'Private':
			isPrivate = True
		else:
			isPrivate = False
	
		# Lists of project readers & editors
		# In this view, these are list of INTEGER USER IDs
		# A User instance needs to be created for each!!!!!!!
		projectReaderIDs = form.getlist("projectReaders")
		projectWriterIDs = form.getlist("projectWriters")

		projectReaders = []
		projectWriters = []

		for rID in projectReaderIDs:
			tmpReader = uHandler.getUserByID(rID)
			projectReaders.append(tmpReader)
		
		for wID in projectWriterIDs:
			tmpWriter = uHandler.getUserByID(wID)
			projectWriters.append(tmpWriter)		
	
		newProject = Packet(projectID, packetName, packetDescription, packetOwner, isPrivate, projectReaders, projectWriters)
		
		self.showProjectDetails('edit', newProject)


	########################################################################################################################################################
	# Save modified project on exit from Modify view - process form input, update database and print the updated project (redirect to Project Details View)
	########################################################################################################################################################
	def saveProject(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		# Handlers
		pHandler = ProjectDatabaseHandler(db, cursor)
		uHandler = UserHandler(db, cursor)
		
		# Get project ID from form
		projectID = form.getvalue("packetID")
		ownerID = form.getvalue("packetOwner")

		# get owner's name
		packetOwner = uHandler.getUserByID(ownerID)

		packetName = form.getvalue("packetName")
		packetDescription = form.getvalue("packetDescription")

		# private or public
		if form.getvalue("private_or_public") == "public":
			isPrivate = False
		else:
			isPrivate = True
		
		# Lists of project readers & editors
		# Updated Sept. 3/08: Do NOT save readers for a public project
		if isPrivate:
			projectReaderIDs = form.getlist("readersList")
		else:
			projectReaderIDs = []
			
		# writers are always needed
		projectWriterIDs = form.getlist("writersList")
		
		projectReaders = []
		projectWriters = []

		for rID in projectReaderIDs:
			tmpReader = uHandler.getUserByID(rID)
			projectReaders.append(tmpReader)

		for wID in projectWriterIDs:
			tmpWriter = uHandler.getUserByID(wID)
			
			# check categories - a Reader cannot be given Write access to a project
			if tmpWriter.getCategory() != 'Reader':
				projectWriters.append(tmpWriter)
			
			#projectWriters.append(tmpWriter)

		# Update database values
		pHandler.updatePacket(projectID, ownerID, packetName, packetDescription, isPrivate, projectReaderIDs, projectWriterIDs)

		# Output new values
		newProject = Packet(projectID, packetName, packetDescription, packetOwner, isPrivate, projectReaders, projectWriters)
		
		self.showProjectDetails('view', newProject)

	
	##########################################################
	# Exit Modify view without saving
	##########################################################
	def cancelModification(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		pHandler = ProjectDatabaseHandler(db, cursor)
		
		# form values
		projectID = int(form.getvalue('packetID'))
		newProject = pHandler.findPacket(projectID)
		
		self.showProjectDetails('view', newProject)


	##########################################################
	# Process Project deletion request
	##########################################################
	def deleteProject(self, form):

		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`

		pHandler = ProjectDatabaseHandler(db, cursor)

		# Get form values
		pID = int(form.getvalue("packetID"))
		success = int(pHandler.deleteProject(pID))
		utils.redirect(hostname + "Project.php?View=3&Success=" + `success` + "&pID=" + `pID`)


	##########################################################
	# Redirect to Packet view from User detailed view
	##########################################################
	def viewPacket(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`

		pHandler = ProjectDatabaseHandler(db, cursor)
		
		# Get form values
		projectID = int(form.getvalue("view_packet"))
		
		newProject = pHandler.findPacket(projectID)
		self.showProjectDetails('view', newProject)
		

	##########################################################
	# Print Project Details
	##########################################################
	def printProjectInfo(self, form):

		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		projectID = int(form.getvalue('packets'))
		pHandler = ProjectDatabaseHandler(db, cursor)
		
		newProject = pHandler.findPacket(projectID)
		
		self.showProjectDetails('view', newProject)

	
	############################################################################
	# Print Project Details page upon exit from creation and modification views
	# newProject: Project INSTANCE
	############################################################################
	def showProjectDetails(self, cmd, newProject):

		pOut = ProjectOutputClass()
		pOut.printProjectInfo(cmd, newProject)
		
	
	# New Oct. 12, 2010: added project search functionality on 'add user' page
	def findPacket(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		pHandler = ProjectDatabaseHandler(db, cursor)
		
		packets = []
		pString = ""
		
		keyword = form.getvalue("keyword")
		packetsList = pHandler.matchProjectKeyword(keyword)
		
		for pID in packetsList:
			pText = packetsList[pID]
			packets.append(pText)

			pString = string.join(packets, ", ")
		
		print "Content-type:text/html"
		print
		print pString

##########################################################
# Central callable function
##########################################################
def main():

	pReqHandler = ProjectRequestHandler()
	pReqHandler.handle()
	#pReqHandler.finish()
main()
