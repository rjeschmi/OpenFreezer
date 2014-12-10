<title>Search Page</title>
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
* @package Search
*
* @copyright  2005-2010 Pawson Laboratory
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* functions
*/
// currentSet can be: Generic OR Advanced
function searchHeader($currentSet)
{
?>
<td>
		<center>
		<a href="../index.php">OpenFreezer Home</a>
		<?php
		echo "<a href=\"search.php?View=" . $currentSet . "\">" . $currentSet . " Search </a>";
		echo "</center>\n";
			
		echo "<FORM METHOD=POST ACTION=\"search.php?Type=" . $currentSet . "\">";
		?>
	</center>
	</td>
	<?php
}

function outputSubMenu($typeOfMenu)
{
	if ($typeOfMenu == "1")
	{
		searchNavForm( "1", "False" );
	}
}

	// Sept 1, 2006, Marina -- Replace search view
	function searchNavForm($currentSet, $isAdvanced)
	{
		global $conn;
	
		$searchOptions[0] = "All";
		$searchOptions[1] = "OpenFreezer ID";
		$searchOptions[3] = "Entrez Gene ID";
		$searchOptions[4] = "Ensembl Gene ID";
		$searchOptions[5] = "Gene Symbol";
		$searchOptions[6] = "Name";
		$searchOptions[7] = "Accession Number";
		$searchOptions[8] = "Protein Sequence";
		$searchOptions[9] = "DNA Sequence";
	
		# internal option values for the categories list
		$searchOptionValues[0] = "all";
		$searchOptionValues[1] = "lims id";
		$searchOptionValues[3] = "entrez gene id";
		$searchOptionValues[4] = "ensembl gene id";
		$searchOptionValues[5] = "official gene symbol";
		$searchOptionValues[6] = "name";
		$searchOptionValues[7] = "accession number";
	
		$propIDs = array();
	
		foreach ($searchOptionValues as $key=>$val)
		{
			$props_rs = mysql_query("SELECT `propertyID` FROM `ReagentPropType_tbl` WHERE `propertyName`='" . $val . "'", $conn) or die("Could not select properties: " . mysql_error());
	
			while ($props_ar = mysql_fetch_array($props_rs, MYSQL_ASSOC))
			{
				// Feb. 28/10: temporary hack for now; will change as search gets more sophisticated
				// determine the category of each of the above properties to output in summary table
				/*
				include "Reagent/Reagent_Function_Class";*/

				$rfunc_obj = new Reagent_Function_Class();

				switch ($val)
				{
					case 'name':
						$category = "General Properties";
					break;

					case 'entrez gene id':
					case 'ensembl gene id':
					case 'official gene symbol':
					case 'accession number':
						$category = "External Identifiers";
					break;
				}

				$propCatID = $rfunc_obj->getPropertyIDInCategory($props_ar["propertyID"], $_SESSION["ReagentPropCategory_Name_ID"][$category]);
				$propIDs[$val] = $propCatID;
			}
		}

		mysql_free_result($props_rs);
		unset($props_ar);

		$mult_rTypes_checked = "";
		$mult_packets_checked = "";
		$rTypesMultiple = "";

		?>
			<form name="search_reagents" method="POST" action="<?php echo $_SERVER["PHP_SELF"] . "?View=1";?>">
				<table width="790px">
					<th colspan="2" style="font-size:13pt; text-align:center; font-weight:bold; color:#0000FF; white-space:nowrap; padding-bottom:10px; padding-top:5px; border-bottom: 1px solid black;">
						REAGENT SEARCH PAGE
					</th>

					<tr>
						<td colspan="2" style="padding-top:10px; padding-bottom:10px; border-bottom: 1px solid black;">
							<table width="790px" cellpadding="6" cellspacing="4" border="0">
								<th colspan="6" style="font-size:10pt; text-align:left; font-weight:bold; color:#1A08F2; white-space:nowrap; padding-top: 10px; padding-top:5px;">
									Optional Search Filters:
								</th>

								<tr>
									<td colspan="2" style="vertical-align:middle; white-space:nowrap; padding-top:10px; padding-left:9px;"><IMG SRC="pictures/bullet.gif" style="vertical-align:middle; padding-left:0; padding-top:0; padding-right:0; padding-bottom:2px;">Reagent type:</td>
								
									<td colspan="2" style="padding-left:10px;"><?php

										// Modified May 13/09: Since we're now adding new reagent types, show Reagent type selection in a list instead of es
										if (isset($_POST["multiple_reagent_types"]))
											$rTypesMultiple = "MULTIPLE";

										echo "<SELECT NAME=\"filter[]\" " . $rTypesMultiple . " ID=\"reagentTypesFilterList\">";

										if (!isset($_POST["filter"]))
											echo "<OPTION VALUE=\"default\" SELECTED>All</OPTION>";
										else
											echo "<OPTION VALUE=\"default\">All</OPTION>";

										foreach ($_SESSION["ReagentType_Name_ID"] as $Rname => $id)
										{
											$selected = "";

											if (strcasecmp($Rname, 'CellLine') == 0)
												$Rname = "Cell Line";
											
											if (isset($_POST["filter"]) && in_array($Rname, $_POST["filter"]))
												$selected = "SELECTED";

											echo "<OPTION VALUE=\"" . $Rname . "\" " . $selected . ">". $Rname . "</OPTION>";
										}

										echo "</SELECT>";

										if (isset($_POST["multiple_reagent_types"]))
											$mult_rTypes_checked = "checked";

										?><input type="checkbox" style="margin-left:10px;" id="searchMultipleReagentTypesCheckbox" name="multiple_reagent_types" <?php echo $mult_rTypes_checked; ?> onClick="searchMultipleReagentTypes();"> Select multiple reagent types
									</td>
								</tr>
							
								<!-- Added July 16/07: Add Project filter - Beginning of a biomart-like search interface -->
								<tr>
									<td colspan="2" style="white-space:nowrap; padding-top:20px; padding-left:9px;"><IMG SRC="pictures/bullet.gif" style="vertical-align:middle; padding-left:0; padding-top:0; padding-right:0; padding-bottom:2px;">Project:</td>

									<td colspan="3" style="padding-left:10px; padding-top:10px; vertical-align:middle;">
										<?php
											// Need to think about this more, as there can be multiple project filter values
											if (isset($_POST["multiple_packets"]))
											{
												$mult_packets_checked = "checked";
												printSearchPackets(1, "", 1);
											}
											else
												printSearchPackets(0, "", 1)
										?>
										<input type="checkbox" onClick="searchMultipleProjects();" id="searchMultiple" name="multiple_packets" <?php echo $mult_packets_checked; ?>> Select multiple projects
									</td>
								</tr>
							
								<tr>
									<td colspan="2" style="white-space:nowrap; padding-top:10px; padding-left:9px;"><IMG SRC="pictures/bullet.gif" style="vertical-align:middle; padding-left:0; padding-top:0; padding-right:0; padding-bottom:2px;">Property type:</td>
								
									<td colspan="2" style="padding-left:10px;">
										<select id="search_by" name="SearchArea" onChange="checkOutputType()"><?php
											foreach ($searchOptionValues as $key=>$val)
											{
												echo "<option value=\"" . $val . "\"";
											
												if (isset($_POST["SearchArea"]))
												{
													$searchCategory = $_POST["SearchArea"];

													if (strcasecmp($searchCategory, $val) == 0)
													{
														echo "selected";
													}
												}
											
												echo ">";
												echo $searchOptions[$key] . "</option>";
											}
										?></select>
									</td>
								</tr>

								<!-- April 27, 2011 -->
								<TR>
									<TD colspan="4" style="padding-left:20px; font-size:9pt;">
										<input type="checkbox" name="gs_only"
										<?php 
											if ( ($_POST["gs_only"] == "on") )
												echo "checked";
										?>
										>&nbsp;Search Glycerol Stocks only (find clones available for order)
										<IMG SRC="pictures/new01.gif" ALT="new" WIDTH="35" HEIGHT="20" style="cursor:auto">
									</TD>
								</TR>
							</table>
						</td>
					</tr>
					
					<tr>
						<td>
							<table width="790px" cellpadding="6" cellspacing="4" border="0" style="display:inline" id="show_search" cellpadding="4">
								<tr>
									<th colspan="2" style="font-size:10pt; text-align:left; font-weight:bold; color:#1A08F2; white-space:nowrap; padding-right:15px; padding-top:5px;">
										Enter search term:
									</td>
								
									<td style="padding-left:0;" colspan="2">
										<?php
											echo "<INPUT TYPE=\"text\" id=\"search_term_id\" name=\"Keyword\" value=\"";

											if (isset($_POST["Keyword"]))
											{
												echo stripslashes($_POST["Keyword"])  . "\"";		// 26/9/06
											}
											else
											{
												echo "\"";
											}
										?>
									</td>
	
									<td>
										<INPUT TYPE="submit" name="search_submit" value="Search">
									</td>

									<!-- Added April 16/07 -->
									<td>
										<input type="checkbox" name="exact_match"
										<?php 
											if ( ($_POST["exact_match"] == "on") )
												echo "checked";
										?>
										>&nbsp;Exact match</INPUT><BR>

										<input type="checkbox" name="search_completed"
										<?php 
											if ( ($_POST["search_completed"] == "on") )
												echo "checked";
										?>
										>&nbsp;Show completed reagents only
									</td>
								</tr>
							</table>
	
							<table width="100%" border="0" id="show_protein" style="display:none">
								<tr>
									<td colspan="3" style="font-size:12px" nowrap>
										Please paste your PROTEIN sequence here:
									</td>
								</tr>
				
								<tr>
									<td colspan="3">
										<textarea wrap="virtual" name="prot_sequence" rows="5" cols="45"></textarea>
									</td>
	
									<td>
										<INPUT TYPE="submit" name="search_submit" value="Search">
									</td>
								</tr>
							</table>
		
							<table width="100%" border="0" id="show_dna" style="display:none">
								<tr>
									<td colspan="3" style="font-size:12px" nowrap>
										Please paste your DNA sequence here:
									</td>
								</tr>
				
								<tr>
									<td colspan="3">
										<textarea wrap="virtual" name="dna_sequence" rows="5" cols="45"></textarea>
									</td>
	
									<td>
										<INPUT TYPE="submit" name="search_submit" value="Search">
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</tbody>
				</table>
			</form>
		<?php
	}
	?>
	<script language="javascript">
	
		function checkOutputType()
		{
			searchBoxElem = document.getElementById("search_by");
			showSearchElem = document.getElementById("show_search");
			showProteinElem = document.getElementById("show_protein");
			showDNAElem = document.getElementById("show_dna");
			searchTermElem = document.getElementById("search_term_id");

			var myindex  = searchBoxElem.selectedIndex
			var selValue = searchBoxElem.options[myindex].value
	
			if (selValue == "protein sequence")
			{
				showProteinElem.style.display="inline";
				showSearchElem.style.display="none";
				showDNAElem.style.display="none";
			}
			else if (selValue == "sequence")
			{
				showDNAElem.style.display="inline";
				showSearchElem.style.display="none";
				showProteinElem.style.display="none";
			}
			else
			{
				showSearchElem.style.display="inline";
				showProteinElem.style.display="none";
				showDNAElem.style.display="none";
			}

			searchTermElem.focus();
		}
	
	</script>
	<?php

function submit_changes( $foundValues, $oldValues, $reagentIDToView )
{
	global $conn; 
	$type_rs = mysql_query( "SELECT `reagentTypeID` FROM `Reagents_tbl` WHERE `status`='ACTIVE' AND `reagentID`='" 
							. $reagentIDToView . "'", $conn ) ;
	
	if( $type_ar = mysql_fetch_array( $type_rs, MYSQL_ASSOC ) )
	{
		switch( $type_ar["reagentTypeID"] )
		{
			case $_SESSION["ReagentType_Name_ID"]["Vector"]:
				check_New_VectorInfo( $foundValues, $oldValues, $reagentIDToView );
				break;
			case $_SESSION["ReagentType_Name_ID"]["Insert"]:
				check_New_InsertInfo( $foundValues, $oldValues, $reagentIDToView );
				break;
			case $_SESSION["ReagentType_Name_ID"]["Oligo"]:
				check_New_OligoInfo( $foundValues, $oldValues, $reagentIDToView );
				break;
			case $_SESSION["ReagentType_Name_ID"]["CellLine"]:
				check_New_CellLineInfo( $foundValues, $oldValues, $reagentIDToView );
				break;
		}
	}
}

function check_New_VectorInfo( $foundValues, $oldValues, $rid )
{
	global $conn;
	
	// founc values should match up with:
	// 0: Name
	// 1: Vector type
	// 2: 5' Cloning Site
	// 3: 3' Cloning Site
	// 4: Description
	// 5: Comments
	// 6: Tag Type
	// 7: Tag Position
	// 8: Antibiotic Resistance
	// 9: Reagent Source
	// 10: Status
	echo "Found values: ";
	print_r( $foundValues );
	echo "<br>";
	$expectedTypes_ar = array();
	$expectedTypes_ar[] = "Name";
	$expectedTypes_ar[] = "Vector Type";
	$expectedTypes_ar[] = "5' cloning site";
	$expectedTypes_ar[] = "3' cloning site";
	$expectedTypes_ar[] = "Description";
	$expectedTypes_ar[] = "Comments";
	$expectedTypes_ar[] = "Tag";
	$expectedTypes_ar[] = "Tag Position";
	$expectedTypes_ar[] = "Antibiotic Resistance";
	$expectedTypes_ar[] = "Reagent Source";
	$expectedTypes_ar[] = "Status";
	
	foreach( $expectedTypes_ar as $key => $value )
	{
		
		if( strlen( trim( $foundValues[ $key ] ) ) > 0 )
			{
				if( strlen( $oldValues[ $_SESSION["ReagentProp_Name_ID"][ $value ] ] ) > 0 )
				{
					// Updating found property!
					
					if( specialCol( $value, $foundValues[ $key ] ) )
					{
						updateViewInfo( $rid,  $value , $foundValues[ $key ], "UPDATEVALUE" );
					}
					else
					{
						mysql_query( "UPDATE `ReagentPropList_tbl` SET `propertyValue`='" . $foundValues[ $key ] . "' WHERE `reagentID`='" . $rid . "'" .
									" AND `propertyID`='" . $_SESSION["ReagentProp_Name_ID"][ $value ] . "'"
									. " AND `status`='ACTIVE'", $conn ) or die("Query failed : " . mysql_error());
					
					}
				}
				else
				{
					// Property didn't exist before!!
					if( specialCol( $value, $foundValues[ $key ] ) )
					{
						updateViewInfo( $rid,  $value , $foundValues[ $key ], "NEWVALUE" );
					}
					else
					{
						mysql_query( "INSERT INTO `ReagentPropList_tbl` (`propListID`, `reagentID`, `propertyID`, `propertyValue`) VALUES ('', '" . 
									$rid . "', '" . $_SESSION["ReagentProp_Name_ID"][ $value ] . "', '" . $foundValues[ $key ] 
									. "')" , $conn ) or die("Query failed : " . mysql_error());
					}
				}
			}
			else
			{
				// Delete old property?
				
			}
		
	}
	
}

function check_New_InsertInfo( $foundValues, $oldValues, $rid )
{
	global $conn;
	// found values should match up with:
	// 0: Name
	// 1: 5' Cloning Site
	// 2: 3' Cloning Site
	// 3: 5' Linker
	// 4: 3' Linker
	// 5: Comments
	// 6: Status
	
	$expectedTypes_ar = array();
	$expectedTypes_ar[] = "Name";
	$expectedTypes_ar[] = "5' cloning site";
	$expectedTypes_ar[] = "3' cloning site";
	$expectedTypes_ar[] = "5' Linker";
	$expectedTypes_ar[] = "3' Linker";
	$expectedTypes_ar[] = "Comments";
	$expectedTypes_ar[] = "Status";
	//print_r( $_SESSION["ReagentProp_Name_ID"]);
	foreach( $expectedTypes_ar as $key => $value )
	{
		
		if( strlen( trim( $foundValues[ $key ] ) ) > 0 )
			{
				if( strlen( $oldValues[ $_SESSION["ReagentProp_Name_ID"][ $value ] ] ) > 0 )
				{
					// Updating found property!
					
					if( specialCol( $value, $foundValues[ $key ] ) )
					{
						updateViewInfo( $rid,  $value , $foundValues[ $key ], "UPDATEVALUE" );
					}
					else
					{
						mysql_query( "UPDATE `ReagentPropList_tbl` SET `propertyValue`='" . $foundValues[ $key ] . "' WHERE `reagentID`='" . $rid . "'" .
									" AND `propertyID`='" . $_SESSION["ReagentProp_Name_ID"][ $value ] . "'"
									. " AND `status`='ACTIVE'", $conn ) or die("Query failed : " . mysql_error());
					
					}
				}
				else
				{
					// Property didn't exist before!!
					if( specialCol( $value, $foundValues[ $key ] ) )
					{
						updateViewInfo( $rid, $value , $foundValues[ $key ], "NEWVALUE" );
					}
					else
					{
						mysql_query( "INSERT INTO `ReagentPropList_tbl` (`propListID`, `reagentID`, `propertyID`, `propertyValue`) VALUES ('', '" . 
									$rid . "', '" . $_SESSION["ReagentProp_Name_ID"][ $value ] . "', '" . $foundValues[ $key ] 
									. "')", $conn  ) or die("Query failed : " . mysql_error());
					}
				}
			}
			else
			{
				// Delete old property?
				
			}
		
	}
}

function updateViewInfo( $rid, $propName, $value, $update_type )
{
	global $conn;
	
	echo "IN the func: " . $propName;
	switch( $propName )
	{
		
		case "Comments":		
		case "Description":
			echo "IN HERE: " . $propName . "<br>";
			switch( $update_type )
			{
				case "UPDATEVALUE":
					$find_old_comment_rs = mysql_query( "SELECT `propertyValue` FROM `ReagentPropList_tbl` WHERE `status`='ACTIVE' "
														. " AND `reagentID`='" . $rid . "'"
														. " AND `propertyID`='" . $_SESSION["ReagentProp_Name_ID"][ $propName ] . "'"
														, $conn );
														
					if( $find_old_comment_ar = mysql_fetch_array( $find_old_comment_rs, MYSQL_ASSOC ) )
					{
						mysql_query( "UPDATE `GeneralComments_tbl` SET `comment`='" . $value . "'"
									. " WHERE `status`='ACTIVE'" 
									. " AND `commentID`='" . $find_old_comment_ar["propertyValue"] . "'", $conn ) 
									or die("Query failed : " . mysql_error());
					}
					break;
				case "NEWVALUE":
					$get_comment_type_rs = mysql_query( "SELECT `commentLinkID` FROM `CommentLink_tbl` WHERE `Link`='Reagent' "
														. " AND `status`='ACTIVE'", $conn ) or die( "Query Failed: " . mysql_error() );
														
					$get_comment_type_ar = mysql_fetch_array( $get_comment_type_rs, MYSQL_ASSOC );
				
					mysql_query( "INSERT INTO `GeneralComments_tbl` (`commentID`, `commentLinkID`, `comment`) VALUES ('', '"
								. $get_comment_type_ar["commentLinkID"] . "',"
								. "'" . $value . "')", $conn ) or die("Query failed: " . mysql_error() );
								
					mysql_query( "INSERT INTO `ReagentPropList_tbl` (`propListID`, `reagentID`, `propertyID`, `propertyValue`) VALUES ('', '" 
								. $rid . "','" . $_SESSION["ReagentProp_Name_ID"][ $propName ] . "','" . mysql_insert_id() 
								. "')" , $conn );
								
					
					break;
			}
			break;
	}
	
}

function specialCol( $tocheck_colName, $colvalue )
{
	if( ( $tocheck_colName == "Description" || $tocheck_colName == "Comments" || $tocheck_colName == "Comment" ) )
	{
		return true;
	}
	elseif( $tocheck_colName == "Sequence" && $colvalue != "" && $colvalue != "KARENFILLTHIS")
	{
		return true;
	}
	elseif( $tocheck_colName == "Length" )
	{
		return true;
	}
	elseif( $tocheck_colName == "Open/Closed" )
	{
		return true;
	}
	
	return false;
}
?>
