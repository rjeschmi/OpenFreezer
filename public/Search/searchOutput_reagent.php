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
* Include/require statements
*/
// include "Views/DetailReagent_View.php";

// Modified April 11/07, Marina: Pass SearchArea and Keyword as parameters
/**
* Output search results in a table
*
* @param STRING searchArea
* @param STRING keyword
*
* @author John Paul Lee @version 2005
* @author Marina Olhovsky @version 3.1
*/
function searchOutputResults($searchArea, $keyword)
{
	global $conn;

	$gfunc_obj = new generalFunc_Class();
	$lfunc_obj = new Location_Funct_Class();	// April 27, 2011

	$reagentSearch_rs = searchReagentProperties($searchArea, $keyword);

	$rfunc_obj = new Reagent_Function_Class();	// March 1, 2010
	
	// June 21/07: Restrict viewing reagents by project
	$userID = $_SESSION["userinfo"]->getUserID();
	$userCategory = $_SESSION["userinfo"]->getCategory();

	if ($userCategory == 1)
		$userProjectIDs = findAllProjects();
	else
		$userProjectIDs = getUserProjectIDs($userID);

	// allow everyone to view public projects
	$publicProjects = getPublicProjects();

	foreach ($publicProjects as $key => $project)
	{
		$pID = $project->getPacketID();
		$userProjectIDs[] = $pID;
	}

	$userProjectIDs = array_unique($userProjectIDs);

	echo "<TABLE>";

	if (isset($_POST["packets"]) && ($_POST["packets"][0] != 0))
	{
		$packetFilter = array();

		foreach ($_POST["packets"] as $pID => $packet)
		{
			$packetFilter[] = $packet;
		}

		while($temp_ar = mysql_fetch_array($reagentSearch_rs, MYSQL_ASSOC))
	//	foreach ($tmp_search_rs_new as $tKey => $temp_ar)
		{
		//	print_r($temp_ar);

			// returned reagentID, reagentTypeID and groupID
			$tmp_rID = $temp_ar["reagentID"];

			$tmp_rType = $temp_ar["reagentTypeID"];
			$tmp_gID = $temp_ar["groupID"];
			
			// Select the project ID for the reagent and check if it's in the project filter
			$tmp_pID = getReagentProjectID($tmp_rID);

			if (in_array($tmp_pID, array_values($packetFilter)))
			{
				$reagentProjectID = getReagentProjectID($temp_ar["reagentID"]);

				if ( (in_array($reagentProjectID, $userProjectIDs) && (sizeof($userProjectIDs) > 0)) || ($userCategory == $_SESSION["userCategoryNames"]["Admin"]) )
				{
					if ($temp_ar["reagentTypeID"] != $lastReagentType)
					{
						// For each reagent found, find the properties associated with its reagent type
						$propToOutput_ar = initializeDefaultOutput($temp_ar["reagentTypeID"]);
						$alias_ar = makeAliases($propToOutput_ar);

						$propToOutput_str = "('";
						$prop_count = 0;

						// Creates the array that will hold all the data for the given reagent type
						$propOrderArray_ar = array_flip($propToOutput_ar);

						foreach ($propOrderArray_ar as $key => $value)
						{
							$propOrderArray_ar[$key] = "";
						}

						foreach($propToOutput_ar as $i => $value)
						{
							if (in_array($value, $_SESSION["ReagentProp_ID_Name"]))
							{
								$propToOutput_str = $propToOutput_str . $_SESSION["ReagentProp_Name_ID"][$value] . "','";
								$prop_count++;
							}
						}

						if( $prop_count > 0 )
						{
							$propToOutput_str = substr( $propToOutput_str, 0, strlen( $propToOutput_str ) - 2 ) . ")";
						}
						else
						{
							$propToOutput_str = "('')";
						}

						outputHeader($propOrderArray_ar, $firstHeader);
						$lastReagentType = $temp_ar["reagentTypeID"];
					}

					$previewInfo_tmp = findoutputReagent_specific($propToOutput_str, $propOrderArray_ar, $temp_ar["reagentID"], $temp_ar["reagentTypeID"], $temp_ar["groupID"]);

					outputReagentPreview( $temp_ar["reagentID"], $gfunc_obj->getConvertedID( $temp_ar["reagentTypeID"], $temp_ar["groupID"]), $previewInfo_tmp, "", "" );

					$numHits++;	// june 21/07
				}
			}
		}

//		mysql_free_result($tmp_search_rs);
		unset($propOrderArray_ar);
		unset($userProjectIDs);
		unset($previewInfo_tmp);
		unset($temp_ar);
	}
	else
	{
		// removed June 21/07 - show number of rows user can actually view
//		echo "Number of hits: " . mysql_num_rows( $reagentSearch_rs ) . "<BR>";
		$numHits = 0;	// june 21/07

		?>
		<link href="styles/SearchStyle.css" rel="stylesheet" type="text/css">
		<center>
		<?php
			$lastReagentType = -1;
			$firstHeader = false;

//				foreach ($tmp_search_rs_new as $rKey => $temp_ar)
				while( $temp_ar = mysql_fetch_array( $reagentSearch_rs, MYSQL_ASSOC ) ) // returns reagentID, reagentTypeID and groupID
				{
					$reagentProjectID = getReagentProjectID($temp_ar["reagentID"]);

					if ((in_array($reagentProjectID, $userProjectIDs) && (sizeof($userProjectIDs) > 0)) || ($userCategory == $_SESSION["userCategoryNames"]["Admin"]))
					{
						if( $temp_ar["reagentTypeID"] != $lastReagentType )
						{
							// For each reagent found, find the properties associated with its reagent type
							$propToOutput_ar = initializeDefaultOutput($temp_ar["reagentTypeID"]);
							$alias_ar = makeAliases($propToOutput_ar);

							$propToOutput_str = "('";
							$prop_count = 0;

							// Creates the array that will hold all the data for the given reagent type
							$propOrderArray_ar = array_flip($propToOutput_ar);

							foreach ($propOrderArray_ar as $key => $value)
							{
								$propOrderArray_ar[$key] = "";
							}

							foreach($propToOutput_ar as $i => $value)
							{
								// March 1, 2010
								$propID = $_SESSION["ReagentProp_Name_ID"][$value];
						
								switch ($value)
								{
									case 'name':
									case 'packet id':
							
									// display type for novel reagent types
									case strtolower($_SESSION["ReagentType_ID_Name"][$temp_ar["reagentTypeID"]]) . ' type':
									case 'type':
										$category = "General Properties";
									break;
						
									case 'entrez gene id':
									case 'ensembl gene id':
									case 'official gene symbol':
									case 'accession number':
										$category = "External Identifiers";
									break;
								}
						
								$propCatID_tmp = $rfunc_obj->getPropertyIDInCategory($propID, $_SESSION["ReagentPropCategory_Name_ID"][$category]);
								
								if ($propCatID_tmp > 0)
								{
									$propToOutput_str = $propToOutput_str . $propCatID_tmp . "','";
									$prop_count++;
								}
							}

							if( $prop_count > 0 )
							{
								$propToOutput_str = substr( $propToOutput_str, 0, strlen( $propToOutput_str ) - 2 ) . ")";
							}
							else
							{
								$propToOutput_str = "('')";
							}

							outputHeader($propOrderArray_ar, $firstHeader);
							$lastReagentType = $temp_ar["reagentTypeID"];
						}

						$previewInfo_tmp = findoutputReagent_specific($propToOutput_str, $propOrderArray_ar, $temp_ar["reagentID"], $temp_ar["reagentTypeID"], $temp_ar["groupID"]);

						outputReagentPreview($temp_ar["reagentID"], $gfunc_obj->getConvertedID($temp_ar["reagentTypeID"], $temp_ar["groupID"]), $previewInfo_tmp, "", "");

						$numHits++;	// june 21/07
					}
				}
			?>
			</table>
		</center>
		<?php
		mysql_free_result($reagentSearch_rs);
//		mysql_free_result($tmp_search_rs_new);
		unset($propToOutput_ar);
		unset($previewInfo_tmp);
		unset($temp_ar);
	}

	// March 19/10: moved this into search.php to print the number of hits at the bottom of the list, not after every reagent type
// 	echo "Number of hits: " . $numHits . "<BR>";
	unset($reagentSearch_rs);

	// June 5/09
	return $numHits;
}


# May 22/07, Marina
# For every property name in $names_ar, select its alias from ReagentPropType_tbl
/**
* Generate a map of property names to their aliases
*
* @param Array names_ar
*
* @author John Paul Lee @version 2005
* @author Marina Olhovsky @version 3.1
*/
function makeAliases($names_ar)
{
	global $conn;
	
	$aliases = array();
	
	foreach ($names_ar as $key=>$value)
	{
		$alias_rs = mysql_query("SELECT `propertyAlias` FROM `ReagentPropType_tbl` WHERE `propertyName`='" . $value . "'");
		
		if ($alias_ar = mysql_fetch_array($alias_rs, MYSQL_ASSOC))
		{
			$aliases[] = $alias_ar["propertyAlias"];
		}
	}
	
	mysql_free_result($alias_rs);
	mysql_close();
	
	unset($alias_ar);
	
	return $aliases;
}

# May 22/07, Marina
/**
* @deprecated
*/
function makeTempTable($tableCols)
{
	global $conn;
	
	$numFields = sizeof($tableCols);
	$count = 1;
	
	$query = "CREATE TABLE TEMP_SEARCH_PROPS(";
	
	
	foreach ($tableCols as $key=>$value)
	{
		if ($count < $numFields)
		{
			$query .= $value . " VARCHAR(250), ";
			$count++;
		}
		else
		{
			$query .= $value . " VARCHAR(250)";
		}
	}
	
	$query .= ")";

	mysql_query($query, $conn) or die("Cannot create temp table: " . mysql_error());
}


/**
* Output table column headers for the search results table
*
* @param Array previewHeaderInfo
* @param boolean testHeader
*
* @author John Paul Lee @version 2005
* @author Marina Olhovsky @version 3.1
*/
function outputHeader($previewHeaderInfo, $testHeader)
{
	global $Const_Table_Width;

	if( !$testHeader )
	{
		echo "</table>";
	}

	echo "<table width=\"770px\" class=\"preview\">";

	echo "<tr>";
	
	// added June 5/09
	echo "<td class=\"searchCheckbox\">";
		echo "<input type=\"checkbox\" ID=\"checkAllSearch\" onClick=\"(!this.checked) ? uncheckAll('ReagentCheckBox', []) : checkAll('ReagentCheckBox'); \" value=\"default\">";
	echo "</td>";
	
	# May 22/07, Marina: Add sort option
	if (isset($_POST["SearchArea"]) && isset($_POST["Keyword"]))
	{
		$searchCategory = $_POST["SearchArea"];
		$keyword = trim($_POST["Keyword"]);
	}
	elseif (isset($_GET["SearchArea"]) && isset($_GET["Keyword"]))
	{
		$searchCategory = trim($_GET["SearchArea"]);
		$keyword = trim($_GET["Keyword"]);
	}
		
	// output a header column for the LIMS ID
	echo "<th name=\"lims_id\" scope=\"col\" class=\"searchHeader\"><a href=\"" . $_SERVER["PHP_SELF"] . "?View=1&SearchArea=" . $searchCategory . "&Keyword=" . $keyword . "&sortBy=lims_id\">OpenFreezer ID</a></th>";
	
	// Step 1: Output headers for each main element property names
	// June 22/07: Property names and header values may vary slightly (e.g. for property 'packet id' output heading 'Project ID')
	foreach ($previewHeaderInfo as $key => $value)
	{
		switch ($key)
		{
			case 'packet id':
				echo "<th scope=\"col\" name=\"" . $key . "\" class=\"searchHeader\"> <a href=\"" . $_SERVER["PHP_SELF"] . "?View=1&SearchArea=" . $searchCategory . "&Keyword=" . $keyword . "&sortBy=" . $key . "\">Project #</a></th>";
			
			break;
			
			default:
				echo "<th scope=\"col\" name=\"" . $key . "\" class=\"searchHeader\"> <a href=\"" . $_SERVER["PHP_SELF"] . "?View=1&SearchArea=" . $searchCategory . "&Keyword=" . $keyword . "&sortBy=" . $key . "\">" . $key . "</a></th>";
	
			break;
		}
	}
}


/**
* Search the reagent properties (not including description or LIMS IDs) for a given property.
*
* Will also take into account the filters set for the current search.
*
* NOTE: If no filter has been set, it will search for every reagent!
*
* @param STRING searchArea
* @param STRING keyword
*
* @author John Paul Lee @version 2005
* @author Marina Olhovsky @version 3.1
*/
function searchReagentProperties($searchArea, $keyword)
{
	global $conn;

	$gfunc_obj = new generalFunc_Class();

	# Sept 26/06 -- Account for escape sequence
	$rfunc = new Reagent_Function_Class();

	if (!get_magic_quotes_gpc())
	{
		$keyword = $rfunc->addQuotes($keyword);
	}

	// September 6, 2006, Marina, modified April 11/07
	# Hack: copying internal option values for the categories list here, since cannot get them all from POST - needs to be fixed
	$searchOptionValues[0] = "all";
	$searchOptionValues[1] = "entrez gene id";
	$searchOptionValues[2] = "packet id";
	$searchOptionValues[3] = "ensembl gene id";
	$searchOptionValues[4] = "official gene symbol";
	$searchOptionValues[5] = "name";
	$searchOptionValues[6] = "accession number";
	$searchOptionValues[7] = "sequence";

	$searchOptionCategories[1] = "External Identifiers";
	$searchOptionCategories[2] = "General Properties";
	$searchOptionCategories[3] = "External Identifiers";
	$searchOptionCategories[4] = "External Identifiers";
	$searchOptionCategories[5] = "General Properties";
	$searchOptionCategories[6] = "General Properties";
	
	// added April 16/07 - find ID of 'status' property if asked to search for completed clones only
	$searchOptionValues[7] = "status";

	$propIDs = array();

	foreach ($searchOptionValues as $key=>$val)
	{
		$catName = $searchOptionCategories[$key];
		$catID = $_SESSION["ReagentPropCategory_Name_ID"][$catName];

		$props_rs = mysql_query("SELECT propCatID FROM ReagentPropType_tbl t, ReagentPropertyCategories_tbl c WHERE t.propertyName='" . $val . "' AND t.propertyID=c.propID AND t.status='ACTIVE' AND c.status='ACTIVE' ", $conn) or die("Could not select properties: " . mysql_error());

		while ($props_ar = mysql_fetch_array($props_rs, MYSQL_ASSOC))
		{
			$propIDs[$val] = $props_ar["propCatID"];
		}
	}

	mysql_free_result($props_rs);
	unset($props_ar);

	// Modified May 15/09: no more checkboxes!
	$filter_tmp = array();

	$filterSet = "";	// filter by reagent type - V, I, O, C {1,2,3,4}
	
	if (isset($_POST["filter"]))		// if one of the reagent types is selected
	{
		foreach ($_POST["filter"] as $key => $value)
		{
			if (strcasecmp($value, "default") != 0)
			{
				if (strcasecmp($value, "Cell Line") == 0)
				{
					$filter_tmp[] = $_SESSION["ReagentType_Name_ID"]['CellLine'];
				}
				else
				{
					$filter_tmp[] = $_SESSION["ReagentType_Name_ID"][$value];
				}

				$filterSet = "('" . implode( "','", $filter_tmp ) . "')";
			}
			else	// search all reagent types
			{
				$filterSet = "('";
		
				foreach( $_SESSION["ReagentType_Name_ID"] as $key => $value )
				{
					$filterSet = $filterSet . $value . "','";
				}
		
				$filterSet = substr( $filterSet, 0,  strlen( $filterSet ) - 2  ) . ")";
			}
		}
	}
	else	// search all reagent types
	{
		$filterSet = "('";

		foreach( $_SESSION["ReagentType_Name_ID"] as $key => $value )
		{
			$filterSet = $filterSet . $value . "','";
		}

		$filterSet = substr( $filterSet, 0,  strlen( $filterSet ) - 2  ) . ")";
	}

	// September 6, 2006, Marina -- Filter search by categories
	$searchCategory = $searchArea;
	$results = array();

	# Try searching for multiple keywords
// 	$keywords_array = explode(" ", $keyword);
// 	print_r($keywords_array);

	# Find keyword in database, filter by specific category if given
	if (strcasecmp($searchCategory, 'lims id') == 0)
	{
		# 'LIMS ID' property is now obsolete, newly created reagents don't have it.  Special case for searching
		$tempType = $gfunc_obj->get_typeID($keyword);

		# Now, user could omit reagent type altogether, only enter the group ID in the keyword box and use the reagent type filter.  Need to account for that case:
		if ($tempType < 0)
		{
			# Check filter
			foreach ($filter_tmp as $key=>$val)
			{
				$tempType = $val;
				$tempGroup = $keyword;

				if (isset($_POST["exact_match"]))
					$srch = "='" . $tempGroup . "'";
				else
					$srch = "LIKE '%" . $tempGroup . "%'";
				
				// April 16/07: Search for completed reagents only - Added 'if' and 'else' blocks
				if (isset($_POST["search_completed"]))
				{
					$statusPropID = $propIDs["status"];

					if (isset($_POST["gs_only"]))
					{
						$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch . " AND b.`propertyID`= '" . $statusPropID . "' AND b.`propertyValue` LIKE '%completed%' AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
					}
					else
					{
						$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch . " AND b.`propertyID`= '" . $statusPropID . "' AND b.`propertyValue` LIKE '%completed%' AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
					}
				}
				else
				{
					if (isset($_POST["gs_only"]))
					{
						$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch . " AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
					}
					else
					{
						$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch . " AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
					}
				}
			}
		}
		else
		{
			$tempGroup = $gfunc_obj->get_groupID($keyword);

			if (isset($_POST["exact_match"]))
				$srch = "='" . $tempGroup . "'";
			else
				$srch = "LIKE '%" . $tempGroup . "%'";

			// April 16/07: Search for completed reagents only - Added 'if' and 'else' blocks
			if (isset($_POST["search_completed"]))
			{
				$statusPropID = $propIDs["status"];

				if (isset($_POST["gs_only"]))
				{
					$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch . " AND b.`propertyID`= '" . $statusPropID . "' AND b.`propertyValue` LIKE '%completed%' AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
				}
				else
				{
					$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch . " AND b.`propertyID`= '" . $statusPropID . "' AND b.`propertyValue` LIKE '%completed%' AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());		
				}
			}
			else
			{
				if (isset($_POST["gs_only"]))
				{
					$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch . " AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
				}
				else
				{
					$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch . " AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
				}
			}
		}
	}
	else if (strcasecmp($searchCategory, "all") != 0)
	{
		$catPropID = $propIDs[$searchCategory];		// e.g. [selectable marker] => ['58', '635']

		if (isset($_POST["exact_match"]))
			$srch = "='" . trim($keyword) . "'";
		else
			$srch = "LIKE '%" . trim($keyword) . "%'";
	
		// April 16/07: Search for completed reagents only - Added 'if' and 'else' blocks
		if (isset($_POST["search_completed"]))
		{
			$statusPropID = $propIDs["status"];

			if (isset($_POST["gs_only"]))
			{
				$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyID`='" . $catPropID . "' AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
			}
			else
			{
				$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyID`='" . $catPropID . "' AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
			}
		}
		else
		{
			if (isset($_POST["gs_only"]))
			{
				$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyID`='" . $catPropID . "' AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
			}
			else
			{
				$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyID`='" . $catPropID . "' AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
			}
		}
	}
	else	# Search category = 'All'
	{
		# the search term could, once again, be an OF ID
		if ($gfunc_obj->get_rid($keyword) > 0)
		{
// echo "Keyword " . $keyword;

// echo "what is this: " . $gfunc_obj->get_rid($keyword);

			if (isset($_POST["exact_match"]))
				$srch = "='" . trim($keyword) . "'";
			else
				$srch = "LIKE '%" . trim($keyword) . "%'";

			# this is an ID; don't need to check filter, since $gfunc_obj->get_rid($keyword) > 0
			$tempType = $gfunc_obj->get_typeID($keyword);
			$tempGroup = $gfunc_obj->get_groupID($keyword);

			if (isset($_POST["exact_match"]))
				$srch_grp = "='" . trim($tempGroup) . "'";
			else
				// Feb. 17/10: Careful with this - the % in front of the group ID can result in C2 being matched to C12!
				$srch_grp = "LIKE '%" . trim($tempGroup) . "%'";

			// Important correction June 8/09 (done on production too!!): If filterSet is set, $tempType must be in it!!!
			
			// April 16/07: Search for completed reagents only - Added 'if' and 'else' blocks
			if (isset($_POST["search_completed"]))
			{
				$statusPropID = $propIDs["status"];

				if (isset($_POST["gs_only"]))
				{
					$searchResultSet = mysql_query("(SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`reagentTypeID` IN " . $filterSet .  " AND a.`groupID` " . $srch_grp . " AND b.`propertyID`= '" . $statusPropID . "' AND b.`propertyValue` LIKE '%completed%' AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'  AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`) UNION (SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . "  AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
				}
				else
				{
					$searchResultSet = mysql_query("(SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`reagentTypeID` IN " . $filterSet .  " AND a.`groupID` " . $srch_grp . " AND b.`propertyID`= '" . $statusPropID . "' AND b.`propertyValue` LIKE '%completed%' AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`) UNION (SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
				}
			}
			else
			{
				if (isset($_POST["gs_only"]))
				{
					$searchResultSet = mysql_query("(SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`reagentTypeID` IN " . $filterSet .  " AND a.`groupID` " . $srch_grp . " AND a.`reagentID`=b.`reagentID` AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`) UNION (SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') AND a.`status`='ACTIVE' AND b.`status`='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());				
				}
				else
				{
					$searchResultSet = mysql_query("(SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`reagentTypeID` IN " . $filterSet .  " AND a.`groupID` " . $srch_grp . " AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`) UNION (SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
				}
			}
		}
		else	// Search category = 'All', and keyword is not a reagent ID
		{
			if (isset($_POST["exact_match"]))
				$srch = "='" . trim($keyword) . "'";
			else
				$srch = "LIKE '%" . trim($keyword) . "%'";

			# Could be searching for:
			# - a non-existent reagent
			# - a group ID (with filter or without; if w/o, only search for properties)
			# - a property (can restrict to a reagent type)

			# And again, the user might be omitting reagent type from the keyword, OR searching for a partial OF ID
			if (is_numeric($keyword))
//			if (ctype_digit($keyword))	// Replaced Jan. 12/09, b/c is_numeric returns true if the search term is C12orf29
			{
				# this could be a group ID, but it could also be part of a property, so search both

				# Check filter
				if (isset($_POST["filter"]))
				{
					foreach ($filter_tmp as $key=>$val)
					{	
						$tempType = $val;
						$tempGroup = $keyword;

						if (isset($_POST["exact_match"]))
							$srch_grp = "='" . trim($tempGroup) . "'";
						else
							$srch_grp = "LIKE '%" . trim($tempGroup) . "%'";

						
						if (isset($_POST["search_completed"]))
						{
							$statusPropID = $propIDs["status"];
						
							if (isset($_POST["gs_only"]))
							{
								$searchResultSet = mysql_query("(SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch_grp . " AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE') UNION (SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
							else
							{
								$searchResultSet = mysql_query("(SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch_grp . " AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE') UNION (SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
						}
						else
						{
							if (isset($_POST["gs_only"]))
							{
								$searchResultSet = mysql_query("(SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch_grp . " AND a.`reagentID`=b.`reagentID` AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') AND a.`status`='ACTIVE' AND b.`status`='ACTIVE') UNION (SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') AND a.`status`='ACTIVE' AND b.`status`='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
							else
							{
								$searchResultSet = mysql_query("(SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch_grp . " AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE') UNION (SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
						}
					}
				}
				else
				{
					# only search properties, but check filter first
					if (isset($_POST["filter"]))
					{
						if (isset($_POST["search_completed"]))
						{
							$statusPropID = $propIDs["status"];
						
							if (isset($_POST["gs_only"]))
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE')", $conn) or die("Could not find reagent: " . mysql_error());
							}
							else
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE'", $conn) or die("Could not find reagent: " . mysql_error());
							}
						}
						else
						{
							if (isset($_POST["gs_only"]))
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . "  AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn) or die("Could not find reagent: " . mysql_error());
							}
							else
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn) or die("Could not find reagent: " . mysql_error());
							}
						}
					}
					else
					{
						if (isset($_POST["search_completed"]))
						{
							$statusPropID = $propIDs["status"];
						
							if (isset($_POST["gs_only"]))
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID` AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
							else
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID` AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
						}
						else
						{
							if (isset($_POST["gs_only"]))
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID` AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
							else
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID` AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
						}
					}
				}
			}
			else	// keyword is not numeric
			{
				# this may still be a partial ID!
				$tempType = $gfunc_obj->get_typeID($keyword);
	
				# In this case we don't need to check the filter, since we already know the keyword is not numeric, so this is not a group ID

				# however, this might not be a reagent ID after all:
				if ($tempType < 0)
				{
					# this is not a reagent ID; just search properties
					
					// Jan. 21, 2011
					$projectList = array_values($_POST["packets"]);
					$projects = join($projectList, ",");
					$projectPropID = $propIDs["packet id"];

					# check completed first
					if (isset($_POST["search_completed"]))
					{
						$statusPropID = $propIDs["status"];
					
						if ($projects != '0')
						{
							if (count($projectList) == 1)
							{
								if (isset($_POST["gs_only"]))
								{
									$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c, ReagentPropList_tbl d WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' AND d.propertyID='" . $projectPropID . "' AND d.propertyValue='" . $projectList[0] . "' AND d.reagentID=c.reagentID AND d.status='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
								}
								else
								{
									$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c, ReagentPropList_tbl d WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' AND d.propertyID='" . $projectPropID . "' AND d.propertyValue='" . $projectList[0] . "' AND d.reagentID=c.reagentID AND d.status='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
								}
							}
							else
							{
								if (isset($_POST["gs_only"]))
								{
									$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c, ReagentPropList_tbl d WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' AND d.propertyID='" . $projectPropID . "' AND d.propertyValue IN (" . $projects . ") AND d.reagentID=c.reagentID AND d.status='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
								}
								else
								{
									$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c, ReagentPropList_tbl d WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' AND d.propertyID='" . $projectPropID . "' AND d.propertyValue IN (" . $projects . ") AND d.reagentID=c.reagentID AND d.status='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
								}
							}
						}
						else
						{
							if (isset($_POST["gs_only"]))
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
							else
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
						}
					}
					else
					{
						// Jan. 21, 2011
						$projectList = array_values($_POST["packets"]);
						$projects = join($projectList, ",");

						$projectPropID = $propIDs["packet id"];

						if ($projects != '0')
						{
							if (count($projectList) == 1)
							{
								if (isset($_POST["gs_only"]))
								{
									$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, ReagentPropList_tbl c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND c.reagentID=b.reagentID AND c.propertyID='" . $projectPropID . "' AND c.propertyValue='" . $projectList[0] . "' AND c.status='ACTIVE' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
								}
								else
								{
									$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, ReagentPropList_tbl c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND c.reagentID=b.reagentID AND c.propertyID='" . $projectPropID . "' AND c.propertyValue='" . $projectList[0] . "' AND c.status='ACTIVE' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
								}
							}
							else
							{
								if (isset($_POST["gs_only"]))
								{
									$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, ReagentPropList_tbl c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND c.reagentID=b.reagentID AND c.propertyID='" . $projectPropID . "' AND c.propertyValue IN (" . $projects . ") AND c.status='ACTIVE' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
								}
								else
								{
									$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, ReagentPropList_tbl c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND c.reagentID=b.reagentID AND c.propertyID='" . $projectPropID . "' AND c.propertyValue IN (" . $projects . ") AND c.status='ACTIVE' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
								}
							}
						}
						else
						{
							if (isset($_POST["gs_only"]))
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
							else
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
						}
					}
				}
				else
				{
					# Could be either a reagent ID, OR something like 'V234abc'!!
					# Therefore, make sure the portion after the leading alpha char is indeed numeric!
					$tempTrail = substr($keyword, strlen($tempType), strlen($keyword));

					if (ctype_digit($tempTrail))	// Replaced Jan. 12/09, b/c is_numeric returns true if the search term is C12orf29
					{
						# check status
						if (isset($_POST["search_completed"]))
						{
							$statusPropID = $propIDs["status"];
							$tempGroup = $gfunc_obj->get_groupID($keyword);

							if (isset($_POST["exact_match"]))
								$srch = "='" . $tempGroup . "'";
							else
								$srch = "LIKE '%" . $tempGroup . "%'";
			
							if (isset($_POST["gs_only"]))
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch . " AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
							else
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch . " AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
						}
						else
						{
							# May search for reagent ID (still no guarantees it would be found), or a property (yes, properties might be of the form 'C342' as well!)
							$tempGroup = $gfunc_obj->get_groupID($keyword);
			
							if (isset($_POST["exact_match"]))
								$srch = "='" . $tempGroup . "'";
							else
								$srch = "LIKE '%" . $tempGroup . "%'";
			
							if (isset($_POST["gs_only"]))
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch . " AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
							else
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentTypeID`='" . $tempType . "' AND a.`groupID` " . $srch . " AND a.`reagentID`=b.`reagentID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
						}
					}
					else
					{
						# search properties
						
						# check status
						if (isset($_POST["search_completed"]))
						{
							$statusPropID = $propIDs["status"];
						
							if (isset($_POST["gs_only"]))
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
							else
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b, `ReagentPropList_tbl` c WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND c.`reagentID`=b.`reagentID` AND c.`propertyID`='" . $statusPropID . "' AND c.`propertyValue` LIKE '%completed%' AND c.status='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
						}
						else
						{
							if (isset($_POST["gs_only"]))
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND a.reagentID IN (select e.reagentID from Experiment_tbl e, Wells_tbl w, Container_tbl c, Isolate_tbl i, Prep_tbl p where p.wellID=w.wellID and p.isolate_pk=i.isolate_pk and w.containerID=c.containerID and i.expID=e.expID and c.contGroupID ='" . $_SESSION["contGroupNames"]["Glycerol Stocks"] . "' AND p.flag !='YES' AND e.status='ACTIVE' AND i.status='ACTIVE' AND w.status='ACTIVE' AND c.status='ACTIVE' and p.status='ACTIVE') ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
							else
							{
								$searchResultSet = mysql_query("SELECT DISTINCT a.`reagentID`, a.`reagentTypeID`, a.`groupID` FROM `Reagents_tbl` a, `ReagentPropList_tbl` b WHERE a.`reagentID`=b.`reagentID`  AND a.`reagentTypeID` IN " . $filterSet .  " AND b.`propertyValue` ". $srch . " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' ORDER BY `reagentTypeID`, `groupID`", $conn) or die("Could not find reagent: " . mysql_error());
							}
						}
					}
				}
			}
		}
	}

	unset($propIDs);
	unset($conn);		// ????????????

	return $searchResultSet;
}


/**
* @deprecated
*/
function getIDSet($keyword)
{
	global $conn;
	$keyword = trim( $keyword );
	
	$reagentID_set = "('";
	$reagent_type = -1;		// 5/16/06
	$rid_count = 0;

	if( strncasecmp( $keyword, "Vector", 1 ) == 0 )
	{
		$reagent_type = $_SESSION["ReagentType_Name_ID"]["Vector"];
	}
	elseif( strncasecmp( $keyword, "Insert", 1 ) == 0 )
	{
		$reagent_type = $_SESSION["ReagentType_Name_ID"]["Insert"];
	}
	elseif( strncasecmp( $keyword, "Oligo", 1 ) == 0 )
	{
		$reagent_type = $_SESSION["ReagentType_Name_ID"]["Oligo"];
	}
	elseif( strncasecmp( $keyword, "CellLine", 1 ) == 0 )
	{
		$reagent_type = $_SESSION["ReagentType_Name_ID"]["CellLine"];
	}
	
	// May 16/06, Marina
	if ($reagent_type > 0)
	{
//echo "reagent_type : " . $reagent_type . "<BR>";
//echo "keyword: " . substr( $keyword, 1 ) . "<BR>";
		$find_reagentID_rs = mysql_query( "SELECT * FROM `Reagents_tbl` WHERE `reagentTypeID`='" . $reagent_type . "'"
		. " AND `groupID` LIKE '" . substr( $keyword, 1 ) . "' AND `status`='ACTIVE'" , $conn ) or die( "Error trying to find the general reagent ID (2) : " . mysql_error());
	}
	else	// 5/16/06
	{
//echo "reagent_type : " . $reagent_type . "<BR>";
//echo "keyword: " . substr( $keyword, 1 ) . "<BR>";
		$find_reagentID_rs = mysql_query( "SELECT * FROM `Reagents_tbl` WHERE `groupID` LIKE '" . $keyword . "' AND `status`='ACTIVE'" , $conn ) or die( "Error trying to find the general reagent ID (2) : " . mysql_error() );
	}

	while( $find_reagentID_ar = mysql_fetch_array( $find_reagentID_rs, MYSQL_ASSOC ) )
	{
		$rid_count++;
		$reagentID_set = $reagentID_set . $find_reagentID_ar["reagentID"] . "','";
	}
	
	if( $rid_count > 0 )
	{
		$reagentID_set = substr( $reagentID_set, 0,  strlen( $reagentID_set ) - 2  ) . ")";
	}
	else
	{
		$reagentID_set = "('')";
	}
	
	return $reagentID_set;
}


/**
* @deprecated
*/
function searchMethodProperty()
{
}


/**
* Perform a JOIN of Comments and Properties tables to retrieve the full-length value of a comment, which is stored in Comments_tbl and referenced as a foreign key in Properties_tbl
*
* @param STRING keyword
*
* @author John Paul Lee @version 2005
* @author Marina Olhovsky @version 3.1
*/
function searchCommentProperty($keyword)
{
	global $conn;
	
	$test_rs = mysql_query( "SELECT  DISTINCT a.`prepID` , a.`comments`  AS value, b.`value`  AS originalID 
							FROM  `Prep_tbl` a
							INNER  JOIN  `PrepElementProp_tbl` b ON a.`PrepID`  = b.`prepID` 
							WHERE a.`comments` 
							LIKE  '%" . $keyword . "%' 
							AND a.`status`='ACTIVE'
							AND b.`status`='ACTIVE'
							AND b.`elementTypeID`  =  '1'", $conn );
	return $test_rs;
}


// Modified June 21/07, Marina: Add 'project id' property
/**
* Return a list of default properties searched for each reagent type
*
* @param INT Reagent type
*
* @author John Paul Lee @version 2005
* @author Marina Olhovsky @version 3.1
*/
function initializeDefaultOutput($type)
{
	// March 18/09, Marina: Since tag type is now a feature that could potentially have multiple values, and tag position is a descriptor for it, remove from search output
// 	$vectorDefault_ar = array(0=>"packet id", 1=>"name", 2=>"vector type", 3=>"status", 4=>"tag type", 5=>"tag position", 6=>"description");
	$vectorDefault_ar = array(0=>"packet id", 1=>"name", 2=>"vector type", 3=>"status", 4=>"tag", 5=>"description");

	$insertDefault_ar[] = "packet id";
	$insertDefault_ar[] = "name";
	$insertDefault_ar[] = "status";
	$insertDefault_ar[] = "type of insert";
	$insertDefault_ar[] = "open/closed";
	$insertDefault_ar[] = "length";
	$insertDefault_ar[] = "comments";

	$oligoDefault_ar[] = "packet id";
	$oligoDefault_ar[] = "name";
	$oligoDefault_ar[] = "sequence";
	$oligoDefault_ar[] = "length";
	$oligoDefault_ar[] = "oligo type";	# marina, oct 19/06, changed from 'sense'
	$oligoDefault_ar[] = "protocol";
	
	$cellLineDefault_ar[] = "packet id";
	$cellLineDefault_ar[] = "name";
	$cellLineDefault_ar[] = "status";
	$cellLineDefault_ar[] = "cell line type";
	$cellLineDefault_ar[] = "parent vector name";
	$cellLineDefault_ar[] = "parent cell line name";
	$cellLineDefault_ar[] = "description";

	$propToOutput_ar = array();
	$propToOutput_str = "('";
	
	switch ($type)
	{
		case $_SESSION["ReagentType_Name_ID"]["Vector"]:
			$propToOutput_ar = $vectorDefault_ar;
		break;

		case $_SESSION["ReagentType_Name_ID"]["Insert"]:
			$propToOutput_ar = $insertDefault_ar;
		break;

		case $_SESSION["ReagentType_Name_ID"]["Oligo"]:
			$propToOutput_ar = $oligoDefault_ar;
		break;

		case $_SESSION["ReagentType_Name_ID"]["CellLine"]:
			$propToOutput_ar = $cellLineDefault_ar;
		break;

		default:
			// Modified June 1/09
			$propToOutput_ar = array("name", "packet id", "status", "type", "description");
		break;
	}
	
	return $propToOutput_ar;
}


/**
* Find the basic information for each reagent that matches the search parameters
*
* @param STRING A string representing the list of IDs of the properties to be displayed, separated by commas and enclosed in brackets, e.g.  ('5','3','14','15','21','13'))
* @param Array An array containing the names of properties to be displayed, e.g. Array ( [name] => [status] => [5' cloning site] => [3' cloning site] => [open/closed] => [comments] => ) )
* @param INT $tempReagentID ID of the reagent whose properties are currently being displayed
* @param INT $type Type of this reagent
* @param INT $groupNum GroupID of this reagent
*
* @return resultset
*/
function findoutputReagent_specific($preview_str, $storePreview_ar, $tempReagentID, $type, $groupNum)
{
	global $conn;

	// Mar 31/06, Marina
	// Added to change the way some properties are displayed 
	// e.g. 'description' and 'comments' were showing the ID of the property in the General Comments table
	// 'sense' was showing as either 'a' or 's', not the full word
	// perhaps more to be added

	$tmp_sequence  = "";	// Aug 31/06

	foreach ($storePreview_ar as $key => $value)
	{
		// Feb. 28/10: add category
		if ($key == 'type')
			$propID = $_SESSION["ReagentProp_Name_ID"][$_SESSION["ReagentType_ID_Name"][$type] . " type"];
		else
			$propID = $_SESSION["ReagentProp_Name_ID"][$key];

		$rfunc_obj = new Reagent_Function_Class();

		switch ($key)
		{
			case 'name':
			case 'packet id':
			case 'vector type':
			case 'oligo type':
			case 'cell line type':
			case 'type':
			case $_SESSION["ReagentType_ID_Name"][$type] . " type":
			case 'status':
			case 'description':
			case 'comments':
				$category = "General Properties";
			break;

			case 'length':
			case 'sequence':
				$category = "DNA Sequence";
			break;

			case 'tag':
				$category = "DNA Sequence Features";
			break;

			case 'entrez gene id':
			case 'ensembl gene id':
			case 'official gene symbol':
			case 'accession number':
				$category = "External Identifiers";
			break;

			case 'type of insert':
			case 'open/closed':
			case 'protocol':
				$category = "Classifiers";
			break;
		}

		$propId_tmp = $rfunc_obj->getPropertyIDInCategory($propID, $_SESSION["ReagentPropCategory_Name_ID"][$category]);

		// Specific property formatting
		if ($key != 'description' && $key != 'comments')
		{
			if ($key == 'sequence')	// May 30/06
			{
				$normal_rs = mysql_query("SELECT a.`sequence` FROM `Sequences_tbl` a, `ReagentPropList_tbl` b WHERE b.`propertyID` = '". $propId_tmp . "' AND b.`reagentID`='" . $tempReagentID . "' AND a.`seqID` = b.`propertyValue` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn) or die( "Failure in searchOutput_reagent.findoutputReagent_specific(2): " . mysql_error() );

				while( $normal_ar = mysql_fetch_array( $normal_rs, MYSQL_ASSOC ) )
				{
					$storePreview_ar[$key] = $normal_ar["sequence"];
					$tmp_sequence =  $normal_ar["sequence"];
				}
			}
			else if ($key == 'length')	# sept 26/06
			{
				$propId_tmp = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);

				# Find sequence and calculate its length on the spot
				$foundSeqID_rs = mysql_query("SELECT s.`sequence` FROM `ReagentPropList_tbl` p, `Sequences_tbl` s WHERE p.`reagentID`='" . $tempReagentID . "' AND p.`propertyID` = '" . $propId_tmp . "' AND s.`seqID`=p.`propertyValue` AND p.`status`='ACTIVE' AND s.`status`='ACTIVE'", $conn);

				if ($foundSeqID_ar = mysql_fetch_array($foundSeqID_rs, MYSQL_ASSOC))
				{
					$sequence = $foundSeqID_ar["sequence"];

					if (strlen($sequence) > 0)
					{
						$storePreview_ar["length"] = strlen($sequence);
					}
				}
			}
			// March 20/09
			else if ($key == 'tag')
			{
				$normal_rs = mysql_query("SELECT propertyID, propertyValue, descriptor FROM ReagentPropList_tbl WHERE reagentID='" . $tempReagentID . "' AND status='ACTIVE' AND propertyID='". $propId_tmp . "'", $conn) or die("Failure in searchOutput_reagent.findoutputReagent_specific(2): " . mysql_error());

				while ($normal_ar = mysql_fetch_array($normal_rs, MYSQL_ASSOC))
				{
					if ($normal_ar["descriptor"] && ($normal_ar["descriptor"] != ""))
					{
						$storePreview_ar[$key][] = $normal_ar["propertyValue"] . "/" . $normal_ar["descriptor"];
					}
					else
					{
						$storePreview_ar[$key][] = $normal_ar["propertyValue"];
					}
				}
			}
			else
			{
				$normal_rs = mysql_query("SELECT `propertyID`, `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $tempReagentID . "' AND `status`='ACTIVE' AND `propertyID` = '". $propId_tmp . "'", $conn) or die( "Failure in searchOutput_reagent.findoutputReagent_specific(2): " . mysql_error() );

				while( $normal_ar = mysql_fetch_array( $normal_rs, MYSQL_ASSOC ) )
				{
					if ($key == 'oligo type')
					{
						// Display 'sense' or 'antisense' instead of letters 'a' and 's'
						// Only set in display variable ($storePreview_ar); therefore, the actual property value is not affected
						if ((strcasecmp($normal_ar["propertyValue"], 'a') == 0) || (strcasecmp($normal_ar["propertyValue"], 'antisense') == 0))
						{
							$storePreview_ar[$key] = "Antisense";
						}
						elseif ((strcasecmp($normal_ar["propertyValue"], 's') == 0) || (strcasecmp($normal_ar["propertyValue"], 'sense') == 0))
						{
							$storePreview_ar[$key] = "Sense";
						}
					}
					else
					{
						$storePreview_ar[$key] = $normal_ar["propertyValue"];
					}
				}
			}
		}
		else
		{
			$normal_rs = mysql_query("SELECT a.`comment` FROM `GeneralComments_tbl` a, `ReagentPropList_tbl` b WHERE b.`propertyID` = '". $propId_tmp . "' AND b.`reagentID`='" . $tempReagentID . "' AND a.`commentID` = b.`propertyValue` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn) or die( "Failure in searchOutput_reagent.findoutputReagent_specific(2): " . mysql_error() );

			while( $normal_ar = mysql_fetch_array( $normal_rs, MYSQL_ASSOC ) )
			{
				$storePreview_ar[$key] = $normal_ar["comment"];
			}
		}
	}

	return $storePreview_ar;
}


/**
* Display the specific properties for each reagent returned by the search query.
*
* @param INT
* @param INT
* @param Array
* @param INT
* @param MIXED
*/
function outputReagentPreview($rid_tmp, $LIMSID_tmp, $previewInfo_tmpArr, $matchPropertyID_tmp, $matchPropertyValue_tmp)
{
	global $conn;

	$gfunc = new generalFunc_Class();
	$bfunc_obj = new Reagent_Background_Class();	// Aug. 12/09
	$rfunc = new Reagent_Function_Class();

	// Aug. 12/09: check authorization for deletion
	$currUserName = $_SESSION["userinfo"]->getDescription();
	$currUserID = $_SESSION["userinfo"]->getUserID();
	$currUserCategory = $_SESSION["userinfo"]->getCategory();
	$userProjects = getUserProjectsByRole($currUserID, 'Writer');
	$rIDpID = getReagentProjectID($rid_tmp);
	$rChildren = $bfunc_obj->getReagentChildren($rid_tmp);
	
	$rTypeID = $rfunc->getType($rid_tmp);

	$delete_restricted = true;

	echo "<INPUT TYPE=\"hidden\" ID=\"lims_id_" . $LIMSID_tmp . "\" VALUE=\"" . $LIMSID_tmp . "\">";

	// Aug. 12/09: Disallow deletion if this reagent has children!
/*	if ($currUserName == 'Administrator')
	{
		$delete_restricted = false;
	}
	else*/

	/*if ($rIDpID <= 0)	// Feb. 16/10
	{
		$delete_restricted = false;
	}
	else*/ if (sizeof($rChildren) == 0)
	{
		// Feb. 16/10: no children, check location
		$loc_obj = new Location_Output_Class();
		$child_expID = $loc_obj->getExpID($rid_tmp);

		// Feb. 18/10: This is not sufficient.  When preps (wells) for a particular reagent are deleted, rows are deleted from Isolates_tbl, but Experiment_tbl entry is untouched.  Therefore, in order to check if preps exist, need to count the number of ISOLATES:
		$num_isolates = 0;

		// echo $child_expID;
		
		if ($child_expID > 0)
		{
		// echo "SELECT COUNT(isolate_pk) as num_iso FROM Isolate_tbl WHERE expID='" . $child_expID . "' AND status='ACTIVE'";
		
			$num_isolates_rs = mysql_query("SELECT COUNT(isolate_pk) as num_iso FROM Isolate_tbl WHERE expID='" . $child_expID . "' AND status='ACTIVE'", $conn) or die("Cannot fetch isolates: " . mysql_error());
		
			if ($num_isolates_ar = mysql_fetch_array($num_isolates_rs, MYSQL_ASSOC))
			{
				$num_isolates = $num_isolates_ar["num_iso"];
		// 		echo $num_isolates;
			}
		}
		
		// echo $num_isolates;

		if (($child_expID < 1) || ($num_isolates == 0))
// 		if ($child_expID < 1)
		{
			// No location, BUT - Dec. 16/09: STILL check if user has project Write access!!!
			if (!in_array($rIDpID, $userProjects))
			{
				if ($currUserName == 'Administrator')
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

			echo "<INPUT TYPE=\"hidden\" ID=\"write_access_" . $rid_tmp . "\" VALUE=\"" . $LIMSID_tmp . "\">";
		}

// 		if ($rIDpID <= 0)
// 		{
// 			$delete_restricted = false;
// 		}
// 		else if ($rIDpID > 0)
// 		{
// 			if ($currUserName == 'Administrator')
// 			{
// 				$delete_restricted = false;
// 			}
// 			else if (in_array($rIDpID, $userProjects))
// 			{
// 				$delete_restricted = false;
// 			}
// 			else
// 			{
// 				$delete_restricted = true;
// 				echo "<INPUT TYPE=\"hidden\" ID=\"write_access_" . $rid_tmp . "\" VALUE=\"" . $LIMSID_tmp . "\">";
// 			}
// 		}
	}
	else
	{
		$delete_restricted = true;
		echo "<INPUT TYPE=\"hidden\" ID=\"has_children_" . $rid_tmp . "\" VALUE=\"" . $LIMSID_tmp . "\">";
	}

	if ($delete_restricted)
		$disabled = "DISABLED";
	else
		$disabled = "";

	echo "<INPUT TYPE=\"hidden\" ID=\"del_status_rid_" . $LIMSID_tmp . "\">";

	echo "<tr>";

	// Step 3: Output corressponding values for those headers
	echo "<td class=\"searchCheckbox\">";
		echo "<input type=\"checkbox\" " . $disabled . " name=\"ReagentCheckBox[]\" value=" . $rid_tmp . ">";
	echo "</td>";

	// output the LIMS ID
	echo "<td class=\"preview\">" . $LIMSID_tmp . "</td>";

	foreach($previewInfo_tmpArr as $key => $value )
	{
// echo $key;
// echo $value;
		if (strcasecmp($key, "tag") == 0)
		{
			echo "<td class=\"preview\">";

			if (sizeof($value) == 1)
			{
				echo $value[0];
			}
			else
			{
				$tagTypeOutput = "";

				foreach ($value as $x => $tagTypeOutput)
				{
					// don't place separator after last entry
					if ($x != sizeof($value)-1)
						echo $tagTypeOutput . ', ';
					else
						echo $tagTypeOutput;
				}
			}
			echo "</td>";
		}
		else if ((strcasecmp($key, "type") != 0)&& (!in_array($key, $_SESSION["ReagentProp_ID_Name"]) || specialCol($key, $value)))
		{
			outputSpecializedColumns($rid_tmp, $key, "Preview");
		}
		else
		{
			echo "<td class=\"preview\">" . $value . "</td>";
		}
	}

	echo "<td>";
		echo "<a class=\"search\" href=\"Reagent.php?View=6&rid=" . $rid_tmp . "\">Detailed View</a></td>";
	echo "</td>";

	echo "<td>";
		echo "<a class=\"search\" href=\"Location.php?View=1&rid=" . $rid_tmp . "\">Location</a>";
	echo "</td>";

	// Only show Primer Design button for Inserts
	if ($gfunc->get_typeID($gfunc->getConvertedID_rid($rid_tmp)) == 2)
	{
		echo "<td>";
			echo "<a class=\"search\" href=\"Reagent.php?View=7&rid=" . $rid_tmp . "\">Primer Design</a>";
		echo "</td>";
	}

	echo "</tr>";
}


// function: outputSpecializedColumns()
// Defn: Will process and output (with td tags) the wanted value found through a special column type
/**
* Process and output (with td tags) the wanted value found through a special column type
*
* @see ColFunctOutputer
* @author John Paul Lee @version 2005
*/
function outputSpecializedColumns($rid_tmp, $colType, $outputType)
{
	echo "<td class=\"preview\">";
		$outputer = new ColFunctOutputer;
		echo $outputer->output($rid_tmp, $colType, $outputType);
	echo "</td>";
	
}

// function: getSpecializedColumns()
// Defn: Will process and return the wanted value found through a special column type
/**
* Process and return the wanted value found through a special column type
*
* @see ColFunctOutputer
* @author John Paul Lee @version 2005
*/
function getSpecializedColumns( $rid_tmp, $colType, $outputType )
{
	$outputer = new ColFunctOutputer;
	$toreturn = $outputer->output( $rid_tmp, $colType, $outputType );
	unset( $outputer );
	
	return $toreturn;
}
?>
