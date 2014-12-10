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

class Container:
	
	__cont_type = ""	# Vector, Glycerol Stock, Oligo, Insert, Cell Line
	__cont_size = 0		# 96-well plate, 81-slot box, etc.
	__rows = 0
	__cols = 0
	__num_wells = 0		# total number of samples stored in the container
	__cont_name = ""
	__cont_desc = ""
	__cont_lab = None	# Laboratory OBJECT, the lab that owns the container
	
	__properties = []	# list of properties specific to this container's type
	
	
	def __init__(self, cType="", cSize=0, nRows=0, nCols=0, cName=0, cLab=0, cDesc="", c_props=[]):
		
		self.__cont_type = cType
		self.__cont_size = cSize
		self.__rows = nRows
		self.__cols = nCols
		self.__num_wells = nRows * nCols
		self.__cont_name = cName
		self.__cont_desc = cDesc
		self.__cont_lab = Laboratory(cLab)
		
		# Assign properties based on container type
		if cType == 'Vector':
			self.__properties = ['Method ID', 'Concentration']
			
		elif cType == 'Glycerol Stocks':
			self.__properties = ['Bacteria Strain']
			
		elif cType == 'Oligo':
			self.__properties = ['Concentration', 'Reagent Source']
		
		elif cType == 'Insert':
			self.__properties = ['Concentration', 'Alternate ID', "5' digest/3' Digest"]
			
		elif cType == 'Cell Line':
			self.__properties = ['Isolate Name', 'Plates/Vial', 'Date', 'Person', 'Passage']
		
		# Jan. 3/10
		else:
			self.__properties = c_props
	
	#################################################
	# Assignment methods
	#################################################
	def setContainerType(self, cType):
		self.__cont_type = cType
		
		
	def setContainerSize(self, cSize):
		self.__cont_size = cSize
		
		
	def setNumRows(self, nRows):
		self.__rows = nRows
		
	
	def setNumCols(self, nCols):
		self.__cols = nCols
		
	
	def setContainerName(self, cName):
		self.__cont_name = cName
		
		
	def setContainerDescription(self, desc):
		self.__cont_desc = desc
		
		
	def setContainerLab(self, cLab):
		newLab = Laboratory(cLab)
		self.__cont_lab = newLab
		
		
	#################################################
	# Access methods
	#################################################
	def getContainerType(self):
		return self.__cont_type
	
	
	def getContainerSize(self):
		return self.__cont_size
	
	
	def getNumRows(self):
		return self.__rows
	
	
	def getNumCols(self):
		return self.__cols
	
	
	def getNumWells(self):
		
		if self.__num_wells == 0:
			return self.__rows * self.__cols
		else:
			return self.__num_wells
		
		
	def getContainerName(self):
		return self.getContainerName
	
	
	def getContainerDescription(self):
		return self.__cont_desc
	
	
	def getContainerLab(self):
		return self.__cont_lab
	
	
	def getContainerProperties(self):
		return self.__properties