#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()
	
import MySQLdb
import sys
import string

from database_conn import DatabaseConn	# april 20/07 
from exception import *
import utils

from mapper import ReagentPropertyMapper, ReagentAssociationMapper, ReagentTypeMapper
from general_handler import ReagentPropertyHandler, ReagentAssociationHandler, AssociationHandler
from reagent_handler import ReagentHandler, InsertHandler
from sequence_handler import DNAHandler, ProteinHandler
from comment_handler import CommentHandler
from system_set_handler import SystemSetHandler

# User and Project info
from user_handler import UserHandler
from project_database_handler import ProjectDatabaseHandler
from session import Session


def preview():
	dbConn = DatabaseConn()
	db = dbConn.databaseConnect()
	
	cursor = db.cursor()
	hostname = dbConn.getHostname()

	rHandler = ReagentHandler(db, cursor)
	propMapper = ReagentPropertyMapper(db, cursor)
	
	prop_Alias_ID_Map = propMapper.mapPropAliasID()
	
	form = cgi.FieldStorage(keep_blank_values="True")

	#print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
	#print					# DITTO
	#print `form`
	
	propVal = ""
	
	if form.has_key("PV"):
		pvVal = form.getvalue("PV")
		pvID = rHandler.convertReagentToDatabaseID(pvVal)
		
	if form.has_key("propAlias"):
		propAlias = form.getvalue("propAlias")
	
	
	if prop_Alias_ID_Map.has_key(propAlias):
		propID = prop_Alias_ID_Map[propAlias]	
		propVal = rHandler.findSimplePropertyValue(pvID, propID)
		
	
	print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
	print					# DITTO
	print propVal
	
preview()