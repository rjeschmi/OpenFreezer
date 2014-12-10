#!/usr/local/bin/python

import os
#import tempfile
import stat
import sys

from database_conn import DatabaseConn
from session import Session

from reagent_handler import ReagentHandler, InsertHandler
from project_database_handler import ProjectDatabaseHandler
from system_set_handler import SystemSetHandler

from general_output import GeneralOutputClass

import utils

class ReagentOutputClass:

	# This function may be called with or without an error message
	def printReagentInfo(self, cmd, reagent, errMsg=""):
		
		dbConn = DatabaseConn()
		hostname = dbConn.getHostname()		# to define form action URL
		
		db = dbConn.databaseConnect()
		cursor = db.cursor()
		
		gOut = GeneralOutputClass()

		currUser = Session.getUser()
		currUserID = currUser.getUserID()
		
		sHandler = SystemSetHandler(db, cursor)
		pHandler = ProjectDatabaseHandler(db, cursor)
		
		# print menu
		content = gOut.printHeader() + gOut.printMainMenu()
		
		# common to all reagent types
		projectList = utils.unique(pHandler.findMemberProjects(currUserID, 'Writer') + pHandler.findMemberProjects(currUserID, 'Owner'))
		
		# Differentiate other properties by reagent type
		if reagent.getType() == 'Insert':
			
			# Fetch dropdown list values to output on the form
			iTypeList = sHandler.findAllSetValues("type of insert")
			statusList = sHandler.findAllSetValues("status")
			cloningMethodList = sHandler.findAllSetValues("insert cloning method")
			fpcsList = sHandler.findAllSetValues("5' cloning site")
			tpcsList = sHandler.findAllSetValues("3' cloning site")
			tagTypeList = sHandler.findAllSetValues("tag")
			tagPositionList = sHandler.findAllSetValues("tag position")
			openClosedList = sHandler.findAllSetValues("open/closed")
			speciesList = sHandler.findAllSetValues("species")
			verificationList = sHandler.findAllSetValues("verification")
		
			# Initialize property values
			iType = ""
			iName = ""
			iStatus = ""
			cloningMethod = ""
			projectID = ""
			fpcs = ""
			tpcs = ""
			tagType = ""
			tagPosition = ""
			openClosed = ""
			species = ""
			accession = ""
			altID = ""
			geneID = ""
			ensemblID = ""
			geneSymbol = ""
			fp_start = ""
			tp_stop = ""
			fwd_linker = ""
			rev_linker = ""
			funcDescr = ""
			verification = ""
			expComm = ""
			verComm = ""
			sequence = ""
		
			# Fetch POST values if available:
			allProps = reagent.getProperties()
			
			if allProps.has_key("type of insert"):
				iType = allProps["type of insert"]
			
			if allProps.has_key("name"):
				iName = allProps["name"]
				
			if allProps.has_key("status"):
				iStatus = allProps["status"]
			
			if allProps.has_key("insert cloning method"):
				cloningMethod = allProps["insert cloning method"]
			
			if allProps.has_key("packet id"):
				projectID = int(allProps["packet id"])
				
			if allProps.has_key("5' cloning site"):
				fpcs = allProps["5' cloning site"]
			
			if allProps.has_key("3' cloning site"):
				tpcs = allProps["3' cloning site"]
			
			if allProps.has_key("tag"):
				tagType = allProps["tag"]
			
			if allProps.has_key("tag position"):
				tagPosition = allProps["tag position"]
				
			if allProps.has_key("open/closed"):
				openClosed = allProps["open/closed"]
			
			if allProps.has_key("species"):
				species = allProps["species"]
				
			if allProps.has_key("accession number"):
				accession = allProps["accession number"]
			
			if allProps.has_key("alternate id"):
				altID = allProps["alternate id"]
			
			if allProps.has_key("entrez gene id"):
				geneID = allProps["entrez gene id"]
			
			if allProps.has_key("ensembl gene id"):
				ensemblID = allProps["ensembl gene id"]
				
			if allProps.has_key("official gene symbol"):
				geneSymbol = allProps["official gene symbol"]
				
			if allProps.has_key("5' start"):
				fp_start = allProps["5' start"]
				
			if allProps.has_key("3' stop"):
				tp_stop = allProps["3' stop"]
				
			if allProps.has_key("5' linker"):
				fwd_linker = allProps["5' linker"]
				
			if allProps.has_key("3' linker"):
				rev_linker = allProps["3' linker"]
				
			if allProps.has_key("description"):
				funcDescr = allProps["description"]
				
			if allProps.has_key("verification"):
				verification = allProps["verification"]
				
			if allProps.has_key("comments"):
				expComm = allProps["comments"]
			
			if allProps.has_key("verification comments"):
				verComm = allProps["verification comments"]
					
			if allProps.has_key("sequence"):
				sequence = allProps["sequence"]
			
			# associations
			ipvID = ""
			senseOligoID = ""
			antisenseOligoID = ""
			
			allAssoc = reagent.getAssociations()
			
			'''
			if allAssoc.has_key("insert_parent_vector"):
				ipvID = allAssoc["insert_parent_vector"]
			
			if allAssoc.has_key("sense_oligo"):
				senseOligoID = allAssoc["sense_oligo"]
			
			if allAssoc.has_key("antisense_oligo"):
				antisenseOligoID = allAssoc["antisense_oligo"]
			'''
			
			# Action: Output form with POST values if set
			if cmd == 'create':
				content += '''
					<div class="main">
						<form NAME="create_insert_form" ACTION="%s" METHOD="POST" onSubmit="return validateInsert() && verifyParents();">
						'''
	
				content += "<INPUT type=\"hidden\" ID=\"curr_username_hidden\" NAME=\"curr_username\" value=\"" + currUser.getFullName() + "\">"
				
				content += '''
							<input type="hidden" name="reagent_type_hidden" value="Insert">
							
							<table id="vector_insertproperty_table_id" border="1" width="780px" cellpadding="4" frame="box" rules="all">
							'''
				if errMsg != "":
					content += "<th colspan=\"3\" style=\"color:#0000FF; text-align:left; font-weight:normal\">There was a problem:<br><P>" + errMsg + "<BR><BR></th>"

				content += '''
								<tr>
									<td colspan="3" style="text-align:center">
										<span style="color:#0000CD; font-size:13pt; font-weight:bold">
											Insert Information
										</span>
										<br>
										
										<span style="font-size:8pt; color:#FF0000">
										Fields marked with a red asterisk (*) are mandatory
										</span>
									</td>
								</tr>
					
								<tr>
									<td width="180px">
										&nbsp;&nbsp;&nbsp;&nbsp;Type of Insert&nbsp;&nbsp;
										<span style="font-size:8pt; color:#FF0000; font-weight:bold">*</span>
									</td>
								
									<td width="275px">
										<SELECT  id="itype_list" size="1" NAME="INPUT_INSERT_info_insert_type_prop">
										'''
				# default option
				content += "<OPTION VALUE=\"\" "

				if len(iType) == 0:
					content += "SELECTED></OPTION>"
				else:
					content += "></OPTION>"
				
				for it in iTypeList:
					content += "<OPTION VALUE=\"" + it + "\""
					
					if it == iType:
						content += " SELECTED>" + it + "</OPTION>"
					else:
						content += ">" + it + "</OPTION>"
						
				content += '''
										</SELECT>
									</td>
									
									<td>
										Describes the type of insert.  Users select from a pre-defined list.  Designed to make insert use more apparent and searches easier.
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Name
									</td>

									<td>
									'''
									
				content += "<INPUT type=\"text\" name=\"INPUT_INSERT_info_name_prop\" value=\"" + iName + "\">"
				
				content += '''
									</td>
									
									<td>
										Name that describes the reagent - free text field
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Status
									</td>
					
									<td>
										<SELECT id="status_list" name="INPUT_INSERT_info_status_prop">
									'''
				# default option
				content += "<OPTION VALUE=\"\" "

				if len(iStatus) == 0:
					content += "SELECTED></OPTION>"
				else:
					content += "></OPTION>"

						
				for s in statusList:
					content += "<OPTION VALUE=\"" + s + "\""
					
					if s == iStatus:
						content += " SELECTED>" + s + "</OPTION>"
					else:
						content += ">" + s + "</OPTION>"

				content += '''	
									</td>
									
									<td>
										Indicates the status and availability of the reagent.  Users select from a pre-defined list.
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Insert Cloning Method
									</td>
					
									<td>
										<SELECT size="1" id="cm_list" name="INPUT_INSERT_info_cloning_method_prop" onChange="showCMText()">
									'''
				# default option
				content += "<OPTION VALUE=\"\" "

				if len(cloningMethod) == 0:
					content += "SELECTED></OPTION>"
				else:
					content += "></OPTION>"

				
				for cm in cloningMethodList:
					content += "<OPTION VALUE=\"" + cm + "\""
					
					if cm == cloningMethod:
						content += " SELECTED>" + cm + "</OPTION>"
					else:
						content += ">" + cm + "</OPTION>"

				content += '''	
									</td>
									
									<td>
										Describes the method used to create the insert (e.g. PCR or RE (restriction enzyme)) and provides additional information for its cloning into a vector (eg. by RE, In Fusion or BP reaction)
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Project ID  <span style="font-size:8pt; color:#FF0000; font-weight:bold">*</span>
									</td>
					
									<td>
										<select id="packetList_insert" size="1" name="INPUT_INSERT_info_packet_id_prop">
										'''
				# need to sort projects
				pNums = []
				projects = {}			# (pID, Packet)
				
				for p in projectList:
					pID = p.getNumber()
					pNums.append(pID)
					projects[pID] = p
				
				pNums.sort()

				for pID in pNums:
					p = projects[pID]
					content += "<OPTION VALUE=\"" + `p.getNumber()` + "\""

					if p.getNumber() == projectID:
						content += " SELECTED>" + `p.getNumber()` + ": " + p.getOwner().getLastName() + ": " + p.getName() + "</OPTION>"
					else:
						content += ">" + `p.getNumber()` + ": " + p.getOwner().getLastName() + ": " + p.getName() + "</OPTION>"

				content += '''
										</select>
										<BR>
										<div id="packet_warning_insert" style="display:none; color:#FF0000">Please select a Project ID from the dropdown list</div>
									</td>
									
									<td>
										Indicates what person and project originally created the reagent.
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;5' Cloning Site on Insert
									</td>
					
									<td>
										<SELECT size="1" id="fpcs_list"  name="INPUT_INSERT_info_5_prime_cloning_site_prop">
									'''
				# default option
				content += "<OPTION VALUE=\"\" "

				if len(fpcs) == 0:
					content += "SELECTED></OPTION>"
				else:
					content += "></OPTION>"
				
				for fps in fpcsList:
					content += "<OPTION VALUE=\"" + fps + "\""
					
					if fps == fpcs:
						content += " SELECTED>" + fps + "</OPTION>"
					else:
						content += ">" + fps + "</OPTION>"

				content += '''	
									</td>
									
									<td>
										Indicates what 5' site on the insert was used to clone it into a vector.  May not necessarily be the same as for the vector.  Users select from a pre-defined list.
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;3' Cloning Site on Insert
									</td>
					
									<td>
										<SELECT size="1" id="tpcs_list"  name="INPUT_INSERT_info_3_prime_cloning_site_prop">
									'''
				# default option
				content += "<OPTION VALUE=\"\" "

				if len(tpcs) == 0:
					content += "SELECTED></OPTION>"
				else:
					content += "></OPTION>"
				
				for tps in tpcsList:
					content += "<OPTION VALUE=\"" + tps + "\""
					
					if tps == tpcs:
						content += " SELECTED>" + tps + "</OPTION>"
					else:
						content += ">" + tps + "</OPTION>"

				content += '''	
									</td>
									
									<td>
										Indicates what 3' site on the insert was used to clone it into a vector.  May not necessarily be the same as for the vector.  Users select from a pre-defined list.
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Tag
									</td>
					
									<td>
										<SELECT size="1" id="tag_list" name="INPUT_INSERT_info_tag_prop" onChange="showTagTypeBox()">
									'''
				# default option
				content += "<OPTION VALUE=\"\" "

				if len(tagType) == 0:
					content += "SELECTED></OPTION>"
				else:
					content += "></OPTION>"
				
				
				for tt in tagTypeList:
					content += "<OPTION VALUE=\"" + tt + "\""
					
					if tt == tagType:
						content += " SELECTED>" + tt + "</OPTION>"
					else:
						content += ">" + tt + "</OPTION>"

				content += '''	
									</td>
									
									<td>
										Indicates if the insert itself has a tag associated with it (outside of the backbone vector).  Users select from a pre-defined list.
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Tag Position
									</td>
					
									<td>
										<SELECT size="1" name="INPUT_INSERT_info_tag_position_prop">
									'''
				# default option
				content += "<OPTION VALUE=\"\" "

				if len(tagPosition) == 0:
					content += "SELECTED></OPTION>"
				else:
					content += "></OPTION>"
				
				for tp in tagPositionList:
					content += "<OPTION VALUE=\"" + tp + "\""
					
					if tp == tagPosition:
						content += " SELECTED>" + tp + "</OPTION>"
					else:
						content += ">" + tp + "</OPTION>"

				content += '''	
									</td>
									
									<td>
										Describes the position of the tag on the insert.  Users select from a pre-defined list.
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Open/Closed
									</td>
					
									<td>
										<SELECT id="oc_list" size="1" name="INPUT_INSERT_info_open_closed_prop">
									'''
				# default option
				content += "<OPTION VALUE=\"\" "

				if len(openClosed) == 0:
					content += "SELECTED></OPTION>"
				else:
					content += "></OPTION>"
				
				for oc in openClosedList:
					content += "<OPTION VALUE=\"" + oc + "\""
					
					if oc == openClosed:
						content += " SELECTED>" + oc + "</OPTION>"
					else:
						content += ">" + oc + "</OPTION>"

				content += '''	
									</td>
									
									<td>
										Indicates for an Open Reading Frame if there is a start codon (ATG) or stop codon (open - no stop codon, closed - stop codon).
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Species
									</td>
					
									<td>
										<SELECT  size="1" style="color:000000" id = "species_list" name="INPUT_INSERT_info_species_prop" onChange="showSpeciesBox()">
									'''
				# default option
				content += "<OPTION VALUE=\"\" "

				if len(species) == 0:
					content += "SELECTED></OPTION>"
				else:
					content += "></OPTION>"
				
				for sp in speciesList:
					content += "<OPTION VALUE=\"" + sp + "\""
					
					if sp == species:
						content += " SELECTED>" + sp + "</OPTION>"
					else:
						content += ">" + sp + "</OPTION>"

				content += '''	
									</td>
									
									<td>
										Indicates the species from which the Insert was derived.  User selects species from a pre-defined list.
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Accession Number
									</td>
									
									<td>
									'''
				content += "<INPUT type=\"text\" name=\"INPUT_INSERT_info_accession_number_prop\" value=\"" + accession + "\">"

				content += '''
									</td>
									
									<td>
										The Accession Number from which the insert was cloned or to which the sequence maps.
									</td>
								</tr>
									
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Alternate ID
									</td>
									
									<td>
										<input type="checkbox" name="INPUT_INSERT_info_alternate_id_prop[]" value="IMAGE" id="_alternate_id_IMAGE_checkbox" onClick="showAltIDTextBox('_alternate_id_IMAGE_checkbox', '_alternate_id_IMAGE_textbox')">IMAGE &nbsp;<INPUT style="display:none" id="_alternate_id_IMAGE_textbox" name="alternate_id_IMAGE_textbox_name" size="15"></INPUT><br>
										
										<input type="checkbox" name="INPUT_INSERT_info_alternate_id_prop[]" value="RIKEN" id="_alternate_id_RIKEN_checkbox" onClick="showAltIDTextBox('_alternate_id_RIKEN_checkbox', '_alternate_id_RIKEN_textbox')">RIKEN &nbsp;<INPUT style="display:none" id="_alternate_id_RIKEN_textbox" name="alternate_id_RIKEN_textbox_name" size="15"></INPUT><br>
										
										<input type="checkbox" name="INPUT_INSERT_info_alternate_id_prop[]" value="Kazusa" id="_alternate_id_Kazusa_checkbox" onClick="showAltIDTextBox('_alternate_id_Kazusa_checkbox', '_alternate_id_Kazusa_textbox')">Kazusa &nbsp;<INPUT style="display:none" id="_alternate_id_Kazusa_textbox" name="alternate_id_Kazusa_textbox_name" size="15"></INPUT><br>
										
										<input type="checkbox" name="INPUT_INSERT_info_alternate_id_prop[]" value="LIFESEQ" id="_alternate_id_LIFESEQ_checkbox" onClick="showAltIDTextBox('_alternate_id_LIFESEQ_checkbox', '_alternate_id_LIFESEQ_textbox')">LIFESEQ &nbsp;<INPUT style="display:none" id="_alternate_id_LIFESEQ_textbox" name="alternate_id_LIFESEQ_textbox_name" size="15"></INPUT><br>
										
										<input type="checkbox" name="INPUT_INSERT_info_alternate_id_prop[]" value="RZPD" id="_alternate_id_RZPD_checkbox" onClick="showAltIDTextBox('_alternate_id_RZPD_checkbox', '_alternate_id_RZPD_textbox')">RZPD &nbsp;<INPUT style="display:none" id="_alternate_id_RZPD_textbox" name="alternate_id_RZPD_textbox_name" size="15"></INPUT><br>
										
										<input type="checkbox" name="INPUT_INSERT_info_alternate_id_prop[]" value="HIP" id="_alternate_id_HIP_checkbox" onClick="showAltIDTextBox('_alternate_id_HIP_checkbox', '_alternate_id_HIP_textbox')">HIP &nbsp;<INPUT style="display:none" id="_alternate_id_HIP_textbox" name="alternate_id_HIP_textbox_name" size="15"></INPUT><br>
										
										<input type="checkbox" name="INPUT_INSERT_info_alternate_id_prop[]" value="ADDGENE" id="_alternate_id_ADDGENE_checkbox" onClick="showAltIDTextBox('_alternate_id_ADDGENE_checkbox', '_alternate_id_ADDGENE_textbox')">ADDGENE &nbsp;<INPUT style="display:none" id="_alternate_id_ADDGENE_textbox" name="alternate_id_ADDGENE_textbox_name" size="15"></INPUT><br>
										
										<input type="checkbox" name="INPUT_INSERT_info_alternate_id_prop[]" value="PMID" id="_alternate_id_PMID_checkbox" onClick="showAltIDTextBox('_alternate_id_PMID_checkbox', '_alternate_id_PMID_textbox')">PMID &nbsp;<INPUT style="display:none" id="_alternate_id_PMID_textbox" name="alternate_id_PMID_textbox_name" size="15"></INPUT><br>
										
										<input type="checkbox" id="_other_Alt_ID_chkbx" name="INPUT_INSERT_info_alternate_id_prop[]" value="Other" onClick="showSpecificOtherCheckbox('_alternate_id_txt')">Other:&nbsp;<INPUT style="display:none" id="_alternate_id_txt" name="alternate_id_name_txt" size="15"></INPUT>
									</td>
								
									<td>
										Indicates any other ID's that identify the insert, including but not limited to IMAGE, Kazusa, RIKEN, and other.  <BR><BR><BR><BR>For 'Other', please enter the source name and ID separated by a semicolon (e.g. IMAGE:123456)
									</td>
								</tr>
									
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Entrez Gene ID
									</td>
									
									<td>
									'''

				content += "<INPUT type=\"text\" name=\"INPUT_INSERT_info_gene_id_prop\" value=\"" + geneID + "\">"

				content += '''
									</td>
									
									<td>
										The NCBI Gene ID to which the sequence maps.
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Ensembl Gene ID
									</td>
									
									<td>
									'''

				content += "<INPUT type=\"text\" name=\"INPUT_INSERT_info_ensembl_gene_id_prop\" value=\"" + ensemblID + "\">"

				content += '''
									</td>
									
									<td>
										The Ensembl Gene ID to which the sequence maps.
									</td>
								</tr>
							
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Official Gene Symbol
									</td>
									
									<td>
									'''

				content += "<INPUT type=\"text\" name=\"INPUT_INSERT_info_gene_symbol_prop\" value=\"" + geneSymbol + "\">"

				content += '''
									</td>
									
									<td>
										The official gene symbol for this Insert.
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;5' Start
									</td>
					
									<td>
									'''

				content += "<INPUT type=\"text\" name=\"INPUT_INSERT_info_5_prime_start_prop\" value=\"" + fp_start + "\">"

				content += '''
									</td>
									
									<td>
										Users can map the start of the sequence onto its accession number by indicating at what nucleotide position on the accession number that the sequence starts.
									</td>
								</tr>
							
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;3' Stop
									</td>
					
									<td>
									'''

				content += "<INPUT type=\"text\" name=\"INPUT_INSERT_info_3_prime_stop_prop\" value=\"" + tp_stop + "\">"

				content += '''
									</td>
									
									<td>
										Users can map the stop of the sequence onto its accession number by indicating at what nucleotide position on the accession number that the sequence stops
									</td>
								</tr>
							
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;5' Linker
									</td>
					
									<td>
									'''

				content += "<INPUT type=\"text\" name=\"INPUT_INSERT_info_5_prime_linker_prop\" value=\"" + fwd_linker + "\">"

				content += '''
									</td>
									
									<td>
										Indicates any sequence added to the 5' end of an insert that does not map to a cDNA sequence but should be included as part of the insert - eg kozak sequences, any nucleotides added to maintain frame.
									</td>
								</tr>
									
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;3' Linker
									</td>
					
									<td>
									'''

				content += "<INPUT type=\"text\" name=\"INPUT_INSERT_info_3_prime_linker_prop\" value=\"" + rev_linker + "\">"

				content += '''
									</td>
									
									<td>
										Indicates any sequence added to the 3' end of an insert that does not map to a cDNA sequence but should be included as part of the insert - eg any nucleotides added to maintain frame.
									</td>
								</tr>
							
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Functional Description
									</td>
					
									<td>
									'''

				content += "<INPUT type=\"text\" name=\"INPUT_INSERT_info_description_prop\" value=\"" + funcDescr + "\">"

				content += '''
									</td>
									
									<td>
										Describes the purpose of the reagent and any information that may be key to working with the reagent.
									</td>
								</tr>
								
								<tr>

									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Verification
									</td>
					
									<td>
										<select size="1" name="INPUT_INSERT_info_verification_prop">
									'''
				# default option
				content += "<OPTION VALUE=\"\" "

				if len(verification) == 0:
					content += "SELECTED></OPTION>"
				else:
					content += "></OPTION>"
				
				
				for v in verificationList:
					content += "<OPTION VALUE=\"" + v + "\""
					
					if v == verification:
						content += " SELECTED>" + v + "</OPTION>"
					else:
						content += ">" + v + "</OPTION>"

				content += '''
										</select>
									</td>
									
									<td>
										Indicates if the reagent has been verified and how.  Users select from a pre-defined list.
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Verification comments
									</td>
					
									<td>
									'''

				content += "<INPUT type=\"text\" name=\"INPUT_INSERT_info_verification_comments_prop\" value=\"" + verComm + "\">"

				content += '''
									</td>
									
									<td>
										Allows user to provide a description of the verification status - eg comments on sequence changes, expression levels.
									</td>
								</tr>
								
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Experimental comments
									</td>
					
									<td>
									'''

				content += "<INPUT type=\"text\" name=\"INPUT_INSERT_info_comments_prop\" value=\"" + expComm + "\">"

				content += '''
									</td>
									
									<td>
										Used to describe any information that descrbes how the reagent works in the experimental context - eg low expression, difficult to grow, etc.
									</td>
								</tr>
									
								<tr>
									<td>
										&nbsp;&nbsp;&nbsp;&nbsp;Sequence
									</td>
					
									<td>
										&nbsp;
									</td>
					
									<td> 
										The sequence of the insert that corresponds to a cDNA or fragment of interest.  Does include artificial start and stop codons.  Does not include linker sequences as described above.
									</td>
								</tr>
						
								<tr>
									<td colspan="3">
										<textarea rows="10" cols="93" id="dna_sequence" name="INPUT_INSERT_info_sequence_prop"> 
										'''
				content += sequence
				content += '''
										</textarea>
									</td>
								</tr>
								'''
									
				content += self.printParents('Insert', allAssoc)
				content += '''
								<tr>
									<td colspan="3">
										<hr><br>
										&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="create_reagent" value="Create" onclick="document.pressed = this.value">
									</td>
								</tr>
							</table>
						</form>
					</div>
				'''

			# Common to all
			content += gOut.printFooter()
			page_content = content % (hostname + "cgi/create.py")
			
			print "Content-type:text/html"		# THIS IS PERMANENT; DO NOT REMOVE
			print					# DITTO
			print page_content
			
	
	# Output parent section based on reagent type
	def printParents(self, rType, assocDict):
		
		if rType == 'Insert':
			
			if assocDict.has_key("insert_parent_vector"):
				ipvID = assocDict["insert_parent_vector"]
			
			if assocDict.has_key("sense_oligo"):
				senseOligoID = assocDict["sense_oligo"]
			
			if assocDict.has_key("antisense_oligo"):
				antisenseOligoID = assocDict["antisense_oligo"]
				
			content = '''
				<tr>
					<td colspan="3" style="font-size:9pt; color:#0000CD; font-weight:bold; text-align:center">
						Parent Information:
					</td>
				</tr>
	
				<tr>
					<td>
						&nbsp;&nbsp;&nbsp;&nbsp;Sense Oligo ID
					</td>
	
					<td>
					'''
			content += "<input type=\"text\" id=\"insertSenseOligo\" name=\"assoc_sense_oligo_prop\" value=\"" + senseOligoID + "\">"
	
			content += '''
					</td>
					
					<td>
						Link to the sense (5') oligonucleotide used to make the insert (if applicable)
					</td>
				</tr>
	
				<tr>
					<td>
						&nbsp;&nbsp;&nbsp;&nbsp;AntiSense Oligo ID
					</td>
					
					<td>
					'''
			content += "<input type=\"text\" id=\"insertAntisenseOligo\" name=\"assoc_antisense_oligo_prop\" value=\"" + antisenseOligoID + "\">"
			
			content += '''
					</td>
	
					<td>
						Link to the antisense (3') oligonucleotide used to make the insert (if applicable)	
					</td>
				</tr>
			
				<tr>
					<td>
						&nbsp;&nbsp;&nbsp;&nbsp;Insert Parent Vector ID
					</td>
	
					<td>
					'''
					
			content += "<input type=\"text\" id=\"insertParentVector\" name=\"assoc_insert_parent_vector_prop\" value=\"" + ipvID + "\">"
	
			content += '''
					</td>
	
					<td>
						The vector that provided this insert
					</td>
				</tr>
				'''
			
		return content