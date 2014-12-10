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

class Well:
	
	__wellID = 0			# internal database ID
	__wellRowNum = 0		# row number (1 -> 'A', 2 -> 'B', etc.)
	__wellRowChar = ""		# alphanumeric representation of the row number (numbers higher than 26 are represented with more than one character)
	__wellCol = 0			# column number
	
	def __init__(self, wellID, wellRowNum, wellCol, contID):
		
		self.__wellID = wellID
		self.__wellRowNum = wellRowNum
		self.__wellRowChar = self.convertRowNumberToChar(self.__wellRowNum )
		self.__wellCol = wellCol
		self.__contID = contID

	
	#################################################
	# Assignment methods
	#################################################
	def setWellID(self, wellID):
		self.__wellID = wellID
		
		
	def setWellColumn(self, wellCol):
		self.__wellCol = wellCol
		
		
	def setWellRowNumber(self, wellRowNum):
		self.__wellRowNum = wellRowNum
		
	
	def setContainerID(self, contID):
		self.__contID = contID
		
		
	#################################################
	# Access methods
	#################################################
	def getWellID(self):
		return self.__wellID
	
	
	def getWellColumn(self):
		return self.__wellCol
	
	
	def getWellRowNumber(self):
		return self.__wellRowNum
		
		
	def getContainerID(self):
		return self.contID
	
	#################################################
	# Utilities
	#################################################
	@classmethod
	def convertRowNumberToChar(Well, rowNum):
		alphabet = string.letters		# this is only A-Z
		return alphabet[rowNum-1].upper()