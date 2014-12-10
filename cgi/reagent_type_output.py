#!/usr/local/bin/python

import cgi

import os
import sys
import tempfile
import stat

from database_conn import DatabaseConn

from reagent_handler import ReagentHandler
from general_handler import *
from mapper import *

from reagent import Reagent
from session import Session

from general_output import GeneralOutputClass
#from system_set_handler import SystemSetHandler

from sequence import *

import utils
import urllib

#############################################################################################################
# This class handles the presentation layer of the website
# Generates HTML content for Reagent Type views and writes it in files, which are then opened by Reagent.php
#
# Written April 12, 2009 by Marina Olhovsky
# Last modified: April 12, 2009
#############################################################################################################
class ReagentTypeOutputClass:
	
	# Invoked at step 2 of Reagent Type creation - set values for new attributes
	def printReagentTypeCreationPage(self, rTypeName, rTypePrefix, step, catDescrDict={}, categoryProps={}, parents=[], errCode=0):

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `categoryProps`
		#print step
		
		#print rTypeName
		#print rTypePrefix

		rTypeName = rTypeName.replace("%", "%%")
		
		dbConn = DatabaseConn()
		hostname = dbConn.getHostname()		# to define form action URL
		root_dir = dbConn.getRootDir()
		
		programmer_email = dbConn.getProgrammerEmail()	# June 14, 2010
		
		db = dbConn.databaseConnect()
		cursor = db.cursor()
		
		sHandler = SystemSetHandler(db, cursor)
		propMapper = ReagentPropertyMapper(db, cursor)
		rtAssocHandler = ReagentTypeAssociationHandler(db, cursor)
		rMapper = ReagentTypeMapper(db, cursor)
		aMapper = ReagentAssociationMapper(db, cursor)
		
		rtPropHandler = ReagentTypePropertyHandler(db, cursor)		# Aug. 31/09
		
		prop_Alias_ID_Map = propMapper.mapPropAliasID()		# (propAlias, propID) - e.g. ('insert_type', '48')
		prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)
		prop_ID_Name_Map = propMapper.mapPropIDName()		# Added March 13/08 - (prop id, prop name)
		prop_Name_Alias_Map = propMapper.mapPropNameAlias()	# (propName, propAlias)
		prop_Alias_Name_Map = propMapper.mapPropAliasName()	# March 18/08 - (propAlias, propName)
		prop_Alias_Descr_Map = propMapper.mapPropAliasDescription()
		prop_Desc_Alias_Map = propMapper.mapPropDescAlias()
		prop_ID_Desc_Map = propMapper.mapPropIDDescription()
		prop_ID_Alias_Map= propMapper.mapPropIDAlias()
		prop_Name_Desc_Map = propMapper.mapPropNameDescription()
		prop_Desc_ID_Map = propMapper.mapPropDescID()

		reagentType_Name_ID_Map =  rMapper.mapTypeNameID()
		reagentType_ID_Name_Map =  rMapper.mapTypeIDName()
		
		assoc_ID_ParentTypeID_Map = aMapper.mapAssocIDParentType()

		prop_ID_Order_map = propMapper.mapPropIDOrdering()
		
		# sort categories
		category_ID_Order_Map = propMapper.mapPropCategoryIDOrdering()
		category_Alias_ID_Map = propMapper.mapPropCategoryAliasID()
		category_ID_Alias_Map = propMapper.mapPropCategoryIDAlias()
		category_ID_Name_Map = propMapper.mapPropCategoryIDName()
		category_Name_ID_Map = propMapper.mapPropCategoryNameID()
		category_Name_Alias_Map = propMapper.mapPropCategoryNameAlias()
		
		# Dec. 9/09
		featureDescriptors = ["tag_position", "expression_system"]
		
		temp_categories = {}
		new_cats = []
		
		#print `category_Alias_ID_Map`
		#print `category_ID_Order_Map`
		
		for catAlias in categoryProps.keys():
			#print catAlias
			
			if category_Alias_ID_Map.has_key(catAlias):
				catID = category_Alias_ID_Map[catAlias]
				#print catID
				if category_ID_Order_Map.has_key(catID):
					ordering = category_ID_Order_Map[catID]
					#print ordering
					temp_categories[ordering] = catAlias
			else:
				new_cats.append(catAlias)
				
		#print `new_cats`

		if len(temp_categories) > 0:
			new_start = max(temp_categories.keys())
		else:
			# only wish to add a new category
			new_start = sys.maxint
		
		#print "Content-type:text/html"
		#print
		#print `new_cats`
		#print new_start

		for catAlias in new_cats:
			#print "New " + catAlias
			ordering = new_start + 1
			temp_categories[ordering] = catAlias
			new_start += 1
		
		#temp_categories.keys().sort()		# removed Oct. 8/09 - calling 'sorted()' further down

		#print `temp_categories`

		rHandler = ReagentHandler(db, cursor)
		rtHandler = ReagentTypeHandler(db, cursor)
		pHandler = ReagentPropertyHandler(db, cursor)

		currUser = Session.getUser()
		
		gOut = GeneralOutputClass()

		content = gOut.printHeader()
		
		rt_alias = string.join(rTypeName.split(" "), "_")
		mandatoryProps = ["name", "status", "packet_id", "comments", "description", rt_alias + "_type"]
		
		display_plus = "inline"
		display_minus = "none"
		
		# Aug. 5/09: User cannot toggle between freetext and dropdown to select the format for the following properties:
		setFormatList = [prop_Name_Alias_Map["name"], prop_Name_Alias_Map["description"], prop_Name_Alias_Map["comments"], prop_Name_Alias_Map["verification comments"], prop_Name_Alias_Map["cdna insert"], prop_Name_Alias_Map["5' linker"], prop_Name_Alias_Map["3' linker"], prop_Name_Alias_Map["5' cloning site"], prop_Name_Alias_Map["3' cloning site"], prop_Name_Alias_Map["packet id"], prop_Name_Alias_Map["sequence"], prop_Name_Alias_Map["protein sequence"], prop_Name_Alias_Map["rna sequence"], prop_Name_Alias_Map["protein translation"], prop_Name_Alias_Map["accession number"], prop_Name_Alias_Map["ensembl gene id"], prop_Name_Alias_Map["official gene symbol"], prop_Name_Alias_Map["entrez gene id"], prop_Name_Alias_Map["alternate id"]]
		
		multipleExceptionslist = [prop_Name_Desc_Map["status"], prop_Name_Desc_Map["packet id"], prop_Name_Desc_Map["restrictions on use"], prop_Name_Desc_Map["open/closed"]]
		
		hyperlinkExceptionsList = [prop_Name_Desc_Map["status"], prop_Name_Desc_Map["packet id"], prop_Name_Desc_Map["accession number"], prop_Name_Desc_Map["verification"], prop_Name_Desc_Map["restrictions on use"], prop_Name_Desc_Map["developmental stage"], prop_Name_Desc_Map["tissue type"], prop_Name_Desc_Map["morphology"], prop_Name_Desc_Map["melting temperature"], prop_Name_Desc_Map["molecular weight"], prop_Name_Desc_Map["protein sequence"], prop_Name_Desc_Map["sequence"], prop_Name_Desc_Map["protein translation"], prop_Name_Desc_Map["alternate id"]]
		
		# Nov. 1/09:
		if category_Name_Alias_Map["DNA Sequence"] in categoryProps or category_Name_Alias_Map["DNA Sequence Features"] in categoryProps:
			setFormatList.append(prop_Name_Alias_Map["restriction site"])
			setFormatList.append(prop_Name_Alias_Map["molecular weight"])
			setFormatList.append(prop_Name_Alias_Map["melting temperature"])
			
		elif category_Name_Alias_Map["Protein Sequence"] in categoryProps or category_Name_Alias_Map["Protein Sequence Features"] in categoryProps:
			setFormatList.append(prop_Name_Alias_Map["molecular weight"])
			setFormatList.append(prop_Name_Alias_Map["melting temperature"])

		if step == 2 or step == 3:
			
			# Oct. 31/09: Flip the categoryProps array to have descriptions as keys and aliases as values
			cat_props_dict_flip = {}

			for catKey in categoryProps.keys():
				
				cat_props = categoryProps[catKey]	# dictionary
				tmp_dict = {}

				for catAlias in cat_props.keys():
					catProp = cat_props[catAlias]
					#print `catProp`
					
					tmp_dict[catProp] = catAlias

					# add descriptors
					tmp_dict["Tag Position"] = "tag_position"
					tmp_dict["Expression System"] = "expression_system"

					#cat_props_dict_flip[catProp] = catAlias
					cat_props_dict_flip[catKey] = tmp_dict

			#print `cat_props_dict_flip`

		if step == 1:
			#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
			#print					# DITTO
			#print `categoryProps`

			content += '''
				<FORM METHOD="POST" ID="updateReagentTypeForm" ACTION="%s" onSubmit="enableCheckboxes(); return checkEmptyDropdowns('updateReagentTypeForm');">

					<!-- pass current user as hidden form field -->
					<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username"
					'''
					
			content += "value=\"" + currUser.getFullName() + "\">"
			
			content += '''
					<INPUT TYPE="hidden" ID=\"reagent_type_name\" NAME="reagentTypeName" VALUE=\"%s\">
					<INPUT TYPE="hidden" ID=\"reagent_type_prefix\" NAME="reagentTypePrefix" VALUE=\"%s\">
					
					<INPUT TYPE="hidden" ID=\"reagent_type_modify\">
					
					<TABLE width="760px" cellpadding="4" cellspacing="4" border="0">
						<TH colspan="4" style="font-size:14pt; font-weight:bold; color:#011FF0; padding-top: 10px; padding-top:5px; padding-bottom:15px;">
							MODIFY REAGENT TYPE %s
							<BR>
						</TH>
						
						<TR>
							<TD colspan="2" style=\"border-top:2px groove green; border-bottom:2px groove green;\">
								<DIV style=\"padding-top:15px; padding-left:15px; padding-bottom:5px; padding-right:15px;\">
								
								This page allows you to:<BR>
								<UL>
									<LI>Modify basic reagent type information
									<LI>Add, change or remove reagent type attributes and their values
									<LI>Select different reagent types as parents for this reagent type.
								</UL>
								</DIV>
							</TD>
						</TR>
		
						<TR ID="rTypePrefixRow">
							<TD style="text-align:justify; font-weight:bold;">
								<BR>Reagent Type Prefix: <span style=\"color:#011FF0; font-size:13pt;\">%s</span>
							</TD>
						</TR>
						'''
				
			if rTypeName != 'Vector' and rTypeName != 'Insert' and rTypeName != 'Oligo' and rTypeName != 'CellLine':
				
				content += '''
			
							<TR ID="rTypeAssocRow">
								<TD style="width:65px; padding-left:8px; white-space:nowrap;">
									<HR><BR><span style="font-weight:bold; color:blue;">Edit Reagent Type Parents:</span><BR><P>
									'''
	
				content += "<TABLE style=\"font-weight:normal;\" border=\"0\">"
				
				content += "<TR><TD style=\"font-weight:bold;\">Parents currently assigned <BR> to reagent type " + rTypeName + ":</TD>"
				
				content += "<TD colspan=\"2\">&nbsp;</TD><TD colspan=\"2\" style=\"font-weight:bold; white-space:nowrap;\">Other parent types available in OpenFreezer:</TD></TR>"
				
				content += "<TR><TD colspan=\"2\"><SELECT SIZE=\"10\" MULTIPLE style=\"margin-top:5px; margin-left:2px;\" ID=\"parentListCurr_" + `reagentType_Name_ID_Map[rTypeName]` + "\" NAME=\"add_reagent_type_parents\">"
				
				rTypeParents = rtAssocHandler.getReagentTypeAssociations(reagentType_Name_ID_Map[rTypeName])
				#print `rTypeParents`
				reagent_type_parents = []
				
				for assocID in rTypeParents.keys():
					#print assocID
					parentTypeID = rtAssocHandler.findAssocParentType(reagentType_Name_ID_Map[rTypeName], assocID)
					#print parentTypeID
					reagent_type_parents.append(parentTypeID)
					
					if rtAssocHandler.isUsedReagentTypeAssociation(reagentType_Name_ID_Map[rTypeName], parentTypeID):
						parent_disabled = "DISABLED"
					else:
						parent_disabled = ""
					
					content += "<OPTION ID=\"" + `parentTypeID` + "\" " + parent_disabled + " VALUE=\"" + reagentType_ID_Name_Map[parentTypeID] + "\">" + reagentType_ID_Name_Map[parentTypeID] + "</OPTION>"
				
				content += "</SELECT></TD>"
				
				content += "<TD style=\"padding-right:35px;\">"
				
				content += "<INPUT TYPE=\"button\" VALUE=\"-->\" onClick=\"moveListElements('parentListCurr_" + `reagentType_Name_ID_Map[rTypeName]` + "', 'parentListAll_" + `reagentType_Name_ID_Map[rTypeName]` + "', false);\">"
				
				content += "<BR><BR><INPUT TYPE=\"button\" VALUE=\"<--\" onClick=\"moveListElements('parentListAll_" + `reagentType_Name_ID_Map[rTypeName]` + "', 'parentListCurr_" + `reagentType_Name_ID_Map[rTypeName]` + "', false);\">"
				
				content += "</TD>"
				
				#content += "<b>Other parent types available in OpenFreezer:</b><BR><BR>"
				
				content += "<TD><SELECT SIZE=\"10\" MULTIPLE style=\"margin-top:5px; margin-left:2px;\" ID=\"parentListAll_" + `reagentType_Name_ID_Map[rTypeName]` + "\">"
				
				#print `reagent_type_parents`
				
				for parentID in reagentType_ID_Name_Map.keys():
					#print parentID
					if parentID not in reagent_type_parents:
						content += "<OPTION ID=\"" + `parentID` + "\" VALUE=\"" + reagentType_ID_Name_Map[parentID] + "\">" + reagentType_ID_Name_Map[parentID] + "</OPTION>"
				
				content += "</SELECT>"
				
				content += "</TD></TR></TABLE></TD></TR>"
				
			content += '''
						<TR ID="rTypePropsRow">
							<TD>
								<HR><BR><SPAN STYLE=\"margin-left:4px; font-weight:bold; color:blue;\">Edit Reagent Type Properties:</span>
								<TABLE ID="addReagentPropsTbl">
									<TR>
										<td colspan="4" style="padding-left:6px; padding-right:10px; font-size:10pt; font-weight:bold; padding-top:5px; white-space:nowrap;"><BR>Select properties that you wish to add, modify or delete:<BR>
										
											<P><DIV style=\"border:2px groove green; padding-right:15px;\">
												<P style=\"padding-left:18px; font-weight:normal;\">&#45;&nbsp;In <span style=\"color:#00B800; font-weight:bold;\"><u>green</u></span> are names of properties that <u>have already been assigned</u> to this reagent type; they may be selected for <span style=\"color:#00B800; font-weight:bold; text-decoration:underline;\">modification</span> or <span style=\"color:#00B800; font-weight:bold; text-decoration:underline;\">deletion</span>.
											
												<BR><P style=\"padding-left:18px; font-weight:normal;\">&#45;&nbsp;In <span style=\"font-weight:bold; color:#000000;\"><u>black</u></span> are names of properties that <u>have <b>not</b> been assigned</u> to this reagent type; they will be <u><b>added</b></u> if selected.<BR>
											
												<P style=\"padding-left:25px; font-weight:bold; color:red;\">Properties that are NOT selected <u>will remain unchanged</u>.
										
												<P style=\"padding-left:25px; color:purple; font-weight:normal;  padding-top:5px;\">To change the order of properties within a category, use '<u>Check All in Use</u>' to select all the properties assigned to the reagent type in that category and update their order at step 2.
										
												<P><DIV style=\"font-weight:normal; font-size:10pt; padding-left:25px; padding-bottom:15px; padding-right:5px;\">
												E.g. If 'Accession Number', 'Alternate ID' and 'Gene Symbol' are attributes assigned to this reagent type, and you wish to have them displayed in the order 'Gene Symbol', 'Accession  Number',<BR>'Alternate ID' on reagent detailed view, select all three of these properties, and on the next page, change their order values, checking to make sure that each property has a distinct order number.<BR>Properties with identical order numbers are displayed next to each other in random fashion.</div>
										
												<div style=\"font-weight:normal; font-size:10pt; padding-left:25px; padding-bottom:15px; color:#FF0000; padding-right:5px;\">LeTTeRcAsE of property names CANNOT be changed by unchecking and re-entering.  &nbsp;&nbsp;If you wish to change the lettercase of a property name, please <a href=\"mailto:%s\">contact the programmer</a>.</div>
											</DIV>
										</td>
									</TR>
									'''

			for rTypeID in reagentType_ID_Name_Map.keys():
				r_type_name = reagentType_ID_Name_Map[rTypeID]
				
				if (r_type_name == "CellLine"):
					r_type_name = "Cell Line"
				
				setFormatList.append(r_type_name + " Type")
			
			setFormatList.append("Type of Insert")
			setFormatList.append("Open/Closed")
			
			content += '''
						<TR>
							<TD style=\"white-space:nowrap;\">
							'''
			
			#toIgnoreProps = ['name', 'status', 'packet_id', rTypeName + '_type', 'description', 'comments', 'length', 'protein_translation']
			
			for categoryID in category_ID_Name_Map:
				catAlias = category_ID_Alias_Map[categoryID]
				category = category_ID_Name_Map[categoryID]
				
				if categoryID != category_Name_ID_Map['DNA Sequence'] and categoryID != category_Name_ID_Map['Protein Sequence'] and categoryID != category_Name_ID_Map['RNA Sequence'] and categoryID != category_Name_ID_Map['DNA Sequence Features'] and categoryID != category_Name_ID_Map['Protein Sequence Features'] and categoryID != category_Name_ID_Map['RNA Sequence Features']:
					
					content += "<P><TABLE ID=\"addReagentTypePropsTbl_" + catAlias.replace("'", "\'") + "\" style=\"white-space:nowrap; width:auto;\">"
					
					content += "<TR ID=\"" + catAlias.replace("'", "\'") + "_row\">"

					content += "<TD style=\"font-weight:bold; padding-top:12px; color:#0000D2; white-space:nowrap;\" colspan=\"4\">"
					
					content += "<IMG ID=\"" + `categoryID` + "_expand_img\" SRC=\"" + hostname + "pictures/arrow_collapse.gif\" WIDTH=\"20\" HEIGHT=\"15\" BORDER=\"0\" ALT=\"plus\" class=\"menu-expanded\" style=\"display:inline;\" onClick=\"showHideCategory('" + `categoryID` + "')\">"
					
					content += "<IMG ID=\"" + `categoryID` + "_collapse_img\" SRC=\"" + hostname + "pictures/arrow_expand.gif\" WIDTH=\"40\" HEIGHT=\"34\" BORDER=\"0\" ALT=\"plus\" class=\"menu-collapsed\" style=\"display:none;\" onClick=\"showHideCategory('" + `categoryID` + "')\">"
					
					content += category
					
					content += '''
									</TD>
								</TR>
								
								<TR>
									<TD style=\"white-space:nowrap;\">
									'''
					
					content += "<TABLE ID=\"category_" + `categoryID` + "_section\" cellpadding=\"4\">"
		
					content += '''
							<TR>
								<TD colspan="4" style="padding-left:15px; white-space:nowrap">
								'''
					
					#tmp_cat = "escape(category_descriptor_" + catAlias.replace("'", "\\'") + ")"
					#print tmp_cat
					tmp_cat1 = "escape(\'" + catAlias.replace("'", "\\'") + "\')"
					
					content += "<input type=\"hidden\" name=\"category[]\" value=\"" + catAlias.replace("'", "\\'") + "\">"
					content += "<input type=\"hidden\" name=\"category_descriptor_" + catAlias.replace("'", "\\'") + "\" value=\"" + category + "\">"

					content += "<span class=\"linkShow\" style=\"margin-left:10px;font-size:8pt; font-weight:normal;\" ID=\"checkAll\" onClick=\"checkAll(" + tmp_cat1 + ");\">Check All</span>"
					
					content += "<span class=\"linkShow\" style=\"margin-left:10px;font-size:8pt; font-weight:normal;\" ID=\"uncheckAll\" onClick=\"uncheckAll(" + tmp_cat1 + ", Array());\">Uncheck All</span>"
	
					content += "<span class=\"linkShow\" style=\"margin-left:10px;font-size:8pt; font-weight:normal;\" ID=\"checkAll\" onClick=\"checkAllExclude('" + catAlias.replace("'", "\'") + "');\">Check All in Use</span>"
					
					content += '''
									</TD>
								</TR>

								<TR>
								'''

					if category_Alias_ID_Map.has_key(catAlias):
						props = pHandler.findPropertiesByCategory(category_Alias_ID_Map[catAlias])
					else:
						props = []
						
					#print `props`
					
					if categoryProps.has_key(catAlias):
						propsList = categoryProps[catAlias]
					else:
						propsList = []
						
					#print `propsList`
					
					# April 19/10: try ordering checkboxes - in a slightly different way; just sort by ordering column
					ordered_props = {}
					tmp_ord = []
					
					for propID in props:
						if prop_ID_Order_map.has_key(propID):
							tmpPropOrder = prop_ID_Order_map[propID]
							
							if ordered_props.has_key(tmpPropOrder):
								tmp_ord = ordered_props[tmpPropOrder]
							else:
								tmp_ord = []
							
							tmp_ord.append(propID)
							ordered_props[tmpPropOrder] = tmp_ord
					
					ordered_props.keys().sort()
					
					pCount = 0

					for p_ord in ordered_props.keys():
						o_props = ordered_props[p_ord]
						
						for propID in o_props:

					#for propID in props:
							propName = prop_ID_Name_Map[propID]
							#print propName
							
							# KEEP THIS!!!!!!!!!
							# (temporarily converting full prop name just to 'type' required!
							if propName == rTypeName + " type":
								propName = "type"
							
							propDescr = prop_ID_Desc_Map[propID]
							propAlias = prop_ID_Alias_Map[propID]
							
							if propAlias in propsList:
								#print propAlias
								color = "#00B800"
								
								#if propAlias in used_props:
									#readOnly = "DISABLED"
							else:
								color = "#000000"
								content += "<INPUT TYPE=\"hidden\" NAME=\"" + catAlias.replace("'", "\'") + "_exclude[]\" VALUE=\"" + propAlias + "\">"
								
							toIgnore = 0
							
							for val in setFormatList:
								if propName.lower() == val.lower():
									toIgnore = 1
									#break
							
							#if toIgnore == 1:
								#continue
	
							if pCount%4 == 0:
								content += "</TR><TR>"
								
							if propName.lower() in mandatoryProps:
								#action = "onClick=\"this.checked = true;\""
								action = ""
								readOnly = "DISABLED"
							else:
								readOnly = ""
								action = ""
						
							#print propName
							
							# KEEP THIS!!!!!!!!!  Now convert back to show 'type' in list!!!
							if propName == "type":
								propName = rTypeName + " type"
							
							content += "<TD style=\"padding-left:20px; white-space:nowrap;\"><INPUT TYPE=\"checkbox\" ID=\"" + catAlias.replace("'", "\'") + "_:_" + propAlias + "\" NAME=\"" + catAlias.replace("'", "\'") + "[]\" VALUE=\"" + prop_Name_Alias_Map[propName] + "\" " + action + "><SPAN style=\"color:" + color + ";\" ID=\"" + catAlias + "_:_" + prop_Name_Alias_Map[propName] + "_desc_hidden\">" + propDescr + "</SPAN></TD>"
	
							pCount += 1
				
					content += "</TR>"
		
					content += '''
							<TR>
								<td colspan="4" style="padding-left:20px; padding-top:10px; white-space:nowrap;">
								'''

					content += "<b>Add new " + category + " property:<b>&nbsp;&nbsp;"
					
					content += "<INPUT type=\"text\" size=\"35\" ID=\"" + catAlias.replace("'", "\'") + "_other\" name=\"" + catAlias.replace("'", "\'") + "Other\" onKeyPress=\"return disableEnterKey(event);\">&nbsp;&nbsp;"

					content += "<input onclick=\"updateCheckboxListFromInput('" + catAlias.replace("'", "\'") + "_other', '" + catAlias.replace("'", "\'") + "', 'category_" + `categoryID` + "_section', 'updateReagentTypeForm'); document.getElementById('" + catAlias.replace("'", "\'") + "_other').focus();\" value=\"Add\" type=\"button\" style=\"font-size:10pt;\"></INPUT>"

					content += '''
									</td>
								</TR>
							</TABLE>
							
							</TD>
						</TR>
					</table>
					'''

			# Sequence sections
			
			# Find out first which of the 3 sequence types this reagent type has:
			if categoryProps.has_key(category_Name_Alias_Map["DNA Sequence"]):
				hasDNA = True
				hasRNA = False
				hasProtein = False
				
				dnaDisplay = "table-row"
				dnaChecked = "checked"
				
				protDisplay = "none"
				protChecked = ""
				
				rnaDisplay = "none"
				rnaChecked = ""
				
				noneChecked = ""
				
				dnaSeqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], category_Name_ID_Map["DNA Sequence"])
				
				# Nov. 19/09: AT KAREN'S REQUEST, DISALLOW SEQUENCE TYPE CHANGE COMPLETELY
				#if rtPropHandler.isUsedProperty(reagentType_Name_ID_Map[rTypeName], dnaSeqPropID):
				protDisabled = "disabled"
				rnaDisabled = "disabled"
				dnaDisabled = ""
				noneDisabled = "disabled"
				#else:
					#noneDisabled = ""
					#protDisabled = ""
					#rnaDisabled = ""
					#dnaDisabled = ""
				
			elif categoryProps.has_key(category_Name_Alias_Map["Protein Sequence"]):
				hasProtein = True
				hasRNA = False
				hasDNA = False
				
				protDisplay = "table-row"
				protChecked = "checked"
				
				dnaDisplay = "none"
				dnaChecked = ""
				
				rnaDisplay = "none"
				rnaChecked = ""
				
				noneChecked = ""
				
				protSeqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protein sequence"], category_Name_ID_Map["Protein Sequence"])
				
				# Nov. 19/09: AT KAREN'S REQUEST, DISALLOW SEQUENCE TYPE CHANGE COMPLETELY
				#if rtPropHandler.isUsedProperty(reagentType_Name_ID_Map[rTypeName], protSeqPropID):
				protDisabled = ""
				rnaDisabled = "disabled"
				dnaDisabled = "disabled"
				noneDisabled = "disabled"
				#else:
					#noneDisabled = ""
					#protDisabled = ""
					#rnaDisabled = ""
					#dnaDisabled = ""

				
				#dnaDisabled = "disabled"
				#rnaDisabled = "disabled"
				#protDisabled = ""
				
			elif categoryProps.has_key(category_Name_Alias_Map["RNA Sequence"]):
				hasRNA = True
				hasDNA = False
				hasProtein = False
				
				rnaDisplay = "table-row"
				rnaChecked = "checked"
				
				protDisplay = "none"
				protChecked = ""
				
				dnaDisplay = "none"
				dnaChecked = ""
				
				noneChecked = ""
				
				rnaSeqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["rna sequence"], category_Name_ID_Map["RNA Sequence"])
				
				# Nov. 19/09: AT KAREN'S REQUEST, DISALLOW SEQUENCE TYPE CHANGE COMPLETELY
				#if rtPropHandler.isUsedProperty(reagentType_Name_ID_Map[rTypeName], rnaSeqPropID):
				protDisabled = "disabled"
				rnaDisabled = ""
				dnaDisabled = "disabled"
				noneDisabled = "disabled"
				#else:
					#noneDisabled = ""
					#protDisabled = ""
					#rnaDisabled = ""
					#dnaDisabled = ""

				
				#dnaDisabled = "disabled"
				#protDisabled = "disabled"
				#rnaDisabled = ""
				
			else:
				hasRNA = False
				hasDNA = False
				hasProtein = False
				
				rnaDisplay = "none"
				rnaChecked = ""
				
				protDisplay = "none"
				protChecked = ""
				
				dnaDisplay = "none"
				dnaChecked = ""
				
				noneChecked = "checked"
			
				# Nov. 19/09: AT KAREN'S REQUEST, DISALLOW SEQUENCE TYPE CHANGE COMPLETELY
				protDisabled = "disabled"
				rnaDisabled = "disabled"
				dnaDisabled = "disabled"
				noneDisabled = ""
				
			# DNA
			if categoryProps.has_key(category_Name_Alias_Map["DNA Sequence"]):
				propsList = categoryProps[category_Name_Alias_Map["DNA Sequence"]]
			
			elif categoryProps.has_key(category_Name_Alias_Map["RNA Sequence"]):
				propsList = categoryProps[category_Name_Alias_Map["RNA Sequence"]]
			
			elif categoryProps.has_key(category_Name_Alias_Map["Protein Sequence"]):
				propsList = categoryProps[category_Name_Alias_Map["Protein Sequence"]]
			
			else:
				propsList = []
				
			#print `propList`
			
			content += '''
					<TR>
						<TD>
							<TABLE>
								<TR>
									<TD colspan="4" style="white-space:nowrap; padding-top:10px;">
									'''
									
			content += "<IMG id=\"sequence_expand_img\" SRC=\"" + hostname + "pictures/arrow_collapse.gif\" WIDTH=\"20\" HEIGHT=\"15\" BORDER=\"0\" ALT=\"plus\" class=\"menu-expanded\" style=\"display:inline\" onClick=\"showHideCategory('sequence');\">"
			
			content += "<IMG id=\"sequence_collapse_img\" SRC=\"" + hostname + "pictures/arrow_expand.gif\" WIDTH=\"40\" HEIGHT=\"34\" BORDER=\"0\" ALT=\"plus\" class=\"menu-collapsed\" style=\"display:none\" onClick=\"showHideCategory('sequence');\">"

			content += '''
										<span style="font-weight:bold; color:#0000D2;">Sequence</span>
							
										<DIV ID="category_sequence_section">
											<P><SPAN style="margin-left:25px;">Select one of the following sequence types:</SPAN><BR>
	
											<P>
											'''

			content += "<INPUT style=\"margin-left:25px; font-size:11pt;\" TYPE=\"radio\" ID=\"sequenceRadioNone\" NAME=\"sequenceType\" " + noneChecked + " " + noneDisabled + " onClick=\"hideSequenceSections();\">None"

			content += "<INPUT style=\"margin-left:25px; font-size:11pt;\" TYPE=\"radio\" ID=\"sequenceRadioDNA\" NAME=\"sequenceType\" VALUE=\"DNA Sequence\" " + dnaChecked + " " + dnaDisabled + " onClick=\"showDNASequenceSection();\">DNA Sequence"
			
			content += "<INPUT style=\"margin-left:25px; font-size:11pt;\" TYPE=\"radio\" ID=\"sequenceRadioProtein\" NAME=\"sequenceType\" VALUE=\"Protein Sequence\" " + protChecked + " " + protDisabled + " onClick=\"showProteinSequenceSection();\">Protein Sequence"

			content += "<INPUT style=\"margin-left:25px; font-size:11pt;\" TYPE=\"radio\" ID=\"sequenceRadioRNA\" NAME=\"sequenceType\" VALUE=\"RNA Sequence\" " + rnaChecked + " " + rnaDisabled + " onClick=\"showRNASequenceSection();\">RNA Sequence"

			content += '''
										</DIV>
									</TD>
								</TR>
								'''

			content += "<TR ID=\"dna_sequence_heading_row\" style=\"display:" + dnaDisplay + ";\">"
			
			content += '''
									<TD colspan="4" style="padding-top:10px; white-space:nowrap; padding-left:25px;">
									'''

			content += "<BR><IMG SRC=\"" + hostname + "pictures/star_bullet.gif\" WIDTH=\"10\" HEIGHT=\"10\" BORDER=\"0\" ALT=\"bullet\" style=\"padding-right:8px; vertical-align:middle; padding-bottom:2px;\"><b>DNA Sequence</b>"
			
			content += '''
										<input type="hidden" ID="dnaSequenceCategoryInput" name="category[]" value="sequence_properties">
			
										<input type="hidden" ID="dnaSequenceCategoryDescriptionInput" name="category_descriptor_sequence_properties" value="DNA Sequence">
				
										<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('sequence_properties');">Check All</span>
										'''
			
			temp_ar = []
			temp_ar.append(category_Name_Alias_Map["DNA Sequence"] + "_:_" + prop_Name_Alias_Map["sequence"])
			temp_ar.append(category_Name_Alias_Map["DNA Sequence"] + "_:_" + prop_Name_Alias_Map["protein translation"])
			
			content += "<span class=\"linkShow\" style=\"margin-left:10px;font-size:8pt; font-weight:normal;\" ID=\"uncheckAll\" onClick=\"uncheckAll('sequence_properties', " + `temp_ar` + ");\">Uncheck All</span>"
			
			content += "<span class=\"linkShow\" style=\"margin-left:10px;font-size:8pt; font-weight:normal;\" ID=\"checkAll\" onClick=\"checkAllExclude('" + category_Name_Alias_Map["DNA Sequence"] + "');\">Check All in Use</span>"
			
			content += '''
									</TD>
								</TR>
								'''
								
			content += "<tr ID=\"dna_sequence_props_row\" style=\"display:" + dnaDisplay + ";\">"
			
			content += '''
									<TD colspan="4" style="padding-left:35px;">
										<TABLE cellspacing="4" ID="addReagentTypeSequenceProperties">
											<TR>
											'''
			pCount = 0

			seqCatAlias = category_Name_Alias_Map["DNA Sequence"]
			seqPropSet = pHandler.findPropertiesByCategory(category_Alias_ID_Map[seqCatAlias])
			
			if categoryProps.has_key(seqCatAlias):
				seqPropsList = categoryProps[seqCatAlias]
			else:
				seqPropsList = []
			
			for propID in seqPropSet:	# this is propertyID, NOT propCatID!!
				value = prop_ID_Name_Map[propID]
				seqPropAlias = prop_ID_Alias_Map[propID]
				
				if pCount%4 == 0:
					content += "</TR><TR>"

				if value.lower() == "sequence" or value.lower() == "protein translation" or value.lower() == "length":
					action = "onClick=\"this.checked = true;\""
					readOnly = "DISABLED"
				else:
					readOnly = ""
					action = ""
					
				if seqPropAlias in seqPropsList:
					color = "#00B800"
					
					if seqPropAlias == 'length':
						readOnly = "DISABLED"
				else:
					if seqPropAlias == 'length':
						readOnly = "DISABLED"
					
					color = "#000000"
					
					content += "<INPUT TYPE=\"hidden\" NAME=\"" + seqCatAlias + "_exclude[]\" VALUE=\"" + seqPropAlias + "\">"
				
				content += "<TD style=\"white-space:nowrap; padding-right:10px;\"><INPUT TYPE=\"checkbox\" ID=\"" + seqCatAlias + "_:_" + seqPropAlias + "\" NAME=\"sequence_properties[]\" VALUE=\"" + seqCatAlias + "_:_" + prop_Name_Alias_Map[value.lower()] + "\" " + action + " " + readOnly + "><SPAN ID=\"" + seqCatAlias + "_:_" + prop_Name_Alias_Map[value.lower()] + "_desc_hidden\" style=\"color:" + color + ";\">" + prop_Name_Desc_Map[value.lower()] + "</SPAN></TD>"

				pCount += 1
			
			content += '''
											</TR>
										</TABLE>
									</TD>
								</TR>
								'''
			
			content += "<TR ID=\"dna_sequence_add_new_props_row\" style=\"display:" + dnaDisplay + ";\">"
			
			content += '''
									<td colspan="4" style="padding-left:45px; padding-top:10px; white-space:nowrap;">
										<b>Add new DNA sequence property:<b>&nbsp;&nbsp;
										<INPUT type="text" size="35" ID="seqProp_other" value="" name="seqPropOther" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
	
										<input onclick="updateCheckboxListFromInput('seqProp_other', 'sequence_properties', 'addReagentTypeSequenceProperties', 'updateReagentTypeForm'); document.getElementById('seqProp_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
									</td>
								</tr>
								'''
								
			content += "<TR ID=\"dna_sequence_features_heading_row\" style=\"display:" + dnaDisplay + ";\">"
			
			content += '''
									<TD colspan="4" style="padding-left:25px; padding-top:10px;">
									'''

			content += "<BR><IMG SRC=\"" + hostname + "pictures/star_bullet.gif\" WIDTH=\"10\" HEIGHT=\"10\" BORDER=\"0\" ALT=\"bullet\" style=\"padding-right:8px; vertical-align:middle; padding-bottom:2px;\"><b>DNA Sequence Features</b>"
			
			content += '''
										<input type="hidden" ID="dnaFeaturesCategoryInput" name="category[]" value="dna_sequence_features">
	
										<input type="hidden" ID="dnaFeaturesCategoryDescriptorInput" name="category_descriptor_dna_sequence_features" value="DNA Sequence Features">
				
										<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('dna_sequence_features');">Check All</span>
										<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="uncheckAll" onClick="uncheckAll('dna_sequence_features', []);">Uncheck All</span>
										
										<span class=\"linkShow\" style=\"margin-left:10px;font-size:8pt; font-weight:normal;\" ID=\"checkAll\" onClick=\"checkAllExclude('dna_sequence_features');\">Check All in Use</span>
				
										<BR><span style="font-size:9pt; padding-left:18px; font-weight:normal;">Properties that are linked to a sequence at specific positions and do not exist independently of it</span>
									</TD>
								</TR>
								'''
			
			content += "<TR ID=\"dna_sequence_features_row\" style=\"display:" + dnaDisplay + ";\">"
			
			content += '''
									<TD colspan="4" style="padding-left:35px;">
										<P><TABLE cellspacing="4" ID="addReagentTypeFeatures">
											<TR>
											'''
										
			fCount = 0

			fCatAlias = category_Name_Alias_Map["DNA Sequence Features"]
			
			# Get ALL features currently assigned to this reagent type
			featureSet = pHandler.findPropertiesByCategory(category_Alias_ID_Map[fCatAlias])
			
			# Get the list of basic features, in case none of them have been assigned to this reagent type
			dna_features = DNASequence.getDNASequenceFeatureNames()
			
			# Merge the default list with the actual assigned list
			for df in dna_features:
				df_id = prop_Desc_ID_Map[df]
				
				if not df_id in featureSet:
					featureSet.append(df_id)
			
			if categoryProps.has_key(fCatAlias):
				fList = categoryProps[fCatAlias]
			else:
				fList = []

			dna_features_sorted = []	# list of Descriptions
			dna_features = {}		# description, fID
			
			for fID in featureSet:
				fDescr = prop_ID_Desc_Map[fID]
				
				dna_features_sorted.append(fDescr)
				dna_features[fDescr] = fID
				
			dna_features_sorted.sort()
			
			for x in dna_features_sorted:
				fID = dna_features[x]
			
			#for fID in featureSet:
				fVal = prop_ID_Name_Map[fID]
				fAlias = prop_ID_Alias_Map[fID]
				
				if fAlias in fList:
					color = "#00B800"
					#readOnly = ""
					
					#if fAlias == 'length':
						#readOnly = "DISABLED"
					#else:
					readOnly = ""
				else:
					#if fAlias == 'length':
						#readOnly = "DISABLED"
					#else:
					
					color = "#000000"
					readOnly = ""
					
					content += "<INPUT TYPE=\"hidden\" NAME=\"" + fCatAlias + "_exclude[]\" VALUE=\"" + fAlias + "\">"
			
				if fCount%4 == 0:
					content += "</TR><TR>"

				f_value = category_Name_Alias_Map["DNA Sequence Features"] + "_:_" +  prop_Name_Alias_Map[fVal.lower()]
				#x = prop_Name_Desc_Map[fVal.lower()]

				if fAlias not in featureDescriptors:
					content += "<TD style=\"white-space:nowrap; padding-right:10px;\"><INPUT TYPE=\"checkbox\" ID=\"" + fCatAlias + "_:_" + fAlias + "\" NAME=\"dna_sequence_features[]\" VALUE=\"" + f_value + "\" " + readOnly + "><SPAN style=\"color:" + color + ";\" ID=\"" + fCatAlias + "_:_" + fAlias + "_desc_hidden\">" + x + "</SPAN></TD>"
					fCount += 1

			content += '''
											</TR>
											'''
			
			content += "<tr ID=\"dna_sequence_add_new_features_row\" style=\"display:" + dnaDisplay + ";\">"
			
			content += '''
												<td colspan="4" style="padding-top:10px; white-space:nowrap;">
													<b>Add new DNA sequence feature:<b>&nbsp;&nbsp;
													<INPUT type="text" size="35" ID="features_other" value="" name="featuresOther" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
				
													<input onclick="updateCheckboxListFromInput('features_other', 'dna_sequence_features', 'addReagentTypeFeatures', 'updateReagentTypeForm'); document.getElementById('features_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
												</td>
											</tr>
										</TABLE>
									</TD>
								</TR>
								'''
			
			content += "<TR ID=\"protein_sequence_heading_row\" style=\"display:" + protDisplay + ";\">"
			
			content += '''
									<TD colspan="4" style="padding-left:25px; padding-top:10px; white-space:nowrap">
									'''

			# Protein
			if categoryProps.has_key(category_Name_Alias_Map["Protein Sequence"]):
				propsList = categoryProps[category_Name_Alias_Map["Protein Sequence"]]
			else:
				propsList = []

			content += "<BR><IMG SRC=\"" + hostname + "pictures/star_bullet.gif\" WIDTH=\"10\" HEIGHT=\"10\" BORDER=\"0\" ALT=\"bullet\" style=\"padding-right:8px; vertical-align:middle; padding-bottom:2px;\"><b>Protein Sequence</b>"
			
			content += '''
										<input type="hidden" ID="proteinSequenceCategoryInput" name="category[]" value="protein_sequence_properties">
										
										<input type="hidden" ID="proteinSequenceCategoryDescriptorInput" name="category_descriptor_protein_sequence_properties" value="Protein Sequence">
										
										<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('protein_sequence_properties');">Check All</span>
										
										<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="uncheckAll" onClick="uncheckAll('protein_sequence_properties', []);">Uncheck All</span>
										
										<span class=\"linkShow\" style=\"margin-left:10px;font-size:8pt; font-weight:normal;\" ID=\"checkAll\" onClick=\"checkAllExclude('protein_sequence_properties');\">Check All in Use</span>
									<TD>
								</TR>
								'''

			content += "<tr ID=\"protein_sequence_props_row\" style=\"display:" + protDisplay + ";\">"
			
			content += '''
									<TD colspan="4" style="padding-left:25px;">
										<P><TABLE cellspacing="4" ID="addReagentTypeProteinSequenceProperties">
											<TR>
											'''
			pCount = 0

			protSeqCatAlias = category_Name_Alias_Map["Protein Sequence"]
			protSeqPropSet = pHandler.findPropertiesByCategory(category_Alias_ID_Map[protSeqCatAlias])
			
			for propID in protSeqPropSet:
				value = prop_ID_Name_Map[propID]
				propAlias = prop_ID_Alias_Map[propID]
				
				if propAlias in propsList:
					color = "#00B800"
					readOnly = ""
					
					if propAlias == 'length' or propAlias == 'protein_sequence':
						readOnly = "DISABLED"
					else:
						readOnly = ""
				else:
					if propAlias == 'length':
						readOnly = "DISABLED"
					else:
						color = "#000000"
						readOnly = ""
						
					content += "<INPUT TYPE=\"hidden\" NAME=\"" + protSeqCatAlias + "_exclude[]\" VALUE=\"" + propAlias + "\">"
				
				if pCount%4 == 0:
					content += "</TR><TR>"

				protSeqPropDescr = prop_Name_Desc_Map[value.lower()]

				protSeqPropVal = category_Name_Alias_Map["Protein Sequence"] + "_:_" +  prop_Name_Alias_Map[value.lower()]
				
				content += "<TD style=\"white-space:nowrap; padding-right:10px;\"><INPUT TYPE=\"checkbox\" ID=\"" + protSeqCatAlias + "_:_" + propAlias + "\" NAME=\"protein_sequence_properties[]\" VALUE=\"" + protSeqPropVal + "\" " + readOnly + "><SPAN style=\"color:" + color + ";\" ID=\"" + protSeqCatAlias + "_:_" + propAlias + "_desc_hidden\">" + protSeqPropDescr + "</SPAN></TD>"

				pCount += 1

			content += '''
											</TR>
										</TABLE>
									</TD>
								</TR>
								'''
			
			content += "<tr ID=\"protein_sequence_add_new_props_row\" style=\"display:" + protDisplay + ";\">"
			
			content += '''		
									<td colspan="4" style="padding-left:25px; padding-top:10px; white-space:nowrap;">
										<b>Add new protein sequence property:<b>&nbsp;&nbsp;
										
										<INPUT type="text" size="35" ID="protSeqProp_other" value="" name="protSeqPropOther" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
	
										<input onclick="updateCheckboxListFromInput('protSeqProp_other', 'protein_sequence_properties', 'addReagentTypeProteinSequenceProperties', 'updateReagentTypeForm'); document.getElementById('protSeqProp_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
									</td>
								</tr>
								'''

			content += "<TR ID=\"protein_sequence_features_heading_row\" style=\"display:" + protDisplay + ";\">"

			content += '''
									<TD colspan="4" style="padding-left:20px; padding-top:10px;">
									'''

			content += "<BR><IMG SRC=\"" + hostname + "pictures/star_bullet.gif\" WIDTH=\"10\" HEIGHT=\"10\" BORDER=\"0\" ALT=\"bullet\" style=\"padding-right:8px; vertical-align:middle; padding-bottom:2px;\"><b>Protein Sequence Features</b>"

			content += '''
										<input type="hidden" name="category[]" ID="proteinFeaturesCategoryInput" value="protein_sequence_features">
	
										<input type="hidden" ID="proteinFeaturesCategoryDescriptorInput" name="category_descriptor_protein_sequence_features" value="Protein Sequence Features">
				
										<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('protein_sequence_features');">Check All</span>
										<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="uncheckAll" onClick="uncheckAll('protein_sequence_features', []);">Uncheck All</span>
										
										<span class=\"linkShow\" style=\"margin-left:10px;font-size:8pt; font-weight:normal;\" ID=\"checkAll\" onClick=\"checkAllExclude('protein_sequence_features');\">Check All in Use</span>
				
										<BR><span style="font-size:9pt; padding-left:18px; font-weight:normal;">Properties that are linked to a sequence at specific positions and do not exist independently of it</span>
									</TD>
								</TR>
								'''
			
			content += "<TR ID=\"protein_sequence_features_row\" style=\"display:" + protDisplay + ";\">"
			
			content += '''
									<TD colspan="4" style="padding-left:25px;">
										<P><TABLE cellspacing="4" ID="addReagentTypeProteinFeatures">
											<TR>
											'''
			pCount = 0
			
			pfCatAlias = category_Name_Alias_Map["Protein Sequence Features"]
			protFeatureSet = pHandler.findPropertiesByCategory(category_Alias_ID_Map[pfCatAlias])

			prot_features = ProteinSequence.getProteinSequenceFeatureNames()
			
			for pf in prot_features:
				pf_id = prop_Desc_ID_Map[pf]
				
				if not pf_id in protFeatureSet:
					protFeatureSet.append(pf_id)

			if categoryProps.has_key(pfCatAlias):
				featuresList = categoryProps[pfCatAlias]
			else:
				featuresList = []
			
			#print `featuresList`
			
			prot_features_sorted = []	# list of Descriptions
			prot_features = {}		# description, Feature
			
			# Flip the dictionary to sort alphabetically
			for fID in protFeatureSet:
				fDescr = prop_ID_Desc_Map[fID]
				prot_features_sorted.append(fDescr)
				prot_features[fDescr] = fID
				
			prot_features_sorted.sort()
			
			for z in prot_features_sorted:
				pfID = prot_features[z]
			
			#for pfID in protFeatureSet:
				pfValue = prop_ID_Name_Map[pfID]
				pfAlias = prop_ID_Alias_Map[pfID]
				
				if pfAlias in featuresList:
					color = "#00B800"
				else:
					color = "#000000"
					
					content += "<INPUT TYPE=\"hidden\" NAME=\"" + pfCatAlias + "_exclude[]\" VALUE=\"" + pfAlias + "\">"
				
				if pCount%4 == 0:
					content += "</TR><TR>"
				
				protFeatureValue = category_Name_Alias_Map["Protein Sequence Features"] + "_:_" +  prop_Name_Alias_Map[pfValue.lower()]
				
				#z = prop_Name_Desc_Map[pfValue.lower()]
				
				content += "<TD style=\"white-space:nowrap; padding-right:10px;\"><INPUT TYPE=\"checkbox\" ID=\"" + pfCatAlias + "_:_" + pfAlias + "\" NAME=\"protein_sequence_features[]\" VALUE=\"" + protFeatureValue + "\"><SPAN style=\"color:" + color + ";\" ID=\"" + pfCatAlias + "_:_" + pfAlias + "_desc_hidden\">" + z + "</SPAN></TD>"

				pCount += 1

			content += '''
											</TR>
										</TABLE>
									</TD>
								</TR>
								'''
			
			content += "<tr ID=\"protein_sequence_add_new_features_row\" style=\"display:" + protDisplay + ";\">"
			
			content += '''
									<td colspan="4" style="padding-left:25px; padding-top:10px; white-space:nowrap;">
										<b>Add new Protein sequence feature:<b>&nbsp;&nbsp;
										<INPUT type="text" size="35" ID="protein_features_other" value="" name="proteinFeaturesOther" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
										<input onclick="updateCheckboxListFromInput('protein_features_other', 'protein_sequence_features', 'addReagentTypeProteinFeatures', 'updateReagentTypeForm'); document.getElementById('protein_features_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
									</td>
								</tr>
								'''
			
			content += "<TR ID=\"rna_sequence_heading_row\" style=\"display:" + rnaDisplay + ";\">"
			
			content += '''
									<TD colspan="4" style="padding-left:20px; padding-top:10px; white-space:nowrap">
									'''

			content += "<BR><IMG SRC=\"" + hostname + "pictures/star_bullet.gif\" WIDTH=\"10\" HEIGHT=\"10\" BORDER=\"0\" ALT=\"bullet\" style=\"padding-right:8px; vertical-align:middle; padding-bottom:2px;\"><b>RNA Sequence</b>"
			content += '''	
										<input type="hidden" name="category[]" ID="rnaSequenceCategoryInput" value="rna_sequence_properties">
										
										<input type="hidden" ID="rnaSequenceCategoryDescriptorInput" name="category_descriptor_rna_sequence_properties" value="RNA Sequence">
										<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('rna_sequence_properties');">Check All</span>
										
										<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="uncheckAll" onClick="uncheckAll('rna_sequence_properties', []);">Uncheck All</span>
										
										<span class=\"linkShow\" style=\"margin-left:10px;font-size:8pt; font-weight:normal;\" ID=\"checkAll\" onClick=\"checkAllExclude('rna_sequence_properties');\">Check All in Use</span>
									</TD>
								</TR>
								'''
			
			content += "<tr ID=\"rna_sequence_props_row\" style=\"display:" + rnaDisplay + ";\">"
			
			content += '''
									<TD colspan="4" style="padding-left:25px;">
										<P><TABLE cellspacing="4" ID="addReagentTypeRNASequenceProperties">
											<TR>
											'''
			pCount = 0

			rnaSeqCatAlias = category_Name_Alias_Map["RNA Sequence"]
			rnaSeqPropSet = pHandler.findPropertiesByCategory(category_Alias_ID_Map[rnaSeqCatAlias])

			# nov. 1/09
			if categoryProps.has_key(rnaSeqCatAlias):
				featuresList = categoryProps[rnaSeqCatAlias]
			else:
				featuresList = []

			for propID in rnaSeqPropSet:
				value = prop_ID_Name_Map[propID]
				fAlias = prop_ID_Alias_Map[propID]

				if fAlias == "rna_sequence" or fAlias == "length":
					readonly = "DISABLED"
				else:
					readonly = ""

				# nov 1/09: show used attributes in green
				if fAlias in featuresList:
					color = "#00B800"
					
					if fAlias == 'length':
						readOnly = "DISABLED"
				else:
					color = "#000000"
					
					if fAlias == 'length':
						readOnly = "DISABLED"
					
					content += "<INPUT TYPE=\"hidden\" NAME=\"" + rnaSeqCatAlias + "_exclude[]\" VALUE=\"" + fAlias + "\">"
				
				if pCount%4 == 0:
					content += "</TR><TR>"

				seqPropDescr = prop_Name_Desc_Map[value.lower()]

				rnaSeqProp = category_Name_Alias_Map["RNA Sequence"] + "_:_" + prop_Name_Alias_Map[value.lower()]
				seqPropDescr = prop_Name_Desc_Map[value.lower()]

				content += "<TD style=\"white-space:nowrap; padding-right:10px;\"><INPUT TYPE=\"checkbox\" ID=\"" + rnaSeqCatAlias + "_:_" + fAlias + "\" NAME=\"rna_sequence_properties[]\" VALUE=\"" + rnaSeqProp + "\" " + readonly + "><SPAN style=\"color:" + color + ";\" ID=\"" + rnaSeqCatAlias + "_:_" + fAlias + "_desc_hidden\">" + seqPropDescr + "</SPAN></TD>"

				pCount += 1

			content += '''
											</TR>
										</TABLE>
									</TD>
								</TR>
								'''
			
			content += "<tr ID=\"rna_sequence_add_new_props_row\" style=\"display:" + rnaDisplay + ";\">"
			
			content += '''
									<td colspan="4" style="padding-left:25px; padding-top:10px; white-space:nowrap;">
										<b>Add new RNA sequence property:<b>&nbsp;&nbsp;
										<INPUT type="text" size="35" ID="rnaSeqProp_other" value="" name="rnaSeqPropOther" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
										<input onclick="updateCheckboxListFromInput('rnaSeqProp_other', 'rna_sequence_properties', 'addReagentTypeRNASequenceProperties', 'updateReagentTypeForm'); document.getElementById('rnaSeqProp_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
									</td>
								</tr>
								'''
			
			content += "<TR ID=\"rna_sequence_features_heading_row\" style=\"display:" + rnaDisplay + ";\">"
			
			content += '''
									<TD colspan="4" style="padding-left:20px; padding-top:10px;">
									'''
			
			content += "<BR><IMG SRC=\"" + hostname + "pictures/star_bullet.gif\" WIDTH=\"10\" HEIGHT=\"10\" BORDER=\"0\" ALT=\"bullet\" style=\"padding-right:8px; vertical-align:middle; padding-bottom:2px;\"><b>RNA Sequence Features</b>"
			
			content += '''
										<input type="hidden" name="category[]" ID="rnaFeaturesCategoryInput" value="rna_sequence_features">
	
										<input type="hidden" ID="rnaFeaturesCategoryDescriptorInput" name="category_descriptor_rna_sequence_features" value="RNA Sequence Features">
				
										<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('rna_sequence_features');">Check All</span>
										<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="uncheckAll" onClick="uncheckAll('rna_sequence_features', []);">Uncheck All</span>
										
										<span class=\"linkShow\" style=\"margin-left:10px;font-size:8pt; font-weight:normal;\" ID=\"checkAll\" onClick=\"checkAllExclude('rna_sequence_features');\">Check All in Use</span>
				
										<BR><span style="font-size:9pt; padding-left:18px; font-weight:normal;">Properties that are linked to a sequence at specific positions and do not exist independently of it</span>
									</TD>
								</TR>
								'''
			
			content += "<TR ID=\"rna_sequence_features_row\" style=\"display:" + rnaDisplay + ";\">"
			
			content += '''
									<TD colspan="4" style="padding-left:25px;">
										<P><TABLE cellspacing="4" ID="addReagentTypeRNAFeatures">
											<TR>
											'''
			pCount = 0

			rnaFeaturesCatAlias = category_Name_Alias_Map["RNA Sequence Features"]
			rnaFeatureSet = pHandler.findPropertiesByCategory(category_Alias_ID_Map[rnaFeaturesCatAlias])

			rna_features = RNASequence.getRNASequenceFeatureNames()
			
			for rf in rna_features:
				rf_id = prop_Desc_ID_Map[rf]
				
				if not rf_id in rnaFeatureSet:
					rnaFeatureSet.append(rf_id)

			# nov. 1/09
			if categoryProps.has_key(rnaFeaturesCatAlias):
				featuresList = categoryProps[rnaFeaturesCatAlias]
			else:
				featuresList = []
			
			rna_features_sorted = []	# list of Descriptions
			rna_features = {}		# description, Feature
			
			# Flip the dictionary to sort alphabetically
			for fID in rnaFeatureSet:
				fDescr = prop_ID_Desc_Map[fID]
				rna_features_sorted.append(fDescr)
				rna_features[fDescr] = fID
				
			rna_features_sorted.sort()
			
			for z in rna_features_sorted:
				fID = rna_features[z]
			
			#for fID in rnaFeatureSet:
				fValue = prop_ID_Name_Map[fID]
				pfAlias = prop_ID_Alias_Map[fID]
				
				#print pfAlias

				if pfAlias in featuresList:
					color = "#00B800"
					
					if pfAlias == 'length':
						readOnly = "DISABLED"
				else:
					color = "#000000"
					
					if pfAlias == 'length':
						readOnly = "DISABLED"
					
					content += "<INPUT TYPE=\"hidden\" NAME=\"" + rnaFeaturesCatAlias + "_exclude[]\" VALUE=\"" + pfAlias + "\">"

				if pCount%4 == 0:
					content += "</TR><TR>"

				fProp = category_Name_Alias_Map["RNA Sequence Features"] + "_:_" +  prop_Name_Alias_Map[fValue.lower()]
				
				z = prop_Name_Desc_Map[fValue.lower()]

				content += "<TD style=\"white-space:nowrap; padding-right:10px;\"><INPUT TYPE=\"checkbox\" ID=\"" + rnaFeaturesCatAlias + "_:_" + pfAlias + "\" NAME=\"rna_sequence_features[]\" VALUE=\"" + fProp + "\"><SPAN style=\"color:" + color + ";\" ID=\"" + rnaFeaturesCatAlias + "_:_" + pfAlias + "_desc_hidden\">" + z + "</SPAN></TD>"

				pCount += 1

			content += '''
											</TR>
										</TABLE>
									</TD>
								</TR>
								'''
			
			content += "<tr ID=\"rna_sequence_add_new_features_row\" style=\"display:" + rnaDisplay + ";\">"
			
			content += '''
									<td colspan="4" style="padding-left:25px; padding-top:10px; white-space:nowrap;">
										<b>Add new RNA sequence feature:<b>&nbsp;&nbsp;
										<INPUT type="text" size="35" ID="rna_features_other" value="" name="rnaFeaturesOther" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
	
										<input onclick="updateCheckboxListFromInput('rna_features_other', 'rna_sequence_features', 'addReagentTypeRNAFeatures', 'updateReagentTypeForm'); document.getElementById('rna_features_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
									</td>
								</tr>
							</TABLE>
						</TD>
					</TR>
					'''

			content += '''
					<TR ID="addCategoryRow">
						<TD style="padding-top:10px; white-space:nowrap; padding-left:38px; font-weight:bold;">
							<P><HR><BR>If the property you wish to add does not fit any of the above categories, please add your own category:<BR>
							<P><INPUT type="text" size="35" ID="new_category" value="" name="newCategory" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
	
							<input onclick="addPropertiesCategory('new_category', 'addReagentPropsTbl', 'addCategoryRow', 'updateReagentTypeForm');" value="Add" type="button" style="font-size:10pt;"></INPUT><BR><HR>
						</TD>
					</TR>
					
					<TR>
						<TD>
							<INPUT TYPE="hidden" name="step" value="1">
							'''
							
			content += "<P><INPUT TYPE=\"submit\" name=\"save_rtype\" value=\"Update Selected Properties\" style=\"margin-left:25px; font-size:10pt;\" onClick=\"selectAllPropertyValues(true); enableParents('parentListCurr_" + `reagentType_Name_ID_Map[rTypeName]` + "'); collectReagentTypeProperties('updateReagentTypeForm');\">"
			
			content += '''
								&nbsp;&nbsp;&nbsp;<INPUT TYPE="submit" name="cancel_rtype_modify" value="Cancel" onClick="document.pressed='Cancel'; return confirm('Cancel reagent type modification?');" style="font-size:10pt;">
							</TD>
						</TR>
					</TABLE>
					
					</TD></TR></table>	<!-- keep this!!! -->
				</FORM>
				'''
			
			content += gOut.printFooter()
			
			# Oct. 7/09: don't make reagent type name uppercase (e.g. 'RNAi')
			page_content = content % (hostname + "cgi/reagent_type_request_handler.py", rTypeName, rTypePrefix,  rTypeName, rTypePrefix, programmer_email)
	
			print "Content-type:text/html"		# THIS IS PERMANENT!
			print					# DITTO
			print page_content

		else:
			if step == 2:
				content += '''
					<FORM METHOD="POST" ID="saveAttributeValuesForm" ACTION="%s" onSubmit="return checkEmptyDropdowns('saveAttributeValuesForm');">
	
						<!-- pass current user as hidden form field -->
						<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username"'''
						
				content += "value=\"" + currUser.getFullName() + "\">"
				
				content += '''
						<INPUT TYPE="hidden" NAME="reagentTypeName" VALUE=\"%s\">
						<INPUT TYPE="hidden" NAME="reagentTypePrefix" VALUE=\"%s\">
						
						<TABLE width="760px" cellpadding="4" cellspacing="4">
							<TR>
								<TD colspan=\"4\">
									<TABLE>
										<TR>
											<TH colspan="4" style="font-size:14pt; font-weight:bold; color:#011FF0; padding-top: 10px; padding-top:5px; padding-left:185px;">
												ADD NEW REAGENT TYPE
												
												<BR><CENTER><SPAN style="font-size:10pt; color:gray; text-align:center; font-weight:bold; white-space:nowrap; padding-top:5px;">Step 2 - Set Attribute Values</SPAN></CENTER>
											</TH>
										</TR>
										
										<TR><TD></TD></TR>
									</TABLE>
								</TD>
							</TR>
							
							<TR>
								<TD style="padding-left:25px;">
									<DIV style="padding-left:15px; padding-right:15px; border:3px groove green;">
										<P><span style="color:brown;">For each of the <u><b>new</b></u> properties introduced for reagent type <b>%s</b>, please define its input format:</SPAN><BR>
										
										<UL style="padding-left:15px; padding-right:15px;">
											<LI>You can either define a set of property values to be shown as a dropdown list (for properties with a limited set of defined values), or leave the input field as free text (for more disparate entries).<BR><BR>
			
											<LI>Name, Description and Comments are always entered as free text.  Project IDs are selected from a dropdown list (please refer to the "Project Management" module documentation).<BR><BR>
										
											<LI>External identifiers, such as Accession Number, Ensembl Gene and Transcript IDs, Entrez Gene ID, Gene Symbol are always entered as free text and displayed as a hyperlink to the corresponding external database.<BR><BR>
											
											<LI>Sequence Features are regions at specific positions on a sequence that do not exist independently of it.  They are always selected from a pre-defined list.  Cloning and Restriction sites are provided by the standard REBASE enzyme set.  Other feature value lists (polyA, tag, intron) may be customized.<BR><BR>
										
											<LI>cDNA is always defined in terms of its start and end positions on a DNA sequence.  It is the region of a DNA sequence that gets translated to protein.<BR><BR>
											
											<LI>Molecular Weight and Melting Temperature are automatically calculated for Oligos; for other sequence types they are entered as free text.<BR><BR>
										
											<!-- April 28, 2010: Add option to have multiple values for this property -->
											<LI>Normally, when reagents are created, only one value can be assigned to each property (e.g. a reagent may only have a single Status or Accession Number).  However, in some cases assignment of multiple values to a property may be required, such as tracking an antibody that has multiple recognition species.  You may indicate that an attribute can potentially have multiple values by checking the "Multiple" checkbox under the corresponding property name.  When you create reagents of this type, the input form will include the option to specify multiple values for these properties.
										</UL>
									</DIV>
								</TD>
							</TR>
							'''
							
							#<TR>
								#<TD style="border: 1px groove black; padding:7px; text-align:justify;">
									#<u><b>Please note</b></u>: The term <b>'Sequence Features'</b> refers to <u>regions at specific positions</u> on a sequence in forward or reverse orientation (e.g. PolyA Tail, Tag, Selectable Marker).  Their values <b>are always selected from a pre-defined list.</b>  Properties that describe the entire sequence rather than portions of it should be stored under a different category (e.g. 'Annotations' or 'Classifiers').
								#</TD>
							#</TR>
			else:
				content += '''
					<FORM METHOD="POST" ID="saveAttributeValuesForm" ACTION="%s" onSubmit="return checkEmptyDropdowns('saveAttributeValuesForm');">
	
						<!-- pass current user as hidden form field -->
						<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username"'''
						
				content += "value=\"" + currUser.getFullName() + "\">"

				# Dec. 11/09: pass hidden reagent type to JS
				content += "<INPUT type=\"hidden\" ID=\"reagent_type_name\" VALUE=\"" + rTypeName + "\">"
				
				content += '''
						<INPUT TYPE="hidden" NAME="reagentTypeName" VALUE=\"%s\">
						<INPUT TYPE="hidden" NAME="reagentTypePrefix" VALUE=\"%s\">
						
						<TABLE width="760px" cellpadding="4" cellspacing="4">
							<TR>
								<TD colspan=\"4\">
									<TABLE>
										<TR>
											<TH colspan="4" style="font-size:14pt; font-weight:bold; color:#011FF0; padding-top: 10px; padding-top:5px; padding-left:185px;">
												UPDATE REAGENT TYPE
												'''
				content += rTypeName
				
				content += '''
												
												<BR><CENTER><SPAN style="font-size:10pt; color:gray; text-align:center; font-weight:bold; white-space:nowrap; padding-top:5px;">Step 2 - Edit Attribute Values</SPAN></CENTER>
											</TH>
										</TR>
										
										<TR><TD></TD></TR>
									</TABLE>
								</TD>
							</TR>
							
							<TR>
								<TD>
									<BR>For each of the <u><b>new</b></u> properties introduced for reagent type <b>%s</b>, please define its input format:<BR>
									
									<P>You can either define a set of property values to appear as a dropdown list (for properties with more than one value), or leave the input field as free-text (for single-valued properties).<BR>
	
									<P>E.g. The 'Name' field would always be a text field, whereas 'Cloning Sites' are selected from a pre-defined list of values.
								</TD>
							</TR>
							
							<TR>
								<TD style="border: 1px groove black; padding:7px; text-align:justify;">
									<u><b>Please note</b></u>: The term <b>'Sequence Features'</b> refers to <u>regions at specific positions</u> on a sequence in forward or reverse orientation (e.g. PolyA Tail, Tag, Selectable Marker).  Their values <b>are always selected from a pre-defined list.</b>  Properties that describe the entire sequence rather than portions of it should be stored under a different category (e.g. 'Annotations' or 'Classifiers').
								</TD>
							</TR>
							'''
			#print "Content-type:text/html"
			#print
			#print `categoryProps`
			#print `temp_categories`
			
			# Nov. 30/09: What about novel categories??
			
			# Change Aug. 4/09: output categories in order - Oct. 8/09: USE sorted()
			for categoryOrder in sorted(temp_categories.keys()):
				catAlias = temp_categories[categoryOrder]
				#print catAlias
				
				# Update June 17/09: for novel categories, add check 'if exists in alias map', get error otherwise
				if category_Alias_ID_Map.has_key(catAlias):
					categoryID = category_Alias_ID_Map[catAlias]
					currCategoryProps = pHandler.findPropertiesByCategory(category_Alias_ID_Map[catAlias])	# moved here Nov. 16/09
				else:
					categoryID = -1
					currCategoryProps = []
				
				content += "<INPUT TYPE=\"hidden\" NAME=\"category[]\" VALUE=\"" + catAlias.replace("'", "\'") + "\">"
				content += "<INPUT TYPE=\"hidden\" NAME=\"categoryID[]\" VALUE=\"" + `categoryID` + "\">"
				content += "<input type=\"hidden\" name=\"category_descriptor_" + catAlias.replace("'", "\'") + "\" value=\"" + catDescrDict[catAlias] + "\">"
				
				propsList = categoryProps[catAlias]
				#print `propsList`

				# nov. 1/09: add descriptors
				if propsList.has_key("tag") and not propsList.has_key("tag_position"):
					propsList["tag_position"] = "Tag Position"
					tmp_list = {}
					tmp_list["tag_position"] = "Tag Position"
					categoryProps[catAlias] = tmp_list
				
				if propsList.has_key("promoter") and not propsList.has_key("expression_system"):
					propsList["expression_system"] = "Expression System"
					tmp_list = {}
					tmp_list["expression_system"] = "Expression System"
					categoryProps[catAlias] = tmp_list

				rTypePropSorted = {}		# order => [list of props]

				for pAlias in propsList.keys():
					#print pAlias
					
					## oct. 31/09: no, look by description
					#propID = pHandler.findPropIDByDescription(propsList[pAlias])

					if prop_Alias_ID_Map.has_key(pAlias):
						propID = prop_Alias_ID_Map[pAlias]
					else:
						propID = -1
					
					pName = propsList[pAlias]
					#print propID
					#print pName
					
					# Change April 14, 2010: property ordering is now linked to a specific property within a specific category for a specific reagent, i.e. it is now a column in ReagentTypeAttributes_tbl and is associated with ReagentPropertyCategories_tbl.propCatID as opposed to ReagentPropType_tbl.propertyID
					if reagentType_Name_ID_Map.has_key(rTypeName):
						rTypeID = reagentType_Name_ID_Map[rTypeName]
						tmpPropID = pHandler.findReagentPropertyInCategoryID(propID, categoryID)
						#print tmpPropID
						
						if rTypeID and rTypeID > 0 and tmpPropID and tmpPropID > 0:
							pOrder = rtPropHandler.getReagentTypePropertyOrdering(rTypeID, tmpPropID)
						else:
							#pOrder = sys.maxint
							pOrder = len(propsList)
							#print pOrder
					else:
						#pOrder = sys.maxint
						pOrder = len(propsList)
					
					#print pOrder
	
					if rTypePropSorted.has_key(pOrder):
						tmp_order_list = rTypePropSorted[pOrder]
						# don't do any more sorting here!
					else:
						tmp_order_list = []
	
					tmp_order_list.append(pName)
					rTypePropSorted[pOrder] = tmp_order_list
				
				rTypePropSorted.keys().sort()
				#print `rTypePropSorted`
				
				#print "Category " + catAlias + ", props " + `propsList`
				#print `ignoreList`
				#print `propsList`
				#print `propsList.keys()`

				# oct. 31/09
				propDescID_list = {}
				
				# June 26/09: Output by categories - Repetitive code but the most robust way to determine unique new category headings
				for pAlias in propsList.keys():
					#print "after: " + pAlias
					pName = propsList[pAlias]
					#print pName
					
					# try to find propID from description
					tmpPropID = pHandler.findPropIDByDescription(pName, True)
					#print tmpPropID

					propDescID_list[pName] = tmpPropID	# oct. 31/09
				
					#if (tmpPropID < 0) or (catAlias not in category_Alias_ID_Map.keys()) or (category_Alias_ID_Map.has_key(catAlias) and tmpPropID > 0 and not pHandler.existsPropertyInCategory(tmpPropID, category_Alias_ID_Map[catAlias])):
					content += "<TR>"
					content += "<TD style=\"font-weight:bold; color:#0000D2; padding-top:25px; padding-left:15px; white-space:nowrap;\">"
					
					#content += "<IMG SRC=\"" + hostname + "pictures/star_bullet.gif\" WIDTH=\"10\" HEIGHT=\"10\" BORDER=\"0\" ALT=\"bullet\"  style=\"padding-right:8px; vertical-align:middle; padding-bottom:2px;\">"
					
					# Aug. 10/09: Make collapsible sections
					#if categoryID == 1:
					display_plus = "inline"
					display_minus = "none"
					#else:
						#display_plus = "none"
						#display_minus = "inline"
					
					content += "<IMG id=\"" + `categoryID` + "_expand_img\" SRC=\"" + hostname + "pictures/arrow_collapse.gif\" WIDTH=\"20\" HEIGHT=\"15\" BORDER=\"0\" ALT=\"plus\" class=\"menu-expanded\" style=\"display:" + display_plus + "\" onClick=\"showHideCategory('" + `categoryID` + "');\">"

					content += "<IMG id=\"" + `categoryID` + "_collapse_img\" SRC=\"" + hostname + "pictures/arrow_expand.gif\" WIDTH=\"40\" HEIGHT=\"34\" BORDER=\"0\" ALT=\"plus\" class=\"menu-collapsed\" style=\"display:" + display_minus + "\" onClick=\"showHideCategory('" + `categoryID` + "');\">"

					content += catDescrDict[catAlias]
					
					content += "</TD>"
					content += "</TR>"
					break		# need here to only print category names once
				
				# Aug. 10/09: make collapsible
				content += "<TR>"
				content += "<TD ID=\"category_" + `categoryID` + "_section\" style=\"display:" + display_plus + "\" style=\"padding-left:35px;\">"
				content += "<TABLE style=\"background-color:#F5F5DC; width:750px; margin-left:35px; padding:6px;\" cellspacing=\"2\">"
				
				for pOrd in rTypePropSorted.keys():
					pNames = rTypePropSorted[pOrd]		# these are in fact DESCRIPTIONS, user input!
					#print `pNames`
					
					for pName in pNames:
						#print pName
						
						# Oct. 31/09: LOOK BY DESCRIPTION, DON'T RELY ON MAPS HERE!!!!!!
						pAlias = cat_props_dict_flip[catAlias][pName]
						#pAlias = cat_props_dict_flip[pName]
						#print pAlias
						
						if pAlias == rTypeName + " Type":
							pAlias = rTypeName + "_type"
						
						tmp_val = (catAlias + "_:_" + pAlias).replace("%", "%%")
						#print tmp_val
						
						# pass ALL properties through form, BUT on the property values input page print only NEW ones
						# REMOVED Feb. 8/10 - deletes the wrong field!!
						#content += "<input type=\"hidden\" ID=\"" + tmp_val + "_input\" name=\"" + catAlias.replace("'", "\'") + "[]\" value=\"" + pAlias + "\">"
						
						## Nov. 6/09: Do the replacement here!!!!!
						#content += "<input type=\"hidden\" ID=\"remove_" + tmp_val.replace("'", "\\'") + "_input\" name=\"remove_" + tmp_val + "_prop\">"
						
						if pAlias.find(catAlias + "_:_") == 0:
							pAlias = pAlias[len(catAlias + "_:_"):]
						
						if pAlias.lower() not in setFormatList:
							isDisabled = ""
						else:
							isDisabled = "DISABLED"
							
						# try to find propID from description
						#print pName
						tmpPropID = pHandler.findPropIDByDescription(pName, True)
						
						rTypeAttrID = -1
						
						if tmpPropID and tmpPropID > 0:
							propCatID = pHandler.findReagentPropertyInCategoryID(tmpPropID, categoryID)
						else:
							propCatID = -1
						
						if step == 3:
							# Aug. 4/09: Modification - Prefill with existing values
							#print "pc " + `propCatID`
							
							if propCatID and propCatID > 0:
								rTypeID = reagentType_Name_ID_Map[rTypeName]
								rTypeAttrID = rtPropHandler.findReagentTypeAttributeID(rTypeID, propCatID)
								#print "atr " + `rTypeAttrID`
								
								if rtPropHandler.isUsedProperty(rTypeID, propCatID):
									isDisabled = "DISABLED"
									
								# Update Oct. 13/09
								allSetValues = sHandler.findAllPropSetValues(propCatID)
								currSetValues = sHandler.findReagentTypeAttributeSetValues(rTypeAttrID)
								#print `currSetValues`
							else:
								allSetValues = []
								currSetValues = []
						else:
							# Creation - no attribute values assigned to reagent type yet
							if propCatID and propCatID > 0:
								allSetValues = sHandler.findAllPropSetValues(propCatID)
								currSetValues = []
							else:
								allSetValues = []
								currSetValues = []
						
						# May 11, 2010: Pre-check if set!!
						hl_chkd = ""
						m_chkd = ""
						o_chkd = ""
						
						if rTypeAttrID:
							if rTypeAttrID > 0:
								if rtPropHandler.isHyperlink(rTypeAttrID):
									hl_chkd = "CHECKED"
						
								if rtPropHandler.isMultiple(rTypeAttrID):
									m_chkd = "CHECKED"
									
								if rtPropHandler.isCustomizeable(rTypeAttrID):
									o_chkd = "CHECKED"

						content += "<TR ID=\"" + tmp_val + "_prop_row\">"
						
						content += "<TD style=\"padding-left:15px; background-color:#FFFFFF; width:750px;\">"
						
						content += "<IMG SRC=\"" + hostname + "pictures/sphere_bullet.jpeg\" WIDTH=\"5\" HEIGHT=\"5\" BORDER=\"0\" ALT=\"sphere\" style=\"padding-right:6px; vertical-align:middle; padding-bottom:2px;\"></IMG>"
						
						content += "<span style=\"font-size:10pt; font-weight:bold\">" + pName + "</span>"
						
						# April 9, 2010: ORDERING
						content += "&nbsp;&nbsp;&nbsp;"
						
						if propCatID > 0:
							content += "<SELECT ID=\"prop_order_list_" + `propCatID` + "\" NAME=\"propOrder_" + `propCatID` + "\">"
						else:
							content += "<SELECT ID=\"prop_order_list_" + `propCatID` + "\" NAME=\"propOrder_" + tmp_val + "\">"
						
						pnum = 1
						
						if step == 3:
							pOrd = rtPropHandler.getReagentTypePropertyOrdering(rTypeID, propCatID)
							
							# when modifying, get ALL properties w/in this category - April 21,10: PLUS any newly added properties!
							
							numProps = len(rtPropHandler.findReagentTypeAttributeNamesByCategory(rTypeID, categoryID))
							
							# run through the props loop again to figure out the length of the ordering list
							for tmp_palias in propsList.keys():
								tmp_pname = propsList[tmp_palias]
								tmp_pID = pHandler.findPropIDByDescription(tmp_pname, True)
							
								if tmp_pID and tmp_pID > 0:
									tmp_pc_id = pHandler.findReagentPropertyInCategoryID(tmp_pID, categoryID)
									
									if not rtPropHandler.existsReagentTypeAttribute(rTypeID, tmp_pc_id):
										numProps += 1
								else:
									numProps += 1
						else:
							pOrd = 0
							numProps = len(propsList)	# here it's whatever came in
						
						#print pOrd
						
						#print "Content-type:text/html"
						#print
						#print numProps
						
						while pnum <= numProps:
							#print pnum
							if pOrd > 0 and pOrd == pnum:
								content += "<OPTION SELECTED VALUE=\"" + `pnum` + "\">" + `pnum` + "</OPTION>"
								
							else:
								content += "<OPTION VALUE=\"" + `pnum` + "\">" + `pnum` + "</OPTION>"
								
							pnum += 1
							
						content += "</SELECT>"
						
						if catAlias != 'dna_sequence_features' and catAlias != 'protein_sequence_features' and catAlias != 'rna_sequence_features':
							
							if step == 3:
								setToCheck = utils.merge(currSetValues, allSetValues)
							else:
								setToCheck = allSetValues
							
							if pName in hyperlinkExceptionsList:
								content += "<INPUT TYPE=\"hidden\" ID=\"no_hl_" + tmp_val + "\" VALUE=\"1\">"
							
							if pName in multipleExceptionslist:
								content += "<INPUT TYPE=\"hidden\" ID=\"no_mult_" + tmp_val + "\" VALUE=\"1\">"

							# May 31, 2010
							if rTypeAttrID > 0:
								if rtPropHandler.isDropdown(rTypeAttrID):
									is_dropdown = True
								else:
									is_dropdown = False

							elif len(setToCheck) > 0:
								is_dropdown = True

							else:
								is_dropdown = False
							
							# changed May 31, 2010
							#if len(setToCheck) == 0:
							if not is_dropdown:
								# project is an exception, b/c it's not selected from standard dropdowns table, but it's not a freetext either, dropdown option needs to be selected
								if pName != prop_Name_Desc_Map["packet id"]:
									
									if pName not in hyperlinkExceptionsList:
										# hyperlink
										content += "<span ID=\"make_hl_" + tmp_val + "\">&nbsp;&nbsp;<INPUT TYPE=\"checkbox\" NAME=\"hyperlink_" + tmp_val + "\" " + hl_chkd + ">Make hyperlink</span>"
									else:
										content += "<span ID=\"make_hl_" + tmp_val + "\" style=\"display:none;\">&nbsp;&nbsp;<INPUT TYPE=\"checkbox\" NAME=\"hyperlink_" + tmp_val + "\" " + hl_chkd + ">Make hyperlink</span>"
										
									content += "<span ID=\"make_mult_" + tmp_val + "\" style=\"display:none;\">&nbsp;&nbsp;<INPUT TYPE=\"CHECKBOX\" ID=\"mult_cb_" + tmp_val + "\" NAME=\"isMultiple_" + tmp_val + "\" " + m_chkd + ">Allow multiple</span>"
									
									content += "<span ID=\"allow_other_" + tmp_val + "\" style=\"display:none;\">&nbsp;&nbsp;<INPUT TYPE=\"CHECKBOX\" ID=\"customize_cb_" + tmp_val + "\" NAME=\"allowOther_" + tmp_val + "\" " + o_chkd + ">Allow Other</span>"
								else:
									# but  for project, DON'T give option to hyperlink, customize or make multiple!
									content += "<span ID=\"make_hl_" + tmp_val + "\" style=\"display:none;\">&nbsp;&nbsp;<INPUT TYPE=\"checkbox\" NAME=\"hyperlink_" + tmp_val + "\" " + hl_chkd + ">Make hyperlink</span>"
									
									content += "<span ID=\"make_multiple_" + tmp_val + "\" style=\"display:none;\">&nbsp;&nbsp;<INPUT TYPE=\"CHECKBOX\" ID=\"mult_cb_" + tmp_val + "\" NAME=\"isMultiple_" + tmp_val + "\" " + m_chkd + ">Allow multiple</span>"
									
									content += "<span ID=\"allow_other_" + tmp_val + "\" style=\"display:none;\">&nbsp;&nbsp;<INPUT TYPE=\"CHECKBOX\" ID=\"customize_cb_" + tmp_val + "\" NAME=\"allowOther_" + tmp_val + "\" " + o_chkd + ">Allow Other</span>"
							# DROPDOWN
							else:
								if pName not in multipleExceptionslist:
									if pName not in hyperlinkExceptionsList:
										content += "<span ID=\"make_hl_" + tmp_val + "\" style=\"display:none;\">&nbsp;&nbsp;<INPUT TYPE=\"checkbox\" NAME=\"hyperlink_" + tmp_val + "\" " + hl_chkd + ">Make hyperlink</span>"
									else:
										content += "<span ID=\"make_hl_" + tmp_val + "\" style=\"display:none;\">&nbsp;&nbsp;<INPUT TYPE=\"checkbox\" NAME=\"hyperlink_" + tmp_val + "\" " + hl_chkd + ">Make hyperlink</span>"
								
									# multiple
									
									# May 19, 2010: if values have been assigned, allow going from multiple to single but not vice versa
									# May 25, 2010: Differentiate b/w creation and modification
									if step == 3:	# modification
										if rtPropHandler.isUsedProperty(rTypeID, propCatID):
											if pName == prop_Name_Desc_Map["alternate id"]:
												mult_disabled = "DISABLED"
												m_chkd = "CHECKED"
											elif not rtPropHandler.isMultiple(rTypeAttrID):
												mult_disabled = ""
											else:
												mult_disabled = "DISABLED"
										else:
											if pName == prop_Name_Desc_Map["alternate id"]:
												mult_disabled = "DISABLED"
												m_chkd = "CHECKED"
											else:
												mult_disabled = ""
									else:
										if pName == prop_Name_Desc_Map["alternate id"]:
											mult_disabled = "DISABLED"
											m_chkd = "CHECKED"
										else:
											mult_disabled = ""
								
									content += "<span ID=\"make_mult_" + tmp_val + "\">&nbsp;&nbsp;<INPUT TYPE=\"CHECKBOX\" " + mult_disabled + " ID=\"mult_cb_" + tmp_val + "\" NAME=\"isMultiple_" + tmp_val + "\" " + m_chkd + ">Allow multiple</span>"
									
									# June 15, 2010: Alternate IDs are printed with 'other' option anyway, so even if user does not check 'allow other' here, still 'Other' checkbox will be printed in the Alt. ID list - so just don't print it here to avoid confusion
									if pName != prop_Name_Desc_Map["alternate id"]:
										content += "<span ID=\"allow_other_" + tmp_val + "\">&nbsp;&nbsp;<INPUT TYPE=\"CHECKBOX\" ID=\"customize_cb_" + tmp_val + "\" NAME=\"allowOther_" + tmp_val + "\" " + o_chkd + ">Allow Other</span>"
									else:
										content += "<span ID=\"allow_other_" + tmp_val + "\">&nbsp;&nbsp;<INPUT TYPE=\"CHECKBOX\" DISABLED CHECKED ID=\"customize_cb_" + tmp_val + "\" NAME=\"allowOther_" + tmp_val + "\">Allow Other</span>"
								else:
									if pName not in hyperlinkExceptionsList:
										content += "<span ID=\"make_hl_" + tmp_val + "\" style=\"display:none;\">&nbsp;&nbsp;<INPUT TYPE=\"checkbox\" NAME=\"hyperlink_" + tmp_val + "\" " + hl_chkd + ">Make hyperlink</span>"

									else:
										content += "<span ID=\"make_hl_" + tmp_val + "\" style=\"display:none;\">&nbsp;&nbsp;<INPUT TYPE=\"checkbox\" NAME=\"hyperlink_" + tmp_val + "\" " + hl_chkd + ">Make hyperlink</span>"
								
									# Multiples are allowed
									content += "<span ID=\"make_mult_" + tmp_val + "\" style=\"display:none;\">&nbsp;&nbsp;<INPUT TYPE=\"CHECKBOX\" " + isDisabled + " ID=\"mult_cb_" + tmp_val + "\" NAME=\"isMultiple_" + tmp_val + "\" " + m_chkd + ">Allow multiple</span>"
									
									# 'Other' - show disabled for Alt. ID, normal for rest
									if pName != prop_Name_Desc_Map["alternate id"]:
										content += "<span ID=\"allow_other_" + tmp_val + "\">&nbsp;&nbsp;<INPUT TYPE=\"CHECKBOX\" ID=\"customize_cb_" + tmp_val + "\" NAME=\"allowOther_" + tmp_val + "\" " + o_chkd + ">Allow Other</span>"
									else:
										content += "<span ID=\"allow_other_" + tmp_val + "\">&nbsp;&nbsp;<INPUT TYPE=\"CHECKBOX\" CHECKED DISABLED ID=\"customize_cb_" + tmp_val + "\" NAME=\"allowOther_" + tmp_val + "\">Allow Other</span>"
						# June 7, 2010: Features
						else:
							#print "Content-type:text/html"
							#print
							#print "this is a feature? " + tmp_val
							
							if pAlias not in setFormatList:
								content += "<span ID=\"allow_other_" + tmp_val + "\">&nbsp;&nbsp;<INPUT TYPE=\"CHECKBOX\" ID=\"customize_cb_" + tmp_val + "\" NAME=\"allowOther_" + tmp_val + "\" " + o_chkd + ">Allow Other</span>"
							
						# Modification - check if property or its values are used by reagents of this type
						if step == 3:
							rTypeID = reagentType_Name_ID_Map[rTypeName]
							
							# Check if used - if property is a descriptor need special code below
							if pName.lower() == "tag position" or pName.lower() == "expression system":
								if pName.lower() == "tag position":
									tmpPropID = prop_Name_ID_Map["tag"]
								elif pName.lower() == "expression system":
									tmpPropID = prop_Name_ID_Map["promoter"]
								
								tmpPropCatID = pHandler.findReagentPropertyInCategoryID(tmpPropID, categoryID)
								
								#tmp_val = (catAlias + "_:_" + pAlias).replace("'", "\\'")
								
								#actn = "escape(\'" + tmp_val.replace("\'", "&#39;") + "_prop_row\')"
								
								actn = tmp_val.replace("'", "\\'") + "_prop_row"
								
								if not rtPropHandler.propertyUsedForReagentType(rTypeID, tmpPropCatID, True) and pAlias not in featureDescriptors:
									content += "<SPAN class=\"linkShow\" style=\"font-size:7pt; margin-left:12px; font-weight:normal;\" onClick=\"deleteReagentTypeAttribute('" + actn + "', 'saveAttributeValuesForm');\">Remove</SPAN>"
							else:
								#print `mandatoryProps`
								
								# Rest of properties are normal
								if pAlias != rTypeName + "_type" and pAlias not in mandatoryProps and not rtPropHandler.propertyUsedForReagentType(rTypeID, propCatID) and pAlias not in featureDescriptors:
									#tmp_val = (catAlias + "_:_" + pAlias).replace("'", "\\'")
								
									# Nov. 6/09: Do the replacement here!!!!!
									actn = tmp_val.replace("'", "\\'") + "_prop_row"
									#print actn
									
									content += "<SPAN class=\"linkShow\" style=\"font-size:7pt; margin-left:12px; font-weight:normal;\" onClick=\"deleteReagentTypeAttribute('" + actn + "', 'saveAttributeValuesForm');\">Remove</SPAN>"
								#else:
									#print "what is this: " + pAlias
								
						# Creation - may remove if not mandatory
						else:
							if pAlias not in mandatoryProps and pAlias not in featureDescriptors:
								#tmp_val = (catAlias + "_:_" + pAlias).replace("'", "\\'")
								
								#actn = "escape(\'" + tmp_val.replace("\'", "&#39;") + "_prop_row\')"
								actn = tmp_val.replace("'", "\\'") + "_prop_row"
								#print actn
								
								content += "<SPAN class=\"linkShow\" style=\"font-size:7pt; margin-left:12px; font-weight:normal;\" onClick=\"deleteReagentTypeAttribute('" + actn + "', 'saveAttributeValuesForm');\">Remove</SPAN>"

						# Moved down here on Feb. 8/10 - up in the loop above this field was hidden inside its preceding row; therefore, when the previous property was removed, the next one after it was removed too.  Keep it here!!!
						content += "<input type=\"hidden\" ID=\"" + tmp_val + "_input\" name=\"" + catAlias.replace("'", "\'") + "[]\" value=\"" + pAlias + "\">"
						
						content += "<BR><P>"
						
						#print pAlias
						#print `allSetValues`
						#print `len(currSetValues)`
						
						# Nov. 5/09
						if step == 3:
							setToCheck = utils.merge(currSetValues, allSetValues)
						else:
							setToCheck = allSetValues
						
						if catAlias != 'dna_sequence_features' and catAlias != 'protein_sequence_features' and catAlias != 'rna_sequence_features':
							content += "&nbsp;&nbsp;&nbsp;&nbsp;Do you want its value(s) to be entered as free text or selected from a list?<BR><P>&nbsp;&nbsp;&nbsp;"
							
							# May 31, 2010
							#if len(setToCheck) == 0:
							if not is_dropdown:
								actn = tmp_val.replace("'", "\\'")
								
								#print "num_vals_" +  tmp_val + `len(setToCheck)`
								
								# project ID won't return a set, but it has to be a dropdown
								if pName == prop_Name_Desc_Map["packet id"]:
									content += "<INPUT TYPE=\"radio\" ID=\"input_format_radio_text_" + tmp_val + "\" " + isDisabled + " NAME=\"num_vals_" +  tmp_val + "\" VALUE=\"freeform\" onClick=\"showHideAddPropertyValuesInput('" + actn + "');\">Free text"
					
									content += "&nbsp;&nbsp;"
					
									content += "<INPUT TYPE=\"radio\" ID=\"input_format_radio_list_" + tmp_val + "\" " + isDisabled + " CHECKED NAME=\"num_vals_" +  tmp_val + "\" VALUE=\"predefined\" onClick=\"showHideAddPropertyValuesInput('" + actn + "');\">Dropdown<BR>"
									
								else:
									content += "<INPUT TYPE=\"radio\" ID=\"input_format_radio_text_" + tmp_val + "\" " + isDisabled + " NAME=\"num_vals_" +  tmp_val + "\" VALUE=\"freeform\" CHECKED onClick=\"showHideAddPropertyValuesInput('" + actn + "');\">Free text"
					
									content += "&nbsp;&nbsp;"
					
									content += "<INPUT TYPE=\"radio\" ID=\"input_format_radio_list_" + tmp_val + "\" " + isDisabled + " NAME=\"num_vals_" +  tmp_val + "\" VALUE=\"predefined\" onClick=\"showHideAddPropertyValuesInput('" + actn + "');\">Dropdown<BR>"
									
								content += "<DIV ID=\"propertyValuesInputDiv_" + tmp_val + "\" style=\"display:none; margin-left:15px;\">"
								
								content += "<span style=\"font-size:9pt; margin-left:5px;\">Use the textbox and 'Add'/'Remove' buttons below to populate the list:</span>"
								
								content += "<TABLE style=\"margin-left:10px; margin-top:10px;\">"
								content += "<TR>"
								
								content += "<TD style=\"padding-bottom:5px;\">"
								
								content += "<span style=\"font-size:9pt; margin-left:8px;\">Values currently assigned to</SPAN><BR><SPAN style=\"font-size:9pt; margin-left:8px;\">reagent type <b>" + rTypeName + "</b>:</span><BR><P>"
								
								content += "</TD>"
								
								content += "<TD></TD>"
								
								content += "<TD>"
								content += "<span style=\"font-size:9pt; margin-left:10px;\">Additional <b>" + pName + "</b> values available in OpenFreezer:</SPAN><BR><P>"
								content += "</TD></TR>"
								
								content += "<TR><TD>"
								
								content += "<SELECT SIZE=\"10\" MULTIPLE style=\"margin-left:8px;\" ID=\"propertyValuesInputList_" + tmp_val + "\" NAME=\"propertyValues_" + tmp_val + "\"></SELECT>"
								
								content += "</TD>"
								
								actn1 = "escape(\'propertyValuesInputList_" + tmp_val.replace("'", "\\'") + "\')"
								actn2 = "escape(\'propertyValuesPoolList_" + tmp_val.replace("'", "\\'") + "\')"
								
								content += "<TD>"
								content += "&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"BUTTON\" VALUE=\"-->\" onClick=\"moveListElements(" + actn1 + ", " + actn2 + ", 'false')\"><BR><BR>&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"BUTTON\" VALUE=\"<--\" onClick=\"moveListElements(" + actn2 + ", " + actn1 + ", 'false')\">"
								content += "</TD>"
								
								content += "<TD>"
								content += "<SELECT MULTIPLE style=\"margin-left:8px;\" SIZE=\"10\" ID=\"propertyValuesPoolList_" + tmp_val + "\" NAME=\"allVals_" + tmp_val + "\">"
								
								for setValue in allSetValues:
									if setValue not in currSetValues:
										content += "<OPTION value=\"" + setValue + "\">" + setValue + "</OPTION>"
							
								content += "</SELECT>"
								content += "</TD>"
								
								content += "</TR>"
								
								content += "<TR>"
								content += "<TD colspan=\"2\">"
								
								content += "<INPUT TYPE=\"checkbox\" style=\"margin-top:5px; margin-left:8px; font-size:8pt;\" onClick=\"selectAll(this.id, " + actn1 + ", false)\" id=\"add_all_chkbx_" + tmp_val + "_inputList\"> Select All</INPUT>"
								
								content += "</TD>"
								
								content += "<TD>"
								
								content += "<INPUT TYPE=\"checkbox\" style=\"margin-top:5px; margin-left:8px; font-size:8pt;\" onClick=\"selectAll(this.id, " + actn2 + ", false)\" id=\"add_all_chkbx_" + tmp_val + "_poolList\"> Select All</INPUT>"
								
								content += "</TD>"
								content += "</TR>"
								
								content += "</TABLE>"
								
								content += "<BR>"
								
								content += "<INPUT TYPE=\"TEXT\" style=\"margin-left:18px;\" id=\"addPropertyValue_" + tmp_val + "_txt\" onKeyPress=\"return disableEnterKey(event);\">"
								
								#actn = "escape(\'" + tmp_val.replace("'", "\\'") + "\')"
								#print actn
							
								actn1 = "escape(\'addPropertyValue_" + tmp_val.replace("'", "\\'") + "_txt\')"
								#print actn1
								
								actn2 = "escape(\'propertyValuesInputList_" + tmp_val.replace("'", "\\'") + "\')"
								#print actn2
							
								content += "&nbsp;<INPUT TYPE=\"BUTTON\" id=\"addPropertyValue_" + tmp_val + "_btn\" onClick=\"addElementToListFromInput(" + actn1 + ", " + actn2 + ");\" VALUE=\"Add\">"
				
								actn3 = "escape(\'" + tmp_val.replace("'", "\\'") + "\')"
				
								content += "&nbsp;<INPUT TYPE=\"BUTTON\" id=\"removePropertyValue_" + tmp_val + "_btn\" onClick=\"removePropertyListValue(" + actn3 + ");\" VALUE=\"Remove Selected\">"
								
								content += "<P><SPAN ID=\"" + tmp_val + "_warning\" style=\"margin-left:10px; display:none; font-weight:bold; color:red;\">Please provide a set of values for " + pName + ", or select 'Free Text' as input format if applicable.</SPAN>"
				
								content += "</DIV>"
							else:
								actn = tmp_val.replace("'", "\\'")
								
								content += "<INPUT TYPE=\"radio\" ID=\"input_format_radio_text_" + tmp_val + "\" " + isDisabled + " NAME=\"num_vals_" +  tmp_val + "\" VALUE=\"freeform\" onClick=\"showHideAddPropertyValuesInput('" + actn + "');\">Free text"
				
								content += "&nbsp;&nbsp;"
				
								content += "<INPUT TYPE=\"radio\" ID=\"input_format_radio_list_" + tmp_val + "\" " + isDisabled + " NAME=\"num_vals_" +  tmp_val + "\" VALUE=\"predefined\" CHECKED onClick=\"showHideAddPropertyValuesInput('" + actn + "');\">Dropdown<BR>"
								
								#print "Content-type:text/html"
								#print
								#print "input_format_radio_list_" + tmp_val
								
								content += "<DIV ID=\"propertyValuesInputDiv_" + tmp_val + "\" style=\"display:inline; margin-left:15px;\">"
								
								content += "<span style=\"font-size:9pt; margin-left:5px;\">Use the textbox and 'Add'/'Remove' buttons below to populate the list:</span><BR>"
		
								content += "<TABLE style=\"margin-left:10px; margin-top:10px;\">"
								
								## April 28, 2010: multiple
								#content += "<TR>"
								#content += "<TD style=\"padding-left:5px; padding-bottom:5px;\">"
								
								# either give options as radio buttons
								#content += "Is multiple? <INPUT TYPE=\"radio\" NAME=\"isMultiple_" + tmp_val + "\" VALUE=\"No\" CHECKED>No"
								#content += "&nbsp;&nbsp;&nbsp;"
								#content += "<INPUT TYPE=\"radio\" NAME=\"isMultiple_" + tmp_val + "\" VALUE=\"Yes\">Yes"
								
								# or checkbox
								#content += "<INPUT TYPE=\"CHECKBOX\" NAME=\"isMultiple_" + tmp_val + "\">Allow multiple"
								
								#content += "</TD>"
								#content += "</TR>"
								
								content += "<TR>"
								
								content += "<TD style=\"padding-bottom:5px;\">"
								
								content += "<span style=\"font-size:9pt; margin-left:8px;\">Values currently assigned to</SPAN><BR><SPAN style=\"font-size:9pt; margin-left:8px;\">reagent type <b>" + rTypeName + "</b>:</span><BR><P>"
								
								content += "</TD>"
								
								content += "<TD></TD>"
								
								content += "<TD>"
								content += "<span style=\"font-size:9pt; margin-left:10px;\">Additional <b>" + pName + "</b> values available in OpenFreezer:</SPAN><BR><P>"
								content += "</TD></TR>"
								
								content += "<TR><TD>"

								content += "<SELECT MULTIPLE SIZE=\"10\" style=\"margin-left:8px;\" ID=\"propertyValuesInputList_" + tmp_val + "\" NAME=\"propertyValues_" + tmp_val + "\">"
								
								for setValue in currSetValues:
									
									if pName.lower() == "tag position" or pName.lower() == "expression system":
										if pName.lower() == "tag position":
											tmpPropID = prop_Name_ID_Map["tag"]
										elif pName.lower() == "expression system":
											tmpPropID = prop_Name_ID_Map["promoter"]
										
										tmpPropCatID = pHandler.findReagentPropertyInCategoryID(tmpPropID, categoryID)
										
										if sHandler.isUsedSetValue(rTypeID, tmpPropCatID, setValue, True):
											val_disabled = "DISABLED"
										else:
											val_disabled = ""
									
									else:
										if sHandler.isUsedSetValue(rTypeID, propCatID, setValue):
											val_disabled = "DISABLED"
										
										# Jan. 29/10: For Alternate IDs, list values are only the prefixes (IMAGE, RIKEN, etc.) - db values are PREFIX:NUMERIC_INDEX
										elif propCatID == pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["alternate id"], category_Name_ID_Map["External Identifiers"]):
											if rtPropHandler.existsPropertyValue(rTypeID, propCatID, setValue+":"):
												val_disabled = "DISABLED"
											else:
												val_disabled = ""
										else:
											val_disabled = ""
									
									content += "<OPTION " + val_disabled + " value=\"" + setValue + "\">" + setValue + "</OPTION>"
							
								content += "</SELECT>"
								content += "</TD>"
							
								content += "<TD style=\"padding-bottom:5px;\">"
								content += "&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"BUTTON\" VALUE=\"-->\" onClick=\"moveListElements('propertyValuesInputList_" + tmp_val.replace("'", "\\'") + "', 'propertyValuesPoolList_" + tmp_val.replace("'", "\\'") + "', 'false')\"><BR><BR>&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"BUTTON\" VALUE=\"<--\" onClick=\"moveListElements('propertyValuesPoolList_" + tmp_val.replace("'", "\\'") + "', 'propertyValuesInputList_" + tmp_val.replace("'", "\\'") + "', 'false')\">"
								content += "</TD>"
								
								content += "<TD style=\"white-space:nowrap; padding-bottom:5px;\">"

								content += "<SELECT MULTIPLE style=\"margin-left:8px;\" SIZE=\"10\" ID=\"propertyValuesPoolList_" + tmp_val + "\" NAME=\"allVals_" + tmp_val + "\">"
								
								for setValue in allSetValues:
									if setValue not in currSetValues:
										content += "<OPTION value=\"" + setValue + "\">" + setValue + "</OPTION>"
							
								content += "</SELECT>"
								
								content += "</TD></TR>"
								
								content += "<TR>"
								content += "<TD colspan=\"2\">"
								
								
								content += "<INPUT TYPE=\"checkbox\" style=\"margin-top:5px; margin-left:8px; font-size:8pt;\" onClick=\"selectAll(this.id, 'propertyValuesInputList_" + tmp_val + "', false)\" id=\"add_all_chkbx_" + tmp_val + "_inputList\"> Select All</INPUT>"
								
								content += "</TD>"
								
								content += "<TD>"
								
								content += "<INPUT TYPE=\"checkbox\" style=\"margin-top:5px; margin-left:8px; font-size:8pt;\" onClick=\"selectAll(this.id, 'propertyValuesPoolList_" + tmp_val + "', false)\" id=\"add_all_chkbx_" + tmp_val + "_poolList\"> Select All</INPUT>"
								
								content += "</TD>"
								content += "</TR>"
								
								content += "</TABLE><BR>"
									
								content += "<INPUT TYPE=\"TEXT\" style=\"margin-left:18px;\" id=\"addPropertyValue_" + tmp_val + "_txt\" onKeyPress=\"return disableEnterKey(event);\">"

								actn1 = "escape(\'addPropertyValue_" + tmp_val.replace("'", "\\'") + "_txt\')"
								#print actn1
								
								actn2 = "escape(\'propertyValuesInputList_" + tmp_val.replace("'", "\\'") + "\')"
								#print actn2
								
								content += "&nbsp;<INPUT TYPE=\"BUTTON\" id=\"addPropertyValue_" + tmp_val + "_btn\" onClick=\"addElementToListFromInput(" + actn1 + ", " + actn2 + ");\" VALUE=\"Add\">"
				
								content += "&nbsp;<INPUT TYPE=\"BUTTON\" id=\"removePropertyValue_" + tmp_val + "_btn\" onClick=\"removePropertyListValue('" + tmp_val.replace("'", "\\'") + "');\" VALUE=\"Remove Selected\">"
								
								content += "<P><SPAN ID=\"" + tmp_val + "_warning\" style=\"display:none; margin-left:10px; font-weight:bold; color:red;\">Please provide a set of values for " + pName + ", or select 'Free Text' as input format if applicable.</SPAN>"
								
						else:
							#print "Content-type:text/html"
							#print
							#print "this is a feature? " + tmp_val
							
							if pAlias not in setFormatList:
								content += "<INPUT TYPE=\"hidden\" NAME=\"num_vals_" + tmp_val + "\" VALUE=\"predefined\">"
									
								content += "<span style=\"font-size:9pt; margin-left:5px;\">Use the textbox and 'Add'/'Remove' buttons below to populate the list:</span><BR><P>"
				
								content += "<TABLE>"
								content += "<TR>"
								
								content += "<TD colspan=\"2\" style=\"padding-bottom:5px;\">"
								content += "<span style=\"font-size:9pt; margin-left:10px;\">Values currently assigned to reagent type <b>" + rTypeName + "</b>:</span><BR><P>"
								content += "</TD>"
								
								content += "<TD style=\"padding-bottom:5px;\">"
								content += "<span style=\"font-size:9pt; margin-left:10px;\">Additional <b>" + pName + "</b> values available in OpenFreezer:</SPAN><BR><P></TD>"
								
								content += "</TR>"
								
								content += "<TR>"
								content += "<TD>"
								
								content += "<SELECT MULTIPLE style=\"margin-left:8px;\" SIZE=\"10\" ID=\"propertyValuesInputList_" + tmp_val + "\" NAME=\"propertyValues_" + tmp_val + "\">"
								#content += "<OPTION value=\"default\">-- Select " + pName + " --</OPTION>"
								
								for setValue in currSetValues:
									if pName.lower() == "tag position" or pName.lower() == "expression system":
										if pName.lower() == "tag position":
											tmpPropID = prop_Name_ID_Map["tag"]
										elif pName.lower() == "expression system":
											tmpPropID = prop_Name_ID_Map["promoter"]
										
										tmpPropCatID = pHandler.findReagentPropertyInCategoryID(tmpPropID, categoryID)
										
										if sHandler.isUsedSetValue(rTypeID, tmpPropCatID, setValue, True):
											val_disabled = "DISABLED"
										else:
											val_disabled = ""
									else:
										if sHandler.isUsedSetValue(rTypeID, propCatID, setValue):
											val_disabled = "DISABLED"
										else:
											val_disabled = ""
										
									content += "<OPTION " + val_disabled + " value=\"" + setValue + "\">" + setValue + "</OPTION>"
							
								content += "</SELECT>"
								content += "</TD>"
								
								#tmp_val = (catAlias + "_:_" + pAlias).replace("'", "\\'")
								
								content += "<TD>"
								content += "&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"BUTTON\" VALUE=\"-->\" onClick=\"moveListElements('propertyValuesInputList_" + tmp_val  + "', 'propertyValuesPoolList_" + tmp_val + "', 'false')\"><BR><BR>&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"BUTTON\" VALUE=\"<--\" onClick=\"moveListElements('propertyValuesPoolList_" + tmp_val + "', 'propertyValuesInputList_" + tmp_val + "', 'false')\">"
								content += "</TD>"
								
								content += "<TD>"
								
								content += "<SELECT MULTIPLE style=\"margin-left:8px;\" SIZE=\"10\" ID=\"propertyValuesPoolList_" + tmp_val + "\" NAME=\"allVals_" + tmp_val + "\">"
								#content += "<OPTION value=\"default\">-- Select " + pName + " --</OPTION>"
								
								for setValue in allSetValues:
									if setValue not in currSetValues:
										content += "<OPTION value=\"" + setValue + "\">" + setValue + "</OPTION>"
							
								content += "</SELECT>"
								content += "</TD>"
								
								content += "</TR>"
								
								content += "<TR>"
								content += "<TD colspan=\"2\">"
								
								
								content += "<INPUT TYPE=\"checkbox\" style=\"margin-top:5px; margin-left:8px; font-size:8pt;\" onClick=\"selectAll(this.id, 'propertyValuesInputList_" + tmp_val + "', false)\" id=\"add_all_chkbx_" + tmp_val + "_inputList\"> Select All</INPUT>"
								
								content += "</TD>"
								
								content += "<TD>"
								
								content += "<INPUT TYPE=\"checkbox\" style=\"margin-top:5px; margin-left:8px; font-size:8pt;\" onClick=\"selectAll(this.id, 'propertyValuesPoolList_" + tmp_val + "', false)\" id=\"add_all_chkbx_" + tmp_val + "_poolList\"> Select All</INPUT>"
								
								content += "</TD>"
								content += "</TR>"
								
								content += "</TABLE>"
								
								content += "<BR><P>"
					
								content += "<INPUT TYPE=\"TEXT\" style=\"margin-left:8px;\" id=\"addPropertyValue_" + tmp_val + "_txt\" onKeyPress=\"return disableEnterKey(event);\">"
							
								#tmp_val = (catAlias + "_:_" + pAlias).replace("'", "\\'")
								
								actn1 = "escape(\'addPropertyValue_" + tmp_val.replace("'", "\\'") + "_txt\')"
								#print actn1
								
								actn2 = "escape(\'propertyValuesInputList_" + tmp_val.replace("'", "\\'") + "\')"
								#print actn2
								
								content += "&nbsp;<INPUT TYPE=\"BUTTON\" id=\"addPropertyValue_" + tmp_val + "_btn\" onClick=\"addElementToListFromInput(" + actn1 + ", " + actn2 + ");\" VALUE=\"Add\">"
				
								content += "&nbsp;<INPUT TYPE=\"BUTTON\" id=\"removePropertyValue_" + tmp_val + "_btn\" onClick=\"removePropertyListValue('" + tmp_val.replace("'", "\\'")+ "');\" VALUE=\"Remove Selected\">"
				
								content += "<P><SPAN ID=\"" + tmp_val + "_warning\" style=\"display:none; margin-left:10px; font-weight:bold; color:red;\">Please provide a set of values for " + pName + ", or select 'Free Text' as input format if applicable.</SPAN>"
			
								content += "</TD>"
								content += "</TR>"
								
								# don't need these here
								#content+= "<TR><TD></TD></TR>"
								#content += "</TABLE>"

							elif pName.lower() == "cdna" or pName.lower() == "cdna insert" or pName.lower() == "5' linker" or pName.lower() == "3' linker":
								content += "<TABLE>"
								content += "<TR>"
								content += "<TD>"
								
								content += "<INPUT TYPE=\"radio\" ID=\"input_format_radio_text_" + tmp_val + "\" CHECKED DISABLED NAME=\"num_vals_" + tmp_val + "\" VALUE=\"freeform\">Free text"
				
								content += "&nbsp;&nbsp;"
				
								content += "<INPUT TYPE=\"radio\" ID=\"input_format_radio_list_" + tmp_val + "\" DISABLED NAME=\"num_vals_" +  tmp_val + "\" VALUE=\"predefined\">Dropdown"

								content += "</TD>"
								content += "</TR>"
								
								content+= "<TR><TD></TD></TR>"
								
								content += "</TABLE>"
						
							elif pName.lower() == "5' cloning site" or pName.lower() == "3' cloning site" or pName.lower() == "restriction site":
								#print "HERE!! " + pAlias + ", " + tmp_val
								content += "<TABLE>"
								content += "<TR>"
								content += "<TD>"
								
								content += "<INPUT TYPE=\"radio\" ID=\"input_format_radio_text_" + tmp_val + "\" DISABLED NAME=\"num_vals_" +  tmp_val + "\" VALUE=\"freeform\">Free text"
				
								content += "&nbsp;&nbsp;"
				
								content += "<INPUT TYPE=\"radio\" ID=\"input_format_radio_list_" + tmp_val + "\" CHECKED DISABLED NAME=\"num_vals_" +  tmp_val + "\" VALUE=\"predefined\">Dropdown"
							
								content += "</TD>"
								content += "</TR>"
								
								content+= "<TR><TD></TD></TR>"
								
								content += "</TABLE>"

						# property either does not exist at all or exists in a different category
						#print tmpPropID
						if (tmpPropID < 0) or (catAlias not in category_Alias_ID_Map.keys()) or (category_Alias_ID_Map.has_key(catAlias) and tmpPropID > 0 and not pHandler.existsPropertyInCategory(tmpPropID, category_Alias_ID_Map[catAlias])):
							#print "new " + pAlias
							
							content += "<INPUT TYPE=\"hidden\" ID=\"" + tmp_val + "_input\" NAME=\"newPropName\" VALUE=\"" + tmp_val + "\">"	# alias, e.g. "Antigen_sequence"
							
							content += "<INPUT TYPE=\"hidden\" NAME=\"newPropDescr_" + tmp_val + "\" VALUE=\"" + pName + "\">"	# actual description, e.g. "Antigen Sequence"
		
						#content += "</TD>"
						#content += "</TR>"

						#else:	# for properties with default input values don't need to let user select input format but still need to save them!

							#content += "<INPUT TYPE=\"hidden\" ID=\"" + tmp_val + "_input\" NAME=\"newPropDescr_" + tmp_val + "\" VALUE=\"" + pName + "\">"	# actual description, e.g. "Antigen Sequence"
			
							#content += "</TD>"
							#content += "</TR>"
						
						content += "</DIV>"
						
				content += "</TABLE></TD></TR>"
				
			if step == 2:
				content += '''
						</TABLE>
						
						<!-- Jan. 29, 2010: DISABLED RADIO BUTTONS ARE NOT SUBMITTED WITH FORM -->
						
						<P><INPUT TYPE="submit" name="create_rtype" value="Continue" style="font-size:10pt;" onClick="enableSelect(); enableRadio(); selectAllPropertyValues(true); enableCheckboxes();">
						
						&nbsp;&nbsp;&nbsp;<INPUT TYPE="submit" name="cancel_rtype_create" value="Cancel" onClick="document.pressed='Cancel'; return confirm('Cancel reagent type creation?');" style="font-size:10pt;">
						<INPUT TYPE="hidden" name="step" value="2">
						'''
			else:
				content += '''
						</TABLE>
	
						<P><INPUT TYPE="submit" name="save_rtype" value="Continue" style="font-size:10pt;" onClick="enableSelect(); enableRadio(); selectAllPropertyValues(true); enableCheckboxes();">
						
						&nbsp;&nbsp;&nbsp;<INPUT TYPE="submit" name="cancel_rtype_modify" value="Cancel" onClick="document.pressed='Cancel'; return confirm('Cancel reagent type modification?');" style="font-size:10pt;">
						<INPUT TYPE="hidden" name="step" value="3">
						'''
				
			for parent in parents:
				content += "<INPUT TYPE=\"hidden\" NAME=\"addReagentTypeParents\" VALUE=\"" + parent + "\">"

			content += '''
				</FORM>
				'''

			content += gOut.printFooter()
			
			# Oct. 7/09: don't make reagent type name uppercase (e.g. 'RNAi')
			page_content = content % (hostname + "cgi/reagent_type_request_handler.py", rTypeName, rTypePrefix, rTypeName)
	
			print "Content-type:text/html"		# THIS IS PERMANENT!
			print					# DITTO
			print page_content


	#def printReagentTypeView(self, rTypeName, rTypePrefix, catDescrDict, categoryProps, err=""):
	def printReagentTypeView(self, rTypeID):
		
		#print "Content-type:text/html"
		#print
		
		dbConn = DatabaseConn()
		hostname = dbConn.getHostname()		# to define form action URL
		
		db = dbConn.databaseConnect()
		cursor = db.cursor()
		root_dir = dbConn.getRootDir()
		
		propMapper = ReagentPropertyMapper(db, cursor)

		prop_Alias_ID_Map = propMapper.mapPropAliasID()		# (propAlias, propID) - e.g. ('insert_type', '48')
		prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)
		prop_ID_Name_Map = propMapper.mapPropIDName()		# Added March 13/08 - (prop id, prop name)
		prop_Name_Alias_Map = propMapper.mapPropNameAlias()	# (propName, propAlias)
		prop_Alias_Name_Map = propMapper.mapPropAliasName()	# March 18/08 - (propAlias, propName)
		
		prop_Alias_Desc_Map = propMapper.mapPropAliasDescription()	# April 20/09 - (propAlias, propDesc)
		prop_Desc_Alias_Map = propMapper.mapPropDescAlias()		# April 20/09 - (propDesc, propAlias)
		prop_Desc_ID_Map = propMapper.mapPropDescID()
		
		category_Alias_ID_Map = propMapper.mapPropCategoryAliasID()
		category_ID_Name_Map = propMapper.mapPropCategoryIDName()
		prop_ID_Desc_Map = propMapper.mapPropIDDescription()
		
		propCategory_ID_Order_Map = propMapper.mapPropCategoryIDOrdering()
		
		rHandler = ReagentHandler(db, cursor)
		rtHandler = ReagentTypeHandler(db, cursor)
		rtPropHandler = ReagentTypePropertyHandler(db, cursor)		# Aug. 31/09
		pHandler = ReagentPropertyHandler(db, cursor)
		rtAssocHandler = ReagentTypeAssociationHandler(db, cursor)
		
		sHandler = SystemSetHandler(db, cursor)
		
		currUser = Session.getUser()
		
		# July 28/09
		rTypeName = rtHandler.findReagentType(rTypeID)
		rTypePrefix = rtHandler.findReagentTypePrefix(rTypeName)
		
		prop_ID_Order_map = propMapper.mapPropIDOrdering()	# keep
		
		rTypeAttrIDs = rtPropHandler.findAllReagentTypeAttributes(rTypeID)
		#print `rTypeAttrIDs`

		propList = {}		# (categoryID, [categoryProps]) dictionary
	
		# Find out what categories this reagent type has
		#for attrID in rTypeAttrIDs:	# this is equivalent to propCatID - changed Aug. 5/09
		for aKey in rTypeAttrIDs:
			#print "Attribute " + `aKey`
			attrID = rTypeAttrIDs[aKey]	# this is equivalent to propCatID
			#print "PropCatID " + `attrID`
			
			propID = pHandler.findReagentPropertyInCategory(attrID)
			#print propID
			categoryID = pHandler.findReagentPropertyCategory(attrID)
			
			if propList.has_key(categoryID):
				tmpProps = propList[categoryID]
			else:
				tmpProps = []
				
			tmpProps.append(propID)
			propList[categoryID] = tmpProps
	
		#print `propList`
		gOut = GeneralOutputClass()

		modify_disabled = True
		delete_disabled = True
		
		if currUser.getCategory() == 'Admin':
			modify_disabled = False

		if rtHandler.isEmpty(rtHandler.findReagentTypeID(rTypeName)):
			delete_disabled = False
			
		if rTypeName == 'CellLine':
			rTypeDescr = "CELL LINE"
		else:
			# Oct. 7/09: don't make reagent type name uppercase (e.g. 'RNAi')
			#rTypeDescr = rTypeName.upper()
			rTypeDescr = rTypeName

		content = gOut.printHeader()
		
		content += '''
				<FORM name="reagent_type_form" method="POST" action="%s">

					<!-- pass current user as hidden form field -->
					<INPUT type="hidden" ID="username_hidden" NAME="curr_username"'''
					
		content += "value=\"" + currUser.getFullName() + "\">"
		
		content += '''
				<TABLE width="760px" cellpadding="5" cellspacing="5" border="1" frame="box" rules="none" style="padding-left:5px; vertical-align:middle" name="reagent_props" class="detailedView_tbl">
					<TR>
						<TD colspan="4">
							<table width="760px">
								<tr>
									<td class="detailedView_heading" style="white-space:nowrap; color:blue; color:#0000DF; padding-left:200px;">
										%s (%s) Details Page
										<INPUT TYPE="hidden" name="reagentType" value="%s">
									</td>
								
									<TD class="detailedView_heading" style="text-align:right">
									'''
		content += "<INPUT TYPE=\"submit\" name=\"modify_rtype\" value=\"Modify\""

		if modify_disabled:
			content += " disabled>"
		else:
			content += ">"
				
		content += "<INPUT TYPE=\"submit\" style=\"margin-left:2px;\" name=\"delete_rtype\" value=\"Delete\" onClick=\"return confirmDeleteReagentType();\""
		
		if modify_disabled or delete_disabled:
			content += " disabled>"
		else:
			content += ">"
	
		content += '''
									</td>
								</tr>
							</table>
						</TD>
					</TR>

					<TR><TD colspan="4"><HR></TD></TR>
					'''
		
		# output them in order
		for categoryID in propCategory_ID_Order_Map.keys():
			
			if propList.has_key(categoryID):
				categoryOrder = propCategory_ID_Order_Map[categoryID]
				category = category_ID_Name_Map[categoryID]
				#print category
				
				content += "<TR><TD colspan=\"4\" class=\"detailedView_heading\" style=\"text-align:left; padding-left:7px; padding-right:6px; padding-top:0; color:#0000DF; font-size:9pt; font-family:Helvetica; white-space:nowrap;\">" + category + "</TD></TR>"
				
				rTypeProps = propList[categoryID]	# list of IDs
				#print `rTypeProps`
				rTypePropSorted = {}
				
				descrAttrID_map = {}

				for propID in rTypeProps:
					# April 9, 2010
					tmpPropCatID = pHandler.findReagentPropertyInCategoryID(propID, categoryID)
					#print tmpPropCatID
					
					pOrder = rtPropHandler.getReagentTypePropertyOrdering(rTypeID, tmpPropCatID)
					#print pOrder
				
					#print propID
					if prop_ID_Desc_Map.has_key(propID):
						pDescr = prop_ID_Desc_Map[propID]
	
					if rTypePropSorted.has_key(pOrder):
						tmp_order_list = rTypePropSorted[pOrder]
						# don't do any more sorting here!
					else:
						tmp_order_list = []
	
					tmp_order_list.append(pDescr)
					rTypePropSorted[pOrder] = tmp_order_list
					
					# April 29, 2010: show property format
					rTypeAttrID = rtPropHandler.findReagentTypeAttributeID(rTypeID, tmpPropCatID)
					descrAttrID_map[pDescr] = rTypeAttrID
					
				rTypePropSorted.keys().sort()
				#print `rTypePropSorted`

				for pOrd in rTypePropSorted.keys():
					pDescrs = rTypePropSorted[pOrd]
					
					for pDescr in pDescrs:
						#print `pDescr`
						
						content += "<TR>"
						content += "<TD class=\"detailedView_colName\" style=\"padding-left:12px;\">" + pDescr.replace('%', '%%') + "</TD>"
						#content += "<TD class=\"detailedView_value\"></TD>"
						#content += "</TR>"
						
						# April 29, 2010: show property format
						rTypeAttrID = descrAttrID_map[pDescr]
						
						if rtPropHandler.isDropdown(rTypeAttrID):
							dForm = "Dropdown"
						
						# May 28, 2010: Introducing change: where you don't want to fill in list values and just want to check 'Other' and let users populate, System_Set_tbl will be empty and sHandler.isDropdown(rTypeAttrID) will return false, so may end up with something like 'Freetext Customizeable Multiple'.  If sHandler.isDropdown(rTypeAttrID) returns false, check again to see if 'isMultiple' or 'isCustomizeable' have been set - because if they were, this cannot be freetext.  Likewise, a dropdown CANNOT be a hyperlink!!
						elif rtPropHandler.isMultiple(rTypeAttrID) or rtPropHandler.isCustomizeable(rTypeAttrID) and not rtPropHandler.isHyperlink(rTypeAttrID):
							dForm = "Dropdown"
						else:
							if pDescr != 'Project ID' and pDescr != "5' Cloning Site" and pDescr != "3' Cloning Site" and pDescr != "Restriction Site":
								dForm = "Freetext"
							else:
								dForm = "Dropdown"
							
						content += "<TD class=\"detailedView_value\" style=\"padding-left:12px;\">" + dForm
						
						#content += "<TD class=\"detailedView_value\"></TD>"
						
						if rtPropHandler.isMultiple(rTypeAttrID) or pDescr == "Alternate ID":
							content += "&nbsp;&nbsp;MULTIPLE"
						
						if rtPropHandler.isHyperlink(rTypeAttrID):
							content += "&nbsp;&nbsp;HYPERLINK"
							
						if rtPropHandler.isCustomizeable(rTypeAttrID):
							content += "&nbsp;&nbsp;CUSTOMIZABLE"
							
							# nice but don't use an image on this view, use it on reagent creation form
							#content += "<IMG src=\"" + hostname + "pictures/link7.png\" WIDTH=\"21\" HEIGHT=\"10\" ALT=\"link_icon\" style=\"cursor:auto\">"
						
						content += "</TD>"
						
						#if pDescr.lower() == "3' cloning site" or pDescr.lower() == "3' linker" or pDescr.lower() == "cdna" or pDescr.lower() == rTypeName.lower() + " type" or pDescr.lower() == "verification comments":
							#content += "<TR><TD></TD></TR>"
						
						content += "<TD class=\"detailedView_value\"></TD>"
		
						content += "</TR>"
						
						
				content += "<TR><TD colspan=\"4\"><HR></TD></TR>"
	
	
		# Aug. 11/09: Print associations
		assocTypes = rtAssocHandler.getReagentTypeAssociations(rTypeID)
		
		if len(assocTypes) > 0:
			content += "<TR><TD class=\"detailedView_heading\" style=\"text-align:left; padding-left:7px; padding-right:6px; padding-top:0; color:#0000DF; font-size:9pt; font-family:Helvetica; white-space:nowrap;\">Parent Types</TD></TR>"
			
			for assocID in assocTypes:
				assocName = assocTypes[assocID]
				
				content += "<TR>"
				content += "<TD width=\"150px\" class=\"detailedView_colName\" style=\"padding-left:12px;\">" + assocName + "</TD>"
				content += "<TD class=\"detailedView_value\"></TD>"
				content += "</TR>"
				content += "<INPUT TYPE=\"hidden\" NAME=\"reagentTypeParents\" VALUE=\"" + assocName + "\">"
	
		content += '''
				</TABLE>
				</FORM>
				'''

		content += gOut.printFooter()

		page_content = content % (hostname + "cgi/reagent_type_request_handler.py", rTypeDescr, rTypePrefix, rTypeName)

		print "Content-type:text/html"		# PERMANENT
		print					# DITTO
		print page_content