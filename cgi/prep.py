#!/usr/local/bin/python

# python modules
import cgi
import cgitb; cgitb.enable()

import SocketServer
from SocketServer import BaseRequestHandler

import MySQLdb

import os
import tempfile
import stat
import sys
import string

# Custom modules
import utils

from database_conn import DatabaseConn
#from session import Session

from general_handler import GeneralHandler
from laboratory import Laboratory

class PrepProperty:
	
	__propID = 0			# internal database ID
	__propName = ""			# 'Method ID', 'Bacteria', 'Plates/Vial', etc.
	__propValue = ""

	def __init__(self, propID, propName, propValue):
		
		self.__propID = propID
		self.__propName = propName
		self.__propValue = propValue

	
	#################################################
	# Assignment methods
	#################################################
	def setPropID(self, propID):
		self.__propID = propID
		
		
	def setPropName(self, propName):
		self.__propName = propName
		
		
	def setPropValue(self, propValue):
		self.__propValue = propValue
		
		
	#################################################
	# Access methods
	#################################################
	def getPropID(self):
		return self.__propID
	
	
	def getPropName(self):
		return self.__propName
	
	
	def getPropValue(self):
		return self.__propValue