#!/usr/local/bin/python

import os
import tempfile
import stat
import sys

from database_conn import DatabaseConn
from user_handler import UserHandler
from lab_handler import LabHandler

from user import User
from session import Session
from laboratory import Laboratory
from mapper import UserCategoryMapper, SystemModuleMapper	# updated Aug. 20, 2010

import utils

class GeneralOutputClass:
	
	def printHeader(self):
		
		content = '''
			<HTML>
				<HEAD>
					<link href="../styles/SearchStyle.css" rel="stylesheet" type="text/css">
					<link href="../styles/Header_styles.css" rel="stylesheet" type="text/css">

					<LINK REL="stylesheet" HREF="../styles/generic.css" type="text/css"/>

					<script type="text/javascript" language="JavaScript1.2" src="../scripts/menu.js"></script>
				</HEAD>

				<BODY style="width:98%%" onLoad="initAll()">

					<table cellpadding="0" cellspacing="0" style="width:100%%; background:black;">
						<tr>
							<td colspan="3"><img src="../pictures/hdr.png"></td>
                                                        <td style="padding-right:2px; background-color:black; padding-top:3px; padding-bottom:2px;"><img src="../pictures/tux.PNG" height="68" ALT="logo"></td>
					
						</tr>
					</table>
				
	                                <div class="notice" style="font-size:9pt; width:100%%; text-align:center;">Best viewed in <a href="http://www.mozilla.com/en-US/firefox/">Mozilla Firefox&#174;</a> version 2 or later</div>
	
					'''
		
		content += self.printMainMenu()
		
		content += '''
					<Div class="main">
						<DIV class="main_content" style="padding:5px;" ID="mainDiv">
						'''
		
		return content


	def printFooter(self):
		content = '''
					</DIV>	<!--close 'main'-->
				</DIV>	<!--close 'mainDiv'-->
				
				<DIV class="footer_container">
					<DIV class="footer">
						Copyright &#169;Mount Sinai Hospital, Toronto, Canada.
					</DIV>
				</DIV>
			</BODY>
		</HTML>
		'''
		
		return content
		
		
	# Returns a string containing HTML content for the side menu
	def printMainMenu(self):

		dbConn = DatabaseConn()
		hostname = dbConn.getHostname()		# to define form action URL
		
		db = dbConn.databaseConnect()
		cursor = db.cursor()

		uHandler = UserHandler(db, cursor)
		
		# Aug. 20, 2010
		pageMapper = SystemModuleMapper(db, cursor)
		
		pageLinkMap = pageMapper.mapPageNameLink()
		
		# Array of section names
		currentSectionNames = []

		# Dictionary of links to names, with names as dictionary keys and links as values
		currentSectionLinks = {}

		# Added Nov. 10/06 by Marina - Classify each header as to what OF section it belongs
    		menuTypes = {}

		# June 04/07 - Differentiate between 'public' and 'private' pages
		publicSectionNames = []
    		publicSectionLinks = []

		publicSections = {}
		
		# Feb. 2, 2010: change menu layout (reflect HeaderFunctions.php code changes Jan. 12/10)
		submenu_links = {}
		submenu_types = {}
		menuitems = {}
		
		# Home
		currentSectionNames.append("Home")
		currentSectionLinks["Home"] = "../index.php"
		publicSections["Home"] = "index.php"

		# Reagent
		currentSectionNames.append("Reagent Tracker")
		currentSectionLinks["Reagent Tracker"] = "../Reagent.php?View=1"
		
		menuTypes["Reagent Tracker"] = "Reagent"
		publicSections["Reagent Tracker"] = "../Reagent.php?View=1"
		
		# Feb. 2, 2010
		tmp_list = []
		tmp_list.append("Reagents")
		tmp_list.append("Reagent Types")
		
		submenu_types["Reagent Tracker"] = tmp_list
		
		tmp_order_list = {}
		tmp_order_list[0] = "Add"
		tmp_order_list[1] = "Search"
		tmp_order_list[2] = "Statistics"

		submenu_order = {}
		submenu_order["Reagents"] = tmp_order_list

		tmp_list = {}
		tmp_list["Add"] = "../Reagent.php?View=2"
		tmp_list["Search"] = "../search.php?View=1"
		tmp_list["Statistics"] = "../Reagent.php?View=4"
		
		submenu_links["Reagents"] = tmp_list
		
		tmp_list = {}
		tmp_list["Add"] = "Add reagents"
		tmp_list["Search"] = "Search reagents"
		tmp_list["Statistics"] = "Statistics"
	
		menuitems["Reagents"] = tmp_list
	
		tmp_order_list = {}
		tmp_order_list[0] = "Add"
		tmp_order_list[1] = "Search"
	
		submenu_order["Reagent Types"] = tmp_order_list

		tmp_list = {}
		tmp_list["Add"] = "../Reagent.php?View=3"
		tmp_list["Search"] = "../Reagent.php?View=5"
		submenu_links["Reagent Types"] = tmp_list
		
		tmp_list = {}
		tmp_list["Add"] = "Add reagent types"
		tmp_list["Search"] = "Search reagent types"
		menuitems["Reagent Types"] = tmp_list
		
		# Locations
		currentSectionNames.append("Location Tracker")
		currentSectionLinks["Location Tracker"] = "../Location.php?View=1"
		
		menuTypes["Location Tracker"] = "Location"
		publicSections["Location Tracker"] = "../Location.php?View=1"

		# Feb. 2/10
		tmp_list = []
		tmp_list.append("Containers")
		tmp_list.append("Container Sizes")
		tmp_list.append("Container Types")
		submenu_types["Location Tracker"] = tmp_list
		
		tmp_order_list = {}
		tmp_order_list[0] = "Add"
		tmp_order_list[1] = "Search"
	
		submenu_order["Container Types"] = tmp_order_list
	
		tmp_order_list = {}
		tmp_order_list[0] = "Add"
		#tmp_order_list[1] = "Search"
	
		submenu_order["Container Sizes"] = tmp_order_list
	
		tmp_order_list = {}
		tmp_order_list[0] = "Add"
		tmp_order_list[1] = "Search"
	
		submenu_order["Containers"] = tmp_order_list
	
		tmp_list = {}
		tmp_list["Add"] = "../Location.php?View=6&Sub=2"
		tmp_list["Search"] = "../Location.php?View=6&Sub=4"
		submenu_links["Container Types"] = tmp_list
	
		tmp_list = {}
		tmp_list["Add"] = "Add container types"
		tmp_list["Search"] = "Search container types"
		menuitems["Container Types"] = tmp_list
		
		tmp_list = {}
		tmp_list["Add"] = "../Location.php?View=6&Sub=1"
		tmp_list["Search"] = "../Location.php?View=6&Sub=5"
		submenu_links["Container Sizes"] = tmp_list
	
		tmp_list = {}
		tmp_list["Add"] = "Add container sizes"
		#tmp_list["Search"] = "Search container sizes"
		menuitems["Container Sizes"] = tmp_list
		
		tmp_list = {}
		tmp_list["Add"] = "../Location.php?View=6&Sub=3"
		tmp_list["Search"] = "../Location.php?View=2"
		submenu_links["Containers"] = tmp_list
		
		tmp_list = {}
		tmp_list["Add"] = "Add containers"
		tmp_list["Search"] = "Search containers"
		menuitems["Containers"] = tmp_list
		
		# Projects
		currentSectionNames.append("Project Management")
		currentSectionLinks["Project Management"] = "../Project.php?View=1"
		menuTypes["Project Management"] = "Project"
		
		# Feb. 2/10
		tmp_list = []
		tmp_list.append("Projects")
		submenu_types["Project Management"] = tmp_list

		tmp_order_list = {}
		tmp_order_list[0] = "Add"
		tmp_order_list[1] = "Search"
	
		submenu_order["Projects"] = tmp_order_list
	
		tmp_list = {}
		tmp_list["Add"] = "../Project.php?View=1"
		tmp_list["Search"] = "../Project.php?View=2"
		submenu_links["Projects"] = tmp_list
	
		tmp_list = {}
		tmp_list["Add"] = "Add projects"
		tmp_list["Search"] = "Search projects"
		menuitems["Projects"] = tmp_list

		# Users and Labs
		currentSectionNames.append("User Management")
		currentSectionLinks["User Management"] = "../User.php"
		menuTypes["User Management"] = "User"

		currentSectionNames.append("Lab Management")
		currentSectionLinks["Lab Management"] = "../User.php"
		menuTypes["Lab Management"] = "Laboratories"

		tmp_order_list = {}
		tmp_order_list[0] = "Add"
		tmp_order_list[1] = "Search"
	
		submenu_order["Laboratories"] = tmp_order_list
	
		# Jan. 7/09: Chemicals
		currentSectionNames.append("Chemical Tracker")
		currentSectionLinks["Chemical Tracker"] = "../Chemical.php?View=1"
		menuTypes["Chemical Tracker"] = "Chemical"
		
		# Feb. 2, 2010
		tmp_list = []
		tmp_list.append("Chemicals")
		submenu_types["Chemical Tracker"] = tmp_list
		
		tmp_order_list = {}
		tmp_order_list[0] = "Add"
		tmp_order_list[1] = "Search"
	
		submenu_order["Chemicals"] = tmp_order_list
	
		tmp_list = {}
		tmp_list["Add"] = "../Chemical.php?View=2"
		tmp_list["Search"] = "../Chemical.php?View=1"
		submenu_links["Chemicals"] = tmp_list
		
		tmp_list = {}
		tmp_list["Add"] = "Add Chemicals"
		tmp_list["Search"] = "Search Chemicals"
		menuitems["Chemicals"] = tmp_list

		# Feb. 2/10
		tmp_list = []
		tmp_list.append("Users")
		submenu_types["User Management"] = tmp_list

		tmp_list = {}
		tmp_list["Add"] = "../User.php?View=1"
		tmp_list["Search"] = "../User.php?View=2"
		tmp_list["Change your password"] = "../User.php?View=6"
		tmp_list["Personal page"] = "../User.php?View=7"
		tmp_list["View your orders"] = "../User.php?View=8"
		submenu_links["Users"] = tmp_list
	
		tmp_order_list = {}
		tmp_order_list[0] = "Add"
		tmp_order_list[1] = "Search"
		tmp_order_list[2] = "Change your password"
		tmp_order_list[3] = "Personal page"
		tmp_order_list[4] = "View your orders"
		
		submenu_order["Users"] = tmp_order_list
	
		tmp_list = {}
		tmp_list["Add"] = "Add users"
		tmp_list["Search"] = "Search users"
		tmp_list["Change your password"] = "Change your password"
		tmp_list["Personal page"] = "Personal page"
		tmp_list["View your orders"] = "View your orders"
		menuitems["Users"] = tmp_list
		
		tmp_list = []
		tmp_list.append("Laboratories")
		submenu_types["Lab Management"] = tmp_list
		
		tmp_list = {}
		tmp_list["Add"] = "../User.php?View=3"
		tmp_list["Search"] = "../User.php?View=4"
		submenu_links["Laboratories"] = tmp_list
		
		tmp_order_list = {}
		tmp_order_list[0] = "Add"
		tmp_order_list[1] = "Search"
		submenu_order["Laboratories"] = tmp_order_list
		
		tmp_list = {}
		
		tmp_list["Add"] = "Add laboratories"
		tmp_list["Search"] = "Search laboratories"
		menuitems["Laboratories"] = tmp_list

		currentSectionNames.append("Documentation")
		currentSectionLinks["Documentation"] = "../docs.php"
		publicSections["Documentation"] = "docs.php"

 		currentSectionNames.append("Terms and Conditions")
 		currentSectionLinks["Terms and Conditions"] = "../copyright.php"
		publicSections["Terms and Conditions"] = "copyright.php"

		currentSectionNames.append("Help and Support")
 		currentSectionLinks["Help and Support"] = "../bugreport.php"
		publicSections["Help and Support"] = "bugreport.php"

 		currentSectionNames.append("Contact Us")
 		currentSectionLinks["Contact Us"] = "../contacts.php"
		publicSections["Contact Us"] = "contacts.php"
		
		# Aug. 20/10: Quick links
		
		tmp_ql = []
		quickLinks = {}

		tmp_ql.append("Add reagents")
		tmp_ql.append("Search reagents")

		quickLinks["Reagent Tracker"] = tmp_ql
		
		tmp_ql = []
		
		tmp_ql.append("Add containers")
		tmp_ql.append("Search containers")
	
		quickLinks["Location Tracker"] = tmp_ql
		
		tmp_ql = []
		
		tmp_ql.append("Add projects")
		tmp_ql.append("Search projects")
		
		quickLinks["Project Management"] = tmp_ql
		
		tmp_ql = []
		
		tmp_ql.append("Change your password")
		tmp_ql.append("View your orders")
		
		quickLinks["User Management"] = tmp_ql
		
		content = '''
			<div class="sidemenu" ID="mainMenu">
				<div class="menu-content">
					<ul class="menulist">
						<!-- menu goes here -->
						'''
		
		# Output the menu link IFF the user is authorized to access that page
		currUser = Session.getUser()

		if currUser:
			ucMapper = UserCategoryMapper(db, cursor)
			category_Name_ID_Map = ucMapper.mapCategoryNameToID()
			currUserCategory = category_Name_ID_Map[currUser.getCategory()]
			
			#print "Content-type:text/html"
			#print
			allowedSections = uHandler.getAllowedSections(currUserCategory)
			#print `allowedSections`
			
			for name in currentSectionNames:
				
				if name in allowedSections:
					
					# added Jan. 7/09
					if name in menuTypes:
						#print "Content-type:text/html"
						#print
						#print name
						
						content += "<DIV style=\"border-top:3px double #FFF8DC; border-right:6px double #FFF8DC; border-bottom:3px double #FFF8DC; border-left:6px double #FFF8DC; margin-top:2px; width:162px; padding-top:5px; padding-bottom:0;\">"
						
						content += "<DIV style=\"background-image:url('../pictures/small_bg.png'); width:166px; height:30px;\">"
						
						content += "<select style=\"cursor:pointer; width:150px; background:#FFF8DC; font-weight:bold; color:#555; font-size:9pt; margin-top:3px; margin-left:2px;  font-family:Helvetica; border:0;\" onChange=\"openPage(this.options[this.options.selectedIndex]);\">"
						
						content += "<option selected style=\"cursor:pointer; font-weight:bold; color:#555; font-size:9pt; border:0; font-family:Helvetica;\" value=\"\">&nbsp;" + name + "</option>"
						
						for st_val in submenu_types[name]:
							numDisallowed = 0
							
							# Jan. 13, 2010: Don't print category heading if user has no access to any of its subitems
							for s_ord in submenu_order[st_val]:
								linkName = submenu_order[st_val][s_ord]
								linkURL = submenu_links[st_val][linkName]
								
								if not menuitems[st_val][linkName] in allowedSections:
									numDisallowed += 1
							
							if numDisallowed == len(submenu_links[st_val]):
								continue
							
							#print st_val.upper()
							content += "<option style=\"cursor:pointer; font-weight:bold; color:#555; background:#EFEFEF; font-size:9pt; border:0; font-family:Helvetica;\" onclick\"\">&nbsp;" + st_val.upper() + "</option>"
						
							# Now: since Python dictionaries are not ordered, arrays with > 2 items (e.g. Users - has more than 'add' and 'search') would appear scrambled.  Use an 'order' array instead
							for s_ord in submenu_order[st_val]:
								
								linkName = submenu_order[st_val][s_ord]
								linkURL = submenu_links[st_val][linkName]
								
								#print st_val
								#print linkName
								
								if menuitems[st_val][linkName] in allowedSections:

									content += "<option style=\"padding-left:15px; font-weight:bold; color:#555; font-size:8pt; border:0; font-family:Helvetica; cursor:pointer;\" value=\"" + linkURL + "\">&nbsp;&nbsp;&nbsp;" + linkName + "</option>"
							
						content += "</SELECT>"
						
						content += "</DIV>"
						
						# Quick links
						if quickLinks.has_key(name):
							content += "<div id=\"quick_links_" + name + "\" style=\"font-family:Helvetica; width:166px; padding-bottom:0; margin-top:0; padding-top:0; padding-left:2px;\">"
							
							content += "<UL style=\"padding-bottom:2px; padding-top:2px; padding-left:10px; position:relative;\">"
							
							for qlName in quickLinks[name]:
							
								if qlName in allowedSections:
								
									content += "<LI style=\"list-style:none;\"><img  src=\"../pictures/silvermenubullet.png\" width=\"7\" height=\"6\" style=\"padding-bottom:2px;\">&nbsp;<a style=\"font-weight:bold; font-size:8pt; font-family:Helvetica; text-decoration:none; color:#555; margin-left:2px;\" href=\"../" + pageLinkMap[qlName] + "\">" + qlName + "</a></LI>"
							
							content += "</UL>"
							
							content += "</DIV>"

						content += "</DIV>"
					else:
						if name == "Home":
							content += "<DIV style=\"background:url('../pictures/small_bg.png') repeat-y; padding-top:7px; margin-top:0; width:162px; border-top:6px double #FFF8DC; border-left:6px double #FFF8DC; border-right:6px double #FFF8DC; padding-bottom:8px;\">"

						else:
							content += "<DIV style=\"background:url('../pictures/small_bg.png') repeat-y; padding-top:7px; margin-top:2px; width:162px; border-left:6px double #FFF8DC; border-right:6px double #FFF8DC; padding-bottom:8px;\">"

						content += "<img src=\"../pictures/silvermenubullet.png\" style=\"width:11px; height:9px; margin-left:5px;\">"

						content += "<a style=\"font-weight:bold; color:#555; font-size:9pt; padding-left:3px; text-decoration:none;\" href=\"" + currentSectionLinks[name] + "\">" + name + "</a>"
						
						content += "</DIV>"
		else:
			# WRITE THIS FUNCTION!!!!!!!!!!
			#content += self.printGeneralMenu(publicSections)
			print "Content-type:text/html"
			print
			print "Unknown user"
		
		content += '''
					</UL>
				
					<!-- moved form down here on Aug. 20, 2010 -->
					<form name="curr_user_form" style="display:none" method="post" action="user_request_handler.py">"
					'''

		content += "<INPUT type=\"hidden\" ID=\"curr_username_hidden\" NAME=\"curr_username\" VALUE=\"" + currUser.getFullName() + "\">"
		
		content += "<INPUT TYPE=\"hidden\" id=\"curr_user_hidden\" name=\"view_user\" VALUE=\"" + `currUser.getUserID()` + "\">"
		
		content += '''
					</FORM>
				
					<div class="login">
					'''

		content += self.printLoginBlock()
		content += '''
					</div>
				</div>
			</div>
			'''
			
		return content


	def printSubmenuHeader(self, submenu_type):
		
		dbConn = DatabaseConn()
		hostname = dbConn.getHostname()		# to define form action URL
		
		db = dbConn.databaseConnect()
		cursor = db.cursor()

		uHandler = UserHandler(db, cursor)
		
		current_selection_names = []		# plain list of section names
		current_selection_links = {}		# dictionary, where section names are keys and their URLs are values
		
		if submenu_type == "Location":

			location_submenu_names = []
			location_submenu_links = {}
			
			location_submenu_names.append("Add container types")
			location_submenu_links["Add container types"] = "../Location.php?View=6&Sub=3"

			location_submenu_names.append("Add container sizes")
			location_submenu_links["Add container sizes"] = "../Location.php?View=6&Sub=1"

			location_submenu_names.append("Add containers")
			location_submenu_links["Add containers"] = "../Location.php?View=6&Sub=3"

			location_submenu_names.append("Search containers")
			location_submenu_links["Search containers"] = "../Location.php?View=2"

			current_selection_names = location_submenu_names
			current_selection_links = location_submenu_links

		elif submenu_type == "Reagent":

			reagent_submenu_names = []
			reagent_submenu_links = {}

			reagent_submenu_names.append("Add reagents")
			reagent_submenu_links["Add reagents"] = "../Reagent.php?View=2"

			reagent_submenu_names.append("Search reagents")
			reagent_submenu_links["Search reagents"] = "../search.php?View=1"

			# June 3/09
			reagent_submenu_names.append("Add reagent types")
			reagent_submenu_links["Add reagent types"] = "../Reagent.php?View=3"
			
			reagent_submenu_names.append("Search reagent types")
			reagent_submenu_links["Search reagent types"] = "../Reagent.php?View=5"
			
			current_selection_names = reagent_submenu_names
			current_selection_links = reagent_submenu_links

		elif submenu_type == "Chemical":
			
			chemical_submenu_names = []
			chemical_submenu_links = {}
			
			chemical_submenu_names.append("Add Chemicals")
			chemical_submenu_links["Add Chemicals"] = "../Chemical.php?View=2"
			
			chemical_submenu_names.append("Search Chemicals")
			chemical_submenu_links["Search Chemicals"] = "../Chemical.php?View=1"
			
			current_selection_names = chemical_submenu_names
			current_selection_links = chemical_submenu_links
			
		elif submenu_type == "Prediction":
			
			prediction_submenu_names = []
			prediction_submenu_links = {}
			
			prediction_submenu_names.append("Search predictions")
			prediction_submenu_links["Search predictions"] = "../Prediction.php?View=1"

			current_selection_names = prediction_submenu_names
			current_selection_links = prediction_submenu_links
	
		elif submenu_type == "Project":	

			project_submenu_names = []
			project_submenu_links = {}
			
			project_submenu_names.append("Add projects")
			project_submenu_links["Add projects"] = "../Project.php?View=1"

			project_submenu_names.append("Search projects")
			project_submenu_links["Search projects"] = "../Project.php?View=2"

			current_selection_names = project_submenu_names
			current_selection_links = project_submenu_links

		elif submenu_type == "User":

			user_submenu_names = []
			user_submenu_links = {}
			
			user_submenu_names.append("Add users")
			user_submenu_links["Add users"] = "../User.php?View=1"

			user_submenu_names.append("Search users")
			user_submenu_links["Search users"] = "../User.php?View=2"

			user_submenu_names.append("Change your password")
			user_submenu_links["Change your password"] = "../User.php?View=6"

			user_submenu_names.append("Personal page")
			user_submenu_links["Personal page"] = "User.php?View=7"
			
			user_submenu_names.append("View your orders")
			user_submenu_links["View your orders"] = "../User.php?View=8"
			
			current_selection_names = user_submenu_names
			current_selection_links = user_submenu_links

		elif submenu_type == "Lab":

			lab_submenu_names = []
			lab_submenu_links = {}
			
			lab_submenu_names.append("Add laboratories")
			lab_submenu_links["Add laboratories"] = "../User.php?View=3"

			lab_submenu_names.append("Search laboratories")
			lab_submenu_links["Search laboratories"] = "../User.php?View=4"

			current_selection_names = lab_submenu_names
			current_selection_links = lab_submenu_links


		# There can be permission differentiations within a menu section as well (e.g. Projects - only Creators can create, buit Writers can view)
		currUser = Session.getUser()

		ucMapper = UserCategoryMapper(db, cursor)
		category_Name_ID_Map = ucMapper.mapCategoryNameToID()

		currUserCategory = category_Name_ID_Map[currUser.getCategory()]
		allowedSections = uHandler.getAllowedSections(currUserCategory)

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `allowedSections`

		content = ""

		for name in current_selection_names:
		
			if name in allowedSections:
				
				if name == 'Personal page':
					content += "<LI class=\"submenu\">"
					
					content += "<IMG SRC=\"../pictures/star_bullet.gif\" WIDTH=\"10\" HEIGHT=\"10\" BORDER=\"0\" ALT=\"plus\" class=\"menu-leaf\">"

					content += "<span class=\"linkShow\" style=\"font-size:9pt\" onClick=\"redirectToCurrentUserDetailedView(" + `currUser.getUserID()` +  ");\">" + name + "</span>"

					content += "</LI>"

					content += "<form name=\"curr_user_form\" style=\"display:none\" method=\"post\" action=\"user_request_handler.py\">"
					
					content += "<INPUT type=\"hidden\" ID=\"curr_username_hidden\" NAME=\"curr_username\" VALUE=\"" + currUser.getFullName() + "\">"
					
					content += "<INPUT type=\"hidden\" id=\"curr_user_hidden\" name=\"view_user\">"
					content += "</FORM>"
				else:
					content += "<LI class=\"submenu\">"
	
					content += "<IMG SRC=\"../pictures/star_bullet.gif\" WIDTH=\"10\" HEIGHT=\"10\" BORDER=\"0\" ALT=\"plus\" class=\"menu-leaf\">"
	
					content += "<a class=\"submenu\" href=\"" + current_selection_links[name] + "\">" + name + "</a>"
					content += "</LI>"
				
		return content		


	# Output login prompt or welcome block
	def printLoginBlock(self):
		content = ""
		
		currUser = Session.getUser()
		
		if currUser:
			uDescr = currUser.getDescription()
			
			content += "<FORM name=\"login_form\" METHOD=POST ACTION=\"../index.php\" style=\"float:left; white-space:nowrap;\">"
			content += "Welcome <span style=\"color:#FF0000\">" + uDescr + "</span>"
			content += "<P><INPUT TYPE=SUBMIT NAME=\"logoutsubmit\" VALUE=\"Logout\" style=\"font-size:12px; elevation:above\"></P></FORM>"

		else:
			content += "<FORM name=\"logout_form\" METHOD=POST ACTION="">"
			
			content += '''
					<P class="login">To view additional sections of the website, please log in:</P>

					Username:<BR/> 
					<INPUT type="text" value="" name="loginusername_field" size="13"><BR/>

					Password:<BR/> 
					<INPUT type="password" value="" name="loginpassword_field" size="13">

					<P>Automatic Login <INPUT type="checkbox" value="" name="persistentlogin_field"></P>

					<INPUT TYPE="submit" NAME="loginsubmit" VALUE="Login" style="font-size:12px; elevation:above">		
			</FORM>
			'''
			
		return content
		
