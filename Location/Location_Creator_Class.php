<?php
/**
* PHP versions 4 and 5
*
* Copyright (c) 2005-2010 Pawson Laboratory
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
 * This class handles functions for creation of new containers, container sizes and container types.
 *
 * Most of the processing code has been moved to Python, but form output is handled here
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
class Location_Creator_Class
{
	/**
	 * Default constructor
	*/
	function Location_Creator_Class()
	{
	}

	/**
	 * Print form to add container TYPES (processing is done by Python, which adds rows to ContainerGroup_tbl and assigns prep attributes)
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2009-12-18
	 *
	*/
	function printForm_addNewContainerTypes()
	{
		global $conn;
		global $cgi_path;

		$lfunc = new Location_Funct_Class();

		?>
		<FORM METHOD="POST" ACTION="<?php echo $cgi_path . "location_type_request_handler.py"; ?>" onSubmit="return verifyContainerType();">

			<table border="1" bordercolor="black" frame="box" rules="all" style="width:765px;" cellpadding="3">

				<tr>
					<td colspan="3" style="font-weight:bold; font-size:10pt; color:#00C00D; padding-top:8px; padding-bottom:10px; text-align:center;">
						ADD CONTAINER TYPE<BR>
				
						<span style="font-size:10pt; color:#FF0000">
							Fields marked with a red asterisk (*) are mandatory
						</span>
					</td>
				</tr>

				<tr>
					<td style="white-space:nowrap; width:150px; padding-left:9px; font-size:9pt; font-weight:bold; color:#238E23;" colspan="3">
						Name <font size="3" face="Helvetica" color="FF0000"><b>*</b></font>
						&nbsp;&nbsp;<INPUT type="text" size="25" name="cont_group_name_field" ID="containerTypeName" onKeyPress="return disableEnterKey(event);">

						<!-- TOOLTIP -->
						&nbsp;&nbsp;<a href="#" style="font-size:9pt; font-weight:normal;" onclick="return overlib('Choose a descriptive name for this container type (e.g. \'MiniPrep Plate\' or \'Liquid Nitrogen Container\')', CAPTION, 'Container Type Name', STICKY);">Details</a>
					</td>
				</tr>

			
				<tr><TD colspan="3"></TD></TR>

				<tr>
					<td style="padding-top:8px; padding-bottom:7px; padding-left:8px; font-size:9pt; font-weight:bold; color:#238E23;" colspan="3">
						Reagent types:

						<!-- TOOLTIP -->
						&nbsp;&nbsp;&nbsp;<a href="#" style="font-size:9pt; font-weight:normal;" onclick="return overlib('Typically, containers are designed to store samples (preps) of only one reagent type(this setup allows to define a distinct set of properties for a container).  However, preps of different reagent types that share the same characteristics may be stored in the same container type (e.g. preps for both Vector and Insert can be stored in 96-well Glycerol Stock plates)', CAPTION, 'Reagent Types', STICKY);">Details</a>

						<BR><BR>

						<select ID="contTypesList" MULTIPLE size="5" name="cont_cont_group_selection" style="margin-left:4px; padding-left:1px;">
						<?php
							foreach ($_SESSION["ReagentType_ID_Name"] as $rTypeID => $rType)
							{
								echo "<option value=\"" . $rTypeID . "\">" . $rType . "</option>";
							}
						?>
						</select><BR>

						<INPUT TYPE="CHECKBOX" ID="selectAllReagentTypesContainer" style="margin-top:7px;" onClick="selectAllContainerProperties(this.id, 'contTypesList', false)"><span style="color:#000000; font-weight:normal;">Select All</span>
					</td>
				</tr>

				<tr><TD colspan="3"></TD></TR>

				<tr>
					<td style="padding-top:8px; padding-bottom:6px; padding-left:8px; font-size:9pt; font-weight:bold; color:#238E23;" colspan="4">
						Container Type Code:<SPAN style="margin-left:4px; font-size:11pt; font-face:Helvetica; font-weight:bold; color:#FF0000;">*</SPAN>

						&nbsp;&nbsp;<INPUT type="text" size="5" value="" ID="containerCode" name="cont_cont_code_field" onKeyPress="return disableEnterKey(event);">

						<!-- TOOLTIP -->
						&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" style="font-weight:normal; font-size:9pt;" onclick="return overlib('Please enter a <B>two-letter</B> <U>unique</U> identifier for this container type (e.g. \'MP\' for MiniPrep Plates, or \'GS\' for Glycerol Stock containers).  <u>Codes that are listed as currently in use may not be reused</u>.', CAPTION, 'Container Code', STICKY);">Details</a><BR><P>

						<SPAN style="margin-left:3px; font-size:9pt; font-face:Helvetica; font-weight:normal; color:#000000;">The following container type codes are currently in use in OpenFreezer and <u>may NOT be reused</u>:</span>

						<TABLE style="margin-left:10px; border:0">
							<TR><UL><?php

							$count = 0;

							$code_rs = mysql_query("SELECT contGroupName, contGroupCode FROM ContainerGroup_tbl WHERE status='ACTIVE'", $conn) or die("Cannot select container codes: " . mysql_error());

							while ($code_ar = mysql_fetch_array($code_rs, MYSQL_ASSOC))
							{
								$contGroupName = $code_ar["contGroupName"];
								$contGroupCode = $code_ar["contGroupCode"];

								if (($count != 0) && ($count % 2 == 0))
									echo "</TR><TR>";

								echo "<TD style=\"font-size:9pt; font-weight:bold; text-decoration:none;\"><LI>" . $contGroupCode . "</TD>";
								echo "<TD>&nbsp;&#45;&nbsp;</TD>";
								echo "<TD style=\"font-size:9pt;\">" . $contGroupName . "</TD>";
								echo "<TD>&nbsp;</TD>";
								echo "<TD>&nbsp;</TD>";

								$count++;
							}

						?></UL></TR></table>
					</td>
				</tr>

				<tr><TD colspan="3"></TD></TR>

				<tr>
					<td  style="white-space:nowrap; width:150px; padding-left:10px; font-size:9pt; font-weight:bold; padding-top:10px; padding-bottom:10px; color:#238E23;" colspan="3">
						Isolate Active&nbsp;&nbsp;&nbsp;

						<span style="font-weight:normal; color:#000000;">
							<input type="radio" checked name="cont_cont_isolateActive_radio" value="Yes"> Yes
							<input type="radio" name="cont_cont_isolateActive_radio" value="No"> No
	
							<!-- TOOLTIP -->
							&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" style="font-size:9pt;" onclick="return overlib('A reagent may be associated with an isolate - different colony picks (e.g. when cloning vectors or cell lines)', CAPTION, 'Isolate Active', STICKY);">Details</a>
						</span>
					</td>
				</tr>

				<!-- Feb. 12/09: List of features for this container type: -->
				<tr><TD colspan="3"></TD></TR>

				<tr>
					<td  style="white-space:nowrap; width:150px; padding-left:9px; font-size:9pt; font-weight:bold; padding-top:12px; padding-bottom:8px; color:#238E23;" colspan="4">
						Container Type Attributes:

						<!-- TOOLTIP -->
						&nbsp;&nbsp;<a href="#" style="font-weight:normal; font-size:9pt;" onclick="return overlib('Features associated with preps stored in this container (e.g. Bacteria Strain, or Method used to prepare the clone).  If your sought feature is not in the list, add it by typing the feature name in the textbox and pressing \'Add\'', CAPTION, 'Container Type Features', STICKY);">Details</a><BR><P>
					
						<table cellpadding="2">
							<tr>
								<TD style="font-size:9pt; font-weight:bold; text-align:center;">
									Attributes that will be assigned<BR>to containers of this type:
								</td>

								<TD>&nbsp;</TD>

								<TD style="font-size:9pt; font-weight:bold; text-align:left;">
									Select from additional container type<BR>attributes available in OpenFreezer:
								</TD>
							</TR>

							<TR>
								<td style="white-space:nowrap; text-align:left;">
									<SELECT SIZE="10" MULTIPLE NAME="container_features" ID="cont_dest_features" style="min-width:140px; margin-left:15px;"></SELECT>
								
									<P><INPUT TYPE="checkbox" style="margin-left:15px;" onClick="selectAll(this.id, 'cont_dest_features')" id="add_all_chkbx_dest"> Select All</INPUT>
								</td>
							
								<td style="text-align:left; padding-left:10px; padding-right:20px;">
									<input style="margin-top:10px;" onclick="moveListElements('cont_src_features', 'cont_dest_features', false)" value="<<" type="button"></INPUT><BR><BR>

									<input onclick="moveListElements('cont_dest_features', 'cont_src_features', false)" value=">>" type="button"></INPUT><BR><BR><BR><BR>
								</td>

								<td style="white-space:nowrap; padding-left:20px;">
								<?php
									$props_rs = mysql_query("SELECT elementTypeID, propertyName FROM PrepElemTypes_tbl WHERE status='ACTIVE' ORDER BY propertyName") or die("Error fetching container attributes");
		
									echo "<SELECT SIZE='10' MULTIPLE ID='cont_src_features'>";
		
									while ($props_ar = mysql_fetch_array($props_rs, MYSQL_ASSOC))
									{
										$propertyName = $props_ar["propertyName"];
										$elementTypeID = $props_ar["elementTypeID"];
		
										// Update Jan. 14, 2010: pass the property NAME to Python, not the value!
										echo "<OPTION VALUE=\"" . $propertyName . "\" NAME=\"" . $propertyName . "\">" . $propertyName . "</OPTION>";
									}
		
									echo "</SELECT>";

									?>
									<P><INPUT TYPE="checkbox" onClick="selectAll(this.id, 'cont_src_features')" id="add_all_chkbx_src"> Select All</INPUT>
								</td>
							</tr>

							<tr>
								<td colspan="3" style="white-space:nowrap;"><HR>or, add new feature:&nbsp;<INPUT type="text" size="35" ID="new_cont_feature_txt" value="" name="new_cont_feature" onKeyPress="return disableEnterKey(event);">&nbsp;<input onclick="addElementToListFromInput('new_cont_feature_txt', 'cont_dest_features')" value="Add" type="button"></INPUT></td>
							</tr>
						</table>
					</td>
				</tr>

				<tr>
					<td colspan="3">
						<input type="submit" name="cont_type_create_button" value="SUBMIT" onClick="selectAllElements('cont_dest_features');">
					</td>
				</tr>
			</table>
		</FORM>
		<?php
	}


	/**
	 * This function outputs a form to create new container 'SIZES', e.g. "96-well plate" or "25-slot box".
	 *
	 * Keep the name "newTypes" for consistency, just remember that historically "type" referred to "size".
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	*/
	function printForm_newTypes()
	{
		global $conn;
		
		?>
		<form method=post action="<?php echo $_SERVER["PHP_SELF"] . "?View=6&Sub=1"; ?>">
		
			<table border=1 frame="box" rules="all" width="780px" cellpadding="5">
		
				<thead>
					<tr>
						<td colspan="3" style="font-weight:bold; text-align:left; padding-top:8px; padding-bottom:10px; padding-left:280px;">
							ADD NEW CONTAINER SIZE
						</td>
					</tr>
				</thead>

				<tbody>

					<tr>
						<td style="padding-left:10px;">
							Name
						</td>

						<td style="padding-left:10px;">
							<INPUT type="text" name="cont_type_name_field" onKeyPress="return disableEnterKey(event);">
						</td>

						<td style="padding-left:10px;">
							Description of the container's form and capacity (e.g. '96-well plate', '25-slot box')
						</td>
					</tr>

					<tr>
						<td style="padding-left:10px;">
							Max Row Number
						</td>

						<td style="padding-left:10px;">
							<INPUT type="text" size="5" name="cont_type_maxrow_field" onKeyPress="return disableEnterKey(event);">
						</td>

						<td style="padding-left:10px;">
							The maximum number of rows in this container.
						</td>
					</tr>

					<tr>
						<td style="padding-left:10px;">
							Max Column Number
						</td>

						<td style="padding-left:10px;">
							<INPUT type="text" size="5" name="cont_type_maxcol_field" onKeyPress="return disableEnterKey(event);">
						</td>

						<td style="padding-left:10px;">
							The maximum number of columns in this container.
						</td>
					</tr>

					<tr>
						<td style="text-align:left; padding-top:10px;" colspan="3">
							<input type="submit" name="cont_type_create_button" value="SUBMIT">
						</td>
					</tr>
                		</tbody>

				<tr>
					<td colspan="3">
					
						<BR><SPAN STYLE="margin-left:25px; text-decoration:underline; font-weight:bold;">Currently Available Container Sizes:</SPAN>

						<UL>
						<?php
							$type_name_rs = mysql_query("SELECT * FROM `ContainerTypeID_tbl` WHERE `status`='ACTIVE'", $conn) or die("ERROR in group name SQL Statement: " . mysql_error());
							
							while ($type_name_ar = mysql_fetch_array($type_name_rs, MYSQL_ASSOC))
							{
								echo "<LI>" . $type_name_ar["containerName"] . "<b>: " . $type_name_ar["maxRow"] . "</b> rows x <b>" . $type_name_ar["maxCol"] . "</b> columns";
							}
						?>
				        	</UL>
					</td>
				</tr>
			</table>
		</form>
		<?php
	}
	

	/**
	 * This function outputs a form to create new CONTAINERS
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 update Sept. 18/07
	 *
	*/
	function printForm_newContainers()
	{
		global $conn;
		global $cgi_path;

		$lfunc = new Location_Funct_Class();

		?><FORM METHOD="POST" ACTION="<?php echo $cgi_path . "location_request_handler.py"; ?>" onSubmit="return confirmMandatoryLocation() && checkLocationNumeric();">
			<table border="1" frame="box" rules="all" width="765px" cellpadding="3" cellspacing="3">

				<th colspan="3" style="font-size:10pt; color:#0055FF; text-align:center">
					CREATE NEW CONTAINERS<BR>
			
					<span style="font-size:10pt; color:#FF0000">
						Fields marked with a red asterisk (*) are mandatory
					</span>
				</th>
			
				<tr><td colspan="3">&nbsp;</td></tr>

				<tr>
					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						Name <SPAN style="font-size:13; font-face:Helvetica; color:#FF0000; font-weight:bold;">*</span>
					</td>

					<td colspan="2" style="padding-left:10px; padding-right:12px; white-space:nowrap;">
						<INPUT type="text" value="" name="cont_cont_name_field" ID="containerName" onKeyPress="return disableEnterKey(event);">

						&nbsp;<a href="#" style="font-weight:normal; font-size:9pt;" onclick="return overlib('Choose a descriptive name that will distinguish this container from other containers (e.g. \'Jane Doe\'s Oligo Plate 1\'', CAPTION, 'Container Name', STICKY);">Details</a>
					</td>
				</tr>

				<tr>
					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						Container Type: <SPAN style="font-size:13; font-face:Helvetica; color:#FF0000; font-weight:bold;">*</span>
					</td>
			
					<td style="padding-left:10px; padding-right:12px; white-space:nowrap;">
						<select name="cont_cont_group_selection" ID="contGroupBox" style="font-size:8pt;">
							<OPTION SELECTED VALUE="default">-- Select container type --</OPTION>
							<?php
								$group_list_rs = mysql_query("SELECT * FROM `ContainerGroup_tbl` WHERE `status`='ACTIVE'", $conn) or die("ERROR SQL#12: " . mysql_error());
								
								while ($group_list_ar = mysql_fetch_array($group_list_rs, MYSQL_ASSOC))
								{
									echo "<option VALUE=\"" . $group_list_ar["contGroupID"] . "\">" . $group_list_ar["contGroupName"] . "</option>";
								}
							?>
						</select>
					</td>
			
					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						If  the sought container type is not in the list, please <a href="<?php echo $hostname . "contacts.php"; ?>" target="new">contact the Administrator</a> to add it.
					</td>
				</tr>

				<tr>
					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						Container Size <SPAN style="font-size:13; font-face:Helvetica; color:#FF0000; font-weight:bold;">*</span>
					</td>
			
					<td style="padding-left:10px; padding-right:25px; white-space:nowrap;">
						<select name="cont_cont_type_selection" ID="contSizeList" style="font-size:8pt;">
							<OPTION SELECTED VALUE="default">-- Select container size--</OPTION>
							<?php
							$type_list_rs = mysql_query("SELECT * FROM `ContainerTypeID_tbl` WHERE `status`='ACTIVE'", $conn) or die("ERROR SQL#13: " . mysql_error());
	
							while( $type_list_ar = mysql_fetch_array( $type_list_rs, MYSQL_ASSOC ) )
							{
								echo "<option>" . $type_list_ar["containerName"] . "</option>";
							}
							?>
						</select>
					</td>

					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						If the sought container size is not in the list, please <a href="<?php echo $_SERVER["PHP_SELF"] . "?View=6&Sub=1"; ?>" target="new">add it</a> first.
					</td>
				</tr>
			
				<tr>
					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						Description:
					</td>
			
					<td style="padding-left:10px; padding-right:12px;" colspan="2">
						<INPUT type="text" value="" name="cont_cont_desc_field" onKeyPress="return disableEnterKey(event);">
					</td>
				</tr>

				<tr>
					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						Laboratory:
					</td>
			
					<td style="padding-left:10px; padding-right:12px; white-space:nowrap;">
						<?php
							$currLabID = $_SESSION["userinfo"]->getLabID();
							$lfunc->printLabList();
						?>
					</td>

					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						If the laboratory is not in the list, please <a href="<?php echo $hostname . "contacts.php"; ?>" target="new">contact the Administrator</a> to add it.

						&nbsp;<a href="#" style="font-weight:normal; font-size:9pt;" onclick="return overlib('Laboratory that owns this container', CAPTION, 'Laboratory', STICKY);">Details</a>
					</td>
				</tr>

				<tr>
					<td style="padding-left:10px; white-space:nowrap; width:150px; font-size:9pt; padding-right:10px;">
						Storage type <font size="3" face="Helvetica" color="FF0000"><b>*</b></font>
					</td>

					<td style="padding-left:10px; white-space:nowrap;">
					<?php
						$lfunc->printStorageTypes();
					?>
					</td>

					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						If the sought storage type is not in the list, please <a href="<?php echo $hostname . "contacts.php"; ?>" target="new">contact the Administrator</a> to add it.

						&nbsp;<a href="#" style="font-weight:normal; font-size:9pt;" onclick="return overlib('Indicate whether this container is stored at room temperature, in a fridge, a freezer, or a Liquid Nitrogen tank', CAPTION, 'Storage Type', STICKY);">Details</a>
					</td>
				</tr>

				<tr>
					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						Storage Name <font size="3" face="Helvetica" color="FF0000"><b>*</b></font>
					</td>

					<td style="padding-left:10px; white-space:nowrap;" colspan="2">
						<INPUT type="text" ID="locationName" name="storage_name" onKeyPress="return disableEnterKey(event);">

						&nbsp;&nbsp;<a href="#" style="font-weight:normal; font-size:9pt;" onclick="return overlib('A descriptive name of the storage (e.g. \'Freezer 1\' or \'Revco -80\')', CAPTION, 'Storage Name', STICKY);">Details</a>
					</td>
				</tr>

				<TR>
					<TD style="padding-left:10px; font-size:9pt; padding-right:10px;">
						Address
					</TD>

					<td style="padding-left:10px; white-space:nowrap;" colspan="2">
						<INPUT type="text" name="storage_address" onKeyPress="return disableEnterKey(event);">

						&nbsp;&nbsp;<a href="#" style="font-weight:normal; font-size:9pt;" onclick="return overlib('A room number or a complete address of the laboratory where the container is located (e.g. \'MSH rm. 1068\', \'60 Murray Street\', or \'London, England\')', CAPTION, 'Address', STICKY);">Details</a>
					</td>
				</TR>

				<tr>
					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						Shelf number
					</td>

					<td style="padding-left:10px; white-space:nowrap;" colspan="2">
						<INPUT type="text" size="5" ID="contShelf" name="cont_shelf" onKeyPress="return disableEnterKey(event);">

						&nbsp;&nbsp;<a href="#" style="font-weight:normal; font-size:9pt;" onclick="return overlib('Indicate the shelf number in the fridge or freezer where the container is located, if applicable (this field may be left blank)', CAPTION, 'Shelf Number', STICKY);">Details</a>
					</td>
				</tr>

				<tr>
					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						Rack number
					</td>

					<td style="padding-left:10px;" colspan="2">
						<INPUT type="text" size="5" ID="contRack" name="cont_rack" onKeyPress="return disableEnterKey(event);">

						&nbsp;&nbsp;<a href="#" style="font-weight:normal; font-size:9pt;" onclick="return overlib('Indicate the rack number in the tank, fridge or freezer where the container is located, if applicable (this field may be left blank)', CAPTION, 'Rack Number', STICKY);">Details</a>
					</td>
				</tr>

				<tr>
					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						Column number
					</td>

					<td style="padding-left:10px;" colspan="2">
						<INPUT type="text" size="5" ID="contCol" name="cont_col" onKeyPress="return disableEnterKey(event);">

						&nbsp;&nbsp;<a href="#" style="font-weight:normal; font-size:9pt;" onclick="return overlib('Indicate the container\'s column number on the rack, if applicable (this field may be left blank)', CAPTION, 'Column Number', STICKY);">Details</a>
					</td>
				</tr>

				<tr>
					<td style="padding-left:10px; font-size:9pt; padding-right:10px;">
						Row number
					</td>

					<td style="padding-left:10px;" colspan="2">
						<INPUT type="text" size="5" ID="contRow" name="cont_row" onKeyPress="return disableEnterKey(event);">

						&nbsp;&nbsp;<a href="#" style="font-weight:normal; font-size:9pt;" onclick="return overlib('Indicate the container\'s row number on the rack, if applicable (this field may be left blank)', CAPTION, 'Row Number', STICKY);">Details</a>
					</td>
				</tr>

				<tr>
					<td colspan="3">
						<input type="submit" name="cont_cont_create_button" onClick="enableSelect();" value="SUBMIT">
					</td>
				</tr>
			</table>
		</FORM>
		<?php
	}
	
	/**
	 * Interpret POST request and invoke the proper processing function
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param Array $postvars $_POST form input
	*/
	function process_submit($postvars)
	{
		if (isset($postvars["cont_type_create_button"]))
		{
			return $this->process_type($postvars);
		}
	}
	

	/**
	 * Create a new container size (insert POST values into database)
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param Array $postvars $_POST form input
	*/
	function process_type($postvars)
	{
		global $conn;
		
		if( strlen( $postvars["cont_type_name_field"] ) <= 0 )
		{
			return "Error Occured inserting new Container Type: Name field was not found!";
		}
		elseif( $postvars["cont_type_maxrow_field"] <= 0 )
		{
			return "Error Occured inserting new Container Type: Row field does not contain a valid value!";
		}
		elseif( $postvars["cont_type_maxcol_field"] <= 0 )
		{
			return "Error Occured inserting new Container Type: Column field does not contain a valid value!";
		}
		
		$type_check_rs = mysql_query("SELECT * FROM `ContainerTypeID_tbl` WHERE (`maxCol`='" . $postvars["cont_type_maxrow_field"] . "' AND `maxRow`='" . $postvars["cont_type_maxcol_field"] . "') OR `containerName`='" . $postvars["cont_type_name_field"] . "' AND `status`='ACTIVE'", $conn) or die( "ERROR IN SQL STATEMENT in CONT TYPE: " . mysql_error());
		
		if ($type_check_ar = mysql_fetch_array($type_check_rs, MYSQL_ASSOC))
		{
			return "Error Occured inserting new Container Type: Duplicate Entry Found!";
		}
		else
		{
			mysql_query("INSERT `ContainerTypeID_tbl` (`contTypeID`, `containerName`, `maxCol`, `maxRow`, `status`) VALUES ('', '" . $postvars["cont_type_name_field"] . "', '" . $postvars["cont_type_maxcol_field"] . "', '" . $postvars["cont_type_maxrow_field"] . "','ACTIVE')" ) or die ("ERROR IN INSERTING NEW CONT TYPE: " . mysql_error());

			return "Container Type successfully inserted!";
		}
	}
}

?>
