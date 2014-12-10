#!/usr/local/bin/python

import cgi

import os
import tempfile
import stat

from database_conn import DatabaseConn

from user_handler import UserHandler
from lab_handler import LabHandler
from general_handler import UserCategoryHandler
from project_database_handler import ProjectDatabaseHandler

from mapper import UserCategoryMapper

from user import User
from session import Session

from general_output import GeneralOutputClass

import utils

#############################################################################################################
# This class handles the presentation layer of the website
# Generates HTML content for User and Lab views and writes it in files, which are then opened by User.php
#
# Written June 22, 2007 by Marina Olhovsky
# Last modified: July 5, 2007
#############################################################################################################
class UserOutputClass:

	def printUserInfo(self, cmd, user, errCode=""):
		
		dbConn = DatabaseConn()
		hostname = dbConn.getHostname()		# to define form action URL
		
		db = dbConn.databaseConnect()
		cursor = db.cursor()
		
		uHandler = UserHandler(db, cursor)
		lHandler = LabHandler(db, cursor)
		pHandler = ProjectDatabaseHandler(db, cursor)
		
		ucMapper = UserCategoryMapper(db, cursor)
		category_ID_Name_Map = ucMapper.mapCategoryIDToName()
		category_Name_ID_Map = ucMapper.mapCategoryNameToID()

		currUser = Session.getUser()
		
		gOut = GeneralOutputClass()
					
		if cmd =='create':
			
			username = user.getUsername()
			firstname = user.getFirstName()
			lastname = user.getLastName()
			email = user.getEmail()
			passwd = user.getPassword()
			
			lab = user.getLab()
			uLabID = lab.getID()
			uLabName = lab.getName()
			
			labs = lHandler.findAllLabs()

			# changed Aug. 18/08 - new format
			#content = gOut.printHeader() + gOut.printMainMenu()
			content = gOut.printHeader()
			
			content += '''
				<FORM NAME="create_user_form" METHOD="POST" ACTION="%s" onSubmit="return verifyAddUser();">

					<!-- pass current user as hidden form field -->
					<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username"'''
					
			content += "value=\"" + currUser.getFullName() + "\">"
			
			content += '''
					<TABLE width="760px" cellpadding="5" cellspacing="5">

						<TH colspan="4" style="color:#0000FF; border-top:1px groove black; border-bottom: 1px groove black; padding-top: 10px; padding-top:5px;">
							ADD NEW USER
							<P style="color:#FF0000; font-weight:normal; font-size:8pt; margin-top:5px;">Fields in red marked with an asterisk (<span style="font-size:9pt; color:#FF0000;">*</span>) are mandatory</P>
						</TH>

						<TR>
							<TD style="width:150px; vertical-align:top; padding-top:10px; color:#FF0000;">
								Laboratory:&nbsp;<sup style="font-size:10pt; color:#FF0000;">*</sup>
							</TD>

							<TD style="vertical-align:top; padding-top:10px">
								<SELECT id="labList" name="labs">
									<OPTION>Select Lab</OPTION>
								'''
			# sort labs by name
			labSortedDict = {}		# will store (labName, labID) tuples 
			labNames = []			# just hold lab names
			
			for labID in labs.keys():
				labName = labs[labID]
				labNames.append(labName)
				labSortedDict[labName] = labID
				
			labNames.sort()

			#for labID in labs.keys():
			for labName in labNames:
				labID = labSortedDict[labName]
				labName = labs[labID]
				content += "<OPTION ID=\"" + `labID` + "\" NAME=\"lab_optn\" VALUE=\"" + `labID` + "\""
				
				if labID == uLabID:
					content += " SELECTED>" + labName
				else:
					content += ">" + labName
					
				content += "</OPTION>"
					
			content += '''
								</SELECT>
								<BR/>
								<P id="lab_warning" style="color:#FF0000; display:none">Please select a laboratory name from the dropdown list above.</P>
							</TD>
						</TR>

						<TR>
							<TD class="createViewColName" style="color:#FF0000;">
								Username:&nbsp;<sup style="font-size:10pt; color:#FF0000;">*</sup>
							</TD>

							<TD class="createViewColValue">
								<INPUT TYPE="TEXT" SIZE="35px" id="user_name" NAME="username" VALUE="%s"/>
								<BR/>
								<!-- Warning anchor -->
								<a name="w1" style="text-decoration:none; font-weight:normal; font-size:8pt">
								
								<P id="dup_uname_warning" style="color:#FF0000; display:inline">This username already exists.  Please specify a different username.</P>
								</a>
							</TD>

							<TD style="font-size:8pt">
								Alphanumeric string up to 10 characters used to log into the system.
							</TD>
						</TR>

						<TR>
							<TD class="createViewColName" style="color:#FF0000;">
								Password:&nbsp;<sup style="font-size:10pt; color:#FF0000;">*</sup>
							</TD>

							<TD class="createViewColValue">
								<INPUT TYPE="PASSWORD" SIZE="35px" id="passwd" NAME="password" VALUE="%s"/>
							</TD>
						</TR>

						<TR>
							<TD class="createViewColName" style="color:#FF0000;">
								First name:&nbsp;<sup style="font-size:10pt; color:#FF0000;">*</sup>
							</TD>

							<TD class="createViewColValue">
								<INPUT TYPE="TEXT" SIZE="35px" id="first_name" NAME="firstName" VALUE="%s"/>
							</TD>
						</TR>

						<TR>
							<TD class="createViewColName" style="color:#FF0000;">
								Last name:&nbsp;<sup style="font-size:10pt; color:#FF0000;">*</sup>
							</TD>

							<TD class="createViewColName">
								<INPUT TYPE="TEXT" SIZE="35px" id="last_name" NAME="lastName" VALUE="%s"/>
							</TD>
						</TR>

						<TR>
							<TD class="createViewColName">
								Email:
							</TD>

							<TD class="createViewColValue">
								<INPUT TYPE="TEXT" SIZE="35px" id="e_mail" NAME="email" VALUE="%s"/>
							</TD>
						</TR>

						<TR>
							<TD>
								Access Level:
							</TD>

							<TD class="createViewColName"  colspan="3">
								<INPUT TYPE="RADIO" name="system_access_level" value="Reader" style="margin-top:8px; font-size:9pt" checked>Reader<BR/>
								<INPUT TYPE="RADIO" name="system_access_level" value="Writer" style="margin-top:8px; font-size:9pt">Writer<BR/>
								<INPUT TYPE="RADIO" name="system_access_level" value="Creator" style="margin-top:8px; font-size:9pt">Creator<BR/>
								<INPUT TYPE="RADIO" name="system_access_level" value="Admin" style="margin-top:8px; font-size:9pt">Admin<BR/>
							</TD>
						</TR>				

						<TR id="project_access">
							<TD colspan="4">
								<TABLE width="100%%">
									<TR>
										<TD colspan="4" style="border-top:1px groove black; border-bottom:1px groove black; padding-top:8px; font-size:8pt; font-weight:bold">
											Grant project access permissions to this user:
										</TD>
									</TR>

									<TR>
										<TD style="width:210px">
											<SELECT id="packetList" name="packets" multiple size="15">
											'''
			# PRINT PROJECT LIST
			projects = pHandler.findAllProjects()
			
			for project in projects:
				projectNumber = project.getNumber()	
				projectName = project.getName()
				
				tmpProject = `projectNumber` + ": " + projectName
				
				content += "<OPTION value=\"" + `projectNumber` + "\">" + tmpProject + "</OPTION>"
				
			content += '''
											</SELECT>
											<BR/>
											<INPUT TYPE="checkbox" style="margin-top:10px; font-size:8pt;" onClick="selectAll(this.id, 'packetList')" id="add_all_chkbx"> Select All</INPUT>
										</TD>

										<TD style="vertical-align:top" colspan="3">
											<span style="font-size:8pt; font-weight:bold">User's access level to selected projects:<BR/></span>
											<input type="radio" id="access_level_radio_read" name="access_levels" value="read" style="margin-top:8px; font-size:9pt" checked>Read-Only &nbsp;&nbsp;&nbsp;<BR/>
											<input type="radio" id="access_level_radio_write" name="access_levels" value="write" style="margin-top:5px; font-size:9pt">Write &nbsp;&nbsp;&nbsp;<BR/>
											<input style="margin-top:8px" onclick="addProjects('packetList', getSelectedRole('1'))" value="Go" type="button"></INPUT>

											<P style="font-size:8pt; border-top:1px groove black; padding-top:10px; padding-bottom:5px; margin-top:10px">
											Access levels: <BR/>
											<span style="font-size: 8pt; margin-left: 9px; font-weight:bold; ">&#45; Read-Only:</span>  May view reagents in a project but may NOT modify them or add new reagents<BR/>

											<span style="font-size: 8pt; margin-left: 9px; font-weight:bold;">&#45; Write:</span>  May create and modify reagents in a project but may NOT change project details or add/remove members to/from the project<BR/>
											</P>
										</TD>
									</TR>

									<TR>
										<TD colspan="4" style="border-top:1px groove black; border-bottom:1px groove black; font-size:8pt; font-weight:bold">
											User's current project access privileges:
										</TD>
									</TR>

									<TR>
										<TD style="border-right:1px solid black; font-size:8pt">
											<B>Read-Only</B><BR/>
											<SELECT id="user_projects_readonly" name="userProjectsReadonly" style="margin-top:5px" multiple size="12">
											'''
			# August 10/07: Default reader access to all on public projects
			publicProjects = pHandler.findAllProjects('FALSE')

			for proj in publicProjects: 
				pID = proj.getNumber()
				pName = proj.getName();
				
				# concatenate project ID and name in the form '1:parent'
				tmpDescr = `pID` + ": " + pName
				
				content += "<OPTION VALUE=\"" + `pID` + "\">" + tmpDescr + "</OPTION>"

			content += '''
											</SELECT><BR/>
											<INPUT style="margin-top:10px;" TYPE="checkbox" onClick="selectAll(this.id, 'user_projects_readonly')" id="select_all_reader_chkbx"> Select All</INPUT>
										</TD>

										<TD style="text-align:center; width:100px; border-right: 1px solid black; padding-left:20px; padding-right:20px;">
											<input onclick="addProjects('user_projects_readonly', 'write')" value="   Make Writeable >>" type="button"></INPUT><BR/>
											<input style="margin-top:30px;" onclick="addProjects('user_projects_write', 'read')" value="<< Make Read-Only" type="button"></INPUT><BR/>
											<input style="margin-top:30px;" onclick="addProjects('user_projects_write'); addProjects('user_projects_readonly')" value="Remove Selected" type="button"></INPUT>
										</TD>

										<TD style="padding-left:50px; font-size:8pt">
											<B>Write</B><BR/>
											<SELECT id="user_projects_write" name="userProjectsWrite" style="margin-top:5px" multiple size="12"></SELECT><BR/>
											<INPUT style="margin-top:10px;" TYPE="checkbox" onClick="selectAll(this.id, 'user_projects_write')" id="select_all_writer_chkbx"> Select All</INPUT>
										</TD>
									</TR>
								</TABLE>
							</TD>
						</TR>

						<TR>
							<TD colspan="4" style="border-top:1px groove black; border-bottom:1px groove black">
								<INPUT TYPE="SUBMIT" id="addUser" NAME="add_user" VALUE="Add User" onClick="selectAllElements('user_projects_readonly'); selectAllElements('user_projects_write');">
							</TD>
						</TR>
					</TABLE>
				</FORM>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				
				</div>
				'''
				
			content += gOut.printFooter()
			
			page_content = content % (hostname + "cgi/user_request_handler.py", username, passwd, firstname, lastname, email)
			
			print "Content-type:text/html"		# THIS IS PERMANENT; DO NOT REMOVE
			print					# DITTO
			print page_content

		elif cmd == 'view':

			userID = user.getUserID()
			username = user.getUsername()
			firstname = user.getFirstName()
			lastname = user.getLastName()
			email = user.getEmail()
			userCat = user.getCategory()
			lab = user.getLab()
			labID = lab.getID()
			labName = lab.getName()
			
			# Only allow modification by admin
			modify_disabled = True
			
			if (currUser.getCategory() == 'Admin'):
				modify_disabled = False
			
			content = gOut.printHeader()
			#content += gOut.printMainMenu()
			
			content += '''
				<FORM name="user_form" method="POST" action="%s">
							
					<!-- pass current user as hidden form field -->
					<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username"'''
					
			content += "value=\"" + currUser.getFullName() + "\">"
			
			content += '''
					<TABLE width="767px" style="margin-left:2px" cellpadding="5px" cellspacing="5px" class="detailedView_tbl" border="1" frame="box" rules="none">
						<TR>
							<TD colspan="6" class="detailedView_heading" style="padding-left:265px">
								USER DETAILS PAGE
								'''
			content += "<INPUT TYPE=\"submit\" style=\"margin-left:50px;\" name=\"modify_user\" value=\"Change User Details\""
			
			if modify_disabled:
				content += " disabled>"
			else:
				content += ">"
						
			content += "<INPUT TYPE=\"submit\" style=\"margin-left:2px;\" name=\"delete_user\" value=\"Delete User\" onClick=\"return verifyDeleteUser();\""
			
			if modify_disabled:
				content += " disabled>"
			else:
				content += ">"

				
			content += '''
							</TD>

						</TR>

						<TR>
							<TD class="projectDetailedViewName" width="50px">
								Username:
							</TD>

							<TD class="detailedView_value" colspan="2" style="width:400px">
								%s
								<INPUT TYPE="hidden" name="username" value="%s">

								<!-- user ID a hidden value -->
								<INPUT TYPE="hidden" name="userID" value="%d">
							</TD>
						</TR>

						<TR>
							<TD class="projectDetailedViewName" width="50px">
								First Name:
							</TD>

							<TD class="detailedView_value" colspan="2" style="width:400px">
								%s
								<INPUT TYPE="hidden" name="firstName" value="%s">
							</TD>
						</TR>

						<TR>
							<TD class="projectDetailedViewName" width="50px">
								Last Name:
							</TD>

							<TD class="detailedView_value" colspan="2" style="width:400px">
								%s
								<INPUT TYPE="hidden" name="lastName" value="%s">
							</TD>
						</TR>
						
						<TR>
							<TD class="projectDetailedViewName" width="50px">
								Laboratory:
							</TD>

							<TD class="detailedView_value" colspan="2" style="width:400px">
							'''
			if modify_disabled:
				content += labName
			else:
				content += "<span class=\"linkShow\" onClick=\"redirectToLabView(" + `labID` + ");\">" + labName + "</span>"
			
			content += '''
								<INPUT TYPE="hidden" name="labID" value="%d">
								<INPUT type="hidden" id="view_lab_hidden" name="view_lab">
							</TD>
						</TR>
						
						<TR>
							<TD class="projectDetailedViewName" width="50px">
								Email:
							</TD>

							<TD class="detailedView_value" colspan="2" style="width:400px">
								%s
								<INPUT TYPE="hidden" name="email" value="%s">
							</TD>
						</TR>
						
						<TR>
							<TD class="projectDetailedViewName" width="50px">
								Access Level:
							</TD>

							<TD class="detailedView_value" colspan="2" style="width:400px">
								%s
								<INPUT TYPE="hidden" name="system_access_level" value="%d">
							</TD>
						</TR>
						
						
						<TR>
							<TD class="projectDetailedViewName" width="50px">
								Projects:
							</TD>
							
						</TR>
						
						<TR>
							<TD style="font-weight:bold; font-size:8pt; width:250px" colspan="2">
								Read-Only:
							</TD>
							
							<TD style="font-weight:bold; font-size:8pt">
								Write:
							</TD>
						</TR>

						<TR>
							<TD style="vertical-align:top;" colspan="2">
								<UL>
								'''
			# show projects for the user
			publicProj = pHandler.findAllProjects("FALSE")
			readOnlyProj = pHandler.findMemberProjects(userID, 'Reader')
			readProj = utils.merge(publicProj, readOnlyProj)
			writeProj = pHandler.findMemberProjects(userID, 'Writer')
			
			# sort read projects
			readKeys = []
			readSorted = {}
			
			for r in readProj:
				rProjectID = r.getNumber()
				readKeys.append(rProjectID)
				readSorted[rProjectID] = r
			
			readKeys = utils.unique(readKeys)
			readKeys.sort()
			
			#for r in readProj:
			for rProjectID in readKeys:
				#rProjectID = r.getNumber()
				r = readSorted[rProjectID]
				rProjectName = r.getName()
				rProjectOwner = r.getOwner()

				try:
					rOwnerName = rProjectOwner.getLastName()
				except AttributeError:
					rOwnerName = ""

				#content += "<LI>" + `rProjectID` + ": " + rOwnerName + ": " + rProjectName
				
				content += "<LI>"
				content += "<span class=\"linkShow\" onClick=\"redirectToProjectDetailedView(" + `rProjectID` + ");\">" + `rProjectID` + ": " + rOwnerName + ": " + rProjectName + "</span>"
				content += "</LI>"

					
			content += '''
								</UL>
							</TD>
							
							<TD style="vertical-align:top;">
								<UL>
								'''
			# sort write projects
			writeKeys = []
			writeSorted = {}
			
			for w in writeProj:
				wProjectID = w.getNumber()
				writeKeys.append(wProjectID)
				writeSorted[wProjectID] = w
				
			writeKeys = utils.unique(writeKeys)
			writeKeys.sort()
			
			#for w in writeProj:
			for wProjectID in writeKeys:
				#wProjectID = w.getNumber()
				w = writeSorted[wProjectID]
				wProjectName = w.getName()
				wProjectOwner = w.getOwner()
				wOwnerName = wProjectOwner.getLastName()
										
				#content += "<LI>" + `wProjectID` + ": " + wProjectName
			
				content += "<LI>"
				content += "<span class=\"linkShow\" onClick=\"redirectToProjectDetailedView(" + `wProjectID` + ");\">" + `wProjectID` + ": " + wOwnerName + ": " + wProjectName + "</span>"
				content += "</LI>"

			content += '''
								</UL>
							</TD>
						</TR>
					</TABLE>
				</FORM>
				
				<FORM id="viewProjectForm" method="POST" action="%s">
					<INPUT type="hidden" id="view_packet_hidden" name="view_packet">
					<INPUT type="hidden" ID="curr_userid_hidden" NAME="curr_user_id" value="%d">
				</FORM>
				
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				
			</div>
			'''

			content += gOut.printFooter()
		
			page_content = content % (hostname + "cgi/user_request_handler.py", username, username, userID, firstname, firstname, lastname, lastname, labID, email, email, userCat, category_Name_ID_Map[userCat], hostname + "cgi/project_request_handler.py", currUser.getUserID())

			print "Content-type:text/html"		# 
			print					# DITTO
			print page_content

		elif cmd == 'edit':
					
			userID = user.getUserID()
			username = user.getUsername()
			firstname = user.getFirstName()
			lastname = user.getLastName()
			email = user.getEmail()
			passwd = user.getPassword()
			userCat = user.getCategory()
			
			lab = user.getLab()
			uLabID = lab.getID()
			labName = lab.getName()
			
			labs = lHandler.findAllLabs()
		
			if errCode == "Dup_un":
				un_warn_display = "inline"
			else:
				un_warn_display = "none"
			
			
			content = gOut.printHeader()
			#content += gOut.printMainMenu()
			
			content += '''
				<FORM name="user_form" method="POST" action="%s" onSubmit="return verifyWriteProjects();">
					
					<!-- pass current user as hidden form field -->
					<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username"'''
					
			content += "value=\"" + currUser.getFullName() + "\">"

			content += '''
					<TABLE width="760px" cellpadding="5px" cellspacing="5px" style="border:1px solid black" frame="box" rules="rows">
					<TR>
						<TD colspan="3" style="padding-left:200px; text-align:center">
							<span style="color:#0000FF; font-weight:bold">CHANGE USER INFORMATION</span>
							<INPUT TYPE="submit" style="margin-left:50px;" name="save_user" value="Save" onClick="selectAllElements('user_projects_readonly'); selectAllElements('user_projects_write');">
							<INPUT TYPE="submit" style="margin-left:20px;" name="cancel_user" value="Cancel">
						</TD>
					</TR>
					
					<TR>
						<TD class="projectDetailedViewName">
							Username:
						</TD>

						<TD class="detailedView_value" style="width:400px">
							<INPUT TYPE="text" size="50px" name="username" value="%s">
							<BR/>
							
							<!-- Warning anchor -->
							<a name="w1" style="text-decoration:none; font-weight:normal; font-size:8pt">
							<P id="dup_uname_warning" style="color:#FF0000; display:%s">This username already exists.  Please specify a different username.</P>
							</a>
							
							<!-- user ID hidden value -->
							<INPUT TYPE="hidden" name="userID" value="%d">
						</TD>
					</TR>


					<TR>
						<TD class="projectDetailedViewName">
							Laboratory:
						</TD>

						<TD style="vertical-align:top; padding-top:10px">
							<SELECT id="labList" name="labs">
							'''
			# sort labs by name
			labSortedDict = {}		# will store (labName, labID) tuples 
			labNames = []			# just hold lab names
			
			for labID in labs.keys():
				labName = labs[labID]
				labNames.append(labName)
				labSortedDict[labName] = labID
				
			labNames.sort()
			
			#for labID in labs.keys():
			for labName in labNames:
				labID = labSortedDict[labName]
				labName = labs[labID]
				content += "<OPTION ID=\"" + `labID` + "\" NAME=\"lab_optn\" VALUE=\"" + `labID` + "\""
				
				if labID == uLabID:
					content += " SELECTED>" + labName
				else:
					content += ">" + labName
					
				content += "</OPTION>"
					
			content += '''
							</SELECT>
						</TD>
					</TR>
					
					<TR>
						<TD class="projectDetailedViewName">
							First Name:
						</TD>

						<TD class="detailedView_value" colspan="2">
							<INPUT TYPE="text" size="50px" name="firstName" value="%s">
						</TD>
					</TR>

					<TR>
						<TD class="projectDetailedViewName">
							Last Name:
						</TD>

						<TD class="detailedView_value" colspan="2">
							<INPUT TYPE="text" size="50px" name="lastName" value="%s">
						</TD>
					</TR>
										
					<TR>
						<TD class="projectDetailedViewName">
							Email:
						</TD>

						<TD class="detailedView_value" colspan="2">
							<INPUT TYPE="text" size="50px" name="email" value="%s">
						</TD>
					</TR>
					
					<TR>
						<TD class="projectDetailedViewName">
							Access Level:
						</TD>
						
						<TD class="detailedView_value" colspan="2">
							<SELECT ID="user_category" NAME="system_access_level">
						'''
			ucHandler = UserCategoryHandler(db, cursor)
			categories = ucHandler.findAllCategories()
			
			for cID in categories.keys():
				
				if categories[cID] == userCat:
					content += "<OPTION VALUE=\"" + `cID` + "\" SELECTED>" + categories[cID] + "</OPTION>"
				else:
					content += "<OPTION VALUE=\"" + `cID` + "\">" + categories[cID] + "</OPTION>"

			# Don't allow addition of Writeable projects to Readers thru Modify view
			if userCat == 'Reader':
				write_disabled = True
			else:
				write_disabled = False
				
			content += '''
							</SELECT>
						</TD>
					</TR>
					
					<TR>
						<TD class="detailedView_value" colspan="3">
							Projects user has access to:
						</TD>
					</TR>

					<TR>
						<td colspan="3">
							<table width="700px">
								<tr>
									<TD colspan="2" style="font-size:8pt; vertical-align:top"">
										Read-Only
									</TD>
									
									<TD style="font-size:8pt; vertical-align:top">
									'''
			if not write_disabled:
				content += "Write"
			else:
				content += "&nbsp;"
			
			content += '''
									</TD>
								</TR>
								
								<TR>
									<TD style="">
										<SELECT id="user_projects_readonly" name="userProjectsReadonly" style="margin-top:5px" multiple size="12">
										'''
										
			# show projects for the user
			readProj = pHandler.findMemberProjects(userID, 'Reader')
			writeProj = pHandler.findMemberProjects(userID, 'Writer')
		
			for r in readProj:
				rProjectID = r.getNumber()
				rProjectName = r.getName()
				
				content += "<OPTION name=\"project_read\" value=\"" + `rProjectID` + "\">" + `rProjectID` + ": " + rProjectName + "</OPTION>"
					
			content += '''
										</SELECT>
										<BR/>
										
										<INPUT TYPE="checkbox" style="margin-top:10px;" onClick="selectAll(this.id, 'user_projects_readonly')" id="select_all_reader_chkbx"> Select All</INPUT>
						'''
			if not write_disabled:
				content += '''
									</TD>
			
									<TD style="text-align:center; padding-right:15px;">
			
										<input onclick="addProjects('user_projects_readonly', 'write')" value="   Make Writeable >>" type="button"></INPUT><BR/>
								
										<input style="margin-top:30px;" onclick="addProjects('user_projects_write', 'read')" value="<< Make Read-Only" type="button"></INPUT><BR/>
			
										&nbsp;<input type="button" style="margin-top:30px;" value="Remove" onclick="removeUserProjects();"></INPUT>
									</TD>
									'''
			
			else:
				content += '''
						&nbsp;<input type="button" value="Remove Selected" onclick="removeUserProjects();"></INPUT>
						'''
			if not write_disabled:
				content += '''	
									<TD style="font-size:8pt">
								
										<SELECT id="user_projects_write" name="userProjectsWrite" style="margin-top:5px" multiple size="12">
										'''
				for w in writeProj:
					wProjectID = w.getNumber()
					wProjectName = w.getName()
					
					content += "<OPTION name=\"project_write\" value=\"" + `wProjectID` + "\">" + `wProjectID` + ": " + wProjectName + "</OPTION>"
						
				content += '''
										</SELECT><BR/>
										
										<INPUT style="margin-top:10px;" TYPE="checkbox" onClick="selectAll(this.id, 'user_projects_write')" id="select_all_writer_chkbx"> Select All</INPUT>
									</TD>
									'''
				
			content += '''
								</TR>
							</table>
						</td>
					</tr>
						
					<TR>
						<TD class="detailedView_value" colspan="3">
							Add new projects:
						</TD>
					</TR>
					
					<TR>
						<TD colspan="3">
							<TABLE>
								<TR>
									<TD>
										<SELECT multiple ID="packetList">
							'''
			# Fetch the list of read and write projects for this user and extract their IDs
			readProjID = []	# list of numerical IDs of read projects
			
			for r in readProj:
				rNum = r.getNumber()
				readProjID.append(rNum)
							
			writeProjID = []
			
			for w in writeProjID:
				wNum = w.getNumber()
				writeProjID.append(wNum)
			
			allPackets = pHandler.findAllProjects()
			
			for p in allPackets:
				pID = p.getNumber()
				pName = p.getName()
				pOwner = p.getOwner()
			
				#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
				#print					# DITTO
				#print `pOwner`

				# update March 11, 2011
				try:
					lastName = pOwner.getLastName()
				except AttributeError:
					lastName = ""

				#pDesc = `pID` + " : " + pOwner + " : " + pName
				pDesc = `pID` + " : " + lastName + " : " + pName
				
				if not pID in readProjID and not pID in writeProjID:
					content += "<OPTION VALUE=\"" + `pID` + "\">" + pDesc

			content += '''
										</SELECT>
										<BR>
										<INPUT TYPE="checkbox" style="margin-top:10px; font-size:8pt;" onClick="selectAll(this.id, 'packetList')" id="add_all_chkbx"> Select All</INPUT>
						'''

			if not write_disabled:
				content += '''	
									</TD>
								
									<TD style="vertical-align:top">
										<span style="font-size:8pt; font-weight:bold">User's access level to selected projects:<BR/></span>
										<input type="radio" id="access_level_radio_read" name="access_levels" value="read" style="margin-top:8px; font-size:9pt" checked>Read-Only &nbsp;&nbsp;&nbsp;<BR/>
										<input type="radio" id="access_level_radio_write" name="access_levels" value="write" style="margin-top:5px; font-size:9pt">Write &nbsp;&nbsp;&nbsp;<BR/>
										<input style="margin-top:8px" onclick="addProjects('packetList', getSelectedRole('1'))" value="Add project" type="button"></INPUT>
									</TD>
								</TABLE>
							</TD>
						</TR>
						'''
			else:
				content += '''
						<input style="margin-left:5px; margin-top:8px" onclick="addProjects('packetList', 'read')" value="Add project" type="button"></INPUT>
						'''

			content += '''
					</TR>
				</TABLE>
			</FORM>
			
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>	
			</div>
			'''
				
			content += gOut.printFooter()
		
			page_content = content % (hostname + "cgi/user_request_handler.py", username, un_warn_display, userID, firstname, lastname, email)
		
			print "Content-type:text/html"		# THIS IS PERMANENT; DO NOT REMOVE
			print					# DITTO
			print page_content

	
	#############################################
	# Laboratory detailed view
	#############################################
	def printLabInfo(self, cmd, newLab, errCode=""):
		dbConn = DatabaseConn()
		hostname = dbConn.getHostname()		# to define form action URL
		
		db = dbConn.databaseConnect()
		cursor = db.cursor()
		
		uHandler = UserHandler(db, cursor)
		lHandler = LabHandler(db, cursor)
		
		currUser = Session.getUser()
		
		gOut = GeneralOutputClass()

		if cmd == 'view':

			labID = newLab.getID()
			labHead = newLab.getLabHead()
			labName = newLab.getName()
			labCode = newLab.getLabCode()
			labDescr = newLab.getDescription()
			address = newLab.getAddress()
			accessLevel = newLab.getDefaultAccessLevel()
			
			# Determine if 'Delete' button should be disabled - if there are members in the lab, disallow deletion
			labMembers = lHandler.findMembers(labID)

			delete_disabled = True

			if len(labMembers) == 0:
				delete_disabled = False
			
			# Only allow modification by admin
			modify_disabled = True
			
			# July 3/07: Can further disallow modification of labs other than the one currUser belongs to; however, this might be too restrictive.  Keep it in the back of our minds but out of the website for now.
			#if (currUser.getCategory() == 'Admin') and (currUser.getLab().getID() == labID):
			if (currUser.getCategory() == 'Admin'):
				modify_disabled = False
			
			#content = gOut.printHeader() + gOut.printMainMenu()
			content = gOut.printHeader()
			
			content += '''
				<FORM name="lab_form" method="POST" action="%s">
			
					<!-- pass current user as hidden form field -->
					<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username"'''
					
			content += "value=\"" + currUser.getFullName() + "\">"
			
			content += '''
					<TABLE width="775px" cellpadding="5px" cellspacing="5px" class="detailedView_tbl">
						<TR>
							<TD colspan="6" class="detailedView_heading" style="padding-left:250px">
								LABORATORY DETAILS PAGE
								'''
			content += "<INPUT TYPE=\"submit\" style=\"margin-left:50px;\" name=\"modify_lab\" value=\"Change Lab Info\""
			
			if modify_disabled:
				content += " disabled>"
			else:
				content += ">"
							
			content += "<INPUT TYPE=\"submit\" style=\"margin-left:2px;\" name=\"delete_lab\" value=\"Delete Lab\" onClick=\"return verifyDeleteLab()\""
			
			if modify_disabled or delete_disabled:
				content += " disabled>"
			else:
				content += ">"
				
			content += '''
							</TD>

						</TR>
					
						<TR>
							<TD class="projectDetailedViewName">
								Name:
							</TD>

							<TD class="detailedView_value" style="width:400px">
								%s
								<INPUT TYPE="hidden" name="labName" value="%s">

								<!-- lab ID a hidden value -->
								<INPUT TYPE="hidden" name="labID" value="%d">
							</TD>
						</TR>

						<TR>
							<TD class="projectDetailedViewName">
								Lab head:
							</TD>

							<TD class="detailedView_value" style="width:400px">
								%s
								<INPUT TYPE="hidden" name="labHead" value="%s">
							</TD>
						</TR>

						<TR>
							<TD class="projectDetailedViewName">
								Lab ID:
							</TD>

							<TD class="detailedView_value" style="width:400px">
								%s
								<INPUT TYPE="hidden" name="labCode" value="%s">
							</TD>
						</TR>
						
						<TR>
							<TD class="projectDetailedViewName">
								Description:
							</TD>

							<TD class="detailedView_value" style="width:400px">
								%s
								<INPUT TYPE="hidden" name="labDescription" value="%s">
							</TD>
						</TR>

						<TR>
							<TD class="projectDetailedViewName">
								Location:
							</TD>

							<TD class="detailedView_value" style="width:400px">
								%s
								<INPUT TYPE="hidden" name="address" value="%s">
							</TD>
						</TR>
						
						<TR>
							<TD class="projectDetailedViewName" style="white-space:nowrap">
								Default access level:
							</TD>

							<TD class="detailedView_value" style="width:400px">
								%s
								<INPUT TYPE="hidden" name="access" value="%s">
							</TD>
						</TR>
						
						<!-- Members -->
						<TR>
							<TD class="projectDetailedViewName">
								Members:
							</TD>
							
							<TD class="detailedView_value" style="width:400px">
								<UL>
							'''
			
			content += "<INPUT type=\"hidden\" id=\"view_user_hidden\" name=\"view_user\">"
				
			for member in labMembers:
				mName = member.getFullName()
				memberID = member.getUserID()
				#content += "<LI>" + mName
				content += "<LI>"
				content += "<span class=\"linkShow\" onClick=\"redirectToUserDetailedView(" + `memberID` + ");\">" + mName + "</span>"
				content += "</LI>"

			content += '''
								</UL>
							</TD>
						</TR>
					</TABLE>
				</FORM>
				
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
				<blockquote>&nbsp;</blockquote>
			</div>
			'''
				
			content += gOut.printFooter()
			
			page_content = content % (hostname + "cgi/user_request_handler.py", labName, labName, labID,  labHead, labHead, labCode, labCode, labDescr, labDescr, address, address, accessLevel, accessLevel)

			print "Content-type:text/html"		# THIS IS PERMANENT; DO NOT REMOVE
			print					# DITTO
			print page_content

		elif cmd == 'edit':
			
			labID = newLab.getID()
			labName = newLab.getName()
			labHead = newLab.getLabHead()
			labCode = newLab.getLabCode()
			labDescr = newLab.getDescription()
			address = newLab.getAddress()
			accLev = newLab.getDefaultAccessLevel()
			
			# Disable name modification if there are members in lab
			labMembers = lHandler.findMembers(labID)

			name_readonly = True

			if len(labMembers) == 0:
				name_readonly = False

			# hide/show duplicate lab code warning
			if errCode == 14:
				dup_lab_code_warn_display = "inline"
			else:
				dup_lab_code_warn_display = "none"
			
			#content = gOut.printHeader() + gOut.printMainMenu()
			content = gOut.printHeader()
			
			content += '''
				<FORM name="user_form" method="POST" action="%s">

					<!-- pass current user as hidden form field -->
					<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username"'''
			content += "value=\"" + currUser.getFullName() + "\">"

			content += '''
					<TABLE width="760px" cellpadding="5px" cellspacing="5px" style="border:1px solid black" frame="box" rules="rows">
					<TR>
						<TD colspan="3" style="padding-left:100px; text-align:center">
							<span style="color:#0000FF; font-weight:bold">CHANGE LABORATORY INFORMATION</span>
							<INPUT TYPE="submit" style="margin-left:180px;" name="save_lab" value="Save" onClick="selectAllElements('labMembersList'); return checkLab();">
							<INPUT TYPE="submit" style="margin-left:20px;" name="cancel_lab" value="Cancel">
						</TD>
					</TR>
					
					<TR>
						<TD class="projectDetailedViewName">
							Name:
						</TD>

						<TD class="detailedView_value" style="width:400px" colspan="2">
							'''
							
			if name_readonly:
				content += "<INPUT TYPE=\"text\" size=\"50px\" id=\"lab_name\" name=\"labName\" value=\"%s\" readonly>"
			else:
				content += "<INPUT TYPE=\"text\" size=\"50px\" id=\"lab_name\" name=\"labName\" value=\"%s\">"
				
			content += '''
							<!-- lab ID hidden value -->
							<INPUT TYPE="hidden" name="labID" value="%d">
						</TD>
					</TR>

					<TR>
						<TD class="projectDetailedViewName">
							Lab head:
						</TD>

						<TD class="detailedView_value" style="width:400px" colspan="2">
							<INPUT TYPE="text" size="50px" id="lab_head" name="labHead" value="%s">
						</TD>
					</TR>
					
					<TR>
						<TD class="projectDetailedViewName">
							Lab ID:
						</TD>

						<TD class="detailedView_value" style="width:400px" colspan="2">
							<INPUT TYPE="text" size="50px" id="lab_id" name="labCode" value="%s">
							<BR>
							<SPAN id="dup_labcode_warning" style="vertical-align:bottom; color:#FF0000; display:%s">This identifier already exists.  Please specify a different lab ID.</SPAN>
						</TD>
					</TR>

					<TR>
						<TD class="projectDetailedViewName">
							Description:
						</TD>

						<TD class="detailedView_value" colspan="2">
							<INPUT TYPE="text" size="50px" id="lab_descr" name="description" value="%s">
						</TD>
					</TR>

					<TR>
						<TD class="projectDetailedViewName">
							Location:
						</TD>

						<TD class="detailedView_value" colspan="2">
							<INPUT TYPE="text" size="50px" id="lab_location" name="address" value="%s">
						</TD>
					</TR>
										
					<TR>
						<TD style="width:50px; vertical-align:top; padding-top:10px; white-space:nowrap; font-size:8pt">
							Default access level:
						</TD>

						<TD style="font-size:8pt; vertical-align:top; width:50px;">
						'''
			# Determine which category radio button should be checked
			ucHandler = UserCategoryHandler(db, cursor)
			categories = ucHandler.findAllCategories()
			
			for cID in categories.keys():
				cat = categories[cID]
				content += "<INPUT TYPE=\"RADIO\" name=\"system_access_level\" value=\"" + cat + "\" style=\"margin-top:8px; font-size:8pt\" onClick=\"showHideProjectAccess()\""
				
				if cat == accLev:
					content += " checked"
				
				content += ">" + cat + "<BR/>"
				
			content += '''
						</TD>
					</TR>
					
					<!-- Members - allow deletion -->
					<TR>
						<TD style="width:50px; vertical-align:top; padding-top:10px; font-size:8pt">
							<b>Members:</b>
							<BR/><BR/>
							Select one or more members to remove them from the system.<BR/><BR/>
							
							(hold down CTRL key to select multiple names)
						</TD>

						<TD style="font-size:8pt; vertical-align:top; width:50px;">
							<SELECT MULTIPLE SIZE="10" ID="labMembersList" NAME="labMembers">
						'''
			members = lHandler.findMembers(labID)
			
			for  member in members:
				mName = member.getFullName()
				memberID = member.getUserID()
				
				content += "<OPTION value=\"" + `memberID` + "\">" + mName + "</OPTION>"
			
			content += '''
							</SELECT>
						</TD>
						
						<TD>
							<INPUT TYPE=\"button\" onClick=\"removeLabMembers('labMembersList')\" value=\"Remove Selected Members\">
						</TD>
					</TR>
				</TABLE>
			</FORM>
			
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
			<blockquote>&nbsp;</blockquote>
		
			</div>
			'''
			
			content += gOut.printFooter()
			
			page_content = content % (hostname + "cgi/user_request_handler.py", labName, labID, labHead, labCode, dup_lab_code_warn_display, labDescr, address)

			print "Content-type:text/html"		# THIS IS PERMANENT; DO NOT REMOVE
			print					# DITTO
			print page_content
