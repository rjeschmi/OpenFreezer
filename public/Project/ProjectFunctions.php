<?php
/**
*
* PHP versions 4 and 5
*
* Copyright (c) 2005-2011 Mount Sinai Hospital, Toronto, Ontario
*
* LICENSE:
*
* OpenFreezer is free software; you can redistribute it
* and/or modify it under the terms of the GNU General
* Public License as published by the Free Software Foundation;
* either version 3 of the License, or (at your option) any
* later version.
*
* OpenFreezer is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
* General Public License for more details.
*
* You should have received a copy of the GNU General Public
* License along with OpenFreezer.  If not, see <http://www.gnu.org/licenses/>.
*
* @author     Marina Olhovsky <olhosvky@lunenfeld.ca>
* @version    3.1
* @package Project
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3*/

/**
* Include/require statements
*/
include_once "Classes/Member_Class.php";
include_once "Classes/Project_Class.php";
include_once "User/UserFunctions.php";

/**
 * Various project-related functions
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Project
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

// Jan. 7, 2010: Still, queries do not include public projects.  See where should add UNION and where not

/**
 * Get all projects the user is a member of
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * @param INT $userID Database ID of the user currently logged in
 * @return Array List of project IDs
*/
function getUserProjectIDs($userID)
{
	global $conn;
	$userProjectIDs = array();
	
	$uProject_set = mysql_query("SELECT packetID FROM ProjectMembers_tbl WHERE memberID='" . $userID . "' AND status='ACTIVE' UNION (SELECT packetID FROM Packets_tbl WHERE is_private='FALSE' AND status='ACTIVE')", $conn) or die("Cannot fetch user projects: " . mysql_error());
	
	while ($uProjects = mysql_fetch_array($uProject_set, MYSQL_ASSOC))
	{
		$userProjectIDs[] = $uProjects["packetID"];
	}
	
	mysql_free_result($uProject_set);
	unset($uProjects);

	// Alternatively, the user may be a project owner, in which case there would be no explicit entry in ProjectMembers_tbl
	$owner_set = mysql_query("SELECT packetID FROM Packets_tbl WHERE ownerID='" . $userID . "' AND status='ACTIVE' UNION (SELECT packetID FROM Packets_tbl WHERE is_private='FALSE' AND status='ACTIVE')");
	
	while ($owners = mysql_fetch_array($owner_set, MYSQL_ASSOC))
	{
		$userProjectIDs[] = $owners["packetID"];
	}
	
	mysql_free_result($owner_set);
	unset($owners);

	return $userProjectIDs;
}


/**
 * Same as getUserProjectIDs, but return a list of Project OBJECTS
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1 2007-08-15
 *
 * @param INT $userID Database ID of the user currently logged in
 * @return Array List of project OBJECTS
*/
function getUserProjects($userID)
{
	global $conn;
	$userProjects = array();
	
	$uProject_set = mysql_query("SELECT m.packetID, p.packetName, p.ownerID, p.packetDescription, p.is_private, u.lastname FROM Packets_tbl p, ProjectMembers_tbl m, Users_tbl u WHERE m.memberID='" . $userID . "' AND p.packetID=m.packetID AND p.ownerID=u.userID AND p.status='ACTIVE' AND m.status='ACTIVE'", $conn) or die("Cannot fetch user projects: " . mysql_error());
	
	while ($uProjects = mysql_fetch_array($uProject_set, MYSQL_ASSOC))
	{
		$packetID = $uProjects["packetID"];
		$packetName = $uProjects["packetName"];
		$packetOwnerID = $uProjects["ownerID"];
		$packetOwnerName = $uProjects["lastname"];
		$pDescr = $owners["packetDescription"];
		$is_private = $owners["is_private"];

		$tmpOwner = new Member_Class($packetOwnerID);
		$tmpOwner->setLastName($packetOwnerName);
		
		$isPrivate = (strcasecmp($is_private, "TRUE") == 0) ? true : false;

		$tmpProject = new Project($packetID, $packetName, $tmpOwner, $pDescr, $isPrivate);
		$userProjects[$packetID] = $tmpProject;
	}

	mysql_free_result($uProject_set);
	unset($uProjects);
	
	// Alternatively, the user may be a project owner, in which case there would be no explicit entry in ProjectMembers_tbl
	$owner_set = mysql_query("SELECT packetID, packetName, lastname, packetDescription, is_private FROM Packets_tbl p, Users_tbl u WHERE p.ownerID='" . $userID . "' AND p.ownerID=u.userID AND p.status='ACTIVE'", $conn) or die("Could not select project owners: " . mysql_error());
	
	while ($owners = mysql_fetch_array($owner_set, MYSQL_ASSOC))
	{
		$packetID = $owners["packetID"];
		$packetName = $owners["packetName"];
		$lastname = $owners["lastname"];
		$pDescr = $owners["packetDescription"];
		$is_private = $owners["is_private"];

		$tmpOwner = new Member_Class($userID);
		$tmpOwner->setLastName($lastname);
		
		$isPrivate = (strcasecmp($is_private, "TRUE") == 0) ? true : false;

		$tmpProject = new Project($packetID, $packetName, $tmpOwner, $pDescr, $isPrivate);
		$userProjects[$packetID] = $tmpProject;
	}
	
	mysql_free_result($owner_set);
	unset($owners);

	return $userProjects;
}

/**
 * Retrieve all projects for users with the given role (one of 'Reader' or 'Writer')
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * @param INT $userID Database ID of the user currently logged in
 * @param $role One of 'Reader' or 'Writer'
 * @return Array List of project IDs
*/
function getUserProjectsByRole($userID, $role)
{
	global $conn;
	$userProjects = array();

	// Jan. 7, 2010: include public projects
	if ($role == 'Reader')
		$uProject_set = mysql_query("SELECT packetID FROM ProjectMembers_tbl WHERE memberID='" . $userID . "' AND role='" . $role . "' AND status='ACTIVE' UNION (SELECT packetID FROM Packets_tbl WHERE is_private='FALSE' AND status='ACTIVE')", $conn) or die("Cannot fetch user projects: " . mysql_error());
	else
		$uProject_set = mysql_query("SELECT packetID FROM ProjectMembers_tbl WHERE memberID='" . $userID . "' AND role='" . $role . "' AND status='ACTIVE'", $conn) or die("Cannot fetch user projects: " . mysql_error());
	
	while ($uProjects = mysql_fetch_array($uProject_set, MYSQL_ASSOC))
	{
		$userProjects[] = $uProjects["packetID"];
	}
	
	// Alternatively, the user may be a project owner, in which case there would be no explicit entry in ProjectMembers_tbl
	if ($role == 'Reader')
		$owner_set = mysql_query("SELECT packetID FROM Packets_tbl WHERE ownerID='" . $userID . "' AND status='ACTIVE' UNION (SELECT packetID FROM Packets_tbl WHERE is_private='FALSE' AND status='ACTIVE')", $conn) or die("Cannot fetch project owner: " . mysql_error());
	else
		$owner_set = mysql_query("SELECT packetID FROM Packets_tbl WHERE ownerID='" . $userID . "' AND status='ACTIVE'", $conn) or die("Cannot fetch project owner: " . mysql_error());
	
	while ($owners = mysql_fetch_array($owner_set, MYSQL_ASSOC))
	{
		$userProjects[] = $owners["packetID"];
	}
	
	return array_unique($userProjects);
}

/**
 * Retrieve all the projects in OpenFreezer
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * @return Array List of project IDs
*/
function findAllProjects()
{
	global $conn;
	
	$allProjects = array();
	
	$project_set = mysql_query("SELECT packetID FROM Packets_tbl WHERE status='ACTIVE'", $conn) or die("Cannot fetch all projects: " . mysql_error());
	
	while ($projects = mysql_fetch_array($project_set, MYSQL_ASSOC))
	{
		$allProjects[] = $projects["packetID"];
	}

	$allProjects[] = 0;		// feb. 24/10

	return $allProjects;
}


/**
 * Retrieve all the **public** projects in OpenFreezer
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1 2010-08-07
 *
 * @return Array List of project OBJECTS (mimics Python implementation)
*/
function getPublicProjects()
{
	global $conn;
	
	$publicProjects = array();
	
	$public_rs = mysql_query("SELECT packetID, packetName, ownerID, lastname, packetDescription FROM Packets_tbl p, Users_tbl u WHERE is_private='FALSE' AND p.ownerID=u.userID AND p.status='ACTIVE'", $conn) or die("Cannot select public projects: " . mysql_error());
	
	while ($public_ar = mysql_fetch_array($public_rs, MYSQL_ASSOC))
	{
		$pID = $public_ar["packetID"];
		$pName = $public_ar["packetName"];
		$pDesc = $public_ar["packetDescription"];
		
		// owner
		$pOwnerID = $public_ar["ownerID"];
		$ownerName = $public_ar["lastname"];
		$pOwner = new Member_Class($pOwnerID);
		$pOwner->setLastName($ownerName);
		$newProject = new Project($pID, $pName, $pOwner, $pDesc, true);
		
		$publicProjects[] = $newProject;
	}
	
	return $publicProjects;
}


/**
 * Return a list of IDs of all projects user can view - i.e. all projects s/he is at least a reader on, plus all public projects
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * @param INT $userID ID
 * @return Array List of project IDs
*/
function getAllowedUserProjectIDs($userID)
{
	global $conn;

	$uCat = getUserCategory($userID);

	// Show all projects for Admin
	if (strcasecmp($uCat, $_SESSION["userCategoryNames"]["Admin"]) == 0)
	{
		return findAllProjects();
	}
	else
	{
		// For non-Admin users, allow access to projects owned by them, public projects, and/or projects they're members of
		$userProjects = array();

		$uProject_set = mysql_query("SELECT DISTINCT packetID FROM ProjectMembers_tbl WHERE memberID='" . $userID . "' AND status='ACTIVE' UNION SELECT DISTINCT packetID FROM Packets_tbl WHERE is_private='FALSE' AND status='ACTIVE'", $conn) or die("Cannot fetch user projects: " . mysql_error());
	
		while ($uProjects = mysql_fetch_array($uProject_set, MYSQL_ASSOC))
		{
			$pID = $uProjects["packetID"];
			$userProjects[] = $pID;
		}
	
		// Alternatively, the user may be a project owner, in which case there would be no explicit entry in ProjectMembers_tbl
		$owner_set = mysql_query("SELECT DISTINCT packetID FROM Packets_tbl WHERE ownerID='" . $userID . "' AND status='ACTIVE'") or die("Cannot fetch user projects: " . mysql_error());
	
		while ($owners = mysql_fetch_array($owner_set, MYSQL_ASSOC))
		{
			$pID = $owners["packetID"];
			$userProjects[] = $pID;
		}
		
		$userProjects = array_unique($userProjects);
	
		return $userProjects;
	}
}

/**
 * Retrieve from the database the project number of the given reagent, identified by $rid
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * @param INT $rid
 * @return INT
*/
function getReagentProjectID($rid)
{
	global $conn;
	$currReagentPacketID = 0;

	$rfunc_obj = new Reagent_Function_Class();

	// update March 1, 2010
	$packetPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["packet id"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

	$project_rs = mysql_query("SELECT p.propertyValue as packetID FROM ReagentPropList_tbl p WHERE p.propertyID='" . $packetPropID . "' AND p.reagentID='" . $rid . "' AND p.status='ACTIVE'", $conn) or die("Could not select reagent packet id: " . mysql_error());
	
	
	if ($project_ar = mysql_fetch_array($project_rs, MYSQL_ASSOC))
	{
		$currReagentPacketID = $project_ar["packetID"];
	}
	
	return $currReagentPacketID;		// will default to 0 if project not found
}


/**
 * Retrieve from the database the numeric ID that corresponds to the given category name
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * @param STRING
 * @return INT
*/
function findCategoryID($categoryName)
{
	global $conn;
	
	$categoryID = 0;
	
	$category_rs = mysql_query("SELECT categoryID FROM UserCategories_tbl WHERE category='" . $categoryName . "'", $conn) or die("Cannot select category ID: " . mysql_error());
	
	while ($category_ar = mysql_fetch_array($category_rs, MYSQL_ASSOC))
	{
		$categoryID = $category_ar["categoryID"];
	}
	
	return $categoryID;
}


/**
 * Print ALL available projects or projects owned by the user
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * @param INT $mult 1 or 0 - make the SELECT list MULTIPLE or not
 * @param STRING $actn
 * @param INT $restr Restrictions on viewing; if $restr == 0 output all projects; otherwise print only projects owned by the user
 * @param INT $sel Selected option number (project ID)
*/
function printPacketList($mult, $actn="", $restr=0, $sel=0)
{
	global $conn;

	?>
	<SELECT id="packetList" name="packets" <?php echo $actn;
		
		if ($mult > 0) 
		{
			echo " multiple>";
		}
		else
		{
			echo ">";
			echo "<OPTION value=\"0\"";
			
			if ($sel == 0) 
				echo " selected>Select Project</OPTION>";
			else
				echo ">Select Project</OPTION>";
		}

		if ($restr == 0)
		{
			$query = "SELECT p.packetID as packetID, p.packetName as packetName, u.lastname as owner FROM Packets_tbl p, Users_tbl u WHERE p.ownerID=u.userID AND p.status='ACTIVE' ORDER BY packetID";

			$find_name_rs = mysql_query($query, $conn) or die("Error fetching packet ID:" . mysql_error());

			while ($find_name_ar = mysql_fetch_array($find_name_rs, MYSQL_ASSOC))
			{
				$temp_packet = $find_name_ar["packetID"] . ": " . $find_name_ar["owner"] . ": " . $find_name_ar["packetName"];
				echo "<OPTION value=\"" . $find_name_ar["packetID"] . "\"";
			
				if ($sel == $find_name_ar["packetID"])
					echo " selected>" . $temp_packet . "</option>";
				else
					echo ">" . $temp_packet . "</option>";
			}
		}
		else
		{
			$currUserID = $_SESSION["userinfo"]->getUserID();
			$currUserCategory = $_SESSION["userinfo"]->getCategory();
			
			// Show ALL projects for Admins; and only projects owned by current user for users with lower access privileges
			if ($currUserCategory == $_SESSION["userCategoryNames"]['Admin'])
				$query = "SELECT p.packetID as packetID, p.packetName as packetName, u.lastname as owner FROM Packets_tbl p, Users_tbl u WHERE p.ownerID=u.userID AND p.status='ACTIVE'";
			else
				$query = "SELECT p.packetID as packetID, p.packetName as packetName, u.lastname as owner FROM Packets_tbl p, Users_tbl u WHERE p.ownerID='" . $currUserID . "' AND p.ownerID=u.userID AND p.status='ACTIVE'";
			
			$find_name_rs = mysql_query($query, $conn) or die("Error fetching packet ID:" . mysql_error());

			while ($find_name_ar = mysql_fetch_array($find_name_rs, MYSQL_ASSOC))
			{
				$temp_packet = $find_name_ar["packetID"] . ": " . $find_name_ar["owner"] . ": " . $find_name_ar["packetName"];

				echo "<OPTION value=\"" . $find_name_ar["packetID"] . "\">" . $temp_packet . "</option>";
			}
		}
		?>
	</SELECT>
	<?php
}

/**
 * Modified version of printPacketList, with a name that includes [] to indicate a list
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1 2007-08-12
 *
 * @param INT $mult 1 or 0 - make the SELECT list MULTIPLE or not
 * @param STRING $actn
 * @param INT $restr Restrictions on viewing; if $restr == 0 output all projects; otherwise print only projects owned by the user
*/
function printSearchPackets($mult, $actn="", $restr=0)
{
	global $conn;
	
	?>
	<SELECT id="packetList" name="packets[]" <?php echo $actn;
		
		if ($mult > 0) 
		{
			echo " multiple>";
		}
		else
		{
			echo ">";
		}

		// Default option 'All' - select if necessary
		echo "<OPTION value=\"0\"";

		if (isset($_POST["packets"]) && in_array(0, array_values($_POST["packets"])))
			echo "selected";

		echo ">All</OPTION>";

		if ($restr == 0)
		{
			$query = "SELECT p.packetID as packetID, p.packetName as packetName, u.lastname as owner FROM Packets_tbl p, Users_tbl u WHERE p.ownerID=u.userID AND p.status='ACTIVE' ORDER BY packetID";
			$find_name_rs = mysql_query($query, $conn) or die("Error fetching packet ID:" . mysql_error());

			while ($find_name_ar = mysql_fetch_array($find_name_rs, MYSQL_ASSOC))
			{
				$temp_packet = $find_name_ar["packetID"] . ": " . $find_name_ar["owner"] . ": " . $find_name_ar["packetName"];

				echo "<OPTION value=\"" . $find_name_ar["packetID"] . "\"";
				
				if (isset($_POST["packets"]) && in_array($find_name_ar["packetID"], array_values($_POST["packets"])))
						echo " selected>" . $temp_packet . "</option>";
				else
					echo ">" . $temp_packet . "</option>";
			}
		}
		else
		{
			$currUserID = $_SESSION["userinfo"]->getUserID();
			$currUserCategory = $_SESSION["userinfo"]->getCategory();

			$allProjects = array();

			// Show ALL projects for Admins; and only projects owner by current user for users with lower access privileges
			if ($currUserCategory == $_SESSION["userCategoryNames"]['Admin'])
			{
				$query = "SELECT p.packetID as packetID, p.packetName as packetName, u.lastname as owner FROM Packets_tbl p, Users_tbl u WHERE p.ownerID=u.userID AND p.status='ACTIVE' ORDER BY packetID";

				$find_name_rs = mysql_query($query, $conn) or die("Error fetching packet ID:" . mysql_error());

				while ($find_name_ar = mysql_fetch_array($find_name_rs, MYSQL_ASSOC))
				{
					$temp_packet = $find_name_ar["packetID"] . ": " . $find_name_ar["owner"] . ": " . $find_name_ar["packetName"];

					echo "<OPTION value=\"" . $find_name_ar["packetID"] . "\">" . $temp_packet . "</option>";
				}
			}
			else
			{
				// allow everyone to search for their own projects and public projects
				$userProjects = getUserProjects($currUserID);
				$publicProjects = getPublicProjects();

				foreach ($userProjects as $key => $project)
				{
					$pID = $project->getPacketID();
					$pName = $project->getName();
					$pOwner = $project->getOwner();
					$ownerName = $pOwner->getLastName();
					
					$tmpName = $pID . " : " . $ownerName . " : " . $pName;
					$allProjects[$pID] = $tmpName;
				}
				
				foreach ($publicProjects as $key => $project)
				{
					$pID = $project->getPacketID();
					$pName = $project->getName();
					$pOwner = $project->getOwner();
					$ownerName = $pOwner->getLastName();
					
					$tmpName = $pID . " : " . $ownerName . " : " . $pName;
					$allProjects[$pID] = $tmpName;
				}

				$allProjects = array_unique($allProjects);
			}
			
			ksort($allProjects);

			foreach ($allProjects as $pID => $tmpName)
			{
				echo "<OPTION VALUE=\"" . $pID . "\"";
				
				if (isset($_POST["packets"]) && in_array($pID, array_values($_POST["packets"])))
					echo "selected>" . $tmpName . "</option>";
				else
					echo ">" . $tmpName . "</option>";
			}
		}
		?>
	</SELECT>
	<?php
}

/**
 * Retrieve project information based on its ID
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1 (restored Aug. 18, 2010)
 *
 * @param INT $packetID
*/
function getPacketByID($packetID)
{
	global $conn;

	$packet_rs = mysql_query("SELECT * FROM Packets_tbl WHERE packetID='" . $packetID . "' AND status='ACTIVE'", $conn) or die("Cannot fetch packet by ID: " . mysql_error());
	
	while ($packet_ar = mysql_fetch_array($packet_rs, MYSQL_ASSOC))
	{
		$pName = $packet_ar["packetName"];
		$ownerID = $packet_ar["ownerID"];
		$tmpOwner = new Member_Class($ownerID);
		$pDescr = $packet_ar["packetDescription"];

		if ($packet_ar["is_private"] == 'TRUE')
			$public = false;
		else
			$public = true;

			$tmpPacket = new Project_Class($packetID, $pName, $tmpOwner, $pDescr, $public);
	}

	mysql_free_result($packet_rs);
	unset($packet_ar);

	return $tmpPacket;
}


/**
 * Determine if project identified by packetID is public
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * @param INT $packetID
*/
function isPublic($packetID)
{
	global $conn;

	// Octl 6, 2010: ignore status='ACTIVE' here, see if it poses a problem
	$public_rs = mysql_query("SELECT is_private FROM Packets_tbl WHERE packetID='" . $packetID . "'", $conn) or die("Error in project->isPublic: " . mysql_error());

	if ($public_ar = mysql_fetch_array($public_rs, MYSQL_ASSOC))
	{
		if ($public_ar["is_private"] == "TRUE")
			return false;
		else
			return true;
	}
}
?>
