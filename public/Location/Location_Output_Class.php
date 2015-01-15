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
 * This class handles location output functions
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
class Location_Output_Class
{
	/**
	 * Default constructor
	*/
	function Location_Output_Class()
	{}
	
	/**
	 * Changed the appearance of location view 1
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-02-09
	*/
	function print_Location_info()
	{
		?>
		<table width="770px" cellpadding="5">
			<tr>
				<td colspan="3" style="font-size:18pt; font-weight:bold; text-align:center;">
					LOCATION TRACKER
				</td>
			</tr>
			<tr>
				<td colspan="3" style="text-align:center; font-weight:bold">
					This module is used to track the locations and properties of all preps of reagents in the laboratory
				</td>
			</tr>
			<tr>
				<td colspan="3" style="padding-top:8px;">
					<DL>
					<DT>
						<span style="font-weight:bold; text-decoration:underline;">Terminology</span>
						<p><UL>
							<LI>
								Each reagent is associated with an <font color="#004891"><i><b><u>isolate</u></b></i></font> (e.g. when cloning vectors or cell lines &ndash; different colony picks) and a <font color="#004891"><i><b><u>prep</u></b></i></font> (a physical preparation of a particular isolate that is stored).  For each reagent, a certain isolate is selected for downstream applications and is marked as the selected isolate.  If a particular reagent does not need isolates (currently oligos), then it is marked as isolate inactive.
							</LI>
							<p><LI>
								To make a new container - a user chooses a container size and a container group (e.g. Vector 96 well plate)
							</LI>
							<p><LI>
								It is in the location tracker that we track isolates and preps of various reagents and designate which isolate is active.  We also flag certain preps that should not be used.
							</LI>
						</UL>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Print locations of all preps of the reagent identified by $rid
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-02-09
	 *
	 * @param INT $rid
	 * @param INT $view Either 'group by container' or 'group by isolates'
	*/
	function printIsolates_ExpandedView($rid, $view)
	{
		global $conn;
		global $hostname;

		$canOrder = false;	// May 13, 2011
		
		$gfunc_obj = new generalFunc_Class();
		$lfunc_obj = new Location_Funct_Class();
		$bfunc_obj = new Reagent_Background_Class();	// April 16/07, Marina
		$rfunc_obj= new Reagent_Function_Class();	// Jan. 30/08, Marina

		$isolates_ht = new HT_Class();
		$wellID_set = "('";
		$setcount = 0;

		$expID = $this->getExpID( $rid );

		if ($expID < 1)
		{
			// April 16/07, Marina: Mostly applies to Inserts: No location for the Insert itself, but show the locations of its child vectors
			if ($gfunc_obj->getTypeID($rid) == $_SESSION["ReagentType_Name_ID"]["Insert"])
			{
				echo "No location was found for Insert <a href=\"Reagent.php?View=6&rid=" . $rid . "\">" . $gfunc_obj->getConvertedID_rid($rid) . "</a> in OpenFreezer<BR/>";
				echo "<P>Below are locations of Vectors that contain this Insert:</P>";

				$children = $bfunc_obj->get_Children($rid, "Insert Children");

				foreach ($children as $key=>$val)
				{
					$child_expID = $this->getExpID($val);

					if ($child_expID < 1)
					{
						echo "No location was found for Vector <a href=\"Reagent.php?View=6&rid=" . $val . "\">" . $gfunc_obj->getConvertedID_rid($val) . "</a> in OpenFreezer<BR>";
					}
					else
					{
						$this->printIsolates_ExpandedView($val, $view);
					}
				}
			}
			else
			{
				echo "No location was found for <a href=\"Reagent.php?View=6&rid=" . $rid . "\">" . $gfunc_obj->getConvertedID_rid($rid) . "</a> in OpenFreezer";
				return false;
			}
		}
		
		// April 24/07, Marina: At Karen's request, show Vector locations for ALL Inserts, not just those that don't have their own location
		else if ($gfunc_obj->getTypeID($rid) == $_SESSION["ReagentType_Name_ID"]["Insert"])
		{
			echo "<P style=\"font-weight:bold; color:#0000CD\">Locations of Vectors that contain Insert <a href=\"Reagent.php?View=6&rid=" . $rid . "\">" . $gfunc_obj->getConvertedID_rid($rid) . "</a>:</P>";

			$children = $bfunc_obj->get_Children($rid, "Insert Children");

			foreach ($children as $key=>$val)
			{
				$child_expID = $this->getExpID($val);

				if ($child_expID < 1)
				{
					echo "No location was found for Vector <a href=\"Reagent.php?View=6&rid=" . $val . "\">" . $gfunc_obj->getConvertedID_rid($val) . "</a> in OpenFreezer";
				}
				else
				{
					$this->printIsolates_ExpandedView($val, $view);
				}
			}
	
			// separator
			echo "<P style=\"font-weight:bold; color:#0000CD\">Locations of Insert <a href=\"Reagent.php?View=6&rid=" . $rid . "\">" . $gfunc_obj->getConvertedID_rid($rid) . "</a>:</P>";
		}
		
		// DO NOT ADD 'ELSE' - WON'T OUTPUT INSERT LOCATION IF IT EXISTS, IN ADDITION TO VECTORS
		// Updated March 26/08 - added 'flag' field to query - Karen asked to disallow ordering clones that are flagged
		// May 18, 2010: added b.refAvailID to query to be printed at Anna's request
		$isolates_rs = mysql_query( "SELECT a.`isolate_pk`, a.`isolateNumber`, a.`beingUsed`, b.`wellID`, b.`flag`, b.`comments`, b.refAvailID FROM `Isolate_tbl` a, `Prep_tbl` b WHERE a.`isolate_pk`=b.`isolate_pk` AND a.`expID`='" . $expID. "' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn) or die("FAILURE IN Location_Output_Class.printIsolates_ContainerView(1):" . mysql_error());

		while ($isolates_ar = mysql_fetch_array($isolates_rs, MYSQL_ASSOC))
		{
			// Updated March 26/08 - added 'flag' and 'comments' to the query
			$isolates_ht->add($isolates_ar["wellID"], array("isolateNumber" => $isolates_ar["isolateNumber"], "beingUsed" => $isolates_ar["beingUsed"], "flag"=>$isolates_ar["flag"], "comments" => $isolates_ar["comments"], "refAvailID" => $isolates_ar["refAvailID"]));
			$wellID_set = $wellID_set . $isolates_ar["wellID"] . "','";
			$setcount++;
		}
		
		$wellID_set = $gfunc_obj->reset_set( $setcount, $wellID_set );
		
		$well_rs = mysql_query("SELECT * FROM `Wells_tbl` WHERE `status`='ACTIVE' AND `wellID` IN " . $wellID_set . "", $conn) or die("FAILURE IN Location_Output_Class.printIsolates_ContainerView(2):" . mysql_error() );

		$contID_set = "('";
		$setcount = 0;

		while ($well_ar = mysql_fetch_array($well_rs, MYSQL_ASSOC))
		{
			$isolate_tmp = $isolates_ht->get($well_ar["wellID"]);
			
			$isolate_tmp["wellRow"] = $well_ar["wellRow"];
			$isolate_tmp["wellCol"] = $well_ar["wellCol"];
			$isolate_tmp["containerID"] = $well_ar["containerID"];
			$isolates_ht->update($well_ar["wellID"], $isolate_tmp);

			$contID_set = $contID_set . $well_ar["containerID"] . "','";
			$setcount++;
		}
			
		$contID_set = $gfunc_obj->reset_set( $setcount, $contID_set );
		$contInfo_ht = new HT_Class();
			
		$cont_rs = mysql_query("SELECT a.`containerID`, b.`contGroupName`, a.`contGroupCount`, a.`name`, a.isolate_active FROM `Container_tbl` a, `ContainerGroup_tbl` b WHERE a.`contGroupID`=b.`contGroupID` AND a.`containerID` IN " . $contID_set . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'" , $conn) or die("FAILURE IN Location_Output_Class.printIsolates_ContainerView(3): " . mysql_error());

		while( $cont_ar = mysql_fetch_array( $cont_rs, MYSQL_ASSOC ) )
		{
			$contInfo_ht->add( $cont_ar["containerID"], array("contGroupName" => $cont_ar["contGroupName"], "contGroupCount" => $cont_ar["contGroupCount"], "isoActive" => $cont_ar["isolate_active"], "name" => $cont_ar["name"]));
		}
		
		if ($view == 1)
		{
echo "<TABLE cellpadding=\"2\" cellspacing=\"2\" style=\"width:100%;\">";
			$finished_rs = mysql_query("SELECT DISTINCT a.`wellID`, b.`containerID`, b.`contGroupID`, b.`contGroupCount`, b.`name` FROM `Wells_tbl` a, `Container_tbl` b WHERE a.`containerID`=b.`containerID` AND a.`wellID` IN " . $wellID_set . " ORDER BY b.`containerID`, a.`wellID`", $conn) or die("FAILURE IN Location_Output_Class.printIsolates_ContainerView(4): " . mysql_error());

			$currentContGroupID = 0;

			$contInfo_tmp = array();
			$toPrint_ar = array();

// 			// Feb. 16/10: discovered this today: there's an expID but no isolate (prob. result of incomplete db deletion but still, this could happen, keep error msg)
// 			if (!mysql_fetch_array($finished_rs, MYSQL_ASSOC))
// 			{
// 				echo "<TR><TD>";
// 					echo "No location was found for <a href=\"Reagent.php?View=6&rid=" . $rid . "\">" . $gfunc_obj->getConvertedID_rid($rid) . "</a> in OpenFreezer<sup>TM</sup>";
// 				echo "</TD></TR>";
// 			}

			while ($finished_ar = mysql_fetch_array($finished_rs, MYSQL_ASSOC))
			{
				echo "<TR><TD>";

				$toPrint_ar = $isolates_ht->get( $finished_ar["wellID"] );
				$contInfo_tmp = $contInfo_ht->get($finished_ar["containerID"]);
				
				if ($currentContGroupID != $finished_ar["contGroupID"])
				{
// 					echo "<P><HR></P>";
				}	// FIX: this brace was initially after the "$currentContGroupID = $finished_ar["contGroupID"];" assignment => was not printing name and number correctly
					// Marina Olhovsky, July 28, 2005

				// Updated Jan 30/06, Marina
				if ($contInfo_tmp["name"] != "NONE")
				{
					echo "Name of container: <strong>" . $contInfo_tmp["name"] . "</strong><BR>";
				}
				else	
				{
					echo "Name of container: <strong>" . $contInfo_tmp["contGroupName"] . "</strong><BR>";
					echo "Container number: <strong>" . $contInfo_tmp["contGroupCount"] . "</strong><BR>";
				}
				
				$currentContGroupID = $finished_ar["contGroupID"];
		
				// Added by Marina on August 1, 2005
				echo "\n<a href=\"Location.php?View=2&Mod=" . $finished_ar["containerID"] . "&Row=" . $toPrint_ar["wellRow"] . "&Col=" . $toPrint_ar["wellCol"] . "#focus_" . $toPrint_ar["wellRow"] . "_" . $toPrint_ar["wellCol"] . "\">" . $gfunc_obj->getConvertedID_rid( $rid );
				
				if( $contInfo_tmp["isoActive"] == "YES" )
				{
					echo "-" . $toPrint_ar["isolateNumber"] . "</a>&nbsp;";
				}
				else
				{
					echo "</a>&nbsp;";
				}
				
				if ($toPrint_ar["beingUsed"] == "YES")
				{
					echo "(Currently set Isolate Being Used)";
				}
				
				// Jan. 22/08: Order clones
				echo $lfunc_obj->getLetterRow($toPrint_ar["wellRow"]) . " : " . $toPrint_ar["wellCol"];

				$inOrder = $lfunc_obj->inOrder($rid, $toPrint_ar["isolateNumber"], $toPrint_ar["containerID"], $toPrint_ar["wellRow"], $toPrint_ar["wellCol"]);

				// Updated Jan. 30/08: ONLY show "order" option for GS Vector locations
				if (($currentContGroupID == $_SESSION["contGroupNames"]["Glycerol Stocks"]) && ($rfunc_obj->getType($rid) == $_SESSION["ReagentType_Name_ID"]["Vector"]))
				{
					// March 26/08: Show if sample is contaminated (flagged) - disallow order
					if ($toPrint_ar["flag"] != "YES")
					{/*
						echo "<BR><span style=\"color:#FF0000; font-size:9pt; font-weight:bold\">Flagged – do not use as contaminated</span><BR>";
					}
					else
					{*/
						$displayOrderLink = ($inOrder == 0) ? "inline" : "none";
						$displayInOrder = ($inOrder == 1) ? "inline" : "none";
					
						echo "<span class=\"linkShow\" NAME=\"addToOrder\" ID=\"orderClone_" . $rid . "_" . $toPrint_ar["isolateNumber"] . "_" . $toPrint_ar["containerID"] . "_" . $toPrint_ar["wellRow"] . "_" . $toPrint_ar["wellCol"] . "\" NAME=\"orderClone\" style=\"padding-left:25px; display:" . $displayOrderLink . "\" onClick=\"addToOrder('" . $rid . "', '" . $toPrint_ar["isolateNumber"] . "', '" . $toPrint_ar["containerID"] . "', '" . $toPrint_ar["wellRow"] . "', '" . $toPrint_ar["wellCol"] . "')\">Add to Order</span>";
		
						echo "<span class=\"linkOrdered\" ID=\"inOrder_" . $rid . "_" . $toPrint_ar["isolateNumber"] . "_" . $toPrint_ar["containerID"] . "_" . $toPrint_ar["wellRow"] . "_" . $toPrint_ar["wellCol"] . "\" style=\"padding-left:25px; display:" . $displayInOrder . "\">In Order</span>";
						
						echo "<BR/>";

						$canOrder = true;	// May 13, 2011
					}
				}

				if ($toPrint_ar["flag"] == "YES")
				{
					// May 18, 2010: Printing Reference at Anna's request
					echo "<BR><span style=\"color:#FF0000; font-size:9pt; font-weight:bold\">" . trim($toPrint_ar["comments"]);

					if (trim($toPrint_ar["refAvailID"]) != trim($toPrint_ar["comments"]))
					{
						echo "&nbsp;" . trim($toPrint_ar["refAvailID"]);
					}

 					echo "</span><BR>";
				}

				echo "</td></tr>";
				unset($contInfo_tmp, $toPrint_ar);
			}

			if ($canOrder)
			{		
				// May 13, 2011
				echo "<TR><TD colspan=\"100%\" style=\"color:#FF0000; font-weight:bold; padding-left:0;\"><P><u>NOTE</u>:<BR>You can search for more clones and add them to your order.<BR>Once all the clones of interest have been added, press <a href=\"" . $hostname . "User.php?View=8\">View your orders</a> on the side menu and follow the instructions to submit your request.</TD></TR>";
			}

			echo "</table>";
		}
		else
		{
			echo "<table cellpadding=\"2\" cellspacing=\"2\" width=\"100%\">";

			$finished_rs = mysql_query("SELECT a.`isolate_pk`, a.`isolateNumber`, a.`beingUsed`, b.`wellID` FROM `Isolate_tbl` a, `Prep_tbl` b WHERE a.`isolate_pk`=b.`isolate_pk` AND a.`expID`='" . $expID. "' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY a.`isolateNumber`", $conn) or die("FAILURE IN Location_Output_Class.printIsolates_ContainerView(1):" . mysql_error());

			$currentIsolateID = 0;
			
			while ($finished_ar = mysql_fetch_array($finished_rs, MYSQL_ASSOC))
			{
				echo "<tr><td>";

				$toPrint_ar = array();
				$toPrint_ar = $isolates_ht->get($finished_ar["wellID"]);
				$contInfo_tmp = $contInfo_ht->get($toPrint_ar["containerID"]);

				// March 26/08: Get container type (in order to show "Order" link on this view)
				$currentContGroupName = $contInfo_tmp["contGroupName"];
				$currentContGroupID =  $lfunc_obj->convertContGroupName_to_ID($currentContGroupName);

				if ($currentIsolateID != $finished_ar["isolateNumber"])
				{
					# Modified May 22/07 by Marina
					# Link the Vector back to its Detailed View, and link to location off the plate name below
					echo "\n<a href=\"Reagent.php?View=6&rid=" . $rid . "\">" . $gfunc_obj->getConvertedID_rid($rid);	# May 22/07
						
					if( $contInfo_tmp["isoActive"] == "YES" )
					{
						echo "-" . $toPrint_ar["isolateNumber"] . "</a>";
					}
					else
					{
						echo "</a>";
					}
					
					if( $toPrint_ar["beingUsed"] == "YES" )
					{
						echo "&nbsp;&nbsp;(Currently set Isolate Being Used)";
					}
					
					echo "<br>";
					$currentIsolateID = $finished_ar["isolateNumber"];
					echo "<BR>";
				}
				
				# Changed May 22/07, Marina - Output full container name instead of a meaningless number
				echo $contInfo_tmp["name"] . ", well ";
				echo "<A HREF=\"Location.php?View=2&Mod=" . $toPrint_ar["containerID"] . "&Row=" . $toPrint_ar["wellRow"] . "&Col=" . $toPrint_ar["wellCol"] . "#focus_" . $toPrint_ar["wellRow"] . "_" . $toPrint_ar["wellCol"] . "\">";
				echo $lfunc_obj->getLetterRow($toPrint_ar["wellRow"]) . ":" . $toPrint_ar["wellCol"];
				echo "</A>";
				
				// March 26/08: Add "Order clones" btn
				$inOrder = $lfunc_obj->inOrder($rid, $toPrint_ar["isolateNumber"], $toPrint_ar["containerID"], $toPrint_ar["wellRow"], $toPrint_ar["wellCol"]);

				// Jan. 30/08: ONLY show "order" option for GS Vector locations
				if (($currentContGroupID == $_SESSION["contGroupNames"]["Glycerol Stocks"]) && ($rfunc_obj->getType($rid) == $_SESSION["ReagentType_Name_ID"]["Vector"]))
				{
					// March 26/08: Show if sample is contaminated (flagged) - disallow order
					if ($toPrint_ar["flag"] != "YES")
					{
						$displayOrderLink = ($inOrder == 0) ? "inline" : "none";
						$displayInOrder = ($inOrder == 1) ? "inline" : "none";
					
						echo "<span class=\"linkShow\" NAME=\"addToOrder\" ID=\"orderClone_" . $rid . "_" . $toPrint_ar["isolateNumber"] . "_" . $toPrint_ar["containerID"] . "_" . $toPrint_ar["wellRow"] . "_" . $toPrint_ar["wellCol"] . "\" NAME=\"orderClone\" style=\"padding-left:25px; display:" . $displayOrderLink . "\" onClick=\"addToOrder('" . $rid . "', '" . $toPrint_ar["isolateNumber"] . "', '" . $toPrint_ar["containerID"] . "', '" . $toPrint_ar["wellRow"] . "', '" . $toPrint_ar["wellCol"] . "')\">Add to Order</span>";
		
						echo "<span class=\"linkOrdered\" ID=\"inOrder_" . $rid . "_" . $toPrint_ar["isolateNumber"] . "_" . $toPrint_ar["containerID"] . "_" . $toPrint_ar["wellRow"] . "_" . $toPrint_ar["wellCol"] . "\" style=\"padding-left:25px; display:" . $displayInOrder . "\">In Order</span>";
						
						echo "<BR/>";

						$canOrder = true;	// May 13, 2011
					}
				}

				// Show "flagged" notification for all reagent types
				if ($toPrint_ar["flag"] == "YES")
				{
					echo "<BR><span style=\"color:#FF0000; font-size:9pt; font-weight:bold\">" . $toPrint_ar["comments"] . "</span><BR>";

					if (strcasecmp(trim($toPrint_ar["refAvailID"]), trim($toPrint_ar["comments"])) != 0)
					{
						echo "&nbsp;" . trim($toPrint_ar["refAvailID"]);
					}

					echo "</span><BR>";
				}

				echo "<BR>";
				echo "</td></tr>";
				unset( $contInfo_tmp, $toPrint_ar );
			}


			// May 13, 2011
			if ($canOrder)
			{		
				// May 13, 2011
				echo "<TR><TD colspan=\"100%\" style=\"color:#FF0000; font-weight:bold; padding-left:0;\"><P><u>NOTE</u>:<BR>You can search for more clones and add them to your order.<BR>Once all the clones of interest have been added, press 'View your orders' on the side menu and follow the instructions to submit your request.</TD></TR>";
			}

			echo "</table>";
		}
		
		unset( $gfunc_obj, $lfunc_obj, $contInfo_ht, $isolates_ht );
	}
	
	/**
	 * Print container type selection list with the appropriate container type selected according to POST variables.  Print list of containers in the selected type.  Used on CONTAINER SEARCH VIEW (Location.php?View=2)
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param Array $postvars
	*/
	function printForm($postvars)
	{
		?>
		<!-- Updated Aug. 28/08: Show list of plates for selected container type -->
		<FORM name="printContainersOfOneTypeForm" method=post action="<?php echo $_SERVER["PHP_SELF"] . "?View=2"; ?>">
			<FIELDSET>
				<LEGEND>Please select a container for viewing, modification, and insertion from one of the following container types:</LEGEND>

				<select name="cont_name_selection" id="cont_name_selection" size=1 onChange="this.form.submit()">
					<option>No container selected</option>
					
					<?php
					foreach ($_SESSION["Container_ID_Name"] as $key => $value)
					{
						if ($postvars["cont_name_selection"] == $value)
						{
							echo "<option selected>" . $value . "</option>\n";
						}
						else
						{
							echo "<option>" . $value . "</option>\n";
						}
					}
					?>
				</select>
			</FIELDSET>
			
			<!-- May 23/07, Marina: Add a hidden field to store name of column to sort by -->
			<INPUT TYPE="hidden" id="sortByCol_hidden" name="sortOn">
		</FORM>
		<?php

		if (isset($_POST["cont_name_selection"]) && $_POST["cont_name_selection"] != "No container selected")
		{
			$loc_funct_obj = new Location_Funct_Class();
			$loc_funct_obj->printContainerInfo($_SESSION["Container_Name_ID"][$_POST["cont_name_selection"]]);
			unset( $loc_funct_obj );
		}
	}

	/**
	 * Print container type selection list with the appropriate container type selected according to POST variables.  Print list of containers in the selected type.
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param Array $postvars
	*/
	function printPlateList($contType, $selPlate=false)
	{
		global $conn;

		$contGroupID = $_SESSION["Container_Name_ID"][$contType];

		$contInfo_rs = mysql_query("SELECT * FROM `Container_tbl` a, `ContainerTypeID_tbl` b, LabInfo_tbl l WHERE a.`contGroupID`='" . $contGroupID . "' AND a.`contTypeID`=b.`contTypeID` AND l.labID=a.labID AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND l.status='ACTIVE' ORDER BY `name`", $conn) or die("ERROR reading from container table sql error" . mysql_error());

		echo "<P><SELECT ID=\"plates_list\" NAME=\"platesList\" onChange=\"this.form.submit();\">";
		echo "<OPTION>-- Select Plate --</OPTION>"; 

		while ($contInfo_ar = mysql_fetch_array($contInfo_rs, MYSQL_ASSOC))
		{
			echo "<OPTION  " . ((strcasecmp($selPlate, $contInfo_ar["name"]) == 0) ? "selected" : "") . ">" . $contInfo_ar["name"] . "</OPTION>";
		}

		echo "</SELECT>";

		echo "</FIELDSET>";
	}

	/**
	 * Get expID that corresponds to $rid
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param INT $rid
	*/
	function getExpID($rid)
	{
		global $conn;
		
		$exp_rs = mysql_query("SELECT `expID` FROM `Experiment_tbl` WHERE `reagentID`='" . $rid . "' AND status='ACTIVE'" , $conn) or die("Error in grabbing expid" . mysql_error());

		if ($exp_ar = mysql_fetch_array($exp_rs, MYSQL_ASSOC))
		{
			return $exp_ar["expID"];
		}
		
		return -1;
	}
	
	/**
	 * Print detailed view of the container identified by $contID
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param INT $contID
	 * @uses outputPlateView_restricted()
	*/
	function outputPlateView_contID($contID)
	{
		$this->outputPlateView_restricted("WHERE a.`containerID`='" . $contID . "' ");
	}

	/**
	 * Print detailed view of the container identified by $groupID and $plateNum (passes different query to outputPlateView_restricted())
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param INT $groupID
	 * @param INT $plateNum
	 *
	 * @uses outputPlateView_restricted()
	*/
	function outputPlateView_groupID($groupID, $plateNum)
	{
		$this->outputPlateView_restricted("WHERE a.`contGroupID`='" . $groupID . "' " . "AND a.`contGroupCount`='" . $plateNum . "' ");
	}

	/**
	 * Print a list of container types ('Glycerol Stock', 'Liquid Nitrogen', etc.) on the CONTAINER TYPE SEARCH VIEW (as opposed to container search view) - Location.php?View=6&Sub=4
	 *
	 * Note: Variable name 'Container_ID_Name' is kept for historical reasons; a better name would be Container**Type**_ID_Name to show that we are selecting container types.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	*/
	function printContainerTypesList()
	{
		global $conn;
		
		?>
		<FORM NAME="searchContainerTypes" METHOD="POST" ACTION="<?php echo $_SERVER["PHP_SELF"] . "?View=6&Sub=4"; ?>">
			<FIELDSET style="width:745px;">
				<LEGEND>Please select a container type from the list:</LEGEND>
	
				<SELECT NAME="contTypeList" style="font-size:8pt;">
					<OPTION>-- Container Type --</OPTION>
					<?php
						foreach ($_SESSION["Container_ID_Name"] as $cTypeID => $cTypeName)
						{
							echo "<OPTION VALUE=\"" . $cTypeID . "\">" . $cTypeName . "</OPTION>";
						}
					?>
				</SELECT>

			<INPUT TYPE="SUBMIT" NAME="viewContainerType" VALUE="Go">
		</FORM>
		<?php
	}

	/**
	 * Print the detailed view of the container type that corresponds to $selContType in either 'view' or 'modify' mode depending on the value of $modify_state.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	*/
	function print_Detailed_Cont_Type($selContType, $modify_state=false)
	{
		global $conn;
		global $cgi_path;

		$lfunc_obj = new Location_Funct_Class();
		$modify_disabled = "";

		if ($lfunc_obj->isUsedContainerType($selContType))
		{
			$modify_disabled = "DISABLED";
		}

		?><FORM METHOD="POST" ACTION="<?php echo $cgi_path . "location_type_request_handler.py"; ?>">
		<table width="765px" cellpadding="3" cellspacing="0" style="background-color:#FEFEEE; border:1px solid gold; padding:10px;">
			<TR>
				<TD style="text-align:center; padding-bottom:5px; font-weight:bold; font-size:12pt; white-space:nowrap; border-bottom:2px solid gold; color:maroon;" colspan="2">
					<INPUT type="hidden" NAME="containerType" VALUE="<?php echo $selContType; ?>">
					<?php echo $_SESSION["Container_ID_Name"][$selContType]; ?> Container Type Details
				</TD>

				<TD style="text-align:right; font-weight:bold; padding-bottom:5px; font-size:12pt; white-space:nowrap; border-bottom:2px solid gold;" colspan="2"><?php

				if ($modify_state)
				{
					?><input type="submit" name="cont_type_save" value="Save" onClick="enableSelect(); enableText(); selectAllElements('cont_dest_features'); selectAllElements('reagentTypesList'); return verifyContainerType();">

					<input type="submit" name="cont_type_delete" <?php echo $modify_disabled;?> value="Delete" onClick="return confirm('Are you sure you want to delete this container type?  Deletion cannot be undone.');">

					<input type="submit" name="cancel_cont_type_modify" value="Cancel"><?php
				}
				else
				{
					?><INPUT TYPE="submit" VALUE="Modify" style="text-align:right; padding-right:10px;" NAME="cont_type_modify"><?php
				}

				?></TD>
			</TR>
			<?php
			if ($modify_state)
			{
				?><tr>
					<td style="white-space:nowrap; width:150px; padding-top:10px; padding-bottom:8px; border-bottom:2px solid gold; font-weight:bold; padding-top:10px; padding-bottom:8px; border-bottom:2px solid gold;" colspan="3">
						Name: <font size="3" face="Helvetica" color="FF0000"><b>*</b></font>&nbsp;&nbsp;&nbsp;

						<INPUT style="padding-left:2px;" type="text" size="25" name="cont_group_name_field" ID="containerTypeName" onKeyPress="return disableEnterKey(event);" <?php echo $modify_disabled; ?> VALUE="<?php echo $_SESSION["Container_ID_Name"][$selContType]; ?>">
					</td>
				</tr><?php
			}

			if ($modify_state)
			{
				?>
				<tr>
					<td style="white-space:nowrap; padding-top:10px; padding-bottom:10px; font-weight:bold;" colspan="3">
						Reagent types:<BR>
						<SPAN style="font-weight:normal; color:black; font-size:9pt; padding-top:2px;">Specify the reagent types that may be stored in this container.  Other reagent types will be disallowed.</SPAN>
					</td>
				</tr>

				<tr>
					<td colspan="3">
						<table>
							<TR>
								<td style="padding-left:6px; padding-bottom:10px; border-bottom:2px solid gold;" colspan="3">
									<select ID="reagentTypesList" MULTIPLE size="5" name="reagent_types" style="margin-left:4px; padding-left:1px;">
									<?php
					
										$allowed_r_types = $lfunc_obj->getContainerReagentTypes($selContType);
			
										foreach ($allowed_r_types as $key => $rTypeID)
										{
											$rType = $_SESSION["ReagentType_ID_Name"][$rTypeID];
		
											// June. 22, 2010
											if ($lfunc_obj->isUsedContainerReagentType($selContType, $rTypeID))
												echo "<option DISABLED value=\"" . $rTypeID . "\">" . $rType . "</option>";
											else
												echo "<option value=\"" . $rTypeID . "\">" . $rType . "</option>";
										}
									?>
									</select><BR>
			
									<INPUT TYPE="CHECKBOX" ID="selectAllReagentTypesContainer" style="margin-top:10px;" onClick="selectAllContainerProperties(this.id, 'reagentTypesList', false)"><span style="font-size:9pt;">Select All</span></INPUT>
								</TD>

								<!-- March 5/10: make 2 lists -->
								<TD style="padding-left:5px">
									<!-- buttons -->
									<INPUT TYPE="BUTTON" VALUE="<<" NAME="addBtn[]" style="font-size:7pt;" onclick="moveListElements('reagentTypesPool', 'reagentTypesList', false, true);"><BR>
			
									<P><INPUT TYPE="BUTTON" NAME="rmvBtn[]" STYLE="font-size:7pt;" style="font-size:8pt;" VALUE=">>" onclick="moveListElements('reagentTypesList', 'reagentTypesPool', false, false);"><BR><BR><BR>
								</TD>

								<td style="padding-left:6px; padding-bottom:10px; border-bottom:2px solid gold;" colspan="3">
									<select ID="reagentTypesPool" MULTIPLE size="5" style="margin-left:4px; padding-left:1px;">
									<?php
										// Feb. 9-16, 2010
										$allowed_r_types = $lfunc_obj->getContainerReagentTypes($selContType);

										foreach ($_SESSION["ReagentType_ID_Name"] as $rTypeID => $rType)
										{
											if (!in_array($rTypeID, array_values($allowed_r_types)))
												echo "<option value=\"" . $rTypeID . "\">" . $rType . "</option>";
										}
									?>
									</select><BR>
			
									<INPUT TYPE="CHECKBOX" ID="selectAllReagentTypesContainer" style="margin-top:10px;" onClick="selectAllContainerProperties(this.id, 'reagentTypesList', false)"><span style="font-size:9pt;">Select All</span></INPUT>
								</td>
							</tr>
						</table>
					</TD>
				</tr>
			
				<tr>
					<td style="padding-top:10px; padding-right:15px; padding-bottom:8px; border-bottom:2px solid gold; font-weight:bold;" colspan="3">
						Container Code:<SPAN style="margin-left:4px; font-size:11pt; font-face:Helvetica; font-weight:bold; color:#FF0000;">*</SPAN>&nbsp;&nbsp;&nbsp;

						<INPUT style="padding-left:2px;" type="text" size="5" value="<?php echo $_SESSION["ContainerTypeID_Code"][$selContType]; ?>" <?php echo $modify_disabled; ?> ID="containerCode" name="cont_cont_code_field" onKeyPress="return disableEnterKey(event);"><BR>

						<SPAN style="font-weight:normal; font-size:9pt;">A unique two-letter identifier for this container type (e.g. 'MP' for MiniPrep Plates, or 'GS' for Glycerol Stock containers)</SPAN><BR><P><HR><P>

						<SPAN style="font-weight:normal; font-size:9pt;">The following container codes, currently used in OpenFreezer, <u>may NOT be reused</u>:</SPAN><BR>

						<TABLE border="0">
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

						?></UL></TR></TABLE>
					</td>
				</tr>
				<?php
			}
			else
			{

				?>
				<tr>
					<td style="white-space:nowrap; font-weight:bold; padding-top:10px; padding-bottom:8px; border-bottom:2px solid gold;" colspan="4">
						Container Code:&nbsp;&nbsp;
						<?php echo $_SESSION["ContainerTypeID_Code"][$selContType]; ?>
					</td>
				</tr>
				<?php
			}
				?>
				<tr>
					<td style="padding-top:10px; padding-bottom:10px; padding-left:5px; border-bottom:2px solid gold;" colspan="3">
						<B>Isolate Active:</b>&nbsp;&nbsp;<?php

						if ($modify_state)
						{
							$isoActive = $lfunc_obj->isolateActive($selContType);

							if ($lfunc_obj->isUsedContainerType($selContType))
								$isoDisabled = "DISABLED";
							else
								$isoDisabled = "";

							?>
							<input type="radio" <?php if (strcasecmp($isoActive, "YES") == 0) echo "checked"; ?> <?php echo $isoDisabled; ?> name="cont_cont_isolateActive_radio" value="Yes"> Yes
							<input type="radio" <?php if (strcasecmp($isoActive, "NO") == 0) echo "checked"; ?> <?php echo $isoDisabled; ?> name="cont_cont_isolateActive_radio" value="No"> No
							<BR><SPAN style="font-size:9pt;">Specify whether preps in containers of this type originate from different colony picks (isolates)</SPAN><?php
						}
						else
						{
							echo $lfunc_obj->isolateActive($selContType);
						}
					?>
					</td>
				</tr>
				<?php
					$allowed_r_types = $lfunc_obj->getContainerReagentTypes($selContType);
		
					if (count($allowed_r_types) > 0)
					{
		
						?>
						<tr>
							<td colspan="100%" style="white-space:nowrap; font-weight:bold; padding-top:12px;">
								Reagent types:
							</td>
						</tr>
		
						<TR>
							<td style="padding-left:10px; padding-top:10px; border-bottom:2px solid gold;" colspan="100%">
							<?php
		
							echo "<UL>";
			
							foreach ($allowed_r_types as $rKey => $rTypeID)
								echo "<LI>" . $_SESSION["ReagentType_ID_Name"][$rTypeID] . "</LI>";
		
							echo "</UL>";
		
							?>
							</td>
						</tr>
						<?php
					}
				?>
				<!-- Feb. 12/09: List of features for this container type: -->
				<tr>
					<td style="font-weight:bold; padding-left:5px; padding-top:15px;" colspan="3">
						Container Type Attributes:
					</td>
				</tr>

				<tr>
					<?php
						if ($modify_state)
						{
							?>
							<td style="padding-left:2px; padding-top:10px; white-space:nowrap;">
								<table cellpadding="2" style="border: 1px groove gray; padding-top:5px; padding-bottom:5px; padding-left:5px; padding-right:5px; width:80%;" frame="box" rules="none">
									<tr>
										<TD style="font-size:9pt; font-weight:bold; text-align:left; padding-left:15px;">
											Attributes assigned to containers of this type:
										</td>
		
										<TD width="78px;">&nbsp;</TD>
		
										<TD style="text-align:center; font-size:9pt; font-weight:bold;">
											Additional container attributes<BR> available in OpenFreezer:
										</TD>
									</TR>
		
									<TR>
										<td style="white-space:nowrap; width:180px; padding-left:10px;">

											<SELECT SIZE="10" MULTIPLE NAME="container_features" ID="cont_dest_features" style="min-width:150px;">
											<?php

											$props_rs = mysql_query("SELECT p.elementTypeID, p.propertyName FROM PrepElemTypes_tbl p, ContainerTypeAttributes_tbl c WHERE c.containerTypeID='" . $selContType . "' AND c.containerTypeAttributeID=p.elementTypeID AND c.status='ACTIVE' AND p.status='ACTIVE'") or die("Error fetching container attributes");
				
											$usedContTypeProps = Array();

											while ($props_ar = mysql_fetch_array($props_rs, MYSQL_ASSOC))
											{
												$propertyName = $props_ar["propertyName"];
												$elementTypeID = $props_ar["elementTypeID"];

												$usedContTypeProps[] = $propertyName;

												$disabled = "";

												if ($lfunc_obj->isUsedPrepElement($selContType, $elementTypeID))
													$disabled = "DISABLED";
												
												// Update Jan. 14, 2010: pass the property NAME to Python, not the value!
												echo "<OPTION " . $disabled . " VALUE=\"" . $propertyName . "\" NAME=\"" . $propertyName . "\">" . $propertyName . "</OPTION>";
											}
				
											?>
											</SELECT>
										
											<P><INPUT TYPE="checkbox" onClick="selectAll(this.id, 'cont_dest_features')" id="add_all_chkbx_dest"> Select All</INPUT>
										</td>
									
										<td style="text-align:center; padding-left:10px; padding-right:10px;">
											<input style="margin-top:10px;" onclick="moveListElements('cont_src_features', 'cont_dest_features', false)" value="<<" type="button"></INPUT><BR><BR>
		
											<input onclick="moveListElements('cont_dest_features', 'cont_src_features', false)" value=">>" type="button"></INPUT><BR><BR><BR><BR>
										</td>
		
										<td style="white-space:nowrap; padding-left:20px;">
										<?php
											$props_rs = mysql_query("SELECT elementTypeID, propertyName FROM PrepElemTypes_tbl WHERE status='ACTIVE' ORDER BY propertyName") or die("Error fetching container attributes");
				
											echo "<SELECT SIZE='10' MULTIPLE ID='cont_src_features' style='min-width:180px;'>";
				
											while ($props_ar = mysql_fetch_array($props_rs, MYSQL_ASSOC))
											{
												$propertyName = $props_ar["propertyName"];
												$elementTypeID = $props_ar["elementTypeID"];
				
												if (!in_array($propertyName, $usedContTypeProps))
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
							<?php
						}
						else
						{
							?><TD><?php

							$contTypeAttrs = $lfunc_obj->getContainerTypeAttributes($selContType);

							if (count($contTypeAttrs) > 0)
							{
								echo "<UL>";

								foreach ($contTypeAttrs as $key => $value)
								{
									echo "<LI>" . $value . "</LI>";
								}

								echo "</UL>";
							}

							?></td><?php
						}
					?>
				</tr>
			</table>
		</FORM>
		<?php
	}


	/**
	 * Print an error message.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-02-24
	 *
	 * @param STRING $err_msg
	*/
	function printErrPage($err_msg)
	{
		echo "<TABLE style=\"width:98%;\">";
		echo "<TR><TD style=\"font-weight:bold; font-size:24pt;\">";
		echo $err_msg;
		echo "</TD></TR>";
		echo "</TABLE>";
	}

	/**
	 * Print the detailed view of a conatiner
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-02-24
	 *
	 * @param STRING $selected_SQL_statement
	*/
	function outputPlateView_restricted($selected_SQL_statement)
	{
		global $conn;
		global $cgi_path;	// jan. 3, 2010
		global $hostname;	// sept. 11/07

		$gfunc_obj = new generalFunc_Class();
		$lfunc_obj = new Location_Funct_Class();
		$locWells_obj = new Location_Well_Class();

		$maxRow_const = 0;
		$maxCol_const = 0;
		$contID_const = 0;
		$groupCount_const = 0;
		$groupID_const = 0;
		
		$groupName_const = "";
		
		$isIsolate_container = "NO";
		
		$general_property_ar = array();
		
		$currentRow = 1;
		$currentCol = 1;
		
		$isIsolate_container = false;
		
		$containerName = "";		// May 23/06, Marina

		// Sept 27, Marina
		if (isset($_SESSION["userinfo"]))
		{
			$currentUserID = $_SESSION["userinfo"]->getUserID();
		}

		// Grab the container basic information
		$containerID_rs = mysql_query("SELECT a.`containerID`, a.`contGroupCount`, a.`contGroupID`, a.`contTypeID`, a.`name`, c.`isolate_active`, b.`maxCol`, b.`maxRow`, a.location, a.labID FROM `Container_tbl` a, `ContainerTypeID_tbl` b, ContainerGroup_tbl c " . $selected_SQL_statement . " AND a.`contTypeID`=b.`contTypeID` AND c.contGroupID=a.contGroupID AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`status`='ACTIVE'", $conn) or die("Error on container groups sql statement: " . mysql_error());
		
		if ($containerID_ar = mysql_fetch_array($containerID_rs, MYSQL_ASSOC))
		{
			$maxRow_const = $containerID_ar["maxRow"];
			$maxCol_const = $containerID_ar["maxCol"];
			$contID_const = $containerID_ar["containerID"];
			$groupCount_const = $containerID_ar["contGroupCount"];
			$groupID_const = $containerID_ar["contGroupID"];
			$containerName = $containerID_ar["name"];
			$contLabID = $containerID_ar["labID"];
			$contTypeID = $containerID_ar["contTypeID"];
			
			if ($containerID_ar["isolate_active"] == "YES")
			{
				$isIsolate_container = true;
			}
			else
			{
				$isIsolate_container = false;
			}
		}
		else
		{
			return false;
		}
		
		// Added June 5/06 by Marina
		$maxGroupCount_rs = mysql_query("SELECT MAX(`contGroupCount`) AS maxCount FROM `Container_tbl` WHERE `contGroupID`='" . $groupID_const . "' AND `status`='ACTIVE'", $conn) or die("Error selecting max group count: " . mysql_error());

		if ($maxGroupCount_ar = mysql_fetch_array($maxGroupCount_rs, MYSQL_ASSOC))
		{
			$maxGroupCount = $maxGroupCount_ar["maxCount"];
		}

		mysql_free_result($containerID_rs);
		unset($containerID_rs, $containerID_ar);
		
		$find_group_name_rs = mysql_query("SELECT `contGroupName` FROM `ContainerGroup_tbl` WHERE `contGroupID`='" . $groupID_const . "' AND `status`='ACTIVE'", $conn) or die("Error in grabbing SQL group name: " . mysql_error());
	
		if( $find_group_name_ar = mysql_fetch_array( $find_group_name_rs, MYSQL_ASSOC ) )
		{
			$groupName_const = $find_group_name_ar["contGroupName"];
		}
		else
		{
			return false;
		}
		
		mysql_free_result( $find_group_name_rs );
		unset( $find_group_name_rs, $find_group_name_ar );
		
		$general_property_set = "('";
		$setcount = 0;
	
		$sql = "SELECT p.elementTypeID, p.propertyName FROM PrepElemTypes_tbl p, ContainerTypeAttributes_tbl c WHERE c.containerTypeID='" . $groupID_const . "' AND c.containerTypeAttributeID=p.elementTypeID AND c.status='ACTIVE' AND p.status='ACTIVE' UNION (SELECT DISTINCT c.prepElementTypeID, b.propertyName FROM ContainerTypeAttributes_tbl a, PrepElemTypes_tbl b, Prep_Req_tbl c WHERE a.containerTypeAttributeID=b.elementTypeID AND a.containerTypeID='" . $groupID_const . "' AND c.prepElementTypeID=b.elementTypeID AND c.containerID='" . $contID_const . "' AND a.status='ACTIVE' AND b.status='ACTIVE' AND c.status='ACTIVE') ORDER BY elementTypeID";

		// Section that finds the cell property requirements for the container
		$find_cell_info_rs = mysql_query($sql, $conn) or die("Failure in grabbing container cell info sql statement:" . mysql_error());
		
		while ($find_cell_info_ar = mysql_fetch_array($find_cell_info_rs, MYSQL_ASSOC))
		{
			$general_property_ar[$find_cell_info_ar["propertyName"]] = $find_cell_info_ar["elementTypeID"];
			$setcount++;
			$general_property_set = $general_property_set . $find_cell_info_ar["elementTypeID"] . "','";
		}
		
		$general_property_set = $this->reset_set( $setcount, $general_property_set );
		$setcount = 0;
		
		mysql_free_result( $find_cell_info_rs );
		unset( $find_cell_info_rs, $find_cell_info_ar );
		
		$isolate_set = "('";
		$prepID_set = "('";
		
		$prep_info_ht = new HT_Class();
		
		// $plate_id_ar will hold all wellID's for every well spot in the container
		$plate_id_ar = array();
		
		// Temporary wellID array for the given row
		$plate_row_tmp_ar = array();
		
		// Temporary row counter
		$row_count = 1;
		
		// SQL Select statement that grabs all the Prep default information
		$find_prep_rs = mysql_query( "SELECT a.`prepID`, a.`wellID`, a.`isolate_pk`, a.`refAvailID`, a.`flag`, a.`comments`, b.`wellCol`, b.`wellRow`"
			. "FROM `Prep_tbl` a INNER JOIN `Wells_tbl` b "
			. "ON a.`wellID`=b.`wellID` "
			. "WHERE b.`containerID`='" . $contID_const . "' "
			. "AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' "
			. "ORDER BY b.`wellRow`, b.`wellCol` ", $conn ) 
			or die( "Failure in prep sql statement:" . mysql_error() );;

		while ($find_prep_ar = mysql_fetch_array($find_prep_rs, MYSQL_ASSOC))
		{
			// Temporary array container for all prep info
			$prep_tmp_ar = array();
			
			if ($find_prep_ar["wellRow"] > $row_count || ($find_prep_ar["wellRow"] == $maxRow_const && $find_prep_ar["wellCol"] == $maxCol_const))
			{
				// next row!

				if( !( $find_prep_ar["wellRow"] == $maxRow_const && $find_prep_ar["wellCol"] == $maxCol_const ) )
				{
					$plate_id_ar[$row_count] = $plate_row_tmp_ar;
				
					$plate_row_tmp_ar = array();
					$row_count = $find_prep_ar["wellRow"];
				}
			}

			// Should alway be adding the next element into the array
			$plate_row_tmp_ar[ $find_prep_ar["wellCol"] ] = $find_prep_ar["wellID"];
			
			$setcount++;
			$isolate_set = $isolate_set . $find_prep_ar["isolate_pk"] . "','";
			$prepID_set = $prepID_set . $find_prep_ar["prepID"] . "','";
			
			$prep_tmp_ar["prepID"] = $find_prep_ar["prepID"];
			$prep_tmp_ar["isolate_pk"] = $find_prep_ar["isolate_pk"];
			$prep_tmp_ar["refAvailID"] = $find_prep_ar["refAvailID"];
			$prep_tmp_ar["flag"] = $find_prep_ar["flag"];
			$prep_tmp_ar["comments"] = $find_prep_ar["comments"];
			$prep_tmp_ar["wellCol"] = $find_prep_ar["wellCol"];
			$prep_tmp_ar["wellRow"] = $find_prep_ar["wellRow"];

			$prep_info_ht->add( $find_prep_ar["wellID"], $prep_tmp_ar );
		}

		// HACK: Need to grab the last element in this container element this way. Cleanest way so far.
		$plate_id_ar[$row_count] = $plate_row_tmp_ar;
		
		$isolate_set = $this->reset_set( $setcount, $isolate_set );
		$prepID_set = $this->reset_set( $setcount, $prepID_set );
		$setcount = 0;
		
		mysql_free_result( $find_prep_rs );
		unset( $find_prep_rs, $find_prep_ar );
		
		$generalProp_ht = new HT_class();

		// SQL Select that finds all the general properties associated with the found prepID's
		$find_gen_rs = mysql_query("SELECT `prepPropID`, `prepID`, `elementTypeID`, `value` FROM `PrepElementProp_tbl` "
		. "WHERE `prepID` IN " . $prepID_set . " AND `elementTypeID` IN " . $general_property_set . " "
		. "AND `status`='ACTIVE' ORDER BY `prepID`, `elementTypeID`", $conn)
		or die( "Failure in trying to get real general properties of container: " . mysql_error());
		
		$temp_ar = array();
		$current_id = 0;
		$new_id = 0;
		
		while( $find_gen_ar = mysql_fetch_array( $find_gen_rs, MYSQL_ASSOC ) )
		{
			if( $current_id == 0 )
			{
				$current_id == $find_gen_ar["prepID"];
			}
			
			$new_id = $find_gen_ar["prepID"];
			
			if( $new_id != $current_id )
			{
				$generalProp_ht->add( $current_id, $temp_ar );
				
				$current_id = $new_id;
				$temp_ar = array();
			}

			$temp_ar[ $find_gen_ar["elementTypeID"] ] = $find_gen_ar["value"];
		}
		
		// Must add the last element hashtable
		$generalProp_ht->add( $current_id, $temp_ar );
		
		$isolate_ht = new HT_Class();
		$reagent_set = "('";
		$find_reagents_rs;
		
		// Finds all the Reagent ID and Isolate information for the given set of Cells
		$find_reagents_rs = mysql_query("SELECT  DISTINCT a.`expID`, a.`reagentID`, b.`isolate_pk`, b.`isolateNumber`, b.`beingUsed` FROM `Experiment_tbl` a INNER JOIN `Isolate_tbl` b ON a.`expID`=b.`expID` WHERE b.`isolate_pk` IN ". $isolate_set . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY b.`isolate_pk`" , $conn) or die( "Failure in grabbing reagent ID SQL statement: " . mysql_error());
		
		while( $find_reagents_ar = mysql_fetch_array( $find_reagents_rs , MYSQL_ASSOC ) )
		{
			$setcount++;
			$reagent_set = $reagent_set . $find_reagents_ar["reagentID"] . "','";
			
			$temp_ar = array();
			$temp_ar["reagentID"] = $find_reagents_ar["reagentID"];
			$temp_ar["isolateNum"] = $find_reagents_ar["isolateNumber"];
			$temp_ar["beingUsed"] = $find_reagents_ar["beingUsed"];
			
			$isolate_ht->add( $find_reagents_ar["isolate_pk"], $temp_ar );
		}
		
		$reagent_set = $this->reset_set($setcount, $reagent_set);
		$setcount = 0;
		
		// Finds the LIMS system Reagent ID information
		$reagentGroup_rs = mysql_query("SELECT DISTINCT `reagentID`, `reagentTypeID`, `groupID` FROM `Reagents_tbl` WHERE `reagentID` IN " . $reagent_set . " AND `status`='ACTIVE' ORDER BY `reagentID`", $conn );
		
		$rGroups_ar = array();
		$rTypes_ar = array();
		
		while( $reagentGroup_ar = mysql_fetch_array( $reagentGroup_rs, MYSQL_ASSOC ) )
		{
			$rTypes_ar[ $reagentGroup_ar["reagentID"] ] = $reagentGroup_ar["reagentTypeID"];
			$rGroups_ar[ $reagentGroup_ar["reagentID"] ] = $reagentGroup_ar["groupID"];
		}

		// August 13/07, Marina: Restrict prep modification by project
		
		// Get the projects this user may modify
		$currUserID = $_SESSION["userinfo"]->getUserID();
		$currUserCategory = $_SESSION["userinfo"]->getCategory();
		$userLabID = $_SESSION["userinfo"]->getLabID();

		$write_disabled = "";

		// admin has unlimited access
		if ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])
		{
			$userProjects = findAllProjects();
		}
		elseif ($currUserCategory == $_SESSION["userCategoryNames"]["Reader"])
		{
			$write_disabled = "DISABLED";
		}
		else
		{
			$userProjects = getUserProjectsByRole($currUserID, 'Writer');
		}

		// FREEEEEEE MEMORRYYYYYYYYYYY
		
		// -------------- Start of Main output area for plate


		// Moved here June 2, 2011
		$contTypeAttribs = $lfunc_obj->getContainerTypeAttributes($groupID_const);
		?>
		<form name="wells_form" method=post action="<?php echo $_SERVER["PHP_SELF"] . "?View=7&Mod=" . $contID_const . ""; ?>">

		<table width="765px" cellpadding="5" border="0">
			<TR>
				<TD colspan="2">
					&nbsp;&nbsp;<a href="<?php echo $_SERVER["PHP_SELF"] . "?View=2&gID=" . $_SESSION["Container_ID_Name"][$groupID_const]; ?>" style="font-size:9pt; font-weight:normal;">View all <?php echo $_SESSION["Container_ID_Name"][$groupID_const]; ?> containers</a><?php
				?></TD>

				<TD style="text-align:right; padding-right:20px; white-space:nowrap;">
				<?php
					$size_rs  = mysql_query( "SELECT `maxCol`, `maxRow` FROM `ContainerTypeID_tbl` WHERE `contTypeID`='" . $contTypeID . "' AND `status`='ACTIVE'" , $conn ) or die("Error reading container original info in SQL statement: " . mysql_error());
				
					if ($size_ar = mysql_fetch_array( $size_rs, MYSQL_ASSOC ) )
					{
						$cols = $size_ar["maxCol"];
						$rows = $size_ar["maxRow"];
					}
				
					$capacity = $cols * $rows;
					$empty_cells = $lfunc_obj->getEmptyCellNum($contID_const, $contTypeID);
					$occupied_cells = $capacity - $empty_cells;

					if (($occupied_cells == 0) && (($contLabID == $userLabID) || ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])) && ($currUserCategory != $_SESSION["userCategoryNames"]["Reader"]))
					{
						?>&nbsp;&nbsp;&nbsp;&nbsp;<a href="<?php echo $_SERVER["PHP_SELF"] . "?View=5&Mod=" . $contID_const; ?>" style="font-size:9pt; font-weight:normal;">Edit Container Information</a><?php
					}

					if ((($currUserCategory != $_SESSION["userCategoryNames"]["Reader"])) && (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || ($contLabID == $userLabID)))
					{
						?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a style="font-size:9pt; font-weight:normal;" href="<?php echo $_SERVER["PHP_SELF"] . "?View=3&Mod=" . $contID_const; ?>">Location Details</a><?php
					}

				?></TD>
			</TR>

			<tr><td colspan="3"><HR></td></tr>

			<tR>
				<td style="width:150px; padding-left:15px;">
				<?php 
					if ($groupCount_const > 1)
					{
						?><a style="font-size:9pt;" href="<?php echo $_SERVER["PHP_SELF"] . "?View=2&Mod=" . $this->get_Previous_ContainerID( $groupCount_const, $groupID_const, $contID_const ) . ""; ?>">Previous</a><?php
					}
					else
					{
						?><span class="linkDisabled" style="font-size:9pt;">Previous</span><?php
					}
				?>
				</td>

				<td style="text-align:center; padding-right:25px; white-space:nowrap; font-size:14px; font-weight:bold; color:#EF4A11;"><?php
					echo strtoupper($containerName);
					?>
				</td>

				<td style="width:120px; text-align:right; padding-right:20px;">
				<?php 
					if ($groupCount_const < $maxGroupCount)
					{
						?><a style="font-size:9pt;" href="<?php echo $_SERVER["PHP_SELF"] . "?View=2&Mod=" . $this->get_Next_ContainerID($groupCount_const, $groupID_const, $contID_const) . ""; ?>">Next</a><?php
					}
					else
					{
						?><span class="linkDisabled" style="font-size:9pt;">Next</span><?php
					}
				?>
				</td>

				</TR>

				<TR><TD colspan="3"><HR></TD></TR>
			</tr>

			<tr>
				<td style="white-space:nowrap;" colspan="3">
					<center>
						<TABLE>
							<TR>
								<TD><input type="submit" style="font-size:9pt;" name="well_new_button" value="Create New Wells" <?php echo $write_disabled; ?> onClick="return verifyWellsSelected();"></TD>
								<TD><input type="submit" style="font-size:9pt;" name="well_modify_button" value="Modify Wells"  <?php echo $write_disabled; ?> onClick="return verifyWellsSelected();"></TD>
								<TD><input type="submit" style="font-size:9pt;" name="well_delete_button" value="Delete Wells"  <?php echo $write_disabled; ?> onClick="return verifyWellsSelected();"></TD>
								
								<!-- May 30, 2011: Removed, done now at the click of the top left corner (implement Excel-like behaviour)
								<TD><input type="button" style="font-size:9pt;" name="well_select_all_button" value="Select All"  <?php echo $write_disabled; ?> onClick="return selectAllWells()"></TD>

								<TD><input type="button" style="font-size:9pt;" name="well_select_all_button" value="Deselect All"  <?php echo $write_disabled; ?> onClick="return clearAllWells()"></TD> -->
								
								<!-- May 30, 2011 -->
								<TD>
									<!-- <input type="submit" style="font-size:9pt; padding-bottom:2px; font-weight:bold;" name="update_selected_attributes" value="Update Selected Attributes"<?php echo $write_disabled; ?>> -->
									<?php
										$contID = $_GET["Mod"];

										$prep_gen_check_rs = mysql_query("SELECT p.elementTypeID, p.propertyName FROM PrepElemTypes_tbl p, ContainerTypeAttributes_tbl c WHERE c.containerTypeID='" . $selContType . "' AND c.containerTypeAttributeID=p.elementTypeID AND c.status='ACTIVE' AND p.status='ACTIVE' UNION (SELECT a.`prepElementTypeID`, b.`propertyName` FROM `Prep_Req_tbl` a INNER JOIN `PrepElemTypes_tbl` b ON a.`prepElementTypeID`=b.`elementTypeID` WHERE a.`containerID`='" . $contID . "' AND a.`requirement`='REQ' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE')") or die("Error fetching container attributes");
										
										$req_set = "('";
										$req_setcount = 0;
										$general_req_ar = array();

										while ($prep_gen_check_ar = mysql_fetch_array($prep_gen_check_rs, MYSQL_ASSOC))
										{
											$req_setcount++;

											$req_set = $req_set . $prep_gen_check_ar["elementTypeID"] . "','";
											$general_req_ar[$prep_gen_check_ar["propertyName"]] = $prep_gen_check_ar["elementTypeID"];
										}

//										print_r($general_req_ar);
									?>
									<SELECT ID="changeAttributeSelect" onChange="if (verifyWellsSelected()) updateWellAttribute(this[this.selectedIndex].value, '<?php echo $contID; ?>');" style="background-color:lightgray; color:navy; font-weight:bold;">
										<OPTION selected value="default">Change attribute value</OPTION>
										<OPTION value="Reference">Reference</OPTION>
										<?php
											foreach ($contTypeAttribs as $a => $attrName)
											{
												echo "<OPTION value=\"" . $attrName . "\">" . $attrName . "</OPTION>";
											}
										?>
										</OPTION>
										<OPTION value="Comments">Comments</OPTION>									
									</SELECT>
									<IMG src="<?php echo $hostname . "pictures/images.jpg"; ?>" HEIGHT="18" width="18" ALT="Help" style="vertical-align:middle; cursor:pointer; padding-bottom:6px;" onClick="return overlib('Select an attribute name from the list to update its value for all selected wells at once.  Empty wells will not be affected.</B>', CAPTION, 'Change Attribute Value', STICKY);"> 
									<IMG SRC="pictures/new01.gif" ALT="new" WIDTH="35" HEIGHT="20" style="cursor:auto;">
								</TD>

								<TD>
									<IMG SRC="pictures/excel.gif" width="13" height="13" style="vertical-align:middle; padding-bottom:2px;">&nbsp;<span class="linkShow" style="font-weight:bold; font-size:10pt;" onClick="document.plate_to_excel.submit()">Download</SPAN>
									<IMG SRC="pictures/new01.gif" ALT="new" WIDTH="35" HEIGHT="20" style="cursor:auto;">
								</TD>
							</TR>
						</TABLE>
					</center>
				</td>
			</tr>

			<!-- May 31, 2011 -->
			<TR><TD colspan="100%" style="font-weight:bold;"><span style="color:red; font-weight:bold;">**NEW**</span>&nbsp;Click on a column or row heading to select it.</TD></TR>

			<TR><TD colspan="100%" style="font-weight:bold;"><span style="color:red; font-weight:bold;">**NEW**</span>&nbsp;Click on the top left corner to select the entire plate.</TD></TR>

			<TR><TD colspan="100%" style="font-weight:bold; color:#EF4A11"><span style="color:red; font-weight:bold;">**</span>&nbsp;Only wells that you are authorized to modify will be highlighted.</TD></TR>

			<TR><TD colspan="3"></TD></TR>

		</table>

		<table style="width:760px" border="1" frame="box" rules="all" cellpadding="2" cellspacing="2">
			<tr>
				<td style="background-color:grey; cursor:pointer; border-top:1px solid gray; border-left:1px solid lightgray; border-bottom:2px outset lightgray; border-right:3px outset lightgray;" onclick="selectOrDeselectAllWells('<?php echo $_GET["Mod"]; ?>', '<?php echo $maxRow_const*$maxCol_const; ?>');" border="0"></td> <!-- For row identifier column --><?php

				for( $i = 1; $i <= $maxCol_const; $i++ )
				{
					echo "<td class=\"plateColumn_hdr\" onClick=\"selectPlateColumn('" . $_GET["Mod"] . "', '" . $i . "')\">" . $i . "</td>";
				}

				?><td class="plateRow_hdr" style="font-size:9pt; cursor:auto; white-space:nowrap; padding-left:8px; padding-right:8px;">Prep Attributes

				</td> <!-- For row identifier cell -->
			</tr><?php

		for ($row = 1; $row <= $maxRow_const; $row++)
		{
			echo "<tr>";

			$wellID_ar_tmp = $plate_id_ar[$row];

			echo "<td class=\"plateRow_hdr\" onClick=\"selectPlateRow('" . $_GET["Mod"] . "', '" . $row . "')\">" . $lfunc_obj->getLetterRow($row) . "</td>";
			
			for ($col = 1; $col <= $maxCol_const; $col++)
			{
				if (strlen($wellID_ar_tmp[$col]) > 0)
				{
					$prep_info_tmp = $prep_info_ht->get($wellID_ar_tmp[$col]);
					$isolate_info_tmp = $isolate_ht->get($prep_info_tmp["isolate_pk"]);
			
					// August 13/07, Marina: If current user does not have write access to the project this reagent belongs to, disallow prep modification - checkbox would be disabled
					$reagentProject = getReagentProjectID($isolate_info_tmp["reagentID"]);
					$disabled = $write_disabled;

					if (($isolate_info_tmp["reagentID"] > 0) && ($reagentProject != 0) && !in_array($reagentProject, $userProjects))
						$disabled = "DISABLED";

					// Feb. 24/10: but not for Admin
					else if (($reagentProject == 0) && ($currUserCategory != $_SESSION["userCategoryNames"]["Admin"]))
						$disabled = "DISABLED";

					// Modified April 20/06 by Marina
					$prep_id_tmp = $gfunc_obj->getConvertedID( $rTypes_ar[ $isolate_info_tmp["reagentID"] ], $rGroups_ar[ $isolate_info_tmp["reagentID"] ] );
	
					// Sept. 11-14, 2007: Make the prep ID a hyperlink back to Reagent view, but disable it if the user does not have Read access to the reagent's project
					
					if ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])
						$readProjects = findAllProjects();
					else
						$readProjects = getAllowedUserProjectIDs($currUserID);

					// Jan. 7, 2010: Restrict access completely if user not project member
					if (in_array($reagentProject, $readProjects))
					{
						# May 22/07, Marina - If navigating from Reagent Group by Isolate view to a specific well, mark it with bright orange colour
						if (isset($_GET["Row"]) && isset($_GET["Col"]) && ($_GET["Row"] == $row) && ($_GET["Col"] == $col))
						{
							?><td ID="well_plate_<?php echo $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col; ?>" style="color:#BA55D3; font-size: x-small; font: Geneva, Arial, Helvetica, sans-serif; border-width:3px; border-style: solid"><?php
						}
						elseif ($prep_info_tmp["flag"] == "YES")
						{
							// Red output - Modified May 19/09: made border thicker
							?><td ID="well_plate_<?php echo $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col; ?>" style="color:#FF0000; font-size: x-small; font:Geneva, Arial, Helvetica, sans-serif; padding-top:2px; padding-bottom:2px; padding-left:5px; padding-right:5px;">
								<DIV style="border: 2px solid #FF0000; padding:3px;"><?php
						}
						elseif ($isolate_info_tmp["beingUsed"] == "YES" && $isIsolate_container)
						{
							// Blue output - Modified May 19/09: made border thicker
							?><td ID="well_plate_<?php echo $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col; ?>" style="color:#0099FF; font-size: x-small; font: Geneva, Arial, Helvetica, sans-serif; padding-top:2px; padding-bottom:2px; padding-left:5px; padding-right:5px;">
								<DIV STYLE="border: 2px solid #0099FF; padding:3px;"><?php
						}
						else
						{
							// Normal black output
							?><td ID="well_plate_<?php echo $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col; ?>" style="font-size: x-small; font: Geneva, Arial, Helvetica, sans-serif; padding-top:2px; padding-bottom:2px; padding-left:5px; padding-right:5px;">
								<DIV style="border:1px solid black; padding:3px;"><?php
						}

						echo "<a href=\"" . $hostname . "Reagent.php?View=6&rid=" . $isolate_info_tmp["reagentID"] . "\">" . $prep_id_tmp;

						if( $isIsolate_container && strlen($prep_id_tmp) > 0)	// apr 20
						{
							echo "-";
							echo $isolate_info_tmp["isolateNum"];
						}
						echo  "</a>";

						// Placing here May 18, 2010, AND using ID instead of Name to recognize the anchor buried inside a table.  THIS WORKS.
						echo "<a ID=\"focus_" . $row . "_" . $col . "\"></a>";	# may 22/07

						echo "<br>";

						echo $prep_info_tmp["refAvailID"] . "<BR>\n";
						
						echo "<BR>";
						$this->printGeneral_property( $generalProp_ht, $prep_info_tmp["prepID"] , "<BR>" );
						echo "<BR>";
						echo $prep_info_tmp["comments"] . "<BR>\n";
						
						# Added May 22/07, Marina: If arriving at this view from the Reagent Group By Isolate view to view the location of a specific prep, mark the location of that prep by selecting its checkbox
						if (isset($_GET["Row"]) && isset($_GET["Col"]) && ($_GET["Row"] == $row) && ($_GET["Col"] == $col))
						{
							echo "\n<input type=checkbox ID=\"plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "\" name=wells_checkbox[] checked value=\"" . $row . "|" . $col . "\" " . $disabled . " onClick=\"highlightWell(this.id, 'well_plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "');\">\n";
						}
						else
						{
							echo "\n<input type=checkbox ID=\"plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "\" name=wells_checkbox[] value=\"" . $row . "|" . $col . "\" " . $disabled . " onClick=\"highlightWell(this.id, 'well_plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "');\">\n";
						}

						echo "</DIV></TD>\n";		// May 19/09, Marina: added closing </DIV>
					}
					else	// Jan. 7, 2010: hide well contents
					{
						?><td style="padding-left:2px; text-align:center; background-color:#E1E1E1;">
							<DIV STYLE="padding-top:35%; width:92%; height:50%; text-align:center; font-weight:bold; font-family:Helvetica; font-size:11pt; color:#8E8E8E;" name="plate_<?php echo $_GET["Mod"]; ?>_wells_restricted[]">RESTRICTED</DIV>
						</TD><?php
					}
				}
				else
				{
					// Added Sept 27, 2005 by Marina, to mark wells that are reserved
					$query = "SELECT * FROM `Wells_tbl` WHERE `containerID`='" . $contID_const . "' AND `wellRow`='" . $row . "' AND `wellCol`='" . $col . "' AND `reserved`='TRUE' AND `status`='ACTIVE'";
					$reserved_wells_rs = mysql_query($query, $conn) or die ("Failure in well info selection " . mysql_error());

					// If the well is reserved, output it in colour, differentiating between wells reserved by the current user and wells reserved by others
					if ($reserved_wells_ar = mysql_fetch_array($reserved_wells_rs))
					{
						if( $reserved_wells_ar["do_not_use"] == "TRUE" )
						{
							// Do not use is set for the current well
							// Should have the highest priority of any well type
							echo "<td ID=\"well_plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "\" style=\"color:#FF0000; font-size: x-small; font: Geneva, Arial, Helvetica, sans-serif; border-width:thick; border-style: groove\">";
							echo "This well has been set to <strong>Do Not Use</strong>";
							echo "</td>";
						}
						elseif ($reserved_wells_ar["creatorID"] == $currentUserID)
						{
							// If the well is currently reserved by a user
							// colour cell green
							echo "<td ID=\"well_plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "\" style=\"color:#009900; font-size: x-small; font: Geneva, Arial, Helvetica, sans-serif; border-width:thick; border-style: groove\">";
							echo "\n<input type=checkbox ID=\"plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "\" name=wells_checkbox[] value=\"" . $row . "|" . $col . "\" " . $write_disabled . " onClick=\"highlightWell(this.id, 'well_plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "');\">\n";
							echo "</td>";
						}
						else
						{
							// Default type
							// colour cell brown
							// June 10, 2011: Now that we're allowing modification by attribute, disable the well if not reserved by you!
							echo "<td ID=\"well_plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "\" style=\"color:#990000; font-size: x-small; font: Geneva, Arial, Helvetica, sans-serif; border-width:thick; border-style: groove\">";
							echo "\n<input type=checkbox ID=\"plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "\" name=wells_checkbox[] value=\"" . $row . "|" . $col . "\" " . "DISABLED" /*$write_disabled*/ . " onClick=\"highlightWell(this.id, 'well_plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "');\">\n";
							echo "</td>";
						}
					}
					else
					{
						// Default type for all normal wells
						echo "<td ID=\"well_plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "\">";

						echo "\n<input type=checkbox ID=\"plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "\" name=wells_checkbox[] value=\"" . $row . "|" . $col . "\"" . $write_disabled . " onClick=\"highlightWell(this.id, 'well_plate_" . $_GET["Mod"] . "_Row_" . $row . "_Col_" . $col . "');\">\n";
						echo "</td>";	
					}
				}
			}

			echo "<td style=\"white-space:nowrap;\">";

			// Jan. 15, 2010: If container type is modified after plate was created, $general_property_ar would not return newly added properties
			$this->printAll_headers(array_flip($contTypeAttribs), "<BR>");
			echo "</td>";
			echo "</tr>";
		}
		
		echo "</table>";
		echo "</form>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";

		?><form name="plate_to_excel" action="<?php echo $cgi_path . "location_request_handler.py"; ?>" method="POST">
			<input type="hidden" name="contID" value="<?php echo $_GET["Mod"]; ?>">
			<input type="hidden" name="export_plate">
		</form><?php
	}

	/**
	 * Auxiliary function
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param INT $count
	 * @param STRING $set
	*/
	function reset_set($count, $set)
	{
		if( $count > 0 )
		{
			$set = substr( $set, 0, strlen( $set ) - 2 ) . ")";
		}
		else
		{
			$set = "('')";
		}
		return $set;
	}
	

	/**
	 * Escape quotation mark in property name
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param STRING $propName
	*/
	function getSafeSelect($propName)
	{
		if (!(strpos($propName, "''" === false)))
		{
			return (str_replace("''", "'", $propName));
		}
	}

	/**
	 * Function printGeneral_property()
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param HT_Class $ht
	 * @param MIXED $key
	 * @param STRING $separator
	*/
	function printGeneral_property($ht, $key , $separator)
	{
		$result_ar = $ht->get( $key );
		
		if( $result_ar == -1 )
		{
			echo "";
			echo $separator;
		}
		else
		{
			foreach( $result_ar as $arrayKey => $arrayValue )
			{
//				echo $arrayValue;
				echo $this->getSafeSelect($arrayValue);		// Feb 22, 2006, Marina
				echo $separator;
			}
		}
	}


	/**
	 * Print ALL prep property names on container view (general plus specific to container type)
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param Array $name_ar
	 * @param STRING $separator
	*/
	function printAll_headers($name_ar, $separator)
	{
		echo "<TABLE style=\"white-space:nowrap; font-weight:bold; font-size:9pt;\" width=\"98%\">";
			echo "<TR><TD style=\"font-weight:bold; font-size:9pt;\">OpenFreezer ID</td></tr>";
			echo "<TR><TD style=\"font-weight:bold; font-size:9pt;\">Reference</td></tr>";
			$this->printGeneral_headers($name_ar, $separator);
			echo "<TR><TD style=\"font-weight:bold; font-size:9pt;\">Comments</td></tr>";

			// May 31, 2011: redesigning, so it's not needed, only confusing
// rmvd May 31, 2011		echo "<TR><TD>Tool Checkbox</td></tr>";
		echo "</TABLE>";
	}
	
	/**
	 * Print ONLY the prep property names that are specific to this container type
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param Array $name_ar
	 * @param STRING $separator
	*/
	function printGeneral_headers($name_ar , $separator)
	{
		foreach($name_ar as $name => $id)
		{
			echo "<TR><TD style=\"font-weight:bold; font-size:9pt;\">";
			echo $name;
			echo "</td></tr>";
		}
	}
	
	/**
	 * Return the ID of the previous container in this container type group
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT $groupCount_const Numeric position of this container in the sequence of other containers of this type
	 * @param INT $groupID_const ID of this container type (e.g. '2' => 'Glycerol Stock'
	 * @param INT $contID ID of this container
	*/
	function get_Previous_ContainerID($groupCount_const, $groupID_const, $contID)
	{
		global $conn;
		
		$find_preview_rs = mysql_query("SELECT `containerID`,`contGroupCount` FROM `Container_tbl` WHERE `contGroupID`='" . $groupID_const . "' AND `contGroupCount`<'" . $groupCount_const . "' AND `status`='ACTIVE' ORDER BY `contGroupCount` DESC", $conn);

		if( $find_preview_ar = mysql_fetch_array( $find_preview_rs, MYSQL_ASSOC ) )
		{
			return $find_preview_ar["containerID"];
		}
		
		return $contID;
	}
	

	/**
	 * Return the ID of the next container in this container type group
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT $groupCount_const Numeric position of this container among other containers of this type
	 * @param INT $groupID_const ID of this container type (e.g. '2' => 'Glycerol Stock'
	 * @param INT $contID ID of this container
	*/
	function get_Next_ContainerID( $groupCount_const, $groupID_const, $contID  )
	{
		global $conn;
		
		$find_preview_rs = mysql_query( "SELECT `containerID`,`contGroupCount` AS next FROM `Container_tbl` WHERE `contGroupID`='" . $groupID_const . "' AND `contGroupCount`>'" . $groupCount_const . "' AND `status`='ACTIVE' ORDER BY `contGroupCount` ASC", $conn );

		if( $find_preview_ar = mysql_fetch_array( $find_preview_rs, MYSQL_ASSOC ) )
		{
			return $find_preview_ar["containerID"];
		}
		
		return $contID;
	}
}

?>