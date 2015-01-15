<?php
/**
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
* @package Location
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
 * This class handles location administration functions, often by invoking functions in other classes of the module
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Location
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 *
*/
class Location_Admin_Class
{
	/**
	 * Default constructor
	*/
	function Location_Admin_Class()
	{}
	
	/**
	 * Print details of the container type identified by $contGroupID
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-01-16
	 *
	 * @param INT $contGroupID Represents a container type, e.g. '2' => 'Glycerol Stock'
	*/
	function printContainerInfo($contGroupID)
	{
		$loc_obj = new Location_Funct_Class();
		$loc_obj->printContainerInfo($contGroupID);
	}

	/**
	 * Print information on a container's storage type (fridge, freezer, LN tank, etc.)
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-01-03
	 *
	 * @param INT $contID
	 * @param boolean $modify_state
	*/
	function printContainerStorageInfo($contID, $modify_state)
	{
		global $conn;
		global $hostname;
		global $cgi_path;

		$lfunc = new Location_Funct_Class();

		// Jan. 3, 2010 - Let Admin delete plate IFF EMPTY
		$currUserID = $_SESSION["userinfo"]->getUserID();
		$currUserCategory = $_SESSION["userinfo"]->getCategory();
		$currUserLab = $_SESSION["userinfo"]->getLabID();

		$contName = $lfunc->getContainerName($contID);
		$contLab = $lfunc->getContainerLabID($contID);

		$original_info_rs = mysql_query("SELECT name, location, locationName, address, shelf, rack, row_number, col_number, locationTypeName FROM Container_tbl c, LocationTypes_tbl l WHERE containerID='" . $contID . "' AND c.status='ACTIVE' AND l.locationTypeID=c.location AND l.status='ACTIVE'", $conn) or die("Error reading container original info in SQL statement: " . mysql_error());

		if ($original_info_ar = mysql_fetch_array($original_info_rs, MYSQL_ASSOC))
		{
			// Display storage type, name, shelf, rack, column, row
			$storageType = $original_info_ar["locationTypeName"];
			$contName = $original_info_ar["name"];
			$storageName = $original_info_ar["locationName"];
			$address = $original_info_ar["address"];
			$shelf = $original_info_ar["shelf"];
			$rack = $original_info_ar["rack"];
			$row = $original_info_ar["row_number"];
			$column = $original_info_ar["col_number"];
		}

			?><FORM method=post action="<?php echo $cgi_path . "location_request_handler.py"; ?>" onSubmit="return confirmMandatoryLocation() && checkLocationNumeric();">
			<input type="hidden" name="cont_id_hidden" value="<?php echo $contID; ?>">

			<table width="765px" cellpadding="5" cellspacing="5" border="1" frame="void" rules="all">
				<TR>
					<td colspan="2" style="font-weight:bold; font-size:12pt; text-align:center; color:#0000DF;">
						<HR>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $contName; ?> Storage Information<?php

						// Modification: only allowed for writers or above from the same lab
						if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || (($currUserCategory <= $_SESSION["userCategoryNames"]["Writer"]) && ($currUserLab == $contLab)))
						{
							if ($modify_state)
							{
								?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="SUBMIT" style="font-size:9pt;" name="save_cont_storage" value="Save">&nbsp;&nbsp;<input type="BUTTON" style="font-size:9pt;" value="Cancel" onClick="window.location.href='<?php echo $_SERVER["PHP_SELF"] . "?View=3&Mod=" . $contID; ?>'"><?php
							}
							else
							{
								?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="SUBMIT" style="font-size:9pt;" name="edit_cont_location" value="Modify"><?php
							}
						}
	
						?><BR><a href="<?php echo $hostname . "Location.php?View=6&Sub=3&Mod=" . $contID; ?>" style="font-weight:normal; font-size:9pt;">Back to Container</a>
						<HR>
					</td>
				</TR>

				<TR>
					<TD style="font-weight:bold; width:150px; border-left:1px solid black; border-bottom:1px solid black;">
						Storage name <font size="3" face="Helvetica" color="FF0000"><b>*</b></font>
					</TD>

					<TD style="border-left:1px solid black; border-right:1px solid black; border-bottom:1px solid black;"><?php

						if ($modify_state)
							echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"34\" name=\"storage_name\" ID=\"locationName\" value=\"" . $storageName . "\">";
						else
							echo $storageName;
					?></TD>
				</TR>

				<TR>
					<TD style="font-weight:bold; border-left:1px solid black; border-bottom:1px solid black;">
						Storage type <font size="3" face="Helvetica" color="FF0000"><b>*</b></font>
					</TD>

					<TD style="border-left:1px solid black; border-right:1px solid black; border-bottom:1px solid black;">
						<?php

							if ($modify_state)
								$lfunc->printStorageTypes($storageType);
							else
								echo $storageType;
						?>
					</TD>
				</TR>

				<TR>
					<TD style="font-weight:bold; width:150px; border-left:1px solid black; border-bottom:1px solid black;">
						Address
					</TD>

					<TD style="border-left:1px solid black; border-right:1px solid black; border-bottom:1px solid black;">
						<?php 
							if ($modify_state)
								echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"34\" name=\"storage_address\" value=\"" . $address . "\">";
							else
								echo $address;
						?>
					</TD>
				</TR>

				<TR>
					<TD style="font-weight:bold; border-left:1px solid black; border-bottom:1px solid black;">
						Shelf number
					</TD>

					<TD style="border-left:1px solid black; border-right:1px solid black; border-bottom:1px solid black;">
						<?php
							$mod_shelf = ($shelf != 0) ? $shelf : "";

							if ($modify_state)
								echo "<INPUT type=\"text\" ID=\"contShelf\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" name=\"cont_shelf\" value=\"" . $mod_shelf . "\">";
							else
								echo $mod_shelf;
						?>
					</TD>
				</TR>

				<TR>
					<TD style="border-left:1px solid black; font-weight:bold;">
						Rack
					</TD>

					<TD style="border-left:1px solid black; border-right:1px solid black; border-bottom:1px solid black;">
						<?php
							$mod_rack = ($rack != 0) ? $rack : "";

							if ($modify_state)
								echo "<INPUT type=\"text\" ID=\"contRack\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" name=\"cont_rack\" value=\"" . $mod_rack . "\">";
							else
								echo $mod_rack;
						?>
					</TD>
				</TR>

				<TR>
					<TD style="font-weight:bold; border-left:1px solid black;">
						Column
					</TD>

					<TD style="border-left:1px solid black; border-right:1px solid black; border-bottom:1px solid black;">
						<?php
							$mod_col = ($column!= 0) ? $column : "";

							if ($modify_state)
								echo "<INPUT type=\"text\" ID=\"contCol\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" name=\"cont_col\" value=\"" . $mod_col . "\">";
							else
								echo $mod_col;
						?>
					</TD>
				</TR>

				<TR>
					<TD style="font-weight:bold; border-left:1px solid black; border-bottom:1px solid black;">
						Row
					</TD>

					<TD style="border-bottom:1px solid black; border-right:1px solid black;  border-left:1px solid black;">
						<?php
							$mod_row = ($row != 0) ? $row : "";

							if ($modify_state)
								echo "<INPUT type=\"text\" ID=\"contRow\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" name=\"cont_row\" value=\"" . $mod_row . "\">";
							else
								echo $mod_row;
						?>
					</TD>
				</TR>
			</table>

			</FORM><?php
		
	}


	/**
	 * Print container modification form
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2007-09-19
	 *
	 * @param STRING $output_str Seems to be inactive in this function, keep for future updates
	 * @param INT $contID
	*/
	function printModifyContainer_Form($output_str, $contID)
	{
		global $conn;
		global $cgi_path;

		$lfunc = new Location_Funct_Class();

		$original_info_rs = mysql_query("SELECT * FROM `Container_tbl` WHERE `containerID`='" . $contID . "' AND `status`='ACTIVE'", $conn) or die("Error reading container original info in SQL statement: " . mysql_error());

		if ($original_info_ar = mysql_fetch_array($original_info_rs, MYSQL_ASSOC))
		{
			?>
			<FORM NAME="deleteContainerForm" METHOD="POST" ACTION="<?php echo $cgi_path . "location_request_handler.py";?>">
				<INPUT TYPE="hidden" NAME="delete_container">
				<INPUT TYPE="hidden" NAME="containerID" VALUE="<?php echo $contID; ?>">
			</FORM>

			<FORM method=post action="<?php /*echo $_SERVER["PHP_SELF"] . "?View=5&Mod=" . $contID . ""; */ echo $cgi_path . "location_request_handler.py"; ?>">

			<input type=hidden name="cont_id_hidden" value="<?php echo $contID; ?>">

			<table width="796px" cellpadding="5" style="border: 1px solid black;" frame="box" rules="all">
				<th colspan="3" style="text-align:center;">
					Update Container Information
				</th>

				<tr>
					<td style="font-size:9pt; white-space:nowrap;">
						Container Name
					</td>
	
					<td style="padding-left:10px; padding-right:10px;">
						<INPUT style="font-size:9pt;" type="text" onKeyPress="return disableEnterKey(event);" size="25" value="<?php echo $original_info_ar["name"] ?>" name="cont_name_field">
					</td>
				</tr>

				<tr>
					<td>
						Container Type
					</td>
	
					<td style="padding-left:10px;">
					<?php 
						// Feb 9, Marina -- Disallow modification of occupied containers
						$size_rs  = mysql_query( "SELECT `maxCol`, `maxRow` FROM `ContainerTypeID_tbl` WHERE `contTypeID`='" . $original_info_ar["contTypeID"] . "' AND `status`='ACTIVE'" , $conn ) or die("Error reading container original info in SQL statement: " . mysql_error());
					
						if ($size_ar = mysql_fetch_array( $size_rs, MYSQL_ASSOC ) )
						{
							$cols = $size_ar["maxCol"];
							$rows = $size_ar["maxRow"];
						}
					
						$capacity = $cols * $rows;

						$loc_func_obj = new Location_Funct_Class();
						$empty_cells = $loc_func_obj->getEmptyCellNum($contID, $original_info_ar["contTypeID"]);
						$occupied_cells = $capacity - $empty_cells;
	
						if (($occupied_cells) > 0)
						{
							// Don't allow modification of occupied container types or sizes
							echo "<select disabled name=\"cont_group_selection\">";
						}
						else
						{
							echo "<select name=\"cont_group_selection\" style=\"font-size:8pt;\">";
						}

						$group_list_rs = mysql_query("SELECT * FROM `ContainerGroup_tbl` WHERE `status`='ACTIVE'", $conn) or die("ERROR SQL#12: " . mysql_error());
						
						while ($group_list_ar = mysql_fetch_array($group_list_rs, MYSQL_ASSOC))
						{
							if ($group_list_ar["contGroupID"] == $original_info_ar["contGroupID"])
							{
								echo "<option selected>" . $group_list_ar["contGroupName"] . "</option>";
							}
							else
							{
								echo "<option>" . $group_list_ar["contGroupName"] . "</option>";
							}
						}
					?>
						</select>
					</td>
	
					<td style="font-size:9pt;">
						Please select a container type from the list provided.  If  the desired type is not in the list, please <a href="<?php echo $hostname . "contacts.php"; ?>" style="font-size:9pt;">contact the Administrator</a> to create it.
					</td>
				</tr>
			
				<tr>
					<td>
						Container Size
					</td>
	
					<td style="padding-left:7px;">
					<?php
	
					if (($occupied_cells) > 0)
					{
						// Don't allow modification of occupied container types or sizes
						echo "<select disabled name=\"cont_size_selection\" style=\"font-size:8pt;\">";
					}
					else
					{
						echo "<select name=\"cont_size_selection\" style=\"font-size:8pt;\">";
					}
		
					$type_list_rs = mysql_query("SELECT * FROM `ContainerTypeID_tbl` WHERE `status`='ACTIVE'", $conn) or die("ERROR: " . mysql_error());

					while( $type_list_ar = mysql_fetch_array( $type_list_rs, MYSQL_ASSOC ) )
					{
						if( $type_list_ar["contTypeID"] == $original_info_ar["contTypeID"] )
						{
							echo "<option selected>" . $type_list_ar["containerName"] . "</option>";
						}
						else
						{
							echo "<option>" . $type_list_ar["containerName"] . "</option>";
						}
					}
					?>
					</select>
					</td>
	
					<td style="font-size:9pt;">
						Please select a container size from the list provided.  If the desired size is not in the list, please add it using the <a href="<?php echo $_SERVER["PHP_SELF"] . "?View=6&Sub=1"; ?>" style="font-size:9pt;">Add container sizes</a> menu item.
					</td>
				</tr>
	
				<tr>
					<td>
						Description:
					</td>
		
					<td style="padding-left:7px;  padding-right:10px;">
						<INPUT type="text" onKeyPress="return disableEnterKey(event);" style="font-size:9pt;" size="35" value="<?php echo $original_info_ar["container_desc"]; ?>" name="cont_desc_field">
					</td>
	
					<td></td>
				</tr>
		
				<!-- Lab - Added Sept. 19/07 -->
				<tr>
					<td>
						Laboratory:
					</td>
		
					<td style="padding-left:7px;">
					<?php
						$lfunc->printLabList($original_info_ar["labID"]);
					?>
					</td>
	
					<td>
						Please select from the dropdown list
					</td>
				</tr>
		
				<tr>
					<td colspan="3">
						<input type="submit" name="cont_modify_button" value="Submit" onClick="enableSelect();">&nbsp;<?php
	
						// Jan. 3, 2010 - Let Admin delete plate IFF EMPTY
						$currUserID = $_SESSION["userinfo"]->getUserID();
						$currUserCategory = $_SESSION["userinfo"]->getCategory();
						$userLab = $_SESSION["userinfo"]->getLabID();
						$contLab = $original_info_ar["labID"];
			
						// check for empty is kinda redundant, since Modification page can now be accessed IFF container empty, but keep for consistency.  Check for user lab is required.
						if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || (($currUserCategory <= $_SESSION["userCategoryNames"]["Writer"]) && ($contLab == $userLab)))
						{
							?><input type="button" style="font-size:9pt;" name="delete_container_button" value="Delete Container" onClick="deleteContainer('<?php echo $contID; ?>')"><?php
						}
						else
						{
							?><input type="button" style="font-size:9pt;" DISABLED value="Delete Container"><?php
						}

						?><input type="button" style="font-size:9pt;" value="Cancel" onClick="window.location.href='<?php echo $_SERVER["PHP_SELF"] . "?View=2&Mod=" . $contID; ?>'">
					</TD>
				</tr>
			</table>

			</FORM><?php
		}
	}
	
	
	/**
	 * Update container information
	 *
	 * Modified Feb 9, 2006 by Marina, to account for cases where container group and size were unchanged
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING $output_str Seems to be inactive in this function, keep for future updates
	 * @param INT $contID
	*/
	function updateContainerInfo($postvars)
	{
		global $conn;
		$lfunc = new Location_Funct_Class();

		$group_changed = false;
		$type_changed = false;

		if (isset($postvars["cont_id_hidden"]))
		{
			$contID_tmp = $postvars["cont_id_hidden"];
		
			// May 31/06, Marina - Get the container's CURRENT information - mostly care about the group for group count update
			$curr_rs = mysql_query("SELECT * FROM `Container_tbl` WHERE `containerID`='" . $contID_tmp . "' AND `status`='ACTIVE'");

			if ($curr_ar = mysql_fetch_array($curr_rs, MYSQL_ASSOC))
			{
				// May not require all those fields now, but good to retrieve them all in case need later
				$curr_name = $curr_ar["name"];
				$curr_desc = $curr_ar["container_desc"];
				$curr_group = $curr_ar["contGroupID"];
				$curr_group_count = $curr_ar["contGroupCount"];
			}
	
			$name_tmp = /*addslashes( */$postvars["cont_name_field"] /*)*/;
			$desc_tmp = /*addslashes( */$postvars["cont_desc_field"] /*)*/;

			if (isset($postvars["cont_group_selection"]))
			{
				// Updated May 31/06 by Marina
				$contGroupID_tmp = $_SESSION["Container_Name_ID"][ $postvars["cont_group_selection"] ];

				if ($contGroupID_tmp != $curr_group)	// may 31/06
				{
					// only in this case has the group changed!!
					$group_changed = true;	
				}
			}

			// June 5/06, Marina -- Instead of relying on the user to select the isolate active state of the container, determine it based on the container's new group type:
			$isolate_rs = mysql_query ("SELECT `isolate_active` FROM `Container_tbl` WHERE `contGroupID`='" . $contGroupID_tmp . "' AND `status`='ACTIVE'", $conn) or die("Error selecting container's isolate active state: " . mysql_error());

			if ($isolate_ar = mysql_fetch_array($isolate_rs, MYSQL_ASSOC))
			{
				$isolate_tmp = $isolate_ar["isolate_active"];
			}
			else
			{
				// default to No
				$isolate_tmp = "NO";
			}


			if (isset($postvars["cont_size_selection"]))
			{
				$size_changed = true;
				$contTypeID_tmp = $lfunc->convertContTypeName_to_ID( $postvars["cont_size_selection"] );
			}
			
			// June 5, Marina - Moved isolate change into group change section; only update name and description here
			mysql_query("UPDATE `Container_tbl` SET `name`='" . $name_tmp . "', `container_desc`='" . $desc_tmp . "' WHERE `containerID`='" . $contID_tmp . "' AND `status`='ACTIVE'", $conn) or die("Error in updating SQL statement: " . mysql_error());
			
			if ($group_changed == true)
			{
				// May 30/06, Marina - Update group COUNT, so that, if you change container type from Vector into Cell Line and there are 40 Vector containers and only 3 Cell Lines, you don't end up with a list of Cell Line containers whose numbers go 1, 2, 3, 40
				// It needs to be done first, before changing the group type - otherwise, if the old group count was high, moving into a group with fewer entries would still result in an incorrect ID
				// Count the number of containers in the new group and set the new container's groupCount to that number
				$find_groupnum_rs = mysql_query("SELECT MAX(`contGroupCount`) AS nextnum FROM `Container_tbl` WHERE `contGroupID`='" . $contGroupID_tmp . "'AND `status`='ACTIVE'", $conn ) or die( "Error in updating SQL statement: " . mysql_error() );

				if( $find_groupnum_ar = mysql_fetch_array( $find_groupnum_rs, MYSQL_ASSOC ) )
				{
					mysql_query("UPDATE `Container_tbl` SET `contGroupCount`='" . ($find_groupnum_ar["nextnum"] + 1) . "' WHERE `containerID`='" . $contID_tmp . "' AND `status`='ACTIVE'", $conn) or die("Error in updating SQL statement: " . mysql_error());
				}

				mysql_query("UPDATE `Container_tbl` SET `contGroupID`='" . $contGroupID_tmp . "' WHERE `containerID`='" . $contID_tmp . "' AND `status`='ACTIVE'", $conn) or die( "Error in updating SQL statement: " . mysql_error() );

				// June 5/06:
				// Update container PROPERTIES, so when a Vector plate is changed into cell line, it displays the right properties
				$lfunc->updateContainerProperties($contID_tmp, $contGroupID_tmp, $isolate_tmp);
			}

			if ($size_changed == true)
			{
				mysql_query("UPDATE `Container_tbl` SET `contTypeID`='" . $contTypeID_tmp . "' WHERE `containerID`='" . $contID_tmp . "' AND `status`='ACTIVE'", $conn) or die("Error in updating SQL statement: " . mysql_error());
			}

			return "Container successfully updated!";
		}
		
		return "Error in trying to update the container!";
		
		unset( $lfunc );
	}
}
?>