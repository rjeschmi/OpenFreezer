#!/usr/local/bin/python

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
import utils

def getNextColor(colorsList):
	
	allColors = colors.getAllNamedColors()
	#print `allColors`
	i = 0
	fColors = allColors.values()
	
	for fColor in fColors:
		color = fColor.hexval()
		
		if not color in colorsList:
			#print color
			return color	# just return next unused color in sequence
		
def getAllColors():
	#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
	#print					# DITTO
	
	allColors = colors.getAllNamedColors()
	#print `allColors`
	i = 0
	fColors = allColors.values()
	#print `fColors`
	hexColors = []
	
	for fColor in fColors:
		color = fColor.hexval()
		hexColors.append(color)
		
	return hexColors