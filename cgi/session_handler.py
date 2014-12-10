#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()

import SocketServer
from SocketServer import BaseRequestHandler

import MySQLdb

import os
import sys
import string

# Custom modules
import utils

from database_conn import DatabaseConn
from exception import *

from user import User
from user_handler import UserHandler

from laboratory import Laboratory
from lab_handler import LabHandler

from Cookie import SimpleCookie
#####################################################################################
# Implements session handling for OpenFreezer
# Extends abstract class BaseRequestHandler
#
# Written June 27, 2007, by Marina Olhovsky
# Last modified: June 27, 2007
#####################################################################################
class SessionHandler(BaseRequestHandler):
	
	__db = None
	__cursor = None
	__hostname = ""
	
	__user = None
	
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
		self.__user = None
		
		
	##########################################################
	# Override parent method
	##########################################################
	def handle(self):
		
		# For convenience, create local copies of global variables
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		uHandler = UserHandler(db, cursor)
		
		form = cgi.FieldStorage(keep_blank_values="True")
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		if form.has_key("loginsubmit"):
			username = form.getvalue("loginusername_field")
			passwd = form.getvalue("loginpassword_field")
			
			if self.checkPermissions(username, passwd):
				session = SimpleCookie(os.environ['HTTP_COOKIE'])
				phpsessid = session['PHPSESSID'].value
				session["userinfo"] = self.__user

				utils.redirect(os.environ['HTTP_REFERER'])
			
	
	############################################################################
	# Verify that a user is authorized to access the system using uname and pwd
	############################################################################
	def checkPermissions(self, uname, pwd):
	
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		db = self.__db
		cursor = self.__cursor
		
		cursor.execute("SELECT u.userID, u.firstname, u.lastname, u.description, c.category, u.labID, l.lab_name, u.email FROM Users_tbl u, LabInfo_tbl l, UserCategories_tbl c WHERE u.username=" + `uname` + " AND u.password=MD5(" + `pwd` + ") AND u.labID=l.labID AND c.categoryID=u.category AND u.status='ACTIVE' AND l.status='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			uid = int(result[0])
			firstname = result[1]
			lastname = result[2]
			description = result[3]
			category = result[4]
			labID = int(result[5])
			labname = result[6]
			email = result[7]
			
			newLab = Laboratory(labID, labname)
			newUser = User(uid, uname, firstname, lastname, description, newLab, category, email, pwd)
			
			self.__user = newUser
			return True
		else:
			return False
	
			

##########################################################
# Central callable function
##########################################################
def main():

	sessHandler = SessionHandler()
	sessHandler.handle()

main()
