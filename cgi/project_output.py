#!/usr/local/bin/python

import os
#import tempfile
import stat
import sys

from database_conn import DatabaseConn
from user_handler import UserHandler
from lab_handler import LabHandler

from user import User
from session import Session
from laboratory import Laboratory

from general_output import GeneralOutputClass

import utils

class ProjectOutputClass:

	def printProjectInfo(self, cmd, project):
		
		dbConn = DatabaseConn()
		hostname = dbConn.getHostname()		# to define form action URL
		
		db = dbConn.databaseConnect()
		cursor = db.cursor()
		
		uHandler = UserHandler(db, cursor)
		lHandler = LabHandler(db, cursor)

		gOut = GeneralOutputClass()

		currUser = Session.getUser()

		if cmd == 'view':

			projectID = project.getNumber()
			projectOwner = project.getOwner()
			ownerName = projectOwner.getFullName()
			ownerID = projectOwner.getUserID()
			projectName = project.getName()
			projectDescr = project.getDescription()
			
			# private or public
			isPrivate = project.isPrivate()
			
			if isPrivate:
				accessType = 'Private'
			else:
				accessType = 'Public'
			
			# Only allow modification by owner or admin AND disallow project deletion if there are reagents in it!!!
			modify_disabled = True
			delete_disabled = True
			
			if (currUser.getUserID() == ownerID) or (currUser.getCategory() == 'Admin'):
				modify_disabled = False

			if project.isEmpty():
				delete_disabled = False

			# Aug. 18/08: Changed b/c of new format
			#content = gOut.printHeader() + gOut.printMainMenu()
			content = gOut.printHeader()
			
			content += '''
				<FORM name="project_form" method="POST" action="%s">
			
					<!-- pass current user as hidden form field -->
					<INPUT type="hidden" ID="username_hidden" NAME="username"'''
					
			content += "value=\"" + currUser.getFullName() + "\">"
			
			content += '''
					<TABLE height="100%%">
						<TABLE width="770px" cellpadding="5px" cellspacing="5px" class="detailedView_tbl">
							<TR>
								<TD class="detailedView_heading" style="white-space:nowrap;">
									PROJECT DETAILS PAGE
								</TD>
								
								<TD class="detailedView_heading" style="text-align:right">
									'''
			content += "<INPUT TYPE=\"submit\" name=\"modify_project\" value=\"Modify Project\""
			
			if modify_disabled:
				content += " disabled>"
			else:
				content += ">"
							
			content += "<INPUT TYPE=\"submit\" style=\"margin-left:2px;\" name=\"delete_project\" value=\"Delete Project\" onClick=\"return confirmDeleteProject();\""
			
			if modify_disabled or delete_disabled:
				content += " disabled>"
			else:
				content += ">"
				
			content += '''
								</TD>
	
							</TR>
	
							<TR>
								<TD class="projectDetailedViewName">
									Project #
								</TD>
	
								<TD class="detailedView_value" width="87%%">
									%d
									<INPUT TYPE="hidden" name="packetID" value="%d">
								</TD>
	
							</TR>
	
							<TR>
								<TD class="projectDetailedViewName">
									Project Owner:
								</TD>
	
								<TD class="detailedView_value">
									%s
									<INPUT TYPE="hidden" name="packetOwner" value="%d">
								</TD>
							</TR>
	
							<TR>
								<TD class="projectDetailedViewName">
									Project Name:
								</TD>
	
								<TD class="detailedView_value">
									%s
									<INPUT TYPE="hidden" name="packetName" value="%s">
								</TD>
							</TR>
	
							<TR>
								<TD class="projectDetailedViewName">
									Project Description:
								</TD>
	
								<TD class="detailedView_value">
									%s
									<INPUT TYPE="hidden" name="packetDescription" value="%s">
								</TD>
							</TR>
							
							<TR>
								<TD class="projectDetailedViewName">
									Access type:
								</TD>
	
								<TD class="detailedView_value">
									%s
									<INPUT TYPE="hidden" name="private_or_public" value="%s">
								</TD>
							</TR>
							
							<TR>
								<TD colspan="2">
									<HR/>
								</TD>
							</TR>
							
							'''
						
			# Now here, show or hide members section depending on the user's access level
			# Condition is the same as for determining whether modification is allowed - so use 'modify_disabled' variable
			if not modify_disabled:
				content += '''
							<TR>
								<TD class="projectDetailedViewName">
									Project Members:
								</TD>
								
								<TD>&nbsp;</TD>
							</TR>
							
							<TR>
								<TD class="detailedView_value" colspan="2">
									<TABLE width="100%%">
										<TR>
											<TD style="font-weight:bold; padding-left:10px" width="30%%">
												Readers:
											</TD>
	
											<TD style="font-weight:bold; padding-left:10px">
												Writers:
											</TD>
										</TR>
	
										<TR>
											<TD class="detailedView_value" style="vertical-align:top">
												<UL>
												'''
			
				if not isPrivate:
					content += "All OpenFreezer Users"
				else:
					# maintain the indent
					readers = project.getReaders()
					
					# sort by labs
					labs = []
					rdrLabs = {}
	
					# First, iterate over readers list to extract all the labs
					for rdr in readers:
						lab = rdr.getLab().getID()
	
						if lab not in labs:
							labs.append(lab)
	
					# Now iterate over the list of labs and link its readers to it
					for lab in labs:
						tmpRdrs = []		# list of members in one lab
	
						for rdr in readers:
							tmpLab = rdr.getLab().getID()
	
							if tmpLab == lab:
								# append reader to list of members of this lab
								if rdrLabs.has_key(lab):
									tmpRdrs = rdrLabs[lab]
	
								tmpRdrs.append(rdr)
								rdrLabs[lab] = tmpRdrs
	
					#for rdr in readers:
					for lab_id in rdrLabs.keys():
						rdrs = rdrLabs[lab_id]		# list of objects!!
						tmp_lab_name = lHandler.findLabName(lab_id)
	
						# print out the lab name
						
						if currUser.getCategory() == 'Admin':
							content += "<span class=\"linkShow\" style=\"color:#2E8B57\" onClick=\"goToLabViewFromProject(" + `lab_id` + ");\">" + tmp_lab_name + "</span><BR/>"
						else:
							content += "<span style=\"color:#2E8B57\">" + tmp_lab_name + "</span><BR/>"
	
						# print reader names
						for rdr in rdrs:
							content += "<INPUT TYPE=\"hidden\" name=\"projectReaders\" value=\"" + `rdr.getUserID()` + "\"></INPUT>"
							
							# Only show hyperlinks if the viewer is an Admin; otherwise just output plain names
							if currUser.getCategory() == 'Admin':
								content += "<LI style=\"list-style:none; padding-left:6px;\">&#45;&#45;&nbsp;&nbsp;<span class=\"linkShow\" onClick=\"redirectToUserFromProject(" + `rdr.getUserID()` + ");\">" + rdr.getFullName() + "</span></LI>"
							else:
								content += "<LI style=\"list-style:none; padding-left:6px;\">&#45;&#45;&nbsp;&nbsp;" + rdr.getFullName() + "</LI>"
											
				content += '''			
											</UL>
										</TD>


										<TD class="detailedView_value" style="width:250px; vertical-align:top">
											<UL>
											'''
				writers = project.getWriters()
				
				# sort them by lab too, same as for readers
				labs = []
				wrtrLabs = {}

				# First, iterate over readers list to extract all the labs
				for wrtr in writers:
					lab = wrtr.getLab().getID()

					if lab not in labs:
						labs.append(lab)

				# Now iterate over the list of labs and link its readers to it
				for lab in labs:
					tmpWrtrs = []		# list of members in one lab

					for wrtr in writers:
						tmpLab = wrtr.getLab().getID()

						if tmpLab == lab:
							# append reader to list of members of this lab
							if wrtrLabs.has_key(lab):
								tmpWrtrs = wrtrLabs[lab]

							tmpWrtrs.append(wrtr)
							wrtrLabs[lab] = tmpWrtrs


				for lab_id in wrtrLabs.keys():
					wrtrs = wrtrLabs[lab_id]		# list of objects!!
					tmp_lab_name = lHandler.findLabName(lab_id)

					# print out the lab name
					if currUser.getCategory() == 'Admin':
						content += "<span class=\"linkShow\" style=\"color:#2E8B57\" onClick=\"goToLabViewFromProject(" + `lab_id` + ");\">" + tmp_lab_name + "</span><BR/>"
					else:
						content += "<span style=\"color:#2E8B57\" " + `lab_id` + ");\">" + tmp_lab_name + "</span><BR/>"

					for wrtr in wrtrs:
	
						content += "<INPUT TYPE=\"hidden\" name=\"projectWriters\" value=\"" + `wrtr.getUserID()` + "\">"
						
						if currUser.getCategory() == 'Admin':
							content += "<LI style=\"list-style:none; padding-left:6px;\">&#45;&#45;&nbsp;&nbsp;<span class=\"linkShow\" onClick=\"redirectToUserFromProject(" + `wrtr.getUserID()` + ");\">" + wrtr.getFullName() + "</span></LI>"
						else:
							content += "<LI style=\"list-style:none; padding-left:6px;\">&#45;&#45;&nbsp;&nbsp;" + wrtr.getFullName() + "</LI>"
							
				content += '''
											</UL>
										</TD>
									</TR>
								</TABLE>
							</TD>	
						</TR>
					</TABLE>
				</FORM>
				
				<FORM id="viewUserForm" method="POST" action="%s">
					<INPUT type="hidden" id="view_user_hidden" name="view_user">
					<INPUT type="hidden" ID="curr_userid_hidden" NAME="curr_user_id" value="%d">
				</FORM>
				
				<FORM id="viewLabForm" method="POST" action="%s">
					<INPUT type="hidden" ID="curr_userid_hidden" NAME="curr_user_id" value="%d">
					<INPUT type="hidden" id="view_lab_hidden" name="view_lab">
				</FORM>
				</TABLE>
				'''
				
				content += gOut.printFooter()
				
			else:
				content += '''
					</TABLE>
				</FORM>
				</TABLE>
				'''
				
				content += gOut.printFooter()

			# and here, depending on what sections of the project view were printed, the number of arguments would vary
			if not modify_disabled:
				page_content = content % (hostname + "cgi/project_request_handler.py", projectID, projectID, ownerName, ownerID, projectName, projectName, projectDescr, projectDescr, accessType, accessType, hostname + "cgi/user_request_handler.py", currUser.getUserID(), hostname + "cgi/user_request_handler.py", currUser.getUserID())
			else:
				page_content = content % (hostname + "cgi/project_request_handler.py", projectID, projectID, ownerName, ownerID, projectName, projectName, projectDescr, projectDescr, accessType, accessType)

			print "Content-type:text/html"		# THIS IS PERMANENT; DO NOT REMOVE
			print					# DITTO
			print page_content

		elif cmd == 'edit':
				
			projectID = project.getNumber()
			projectOwner = project.getOwner()
			ownerName = projectOwner.getFullName()
			ownerID = projectOwner.getUserID()
			projectName = project.getName()
			projectDescr = project.getDescription()
			isPrivate = project.isPrivate()
			
			content = gOut.printHeader()
			#content += gOut.printMainMenu()
			
			content += '''
				<FORM name="project_form" method="POST" action="%s">

					<!-- pass current user as hidden form field -->
					<INPUT type="hidden" ID="username_hidden" NAME="username"'''
			content += "value=\"" + currUser.getFullName() + "\">"

			content += '''
					<TABLE width="770px" cellpadding="5px" cellspacing="5px" style="border:1px solid black" frame="box" rules="rows">
					<TR>
						<TD colspan="3" style="padding-left:200px; text-align:center">
							
							<span style="color:#0000FF; font-weight:bold">MODIFY PROJECT </span>
							<span style="color:#FF0000; font-weight:bold">%d</span>
							
							<INPUT TYPE="hidden" name="packetID" value="%d">
							
							<INPUT TYPE="submit" style="margin-left:200px;" name="save_project" value="Save" onClick=\"alert('Please note: If your project writers list contains names of users who have read-only access to OpenFreezer, their names will be removed from the list during saving.'); addProjectOwnerToWritersList(); selectAllElements('readers_target_list'); selectAllElements('writers_target_list'); return verifyProjectOwner('projectOwnersList') && verifyProjectName('packet_name') && verifyProjectDescr('packet_descr') && verifyMembers('readers_target_list') && verifyMembers('writers_target_list');\">
							
							<INPUT TYPE="submit" style="margin-left:20px;" name="cancel_project" value="Cancel">
						</TD>
					</TR>

					<TR>
						<TD class="projectDetailedViewName">
							Project Owner:
						</TD>

						<TD class="detailedView_value" colspan="2">
							<SELECT ID="projectOwnersList" name="packetOwner">
							'''

			# Get list of all potential project owners - users with 'CREATOR' or higher privileges
			# Returns list of User **objects**
			creators = uHandler.findAllMembersInCategory('Creator', False, '<=')
			creatorsDict = {}	# name, uid
			
			for creator in creators:
				uid = creator.getUserID()
				name = creator.getFullName()
				creatorsDict[name] = uid
				
			names = creatorsDict.keys()
			names.sort()
			
			#print "Content-type:text/html"
			#print
			
			for name in names:
				#print name
				uid = creatorsDict[name]
				#print uid
				#print ownerID

				if uid == ownerID:
					content += "<OPTION SELECTED value=" + `uid` + ">" + name + "</OPTION>"
				else:
					content += "<OPTION value=" + `uid` + ">" + name + "</OPTION>"
							
			content += '''
							</SELECT>
							
							<DIV ID="projectOwnerWarning" STYLE="display:none; color:#FF0000; font-weight:normal;">
								<BR>Please select a name from the list above.
							</DIV>
						</TD>
					</TR>

					<TR>
						<TD class="projectDetailedViewName">
							Project Name:
						</TD>

						<TD class="detailedView_value" colspan="2">
							<INPUT TYPE="text" id="packet_name" name="packetName" value="%s">
							
							<DIV ID="projectNameWarning" STYLE="display:none; color:#FF0000; font-weight:normal;">
								<BR>Please provide a project name.
							</DIV>
						</TD>
					</TR>

					<TR>
						<TD class="projectDetailedViewName">
							Project Description:
						</TD>

						<TD class="detailedView_value" colspan="2">
							<INPUT TYPE="text" id="packet_descr" name="packetDescription" value="%s">
							
							<DIV ID="projectDescrWarning" STYLE="display:none; color:#FF0000; font-weight:normal;">
								<BR>Please provide a project description.
							</DIV>
						</TD>
					</TR>
					
					
					<TR>
						<TD class="projectDetailedViewName">
							Access type:
						</TD>

						<TD class="detailedView_value" style="width:400px">
						'''
			if not isPrivate:
				content += "<INPUT TYPE=\"RADIO\" NAME=\"private_or_public\" VALUE=\"public\" checked>Public&nbsp;&nbsp;&nbsp;&nbsp;"
				content += "<INPUT TYPE=\"RADIO\" NAME=\"private_or_public\" VALUE=\"private\">Private"
			else:
				content += "<INPUT TYPE=\"RADIO\" NAME=\"private_or_public\" VALUE=\"public\">Public&nbsp;&nbsp;&nbsp;&nbsp;"
				content += "<INPUT TYPE=\"RADIO\" NAME=\"private_or_public\" VALUE=\"private\" checked>Private"
				
			content += '''
						</TD>
					</TR>

					<TR>
						<TD class="projectDetailedViewName">
							Project Members:
						</TD>

						<TD class="detailedView_value" colspan="2">
							&nbsp;
						</TD>
					</TR>
					
					<TR>
						<TD class="detailedView_value" colspan="3">
							Edit existing project members lists:
						</TD>
					</TR>

					<TR>
						<TD style="width:100px">
							<SELECT multiple size="10" id="readers_target_list" name="readersList">
						'''
			# Readers and writers associated with this project
			currReaders = project.getReaders()
			currWriters = project.getWriters()
	
			# Since object comparison is done by reference, cannot check if a User object returned by findAllMembers is a member of this project by using 'in array'.  Need to compare user IDs explicitly
			currReaderIDs = []
			currWriterIDs = []

			currReaderNames = []
			currWriterNames = []

			currReadersDict = {}	# name, id
			currWritersDict = {}

			# need lab IDs too - to match members to their labs when moved between lists, but having a 'memberID, labID' dictionary is too clumsy.  Easiest approach: have 'memberID, Member Object' dictionary
			currReaderObjDict = {}	# id, User object
			currWriterObjDict = {}

			for r in currReaders:
				rID = r.getUserID()
				rName = r.getFullName()
				
				# associate rID with its containing object 
				currReaderObjDict[rID] = r
				
				currReaderIDs.append(rID)
				currReaderNames.append(rName)
				currReadersDict[rName] = rID


			for w in currWriters:
				wID = w.getUserID()
				wName = w.getFullName()
				
				currWriterObjDict[wID] = w

				currWriterIDs.append(wID)
				currWriterNames.append(wName)
				currWritersDict[wName] = wID

			currReaderNames.sort()
			currWriterNames.sort()
			
			for rName in currReaderNames:
				rID = currReadersDict[rName]
				rdr = currReaderObjDict[rID]
				rdrLabID = rdr.getLab().getID()
				
				#content += "<OPTION id=" + `rID` + " value=" + `rID` + ">" + rName + "</OPTION>"
				
				# June 28/07: Include labID in the option id 
				content += "<OPTION id=\"user_" + `rID` + "_lab_" + `rdrLabID` + "\" value=" + `rID` + ">" + rName + "</OPTION>"

			content += '''
							</SELECT>
							<BR/>
							<INPUT TYPE="checkbox" style="margin-top:10px" onClick="selectAll(this.id, 'readers_target_list')" id="select_all_reader_chkbx"> Select All</INPUT>
						</TD>

						<TD width="30px">
							<input onclick="addMembers('readers_target_list', 'write')" value="   Make Writer >>" type="button"></INPUT><BR/>
							<input style="margin-top:10px;" onclick="addMembers('writers_target_list', 'read')" value="<< Make Reader" type="button"></INPUT><BR/>
							<input style="margin-top:10px;" onclick="removeProjectMembers()" value="Remove Selected" type="button"></INPUT>
						</TD>

						<TD>
							<SELECT multiple size="10" id="writers_target_list" name="writersList">
						'''
			for wName in currWriterNames:
				wID = currWritersDict[wName]
				wrtr = currWriterObjDict[wID]
				wrtrLabID = wrtr.getLab().getID()
				
				#content += "<OPTION id=" + `wID` + " value=" + `wID` + ">" + wName + "</OPTION>"
				
				# June 28/07: Include labID in the option id 
				content += "<OPTION id=\"user_" + `wID` + "_lab_" + `wrtrLabID` + "\" value=" + `wID` + ">" + wName + "</OPTION>"

			content += '''
							</SELECT>
							
							<BR/>
							<INPUT style="margin-top:10px;" TYPE="checkbox" onClick="selectAll(this.id, 'writers_target_list')" id="select_all_writer_chkbx"> Select All</INPUT>
						</TD>
					</TR>
					
					<TR>
						<TD class="detailedView_value" colspan="3">
							Add new members to this project:
						</TD>
					</TR>
					
					<TR>
						<TD class="detailedView_value" colspan="3">
							Laboratory:&nbsp;&nbsp;&nbsp;&nbsp;
			
							<SELECT id="labList" name="labs" onChange="showLabMembersList()">
							'''
			# fetch lab list - Updated August 90/7: Fetch ALL labs, with any access - then if a read-only lab has members with higher access, would show these members in list
			#labs = lHandler.findAllLabs('Writer', '<=')
			labs = lHandler.findAllLabs()
			
			# sort lab names alphabetically
			labNames = []
			labsDict = {}	# name, id
			
			for labID in labs.keys():
				labName = labs[labID]
				labNames.append(labName)
				labsDict[labName] = labID
			
			labNames.sort()
			
			currLab = projectOwner.getLab()
			currLabID = currLab.getID()
			
			#for labID in labs.keys():
			for labName in labNames:
				#labName = labs[labID]
				labID = labsDict[labName]
				
				if labID == currLabID:
					content += "<OPTION SELECTED id='" + `labID` + "' NAME='lab_optn' value=" + `labName` + ">" + labName + "</OPTION>"
				else:
					content += "<OPTION id='" + `labID` + "' NAME='lab_optn' value=" + `labName` + ">" + labName + "</OPTION>"

			content += '''
							</SELECT>
						</TD>
					</TR>
					
					<TR>
						<TD width="100px">
							'''
							
			# For each lab, print a list of its members
			for labID in labs.keys():
	
				# First, fetch a list of users 
				# These are **User instances** - need to get their names and IDs for comparison
				
				# August 9/07: Don't fetch only writers, fetch readers too - it's up to the project owner to grant them access to the project
				#writers = uHandler.findAllMembersInCategory('Writer', True, '<=', labID)
				writers = uHandler.findAllMembersInCategory('Reader', True, '<=', labID)
				writersDict = {}	# name, uid
				writersObjDict = {}	# id, User object
				
				# Fetch user IDs and sort their names alphabetically
				for writer in writers:
					name = writer.getFullName()
					uid = writer.getUserID()
					labID = (writer.getLab()).getID()
					writersDict[name] = uid
					writersObjDict[uid] = writer
					
				names = writersDict.keys()
				names.sort()

				# Show members for one lab at a time
				if labID == currLabID:
					display = "inline"
				else:
					display = "none"
				
				content += "<SELECT MULTIPLE id=\"lab_source_list_" + `labID` + "\" name=\"labSourceMembers_" + `labID` + "\" SIZE=\"10\" style=\"display:" + display + "\">"

				for name in names:
					uid = writersDict[name]
					labID = writersObjDict[uid].getLab().getID()
					
					if uid not in currReaderIDs and uid not in currWriterIDs:
						#content += "<OPTION value=" + `uid` + ">" + name + "</OPTION>"
						content += "<OPTION id=\"user_" + `uid` + "_lab_" + `labID` + "\" value=" + `uid` + ">" + name + "</OPTION>"
				
				content += "</SELECT>"
				
			content += '''
							<BR/>
							<INPUT TYPE="checkbox" style="margin-top:8px" onClick="selectAll(this.id, 'lab_source_list_' + getSelectedLab())" id="add_all_chkbx"> Select All Members</INPUT>
						</TD>

						<TD colspan="2" style="vertical-align:top">
							Add selected members to:
							
							<P style="font-size:9pt; margin-top:5px;">
								<input type="radio" id="access_level_radio_read" name="access_levels" value="read" checked>Readers list</INPUT><BR/> 
								<input type="radio" id="access_level_radio_write" name="access_levels" value="write">Writers list</INPUT><BR/>
								<input style="margin-top:8px" onclick="addMembers('lab_source_list_' + getSelectedLab(), getSelectedRole('1'))" value="Go" type="button"></INPUT>
								<BR/>
							</P>

						</TD>
					</TR>
				</TABLE>
			</FORM>
			'''
				
			content += gOut.printFooter()
		
			page_content = content % (hostname + "cgi/project_request_handler.py", project.getNumber(),  project.getNumber(), project.getName(), project.getDescription())		

			print "Content-type:text/html"		# THIS IS PERMANENT; DO NOT REMOVE
			print					# DITTO
			print page_content
