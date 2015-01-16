<?php
/**
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
* Contains output functions for Reagent module
*
* @author John Paul Lee @version 2005
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
* @package Reagent
*
* @copyright  2005-2010 Pawson Laboratory
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*
*/
class Reagent_Output_Class
{
	/**
	 * @var Reagent_Function_Class
	 * Helper object (instance of Reagent_Function_Class)
	*/
	var $rfunc_obj;

	/**
	 * Zero-argument constructor
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	*/
	function Reagent_Output_Class()
	{
		$this->rfunc_obj = new Reagent_Function_Class();
	}


	/**
	 * Output all currently available reagent types in a list (used in 'Search Reagent Types')
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2009-06-03
	 *
	*/
	function printReagentTypesList()
	{
		?>
		<SELECT id="reagentTypes" name="reagentType" onChange="showReagentSubtype()">
			<OPTION SELECTED value="default">Select reagent type</OPTION>
			<?php
				$rTypeNames = array_keys($_SESSION["ReagentType_Name_ID"]);
				sort($rTypeNames, SORT_STRING);

				foreach ($rTypeNames as $key => $r_type)
				{
					echo "<OPTION VALUE=\"" . $r_type . "\">";
			
					if ($r_type == "CellLine")
						echo "Cell Line";
					else
						echo $r_type;
			
					echo "</OPTION>";
				}
			?>
		</SELECT>
		<?php
	}


	/**
	 * Print reagent statistics (number of reagents of each type available in OpenFreezer)
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2010-07-16
	 *
	*/
	function printReagentStats()
	{
		global $conn;

		$total = 0;
		?>
		<table width="950px">
			<tr>
				<td style="font-size:9pt; color:#238E23; font-weight:bold; padding-bottom:10px; padding-left:25px;">
					Statistics of reagents currently stored in OpenFreezer
				</td>
			</tr>

			<TR>
				<td>
					<table>
						<tr>
							<td style="font-size:9pt; padding-left:25px; padding-bottom:10px;" colspan="3">
								Number of reagents in the repository:
							</td>
						</tr>
					<?php
						foreach ($_SESSION["ReagentType_Name_ID"] as $rTypeName => $rTypeID)
						{
							echo "<tr>";
		
							echo "<TD style=\"font-size:9pt; padding-left:25px; width:100px;\">" . $rTypeName . ": </td>";
		
							$rcount_rs = mysql_query("SELECT COUNT(reagentID) as rcount FROM Reagents_tbl WHERE reagentTypeID='" . $rTypeID . "' AND status='ACTIVE'");
		
							if ($rcount_ar = mysql_fetch_array($rcount_rs, MYSQL_ASSOC))
							{
								$total += $rcount_ar["rcount"];
								echo "<TD style=\"text-align:right; font-size:9pt;\">" . number_format($rcount_ar["rcount"]) . "</td>";
							}
		
						}

						echo "<TR><TD style=\"padding-top:5px; font-weight:bold; color:#238E23; font-size:9pt; padding-left:25px; width:100px;\">Total:</TD><TD style=\"text-align:right; border-top:1px solid black; padding-top:5px; font-weight:bold; font-size:9pt;\">" . number_format($total) . "</td></tr>";
					?>
					</table>
				</td>
			</tr>
		</table>
		<?php
	}


	// June 10, 2011
	/**
	 * Print cell line biosafety statistics
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2011-06-10
	 *
	*/
	function printCellLineBiosafety()
	{
		global $conn;
		global $cgi_path;

		?><P><span class="linkShow" style="margin-left:30px; font-size:10pt;" onClick="document.cellLineBiosafetyForm.submit();">View All Cell Lines according to Biosafety Classification</SPAN>		
		<FORM name="cellLineBiosafetyForm" METHOD="POST" ACTION="<?php echo $cgi_path . "cell_line_stats.py"; ?>"></FORM><?php
	}


	/**
	 * Print general information on Reagent Tracker info page
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	*/
	function print_reagent_intro()
	{
		?>
		<table width="770px">
			<tr>
				<td colspan="3" style="color:#333333; font-size:18pt; font-weight:bold; text-align:center">
					REAGENT TRACKER
				</td>
			</tr>
			<tr>
				<td border="1" colspan="3" style="font-size:10pt; font-weight:bold; text-align:center; padding-top:10px;">
					This module is used to track various types of reagents available in the laboratory
				</td>
			</tr>

			<tr>
				<td border="1" colspan="3">
				<p><p>Currently tracks the following types of reagents:<br>
					<DL>
					<DT><font color="#004891"><u><b>Vectors</b></u>:</font><DD>
					<UL><li>Self-propagating circular DNA that is comprised of a backbone (parent vector) and may contain an insert.<br><br></LI></UL>
					<DT><font color="#004891"><u><b>Inserts</b></u>:</font>
					<DD><UL><li>Linear piece of DNA formed either by PCR, oligo hybridization, or restriction digestion of a parent vector.   The purpose of most inserts is to be subcloned into a vector for propagation and downstream applications.  The insert itself is often stored as an intermediate step in the cloning pathway.  Inserts can also be siRNAs.<br><br></LI></UL>
					<DT><font color="#004891"><u><b>Oligos</b></u>:</font>
					<DD><UL><li>Single stranded DNA usually less than 100 nucleotides that are used as PCR primers, sequencing primers, or hybridized to form an insert.<br><br></LI></UL>
					<DT><font color="#004891"><u><b>Cell lines</b></u>:</font>
					<DD><UL><li>Immortalized cells that are propagated within the laboratory.  The cell lines are classified as either parent cell lines or stable cell lines, where one or more vectors have been stably expressed within the cell line.</LI></UL>
					</DL>
				</td>
				</tr>
			<tr>
		</table>
		<?php
	}


	/**
	 * Print start or end position for a sequence feature
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param STRING $posFieldName Form input name, e.g. 'reagent_detailedview_selectable_marker_Ampicillin_start_139_prop'
	 * @param STRING $fAlias Internal feature alias (for form input) - e.g. 'selectable_marker'
	 * @param INT $fPosType Start or end position (1 = start, 0 = end)
	 * @param STRING $fValue Actual feature value, e.g. Ampicillin
	 * @param INT $posVal Actual position, e.g. 139
	 * @param boolean $readonly Not modifiable in some views, e.g. step 3 of Vector creation, where cloning site positions are loaded from parents and should not be changed
	*/
	function print_feature_position($posFieldName, $fAlias, $fPosType, $fValue, $posVal, $readonly)
	{
		$rdonly = ($readonly === true) ? "READONLY" : "";
		$className = ($readonly === true) ? "input_disabled" : "input_normal";

		if ($fPosType == 1)	// start
		{
			echo "<input type=\"text\" class=\"" . $className . "\" style=\"font-size:7pt;\"  size=\"5\" id=\"" . $fAlias . "_start\" name=\"" . $posFieldName . "\" value=\"" . (($posVal >= 0) ? $posVal : "") . "\" " . $rdonly . "></input>";
		}
		else	// end
		{
			echo "<input type=\"text\" class=\"" . $className . "\" size=\"5\" style=\"font-size:7pt;\"  id=\"" . $fAlias . "_end\" name=\"" . $posFieldName . "\" value=\"" . (($posVal >= 0) ? $posVal : "") . "\" " . $rdonly . "></input>";
		}

		if ($fAlias == $_SESSION["ReagentProp_Name_Alias"]["cdna insert"])
			echo "&nbsp;<span style=\"font-size:10pt; color:#FF0000; font-weight:bold\">*</span>";
	}


	/**
	 * Print sequence feature direction (orientation - 'forward' or 'reverse')
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param STRING $dirFieldName Form input name, e.g. 'reagent_detailedview_selectable_marker_Ampicillin_start_139_prop'
	 * @param STRING $fAlias Internal feature alias (for form input) - e.g. 'selectable_marker'
	 * @param STRING $fValue Actual feature value, e.g. Ampicillin
	 * @param STRING $fDir Actual direction - either 'forward' or 'reverse'
	*/
	function print_feature_direction($dirFieldName, $fAlias, $fValue, $fDir)
	{
		if (!$fDir || ($fDir == "") || (sizeof($fDir) == 0) || (strcasecmp($fDir, "forward") == 0))
		{
			$fwd_checked = "checked";
			$rev_checked = "";
		}
		else
		{
			$fwd_checked = "";
			$rev_checked = "checked";
		}

		echo "<INPUT TYPE=\"radio\" NAME=\"" . $dirFieldName . "\" " . $fwd_checked . " VALUE=\"forward\" style=\"font-size:7pt\">Forward</INPUT>";

		echo "<INPUT TYPE=\"radio\" NAME=\"" . $dirFieldName . "\" " . $rev_checked . " VALUE=\"reverse\" style=\"font-size:7pt\">Reverse</INPUT>";
	}


	/**
	 * Output a feature descriptor (only two now: Tag Position for Tag or Expression System for Promoter)
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2008-05-13
	 *
	 * @param STRING $prefix Used in form input name, e.g. 'reagent_detailedview'
	 * @param STRING $postfix Used in form input name, e.g. '_prop'
	 * @param STRING $fName Name of the feature associated with this descriptor (either 'tag' or 'promoter' currently)
	 * @param STRING $fDescr Actual **value** of the **descriptor** (e.g. 'Internal', 'C-Terminus', etc. for Tag Position, or 'Bacterial', 'Mammalian', etc. for Expression System)
	 * @param STRING $featureValue Value of the feature (tag or promoter, NOT the descriptor!! - e.g. 'His' tag or promoter 'T7') - needed when there are multiple features of the same type in one sequence (three promoters and four tags - need to know which descriptor belongs to which feature at which position)
	 * @param INT $fStart Start position of the feature
	 * @param INT $fEnd End position of the feature
	 * @param INT $rID Internal database reagent ID
	 * @param boolean $modify Mode ('Modify' or 'View') - passed on to print_property_final() function
	 * @param STRING $type_of_output Argument for print_property_final()
	 * @param STRING $subtype Argument for print_property_final()
	 * @param STRING $rType Reagent type (used to retrieve attribute ID)
	 * @param boolean $readonly
	*/
	function print_feature_descriptor($prefix, $postfix, $fName, $fDescr, $featureValue, $fStart, $fEnd, $rID, $modify= false, $type_of_output = "", $subtype = "", $rType="", $readonly=false)
	{
		$rfunc_obj = new Reagent_Function_CLass();

		// Find the descriptor that corresponds to the given feature
		$f_descriptors = $rfunc_obj->getFeatureDescriptors();

		$aDescr = $f_descriptors[$fName];
		$fAlias = $_SESSION["ReagentProp_Name_Alias"][$aDescr];

		// Oct. 26/09
		if ($rID <= 0)
			$POST_VAR_NAME = $prefix . $rType . "_" . $fAlias . "_:_" . $featureValue . "_start_" . $fStart . "_end_" . $fEnd . $postfix;
		else
			$POST_VAR_NAME = $prefix . $fAlias . "_:_" . $featureValue . "_start_" . $fStart . "_end_" . $fEnd . $postfix;

		// Oct. 22/09
		if ($rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$rType], "protein sequence", $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]))
			$category = "Protein Sequence Features";
		else if ($rfunc_obj->hasAttribute($_SESSION["ReagentType_Name_ID"][$rType], "rna sequence", $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]))
                        $category = "RNA Sequence Features";
		else
			$category = "DNA Sequence Features";

		$fID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$fName], $_SESSION["ReagentPropCategory_Name_ID"][$category]);

		// make descriptor category also 'features' for simplicity
		switch ($fName)
		{
			case 'tag':
				$this->print_property_final($POST_VAR_NAME, "tag position",  $fDescr, $rID, $modify, $category, $type_of_output, $subtype, $rType, $readonly);
			break;

			case 'promoter':
				$this->print_property_final($POST_VAR_NAME, "expression system",  $fDescr, $rID, $modify, $category, $type_of_output, $subtype, $rType, $readonly);
			break;
		}
	}


	/**
	 * Common gateway for outputting reagents in a uniform fashion
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING $POST_VAR_NAME Name representing the given property on the form (serves as unique HTML identifier)
	 * @param STRING $type Name of the property to be printed
	 * @param MIXED $value Property value - alphanumeric data type specific to that property (may be a string, an integer, a float, etc.)
	 * @param INT $rid Reagent ID
	 * @param boolean $modify Differentiate between 'view' and 'modify' modes
	 * @param STRING $category Category in which this property is defined for the current reagent type (added July 9/09)
	 * @param STRING $type_of_output Passed on to get_Special_Column_Type() as an argument
	 * @param STRING $subtype Reagent subtype (needed for different Vector subtypes at creation as argument to Javascript, to distinguish when a form contains multiple fields with the same name but some are hidden - added May 28/07)
	 * @param STRING $rType Reagent type (added Feb. 5/08)
	 * @param boolean $readonly Indicates whether this property should be printed as a read-only or modifiable field
	 * @param STRING $descriptor Feature descriptor (if this property is a feature)
	 * @param INT $start Start position (applies to features only)
	 * @param INT $end End position (applies to features only)
	 * @param boolean $isProtein Is this property a Protein sequence property/feature (determines units - nt or aa) - default 'false'
	 * @param boolean $isRNA Is this property a RNA sequence property/feature - default 'false'
	 * @param boolean $isDNA Is this property a DNA sequence property/feature - default 'true'
	 * @param boolean $preload If this property is preloaded from parent reagent (e.g. when changing Cell Line parents), change font colour
	 *
	 * @see get_Special_Column_Type()
	*/
	function print_property_final($POST_VAR_NAME, $type, $value, $rid, $modify = false, $category="", $type_of_output = "", $subtype = "", $rType="", $readonly=false, $descriptor="", $start=0, $end=0, $isProtein=false, $isRNA=false, $isDNA=true, $preload=false)
	{
		global $conn;
		global $cgi_path;

		if ($isProtein)
			$units = "aa";
		else
			$units = "nt";

//echo $POST_VAR_NAME;

		$functionerror = "print_property_final(";

		$genfunc_obj = new generalFunc_Class();
		$rfunc_obj = new Reagent_Function_CLass();

		$lims_id = $genfunc_obj->getConvertedID_rid($rid);
		$rTypeID = $genfunc_obj->get_typeID($lims_id);

		$outputer_obj = new ColFunctOutputer_Class();

		if (!$rTypeID || ($rTypeID < 0) || strlen($rTypeID) == 0)	// creation
		{
			$rTypeID = $_SESSION["ReagentType_Name_ID"][$rType];
		}

		// May 12/06, Marina
		if (isset($_GET["error_anchor"]))
		{
			$font_color = "FF0000";
		}
// change jan. 20, 2010
// 		elseif (isset($_POST["change_vp_id"]) || isset($_POST["change_cl_id"]))
// 		{
// 			$font_color = "0000FF";
// 		}
		elseif ($preload)
		{
			$font_color = "0000FF";
		}
// 		else
// 		{
// 			$font_color = "000000";
// 		}

		// Feb. 25/08: Extract prefix and postfix to generate a name for the start and stop input fields
		if ($rid)
			$rType = $_SESSION["ReagentType_ID_Name"][$rfunc_obj->getType($rid)];

		$prop_alias = $_SESSION["ReagentProp_Name_Alias"][$type];

		// March 31, 2010
		$propCatID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$type], $_SESSION["ReagentPropCategory_Name_ID"][$category]);

		$aliasIndex = strpos($POST_VAR_NAME, $prop_alias) + strlen($prop_alias);

		$prefix = "reagent_detailedview_";
		$postfix = "_prop";

		$attrID = $rfunc_obj->getRTypeAttributeID($rTypeID, $type, $_SESSION["ReagentPropCategory_Name_ID"][$category]);

		switch ($type)
		{
			case "accession number":
				if ($modify)
				{
					if ($this->isDropdownProperty($type, $_SESSION["ReagentPropCategory_Name_ID"][$category], $rTypeID))
					{
						if (is_array($value) && (count($value) > 0))
						{
							foreach ($value as $key => $val)
							{
								echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $val . "\" name=\"" . $POST_VAR_NAME . "\"><BR>";
							}
						}
						else
						{
							echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $value . "\" name=\"" . $POST_VAR_NAME . "\"><BR>";
						}
					}
					else
					{
						echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $value . "\" name=\"" . $POST_VAR_NAME . "\">";
					}

					// June 15, 2010
					echo "&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/link5.png\" WIDTH=\"18\" HEIGHT=\"8\" ALT=\"link_icon\" style=\"cursor:auto\" onmouseover=\"return overlib('You may link out to an external website by entering a complete URL in the textbox, e.g. http://www.mysite.com', CAPTION, 'Hyperlink', STICKY);\">";

					break;
				}

				// Make the accession value a hyperlink unless the value is "in house"
// 				if (strcasecmp($value, "in house" ) != 0)
// 				{
// 					echo "<a href=\"http://www.ncbi.nlm.nih.gov/entrez/viewer.fcgi?cmd=Search&db=nuccore&val=" . $value . "&doptcmdl=GenBank&cmd=retrieve\" target=\"_blank\">" . $value . "</a>";

					// Modified March 19/09: show multiple accessions
					echo $this->get_Special_Column_Type("accession number", $category, $rid, "None");
// 				}
// 				else
// 				{
// 					echo $value;
// 				}
			break;


			// March 20/07, Marina
			case "ensembl gene id":
			
				if ($modify)
				{
					// June 15, 2010: this shouldn't be a dropdown, not for gene id
// 					if ($this->isDropdownProperty($type, $_SESSION["ReagentPropCategory_Name_ID"][$category], $rTypeID))
// 					{
// 						echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $value . "\" name=\"" . $POST_VAR_NAME . "\">";
// 					}
// 					else
// 					{
						echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $value . "\" name=\"" . $POST_VAR_NAME . "\">";
// 					}

					// June 15, 2010 - should always be a hyperlink but don't think it's implemented for all reagent types
					echo "&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/link5.png\" WIDTH=\"18\" HEIGHT=\"8\" ALT=\"link_icon\" style=\"cursor:auto\" onmouseover=\"return overlib('You may link out to an external website by entering a complete URL in the textbox, e.g. http://www.mysite.com', CAPTION, 'Hyperlink', STICKY);\">";

					break;
				}
				
				// Link gene ID to appropriate species
				$species = $rfunc_obj->getPropertyValue($rid, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["species"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]));

				switch (strtolower($species))
				{
					case "homo sapiens":
						echo "<a href=\"http://www.ensembl.org/Homo_sapiens/geneview?gene=" . $value . "\" target=\"_blank\">" . $value . "</a>";
					break;
					
					case "mus musculus":
						echo "<a href=\"http://www.ensembl.org/Mus_musculus/geneview?gene=" . $value . "\" target=\"_blank\">" . $value . "</a>";
					break;
					
					case "danio rerio":
						echo "<a href=\"http://www.ensembl.org/Danio_rerio/geneview?gene=" . $value . "\" target=\"_blank\">" . $value . "</a>";
					break;
					
					case "rattus norvegicus":
						echo "<a href=\"http://www.ensembl.org/Rattus_norvegicus/geneview?gene=" . $value . "\" target=\"_blank\">" . $value . "</a>";
					break;
					
					default:
						echo $value;
					break;
				}
				
			break;
			
			// March 20/07, Marina
			case "official gene symbol":
			
				if ($modify)
				{
					if ($this->isDropdownProperty($type, $_SESSION["ReagentPropCategory_Name_ID"][$category], $rTypeID))
					{
						echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $value . "\" name=\"" . $POST_VAR_NAME . "\">";
					}
					else
					{
						echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $value . "\" name=\"" . $POST_VAR_NAME . "\">";
					}

					if ($rfunc_obj->isHyperlink($attrID))
						echo "&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/link5.png\" WIDTH=\"18\" HEIGHT=\"8\" ALT=\"link_icon\" style=\"cursor:auto\" onmouseover=\"return overlib('You may link out to an external website by entering a complete URL in the textbox, e.g. http://www.mysite.com', CAPTION, 'Hyperlink', STICKY);\">";

					break;
				}

				echo $value;
				
			break;

			case "alternate id":
				if ($modify)
				{
					$error_tmp = $this->print_Set_Extended_Checkbox("alternate id", $POST_VAR_NAME , $value, $rid, $_SESSION["ReagentType_ID_Prefix"][$rTypeID], $category, $rTypeID);

					break;
				}

				echo $this->get_Special_Column_Type("alternate id", $category, $rid, "None");

			break;
			
			case "entrez gene id":
			
				if ($modify)
				{
					if ($this->isDropdownProperty($type, $_SESSION["ReagentPropCategory_Name_ID"][$category], $rTypeID))
					{
						echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $value  . "\" name=\"" . $POST_VAR_NAME . "\">";
					}
					else
					{
						echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $value . "\" name=\"" . $POST_VAR_NAME . "\">";
					}

					// June 15, 2010
					echo "&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/link5.png\" WIDTH=\"18\" HEIGHT=\"8\" ALT=\"link_icon\" style=\"cursor:auto\" onmouseover=\"return overlib('You may link out to an external website by entering a complete URL in the textbox, e.g. http://www.mysite.com', CAPTION, 'Hyperlink', STICKY);\">";

					break;
				}
				
				// hyperlink to Entrez
				echo "<a href=\"http://www.ncbi.nlm.nih.gov/sites/entrez?Db=gene&Cmd=ShowDetailView&TermToSearch=" . $value . "\" target=\"_blank\">" . $value . "</a>";
				
			break;

			case "protein sequence":
				if ($readonly === true)
				{
					$readonly = "READONLY";
					$fillColor = "#E8E8E8";
				}
				else
				{
					$readonly = "";
					$fillColor = "#FFFFFF";
				}

				if ($modify)
				{
					// Added March 28/07, Marina: CREATION VIEW
					if (strlen($rid) == 0)
					{
						echo "<textarea " . $readonly . " rows=\"10\" cols=\"89\" id=\"protein_sequence_" . $rType . "\" name=\"" . $POST_VAR_NAME . "\" style=\"background-color:" . $fillColor . ";\">";

						$sequence = "";

						// Fetch the actual sequence identified by seqID
						$query = "SELECT `sequence` FROM `Sequences_tbl` WHERE `seqID`='" . $value . "'";
						$seq_rs = mysql_query($query, $conn) or die("Failure in sequence retrieval: " . $mysql_error());

						if ($seq_ar = mysql_fetch_array($seq_rs, MYSQL_ASSOC))
						{
							$sequence = $seq_ar["sequence"];
						}
						
						echo chunk_split($sequence, 10, " ");

							echo "</textarea>";
					}
					else
					{
						// Jan. 8/09: changed column count to 96
						echo "<textarea " . $readonly . "\" rows=\"12\" cols=\"96\" id=\"protein_sequence_" . $rType . "\" name=\"" . $POST_VAR_NAME . "\" style=\"padding-left:5px; background-color:" . $fillColor . "\">";

						echo chunk_split($value, 10, " ");

						echo "</textarea>";
					}
				}
				else
				{
					echo $this->get_Special_Column_Type($type, $category, $rid, $type_of_output);
				}
			break;

			// Aug. 13/09
			case "rna sequence":
				if ($readonly === true)
				{
					$readonly = "READONLY";
					$fillColor = "#E8E8E8";
				}
				else
				{
					$readonly = "";
					$fillColor = "#FFFFFF";
				}

				if ($modify)
				{
					// Added March 28/07, Marina: CREATION VIEW
					if (strlen($rid) == 0)
					{
						echo "<textarea " . $readonly . " rows=\"10\" cols=\"89\" id=\"rna_sequence_" . $rType . "\" name=\"" . $POST_VAR_NAME . "\" style=\"background-color:" . $fillColor . ";\">";
					
						$sequence = "";

						// Fetch the actual sequence identified by seqID
						$query = "SELECT `sequence` FROM `Sequences_tbl` WHERE `seqID`='" . $value . "'";
						$seq_rs = mysql_query($query, $conn) or die("Failure in sequence retrieval: " . $mysql_error());

						if ($seq_ar = mysql_fetch_array($seq_rs, MYSQL_ASSOC))
						{
							$sequence = $seq_ar["sequence"];
						}
						
						echo chunk_split($sequence, 10, " ");

						echo "</textarea>";
					}
					else
					{
						// Oct. 26/09: changed column count to 108
						echo "<textarea " . $readonly . "\" rows=\"12\" cols=\"108\" id=\"rna_sequence_" . $rType . "\" name=\"" . $POST_VAR_NAME . "\" style=\"padding-left:5px; background-color:" . $fillColor . "\">";

						$propID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["rna sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]);
				
						$rnaSeqRS = mysql_query("SELECT s.`sequence`, s.`start`, s.`end`, s.`length` FROM `Sequences_tbl` s, `ReagentPropList_tbl` r WHERE r.`reagentID`='" . $rid . "' AND r.`propertyID`='" . $propID . "' AND s.`seqID`=r.`propertyValue` AND r.`status`='ACTIVE' AND s.`status`='ACTIVE'", $conn) or die("Could not select sequence: " . mysql_error());
				
						if ($seqResult = mysql_fetch_array($rnaSeqRS, MYSQL_ASSOC))
						{
							$constRNASeq = $seqResult["sequence"];
							echo chunk_split($constRNASeq, 10, " ");
						}

						echo "</textarea>";
					}
				}
				else
				{
					echo $this->get_Special_Column_Type($type, $category, $rid, $type_of_output);
				}
			break;

			case "sequence":

				// May 2/08, Marina: Sequence can be disabled, depending on function argument
				if ($readonly === true)
				{
					$readonly = "READONLY";
					$fillColor = "#E8E8E8";
				}
				else
				{
					$readonly = "";
					$fillColor = "#FFFFFF";
				}

				if ($modify)
				{
					// Added March 28/07, Marina: CREATION VIEW
					if (strlen($rid) == 0)
					{
						// Nov. 17/08 - assign a different sequence ID for Oligo
						if ($_SESSION["ReagentType_ID_Name"][$rType] == "Oligo")
						{
							echo "<textarea " . $readonly . " rows=\"10\" cols=\"89\" id=\"oligo_dna_sequence\" name=\"" . $POST_VAR_NAME . "\" style=\"background-color:" . $fillColor . ";\">";
						}
						else
						{
							echo "<textarea " . $readonly . " rows=\"10\" cols=\"89\" id=\"dna_sequence_" . $rType . "\" name=\"" . $POST_VAR_NAME . "\" style=\"background-color:" . $fillColor . ";\">";
						}

						$sequence = "";

						// Fetch the actual sequence identified by seqID
						$query = "SELECT `sequence` FROM `Sequences_tbl` WHERE `seqID`='" . $value . "'";
						$seq_rs = mysql_query($query, $conn) or die("Failure in sequence retrieval: " . $mysql_error());

						if ($seq_ar = mysql_fetch_array($seq_rs, MYSQL_ASSOC))
						{
							$sequence = $seq_ar["sequence"];
						}
						
						echo chunk_split($sequence, 10, " ");

						echo "</textarea>";
					}
					else
					{
						// Jan. 8/09: changed column count to 96
						echo "<textarea " . $readonly . "\" rows=\"12\" cols=\"96\" id=\"dna_sequence_" . $rType . "\" name=\"" . $POST_VAR_NAME . "\" style=\"padding-left:5px; background-color:" . $fillColor . "\">";

						if (strlen(trim($value)) > 0)
						{
							echo $this->get_Special_Column_Type($type, $category, $rid, "modify");
						}

						echo "</textarea>";
					}
				}
				else
				{
					echo $this->get_Special_Column_Type($type, $category, $rid, $type_of_output);
				}
			break;
			
			case "protein translation":
				echo $this->get_Special_Column_Type($type, $category, $rid, $type_of_output);
			break;
			
			case "length":	
				echo $this->get_Special_Column_Type("length", $category, $rid, $type_of_output);
			break;
			
			// Restored March 30/07, Marina; updated April 6/07 - added "if modify" condition
			// August 13/07: making modifiable
 			case "packet id":
				if ($modify)
				{
					echo "<select id=\"values_" . $attrID . "\" size=\"1\" name=\"" . $POST_VAR_NAME . "\" style=\"font-size:7pt;\">";
						$error_tmp = $this->get_Special_Column_Type("packet", $category, $rid, "modify");
					echo "</select>";
					
					// May 11/08: Put warning here
					?>
						<BR><div NAME="warnings[]" id="warn_<?php echo $attrID; ?>" style="display:none; color:#FF0000">Please select a Project ID from the dropdown list</div>
					<?php
				}
				else
				{
					echo $this->get_Special_Column_Type("packet", $category, $rid, "preview");
				}
 			break;	

			case "5' cloning site":

				if ($modify)
				{
					// Aug. 5/08: 'Select' elements don't have a 'readonly' attribute, but 'disabled' prevents the element from being submitted with the form.  Solution: Keep 'disabled' here, AND EVERYWHERE this element is used, add a Javascript call on form submit that enables the 'select' box.
					$rdOnly = ($readonly === true) ? "DISABLED" : "";

					// if-else block added Feb. 25/08: Call different JS functions depending on view
					if (strlen($rid) <= 0)
					{
						// Creation - reagent ID unknown.  MUST BE MORE SPECIFIC WITH GATEWAY SITES (e.g. attB1, attL2, etc. instead of just attB, attL, etc.)
						echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier; color:" . $font_color . "\" id=\"fpcs_list_1\" name=\"" . $POST_VAR_NAME . "\" onChange=\"showHideFivePrimeOther('" . $subtype . "');\" " . $rdOnly . ">";
					}
					// March 5/08
					else if ($rType == $_SESSION["ReagentType_Name_ID"]["Insert"])
					{
						echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier; color:" . $font_color . "\" id=\"fpcs_list_1\" name=\"" . $POST_VAR_NAME . "\" onChange=\"showHideFivePrimeOther('" . $subtype . "');\" " . $rdOnly . ">";
					}
					// April 18/08
					else if ($subtype != "")
					{
						echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier; color:" . $font_color . "\" id=\"fpcs_list_" . $subtype . "\" name=\"" . $POST_VAR_NAME . "\" onChange=\"showHideFivePrimeOther('" . $subtype . "');\" " . $rdOnly . ">";
					}
					else
					{
						echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier; color:" . $font_color . "\" id=\"fpcs_list\" name=\"" . $POST_VAR_NAME . "\" onChange=\"showHideFivePrimeOther('" . $subtype . "');\" " . $rdOnly . ">";
					}

					echo "</select>";

					echo "<input type=\"hidden\" id=\"fpcs_val\" value=\"" . $value . "\"></input>";

					// DO NOT USE 'DISABLED'
					$disabled = ($readonly === true) ? "READONLY=\"true\"" : "";
					$inputColor = ($readonly === true) ? "#E9E9E9" : "";

					echo "&nbsp;&nbsp;<INPUT TYPE=\"TEXT\" onKeyPress=\"return disableEnterKey(event);\" style=\"font-size:8pt; display:none; background-color:" . $inputColor . "\" ID=\"fpcs_txt_" . $subtype . "\" NAME=\"5_prime_cloning_site_name_txt\" SIZE=\"20\" value=\" " . $value . "\"" . $disabled . "> </INPUT>";

					break;
				}

				echo $value;

				// Jan. 29/08: Show positions
				$pStart = $rfunc_obj->getStartPos($rid, $type, $_SESSION["ReagentProp_Name_ID"]["5' cloning site"], "");
				$pEnd = $rfunc_obj->getEndPos($rid, $type, $_SESSION["ReagentProp_Name_ID"]["5' cloning site"], "");

				if (($pStart > 0) && ($pEnd > 0))
				{
					echo "&nbsp; <span style=\"color:#006400\">(" . $units . " " . $pStart . "&nbsp;&#45;";
					echo "&nbsp;" . $pEnd . ")</span>";
				}
			break;

			case "3' cloning site":
				
				if ($modify)
				{
					// Aug. 5/08: 'Select' elements don't have a 'readonly' attribute, but 'disabled' prevents the element from being submitted with the form.  Solution: Keep 'disabled' here, AND EVERYWHERE this element is used, add a Javascript call on form submit that enables the 'select' box.
					$rdOnly = ($readonly === true) ? "DISABLED" : "";

					// if-else block added Feb. 25/08: Call different JS functions depending on view
					if (strlen($rid) <= 0)
					{
						echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier; color:" . $font_color . "\" id=\"tpcs_list_1\" name=\"" . $POST_VAR_NAME . "\" onChange=\"showHideThreePrimeOther('" . $subtype . "');\" " . $rdOnly . "></select>";
					}

					// March 5/08
					else if ($rType == $_SESSION["ReagentType_Name_ID"]["Insert"])
					{
						echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier; color:" . $font_color . "\" id=\"tpcs_list_1\" name=\"" . $POST_VAR_NAME . "\" onChange=\"showHideFivePrimeOther('" . $subtype . "');\" " . $rdOnly . ">";
					}

					// April 18/08
					else if ($subtype != "")
					{
						echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier; color:" . $font_color . "\" id=\"tpcs_list_" . $subtype . "\" name=\"" . $POST_VAR_NAME . "\" onChange=\"showHideFivePrimeOther('" . $subtype . "');\" " . $rdOnly . ">";
					}
					else
					{
						echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier; color:" . $font_color . "\" id=\"tpcs_list\" name=\"" . $POST_VAR_NAME . "\" onChange=\"showHideThreePrimeOther('" . $subtype . "');\" " . $rdOnly . "></select>";
					}

					echo "</select>";

					echo "<input type=\"hidden\" id=\"tpcs_val\" value=\"" . $value . "\"></INPUT>";

					// DO NOT USE 'DISABLED'
					$disabled = $rdOnly ? "READONLY=\"true\"" : "";
					$inputColor = $rdOnly ? "#E9E9E9" : "";

					echo "&nbsp;&nbsp;<INPUT TYPE=\"text\" onKeyPress=\"return disableEnterKey(event);\" NAME=\"3_prime_cloning_site_name_txt\" ID=\"tpcs_txt_" . $subtype . "\" SIZE=\"20\" style=\"display:none; font-size:8pt; background-color:" . $inputColor . ";\" value=\"" . $value . "\" " . $disabled . "></INPUT>";

					break;
				}

				echo $value;

				// Jan. 29/08: Show positions
				$pStart = $rfunc_obj->getStartPos($rid, $type, $_SESSION["ReagentProp_Name_ID"]["3' cloning site"], "");
				$pEnd = $rfunc_obj->getEndPos($rid, $type, $_SESSION["ReagentProp_Name_ID"]["3' cloning site"], "");

				if (($pStart > 0) && ($pEnd > 0))
				{
					echo "&nbsp; <span style=\"color:#006400\">(nt " . $pStart . "&nbsp;&#45;";
					echo "&nbsp;" . $pEnd . ")</span>";
				}
			break;
				
			case "5' linker":

				if ($modify)
				{
					echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" ID=\"fp_linker_prop\" value=\"" . $value . "\" name=\"" . $POST_VAR_NAME . "\">";

					break;
				}

				$tmp_linker = $this->get_Special_Column_Type("5' linker", $category, $rid, "None");
				echo $tmp_linker;

				// Feb. 13/08: Show positions
				$pStart = $rfunc_obj->getStartPos($rid, $type, $_SESSION["ReagentProp_Name_ID"]["5' linker"], "");
				$pEnd = $rfunc_obj->getEndPos($rid, $type, $_SESSION["ReagentProp_Name_ID"]["5' linker"], "");

				// Update March 3/09 - show linker IFF not empty
				if ($tmp_linker && (strlen($tmp_linker) > 0) && ($pStart >= 0) && ($pEnd >= 0))
				{
					echo "&nbsp; <span style=\"color:#006400\">(nt " . $pStart . "&nbsp;&#45;";
					echo "&nbsp;" . $pEnd . ")</span>";
				}
			break;
				
			case "3' linker":

				if ($modify)
				{
					echo "<INPUT type=\"text\" onKeyPress=\"return disableEnterKey(event);\" ID=\"tp_linker_prop\" value=\"" . $value . "\" name=\"" . $POST_VAR_NAME . "\">";

					break;
				}

				$tmp_linker = $this->get_Special_Column_Type("3' linker", $category, $rid, "None");
				echo $tmp_linker;

				// Feb. 13/08: Show positions
				$pStart = $rfunc_obj->getStartPos($rid, $type, $_SESSION["ReagentProp_Name_ID"]["3' linker"], "");
				$pEnd = $rfunc_obj->getEndPos($rid, $type, $_SESSION["ReagentProp_Name_ID"]["3' linker"], "");

				// Update March 3/09 - show linker IFF not empty
				if ($tmp_linker && (strlen($tmp_linker) > 0) && ($pStart >= 0) && ($pEnd >= 0))
				{
					echo "&nbsp; <span style=\"color:#006400\">(nt " . $pStart . "&nbsp;&#45;";
					echo "&nbsp;" . $pEnd . ")</span>";
				}
			break;

			case "restriction site":
				if ($modify)
				{
					if (($rTypeID == $_SESSION["ReagentType_Name_ID"]["Vector"]) || ($rTypeID == $_SESSION["ReagentType_Name_ID"]["Insert"]))
					{
						echo "<select size=\"1\" style=\"font-size:7pt; font-family:Courier; color:" . $font_color . "\" id=\"restriction_site_:_list\" name=\"" . $POST_VAR_NAME . "\"></select>";
	
						echo "<br>";
						?>
							<INPUT style="margin-top:2px; display:none" id="restriction_site_:_txt" name="restriction_site_:_name_txt" size="20"></INPUT>
						<?php
					}
					else
					{
						echo "<select size=\"1\" id=\"" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$rTypeID]) . "_restriction_site_:_list\" name=\"" . $POST_VAR_NAME . "\" style=\"font-size:7pt;\">";

						$error_tmp = $this->print_Set_Final_Dropdown($type, $value, $category, $rTypeID, "Preview");
						echo "</select>";
					}
		
					break;
				}
			
				echo $this->get_Special_Column_Type($type, $category, $rid, "None");
			break;

			default:

				if ($modify)
				{
					if ($readonly)
						$disabled = "DISABLED";
					else
						$disabled = "";

					// June 8, 2010: isDropdownProperty checks whether there are dropdown list values assigned to this property; if none have been assigned yet b/c the property is customizeable (only 'Allow Other' was checked during reagent type creation/modification, then isDropdownProperty() would return false but isCustomizeable() would return true.
					if ($this->isDropdownProperty($type, $_SESSION["ReagentPropCategory_Name_ID"][$category], $rTypeID) || $rfunc_obj->isCustomizeable($attrID))
					{
 				//		echo "THIS IS DROPDOWN " . $type;

						// Jan. 18, 2010
						if (!$rfunc_obj->isSequenceFeature($propCatID))
						{
							if ($rfunc_obj->isMultiple($attrID))
							{
								$vals = Array();
								$cb_id = "mult_cb_" . $attrID;

								// there can still be only one value stored for a multiple list
								if (is_array($value))
								{
									$vals = $value;
								}
								else
								{
									$vals[] = $value;
								}

								// Make a small table w/ 2 lists again
								if ($value != "")
								{
									$mult_display = "inline";
									$one_display = "none";
								}
								else
								{
									$mult_display = "none";
									$one_display = "inline";
								}

								?>
								<DIV ID="multiples_<?php echo $attrID; ?>">
									<TABLE>
										<TR>
											<TD><?php

												echo "<SELECT MULTIPLE=\"MULTIPLE\" SIZE=\"5\" id=\"targetList_" . $attrID . "\" name=\"" . $POST_VAR_NAME . "\" style=\"font-size:7pt; color:#" . $font_color . "\">";

												foreach ($vals as $key => $value)
												{
													// May 14, 2010: important not to print empty options!!
													if ($value != "")
													{
														echo "<OPTION VALUE=\"" . $value . "\">" . $value . "</OPTION>";
													}
												}
	
												echo "</SELECT>";
											?></TD>
	
											<TD>
												<!-- buttons -->
												<INPUT TYPE="BUTTON" VALUE="<< Add" NAME="addBtn[]" style="font-size:7pt;" onclick="moveListElements('srcList_<?php echo $attrID; ?>', 'targetList_<?php echo $attrID; ?>', false, true);"><BR>
	
												<INPUT TYPE="BUTTON" NAME="rmvBtn[]" STYLE="font-size:7pt;" style="font-size:8pt;" VALUE="Remove >>" onclick="moveListElements('targetList_<?php echo $attrID; ?>', 'srcList_<?php echo $attrID; ?>', false, false);">
											</TD>
	
											<TD>
												<SELECT MULTIPLE SIZE="5" ID="srcList_<?php echo $attrID; ?>" style="font-size:7pt"><?php
	
												$allAttrVals = $rfunc_obj->getAllReagentTypeAttributeSetValues($attrID);
	
												foreach ($allAttrVals as $sKey => $sVal)
												{
													if (!in_array($sVal, $vals))
													{
														echo "<OPTION VALUE=\"" . $sVal . "\">" . $sVal . "</OPTION>";
													}
												}
												?></SELECT>
											</TD>
										</TR>
	
										<!-- Other -->
										<?php

										if ($rfunc_obj->isCustomizeable($attrID))
										{
											?>
											<TR>
												<TD colspan="3">
													<INPUT TYPE="text" NAME="" ID="otherText_<?php echo $attrID; ?>">
		
													<INPUT TYPE="button" style="font-size:7pt;" VALUE="Add" ID="addOtherBtn_<?php echo $attrID; ?>" onClick="addElementToListFromInput('otherText_<?php echo $attrID; ?>', 'targetList_<?php echo $attrID; ?>')" NAME="addBtn[]">
												</TD>
											</TR>
											<?php
										}
									?>
									</TABLE>
								</DIV><?php
								break;
							}
							else
							{
								if ($value != "")
								{
									if ($descr != "")
									{
										$descrTypes = $rfunc_obj->getFeatureDescriptors();
	
										$descrType = $descrTypes[$type];
	
										$rowID = $prefix . $_SESSION["ReagentProp_Name_Alias"][$type] . "_:_" . $value . "_" . $descrType . "_" . $descr . "_start_" . $start . "_end_" . $end . $postfix;
	
										$txtID = $rType . "_" . $_SESSION["ReagentProp_Name_Alias"][$type] . "_:_" . $value . "_" . $descrType . "_" . $descr . "_start_" . $start . "_end_" . $end . "_" . "txt";
									}
									else
									{
										$txtID = $rType . "_" . $_SESSION["ReagentProp_Name_Alias"][$type] . "_:_" . $value . "_start_" . $start . "_end_" . $end . "_" . "txt";
	
										$rowID = $prefix . $_SESSION["ReagentProp_Name_Alias"][$type] . "_:_" . $value . "_start_" . $start . "_end_" . $end . $postfix;
									}
								}
								else
								{
									$txtID = str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$rTypeID]) . "_" . $_SESSION["ReagentProp_Name_Desc"][$type] . "_txt";
	
									$rowID = str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$rTypeID]) . "_" . $_SESSION["ReagentProp_Name_Desc"][$type] . "_prop";
								}
							}
						}
						else
						{
							if ($rfunc_obj->isDescriptor($type))
							{
								// extract main prop info from $POST_VAR_NAME
								$before = $prefix . $type . "_:_";
								
								$list_id = substr($POST_VAR_NAME, strlen($before), strlen($POST_VAR_NAME)-strlen($before)-strlen($postfix));

								$txtID = $prefix . $_SESSION["ReagentProp_Name_Alias"][$type] . "_:_" . $list_id . "_txt";
							}
							else
							{
								$txtID = $prefix . $_SESSION["ReagentProp_Name_Alias"][$type] . "_:_" . $value . "_start_" . $start . "_end_" . $end . "_" . "txt";

								// Now: CANNOT use simply values_attrID for the list ID, because when there is > 1 of the same feature type, the Other textbox is not shown for the second (the 2 lists have the same ID!!!)  Need to supply again a way of differentiating b/w rows - e.g. with value, positions, direction
								$tmpStart = strlen($prefix . $_SESSION["ReagentProp_Name_Alias"][$type] . "_:_");
								
								$list_id = substr($txtID, $tmpStart, strlen($txtID)-$tmpStart-strlen($postfix)+1);
							}

							// Jan 18/10
							$rowID = $prefix . $_SESSION["ReagentProp_Name_Alias"][$type] . "_:_" . $value . "_start_" . $start . "_end_" . $end . $postfix;
						}

						// update June 9/10
						// Must have different list IDs for features since there can be multiple, BUT in latest implementation of mandatory props check, a list ID "values_" . $attrID is used.
						// Bottom line: differentiate here b/w features and non-features
						if (!$rfunc_obj->isSequenceFeature($propCatID))
						{
							echo "<select id=\"values_" . $attrID . "\" name=\"" . $POST_VAR_NAME . "\" style=\"font-size:7pt; color:#" . $font_color . "\" " . $disabled . " onChange=\"showSpecificOtherTextbox(this.id, '" . str_replace("'", "\'", $txtID) . "');\">";
						}
						else
						{
							echo "<select id=\"values_" . $attrID . "_" . $list_id . "\" name=\"" . $POST_VAR_NAME . "\" style=\"font-size:7pt; color:#" . $font_color . "\" " . $disabled . " onChange=\"showSpecificOtherTextbox(this.id, '" . str_replace("'", "\'", $txtID) . "');\">";
						}

						$error_tmp = $this->print_Set_Final_Dropdown($type, $value, $category, $rTypeID, "Preview");

						echo "</select>";

						// March 3, 2011
						if (strcasecmp($type, "type of insert") == 0)
						{
							echo "<INPUT TYPE=\"hidden\" id=\"type_of_insert_attr_id_hidden\" VALUE=\"" . $attrID . "\">";

							// THIS IS THE GOOD 'HELP TOOLTIP' IMAGE!!!
//							echo "&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/hm.png\" HEIGHT=\"18\" width=\"18\" ALT=\"link_icon\" style=\"vertical-align:middle; cursor:auto\" onmouseover=\"return overlib('Select the correct type of insert to ensure correct  translation of the DNA sequence to protein.', CAPTION, 'Type of Insert', STICKY);\">";

							echo "<span ID=\"translationRules\" class=\"linkExportSequence\" style=\"vertical-align:bottom; margin-left:5px; font-size:9pt;\" onClick=\"return popup('Reagent/pr_translation_rules.pdf', 'Protein Translation Rules', 1200, 1200, 'yes');\">Translation Guidelines</span><IMG SRC=\"pictures/new01.gif\" ALT=\"new\" WIDTH=\"35\" HEIGHT=\"20\">";
						}

						// March 3, 2011
						if (strcasecmp($type, "open/closed") == 0)
						{
//							echo "&nbsp;&nbsp;&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/link5.png\" WIDTH=\"18\" HEIGHT=\"8\" ALT=\"link_icon\" style=\"cursor:auto\" onmouseover=\"return overlib('Select the correct open/closed value to ensure correct  translation of the DNA sequence to protein.', CAPTION, 'Open/Closed', STICKY);\">";

							echo "&nbsp;&nbsp;<span ID=\"translationRules\" class=\"linkExportSequence\" style=\"margin-left:5px; font-size:9pt;\" onClick=\"return popup('Reagent/pr_translation_rules.pdf', 'Protein Translation Rules', 1200, 1200, 'yes');\">Translation Guidelines</span><IMG SRC=\"pictures/new01.gif\" ALT=\"new\" WIDTH=\"35\" HEIGHT=\"20\">";
						}

						if (!$rfunc_obj->isSequenceFeature($propCatID))
						{
							$txtName = $prefix . $rType . "_" . $_SESSION["ReagentPropCategory_Name_Alias"][$category] . "_:_" . $_SESSION["ReagentProp_Name_Alias"][$type] . "_name_txt";
						}
						else
						{
							$txtName = str_replace("_prop", "_name_txt", $POST_VAR_NAME);
						}

						echo "<INPUT type=\"text\" style=\"display:none; width:200px; font-size:7pt;\" ID=\"" . $txtID . "\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $value . "\" name=\"" . $txtName . "\">";

						?><div NAME="warnings[]" id="warn_<?php echo $attrID; ?>" style="display:none; color:#FF0000;"><BR>Please select a value for <?php echo $_SESSION["ReagentProp_Name_Desc"][$type]; ?> from the dropdown list</div><?php

						break;
					}
					else if ($this->isComment($type, $_SESSION["ReagentPropCategory_Name_ID"][$category], $rTypeID))
					{
						$descrValue = $this->get_Special_Column_Type($type, $category, $rid, "None");

						echo "<INPUT type=\"text\" " . $disabled . " onKeyPress=\"return disableEnterKey(event);\" value=\"" . $descrValue . "\" name=\"" . $POST_VAR_NAME . "\" style=\"color:#" . $font_color . ";\">";

						// June 15, 2010: show hyperlink tooltip
						if ($rfunc_obj->isHyperlink($attrID))
							echo "&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/link5.png\" WIDTH=\"18\" HEIGHT=\"8\" ALT=\"link_icon\" style=\"cursor:auto\" onmouseover=\"return overlib('You may link out to an external website by entering a complete URL in the textbox, e.g. http://www.mysite.com', CAPTION, 'Hyperlink', STICKY);\">";

						break;
					}
					else
					{
						// append units to Tm and MW (April 14, 2011)
						if ($type == "melting temperature")
						{
							if (is_array($value) && count($value) == 0)
								$value = "";

							echo "<INPUT type=\"text\" " . $disabled . " id=\"values_" . $attrID . "\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $value . "\" name=\"" . $POST_VAR_NAME . "\" style=\"color:" . $font_color . ";\" SIZE=\"5\">";

							echo " <b>&#176;C</b>";
						}
						else if ($type == "molecular weight")
						{
							if (is_array($value) && count($value) == 0)
								$value = "";

							$val = ($value != "") ? round($value, 2) : $value;

							echo "<INPUT type=\"text\" " . $disabled . " id=\"values_" . $attrID . "\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $val . "\" name=\"" . $POST_VAR_NAME . "\" style=\"color:" . $font_color . ";\" SIZE=\"5\">";

							if ($isProtein)
								echo " <b>kDa</b>";
							else
								echo " <b>g/mol</b>";
						}
						else	// added this 'else' on April 14, 2011 to show a smaller box for Tm and MW
						{
							if (is_array($value) && count($value) == 0)
								$value = "";

							echo "<INPUT type=\"text\" " . $disabled . " id=\"values_" . $attrID . "\" onKeyPress=\"return disableEnterKey(event);\" value=\"" . $value . "\" name=\"" . $POST_VAR_NAME . "\" style=\"color:" . $font_color . ";\">";
						}
						// June 15, 2010 show hyperlink tooltip
						if ($rfunc_obj->isHyperlink($attrID))
							echo "&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/link5.png\" WIDTH=\"18\" HEIGHT=\"8\" ALT=\"link_icon\" style=\"cursor:auto\" onmouseover=\"return overlib('You may link out to an external website by entering a complete URL in the textbox, e.g. http://www.mysite.com', CAPTION, 'Hyperlink', STICKY);\">";

						// May 20, 2010: changed ID
						?><BR><div NAME="warnings[]" id="warn_<?php echo $attrID; ?>" style="display:none; color:#FF0000">Please provide a <?php echo $_SESSION["ReagentProp_Name_Desc"][$type]; ?> value for the new <?php echo $_SESSION["ReagentType_ID_Name"][$rTypeID]; ?></div><?php

						break;
					}
				}

				if ($rfunc_obj->isSequenceFeature($rid, $type))
				{
					echo $this->get_Special_Column_Type($type, $category, $rid, "None");

					?><BR><div id="<?php echo str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$rTypeID]) . "_" . $_SESSION["ReagentProp_Name_Desc"][$type]; ?>_warning" style="display:none; color:#FF0000">Please provide <?php echo $_SESSION["ReagentProp_Name_Desc"][$type]; ?> for the new <?php echo $_SESSION["ReagentType_ID_Name"][$rTypeID]; ?></div><?php

					break;
				}

				// June 15, 2010: Output hyperlinks
				if ($rfunc_obj->isHyperlink($attrID))
					echo "<a href=\"" . $value . "\">" . $value . "</a>";
				else
				{
					if (is_array($value))
					{
						echo "<UL style=\"padding-left:15px;\">";

						foreach ($value as $a => $val)
							echo "<LI>" . $val . "</LI>";

						echo "</UL>";
					}
					else
					{
						// May 9, 2011: round MW
						if ($type == "molecular weight")
						{
							echo round($value, 2);
						}
						else
							echo $value;
					}
				}

				// April 14, 2011: Add units
				if ($type == "melting temperature")
					echo " <b>&#176;C</b>";

				else if ($type == "molecular weight")
				{
					if ($isProtein)
						echo " <b>kDa</b>";
					else
						echo " <b>g/mol</b>";
				}

			break;
		}
	}

	
	/**
	 * Print individual properties that require special output format
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING $type Name of the property to be printed
	 * @param INT $rid Reagent ID
	 * @param STRING $category Category in which this property is defined for the current reagent type (added Aug. 21/09)
	 * @param STRING $output_type Passed on to get_Special_Column_Type() as an argument
	*/
	function get_Special_Column_Type($type, $category, $rid, $output_type)
	{
		$outputer_obj = new ColFunctOutputer_Class();
		$toreturn = $outputer_obj->output_final($rid, $type, $output_type, $category);
		unset( $outputer_obj );
		
		return $toreturn;
	}


	// -----------------------------------------------------------------------------------------------------------------------------
	// The next 3 functions were written to preload information about parents during the modification of reagent associations
	// They may be combined into one function and differentiated on reagent type - I have separated them for now in case they
	// need to be extended later on
	// May 4, 2006, Marina
	// -----------------------------------------------------------------------------------------------------------------------------

	/**
	 * Preload parent vector properties at Stable cell line modification
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-05-04
	 *
	 * @param INT $rID reagent ID (child cell line)
	 * @deprecated
	*/
// removed Nov. 24, 2010
// 	function preload_StableCL_PV_props($rID)
// 	{
// 		$prefix = "reagent_detailedview_";
// 		$postfix = "_prop";
// 	
// 		$v_names = $this->rfunc_obj->get_Post_Names("Vector", "General");
// 	
// 		$creator_obj = new Reagent_Creator_Class();
// 		$property_tmp = array();
// 	
// 		if( $rID > 0 )
// 		{
// 			$property_tmp = $creator_obj->backFill($rID, $v_names);
// 		}
// 	
// 		return $property_tmp;
// 	}

	/**
	 * Preload parent cell line properties at Stable cell line modification
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-05-01
	 *
	 * @param INT $rID reagent ID (child cell line)
	 * @deprecated
	*/
// removed Nov. 24, 2010
// 	function preload_CellLineProps_Modify($rID)
// 	{
// 		$prefix = "reagent_detailedview_";
// 		$postfix = "_prop";
// 	
// 		$cl_names = $this->rfunc_obj->get_Post_Names("CellLine", "");
// 	
// 		$creator_obj = new Reagent_Creator_Class();
// 		$property_tmp = array();
// 	
// 		if( $rID > 0 )
// 		{	
// 			// Match property names to their database ids
// 			$property_tmp = $creator_obj->backFill($rID, $cl_names);
// 		}
// 	
// 		return $property_tmp;
// 	}

	/**
	 * Central function for displaying reagent detailed view, that invokes other output functions to print a detailed view depending on the reagent type
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING $modify
	 * @param INT $view Page number for redirection
	 * @param INT $toViewReagentID Current reagent ID
	 * @param INT $check Error code from parent modification
	*/
	function print_Detailed_Reagent($modify, $view, $toViewReagentID, $check)
	{
		global $conn;

		$gfunc_obj = new generalFunc_Class();
		$modify_state = false;

		if ($modify == "Modify")
		{
			$modify_state = true;
		}
		
		$typeOf_rs = mysql_query("SELECT `reagentTypeID`, `groupID` FROM `Reagents_tbl` WHERE `status`='ACTIVE' AND `reagentID`='" . $toViewReagentID . "'", $conn);
		
		// July 17/07, Marina: Limit modification by project - restrict to Admin, project owner or writers in this project
		$currUserID = $_SESSION["userinfo"]->getUserID();
		$currUserCategory = $_SESSION["userinfo"]->getCategory();
		$userProjects = getUserProjectsByRole($currUserID, 'Writer');

		$rIDpID = getReagentProjectID($toViewReagentID);

		// Admin has unlimited access
		if ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])
		{
			$modify_restricted = false;
		}
		else
		{
			// Jan. 31/08: Negative or 0 project ID is encountered only during reagent creation.  Allow
			if ($rIDpID <= 0)
			{
				$modify_restricted = false;
			}
			else
			{
				if (in_array($rIDpID, $userProjects))
				{
					$modify_restricted = false;
				}
				else
				{
					$modify_restricted = true;
				}
			}
		}
		
		if ($typeOf_ar = mysql_fetch_array($typeOf_rs, MYSQL_ASSOC))
		{
			// Dec. 22/09: Quick and dirty - keep existing code for Cell Line modify
			if ($modify && ($typeOf_ar["reagentTypeID"] == 4))
			{
				$this->print_Detailed_Cellline($modify, $view, $toViewReagentID, $check, $modify_restricted);
			}
			else
			{
				// April 28/09 - Adding ability to add new reagent types
				$this->print_Detailed_Reagent_Other($modify_state, $view, $toViewReagentID, $typeOf_ar["reagentTypeID"], $typeOf_ar["groupID"], $modify_restricted);
			}
		}
	}

	/**
	 * Is this property a comment? (there is a separate table for comments in the database, so a different query is used for retrieving the comment itself rather than its column ID number)
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING $name Property name (e.g. 'description')
	 * @param INT $categoryID (e.g. '1' for 'General Properties')
	 * @param INT $rTypeID (e.g. '1' for 'Vector')
	*/
	function isComment($name, $categoryID, $rTypeID)
	{
		global $conn;
		$rfunc_obj = new Reagent_Function_Class();

		$result = false;

		// Nov. 11/09: For now - plain and simple hack: just return true if this is a comment or description value, change later if needed --> Feb.5/10: needed now (can have comments under General and under Growth Properties)!
		if ($categoryID == 1)
		{
			switch ($name)
			{
				case 'description':
				case 'comments':
				case 'verification comments':
					$result = true;
				break;
	
				default:
					$result = false;
				break;
			}
		}
		else
			$result = false;

		return $result;
	}


	/**
	 * Should this property be entered as an arbitrary freetext value, or selected from a dropdown list of pre-defined values?  Sequence features are always selected from a list; input form of other properties is specific for each reagent type and is defined at the introduction of a new reagent type into OpenFreezer.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2009-04-30
	 *
	 * @param STRING $name Property name (e.g. 'promoter')
	 * @param INT $categoryID (e.g. '1' for 'DNA Sequence Features')
	 * @param INT $rTypeID (e.g. '1' for 'Insert')
	*/
	function isDropdownProperty($name, $categoryID, $rTypeID)
	{
		global $conn;
		$rfunc_obj = new Reagent_Function_Class();

		// updates Nov. 10/09
		$propCatID = $rfunc_obj->findReagentTypeAttributeID($rTypeID, $name, $categoryID);
		$attrID = $rfunc_obj->getRTypeAttributeID($rTypeID, $name, $categoryID);
		$setValsList = Array();

		$set_rs = mysql_query("SELECT reagentTypeAttributeSetID FROM ReagentTypeAttribute_Set_tbl WHERE reagentTypeAttributeID='" . $attrID . "' AND status='ACTIVE'", $conn) or die("FAILURE IN Reagent_Creator_Class.print_set(3): " . mysql_error());

		if ($set_ar = mysql_fetch_array($set_rs, MYSQL_ASSOC))
			return true;
		else
			return false;
	}


	/**
	 * Output a dropdown (SELECT) list of properties that have pre-defined values
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING $name Property name (e.g. 'intron')
	 * @param STRING $selected Selected list value
	 * @param STRING $category (e.g. 'General Properties')
	 * @param INT $rTypeID (e.g. '1' for 'Vector')
	 * @param STRING $type_of_output Obsolete, but caution when deleting - need to update everywhere this function is called
	*/
	function print_Set_Final_Dropdown($name, $selected, $category, $rTypeID=0, $type_of_output = "")
	{
		global $conn;

		$rfunc_obj = new Reagent_Function_Class();

		// Update Nov. 10/09
		$categoryID = $_SESSION["ReagentPropCategory_Name_ID"][$category];
		$attrID = $rfunc_obj->getRTypeAttributeID($rTypeID, $name, $categoryID);
		$errorcount=0;

		$rs_2 = mysql_query("SELECT s.entityName FROM ReagentTypeAttribute_Set_tbl r, System_Set_tbl s WHERE r.reagentTypeAttributeID='" . $attrID . "' AND s.ssetID=r.ssetID AND s.status='ACTIVE' AND r.status='ACTIVE' ORDER BY s.entityName", $conn) or die("FAILURE IN Reagent_Creator_Class.print_set(1): " . mysql_error());

		// Jan. 15, 2010
		echo "<OPTION value=\"\">-- Select " . ucwords($name) . "--</option>";

		while ($set_ar = mysql_fetch_array($rs_2 , MYSQL_ASSOC))
		{
			if (strcasecmp($set_ar["entityName"], trim($selected)) == 0)
			{
				echo "<option selected value=\"" . $selected . "\">";
				$errorcount++;
			}
			else
			{
				echo "<option value=\"" . $set_ar["entityName"] . "\">";
			}

			echo stripslashes($set_ar["entityName"]) . "</option>";
		}

		// added June 7, 2010 (moved from print_property_final() function, better this way for features)
		if ($rfunc_obj->isCustomizeable($attrID))
		{
			echo "<option value=\"Other\">Other</option>";
		}
	}


	/**
	 * Output a list of checkboxes (e.g. Alternate ID)
	 *
	 * Feb. 26/08: Changed method signature - added $rid parameter - for showing start/stop positions of checkbox values for a particular reagent
	 *
	 * July 16/09: changed queries to reflect new table structure
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING $name Property name (e.g. 'alternate id')
	 * @param STRING $checkboxname Name of the checkbox on the form
	 * @param Array $selected_ar List of selected checkbox values
	 * @param INT $rid Reagent ID
	 * @param STRING $rTypePrefix Prefix of the reagent type (e.g. 'V' for Vector)
	 * @param STRING $category Name of the category in which the property appears (e.g. 'External Identifiers)
	 * @param INT $rTypeID Reagent type ID (e.g. '1' for Vector)
	*/
	function print_Set_Extended_Checkbox($name, $checkboxname, $selected_ar, $rid=0, $rTypePrefix="", $category, $rTypeID=0)
	{
		global $conn;

		$prefix = "reagent_detailedview_";
		$postfix = "_prop";

		$rfunc_obj = new Reagent_Function_Class();	// Feb. 26/08

		// Feb. 26/08: show positions
		$pStart = 0;
		$pEnd = 0;

		// Extract the property alias from the checkbox name
		$aliasStart = substr($checkboxname, strlen($prefix));
		$prop_alias = substr($aliasStart, 0, strlen($aliasStart)-strlen($postfix));

		$font_color = "000000";

		if (isset($_POST["change_vp_id"]))
		{
			$font_color = "FF0000";	
		}

		$propID = $rfunc_obj->getRTypeAttributeID($rTypeID, $name, $_SESSION["ReagentPropCategory_Name_ID"][$category]);

		// Feb. 5/10: If there's only one value in $selected_ar, it is NOT an array!  Make it into one:
		if (isset($selected_ar) && (!is_array($selected_ar)) && $selected_ar != "")
		{
			$tmp_ar = Array();
			$tmp_ar[] = $selected_ar;

			$selected_ar = $tmp_ar;
		}

		// updates Nov. 10/09
		$categoryID = $_SESSION["ReagentPropCategory_Name_ID"][$category];
		$categoryAlias = $_SESSION["ReagentPropCategory_Name_Alias"][$category];	// Feb. 5/10

		$propCatID = $rfunc_obj->findReagentTypeAttributeID($rTypeID, $name, $categoryID);
		$attrID = $rfunc_obj->getRTypeAttributeID($rTypeID, $name, $categoryID);

		$attrSet_rs = mysql_query("SELECT entityName FROM System_Set_tbl s, ReagentTypeAttribute_Set_tbl r WHERE r.reagentTypeAttributeID='" . $attrID . "' AND s.ssetID=r.ssetID AND r.status='ACTIVE' AND s.status='ACTIVE'", $conn) or die("FAILURE IN Reagent_Creator_Class.print_set(1): " . mysql_error());

		// For Alternate IDs, extract the name portion before the identifier (semicolon-delimited)
		if (strcasecmp(strtolower($name), "alternate id") == 0)
		{
			$tmp_selected = array();
	
			if (isset($selected_ar))
			{
				foreach ($selected_ar as $key => $value)
				{
					$db_name = substr($value, 0, strpos($value, ":"));
					$db_id = substr($value, strpos($value, ":") + 1);
					$tmp_selected[$db_name] = $db_id;
				}
	
				$selected_ar = $tmp_selected;
			}
		}
		
		switch (strtolower($name))
		{
			case 'alternate id':
	
				while ($set_ar = mysql_fetch_array($attrSet_rs, MYSQL_ASSOC))
				{
					if (isset($selected_ar) && in_array($set_ar["entityName"], array_keys($selected_ar)))
					{
						echo "<input type=\"checkbox\" checked name=\"" . $checkboxname . "\" value=\"". $set_ar["entityName"] . "\" id=\"" . $rTypePrefix . "_alternate_id_" . $set_ar["entityName"] . "_checkbox\" onClick=\"showAltIDTextBox('" . $rTypePrefix . "_alternate_id_" . $set_ar["entityName"] . "_checkbox', '" . $rTypePrefix . "_alternate_id_" . $set_ar["entityName"] . "_textbox')\">"  . $set_ar["entityName"] . " &nbsp;";
		
						echo "<INPUT style=\"display:inline\" id=\"" . $rTypePrefix . "_alternate_id_" . $set_ar["entityName"] . "_textbox\" name=\"alternate_id_" . $set_ar["entityName"] . "_textbox_name\" size=\"15\" value=\"" . $selected_ar[$set_ar["entityName"]] . "\"></INPUT><br>";
		
// 						$errorcount++;
					}
					else
					{
						echo "<input type=\"checkbox\" name=\"" . $checkboxname . "\" value=\"". $set_ar["entityName"] . "\" id=\"" . $rTypePrefix . "_alternate_id_" . $set_ar["entityName"] . "_checkbox\" onClick=\"showAltIDTextBox('" . $rTypePrefix . "_alternate_id_" . $set_ar["entityName"] . "_checkbox', '" . $rTypePrefix . "_alternate_id_" . $set_ar["entityName"] . "_textbox')\">"  . $set_ar["entityName"] . " &nbsp;";
		
						echo "<INPUT style=\"display: none\" id=\"" . $rTypePrefix . "_alternate_id_" . $set_ar["entityName"] . "_textbox\" name=\"alternate_id_" . $set_ar["entityName"] . "_textbox_name\" size=\"15\"></INPUT><br>";
					}
				}

				// other
				echo "<input type=\"checkbox\" id=\"" . $rTypePrefix . "_other_chkbx\" name=\"" . $checkboxname . "\" value=\"Other\" onClick=\"if (this.checked) showSpecificOtherCheckbox('" . $rTypePrefix . "_alternate_id_Other_chkbx_txt')\">Other";

				?>
					<INPUT style="display:none; margin-left:5px;" id="<?php echo $rTypePrefix; ?>_alternate_id_Other_chkbx_txt" name="alternate_id_Other_textbox_name" size="15"></INPUT>
				<?php

			break;
		}
	}
	
	/**
	 * Use to display Vector and Cell Line subtype - i.e. stable cell line, gateway entry vector, etc. on reagent creation page
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2007-03-28
	 *
	 * @param INT $rTypeID Reagent type ID (e.g. '1' for Vector)
	 * @param STRING $selected Selected reagent subtype alias (e.g. 'non_recomb')
	 * @param STRING $listID ID of the SELECT element on the form
	 * @param STRING $listName Name of the SELECT element on the form
	 * @param boolean $readonly Should the element be displayed as read-only or made editable
	 * @param STRING $vDisplay Either 'inline' or 'none' - indicates whether the Vector subtypes list should be visible or hidden
	 * @param STRING $cDisplay Either 'inline' or 'none' - indicates whether the Cell Line subtypes list should be visible or hidden
	*/
	function printReagentSubtype($rTypeID, $selected, $listID, $listName, $readonly, $vDisplay="none", $cDisplay="none")
	{
		global $conn;
		$count = 0;
		
		$rfunc_obj = new Reagent_Function_Class();
		$rfunc_obj->check_Reagent_Session();
		$rfunc_obj->reset_Reagent_Session();
		unset( $rfunc_obj );
		
		$subtype_rs = mysql_query("SELECT `name`, `alias` FROM Reagent_SubType_tbl WHERE `reagent_typeID`='" . $rTypeID . "'", $conn) or die("Could not fetch reagent subtype: " . mysql_error());
		
		// default option
		switch ($rTypeID)
		{
			case $_SESSION["ReagentType_Name_ID"]["Vector"]:
	
				if ($readonly == 1)
					echo "<SELECT id=\"" . $listID . "\" name=\"" . $listName . "\" disabled onChange=\"showParents('" . $listID . "')\">";
				else
					echo "<SELECT style=\"display:" . $vDisplay . "\" id=\"" . $listID . "\" name=\"" . $listName . "\" onChange=\"showParents('" . $listID . "')\">";
				
				echo "<OPTION value=\"default\">Select Vector subtype</OPTION>";
			break;
			
			case $_SESSION["ReagentType_Name_ID"]["CellLine"]:
				if ($readonly == 1)
					echo "<SELECT id=\"" . $listID . "\" name=\"" . $listName . "\" disabled onChange=\"showParents(this.id)\">";
				else
					echo "<SELECT style=\"display:" . $cDisplay . "\" id=\"" . $listID . "\" name=\"" . $listName . "\" onChange=\"showParents(this.id)\">";
				
				echo "<OPTION value=\"default\">Select Cell Line subtype</OPTION>";
			break;
			
			default:
				echo "<OPTION value=\"default\">Select Reagent subtype</OPTION>";
			break;
		}
		
		while ($subtype_ar = mysql_fetch_array($subtype_rs, MYSQL_ASSOC))
		{
			$subtype = $subtype_ar["name"];
			$alias = $subtype_ar["alias"];

			if ($alias == $selected)
				echo "<OPTION selected ";
			else
				echo "<OPTION ";
				
			echo "value=\"" . $alias . "\">" . $subtype . "</OPTION>";
		}
		
		echo "</SELECT>";
	}

	/*
	* Output an error message when the user tries to access a reagent s/he does not have project access permissions for
	*
	* @author Marina Olhovsky
	* @version 3.1
	*
	* @param STRING Error message that will be shown on the page
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
	 * Print Vector parents section on Vector detailed view
	 *
	 * Dec. 15/09: Replace printForm_Vector_show_associations() - just print parents
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT $reagentIDToView Reagent ID
	*/
	function printVectorParents($reagentIDToView)
	{
		global $conn;
	
		$gfunc_obj = new generalFunc_Class();
		$bfunc_obj = new Reagent_Background_Class();
		$rfunc_obj = new Reagent_Function_Class();
	
		$colspan = 6;
	
		$currUserCategory = $_SESSION["userinfo"]->getCategory();
	
		// Determine cloning method from Association_tbl:
		$query = "SELECT * FROM `Association_tbl` WHERE `reagentID`='" . $reagentIDToView . "' AND `status`='ACTIVE'";
		$cloning_method_rs = mysql_query($query, $conn) or die("Could not determine cloning method: " . mysql_error());
		
		if ($cloning_method_ar = mysql_fetch_array($cloning_method_rs, MYSQL_ASSOC))
		{
			$cloningMethod = $cloning_method_ar["ATypeID"];
		}
		else
		{
			$cloningMethod = 3;	// set default to BASIC
		}
	
		if ($cloningMethod != 3)
		{
			// don't display parents for parent vectors
			?>
			<table width="100%" border="0" cellspacing="5" cellpadding="5">

			<tr>
				<td align=left colspan="<?php echo $colspan;?>">
					<i>&nbsp;&nbsp;&nbsp;&nbsp;<b>Parents</b></i>
				</td>
			</tr>
	
			<tr>
				<td width="275px" nowrap>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Parent Vector <b>OpenFreezer</b> ID
				</td>

				<td nowrap>
				<?php
					$tmp_rid = $bfunc_obj->get_Background_rid( $reagentIDToView, "Vector Parent ID" );
					
					if ($tmp_rid > 0)
					{
						// August 17/07: Restrict viewing by project - if parent belongs to a project you don't have access to, don't allow linking
						$tmpProject = getReagentProjectID($tmp_rid);
						$userProjects = getAllowedUserProjectIDs($_SESSION["userinfo"]->getUserID());

						if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($tmpProject, $userProjects))
						{
							echo "<a href=\"Reagent.php?View=6&rid=" . $tmp_rid . "\">";
							echo $gfunc_obj->getConvertedID_rid( $tmp_rid );
							echo "</a>&nbsp;";
						}
						else
						{
							echo "<span class=\"linkDisabled\">";
							echo $gfunc_obj->getConvertedID_rid( $tmp_rid );
							echo "</span>";
						}
					}
					else
					{
						echo "None";
					}
				?></td>
			</tr>
			<?php
				// Decide what to show here - insert or IPV - based on the vector's cloning method:	
				if ($cloningMethod == 1)
				{
					// PCR
					// Show the insert
					?>
					<tr>
						<td width="180px" nowrap>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Insert ID
						</td>
	
						<td nowrap>
						<?php
							$tmp_rid = $bfunc_obj->get_Background_rid($reagentIDToView, "Insert ID");
	
							if ($tmp_rid > 0)
							{
								// August 17/07: Restrict viewing by project - if parent belongs to a project you don't have access to, don't allow linking
								$tmpProject = getReagentProjectID($tmp_rid);
								$userProjects = getAllowedUserProjectIDs($_SESSION["userinfo"]->getUserID());
	
								if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($tmpProject, $userProjects))
								{
									echo "<a href=\"Reagent.php?View=6&rid=" . $tmp_rid . "\">";
									echo $gfunc_obj->getConvertedID_rid( $tmp_rid );
									echo "</a>&nbsp;";
								}
								else
								{
									echo "<span class=\"linkDisabled\">";
									echo $gfunc_obj->getConvertedID_rid( $tmp_rid );
									echo "</span>";
								}
							}
							else
							{
								echo "None";
							}
						?></td>
					</tr>
					<?php
				}
				elseif ($cloningMethod == 2)
				{
					// LOXP
					// Show IPV
					?>
						<tr>
							<td width="180px" nowrap>
								&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Insert Parent Vector ID
							</td>
	
							<td nowrap>
							<?php
								$tmp_rid = $bfunc_obj->get_Background_rid($reagentIDToView, "parent insert vector");
	
								if ($tmp_rid > 0)
								{
									// August 17/07: Restrict viewing by project - if parent belongs to a project you don't have access to, don't allow linking
									$tmpProject = getReagentProjectID($tmp_rid);
									$userProjects = getAllowedUserProjectIDs($_SESSION["userinfo"]->getUserID());
	
									if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($tmpProject, $userProjects))
									{
										echo "<a href=\"Reagent.php?View=6&rid=" . $tmp_rid . "\">";
										echo $gfunc_obj->getConvertedID_rid( $tmp_rid );
										echo "</a>&nbsp;";
									}
									else
									{
										echo "<span class=\"linkDisabled\">";
										echo $gfunc_obj->getConvertedID_rid( $tmp_rid );
										echo "</span>";
									}
								}
								else
								{
									echo "None";
								}
							?></td>
						</tr>
					<?php
				}
			?>
			</table>	<!-- close table -->
			<?php
		}
	}


	/**
	 * Print Cell Line parents section on Cell Line detailed view
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2009-12-22
	 *
	 * @param INT $reagentIDToView Reagent ID
	*/
	function printCellLineParents($reagentIDToView)
	{
		global $conn;
	
		$gfunc_obj = new generalFunc_Class();
		$bfunc_obj = new Reagent_Background_Class();
		$rfunc_obj = new Reagent_Function_Class();
	
		$colspan = 6;
	
		$currUserCategory = $_SESSION["userinfo"]->getCategory();
	
		?>
		<table border="0" width="100%" cellspacing="5" cellpadding="5">
			
			<tr>
				<td colspan="<?php echo $colspan; ?>">
					<table border="0" width="100%">
						<TR>
							<td colspan="6" style="color:#0000BB; text-align:left; font-weight:bold;">
								Parents
							</td>
						</tr>
						
						<tr>
							<td width="150px" style="padding-left:10px; padding-top:10px;">
								Parent Vector
							</td>

							<td style="text-align:left; padding-top:10px;">
								<?php
									$tmp_rid = $bfunc_obj->get_Background_rid($reagentIDToView, "cell line parent vector id");

									if ($tmp_rid > 0)
									{
										// August 17/07: Restrict viewing by project - if parent belongs to a project you don't have access to, don't allow linking
										$tmpProject = getReagentProjectID($tmp_rid);
										$userProjects = getAllowedUserProjectIDs($_SESSION["userinfo"]->getUserID());
		
										if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($tmpProject, $userProjects))
										{
											echo "<a href=\"Reagent.php?View=6&rid=" . $tmp_rid . "\">";
											echo $gfunc_obj->getConvertedID_rid( $tmp_rid );
											echo "</a>&nbsp;";
										}
										else
										{
											echo "<span class=\"linkDisabled\">";
											echo $gfunc_obj->getConvertedID_rid( $tmp_rid );
											echo "</span>";
										}
									}
									else
									{
										echo "None";
									}
								?>
							</td>
						</tr>
			
						<tr>
							<td width="150px" style="padding-left:10px; padding-top:8px;">Parent Cell Line</td>

							<td style="text-align:left; padding-top:8px;">
								<?php
									$tmp_rid = $bfunc_obj->get_Background_rid($reagentIDToView, "parent cell line id");
									
									if( $tmp_rid > 0 )
									{
										// August 17/07: Restrict viewing by project - if parent belongs to a project you don't have access to, don't allow linking
										$tmpProject = getReagentProjectID($tmp_rid);
										$userProjects = getAllowedUserProjectIDs($_SESSION["userinfo"]->getUserID());
		
										if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($tmpProject, $userProjects))
										{
											echo "<a href=\"Reagent.php?View=6&rid=" . $tmp_rid . "\">";
											echo $gfunc_obj->getConvertedID_rid( $tmp_rid );
											echo "</a>&nbsp;";
										}
										else
										{
											echo "<span class=\"linkDisabled\">";
											echo $gfunc_obj->getConvertedID_rid( $tmp_rid );
											echo "</span>";
										}
									}
									else
									{
										echo "None";
									}
								?>
							</td>
							
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Prints the parent/child association section of reagent detailed view in Modify mode
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-04-26
	 *
	 * @param INT $reagentType Reagent type ID (e.g. '1' for Vector)
	 * @param INT $reagentIDToView Reagent ID
	 * @param INT $check Error code from changing parent information (e.g. in Cell Lines)
	 * @param STRING $error_type Indicates the view/reagent type where the error occurred
	*/
	function printForm_Vector_modify_associations($reagentType, $reagentIDToView, $check, $error_type="")
	{
		global $conn;
		global $cgi_path;
		
		$gfunc_obj = new generalFunc_Class();
		$bfunc_obj = new Reagent_Background_Class();
		$rfunc_obj = new Reagent_Function_Class();

		$colspan = 4;

		$currUserName = $_SESSION["userinfo"]->getDescription();		// Nov. 14/07
		
		// Associations vary according to reagent type
		switch ($reagentType)
		{
			case "1":	// vector
				// Changed June 7/06, Marina:
				// Determine vector's cloning method.  DO NOT DISPLAY FORM FOR PARENT VECTORS!!!!!!!
				$query = "SELECT * FROM `Association_tbl` WHERE `reagentID`='" . $reagentIDToView . "' AND `status`='ACTIVE'";
				$cloning_method_rs = mysql_query($query, $conn) or die("Could not determine cloning method: " . mysql_error());

				if ($cloning_method_ar = mysql_fetch_array($cloning_method_rs, MYSQL_ASSOC))
					$cloningMethod = $cloning_method_ar["ATypeID"];
				else
					$cloningMethod = 3;	// hard-code BASIC cloning method - parent vector

				// Feb. 17/10
				$clAssoc = $rfunc_obj->findReagentTypeAssociations($reagentType);

				foreach ($clAssoc as $assocID => $assocValue)
				{
					$assocAlias = $_SESSION["ReagentAssoc_ID_Alias"][$assocID];

					?><INPUT TYPE="hidden" ID="<?php echo $_SESSION["ReagentType_ID_Name"][$reagentType]; ?>_assoc_<?php echo $assocAlias; ?>_input" VALUE="<?php echo $assocID; ?>"><?php
				}

				if ($cloningMethod != '3')
				{
					?>
					<table border="1" frame="hsides" rules="groups" width="100%" cellspacing="5" cellpadding="5">
						<tr>
							<td nowrap width="200px">
								Parent Vector <b>OpenFreezer</b> ID
							</td>
							<?php

							if ($check != 3)
							{
// echo "Check NOT 3 ";
// echo "GET PV " . $_GET["P"];
								$font_color = "000000";

								if (!isset($_POST["change_vp_id"]))
								{
									if (isset($_GET["P"]))	// there is an error but it's not PV
									{
										$tmp_rid = $gfunc_obj->get_rid($_GET["P"]);
										$font_color = "#000000";
										$tmpPV = $_GET["P"];
									}
									elseif (isset($_GET["PV"]))	// PV is the reason for error
									{
										$tmp_rid = $gfunc_obj->get_rid($_GET["PV"]);
										$font_color = "#FF0000";
										$tmpPV = $_GET["PV"];
									}
									else
									{
										$tmp_rid = $bfunc_obj->get_Background_rid($reagentIDToView, "Vector Parent ID");
										$font_color = "000000";
									}
								}
								else
								{
									$tmp_rid = $gfunc_obj->get_rid($_POST["parent_vector_name_txt"]);
									$font_color = "0000FF";
								}
	
								echo "<td colspan=\"2\">";

								echo "<INPUT TYPE=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"10px\" name=\"parent_vector_name_txt\" id=\"parent_vector_id_txt\" style=\"color:" . $font_color . "\" value=\"";

								echo $gfunc_obj->getConvertedID_rid($tmp_rid) . "\">";

								echo "</td>";
							}
							else
							{
// echo "Check 3";
								// print the last value entered for parent
								if (isset($_POST["parent_vector_name_txt"]))
								{
									$tmp_rid = $gfunc_obj->get_rid($_POST["parent_vector_name_txt"]);
									$tmpPV = strtoupper($_POST["parent_vector_name_txt"]);
									$font_color = "FF0000";
								}
								elseif (isset($_GET["PV"]))	// error
								{
									$tmp_rid = $gfunc_obj->get_rid($_GET["PV"]);
									$font_color = "FF0000";
									$tmpPV = $_GET["PV"];
								}
								elseif (isset($_GET["P"]))
								{
									// Nov. 14/07: Pass 'P' as argument from CGI if PV is OK but the other parent is not
									$tmp_rid = $gfunc_obj->get_rid($_GET["P"]);
									$font_color = "0000FF";
								}
								else
								{
									// ???
									$tmp_rid = "";
									$font_color = "0000FF";
								}

								echo "<td>";

								echo "<INPUT TYPE=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"10px\" name=\"parent_vector_name_txt\" id=\"parent_vector_id_txt\" style=\"color:" . $font_color . "\" value=\"";
								echo $gfunc_obj->getConvertedID_rid($tmp_rid) . "\">";
// 								echo "\">";

								echo "</td>";

								if (strlen($tmpPV) > 0)
									echo "<a name=\"rp\"></a><td style=\"color:" . $font_color . "\">You may not use " . $tmpPV . " as your parent Vector, since you do not have Read access to its project.  Please select another reagent, or contact the project owner to obtain permissions.</td>";
							}
						?>
					</tr>
					<?php
						// ====================================================================================
						// Modified May 12/06 by Marina
						// Now decide what to show here - insert or IPV - based on the vector's cloning method:
			
						// if PCR - display only PV and I
						// if LOXP - display PV
						// ====================================================================================
						switch ($cloningMethod)
						{
							case '1':	
								// PCR
								// Show the insert
								?>
								<tr>
									<td width="100px" nowrap>
										Insert ID
									</td>
			
									<?php
										if (!isset($_POST["change_vp_id"]))
										{
											$tmp_rid = $bfunc_obj->get_Background_rid($reagentIDToView, "Insert ID");
											$font_color = "000000";
										}
										else
										{
											$tmp_rid = $gfunc_obj->get_rid($_POST["insert_name_txt"]);
											$font_color = "0000FF";	// mark change in blue
										}

										if ($check == 2)
											$font_color = "FF0000";
								
										echo "<td colspan=\"2\">";

										echo "<a name=\"ri\"></a><INPUT onKeyPress=\"return disableEnterKey(event);\" TYPE=\"text\" style=\"color:" . $font_color . "\" size=\"10px\" name=\"insert_name_txt\" id=\"insert_id_txt\" value=\"";
						
										if( $tmp_rid > 0 )
										{
											echo $gfunc_obj->getConvertedID_rid($tmp_rid) . "\">";
										}
										else
										{
											if (isset($_POST["insert_name_txt"]))
											{
												echo strtoupper($_POST["insert_name_txt"]) . "\">";
											}
											else
											{
												echo "\">";
											}
										}

										if ($check == 2)
										{
											if (isset($_POST["insert_name_txt"]))
											{
												$tmpInsert = $_POST["insert_name_txt"];
											}
											elseif (isset($_GET["I"]))
											{
												$tmpInsert = $_GET["I"];
											}

											if (strlen($tmpInsert) > 0)
												echo "<a name=\"ri\"></a><td style=\"color:" . $font_color . "\">You may not use " . $tmpInsert . " as your parent Insert, since you do not have Read access to its project.  Please contact the project owner or administrator.";
										}

										// Jan. 29/09: Do not give reverse option for gateway clones
										$vType = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["vector type"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));

// 	
// 										echo "vector type " . $vType;

										if (strpos(strtolower($vType), 'gateway') === false)
										{
											// Feb. 25/08: Check cDNA orientation; if reverse - show checkbox checked by default

											$cdnaDir = $rfunc_obj->getPropertyDirection($reagentIDToView, $_SESSION["ReagentProp_Name_ID"]["cdna insert"]);

											?>&nbsp;&nbsp;<INPUT TYPE="checkbox" ID="reverseInsertCheckbox" <?php if ($cdnaDir == 'reverse') echo "checked"; ?> onClick="if (this.checked == true) document.getElementById('customSitesCheckbox').checked=true; showHideCustomSites()" NAME="reverse_insert" style="font-size:10pt">Reverse Complement<?php
										}

										echo "</td>";
									?>
								</tr>
								<?php
										// Jan. 29/09: Gateway sites are not customizeable
										$vType = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["vector type"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));

										if (strpos(strtolower($vType), 'gateway') === false)
										{
											?>
											<TR>
												<TD colspan="4" style="text-align:left; padding-left:20px; padding-top:10px;">
							
													<!-- Nov. 17/08: Add option to make custom sites -->
													<INPUT TYPE="checkbox" ID="customSitesCheckbox" NAME="custom_sites" style="font-size:10pt" onClick="showHideCustomSites()"> Customize cloning sites<BR>
													<span style="margin-left:20px; font-size:9pt;">(Click here if Insert and Parent Vector sites are not identical)</span>
													<BR><BR>
							
													<TABLE cellpadding="4px" ID="customCloningSites" style="display:none; border:1px solid black;">
														<TH colspan="2">
															<b>Please specify cloning sites on the Parent Vector and insert:</b><BR>
														</TH>
							
														<tr>
															<td colspan="2" style="font-size:9pt; font-weight:bold">
																<HR>Parent Vector Cloning Sites:
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
																<HR>Insert Cloning Sites:
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
												</TD>
											</TR>
											<?php

										}	// close if ($vType not gateway)
							break;
		
							case '2':
								// LOXP
								// Show IPV
								?>
								<tr>
									<td width="100px" nowrap>
										Insert Parent Vector ID
									</td>
			
									<?php
										if (!isset($_POST["change_vp_id"]))
										{
											$tmp_rid = $bfunc_obj->get_Background_rid($reagentIDToView, "Parent Insert Vector");
											$font_color = "000000";
										}
										else
										{
											$tmp_rid = $gfunc_obj->get_rid($_POST["ipv_name_txt"]);
											$font_color = "0000FF";
										}
										
										if ($check == 2)
											$font_color = "FF0000";
										
										echo "<td width=\"100px\" colspan=\"2\" nowrap>";
										echo "<INPUT TYPE=\"text\" onKeyPress=\"return disableEnterKey(event);\" size=\"10px\" name=\"ipv_name_txt\" style=\"color:" . $font_color . "\" id=\"ipv_id_txt\" value=\"";
					
										if ($tmp_rid > 0)
										{
											echo $gfunc_obj->getConvertedID_rid( $tmp_rid ) . "\">";
										}
										else
										{
											if (isset($_POST["ipv_name_txt"]))
											{
												echo strtoupper($_POST["ipv_name_txt"]) . "\">";
											}
											else
											{
												echo "\">";
											}
										}
					
										echo "</td>";
									
										if ($check == 2)
										{
											if (isset($_POST["ipv_name_txt"]))
											{
												$tmpIPV = $_POST["ipv_name_txt"];
											}
											elseif (isset($_GET["IPV"]))
											{
												$tmpIPV = $_GET["IPV"];
											}

											if (strlen($tmpIPV) > 0)
												echo "<a name=\"ipvr\"></a><td style=\"color:" . $font_color . "\">You may not use " . $tmpIPV . " as your Insert Parent Vector, since you do not have Read access to its project.  Please contact the project owner or administrator.</td>";
										}
									?>
								</tr>
							<?php
							break;
							
							default:	// something wrong
								echo "What's up, doc???";
							break;
						}	// end switch
						?>
				</table><?php
				}	// end cloning method != 3
			break;

			case "2":	// insert
				// Change Oligos and Insert Parent Vector
				
				// Determine if an error was issued:
				if (isset($_GET["ErrCode"]) && ($_GET["ErrCode"] == 5))
				{
					if (isset($_GET["IPV"]))
					{
						// Show IPV error
						$ipv_warning_display = "inline";
						$sense_warning_display = "none";
						$antisense_warning_display = "none";
					}
					elseif (isset($_GET["S"]))
					{
						// Show IPV error
						$sense_warning_display = "inline";
						$ipv_warning_display = "none";
						$antisense_warning_display = "none";
					}
					elseif (isset($_GET["A"]))
					{
						// Show IPV error
						$antisense_warning_display = "inline";
						$ipv_warning_display = "none";
						$sense_warning_display = "none";
					}
				}
				else
				{
					// Don't show any errors
					$antisense_warning_display = "none";
					$ipv_warning_display = "none";
					$sense_warning_display = "none";
				}
				?>
				<SCRIPT language="javascript">

					function previewDetails()
					{
						var parent_vector_id = "parent_vector_id_txt";
						var parentElement = document.getElementById(parent_vector_id);
						var parentValue = parentElement.value;
					}

				</SCRIPT>

				<table width="100%" cellspacing="5" cellpadding="5">
					<tr>
						<td colspan="3" style="font-weight:bold; padding-left:15px;" class="detailedView_BlankTitle">
							Parent Child Information
						</td>
					</tr>

					<tr>
						<td width="180px" style="white-space:nowrap; padding-left:15px;">
							Sense Oligo ID
						</td>
	
						<?php
							echo "<td style=\"white-space:nowrap\">";
							echo "<INPUT onKeyPress=\"return disableEnterKey(event);\" TYPE=\"text\" size=\"10px\" name=\"assoc_sense_oligo\" id=\"sense_oligo_id_txt\" style=\"color:" . $font_color . "\" value=\"";	// mar 14/07
						
							$tmp_rid = $bfunc_obj->get_Background_rid($reagentIDToView, "sense oligo");

							if ($tmp_rid > 0)
							{
								echo $gfunc_obj->getConvertedID_rid($tmp_rid) . "\">";
							}
							else
							{
								echo "\">";
							}
		
							echo "</td>";

							// Sept. 7/07: Error output
							echo "<td>";

							if (strlen($tmp_rid) > 0)
								echo "<span style=\"color:#FF0000; display:" . $sense_warning_display . "\">You may not use <span style=\"color:#0000FF;\">" . $gfunc_obj->getConvertedID_rid($tmp_rid) . "</span> as your Sense Oligo, since you do not have Read access to its project.  Please contact the project owner to obtain permission or select a different Sense Oligo value.";
							echo "</td>";
						?>
					</tr>
					<tr>
						<td width="180px" style="padding-left:15px; white-space:nowrap;">
							Antisense Oligo ID
						</td>

						<?php
							$tmp_rid = $bfunc_obj->get_Background_rid($reagentIDToView, "antisense oligo");
		
							echo "<td>";

							echo "<INPUT onKeyPress=\"return disableEnterKey(event);\" TYPE=\"text\" size=\"10px\" name=\"assoc_antisense_oligo\" id=\"antisense_id_txt\" value=\"";		// mar 14/07
							
							if( $tmp_rid > 0 )
							{
								echo $gfunc_obj->getConvertedID_rid($tmp_rid) . "\">";
							}
							else
							{
								echo "\">";
							}
		
							echo "</td>";
							
							// Sept. 7/07: Error output
							echo "<td>";
	
							if (strlen($tmp_rid) > 0)
								echo "<span style=\"color:#FF0000; display:" . $antisense_warning_display . "\">You may not use <span style=\"color:#0000FF;\">" . $gfunc_obj->getConvertedID_rid($tmp_rid) . "</span> as your Antisense Oligo, since you do not have Read access to its project.  Please contact the project owner to obtain permission or select a different Antisense Oligo value.";
							
							echo "</td>";
						?>
					</tr>

					<tr>
						<td style="padding-left:15px; white-space:nowrap;">
							Insert Parent Vector ID
						</td>

						<?php
							$tmp_rid = $bfunc_obj->get_Background_rid($reagentIDToView, "Insert Parent Vector ID");
			
							echo "<td>";

							echo "<INPUT onKeyPress=\"return disableEnterKey(event);\" TYPE=\"text\" size=\"10px\" name=\"assoc_insert_parent_vector\" id=\"ipv_id_txt\" value=\"";	// mar 14/07
		
							if ($tmp_rid > 0)
							{
								echo $gfunc_obj->getConvertedID_rid( $tmp_rid ) . "\">";
							}
							else
							{
								echo "\">";
							}
		
							echo "</td>";
							
							// Sept. 7/07: Error output
							echo "<td>";

							if (strlen($tmp_rid) > 0)
								echo "<span style=\"color:#FF0000; display:" . $ipv_warning_display . "\">You may not use <span style=\"color:#0000FF;\">" . $gfunc_obj->getConvertedID_rid($tmp_rid) . "</span> as your Insert Parent Vector, since you do not have Read access to its project.  Please contact the project owner to obtain permission or select a different Insert Parent Vector value.";

							echo "</td>";
						?>
					</tr>
				<?php 	
//					echo "</FORM>"; 
				?>
			</table><?php
			break;

			case "3":	// oligo

			break;

			case "4":	// cell line
// 				$currUserName = $_SESSION["userinfo"]->getDescription();

				$clAssoc = $rfunc_obj->findReagentTypeAssociations($reagentType);

				foreach ($clAssoc as $assocID => $assocValue)
				{
					$assocAlias = $_SESSION["ReagentAssoc_ID_Alias"][$assocID];

					?><INPUT TYPE="hidden" ID="<?php echo $_SESSION["ReagentType_ID_Name"][$reagentType]; ?>_assoc_<?php echo $assocAlias; ?>_input" VALUE="<?php echo $assocID; ?>"><?php
				}

				?>
				<table border="0" width="100%" cellspacing="5" cellpadding="5">
					<?php
						echo "<FORM METHOD=POST ACTION=\"" . $_SERVER["PHP_SELF"] . "?View=" . $_GET["View"] . "&rid=" . $reagentIDToView . "\">";
					?>

					<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

					<tr>
						<td align=left class="detailedView_BlankTitle" colspan="<?php echo $colspan;?>">
							<table width="100%" border="1" rules="none" frame="hsides" cellpadding="5">
								<tr>
									<td style="white-space:nowrap; font-weight:bold">
										Cell Line Background Information<BR>
										<P style="font-size:8pt; color:blue; font-weight:normal">Pressing "Change" uploads the new parent vector and cell line details on the form in blue.Please verify all properties before saving.
									</td>
								</tr>
							</table>
						</td>
					</tr>
	
					<tr>
						<td width="100px">
							Parent Vector
						</td>
	
						<?php
//							echo "<td>";
							$font_color = "000000";

//							if ($check == 1)
							if ($check != 3)
							{
								if (!isset($_POST["change_cl_id"]) && !isset($_POST["change_vp_id"]))
								{
									$tmp_rid = $bfunc_obj->get_Background_rid($reagentIDToView, "Cell Line Parent Vector ID");
									$font_color = "000000";
								}
								else
								{
									$tmp_rid = $gfunc_obj->get_rid($_POST["pv_name_txt"]);
									$font_color = "0000FF";	
								}

								echo "<td colspan=\"2\">";
							}
							else
							{
								// print the last value entered for parent
								if (isset($_POST["pv_name_txt"]))
									$tmp_rid = $gfunc_obj->get_rid($_POST["pv_name_txt"]);
								
								// Added August 28/07, Marina: Changed variable names due to separation of nested forms
								else if (isset($_GET["ErrVal"]))
									$tmp_rid = $gfunc_obj->get_rid($_GET["ErrVal"]);
								else
								{
									// ???
								}

								$font_color = "FF0000";
							
								echo "<TD>";
							}
	
							echo "<INPUT onKeyPress=\"return disableEnterKey(event);\" TYPE=\"text\" size=\"10px\" name=\"pv_name_txt\" id=\"pv_id_txt\" style=\"color:" . $font_color . "\" value=\"";
	
							if ($tmp_rid > 0)
								echo $gfunc_obj->getConvertedID_rid($tmp_rid) . "\">";
							else
								echo "\">";
							
							echo "</td>";

							if ($check == 3)
							{
								if (strlen($_POST["pv_name_txt"]) > 0)
									echo "<td style=\"color:" . $font_color . "\">You may not use " . $_POST["pv_name_txt"] . " as your parent Vector, since you do not have Read access to its project.  Please contact the project owner or administrator.</td>";
							}
						?>
					</tr>
	
					<tr>
						<td width="100px">
							Parent Cell Line
						</td>
	
						<?php
							$font_color = "000000";
	
							if ($check != 4)
							{
								if (!isset($_POST["change_cl_id"]) && !isset($_POST["change_vp_id"]))
								{
									$tmp_cl_id = $bfunc_obj->get_Background_rid($reagentIDToView, "Parent Cell Line ID");
									$font_color = "000000";
	
									// Set flag to remember if Change button was pressed
									echo "<input type=\"hidden\" id=\"assoc_cl_change\" name=\"assoc_change_cl_flag\" value=\"0\">";
								}
								else
								{
									$tmp_cl_id = $gfunc_obj->get_rid($_POST["cl_name_txt"]);
									$font_color = "0000FF";
	
									// Set flag to remember if Change button was pressed
									echo "<input type=\"hidden\" id=\"assoc_cl_change\" name=\"assoc_change_cl_flag\" value=\"1\">";
								}

								echo "<td colspan=\"2\">";
							}
							else
							{
								// print the last value entered for parent
								if (isset($_POST["cl_name_txt"]))
								{
									$tmp_cl_id = $gfunc_obj->get_rid($_POST["cl_name_txt"]);
								}
								else if (isset($_POST["detailed_view_cl_backfillID_old"]))
								{
									$tmp_cl_id = $gfunc_obj->get_rid($_POST["detailed_view_cl_backfillID_old"]);
								}
								elseif (isset($_GET["CL"]))
								{
									$tmp_cl_id = $gfunc_obj->get_rid($_GET["CL"]);
								}
								else
								{
									// ???
	//								print_r($_POST);
								}
	
								if (strcasecmp($error_type, "Cell Line") == 0)
								{
									$font_color = "FF0000";
								}

								echo "<input type=\"hidden\" id=\"assoc_cl_change\" name=\"assoc_change_cl_flag\" value=\"" . (isset($_POST["change_cl_id"]) ? "1" : "0") . "\">";

								echo "<TD>";
							}
	
							if ($check == 4)
								$font_color = "FF0000";

							echo "<INPUT onKeyPress=\"return disableEnterKey(event);\" TYPE=\"text\" size=\"10px\" name=\"cl_name_txt\" id=\"cl_id_txt\" style=\"color:" . $font_color . "\" value=\"";
		
							if ($tmp_cl_id > 0)
							{
								echo $gfunc_obj->getConvertedID_rid($tmp_cl_id) . "\">";
							}
							else
							{
								echo $_POST["cl_name_txt"] .  "\">";	// sept 14/07 - print the value entered
							}
		
							echo "</td>";

							if ($check == 4)
							{
								if (isset($_POST["cl_name_txt"]))
								{
									$tmpCL = $_POST["cl_name_txt"];
								}
								elseif (isset($_GET["CL"]))
								{
									$tmpCL = $_GET["CL"];
								}

								if (strlen($tmpCL))
									echo "<td style=\"color:" . $font_color . "\">You may not use " . $tmpCL . " as your parent Cell Line, since you do not have Read access to its project.  Please contact the project owner or administrator.</td>";
							}
						?>
					</tr>

					<tr>
						<td>&nbsp;</td>

						<td colspan="2">
							<INPUT TYPE="SUBMIT" NAME="change_vp_id" VALUE="Change" onClick="return checkParentFormat('<?php echo str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]); ?>')">
						</td>
					</tr>
				</table>
				<?php
			break;

			default:

			break;
		}
	}

	/**
	 * Print Cell Line details page (using old implementation for cell lines; for other reagent types see print_Detailed_Reagent_Other())
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-04-26
	 *
	 * @param boolean $modify_state Are we in modify mode?
	 * @param INT $view Page (view) number for redirection
	 * @param INT $reagentIDToView Reagent ID
	 * @param INT $check Error code from changing parent information (e.g. in Cell Lines)
	 * @param boolean $modify_restricted Is modification restricted for the current user according to his/her project and/or system access? 'Modify' button is disabled if user is not authorized to modify
	 * @param STRING $error_type Indicates the view/reagent type where the error occurred
	 *
	 * @see print_Detailed_Reagent_Other()
	*/
	function print_Detailed_Cellline($modify_state, $view, $reagentIDToView, $check, $modify_restricted, $error_type="")
	{
		global $conn;
		global $cgi_path;

		$gfunc_obj = new generalFunc_Class();
		$bfunc_obj = new Reagent_Background_Class();
		$rfunc_obj = new Reagent_Function_Class();
		
		$first_w = "150px";
		$second_w = "150px";
		$third_w = "100px";
		
		$cellline_prefix = "reagent_detailedview_";
		$cellline_postfix = "_prop";

		$normal_rs = mysql_query("SELECT `propertyID`, `propertyValue` FROM `ReagentPropList_tbl` WHERE `status`='ACTIVE' AND `reagentID`='" . $reagentIDToView . "';", $conn);
	
		$antibiotic_resistance_ar = array();
		$alternate_ids_ar = array();

		// Dec. 22/09 - CHANGE CATEGORY TO 'GROWTH PROPERTIES'!!!!!!!!!!!!
		$selMarkerPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["selectable marker"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]);

		$altID_PropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["alternate id"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]);

		while( $currentProp_ar = mysql_fetch_array( $normal_rs, MYSQL_ASSOC))
		{
			switch ($currentProp_ar["propertyID"])
			{
				case $selMarkerPropID:
					$antibiotic_resistance_ar[] = $currentProp_ar["propertyValue"];
				break;

				case $altID_PropID:
					$alternate_ids_ar[] = $currentProp_ar["propertyValue"];
				break;

				default:
					$tempPropHolder_ar[$currentProp_ar["propertyID"]] = stripslashes($currentProp_ar["propertyValue"] );
				break;
			}
		}

		mysql_free_result( $normal_rs );

		$tempPropHolder_ar[$selMarkerPropID] = $antibiotic_resistance_ar;
		$tempPropHolder_ar[$altID_PropID] = $alternate_ids_ar;

		// May 1/06, Marina
		$new_pv_props_ar = array();
		$new_cl_props_ar = array();

		if (isset($_POST["change_vp_id"]) || isset($_POST["change_cl_id"]))
		{
			$change_parent_vector = true;
			$change_parent_cellline = true;

			// Preload details of new parent vector
			$new_VP = $_POST["pv_name_txt"];
			$new_VP_id = $gfunc_obj->get_rid($new_VP);

			// Preload details of new parent cell line
			$new_CL = $_POST["cl_name_txt"];
			$new_CL_id = $gfunc_obj->get_rid($new_CL);
		}
		
		$colspan = 4;

		// August 24/07: Find the cloning method
		// September 13/07: For Cell Lines there is only one: ATypeID = 5. Hardcode
		$cloningMethod = 5;
		
		$typeOf_rs = mysql_query("SELECT `reagentTypeID`, `groupID` FROM `Reagents_tbl` WHERE `status`='ACTIVE' AND `reagentID`='" . $reagentIDToView . "'", $conn) or die("Error: " . $mysql_error());
		
		if ($typeOf_ar = mysql_fetch_array($typeOf_rs, MYSQL_ASSOC))
		{
			$reagentType = $typeOf_ar["reagentTypeID"];
			$reagentGroup = $typeOf_ar["groupID"];
		}
		
		$currUserName = $_SESSION["userinfo"]->getDescription();

		?><FORM onSubmit="if (verifyCellLineParents() && checkMandatoryProps('CellLine') ) return true; else return false;" NAME="process_cell_line_props" METHOD="POST" ACTION="<?php echo $cgi_path . "update.py"; ?>">

		<!-- Hidden form elements for Python -->
		<input type="hidden" name="reagent_id_hidden" value="<?php echo $reagentIDToView; ?>">
		<input type="hidden" name="save_section" value="associations" ID="preload_cell_line">
		<input type="hidden" name="reagent_typeid_hidden" value="<?php echo $reagentType; ?>">
		<input type="hidden" name="reagent_groupnum_hidden" value="<?php echo $reagentGroup; ?>">
		<input type="hidden" name="cloning_method_hidden" value="<?php echo $cloningMethod; ?>">
		<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">

		<table width="786px" cellpadding="2px" valign="middle" name="reagent_props" class="detailedView_tbl" border="1" frame="box" rules="none">
		
	 	<th colspan="6" class="detailedView_heading" style="font-size:15pt">
			<?php
				echo $gfunc_obj->getConvertedID($reagentType, $reagentGroup);
			?>
		</th>
		<tr><td colspan="4"><HR></td></tr>

	  	<tr>
			<td colspan="6">
				<!-- insert a small table for the buttons -->
				<table width="100%" class="detailedView_buttons" border="1" frame="vsides" rules="all">
					<tr>
					<?php 
						if (!$modify_restricted)
						{
							if( $modify_state )
							{
								// "Save" and "Cancel" buttons
								echo "<td>";
								echo "<INPUT TYPE=\"SUBMIT\" NAME=\"change_state\" VALUE=\"Save\" onClick=\"document.pressed=this.value; selectAllPropertyValues(); changeParentValues('" . $cloningMethod . "');\">";
								echo "</td>";

								echo "<td>";
								echo "<INPUT TYPE=\"SUBMIT\" NAME=\"change_state\" VALUE=\"Cancel\" onClick=\"document.pressed=this.value\">";
								echo "</td>";
							}
							else
							{
								// "Modify" button
								echo "<td><INPUT TYPE=\"SUBMIT\" NAME=\"change_state\" VALUE=\"Modify\"></td>";

								// Location link
								echo "<td><a href=\"Location.php?View=1&rid=" . $reagentIDToView . "\">Location</a></td>";
							}
						}
						else
						{
							// "Modify" button
							echo "<td><INPUT TYPE=\"SUBMIT\" NAME=\"change_state\" VALUE=\"Modify\" disabled></td>";

							// Location link
							echo "<td><a href=\"Location.php?View=1&rid=" . $reagentIDToView . "\">Location</a></td>";
						}

						// June 9/09 ===>>> NOOOOOOOOOOOO, check for preps and children!!!!!!!!!!!
						if ($currUserName == 'Administrator')
						{
							?><TD><SPAN class="linkExportSequence" style="font-size:10pt; padding-top:0; margin-left:5px;" onClick="deleteReagentFromDetailedView('<?php echo $reagentIDToView; ?>');">Delete</SPAN></TD><?php
						}

					?>
					</tr>
				</table>
			</td>
		</tr>

		<TR><TD colspan="6"><HR></TD></TR>
		<?php

		$rPropCategories = $rfunc_obj->findAllReagentTypeAttributeCategories($_SESSION["ReagentType_Name_ID"]["CellLine"]);
		$cellLineAttribs = $rfunc_obj->getAllReagentTypeAttributes($_SESSION["ReagentType_Name_ID"]["CellLine"]);

		$r_type = "CellLine";
		
		foreach ($rPropCategories as $categoryID => $category)
		{
			$rTypeAttributes = $rfunc_obj->getReagentTypeAttributesByCategory($_SESSION["ReagentType_Name_ID"][$r_type], $categoryID);

			if (count($rTypeAttributes) == 0)
				continue;

			$catAlias = $_SESSION["ReagentPropCategory_ID_Alias"][$categoryID];

			echo "<TR>";
				echo "<TD style=\"padding-left:10px;\">";
					echo "<table style=\"width:98%; background-color:#FFF8DC; border:1px groove black; padding:5px;\">";
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
								echo "<table ID=\"category_" . $catAlias . "_section_" . $r_type . "\" cellpadding=\"4\" cellspacing=\"2\" style=\"margin-top:4px;\">";

								// Update May 5, 2010: $key now corresponds to reagentTypePropertyID column in ReagentTypeAttributes_tbl
								foreach ($rTypeAttributes as $key => $attribute)
								{
									$categoryName = $attribute->getPropertyCategory();
	
									$propName = $attribute->getPropertyName();

									// May 11, 2011: verify mandatory props on saving
									if ($rfunc_obj->isMandatory($r_type, $propName))
										echo "<INPUT TYPE=\"hidden\" NAME=\"" . $r_type . "_mandatoryProps[]\" VALUE=\"" . $key . "\">";
									
									$propID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$propName], $categoryID);
									$propDescr = $attribute->getPropertyDescription();

									$propval = $rfunc_obj->getPropertyValue($reagentIDToView, $propID);
						
									echo "<TR ID=\"" . $categoryAlias . "\">";
										echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold; white-space:nowrap;\">" . $propDescr . "</TD>";

										if ($change_parent_vector || $change_parent_cellline)
										{
											$new_pv_prop_val = $rfunc_obj->getPropertyValue($new_VP_id, $propID);

											$new_cl_prop_val = $rfunc_obj->getPropertyValue($new_CL_id, $propID);

											// preload properties from parents
											switch (strtolower($propName))
											{
												case 'name':
													if ((strlen($new_pv_prop_val) > 0) && (strlen($new_cl_prop_val) > 0))
													{
														$new_prop_val = $new_pv_prop_val . " in " . $new_cl_prop_val;
													}
													else
													{
														$new_prop_val = (strlen($new_pv_prop_val) > 0) ? $new_pv_prop_val : $new_cl_prop_val;
													}
						
													echo "<TD colspan=\"4\" class=\"detailedView_value_load\" style=\"white-space:normal;\">";
														$this->print_property_final($cellline_prefix . $r_type . "_" . $catAlias . "_:_" . $_SESSION["ReagentProp_Name_Alias"][$propName] . $cellline_postfix, $propName, $new_prop_val, $reagentIDToView, true, $categoryName, "", "", "CellLine");

													echo "</TD>";
												break;
						
												case 'selectable marker':
												case 'species':
												case 'tissue type':
												case 'developmental stage':
												case 'morphology':
													echo "<TD colspan=\"4\" class=\"detailedView_value_load\" style=\"white-space:normal;\">";
												
														$this->print_property_final($cellline_prefix . $r_type . "_" . $catAlias . "_:_" . $_SESSION["ReagentProp_Name_Alias"][$propName] . $cellline_postfix, $propName, $new_cl_prop_val, $new_CL_id, true, $categoryName, "", "", "CellLine");
													echo "</TD>";
												break;
							
												default:
													echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
														$this->print_property_final($cellline_prefix . $r_type . "_" . $catAlias . "_:_" . $_SESSION["ReagentProp_Name_Alias"][$propName] . $cellline_postfix, $propName, $propval, $reagentIDToView, true, $categoryName, "", "", "CellLine");
													echo "</TD>";
												break;
											}
										}
										else
										{
											echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
												$this->print_property_final($cellline_prefix . $_SESSION["ReagentProp_Name_Alias"][$propName] . $cellline_postfix, $propName, $propval, $reagentIDToView, true, $categoryName, "", "", "CellLine");
											echo "</TD>";
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

			// spacer row
			echo "<TR><TD>&nbsp;</TD></TR>";
		}

		?>
			<!-- Python input -->
			<input type="hidden" id="clpv_id_hidden" name="assoc_cell_line_parent_vector_prop">
			<input type="hidden" id="cl_id_hidden" name="assoc_parent_cell_line_prop">
		
			</form>
			<?php
	
			$old_pv_id = $bfunc_obj->get_Background_rid($reagentIDToView, "cell line parent vector id");
			$old_cl_id = $bfunc_obj->get_Background_rid($reagentIDToView, "parent cell line id");
			
			if (isset($_POST["pv_name_txt"]))
				$old_pv = $_POST["pv_name_txt"];
			else
				$old_pv = $gfunc_obj->getConvertedID_rid($old_pv_id);
			
			if (isset($_POST["cl_name_txt"]))
				$old_cl = $_POST["cl_name_txt"];
			else
				$old_cl = $gfunc_obj->getConvertedID_rid($old_cl_id);
		?>
		<input type="hidden" id="parent_vector_old_id" name="detailed_view_pv_backfillID_old" value="<?php echo $old_pv; ?>">
		<input type="hidden" id="parent_cellline_old_id" name="detailed_view_cl_backfillID_old" value="<?php echo $old_cl; ?>">
		
		<input type="hidden" id="change_pv" name="change_pv_flag" value="">
		<input type="hidden" id="change_cl" name="change_cl_flag" value="">
	
		<!-- *********************************
			Parent/Child section 
			Edited by Marina on May 1/06
		********************************** -->
		<table>
			<tr>
				<td colspan="<?php echo $colspan; ?>">
				<?php
					if ($modify_state)
					{
						$this->printForm_Vector_modify_associations($reagentType, $reagentIDToView, $check, $error_type);
					}
					else
					{
						$this->printForm_Vector_show_associations($reagentType, $reagentIDToView);
					}
				?>
				</td>
			</tr>
		</table>
		<?php
	}


	/**
	 * Print reagent detailed view
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2009-04-28
	 *
	 * @param boolean $modify_state Are we in modify mode?
	 * @param INT $view Page (view) number for redirection
	 * @param INT $reagentIDToView Reagent ID
	 * @param INT $reagentType Reagent type ID
	 * @param INT $reagentGroup Numeric portion of the reagent's OpenFreezer identifier, NOT the database ID (e.g. 'V123' => $reagentGroup = 123)
	 * @param boolean $modify_restricted Is modification restricted for the current user according to his/her project and/or system access? 'Modify' button is disabled if user is not authorized to modify
	*/
	function print_Detailed_Reagent_Other($modify_state, $view, $reagentIDToView, $reagentType, $reagentGroup, $modify_restricted)
	{
		global $conn;
		global $cgi_path;
		global $hostname;
		
		$gfunc_obj = new generalFunc_Class();
		$bfunc_obj = new Reagent_Background_Class();
		$rfunc_obj = new Reagent_Function_Class();
		$sfunc_obj = new Sequence_Function_Class();
		$tempPropHolder_ar = $_SESSION["ReagentProp_ID_Name"];
	
		foreach ($tempPropHolder_ar as $key => $value)
		{
			$tempPropHolder_ar[$key] = "";
		}

		$genPrefix = "reagent_detailedview_";
		$genPostfix = "_prop";

		$rGeneralProps = array();
		$rSequenceProps = array();
		$rSequenceFeatures = array();
		$rClassifiers = array();
		$rExternalIDs = array();

		// Fetch the properties of this reagent in the basic categories first, then check to see if the reagent has more
		$rPropCategories = $rfunc_obj->findAllReagentTypeAttributeCategories($reagentType);	// returns a list of category IDs

		$currUserName = $_SESSION["userinfo"]->getDescription();

		?><table name="reagent_props" style="vertical-align:middle; padding-right:2px; padding-left:10px;" border="1" frame="box" rules="none" cellspacing="4" class="detailedView_tbl" width="768px"><?php

        error_log("reagentType: ".$reagentType." reagentGroup: ".$reagentGroup);
		$limsID = $gfunc_obj->getConvertedID($reagentType, $reagentGroup);

		// July 7/09: Get parents here and print Insert ID next to 'cDNA' feature positions for recombination vectors
		$parents = $bfunc_obj->getReagentParents($reagentIDToView);
		$children = $bfunc_obj->getReagentChildren($reagentIDToView);
		$parentTypes = $bfunc_obj->getAssociationTypes($reagentType);

		$childrenTypes = Array();
		$num_children = Array();

		foreach ($children as $aPropID => $childrenIDs)
		{
			foreach ($childrenIDs as $cKey => $childID)
			{
				$childTypeID = $gfunc_obj->getTypeID($childID);

				$childrenTypes[] = $childTypeID;

				$num_children[$childTypeID]++;
			}
		}

		// July 7/09: print the correct parents for Vectors
		if ($reagentType == $_SESSION["ReagentType_Name_ID"]["Vector"])
		{
			$cloningMethod = $rfunc_obj->getCloningMethod($reagentIDToView);
			$new_parents = Array();
			$newParentTypes = Array();

			switch ($cloningMethod)
			{
				case '1':
					$newParentTypes[] = $_SESSION["ReagentAssoc_Name_ID"]["vector parent id"];
					$newParentTypes[] = $_SESSION["ReagentAssoc_Name_ID"]["insert id"];

					$new_parents[$_SESSION["ReagentAssoc_Name_ID"]["vector parent id"]] = $parents[$_SESSION["ReagentAssoc_Name_ID"]["vector parent id"]];

					$new_parents[$_SESSION["ReagentAssoc_Name_ID"]["insert id"]] = $parents[$_SESSION["ReagentAssoc_Name_ID"]["insert id"]];

					$parents = $new_parents;
					$parentTypes = $newParentTypes;
				break;

				case '2':
					$newParentTypes[] = $_SESSION["ReagentAssoc_Name_ID"]["vector parent id"];
					$newParentTypes[] = $_SESSION["ReagentAssoc_Name_ID"]["parent insert vector"];

					$new_parents[$_SESSION["ReagentAssoc_Name_ID"]["vector parent id"]] = $parents[$_SESSION["ReagentAssoc_Name_ID"]["vector parent id"]];

					$new_parents[$_SESSION["ReagentAssoc_Name_ID"]["parent insert vector"]] = $parents[$_SESSION["ReagentAssoc_Name_ID"]["parent insert vector"]];

					$parents = $new_parents;
					$parentTypes = $newParentTypes;
				break;

				case '3':
				break;
			}

			// April 15/10: Export in GenBank format
			$fList = array();

			$vName = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));

			$vStatus = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["status"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));
									
			$vDescr = $this->get_Special_Column_Type("description", "General Properties", $reagentIDToView, "None");
						
			$vComms = $this->get_Special_Column_Type("comments", "General Properties", $reagentIDToView, "None");

			$verComms = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["verification comments"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));

			$verification = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["verification"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));

			$vType = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["vector type"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));

			$vSrc = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["reagent source"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));

			$vRestr = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["restrictions on use"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));

			$vSeqID = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]));
			$vSeq = $rfunc_obj->getSequenceByID($vSeqID);
		
			$fList = $rfunc_obj->findReagentPropertiesByCategory($reagentIDToView, $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]);

			# Get Insert features
			$insertID = $bfunc_obj->get_Background_rid($reagentIDToView, "insert id");
	
			// Accession - from Insert// 	
			$accession = $rfunc_obj->getPropertyValue($insertID, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["accession number"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]));
	
			$altID = $rfunc_obj->getPropertyValue($insertID, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["alternate id"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]));
	
			$insertName = $rfunc_obj->getPropertyValue($insertID, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));

			// Species - from Insert
			$species = $rfunc_obj->getPropertyValue($insertID, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["species"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]));

			$gbk_content = "";

			$gbk_content .= "LOCUS       " . $vName . "      " . strlen($vSeq) . " bp    DNA   CIRCULAR        " . date('d-m-Y') . "\n";

			$gbk_content .= "DEFINITION  " . $vType . "&#44; " . $vDescr . "\n";
			$gbk_content .= "ACCESSION   " . $limsID . "\n";
			$gbk_content .= "KEYWORDS    \n";
			$gbk_content .= "SOURCE      \n";
			$gbk_content .= "  ORGANISM  \n";
			
			$gbk_content .= "REFERENCE   1 - " . strlen($vSeq) . " (bases 1 to " . strlen($vSeq) . ")\n";

			$gbk_content .= "  AUTHORS   " . $vSrc . "\n";
			$gbk_content .= "  JOURNAL   " . "\n";
	
			$gbk_content .= "COMMENT     " . $vComms . "\n";

			$gbk_content .= "FEATURES             Location/Qualifiers\n";
			$gbk_content .= "     source          1.." . strlen($vSeq) . "\n";
			
			foreach ($fList as $key => $myFeature)
 			{
				foreach ($myFeature as $fKey => $feature)
				{
					$fType = $feature->getFeatureType();
					$fVal =  $feature->getFeatureValue();
					$fStart = $feature->getFeatureStart();
					$fEnd = $feature->getFeatureEnd();
					$fDir = $feature->getFeatureDirection();
					$fDescr = $feature->getFeatureDescriptor();

					$n_sp = 21 - strlen("CDS") - 5;

					$a_sp = "";

					for ($j=0; $j < $n_sp; $j++)
					{
						$a_sp .= " ";
					}
					
					if (strcasecmp($fType, "cdna insert") == 0)
					{					
						if ($fEnd > 0)
						{
							$cdna_start = $fStart;
							$cdna_end = $fEnd;
							$cdna_length = $fEnd - $fStart + 1;
						}
						else
						{
							$cdna_start = 0;
							$cdna_end = 0;
							$cdna_length = 0;
						}
			
						switch ($fDir)
						{
							case 'reverse':
								$gbk_content .= "     CDS" . $a_sp . "complement(" . $cdna_start . ".." . $cdna_end . ")\n";
							break;

							default:
								$gbk_content .= "     CDS" . $a_sp . $cdna_start . ".." . $cdna_end . "\n";
							break;
						}

						$gbk_content .= "                     &#47;organism=" . ucwords($species) . "\n";
						$gbk_content .= "                     /gene=" . $insertName . "\n";
				
						$gbk_content .= "                     /note=accession: " . $accession .  "\n";

						// Alternate ID
						if (is_array($altID))
						{
							$gbk_content .= "                     /note=";

							foreach ($altID as $a => $aID)
							{
								$gbk_content .= $aID . " ";
							}

							$gbk_content .= "\n";
						}
						else
						{
							$gbk_content .= "                     /note=" . $altID . "\n";
						}

						# Get gene ID and translation from Insert	
						$geneID = $rfunc_obj->getPropertyValue($insertID, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["entrez gene id"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]));
				
						if ($geneID)
							$gbk_content .= "                     /db_xref=GeneID:" . $geneID . "\n";
				
						$protSeqObj = $sfunc_obj->findProteinTranslation($insertID);

						if ($protSeqObj)
							$protSeq = $protSeqObj->getSequence();
									
						if ($protSeq)
						{
							$gbk_content .= "                     /translation=" . $protSeq . "\n";
						}

						$gbk_content .= "                     /SECDrawas=Gene\n";
					}
					else if ( (strcasecmp($fType, "selectable marker") == 0) || (strcasecmp($fType, "tag") == 0))
					{
						switch ($fDir)
						{
							case 'reverse':
								$gbk_content .= "     CDS" . $a_sp . "complement(" . $fStart . ".." . $fEnd . ")\n";
							break;

							default:
								$gbk_content .= "     CDS" . $a_sp . $fStart . ".." . $fEnd . "\n";
							break;
						}

						$gbk_content .= "                     /note=" . $fType . "\n";
						$gbk_content .= "                     /gene=" . $fVal . "\n";
						$gbk_content .= "                     /SECDrawas=Region\n";
					}
					else if (strcasecmp($fType, "promoter") == 0)
					{
					
						switch ($fDir)
						{
							case 'reverse':
								$gbk_content .= "     promoter        complement(" . $fStart . ".." . $fEnd . ")\n";
							
							break;

							default:
								$gbk_content .= "     promoter        " . $fStart . ".." . $fEnd . ")\n";
							break;
						}

						$gbk_content .= "                     /note=" . $fType . "\n";
						$gbk_content .= "                     /gene=" . $fVal . "\n";
						$gbk_content .= "                     /SECDrawas=Region\n";
					}
					else if (strcasecmp($fType, "polya tail") == 0)
					{					
						switch ($fDir)
						{
							case 'reverse':
								$gbk_content .= "     polyA_signal    complement(" . $fStart . ".." . $fEnd . ")\n";
							break;

							default:
								$gbk_content .= "     polyA_signal    " . $fStart . ".." . $fEnd . ")\n";
							break;
						}

						$gbk_content .= "                     /note=" . $fType . "\n";
						$gbk_content .= "                     /gene=" . $fVal . "\n";
						$gbk_content .= "                     /SECDrawas=Region\n";
					}
					else if (strcasecmp($fType, "origin of replication") == 0)
					{					
						switch ($fDir)
						{
							case 'reverse':
								$gbk_content .= "     rep_origin      complement(" . $fStart . ".." . $fEnd . ")\n";
							break;

							default:
								$gbk_content .= "     rep_origin      " . $fStart . ".." . $fEnd . ")\n";
							break;
						}

						$gbk_content .= "                     /note=" . $fType . "\n";
						$gbk_content .= "                     /gene=" . $fVal . "\n";
						$gbk_content .= "                     /SECDrawas=Region\n";
					}
					else if (strcasecmp($fType, "intron") == 0)
					{					
						switch ($fDir)
						{
							case 'reverse':
								$gbk_content .= "     intron          complement(" . $fStart . ".." . $fEnd . ")\n";
							break;

							default:
								$gbk_content .= "     intron          " . $fStart . ".." . $fEnd . ")\n";
							break;
						}

						$gbk_content .= "                     /note=" . $fType . "\n";
						$gbk_content .= "                     /gene=" . $fVal . "\n";
						$gbk_content .= "                     /SECDrawas=Region\n";
					}
					else
					{
						switch ($fDir)
						{
							case 'reverse':
								$gbk_content .= "     misc_feature    complement(" . $fStart . ".." . $fEnd . ")\n";
							break;

							default:
								$gbk_content .= "     misc_feature    " . $fStart . ".." . $fEnd . "\n";
							break;
						}

						$gbk_content .= "                     /note=" . $fType . "\n";
						$gbk_content .= "                     /gene=" . $fVal . "\n";
						$gbk_content .= "                     /SECDrawas=Region\n";
					}
				}
 			}
 			
			$gbk_content .= "\nORIGIN\n";
 	
 			$chunks = explode(" ", chunk_split(strtoupper($vSeq), 10, " "));
 	 	
 			for ($i = 0; $i < sizeof($chunks); $i++)
 			{				
				// leading spaces
				if ($i%6 == 0)
 				{
 					$gbk_content .= "\n";

					$num_spaces = 10 - strlen($i*10+1);

					for ($k = 0; $k < $num_spaces-1; $k++)
					{
						$gbk_content .= " ";
					}

					$gbk_content .= $i*10+1 . " ";
 				}

 				$gbk_content .=  $chunks[$i] . " ";
 			}
 	
 			$gbk_content .=  $chunks[$i] . "\n//\n";
		}

		if ($rfunc_obj->hasAttribute($reagentType, "protein translation", $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]))
		{
			$isDNA = true;
			$isRNA = false;
			$isProtein = false;
			$units = "nt";
		}
		else if ($rfunc_obj->hasAttribute($reagentType, "protein sequence", $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]))
		{
			$isDNA = false;
			$isRNA = false;
			$isProtein = true;
			$units = "aa";
		}
		else
		{
			$isDNA = false;
			$isRNA = true;
			$isProtein = false;
			$units = "nt";
		}

		?><th colspan="4" class="detailedView_heading" style="font-size:15pt; text-align:center"><?php
			echo $limsID;
		?></th><?php

		?><tr><td colspan="6"><HR></td></tr>

		<input type="hidden" ID="lims_id" name="limsID" value="<?php echo $limsID; ?>">

		<tr>
			<td colspan="6">
				<table width="100%" class="detailedView_buttons" border="0">
					<tr>
					<?php
						if ($reagentType == $_SESSION["ReagentType_Name_ID"]["Vector"])
						{
							?>
							<td style="text-align:center; padding-left:10px;">
								<span ID="exportGenbank"  class="linkExportSequence" style="font-size:10pt; padding-top:0; margin-left:5px;" onClick="document.genbank_export.submit();">Export GenBank</span><IMG SRC="pictures/new01.gif" ALT="new" WIDTH="35" HEIGHT="20">&nbsp;&nbsp;
							</td>
							<?php
						}
					?>		
						<td><a href="Location.php?View=1&#38;rid=<?php echo $reagentIDToView; ?>">Location</a></td><?php

						if ($reagentType == $_SESSION["ReagentType_Name_ID"]["Vector"])
						{
							$url = $hostname . "Reagent/vector_maps/" . $limsID . "_map.pdf";
							$oligoMap = $hostname . "Reagent/vector_maps/" . $limsID . "_Oligo_map.pdf";
			
							?>
							<td>
								<span ID="viewVectorMap" class="linkExportSequence" style="font-size:10pt;" onClick="return popup('<?php echo $url; ?>', '<?php echo $limsID; ?>', 1200, 1200, 'yes');">View Map</span>
							</td>

							<td>
								<input type="hidden" id="hidden_vector_id" name="reagent_id_hidden" value="<?php echo $reagentIDToView; ?>">
		
								<span ID="viewORFs" class="linkExportSequence" style="font-size:10pt;" onClick="document.vector_frames_form.submit()">View ORFs</span>
							</td>

							<td>
								<span ID="viewOligoMap" class="linkExportSequence" style="font-size:10pt;" onClick="return popup('<?php echo $oligoMap; ?>', '<?php echo $limsID; ?>', 1200, 1200, 'yes');">Oligo Map</span>
							</td>
							<?php
						}
	
						if  ($reagentType == $_SESSION["ReagentType_Name_ID"]["Insert"])
						{
							echo "<td><a href=\"" . $_SERVER["PHP_SELF"] . "?View=7&rid=" . $reagentIDToView . "\">Get Primers</a></td>";

							if (($currUserName == 'Administrator') || ($_SESSION["userinfo"]->getLabID() == 1))
							{
								$geneSymbol = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["official gene symbol"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]));

							}
						}
		
						?><td><?php
						
							// Disallow deletion of reagents that have preps or children, or reagents in projects that the user does not have Write access to (Readers, of course, may not delete anything)
							$currUserID = $_SESSION["userinfo"]->getUserID();
							$currUserCategory = $_SESSION["userinfo"]->getCategory();

							if ($currUserCategory == 1)
								$userProjects = findAllProjects();
							else
								$userProjects = getUserProjectsByRole($currUserID, 'Writer');

							$rIDpID = getReagentProjectID($reagentIDToView);
							$rChildren = $bfunc_obj->getReagentChildren($reagentIDToView);
							$delete_restricted = true;
						
							if (sizeof($rChildren) == 0)
							{
								if ($rIDpID > 0)
								{
									// Nov. 20/09: NO!  Even Admin cannot delete reagents that have preps!!
									if ($currUserCategory == 4)
									{
										$delete_restricted = true;
									}
									else
									{
										$loc_obj = new Location_Output_Class();
										$child_expID = $loc_obj->getExpID($reagentIDToView);

										// Feb. 18/10: This is not sufficient.  When preps (wells) for a particular reagent are deleted, rows are deleted from Isolates_tbl, but Experiment_tbl entry is untouched.  Therefore, in order to check if preps exist, need to count the number of ISOLATES:
										$num_isolates = 0;
										
										if ($child_expID > 0)
										{
											$num_isolates_rs = mysql_query("SELECT COUNT(isolate_pk) as num_iso FROM Isolate_tbl WHERE expID='" . $child_expID . "' AND status='ACTIVE'", $conn) or die("Cannot fetch isolates: " . mysql_error());
										
											if ($num_isolates_ar = mysql_fetch_array($num_isolates_rs, MYSQL_ASSOC))
											{
												$num_isolates = $num_isolates_ar["num_iso"];
											}
										}

										if (($child_expID < 1) || $num_isolates == 0)
										{
											// No location, BUT - Dec. 16/09: STILL check if user has project Write access!!!
											if (!in_array($rIDpID, $userProjects))
											{
												if ($currUserCategory == 1)
												{
													$delete_restricted = false;
												}
												else
												{
													$delete_restricted = true;
												}
											}
											else
											{
												// no location and project writer, can delete
												$delete_restricted = false;
											}
										}
										else
										{
											// NO ONE, NOT EVEN ADMIN, can delete if has location
											$delete_restricted = true;
	
											echo "<INPUT TYPE=\"hidden\" ID=\"write_access_" . $reagentIDToView . "\" VALUE=\"" . $limsID . "\">";
										}
									}
								}
								else
								{
									if ($currUserCategory == 1)
									{
										$delete_restricted = false;
									}
									
								}
							}
							else
							{
								// No one may delete if has children
								$delete_restricted = true;

								echo "<INPUT TYPE=\"hidden\" ID=\"has_children_" . $reagentIDToView . "\" VALUE=\"" . $limsID . "\">";
							}

							if (intval($delete_restricted) != 1)
							{
								?><INPUT type="button" VALUE="Delete" onClick="deleteReagentFromDetailedView('<?php echo $reagentIDToView; ?>');"><?php
							}
							else
							{
								?><INPUT type="button" VALUE="Delete" DISABLED><?php
							}
						?></td>
					</tr>
				</table>
			</td>
		</tr>

		<tr><td colspan="<?php echo $colspan; ?>"><HR></td></tr><?php

		foreach ($rPropCategories as $key => $category)
		{
			$categoryID = $_SESSION["ReagentPropCategory_Name_ID"][$category];
			$categoryAlias = $_SESSION["ReagentPropCategory_ID_Alias"][$categoryID];

			?>
			<FORM METHOD="POST" ID="editReagentForm_<?php echo $categoryAlias; ?>" NAME="reagentDetailForm_<?php echo $category; ?>" ACTION="<?php echo $cgi_path . "update.py"; ?>">
				<input type="hidden" name="reagent_id_hidden" value="<?php echo $reagentIDToView; ?>">
				<input type="hidden" name="reagent_typeid_hidden" value="<?php echo $reagentType; ?>">
				<input type="hidden" name="reagent_groupnum_hidden" value="<?php echo $reagentGroup; ?>">
				<input type="hidden" name="cloning_method_hidden" value="<?php echo $cloningMethod; ?>">
				<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_username" VALUE="<?php echo $currUserName; ?>">
				<?php
					if ($reagentType == $_SESSION["ReagentType_Name_ID"]["Insert"])
						$is_linear = True;
					else
						$is_linear = False;
				?>
				<input type="hidden" ID="is_linear" value="<?php echo $is_linear; ?>">

			<tr><td colspan="<?php echo $colspan; ?>"></td></tr><?php
			$r_props = $rfunc_obj->findReagentPropertiesByCategory($reagentIDToView, $_SESSION["ReagentPropCategory_Name_ID"][$category]);

			// April 21, 2010: SORT properties in order assigned to this reagent type
			$tmp_order_list = Array();

			foreach ($r_props as $propCatID => $props)
			{
				$pOrd = $rfunc_obj->getReagentTypePropertyOrdering($reagentType, $propCatID);
				$tmp_order_list[$propCatID] = $pOrd;
			}

			// Now: Do NOT put an 'if count($r_props) > 0' block here, because what if the reagent has no External IDs at the moment but user wants to fill them in later??  Of course section headings need to be shown, along with 'Edit' buttons, and when user hits 'Edit' output the full property set for this category to modify as desired
			if ((strcasecmp($category, "DNA Sequence") != 0) && (strcasecmp($category, "Protein Sequence") != 0) && (strcasecmp($category, "RNA Sequence") != 0))
			{
				?><tr ID="<?php echo $categoryAlias; ?>_tbl_view">
					<td style="padding-top:15px;">
						<TABLE style="background-color:#FFF8DC; width:100%; border:1px outset; padding:6px;">
							<tr>
								<td class="detailedView_heading" colspan="2" style="text-align:left; padding-left:5px; font-weight:bold; color:blue;">
									<?php echo $category; ?>
								</td>
					
								<td class="detailedView_heading" style="text-align:right; padding-right:6px; padding-top:0; color:#000000; font-size:10pt; font-family:Helvetica; white-space:nowrap;">
									<?php
										if ($modify_restricted)
											$modify_disabled = "DISABLED";
				
											// "Modify" button
											echo "<INPUT TYPE=\"BUTTON\" ID=\"update_reagent_" . $categoryAlias . "\" style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" " . $modify_disabled . " VALUE=\"Edit " . $category . "\" onClick=\"toggleReagentEditModify('edit', '" . $categoryAlias . "', '" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";
									?>
								</td>
							</tr><?php

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

						for ($i = 1; $i <= max(array_keys($psorted)); $i++)
						{
							$pList = $psorted[$i];

							foreach ($pList as $ind => $propCatID)
							{
								$props = $r_props[$propCatID];

								if (count($props) == 1)
								{
									foreach ($props as $key => $prop)
									{
										$propName = "";
										$propDescr = "";
										$propval = "";

										try
										{
											$propName = $prop->getPropertyName();
											$propDescr = $prop->getPropertyDescription();
											$propval = $prop->getPropertyValue();

											echo "<TR ID=\"" . $categoryAlias . "_view\">";

												echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold; white-space:nowrap;\">" . $propDescr . "</TD>";

												echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";

												// Jan. 20, 2010: NO!!!!!!!!!!!!!!  What if have 'comments' under additional categories??
// 												if ((strcasecmp($propName, "description") == 0) || (strcasecmp($propName, "comments") == 0) || (strcasecmp($propName, "verification comments") == 0) || (strcasecmp($propName, "accession number") == 0))

												if (($propCatID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["description"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || ($propCatID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["comments"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || ($propCatID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["verification comments"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"])) || ($propCatID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["accession number"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"])))
												{
													// June 10, 2010: hyperlink??
													$attr_ID = $rfunc_obj->getRTypeAttributeID($reagentType, $propName, $_SESSION["ReagentPropCategory_Name_ID"][$category]);

													if ($rfunc_obj->isHyperlink($attr_ID))
													{
														echo "<a target=\"new\" href=\"" . $this->get_Special_Column_Type($propName, $category, $reagentIDToView, "None") . "\">" . $this->get_Special_Column_Type($propName, $category, $reagentIDToView, "None") . "</a>";
													}
													else
														echo $this->get_Special_Column_Type($propName, $category, $reagentIDToView, "None");
												}
												else if (strcasecmp($propName, "entrez gene id") == 0)
												{
													echo "<a href=\"http://www.ncbi.nlm.nih.gov/sites/entrez?Db=gene&Cmd=ShowDetailView&TermToSearch=" . $propval . "\" target=\"_blank\">" . $propval . "</a>";
												}
												else if (strcasecmp($propName, "ensembl gene id") == 0)
												{
													$species = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["species"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]));

													switch (strtolower($species))
													{
														case "homo sapiens":
															echo "<a href=\"http://www.ensembl.org/Homo_sapiens/geneview?gene=" . $propval . "\" target=\"_blank\">" . $propval . "</a>";
														break;
														
														case "mus musculus":
															echo "<a href=\"http://www.ensembl.org/Mus_musculus/geneview?gene=" . $propval . "\" target=\"_blank\">" . $propval . "</a>";
														break;
														
														case "danio rerio":
															echo "<a href=\"http://www.ensembl.org/Danio_rerio/geneview?gene=" . $propval . "\" target=\"_blank\">" . $propval . "</a>";
														break;
														
														case "rattus norvegicus":
															echo "<a href=\"http://www.ensembl.org/Rattus_norvegicus/geneview?gene=" . $propval . "\" target=\"_blank\">" . $propval . "</a>";
														break;
														
														default:
															echo $propval;
														break;
													}
												}
												else if ((strcasecmp($propName, "packet id") == 0) || (strcasecmp($propName, "owner") == 0))
												{
													echo $this->get_Special_Column_Type("packet", "General Properties", $reagentIDToView, "preview");
												}
												else if (strcasecmp($propName, "oligo type") == 0)
												{
													if (strcasecmp($propval, 's') == 0)
														$propval = 'Sense';
													else if (strcasecmp($propval, 'a') == 0)
														$propval = 'Antisense';
													
													echo $propval;
												}
												else
												{
													// June 15, 2010: Print hyperlinks
													$attr_ID = $rfunc_obj->getRTypeAttributeID($reagentType, $propName, $_SESSION["ReagentPropCategory_Name_ID"][$category]);

													if ($rfunc_obj->isHyperlink($attr_ID))
													{
														echo "<a target=\"new\" href=\"" . $propval . "\">" . $propval . "</a>";
													}
													else
														echo $propval;
												}

												echo "</TD>";

											echo "</TR>";
										}
										catch (Exception $e)
										{
											// this is a feature
											$propName = $prop->getFeatureType();
											$propDescr = $prop->getFeatureDescription();
											$featureValue = $prop->getFeatureValue();
											$featureStart = $prop->getFeatureStart();
											$featureEnd = $prop->getFeatureEnd();
											$featureDir = $prop->getFeatureDirection();

											if ($featureEnd != 0)
											{
												$positionString = "<span style=\"color:#006400\">(" . $units . " " . $featureStart . " - " . $featureEnd . ")</span>";
												$cdna_length = $featureEnd - $featureStart + 1;
												$cdna_string = "<span style=\"color:#006400\">" . $cdna_length . "</SPAN> nucleotides " . $positionString . ", " . $featureDir . " orientation";
											}
											else
											{
												$positionString = "";
												$cdna_length = 0;
												$cdna_string = "";
											}

											echo "<TR ID=\"" . $categoryAlias . "_view\">";

											echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold; white-space:nowrap;\">" . $propDescr . "</TD>";
	
											echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";

											if (strcasecmp($propName, "cdna insert") == 0)
											{
												echo $cdna_string;

												// show cDNA insert
												if ($cloningMethod != 1)
												{
													// Get the Insert that belongs to IPV
													$tmp_rid = $bfunc_obj->get_Background_rid($reagentIDToView, "Parent Insert Vector");
										
													if ($tmp_rid > 0)
													{
														$tmp_insert = $bfunc_obj->get_Insert($tmp_rid);
										
														// August 17/07: Restrict viewing by project - if parent belongs to a project you don't have access to, don't allow linking
														$tmpProject = getReagentProjectID($tmp_insert);
														$userProjects = getAllowedUserProjectIDs($_SESSION["userinfo"]->getUserID());
										
														if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($tmpProject, $userProjects))
														{
															echo "<a href=\"Reagent.php?View=6&rid=" . $tmp_insert . "\" style=\"margin-left:8px; font-weight:normal;\">";
															echo $gfunc_obj->getConvertedID_rid($tmp_insert);
															echo "</a>&nbsp;";
														}
														else
														{
															echo "<span class=\"linkDisabled\" style=\"margin-left:8px; font-weight:normal;\">";
															echo $gfunc_obj->getConvertedID_rid($tmp_insert);
															echo "</span>";
														}
													}
												}

												echo "</TD></TR><TR><TD></TD></TR>";
											}
											else if ((strcasecmp($propName, "5' cloning site") == 0) || (strcasecmp($propName, "3' cloning site") == 0))
											{
												echo $featureValue . "&nbsp;" . $positionString;

												if (strcasecmp($propName, "3' cloning site") == 0)
													echo "</TD></TR><TR><TD></TD></TR>";
											}
											else if ((strcasecmp($propName, "5' linker") == 0) || (strcasecmp($propName, "3' linker") == 0))
											{
												if (strlen($featureValue) > 0)		// July 08/09
												{
													echo $featureValue . "&nbsp;" . $positionString . ", " . $featureDir . " orientation";
	
													if (strcasecmp($propName, "3' linker") == 0)
														echo "</TD></TR><TR><TD></TD></TR>";
												}
											}
											else
											{
												$featureDescriptor = $prop->getFeatureDescriptor();

												echo "<UL style=\"padding-left:1.5em; padding-top:0.5em;\">";

												if ($featureDescriptor && strlen($featureDescriptor) > 0)
												{
													switch($propName)
													{
														case 'tag':
															echo "<LI>" . $featureValue . "&nbsp;" . $positionString . ", " . $featureDir . " orientation, position " . $featureDescriptor .  "</LI>";
														break;
													
														case 'promoter':
															echo "<LI>" . $featureValue . "&nbsp;" . $positionString . ", " . $featureDir . " orientation, expression system " . $featureDescriptor .  "</LI>";
														break;
													}
												}
												else
													echo "<LI>" . $featureValue . "&nbsp;" . $positionString . ", " . $featureDir . " orientation</LI>";

												echo "</UL>";

												echo "</TD></TR>";
											}
										}
									}
								}
								else
								{
									$pID = $rfunc_obj->findPropertyInCategoryID($propCatID);

									// name and description are the same
									$propName = $_SESSION["ReagentProp_ID_Name"][$pID];
									$propDescr = $_SESSION["ReagentProp_ID_Desc"][$pID];

									echo "<TR ID=\"" . $categoryAlias . "_view\">";
										echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold; white-space:nowrap;\">" . $propDescr . "</TD>";

										echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";

										echo "<UL style=\"padding-left:1.5em; padding-top:0.5em;\">";

										foreach ($props as $key => $prop)
										{
											// May 26, 2011: RESET VARIABLES!!!!!!!!! OTHERWISE IT WILL PRINT POSITIONS FOR NON-FEATURES!!!!!!!
											$fValue = null;
											$fStart = null;
											$fEnd = null;
											$fDir = null;
											$fDescr = null;

											// June 30/09: Cannot automatically assume features because non-features like Alternate ID ("checkbox" props) have >1 value
											if (!$rfunc_obj->isSequenceFeature($propCatID))
											{
												$fValue = $prop->getPropertyValue();
											}
											else
											{
												$fValue = $prop->getFeatureValue();
												$fStart = $prop->getFeatureStart();
												$fEnd = $prop->getFeatureEnd();
												$fDir = $prop->getFeatureDirection();
												$fDescr = $prop->getFeatureDescriptor();
											}

											if ($fEnd && ($fEnd != 0))
												$posString = "<span style=\"color:#006400\">(" . $units . " " . $fStart . " - " . $fEnd . ")</span>";
											else
												$posString = "";

											if ($fDir && strlen($fDir) > 0)
												$dirString =  ", " . $fDir . " orientation";
											else
												$dirString = "";

											if ($fDescr && strlen($fDescr) > 0)
												echo "<LI>" . $fValue . "&nbsp;" . $posString . ", " . $fDescr . $dirString . "</LI>";

											else
												echo "<LI>" . $fValue . "&nbsp;" . $posString . $dirString . "</LI>";
										}

										echo "</UL>";

										echo "</TD>";
									echo "</TR>";
								}
							}
						}

						?></table>
					</td>
				</tr>

				<tr ID="<?php echo $categoryAlias; ?>_tbl_modify" style="display:none;">
					<td style="padding-top:15px;">
							<TABLE style="background-color:#FFF8DC; width:725px; border:1px outset; padding:6px;">
								<tr>
									<td class="detailedView_heading" colspan="2" style="text-align:left; padding-left:5px; font-weight:bold; color:blue;">
										<?php echo $category; ?>
									</td>
						
									<td class="detailedView_heading" style="text-align:right; padding-right:6px; padding-top:0; color:#000000; font-size:10pt; font-family:Helvetica; white-space:nowrap;">
										<?php
											if ($modify_restricted)
												$modify_disabled = "DISABLED";
					
												// "Save" and "Cancel" buttons
												echo "<INPUT TYPE=\"BUTTON\" ID=\"update_reagent_" . $categoryAlias . "\" style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" " . $modify_disabled . " VALUE=\"Save\" onClick=\"document.pressed = this.value; if (checkMandatoryProps('" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "', '" . $categoryAlias . "')) toggleReagentEditModify('save', '" . $categoryAlias . "', '" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";
	
												echo "<INPUT TYPE=\"BUTTON\" ID=\"update_reagent_" . $categoryAlias . "\" style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" " . $modify_disabled . "  VALUE=\"Cancel\" onClick=\"document.pressed = this.value; cancelModify('" . $reagentIDToView . "');\"></INPUT>";
										?>
									</td>
								</tr><?php
	
								// Here need more than saved properties though, need ALL properties in this section for this reagent type
								$all_attributes = $rfunc_obj->getReagentTypeAttributesByCategory($reagentType, $categoryID);

								if ($categoryID == $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"])
								{
									?><TR><TD colspan="7">
										<table>
											<TR>
												<TD colspan="7">
												<?php
													$rc_obj = new Reagent_Creator_Class();
													$seqFunc_obj = new Sequence_Function_Class();
		
													$dnaSequence = $seqFunc_obj->findDNASequence($reagentIDToView);
													
													if ($dnaSequence)
														$sequence = $dnaSequence->getSequence();
													else
														$sequence = "";
		
													$seqID = $seqFunc_obj->findSequenceID($sequence);
		
													// Do NOT pass 'insertFeaturesForm' name to printFeatures function!!!!!!!!!!!!
													$tblID = "modifyReagentPropsTbl_" . $categoryAlias;

													$rc_obj->printFeatures($_SESSION["ReagentType_ID_Name"][$reagentType], "", $reagentIDToView, $seqID, "reagentDetailForm_" . $category, false, true, $tblID);
												?>
												</td>
											</tr>
										</table>	<!-- close insert_features_view table -->
									</td></tr><?php
								}
								else if ($categoryID == $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence Features"])
								{
									?><TR><TD colspan="7">
										<table>
											<TR>
												<TD colspan="7">
												<?php
													$rc_obj = new Reagent_Creator_Class();
													$seqFunc_obj = new Sequence_Function_Class();
		
													$proteinSequence = $seqFunc_obj->findProteinSequence($reagentIDToView);
													
													if ($proteinSequence)
														$sequence = $proteinSequence->getSequence();
													else
														$sequence = "";
		
													$seqID = $seqFunc_obj->findSequenceID($sequence);
		
													// Do NOT pass 'insertFeaturesForm' name to printFeatures function!!!!!!!!!!!!
													$tblID = "modifyReagentPropsTbl_" . $categoryAlias;
													$rc_obj->printFeatures($_SESSION["ReagentType_ID_Name"][$reagentType], "", $reagentIDToView, $seqID, "reagentDetailForm_" . $category, false, true, $tblID);
												?>
												</td>
											</tr>
										</table>	<!-- close insert_features_view table -->
									</td></tr><?php
								}
								else if ($categoryID == $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence Features"])
								{
									?><TR><TD colspan="7">
										<table>
											<TR>
												<TD colspan="7">
												<?php
													$rc_obj = new Reagent_Creator_Class();
													$seqFunc_obj = new Sequence_Function_Class();
		
													$rnaSequence = $seqFunc_obj->findRNASequence($reagentIDToView);
													
													if ($rnaSequence)
														$sequence = $rnaSequence->getSequence();
													else
														$sequence = "";
		
													$seqID = $seqFunc_obj->findSequenceID($sequence);
		
													// Do NOT pass 'insertFeaturesForm' name to printFeatures function!!!!!!!!!!!!
													$tblID = "modifyReagentPropsTbl_" . $categoryAlias;

													$rc_obj->printFeatures($_SESSION["ReagentType_ID_Name"][$reagentType], "", $reagentIDToView, $seqID, "reagentDetailForm_" . $category, false, true, $tblID);
												?>
												</td>
											</tr>
										</table>	<!-- close insert_features_view table -->
									</td></tr><?php
								}
								else
								{
									// May 5, 2010: keys are now attribute IDs
									foreach ($all_attributes as $attrID => $attribute)
									{
										$pName = $attribute->getPropertyName();
										$pCategory = $attribute->getPropertyCategory();

										$p_id = $_SESSION["ReagentProp_Name_ID"][$pName];
										$c_id = $_SESSION["ReagentPropCategory_Name_ID"][$pCategory];

										$pcID = $rfunc_obj->getPropertyIDInCategory($p_id, $c_id);

										// May 11, 2011: verify mandatory props on saving
										if ($rfunc_obj->isMandatory($_SESSION["ReagentType_ID_Name"][$reagentType], $pName))
										{
											//echo "MANDATORY: " . $pName;

											echo "<INPUT TYPE=\"hidden\" NAME=\"" . $_SESSION["ReagentType_ID_Name"][$reagentType] . "_mandatoryProps[]\" VALUE=\"" . $attrID . "\">";
										}

										if (in_array($pcID, array_keys($r_props)))
										{
											$props = $r_props[$pcID];

											if (count($props) == 1)
											{
												foreach ($props as $key => $prop)
												{
													$propName = "";
													$propDescr = "";
													$propval = "";
			
													try
													{
														$propName = $prop->getPropertyName();

														// May 11, 2011: verify mandatory props on saving
														if ($rfunc_obj->isMandatory($_SESSION["ReagentType_ID_Name"][$reagentType], $propName))
														{
															//echo "MANDATORY: " . $propName;

															echo "<INPUT TYPE=\"hidden\" NAME=\"" . $reagentType . "_mandatoryProps[]\" VALUE=\"" . $attrID . "\">";
														}

														$propDescr = $prop->getPropertyDescription();
										
														$propval = $prop->getPropertyValue();
														echo "<TR ID=\"" . $categoryAlias . "\">";

														if ($pcID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["alternate id"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]))
														{
															echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr;
																echo "<BR><BR><span style=\"font-size:8pt;\">For 'Other', please enter<BR> the database name and<BR> numeric identifier, separated<BR> by semicolon, in the textbox<BR> (e.g. 'IMAGE:123456')</span>";
															echo "</TD>";
														}
														else if ($pcID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["type of insert"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]))
														{
															echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr;

															echo "&nbsp;<span style=\"font-size:9pt; color:#FF0000; font-weight:bold\">*</span>&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/hm4.png\" HEIGHT=\"13\" width=\"16\" ALT=\"link_icon\" style=\"vertical-align:middle; cursor:auto\" onmouseover=\"return overlib('Select the correct type of insert value to ensure correct translation of the DNA sequence to protein.  Refer to the \'Translation Guidelines\' for more information.', CAPTION, 'Type of Insert', STICKY);\">";			

															echo "</TD>";
														}
														else if ($pcID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["open/closed"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]))
														{
															echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr;

															echo "&nbsp;<span style=\"font-size:9pt; color:#FF0000; font-weight:bold\">*</span>&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/hm4.png\" HEIGHT=\"13\" width=\"16\" ALT=\"link_icon\" style=\"vertical-align:middle; cursor:auto\" onmouseover=\"return overlib('Select the correct open/closed value to ensure correct translation of the DNA sequence to protein.  Refer to the \'Translation Guidelines\' for more information.', CAPTION, 'Open/Closed', STICKY);\">";			

															echo "</TD>";
														}
														else
														{
															echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr . "</TD>";
														}
			
															echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";		
																$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"][$propName] . $genPostfix, $propName, $propval, $reagentIDToView, true, $category);
															echo "</TD>";
														echo "</TR>";
													}
													catch (Exception $e)
													{
														// this is a feature
														$propName = $prop->getFeatureType();
														$propDescr = $prop->getFeatureDescription();
														$featureValue = $prop->getFeatureValue();
														$featureStart = $prop->getFeatureStart();
														$featureEnd = $prop->getFeatureEnd();
														$featureDir = $prop->getFeatureDirection();

														// May 11, 2011: verify mandatory props on saving
														if ($rfunc_obj->isMandatory($_SESSION["ReagentType_ID_Name"][$reagentType], $propName))
														{
															//echo "MANDATORY: " . $propName;

															echo "<INPUT TYPE=\"hidden\" NAME=\"" . $reagentType . "_mandatoryProps[]\" VALUE=\"" . $attrID . "\">";
														}
			
														if ($featureEnd != 0)
														{
															$positionString = "<span style=\"color:#006400\">(" . $units . " " . $featureStart . " - " . $featureEnd . ")</span>";
															$cdna_length = $featureEnd - $featureStart + 1;
															$cdna_string = "<span style=\"color:#006400\">" . $cdna_length . "</SPAN> nucleotides " . $positionString . ", " . $featureDir . " orientation";
														}
														else
														{
															$positionString = "";
															$cdna_length = 0;
															$cdna_string = "";
														}
			
														echo "<TR ID=\"" . $categoryAlias . "\">";
			
														echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr;

															if ($pcID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["alternate id"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]))
																echo "<BR><BR><span style=\"font-size:8pt;\">For 'Other', please enter<BR> the database name and<BR> numeric identifier, separated<BR> by semicolon, in the textbox<BR> (e.g. 'IMAGE:123456')</span>";

														echo "</TD>";
				
														echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
			
														// No cDNA, sites or linkers for RNA sequence
														$featureDescriptor = $prop->getFeatureDescriptor();
		
														echo "<UL style=\"padding-left:1.5em; padding-top:0.5em;\">";
		
														if ($featureDescriptor && strlen($featureDescriptor) > 0)
														{
															switch($propName)
															{
																case 'tag':
																	echo "<LI>" . $featureValue . "&nbsp;" . $positionString . ", " . $featureDir . " orientation, position " . $featureDescriptor .  "</LI>";
																break;
															
																case 'promoter':
																	echo "<LI>" . $featureValue . "&nbsp;" . $positionString . ", " . $featureDir . " orientation, expression system " . $featureDescriptor .  "</LI>";
																break;
															}
														}
														else
															echo "<LI>" . $featureValue . "&nbsp;" . $positionString . ", " . $featureDir . " orientation</LI>";
		
														echo "</UL>";
		
														echo "</TD></TR>";
													}
												}
											}
											else
											{
												$pVals = Array();

												foreach ($props as $pkey => $a_prop)
												{
													$pVals[$pcID][] = $a_prop->getPropertyValue();
												}

												$pID = $rfunc_obj->findPropertyInCategoryID($pcID);
			
												$propName = $_SESSION["ReagentProp_ID_Name"][$pID];

												$propDescr = $_SESSION["ReagentProp_ID_Desc"][$pID];

												// May 11, 2011: verify mandatory props on saving
												if ($rfunc_obj->isMandatory($_SESSION["ReagentType_ID_Name"][$reagentType], $propName))
												{
													//echo "MANDATORY: " . $propName;

													echo "<INPUT TYPE=\"hidden\" NAME=\"" . $reagentType . "_mandatoryProps[]\" VALUE=\"" . $attrID . "\">";
												}

												if ($propName == "alternate id")
												{
													echo "<TR ID=\"" . $categoryAlias . "\">";
														echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr;

														if ($pcID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["alternate id"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]))
															echo "<BR><BR><span style=\"font-size:8pt;\">For 'Other', please enter<BR> the database name and<BR> numeric identifier, separated<BR> by semicolon, in the textbox<BR> (e.g. 'IMAGE:123456')</span>";

														echo "</TD>";

														echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
															$this->print_Set_Extended_Checkbox($propName, $genPrefix . $_SESSION["ReagentProp_Name_Alias"][$propName] . $genPostfix, $pVals[$pcID], $reagentIDToView, "", $category, $reagentType);
														echo "</TD>";
													echo "</TR>";
												}
												// May 7, 2010: Allowing other properties to have multiple values
												else
												{
													echo "<TR ID=\"" . $categoryAlias . "\">";
														echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr;

														if ($pcID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["alternate id"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]))
															echo "<BR><BR><span style=\"font-size:8pt;\">For 'Other', please enter<BR> the database name and<BR> numeric identifier, separated<BR> by semicolon, in the textbox<BR> (e.g. 'IMAGE:123456')</span>";
														echo "</TD>";

														echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
															$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"][$propName] . $genPostfix, $propName, $pVals[$pcID], $reagentIDToView, true, $category);
														echo "</TD>";
													echo "</TR>";
												}
											}
										}
										else
										{
											$tmp_propID = $rfunc_obj->findPropertyInCategoryID($pcID);
											$tmp_propDescr = $_SESSION["ReagentProp_ID_Desc"][$tmp_propID];
											$tmp_propName = $_SESSION["ReagentProp_ID_Name"][$tmp_propID];
	
											// May 11, 2011: verify mandatory props on saving
											if ($rfunc_obj->isMandatory($_SESSION["ReagentType_ID_Name"][$reagentType], $tmp_propName))
											{
												//echo "MANDATORY: " . $tmp_propName;

												echo "<INPUT TYPE=\"hidden\" NAME=\"" . $reagentType . "_mandatoryProps[]\" VALUE=\"" . $attrID . "\">";
											}

											if (strcasecmp($tmp_propName, "protein translation") != 0)
											{
												$tmp_propAlias = $_SESSION["ReagentProp_Name_Alias"][$tmp_propName];
		
												echo "<TR ID=\"" . $categoryAlias . "\">";
													echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $tmp_propDescr;

														if ($pcID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["alternate id"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]))
															echo "<BR><BR><span style=\"font-size:8pt;\">For 'Other', please enter<BR> the database name and<BR> numeric identifier, separated<BR> by semicolon, in the textbox<BR> (e.g. 'IMAGE:123456')</span>";

														else if ($pcID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["type of insert"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]))
														{															
															echo "&nbsp;<span style=\"font-size:9pt; color:#FF0000; font-weight:bold\">*</span>&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/hm4.png\" HEIGHT=\"13\" width=\"16\" ALT=\"link_icon\" style=\"vertical-align:middle; cursor:auto\" onmouseover=\"return overlib('Select the correct type of insert value to ensure correct translation of the DNA sequence to protein.  Refer to the \'Translation Guidelines\' for more information.', CAPTION, 'Type of Insert', STICKY);\">";			

															echo "</TD>";
														}
														else if ($pcID == $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["open/closed"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]))
														{														
															echo "&nbsp;<span style=\"font-size:9pt; color:#FF0000; font-weight:bold\">*</span>&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/hm4.png\" HEIGHT=\"13\" width=\"16\" ALT=\"link_icon\" style=\"vertical-align:middle; cursor:auto\" onmouseover=\"return overlib('Select the correct open/closed value to ensure correct translation of the DNA sequence to protein.  Refer to the \'Translation Guidelines\' for more information.', CAPTION, 'Open/Closed', STICKY);\">";			

															echo "</TD>";
														}
	
													echo "</TD>";
		
													echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
														$this->print_property_final($genPrefix . $tmp_propAlias . $genPostfix , $tmp_propName, "", $reagentIDToView, true, $category);
													echo "</TD>";
												echo "</TR>";
											}
										}
									}
								}
							?></table>
					</td>
				</tr><?php		// end 'Modify' section
			}
			else if ( (strcasecmp($category, "DNA Sequence") == 0) && $rfunc_obj->hasAttribute($reagentType, "sequence", $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]))
			{
				// June 18, 2010
				$propsList = $rfunc_obj->findReagentPropertiesByCategory($reagentIDToView, $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);

				// show tabs IFF has protein translation with DNA!!!!!!!!!!!!
				echo "<TR><TD colspan=\"3\" style=\"padding-right:5px;\">&nbsp;</TD></TR>";
			
				?><tr>
					<TD colspan="2">
							<TABLE class="detailedView_tbl" style="width:725px; border:1px outset; padding:6px;" style="border: 1px solid black;">
								<tr ID="other_reagent_sequence_tbl_view">
									<td class="detailedView_heading" style="text-align:left; padding-left:5px; font-weight:bold; color:blue;">
										Sequence Information
									</TD>
			
									<td class="detailedView_heading" style="text-align:right; padding-right:6px; padding-top:0; color:#000000; font-size:10pt; font-family:Helvetica; white-space:nowrap;"><?php
			
										if ($modify_restricted)
											$modify_disabled = "DISABLED";
			
										// "Modify" button
										echo "<INPUT TYPE=\"BUTTON\" ID=\"edit_reagent_" . $categoryAlias . "\" style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" " . $modify_disabled . "  VALUE=\"Edit Sequence\" onClick=\"toggleReagentEditModify('edit', '" . $categoryAlias . "', '" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";

										// "Save" and "Cancel" buttons
										echo "<DIV ID=\"sequenceButtons\" style=\"display:none\">";
											echo "<INPUT TYPE=\"BUTTON\" ID=\"save_reagent_" . $categoryAlias . "\" style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" " . $modify_disabled . " VALUE=\"Save\" onClick=\"document.pressed = this.value; if (checkMandatoryProps(" . $reagentType . ")) selectAllPropertyValues(); toggleReagentEditModify('save', '" . $categoryAlias . "', '" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";

											echo "<INPUT TYPE=\"BUTTON\" ID=\"cancel_save_" . $categoryAlias . "\" style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" " . $modify_disabled . "  VALUE=\"Cancel\" onClick=\"document.pressed = this.value; toggleReagentEditModify('cancel', '" . $categoryAlias . "', '" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";
										echo "</DIV>";
										?>
									</td>
								</tr><?php
			
								// June 30/09: The most accurate check is to see whether the reagent has attribute 'protein translation' as a function of 'DNA sequence'.  A protein translation cannot exist without DNA; a protein or RNA sequence, on the other hand, exists as the default sequence for some reagent types instead of DNA
								if ($rfunc_obj->hasAttribute($reagentType, "protein translation", $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]))
								{
									?><tr>
										<td style="padding-left:0px;" colspan="2">
											<TABLE id="tabsTbl" cellspacing="0">
												<TR>
													<TD style="padding-left:0px;" class="currentView">
														<span id="viewDnaTab" class="seqTabLinkActive" onClick="showDNASequence();">DNA Sequence</span>
													</TD>
							
													<TD class="switchTo">
														<span id="viewProteinTab" class="seqTabLinkInactive" onClick="showProteinSequence();">Translated Protein Sequence</span>
													</TD>
												</TR>
											</TABLE>
										</TD>
									</TR>
			
									<TR>
										<TD class="sequenceContent" colspan="2">
											<?php
												// DNA SEQUENCE INFO

												// Correction June 30/09, keep 'insert' variable name for consistency 
												$insertName = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));

												// get sequence by ID
												$seqFunc_obj = new Sequence_Function_Class();
												$dnaSequence = $seqFunc_obj->findDNASequence($reagentIDToView);
												
												if ($dnaSequence)
													$sequence = $dnaSequence->getSequence();
												else
													$sequence = "";

												// Replaced July 13/09
												$insertLength = strlen(str_replace(" ", "", trim($sequence)));

												// June 30/09: added 'nucleotides'
												$content = ">" . $limsID . ": " . $insertName . "; length " . $insertLength . '\r' . $sequence . '\r';
							
												// PROTEIN SEQUENCE INFO
												// Replaced June 23/09
												$seqFunc_obj = new Sequence_Function_Class();

												// this is a Protein Sequence OBJECT
												$proteinSequence = $seqFunc_obj->findProteinTranslation($reagentIDToView);

												if ($proteinSequence)
												{
													$frame = $proteinSequence->getFrame();
													$protStart = $proteinSequence->getStart();
													$protEnd = $proteinSequence->getEnd();
													$protSeq = $proteinSequence->getSequence();
													$protLength = strlen(trim($protSeq));
													$protMW = $proteinSequence->getMW();

													if (!$protMW || $protMW == 0)
													{
														$protMW = $rfunc_obj->computeProteinMW($protSeq);
													}
												}
												else
												{
													$protSeq = "";
													$protStart = 0;
													$protEnd = 0;
													$protLength = 0;
												}

												$proteinFastaContent = ">" . $limsID . ": " . $insertName . "; length " . $protLength . " (translation of the longest ORF at nt " . $protStart . "-" . $protEnd . ")" . '\r' . $protSeq . '\r';
											?>
											<!-- Actual TAB, container for sequence information -->
											<table ID="dnaSequenceTab" width="725px" style="display:inline;" border="0">
												<tr>
													<td style="padding-left:5px; padding-right:5px; padding-top:3px;">
														<TABLE ID="insert_sequence_table" border="0">
															<tr>
																<td colspan="2" style="padding-left:10px; padding-right:10px; white-space:nowrap;">
																	<span ID="exportDNASeqFasta" style="font-weight:normal; padding-top:0" class="linkExportSequence" onClick="exportDNASeqToFasta('<?php echo str_replace("'", "\'", $content); ?>')">Export to FASTA</span>
										
																	<span ID="viewRestrMap" class="linkExportSequence" style="padding-left:7px; font-weight:normal;" onClick="document.restriction_map_insert.submit()">Restriction Map</span>
																</td>
															</tr>
					
															<tr>
																<td colspan="2" class="detailedView_value">
																	<?php
																		echo "<DIV ID=\"sequence_properties_tbl_view\" class=\"tabSequence\" style=\"margin-top:4px;\">";
																			$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"]["sequence"] . $genPostfix , "sequence", $sequence, $reagentIDToView, false, $category, "", "", $reagentType);
																		echo "</DIV>";

																		// Feb 28/08 - Sequence in Modify mode - hidden
																		echo "<DIV ID=\"sequence_properties_tbl_modify\" style=\"display:none\" class=\"tabSequence\" style=\"margin-top:4px;\">";
																			$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"]["sequence"] . $genPostfix , "sequence", $sequence, $reagentIDToView, true, $category, "", "", $reagentType);
																		echo "</DIV>";
																	?>
																</td>
															</tr>
															<?php

															// August 13/09: This is ONLY required for Inserts!!!!!!!
															// REMOVED MAY 19, 2011: causes issues with JS verification of mandatory properties
															//if ($reagentType == $_SESSION["ReagentType_Name_ID"]["Insert"])
															//{
																?>
																<!-- <tr><td colspan="6" style="padding-top:4px"></td></tr>
																
																<tr ID="itype_modify" style="display:none">
																	<td class="detailedView_colName">Type of Insert
																	<?php
																	echo "&nbsp;<span style=\"font-size:9pt; color:#FF0000; font-weight:bold\">*</span>&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/hm4.png\" HEIGHT=\"13\" width=\"16\" ALT=\"link_icon\" style=\"vertical-align:middle; cursor:auto\" onmouseover=\"return overlib('Select the correct type of insert value to ensure correct translation of the DNA sequence to protein.  Refer to the \'Translation Guidelines\' for more information.', CAPTION, 'Type of Insert', STICKY);\">";
																	?>
																	</td>
													
																	<td colspan="5" class="detailedView_value"><?php
	
																		$iTypeID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["type of insert"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]);
																		$insertType = $rfunc_obj->getPropertyValue($reagentIDToView, $iTypeID);
														
																		$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"]["type of insert"] . $genPostfix , "type of insert", $insertType, $reagentIDToView, true, "Classifiers");

																		?><div id="it_warning" style="display:none; color:#FF0000"><P>Please select a Type of Insert from the dropdown list</div>
																	</td>
																</tr>
											
																<tr ID="modifyOpenClosed" style="display:none">
																	<td class="detailedView_colName">
																		Open/Closed
																		<?php
																		echo "&nbsp;<span style=\"font-size:9pt; color:#FF0000; font-weight:bold\">*</span>&nbsp;&nbsp;<IMG src=\"" . $hostname . "pictures/hm4.png\" HEIGHT=\"13\" width=\"16\" ALT=\"link_icon\" style=\"vertical-align:middle; cursor:auto\" onmouseover=\"return overlib('Select the correct open/closed value to ensure correct translation of the DNA sequence to protein.  Refer to the \'Translation Guidelines\' for more information.', CAPTION, 'Open/Closed', STICKY);\">";
																		?>
																	</td>
										
																	<td colspan="5" class="detailedView_value"><?php
	
																		$ocID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["open/closed"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]);
																		$openClosed = $rfunc_obj->getPropertyValue($reagentIDToView, $ocID);
														
																		$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"]["open/closed"] . $genPostfix , "open/closed", $openClosed, $reagentIDToView, true, "Classifiers");

																		?><div id="oc_warning" style="display:none; color:#FF0000"><P>Please select an Open/Closed value from the dropdown list</div>
																	</td>
																</tr> -->
																<?php 
															//}	// close "if Insert"
															
															?>
															<tr id="dnaLengthView">
																<td class="detailedView_colName" style="padding-left:6px; background-color:#C1FFC1; font-size:9pt; font-weight:bold;">
																	DNA Length:
																</td>
												
																<td class="detailedView_value">
																	<SPAN style="color:#006400"><?php
																		echo $insertLength;
																	?></SPAN>&nbsp;nucleotides
																</td>
															</tr>

															<tr id="dnaLengthModify" style="display:none">
																<td class="detailedView_colName" style="padding-left:6px; background-color:#C1FFC1; font-size:9pt; font-weight:bold;">
																	DNA Length:
																</td>
													
																<td class="detailedView_value">
																	<SPAN style="color:#006400"><?php
																		echo $insertLength;
																	?></SPAN>&nbsp;nucleotides
																</td>
															</tr>
														</table>	<!-- close insert_sequence_tbl -->
													</td>
												</tr>
											</table>	<!-- close DNA tab!!! -->
							
											<!-- Keep format as is, it looks good -->
											<table ID="proteinSequenceTab" style="display:none">
												<tr>
													<td style="padding-left:5px; padding-right:5px; padding-top:3px">
														<TABLE ID="protein_sequence_table" border="0">
															<tr>
																<td colspan="2" style="font-size:9pt; margin-top:5px;  padding-left:10px; font-weight:bold;">
																	<!-- Caption text edited Sept. 11/08 -->
																	Translated Protein Sequence of the longest Open Reading Frame (<span style="color:#0000CD">nt&nbsp;<?php echo $protStart . " - " . $protEnd; ?></span>) from cDNA:
															
																	<span class="linkExportSequence" style="font-weight:normal; margin-left:15px" onClick="exportProteinSeqToFasta('<?php echo $proteinFastaContent; ?>')">Export to FASTA</span>
																</td>
															</tr>
										
															<tr>
																<td colspan="2">
																<?php
																	echo "<DIV style=\"margin-top:2px; padding-left:10px;\" class=\"tabSequence\">";

																	echo "<table cellpadding=\"4\" border=\"0\">";
		
																	if (strlen($protSeq) > 0)
																	{
																		$tmp_protSeq = $rfunc_obj->spaces($protSeq);
																		$tmp_prot = split("\n", $tmp_protSeq);
															
																		foreach ($tmp_prot as $i=>$tok)
																		{
																			echo "<TR>";

																			$rowIndex = $i+1;
																			
																			if ($i == 0)	// first row
																			{
																				$rowStart = 1;
																			}
																			else
																			{
																				$rowStart = $i*100 + 1;
																			}
																			
																			
																			// Nov. 3/06 - Output nt count on last line
																			if ($protLength >= ($rowIndex*100))
																			{
																				$rowEnd = $rowIndex*100;
																			}
																			else
																			{
																				$rowEnd = $protLength;
																			}
																			
																			// July 15/08: Show start position only
																			echo "<TD nowrap style=\"font-size:10px; font-weight:bold; text-align:right; vertical-align:top\">" . $rowStart . "</TD>";
														
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
																	}

																	echo "</table>";

																	echo "</DIV>";
																?>
																</td>
															</tr>
										
															<tr><td colspan="2"></td></tr>
												
															<tr><td colspan="2" style="padding-top:4px; color:#0000CD; padding-left:3px; font-weight:bold; font-size:9pt;">Protein Sequence Features:</td></tr>
												
															<tr><td colspan="2"></td></tr>
												
															<tr>
																<td class="detailedView_colName" width="150px">Length</td>
																
																<td class="detailedView_value" colspan="5"><?php

																	if ($protLength > 0)
																		echo "<span style=\"color:green;\">" . $protLength . "</span>&nbsp;&nbsp;amino acids";
																?></td>
															</tr>
												
															<tr>
																<td class="detailedView_colName">Frame</td>
																
																<td class="detailedView_value">
																<?php echo $frame; ?>
																</td>
															</tr>

															<!-- Jan. 22, 2010: print MW  -->
															<tr>
																<td class="detailedView_colName">Molecular Weight</td>
																
																<td class="detailedView_value">
																<?php
																	if ($protMW && $protMW > 0) echo round($protMW, 2) . " kDa"; ?>
																</td>
															</tr>

												
															<tr><td colspan="2"></td></tr>
														</table>
													<td>
												</tr>
											</table>
										</td>
									</tr>
			
									<TR><TD>&nbsp;</TD></TR><?php
								}	// end if has protein sequence
								else
								{
									?><TR>
										<TD class="sequenceContent" colspan="2">
											<?php
												// DNA SEQUENCE INFO
												$insertName = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));

												// get sequence by ID
												$seqFunc_obj = new Sequence_Function_Class();
												$dnaSequence = $seqFunc_obj->findDNASequence($reagentIDToView);
												
												if ($dnaSequence)
												{
													$sequence = $dnaSequence->getSequence();
													// echo $sequence;

													// Jan. 25/10
													$mw = $dnaSequence->getMW();
													
													if (!$mw || ($mw == null) || ($mw < 0))
													{
														$mw = $rfunc_obj->get_MW($sequence);
													}

													$tm = $dnaSequence->getTM();

													if (!$tm || ($tm == null) || ($tm < 0))
													{
														$tm = $rfunc_obj->calcTemp($sequence);
													}

													$gc = $dnaSequence->getGC();

													if (!$gc || ($gc == null) || ($gc == 0))
													{
														$gc = $rfunc_obj->get_gc_content($sequence);
													}
												}

												// Replaced July 13/09
												$insertLength = strlen(str_replace(" ", "", $sequence));
												$content = ">" . $limsID . ": " . $insertName . "; length " . $insertLength . '\r' . $sequence . '\r';
											?>
											<TABLE ID="sequence_properties_tbl_view">
												<tr>
													<td colspan="2" style="padding-left:10px; padding-right:10px; white-space:nowrap;">
														<span ID="exportDNASeqFasta" style="font-weight:normal; padding-top:0" class="linkExportSequence" onClick="exportDNASeqToFasta('<?php echo str_replace("'", "\'", $content); ?>')">Export to FASTA</span>
							
														<span ID="viewRestrMap" class="linkExportSequence" style="padding-left:7px; font-weight:normal;" onClick="document.restriction_map_insert.submit()">Restriction Map</span>

														<!-- March 19, 2010: Print Oligos for Vector -->
														<!-- Correction Aug. 18, 2010: DON'T print sequencing primers for other reagent types!!!!!!!!!! -->
														<?php
															if ($reagentType == $_SESSION["ReagentType_Name_ID"]["Vector"])
															{
																?><span ID="viewOligos" class="linkExportSequence" style="font-weight:normal; padding-left:7px;" onClick="document.vector_oligos_form.submit()">Sequencing Primers</span><?php

																//echo "<IMG SRC=\"pictures/new01.gif\" ALT=\"new\" WIDTH=\"35\" HEIGHT=\"20\" style=\"cursor:auto\">";
															}
														?>
													</td>
												</tr>
			
												<tr>
													<td colspan="6">
														<?php
															echo "<DIV class=\"tabSequence\" style=\"margin-top:4px;\">";
																$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"]["sequence"] . $genPostfix , "sequence", $sequence, $reagentIDToView, false, "DNA Sequence");
															echo "</DIV>";
														?>
													</td>
												</tr>
								
												<tr><td colspan="6" style="padding-top:4px"></td></tr>
												
												<tr>
													<td class="detailedView_colName" style="padding-left:6px;">
														DNA Length:
													</td>
										
													<td class="detailedView_value">
									
														<SPAN style="color:#006400"><?php
															echo $insertLength;
														?></SPAN>&nbsp;nucleotides
													</td>
												</tr>
										
												<tr id="dnaLengthModify" style="display:none">
													<td class="detailedView_colName" style="padding-left:6px;">
														DNA Length:
													</td>
										
													<td class="detailedView_value">
									
														<SPAN style="color:#006400"><?php
															echo $insertLength;
														?></SPAN>&nbsp;nucleotides
													</td>
												</tr>

												<tr><td colspan="6"></td></tr>
												<?php

//												echo "</FORM>";
											
												if ($rfunc_obj->hasAttribute($reagentType, "molecular weight", $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]))
												{
													// get MW and Tm for Oligo - Jan. 25/10: no, compute from sequence
													?><tr>
														<td class="detailedView_colName" style="padding-left:6px;">Molecular Weight:</td>
											
														<td class="detailedView_value"><?php 
															if ($mw && ($mw > 0))
																echo round($mw, 2) . " g/mol";
														?></td>
													</tr><?php
												}

												if ($rfunc_obj->hasAttribute($reagentType, "melting temperature", $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]))
												{
													?><tr>
														<td class="detailedView_colName" style="padding-left:6px;">Melting Temperature:</td>
											
														<td class="detailedView_value"><?php
															if ($tm && ($tm > 0))
																echo $tm . " &#176;C";
														?></td>
													</tr><?php
												}

												if ($rfunc_obj->hasAttribute($reagentType, "gc content", $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]))
												{
													$gc_content = $rfunc_obj->get_gc_content($sequence);

													?><tr>
														<td class="detailedView_colName" style="padding-left:6px;">GC content:</td>
											
														<td class="detailedView_value"><?php
															if ($gc_content && ($gc_content > 0))
																echo $gc_content . " &#37;";
														?></td>
													</tr><?php
												}

												?><tr><td colspan="6"></td></tr><?php

												// April 19, 2011
												$all_attributes = $rfunc_obj->getReagentTypeAttributesByCategory($reagentType, $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);

												// May 5, 2010: keys are now attribute IDs
												foreach ($all_attributes as $attrID => $attribute)
												{
													$pName = $attribute->getPropertyName();

													if (($pName != "sequence") && ($pName != "melting temperature") && ($pName != "molecular weight") && ($pName != "gc content") && ($pName != "length"))
													{
														// echo $pName . "<BR>";

														$p_id = $_SESSION["ReagentProp_Name_ID"][$pName];

														$pcID = $rfunc_obj->getPropertyIDInCategory($p_id, $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);

														// only printing properties whose values are filled in
														if (in_array($pcID, array_keys($r_props)))
														{
															$props = $r_props[$pcID];

															if (count($props) == 1)
															{
																$prop = $props[0];

																$propval = "";
						
																$propName = $prop->getPropertyName();
																$propDescr = $prop->getPropertyDescription();
																$propval = $prop->getPropertyValue();

																echo "<TR ID=\"" . $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"] . "\">";

																echo "<TD class=\"detailedView_colName\" style=\"font-weight:bold;\">" . $propDescr . ":</TD>";
																	echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";		
																		$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"][$propName] . $genPostfix, $propName, $propval, $reagentIDToView, false, "DNA Sequence");
																	echo "</TD>";
																echo "</TR>";
															}
															else
															{
																// Property with multiple values, e.g. a multiple dropdown
																$pVals = Array();

																foreach ($props as $pkey => $a_prop)
																{
																	$pVals[$pcID][] = $a_prop->getPropertyValue();
																}

																$pID = $rfunc_obj->findPropertyInCategoryID($pcID);
							
																$propName = $_SESSION["ReagentProp_ID_Name"][$pID];
																$propDescr = $_SESSION["ReagentProp_ID_Desc"][$pID];
																
																echo "<TR ID=\"" . $_SESSION["ReagentPropCategory_Name_Alias"]["DNA Sequence"] . "\">";
																	echo "<TD class=\"detailedView_colName\" style=\"font-weight:bold;\">" . $propDescr . ":</td>";

																	echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
																		$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"][$propName] . $genPostfix, $propName, $pVals[$pcID], $reagentIDToView, false, "DNA Sequence");
																	echo "</TD>";
																echo "</TR>";															
															}
														}
													}
												}
										?></table>

										<TABLE ID="sequence_properties_tbl_modify" style="display:none">
											<tr>
												<td colspan="6">	<!-- keep colspan!-->
													<?php
														// Feb 28/08 - Sequence in Modify mode - hidden
														echo "<DIV style=\"margin-top:4px;\">";

														$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"]["sequence"] . $genPostfix , "sequence", $sequence, $reagentIDToView, true, "DNA Sequence");

														echo "</DIV>";
													?>
												</td>
											</tr>
											<?php

										$all_attributes = $rfunc_obj->getReagentTypeAttributesByCategory($reagentType, $categoryID);

										// May 5, 2010: keys are now attribute IDs
										foreach ($all_attributes as $attrID => $attribute)
										{
											$pName = $attribute->getPropertyName();

											if (($pName != "sequence") && ($pName != "melting temperature") && ($pName != "molecular weight") && ($pName != "gc content") && ($pName != "length"))
											{
												$p_id = $_SESSION["ReagentProp_Name_ID"][$pName];

												$pcID = $rfunc_obj->getPropertyIDInCategory($p_id, $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);

												if (in_array($pcID, array_keys($r_props)))
												{
													$props = $r_props[$pcID];

													if (count($props) == 1)
													{
														$prop = $props[0];
														
														$propName = "";
														$propDescr = "";
														$propval = "";
				
														$propName = $prop->getPropertyName();

														$propDescr = $prop->getPropertyDescription();
														$propval = $prop->getPropertyValue();

														echo "<TR>";

															echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr . "</TD>";
															
															echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";	
															
																$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"][$propName] . $genPostfix, $propName, $propval, $reagentIDToView, true, "DNA Sequence");
															
															echo "</TD>";

														echo "</TR>";
													}
													else
													{
														$pVals = Array();

														foreach ($props as $pkey => $a_prop)
														{
															$pVals[] = $a_prop->getPropertyValue();
														}
//print_r($pVals);
														$pID = $rfunc_obj->findPropertyInCategoryID($pcID);
					
														$propName = $_SESSION["ReagentProp_ID_Name"][$pID];

														$propDescr = $_SESSION["ReagentProp_ID_Desc"][$pID];

														echo "<TR>";
															echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr . ":</TD>";

															echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
																$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"][$propName] . $genPostfix, $propName, $pVals, $reagentIDToView, true, "DNA Sequence");
															echo "</TD>";
														echo "</TR>";
													}
												}
												else
												{
													$tmp_propID = $rfunc_obj->findPropertyInCategoryID($pcID);
													$tmp_propDescr = $_SESSION["ReagentProp_ID_Desc"][$tmp_propID];
													$tmp_propName = $_SESSION["ReagentProp_ID_Name"][$tmp_propID];
			
													if (strcasecmp($tmp_propName, "protein translation") != 0)
													{
														$tmp_propAlias = $_SESSION["ReagentProp_Name_Alias"][$tmp_propName];
				
														echo "<TR>";
															echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $tmp_propDescr . "</TD>";
				
															echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
																$this->print_property_final($genPrefix . $tmp_propAlias . $genPostfix , $tmp_propName, "", $reagentIDToView, true, "DNA Sequence");
															echo "</TD>";
														echo "</TR>";
													}
												}										
											}
										}
										?></table>
										</td>
									</tr><?php
								}
		
								echo "<TR><TD>&nbsp;</TD></TR>";
							?></table>
					</td>
				</tr><?php
			}
			// Aug. 13/09: Different sequence types
			else if ( (strcasecmp($category, "Protein Sequence") == 0) && $rfunc_obj->hasAttribute($reagentType, "protein sequence", $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]))
			{
				// June 18, 2010
				$propsList = $rfunc_obj->findReagentPropertiesByCategory($reagentIDToView, $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]);

				echo "<TR><TD colspan=\"3\" style=\"padding-right:5px;\">&nbsp;</TD></TR>";
			
				?>
				<TR>
					<TD>
						<TABLE class="detailedView_tbl" style="width:725px; border:1px outset; padding:6px;">
							<tr ID="other_reagent_sequence_tbl_view">
								<td class="detailedView_heading" style="text-align:left; padding-left:5px; font-weight:bold; color:blue;">			
									Protein Sequence Information&nbsp;&nbsp;

									<td class="detailedView_heading" style="text-align:right; padding-right:6px; padding-top:0; color:#000000; font-size:10pt; font-family:Helvetica; white-space:nowrap;"><?php

									if ($modify_restricted)
										$modify_disabled = "DISABLED";

									// "Modify" button
									echo "<INPUT TYPE=\"BUTTON\" ID=\"edit_reagent_" . $categoryAlias . "\" style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" " . $modify_disabled . "  VALUE=\"Edit Sequence\" onClick=\"toggleReagentEditModify('edit', 'protein_sequence_properties', '" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";

									// "Save" and "Cancel" buttons
									echo "<DIV ID=\"sequenceButtons\" style=\"display:none\">";
										echo "<INPUT TYPE=\"BUTTON\" ID=\"save_reagent_" . $categoryAlias . "\" style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" " . $modify_disabled . " VALUE=\"Save\" onClick=\"selectAllPropertyValues(); document.pressed = this.value; toggleReagentEditModify('save', '" . $categoryAlias . "', '" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";

										echo "<INPUT TYPE=\"BUTTON\" ID=\"cancel_save_" . $categoryAlias . "\" style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" " . $modify_disabled . "  VALUE=\"Cancel\" onClick=\"document.pressed = this.value; toggleReagentEditModify('cancel', '" . $categoryAlias . "', '" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";
									echo "</DIV>";
									?>
								</td>
							</tr>

							<TR ID="protSequenceContent">
								<TD>
								<?php

									// Protein SEQUENCE INFO
									$insertName = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));
							
									// get sequence by ID
									$seqFunc_obj = new Sequence_Function_Class();
									$protSequence = $seqFunc_obj->findProteinSequence($reagentIDToView);

									if ($protSequence)
									{
										$protSeq = $protSequence->getSequence();

										// Jan. 25/10
										$protMW = $protSequence->getMW();

										if (!$protMW || ($protMW == null) || ($protMW < 0))
										{
											$protMW = $rfunc_obj->computeProteinMW($protSeq);
										}
									}

									$protLength = strlen(str_replace(" ", "", $protSeq));

									$proteinFastaContent = ">" . $limsID . ": " . $insertName . "; length " . $protLength . '\r' . $protSeq . '\r';

									?><span class="linkExportSequence" style="font-weight:normal;" onClick="exportProteinSeqToFasta('<?php echo $proteinFastaContent; ?>')">Export to FASTA</span>
								</td>
							</tr>
											
							<tr>
								<td colspan="2">											
									<table ID="protein_sequence_properties_tbl_view">
										<tr>
											<td colspan="6"><?php
												echo "<DIV style=\"margin-top:2px; padding-left:10px;\" class=\"tabSequence\">";

												echo "<table cellpadding=\"4\" border=\"0\">";

												if (strlen(str_replace(" ", "", trim($protSeq))) > 0)
												{
													$tmp_protSeq = $rfunc_obj->spaces($protSeq);
													$tmp_prot = split("\n", $tmp_protSeq);
											
													foreach ($tmp_prot as $i=>$tok)
													{
														echo "<TR>";
															$rowIndex = $i+1;
															
															if ($i == 0)	// first row
															{
																$rowStart = 1;
															}
															else
															{
																$rowStart = $i*100 + 1;
															}
															
															
															// Nov. 3/06 - Output nt count on last line
															if ($protLength >= ($rowIndex*100))
															{
																$rowEnd = $rowIndex*100;
															}
															else
															{
																$rowEnd = $protLength;
															}
															
															// July 15/08: Show start position only
															echo "<TD nowrap style=\"font-size:10px; font-weight:bold; text-align:right; vertical-align:top\">" . $rowStart . "</TD>";
										
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
												}

												echo "</div>";
												echo "</TABLE>";
											?></td>
										</tr>

										<!-- print the rest of Protein sequence properties in 'view' state -->

										<!-- Sequence length first -->
										<TR>
											<td class="detailedView_colName" style="padding-left:15px;">Length:</td>
											
											<td class="detailedView_value">
												<?php 
													echo $protLength . " aa";
												?>
											</td>
										</tr>
										<?php

										// May 2, 2011: Sort properties in order
										$psorted = Array();
		
										sort(array_values($tmp_order_list));

										foreach ($tmp_order_list as $pcID => $pOrd)
										{
											$tmp_sorted = Array();
											$tmp_sorted = $psorted[$pOrd];
											$tmp_sorted[] = $pcID;
											$psorted[$pOrd] = $tmp_sorted;
										}

									// May 5, 2010: keys are now attribute IDs
									$categoryID = $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"];

									$all_attributes = $rfunc_obj->getReagentTypeAttributesByCategory($reagentType, $categoryID);

									foreach ($all_attributes as $attrID => $attribute)
									{
										$pName = $attribute->getPropertyName();

										if (($pName != "protein sequence") && ($pName != "length"))
										{
											$p_id = $_SESSION["ReagentProp_Name_ID"][$pName];

											$pcID = $rfunc_obj->getPropertyIDInCategory($p_id, $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]);

											$r_props = $propsList[$pcID];

											$pVals = Array();

											foreach ($r_props as $pkey => $a_prop)
											{
												$pVals[] = $a_prop->getPropertyValue();
											}

											if (count($pVals) == 1)
												$pVals = $pVals[0];

											$pID = $rfunc_obj->findPropertyInCategoryID($pcID);

											$propName = $_SESSION["ReagentProp_ID_Name"][$pID];

											$propDescr = $_SESSION["ReagentProp_ID_Desc"][$pID];

											if ($pVals && $pVals != "" && count($pVals) > 0)
											{
												echo "<TR>";
													echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr . ":</TD>";

													echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
														$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"][$propName] . $genPostfix, $propName, $pVals, $reagentIDToView, false, "Protein Sequence", "", "", $reagentType, false, "", 0, 0, true, false, false, false);
													echo "</TD>";
												echo "</TR>";
											}
										}
									}

									?>
									</table>

									<table style="display:none;" ID="protein_sequence_properties_tbl_modify">
										<tr>
											<td colspan="5"><?php
												echo "<DIV style=\"margin-top:4px;\">";
													$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"]["protein sequence"] . $genPostfix , "protein sequence", $protSeq, $reagentIDToView, true, "Protein Sequence", "", "", $reagentType, false, "", 0, 0, true, false, false, false);
												echo "</DIV>";
											?></td>
										</tr><?php

										// May 2, 2011: Sort properties in order
										$psorted = Array();
		
										sort(array_values($tmp_order_list));

										foreach ($tmp_order_list as $pcID => $pOrd)
										{
											$tmp_sorted = Array();
											$tmp_sorted = $psorted[$pOrd];
											$tmp_sorted[] = $pcID;
											$psorted[$pOrd] = $tmp_sorted;
										}

										// May 5, 2010: keys are now attribute IDs
										$categoryID = $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"];

										$all_attributes = $rfunc_obj->getReagentTypeAttributesByCategory($reagentType, $categoryID);

										foreach ($all_attributes as $attrID => $attribute)
										{
											$pName = $attribute->getPropertyName();

											if (($pName != "protein sequence") && ($pName != "molecular weight") && ($pName != "length"))
											{
												$p_id = $_SESSION["ReagentProp_Name_ID"][$pName];

												$pcID = $rfunc_obj->getPropertyIDInCategory($p_id, $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]);

												$r_props = $propsList[$pcID];

												$pVals = Array();

												foreach ($r_props as $pkey => $a_prop)
												{
													$pVals[] = $a_prop->getPropertyValue();
												}

												if (count($pVals) == 1)
													$pVals = $pVals[0];

												$pID = $rfunc_obj->findPropertyInCategoryID($pcID);

												$propName = $_SESSION["ReagentProp_ID_Name"][$pID];

												$propDescr = $_SESSION["ReagentProp_ID_Desc"][$pID];
											
												echo "<TR>";
													echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr . ":</TD>";

													echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
													
														$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"][$propName] . $genPostfix, $propName, $pVals, $reagentIDToView, true, "Protein Sequence", "", "", $reagentType, false, "", 0, 0, true, false, false, false);
													echo "</TD>";
												echo "</TR>";
											
											}
										}

										?><tr><td colspan="6"></td></tr>
									</table>
								</td>
							</tr>			
						</TABLE>
					</TD>
				</TR>
				<?php
			}
			// Update Dec. 4/09: if there is no sequence at all, the 'else' would output 'RNA'!!
			else if ( (strcasecmp($category, "RNA Sequence") == 0) && $rfunc_obj->hasAttribute($reagentType, "rna sequence", $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]))
			{
				// June 18, 2010
				$propsList = $rfunc_obj->findReagentPropertiesByCategory($reagentIDToView, $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]);

				#print_r($propsList);

				echo "<TR><TD colspan=\"3\" style=\"padding-right:5px;\">&nbsp;</TD></TR>";
			
				?><tr>
					<TD colspan="2">
						<TABLE class="detailedView_tbl" style="width:100%; border:1px outset; padding:6px; background:#FFF8DC;" style="border: 1px solid black;">
							<tr ID="other_reagent_sequence_tbl_view">
								<td class="detailedView_heading" style="text-align:left; padding-left:5px; font-weight:bold; color:blue;">
									RNA Sequence Information

									<td class="detailedView_heading" style="text-align:right; padding-right:6px; padding-top:0; color:#000000; font-size:10pt; font-family:Helvetica; white-space:nowrap;"><?php
		
									if ($modify_restricted)
										$modify_disabled = "DISABLED";
		
									// "Modify" button
									echo "<INPUT TYPE=\"BUTTON\" ID=\"edit_reagent_" . $categoryAlias . "\" style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" " . $modify_disabled . "  VALUE=\"Edit Sequence\" onClick=\"toggleReagentEditModify('edit', 'rna_sequence_properties', '" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";

									// "Save" and "Cancel" buttons
									echo "<DIV ID=\"sequenceButtons\" style=\"display:none\">";
										echo "<INPUT TYPE=\"BUTTON\" ID=\"save_reagent_" . $categoryAlias . "\" style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" " . $modify_disabled . " VALUE=\"Save\" onClick=\"selectAllPropertyValues(); document.pressed = this.value; toggleReagentEditModify('save', '" . $categoryAlias . "', '" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";

										echo "<INPUT TYPE=\"BUTTON\" ID=\"cancel_save_" . $categoryAlias . "\" style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" " . $modify_disabled . "  VALUE=\"Cancel\" onClick=\"document.pressed = this.value; toggleReagentEditModify('cancel', '" . $categoryAlias . "', '" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";
									echo "</DIV>";
									?>
								</td>
							</tr>

							<TR>
								<TD class="rnaSequenceContent" colspan="2"><?php

									// RNA SEQUENCE INFO
									$insertName = $rfunc_obj->getPropertyValue($reagentIDToView, $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]));
							
									// get sequence by ID
									$seqFunc_obj = new Sequence_Function_Class();
									$rnaSequence = $seqFunc_obj->findRNASequence($reagentIDToView);
// 									print_r($rnaSequence);
									if ($rnaSequence)
									{
										$rnaSeq = $rnaSequence->getSequence();

										// Jan. 25/10
										$rnaMW = $rnaSequence->getMW();
										
										/* April 14, 2011: no, don't calculate any of those for RNA
										if (!$rnaMW || ($rnaMW == null) || ($rnaMW < 0))
										{
											$rnaMW = $rfunc_obj->getRNA_MW($rnaSeq);
										}

										$rna_tm = $rnaSequence->getTM();

										if (!$rna_tm || ($rna_tm == null) || ($rna_tm < 0))
										{
											$rna_tm = $rfunc_obj->calcTemp($rnaSeq);
										}
										*/
									}

									// Replaced July 13/09
									$rnaLength = strlen(str_replace(" ", "", $rnaSeq));
									$rnaFastaContent = ">" . $limsID . ": " . $insertName . "; length " . $rnaLength . '\r' . trim($rnaSeq) . '\r';

									?><TABLE ID="rna_sequence_table" border="0">
										<tr>
											<td colspan="6" style="font-size:9pt; margin-top:5px; padding-left:2px; font-weight:bold;">

												<span class="linkExportSequence" style="font-weight:normal;" onClick="exportRNASeqToFasta('<?php echo $rnaFastaContent; ?>')">Export to FASTA</span>
											</td>
										</tr>
								
										<tr>
											<td colspan="2">												
												<table ID="rna_sequence_properties_tbl_view">
													<tr>
														<td colspan="6"><?php
															echo "<DIV style=\"margin-top:2px; padding-left:10px;\" class=\"tabSequence\">";

															echo "<table cellpadding=\"4\" border=\"0\">";

															if (strlen(str_replace(" ", "", trim($rnaSeq))) > 0)
															{
																$tmp_rnaSeq = $rfunc_obj->spaces($rnaSeq);
																$tmp_rna = split("\n", $tmp_rnaSeq);
														
																foreach ($tmp_rna as $i=>$tok)
																{
																	echo "<TR>";
																		$rowIndex = $i+1;
																		
																		if ($i == 0)	// first row
																		{
																			$rowStart = 1;
																		}
																		else
																		{
																			$rowStart = $i*100 + 1;
																		}
																		
																		
																		// Nov. 3/06 - Output nt count on last line
																		if ($rnaLength >= ($rowIndex*100))
																		{
																			$rowEnd = $rowIndex*100;
																		}
																		else
																		{
																			$rowEnd = $rnaLength;
																		}
																		
																		// July 15/08: Show start position only
																		echo "<TD nowrap style=\"font-size:10px; font-weight:bold; text-align:right; vertical-align:top\">" . $rowStart . "</TD>";
													
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
															}

															echo "</table>";

															echo "</DIV>";
														?>
														</td>
													</tr>

													<!-- print the rest of RNA sequence properties in 'view' state -->

													<!-- Sequence length first -->
													<TR>
														<td class="detailedView_colName" style="padding-left:15px;">Length:</td>
														
														<td class="detailedView_value">
															<?php 
																echo $rnaLength . " nt";
															?>
														</td>
													</tr>
													<?php
														
														// May 2, 2011: Sort properties in order
														$psorted = Array();
						
														sort(array_values($tmp_order_list));

														foreach ($tmp_order_list as $pcID => $pOrd)
														{
															$tmp_sorted = Array();
															$tmp_sorted = $psorted[$pOrd];
															$tmp_sorted[] = $pcID;
															$psorted[$pOrd] = $tmp_sorted;
														}

													// May 5, 2010: keys are now attribute IDs
													$categoryID = $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"];

													$all_attributes = $rfunc_obj->getReagentTypeAttributesByCategory($reagentType, $categoryID);

													foreach ($all_attributes as $attrID => $attribute)
													{
														$pName = $attribute->getPropertyName();

														if (($pName != "rna sequence") && ($pName != "gc content") && ($pName != "length"))
														{
														//	echo $pName;

															$p_id = $_SESSION["ReagentProp_Name_ID"][$pName];

															$pcID = $rfunc_obj->getPropertyIDInCategory($p_id, $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]);

															$r_props = $propsList[$pcID];

															//print_r($r_props);

															$pVals = Array();

															foreach ($r_props as $pkey => $a_prop)
															{
																$pVals[] = $a_prop->getPropertyValue();
															}

															if (count($pVals) == 1)
																$pVals = $pVals[0];

															$pID = $rfunc_obj->findPropertyInCategoryID($pcID);

															$propName = $_SESSION["ReagentProp_ID_Name"][$pID];

															$propDescr = $_SESSION["ReagentProp_ID_Desc"][$pID];

															if ($pVals && $pVals != "" && count($pVals) > 0)
															{
																echo "<TR>";
																	echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr . ":</TD>";

																	echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
																	
																		$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"][$propName] . $genPostfix, $propName, $pVals, $reagentIDToView, false, "RNA Sequence", "", "", $reagentType, false, "", 0, 0, false, true, false, false);

																	echo "</TD>";
																echo "</TR>";
															}
														}
													}

													?>
												</table>									
												
												<table ID="rna_sequence_properties_tbl_modify" style="display:none;">
												<?php
													
													// sequence first
													echo "<tr>";
														echo "<td colspan=\"6\">";
															echo "<DIV style=\"margin-top:2px; padding-left:10px;\" class=\"tabSequence\">";
																$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"]["rna sequence"] . $genPostfix, "rna sequence", $rnaSeq, $reagentIDToView, true, "RNA Sequence", "", "", $reagentType, false, "", 0, 0, false, true, false, false);
															echo "</DIV>";
														echo "</td>";
													echo "</tr>";
													
													$categoryID = $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"];

													$all_attributes = $rfunc_obj->getReagentTypeAttributesByCategory($reagentType, $categoryID);

													// May 5, 2010: keys are now attribute IDs
													foreach ($all_attributes as $attrID => $attribute)
													{
														$pName = $attribute->getPropertyName();

														if (($pName != "rna sequence") && ($pName != "gc content") && ($pName != "length"))
														{
												
															$p_id = $_SESSION["ReagentProp_Name_ID"][$pName];

															$pcID = $rfunc_obj->getPropertyIDInCategory($p_id, $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]);

															$r_props = $propsList[$pcID];

															//print_r($r_props);

															$pVals = Array();

															foreach ($r_props as $pkey => $a_prop)
															{
																$pVals[] = $a_prop->getPropertyValue();
															}

											//	print_r($pVals);

															if (count($pVals) == 1)
																$pVals = $pVals[0];

															$pID = $rfunc_obj->findPropertyInCategoryID($pcID);

															$propName = $_SESSION["ReagentProp_ID_Name"][$pID];

															$propDescr = $_SESSION["ReagentProp_ID_Desc"][$pID];

															echo "<TR>";
																echo "<TD class=\"detailedView_colName\" style=\"padding-left:15px; font-weight:bold;\">" . $propDescr . ":</TD>";

																echo "<TD colspan=\"4\" class=\"detailedView_value\" style=\"white-space:normal;\">";
																
																	$this->print_property_final($genPrefix . $_SESSION["ReagentProp_Name_Alias"][$propName] . $genPostfix, $propName, $pVals, $reagentIDToView, true, "RNA Sequence", "", "", $reagentType, false, "", 0, 0, false, true, false, false);
																echo "</TD>";
															echo "</TR>";
														}
													}
												?></table>
											</td>
										</tr>		
									</table>
								<td>
							</tr>
						</table>
					<!-- </td>
				</tr> -->
				<?php
			}
			
			// 'switch' was here - removed June 23/09, look in SVN if anything
			?>
			</FORM>
			<?php
		}

		?>
		<!-- Export sequence to FASTA -->
		<form id="export_dna_sequence_form" name="dna_sequence_export" action="Reagent/fasta.php" method="POST">
			<input type="hidden" name="filename" value="<?php echo $limsID . "_DNA"; ?>"/>
			<input type="hidden" id="dna_fasta_content" name="dnaFastaContent" value="<?php echo $content; ?>" />
			<input type="hidden" id="currDNAExportSelection" name="curr_export_sel" value="DNA">
		</form>
		
		<form id="export_protein_sequence_form" name="protein_sequence_export" action="Reagent/fasta.php" method="POST">
			<input type="hidden" name="filename" value="<?php echo $limsID . "_PEP"; ?>"/>
			<input type="hidden" id="protein_fasta_content" name="protFastaContent" value="<?php echo $proteinFastaContent; ?>" />
			<input type="hidden" id="currProteinExportSelection" name="curr_export_sel" value="Protein">
		</form>

		<form id="export_rna_sequence_form" name="rna_sequence_export" action="Reagent/fasta.php" method="POST">
			<input type="hidden" name="filename" value="<?php echo $limsID . "_RNA"; ?>"/>
			<input type="hidden" id="rna_fasta_content" name="rnaFastaContent" value="<?php echo $rnaFastaContent; ?>" />
			<input type="hidden" id="currRNAExportSelection" name="curr_export_sel" value="RNA">
		</form>
		

		<form id="restriction_map_vector_form" name="restriction_map_vector" action="<?php echo $cgi_path . "restriction.py"; ?>" method="POST">
			<input type="hidden" name="limsID" value="<?php echo $limsID; ?>">
			<input type="hidden" name="vector_sequence" value="<?php echo $sequence; ?>">
		</form>
	
		<!-- Print all Vector frames - Jan. 23/09 -->
		<form id="vector_frames" name="vector_frames_form" action="<?php echo $cgi_path . "vector_frames.py"; ?>" method="POST">
			<input type="hidden" name="rID" value="<?php echo $reagentIDToView; ?>">
			<input type="hidden" name="vector_sequence" value="<?php echo $sequence; ?>">
			<input type="hidden" name="orf_type">
		</form>

		<!-- Print all Vector frames - March 19, 2010 -->
		<form id="vector_oligos" name="vector_oligos_form" action="<?php echo $cgi_path . "vector_oligos.py"; ?>" method="POST">
			<input type="hidden" name="rID" value="<?php echo $reagentIDToView; ?>">
			<input type="hidden" name="vector_sequence" value="<?php echo $sequence; ?>">
		</form>

		<form id="oligos_vector_map" name="oligos_vector_map_form" action="<?php echo $cgi_path . "oligos_vector_map.py"; ?>" method="POST">
			<input type="hidden" name="rID" value="<?php echo $reagentIDToView; ?>">
			<input type="hidden" name="user_id_hidden" value="<?php echo $currUserID; ?>">
			<input type="hidden" name="vector_sequence" value="<?php echo $sequence; ?>">
		</form>

		<!-- Restriction map -->
		<form id="restriction_map_insert_form" name="restriction_map_insert" action="<?php echo $cgi_path . "restriction.py"; ?>" method="POST">
			<input type="hidden" name="limsID" value="<?php echo $limsID; ?>">
			<input type="hidden" name="insert_sequence" value="<?php echo $sequence; ?>">
		</form><?php

		// PARENT/CHILD INFO
		?><tr ID="other_reagent_parents_tbl_view">
			<td colspan="6" style="padding-top:15px;">

			<input type="hidden" id="vector_cloning_method" name="cloning_method_hidden" value="<?php echo $cloningMethod; ?>">
			<input type="hidden" id="curr_uname" name="curr_username" value="<?php echo $currUserName; ?>">
			
			<!-- Set hidden action identifiers for Python -->
			<input type="hidden" ID="changeStateVectorIntro" name="change_state">
			
				<table style="width:725px; background-color:#FFF8DC; border: 1px outset; margin-top:8px;">
					<tr>
						<td colspan="4" class="detailedView_heading" style="text-align:left; padding-left:12px; padding-right:6px; padding-top:10px; font-size:10pt; color:blue; font-weight:bold; white-space:nowrap;">
							Parent-Child Information
						</td><?php

							// Dec. 22/09: find cell line type here and don't show Edit button for Parent
							$clTypePropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["cell line type"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

							$cellLineType = $rfunc_obj->getPropertyValue($reagentIDToView, $clTypePropID);
	
							// July 13/09: don't show Edit Parents section for Parent Vector
							if (($cloningMethod != 3) && (strtolower($cellLineType) != "parent") && ($_SESSION["ReagentType_ID_Name"][$reagentType] != 'Oligo'))
							{
								?><td style="text-align:right; padding-right:12px; color:#000000; font-size:10pt;"><?php
	
								if (!$modify_restricted)
								{
									// fixed number of parents
									if (($_SESSION["ReagentType_ID_Name"][$reagentType] == "Vector") || ($_SESSION["ReagentType_ID_Name"][$reagentType] == "CellLine"))
										echo "<INPUT TYPE=\"BUTTON\" ID=\"editReagentParentsBtn\" style=\"margin-left:8px; font-weight:bold; font-size:8pt;\" VALUE=\"Edit Parents\" onClick=\"document.pressed=this.value; switchReagentParentsModify('" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";
									else
										echo "<INPUT TYPE=\"BUTTON\" ID=\"editReagentParentsBtn\" style=\"margin-left:8px; font-weight:bold; font-size:8pt;\" VALUE=\"Edit Parents\" onClick=\"document.pressed=this.value; switchReagentParentsModify('" . $reagentType . "');\"></INPUT>";

									// Feb. 5, 2010
									if ($_SESSION["ReagentType_ID_Name"][$reagentType] != 'CellLine')
									{
										// "Save" button - hidden initially
										$assoc = $rfunc_obj->findReagentTypeAssociations($reagentType);

										echo "<INPUT TYPE=\"BUTTON\" ID=\"saveReagentParentsBtn\" style=\"display:none; margin-left:10px; font-weight:bold; font-size:8pt;\" VALUE=\"Save\" onClick=\"document.pressed=this.value; if (checkParentFormat('" . $_SESSION["ReagentType_ID_Name"][$reagentType] . "')) saveReagentParents('" . str_replace("'", "\'", $_SESSION["ReagentType_ID_Name"][$reagentType]) . "');\"></INPUT>";
									}

									// Ditto "Cancel" button
									echo "<INPUT TYPE=\"BUTTON\" ID=\"cancelChangeReagentParents\" style=\"display:none; margin-left:10px; font-weight:bold; font-size:8pt;\" VALUE=\"Cancel\" onClick=\"document.pressed=this.value; cancelReagentParentsModification();\"></INPUT>";
								}
								else
								{
									// Show disabled "Modify" button
									echo "<INPUT TYPE=\"BUTTON\" DISABLED style=\"margin-left:10px; font-weight:bold; font-size:8pt;\" VALUE=\"Edit Parents\"></INPUT>";
								}
							}
						?></td>
					</tr>

					<tr>
						<td colspan="7"><?php
							switch ($_SESSION["ReagentType_ID_Name"][$reagentType])
							{
								case 'Vector':
									?><FORM NAME="reagentParentsForm" METHOD="POST" ACTION="<?php echo $cgi_path . "update.py"; ?>">
						
									<input type="hidden" ID="changeStateParents" name="change_state">
									<input type="hidden" name="save_parents">
									<input type="hidden" ID="rID_hidden" name="reagent_id_hidden" value="<?php echo $reagentIDToView; ?>">
									<input type="hidden" id="cloning_method" name="cloning_method_hidden" value="<?php echo $cloningMethod; ?>">
									<input type="hidden" id="curr_uname" name="curr_username" value="<?php echo $currUserName; ?>">
									<input type="hidden" name="reagent_typeid_hidden" value="<?php echo $reagentType; ?>">
									<input type="hidden" name="reagent_groupnum_hidden" value="<?php echo $reagentGroup; ?>">

									<table id="showVectorAssoc">
										<tr>
											<td colspan="5">
											<?php
												$this->printVectorParents($reagentIDToView);
											?>
											</TD>
										</TR>
										<?php
											if (count($children) > 0)
											{
												?><tr>
													<td colspan="5" class="detailedView_parents" style="font-style:italic; font-weight:bold; padding-left:25px; padding-top:10px;">
														<P>Children
													<td>
												</tr>
				
												<TR>
													<TD COLSPAN="6" style="padding-top:10px;">
														<TABLE>
															<tr><?php
																$cols = 0;
																$MAX_COLS = 17;

																foreach (array_unique($childrenTypes) as $key => $value)
																{
																	if ($value == 4)
																		echo "<td style=\"padding-left:40px; width:180px; white-space:nowrap\">Cell Line Children</td>";
																	else
																		echo "<td style=\"padding-left:40px; width:180px; white-space:nowrap\">" . $_SESSION["ReagentType_ID_Name"][$value] . " Children</td>";
																
																		?><td width="350px">
																			<SPAN id="show_<?php echo $_SESSION["ReagentType_ID_Name"][$value]; ?>" class="linkShow" onclick="showHideChildren(this.id, '<?php echo $_SESSION["ReagentType_ID_Name"][$value] . "_children"; ?>');">Show Children</SPAN>
									
																			<SPAN id="hide_<?php echo $_SESSION["ReagentType_ID_Name"][$value]; ?>" class="linkHide" onclick="showHideChildren(this.id, '<?php echo $_SESSION["ReagentType_ID_Name"][$value] . "_children"; ?>');">Hide Children</SPAN>
																			&nbsp;&nbsp;<B>(<?php echo $num_children[$value]; ?>&nbsp; total)</B>
																		</td>
																	</tr>
								
																	<tr id="<?php echo $_SESSION["ReagentType_ID_Name"][$value] . "_children"; ?>" style="display:none;">
																		<td colspan="5">
																			<div class="children">
																				<TABLE class="children">
																					<TR><?php

																					foreach ($children as $aPropID => $childrenIDs)
																					{
																						foreach ($childrenIDs as $cKey => $childID)
																						{
																							$childTypeID = $gfunc_obj->getTypeID($childID);
											
																							if ($childTypeID == $value)
																							{
																								if ($cols == $MAX_COLS)
																								{
																									echo "</tr><tr>";
																									$cols = 0;
																								}
						
																								echo "<td>";
			
																									$tmpProject = getReagentProjectID($childID);
																									$userProjects = getAllowedUserProjectIDs($_SESSION["userinfo"]->getUserID());	// redefine here again, b/c before it was defined as *Writeable* projects only
																							
																									if (($tmpProject && ($tmpProject > 0)) && (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($tmpProject, $userProjects)))
																									{
																										echo "<a href=\"Reagent.php?View=6&rid=" . $childID . "\">" . $gfunc_obj->getConvertedID_rid($childID) . "</a>&nbsp;&nbsp;";
																									}
																									else
																									{
																										echo "<span class=\"linkDisabled\">" . $gfunc_obj->getConvertedID_rid($childID) . "</span>&nbsp;&nbsp;";
																									}

																								echo "</td>";
																								$cols++;
																							}
																						}
																					}
															
																					?></TR>
																				</TABLE>
																			</DIV>
																		</td>
																	</tr><?php
																}

																?>
																</tr>
															</TABLE>
														</TD>
													</TR><?php
												}	// end if has children
												?>
											</td>
										</tr>
									</table>
					
									<table id="editVectorAssoc" style="display:none">
										<tr>
											<td colspan="5">
												<?php
													$this->printForm_Vector_modify_associations($reagentType, $reagentIDToView, $check);
												?>
											</td>
										</tr>
					
										<!-- July 8/08 -->
										<TR><td colspan="5" style="font-weight:bold; padding-top:8px;">Please update the following Vector information:</TD></TR>
					
										<tr>
											<td class="detailedView_colName">
												Name
												<span style="font-size:10pt; color:#000000; font-weight:bold">*</span>
											</td>
											
											<td class="detailedView_value">
											<?php
												echo "<INPUT type=\"text\" id=\"new_vector_name\" value=\"" . $tempPropHolder_ar[$_SESSION["ReagentProp_Name_ID"]["name"]] . "\"";
											?>
											</td>
										</tr>
					
										<tr>
											<td class="detailedView_colName">Vector Type</td>
											
											<td class="detailedView_value">
											<?php
												echo "<select id=\"new_vector_type\" size=\"1\">";
												echo $this->print_Set_Final_Dropdown("vector type", $tempPropHolder_ar[$_SESSION["ReagentProp_Name_ID"]["vector type"]], "General Properties", $reagentType);
												echo "</select>";
											?>
											</td>
										</tr>
								
										<tr>
											<td class="detailedView_colName">
												Project ID
												<span style="font-size:10pt; color:#000000; font-weight:bold">*</span>
											</td>
											
											<td class="detailedView_value">
											<?php
												echo "<select id=\"new_packet_id\" size=\"1\">";
												echo $this->get_Special_Column_Type("packet", "General Properties", $reagentIDToView, "modify");
												echo "</select>";
					
											?>
											</td>
										</tr>
					
										<tr>
											<td class="detailedView_colName" nowrap>Functional Description</td>
								
											<td class="detailedView_value" colspan="<?php echo $colspan-1; ?>" style="font-weight:bold; font-size:8pt;">
												<?php
													echo "<INPUT type=\"text\" id=\"new_description\" value=\"" . $this->get_Special_Column_Type( "description", $reagentIDToView, "None") . "\"";
												?>
											</td>
										</tr>
							
										<tr>
											<td class="detailedView_colName">Verification</td>
								
											<td class="detailedView_value" colspan="3">
											<?php 
												echo "<select id=\"new_verification\" size=\"1\">";
												echo $this->print_Set_Final_Dropdown("verification",  $tempPropHolder_ar[$_SESSION["ReagentProp_Name_ID"]["verification"]], "General Properties", $reagentType);
												echo "</select>";
											?>
											</td>
										</tr>
									</table>

									<!-- Hidden parent input values -->
									<input type="hidden" id="pv_id_hidden" name="assoc_parent_vector">
									<input type="hidden" id="insert_id_hidden" name="assoc_insert_id">
									<input type="hidden" id="ipv_id_hidden" name="assoc_parent_insert_vector">
									<?php
										$old_pv_id = $bfunc_obj->get_Background_rid($reagentIDToView, "vector parent id");
							
										// Sept. 12/07: Remember old IPV and Insert values too
										$old_insert_id = $bfunc_obj->get_Background_rid($reagentIDToView, "insert id");
										$old_ipv_id = $bfunc_obj->get_Background_rid($reagentIDToView, "parent insert vector");
										
										$old_insert = $gfunc_obj->getConvertedID_rid($old_insert_id);
										$old_ipv = $gfunc_obj->getConvertedID_rid($old_ipv_id);
							
										if (isset($_POST["pv_name_txt"]))
											$old_pv = $_POST["pv_name_txt"];
										else
											$old_pv = $gfunc_obj->getConvertedID_rid($old_pv_id);
							
										if (isset($_POST["change_vp_id"]) && ($_POST["change_vp_id"] == "Change"))
											$change = 1;
										else
											$change = 0;
									?>
									<input type="hidden" id="parent_vector_old_id" name="detailed_view_pv_backfillID_old" value="<?php echo $old_pv; ?>">
									<input type="hidden" id="change_pv" name="change_vp_flag" value="<?php echo $change; ?>">
							
									<!-- Added Sept. 12/07, Marina -->
									<input type="hidden" id="insert_old_id" name="detailed_view_insert_backfillID_old" value="<?php echo $old_insert; ?>">
							
									<!-- Nov. 16/07: Static values -->
									<input type="hidden" id="pv_original" value="<?php echo $old_pv; ?>">
									<input type="hidden" id="insert_original" value="<?php echo $old_insert; ?>">
									<input type="hidden" id="ipv_original" value="<?php echo $old_ipv; ?>">
							
									<input type="hidden" id="ipv_old_id" name="detailed_view_ipv_backfillID_old" value="<?php echo $old_ipv; ?>">

									</FORM><?php
								break;
					
								case 'CellLine':
									$bfunc_obj = new Reagent_Background_Class();
									$gfunc_obj = new generalFunc_Class();
									
									$old_pv_id = $bfunc_obj->get_Background_rid($reagentIDToView, "cell line parent vector id");
									$old_cl_id = $bfunc_obj->get_Background_rid($reagentIDToView, "parent cell line id");
									
									if (isset($_POST["pv_name_txt"]))
										$old_pv = $_POST["pv_name_txt"];
									else
										$old_pv = $gfunc_obj->getConvertedID_rid($old_pv_id);
									
									if (isset($_POST["cl_name_txt"]))
										$old_cl = $_POST["cl_name_txt"];
									else
										$old_cl = $gfunc_obj->getConvertedID_rid($old_cl_id);
					
									?>
									<FORM NAME="reagentParentsForm" METHOD="POST" ACTION="<?php echo $cgi_path . "update.py"; ?>">
						
									<input type="hidden" ID="changeStateParents" name="change_state">
									<input type="hidden" name="save_parents">
									<input type="hidden" ID="rID_hidden" name="reagent_id_hidden" value="<?php echo $reagentIDToView; ?>">
									<input type="hidden" id="cloning_method" name="cloning_method_hidden" value="<?php echo $cloningMethod; ?>">
									<input type="hidden" id="curr_uname" name="curr_username" value="<?php echo $currUserName; ?>">
									<input type="hidden" name="reagent_typeid_hidden" value="<?php echo $reagentType; ?>">
									<input type="hidden" name="reagent_groupnum_hidden" value="<?php echo $reagentGroup; ?>">

									<table id="showCellLineAssoc">
										<tr>
											<td colspan="5"><?php

												if (strtolower($cellLineType) != "parent")
													$this->printCellLineParents($reagentIDToView);

											?></TD>
										</TR><?php

										if (count($children) > 0)
										{
											?><tr>
												<td colspan="5" class="detailedView_parents" style="font-style:italic; font-weight:bold; padding-left:25px; padding-top:10px;">
													<P>Children
												<td>
											</tr>
			
											<TR>
												<TD COLSPAN="6" style="padding-top:10px;">
													<TABLE>
														<tr><?php
															$cols = 0;
															$MAX_COLS = 17;
					
															foreach (array_unique($childrenTypes) as $key => $value)
															{
																if ($value == 4)
																	echo "<td style=\"padding-left:40px; width:180px; white-space:nowrap\">Cell Line Children</td>";
																else
																	echo "<td style=\"padding-left:40px; width:180px; white-space:nowrap\">" . $_SESSION["ReagentType_ID_Name"][$value] . " Children</td>";
															
																	?><td width="350px">
																		<SPAN id="show_<?php echo $_SESSION["ReagentType_ID_Name"][$value]; ?>" class="linkShow" onclick="showHideChildren(this.id, '<?php echo $_SESSION["ReagentType_ID_Name"][$value] . "_children"; ?>');">Show Children</SPAN>
								
																		<SPAN id="hide_<?php echo $_SESSION["ReagentType_ID_Name"][$value]; ?>" class="linkHide" onclick="showHideChildren(this.id, '<?php echo $_SESSION["ReagentType_ID_Name"][$value] . "_children"; ?>');">Hide Children</SPAN>
																		&nbsp;&nbsp;<B>(<?php echo $num_children[$value]; ?>&nbsp; total)</B>
																	</td>
																</tr>
							
																<tr id="<?php echo $_SESSION["ReagentType_ID_Name"][$value] . "_children"; ?>" style="display:none;">
																	<td colspan="5">
																		<div class="children">
																			<TABLE class="children">
																				<TR><?php
																				foreach ($children as $aPropID => $childrenIDs)
																				{
																					foreach ($childrenIDs as $cKey => $childID)
																					{
																						$childTypeID = $gfunc_obj->getTypeID($childID);
										
																						if ($childTypeID == $value)
																						{
																							if ($cols == $MAX_COLS)
																							{
																								echo "</tr><tr>";
																								$cols = 0;
																							}
					
																							echo "<td>";
			
																							$tmpProject = getReagentProjectID($value);
																							
																							if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($tmpProject, $userProjects))
																							{
																								echo "<a href=\"Reagent.php?View=6&rid=" . $childID . "\">" . $gfunc_obj->getConvertedID_rid($childID) . "</a>&nbsp;&nbsp;";
																							}
																							else
																							{
																								echo "<span class=\"linkDisabled\">" . $gfunc_obj->getConvertedID_rid($childID) . "</span>&nbsp;&nbsp;";
																							}
																		
																							echo "</td>";
																							$cols++;
																						}
																					}
																				}
																				?></TR>
																			</TABLE>
																		</DIV>
																	</td>
																</tr>
																<?php
															}

														?></tr>
													</TABLE>
												</TD>
											</TR><?php
										}	// end if has children

									?></table></FORM>
					
									<table id="editCellLineAssoc" style="display:none">
										<tr>
											<td colspan="5">

												<input type="hidden" id="parent_vector_old_id" name="detailed_view_pv_backfillID_old" value="<?php echo $old_pv; ?>">
								
												<input type="hidden" id="parent_cellline_old_id" name="detailed_view_cl_backfillID_old" value="<?php echo $old_cl; ?>">
												
												<input type="hidden" id="change_pv" name="change_pv_flag" value="">
												<input type="hidden" id="change_cl" name="change_cl_flag" value=""><?php
								
												$this->printForm_Vector_modify_associations($reagentType, $reagentIDToView, $check, $error_type="");
											?></TD>
										</TR>
									</TABLE><?php
								break;

								default:
									?><table class="detailedView_parents" ID="viewReagentParents" cellspacing="2" cellpadding="5"><?php
									
									$userProjects = getAllowedUserProjectIDs($_SESSION["userinfo"]->getUserID());

									if (count($parents) > 0)
									{
										?><tr>
											<td colspan="2" class="detailedView_parents" style="font-weight:bold; padding-left:10px;">
												Parents
											</td>
										</tr><?php
									
										// Existing reagent types still have specific output requirements
										foreach ($parentTypes as $key => $value)
										{
// 											print "Parent types: " . $key . ", " . $value . "<BR>";

											echo "<tr><td width=\"180px\" style=\"white-space:nowrap; padding-left:20px; font-size:9pt;\">" . $_SESSION["ReagentAssoc_ID_Description"][$value] . "</td>";
										
											echo "<td>";
	
											foreach ($parents as $aPropID => $parentList)
											{
												foreach ($parentList as $pID => $parentID)
												{
													if ($aPropID == $value)
													{
														$tmpProject = getReagentProjectID($value);

														if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($tmpProject, $userProjects))
														{
															echo "<a style=\"padding-left:10px; padding-right:3px;\" href=\"Reagent.php?View=6&rid=" . $parentID . "\">" . $gfunc_obj->getConvertedID_rid($parentID) . "</a>";
														}
														else
														{
															echo "<span style=\"padding-left:10px; padding-right:3px;\" class=\"linkDisabled\">" . $gfunc_obj->getConvertedID_rid($parentID) . "</span>";
														}
													}
												}
											}

											echo "</td>";
										}
	
										?></tr><?php
									}
		
									?></TABLE><?php

									// feb 12/10
									$assocDict = Array();

									foreach ($assoc as $tmpAssocID => $tmpAssocValue)
									{
										$tmpAssocAlias = $_SESSION["ReagentAssoc_ID_Alias"][$tmpAssocID];
										$assocDict[$tmpAssocID] = $tmpAssocAlias;
									}

								?><FORM NAME="reagentParentsForm" METHOD="POST" ACTION="<?php echo $cgi_path . "update.py"; ?>">
						
									<input type="hidden" ID="changeStateParents" name="change_state">
									<input type="hidden" name="save_parents">
									<input type="hidden" ID="rID_hidden" name="reagent_id_hidden" value="<?php echo $reagentIDToView; ?>">
									<input type="hidden" id="cloning_method" name="cloning_method_hidden" value="<?php echo $cloningMethod; ?>">
									<input type="hidden" id="curr_uname" name="curr_username" value="<?php echo $currUserName; ?>">
									<input type="hidden" name="reagent_typeid_hidden" value="<?php echo $reagentType; ?>">
									<input type="hidden" name="reagent_groupnum_hidden" value="<?php echo $reagentGroup; ?>">
				
									<table ID="category_assoc_section_<?php echo $reagentType; ?>" class="detailedView_parents" style="display:none" cellspacing="2" cellpadding="5"><?php
									
									// placing this here again for even table alignment (July 13/09)
									?><tr>
										<td colspan="2" class="detailedView_parents" style="font-weight:bold; padding-left:10px;">
											Parents
										</td>
									</tr><?php

									// $key is just array indexing; $value is APropertyID
									foreach ($parentTypes as $key => $value)
									{
										$assocAlias = $_SESSION["ReagentAssoc_ID_Alias"][$value];

										// 'value' is APropertyID, use it to grab parent type and prefix (Dec. 10/09)
										if ($value > 0)
										{
											$parentTypeID = $rfunc_obj->findAssocParentType($value);
											$parentPrefix = $_SESSION["ReagentType_ID_Prefix"][$parentTypeID];

											if (in_array($value, array_keys($parents)))
											{
												$parentIDs = $parents[$value];
											
												foreach ($parentIDs as $aKey => $parentID)
												{
													echo "<tr ID=\"" . $_SESSION["ReagentType_ID_Name"][$reagentType] . "_" . $assocAlias . "_assoc_row_" . $aKey . "\">";

													echo "<td width=\"180px\" style=\"white-space:nowrap; padding-left:40px; font-weight:normal;\">" . $_SESSION["ReagentAssoc_ID_Description"][$value] . " (" . $parentPrefix . ")</td>";
	
													echo "<td>";
												
													?><INPUT TYPE="TEXT" ID="<?php echo $_SESSION["ReagentType_ID_Name"][$reagentType]; ?>_assoc_<?php echo $assocAlias; ?>_input" onKeyPress="return disableEnterKey(event);" NAME="<?php echo $_SESSION["ReagentType_ID_Name"][$reagentType]; ?>_assoc_<?php echo $value; ?>_prop" VALUE="<?php echo $gfunc_obj->getConvertedID_rid($parentID); ?>"><?php
	
													if (!in_array($_SESSION["ReagentType_ID_Name"][$reagentType], Array('Vector', 'Insert', 'Oligo', 'CellLine')))
													{
														?><SPAN class="linkExportSequence" style="margin-left:15px; font-weight:normal;" onClick="addParent('<?php echo $reagentType; ?>', '<?php echo $_SESSION["ReagentType_ID_Name"][$reagentType]; ?>', '<?php echo $assocAlias; ?>', '<?php echo $_SESSION["ReagentAssoc_ID_Description"][$value];?>', '<?php echo $value; ?>', '<?php echo $aKey; ?>');">Add New</SPAN>
														<SPAN class="linkExportSequence" style="margin-left:15px; font-weight:normal;" onClick="deleteTableRow('category_assoc_section_<?php echo $reagentType; ?>', '<?php echo $_SESSION["ReagentType_ID_Name"][$reagentType] . "_" . $assocAlias; ?>_assoc_row_<?php echo $aKey; ?>');">Remove</SPAN><?php
													}
	
													echo "</td>";
													echo "</tr>";
												}
											}
											else
											{
												echo "<tr ID=\"" . $_SESSION["ReagentType_ID_Name"][$reagentType] . "_" . $assocAlias . "_assoc_row_" . $aKey . "\">";
	
												echo "<td width=\"180px\" style=\"white-space:nowrap; padding-left:40px; font-weight:normal;\">" . $_SESSION["ReagentAssoc_ID_Description"][$value] . " (" . $parentPrefix . ")</td>";
											
												echo "<td>";

												?><INPUT TYPE="TEXT" ID="<?php echo $_SESSION["ReagentType_ID_Name"][$reagentType]; ?>_assoc_<?php echo $assocAlias; ?>_input" onKeyPress="return disableEnterKey(event);" NAME="<?php echo $_SESSION["ReagentType_ID_Name"][$reagentType]; ?>_assoc_<?php echo $value; ?>_prop" VALUE=""><?php

												if (!in_array($_SESSION["ReagentType_ID_Name"][$reagentType], Array('Vector', 'Insert', 'Oligo', 'CellLine')))
												{
													?><SPAN class="linkExportSequence" style="margin-left:15px; font-weight:normal;" onClick="addParent('<?php echo  $reagentType; ?>', '<?php echo $_SESSION["ReagentType_ID_Name"][$reagentType]; ?>', '<?php echo $assocAlias; ?>', '<?php echo $_SESSION["ReagentAssoc_ID_Description"][$value];?>', '<?php echo $key; ?>');">Add New</SPAN>
		
													<SPAN class="linkExportSequence" style="margin-left:15px; font-weight:normal;" onClick="deleteTableRow('category_assoc_section_<?php echo $reagentType; ?>', '<?php echo $_SESSION["ReagentType_ID_Name"][$reagentType] . "_" . $assocAlias; ?>_assoc_row_<?php echo $key; ?>');">Remove</SPAN><?php

												}

												echo "</td>";

												echo "</tr>";
											}
										}
									}

									?></tr></TABLE><?php

									if (count($children) > 0)
									{
										?><tr>
											<td colspan="2" class="detailedView_parents">
												<i>&nbsp;&nbsp;&nbsp;&nbsp;<b>Children</b></i>
											</td>
										</tr>
		
										<TR>
											<TD COLSPAN="6">
												<TABLE>
													<tr><?php
														$cols = 0;
														$MAX_COLS = 17;
				
														foreach (array_unique($childrenTypes) as $key => $value)
														{
															if ($value == 4)
																echo "<td style=\"padding-left:40px; width:180px; white-space:nowrap\">Cell Line Children</td>";
															else
																echo "<td style=\"padding-left:40px; width:180px; white-space:nowrap\">" . $_SESSION["ReagentType_ID_Name"][$value] . " Children</td>";
														
																?><td width="350px">
																	<SPAN id="show_<?php echo $_SESSION["ReagentType_ID_Name"][$value]; ?>" class="linkShow" onclick="showHideChildren(this.id, '<?php echo $_SESSION["ReagentType_ID_Name"][$value] . "_children"; ?>');">Show Children</SPAN>
							
																	<SPAN id="hide_<?php echo $_SESSION["ReagentType_ID_Name"][$value]; ?>" class="linkHide" onclick="showHideChildren(this.id, '<?php echo $_SESSION["ReagentType_ID_Name"][$value] . "_children"; ?>');">Hide Children</SPAN>
																	&nbsp;&nbsp;<B>(<?php echo $num_children[$value]; ?>&nbsp; total)</B>
																</td>
															</tr>
						
															<tr id="<?php echo $_SESSION["ReagentType_ID_Name"][$value] . "_children"; ?>" style="display:none;">
																<td colspan="5">
																	<div class="children">
																		<TABLE class="children">
																			<TR><?php

																			foreach ($children as $aPropID => $childrenIDs)
																			{
																				foreach ($childrenIDs as $cKey => $childID)
																				{
																					$childTypeID = $gfunc_obj->getTypeID($childID);
									
																					if ($childTypeID == $value)
																					{
																						if ($cols == $MAX_COLS)
																						{
																							echo "</tr><tr>";
																							$cols = 0;
																						}
				
																						echo "<td>";

																						$tmpProject = getReagentProjectID($value);
																						
																						if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($tmpProject, $userProjects))
																						{
																							echo "<a href=\"Reagent.php?View=6&rid=" . $childID . "\">" . $gfunc_obj->getConvertedID_rid($childID) . "</a>&nbsp;&nbsp;";
																						}
																						else
																						{
																							echo "<span class=\"linkDisabled\">" . $gfunc_obj->getConvertedID_rid($childID) . "</span>&nbsp;&nbsp;";
																						}

																						echo "</td>";
																						$cols++;
																					}
																				}
																			}

																			?></TR>
																		</TABLE>
																	</DIV>
																</td>
															</tr><?php
														}
					
													?></tr>
												</TABLE>
											</TD>
										</TR><?php
									}	// end if has children
								?></table><?php
							break;
						}
					?></td>
				</tr>

				<!-- Hidden error messages -->
				<tr>
					<td colspan="<?php echo $colspan; ?>">
		
						<SPAN style="color:#FF0000; font-weight:bold; display:<?php echo (isset($_GET["ErrCode"]) && (strcasecmp($_GET["ErrCode"], '6') == 0)) ? "table-row" : "none"; ?>">Vector sequence cannot be reconstructed using the restriction sites and parents provided.  Please verify restriction sites and/or parent input values.</SPAN>
		
						<SPAN style="color:#FF0000; font-weight:bold; display:<?php echo (isset($_GET["ErrCode"]) && (strcasecmp($_GET["ErrCode"], '8') == 0)) ? "table-row" : "none"; ?>">Unknown sites on Insert.  Please verify your Insert input value.</SPAN>
		
						<SPAN style="color:#FF0000; font-weight:bold; display:<?php echo (isset($_GET["ErrCode"]) && (strcasecmp($_GET["ErrCode"], '9') == 0)) ? "table-row" : "none"; ?>">Sequence generation failed: Restriction sites could not be found on Parent Vector sequence.  Please verify your input values.</SPAN>
		
						<SPAN style="color:#FF0000; font-weight:bold; display:<?php echo (isset($_GET["ErrCode"]) && (strcasecmp($_GET["ErrCode"], '10') == 0)) ? "table-row" : "none"; ?>">Sequence generation failed: Insert sites found more than once in Parent Vector sequence.  Please verify your parent input values.</SPAN>
		
						<SPAN style="color:#FF0000; font-weight:bold; display:<?php echo (isset($_GET["ErrCode"]) && (strcasecmp($_GET["ErrCode"], '11') == 0)) ? "table-row" : "none"; ?>">Sequence generation failed: Restriction sites cannot be hybridized.  Please verify your restriction sites and/or parent input values.</SPAN>
		
						<SPAN style="color:#FF0000; font-weight:bold; display:<?php echo (isset($_GET["ErrCode"]) && (strcasecmp($_GET["ErrCode"], '12') == 0)) ? "table-row" : "none"; ?>">Sequence generation failed: 5' site occurs after 3' restriction site on Parent Vector sequence.  Please verify your restriction sites and/or parent input values.</SPAN>
					</td>
				</tr>
			</table><?php

		echo "</FORM>";

		?>
			<!-- March 23/09: Export reagent info in GenBank format -->
			<form id="export_genbank_form" name="genbank_export" action="Reagent/genBank.php" method="POST">
				<input type="hidden" name="filename" value="<?php echo $limsID; ?>"/>
				<input type="hidden" id="gbk_content" name="gbkContent" value="<?php echo $gbk_content; ?>" />
			</form>
		<?php
	}
}
?>
