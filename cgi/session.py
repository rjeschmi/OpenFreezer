#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()

import MySQLdb
import os

from database_conn import DatabaseConn
from exception import *

from user import User
from user_handler import UserHandler

from laboratory import Laboratory
from lab_handler import LabHandler

#####################################################################################
# Stores OpenFreezer session information
#
# Written June 27, 2007, by Marina Olhovsky
# Last modified: June 27, 2007
#####################################################################################
class Session:
	
	# Session variables
	__user = None
	
	# ... more to come ...
	
	
	##########################################################
	# Constructor
	##########################################################
	def __init__(self):
		self.__user = None
		
	@classmethod
	def setUser(self, user):
		self.__user = user
				
		
	def setSessionVars(self, user):
		self.__user = user
		
		# ... more to come ...

	@classmethod
	def getUser(self):
		return self.__user
