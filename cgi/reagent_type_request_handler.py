#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()

import SocketServer
from SocketServer import BaseRequestHandler
#import urllib

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
#from system_set_handler import SystemSetHandler

from reagent_type_output import ReagentTypeOutputClass
from location_type_database_handler import LocationTypeHandler

from general_handler import *
from mapper import *

from session import Session

# Handlers and Mappers
dbConn = DatabaseConn()
db = dbConn.databaseConnect()
cursor = db.cursor()
hostname = dbConn.getHostname()

rtHandler = ReagentTypeHandler(db, cursor)
rtPropHandler = ReagentTypePropertyHandler(db, cursor)		# Aug. 31/09
pHandler = ReagentPropertyHandler(db, cursor)
uHandler = UserHandler(db, cursor)

propMapper = ReagentPropertyMapper(db, cursor)
rMapper = ReagentTypeMapper(db, cursor)

rtOut = ReagentTypeOutputClass()

reagentType_Name_ID_Map =  rMapper.mapTypeNameID()
reagentType_ID_Name_Map = rMapper.mapTypeIDName()

prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)
prop_Name_Alias_Map = propMapper.mapPropNameAlias()	# (propName, propAlias)

prop_ID_Name_Map = propMapper.mapPropIDName()		# Added March 13/08 - (prop id, prop name)
prop_ID_Alias_Map = propMapper.mapPropIDAlias()		

prop_Alias_Name_Map = propMapper.mapPropAliasName()	# March 18/08 - (propAlias, propName)
prop_Alias_ID_Map = propMapper.mapPropAliasID()		# (propAlias, propID) - e.g. ('insert_type', '48')
prop_Alias_Desc_Map = propMapper.mapPropAliasDescription()

prop_Desc_Alias_Map = propMapper.mapPropDescAlias()
prop_Desc_Name_Map = propMapper.mapPropDescName()
prop_Desc_ID_Map = propMapper.mapPropDescID()
prop_ID_Desc_Map = propMapper.mapPropIDDescription()

prop_Name_Desc_Map = propMapper.mapPropNameDescription()

# added June 3/09
category_Name_ID_Map = propMapper.mapPropCategoryNameID()
category_ID_Name_Map = propMapper.mapPropCategoryIDName()
category_Name_Alias_Map = propMapper.mapPropCategoryNameAlias()
category_Alias_ID_Map = propMapper.mapPropCategoryAliasID()

rtOut = ReagentTypeOutputClass()

aHandler = AssociationHandler(db, cursor)
raHandler = ReagentAssociationHandler(db, cursor)
rtAssocHandler = ReagentTypeAssociationHandler(db, cursor)

## Aug. 11/09: make globals??
#parents = []
#children = []

#####################################################################################
# Contains functions to handle reagent type creation, modification and deletion requests
#
# Written April 12, 2009, by Marina Olhovsky
# Last modified: April 12, 2009
#####################################################################################
class ReagentTypeRequestHandler:
	__db = None
	__cursor = None
	__hostname = ""
	
	##########################################################
	# Constructor
	##########################################################
	def __init__(self):
	
		self.__db = db
		self.__cursor = cursor
		self.__hostname = hostname
		
		
	##########################################################
	# Main action method
	##########################################################
	def handle(self):
		
		db = self.__db
		cursor = self.__cursor
		
		form = cgi.FieldStorage(keep_blank_values="True")
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		if form.has_key("curr_username"):
			currUname = form.getvalue("curr_username")
			currUser = uHandler.getUserByDescription(currUname)
			Session.setUser(currUser)
				
		elif form.has_key("curr_user_id"):
			currUID = form.getvalue("curr_user_id")
			currUser = uHandler.getUserByID(currUID)
			Session.setUser(currUser)
			
		if form.has_key("reagentTypeName"):
			rTypeName = form.getvalue("reagentTypeName")		# capitalize first letter????
		
		if form.has_key("reagentTypePrefix"):
			rTypePrefix = form.getvalue("reagentTypePrefix")	# should the prefix always be capitalized??
		
		if form.has_key("create_rtype"):
			if form.has_key("step"):
				step = form.getvalue("step")

				# Aug. 11/09: Save associations
				if form.has_key("addReagentTypeParents"):
					parents = form.getlist("addReagentTypeParents")
				else:
					parents = []
					
				#if form.has_key("addReagentTypeChildren"):
					#children = form.getlist("addReagentTypeChildren")

				if int(step) == 1:
					self.previewReagentTypePropertyValues(form)
					
				elif int(step) == 2:
					rTypeID = self.createReagentType(form)
					self.saveReagentTypeAssociations(rTypeID, rTypeName, parents)
			
					rtOut.printReagentTypeView(rTypeID)
		
		elif form.has_key("view_reagent_type"):
			rTypeName = form.getvalue("reagentType")
			rTypePrefix = rtHandler.findReagentTypePrefix(rTypeName)
			
			catDescrDict = {}	# categoryAlias, categoryDescription
			categoryProps = {}	# categoryAlias, rTypeProps (see below)
			
			rTypeID = reagentType_Name_ID_Map[rTypeName]
			rTypeAttributeIDs = rtPropHandler.findAllReagentTypeAttributes(rTypeID)
			#print `rTypeAttributeIDs`
			
			#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
			#print
			
			#for attrID in rTypeAttributeIDs:	# update Aug. 5/09
			for aKey in rTypeAttributeIDs:
				#print aKey
				attrID = rTypeAttributeIDs[aKey]
				#print attrID
				categoryID = pHandler.findReagentPropertyCategory(attrID)
				
				if category_ID_Name_Map.has_key(categoryID):
					category = category_ID_Name_Map[categoryID]
					#print "Category " + category
					catAlias = category_Name_Alias_Map[category]
					#print catAlias
					catDescrDict[catAlias] = category
					cp = pHandler.findReagentPropertyInCategory(attrID)
					#print cp
					#print `cat_propList`
					
					#print "Property " + cAlias
					
					if categoryProps.has_key(catAlias):
						cat_propList = categoryProps[catAlias]
					else:
						cat_propList = []
					
					if prop_ID_Alias_Map.has_key(cp):
						cAlias = prop_ID_Alias_Map[cp]
						cat_propList.append(cAlias)
					# this would happen only in case of incomplete db deletion!
					#else:
						#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
						#print
						
					#print `cat_propList`
					
					categoryProps[catAlias] = cat_propList
					#print `categoryProps`

			#rtOut.printReagentTypeView(rTypeName, rTypePrefix, catDescrDict, categoryProps)
			rtOut.printReagentTypeView(rTypeID)
			
		elif form.has_key("modify_rtype"):
			
			# Go to "Modify Reagent Type" page
			rTypeName = form.getvalue("reagentType")
			rTypePrefix = rtHandler.findReagentTypePrefix(rTypeName)
			
			catDescrDict = {}	# categoryAlias, categoryDescription
			categoryProps = {}	# categoryAlias, rTypeProps (see below)
			
			rTypeID = reagentType_Name_ID_Map[rTypeName]
			rTypeAttributeIDs = rtPropHandler.findAllReagentTypeAttributes(rTypeID)
			#print `rTypeAttributeIDs`

			if form.has_key("reagentTypeParents"):
				parents = form.getlist("reagentTypeParents")
			else:
				parents = []

			for aKey in rTypeAttributeIDs.keys():
				attrID = rTypeAttributeIDs[aKey]	# this is propCatID
				#print attrID
				categoryID = pHandler.findReagentPropertyCategory(attrID)
				
				if category_ID_Name_Map.has_key(categoryID):
					category = category_ID_Name_Map[categoryID]
					#print "Category " + category
					catAlias = category_Name_Alias_Map[category]
					catDescrDict[catAlias] = category
					cp = pHandler.findReagentPropertyInCategory(attrID)
					#print cp
					#print `cat_propList`
					if prop_ID_Alias_Map.has_key(cp):
						cAlias = prop_ID_Alias_Map[cp]		# gave error here on 'pralias issue' reagentt type, check if others
						#print "Property " + cAlias
						
						if categoryProps.has_key(catAlias):
							cat_propList = categoryProps[catAlias]
						else:
							cat_propList = []
						
						#print cAlias + " not used"
						cat_propList.append(cAlias)
		
						#print `cat_propList`
						categoryProps[catAlias] = cat_propList
				
			#print `categoryProps`
			rtOut.printReagentTypeCreationPage(rTypeName, rTypePrefix, 1, catDescrDict, categoryProps, parents, 0)

		elif form.has_key("save_rtype"):
			self.updateReagentType(form)

		elif form.has_key("delete_rtype"):
			self.deleteReagentType(form)
			utils.redirect(hostname + "Reagent.php?View=5&Del=1")
			
		elif form.has_key("cancel_rtype_create"):
			utils.redirect(hostname + "Reagent.php?View=2")
			
		elif form.has_key("cancel_rtype_modify"):
			rTypeName = form.getvalue("reagentTypeName")
			rTypeID = reagentType_Name_ID_Map[rTypeName]
			rtOut.printReagentTypeView(rTypeID)
		
		cursor.close()
		db.close()
	
	
	def saveReagentTypeAssociations(self, rTypeID, rTypeName, parents):
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `parents`
		
		aTypeID = rtAssocHandler.findAssociationByReagentType(rTypeID)
		
		if aTypeID <= 0:
			# create AType_tbl entry
			aHandler.createAssociation(rTypeName)	# just plain and simple store this reagent type name as association descriptor
			
		for pType in parents:
			if reagentType_Name_ID_Map.has_key(pType):
				pTypeID = reagentType_Name_ID_Map[pType]
				
			elif pType == rTypeName:
				# nov. 24/09: include the reagent type currently being saved!!!!!!!!!!
				pTypeID = rTypeID
			
			assocName = "parent " + pType.lower()
			alias = "parent_" + pType.lower() + "_id"
			description = "Parent " + pType + " ID"
		
			# also check if not exist
			rtAssocHandler.addReagentTypeAssociationPropertyValue(rTypeID, assocName, alias, description, pTypeID)


	def deleteReagentTypeAssociations(self, rTypeID):
		
		db = self.__db
		cursor = self.__cursor
		
		#rTypeID = reagentType_Name_ID_Map[rTypeName]
		
		# Jan. 28/10: this code deletes this reagent type as a child of other types
		aTypeID = rtAssocHandler.findAssociationByReagentType(rTypeID)
		assocProps = rtAssocHandler.findReagentTypeAssocProps(rTypeID)
		
		for aPropID in assocProps.keys():
			aHandler.deleteAssociationProperty(aPropID)

		# Jan. 28/10: also need to delete this rtype as parent of other rtypes
		aHandler.deleteReagentTypeAssociations(rTypeID)
		aHandler.deleteAssociation(aTypeID)


	def updateReagentType(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		rtHandler = ReagentTypeHandler(db, cursor)
		pHandler = ReagentPropertyHandler(db, cursor)
		rtAssocHandler = ReagentTypeAssociationHandler(db, cursor)
		
		sHandler = SystemSetHandler(db, cursor)
		
		propMapper = ReagentPropertyMapper(db, cursor)
		rtAssocMapper = ReagentAssociationMapper(db, cursor)

		prop_Alias_ID_Map = propMapper.mapPropAliasID()		# (propAlias, propID) - e.g. ('insert_type', '48')
		category_Alias_ID_Map = propMapper.mapPropCategoryAliasID()
		
		#rType_assoc_ID_Parent_Map = rtAssocMapper.mapAssocIDParentType()

		rtOut = ReagentTypeOutputClass()
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		catDescrDict = {}	# categoryAlias, categoryDescription
		categoryProps = {}	# categoryAlias, rTypeProps (see below)
		
		rTypeName = form.getvalue("reagentTypeName")
		old_rTypePrefix = form.getvalue("reagentTypePrefix")

		if reagentType_Name_ID_Map.has_key(rTypeName):
			rTypeID = reagentType_Name_ID_Map[rTypeName]
		else:
			print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
			print
			print "Error: Unknown reagent type " + rTypeName
		
		
		# Nov. 20-23/09: PARENTS
		# NOTE: Disabled options are NOT submitted.  I.e. if for Inserts have 'RNAi', 'Vector' and 'Oligo' parents, and want to remove RNAi so that only V+O remain - since V and O options are disabled, the select list is NOT submitted; hence, the 'delete' statement is never called and RNAi remains in the list.  Modify display code to enable all options at submission.
		if form.has_key("add_reagent_type_parents"):
			parentList = form.getlist("add_reagent_type_parents")
			#print "parents " + `parentList`

			rTypeAssocParents = rtAssocHandler.findReagentAssociationParentTypes(reagentType_Name_ID_Map[rTypeName])

			for assocTypeID in rTypeAssocParents.keys():
				pTypeID = rTypeAssocParents[assocTypeID]
				
				if not rtAssocHandler.isUsedReagentTypeAssociation(reagentType_Name_ID_Map[rTypeName], pTypeID):
					aHandler.deleteAssociationProperty(assocTypeID)

			self.saveReagentTypeAssociations(rTypeID, rTypeName, parentList)

			#if len(parentList) == 0:
				#self.deleteReagentTypeAssociations(rTypeName)
			#else:
					
				#for parentType in parentList:
					#print parentType

					#assocTypeID = rtAssocHandler.findParentAssocType(reagentType_Name_ID_Map[rTypeName], reagentType_Name_ID_Map[parentType])

					#if not rtAssocHandler.isUsedReagentTypeAssociation(reagentType_Name_ID_Map[rTypeName], reagentType_Name_ID_Map[parentType]):
						##self.deleteReagentTypeAssociations(rTypeName)
						#aHandler.deleteAssociationProperty(assocTypeID)

				#self.saveReagentTypeAssociations(rTypeID, rTypeName, parentList)
	
		# Sept. 4/09: NO - do NOT allow changing reagent type name - see comments file
		#if form.has_key("newReagentTypeName"):
			#rTypeName = form.getvalue("newReagentTypeName")
		
			#if old_rTypeName != rTypeName:
				## change rtypename
				#rtHandler.setReagentTypeName(rTypeID, rTypeName)
		#else:
			#rTypeName = old_rTypeName
			
		if form.has_key("newReagentTypePrefix"):
			rTypePrefix = form.getvalue("newReagentTypePrefix")
			
			if old_rTypePrefix != rTypePrefix:
				# change rtypePrefix
				rtHandler.setReagentTypePrefix(rTypeID, rTypePrefix)
		else:
			rTypePrefix = old_rTypePrefix

		if form.has_key("addReagentTypeParents"):
			parents = form.getlist("addReagentTypeParents")
		else:
			parents = []
		
		if form.has_key("addReagentTypeChildren"):
			children = form.getlist("addReagentTypeChildren")

		#print `catDescrDict`

		# June 11/09: Select only one sequence type
		sequenceTypeCategoryAliases = {"DNA Sequence":["sequence_properties", "dna_sequence_features"], "Protein Sequence":["protein_sequence_properties", "protein_sequence_features"], "RNA Sequence":["rna_sequence_properties", "rna_sequence_features"]}
	
		ignoreSequenceAttributes = []
		
		if form.has_key("sequenceType"):
			sequenceType = form.getvalue("sequenceType")
			
			for st in sequenceTypeCategoryAliases.keys():
				if st != sequenceType:
					ignore_props_list = sequenceTypeCategoryAliases[st]
					ignoreSequenceAttributes += ignore_props_list

		step = form.getvalue("step")
		
		#print step

		if step == '1':
			if form.has_key("category[]"):
				categories = form.getlist("category[]")
				
				#print "Content-type:text/html"
				#print
				#print `categories`
				
				for category in categories:	# this is an alias, e.g. "general_properties" => 'General Properties'
					rTypeProps = {}		# propAlias, propDescr
					
					category = category.replace("'", "\\'")
					
					#print "CATEGORY " + category
					
					if (len(ignoreSequenceAttributes) == 0) or (len(ignoreSequenceAttributes) > 0 and category not in ignoreSequenceAttributes):
						#print "?????????????Category " + category
						
						#print "what's this: " + "category_descriptor_" + category
						
						#print `form.has_key("category_descriptor_" + category)`
						
						# April 15, 2009
						categoryDescriptor = form.getvalue("category_descriptor_" + category)
						#print "???" + categoryDescriptor + "!!"
			
						# Update Nov. 30/09: Now novel properties are prefixed by "createReagentType_" but old properties are not
						checkboxAliases = form.getlist("createReagentType_" + category+"[]") + form.getlist(category+"[]")
						
						# Aug. 13/09: Don't save empty categories
						if len(checkboxAliases) > 0:
							catDescrDict[category] = categoryDescriptor
			
						#print `checkboxAliases`
						
						for cbAlias in checkboxAliases:
							#print "Alias " + cbAlias
							
							cbAlias = cbAlias.strip()
							
							if cbAlias == 'type':
								# prepend reagent type name to it
								pDescr = rTypeName + " Type"
								pAlias = string.join(rTypeName.split(" "), "_") + "_type"
							
							elif form.has_key("createReagentType_" + category + "_:_" + cbAlias):
								tmpDescr = form.getvalue("createReagentType_" + category + "_:_" + cbAlias)
								
								if pHandler.findPropertyDescriptionFromAlias(cbAlias) > 0:
									pAlias = cbAlias
									pDescr = pHandler.findPropertyDescriptionFromAlias(pAlias)
									pName = pDescr.lower()
								
								elif pHandler.findPropertyAliasFromDescription(tmpDescr):
									pAlias = pHandler.findPropertyAliasFromDescription(tmpDescr)
									pDescr = tmpDescr
									pName = pDescr.lower()
									
								#elif form.has_key(category + "_:_" + cbAlias):
									#pAlias = cbAlias
									#pDescr = form.getvalue(category + "_:_" + cbAlias)
									#pName = pDescr.lower()
								
								else:
									pAlias = cbAlias
									pDescr = tmpDescr
									pName = pDescr.lower()
									
									#print "Content-type:text/html"
									#print
									#print "Unknown alias: " + pAlias
								
							# Existing properties
							elif pHandler.findPropertyDescriptionFromAlias(cbAlias) > 0:
								pDescr = pHandler.findPropertyDescriptionFromAlias(cbAlias)
								pName = pDescr.lower()
								pAlias = cbAlias
							
							elif cbAlias.find(category + "_:_") == 0:
								# Existing properties
								pAlias = cbAlias[len(category + "_:_"):]
								#print "pAlias " + pAlias
								#print category
								#print category + "_:_" + pAlias
								
								if form.has_key(category + "_:_" + pAlias):
									pDescr = form.getvalue(category + "_:_" + pAlias)
									#print pDescr
									
									# Nov. 2/09: This is a new property - BUT check if it exists in the database; if yes grab its alias
									if pHandler.findPropertyAliasFromDescription(pDescr):
										pAlias = pHandler.findPropertyAliasFromDescription(pDescr)
									
								elif pHandler.findPropertyDescriptionFromAlias(pAlias) > 0:
									pDescr = pHandler.findPropertyDescriptionFromAlias(pAlias)
									#pDescr = pHandler.findPropertyDescription(prop_Alias_ID_Map[pAlias])
									#pDescr = prop_ID_Desc_Map[prop_Alias_ID_Map[pAlias]]
									
								else:
									print "Content-type:text/html"
									print
									print "Unknown alias: " + pAlias

								if prop_Alias_ID_Map.has_key(pAlias):
								#if pHandler.findPropertyIDFromAlias(pAlias, True):
									#pID = pHandler.findPropertyIDFromAlias(pAlias, True)
									pID = prop_Alias_ID_Map[pAlias]
									#pDescr = pHandler.findPropertyDescription(pID)
									pDescr = prop_ID_Desc_Map[pID]
								else:
									pDescr = form.getvalue(category + "_:_" + pAlias)
									#print `pDescr`
								
								#print "Description " + `pDescr`
							else:
								print "Content-type:text/html"
								print
								print "Unknown alias: " + cbAlias
							
							#else:
								## Novel properties
								#if form.has_key(category + "_:_" + cbAlias):
									#pDescr = form.getvalue(category + "_:_" + cbAlias)
									##pName = pDescr.lower()
									#pAlias = cbAlias
									
								##print cbAlias
								##print `prop_Alias_ID_Map[cbAlias]`
								
								#elif prop_Alias_ID_Map.has_key(cbAlias):
									#pID = prop_Alias_ID_Map[cbAlias]
									#pDescr = prop_ID_Desc_Map[pID]
									#pAlias = cbAlias
									
								#else:
									#print "Content-type:text/html"
									#print
									#print "Unknown alias: " + cbAlias
							
							# added check for list June 18/09 - catch duplicates
							if utils.isList(pDescr):
								for desc in pDescr:
									pName = desc.lower()
									
									# use one dictionary for all props - June 17/09 update: don't store duplicate values
									if not desc in rTypeProps.values():
										rTypeProps[pAlias] = desc
							else:
								pName = pDescr.lower()
								#print "Name " + pName
				
								# use one dictionary for all props - June 17/09 update: don't store duplicate values
								if not pDescr in rTypeProps.values():
									rTypeProps[pAlias] = pDescr
						
						#print "Category " + category + ", description " + pDescr
						#print `rTypeProps`
						
						#print "Description " + pDescr
						
						# check again for non-empty props list
						if len(rTypeProps) > 0:
							categoryProps[category] = rTypeProps
				
				if len(categoryProps) > 0:
					rtOut.printReagentTypeCreationPage(rTypeName, rTypePrefix, 3, catDescrDict, categoryProps, parents)
				else:
					rtOut.printReagentTypeView(rTypeID)

		elif step == '3':
			
			#print "Content-type:text/html"
			#print
			#print `form`
			
			# For new properties, save predefined values
			cat_newProps = form.getlist("newPropName")
			all_newProps = {}
	
			for c_prop in cat_newProps:
				pList = []
	
				if c_prop.find("_:_") >= 0:
					tmpStart = c_prop.index("_:_")
					tmpEnd = tmpStart + len("_:_")
					tmpCat = c_prop[0:tmpStart]
					newProp = c_prop[tmpEnd:]
					
					#print newProp
					
					if all_newProps.has_key(tmpCat):
						pList = all_newProps[tmpCat]
						
						if newProp not in pList:
							pList.append(newProp)
							all_newProps[tmpCat] = pList
					else:
						pList = []
						pList.append(newProp)
						all_newProps[tmpCat] = pList
				
			#print "New: " + `all_newProps`
			propsToRemove = []
			
			# Feb. 1/10: do removal here, doesn't work otherwise... Feb. 3/10: well, no, these may be NEW props/categories...
			for cat_alias in category_Alias_ID_Map.keys():
				cat_id = category_Alias_ID_Map[cat_alias]
				allCatPropIDs = pHandler.findPropertiesByCategory(cat_id)
				
				for pID in allCatPropIDs:
					pAlias = prop_ID_Alias_Map[pID]
					
					if form.has_key("remove_" + cat_alias + "_:_" + pAlias + "_prop"):
						rmvFlag = form.getvalue("remove_" + cat_alias + "_:_" + pAlias + "_prop")
						
						if rmvFlag == "1":
							propsToRemove.append(pAlias)
							pcID = pHandler.findReagentPropertyInCategoryID(pID, cat_id)
							rtPropHandler.deleteReagentTypeAttribute(rTypeID, pcID)
	
			#print `propsToRemove`
	
			# Save remaining properties as attributes of this reagent type
			if form.has_key("category[]"):
				categories = form.getlist("category[]")
				#print "All categories: " + `categories`
				
				for category in categories:	# this is an alias, e.g. "general_properties" => 'General Properties'
					#print category
					
					if all_newProps.has_key(category):
						newProps = all_newProps[category]
					else:
						newProps = []

					#print "New props " + `newProps`
					
					# Jan. 26/10: need to record special info for features, e.g. colour
					if category == category_Name_Alias_Map["DNA Sequence Features"] or category == category_Name_Alias_Map["RNA Sequence Features"] or category == category_Name_Alias_Map["Protein Sequence Features"]:
						isSeqFeature = True
					else:
						isSeqFeature = False
					
					categoryDescriptor = form.getvalue("category_descriptor_" + category)
					
					if not pHandler.existsReagentPropertyCategory(categoryDescriptor) or not category_Alias_ID_Map.has_key(category):
						categoryID = pHandler.addReagentPropertyCategory(categoryDescriptor, category)
					else:
						categoryID = category_Alias_ID_Map[category]
					
					#print `categoryID`
					
					propAliases = form.getlist(category+"[]")
					#print `propAliases`
					
					#print `newProps`
					
					for pAlias in propAliases:
						#print pAlias
						
						if pAlias in propsToRemove:
							continue
						
						if form.has_key("remove_" + category + "_:_" + pAlias + "_prop"):
							if form.getvalue("remove_" + category + "_:_" + pAlias + "_prop") == "1":
								continue
							
						#if pAlias.lower() == prop_Name_Alias_Map["tag type"]:
							#propAliases.append("tag_position")
							
						#if pAlias.lower() == prop_Name_Alias_Map["promoter"]:
							#propAliases.append("expression_system")
						#removeFlag = form.getvalue("remove_" + category + "_:_" + pAlias + "_prop")
						
						if pAlias == rTypeName + "_type":
							# prepend reagent type name to it
							#pAlias = rTypeName + "_type"
							pName = rTypeName + " type"
							pDescr = rTypeName + " Type"
							
							newPropAlias = category + "_:_" + pAlias
							
							#if removeFlag == "1":
								#propCatID = pHandler.findReagentPropertyInCategoryID(newPropID, categoryID)
								
								#if propCatID > 0:
									#rtPropHandler.deleteReagentTypeAttribute(rTypeID, propCatID)
								## otherwise don't do anything
								#continue
							
							# 'if exists' added June 17/09
							if not pHandler.existsReagentPropertyType(pName):
								# correction June 22/09: don't include category in argument list
								# (obviously this is not a feature, no need to set parameter - jan. 26/10)
								newPropID = pHandler.addReagentPropertyType(pName, pAlias, pDescr)
							else:
								newPropID = pHandler.findPropID(pName, True)
								#newPropID = prop_ID_Name_Map[pName]
							
							# June 27/09: set ordering to place 'type' after Name, Status and Project ID on the detailed view
							#pHandler.setPropertyOrdering(newPropID, 4)
							
							# added June 17/09
							if not pHandler.existsPropertyInCategory(newPropID, categoryID):
								propCatID = pHandler.addReagentPropertyToCategory(newPropID, categoryID)
							else:
								propCatID = pHandler.findReagentPropertyInCategoryID(newPropID, categoryID)
	
							# 'if exists' added June 17/09
							if not rtPropHandler.existsReagentTypeAttribute(rTypeID, propCatID):
								rTypeAttrID = rtPropHandler.addReagentTypeAttribute(rTypeID, propCatID)
							else:
								rTypeAttrID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propCatID)
	
							# April 9, 2010
							if propCatID > 0 and form.has_key("propOrder_" + `propCatID`):
								pOrder = form.getvalue("propOrder_" + `propCatID`)
								#print "Setting order " + `pOrder` + " for " + pAlias
								rtPropHandler.setReagentTypeAttributeOrdering(rTypeAttrID, pOrder)
							else:
								#print "HERE!!! this is a new property!!"
								tmp_prop_cat_alias = category + "_:_" + pAlias
								
								if form.has_key("propOrder_" + tmp_prop_cat_alias):
									pOrder = form.getvalue("propOrder_" + tmp_prop_cat_alias)
									rtPropHandler.setReagentTypeAttributeOrdering(rTypeAttrID, pOrder)

							# update list for multiple values
							numVals = form.getvalue("num_vals_" + newPropAlias)
							
							comms = categoryDescriptor + " " + pName
							
							if not sHandler.existsSetGroup(propCatID):
								ssetGroupID = sHandler.addSetGroupID(propCatID, comms)
							else:
								ssetGroupID = sHandler.findPropSetGroupID(propCatID)
							
							sHandler.deleteReagentTypeAttributeSetValues(rTypeAttrID)
							
							#print numVals
							
							if numVals == 'predefined':		# dropdown list of values
								#print "predef " + newPropAlias
								
								# May 28/10: only if list allow multiple and other!!!
								# Apr. 29, 2010: Hyperlinks and multiples
								if form.has_key("isMultiple_" + newPropAlias):
									rtPropHandler.makeMultiple(rTypeAttrID)
								else:
									rtPropHandler.makeSingle(rTypeAttrID)
								
								if form.has_key("allowOther_" + newPropAlias):
									rtPropHandler.makeCustomizeable(rTypeAttrID)
								else:
									rtPropHandler.removeCustomizeable(rTypeAttrID)
							
								# do this to change free-text to dropdown!!!!
								rtPropHandler.removeHyperlink(rTypeAttrID)
							
								propVals = form.getlist("propertyValues_" + newPropAlias)
								#print `propVals`
								
								for pVal in propVals:
									if pVal != 'default':
										#print pVal
										#sHandler.updateSet(pName, pVal)
										#setComms = pDescr
										#sHandler.updateSet(rTypeAttrID, setComms, pVal)
										sID = sHandler.findSetValueID(ssetGroupID, pVal)
										
										if sID <= 0:
											sID = sHandler.addSetValue(ssetGroupID, pVal)
										
										if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttrID, sID):
											sHandler.addReagentTypeAttributeSetEntry(rTypeAttrID, sID)
							else:
								if form.has_key("hyperlink_" + newPropAlias):
									rtPropHandler.makeHyperlink(rTypeAttrID)
								else:
									rtPropHandler.removeHyperlink(rTypeAttrID)
	
								# convert from d/d to f/t
								rtPropHandler.removeCustomizeable(rTypeAttrID)
								rtPropHandler.makeSingle(rTypeAttrID)
	
						elif pAlias not in newProps:
							#print "Alias " + pAlias
	
							# think I've stripped category from the alias already but test more to make sure
							if pAlias.find(category + "_:_") == 0:
								pAlias = pAlias[len(category + "_:_"):]

							# June 14, 2010: Ok, try to make alias lowercase - pray this doesn't break anything
							pAlias = pAlias.lower()
							
							tmpPropID = pHandler.findPropertyIDFromAlias(pAlias, True)
							
							#print "alias " + pAlias + ", propID " + `tmpPropID`

							# change Oct. 31/09
							#pDescr = prop_Alias_Desc_Map[pAlias]
							pDescr = pHandler.findPropertyDescriptionFromAlias(pAlias, True)
							
							#if not pDescr:
								#pDescr = pAlias
							
							#print pDescr
							
							# June 15/09: Now that we allow property names to be shared between categories, need to specify the category when checking if a property exists in the database.  Check if this property exists specifically under our category; if not, insert as a new ReagentPropType_tbl entry
							if not pHandler.existsPropertyInCategory(tmpPropID, categoryID):
								#print "Adding property " + pAlias + " to category " + `categoryID`
								propCatID = pHandler.addReagentPropertyToCategory(tmpPropID, categoryID)
							else:
								propCatID = pHandler.findReagentPropertyInCategoryID(tmpPropID, categoryID)

							#print propCatID
							
							# See if this property needs to be deleted
							#removeFlag = form.getvalue("remove_" + (category + "_:_" + pAlias) + "_prop")
							#print removeFlag + " remove " + category + "_:_" + pAlias
							
							#if removeFlag == "1":
								##print removeFlag + " remove " + category + "_:_" + pAlias
								#rtPropHandler.deleteReagentTypeAttribute(rTypeID, propCatID)
							
							#if pAlias in propsToRemove:
								#continue
							
							if not rtPropHandler.existsReagentTypeAttribute(rTypeID, propCatID):
								rTypeAttrID = rtPropHandler.addReagentTypeAttribute(rTypeID, propCatID)
							else:
								rTypeAttrID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propCatID)
							
							# April 9, 2010
							if propCatID > 0 and form.has_key("propOrder_" + `propCatID`):
								pOrder = form.getvalue("propOrder_" + `propCatID`)
								rtPropHandler.setReagentTypeAttributeOrdering(rTypeAttrID, pOrder)
							else:
								tmp_prop_cat_alias = category + "_:_" + pAlias
								
								if form.has_key("propOrder_" + tmp_prop_cat_alias):
									pOrder = form.getvalue("propOrder_" + tmp_prop_cat_alias)
									rtPropHandler.setReagentTypeAttributeOrdering(rTypeAttrID, pOrder)

							oldInputFormat = rtPropHandler.getAttributeInputFormat(rTypeAttrID)
							
							#print oldInputFormat

							#print `rTypeAttrID` + "??"
							
							# Still have to update dropdown set
							numVals = form.getvalue("num_vals_" + (category + "_:_" + pAlias).replace("'", "\\'"))
							
							#print pAlias + " " + `numVals`
							#print pDescr
							
							comms = categoryDescriptor + " " + pDescr

							if numVals == 'predefined':		# dropdown list of values
								
								if not sHandler.existsSetGroup(propCatID):
									ssetGroupID = sHandler.addSetGroupID(propCatID, comms)
								else:
									ssetGroupID = sHandler.findPropSetGroupID(propCatID)
								
								#print ssetGroupID
								
								sHandler.deleteReagentTypeAttributeSetValues(rTypeAttrID)

								propVals = form.getlist("propertyValues_" + (category + "_:_" + pAlias).replace("'", "\\'"))
								
								for pVal in propVals:
									sID = sHandler.findSetValueID(ssetGroupID, pVal)
									
									if sID <= 0:
										sID = sHandler.addSetValue(ssetGroupID, pVal)
									
									if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttrID, sID):
										sHandler.addReagentTypeAttributeSetEntry(rTypeAttrID, sID)
								
								# moved here on May 28/10: do this intelligently!
								
								# customizable
								if form.has_key("allowOther_" + category + "_:_" + pAlias):
									rtPropHandler.makeCustomizeable(rTypeAttrID)
								else:
									rtPropHandler.removeCustomizeable(rTypeAttrID)
								
								# Apr. 29, 2010: Hyperlinks and multiples
								if form.has_key("isMultiple_" + category + "_:_" + pAlias):
									rtPropHandler.makeMultiple(rTypeAttrID)
								else:
									rtPropHandler.makeSingle(rTypeAttrID)
								
								rtPropHandler.removeHyperlink(rTypeAttrID)

							else:
								# Freetext; check if change from dropdown
								if numVals != oldInputFormat:
									ssetGroupID = sHandler.findPropSetGroupID(propCatID)
									
									# change dropdown to freetext
									sHandler.deleteReagentTypeAttributeSetValues(rTypeAttrID)
									
								if form.has_key("hyperlink_" + category + "_:_" + pAlias):
									#print "MAKING HYPERLINK"
									rtPropHandler.makeHyperlink(rTypeAttrID)
								else:
									rtPropHandler.removeHyperlink(rTypeAttrID)
									
								# remove dropdown attributes
								rtPropHandler.makeSingle(rTypeAttrID)
								rtPropHandler.removeCustomizeable(rTypeAttrID)

						else:
							#print category
							tmp_new_props = all_newProps[category]
							#print `tmp_new_props`
							
							for tmp_alias in tmp_new_props:
								#newPropAlias = pAlias
								
								# Final decision Nov. 19/09: Make LOWERCASE
								newPropAlias = tmp_alias.lower()
								
								if form.has_key("newPropDescr_" + category + "_:_" + tmp_alias):
									descriptions = form.getvalue("newPropDescr_" + category + "_:_" + tmp_alias)	# is this a list?
									#print `descriptions`
									
									if utils.isList(descriptions):
										for pDescr in descriptions:
											# Final decision Nov. 19/09: Make LOWERCASE
											pName = pDescr.lower()
									else:
										pDescr = descriptions
										
										# Final decision Nov. 19/09: Make LOWERCASE
										pName = pDescr.lower()
								else:
									pDescr = newPropAlias
									
									if pDescr.find(category + "_:_" + newPropAlias + "_:_") == 0:
										pDescr = pDescr[len(category + "_:_" + newPropAlias + "_:_"):]
										
									# Final decision Nov. 19/09: Make LOWERCASE
									pName = pDescr.lower()
	
								if not pHandler.existsReagentPropertyType(pName):
									# correction June 22/09: don't include category in arguments list
									newPropID = pHandler.addReagentPropertyType(pName, newPropAlias, pDescr, isSeqFeature)
								else:
									newPropID = pHandler.findPropID(pName)

								#print newPropID
								
								tmpCatID = pHandler.findReagentPropertyInCategoryID(newPropID, categoryID)
								
								if tmpCatID <= 0:
									tmpCatID = pHandler.addReagentPropertyToCategory(newPropID, categoryID)
									
								#print tmpCatID
								
								# isn't the check for existence redundant?  this is a new property!
								if not rtPropHandler.existsReagentTypeAttribute(rTypeID, tmpCatID):
									rTypeAttrID = rtPropHandler.addReagentTypeAttribute(rTypeID, tmpCatID)
								else:
									rTypeAttrID = rtPropHandler.findReagentTypeAttributeID(rTypeID, tmpCatID)
									
								#print rTypeAttrID
								
								#print propCatID
								
								if tmpCatID > 0 and form.has_key("propOrder_" + `tmpCatID`):
									pOrder = form.getvalue("propOrder_" + `tmpCatID`)
									#print "Setting order " + `pOrder` + " for " + pAlias
									rtPropHandler.setReagentTypeAttributeOrdering(rTypeAttrID, pOrder)
								else:
									#print "HERE!!! this is a new property!!"
									tmp_prop_cat_alias = category + "_:_" + tmp_alias
									
									if form.has_key("propOrder_" + tmp_prop_cat_alias):
										pOrder = form.getvalue("propOrder_" + tmp_prop_cat_alias)
										rtPropHandler.setReagentTypeAttributeOrdering(rTypeAttrID, pOrder)

								# update list for multiple values
								numVals = form.getvalue("num_vals_" + category + "_:_" + tmp_alias)	# THIS IS RIGHT
								#print `numVals`
								
								if numVals == 'predefined':		# dropdown list of values
									
									# moved here May 28/10: do this intelligently
									
									# Apr. 29, 2010: Hyperlinks and multiples
									if form.has_key("isMultiple_" + tmp_prop_cat_alias):
										#print "MAKING MULTIPLE"
										rtPropHandler.makeMultiple(rTypeAttrID)
									else:
										rtPropHandler.makeSingle(rTypeAttrID)
										
									if form.has_key("allowOther_" + tmp_prop_cat_alias):
										rtPropHandler.makeCustomizeable(rTypeAttrID)
									else:
										rtPropHandler.removeCustomizeable(rTypeAttrID)
									
									rtPropHandler.removeHyperlink(rTypeAttrID)
									
									propVals = form.getlist("propertyValues_" + category + "_:_" + tmp_alias)
									
									comms = categoryDescriptor + " " + pName
									
									if not sHandler.existsSetGroup(tmpCatID):
										ssetGroupID = sHandler.addSetGroupID(tmpCatID, comms)
									else:
										ssetGroupID = sHandler.findPropSetGroupID(tmpCatID)
									
									sHandler.deleteReagentTypeAttributeSetValues(rTypeAttrID)
			
									#print `propVals`
									for pVal in propVals:
										if pVal != 'default':
											#print pVal
											#setComms = rTypeName + " " + pDescr
											#print setComms
											#sHandler.updateSet(rTypeAttrID, setComms, pVal)
											sID = sHandler.findSetValueID(ssetGroupID, pVal)
										
											if sID <= 0:
												sID = sHandler.addSetValue(ssetGroupID, pVal)
											
											if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttrID, sID):
												sHandler.addReagentTypeAttributeSetEntry(rTypeAttrID, sID)
								# Added May 28, 2010
								else:
									if form.has_key("hyperlink_" + tmp_prop_cat_alias):
										#print "MAKING HYPERLINK"
										rtPropHandler.makeHyperlink(rTypeAttrID)
									else:
										rtPropHandler.removeHyperlink(rTypeAttrID)
				
									rtPropHandler.removeCustomizeable(rTypeAttrID)
									rtPropHandler.makeSingle(rTypeAttrID)

			rtOut.printReagentTypeView(rTypeID)

	# Let user define property input format
	def previewReagentTypePropertyValues(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		rtHandler = ReagentTypeHandler(db, cursor)
		pHandler = ReagentPropertyHandler(db, cursor)
		
		propMapper = ReagentPropertyMapper(db, cursor)
		
		rtOut = ReagentTypeOutputClass()
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		prop_Alias_ID_Map = propMapper.mapPropAliasID()			# (propAlias, propID) - e.g. ('insert_type', '48')
		category_Alias_ID_Map = propMapper.mapPropCategoryAliasID()
		
		catDescrDict = {}	# categoryAlias, categoryDescription
		categoryProps = {}	# categoryAlias, rTypeProps (see below)
		
		if form.has_key("reagentTypeName"):
			rTypeName = form.getvalue("reagentTypeName")		# capitalize first letter????  NO!!!
		
		if form.has_key("reagentTypePrefix"):
			# should the prefix always be capitalized?? --> Karen: YES --> Marina Oct. 22/09: NO.  What if want "Ri" prefix for RNAi??  Don't capitalize!!
			rTypePrefix = form.getvalue("reagentTypePrefix")
	
		# June 11/09: Select only one sequence type
		sequenceTypeCategoryAliases = {"DNA Sequence":["sequence_properties", "dna_sequence_features"], "Protein Sequence":["protein_sequence_properties", "protein_sequence_features"], "RNA Sequence":["rna_sequence_properties", "rna_sequence_features"]}
	
		ignoreSequenceAttributes = []
		
		if form.has_key("sequenceType"):
			sequenceType = form.getvalue("sequenceType")
			
			if sequenceType == "None":
				ignoreSequenceAttributes = ["protein_sequence_properties", "protein_sequence_features", "rna_sequence_properties", "rna_sequence_features", "sequence_properties", "dna_sequence_features"]
				
			elif sequenceType == "DNA Sequence":
				ignoreSequenceAttributes = ["protein_sequence_properties", "protein_sequence_features", "rna_sequence_properties", "rna_sequence_features"]
				
			elif sequenceType == "Protein Sequence":
				ignoreSequenceAttributes = ["rna_sequence_properties", "rna_sequence_features", "sequence_properties", "dna_sequence_features"]
				
			elif sequenceType == "RNA Sequence":
				ignoreSequenceAttributes = ["protein_sequence_properties", "protein_sequence_features", "sequence_properties", "dna_sequence_features"]
			
			#for st in sequenceTypeCategoryAliases.keys():
				#if st != sequenceType:
					#ignore_props_list = sequenceTypeCategoryAliases[st]
					#ignoreSequenceAttributes += ignore_props_list

		#print "to ignore " + `ignoreSequenceAttributes`
		
		if form.has_key("category[]"):
			categories = form.getlist("category[]")
			#print `categories`
			
			for category in categories:	# this is an alias, e.g. "general_properties" => 'General Properties'
				rTypeProps = {}		# propAlias, propDescr
				
				# Dec. 4/09: Remove this - sequence is not saved
				#if len(ignoreSequenceAttributes) > 0 and category not in ignoreSequenceAttributes:
				if category not in ignoreSequenceAttributes:
					
					#print "Category: " + category
	
					# April 15, 2009
					categoryDescriptor = form.getvalue("category_descriptor_" + category)
					#print categoryDescriptor
	
					categoryDescriptor = categoryDescriptor.replace('%', '%%')
	
					checkboxAliases = form.getlist("createReagentType_" + category+"[]") + form.getlist(category+"[]")
	
					# Aug. 13/09: Don't save empty categories
					if len(checkboxAliases) > 0:
						catDescrDict[category] = categoryDescriptor
		
					#print `checkboxAliases`
					
					for cbAlias in checkboxAliases:
						#print "Alias " + cbAlias
						cbAlias = cbAlias.strip()
						#print cbAlias
						
						if cbAlias == 'type':
							# prepend reagent type name to it
							pDescr = rTypeName + " Type"
							pAlias = string.join(rTypeName.split(" "), "_") + "_type"
							#print pDescr
						
						# Update Dec. 1/09
						elif form.has_key("createReagentType_" + category + "_:_" + cbAlias):
							tmpDescr = form.getvalue("createReagentType_" + category + "_:_" + cbAlias)
							
							if pHandler.findPropertyDescriptionFromAlias(cbAlias) > 0:
								pAlias = cbAlias
								pDescr = pHandler.findPropertyDescriptionFromAlias(pAlias)
								pName = pDescr.lower()
							
							elif pHandler.findPropertyAliasFromDescription(tmpDescr):
								pAlias = pHandler.findPropertyAliasFromDescription(tmpDescr)
								pDescr = tmpDescr
								pName = pDescr.lower()
								
							#elif form.has_key(category + "_:_" + cbAlias):
								#pAlias = cbAlias
								#pDescr = form.getvalue(category + "_:_" + cbAlias)
								#pName = pDescr.lower()
							
							else:
								pAlias = cbAlias
								pDescr = tmpDescr
								pName = pDescr.lower()
								
								#print "Content-type:text/html"
								#print
								#print "Unknown alias: " + pAlias
							
						# Existing properties
						elif pHandler.findPropertyDescriptionFromAlias(cbAlias) > 0:
							pDescr = pHandler.findPropertyDescriptionFromAlias(cbAlias)
							pName = pDescr.lower()
							pAlias = cbAlias
						
						else:
							print "Content-type:text/html"
							print
							print "Unknown alias: " + cbAlias
						
						#print "Category " + category + ", description " + `pDescr`
						
						pAlias = pAlias.replace('%', '%%')
	
						# added check for list June 18/09 - catch duplicates
						if utils.isList(pDescr):
							for desc in pDescr:
								desc = desc.replace('%', '%%')
								pName = desc.lower()
								
								# use one dictionary for all props - June 17/09 update: don't store duplicate values
								if not desc in rTypeProps.values():
									rTypeProps[pAlias] = desc
						else:
							#print "Name " + `pDescr`
							pDescr = pDescr.replace('%', '%%')
							pName = pDescr.lower()
							
							# use one dictionary for all props - June 17/09 update: don't store duplicate values
							if not pDescr in rTypeProps.values():
								rTypeProps[pAlias] = pDescr
	
						# add descriptors - nov. 1/09: why the 'if not new' restriction??
						#if pAlias.lower() == prop_Name_Alias_Map["tag type"] and not form.has_key("is_new_" + category + "_:_" + pAlias):
						
						#print "final alias: " + pAlias
						
						# HARD-CODE (Nov. 1/09)
						#if pAlias.lower() == prop_Name_Alias_Map["tag type"]:
						if pDescr.lower() == "tag":
							rTypeProps["tag_position"] = "Tag Position"
							
						#if pAlias.lower() == prop_Name_Alias_Map["promoter"] and not form.has_key("is_new_" + category + "_:_" + pAlias):
						if pAlias.lower() == prop_Name_Alias_Map["promoter"]:
							rTypeProps["expression_system"] = "Expression System"
						
					#print "Category " + category + ", description " + pDescr
					#print `rTypeProps`
					
					#print "Description " + pDescr
					
					category = category.replace('%', '%%')
					
					# check again for non-empty props list
					if len(rTypeProps) > 0:
						categoryProps[category] = rTypeProps

		#print `rTypeProps`
		#print `categoryProps`
		
		# Aug. 11/09: Save associations
		if form.has_key("addReagentTypeParents"):
			parents = form.getlist("addReagentTypeParents")
		else:
			parents = []
		
		if form.has_key("addReagentTypeChildren"):
			children = form.getlist("addReagentTypeChildren")

		# Go to step 2 IFF there are properties whose values need to be set
		if len(categoryProps) > 0:
			if form.has_key("save_rtype"):
				rtOut.printReagentTypeCreationPage(rTypeName, rTypePrefix, 3, catDescrDict, categoryProps, parents)
			else:
				rtOut.printReagentTypeCreationPage(rTypeName, rTypePrefix, 2, catDescrDict, categoryProps, parents)
				
		# otherwise just process changes
		else:
			self.updateReagentType(form)
		

	# Save all properties for new reagent type creation
	def createReagentType(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`

		rtHandler = ReagentTypeHandler(db, cursor)			# April 3/09
		pHandler = ReagentPropertyHandler(db, cursor)
		sHandler = SystemSetHandler(db, cursor)

		propMapper = ReagentPropertyMapper(db, cursor)

		prop_Alias_ID_Map = propMapper.mapPropAliasID()			# (propAlias, propID) - e.g. ('insert_type', '48')
		
		category_Alias_ID_Map = propMapper.mapPropCategoryAliasID()
		#print `category_Alias_ID_Map`

		if form.has_key("reagentTypeName"):
			rTypeName = form.getvalue("reagentTypeName")		# capitalize first letter????  NO!!!!!
		
		if form.has_key("reagentTypePrefix"):
			rTypePrefix = form.getvalue("reagentTypePrefix")	# should the prefix always be capitalized??  NO!!!!!
	
		# create a new reagent type table entry
		if not rtHandler.existsReagentType(rTypeName, rTypePrefix):
			newRTypeID = rtHandler.addReagentType(rTypeName, rTypePrefix)
		else:
			newRTypeID = rtHandler.findReagentTypeID(rTypeName)

		#print newRTypeID
		
		# For new properties, save predefined values
		cat_newProps = form.getlist("newPropName")
		all_newProps = {}
		
		cursor.execute("SELECT MAX(propertyID) FROM ReagentPropType_tbl")
		result = cursor.fetchone()
		nextPropID = int(result[0]) + 1
		
		#print `cat_newProps`
		
		for c_prop in cat_newProps:
			pList = []

			if c_prop.find("_:_") >= 0:
				tmpStart = c_prop.index("_:_")
				tmpEnd = tmpStart + len("_:_")
				tmpCat = c_prop[0:tmpStart]
				newProp = c_prop[tmpEnd:]
				
				newProp = newProp.replace("\\'", "'")
				
				if all_newProps.has_key(tmpCat):
					pList = all_newProps[tmpCat]
					
					if newProp not in pList:
						pList.append(newProp)
						all_newProps[tmpCat] = pList
				else:
					pList = []
					pList.append(newProp)
					all_newProps[tmpCat] = pList
		
		#print "New: " + `all_newProps`

		# NO!!!!!!!!  What if category/properties are NEW, not in map?????
		#propsToRemove = []

		## Feb. 1/10: do removal here, doesn't work otherwise
		#for cat_alias in category_Alias_ID_Map.keys():
			#cat_id = category_Alias_ID_Map[cat_alias]
			#allCatPropIDs = pHandler.findPropertiesByCategory(cat_id)
			
			#for pID in allCatPropIDs:
				#pAlias = prop_ID_Alias_Map[pID]
				
				#if form.has_key("remove_" + cat_alias + "_:_" + pAlias + "_prop"):
					#rmvFlag = form.getvalue("remove_" + cat_alias + "_:_" + pAlias + "_prop")
					
					#if rmvFlag == "1":
						#propsToRemove.append(pAlias)
						#pcID = pHandler.findReagentPropertyInCategoryID(pID, cat_id)
						#rtPropHandler.deleteReagentTypeAttribute(newRTypeID, pcID)
		
		# Save remaining properties as attributes of this reagent type
		if form.has_key("category[]"):
			categories = form.getlist("category[]")
			#print "All categories: " + `categories`

			for category in categories:	# this is an alias, e.g. "general_properties" => 'General Properties'
				#print category
				
				# Jan. 26/10: need to record special info for features, e.g. colour
				if category == category_Name_Alias_Map["DNA Sequence Features"] or category == category_Name_Alias_Map["RNA Sequence Features"] or category == category_Name_Alias_Map["Protein Sequence Features"]:
					isSeqFeature = True
				else:
					isSeqFeature = False
				
				if all_newProps.has_key(category):
					newProps = all_newProps[category]
				else:
					newProps = []
				
				categoryDescriptor = form.getvalue("category_descriptor_" + category)
				
				categoryDescriptor = categoryDescriptor.replace("\\'", "'")
				#print "??" + categoryDescriptor + "!!"
				
				if not pHandler.existsReagentPropertyCategory(categoryDescriptor) or not category_Alias_ID_Map.has_key(category):
					categoryID = pHandler.addReagentPropertyCategory(categoryDescriptor, category)
				else:
					categoryID = category_Alias_ID_Map[category]
				
				#print `categoryID`
				
				propAliases = form.getlist(category+"[]")
				#print `propAliases`
				#print `propsToRemove`
				
				#print `newProps`
				
				for pAlias in propAliases:
					#print pAlias
					
					pAlias = pAlias.replace("\\'", "'")	# Nov. 1/09: make lowercase??
					#pAlias = pAlias.replace("'", "prime")
					#print "SAVING " + pAlias
					
					#if form.has_key("remove_" + category + "_:_" + pAlias + "_prop"):
						##print "REMOVING " + pAlias
						#rmvFlag = form.getvalue("remove_" + category + "_:_" + pAlias + "_prop")
						##print "remove_" + category + "_:_" + pAlias + "_prop"
						
						#if rmvFlag == "1":
							#continue
					# this is just a debugging print statement
					#else:
						#print "NOT remove " + pAlias
					
					if pAlias == rTypeName + "_type":
						# prepend reagent type name to it
						#pAlias = rTypeName + "_type"
						pName = rTypeName + " type"
						pDescr = rTypeName + " Type"
						
						newPropAlias = category + "_:_" + pAlias
						
						# 'if exists' added June 17/09
						if not pHandler.existsReagentPropertyType(pName):
							# correction June 22/09: don't include category in argument list
							# (obviously this is not a feature, no need to set parameter - jan. 26/10)
							newPropID = pHandler.addReagentPropertyType(pName, pAlias, pDescr)
						else:
							#newPropID = prop_Alias_ID_Map[pAlias]
							newPropID = pHandler.findPropID(pName, True)
						
						#print pAlias
						#print newPropID

						# added June 17/09
						if not pHandler.existsPropertyInCategory(newPropID, categoryID):
							#print "here??"
							propCatID = pHandler.addReagentPropertyToCategory(newPropID, categoryID)
						else:
							propCatID = pHandler.findReagentPropertyInCategoryID(newPropID, categoryID)

						#print `propCatID`

						# 'if exists' added June 17/09
						if not rtPropHandler.existsReagentTypeAttribute(newRTypeID, propCatID):
							rTypeAttrID = rtPropHandler.addReagentTypeAttribute(newRTypeID, propCatID)
						else:
							rTypeAttrID = rtPropHandler.findReagentTypeAttributeID(newRTypeID, propCatID)
						
						# NOOO, this is a NEW property, where would propCatID come from??
						if propCatID > 0 and form.has_key("propOrder_" + `propCatID`):
							pOrder = form.getvalue("propOrder_" + `propCatID`)
							#print "Setting order " + `pOrder` + " for " + pAlias
							rtPropHandler.setReagentTypeAttributeOrdering(rTypeAttrID, pOrder)
						else:
							#print "HERE!!! this is a new property!!"
							tmp_prop_cat_alias = category + "_:_" + pAlias
							
							if form.has_key("propOrder_" + tmp_prop_cat_alias):
								pOrder = form.getvalue("propOrder_" + tmp_prop_cat_alias)
								rtPropHandler.setReagentTypeAttributeOrdering(rTypeAttrID, pOrder)
						
						# June 16, 2010
						rtPropHandler.makeSingle(rTypeAttrID)
						rtPropHandler.removeCustomizeable(rTypeAttrID)
						
						if form.has_key("hyperlink_" + newPropAlias):
							#print "MAKING HYPERLINK"
							rtPropHandler.makeHyperlink(rTypeAttrID)
						else:
							rtPropHandler.removeHyperlink(rTypeAttrID)
		
						# update list for multiple values
						numVals = form.getvalue("num_vals_" + newPropAlias)
						
						#print numVals
						
						if numVals == 'predefined':		# dropdown list of values
							#print newPropAlias
							
							# June 16, 2010
							rtPropHandler.removeHyperlink(rTypeAttrID)
							
							# Apr. 29, 2010: Hyperlinks and multiples
							if form.has_key("isMultiple_" + newPropAlias):
								#print "MAKING MULTIPLE"
								rtPropHandler.makeMultiple(rTypeAttrID)
							else:
								rtPropHandler.makeSingle(rTypeAttrID)
							
							if form.has_key("allowOther_" + newPropAlias):
								rtPropHandler.makeCustomizeable(rTypeAttrID)
							else:
								rtPropHandler.removeCustomizeable(rTypeAttrID)
							
							comms = categoryDescriptor + " " + pName
							
							if not sHandler.existsSetGroup(propCatID):
								ssetGroupID = sHandler.addSetGroupID(propCatID, comms)
							else:
								ssetGroupID = sHandler.findPropSetGroupID(propCatID)
							
							propVals = form.getlist("propertyValues_" + newPropAlias)
							#print `propVals`
							
							for pVal in propVals:
								if pVal != 'default':
									#print pVal
									#sHandler.updateSet(pName, pVal)
									#setComms = pDescr
									#sHandler.updateSet(rTypeAttrID, setComms, pVal)
									sID = sHandler.findSetValueID(ssetGroupID, pVal)
									
									if sID <= 0:
										sID = sHandler.addSetValue(ssetGroupID, pVal)
									
									if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttrID, sID):
										sHandler.addReagentTypeAttributeSetEntry(rTypeAttrID, sID)

					elif pAlias not in newProps:
						pAlias = pAlias.lower()
						#print "Alias existing: " + pAlias

						# think I've stripped category from the alias already but test more to make sure
						#if pAlias.find(category + "_:_") == 0:
							#pAlias = pAlias[len(category + "_:_"):]

						tmpPropID = pHandler.findPropertyIDFromAlias(pAlias)
						#print `prop_Alias_ID_Map`

						#tmpPropID = prop_Alias_ID_Map[pAlias]
						#pDescr = prop_Alias_Desc_Map[pAlias]	# nov. 1/09: verify this

						pDescr = pHandler.findPropertyDescriptionFromAlias(pAlias)
						
						#removeFlag = form.getvalue("remove_" + (category + "_:_" + pAlias).replace("'", "\\'") + "_prop")
						
						#if removeFlag == "1":
							##print "REMOVING " + pAlias + " from category " + category

							#if pHandler.existsPropertyInCategory(tmpPropID, categoryID):
								#propCatID = pHandler.findReagentPropertyInCategoryID(tmpPropID, categoryID)
								#rtPropHandler.deleteReagentTypeAttribute(newRTypeID, propCatID)
							
						#else:
						if not pHandler.existsPropertyInCategory(tmpPropID, categoryID):
							#print "Adding property " + pAlias + " to category " + `categoryID`
							propCatID = pHandler.addReagentPropertyToCategory(tmpPropID, categoryID)
						else:
							propCatID = pHandler.findReagentPropertyInCategoryID(tmpPropID, categoryID)
						
						#print propCatID
						
						if not rtPropHandler.existsReagentTypeAttribute(newRTypeID, propCatID):
							rTypeAttrID = rtPropHandler.addReagentTypeAttribute(newRTypeID, propCatID)
						else:
							rTypeAttrID = rtPropHandler.findReagentTypeAttributeID(newRTypeID, propCatID)
					
						#print rTypeAttrID
						
						# April 20, 2010
						if propCatID > 0 and form.has_key("propOrder_" + `propCatID`):
							pOrder = form.getvalue("propOrder_" + `propCatID`)
							#print "Setting order " + `pOrder` + " for " + pAlias
							rtPropHandler.setReagentTypeAttributeOrdering(rTypeAttrID, pOrder)
						else:
							#print "HERE!!! this is a new property!!"
							tmp_prop_cat_alias = category + "_:_" + pAlias
							
							if form.has_key("propOrder_" + tmp_prop_cat_alias):
								pOrder = form.getvalue("propOrder_" + tmp_prop_cat_alias)
								rtPropHandler.setReagentTypeAttributeOrdering(rTypeAttrID, pOrder)

						# June 16, 2010
						rtPropHandler.makeSingle(rTypeAttrID)
						rtPropHandler.removeCustomizeable(rTypeAttrID)
						
						if form.has_key("hyperlink_" + category + "_:_" + pAlias):
							#print "MAKING HYPERLINK"
							rtPropHandler.makeHyperlink(rTypeAttrID)
						else:
							rtPropHandler.removeHyperlink(rTypeAttrID)
	
						# Still have to update dropdown set
						numVals = form.getvalue("num_vals_" + category + "_:_" + pAlias)
						#print category  + ": " + `numVals`
						
						if numVals == 'predefined':		# dropdown list of values
							propVals = form.getlist("propertyValues_" + category + "_:_" + pAlias)
							#print `propVals`
							
							# June 16, 2010
							rtPropHandler.removeHyperlink(rTypeAttrID)
	
							# Apr. 29, 2010: Hyperlinks and multiples
							if form.has_key("isMultiple_" + category + "_:_" + pAlias):
								#print "MAKING MULTIPLE " + pAlias
								rtPropHandler.makeMultiple(rTypeAttrID)
							else:
								rtPropHandler.makeSingle(rTypeAttrID)
							
							if form.has_key("allowOther_" + category + "_:_" + pAlias):
								rtPropHandler.makeCustomizeable(rTypeAttrID)
							else:
								rtPropHandler.removeCustomizeable(rTypeAttrID)
								
							comms = categoryDescriptor + " " + pDescr
							
							if not sHandler.existsSetGroup(propCatID):
								ssetGroupID = sHandler.addSetGroupID(propCatID, comms)
							else:
								ssetGroupID = sHandler.findPropSetGroupID(propCatID)
							
							for pVal in propVals:
								if pVal != 'default':
									#print pVal
									#sHandler.updateSet(pName, pVal)
									#setComms = rTypeName + " " + pDescr
									#sHandler.updateSet(rTypeAttrID, setComms, pVal)
									sID = sHandler.findSetValueID(ssetGroupID, pVal)
									
									if sID <= 0:
										sID = sHandler.addSetValue(ssetGroupID, pVal)
									
									if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttrID, sID):
										sHandler.addReagentTypeAttributeSetEntry(rTypeAttrID, sID)
					else:
						######################################################################################
						# Oct. 27/09: This might be a completely new property that does not exist in the database.
						#
						# Alternatively, it could be an existing property under a different category.
						#
						# However, property names don't always correspond to property descriptions in lowercase (hence the distinction between the table columns) - e.g. 'mw' propertyName => 'Molecular Weight (MW)' description, or 'tag type' propertyName ==> 'Tag' description.  Attempting to match property ID by assuming propertyMame is lowercase description and searching for it is not correct.
						#
						# Either search by the description provided, OR rely on the alias.
						######################################################################################
						
						#print "NEW " + pAlias
						#print category
						
						tmp_new_props = all_newProps[category]
						#print `tmp_new_props`
						
						for tmp_alias in tmp_new_props:
							#print tmp_alias
							
							#newPropAlias = pAlias
							newPropAlias = tmp_alias.lower()	# ?????????
							#print newPropAlias + ", " + pAlias
							
							temp_val = (category + "_:_" + tmp_alias).replace("'", "\\'")
							
							if form.has_key("newPropDescr_" + category + "_:_" + tmp_alias):
								descriptions = form.getvalue("newPropDescr_" + category + "_:_" + tmp_alias)	# is this a list?
								#print `descriptions`
								
								if utils.isList(descriptions):
									for pDescr in descriptions:
										#if prop_Desc_Name_Map.has_key(pDescr):
										if pHandler.findPropNameByDescription(pDescr):
											pName = pHandler.findPropNameByDescription(pDescr)
											#pName = prop_Desc_Name_Map[pDescr]
										else:
											# Nov. 3/09: CHECK FOR EXISTENCE ***IN ANY CASE***
											# Because might end up w/ smtg like 'Tag Type' being added => the name 'tag type' exists, even though its description is 'Tag'.
										
											if not phandler.existsPropertyName(pDescr.lower()):
												pName = pDescr.lower()	# Nov. 2/09: yes, make lowercase!!
											else:
												# Nov. 1/09: Generate a NEW name
												pName = pDescr.lower() + `nextPropID`
								else:
									pDescr = descriptions
									
									if pHandler.findPropNameByDescription(pDescr):
									#if prop_Desc_Name_Map.has_key(pDescr):
										#pName = prop_Desc_Name_Map[pDescr]
										pName = pHandler.findPropNameByDescription(pDescr)
	
										#print "name found " + pName
									else:
										# Oct. 29/09: This is a new description.  However, the alias or name may exist! - e.g. "Tag Type" description would map to 'Tag_Type' alias in lowercase => and the name would be 'tag type'.  No good!  If description is not found here, still check name and alias!!
	
										#if prop_Alias_ID_Map.has_key(newPropAlias):
										if pHandler.findPropertyIDFromAlias(newPropAlias) > 0:
											# make a new table entry - the description provided by the user, a new alias and a new name
											newPropAlias = newPropAlias + "_" + `nextPropID`	# don't make lowercase
	
											#print "changed alias"
	
											# check name
											#if prop_Desc_Name_Map.has_key(pDescr):
											if pHandler.findPropNameByDescription(pDescr):
												# Different description and alias but same name => make a new entry, just not lowercase
												pName = pDescr.lower() + "_" + `nextPropID`
											else:
												# still check for existence!!! (nov. 1/09)
												if not pHandler.existsPropertyName(pDescr.lower()):
													pName = pDescr.lower()	# Nov. 2/09: yes, make lowercase!!
												else:
													# Nov. 1/09: Generate a NEW name
													pName = pDescr.lower() + `nextPropID`
										
												#pName = pDescr.lower()	# Nov. 1/09
										else:
											# descr and alias are new; check name
											if pHandler.findPropNameByDescription(pDescr):
											#if prop_Desc_Name_Map.has_key(pDescr):
												# Different description and alias but same name => make a new entry
												pName = pDescr.lower() + "_" + `nextPropID`
											else:
												# check for existence again
												if not pHandler.existsPropertyName(pDescr.lower()):
													pName = pDescr.lower()	# Nov. 2/09: yes, make lowercase!!
												else:
													# Nov. 1/09: Generate a NEW name
													pName = pDescr.lower() + `nextPropID`
										
												#pName = pDescr.lower()
	
										#print "name " + pName
								
								# increment here
								nextPropID += 1
								
							elif form.has_key("newPropDescr_" + temp_val):
								descriptions = form.getvalue("newPropDescr_" + temp_val)
								
								if utils.isList(descriptions):
									for pDescr in descriptions:
										#if prop_Desc_Name_Map.has_key(pDescr):
										if pHandler.findPropNameByDescription(pDescr):
											#pName = prop_Desc_Name_Map[pDescr]
											pName = pHandler.findPropNameByDescription(pDescr)
										else:
											#pName = pDescr.lower()
										
											if not pHandler.existsPropertyName(pDescr.lower()):
												pName = pDescr.lower()	# Nov. 2/09: yes, make lowercase!!
											else:
												# Nov. 1/09: Generate a NEW name
												pName = pDescr.lower() + `nextPropID`
								else:
									pDescr = descriptions
									
									#if prop_Desc_Name_Map.has_key(pDescr):
									if pHandler.findPropNameByDescription(pDescr):
										pName = pHandler.findPropNameByDescription(pDescr)
										#pName = prop_Desc_Name_Map[pDescr]
									else:
										#pName = pDescr.lower()
										
										if not pHandler.existsPropertyName(pDescr.lower()):
											pName = pDescr.lower()	# Nov. 2/09: yes, make lowercase!!
										else:
											# Nov. 1/09: Generate a NEW name
											pName = pDescr.lower() + `nextPropID`
							else:
								pDescr = newPropAlias
								
								if pDescr.find(category + "_:_" + newPropAlias + "_:_") == 0:
									pDescr = pDescr[len(category + "_:_" + newPropAlias + "_:_"):]
								
								#if prop_Desc_Name_Map.has_key(pDescr):
								if pHandler.findPropNameByDescription(pDescr):
									pName = pHandler.findPropNameByDescription(pDescr)
									#pName = prop_Desc_Name_Map[pDescr]
								else:
									#pName = pDescr.lower()
									
									if not pHandler.existsPropertyName(pDescr.lower()):
										pName = pDescr.lower()	# Nov. 2/09: yes, make lowercase!!
									else:
										# Nov. 1/09: Generate a NEW name
										pName = pDescr.lower() + `nextPropID`
							
							#print pName
							#print pDescr
	
							#print `prop_Desc_ID_Map`
	
							#if not pHandler.existsReagentPropertyType(pName, True):
							if pHandler.findPropIDByDescription(pDescr) <= 0:
							#if not prop_Desc_ID_Map.has_key(pDescr):
								# correction June 22/09: don't include category in arguments list
								newPropID = pHandler.addReagentPropertyType(pName, newPropAlias, pDescr, isSeqFeature)
								#print newPropID
							else:
								#print "what, here??"
								#newPropID = pHandler.findPropID(pName, True)
	
								# Nov. 1/09: don't search by name, search description!
								newPropID = pHandler.findPropIDByDescription(pDescr)
								
							#print newPropID
							
							tmpCatID = pHandler.findReagentPropertyInCategoryID(newPropID, categoryID)
							
							if tmpCatID <= 0:
								tmpCatID = pHandler.addReagentPropertyToCategory(newPropID, categoryID)
								
							#print tmpCatID
							
							#removeFlag = form.getvalue("remove_" + (category + "_:_" + newPropAlias).replace("'", "\\'") + "_prop")
							
							#print "remove_" + category + "_:_" + pAlias + "_prop"
							#print "?? " + removeFlag + "!!"
							
							#if not removeFlag or removeFlag != "1":
								#print newPropID
							
							# isn't the check for existence redundant?  this is a new property!
							if not rtPropHandler.existsReagentTypeAttribute(newRTypeID, tmpCatID):
								rTypeAttrID = rtPropHandler.addReagentTypeAttribute(newRTypeID, tmpCatID)
								#print "rtypeattr " + `rTypeAttrID`
							else:
								rTypeAttrID = rtPropHandler.findReagentTypeAttributeID(newRTypeID, tmpCatID)
							
							# April 20, 2010
							# this is highly unlikely but keep for robustness
							if propCatID > 0 and form.has_key("propOrder_" + `tmpCatID`):
								pOrder = form.getvalue("propOrder_" + `tmpCatID`)
								#print "Setting order " + `pOrder` + " for " + pAlias
								rtPropHandler.setReagentTypeAttributeOrdering(rTypeAttrID, pOrder)
							else:
								if form.has_key("propOrder_" + temp_val):
									pOrder = form.getvalue("propOrder_" + temp_val)
									rtPropHandler.setReagentTypeAttributeOrdering(rTypeAttrID, pOrder)
						
							#print "was ist das? " + "isMultiple_" + temp_val
							
							# Set multiple and other for dropdowns only
							rtPropHandler.makeSingle(rTypeAttrID)
							rtPropHandler.removeCustomizeable(rTypeAttrID)
							
							if form.has_key("hyperlink_" + temp_val):
								#print "MAKING HYPERLINK??????????????"
								rtPropHandler.makeHyperlink(rTypeAttrID)
							else:
								rtPropHandler.removeHyperlink(rTypeAttrID)

							#print "rtypeattr " + `rTypeAttrID`
							
							# change Oct. 19/09 - catch single quotes
							# update list for multiple values
							numVals = form.getvalue("num_vals_" + category + "_:_" + tmp_alias)	# THIS IS RIGHT

							#numVals = form.getvalue("num_vals_" + (category + "_:_" + newPropAlias).replace("'", "\\'"))

							#print "num_vals_" + category + "_:_" + pAlias

							#numVals = form.getvalue("num_vals_" + (category + "_:_" + tmp_alias).replace("'", "\\'"))
							
							#print "palias " + pAlias
							#print ", newPropAlias " + newPropAlias
							#print ", numvals " + `numVals`
							
							comms = categoryDescriptor + " " + pDescr
							
							if numVals == 'predefined':		# dropdown list of values
								
								# remove hyperlink for dropdowns
								rtPropHandler.removeHyperlink(rTypeAttrID)

								# Apr. 29, 2010: Hyperlinks and multiples
								if form.has_key("isMultiple_" + temp_val):
									#print "MAKING MULTIPLE " + pDescr
									rtPropHandler.makeMultiple(rTypeAttrID)
								else:
									rtPropHandler.makeSingle(rTypeAttrID)
									
								if form.has_key("allowOther_" + temp_val):
									rtPropHandler.makeCustomizeable(rTypeAttrID)
								else:
									rtPropHandler.removeCustomizeable(rTypeAttrID)
								
								if not sHandler.existsSetGroup(tmpCatID):
									ssetGroupID = sHandler.addSetGroupID(tmpCatID, comms)
								else:
									ssetGroupID = sHandler.findPropSetGroupID(tmpCatID)
									
								#print "????? " + `ssetGroupID`
								
								#if pHandler.findPropertyIDFromAlias(newPropAlias, True) > 0:
								if form.has_key("propertyValues_" + (category + "_:_" + newPropAlias).replace("'", "\\'")):
									propVals = form.getlist("propertyValues_" + (category + "_:_" + newPropAlias).replace("'", "\\'"))
									#print "HERE???"
								elif form.has_key("propertyValues_" + category + "_:_" + tmp_alias):
									propVals = form.getlist("propertyValues_" + category + "_:_" + tmp_alias)
									#print "OR HERE???"
								else:
									propVals = []
								
								#print "new alias " + newPropAlias
								#print "alias " + pAlias

								#print "INSERTING " + `propVals`
								
								for pVal in propVals:
									if pVal != 'default':
										#print rTypeAttrID
										#print pVal
										#setComms = rTypeName + " " + pDescr
										#print setComms
										#print "UPDATING SET!!! " + pAlias
										#sHandler.updateSet(rTypeAttrID, setComms, pVal)
									
										sID = sHandler.findSetValueID(ssetGroupID, pVal)
										
										if sID <= 0:
											sID = sHandler.addSetValue(ssetGroupID, pVal)
										
										#print `sID`
									
										if not sHandler.existsReagentTypeAttributeSetValue(rTypeAttrID, sID):
											sHandler.addReagentTypeAttributeSetEntry(rTypeAttrID, sID)

				#print newPropID

		return newRTypeID


	def deleteReagentType(self, form):
		
		db = self.__db
		cursor = self.__cursor
		hostname = self.__hostname
		
		rtHandler = ReagentTypeHandler(db, cursor)
		rtPropHandler = ReagentTypePropertyHandler(db, cursor)		# Aug. 31/09
		pHandler = ReagentPropertyHandler(db, cursor)
		sHandler = SystemSetHandler(db, cursor)
		ltHandler = LocationTypeHandler(db, cursor)
		
		propMapper = ReagentPropertyMapper(db, cursor)
		rMapper = ReagentTypeMapper(db, cursor)
		
		prop_Alias_ID_Map = propMapper.mapPropAliasID()		# (propAlias, propID) - e.g. ('insert_type', '48')
		
		category_Alias_ID_Map = propMapper.mapPropCategoryAliasID()
		category_Name_ID_Map = propMapper.mapPropCategoryNameID()
		
		#print `category_Alias_ID_Map`

		reagentType_Name_ID_Map =  rMapper.mapTypeNameID()
		reagentType_ID_Name_Map = rMapper.mapTypeIDName()

		# March 10, 2011
		ignoreList = ["sequence", "length", "melting temperature", "molecular weight", "protein sequence", "rna sequence"]

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
	
		# search and creation views have different input names
		if form.has_key("reagentType"):
			rTypeName = form.getvalue("reagentType")
		elif form.has_key("reagentTypeName"):
			rTypeName = form.getvalue("reagentTypeName")
		else:
			raise UnknownReagentTypeException("Unknown reagent type")
		
		#print rTypeName
		rTypeID = rtHandler.findReagentTypeID(rTypeName)
		rTypePrefix = rtHandler.findReagentTypePrefix(rTypeName)
		
		rTypeAttributes = rtPropHandler.findAllReagentTypeAttributes(rTypeID)
		allSetIDs = []
		
		# Find all currently available reagent type dropdown values
		for r_type_id in reagentType_ID_Name_Map.keys():
			#print "Checking rtype " + `r_type_id`
			
			if r_type_id != rTypeID:
				attrs = rtPropHandler.findAllReagentTypeAttributes(r_type_id)
				#print "Its attributes are: " + `attrs`
				
				for attr in attrs:
					set_ids = sHandler.findReagentTypeAttributeSetIDs(attr)
					#print "For attr " + `attr` + ", the set ids are: " + `set_ids`
					
					allSetIDs += set_ids
		
		#print `allSetIDs`
		
		for aKey in rTypeAttributes:
			attrID = rTypeAttributes[aKey]		# this is propCatID
			#print "Attr ID " + `attrID`
			
			# Before deprecating attrID:
			propCatID = rtPropHandler.findReagentTypeAttributePropertyID(aKey)
			#print "propCatID " + `propCatID`
			
			# Aug. 5/09: Delete System_Set_Group values - Nov. 16/09: modified code
			setGroupID = sHandler.findPropSetGroupID(propCatID)
			#print "setGroupID " + `setGroupID`
			
			# Find its values AND check again to make sure they're not used for other reagent types!  Then delete
			setIDs = sHandler.findAllSetIDs(setGroupID)
			
			#print `setIDs`
			
			for sID in setIDs:
				if sID not in allSetIDs:
					#print "Deleting set id " + `sID`
					sHandler.deleteSetValue(sID)
			
			# and now can delete 'orphaned' groups
			if len(sHandler.findAllSetIDs(setGroupID)) == 0:
				sHandler.deleteSetGroup(setGroupID)
			
			#sHandler.deleteSetValues(setGroupID)
			
			sHandler.deleteReagentTypeAttributeSetValues(aKey)
			
			# Aug. 11/09: Find its category here - will delete later if empty
			attr_category = pHandler.findReagentPropertyCategory(attrID)
			#attr_propID = pHandler.findReagentPropertyInCategory(attrID)

			# March 10, 2011
			propID = pHandler.findReagentPropertyInCategory(propCatID)

			#print "pcid" + `propCatID`
			#print "propid " + `propID`

			# don't touch ReagentPropType_tbl but delete property from category **IFF** not used anywhere else!!!!!!!!!!!!!!!!
			cursor.execute("SELECT * FROM ReagentTypeAttributes_tbl WHERE propertyTypeID=" + `attrID` + " AND reagentTypeID != " + `rTypeID` + " AND status='ACTIVE'")
			results = cursor.fetchall()

			# March 10, 2011
			if prop_ID_Name_Map[propID] not in ignoreList:
			
				if not results or len(results) == 0:
					pHandler.deleteReagentPropertyInCategory(attrID)

				# Remove ReagentTypeAttributes table entry March 10, 2011: **EXCEPT** Protein Sequence and RNA Sequence!!!!!!!!!
				rtPropHandler.deleteReagentTypeAttribute(rTypeID, attrID)

			# delete category if empty, EXCLUDE DEFAULT CATEGORIES (i.e. even if no other reagent type has RNA sequence, don't delete 'RNA Sequence' category!!!!!!!
			if attr_category != category_Name_ID_Map["General Properties"] and attr_category != category_Name_ID_Map["DNA Sequence"] and attr_category != category_Name_ID_Map["External Identifiers"] and attr_category != category_Name_ID_Map["Classifiers"] and attr_category != category_Name_ID_Map["DNA Sequence Features"] and attr_category != category_Name_ID_Map["RNA Sequence"] and attr_category != category_Name_ID_Map["RNA Sequence Features"] and attr_category != category_Name_ID_Map["Protein Sequence"] and attr_category != category_Name_ID_Map["Protein Sequence Features"] and pHandler.isEmpty(attr_category):
				#print "DELETEING CATEGORY " + `attr_category`
				pHandler.deleteReagentPropertyCategory(attr_category)

		# Aug. 10/09: Delete "thisReagentType Type" property, e.g. if deleting Virus also delete 'Virus type' property - it's not going to be used anywhere else
		if prop_Name_ID_Map.has_key(rTypeName + " type"):
			pHandler.deleteReagentProperty(prop_Name_ID_Map[rTypeName + " type"])
		
		# Aug. 27/09: Delete associations - Update Dec. 22/09: changed rTypeName to rTypeID
		self.deleteReagentTypeAssociations(rTypeID)
		
		# Feb. 25/10
		ltHandler.deleteContainerReagentType(rTypeID)
	
		rtHandler.deleteReagentType(rTypeID)


##########################################################
# Central callable function
##########################################################
def main():

	rtReqHandler = ReagentTypeRequestHandler()
	rtReqHandler.handle()

main()