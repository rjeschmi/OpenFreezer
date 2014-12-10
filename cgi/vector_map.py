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
from reagent_handler import ReagentHandler, InsertHandler
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

''' removed Jan. 26, 2010
def loadColors():
	
	dbConn = DatabaseConn()
	db = dbConn.databaseConnect()
	
	cursor = db.cursor()
	hostname = dbConn.getHostname()

	cursor.execute("SELECT propertyName FROM ReagentPropType_tbl WHERE status='ACTIVE'")
	props = cursor.fetchall()
	
	features = Vector.getSequenceFeatures() + Vector.getSingleFeatures()
	#print `features`
	allColors = colors.getAllNamedColors()
	#print `allColors`
	i = 0
	fColors = allColors.values()
	
	for prop in props:
		propName = prop[0]
		
		if propName in features:
			fColor = fColors.pop(i)
			color = `fColor.rgb()`
			#print `color`
			#print "UPDATE ReagentPropType_tbl SET propertyColor=" + `color` + " WHERE propertyName=" + `propName` + " AND status='ACTIVE'"
			cursor.execute("UPDATE ReagentPropType_tbl SET propertyColor=" + `color` + " WHERE propertyName=" + `propName` + " AND status='ACTIVE'")
			i += 1
'''

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
	else:
		#rID = 148913	# V4550
		rID = 29125
		#rID = 72 
		#rID = 309
		#rID = 1901	# small feature labels @ start of seq. overlap
		#rID = 36415
		#print rID

	reagentID = rHandler.convertDatabaseToReagentID(rID)
	
	#print "Content-type:text/html"
	#print
	#print reagentID
	
	namePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["name"], prop_Category_Name_ID_Map["General Properties"])
		
	rName = rHandler.findSimplePropertyValue(rID, namePropID)
	#rName = rHandler.findSimplePropertyValue(rID, prop_Name_ID_Map["name"])
	rTypeID = rHandler.findReagentTypeID(rID)
	
	try:
		os.remove(root_path + "Reagent/vector_maps/" + reagentID + "_map.pdf")
	except OSError:
		pass
	
	#print root_path
	
	c = Canvas(root_path + "Reagent/vector_maps/" + reagentID + "_map.pdf")
	
	c.setPageSize((1000,1000))
	c.setStrokeColorRGB(0,0,0)
	c.saveState()
	
	# Draw circle
	origin = 0
	origin_x = 500
	origin_y = 470
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
	path.moveTo(origin_x, origin_y+radius+7)
	path.lineTo(origin_x-5, origin_y+radius+14)
	path.lineTo(origin_x+5, origin_y+radius+14)
	path.lineTo(origin_x, origin_y+radius+7)
	c.drawPath(path, True, True)
	
	# label 1
	t = c.beginText()
	t.setStrokeColor(black)
	t.setFillColor(black)
	t.setFont("Helvetica-Bold", 16)
	t.setTextOrigin(origin_x-5, origin_y+radius+19)
	t.textOut("1")
	c.drawText(t)
	
	c.restoreState()

	# Calculate feature segment sizes
	sequenceFeatures = rHandler.findReagentSequenceFeatures(rID)
	#print `sequenceFeatures`
	
	# Draw legend
	ox_legend = 800
	oy_legend = 985
	
	prev_legend = oy_legend
	
	# draw frame
	c.setStrokeColor(darkgray)
	#c.setStrokeColor(white)
	#c.setFillColor(lightgrey)
	c.setFillColor(white)
	c.saveState()
	
	# Output the rest of the features in alphabetical order - Moved here Jan. 26/10
	#featureNames = featureNameColorMap.keys()
	
	#print `featureNameColorMap.keys()`
	#print len(featureNameColorMap.keys())
	
	#fnames = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, prop_Category_Name_ID_Map["DNA Sequence Features"])
	#fnames.remove('expression system')
	#fnames.remove('tag position')
	
	#print `fnames`
	#print len(fnames)
	
	featureNames = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, prop_Category_Name_ID_Map["DNA Sequence Features"])
	featureNames.sort()
	
	#print `featureNames`
	#print prev_legend-240
	#print prev_legend-15-len(featureNames)*15
	
	#c.rect(ox_legend-25, prev_legend-240, 215, 235, 1, 1)
	#c.rect(ox_legend-25, prev_legend-15-len(featureNames)*15, 215, 10+len(featureNames)*15, 1, 1)	# good for list of all db features
	
	if len(featureNames) > 15:
		origin_spacer = -19
		coeff = 12
	else:
		origin_spacer = 65
		coeff = 15
	
	#origin_legend = prev_legend+65-len(featureNames)*15
	origin_legend = prev_legend+origin_spacer-len(featureNames)*coeff
	
	x = (990 - origin_legend) / len(featureNames)
	#print x
	
	if x < 12:
		x = 12
		
	legend_height = len(featureNames)*x + 15
	#print legend_height
	
	c.rect(ox_legend-25, origin_legend-17, 215, legend_height, 1, 1)	# good for specific rtype attributes
	
	c.restoreState()
	
	# Order: 5' site, 3' site, 5' linker, 3' linker, then the rest
	sites_color = featureNameColorMap["5' cloning site"]		# same for 3' site
	
	# 5' site
	c.setStrokeColor(sites_color)
	c.setFillColor(sites_color)
	c.saveState()
	c.rect(ox_legend-15, prev_legend-20, 25,8, 1, 1)
	c.restoreState
	
	t = c.beginText()
	#t.setStrokeColor(black)
	t.setStrokeColor(sites_color)
	#t.setFillColor(black)
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
	#t.setStrokeColor(black)
	#t.setFillColor(black)

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
	#t.setStrokeColor(black)
	#t.setFillColor(black)
	
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
	#t.setStrokeColor(black)
	#t.setFillColor(black)

	t.setStrokeColor(linkers_color)
	t.setFillColor(linkers_color)

	t.setFont("Helvetica-Bold", 10)
	t.setTextOrigin(ox_legend+12, prev_legend-20)
	t.textOut(" - " + "3' LINKER")
	c.drawText(t)
	c.restoreState

	prev_legend = prev_legend-15
	
	## Output the rest of the features in alphabetical order
	#featureNames = featureNameColorMap.keys()
	#featureNames.sort()
	
	#print `featureNames`
	
	for featureName in featureNames:
		if featureNameColorMap.has_key(featureName):
			color = featureNameColorMap[featureName]
		#else:
			#continue
			
			if featureName != "5' cloning site" and featureName != "3' cloning site" and featureName != "5' linker" and featureName != "3' linker" and color != None:
				#print featureName
				c.setStrokeColor(color)
				c.setFillColor(color)
				c.saveState()
				c.rect(ox_legend-15, prev_legend-20, 25,8, 1, 1)
				c.restoreState
				
				t = c.beginText()
				
				#t.setStrokeColor(black)
				#t.setFillColor(black)
				
				t.setStrokeColor(color)
				t.setFillColor(color)

				t.setFont("Helvetica-Bold", 10)
				t.setTextOrigin(ox_legend+12, prev_legend-20)
				t.textOut(" - " + featureName.upper())
				c.drawText(t)
				
				c.restoreState
				
				prev_legend = prev_legend-15
	#print prev_legend
	
	# Print reagent ID and size at the centre of the circle and print name at the top of the page
	c.setFont("Helvetica-Bold", 32)
	c.setFillColor(black)
	c.saveState()
	
	c.drawCentredString(origin_x, origin_y+35, reagentID)
	c.drawCentredString(origin_x, origin_y+10, "nt 1 - " + `seqLen`)
	
	if rName:
		c.setFillColor(blue)
		c.drawCentredString(origin_x, 935, rName)
	
	c.restoreState()
	
	# SORT features by size, so that short features are not hidden behind the long ones
	fSizes = []
	sortedFeatures = []
	
	for feature in sequenceFeatures:
		fSize = int(feature.getFeatureSize())
		
		if fSize > 0:
			fSizes.append(fSize)
	
	fSizes.sort(reverse=True)
	#print `fSizes`
	
	for fs in fSizes:
		for feature in sequenceFeatures:
			fSize = feature.getFeatureSize()

			# added existence check July 17/08 - different features may have same sizes so end up with duplicate features in list (e.g. cloning sizes appeared twice on the map)
			if fs == fSize and feature not in sortedFeatures:
				sortedFeatures.append(feature)
	
	#print `sortedFeatures`
	
	ox_labels = 40
	oy_labels = 910
	prev_legend = oy_labels

	t = c.beginText()
	t.setStrokeColor(black)
	t.setFillColor(black)
	t.setFont("Helvetica-Bold", 10)
	t.setTextOrigin(ox_labels, prev_legend-15)
	t.textOut("Features shorter than 150 nt:")
	c.drawText(t)
	c.restoreState

	prev_legend = prev_legend-18

	#for feature in sequenceFeatures:
	for feature in sortedFeatures:
		fType = feature.getFeatureType()
		fValue = feature.getFeatureName()
		fSize = feature.getFeatureSize()
		
		#print fType
		#print fValue
		#print fSize
		
		fColor = featureNameColorMap[fType]
		
		if fType == 'cdna insert':
			fValue = "cDNA Insert"
			
		elif fType == 'promoter':
			fValue = fValue + " " + fType
		
		fStart = feature.getFeatureStartPos()
		fEnd = feature.getFeatureEndPos()
		fDir = feature.getFeatureDirection()
		
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
			#t = c.beginText()
			
			c.setLineWidth(10)
			c.setLineJoin(1)
			
			fColor = featureNameColorMap[fType]
			c.setStrokeColor(fColor)
			c.saveState()
			
			p.arc(x1, y1, x2, y2, startAngle, extAngle)
			c.drawPath(p)
			c.restoreState()
			
			## jan. 27/10: this makes a contour for each arc
			#p = c.beginPath()
			#c.setLineWidth(1)
			
			##fColor = featureNameColorMap[fType]
			#c.setStrokeColor(black)
			#c.setFillColor(fColor)
			#c.saveState()
			
			#p.arc(x1+5, y1+5, x2-5, y2-5, startAngle, extAngle)
			#c.drawPath(p)
			##c.restoreState()
			
			##c.setLineWidth(1)
			
			##fColor = featureNameColorMap[fType]
			##c.setStrokeColor(black)
			##c.setFillColor(fColor)
			##c.saveState()
			
			#p.arc(x1-5, y1-5, x2+5, y2+5, startAngle, extAngle)
			#c.drawPath(p)
			#c.restoreState()	# jan. 27/10 modification ends here
			
			
			# common to all
			startAngle_rad = (startAngle * math.pi) / 180.0
			endAngle_rad = (endAngle * math.pi) / 180.0
			
			#c.setStrokeColor(black)
			#c.setFillColor(black)
			
			c.setStrokeColor(fColor)
			c.setFillColor(fColor)
			
			c.setFont("Helvetica-Bold", 9)
			c.saveState()
			
			arc_x_start = origin_x + (radius+5)*math.cos(startAngle_rad)
			arc_y_start = origin_y + (radius+5)*math.sin(startAngle_rad)
			
			arc_x_end = origin_x+(radius+5)*math.cos(endAngle_rad)
			arc_y_end = origin_y+(radius+5)*math.sin(endAngle_rad)
			
			# draw label
			c.setStrokeColorRGB(0,0,1)
			c.setFillColorRGB(0,0,1)
			c.setFont("Helvetica-Bold", 12)
			c.saveState()
			
			# draw line
			delta = 45
			
			# Rotate labels only for SMALL features (they can be crammed together)
			#if fSize > 100:
				#delta = 45
			#else:
				#delta = 32
			
			if fStart < seqLen/2:
				if arc_y_start > origin_y:
					
					#c.setStrokeColor(black)
					#c.setFillColor(black)
					
					c.setStrokeColor(fColor)
					c.setFillColor(fColor)
					
					c.setLineWidth(1)
					c.saveState()
					
					#if fSize <= 100:
						#c.line(arc_x_start, arc_y_start, arc_x_start+50*math.fabs(math.sin(delta)), arc_y_start+55*math.fabs(math.cos(delta)))
					#else:

					# July 17/08: Show labels for long features only
					if fSize > 150:
						c.line(arc_x_start, arc_y_start, arc_x_start+50*math.fabs(math.sin(delta)), arc_y_start+55*math.fabs(math.cos(delta)))
				
					c.restoreState()
					
					# July 17/08: Show labels for long features only
					if fSize > 150:
					
						# draw label
						
						#c.setStrokeColorRGB(0,0,1)
						#c.setFillColorRGB(0,0,1)
						
						c.setStrokeColor(fColor)
						c.setFillColor(fColor)

						c.setFont("Helvetica-Bold", 11)
						c.saveState()

						c.drawString(arc_x_start+50*math.fabs(math.sin(delta)), arc_y_start+55*math.fabs(math.cos(delta)), fValue + " (" + `fStart` + "-" + `fEnd` + ")")
	
					else:
						c.setStrokeColor(fColor)
						c.setFillColor(fColor)
						c.saveState()
						c.rect(ox_labels+5, prev_legend-15, 15,6, 1, 1)
						c.restoreState

						t = c.beginText()
						#t.setStrokeColor(black)
						#t.setFillColor(black)
					
						t.setStrokeColor(fColor)
						t.setFillColor(fColor)
					
						t.setFont("Helvetica-Bold", 10)
						t.setTextOrigin(ox_labels+25, prev_legend-15)
						t.textOut(fValue + " (" + `fStart` + "-" + `fEnd` + ")")
						c.drawText(t)
						c.restoreState
						prev_legend = prev_legend-15
					
					c.restoreState()
					
				else:
					#c.setStrokeColor(black)
					#c.setFillColor(black)
					
					c.setStrokeColor(fColor)
					c.setFillColor(fColor)
					
					c.setLineWidth(1)
					c.saveState()
					
					if fSize > 150:
						c.line(arc_x_start, arc_y_start, arc_x_start+50*math.fabs(math.sin(delta)), arc_y_start-55*math.fabs(math.cos(delta)))
						
					c.restoreState()
					
					# draw label
					if fSize > 150:
						
						#c.setStrokeColorRGB(0,0,1)
						#c.setFillColorRGB(0,0,1)
						
						c.setStrokeColor(fColor)
						c.setFillColor(fColor)

						c.setFont("Helvetica-Bold", 11)
						c.saveState()
						
						c.drawString(arc_x_start+50*math.fabs(math.sin(delta)), arc_y_start-55*math.fabs(math.cos(delta)), fValue + " (" + `fStart` + "-" + `fEnd` + ")")
					else:
						c.setStrokeColor(fColor)
						c.setFillColor(fColor)
						c.saveState()
						c.rect(ox_labels+5, prev_legend-15, 15,6, 1, 1)
						c.restoreState

						t = c.beginText()
						#t.setStrokeColor(black)
						#t.setFillColor(black)
						
						t.setStrokeColor(fColor)
						t.setFillColor(fColor)
						
						t.setFont("Helvetica-Bold", 10)
						t.setTextOrigin(ox_labels+25, prev_legend-15)
						t.textOut(fValue + " (" + `fStart` + "-" + `fEnd` + ")")
						c.drawText(t)
						c.restoreState
						prev_legend = prev_legend-15
						
					c.restoreState()
			else:
				if arc_y_start > origin_y:
					
					#c.setStrokeColor(black)
					#c.setFillColor(black)

					c.setStrokeColor(fColor)
					c.setFillColor(fColor)
					
					c.setLineWidth(1)
					c.saveState()
					
					if fSize > 150:
						c.line(arc_x_start, arc_y_start, arc_x_start-50*math.fabs(math.sin(delta)), arc_y_start+55*math.fabs(math.cos(delta)))
						
						#c.line(arc_x_start, arc_y_start, arc_x_start+10*math.fabs(math.sin(delta)), arc_y_start-10*math.fabs(math.cos(delta)))
				
					c.restoreState()
					
					# draw label
					if fSize > 150:
						#c.setStrokeColorRGB(0,0,1)
						#c.setFillColorRGB(0,0,1)
						
						c.setStrokeColor(fColor)
						c.setFillColor(fColor)

						c.setFont("Helvetica-Bold", 11)
						c.saveState()
						
						c.drawRightString(arc_x_start-50*math.fabs(math.sin(delta)), arc_y_start+55*math.fabs(math.cos(delta)), fValue + " (" + `fStart` + "-" + `fEnd` + ")")
					else:
						c.setStrokeColor(fColor)
						c.setFillColor(fColor)
						c.saveState()
						c.rect(ox_labels+5, prev_legend-15, 15,6, 1, 1)
						c.restoreState

						t = c.beginText()
						#t.setStrokeColor(black)
						#t.setFillColor(black)
						
						t.setStrokeColor(fColor)
						t.setFillColor(fColor)
						
						t.setFont("Helvetica-Bold", 10)
						t.setTextOrigin(ox_labels+25, prev_legend-15)
						t.textOut(fValue + " (" + `fStart` + "-" + `fEnd` + ")")
						c.drawText(t)
						c.restoreState
						prev_legend = prev_legend-15
							
					c.restoreState()
					
				else:
					#c.setStrokeColor(black)
					#c.setFillColor(black)
					
					c.setStrokeColor(fColor)
					c.setFillColor(fColor)
					
					c.setLineWidth(1)
					c.saveState()
					
					if fSize > 150:
						c.line(arc_x_start, arc_y_start, arc_x_start-50*math.fabs(math.sin(delta)), arc_y_start-55*math.fabs(math.cos(delta)))
				
					c.restoreState()
					
					# draw label
					if fSize > 150:
						
						#c.setStrokeColorRGB(0,0,1)
						#c.setFillColorRGB(0,0,1)
						
						c.setStrokeColor(fColor)
						c.setFillColor(fColor)
						
						c.setFont("Helvetica-Bold", 11)
						c.saveState()
						
						c.drawRightString(arc_x_start-50*math.fabs(math.sin(delta)), arc_y_start-55*math.fabs(math.cos(delta)), fValue + " (" + `fStart` + "-" + `fEnd` + ")")
						
						c.restoreState()
					else:
						c.setStrokeColor(fColor)
						c.setFillColor(fColor)
						c.saveState()
						c.rect(ox_labels+5, prev_legend-15, 15,6, 1, 1)
						c.restoreState

						t = c.beginText()
						#t.setStrokeColor(black)
						#t.setFillColor(black)
						
						t.setStrokeColor(fColor)
						t.setFillColor(fColor)
						
						t.setFont("Helvetica-Bold", 10)
						t.setTextOrigin(ox_labels+25, prev_legend-15)
						t.textOut(fValue + " (" + `fStart` + "-" + `fEnd` + ")")
						c.drawText(t)
						c.restoreState
						prev_legend = prev_legend-15
			
	c.showPage()
	c.save()
	
	# Feb. 2/09: Change permissions
	#os.chmod("/tmp/vector_maps/" + reagentID + "_map.pdf", stat.S_IMODE(stat.S_IRWXU | stat.S_IRWXO | stat.S_IRWXG))
	
def drawMap2():
	dbConn = DatabaseConn()
	db = dbConn.databaseConnect()
	
	cursor = db.cursor()
	hostname = dbConn.getHostname()

	form = cgi.FieldStorage(keep_blank_values="True")
	
	#print "Content-type:text/html"
	#print
	#print `form`
	
	if form.has_key("rID"):
		rID = form.getvalue("rID")
	else:
		#rID = 791
		#print rID
		rID = 154659 

	reagentID = rHandler.convertDatabaseToReagentID(rID)
	
	d = Drawing(1000,1000)
	
	origin = 0
	origin_x = 500
	origin_y = 500
	radius = 350
	
	c = Circle(origin_x, origin_y, radius)
	
	c.strokeColor = blue
	c.fillColor = white
	
	d.add(c)
	
	c.line(origin_x, origin_y+radius-7, origin_x-7, origin_y+radius)
	c.line(origin_x-7, origin_y+radius, origin_x, origin_y+radius+7)
	c.line(origin_x, origin_y+radius+7, origin_x+7, origin_y+radius)
	c.line(origin_x+7, origin_y+radius, origin_x, origin_y+radius-7)
	
	
	p = c.beginPath()
	c.setFillColor(black)
	c.saveState()
	p.moveTo(origin_x, origin_y+radius-7)
	p.lineTo(origin_x-7, origin_y+radius)
	p.lineTo(origin_x, origin_y+radius+7)
	p.lineTo(origin_x+7, origin_y+radius)
	p.close()
	c.drawPath(p)
	c.restoreState()
	
	# Divide circle into 100-unit sectors
	rSeqID = rHandler.findDNASequenceKey(rID)
	rSeq = sHandler.findSequenceByID(rSeqID)
	seqLen = len(rSeq)
	#print seqLen
	
	unit_angle_measure = float(360) / float(seqLen)
	
	# Calculate feature segment sizes
	sequenceFeatures = rHandler.findReagentSequenceFeatures(rID)
	
	for feature in sequenceFeatures:
		fType = feature.getFeatureType()
		fValue = feature.getFeatureName()
		
		fStart = feature.getFeatureStartPos()
		fEnd = feature.getFeatureEndPos()
		fDir = feature.getFeatureDirection()
		
		f_start = fStart * unit_angle_measure
		f_end = fEnd * unit_angle_measure
		
		if fStart > 0 and fEnd > 0:
			print "Start " + `fStart`
			print "End " + `fEnd`
		
			startAngle = 90 - f_start
			extAngle = -1*(f_end - f_start)
			
			print "Angle " + `extAngle`
			
			x1 = origin_x - radius
			y1 = origin_y - radius
			
			x2 = origin_x + radius
			y2 = origin_y + radius
			
			p = ArcPath()
			#p.dumpProperties()
			
			p.strokeWidth=15
			p.strokeColor = black
			p.fillColor = red
			
			#p.addArc(x1, y1, x2, y2, startAngle, extAngle)		# NO
			
			d.add(p)
			
			d.add(String(origin_x+fStart, origin_y+fStart, fValue))
	
	renderPDF.drawToFile(d, "V7066_map.png")
	
	
drawMap()
#drawMap2()
