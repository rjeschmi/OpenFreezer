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

from container import Container
from location_type_database_handler import LocationTypeHandler

##################################################################################################################
# Contains functions to handle requests for creation, modification and deletion of containers or container types
#
# Written Sept. 18, 2007, by Marina Olhovsky
# Last modified: Sept. 18, 2007
##################################################################################################################
class LocationTypeRequestHandler:
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
		
		#print "Content-type:text/html"
		#print
		
		form = cgi.FieldStorage(keep_blank_values="True")
		#print `form`
		
		if form.has_key("cont_type_create_button"):
			self.addContainerType(form)
		
		elif form.has_key("cont_type_modify"):
			self.modifyContainerType(form)
		
		elif form.has_key("cont_type_save"):
			self.updateContainerType(form)
			
		elif form.has_key("cont_type_delete"):
			self.deleteContainerType(form)
			
		elif form.has_key("cancel_cont_type_modify"):
			self.cancelContainerTypeModification(form)
		
		cursor.close()
		db.close()
	

	# Add a new container TYPE (i.e. update ContainerGroup_tbl and add prep attributes for it)
	def addContainerType(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
	
		ltHandler = LocationTypeHandler(db, cursor)
		
		# some form values may be blank, so initialize as empty
		contName = ""
		contDesc = ""
		
		#print "Content-type:text/html"
		#print
		#print `form`
		
		contGroupName = form.getvalue("cont_group_name_field")
		contGroupCode = form.getvalue("cont_cont_code_field")
		
		if form.has_key("cont_cont_isolateActive_radio"):
			if form.getvalue("cont_cont_isolateActive_radio").upper() == 'YES':
				isoActive = form.getvalue("cont_cont_isolateActive_radio").upper()
			else:
				isoActive = "NO"
		
		newContTypeFeatures = form.getlist("container_features")
		#print `newContTypeFeatures`
		newContTypeID = ltHandler.insertContainerType(contGroupName, isoActive, contGroupCode)
		
		ltHandler.addContainerTypeFeatures(newContTypeID, newContTypeFeatures)
		
		reagentTypes = form.getlist("cont_cont_group_selection")	# feb. 16/10
		#print `reagentTypes`
		ltHandler.updateContainerReagentTypes(newContTypeID, reagentTypes)
		
		utils.redirect(hostname + "Location.php?View=6&Sub=4&contTypeID=" + `newContTypeID`)


	def modifyContainerType(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
	
		contTypeID = form.getvalue("containerType")
		
		# plain redirect
		utils.redirect(hostname + "Location.php?View=6&Sub=4&contTypeID=" + contTypeID + "&Mod=1")
		
	
	# Update container type information
	def updateContainerType(self, form):
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
	
		ltHandler = LocationTypeHandler(db, cursor)
		
		contTypeID = form.getvalue("containerType")
		
		contName = ""
		contDesc = ""
		
		#print "Content-type:text/html"
		#print
		#print `form`
		
		contTypeID = int(form.getvalue("containerType"))
		
		contGroupName = form.getvalue("cont_group_name_field")
		contGroupCode = form.getvalue("cont_cont_code_field")
		
		if form.has_key("cont_cont_isolateActive_radio"):
			if form.getvalue("cont_cont_isolateActive_radio").upper() == 'YES':
				isoActive = form.getvalue("cont_cont_isolateActive_radio").upper()
				#print isoActive
			else:
				isoActive = "NO"
		else:
			isoActive = ltHandler.findIsolateActive(contTypeID)
			
		newContTypeFeatures = form.getlist("container_features")
		
		reagentTypes = form.getlist("reagent_types")	# feb. 9/10
		
		#print `reagentTypes`

		ltHandler.updateContainerType(contTypeID, contGroupName, contGroupCode, isoActive, newContTypeFeatures, reagentTypes)
		
		utils.redirect(hostname + "Location.php?View=6&Sub=4&contTypeID=" + `contTypeID`)


	def deleteContainerType(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
	
		ltHandler = LocationTypeHandler(db, cursor)
		
		#print "Content-type:text/html"
		#print
		#print `form`
		
		contTypeID = form.getvalue("containerType")
		#print contTypeID
		
		ltHandler.deleteContainerType(contTypeID)
		utils.redirect(hostname + "Location.php?View=6&Sub=4&Del=1")
	
	
	def cancelContainerTypeModification(self, form):
		hostname = self.__hostname
		
		contTypeID = form.getvalue("containerType")
		utils.redirect(hostname + "Location.php?View=6&Sub=4&contTypeID=" + contTypeID)
	
	
##########################################################
# Central callable function
##########################################################
def main():

	ltReqHandler = LocationTypeRequestHandler()
	ltReqHandler.handle()

main()