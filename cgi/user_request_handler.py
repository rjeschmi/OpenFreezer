#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()

import SocketServer
from SocketServer import BaseRequestHandler

import smtplib
#import mimetypes,mimetools,MimeWriter		# deprecated

import email
from email import *

#from email import MIMEText
#from email import MIMEMultipart
#from email import MIMEBase
#from email import Utils 
#from email.Utils import COMMASPACE, formatdate
#from email import Encoders

import MySQLdb

import os
import tempfile
import stat
import sys
import string

from random import choice	# June 1, 2010: random password generation

# July 30, 2010
import MimeWriter
import mimetools
import cStringIO

# Custom modules
import utils

from database_conn import DatabaseConn
from exception import DuplicateUsernameException, DeletedUserException, DuplicateLabCodeException
from session import Session

from packet import Packet
from user import User
from laboratory import Laboratory

from project_database_handler import ProjectDatabaseHandler
from user_handler import UserHandler
from lab_handler import LabHandler
from mapper import UserCategoryMapper

from user_output import UserOutputClass

#####################################################################################
# Contains functions to handle User creation, modification and deletion requests
#
# Written June 28, 2007, by Marina Olhovsky
# Last modified: Sept. 17, 2007
#####################################################################################
#class UserRequestHandler(BaseRequestHandler):
class UserRequestHandler:
	__db = None
	__cursor = None
	__hostname = ""
	__clone_request = ""
	__mail_programmer = ""
	__mail_biologist = ""
	__mail_server = ""		# August 19, 2011

	##########################################################
	# Constructor
	##########################################################
	def __init__(self):
	
		dbConn = DatabaseConn()
		db = dbConn.databaseConnect()
		cursor = db.cursor()
		hostname = dbConn.getHostname()
		clone_request = dbConn.getMailCloneRequest()
		
		mail_programmer = dbConn.getProgrammerEmail()
		mail_biologist = dbConn.getBiologistEmail()
		mail_admin = dbConn.getAdminEmail()		# August 19, 2011

		mail_server = dbConn.getMailServer()		# August 19, 2011

		self.__db = db
		self.__cursor = cursor
		self.__hostname = hostname
		self.__clone_request = clone_request
		self.__mail_programmer = mail_programmer	# July 30, 2010
		self.__mail_biologist = mail_biologist		# July 30, 2010
		self.__mail_admin = mail_admin			# August 19, 2011
		self.__mail_server = mail_server		# August 19, 2011


	##########################################################
	# Override parent method
	##########################################################
	def handle(self):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname

		mail_server = self.__mail_server		# August 19, 2011
		mail_admin = self.__mail_admin			# August 19, 2011
		
		clone_request = self.__clone_request
		
		form = cgi.FieldStorage(keep_blank_values="True")
		
		uHandler = UserHandler(db, cursor)
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		if form.has_key("curr_username"):
			# store the user ID for use throughout the session; add to other views in addition to create in PHP
			currUname = form.getvalue("curr_username")
			currUser = uHandler.getUserByDescription(currUname)
			
			Session.setUser(currUser)
		
		elif form.has_key("curr_user_id"):
			currUID = form.getvalue("curr_user_id")
			currUser = uHandler.getUserByID(currUID)
			Session.setUser(currUser)
		
		
		if form.has_key("add_user"):
			self.addUser(form)
		
		elif form.has_key("modify_user"):
			self.modifyUser(form)

		elif form.has_key("cancel_user"):
			self.cancelUserModification(form)
		
		elif form.has_key("save_user"):
			self.saveUser(form)

		elif form.has_key("delete_user"):
			self.deleteUser(form)

		elif form.has_key("view_user") and form.getvalue("view_user") != "" and not form.has_key("modify_lab") and not form.has_key("delete_lab"):
			self.viewUser(form)

		# Nov. 17/07 - Personal user page
		elif form.has_key("view_user") and form.getvalue("view_user") == "" and not form.has_key("modify_lab") and not form.has_key("delete_lab"):
			self.printUserInfo('view', currUser)
		
		elif form.has_key("add_lab"):
			self.addLab(form)

		elif form.has_key("view_lab"):
			self.viewLab(form)

		elif form.has_key("modify_lab"):
			self.modifyLab(form)

		elif form.has_key("save_lab"):
			self.saveLab(form)
			
		elif form.has_key("cancel_lab"):
			self.cancelLabModification(form)
			
		elif form.has_key("delete_lab"):
			self.deleteLab(form)
			
		elif form.has_key("bug_report"):
			self.submitBug(form)
		
		elif form.has_key("send_order"):
			
			######################################################################
			# CHANGE SERVER NAME AND EMAIL TO YOUR LOCAL CREDENTIALS
			######################################################################

			userID = form.getvalue("curr_user_id")
			userDescr = form.getvalue("curr_username")
			
			from_email = uHandler.findEmail(userID)
			
			if not from_email:
				from_email = userDescr

			to_email = clone_request
			
			email_subject = userDescr + ": Clone Request"
			
			f_in = form.getvalue("outputContent")
			infile = open(f_in, 'rb')
			
			msg = email.MIMEMultipart.MIMEMultipart()
 			#msg.attach(email.MIMEText.MIMEText(infile.read()))	# no, this attaches plain text
			msg['Subject'] = email_subject
			
			part = email.MIMEBase.MIMEBase('application', "octet-stream")
			part.set_payload(infile.read())
			email.Utils.base64.standard_b64encode(infile.read())
			part.add_header('Content-Disposition', 'attachment; filename="%s"' % os.path.basename(f_in))
			msg.attach(part)
			
			server = smtplib.SMTP(mail_server)

			server.set_debuglevel(1)

			# Send a request to your clone request address
			server.sendmail(from_email, to_email, msg.as_string())
			
			# AND send a copy to the user (change the subject)
			#msg['Subject'] = "Clone request confirmation"		# doesn't change, investigate later
			
			# Return email text changed March 31/08
			
			#######################################
			# CHANGE TEXT AS NEEDED
			#######################################
			msg.attach(email.MIMEText.MIMEText("This is a copy of your clone request.  Please retain for your records.  You will be notified by e-mail when your clone is ready."))
			
			server.sendmail(to_email, from_email, msg.as_string())
			server.quit()
			
			# Method 2
			#sendmail = "/usr/sbin/sendmail"
			
			#o = os.popen("%s -t" %  sendmail,"w")
			#o.write("To: %s\r\n" %  to_email)
			
			#if from_email:
				#o.write("From: %s\r\n" %  from_email)
				#o.write("Subject: %s\r\n" %  email_subject)
				#o.write("\r\n")
				#o.write("%s\r\n" % msg)
			
			#o.close()
			
			os.remove(f_in)		# delete the file from /tmp dir
			
			utils.redirect(hostname + "User.php?View=8&Sent=1")
		
		# June 1, 2010: Automated password reset
		elif form.has_key("reset_pw"):
			
			# change June 2, 2010: Don't enter email, rather, ask users to enter their username - more secure
			#to_email = form.getvalue("email")
			
			from_email = mail_admin

			#success = True
			
			chars = string.letters + string.digits
			new_passwd = ""
			
			for i in range(10):
				new_passwd += choice(chars)
	
			# reset it in the database
			if form.has_key("uName"):
				u_name = form.getvalue("uName")
				
				userID = uHandler.findUserIDByUsername(u_name)
								
				if userID > 0:
					u_descr = uHandler.findDescription(userID)
					
					to_email = uHandler.findEmail(userID)

					uHandler.setUserPropertyValue(userID, 'password', new_passwd)
				
					email_subject = "OpenFreezer Password Change"
					
					msg = email.MIMEMultipart.MIMEMultipart()
					#msg.attach(email.MIMEText.MIMEText(infile.read()))	# no, this attaches plain text
					
					msg['Subject'] = email_subject
					
					###################################
					# CHANGE TEXT AS NEEDED
					###################################
					msg.attach(email.MIMEText.MIMEText("Dear " + u_descr + ",\n\nYour password for OpenFreezer has been changed.\n\nYour temporary new password is: " + new_passwd + ".\n\nPlease change the temporary password as soon as you log into the system.\n\nYour username for OpenFreezer is '" + u_name + "'.\n\nFor any questions, please refer to http://openfreezer.org. \n\nSincerely,\nOpenFreezer support team.\n--------------------------------\nThis is an automatically generated e-mail message.  Please do not reply to this e-mail.  All questions should be directed to your local administrator."))
					
					server = smtplib.SMTP(mail_server)

					server.set_debuglevel(1)
		
					server.sendmail(from_email, to_email, msg.as_string())
					server.quit()
					
					utils.redirect(hostname + "User.php?View=6&Reset=1&uid=" + `userID`)
				else:
					# retry by description
					if form.has_key("uDesc"):
						u_descr = form.getvalue("uDesc")
						
						# but account for whitespace
						toks = u_descr.split(" ")
						
						tmp_descr = ""
						
						for tok in toks:
							tmp_descr += tok.strip() + " "
		
						# strip extra whitespace from end
						tmp_descr = tmp_descr.strip()
					
						userID = uHandler.findUserIDByDescription(tmp_descr)
						
						if userID > 0:
							u_name = uHandler.findUsername(userID)
							
							to_email = uHandler.findEmail(userID)
							uHandler.setUserPropertyValue(userID, 'password', new_passwd)
							
							email_subject = "OpenFreezer Password Change"
							
							msg = email.MIMEMultipart.MIMEMultipart()
							#msg.attach(email.MIMEText.MIMEText(infile.read()))	# no, this attaches plain text
							
							msg['Subject'] = email_subject
							
							##############################
							# CHANGE TEXT AS NEEDED
							##############################
							msg.attach(email.MIMEText.MIMEText("Dear " + u_descr + ",\n\nYour password for OpenFreezer has been changed.\n\nYour temporary new password is: " + new_passwd + ".\n\nPlease change the temporary password as soon as you log into the system.\n\nYour username for OpenFreezer is '" + u_name + "'.\n\nPlease refer to http://openfreezer.org for additional support.\n\nSincerely,\nOpenFreezer support team.\n--------------------------------\nThis is an automatically generated e-mail message.  Please do not reply to this e-mail.  All questions should be directed to <a href='mailto:" + mail_admin + "'>" + mail_admin + "</a>"))
							
							server = smtplib.SMTP(mail_server)
							server.set_debuglevel(1)
				
							server.sendmail(from_email, to_email, msg.as_string())
							server.quit()
							
							utils.redirect(hostname + "User.php?View=6&Reset=1&uid=" + `userID`)
						else:
							utils.redirect(hostname + "User.php?View=6&Reset=0")
					else:
						utils.redirect(hostname + "User.php?View=6&Reset=0")
			else:
				utils.redirect(hostname + "User.php?View=6&Reset=0")

		cursor.close()
		db.close()
	
	
	##########################################################
	# Process Add User request
	##########################################################
	def addUser(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		mail_server = self.__mail_server		# August 19, 2011

		mail_programmer = self.__mail_programmer	# July 30, 2010
		mail_biologist = self.__mail_biologist
		mail_admin = self.__mail_admin
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		uHandler = UserHandler(db, cursor)
		lHandler = LabHandler(db, cursor)
		pHandler = ProjectDatabaseHandler(db, cursor)
		
		ucMapper = UserCategoryMapper(db, cursor)
		category_Name_ID_Map = ucMapper.mapCategoryNameToID()
		
		# Get form values
		labID = int(form.getvalue("labs"))
		username = form.getvalue("username")
		
		firstName = form.getvalue("firstName")
		lastName = form.getvalue("lastName")
		description = firstName + " " + lastName
		
		to_email = form.getvalue("email")

		from_email = mail_admin

		# Change July 30, 2010 - random password generator
		#passwd = form.getvalue("password")
	
		chars = string.letters + string.digits
		passwd = ""
		
		for i in range(10):
			passwd += choice(chars)
		
		# System access level: Lab default or override?
		#if form.getvalue("privChoiceRadio") == 'override':
		accessLevel = category_Name_ID_Map[form.getvalue("system_access_level")]
		#else:
		#accessLevel = lHandler.findDefaultAccessLevel(labID)
		
		newProps = {}
		
		try:
			# Insert User information
			userID = uHandler.insertUser(username, firstName, lastName, description, accessLevel, to_email, passwd, labID)
			#newUser = uHandler.getUserByID(userID)
			tmpLab = lHandler.findLabByID(labID)
			#print tmpLab.getName()
			
			# Insert Project info
			# Sept. 11/07: Differentiate between user categories Reader and Writer - different field names
			if form.has_key("userProjectsReadonly"):
				# list of IDs
				readonlyProjects = utils.unique(form.getlist("userProjectsReadonly"))
				#print `readonlyProjects`
				pHandler.insertMemberProjects(userID, readonlyProjects, 'Reader')
			
			elif form.has_key("userProjectsReadonlyWrite"):
				# list of IDs
				readonlyProjects = utils.unique(form.getlist("userProjectsReadonlyWrite"))
				#print `readonlyProjects`
				pHandler.insertMemberProjects(userID, readonlyProjects, 'Reader')
				
			# Write projects exist only for Writers
			if form.has_key("userProjectsWrite"):
				writeProjects = utils.unique(form.getlist("userProjectsWrite"))
				pHandler.insertMemberProjects(userID, writeProjects, 'Writer')
			
			# don't assign projects to a User instance - will retrieve them from db in output function
			newUser = User(userID, username, firstName, lastName, description, tmpLab, form.getvalue("system_access_level"), to_email, passwd, [], [])
			
			email_subject = "OpenFreezer User Account"
			
			msg = email.MIMEMultipart.MIMEMultipart('alternative')
			
			msg['Subject'] = email_subject
			msg['To'] = to_email

			msgText = "Hi " + firstName + ",<BR><BR>An OpenFreezer account has been created for you.&nbsp;&nbsp;Your access level is " + form.getvalue("system_access_level") + ", so you can "
			
			if form.getvalue("system_access_level") == 'Reader':
				msgText += "search for clones.&nbsp;&nbsp;If you wish to add/modify reagents or create projects, please contact the administrator to upgrade your access level.<BR>"
			
			elif form.getvalue("system_access_level") == 'Writer':
				msgText += "search, add, and modify reagents.&nbsp;&nbsp;If you wish to create projects, please contact the administrator to upgrade your access level.<BR>"
			
			elif form.getvalue("system_access_level") == 'Creator':
				msgText += "search for clones, add and modify reagents, as well as create your own projects.<BR>"
			

			#####################################################
			# CHANGE TEXT AS NEEDED
			#####################################################

			msgText += "<BR>The URL to access the system is <a href='" + hostname + "'>" + hostname + "</a>.&nbsp;&nbsp;Your username is <b>" + username + "</b>, and your temporary password is <b>" + passwd + "</b>.&nbsp;&nbsp;Please <u>change the temporary password as soon as you log into the website</u> - you can do it through the 'Change your password' link under the 'User Management' menu section.<BR><BR>Please refer to http://openfreezer.org for additional support.<BR><BR>Sincerely,<BR>OpenFreezer  support team.<BR><BR><span style='font-family:Courier; font-size:10pt;'><HR>This is an automatically generated e-mail message.&nbsp;&nbsp;Please do not reply to this e-mail.&nbsp;&nbsp;All questions should be directed to your local administrator.</span>"
			
			msgText = email.MIMEText.MIMEText(msgText, 'html')
			msg.attach(msgText)
			
			server = smtplib.SMTP(mail_server)
			server.set_debuglevel(1)

			server.sendmail(from_email, [to_email], msg.as_string())
			server.quit()
			
			self.printUserInfo('view', newUser)
			
		except DeletedUserException:
		
			# Without asking too many questions, reactivate the deleted user and overwrite his/her attributes with the form input values
			userID = uHandler.findUserIDByUsername(username)
			
			newProps["firstname"] = firstName
			newProps["lastname"] = lastName
			newProps["description"] = description
			newProps["email"] = email
			newProps["status"] = "ACTIVE"
			newProps["password"] = passwd

			# Insert new database values and create new object
			uHandler.updateUserProperties(userID, newProps)			# database update
			newUser = uHandler.getUserByID(userID)		
			
			# Insert Project info
			readProjects = []
			writeProjects = []
			
			if form.has_key("userProjectsReadonly"):
				# list of IDs
				readonlyProjects = form.getlist("userProjectsReadonly")

				for r in readonlyProjects:
					pHandler.addProjectMember(r, userID, 'Reader')
					
					#tmpReadProject = pHandler.findPacket(r)
					#readProjects.append(tmpReadProject)
					#newUser.addProject(tmpReadProject, 'read')

			if form.has_key("userProjectsWrite"):
				writeProjects = form.getlist("userProjectsWrite")

				for w in writeProjects:
					pHandler.addProjectMember(w, userID, 'Writer')
					
					#tmpWriteProject = pHandler.findPacket(w)
					#writeProjects.append(tmpWriteProject)
					#newUser.addProject(tmpWriteProject, 'write')
			
			#newUser.setReadProjects(readProjects)
			#newUser.setWriteProjects(writeProjects)
			
			self.printUserInfo('view', newUser)
			#utils.redirect(hostname + "User.php?View=3&fd=" + filename)
			
		except DuplicateUsernameException:
			
			# return to the view with input values and error message
			# Need to construct a dummy User instance to save form values for error output on the next page (otherwise they're lost as soon as Submit is pressed and creation view is exited)
			newLab = lHandler.findLabByID(labID)
			newUser = User(0, username, firstName, lastName, description, newLab, "", email, passwd)
			
			self.printUserInfo('create', newUser)
			#utils.redirect(hostname + "User.php?View=1&fd=" + filename + "&ErrCode=Dup_un#w1")
			
	
	##########################################################
	# Process Change User Details request
	##########################################################	
	def modifyUser(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname

		uHandler = UserHandler(db, cursor)
		lHandler = LabHandler(db, cursor)
		pHandler = ProjectDatabaseHandler(db, cursor)
		
		ucMapper = UserCategoryMapper(db, cursor)
		category_Name_ID_Map = ucMapper.mapCategoryNameToID()

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`

		# Get form values
		userID = int(form.getvalue("userID")) 
		newUser = uHandler.getUserByID(userID)

		'''
		labID = int(form.getvalue("labID"))
		username = form.getvalue("username")
		
		firstName = form.getvalue("firstName")
		lastName = form.getvalue("lastName")
		description = firstName + " " + lastName
		
		email = form.getvalue("email")
		passwd = form.getvalue("password")
		'''
		
		readProjects = pHandler.findMemberProjects(userID, 'Reader')
		newUser.setReadProjects(readProjects)
		
		writeProjects = pHandler.findMemberProjects(userID, 'Writer')
		newUser.setWriteProjects(writeProjects)
		
		self.printUserInfo('edit', newUser)
		#utils.redirect(hostname + "User.php?View=3&fd=" + filename)


	##########################################################
	# Exit Modify view without saving
	##########################################################
	def cancelUserModification(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		uHandler = UserHandler(db, cursor)
		pHandler = ProjectDatabaseHandler(db, cursor)
				
		userID = int(form.getvalue('userID'))
		newUser = uHandler.getUserByID(userID)
		
		self.printUserInfo('view', newUser)
		#utils.redirect(hostname + "User.php?View=3&fd=" + filename)

	
	##########################################################
	# Store user details upon exit from Modify view
	##########################################################
	def saveUser(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		uHandler = UserHandler(db, cursor)
		lHandler = LabHandler(db, cursor)
		pHandler = ProjectDatabaseHandler(db, cursor)
		
		ucMapper = UserCategoryMapper(db, cursor)
		category_ID_Name_Map = ucMapper.mapCategoryIDToName()
		
		newProps = {}
		
		# Get form values
		userID = int(form.getvalue("userID"))
		newUser = uHandler.getUserByID(userID)
		
		labID = int(form.getvalue("labs"))
		tmpLab = lHandler.findLabByID(labID)

		# rest of user properties
		username = form.getvalue("username")
		firstName = form.getvalue("firstName")
		lastName = form.getvalue("lastName")
		description = firstName + " " + lastName
		email = form.getvalue("email")
		category = category_ID_Name_Map[int(form.getvalue("system_access_level"))]

		newProps["labID"] = labID
		newProps["username"] = username
		newProps["firstname"] = firstName
		newProps["lastname"] = lastName
		newProps["description"] = description
		newProps["email"] = email
		newProps["category"] = category

		try:
			# Now do an update on database level AND on class level:
			uHandler.updateUserProperties(userID, newProps)			# database update
			
			# Interface level
			newUser.setUsername(username)
			newUser.setFirstName(firstName)
			newUser.setLastName(lastName)
			newUser.setDescription(description)
			newUser.setEmail(email)
			newUser.setLab(tmpLab)
			newUser.setCategory(category)

			# update list of user's projects
			if form.has_key("userProjectsReadonly"):
				# list of IDs
				readonlyProjects = utils.unique(form.getlist("userProjectsReadonly"))
				pHandler.updateUserProjects(userID, readonlyProjects, 'Reader')
			else:
				# safe to assume should delete projects?
				pHandler.deleteMemberProjects(userID, 'Reader')

			if form.has_key("userProjectsWrite"):
				writeProjects = utils.unique(form.getlist("userProjectsWrite"))
				pHandler.updateUserProjects(userID, writeProjects, 'Writer')
			else:
				# safe to assume should delete projects?
				pHandler.deleteMemberProjects(userID, 'Writer')
				
			# think about this
			#newUser.setReadProjects(readProjects)
			#newUser.setWriteProjects(writeProjects)

			# return to detailed view
			self.printUserInfo('view', newUser)
			#utils.redirect(hostname + "User.php?View=3&fd=" + filename)

		except DuplicateUsernameException:
			
			# return to the view with input values and error message
			# Need to construct a dummy User instance to save form values for error output on the next page (otherwise they're lost as soon as Submit is pressed and creation view is exited)
			newLab = lHandler.findLabByID(labID)
			newUser = User(userID, username, firstName, lastName, description, newLab, category, email, "")
			
			self.printUserInfo('edit', newUser, "Dup_un")
			#utils.redirect(hostname + "User.php?View=1&fd=" + filename + "&ErrCode=Dup_un#w1")
		


	##############################################
	# Process Delete User request
	##############################################
	def deleteUser(self, form):
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		uHandler = UserHandler(db, cursor)
		pHandler = ProjectDatabaseHandler(db, cursor)

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		uid = form.getvalue("userID")

		# list of user IDs
		#deletionCandidates = form.getlist("deletionCandidates")
		
		# Delete users and revoke their access to projects
		#for uid in deletionCandidates:
		uHandler.deleteUser(uid)
		pHandler.deleteMemberFromllProjects(uid)
		
		utils.redirect(hostname + "User.php?View=2&Del=1")


	##########################################################
	# Redirect to User detailed view
	##########################################################	
	def viewUser(self, form):
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		uHandler = UserHandler(db, cursor)
		
		userID = form.getvalue("view_user")
		newUser = uHandler.getUserByID(userID)
		
		self.printUserInfo('view', newUser)
		#utils.redirect(hostname + "User.php?View=3&fd=" + filename)
		

	##########################################################
	# Output User Detailed View page
	##########################################################
	def printUserInfo(self, cmd, newUser, errCode = ""):
		userOutput = UserOutputClass()
		userOutput.printUserInfo(cmd, newUser, errCode)


	##########################################################
	# Process Add Lab request
	##########################################################	
	def addLab(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		lHandler = LabHandler(db, cursor)
		ucMapper = UserCategoryMapper(db, cursor)
		category_Name_ID_Map = ucMapper.mapCategoryNameToID()
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`

		# Get form values
		labName = form.getvalue("labName")
		labHeadTitle = form.getvalue("titles")
		labHeadName = form.getvalue("labHead")
		labHead = labHeadTitle + " " + labHeadName
		labCode = form.getvalue("labCode").upper()
		labDescr = form.getvalue("labDescription")
		labAddress = form.getvalue("labAddress")
		labAccess = form.getvalue("system_access_level")
		defaultLabAccessLevel = category_Name_ID_Map[labAccess]		# map to database ID
			
		try:
			newLabID = lHandler.insertLab(labName, labDescr, labAddress, defaultLabAccessLevel, labHead, labCode)
			#print `newLabID`
			newLab = Laboratory(newLabID, labName, labDescr, labAccess, labAddress, labHead, labCode)
			self.printLabInfo('view', newLab)
		
		except DuplicateLabCodeException:
		
			d = DuplicateLabCodeException()
			utils.redirect(hostname + "User.php?View=3&labName=" + labName + "&title=" + labHeadTitle + "&labHead=" + labHeadName + "&labCode=" + labCode + "&labDescr=" + labDescr + "&locn=" + labAddress + "&access=" + labAccess + "&ErrCode=" + `d.err_code()`)
			

	############################################################################
	# All this essentially does is calls the print method of UserOutput class
	############################################################################
	def printLabInfo(self, cmd, newLab, errCode = ""):
		userOutput = UserOutputClass()
		userOutput.printLabInfo(cmd, newLab, errCode)


	##################################################
	# Redirect to lab detailed view
	##################################################
	def viewLab(self, form):
	
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		userOutput = UserOutputClass()
		lHandler = LabHandler(db, cursor)
	
		# request may come from different views, so field names may vary
		if form.has_key("labs"):
			# request received through 'View Labs' menu item
			labID = int(form.getvalue("labs"))
			
		elif form.has_key("labID"):
			# request came from User Detailed View to navigate back to the Lab Detailed View 
			labID = int(form.getvalue("labID"))
		
		else:
			labID = int(form.getvalue("view_lab"))
			
		newLab = lHandler.findLabByID(labID)
		
		self.printLabInfo('view', newLab)
		#utils.redirect(hostname + "User.php?View=4&Lab=" + `labID` + "&fd=" + filename)


	##########################################################
	# Output Lab modification view
	##########################################################	
	def modifyLab(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname

		lHandler = LabHandler(db, cursor)
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		labID = int(form.getvalue("labID"))
		newLab = lHandler.findLabByID(labID)

		self.printLabInfo('edit', newLab)
		#utils.redirect(hostname + "User.php?View=5&Lab=" + `labID` + "&fd=" + filename)


	##########################################################
	# Store lab details upon exit from Modify view
	##########################################################
	def saveLab(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		# Handlers and mappers
		lHandler = LabHandler(db, cursor)
		ucMapper = UserCategoryMapper(db, cursor)
		category_Name_ID_Map = ucMapper.mapCategoryNameToID()
		
		# Get form values
		labID = int(form.getvalue("labID")) 
		lab = Laboratory(labID)			# here need to use the default constructor and not findLabByID, because lab is being updated and need a fresh instance and set its attributes to new values
		
		newName = form.getvalue("labName")
		newLabHead = form.getvalue("labHead")
		newLabCode = form.getvalue("labCode").upper()
		newDescr = form.getvalue("description")
		newAddr = form.getvalue("address")
		newAccess = form.getvalue("system_access_level")
		newAccLev = category_Name_ID_Map[newAccess]
		
		# change database values
		try:
			lHandler.setLabName(labID, newName)
			lHandler.setLabHead(labID, newLabHead)
			lHandler.setLabCode(labID, newLabCode)
			lHandler.setLabDescription(labID, newDescr)
			lHandler.setLabAccessLevel(labID, newAccLev)
			lHandler.setLocation(labID, newAddr)	
		
			#######################
			# update members!
			#######################

			newMembers = form.getlist("labMembers")
			lHandler.updateLabMembers(labID, newMembers)
			
			# change object values
			lab.setName(newName)
			lab.setLabHead(newLabHead)
			lab.setLabCode(newLabCode)
			lab.setDescription(newDescr)
			lab.setAddress(newAddr)
			lab.setDefaultAccessLevel(newAccess)

			# return to detailed view
			self.printLabInfo('view', lab)
			#utils.redirect(hostname + "User.php?View=5&Lab=" + `labID` + "&fd=" + filename)
		
		except DuplicateLabCodeException:
			
			newLab = Laboratory(labID, newName, newDescr, newAccess, newAddr, newLabHead, newLabCode)
			
			d = DuplicateLabCodeException()
			self.printLabInfo('edit', newLab, d.err_code())
			

	#################################################################
	# Cancel Lab modification - return to detailed view w/o saving
	#################################################################
	def cancelLabModification(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname

		lHandler = LabHandler(db, cursor)
		
		labID = int(form.getvalue("labID"))
		lab = lHandler.findLabByID(labID)	# fetch old lab attribute values
		
		self.printLabInfo('view', lab)
		#utils.redirect(hostname + "User.php?View=4&Lab=" + `labID` + "&fd=" + filename)
		
		
		
	##############################################
	# Process Delete User request
	##############################################
	def deleteLab(self, form):
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		lHandler = LabHandler(db, cursor)
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		labID = form.getvalue("labID")

		lHandler.deleteLab(labID)

		utils.redirect(hostname + "User.php?View=5")
		
		
	##############################################
	# July 1, 2010
	##############################################
	def submitBug(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		if form.has_key("request_type"):	# it must, but still
			request_type = form.getvalue("request_type")

		if form.has_key("modules"):
			module = form.getvalue("modules")
			
		if form.has_key("bug_description"):
			bug_description = form.getvalue("bug_description")
			
		if form.has_key("curr_userid"):
			userID = int(form.getvalue("curr_userid"))
		
		# insert into database - NOT MAKING A SEPARATE bug_handler.py module now, no need to!!!
		cursor.execute("INSERT INTO BugReport_tbl(bug_type, module, bug_descr, requested_by) VALUES(" + `request_type` + ", " + `module` + ", " + `bug_description` + ", " + `userID` + ")")
		
		utils.redirect(hostname + "bugreport.php?Req=1")


##########################################################
# Central callable function
##########################################################
def main():

	uReqHandler = UserRequestHandler()
	uReqHandler.handle()

main()
