<?php
/**
* Root file for Reagent module
*
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
* @package Reagent
*
* @copyright  2005-2010 Pawson Laboratory
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* Include/require statements
*/
	include_once "Classes/MemberLogin_Class.php";
	include_once "Classes/Member_Class.php";
	include_once "Classes/Session_Var_Class.php";
	include_once "Classes/generalFunc_Class.php";
	include_once "Classes/HT_Class.php";
	include "Classes/StopWatch.php";

	include_once "Classes/Reagent_Class.php";

	include_once "Reagent/Reagent_Special_Prop_Class.php";
	include_once "Reagent/Reagent_Creator_Class.php";

	include "Location/Location_Output_Class.php";
	include_once "Reagent/Reagent_Output_Class.php";
	include_once "Reagent/Reagent_Background_Class.php";
	include_once "Reagent/ColFunctOutputer_Class.php";
	include_once "Reagent/Reagent_Function_Class.php";
	include_once "Reagent/Sequence_Function_Class.php";

	include_once "Project/ProjectFunctions.php";

	include_once "Classes/SeqFeature.php";
	include_once "Classes/Sequence.php";

	include_once "DatabaseConn.php";
	include "HeaderFunctions.php";

	/**
	 * A constant used to define table width on User module views
	 * @global INT $Const_Table_Width
	*/
	global $Const_Table_Width;
	$colspan_const = 0;

	header("Cache-control: private"); //IE 6 Fix

	session_start();


	$loginBlock = new MemberLogin_Class();
	$loginBlock->loginCheck($_POST);

	// print header	
	outputMainHeader();

	?>
	<title>Reagent Page</title>

	<table height="100%">
		<table style="width:98%;">
		<?php
		if (isset($_SESSION["userinfo"]))
		{
			// Moved the following 3 statements up here on May 11/09
			$currUserID = $_SESSION["userinfo"]->getUserID();
			$currUserCategory = $_SESSION["userinfo"]->getCategory();
			$allowedUP = getAllowedUserProjectIDs($currUserID);

			if ($loginBlock->verifyPermissions(basename($_SERVER["PHP_SELF"]), $_SESSION["userinfo"]->getUserID()))
			{
				$sessionChecker = new Session_Var_Class();
				$sessionChecker->checkSession_all(); 	// july 17/07

				unset($sessionChecker);

				?>
				<tr>
					<td>
						<?php	

						// View 1
						// Basic view for general reagent information, nothing more

						if ($_GET["View"] == "1")
						{
							$reagent_obj = new Reagent_Output_CLass();
							$reagent_obj->print_reagent_intro();
						}
						elseif($_GET["View"] == "2")
						{
							$reagent_obj = new Reagent_Creator_Class();

							// April 17/07: Add exception handling code, if Python found an error, redirect to a proper error page
							if (isset($_GET["Err"]))
							{
								$reagent_obj->process_Error();
							}
							else if (isset($_GET["Type"]))
							{
								$rType = $_GET["Type"];

								switch ($_GET["Type"])
								{
									case 'Vector':
										// Added April 28/08: Multi-step creation
										if (isset($_GET["Step"]))
										{
											if (isset($_GET["rID"]))
												$rID = $_GET["rID"];

											if (isset($_GET["Sub"]))
												$subtype = $_GET["Sub"];
									
											if (isset($_GET["Seq"]))
												$seqID = $_GET["Seq"];

											switch ($_GET["Step"])
											{
												case '1':
													// Preview sequence constructed from parents
													// Output:
													// 	1. Heading: "Step 2 of 4: Preview Sequence"
													// 	2. Parents w/ option to change
													// 	3. Constructed sequence
													// 	4. 'Next' and 'Back' buttons
													$reagent_obj->previewReagentSequence($rType, $subtype, $rID, $seqID);
												break;
			
												case '2':
													// May 1/08 - Preview Features Loaded from Parents during sequence reconstitution
													$reagent_obj->previewReagentFeatures($rType, $subtype, $rID, $seqID);
												break;
			
												case '3':
													// May 2/08 - Final step in the creation process - fill in general info, s.a. name, status, project, etc.
													$reagent_obj->previewReagentIntro("Vector", $subtype, $rID, $seqID);
												break;
											}
										}
										else
										{
											$parents = array();
											
											$parents["PV"] = $_GET["PV"];
											
											if (isset($_GET["I"]))
												$parents["I"] = $_GET["I"];
											
											else if (isset($_GET["IPV"]))
												$parents["IPV"] = $_GET["IPV"];

											$reagent_obj->printCreationForm($_GET["Type"], $_GET["Sub"], $parents);
										}

									break;

									case 'CellLine':
										$reagent_obj->process_CellLine_TypeChoice();
									break;

									case 'Insert':

										if (isset($_GET["ErrCode"]))
											$reagent_obj->printForm_Insert_Insert(0, false, true);

										if (isset($_GET["Step"]))
										{
											if (isset($_GET["rID"]))
												$rID = $_GET["rID"];

											if (isset($_GET["Seq"]))
												$seqID = $_GET["Seq"];

											switch ($_GET["Step"])
											{
												case '1':
													// Preview sequence constructed from parents
													// Output:
													// 	1. Heading: "Step 2 of 4: Preview Sequence"
													// 	2. Parents w/ option to change
													// 	3. Constructed sequence
													// 	4. 'Next' and 'Back' buttons
													$reagent_obj->previewReagentSequence($rType, $subtype, $rID, $seqID);
												break;
			
												case '2':
													// May 1/08 - Preview Features Loaded from Parents during sequence reconstitution
													$reagent_obj->previewReagentFeatures($rType, $subtype, $rID, $seqID);
												break;
			
												case '3':
													// May 2/08 - Final step in the creation process - fill in general info, s.a. name, status, project, etc.
													$reagent_obj->previewReagentIntro("Insert", "", $rID, $seqID);
												break;

												// Added May 14/08: New procedure for saving Insert from Primer Design
												case '4':
													$reagent_obj->previewInsertFeaturesPrimer($rID, $seqID, $_GET["PIV"], $_GET["SO"], $_GET["AS"]);
												break;

												case '5':
													$reagent_obj->previewInsertIntroPrimer($rID, $seqID, $_GET["PIV"], $_GET["SO"], $_GET["AS"]);
												break;
											}
										}
										else
										{
											$parents = array();
											
											$parents["PV"] = $_GET["PV"];
											
											if (isset($_GET["I"]))
												$parents["I"] = $_GET["I"];
											
											else if (isset($_GET["IPV"]))
												$parents["IPV"] = $_GET["IPV"];

											$reagent_obj->printCreationForm($_GET["Type"], "", $parents);
										}
									break;

									default:
										$reagent_obj->printCreationForm();
									break;
								}
							}
							else
							{
								$reagent_obj->printCreationForm();
							}
						}
						// Add reagent types
						elseif( $_GET["View"] == "3" )
						{
							$reagent_obj = new Reagent_Creator_CLass();

							if (isset($_GET["Step"]))
							{
								$reagent_obj->printFormAddReagentType($_GET["Step"]);
							}
							else
							{
								if (isset($_GET["goback"]))
								{
									$reagent_obj->printFormAddReagentType(1, true);
								}
								else
								{
									$reagent_obj->printFormAddReagentType();
								}
							}
						}
						elseif( $_GET["View"] == "4" )
						{
							$rout_obj = new Reagent_Output_Class();
							$rout_obj->printReagentStats();

							// June 10, 2011: "Canned" :) query for Cell Lines (NM review)
							$rout_obj->printCellLineBiosafety();
						}
						elseif( $_GET["View"] == "5" )
						{
							// Modified June 3/09, Marina: This is obsolete, I've implemented a new method of creating new reagent types; using this view as a search page for Admin to browse through and/or delete reagent types

							if (isset($_GET["Del"]) && ($_GET["Del"] == 1))
							{
								echo "<span style=\"color:#FF0000; font-weight:bold\">Reagent type deleted successfully.</span><BR/><BR/>";
								echo "<a href=\"" . $_SERVER["PHP_SELF"]. "?View=5\">Back to Reagent Types list</a>";
							}
							else
								printViewReagentTypesForm();
						}
						elseif ($_GET["View"] == "6")
						{
							$gfunc = new generalFunc_Class();		// May 11/06
							$rfunc = new Reagent_Function_Class();		// May 12/06

							// Detailed view of reagents
							if ($_GET["rid"] > 0 )
							{
// print_r($_POST);
								$rout_obj = new Reagent_Output_Class();
								$bfunc_obj = new Reagent_Background_Class();	// May 26/06

								// Feb. 24/10: try to prevent user from viewing unauthorized reagents by checking project access
								$userProjects = getAllowedUserProjectIDs($currUserID);
								$reagentProject = getReagentProjectID($_GET["rid"]);
								
								if (!in_array($reagentProject, $userProjects))
								{
									$rout_obj->printErrPage("Restricted access!");
								}
								else if (isset($_GET["ErrCode"]))
								{
// echo "Error??";
									if ($_GET["ErrCode"] == 3)
									{
										// Incorrect Parent Vector ID (check could not be caught by JS) - in ALL reagent types
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 3);
									}
									elseif ($_GET["ErrCode"] == 1)
									{
										switch ($rfunc->getType($_GET["rid"]))
										{
											case '1':
												// vector - PV not loaded
											break;

											case '2':
												// Insert - Sites not found at specified position
												$rout_obj->print_Detailed_Insert(true, $_GET["View"], $_GET["rid"], "Insert");
											break;
											
											case '4':
												// CL - PV not loaded
												$rout_obj->print_Detailed_CellLine("Modify", $_GET["View"], $_GET["rid"], 0, "Vector");
											break;
											
											default:
											break;
										}
									}
									elseif ($_GET["ErrCode"] == 2)
									{
										// At the moment, this scenario could only occur for Cell Lines - where their Parent CL details were not preloaded.  But keep the 'switch' just in case, easy to remove afterwards if not needed
										switch ($rfunc->getType($_GET["rid"]))
										{
											case '4':
												// Parent CL details not preloaded
												$rout_obj->print_Detailed_CellLine("Modify", $_GET["View"], $_GET["rid"], 0, "Cell Line");
											break;
											
											default:
											break;
										}
									}
									elseif ($_GET["ErrCode"] == 4)
									{
										// Cell Line modification - cannot use the parent cell line entered, don't have read access
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 4);
									}
									elseif ($_GET["ErrCode"] == 5)
									{
										// Insert modification - cannot use IPV or one of the oligos
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 5);
									}

									elseif ($_GET["ErrCode"] == 6)
									{
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"]);
									}

									elseif ($_GET["ErrCode"] == 7)
									{
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"]);
									}

									elseif ($_GET["ErrCode"] == 8)
									{
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"]);
									}
									elseif ($_GET["ErrCode"] == 9)
									{
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"]);
									}
									elseif ($_GET["ErrCode"] == 10)
									{
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"]);
									}
									elseif ($_GET["ErrCode"] == 11)
									{
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"]);
									}
									elseif ($_GET["ErrCode"] == 12)
									{
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"]);
									}
									elseif ($_GET["ErrCode"] == 13)
									{
										// August 31/07: Vector modification - Not allowed to use the Insert or IPV due to lack of project access
										// Using the same ErrCode value, since both Insert and IPV cannot occur simultaneously on one view
										if (isset($_GET["I"]) || isset($_GET["IPV"]))
										{
											$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 2);
										}
									}
								}
								else if (isset($_GET["mode"]) && ($_GET["mode"] == 'Create'))
								{
									$rout_obj->print_Detailed_Reagent("Create", $_GET["View"], $_GET["rid"], 1);
								}
								// Modify associations
								// August 31/07, Marina: Although both Vectors and Cell Lines have a Parent Vector, its form field names are different: For Vectors the field is called parent_vector_name_txt, for Cell Lines it's called pv_name_txt (for historical reasons and naming consistency)
								elseif (isset($_POST["change_vp_id"]))
								{
// echo "Changing parent vector";
									// August 29/07, Marina: Verify that the user can access parent by project
								
									// Parent vector is used everywhere; the other parent type needs to be differentiated based on reagent type

									// Editing a Vector - Check parent vector, and either Insert or IPV ID, depending on the vector type
									if (isset($_POST["parent_vector_name_txt"]))
									{
										$pvID = $gfunc->get_rid($_POST["parent_vector_name_txt"]);
										$parentVectorProjectID = getReagentProjectID($pvID);
										
										if (($currUserCategory != $_SESSION["userCategoryNames"]["Admin"]) && ($pvID > 0) && !in_array($parentVectorProjectID, $allowedUP))
										{
											$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 3);
										}
										
										// PV is OK; now determine if the other parent is an insert or IPV
										elseif (isset($_POST["ipv_name_txt"]))
										{
											if ($_POST["ipv_name_txt"] != "")
											{
												$ipvID = $gfunc->get_rid($_POST["ipv_name_txt"]);
												$ipvProjectID = getReagentProjectID($ipvID);
												
												// take into account that one of the parent values may be empty
												if (($currUserCategory != $_SESSION["userCategoryNames"]["Admin"]) && !in_array($ipvProjectID, $allowedUP))
													$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 2);
												
												else
													$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 1);
											}
											else
												$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 2);
										}
										elseif (isset($_POST["insert_name_txt"]))
										{
											if ($_POST["insert_name_txt"] != "")
											{
												$insertID = $gfunc->get_rid($_POST["insert_name_txt"]);	
												$insertProjectID = getReagentProjectID($insertID);
												
												if (($currUserCategory != $_SESSION["userCategoryNames"]["Admin"]) && !in_array($insertProjectID, $allowedUP))
													$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 2);
												else
													$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 1);
											}
											else
												$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 1);
										}
										else
										{
											$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 1);
										}
									}
									elseif (isset($_POST["pv_name_txt"]))
									{
										// Editing Cell Line; verify parent vector and parent cell line IDs

										$pvID = $gfunc->get_rid($_POST["pv_name_txt"]);
										$parentVectorProjectID = getReagentProjectID($pvID);
										
										if (($pvID > 0) && !in_array($parentVectorProjectID, $allowedUP))
										{
											$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 3);
										}
										elseif (isset($_POST["cl_name_txt"]))
										{
											if ($_POST["cl_name_txt"] != "")
											{
												$clID = $gfunc->get_rid($_POST["cl_name_txt"]);
												$clProjectID = getReagentProjectID($clID);

												if (($currUserCategory != $_SESSION["userCategoryNames"]["Admin"]) && !in_array($clProjectID, $allowedUP))
													$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 4);
												else
													$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 1);
											}
											else
												$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 1);
										}
									}
								}
								elseif (isset($_GET["ErrCode"]))
								{
									if ($_GET["ErrCode"] == 5)
									{
										// Insert modification - entered inaccessible IPV ID
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 5);
									}
									elseif ($_GET["ErrCode"] == 6)
									{
										// Insert modification - entered inaccessible Sense Oligo ID
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 6);
									}
									elseif ($_GET["ErrCode"] == 7)
									{
										// Insert modification - entered inaccessible Antisense Oligo ID
										$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 7);
									}
								}
								// Jan. 31/08: Process Insert creation from Primer Design view
								elseif (isset($_GET["mode"]) && ($_GET["mode"] == 'Create'))
								{
									$rout_obj->print_Detailed_Reagent("Modify", $_GET["View"], $_GET["rid"], 1);
								}
								else
								{
									$rout_obj->print_Detailed_Reagent($_GET["mode"], $_GET["View"], $_GET["rid"], 1);
								}
							}

							echo "</td></tr></table>";	// feb. 8/08 - close table here to have footer display properly (over entire page, incl. side menu)
						}
						elseif( $_GET["View"] == "7" )
						{
							# Sept 8, 2006 -- New Primer Design View
							$gfunc_obj = new generalFunc_Class();
							$rfunc_obj = new Reagent_Function_Class();

							# Display general header
							# ....

							# Specific reagent info
							if (isset($_GET["rid"]))
							{
								$rID = $_GET["rid"];

								if (isset($_POST["get_primers"]))
								{
									// Update Jan. 15, 2011: added strtolower() to avoid case-sensitivity issues
									$fwd_linker = strtolower($rfunc_obj->filterSpaces($_POST["fwd_linker"]));
									$rev_linker = strtolower($rfunc_obj->filterSpaces($_POST["rev_linker"]));

									$fwdLen = $_POST["fwd_length"];
									$revLen = $_POST["rev_length"];

									$fwdTm = $_POST["fwd_tm"];
									$revTm = $_POST["rev_tm"];

									# Create primers and show them on the same page below
									echo "<table width=\"700px\" border=\"0\" style=\"font-face:Helvetica; font-size:10px\" cellpadding=\"5\">";

										echo "<th colspan=\"5\" style=\"font-size:12px; text-decoration:underline; text-align:left; color:#DA000D; font-weight:bold\">PRIMER DESIGN</th>";

										$constSeq = $_POST["const_dna_seq_" . $rID];
										$cDNASeq = $rfunc_obj->spaces($constSeq);
										$protSeq = $_POST["const_prot_seq_" . $rID];
										$primStart = $_POST["aa_start_" . $rID];
										$primEnd = $_POST["aa_end_" . $rID];
										$tar_seq = $rfunc_obj->getSeqToClone($constSeq, $primStart, $primEnd);

										$primers_ar = $rfunc_obj->getPrimers($constSeq, $primStart, $primEnd, $fwd_linker, $rev_linker, $fwdLen, $revLen, $fwdTm, $revTm);	// 12/09/06

										// Retrieving primers and their Tm from an associative array
										$fwd_ar = $primers_ar[0];

										foreach ($fwd_ar as $pr=>$tm)
										{
											$fwd_primer = $pr;
											$fwd_tm = $tm;
										}

										$rev_ar = $primers_ar[1];

										foreach ($rev_ar as $pr=>$tm)
										{
											$rev_primer = $pr;
											$rev_tm = $tm;
										}

											/* Removed Sept 13/06 -- Karen asked to preserve original linker spacing
											# filter spaces from primers - it is formatted on return, perhaps change that and format only before display
											$tmp_fwd = $rfunc_obj->filterSpaces($fwd_primer);
											$tmp_rev = $rfunc_obj->filterSpaces($rev_primer);

											# primer concatenated with linker (w/o spaces)
											$tmp_fp = $fwd_linker . $tmp_fwd;
											$tmp_rp = $rev_linker . $tmp_rev;
											*/

										// Instead:
										$tmp_fp = $_POST["fwd_linker"] . $fwd_primer;
										$tmp_rp = $_POST["rev_linker"] . $rev_primer;

										// added jan 31/08
										echo "<tr>";

										echo "<td style=\"font-size:11px; white-space:nowrap; font-weight:bold\">cDNA sequence:&nbsp;&nbsp;&nbsp;&nbsp;";

										// May 11/09: Show 'Create' link IFF the user is authorized to create reagents (i.e. access level >= 'Writer').  Primer Design is NOT restricted for readers, BUT they CANNOT save any reagents into the system!!!!
										if ($currUserCategory != $_SESSION["userCategoryNames"]["Reader"])
										{
											echo "<SPAN class=\"linkShow\" style=\"font-size:11px; font-weight:normal;\" onClick=\"document.createInsert.submit();\">Create Insert</SPAN></td></tr>";
										}

										echo "<tr><td colspan=\"5\">";
											echo "<pre font-size:10px>";

											$seqOut = $rfunc_obj->dnaSpace($tar_seq, $_POST["fwd_linker"], $rfunc_obj->reverse_complement($_POST["rev_linker"]));

											echo "<div id=\"sel_dna_div_" . $rID . "\" name=\"sel_dna_div_" . $rID . "\" style=\"white-space:normal; height:auto\">" . $seqOut  . "</div>";

											echo "</pre>";
										echo "</td></tr>";

										echo "<tr>";
											echo "<td colspan=\"5\" style=\"font-weight:bold; font-size:11px\">Forward Primer:</td>";
										echo "</tr>";

										echo "<tr>";
											echo "<td style=\"font-size:11px; white-space:nowrap\" width=\"25%\">" . $tmp_fp . "</td>";

											echo "<td style=\"font-size:11px; white-space:nowrap\" width=\"8%\"><B>Length:</B> &nbsp;" . strlen($rfunc_obj->filterSpaces($tmp_fp)) . "</td>";

											// Jan. 31/08: Store TM in a variable
											$fp_tm = $rfunc_obj->calcTemp($rfunc_obj->filterSpaces($tmp_fp));

											echo "<td style=\"font-size:11px; white-space:nowrap\" width=\"20%\"><B>Tm (w/o linker):</B>&nbsp;" . $fwd_tm  . "<B>&nbsp;&#176;C</B></td>";

											echo "<td style=\"font-size:11px; white-space:nowrap\" width=\"20%\"><B>Tm (with linker):</B> &nbsp;" . $fp_tm  . "<B>&nbsp;&#176;C</B></td>";

											// Jan. 31/08: Store MW in a variable
											$fp_mw = $rfunc_obj->get_mw($rfunc_obj->filterSpaces($tmp_fp));

											echo "<td style=\"font-size:11px; white-space:nowrap\"><B>MW:&nbsp;</B>" . $fp_mw . "<B>&nbsp;g/mole</B></td>";

											// June 18/10: Output GC content
											$fp_gc = $rfunc_obj->get_gc_content($rfunc_obj->filterSpaces($tmp_fp));

											echo "<td style=\"font-size:11px; white-space:nowrap\"><B>GC %:&nbsp;</B>" . $fp_gc . "</td>";

											// JAN. 31/08: Proceed to Oligo creation
											echo "<FORM name=\"createSenseOligo\" method=\"POST\" action=\"" . $cgi_path . "create.py\">";

											// Store all attributes as hidden form values
											echo "<INPUT TYPE=\"hidden\" NAME=\"primer_sequence\" VALUE=\"" . $tmp_fp . "\">";

											echo "<INPUT TYPE=\"hidden\" NAME=\"create_oligo\">";

											echo "<INPUT TYPE=\"hidden\" NAME=\"primer_mw\" VALUE=\"" . $fp_mw . "\">";

											echo "<INPUT TYPE=\"hidden\" NAME=\"primer_tm\" VALUE=\"" . $fp_tm . "\">";

											echo "<INPUT TYPE=\"hidden\" NAME=\"oligo_type\" VALUE=\"Sense\">";

											echo "<INPUT TYPE=\"hidden\" NAME=\"primer_insert\" VALUE=\"" . $rID . "\">";

											// Feb. 4/08: Pass linkers to Insert creation form
											echo "<INPUT TYPE=\"hidden\" NAME=\"fwd_linker\" VALUE=\"" . $fwd_linker . "\">";

											echo "<INPUT TYPE=\"hidden\" NAME=\"rev_linker\" VALUE=\"" . $rev_linker . "\">";

											// removed April 3/08 at Karen's request
											// echo "<td style=\"font-size:11px; white-space:nowrap\"><SPAN class=\"linkShow\" onClick=\"document.createSenseOligo.submit();\">Create Oligo</SPAN></td>";

											echo "</FORM>";
										echo "</tr>";

										echo "<tr>";
											echo "<td colspan=\"5\" style=\"font-weight:bold; font-size:11px\">Reverse Primer:</td>";
										echo "</tr>";

										echo "<tr>";
											echo "<td style=\"font-size:11px; white-space:nowrap\">" . $tmp_rp . "</td>";

											echo "<td style=\"font-size:11px; white-space:nowrap\"><B>Length:</B> &nbsp;" . strlen($rfunc_obj->filterSpaces($tmp_rp)) . "</td>";
											echo "<td style=\"font-size:11px; white-space:nowrap\"><B>Tm (w/o linker):</B>&nbsp;" . $rev_tm . "<B>&nbsp;&#176;C</B></td>";

											// Changed jan. 30/08: Stored TM in a variable
											$rp_tm = $rfunc_obj->calcTemp($rfunc_obj->filterSpaces($tmp_rp));

											echo "<td style=\"font-size:11px; white-space:nowrap\"><B>Tm (with linker):</B> &nbsp;" . $rp_tm . "<B>&nbsp;&#176;C</B></td>";

											// Changed jan. 30/08: Stored MW in a variable
											$rp_mw = $rfunc_obj->get_mw($rfunc_obj->filterSpaces($tmp_rp));

											echo "<td style=\"font-size:11px; white-space:nowrap\"><B>MW:&nbsp;</B>" . $rp_mw . "<B>&nbsp;g/mole</B></td>";

											// June 18/10: Output GC content
											$rp_gc = $rfunc_obj->get_gc_content($rfunc_obj->filterSpaces($tmp_rp));

											echo "<td style=\"font-size:11px; white-space:nowrap\"><B>GC %:&nbsp;</B>" . $rp_gc . "</td>";

											// NEW JAN. 30/08: Proceed to Oligo creation
											echo "<FORM name=\"createAntisenseOligo\" method=\"POST\" action=\"" . $cgi_path . "create.py\">";

											// Store all attributes as hidden form values
											echo "<INPUT TYPE=\"hidden\" NAME=\"primer_sequence\" VALUE=\"" . $tmp_rp . "\">";

											echo "<INPUT TYPE=\"hidden\" NAME=\"create_oligo\">";

											echo "<INPUT TYPE=\"hidden\" NAME=\"primer_mw\" VALUE=\"" . $rp_mw . "\">";

											echo "<INPUT TYPE=\"hidden\" NAME=\"primer_tm\" VALUE=\"" . $rp_tm . "\">";

											echo "<INPUT TYPE=\"hidden\" NAME=\"primer_insert\" VALUE=\"" . $rID . "\">";

											echo "<INPUT TYPE=\"hidden\" NAME=\"oligo_type\" VALUE=\"Antisense\">";

											// removed April 3/08 at Karen's request
											// echo "<td style=\"font-size:11px; white-space:nowrap\"><SPAN class=\"linkShow\" onClick=\"document.createAntisenseOligo.submit();\">Create Oligo</SPAN></td>";

											echo "</FORM>";

										// Jan. 31/08: Placing Insert form here, to include Oligo information
										echo "<FORM style=\"display:none\" name=\"createInsert\" method=\"POST\" action=\"" . $cgi_path . "create.py\">";

										// Insert Info
										echo "<INPUT TYPE=\"hidden\" name=\"create_insert\">";

										// Correction Jan. 17, 2011:
										// echo "<INPUT TYPE=\"hidden\" name=\"insert_seq\" value=\"" . $_POST["fwd_linker"] . $tar_seq . $rfunc_obj->reverse_complement($_POST["rev_linker"]) . "\">";

										// replaced Jan. 17, 2011
										echo "<INPUT TYPE=\"hidden\" name=\"insert_seq\" value=\"" . $fwd_linker . $tar_seq . $rfunc_obj->reverse_complement($rev_linker) . "\">";

										// Sense Oligo Info
										echo "<INPUT TYPE=\"hidden\" NAME=\"sense_sequence\" VALUE=\"" . $tmp_fp . "\">";

										echo "<INPUT TYPE=\"hidden\" NAME=\"sense_mw\" VALUE=\"" . $fp_mw . "\">";

										echo "<INPUT TYPE=\"hidden\" NAME=\"sense_tm\" VALUE=\"" . $fp_tm . "\">";

										echo "<INPUT TYPE=\"hidden\" NAME=\"sense_gc\" VALUE=\"" . $fp_gc . "\">";

										// Antisense Oligo Info
										echo "<INPUT TYPE=\"hidden\" NAME=\"antisense_sequence\" VALUE=\"" . $tmp_rp . "\">";

										echo "<INPUT TYPE=\"hidden\" NAME=\"antisense_mw\" VALUE=\"" . $rp_mw . "\">";

										echo "<INPUT TYPE=\"hidden\" NAME=\"antisense_tm\" VALUE=\"" . $rp_tm . "\">";


										echo "<INPUT TYPE=\"hidden\" NAME=\"antisense_gc\" VALUE=\"" . $rp_gc . "\">";

										# Original (template) Insert (for naming oligos)
										echo "<INPUT TYPE=\"hidden\" NAME=\"template_insert\" VALUE=\"" . $rID . "\">";

										# Feb. 12/08: Pass linkers
										echo "<INPUT TYPE=\"hidden\" NAME=\"fwd_linker\" VALUE=\"" . $fwd_linker . "\">";

										echo "<INPUT TYPE=\"hidden\" NAME=\"rev_linker\" VALUE=\"" . $rev_linker . "\">";

										echo "</FORM>";
										echo "</tr>";
									echo "</table>";
								}	// end if isset get primers
								else
								{
									echo "<form name=\"primers_form\" method=\"post\" onSubmit=\"return verifyTmAndLength()\" action=\"" . $_SERVER["PHP_SELF"] . "?View=7&rid=" . $rID . "\">";

										echo "<table border=\"0\" cellpadding=\"5\">";
											echo "<th style=\"text-align:left\">PRIMER DESIGN</th>";
											echo "<input type=\"hidden\" name=\"reagents[]\" value=\"" . $rID . "\">";
											echo "<tr><td><a style=\"font-size:11px; text-decoration:underline\" href=\"" . $_SERVER["PHP_SELF"] . "?View=6&rid=" . $rID . "\">" . $gfunc_obj->getConvertedID_rid($rID) . "</a>&nbsp;\t";
	
											# retrieve PROTEIN sequence (hardcoded property ID but selection trivial)
											echo "<font style=\"font-size:10px; font-weight:bold\">Translated Protein Sequence:";
		
											// Modified Nov. 5/08 - removed 'length' from query - no longer stored as column, calculate on the spot
											$protSeqPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["protein translation"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);
		
											$protSeqRS = mysql_query("SELECT s.`sequence`, s.`frame`, s.`start`, s.`end` FROM `Sequences_tbl` s, `ReagentPropList_tbl` r WHERE r.`reagentID`='" . $rID . "' AND r.`propertyID`='" . $protSeqPropID . "' AND s.`seqID`=r.`propertyValue` AND r.`status`='ACTIVE' AND s.`status`='ACTIVE'", $conn) or die("Could not select sequence: " . mysql_error());
	
											if ($seqResult = mysql_fetch_array($protSeqRS, MYSQL_ASSOC))
											{
												$constProtSeq = $seqResult["sequence"];
												$protSeq = $rfunc_obj->spaces($constProtSeq);
												$frame = $seqResult["frame"];
												$startpos = $seqResult["start"];
												$endpos = $seqResult["end"];
		
												$length = strlen($constProtSeq);
		
												echo "&nbsp;length " . $length . "</font><BR>";
		
												echo "<input type=\"hidden\" name=\"const_prot_seq_" . $rID . "\" id=\"const_protSeq_" . $rID . "\" value=\"" . $constProtSeq . "\">";
		
												echo "</td></tr>";	// for the header row
		
												echo "<tr><td>";
													# Aug 16/06
													echo "<table width=\"100\" cellpadding=\"2\" border=\"0\" style=\"font-size:8px\">";
		
		
													echo "<th>";
														for ($x = 1; $x <= 10; $x++)
														{
															if ($length >= $x*10)
															{
																echo "<TD style=\"font-size:10px;text-align:right;  font-weight:bold\">" . $x*10 . "</TD>";
															}
														}
													echo "</th>";
		
		
													$tmp_prot = split("\n", $protSeq);
		
													foreach ($tmp_prot as $i=>$tok)
													{
														echo "<TR>";
															// Updated Sept 13/06, Karen asked for row index on the side
															$rowIndex = $i+1;
		
															if ($i == 0)	// first row
															{
																$rowStart = 1;
															}
															else
															{
																$rowStart = $i*100 + 1;
															}
		
															if ($length >= ($rowIndex*100))
															{
																$rowEnd = $rowIndex*100;
															}
															else
															{
																$rowEnd = $length;
															}
		
															echo "<TD  style=\"white-space:nowrap; font-size:10px; font-weight:bold; text-align:right; vertical-align:top\">" . $rowStart . "&nbsp;-&nbsp;" . $rowEnd . "</TD>";
		
															$tmp_chunks = split(" ", $tok);
		
															foreach ($tmp_chunks as $j=>$chunk)
															{
																$chunk = trim($chunk);
		
																if (strlen($chunk) > 0)
																{
																	echo "<TD>";
																		echo "<pre font-size:11px;  text-align:justify>" . $chunk . "</pre>";
																	echo "</TD>";
																}
															}
														echo "</TR>";
													}
		
													echo "</table>";
												echo "</td></tr>";
		
												echo "<input type=\"hidden\" name=\"prot_seq\" value=\"" . $protSeq . "\">";
											}
	
											// August 14, 2006: cDNA -- Moved here; Karen requested removing cDNA from webpage
											$constSeq = $rfunc_obj->reverseTranslate($protSeq, $rID, $frame, $startpos, $endpos, $length);
		
											echo "<input type=\"hidden\" name=\"const_dna_seq_" . $rID . "\" id=\"const_dnaSeq_" . $rID . "\" value=\"" . $constSeq . "\">";
		
											// Sept. 11/08: Protein start and end translation indices 
											echo "<input type=\"hidden\" id=\"prot_start\" value=\"" . $startpos . "\">";
											echo "<input type=\"hidden\" id=\"prot_stop\" value=\"" . $endpos . "\">";
		
											// Sept. 11/08: cDNA start and end indices
											$cdnaStart = $rfunc_obj->getStartPos($rID, "cdna insert", $_SESSION["ReagentProp_Name_ID"]["cdna insert"]);
											$cdnaEnd = $rfunc_obj->getEndPos($rID, "cdna insert", $_SESSION["ReagentProp_Name_ID"]["cdna insert"]);
		
											echo "<input type=\"hidden\" id=\"cdna_start\" value=\"" . $cdnaStart . "\">";
											echo "<input type=\"hidden\" id=\"cdna_stop\" value=\"" . $cdnaEnd . "\">";
		
											// Primer selection section
											echo "<tr><td>";
												echo "<table width=\"100%\" id=\"primers_tbl_" . $rID . "\" cellpadding=\"5\" cellspacing=\"5\" style=\"display:inline\" border=\"0\">";
													echo "<th colspan=\"3\" style=\"font-size:11px;  text-align:left; white-space:nowrap\">PRIMER BOUNDARIES DEFINITION</th>";
		
													echo "<tr><td colspan=\"3\" style=\"font-size:11px; white-space:nowrap\">Please enter the <b>Amino Acid start and stop</b> positions into the boxes below and press &#34GO&#34:</td></tr>";
				
													echo "<tr>";
														echo "<td colspan=\"3\" style=\"font-size:11px\" width=\"10%\">Start:&nbsp;&nbsp;&nbsp;<input type=\"text\" style=\"color:#000000; font-weight:regular\" size=\"4px\" id=\"aa_start_txt_" . $rID . "\" name=\"aa_start_" . $rID . "\" value=\"";
				
														if (isset($_POST["aa_start"]))
														{
															echo $_POST["aa_start"] . "\"></td>";
														}
														else
														{
															echo "\">";
														}
				
														echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;End:&nbsp;&nbsp;&nbsp;<input type=\"text\" style=\"color:#000000; font-weight:regular\" size=\"4px\" id=\"aa_end_txt_" . $rID . "\" name=\"aa_end_" . $rID . "\" value=\"";
				
														if (isset($_POST["aa_end"]))
														{
															echo $_POST["aa_end"] . "\"></td>";
														}
														else
														{
															echo "\">";
														}
				
														echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"button\" id=\"aa_bounds_" . $rID . "\" value=\"Go\" onClick=\"return getBounds('protein', " . $rID . ")\"></td>";
													echo "</tr>";
		
													echo "<tr><td colspan=\"3\">";
														echo "<pre>";
															echo "<div id=\"sel_prot_div_" . $rID . "\" name=\"sel_prot_div_" . $rID . "\" style=\"width:900px; height:auto\"></div>";
														echo "</pre>";
													echo "</td></tr>";
		
													echo "<tr><td colspan=\"3\">";
														echo "<pre font-size:10px>";
															echo "<div id=\"sel_dna_div_" . $rID . "\" name=\"sel_dna_div_" . $rID . "\" style=\"width:900px; height:auto\"></div>";
														echo "</pre>";
													echo "</td></tr>";
		
													# Linkers
													echo "<tr><td colspan=\"3\">";
														echo "<table border=\"0\" id=\"linkers_tbl\" width=\"100%\" style=\"display:none\" cellspacing=\"5\" cellpadding=\"5\">";
															echo "<tr>";
																echo "<td width=\"250px\" colspan=\"2\" style=\"font-size:11px; font-weight:bold; white-space:nowrap\">Would you like to add primer extensions?&nbsp;&nbsp;";
			
																	echo "<select id=\"linker_selector\"onChange=\"showHideLinkers()\">";
																		echo "<option selected value=\"No\">No";
																		echo "<option value=\"Yes\">Yes";
																		echo "</select>";
																echo "</td>";
															echo "</tr>";
			
															echo "<tr id=\"linkers_row\" style=\"display:none\">";
																echo "<td colspan=\"2\">";
																	echo "<table width=\"100%\" cellspacing=\"5\" cellpadding=\"3\" border=\"0\"";
																		echo "<tr>";
																			echo "<td colspan=\"2\" style=\"font-size:11px; white-space:nowrap\">Please select primer extensions for subcloning into selected vectors:</td>";
																		echo "</tr>";
			
																		echo "<tr>";
																			echo "<td width=\"100px\" style=\"font-size:11px; font-weight:bold; white-space:nowrap\">Forward Primer extension:</td>";
			
																			echo "<td>";
																				echo "<select id=\"fwd_linker_types_selector\" name=\"fwd_linker_types\" onChange=\"selectLinkers()\" style=\"font-size:11px\">";
																					echo "<option selected value=\"default\">None";
																					echo "<option value=\"gw_atg\">Gateway (donor vector V1986) 5' linker with ATG";
																					echo "<option value=\"gw_no_atg\">Gateway (donor vector V1986) 5' linker without ATG";
																					echo "<option value=\"creator_v7_fusion_atg\">Creator (V7-based in fusion) 5' linker with ATG";
																					echo "<option value=\"creator_v7_fusion_no_atg\">Creator (V7-based in fusion) 5' linker without ATG";
																					echo "<option value=\"creator_v37_fusion_atg\">Creator (V37-based in fusion) 5' linker with ATG";
																					echo "<option value=\"creator_v37_fusion_no_atg\">Creator (V37-based in fusion) 5' linker without ATG";
																					echo "<option value=\"his_vector_atg\">His-vector (pET28 SacB AP V2082 In Fusion) 5' linker with ATG";
																					echo "<option value=\"his_vector_no_atg\">His-vector (pET28 SacB AP V2082 In Fusion) 5' linker without ATG";
				
																					// April 22, 2009: T7/SP6 Promoters w/ Kozak for Nick and Jerry
																					echo "<option value=\"t7_promoter\">T7 (T7 PCR Reticulocyte kit)";
																					echo "<option value=\"sp6_promoter\">SP6 (SP6 Wheat Germ kit)";
																					echo "<option value=\"other\">Other";
																				echo "</select>";
																			echo "</td>";
																		echo "</tr>";
			
																		echo "<tr><td>&nbsp;</td><td colspan=\"2\"><input type=\"text\" id=\"fp_linker\" style=\"font-size:12px\" size=\"65px\" name=\"fwd_linker\"></td></tr>";
			
																		echo "<tr>";
																			echo "<td style=\"font-size:11px; font-weight:bold; white-space:nowrap\">Reverse Primer Extension:</td>";
																			echo "<td>";
																				echo "<select id=\"rev_linker_types_selector\" name=\"rev_linker_types\" style=\"font-size:11px\" onChange=\"selectLinkers()\">";
																					echo "<option selected value=\"default\">None";
																					echo "<option value=\"gw_stop\">Gateway (donor vector V1986) 3' linker with stop codon";
																					echo "<option value=\"gw_no_stop\">Gateway (donor vector V1986) 3' linker without stop codon";
																					echo "<option value=\"creator_fusion_stop\">Creator (V7 or V37-based in fusion) 3' linker with stop codon";
																					echo "<option value=\"creator_fusion_no_stop\">Creator (V7 or V37-based in fusion) 3' linker without stop codon";
																					echo "<option value=\"his_vector_stop\">His-vector (pET28 SacB AP V2082 In Fusion) 3' linker with stop codon";
																					echo "<option value=\"his_vector_no_stop\">His-vector (pET28 SacB AP V2082 In Fusion) 3' linker without stop codon";
			
																					// April 22, 2009: Flag-Tag for Nick and Jerry
																					echo "<option value=\"flag_tag\">C-Terminal FLAG";
			
																					echo "<option value=\"other\">Other";
																				echo "</select>";
																			echo "</td>";
																		echo "</tr>";
			
																		echo "<tr><td>&nbsp;</td><td colspan=\"2\"><input type=\"text\" style=\"font-size:12px\" size=\"65px\" id=\"tp_linker\" name=\"rev_linker\"></td></tr>";
																	echo "</table>"; // 5' & 3' linkers
																echo "</td>";
															echo "</tr>";
		
															# Limit primer size
															echo "<tr>";
																echo "<td style=\"font-size:11px\" colspan=\"2\">";
																	echo "<b>How would you like to limit primer size?</b>";																
																echo "</td>";
															echo "</tr>";
			
															echo "<tr>";
																echo "<td style=\"font-size:11px\" colspan=\"2\">";
																	echo "<HR><u><B>Note:</B></u><BR><P>Primers are generated adding one nucleotide at a time until either Tm or size limit is reached.";
																	echo "<BR><P>The <b><i>length</i></b> parameter is <u>optional</u>.  If no length is provided, primer assembly stops when 'Tm' is reached.  If 'length' is entered, primer generation stops when its size becomes equal to the 'length' value, even if 'Tm' is not reached at that point.";
																	echo "<BR><P>The <b><i>Tm</i></b> parameter is <u>mandatory</u>.  You may overwrite the default value of 55 by entering a Tm of your choice in the appropriate textbox.<HR><BR>";
																echo "</td>";
															echo "</tr>";
			
															echo "<tr>";
																echo "<td colspan=\"3\"><table width=\"100\" cellpadding=\"5px\" border=\"0\">";
																echo "<tr>";
																	echo "<td style=\"font-size:11px; font-weight:bold; white-space:nowrap\">5' primer:</td>";
																	echo "<td style=\"font-size:11px; white-space:nowrap\">First by length (including linkers):</td>";
																	echo "<td><input type=\"text\" id=\"fwd_length_id\" name=\"fwd_length\" size=\"5px\"></td>";
																	echo "<td style=\"font-size:11px; white-space:nowrap\">Then by Tm (not including linkers):</td>";
																	echo "<td><input type=\"text\" id=\"fwd_tm_id\" name=\"fwd_tm\" size=\"5px\" value=\"55\"></td>";
																echo "</tr>";
			
																echo "<tr>";
																	echo "<td style=\"font-size:11px; font-weight:bold; white-space:nowrap\">3' primer:</td>";
																	echo "<td style=\"font-size:11px; white-space:nowrap\">First by length (including linkers):</td>";
																	echo "<td><input type=\"text\" id=\"rev_length_id\" name=\"rev_length\" size=\"5px\"></td>";
																	echo "<td style=\"font-size:11px; white-space:nowrap\">Then by Tm (not including linkers):</td>";
																	echo "<td><input type=\"text\" id=\"rev_tm_id\" name=\"rev_tm\" size=\"5px\" value=\"55\"></td>";
																echo "</tr>";
															echo "</tr>";
														echo "</table>";	// linkers and primer size limits
													echo "</td></tr>";
												echo "</table>";	// inner primer table
											echo "</td></tr>";
		
											echo "<tr>";
												// Updated June 9/09: Do not submit form if user hits 'Enter'
												echo "<td colspan=\"3\"><input type=\"submit\" style=\"display:none\" id=\"get_primers_btn\" name=\"get_primers\" value=\"Get Primers\"></td>";
											echo "</tr>";
										echo "</table>";
									echo "</form>";

									// Sept. 1, 2010: this is the closing tag for the main DIV table; w/o it the footer doesn't go to bottom of page.  KEEP IT!!
									echo "</table>";	// outer table
								}	// end else
							}	// end if isset rid
						}	// end view 7
						?>
					</td>
				</tr>
			</table>
		<?php
				mysql_close();
				outputMainFooter();
			}
			else
			{
						echo "<tr><td class=\"warning\">";
						echo "You are not authorized to view this page.  Please contact the site administrator.";
						echo "</td></tr>";
						?>
					</table>
				<?php

				outputMainFooter();
			}
		}
		else
		{
			// April 9/07, Marina: May output general info page for non-registered users
			if (isset($_GET["View"]) && ($_GET["View"] == 1))
			{
				$reagent_obj = new Reagent_Output_CLass();
				$reagent_obj->print_reagent_intro();

				?>
				</table>
				<?php

				outputMainFooter();
			}
			else
			{
				echo "<tr><td class=\"warning\">";
				echo "Please log in to access this page.";
				echo "</td></tr>";
				
				?>
				</table>
				<?php
			
				outputMainFooter();
			}
			?>
			</table>
			<?php
		}
		
		mysql_close($conn);
		?>
	</TABLE>
	<?php


	/**
	* Output reagent type selection list
	*
	* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	* @version 3.1 2009-06-03
	*/
	function printViewReagentTypesForm()
	{
		global $cgi_path;
		global $conn;
		
		$currUserName = $_SESSION["userinfo"]->getDescription();
		$currUserID = $_SESSION["userinfo"]->getUserID();

		$r_out = new Reagent_Output_Class();

		?>
			<FORM METHOD="POST" ACTION="<?php echo $cgi_path . "reagent_type_request_handler.py"; ?>">

				<!-- Pass user info as a hidden form value to Python -->
				<INPUT type="hidden" ID="username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
				
				<TABLE width="775px" border="1px" frame="box" rules="none" cellpadding="5">
				
					<TH colspan="2" class="title">
						SEARCH REAGENT TYPES
					</TH>
		

					<TR>
						<TD style="font-weight:bold; padding-left:10px;">
							Select reagent type from the list below and click 'Go' to view/modify its attributes
						</TD>
					</TR>

					<TR>
						<TD style="padding-left:10px;">
							<?php
								$r_out->printReagentTypesList();
							?>
						</TD>
					</TR>

					<TR>
						<TD style="padding-left:10px;">
							<P><INPUT TYPE="SUBMIT" id="viewReagentType" NAME="view_reagent_type" VALUE="Go" onClick="return checkSelected('reagentTypes');">
						</TD>
					</TR>

					<TR><TD>&nbsp;</TD></TR>
				</TABLE>
			</FORM>
		<?php
	}
?>
