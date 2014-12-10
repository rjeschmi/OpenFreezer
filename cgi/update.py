#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()

import MySQLdb
import sys
import string

from database_conn import DatabaseConn

from mapper import ReagentPropertyMapper, ReagentAssociationMapper, ReagentTypeMapper
from general_handler import *
from reagent_handler import ReagentHandler, InsertHandler
from sequence_handler import *
from comment_handler import CommentHandler
#from system_set_handler import SystemSetHandler
#from sequence_handler import SequenceHandler

import utils

from user_handler import UserHandler
from project_database_handler import ProjectDatabaseHandler
from session import Session
from exception import *

from sequence_feature import SequenceFeature

from reagent import *

import Bio
from Bio.Seq import Seq
#from Bio import Enzyme
from Bio.Restriction import *

########################################################
# Global Variables
########################################################

dbConn = DatabaseConn()
db = dbConn.databaseConnect()

cursor = db.cursor()
hostname = dbConn.getHostname()

# Handlers and mappers
rHandler = ReagentHandler(db, cursor)
iHandler = InsertHandler(db, cursor)
sHandler = SystemSetHandler(db, cursor)
pHandler = ReagentPropertyHandler(db, cursor)
raHandler = ReagentAssociationHandler(db, cursor)
rtAssocHandler = ReagentTypeAssociationHandler(db, cursor)
rtPropHandler = ReagentTypePropertyHandler(db, cursor)	# Aug. 31/09
aHandler = AssociationHandler(db, cursor)
rtHandler = ReagentTypeHandler(db, cursor)	# added June 3/09
dnaHandler = DNAHandler(db, cursor)
commHandler = CommentHandler(db, cursor)
protHandler = ProteinHandler(db, cursor)
rnaHandler = RNAHandler(db, cursor)

propMapper = ReagentPropertyMapper(db, cursor)
assocMapper = ReagentAssociationMapper(db, cursor)
rMapper = ReagentTypeMapper(db, cursor)

# Nov. 11/08
reagentType_Name_ID_Map = rMapper.mapTypeNameID()
reagentType_ID_Name_Map = rMapper.mapTypeIDName()

# August 29/07: Restrict creation by user and project access
packetHandler = ProjectDatabaseHandler(db, cursor)

########################################################
# Various maps
########################################################

prop_Alias_ID_Map = propMapper.mapPropAliasID()		# (propAlias, propID) - e.g. ('insert_type', '48') --> represents 'type of insert' property
prop_Name_Alias_Map = propMapper.mapPropNameAlias()	# (propName, propAlias)
prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)

prop_ID_Name_Map = propMapper.mapPropIDName()		# Added March 13/08 - (prop id, prop name)
prop_Alias_Name_Map = propMapper.mapPropAliasName()	# March 18/08 - (propAlias, propName)

# July 2/09
prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()
prop_Category_ID_Name_Map = propMapper.mapPropCategoryNameID()

prop_Category_Name_Alias_Map = propMapper.mapPropCategoryNameAlias()	# July 21/09
prop_Category_Alias_Name_Map = propMapper.mapPropCategoryAliasName()

# May 7/09: (property category name, property category ID) tuples, e.g. 'General Properties' => 1
prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()
prop_Category_Alias_ID_Map = propMapper.mapPropCategoryAliasID()

# April 10/08
assoc_Name_Alias_Map = assocMapper.mapAssocNameAlias()
assoc_Name_ID_Map = assocMapper.mapAssocNameID()
assoc_Alias_ID_Map = assocMapper.mapAssocAliasID()
assoc_ID_Alias_Map = assocMapper.mapAssocIDAlias()

# Get enzymes list for mapping sequence features
enzDict = utils.join(dnaHandler.sitesDict, dnaHandler.gatewayDict)
enzDict = utils.join(enzDict, dnaHandler.recombDict)		# add LoxP
enzDict['None'] = ""						# add 'None'

# Process reagent properties upon exit from Modify view
def update():

	form = cgi.FieldStorage(keep_blank_values="True")
	
	#print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
	#print					# DITTO
	#print `form`

	rID = int(form.getvalue("reagent_id_hidden"))
	
	# Aug 29/07
	uHandler = UserHandler(db, cursor)
	
	if form.has_key("curr_username"):
		# store the user ID for use throughout the session; add to other views in addition to create in PHP
		currUname = form.getvalue("curr_username")
		currUser = uHandler.getUserByDescription(currUname)
		
		Session.setUser(currUser)
	
	# check if we're in modify or save mode
	mode = form.getvalue("change_state")
	#print "mode? " + `mode`
	
	if mode == "Modify":

		# print Modify view
		utils.redirect(hostname + "Reagent.php?View=6&mode=Modify&rid=" + `rID`)

	elif mode == "Save":

		#print "Content-type:text/html"
		#print
		#print `form`

		prefix = "reagent_detailedview_"
		postfix = "_prop"
		
		assocPrefix = "assoc_"
		
		# Feb. 25/08: Start and stop positions
		startPostfix = "_startpos"
		endPostfix = "_endpos"

		# Feb. 26/08: Orientation
		orientationPostfix = "_orientation"

		if form.has_key("reagent_typeid_hidden"):
			rTypeID = form.getvalue("reagent_typeid_hidden")
			
		if form.has_key("cloning_method_hidden"):
			cloning_method = form.getvalue("cloning_method_hidden")
		
		if form.has_key("change_pv_flag"):
			change_PV_Flag = form.getvalue("change_pv_flag")
			
			if change_PV_Flag != '1':
				# hardcode temporarily, fix later
				if rTypeID == '4':
					if form.has_key("assoc_cell_line_parent_vector"):
						utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID` + "&ErrCode=1&ErrVal=" + form.getvalue("assoc_cell_line_parent_vector"))
		
		if form.has_key("change_cl_flag"):
			change_CL_Flag = form.getvalue("change_cl_flag")
			
			if change_CL_Flag != '1':
				if form.has_key("reagent_typeid_hidden"):
					rTypeID = form.getvalue("reagent_typeid_hidden")
					
					# hardcode temporarily, fix later
					if rTypeID == '4':
						if form.has_key("assoc_parent_cell_line"):
							utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID` + "&ErrCode=2&ErrVal=" + form.getvalue("assoc_parent_cell_line"))
		
		########################################################
		# Instance of Reagent that corresponds to rID
		########################################################

		#print "Content-type:text/html"
		#print
		
		# July 20/09
		section_to_save = form.getvalue("save_section")
		#print `prop_Category_Name_Alias_Map`
		#print section_to_save
		
		reagent = rHandler.createReagent(rID)
		reagent.setCloningMethod(cloning_method)
		
		rType = reagentType_ID_Name_Map[rHandler.findReagentTypeID(rID)]
		
		checkboxPropNames = reagent.getCheckboxProps()		# NAMES of checkbox properties (e.g. "alternate id", 
		
		checkboxPropAliases = {}		# (checkboxPropAlias, checkboxPropName)
		checkboxProps = {}			# (checkboxPropID, checkboxPropName)
		newSetEntry = ""			# if set needs to be updated with a new value
		
		if checkboxPropNames and len(checkboxPropNames) > 0:	# have to add to avoid NoneType error
			for c in checkboxPropNames:
			
				cAlias = prop_Name_Alias_Map[c]
				cPropID = prop_Name_ID_Map[c]
				
				checkboxPropAliases[cAlias] = c
				checkboxProps[cPropID] = c
		
		# April 7/08: Introduced reagent modification by sections
		#if form.has_key("sequence_modify"):
		if section_to_save == prop_Category_Name_Alias_Map["DNA Sequence"]:
			
			#print "Content-type:text/html"
			#print
			#print `form`
			
			dnaSeqCategoryID = prop_Category_Name_ID_Map["DNA Sequence"]
			
			# moved here April 10/08
			newPropsDict_name = {}					# e.g. ('status', 'Completed')
			newPropsDict_id = {}					# e.g. ('3', 'Completed') - db ID instead of property name
			
			# Save new DNA sequence, new type of Insert and open/closed, new translation, AND recompute feature positions
			
			# April 13, 2010: do only if sequence has actually been changed???
			
			newSeq = form.getvalue(prefix + prop_Name_Alias_Map["sequence"] + postfix)
			
			# July 2/09: propertyID <= propCatID
			seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], dnaSeqCategoryID)
			newPropsDict_id[seqPropID] = newSeq
			
			# Whether the form includes Type of Insert and Open/Closed depends on the reagent type
			if rTypeID == '2':
				# May 18, 2011: REMOVED Type of Insert and Open/Closed from sequence section!
				# so just get existing values
				#newInsertType = form.getvalue(prefix + prop_Name_Alias_Map["type of insert"] + postfix)
				#newOpenClosed = form.getvalue(prefix + prop_Name_Alias_Map["open/closed"] + postfix)

				insertTypePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["type of insert"], prop_Category_Name_ID_Map["Classifiers"])
				#print insertTypePropID
				
				openClosedPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["open/closed"], prop_Category_Name_ID_Map["Classifiers"])
				#print openClosedPropID
			
				newInsertType = iHandler.findSimplePropertyValue(rID, insertTypePropID)
				newOpenClosed = iHandler.findSimplePropertyValue(rID, openClosedPropID)
				
				#print "New Insert type: " + newInsertType
				#print "New Open/Closed: " + newOpenClosed
				
				# Removed May 19, 2011 (see above)
				'''
				# May 17, 2010: but now cannot update with the rest, need to do them separately b/c they're under a different category!
				#newPropsDict_id[insertTypePropID] = newInsertType	# removed May 17, 2010
				#newPropsDict_id[openClosedPropID] = newOpenClosed	# removed May 17, 2010
				
				#rHandler.changePropertyValue(rID, insertTypePropID, newInsertType)
				#rHandler.changePropertyValue(rID, openClosedPropID, newOpenClosed)
				'''

			# April 7/08: Recompute feature positions (do before calling 'update' to fetch previous sequence value)
			# Grab the old sequence
			oldSeqID = rHandler.findDNASequenceKey(rID)
			oldSeq = dnaHandler.findSequenceByID(oldSeqID)
			newSeq = dnaHandler.squeeze(newSeq)
			
			if rTypeID == '2':
				# Update July 23/09: account for cases where insert type - open/closed are empty
				iHandler.updateInsertProteinSequence(rID, newInsertType, newOpenClosed)

			# Update the rest of the features
			if len(oldSeq) > 0:
				rHandler.updateFeaturePositions(rID, oldSeq.lower(), newSeq.lower())
				
				# Nov. 18/08 - do linkers separately, AFTER the rest of the features b/c updateFeaturePositions() deletes them
				# Update July 2/09
				fpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
				tpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
				insert_fp_linker = rHandler.findSimplePropertyValue(rID, fpLinkerPropID)
				insert_tp_linker = rHandler.findSimplePropertyValue(rID, tpLinkerPropID)
				
				if insert_fp_linker and len(insert_fp_linker) >= 10 and newSeq.lower().find(insert_fp_linker.lower()) >= 0:
					fpLinkerStartPos = newSeq.lower().find(insert_fp_linker.lower()) + 1
					fpLinkerEndPos = fpLinkerStartPos + len(insert_fp_linker)
					
					rHandler.deleteReagentProperty(rID, fpLinkerPropID)
					rHandler.addReagentProperty(rID, fpLinkerPropID, insert_fp_linker, fpLinkerStartPos, fpLinkerEndPos-1)
				else:
					rHandler.deleteReagentProperty(rID, fpLinkerPropID)
					#fpLinkerStartPos = 0
					#fpLinkerEndPos = 0
					
				# 3' linker
				if insert_tp_linker and len(insert_tp_linker) >= 10 and newSeq.lower().find(insert_tp_linker.lower()) >= 0:
					tpLinkerStartPos = newSeq.lower().find(insert_tp_linker.lower()) + 1
					tpLinkerEndPos = tpLinkerStartPos + len(insert_tp_linker)
					
					rHandler.deleteReagentProperty(rID, tpLinkerPropID)
					rHandler.addReagentProperty(rID, tpLinkerPropID, insert_tp_linker, tpLinkerStartPos, tpLinkerEndPos-1)
				else:
					rHandler.deleteReagentProperty(rID, tpLinkerPropID)
					#tpLinkerStartPos = 0
					#tpLinkerEndPos = 0
				
			# April 19, 2011: update other sequence properties (rare but could happen)
			#print "Content-type:text/html"
			#print

			for sKey in form.keys():
				propAlias = sKey.lstrip(prefix).rstrip(postfix)
				propVal = form.getlist(sKey)

				if prop_Alias_ID_Map.has_key(propAlias) and propAlias != prop_Name_Alias_Map["molecular weight"] and propAlias != prop_Name_Alias_Map["melting temperature"] and propAlias != prop_Name_Alias_Map["sequence"] and propAlias != prop_Name_Alias_Map["gc content"]:
					
					#print propAlias
					#print `propVal`
					
					propCatID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[propAlias], prop_Category_Name_ID_Map["DNA Sequence"])
					
					rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propCatID)

					if len(propVal) > 1:
						for pVal in propVal:
							#print pVal

							setGroupID = sHandler.findPropSetGroupID(propCatID)
							ssetID = sHandler.findSetValueID(setGroupID, pVal)

							if sHandler.findSetValueID(setGroupID, pVal) <= 0:
								ssetID = sHandler.addSetValue(setGroupID, pVal)
							
							if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
								sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
					else:
						pVal = propVal[0]
						
						if pVal.lower() == 'other':	
							textBoxName = prefix + reagentType_ID_Name_Map[int(rTypeID)] + "_" + prop_Category_Name_Alias_Map["DNA Sequence"] + "_:_" + propAlias + "_name_txt"
							
							if form.has_key(textBoxName):
								propVal = form.getvalue(textBoxName)

								setGroupID = sHandler.findPropSetGroupID(propCatID)
								ssetID = sHandler.findSetValueID(setGroupID, propVal)

								# update set
								if sHandler.findSetValueID(setGroupID, propVal) <= 0:
									ssetID = sHandler.addSetValue(setGroupID, propVal)
								
								if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
									sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)

					newPropsDict_id[propCatID] = propVal
					#print `newPropsDict_id`

			# May 17, 2010
			rHandler.updateReagentPropertiesInCategory(rID, dnaSeqCategoryID, newPropsDict_id)

			# June 8/08: Since sites are shorter than 10 nt and have been deleted remap them here
			fpSitePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
			
			tpSitePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
			
			five_prime_site = rHandler.findSimplePropertyValue(rID, fpSitePropID)
			three_prime_site = rHandler.findSimplePropertyValue(rID, tpSitePropID)

			if five_prime_site and three_prime_site:	# added July 11/08, handle empty sequence/sites
				rHandler.updateSitePositions(rID, newSeq, five_prime_site, three_prime_site)
			
			# return to detailed view - Moved here July 7/08
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)
			
		elif section_to_save == prop_Category_Name_Alias_Map["RNA Sequence"]:
			
			#print "Content-type:text/html"
			#print
			#print `form`
			
			# moved here April 10/08
			newPropsDict_name = {}		# e.g. ('status', 'Completed')
			newPropsDict_id = {}		# e.g. ('3', 'Completed') - db ID instead of property name
			
			rnaSeqCategoryID = prop_Category_Name_ID_Map["RNA Sequence"]

			newSeq = form.getvalue(prefix + prop_Name_Alias_Map["rna sequence"] + postfix)
			#print newSeq
			
			# July 2/09: propertyID <= propCatID
			seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["rna sequence"], prop_Category_Name_ID_Map["RNA Sequence"])
			newSeq = rnaHandler.squeeze(newSeq)
			#print newSeq
			newPropsDict_id[seqPropID] = newSeq
			
			# April 7/08: Recompute feature positions (do before calling 'update' to fetch previous sequence value)
			# Grab the old sequence
			oldSeqID = rHandler.findRNASequenceKey(rID)
			oldSeq = rnaHandler.findSequenceByID(oldSeqID)
			#print oldSeqID
			
			# Update the rest of the features
			if len(oldSeq) > 0:
				rHandler.updateFeaturePositions(rID, oldSeq.lower(), newSeq.lower(), False, True)
				
				# No linkers for RNA
							
			# April 12, 2011: Get Tm from input
			tmPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["melting temperature"], prop_Category_Name_ID_Map["RNA Sequence"])

			if form.has_key(prefix + prop_Name_Alias_Map["melting temperature"] + postfix):
				tm_rna = form.getvalue(prefix + prop_Name_Alias_Map["melting temperature"] + postfix)
				#print tm_rna
				newPropsDict_id[tmPropID] = tm_rna

			# April 14, 2011: Ditto MW
			mwPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["RNA Sequence"])

			if form.has_key(prefix + prop_Name_Alias_Map["molecular weight"] + postfix):
				mw_rna = form.getvalue(prefix + prop_Name_Alias_Map["molecular weight"] + postfix)
				newPropsDict_id[mwPropID] = mw_rna

			# No sites for RNA either

			# rest of RNA sequence properties
			
			#print `form`
				
			#print "pre " + prefix

			for sKey in form.keys():
				#print "key: " + sKey
				#print "post " + postfix

				t1 = sKey[0:sKey.find(postfix)]
				t2 = t1[len(prefix):]
				
				propAlias = t2
				
				#print propAlias

				propVal = form.getlist(sKey)

				#print propAlias
				#print `propVal`

				if prop_Alias_ID_Map.has_key(propAlias) and propAlias != prop_Name_Alias_Map["rna sequence"]:
					
					#print propAlias
					#print `propVal`
					
					propCatID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[propAlias], prop_Category_Name_ID_Map["RNA Sequence"])

					#print "propcatid " + `propCatID`

					rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propCatID)

					if len(propVal) > 1:
						for pVal in propVal:
							#print pVal

							setGroupID = sHandler.findPropSetGroupID(propCatID)
							ssetID = sHandler.findSetValueID(setGroupID, pVal)

							if sHandler.findSetValueID(setGroupID, pVal) <= 0:
								ssetID = sHandler.addSetValue(setGroupID, pVal)
							
							if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
								sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
					else:
						pVal = propVal[0]
						
						if pVal.lower() == 'other':	
							textBoxName = prefix + reagentType_ID_Name_Map[int(rTypeID)] + "_" + prop_Category_Name_Alias_Map["RNA Sequence"] + "_:_" + propAlias + "_name_txt"
							
							if form.has_key(textBoxName):
								propVal = form.getvalue(textBoxName)

								setGroupID = sHandler.findPropSetGroupID(propCatID)
								ssetID = sHandler.findSetValueID(setGroupID, propVal)

								# update set
								if sHandler.findSetValueID(setGroupID, propVal) <= 0:
									ssetID = sHandler.addSetValue(setGroupID, propVal)
								
								if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
									sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)

					newPropsDict_id[propCatID] = propVal

			#print `newPropsDict_id`

			rHandler.updateReagentPropertiesInCategory(rID, rnaSeqCategoryID, newPropsDict_id)

			# return to detailed view - Moved here July 7/08
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)
			
		# April 10/08
		#elif form.has_key("save_intro"):
		elif section_to_save == prop_Category_Name_Alias_Map["General Properties"]:

			#print "Content-type:text/html"
			#print
			#print `form`

			# moved here April 10/08
			newPropsDict_name = {}					# e.g. ('status', 'Completed')
			newPropsDict_id = {}					# e.g. ('3', 'Completed') - db ID instead of property name
			
			gpCategoryID = prop_Category_Name_ID_Map["General Properties"]
			
			# Save only general reagent information, no effect on sequence, features, parents or annotations
			
			# Updated May 7/09
			#generalPropNames = reagent.getGeneralProperties()		# removed May 7/09
			generalPropNamesBasic = reagent.getGeneralProperties()		# added May 7/09
			
			#print `generalPropNamesBasic`
			
			# May 7/09: Get additional general properties that may have been assigned to this reagent type in addition to basic properties common to all
			generalReagentAttributes = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, gpCategoryID)
			generalPropNames = utils.merge(generalPropNamesBasic, generalReagentAttributes)		# May 7/09
			#print `generalPropNames`
			
			generalPropertyAliases = {}
			generalProperties = {}
			
			for g in generalPropNames:
				
				gpAlias = prop_Name_Alias_Map[g]
				gPropID = prop_Name_ID_Map[g]
				
				generalPropertyAliases[gpAlias] = g
				generalProperties[gPropID] = g
				
			#print `generalProperties`
			
			for gpAlias in generalPropertyAliases:
				# Check if the form has this value
				tmpPropName = prefix + gpAlias + postfix
				
				tmpPropVals = []	# in case of multiple properties (May 14/2010)
			
				#print tmpPropName
				
				if form.has_key(tmpPropName):
					tmpPropVal = form.getvalue(tmpPropName)
					#print tmpPropVal
					
					# May 14, 2010: move up here for speed
					pcID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[gpAlias], gpCategoryID)
					#print pcID
					
					rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(int(rTypeID), pcID)
					#print rTypeAttributeID
	
					setGroupID = sHandler.findPropSetGroupID(pcID)
					
					if not utils.isList(tmpPropVal):	# this is a single value, either in a list or freetext
						if tmpPropVal.lower() == 'other':
							# look for a textbox
							otherTextboxName = prefix + rType + "_" + section_to_save + "_:_" + gpAlias + "_name_txt"
							#print otherTextboxName
							
							if form.has_key(otherTextboxName):
								tmpPropVal = form.getvalue(otherTextboxName)
								#print "this is other " + tmpPropVal
						
								# moved this back under 'if other text' May 27/10
								ssetID = sHandler.findSetValueID(setGroupID, tmpPropVal)
								#print ssetID
								
								if ssetID <= 0:
									ssetID = sHandler.addSetValue(setGroupID, tmpPropVal)
								
								if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
									sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
					
						# May 27/10: check if this is a single value under multiple d/d list
						# We are not storing property format in database, only way of differentiating b/w freetext and dropdowns that should be updated is: a) If textbox is found - this is a dropdown, save value  b) If rtPropHandler.isMultiple() returns true, this is a multiple dropdown where only one value was entered - OK.  DO NOT ADD ANY OTHER VALUES TO SET!!!!!!!!!!
					
						if rtPropHandler.isMultiple(rTypeAttributeID):
							tmp_ar = []
							
							tmp_ar.append(tmpPropVal)
							
							for val in tmp_ar:
								
								ssetID = sHandler.findSetValueID(setGroupID, val)
								#print ssetID
								
								if ssetID <= 0:
									ssetID = sHandler.addSetValue(setGroupID, val)
								
								if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
									sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
					
					# May 14, 2010: still, update Other set even for multiple
					else:
						tmpPropVal = utils.unique(tmpPropVal)
						
						for t in tmpPropVal:
							# check if each value is in set and, if not, update
							ssetID = sHandler.findSetValueID(setGroupID, t)
							#print ssetID
							
							if ssetID <= 0:
								ssetID = sHandler.addSetValue(setGroupID, t)
							
							if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
								sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)

					newPropsDict_name[gpAlias] = tmpPropVal		# moved here May 14, 2010
			
			#print `newPropsDict_name`
			
			# Match db IDs to property aliases
			for tmpProp in newPropsDict_name.keys():
				
				#print tmpProp
				
				if prop_Alias_ID_Map.has_key(tmpProp):
					
					# July 2/09: propertyID <= propCatID
					#propID = prop_Alias_ID_Map[tmpProp]	# removed July 2/09
					propID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[tmpProp], gpCategoryID)
					newPropsDict_id[propID] = newPropsDict_name[tmpProp]
				
			#print `newPropsDict_id`

			# ACTION			
			rHandler.updateReagentPropertiesInCategory(rID, gpCategoryID, newPropsDict_id)
			
			# return to detailed view - Moved here July 7/08
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)
			
		# Annotations
		#elif form.has_key("save_annos"):
		elif section_to_save == prop_Category_Name_Alias_Map["External Identifiers"]:
			
			#print "Content-type:text/html"
			#print
			#print `form`
			
			eiCategoryID = prop_Category_Name_ID_Map["External Identifiers"]
			
			# moved here April 10/08
			newPropsDict_name = {}			# e.g. ('status', 'Completed')
			newPropsDict_id = {}			# e.g. ('3', 'Completed') - db ID instead of property name

			annoNames = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, eiCategoryID)
			
			annoAliases = {}
			annotations = {}
				
			# Now map annotation names to their db aliases and IDs
			for anno in annoNames:
				annoAlias = prop_Name_Alias_Map[anno]
				annoAliases[annoAlias] = anno
			
			# Get annotation values from the form
			for annoAlias in annoAliases:
				tmpPropName = prefix + annoAlias + postfix
				
				if form.has_key(tmpPropName):
					tmpPropVal = form.getvalue(tmpPropName)
					newPropsDict_name[annoAlias] = tmpPropVal
			
					if annoAlias == "alternate_id":
						
						newPropVal = form.getlist(tmpPropName)
						#print `newPropVal`
						tmp_alt_ids = []
						
						for altID in newPropVal:
							if form.has_key(annoAlias + "_" + altID + "_textbox_name"):
								if altID.lower() == 'other':
									tmp_alt_id = form.getvalue(annoAlias + "_" + altID + "_textbox_name")
									
									#print annoAlias
									#print annoPropID
									
									# update list
									pcID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[annoAlias], eiCategoryID)
									#print pcID
							
									rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(int(rTypeID), pcID)
									#print rTypeAttributeID
									
									setGroupID = sHandler.findPropSetGroupID(pcID)
									#print setGroupID
									
									ssetID = sHandler.findSetValueID(setGroupID, tmp_alt_id[0:tmp_alt_id.find(":")])
									#print ssetID
									
									if ssetID <= 0:
										ssetID = sHandler.addSetValue(setGroupID, tmp_alt_id[0:tmp_alt_id.find(":")])
									
									if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
										sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
								else:
									tmp_alt_id = altID + ":" + form.getvalue(annoAlias + "_" + altID + "_textbox_name")
									#print tmp_alt_id
									
								tmp_alt_ids.append(tmp_alt_id)
						
						newPropVal = tmp_alt_ids
						newPropsDict_name[annoAlias] = newPropVal
					#else:
						# delete???????????
						#print tmpPropName
						
			#print `newPropsDict_name`
			
			# Match db IDs to property aliases
			for tmpProp in newPropsDict_name.keys():
				
				if prop_Alias_ID_Map.has_key(tmpProp):
					
					# July 2/09: propertyID <= propCatID
					#propID = prop_Alias_ID_Map[tmpProp]	# removed July 2/09
					propID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[tmpProp], eiCategoryID)
					newPropsDict_id[propID] = newPropsDict_name[tmpProp]

					newPropsDict_id[propID] = newPropsDict_name[tmpProp]

			# ACTION - Update May 17, 2010
			rHandler.updateReagentPropertiesInCategory(rID, eiCategoryID, newPropsDict_id)
		
			# return to detailed view - Moved here July 7/08
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)
			
		elif section_to_save == prop_Category_Name_Alias_Map["Classifiers"]:
			
			#print "Content-type:text/html"
			#print
			#print rTypeID
			#print `form`
			
			newPropsDict_name = {}			# e.g. ('status', 'Completed')
			newPropsDict_id = {}			# e.g. ('3', 'Completed') - db ID instead of property name
			
			classifiersCategoryID = prop_Category_Name_ID_Map["Classifiers"]
			classifierNames = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, classifiersCategoryID)
		
			classifierAliases = {}
			classifiers = {}
			
			# Map classifier names to aliases
			for c in classifierNames:
				cAlias = prop_Name_Alias_Map[c]
				#cPropID = prop_Name_ID_Map[c]
				
				classifierAliases[cAlias] = c
				#classifiers[cPropID] = c
				
			# Get classifier values from form
			for cAlias in classifierAliases:
				
				tmpPropName = prefix + cAlias + postfix
				#print tmpPropName
				
				if form.has_key(tmpPropName):
					if not utils.isList(form.getvalue(tmpPropName)):
						
						newPropVal = form.getvalue(tmpPropName).strip()
						#print newPropVal
						
						# Jan. 9/09: save 'other' values - Update Feb. 4/10
						if newPropVal.lower() == 'other':

							#textBox = cAlias + "_name_txt"		# rmvd Feb. 4/10
							textBoxName = prefix + reagentType_ID_Name_Map[int(rTypeID)] + "_" + prop_Category_Name_Alias_Map["Classifiers"] + "_:_" + cAlias + "_name_txt"
							
							#print textBoxName
							
							if form.has_key(textBoxName):
								newPropVal = form.getvalue(textBoxName).strip()
								
								# rmvd Feb. 4/10
								#tmpPropVal = form[textBox].value.strip()
								#newSetEntry = tmpPropVal
								#sHandler.updateSet(cAlias, newSetEntry)
								
								pcID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[cAlias], classifiersCategoryID)
								
								rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, pcID)
								
								setGroupID = sHandler.findPropSetGroupID(pcID)
								ssetID = sHandler.findSetValueID(setGroupID, newPropVal)
								
								if sHandler.findSetValueID(setGroupID, newPropVal) <= 0:
									ssetID = sHandler.addSetValue(setGroupID, newPropVal)
								
								if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
									sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
					else:
						newPropVal = utils.unique(form.getvalue(tmpPropName))
						
					newPropsDict_name[cAlias] = newPropVal
				else:
					# May 13, 2010: delete
					pcID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[cAlias], classifiersCategoryID)
					rHandler.deleteReagentProperty(rID, pcID)
			
			# Match db IDs to property aliases
			for tmpProp in newPropsDict_name.keys():
				
				if prop_Alias_ID_Map.has_key(tmpProp):
					
					# July 2/09: propertyID <= propCatID
					#propID = prop_Alias_ID_Map[tmpProp]	# removed July 2/09
					propID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[tmpProp], prop_Category_Name_ID_Map["Classifiers"])
					#print propID
					newPropsDict_id[propID] = newPropsDict_name[tmpProp]

			#print `newPropsDict_id`

			# ACTION
			rHandler.updateReagentPropertiesInCategory(rID, classifiersCategoryID, newPropsDict_id)
			
			# Jan. 9/09: Need to update Insert protein translation if its type or open/closed changes
			if rTypeID == '2':
				iHandler.updateInsertProteinSequence(rID)
			
			# return to detailed view - Moved here July 7/08
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)
		
		# April 10/08: Features
		#elif form.has_key("save_features"):
		elif section_to_save == prop_Category_Name_Alias_Map["DNA Sequence Features"]:
			
			#print "Content-type:text/html"
			#print
			#print rTypeID
			#print `form`
			
			# Save features - taken from 'preload.py'
			# (deletion is performed in updateFeatures function, no need to call here)
			newPropsDict_name = {}			# e.g. ('status', 'Completed')
			newPropsDict_id = {}			# e.g. ('3', 'Completed') - db ID instead of property name
			
			startPosDict = {}			# (propID, startpos)
			endPosDict = {}				# (propID, endpos)
			
			# Store orientation
			orientationDict = {}			# (propID, orientation)
			
			# Nov. 6/08: Delete before update
			fpSitePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
			
			tpSitePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
			rHandler.deleteReagentProperty(rID, fpSitePropID)
			rHandler.deleteReagentProperty(rID, tpSitePropID)
			
			# Sept. 8/08: Delete linkers too
			fpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
			tpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
			rHandler.deleteReagentProperty(rID, fpLinkerPropID)
			rHandler.deleteReagentProperty(rID, tpLinkerPropID)
			
			# features too
			rHandler.deleteReagentFeatures(rID)

			if form.has_key(prefix + prop_Name_Alias_Map["5' linker"] + postfix):
				five_prime_linker = form.getvalue(prefix + prop_Name_Alias_Map["5' linker"] + postfix).replace(" ", "").strip()
				fAlias = prop_Name_Alias_Map["5' linker"]

				#fID = prop_Alias_ID_Map[fAlias]
				fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map["DNA Sequence Features"])
				
				startFieldName = prefix + fAlias + "_startpos" + postfix
				endFieldName = prefix + fAlias + "_endpos" + postfix
				
				if form.has_key(startFieldName):
					fStartPos = form.getvalue(startFieldName)
				else:
					fStartPos = 0
					
				if form.has_key(endFieldName):
					fEndPos = form.getvalue(endFieldName)
				else:
					fEndPos = 0

				# orientation
				orientationFieldName = prefix + fAlias + "_orientation" + postfix
			
				if form.has_key(orientationFieldName):
					tmpDir = form.getvalue(orientationFieldName)
					#print tmpDir
				else:
					tmpDir = 'forward'

				rHandler.addReagentProperty(rID, fID, five_prime_linker, fStartPos, fEndPos, tmpDir)
				
			if form.has_key(prefix + prop_Name_Alias_Map["3' linker"] + postfix):
				three_prime_linker = form.getvalue(prefix + prop_Name_Alias_Map["3' linker"] + postfix).replace(" ", "").strip()
				
				fAlias = prop_Name_Alias_Map["3' linker"]

				#fID = prop_Alias_ID_Map[fAlias]
				fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map["DNA Sequence Features"])
				
				startFieldName = prefix + fAlias + "_startpos" + postfix
				endFieldName = prefix + fAlias + "_endpos" + postfix
				
				if form.has_key(startFieldName):
					fStartPos = form.getvalue(startFieldName)
				else:
					fStartPos = 0
					
				if form.has_key(endFieldName):
					fEndPos = form.getvalue(endFieldName)
				else:
					fEndPos = 0

				# orientation
				orientationFieldName = prefix + fAlias + "_orientation" + postfix
			
				if form.has_key(orientationFieldName):
					tmpDir = form.getvalue(orientationFieldName)
					#print tmpDir
				else:
					tmpDir = 'forward'
					
				rHandler.addReagentProperty(rID, fID, three_prime_linker, fStartPos, fEndPos, tmpDir)
			
			# March 12/08: Treat properties with multiple values and start/end positions (a.k.a. features) as objects
			seqFeatures = []
	
			# Dec. 17/09: Use same code for all
			
			# May 15/09: Need to adjust for custom reagent types
			#if rTypeID != '1' and rTypeID != '2':
			featureCategoryID = prop_Category_Name_ID_Map["DNA Sequence Features"]
			sequenceFeatures = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, featureCategoryID)
			featureDescriptors = Reagent.getFeatureDescriptors()
				
			#else:	# May 15/09 - keep existing code for Inserts and Vectors: right now I'm focusing on custom reagent types and don't want to break anything that already works in the sytem; can always generalize later
				# March 17/08: Fetch Insert feature descriptors - tag position and expression system
				# Use built-in Insert function instead of 'if-else' blocks with hardcoded values
				#sequenceFeatures = Reagent.getSequenceFeatures()
				#featureDescriptors = Reagent.getFeatureDescriptors()
				
			#print `sequenceFeatures`
		
			# Nov. 13/08: Get Insert sequence
			#seqPropID = prop_Name_ID_Map["sequence"]
			seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])
			seqID = rHandler.findIndexPropertyValue(rID, seqPropID)
			insertSeq = dnaHandler.findSequenceByID(seqID).lower()
			
			# Removed May 16/08 - not a very good idea to delete all features, including sites and linkers - linkers may hinder modification but are necessary for correct feature remapping during Vector sequence reconstitution.  See what happens
			#singleValueFeatures = reagent.getSingleFeatures()	# March 18/08 - Differentiate between features such as promoter or tag type, which could have multiple values, and cloning sites and linkers, which only have one value and one position
			
			#featureNames = sequenceFeatures + singleValueFeatures
			
			featureNames = sequenceFeatures
			
			featureAliases = {}
			features = {}
		
			for f in featureNames:
				fAlias = prop_Name_Alias_Map[f]
				fPropID = prop_Name_ID_Map[f]
				
				featureAliases[fAlias] = f
				features[fPropID] = f
			
			for fAlias in featureAliases:
				#print "alias " + fAlias
				
				# Special case: cDNA start and stop positions
				if prop_Alias_Name_Map[fAlias].lower() == 'cdna insert':
					
					fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map["DNA Sequence Features"])
					
					startFieldName = prefix + fAlias + "_startpos" + postfix
					endFieldName = prefix + fAlias + "_endpos" + postfix
					
					if form.has_key(startFieldName):
						tmpStartPos = form.getvalue(startFieldName)
					else:
						tmpStartPos = 0

					if form.has_key(endFieldName):
						tmpEndPos = form.getvalue(endFieldName)
						#fTemp.setFeatureEndPos(tmpEndPos)
					else:
						tmpEndPos = 0
					
					# Nov. 6/08: Orientation needed too (for reverse complemented Inserts)
					orientationFieldName = prefix + fAlias + "_orientation" + postfix
				
					if form.has_key(orientationFieldName):
						tmpDir = form.getvalue(orientationFieldName)
						#print tmpDir
					else:
						tmpDir = 'forward'
						
					rHandler.addReagentProperty(rID, fID, "", tmpStartPos, tmpEndPos)
					
				elif prop_Alias_Name_Map[fAlias].lower() == "5' cloning site":
					
					five_prime_site = form.getvalue(prefix + prop_Name_Alias_Map["5' cloning site"] + postfix)
					#print "5' site " + five_prime_site
					
					fAlias = prop_Name_Alias_Map["5' cloning site"]
	
					# May 28/08: Allow blank sites for Novel vectors
					if five_prime_site:
						if five_prime_site == "Other":
							five_prime_site = form.getvalue("5_prime_cloning_site_name_txt")
					
						fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map["DNA Sequence Features"])
					
						startFieldName = prefix + fAlias + "_startpos" + postfix
						endFieldName = prefix + fAlias + "_endpos" + postfix
						
						#print startFieldName
						
						if form.has_key(startFieldName):
							fStartPos = form.getvalue(startFieldName)
						else:
							fStartPos = 0
							
						if form.has_key(endFieldName):
							fEndPos = form.getvalue(endFieldName)
						else:
							fEndPos = 0
		
						# orientation
						orientationFieldName = prefix + fAlias + "_orientation" + postfix
					
						if form.has_key(orientationFieldName):
							tmpDir = form.getvalue(orientationFieldName)
							#print tmpDir
						else:
							tmpDir = 'forward'
		
						rHandler.addReagentProperty(rID, fID, five_prime_site, fStartPos, fEndPos, tmpDir)
					
				elif prop_Alias_Name_Map[fAlias].lower() == "3' cloning site":
					three_prime_site = form.getvalue(prefix + prop_Name_Alias_Map["3' cloning site"] + postfix)
					#print "3' site: " + three_prime_site
					
					fAlias = prop_Name_Alias_Map["3' cloning site"]
	
					# May 28/08: Allow blank sites for Novel vectors
					if three_prime_site:
						if three_prime_site == "Other":
							three_prime_site = form.getvalue("3_prime_cloning_site_name_txt")
						#else:
							#fVal = three_prime_site
						
					#fID = prop_Alias_ID_Map[fAlias]
					
						fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map["DNA Sequence Features"])
					
						startFieldName = prefix + fAlias + "_startpos" + postfix
						endFieldName = prefix + fAlias + "_endpos" + postfix
						
						if form.has_key(startFieldName):
							fStartPos = form.getvalue(startFieldName)
						else:
							fStartPos = 0
							
						if form.has_key(endFieldName):
							fEndPos = form.getvalue(endFieldName)
						else:
							fEndPos = 0
						
						# Sept. 3/08: Store orientation
						orientationFieldName = prefix + fAlias + "_orientation" + postfix
						
						if form.has_key(orientationFieldName):
							tmpDir = form.getvalue(orientationFieldName)
							#print tmpDir
						else:
							tmpDir = 'forward'
		
						rHandler.addReagentProperty(rID, fID, three_prime_site, fStartPos, fEndPos, tmpDir)
					
				else:
					tmpStart = -1
					tmpEnd = -1
					
					#tmpPropName = prefix + fAlias + postfix
					featureType = "dna_sequence_features"
				
					for tmpPropName in form.keys():
						#print tmpPropName
						#print fAlias
						
						# convert to lowercase b/c of PolyA
						if tmpPropName.find(prefix + fAlias + "_:_") >= 0:
					
							pValStartInd = len(prefix + fAlias)+3
							#print pValStartInd
							pValStopInd = tmpPropName.rfind("_start_", pValStartInd)
							#print pValStopInd
							
							# actual feature value
							tmpPropValue = tmpPropName[pValStartInd:pValStopInd]
							
							if tmpPropValue and len(tmpPropValue) > 0:
								# get positions - changed oct. 25/08
								
								# Update Nov 3/09
								#start_ind1 = tmpPropName.find(tmpPropValue) + len(tmpPropValue) + len("_start_")
								
								start_ind1 = tmpPropName.rfind("_start_") + len("_start_")
								start_ind2 = tmpPropName.find("_end_")
								#print start_ind2
		
								tmpStart = tmpPropName[start_ind1:start_ind2]
								#print "start " + tmpStart
								
								if tmpStart and len(tmpStart) > 0 and int(tmpStart) > 0:
									end_ind_1 = start_ind2 + len("_end_")
									#print end_ind_1
									end_ind_2 = tmpPropName.find("_", end_ind_1)
									#print end_ind_2
			
									tmpEnd = tmpPropName[end_ind_1:end_ind_2]
									#print "end " + tmpEnd
									
									if tmpEnd and len(tmpEnd) > 0 and int(tmpEnd) > 0:
										tmpDirName = prefix + fAlias + "_:_" + tmpPropValue + "_start_" + `int(tmpStart)` + "_end_" + `int(tmpEnd)` + "_orientation" + postfix
										#print tmpDirName
										
										if form.has_key(tmpDirName):
											tmpDir = form.getvalue(tmpDirName)
											#print "FOUND DIRECTION " + tmpDirName
								
											# Nov. 13/08: If there are duplicates, select one
											if utils.isList(tmpDir):
												#print "Descriptor " + tmpDescr
												tmpDir = tmpDir[0]
								
											#fID = prop_Alias_ID_Map[fAlias]
											fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
											
											# descriptor
											if featureDescriptors.has_key(prop_Alias_Name_Map[fAlias]):
												tmpDescrName = featureDescriptors[prop_Alias_Name_Map[fAlias]]
												tmpDescrAlias = prop_Name_Alias_Map[tmpDescrName]
												#print "DESCRIPTOR " + tmpDescrAlias
										
												tmpDescrField = prefix + tmpDescrAlias + "_:_" + tmpPropValue + "_start_" + tmpStart + "_end_" + tmpEnd + postfix
	
												#print tmpDescrField
												
												if form.has_key(tmpDescrField):
													
													tmpDescr = form.getvalue(tmpDescrField)
										
													# Nov. 13/08: If there are duplicates, select one
													if utils.isList(tmpDescr):
														#print "Descriptor " + tmpDescr
														tmpDescr = tmpDescr[0]
										
													# June 8, 2010: For descriptor have to go to textbox to fetch Other values
													if tmpDescr.lower() == 'other':
														
														if fAlias == "tag":
															descrAlias = "tag_position"
														elif fAlias == "promoter":
															descrAlias = "expression_system"
											
														tmpDescr_text = prefix + tmpDescrAlias + "_:_" + tmpPropValue + "_start_" + tmpStart + "_end_" + tmpEnd + "_name_txt"
											
														#print tmpDescr_text
											
														tmpDescr = form.getvalue(tmpDescr_text)
										
													if utils.isList(tmpDescr):
														tmpDescr = tmpDescr[0]
										
													#print tmpDescr

													# March 29, 2010: Update set
													tmpDescrPropID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[tmpDescrAlias], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
										
													rTypeDescrAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, tmpDescrPropID)
													
													descrSetGroupID = sHandler.findPropSetGroupID(tmpDescrPropID)
													descr_ssetID = sHandler.findSetValueID(descrSetGroupID, tmpDescr)
													
													if sHandler.findSetValueID(descrSetGroupID, tmpDescr) <= 0:
														descr_ssetID = sHandler.addSetValue(descrSetGroupID, tmpDescr)

													if not sHandler.existsReagentTypeAttributeSetValue(rTypeDescrAttributeID, descr_ssetID):
														#print "HERE, updating"
														sHandler.addReagentTypeAttributeSetEntry(rTypeDescrAttributeID, descr_ssetID)
												else:
													tmpDescr = ""
											else:
												tmpDescr = ""
										
											if not rHandler.existsPropertyValue(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDescr, tmpDir):
												#print "adding"
												rHandler.addReagentProperty(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDir)
												
												rHandler.setReagentFeatureDescriptor(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDescr)
											
											# March 29, 2010: Update set
											rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, fID)
											
											setGroupID = sHandler.findPropSetGroupID(fID)
											ssetID = sHandler.findSetValueID(setGroupID, tmpPropValue)
											
											if sHandler.findSetValueID(setGroupID, tmpPropValue) <= 0:
												ssetID = sHandler.addSetValue(setGroupID, tmpPropValue)
											
											if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
												sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
									
										else:
											pass
									else:
										pass
								else:
									pass
							else:
								pass
						else:
							pass
			
			# Match db IDs to property aliases
			for tmpProp in newPropsDict_name.keys():
				if prop_Alias_ID_Map.has_key(tmpProp):
					# July 2/09: propertyID <= propCatID
					#propID = prop_Alias_ID_Map[tmpProp]	# removed July 2/09
					propID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[tmpProp], prop_Category_Name_ID_Map["DNA Sequence Features"])
					newPropsDict_id[propID] = newPropsDict_name[tmpProp]
			
			# ACTION
			#rHandler.updateReagentSequenceFeatures(rID, seqFeatures)

			# June 8/08: Update sites
			seqID = rHandler.findDNASequenceKey(rID)
			seq = dnaHandler.findSequenceByID(seqID)
			
			#print `five_prime_site`
			#rHandler.updateSitePositions(rID, seq, five_prime_site, three_prime_site)
			
			# June 16/08: Update protein translation
			if rTypeID == '2':
				iHandler.updateInsertProteinSequence(rID)
			
			# return to detailed view - Moved here July 7/08
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)

		# April 10/08: Associations
		elif form.has_key("save_parents"):
			
			#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
			#print
			#print `form`
			#print "hoho"
			
			five_prime_site = []		# jan. 19/09 - initialize arrays for potential hybrid sites
			three_prime_site = []		# jan. 19/09 - initialize arrays for potential hybrid sites
			
			assocPropsDict = {}
			newPropsDict_name = {}			# e.g. ('status', 'Completed')
			newPropsDict_id = {}			# e.g. ('3', 'Completed') - db ID instead of property name

			#assocTypes = reagent.getAssociationTypes()
			
			assocTypes = rtAssocHandler.findReagentTypeAssocProps(rTypeID)
			#print `assocTypes`
			
			assocAliases = {}
			associations = {}
			
			rType = reagentType_ID_Name_Map[int(rTypeID)]
			
			for assocPropID in assocTypes:
				#print assocPropID
				
				#assocAlias = assoc_ID_Alias_Map[assocPropID]
				#assocID = assoc_Name_ID_Map[aType]
				
				#tmpPropName = assocPrefix + assocAlias + postfix
				
				tmpPropName = rType + "_" + assocPrefix + `assocPropID` + postfix
				#print tmpPropName
				
				#assocAliases[assocAlias] = aType
				#associations[assocID] = aType
			
			#for assocAlias in assocAliases:
				#tmpPropName = assocPrefix + assocAlias + postfix
				#print tmpPropName
				
			#for assocID in assocTypes:
				#tmpPropName = assocPrefix + `assocID` + postfix
				
				if form.has_key(tmpPropName):
					tmpPropVal = form.getvalue(tmpPropName)
					#print tmpPropVal
					#assocID = assoc_Alias_ID_Map[assocAlias]
					#print assocID
					#assocPropsDict[assocID] = tmpPropVal
					assocPropsDict[assocPropID] = tmpPropVal
			
			#print `assocPropsDict`
			
			# Verify parent project access AND reconstruct the sequence IFF parent values are changed
			newSeq = ""
	
			# Fetch projects the user has AT LEAST Read access to (i.e. if he is explicitly declared a Writer on a project but not declared a Reader, include that project, plus all public projects)
			currReadProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Reader')
			currWriteProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Writer')
			publicProj = packetHandler.findAllProjects(isPrivate="FALSE")
			
			# list of Packet OBJECTS
			currUserWriteProjects = utils.unique(currReadProj + currWriteProj + publicProj)
			
			uPackets = []
			
			for p in currUserWriteProjects:
				uPackets.append(p.getNumber())
		
			# Get project IDs of parents
			packetPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["packet id"], prop_Category_Name_ID_Map["General Properties"])

			# July 7/08: site property IDs
			insertCloningSites = []
			
			#fpcs_prop_id = prop_Name_ID_Map["5' cloning site"]
			fpcs_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
			
			#tpcs_prop_id = prop_Name_ID_Map["3' cloning site"]
			tpcs_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])

			# Vector Function - needed later to determine sequence construction
			#vFunc_prop_id = prop_Name_ID_Map["vector type"]
			vFunc_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["vector type"], prop_Category_Name_ID_Map["General Properties"])

			vFunc = rHandler.findSimplePropertyValue(rID, vFunc_prop_id)
			
			seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])
			
			fpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
			
			tpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])

			cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
			
			namePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["name"], prop_Category_Name_ID_Map["General Properties"])
			
			vTypePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["vector type"], prop_Category_Name_ID_Map["General Properties"])
			
			projectPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["packet id"], prop_Category_Name_ID_Map["General Properties"])
			
			descrPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["description"], prop_Category_Name_ID_Map["General Properties"])
			
			verifPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["verification"], prop_Category_Name_ID_Map["General Properties"])

			# Differentiate parents by reagent type and subtype
			if rTypeID == '1':
				#print "Content-type:text/html"
				#print
				#print `form`

				# Vector.  Check Parent Vector, and either Insert or IPV, depending on the cloning method
				# Modified July 7/08
				#if form.has_key("assoc_parent_vector"):
				if form.has_key("PV"):
					pvVal = form.getvalue("PV")
					#pvVal = form.getvalue("assoc_parent_vector")
	
					if len(pvVal) > 0:
						pvID = rHandler.convertReagentToDatabaseID(pvVal)
	
						try:
							pvProjectID = int(rHandler.findSimplePropertyValue(pvID, packetPropID))
							pvSeqID = rHandler.findDNASequenceKey(pvID)
							pvSequence = dnaHandler.findSequenceByID(pvSeqID)
						
						except TypeError:
							
							# July 4/08
							i = PVProjectAccessException("Invalid Parent Vector project ID")
							
							print "Content-type:text/html"
							print
							print `i.err_code()`
							return
					else:
						pvID = -1
						pvProjectID = -1
						#errVal = True
						
						# July 4/08
						i = PVProjectAccessException("Invalid Parent Vector project ID")
						
						print "Content-type:text/html"
						print
						print `i.err_code()`
						return
				else:
					pvProjectID = -1
					#errVal = True
					
					# July 4/08
					i = PVProjectAccessException("Invalid Parent Vector project ID")
					
					print "Content-type:text/html"
					print
					print `i.err_code()`
					return
	
				if pvProjectID > 0 and currUser.getCategory() != 'Admin' and pvProjectID not in uPackets:
					
					# updated July 4/08
					i = PVProjectAccessException("Invalid Parent Vector project ID")
					
					print "Content-type:text/html"
					print
					print `i.err_code()`
					return
					
					#errVal = True
					#utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID` + "&mode=Modify&ErrCode=3&PV=" + pvVal + "#rp")
				
				# Find out if parents were changed and, if yes, update sequence according to vector type
				oldAssoc = rHandler.findAllReagentAssociationsByName(rID)
	
				# get the old parent vector association value
				if oldAssoc.has_key("vector parent id"):
					oldPVID = oldAssoc["vector parent id"]
				else:
					oldPVID = -1
	
				if cloning_method == '1':
					
					isGateway = False
					
					# Non-recombination vector - Get the Insert
					if oldAssoc.has_key("insert id"):
						oldInsertID = oldAssoc["insert id"]
					else:
						oldInsertID = -1
					
					# Modified July 7/08
					#if form.has_key("assoc_insert_id"):
						#insertVal = form.getvalue("assoc_insert_id")
						
					if form.has_key("I"):
						insertVal = form.getvalue("I")
						
						if len(insertVal) > 0:
							insertID = rHandler.convertReagentToDatabaseID(insertVal)
							
							try:
								insertProjectID = int(rHandler.findSimplePropertyValue(insertID, packetPropID))
								
							except (TypeError, ValueError, IndexError):
								insertProjectID = 0
								
							if currUser.getCategory() != 'Admin' and insertProjectID not in uPackets:
								i = InsertProjectAccessException("You do not have read access to this project")
						
								print "Content-type:text/html"
								print
								print `i.err_code()`
								return
							
							# get Insert sequence ID for reconstitution
							insertSeqID = rHandler.findDNASequenceKey(insertID)
							insertSeq = dnaHandler.findSequenceByID(insertSeqID)
							
							# Get linkers from Insert
							insertLinkers = []
							
							# Update July 2/09
							#fpLinkerPropID = prop_Name_ID_Map["5' linker"]		# removed July 2/09
							#tpLinkerPropID = prop_Name_ID_Map["3' linker"]		# removed July 2/09
							
							fp_insert_linker = rHandler.findSimplePropertyValue(insertID, fpLinkerPropID)
							tp_insert_linker = rHandler.findSimplePropertyValue(insertID, tpLinkerPropID)
							
							# sept. 3/07 - needed to cast to string
							fwd_linker = ""
						
							if fp_insert_linker and len(fp_insert_linker) > 0 and fp_insert_linker != 0 and fp_insert_linker != '0':
								fp_insert_linker = fwd_linker + fp_insert_linker
							else:
								fp_insert_linker = fwd_linker
								
							# April 24/08
							if not tp_insert_linker or len(tp_insert_linker) == 0 or tp_insert_linker == 0 or tp_insert_linker == '0':
								tp_insert_linker = ""

							insertLinkers.append(fp_insert_linker)
							insertLinkers.append(tp_insert_linker)
							
							if form.has_key("reverse_insert") and form.getvalue("reverse_insert") == 'true':
								reverse_insert = True
							else:
								reverse_insert = False
							
							if form.has_key("custom_sites") and form.getvalue("custom_sites") == 'true':
								insert_fp_custom = form.getvalue("insert_custom_five_prime")
								insert_tp_custom = form.getvalue("insert_custom_three_prime")
								
								pv_fp_custom = form.getvalue("pv_custom_five_prime")
								pv_tp_custom = form.getvalue("pv_custom_three_prime")
							
								if insert_fp_custom == pv_fp_custom:
									fpcs = pv_fp_custom
								else:
									fpcs = pv_fp_custom + "-" + insert_fp_custom
							
								if insert_tp_custom == pv_tp_custom:
									tpcs = pv_tp_custom
								else:
									tpcs = insert_tp_custom + "-" + pv_tp_custom
							
								insertCloningSites.append(fpcs)
								insertCloningSites.append(tpcs)
								
								# Feb. 12/09
								if fpcs != tpcs:
									linear = False
								else:
									# Do NOT use linear=False in searching Insert sequence for IDENTICAL sites!!! (e.g. EcoRI children of V2327 Top) 
									linear = True
								
								five_prime_site = utils.make_array(fpcs)
								three_prime_site = utils.make_array(tpcs)
								
								if dnaHandler.isHybrid(five_prime_site):
									fpSite = dnaHandler.hybridSeq(five_prime_site)
									
									fp_h1 = dnaHandler.get_H_1(five_prime_site)
									pvSeq = Seq(pvSequence)
									
									if dnaHandler.enzDict.has_key(fp_h1):
										fp_h1_enz = dnaHandler.enzDict[fp_h1]
									
										# added Dec. 18/08
										fp_h1_seq = fp_h1_enz.elucidate().replace("_", "")
										fp_h1_clvg = fp_h1_seq.find("^")
										fp_h1_flank = fp_h1_seq[0:fp_h1_clvg]
										
										pv_fp_h1_pos = fp_h1_enz.search(pvSeq, False)
										pv_fp_h1_pos.sort()
										
										if len(pv_fp_h1_pos) > 0:
											fp_start = pv_fp_h1_pos[0] - len(fp_h1_flank)
											fp_end = fp_start + len(fp_h1_seq)
									
											fpStartPos = fp_start
											fpEndPos = fp_end
										
								if dnaHandler.isHybrid(three_prime_site):
									tpSite = dnaHandler.hybridSeq(three_prime_site)
									
									if not tpSite:
										i = HybridizationException("")
										err = i.err_code()
										print "Content-type:text/html"
										print
										print `err`
										return
									
									tp_h2 = dnaHandler.get_H_2(three_prime_site)
									pvSeq = Seq(pvSequence)
									
									if dnaHandler.enzDict.has_key(tp_h2):
										tp_h2_enz = dnaHandler.enzDict[tp_h2]
										#print tp_h2_enz
										
										tp_h2_seq = tp_h2_enz.elucidate().replace("_", "")
										tp_h2_clvg = tp_h2_seq.find("^")
										tp_h2_flank = tp_h2_seq[0:tp_h2_clvg]
										
										pv_tp_h2_pos = tp_h2_enz.search(pvSeq, False)
										pv_tp_h2_pos.sort()
										
										if len(pv_tp_h2_pos) > 0:
											pv_tp_start = pv_tp_h2_pos[len(pv_tp_h2_pos)-1] - len(tp_h2_flank)
											pv_tp_end = pv_tp_start + len(tpSite)
											
											pv_post = pvSequence[pv_tp_end:].lower()
											#print pv_post

							else:
								# get sites from Insert
								
								fp_insert_cs = rHandler.findSimplePropertyValue(insertID, fpcs_prop_id)
								tp_insert_cs = rHandler.findSimplePropertyValue(insertID, tpcs_prop_id)
								
								five_prime_site = utils.make_array(fp_insert_cs)
								three_prime_site = utils.make_array(tp_insert_cs)
								
								# Dec. 18/08: Check directional cloning
								if fp_insert_cs != tp_insert_cs:
									linear = False
								else:
									# Do NOT use linear=False in searching Insert sequence for IDENTICAL sites!!! (e.g. EcoRI children of V2327 Top) 
									linear = True
								
								if fp_insert_cs:
									# Check here if Insert sites are Gateway; if yes, compare them to Vector Type
									if fp_insert_cs.lower().find("attb") >= 0:
										isGateway = True
										insertCloningSites.append(fp_insert_cs)
									else:
										insertCloningSites.append(fp_insert_cs)
								else:
									insertCloningSites.append("")
								
								if tp_insert_cs:
									# again, check gateway (somewhat redundant but makes system more robust)
									if tp_insert_cs.lower().find('attb') >= 0:
										
										# set GW sites later
										isGateway = True
										insertCloningSites.append(tp_insert_cs)
									else:
										insertCloningSites.append(tp_insert_cs)
								else:
									insertCloningSites.append("")
							
							try:
								# Check if sites are empty
								if len(insertCloningSites) == 0:
									# Modified Feb. 12/09
									i = EmptyCloningSitesException("")
									err = i.err_code()
									#err = 13
									
									print "Content-type:text/html"
									print
									print `err`
									return
								
								if not isGateway:
									
									fp_insert_cs = insertCloningSites[0]
									tp_insert_cs = insertCloningSites[1]
									
									# Jan. 19/09:
									if not dnaHandler.isHybrid(five_prime_site):
										# Sept. 30/08: SfiI sequences from V1889
										if fp_insert_cs == 'SfiI':
											fpSite = "GGCCATTACGGCC"
										else:
											if enzDict.has_key(fp_insert_cs):
												fpSite = enzDict[fp_insert_cs]
											else:
												print "Content-type:text/html"
												print
												print "Unknown 5' site: " + fp_insert_cs
												return
										
										fp_start = insertSeq.lower().find(fpSite) + 1
										fp_end = fp_start + len(fpSite)
										
										if fp_start == 0:
											# Dec. 17/08: If sites are not found, use BioPython to look for degenerate sites with variable sequences
											tmpSeq = Bio.Seq.Seq(insertSeq)
											fp_cs = dnaHandler.enzDict[fp_insert_cs]
											
											# added Dec. 18/08
											fp_seq = fp_cs.elucidate().replace("_", "")
											fp_clvg = fp_seq.find("^")
											fp_flank = fp_seq[0:fp_clvg]
											
											#print "Content-type:text/html"
											#print
										
											tmp_fp_pos = fp_cs.search(tmpSeq, linear)
											tmp_fp_pos.sort()
										
											if len(tmp_fp_pos) > 0:
												fp_start = tmp_fp_pos[0] - len(fp_flank)
												fp_end = fp_start + len(fpSite)
												
												if fp_start == 0:
													fp_start = 0
													fp_end = 0
											else:
												fp_start = 0
												fp_end = 0
										
										# Compare to site positions stored for this Insert
										orig_fp_start = rHandler.findReagentFeatureStart(insertID, fpcs_prop_id)
										orig_fp_end = rHandler.findReagentFeatureEnd(insertID, fpcs_prop_id)
										
										# update Feb. 12/09
										if not reverse_insert and fp_insert_cs == fpSite and tp_insert_cs == tpSite and orig_fp_start != fp_start and orig_fp_end != fp_end:
											# error
											#err = 13
										
											# Modified Feb. 12/09
											i = EmptyCloningSitesException("")
											err = i.err_code()
											
											print "Content-type:text/html"
											print
											print `err`
											return
									
									# 3' site
									if not dnaHandler.isHybrid(three_prime_site):
										# Sept. 30/08: Special case: SfiI from V1889
										if tp_insert_cs == 'SfiI':
											tpSite = "GGCCGCCTCGGCC"
										else:
											if enzDict.has_key(tp_insert_cs):
												tpSite = enzDict[tp_insert_cs]
												#print tpSite
											else:
												print "Content-type:text/html"
												print
												print "Unknown 3' site: " + tp_insert_cs
												return
									
										# Jan. 22/09 - added 'rfind' and 'lower()'
										tp_start = insertSeq.lower().rfind(tpSite.lower()) + 1
										tp_end = tp_start + len(tpSite) - 1
										
										if tp_start == 0:
											# check for degenerate
											tmpSeq = Bio.Seq.Seq(insertSeq)
											tp_cs = dnaHandler.enzDict[tp_insert_cs]
											
											tp_seq = tp_cs.elucidate().replace("_", "")
											tp_clvg = tp_seq.find("^")
											tp_flank = tp_seq[0:tp_clvg]
											
											tmp_tp_pos = tp_cs.search(tmpSeq, linear)
											tmp_tp_pos.sort()
										
											if len(tmp_tp_pos) > 0:
												tp_start = tmp_tp_pos[len(tmp_tp_pos)-1] - len(tp_flank)
												tp_end = tp_start + len(tpSite)
												
												if tp_start == 0:
													tp_start = 0
													tp_end = 0
											else:
												tp_start = 0
												tp_end = 0
										
										# Feb. 12/09
										if not reverse_insert and fp_insert_cs == fpSite and tp_insert_cs == tpSite:
											orig_tp_start = rHandler.findReagentFeatureStart(insertID, tpcs_prop_id)
											orig_tp_end = rHandler.findReagentFeatureEnd(insertID, tpcs_prop_id)
										
											if orig_tp_start != tp_start and orig_tp_end != tp_end:
												# error
												#err = 13
										
												# Modified Feb. 12/09
												i = EmptyCloningSitesException("")
												err = i.err_code()
												
												print "Content-type:text/html"
												print
												print `err`
												return
								
									# nov. 6/08
									if (not dnaHandler.isHybrid(five_prime_site) and dnaHandler.gatewayDict.has_key(insertCloningSites[0])) or (not dnaHandler.isHybrid(three_prime_site) and dnaHandler.gatewayDict.has_key(insertCloningSites[1])) or (not dnaHandler.isHybrid(five_prime_site) and dnaHandler.recombDict.has_key(insertCloningSites[0])) or (not dnaHandler.isHybrid(three_prime_site) and dnaHandler.recombDict.has_key(insertCloningSites[1])):
									
										# Non-recombination vector attempted with gateway or LoxP Insert - disallow
										i = InsertSitesException("Wrong sites on Insert")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print `err`
										return
									
									cdnaStart = iHandler.findCDNAStart(insertID)
									cdnaEnd = iHandler.findCDNAEnd(insertID)
									
									try:
										newSeq = dnaHandler.constructNonRecombSequence(pvSeqID, insertSeq, insertCloningSites, reverse_insert)		# Oct. 30/08
										
										if dnaHandler.isHybrid(three_prime_site):
											tp_start = newSeq.lower().find(pv_post.lower()) - len(tpSite)
											tp_end = tp_start + len(tpSite)
									
											tpStartPos = tp_start
											tpEndPos = tp_end
										
										newSeqID = int(dnaHandler.matchSequence(newSeq))
							
										if newSeqID <= 0:
											newSeqID = int(dnaHandler.insertSequence(newSeq))
											
										# Now save parents - delete all previous information
										pvAssocProp = aHandler.findAssocPropID("vector parent id")
										insertAssocProp = aHandler.findAssocPropID("insert id")
										
										rAssocID = raHandler.findReagentAssociationID(rID)
										
										rHandler.deleteReagentAssociationProp(rID, pvAssocProp)
										rHandler.addAssociationValue(rID, pvAssocProp, pvID, rAssocID)
					
										rHandler.deleteReagentAssociationProp(rID, insertAssocProp)
										rHandler.addAssociationValue(rID, insertAssocProp, insertID, rAssocID)
									
										# Save new sequence
										rHandler.updateDNASequence(rID, newSeqID)
									
										# Remap features - Delete old features first
										rHandler.deleteReagentFeatures(rID)
										
										#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["5' cloning site"])
										#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["3' cloning site"])
										
										# Nov. 6/08 - Delete linkers separately, as they're not included in features list (if they don't exist no harm done)
										rHandler.deleteReagentProperty(rID, fpLinkerPropID)
										rHandler.deleteReagentProperty(rID, tpLinkerPropID)

										newSeq = utils.squeeze(newSeq).lower()
										
										# Avoid duplicate values - may already have ReagentPropList_tbl entries for this reagent, clear them before saving (for consistency)
										
										rHandler.deleteReagentProperty(rID, seqPropID)
										rHandler.addReagentProperty(rID, seqPropID, newSeqID)
										
										# find cDNA portion on **ORIGINAL** Insert sequence
										insert_cdnaStart = iHandler.findCDNAStart(insertID)
										insert_cdnaEnd = iHandler.findCDNAEnd(insertID)
										
										# Updated June 1/08: added 'if-else'
										if insert_cdnaStart > 0 and insert_cdnaEnd > 0:
											cdnaSeq = insertSeq[insert_cdnaStart-1:insert_cdnaEnd]
										else:
											cdnaSeq = insertSeq
										
										# Jan. 21/09
										if reverse_insert:
											cdnaSeq = dnaHandler.reverse_complement(cdnaSeq)
										
											# Update July 2/09: pass cdnaPropID to setPropertyDirection() function in combination with its category
											rHandler.setPropertyDirection(rID, cdnaPropID, 'reverse')
										
											cdnaStart = newSeq.lower().find(cdnaSeq.lower()) + 1
											cdnaEnd = cdnaStart + len(cdnaSeq)
									
										# July 2/09
										#cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
										rHandler.setPropertyPosition(rID, cdnaPropID, "startPos", cdnaStart)
										rHandler.setPropertyPosition(rID, cdnaPropID, "endPos", cdnaEnd)
										
										# 5' cloning site (sequence in lowercase, search for site in lowercase too)
										if not dnaHandler.isHybrid(five_prime_site):
											if newSeq.find(fpSite.lower()) >= 0:
												fpStartPos = newSeq.find(fpSite.lower()) + 1
												fpEndPos = fpStartPos + len(fpSite)
											else:
												# Dec. 17/08
												# reason could be that the site is a degenerate - try one more time with BioPython
												tmpSeq = Bio.Seq.Seq(newSeq)
												fp_cs = dnaHandler.enzDict[fp_insert_cs]
										
												# added Dec. 18/08
												fp_seq = fp_cs.elucidate().replace("_", "")
												fp_clvg = fp_seq.find("^")
												fp_flank = fp_seq[0:fp_clvg]
												
												tmp_fp_pos = fp_cs.search(tmpSeq, linear)
												tmp_fp_pos.sort()
										
												if len(tmp_fp_pos):
													fpStartPos = tmp_fp_pos[0] - len(fp_flank)
													fpEndPos = fpStartPos + len(fpSite)
													
													if fpStartPos == 0:
														fpStartPos = 0
														fpEndPos = 0
												else:
													fpStartPos = 0
													fpEndPos = 0
										
										# 3' cloning site
										if not dnaHandler.isHybrid(three_prime_site):
											if newSeq.rfind(tpSite.lower()) >= 0:
												tpStartPos = newSeq.rfind(tpSite.lower()) + 1		# look from END of sequence?????
												tpEndPos = tpStartPos + len(tpSite)
											else:	
												# Dec. 17/08
												# reason could be that the site is a degenerate - try one more time with BioPython
												tmpSeq = Bio.Seq.Seq(newSeq)
												tp_cs = dnaHandler.enzDict[tp_insert_cs]
										
												# added Dec. 18/08
												tp_seq = tp_cs.elucidate().replace("_", "")
												tp_clvg = tp_seq.find("^")
												tp_flank = tp_seq[0:tp_clvg]
												
												tmp_tp_pos = tp_cs.search(tmpSeq, linear)
												tmp_tp_pos.sort()
										
												if len(tmp_tp_pos) > 0:
													tpStartPos = tmp_tp_pos[len(tmp_tp_pos)-1] - len(tp_flank)
													tpEndPos = tpStartPos + len(tpSite)
													
													if tpStartPos == 0:
														tpStartPos = 0
														tpEndPos = 0
												else:
													tpStartPos = 0
													tpEndPos = 0
											
										# Nov. 6/08: Map linkers in the same fashion
										if fp_insert_linker and len(fp_insert_linker) >= 10:
										
											# Jan. 22/09
											if reverse_insert:
												fp_insert_linker = dnaHandler.reverse_complement(fp_insert_linker)
											
											fpLinkerStartPos = newSeq.find(fp_insert_linker.lower()) + 1
											fpLinkerEndPos = fpLinkerStartPos + len(fp_insert_linker)
											rHandler.addReagentProperty(rID, fpLinkerPropID, fp_insert_linker, fpLinkerStartPos, fpLinkerEndPos-1)
										else:
											fpLinkerStartPos = 0
											fpLinkerEndPos = 0
											
										# 3' linker
										if tp_insert_linker and len(tp_insert_linker) >= 10:
											
											# Jan. 22/09
											if reverse_insert:
												tp_insert_linker = dnaHandler.reverse_complement(tp_insert_linker)
										
											tpLinkerStartPos = newSeq.find(tp_insert_linker.lower()) + 1
											tpLinkerEndPos = tpLinkerStartPos + len(tp_insert_linker)
											rHandler.addReagentProperty(rID, tpLinkerPropID, tp_insert_linker, tpLinkerStartPos, tpLinkerEndPos-1)
										else:
											tpLinkerStartPos = 0
											tpLinkerEndPos = 0
										
										# Nov. 3/08 - check if 5' site is after 3' - if yes, look for the reverse sequence
										cdna_dir = 'forward'
										
										if fpStartPos > tpStartPos:
											cdnaSeq = dnaHandler.reverse_complement(cdnaSeq)
										
											# Update July 2/09: pass cdnaPropID to setPropertyDirection() function in combination with its category
											#cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
											rHandler.setPropertyDirection(rID, cdnaPropID, 'reverse')
										
										elif reverse_insert:
											# set orientation='reverse' but don't RC cDNA!!!
										
											# Update July 2/09: pass cdnaPropID to setPropertyDirection() function in combination with its category
											#cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
											rHandler.setPropertyDirection(rID, cdnaPropID, 'reverse')
										
										# cDNA (in lowercase already)
										if newSeq.find(cdnaSeq) >= 0:
											
											cdnaStart = newSeq.find(cdnaSeq) + 1		# may 22/08
											cdnaEnd = cdnaStart + len(cdnaSeq) - 1		# may 22/08
										else:
											cdnaStart = 0
											cdnaEnd = 0
										
										# July 2/09
										#cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
										rHandler.setPropertyPosition(rID, cdnaPropID, "startPos", cdnaStart)
										rHandler.setPropertyPosition(rID, cdnaPropID, "endPos", cdnaEnd)
										
										rHandler.addReagentProperty(rID, fpcs_prop_id, fp_insert_cs, fpStartPos, fpEndPos-1)
										rHandler.addReagentProperty(rID, tpcs_prop_id, tp_insert_cs, tpStartPos, tpEndPos-1)
										
										iFeatures = rHandler.findReagentSequenceFeatures(insertID)
										pvFeatures = rHandler.findReagentSequenceFeatures(pvID)
										
										# changes made Oct. 14/08
										tmp_dict = {}
										
										# Parent Vector features are found before 5' start and after 3' end on the new sequence
										for f in pvFeatures:
											fType = f.getFeatureType()
											#print fType
											
											if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
											
												# original feature positions on PV sequence
												pv_fStart = f.getFeatureStartPos()
												#print `pv_fStart`
												pv_fEnd = f.getFeatureEndPos()
												
												fSeq = pvSequence[pv_fStart-1:pv_fEnd].lower()
												tmp_dict[fSeq] = f
										
										for fSeq in tmp_dict.keys():
											f_tmp = tmp_dict[fSeq]
											
											if len(fSeq) >= 10:
												fList = utils.findall(newSeq.lower(), fSeq, [])
												
												for fIndex in fList:
													fpStart = fIndex + 1
													fpEnd = fpStart + len(fSeq) - 1
													
													if fpStart > 0 and fpEnd > 0:
														# If found, make sure this feature occurs BETWEEN the CLONING SITES
														if (fpStart < fpStartPos and fpEnd < fpStartPos) or (fpStart > fpEndPos and fpEnd > fpEndPos):
															
															fType = f_tmp.getFeatureType()
															fVal =  f_tmp.getFeatureName()
															pv_fDir = f_tmp.getFeatureDirection()
													
															# Nov. 4/08: fpStart >= tpEndPos, since tpEndPos is one greater than the actual site end value
															if (fpStart < fpStartPos and fpEnd < fpStartPos) or (fpStart >= tpEndPos and fpEnd > tpEndPos):
																#print fType + ": " + fVal + ": " + `fpStart` + "-" + `fpEnd`
																fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
																#rHandler.addReagentProperty(rID, prop_Name_ID_Map[fType], fVal, fpStart, fpEnd, pv_fDir)
																rHandler.addReagentProperty(rID, fID, fVal, fpStart, fpEnd, pv_fDir)
														
																if f_tmp.getFeatureDescrType():
																	fDescr = f_tmp.getFeatureDescrName()
																	fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
																	rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fpStart, fpEnd, fDescr)
								
										# Search for each Insert feature on the new sequence
										tmp_i_dict = {}
										
										for f in iFeatures:
											fType = f.getFeatureType()
											#print fType
											
											if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
												# feature positions on the Insert sequence - Modified May 21/08: Account for the fact that NOT the entire Insert sequence is used, only a subsequence
												fiStart = f.getFeatureStartPos()
												fiEnd = f.getFeatureEndPos()
												fSeq = insertSeq[fiStart:fiEnd].lower()
												tmp_i_dict[fSeq] = f
											
											
										for fSeq in tmp_i_dict.keys():
											f_tmp = tmp_i_dict[fSeq]
											
											if len(fSeq) >= 10:
												# Jan. 22/09
												if reverse_insert:
													fSeq = dnaHandler.reverse_complement(fSeq)
												
												fList = utils.findall(newSeq.lower(), fSeq, [])
												
												for fIndex in fList:
													fStart = newSeq.lower().find(fSeq)
													fEnd = fStart + len(fSeq)
													
													f_tmp.getFeatureName()
													
													# still, double check to make sure this feature occurs between the CLONING SITES on the resulting Vector sequence!!!!
													if fStart >= fpStartPos and fEnd <= tpEndPos:
														fType = f_tmp.getFeatureType()
														fVal =  f_tmp.getFeatureName()
														fiDir = f_tmp.getFeatureDirection()
										
														# Jan. 22/09
														if reverse_insert:
															fiDir = 'reverse'
														
														fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
			
														#rHandler.addReagentProperty(rID, prop_Name_ID_Map[fType], fVal, fStart, fEnd, fiDir)
														rHandler.addReagentProperty(rID, fID, fVal, fStart, fEnd, fiDir)
													
														if f.getFeatureDescrType():
															fDescr = f_tmp.getFeatureDescrType()
															
															# Updated July 2/09
															#fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
															rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fStart, fEnd, fDescr)
									
										
										if form.has_key("newVectorName"):
											newName = form.getvalue("newVectorName")
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["name"])
											rHandler.deleteReagentProperty(rID, namePropID)
											#rHandler.addReagentProperty(rID, prop_Name_ID_Map["name"], newName)
											rHandler.addReagentProperty(rID, namePropID, newName)
										else:
											rHandler.deleteReagentProperty(rID, namePropID)
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["name"])
										
										if form.has_key("newVectorType"):
											newVectorType = form.getvalue("newVectorType")
											
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["vector type"])
											rHandler.deleteReagentProperty(rID, vTypePropID)
											#rHandler.addReagentProperty(rID, prop_Name_ID_Map["vector type"], newVectorType)
											rHandler.addReagentProperty(rID, vTypePropID, newVectorType)
										else:
											rHandler.deleteReagentProperty(rID, vTypePropID)
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["vector type"])
										
										if form.has_key("newProjectID"):
											newProjectID = form.getvalue("newProjectID")
										
											# Update July 2/09: pass projectPropID to changePropertyValue() function in combination with its category
											rHandler.changePropertyValue(rID, projectPropID, newProjectID)
										else:
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["project id"])
											rHandler.deleteReagentProperty(rID, projectPropID)
										
										if form.has_key("newDescription"):
											newDescription = form.getvalue("newDescription")
											
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["description"])
											rHandler.deleteReagentProperty(rID, descrPropID)
											#rHandler.addComment(rID, prop_Name_ID_Map["description"], newDescription)
											rHandler.addComment(rID, descrPropID, newDescription)
										else:
											rHandler.deleteReagentProperty(rID, descrPropID)
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["description"])
										
										if form.has_key("newVerification"):
											newVerification = form.getvalue("newVerification")
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["verification"])
										
											rHandler.deleteReagentProperty(rID, verifPropID)
											#rHandler.addReagentProperty(rID, prop_Name_ID_Map["verification"], newVerification)
											rHandler.addReagentProperty(rID, verifPropID, newVerification)
										else:
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["verification"])
											rHandler.deleteReagentProperty(rID, verifPropID)
								
										print "Content-type:text/html"
										print
										print "0"
										return
								
									except InsertSitesException:
										i = InsertSitesException("Wrong sites on Insert")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print `err`
										return
								
									except InsertSitesNotFoundOnParentSequenceException:
										i = InsertSitesNotFoundOnParentSequenceException("Insert sites not found on parent vector sequence")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print `err`
										return
								
									except MultipleSiteOccurrenceException:
										i = MultipleSiteOccurrenceException("Site found more than once on parent vector sequence")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print `err`
										return
										
									except HybridizationException:
										i = HybridizationException("Sites cannot be hybridized")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print `err`
										return
								
									except FivePrimeAfterThreePrimeException:
										
										i = FivePrimeAfterThreePrimeException("5' site occurs after 3' site on parent vector sequence")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print `err`
										return
								
									# June 2/08
									except InvalidDonorVectorSitesNotFoundException:
										i = InvalidDonorVectorSitesNotFoundException("LoxP sites not found on donor sequence")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print `err`
										return
										
									except InvalidDonorVectorMultipleSitesException:
										i = InvalidDonorVectorMultipleSitesException("LoxP sites occur more than twice on donor sequence")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print `err`
										return
								
									except InvalidDonorVectorSingleSiteException:
										i = InvalidDonorVectorSingleSiteException("Donor vector sequence contains a singe LoxP site")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print `err`
										return
										
									except CloningSitesNotFoundInInsertException:
										i = CloningSitesNotFoundInInsertException("")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print `err`
										return
									
									# Dec. 14/09
									except EmptyParentVectorSequenceException:
										i = EmptyParentVectorSequenceException("The sequence of the parent Vector provided is empty.")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print "Error: " + i.toString() + " Please verify your input."
										return
									
									# Dec. 14/09
									except EmptyParentInsertSequenceException:
										i = EmptyParentInsertSequenceException("The sequence of the parent Insert provided is empty.")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print "Error: " + i.toString() + " Please verify your input."
										return
									
									# Dec. 14/09
									except EmptyInsertParentVectorSequenceException:
										i = EmptyInsertParentVectorSequenceException("The sequence of the Insert Parent Vector provided is empty.")
										err = i.err_code()
									
										print "Content-type:text/html"
										print
										print "Error: " + i.toString() + " Please verify your input."
										return
									
								# Gateway Entry clone
								else:
									fp_insert_cs = 'attL1'
									tp_insert_cs = 'attL2'
									
									# Special sequences - Changed May 29/08 after discussion with Karen
									fpSite = 'tttgtacaaaaaa'
									tpSite = 'tttcttgtacaaagtt'
									
									# recompute sequence
									newSeq = dnaHandler.entryVectorSequence(pvSeqID, insertSeq)
									newSeqID = int(dnaHandler.matchSequence(newSeq))
								
									if newSeqID <= 0:
										newSeqID = int(dnaHandler.insertSequence(newSeq))
									
									newSeq = utils.squeeze(newSeq).lower()
									
									# update sequence
									rHandler.deleteReagentProperty(rID, seqPropID)
									rHandler.addReagentProperty(rID, seqPropID, newSeqID)

									# Save parents - delete all previous information
									pvAssocProp = aHandler.findAssocPropID("vector parent id")
									insertAssocProp = aHandler.findAssocPropID("insert id")
									
									rAssocID = raHandler.findReagentAssociationID(rID)
									
									rHandler.deleteReagentAssociationProp(rID, pvAssocProp)
									rHandler.addAssociationValue(rID, pvAssocProp, pvID, rAssocID)
				
									rHandler.deleteReagentAssociationProp(rID, insertAssocProp)
									rHandler.addAssociationValue(rID, insertAssocProp, insertID, rAssocID)
								
									# find cDNA portion on **ORIGINAL** Insert sequence
									insert_cdnaStart = iHandler.findCDNAStart(insertID)
									insert_cdnaEnd = iHandler.findCDNAEnd(insertID)

									if insert_cdnaStart > 0 and insert_cdnaEnd > 0:
										cdnaSeq = insertSeq[insert_cdnaStart-1:insert_cdnaEnd]
									else:
										cdnaSeq = insertSeq

									# Remap features - Delete old values first
									rHandler.deleteReagentFeatures(rID)
								
									# Nov. 6/08 - Delete linkers separately, as they're not included in features list
									#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["5' linker"])
									#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["3' linker"])
									
									rHandler.deleteReagentProperty(rID, fpLinkerPropID)
									rHandler.deleteReagentProperty(rID, tpLinkerPropID)
									
									#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["5' cloning site"])
									#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["3' cloning site"])
									
									# 5' cloning site (sequence in lowercase, search for site in lowercase too)
									if newSeq.find(fpSite.lower()) >= 0:
										fpStartPos = newSeq.find(fpSite.lower()) + 1
										fpEndPos = fpStartPos + len(fpSite)
									else:
										fpStartPos = 0
										fpEndPos = 0
										
									# 3' cloning site
									if newSeq.rfind(tpSite.lower()) >= 0:
										tpStartPos = newSeq.rfind(tpSite.lower()) + 1		# look from END of sequence?????
										tpEndPos = tpStartPos + len(tpSite)
									else:	
										tpStartPos = 0
										tpEndPos = 0
										
									# Nov. 6/08: Map linkers in the same fashion
									if fp_insert_linker and len(fp_insert_linker) >= 10:
										fpLinkerStartPos = newSeq.find(fp_insert_linker.lower()) + 1
										fpLinkerEndPos = fpLinkerStartPos + len(fp_insert_linker)
										#rHandler.addReagentProperty(rID, prop_Name_ID_Map["5' linker"], fp_insert_linker, fpLinkerStartPos, fpLinkerEndPos-1)
										rHandler.addReagentProperty(rID, fpLinkerPropID, fp_insert_linker, fpLinkerStartPos, fpLinkerEndPos-1)
									else:
										fpLinkerStartPos = 0
										fpLinkerEndPos = 0
										
									# 3' linker
									if tp_insert_linker and len(tp_insert_linker) >= 10:
										tpLinkerStartPos = newSeq.find(tp_insert_linker.lower()) + 1
										tpLinkerEndPos = tpLinkerStartPos + len(tp_insert_linker)
										#rHandler.addReagentProperty(rID, prop_Name_ID_Map["3' linker"], tp_insert_linker, tpLinkerStartPos, tpLinkerEndPos-1)
										rHandler.addReagentProperty(rID, tpLinkerPropID, tp_insert_linker, tpLinkerStartPos, tpLinkerEndPos-1)
									else:
										tpLinkerStartPos = 0
										tpLinkerEndPos = 0
									
									# Nov. 3/08 - check if 5' site is after 3' - if yes, look for the reverse sequence
									cdna_dir = 'forward'
									
									if fpStartPos > tpStartPos:
										cdnaSeq = dnaHandler.reverse_complement(cdnaSeq)
										
										# Update July 2/09: pass cdnaPropID to setPropertyDirection() function in combination with its category
										#cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
										rHandler.setPropertyDirection(rID, cdnaPropID, 'reverse')
										
									# cDNA (in lowercase already)
									if newSeq.find(cdnaSeq) >= 0:
										
										cdnaStart = newSeq.find(cdnaSeq) + 1		# may 22/08
										cdnaEnd = cdnaStart + len(cdnaSeq) - 1		# may 22/08
									else:
										cdnaStart = 0
										cdnaEnd = 0
									
									# July 2/09
									#cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
									
									rHandler.setPropertyPosition(rID, cdnaPropID, "startPos", cdnaStart)
									rHandler.setPropertyPosition(rID, cdnaPropID, "endPos", cdnaEnd)
									
									rHandler.addReagentProperty(rID, fpcs_prop_id, fp_insert_cs, fpStartPos, fpEndPos-1)
									rHandler.addReagentProperty(rID, tpcs_prop_id, tp_insert_cs, tpStartPos, tpEndPos-1)
									
									# Remap features
									iFeatures = rHandler.findReagentSequenceFeatures(insertID)
									pvFeatures = rHandler.findReagentSequenceFeatures(pvID)
									
									# Parent Vector features are found before cDNA start and after cDNA end on the new sequence
									for f in pvFeatures:
										fType = f.getFeatureType()
										
										if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
										
											# original feature positions on PV sequence
											pv_fStart = f.getFeatureStartPos()
											pv_fEnd = f.getFeatureEndPos()
										
											fVal = f.getFeatureName()
											pv_fDir = f.getFeatureDirection()
											
											if pv_fStart > 0 and pv_fEnd > 0:
												
												# Features from PV are inherited IFF they are located **entirely** before the 5' cloning site or after 3' cloning site
												# Hence: May 22/08 - Find 5' start and 3' end on **original** PV sequence
												pv_fpcs_start = pvSequence.lower().find(fpSite)
												pv_tpcs_end = pvSequence.lower().find(tpSite) + len(tpSite)
												
												fSeq = pvSequence[pv_fStart-1:pv_fEnd].lower()
								
												# June 4/08 - Search for the feature IFF > 10 nts
												if len(fSeq) >= 10:
								
													# Look for this feature on the NEWly reconstituted Vector
													fIndex = newSeq.lower().find(fSeq)
								
													if fIndex >= 0:
														fpStart = fIndex + 1
														fpEnd = fpStart + len(fSeq) - 1
														
														# If found, make sure this feature occurs either before or after the INSERT - i.e. before 5' cloning site start OR after 3' cloning site end
														if (fpStart < fpStartPos and fpEnd < fpStartPos) or (fpStart >= tpEndPos and fpEnd > tpEndPos):
															fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
															#rHandler.addReagentProperty(rID, prop_Name_ID_Map[fType], fVal, fpStart, fpEnd, pv_fDir)
															rHandler.addReagentProperty(rID, fID, fVal, fpStart, fpEnd, pv_fDir)
												
															if f.getFeatureDescrType():
																fDescr = f.getFeatureDescrName()
										
																# Updated July 2/09
																#fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
																rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fpStart, fpEnd, fDescr)
											
									# Search for each Insert feature on the new sequence
									# June 6/08: Added 'if' statement - needs testing
									for f in iFeatures:
										fType = f.getFeatureType()
										#print fType
										
										if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
											# feature positions on the Insert sequence - Modified May 21/08: Account for the fact that NOT the entire Insert sequence is used, only a subsequence
											fiStart = f.getFeatureStartPos()
											fiEnd = f.getFeatureEndPos()
											fVal = f.getFeatureName()
											#print fVal
											fSeq = insertSeq[fiStart:fiEnd].lower()
										
											if len(fSeq) >= 10:
												fStart = newSeq.lower().find(fSeq)
												fEnd = fStart + len(fSeq)
											
												if fStart >= 1:
								
													# Insert features, on the other hand, must occur entirely WITHIN the Insert - i.e. between the cloning sites (Oct. 30/08)
													if fStart >= fpStartPos and fEnd <= tpEndPos:
														# Orientation
														fiDir = f.getFeatureDirection()
														fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
											
														#rHandler.addReagentProperty(rID, prop_Name_ID_Map[fType], fVal, fStart, fEnd, fiDir)
														rHandler.addReagentProperty(rID, fID, fVal, fStart, fEnd, fiDir)
													
														if f.getFeatureDescrType():
															fDescr = f.getFeatureDescrType()
										
															# Updated July 2/09
															#fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
															rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fStart, fEnd, fDescr)
										
										if form.has_key("newVectorName"):
											newName = form.getvalue("newVectorName")
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["name"])
											rHandler.deleteReagentProperty(rID, namePropID)
											#rHandler.addReagentProperty(rID, prop_Name_ID_Map["name"], newName)
											rHandler.addReagentProperty(rID, namePropID, newName)
										else:
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["name"])
											rHandler.addReagentProperty(rID, namePropID, newName)
										
										if form.has_key("newVectorType"):
											newVectorType = form.getvalue("newVectorType")
											rHandler.deleteReagentProperty(rID, vTypePropID)
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["vector type"])
											#rHandler.addReagentProperty(rID, prop_Name_ID_Map["vector type"], newVectorType)
											rHandler.addReagentProperty(rID, vTypePropID, newVectorType)
										else:
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["vector type"])
											rHandler.deleteReagentProperty(rID, vTypePropID)
										
										if form.has_key("newProjectID"):
											newProjectID = form.getvalue("newProjectID")
										
											# Update July 2/09: pass projectPropID to changePropertyValue() function in combination with its category
											rHandler.changePropertyValue(rID, projectPropID, newProjectID)
										else:
											rHandler.deleteReagentProperty(rID, projectPropID)
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["project id"])
										
										if form.has_key("newDescription"):
											newDescription = form.getvalue("newDescription")
											rHandler.deleteReagentProperty(rID, descrPropID)
											rHandler.addComment(rID, descrPropID, newDescription)
										else:
											rHandler.deleteReagentProperty(rID, descrPropID)
											
											#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["description"])
										
										if form.has_key("newVerification"):
											newVerification = form.getvalue("newVerification")
											rHandler.deleteReagentProperty(rID, verifPropID)
											rHandler.addReagentProperty(rID, verifPropID, newVerification)
										else:
											rHandler.deleteReagentProperty(rID, verifPropID)
										
										print "Content-type:text/html"
										print
										print "0"
										return
										
									else:
										#err = 13
										
										# Modified Feb. 12/09
										i = EmptyCloningSitesException("")
										err = i.err_code()
										
										print "Content-type:text/html"
										print
										print `err`
										return
							
							except InsertSitesException:
								i = InsertSitesException("Wrong sites on Insert")
								
								print "Content-type:text/html"
								print
								print `i.err_code()`
								return
							
							except InsertSitesNotFoundOnParentSequenceException:
								i = InsertSitesNotFoundOnParentSequenceException("Insert sites not found on parent vector sequence")
								
								print "Content-type:text/html"
								print
								print `i.err_code()`
								return
							
							except MultipleSiteOccurrenceException:
								i = MultipleSiteOccurrenceException("Site found more than once on parent vector sequence")
								
								print "Content-type:text/html"
								print
								print `i.err_code()`
								return
								
							except HybridizationException:
								i = HybridizationException("Sites cannot be hybridized")
								
								print "Content-type:text/html"
								print
								print `i.err_code()`
								return
							
							except FivePrimeAfterThreePrimeException:
								i = FivePrimeAfterThreePrimeException("5' site occurs after 3' site on parent vector sequence")
								
								print "Content-type:text/html"
								print
								print `i.err_code()`
								return
							
							except InvalidDonorVectorSitesNotFoundException:
								i = InvalidDonorVectorSitesNotFoundException("LoxP sites not found on donor sequence")
						
								print "Content-type:text/html"
								print
								print `i.err_code()`
								return
							
							except InvalidDonorVectorMultipleSitesException:
								i = InvalidDonorVectorMultipleSitesException("LoxP sites occur more than twice on donor sequence")
							
								print "Content-type:text/html"
								print
								print `i.err_code()`
								return
							
							except InvalidDonorVectorSingleSiteException:
								i = InvalidDonorVectorSingleSiteException("Donor vector sequence contains a singe LoxP site")
								
								print "Content-type:text/html"
								print
								print `i.err_code()`
								return
							
							# Dec. 14/09
							except EmptyParentVectorSequenceException:
								i = EmptyParentVectorSequenceException("The sequence of the parent Vector provided is empty.")
								err = i.err_code()
							
								print "Content-type:text/html"
								print
								print "Error: " + i.toString() + " Please verify your input."
								return
							
							# Dec. 14/09
							except EmptyParentInsertSequenceException:
								i = EmptyParentInsertSequenceException("The sequence of the parent Insert provided is empty.")
								err = i.err_code()
							
								print "Content-type:text/html"
								print
								print "Error: " + i.toString() + " Please verify your input."
								return
							
							# Dec. 14/09
							except EmptyInsertParentVectorSequenceException:
								i = EmptyInsertParentVectorSequenceException("The sequence of the Insert Parent Vector provided is empty.")
								err = i.err_code()
							
								print "Content-type:text/html"
								print
								print "Error: " + i.toString() + " Please verify your input."
								return

				elif cloning_method == '2':
					
					# Get association property IDs here
					pvAssocProp = aHandler.findAssocPropID("vector parent id")
					ipvAssocProp = aHandler.findAssocPropID("parent insert vector")
					insertAssocProp = aHandler.findAssocPropID("insert id")
					
					# get IPV
					if oldAssoc.has_key("parent insert vector"):
						oldIPVID = oldAssoc["parent insert vector"]
					else:
						oldIPVID = -1
					
					# Modified July 13/08
					isGateway = False
					
					if form.has_key("IPV"):
						ipvID = form.getvalue("IPV")			# added July 13/08
						
						if len(ipvID) > 0:
							ipv_db_id = rHandler.convertReagentToDatabaseID(ipvID)
							
							try:
								ipvProjectID = int(rHandler.findSimplePropertyValue(ipv_db_id, packetPropID))	# need to cast
								
							except TypeError:
								i = PVProjectAccessException("Invalid Insert Parent Vector project ID")
							
								print "Content-type:text/html"
								print
								print `i.err_code()`
								return

							if currUser.getCategory() != 'Admin' and pvProjectID not in uPackets:
								i = PVProjectAccessException("You do not have read access to this project")
						
								print "Content-type:text/html"
								print
								print `i.err_code()`
								return
								
							if currUser.getCategory() != 'Admin' and ipvProjectID not in uPackets:
								i = IPVProjectAccessException("You do not have read access to this project")
						
								print "Content-type:text/html"
								print
								print `i.err_code()`
								return
								
							# A recombination clone can be either a Creator Expression Vector or a Gateway Expression Clone.
							
							# get IPV sequence
							ipvSeqKey = rHandler.findDNASequenceKey(ipv_db_id)
							ipvSeq = dnaHandler.findSequenceByID(ipvSeqKey).strip().upper()
							
							# Get the Insert that belongs to the donor vector
							ipvInsertAssocID = raHandler.findReagentAssociationID(ipv_db_id)
							insertAssocPropID = aHandler.findAssocPropID("insert id")
							insertID = aHandler.findAssocPropValue(ipvInsertAssocID, insertAssocPropID)

							# comment Dec. 6/10: what if there is no insert???  Dec. 7/10, Karen: lift off this restriction
							## get Insert sequence						# removed Dec. 7/10
							#insertSeqKey = rHandler.findDNASequenceKey(insertID)		# removed Dec. 7/10
							#insertSeq = dnaHandler.findSequenceByID(insertSeqKey)		# removed Dec. 7/10
							
							# get sites from INSERT PARENT VECTOR
							# (keep names since reusing code but in fact these are IPV sites, NOT Insert)
							insertCloningSites = []
	
							# get linkers if there are any
							insertLinkers = []
						
							# Update July 2/09
							fpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
							tpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])

							#fpLinkerPropID = prop_Name_ID_Map["5' linker"]		# removed July 2/09
							#tpLinkerPropID = prop_Name_ID_Map["3' linker"]		# removed July 2/09
							
							fp_insert_linker = rHandler.findSimplePropertyValue(insertID, fpLinkerPropID)
							tp_insert_linker = rHandler.findSimplePropertyValue(insertID, tpLinkerPropID)
							
							# sept. 3/07 - needed to cast to string
							fwd_linker = ""
						
							if fp_insert_linker and len(fp_insert_linker) > 0 and fp_insert_linker != 0 and fp_insert_linker != '0':
								fp_insert_linker = fwd_linker + fp_insert_linker
							else:
								fp_insert_linker = fwd_linker
								
							# April 24/08
							if not tp_insert_linker or len(tp_insert_linker) == 0 or tp_insert_linker == 0 or tp_insert_linker == '0':
								tp_insert_linker = ""
								
							insertLinkers.append(fp_insert_linker)
							insertLinkers.append(tp_insert_linker)
							
							fp_linker_start = rHandler.findReagentFeatureStart(insertID, fpLinkerPropID)
							fp_linker_end = rHandler.findReagentFeatureEnd(insertID, fpLinkerPropID)
							
							tp_linker_start = rHandler.findReagentFeatureStart(insertID, tpLinkerPropID)
							tp_linker_end = rHandler.findReagentFeatureEnd(insertID, tpLinkerPropID)
						
							# Don't go by cloning sites, just try to construct sequences
							# Creator Expression Vector
							# Check that LoxP occurs exactly twice on IPV sequence
							loxp_seq = enzDict["LoxP"]
							
							numLoxp = utils.numOccurs(ipvSeq.lower(), loxp_seq.lower())
							
							if numLoxp == 0 or numLoxp == 1 or numLoxp > 2:
								
								# This might be a gateway expression vector
								fp_insert_cs = 'attB1'
								tp_insert_cs = 'attB2'
								
								fpSite = 'gtacaaaaaa'
								tpSite = 'tttcttgtac'
								
								try:
									newSeq = dnaHandler.expressionVectorSequence(pvSeqID, ipvSeq)
									newSeqID = int(dnaHandler.matchSequence(newSeq))
									
									if newSeqID <= 0:
										newSeqID = int(dnaHandler.insertSequence(newSeq))
									
									rHandler.deleteReagentProperty(rID, seqPropID)
									rHandler.addReagentProperty(rID, seqPropID, newSeqID)
	
									# Remap features
									newSeq = utils.squeeze(newSeq)
								
									# Delete old features first
									rHandler.deleteReagentFeatures(rID)
									
									# cDNA
									# CHANGE DEC. 6, 2010: SAME ERROR KAREN DISCOVERED AT CREATION: DO **NOT** MAP CDNA FROM INSERT!!!! IT SHOULD BE MAPPED FROM ENTRY CLONE
									#insert_cdnaStart = iHandler.findCDNAStart(insertID)
									#insert_cdnaEnd = iHandler.findCDNAEnd(insertID)
									
									# keep 'insert' in the name for consistency though
									cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
									
									insert_cdnaStart = rHandler.findReagentFeatureStart(ipv_db_id, cdnaPropID)
									insert_cdnaEnd = rHandler.findReagentFeatureEnd(ipv_db_id, cdnaPropID)
									
									#print "start " + `insert_cdnaStart` + ", print " + `insert_cdnaEnd`
	
									# NO!!!!!!! DEC. 6/10: GET CDNA FROM ENTRY VECTOR!!!!
									if insert_cdnaStart > 0 and insert_cdnaEnd > 0:
										#cdnaSeq = insertSeq[insert_cdnaStart-1:insert_cdnaEnd]
										cdnaSeq = ipvSeq[insert_cdnaStart-1:insert_cdnaEnd]
										
										# moved this code up here too
										if newSeq.lower().find(cdnaSeq.lower()) >= 0:
										
											cdnaStart = newSeq.lower().find(cdnaSeq.lower()) + 1
											cdnaEnd = cdnaStart + len(cdnaSeq) - 1
										else:
											cdnaStart = 0
											cdnaEnd = 0

									else:
										# now not sure what to do here, check with Karen tomorrow
										#cdnaSeq = insertSeq
										cdnaSeq = ""	# temporary, probably wrong
	
									# commented out on Dec. 6, 2010
									#if newSeq.lower().find(cdnaSeq.lower()) >= 0:
										#cdnaStart = newSeq.lower().find(cdnaSeq) + 1
										#cdnaEnd = cdnaStart + len(cdnaSeq) - 1
									#else:
										cdnaStart = 0
										cdnaEnd = 0

									# July 2/09
									#cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
									rHandler.setPropertyPosition(rID, cdnaPropID, "startPos", cdnaStart)
									rHandler.setPropertyPosition(rID, cdnaPropID, "endPos", cdnaEnd)
						
									fpStartPos = newSeq.lower().find(fpSite) + 1
									fpEndPos = fpStartPos + len(fpSite)
									
									tpStartPos = newSeq.lower().find(tpSite) + 1
									tpEndPos = tpStartPos + len(tpSite)
									
									rHandler.addReagentProperty(rID, fpcs_prop_id, "attB1", fpStartPos, fpEndPos-1)
									rHandler.addReagentProperty(rID, tpcs_prop_id, "attB2", tpStartPos, tpEndPos-1)
									
									ipvFeatures = rHandler.findReagentSequenceFeatures(ipv_db_id)
									pvFeatures = rHandler.findReagentSequenceFeatures(pvID)
										
									# Parent Vector features are found before cDNA start and after cDNA end on the new sequence
									for f in pvFeatures:
										fType = f.getFeatureType()
										#print fType
										
										if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
										
											# original feature positions on PV sequence
											pv_fStart = f.getFeatureStartPos()
											pv_fEnd = f.getFeatureEndPos()
										
											fVal = f.getFeatureName()
											#print fVal
											pv_fDir = f.getFeatureDirection()
											
											#print pv_fDir
											
											if pv_fStart > 0 and pv_fEnd > 0:
												
												# Features from PV are inherited IFF they are located **entirely** before the 5' cloning site or after 3' cloning site
												# Hence: May 22/08 - Find 5' start and 3' end on **original** PV sequence
												pv_fpcs_start = pvSequence.lower().find(fpSite)
												pv_tpcs_end = pvSequence.lower().find(tpSite) + len(tpSite)
												
												fSeq = pvSequence[pv_fStart-1:pv_fEnd].lower()
								
												# June 4/08 - Search for the feature IFF > 10 nts
												if len(fSeq) >= 10:
								
													# Look for this feature on the NEWly reconstituted Vector
													fIndex = newSeq.lower().find(fSeq)
								
													if fIndex >= 0:
														fpStart = fIndex + 1
														fpEnd = fpStart + len(fSeq) - 1
														
														#print "start " + `fpStart`
														#print "end " + `fpEnd`
								
														# If found, make sure this feature occurs either before or after the cDNA
														if (fpStart < fpStartPos and fpEnd < fpStartPos) or (fpStart >= tpEndPos and fpEnd > tpEndPos):
															
															# June 9/08: Orientation
															fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
										
															rHandler.addReagentProperty(rID, fID, fVal, fpStart, fpEnd, pv_fDir)
												
															if f.getFeatureDescrType():
																fDescr = f.getFeatureDescrName()
																#print fVal
																#print fDescr
																
																# Updated July 2/09
																#fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
																rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fpStart, fpEnd, fDescr)
											
									# Search for each Insert feature on the new sequence
									# June 6/08: Added 'if' statement - needs testing
									for f in ipvFeatures:
										fType = f.getFeatureType()
										#print fType
										
										if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
											# feature positions on the Insert sequence - Modified May 21/08: Account for the fact that NOT the entire Insert sequence is used, only a subsequence
											fiStart = f.getFeatureStartPos()
											fiEnd = f.getFeatureEndPos()
											fVal = f.getFeatureName()
											#print fVal
											
											#fSeq = cdnaSeq[fiStart:fiEnd].lower()	# NO!!!!
											
											fSeq = ipvSeq[fiStart:fiEnd].lower()
										
											if len(fSeq) >= 10:
												fStart = newSeq.lower().find(fSeq)
												fEnd = fStart + len(fSeq)
											
												if fStart >= 1:
								
													# May 22/08: NO!!! This is precisely what we decided we're NOT going to do!!!
													'''
													## recompute based on cDNA start
													#fiStart = fiStart - insert_cdnaStart
													#fiEnd = fiEnd - insert_cdnaStart
													
													# feature positions on the new Vector sequence
													#fStart = cdnaStart + fiStart
													#fEnd = fStart + len(fSeq)
													'''
													
													# still, double check to make sure this feature occurs between cloning sites on resulting Vector sequence
													if fStart >= fpEndPos and fEnd <= tpStartPos:
														
														# June 9/08: Orientation
														fiDir = f.getFeatureDirection()
														fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
														
														rHandler.addReagentProperty(rID, fID, fVal, fStart, fEnd, fiDir)
													
														if f.getFeatureDescrType():
															fDescr = f.getFeatureDescrType()
															
															# Updated July 2/09
															#fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
															rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fStart, fEnd, fDescr)
								
									# Now save parents - delete all previous information
									rAssocID = raHandler.findReagentAssociationID(rID)
									
									rHandler.deleteReagentAssociationProp(rID, pvAssocProp)
									rHandler.addAssociationValue(rID, pvAssocProp, pvID, rAssocID)
				
									rHandler.deleteReagentAssociationProp(rID, ipvAssocProp)
									rHandler.addAssociationValue(rID, ipvAssocProp, ipv_db_id, rAssocID)
								
									# Finally: July 8/08: Update Vector details: name, type, description, project ID, verification
									
									#print "Content-type:text/html"
									#print
									#print `form`
									
									if form.has_key("newVectorName"):
										newName = form.getvalue("newVectorName")
										rHandler.deleteReagentProperty(rID, namePropID)
										rHandler.addReagentProperty(rID, namePropID, newName)
									else:
										rHandler.deleteReagentProperty(rID, namePropID)
									
									if form.has_key("newVectorType"):
										newVectorType = form.getvalue("newVectorType")
										rHandler.deleteReagentProperty(rID, vTypePropID)
										rHandler.addReagentProperty(rID, vTypePropID, newVectorType)
									else:
										rHandler.deleteReagentProperty(rID, vTypePropID)
									
									if form.has_key("newProjectID"):
										newProjectID = form.getvalue("newProjectID")
										rHandler.changePropertyValue(rID, projectPropID, newProjectID)
									else:
										rHandler.deleteReagentProperty(rID, projectPropID)
									
									if form.has_key("newDescription"):
										newDescription = form.getvalue("newDescription")
										rHandler.deleteReagentProperty(rID, descrPropID)
										rHandler.addComment(rID, descrPropID, newDescription)
									else:
										rHandler.deleteReagentProperty(rID, descrPropID)
									
									if form.has_key("newVerification"):
										newVerification = form.getvalue("newVerification")
										rHandler.deleteReagentProperty(rID, verifPropID)
										rHandler.addReagentProperty(rID, verifPropID, newVerification)
									else:
										rHandler.deleteReagentProperty(rID, verifPropID)
									
									print "Content-type:text/html"
									print
									print "0"
									return
								
								# removed dec. 7/10: why twice??
								#except InsertSitesNotFoundOnParentSequenceException:
									#i = InsertSitesNotFoundOnParentSequenceException("")
									#print "Content-type:text/html"
									#print
									#print `i.err_code()`
									#return
									
								except MultipleSiteOccurrenceException:
									i = MultipleSiteOccurrenceException("")
									print "Content-type:text/html"
									print
									print `i.err_code()`
									return
									
								except InsertSitesNotFoundOnParentSequenceException:
									i = InsertSitesNotFoundOnParentSequenceException("")
									print "Content-type:text/html"
									print
									print `i.err_code()`
									return
									
								except MultipleSiteOccurrenceException:
									i = MultipleSiteOccurrenceException("")
									print "Content-type:text/html"
									print
									print `i.err_code()`
									return
									
								# Dec. 14/09
								except EmptyParentVectorSequenceException:
									i = EmptyParentVectorSequenceException("The sequence of the parent Vector provided is empty.")
									err = i.err_code()
								
									print "Content-type:text/html"
									print
									print "Error: " + i.toString() + " Please verify your input."
									return
								
								# Dec. 14/09
								except EmptyParentInsertSequenceException:
									i = EmptyParentInsertSequenceException("The sequence of the parent Insert provided is empty.")
									err = i.err_code()
								
									print "Content-type:text/html"
									print
									print "Error: " + i.toString() + " Please verify your input."
									return
								
								# Dec. 14/09
								except EmptyInsertParentVectorSequenceException:
									i = EmptyInsertParentVectorSequenceException("The sequence of the Insert Parent Vector provided is empty.")
									err = i.err_code()
								
									print "Content-type:text/html"
									print
									print "Error: " + i.toString() + " Please verify your input."
									return

							else:
								try:
									newSeq = dnaHandler.constructRecombSequence(pvSeqID, ipvSeqKey)
								
								except MultipleSiteOccurrenceException:
									i = MultipleSiteOccurrenceException("")
									print "Content-type:text/html"
									print
									print `i.err_code()`
									return
								
								# Dec. 14/09
								except EmptyParentVectorSequenceException:
									i = EmptyParentVectorSequenceException("The sequence of the parent Vector provided is empty.")
									err = i.err_code()
								
									print "Content-type:text/html"
									print
									print "Error: " + i.toString() + " Please verify your input."
									return
								
								# Dec. 14/09
								except EmptyParentInsertSequenceException:
									i = EmptyParentInsertSequenceException("The sequence of the parent Insert provided is empty.")
									err = i.err_code()
								
									print "Content-type:text/html"
									print
									print "Error: " + i.toString() + " Please verify your input."
									return
								
								# Dec. 14/09
								except EmptyInsertParentVectorSequenceException:
									i = EmptyInsertParentVectorSequenceException("The sequence of the Insert Parent Vector provided is empty.")
									err = i.err_code()
								
									print "Content-type:text/html"
									print
									print "Error: " + i.toString() + " Please verify your input."
									return
	
								# added Dec. 7, 2010	
								except InsertSitesNotFoundOnParentSequenceException:
									i = InsertSitesNotFoundOnParentSequenceException("")
									print "Content-type:text/html"
									print
									print `i.err_code()`
									return
									
								fpSite = loxp_seq
								tpSite = loxp_seq
								
								newSeqID = int(dnaHandler.matchSequence(newSeq))
								
								if newSeqID <= 0:
									newSeqID = int(dnaHandler.insertSequence(newSeq))
								
								rHandler.deleteReagentProperty(rID, seqPropID)
								rHandler.addReagentProperty(rID, seqPropID, newSeqID)

								# Remap features
								newSeq = utils.squeeze(newSeq)
							
								# Delete old features first
								rHandler.deleteReagentFeatures(rID)
								
								# cDNA
								# Update Dec. 7, 2010: don't map cDNA boundaries from Insert, get them from IPV
								cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])

								insert_cdnaStart = rHandler.findReagentFeatureStart(ipv_db_id, cdnaPropID)
								insert_cdnaEnd = rHandler.findReagentFeatureEnd(ipv_db_id, cdnaPropID)
							
								#insert_cdnaStart = iHandler.findCDNAStart(insertID)
								#insert_cdnaEnd = iHandler.findCDNAEnd(insertID)

								if insert_cdnaStart > 0 and insert_cdnaEnd > 0:
									# update Dec. 7/10
									#cdnaSeq = insertSeq[insert_cdnaStart-1:insert_cdnaEnd]		# removed Dec. 7/10
									cdnaSeq = ipvSeq[insert_cdnaStart-1:insert_cdnaEnd]		# replaced dec. 7/10

									# moved this code up here too
									if newSeq.lower().find(cdnaSeq.lower()) >= 0:
										cdnaStart = newSeq.lower().find(cdnaSeq.lower()) + 1
										cdnaEnd = cdnaStart + len(cdnaSeq) - 1
									else:
										cdnaStart = 0
										cdnaEnd = 0
									
								else:
									#cdnaSeq = insertSeq	# rmvd dec 7/10
									cdnaSeq = ""		# replaced dec 7/10
									
								# commented out on Dec. 7, 2010
								#if newSeq.lower().find(cdnaSeq.lower()) >= 0:
									#cdnaStart = newSeq.lower().find(cdnaSeq) + 1
									#cdnaEnd = cdnaStart + len(cdnaSeq) - 1
								#else:
									cdnaStart = 0
									cdnaEnd = 0
								
								# July 2/09
								rHandler.setPropertyPosition(rID, cdnaPropID, "startPos", cdnaStart)
								rHandler.setPropertyPosition(rID, cdnaPropID, "endPos", cdnaEnd)
					
								fpStartPos = newSeq.lower().find(fpSite.lower()) + 1
								fpEndPos = fpStartPos + len(fpSite.lower())
								
								tpStartPos = newSeq.lower().rfind(tpSite.lower()) + 1
								tpEndPos = tpStartPos + len(tpSite.lower())
								
								rHandler.addReagentProperty(rID, fpcs_prop_id, "LoxP", fpStartPos, fpEndPos-1)
								
								#rHandler.deleteReagentProperty(rID, tpcs_prop_id)
								rHandler.addReagentProperty(rID, tpcs_prop_id, "LoxP", tpStartPos, tpEndPos-1)
								
								# April 17/08: Map the rest of the features
								ipvFeatures = rHandler.findReagentSequenceFeatures(ipv_db_id)
								pvFeatures = rHandler.findReagentSequenceFeatures(pvID)
								
								# changes made Oct. 14/08
								tmp_dict = {}
								
								# Parent Vector features are found before cDNA start and after cDNA end on the new sequence
								for f in pvFeatures:
									fType = f.getFeatureType()
									
									if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
									
										# original feature positions on PV sequence
										pv_fStart = f.getFeatureStartPos()
										pv_fEnd = f.getFeatureEndPos()
										
										fSeq = pvSequence[pv_fStart-1:pv_fEnd].lower()
										tmp_dict[fSeq] = f
									
								for fSeq in tmp_dict.keys():
									f_tmp = tmp_dict[fSeq]
									
									if len(fSeq) >= 10:
										#print f_tmp.getFeatureName()
						
										fList = utils.findall(newSeq.lower(), fSeq, [])
										#print `fList`
										
										for fIndex in fList:
											fpStart = fIndex + 1
											fpEnd = fpStart + len(fSeq) - 1
											
											if fpStart > 0 and fpEnd > 0:
												
												fType = f_tmp.getFeatureType()
												fVal =  f_tmp.getFeatureName()
												#print fVal
												pv_fDir = f_tmp.getFeatureDirection()
										
												# Nov. 4/08: In recombination vectors there's only one LoxP occurrence on the parent and it should be transferred onto the child as a restriction site; rest of features are all inherited.  So remove the site position check; don't replace it with check for a single LoxP site occurrence yet, see how it goes.
												
												# removed Nov. 6/08
												##If found, make sure this feature occurs either before or after the CLONING SITES!!!!!!!!
												#if (fpStart < fpStartPos and fpEnd < fpStartPos) or (fpStart >= tpEndPos and fpEnd > tpEndPos):
													#print fType + ": " + fVal + ": " + `fpStart` + "-" + `fpEnd`
										
												fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
												
												rHandler.addReagentProperty(rID, fID, fVal, fpStart, fpEnd, pv_fDir)
										
												if f_tmp.getFeatureDescrType():
													fDescr = f_tmp.getFeatureDescrName()
													#print fDescr
										
													# Updated July 2/09
													rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fpStart, fpEnd, fDescr)
						
								# IPV features
								tmp_ipv_dict = {}
									
								for f in ipvFeatures:
									fType = f.getFeatureType()
									#print fType
									
									if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
										# feature positions on the Insert sequence - Modified May 21/08: Account for the fact that NOT the entire Insert sequence is used, only a subsequence
										fiStart = f.getFeatureStartPos()
										fiEnd = f.getFeatureEndPos()
										#fVal = f.getFeatureName()
										#print fVal
										fSeq = ipvSeq[fiStart:fiEnd].lower()
										tmp_ipv_dict[fSeq] = f
									
								for fSeq in tmp_ipv_dict.keys():
									f_tmp = tmp_ipv_dict[fSeq]
									#print f_tmp.getFeatureName()
						
									if len(fSeq) >= 10:
										fList = utils.findall(newSeq.lower(), fSeq, [])
										#print `fList`
						
										for fIndex in fList:
											fStart = newSeq.lower().find(fSeq)
											#print "start " + `fStart`
											fEnd = fStart + len(fSeq)
											#print "end  " + `fEnd`
											
											# still, double check to make sure this feature occurs between Insert cDNA start and end on resulting Vector sequence
											#print "cdna start " + `cdnaStart`
											#print "cdna end " + `cdnaEnd`
						
											# features must be between the cloning sites - this check remains!!!
											if fStart > fpEndPos and fEnd < tpEndPos:
												fType = f_tmp.getFeatureType()
												fVal =  f_tmp.getFeatureName()
												fiDir = f_tmp.getFeatureDirection()
												#print fType + ": " + fVal + ": " + `fStart` + "-" + `fEnd`
										
												fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
											
												rHandler.addReagentProperty(rID, fID, fVal, fStart, fEnd, fiDir)
											
												if f.getFeatureDescrType():
													fDescr = f_tmp.getFeatureDescrType()
													#print fDescr
										
													# Updated Sept. 2/08
										
													# Updated July 2/09

													# removed July 2/09 - why twice??
													#rHandler.setReagentFeatureDescriptor(rID, prop_Name_ID_Map[fType], fVal, fDescr)
										
													rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fStart, fEnd, fDescr)
									
								# Now save parents - delete all previous information
								rAssocID = raHandler.findReagentAssociationID(rID)
								
								rHandler.deleteReagentAssociationProp(rID, pvAssocProp)
								rHandler.addAssociationValue(rID, pvAssocProp, pvID, rAssocID)
			
								rHandler.deleteReagentAssociationProp(rID, ipvAssocProp)
								rHandler.addAssociationValue(rID, ipvAssocProp, ipv_db_id, rAssocID)
							
								# Finally: July 8/08: Update Vector details: name, type, description, project ID, verification
								
								#print "Content-type:text/html"
								#print
								#print `form`
									
								if form.has_key("newVectorName"):
									newName = form.getvalue("newVectorName")
									rHandler.deleteReagentProperty(rID, namePropID)
									rHandler.addReagentProperty(rID, namePropID, newName)
								else:
									rHandler.deleteReagentProperty(rID, namePropID)
								
								if form.has_key("newVectorType"):
									newVectorType = form.getvalue("newVectorType")
									rHandler.deleteReagentProperty(rID, vTypePropID)
									rHandler.addReagentProperty(rID, vTypePropID, newVectorType)
								else:
									rHandler.deleteReagentProperty(rID, vTypePropID)
								
								if form.has_key("newProjectID"):
									newProjectID = form.getvalue("newProjectID")
									rHandler.changePropertyValue(rID, projectPropID, newProjectID)
								else:
									rHandler.deleteReagentProperty(rID, projectPropID)
								
								if form.has_key("newDescription"):
									newDescription = form.getvalue("newDescription")
									rHandler.deleteReagentProperty(rID, descrPropID)
									rHandler.addComment(rID, descrPropID, newDescription)
								else:
									rHandler.deleteReagentProperty(rID, descrPropID)
								
								if form.has_key("newVerification"):
									newVerification = form.getvalue("newVerification")
									rHandler.deleteReagentProperty(rID, verifPropID)
									rHandler.addReagentProperty(rID, verifPropID, newVerification)
								else:
									rHandler.deleteReagentProperty(rID, verifPropID)
								
								print "Content-type:text/html"
								print
								print "0"
								return
						else:
							ipvSeqID = -1
					else:
						ipv_db_id = -1
			
			else:
				rHandler.updateReagentAssociations(rID, assocPropsDict)
				
				# redirect to detailed view of the new reagent
				utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)

		# Aug. 13/09
		elif section_to_save == prop_Category_Name_Alias_Map["Protein Sequence"]:
			
			#print "Content-type:text/html"
			#print
			#print `form`

			# moved here April 10/08
			newPropsDict_name = {}		# e.g. ('status', 'Completed')
			newPropsDict_id = {}		# e.g. ('3', 'Completed') - db ID instead of property name
			
			protSeqCategoryID = prop_Category_Name_ID_Map["Protein Sequence"]

			# CONVERT TO UPPERCASE!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			newSeq = form.getvalue(prefix + prop_Name_Alias_Map["protein sequence"] + postfix).upper()
			#print newSeq
			
			# July 2/09: propertyID <= propCatID
			seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein sequence"], prop_Category_Name_ID_Map["Protein Sequence"])
			
			#print seqPropID 

			newSeq = protHandler.squeeze(newSeq)
			#print newSeq

			newPropsDict_id[seqPropID] = newSeq
			
			# April 7/08: Recompute feature positions (do before calling 'update' to fetch previous sequence value)
			# Grab the old sequence
			oldSeqID = rHandler.findProteinSequenceKey(rID)
			oldSeq = protHandler.findSequenceByID(oldSeqID)
			#print oldSeqID
			
			# Update the rest of the features
			if len(oldSeq) > 0:
				rHandler.updateFeaturePositions(rID, oldSeq.lower(), newSeq.lower(), False, True)
				
				# No linkers for Protein
							
			# April 12, 2011: Get Tm from input
			tmPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["melting temperature"], prop_Category_Name_ID_Map["Protein Sequence"])

			if form.has_key(prefix + prop_Name_Alias_Map["melting temperature"] + postfix):
				tm_prot = form.getvalue(prefix + prop_Name_Alias_Map["melting temperature"] + postfix)
				#print tm_prot
				newPropsDict_id[tmPropID] = tm_prot

			# MW is autocomputed for protein - May 9, 2011: done in reagent_handler.py, removed code here
			
			# No sites for Protein either

			# rest of Protein sequence properties
			
			for sKey in form.keys():
				t1 = sKey[0:sKey.find(postfix)]
				t2 = t1[len(prefix):]
				
				propAlias = t2
				propVal = form.getlist(sKey)

				if prop_Alias_ID_Map.has_key(propAlias) and propAlias != prop_Name_Alias_Map["protein sequence"]:
					
					propCatID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[propAlias], prop_Category_Name_ID_Map["Protein Sequence"])

					rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propCatID)

					if len(propVal) > 1:
						for pVal in propVal:
							#print pVal

							setGroupID = sHandler.findPropSetGroupID(propCatID)
							ssetID = sHandler.findSetValueID(setGroupID, pVal)

							if sHandler.findSetValueID(setGroupID, pVal) <= 0:
								ssetID = sHandler.addSetValue(setGroupID, pVal)
							
							if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
								#print "Adding " + pVal
								sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
					else:
						pVal = propVal[0]
						
						if pVal.lower() == 'other':	
							textBoxName = prefix + reagentType_ID_Name_Map[int(rTypeID)] + "_" + prop_Category_Name_Alias_Map["Protein Sequence"] + "_:_" + propAlias + "_name_txt"
							
							if form.has_key(textBoxName):
								propVal = form.getvalue(textBoxName)

								setGroupID = sHandler.findPropSetGroupID(propCatID)
								ssetID = sHandler.findSetValueID(setGroupID, propVal)

								# update set
								if sHandler.findSetValueID(setGroupID, propVal) <= 0:
									ssetID = sHandler.addSetValue(setGroupID, propVal)
								
								if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
									sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)

					newPropsDict_id[propCatID] = propVal

			#print `newPropsDict_id`

			rHandler.updateReagentPropertiesInCategory(rID, protSeqCategoryID, newPropsDict_id)

			# return to detailed view - Moved here July 7/08
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)
		
		# Aug. 18/09
		elif section_to_save == prop_Category_Name_Alias_Map["Protein Sequence Features"]:
			
			#print "Content-type:text/html"
			#print
			#print `form`
			
			# Save features - taken from 'preload.py'
			# (deletion is performed in updateFeatures function, no need to call here)
			newPropsDict_name = {}			# e.g. ('status', 'Completed')
			newPropsDict_id = {}			# e.g. ('3', 'Completed') - db ID instead of property name
			
			startPosDict = {}			# (propID, startpos)
			endPosDict = {}				# (propID, endpos)
			
			# Store orientation
			orientationDict = {}			# (propID, orientation)
			
			# features too
			rHandler.deleteReagentFeatures(rID, True)	# redo for protein
			
			# March 12/08: Treat properties with multiple values and start/end positions (a.k.a. features) as objects
			seqFeatures = []
	
			featureCategoryID = prop_Category_Name_ID_Map["Protein Sequence Features"]
			sequenceFeatures = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, featureCategoryID)
			featureDescriptors = Reagent.getFeatureDescriptors()
			
			# Nov. 13/08: Get Insert sequence
			#seqPropID = prop_Name_ID_Map["sequence"]
			seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein sequence"], prop_Category_Name_ID_Map["Protein Sequence"])
			seqID = rHandler.findIndexPropertyValue(rID, seqPropID)
			insertSeq = dnaHandler.findSequenceByID(seqID).lower()
			
			# Removed May 16/08 - not a very good idea to delete all features, including sites and linkers - linkers may hinder modification but are necessary for correct feature remapping during Vector sequence reconstitution.  See what happens
			#singleValueFeatures = reagent.getSingleFeatures()	# March 18/08 - Differentiate between features such as promoter or tag type, which could have multiple values, and cloning sites and linkers, which only have one value and one position
			
			#featureNames = sequenceFeatures + singleValueFeatures
			
			featureNames = sequenceFeatures
			#print `featureNames`
			
			featureAliases = {}
			features = {}
		
			for f in featureNames:
				fAlias = prop_Name_Alias_Map[f]
				#fPropID = prop_Name_ID_Map[f]
				fPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[f], prop_Category_Name_ID_Map["Protein Sequence Features"])
				
				featureAliases[fAlias] = f
				features[fPropID] = f
			
			#print `featureAliases`
			#print `prop_Alias_Name_Map`
		
			for fAlias in featureAliases:
				#print "alias " + fAlias
			
				tmpStart = -1
				tmpEnd = -1
				
				#tmpPropName = prefix + fAlias + postfix
				
				featureType = "Protein Sequence Features"
				
				for tmpPropName in form.keys():
					#print tmpPropName
					#print fAlias
					
					fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map["Protein Sequence Features"])
					
					if tmpPropName.find(prefix + fAlias + "_:_") >= 0:
						pValStartInd = len(prefix + fAlias)+3
						#print pValStartInd
						#pValStopInd = tmpPropName.find("_", pValStartInd)	# rmvd nov 1/09
						pValStopInd = tmpPropName.rfind("_start_", pValStartInd)
						#print pValStopInd
						
						# actual feature value
						tmpPropValue = tmpPropName[pValStartInd:pValStopInd]
						#print " feature " + tmpPropName
						#print ", value " + tmpPropValue
						
						if tmpPropValue and len(tmpPropValue) > 0:
							# get positions - changed oct. 25/08
							
							# Update Nov 3/09
							#start_ind1 = tmpPropName.find(tmpPropValue) + len(tmpPropValue) + len("_start_")
							
							start_ind1 = tmpPropName.rfind("_start_") + len("_start_")
							start_ind2 = tmpPropName.find("_end_")
							#print start_ind2
	
							tmpStart = tmpPropName[start_ind1:start_ind2]
							#print "start " + tmpStart
							
							if tmpStart and len(tmpStart) > 0 and int(tmpStart) > 0:
								end_ind_1 = start_ind2 + len("_end_")
								#print end_ind_1
								end_ind_2 = tmpPropName.find("_", end_ind_1)
								#print end_ind_2
		
								tmpEnd = tmpPropName[end_ind_1:end_ind_2]
								#print "end " + tmpEnd
								
								if tmpEnd and len(tmpEnd) > 0 and int(tmpEnd) > 0:
									tmpDirName = prefix + fAlias + "_:_" + tmpPropValue + "_start_" + `int(tmpStart)` + "_end_" + `int(tmpEnd)` + "_orientation" + postfix
									#print tmpDirName
									
									if form.has_key(tmpDirName):
										tmpDir = form.getvalue(tmpDirName)
										#print "FOUND DIRECTION " + tmpDirName
							
										# Nov. 13/08: If there are duplicates, select one
										if utils.isList(tmpDir):
											#print "Descriptor " + tmpDescr
											tmpDir = tmpDir[0]
							
										#fID = prop_Alias_ID_Map[fAlias]
										fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map[featureType])
										
										# June 8, 2010: Update set
										rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, fID)
									
										#print fAlias
										#print rTypeAttributeID
										
										# update Nov. 18/09
										setGroupID = sHandler.findPropSetGroupID(fID)	# it must exist
									
										#sHandler.updateSet(rTypeAttributeID, rType + " " + propName, newSetEntry)
										ssetID = sHandler.findSetValueID(setGroupID, tmpPropValue)
										
										if ssetID <= 0:
											ssetID = sHandler.addSetValue(setGroupID, tmpPropValue)
									
										#print ssetID
										#print rTypeAttributeID
										
										if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
											sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
										
										# descriptor
										if featureDescriptors.has_key(prop_Alias_Name_Map[fAlias]):
											tmpDescrName = featureDescriptors[prop_Alias_Name_Map[fAlias]]
											tmpDescrAlias = prop_Name_Alias_Map[tmpDescrName]
											#print "DESCRIPTOR " + tmpDescrAlias
									
											tmpDescrID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[tmpDescrName], prop_Category_Name_ID_Map["Protein Sequence Features"])
										
											tmpDescrField = prefix + tmpDescrAlias + "_:_" + tmpPropValue + "_start_" + tmpStart + "_end_" + tmpEnd + postfix

											#print tmpDescrField
											
											if form.has_key(tmpDescrField):
												#print "?????????"
									
												tmpDescr = form.getvalue(tmpDescrField)
									
												# Nov. 13/08: If there are duplicates, select one
												if utils.isList(tmpDescr):
													#print "Descriptor " + tmpDescr
													tmpDescr = tmpDescr[0]
										
												# June 8, 2010: For descriptor have to go to textbox to fetch Other values
												if tmpDescr.lower() == 'other':
													
													if fAlias == "tag":
														descrAlias = "tag_position"
													elif fAlias == "promoter":
														descrAlias = "expression_system"
										
													tmpDescr_text = prefix + tmpDescrAlias + "_:_" + tmpPropValue + "_start_" + tmpStart + "_end_" + tmpEnd + "_name_txt"
										
													#print tmpDescr_text
										
													tmpDescr = form.getvalue(tmpDescr_text)
										
													#print "SAVING OTHER " + `tmpDescr`
										
												if utils.isList(tmpDescr):
													tmpDescr = tmpDescr[0]
													#print tmpDescr
									
												newSetEntry = tmpDescr
										
												#print newSetEntry
		
												# invoke handler to add textbox value to dropdown list
												rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, tmpDescrID)
										
												#print tmpDescrID
												#print rTypeAttributeID
												
												# update Nov. 18/09
												setGroupID = sHandler.findPropSetGroupID(tmpDescrID)
										
												#print setGroupID
												
												ssetID = sHandler.findSetValueID(setGroupID, newSetEntry)
												
												if ssetID <= 0:
													ssetID = sHandler.addSetValue(setGroupID, newSetEntry)
										
												#print ssetID
												
												if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
													sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
											else:
												tmpDescr = ""
										else:
											tmpDescr = ""
									
										if not rHandler.existsPropertyValue(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDescr, tmpDir):
											rHandler.addReagentProperty(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDir)
											
											rHandler.setReagentFeatureDescriptor(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDescr)
									else:
										pass
								else:
									pass
							else:
								pass
						else:
							pass
					else:
						pass
		
			# Match db IDs to property aliases
			for tmpProp in newPropsDict_name.keys():
				if prop_Alias_ID_Map.has_key(tmpProp):
					# July 2/09: propertyID <= propCatID
					#propID = prop_Alias_ID_Map[tmpProp]	# removed July 2/09
					propID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[tmpProp], prop_Category_Name_ID_Map["Protein Sequence Features"])
					newPropsDict_id[propID] = newPropsDict_name[tmpProp]
			
			seqID = rHandler.findDNASequenceKey(rID)
			seq = dnaHandler.findSequenceByID(seqID)
			
			# June 16/08: Update protein translation
			if rTypeID == '2':
				iHandler.updateInsertProteinSequence(rID)
			
			# return to detailed view - Moved here July 7/08
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)
			
		elif section_to_save == prop_Category_Name_Alias_Map["RNA Sequence Features"]:
			
			#print "Content-type:text/html"
			#print
			#print `form`

			# Save features - taken from 'preload.py'
			# (deletion is performed in updateFeatures function, no need to call here)
			newPropsDict_name = {}			# e.g. ('status', 'Completed')
			newPropsDict_id = {}			# e.g. ('3', 'Completed') - db ID instead of property name
			
			startPosDict = {}			# (propID, startpos)
			endPosDict = {}				# (propID, endpos)
			
			# Store orientation
			orientationDict = {}			# (propID, orientation)
			
			# features too
			rHandler.deleteReagentFeatures(rID, False, True)
			
			# March 12/08: Treat properties with multiple values and start/end positions (a.k.a. features) as objects
			seqFeatures = []
	
			featureCategoryID = prop_Category_Name_ID_Map["RNA Sequence Features"]
			sequenceFeatures = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, featureCategoryID)
			featureDescriptors = Reagent.getFeatureDescriptors()
			
			#print `featureDescriptors`
			
			# Nov. 13/08: Get Insert sequence
			#seqPropID = prop_Name_ID_Map["sequence"]
			seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["rna sequence"], prop_Category_Name_ID_Map["RNA Sequence"])
			seqID = rHandler.findIndexPropertyValue(rID, seqPropID)
			insertSeq = dnaHandler.findSequenceByID(seqID).lower()
			
			featureType = "rna_sequence_features"
			
			# Removed May 16/08 - not a very good idea to delete all features, including sites and linkers - linkers may hinder modification but are necessary for correct feature remapping during Vector sequence reconstitution.  See what happens
			#singleValueFeatures = reagent.getSingleFeatures()	# March 18/08 - Differentiate between features such as promoter or tag type, which could have multiple values, and cloning sites and linkers, which only have one value and one position
			
			#featureNames = sequenceFeatures + singleValueFeatures
			
			featureNames = sequenceFeatures
			#print `featureNames`
			
			featureAliases = {}
			features = {}
		
			for f in featureNames:
				fAlias = prop_Name_Alias_Map[f]
				#fPropID = prop_Name_ID_Map[f]
				fPropID = fPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[f], prop_Category_Name_ID_Map["RNA Sequence Features"])
				
				featureAliases[fAlias] = f
				features[fPropID] = f
			
			for fAlias in featureAliases:
				#print "alias " + fAlias
			
				tmpStart = -1
				tmpEnd = -1
				
				#tmpPropName = prefix + fAlias + postfix
				
				for tmpPropName in form.keys():
					#print tmpPropName
					#print fAlias
					
					# UPDATE Nov. 4/09
					if tmpPropName.find(prefix + fAlias + "_:_") >= 0:
						pValStartInd = len(prefix + fAlias)+3
						#print pValStartInd
						#pValStopInd = tmpPropName.find("_", pValStartInd)	# rmvd nov 1/09
						pValStopInd = tmpPropName.rfind("_start_", pValStartInd)
						#print pValStopInd
						
						# actual feature value
						tmpPropValue = tmpPropName[pValStartInd:pValStopInd]
						#print " feature " + tmpPropName
						#print ", value " + tmpPropValue
						
						if tmpPropValue and len(tmpPropValue) > 0:
							# get positions - changed oct. 25/08
							
							# Update Nov 3/09
							#start_ind1 = tmpPropName.find(tmpPropValue) + len(tmpPropValue) + len("_start_")
							
							start_ind1 = tmpPropName.rfind("_start_") + len("_start_")
							start_ind2 = tmpPropName.find("_end_")
							#print start_ind2
	
							tmpStart = tmpPropName[start_ind1:start_ind2]
							#print "start " + tmpStart
							
							if tmpStart and len(tmpStart) > 0 and int(tmpStart) > 0:
								end_ind_1 = start_ind2 + len("_end_")
								#print end_ind_1
								end_ind_2 = tmpPropName.find("_", end_ind_1)
								#print end_ind_2
		
								tmpEnd = tmpPropName[end_ind_1:end_ind_2]
								#print "end " + tmpEnd
								
								if tmpEnd and len(tmpEnd) > 0 and int(tmpEnd) > 0:
									tmpDirName = prefix + fAlias + "_:_" + tmpPropValue + "_start_" + `int(tmpStart)` + "_end_" + `int(tmpEnd)` + "_orientation" + postfix
									#print tmpDirName
									
									if form.has_key(tmpDirName):
										tmpDir = form.getvalue(tmpDirName)
										#print "FOUND DIRECTION " + tmpDirName
							
										# Nov. 13/08: If there are duplicates, select one
										if utils.isList(tmpDir):
											#print "Descriptor " + tmpDescr
											tmpDir = tmpDir[0]
							
										#fID = prop_Alias_ID_Map[fAlias]
										fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
										
										# descriptor
										if featureDescriptors.has_key(prop_Alias_Name_Map[fAlias]):
											tmpDescrName = featureDescriptors[prop_Alias_Name_Map[fAlias]]
											tmpDescrAlias = prop_Name_Alias_Map[tmpDescrName]
											#print "DESCRIPTOR " + tmpDescrAlias
									
											tmpDescrField = prefix + tmpDescrAlias + "_:_" + tmpPropValue + "_start_" + tmpStart + "_end_" + tmpEnd + postfix

											#print tmpDescrField
											
											if form.has_key(tmpDescrField):
												#print "?????????"
									
												tmpDescr = form.getvalue(tmpDescrField)
												#print tmpDescr
										
												# Nov. 13/08: If there are duplicates, select one
												if utils.isList(tmpDescr):
													#print "Descriptor " + tmpDescr
													tmpDescr = tmpDescr[0]
										
												# June 8, 2010: For descriptor have to go to textbox to fetch Other values
												if tmpDescr.lower() == 'other':
													
													if fAlias == "tag":
														descrAlias = "tag_position"
													elif fAlias == "promoter":
														descrAlias = "expression_system"
										
													tmpDescr_text = prefix + tmpDescrAlias + "_:_" + tmpPropValue + "_start_" + tmpStart + "_end_" + tmpEnd + "_name_txt"
										
													#print tmpDescr_text
										
													tmpDescr = form.getvalue(tmpDescr_text)
										
												#print tmpDescr
									
												if utils.isList(tmpDescr):
													tmpDescr = tmpDescr[0]
										
												# March 29, 2010: Update set
												tmpDescrPropID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[tmpDescrAlias], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
									
												rTypeDescrAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, tmpDescrPropID)
												
												descrSetGroupID = sHandler.findPropSetGroupID(tmpDescrPropID)
												descr_ssetID = sHandler.findSetValueID(descrSetGroupID, tmpDescr)
												
												if sHandler.findSetValueID(descrSetGroupID, tmpDescr) <= 0:
													descr_ssetID = sHandler.addSetValue(descrSetGroupID, tmpDescr)

												#print descr_ssetID

												if not sHandler.existsReagentTypeAttributeSetValue(rTypeDescrAttributeID, descr_ssetID):
													#print "HERE, updating"
													sHandler.addReagentTypeAttributeSetEntry(rTypeDescrAttributeID, descr_ssetID)

											else:
												tmpDescr = ""
										else:
											tmpDescr = ""
									
										if not rHandler.existsPropertyValue(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDescr, tmpDir):
											rHandler.addReagentProperty(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDir)
											
											rHandler.setReagentFeatureDescriptor(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDescr)
										
										# March 29, 2010: Update set
										rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, fID)
										
										setGroupID = sHandler.findPropSetGroupID(fID)
										ssetID = sHandler.findSetValueID(setGroupID, tmpPropValue)
										
										if sHandler.findSetValueID(setGroupID, tmpPropValue) <= 0:
											ssetID = sHandler.addSetValue(setGroupID, tmpPropValue)
										
										if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
											sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
									else:
										pass
								else:
									pass
							else:
								pass
						else:
							pass
					else:
						pass
						
			# Match db IDs to property aliases
			for tmpProp in newPropsDict_name.keys():
				if prop_Alias_ID_Map.has_key(tmpProp):
					# July 2/09: propertyID <= propCatID
					#propID = prop_Alias_ID_Map[tmpProp]	# removed July 2/09
					propID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[tmpProp], prop_Category_Name_ID_Map["RNA Sequence Features"])
					newPropsDict_id[propID] = newPropsDict_name[tmpProp]

			# June 8/08: Update sites
			seqID = rHandler.findDNASequenceKey(rID)
			seq = dnaHandler.findSequenceByID(seqID)
			
			#print `five_prime_site`
			#rHandler.updateSitePositions(rID, seq, five_prime_site, three_prime_site)
			
			# June 16/08: Update protein translation
			if rTypeID == '2':
				iHandler.updateInsertProteinSequence(rID)
			
			# return to detailed view - Moved here July 7/08
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)
		
		# This is saving properties from Cell Line old form that preloads from parents
		elif section_to_save == "associations":
			
			#print "Content-type:text/html"
			#print
			#print `form`
			
			assocPropsDict = {}
			newPropsDict_name = {}			# e.g. ('status', 'Completed')
			newPropsDict_id = {}			# e.g. ('3', 'Completed') - db ID instead of property name

			assocTypes = reagent.getAssociationTypes()
			#print `assocTypes`
			
			assocAliases = {}
			associations = {}
			
			for aType in assocTypes:
				#print aType
				assocAlias = assoc_Name_Alias_Map[aType]
				#print assocAlias
				assocID = assoc_Name_ID_Map[aType]
				
				assocAliases[assocAlias] = aType
				associations[assocID] = aType
				
			for assocAlias in assocAliases:
				tmpPropName = assocPrefix + assocAlias + postfix
				#print tmpPropName

				if form.has_key(tmpPropName):
					tmpPropVal = form.getvalue(tmpPropName)
					assocPropsDict[assocAlias] = tmpPropVal
			
			#print `assocPropsDict`
			packetPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["packet id"], prop_Category_Name_ID_Map["General Properties"])

			
			# Fetch projects the user has AT LEAST Read access to (i.e. if he is explicitly declared a Writer on a project but not declared a Reader, include that project, plus all public projects)
			currReadProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Reader')
			currWriteProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Writer')
			publicProj = packetHandler.findAllProjects(isPrivate="FALSE")
			
			# list of Packet OBJECTS
			currUserWriteProjects = utils.unique(currReadProj + currWriteProj + publicProj)
			
			uPackets = []
			
			for p in currUserWriteProjects:
				uPackets.append(p.getNumber())
		
			# Cell Line.  Check Parent Vector, and parent cell line
			pvVal = form.getvalue("assoc_cell_line_parent_vector_prop")
			#print pvVal
	
			if len(pvVal) > 0:
				pvID = rHandler.convertReagentToDatabaseID(pvVal)
				#print pvID
				#print packetPropID

				try:
					pvProjectID = int(rHandler.findSimplePropertyValue(pvID, packetPropID))
					
				except TypeError:
					pvProjectID = 0
			else:
				pvID = 0
				pvProjectID = 0

			if pvID > 0:
				if rHandler.findSimplePropertyValue(pvID, packetPropID):
					pvProjectID = int(rHandler.findSimplePropertyValue(pvID, packetPropID))
					
					if currUser.getCategory() != 'Admin' and pvProjectID not in uPackets:
						utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID` + "&mode=Modify&ErrCode=4&PV=" + pvVal)
				else:
					# Project value not recorded for the parent vector - catch this error
					utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID` + "&mode=Modify&ErrCode=4&PV=" + pvVal)
				
			# cell line
			pclVal = form.getvalue("assoc_parent_cell_line_prop")
			#print pclVal
			if len(pclVal) > 0:
				pclID = rHandler.convertReagentToDatabaseID(pclVal)
				#print pclID
			else:
				pclID = 0
			
			if pclID > 0:
				if rHandler.findSimplePropertyValue(pclID, packetPropID):
					pclProjectID = int(rHandler.findSimplePropertyValue(pclID, packetPropID))

					if currUser.getCategory() != 'Admin' and pclProjectID not in uPackets:
						utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID` + "&mode=Modify&ErrCode=4&CL=" + pclVal)
				else:
					# Project value not recorded for the parent cell line - catch this error
					utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID` + "&mode=Modify&ErrCode=4&CL=" + pclVal)
			
			# Feb. 16/10
			assocPropsDict = {}
			
			clpvAssocPropID = rtAssocHandler.findParentAssocType(reagentType_Name_ID_Map["CellLine"], reagentType_Name_ID_Map["Vector"])
			#print `clpvAssocPropID`
			
			pclAssocPropID = rtAssocHandler.findParentAssocType(reagentType_Name_ID_Map["CellLine"], reagentType_Name_ID_Map["CellLine"])
			#print `pclAssocPropID`
			
			assocPropsDict[clpvAssocPropID] = pvVal
			assocPropsDict[pclAssocPropID] = pclVal
			
			# save rest of properties
			prefix = "reagent_detailedview_"
			postfix = "_prop"
			
			# For Cell Lines, update properties preloaded from parents
			
			# Find the actual names and database IDs of POST values
			newPropsDict_name = {}		# e.g. ('status', 'Completed')
        		newPropsDict_id = {}		# e.g. ('3', 'Completed') - db ID instead of property name
			
			# Find actual property values
			for propName_tmp in form.keys():
				
				# new value that would be added to the set (dropdown/checkbox)
				newSetEntry = ""
				
				#print propName_tmp
			
				if propName_tmp.find("_:_") > 0:
					pToks = propName_tmp.split("_:_")
					#print `pToks`
					
					if pToks[0].find(prefix + reagentType_ID_Name_Map[int(rTypeID)] + "_") == 0:
						catAlias = pToks[0][len(prefix + reagentType_ID_Name_Map[int(rTypeID)] + "_"):]
						categoryID = prop_Category_Alias_ID_Map[catAlias]
					else:
						continue
						
					if pToks[1].find(postfix) + len(postfix) == len(pToks[1]):
						propAlias = pToks[1][0:pToks[1].find(postfix)]
						propName = prop_Alias_Name_Map[propAlias]
						propID = prop_Alias_ID_Map[propAlias]
					else:
						continue
					
					if utils.isList(form[propName_tmp]):
						newPropVal = utils.unique(form.getlist(propName_tmp))
					else:
						newPropVal = form[propName_tmp].value.strip()
					
					#print propName
					#print newPropVal

					propCatID = pHandler.findReagentPropertyInCategoryID(propID, categoryID)
					rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propCatID)
					
					# Alternate ID - Combine the checkbox value representing external source name with the identifier for that source in a textbox
					if propName.lower() == "alternate id":
						#print `newPropVal`
						
						if utils.isList(newPropVal):
							tmp_alt_ids = []
						
							for altID in newPropVal:
								if form.has_key(propAlias + "_" + altID + "_textbox_name"):
									if altID.lower() == 'other':
										tmp_alt_id = form.getvalue(propAlias + "_" + altID + "_textbox_name")
										
										# update list
										pcID = pHandler.findReagentPropertyInCategoryID(propID, categoryID)
								
										rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, pcID)
										
										setGroupID = sHandler.findPropSetGroupID(pcID)
										ssetID = sHandler.findSetValueID(setGroupID, tmp_alt_id[0:tmp_alt_id.find(":")])
										
										if ssetID <= 0:
											ssetID = sHandler.addSetValue(setGroupID, tmp_alt_id[0:tmp_alt_id.find(":")])
										
										if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
											sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
									else:
										tmp_alt_id = altID + ":" + form.getvalue(propAlias + "_" + altID + "_textbox_name")
										#print tmp_alt_id
									
									tmp_alt_ids.append(tmp_alt_id)
							
							newPropVal = tmp_alt_ids
						else:
							if form.has_key(propAlias + "_" + newPropVal + "_textbox_name"):
								if newPropVal.lower() == 'other':
									tmp_alt_id = form.getvalue(propAlias + "_" + newPropVal + "_textbox_name")
									
									# update list
									pcID = pHandler.findReagentPropertyInCategoryID(propID, categoryID)
							
									rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, pcID)
									
									setGroupID = sHandler.findPropSetGroupID(pcID)
									ssetID = sHandler.findSetValueID(setGroupID, tmp_alt_id[0:tmp_alt_id.find(":")])
									
									if ssetID <= 0:
										ssetID = sHandler.addSetValue(setGroupID, tmp_alt_id[0:tmp_alt_id.find(":")])
									
									if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
										sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
								else:
									tmp_alt_id = newPropVal + ":" + form.getvalue(propAlias + "_" + newPropVal + "_textbox_name")
									#print tmp_alt_id
							
							newPropVal = tmp_alt_id
					else:	
						if utils.isList(newPropVal):
							if len(newPropVal) > 1:
								for pVal in newPropVal:
									#print pVal

									setGroupID = sHandler.findPropSetGroupID(propCatID)
									ssetID = sHandler.findSetValueID(setGroupID, pVal)

									if sHandler.findSetValueID(setGroupID, pVal) <= 0:
										ssetID = sHandler.addSetValue(setGroupID, pVal)
									
									if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
										sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
							else:
								newPropVal = newPropVal[0]
						else:
							if newPropVal.lower() == "other":
								txtbox = propName_tmp.replace(postfix, "_name_txt")
								#print txtbox
								
								if form.has_key(txtbox):
									newPropVal = form.getvalue(txtbox)
									
									# update list
									pcID = pHandler.findReagentPropertyInCategoryID(propID, categoryID)
							
									rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, pcID)
									
									setGroupID = sHandler.findPropSetGroupID(pcID)
									ssetID = sHandler.findSetValueID(setGroupID, newPropVal)
									
									if sHandler.findSetValueID(setGroupID, newPropVal) <= 0:
										ssetID = sHandler.addSetValue(setGroupID, newPropVal)
									
									if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
										sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
							
					newPropsDict_id[propCatID] = newPropVal

					## store new property alias and value in dictionary
					#newPropsDict_name[propName] = newPropVal
			
			#print `newPropsDict_id`
			
			# Action
			rHandler.updateReagentProperties(rID, newPropsDict_id)
			#print `assocPropsDict`
			
			rHandler.updateReagentAssociations(rID, assocPropsDict)
	
			# return to detailed view
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)

		## return to detailed view
		#utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)

		# SAVE NOVEL CATEGORIES - AUG. 24/09
		else:
			#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
			#print
			#print `form`
			#print `prop_Category_Name_ID_Map`
			#print section_to_save
			
			# Code taken from Classifiers
			newPropsDict_name = {}			# e.g. ('status', 'Completed')
			newPropsDict_id = {}			# e.g. ('3', 'Completed') - db ID instead of property name

			# Nov. 12/09: Here, section_to_save is an ALIAS
			#categoryID = prop_Category_Name_ID_Map[section_to_save]
			categoryID = prop_Category_Alias_ID_Map[section_to_save]
			classifierNames = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, categoryID)

			classifierAliases = {}
			classifiers = {}
			
			# Map property names to aliases
			for c in classifierNames:
				cAlias = prop_Name_Alias_Map[c]
				#cPropID = prop_Name_ID_Map[c]
				cPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[c], categoryID)
				
				classifierAliases[cAlias] = c
				classifiers[cPropID] = c
				
			# Get property values from form
			for cAlias in classifierAliases:
				tmpPropName = prefix + cAlias + postfix
				#print tmpPropName
				
				pcID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[cAlias], categoryID)
				rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, pcID)
				
				if form.has_key(tmpPropName):
					if not utils.isList(form.getvalue(tmpPropName)):
						newPropVal = form.getvalue(tmpPropName).strip()
					else:
						#print tmpPropName
						newPropVal = utils.unique(form.getlist(tmpPropName))
					
					#print newPropVal
					#print rTypeAttributeID
					
					# May 20, 2010: Check for new list values here, and don't use isList to check; use isMultiple b/c even for multiple dropdowns, when only one value is added, it's not considered a list!!!
					# the check for isMultiple here only tells us whether 'Other' values should be retrieved from a textbox or read from list one-by-one
					if rtPropHandler.isMultiple(rTypeAttributeID):
						tmp_ar = []
						
						if not utils.isList(newPropVal):
							tmp_ar.append(newPropVal)
						else:
							tmp_ar = newPropVal
							
						for val in tmp_ar:
							
							# update set with new values
							setGroupID = sHandler.findPropSetGroupID(pcID)
							ssetID = sHandler.findSetValueID(setGroupID, val)
							
							if sHandler.findSetValueID(setGroupID, val) <= 0:
								ssetID = sHandler.addSetValue(setGroupID, val)
							
							if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
								sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
					else:
						# Jan. 9/09: save 'other' values - Update Feb. 4/10
						#print tmpPropName
						#print `newPropVal`
						
						if newPropVal.lower() == 'other':
							textBoxName = prefix + reagentType_ID_Name_Map[int(rTypeID)] + "_" + section_to_save + "_:_" + cAlias + "_name_txt"
							#print textBoxName
							
							if form.has_key(textBoxName):
								#print "ok"
								newPropVal = form.getvalue(textBoxName).strip()
								#print newPropVal
								
								setGroupID = sHandler.findPropSetGroupID(pcID)
								ssetID = sHandler.findSetValueID(setGroupID, newPropVal)
								
								if sHandler.findSetValueID(setGroupID, newPropVal) <= 0:
									ssetID = sHandler.addSetValue(setGroupID, newPropVal)
								
								if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
									sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)

					newPropsDict_name[cAlias] = newPropVal
					#newPropsDict_name[cAlias] = tmpPropVal
				#else:
					## delete????
					#pcID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[cAlias], categoryID)
					#rHandler.deleteReagentProperty(rID, pcID)
			
			# Match db IDs to property aliases
			for tmpProp in newPropsDict_name.keys():
				
				if prop_Alias_ID_Map.has_key(tmpProp):
					
					# July 2/09: propertyID <= propCatID
					#propID = prop_Alias_ID_Map[tmpProp]	# removed July 2/09
					
					# Nov. 12/09: section_to_save is an alias for new categories
					propID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[tmpProp], prop_Category_Alias_ID_Map[section_to_save])
					newPropsDict_id[propID] = newPropsDict_name[tmpProp]

			# ACTION			
			rHandler.updateReagentPropertiesInCategory(rID, categoryID, newPropsDict_id)
			
			# return to detailed view - Moved here July 7/08
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)

	elif mode == "Cancel":

		# return to detailed view w/o doing anything
		utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)
	
	cursor.close()
	db.close()
	
update()
