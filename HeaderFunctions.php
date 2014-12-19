<?php
/**
 * Output page for main header, footer and side menu
 *
 * @author John Paul Lee <ninja_gara@hotmail.com>
 * @version 2005
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * @copyright	Mount Sinai Hospital, Toronto, Canada
 *
*/
// Function: outputMainHeader()
// Defn: Will output the main header that should go on all pages
function outputMainHeader()
{
	global $Const_Table_Width;
	global $hostname;

	?>
	<!--<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">-->
	<HTML>
		<HEAD>
			<link href="styles/SearchStyle.css" rel="stylesheet" type="text/css">
			<link href="styles/Header_styles.css" rel="stylesheet" type="text/css">
			
			<!-- August 14/07, Marina: For MS-Map - copied from Nexus -->
			<LINK REL="stylesheet" HREF="styles/generic.css" type="text/css"/>
			<!-- <script src="scripts/generic.js" language="javascript" type="text/javascript"></script>-->

			<script type="text/javascript" src="scripts/menu.js"></script>

			<!-- March 29, 2010: Incorporate overlib for sticky popups and tooltips -->
			<script type='text/javascript' src='overlib/overlib.js'></script>
			<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000"></div>
		</HEAD>

<!-- removed Oct. 3/07 - doesn't work on Mac OS X	<BODY onLoad="initAll()"> -->

		<BODY style="width:98%; padding-right:10px;">

			<!-- this is for infopops -->
			<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>

			<table id="header_table" cellpadding="0" cellspacing="0" style="background:#000000; width:100%;">
				<tr><td style="padding:0;"><img src="pictures/hdr.png"></td><td><img src="pictures/tux.PNG" style="margin-left:2%;"></td></tr>
			</table>

			<?php
				// moved here Aug. 6/08
				outputNavBar();
			?>
			<Div class="main">
				<DIV class="main_content" style="padding:5px;" ID="mainDiv">
				<?php
}

function outputMainFooter()
{
	global $cgi_path;		// Nov. 12/07

// 			echo "</DIV>";	// moved here July 25/08 - close 'main'
			?>
				</DIV>	<!--close 'main_content'-->
			</DIV>	<!--close 'main'-->

			<DIV class="footer_container">
				<DIV class="footer">
					Copyright &#169;Mount Sinai Hospital, Toronto, Canada.
<!--  <a class="footer" href="copyright.php">More Details</a> -->
				</DIV>
			</DIV>

			<!-- Added Oct. 3/07, replacing onLoad initAll -->
			<script type="text/javascript">initAll(); getEnzymes('<?php echo $cgi_path; ?>');</script>
		</BODY>
	</HTML>
	<?php
}

function outputNavBar()
{
	global $cgi_path;
// echo $cgi_path;
	global $conn;		// Aug. 13, 2010
// echo "ok";
	$colspan_tmp = 0;
	
	// Array of section names
	$currentSectionNames = array();

	// Array of links to names
	$currentSectionLinks = array();
	
	// Classify each header as to what OF section it belongs
	$menuTypes = array();

	// Jan. 12, 2010: change menu layout
	$submenu_links = array();
	$submenu_types = array();
	$menuitems = array();	// to check permissions

	// Differentiate between 'public' and 'private' pages
	$publicSectionNames = array();
	$publicSectionLinks = array();

	$quickLinks = array();	// Aug. 13/10

	$quickLinks["Reagent Tracker"][] = "Add reagents";
	$quickLinks["Reagent Tracker"][] = "Search reagents";

	$quickLinks["Location Tracker"][] = "Add containers";
	$quickLinks["Location Tracker"][] = "Search containers";

	$quickLinks["Project Management"][] = "Add projects";
	$quickLinks["Project Management"][] = "Search projects";

	$quickLinks["User Management"][] = "Change your password";
	$quickLinks["User Management"][] = "View your orders";

	// make a db map for quick links
	$linksMap = array();

	$links_rs = mysql_query("SELECT section, baseURL FROM SecuredPages_tbl WHERE status='ACTIVE'", $conn) or die("Cannot select menu links " . mysql_error());

	while ($links_ar = mysql_fetch_array($links_rs, MYSQL_ASSOC))
	{
		$section = $links_ar["section"];
		$baseURL = $links_ar["baseURL"];

		$linksMap[$section] = $baseURL;
	}

	$currentSectionNames[] = "Home";
	$currentSectionLinks["Home"] = "index.php";
	$publicSections["Home"] = "index.php";
	
	// Reagent Tracker
	$currentSectionNames[] = "Reagent Tracker";
	$currentSectionLinks["Reagent Tracker"] = "Reagent.php?View=1";
	$menuTypes["Reagent Tracker"] = "Reagent";

	$publicSections["Reagent Tracker"] = "Reagent.php?View=1";

	// Jan. 12, 2010
	$submenu_types["Reagent Tracker"][] = "Reagents";
	$submenu_types["Reagent Tracker"][] = "Reagent Types";

	$submenu_links["Reagents"]["Add"] = "Reagent.php?View=2";
	$submenu_links["Reagents"]["Search"] = "search.php?View=1";
	$submenu_links["Reagents"]["Statistics"] = "Reagent.php?View=4";

	$menuitems["Reagents"]["Add"] = "Add reagents";		// SecuredPages_tbl.section column value
	$menuitems["Reagents"]["Search"] = "Search reagents";	// SecuredPages_tbl.section column value
	$menuitems["Reagents"]["Statistics"] = "Statistics";	// SecuredPages_tbl.section column value

	$submenu_links["Reagent Types"]["Add"] = "Reagent.php?View=3";
	$submenu_links["Reagent Types"]["Search"] = "Reagent.php?View=5";

	$menuitems["Reagent Types"]["Add"] = "Add reagent types";	// May 18, 2010
	$menuitems["Reagent Types"]["Search"] = "Search reagent types";

	// Location Tracker
	$currentSectionNames[] = "Location Tracker";
	$currentSectionLinks["Location Tracker"] = "Location.php?View=1";
	$menuTypes["Location Tracker"] = "Location";

	$publicSections["Location Tracker"] = "Location.php?View=1";

	// Jan. 12, 2010
	$submenu_types["Location Tracker"][] = "Containers";
	$submenu_types["Location Tracker"][] = "Container Sizes";
	$submenu_types["Location Tracker"][] = "Container Types";

	$submenu_links["Container Types"]["Add"] = "Location.php?View=6&Sub=2";
	$submenu_links["Container Types"]["Search"] = "Location.php?View=6&Sub=4";

	$menuitems["Container Types"]["Add"] = "Add container types";
	$menuitems["Container Types"]["Search"] = "Search container types";

	$submenu_links["Container Sizes"]["Add"] = "Location.php?View=6&Sub=1";
	$submenu_links["Container Sizes"]["Search"] = "Location.php?View=6&Sub=5";

	$menuitems["Container Sizes"]["Add"] = "Add container sizes";
	//$menuitems["Container Sizes"]["Search"] = "Search container sizes";

	$submenu_links["Containers"]["Add"] = "Location.php?View=6&Sub=3";
	$submenu_links["Containers"]["Search"] = "Location.php?View=2";

	$menuitems["Containers"]["Add"] = "Add containers";
	$menuitems["Containers"]["Search"] = "Search containers";

	// Projects
	$currentSectionNames[] = "Project Management";
	$currentSectionLinks["Project Management"] = "Project.php?View=1";
	$menuTypes["Project Management"] = "Project";
	$submenu_types["Project Management"][] = "Projects";

	$submenu_links["Projects"]["Add"] = "Project.php?View=1";
	$submenu_links["Projects"]["Search"] = "Project.php?View=2";

	$menuitems["Projects"]["Add"] = "Add Projects";
	$menuitems["Projects"]["Search"] = "Search Projects";

	// Users and Labs
	$currentSectionNames[] = "User Management";
	$currentSectionLinks["User Management"] = "User.php";
	$menuTypes["User Management"] = "User";

	$submenu_types["User Management"][] = "Users";

	$submenu_links["Users"]["Add"] = "User.php?View=1";
	$submenu_links["Users"]["Search"] = "User.php?View=2";

	$submenu_links["Users"]["Change your password"] = "User.php?View=6";
	$submenu_links["Users"]["Personal page"] = "User.php?View=7";
	$submenu_links["Users"]["View your orders"] = "User.php?View=8";

	$menuitems["Users"]["Add"] = "Add Users";
	$menuitems["Users"]["Search"] = "Search Users";
	$menuitems["Users"]["Change your password"] = "Change your password";
	$menuitems["Users"]["Personal page"] = "Personal page";
	$menuitems["Users"]["View your orders"] = "View your orders";

	$currentSectionNames[] = "Lab Management";
	$currentSectionLinks["Lab Management"] = "User.php";
	$menuTypes["Lab Management"] = "Lab";
	$submenu_types["Lab Management"][] = "Laboratories";

	$submenu_links["Laboratories"]["Add"] = "User.php?View=3";
	$submenu_links["Laboratories"]["Search"] = "User.php?View=4";

	$menuitems["Laboratories"]["Add"] = "Add laboratories";
	$menuitems["Laboratories"]["Search"] = "Search laboratories";

	// July 28/08: Chemical Tracker
	$currentSectionNames[] = "Chemical Tracker";
	$currentSectionLinks["Chemical Tracker"] = "Chemical.php?View=1";
	$menuTypes["Chemical Tracker"] = "Chemical";
	
	// Jan. 12, 2010
	$submenu_types["Chemical Tracker"][] = "Chemicals";
	
	$submenu_links["Chemicals"]["Add"] = "Chemical.php?View=2";
	$submenu_links["Chemicals"]["Search"] = "Chemical.php?View=1";

	$menuitems["Chemicals"]["Add"] = "Add Chemicals";
	$menuitems["Chemicals"]["Search"] = "Search Chemicals";

	$currentSectionNames[] = "Documentation";
	$currentSectionLinks["Documentation"] = "docs.php";
	$publicSections["Documentation"] = "docs.php";

 	$currentSectionNames[] = "Terms and Conditions";
 	$currentSectionLinks["Terms and Conditions"] = "copyright.php";
	$publicSections["Terms and Conditions"] = "copyright.php";

	$currentSectionNames[] = "Help and Support";
 	$currentSectionLinks["Help and Support"] = "bugreport.php";
	$publicSections["Help and Support"] = "bugreport.php";
	
 	$currentSectionNames[] = "Contact Us";
 	$currentSectionLinks["Contact Us"] = "contacts.php";
	$publicSections["Contact Us"] = "contacts.php";

	$counter = 0;

	?>
	<div class="sidemenu" ID="mainMenu">
		<div class="menu-content">
<!-- 			<ul class="menulist"> -->
			<ul id="nav" class="menu">
			<?php
				// June 4/07, Marina: Hide restricted pages, according to user roles: (sort of 'private' and 'public' pages)
				$loginBlock = new MemberLogin_Class();  
				$loginBlock->loginCheck($_POST);

				// Output the menu link IFF the user is authorized to access that page
				if (isset($_SESSION["userinfo"]))
				{
					foreach ($currentSectionNames as $i => $name)
					{
						echo "<LI>";
						
						if (($loginBlock->verifySections($name, $_SESSION["userinfo"]->getCategory())))
						{
							if ($menuTypes[$name])
							{
								$counter = 0;

								echo "<DIV style=\"border-top:3px double #FFF8DC; border-right:3px double #FFF8DC; border-bottom:3px double #FFF8DC; border-left:6px double #FFF8DC; margin-top:2px; width:166px; padding-top:5px; padding-bottom:5px;\">";

									echo "<DIV style=\"background-image:url('pictures/small_bg.png'); width:166px; height:30px;\">";

										echo "<select style=\"cursor:pointer; width:150px; background:#FFF8DC; font-weight:bold; color:#555; font-size:9pt; font-family:Helvetica; position:absolute; top:10; left:10; border:0;\" onChange=\"if ((window.location.toString().indexOf('Reagent.php?View=2') >= 0) && (window.location.toString().indexOf('&rID') >= 0)){result = confirm('Are you sure you want to navigate away from this page?  Your input will be lost.'); if (result) cancelReagentCreation(); return result;} else{openPage(this.options[this.options.selectedIndex]);}\">";

											echo "<option selected style=\"cursor:pointer; font-weight:bold; color:#555; font-size:9pt; position:absolute; top:13; left:16; border:0; font-family:Helvetica;\" value=\"\">&nbsp;" . $name . "</option>";


											foreach ($submenu_types[$name] as $stKey => $stName)
											{
												$nAllowed = 0;
	
												foreach ($submenu_links[$stName] as $slKey => $sLink)
												{
													if (($loginBlock->verifySections($menuitems[$stName][$slKey], $_SESSION["userinfo"]->getCategory())))
													{
														$nAllowed++;
													}
												}
			
												if ($nAllowed > 0)
												{
													// $stName is e.g. 'Chemicals'
													echo "<option style=\"font-family:Helvetica; cursor:pointer; background-color:#FFF8DC; border:0; font-weight:bold; font-family:Helvetica; font-size:8pt; text-decoration:none; color:#555; margin-left:2px;\" value=\"\">&nbsp;&nbsp;" . strtoupper($stName) . "</option>";
		
													foreach ($submenu_links[$stName] as $slKey => $sLink)
													{
														if (($loginBlock->verifySections($menuitems[$stName][$slKey], $_SESSION["userinfo"]->getCategory())))
														{
															echo "<option style=\"font-weight:bold; color:#555; background:#EFEFEF; font-size:8pt; border:0; outline:none; font-family:Helvetica; cursor:pointer;\" value=\"" . $sLink . "\">&nbsp;&nbsp;&nbsp;&nbsp;" . $slKey . "</option>";
														}
													}
												}
											}

										echo "</select>";
									echo "</div>";

									if (in_array($name, array_keys($quickLinks)))
									{
										// Quick links - also by permission
										echo "<div id=\"quick_links_" . $name . "\" style=\"font-family:Helvetica; width:166px; padding-bottom:0; margin-top:0; padding-top:2px; padding-left:7px;\">";
	
											echo "<UL style=\"width:90%; font-family:Helvetica; display:inline;\">";
	
												foreach ($quickLinks[$name] as $qlKey => $qlName)
												{
													if (($loginBlock->verifySections($qlName, $_SESSION["userinfo"]->getCategory())))
													{
														echo "<LI><img  src=\"pictures/silvermenubullet.png\" width=\"7\" height=\"6\" style=\"padding-bottom:2px;\">&nbsp;<a style=\"font-weight:bold; font-size:8pt; font-family:Helvetica; text-decoration:none; color:#555; margin-left:2px;\" onclick=\"if ((window.location.toString().indexOf('Reagent.php?View=2') >= 0) && (window.location.toString().indexOf('&rID') >= 0)){result = confirm('Are you sure you want to navigate away from this page?  Your input will be lost.'); if (result) cancelReagentCreation(); return result;}\" href=\"" . $linksMap[$qlName] . "\">" . $qlName . "</a></LI>";
													}
												}
											echo "</UL>";
										echo "</DIV>";
									}

								echo "</div>";
							}
							else
							{
								if (strcasecmp($name, "Home") == 0)
									echo "<DIV style=\"background:url('pictures/small_bg.png') repeat-y; border-top: 2px solid #FFF8DC; border-left:6px double #FFF8DC; border-right:6px double #FFF8DC; padding-top:7px; margin-top:2px; width:166px; padding-bottom:8px;\">";
								else
									echo "<DIV style=\"background:url('pictures/small_bg.png') repeat-y; border-left:6px double #FFF8DC; border-right:6px double #FFF8DC; padding-top:7px; margin-top:2px; width:166px; padding-bottom:8px;\">";

									// OK - bullet image - KEEP
									echo "<img src=\"pictures/silvermenubullet.png\" style=\"width:11px; height:9px; margin-left:5px;\">";

									// actual inscription (don't have to make it h/l actually)
									echo "<a onclick=\"if ((window.location.toString().indexOf('Reagent.php?View=2') >= 0) && (window.location.toString().indexOf('&rID') >= 0)){result = confirm('Are you sure you want to navigate away from this page?  Your input will be lost.'); if (result) cancelReagentCreation(); return result;}\" style=\"font-weight:bold; color:#555; font-size:9pt; font-family:Helvetica; padding-left:3px; text-decoration:none;\" href=\"" . $currentSectionLinks[$name] . "\">" . $name . "</a>";

								echo "</DIV>";
							}
						}
					}

					echo "</LI>";

					?><form name="curr_user_form" style="display:none" method="post" action="<?php echo $cgi_path . "user_request_handler.py"; ?>">
						<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $_SESSION["userinfo"]->getDescription(); ?>">
						<INPUT type="hidden" id="curr_user_hidden" name="view_user" VALUE="<?php echo $_SESSION["userinfo"]->getUserID(); ?>">
					</FORM><?php

				}
				else
				{
					printGeneralMenu($publicSections);
				}
			?>
			</UL>

			<div class="login">
			<?php
				// print login block on the menu side
				$loginBlock = new MemberLogin_Class();
				$loginBlock->loginCheck($_POST);
				$loginBlock->printLoginInfo("?View=1");
			?>
			</div>
		</div>
	</div>

	<!-- Marina: March 4/08: Create a resizeable border -->
<!-- 	<div id="mainBorder" class="resizeableRight" onMouseDown="resize();"></div> -->
	<?php
}

function printGeneralMenu($publicSections)
{
	// Output general info pages - homepage, docs, copyright, contact us
	foreach ($publicSections as $name => $url)
	{
		if (strcasecmp($name, "Home") == 0)
			echo "<DIV style=\"background:url('pictures/small_bg.png') repeat-y; border-top: 2px solid #FFF8DC; border-left:6px double #FFF8DC; padding-top:7px; margin-top:2px; width:166px; padding-bottom:8px;\">";
		else
			echo "<DIV style=\"background:url('pictures/small_bg.png') repeat-y; border-left:6px double #FFF8DC; padding-top:7px; margin-top:2px; width:160px; padding-bottom:8px;\">";

			// OK - bullet image - KEEP
			echo "<img src=\"pictures/silvermenubullet.png\" style=\"width:11px; height:9px; margin-left:5px;\">";

			// actual inscription (don't have to make it h/l actually)
			echo "<a onclick=\"if ((window.location.toString().indexOf('Reagent.php?View=2') >= 0) && (window.location.toString().indexOf('&rID') >= 0)){result = confirm('Are you sure you want to navigate away from this page?  Your input will be lost.'); if (result) cancelReagentCreation(); return result;}\" style=\"font-weight:bold;  color:#555; font-size:9pt; padding-left:3px; text-decoration:none;\" href=\"" . $url . "\">" . $name . "</a>";

		echo "</DIV>";
	}
}


// Signature modified July 16/07 by Marina - Added $loginBlock parameter
function outputSubmenu_header($submenu_type, $loginBlock)
{
	global $cgi_path;

	$current_selection_names_ar = array();
	$current_selection_links_ar = array();
	
	switch ($submenu_type)
	{
		case "Location":
		
			$location_submenu_names_ar = array();
			$location_submenu_links_ar = array();

			$location_submenu_names_ar[] = "Add container types";
			$location_submenu_links_ar[] = "Location.php?View=6&Sub=2";

			// Jan. 8, 2010
			$location_submenu_names_ar[] = "Search container types";
			$location_submenu_links_ar[] = "Location.php?View=6&Sub=4";

			$location_submenu_names_ar[] = "Add container sizes";
			$location_submenu_links_ar[] = "Location.php?View=6&Sub=1";

			// Jan. 8, 2010
			//$location_submenu_names_ar[] = "Search container sizes";
			//$location_submenu_links_ar[] = "Location.php?View=6&Sub=5";

			$location_submenu_names_ar[] = "Add containers";
			$location_submenu_links_ar[] = "Location.php?View=6&Sub=3";

			$location_submenu_names_ar[] = "Search containers";
			$location_submenu_links_ar[] = "Location.php?View=2";

			$current_selection_names_ar = $location_submenu_names_ar;
			$current_selection_links_ar = $location_submenu_links_ar;
			
		break;


		case "Reagent":
		
			$reagent_submenu_names_ar = array();
			$reagent_submenu_links_ar = array();
			
			$reagent_submenu_names_ar[] = "Add reagents";
			$reagent_submenu_links_ar[] = "Reagent.php?View=2";
			
			$reagent_submenu_names_ar[] = "Search reagents";
			$reagent_submenu_links_ar[] = "search.php?View=1";

			// June 3, 2009
			$reagent_submenu_names_ar[] = "Search reagent types";
			$reagent_submenu_links_ar[] = "Reagent.php?View=5";

			$current_selection_names_ar = $reagent_submenu_names_ar;
			$current_selection_links_ar = $reagent_submenu_links_ar;
			
		break;

		
		// July 28/08
		case "Chemical":
		
			$chemical_submenu_names_ar = array();
			$chemical_submenu_links_ar = array();
			
			$chemical_submenu_names_ar[] = "Add chemicals";
			$chemical_submenu_links_ar[] = "Chemical.php?View=2";

			$chemical_submenu_names_ar[] = "Search chemicals";
			$chemical_submenu_links_ar[] = "Chemical.php?View=1";

			$current_selection_names_ar = $chemical_submenu_names_ar;
			$current_selection_links_ar = $chemical_submenu_links_ar;
			
		break;	
		
		// May 30/07, Marina: Project Management section
		case "Project":
			
			$project_submenu_names_ar[] = "Add projects";
			$project_submenu_links_ar[] = "Project.php?View=1";
			
			$project_submenu_names_ar[] = "Search projects";
			$project_submenu_links_ar[] = "Project.php?View=2";
			
//			$project_submenu_names_ar[] = "Delete Projects";
//			$project_submenu_links_ar[] = "Project.php?View=3";
			
			$current_selection_names_ar = $project_submenu_names_ar;
			$current_selection_links_ar = $project_submenu_links_ar;
		break;
		
		
		// July 11/07, Marina: User Management section
		case "User":
		
//			$user_submenu_names_ar[] = "Delete Users";
//			$user_submenu_links_ar[] = "User.php?View=2";

			$user_submenu_names_ar[] = "Add users";
			$user_submenu_links_ar[] = "User.php?View=1";
			
			$user_submenu_names_ar[] = "Search users";
			$user_submenu_links_ar[] = "User.php?View=2";
			
			$user_submenu_names_ar[] = "Change your password";
			$user_submenu_links_ar[] = "User.php?View=6";

			$user_submenu_names_ar[] = "Personal page";
			$user_submenu_links_ar[] = "User.php?View=7";

			// Jan. 23/08: Order clones
			$user_submenu_names_ar[] = "View your orders";
			$user_submenu_links_ar[] = "User.php?View=8";
		
			$current_selection_names_ar = $user_submenu_names_ar;
			$current_selection_links_ar = $user_submenu_links_ar;
		break;
		
		case "Lab":
			
			$lab_submenu_names_ar[] = "Add laboratories";
			$lab_submenu_links_ar[] = "User.php?View=3";
			
			$lab_submenu_names_ar[] = "Search laboratories";
			$lab_submenu_links_ar[] = "User.php?View=4";
			
			$current_selection_names_ar = $lab_submenu_names_ar;
			$current_selection_links_ar = $lab_submenu_links_ar;
		break;
	}
	
	foreach ($current_selection_names_ar as $i => $name)
	{
		// July 16/07, Marina: There can be permission differentiations within a menu section as well (e.g. Projects - only Creators can create, buit Writers can view)
		if ( ($loginBlock->verifySections($name, $_SESSION["userinfo"]->getCategory())) )
		{
			// Sept. 17/07: User page (quasi-hack to pass control to CGI)
			if (strcasecmp($name, "Personal page") == 0)
			{
				echo "<LI>";
// 				echo "<IMG SRC=\"pictures/star_bullet.gif\" WIDTH=\"10\" HEIGHT=\"10\" BORDER=\"0\" ALT=\"star\" class=\"menu-leaf\">";

				echo "<span class=\"linkShow\" style=\"font-size:9pt\" onClick=\"redirectToCurrentUserDetailedView(" . $_SESSION["userinfo"]->getUserID() .  ");\">" . $name . "</span>";

				echo "</LI>";
				?>
				<form name="curr_user_form" style="display:none" method="post" action="<?php echo $cgi_path . "user_request_handler.py"; ?>">
					<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $_SESSION["userinfo"]->getDescription(); ?>">
					<INPUT type="hidden" id="curr_user_hidden" name="view_user" VALUE="<?php echo $_SESSION["userinfo"]->getUserID(); ?>">
				</FORM>
				<?php
			}
			else
			{
				echo "<LI>";
	
// 				echo "<IMG SRC=\"pictures/star_bullet.gif\" WIDTH=\"10\" HEIGHT=\"10\" BORDER=\"0\" ALT=\"plus\" class=\"menu-leaf\">";
	
				echo "<a class=\"top_title\" class=\"submenu\" href=\"" . $current_selection_links_ar[$i] . "\">" . $name . "</a>";
				echo "</LI>";
			}
		}
	}
}

function getLocation_submenu_links()
{
}

function outputLoginBlock( $trailing, $loginBlock )
{
	if( isset( $_SESSION["userinfo"] ) ) 
		{ 
			if( $loginBlock->verifyPermissions( basename( $_SERVER["PHP_SELF"] ), $_SESSION["userinfo"]->getUserID()))
			{
				echo "basename: " .  basename( $_SERVER['PHP_SELF'] ) . "<BR>";
				echo "username: " .  $_SESSION['userinfo']->getUserID( ) . "<BR>";
				$loginBlock->printLoginInfo();
			}
			else
			{
				echo "basename: " .  basename( $_SERVER['PHP_SELF'] ) . "<BR>";
				echo "username: " .  $_SESSION['userinfo']->getUserID( ) . "<BR>";
				echo "FAILED!";
			}
		}
	else
	{

	?>
		<FORM METHOD="POST" ACTION="<?php echo $_SERVER['PHP_SELF'] . $trailing; ?>" style="float:right">
			Username: <INPUT type="text" value="" name="loginusername_field"> 
			Password: <INPUT type="password" value="" name="loginpassword_field">
			Automatic Login <INPUT type="checkbox" value="" name="persistentlogin_field"> 

			<INPUT TYPE="SUBMIT" NAME="loginsubmit" VALUE="Loginsubmit">
		</FORM>
	<?php
	}
}
?>
