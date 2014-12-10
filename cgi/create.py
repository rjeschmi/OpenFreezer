#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()

import MySQLdb
import sys, os, tempfile
import string

from database_conn import DatabaseConn

from mapper import ReagentPropertyMapper, ReagentAssociationMapper, ReagentTypeMapper

from general_handler import *
from reagent_handler import *
from sequence_handler import DNAHandler, ProteinHandler
from comment_handler import CommentHandler
#from system_set_handler import SystemSetHandler
from user_handler import UserHandler
from project_database_handler import ProjectDatabaseHandler

from session import Session

from reagent_output import ReagentOutputClass
from reagent import Reagent, Vector, Insert

from sequence_feature import SequenceFeature

import utils

dbConn = DatabaseConn()
db = dbConn.databaseConnect()

#db = MySQLdb.connect(host="localhost", user="www", passwd="sh2domain", db="LIMS_ReagentTracker_TEST")
cursor = db.cursor()
hostname = dbConn.getHostname()

root_path = dbConn.getRootDir()

# Handlers and mappers
rHandler = ReagentHandler(db, cursor)
iHandler = InsertHandler(db, cursor)
commHandler = CommentHandler(db, cursor)
sHandler = SystemSetHandler(db, cursor)
aHandler = AssociationHandler(db, cursor)
dnaHandler = DNAHandler(db, cursor)
protHandler = ProteinHandler(db, cursor)
rnaHandler = RNAHandler(db, cursor)
pHandler = ReagentPropertyHandler(db, cursor)
raHandler = ReagentAssociationHandler(db, cursor)
rtAssocHandler = ReagentTypeAssociationHandler(db, cursor)
rtPropHandler = ReagentTypePropertyHandler(db, cursor)	# Aug. 31/09
packetHandler = ProjectDatabaseHandler(db, cursor)
rtHandler = ReagentTypeHandler(db, cursor)

propMapper = ReagentPropertyMapper(db, cursor)
assocMapper = ReagentAssociationMapper(db, cursor)
rMapper = ReagentTypeMapper(db, cursor)

prop_Alias_ID_Map = propMapper.mapPropAliasID()		# (propAlias, propID) - e.g. ('insert_type', '48') --> represents 'type of insert' property
prop_Name_Alias_Map = propMapper.mapPropNameAlias()	# (propName, propAlias)
prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)

prop_ID_Name_Map = propMapper.mapPropIDName()		# Added March 13/08 - (prop id, prop name)
prop_Alias_Name_Map = propMapper.mapPropAliasName()	# March 18/08 - (propAlias, propName)
prop_Alias_Descr_Map = propMapper.mapPropAliasDescription()

# June 1/09
prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()
prop_Category_Alias_ID_Map = propMapper.mapPropCategoryAliasID()
prop_Category_Alias_Name_Map = propMapper.mapPropCategoryAliasName()
prop_Category_Name_Alias_Map = propMapper.mapPropCategoryNameAlias()

rOut = ReagentOutputClass()

# Create a type name/id map upfront
reagentType_Name_ID_Map = rMapper.mapTypeNameID()
reagentType_ID_Name_Map = rMapper.mapTypeIDName()


def redirect(url):
	"Called to redirect to the given url."
	print 'Location:' + url
	print


# Process reagent properties upon exit from Modify view
def create():
	
	form = cgi.FieldStorage(keep_blank_values="True")

	#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
	#print					# DITTO
	#print `form`

	# Sept. 3/07
	uHandler = UserHandler(db, cursor)
	
	if form.has_key("curr_username"):
		# store the user ID for use throughout the session; add to other views in addition to create in PHP
		currUname = form.getvalue("curr_username")
		currUser = uHandler.getUserByDescription(currUname)
		
		Session.setUser(currUser)
	
	########################################################
	# Various maps
	########################################################

	prop_Alias_ID_Map = propMapper.mapPropAliasID()		# (propAlias, propID) - e.g. ('insert_type', '48') --> represents 'type of insert' property
	prop_Name_Alias_Map = propMapper.mapPropNameAlias()	# (propName, propAlias)
	prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)
	prop_Alias_Name_Map = propMapper.mapPropAliasName()	# (propAlias, propName)
	
	# Jan. 31/08
	assoc_Name_ID_Map = assocMapper.mapAssocNameID()	# (assocName, assocID)
	assoc_ID_Name_Map = assocMapper.mapAssocIDName()	# (assocID, assocName)
	assoc_ID_Alias_Map = assocMapper.mapAssocIDAlias()	# (assocID, assocAlias)

	# April 15, 2011
	assocType_ID_Name_Map = assocMapper.mapAssocTypeIDName()
	assocType_Name_ID_Map = assocMapper.mapAssocTypeNameID()
	
	# April 15, 2011
	assocType_ID_Name_Map = assocMapper.mapAssocTypeIDName()
	assocType_Name_ID_Map = assocMapper.mapAssocTypeNameID()

	# Nov. 6/08: 'Cancel' function - top priority, regardless of what other actions the form may have
	if form.has_key("cancel_creation"):
		
		# Delete (deprecate) the reagent itself, its properties, protein/DNA sequence and associations
		#rType = form.getvalue("reagent_type_hidden")
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `prop_Alias_Name_Map`
		#print `form`
		
		prefix = "reagent_detailedview_"
		postfix = "_prop"
		
		rID = int(form.getvalue("reagent_id_hidden"))
		
		# if this is an Insert made from Primer, delete the newly created Oligos too
		if form.has_key("from_primer") and form.getvalue("from_primer") == 'True':
			senseOligoID = iHandler.findSenseOligoID(rID)
			antisenseOligoID = iHandler.findAntisenseOligoID(rID)
			
			rHandler.deleteReagent(senseOligoID)
			rHandler.deleteReagent(antisenseOligoID)
			rHandler.deleteReagent(rID)
		else:
			rHandler.deleteReagent(rID)
		
		utils.redirect(hostname + "Reagent.php?View=1")

	# Proceed with creation depending on the reagent type
	elif form.has_key("create_reagent"):
		rType = form.getvalue("reagent_type_hidden")
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print "REAGENT TYPE " + rType

		# Different form name prefixes depending on reagent type - original setup
	
		if rType == 'CellLine':
			
			#print "Content-type:text/html"
			#print
			#print `form`

			# changed Jan. 19, 2010
			#genPrefix = "INPUT_CELLLINE_info_"
			genPrefix = "reagent_detailedview_CellLine_"
			postfix = "_prop"
			
			#assocPrefix = "assoc_"

			assocPropsDict = {}
			
			# Create a new Cell Line instance - Jan. 21, 2010: IFF PARENT!!!!!!! rID already generated for stable
			if form.has_key("reagent_id_hidden"):
				rID = int(form.getvalue("reagent_id_hidden"))
			else:
				rID = rHandler.createNewReagent(rType)
			
			reagent = rHandler.createReagent(rID)
			
			rTypeID = reagentType_Name_ID_Map["CellLine"]

			# Associations
			
			# Jan. 21, 2010
			if form.has_key("INPUT_CELLLINE_info_cellline_id_prop"):
				parentCL = form.getvalue("INPUT_CELLLINE_info_cellline_id_prop").strip()
				parentVector = form.getvalue("INPUT_CELLLINE_info_vector_id_prop").strip()
				
				assocPropsDict["cell_line_parent_vector"] = parentVector
				assocPropsDict["parent_cell_line"] = parentCL
			else:
				aTypeID = "Parent Cell Line"
			
			# Find the actual names and database IDs of POST values
			newPropsDict_name = {}		# e.g. ('status', 'Completed')
        		newPropsDict_id = {}		# e.g. ('3', 'Completed') - db ID instead of property name
			
			# Find actual property values
			for propName_tmp in form.keys():
				#print propName_tmp
				
				if propName_tmp.find("_:_") > 0:
					pToks = propName_tmp.split("_:_")
					#print `pToks`
					
					if pToks[0].find(genPrefix) == 0:
						catAlias = pToks[0][len(genPrefix):]
						#print catAlias
						categoryID = prop_Category_Alias_ID_Map[catAlias]
					else:
						continue
						
					if pToks[1].find(postfix) + len(postfix) == len(pToks[1]):
						propAlias = pToks[1][0:pToks[1].find(postfix)]
						propName = prop_Alias_Name_Map[propAlias]
						propID = prop_Alias_ID_Map[propAlias]
					else:
						continue
					
					if utils.isList(form[propName_tmp]) or propAlias.lower() == "alternate_id":
						newPropVal = utils.unique(form.getlist(propName_tmp))
						#print `newPropVal`
						
						tmp_alt_ids = []
						
						for altID in newPropVal:
							#print altID
							
							# this is ONLY for Alternate ID!!!!!!
							if form.has_key(propAlias + "_" + altID + "_textbox_name"):
								if altID.lower() != "other":
									tmp_alt_id = altID + ":" + form.getvalue(propAlias + "_" + altID + "_textbox_name")
								else:
									#print "HERE!!"
									tmp_alt_id = form.getvalue(propAlias + "_" + altID + "_textbox_name")
									
									#print tmp_alt_id
									
									# Feb. 5/10: update list
									other_alt_id = form.getvalue(propAlias + "_" + altID + "_textbox_name")
									
									#print other_alt_id
									new_set_val = other_alt_id[0:other_alt_id.find(":")]
									#print new_set_val
									
									pcID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[propAlias], categoryID)
							
									rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, pcID)
									
									setGroupID = sHandler.findPropSetGroupID(pcID)
									ssetID = sHandler.findSetValueID(setGroupID, new_set_val)
									
									if sHandler.findSetValueID(setGroupID, form.getvalue(propAlias + "_" + altID + "_textbox_name")) <= 0:
										ssetID = sHandler.addSetValue(setGroupID, new_set_val)
									
									if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
										sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
								
								#print tmp_alt_id
								tmp_alt_ids.append(tmp_alt_id)

							# May 26, 2010: This is for any multiple list properties for Cell Line
							else:
								#print "here " + altID
								
								# update list and save reagent property values
								new_set_val = altID
								
								pcID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[propAlias], categoryID)
								
								#print pcID
							
								rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, pcID)
								
								#print rTypeAttributeID
								
								setGroupID = sHandler.findPropSetGroupID(pcID)
								#print setGroupID
								ssetID = sHandler.findSetValueID(setGroupID, new_set_val)
								#print ssetID
								
								if sHandler.findSetValueID(setGroupID, new_set_val) <= 0:
									ssetID = sHandler.addSetValue(setGroupID, new_set_val)
								
								if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
									sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
								
								tmp_alt_ids.append(altID)

						newPropVal = tmp_alt_ids
					else:
						newPropVal = form[propName_tmp].value.strip()
						
						# May 27, 2010: Here, add check again to see if this is one value in a multiple list!!!  We are not storing property format in database, only way of differentiating b/w freetext and dropdowns that should be updated is: a) If textbox is found - this is a dropdown, save value  b) If rtPropHandler.isMultiple() returns true, this is a multiple dropdown where only one value was entered - OK.  DO NOT ADD ANY OTHER VALUES TO SET!!!!!!!!!!
						pcID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[propAlias], categoryID)
						
						rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, pcID)
						
						# This is a multiple dropdown where only one value was entered.  Update set
						if rtPropHandler.isMultiple(rTypeAttributeID):
							
							setGroupID = sHandler.findPropSetGroupID(pcID)
							ssetID = sHandler.findSetValueID(setGroupID, newPropVal)
							
							if sHandler.findSetValueID(setGroupID, newPropVal) <= 0:
								ssetID = sHandler.addSetValue(setGroupID, newPropVal)
							
							if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
								sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)

						# In 99.9% of cases this is a single dropdown (would be EXTREMELY unlikely that this is a text value that says 'Other').  If textbox exists, update set
						elif newPropVal.lower() == 'other':
							# get the value in the textbox
							#textBox = propName_tmp.replace(postfix, "_name_txt")
							
							# But DO NOT USE 'replace()', as it would replace first occurrence from BEGINNING of name, so if have 'prefix_general_PROPerties_Vector_category_:_propName_prop', the first 'PROP' would get replaced.  DON'T use rstrip() either
							#textBox = propName_tmp.rstrip(postfix) + "_name_txt"
							textBox = propName_tmp[0:len(propName_tmp)-len(postfix)] + "_name_txt"
							
							if form.has_key(textBox):
								newPropVal = form[textBox].value.strip()
								#print newPropVal
								
								setGroupID = sHandler.findPropSetGroupID(pcID)
								ssetID = sHandler.findSetValueID(setGroupID, newPropVal)
								
								if sHandler.findSetValueID(setGroupID, newPropVal) <= 0:
									ssetID = sHandler.addSetValue(setGroupID, newPropVal)
								
								if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
									sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)

					propCatID = pHandler.findReagentPropertyInCategoryID(propID, categoryID)
					newPropsDict_id[propCatID] = newPropVal
					newPropsDict_name[propName] = newPropVal

			# Action
			#print `newPropsDict_id`
			rHandler.addReagentProperties(rID, newPropsDict_id)
			rHandler.addReagentAssociations(rID, assocPropsDict)
			
			# redirect to detailed view of the new reagent
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)
			
		# April 27/09: New reagent types
		else:
			#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
			#print					# DITTO
			#print `form`
			#print rType
			
			genPrefix = "reagent_detailedview_" + rType + "_"	# update Oct. 26/09 - reagent type included in feature names
			postfix = "_prop"
			
			# Create a new Insert instance
			rID = rHandler.createNewReagent(rType)
			reagent = rHandler.createReagent(rID)
			
			rTypeID = reagentType_Name_ID_Map[rType]
			
			# Find the actual names and database IDs of POST values (no checkbox properties for Oligos)
			newPropsDict_name = {}		# e.g. ('status', 'Completed')
        		newPropsDict_id = {}		# e.g. ('3', 'Completed') - db ID instead of property name
			assocPropsDict = {}
			
			enzDict = utils.join(dnaHandler.sitesDict, dnaHandler.gatewayDict)
			
			# moved here June 1/09
			#featureNames = Reagent.getSequenceFeatures()
			#featureDescriptors = Reagent.getFeatureDescriptors()
			
			# Aug 21/09: Features
			if form.has_key("feature_type_" + rType):
				featureType = form.getvalue("feature_type_" + rType)	# this is an alias, e.g. 'dna_sequence_features', 'rna_sequence_features', or 'protein_sequence_features'
			
			# Dec. 10/09: Removed the restriction if rType is Vector or Insert - may update reagent type and its features
			featureCategoryID = prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]]
			#print featureCategoryID
			featureNames = rtPropHandler.findReagentTypeAttributeNamesByCategory(reagentType_Name_ID_Map[rType], featureCategoryID)
			featureDescriptors = Reagent.getFeatureDescriptors()

			categories = {}

			# Find actual property values
			for propName_tmp in form.keys():
				#print propName_tmp
				
				# Extract propName portion
				start = propName_tmp.find(genPrefix)

				#print start

				if start >= 0:
					#print "why here???"
					#print propName_tmp
					spacer_index = propName_tmp.find("_:_")
					#print spacer_index
					categoryAlias = propName_tmp[start+len(genPrefix):spacer_index]
					#print categoryAlias + "!!!"
					
					if prop_Category_Alias_ID_Map.has_key(categoryAlias):
						categoryID = prop_Category_Alias_ID_Map[categoryAlias]
						
						# Aug 6/09
						if categories.has_key(categoryAlias):
							tmp_cat_props = categories[categoryAlias]
						else:
							tmp_cat_props = []
						
						#print categoryAlias
						
						
						# update Jan. 20, 2010
						#end = propName_tmp.rfind(postfix)
						
						if propName_tmp.rfind(postfix) == len(propName_tmp)-len(postfix):
							end = len(propName_tmp) - len(postfix)
						else:
							continue
						
						#print end
						propName = propName_tmp[spacer_index+len("_:_"):end]
						
						tmp_cat_props.append(propName)
						categories[categoryAlias] = tmp_cat_props

						#print propName
						prop_id = prop_Alias_ID_Map[propName]
						propCatID = pHandler.findReagentPropertyInCategoryID(prop_id, categoryID)
						
						rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propCatID)
						#print rTypeAttributeID
		
						# Dec. 10/09: NO!!!!!!
						# What if a feature name is shared between categories?  There can be 'origin' sequence feature and 'origin' classifier!!!  Check CATEGORY here!!!!!!
						#if propName.lower() not in featureNames:	# june 1/09
						if categoryID != prop_Category_Name_ID_Map["DNA Sequence Features"] and categoryID != prop_Category_Name_ID_Map["Protein Sequence Features"] and categoryID != prop_Category_Name_ID_Map["RNA Sequence Features"]:
							#print propName
							
							# new value that would be added to the set (dropdown/checkbox)
							newSetEntry = ""
							
							#tmpPropList = form.getlist(propName_tmp)
							newPropVal = form.getvalue(propName_tmp)
							#print `newPropVal`
							
							#if utils.isList(tmpPropList):
							#for newPropVal in tmpPropList:
								#print newPropVal
								#newPropVal = form[propName_tmp].value.strip()
								
							#print propName_tmp
							#print newPropVal
							
							if propName.lower() == "alternate_id":
								newPropVal = form.getlist(propName_tmp)
								
								tmp_alt_ids = []
								
								for altID in newPropVal:
									if form.has_key(propName + "_" + altID + "_textbox_name"):
										if altID.lower() != "other":
											tmp_alt_id = altID + ":" + form.getvalue(propName + "_" + altID + "_textbox_name")
										else:
											tmp_alt_id = form.getvalue(propName + "_" + altID + "_textbox_name")
											
											# Feb. 5/10: update list
											other_alt_id = form.getvalue(propName + "_" + altID + "_textbox_name")
											new_set_val = other_alt_id[0:other_alt_id.find(":")]
											
											#rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propCatID)
											
											setGroupID = sHandler.findPropSetGroupID(propCatID)
											ssetID = sHandler.findSetValueID(setGroupID, new_set_val)
											
											if sHandler.findSetValueID(setGroupID, form.getvalue(propName + "_" + altID + "_textbox_name")) <= 0:
												ssetID = sHandler.addSetValue(setGroupID, new_set_val)
											
											if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
												sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
										
										#print tmp_alt_id
										tmp_alt_ids.append(tmp_alt_id)
	
								newPropVal = tmp_alt_ids
								
							else:
								#print propName

								# April 11, 2011: Compute MW, Tm, GC% - whichever is applicable for the current sequence type
								if propName.lower() == prop_Name_Alias_Map["sequence"]:
									# MW, Tm, GC% (protein translation computed for Inserts separately)
									
									tmPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["melting temperature"], prop_Category_Name_ID_Map["DNA Sequence"])
									
									mwPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["DNA Sequence"])

									gcPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["gc content"], prop_Category_Name_ID_Map["DNA Sequence"])
									
									molWeight = dnaHandler.calculateMW(dnaHandler.squeeze(newPropVal.lower().strip()))
									
									gc_content = dnaHandler.calculateGC(dnaHandler.squeeze(newPropVal.lower().strip()))
									
									melTemp = dnaHandler.calculateTm(dnaHandler.squeeze(newPropVal.lower().strip()))

									newPropsDict_id[gcPropID] = gc_content
									newPropsDict_id[mwPropID] = molWeight
									newPropsDict_id[tmPropID] = melTemp
									
								#elif propName.lower() == prop_Name_Alias_Map["rna sequence"]:
									# MW, GC%
									
									#mwPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["RNA Sequence"])

									# Actually, remove GC% from RNA, it's not a default property
									#gcPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["gc content"], prop_Category_Name_ID_Map["RNA Sequence"])
									
									#gc_content = dnaHandler.calculateGC(dnaHandler.squeeze(newPropVal.lower().strip()))
									
									#newPropsDict_id[gcPropID] = gc_content
									
									# April 14, 2011: Karen said to NOT calculate MW for RNA automatically
									#molWeight = rnaHandler.calculateMW_RNA(rnaHandler.squeeze(newPropVal.lower().strip()))

									#newPropsDict_id[mwPropID] = molWeight									
								elif propName.lower() == prop_Name_Alias_Map["protein sequence"]:
									# only MW
									mwPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["Protein Sequence"])

									peptideMass = protHandler.calculatePeptideMass(protHandler.squeeze(newPropVal.lower().strip()))

									newPropsDict_id[mwPropID] = peptideMass

								if utils.isList(form[propName_tmp]):
									newPropVal = utils.unique(form.getlist(propName_tmp))
								
								# OK, this is for single dropdowns
								elif newPropVal.lower() == 'other':
				
									# get the value in the textbox
									#textBox = propName + "_name_txt"
									textBox = propName_tmp[0:len(propName_tmp)-len(postfix)] + "_name_txt"
									newPropVal = form[textBox].value.strip()
									newSetEntry = newPropVal
				
									# invoke handler to add textbox value to dropdown list
									
									# update Nov. 18/09
									setGroupID = sHandler.findPropSetGroupID(propCatID)	# it must exist
									
									ssetID = sHandler.findSetValueID(setGroupID, newSetEntry)
									
									if sHandler.findSetValueID(setGroupID, newSetEntry) <= 0:
										ssetID = sHandler.addSetValue(setGroupID, newSetEntry)
									
									if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
										sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)

								# May 20, 2010: Check for new list values here, and don't use isList to check; use isMultiple b/c even for multiple dropdowns, when only one value is added, it's not considered a list1!!!
								#rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propCatID)

								if rtPropHandler.isMultiple(rTypeAttributeID):
									tmp_ar = []
									
									if not utils.isList(newPropVal):
										tmp_ar.append(newPropVal)
									else:
										tmp_ar = newPropVal
									
									for val in tmp_ar:
										
										# update set with new values
										setGroupID = sHandler.findPropSetGroupID(propCatID)
										ssetID = sHandler.findSetValueID(setGroupID, val)
										
										if sHandler.findSetValueID(setGroupID, val) <= 0:
											ssetID = sHandler.addSetValue(setGroupID, val)
										
										if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
											sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)

							# store new property alias and value in dictionary
							newPropsDict_id[propCatID] = newPropVal

			#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
			#print					# DITTO
			#print `newPropsDict_id`

			# Action
			rHandler.addReagentProperties(rID, newPropsDict_id)

			# (deletion is performed in updateFeatures function, no need to call here)
			newPropsDict_name = {}			# e.g. ('status', 'Completed')
			newPropsDict_id = {}			# e.g. ('3', 'Completed') - db ID instead of property name
			
			startPosDict = {}			# (propID, startpos)
			endPosDict = {}				# (propID, endpos)
			
			# Store orientation
			orientationDict = {}			# (propID, orientation)
			
			# Nov. 6/08: Delete before update
			rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["5' cloning site"])
			rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["3' cloning site"])
			
			# Sept. 8/08: Delete linkers too
			rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["5' linker"])
			rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["3' linker"])
			
			# features too
			rHandler.deleteReagentFeatures(rID)

			if form.has_key(genPrefix + prop_Name_Alias_Map["5' linker"] + postfix):
				#print "5' Linker"
				five_prime_linker = form.getvalue(genPrefix + prop_Name_Alias_Map["5' linker"] + postfix).replace(" ", "").strip()
				fAlias = prop_Name_Alias_Map["5' linker"]

				#fID = prop_Alias_ID_Map[fAlias]
				fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
				
				startFieldName = genPrefix + fAlias + "_startpos" + postfix
				endFieldName = genPrefix + fAlias + "_endpos" + postfix
				
				if form.has_key(startFieldName):
					fStartPos = form.getvalue(startFieldName)
				else:
					fStartPos = 0
					
				if form.has_key(endFieldName):
					fEndPos = form.getvalue(endFieldName)
				else:
					fEndPos = 0

				# orientation
				orientationFieldName = genPrefix + fAlias + "_orientation" + postfix
			
				if form.has_key(orientationFieldName):
					tmpDir = form.getvalue(orientationFieldName)
					#print tmpDir
				else:
					tmpDir = 'forward'

				if len(five_prime_linker) > 0:
					rHandler.addReagentProperty(rID, fID, five_prime_linker, fStartPos, fEndPos, tmpDir)
				
			if form.has_key(genPrefix + prop_Name_Alias_Map["3' linker"] + postfix):
				three_prime_linker = form.getvalue(genPrefix + prop_Name_Alias_Map["3' linker"] + postfix).replace(" ", "").strip()
				
				fAlias = prop_Name_Alias_Map["3' linker"]

				#fID = prop_Alias_ID_Map[fAlias]
				fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
				
				startFieldName = genPrefix + fAlias + "_startpos" + postfix
				endFieldName = genPrefix + fAlias + "_endpos" + postfix
				
				if form.has_key(startFieldName):
					fStartPos = form.getvalue(startFieldName)
				else:
					fStartPos = 0
					
				if form.has_key(endFieldName):
					fEndPos = form.getvalue(endFieldName)
				else:
					fEndPos = 0

				# orientation
				orientationFieldName = genPrefix + fAlias + "_orientation" + postfix
			
				if form.has_key(orientationFieldName):
					tmpDir = form.getvalue(orientationFieldName)
					#print tmpDir
				else:
					tmpDir = 'forward'
					
				if len(three_prime_linker) > 0:
					rHandler.addReagentProperty(rID, fID, three_prime_linker, fStartPos, fEndPos, tmpDir)
			
			# May 27/09: Features - all on the same form for custom reagent types
			seqFeatures = []
			
			featureAliases = {}
			features = {}
		
			# Update Nov. 3/09
			#print featureType
			featureNames = rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, prop_Category_Alias_ID_Map[featureType])
			
			#print `featureNames`
			
			for f in featureNames:
				fAlias = prop_Name_Alias_Map[f]
				fPropID = prop_Name_ID_Map[f]
				
				featureAliases[fAlias] = f
				features[fPropID] = f
			
			#print `featureAliases`
			#print `prop_Alias_Name_Map`
		
			for fAlias in featureAliases:
				#print fAlias
				
				# Special case: cDNA start and stop positions
				#print prop_Alias_Name_Map[fAlias].lower()
				if prop_Alias_Name_Map[fAlias].lower() == 'cdna insert':
					
					# No value, positions only
					# Still need to create a new Feature instance
					#fTemp = SequenceFeature(SequenceFeature)
					#fID = prop_Alias_ID_Map[fAlias]
					fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
				
					#fType = prop_ID_Name_Map[fID]
					#fTemp.setFeatureType(fType)
					
					startFieldName = genPrefix + fAlias + "_startpos" + postfix
					endFieldName = genPrefix + fAlias + "_endpos" + postfix
					
					if form.has_key(startFieldName):
						tmpStartPos = form.getvalue(startFieldName)
						#fTemp.setFeatureStartPos(tmpStartPos)
					else:
						tmpStartPos = 0

					if form.has_key(endFieldName):
						tmpEndPos = form.getvalue(endFieldName)
						#fTemp.setFeatureEndPos(tmpEndPos)
					else:
						tmpEndPos = 0

					#seqFeatures.append(fTemp)
					
					# Nov. 6/08: Orientation needed too (for reverse complemented Inserts)
					orientationFieldName = genPrefix + fAlias + "_orientation" + postfix
				
					if form.has_key(orientationFieldName):
						tmpDir = form.getvalue(orientationFieldName)
						#print tmpDir
					else:
						tmpDir = 'forward'
						
					if tmpEndPos > 0:
						rHandler.addReagentProperty(rID, fID, "", tmpStartPos, tmpEndPos)
					
				elif prop_Alias_Name_Map[fAlias].lower() == "5' cloning site":
					#print genPrefix + prop_Name_Alias_Map["5' cloning site"] + postfix
					five_prime_site = form.getvalue(genPrefix + prop_Name_Alias_Map["5' cloning site"] + postfix)
					#print "5' site " + five_prime_site
					
					fAlias = prop_Name_Alias_Map["5' cloning site"]
	
					# May 28/08: Allow blank sites for Novel vectors
					if five_prime_site:
						if five_prime_site == "Other":
							five_prime_site = form.getvalue("5_prime_cloning_site_name_txt")
						
						#fID = prop_Alias_ID_Map[fAlias]
						fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
				
						startFieldName = genPrefix + fAlias + "_startpos" + postfix
						endFieldName = genPrefix + fAlias + "_endpos" + postfix
						
						#print startFieldName
						
						if form.has_key(startFieldName):
							fStartPos = form.getvalue(startFieldName)
						else:
							fStartPos = 0
							
						if form.has_key(endFieldName):
							fEndPos = form.getvalue(endFieldName)
						else:
							fEndPos = 0
		
						#print " start " + fStartPos
						#print " end " + fEndPos
		
						# orientation
						orientationFieldName = genPrefix + fAlias + "_orientation" + postfix
					
						if form.has_key(orientationFieldName):
							tmpDir = form.getvalue(orientationFieldName)
							#print tmpDir
						else:
							tmpDir = 'forward'
		
						rHandler.addReagentProperty(rID, fID, five_prime_site, fStartPos, fEndPos, tmpDir)
					
				elif prop_Alias_Name_Map[fAlias].lower() == "3' cloning site":
					three_prime_site = form.getvalue(genPrefix + prop_Name_Alias_Map["3' cloning site"] + postfix)
					#print "3' site: " + three_prime_site
					
					fAlias = prop_Name_Alias_Map["3' cloning site"]
	
					# May 28/08: Allow blank sites for Novel vectors
					if three_prime_site:
						if three_prime_site == "Other":
							three_prime_site = form.getvalue("3_prime_cloning_site_name_txt")
							
						#fID = prop_Alias_ID_Map[fAlias]
						fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
				
						startFieldName = genPrefix + fAlias + "_startpos" + postfix
						endFieldName = genPrefix + fAlias + "_endpos" + postfix
						
						if form.has_key(startFieldName):
							fStartPos = form.getvalue(startFieldName)
						else:
							fStartPos = 0
							
						if form.has_key(endFieldName):
							fEndPos = form.getvalue(endFieldName)
						else:
							fEndPos = 0
						
						# Sept. 3/08: Store orientation
						orientationFieldName = genPrefix + fAlias + "_orientation" + postfix
						
						if form.has_key(orientationFieldName):
							tmpDir = form.getvalue(orientationFieldName)
							#print tmpDir
						else:
							tmpDir = 'forward'
		
						rHandler.addReagentProperty(rID, fID, three_prime_site, fStartPos, fEndPos, tmpDir)
				else:
					# make sure linkers are not added here again!
					
					tmpStart = -1
					tmpEnd = -1
					
					for tmpPropName in form.keys():
						#print tmpPropName
						#print fAlias
						
						# convert to lowercase b/c of PolyA
						if tmpPropName.find(genPrefix + fAlias + "_:_") >= 0:
							#print tmpPropName
	
							pValStartInd = len(genPrefix + fAlias)+3
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
										tmpDirName = genPrefix + fAlias + "_:_" + tmpPropValue + "_start_" + `int(tmpStart)` + "_end_" + `int(tmpEnd)` + "_orientation" + postfix
										#print "ok " + tmpDirName
										
										if form.has_key(tmpDirName):
											tmpDir = form.getvalue(tmpDirName)
											#print "FOUND DIRECTION " + tmpDirName
								
											# Nov. 13/08: If there are duplicates, select one
											if utils.isList(tmpDir):
												#print "Descriptor " + tmpDescr
												tmpDir = tmpDir[0]
								
											#fID = prop_Alias_ID_Map[fAlias]
											fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
										
											# add value to list if not already in it
											# April 23, 2010: When we get to this stage, the value is not 'Other' anymore, but it's the actual textbox value, appended and passed to the form by JS.  So don't check if other here, just check if exists in dropdown and update list if not!!!
											#if tmpPropValue.lower() == 'other':	# rmvd April 23, 2010
												#sHandler.updateSet(fAlias, tmpPropValue)
											
											# update Nov. 18/09
											setGroupID = sHandler.findPropSetGroupID(fID)
											
											ssetID = sHandler.findSetValueID(setGroupID, tmpPropValue)
											
											if sHandler.findSetValueID(setGroupID, tmpPropValue) <= 0:
												ssetID = sHandler.addSetValue(setGroupID, tmpPropValue)
										
											rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, fID)
											
											if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
												sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
											
										
											#print "id is " + fID
											#print fAlias
											#print prop_Alias_Name_Map[fAlias]
								
											# descriptor
											if featureDescriptors.has_key(prop_Alias_Name_Map[fAlias]):
												tmpDescrName = featureDescriptors[prop_Alias_Name_Map[fAlias]]
												tmpDescrAlias = prop_Name_Alias_Map[tmpDescrName]
												#print "DESCRIPTOR " + tmpDescrAlias
										
												tmpDescrField = genPrefix + tmpDescrAlias + "_:_" + tmpPropValue + "_start_" + tmpStart + "_end_" + tmpEnd + postfix

												#print tmpDescrField
												
												if form.has_key(tmpDescrField):
													
													tmpDescr = form.getvalue(tmpDescrField)
										
													# add value to list if not already in it
													#if tmpDescr.lower() == 'other':
													descPropID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[tmpDescrAlias], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
										
													rTypeDescrAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, descPropID)
									
													# update Nov. 18/09
													setGroupID = sHandler.findPropSetGroupID(descPropID)
													
													ssetID = sHandler.findSetValueID(setGroupID, tmpDescr)
													
													if sHandler.findSetValueID(setGroupID, tmpDescr) <= 0:
														ssetID = sHandler.addSetValue(setGroupID, tmpDescr)
													
													if not sHandler.existsReagentTypeAttributeSetValue(rTypeDescrAttributeID, ssetID):
														sHandler.addReagentTypeAttributeSetEntry(rTypeDescrAttributeID, ssetID)
													
										
													# Nov. 13/08: If there are duplicates, select one
													if utils.isList(tmpDescr):
														#print "Descriptor " + tmpDescr
														tmpDescr = tmpDescr[0]
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
			
			# June 12/09: Verification image
			try: # Windows needs stdio set for binary mode.
				import msvcrt
				msvcrt.setmode (0, os.O_BINARY) # stdin  = 0
				msvcrt.setmode (1, os.O_BINARY) # stdout = 1
			except ImportError:
				pass
			
			# Generator to buffer file chunks
			def fbuffer(f, chunk_size=10000):
				while True:
					chunk = f.read(chunk_size)
					
					if not chunk:
						break
					
				yield chunk

			if form.has_key("verification_image"):
				fileitem = form["verification_image"]
				
				# Test if the file was uploaded
				# Oct 22/09: Removed temporarily - apparently fileitem is a list, likely b/c I'm not differentiating verification_image form fields by reagent type.  To be fixed.
				#if fileitem.filename:
					##print fileitem.filename
					## strip leading path from file name to avoid directory traversal attacks
					#fn = os.path.basename(fileitem.filename)
					#f = open(root_path + 'image_uploads/' + fn, 'wb', 10000)
				
					## Read the file in chunks
					#for chunk in fbuffer(fileitem.file):
						#f.write(chunk)
					
					#f.close()
			
			if form.has_key("reagent_association_types"):
				# Aug. 11/09: Associations; moved here Dec. 9/09 - don't do anything if reagent has no associations, e.g. Oligo
				aMapper = ReagentAssociationMapper(db, cursor)
				assoc_Type_Name_Map = aMapper.mapAssocTypeNameID()
				
				# Associations
				assocPrefix = "assoc_"
				
				if rType == 'Vector' or rType == 'Insert' or rType == 'CellLine':
					assocPostfix = "_hidden"
				else:
					assocPostfix = "_prop"
					
				aTypeID = rtAssocHandler.findAssociationByReagentType(rTypeID)
				#print aTypeID
				
				assocID = rHandler.createReagentAssociation(rID, aTypeID)
				reagent.setCloningMethod(assocID)	# sept. 13/07
	
				assocPropsDict = {}
				reagent_association_types = []

				reagent_association_types = form.getlist("reagent_association_types")
				
				#print `reagent_association_types`
				
				for assocPropID in reagent_association_types:
					
					assocPropID = int(assocPropID)
					
					if form.has_key("assoc_" + `assocPropID` + "_prop"):
						#tmpAssocProp = form.getvalue("assoc_" + assocPropAlias + "_prop").strip()
						
						assocProps = form.getlist("assoc_" + `assocPropID` + "_prop")
					
					elif form.has_key(rType + "_assoc_" + `assocPropID` + "_prop"):
						assocProps = form.getlist(rType + "_assoc_" + `assocPropID` + "_prop")
						
					#print `assocProps`
					
					for tmpAssocProp in assocProps:
						#print tmpAssocProp
						
						if assoc_ID_Alias_Map.has_key(assocPropID):
							#assocPropAlias = assoc_ID_Alias_Map[assocPropID]
							
							#if assocPropsDict.has_key(assocPropAlias):
							if assocPropsDict.has_key(assocPropID):
								#tmp_assoc = assocPropsDict[assocPropAlias]
								tmp_assoc = assocPropsDict[assocPropID]
							else:
								tmp_assoc = []

							tmp_assoc.append(tmpAssocProp.strip())
							assocPropsDict[assocPropID] = tmp_assoc
			
			rHandler.addReagentAssociations(rID, assocPropsDict)
			
			utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)
			
	# Added Jan. 31/08: From Primer Designer, use the generated sequence to create Insert or Oligos directly
	elif form.has_key("create_oligo"):
		
		# Create ONLY Oligos, independently of Insert
		oligo_type = form.getvalue("oligo_type")
		oligo_sequence = form.getvalue("primer_sequence")
		
		mw = form.getvalue("primer_mw")
		tm = form.getvalue("primer_tm")
		
		# June 25, 2010
		gc = form.getvalue("primer_gc")
		
		# Start processing
		oTypeID = reagentType_Name_ID_Map['Oligo']
		#print `oTypeID`
		
		oligoID = rHandler.createNewReagent('Oligo')
		#oligo = rHandler.createReagent(oligoID)
		
		# Get the name of the template insert and add 'forward'/'reverse' to it
		templateID = form.getvalue("primer_insert")
		
		# correction Oct. 1, 2010
		namePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map['name'], prop_Category_Name_ID_Map["General Properties"])
		
		templateName = rHandler.findSimplePropertyValue(templateID, namePropID)
		
		if templateName:
			if oligo_type == "Sense":
				newOligoName = templateName + " forward"
			else:
				newOligoName = templateName + " reverse"
		
			rHandler.addReagentProperty(oligoID, namePropID, newOligoName)
			
		# Fix March 22, 2010
		oTypePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map['oligo type'], prop_Category_Name_ID_Map["General Properties"])
		
		mwPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["DNA Sequence"])
	
		tmPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["melting temperature"], prop_Category_Name_ID_Map["DNA Sequence"])
		
		# June 25, 2010
		gcPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["gc content"], prop_Category_Name_ID_Map["DNA Sequence"])
						
		rHandler.addReagentProperty(oligoID, oTypePropID, oligo_type)
		rHandler.addReagentProperty(oligoID, mwPropID, mw)
		rHandler.addReagentProperty(oligoID, tmPropID, tm)
		rHandler.addReagentProperty(oligoID, gcPropID, gc)	# june 25, 2010
		
		oligoSeqID = dnaHandler.getSequenceID(oligo_sequence)
		
		seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])
		
		rHandler.addReagentProperty(oligoID, seqPropID, oligoSeqID)

		# Redirect to Oligo **Modify** view - with input fields pre-filled, user can fill in the rest
		utils.redirect(hostname + "Reagent.php?View=6&mode=Create&rType=" + rType + "&rid=" + `oligoID`)

	elif form.has_key("create_insert"):
		
		# Create Insert WITH Oligos (from Primer Design)
		rType = 'Insert'		# april 23/08

		#print "Content-type:text/html"
		#print
		#print `form`
		
		# june 6/08
		cdnaStart = 0
		cdnaEnd = 0

		# sequence includes linkers
		insert_seq = utils.squeeze(form.getvalue("insert_seq")).lower()		# remember to change case!!!!!
		
		iTypeID = reagentType_Name_ID_Map['Insert']
		insertID = rHandler.createNewReagent('Insert')
		
		iSeqID = dnaHandler.getSequenceID(insert_seq)
		seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])
		rHandler.addReagentProperty(insertID, seqPropID, iSeqID)
	
		iTypeAssocID = assocType_Name_ID_Map["Insert Oligos"]
	
		iTypeAssocID = assocType_Name_ID_Map["Insert Oligos"]

		# Create an association for this insert/oligos
		assocID = rHandler.createReagentAssociation(insertID, iTypeAssocID)
		
		# Get the name of the original (template) Insert and construct names for the new oligos
		templateID = form.getvalue("template_insert")
		
		# moved up here Oct. 1, 2010
		namePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["name"], prop_Category_Name_ID_Map["General Properties"])
		
		templateName = rHandler.findSimplePropertyValue(templateID, namePropID)
		
		# Feb. 13/08: Cannot set the IPV of the new insert to be its template's child vector, since the template may have multiple vector children
		
		# Sense Oligo
		senseSeq = form.getvalue("sense_sequence")
		senseMW = form.getvalue("sense_mw")
		senseTM = form.getvalue("sense_tm")
		
		senseGC = form.getvalue("sense_gc")
		
		senseID = rHandler.createNewReagent('Oligo')
		
		# May 14/08: Human-readable form, to pass to next step
		senseOligo = rHandler.convertDatabaseToReagentID(senseID)
		
		oligoTypePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["oligo type"], prop_Category_Name_ID_Map["General Properties"])
		
		mwPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["molecular weight"], prop_Category_Name_ID_Map["DNA Sequence"])
	
		tmPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["melting temperature"], prop_Category_Name_ID_Map["DNA Sequence"])
		
		# April 1, 2010
		gcPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["gc content"], prop_Category_Name_ID_Map["DNA Sequence"])
		
		# Store Sense properties
		if templateName:
			senseOligoName = templateName + " forward"
			rHandler.addReagentProperty(senseID, namePropID, senseOligoName)
			
		rHandler.addReagentProperty(senseID, oligoTypePropID, "Sense")
		rHandler.addReagentProperty(senseID, mwPropID, senseMW)
		rHandler.addReagentProperty(senseID, tmPropID, senseTM)
		
		rHandler.addReagentProperty(senseID, gcPropID, senseGC)
		
		senseSeqID = dnaHandler.getSequenceID(senseSeq)
		rHandler.addReagentProperty(senseID, seqPropID, senseSeqID)
		
		# Store 'sense oligo' association
		sensePropID = aHandler.findAssocPropID("sense oligo")
		rHandler.addAssociationValue(insertID, sensePropID, senseID, assocID)
		
		# Antisense Oligo
		antisenseSeq = form.getvalue("antisense_sequence")
		antisenseMW = form.getvalue("antisense_mw")
		antisenseTM = form.getvalue("antisense_tm")
		
		antisenseGC = form.getvalue("antisense_gc")
		
		antisenseID = rHandler.createNewReagent('Oligo')
		
		# May 14/08: Human-readable form, to pass to next step
		antisenseOligo = rHandler.convertDatabaseToReagentID(antisenseID)
		
		if templateName:
			antisenseOligoName = templateName + " reverse"
			rHandler.addReagentProperty(antisenseID, namePropID, antisenseOligoName)
		
		rHandler.addReagentProperty(antisenseID, oligoTypePropID, "Antisense")
		rHandler.addReagentProperty(antisenseID, mwPropID, antisenseMW)
		rHandler.addReagentProperty(antisenseID, tmPropID, antisenseTM)
		
		rHandler.addReagentProperty(antisenseID, gcPropID, antisenseGC)
		
		antisenseSeqID = dnaHandler.getSequenceID(antisenseSeq)
		rHandler.addReagentProperty(antisenseID, seqPropID, antisenseSeqID)

		# Store Antisense Oligo association
		antisensePropID = aHandler.findAssocPropID("antisense oligo")
		rHandler.addAssociationValue(insertID, antisensePropID, antisenseID, assocID)
		
		# April 3/08: Get the Insert Parent Vector ID from template
		#print "Content-type:text/html"
		#print

		aTypeID = aHandler.findATypeID("Insert Parent Vector")
		ipvPropID = aHandler.findAssocPropID("insert parent vector id")
		#print `ipvPropID`
		ipvAssocID = raHandler.findSpeReagentAssociationPropertyID(templateID, ipvPropID)
		#print `templateID`
		ipvID = aHandler.findAssocPropValue(ipvAssocID, ipvPropID)
		#print `ipvID`
		rHandler.addAssociationValue(insertID, ipvPropID, ipvID)
		
		# june 10/08
		insertParentVector = rHandler.convertDatabaseToReagentID(ipvID)
		
		# Determine Insert sites and their positions from primer linkers
		# Here, fwd_linker and rev_linker refer to the sequences user may choose to add to primers by selecting them from a dropdown list
		# e.g. "Gateway 5' linker with stop codon", "HIS linker without stop codon", etc.
		# They are NOT the same as 5' and 3' linker values stored for the end Insert
		if form.has_key("fwd_linker"):
			fwd_linker = form.getvalue("fwd_linker")
		else:
			fwd_linker = ""
	
		if form.has_key("rev_linker"):
			rev_linker = form.getvalue("rev_linker")
		else:
			rev_linker = ""

		##########################################################################################################################
		# Feb. 12/08: Wanted to search the linker sequence for **every possible restriction enzyme**.  If found, designate the sequence portion before the enzyme as the "filler" and the sequence portion after the enzyme as the "linker"
		
		# BUT dismissed this idea because the linker may contain multiple site sequences (mostly His-vectors but still)
		
		# Just keep it simple:
		enzDict = utils.join(dnaHandler.sitesDict, dnaHandler.gatewayDict)	# still need this
		
		fpSitePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
		
		tpSitePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
		fpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
		
		tpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
		
		if len(fwd_linker) > 0:
			if utils.squeeze(fwd_linker.strip().lower()) == "ggggacaactttgtacaaaaaagttggcaccatg" or utils.squeeze(fwd_linker.strip().lower()) == "ggggacaactttgtacaaaaaagttggcacc":
				fp_site = 'attB1'
				db_prop_val = fp_site
				
			else:
				fp_site = 'AscI'
				db_prop_val = fp_site
			
			fp_seq = enzDict[fp_site].lower()
			
			# Record the site's position relative to the **ENTIRE Insert sequence**
			if insert_seq.find(fp_seq) >= 0:
				five_start_pos = insert_seq.index(fp_seq) + 1		# make it human-readable, don't start from 0
				five_end_pos = five_start_pos + len(fp_seq) - 1
			
				# store site WITH POSITIONS
				rHandler.addReagentProperty(insertID, fpSitePropID, db_prop_val, five_start_pos, five_end_pos)
			
			# Find filler and linker on the new Insert sequence
			# "Filler" is the portion from the start of the sequence to the start of the 5' site
			# "Linker" is the portion from the end of the 5' site to the start of the cDNA
			if fwd_linker.find(fp_seq) >= 0:	# better be found - Nov. 20/08: EXCEPT when linker == 'Other'!!
				fp_site_start = fwd_linker.index(fp_seq)
				fp_site_end = fp_site_start + len(fp_seq)
				
				# filler - portion of the linker sequence up to the enzyme
				fp_filler = insert_seq[0:five_start_pos]
				
				# linker - portion of the linker sequence between the enzyme and cDNA
				fwd_linker_len = len(fwd_linker)
				insert_linker_size = fwd_linker_len - fp_site_end
				fp_linker_end = five_end_pos + insert_linker_size
				fp_linker = insert_seq[five_end_pos:fp_linker_end]
				
				# store fp_linker as property of the Insert
				if len(fp_linker) > 0:
					rHandler.addReagentProperty(insertID, fpLinkerPropID, fp_linker, five_end_pos+1, fp_linker_end)
			else:	# nov 20/08
				#fp_linker_start = 0
				fp_linker_end = -1

		# 3' linker
		if len(rev_linker) > 0:
			
			# Reverse complement the linker (APril 25/08)
			rev_linker_seq = dnaHandler.reverse_complement(rev_linker)
			
			if utils.squeeze(rev_linker.strip().lower()) == "ggggacaactttgtacaagaaagttgggtacta" or utils.squeeze(rev_linker.strip().lower()) == "ggggacaactttgtacaagaaagttgggta":
				tp_site = 'attB2'
				
				# Database value needs to be "attB", not "attB1" => APRIL 24/08: NO!!!!!! actually storing indices
				db_prop_val = tp_site
			
				# When searching for the 3' site on the Insert sequence, site sequence needs to be reversed
				tp_seq = enzDict[tp_site].lower()
				rev_tp_seq = dnaHandler.reverse_complement(tp_seq)
			
			else:
				tp_site = 'PacI'
				db_prop_val = tp_site
				tp_seq = enzDict[tp_site].lower()	# palindrome - reads the same both ways, forward or reverse

				## Change Jan. 17, 2011: for linker 'other' the site is not necessarily PacI
				#if rev_linker.lower().find(tp_seq.lower()):
				rev_tp_seq = tp_seq
				#else:
					#rev_tp_seq = ""
		
			# Changes made April 25/08
			
			#print "Content-type:text/html"
			#print
			
			# Search the ***ENTIRE*** Insert sequence for a ***REVERSE***-oriented LINKER
			rev_linker_start = insert_seq.lower().find(rev_linker_seq.lower())	# Occurs right after cDNA; DON'T add 1 **yet**
			
			# And here, search for the **FORWARD**-ORIENTED **site** sequence
			# add 1 here, subtract later during linker computation
			if rev_linker_seq.find(tp_seq) >= 0:
				three_start_pos = rev_linker_start + rev_linker_seq.index(tp_seq) + 1
				three_end_pos = three_start_pos + len(tp_seq) - 1	# length does not depend on orientation
				
				# Now the 'linker' portion of the primer linker sequence is between the end of cDNA and the enzyme
				tp_linker_start = rev_linker_start
				tp_linker_end = three_start_pos - 1		# subtract 1 here for string lookup
				
				# 'Linker' portion of the entire primer linker - between cDNA and cloning site
				tp_linker = insert_seq[tp_linker_start:tp_linker_end]
				
				# store site with positions
				rHandler.addReagentProperty(insertID, tpSitePropID, db_prop_val, three_start_pos, three_end_pos)
				
				# store 3' linker as property of the Insert, add 1 to startpos for readability (do it here, preserve zero-indexing during sequence lookup above but insert a human-readable value into the database)
				if len(tp_linker) > 0:
					rHandler.addReagentProperty(insertID, tpLinkerPropID, tp_linker, tp_linker_start+1, tp_linker_end)
			else:	# nov 20/08
				tp_linker_start = 0
				
		# May 23/08: While we're at it, why not autofill cDNA positions - from 5' linker end to 3' linker start
		if len(fwd_linker) > 0 and len(rev_linker) > 0:		# added June 6/08 to cover case w/o linkers
			cdnaStart = fp_linker_end + 1
			cdnaEnd = tp_linker_start

		# July 2/09
		cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
		
		rHandler.setPropertyPosition(insertID, cdnaPropID, "startPos", cdnaStart)
		rHandler.setPropertyPosition(insertID, cdnaPropID, "endPos", cdnaEnd)
		
		##################################################################
		# Feb. 5/08: Inherit various properties from template:
		##################################################################
		# Accession
		accessionPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["accession number"], prop_Category_Name_ID_Map["External Identifiers"])
		
		entrezGenePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["entrez gene id"], prop_Category_Name_ID_Map["External Identifiers"])
		
		geneSymbolPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["official gene symbol"], prop_Category_Name_ID_Map["External Identifiers"])
		
		ensemblGenePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["ensembl gene id"], prop_Category_Name_ID_Map["External Identifiers"])
		
		template_accession = rHandler.findSimplePropertyValue(templateID, accessionPropID)
		
		if template_accession and template_accession != "":
			rHandler.addReagentProperty(insertID, accessionPropID, template_accession)
		
		# Entrez Gene ID
		template_gene_id = rHandler.findSimplePropertyValue(templateID, entrezGenePropID)
		
		if template_gene_id and template_gene_id != "":
			rHandler.addReagentProperty(insertID, entrezGenePropID, template_gene_id)
		
		# Gene Symbol
		template_gene_symbol = rHandler.findSimplePropertyValue(templateID, geneSymbolPropID)
		
		if template_gene_symbol and template_gene_symbol != "":
			rHandler.addReagentProperty(insertID, geneSymbolPropID, template_gene_symbol)
		
		# Ensembl Gene ID
		ensembl_template_gene_id = rHandler.findSimplePropertyValue(templateID, ensemblGenePropID)
		
		if ensembl_template_gene_id and ensembl_template_gene_id != "":
			rHandler.addReagentProperty(insertID, ensemblGenePropID, ensembl_template_gene_id)
		
		# Species
		speciesPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["species"], prop_Category_Name_ID_Map["Classifiers"])
		
		template_species = rHandler.findSimplePropertyValue(templateID, speciesPropID)
		
		if template_species and template_species != "":
			rHandler.addReagentProperty(insertID, speciesPropID, template_species)
		
		# Default status to "In Progress"
		statusPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["status"], prop_Category_Name_ID_Map["General Properties"])
		
		rHandler.addReagentProperty(insertID, statusPropID, "In Progress")
		
		# Modified May 14/08: At this point the first 2 steps of creation - parents and sequence - have been completed; now keep things simple - fill in the Insert's general info (mainly name and project), and proceed to Detailed View (can add features later through Editing)
		utils.redirect(hostname + "Reagent.php?View=2&Step=4&Type=" + rType + "&rID=" + `insertID` + "&Seq=" + `iSeqID` + "&SO=" + senseOligo + "&AS=" + antisenseOligo + "&PIV=" + insertParentVector)
		
		## Redirect to Modify view in creation mode								# removed May 14/08
		#utils.redirect(hostname + "Reagent.php?View=6&mode=Create&rType=" + rType + "&rid=" + `insertID`)	# removed May 14/08

	# May 5/08: Final creation step - save general info
	elif form.has_key("save_intro"):
		
		#print "Content-type:text/html"
		#print
		#print `form`
		
		rID = int(form.getvalue("reagent_id_hidden"))
		rType = form.getvalue("reagent_type_hidden")

		genPrefix = "reagent_detailedview_" + rType + "_"	# update Oct. 26/09 - reagent type included in feature names
		postfix = "_prop"

		## Create a new Insert instance
		#rID = rHandler.createNewReagent(rType)
		#reagent = rHandler.createReagent(rID)
		
		rTypeID = reagentType_Name_ID_Map[rType]
		
		# Find the actual names and database IDs of POST values (no checkbox properties for Oligos)
		newPropsDict_name = {}		# e.g. ('status', 'Completed')
		newPropsDict_id = {}		# e.g. ('3', 'Completed') - db ID instead of property name
		assocPropsDict = {}
		
		categories = {}
		
		#print `prop_Alias_ID_Map`

		# Find actual property values
		for propName_tmp in form.keys():
			#print propName_tmp
			
			# Extract propName portion
			start = propName_tmp.find(genPrefix)

			#print start

			if start >= 0:
				#print propName_tmp
				spacer_index = propName_tmp.find("_:_")
				#print spacer_index
				categoryAlias = propName_tmp[start+len(genPrefix):spacer_index]
				#print categoryAlias + "!!!"
				
				if prop_Category_Alias_ID_Map.has_key(categoryAlias):
					categoryID = prop_Category_Alias_ID_Map[categoryAlias]
					
					# Aug 6/09
					if categories.has_key(categoryAlias):
						tmp_cat_props = categories[categoryAlias]
					else:
						tmp_cat_props = []
					
					#print categoryAlias
					
					# update Jan. 20, 2010
					if propName_tmp.rfind(postfix) == len(propName_tmp)-len(postfix):
						end = len(propName_tmp) - len(postfix)
					else:
						continue
					
					#end = propName_tmp.rfind(postfix)
					
					#print end
					propName = propName_tmp[spacer_index+len("_:_"):end]
					
					#print "pname:" + propName + "!"
					
					tmp_cat_props.append(propName)
					categories[categoryAlias] = tmp_cat_props

					prop_id = prop_Alias_ID_Map[propName]
					propCatID = pHandler.findReagentPropertyInCategoryID(prop_id, categoryID)
					
					#print propCatID
	
					# new value that would be added to the set (dropdown/checkbox)
					newSetEntry = ""
					
					#print propName
					
					if propName == "alternate_id":
						newPropVal = form.getlist(propName_tmp)
						#print `newPropVal`
						tmp_alt_ids = []
						
						annoAlias = propName
						
						for altID in newPropVal:
							if form.has_key(annoAlias + "_" + altID + "_textbox_name"):
								if altID.lower() == 'other':
									tmp_alt_id = form.getvalue(annoAlias + "_" + altID + "_textbox_name")
									
									#print annoAlias
									#print annoPropID
									
									# update list
									pcID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[annoAlias], prop_Category_Name_ID_Map["External Identifiers"])
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
						#print `newPropVal`
						#newPropsDict_name[annoAlias] = newPropVal
						
					elif utils.isList(form[propName_tmp]):
						newPropVal = utils.unique(form.getlist(propName_tmp))
						
					else:
						newPropVal = form.getvalue(propName_tmp)
						
						# OK, this is for single dropdowns
						if newPropVal.lower() == 'other':
		
							# get the value in the textbox
							#textBox = propName + "_name_txt"
							textBox = propName_tmp[0:len(propName_tmp)-len(postfix)] + "_name_txt"
							newPropVal = form[textBox].value.strip()
							newSetEntry = newPropVal
		
							# invoke handler to add textbox value to dropdown list
							rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propCatID)
							
							# update Nov. 18/09
							setGroupID = sHandler.findPropSetGroupID(propCatID)	# it must exist
							
							ssetID = sHandler.findSetValueID(setGroupID, newSetEntry)
							
							if sHandler.findSetValueID(setGroupID, newSetEntry) <= 0:
								ssetID = sHandler.addSetValue(setGroupID, newSetEntry)
							
							if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
								sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)

						# May 20, 2010: Check for new list values here, and don't use isList to check; use isMultiple b/c even for multiple dropdowns, when only one value is added, it's not considered a list1!!!
						rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propCatID)

						if rtPropHandler.isMultiple(rTypeAttributeID):
							tmp_ar = []
							
							if not utils.isList(newPropVal):
								tmp_ar.append(newPropVal)
							else:
								tmp_ar = newPropVal
							
							for val in tmp_ar:
								
								# update set with new values
								setGroupID = sHandler.findPropSetGroupID(propCatID)
								ssetID = sHandler.findSetValueID(setGroupID, val)
								
								if sHandler.findSetValueID(setGroupID, val) <= 0:
									ssetID = sHandler.addSetValue(setGroupID, val)
								
								if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
									sHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)

					# store new property alias and value in dictionary
					newPropsDict_id[propCatID] = newPropVal

		# Match db IDs to property aliases
		for tmpProp in newPropsDict_name.keys():
			
			if prop_Alias_ID_Map.has_key(tmpProp):
				#propID = prop_Alias_ID_Map[tmpProp]
				propID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[tmpProp], prop_Category_Name_ID_Map["External Identifiers"])
				
				newPropsDict_id[propID] = newPropsDict_name[tmpProp]

		# ACTION
		# Correction May 20, 2011: No.  This deletes previously saved properties on multi-step creation
		#rHandler.updateReagentProperties(rID, newPropsDict_id)
		rHandler.updateReagentPropertiesInCategory(rID, prop_Category_Name_ID_Map["General Properties"], newPropsDict_id)
		
		# a few extra actions for Insert
		if rType == 'Insert':
			
			# May 14/08: Once Type of Insert and Open/Closed values have been set, translate this Insert's sequence
			iHandler.updateInsertProteinSequence(rID)
			
			# June 12/08: If Insert was created from Primer Design, assign the same project ID to the resulting Oligos
			if form.has_key("from_primer") and form.getvalue("from_primer") == 'True':
				
				insertAssoc = rHandler.findAllReagentAssociationsByName(rID)
				
				senseOligoID = insertAssoc["sense oligo"]
				#print `senseOligoID`
				antisenseOligoID = insertAssoc["antisense oligo"]
				#print `antisenseOligoID`
				
				packetPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["packet id"], prop_Category_Name_ID_Map["General Properties"])
				#print packetPropID
				
				#print genPrefix + prop_Category_Name_Alias_Map["General Properties"] + "_:_" + prop_Name_Alias_Map["packet id"] + postfix
				
				packetID = form.getvalue(genPrefix + prop_Category_Name_Alias_Map["General Properties"] + "_:_" + prop_Name_Alias_Map["packet id"] + postfix)
				#print `packetID`
				
				rHandler.addReagentProperty(senseOligoID, packetPropID, packetID)
				rHandler.addReagentProperty(antisenseOligoID, packetPropID, packetID)

			# Also, classifiers are passed here
			rHandler.updateReagentPropertiesInCategory(rID, prop_Category_Name_ID_Map["Classifiers"], newPropsDict_id)

			# and external identifiers, e.g. Alternate ID
			rHandler.updateReagentPropertiesInCategory(rID, prop_Category_Name_ID_Map["External Identifiers"], newPropsDict_id)

			# AND translate the sequence!!!!
			insertTypePropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["type of insert"], prop_Category_Name_ID_Map["Classifiers"])
			#print insertTypePropID
			
			openClosedPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["open/closed"], prop_Category_Name_ID_Map["Classifiers"])
			#print openClosedPropID
		
			newInsertType = iHandler.findSimplePropertyValue(rID, insertTypePropID)
			newOpenClosed = iHandler.findSimplePropertyValue(rID, openClosedPropID)

			# Update July 23/09: account for cases where insert type - open/closed are empty
			iHandler.updateInsertProteinSequence(rID, newInsertType, newOpenClosed)
	
		# Redirect to Modify view in creation mode
		utils.redirect(hostname + "Reagent.php?View=6&rid=" + `rID`)
	
	cursor.close()
	db.close()
	
create()
