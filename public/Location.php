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
* @author John Paul Lee @version 2005
*
* @author     Marina Olhovsky <olhosvky@lunenfeld.ca>
* @version    3.1
* @package Location
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* Include/require statements
*/

	include "Classes/MemberLogin_Class.php";
	include "Classes/Member_Class.php";
	include "Classes/Session_Var_Class.php";
	include "Classes/Order_Class.php";	// Jan. 23/08
	include "Classes/generalFunc_Class.php";
	include "Classes/HT_Class.php";
	include "Classes/StopWatch.php";
	include "Classes/ColFunctOutputer.php";

	include "Location/Location_Funct_Class.php";
	include "Location/Location_Output_Class.php";
	include "Location/Location_Admin_Class.php";
	include "Location/Location_Creator_Class.php";
	include "Location/Location_Well_Class.php";

	include "DatabaseConn.php";
	include "HeaderFunctions.php";

	include "Reagent/Reagent_Background_Class.php";	// april 16/07, Marina

	include "Project/ProjectFunctions.php";		// August 13/07, Marina

/**
 * Central Location deploy page
 *
 * @author John Paul Lee @version 2005
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Search
 *
 * @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 *
*/
	
	/**
	 * A constant used to define table width on User module views
	 * @global INT $Const_Table_Width
	*/
	global $Const_Table_Width;

	session_start();

	$loginBlock = new MemberLogin_Class( );  
	$loginBlock->loginCheck( $_POST );

	header("Cache-control: private"); //IE 6 Fix 

	$colspan_const = 0;

	// print header	
	outputMainHeader();
	
	?>
	<title>OpenFreezer Location Page</title>

	<table height="100%">
		<table border="0" width="100%">
			<!-- Marina: March 4/08: Create a resizeable border -->
			<!--<tr>
				<td rowspan="100%" style="padding-left:0; padding-right:5px;">
					<div class="resizeable" onMouseDown="resize();"></div>
				</td>
			</tr>-->
		<?php

		if (isset($_SESSION["userinfo"]))
		{
			if ($loginBlock->verifyPermissions(basename($_SERVER['PHP_SELF']), $_SESSION['userinfo']->getUserID()))
			{
				$sessionChecker = new Session_Var_Class();
				$sessionChecker->checkSession_all();	// Dec. 21/09
				unset( $sessionChecker );

				?>
				<tr>
					<td>
					<?php
						if ($_GET["View"] == "1")
						{
							if (isset($_POST["location_view1_changeview_submit"]))
							{
								if ($_POST["location_view1_currentgroup_hidden"] == "1")
								{
									$currentGrouping = 2;
								}
								else
								{
									$currentGrouping = 1;
								}
							}
							else
							{
								$currentGrouping = 1;
							}

							// View 1
							// Basic view for general location information, nothing more
							$loc_obj = new Location_Output_Class();
// 							$loc_obj->init_Location_vars();
							$gfunc_obj = new generalFunc_Class();	// feb. 15/08

							if( isset( $_GET["rid"] ) )
							{
								// Jan. 23/08: Order reagents
								$lfunc_obj = new Location_Funct_Class();

								if (isset($_POST["addToOrder"]) && $_POST["addToOrder"] == "1")
								{
									$tmp_rid = $_POST["orderReagentID"];
									$tmp_iso = $_POST["orderIsolateNum"];
									$tmp_cont = $_POST["orderContainerID"];
									$tmp_row = $_POST["orderRow"];
									$tmp_col = $_POST["orderCol"];

									// Add order IFF does not exist
									if (!$lfunc_obj->inOrder($tmp_rid, $tmp_iso, $tmp_cont, $tmp_row, $tmp_col))
									{
										$tempOrder = new Order_Class($tmp_rid, $tmp_iso, $tmp_cont, $tmp_row, $tmp_col);
	
										$tmpUserOrders = $_SESSION["userinfo"]->getOrders();
	
										if (sizeof($tmpUserOrders) == 0)
										{
											$tmp_orders = Array();
											$tmp_orders[] = $tempOrder;
											$_SESSION["userinfo"]->setOrders($tmp_orders);
										}
										else
										{
											$tmpUserOrders[] = $tempOrder;
											$_SESSION["userinfo"]->setOrders($tmpUserOrders);
										}
									}
								}

								// Changes made Feb. 15/08
								if ($currentGrouping == 1)
								{
									$currView = "Group by Container";
									$switchTo = "Group by Isolate";

									$contViewClass = "currentView";
									$isoViewClass = "switchTo";

									$contSpanClass = "tabLinkActive";
									$isoSpanClass =  "tabLinkInactive";

									$caption = "Locations of " . $_SESSION["ReagentType_ID_Name"][$gfunc_obj->getTypeID($_GET["rid"])] . " " . $gfunc_obj->getConvertedID_rid($_GET["rid"]) . " grouped by Container:";
								}
								else
								{
									$currView = "Group by Isolate";
									$switchTo = "Group by Container";
						
									$isoViewClass = "currentView";
									$contViewClass = "switchTo";

									$contSpanClass = "tabLinkInactive";
									$isoSpanClass =  "tabLinkActive";

									$caption = "Locations of " . $_SESSION["ReagentType_ID_Name"][$gfunc_obj->getTypeID($_GET["rid"])] . " " . $gfunc_obj->getConvertedID_rid($_GET["rid"]) . " grouped by Isolate:";
								}
								?>
								<TABLE width="100%" cellpadding="2" cellspacing="1">
									<TR>
										<TD>
											<TABLE id="tabsTbl" cellspacing="0">
												<TR>
													<TD style="padding-left:1px;" class="<?php echo $contViewClass; ?>">
														<span id="groupByContainerTab" class="<?php echo $contSpanClass; ?>"  onClick="groupByContainer();">  Group by Container  </span>
													</TD>
			
													<TD class="<?php echo $isoViewClass; ?>">
														<span id="groupByIsolateTab" class="<?php echo $isoSpanClass?>" style="padding-left:38px; padding-right:38px;" onClick="groupByIsolate();">Group by Isolate</span>
													</TD>
												</TR>
											</TABLE>
										</TD>
									</TR>

									<form NAME="viewLocation" action="<?php echo $_SERVER["PHP_SELF"]; ?>?View=1&amp;rid=<?php echo $_GET["rid"]; ?>" method="post">

										<input type="hidden" id="location_output_mode" name="location_view1_currentgroup_hidden" VALUE="<?php echo $_POST["location_view1_currentgroup_hidden"]; ?>">
	
										<SPAN style="font-weight:bold; font-size:10pt; text-align:left; text-decoration:none; padding-left:8px; color:#545454;">Locations of <?php echo $_SESSION["ReagentType_ID_Name"][$gfunc_obj->getTypeID($_GET["rid"])] . " " . $gfunc_obj->getConvertedID_rid($_GET["rid"]); ?></SPAN><BR><BR>
	
										<!-- Feb. 15/08 -->
										<input type="hidden" name="location_view1_changeview_submit" value="<?php echo $switchTo; ?>">
	
										<!-- Jan. 23/08: Order clones -->
										<INPUT TYPE="hidden" ID="order_rid" NAME="orderReagentID">
	
										<INPUT TYPE="hidden" ID="order_iso_num" NAME="orderIsolateNum">
	
										<INPUT TYPE="hidden" ID="order_cont_id" NAME="orderContainerID">
	
										<INPUT TYPE="hidden" ID="order_row" NAME="orderRow">
	
										<INPUT TYPE="hidden" ID="order_col" NAME="orderCol">
	
										<INPUT TYPE="hidden" ID="order_placed" NAME="addToOrder" VALUE="0">
									</form>

									<TR><TD class="locationContent">
									<?php
										echo "<SPAN style=\"font-size:10pt; font-weight:bold; color:#0000CD;\">" . $caption . "</SPAN><BR><BR>";

										$loc_obj->printIsolates_ExpandedView( $_GET["rid"], $currentGrouping );
									?>
									</TD></TR>
								</TABLE>
								<?php
							}
							else
							{
								// Feb 9, Marina -- Replace with a short index page
								$loc_obj->print_Location_info();
							}
						}
						elseif( $_GET["View"] == "2" )
						{
							// View 2
							// View choice menu information options		** (the drop-down container selection -- Marina)
							$loc_obj = new Location_Output_Class();

							# May 22/07, Marina
							$lfunc_obj = new Location_Funct_Class();

							if (isset($_POST["cont_name_selection"]) && isset($_POST["cont_plate_selection"]))
							{
								$loc_obj->printForm($_POST);
								$loc_obj->outputPlateView_groupID($_SESSION["Container_Name_ID"][$_POST["cont_name_selection"]], $_POST["cont_plate_selection"]);
							}
							else if (isset($_GET["gID"]))
							{
								$_POST["cont_name_selection"] = $_GET["gID"];
								$loc_obj->printForm($_POST);
							}
							elseif(isset($_GET["Mod"]))
							{
								$loc_obj->outputPlateView_contID($_GET["Mod"]);
							}
							elseif(!isset($_POST["cont_name_selection"]))
							{
								$loc_obj->printForm($_POST);
							}
							elseif (isset($_POST["cont_name_selection"]) && strcasecmp($_POST["cont_name_selection"], "No container selected") == 0)
							{
								$loc_obj->printForm($_POST);
							}
							else
							{
								$loc_obj->printForm($_POST);
							}
						}
						elseif ($_GET["View"] == "3")
						{
							if (isset($_GET["Sub"]))
							{
								if ($_GET["Sub"] == "1")
								{
									$loc_obj = new Location_Admin_Class();
									$loc_obj->printContainerStorageInfo($_GET["Mod"], true);
								}
							}
							else
							{
								if (isset($_GET["Mod"]))
								{
									// Jan. 3, 2010: View the container's storage (freezer, fridge, LN tank, etc.)
									$loc_obj = new Location_Admin_Class();

									// Check user access here!!!!!!!!!!
									// ...........

									$loc_obj->printContainerStorageInfo($_GET["Mod"]);
								}
								else	// feb. 24/10
								{
									$loc_obj = new Location_Output_Class();
									$loc_obj->printErrPage("Invalid selection!");
								}
							}
						}
						elseif ($_GET["View"] == "4")
						{
							// Feb. 24/10: this is going to be container sizes search view but restrict for now
							$loc_obj = new Location_Output_Class();
							$loc_obj->printErrPage("Invalid selection!");
						}
						// View 5
						// View for modifying and admining all containers
						elseif ($_GET["View"] == "5")
						{
// Feb. 24/10: again, check access here
// .............

							$loc_obj = new Location_Admin_Class();

							if( isset( $_POST["cont_name_selection"] ) && !isset( $_GET["Mod"] ) )
							{
								$loc_obj->printContainerInfo( $_SESSION["Container_Name_ID"][$_POST["cont_name_selection"]] );
							}
							elseif( isset( $_GET["Mod"] ) && !isset( $_POST["cont_modify_button"] ) )
							{
								$loc_obj->printModifyContainer_Form( "", $_GET["Mod"] );
							}
							elseif (isset($_POST["cont_modify_button"]))
							{
								$loc_obj->printModifyContainer_Form($loc_obj->updateContainerInfo($_POST), $_GET["Mod"]);
							}
							elseif (isset($_POST["container_submit_button"]))
							{
								$loc_obj->printContainerInfo( $_SESSION["Container_Name_ID"][$_POST["cont_name_selection"]] );
							}
							else if (isset($_GET["Del"]))
							{
								if ($_GET["Del"] == 1)
								{
									echo "<span style=\"font-size:10pt; font-weight:bold; color:brown;\">Container deleted successfully.</span>";
								}
							}
							else	// feb. 24/10
							{
								$loc_obj = new Location_Output_Class();
								$loc_obj->printErrPage("Invalid selection!");
							}
						}
						// View 6
						// View for creating new types of containers
						elseif ($_GET["View"] == "6")
						{
// Feb. 24/10: add access check!!!!!!

							$loc_obj = new Location_Creator_Class();
		
							if (isset($_GET["Sub"]))
							{
								if ($_GET["Sub"] == "1")
								{
									// Submenu Section: New Container **Sizes**
									if (isset($_POST["cont_type_create_button"]))
									{
										$loc_obj->process_submit($_POST);
									}

									$loc_obj->printForm_newTypes();
								}
								elseif ($_GET["Sub"] == "2")
								{
									$loc_obj->printForm_addNewContainerTypes();
								}
								elseif( $_GET["Sub"] == "3" ) 
								{
									// Modified Sept. 18/07 by Marina
									// Creation processing performed by CGI, redirected to newly created plate
									if (isset($_GET["Mod"]))
									{
										$loc_obj = new Location_Output_Class();
										$lfunc_obj = new Location_Funct_Class();

										$loc_obj->outputPlateView_contID($_GET["Mod"]);
									}
									else
									{
										$loc_obj->printForm_newContainers();
									}
								}
								elseif ($_GET["Sub"] == "4")
								{
									// Jan. 8, 2010 - View container types
									$loc_obj = new Location_Output_Class();

									if (isset($_GET["contTypeID"]))
									{
										if (isset($_GET["Mod"]))
										{
											// Print container type in Modify mode
											$loc_obj->print_Detailed_Cont_Type($_GET["contTypeID"], true);
										}
										else
										{
											// Print detailed view of selected container type
											$loc_obj->print_Detailed_Cont_Type($_GET["contTypeID"], false);
										}
									}
									else if (isset($_POST["contTypeList"]))
									{
										// Print detailed view of selected container type
										$loc_obj->print_Detailed_Cont_Type($_POST["contTypeList"], false);
									}
									else if (isset($_GET["Del"]))
									{
										echo "<SPAN style=\"font-weight:bold; color:maroon;\">Container type deleted successfully.</SPAN><BR><P>";

										echo "<a href='" . $_SERVER["PHP_SELF"] . "?View=6&Sub=4'>Back to container types list</a>";
									}
									else
									{
										// Print container types selection list
										$loc_obj->printContainerTypesList();
									}
								}
							}
							else	// feb. 24/10
							{
								$loc_obj = new Location_Output_Class();
								$loc_obj->printErrPage("Invalid selection!");
							}
						}
						// View 7
						// View for inserting new wells
						elseif( $_GET["View"] == "7" ) 
						{
							if (isset($_POST["well_new_button"]))
							{
								$loc_well_obj = new Location_Well_Class();
								$loc_well_obj->create_wells( $_POST, $_GET["Mod"] );
							}

							elseif (isset($_POST["well_unreserve_button"]))
							{
								$loc_well_obj = new Location_Well_Class();
								$loc_well_obj->unreserve_wells($_POST, $_GET["Mod"]);
							}

							elseif (isset($_POST["well_delete_button"]))
							{
								$loc_well_obj = new Location_Well_Class();
								$loc_well_obj->delete_wells($_POST, $_GET["Mod"]);
							}

							elseif( isset( $_POST["well_modify_button"] ) )
							{
								$loc_well_obj = new Location_Well_Class();
								$loc_well_obj->printForm_modify( $_POST["wells_checkbox"], $_GET["Mod"], false );
							}

							elseif( isset( $_POST["well_mod_submit_button"] ) )
							{
								$loc_well_obj = new Location_Well_Class();

								if ($loc_well_obj->process_modify($_GET["Mod"]) === true)
								{
									$loc_obj = new Location_Output_Class();
									$loc_obj->outputPlateView_contID($_GET["Mod"]);
								}
								else
								{
									$loc_well_obj->printForm_modify( $_SESSION["wells_checkbox_tmp"], $_GET["Mod"], true );
								}
							}

							// Added April 24/07 -- Add Cancel button to well creation/modification views
							elseif (isset($_POST["well_mod_cancel_button"]))
							{
								if( isset( $_GET["Mod"] ) )
								{
									$loc_obj = new Location_Output_Class();
									$loc_obj->outputPlateView_contID( $_GET["Mod"] );
								}
							}

							// Cancel button for well creation view
							// The well would still be reserved, but if the user changes his/her mind and decides to not put anything in the well, s/he can cancel his/her action in an elegant way instead of pressing browser's Back button
							// Update Jan. 7, 2010: I think the well should be unreserved if user decides to cancel.
							elseif (isset($_POST["well_create_cancel_button"]))
							{
								$loc_well_obj = new Location_Well_Class();

								if (isset($_GET["Mod"]))
								{
									$loc_obj = new Location_Output_Class();
									$loc_obj->outputPlateView_contID($_GET["Mod"]);
								}
							}

							elseif( isset( $_POST["mod_empty_well_cancel"] ) )
							{
								if( isset( $_GET["Mod"] ) )
								{
									$loc_obj = new Location_Output_Class();
									$loc_obj->outputPlateView_contID( $_GET["Mod"] );
								}
							}

							elseif( isset( $_POST["well_limsid_submit_button"] ) )
							{
								$loc_well_obj = new Location_Well_Class();

								if ($loc_well_obj->process_firstStage( $_POST , $_GET["Mod"] ) === true)
								{
									$loc_obj = new Location_Output_Class();

									if( isset( $_GET["Mod"] ) )
									{
										$loc_obj->outputPlateView_contID( $_GET["Mod"] );
									}
								}
								else
								{
									// Creation failed
									$loc_well_obj->create_wells( $_SESSION["wells_checkbox_tmp"], $_GET["Mod"], true);
								}
							}
							elseif (isset( $_POST["well_delete_submit_button"] ))
							{
								$loc_well_obj = new Location_Well_Class();

								if ($loc_well_obj->process_delete( $_SESSION["wells_checkbox_tmp"], $_GET["Mod"] ))
								{
									$loc_obj = new Location_Output_Class();

									if( isset( $_GET["Mod"] ) )
									{
										$loc_obj->outputPlateView_contID( $_GET["Mod"] );
									}
								}
								else
								{
									// echo "Deletion failed <br>";
								}
							}
							else	// feb. 24/10
							{
								$loc_obj = new Location_Output_Class();
								$loc_obj->printErrPage("Invalid selection!");
							}
						}

						?>
						</td>
					</tr>
					<?php
				}
				else
				{
					// May 30/07, Marina
					echo "<tr><td class=\"warning\">";
					echo "You are not authorized to view this page.  Please contact the site administrator.";
					echo "</td></tr>";
				}

				?>
				</table>
			<?php

			mysql_close();
			outputMainFooter();
		}
		else
		{
			// April 9/07, Marina: May output general info page for non-registered users
			if (isset($_GET["View"]) && ($_GET["View"] == 1))
			{
				$loc_obj = new Location_Output_Class();
				$loc_obj->print_Location_info();
				
				outputMainFooter();
			}
			else
			{
				echo "<tr><td class=\"warning\">";
				echo "Please log in to access this page.";
				echo "</td></tr>";
				?>
						</TABLE>
					</table>
				<?php
				
				outputMainFooter();
			}
		}
	mysql_close($conn);
?>
