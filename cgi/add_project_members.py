#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()
	
import MySQLdb
import sys
import string

from database_conn import DatabaseConn
from packet import Packet
#from packet_handler import PacketHandler
from exception import *

import utils

def redirect(url):
	"Called to redirect to the given url."
	print 'Location:' + url
	print


# Process Project (Packet) creation request
def add_members():

	dbConn = DatabaseConn()
	
	db = dbConn.databaseConnect()
	cursor = db.cursor()
	hostname = dbConn.getHostname()
		
	form = cgi.FieldStorage(keep_blank_values="True")
	
	pHandler = PacketHandler(db, cursor)
	
	#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
	#print					# DITTO
	#print `form`

	# Get form values
	projectID = form.getvalue("packets")
	projectMembers = form.getlist("projectMembers")
	
	pHandler.insertProjectMembers(projectID, projectMembers)
		
	redirect(hostname)
	
add_members()
