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
 * This class handles functions related to wells (1-prep slots) in container
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
class Location_Well_Class
{
	/**
	 * Default constructor
	*/
	function Location_Well_Class()
	{}
	
	/**
	 * Fetch the internal database ID of a container SIZE from its name
	 *
	 * Last modified by Marina on Sept 10, 2007
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param Array $postvars
	 * @param INT $contID
	 * @param boolean $err
	 *
	 * @return boolean TRUE on successful creation, FALSE on error
	*/
	function create_wells($postvars, $contID, $err=false)
	{
		global $conn;

		$lfunc_obj = new Location_Funct_Class();
		$gfunc_obj = new generalFunc_Class();
		
		$well_occupied = false;
		$wellUnavailable = false;
		$submit_btn = false;
		$isoActiveCont = false;

		// Feb. 9, 2010
		$selContTypeName = $lfunc_obj->getContainerGroupName($contID);
		$selContType = $_SESSION["Container_Name_ID"][$selContTypeName];

		if (isset( $_SESSION["userinfo"] ) )
		{
			$currentUserID = $_SESSION["userinfo"]->getUserID();
			$currUserCategory = $_SESSION["userinfo"]->getCategory();
			$userProjects = getUserProjectsByRole($currentUserID, 'Writer');
		}

		if ($err)
		{
			// Return the form with error output
			?>
			<form METHOD="POST" ACTION="<?php echo $_SERVER["PHP_SELF"] . "?View=7&Mod=" . $contID . ""; ?>">
				<table border="1" frame="box" rules="all" valign="middle" cellpadding="3">
					<th colspan="100%" class="detailedView_heading"><?php echo $lfunc_obj->getContainerName($contID); ?>
						<BR><span style="font-size:8pt; font-weight:bold; color:red;"><IMG SRC="pictures/new01.gif" ALT="new" WIDTH="35" HEIGHT="20" style="cursor:auto;">You may update multiple wells at once by clicking on the property's column heading and typing its value in the pop-up box.</span>
					</th>
				
					<tr>
						<td style="width:5%; font-weight:bold; text-align:center; font-size:7pt; color:navy; text-align:center;">Well</td>

						<td style="font-weight:bold; padding-left:4px; padding-right:4px; text-align:center; font-size:7pt; font-weight:bold; color:navy; white-space:nowrap; cursor:pointer;" onClick="popupWellAttrUpdateForm('OpenFreezer ID');">OpenFreezer ID</td>
						<?php
							// Correction June 8, 2011							
							/*
							$isoAct_state = $lfunc_obj->isIsoActive($contID);

							if ($isoAct_state)*/

							$isoAct_state = $lfunc_obj->isIsoActive( $contID );
							$_SESSION["isoAct_state"] = $isoAct_state;
			
							// Added by Marina on July 25, 2005; MOVED UP HERE ON AUG 31, 2006
							if ($isoAct_state == "YES")
							{
								$isoAct_state = true;
								$isIsolate_container = true;
							}
							else
							{
								$isoAct_state = false;
								$isIsolate_container = false;
							}

							if ($isIsolate_container) 
							{
								?>
								<input type="hidden" name="well_limsid_isoAct_hidden" value="YES">
								<td style="font-weight:bold; padding-left:4px; padding-right:4px; text-align:center; font-size:7pt; color:navy; cursor:pointer;" onClick="popupWellAttrUpdateForm('Isolate Number');">Isolate Number</td>
							
								<td style="font-weight:bold; text-align:center; font-size:7pt; color:navy; padding-left:7px; padding-right:7px; cursor:pointer;" onClick="checkUncheckAllIsolates();">Current Set Isolate</td>
								<?php
							}

						?><td style="font-weight:bold; text-align:center; font-size:7pt; color:navy; cursor:pointer;" onClick="popupWellAttrUpdateForm('Reference');">Reference</td>
						<td style="font-weight:bold; text-align:center; font-size:7pt; color:navy; width:50px; padding-left:5px; padding-right:5px; cursor:pointer;" onClick="checkUncheckAllFlags();">Flag</td>
						<td style="font-weight:bold; text-align:center; font-size:7pt; color:navy; cursor:pointer;" onClick="popupWellAttrUpdateForm('Comments');">Comments</td>
						<?php
							// General Container Properties
							$genContProps = array();

							$prep_gen_check_rs = mysql_query("SELECT p.elementTypeID, p.propertyName FROM PrepElemTypes_tbl p, ContainerTypeAttributes_tbl c WHERE c.containerTypeID='" . $selContType . "' AND c.containerTypeAttributeID=p.elementTypeID AND c.status='ACTIVE' AND p.status='ACTIVE' UNION (SELECT a.`prepElementTypeID`, b.`propertyName` FROM `Prep_Req_tbl` a INNER JOIN `PrepElemTypes_tbl` b ON a.`prepElementTypeID`=b.`elementTypeID` WHERE a.`containerID`='" . $contID . "' AND a.`requirement`='REQ' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE')") or die("Error fetching container attributes");

							while ($prep_gen_check_ar = mysql_fetch_array($prep_gen_check_rs, MYSQL_ASSOC))
							{
								// change Feb. 24/10: different column name returned!
								$genContProps[$prep_gen_check_ar["elementTypeID"]] = $prep_gen_check_ar["propertyName"];
							}

							foreach ($genContProps as $propID => $propName)
								echo "<td  style=\"font-weight:bold; text-align:center; cursor:pointer; font-size:7pt; color:navy;\" onClick=\"popupWellAttrUpdateForm('" . $propName . "');\">" . $propName . "</td>";
						?>	
					</tr>
					<?php

					// print POST values
					foreach ($postvars as $key => $value)
					{
						$pieces = explode( "|", $value );
						$wellRow = $pieces[0];		// get the row number of each well
						$wellCol = $pieces[1];		// get the column number of each well
						$general_info_ht = new HT_Class();
			
						?>
						<tr>
							<td style="font-weight:bold; color:#104E8B; width:5%; font-size:8pt; text-align:center;">
							<?php 
								// Coordinates
								echo strtoupper($lfunc_obj->getLetterRow($wellRow)) . ":" . $wellCol; 
							?>
							</td>

							<td name="limsID_td[]">
                                <input type="text" size="8" class="locationText typeahead" name="well_LIMSID_field[]" value="<?php echo strtoupper($_POST["well_LIMSID_field"][$key]); ?>" onKeyPress="return disableEnterKey(event);" data-provide="typeahead" / >
							</td>

							<?php 
								// Isolate # if exists
								if ($isoAct_state)
								{
									// Isolate number
									?>
									<td style="text-align:center" name="isoNum_td[]">
										<input type="text" class="locationText" size="5" style="padding-left:5px; padding-right:5px;" name="well_isoNum<?php echo $key; ?>_textfield" value="<?php echo $_POST["well_isoNum" . $key . "_textfield"]; ?>" onKeyPress="return disableEnterKey(event);">
									</td>

									<td style="text-align:center">
										<?php
											// Current set isolate?
											if (isset($_POST["well_mod_beingUsed" . $key . "_checkbox"])) 
											{
												?>
												<input type="checkbox" name="well_beingUsed<?php echo $key; ?>_checkbox" checked value="YES"></input>
												<?php
											}
											else 
											{
												?>
												<input type="checkbox" name="well_beingUsed<?php echo $key; ?>_checkbox" value="NO"></input>
												<?php
											}
										?>
									</td>
									<?php
								}
							?>
							<td name="Reference_td[]">
								<input type="text" class="locationText" size="12" style="padding-left:5px; padding-right:5px;" name="well_refAvail_text[]" value="<?php echo $_POST["well_refAvail_text"][$key]; ?>" onKeyPress="return disableEnterKey(event);">
							</td>

							<td style="text-align:center">
							<?php
								// Flag
								if (isset($_POST["well_flag" . $key . "_checkbox"]))
								{
									?>
										<input type="checkbox" name="well_flag<?php echo $key; ?>_checkbox" CHECKED value="YES">
									<?php
								}
								else
								{
									?>
										<input type="checkbox" name="well_flag<?php echo $key; ?>_checkbox" value="NO">
									<?php
								}
							?>
							</td>

							<td name="Comments_td[]">
								<input type="text" class="locationText" size="20" style="padding-left:5px; padding-right:5px;" name="well_comments_text[]" value="<?php echo $_POST["well_comments_text"][$key]; ?>" onKeyPress="return disableEnterKey(event);">
							</td>
							<?php
								// finish off general properties
								foreach ($genContProps as $propID => $propName)
								{
									//echo $propID . ", ";

									echo "<td style=\"text-align:center\" name=\"" . $propName . "_td[]\">";
									echo "<INPUT TYPE=\"TEXT\" class=\"locationText\" style=\"padding-left:5px; padding-right:5px;\" onKeyPress=\"return disableEnterKey(event);\" name=\"well_mod_general" . $propID . "_text[]\" value=\"";
									echo $_POST["well_mod_general" . $propID . "_text"][$key];
									echo "\">";
									echo "</td>";
								}
							?>
							</tr>
							<?php
						// Now pull up the project ID again and issue an error if user can't write to it - only way
						$tmp_lims_id = $gfunc_obj->get_rid($_POST["well_LIMSID_field"][$key]);
						$projectID = getReagentProjectID($tmp_lims_id);

						if (($tmp_lims_id > 0) && ($currUserCategory != $_SESSION["userCategoryNames"]["Admin"]) && !in_array($projectID, $userProjects))
						{
							// print error message
							echo "<tr>";
							echo "<td style=\"font-weight:bold; color:#104E8B; width:100px; font-size:8pt; padding-left:10px; padding-right:10px;\"></td>";
							echo "<td colspan=\"100%\" style=\"color:#FF0000; font-weight:bold; font-size:7pt; padding-left:5px;\">";
							echo "You may not insert <span style=\"color:#0000FF\">" .  strtoupper($_POST["well_LIMSID_field"][$key]) . "</span> into this well, since you do not have Write access to its project.  Please contact the project owner to obtain permission.";
							echo "</td>";
							echo "</tr>";
						}
					}

					?><TR><TD colspan="100%" style="padding-top:15px; padding-left:10px; color:#0101DE; font-size:9pt;"><HR><P><B><U>Note</U>: Only preps of the following reagent types may be stored in this container; other prep types are not allowed:</B><BR><BR>
		
					<TABLE style="width:200px;" cellpadding="2">
						<TR>
							<TD style="text-decoration:underline; padding-left:5px; font-size:9pt; color:green; font-weight:bold;">Name</TD>
							<TD style="text-decoration:underline; color:green; font-size:9pt; font-weight:bold;">Prefix</TD>
						</TR>
					<?php
		
					$allowed_r_types = $lfunc_obj->getContainerReagentTypes($selContType);
		
					foreach ($allowed_r_types as $key => $value)
					{
						echo "<TR>";
							echo "<TD style=\"padding-left:5px; color:#0202DD;\">" . $_SESSION["ReagentType_ID_Name"][$value] . "</TD>";
							echo "<TD style=\"padding-left:5px; color:#0202DD;\">" . $_SESSION["ReagentType_ID_Prefix"][$value] . "</TD>";
		
							echo "<INPUT TYPE=\"hidden\" ID=\"rTypes_allowed[]\" NAME=\"allowed_prep_prefixes[]\" VALUE=\"" . $_SESSION["ReagentType_ID_Prefix"][$value] . "\">";
						echo "</TR>";
					}
		
					// list of ALL prefixes
					foreach ($_SESSION["ReagentType_ID_Prefix"] as $rTypeID => $rTypePrefix)
					{
						// Feb. 9, 2010
						echo "<INPUT TYPE=\"hidden\" NAME=\"all_prefixes[]\" value=\"" . $rTypePrefix . "\">";
					}

					?></table></td></tr>

					<tr>
						<td>&nbsp;</td>
						<td style="text-align:center"><input type="submit" name="well_limsid_submit_button" onClick="return checkPrepType() && checkExistingPrepID();" value="SUBMIT"></td>
						<td colspan="11"><input type="submit" name="well_create_cancel_button" value="CANCEL"></td>
					</tr>
				</table>
			</form>
			<?php
		}
		elseif (isset($postvars["wells_checkbox"]))	// added by Marina on August 1, 2005
		{
			// Arrays to store coordinates of wells (if multiple selected)
			$wellRow_ar = array();
			$wellCol_ar = array();
			
			$interWellRow_ar = array();
			$interWellCol_ar = array();
			
			$finalWellRow_ar = array();
			$finalWellCol_ar = array();
			
			$goodRow_ar = array();
			$goodCol_ar = array();

			$badRow_ar = array();
			$badCol_ar = array();
			
			$reservedRow_ar = array();
			$reservedCol_ar = array();

			$well_occupied = false;

			foreach( $postvars["wells_checkbox"] as $key => $value )
			{
				$pieces = explode( "|", $value );
				$wellRow_ar[ $key ] = $pieces[ 0 ];	// get the row number of each well
				$wellCol_ar[ $key ] = $pieces[ 1 ];	// get the column number of each well
			}
			
			$_SESSION["wells_checkbox_tmp"] = $postvars["wells_checkbox"];			

			$bad_tmp_count = 0;
			$good_tmp_count = 0;
			$temp_count = 0;

			// -----------------------------------------------
			// General Property Section of the Modify Function
			// Updated Oct 12/05 Added by John Paul Lee
			$general_info_ht = new HT_Class();

			// Feb. 24/10, Marina: CHANGE
			$prep_gen_check_rs = mysql_query("SELECT p.elementTypeID, p.propertyName FROM PrepElemTypes_tbl p, ContainerTypeAttributes_tbl c WHERE c.containerTypeID='" . $selContType . "' AND c.containerTypeAttributeID=p.elementTypeID AND c.status='ACTIVE' AND p.status='ACTIVE' UNION (SELECT a.`prepElementTypeID`, b.`propertyName` FROM `Prep_Req_tbl` a INNER JOIN `PrepElemTypes_tbl` b ON a.`prepElementTypeID`=b.`elementTypeID` WHERE a.`containerID`='" . $contID . "' AND a.`requirement`='REQ' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE')") or die("Error fetching container attributes");
	
			$req_set = "('";
			$req_setcount = 0;
			$general_req_ar = array();
		
			while ($prep_gen_check_ar = mysql_fetch_array($prep_gen_check_rs, MYSQL_ASSOC))
			{
				$req_setcount++;

				$req_set = $req_set . $prep_gen_check_ar["elementTypeID"] . "','";
				$general_req_ar[ $prep_gen_check_ar["propertyName"] ] = $prep_gen_check_ar["elementTypeID"];
			}
				
			$req_set = $this->reset_set( $req_setcount, $req_set );

			// Finding actual general properties for container
			foreach( $goodRow_ar as $wellID_tmp => $wellSpot_tmp )
			{
				$general_tmp_ar = array();

				$prep_tmp_ar = $well_info_ht->get($wellID_tmp);
				$prepID_tmp = $prep_tmp_ar["prepID"];
				
				$find_gen_prop_rs = mysql_query("SELECT a.`prepID`, a.`elementTypeID`, a.`value`, b.`propertyName` FROM `PrepElementProp_tbl` a INNER JOIN `PrepElemTypes_tbl` b ON a.`elementTypeID`=b.`elementTypeID` WHERE a.`prepID`='" . $prepID_tmp . "' AND b.`elementTypeID` IN " . $req_set . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn) or die("FAILURE IN GENERAL PROPERTY SEARCH SQL: " . mysql_error());
				
				while( $find_gen_prop_ar = mysql_fetch_array( $find_gen_prop_rs, MYSQL_ASSOC ) )
				{
					//$general_tmp_ar["propertyName"] = $find_gen_prop_ar["propertyName"];
					//$general_tmp_ar["value"] = $find_gen_prop_ar["value"];
					
					$general_tmp_ar[ $find_gen_prop_ar["propertyName"] ] = $find_gen_prop_ar["value"];
					
				}
				
				$general_info_ht->add( $prepID_tmp, $general_tmp_ar );
			}

			// End section for general property 
			// -----------------------------------------------

			?><form method=post action="<?php echo $_SERVER["PHP_SELF"] . "?View=7&Mod=" . $contID . ""; ?>">
			<table style="min-width:100%" border="1" frame="box" rules="all" valign="middle" cellpadding="3">
				<th colspan="100%" class="detailedView_heading"><?php echo $lfunc_obj->getContainerName($contID); ?>				
					<BR><span style="font-size:8pt; font-weight:bold; color:red;"><IMG SRC="pictures/new01.gif" ALT="new" WIDTH="35" HEIGHT="20" style="cursor:auto;">You may update multiple wells at once by clicking on the property's column heading and typing its value in the pop-up box.</span>
				</th>
				
				<tr>
					<td style="font-weight:bold; text-align:center; font-size:7pt; font-weight:bold; color:navy; padding-left:7px; padding-right:7px;">Well</td>

					<td style="font-weight:bold; padding-left:4px; padding-right:4px; text-align:center; font-size:7pt; font-weight:bold; color:navy; white-space:nowrap; cursor:pointer;" onclick="popupWellAttrUpdateForm('OpenFreezer ID');">OpenFreezer ID</td>
					<?php
						// 1. Check if this is an isolate active container
						$lfunc_obj = new Location_Funct_Class();
						$isoAct_state = $lfunc_obj->isIsoActive( $contID );
						$_SESSION["isoAct_state"] = $isoAct_state;
		
						// Added by Marina on July 25, 2005; MOVED UP HERE ON AUG 31, 2006
						if ($isoAct_state == "YES")
						{
							$isoAct_state = true;
							$isIsolate_container = true;
						}
						else
						{
							$isoAct_state = false;
							$isIsolate_container = false;
						}

						if ($isIsolate_container) 
						{
							?><td style="font-weight:bold; padding-left:4px; padding-right:4px; text-align:center; font-size:7pt; color:navy; cursor:pointer;" onclick="popupWellAttrUpdateForm('Isolate Number');">Isolate Number</td>

							<td width="10%" style="font-weight:bold; text-align:center; font-size:7pt; color:navy; padding-left:7px; padding-right:7px; cursor:pointer;" onclick="checkUncheckAllIsolates();">Current Set Isolate</td><?php
						}
					?>
					<td style="font-weight:bold; text-align:center; font-size:7pt; color:navy; cursor:pointer;" onclick="popupWellAttrUpdateForm('Reference');">Reference</td>

					<td style="font-weight:bold; text-align:center; font-size:7pt; color:navy; width:50px; padding-left:5px; padding-right:5px;cursor:pointer;" onClick="checkUncheckAllFlags();">Flag</td>

					<td style="font-weight:bold; text-align:center; font-size:7pt; color:navy; cursor:pointer;" onclick="popupWellAttrUpdateForm('Comments');">Comments</td>
					<?php
						// FIX IT: Updated Oct 12/05
						// For the general properties of each container
						foreach( $general_req_ar as $pname => $pid )
						{
							echo "<td style=\"font-weight:bold; text-align:center; cursor:pointer; font-size:7pt; color:navy;\" onClick=\"popupWellAttrUpdateForm('" . str_replace("'", "\'", $pname) . "');\">";
							echo $pname;
							echo "</td>";
						}
					?>
				</tr>
			<?php

			foreach ($wellRow_ar as $key => $rowvalue)	// ==> for each <key, value> pair in $wellRow_ar (associative array)
			{
				// Check if the well is reserved
				$check_well_reserved_rs = mysql_query( "SELECT * FROM `Wells_tbl` WHERE `containerID`='" . $contID . "' AND `wellRow`='" . $rowvalue . "' AND `wellCol`='" . $wellCol_ar[$key] . "' AND `status`='ACTIVE' AND `reserved`='TRUE'", $conn) or die("ERROR IN CHECKING WELL SQL STATEMENT: " . mysql_error());

				if ($check_well_reserved_ar = mysql_fetch_array($check_well_reserved_rs, MYSQL_ASSOC))
				{
					// Well is reserved

					// if a well is reserved, it cannot be occupied
					$well_occupied = false;

					// Check to see who reserved it
					$tmp_creator = $check_well_reserved_ar["creatorID"];
					
					// Cannot modify a well reserved by someone else
					if ($tmp_creator != $currentUserID)
					{
						$wellUnavailable = true;
						$query = "SELECT `description` FROM `Users_tbl` WHERE `userID`='" . $tmp_creator . "' AND `status`='ACTIVE'";
						$resultset = mysql_query($query, $conn);

						if ($result_ar = mysql_fetch_array($resultset))
						{
							// Feb 7, Marina - Use full names instead of aliases
							$username = $result_ar["description"];
						}
						else	// default
						{
							// June 17, 2010: Well might be reserved by a DEPd user too!
							$dep_rs = mysql_query("SELECT `description` FROM `Users_tbl` WHERE `userID`='" . $tmp_creator . "'");

							if ($dep_ar = mysql_fetch_array($dep_rs))
							{
								$username = $dep_ar["description"];
							}
						}

						$badRow_ar[ $bad_tmp_count ] = $wellRow_ar[ $key ];
						$badCol_ar[ $bad_tmp_count ] = $wellCol_ar[ $key ];
						$bad_tmp_count++;
					}
					else
					{
						$wellUnavailable = false;

						// it's a good row
						$goodRow_ar[ $good_tmp_count ] = $wellRow_ar[ $key ];
						$goodCol_ar[ $good_tmp_count ] = $wellCol_ar[ $key ];
						$good_tmp_count++;
					}
				}
				else	// well not reserved
				{
					// Check if the well is in the table but is not reserved
					$check_well_reserved_rs = mysql_query("SELECT * FROM `Wells_tbl` WHERE `containerID`='" . $contID . "' AND `wellRow`='" . $rowvalue . "' AND `wellCol`='" . $wellCol_ar[ $key ] . "' AND `status`='ACTIVE' AND `reserved`='FALSE'", $conn) or die("ERROR IN CHECKING WELL SQL STATEMENT: " . mysql_error());

					// If found, check if the well is occupied
					if ($check_well_reserved_ar = mysql_fetch_array($check_well_reserved_rs, MYSQL_ASSOC))
					{
						$check_well_occupied_rs = mysql_query( "SELECT b.`prepID`, b.`isolate_pk`, b.`refAvailID`, b.`flag`, b.`comments`, a.`wellID` FROM `Wells_tbl` a INNER JOIN `Prep_tbl` b ON a.`wellID`=b.`wellID` WHERE a.`wellRow`='" . $rowvalue . "' AND a.`wellCol`='" . $wellCol_ar[ $key ] . "' AND a.`containerID`='" . $contID . "' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn ) 
						or die( "ERROR IN CHECKING WELL SQL STATEMENT: " . mysql_error() );

						// If not occupied, may reserve and update it
						$check_well_occupied_ar = mysql_fetch_array( $check_well_occupied_rs, MYSQL_ASSOC );

						if($check_well_occupied_ar)
						{
							$well_occupied = true;
							$wellUnavailable = false;

							$badRow_ar[ $bad_tmp_count ] = $wellRow_ar[ $key ];
							$badCol_ar[ $bad_tmp_count ] = $wellCol_ar[ $key ];
							$bad_tmp_count++;
						}
						else
						{
							// Update the well's reservation status and creator ID
							$well_occupied = false;
							$wellUnavailable = false;

							$result1 = mysql_query("UPDATE `Wells_tbl` SET `reserved`='TRUE' WHERE `containerID`='" . $contID . "' AND `wellRow`='" . $rowvalue . "' AND `wellCol`='" . $wellCol_ar[ $key ] . "' AND `status`='ACTIVE'", $conn);

							$result2 = mysql_query("UPDATE `Wells_tbl` SET `creatorID`='" . $currentUserID . "' WHERE `containerID`='" . $contID . "' AND `wellRow`='" . $rowvalue . "' AND `wellCol`='" . $wellCol_ar[ $key ] . "' AND `status`='ACTIVE'", $conn);	

							$goodRow_ar[ $good_tmp_count ] = $wellRow_ar[ $key ];
							$goodCol_ar[ $good_tmp_count ] = $wellCol_ar[ $key ];
							$good_tmp_count++;
						}
					}
					else
					{
						// Create a new entry in the Wells table for this well
						$well_occupied = false;
						$wellUnavailable = false;

						$well_rs = mysql_query("INSERT INTO `Wells_tbl` (`wellID`, `containerID`, `wellCol`, `wellRow`, `reserved`, `creatorID`) VALUES ('', '" . $contID . "', '" . $wellCol_ar[ $key ] . "', '" . $rowvalue . "', 'TRUE', '" . $currentUserID . "')", $conn) or die( "FAILED TO CREATE WELL LOCATION!" . mysql_error() );	
						
						$wellID_ar[ $key ] = mysql_insert_id( $conn );

						$goodRow_ar[ $good_tmp_count ] = $wellRow_ar[ $key ];
						$goodCol_ar[ $good_tmp_count ] = $wellCol_ar[ $key ];
						$good_tmp_count++;
					}
				}
				
				// Store well row and column information for next processing stage
				$interWellRow_ar[ $key ] = $wellRow_ar[ $key ];
				$interWellCol_ar[ $key ] = $wellCol_ar[ $key ];
				
				$_SESSION["interWellRow_ar"] = $interWellRow_ar;
				$_SESSION["interWellCol_ar"] = $interWellCol_ar;
				$_SESSION["badRow_ar"] = $badRow_ar;
				$_SESSION["badCol_ar"] = $badCol_ar;
	
				$_SESSION["goodRow_ar"] = $goodRow_ar;
				$_SESSION["goodCol_ar"] = $goodCol_ar;

				if ((!$well_occupied) && (!$wellUnavailable))
				{
					?>
					<tr>
						<td style="display:none"><input type="checkbox" name="wells_release_checkbox[]" id="well_release_checkbox[]" value="NO"></td>
						<td style="font-weight:bold; color:#104E8B; width:50px; font-size:8pt; text-align:center;"><?php echo $lfunc_obj->getLetterRow($rowvalue) . ":" . $wellCol_ar[$key]?></td>

                        <td name="limsID_td[]" style="padding-left:7px;"><input type="text" size="7" class="locationText typeahead" name="well_LIMSID_field[]" value="" onKeyPress="return disableEnterKey(event);" data-provide="typeahead"></td>
                        <?php

						if ($isIsolate_container)
						{
							echo "<input type=\"hidden\" name=\"well_limsid_isoAct_hidden\" value=\"YES\">\n";	
							
							?><td name="isoNum_td[]"><input type="text" class="locationText" size="5" style="padding-left:5px; padding-right:5px;" onKeyPress="return disableEnterKey(event);" name="well_isoNum<?php echo $temp_count; ?>_textfield" id="well_isoNum<?php echo $temp_count; ?>_textfield" value=""></td>

							<td style="text-align:center;"><input type="checkbox" name="well_beingUsed<?php echo $temp_count; ?>_checkbox" id="well_beingUsed<?php echo $temp_count; ?>_checkbox" value="YES"></td>
							<?php
						}

					?><td name="Reference_td[]"><input type="text" class="locationText" size="12" style="padding-left:5px; padding-right:5px;" onKeyPress="return disableEnterKey(event);" name="well_refAvail_text[]" value="&nbsp;"></td>

					<td style="text-align:center;"><input type="checkbox" name="well_flag<?php echo $temp_count; ?>_checkbox" value="YES"></td>

					<td  name="Comments_td[]"><input type="text" class="locationText" size="20" style="padding-left:5px; padding-right:5px;" onKeyPress="return disableEnterKey(event);" name="well_comments_text[]" value=""></td><?php

					// UPDATED: Oct 12/05
					// Added by: John Paul Lee
					// Re-integration of general properties that was removed.
					foreach( $general_req_ar as $pname => $pid )
					{
						echo "<td name=\"" . $pname . "_td[]\">";
						$general_tmp_ar = $general_info_ht->get( $prepID_tmp );
						echo "<input type=\"text\" class=\"locationText\" style=\"padding-left:5px; padding-right:5px;\" onKeyPress=\"return disableEnterKey(event);\" name=\"well_mod_general" . $pid . "_text[]\" value=\"";
						echo $general_tmp_ar[ $pname ];
						echo "\">";
						echo "</td>";
					}
					?>
					</tr>
					<?php

					$temp_count++;
					$submit_btn = true;
				}
				else
				{
					if ($well_occupied)
					{
						echo "<tr>";

						echo "<TD style=\"width:15%; font-weight:bold; font-size:8pt; color:red; text-align:center;\">" . $lfunc_obj->getLetterRow($wellRow_ar[$key]) . ":" . $wellCol_ar[$key] . "</TD>";

						echo "<td colspan=\"100%\" style=\"color:red; font-weight:bold; font-size:7pt; padding-left:5px;\">Well " . $lfunc_obj->getLetterRow($wellRow_ar[$key]) . ":" . $wellCol_ar[$key] . " is occupied. Please select a different well to populate.</td></tr>";
					}
					if ($wellUnavailable)
					{
						echo "<tr>";
						
						echo "<TD style=\"width:100px; font-weight:bold; font-size:8pt; color:red; padding-left:10px; padding-right:10px;\">" . $lfunc_obj->getLetterRow($wellRow_ar[$key]) . ":" . $wellCol_ar[$key] . "</TD>";

						echo "<td colspan=\"100%\" style=\"color:red; font-weight:bold; font-size:7pt; padding-left:5px;\">Well " . $lfunc_obj->getLetterRow($wellRow_ar[$key]) . ":" . $wellCol_ar[$key] . " has been reserved by " . $username . ".  Please select a different well to populate.</td></tr>";
					}
				}
			}	// end foreach

			?><TR><TD colspan="100%" style="padding-top:18px; padding-left:10px; color:#0101DE; font-size:9pt;"><B><U>Note</U>: Only preps of the following reagent types may be stored in this container; other prep types are not allowed:</B><BR>

			<TABLE style="width:200px;" cellpadding="2">
				<TR>
					<TD style="text-decoration:underline; padding-top:10px; padding-left:5px; font-size:9pt; color:green; font-weight:bold;">Name</TD>
					<TD style="text-decoration:underline; color:green; padding-top:10px; font-size:9pt; font-weight:bold;">Prefix</TD>
				</TR>
				<?php
	
				$allowed_r_types = $lfunc_obj->getContainerReagentTypes($selContType);
	
				foreach ($allowed_r_types as $key => $value)
				{
					echo "<TR>";
						echo "<TD style=\"padding-left:5px; color:#0202DD;\">" . $_SESSION["ReagentType_ID_Name"][$value] . "</TD>";
						echo "<TD style=\"padding-left:5px; color:#0202DD;\">" . $_SESSION["ReagentType_ID_Prefix"][$value] . "</TD>";
	
						echo "<INPUT TYPE=\"hidden\" ID=\"rTypes_allowed[]\" NAME=\"allowed_prep_prefixes[]\" VALUE=\"" . $_SESSION["ReagentType_ID_Prefix"][$value] . "\">";
					echo "</TR>";
				}
	
				// list of ALL prefixes
				foreach ($_SESSION["ReagentType_ID_Prefix"] as $rTypeID => $rTypePrefix)
				{
					// Feb. 9, 2010
					echo "<INPUT TYPE=\"hidden\" NAME=\"all_prefixes[]\" value=\"" . $rTypePrefix . "\">";
				}

			?></TABLE></TD></TR><?php

			if ($submit_btn == true)
			{
				// Output the submit button at the bottom of the table
				?>
				<tr>
					<td colspan="100%" style="padding-left:25px; text-align:left;"><input type="submit" name="well_limsid_submit_button" value="SUBMIT" onClick="return checkPrepType() && checkExistingPrepID();">&nbsp;&nbsp;<input type="submit" name="well_create_cancel_button" value="CANCEL"></td>
				</tr>
				</table>
				</form>
                <script src="js/openfreezer.js">
                </script>
				<?php
			}
			else
			{
				// Output an OK button
				// (or Javascript - think about it)
				?>
				<tr>
					<td colspan="100%"><FONT SIZE="4"><B><CENTER><input type="submit" name="mod_empty_well_cancel" value="OK"></CENTER></B></FONT></td>
				</tr>
				</table>
                </form>
				<?php
			}
		}	// end if isset wells_checkbox
		else
		{
			if (!$error_state)
			{
				?><CENTER><FORM METHOD=POST ACTION="<?php echo $_SERVER["PHP_SELF"] . "?View=7&Mod=" . $contID . ""; ?>">
				&nbsp;&nbsp;&nbsp;&nbsp;<P><B>No well has been selected.  Please select a well to populate.</B><BR><BR>		
				&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE="submit" name="mod_empty_well_cancel" value="OK">
				</FORM></CENTER>
				<?php
				

				return false;
			}
		}

		unset( $postvars["wells_checkbox"], $count_tmp, $_SESSION["well_create_error_msg"]);
		return true;
	}


	/**
	 * Process first stage of well creation (adding a prep to an empty well)
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param Array $postvars
	 * @param INT $contID
	 *
	 * @return boolean TRUE on successful creation, FALSE on error
	*/
	function process_firstStage($postvars, $contID)
	{
		global $conn;

		$gfunc_obj = new generalFunc_Class();
		$lfunc_obj = new Location_Funct_Class();

		$selContTypeName = $lfunc_obj->getContainerGroupName($contID);
		$selContType = $_SESSION["Container_Name_ID"][$selContTypeName];

		// Get user info
		if( isset( $_SESSION["userinfo"] ) )
		{
			$currentUserID = $_SESSION["userinfo"]->getUserID();
			$currUserCategory = $_SESSION["userinfo"]->getCategory();
			$userProjects = getUserProjectsByRole($currentUserID, 'Writer');
		}

		$wellID_ar = array();
		$isoAct_state = false;		
		$temp_count = 0;
		$tmp_count = 0;

		$prep_gen_check_rs = mysql_query("SELECT p.elementTypeID, p.propertyName FROM PrepElemTypes_tbl p, ContainerTypeAttributes_tbl c WHERE c.containerTypeID='" . $selContType . "' AND c.containerTypeAttributeID=p.elementTypeID AND c.status='ACTIVE' AND p.status='ACTIVE' UNION (SELECT a.`prepElementTypeID`, b.`propertyName` FROM `Prep_Req_tbl` a INNER JOIN `PrepElemTypes_tbl` b ON a.`prepElementTypeID`=b.`elementTypeID` WHERE a.`containerID`='" . $contID . "' AND a.`requirement`='REQ' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE') order by elementTypeID") or die("Error fetching container attributes");

		$general_req_ar = array();
			
		while( $prep_gen_check_ar = mysql_fetch_array( $prep_gen_check_rs, MYSQL_ASSOC ) )
		{
			$general_req_ar[ $prep_gen_check_ar["propertyName"] ] = $prep_gen_check_ar["elementTypeID"];		
		}

		if( $postvars["well_limsid_isoAct_hidden"] == "YES" )
		{
			$isoAct_state = true;
		}

		// Sept. 10/07: Check project ID of input reagents.  Do not proceed until ALL input values are valid - else get 'well occupied' errors if one well gets saved and other(s) don't
		foreach ($postvars["well_LIMSID_field"] as $key => $value)
		{
			$tmp_lims_id = $gfunc_obj->get_rid($value);
			$projectID = getReagentProjectID($tmp_lims_id);

			if (($tmp_lims_id > 0) && ($currUserCategory != $_SESSION["userCategoryNames"]["Admin"]) && !in_array($projectID, $userProjects))
			{
				return false;
			}
		}

		// Find the `wellID` of each well submitted
		foreach ($postvars["well_LIMSID_field"] as $key => $value)
		{
			if (strlen(trim($value)) > 0)
			{
				$query = "SELECT * FROM `Wells_tbl` WHERE `containerID`='" . $contID . "' "
				. "AND `wellCol`='" . $_SESSION["goodCol_ar"][ $key ] . "' "
				. "AND `wellRow`='" . $_SESSION["goodRow_ar"][ $key ] . "' "
				. "AND `status`='ACTIVE'";
				
				$well_tmp_rs = mysql_query( $query, $conn ) or die( "FAILURE IN process_firstStage(): " . mysql_error() );

				if ($well_tmp_ar = mysql_fetch_array($well_tmp_rs, MYSQL_ASSOC))
				{
					$wellID_ar[$key] = $well_tmp_ar["wellID"];
				}
				else
				{
					// Added by Marina on Sept 29/05 to account for occupied or reserved wells

					// Look for it in the "bad" row and col arrays
					$q2 = "SELECT * FROM `Wells_tbl` WHERE `containerID`='" . $contID . "' AND `wellCol`='" . $_SESSION["badCol_ar"][ $key ] . "' AND `wellRow`='" . $_SESSION["badRow_ar"][ $key ] . "' AND `status`='ACTIVE'";

					$bad_wells_tmp_rs = mysql_query($q2, $conn) or die("FAILURE IN process_firstStage(): " . mysql_error());
					
					if ($bad_wells_tmp_ar = mysql_fetch_array($bad_wells_tmp_rs, MYSQL_ASSOC))
					{
						continue;
					}
					else
					{
						echo "Cannot locate a valid ID of one or all of the wells submitted <br>";
						return false;
					}
				}
			}
			else
			{}

			$reagentTypeID_tmp = 0;
			$groupID_tmp = 0;
			$isolate_tmp = 0;

			// Finds isolate number if this is an isolate active container
			if( $isoAct_state )
			{
				$isolate_tmp = $_POST["well_isoNum" . $tmp_count . "_textfield"];

				if (isset($_POST["well_beingUsed" . $tmp_count . "_checkbox"]))
				{
					$beingUsed = "YES";
				}
				else
				{
					$beingUsed = "NO";
				}
			}

			// Finds the given reagent Type and group ID
			// Modified June 8/09
			$reagentTypeID_tmp = $gfunc_obj->get_typeID($value);
			$groupID_tmp = $gfunc_obj->get_groupID($value);

			// Finds the reagent ID associated with the inputted LIMS ID
			$reagentID_rs = mysql_query("SELECT * FROM Reagents_tbl WHERE reagentTypeID='" . $reagentTypeID_tmp . "' " . "AND `groupID`='" . $groupID_tmp . "' AND `status`='ACTIVE'", $conn) or die("FAILURE IN SQL: " . mysql_error());

			while ($reagentID_ar = mysql_fetch_array($reagentID_rs, MYSQL_ASSOC))
			{
				$expID_tmp = $this->backChecking_LIMSID_Existance($reagentID_ar["reagentID"]);

				if ($expID_tmp <= 0)
				{
					// reagentID does not exist in experiment side of database
					$expInsert_rs = mysql_query("INSERT INTO `Experiment_tbl` (`expID`, `reagentID`, `status`) VALUES ('', '" . $reagentID_ar["reagentID"] . "','ACTIVE')", $conn) or die( "FAILURE IN SQL EXP INSERT SQL: " . mysql_error());

					$expID_tmp = mysql_insert_id($conn);
				}
				
				// Check isolate
				$isoCheck_rs = mysql_query("SELECT * FROM `Isolate_tbl` WHERE `expID`='" . $expID_tmp . "' AND `isolateNumber`='" . $isolate_tmp . "' AND `status`='ACTIVE'", $conn) or die("FAILURE IN: Location_well_Class.process_firststage(1): " . mysql_error());

				if ($isoCheck_ar = mysql_fetch_array($isoCheck_rs, MYSQL_ASSOC))
				{
					$isoID_tmp = $isoCheck_ar["isolate_pk"];
					
					// if you're creating an isolate that exists in the db, but want to make it beingUsed
					// e.g. there is a V24-1 in the db, but it's not beingUsed, and you want to create another V24-1 and make it beingUsed
					if (!($this->backChecking_beingUsed_duplicate($expID_tmp, $isoID_tmp, $beingUsed)) && ($beingUsed == "YES"))
					{
						mysql_query("UPDATE `Isolate_tbl` SET `beingUsed`='YES' WHERE `isolate_pk`='" . $isoID_tmp . "'", $conn);
					}
				}
				else
				{
					if ($isoAct_state)
					{
						$isoInsert_rs = mysql_query("INSERT INTO `Isolate_tbl` (`isolate_pk`, `expID`, `isolateNumber`, `beingUsed`) " . "VALUES ('', '" . $expID_tmp . "','" . $isolate_tmp . "','NO')", $conn);

						$isoID_tmp = mysql_insert_id( $conn );

						if (!($this->backChecking_beingUsed_duplicate($expID_tmp, $isoID_tmp, $beingUsed)) && ($beingUsed == "YES"))
						{
							mysql_query("UPDATE `Isolate_tbl` SET `beingUsed`='YES' WHERE `isolate_pk`='" . $isoID_tmp . "'", $conn);
						}
					}
					else
					{
						$isoInsert_rs = mysql_query( "INSERT INTO `Isolate_tbl` (`isolate_pk`, `expID`, `isolateNumber`, `beingUsed`) " . "VALUES ('', '" . $expID_tmp . "','0','NO')", $conn ) 
						or die( "FAILURE IN Location_well_Class.process_firststage(2):: " . mysql_error() );

						$isoID_tmp = mysql_insert_id( $conn );
					}
				}

				$query =  "SELECT * FROM `Prep_tbl` WHERE `wellID`='" . $wellID_ar[ $key ] . "' AND `status`='ACTIVE'";

				$prep_location_check_rs = mysql_query($query, $conn) or die("FAILURE IN Location_well_Class.process_firststage(3):: " . mysql_error());

				if ($prep_location_check_ar = mysql_fetch_array($prep_location_check_rs, MYSQL_ASSOC))
				{
					// removed June 7, 2011 - can't remember if this is a debug statement or part of the code
					//echo "<B>This location is already occupied by prep #" . $prep_location_check_ar[$prepID] . "</B><br>";

					return false;
				}
				else
				{
					$query = "INSERT INTO `Prep_tbl` (`prepID`, `isolate_pk`, `refAvailID`, `wellID`, `flag`, `comments`, `status`) VALUES ('', '" . $isoID_tmp . "', '" . $_POST["well_refAvail_text"][$tmp_count] . "', '" . $wellID_ar[$key] . "', '" . $_POST["well_flag" . $tmp_count . "_checkbox"] . "', '" . $_POST["well_comments_text"][$tmp_count] . "', 'ACTIVE')";
					
					$prepInsert_rs = mysql_query($query, $conn) or die("FAILURE IN Location_well_Class.process_firststage(4):: " . mysql_error());
				
					$prepID_tmp = mysql_insert_id( $conn ); // Added: Oct 13/05 - Holds the new prepID of the newly inserted information, used to link the general properties
					
					// Unreserve the well
					$query = "UPDATE `Wells_tbl` SET `reserved`='FALSE' WHERE `wellID`='" . $wellID_ar[ $key ] . "' AND `status`='ACTIVE'";
					
					$unreserve_result = mysql_query($query, $conn) or die("FAILURE IN Location_well_Class.process_firststage(4):: " . mysql_error());
					
					foreach($general_req_ar as $pname => $pid)
					{
						$current_general_value_tmp = $_POST["well_mod_general" . $pid . "_text"][$tmp_count];
				
						if ($current_general_value_tmp != $general_tmp_ar[$pname])
						{
							// check to see if it already exists
							$check_general_rs = mysql_query("SELECT * FROM `PrepElementProp_tbl` WHERE `elementTypeID`='" . $pid . "' AND `prepID`='" . $prepID_tmp . "' AND `status`='ACTIVE'", $conn) or die( "FAILURE IN Location_Well_Class.process_modify(7): " . mysql_error());
							
							if ($check_general_ar = mysql_fetch_array($check_general_rs, MYSQL_ASSOC))
							{
								//echo "Entered update of general property section<BR>";
								//echo "prepID: " . $prepID_tmp . " -- elementtypeID: " . $pid . " -- value : " . $_POST["well_mod_general" . $pid . "_text"][ $input_count ] . "<BR>";
								// If the property type already exists in the database, modify the existing value
								mysql_query( "UPDATE `PrepElementProp_tbl` "
										. "SET `value`='" . /*addslashes(*/ $current_general_value_tmp /*)*/ . "' "
										. "WHERE `prepID`='" . $prepID_tmp . "' "
										. "AND `elementTypeID`='" . $pid . "' "
										. "AND `status`='ACTIVE' ", $conn )
										or die( "FAILIURE IN Location_Well_Class.process_modify(8): " . mysql_error() );
							}
							else
							{
								// If no property type exists, add as new!
								//echo "Entered INSERT of general property section<BR>";
								//echo "prepID: " . $prepID_tmp . " -- elementtypeID: " . $pid . " -- value : " . $_POST["well_mod_general" . $pid . "_text"][ $input_count ] . "<BR>";
								
								mysql_query( "INSERT INTO `PrepElementProp_tbl` (`prepPropID`, `prepID`, `elementTypeID`,`value`, `status`) VALUES ('', '" . $prepID_tmp . "', '" . $pid . "', '" . $current_general_value_tmp /*)*/ . "', 'ACTIVE')", $conn) or die( "FAILIURE IN Location_Well_Class.process_modify(9): " . mysql_error());
							}
						}
					}

					// End of General Property Insert
					
					if ($unreserve_result == false)
					{
						echo "Failed to unreserve well " . $wellID_ar[ $key ] . "<br>";
						return false;
					}
				}
			}

			$tmp_count++;
		}
		
		return true;	
	}
	
	/**
	 * First step in unreserving wells, takes user input and passes on to processing function (not actively called anymore, removed 'unreserve' button, but don't delete this function, may be of use)
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param Array $postvars
	 * @param INT $contID
	 *
	 * @return boolean TRUE on success, FALSE on error
	*/
	function unreserve_wells ($postvars, $contID)
	{
		global $conn;

		$lfunc_obj = new Location_Funct_Class();
		$submit_btn = false;

		if( isset( $_SESSION["userinfo"] ) )
		{
			$currentUserID = $_SESSION["userinfo"]->getUserID();
		}

		if (!isset($postvars["wells_checkbox"] ))
		{
			?><CENTER><FORM METHOD=POST ACTION="<?php echo $_SERVER["PHP_SELF"] . "?View=7&Mod=" . $contID . ""; ?>">
			&nbsp;&nbsp;&nbsp;&nbsp;<P><B><FONT SIZE="" COLOR="#FF0000">No well has been selected!&nbsp;&nbsp;Please select a well to unreserve.</FONT></B><BR><BR>
			&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE="submit" name="mod_empty_well_cancel" value="OK">
			</FORM></CENTER><?php

			return false;
		}
		else
		{
			foreach ($postvars["wells_checkbox"] as $key => $value)
			{
				$pieces = explode( "|", $value );
				$wellRow_ar[ $key ] = $pieces[ 0 ];	// get the row numbers of all the wells selected
				$wellCol_ar[ $key ] = $pieces[ 1 ];	// get the column numbers of all the wells selected

				// set for next stage
				$_SESSION["wells_checkbox_tmp"] = $postvars["wells_checkbox"];
			}

			?><CENTER><FORM METHOD=POST ACTION="<?php echo $_SERVER["PHP_SELF"] . "?View=7&Mod=" . $contID . ""; ?>"><P><B><FONT SIZE="3">Are you sure you want to cancel your reservation for the well(s) selected?</FONT></B><BR><BR><?php

			foreach ($wellRow_ar as $key => $rowvalue)
			{
				// Check if the well is occupied; in that case cannot unreserve
				$q1 = "SELECT b.`prepID`, b.`isolate_pk`, b.`refAvailID`, b.`flag`, b.`comments`, a.`wellID` FROM `Wells_tbl` a INNER JOIN `Prep_tbl` b ON a.`wellID`=b.`wellID` WHERE a.`wellRow`='" . $rowvalue . "' AND a.`wellCol`='" . $wellCol_ar[$key] . "' AND a.`containerID`='" . $contID . "' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'";

				$check_well_occupied_rs = mysql_query($q1, $conn) or die("ERROR IN CHECKING WELL SQL STATEMENT: " . mysql_error());

				if ($check_well_occupied_ar = mysql_fetch_array($check_well_occupied_rs, MYSQL_ASSOC))
				{
					// change this (commented sept 3/10)
					echo "<B><CENTER><FONT COLOR=\"#FF0000\">Well " . $lfunc_obj->getLetterRow($rowvalue) . "" . $wellCol_ar[$key] . " is occupied! It cannot be unreserved.</FONT></CENTER></B><BR>";
				}
				else
				{
					// Check that the well has indeed been reserved
					$check_well_reserved_rs = mysql_query("SELECT * FROM `Wells_tbl` WHERE `containerID`='" . $contID . "' AND `wellRow` = '" . $rowvalue . "' AND `wellCol` = '" . $wellCol_ar[$key] . "' AND `reserved`='TRUE' AND `status`='ACTIVE'", $conn) or die ("Failure checking well reserved status: " . mysql_error);

					if ($check_well_reserved_ar = mysql_fetch_array($check_well_reserved_rs, MYSQL_ASSOC))
					{
						// Check that it has been reserved by the current user, i.e. creatorID = current user ID
						$creatorID_tmp = $check_well_reserved_ar["creatorID"];

						if ($creatorID_tmp == $currentUserID)
						{
							// Confirm unreserve:

							$submit_btn = true;
						}
						else
						{
							// Cannot unreserve a well that has been reserved by someone other than yourself
							if ($creatorID_tmp == 0)
							{
								$username = "Administrator";
							}
							else
							{
								$query = "SELECT `description` FROM Users_tbl WHERE `userID`='" . $creatorID_tmp . "' AND `status`='ACTIVE'";	// use full names
								$resultset = mysql_query($query, $conn);

								if ($result_ar = mysql_fetch_array($resultset))
								{
									$username = $result_ar["description"];
								}
							}

							// comment sept 3/10: change this
							echo "<FONT COLOR=\"#FF0000\"><CENTER><B>Well " . $lfunc_obj->getLetterRow($rowvalue) . "" . $wellCol_ar[$key] . " has been reserved by " . $username . ".  Please contact him/her to unreserve this well.</B></CENTER></FONT><BR>";
						}
					}
					else	// well is not reserved
					{
						echo "<FONT COLOR=\"#FF0000\"><B><CENTER>Well " . $lfunc_obj->getLetterRow($rowvalue) . "" . $wellCol_ar[$key] . " is not reserved!</CENTER></B></FONT><BR>";
					}
				}
			}

			if ($submit_btn)
			{
				?><INPUT TYPE="submit" name="well_unreserve_submit_button" value="Unreserve">
				&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE="submit" name="mod_empty_well_cancel" value="Cancel"><BR><?php
			}
			else
			{
				?><INPUT TYPE="submit" name="mod_empty_well_cancel" value="OK"><?php
			}

			?></FORM></CENTER>
			<?php	
		}

		return true;
	}

	/**
	 * Process well unreserve.  Looks like it's been deleted in Location.php, but keep it, may need later.
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param Array $session_vars
	 * @param INT $contID
	 *
	 * @return boolean TRUE on success, FALSE on error
	*/
	function process_unreserve($session_vars, $contID)
	{
		global $conn;
		$lfunc_obj = new Location_Funct_Class();

		if (isset($session_vars))
		{
			foreach ($_SESSION["wells_checkbox_tmp"] as $key => $value)
			{
				$pieces = explode( "|", $value );
				$wellRow_ar[ $key ] = $pieces[ 0 ];		// get the row numbers of all the wells selected
				$wellCol_ar[ $key ] = $pieces[ 1 ];		// get the column numbers of all the wells selected
			}
		}

		foreach( $wellRow_ar as $key => $rowvalue )	
		{	
			$query1 = "UPDATE `Wells_tbl` SET `reserved`='FALSE' WHERE `containerID`='" . $contID . "' AND `wellRow` = '" . $rowvalue . "' AND `wellCol` = '" . $wellCol_ar[$key] . "' AND `reserved`='TRUE' AND `status`='ACTIVE'";

			$result1 = mysql_query($query1, $conn) or die ("Failure unreserving well: ". mysql_error);
				
			if ($result1 == false)
			{
				echo "Failed to unreserve well " . $rowvalue . ":" . $wellCol_ar[$key] . "<br>";
				return false;
			}

			$query2 = "UPDATE `Wells_tbl` SET `creatorID`='0' WHERE `containerID`='" . $contID . "' AND `wellRow` = '" . $rowvalue . "' AND `wellCol` = '" . $wellCol_ar[$key] . "'  AND `status`='ACTIVE'";

			$result2 = mysql_query($query2, $conn) or die ("Failure unsetting creator ID: ". mysql_error);
			
			if ($result2 == false)
			{
				echo "Failed to reset creator ID for well " . $rowvalue . ":" . $wellCol_ar[$key] . "<br>";
				return false;
			}
		}

		return true;
	}

	/**
	 * Step 1 in well deletion - redirect to confirmation page and pass to processing function.
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param Array $session_vars
	 * @param INT $contID
	 *
	 * @return boolean TRUE on success, FALSE on error
	*/
	function delete_wells($postvars, $contID)
	{
		global $conn;

		$lfunc_obj = new Location_Funct_Class();
		$submit_btn = false;

		if (isset($_SESSION["userinfo"]))
		{
			$currentUserID = $_SESSION["userinfo"]->getUserID();
		}

		if (!isset($postvars["wells_checkbox"]))
		{
			?><CENTER><FORM METHOD=POST ACTION="<?php echo $_SERVER["PHP_SELF"] . "?View=7&Mod=" . $contID . ""; ?>">
			&nbsp;&nbsp;&nbsp;&nbsp;<P><B><FONT size="3" COLOR="#FF0000">No well has been selected!&nbsp;&nbsp;Please select a well for deletion.</FONT></B><BR><BR><BR>&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE="submit" name="mod_empty_well_cancel" value="OK"></FORM></CENTER><?php

			return false;
		}
		else
		{
			?><CENTER><FORM METHOD=POST ACTION="<?php echo $_SERVER["PHP_SELF"] . "?View=7&Mod=" . $contID . ""; ?>"><BR><SPAN style="font-size:13pt; font-weight:bold;">Are you sure you want to delete the well(s) selected?</span></CENTER><BR><?php

echo "<SPAN class=\"linkShow\" style=\"font-size:8pt; margin-left:30px;\" onClick=\"checkAll('wells_checkbox_tmp', [])\">Check All</SPAN>";
echo "<SPAN class=\"linkShow\" style=\"font-size:8pt; margin-left:10px;\" onClick=\"uncheckAll('wells_checkbox_tmp', [])\">Uncheck All</SPAN>";

echo "<BR><BR><table style=\"margin-left:20px;\" cellpadding=\"4\" cellspacing=\"4\" border=\"1\" frame=\"box\" rules=\"all\">";

echo "<TR>";
echo "<TD></TD>";
echo "<TD style=\"text-align:center; white-space:nowrap; font-size:9pt; font-weight:bold; color:navy; padding-left:7px; padding-right:7px;\">Well</TD>";
echo "<TD style=\"text-align:center; white-space:nowrap; font-size:9pt; font-weight:bold; color:navy; padding-left:7px; padding-right:7px;\">Reagent ID</TD>";
echo "<TD style=\"text-align:center; white-space:nowrap; font-size:9pt; font-weight:bold; color:navy; padding-left:7px; padding-right:7px;\">Reference</TD>";
echo "<TD style=\"text-align:center; white-space:nowrap; font-size:9pt; font-weight:bold; color:navy; padding-left:7px; padding-right:7px;\">Comments</TD>";
echo "</TR>";

			foreach ($postvars["wells_checkbox"] as $key => $value)
			{
				$pieces = explode( "|", $value );
				$wellRow_ar[ $key ] = $pieces[ 0 ];		// get the row numbers of all the wells selected
				$wellCol_ar[ $key ] = $pieces[ 1 ];		// get the column numbers of all the wells selected

				// set for next stage
				$_SESSION["wells_checkbox_tmp"] = $postvars["wells_checkbox"];
			}

			foreach ($wellRow_ar as $key => $rowvalue)
			{
				// Check if the well is occupied
//				$q1 = "SELECT b.`prepID`, b.`isolate_pk`, b.`refAvailID`, b.`flag`, b.`comments`, a.`wellID` FROM `Wells_tbl` a, `Prep_tbl` b WHERE a.`wellID`=b.`wellID` AND a.`wellRow`='" . $rowvalue . "' AND a.`wellCol`='" . $wellCol_ar[$key] . "' AND a.`containerID`='" . $contID . "' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'";
				
				// Update May 10, 2011: show the numbers/reagent IDs of the selected wells and let user cancel creation of some if needed
				$q1 = "SELECT b.prepID, b.isolate_pk, b.refAvailID, b.flag, b.comments, a.wellID, i.isolateNumber, e.reagentID, r.reagentTypeID, r.groupID FROM Wells_tbl a, Prep_tbl b, Isolate_tbl i, Experiment_tbl e, Reagents_tbl r WHERE a.`wellID`=b.`wellID` AND a.`wellRow`='" . $rowvalue . "' AND a.`wellCol`='" . $wellCol_ar[$key] . "' AND a.`containerID`='" . $contID . "' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND i.isolate_pk=b.isolate_pk AND e.expID=i.expID AND i.status='ACTIVE' AND e.status='ACTIVE' AND r.status='ACTIVE' AND r.reagentID=e.reagentID";

				//	echo $q1;

				$check_well_occupied_rs = mysql_query($q1, $conn) or die("ERROR IN CHECKING WELL SQL STATEMENT: " . mysql_error());

				if ($check_well_occupied_ar = mysql_fetch_array($check_well_occupied_rs, MYSQL_ASSOC))
				{
					$submit_btn = true;

//					print_r($check_well_occupied_ar);

					if ($check_well_occupied_ar["flag"] == "YES")
						$color = "#FF0000";
					else
						$color = "";

					echo "<TR>";
						echo "<TD style=\"color:" . $color . "; width:35px; text-align:center;\">";
							echo "<INPUT TYPE=\"checkbox\" CHECKED NAME=\"wells_checkbox_tmp[]\" VALUE=\"" . $lfunc_obj->getLetterRow($rowvalue) . "|" . $wellCol_ar[$key] . "\">&nbsp;&nbsp;";
						echo "</TD>";

						echo "<TD style=\"color:" . $color . "; width:50px; text-align:center;\">";
							echo $lfunc_obj->getLetterRow($rowvalue) . ":" . $wellCol_ar[$key];
						echo "</TD>";

						echo "<TD style=\"color:" . $color . "; width:50px;\">";
							echo $_SESSION["ReagentType_ID_Prefix"][$check_well_occupied_ar["reagentTypeID"]] . $check_well_occupied_ar["groupID"];

							if ($check_well_occupied_ar["isolateNumber"] && $check_well_occupied_ar["isolateNumber"] != "0")
							{
								echo "-";
								echo $check_well_occupied_ar["isolateNumber"];
							}
						echo "</TD>";

						echo "<TD style=\"color:" . $color . "; width:auto;\">";
							echo $check_well_occupied_ar["refAvailID"];
						echo "</TD>";

						echo "<TD style=\"color:" . $color . "; width:auto;\">";
							echo $check_well_occupied_ar["comments"];
						echo "</TD>";

					echo "</TR>";
				}
				else
				{
					// check if the well is reserved
					$check_well_reserved_rs = mysql_query("SELECT * FROM `Wells_tbl` WHERE `containerID`='" . $contID . "' AND `wellRow` = '" . $rowvalue . "' AND `wellCol` = '" . $wellCol_ar[$key] . "' AND `reserved`='TRUE' AND `status`='ACTIVE'", $conn) or die ("Failure checking well reserved status: " . mysql_error);

					// if yes, check who reserved it
					if ($check_well_reserved_ar = mysql_fetch_array($check_well_reserved_rs, MYSQL_ASSOC))
					{
						$creatorID_tmp = $check_well_reserved_ar["creatorID"];

						// if you, then delete the well
						if ($creatorID_tmp == $currentUserID)
						{
							echo "<TR>";
								echo "<TD style=\"color:navy; width:35px; text-align:center;\">";
									echo "<INPUT TYPE=\"checkbox\" CHECKED NAME=\"wells_checkbox_tmp[]\" VALUE=\"" . $lfunc_obj->getLetterRow($rowvalue) . "|" . $wellCol_ar[$key] . "\">&nbsp;&nbsp;";
								echo "</TD>";

								echo "<TD style=\"color:navy;\" colspan=\"100%;\">";
									echo "You have reserved well " . $lfunc_obj->getLetterRow($wellRow_ar[$key]) . ":" . $wellCol_ar[$key] . ".  Deletion will release the reservation. Uncheck if you wish to keep the well reserved.";
								echo "</TD>";
							echo "</TR>";

							$submit_btn = true;
						}
						else
						{
							// otherwise, find out who reserved this well and notify the user
							if ($creatorID_tmp == 0)
							{
								$username = "Administrator";
							}
							else
							{
								$query = "SELECT `description` FROM Users_tbl WHERE `userID`='" . $creatorID_tmp . "' AND `status`='ACTIVE'";
								$resultset = mysql_query($query, $conn);

								if ($result_ar = mysql_fetch_array($resultset))
								{
									$username = $result_ar["description"];
								}
							}

							echo "<TR>";
								echo "<TD colspan=\"100%;\" style=\"color:#FF0000;\">";
									echo "Well " . $lfunc_obj->getLetterRow($wellRow_ar[$key]) . "" . $wellCol_ar[$key] . " has been reserved by " . $username . ".  Please contact him/her to delete this well.";
								echo "</TD>";
							echo "</TR>";
						}
					}
					else
					{
						// empty, unreserved well
						echo "<TR>";
							echo "<TD colspan=\"100%;\" style=\"color:#FF0000;\">";
								echo "Well " . $lfunc_obj->getLetterRow($wellRow_ar[$key]) . "" . $wellCol_ar[$key] . " is empty.";
							echo "</TD>";
						echo "</TR>";
					}
				}
			}

echo "</table>";

			if ($submit_btn)
			{
				?><INPUT TYPE="submit" name="well_delete_submit_button" value="Delete" style="margin-left:25px; margin-top:10px;">
				&nbsp;<INPUT TYPE="submit" name="mod_empty_well_cancel" value="Cancel"><?php
			}
			else
			{
				?><INPUT TYPE="submit" name="mod_empty_well_cancel" value="OK"><?php
			}
		}

		return true;
	}
	
	/**
	 * Process well deletion - delete preps, make the well(s) empty (reserve????)
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param Array $session_vars
	 * @param INT $contID
	 *
	 * @return boolean TRUE on success, FALSE on error
	*/
	function process_delete($session_vars, $contID)
	{
	//	print_r($session_vars);

		global $conn;
		$lfunc_obj = new Location_Funct_Class();

		if( isset( $_SESSION["userinfo"] ) )
		{
			$currentUserID = $_SESSION["userinfo"]->getUserID();
		}

		if (isset($session_vars))
		{
			foreach ($session_vars as $key => $value)
			{
				$pieces = explode("|", $value);
				$wellRow_ar[$key] = $pieces[0];		// get the row numbers of all the wells selected
				$wellCol_ar[$key] = $pieces[1];		// get the column numbers of all the wells selected
			}
		}

		foreach($wellRow_ar as $key => $rowvalue)
		{
			$rowvalue = $lfunc_obj->alpha2num($rowvalue) + 1;

			// Get the wellID, the prepID and the isolate
			$q1 = "SELECT b.`prepID`, b.`isolate_pk`, b.`refAvailID`, b.`flag`, b.`comments`, a.`wellID` FROM `Wells_tbl` a INNER JOIN `Prep_tbl` b ON a.`wellID`=b.`wellID` WHERE a.`wellRow`='" . $rowvalue . "' AND a.`wellCol`='" . $wellCol_ar[$key] . "' AND a.`containerID`='" . $contID . "' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'";

			$check_well_occupied_rs = mysql_query($q1, $conn ) or die( "ERROR IN CHECKING WELL SQL STATEMENT: " . mysql_error());

			if ($check_well_occupied_ar = mysql_fetch_array($check_well_occupied_rs, MYSQL_ASSOC))
			{
				$wellID_tmp = $check_well_occupied_ar["wellID"];
				$prepID_tmp = $check_well_occupied_ar["prepID"];
				$isoID_tmp = $check_well_occupied_ar["isolate_pk"];

				// delete well
				$q5 = "UPDATE `Wells_tbl` SET `status`='DEP' WHERE `wellID`='" . $wellID_tmp . "' AND `status`='ACTIVE'";
				$result3 = mysql_query($q5, $conn) or die( "ERROR IN WELL DELETION: " . mysql_error() );

				if ($result3 == false)
				{
					echo "Could not delete well " . $wellID_tmp . "<br>";
					return false;
				}

				// delete prep
				$q4 = "UPDATE `Prep_tbl` SET `status`='DEP' WHERE `prepID`='" . $prepID_tmp . "' AND `status`='ACTIVE'";
				$result2 = mysql_query($q4, $conn) or die( "ERROR IN PREP DELETION: " . mysql_error() );

				if ($result2 == false)
				{
					echo "Could not delete prep " . $prepID_tmp . "<br>";
					return false;
				}

				// Added Oct. 1/07, Marina: Delete prep properties if existed!!!!!!!!!
				mysql_query("UPDATE PrepElementProp_tbl SET status='DEP' WHERE prepID='" . $prepID_tmp . "' AND `status`='ACTIVE'", $conn) or die("Could not delete prep properties: " . mysql_error());

				// Check if the prep was the only prep for that isolate #
				$q2 = "SELECT * FROM `Prep_tbl` WHERE `isolate_pk`='" . $isoID_tmp . "' AND `status`='ACTIVE'";
				$check_preps_rs = mysql_query($q2, $conn ) or die( "ERROR IN CHECKING ISOLATES: " . mysql_error() );

				// if yes, delete the isolate too
				if (!($check_preps_ar = mysql_fetch_array($check_preps_rs, MYSQL_ASSOC)))
				{
					$q3 = "UPDATE `Isolate_tbl` SET `status`='DEP' WHERE `isolate_pk`='" . $isoID_tmp . "' AND `status`='ACTIVE'";
					$result1 = mysql_query($q3, $conn) or die( "ERROR IN ISOLATE DELETION: " . mysql_error() );

					if ($result1 == false)
					{
						echo "Could not delete isolate " . $isoID_tmp . "<br>";
						return false;
					}
				}
			}
			else
			{
				// check if the well is reserved
				$check_well_reserved_rs = mysql_query("SELECT * FROM `Wells_tbl` WHERE `containerID`='" . $contID . "' AND `wellRow` = '" . $rowvalue . "' AND `wellCol` = '" . $wellCol_ar[$key] . "' AND `reserved`='TRUE' AND `status`='ACTIVE'", $conn) or die ("Failure checking well reserved status: " . mysql_error);

				// if yes, check who reserved it
				if ($check_well_reserved_ar = mysql_fetch_array($check_well_reserved_rs, MYSQL_ASSOC))
				{
					$wellID_tmp = $check_well_reserved_ar["wellID"];
					$creatorID_tmp = $check_well_reserved_ar["creatorID"];						

					// if you, then delete the well
					if ($creatorID_tmp == $currentUserID)
					{
						$q6 = "UPDATE `Wells_tbl` SET `status`='DEP' WHERE `wellID`='" . $wellID_tmp . "' AND `status`='ACTIVE'";
						$result4 = mysql_query($q6, $conn) or die( "ERROR IN WELL DELETION: " . mysql_error() );

						if ($result4 == false)
						{
							echo "Could not delete well " . $wellID_tmp . "<br>";
							return false;
						}
					}
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Print well in 'modify' mode (after user has selected a well for modification)
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param Array $wells_checkbox_ar Selected well coordinates, passed as array [row | column]
	 * @param INT $contID
	 * @param boolean $error_state
	 *
	 * @return boolean TRUE on success, FALSE on error
	*/
	function printForm_modify($wells_checkbox_ar, $contID, $error_state)
	{
		global $conn;
	
		$wellRow_ar = array();
		$wellCol_ar = array();
		
		$gfunc_obj = new generalFunc_Class();
		$lfunc_obj = new Location_Funct_Class();

		$selContTypeName = $lfunc_obj->getContainerGroupName($contID);
		$selContType = $_SESSION["Container_Name_ID"][$selContTypeName];

		// Added by Marina on August 1, 2005
		if (isset($wells_checkbox_ar))
		{
			if ($lfunc_obj->isIsoActive( $contID ) == "YES")
			{
				$isIsolate_container = true;
			}
			else
			{
				$isIsolate_container = false;
			}
			
			foreach( $wells_checkbox_ar as $key => $value )
			{
				$pieces = explode( "|", $value );
				$wellRow_ar[ $key ] = $pieces[ 0 ];
				$wellCol_ar[ $key ] = $pieces[ 1 ];
			}
				
			// In case errors occur in the submit, allows retrevial of information
			$_SESSION["wells_checkbox_tmp"] = $wells_checkbox_ar;
			
			$goodRow_ar = array();
			$goodCol_ar = array();
			
			$badRow_ar = array();
			$badCol_ar = array();

			$goodWells_ar = array();

			$count = 0;
			
			$well_info_ht = new HT_Class();

			// Comments added by Marina on July 28, 2005
			// Check if this well is occupied - i.e. if this well id has been assigned to a prep.  If yes, the well contains a prep and is occupied.
			foreach( $wellRow_ar as $key => $rowvalue )
			{
				// But this check performs a JOIN with the Preps table - therefore, it checks to see if this well is occupied by a prep.  If it is, pull the prep info out and modify.
				
				$check_well_exist_rs = mysql_query("SELECT b.`prepID`, b.`isolate_pk`, b.`refAvailID`, b.`flag`, b.`comments`, a.`wellID`" . "FROM `Wells_tbl` a INNER JOIN `Prep_tbl` b ON a.`wellID`=b.`wellID` "
							. "WHERE a.`wellRow`='" . $rowvalue . "' AND a.`wellCol`='" . $wellCol_ar[ $key ] . "' "
							. "AND a.`containerID`='" . $contID . "' "
							. "AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn)
							or die( "ERROR IN CHECKING WELL SQL STATEMENT: " . mysql_error());
				
				if( $check_well_exist_ar = mysql_fetch_array( $check_well_exist_rs, MYSQL_ASSOC ) )
				{
					$temp_well_ar = array();
					
					$temp_well_ar["isolate_pk"] = $check_well_exist_ar["isolate_pk"];
					$temp_well_ar["refAvailID"] = $check_well_exist_ar["refAvailID"];
					$temp_well_ar["flag"] = $check_well_exist_ar["flag"];
					$temp_well_ar["comments"] = $check_well_exist_ar["comments"];
					$temp_well_ar["prepID"] = $check_well_exist_ar["prepID"];
					
					$well_info_ht->add( $check_well_exist_ar["wellID"], $temp_well_ar );
					unset( $temp_well_ar );
					
					$goodRow_ar[ $check_well_exist_ar["wellID"] ] = $wellRow_ar[ $key ];
					$goodCol_ar[ $check_well_exist_ar["wellID"] ] = $wellCol_ar[ $key ];
					$goodWells_ar[$count] = $check_well_exist_ar["wellID"];
				}
				else		
				{
					// Added by Marina on July 28, 2005	-- Modified on Sept 29
					// No prep has been assigned to this well.  Nothing to modify.  NEED TO TELL THAT TO THE USER!!!
					
					$well_exists = false;
	
					$well_tmp_info_rs = mysql_query( "SELECT * FROM `Wells_tbl` WHERE `containerID`='" . $contID . "' "
											. "AND `wellRow`='" . $rowvalue . "' AND `wellCol`='" . $wellCol_ar[ $key ] . "' "
											. "AND `status`='ACTIVE'", $conn )
											or die( "ERROR IN CHECKING WELL SQL STATEMENT: " . mysql_error() );
				
					if ($well_tmp_info_ar = mysql_fetch_array($well_tmp_info_rs, MYSQL_ASSOC))
					{	
						$badRow_ar[ $well_tmp_info_ar["wellID"] ] = $wellRow_ar[$key];
						$badCol_ar[ $well_tmp_info_ar["wellID"] ] = $wellCol_ar[ $key ];
						$badWells_ar[$count] = $check_well_exist_ar["wellID"];
					}
				}
	
				$count++;
			}
			
			// To track which variables are where
			$_SESSION["identifier_ar"] = $goodRow_ar;
			
			// Well/Prep information Hash Table
			$_SESSION["well_info_ht"] = $well_info_ht;
			
			// -----------------------------------------------
			// General Property Section of the Modify Function
			$general_info_ht = new HT_Class();
			
			$prep_gen_check_rs = mysql_query("SELECT p.elementTypeID, p.propertyName FROM PrepElemTypes_tbl p, ContainerTypeAttributes_tbl c WHERE c.containerTypeID='" . $selContType . "' AND c.containerTypeAttributeID=p.elementTypeID AND c.status='ACTIVE' AND p.status='ACTIVE' UNION (SELECT a.`prepElementTypeID`, b.`propertyName` FROM `Prep_Req_tbl` a INNER JOIN `PrepElemTypes_tbl` b ON a.`prepElementTypeID`=b.`elementTypeID` WHERE a.`containerID`='" . $contID . "' AND a.`requirement`='REQ' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE')") or die("Error fetching container attributes");
	
			$req_set = "('";
			$req_setcount = 0;
			$general_req_ar = array();
		
			while ($prep_gen_check_ar = mysql_fetch_array($prep_gen_check_rs, MYSQL_ASSOC))
			{
				$req_setcount++;
				$req_set = $req_set . $prep_gen_check_ar["elementTypeID"] . "','";
				$general_req_ar[ $prep_gen_check_ar["propertyName"] ] = $prep_gen_check_ar["elementTypeID"];
			}
		
			$req_set = $this->reset_set($req_setcount, $req_set);
	
			// Session saved variable for the general properties for processing later
			$_SESSION["stage2_general_req_ar"] = $general_req_ar;
				
			// Finding actual general properties for container
			foreach( $goodRow_ar as $wellID_tmp => $wellSpot_tmp )
			{
				$general_tmp_ar = array();
				
				$prep_tmp_ar = $well_info_ht->get( $wellID_tmp );
				$prepID_tmp = $prep_tmp_ar["prepID"];
	
				$find_gen_prop_rs = mysql_query("SELECT a.`prepID`, a.`elementTypeID`, a.`value`, b.`propertyName` FROM `PrepElementProp_tbl` a, `PrepElemTypes_tbl` b WHERE a.`elementTypeID`=b.`elementTypeID` AND a.`prepID`='" . $prepID_tmp . "' AND b.`elementTypeID` IN " . $req_set . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn) or die( "FAILURE IN GENERAL PROPERTY SEARCH SQL: " . mysql_error());
				
				while ($find_gen_prop_ar = mysql_fetch_array($find_gen_prop_rs, MYSQL_ASSOC))
				{
					$general_tmp_ar[$find_gen_prop_ar["propertyName"]] = $find_gen_prop_ar["value"];
				}
	
				$general_info_ht->add( $prepID_tmp, $general_tmp_ar );
			}
			
			// General property Hast table saved
			$_SESSION["general_info_ht"] = $general_info_ht;
			
			// ------------------------------------------------------------
			// Experiment and Isolate Information section of the function
			$exp_info_ht = new HT_Class();
			
			foreach( $goodRow_ar as $wellID_tmp => $wellSpot_tmp )
			{
				$prep_tmp_ar = array();
				
				$prep_tmp_ar = $well_info_ht->get( $wellID_tmp );
				$isoID_tmp = $prep_tmp_ar["isolate_pk"];
				
				$exp_check_rs = mysql_query( "SELECT b.`expID`, b.`reagentID`, a.`isolateNumber`, a.`isolate_active`, a.`beingUsed` "
				. "FROM `Isolate_tbl` a INNER JOIN `Experiment_tbl` b ON a.`expID`=b.`expID` "
				. "WHERE a.`isolate_pk`='" . $isoID_tmp . "' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn )
				or die( "FAILURE IN PREP SQL STATEMENT: " . mysql_error() );
				
				$temp_exp_ar = array();
				
				if( $exp_check_ar = mysql_fetch_array( $exp_check_rs , MYSQL_ASSOC ) )
				{
					$temp_exp_ar["expID"] = $exp_check_ar["expID"];
					$temp_exp_ar["reagentID"] = $exp_check_ar["reagentID"];
					$temp_exp_ar["isolateNumber"] = $exp_check_ar["isolateNumber"];
					$temp_exp_ar["isolate_active"] = $exp_check_ar["isolate_active"];
					$temp_exp_ar["beingUsed"] = $exp_check_ar["beingUsed"];
					
					$exp_info_ht->add( $isoID_tmp, $temp_exp_ar );
					unset( $temp_exp_ar );
				}			
			}
		
			// Experiment property information saved Hash Table
			$_SESSION["exp_info_ht"] = $exp_info_ht;
			$temp_count = 0;
			
			?><form name="well_modify_form" method=post action="<?php echo $_SERVER["PHP_SELF"] . "?View=7&Mod=" . $contID . ""; ?>">
				<table border="1" frame="box" rules="all" style="min-width:986px" cellpadding="3">
					<th colspan="100%" class="detailedView_heading"><?php echo $lfunc_obj->getContainerName($contID); ?>
						<BR><span style="font-size:8pt; font-weight:bold; color:red;"><IMG SRC="pictures/new01.gif" ALT="new" WIDTH="35" HEIGHT="20" style="cursor:auto;">You may update multiple wells at once by clicking on the property's column heading and typing its value in the pop-up box.</span>
					</th>
				
					<tr><?php

				if (!$isIsolate_container)
				{
					?>
						<td style="text-align:center; font-size:7pt; color:navy; font-weight:bold; width:5%;">Well</td>
						<td style="text-align:center; font-size:7pt; color:navy; font-weight:bold; cursor:pointer;" onclick="popupWellAttrUpdateForm('OpenFreezer ID');">OpenFreezer ID</td>
						<td style="text-align:center; font-size:7pt; color:navy; font-weight:bold; cursor:pointer;" onclick="popupWellAttrUpdateForm('Reference');">Reference</td>
						<td style="text-align:center; font-size:7pt; color:navy; font-weight:bold; cursor:pointer;" onClick="checkUncheckAllFlags();">Flag</td>
						<td style="text-align:center; font-size:7pt; color:navy; font-weight:bold; cursor:pointer;" onclick="popupWellAttrUpdateForm('Comments');">Comments</td>
					<?php
				}
				else
				{
					?><td style="text-align:center; font-size:7pt; color:navy; font-weight:bold; width:5%;">Well</td>
					<td style="text-align:center; font-size:7pt; color:navy; font-weight:bold; cursor:pointer;" onclick="popupWellAttrUpdateForm('OpenFreezer ID');">OpenFreezer ID</td>
					<td style="text-align:center; font-size:7pt; color:navy; font-weight:bold; cursor:pointer;" onclick="popupWellAttrUpdateForm('Isolate Number');">Isolate Number</td>
					<td style="text-align:center; font-size:7pt; color:navy; font-weight:bold; padding-left:5px; padding-right:5px; cursor:pointer;" onclick="checkUncheckAllIsolates();">Current Set Isolate</td>
					<td style="text-align:center; font-size:7pt; color:navy; font-weight:bold; cursor:pointer;" onclick="popupWellAttrUpdateForm('Reference');">Reference</td>
					<td style="text-align:center; font-size:7pt; color:navy; font-weight:bold; padding-left:5px; padding-right:5px; cursor:pointer;" onClick="checkUncheckAllFlags();">Flag</td>
					<td style="text-align:center; font-size:7pt; color:navy; font-weight:bold; cursor:pointer;" onclick="popupWellAttrUpdateForm('Comments');">Comments</td>
					<?php
				}

			// Output general property headers
			foreach($general_req_ar as $pname => $pid)
			{
				// June 2, 2011: Enable column selection and setting same values
				echo "<td style=\"text-align:center; font-weight:bold; font-size:7pt; color:navy; cursor:pointer;\" id=\"hdr_" . $pname . "\" onClick=\"popupWellAttrUpdateForm('" . str_replace("'", "\'", $pname) . "');\">";
				echo $pname;
				echo "</td>";
			}

			?></tr><?php

			foreach ($wellRow_ar as $key => $value)
			{
				$wellID_tmp = $goodWells_ar[$key];
				
				if ($wellID_tmp)
				{
					$prep_tmp_ar =  $well_info_ht->get($wellID_tmp);
					
					$isoID_tmp = $prep_tmp_ar["isolate_pk"];
					$prepID_tmp = $prep_tmp_ar["prepID"];
					
					$exp_tmp_ar = $exp_info_ht->get($isoID_tmp);

					?>
					<tr>
						<td style="width:5%; font-size:8pt; font-weight:bold; color:#104E8B; text-align:center;"><?php echo $lfunc_obj->getLetterRow($wellRow_ar[$key]) . ":" . $wellCol_ar[$key]?></td>

						<td name="limsID_td[]" style="padding-left:7px;">
							<?php
								$rid_val = "";	// added June 3, 2011

								// update June 3, 2011
								if (($error_state == true) && (strlen($_SESSION["well_mod_rid_text"][$temp_count]) > 0) )
								{
									$rid_val = $_SESSION["well_mod_rid_text"][$temp_count];
								}
								else
								{
									$rid_val = $gfunc_obj->getConvertedID_rid($exp_tmp_ar["reagentID"]);
								}
							?>
							
						<!-- update June 3, 2011 -->
						<input type="text" size="5" class="locationText" onKeyPress="return disableEnterKey(event);" NAME="well_mod_rid_text[]" value="<?php echo $rid_val; ?>">
					</td>
	
					<?php
						if ($isIsolate_container)	// Added by Marina on July 27, 2005
						{
							?>
							<td style="text-align:center; padding-left:5px; padding-right:5px;" name="isoNum_td[]">
								<input type="text" size="5" class="locationText" onKeyPress="return disableEnterKey(event);" name="well_mod_isoNum<?php echo $temp_count; ?>_textfield" id="well_mod_isoNum<?php echo $temp_count; ?>_textfield" value="<?php 
								//if( $_SESSION["well_mod_error_state"] == "ERROR" )
								if ($error_state)
								{
									echo $_SESSION["well_mod_isoNum_ar"][$temp_count];
								} 
								else
								{ 
									if (strlen($exp_tmp_ar["isolateNumber"]) > 0)
									{
										echo $exp_tmp_ar["isolateNumber"];
									}
								}?>">
							</td>
	
							<td align="center">
								<input type="checkbox" name="well_mod_beingUsed<?php echo $temp_count; ?>_checkbox" value="YES" <?php 
								//if( $_SESSION["well_mod_error_state"] == "ERROR" )
								if( $error_state )
								{
									if( $_SESSION["well_mod_beingUsed_ar"][ $temp_count ] == "YES" )
									{
										echo " checked ";
									}
									else
									{
										echo " ";
									}
								}
								else
								{
									if( $exp_tmp_ar["beingUsed"] == "YES" )
									{
										echo " checked ";
									}
									else
									{
										echo " ";
									}
									
								}
								?>>
							</td>
							<?php
						}
					?>
					<td name="Reference_td[]">
						<input type="text" class="locationText" size="18" onKeyPress="return disableEnterKey(event);" name="well_mod_refAvail_text[]" value="<?php 
							//if( $_SESSION["well_mod_error_state"] == "ERROR" )
							if( $error_state )
							{
								echo $_SESSION["well_mod_refAvail_text"][ $temp_count ];
							}
							else
							{
								if( strlen( $prep_tmp_ar["refAvailID"] ) > 0 )
								{
									echo $prep_tmp_ar["refAvailID"];
								}
							}
						?>">
					</td>

					<td style="text-align:center; padding-left:5px; padding-right:5px;">
						<!-- name="well_mod_flag_checkbox[]"	=> MODIFIED AUG 10--> 
					<input type="checkbox" 
						name="well_mod_flag<?php echo $temp_count; ?>_checkbox"
						value="YES"<?php
						//if( $_SESSION["well_mod_error_state"] == "ERROR" )

// echo "what is this: ";
// print_r($_SESSION["well_mod_flag_checkbox"]);

						if ($error_state)
						{
							if( $_SESSION["well_mod_flag_checkbox"][$temp_count] == "YES" )
							{
								echo " checked ";
							}
							else		// Aug 10
							{
								echo " ";
							}
						}
						else
						{
							if( $prep_tmp_ar["flag"] == "YES" )
							{
								echo " checked ";
							}
							else		// Aug 10
							{
								echo " ";
							}
						}
					?>>
					</td>

					<td name="Comments_td[]">
					<input type="text" class="locationText" onKeyPress="return disableEnterKey(event);" name="well_mod_comments_text[]" value="<?php
						//if( $_SESSION["well_mod_error_state"] == "ERROR" )
						if( $error_state )
						{
							echo $_SESSION["well_mod_comments_text"][ $temp_count ];
						}
						else
						{
							if( strlen( $prep_tmp_ar["comments"] ) > 0 )
							{
								echo $prep_tmp_ar["comments"];
							}
						}
						?>">
					</td>
					<?php

					foreach($general_req_ar as $pname => $pid )
					{
						echo "<td name=\"" . $pname . "_td[]\">";
						$general_tmp_ar = $general_info_ht->get( $prepID_tmp );
						echo "<input type=\"text\" class=\"locationText\" onKeyPress=\"return disableEnterKey(event);\" name=\"well_mod_general" . $pid . "_text[]\" value=\"";
						echo $general_tmp_ar[ $pname ];
						echo "\">";
						echo "</td>";
					}

					?>
					</tr>
				
					<?php
					if( $error_state && strlen( $_SESSION["well_mod_error_msg"][$temp_count] ) > 0 )
					{
						?>
						<tr>
							<td></td>

							<td colspan="100%" style="color:#FF0000; font-weight:bold; font-size:7pt">Error Occurred: <?php echo $_SESSION["well_mod_error_msg"][$temp_count]; ?></td>
						</tr>
						<?php
					}
				
					$temp_count++;
					$submit_btn = true;
				}
				else	// empty well
				{
					$badWellID = $wellRow_ar[$key];
					$tmpRowID = $wellRow_ar[$key];
					$tmpColID = $wellCol_ar[$key];
	
					echo "<tr>";
					echo "<TD style=\"width:5%; font-weight:bold; font-size:8pt; color:#104E8B; text-align:center;\">" . $lfunc_obj->getLetterRow($tmpRowID) . ":" . $tmpColID . "</TD>";
					
					echo "<td colspan=\"100%\" style=\"font-size:7pt; font-weight:bold; color:#104E8B; padding-left:5px;\">No prep information has been stored in well " . $lfunc_obj->getLetterRow($tmpRowID) . ":" . $tmpColID . ".  Please select another well to modify.</td></tr>";
				}
			}	// end foreach

			if ($submit_btn)
			{
				?>
					<tr>
						<td></td>

						<td  colspan="100%">
							<input type="submit" name="well_mod_submit_button" value="Submit">&nbsp;&nbsp;&nbsp;
							<input type="submit" name="well_mod_cancel_button" value="Cancel">
						</td>
					</tr>
				</table>
				<?php
			}
			else
			{
				?>
				<tr>
				<td>
				<input type="submit" name="mod_empty_well_cancel" value="OK">
				</td>
				</tr>
				</table>
				<?php
			}

			unset( $_SESSION["well_mod_error_msg"] );
			unset( $gfunc_obj );
		}
		else
		{
			?><FORM METHOD=POST ACTION="<?php echo $_SERVER["PHP_SELF"] . "?View=7&Mod=" . $contID . ""; ?>"><FONT COLOR="#FF0000"><CENTER><B>No well has been selected for modification!&nbsp;&nbsp;Please select a well to modify.</B></CENTER></FONT><BR>		
			<CENTER><INPUT TYPE="submit" name="mod_empty_well_cancel" value="OK"></CENTER>
			</FORM>
			<?php
		}
	}

	/**
	 * Save modified well information upon exit from 'modify' mode
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT $contID_tmp
	 * @return boolean TRUE on success, FALSE on error
	*/
	function process_modify($contID_tmp)
	{
		global $conn;
		
		$gfunc_obj = new generalFunc_Class();
		$isIsolate_container = false;
		$input_count = 0;
		$error_msg = "";
		
		$isolateDuplicate_ar = array();
		
		$inputRow_check_ar = array();
		
		unset( $_SESSION["well_mod_error_msg"] );
		
		if( !isset( $_SESSION["well_mod_error_msg"] ) )
		{
			$_SESSION["well_mod_error_msg"] = array();
			//$_SESSION["well_mod_error_row"] = array();
		}
		
		// Grab the isolate active state of the container
		$isoAct_rs = mysql_query( "SELECT `isolate_active` FROM `Container_tbl` WHERE `containerID`='" . $contID_tmp . "' " . "AND `status`='ACTIVE'", $conn ) or die( "FAILURE IN process_modify() : isoAct_rs: " . mysql_error() );
	
		if( $isoAct_ar = mysql_fetch_array( $isoAct_rs, MYSQL_ASSOC ) )
		{
			if( $isoAct_ar["isolate_active"] == "YES" )
			{
				$isIsolate_container = true;
			}
			else		// added July 28
			{
				$isIsolate_container = false;
			}
		}
		
		mysql_free_result( $isoAct_rs );
		unset( $isoAct_rs, $isoAct_ar );
		
		foreach( $_SESSION["identifier_ar"] as $wellID_tmp => $wellRow_spot )
		{
			$prep_tmp_ar =  $_SESSION["well_info_ht"]->get( $wellID_tmp );
			$isoID_tmp = $prep_tmp_ar["isolate_pk"];
			$prepID_tmp = $prep_tmp_ar["prepID"];

			$rid_tmp = 0;

			$stage_rid_check = "FAIL";
			$stage_isoNum_check = "FAIL";
			
			$stage_beingUsed_check = "FAIL";
			
			$exp_tmp_ar = $_SESSION["exp_info_ht"]->get( $isoID_tmp );
			
			// VERIFY THE VALIDITY OF THE INFORMATION ENTERED BEFORE INSERTING IT INTO THE DATABASE

			//	1. LIMS ID
			if( isset( $_POST["well_mod_rid_text"] ) && strlen( $_POST["well_mod_rid_text"][ $input_count ] ) > 0 
				&& $gfunc_obj->getConvertedID_rid( $exp_tmp_ar["reagentID"] ) != $_POST["well_mod_rid_text"][ $input_count ] )
			{
				// Must do back checking for LIMS ID integrity check
				$rid_tmp = $gfunc_obj->get_rid( $_POST["well_mod_rid_text"][ $input_count ] );
			}
			else
			{
				$rid_tmp = $exp_tmp_ar["reagentID"];
			}

			$expID_tmp = -1;
			
			if( $rid_tmp > 0 )
			{
				// September 9, 2007: Add project ID check:
				$currUserID = $_SESSION["userinfo"]->getUserID();
				$currUserCategory = $_SESSION["userinfo"]->getCategory();
				$userProjects = getUserProjectsByRole($currUserID, 'Writer');
				$projectID = getReagentProjectID($rid_tmp);
				
				if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($projectID, $userProjects))
				{
					$stage_rid_check = "PASS";
					$expID_tmp = $this->backChecking_LIMSID_Existance( $rid_tmp );
					
					if( $expID_tmp <= 0 )
						$expID_tmp = $this->insert_new_expID( $rid_tmp );
				}
				else
					$error_msg = "You may not insert " .  $_POST["well_mod_rid_text"][ $input_count ] . " into this well, since you do not have Write access to its project.  Please contact the project owner to obtain permission.";
			}
			else
			{
				$error_msg = $error_msg . " | The LIMS ID was not parsed correctly. Please Check the format of the LIMS ID. ";
				$stage_rid_check = "FAIL";
			}
			
			
			// 2. Isolate Active
			if( $isIsolate_container && $stage_rid_check == "PASS")
			{
				// Only executed if this is container is Isolate Active
				if( $isIsolate_container )
				{
					// 3. Isolate Number
					if( isset( $_POST["well_mod_isoNum" . $input_count . "_textfield"] ) 
						&& $_POST["well_mod_isoNum" . $input_count . "_textfield"] != $exp_tmp_ar["isolateNumber"] )	// if the inputted isolate is different
					{
						// Check if the inputted isolate is already taken
						$iso_checkdup_tmp = $this->backChecking_isoNum_duplicate( $expID_tmp, $isoID_tmp, $_POST["well_mod_isoNum" . $input_count . "_textfield"] );
						
						// Modified Aug 5
						if( $iso_checkdup_tmp != -1)
						{
							$isolateDuplicate_ar[ $input_count ] = true;
						}
						else
						{
							$isolateDuplicate_ar[ $input_count ] = false;
						}
					}
					else
					{

					}
					

					// 4. Being Used -- Checks if this is the Current Set Isolate!
					if( isset( $_POST["well_mod_beingUsed" . $input_count . "_checkbox"] ) 
						&& $_POST["well_mod_beingUsed" . $input_count . "_checkbox"] != $exp_tmp_ar["beingUsed"] )
					{
						if( $this->backChecking_beingUsed_duplicate( $expID_tmp, $isoID_tmp, $_POST["well_mod_beingUsed" . $input_count . "_checkbox"] ) )
						{
							$stage_beingUsed_check = "FAIL";
							$error_msg = $error_msg . " There is already a isolate set as being used! ";
//							echo "Error msg in isolate used fail: " . $error_msg . "<BR>";
						}
						else
						{
							$stage_beingUsed_check = "PASS";
						}
					}
					else
					{
						// If this isn't set, default to not default or hasn't been set!, it's ok!
						$stage_beingUsed_check = "PASS";
					}
				}
				else	// Not isolate active container
				{
						
				}
			}
			elseif( !$isIsolate_container && $stage_rid_check == "PASS" )
			{
				// NOT AN ISOLATE ACTIVE CONTAINER!!!
				$stage_isoNum_check = "FAIL";
				$stage_beingUsed_check = "PASS";
			}
			
			if ($stage_rid_check == "PASS" && $stage_beingUsed_check == "PASS")
			{
				$inputRow_check_ar[ $input_count ] = "PASS";
			}
			else
			{
				$_SESSION["well_mod_error_msg"][ $input_count ] = $error_msg;
				$inputRow_check_ar[ $input_count ] = "FAIL";
			}
			
			$error_msg = "";
			$value_checker_bool = false;

			if( !in_array( "FAIL", $inputRow_check_ar ) )
			{
				$value_checker_bool = true;
			}
			else
			{
				$value_checker_bool = false;
			}

			if ($value_checker_bool)
			{
				// Update the information that was changed
				$prep_tmp_ar =  $_SESSION["well_info_ht"]->get( $wellID_tmp );
				$isoID_tmp = $prep_tmp_ar["isolate_pk"];
				$prepID_tmp = $prep_tmp_ar["prepID"];

				$rid_tmp = 0;
				$expID_tmp = 0;
				$beingUsed_tmp = "NO";
				$flag_tmp = "NO";
				
				$changed_count = 0;
				
				$exp_tmp_ar = $_SESSION["exp_info_ht"]->get( $isoID_tmp );
				
				$general_tmp_ar = $_SESSION["general_info_ht"]->get( $prepID_tmp );
				
				
				// 1. LIMS ID

				if( strlen( $_POST["well_mod_rid_text"][ $input_count ] ) > 0 && $gfunc_obj->getConvertedID_rid( $exp_tmp_ar["reagentID"] ) != $_POST["well_mod_rid_text"][$input_count])
				{
					$rid_tmp = $gfunc_obj->get_rid( $_POST["well_mod_rid_text"][ $input_count ] );
					$changed_count++;
				}
				else
				{
					$rid_tmp = $exp_tmp_ar["reagentID"];
					
				}
				
				$expID_tmp = $gfunc_obj->get_ExpID( $rid_tmp );
				$isoActive_sql_string = "";
				
				// 2. Isolate number
				// Modified by Marina on August 8, 2005
				
				// Only executed if container is isolate active - NOT executed for Oligos and Inserts
				if ($isIsolate_container)
				{
					// Execute only if the inputted isolate is different (added by Marina on Aug 8, 2005)
					if (isset($_POST["well_mod_isoNum" . $input_count . "_textfield"]) && $_POST["well_mod_isoNum" . $input_count . "_textfield"] != $exp_tmp_ar["isolateNumber"])
					{
						// Insertion/update of isolate information is done here
						if ($isolateDuplicate_ar[$input_count])
						{
							// Need to find the previous isolate number isolate_pk and associate with this isolate entry!
							
							// Find this prep's old isolate
							$isoCheck_rs = mysql_query("SELECT * FROM `Isolate_tbl` WHERE `expID`='" . $expID_tmp . "' " . "AND `isolateNumber`='" . $isolate_tmp . "' " . "AND `status`='ACTIVE'", $conn) or die( "FAILURE IN: process_modify " . mysql_error());

							if ($isoCheck_ar = mysql_fetch_array($isoCheck_rs, MYSQL_ASSOC))
							{
								$isoID_old = $isoCheck_ar["isolate_pk"];
							}
							else
							{
								$isoID_old = $isoID_tmp;
							}
							
							// Find the new isolate
							$isolate_tmp = $_POST["well_mod_isoNum" . $input_count . "_textfield"];

							$isoCheck_rs = mysql_query("SELECT * FROM `Isolate_tbl` WHERE `expID`='" . $expID_tmp . "' " . "AND `isolateNumber`='" . $isolate_tmp . "' " . "AND `status`='ACTIVE'", $conn) or die("FAILURE IN: process_modify " . mysql_error());
							
							if( $isoCheck_ar = mysql_fetch_array( $isoCheck_rs, MYSQL_ASSOC ) )
							{
								$isoID_new = $isoCheck_ar["isolate_pk"];
							}
							else
							{
								$isoID_new = $iso_checkdup_tmp;
							}
							
							// Link the prep to the new isolate (change the prep's isolate ID)
							mysql_query( "UPDATE `Prep_tbl` SET `isolate_pk`='" . $isoID_new . "' " . "WHERE `prepID`='" . $prepID_tmp . "' AND `status`='ACTIVE'", $conn ) or die( "Failure in Prep Update: " . mysql_error() );
						
							// Check if there are other preps associated with this isolate
							$prep_rs = mysql_query("SELECT * FROM `Prep_tbl` WHERE `isolate_pk` = '" . $isoID_old . "'", $conn) or die( "FAILURE IN prep deletion:: " . mysql_error());

							if (($preps = mysql_num_rows($prep_rs)) > 0)
							{
								$preps_exist = true;	
							}
							else
							{
								$preps_exist = false;
							}

							// If no other preps are associated with this isolate_pk, deprecate it
							if ($preps_exist == false)
							{
								$isoNum_rs = mysql_query("SELECT * FROM Isolate_tbl WHERE `isolate_pk`='" . $isoID_old . "' AND `expID` = '" . $expID_tmp . "'", $conn) or die ("Failure in isolate number extraction " . mysql_error());
								
								$isoNum_ar = mysql_fetch_array($isoNum_rs);
								$isoNum_tmp = $isoNum_ar["isolateNumber"];

								mysql_query("UPDATE `Isolate_tbl` SET `status`='DEP' WHERE `isolate_pk` = '" . $isoID_old . "' AND `expID` = '" . $expID_tmp . "' AND isolateNumber = '" . $isoNum_tmp . "'", $conn) or die( "FAILURE IN isolate deletion:: " . mysql_error() );
							}
						}
						else
						{
							// Written by Marina on August 9, 2005
							
							// Create a new isolate with the new isolate number
							$isoNum_tmp = $_POST["well_mod_isoNum" . $input_count . "_textfield"];		

							mysql_query( "INSERT INTO `Isolate_tbl` (`isolate_pk`, `expID`, `isolateNumber`, `beingUsed`) "	. "VALUES ('', '" . $expID_tmp . "','" . $isoNum_tmp . "','NO')", $conn )
							or die( "FAILURE IN new isolate insertion:: " . mysql_error() );

							// Setting new iso ID
							$isoID_new = mysql_insert_id($conn);

							// Insert the new prep in that location
							mysql_query("UPDATE `Prep_tbl` SET `isolate_pk`='" . $isoID_new . "' " . "WHERE `prepID`='" . $prepID_tmp . "' AND `status`='ACTIVE'", $conn) or die("Failure in Prep Update: " . mysql_error());

							// Check if there are any remaining preps associated with the old isolate; if none, deprecate it
							$prep_rs = mysql_query("SELECT * FROM `Prep_tbl` WHERE `isolate_pk` = '" . $isoID_tmp . "' AND `status`='ACTIVE'", $conn) or die("FAILURE IN prep deletion:: " . mysql_error());

							if (($preps = mysql_num_rows($prep_rs)) > 0)
							{
								$preps_exist = true;
							}
							else
							{
								$preps_exist = false;
							}

							// If no other preps are associated with this isolate_pk, deprecate it
							if ($preps_exist == false)
							{
								$isoNum_rs = mysql_query("SELECT * FROM Isolate_tbl WHERE `isolate_pk`='" . $isoID_tmp . "' AND `expID` = '" . $expID_tmp . "'", $conn) or die ("Failure in isolate number extraction " . mysql_error());
								
								$isoNum_ar = mysql_fetch_array($isoNum_rs);
								$isoNum_tmp = $isoNum_ar["isolateNumber"];

								mysql_query("UPDATE `Isolate_tbl` SET `status`='DEP' WHERE `isolate_pk` = '" . $isoID_tmp . "' AND `expID` = '" . $expID_tmp . "' AND isolateNumber = '" . $isoNum_tmp . "' AND `status`='ACTIVE'", $conn) or die( "FAILURE IN isolate deletion:: " . mysql_error() );
							}
						}
					}

					//  3. Being Used
					//	Updated by Marina on August 10, 2005
					if (isset( $_POST["well_mod_beingUsed" . $input_count . "_checkbox"]))
					{
						if ($_POST["well_mod_beingUsed" . $input_count . "_checkbox"] != $exp_tmp_ar["beingUsed"])
						{
							// Modify current used status
							$beingUsed_tmp = "YES";
							$changed_count++;
						}
						else
						{
							$beingUsed_tmp = $exp_tmp_ar["beingUsed"];
						}
					}
					else
					{
						if ($_POST["well_mod_beingUsed" . $input_count . "_checkbox"] != $exp_tmp_ar["beingUsed"])
						{
							$beingUsed_tmp = "NO";
							$changed_count++;
						}
						else
						{
							$beingUsed_tmp = $exp_tmp_ar["beingUsed"];
						}
					}
					
					if ($changed_count > 0)
					{
						mysql_query( "UPDATE `Isolate_tbl` SET `beingUsed`='" . $beingUsed_tmp . "' WHERE `expID`='" . $expID_tmp . "' AND `isolate_pk`='" . $isoID_tmp . "' AND `status`='ACTIVE'", $conn ) or die( "Failure in Process Modify (Updating Iso Info) SQL statement: " . mysql_error() );
					}
				}	// end if isolateActive_container

				
				// 4. Flag
				// Updated by Marina on August 11, 2005

				if (isset( $_POST["well_mod_flag" . $input_count . "_checkbox"]))
				{
					if ($_POST["well_mod_flag" . $input_count . "_checkbox"] != $prep_tmp_ar["flag"])
					{
						$flag_tmp = "YES";
						$changed_count++;
					}
					else
					{
						$flag_tmp = $prep_tmp_ar["flag"];
					}
				}
				else
				{
					// Added Aug 11
 					if ($_POST["well_mod_flag" . $input_count . "_checkbox"] != $prep_tmp_ar["flag"])
					{
						$flag_tmp = "NO";
						$changed_count++;
					}
					else
					{
						$flag_tmp = $prep_tmp_ar["flag"];
					}
				}

				if ($changed_count > 0)
				{	
					mysql_query( "UPDATE `Prep_tbl` SET `flag`='" . $flag_tmp . "' WHERE `prepID`='" . $prepID_tmp . "' AND `status`='ACTIVE'", $conn ) or die( "FAILURE in process_modify (Updating Prep Info) SQL error: " . mysql_error() );
				}


				// 5. Reference
				if (isset($_POST["well_mod_refAvail_text"]) && $_POST["well_mod_refAvail_text"][$input_count] != $exp_tmp_ar["refAvail"])
				{
					$refAvail_tmp = $_POST["well_mod_refAvail_text"][ $input_count ];
					$changed_count++;
				}
				else			// Added by Marina on July 22, 2005
				{
					$refAvail_tmp = "";
					$changed_count++;
				}


				// 6. Comments

				if( isset( $_POST["well_mod_comments_text"] ) 
					&& $_POST["well_mod_comments_text"][ $input_count ] != $exp_tmp_ar["comments"] )
				{
					// Must do updating for isolate number as well as check that inputted isolate is not taken!
					$comments_tmp =  $_POST["well_mod_comments_text"][ $input_count ];
					$changed_count++;
				}
				else				// Added by Marina on July 22, 2005
				{
					$comments_tmp = "";
					$changed_count++;
				}
								
				if( $changed_count > 0 )
				{
					mysql_query( "UPDATE `Prep_tbl` SET `refAvailID`='" . $refAvail_tmp . "', "
	//						. "`flag`='" . $flag_tmp . "', "
							. "`comments`='" . $comments_tmp . "' "
							. "WHERE `prepID`='" . $prepID_tmp . "' AND `status`='ACTIVE'", $conn )
							or die( "FAILURE in process_modify (Updating Prep Info) SQL error: " . mysql_error() );
				}
				
				
				// 7. General Properties
				foreach($_SESSION["stage2_general_req_ar"] as $pname => $pid)
				{
					$current_general_value_tmp = $_POST["well_mod_general" . $pid . "_text"][ $input_count ];
					
					if ($current_general_value_tmp != $general_tmp_ar[$pname])
					{
						$check_general_rs = mysql_query("SELECT * FROM `PrepElementProp_tbl` WHERE `elementTypeID`='" . $pid . "' AND `prepID`='" . $prepID_tmp . "' AND `status`='ACTIVE'", $conn) or die( "FAILURE IN Location_Well_Class.process_modify(7): " . mysql_error());

						if( $check_general_ar = mysql_fetch_array( $check_general_rs, MYSQL_ASSOC ) )
						{
							// If the property type already exists in the database, modify the existing value
							mysql_query("UPDATE `PrepElementProp_tbl` SET `value`='" . /*addslashes(*/ $current_general_value_tmp /*)*/ . "' WHERE `prepID`='" . $prepID_tmp . "' AND `elementTypeID`='" . $pid . "' AND `status`='ACTIVE' ", $conn) or die("FAILIURE IN Location_Well_Class.process_modify(8): " . mysql_error());
						}
						else
						{
							// If no property type exists, add as new!
							mysql_query( "INSERT INTO `PrepElementProp_tbl` (`prepPropID`, `prepID`, `elementTypeID`,`value`, `status`) VALUES ('', '" . $prepID_tmp . "', '" . $pid . "', '" . $current_general_value_tmp  . "', 'ACTIVE')", $conn) or die("FAILIURE IN Location_Well_Class.process_modify(9): " . mysql_error());
						}
					}
				}
				
				$input_count++;		
			}	// end if(value_check_bool)
			else
			{
				// Return to form page with error output
				
				// NEED: storage of all inputted values so user doesn't have to reinput them
				$_SESSION["well_mod_rid_text"] = $_POST["well_mod_rid_text"];
				$_SESSION["well_mod_comments_text"] = $_POST["well_mod_comments_text"];
				$_SESSION["well_mod_flag_checkbox"] = $_POST["well_mod_flag_checkbox"];
				$_SESSION["well_mod_refAvail_text"] = $_POST["well_mod_refAvail_text"];
				
				$_SESSION["well_mod_isoact_ar"] = array();
				$_SESSION["well_mod_isoNum_ar"] = array();
				$_SESSION["well_mod_beingUsed_ar"] = array();
				
				//foreach( $_SESSION["identifier_ar"] as $key => $value )
				for( $key = 0; $key < sizeof( $_SESSION["identifier_ar"] ) ; $key++ )
				{
					$_SESSION["well_mod_isoNum_ar"][ $key ] = $_POST["well_mod_isoNum" . $key . "_textfield"];
					$_SESSION["well_mod_beingUsed_ar"][ $key ] = $_POST["well_mod_beingUsed" . $key . "_checkbox"];
				}
				return false;
			}
			
		}		// ADDED AUG 12/05 -- CLOSE WHILE LOOP

		return true;
	}
	
	/**
	 * @author John Paul Lee @version 2005
	 *
	 * @param INT $count
	 * @param STRING $set
	 *
	 * @return STRING
	*/
	function reset_set($count, $set)
	{
		if ($count > 0)
		{
			$set = substr($set, 0, strlen($set) - 2) . ")";
		}
		else
		{
			$set = "('')";
		}

		return $set;
	}

	
	/**
	 * Check to see if an experiment has been created for the given reagentID; if yes, return the expID; return 0 otherwise
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param INT $rid_tmp
	 *
	 * @return INT Experiment ID (value of expID column in Experiment_tbl)
	*/
	function backChecking_LIMSID_existance($rid_tmp)
	{
		global $conn;
		
		$expID_rs = mysql_query("SELECT * FROM `Experiment_tbl` WHERE `reagentID`='" . $rid_tmp . "' AND `status`='ACTIVE'", $conn) or die( "FAILURE IN backChecking_LIMSID_Existance(): " . mysql_error());
		
		if ($expID_ar = mysql_fetch_array($expID_rs, MYSQL_ASSOC))
		{
			return $expID_ar["expID"];
		}
		
		return -1;
	}
	

	/**
	 * Check to see if a given reagentID exists as an experiment yet. If it does, return the expID; otherwise, create an existance of that reagentID in the experiment_tbl, and returns the new expID (differs from backChecking_LIMSID_existance() in that this function creates a new expID for this reagent; backChecking_LIMSID_existance() simply returns -1 if no expID found, without inserting)
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param INT $rid_tmp
	 *
	 * @return INT Experiment ID (value of expID column in Experiment_tbl)
	*/
	function insert_new_expID($rid_tmp)
	{
		global $conn;
		
		$exp_check_rs = mysql_query("SELECT * FROM `Experiment_tbl` WHERE `reagentID`='" . $rid_tmp . "' AND `status`='ACTIVE'", $conn) or die("FAILURE IN insert_new_expID(): " . mysql_error());
		
		if ($exp_check_ar = mysql_fetch_array($exp_check_rs, MYSQL_ASSOC))
		{
			$lastid_tmp = $exp_check_ar["expID"];
		}
		else
		{
			mysql_query("INSERT INTO `Experiment_tbl` (`expID`, `reagentID`, `status`) VALUES ('', '" . $rid_tmp . "', 'ACTIVE')", $conn) or die( "FAILURE IN backChecking_LIMSID_EXistance() : " . mysql_error());
	
			$lastid_tmp = mysql_insert_id( $conn );
		}

		return $lastid_tmp;
	}


	/**
	 * Check if a database record exists for the given experiment and isolate number
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT $expID_tmp
	 * @param INT $isoID_tmp Not used in this function; relying on $value in query
	 * @param INT $value
	 *
	 * @return INT Isolate number if found; -1 otherwise
	*/
	function backChecking_isoNum_duplicate($expID_tmp, $isoID_tmp, $value)
	{
		global $conn;
		
		$isoNum_rs = mysql_query("SELECT * FROM `Isolate_tbl` WHERE `expID`='" . $expID_tmp . "' AND `isolateNumber`='" . $value . "' AND `status`='ACTIVE'" , $conn) or die("FAILURE IN backChecking_isoNum() : " . mysql_error());

		if( $isoNum_ar = mysql_fetch_array( $isoNum_rs, MYSQL_ASSOC ) )
		{
			return $isoNum_ar["isolate_pk"];
		}
		else
		{
			return -1;
		}
	}

	/**
	 * Check if the given isolate number is the selected isolate
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT $expID_tmp
	 * @param INT $isoID_tmp
	 * @param INT $value Not used in this function; relying on $isoID_tmp in query
	 *
	 * @return boolean
	*/
	function backChecking_beingUsed_duplicate($expID_tmp, $isoID_tmp, $value)
	{
		global $conn;
		
		$beingUsed_rs = mysql_query( "SELECT * FROM `Isolate_tbl` WHERE `expID`='" . $expID_tmp . "' "
								. "AND `beingUsed`='YES' AND `status`='ACTIVE'", $conn )
								or die("FAILURE IN backChecking_beingUsed() : " . mysql_error() );
								
		if( $beingUsed_ar = mysql_fetch_array( $beingUsed_rs, MYSQL_ASSOC ) )
		{
			if( $beingUsed_ar["isolate_pk"] == $isoID_tmp )
			{
				return false;
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Extract isolate number from prep ID (e.g. V123-1 -- return isolate number 1) -- Think it's deprecated
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 2005-08-12 Modified to handle the case where isolate number is not entered
	 *
	 * @param STRING $limsid
	 *
	 * @return STRING
	*/
	function getIsolateNumber($limsid)
	{
		if (strpos($limsid, "-"))
		{
			return substr($limsid, strpos($limsid, "-") + 1, strlen($limsid));
		}
		else
		{
			return $limsid;
		}
	}
	
	/**
	 * Print radio buttons for selecting isolate -- Think it's deprecated
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	*/
	function isoActiveEnabled_radio_buttons_here()
	{
		?>
		<td>
			<input type="radio" name="well_mod_isoact<?php echo $temp_count; ?>_radio" id="well_mod_isoact<?php echo $temp_count; ?>_radio"
			<?php
				
				
				if( $isIsolate_container )
				{
					echo " disabled checked ";
				}
				else
				{
					echo " disabled ";
				}
				
			?>
			value="YES" onClick="enable_isoActive(<?php echo $temp_count; ?>)"> Yes
			<input type="radio" name="well_mod_isoact<?php echo $temp_count; ?>_radio"  id="well_mod_isoact<?php echo $temp_count; ?>_radio"
			<?php
				//if( $exp_tmp_ar["isolate_active"] == "NO" )
				//{
				//	echo " checked ";
				//}
				if( $isIsolate_container )
				{
					echo " disabled ";
				}
				else
				{	
					echo " disabled checked ";
				}
				
			?>
			value="NO" onClick="disable_isoActive(<?php echo $temp_count; ?>)"> No
		</td>
		<?php	
	}
}
?>
