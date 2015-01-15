<?php
/**
* PHP versions 4 and 5
*
* Copyright (c) 2005-2010 Pawson Laboratory, All Rights Reserved
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
* @author John Paul Lee @version 2005
*
* @author     Marina Olhovsky <olhosvky@lunenfeld.ca>
* @version    3.1
* @package Location
*
* @copyright  2005-2010 Pawson Laboratory
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
 * This class handles general location functions
 *
 * @author John Paul Lee @version 2005
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Location
 *
 * @copyright  2005-2010 Pawson Laboratory
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 *
*/
class Location_Funct_Class
{
	/**
	 * Default constructor
	*/
	function Location_Funct_Class()
	{}

	/**
	 * Fetch the internal database ID of a container SIZE from its name
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING $name E.g. '81-slot box'
	 * @return INT Internal database ID of the container size
	*/
	function convertContTypeName_to_ID($name)
	{
		global $conn;
		
		$typeID_rs = mysql_query("SELECT `contTypeID` FROM `ContainerTypeID_tbl` WHERE `containerName`='" . $name . "' AND `status`='ACTIVE'", $conn) or die( "Error in converting NAME to ID in SQL: " . mysql_error());
	
		if ($typeID_ar = mysql_fetch_array($typeID_rs, MYSQL_ASSOC))
		{
			return $typeID_ar["contTypeID"];
		}
		
		return -1;
	}
	
	/**
	 * Fetch the internal database ID of a container TYPE from its name
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING $name E.g. 'Glycerol Stocks'
	 * @return INT Internal database ID of the container type
	*/
	function convertContGroupName_to_ID($name)
	{
		global $conn;
		
		$groupID_rs = mysql_query("SELECT `contGroupID` FROM `ContainerGroup_tbl` WHERE `contGroupName`='" . $name . "' AND `status`='ACTIVE'", $conn) or die("ERROR READING SQL GROUP NAME: " . mysql_error());

		if ($groupID_ar = mysql_fetch_array($groupID_rs, MYSQL_ASSOC))
		{
			return $groupID_ar["contGroupID"];
		}
		
		return -1;
	}
	
	/**
	 * Fetch a container's name given its internal db ID
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2007-04-24
	 *
	 * @param INT $contID
	 * @return STRING
	*/
	function getContainerName($contID)
	{
		global $conn;
		
		$contID_rs = mysql_query("SELECT `name` FROM `Container_tbl` WHERE `containerID`='" . $contID . "'", $conn) or die("Could not select container name: " . mysql_error());
		
		if ($contID_ar = mysql_fetch_array($contID_rs, MYSQL_ASSOC))
		{
			return $contID_ar["name"];
		}
		
		return "";
	}
	
	/**
	 * Determine the group name (type) of a given container
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2007-05-22
	 *
	 * @param INT $contID
	 * @return STRING Container type (e.g. Vector, Glycerol Stock, Oligo, Insert, Cell Line)
	*/
	function getContainerGroupName($contID)
	{
		global $conn;
		
		$typeID_rs = mysql_query("SELECT g.`contGroupName` FROM `Container_tbl` c, `ContainerGroup_tbl` g WHERE c.`containerID`='" . $contID . "' AND c.`contGroupID`=g.`contGroupID` AND c.`status`='ACTIVE' AND g.`status`='ACTIVE'", $conn) or die( "Error retrieving container type: " . mysql_error());
		
		if ($typeID_ar = mysql_fetch_array($typeID_rs, MYSQL_ASSOC))
		{
			return $typeID_ar["contGroupName"];
		}
		
		return "";
	}
	
	/**
	 * Determine the lab ID of a given container
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT $contID
	 * @return INT
	*/
	function getContainerLabID($contID)
	{
		global $conn;
		
		$typeID_rs = mysql_query("SELECT c.labID FROM Container_tbl c WHERE c.containerID='" . $contID . "' AND c.status='ACTIVE'", $conn) or die( "Error retrieving container type: " . mysql_error());
		
		if ($typeID_ar = mysql_fetch_array($typeID_rs, MYSQL_ASSOC))
		{
			return $typeID_ar["labID"];
		}
		
		return "";
	
	}

	/**
	 * Update container properties (types of prep attributes)
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-06-05
	 *
	 * @param INT $contID
	 * @param INT $groupID
	 * @param STRING $isolate_tmp YES or NO - do preps in this container have isolates?
	*/
	function updateContainerProperties($contID, $groupID, $isolate_tmp)
	{
		global $conn;

		// First, CLEAR CURRENT PROPERTIES OF THE CONTAINER!!!!!!!!
		mysql_query("UPDATE `Prep_Req_tbl` SET `status`='DEP' WHERE `containerID`='" . $contID . "' AND `status`='ACTIVE'", $conn) or die("Cannot clear container properties: " . mysql_error());

		// Update isolate active state of the container
		mysql_query("UPDATE `Container_tbl` SET `isolate_active`='" .  $isolate_tmp . "' WHERE `containerID`='" . $contID . "' AND `status`='ACTIVE'", $conn) or die("Error in updating SQL statement: " . mysql_error());

		// Required properties for each container type: HARD-CODED, NEEDS FIXING!!!
		switch ($groupID)
		{
			case '1':	// Vector plates - Method ID, Concentration
	
				// Method ID
				$method_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='Method ID'") or die("Error fetching Method ID property");
	
				if ($method_ar = mysql_fetch_array($method_rs, MYSQL_ASSOC))
				{
					$method_id = $method_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $method_id . "', '" . $contID . "','REQ')");
	
	
				// Concentration
				$conc_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='Concentration'") or die("Error fetching Concentration property " . mysql_error());
	
				if ($conc_ar = mysql_fetch_array($conc_rs, MYSQL_ASSOC))
				{
					$conc_id = $conc_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $conc_id . "', '" . $contID . "','REQ')");
	
			break;
	
			case '2':	// Glycerol Stocks - Bacteria Strain (no concentration!)
	
				$bact_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='Bacteria Strain'") or die("Error fetching Concentration property " . mysql_error());
	
				if ($bact_ar = mysql_fetch_array($bact_rs, MYSQL_ASSOC))
				{
					$bact_id = $bact_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $bact_id . "', '" . $contID . "','REQ')");
	
			break;
	
			case '3':	// Oligo boxes/plates - Reagent Source, Concentration
	
				// Reagent Source
				$source_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='Reagent Source'") or die("Error fetching Concentration property " . mysql_error());
	
				if ($source_ar = mysql_fetch_array($source_rs, MYSQL_ASSOC))
				{
					$source_id = $source_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $source_id . "', '" . $contID . "','REQ')");
	
				// Concentration
				$conc_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='Concentration'") or die("Error fetching Concentration property " . mysql_error());
	
				if ($conc_ar = mysql_fetch_array($conc_rs, MYSQL_ASSOC))
				{
					$conc_id = $conc_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $conc_id . "', '" . $contID . "','REQ')");
	
			break;
	
			case '4':	//  Insert plates - Alternate ID, 5'/3' digest, Concentration
	
				// Alternate ID
				$alt_id_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='Alternate ID'") or die("Error fetching Alternate ID property");
	
				if ($alt_id_ar = mysql_fetch_array($alt_id_rs, MYSQL_ASSOC))
				{
					$alt_id_id = $alt_id_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $alt_id_id . "', '" . $contID . "','REQ')");
	
				// 5' digest/3' Digest
				$digest_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='" . addslashes("5' digest/3' Digest") . "'") or die("Error fetching Digest property");
	
				if ($digest_ar = mysql_fetch_array($digest_rs, MYSQL_ASSOC))
				{
					$digest_id = $digest_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $digest_id . "', '" . $contID . "','REQ')");
	
				// Concentration
				$conc_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='Concentration'") or die("Error fetching Concentration property");
	
				if ($conc_ar = mysql_fetch_array($conc_rs, MYSQL_ASSOC))
				{
					$conc_id = $conc_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $conc_id . "', '" . $contID . "','REQ')");
	
			break;	
	
			case '5':	// Cell Lines - Isolate Name, Plates/Vial, Date, Person, Passage
	
				// Isolate Name
				$iso_name_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='Isolate Name'") or die("Error fetching Isolate Name property");
	
				if ($iso_name_ar = mysql_fetch_array($iso_name_rs, MYSQL_ASSOC))
				{
					$iso_name_id = $iso_name_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $iso_name_id . "', '" . $contID . "','REQ')");
	
				// Plates/Vial
				$plates_vial_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='Plates/Vial'") or die("Error fetching Plates/Vial property");
	
				if ($plates_vial_ar = mysql_fetch_array($plates_vial_rs, MYSQL_ASSOC))
				{
					$plates_vial_id = $plates_vial_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $plates_vial_id . "', '" . $contID . "','REQ')");
	
				// Date
				$date_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='Date'") or die("Error fetching Date property");
	
				if ($date_ar = mysql_fetch_array($date_rs, MYSQL_ASSOC))
				{
					$date_id = $date_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $date_id . "', '" . $contID . "','REQ')");
	
				// Person
				$person_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='Person'") or die("Error fetching Person property");
	
				if ($person_ar = mysql_fetch_array($person_rs, MYSQL_ASSOC))
				{
					$person_id = $person_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $person_id . "', '" . $contID . "','REQ')");
	
				// Passage
				$passage_rs = mysql_query("SELECT `elementTypeID` FROM `PrepElemTypes_tbl` WHERE `propertyName`='Passage'") or die("Error fetching Person property");
	
				if ($passage_ar = mysql_fetch_array($passage_rs, MYSQL_ASSOC))
				{
					$passage_id = $passage_ar["elementTypeID"];
				}
	
				mysql_query("INSERT INTO `Prep_Req_tbl` (`prepElementTypeID`, `containerID`, `requirement`) VALUES ('" . $passage_id . "', '" . $contID . "','REQ')");
	
			break;
	
			default:
				echo "Reagent type not found in database!<br>";
			break;
		}
	}

	/**
	 * Get reagent types allowed to be stored in this container type
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-02-09
	 *
	 * @param INT $selContType
	 * @return Array
	*/
	function getContainerReagentTypes($selContType)
	{
		global $conn;
	
		$r_types_rs = mysql_query("SELECT DISTINCT(reagentTypeID) FROM ContainerReagentTypes_tbl WHERE contTypeID='" . $selContType . "' AND status='ACTIVE'", $conn);
	
		$allowed_r_types = array();
	
		while ($r_types_ar = mysql_fetch_array($r_types_rs, MYSQL_ASSOC))
		{
			$rTypeID = $r_types_ar["reagentTypeID"];
			$allowed_r_types[] = $rTypeID;
		}

		return $allowed_r_types;
	}

	/**
	 * Sort names of plates on plate search page (list returned by selecting a container type)
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-06-02
	 *
	 * @param Array $plates_ar
	*/
	function sortPlateNames($plates_ar)
	{
		// Output the array.  The trick is to sort first the alphabetic and then the numeric portion of the name
		foreach ($plates_ar as $key => $value)
		{
			$plateIndex = strpos($value, "Plate ");
			$temp_ar[$value] = $key;
		}

		return $temp_ar;
	}

	/**
	 * Usage is different from User.php, modifying
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-01-13
	 *
	 * @param INT $selLab
	*/
	function printLabList($selLab=0)
	{
		global $conn;

		$labList = array();

		$currLabID = $_SESSION["userinfo"]->getLabID();
		$currUserCategory = $_SESSION["userinfo"]->getCategory();

		// Select list of labs from the database
		$labList_rs = mysql_query("SELECT labID, lab_name FROM LabInfo_tbl WHERE status='ACTIVE' ORDER BY lab_name", $conn) or die("Error selecting labs: " . mysql_error());
		
		while ($labList_ar = mysql_fetch_array($labList_rs, MYSQL_ASSOC))
		{
			$labID = $labList_ar["labID"];
			$labName = $labList_ar["lab_name"];
			
			$labList[$labID] = $labName;
		}
	
		if ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])
		{
			echo "<SELECT id=\"labList\" name=\"labs\" style=\"font-size:8pt;\">";

			echo "<OPTION VALUE=\"default\">-- Select Laboratory --</OPTION>";	// Feb. 8/10

			foreach ($labList as $labID => $lab)
			{
				if ($selLab != 0)
				{
					if ($selLab == $labID)
						$selected = "SELECTED";
					else
						$selected = "";
				}
				else
				{
					if ($labID == $currLabID)
						$selected = "SELECTED";
					else
						$selected = "";
				}
				
				?><OPTION ID="<?php echo $labID; ?>" NAME="lab_optn" VALUE="<?php echo $labID; ?>" <?php echo $selected; ?>><?php echo $lab; ?></OPTION><?php
			}
		
			echo "</SELECT>";
		}
		else
		{
			// Select the user's lab and disable the rest
			echo "<SELECT id=\"labList\" DISABLED name=\"labs\" style=\"font-size:8pt;\">";
			
			foreach ($labList as $labID => $lab)
			{
				if ($labID == $currLabID)
				{
					$selected = "SELECTED";
				}
				else
				{
					$selected = "";
				}
				
				echo "<OPTION VALUE=\"default\">-- Select Laboratory --</OPTION>";

				?><OPTION ID="<?php echo $labID; ?>" NAME="lab_optn" VALUE="<?php echo $labID; ?>" <?php echo $selected; ?>><?php echo $lab; ?></OPTION><?php
			}
		
			echo "</SELECT>";
		}
	}

	/**
	 * Print a list of storage types (-80 Fridge, room temp, etc. - select the option whose value is $selected)
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-01-13
	 *
	 * @param STRING $selected
	*/
	function printStorageTypes($selected)
	{
		global $conn;

		$props_rs = mysql_query("SELECT locationTypeID, locationTypeName FROM LocationTypes_tbl WHERE status='ACTIVE' ORDER BY locationTypeID") or die("Error fetching storage types: " . mysql_error());

		echo "<SELECT ID='location_types' NAME=\"storage_type\" style=\"font-size:8pt;\">";

		if (!$selected)
			echo "<OPTION value=\"default\" SELECTED>-- Select storage type -- </OPTION>";
		else
			echo "<OPTION value=\"default\" SELECTED>-- Select storage type -- </OPTION>";

		while ($props_ar = mysql_fetch_array($props_rs, MYSQL_ASSOC))
		{
			$locationTypeName = $props_ar["locationTypeName"];
			$locationTypeID = $props_ar["locationTypeID"];

			if ($selected && (strcasecmp($selected, $locationTypeName) == 0))
				echo "<OPTION SELECTED VALUE='" . $locationTypeID . "' NAME='" . $locationTypeName . "'>" . $locationTypeName . "</OPTION>";
			else
				echo "<OPTION VALUE='" . $locationTypeID . "' NAME='" . $locationTypeName . "'>" . $locationTypeName . "</OPTION>";
		}

		echo "</SELECT>";
	}

	/**
	 * Output information summary row for a selected plate (Changing main Location search page)
	 *
	 * This is NOT actively used in this version; will apply in redesigning container search view
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2008-08-28
	 *
	 * @param STRING $plateName
	 * @param INT $contTypeID
	*/
	function printPlateInfo($plateName, $contTypeID)
	{
		global $conn;

		// Aug. 28/08
		$contCheck_rs = mysql_query("SELECT * FROM Container_tbl WHERE name='" . $plateName . "' AND contGroupID=" . $contTypeID . " AND status='ACTIVE'", $conn) or die("ERROR reading from container table sql error" . mysql_error());

		if ($contCheck_ar = mysql_fetch_array($contCheck_rs, MYSQL_ASSOC))
		{
			?>
			<P><table width="100%" border="1" frame="box" rules="all" bordercolor="#004891" id="preview_container_view">
			
			<!-- Updated May 23/07, Marina -->
			<TR>
				<TD class="locationHeading" onClick="sortByColumn('barcode')">Barcode</TD>
	
				<TD class="locationHeading" onClick="sortByColumn('name')">Name</TD>
	
				<TD class="locationHeading" onClick="sortByColumn('containerName')">Type</TD>
	
				<TD class="locationHeading" onClick="sortByColumn('container_desc')">Description</TD>
	
				<TD class="locationHeading" onClick="sortByColumn('description')">Laboratory</TD>
	
				<TD class="locationHeading" onClick="sortByColumn('empty_cells')">Empty Cells</TD>
				
				<TD class="locationHeadingNonLink" colspan="3">Links</TD>
			</TR>
			<?php
	
			// Changed Aug. 28/08
			$contInfo_rs = mysql_query("SELECT * FROM `Container_tbl` a, `ContainerTypeID_tbl` b, LabInfo_tbl l WHERE a.`name`='" . $plateName . "' AND a.`contTypeID`=b.`contTypeID` AND l.labID=a.labID AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND l.status='ACTIVE'", $conn) or die("ERROR reading from container table sql error" . mysql_error());
	
			while ($contInfo_ar = mysql_fetch_array($contInfo_rs, MYSQL_ASSOC))
			{
				echo "<tr>";
				
					// Sept. 19/07, Marina: New - Barcode
					echo "<td align=\"center\">" . $contInfo_ar["barcode"] . "</td>";
		
					// Name
					if (isset($contInfo_ar["name"]))
					{
						echo "<td class=\"plateNameValue\" style=\"text-align:center\">" . $contInfo_ar["name"] . "</td>";
					}
					else
					{
						echo "<td>" . "Not Currently Set" . "</td>";
					}
		
					// Size
					echo "<td class=\"locationOtherValue\">" . $contInfo_ar["containerName"] . "</td>";
					
					
					// Description
					if( isset( $contInfo_ar["container_desc"] ) )
					{
						echo "<td class=\"descriptionValue\"  style=\"text-align:center\">" . $contInfo_ar["container_desc"] . "</td>";
					}
					else
					{
						echo "<td>&nbsp;</td>";
					}
					
					// Lab - get the complete lab name instead of ID - Marina, May 23/07
					echo "<td class=\"locationOtherValue\">" . $contInfo_ar["lab_name"] . "</td>";
		
					// Empty Cells
					echo "<td align=\"center\">" . $this->getEmptyCellNum( $contInfo_ar["containerID"], $contInfo_ar["contTypeID"] ) . "</td>";	// may 23/07
		
					// "Modify" and "View" links
					// Mar 28/06, Marina -- Disallow modification of occupied containers
					$size_rs  = mysql_query( "SELECT `maxCol`, `maxRow` FROM `ContainerTypeID_tbl` WHERE `contTypeID`='" . $contInfo_ar["contTypeID"] . "' AND `status`='ACTIVE'" , $conn ) or die("Error reading container original info in SQL statement: " . mysql_error());
						
					if ($size_ar = mysql_fetch_array( $size_rs, MYSQL_ASSOC ) )
					{
						$cols = $size_ar["maxCol"];
						$rows = $size_ar["maxRow"];
					}
				
					$capacity = $cols * $rows;
					$empty_cells = $this->getEmptyCellNum($contInfo_ar["containerID"], $contInfo_ar["contTypeID"]);
					$occupied_cells = $capacity - $empty_cells;
		
					// Sept. 12/07, Marina: A Reader cannot modify in any case
					$currUserID = $_SESSION["userinfo"]->getUserID();
					$currUserCategory = $_SESSION["userinfo"]->getCategory();
		
					if (($occupied_cells > 0) || ($currUserCategory == $_SESSION["userCategoryNames"]["Reader"]))
					{
						echo "<td class=\"adminDisabled\">Modify</td>";
					}
					else
					{
						echo "<td class=\"locationOtherValue\">" . "<a href=\"Location.php?View=5&Mod=" . $contInfo_ar["containerID"] . "\">Modify</a></td>";
						
					}
		
					echo "<td class=\"locationOtherValue\">" . "<a href=\"Location.php?View=2&Mod=" . $contInfo_ar["containerID"] . "\">View</a></td>";	// may 23/07

				echo "</tr>";
			}
	
			echo "</table>";
		}
	}

	/**
	 * Find the experiment ID of a given reagent
	 *
	 * @author Marina Olhovsky
	 * @version April 27, 2011
	 *
	 * @param Array or INT User can query for a single reagent ID or a list of reagent IDs to fetch experiment ID(s) for
	 *
	 * @return Array
	*/
	function findReagentExperimentID($rID)
	{
		global $conn;

		$experiments_ar = Array();

		if (is_array($rID))
		{
//			$rID_str = implode($rID, ",");
//			$rID_list = "(" . $rID_str . ")";

			foreach ($rID as $a => $r)
			{
				$exp_rs = mysql_query("SELECT expID FROM Experiment_tbl WHERE reagentID = '" . $r . "' AND status='ACTIVE'");	
				
				while ($exp_ar = mysql_fetch_array($exp_rs, MYSQL_ASSOC))
				{
					$expID = $exp_ar["expID"];
					$experiments_ar[$r] = $expID;
				}
			}
		}
		else
		{
			$exp_rs = mysql_query("SELECT expID FROM Experiment_tbl WHERE reagentID='" . $rID . "' AND status='ACTIVE'");
		
			while ($exp_ar = mysql_fetch_array($exp_rs, MYSQL_ASSOC))
			{
				$expID = $exp_ar["expID"];
				$experiments_ar[] = $expID;
			}
		}

		return $experiments_ar;
	}


	/**
	 * Print search results by container type (main container search view, where a type - 'Glycerol Stocks' or 'Liquid Nitrogen' is selected)
	 *
	 * Last modified: May 23/07, Marina: Add option to sort by column
	 *
	 * @author John Paul Lee @version 2005
	 *
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT $contGroupID
	*/
	function printContainerInfo($contGroupID)
	{
		//print_r($_POST);

		global $conn;
		?>
		<table width="100%" border="1" frame="box" rules="all" bordercolor="#004891" id="preview_container_view">
		
		<!-- Updated May 23/07, Marina -->
		<TR>
			<TD class="locationHeading"  style="padding-left:3px; padding-right:3px;" onClick="sortByColumn('contGroupCount')">Container #</TD>

			<TD class="locationHeading" onClick="sortByColumn('barcode')" style="padding-left:3px; padding-right:3px;">Barcode</TD>

			<TD class="locationHeading"  style="padding-left:3px; padding-right:3px;" onClick="sortByColumn('name')">Name</TD>

			<TD  style="padding-left:3px; padding-right:3px;" class="locationHeading" onClick="sortByColumn('containerName')">Type</TD>

			<TD class="locationHeading"  style="padding-left:3px; padding-right:3px;" onClick="sortByColumn('container_desc')">Description</TD>

			<TD class="locationHeading" onClick="sortByColumn('description')" style="padding-left:3px; padding-right:3px;">Laboratory</TD>

			<TD class="locationHeading" onClick="sortByColumn('empty_cells')" style="padding-left:3px; padding-right:3px;">Empty Cells</TD>
			
			<TD class="locationHeadingNonLink" style="padding-left:3px; padding-right:3px;" colspan="3">Links</TD>
		</TR>
		<?php
 		
		// May 23/07, Marina: Add column 'sort' option
		if (isset($_POST["sortOn"]) && (strlen($_POST["sortOn"]) != 0))
		{
			$sortBy = $_POST["sortOn"];

			if ($sortBy != "empty_cells")
			{
				$q = "SELECT * FROM `Container_tbl` a, `ContainerTypeID_tbl` b, LabInfo_tbl l WHERE a.`contGroupID`='" . $contGroupID . "' AND a.`contTypeID`=b.`contTypeID` AND l.labID=a.labID AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND l.status='ACTIVE' ORDER BY " . $sortBy;

				//echo $q;

				$contInfo_rs = mysql_query("SELECT * FROM `Container_tbl` a, `ContainerTypeID_tbl` b, LabInfo_tbl l WHERE a.`contGroupID`='" . $contGroupID . "' AND a.`contTypeID`=b.`contTypeID` AND l.labID=a.labID AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND l.status='ACTIVE' ORDER BY " . $sortBy, $conn) or die("ERROR reading from container table sql error" . mysql_error());

				while ($contInfo_ar = mysql_fetch_array($contInfo_rs, MYSQL_ASSOC))
				{
					$contLab = $contInfo_ar["labID"];

					echo "<tr>";

					// Container #
					// (represents the container's SERIAL NUMBER IN ITS CATEGORY)
					// (e.g. Cont.#37 comes 12th in the Glycerol Stocks group, etc.)
					echo "<td align=\"center\">" . $contInfo_ar["contGroupCount"] . "</td>";

					// Sept. 19/07, Marina: New - Barcode
					echo "<td align=\"center\">" . $contInfo_ar["barcode"] . "</td>";

					// Name
					if( isset( $contInfo_ar["name"] ) )
					{
						echo "<td class=\"plateNameValue\">" . $contInfo_ar["name"] . "</td>";	// may 23/07
					}
					else
					{
						echo "<td>" . "Not Currently Set" . "</td>";
					}

					// Size
					echo "<td class=\"locationOtherValue\">" . $contInfo_ar["containerName"] . "</td>";	// may 23/07
					
					// Description
					if( isset( $contInfo_ar["container_desc"] ) )
					{
						echo "<td class=\"descriptionValue\">" . $contInfo_ar["container_desc"] . "</td>";	// may 23/07
					}
					else
					{
						echo "<td>&nbsp;</td>";
					}
					
					// Lab - get the complete lab name instead of ID - Marina, May 23/07
					echo "<td class=\"locationOtherValue\">" . $contInfo_ar["lab_name"] . "</td>";		// may 23/07

					// Empty Cells
					echo "<td align=\"center\">" . $this->getEmptyCellNum( $contInfo_ar["containerID"], $contInfo_ar["contTypeID"] ) . "</td>";	// may 23/07

					// "Modify" and "View" links
					// Mar 28/06, Marina -- Disallow modification of occupied containers
					$size_rs  = mysql_query( "SELECT `maxCol`, `maxRow` FROM `ContainerTypeID_tbl` WHERE `contTypeID`='" . $contInfo_ar["contTypeID"] . "' AND `status`='ACTIVE'" , $conn ) or die("Error reading container original info in SQL statement: " . mysql_error());
					
					if ($size_ar = mysql_fetch_array( $size_rs, MYSQL_ASSOC ) )
					{
						$cols = $size_ar["maxCol"];
						$rows = $size_ar["maxRow"];
					}
				
					$capacity = $cols * $rows;
					$empty_cells = $this->getEmptyCellNum($contInfo_ar["containerID"], $contInfo_ar["contTypeID"]);
					$occupied_cells = $capacity - $empty_cells;

					// Sept. 12/07, Marina: A Reader cannot modify in any case
					$currUserID = $_SESSION["userinfo"]->getUserID();
					$currUserCategory = $_SESSION["userinfo"]->getCategory();
					$currUserProjects = getAllowedUserProjectIDs($currUserID);
					$currUserLab = $_SESSION["userinfo"]->getLabID();

					$rfunc_obj = new Reagent_Function_Class();

					$packetPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["packet id"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

					// Jan. 7, 2010: find out whether user has project access to ALL preps in container; if s/he does not, disable 'View' link
					$q = "SELECT DISTINCT(l.propertyValue) FROM Container_tbl c, ReagentPropList_tbl l, Prep_tbl p, Wells_tbl w, Isolate_tbl i, Experiment_tbl e WHERE c.containerID='" . $contInfo_ar["containerID"] . "' AND w.containerID=c.containerID AND p.wellID=w.wellID AND i.isolate_pk=p.isolate_pk AND i.expID=e.expID AND e.reagentID=l.reagentID AND l.propertyID='" . $packetPropID . "' AND c.status='ACTIVE' AND l.status='ACTIVE' AND p.status='ACTIVE' AND w.status='ACTIVE' AND i.status='ACTIVE' AND e.status='ACTIVE'";

					$prepProject_rs = mysql_query($q, $conn) or die("Error finding prep project IDs: " . mysql_error());
					$prepProjects = Array();

					while ($prepProject_ar = mysql_fetch_array($prepProject_rs, MYSQL_ASSOC))
					{
						$prepProjects[] = $prepProject_ar["propertyValue"];
					}

					$isAllowed = false;

					foreach ($prepProjects as $pKey => $pID)
					{
						if (in_array($pID, $currUserProjects))
						{
							$isAllowed = true;
							break;
						}
					}

					if ($occupied_cells == 0)
					{
						// may view empty containers
						$isAllowed = true;
					}

					if ($isAllowed)
						echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\">" . "<a href=\"Location.php?View=2&Mod=" . $contInfo_ar["containerID"] . "\">View</a></td>";	// may 23/07
					else
						echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\">" . "<SPAN class=\"linkDisabled\" style=\"font-size:9pt;\">View</SPAN></td>";

					if (($occupied_cells == 0) && ($currUserCategory != $_SESSION["userCategoryNames"]["Reader"]) && (($contLab == $currUserLab) || ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])))
					{
						echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\">" . "<a href=\"Location.php?View=5&Mod=" . $contInfo_ar["containerID"] . "\">Modify</a></td>";	// may 23/07
						
					}
					else
					{
						echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\"><span class=\"linkDisabled\" style=\"font-size:9pt;\">Modify</span></td>";		// may 23/07
					}

					// Jan. 4, 2010
					if (($currUserCategory != $_SESSION["userCategoryNames"]["Reader"]) && (($contLab == $currUserLab) || ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])))
					{
						echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\">" . "<a href=\"Location.php?View=3&Mod=" . $contInfo_ar["containerID"] . "\">Location</a></td>";
					}
					else
					{
						echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\"><span class=\"linkDisabled\" style=\"font-size:9pt;\">Location</span></td>";
					}

					echo "</tr>";
				}
			}
			else
			{
				// Find empty cells first and sort; then repeat selection (use different variable names)
				$contInfo_tmp_rs = mysql_query("SELECT * FROM `Container_tbl` a, `ContainerTypeID_tbl` b, LabInfo_tbl l WHERE a.`contGroupID`='" . $contGroupID . "' AND a.`contTypeID`=b.`contTypeID` AND l.labID=a.labID AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND l.status='ACTIVE'", $conn) or die("ERROR reading from container table sql error" . mysql_error());			

				// generate a sorted array by empty cells
				while ($contInfo_tmp_ar = mysql_fetch_array($contInfo_tmp_rs, MYSQL_ASSOC))
				{
					$cEmpty = $this->getEmptyCellNum($contInfo_tmp_ar["containerID"], $contInfo_tmp_ar["contTypeID"]);
					$cEmpty_sorted[$contInfo_tmp_ar["containerID"]] = $cEmpty;
				}

				asort($cEmpty_sorted);
				
				//print_r($cEmpty_sorted);

				// Repeat search, this time fetching actual containers
				foreach ($cEmpty_sorted as $contID => $emptyCells)
				{
					//echo $contID . ", ";

				//	echo "SELECT * FROM `Container_tbl` a, `ContainerTypeID_tbl` b, LabInfo_tbl l WHERE a.`containerID`='" . $contID . "' AND a.`contTypeID`=b.`contTypeID` AND l.labID=a.labID AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND l.status='ACTIVE'";

					$contInfo_rs = mysql_query("SELECT * FROM `Container_tbl` a, `ContainerTypeID_tbl` b, LabInfo_tbl l WHERE a.`containerID`='" . $contID . "' AND a.`contTypeID`=b.`contTypeID` AND l.labID=a.labID AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND l.status='ACTIVE'", $conn) or die("ERROR reading from container table sql error" . mysql_error());

					while ($contInfo_ar = mysql_fetch_array($contInfo_rs, MYSQL_ASSOC))
					{
						$contLab = $contInfo_ar["labID"];

						echo "<tr>";

						// Container #
						// (represents the container's SERIAL NUMBER IN ITS CATEGORY)
						// (e.g. Cont.#37 comes 12th in the Glycerol Stocks group, etc.)
						echo "<td align=\"center\">" . $contInfo_ar["contGroupCount"] . "</td>";

						// Sept. 19/07, Marina: New - Barcode
						echo "<td align=\"center\">" . $contInfo_ar["barcode"] . "</td>";

						// Name
						if( isset( $contInfo_ar["name"] ) )
						{
							echo "<td class=\"plateNameValue\">" . $contInfo_ar["name"] . "</td>";	// may 23/07
						}
						else
						{
							echo "<td>" . "Not Currently Set" . "</td>";
						}

						// Size
						echo "<td class=\"locationOtherValue\">" . $contInfo_ar["containerName"] . "</td>";	// may 23/07
						
						
						// Description
						if( isset( $contInfo_ar["container_desc"] ) )
						{
							echo "<td class=\"descriptionValue\">" . $contInfo_ar["container_desc"] . "</td>";	// may 23/07
						}
						else
						{
							echo "<td>&nbsp;</td>";
						}
						
						// Lab - get the complete lab name instead of ID - Marina, May 23/07
						echo "<td class=\"locationOtherValue\">" . $contInfo_ar["lab_name"] . "</td>";		// may 23/07

						// Empty Cells
						echo "<td align=\"center\">" . $cEmpty_sorted[$contInfo_ar["containerID"]] . "</td>";

						//echo "<td align=\"center\">" . $this->getEmptyCellNum( $contInfo_ar["containerID"], $contInfo_ar["contTypeID"] ) . "</td>";	// may 23/07

						// "Modify" and "View" links
						// Mar 28/06, Marina -- Disallow modification of occupied containers
						$size_rs  = mysql_query( "SELECT `maxCol`, `maxRow` FROM `ContainerTypeID_tbl` WHERE `contTypeID`='" . $contInfo_ar["contTypeID"] . "' AND `status`='ACTIVE'" , $conn ) or die("Error reading container original info in SQL statement: " . mysql_error());
						
						if ($size_ar = mysql_fetch_array( $size_rs, MYSQL_ASSOC ) )
						{
							$cols = $size_ar["maxCol"];
							$rows = $size_ar["maxRow"];
						}
					
						$capacity = $cols * $rows;
						$empty_cells = $this->getEmptyCellNum($contInfo_ar["containerID"], $contInfo_ar["contTypeID"]);
						$occupied_cells = $capacity - $empty_cells;

						// Sept. 12/07, Marina: A Reader cannot modify in any case
						$currUserID = $_SESSION["userinfo"]->getUserID();
						$currUserCategory = $_SESSION["userinfo"]->getCategory();
						$currUserProjects = getAllowedUserProjectIDs($currUserID);
						$currUserLab = $_SESSION["userinfo"]->getLabID();

						$rfunc_obj = new Reagent_Function_Class();

						$packetPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["packet id"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

						// Jan. 7, 2010: find out whether user has project access to ALL preps in container; if s/he does not, disable 'View' link
						$q = "SELECT DISTINCT(l.propertyValue) FROM Container_tbl c, ReagentPropList_tbl l, Prep_tbl p, Wells_tbl w, Isolate_tbl i, Experiment_tbl e WHERE c.containerID='" . $contInfo_ar["containerID"] . "' AND w.containerID=c.containerID AND p.wellID=w.wellID AND i.isolate_pk=p.isolate_pk AND i.expID=e.expID AND e.reagentID=l.reagentID AND l.propertyID='" . $packetPropID . "' AND c.status='ACTIVE' AND l.status='ACTIVE' AND p.status='ACTIVE' AND w.status='ACTIVE' AND i.status='ACTIVE' AND e.status='ACTIVE'";

						$prepProject_rs = mysql_query($q, $conn) or die("Error finding prep project IDs: " . mysql_error());
						$prepProjects = Array();

						while ($prepProject_ar = mysql_fetch_array($prepProject_rs, MYSQL_ASSOC))
						{
							$prepProjects[] = $prepProject_ar["propertyValue"];
						}

						$isAllowed = false;

						foreach ($prepProjects as $pKey => $pID)
						{
							if (in_array($pID, $currUserProjects))
							{
								$isAllowed = true;
								break;
							}
						}

						if ($occupied_cells == 0)
						{
							// may view empty containers
							$isAllowed = true;
						}

						if ($isAllowed)
							echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\">" . "<a href=\"Location.php?View=2&Mod=" . $contInfo_ar["containerID"] . "\">View</a></td>";	// may 23/07
						else
							echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\">" . "<SPAN class=\"linkDisabled\" style=\"font-size:9pt;\">View</SPAN></td>";

						if (($occupied_cells == 0) && ($currUserCategory != $_SESSION["userCategoryNames"]["Reader"]) && (($contLab == $currUserLab) || ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])))
						{
							echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\">" . "<a href=\"Location.php?View=5&Mod=" . $contInfo_ar["containerID"] . "\">Modify</a></td>";	// may 23/07
							
						}
						else
						{
							echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\"><span class=\"linkDisabled\" style=\"font-size:9pt;\">Modify</span></td>";		// may 23/07
						}

						// Jan. 4, 2010
						if (($currUserCategory != $_SESSION["userCategoryNames"]["Reader"]) && (($contLab == $currUserLab) || ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])))
						{
							echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\">" . "<a href=\"Location.php?View=3&Mod=" . $contInfo_ar["containerID"] . "\">Location</a></td>";
						}
						else
						{
							echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\"><span class=\"linkDisabled\" style=\"font-size:9pt;\">Location</span></td>";
						}

						echo "</tr>";
					}
				}
			}
		}
		else
		{
			//$q = "SELECT * FROM `Container_tbl` a, `ContainerTypeID_tbl` b, LabInfo_tbl l WHERE a.`contGroupID`='" . $contGroupID . "' AND a.`contTypeID`=b.`contTypeID` AND l.labID=a.labID AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND l.status='ACTIVE' ORDER BY contGroupCount";

			$contInfo_rs = mysql_query("SELECT * FROM `Container_tbl` a, `ContainerTypeID_tbl` b, LabInfo_tbl l WHERE a.`contGroupID`='" . $contGroupID . "' AND a.`contTypeID`=b.`contTypeID` AND l.labID=a.labID AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND l.status='ACTIVE' ORDER BY contGroupCount", $conn) or die("ERROR reading from container table sql error" . mysql_error());

			while ($contInfo_ar = mysql_fetch_array($contInfo_rs, MYSQL_ASSOC))
			{
				$contLab = $contInfo_ar["labID"];

				echo "<tr>";

				// Container #
				// (represents the container's SERIAL NUMBER IN ITS CATEGORY)
				// (e.g. Cont.#37 comes 12th in the Glycerol Stocks group, etc.)
				echo "<td align=\"center\">" . $contInfo_ar["contGroupCount"] . "</td>";

				// Sept. 19/07, Marina: New - Barcode
				echo "<td align=\"center\">" . $contInfo_ar["barcode"] . "</td>";

				// Name
				if( isset( $contInfo_ar["name"] ) )
				{
					echo "<td class=\"plateNameValue\">" . $contInfo_ar["name"] . "</td>";	// may 23/07
				}
				else
				{
					echo "<td>" . "Not Currently Set" . "</td>";
				}

				// Size
				echo "<td class=\"locationOtherValue\">" . $contInfo_ar["containerName"] . "</td>";	// may 23/07
				
				
				// Description
				if( isset( $contInfo_ar["container_desc"] ) )
				{
					echo "<td class=\"descriptionValue\">" . $contInfo_ar["container_desc"] . "</td>";	// may 23/07
				}
				else
				{
					echo "<td>&nbsp;</td>";
				}
				
				// Lab - get the complete lab name instead of ID - Marina, May 23/07
				echo "<td class=\"locationOtherValue\">" . $contInfo_ar["lab_name"] . "</td>";		// may 23/07

				// Empty Cells
				echo "<td align=\"center\">" . $this->getEmptyCellNum( $contInfo_ar["containerID"], $contInfo_ar["contTypeID"] ) . "</td>";	// may 23/07

				// "Modify" and "View" links
				// Mar 28/06, Marina -- Disallow modification of occupied containers
				$size_rs  = mysql_query( "SELECT `maxCol`, `maxRow` FROM `ContainerTypeID_tbl` WHERE `contTypeID`='" . $contInfo_ar["contTypeID"] . "' AND `status`='ACTIVE'" , $conn ) or die("Error reading container original info in SQL statement: " . mysql_error());
				
				if ($size_ar = mysql_fetch_array( $size_rs, MYSQL_ASSOC ) )
				{
					$cols = $size_ar["maxCol"];
					$rows = $size_ar["maxRow"];
				}
			
				$capacity = $cols * $rows;
				$empty_cells = $this->getEmptyCellNum($contInfo_ar["containerID"], $contInfo_ar["contTypeID"]);
				$occupied_cells = $capacity - $empty_cells;

				// Sept. 12/07, Marina: A Reader cannot modify in any case
				$currUserID = $_SESSION["userinfo"]->getUserID();
				$currUserCategory = $_SESSION["userinfo"]->getCategory();
				$currUserProjects = getAllowedUserProjectIDs($currUserID);
				$currUserLab = $_SESSION["userinfo"]->getLabID();

				$rfunc_obj = new Reagent_Function_Class();

				$packetPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["packet id"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

				// Jan. 7, 2010: find out whether user has project access to ALL preps in container; if s/he does not, disable 'View' link
				$q = "SELECT DISTINCT(l.propertyValue) FROM Container_tbl c, ReagentPropList_tbl l, Prep_tbl p, Wells_tbl w, Isolate_tbl i, Experiment_tbl e WHERE c.containerID='" . $contInfo_ar["containerID"] . "' AND w.containerID=c.containerID AND p.wellID=w.wellID AND i.isolate_pk=p.isolate_pk AND i.expID=e.expID AND e.reagentID=l.reagentID AND l.propertyID='" . $packetPropID . "' AND c.status='ACTIVE' AND l.status='ACTIVE' AND p.status='ACTIVE' AND w.status='ACTIVE' AND i.status='ACTIVE' AND e.status='ACTIVE'";

				$prepProject_rs = mysql_query($q, $conn) or die("Error finding prep project IDs: " . mysql_error());
				$prepProjects = Array();

				while ($prepProject_ar = mysql_fetch_array($prepProject_rs, MYSQL_ASSOC))
				{
					$prepProjects[] = $prepProject_ar["propertyValue"];
				}

				$isAllowed = false;

				foreach ($prepProjects as $pKey => $pID)
				{
					if (in_array($pID, $currUserProjects))
					{
						$isAllowed = true;
						break;
					}
				}

				if ($occupied_cells == 0)
				{
					// may view empty containers
					$isAllowed = true;
				}

				if ($isAllowed)
					echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\">" . "<a href=\"Location.php?View=2&Mod=" . $contInfo_ar["containerID"] . "\">View</a></td>";	// may 23/07
				else
					echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\">" . "<SPAN class=\"linkDisabled\" style=\"font-size:9pt;\">View</SPAN></td>";

				if (($occupied_cells == 0) && ($currUserCategory != $_SESSION["userCategoryNames"]["Reader"]) && (($contLab == $currUserLab) || ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])))
				{
					echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\">" . "<a href=\"Location.php?View=5&Mod=" . $contInfo_ar["containerID"] . "\">Modify</a></td>";	// may 23/07
					
				}
				else
				{
					echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\"><span class=\"linkDisabled\" style=\"font-size:9pt;\">Modify</span></td>";		// may 23/07
				}

				// Jan. 4, 2010
				if (($currUserCategory != $_SESSION["userCategoryNames"]["Reader"]) && (($contLab == $currUserLab) || ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])))
				{
					echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\">" . "<a href=\"Location.php?View=3&Mod=" . $contInfo_ar["containerID"] . "\">Location</a></td>";
				}
				else
				{
					echo "<td class=\"locationOtherValue\" style=\"padding-left:7px; padding-right:7px;\"><span class=\"linkDisabled\" style=\"font-size:9pt;\">Location</span></td>";
				}

				echo "</tr>";
			}
		}
		
		echo "</table>";
	}
	

	/**
	 * Retrieve size of container identified by $contID
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-01-03
	 *
	 * @param INT $contID
	 * @return INT
	*/
	function getContainerSize($contID)
	{
		global $conn;
		
		$contSize = 0;

		$maxContSize_rs = mysql_query("SELECT (`maxCol` * `maxRow`) AS maxSize FROM `ContainerTypeID_tbl` WHERE `contTypeID`='" . $contTypeID . "' AND `status`='ACTIVE'") or die("ERROR reading container type id table sql " . mysql_error());
		
		if ($maxContSize_ar = mysql_fetch_array($maxContSize_rs, MYSQL_ASSOC))
		{
			$contSize = $contSize_ar["maxSize"];
		}

		return $contSize;
	}

	/**
	 * Determine if any containers of type $contTypeID exist in the database
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-01-12
	 *
	 * @param INT $contTypeID
	 * @return boolean
	*/
	function isUsedContainerType($contTypeID)
	{
		global $conn;

		$numConts = 0;

		$contTypeSet = mysql_query("SELECT COUNT(containerID) AS cont_num FROM Container_tbl WHERE contGroupID='" . $contTypeID . "' AND status='ACTIVE'", $conn);

		if ($contTypes = mysql_fetch_array($contTypeSet, MYSQL_ASSOC))
		{
			$numConts = $contTypes["cont_num"];
		}

		return $numConts > 0;
	}


	/**
	 * Determine if this container is empty
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-01-03
	 *
	 * @param INT $contID
	 * @return boolean
	*/
	function isEmpty($contID)
	{
		global $conn;
	
		$contTypeID = $this->getContainerSize($contID);
		return ($this->getEmptyCellNum($contID, $contTypeID) == 0);
	}


	/**
	 * Get the number of unoccupied slots in this container
	 *
	 * Last modified: October 9, 2006
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT $contID
	 * @param INT $contTypeID
	 *
	 * @return INT
	*/
	function getEmptyCellNum($contID, $contTypeID)
	{
		global $conn;
		
		// Get the maximum capacity of this container TYPE (i.e. 96, 81, etc)
		$maxContSize_rs = mysql_query("SELECT (`maxCol` * `maxRow`) AS maxSize FROM `ContainerTypeID_tbl` WHERE `contTypeID`='" . $contTypeID . "' AND `status`='ACTIVE'") or die("ERROR reading container type id table sql " . mysql_error());
		
		$maxContSize_ar = mysql_fetch_array($maxContSize_rs, MYSQL_ASSOC);	
		
		// Find number of wells occupied in current container:
		$maxWells_rs = mysql_query("SELECT COUNT(p.`wellID`) AS currentSize FROM `Wells_tbl` w, `Prep_tbl` p WHERE w.`containerID`='" . $contID . "' AND w.`wellID`=p.`wellID` AND w.`status`='ACTIVE' AND p.`status`='ACTIVE'") or die("ERROR reading container table!" . mysql_error());
	
		$maxWells_ar = mysql_fetch_array( $maxWells_rs, MYSQL_ASSOC );
		
		// empty cells = (capacity - occupied cells)
		return $maxContSize_ar["maxSize"] - $maxWells_ar["currentSize"];
	}
	

	/**
	 * Is this container isolate active?
	 *
	 * Modified Sept. 20/07: Changed table structure - isolate_active is no longer a column in Container_tbl but has been moved into ContainerGroup_tbl - modify selection function accordingly
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT $contID
	 * @return boolean
	*/
	function isIsoActive($contID)
	{
		global $conn;
		
		$contGroupName = $this->getContainerGroupName($contID);

		$isoAct_rs = mysql_query("SELECT `isolate_active` FROM `ContainerGroup_tbl` WHERE `contGroupName`='" . $contGroupName . "' AND `status`='ACTIVE'", $conn) or die( "FAILURE IN: Location_Funct_Class.getIsoAct_State(1): " . mysql_error() );
		
		$isoAct_ar = mysql_fetch_array( $isoAct_rs , MYSQL_ASSOC );
		
		$iso_act = $isoAct_ar["isolate_active"];
		
		mysql_free_result( $isoAct_rs );
		unset( $isoAct_rs, $isoAct_ar );
		
		return $iso_act;
	}

	/**
	 * Return the attributes of this container type
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-01-08
	 *
	 * @param INT $selContType
	 * @return Array
	*/
	function getContainerTypeAttributes($selContType)
	{
		global $conn;

		$attrs_rs = mysql_query("SELECT p.elementTypeID, p.propertyName FROM ContainerTypeAttributes_tbl c, PrepElemTypes_tbl p WHERE c.containerTypeID='" . $selContType . "' AND c.containerTypeAttributeID=p.elementTypeID AND c.status='ACTIVE' AND p.status='ACTIVE' ORDER BY p.elementTypeID");

		$props = Array();

		while ($attrs_ar = mysql_fetch_array($attrs_rs, MYSQL_ASSOC))
		{
			$propID = $attrs_ar["elementTypeID"];
			$propType = $attrs_ar["propertyName"];
			$props[$propID] = $propType;
		}

		return $props;
	}


	/**
	 * Does this reagent type have any preps in this container type? (e.g. are there any Cell Lines stored in Liquid Nitrogen plates)
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-06-22
	 *
	 * @param INT $selContType
	 * @param INT $rType
	 * @return boolean
	*/
	function isUsedContainerReagentType($selContType, $rType)
	{
		global $conn;

		$isUsed_rs = mysql_query("select count(*) as num_used from Experiment_tbl e, Isolate_tbl i, Prep_tbl p, Wells_tbl w, Reagents_tbl r, Container_tbl c where r.reagentTypeID='" . $rType . "' and e.reagentID=r.reagentID and e.expID=i.expID and i.isolate_pk=p.isolate_pk and p.wellID=w.wellID and w.containerID=c.containerID and c.contGroupID='" . $selContType . "' and e.status='ACTIVE' and i.status='ACTIVE' and p.status='ACTIVE' and w.status='ACTIVE'", $conn) or die("Error checking used container feature: " . mysql_error());

		if ($isUsed_ar = mysql_fetch_array($isUsed_rs, MYSQL_ASSOC))
		{
			$numUsed = $isUsed_ar["num_used"];

			if ($numUsed > 0)
				return true;
		}

		return false;
	}

	/**
	 * Is the given property assigned to any preps in this container type?
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-01-11
	 *
	 * @param INT $selContType
	 * @param INT $elementTypeID
	 * @return boolean
	*/
	function isUsedPrepElement($selContType, $elementTypeID)
	{
		global $conn;

		$isUsed_rs = mysql_query("SELECT COUNT(prepPropID) as num_used FROM PrepElementProp_tbl pep, Prep_tbl p, Wells_tbl w, Container_tbl c WHERE pep.elementTypeID='" . $elementTypeID . "' AND pep.prepID=p.prepID and p.wellID=w.wellID and w.containerID=c.containerID and c.contGroupID='" . $selContType . "' AND p.status='ACTIVE' AND c.status='ACTIVE' AND p.status='ACTIVE'", $conn) or die("Error checking used container feature: " . mysql_error());

		if ($isUsed_ar = mysql_fetch_array($isUsed_rs, MYSQL_ASSOC))
		{
			$numUsed = $isUsed_ar["num_used"];

			if ($numUsed > 0)
				return true;
		}

		return false;
	}


	/**
	 * Is this container **type**, identified by $contTypeID, isolate active?
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-01-08
	 *
	 * @param INT $contTypeID
	 * @return boolean
	*/
	function isolateActive($contTypeID)
	{
		global $conn;

		$isoAct_rs = mysql_query("SELECT isolate_active FROM ContainerGroup_tbl WHERE contGroupID='" . $contTypeID . "' AND status='ACTIVE'", $conn) or die("FAILURE IN: Location_Funct_Class.getIsoAct_State(2): " . mysql_error());
		
		$isoAct_ar = mysql_fetch_array($isoAct_rs, MYSQL_ASSOC);
		
		$iso_act = $isoAct_ar["isolate_active"];
		
		mysql_free_result($isoAct_rs);
		unset($isoAct_rs, $isoAct_ar);
		
		return $iso_act;
	}

	/**
	 * Converts an integer into the alphabet base (A-Z).
	 *
	 * Source: http://php.net/manual/fr/function.base-convert.php
	 *
	 * @param int $n This is the number to convert.
	 * @return string The converted number.
	 * @author Theriault
	 *
	*/
	function num2alpha($n) 
	{
		$r = '';

		for ($i = 1; $n >= 0 && $i < 10; $i++) 
		{
			$r = chr(0x41 + ($n % pow(26, $i) / pow(26, $i - 1))) . $r;
			$n -= pow(26, $i);
		}

		return $r;
	}
	
	/**
	* Converts an alphabetic string into an integer.
	*
	* @param int $n This is the number to convert.
	* @return string The converted number.
	* @author Theriault http://php.net/manual/fr/function.base-convert.php
	*
	*/
	function alpha2num($a)
	{
		$r = 0;
		$l = strlen($a);

		for ($i = 0; $i < $l; $i++)
		{
			$r += pow(26, $i) * (ord($a[$l - $i - 1]) - 0x40);
		}

		return $r - 1;
	}

	/**
	 * Get the letter that corresponds to the number representing a row in a container.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-01-08
	 *
	 * @param INT $row
	 * @return STRING
	*/
	function getLetterRow($row)
	{
		return $this->num2alpha($row - 1);
	}

	/**
	 * Determine if the reagent argument is in the current user's order. Return 1 if yes or 0 if no
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2008-01-23
	 *
	 * @param INT $row
	 * @return INT
	*/
	function inOrder($rID, $isoNum, $contID, $row, $col)
	{
		$userID = $_SESSION["userinfo"]->getUserID();
		$userOrders = $_SESSION["userinfo"]->getOrders();
		$tempOrderKey = $rID . "_" . $isoNum . "_" . $contID . "_" . $row . "_" . $col;

		foreach ($userOrders as $key => $order)
		{
			$oKey = $order->getOrderKey();

			if (strcasecmp($tempOrderKey, $oKey) == 0)
				return 1;
		}

		return 0;
	}
}
?>