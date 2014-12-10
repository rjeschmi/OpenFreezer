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

from reagent import Reagent
from user import User

from reagent_handler import ReagentHandler
from user_handler import UserHandler
from location_database_handler import LocationHandler

from general_handler import *
from mapper import *

from session import Session

dbConn = DatabaseConn()
db = dbConn.databaseConnect()

cursor = db.cursor()
hostname = dbConn.getHostname()

rHandler = ReagentHandler(db, cursor)
lHandler = LocationHandler(db, cursor)

###################################################################################################################################
# This is an AJAX function invoked via Javascript from reagent search page
# Written June 8, 2009, by Marina Olhovsky
# Last modified June 8, 2009
###################################################################################################################################
def deleteReagent(form):
	
	if form.has_key("rID"):
		rID = int(form.getvalue("rID"))
		limsID = rHandler.convertDatabaseToReagentID(rID)
		
		#print "Content-type:text/html"
		#print
		#print `form`
		lHandler.deleteExperimentID(rID)
		delStatus = rHandler.deleteReagent(rID)		# if deletion failed (reagent has preps) return 0, else return 1
		
		print "Content-type:text/html"
		print
		print "rID=" + limsID + "&Status=" + `delStatus`


form = cgi.FieldStorage(keep_blank_values="True")
deleteReagent(form)