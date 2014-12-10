#!/usr/local/bin/python

#import reportlab

#from reportlab.graphics.shapes import *
#from reportlab.graphics.charts.doughnut import Doughnut
#from reportlab.graphics import widgetbase
#from reportlab.graphics import renderPDF
#from reportlab.lib import *

import reportlab
import math
import random

from reportlab.pdfgen import canvas, pathobject
from reportlab.pdfgen.canvas import *
from reportlab.pdfgen.pathobject import *

from reportlab.graphics import renderPDF
from reportlab.graphics.shapes import *

from reportlab.lib.units import cm
from reportlab.lib.colors import *

import cgi
import cgitb; cgitb.enable()

import MySQLdb
import sys
import os
import stat
import string

from database_conn import DatabaseConn		# april 20/07
from exception import *
import utils

from mapper import ReagentPropertyMapper, ReagentAssociationMapper, ReagentTypeMapper
from general_handler import *
from reagent_handler import *
from comment_handler import CommentHandler
#from system_set_handler import SystemSetHandler
from sequence_handler import DNAHandler, ProteinHandler

from reagent import Reagent, Vector
from sequence_feature import SequenceFeature

# User and Project info
from user_handler import UserHandler
from project_database_handler import ProjectDatabaseHandler
from session import Session

# make global??
dbConn = DatabaseConn()
db = dbConn.databaseConnect()
hostname = dbConn.getHostname()
cursor = db.cursor()

# Handlers and Mappers
aHandler = AssociationHandler(db, cursor)
rHandler = ReagentHandler(db, cursor)
iHandler = InsertHandler(db, cursor)
raHandler = ReagentAssociationHandler(db, cursor)
sHandler = DNAHandler(db, cursor)
pHandler = ReagentPropertyHandler(db, cursor)
packetHandler = ProjectDatabaseHandler(db, cursor)
uHandler = UserHandler(db, cursor)
rtPropHandler = ReagentTypePropertyHandler(db, cursor)

oHandler = OligoHandler(db, cursor)

propMapper = ReagentPropertyMapper(db, cursor)
aMapper = ReagentAssociationMapper(db, cursor)
rMapper = ReagentTypeMapper(db, cursor)

# Various maps
reagentType_Name_ID_Map =  rMapper.mapTypeNameID()
reagentType_ID_Name_Map = rMapper.mapTypeIDName()

assoc_Type_Name_Map = aMapper.mapAssocTypeNameID()
assoc_Name_Alias_Map = aMapper.mapAssocNameAlias()
assoc_Name_Type_Map = aMapper.mapAssocTypeNameID()

prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)
prop_ID_Name_Map = propMapper.mapPropIDName()		# (prop id, prop name)
prop_Name_Alias_Map = propMapper.mapPropNameAlias()	# (propName, propAlias)
prop_Alias_Name_Map = propMapper.mapPropAliasName()	# (propAlias, propName)
prop_Alias_ID_Map = propMapper.mapPropAliasID()		# (propAlias, propID) - e.g. ('insert_type', '48')

prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()

featureNameColorMap = propMapper.mapFeatureNameColor()

#print `featureNameColorMap`

# Get enzymes list for mapping sequence features
enzDict = utils.join(sHandler.sitesDict, sHandler.gatewayDict)
enzDict = utils.join(enzDict, sHandler.recombDict)	# add LoxP
enzDict['None'] = ""					# add 'None'

# currUser is an INT user ID
def getCurrentUserProjects(currUser):
	
	#print "Content-type:text/html"
	#print
	
	# get projects user has AT LEAST Read access to (i.e. if he is explicitly declared a Writer on a project but not declared a Reader, that's allowed)
	currReadProj = packetHandler.findMemberProjects(currUser, 'Reader')
	currWriteProj = packetHandler.findMemberProjects(currUser, 'Writer')
	publicProj = packetHandler.findAllProjects(isPrivate="FALSE")
	
	# list of Packet OBJECTS
	currUserWriteProjects = utils.unique(currReadProj + currWriteProj + publicProj)
	
	if currUser == 1:
		privateProjects = packetHandler.findAllProjects("TRUE")
		currUserWriteProjects = utils.unique(privateProjects + publicProj)
		
	uPackets = []
	
	for p in currUserWriteProjects:
		uPackets.append(p.getNumber())

	return uPackets


def drawMap():
	
	dbConn = DatabaseConn()
	db = dbConn.databaseConnect()
	
	cursor = db.cursor()
	hostname = dbConn.getHostname()
	root_path = dbConn.getRootDir()
	
	form = cgi.FieldStorage(keep_blank_values="True")
	
	#print "Content-type:text/html"
	#print
	#print `form`
	
	if form.has_key("rID"):
		rID = form.getvalue("rID")
	else:	# script executed from command line
		# Worst-case scenarios
		#rID = 260	# V260
		#rID = 230	# V260
		#rID = 80	# V80 - ok
		#rID = 69661	# V2412 - not bad at all, just one of the oligos overlaps exactly with promoter (1 nt difference)
		#rID = 23999 	# V59541
		rID = 97918
		#print rID

	if form.has_key("user_id_hidden"):
		userID = form.getvalue("user_id_hidden")
	else:	# command-line execution
		userID = 1	# debug

	reagentID = rHandler.convertDatabaseToReagentID(rID)
	
	uPackets = getCurrentUserProjects(userID)
	
	#print "Content-type:text/html"
	#print
	#print reagentID
	uPackets.sort()
	#print `uPackets`

	namePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["name"], prop_Category_Name_ID_Map["General Properties"])
	statusPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["status"], prop_Category_Name_ID_Map["General Properties"])
	projectPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["packet id"], prop_Category_Name_ID_Map["General Properties"])
	
	rName = rHandler.findSimplePropertyValue(rID, namePropID)
	rTypeID = rHandler.findReagentTypeID(rID)
	
	try:
		os.remove(root_path + "Reagent/vector_maps/" + reagentID + "_Oligo_map.pdf")
	except OSError:
		pass
	
	#print root_path
	
	c = Canvas(root_path + "Reagent/vector_maps/" + reagentID + "_Oligo_map.pdf")
	
	c.setPageSize((1500,1500))
	c.setStrokeColorRGB(0,0,0)
	c.saveState()
	
	# Draw circle
	origin = 0
	origin_x = 750
	origin_y = 750
	radius = 200
	
	c.circle(origin_x, origin_y, radius)
	c.restoreState()
	
	# Divide circle into 100-unit sectors
	rSeqID = rHandler.findDNASequenceKey(rID)
	rSeq = sHandler.findSequenceByID(rSeqID)
	seqLen = len(rSeq)
	
	#print "Content-type:text/html"
	#print
	#print `rID`
	
	unit_angle_measure = float(360) / float(seqLen)
	#print unit_angle_measure
	
	# Mark 1 on the circle - KEEP, Karen said!
	c.setLineWidth(1)
	c.setStrokeColor(black)
	c.setFillColor(black)
	c.saveState()

	path = c.beginPath()
	
	# Draw a triangle pointing down above the circle
	#path.moveTo(origin_x, origin_y+radius+7)
	#path.lineTo(origin_x-5, origin_y+radius+14)
	#path.lineTo(origin_x+5, origin_y+radius+14)
	#path.lineTo(origin_x, origin_y+radius+7)
	
	# Draw a triangle pointing up inside the circle
	#path.moveTo(origin_x, origin_y+radius-10)
	#path.lineTo(origin_x-5, origin_y+radius-14)
	#path.lineTo(origin_x+5, origin_y+radius-14)
	#path.lineTo(origin_x, origin_y+radius-10)
	
	# Draw a triangle pointing right inside the circle
	path.moveTo(origin_x, origin_y+radius-14)
	path.lineTo(origin_x, origin_y+radius-28)
	path.lineTo(origin_x+8, origin_y+radius-21)
	path.lineTo(origin_x, origin_y+radius-14)
	c.drawPath(path, True, True)
	
	# label 1
	t = c.beginText()
	t.setStrokeColor(black)
	t.setFillColor(black)
	t.setFont("Helvetica-Bold", 16)
	#t.setTextOrigin(origin_x-5, origin_y+radius+19)	# above circle
	t.setTextOrigin(origin_x-3, origin_y+radius-50)	# above circle
	t.textOut("1")
	c.drawText(t)
	
	c.restoreState()

	# Calculate feature segment sizes
	sequenceFeatures = rHandler.findReagentSequenceFeatures(rID)
	#print `sequenceFeatures`

	# Draw legend
	ox_legend = 1280
	oy_legend = 1465
	
	prev_legend = oy_legend
	
	# draw frame
	c.setStrokeColor(darkgray)
	c.setFillColor(white)
	c.saveState()
	
	featureNames = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, prop_Category_Name_ID_Map["DNA Sequence Features"])
	featureNames.sort()

	if len(featureNames) > 15:
		origin_spacer = -19
		coeff = 12
	else:
		origin_spacer = 65
		coeff = 15
	
	origin_legend = prev_legend+origin_spacer-len(featureNames)*coeff
	
	x = (990 - origin_legend) / len(featureNames)
	#print x
	
	if x < 12:
		x = 12
	
	# WHEN WANT TO ADJUST LEGEND GREY BOX HEIGHT: UPDATE THE VALUE ADDED TO legend_height AND THE VALUE SUBTRACTED FROM origin_legend
	legend_height = len(featureNames)*x + 55
	
	c.rect(ox_legend-25, origin_legend-35, 215, legend_height, 1, 1)	# good for specific rtype attributes
	
	c.restoreState()
	
	protocolPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protocol"], prop_Category_Name_ID_Map["Classifiers"])
	seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])
	
	sequenceFeatures = rHandler.findAllReagentFeatures(Vector(rID), rID)
	
	# Print reagent ID and size at the centre of the circle and print name at the top of the page
	c.setFont("Helvetica-Bold", 32)
	c.setFillColor(black)
	c.saveState()
	
	c.drawCentredString(origin_x, origin_y+35, reagentID)
	c.drawCentredString(origin_x, origin_y+10, "nt 1 - " + `seqLen`)
	
	if rName:
		c.setFillColor(blue)	
		c.drawCentredString(origin_x, 1415, rName)
	
	c.restoreState()
	
	# find all Oligos whose protocol is 'sequencing'
	# April 26, 2010: Do not include status in this query, as it might just not be recorded for the oligo at all (early entries)
	cursor.execute("SELECT r.reagentID, r.groupID, s.sequence FROM Sequences_tbl s, ReagentPropList_tbl p1, ReagentPropList_tbl p2, Reagents_tbl r WHERE p1.propertyID=" + `protocolPropID` + " AND p1.propertyValue='sequencing' AND p1.reagentID=r.reagentID AND r.reagentTypeID='3' AND p1.reagentID=p2.reagentID AND p2.propertyID=" + `seqPropID` + " AND p2.propertyValue=s.seqID AND p1.status='ACTIVE' AND p2.status='ACTIVE' AND r.status='ACTIVE' AND s.status='ACTIVE'")
	
	#print "SELECT r.reagentID, r.groupID, s.sequence FROM Sequences_tbl s, ReagentPropList_tbl p1, ReagentPropList_tbl p2, Reagents_tbl r WHERE p1.propertyID=" + `protocolPropID` + " AND p1.propertyValue='sequencing' AND p1.reagentID=r.reagentID AND r.reagentTypeID='3' AND p1.reagentID=p2.reagentID AND p2.propertyID=" + `seqPropID` + " AND p2.propertyValue=s.seqID AND p1.status='ACTIVE' AND p2.status='ACTIVE' AND r.status='ACTIVE' AND s.status='ACTIVE'"
	
	results = cursor.fetchall()
	
	rSeq = utils.squeeze(rSeq).lower()
	#print rSeq
	
	oligoFeatures = []
	
	oligoIDsDict = {}	# O123 => 123
	oligoNamesDict = {}	# 123 => "O123 Name"
	oligoProjectDict = {}	# 123 => 7
	
	for result in results:
		
		if result:
			oligoID = int(result[0])
			groupID = int(result[1])
			
			oligo_lims_id = "O" + `groupID`
			oligoIDsDict[oligo_lims_id] = oligoID
			#print oligo_lims_id
			
			oligoName = rHandler.findSimplePropertyValue(oligoID, namePropID)
			
			# April 26, 2010, Karen's request: Ignore Oligos whose status is 'Failed' or 'Do Not Use'
			oligoStatus = rHandler.findSimplePropertyValue(oligoID, statusPropID)

			if oligoStatus:
				#print oligoStatus
				if oligoStatus.lower() == 'failed' or oligoStatus.lower() == 'do not use':
					continue
			
			if oligoName:
				oligoNamesDict[oligoID] = oligoName
			else:
				oligoNamesDict[oligoID] = ""
			
			#cursor.execute("SELECT propertyValue FROM ReagentPropList_tbl WHERE reagentID=" + `oligoID` + " AND propertyID=" + `namePropID` + " AND status='ACTIVE'")
			#result2 = cursor.fetchone()
			
			#if result2:
				#oligoName = result2[0]
				#oligoNamesDict[oligoID] = oligoName
			#else:
				#oligoNamesDict[oligoID] = ""
			
			# PROJECT - MUST CAST TO INT
			oligoPacket = int(rHandler.findSimplePropertyValue(oligoID, projectPropID))
			#print oligoPacket
			
			if oligoPacket:
				if oligoPacket not in uPackets:
					#print oligoPacket
					continue
			
			#cursor.execute("SELECT propertyValue FROM ReagentPropList_tbl WHERE reagentID=" + `oligoID` + " AND propertyID=" + `projectPropID` + " AND status='ACTIVE'")
			#result3 = cursor.fetchone()
			
			#if result3:
				#oligoPacket = int(result3[0])
				##print oligoPacket
				
				#if oligoPacket not in uPackets:
					##print oligoPacket
					#continue
				
				#oligoProjectDict[oligoID] = oligoPacket
			#else:
				#oligoProjectDict[oligoID] = 0
			
			if len(result) == 3:
				oligoSeq = result[2].strip().lower()
				#print oligoSeq
				
				tm = sHandler.calculateTm(oligoSeq)
				
				# Changes made April 26, 2010: Karen caught that the sense/antisense check does not work for T3 and T7 vectors and said to check oligo sequence both ways
				#if rSeq.find(oligoSeq) < 0:
					#continue
					
				if rSeq.find(oligoSeq) >= 0:
					fStart = rSeq.find(oligoSeq) + 1
					fEnd = fStart + len(oligoSeq) - 1
					
					#if oHandler.isSenseOligo(oligoID):
					oFeature = SequenceFeature("sequencing primer", oligo_lims_id, fStart, fEnd, 'forward', oligoSeq, tm)
					#else:
						#oFeature = SequenceFeature("sequencing primer", oligo_lims_id, fStart, fEnd, 'reverse', oligoSeq, tm)
					
				else:
					revSeq = sHandler.reverse_complement(oligoSeq)
					
					if rSeq.find(revSeq) < 0:
						continue
					
					fStart = rSeq.find(revSeq) + 1
					fEnd = fStart + len(revSeq)
					
					# Note: start pos > end pos, as we're talking about reverse Oligo sequence
					revStart = fEnd - 1
					revEnd = fStart
				
					# Still, record sense/antisense for displaying on the map ('forward' and 'reverse' here are just static values in Feature class, it doesn't reflect sequence orientation in our case - just use as a temporary placeholder)
					#if oHandler.isSenseOligo(oligoID):
						#oligoFeatures.append(oFeature)
						#oFeature = SequenceFeature("sequencing primer", oligo_lims_id, revStart, revEnd, 'forward', oligoSeq, tm)
					#else:
					oFeature = SequenceFeature("sequencing primer", oligo_lims_id, revStart, revEnd, 'reverse', oligoSeq, tm)
				
				oligoFeatures.append(oFeature)
	
				sequenceFeatures.append(oFeature)

	# SORT features by size, so that short features are not hidden behind the long ones
	fSizes = []
	sortedFeatures = []
	
	fSortedPos = {}
	
	fStartPos = []
	fEndPos = []
	
	for feature in sequenceFeatures:
		fSize = int(feature.getFeatureSize())
		
		if fSize > 0:
			fSizes.append(fSize)
		
		#if fSize > 150:
		f_start_tmp = int(feature.getFeatureStartPos())
		fStartPos.append(f_start_tmp)
		
		fSortedPos[f_start_tmp] = feature
	
		f_end_tmp = int(feature.getFeatureEndPos())
		fEndPos.append(f_end_tmp)
		
	
	fEndPos.sort()
	fStartPos.sort()
	
	#print `fStartPos`
	#print `fEndPos`
	
	fSizes.sort(reverse=True)
	#print `fSizes`
	
	for fs in fSizes:
		for feature in sequenceFeatures:
			fSize = feature.getFeatureSize()

			# added existence check July 17/08 - different features may have same sizes so end up with duplicate features in list (e.g. cloning sizes appeared twice on the map)
			if fs == fSize and feature not in sortedFeatures:
				sortedFeatures.append(feature)
	
	# Order: 5' site, 3' site, 5' linker, 3' linker, then the rest
	sites_color = featureNameColorMap["5' cloning site"]		# same for 3' site
	
	# 5' site
	c.setStrokeColor(sites_color)
	c.setFillColor(sites_color)
	c.saveState()
	c.rect(ox_legend-15, prev_legend-20, 25,8, 1, 1)
	c.restoreState
	
	t = c.beginText()
	t.setStrokeColor(sites_color)
	t.setFillColor(sites_color)
	t.setFont("Helvetica-Bold", 10)
	t.setTextOrigin(ox_legend+12, prev_legend-20)
	t.textOut(" - " + "5' CLONING SITE")
	c.drawText(t)
	c.restoreState
	
	prev_legend = prev_legend-15
	
	# 3' site
	c.setStrokeColor(sites_color)
	c.setFillColor(sites_color)
	c.saveState()
	c.rect(ox_legend-15, prev_legend-20, 25,8, 1, 1)
	c.restoreState
	
	t = c.beginText()
	t.setStrokeColor(sites_color)
	t.setFillColor(sites_color)
	t.setFont("Helvetica-Bold", 10)
	t.setTextOrigin(ox_legend+12, prev_legend-20)
	t.textOut(" - " + "3' CLONING SITE")
	c.drawText(t)
	c.restoreState

	prev_legend = prev_legend-15
	
	# Show legend for linkers
	linkers_color = featureNameColorMap["5' linker"]
	
	# 5' linker
	c.setStrokeColor(linkers_color)
	c.setFillColor(linkers_color)
	c.saveState()
	c.rect(ox_legend-15, prev_legend-20, 25,8, 1, 1)
	c.restoreState
	
	t = c.beginText()
	t.setStrokeColor(linkers_color)
	t.setFillColor(linkers_color)
	t.setFont("Helvetica-Bold", 10)
	t.setTextOrigin(ox_legend+12, prev_legend-20)
	t.textOut(" - " + "5' LINKER")
	c.drawText(t)
	c.restoreState

	prev_legend = prev_legend-15
	
	# 3' linker
	c.setStrokeColor(linkers_color)
	c.setFillColor(linkers_color)
	c.saveState()
	c.rect(ox_legend-15, prev_legend-20, 25,8, 1, 1)
	c.restoreState
	
	t = c.beginText()
	t.setStrokeColor(linkers_color)
	t.setFillColor(linkers_color)
	t.setFont("Helvetica-Bold", 10)
	t.setTextOrigin(ox_legend+12, prev_legend-20)
	t.textOut(" - " + "3' LINKER")
	c.drawText(t)
	c.restoreState

	prev_legend = prev_legend-15
	
	# Output the rest of the features in alphabetical order
	featureNames = featureNameColorMap.keys()
	featureNames.sort()
	
	#print `featureNames`
	
	for featureName in featureNames:
		#print featureName
		if featureNameColorMap.has_key(featureName):
			color = featureNameColorMap[featureName]
			
			if featureName != "5' cloning site" and featureName != "3' cloning site" and featureName != "5' linker" and featureName != "3' linker" and color != None:
				#print featureName
				c.setStrokeColor(color)
				c.setFillColor(color)
				c.saveState()
				c.rect(ox_legend-15, prev_legend-20, 25,8, 1, 1)
				c.restoreState
				
				t = c.beginText()
				t.setStrokeColor(color)
				t.setFillColor(color)
				t.setFont("Helvetica-Bold", 10)
				t.setTextOrigin(ox_legend+12, prev_legend-20)
				t.textOut(" - " + featureName.upper())
				c.drawText(t)
				
				c.restoreState
				
				prev_legend = prev_legend-15

	# Sequencing Primer
	oligoColor = "#7cfc00"
	
	c.setStrokeColor(oligoColor)
	c.setFillColor(oligoColor)
	c.saveState()
	c.rect(ox_legend-15, prev_legend-20, 25,8, 1, 1)
	c.restoreState
	
	t = c.beginText()
	t.setStrokeColor(oligoColor)
	t.setFillColor(oligoColor)
	t.setFont("Helvetica-Bold", 10)
	t.setTextOrigin(ox_legend+12, prev_legend-20)
	t.textOut(" - " + "SEQUENCING PRIMER")
	c.drawText(t)
	c.restoreState

	prev_legend = prev_legend-15
	
	ox_labels = 40
	oy_labels = 40
	#prev_legend = oy_labels
	
	for feature in sortedFeatures:
		
		fType = feature.getFeatureType()
		fValue = feature.getFeatureName()
		fSize = feature.getFeatureSize()
		
		#print fType
		#print fValue
		#print fSize
		
		# color
		if featureNameColorMap.has_key(fType):
			fColor = featureNameColorMap[fType]
			textColor = fColor
			
		elif fType == 'sequencing primer':
			fColor = "#7cfc00"
			textColor = "black"

			o_id = oligoIDsDict[fValue]
			oligoName = oligoNamesDict[o_id]
			
			if len(oligoName) > 0:
				fValue += " " + oligoName
		
		# property name
		if fType == 'cdna insert':
			fValue = "cDNA Insert"
			
		elif fType == 'promoter':
			fValue = fValue + " " + fType
		
		fStart = feature.getFeatureStartPos()
		fEnd = feature.getFeatureEndPos()
		fDir = feature.getFeatureDirection()
		
		# value
		#if fType == 'sequencing primer':
		
			#oligoDir = feature.getFeatureDirection()
		
			#if oligoDir == 'forward':
				#oligoType = 'Sense'
			#else:
				#oligoType = 'Antisense'
			
			#fTxt = fValue + " (" + `fStart` + "-" + `fEnd` + "), " + oligoDir
		#else:
		
		fTxt = fValue + " (" + `fStart` + "-" + `fEnd` + ")"
			
		if fSize > 0:
			f_start = fStart * unit_angle_measure
		
			#print "Start " + `fStart`
			#print "End " + `fEnd`
		
			startAngle = 90 - f_start
			#print "Start angle " + `startAngle`
			
			f_end = fEnd * unit_angle_measure
			endAngle = 90 - f_end
			#print "End angle " + `endAngle`
			
			extAngle = -1*(f_end - f_start)
			#print "Ext angle " + `extAngle`

			x1 = origin_x - radius
			y1 = origin_y - radius
			
			x2 = origin_x + radius
			y2 = origin_y + radius
			
			p = c.beginPath()
			
			c.setLineWidth(10)
			c.setLineJoin(1)
			
			c.setStrokeColor(fColor)
			c.saveState()
			
			p.arc(x1, y1, x2, y2, startAngle, extAngle)
			c.drawPath(p)
			c.restoreState()
			
			# common to all
			startAngle_rad = (startAngle * math.pi) / 180.0
			endAngle_rad = (endAngle * math.pi) / 180.0
			
			c.setStrokeColor(fColor)
			c.setFillColor(fColor)
			c.setFont("Helvetica-Bold", 9)
			c.saveState()
			
			arc_x_start = origin_x + (radius+5)*math.cos(startAngle_rad)
			arc_y_start = origin_y + (radius+5)*math.sin(startAngle_rad)
			
			arc_x_end = origin_x+(radius+5)*math.cos(endAngle_rad)
			arc_y_end = origin_y+(radius+5)*math.sin(endAngle_rad)
			
			# draw label
			#c.setStrokeColorRGB(0,0,1)
			#c.setFillColorRGB(0,0,1)
			c.setStrokeColor(fColor)
			c.setFillColor(fColor)
			c.setFont("Helvetica-Bold", 12)
			c.saveState()
			
			# draw line
			delta = 45
			
			if fStart < seqLen/2:
				
				if arc_y_start > origin_y:
					
					# THIS WORKS!!!!
					c.setStrokeColor(fColor)
					c.setFillColor(fColor)
					c.setLineWidth(1)
					c.saveState()
					
					fInd = fStartPos.index(fStart)
					fY = len(fStartPos) - fInd

					c.line(arc_x_start, arc_y_start, arc_x_start+math.fabs(math.sin(delta))*(fInd*10), arc_y_start+math.fabs(math.cos(delta))*(fY*10))

					#c.setStrokeColorRGB(0,0,1)
					#c.setFillColorRGB(0,0,1)

					c.setStrokeColor(textColor)
					c.setFillColor(textColor)
		
					c.setFont("Helvetica-Bold", 8)
					c.saveState()

					#c.drawString(arc_x_start+math.fabs(math.sin(delta))*(fInd*10)+2, arc_y_start+math.fabs(math.cos(delta))*(fY*10)-math.cos(delta)*0.9, fValue + " (" + `fStart` + "-" + `fEnd` + ")")
					
					c.drawString(arc_x_start+math.fabs(math.sin(delta))*(fInd*10)+2, arc_y_start+math.fabs(math.cos(delta))*(fY*10)-math.cos(delta)*0.9, fTxt)

					c.restoreState()
					
				else:
					fInd = fStartPos.index(fStart)
					fY = len(fStartPos) - fInd

					c.setStrokeColor(fColor)
					c.setFillColor(fColor)
					c.setLineWidth(1)
					c.saveState()
					
					c.line(arc_x_start, arc_y_start, arc_x_start+math.fabs(math.sin(delta))*(fY*10), arc_y_start-math.fabs(math.cos(delta))*(fInd*10))
				
					c.restoreState()
					
					#c.setStrokeColorRGB(0,0,1)
					#c.setFillColorRGB(0,0,1)
					
					c.setStrokeColor(textColor)
					c.setFillColor(textColor)
		
					c.setFont("Helvetica-Bold", 8)
					c.saveState()
				
					#c.drawString(arc_x_start+math.fabs(math.sin(delta))*(fY*10)+2, arc_y_start-math.fabs(math.cos(delta))*(fInd*10)-math.cos(delta)*13, fValue + " (" + `fStart` + "-" + `fEnd` + ")")
					
					c.drawString(arc_x_start+math.fabs(math.sin(delta))*(fY*10)+2, arc_y_start-math.fabs(math.cos(delta))*(fInd*10)-math.cos(delta)*13, fTxt)
					
					c.restoreState()
			else:
				if arc_y_start > origin_y:
					
					fInd = fStartPos.index(fStart)
					fY = len(fStartPos) - fInd

					c.setStrokeColor(fColor)
					c.setFillColor(fColor)
					c.setLineWidth(1)
					c.saveState()
					
					c.line(arc_x_start, arc_y_start, arc_x_start-math.fabs(math.sin(delta)*(fY*10)), arc_y_start+math.fabs(math.cos(delta)*(fInd*10)))
					
					c.restoreState()
					
					# draw label
					#c.setStrokeColorRGB(0,0,1)
					#c.setFillColorRGB(0,0,1)
					
					c.setStrokeColor(textColor)
					c.setFillColor(textColor)
		
					c.setFont("Helvetica-Bold", 8)
					c.saveState()
					
					#c.drawRightString(arc_x_start-math.fabs(math.sin(delta)*(fY*10))+2, arc_y_start+math.fabs(math.cos(delta)*(fInd*10)), fValue + " (" + `fStart` + "-" + `fEnd` + ")")
					
					c.drawRightString(arc_x_start-math.fabs(math.sin(delta)*(fY*10))+2, arc_y_start+math.fabs(math.cos(delta)*(fInd*10)), fTxt)
					
					c.restoreState()
				else:
					
					fInd = fStartPos.index(fStart)
					fY = len(fStartPos) - fInd

					c.setStrokeColor(fColor)
					c.setFillColor(fColor)
					c.setLineWidth(1)
					c.saveState()
					
					c.line(arc_x_start, arc_y_start, arc_x_start-math.fabs(math.sin(delta)*(fInd*10)), arc_y_start-math.fabs(math.cos(delta)*(fY*10)))
				
					c.restoreState()
					
					# draw label
					#c.setStrokeColorRGB(0,0,1)
					#c.setFillColorRGB(0,0,1)
					
					c.setStrokeColor(textColor)
					c.setFillColor(textColor)

					c.setFont("Helvetica-Bold", 8)
					c.saveState()
					
					#c.drawRightString(arc_x_start-math.fabs(math.sin(delta)*(fInd*10))-2, arc_y_start-math.fabs(math.cos(delta)*(fY*10))-math.cos(delta)*12, fValue + " (" + `fStart` + "-" + `fEnd` + ")")
					
					c.drawRightString(arc_x_start-math.fabs(math.sin(delta)*(fInd*10))-2, arc_y_start-math.fabs(math.cos(delta)*(fY*10))-math.cos(delta)*12, fTxt)
					
					c.restoreState()

	# print Oligo info
	#prev_legend = prev_legend-10
	prev_legend = 1365
	
	t = c.beginText()
	t.setStrokeColor(black)
	t.setFillColor(black)
	t.setFont("Helvetica-Bold", 10)
	t.setTextOrigin(ox_labels, prev_legend-15)
	t.textOut("Sequencing primers for " + reagentID + ":")
	c.drawText(t)
	c.restoreState

	prev_legend = prev_legend-18
	
	fColor = "#7cfc00"

	# sort oligos
	tmp_oligos = {}
	
	#print `oligoFeatures`
	
	for oFeature in oligoFeatures:
		oligoID = oFeature.getFeatureName()
		#print oligoID
		oligoStart = oFeature.getFeatureStartPos()
		#print oligoStart
		
		if tmp_oligos.has_key(oligoStart):
			tmp_o_list = tmp_oligos[oligoStart]
		else:
			tmp_o_list = []
			tmp_oligos[oligoStart] = oFeature
		
		tmp_o_list.append(oFeature)
		tmp_oligos[oligoStart] = tmp_o_list
		
		oligoSeq = oFeature.getFeatureDescrType()
		#print oligoSeq
	
	#print `tmp_oligos`

	for oligoStart in sorted(tmp_oligos.keys()):
		oFeatures = tmp_oligos[oligoStart]
		
		for oFeature in oFeatures:
			oligoID = oFeature.getFeatureName()
			#oligoSeq = oFeature.getFeatureDescrType()
			oligoTm = oFeature.getFeatureDescrName()
			oligoEnd = oFeature.getFeatureEndPos()
			oligoDir = oFeature.getFeatureDirection()
			
			o_rID = oligoIDsDict[oligoID]
			oligoName = oligoNamesDict[o_rID]
			
			#if oligoDir == 'forward':
				#oligoType = 'Sense'
			#else:
				#oligoType = 'Antisense'
			
			c.setStrokeColor(fColor)
			c.setFillColor(fColor)
			c.saveState()
			c.rect(ox_labels+5, prev_legend-15, 15,6, 1, 1)
			c.restoreState
		
			t = c.beginText()
			t.setStrokeColor(black)
			t.setFillColor(black)
			t.setFont("Helvetica-Bold", 10)
			t.setTextOrigin(ox_labels+25, prev_legend-15)
			
			if len(oligoName) > 0:
				#t.textOut(oligoID + ": " + oligoName + " (" + `oligoStart` + "-" + `oligoEnd` + ")" ", " + oligoType)
				t.textOut(oligoID + ": " + oligoName + " (" + `oligoStart` + "-" + `oligoEnd` + ")" ", " + oligoDir)
			else:
				#t.textOut(oligoID + ": (" + `oligoStart` + "-" + `oligoEnd` + ")" ", " + oligoType)
				t.textOut(oligoID + ": (" + `oligoStart` + "-" + `oligoEnd` + ")" ", " + oligoDir)
			
			#print oligoID + ": (" + `oligoStart` + "-" + `oligoEnd` + ")" ", " + oligoType
			
			c.drawText(t)
			c.restoreState
			prev_legend = prev_legend-15
		
			c.restoreState()
	
	c.showPage()
	c.save()
		
	# Feb. 2/09: Change permissions
	#os.chmod(root_path + "Reagent/vector_maps/" + reagentID + "_Oligo_map.pdf", stat.S_IMODE(stat.S_IRWXU | stat.S_IRWXO | stat.S_IRWXG))
	
drawMap()