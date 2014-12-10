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
* @package Reagent
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
/**
 * Contains functions for creation of reagents
 *
 * @author John Paul Lee @version 2005
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Reagent
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 *
*/
class Reagent_Creator_Class
{
	/**
	 * @var Array
	 * A map of cell line association type ID => name relationships, i.e. (1 => "Parent", 2 => "Stable").  Does NOT mirror AssocType_tbl column values, just for internal reference.
	*/
	var $CellLine_SubCategory_ID_Name;

	/**
	 * @var Array
	 * A map of cell line association type name => ID relationships, i.e. ("Parent" => 1, "Stable" => 2), the inverse of $CellLine_SubCategory_ID_Name.  Does NOT mirror AssocType_tbl column values, just for internal reference.
	*/
	var $CellLine_SubCategory_Name_ID;	// this is the one used in creation form (aug. 27, 2010)!!!

	/**
	 * @var STRING
	 * Error message returned by this class in various cases (contains the name of this class to help pinpoint the source of the error)
	*/
	var $classerror;

	// Helper objects

	/**
	 * @var Reagent_Background_Class
	 * Helper object (instance of Reagent_Background_Class) to handle reagent parent-child associations
	*/
	var $bfunc_obj;

	/**
	 * @var generalFunc_Class
	 * Helper object (instance of generalFunc_Class) to execute general functions
	*/
	var $gfunc_obj;

	/**
	 * @var Reagent_Function_Class
	 * Helper object (instance of Reagent_Function_Class) to execute general functions with respect to reagents
	*/
	var $rfunc_obj;

	/**
	 * Default constructor
	*/
	function Reagent_Creator_Class()
	{
		$this->CellLine_SubCategory_ID_Name = array(1 => "Parent", 2 => "Stable");
		$this->CellLine_SubCategory_Name_ID = array_flip( $this->CellLine_SubCategory_ID_Name);

		$this->classerror = "Reagent_Creator_Class.";
		$this->bfunc_obj = new Reagent_Background_Class();
		$this->gfunc_obj = new generalFunc_Class();
		$this->rfunc_obj = new Reagent_Function_Class();
	}
	
	/**
	 * Print a selection form for reagent types (user selects the type of reagent to create from a dropdown list)
	*/
	function printReagentTypeSelection()
	{
		global $cgi_path;
		
		$r_out = new Reagent_Output_Class();
		$gfunc_obj = new generalFunc_Class();
		$rfunc_obj = new Reagent_Function_Class();

		?>
		<FIELDSET style="width:745px;">
			<LEGEND>What type of reagent would you like to create?</LEGEND>
			
			<SELECT id="reagentTypes" name="reagentType" onChange="showReagentSubtype()">
				<OPTION SELECTED value="default">Select reagent type</OPTION>
				<OPTION DISABLED>&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;&nbsp;&#45;</OPTION>

				<?php
					$rTypeNames = array_keys($_SESSION["ReagentType_Name_ID"]);
					sort($rTypeNames, SORT_STRING);

					foreach ($rTypeNames as $key => $r_type)
					{
						$rTypePrefix = $_SESSION["ReagentType_Name_Prefix"][$r_type];

						echo "<OPTION VALUE=\"" . $r_type . "\">";
				
						if ($r_type == "CellLine")
							echo "Cell Line (" . $rTypePrefix . ")";
						else
							echo $r_type . " (" . $rTypePrefix . ")";
				
						echo "</OPTION>";
					}
				?>
			</SELECT>
		</FIELDSET>

		<TABLE ID="rTypeSelect" width="745px" border="0">
			<TR>
				<TD style="padding-left:10px; padding-top:10px;">
					<?php
						$vDisplay = "none";
						$cDisplay = "none";
					?>
						<SPAN ID="vector_heading" style="display:none; font-size:10pt; font-weight:bold;">Please indicate Vector subtype:<BR><BR></SPAN>

						<SPAN ID="cell_line_heading" style="display:none; font-size:10pt; font-weight:bold;">Please indicate Cell Line subtype:<BR><BR></SPAN>
					<?php
						// Vector and Cell Line subtype selection lists - both hidden initially
						$r_out->printReagentSubtype($_SESSION["ReagentType_Name_ID"]["Vector"], $subtype, "vectorSubtypes", "vectorSubtype", 0, $vDisplay, $cDisplay);
						
						// Cell Line subtype selection
						$r_out->printReagentSubtype($_SESSION["ReagentType_Name_ID"]["CellLine"], $subtype, "cellLineSubtypes", "cellLineSubtype", 0, $vDisplay, $cDisplay);
					?>
				</TD>
			</TR>
		
			<!-- April 11/07: Illustration of different vector and cell line subtypes -->
			<TR>
				<TD style="padding-left:10px; padding-top:10px;">
					<A href="Reagent/vector_assoc.png" id="vector_types_diagram" style="display:<?php echo /*($rType == "Vector") ? "inline" :*/ "none"; ?>" onClick="return popup(this, 'diagram', '650', '725', 'yes')">Click here to view an illustration of vector types and associations</A>

					<A href="Reagent/cell_assoc.png" id="cell_line_types_diagram" style="display:<?php echo /*($rType == "CellLine") ? "inline" : */"none"; ?>" onClick="return popup(this, 'diagram', '650', '725', 'yes')">Click here to view an illustration of cell line types and associations</A>
				</TD>
			</TR>

		</TABLE>
		<?php
	}


	/**
	 * Print the form to add a new reagent type
	 * @param INT
	 * @param boolean
	 * @author Marina Olhovsky
	 * @version 2009-04-23
	*/
	function printFormAddReagentType($step=1, $goback=false)
	{
		global $conn;
		global $cgi_path;

		$genPrefix = "reagent_detailedview_";
		$genPostfix = "_prop";

		$r_out = new Reagent_Output_Class();
		$gfunc_obj = new generalFunc_Class();
		$rfunc_obj = new Reagent_Function_Class();

		$currUserName = $_SESSION["userinfo"]->getDescription();

		?>
		<FORM METHOD="POST" ACTION="<?php echo $cgi_path . "reagent_type_request_handler.py"; ?>" NAME="addReagentTypeForm" ID="add_reagent_type_form" onSubmit="enableCheckboxes(); return verifyNewReagentTypeName() && verifyNewReagentTypePrefix();">
			<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
		
			<TABLE width="768px" style="margin-left:10px; display:<?php echo $display; ?>; padding:5px;" cellpadding="4" cellspacing="2" ID="addReagentTypeTable">
				<TH style="font-size:13pt; text-align:left; padding-left:10%; font-weight:bold; color:#0000FF; white-space:nowrap; padding-top: 10px; padding-top:5px;">
					CREATE A NEW REAGENT TYPE
				</TH>
		
				<TR>
					<TD style="font-size:10pt; text-align:left; padding-left:16%; font-weight:bold; color:#00C0DD; white-space:nowrap; padding-top:5px;">
						Step 1 - Define Attributes
					</TD>
				</TR>
		
				<TR>
					<TD colspan="2" style="font-weight:bold;">
						1. Please indicate the <u>name</u> of the new reagent type (e.g. Antibody, Virus, etc.):<BR><BR>

						<SPAN style="font-weight:normal; color:brown; margin-left:16px;">'Sequence', 'DNA Sequence', 'RNA Sequence', 'Protein Sequence', 'DNA' or 'RNA' may <u>NOT</u> be used as reagent<BR><SPAN style="font-weight:normal; color:brown; margin-left:16px;">type names.&nbsp;&nbsp;'RNAi' or 'siRNA', however, are allowed.</SPAN>

						<P>
						<INPUT TYPE="TEXT" size="65px" style="margin-left:16px;" ID="reagent_type_name" NAME="reagentTypeName" VALUE="<?php if (isset($_GET["rType"])) echo $_GET["rType"]; ?>" onKeyPress="return disableEnterKey(event);">
				
						<BR><div id="reagent_type_name_warning" style="display:none; font-weight:bold;"><span style="margin-left:16px; color:#FF0000;"><IMG SRC="../pictures/up_arrow.png" style="vertical-align:bottom; padding-bottom:4px;">&nbsp;&nbsp;Please provide a unique name at least 4 characters long for the new reagent type.</span><BR><P><span style="margin-left:16px;">'Sequence', 'DNA Sequence', 'RNA Sequence', 'Protein Sequence', 'DNA' or 'RNA' are <U>NOT</U> valid</SPAN><BR><span style="margin-left:16px;">reagent type names.</span></div>
					</TD>
				</TR>
		
				<TR ID="rTypePrefixRow">
					<TD colspan="2" style="text-align:justify;">

						<!-- Keep formatting -->
						<b><BR>2. Please specify a unique <u>prefix</u>, between one and three characters in length, that will be used <BR><span style="margin-left:16px;">to identify reagents of this type (e.g. 'V' for Vector):</span><BR></b>

						<BR><span style="margin-left:16px; white-space:nowrap;">Normally prefixes are a single character, usually the first letter of the reagent type name specified above.</span><BR><span style="margin-left:16px; white-space:nowrap;">However, other prefixes are acceptable.</span><BR>
					
						<P><INPUT TYPE="TEXT" size="5px" style="margin-left:16px;" ID="reagent_type_prefix" NAME="reagentTypePrefix" VALUE="<?php if (isset($_GET["prefix"])) echo $_GET["prefix"]; ?>" onKeyPress="return disableEnterKey(event);">
		
						<DIV id="reagent_type_prefix_warning" style="margin-left:16px; display:none; font-weight:bold; color:#FF0000"><IMG SRC="../pictures/up_arrow.png" style="vertical-align:bottom; padding-bottom:4px;">&nbsp;&nbsp;Please specify a unique prefix (3 characters or less, not containing digits) for the new reagent type</DIV>
					</TD>
				</TR>
		
				<TR ID="rTypeAssocRow">
					<TD>
						<TABLE>
							<TR>
								<TD style="white-space:nowrap; font-weight:normal;">
									<b><BR>3. Please define parent/child relations for the new reagent type<!--<SPAN NAME="newReagentType" style="font-weight:bold;"></SPAN>-->:<BR></b>
								</TD>
							</TR>
			
							<TR>
								<TD colspan="2" style="padding-left:12px; font-weight:nowrap; padding-top:10px; font-size:10pt;">
									Select <b>parent</b> types <!--for <SPAN NAME="newReagentType" style="font-weight:bold;"></SPAN> -->from the list below.<BR><SPAN style="color:red">If the sought type is not in the list, you need to create that type first.</SPAN><BR>

									<P><SELECT MULTIPLE id="add_reagent_type_parents" name="addReagentTypeParents"><?php

									foreach ($_SESSION["ReagentType_ID_Name"] as $key => $value)
									{
										echo "<OPTION " . $selected . " value=\"" . $value . "\">" . $value . "</OPTION>";
									}

									?>
									</SELECT><BR>

									<SPAN class="linkShow" id="select_all_rtype_parents_chkbx" onClick="selectAllElements('add_reagent_type_parents', false);" style="padding-top:12px; font-size:8pt;"> Select All</SPAN>

									<SPAN class="linkShow" id="clear_all_rtype_parents_chkbx" onClick="clearAllElements('add_reagent_type_parents');" style="padding-top:12px; padding-left:5px; font-size:8pt;"> Clear All</SPAN>
								</TD>
							</TR>

							<TR><TD style="padding-left:8px;"><INPUT TYPE="checkbox" ID="includeParent">&nbsp;Parent of itself</TD></TR>
						</TABLE>
					</TD>
				</TR>
		
				<TR ID="rTypePropsRow">
					<TD>
						<TABLE ID="addReagentPropsTbl" width="768px">
							<TR>
								
								<TD style="padding-top:10px; font-weight:bold;">
									<BR>4. Please select properties for the new reagent type in the following categories:<BR>
									<P><span style="font-size:10pt; padding-left:17px; padding-top: 7px; font-weight:normal; color:#FF0000;">If you do not wish to include a property, uncheck the checkbox next to it.</span>
								</TD>
							</TR>

							<TR>
								<td colspan="4" style="font-size:9pt; font-weight:normal; padding-left:18px; padding-top:5px; color:#000000;">Checkboxes whose selection is disabled are mandatory for all reagent types (e.g. 'Name', 'Status', 'Project ID', 'Description')</td>
							</TR>

							<!-- Aug. 24/09: Explain how to correct input errors -->
							<TR>
								<td colspan="4" style="padding-top:10px; padding-left:18px; color:green;">
									<DIV style="border:2px double gold; font-size:10pt; padding-right:28px;">
										<UL>
											<LI style="color:green">If a novel property name is mistyped, uncheck the checkbox next to the incorrect entry and re-enter it using the 'Add New Property' feature. Unchecked checkboxes are not saved; neither are categories that do not contain any properties, or whose properties have all been unchecked.<BR>

											<LI style="color:brown"><P>The term <b>'Sequence Features'</b> refers to <u>regions at specific positions</u> on a sequence in forward or reverse orientation (e.g. PolyA Tail, Tag, Selectable Marker).  Their values <b>are always selected from a pre-defined list.</b>  Properties that describe the entire sequence rather than portions of it should be stored under a different category (e.g. 'Annotations' or 'Classifiers').

											<LI style="color:green"><P>A property name may only be added to a category once (i.e. 'Name' cannot appear twice under "General Properties").  You will be given an opportunity to assign multiple values to a single property at the next step.

											<LI style="color:brown"><P>Multiple property names across categories entered in different LeTtErCaSe will be converted to the same lettercase at saving  (e.g. 'Alternate ID' and 'alternate id' are considered the same property, even if they appear in different categories).<BR><P><SPAN STYLE="color:red; font-weight:bold;">Existing property names cannot be changed.</SPAN>
										</UL>
									</DIV>
								</td>
							</TR><?php

							// July 31/09: List of properties to exclude from option list
							$ignoreList = Array();
							
							foreach ($_SESSION["ReagentType_ID_Name"] as $id => $name)
							{
								if ($name == "CellLine")
									$name = "Cell Line";
							
								$ignoreList[] = $name . " Type";
							}
							
							$ignoreList[] = "Type of Insert";
							$ignoreList[] = "Open/Closed";

							?><TR>
								<TD><?php

								foreach ($_SESSION["ReagentPropCategory_ID_Name"] as $categoryID => $category)
								{
									$catAlias = $_SESSION["ReagentPropCategory_ID_Alias"][$categoryID];
	
									if ((strcasecmp($category, "DNA Sequence") != 0) && (strcasecmp($category, "Protein Sequence") != 0) && (strcasecmp($category, "RNA Sequence") != 0) && (strcasecmp($category, "DNA Sequence Features") != 0) && (strcasecmp($category, "RNA Sequence Features") != 0) && (strcasecmp($category, "Protein Sequence Features") != 0))
									{
										?><P><TABLE ID="addReagentTypePropsTbl_<?php echo $catAlias; ?>" style="white-space:nowrap; width:auto;">
											<TR ID="<?php echo $catAlias; ?>_row">
												<TD style="font-weight:bold; padding-top:12px; color:#0000D2; white-space:nowrap;" colspan="4">
												<?php

													// Expand only the first category, General Properties; collapse the rest
													?><IMG id="<?php echo $categoryID . "_expand_img"; ?>" SRC="pictures/arrow_collapse.gif" WIDTH="20" HEIGHT="15" BORDER="0" ALT="plus" class="menu-expanded" style="display:inline;" onClick="showHideCategory('<?php echo $categoryID; ?>');">

													<IMG id="<?php echo $categoryID . "_collapse_img"; ?>" SRC="pictures/arrow_expand.gif" WIDTH="40" HEIGHT="34" BORDER="0" ALT="plus" class="menu-collapsed" style="display:none;" onClick="showHideCategory('<?php echo $categoryID; ?>');"><?php

													echo $category;
													
													?>
												</TD>
											</TR>
											
											<TR>
												<TD>
													<TABLE ID="category_<?php echo $categoryID; ?>_section" cellpadding="4">
														<TR>
															<TD colspan="4" style="padding-left:15px; white-space:nowrap">
																<input type="hidden" name="category[]" value="<?php echo $catAlias; ?>">

																<input type="hidden" name="category_descriptor_<?php echo $catAlias; ?>" value="<?php echo $category;?>">
											
																<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('<?php echo "createReagentType_" . $catAlias; ?>');">Check All</span>
							
																<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="uncheckAll" onClick="uncheckAll('<?php echo "createReagentType_" . $catAlias; ?>', Array('name', 'status', '<?php echo $_SESSION["ReagentProp_Name_Alias"]['packet id']; ?>', 'comments', 'type', 'description'));">Uncheck All</span>
															</TD>
														</TR>

														<TR><?php
															$props = $rfunc_obj->getPropertiesByCategory($categoryID);

															$mandatoryGeneral = array('name', 'status', 'packet id', 'type', 'comments', 'description');

$mandatoryGeneralPropIDs = array();

foreach ($mandatoryGeneral as $p_k => $p_nm)
{
	$mandatoryPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$p_nm], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

	$mandatoryGeneralPropIDs[] = $mandatoryPropID;
}


															$pCount = 0;

															foreach ($props as $key => $prop)	// $prop is an Object
															{
																$propName = "";
																$propDescr = "";

																try
																{
																	$propName = $prop->getPropertyName();
																	$propDescr = $prop->getPropertyDescription();
																}
																catch (Exception $e)
																{
																	// this is a feature
																	$propName = $prop->getFeatureType();
																	$propDescr = $prop->getFeatureDescription();
																	$featureValue = $prop->getFeatureValue();
																}

																// July 31/09: Some properties only apply to selected types, don't output them as options
																$toIgnore = 0;
																
																// do a full loop comparison here because of case sensitivity
																foreach ($ignoreList as $x => $val)
																{
																	if (strcasecmp($propName, $val) == 0)
																	{
																		$toIgnore = 1;
																		break;
																	}
																}
																
																if ($toIgnore == 1)
																	continue;

																if ($pCount%4 == 0)
																	echo "</TR><TR>";

																// Feb. 8/10
																$pc_id = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$propName], $categoryID);
// echo $propName . ": " . $pc_id;

// rmvd Feb 8/10															if (in_array($propName, $mandatoryGeneral))
																if (in_array($pc_id, array_values($mandatoryGeneralPropIDs)))
																{
																	$action = "onClick=\"this.checked = true;\"";
																	$readOnly = "DISABLED";
																}
																else
																{
																	$readOnly = "";
																	$action = "";
																}

																?><TD style="padding-left:20px; white-space:nowrap;"><INPUT TYPE="checkbox" NAME="createReagentType_<?php echo $catAlias . "[]"; ?>" VALUE="<?php echo $_SESSION["ReagentProp_Name_Alias"][$propName];?>" <?php echo $action; ?> <?php echo $readOnly; ?> checked>

<INPUT TYPE="hidden" ID="<?php echo $catAlias . "_:_" . $_SESSION["ReagentProp_Name_Alias"][$propName]; ?>_checkbox_desc_hidden" NAME="propCheckboxes[]" VALUE="<?php echo $propDescr; ?>">

<?php echo $propDescr; ?></TD>
<?php

																$pCount++;
															}

															if ($categoryID == 1)
															{
																if ($pCount%4 == 0)
																	echo "</TR><TR>";

																?><TD style="padding-left:20px; white-space:nowrap;"><INPUT TYPE="checkbox" onClick="this.checked = true;" NAME="createReagentType_general_properties[]" VALUE="type" CHECKED DISABLED>Type</TD><?php
															}

														?></TR>

														<tr>
															<td colspan="4" style="padding-left:20px; padding-top:10px; white-space:nowrap;">
																<b>Add new <?php echo $category; ?> property:<b>&nbsp;&nbsp;
																<INPUT type="text" size="35" ID="<?php echo $catAlias; ?>_other" value="" name="<?php echo $catAlias; ?>Other" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
							
																<input onclick="updateCheckboxListFromInput('<?php echo $catAlias; ?>_other', '<?php echo $catAlias; ?>', 'category_<?php echo $categoryID; ?>_section', 'add_reagent_type_form'); document.getElementById('<?php echo $catAlias; ?>_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
															</td>
														</tr>
													</TABLE>
												</TD>
											</TR>
										</TABLE><?php
									}
								}

								?></TD>
							</TR>

							<TR>
								<TD>
									<TABLE>
										<!-- Now print sequence and Features sections -->
		
										<!-- June 10/09: Allow selecting only one sequence type -->
										<TR>
											<TD colspan="4" style="white-space:nowrap; padding-top:10px;">

												<IMG id="sequence_expand_img" SRC="pictures/arrow_collapse.gif" WIDTH="20" HEIGHT="15" BORDER="0" ALT="plus" class="menu-expanded" style="display:inline" onClick="showHideCategory('sequence');">

												<IMG id="sequence_collapse_img" SRC="pictures/arrow_expand.gif" WIDTH="40" HEIGHT="34" BORDER="0" ALT="plus" class="menu-collapsed" style="display:none" onClick="showHideCategory('sequence');">

												<span style="font-weight:bold; color:#0000D2;">Sequence</span>
			
													<DIV ID="category_sequence_section">
														<P><SPAN style="margin-left:25px;">Select one of the following sequence types:</SPAN><BR>
				
														<P>
														<INPUT style="margin-left:25px; font-size:11pt;" TYPE="radio" ID="sequenceRadioNone" NAME="sequenceType" VALUE="None" checked onClick="hideSequenceSections();">None
														<INPUT style="margin-left:25px; font-size:11pt;" TYPE="radio" ID="sequenceRadioDNA" NAME="sequenceType" VALUE="DNA Sequence" onClick="showDNASequenceSection();">DNA Sequence
														<INPUT style="margin-left:25px; font-size:11pt;" TYPE="radio" ID="sequenceRadioProtein" NAME="sequenceType" VALUE="Protein Sequence" onClick="showProteinSequenceSection();">Protein Sequence
														<INPUT style="margin-left:25px; font-size:11pt;" TYPE="radio" ID="sequenceRadioRNA" NAME="sequenceType" VALUE="RNA Sequence" onClick="showRNASequenceSection();">RNA Sequence
													</DIV>
											</td>
										</tr>
									
										<TR ID="dna_sequence_heading_row" style="display:none;">
											<TD colspan="4" style="padding-top:10px; white-space:nowrap; padding-left:20px;">
												<BR><IMG SRC="pictures/star_bullet.gif" WIDTH="10" HEIGHT="10" BORDER="0" ALT="bullet" style="padding-right:8px; vertical-align:middle; padding-bottom:2px;"><b>DNA Sequence</b>
						
												<input type="hidden" ID="dnaSequenceCategoryInput" name="category[]" value="sequence_properties">
			
												<input type="hidden" ID="dnaSequenceCategoryDescriptionInput" name="category_descriptor_sequence_properties" value="DNA Sequence">
						
												<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('createReagentType_sequence_properties');">Check All</span>

												<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="uncheckAll" onClick="uncheckAll('createReagentType_sequence_properties', Array('<?php echo $_SESSION["ReagentProp_Name_Alias"]["sequence"]; ?>'));">Uncheck All</span>
											</TD>
										</TR>
				
										<tr ID="dna_sequence_props_row" style="display:none;">
											<TD colspan="4" style="padding-left:25px;">
												<TABLE cellspacing="4" ID="addReagentTypeSequenceProperties"><?php
													$pCount = 0;
													echo "<TR>";

													$seqPropSet =  $rfunc_obj->getPropertiesByCategory($_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);
													
													foreach ($seqPropSet as $key => $prop)
													{
														if ($pCount%4 == 0)
															echo "</TR><TR>";
			
														if (strcasecmp($value, "sequence") == 0)
														{
															$action = "onClick=\"this.checked = true;\"";
															$readOnly = "DISABLED";
														}
														else
														{
															$readOnly = "";
															$action = "";
														}
		
														$value = $prop->getPropertyName();
													
														// June 11/09: Include category alias in property input name to handle multiple properties in different categories
														?><TD style="white-space:nowrap; padding-right:10px;">
															<INPUT TYPE="checkbox" NAME="createReagentType_sequence_properties[]" VALUE="<?php echo $_SESSION["ReagentProp_Name_Alias"][$value]; ?>" <?php echo $action; ?> <?php echo $readOnly; ?> checked>

<INPUT TYPE="hidden" ID="<?php echo $_SESSION["ReagentPropCategory_Name_Alias"]["DNA Sequence"] . "_:_" . $_SESSION["ReagentProp_Name_Alias"][$value]; ?>_checkbox_desc_hidden" NAME="propCheckboxes[]" VALUE="<?php echo $_SESSION["ReagentProp_Name_Desc"][$value]; ?>">

<?php echo $_SESSION["ReagentProp_Name_Desc"][$value]; ?></TD><?php
														$pCount++;
													}
						
													echo "</TR>";
												?>
												</TABLE>
											</TD>
										</TR>
				
										<tr ID="dna_sequence_add_new_props_row" style="display:none;">
											<td colspan="4" style="padding-left:25px; padding-top:10px; white-space:nowrap;">
												<b>Add new DNA sequence property:<b>&nbsp;&nbsp;
												<INPUT type="text" size="35" ID="sequence_properties_other" value="" name="sequence_propertiesOther" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;

												<input onclick="updateCheckboxListFromInput('sequence_properties_other', 'sequence_properties', 'addReagentTypeSequenceProperties', 'add_reagent_type_form'); document.getElementById('sequence_properties_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
											</td>
										</tr>

										<!-- Moved here June 10/09 - do separate property set for each sequence type -->
										<TR ID="dna_sequence_features_heading_row" style="display:none;">
											<TD colspan="4" style="padding-left:20px; padding-top:10px;">
												<BR><IMG SRC="pictures/star_bullet.gif" WIDTH="10" HEIGHT="10" BORDER="0" ALT="bullet" style="padding-right:8px; vertical-align:middle; padding-bottom:2px;"><b>DNA Sequence Features</b>
						
												<input type="hidden" ID="dnaFeaturesCategoryInput" name="category[]" value="dna_sequence_features">
			
												<input type="hidden" ID="dnaFeaturesCategoryDescriptorInput" name="category_descriptor_dna_sequence_features" value="DNA Sequence Features">
						
												<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('createReagentType_dna_sequence_features');">Check All</span>
												<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="uncheckAll" onClick="uncheckAll('createReagentType_dna_sequence_features', []);">Uncheck All</span>
						
												<BR><span style="font-size:9pt; padding-left:18px; font-weight:normal;">Properties that are linked to a sequence at specific positions and do not exist independently of it</span>
											</TD>
										</TR>

										<TR ID="dna_sequence_features_row" style="display:none;">
											<TD colspan="4" style="padding-left:25px;">
												<P><TABLE cellspacing="4" ID="addReagentTypeFeatures"><?php
													$pCount = 0;
						
													echo "<TR>";
						
													foreach ($featureSet as $key => $value)
													{
														if ($pCount%4 == 0)
															echo "</TR><TR>";

														if ((strcasecmp($value, "tag position") != 0) && (strcasecmp($value, "expression system") != 0))
														{
															?><TD style="white-space:nowrap; padding-right:10px;">
																<INPUT TYPE="checkbox" NAME="createReagentType_dna_sequence_features[]" VALUE="<?php echo $_SESSION["ReagentProp_Name_Alias"][$value];?>" checked><?php echo $_SESSION["ReagentProp_Name_Desc"][$value]; ?></TD>

<INPUT TYPE="hidden" ID="<?php echo "dna_sequence_features_:_" . $_SESSION["ReagentProp_Name_Alias"][$value]; ?>_checkbox_desc_hidden" NAME="propCheckboxes[]" VALUE="<?php echo $_SESSION["ReagentProp_Name_Desc"][$value]; ?>">
<?php
														}
						
														$pCount++;
													}

													// Oct. 29/09: show custom sequence features
													$otherFeatures = $rfunc_obj->getPropertiesByCategory($_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]);

													$pCount = 0;

													foreach ($otherFeatures as $key => $prop)	// $prop is an Object
													{
														$propName = "";
														$propDescr = "";

														try
														{
															$propName = $prop->getPropertyName();
															$propDescr = $prop->getPropertyDescription();
														}
														catch (Exception $e)
														{
															// this is a feature
															$propName = $prop->getFeatureType();
															$propDescr = $prop->getFeatureDescription();
															$featureValue = $prop->getFeatureValue();
														}

														if (!in_array($propName, $featureSet) && (strcasecmp($propName, "tag position") != 0) && (strcasecmp($propName, "expression system") != 0))
														{
															if ($pCount%4 == 0)
																echo "</TR><TR>";

															?><TD style="white-space:nowrap;"><INPUT TYPE="checkbox" NAME="createReagentType_dna_sequence_features[]" VALUE="<?php echo $_SESSION["ReagentProp_Name_Alias"][$propName];?>" checked>

<INPUT TYPE="hidden" ID="<?php echo "dna_sequence_features_:_" . $_SESSION["ReagentProp_Name_Alias"][$propName]; ?>_checkbox_desc_hidden" NAME="propCheckboxes[]" VALUE="<?php echo $_SESSION["ReagentProp_Name_Desc"][$propName]; ?>">

<?php echo $propDescr; ?>
</TD>
<?php

															$pCount++;
														}
													}
						
													echo "</TR>";
												?>
												</TABLE>
											</TD>
										</TR>
						
										<tr ID="dna_sequence_add_new_features_row" style="display:none;">
											<td colspan="4" style="padding-left:25px; padding-top:10px; white-space:nowrap;">
												<b>Add new DNA sequence feature:<b>&nbsp;&nbsp;
												<INPUT type="text" size="35" ID="dna_sequence_features_other" value="" name="featuresOther" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
			
												<input onclick="updateCheckboxListFromInput('dna_sequence_features_other', 'dna_sequence_features', 'addReagentTypeFeatures', 'add_reagent_type_form'); document.getElementById('dna_sequence_features_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
											</td>
										</tr>
	
										<!-- June 2/09: Different sequence types - Protein Sequence -->
										<TR ID="protein_sequence_heading_row" style="display:none;">
											<TD colspan="4" style="padding-left:20px; padding-top:10px; white-space:nowrap">
												<BR><IMG SRC="pictures/star_bullet.gif" WIDTH="10" HEIGHT="10" BORDER="0" ALT="bullet" style="padding-right:8px; vertical-align:middle; padding-bottom:2px;"><b>Protein Sequence</b>
						
												<input type="hidden" ID="proteinSequenceCategoryInput" name="category[]" value="protein_sequence_properties">
			
												<input type="hidden" ID="proteinSequenceCategoryDescriptorInput" name="category_descriptor_protein_sequence_properties" value="Protein Sequence">
						
												<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('createReagentType_protein_sequence_properties');">Check All</span>
			
												<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="uncheckAll" onClick="uncheckAll('createReagentType_protein_sequence_properties',  Array('<?php echo $_SESSION["ReagentProp_Name_Alias"]["protein sequence"]; ?>'));">Uncheck All</span>
											</TD>
										</TR>
				
										<tr ID="protein_sequence_props_row" style="display:none;">
											<TD colspan="4" style="padding-left:25px;">
												<P><TABLE cellspacing="4" ID="addReagentTypeProteinSequenceProperties"><?php
													$pCount = 0;
						
													echo "<TR>";
						
													$protSeqPropSet =  $rfunc_obj->getPropertiesByCategory($_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]);
													
													foreach ($protSeqPropSet as $key => $prop)
													{
														if ($pCount%4 == 0)
															echo "</TR><TR>";

														$value = $prop->getPropertyName();

			
														$seqPropDescr = $_SESSION["ReagentProp_Name_Desc"][$value];

														echo "<TD style=\"white-space:nowrap; padding-right:10px;\">";

														if ((strcasecmp($value, "protein sequence") == 0))
														{
															echo "<INPUT TYPE=\"checkbox\" NAME=\"createReagentType_protein_sequence_properties[]\" VALUE=\"" . $_SESSION["ReagentProp_Name_Alias"][$value] . "\" onClick=\"this.checked = true;\" CHECKED DISABLED>";
														}
														else
														{
															echo "<INPUT TYPE=\"checkbox\" NAME=\"createReagentType_protein_sequence_properties[]\" CHECKED VALUE=\"" . $_SESSION["ReagentProp_Name_Alias"][$value] . "\">";
														}

														?><INPUT TYPE="hidden" ID="<?php echo "protein_sequence_properties_:_" . $_SESSION["ReagentProp_Name_Alias"][$value]; ?>_checkbox_desc_hidden" NAME="propCheckboxes[]" VALUE="<?php echo $seqPropDescr; ?>"><?php
												
														echo $seqPropDescr;
														echo "</TD>";
														$pCount++;
													}
						
													echo "</TR>";
												?></TABLE>
											</TD>
										</TR>
						
										<tr ID="protein_sequence_add_new_props_row" style="display:none;">
											<td colspan="4" style="padding-left:25px; padding-top:10px; white-space:nowrap;">
												<b>Add new protein sequence property:<b>&nbsp;&nbsp;
												<INPUT type="text" size="35" ID="protein_sequence_properties_other" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
			
												<input onclick="updateCheckboxListFromInput('protein_sequence_properties_other', 'protein_sequence_properties', 'addReagentTypeProteinSequenceProperties', 'add_reagent_type_form'); document.getElementById('protein_sequence_properties_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
											</td>
										</tr>
			
										<TR ID="protein_sequence_features_heading_row" style="display:none;">
											<TD colspan="4" style="padding-left:20px; padding-top:10px;">
												<BR><IMG SRC="pictures/star_bullet.gif" WIDTH="10" HEIGHT="10" BORDER="0" ALT="bullet" style="padding-right:8px; vertical-align:middle; padding-bottom:2px;"><b>Protein Sequence Features</b>
						
												<input type="hidden" name="category[]" ID="proteinFeaturesCategoryInput" value="protein_sequence_features">
			
												<input type="hidden" ID="proteinFeaturesCategoryDescriptorInput" name="category_descriptor_protein_sequence_features" value="Protein Sequence Features">
						
												<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('createReagentType_protein_sequence_features');">Check All</span>
												<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="uncheckAll" onClick="uncheckAll('createReagentType_protein_sequence_features', []);">Uncheck All</span>
						
												<BR><span style="font-size:9pt; padding-left:18px; font-weight:normal;">Properties that are linked to a sequence at specific positions and do not exist independently of it</span>
											</TD>
										</TR>
				
										<TR ID="protein_sequence_features_row" style="display:none;">
											<TD colspan="4" style="padding-left:25px;">
												<P><TABLE cellspacing="4" ID="addReagentTypeProteinFeatures"><?php
													$pCount = 0;
						
													echo "<TR>";
						
													foreach ($protFeatureSet as $key => $value)
													{
														if ($pCount%4 == 0)
															echo "</TR><TR>";

														// Nov. 2/09: don't print tag position and expression system; they are dependent on tag type and promoter
														if ((strcasecmp($value, "tag position") != 0) && (strcasecmp($value, "expression system") != 0))
														{
															?><TD style="white-space:nowrap; padding-right:10px;"><INPUT TYPE="checkbox" NAME="createReagentType_protein_sequence_features[]" VALUE="<?php echo $_SESSION["ReagentProp_Name_Alias"][$value];?>" checked><?php echo $_SESSION["ReagentProp_Name_Desc"][$value]; ?>

<INPUT TYPE="hidden" ID="<?php echo "protein_sequence_features_:_" . $_SESSION["ReagentProp_Name_Alias"][$value]; ?>_checkbox_desc_hidden" NAME="propCheckboxes[]" VALUE="<?php echo $_SESSION["ReagentProp_Name_Desc"][$value];; ?>"></TD><?php
														}
						
														$pCount++;
													}

													// Oct. 29/09: show custom sequence features
													$otherProtFeatures = $rfunc_obj->getPropertiesByCategory($_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence Features"]);

													$pCount = 0;

													foreach ($otherProtFeatures as $key => $prop)	// $prop is an Object
													{
														$propName = "";
														$propDescr = "";

														try
														{
															$propName = $prop->getPropertyName();
															$propDescr = $prop->getPropertyDescription();
														}
														catch (Exception $e)
														{
															// this is a feature
															$propName = $prop->getFeatureType();
															$propDescr = $prop->getFeatureDescription();
															$featureValue = $prop->getFeatureValue();
														}

														if (!in_array($propName, $protFeatureSet) && (strcasecmp($propName, "tag position") != 0) && (strcasecmp($propName, "expression system") != 0))
														{
															if ($pCount%4 == 0)
																echo "</TR><TR>";

															?><TD style="white-space:nowrap;"><INPUT TYPE="checkbox" NAME="createReagentType_protein_sequence_features[]" VALUE="<?php echo $_SESSION["ReagentProp_Name_Alias"][$propName];?>" checked>

<INPUT TYPE="hidden" ID="<?php echo "protein_sequence_features_:_" . $_SESSION["ReagentProp_Name_Alias"][$propName]; ?>_checkbox_desc_hidden" NAME="propCheckboxes[]" VALUE="<?php echo $propDescr; ?>">
<?php echo $propDescr; ?>
</TD><?php
														
	
															$pCount++;
														}
													}

													echo "</TR>";
												?>
												</TABLE>
											</TD>
										</TR>
								
										<tr ID="protein_sequence_add_new_features_row" style="display:none;">
											<td colspan="4" style="padding-left:25px; padding-top:10px; white-space:nowrap;">
												<b>Add new Protein sequence feature:<b>&nbsp;&nbsp;
												<INPUT type="text" size="35" ID="protein_sequence_features_other" value="" name="proteinFeaturesOther" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
			
												<input onclick="updateCheckboxListFromInput('protein_sequence_features_other', 'protein_sequence_features', 'addReagentTypeProteinFeatures', 'add_reagent_type_form'); document.getElementById('protein_sequence_features_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
											</td>
										</tr>
			
			
										<!-- June 2/09: Different sequence types - RNA sequence -->
										<TR ID="rna_sequence_heading_row" style="display:none;">
											<TD colspan="4" style="padding-left:20px; padding-top:10px; white-space:nowrap">
												<BR><IMG SRC="pictures/star_bullet.gif" WIDTH="10" HEIGHT="10" BORDER="0" ALT="bullet" style="padding-right:8px; vertical-align:middle; padding-bottom:2px;"><b>RNA Sequence</b>
						
												<input type="hidden" name="category[]" ID="rnaSequenceCategoryInput" value="rna_sequence_properties">
			
												<input type="hidden" ID="rnaSequenceCategoryDescriptorInput" name="category_descriptor_rna_sequence_properties" value="RNA Sequence">
						
												<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('createReagentType_rna_sequence_properties');">Check All</span>
			
												<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="uncheckAll" onClick="uncheckAll('createReagentType_rna_sequence_properties',  Array('<?php echo $_SESSION["ReagentProp_Name_Alias"]["rna sequence"]; ?>'));">Uncheck All</span>
											</TD>
										</TR>
						
										<tr ID="rna_sequence_props_row" style="display:none;">
											<TD colspan="4" style="padding-left:25px;">
												<P><TABLE cellspacing="4" ID="addReagentTypeRNASequenceProperties"><?php
													$pCount = 0;
						
													echo "<TR>";

													$rnaSeqPropSet = $rfunc_obj->getPropertiesByCategory($_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]);													
						
													foreach ($rnaSeqPropSet as $key => $prop)
													{
														if ($pCount%4 == 0)
															echo "</TR><TR>";

														$value = $prop->getPropertyName();
			
														$seqPropDescr = $_SESSION["ReagentProp_Name_Desc"][$value];

														echo "<TD style=\"white-space:nowrap; padding-right:10px;\">";

														if ((strcasecmp($value, "rna sequence") == 0))
														{
															echo "<INPUT TYPE=\"checkbox\" NAME=\"createReagentType_rna_sequence_properties[]\" VALUE=\"" . $_SESSION["ReagentProp_Name_Alias"][$value] . "\" onClick=\"this.checked = true;\" CHECKED DISABLED>";
														}
														else
														{
															echo "<INPUT TYPE=\"checkbox\" NAME=\"createReagentType_rna_sequence_properties[]\" CHECKED VALUE=\"" . $_SESSION["ReagentProp_Name_Alias"][$value] . "\">";
														}

														?><INPUT TYPE="hidden" ID="<?php echo "rna_sequence_properties_:_" . $_SESSION["ReagentProp_Name_Alias"][$value]; ?>_checkbox_desc_hidden" NAME="propCheckboxes[]" VALUE="<?php echo $seqPropDescr; ?>"><?php
														echo $seqPropDescr; 
														echo "</TD>";
														$pCount++;
													}
						
													echo "</TR>";
												?>
												</TABLE>
											</TD>
										</TR>

										<tr ID="rna_sequence_add_new_props_row" style="display:none;">
											<td colspan="4" style="padding-left:25px; padding-top:10px; white-space:nowrap;">
												<b>Add new RNA sequence property:<b>&nbsp;&nbsp;
												<INPUT type="text" size="35" ID="rna_sequence_properties_other" value="" name="rnaSeqPropOther" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
			
												<input onclick="updateCheckboxListFromInput('rna_sequence_properties_other', 'rna_sequence_properties', 'addReagentTypeRNASequenceProperties', 'add_reagent_type_form'); document.getElementById('rna_sequence_properties_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
											</td>
										</tr>
		
										<TR ID="rna_sequence_features_heading_row" style="display:none;">
											<TD colspan="4" style="padding-left:20px; padding-top:10px;">
												<BR><IMG SRC="pictures/star_bullet.gif" WIDTH="10" HEIGHT="10" BORDER="0" ALT="bullet" style="padding-right:8px; vertical-align:middle; padding-bottom:2px;"><b>RNA Sequence Features</b>
						
												<input type="hidden" name="category[]" ID="rnaFeaturesCategoryInput" value="rna_sequence_features">
			
												<input type="hidden" ID="rnaFeaturesCategoryDescriptorInput" name="category_descriptor_rna_sequence_features" value="RNA Sequence Features">
						
												<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="checkAll" onClick="checkAll('createReagentType_rna_sequence_features');">Check All</span>
												<span class="linkShow" style="margin-left:10px;font-size:8pt; font-weight:normal;" ID="uncheckAll" onClick="uncheckAll('createReagentType_rna_sequence_features', []);">Uncheck All</span>
						
												<BR><span style="font-size:9pt; padding-left:18px; font-weight:normal;">Properties that are linked to a sequence at specific positions and do not exist independently of it</span>
											</TD>
										</TR>
								
										<TR ID="rna_sequence_features_row" style="display:none;">
											<TD colspan="4" style="padding-left:25px;">
												<P><TABLE cellspacing="4" ID="addReagentTypeRNAFeatures"><?php
													$pCount = 0;
						
													echo "<TR>";
						
													$rnaFeatureSet = $rfunc_obj->getPropertiesByCategory($_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence Features"]);

													foreach ($rnaFeatureSet as $key => $prop)
													{
														if ($pCount%4 == 0)
															echo "</TR><TR>";

														$value = $prop->getPropertyName();
		
														if ((strcasecmp($value, "tag position") != 0) && (strcasecmp($value, "expression system") != 0))
														{
															?><TD style="white-space:nowrap; padding-right:10px;"><INPUT TYPE="checkbox" NAME="createReagentType_rna_sequence_features[]" VALUE="<?php echo $_SESSION["ReagentProp_Name_Alias"][$value];?>" checked><?php echo $_SESSION["ReagentProp_Name_Desc"][$value]; ?>


<INPUT TYPE="hidden" ID="<?php echo "rna_sequence_features_:_" . $_SESSION["ReagentProp_Name_Alias"][$value]; ?>_checkbox_desc_hidden" NAME="propCheckboxes[]" VALUE="<?php echo $_SESSION["ReagentProp_Name_Desc"][$value]; ?>"></TD><?php
														}
						
														$pCount++;
													}

// Oct. 29/09: show custom sequence features
													$otherRNAFeatures = $rfunc_obj->getPropertiesByCategory($_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence Features"]);

													foreach ($otherRNAFeatures as $key => $prop)	// $prop is an Object
													{
														$propName = "";
														$propDescr = "";

														try
														{
															$propName = $prop->getPropertyName();
															$propDescr = $prop->getPropertyDescription();
														}
														catch (Exception $e)
														{
															// this is a feature
															$propName = $prop->getFeatureType();
															$propDescr = $prop->getFeatureDescription();
															$featureValue = $prop->getFeatureValue();
														}

														if (!in_array($propName, $rnaFeatureSet) && (strcasecmp($propName, "tag position") != 0) && (strcasecmp($propName, "expression system") != 0))
														{
															if ($pCount%4 == 0)
																echo "</TR><TR>";
															?><TD style="white-space:nowrap;"><INPUT TYPE="checkbox" NAME="createReagentType_rna_sequence_features[]" VALUE="<?php echo $_SESSION["ReagentProp_Name_Alias"][$propName];?>" checked>

<INPUT TYPE="hidden" ID="<?php echo "rna_sequence_features_:_" . $_SESSION["ReagentProp_Name_Alias"][$propName]; ?>_checkbox_desc_hidden" NAME="propCheckboxes[]" VALUE="<?php echo $propDescr; ?>">
<?php echo $propDescr; ?></TD>
<?php
														}

														$pCount++;
													}
						
													echo "</TR>";
												?>
												</TABLE>
											</TD>
										</TR>
		
										<tr ID="rna_sequence_add_new_features_row" style="display:none;">
											<td colspan="4" style="padding-left:25px; padding-top:10px; white-space:nowrap;">
												<b>Add new RNA sequence feature:<b>&nbsp;&nbsp;
												<INPUT type="text" size="35" ID="rna_sequence_features_other" value="" name="rnaFeaturesOther" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
			
												<input onclick="updateCheckboxListFromInput('rna_sequence_features_other', 'rna_sequence_features', 'addReagentTypeRNAFeatures', 'add_reagent_type_form'); document.getElementById('rna_sequence_features_other').focus();" value="Add" type="button" style="font-size:10pt;"></INPUT>
											</td>
										</tr>
									</TABLE>
								</TD>
							</TR>

							<TR ID="addCategoryRow">
								<TD style="padding-top:10px; white-space:nowrap; padding-left:18px; font-weight:bold;">
									<P><HR><BR>If the property you wish to add does not fit any of the above categories, please add your own category:<BR>
									<P><INPUT type="text" size="35" ID="new_category" value="" name="newCategory" onKeyPress="return disableEnterKey(event);">&nbsp;&nbsp;
		
									<input onclick="addPropertiesCategory('new_category', 'addReagentPropsTbl', 'addCategoryRow', 'add_reagent_type_form');" value="Add" type="button" style="font-size:10pt;"></INPUT>
		
									<BR><BR><HR>
								</TD>
							</TR>
		
							<TR ID="rTypeSubmitRow">
								<td style="padding-left:16px; font-size:9pt;">
									<BR><INPUT TYPE="submit" name="create_rtype" value="Continue" onclick = "collectReagentTypeProperties('add_reagent_type_form'); addReagentTypeNameToParentsList(); return verifyNewReagentTypeName() && verifyNewReagentTypePrefix() && verifyUniqueReagentTypeName() && verifyReagentTypePrefix(); document.getElementById('navigate_away').value=0;" style="font-size:10pt;">
									<INPUT TYPE="hidden" name="step" value="1">
								</td>
							</TR>
						</TABLE>	<!-- close addReagentPropsTbl -->
					</TD>
				</TR>

				<TR><TD style="padding-left:36px;">&nbsp;</td></tr>
			</TABLE>
		</FORM>
		<?php

		foreach ($_SESSION["ReagentType_ID_Name"] as $key => $value)
		{
			echo "<INPUT TYPE=\"hidden\" NAME=\"reagent_type_names[]\" value=\"" . $value . "\">";
		}

		foreach ($_SESSION["ReagentType_ID_Prefix"] as $key => $value)
		{
			echo "<INPUT TYPE=\"hidden\" NAME=\"reagent_type_prefixes[]\" value=\"" . $value . "\">";
		}
	}

	
	/**
	 * This function prints forms to add new reagents for all reagent types in OpenFreezer.  The forms are hidden initially, displayed if that reagent type is selected from the dropdown menu
	 *
	 * @author Marina Olhovsky
	 * @version 2007-03-27
	 *
	 * @param STRING
	 * @param STRING
	 * @param Array
	*/
	function printCreationForm($rType="", $subtype="", $parents=array())
	{
		global $cgi_path;
		
		$r_out = new Reagent_Output_Class();
		$gfunc_obj = new generalFunc_Class();
		$rfunc_obj = new Reagent_Function_Class();

		// May 5/08: Use a different prefix - make uniform for all reagent types and for dynamic features
		$genPrefix = "reagent_detailedview_";
		$genPostfix = "_prop";
		
		// Property names
		$v_name = $rfunc_obj->get_Post_Names("Vector", "");

		// August 21/07, Marina: Pass current user info to CGI script
		$currUserName = $_SESSION["userinfo"]->getDescription();

		?>
		<!-- Vector - 3 subtypes -->
		<FORM method="POST" NAME="createReagentForm" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $rType; ?>', '<?php echo $subtype; ?>');">

			<!-- Pass user info to Python as hidden form value - Aug 21/07 -->
			<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
			<INPUT type="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">

			<!-- MAY 2/08: IF RETURNING TO THIS VIEW FROM A LATER PAGE TO CHANGE PARENTS - PASS THE REAGENT ID TO PYTHON!!!!!! -->
			<?php
				if (isset($_GET["rID"]))
				{
					?>
						<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $_GET["rID"]; ?>">
					<?php
				}

				// May 26/08: Only show reagent type selection box initially; if type/subtype have already been selected disallow changing it in the middle of creation
				if ($rType == "")
				{
					$this->printReagentTypeSelection();
				}
				else
				{
					// However, Python expects reagent type and subtype as form input. If not showing lists, pass hidden values
					?>
						<INPUT TYPE="hidden" NAME="reagentType" VALUE="<?php echo $rType; ?>">
						<?php

						if (strcasecmp($rType, "Vector") == 0)
							echo "<INPUT TYPE=\"hidden\" NAME=\"vectorSubtype\" VALUE=\"" . $subtype . "\">";
						
						else if (strcasecmp($rType, "CellLine") == 0)
							echo "<INPUT TYPE=\"hidden\" NAME=\"cellLineSubtype\" VALUE=\"" . $subtype . "\">";
				}
			?>
			<!-- PARENTS -->
			<table id="nonrec_parents" cellpadding="2" cellspacing="2" style="text-align:center; display:<?php echo (($subtype == "nonrecomb") || ($subtype == "gateway_entry")) ? "inline" : "none"; ?>;">
				<TR><TD>&nbsp;</TD></TR>

				<tr>
					<td colspan="4">
						<span id="nonrecomb_hdr" style="font-weight:bold; color:#00C00D; font-size:13pt; white-space:nowrap; padding-bottom:18px; display:<?php echo ($subtype == "nonrecomb") ? "inline" : "none"; ?>">
							CREATE A NON-RECOMBINATION VECTOR<BR>
						</span>

						<span id="gw_entry_hdr" style="color:#00C00D; font-size:13pt; font-weight:bold; white-space:nowrap; display:<?php echo ($subtype == "gateway_entry") ? "inline" : "none"; ?>">
							CREATE A GATEWAY ENTRY VECTOR<BR>
						</span>

						<span style="color:#00C00D; font-weight:bold; font-size:10pt; text-align:center; white-space:nowrap;">
							Step 1 of 4: Define Parents
						</span>
						<BR><BR>

						<span id="nonrec_title" style="padding-left:15px; font-weight:bold; white-space:nowrap; display:<?php echo ($subtype == "nonrecomb") ? "inline" : "none"; ?>">Please enter  OpenFreezer IDs of the <u>Parent Vector</u> and <u>Insert</u> used to generate this Vector:<BR></span>

						<span id="gw_entry_title" style="padding-left:15px; font-weight:bold; white-space:nowrap; display:<?php echo ($subtype == "gateway_entry") ? "inline" : "none"; ?>">Please enter OpenFreezer IDs of the <u>Gateway Parent Donor Vector</u> and <u>Insert</u> in the fields below:<BR></span>

						<BR><TABLE width="700px" style="text-align:left;">
							<TR>
								<TD width="125px" style="white-space:nowrap; padding-left:20px;">
									<SPAN id="nonrec_pv" style="display:<?php echo ($subtype == "nonrecomb") ? "inline" : "none"; ?>">Parent Vector OpenFreezer ID:<BR></SPAN>

									<span id="gw_pv" style="display:<?php echo ($subtype == "gateway_entry") ? "inline" : "none"; ?>">Gateway Parent Donor Vector OpenFreezer ID:<BR></span>
								</TD>

								<TD>
									<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="nr_pv_id" name="nr_parent_vector" size="8" value="<?php echo $parents["PV"]; ?>">
								</TD>
							</TR>
			
							<TR>
								<TD width="125px" style="white-space:nowrap; padding-left:20px;">
									Insert OpenFreezer ID:
								</TD>
								
								<TD style="white-space:nowrap">
									<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="insert" name="insert_id" size="8" value="<?php echo $parents["I"]; ?>">

									<!-- Jan. 21/09 -->
									<?php
									if ($subtype != 'gateway_entry')
									{
										?>&nbsp;&nbsp;<INPUT TYPE="checkbox" ID="reverseComplementCheckbox" onClick="document.getElementById('customSitesCheckbox').checked=true; showHideCustomSites()" NAME="reverse_complement" style="font-size:10pt"><span id="rc_text">Reverse Complement&nbsp;&nbsp;&nbsp;&nbsp;</span><?php
									}
								?></TD>
							</TR>
						</TABLE>
					</td>
				</tr>

				<TR>
					<TD colspan="4" style="text-align:left; padding-left:20px; padding-top:10px;">
					<?php
						if ($subtype != 'gateway_entry')
						{
							?><div id="rc_caption">
								<HR>
								By default, the new Vector's cloning sites are identical to those of the specified parent Insert.  You may change the default values using the 'Customize Sites' function below.

								<A href="pictures/nonrecomb_vector.pdf" onClick="return popup(this, 'diagram', '665', '635', 'yes')">Click here to view an illustration</A><BR><BR>

								<span style="color:#FF0000;">If 'Reverse Complement' is selected, you must also reverse the order of the Insert's cloning sites for non-directional cloning.</span>&nbsp;&nbsp;E.g. if the 5' and 3' cloning sites of the Insert are BamHI and BglII respectively, indicate "Insert 5' site" => "BglII" and "Insert 3' site" => "BamHI" below.<BR><HR><BR>
							</div>

							<!-- Nov. 17/08: Add option to make custom sites -->
							<INPUT TYPE="checkbox" ID="customSitesCheckbox" NAME="custom_sites" style="font-size:10pt" onClick="showHideCustomSites()"> <span id="customize_sites_text">Customize cloning sites<BR>
							<span style="margin-left:20px; font-size:9pt;">(Click here if Insert and Parent Vector sites are not identical)</span>
							<BR><BR></span><?php
						}

						?><TABLE cellpadding="4px" border="1" frame="none" rules="all" ID="customCloningSites" style="display:none">
							<TH colspan="2">
								<b>Please specify cloning sites on the Parent Vector and insert:</b><BR>
							</TH>

							<tr>
								<td colspan="2" style="font-size:9pt; font-weight:bold">
									Parent Vector Cloning Sites:
								</td>
							</tr>
					
							<TR>
								<TD style="padding-left:10px">
									5' Cloning Site:
								</TD>
					
								<TD>
									<?php
										echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list_1\" name=\"pv_custom_five_prime\"></select>";
									?>
								</TD>
							</TR>
					
							<TR>
								<TD style="padding-left:10px">
									3' Cloning Site:
								</TD>
					
								<TD>
									<?php
										echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list_1\" name=\"pv_custom_three_prime\"></select>";
									?>
								</TD>
							</TR>


							<tr>
								<td colspan="2" style="font-size:9pt; font-weight:bold;">
									Insert Cloning Sites:
								</td>
							</tr>
					
							<TR>
								<TD style="padding-left:10px">
									5' Cloning Site:
								</TD>
					
								<TD>
									<?php
										echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list\" name=\"insert_custom_five_prime\"></select>";
									?>
									<BR><div id="fp_warning" style="display:none; color:#FF0000">Please select a value for the 5' restriction site from the dropdown list</div>
								</TD>
							</TR>
					
							<TR>
								<TD style="padding-left:10px">
									3' Cloning Site:
								</TD>
					
								<TD>
									<?php
										echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list\" name=\"insert_custom_three_prime\"></select>";
									?>
									<BR><div id="tp_warning" style="display:none; color:#FF0000">Please select a value for the 3' restriction site from the dropdown list</div>
								</TD>
							</TR>

						</table>

						<BR><INPUT TYPE="submit" name="preload" value="Continue" onClick="if (document.getElementById('cancel_set')) {document.getElementById('cancel_set').value=0}; return verifyCustomSites(); document.getElementById('navigate_away').value=0;">
						<?php
							// Nov. 5/08: Add 'Cancel' btn
							if (isset($_GET["rID"]))
							{
								?>
									&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">

									<INPUT type="hidden" ID="cancel_set" value="0">
								<?php
							}
						?>
					</TD>
				</TR>
			</table>

			<TABLE id="recomb_parents" width="700px" style="display:<?php echo (($subtype == "recomb") || ($subtype == "gateway_expression")) ? "inline" : "none"; ?>" cellpadding="2px">
				<tr><td>&nbsp;</td></tr>

				<tr>
					<td colspan="2">
						<center>
						<span id="recomb_hdr" style="font-weight:bold; color:#00C00D; font-size:13pt; white-space:nowrap; padding-bottom:18px; text-align:center; display:<?php echo ($subtype == "recomb") ? "inline" : "none"; ?>">
							CREATE AN EXPRESSION VECTOR<BR>
						</span>

						<span id="gw_expr_hdr" style="color:#00C00D; font-size:13pt; font-weight:bold; white-space:nowrap; padding-bottom:18px; text-align:center; display:<?php echo ($subtype == "gateway_expression") ? "inline" : "none"; ?>">
							CREATE A GATEWAY EXPRESSION VECTOR<BR>
						</span>
						
						<span style="color:#00C00D; font-weight:bold; font-size:10pt; text-align:center; white-space:nowrap; padding-bottom:18px;">
							Step 1 of 4: Define Parents
						</span>
						<BR><BR>

						<span id="creator_title" style="padding-left:15px; font-weight:bold; display:<?php echo ($subtype == "recomb") ? "inline" : "none"; ?>">Please enter  OpenFreezer IDs of the <u>Acceptor</u> and <u>Donor</u> Vectors used to generate this Vector:<BR></span>
						</center>

						<span id="gw_expression_title" style="padding-left:15px; font-weight:bold; display:<?php echo ($subtype == "gateway_expression") ? "inline" : "none"; ?>">Please enter OpenFreezer IDs of the <u>Gateway Parent Destination Vector</u> and <u>Gateway Entry Clone</u> in the fields below:<BR></span>
						
						<BR><table width="400px" cellpadding="2" cellspacing="2" style="border:0; margin-left:10px;">
							<TR>
								<TD width="85px" style="white-space:nowrap; padding-left:10px;">
									<span id="creator_acceptor_pv" style="display:<?php echo ($subtype == "recomb") ? "inline" : "none"; ?>">Creator Acceptor Vector OpenFreezer ID:</span>

									<span id="destination_pv" style="display:<?php echo ($subtype == "gateway_expression") ? "inline" : "none"; ?>">Gateway Parent Destination Vector OpenFreezer ID:</span>
								</TD>
			
								<TD colspan="2">
									<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="rec_pv_id" name="rec_parent_vector" size="8" value="<?php echo $parents["PV"]; ?>">
								</TD>
							</TR>
			
							<TR>
								<TD width="125px" style="padding-left:10px; white-space:nowrap">
									<span id="creator_donor_ipv" style="display:<?php echo ($subtype == "recomb") ? "inline" : "none"; ?>">Creator Donor Vector OpenFreezer ID:</span>

									<span id="gateway_donor_ipv" style="display:<?php echo ($subtype == "gateway_expression") ? "inline" : "none"; ?>">Gateway Entry Clone OpenFreezer ID:</span>
								</TD>

								<TD>
									<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="insert_parent_vector_id" name="insert_parent_vector" size="8" value="<?php echo $parents["IPV"]; ?>">
								</TD>
							</TR>
						</TABLE>
					</td>
				</tr>

				<TR>
					<TD colspan="2" style="padding-top:10px; padding-left:20px;">
						<INPUT TYPE="submit" name="preload" value="Next">
					</TD>
				</TR>
			</TABLE>

			<!-- Novel Vectors -->
			<TABLE id="vector_general_props" style="display:none">
				<TR>
					<TD colspan="3">
						<table width="100%" cellpadding="4">
							<tr>
								<td style="text-align:center">
									<span style="color:#00C00D; font-size:13pt; font-weight:bold">
										CREATE A NEW PARENT (NOVEL) VECTOR
									</span>

									<!-- For Novel vectors need to redirect to features page from here, so to invoke a different CGI code segment must pass other arguments -->
									<input type="hidden" name="reagent_type_hidden" value="Vector">
									<input type="hidden" name="subtype_hidden" value="novel">
								</td>
							</tr>
						</table>
				
						<table border="1" width="100%" cellpadding="4" frame="box" rules="none">
							<tr>
								<td style="font-size:9pt; padding-left:10px;">
									<b>Step 1 of 3:</b><BR><P><span style="padding-left:5px;">Please paste your Vector sequence in the box below<!--, <b>including <u>intact cloning sites</u> and any <u>linking sequences</u> between the cloning sites and the start of the cDNA sequence:</b>-->:</span>
								</td>
							</tr>
						
							<tr>
								<td>
									<?php
										$r_out->print_property_final($genPrefix . $v_name["sequence"] . $genPostfix, "sequence", "", "Preview", true, "DNA Sequence", "");
									?>
									<BR><div id="vector_sequence_warning" style="display:none; color:#FF0000">Please paste a sequence in the textbox above.</div>
								</td>
							</tr>
						</table>
						<BR>
						
						<table style="padding-top:10px;">
							<TR>
								<td style="padding-left:0px;">
									<input type="submit" name="confirm_features" value="Continue" onClick="if (document.getElementById('cancel_set')) {document.getElementById('cancel_set').value=0}; return verifyNovelVectorSequence(); document.getElementById('navigate_away').value=0;">
								</td>
							</tr>
							
						</table>
					</TD>
				</TR>
			</TABLE>
 		</FORM>		<!-- moved here May 12/08 -->

		<!-- April 19, 2010: Upload Excel sheets - will be finalized a little later, don't delete this section -->
		<!--<TABLE>
			<TR>
				<TD style="padding-left:10px; padding-top:10px;">
					Or, use the 'Batch Upload' feature to load more than 10 reagents:&nbsp;
					<a href="batch_templates/downloads/vector_reagents_v3.xls" class="linkShow">Download Template</a>

					<BR>
					<P><B>Please note: Batch input files must contain a minimum of 10 reagents.  For fewer than 10 entries, please use the standard reagent input mechanism.</B>
				</TD>
			</TR>

			<TR>
				<TD style="padding-left:10px; padding-top:10px;">
					<form enctype="multipart/form-data" action="Reagent/upload.php" method="POST">
						<input type="hidden" name="MAX_FILE_SIZE" value="100000" />
						Choose a file to upload: <input name="uploadedfile" type="file" /><br />
						<input type="submit" value="Upload File" />
					</form>
				</TD>
			</TR>
		<TABLE>-->
		<?php

		// Insert
		$iShow = ($rType == "Insert") ? true : false;

		if (isset($_GET["rID"]))
		{
			$rID = $_GET["rID"];
		}
		else
		{
			$rID = "";
		}

		$this->printForm_Insert_Insert($rID, $iShow);
		
		// Other
		foreach ($_SESSION["ReagentType_Name_ID"] as $r_type => $rTypeID)
		{
			switch ($r_type)
			{
				case 'Vector':
				break;

				case 'Insert':
				break;

				case 'CellLine':	// update Jan. 19, 2010
					?>
					<!-- CELL LINE -->
					<FORM method="POST" NAME="createReagentForm" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $rType; ?>', '<?php echo $subtype; ?>');">
						<!-- Pass user info to Python as hidden form value - Aug 21/07 -->
						<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
						<INPUT type="hidden" NAME="cellLineSubtype" VALUE="stable_cell_line">
						<INPUT TYPE="hidden" NAME="reagentType" VALUE="CellLine">
				
						<TABLE id="stable_cell_line_parents" style="display:none" cellpadding="2px">
						
							<TR>
								<TD nowrap>
									Parent Vector OpenFreezer ID:
								</TD>
			
								<TD>
									<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="cl_pv_id" name="cell_line_parent_vector" size="8"/>
								</TD>
			
							</TR>
							
							<TR>
								<TD nowrap>
									Parent Cell Line OpenFreezer ID:
								</TD>
			
								<TD>
									<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="parent_cell_line_id" name="parent_cell_line" size="8"/>
								</TD>
							</TR>
			
							<!-- July 17/08	-->
							<TR>
								<TD colspan="2" style="padding-top:8px; padding-left:10px;">
									<INPUT TYPE="submit" name="preload" value="Next">
								</TD>
							</TR>
						</TABLE>
					</FORM>
					<?php

					// General Cell Line properties form
					$this->printCellLineGeneralPropsForm();
				break;

				default:
					if ($rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$r_type], "protein sequence", $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]))
						$seq_type = "protein";
					else if ($rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$r_type], "rna sequence", $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]))
						$seq_type = "rna";
					else
						$seq_type = "dna";

					?><FORM method="POST" ENCTYPE="multipart/form-data" NAME="createReagentForm<?php echo $r_type; ?>" action="<?php echo $cgi_path . "create.py"; ?>">
		
					<!-- Pass user info to Python as hidden form value - Aug 21/07 -->
					<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

					<INPUT type="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">

					<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $r_type; ?>">
					<?php
						
						// moved up here on April 11, 2011
						$ignoreList = Array();

						$isProtein = $rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$r_type], "protein sequence", $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]);

						$isRNA = $rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$r_type], "rna sequence", $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]);

						if ($isProtein)
						{
							$featureType = $_SESSION["ReagentPropCategory_Name_Alias"]["Protein Sequence Features"];

							// For Protein, Tm is entered manually, MW computed automatically
							$ignoreList[] = "molecular weight";
						}
						else if ($isRNA)
						{
							$featureType = $_SESSION["ReagentPropCategory_Name_Alias"]["RNA Sequence Features"];
						}
						else
						{
							$featureType = $_SESSION["ReagentPropCategory_Name_Alias"]["DNA Sequence Features"];

							// For DNA, Tm, MW, GC% and translation are computed automatically
							$ignoreList[] = "protein translation";
							$ignoreList[] = "melting temperature";
							$ignoreList[] = "molecular weight";
							$ignoreList[] = "gc content";	// june 25, 2010
						}
					?>
					<INPUT TYPE="hidden" NAME="feature_type_<?php echo $r_type; ?>" VALUE="<?php echo $featureType; ?>">

					<TABLE width="700px" ID="createReagentTbl_<?php echo $r_type; ?>" NAME="reagentTypeCreateOther" style="display:none; margin-left:15px; font-size:10pt; width:705px;" cellpadding="4" cellspacing="4">
						<TR>
							<TD>
								<!-- making title a standalone table so it is not resized when sections are collapsed -->
								<table style="text-align:center; white-space:nowrap;" width="705px"><tr><td style="font-size:12pt; color:#00C00D; font-weight:bold;"><?php echo "ADD NEW " . $r_type; ?></td></tr></table>
							</TD>
						</TR>
						<?php

						// find the attributes of this reagent type
						$categories = $rfunc_obj->findAllReagentTypeAttributeCategories($rTypeID);

						$seqFeaturesOther = Array();

						foreach ($categories as $categoryID => $category)
						{
							if (($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence Features"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence Features"]))
							{
								$rTypeAttributes = $rfunc_obj->getReagentTypeAttributesByCategory($_SESSION["ReagentType_Name_ID"][$r_type], $categoryID);
								
								if (count($rTypeAttributes) == 0)
									continue;

								$catAlias = $_SESSION["ReagentPropCategory_ID_Alias"][$categoryID];

								echo "<tr><td><HR></td></tr>";
	
								echo "<TR>";
									echo "<TD>";
										echo "<table style=\"background-color:#FFFFFF;\">";

											// Aug. 19/09: Changed categoryID to catAlias, since that is also the name of the table in addNewReagentType() and JS was confusing between the two
											echo "<tr>";
												echo "<td style=\"font-weight:bold; color:blue; padding-top:2px;\">";
													echo "<IMG id=\"" . $catAlias . "_expand_img_" . $r_type . "\" SRC=\"pictures/arrow_collapse.gif\" WIDTH=\"20\" HEIGHT=\"15\" BORDER=\"0\" ALT=\"plus\" class=\"menu-expanded\" style=\"display: inline\" onClick=\"showHideCategory('" . $catAlias . "', '" . $r_type . "');\">";
	
													echo "<IMG id=\"" . $catAlias . "_collapse_img_" . $r_type . "\" SRC=\"pictures/arrow_expand.gif\" WIDTH=\"40\" HEIGHT=\"34\" BORDER=\"0\" ALT=\"plus\" class=\"menu-collapsed\" style=\"display: none\" onClick=\"showHideCategory('" . $catAlias . "', '" . $r_type . "');\">";
	
													echo "<span id=\"section_title_" . $catAlias . "\">" . $category . "</span>";
												echo "</TD>";
											echo "</TR>";
	
											echo "<tr>";
												echo "<td style=\"padding-left:2px;\">";
													echo "<table ID=\"category_" . $catAlias . "_section_" . $r_type . "\" cellpadding=\"4\" cellspacing=\"2\" style=\"background-color:#FFFFFF; margin-top:4px;\">";
//	print_r($ignoreList);
													// May 5, 2010
													foreach ($rTypeAttributes as $attrID => $cProp)
													{
														$propDescr = $cProp->getPropertyDescription();
														$propAlias = $cProp->getPropertyAlias();
														$propName = $cProp->getPropertyName();
														$propCategory = $cProp->getPropertyCategory();

														// May 5, 2010
														$p_id = $_SESSION["ReagentProp_Name_ID"][$propName];
														$c_id = $_SESSION["ReagentPropCategory_Name_ID"][$propCategory];
														$pID = $rfunc_obj->getPropertyIDInCategory($p_id, $c_id);
	
														if (!in_array($propName, $ignoreList))
														{
															echo "<TR style=\"background-color:#F5F5DC;\">";

																echo "<TD style=\"padding-left:15px; padding-right:15px; font-size:8pt; width:100px; font-weight:bold; white-space:nowrap;\">" . $propDescr;

																	// Changes Feb. 8/10
																	$tmp_pc_id = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$propName], $_SESSION["ReagentPropCategory_Name_ID"][$propCategory]);

																	//if ( ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["project id"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || (strcasecmp($propName, "packet id") == 0) || (strcasecmp($propName, "owner") == 0) || ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["status"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]))|| ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["type of insert"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"])) || ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["open/closed"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"])) || (strcasecmp(strtolower($propName), strtolower($r_type) . " type") == 0))

																	if ($rfunc_obj->isMandatory($r_type, strtolower($propName)))
																	{
																		echo "<span style=\"font-size:9pt; color:#FF0000; font-weight:bold; margin-left:5px;\">*</span></TD>";
																		echo "<INPUT TYPE=\"hidden\" NAME=\"" . $r_type . "_mandatoryProps[]\" VALUE=\"" . $attrID . "\">";
																	}

																	// June 18, 2010: add explanation for Alt. ID Other
																	else if ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["alternate id"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]))
																	{
																		echo "<BR><BR><span style=\"font-size:8pt;\">For 'Other', please enter<BR> the database name and<BR> numeric identifier, separated<BR> by semicolon, in the textbox<BR> (e.g. 'IMAGE:123456')</span></TD>";
																	}
																	else
																		echo "</TD>";
																	
																	echo "<TD style=\"font-size:8pt; padding-left:10px; width:600px;\">";

																	$r_out->print_property_final($genPrefix . $r_type . "_" . $catAlias . "_:_" . $propAlias . $genPostfix, $propName, "", "", true, $propCategory, "Preview", "", $r_type, false, "", 0, 0);

																	if ($rfunc_obj->isCustomizeable($attrID))
																	{
																		echo "<BR><INPUT TYPE=\"text\" style=\"display:none;\" ID=\"otherText_" . $propCatID . "\">";
																	
																		echo "<INPUT TYPE=\"button\" style=\"font-size:7pt; display:none;\" VALUE=\"Add\" ID=\"addOtherBtn_" . $propCatID . "\" onClick=\"addElementToListFromInput('otherText_" . $propCatID . "', '" . $genPrefix . $r_type . "_" . $catAlias . "_:_" . $propAlias . $genPostfix . "')\" NAME=\"addBtn[]\">";
																	}

																echo "</TD>";
															echo "</TR>";
														}
													}
	
													echo "</TABLE>";
												echo "</TD>";
											echo "</TR>";
										echo "</TABLE>";
									echo "</TD>";
								echo "</TR>";
							}
							else
							{
								$catAlias = $_SESSION["ReagentPropCategory_ID_Alias"][$categoryID];

								echo "<tr><td><HR></td></tr>";
	
								echo "<TR>";
									echo "<TD>";
										echo "<table style=\"background-color:#FFFFFF;\">";

											// Aug. 19/09: Changed categoryID to catAlias, since that is also the name of the table in addNewReagentType() and JS was confusing between the two
											echo "<tr>";
												echo "<td style=\"font-weight:bold; color:blue; padding-top:2px;\">";
													echo "<IMG id=\"" . $catAlias . "_expand_img_" . $r_type . "\" SRC=\"pictures/arrow_collapse.gif\" WIDTH=\"20\" HEIGHT=\"15\" BORDER=\"0\" ALT=\"plus\" class=\"menu-expanded\" style=\"display: inline\" onClick=\"showHideCategory('" . $catAlias . "', '" . $r_type . "');\">";
	
													echo "<IMG id=\"" . $catAlias . "_collapse_img_" . $r_type . "\" SRC=\"pictures/arrow_expand.gif\" WIDTH=\"40\" HEIGHT=\"34\" BORDER=\"0\" ALT=\"plus\" class=\"menu-collapsed\" style=\"display: none\" onClick=\"showHideCategory('" . $catAlias . "', '" . $r_type . "');\">";
	
													echo "<span id=\"section_title_" . $catAlias . "\">" . $category . "</span>";
												echo "</TD>";
											echo "</TR>";
	
											echo "<tr ID=\"category_" . $catAlias . "_section_" . $r_type . "\" style=\"background-color:#FFFFFF; padding-top:4px;\">";
												echo "<td style=\"padding-left:2px;\">";
													$this->printFeatures($r_type, "", "", "", "createReagentForm" . $r_type, false, true, "category_" . $catAlias . "_features_section_" . $r_type . "_tbl");
												echo "</td>";
											echo "</TR>";
										echo "</TABLE>";
									echo "</TD>";
								echo "</TR>";
							}
						}

						// Aug. 11/09: Parents
						$assoc = $rfunc_obj->findReagentTypeAssociations($rTypeID);

						if (count($assoc) > 0)
						{
							echo "<tr><td><HR></td></tr>";

							echo "<tr>";
								echo "<td style=\"font-weight:bold; color:blue; padding-top:2px;\">";
									echo "<IMG id=\"assoc_expand_img\" SRC=\"pictures/arrow_collapse.gif\" WIDTH=\"20\" HEIGHT=\"15\" BORDER=\"0\" ALT=\"plus\" class=\"menu-expanded\" style=\"display: inline\" onClick=\"showHideCategory('assoc');\">";

									echo "<IMG id=\"assoc_collapse_img\" SRC=\"pictures/arrow_expand.gif\" WIDTH=\"40\" HEIGHT=\"34\" BORDER=\"0\" ALT=\"plus\" class=\"menu-collapsed\" style=\"display: none\" onClick=\"showHideCategory('assoc');\">";

									echo "<span id=\"section_title_associations\">Associations</span>";
								echo "</TD>";
							echo "</TR>";

							echo "<tr>";
								echo "<td style=\"padding-left:8px; padding-top:10px; padding-bottom:10px;\">";
									echo "<table ID=\"category_assoc_section_" . $rTypeID . "\" cellpadding=\"4\" cellspacing=\"2\" style=\"background-color:#FFFFFF; margin-top:4px;\">";
						
									$r_count = 0;

									foreach ($assoc as $assocID => $assocValue)
									{
										// echo "assoc id " . $assocID . "<BR>";
										// echo "assoc value " . $assocValue . "<BR>";

										// Dec. 10/09: show parent prefix
										$parentTypeID = $rfunc_obj->findAssocParentType($assocID);
										$parentPrefix = $_SESSION["ReagentType_ID_Prefix"][$parentTypeID];

										$assocAlias = $_SESSION["ReagentAssoc_ID_Alias"][$assocID];

										// echo "assoc alias " . $assocAlias . "<BR>";

										?><TR ID="<?php echo $rTypeID . "_" . $assocAlias; ?>_assoc_row_<?php echo $assocID . "_" . $r_count; ?>" style="background-color:#F5F5DC;">
											<TD style="padding-left:15px; padding-right:15px; font-size:8pt; width:120px; font-weight:bold; white-space:nowrap;"><?php
												echo $assocValue . " (" . $parentPrefix . ")";
											?></TD>
			
											<TD style="font-size:8pt; padding-left:10px; width:600px;">

												<!-- Nov. 24/09: DO ***NOT*** use assocAlias here, because they are ***NOT UNIQUE*** across reagent types!!!!!!!!!!!!!!!!!!!!
												A Parent Vector can belong to an Antibody as well as a Cell Line!!!!! -->

												<INPUT TYPE="TEXT" ID="<?php echo $r_type; ?>_assoc_<?php echo $assocAlias; ?>_input" onKeyPress="return disableEnterKey(event);" NAME="<?php echo $r_type; ?>_assoc_<?php echo $assocID; ?>_prop">

												<INPUT TYPE="hidden" ID="<?php echo $r_type; ?>_assoc_type" NAME="reagent_association_types" VALUE="<?php echo $assocID; ?>">
											
												<SPAN class="linkExportSequence" style="margin-left:15px; font-weight:normal;" onClick="addParent('<?php echo $rTypeID; ?>', '<?php echo $r_type; ?>', '<?php echo $assocAlias; ?>', '<?php echo $assocValue;?>', '<?php echo $assocID; ?>', '<?php echo $r_count; ?>');">Add New</SPAN>

												<SPAN class="linkExportSequence" style="margin-left:15px; font-weight:normal;" onClick="deleteTableRow('category_assoc_section_<?php echo $rTypeID?>', '<?php echo $rTypeID . "_" . $assocAlias; ?>_assoc_row_<?php echo $assocID . "_" . $r_count; ?>');">Remove</SPAN>
											</TD>
										</TR><?php

										$r_count++;
									}

									echo "</TABLE>";
								}

							echo "</TD>";
						echo "</TR>";

						// submit button and close form
						?><TR>
							<TD colspan="2"><BR>
								<INPUT TYPE="submit" name="create_reagent" value="Create" onClick="document.pressed = this.value; if (document.getElementById('cancel_set')) {document.getElementById('cancel_set').value=0}; enableSites(); selectAllPropertyValues(true); changeFieldNames('createReagentForm<?php echo str_replace("'", "\'", $r_type); ?>', 'createReagentTbl_<?php echo str_replace("'", "\'", $r_type); ?>', '<?php echo str_replace("'", "\'", $r_type); ?>'); setFeaturePositions('<?php echo str_replace("'", "\'", $r_type); ?>'); verifyPositions(); return checkMandatoryProps('<?php echo str_replace("'", "\'", $r_type); ?>') && verifySequence('<?php echo str_replace("'", "\'", $r_type); ?>', '<?php echo $seq_type; ?>') && checkParentFormat('<?php echo str_replace("'", "\'", $r_type); ?>');">
								<?php
									if (isset($_GET["rID"]))
									{
										?>&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost')">

										<INPUT type="hidden" ID="cancel_set" value="0"><?php
									}
								?>
							</TD>
						</TR>
					</TABLE>
				</FORM>
				<?php

				break;
			}
		}
	}
	
	
	/**
	 * Exception handling function - prints a form notifying the user of an error in reagent creation and giving options to correct the input or start anew.
	 *
	 * Possible error codes:
	 *
	 * VECTOR:
	 *
	 * 1. Wrong sites on Insert, not matching type of Vector being created. Only generated at non_recomb vector creation, i.e. target vector subtype is either non-recombination or gateway entry.
	 * 2. Multiple occurrences of Insert site sequence on Parent Vector sequence: Theoretically occurs for any vector subtype.
	 * 3. Insert site sequence not found on Parent Vector sequence: Can occur at any vector subtype creation.	
	 *
	 * ... more error codes are defined in Python ...
	 *
	 * COMMON TO ALL:
	 *
	 * 1. Non-existent parent ID provided
	 * 2. Creator does not have read access to parent project
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 2007-04-17
	 *
	*/
	function process_Error()
	{
		$rprint_obj = new Reagent_Output_Class();
		$gfunc_obj = new generalFunc_Class();
		$rfunc_obj = new Reagent_Function_Class();
		$gfunc_obj = new generalFunc_Class();

		// August 23/07: Added error checking for other reagent types, so do need to analyze the Type argument
		$type = $_GET["Type"];

		$err_code = $_GET["Err"];
		$subtype = $_GET["Sub"];

		$cs_names = $this->rfunc_obj->get_Post_Names("Insert", "Cloning Sites");
		
		$vector_prefix = "INPUT_VECTOR_backinfo_";
		$vector_postfix = "_prop";

		// August 21/07, Marina: Pass current user info to CGI script
		$currUserName = $_SESSION["userinfo"]->getDescription();

		global $cgi_path;
		global $hostname;

		// April 30/08
		$rID = -1;

		if (isset($_GET["rID"]))	// it better be set!!
			$rID = $_GET["rID"];
		
		// Hidden list of restriction enzymes - one for all reagent types and subtypes
		echo "<SELECT ID=\"enzymeList\" STYLE=\"display:none\"></SELECT>";

		// Updated June 3/08
		switch ($type)
		{
			case 'Vector':
				switch ($subtype)
				{
					case 'nonrecomb':

						// Insert and Parent Vector: Get their database IDs
						$insert_id_tmp = $gfunc_obj->get_rid($_GET["I"]);
						$pv_id_tmp = $gfunc_obj->get_rid($_GET["PV"]);

						if (isset($_GET["R1"]) && isset($_GET["R2"]) && !isset($_GET["Err"]))
						{
							$fpcs = $_GET["R1"];
							$tpcs = $_GET["R2"];

							if (strpos($fpcs, "-"))
							{
								$fp_h1_end = strpos($fpcs, "-");
							
								$fp_h1 = substr($fpcs, 0, $fp_h1_end);
								$fp_h2 = substr($fpcs, $fp_h1_end);
							
								$pv_fpcs = $fp_h1;
								$insert_fpcs = $fp_h2;
							
								$tp_h1_end = strpos($tpcs, "-");
							
								$tp_h1 = substr($tpcs, 0, $tp_h1_end);
								$tp_h2 = substr($tpcs, $tp_h1_end);
							
								$pv_tpcs = $tp_h1;
								$insert_tpcs = $tp_h2;
							}
							else
							{
								$pv_fpcs = $_GET["R1"];
								$insert_fpcs = $_GET["R1"];
								
								$pv_tpcs = $_GET["R2"];
								$insert_tpcs = $_GET["R2"];
							}
						}
						// Nov. 18/08: Allow cloning site values from GET if the exception is a hybridization exception - probably won't work all the time, see what happens
						else if (isset($_GET["R1"]) && isset($_GET["R2"]) && isset($_GET["Err"]) && ($_GET["Err"] == 4))
						{
							$fpcs = $_GET["R1"];
							$tpcs = $_GET["R2"];

							if (strpos($fpcs, "-"))
							{
								$fp_h1_end = strpos($fpcs, "-");
							
								$fp_h1 = substr($fpcs, 0, $fp_h1_end);
								$fp_h2 = substr($fpcs, $fp_h1_end);
							
								$pv_fpcs = $fp_h1;
								$insert_fpcs = $fp_h2;
							
								$tp_h1_end = strpos($tpcs, "-");
							
								$tp_h1 = substr($tpcs, 0, $tp_h1_end);
								$tp_h2 = substr($tpcs, $tp_h1_end);
							
								$pv_tpcs = $tp_h1;
								$insert_tpcs = $tp_h2;
							}
							else
							{
								$pv_fpcs = $_GET["R1"];
								$insert_fpcs = $_GET["R1"];
								
								$pv_tpcs = $_GET["R2"];
								$insert_tpcs = $_GET["R2"];
							}
						}
						else
						{
							// CORRECTION NOVEMBER 24, 2010

// 							$property_tmp = $this->backFill($insert_id_tmp, $cs_names);
// 						
// 							$fpcs = $property_tmp[$_SESSION["ReagentProp_Name_ID"]["5' cloning site"]];
// 							$tpcs = $property_tmp[$_SESSION["ReagentProp_Name_ID"]["3' cloning site"]];

							$fpcs_prop_id = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["5' cloning site"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]);

							$fpcs = $rfunc_obj->getPropertyValue($insert_id_tmp, $fpcs_prop_id);

							$tpcs_prop_id = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["3' cloning site"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]);
							$tpcs = $rfunc_obj->getPropertyValue($insert_id_tmp, $tpcs_prop_id);

							$tmp_fpcs = $_GET["R1"];
							$tmp_tpcs = $_GET["R2"];

							if (strpos($tmp_fpcs, "-"))
							{
								$fp_h1_end = strpos($tmp_fpcs, "-");

								$fp_h1 = substr($tmp_fpcs, 0, $fp_h1_end);
								$fp_h2 = substr($tmp_fpcs, $fp_h1_end+1);
							
								$pv_fpcs = $fp_h1;
								$insert_fpcs = $fp_h2;
							}
							else
							{
								$pv_fpcs = $_GET["R1"];
								$insert_fpcs = $_GET["R1"];
							}

							if (strpos($tmp_tpcs, "-") > 0)
							{
								$tp_h1_end = strpos($tmp_tpcs, "-");
							
								$tp_h1 = substr($tmp_tpcs, 0, $tp_h1_end);
								$tp_h2 = substr($tmp_tpcs, $tp_h1_end+1);
							
								$pv_tpcs = $tp_h1;
								$insert_tpcs = $tp_h2;
							}
							else
							{
								$pv_tpcs = $_GET["R2"];
								$insert_tpcs = $_GET["R2"];
							}
						}
				
						switch ($err_code)
						{
							case '1':
								// Occurs when an attempt is made to generate a NR vector using an Insert with attB sites
								// In this case, only give the options of either choosing a different Insert or starting over - Not to create hybrid sites (and the default option of proceeding - technically incorrect, but verify with Karen)
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">

									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>A sequence for the new vector could not be generated from the sequences of its parents.  The parent vector cannot be digested at one or both of the following restriction sites to generate a non-recombination vector:

										<P>What would you like to do?

										<P>
										<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Change parent values<BR>
										<input type="radio" ID="process_error_restart" name="warning_change_input" value="restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');" checked>Go back and choose a different type of reagent to create<BR>

										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<TABLE cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD style="white-space:nowrap; padding-right:60px;">
													5' Cloning Site on Insert:
												</TD>

												<TD nowrap>
													<?php
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"10\" style=\"color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $fpcs  . "\" name=\"" . $cs_names["5' cloning site"] . "\">";
													?>
												</TD>
											</TR>

											<TR>
												<TD style="white-space:nowrap; padding-right:60px;">
													3' Cloning Site on Insert:
												</TD>

												<TD nowrap>
													<?php
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"10\" style=\"color:brown;  background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $tpcs  . "\" name=\"" . $cs_names["3' cloning site"] . "\">";
													?>
												</TD>
											</TR>
										</TABLE>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							case '2':
								// Insert sites not found on PV sequence.  Here, give the option of making a hybrid:
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
	
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>A sequence for the new vector could not be generated from the sequences of its parents.  One or both cloning sites could not be found on the sequence of the Parent Vector you provided:

										<P>What would you like to do?

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values only (restriction sites will be uploaded from Insert)<BR>
										<input type="radio" id="make_hybrid_option" name="warning_change_input" value="make_hybrid" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Keep existing parent values and change restriction sites to hybrid (or modify existing hybrid values)<BR>
										<input type="radio" name="warning_change_input"  ID="process_error_restart" value="restart" checked onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Go back and choose different parent values or vector type<BR>

										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="15" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="15" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<!-- Updated Nov. 19/08: Replaced text fields with dropdown lists -->
										<TABLE cellpadding="4px" border="1" frame="none" rules="all">
											<TH colspan="2">
												<b>Please specify cloning sites on the Parent Vector and insert:</b><BR>
											</TH>
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold">
													Parent Vector Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:10px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<input type=\"hidden\" id=\"fpcs_val\" value=\"" . $pv_fpcs . "\"></input>";

														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list_1\" name=\"pv_custom_five_prime\" value=\"" . $pv_fpcs . "\" disabled></select>";
													?>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:10px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<input type=\"hidden\" id=\"tpcs_val\" value=\"" . $pv_tpcs . "\"></input>";

														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list_1\" name=\"pv_custom_three_prime\" value=\"" . $pv_tpcs . "\" disabled></select>";
													?>
												</TD>
											</TR>
				
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold;">
													Insert Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:10px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<input type=\"hidden\" id=\"fpcs_val_insert\" value=\"" . $insert_fpcs . "\"></input>";
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list\" name=\"insert_custom_five_prime\" value=\"" . $insert_fpcs . "\" disabled=\"true\"></select>";
													?>
													<BR><div id="fp_warning" style="display:none; color:#FF0000">Please select a value for the 5' restriction site from the dropdown list</div>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:10px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<input type=\"hidden\" id=\"tpcs_val_insert\" value=\"" . $insert_tpcs . "\"></input>";
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list\" value=\"" . $insert_fpcs . "\" name=\"insert_custom_three_prime\" disabled></select>";
													?>
													<BR><div id="tp_warning" style="display:none; color:#FF0000">Please select a value for the 3' restriction site from the dropdown list</div>
												</TD>
											</TR>
				
										</table>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;
							
							case '3':
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents(); ">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>Found multiple occurrences of one or both of the following restriction sites on the parent vector sequence:

										<P>What would you like to do?

										<P>
										<input type="radio" id="make_hybrid_option" name="warning_change_input" value="change_sites" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Create hybrid restriction sites for the new vector, or change existing hybrid site values<BR>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values only (restriction sites will be uploaded from Insert)<BR>
										<input type="radio" name="warning_change_input"  ID="process_error_restart" value="restart" onClick="enableOrDisableSites()" checked>Go back and start over<BR>

										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<TABLE cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													<span style="padding-right:60px;" id="main_5_site_caption">5' Cloning Site on Insert:</span>

													<span id="alt_5_site_caption" style="display:none">5' Cloning Site on resulting Vector:</span>
												</TD>

												<TD>
													<?php
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"10\" id=\"fpcs\" style=\"color:brown\" readonly=\"true\" value=\"" . $fpcs  . "\" name=\"" . $cs_names["5' cloning site"] . "\"><BR>";

														echo "<P id=\"fpcs_comment\" style=\"font-size:7pt; display:none\">Please enter a hyphen-delimited name of the hybrid restriction site (e.g. SalI-XhoI, order: vector_site-insert_site)</P>";
													?>
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													<span style="padding-right:60px;" id="main_3_site_caption">3' Cloning Site on Insert:</span>

													<span id="alt_3_site_caption" style="display:none">3' Cloning Site on resulting Vector:</span>
												</TD>

												<TD>
													<?php
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" id=\"tpcs\" size=\"10\" style=\"color:brown\" readonly=\"true\" value=\"" . $tpcs  . "\" name=\"" . $cs_names["3' cloning site"] . "\"><BR>";

														echo "<P id=\"tpcs_comment\" style=\"font-size:7pt; display:none\">Please enter a hyphen-delimited name of the hybrid restriction site (e.g. SalI-XhoI, order: insert_site-vector_site)</P>";
													?>
												</TD>
											</TR>
										</TABLE>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;
							
							case '4':
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>One or both of the following restriction sites cannot be hybridized:

										<P>What would you like to do?<BR>

										<P>
										<input type="radio" id="make_hybrid_option" name="warning_change_input" value="change_sites" onClick="showHideCustomSites();">Change restriction sites<BR>
										<input type="radio" name="warning_change_input"  ID="process_error_restart" value="restart" onClick="enableOrDisableSites()" checked>Go back and start over<BR>

										<P>
										<TABLE width="400px" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD width="150px">
													5' Cloning Site:
												</TD>

												<TD>
													<?php
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"10\" id=\"fpcs\" style=\"color:brown\" readonly=\"true\" value=\"" . $fpcs  . "\" name=\"" . $cs_names["5' cloning site"] . "\"><BR>";

														echo "<P id=\"fpcs_comment\" style=\"font-size:7pt; display:none\">Please enter a hyphen-delimited name of the hybrid restriction site (e.g. SalI-XhoI, order: vector_site-insert_site)</P>";
													?>
												</TD>
											</TR>

											<TR>
												<TD>
													3' Cloning Site:
												</TD>

												<TD>
													<?php
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" id=\"tpcs\" size=\"10\" style=\"color:brown\" readonly=\"true\" value=\"" . $tpcs  . "\" name=\"" . $cs_names["3' cloning site"] . "\"><BR>";

														echo "<P id=\"tpcs_comment\" style=\"font-size:7pt; display:none\">Please enter a hyphen-delimited name of the hybrid restriction site (e.g. SalI-XhoI, order: insert_site-vector_site)</P>";
													?>
												</TD>
											</TR>
										</TABLE>

										<P>
										<TABLE id="nonrec_parents" width="400px" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<!-- Updated Nov. 19/08: Replaced text fields with dropdown lists -->
										<TABLE cellpadding="4px" border="1" frame="none" rules="all" ID="customCloningSites" style="display:none;">
											<TH colspan="2">
												<b>Please specify cloning sites on the Parent Vector and insert:</b><BR>
											</TH>
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold">
													Parent Vector Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:10px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list_1\" name=\"pv_custom_five_prime\"></select>";
													?>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:10px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list_1\" name=\"pv_custom_three_prime\"></select>";
													?>
												</TD>
											</TR>
				
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold;">
													Insert Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:10px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list\" name=\"insert_custom_five_prime\"></select>";
													?>
													<BR><div id="fp_warning" style="display:none; color:#FF0000">Please select a value for the 5' restriction site from the dropdown list</div>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:10px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list\" name=\"insert_custom_three_prime\"></select>";
													?>
													<BR><div id="tp_warning" style="display:none; color:#FF0000">Please select a value for the 3' restriction site from the dropdown list</div>
												</TD>
											</TR>
										</table>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							case '5':
								// 5' site occurs after 3' on parent sequence
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents(); setCustomSites();">

									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
									<select size="1" style="display:none" id="tpcs_list_<?php echo $subtype; ?>"></select>

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>The 5' Insert cloning site (<b><?php echo $_GET["R1"]; ?></b>) occurs after the 3' Insert cloning site (<b><?php echo $_GET["R2"]; ?></b>) on the parent vector sequence.

										<P>What would you like to do?<BR>

										<A href="pictures/five_after_three.pdf" onClick="return popup(this, 'diagram', '665', '635', 'yes')">Click here to view an illustration of the options below</A><BR>

										<P><input type="radio" id="reverse_insert_option" name="warning_change_input" onClick="document.getElementById('fpcs_list_1').disabled=false; document.getElementById('tpcs_list_1').disabled=false; document.getElementById('fpcs_list').disabled=false; document.getElementById('tpcs_list').disabled=false;" value="reverse_insert">Reverse Complement the Insert sequence&nbsp;
										<P><span style="font-weight:bold; color:red; font-size:8pt; width:150px;"><u>Note</u>: When selecting this option, you must select the cloning sites of the parent vector and Insert from the lists below.  OpenFreezer will reverse complement the Insert sequence for ligation into the Vector after the user selects the sites (i.e. the 3' site on the Insert now has to be designated as the 5' Insert cloning site that will be ligated to the 5' site of the Vector.  The 3' sites must be updated in a similar fashion.</span><BR/>

										<P>
										<input type="radio" id="make_hybrid_option" name="warning_change_input" value="change_sites" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Customize cloning sites by selecting different restriction enzymes or creating hybrid sites<BR>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values only (restriction sites will be uploaded from Insert)<BR>
										<input type="radio" name="warning_change_input" ID="process_error_restart" value="restart" onClick="enableOrDisableSites()" checked>Go back and start over<BR/>

										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
										
										<!-- Updated Nov. 19/08: Replaced text fields with dropdown lists -->
										<P><HR><TABLE cellpadding="4px" ID="cloning_sites"  style="display:inline; margin-top:20px; color:green;">
											<TH colspan="2" style="padding-top:10px;">
												Please specify cloning sites on the Parent Vector and insert:
											</TH>
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold; padding-left:10px; padding-top:15px; color:#0A0AAA;">
													Parent Vector Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:15px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list_1\" name=\"pv_custom_five_prime\" disabled></select>";

														?>
														<BR><SPAN ID="pv_fpcs_warning" style="display:none; color:red; font-size:9pt; font-weight:bold">Please provide a specific value for the 5' cloning site.</SPAN>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:15px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list_1\" name=\"pv_custom_three_prime\" disabled></select>";
													?>
													<BR><SPAN ID="pv_tpcs_warning" style="display:none; font-size:9pt; color:red; font-weight:bold">Please provide a specific value for the 3' cloning site.</SPAN>
												</TD>
											</TR>
				
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold; padding-top:8px; padding-left:10px; color:#0A0AAA;">
													Insert Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:15px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list\" name=\"insert_custom_five_prime\" value=\"\" disabled=\"true\"></select>";
													?>
													<BR><SPAN id="insert_fpcs_warning" style="display:none; color:#FF0000">Please select a value for the 5' restriction site from the dropdown list</SPAN>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:15px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list\" name=\"insert_custom_three_prime\" disabled></select>";
													?>
													<BR><SPAN id="insert_tpcs_warning" style="display:none; color:#FF0000">Please select a value for the 3' restriction site from the dropdown list</SPAN>
												</TD>
											</TR>
				
										</table>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go"  onClick="return verifySites({'fpcs_list_1':'pv_fpcs_warning', 'tpcs_list_1':'pv_tpcs_warning', 'fpcs_list':'insert_fpcs_warning', 'tpcs_list':'insert_tpcs_warning'});">
									</FIELDSET>
								</FORM>
								<?php
							break;
							
							case '6':
								// One or both of the Parent IDs entered do not exist
								// Check which one
								$assocType = $_GET["AP"];	// this is an integer, corresponding to APropertyID in Assoc_Prop_Type_tbl
								
								switch ($assocType)
								{
									case '1':	// insert
										?>
										<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">

											<!-- Pass current user ID as hidden form field -->
											<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

											<!-- Pass type and subtype as hidden value to Python code -->
											<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
											<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
											<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

											<FIELDSET style="width:700px; border:0; padding-left:15px;">
												<P><SPAN style="color:red">There was a problem:</SPAN>
												<P>The Insert ID you provided does not match an existing reagent in the system.

												<P>What would you like to do?

												<P>
												<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); document.getElementById('insert').focus();">Enter a new Insert ID<BR>
												<input type="radio" ID="process_error_restart" name="warning_change_input" value="restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR>

												<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
													<TR>
														<TD nowrap>
															Parent Vector OpenFreezer ID:
														</TD>

														<TD>
															<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
														</TD>

													</TR>
													
													<TR>
														<TD nowrap>
															Insert OpenFreezer ID:
														</TD>

														<TD>
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
														</TD>
													</TR>
												</TABLE>

												<P>
												<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
											</FIELDSET>
										</FORM>
										<?php
									break;
									
									case '2':	// parent vector
										?>
										<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

											<!-- Pass type and subtype as hidden value to Python code -->
											<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
											<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
											<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

											<!-- Pass current user ID as hidden form field -->
											<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

											<FIELDSET style="width:700px; border:0; padding-left:15px;">
												<P><SPAN style="color:red">There was a problem:</SPAN>
												<P>The Parent Vector ID you provided does not match an existing reagent in the system.

												<P>What would you like to do?

												<P>
												<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Enter a new Parent Vector ID<BR>
												<input type="radio" ID="process_error_restart" name="warning_change_input" value="restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR>

												<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
													
													<TR>
														<TD nowrap>
															Parent Vector OpenFreezer ID:
														</TD>

														<TD>
															<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
														</TD>

													</TR>
													
													<TR>
														<TD nowrap>
															Insert OpenFreezer ID:
														</TD>

														<TD>
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
														</TD>
													</TR>
												</TABLE>

												<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
											</FIELDSET>
										</FORM>
										<?php
									break;
									
									case '7':	// parent insert vector
									
									break;
									
									case '8':	// parent cell line vector
									
									break;
									
									case '9':	// parent cell line
									
									break;
									
									default:
									break;	
								}
							break;
							
							case '10':
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
						
									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P style="color:brown;">You are not authorized to use <B><?php echo strtoupper($_GET["PV"]); ?></B> as the parent vector, since <U>you do not have read access to its project</U>.<BR>

										Please contact the project owner to obtain permission or select a different parent vector for your reagent.<BR>

										<input type="radio" name="warning_change_input"  ID="process_error_restart" value="restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');" checked>Start over<BR>
										<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents"; onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Change parent values<BR>

										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php

							break;

							case '11':
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
						
									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P style="color:brown;">You are not authorized to use <B><?php echo strtoupper($_GET["I"]); ?></B> as the parent Insert, since <U>you do not have read access to its project</U>.<BR>

										Please contact the project owner to obtain permission or select a different parent Insert for your reagent.<BR>

										<input type="radio" name="warning_change_input" ID="process_error_restart" value="restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');" checked>Start over<BR>
										<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents"; onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Change parent values<BR>

										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
									</FIELDSET>
								</FORM>
								<?php

							break;

							// May 15/08
							case '13':
								// Insert does not contain cloning sites
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>A sequence for the new vector could not be generated from the sequences of its parents.  Either the Insert sequence does not contain one or both of the following cloning sites, or, if 5' and 3' site values are identical, only contains the enzyme at one end:

										<P><?php
											$tmp_r1 = $_GET["R1"];
											
											if (strlen($tmp_r1) > 0)
											{
												if (strpos($tmp_r1, "-") > 0)
												{
													// hybrid 5', Insert site is after the dash
													$tmp_insert_r1 = substr($tmp_r1, strpos($tmp_r1, "-")+1);
												}
												else
												{
													$tmp_insert_r1 = $tmp_r1;
												}

												echo "<u>5' site</u>: <b>" . $tmp_insert_r1 . "</b><BR>";
											}
										?>
										<P><?php
											$tmp_r2 = $_GET["R2"];
											
											if (strlen($tmp_r2) > 0)
											{
												if (strpos($tmp_r2, "-") > 0)
												{
													// hybrid 3', Insert site is BEFORE the dash
													$tmp_insert_r2 = substr($tmp_r2, 0, strpos($tmp_r2, "-"));
												}
												else
												{
													$tmp_insert_r2 = $tmp_r2;
												}

												echo "<u>3' site</u>: <b>" . $tmp_insert_r2 . "</b><BR>";
											}
										?>
										<P>What would you like to do?

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values (cloning sites will be loaded from the Insert)<BR>

										<input type="radio" id="make_hybrid_option" name="warning_change_input" value="make_hybrid" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Keep existing parent values and change restriction sites to hybrid (or modify existing hybrid values)<BR/>

										<input type="radio" name="warning_change_input" ID="process_error_restart" value="restart" checked onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Go back and choose different parent values or vector type<BR/>
										
										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<!-- Updated Nov. 19/08: Replaced text fields with dropdown lists -->
										<TABLE cellpadding="4px" border="1" frame="none" rules="all">
											<TH colspan="2">
												<b>Please specify cloning sites on the Parent Vector and insert:</b><BR>
											</TH>
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold">
													Parent Vector Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:10px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list_1\" name=\"pv_custom_five_prime\" disabled></select>";
													?>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:10px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list_1\" name=\"pv_custom_three_prime\" disabled></select>";
													?>
												</TD>
											</TR>
				
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold;">
													Insert Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:10px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list\" name=\"insert_custom_five_prime\" disabled=\"true\"></select>";
													?>
													<BR><div id="fp_warning" style="display:none; color:#FF0000">Please select a value for the 5' restriction site from the dropdown list</div>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:10px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list\" disabled name=\"insert_custom_three_prime\"></select>";
													?>
													<BR><div id="tp_warning" style="display:none; color:#FF0000">Please select a value for the 3' restriction site from the dropdown list</div>
												</TD>
											</TR>
				
										</table>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							// May 30/08
							case '14':
								// Insert cloning site values don't match sequence at site positions
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>A sequence for the new vector could not be generated from the sequences of its parents.<BR>

										<P>One or both cloning sites does not match an actual enzyme sequence at the following positions on <a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["I"]); ?>" target="blank"><?php echo $_GET["I"]; ?></a>:

										<P>What would you like to do?

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values<BR>
										<input type="radio" name="warning_change_input" ID="process_error_restart" value="restart" checked onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Go back and choose different parent values or vector type<BR>

										<TABLE width="100%" id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD style="width:250px;">
													Parent Vector OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD style="width:180px;">
													Insert OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
										<BR>

										<TABLE width="100%" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD style="width:250px;">
													<span id="main_5_site_caption">5' Cloning Site on Insert:</span>

													<span id="alt_5_site_caption" style="display:none">5' Cloning Site on resulting Vector:</span>
												</TD>

												<TD style="padding-left:10px; white-space:nowrap;">
													<?php
														// May 30/08: DO **NOT** give these inputs a name!!!  Don't call them "sites" - Python  will treat them as form input and take the wrong action!!!!!!  Sites here are just for reference; the only input CGI needs here are parents!!!
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"15\" id=\"fpcs\" style=\"color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $_GET["R1"]  . "\">";

														$pStart = $rfunc_obj->getStartPos($gfunc_obj->get_rid($_GET["I"]), "5' cloning site", $_SESSION["ReagentProp_Name_ID"]["5' cloning site"]);

														echo "Start: <INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" style=\"margin-left:5px; color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $pStart  . "\">";

														$pEnd = $rfunc_obj->getEndPos($gfunc_obj->get_rid($_GET["I"]), "5' cloning site", $_SESSION["ReagentProp_Name_ID"]["5' cloning site"]);

														echo "End:<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" style=\"margin-left:5px; color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $pEnd . "\">";

														echo "<BR>";

														echo "<P id=\"fpcs_comment\" style=\"font-size:7pt; display:none\">Please enter a hyphen-delimited name of the hybrid restriction site (e.g. SalI-XhoI, order: vector_site-insert_site)</P>";
													?>
												</TD>
											</TR>

											<TR>
												<TD  style="width:250px;">
													<span id="main_3_site_caption">3' Cloning Site on Insert:</span>

													<span id="alt_3_site_caption" style="display:none">3' Cloning Site on resulting Vector:</span>
												</TD>

												<TD style="padding-left:10px;">
												<?php
													// May 30/08: DO **NOT** give these inputs a name!!!  Don't call them "sites" - Python  will treat them as form input and take the wrong action!!!!!!  Sites are just for reference here; the only input CGI needs here are parents!!!
													echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" id=\"tpcs\" size=\"15\" style=\"color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $_GET["R2"]  . "\">";

													$pStart = $rfunc_obj->getStartPos($gfunc_obj->get_rid($_GET["I"]), "3' cloning site", $_SESSION["ReagentProp_Name_ID"]["3' cloning site"]);

													echo "Start: <INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" style=\"margin-left:5px; color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $pStart  . "\">";

													$pEnd = $rfunc_obj->getEndPos($gfunc_obj->get_rid($_GET["I"]), "3' cloning site", $_SESSION["ReagentProp_Name_ID"]["3' cloning site"]);

													echo "End:<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" style=\"margin-left:5px; color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $pEnd . "\">";

													echo "<BR>";

													echo "<P id=\"tpcs_comment\" style=\"font-size:7pt; display:none\">Please enter a hyphen-delimited name of the hybrid restriction site (e.g. SalI-XhoI, order: insert_site-vector_site)</P>";
												?>
												</TD>
											</TR>
										</TABLE>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							// Added November 24, 2010: Empty Insert cloning sites (e.g. I789 - DNA fragment, no sequence, no sites, mutagenesis)
							case '23':
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>Cloning sites are not defined on the parent Insert.<BR>

										<P>Please verify the information on cloning sites recorded for <a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["I"]); ?>" target="blank"><?php echo $_GET["I"]; ?></a>:

										<P>What would you like to do?

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values<BR>
										<input type="radio" name="warning_change_input" ID="process_error_restart" value="restart" checked onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Go back and choose different parent values or vector type<BR><BR>

										<TABLE width="350px" id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD style="white-space:nowrap;">
													Parent Vector OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD style="white-space:nowrap;">
													Insert OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
										<BR>

										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							// Dec. 14/09
							case '24':
								// Empty parent Vector sequence
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>A sequence for the new vector could not be generated from the sequences of its parents.<BR>

										<P>The sequence of the parent Vector <a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["PV"]); ?>" target="blank"><?php echo $_GET["PV"]; ?></a> is empty.

										<P>What would you like to do?

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values<BR>
										<input type="radio" name="warning_change_input" ID="process_error_restart" value="restart" checked onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Cancel creation and start over<BR>
										
										<TABLE width="100%" id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD style="width:250px;">
													Parent Vector OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD style="width:180px;">
													Insert OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
										<BR>
										
										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
									</FIELDSET>
								</FORM>
								<?php
							break;

							// Dec. 14/09
							case '25':
								// Empty parent Insert sequence
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>A sequence for the new vector could not be generated from the sequences of its parents.<BR>

										<P>The sequence of the parent Insert <a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["I"]); ?>" target="blank"><?php echo $_GET["I"]; ?></a> is empty.

										<P>What would you like to do?

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values<BR>
										<input type="radio" name="warning_change_input" ID="process_error_restart" value="restart" checked onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Cancel creation and start over<BR>

										<TABLE width="100%" id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD style="width:250px;">
													Parent Vector OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD style="width:180px;">
													Insert OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
										<BR>
										
										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
									</FIELDSET>
								</FORM>
								<?php
							break;


							case '27':	// March 5, 2010
								// Incompatible overhangs
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents(); setCustomSites();">
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
									<select size="1" style="display:none" id="tpcs_list_<?php echo $subtype; ?>"></select>

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>The 5' overhangs of Parent Vector and Insert cloning sites are incompatible with each other.

										<P>What would you like to do?<BR>

										<input type="radio" id="make_hybrid_option" name="warning_change_input" value="change_sites" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Customize cloning sites by selecting different restriction enzymes or creating hybrid sites<BR>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values only (restriction sites will be uploaded from Insert)<BR>
										<input type="radio" name="warning_change_input" ID="process_error_restart" value="restart" onClick="enableOrDisableSites()" checked>Go back and start over<BR>
										
										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
										
										<!-- Updated Nov. 19/08: Replaced text fields with dropdown lists -->
										<P><HR><TABLE cellpadding="4px" ID="cloning_sites"  style="display:inline; margin-top:20px; color:green;">
											<TH colspan="2" style="padding-top:10px;">
												Please specify cloning sites on the Parent Vector and insert:
											</TH>
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold; padding-left:10px; padding-top:15px; color:#0A0AAA;">
													Parent Vector Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:15px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list_1\" name=\"pv_custom_five_prime\" disabled></select>";
													?>
													<BR><SPAN ID="pv_fpcs_warning" style="display:none; color:red; font-size:9pt; font-weight:bold">Please provide a specific value for the 5' cloning site.</SPAN>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:15px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list_1\" name=\"pv_custom_three_prime\" disabled></select>";
													?>
													<BR><SPAN ID="pv_tpcs_warning" style="display:none; font-size:9pt; color:red; font-weight:bold">Please provide a specific value for the 3' cloning site.</SPAN>
												</TD>
											</TR>
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold; padding-top:8px; padding-left:10px; color:#0A0AAA;">
													Insert Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:15px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list\" name=\"insert_custom_five_prime\" value=\"\" disabled=\"true\"></select>";
													?>
													<BR><SPAN id="insert_fpcs_warning" style="display:none; color:#FF0000">Please select a value for the 5' restriction site from the dropdown list</SPAN>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:15px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list\" name=\"insert_custom_three_prime\" disabled></select>";
													?>
													<BR><SPAN id="insert_tpcs_warning" style="display:none; color:#FF0000">Please select a value for the 3' restriction site from the dropdown list</SPAN>
												</TD>
											</TR>
										</table>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go"  onClick="return verifySites({'fpcs_list_1':'pv_fpcs_warning', 'tpcs_list_1':'pv_tpcs_warning', 'fpcs_list':'insert_fpcs_warning', 'tpcs_list':'insert_tpcs_warning'});">
									</FIELDSET>
								</FORM>
								<?php
							break;

							case '28':	// March 5, 2010
								// Incompatible overhangs
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents(); setCustomSites();">
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
									<select size="1" style="display:none" id="tpcs_list_<?php echo $subtype; ?>"></select>

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>The 3' overhangs of Parent Vector and Insert cloning sites are incompatible with each other.

										<P>What would you like to do?<BR>

										<input type="radio" id="make_hybrid_option" name="warning_change_input" value="change_sites" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Customize cloning sites by selecting different restriction enzymes or creating hybrid sites<BR>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values only (restriction sites will be uploaded from Insert)<BR>
										<input type="radio" name="warning_change_input" ID="process_error_restart" value="restart" onClick="enableOrDisableSites()" checked>Go back and start over<BR/>

										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
										
										<!-- Updated Nov. 19/08: Replaced text fields with dropdown lists -->
										<P><HR><TABLE cellpadding="4px" ID="cloning_sites"  style="display:inline; margin-top:20px; color:green;">
											<TH colspan="2" style="padding-top:10px;">
												Please specify cloning sites on the Parent Vector and insert:
											</TH>
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold; padding-left:10px; padding-top:15px; color:#0A0AAA;">
													Parent Vector Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:15px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list_1\" name=\"pv_custom_five_prime\" disabled></select>";
													?>
													<BR><SPAN ID="pv_fpcs_warning" style="display:none; color:red; font-size:9pt; font-weight:bold">Please provide a specific value for the 5' cloning site.</SPAN>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:15px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list_1\" name=\"pv_custom_three_prime\" disabled></select>";
													?>
													<BR><SPAN ID="pv_tpcs_warning" style="display:none; font-size:9pt; color:red; font-weight:bold">Please provide a specific value for the 3' cloning site.</SPAN>
												</TD>
											</TR>
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold; padding-top:8px; padding-left:10px; color:#0A0AAA;">
													Insert Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:15px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list\" name=\"insert_custom_five_prime\" value=\"\" disabled=\"true\"></select>";
													?>
													<BR><SPAN id="insert_fpcs_warning" style="display:none; color:#FF0000">Please select a value for the 5' restriction site from the dropdown list</SPAN>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:15px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list\" name=\"insert_custom_three_prime\" disabled></select>";
													?>
													<BR><SPAN id="insert_tpcs_warning" style="display:none; color:#FF0000">Please select a value for the 3' restriction site from the dropdown list</SPAN>
												</TD>
											</TR>
				
										</table>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go"  onClick="return verifySites({'fpcs_list_1':'pv_fpcs_warning', 'tpcs_list_1':'pv_tpcs_warning', 'fpcs_list':'insert_fpcs_warning', 'tpcs_list':'insert_tpcs_warning'});">
									</FIELDSET>
								</FORM>
								<?php
							break;

							case '29':

								if (strpos($_GET["R1"], "-") > 0)
								{
									$fp_sites_tok = split("-", $_GET["R1"]);
									$tp_sites_tok = split("-", $_GET["R2"]);

									$insert_fpcs = $fp_sites_tok[1];
									$insert_tpcs = $tp_sites_tok[0];
								}
								else
								{
									$insert_fpcs = $_GET["R1"];
									$insert_tpcs = $_GET["R2"];
								}

								// 5' site occurs after 3' on parent INSERT sequence
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents(); setCustomSites();">

									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
									<select size="1" style="display:none" id="tpcs_list_<?php echo $subtype; ?>"></select>

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>The 5' Insert cloning site provided (<b><?php echo $insert_fpcs; ?></b>) occurs after the 3' Insert cloning site provided (<b><?php echo $insert_tpcs; ?></b>) on the Insert sequence.

										<P>What would you like to do?

										<A href="pictures/five_after_three.pdf" onClick="return popup(this, 'diagram', '665', '635', 'yes')">Click here to view an illustration of the options below</A><BR>

										<P><input type="radio" id="reverse_insert_option" name="warning_change_input" onClick="document.getElementById('fpcs_list_1').disabled=false; document.getElementById('tpcs_list_1').disabled=false; document.getElementById('fpcs_list').disabled=false; document.getElementById('tpcs_list').disabled=false;" value="reverse_insert">Reverse Complement the Insert sequence&nbsp;
										<P><span style="font-weight:bold; color:red; font-size:8pt; width:180px;"><u>Note</u>: When selecting this option, you must select the cloning sites of the parent vector and Insert from the lists below.  OpenFreezer will reverse complement the Insert sequence for ligation into the Vector after the user selects the sites (i.e. the 3' site on the Insert now has to be designated as the 5' Insert cloning site that will be ligated to the 5' site of the Vector.  The 3' sites must be updated in a similar fashion.</span><BR>

										<P>
										<input type="radio" id="make_hybrid_option" name="warning_change_input" value="change_sites" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Customize cloning sites by selecting different restriction enzymes or creating hybrid sites<BR>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values only (restriction sites will be uploaded from Insert)<BR>
										<input type="radio" name="warning_change_input" ID="process_error_restart" value="restart" onClick="enableOrDisableSites()" checked>Go back and start over<BR>

										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
										
										<!-- Updated Nov. 19/08: Replaced text fields with dropdown lists -->
										<P><HR><TABLE cellpadding="4px" ID="cloning_sites"  style="display:inline; margin-top:20px; color:green;">
											<TH colspan="2" style="padding-top:10px;">
												Please specify cloning sites on the Parent Vector and insert:
											</TH>
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold; padding-left:10px; padding-top:15px; color:#0A0AAA;">
													Parent Vector Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:15px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list_1\" name=\"pv_custom_five_prime\" disabled></select>";
													?>
													<BR><SPAN ID="pv_fpcs_warning" style="display:none; color:red; font-size:9pt; font-weight:bold">Please provide a specific value for the 5' cloning site.</SPAN>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:15px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list_1\" name=\"pv_custom_three_prime\" disabled></select>";
													?>
													<BR><SPAN ID="pv_tpcs_warning" style="display:none; font-size:9pt; color:red; font-weight:bold">Please provide a specific value for the 3' cloning site.</SPAN>
												</TD>
											</TR>
				
											<tr>
												<td colspan="2" style="font-size:9pt; font-weight:bold; padding-top:8px; padding-left:10px; color:#0A0AAA;">
													Insert Cloning Sites:
												</td>
											</tr>
									
											<TR>
												<TD style="padding-left:15px">
													5' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"fpcs_list\" name=\"insert_custom_five_prime\" value=\"\" disabled=\"true\"></select>";
													?>
													<BR><SPAN id="insert_fpcs_warning" style="display:none; color:#FF0000">Please select a value for the 5' restriction site from the dropdown list</SPAN>
												</TD>
											</TR>
									
											<TR>
												<TD style="padding-left:15px">
													3' Cloning Site:
												</TD>
									
												<TD>
													<?php
														echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier;\" id=\"tpcs_list\" name=\"insert_custom_three_prime\" disabled></select>";
													?>
													<BR><SPAN id="insert_tpcs_warning" style="display:none; color:#FF0000">Please select a value for the 3' restriction site from the dropdown list</SPAN>
												</TD>
											</TR>
										</table>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go"  onClick="return verifySites({'fpcs_list_1':'pv_fpcs_warning', 'tpcs_list_1':'pv_tpcs_warning', 'fpcs_list':'insert_fpcs_warning', 'tpcs_list':'insert_tpcs_warning'});">
									</FIELDSET>
								</FORM>
								<?php
							break;
							

							default:
								return false;
							break;
						}
					break;

					case 'recomb':
						$pv_id_tmp = $gfunc_obj->get_rid($_GET["PV"]);
						$ipv_id_tmp = $gfunc_obj->get_rid($_GET["IPV"]);

						switch ($err_code)
						{
							// July 4/08
							case '2':
								// LoxP sites not found on PV sequence.
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
									
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P style="color:#FF0000; font-weight:bold;">Error: Cannot construct sequence<BR>

										<P style="color:#FF0000">The <b>LoxP</b> restriction site could not be found on the sequence of the Creator Acceptor Vector you provided:<BR>

										<P>What would you like to do?<BR>

										<P><input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Change parent values<BR>

										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" checked>Go back and select another vector type<BR>
										
										<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Creator Acceptor Vector ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>
											
											<TR>
												<TD nowrap>
													Creator Donor Vector ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;


							case '3':
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents(); ">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>Found multiple occurrences of LoxP site on the sequence of the Creator Acceptor Vector:

										<P>What would you like to do?

										<P><input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Change parent values<BR>
										<input type="radio" name="warning_change_input"  ID="process_error_restart" value="restart" checked>Go back and start over<BR>

										<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Creator Acceptor Vector ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>

											</TR>
											
											<TR>
												<TD nowrap>
													Creator Donor Vector ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							// May 30/08: Capture error where a non-existent parent ID is passed
							case '6':
								// One or both of the Parent IDs entered do not exist
								// Check which one
								$assocType = $_GET["AP"];	// this is an integer, corresponding to APropertyID in Assoc_Prop_Type_tbl
								
								switch ($assocType)
								{
									case '2':	// parent vector
										?>
										<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

											<!-- Pass type and subtype as hidden value to Python code -->
											<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
											<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
											<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

											<!-- Pass current user ID as hidden form field -->
											<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

											<FIELDSET style="width:100%; border:0; padding-left:15px;">
												<P><SPAN style="color:red">There was a problem:</SPAN>
												<P>The ID of the Creator Acceptor Vector provided does not match an existing reagent in the system.

												<P>What would you like to do?

												<P>
												<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Enter a new Creator Acceptor Vector ID<BR>
												<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR>

												<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
													
													<TR>
														<TD nowrap>
															Creator Acceptor Vector ID:
														</TD>

														<TD>
															<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
														</TD>

													</TR>
													
													<TR>
														<TD nowrap>
															Creator Donor  Vector ID:
														</TD>

														<TD>
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
														</TD>
													</TR>
												</TABLE>

												<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
											</FIELDSET>
										</FORM>
										<?php
									break;
									
									case '7':	// parent insert vector
										?>
										<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

											<!-- Pass type and subtype as hidden value to Python code -->
											<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
											<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
											<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

											<!-- Pass current user ID as hidden form field -->
											<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

											<FIELDSET style="width:100%; border:0; padding-left:15px;">
												<P><SPAN style="color:red;">An error occurred during sequence construction:</SPAN>

												<P style="color:red; font-weight:bold">Invalid Creator Donor Vector:

												<P>Either <SPAN style="color:#FF0000;"><?php echo strtoupper($_GET["IPV"]); ?></span> does not match an existing reagent in the system or a valid Insert for it could not be found.</P>

												<P>What would you like to do?</P>

												<P>
												<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Enter a new Vector ID<BR/>

												<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR/>
												</P>

												<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
													
													<TR>
														<TD nowrap>
															Creator Acceptor Vector ID:
														</TD>

														<TD>
															<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
														</TD>

													</TR>
													
													<TR>
														<TD nowrap>
															Creator Donor Vector ID:
														</TD>

														<TD>
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
														</TD>
													</TR>
												</TABLE>

												<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
											</FIELDSET>
										</FORM>
										<?php
									break;
									
									default:
									break;
								}
							break;

							case '10':
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
						
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>

										<P style="color:brown;">You are not authorized to use <B><?php echo strtoupper($_GET["PV"]); ?></B> as the Creator Acceptor Vector, since <U>you do not have read access to its project</U>.<BR>
										Please contact the project owner to obtain permission or select a different parent vector for your reagent.<BR>

										<input type="radio" name="warning_change_input"  ID="process_error_restart" value="restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');" checked>Start over<BR>
										<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableRecombParents();">Change parent values<BR>

										<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Creator Acceptor Vector ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Creator Donor Vector ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo strtoupper($_GET["IPV"]); ?>">
												</TD>
											</TR>
										</TABLE>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							case '12':
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
			
									<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
						
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:#FF0000;">There was a problem:</SPAN>

										<SPAN style="color:brown;">You are not authorized to use <B><?php echo strtoupper($_GET["IPV"]); ?></B> as the Creator Donor Vector, since <U>you do not have read access to its project</U>.<BR>

										Please contact the project owner to obtain permission or select a different Parent Insert Vector for your reagent.<BR>

										<input type="radio" name="warning_change_input"  ID="process_error_restart" value="restart" onClick="enableOrDisableRecombParents();" checked>Start over<BR>
										<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents"; onClick="enableOrDisableRecombParents();">Change parent values<BR>

										</SPAN>

										<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Creator Acceptor Vector ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Creator Donor Vector ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							// June 1/08
							case '14':
								// June 4, 2010: Insert for recombination cloning?  will produce an error with our latest change! investigate where this is thrown

								// Insert cloning site values don't match sequence at site positions
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>A sequence for the new vector could not be generated from the sequences of its parents.<BR>

										<P>One or both cloning sites does not match an actual enzyme sequence at the following positions on <a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["I"]); ?>" target="blank"><?php echo $_GET["I"]; ?></a>:<BR>

										<P>What would you like to do?<BR>

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values<BR>
										<input type="radio" name="warning_change_input"  ID="process_error_restart" value="restart" checked onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Go back and choose different parent values or vector type<BR>

										<TABLE width="100%" id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD style="width:250px;">
													Parent Vector OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD style="width:250px;">
													Insert Parent Vector OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD style="width:180px;">
													Insert OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
										<BR>

										<TABLE width="650px" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD style="width:250px;">
													<span id="main_5_site_caption">5' Cloning Site on Insert:</span>

													<span id="alt_5_site_caption" style="display:none">5' Cloning Site on resulting Vector:</span>
												</TD>

												<TD style="padding-left:10px; white-space:nowrap;">
													<?php
														// May 30/08: DO **NOT** give these inputs a name!!!  Don't call them "sites" - Python  will treat them as form input and take the wrong action!!!!!!  Sites here are just for reference; the only input CGI needs here are parents!!!
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"15\" id=\"fpcs\" style=\"color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $_GET["R1"]  . "\">";

														$pStart = $rfunc_obj->getStartPos($gfunc_obj->get_rid($_GET["I"]), "5' cloning site", $_SESSION["ReagentProp_Name_ID"]["5' cloning site"]);

														echo "Start: <INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" style=\"margin-left:5px; color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $pStart  . "\">";

														$pEnd = $rfunc_obj->getEndPos($gfunc_obj->get_rid($_GET["I"]), "5' cloning site", $_SESSION["ReagentProp_Name_ID"]["5' cloning site"]);

														echo "End:<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" style=\"margin-left:5px; color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $pEnd . "\">";

														echo "<BR>";

														echo "<P id=\"fpcs_comment\" style=\"font-size:7pt; display:none\">Please enter a hyphen-delimited name of the hybrid restriction site (e.g. SalI-XhoI, order: vector_site-insert_site)</P>";
													?>
												</TD>
											</TR>

											<TR>
												<TD  style="width:250px;">
													<span id="main_3_site_caption">3' Cloning Site on Insert:</span>

													<span id="alt_3_site_caption" style="display:none">3' Cloning Site on resulting Vector:</span>
												</TD>

												<TD style="padding-left:10px;">
													<?php
														// May 30/08: DO **NOT** give these inputs a name!!!  Don't call them "sites" - Python  will treat them as form input and take the wrong action!!!!!!  Sites are just for reference here; the only input CGI needs here are parents!!!
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" id=\"tpcs\" size=\"15\" style=\"color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $_GET["R2"]  . "\">";

														$pStart = $rfunc_obj->getStartPos($gfunc_obj->get_rid($_GET["I"]), "3' cloning site", $_SESSION["ReagentProp_Name_ID"]["3' cloning site"]);

														echo "Start: <INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" style=\"margin-left:5px; color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $pStart  . "\">";

														$pEnd = $rfunc_obj->getEndPos($gfunc_obj->get_rid($_GET["I"]), "3' cloning site", $_SESSION["ReagentProp_Name_ID"]["3' cloning site"]);

														echo "End:<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" style=\"margin-left:5px; color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $pEnd . "\">";

														echo "<BR>";

														echo "<P id=\"tpcs_comment\" style=\"font-size:7pt; display:none\">Please enter a hyphen-delimited name of the hybrid restriction site (e.g. SalI-XhoI, order: insert_site-vector_site)</P>";
													?>
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							// June 2/08
							case '15':
								// LoxP not found on DONOR (IPV) sequence
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
									
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>

										<P><SPAN style="color:#FF0000;"><b>LoxP</b> cloning site could not be found on the sequence of &nbsp;<a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["IPV"]); ?>"><?php echo strtoupper($_GET["IPV"]); ?></a></SPAN>

										<P>What would you like to do?<BR>

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Change parent values<BR>
										<input type="radio" ID="process_error_restart" name="warning_change_input" value="restart" checked>Go back and select another vector type<BR>

										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Creator Acceptor Vector ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Creator Donor Vector ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo strtoupper($_GET["IPV"]); ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							// June 3/08
							case '16':
								// LoxP site only found once on donor sequence
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red;">An error occurred during sequence construction:</SPAN>

										<P><SPAN style="color:red; font-weight:bold;">Found only one occurrence of LoxP cloning site on the sequence of donor vector <a style="font-weight:normal;" href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["IPV"]); ?>"><?php echo strtoupper($_GET["IPV"]); ?></a></SPAN>

										<P>What would you like to do?</P>

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Enter a new Donor Vector ID<BR/>

										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR/>
										</P>

										<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
											
											<TR>
												<TD nowrap>
													Creator Acceptor Vector ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>

											</TR>
											
											<TR>
												<TD nowrap>
													Creator Donor Vector ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							// June 3/08
							case '18':
								// One or both LoxP sites in resulting recomb. vector sequence got destroyed as a result of user modification (between steps 2 and 3 - previewSequence and previewFeatures)
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red; font-weight:bold;">Error: Cannot find LoxP site on resulting sequence!</SPAN>

										<P>Please go back and correct the sequence before proceeding

										<P><INPUT TYPE="BUTTON" style="margin-left:10px;" onClick="document.getElementById('navigate_away').value=0; redirect('<?php echo $hostname . "Reagent.php?View=2&Step=1&Type=Vector&Sub=" . $subtype . "&PV=" . $gfunc_obj->get_rid($_GET["PV"]) . "&IPV=" . $gfunc_obj->get_rid($_GET["IPV"]) . "&rID=" . $rID . "&Seq=" . $_GET["Seq"]; ?>');" VALUE="Go back and edit sequence"></INPUT>
									</FIELDSET>
								</FORM>
								<?php
							break;

							// June 2/08
							case '19':
								// Raised when one of the vector parents provided is not of suitable type 
								// Introduced mainly to prevent errors where recomb. vectors are used as donors - i.e. IPV in recomb. vector creation must have ATypeID 1
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red;">An error occurred during sequence construction:</SPAN>

										<P><a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["IPV"]); ?>"><?php echo strtoupper($_GET["IPV"]); ?></a>&nbsp; cannot be properly recombined to produce a Creator Expression Vector.  Please verify your Donor Vector input.</SPAN>

										<P>What would you like to do?</P>

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Enter a new Donor Vector ID<BR/>

										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR/>
										</P>

										<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
											
											<TR>
												<TD nowrap>
													Creator Acceptor Vector ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>

											</TR>
											
											<TR>
												<TD nowrap>
													Creator Donor Vector ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;
						}
					break;

					case 'gateway_entry':
						// Insert and Parent Vector: Get their database IDs
						$insert_id_tmp = $gfunc_obj->get_rid($_GET["I"]);
						$pv_id_tmp = $gfunc_obj->get_rid($_GET["PV"]);

						switch ($err_code)
						{
							// April 10/08
							case '2':
								// Insert sites not found on PV sequence.  Here, give the option of making a hybrid:
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>A sequence for the new vector could not be generated from the sequences of its parents.  One or both of the following restriction sites could not be found on the sequence of the Parent Vector you provided:<BR>

										<P>What would you like to do?<BR>

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values only (restriction sites will be uploaded from Insert)<BR>
										<input type="radio" id="make_hybrid_option" name="warning_change_input" value="make_hybrid" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Keep existing parent values and change restriction sites to hybrid (or modify existing hybrid values)<BR>
										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" checked onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Go back and choose different parent values or vector type<BR>

										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<TABLE cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													<span style="padding-right:60px;" id="main_5_site_caption">5' Cloning Site on Insert:</span>

													<span id="alt_5_site_caption" style="display:none">5' Cloning Site on resulting Vector:</span>
												</TD>

												<TD>
													<?php
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"10\" id=\"fpcs\" style=\"color:brown\" readonly=\"true\" value=\"" . $_GET["R1"]  . "\" name=\"" . $_SESSION["ReagentProp_Name_Alias"]["5' cloning site"] . "\"><BR>";

														echo "<P id=\"fpcs_comment\" style=\"font-size:7pt; display:none\">Please enter a hyphen-delimited name of the hybrid restriction site (e.g. SalI-XhoI, order: vector_site-insert_site)</P>";
													?>
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													<span style="padding-right:60px;" id="main_3_site_caption">3' Cloning Site on Insert:</span>

													<span id="alt_3_site_caption" style="display:none">3' Cloning Site on resulting Vector:</span>
												</TD>

												<TD>
													<?php
														// Modified May 14/08
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" id=\"tpcs\" size=\"10\" style=\"color:brown\" readonly=\"true\" value=\"" . $_GET["R2"]  . "\" name=\"" . $_SESSION["ReagentProp_Name_Alias"]["3' cloning site"] . "\"><BR>";

														echo "<P id=\"tpcs_comment\" style=\"font-size:7pt; display:none\">Please enter a hyphen-delimited name of the hybrid restriction site (e.g. SalI-XhoI, order: insert_site-vector_site)</P>";
													?>
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
									</FIELDSET>
								</FORM>
								<?php
							break;

							case '6':
								// One or both of the Parent IDs entered do not exist
								// Check which one
								$assocType = $_GET["AP"];	// this is an integer, corresponding to APropertyID in Assoc_Prop_Type_tbl
								
								switch ($assocType)
								{
									case '1':	// insert
										?>
										<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">

											<!-- Pass current user ID as hidden form field -->
											<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

											<!-- Pass type and subtype as hidden value to Python code -->
											<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
											<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
											<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

											<FIELDSET style="width:100%; border:0; padding-left:15px;">
												<P><SPAN style="color:red">There was a problem:</SPAN>

												<P>The Insert ID you provided does not match an existing reagent in the system.

												<P>What would you like to do?

												<P>
												<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); document.getElementById('insert').focus();">Enter a new Insert ID<BR>
												<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR>
												
												<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
													<TR>
														<TD nowrap>
															Parent Vector OpenFreezer ID:
														</TD>

														<TD>
															<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
														</TD>

													</TR>
													
													<TR>
														<TD nowrap>
															Insert OpenFreezer ID:
														</TD>

														<TD>
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
														</TD>
													</TR>
												</TABLE>

												<P>
												<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
											</FIELDSET>
										</FORM>
										<?php
									break;
									
									case '2':	// parent vector
										?>
										<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

											<!-- Pass type and subtype as hidden value to Python code -->
											<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
											<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
											<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

											<!-- Pass current user ID as hidden form field -->
											<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

											<FIELDSET style="width:100%; border:0; padding-left:15px;">
												<P><SPAN style="color:red">There was a problem:</SPAN>
												<P>The Parent Vector ID you provided does not match an existing reagent in the system.

												<P>What would you like to do?

												<P>
												<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Enter a new Parent Vector ID<BR>
												<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR>
												
												<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
													<TR>
														<TD nowrap>
															Parent Vector OpenFreezer ID:
														</TD>

														<TD>
															<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
														</TD>

													</TR>
													
													<TR>
														<TD nowrap>
															Insert OpenFreezer ID:
														</TD>

														<TD>
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
														</TD>
													</TR>
												</TABLE>

												<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
											</FIELDSET>
										</FORM>
										<?php
									break;
								}
							break;

							case '10':
								// don't have access to parent vector project
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
						
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
			
										<P style="color:brown;">You are not authorized to use <B><?php echo strtoupper($_GET["PV"]); ?></B> as the parent vector, since <U>you do not have read access to its project</U>.<BR>
			
										Please contact the project owner to obtain permission or select a different parent vector for your reagent.</P>
			
										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');" checked>Start over<BR>
										<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents"; onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Change parent values<BR>
			
										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>
			
												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>
			
											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>
			
												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
			
										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;
			
							case '11':
								// don't have read access to Insert project
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
						
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>

										<P><SPAN style="color:brown;">You are not authorized to use <B><?php echo strtoupper($_GET["I"]); ?></B> as the parent Insert, since <U>you do not have read access to its project</U>.</SPAN><BR>
			
										Please contact the project owner to obtain permission or select a different parent Insert for your reagent.</P>
			
										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');" checked>Start over<BR/>
			
										<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents"; onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Change parent values<BR/>
			
										</P>
			
										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>
			
												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>
			
											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>
			
												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
			
										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;
			
							// May 14/08
							case '13':
								// Insert does not contain cloning sites
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>

										<P>A sequence for the new vector could not be generated from the sequences of its parents.  The Insert sequence does not contain one or both of the following restriction sites:<BR>

										<P>What would you like to do?

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values<BR>
										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" checked onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Go back and choose different parent values or vector type<BR>
										
										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<TABLE cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													<span style="padding-right:60px;" id="main_5_site_caption">5' Cloning Site on Insert:</span>

													<span id="alt_5_site_caption" style="display:none">5' Cloning Site on resulting Vector:</span>
												</TD>

												<TD>
													<?php
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"10\" id=\"fpcs\" style=\"color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $_GET["R1"]  . "\" name=\"" . $_SESSION["ReagentProp_Name_Alias"]["5' cloning site"] . "\"><BR>";
													?>
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													<span style="padding-right:60px;" id="main_3_site_caption">3' Cloning Site on Insert:</span>

													<span id="alt_3_site_caption" style="display:none">3' Cloning Site on resulting Vector:</span>
												</TD>

												<TD>
													<?php
														// Modified May 14/08
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" id=\"tpcs\" size=\"10\" style=\"color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $_GET["R2"]  . "\" name=\"" . $_SESSION["ReagentProp_Name_Alias"]["3' cloning site"] . "\"><BR>";
													?>
												</TD>
											</TR>
										</TABLE>

										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							// May 29/08
							case '14':
								// Insert cloning site values don't match sequence at site positions
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>A sequence for the new vector could not be generated from the sequences of its parents.<BR>

										<P>One or both cloning sites does not match an actual enzyme sequence at the following positions on <a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["I"]); ?>" target="blank"><?php echo $_GET["I"]; ?></a>:<BR>

										<P>What would you like to do?<BR>

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values<BR>
										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" checked onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Go back and choose different parent values or vector type<BR>

										<TABLE width="100%" id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD style="width:250px;">
													Parent Vector OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD style="width:180px;">
													Insert OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
										<BR>

										<TABLE width="650px" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD style="width:250px;">
													<span id="main_5_site_caption">5' Cloning Site on Insert:</span>

													<span id="alt_5_site_caption" style="display:none">5' Cloning Site on resulting Vector:</span>
												</TD>

												<TD style="padding-left:10px; white-space:nowrap;">
													<?php
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"15\" id=\"fpcs\" style=\"color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $_GET["R1"]  . "\" name=\"" . $_SESSION["ReagentProp_Name_Alias"]["5' cloning site"] . "\">";

														$pStart = $rfunc_obj->getStartPos($gfunc_obj->get_rid($_GET["I"]), "5' cloning site", $_SESSION["ReagentProp_Name_ID"]["5' cloning site"]);

														echo "Start: <INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" style=\"margin-left:5px; color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $pStart  . "\">";

														$pEnd = $rfunc_obj->getEndPos($gfunc_obj->get_rid($_GET["I"]), "5' cloning site", $_SESSION["ReagentProp_Name_ID"]["5' cloning site"]);

														echo "End:<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" style=\"margin-left:5px; color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $pEnd . "\">";

														echo "<BR>";

														echo "<P id=\"fpcs_comment\" style=\"font-size:7pt; display:none\">Please enter a hyphen-delimited name of the hybrid restriction site (e.g. SalI-XhoI, order: vector_site-insert_site)</P>";
													?>
												</TD>
											</TR>

											<TR>
												<TD  style="width:250px;">
													<span id="main_3_site_caption">3' Cloning Site on Insert:</span>

													<span id="alt_3_site_caption" style="display:none">3' Cloning Site on resulting Vector:</span>
												</TD>

												<TD style="padding-left:10px;">
													<?php
														// Modified May 14/08
														echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" id=\"tpcs\" size=\"15\" style=\"color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $_GET["R2"]  . "\" name=\"" . $_SESSION["ReagentProp_Name_Alias"]["3' cloning site"] . "\">";

														$pStart = $rfunc_obj->getStartPos($gfunc_obj->get_rid($_GET["I"]), "3' cloning site", $_SESSION["ReagentProp_Name_ID"]["3' cloning site"]);

														echo "Start: <INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" style=\"margin-left:5px; color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $pStart  . "\">";

														$pEnd = $rfunc_obj->getEndPos($gfunc_obj->get_rid($_GET["I"]), "3' cloning site", $_SESSION["ReagentProp_Name_ID"]["3' cloning site"]);

														echo "End:<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"5\" style=\"margin-left:5px; color:brown; background-color:#E8E8E8;\" readonly=\"true\" value=\"" . $pEnd . "\">";

														echo "<BR>";

														echo "<P id=\"tpcs_comment\" style=\"font-size:7pt; display:none\">Please enter a hyphen-delimited name of the hybrid restriction site (e.g. SalI-XhoI, order: insert_site-vector_site)</P>";
													?>
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
									</FIELDSET>
								</FORM>
								<?php
							break;

							case '24':
								// Empty parent Vector sequence
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>A sequence for the new vector could not be generated from the sequences of its parents.<BR>

										<P>The sequence of the parent Vector <a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["PV"]); ?>" target="blank"><?php echo $_GET["PV"]; ?></a> is empty.

										<P>What would you like to do?

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values<BR>
										<input type="radio" name="warning_change_input" ID="process_error_restart" value="restart" checked onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Cancel creation and start over<BR>

										<TABLE width="100%" id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD style="width:250px;">
													Parent Vector OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD style="width:180px;">
													Insert OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
										<BR>
										
										<P><INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							// Dec. 14/09
							case '25':
								// Empty parent Insert sequence
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

									<FIELDSET style="width:700px; border:0; padding-left:15px;">
										<P><SPAN style="color:red">There was a problem:</SPAN>
										<P>A sequence for the new vector could not be generated from the sequences of its parents.<BR>

										<P>The sequence of the parent Insert <a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["I"]); ?>" target="blank"><?php echo $_GET["I"]; ?></a> is empty.

										<P>What would you like to do?

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Change parent values<BR>
										<input type="radio" name="warning_change_input" ID="process_error_restart" value="restart" checked onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>'); enableOrDisableSites()">Cancel creation and start over<BR>

										<TABLE width="100%" id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD style="width:250px;">
													Parent Vector OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="nr_pv_id" name="new_nr_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD style="width:180px;">
													Insert OpenFreezer ID:
												</TD>

												<TD style="padding-left:10px;">
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="insert" name="new_insert_id" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["I"]; ?>">
												</TD>
											</TR>
										</TABLE>
										<BR>
										
										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
									</FIELDSET>
								</FORM>
								<?php
							break;

							default:
								return false;
							break;
						}
					break;

// continue code cleanup from here; paste <P><SPAN style="color:red">There was a problem:</SPAN>

					case 'gateway_expression':
						$pv_id_tmp = $gfunc_obj->get_rid($_GET["PV"]);
						$ipv_id_tmp = $gfunc_obj->get_rid($_GET["IPV"]);

						switch ($err_code)
						{
							// April 10/08
							case '2':
								// Insert sites not found on PV sequence.  Here, give the option of making a hybrid:
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
									
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P style="color:red">There was a problem:</P>
										<P>A sequence for the new vector could not be generated. The  <span style="color:#FF0000">att</span> cloning sites could not be found on either the donor or entry vector sequence.<BR>
<!-- 										<P>The <span color="#FF0000">attR</span> restriction site could not be found on the sequence of the Parent Vector you provided:</P> -->

										<P>What would you like to do?</P>

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Change parent values<BR/>

										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" checked>Go back and select another vector type<BR/>
										</P>

										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo strtoupper($_GET["IPV"]); ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
										</P>

										<!-- footer padding -->
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>

									</FIELDSET>
								</FORM>
								<?php
							break;

							case '6':
								// One or both of the Parent IDs entered do not exist
								// Check which one
								$assocType = $_GET["AP"];	// this is an integer, corresponding to APropertyID in Assoc_Prop_Type_tbl
								
								switch ($assocType)
								{
									case '2':	// parent vector
										?>
										<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

											<!-- Pass type and subtype as hidden value to Python code -->
											<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
											<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
											<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

											<!-- Pass current user ID as hidden form field -->
											<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
											<FIELDSET style="width:100%; border:0; padding-left:15px;">
												<P style="color:red">There was a problem:</P>
												<P>The Parent Vector ID you provided does not match an existing reagent in the system.</P>

												<P>What would you like to do?</P>

												<P>
												<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Enter a new Parent Vector ID<BR/>

												<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR/>
												</P>

												<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
													
													<TR>
														<TD nowrap>
															Parent Vector OpenFreezer ID:
														</TD>

														<TD>
															<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
														</TD>

													</TR>
													
													<TR>
														<TD nowrap>
															Insert Parent Vector OpenFreezer ID:
														</TD>

														<TD>
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
														</TD>
													</TR>
												</TABLE>

												<P>
												<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
												</P>
											</FIELDSET>
										</FORM>
										<?php
									break;
									
									case '7':	// parent insert vector
										?>
										<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

											<!-- Pass type and subtype as hidden value to Python code -->
											<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
											<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
											<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

											<!-- Pass current user ID as hidden form field -->
											<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
											<FIELDSET style="width:100%; border:0; padding-left:15px;">
												<P style="color:red;">An error occurred during sequence construction:</P>

												<P style="color:red; font-weight:bold">Invalid Insert Parent Vector ID</P>

												<P>Either <SPAN style="color:#FF0000;"><?php echo strtoupper($_GET["IPV"]); ?></span> does not match an existing reagent in the system or a valid Insert for it could not be found.</P>

												<P>What would you like to do?</P>

												<P>
												<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Enter a new Insert Parent Vector ID<BR/>

												<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR/>
												</P>

												<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
													
													<TR>
														<TD nowrap>
															Parent Vector OpenFreezer ID:
														</TD>

														<TD>
															<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
														</TD>

													</TR>
													
													<TR>
														<TD nowrap>
															Insert Parent Vector OpenFreezer ID:
														</TD>

														<TD>
															<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
														</TD>
													</TR>
												</TABLE>

												<P>
												<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
												</P>
											</FIELDSET>
										</FORM>
										<?php
									break;
									
									default:
									break;	
								}
							break;

							case '10':
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
						
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P style="color:#FF0000;">There was a problem:</P>

										<P style="color:brown;">You are not authorized to use <B><?php echo strtoupper($_GET["PV"]); ?></B> as the parent vector, since <U>you do not have read access to its project</U>.<BR>

										Please contact the project owner to obtain permission or select a different parent vector for your reagent.</P>

										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');" checked>Start over<BR>

										<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableRecombParents();">Change parent values<BR/>

										</P>

										<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo strtoupper($_GET["IPV"]); ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
										</P>

										<!-- footer padding -->
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
									</FIELDSET>
								</FORM>
								<?php

							break;

							case '12':
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
						
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
						
									<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
						
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P style="color:#FF0000;">There was a problem:</P>

										<P style="color:brown;">You are not authorized to use <B><?php echo strtoupper($_GET["IPV"]); ?></B> as the Insert Parent Vector, since <U>you do not have read access to its project</U>.<BR>

										Please contact the project owner to obtain permission or select a different Parent Insert Vector for your reagent.</P>

										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableRecombParents();" checked>Start over<BR/>

										<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents"; onClick="enableOrDisableRecombParents();">Change parent values<BR/>

										</P>

										<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
											</TR>

											<TR>
												<TD nowrap>
													Insert Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
										</P>

										<!-- footer padding -->
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
										<blockquote>&nbsp;</blockquote>
									</FIELDSET>
								</FORM>
								<?php
							break;

							// Oct. 31/08
							case '19':
								// Raised when one of the vector parents provided is not of suitable type 
								// Introduced mainly to prevent errors where recomb. vectors are used as donors - i.e. IPV in recomb. vector creation must have ATypeID 1
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red;">An error occurred during sequence construction:</SPAN>

										<P><a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["IPV"]); ?>"><?php echo strtoupper($_GET["IPV"]); ?></a>&nbsp; cannot be properly recombined to produce a Gateway Expression Vector.  Please verify your Insert Parent Vector input.</SPAN>

										<P>What would you like to do?</P>

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Enter a new Insert Parent Vector ID<BR/>

										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR/>
										</P>

										<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
											
											<TR>
												<TD nowrap>
													Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>

											</TR>
											
											<TR>
												<TD nowrap>
													Insert Parent Vector OpenFreezer ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
										</P>
									</FIELDSET>
								</FORM>
								<?php
							break;

							case '30':
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red;">An error occurred during sequence construction:</SPAN>

										<P>The Gateway Parent Destination Vector provided (<a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["PV"]); ?>"><?php echo strtoupper($_GET["PV"]); ?></a>) does not contain a <span style="color:red;">ccdB</span> gene, which is necessary for successful Gateway cloning.</SPAN>

										<P>What would you like to do?</P>

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Change parent values<BR/>

										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR/>
										</P>

										<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
											
											<TR>
												<TD nowrap>
													Gateway Parent Destination Vector ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>

											</TR>
											
											<TR>
												<TD nowrap>
													Gateway Entry Vector ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
										</P>
									</FIELDSET>
								</FORM>
								<?php
							break;

							case '31':
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:red;">An error occurred during sequence construction:</SPAN>

										<P>The <span style="color:red;">ccdB</span> gene was not found in the sequence of the Gateway Parent Destination Vector (<a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["PV"]); ?>"><?php echo strtoupper($_GET["PV"]); ?></a>) but found in the sequence of the Gateway Entry Clone (<a href="<?php echo $hostname . "Reagent.php?View=6&rid=" . $gfunc_obj->get_rid($_GET["IPV"]); ?>"><?php echo strtoupper($_GET["IPV"]); ?></a>), suggesting the input was reversed.</SPAN>

										<P>What would you like to do?</P>

										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">Change parent values<BR/>

										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>', '<?php echo $subtype; ?>')" checked>Go back and start over<BR/>
										</P>

										<TABLE id="recomb_parents" cellpadding="4px" border="1" frame="box" rules="all">
											
											<TR>
												<TD nowrap>
													Gateway Parent Destination Vector ID:
												</TD>

												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="rec_pv_id" name="rec_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>

											</TR>
											
											<TR>
												<TD nowrap>
													Gateway Entry Vector ID:
												</TD>

												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="ipv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["IPV"]; ?>">
												</TD>
											</TR>
										</TABLE>

										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
										</P>
									</FIELDSET>
								</FORM>
								<?php
							break;

							default:
							break;
						}
					break;

					default:
						return false;
					break;
				}
			break;

			case 'Insert':
				switch ($_GET["Err"])
				{
					case '6':
						// One or all of the Parent IDs entered do not exist
						?>
						<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">

							<!-- Pass type and subtype as hidden value to Python code -->
							<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
							<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
							<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">

							<!-- Pass current user ID as hidden form field -->
							<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
							<FIELDSET style="width:100%; border:0; padding-left:15px;">
								<P><SPAN style="color:#FF0000; font-weight:bold;">Error: Invalid parent ID</SPAN>
								
								<P><SPAN>One or more of the parent IDs provided does not match an existing reagent in the system.  Please verify your parent input.</P>

								<P>What would you like to do?</P>

								<P>
								<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>');">Edit parent values<BR/>

								<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>')" checked>Go back and start over<BR/>
								</P>

								<TABLE id="insert_parents" cellpadding="4px" border="1" frame="box" rules="all">
									
									<TR>
										<TD nowrap>
											Insert Parent Vector OpenFreezer ID:
										</TD>

										<TD>
											<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
											<INPUT TYPE="text" id="piv_id" onKeyPress="return disableEnterKey(event);"  name="insert_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PIV"]; ?>">
										</TD>

									</TR>
									
									<TR>
										<TD nowrap>
											Sense Oligo OpenFreezer ID:
										</TD>

										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="sense_id" name="sense_oligo" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["SO"]; ?>">
										</TD>
									</TR>

										<TR>
										<TD nowrap>
											Antisense Oligo OpenFreezer ID:
										</TD>

										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="antisense_id" name="antisense_oligo" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["AS"]; ?>">
										</TD>
									</TR>
								</TABLE>

								<P>
								<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
								</P>
							</FIELDSET>
						</FORM>
						<?php
					break;

					case '12':
						// No access to IPV's project, can't use for creation
						?>
						<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
				
							<!-- Pass type and subtype as hidden value to Python code -->
							<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
							<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
							<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
				
							<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
							<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
				
							<FIELDSET style="width:100%; border:0; padding-left:15px;">
								<P style="color:#FF0000;">There was a problem:</P>

								<P style="color:brown;">You are not authorized to use <B><?php echo strtoupper($_GET["PIV"]); ?></B> as the Insert Parent Vector, since <U>you do not have read access to its project</U>.<BR>

								Please contact the project owner to obtain permission, or select one of the following options:</P>

								<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('Insert');" checked>Start over<BR/>

								<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('Insert');">Change parent values<BR/>

								</P>

								<TABLE cellpadding="4px" border="1" frame="box" rules="all">
									<TR>
										<TD nowrap>
											Insert Parent Vector OpenFreezer ID:
										</TD>

										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="piv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["PIV"]; ?>">
										</TD>
									</TR>
									
									<TR>
										<TD nowrap>
											Sense Oligo OpenFreezer ID:
										</TD>

										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="sense_id" name="sense_oligo" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["SO"]; ?>">
										</TD>
									</TR>

										<TR>
										<TD nowrap>
											Antisense Oligo OpenFreezer ID:
										</TD>

										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="antisense_id" name="antisense_oligo" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["AS"]; ?>">
										</TD>
									</TR>
								</TABLE>

								<P>
								<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
								</P>
							</FIELDSET>
						</FORM>
						<?php
					break;

					case '20':
						// No access to Sense Oligo's project, can't use for creation
						?>
						<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
				
							<!-- Pass type and subtype as hidden value to Python code -->
							<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
							<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
							<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
				
							<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
							<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
				
							<FIELDSET style="width:100%; border:0; padding-left:15px;">
								<P style="color:#FF0000; font-weight:bold;">There was a problem:</P>

								<P style="color:brown">You are not authorized to use <B><?php echo strtoupper($_GET["SO"]); ?></B> as the Sense Oligo, since <U>you do not have read access to its project</U>.</P>

								<P style="color:brown">Please contact the project owner to obtain permission, or select one of the following options:</P>

								<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('Insert');" checked>Start over<BR/>

								<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('Insert');">Change parent values<BR/>

								</P>

								<TABLE cellpadding="4px" border="1" frame="box" rules="all">
									<TR>
										<TD nowrap>
											Insert Parent Vector OpenFreezer ID:
										</TD>

										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="piv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["PIV"]; ?>">
										</TD>
									</TR>
									
									<TR>
										<TD nowrap>
											Sense Oligo OpenFreezer ID:
										</TD>

										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="sense_id" name="sense_oligo" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["SO"]; ?>">
										</TD>
									</TR>

										<TR>
										<TD nowrap>
											Antisense Oligo OpenFreezer ID:
										</TD>

										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="antisense_id" name="antisense_oligo" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["AS"]; ?>">
										</TD>
									</TR>
								</TABLE>

								<P>
								<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
								</P>
							</FIELDSET>
						</FORM>
						<?php
					break;

					case '21':
						// No access to Sense Oligo's project, can't use for creation
						?>
						<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
				
							<!-- Pass type and subtype as hidden value to Python code -->
							<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
							<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
							<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
				
							<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
							<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
				
							<FIELDSET style="width:100%; border:0; padding-left:15px;">
								<P style="color:#FF0000; font-weight:bold;">There was a problem:</P>

								<P style="color:brown">You are not authorized to use <B><?php echo strtoupper($_GET["AS"]); ?></B> as the Antisense Oligo, since <U>you do not have read access to its project</U>.</P>

								<P style="color:brown">Please contact the project owner to obtain permission, or select one of the following options:</P>

								<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('Insert');" checked>Start over<BR/>

								<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('Insert');">Change parent values<BR/>

								</P>

								<TABLE cellpadding="4px" border="1" frame="box" rules="all">
									<TR>
										<TD nowrap>
											Insert Parent Vector OpenFreezer ID:
										</TD>

										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="piv_id" name="insert_parent_vector" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["PIV"]; ?>">
										</TD>
									</TR>
									
									<TR>
										<TD nowrap>
											Sense Oligo OpenFreezer ID:
										</TD>

										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="sense_id" name="sense_oligo" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["SO"]; ?>">
										</TD>
									</TR>

										<TR>
										<TD nowrap>
											Antisense Oligo OpenFreezer ID:
										</TD>

										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="antisense_id" name="antisense_oligo" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["AS"]; ?>">
										</TD>
									</TR>
								</TABLE>

								<P>
								<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
								</P>
							</FIELDSET>
						</FORM>
						<?php
					break;
				}
			break;

			// New Aug. 27/08
			case 'CellLine':

				$pv_id_tmp = $gfunc_obj->get_rid($_GET["PV"]);
				$cl_id_tmp = $gfunc_obj->get_rid($_GET["CL"]);

				switch ($err_code)
				{
					case '6':
						$assocType = $_GET["AP"];	// this is an integer, corresponding to APropertyID in Assoc_Prop_Type_tbl
								
						switch ($assocType)
						{
							case '7':
								# non-existent parent Cell Line
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">
		
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
		
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:#FF0000; font-weight:bold;">Error: Invalid parent Cell Line ID</SPAN>
										
										<P><SPAN>The ID of the Parent Cell Line provided does not match an existing reagent in the system.  Please verify your parent input.</P>
		
										<P>What would you like to do?</P>
		
										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>');">Edit parent values<BR/>
		
										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>')" checked>Go back and start over<BR/>
										</P>
		
										<TABLE id="cellLine_parents" cellpadding="4px" border="1" frame="box" rules="all">
											
											<TR>
												<TD nowrap>
													Cell Line Parent Vector OpenFreezer ID:
												</TD>
		
												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="clpv_id" name="cell_line_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
		
											</TR>
											
											<TR>
												<TD nowrap>
													Parent Cell Line OpenFreezer ID:
												</TD>
		
												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="pcl_id" name="parent_cell_line" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["CL"]; ?>">
												</TD>
											</TR>
										</TABLE>
		
										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
										</P>
									</FIELDSET>
								</FORM>
								<?php
							break;
							
							case '8':	// parent cell line vector
								# non-existent parent Cell Line
								?>
								<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents('<?php echo $type; ?>', '<?php echo $subtype; ?>');">
		
									<!-- Pass type and subtype as hidden value to Python code -->
									<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
									<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
									<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
		
									<!-- Pass current user ID as hidden form field -->
									<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
									<FIELDSET style="width:100%; border:0; padding-left:15px;">
										<P><SPAN style="color:#FF0000; font-weight:bold;">Error: Invalid parent Vector ID</SPAN>
										
										<P><SPAN>The ID of the Parent Vector provided does not match an existing reagent in the system.  Please verify your parent input.</P>
		
										<P>What would you like to do?</P>
		
										<P>
										<input type="radio" id="change_parents_warning_option" name="warning_change_input" value="change_parents" onClick="enableOrDisableParents('<?php echo $type; ?>');">Edit parent values<BR/>
		
										<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableParents('<?php echo $type; ?>')" checked>Go back and start over<BR/>
										</P>
		
										<TABLE id="cellLine_parents" cellpadding="4px" border="1" frame="box" rules="all">
											
											<TR>
												<TD nowrap>
													Cell Line Parent Vector OpenFreezer ID:
												</TD>
		
												<TD>
													<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="clpv_id" name="cell_line_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
												</TD>
		
											</TR>
											
											<TR>
												<TD nowrap>
													Parent Cell Line OpenFreezer ID:
												</TD>
		
												<TD>
													<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);"  id="pcl_id" name="parent_cell_line" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["CL"]; ?>">
												</TD>
											</TR>
										</TABLE>
		
										<P>
										<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">
										</P>
									</FIELDSET>
								</FORM>
								<?php
							break;
							
							default:
							break;	
						}
					break;

					case '10':
						?>
						<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
				
							<!-- Pass type and subtype as hidden value to Python code -->
							<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
							<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
							<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
				
							<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
							<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
				
							<FIELDSET style="width:100%; border:0; padding-left:15px;">
								<P style="color:#FF0000;">There was a problem:</P>
	
								<P style="color:brown;">You are not authorized to use <B><?php echo strtoupper($_GET["PV"]); ?></B> as the parent vector, since <U>you do not have read access to its project</U>.<BR>
	
								Please contact the project owner to obtain permission or select a different parent vector for your reagent.</P>
	
								<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableCellLineParents();" checked>Start over<BR/>
	
								<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents"; onClick="enableOrDisableCellLineParents();">Change parent values<BR/>
	
								</P>
	
								<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
									<TR>
										<TD nowrap>
											Parent Vector OpenFreezer ID:
										</TD>
	
										<TD>
											<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
											<INPUT TYPE="text" id="cl_pv_id" onKeyPress="return disableEnterKey(event);"  name="cell_line_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
										</TD>
									</TR>
	
									<TR>
										<TD nowrap>
											Cell Line OpenFreezer ID:
										</TD>
	
										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="parent_cell_line_id" name="parent_cell_line" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["CL"]; ?>">
										</TD>
									</TR>
								</TABLE>
	
								<P>
								<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
								</P>
	
								<!-- footer padding -->
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
	
							</FIELDSET>
						</FORM>
						<?php
					break;

					case '13':
						?>
						<FORM method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
				
							<!-- Pass type and subtype as hidden value to Python code -->
							<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $type; ?>">
							<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
							<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
				
							<!-- Pass user info to Python as hidden form value too - Aug 21/07 -->
							<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
				
							<FIELDSET style="width:100%; border:0; padding-left:15px;">
								<P style="color:#FF0000;">There was a problem:</P>
	
								<P style="color:brown;">You are not authorized to use <B><?php echo strtoupper($_GET["CL"]); ?></B> as the parent cell line, since <U>you do not have read access to its project</U>.<BR>
	
								Please contact the project owner to obtain permission or select a different parent vector for your reagent.</P>
	
								<input type="radio" name="warning_change_input" value="restart" ID="process_error_restart" onClick="enableOrDisableCellLineParents();" checked>Start over<BR/>
	
								<input type="radio" id = "change_parents_warning_option" name="warning_change_input" value="change_parents"; onClick="enableOrDisableCellLineParents();">Change parent values<BR/>
	
								</P>
	
								<TABLE id="nonrec_parents" cellpadding="4px" border="1" frame="box" rules="all">
									<TR>
										<TD nowrap>
											Parent Vector OpenFreezer ID:
										</TD>
	
										<TD>
											<!-- DO NOT USE 'disabled'!!! field won't be recognized by CGI -->
											<INPUT TYPE="text" id="cl_pv_id" onKeyPress="return disableEnterKey(event);"  name="cell_line_parent_vector" size="10" style="color:brown" readonly="true" value="<?php echo $_GET["PV"]; ?>">
										</TD>
									</TR>
	
									<TR>
										<TD nowrap>
											Cell Line OpenFreezer ID:
										</TD>
	
										<TD>
											<INPUT TYPE="text" onKeyPress="return disableEnterKey(event);" id="parent_cell_line_id" name="parent_cell_line" size="10" readonly="true" style="color:brown"  value="<?php echo $_GET["CL"]; ?>">
										</TD>
									</TR>
								</TABLE>
	
								<P>
								<INPUT TYPE="SUBMIT" NAME="process_warning" VALUE="Go">&nbsp;&nbsp;&nbsp;
								</P>
	
								<!-- footer padding -->
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
								<blockquote>&nbsp;</blockquote>
	
							</FIELDSET>
						</FORM>
						<?php
					break;
				}
			break;
		}
	}
	

	/**
	 * Print features table upon saving Insert from Primer Design
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT rID
	 * @param INT seqID
	 * @param INT insertParentVector
	 * @param INT sense oligo ID
	 * @param INT antisense oligo ID
	*/
	// May 14/08: Changed the procedure of saving Insert from Primer Design slightly
	function previewInsertFeaturesPrimer($rID, $seqID, $insertParentVector, $sense, $antisense)
	{
		global $hostname;
		global $cgi_path;

		$gfunc_obj = new generalFunc_Class();
		$rprint_obj = new Reagent_Output_Class();

		$outputer_obj = new ColFunctOutputer_Class();		// Nov. 10/08

		// May 5/08: Use a different prefix - make uniform for all reagent types and for dynamic features
		$genPrefix = "reagent_detailedview_";
		$genPostfix = "_prop";

		$currUserName = $_SESSION["userinfo"]->getDescription();

		?>
		<FORM NAME="reagentDetailForm" method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onSubmit="setFeaturePositions(); return verifyPositions(true);">

			<!-- Pass user info to Python as hidden form value - Aug 21/07 -->
			<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
			<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="Insert">
			<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
			<INPUT TYPE="hidden" NAME="seq_id_hidden" VALUE="<?php echo $seqID; ?>">

			<INPUT TYPE="hidden" NAME="from_primer" VALUE="True">

			<table width="780px" cellpadding="4">
				<tr>
					<td style="text-align:center; font-weight:bold; padding-left:150px;">
						<span style="color:#00C00D; font-size:13pt; padding-bottom:18px;">
							INSERT CREATION
							<BR>
						</span>
	
						<span style="color:#00B38D; font-size:10pt;">
							Step 3 of 4: Confirm Features
						</span>
					</td>
					<td style="text-align:right; padding-right:10px;">
						<input type="submit" name="confirm_intro" value="Continue" onClick="document.getElementById('cancel_set').value=0; document.getElementById('navigate_away').value=0;">
						&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">

						<INPUT type="hidden" ID="cancel_set" value="0">
					</td>
				</tr>

<!-- NO, don't do this - will get no added benefit and Oligos just created would be left orphaned (project has not been assigned yet)
				<tr>
					<td colspan="2">
						<a style="font-size:10pt; margin-left:8px;" href="<?php /*echo $hostname . "Reagent.php?View=7&rid=" . $rID;*/ ?>">Back to Primer Design page</a>
					</td>
				</tr>
-->
			</table>

			<?php
				// Updated Nov. 5/08 - 'Back' buttons will have different action on Primer Design
				$this->printParents("Insert", "", true, $insertParentVector, $sense, $antisense, true);
			?>
			<BR>
			<TABLE width="576px" cellpadding="2" style="margin-left:10px;">
				<TR>
					<TD style="font-weight:bold;" colspan="2">
						Sequence:
					</TD>
				</TR>

				<TR>
					<TD colspan="2">
						<div style="width:970px; height:175px; background-color:#D1D1D1; border:1px solid gray; padding:8px; overflow:auto">
							<?php
								// Here can pass $rID as parameter b/c a PropList_tbl entry has already been created
								// The sequence will be printed WITH NUMBERS (Sept. 2/08)
								$outputer_obj->output_sequence($rID);
							?>
						</div>
					</TD>
				</TR>
			</TABLE>
			<?php
			
			$this->printFeatures("Insert", "", $rID, $seqID, "", true);
			?>
		</FORM>
		<?php
	}


	/**
	 * Step 3 of creation, preview features a Vector inherited from parents during sequence construction
	 *
	 * @author Marina Olhovsky
	 * @version 2008-05-01
	 *
	 * @param STRING
	 * @param STRING
	 * @param INT
	 * @param INT
	*/
	function previewReagentFeatures($rType, $subtype, $rID, $seqID)
	{
		global $hostname;
		global $cgi_path;

		$gfunc_obj = new generalFunc_Class();
		$rprint_obj = new Reagent_Output_Class();

		$outputer_obj = new ColFunctOutputer_Class();		// Sept. 4/08

		// May 5/08: Use a different prefix - make uniform for all reagent types and for dynamic features
		$genPrefix = "reagent_detailedview_";
		$genPostfix = "_prop";

		$currUserName = $_SESSION["userinfo"]->getDescription();

		// Numerical db IDs of parents (parent1 is PV while parent2 can be either Insert or IPV; for Inserts there are 3 parents: 2 Oligos and PIV)
		$parent1 = -1;
		$parent2 = -1;
		$parent3 = -1;

		if ($rType == 'Vector')
			$linear = false;
		else
			$linear = true;
		?>
		<FORM NAME="reagentDetailForm" method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onSubmit="setFeaturePositions(); return verifyPositions(<?php echo $linear; ?>);">

			<!-- Pass user info to Python as hidden form value - Aug 21/07 -->
			<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
			<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $rType; ?>">
			<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
			<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
			<INPUT TYPE="hidden" NAME="seq_id_hidden" VALUE="<?php echo $seqID; ?>">
			<?php
				switch ($rType)
				{
					case 'Vector':
						switch($subtype)
						{
							case 'nonrecomb':
							case 'gateway_entry':
								$pvID = $_GET["PV"];
								$insertID = $_GET["I"];
		
								$parent1 = $pvID;
								$parent2 = $insertID;

								# Jan. 21/09: if Insert was reverse complemented, cDNA (and other features??), in order to be mapped correctly, need to be RCd too (only applies to NR vectors)

								if (isset($_GET["Rev"]) && ($_GET["Rev"] == 'True'))
								{
									$rev = true;
									echo "<INPUT TYPE=\"hidden\" NAME=\"reverse_complement\" VALUE=\"" . $rev . "\">";
								}
								?>
									<table width="750px" cellpadding="4">
										<tr>
											<td style="text-align:center; font-weight:bold;">
												<span style="color:#00C00D; font-size:13pt; padding-bottom:18px; margin-left:105px;">
													<?php
														if ($subtype == 'nonrecomb')
															echo "CREATE A NON-RECOMBINATION VECTOR";
														else if ($subtype == 'gateway_entry')
															echo "CREATE A GATEWAY ENTRY VECTOR";
													?>
													<BR>
												</span>
							
												<span style="color:#00B38D; font-size:10pt; margin-left:105px;">
													Step 3 of 4: Confirm Features
												</span>
											</td>

											<!-- nov. 5/08 -->
											<TD style="text-align:right; padding-right:15px;">
												<input type="submit" name="confirm_intro" value="Continue" onClick="document.getElementById('cancel_set').value=0; enableSites(); changeFieldNames('reagentDetailForm', null, '<?php echo $rType; ?>'); setFeaturePositions(); verifyPositions(); document.getElementById('navigate_away').value=0;">
						
												&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">
						
												<INPUT type="hidden" ID="cancel_set" value="0">
											</TD>
										</tr>
									</table>

									<!-- May 5/08: Add parents to form -->
									<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["vector parent id"] . $genPostfix; ?>" VALUE="<?php echo strtoupper($gfunc_obj->getConvertedID_rid($parent1)); ?>">
						
									<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["insert id"] . $genPostfix; ?>" VALUE="<?php echo strtoupper($gfunc_obj->getConvertedID_rid($parent2)); ?>">
								<?php
		
								$this->printParents($rType, $subtype, true, $parent1, $parent2);
							break;
		
							case 'recomb':
							case 'gateway_expression':
								$pvID = $_GET["PV"];
								$ipvID = $_GET["IPV"];
		
								$parent1 = $pvID;
								$parent2 = $ipvID;
		
								?>
									<table width="100%" cellpadding="4">
										<tr>
											<td colspan="2" style="text-align:center; font-weight:bold;">
												<span style="color:#00C00D; font-size:13pt; white-space:nowrap; padding-bottom:18px;">
													<?php
														if ($subtype == 'recomb')
															echo "CREATE AN EXPRESSION VECTOR";
														else if ($subtype == 'gateway_expression')
															echo "CREATE A GATEWAY EXPRESSION VECTOR";
													?>
													<BR>
												</span>
							
												<span style="color:#00B38D; font-size:10pt; white-space:nowrap;">
													Step 3 of 4: Confirm Features
												</span>
											</td>

											<!-- nov. 5/08 -->
											<TD style="text-align:right; padding-right:15px;">
												<input type="submit" name="confirm_intro" value="Continue" onClick="document.getElementById('cancel_set').value=0; enableSites(); changeFieldNames('reagentDetailForm', null, '<?php echo $rType; ?>'); setFeaturePositions(); verifyPositions(); document.getElementById('navigate_away').value=0;">
						
												&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">
						
												<INPUT type="hidden" ID="cancel_set" value="0">
											</TD>
										</tr>
									</table>
		
									<!-- May 5/08: Add parents to form -->
									<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["vector parent id"] . $genPostfix; ?>" VALUE="<?php echo strtoupper($gfunc_obj->getConvertedID_rid($parent1)); ?>">
						
									<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["parent insert vector"] . $genPostfix; ?>" VALUE="<?php echo strtoupper($gfunc_obj->getConvertedID_rid($parent2)); ?>">

									<P>
								<?php
		
								$this->printParents($rType, $subtype, true, $parent1, $parent2);
							break;
		
							case 'novel':
								?>
									<table width="100%" cellpadding="4">
										<tr>
											<td colspan="2" style="text-align:center; font-weight:bold;">
												<span style="color:#00C00D; font-size:13pt; margin-left:150px; padding-bottom:18px;">
													CREATE A NOVEL (PARENT) VECTOR
													<BR>
												</span>
							
												<span style="color:#00B38D; font-size:10pt; margin-left:150px;">
													Step 2 of 3: Confirm Features
												</span>
											</td>

											<!-- nov. 5/08 -->
											<TD style="text-align:right; padding-right:15px;">
												<input type="submit" name="confirm_intro" value="Continue" onClick="document.getElementById('cancel_set').value=0; enableSites(); changeFieldNames('reagentDetailForm', null, '<?php echo $rType; ?>'); setFeaturePositions(); verifyPositions(); document.getElementById('navigate_away').value=0;">
						
												&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">
						
												<INPUT type="hidden" ID="cancel_set" value="0">
											</TD>
										</tr>
									</table>
								<?php
							break;
						}
					break;

					case 'Insert':
						$pivID = $_GET["PIV"];
						$parent1 = $pivID;
						
						$senseID = $_GET["SO"];
						$parent2 = $senseID;
						
						$antisenseID = $_GET["AS"];
						$parent3 = $antisenseID;
						?>
							<table width="750px" cellpadding="2">
								<tr>
									<td colspan="2" style="text-align:center; font-weight:bold; padding-left:225px;">
										<span style="color:#00C00D; font-size:13pt; padding-bottom:18px;">
											CREATE AN INSERT
											<BR>
										</span>
					
										<span style="color:#00B38D; font-size:10pt;">
											Step 3 of 4: Confirm Features
										</span>
									</td>

									<!-- nov. 5/08 -->
									<TD style="text-align:right; padding-right:15px;">
										<input type="submit" name="confirm_intro" value="Continue" onClick="document.getElementById('cancel_set').value=0; enableSites(); changeFieldNames('reagentDetailForm', 'modifyReagentPropsTbl',  'Insert'); setFeaturePositions(); verifyPositions(); document.getElementById('navigate_away').value=0;">
				
										&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">
				
										<INPUT type="hidden" ID="cancel_set" value="0">
									</TD>
								</tr>
							</table>
						<?php

						$this->printParents($rType, $subtype, true, $parent1, $parent2, $parent3);
						?>
							<!-- May 5/08: Add parents to form -->
							<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["insert parent vector id"] . $genPostfix; ?>" VALUE="<?php echo strtoupper($parent1); ?>">
				
							<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["sense oligo"] . $genPostfix; ?>" VALUE="<?php echo strtoupper($parent2); ?>">
	
							<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["antisense oligo"] . $genPostfix; ?>" VALUE="<?php echo strtoupper($parent3); ?>">
						<?php
					break;
				}
			?>
			<BR>
			<TABLE width="750px" cellpadding="2" style="margin-left:10px;">
				<TR>
					<TD style="font-weight:bold; font-size:10pt; padding-top:10px;" colspan="2">
						Sequence:
						<?php
							switch ($rType)
							{
								case 'Vector':
									switch($subtype)
									{
										case 'nonrecomb':
// May 20, 2010: why is this commented out???
// 										case 'gateway_entry':

// adding back May 20, 2010, see if causes problems:
										case 'gateway_entry':
											if (isset($_GET["R1"]))
											{
												if (isset($_GET["R2"]))
												{
													?><a style="margin-left:5px; font-size:9pt; font-weight:normal;" href="<?php echo $hostname . "Reagent.php?View=2&Step=1&Type=" . $rType . "&Sub=" . $subtype . "&rID=" . $rID . "&PV=" . $pvID . "&I=" . $insertID . "&Seq=" . $seqID . "&R1=" . $_GET["R1"] . "&R2=" . $_GET["R2"] . "&Rev=" . $_GET["Rev"]; ?>">Go back and edit sequence</a><P><?php
												}
												else
												{
													?><a style="margin-left:10px; font-size:9pt; font-weight:normal;" href="<?php echo $hostname . "Reagent.php?View=2&Step=1&Type=" . $rType . "&Sub=" . $subtype . "&rID=" . $rID . "&PV=" . $pvID . "&I=" . $insertID . "&Seq=" . $seqID . "&R1=" . $_GET["R1"] . "&Rev=" . $_GET["Rev"]; ?>">Go back and edit sequence</a><P><?php
												}
											}
											else if (isset($_GET["R2"]))
											{
												// not checking for R1, clearly not set
												?><a style="margin-left:10px; font-size:9pt; font-weight:normal;" onclick="document.getElementById('navigate_away').value=0;" href="<?php echo $hostname . "Reagent.php?View=2&Step=1&Type=" . $rType . "&Sub=" . $subtype . "&rID=" . $rID . "&PV=" . $pvID . "&I=" . $insertID . "&Seq=" . $seqID . "&R2=" . $_GET["R2"] . "&Rev=" . $_GET["Rev"]; ?>">Go back and edit sequence</a><P><?php
											}
											else
											{
												?><a style="margin-left:10px; font-size:9pt; font-weight:normal;" onclick="document.getElementById('navigate_away').value=0;" href="<?php echo $hostname . "Reagent.php?View=2&Step=1&Type=" . $rType . "&Sub=" . $subtype . "&rID=" . $rID . "&PV=" . $pvID . "&I=" . $insertID . "&Seq=" . $seqID . "&Rev=" . $_GET["Rev"]; ?>">Go back and edit sequence</a><P><?php
											}
										break;

										// Moved here Nov. 20/08 - sites are passed through GET variable for NON-RECOMBINATION vectors ONLY
										case 'gateway_entry':
											?><a style="margin-left:10px; font-size:9pt; font-weight:normal;" onclick="document.getElementById('navigate_away').value=0;" href="<?php echo $hostname . "Reagent.php?View=2&Step=1&Type=" . $rType . "&Sub=" . $subtype . "&rID=" . $rID . "&PV=" . $pvID . "&I=" . $insertID . "&Seq=" . $seqID; ?>">Go back and edit sequence</a><P><?php
										break;

										case 'recomb':
										case 'gateway_expression':
										?>
											<a style="margin-left:10px; font-size:9pt; font-weight:normal;" onclick="document.getElementById('navigate_away').value=0;" href="<?php echo $hostname . "Reagent.php?View=2&Step=1&Type=" . $rType . "&Sub=" . $subtype . "&rID=" . $rID . "&PV=" . $pvID . "&IPV=" . $ipvID . "&Seq=" . $seqID; ?>">Go back and edit sequence</a><P>
										<?php
										break;

										// oct 28/08
										case 'novel':
										?>
											<a style="margin-left:10px; font-size:9pt; font-weight:normal;" onclick="document.getElementById('navigate_away').value=0;" href="<?php echo $hostname . "Reagent.php?View=2&Step=1&Type=" . $rType . "&Sub=" . $subtype . "&rID=" . $rID . "&Seq=" . $seqID; ?>">Go back and edit sequence</a><P>
										<?php
										break;
									}
								break;

								case 'Insert':
								?>
									<a style="margin-left:10px; font-size:9pt; font-weight:normal;" onclick="document.getElementById('navigate_away').value=0;" href="<?php echo $hostname . "Reagent.php?View=2&Step=1&Type=Insert&rID=" . $rID . "&Seq=" . $seqID; ?>">Go back and edit sequence</a><P>
								<?php
								break;
							}
					?>
					</TD>
				</TR>

				<TR>
					<TD colspan="2">
						<div style="width:700px; height:165px; background-color:#F9F9F9; border:1px solid gray; padding:8px; overflow:auto">
							<?php
								// Sept. 2/08: Print sequence WITH NUMBERS
								$outputer_obj->output_sequence($rID);
							?>
						</div>
					</TD>
				</TR>
			</TABLE>
			<BR>
			<TABLE width="750px" cellpadding="2" style="margin-left:10px;">
				<TH style="text-align:left">Features:</TH>

				<TR>
					<TD colspan="2">
						<?php
							$this->printFeatures($rType, $subtype, $rID, $seqID);
						?>
					</TD>
				</TR>
			</TABLE>
		</FORM>
		<?php
	}


	/**
	 * Separate function for common HTML output - print sequence features as a table.
	 * Modified May 15/09: Make actual props table ID a parameter too, since this function is used in many different places and tables have different names.
	 *
	 * @param STRING
	 * @param STRING
	 * @param INT
	 * @param INT
	 *
	 * @author Marina Olhovsky
	 * @version 2008-05-23
	*/
	function outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, $featureName, $feature, $readonly=false, $from_primer=false, $modify=false, $propsTbl_id="modifyReagentPropsTbl", $isProtein=false, $isRNA=false)
	{
		$rprint_obj = new Reagent_Output_Class();
		$rfunc_obj = new Reagent_Function_Class();

		if ($feature)
		{
			$featureValue = $feature->getFeatureValue();
			$fStart = $feature->getFeatureStart();
			$fEnd = $feature->getFeatureEnd();
			$fDir = $feature->getFeatureDirection();
			$fDescr = $feature->getFeatureDescriptor();
		}
		else
		{
			// show empty fields
			$featureValue = "";
			$fStart = 0;
			$fEnd = 0;
			$fDir = "forward";
			$fDescr = "";
		}

		if (!$isProtein && !$isRNA)
			$featureID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$featureName], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]);
		else if ($isRNA)
			$featureID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$featureName], $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence Features"]);
		else
			$featureID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$featureName], $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence Features"]);

		$fAlias = $_SESSION["ReagentProp_Name_Alias"][$featureName];

		// Oct. 26/09: Differentiate between creation and modification views
		if ($rID <= 0)
			$tblID = $rType . "_" . $fAlias;
		else
			$tblID = $fAlias;

		?>
		<!-- Updated Oct. 22/08: give unique row IDs for identical features -->
		<TR ID="<?php echo $tblID; ?>_:_row_<?php echo $featureValue; ?>_start_<?php echo $fStart; ?>_end_<?php echo $fEnd; ?>" style="background-color:#F5F5DC;">
			<TD style="padding-left:7px; white-space:nowrap; font-size:7pt; padding-right:10px;">
				<?php
					echo $_SESSION["ReagentProp_Name_Desc"][$featureName];
					
					if ((strcasecmp($subtype, "novel") == 0) && (strcasecmp($featureName, "5' cloning site") == 0))
					{
						echo "<INPUT TYPE=\"checkbox\" ID=\"five_prime_site_uknown\" NAME=\"no_five_prime_site\" style=\"margin-left:10px; font-weight:bold\">N/A";
					}
					else if ((strcasecmp($subtype, "novel") == 0) && (strcasecmp($featureName, "3' cloning site") == 0))
					{
						echo "<INPUT TYPE=\"checkbox\" ID=\"three_prime_site_uknown\" NAME=\"no_three_prime_site\" style=\"margin-left:10px; font-weight:bold\">N/A";
					}
				?>
			</TD>

			<TD style="padding-left:7px; white-space:nowrap; font-size:7pt; padding-bottom:1px; padding-top:1px;">
				<?php
					if (strcasecmp($featureName, 'cdna insert') == 0)
					{
						echo "If cDNA positions are unknown, select <INPUT TYPE=\"checkbox\" ID=\"cdna_unknown\" NAME=\"no_cdna\" style=\"margin-left:10px; font-weight:bold\">N/A";
					}
					else
					{
						if ((strcasecmp($featureName, "5' cloning site") != 0) && (strcasecmp($featureName, "3' cloning site") != 0) && (strcasecmp($featureName, "5' linker") != 0) && (strcasecmp($featureName, "3' linker") != 0))
						{
							if ($isProtein)
								echo $rprint_obj->print_property_final($genPrefix . $fAlias . "_:_" . $featureValue . "_start_" . $fStart . "_end_" . $fEnd . $genPostfix, $featureName, $featureValue, $rID, true, "Protein Sequence Features", "", "", $rType, $readonly, $fDescr, $fStart, $fEnd);
							else if ($isRNA)
								echo $rprint_obj->print_property_final($genPrefix . $fAlias . "_:_" . $featureValue . "_start_" . $fStart . "_end_" . $fEnd . $genPostfix, $featureName, $featureValue, $rID, true, "RNA Sequence Features", "", "", $rType, $readonly, $fDescr, $fStart, $fEnd);
							else
								echo $rprint_obj->print_property_final($genPrefix . $fAlias . "_:_" . $featureValue . "_start_" . $fStart . "_end_" . $fEnd . $genPostfix, $featureName, $featureValue, $rID, true, "DNA Sequence Features", "", "", $rType, $readonly, $fDescr, $fStart, $fEnd);
						}
						else
						{
							if ($from_primer || $modify)
								$readonly = false;

							// DNA OK here, only DNA sequences have cloning sites
							if ($rType && $rID <= 0)
								echo $rprint_obj->print_property_final($genPrefix . $rType . "_" . $fAlias . $genPostfix, $featureName, $featureValue, $rID, true, "DNA Sequence Features", "", "", $rType, $readonly);
							else
								echo $rprint_obj->print_property_final($genPrefix . $fAlias . $genPostfix, $featureName, $featureValue, $rID, true, "DNA Sequence Features", "", "", $rType, $readonly);

							if (isset($_GET["ErrCode"]) && ($_GET["ErrCode"] == 1))
							{
								if (isset($_GET["R1"]) && (strcasecmp($featureName, "5' cloning site") == 0) && (strcasecmp($featureValue, $_GET["R1"]) == 0))
									echo "<BR><SPAN style=\"color:#FF0000; font-size:7pt;\">Site not found on " . $rType . " sequence at the specified positions.  Please verify your input.</span>";
							
								if (isset($_GET["R2"]) && (strcasecmp($featureName, "3' cloning site") == 0) && (strcasecmp($featureValue, $_GET["R2"]) == 0))
									echo "<BR><SPAN style=\"color:#FF0000; font-size:7pt;\">Site not found on " . $rType . " sequence at the specified positions.  Please verify your input.</span>";
							}
						}

/* put back when fix alignment, looks terrible otherwise
						if (strcasecmp($featureName, "5' cloning site") == 0)
						{
							?><BR><div id="fp_warning" style="display:none; color:#FF0000">Please select a value for the 5' restriction site from the dropdown list</div><?php
						}
						else if (strcasecmp($featureName, "3' cloning site") == 0)
						{
							?><div id="tp_warning" style="display:none; color:#FF0000">Please select a value for the 3' restriction site from the dropdown list</div><?php
						}*/
					}
				?>
			</TD>

			<TD style="padding-left:7px; font-size:7pt; white-space:nowrap;">
				<?php
					echo $rprint_obj->print_feature_descriptor($genPrefix, $genPostfix, $featureName, $fDescr, $featureValue, $fStart, $fEnd, $rID, true, "", $subtype, $rType, false);
				?>
			</TD>

			<TD style="text-align:left; font-size:7pt; padding-right:2px; padding-left:7px; white-space:nowrap;">
				<?php
					// Start position - posType 1 means start
					if ((strcasecmp($featureName, "5' cloning site") != 0) && (strcasecmp($featureName, "3' cloning site") != 0) && (strcasecmp($featureName, 'cdna insert') != 0) && (strcasecmp($featureName, "5' linker") != 0) && (strcasecmp($featureName, "3' linker") != 0))
					{
						// Oct. 26/09
						if ($rID <= 0)
							$startFieldName = $genPrefix . $rType . "_" . $fAlias . "_:_" . $featureValue . "_start_" . $fStart . "_end_" . $fEnd . "_startpos" . $genPostfix;
						else
							$startFieldName = $genPrefix . $fAlias . "_:_" . $featureValue . "_start_" . $fStart . "_end_" . $fEnd . "_startpos" . $genPostfix;

						$readonly = false;
					}
					else
					{
						// Jan. 9, 2012, Marina: Enable site positions on Novel Vector
						if (strcasecmp($subtype, "novel") == 0)
						{
							$readonly = false;
						}
						else
						{		
							if ((strcasecmp($featureName, 'cdna insert') != 0) && (strcasecmp($featureName, "5' linker") != 0) && (strcasecmp($featureName, "3' linker") != 0))
							{
								// Nov. 6/08
								if ($from_primer || $modify)
									$readonly = false;
								else
									$readonly = true;
							}
							else
								$readonly = false;
						}
						
						// Oct. 26/09
						if ($rID <= 0)
							$startFieldName = $genPrefix . $rType . "_" . $fAlias . "_startpos" . $genPostfix;
						else
							$startFieldName = $genPrefix . $fAlias . "_startpos" . $genPostfix;

					}
				
					$rprint_obj->print_feature_position($startFieldName, $fAlias, 1, $featureValue, $fStart, $readonly);
				?>
			</TD>

			<TD style="text-align:left; padding-right:2px; font-size:7pt; padding-left:7px; white-space:nowrap;">
				<?php
					// End position - posType 0 means end
					if ((strcasecmp($featureName, "5' cloning site") != 0) && (strcasecmp($featureName, "3' cloning site") != 0) && (strcasecmp($featureName, 'cdna insert') != 0) && (strcasecmp($featureName, "5' linker") != 0) && (strcasecmp($featureName, "3' linker") != 0))
					{
						// Oct. 26/09
						if ($rID <= 0)
							$endFieldName = $genPrefix . $rType . "_" . $fAlias . "_:_" . $featureValue . "_start_" . $fStart . "_end_" . $fEnd . "_endpos" . $genPostfix;
						else
							$endFieldName = $genPrefix . $fAlias . "_:_" . $featureValue . "_start_" . $fStart . "_end_" . $fEnd . "_endpos" . $genPostfix;

						$readonly = false;
					}
					else
					{
						// Jan. 9, 2012, Marina: Enable site positions on Novel Vector
						if (strcasecmp($subtype, "novel") == 0)
						{
							$readonly = false;
						}
						else
						{
							if (strcasecmp($featureName, 'cdna insert') != 0 && (strcasecmp($featureName, "5' linker") != 0) && (strcasecmp($featureName, "3' linker") != 0))
							{
								// Nov. 6/08
								if ($from_primer || $modify)
									$readonly = false;
								else
									$readonly = true;
							}
							else
								$readonly = false;
						}

						// Oct. 26/09
						if ($rID <= 0)
							$endFieldName = $genPrefix . $rType . "_" . $fAlias . "_endpos" . $genPostfix;
						else
							$endFieldName = $genPrefix . $fAlias . "_endpos" . $genPostfix;

					}

					$rprint_obj->print_feature_position($endFieldName, $fAlias, 0, $featureValue, $fEnd, $readonly);
				?>
			</TD>

			<TD style="text-align:left; padding-left:5px; padding-right:5px; font-size:7pt; white-space:nowrap;">
				<?php
					// June 6/08: Direction not needed for cDNA feature
					// Nov. 3/08: NEEDED IF INSERT IS REVERSE COMPLEMENTED!!!!!!!!!!!!!
					if ((strcasecmp($featureName, "5' cloning site") != 0) && (strcasecmp($featureName, "3' cloning site") != 0) && (strcasecmp($featureName, "5' linker") != 0) && (strcasecmp($featureName, "3' linker") != 0) && (strcasecmp($featureName, "cdna insert") != 0))
					{
						// Oct. 26/09
						if ($rID <= 0)
							$dirFieldName = $genPrefix . $rType . "_" . $fAlias . "_:_" . $featureValue . "_start_" . $fStart . "_end_" . $fEnd . "_orientation" . $genPostfix;
						else
							$dirFieldName = $genPrefix . $fAlias . "_:_" . $featureValue . "_start_" . $fStart . "_end_" . $fEnd . "_orientation" . $genPostfix;
					}
					else
					{
						// Oct. 26/09
						if ($rID <= 0)
							$dirFieldName = $genPrefix . $rType . "_" . $fAlias . "_orientation" . $genPostfix;
						else
							$dirFieldName = $genPrefix . $fAlias . "_orientation" . $genPostfix;

					}
					
					$rprint_obj->print_feature_direction($dirFieldName, $fAlias, $featureValue, $fDir);
				?>
			</TD>
			
			<TD style="background-color:#FFFFFF;">
			<?php
				// Oct. 26/09
				if ($rID <= 0)
					$row_name = $rType . "_" . $fAlias;
				else
					$row_name = $fAlias;

				if ((strcasecmp($featureName, "cdna insert") != 0) && (strcasecmp($featureName, "5' cloning site") != 0) && (strcasecmp($featureName, "3' cloning site") != 0))
				{
					?><SPAN class="linkShow" style="font-size:7pt; margin-left:5px; font-weight:normal;" onClick="deleteTableRow('<?php echo $propsTbl_id; ?>', '<?php echo $row_name; ?>_:_row_<?php echo addslashes($featureValue); ?>_start_<?php echo $fStart; ?>_end_<?php echo $fEnd; ?>');">Remove</SPAN><?php
				}

				else
				{
					// cDNA and cloning sites - non-mandatory for reagents other than Vector and Insert
					if (($rType == "Vector") || ($rType == "Insert"))
					{
						?><SPAN class="linkDisabled" style="font-size:7pt; margin-left:5px; font-weight:normal;">Remove</SPAN><?php
					}
					else
					{
						?><SPAN class="linkShow" style="font-size:7pt; margin-left:5px; font-weight:normal;" onClick="deleteTableRow('<?php echo $propsTbl_id; ?>', '<?php echo $row_name; ?>_:_row_<?php echo addslashes($featureValue); ?>_start_<?php echo $fStart; ?>_end_<?php echo $fEnd; ?>');">Remove</SPAN><?php
					}
				}
			?>
			</td>
		</TR>
		<?php
	}


	// May 1/08: Select features from database and print them out
	// Nov. 6/08: Added $modify parameter to differentiate between creation and modification views.  On modification sites should not be disabled
	/**
	 * Select features from database and print them out.
	 *
	 * This is a special output function for sequence features that relies heavily on Javascript.  User selects feature names from a dropdown list, and they are appended dynamically to the output table (new table rows are generated using Javascript and assigned separate identifiers, so that features of the same type having different values and positions can be saved correctly).
	 *
	 * Nov. 6/08: Added $modify parameter to differentiate between creation and modification views.  On modification sites should not be disabled.
	 *
	 * @param STRING
	 * @param STRING
	 * @param INT
	 * @param INT
	 *
	 * @author Marina Olhovsky
	 * @version 2008-05-23
	 *
	*/
	function printFeatures($rType, $subtype, $rID=-1, $seqID, $form_name="", $from_primer=false, $modify=false, $propsTbl_id="modifyReagentPropsTbl")
	{
		global $hostname;
		global $cgi_path;
		global $conn;

		$gfunc_obj = new generalFunc_Class();
		$rfunc_obj = new Reagent_Function_Class();
		$rprint_obj = new Reagent_Output_Class();

		$genPrefix = "reagent_detailedview_";
		$genPostfix = "_prop";

		$currUserName = $_SESSION["userinfo"]->getDescription();

		// Numerical db IDs of parents (parent1 is PV while parent2 can be either Insert or IPV)
		$parent1 = -1;
		$parent2 = "";

		$isProtein = $rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$rType], "protein sequence", $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]);

		$isRNA = $rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$rType], "rna sequence", $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]);

		// First output existing features and then give option list to select more
		?>
			<span style="font-size:10pt; color:#FF0000; font-weight:bold;">
				Fields marked with a red asterisk (*) are mandatory
			</span>
		
			<!-- Crucial: set the ID of the table - otherwise JS won't add features -->
			<!-- July 22/09: Replaced modifyReagentPropsTbl with $table_id -->
			<TABLE ID="<?php echo $propsTbl_id; ?>" style="width:700px; margin-top:4px; border:1px groove black; background-color:#FFFFFF;" cellpadding="2px" cellspacing="4px">
				<TR style="background-color:#C1FFC1;">
					<TD style="text-align:center; font-weight:bold; text-decoration:underline;">Name</TD>
					<TD style="text-align:center; font-weight:bold; text-decoration:underline;">Value</TD>
					<TD style="text-align:center; font-weight:bold; text-decoration:underline;">Descriptor</TD>
					<TD style="text-align:center; font-weight:bold; text-decoration:underline; padding-right:10px;">Start</TD>
					<TD style="text-align:center; font-weight:bold; text-decoration:underline; padding-right:10px;">End</TD>
					<TD style="text-align:center; font-weight:bold; text-decoration:underline;">Orientation</TD>
					<TD style="background-color:#FFFFFF; width:25px;"></TD>
				<TR/>
				<?php

				$v_features = $rfunc_obj->getReagentFeatures($rType, $subtype);
				$vfSet = "";
			
				foreach ($v_features as $fID => $fName)
				{
					if (strlen($vfSet) == 0)
						$vfSet .= $fID;
					else
						$vfSet .= "," . $fID;
				}

				// May 23/08: Display features in specific order (sites together, linkers together, cDNA separately, rest sorted AB-ly)
				$cloning_sites = array();
				$linkers = array();
				$cdnaInsert = new SeqFeature();
		
				if ($rID && $rID > 0)
				{
					$query = "SELECT propertyID, propertyValue, descriptor, startPos, endPos, direction FROM ReagentPropList_tbl WHERE reagentID=" . $rID . " AND status='ACTIVE' AND propertyID IN (" . $vfSet . ")";
	
					$feature_rs = mysql_query($query, $conn) or die("Could not select reagent features: " . mysql_error());
				}
				else
				{
					$feature_rs = $v_features;
				}
	
				while ($features = mysql_fetch_array($feature_rs, MYSQL_ASSOC))
				{
					$featureID = $features["propertyID"];
					$featureValue = $features["propertyValue"];
					$fDescr = $features["descriptor"];
					$fStart = $features["startPos"];
					$fEnd = $features["endPos"];
					$fDir = $features["direction"];
	
					$featureName = $_SESSION["ReagentProp_ID_Name"][$rfunc_obj->findPropertyInCategoryID($featureID)];
	
					// May 23/08: Use Feature object for sorting
					$fTemp = new SeqFeature($featureName, $featureValue, $fStart, $fEnd, $fDir, $fDescr);
	
					// Group features to display in a certain order
					if (strcasecmp($featureName, "5' cloning site") == 0)
					{
						// Cloning sites - Updated Nov. 18/08: Assign $_GET values if set FOR NON-RECOMBINATION VECTORS ONLY!!!!!!!!!!!!!!!!!
						if (strcasecmp($subtype, 'nonrecomb') == 0)
						{
							if (isset($_GET["R1"]))
							{
								$fpcs = $_GET["R1"];
								$fpStart = $_GET["FPS"];
								$fpEnd = $_GET["FPE"];
								$fTemp = new SeqFeature("5' cloning site", $fpcs, $fpStart, $fpEnd);
								$cloning_sites["5' cloning site"] = $fTemp;
							}
		
							else
								$cloning_sites["5' cloning site"] = $fTemp;
						}
						else
							$cloning_sites["5' cloning site"] = $fTemp;
					}
					else if (strcasecmp($featureName, "3' cloning site") == 0)
					{
						if (strcasecmp($subtype, 'nonrecomb') == 0)
						{
							if (isset($_GET["R2"]))
							{
								$tpcs = $_GET["R2"];
								$tpStart = $_GET["TPS"];
								$tpEnd = $_GET["TPE"];
								$fTemp = new SeqFeature("3' cloning site", $tpcs, $tpStart, $tpEnd);
								$cloning_sites["3' cloning site"] = $fTemp;
							}
							else
								$cloning_sites["3' cloning site"] = $fTemp;
						}
						else
							$cloning_sites["3' cloning site"] = $fTemp;
					}
					else if (strcasecmp($featureName, "5' linker") == 0)
						$linkers["5' linker"] = $fTemp;
	
					else if (strcasecmp($featureName, "3' linker") == 0)
						$linkers["3' linker"] = $fTemp;
					
					else if (strcasecmp($featureName, "cdna insert") == 0)
						$cdnaInsert = $fTemp;
					
					else
						$tmp_features[$featureName][] = $fTemp;		// Here, use an array, b/c the printFeatures function from ColFuncOutputer_Class that takes care of multiple values is not called, so we're responsible for selecting and printing multiple feature values
				}
			
				// Change April 22, 2010: Don't sort features by name, sort by order in which they are assigned to this reagent type - same code as in OutputClass (see below)
	
				// April 21, 2010: SORT properties in order assigned to this reagent type
				$tmp_order_list = Array();
				$reagentType = $_SESSION["ReagentType_Name_ID"][$rType];
			
				foreach ($tmp_features as $fName => $feature)
				{
					$fID = $_SESSION["ReagentProp_Name_ID"][$fName];
	
					if ($isProtein)
						$fCatID = $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence Features"];
					else if($isRNA)
						$fCatID = $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence Features"];
					else
						$fCatID = $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"];
	
					$fPropCatID = $rfunc_obj->getPropertyIDInCategory($fID, $fCatID);
	
					$fOrd = $rfunc_obj->getReagentTypePropertyOrdering($reagentType, $fPropCatID);
					$tmp_order_list[$fPropCatID] = $fOrd;
				}
	
				// April 22, 2010: Print properties in their assigned order
				$psorted = Array();
				
				sort(array_values($tmp_order_list));
	
				foreach ($tmp_order_list as $pcID => $pOrd)
				{
						$tmp_sorted = Array();
						$tmp_sorted = $psorted[$pOrd];
						$tmp_sorted[] = $pcID;
						$psorted[$pOrd] = $tmp_sorted;
				}
	
				// First print cDNA positions, followed by sites, linkers, and the rest	
				if ($rType == 'Vector')
				{
					if ($subtype != 'novel')
					{
						$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, "5' cloning site", $cloning_sites["5' cloning site"], true, $from_primer, $modify, $propsTbl_id);
						$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, "3' cloning site", $cloning_sites["3' cloning site"], true, $from_primer, $modify, $propsTbl_id);
		
						$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, "cdna insert", $cdnaInsert, false, $from_primer, $modify, $propsTbl_id);
		
						$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, "5' linker", $linkers["5' linker"], false, $from_primer, $modify, $propsTbl_id);
						$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, "3' linker", $linkers["3' linker"], false, $from_primer, $modify, $propsTbl_id);
		
						foreach ($tmp_features as $f => $features)
						{
							//sort($features, SORT_STRING);
		
							foreach ($features as $fKey => $feature)
							{
								$featureName = $feature->getFeatureType();
	
								// actual output value (takes care of case issues)
								$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, $featureName, $feature, false, $from_primer, $modify, $propsTbl_id);
							}
						}
					}
					else
					{
						// May 22/08: Give option to select cloning sites for Novel Vectors
						// output an empty enzyme selection list for 5' site
						$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, "5' cloning site", $cloning_sites["5' cloning site"], false, $from_primer, $modify, $propsTbl_id);
		
						$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, "3' cloning site", $cloning_sites["3' cloning site"], false, $from_primer, $modify, $propsTbl_id);
		
						// row separator
						echo "<TR><TD colspan=\"6\"><TD></TR>";
		
						// rest of the features - modified April 22, 2010 to print features in assigned order
						for ($i = 1; $i <= max(array_keys($psorted)); $i++)
						{
							$pList = $psorted[$i];
			
							foreach ($pList as $ind => $propCatID)
							{
								$fID = $rfunc_obj->findPropertyInCategoryID($propCatID);
								$fName =  $_SESSION["ReagentProp_ID_Name"][$fID];
								$features = $tmp_features[$fName];
		
								foreach ($features as $fInd => $feature)
								{
									$featureName = $feature->getFeatureType();
		
									// actual output value (takes care of case issues)
									$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, $featureName, $feature, false, $from_primer, $modify, $propsTbl_id);
								}
							}
						}
					}
				}
				else
				{
					if (!$isProtein && !$isRNA)
					{
						// updated Nov. 6/08 - added $from_primer and $modify arguments to function call
						if ($rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$rType], "5' cloning site", $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]))
							$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, "5' cloning site", $cloning_sites["5' cloning site"], true, $from_primer, $modify, $propsTbl_id);
	
						if ($rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$rType], "3' cloning site", $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]))
							$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, "3' cloning site", $cloning_sites["3' cloning site"], true, $from_primer, $modify, $propsTbl_id);
			
						if ($rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$rType], "cdna insert", $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]))
							$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, "cdna insert", $cdnaInsert, false, $from_primer, $modify, false, true, $propsTbl_id);
			
						if ($rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$rType], "5' linker", $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]))
							$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, "5' linker", $linkers["5' linker"], false, $from_primer, $modify, $propsTbl_id);
	
						if ($rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$rType], "3' linker", $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]))
							$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, "3' linker", $linkers["3' linker"], false, $from_primer, $modify, $propsTbl_id);
					}
	
	
					for ($i = 1; $i <= max(array_keys($psorted)); $i++)
					{
						$pList = $psorted[$i];
		
						foreach ($pList as $ind => $propCatID)
						{
							$fID = $rfunc_obj->findPropertyInCategoryID($propCatID);
							$fName =  $_SESSION["ReagentProp_ID_Name"][$fID];
							$features = $tmp_features[$fName];
	
							foreach ($features as $fKey => $feature)
							{
								$featureName = $feature->getFeatureType();
		
								// actual output value (takes care of case issues)
								$this->outputFeaturesTable($rType, $subtype, $rID, $genPrefix, $genPostfix, $featureName, $feature, false, $from_primer, $modify, $propsTbl_id, $isProtein, $isRNA);
							}
						}
					}
				}
	
				// Oct. 26/09
				if ($rID <= 0)
				{
					$createNew = "true";
					$prop_prefix = $rType . "_";
				}
				else
				{
					$createNew = "false";
					$prop_prefix = "";
				}

				?>
				<TR><TD colspan="7"><HR></TD></TR>
				<TR><TD colspan="7"></TD></TR>

				<!-- Row to add features -->
				<TR ID="addlPropsListRow_<?php echo $rType; ?>">
					<TD colspan="5" style="font-weight:bold; white-space:nowrap">
						Select Additional Features:

						<SELECT ID="sequence_property_names_<?php echo $rType; ?>" NAME="sequencePropertyNames" onChange="showPropertyValues2(this.id, '<?php echo str_replace("'", "\'", $form_name); ?>', '<?php echo str_replace("'", "\'", $propsTbl_id); ?>', '<?php echo str_replace("'", "\'", $rType); ?>', '<?php echo $createNew; ?>');" style="font-size:7pt">
							<OPTION VALUE="default">-- Select More Features --</OPTION>
							<?php
								$fText = "";

								foreach ($v_features as $key => $fName)
								{
									$f_id = $rfunc_obj->findPropertyInCategoryID($key);
									$fDescr = $_SESSION["ReagentProp_ID_Desc"][$f_id];
									$fAlias = $_SESSION["ReagentProp_ID_Alias"][$f_id];

									// Sites and cDNA can only occur once; rest of features can occur > 1s
									// June 13/08: Remove linkers too
									if ( (strcasecmp($fName, "5' cloning site") != 0) && (strcasecmp($fName, "3' cloning site") != 0) && (strcasecmp($fName, "cdna insert") != 0) && (strcasecmp($fName, "5' linker") != 0) && (strcasecmp($fName, "3' linker") != 0) && (strcasecmp($fName, "tag position") != 0) && (strcasecmp($fName, "expression system") != 0))

										echo "<OPTION VALUE=\"" . $fAlias . "\">" . $fDescr . "</OPTION>";
								}
							?>
						</SELECT>
					</TD>
				</TR>

				<TR><TD colspan="7"></TD></TR>

				<!-- TEMPLATE FEATURE VALUES LISTS - DO NOT REMOVE!!!! -->
				<?php
					foreach ($v_features as $key => $fName)
					{
						// Update Nov. 3/09
						$f_id = $rfunc_obj->findPropertyInCategoryID($key);
						$fDescr = $_SESSION["ReagentProp_ID_Desc"][$f_id];
						$fAlias = $_SESSION["ReagentProp_ID_Alias"][$f_id];

						?><tr id="<?php echo $prop_prefix . $fAlias; ?>_attribute" style="display:none">
							<td class="detailedView_colName"><?php echo $fDescr; ?></td>
					
							<INPUT TYPE="hidden" ID="<?php echo $rType . "_" . $fAlias . "_descr"; ?>" VALUE="<?php echo $fDescr; ?>">

							<td class="detailedView_value" style="white-space:nowrap;"><?php

								echo "<SELECT ID=\"" . $prop_prefix . $fAlias . "_:_list\" SIZE=\"1\">";

							if ($isProtein)
								echo $rprint_obj->print_Set_Final_Dropdown($fName, "", "Protein Sequence Features", $_SESSION["ReagentType_Name_ID"][$rType]);

							else if ($isRNA)
								echo $rprint_obj->print_Set_Final_Dropdown($fName, "", "RNA Sequence Features", $_SESSION["ReagentType_Name_ID"][$rType]);
							else
								echo $rprint_obj->print_Set_Final_Dropdown($fName, "", "DNA Sequence Features", $_SESSION["ReagentType_Name_ID"][$rType]);
						echo "</select>";
							?></td>
						</tr><?php
					}

					// also feature descriptors
					$f_descriptors = $rfunc_obj->getFeatureDescriptors();

					// $fd in this case is the name of the main property; $fName is the name of the descriptor
					// E.g. $fd == 'tag type', $fName == 'tag position'
					foreach ($f_descriptors as $fd => $fName)
					{
						$fd_id = $_SESSION["ReagentProp_Name_ID"][$fName];
						$fdDescr = $_SESSION["ReagentProp_ID_Desc"][$fd_id];
						$fdAlias = $_SESSION["ReagentProp_ID_Alias"][$fd_id];

						?><tr id="<?php echo $prop_prefix . $fdAlias; ?>_attribute" style="display:none">
							<td class="detailedView_value" style="white-space:nowrap;"><?php
								echo "<SELECT ID=\"" . $prop_prefix . $fdAlias . "_:_list\" SIZE=\"1\">";
									if ($isProtein)
										echo $rprint_obj->print_Set_Final_Dropdown($fName, "", "Protein Sequence Features", $_SESSION["ReagentType_Name_ID"][$rType]);
									if ($isRNA)
										echo $rprint_obj->print_Set_Final_Dropdown($fName, "", "RNA Sequence Features", $_SESSION["ReagentType_Name_ID"][$rType]);
									else
										echo $rprint_obj->print_Set_Final_Dropdown($fName, "", "DNA Sequence Features", $_SESSION["ReagentType_Name_ID"][$rType]);
								echo "</select>";
							?></td>
						</tr><?php
					}

				?>
			</TABLE>
		<?php
	}


	/**
	 * Step 2 of creation, show parents w/ option to change and sequence.
	 * May 12/08: Renamed to previewReagentSequence - use for all reagent types
	 *
	 * @param STRING
	 * @param STRING
	 * @param INT
	 * @param INT
	 *
	 * @author Marina Olhovsky
	 * @version 2008-04-28
	 *
	*/
	function previewReagentSequence($rType, $subtype, $rID, $seqID)
	{
		global $hostname;
		global $cgi_path;

		$gfunc_obj = new generalFunc_Class();
		$rprint_obj = new Reagent_Output_Class();
		$rfunc_obj = new Reagent_Function_Class();

		$genPrefix = "reagent_detailedview_";
		$genPostfix = "_prop";

		$currUserName = $_SESSION["userinfo"]->getDescription();

		// Numerical db IDs of parents (parent1 is PV while parent2 can be either Insert or IPV)
		$parent1 = -1;
		$parent2 = "";

		?>
		<!-- updated Nov. 5/08 - added 'subtype' parameter -->
		<FORM NAME="reagentSeqPreview" method="POST" action="<?php echo $cgi_path . "preload.py"; ?>" onSubmit="return verifyReagentSequenceCreation('<?php echo $rType; ?>', '<?php echo $subtype; ?>');">

			<!-- Pass user info to Python as hidden form value - Aug 21/07 -->
			<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
			
			<!-- Pass reagent info -->
			<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $rType; ?>">
			<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
			<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
			<INPUT TYPE="hidden" NAME="seq_id_hidden" VALUE="<?php echo $seqID; ?>">
			<?php
				switch ($rType)
				{
					case 'Vector':
						switch($subtype)
						{
							case 'nonrecomb':
							case 'gateway_entry':
								$pvID = $_GET["PV"];
								$insertID = $_GET["I"];
		
								$parent1 = $pvID;
								$parent2 = $insertID;
		
								# Jan. 21/09: if Insert was reverse complemented, cDNA (and other features??), in order to be mapped correctly, need to be RCd too (only applies to NR vectors)
								if (isset($_GET["Rev"]) && ($_GET["Rev"] == 'True'))
								{
									$rev = true;
									echo "<INPUT TYPE=\"hidden\" NAME=\"reverse_complement\" VALUE=\"" . $rev . "\">";
								}

								?>
									<table width="770px" cellpadding="4">
										<tr>
											<td style="text-align:center; padding-left:70px; font-weight:bold;">
												<span style="color:#00C00D; font-size:13pt; padding-bottom:18px;">
												<?php
													if ($subtype == 'nonrecomb')
														echo "CREATE A NON-RECOMBINATION VECTOR";
													else if ($subtype == 'gateway_entry')
														echo "CREATE A GATEWAY ENTRY VECTOR";
												?>
												<BR>
												</span>
							
												<span style="color:#00B38D; font-size:10pt;">
													Step 2 of 4: Confirm Sequence
												</span>	
											</td>

											<!-- nov. 5/08 -->
											<TD style="text-align:right; padding-right:15px;">
												<input type="submit" name="confirm_features" value="Continue" onClick="document.getElementById('cancel_set').value=0; document.getElementById('navigate_away').value=0;">
						
												&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">
						
												<INPUT type="hidden" ID="cancel_set" value="0">
											</TD>
										</tr>
									</table>
		
									<!-- Pass parent info as hidden form input to CGI -->
									<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["vector parent id"] . $genPostfix; ?>" VALUE="<?php echo $pvID; ?>">
									<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["insert id"] . $genPostfix; ?>" VALUE="<?php echo $insertID; ?>">

									<!-- Nov. 18/08: Pass sites to CGI too, as hidden input -->
									<?php
										if (isset($_GET["R1"]))
										{
											?><INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentProp_Name_Alias"]["5' cloning site"] . $genPostfix; ?>" VALUE="<?php echo $_GET["R1"]; ?>"><?php
										}

										if (isset($_GET["R2"]))
										{
											?><INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentProp_Name_Alias"]["3' cloning site"] . $genPostfix; ?>" VALUE="<?php echo $_GET["R2"]; ?>"><?php
										}	
							break;
		
							case 'recomb':
							case 'gateway_expression':
								$pvID = $_GET["PV"];
								$ipvID = $_GET["IPV"];
		
								$parent1 = $pvID;
								$parent2 = $ipvID;

								?>
									<table width="750px" cellpadding="4">
										<tr>
											<td colspan="2" style="text-align:center; font-weight:bold;">
												<span style="color:#00C00D; font-size:13pt; padding-bottom:18px;">
												<?php
													if ($subtype == 'recomb')
														echo "CREATE AN EXPRESSION VECTOR";
													else if ($subtype == 'gateway_expression')
														echo "CREATE A GATEWAY EXPRESSION VECTOR";
												?>
												<BR>
												</span>
							
												<span style="color:#00B38D; font-size:10pt;">
													Step 2 of 4: Confirm Sequence
												</span>	
											</td>

											<!-- nov. 5/08 -->
											<TD style="text-align:right; padding-right:15px;">
												<input type="submit" name="confirm_features" value="Continue" onClick="document.getElementById('cancel_set').value=0; document.getElementById('navigate_away').value=0;">
						
												&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">
						
												<INPUT type="hidden" ID="cancel_set" value="0">
											</TD>
										</tr>
									</table>
									<P>

									<!-- Pass parent info as hidden form input to CGI -->
									<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["vector parent id"] . $genPostfix; ?>" VALUE="<?php echo $pvID; ?>">
		
									<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["parent insert vector"] . $genPostfix; ?>" VALUE="<?php echo $ipvID; ?>">
								<?php
							break;
		
							case 'novel':
								?>
								<table width="750px" cellpadding="4">
									<tr>
										<td colspan="2" style="text-align:center; font-weight:bold;">
											<span style="color:#00C00D; font-size:13pt; margin-left:150px; padding-bottom:18px;">
												CREATE A NOVEL (PARENT) VECTOR
												<BR>
											</span>
						
											<span style="color:#00B38D; font-size:10pt; margin-left:150px;">
												Step 1 of 3: Confirm Sequence:
											</span>
										</td>

										<!-- nov. 5/08 -->
										<TD style="text-align:right; padding-right:15px;">

											<input type="submit" name="confirm_features" value="Continue" onClick="document.getElementById('cancel_set').value=0; document.getElementById('navigate_away').value=0;">
											
											&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">
					
											<INPUT type="hidden" ID="cancel_set" value="0">
										</TD>
									</tr>
								</table>
								<?php
							break;
						}
					break;

					case 'Insert':
						$pivID = $_GET["PIV"];
						$senseID = $_GET["SO"];
						$antisenseID = $_GET["AS"];
		
						$parent1 = $pivID;
						$parent2 = $senseID;
						$parent3 = $antisenseID;

						?>
						<!-- Pass parent info as hidden form input to CGI -->
						<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["insert parent vector id"] . $genPostfix; ?>" VALUE="<?php echo $pivID; ?>">

						<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["sense oligo"] . $genPostfix; ?>" VALUE="<?php echo $senseID; ?>">

						<INPUT TYPE="hidden" NAME="<?php echo $genPrefix . $_SESSION["ReagentAssoc_Name_Alias"]["antisense oligo"] . $genPostfix; ?>" VALUE="<?php echo $antisenseID; ?>">

						<table width="750px" cellpadding="4">
							<tr>
								<td colspan="2" style="text-align:center; font-weight:bold; padding-left:220px">
									<span style="color:#00C00D; font-size:13pt; padding-bottom:18px;">
										CREATE A NEW INSERT
										<BR>
									</span>
				
									<span style="color:#00B38D; font-size:10pt;">
										Step 2 of 4: Confirm Sequence
									</span>
								</td>

								<!-- nov. 5/08 -->
								<TD style="text-align:right; padding-right:15px;">
									<input type="submit" name="confirm_features" value="Continue" onClick="document.getElementById('cancel_set').value=0; document.getElementById('navigate_away').value=0;">

									&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">
			
									<INPUT type="hidden" ID="cancel_set" value="0">
								</TD>
							</tr>
						</table>
						<?php
					break;
				}

				$this->printParents($rType, $subtype, $readonly, $parent1, $parent2, $parent3);
			?>
			<BR>

			<TABLE style="width:750px; margin-top:4px; margin-left:10px;">
				<TH colspan="2" style="padding-left:2px; text-align:left; font-size:10pt;">Sequence:</TH>

				<TR>
					<td colspan="2">
					<?php
						switch ($rType)
						{
							case 'Insert':
								?>
								<table width="760px" cellpadding="4">
									<tr>
										<td style="font-size:9pt;">
											Please paste your insert sequence in the box below, <b>including <u>intact cloning sites</u> and any <u>linking sequences</u> between the cloning sites and the start of the cDNA sequence:</b>

<!-- 											<span class="linkShow" style="padding-left:10px;font-size:8pt; font-weight:normal;">View Example</span> -->

											&nbsp;&nbsp;<A href="pictures/insert_example.png" onClick="return popup(this, 'diagram', '665', '635', 'yes')">View example</A>
										</td>
									</tr>
								
									<tr>
										<td colspan="4">
											<?php
												$rprint_obj->print_property_final($genPrefix . "sequence" . $genPostfix, "sequence", $seqID, "", true, "DNA Sequence", "Preview", "", $rType, false);
											?>
											<BR><div id="sequence_warning" style="display:none; color:#FF0000">Please paste a sequence in the textbox above.</div>
										</td>
									</tr>
								</table>
								<BR>
									
								<table width="760px" cellpadding="4">
									<tr>
										<td colspan="4" style="font-size:9pt;">
											<b>Please specify cloning sites on the insert:</b><BR>
										</td>
									</tr>
							
									<TR>
										<TD width="150px" style="padding-left:10px">
											5' Cloning Site:
										</TD>
							
										<TD style="padding-top:10px;">
											<?php
												$five_site = $rfunc_obj->getPropertyValue($rid, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["5' cloning site"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]));

												$rprint_obj->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"]["5' cloning site"] . $genPostfix, "5' cloning site", $five_site, "", true, "DNA Sequence Features", "", "insert");
											?>
											<BR><div id="fp_warning" style="display:none; color:#FF0000">Please select a value for the 5' restriction site from the dropdown list</div>
										</TD>
									</TR>
							
									<TR>
										<TD style="padding-left:10px">
											3' Cloning Site:
										</TD>
							
										<TD>
											<?php
												$three_site = $rfunc_obj->getPropertyValue($rid, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["3' cloning site"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]));

												$rprint_obj->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"]["3' cloning site"] . $genPostfix, "3' cloning site",$three_site, "", true, "DNA Sequence Features", "", "insert");
											?>
											<BR><div id="tp_warning" style="display:none; color:#FF0000">Please select a value for the 3' restriction site from the dropdown list</div>
										</TD>
									</TR>

									<TR><TD></td></tr>

									<TR>
										<TD colspan="2" style="border:1px solid purple; padding-left:7px; vertical-align:full; padding-right:10px; padding-left:10px; padding-bottom:10px; text-align:justify; padding-top:10px; font-weight:bold; color:green; font-size:9pt;">Cloning sites will be mapped onto their positions on the sequence at the next step.  If the sites cannot be found on the sequence, their positions will be set to 0.</TD>
									</TR>
								</table>
								<?php
							break;

							default:
								// don't pass $rID as a parameter here b/c there's no PropList_tbl entry at this point - output function will do a select query and return nothing
								$rprint_obj->print_property_final($genPrefix . "sequence" . $genPostfix, "sequence", $seqID, "", "Preview", true, "DNA Sequence", "");
								?>
								<BR><div id="vector_sequence_warning" style="display:none; color:#FF0000">Please paste a sequence in the textbox above.</div>
								<?php
							break;
						}
						?>
					</td>
				</TR>
			</TABLE>
		</FORM>
		<?php
	}
	

	/**
	 * Just OUTPUT parents table.
	 *
	 * @param STRING
	 * @param STRING
	 * @param boolean
	 * @param INT
	 * @param INT
	 * @param INT
	 * @param boolean
	 *
	 * @author Marina Olhovsky
	*/
	function printParents($rType, $subtype, $readonly=false, $parent1=-1, $parent2=-1, $parent3=-1, $from_primer=false)
	{
		$gfunc_obj = new generalFunc_Class();
		$rprint_obj = new Reagent_Output_Class();

		$genPrefix = "reagent_detailedview_";
		$genPostfix = "_prop";

		$classname = $readonly ? "creation_readonly" : "creation_normal";

		$rID = $_GET["rID"];	// Nov. 5/08

		if (!$readonly)
			echo "<table style=\"margin-top:5px; margin-left:10px;\" cellpadding=\"4\">";
		else
			echo "<table style=\"width:725px; margin-top:5px; margin-left:10px; background-color:#F9F9F9; border:1px solid black; padding:5px;\" cellpadding=\"4\">";

			switch ($rType)
			{
				case 'Vector':
					switch($subtype)
					{
						case 'nonrecomb':
						case 'gateway_entry':
							$pvID = $parent1;
							$insertID = $parent2;
	
							?>
								<tr>
									<td style="padding-left:10px; padding-right:10px; font-size:9pt; width:125px; white-space:nowrap;">
									<?php
										// correction May 20, 2010
										if ($subtype == "gateway_entry")
											echo "Gateway Parent Donor Vector ID:";
										else
											echo "Parent Vector ID:";
									?>
									<!-- Parent Vector ID -->
									</td>
				
									<td style="white-space:nowrap">
									<?php
										if (!$readonly)
											echo "<input type=\"text\" onKeyPress=\"return disableEnterKey(event);\" readonly=\"true\"  style=\"background-color:#E8E8E8;\" value=\"" . strtoupper($gfunc_obj->getConvertedID_rid($pvID)) . "\">";
										else
										{
											echo "<A target=\"blank\" HREF=\"Reagent.php?View=6&rid=" . $pvID . "\">" . strtoupper($gfunc_obj->getConvertedID_rid($pvID)) . "</A>";
										}
	
										?>
									</td>
								</tr>
							
								<tr>
									<td style="padding-left:10px; font-size:9t;">
										Insert ID:
									</td>
			
									<td style="white-space:nowrap">
									<?php
										if (!$readonly)
											echo "<input type=\"text\" onKeyPress=\"return disableEnterKey(event);\" readonly=\"true\"  style=\"background-color:#E8E8E8;\" value=\"" . strtoupper($gfunc_obj->getConvertedID_rid($insertID)) . "\">";
										else
										{
											echo "<A target=\"blank\" HREF=\"Reagent.php?View=6&rid=" . $insertID . "\">" . strtoupper($gfunc_obj->getConvertedID_rid($insertID)) . "</A>";
										}
									?>
									</td>
								</tr>
	
								<TR>
									<td colspan="2">
										<a style="font-size:9pt; padding-left:5px; font-weight:normal;" href="<?php echo $hostname . "Reagent.php?View=2&Type=Vector&Sub=" . $subtype . "&PV=" . strtoupper($gfunc_obj->getConvertedID_rid($pvID)) . "&I=" . strtoupper($gfunc_obj->getConvertedID_rid($insertID)) . "&rID=" . $rID; ?>">Go back and edit parent values</a>
									</td>
								</tr>
								<?php
							break;
		
							case 'recomb':
							case 'gateway_expression':
								$pvID = $parent1;
								$ipvID = $parent2;
		
								?>
								<tr>
									<td colspan="2" style="font-size:11pt; font-weight:bold;">
										Parents:

										<a style="font-size:10pt; font-weight:normal; margin-left:25px;" href="<?php echo $hostname . "Reagent.php?View=2&Type=Vector&Sub=" . $subtype . "&PV=" . strtoupper($gfunc_obj->getConvertedID_rid($pvID)) . "&IPV=" . strtoupper($gfunc_obj->getConvertedID_rid($ipvID)) . "&rID=" . $rID; ?>">Go back and edit parent values</a>
									<td>
								</tr>
								
								<tr>
									<td>
										<table width="500px" style="background-color:#D1D1D1; margin-top:5px;" cellpadding="4" border="1" frame="box" rules="all">
											<tr>
												<td style="white-space:nowrap">
												<?php
													// correction May 20, 2010
													if ($subtype == "gateway_expression")
														echo "Gateway Parent Destination Vector ID:";
													else
														echo "Creator Acceptor ID:";
												?>
									
<!-- 													Parent Vector ID -->
												</td>
							
												<td style="white-space:nowrap">
													<input type="text" onKeyPress="return disableEnterKey(event);"  readonly="<?php echo $readonly; ?>"  style="background-color:#E8E8E8;" value="<?php echo strtoupper($gfunc_obj->getConvertedID_rid($pvID)); ?>">
												</td>
											</tr>
						
											<tr>
												<td style="white-space:nowrap">
												<?php
													// correction May 20, 2010
													if ($subtype == "gateway_expression")
														echo "Gateway Entry Vector ID:";
													else
														echo "Creator Donor Vector ID:";
													?>
<!-- 													Insert Parent Vector ID -->
												</td>
						
												<td style="white-space:nowrap">
													<input type="text" onKeyPress="return disableEnterKey(event);"  readonly="<?php echo $readonly; ?>" value="<?php echo strtoupper($gfunc_obj->getConvertedID_rid($ipvID)); ?>" style="background-color:#E8E8E8;">
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<?php
							break;
						}
					break;

					case 'Insert':
						$pivID = $parent1;
						$senseID = $parent2;
						$antisenseID = $parent3;

						// Nov. 5/08
						if ($from_primer)
						{
							?>
							<tr>
								<TD>
								</td>
							</tr>
							<?php
						}
						?>
						<tr>
							<td colspan="2" style="font-size:11pt; font-weight:bold;">
								Parents:
								<?php
									// Nov. 5/08
									if (!$from_primer)
									{
										?>
										<a style="font-size:10pt; font-weight:normal; margin-left:10px;" href="<?php echo $hostname . "Reagent.php?View=2&Type=Insert&PIV=" . $pivID . "&SO=" . $senseID . "&AS=" . $antisenseID . "&rID=" . $rID; ?>">Go back and edit parent values</a>
										<?php
									}
									?>
							</td>
						</tr>
						
						<tr>
							<td>
								<table width="500px" style="background-color:#D1D1D1; margin-top:5px;" cellpadding="4" border="1" frame="box" rules="all">
									<tr>
										<td style="white-space:nowrap">
											Sense Oligo ID
										</td>
				
										<td style="white-space:nowrap">
											<input type="text" onKeyPress="return disableEnterKey(event);" readonly="<?php echo $readonly; ?>" value="<?php echo strtoupper($senseID); ?>" style="background-color:#E8E8E8;">
										</td>
									</tr>

									<tr>
										<td style="white-space:nowrap">
											Antisense Oligo ID
										</td>
				
										<td style="white-space:nowrap">
											<input type="text" onKeyPress="return disableEnterKey(event);" readonly="<?php echo $readonly; ?>" value="<?php echo strtoupper($antisenseID); ?>" style="background-color:#E8E8E8;">
										</td>
									</tr>

									<tr>
										<td style="white-space:nowrap">
											Insert Parent Vector ID
										</td>

										<td style="white-space:nowrap">
										<?php
											// Nov. 5/08
											if (!$from_primer)
											{
												?>
												<input type="text" onKeyPress="return disableEnterKey(event);" readonly="<?php echo $readonly; ?>" style="background-color:#E8E8E8;" value="<?php echo strtoupper($pivID); ?>">
												<?php
											}
											else
											{
												?>
												<!-- hard-code name, selection trivial -->
												<input type="text" onKeyPress="return disableEnterKey(event);" name="<?php echo $genPrefix . "insert_parent_vector" . $genPostfix; ?>" value="<?php echo strtoupper($pivID); ?>">
												<?php
											}
										?>
										</td>
									</tr>
									<?php
										if ($from_primer)
										{
											echo "(Please input Insert Parent Vector ID if known)";
										}
									?>
				
								</table>
							</td>
						</tr>
						<?php
					break;
				}
			?>
		</table>
		<?php
	}


	/**
	 * Print Vector creation form (don't dare delete!!!!!!!!!!!!!!!!)
	 *
	 * @param STRING
	 * @param STRING
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 2008-04-14
	*/
	function printForm_Vector_Create($rType, $subtype)
	{
		global $cgi_path;

		// Form input
		$genPrefix = "reagent_detailedview_";
		$genPostfix = "_prop";

		$assoc_prefix = "assoc_";
		$assocPostfix = "_hidden";

		// Property names
		$v_name = $this->rfunc_obj->get_Post_Names("Vector", "");

		// Object Instatiations
		$gfunc_obj = new generalFunc_Class();
		$rprint_obj = new Reagent_Output_Class();
		
		// User info
		$currUserName = $_SESSION["userinfo"]->getDescription();

		switch ($subtype)
		{
			case 'novel':
				?>
				<!-- Pass user info to Python as hidden form value - Aug 21/07 -->
				<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>"> 
				<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $rType; ?>">
				<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
				<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
				<input type="hidden" id="vector_cloning_method" name="cloning_method_hidden" value="<?php echo $cloningMethod; ?>">

				<!-- MAY 2/08: IF RETURNING TO THIS VIEW FROM A LATER PAGE TO CHANGE PARENTS - PASS THE REAGENT ID TO PYTHON!!!!!! -->
				<?php
					if (isset($_GET["rID"]))
					{
						?>
							<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $_GET["rID"]; ?>">
						<?php
					}
				?>
				<table width="680px" cellpadding="4">
					<tr>
						<td style="text-align:center">
							<span style="color:#00C00D; font-size:13pt; font-weight:bold">
								CREATE A NEW PARENT (NOVEL) VECTOR
							</span>
						</td>
					</tr>
				</table>
		
				<table border="1" width="600px" cellpadding="4" frame="box" rules="none">
					<tr>
						<td style="font-size:9pt; padding-left:10px;">
							<b>Step 1 of 3:</b><BR><P><span style="padding-left:5px;">Please paste your Vector sequence in the box below<!--, <b>including <u>intact cloning sites</u> and any <u>linking sequences</u> between the cloning sites and the start of the cDNA sequence:</b>-->:</span>
						</td>
					</tr>
				
					<tr>
						<td>
							<?php
								$rprint_obj->print_property_final($genPrefix . $v_name["sequence"] . $genPostfix, "sequence", "", "Preview", true, "DNA Sequence", "");
							?>
							<BR><div id="vector_sequence_warning" style="display:none; color:#FF0000">Please paste a sequence in the textbox above.</div>
						</td>
					</tr>
				</table>
				<BR>
				
				<table style="padding-top:10px;">
					<TR>
						<td style="padding-left:0px;">
							<input type="submit" name="confirm_features" value="Continue" onClick="document.getElementById('cancel_set').value=0; return verifyNovelVectorSequence(); document.getElementById('navigate_away').value=0;">
						</td>
					</tr>
					
				</table>
				<?php
			break;
		}
	}


	/**
	 * Final Insert creation step from Primer Designer
	 *
	 * @param INT
	 * @param INT
	 * @param STRING sense oligo ID, e.g. O123
	 * @param STRING antisense oligo ID, e.g. O123
	 *
	 * @author Marina Olhovsky
	 * @version 2008-05-14
	*/
	function previewInsertIntroPrimer($rID, $seqID, $sense, $antisense)
	{
		global $hostname;
		global $cgi_path;

		$gfunc_obj = new generalFunc_Class();
		$rprint_obj = new Reagent_Output_Class();
		$rfunc_obj = new Reagent_Function_Class();

		// May 5/08: Use a different prefix - make uniform for all reagent types and for dynamic features
		$genPrefix = "reagent_detailedview_";
		$genPostfix = "_prop";

		$currUserName = $_SESSION["userinfo"]->getDescription();

		$reagent_name = $rfunc_obj->get_Post_Names("Insert", "Primer General");

		// When creating Insert from Primer, most of its general properties, annotations and classifiers have already been preloaded from parents and saved.  Only need to provide a name for the Insert, a project ID, description, verification, comments; optionally a Parent Vector

		?>
		<FORM NAME="reagentDetailForm" method="POST" action="<?php echo $cgi_path . "create.py"; ?>">

			<input type="hidden" ID="changeStateIntro" name="change_state" value="Save">
			<input type="hidden" name="save_intro">

			<INPUT TYPE="hidden" NAME="from_primer" VALUE="True">

			<!-- Pass user info to Python as hidden form value - Aug 21/07 -->
			<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
			<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="Insert">
			<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
			<INPUT TYPE="hidden" NAME="seq_id_hidden" VALUE="<?php echo $seqID; ?>">
			<?php

			$senseID = $_GET["SO"];
			$antisenseID = $_GET["AS"];
			$pivID = $_GET["PIV"];

			?>
			<table width="780px" cellpadding="4">
				<tr>
					<td colspan="2" style="text-align:center; font-weight:bold;">
						<span style="color:#00C00D; font-size:13pt; padding-bottom:18px;">
							INSERT CREATION
							<BR>
						</span>
	
						<span style="color:#00B38D; font-size:10pt;">
							Step 4 of 4: Confirm General Properties
						</span>

						<BR>
			
						<a onclick="document.getElementById('navigate_away').value=0;" href="<?php echo $hostname . "Reagent.php?View=2&Step=4&Type=Insert&SO=" . $senseID . "&AS=" . $antisenseID . "&PIV=" . $pivID . "&rID=" . $rID . "&Seq=" . $seqID; ?>">Go back and edit features</a>

					</td>

					<!-- nov. 5/08 -->
					<TD style="text-align:right; padding-right:15px;">
						<input type="submit" name="confirm_intro" value="Continue" onClick="document.pressed='Create'; document.getElementById('cancel_set').value=0; selectAllPropertyValues(true); return checkMandatoryProps('Insert'); document.getElementById('navigate_away').value=0;">

						&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">

						<INPUT type="hidden" ID="cancel_set" value="0">
					</TD>
				</tr>

				<TR>
					<TD colspan="2" style="text-align:center; font-weight:bold;">
						<span style="font-size:9pt; color:#FF0000; font-weight:bold;">
							Fields marked with a red asterisk (*) are mandatory
						</span>
					</TD>
				</TR>
			</table>

			<P>
			<?php

			$rType = "Insert";
			$rTypeID = $_SESSION["ReagentType_Name_ID"]["Insert"];

			// find the attributes of this reagent type
			$categories = $rfunc_obj->findAllReagentTypeAttributeCategories($rTypeID);

			echo "<P><HR><TABLE>";

			foreach ($categories as $categoryID => $category)
			{
				if (($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence Features"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence Features"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]))
				{
					$rTypeAttributes = $rfunc_obj->getReagentTypeAttributesByCategory($rTypeID, $categoryID);

					if (count($rTypeAttributes) == 0)
						continue;

					$catAlias = $_SESSION["ReagentPropCategory_ID_Alias"][$categoryID];

					echo "<TR>";
						echo "<TD style=\"padding-left:5px;\">";
							echo "<table style=\"background-color:#FFFFFF;\">";
								// Aug. 19/09: Changed categoryID to catAlias, since that is also the name of the table in addNewReagentType() and JS was confusing between the two
								echo "<tr>";
									echo "<td style=\"font-weight:bold; color:blue; padding-top:2px;\">";
										echo "<IMG id=\"" . $catAlias . "_expand_img_" . $rType . "\" SRC=\"pictures/arrow_collapse.gif\" WIDTH=\"20\" HEIGHT=\"15\" BORDER=\"0\" ALT=\"plus\" class=\"menu-expanded\" style=\"display: inline\" onClick=\"showHideCategory('" . $catAlias . "', '" . $rType . "');\">";

										echo "<IMG id=\"" . $catAlias . "_collapse_img_" . $rType . "\" SRC=\"pictures/arrow_expand.gif\" WIDTH=\"40\" HEIGHT=\"34\" BORDER=\"0\" ALT=\"plus\" class=\"menu-collapsed\" style=\"display: none\" onClick=\"showHideCategory('" . $catAlias . "', '" . $rType . "');\">";

										echo "<span id=\"section_title_" . $catAlias . "\">" . $category . "</span>";
									echo "</TD>";
								echo "</TR>";

								echo "<tr>";
									echo "<td style=\"padding-left:20px;\">";
										echo "<table ID=\"category_" . $catAlias . "_section_" . $rType . "\" cellpadding=\"4\" cellspacing=\"2\" style=\"background-color:#FFFFFF; margin-top:4px;\">";

										foreach ($rTypeAttributes as $attrID => $cProp)
										{
											$propDescr = $cProp->getPropertyDescription();
											$propAlias = $cProp->getPropertyAlias();
											$propName = $cProp->getPropertyName();
											$propCategory = $cProp->getPropertyCategory();

											// May 5, 2010
											$p_id = $_SESSION["ReagentProp_Name_ID"][$propName];
											$c_id = $_SESSION["ReagentPropCategory_Name_ID"][$propCategory];
											$pID = $rfunc_obj->getPropertyIDInCategory($p_id, $c_id);

											if (!in_array($propName, $ignoreList))
											{
												echo "<TR style=\"background-color:#F5F5DC;\">";

													echo "<TD style=\"padding-left:15px; padding-right:15px; font-size:8pt; width:100px; font-weight:bold; white-space:nowrap;\">" . $propDescr;

													$tmp_pc_id = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$propName], $_SESSION["ReagentPropCategory_Name_ID"][$propCategory]);

													if ( ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["project id"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || (strcasecmp($propName, "packet id") == 0) || (strcasecmp($propName, "owner") == 0) || ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["status"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || (strcasecmp($propName, "type of insert") == 0) || (strcasecmp($propName, "open/closed") == 0) || (strcasecmp($propName, $rType . " type") == 0))
													{
														// March 3, 2011
														if (strcasecmp($propName, "open/closed") == 0)
															echo "&nbsp;<span style=\"font-size:9pt; color:#FF0000; font-weight:bold\">*</span>&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/hm4.png\" HEIGHT=\"13\" width=\"16\" ALT=\"link_icon\" style=\"vertical-align:middle; cursor:auto\" onmouseover=\"return overlib('Select the correct open/closed value to ensure correct translation of the DNA sequence to protein.  Refer to the \'Translation Guidelines\' for more information.', CAPTION, 'Open/Closed', STICKY);\">";

														else if (strcasecmp($propName, "type of insert") == 0)
															echo "&nbsp;<span style=\"font-size:9pt; color:#FF0000; font-weight:bold\">*</span>&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/hm4.png\" HEIGHT=\"13\" width=\"16\" ALT=\"link_icon\" style=\"vertical-align:middle; cursor:auto\" onmouseover=\"return overlib('Select the correct type of insert value to ensure correct translation of the DNA sequence to protein.  Refer to the \'Translation Guidelines\' for more information.', CAPTION, 'Type of Insert', STICKY);\">";
														
														else
															echo "&nbsp;<span style=\"font-size:9pt; color:#FF0000; font-weight:bold\">*</span></TD>";
// echo $rType . "_mandatoryProps[]";

														echo "<INPUT TYPE=\"hidden\" NAME=\"" . $rType . "_mandatoryProps[]\" VALUE=\"" . $attrID . "\">";


														// When checking open/closed, need to pass type of insert to Javascript so it can be checked too
														if (strcasecmp($propName, "type of insert") == 0)
														{
															$openClosedAttrID = $rfunc_obj->getRTypeAttributeID($rTypeID, "open/closed", $categoryID);

															// echo $openClosedAttrID;
															echo "<INPUT TYPE=\"hidden\" ID=\"Insert_open_closed_prop\" VALUE=\"" . $openClosedAttrID . "\">";
														}

														else if (strcasecmp($propName, "open/closed") == 0)
														{
															$iTypeAttrID = $rfunc_obj->getRTypeAttributeID($rTypeID, "type of insert", $categoryID);

															// echo $openClosedAttrID;
															echo "<INPUT TYPE=\"hidden\" ID=\"Insert_type_of_insert_prop\" VALUE=\"" . $iTypeAttrID . "\">";
														}
													}
													else if ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["alternate id"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]))
													{
														echo "<BR><BR><span style=\"font-size:8pt;\">For 'Other', please enter<BR> the database name and<BR> numeric identifier, separated<BR> by semicolon, in the textbox<BR> (e.g. 'IMAGE:123456')</span></TD>";
													}
													else
													{
														echo "</TD>";
													}

													echo "<TD style=\"font-size:8pt; padding-left:10px; width:600px;\">";

													// March 10/10: preload properties from parent
													$pVal = $rfunc_obj->getPropertyValue($rID, $tmp_pc_id);
													$rprint_obj->print_property_final($genPrefix . $rType . "_" . $catAlias . "_:_" . $propAlias . $genPostfix, $propName, $pVal, "", true, $propCategory, "Preview", "", $rType, false, "", 0, 0);

													echo "</TD>";
												echo "</TR>";
											}
										}

										echo "</TABLE>";
									echo "</TD>";
								echo "</TR>";
							echo "</TABLE>";

							echo "<BR>";

						echo "</TD>";
					echo "</TR>";

					echo "<TR><TD colspan=\"2\" style=\"padding-left:5px; border-top:1px solid black;\"><BR></TD></TR>";
				}
			}

			echo "</TABLE>";
		?>
		</FORM>
		<?php
	}


	/**
	 * Final step in creation - ONLY show form to enter general info, DON'T show sequence/features (too much, too messy)
	 *
	 * @param STRING
	 * @param STRING
	 * @param INT
	 * @param INT
	 *
	 * @author Marina Olhovsky
	 * @version 2008-05-05
	*/
	function previewReagentIntro($rType, $subtype, $rID, $seqID)
	{
		global $hostname;
		global $cgi_path;
		global $conn;

		$gfunc_obj = new generalFunc_Class();
		$rprint_obj = new Reagent_Output_Class();
		$rfunc_obj = new Reagent_Function_Class();

		// May 5/08: Use a different prefix - make uniform for all reagent types and for dynamic features
		$genPrefix = "reagent_detailedview_";
		$genPostfix = "_prop";

		$currUserName = $_SESSION["userinfo"]->getDescription();

		// Numerical db IDs of parents (parent1 is PV while parent2 can be either Insert or IPV)
		$parent1 = -1;
		$parent2 = "";

		$allAttrs = $rfunc_obj->getAllReagentTypeAttributes($_SESSION["ReagentType_Name_ID"][$rType]);

		// get cloning method (ATypeID)
		$query = "SELECT * FROM Association_tbl WHERE reagentID='" . $rID . "' AND status='ACTIVE'";

		$cloning_method_rs = mysql_query($query, $conn) or die("Could not determine cloning method: " . mysql_error());

		if ($cloning_method_ar = mysql_fetch_array($cloning_method_rs, MYSQL_ASSOC))
		{
			$cloningMethod = $cloning_method_ar["ATypeID"];
		}
		else
		{
			$cloningMethod = 3;	// hard-code BASIC cloning method - parent vector
		}

		mysql_free_result($cloning_method_rs);
		unset($cloning_method_ar);

		?>
		<!-- Pass user info to Python as hidden form value - Aug 21/07 -->
		<FORM NAME="reagentDetailForm" method="POST" action="<?php echo $cgi_path . "create.py"; ?>">

			<input type="hidden" ID="changeStateVectorIntro" name="change_state" value="Save">
			<input type="hidden" name="save_intro">

			<!-- Pass user info to Python as hidden form value - Aug 21/07 -->
			<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
			<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $_GET["Type"]; ?>">
			<INPUT TYPE="hidden" NAME="subtype_hidden" VALUE="<?php echo $subtype; ?>">
			<INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $rID; ?>">
			<input type="hidden" id="vector_cloning_method" name="cloning_method_hidden" value="<?php echo $cloningMethod; ?>">
			<INPUT TYPE="hidden" NAME="seq_id_hidden" VALUE="<?php echo $seqID; ?>">
			<?php
				switch ($rType)
				{
					case 'Vector':
						switch($subtype)
						{
							case 'nonrecomb':
							case 'gateway_entry':
								$pvID = $_GET["PV"];
								$insertID = $_GET["I"];
		
								$parent1 = $pvID;
								$parent2 = $insertID;

								?>
								<table width="780px" cellpadding="4">
									<tr>
										<td colspan="2" style="text-align:center; padding-left:200px; font-weight:bold;">
											<span style="color:#00C00D; font-size:13pt; padding-bottom:18px;">
												<?php
													if ($subtype == 'nonrecomb')
														echo "CREATE A NON-RECOMBINATION VECTOR";
													else if ($subtype == 'gateway_entry')
														echo "CREATE A GATEWAY ENTRY VECTOR";
												?>
												<BR>
											</span>
						
											<span style="color:#00B38D; font-size:10pt;">
												Step 4 of 4: Confirm General Properties
											</span>
										</td>
 										<!-- nov. 5/08 -->
										<TD style="text-align:right; padding-right:15px;">
											<input type="submit" name="confirm_intro" value="Continue" onClick="document.pressed='Create'; document.getElementById('cancel_set').value=0; enableSites(); changeFieldNames('reagentDetailForm', null, '<?php echo $rType; ?>'); setFeaturePositions(); verifyPositions(); selectAllPropertyValues(true); return checkMandatoryProps('<?php echo $_GET["Type"]; ?>', '<?php echo $subtype; ?>'); document.getElementById('navigate_away').value=0;">
					
											&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">
					
											<INPUT type="hidden" ID="cancel_set" value="0">
										</TD>
									</tr>
								</table>
								<?php
							break;
		
							case 'recomb':
							case 'gateway_expression':
								$pvID = $_GET["PV"];
								$ipvID = $_GET["IPV"];
		
								$parent1 = $pvID;
								$parent2 = $ipvID;
		
								?>
								<table width="780px" cellpadding="4">
									<tr>
										<td colspan="2" style="text-align:center; padding-left:200px; font-weight:bold;">
											<span style="color:#00C00D; font-size:13pt; padding-bottom:18px; margin-left:150px;">
												<?php
													if ($subtype == 'recomb')
														echo "CREATE AN EXPRESSION VECTOR";
													else if ($subtype == 'gateway_expression')
														echo "CREATE A GATEWAY EXPRESSION VECTOR";
												?>
												<BR>
											</span>
						
											<span style="color:#00B38D; font-size:10pt;">
												Step 4 of 4: Confirm General Properties
											</span>
										</td>

										<!-- nov. 5/08 -->
										<TD style="text-align:right; padding-right:15px;">
											<input type="submit" name="confirm_intro" value="Continue" onClick="document.pressed='Create'; document.getElementById('cancel_set').value=0; enableSites(); changeFieldNames('reagentDetailForm', null, '<?php echo $rType; ?>'); setFeaturePositions(); verifyPositions(); selectAllPropertyValues(true); return checkMandatoryProps('<?php echo $_GET["Type"]; ?>', '<?php echo $subtype; ?>'); document.getElementById('navigate_away').value=0;">
					
											&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">
					
											<INPUT type="hidden" ID="cancel_set" value="0">
										</TD>
									</tr>
								</table>
								<?php
							break;

							default:
								?>
								<table width="780px" cellpadding="4">
									<tr>
										<td style="text-align:center; font-weight:bold;">
											<span style="color:#00C00D; font-size:13pt; padding-bottom:18px; margin-left:150px;">CREATE NOVEL VECTOR<BR></span>
						
											<span style="color:#00B38D; font-size:10pt; margin-left:150px;">
												Step 3 of 3: Confirm General Properties
											</span>
										</td>

										<!-- nov. 5/08 -->
										<TD style="text-align:right; padding-right:15px;">
											<input type="submit" name="confirm_intro" value="Continue" onClick="document.pressed='Create'; document.getElementById('cancel_set').value=0; enableSites(); changeFieldNames('reagentDetailForm', null, '<?php echo $rType; ?>'); setFeaturePositions(); verifyPositions(); selectAllPropertyValues(true); return checkMandatoryProps('<?php echo $_GET["Type"]; ?>', '<?php echo $subtype; ?>'); document.getElementById('navigate_away').value=0;">

											&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">
					
											<INPUT type="hidden" ID="cancel_set" value="0">
										</TD>
									</tr>
								</table>
								<?php
							break;
						}
					break;

					case 'Insert':
						$pivID = $_GET["PIV"];
						$senseID = $_GET["SO"];
						$antisenseID = $_GET["AS"];

						$parent1 = $pivID;
						$parent2 = $senseID;
						$parent3 = $antisenseID;

						?>
						<table style="width:780px" cellpadding="4">
							<tr>
								<td style="text-align:center; padding-left:200px; font-weight:bold;">
									<span style="color:#00C00D; font-size:13pt;  padding-bottom:18px;">
										CREATE AN INSERT
										<BR>
									</span>
				
									<span style="color:#00B38D; font-size:10pt;">
										Step 4 of 4: Confirm General Properties
									</span>
								</td>

								<!-- nov. 5/08 -->
								<TD style="text-align:right; padding-right:15px;">
									<input type="submit" name="confirm_intro" value="Continue" onClick="document.pressed='Create'; document.getElementById('cancel_set').value=0; document.pressed='Create'; enableSites(); changeFieldNames('reagentDetailForm', null, '<?php echo $rType; ?>'); setFeaturePositions(); verifyPositions(); selectAllPropertyValues(true); return checkMandatoryProps('<?php echo $_GET["Type"]; ?>', '<?php echo $subtype; ?>'); document.getElementById('navigate_away').value=0;">
			
									&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">
			
									<INPUT type="hidden" ID="cancel_set" value="0">
								</TD>
							</tr>
						</table>
						<?php
					break;
				}

				?><TABLE cellpadding="2" width="700px">
				<TR>
					<TD style="padding-left:10px;">
					<?php
						switch ($rType)
						{
							case 'Vector':
								switch($subtype)
								{
									case 'nonrecomb':
									case 'gateway_entry':
									?>
										<a onclick="document.getElementById('navigate_away').value=0;" href="<?php echo $hostname . "Reagent.php?View=2&Step=2&Type=Vector&Sub=" . $subtype . "&PV=" . $pvID . "&I=" . $insertID . "&rID=" . $rID . "&Seq=" . $seqID . "&Rev=" . $_GET["Rev"]; ?>">Go back and edit features</a>
									<?php
									break;
	
									case 'recomb':
									case 'gateway_expression':
									?>
										<a onclick="document.getElementById('navigate_away').value=0;" href="<?php echo $hostname . "Reagent.php?View=2&Step=2&Type=Vector&Sub=" . $subtype . "&PV=" . $pvID . "&IPV=" . $ipvID . "&rID=" . $rID . "&Seq=" . $seqID; ?>">Go back and edit features</a>
									<?php
									break;

									case 'novel':
									?>
										<a onclick="document.getElementById('navigate_away').value=0;" href="<?php echo $hostname . "Reagent.php?View=2&Step=2&Type=Vector&Sub=" . $subtype . "&rID=" . $rID . "&Seq=" . $seqID; ?>">Go back and edit features</a>
									<?php
									break;
								}
							break;

							case 'Insert':
							?>
								<a onclick="document.getElementById('navigate_away').value=0;" href="<?php echo $hostname . "Reagent.php?View=2&Step=2&Type=Insert&PIV=" . $pivID . "&SO=" . $senseID . "&AS=" . $antisenseID . "&rID=" . $rID . "&Seq=" . $seqID; ?>">Go back and edit features</a>
							<?php
							break;
						}
						?>
					</TD>
				</TR>
			</TABLE>
			<?php
					$rTypeID = $_SESSION["ReagentType_Name_ID"][$rType];

					// find the attributes of this reagent type
					$categories = $rfunc_obj->findAllReagentTypeAttributeCategories($rTypeID);

					echo "<P><HR><TABLE>";

					foreach ($categories as $categoryID => $category)
					{
						if (($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence Features"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence Features"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]))
						{
							$rTypeAttributes = $rfunc_obj->getReagentTypeAttributesByCategory($rTypeID, $categoryID);
							
							if (count($rTypeAttributes) == 0)
								continue;

							$catAlias = $_SESSION["ReagentPropCategory_ID_Alias"][$categoryID];

							// print_r($rTypeAttributes);
		
							echo "<TR>";
								echo "<TD style=\"padding-left:5px;\">";
									echo "<table style=\"background-color:#FFFFFF;\">";
										// Aug. 19/09: Changed categoryID to catAlias, since that is also the name of the table in addNewReagentType() and JS was confusing between the two
										echo "<tr>";
											echo "<td style=\"font-weight:bold; color:blue; padding-top:2px;\">";
												echo "<IMG id=\"" . $catAlias . "_expand_img_" . $rType . "\" SRC=\"pictures/arrow_collapse.gif\" WIDTH=\"20\" HEIGHT=\"15\" BORDER=\"0\" ALT=\"plus\" class=\"menu-expanded\" style=\"display: inline\" onClick=\"showHideCategory('" . $catAlias . "', '" . $rType . "');\">";

												echo "<IMG id=\"" . $catAlias . "_collapse_img_" . $rType . "\" SRC=\"pictures/arrow_expand.gif\" WIDTH=\"40\" HEIGHT=\"34\" BORDER=\"0\" ALT=\"plus\" class=\"menu-collapsed\" style=\"display: none\" onClick=\"showHideCategory('" . $catAlias . "', '" . $rType . "');\">";

												echo "<span id=\"section_title_" . $catAlias . "\">" . $category . "</span>";
											echo "</TD>";
										echo "</TR>";

										echo "<tr>";
											echo "<td style=\"padding-left:20px;\">";
												echo "<table ID=\"category_" . $catAlias . "_section_" . $rType . "\" cellpadding=\"4\" cellspacing=\"2\" style=\"background-color:#FFFFFF; margin-top:4px;\">";

												// Update May 5, 2010: keys are now attrIDs
												foreach ($rTypeAttributes as $attrID => $cProp)
												{
													$propDescr = $cProp->getPropertyDescription();
													$propAlias = $cProp->getPropertyAlias();
													$propName = $cProp->getPropertyName();
													$propCategory = $cProp->getPropertyCategory();

													// added May 5, 2010 just in case
													$p_id = $_SESSION["ReagentProp_Name_ID"][$propName];
													$c_id = $_SESSION["ReagentPropCategory_Name_ID"][$propCategory];
													$pID = $rfunc_obj->getPropertyIDInCategory($p_id, $c_id);

													if (!in_array($propName, $ignoreList))
													{
														echo "<TR style=\"background-color:#F5F5DC;\">";

															echo "<TD style=\"padding-left:15px; padding-right:15px; font-size:8pt; width:100px; font-weight:bold; white-space:nowrap;\">" . $propDescr;

															// Feb. 8/10
															$tmp_pc_id = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$propName], $_SESSION["ReagentPropCategory_Name_ID"][$propCategory]);

															//if ( ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["project id"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || (strcasecmp($propName, "packet id") == 0) || (strcasecmp($propName, "owner") == 0) || ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["status"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || (strcasecmp($propName, "type of insert") == 0) || (strcasecmp($propName, "open/closed") == 0) || (strcasecmp($propName, $rType . " type") == 0))
															if ($rfunc_obj->isMandatory($rType, $propName))
															{
																// March 3, 2011
																if (strcasecmp($propName, "open/closed") == 0)
																	echo "&nbsp;<span style=\"font-size:9pt; color:#FF0000; font-weight:bold\">*</span>&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/hm4.png\" HEIGHT=\"13\" width=\"16\" ALT=\"link_icon\" style=\"vertical-align:middle; cursor:auto\" onmouseover=\"return overlib('Select the correct open/closed value to ensure correct translation of the DNA sequence to protein.  Refer to the \'Translation Guidelines\' for more information.', CAPTION, 'Open/Closed', STICKY);\">";

																else if (strcasecmp($propName, "type of insert") == 0)
																	echo "&nbsp;<span style=\"font-size:9pt; color:#FF0000; font-weight:bold\">*</span>&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/hm4.png\" HEIGHT=\"13\" width=\"16\" ALT=\"link_icon\" style=\"vertical-align:middle; cursor:auto\" onmouseover=\"return overlib('Select the correct type of insert value to ensure correct translation of the DNA sequence to protein.  Refer to the \'Translation Guidelines\' for more information.', CAPTION, 'Type of Insert', STICKY);\">";
																
																else
																	echo "&nbsp;<span style=\"font-size:9pt; color:#FF0000; font-weight:bold\">*</span></TD>";

																echo "<INPUT TYPE=\"hidden\" NAME=\"" . $rType . "_mandatoryProps[]\" VALUE=\"" . $attrID . "\">";

																// When checking open/closed, need to pass type of insert to Javascript so it can be checked too
																if (strcasecmp($propName, "type of insert") == 0)
																{
																	$openClosedAttrID = $rfunc_obj->getRTypeAttributeID($rTypeID, "open/closed", $categoryID);

																	echo "<INPUT TYPE=\"hidden\" ID=\"Insert_open_closed_prop\" VALUE=\"" . $openClosedAttrID . "\">";
																}

																else if (strcasecmp($propName, "open/closed") == 0)
																{
																	$iTypeAttrID = $rfunc_obj->getRTypeAttributeID($rTypeID, "type of insert", $categoryID);

																	echo "<INPUT TYPE=\"hidden\" ID=\"Insert_type_of_insert_prop\" VALUE=\"" . $iTypeAttrID . "\">";
																}

															}
															else if ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["alternate id"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]))
															{
																echo "<BR><BR><span style=\"font-size:8pt;\">For 'Other', please enter<BR> the database name and<BR> numeric identifier, separated<BR> by semicolon, in the textbox<BR> (e.g. 'IMAGE:123456')</span></TD>";
															}
															else
																echo "</TD>";

															echo "<TD style=\"font-size:8pt; padding-left:10px; width:600px;\">";

															$rprint_obj->print_property_final($genPrefix . $rType . "_" . $catAlias . "_:_" . $propAlias . $genPostfix, $propName, "", "", true, $propCategory, "Preview", "", $rType, false, "", 0, 0);

															echo "</TD>";
														echo "</TR>";
													}
												}

												echo "</TABLE>";
											echo "</TD>";
										echo "</TR>";
									echo "</TABLE>";

									echo "<BR>";

								echo "</TD>";
							echo "</TR>";

							echo "<TR><TD colspan=\"2\" style=\"padding-left:5px; border-top:1px solid black;\"><BR></TD></TR>";
						}
					}

					echo "</TABLE>";
			?></FORM>
		</FORM>
		<?php
	}


	/**
	 * In this step, allow user to input Insert parents (May 12/08: Split Insert creation into steps, like Vector)
	 *
	 * @param INT
	 * @param boolean
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 2008-05-05
	*/
	function printForm_Insert_Insert($rID, $show)
	{
		if (isset($_GET["rID"]))
		{
			$rID = $_GET["rID"];
		}

		global $cgi_path;	// march 31/07, marina

		$tablestyle = "";
		$vectorcloningsites_style = "";
		
		// Object Instatiations
		$gfunc_obj = new generalFunc_Class();
		$rprint_obj = new Reagent_Output_Class();
		
		$rfunc_obj = new Reagent_Function_Class();
		$rfunc_obj->reset_Reagent_Session();
		
		$insert_prefix = "INPUT_INSERT_info_";
		$insert_postfix = "_prop";
		$reagentTypeID = $_SESSION["ReagentType_Name_ID"]["Insert"];
		
		$il_names = $this->rfunc_obj->get_Post_Names("Insert", "");
		$property_tmp = array();
		$error_tmp = "";
		$rid_tmp = 0;
		
		$vectorcloningsites_style = "style=\"visibility:hidden; display:none;\"";
		
		if( $show == false )
		{
			$tablestyle = "display:none";
		}
	
		$assoc_prefix = "assoc_";

		$currUserName = $_SESSION["userinfo"]->getDescription();

		$pivID = $_GET["PIV"];
		$senseID = $_GET["SO"];
		$antisenseID = $_GET["AS"];
		
		$parent1 = $pivID;
		$parent2 = $senseID;
		$parent3 = $antisenseID;
		?>
		<FORM method="POST" NAME="createReagentForm" action="<?php echo $cgi_path . "preload.py"; ?>" onsubmit="return verifyParents();">
			<input type="hidden" name="reagentType" value="Insert">
			<input type="hidden" name="reagent_id_hidden" value="<?php echo $rID; ?>">
 			<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

			<TABLE width="100%" id="insert_general_props" style="<?php echo $tablestyle; ?>">
				<tr>
					<td style="font-size:13pt;color:#00C00D; text-align:center; font-weight:bold">
						CREATE A NEW INSERT
					</td>
				</tr>

				<tr>
					<td style="color:#00B38D; font-size:10pt; text-align:center; font-weight:bold">
						Step 1 of 4: Define Parents
						<BR>

						<BR>
						<SPAN style="font-size:9pt; font-weight:bold; color:#0000BD;">** This step is <u>optional</u> - if parent values are not known, you may leave this page blank and press 'Continue' to proceed to the next step **</SPAN>
					</td>
				</tr>
			
				<tr>
					<td>
						<TABLE width="100%" style="border: 1px outset black;" cellpadding="3">
							<TR>
								<TD colspan="2">
									<span style="font-size:9pt; padding-left:5px; font-weight:bold; white-space:nowrap;">Please enter  OpenFreezer IDs of the reagents (Insert Parent Vector, Sense and Antisense Oligos) used to generate this Insert, if known:</span>
								</td>
							</tr>
				
							<TR>
								<TD style="white-space:nowrap; padding-left:20px;">
									Sense Oligo ID
								</TD>
				
								<TD>
									<input type="text" onKeyPress="return disableEnterKey(event);" size="5" id="insertSenseOligo" name="sense_oligo" style="margin-left:10px;" VALUE="<?php echo $senseID; ?>">
								</TD>
							</TR>
				
							<TR>
								<TD style="white-space:nowrap; padding-left:20px;">
									AntiSense Oligo ID
								</TD>
				
								<TD>
									<input type="text" onKeyPress="return disableEnterKey(event);" size="5" style="margin-left:10px;" id="insertAntisenseOligo" name="antisense_oligo" VALUE="<?php echo $antisenseID; ?>">
								</TD>
							</TR>
				
							<TR>
								<TD style="white-space:nowrap; padding-left:20px;">
									Insert Parent Vector ID
								</TD>
				
								<TD>
									<input type="text" onKeyPress="return disableEnterKey(event);" size="5" style="margin-left:10px;" id="insertParentVector" name="insert_parent_vector" VALUE="<?php echo $pivID; ?>">
								</TD>
							</TR>
						</TABLE>
					</TD>
				</TR>

				<TR>
					<td colspan="2">
						<input type="submit" name="preload" value="Continue" style="margin-top:5px; margin-left:2px;" onClick="document.getElementById('cancel_set').value=0; document.getElementById('navigate_away').value=0;">
						<?php
							// if returning to view from a later stage, add 'Cancel' button
							if (isset($_GET["rID"]))
							{
								?>
									&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost'); document.getElementById('navigate_away').value=0;">

									<INPUT type="hidden" ID="cancel_set" value="0">
								<?php
							}
						?>
					</td>
				</tr>
			</TABLE>
		</FORM>
		<?php
	}

/* Aug. 27, 2010: obsolete??
	 // Print Cell Line creation page that looks and behaves like Vector.  Offer a choice between parent and stable cell line, and display different forms depending on the selection. (2006-04-06)
	function printForm_CellLine_TypeChoice($view, $type)
	{
		global $conn;

		echo "<script type=\"text/javascript\">\n";
		echo "function dummysubmit( formname )\n";
		echo "{\n";
			echo "var getformid = document.getElementById( formname );\n";
			
			echo "getformid.submit();\n";
		echo "}\n";
		echo "</script>\n";
		
		$cell_line_types = array();
		?>
			<form method="get" id="cellline_type_form_id" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
			<input type="hidden" name="View" value="<?php echo $view; ?>">
			<input type="hidden" name="Type" value="<?php echo $type; ?>">
				<table cellpadding="3" width="770px">
					<tr>
						<td>
							What type of Cell Line are you creating?
						</td>
					</tr>

					<tr> 
						<td>
							&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="Mod" value="<?php echo $this->CellLine_SubCategory_Name_ID["Parent"]; ?>" checked>Parent Cell Line<br>

							&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="Mod" value="<?php echo $this->CellLine_SubCategory_Name_ID["Stable"]; ?>">Stable Cell Line<br>
						</td>
					</tr>

					<!-- May 11/06, Marina -->
					<tr>
						<td>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<A href="Reagent/cell_assoc.png" onClick="return popup(this, 'diagram', '650', '725', 'yes')">Click here to view an illustration of cell line types and associations</A>
						<!-- <IMG width="500" height="500" src="vector_assoc.png" alt="Illustration"> -->
						</td>
					</tr>

					<tr>
						<td>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" name="Doestmatter" value="Next" onClick="dummysubmit('cellline_type_form_id')">
						</td>
					</tr>
				</table>
			</form>
		<?php
	}
*/

	/**
	 * Print form to input cell line general properties preloaded from parents at stable cell line creation.
	 *
	 * @param INT parent cell line ID
	 * @param INT parent vector ID
	 * @param boolean
	 * @param boolean
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	*/
	function printCellLineGeneralPropsForm($backfill_CellLine_ID=null, $backfill_Vector_ID=null, $attemptBackfill=false, $show=false)
	{
		global $cgi_path;

		$r_out = new Reagent_Output_Class();
		$gfunc_obj = new generalFunc_Class();
		$rfunc_obj = new Reagent_Function_Class();

		// May 5/08: Use a different prefix - make uniform for all reagent types and for dynamic features
		$genPrefix = "reagent_detailedview_";
		$genPostfix = "_prop";

		$r_type = "CellLine";
		$rTypeID = 4;

		$new_name = "";
		$rid_vector_tmp = -1;
		$rid_cellline_tmp = -1;
		$default_class = "Parent";

		$parent_CL_props = Array();
		$preload = false;

		$display = "none";
		$header = "<TR><TD><table style=\"text-align:center; white-space:nowrap;\" width=\"705px\"><tr><td style=\"font-size:12pt; color:#00C00D; font-weight:bold;\">ADD NEW CELL LINE</td></tr></table></TD></TR>";

		// differentiate between parent and stable
		if ($attemptBackfill)
		{
			// Parent information
			if ($backfill_CellLine_ID)
				$rid_cellline_tmp = $gfunc_obj->get_rid($backfill_CellLine_ID);
			else
				$rid_cellline_tmp = -1;
	
			if ($backfill_Vector_ID)
				$rid_vector_tmp = $gfunc_obj->get_rid($backfill_Vector_ID);
			else
				$rid_vector_tmp = -1;
	
			$default_class = "cDNA Stable";

			$namePropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

			$vector_name = $rfunc_obj->getPropertyValue($rid_vector_tmp, $namePropID);
			$cellline_name = $rfunc_obj->getPropertyValue($rid_cellline_tmp, $namePropID);

			if (strlen($vector_name) > 0 && strlen($cellline_name) > 0)
			{
				$new_name = $vector_name . " in " . $cellline_name;
			}

			$parent_CL_props[$namePropID] = $new_name;

			// Upload properties from parent cell line: species, developmental stage, selectable marker, tissue type, morphology
			$clSpeciesPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["species"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]);
			
			$parent_CL_Species = $rfunc_obj->getPropertyValue($rid_cellline_tmp, $clSpeciesPropID);

			$parent_CL_props[$clSpeciesPropID] = $parent_CL_Species;

			$clDevStagePropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["developmental stage"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]);

			$parent_CL_DevStage = $rfunc_obj->getPropertyValue($rid_cellline_tmp, $clDevStagePropID);

			$parent_CL_props[$clDevStagePropID] = $parent_CL_DevStage;

			// REMEMBER TO SWITCH THIS TO 'GROWTH PROPERTIES' CATEGORY WHEN GOING LIVE!!!!!!!!!!!!
			$clSelMarkerPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["selectable marker"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]);

			$parent_CL_SelMarker = $rfunc_obj->getPropertyValue($rid_cellline_tmp, $clSelMarkerPropID);
			$parent_CL_props[$clSelMarkerPropID] = $parent_CL_SelMarker;

			$clMorphologyPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["morphology"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]);

			$parent_CL_Morphology = $rfunc_obj->getPropertyValue($rid_cellline_tmp, $clMorphologyPropID);

			$parent_CL_props[$clMorphologyPropID] = $parent_CL_Morphology;

			$clTissueTypePropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["tissue type"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]);

			$parent_CL_TissueType = $rfunc_obj->getPropertyValue($rid_cellline_tmp, $clTissueTypePropID);

			$parent_CL_props[$clTissueTypePropID] = $parent_CL_TissueType;

			$display = "inline";
			
			$header = "";
		}

		// pass parent IDs as hidden form values
		echo "<input type=\"hidden\" name=\"assoc_parent_cell_line_prop\" value=\"" . $backfill_CellLine_ID . "\">";
		echo "<input type=\"hidden\" name=\"assoc_cell_line_parent_vector_prop\" value=\"" . $backfill_Vector_ID . "\">";

// Removing Nov. 24, 2010 - seems obsolete
// 		// Apr 07/06, Marina
// 		if ($rid_cellline_tmp > 0)
// 		{
// 			// Match background property names to their database ids and retrieve their values
// 			$back_cellline_prop_ar = $this->backFill($rid_cellline_tmp, $cl_name);
// 			$back_vector_prop_ar = $this->backFill($rid_vector_tmp, $cl_name);
// 		}

		?><FORM method="POST" ENCTYPE="multipart/form-data" NAME="createReagentForm<?php echo $r_type; ?>" action="<?php echo $cgi_path . "create.py"; ?>">

			<!-- Pass user info to Python as hidden form value - Aug 21/07 -->
			<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
		
			<INPUT TYPE="hidden" NAME="reagent_type_hidden" VALUE="<?php echo $r_type; ?>">
			<?php
				if ($attemptBackfill)
				{
					?>
					<INPUT type="hidden" name="INPUT_CELLLINE_info_cellline_id_prop" value="<?php echo $backfill_CellLine_ID; ?>">

					<INPUT type="hidden" name="INPUT_CELLLINE_info_vector_id_prop" value="<?php echo $backfill_Vector_ID; ?>">
					<?php
				}
			?>
			<TABLE ID="cell_line_general_props" NAME="cellLineCreate" style="display:<?php echo $display; ?>; font-size:10pt; width:750px;" cellpadding="4" cellspacing="4"><?php

				if (isset($_GET["rID"]))
				{
					?><INPUT TYPE="hidden" NAME="reagent_id_hidden" VALUE="<?php echo $_GET["rID"]; ?>"><?php
				}

				echo $header;

				// find the attributes of this reagent type
				$categories = $rfunc_obj->findAllReagentTypeAttributeCategories($rTypeID);
	
				foreach ($categories as $categoryID => $category)
				{
					$rTypeAttributes = $rfunc_obj->getReagentTypeAttributesByCategory($_SESSION["ReagentType_Name_ID"][$r_type], $categoryID);

					if (count($rTypeAttributes) == 0)
						continue;

					$catAlias = $_SESSION["ReagentPropCategory_ID_Alias"][$categoryID];

					echo "<TR>";
						echo "<TD>";
							echo "<table style=\"background-color:#FFFFFF;\">";
								// Aug. 19/09: Changed categoryID to catAlias, since that is also the name of the table in addNewReagentType() and JS was confusing between the two
								echo "<tr>";
									echo "<td style=\"font-weight:bold; color:blue; padding-top:2px;\">";
										echo "<IMG id=\"" . $catAlias . "_expand_img_" . $r_type . "\" SRC=\"pictures/arrow_collapse.gif\" WIDTH=\"20\" HEIGHT=\"15\" BORDER=\"0\" ALT=\"plus\" class=\"menu-expanded\" style=\"display: inline\" onClick=\"showHideCategory('" . $catAlias . "', '" . $r_type . "');\">";

										echo "<IMG id=\"" . $catAlias . "_collapse_img_" . $r_type . "\" SRC=\"pictures/arrow_expand.gif\" WIDTH=\"40\" HEIGHT=\"34\" BORDER=\"0\" ALT=\"plus\" class=\"menu-collapsed\" style=\"display: none\" onClick=\"showHideCategory('" . $catAlias . "', '" . $r_type . "');\">";

										echo "<span id=\"section_title_" . $catAlias . "\">" . $category . "</span>";
									echo "</TD>";
								echo "</TR>";
	
								echo "<tr>";
									echo "<td style=\"padding-left:3px;\">";
										echo "<table ID=\"category_" . $catAlias . "_section_" . $r_type . "\" cellpadding=\"4\" cellspacing=\"2\" style=\"background-color:#FFFFFF; margin-top:4px;\">";

										foreach ($rTypeAttributes as $attrID => $cProp)
										{
											$propDescr = $cProp->getPropertyDescription();
											$propAlias = $cProp->getPropertyAlias();
											$propName = $cProp->getPropertyName();
											$propCategory = $cProp->getPropertyCategory();

											// May 5, 2010
											$p_id = $_SESSION["ReagentProp_Name_ID"][$propName];
											$c_id = $_SESSION["ReagentPropCategory_Name_ID"][$propCategory];
											$pID = $rfunc_obj->getPropertyIDInCategory($p_id, $c_id);

											echo "<TR style=\"background-color:#F5F5DC;\">";

												echo "<TD style=\"padding-left:15px; padding-right:15px; font-size:8pt; width:100px; font-weight:bold; white-space:nowrap;\">" . $propDescr;

												$tmp_pc_id = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$propName], $_SESSION["ReagentPropCategory_Name_ID"][$propCategory]);

												if ( ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["project id"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || (strcasecmp($propName, "packet id") == 0) || (strcasecmp($propName, "owner") == 0) || ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["status"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || (strcasecmp($propName, "Cell Line Type") == 0))
												{
													echo "<span style=\"font-size:9pt; color:#FF0000; font-weight:bold; margin-left:5px;\">*</span></TD>";

													echo "<INPUT TYPE=\"hidden\" NAME=\"" . $r_type . "_mandatoryProps[]\" VALUE=\"" . $attrID . "\">";
												}
												else if ($tmp_pc_id == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["alternate id"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]))
												{
													echo "<BR><BR><span style=\"font-size:8pt;\">For 'Other', please enter<BR> the database name and<BR> numeric identifier, separated<BR> by semicolon, in the textbox<BR> (e.g. 'IMAGE:123456')</span></TD>";
												}
												else
													echo "</TD>";

												echo "<TD style=\"font-size:8pt; padding-left:10px; width:630px;\">";

												// Prefill some of the default properties
												if (strcasecmp($propName, "Cell Line Type") == 0)
												{
													$r_out->print_property_final($genPrefix . $r_type . "_" . $catAlias . "_:_" . $propAlias . $genPostfix, $propName, $default_class, "", true, $propCategory, "Preview", "", $r_type, true, "", 0, 0);
												}
												else if (strcasecmp($propName, "Status") == 0)
												{
													$r_out->print_property_final($genPrefix . $r_type . "_" . $catAlias . "_:_" . $propAlias . $genPostfix, $propName, "In Progress", "", true, $propCategory, "Preview", "", $r_type, false, "", 0, 0);
												}
												else
												{
													if (in_array($pID, array_keys($parent_CL_props)))
													{
														$preload = true;
														$pVal = $parent_CL_props[$pID];
													}
													else
													{
														$preload = false;
														$pVal = "";
													}

													$r_out->print_property_final($genPrefix . $r_type . "_" . $catAlias . "_:_" . $propAlias . $genPostfix, $propName, $pVal, "", true, $propCategory, "Preview", "", $r_type, false, "", 0, 0, false, false, false, $preload);
												}

												echo "</TD>";
											echo "</TR>";
										}

										echo "</TABLE>";
									echo "</TD>";
								echo "</TR>";
							echo "</TABLE>";
						echo "</TD>";
					echo "</TR>";
				}

				?><TR>
					<TD colspan="2"><BR>
						<INPUT TYPE="submit" name="create_reagent" value="Create" onClick="if (document.getElementById('cancel_set')) {document.getElementById('cancel_set').value=0}; document.pressed = this.value; selectAllPropertyValues(true); return checkMandatoryProps('<?php echo str_replace("'", "\'", $r_type); ?>'); selectAllPropertyValues(true);">
						<?php
							if (isset($_GET["rID"]))
							{
								?>&nbsp;<input type="submit" name="cancel_creation" value="Cancel Creation" onClick="document.getElementById('cancel_set').value=1; return confirm('Cancel reagent creation?\n All input will be lost')">
	
								<INPUT type="hidden" ID="cancel_set" value="0"><?php
							}
						?>
					</TD>
				</TR>
			</TABLE>
		</FORM>
		<?php
	}


	// Now this function only applies to Stable Cell Lines - outputs general and parent properties form
	/**
	 * Stable Cell Line creation - save parents and preload their properties.
	 * Modified April 7/07, Marina
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	*/
	function process_CellLine_TypeChoice()
	{
		global $cgi_path;

		echo "<P><CENTER><SPAN style=\"font-size:12pt; color:#00C00D; font-weight:bold;\">ADD NEW CELL LINE</SPAN></CENTER><BR>";

		// Parents
		$parent_vector_id = $_GET["PV"];	// e.g. V12
		$parent_cellline_id = $_GET["CL"];	// e.g. C12

		// Output parents here to avoid nested forms
		$this->printForm_CellLine_Parents($parent_vector_id, $parent_cellline_id);
		
		$backfill_Vector_ID = $this->gfunc_obj->get_rid($parent_vector_id);
		$backfill_CellLine_ID = $this->gfunc_obj->get_rid($parent_cellline_id);		// db ID, e.g. 4527

		$this->printCellLineGeneralPropsForm($parent_cellline_id, $parent_vector_id, true, true);
	}
	

	/**
	 * Print form to input cell line parents at stable cell line creation.
	 *
	 * @param INT parent vector ID
	 * @param INT parent cell line ID
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	*/
	function printForm_CellLine_Parents($backfill_Vector_ID, $backfill_CellLine_ID)
	{
		global $cgi_path;

		$currUserName = $_SESSION["userinfo"]->getDescription();

		echo "<form action=\"" . $cgi_path . "preload.py\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"reagent_type_hidden\" value=\"" . $_GET["Type"] . "\">";
		echo "<input type=\"hidden\" name=\"subtype_hidden\" value=\"" . $_GET["Sub"] . "\">";

		if (isset($_GET["rID"]))
			$rID_tmp = $_GET["rID"];
			?>
			<input type="hidden" name="reagent_id_hidden" value="<?php echo $rID_tmp; ?>">

			<!-- Pass user info to Python as hidden form value - Aug 21/07 -->
			<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

			<table width="750px" cellpadding="2" border="1" frame="box" rules="none" style="margin-left:10px; margin-right:15px;">
				<th colspan="3" style="color:#0000CD; font-weight:bold; text-align:left">Parent Information:</th>

				<tr>
					<td nowrap width="150px">
						Parent Cell Line ID
					</td>

					<td>
						<input type=text name="INPUT_CELLLINE_info_cellline_id_prop" value="<?php echo $backfill_CellLine_ID; ?>">
					</td>
				</tr>
			
				<tr>
					<td nowrap>
						Parent Vector ID
					</td>
	
					<td>
						<input type=text name="INPUT_CELLLINE_info_vector_id_prop" value="<?php echo $backfill_Vector_ID; ?>">
					</td>
				</tr>

				<tr>
					<td>
						<input type="submit" name="change_parents" value="Change">
					</td>
				</tr>
			
			</table>
		<?php

		// close parents form
		echo "</form>";
	}


	/**
	 * Fetch properties that should be preloaded from parents at stable cell line creation (retrieve specific property values of the given parent IDs and pass to print function).
	 *
	 * @param INT
	 * @param Array
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 * @deprecated
	*/
// 	function backFill($rid, $expectedValues)
// 	{
// // print_r($expectedValues);
// 		global $conn;
// 
// 		$temp_ar = array();
// 		$property_set = "('";
// 		$setcount = 0;
// 		
// 		// Make a distinction between antibiotic resistance for vectors and resistance marker for cell lines - Marina, April 9/06
// 
// 		// November 24, 2010: Antibiotic resistance has long ago been changed to selectable marker!!!
// /*
// 		$antibio_ar = array();
// 		$resmark_ar = array();	// April 9/06*/
// 		
// 		foreach( $expectedValues as $name => $post_name )
// 		{
// 			$property_set = $property_set . $_SESSION["ReagentProp_Name_ID"][ $name ] . "','";
// 			$temp_ar[ $_SESSION["ReagentProp_Name_ID"][ $name ] ] = "";
// 			$setcount++;
// 		}
// 
// 		$property_set = $this->gfunc_obj->reset_set( $setcount , $property_set );
// 		$expectedValues = $temp_ar;
// 		
// 		$backfill_rs = mysql_query("SELECT * FROM `ReagentPropList_tbl` WHERE `status`='ACTIVE' AND `reagentID`='" . $rid . "' AND `propertyID` IN " . $property_set . "", $conn ) or die("FAILURE IN REAGENT_CREATOR_CLASS.backFill(1): " . mysql_error());
// 
// 		while( $backfill_ar = mysql_fetch_array( $backfill_rs, MYSQL_ASSOC ) )
// 		{
// 			if ($backfill_ar["propertyID"] == $_SESSION["ReagentProp_Name_ID"]["antibiotic resistance"])
// 			{
// 				$antibio_ar[] = $backfill_ar[ "propertyValue" ];
// 			}
// 			else if ($backfill_ar["propertyID"] == $_SESSION["ReagentProp_Name_ID"]["resistance marker"])
// 			{
// 				$resmark_ar[] = $backfill_ar[ "propertyValue" ];
// 			}
// 			else
// 			{
// 				$expectedValues[ $backfill_ar["propertyID"] ] = $backfill_ar["propertyValue"];
// 			}
// 		}
// 		
// // 		$expectedValues[ $_SESSION["ReagentProp_Name_ID"]["antibiotic resistance"] ] = $antibio_ar;
// // 		$expectedValues[ $_SESSION["ReagentProp_Name_ID"]["resistance marker"] ] = $resmark_ar;
// 
// 		return $expectedValues;
// 	}
}

?>
