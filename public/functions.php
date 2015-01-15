<?php

// DO NOT DELETE THIS CLASS - USED IN SEARCH TABLE OUTPUT!!!!!!!!!!!!!!!!!!!!!!!

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
* @package Search
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* Handles functions to output reagent search results (summary) table
 *
 * @author John Paul Lee @version 2005
 *
 * @package Search
 *
 * @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* Include/require statements
*/
include "Reagent/Reagent_Function_Class.php";


/**
* @author John Paul Lee @version 2005
*/
function setVectorTypeIDs( $tomatch )
{
	$vectorTypeID_arr = array();

	//print_r( $tomatch );
	
	while( $row = mysql_fetch_row($tomatch) )
	{
		//foreach ($row as $col_value) {
        //echo "\t\t$col_value\n";
    	//	}	
		$vectorTypeID_arr[ $row[1] ] = $row[0];
	}	 
	
	//print_r( $vectorTypeID_arr );
	
	return $vectorTypeID_arr;
}

/**
* Output reagent type selection box on search page
*
* @author John Paul Lee @version 2005
*/
function reagentChoice()
{
	echo "<SELECT NAME=\"ReagentChoice\" SIZE=1>\n";
	echo "<OPTION>Vector</OPTION>\n";
	echo "<OPTION>Insert</OPTION>\n";
	echo "<OPTION>Oligo</OPTION>\n";
	echo "<OPTION>CellLine</OPTION>\n";
	echo "</SELECT>";
}



function outputarray( $tooutput )
{
	
}


/**
* Generate a map of reagent property IDs => names
*
* @author John Paul Lee @version 2005
*
* @see setVectorTypeIDs()
*/
function getReagentPropID_const()
{
	// CONST : Finds all the names of the property and their given property ID's
	//			propName --> propID
	//           [DEBUG] : 1
	global $conn;
	
	$reagentPropID_rs = mysql_query( "SELECT propertyID, propertyName FROM `ReagentPropType_tbl` WHERE `status`='ACTIVE'", $conn) 
								or die("Query to find property names failed!: ".mysql_error() );
	
	
	$reagentPropID_const = setVectorTypeIDs( $reagentPropID_rs );
	mysql_free_result( $reagentPropID_rs );
	//echo "<br> ---- reagentPropID_const ---- <br>";
	//print_r( $reagentPropID_const );
	return $reagentPropID_const;
}


/**
* Generate a map of reagent property names => IDs
*
* @author John Paul Lee @version 2005
*
* @see setVectorTypeIDs()
*/
function getReagentPropID_const_rev( )
{
	// CONST : Finds all the names of the property and their given property ID's
	//			propName --> propID
	//           [1] : DEBUG
	global $conn;
	
	$reagentPropID_rs = mysql_query( "SELECT propertyName, propertyID FROM `ReagentPropType_tbl` WHERE `status`='ACTIVE'", $conn ) 
						or die("Query to find property names failed!: ".mysql_error() );
	
	
	$reagentPropID_const = setVectorTypeIDs( $reagentPropID_rs );
	mysql_free_result( $reagentPropID_rs );
	//echo "<br> ---- reagentPropID_const ---- <br>";
	//print_r( $reagentPropID_const );
	return $reagentPropID_const;
}


/**
* Generate a map of reagent type IDs => names
*
* @author John Paul Lee @version 2005
*
* @see setVectorTypeIDs()
*/
function getReagentTypes_const()
{
	// CONST : Finds all vector types available currently in the database
	// 			reagentType --> reagentTypeID
	// 			Vector --> 1
	global $conn;
	$reagentTypes_rs = mysql_query( "SELECT `reagentTypeID`, `reagentTypeName` FROM `ReagentType_tbl` WHERE `status`='ACTIVE'", $conn ) 
							or die("Query Failed 1: " .mysql_error() );
	$reagentTypes_const = setVectorTypeIDs( $reagentTypes_rs );
	mysql_free_result( $reagentTypes_rs );
	//echo "<br> ---- reagentTypes_const ---- <br>";
	//print_r( $reagentTypes_const );
	return $reagentTypes_const;
}


/**
* Generate a map of reagent type names => IDs
*
* @author John Paul Lee @version 2005
*
* @see setVectorTypeIDs()
*/
function getReagentTypes_const_rev()
{
	// CONST : Finds all vector types available currently in the database
	// 			reagentType --> reagentTypeID
	// 			1 --> Vector
	global $conn;
	
	$reagentTypes_rs = mysql_query( "SELECT `reagentTypeName`, `reagentTypeID` FROM `ReagentType_tbl` WHERE `status`='ACTIVE'", $conn) 
							or die("Query Failed 1: " .mysql_error() );
	$reagentTypes_const = setVectorTypeIDs( $reagentTypes_rs );
	mysql_free_result( $reagentTypes_rs );
	//echo "<br> ---- reagentTypes_const ---- <br>";
	//print_r( $reagentTypes_const );
	return $reagentTypes_const;
}

// CONST : Finds all the vector properties associated with a given vector type.
/**
* @author John Paul Lee @version 2005
*/
function getReagentPropReq( $reagentToFind, $types )
{
	global $conn;
	
	$reagentProp_rs = mysql_query( "SELECT `propertyID`,`requirement` FROM `PropertyReq_tbl` WHERE reagentTypeID='" . 
					$types[$reagentToFind] . "' AND `status`='ACTIVE' ORDER BY `propertyID` ASC;", $conn ) 
						or die("Query to find reagent property associated failed!: ".mysql_error() );
	
	$tempPropReq_ar = array ();
	
	while( $row = mysql_fetch_array( $reagentProp_rs, MYSQL_ASSOC ) )
	{
		$tempPropReq_ar[$row["propertyID"]]=$row["requirement"];
	}
	
	mysql_free_result( $reagentProp_rs );
	return $tempPropReq_ar;
}

/**
* Fetch a list of required properties for preps of type identified by $reagentTypeID_tofind
*
* @author John Paul Lee @version 2005
*
* @param INT represents reagent type ID, e.g. 1 => 'Vector'
*/
function getReagentPropReq_num( $reagentTypeID_tofind, $types ) 
{
	global $conn;
	$reagentProp_rs = mysql_query( "SELECT `propertyID`,`requirement` FROM `PropertyReq_tbl` WHERE reagentTypeID='" . 
					$reagentTypeID_tofind . "' AND `status`='ACTIVE' ORDER BY `propertyID` ASC;", $conn  ) 
						or die("Query to find reagent property associated failed!: ".mysql_error() );
	
	$tempPropReq_ar = array ();
	
	while( $row = mysql_fetch_array( $reagentProp_rs, MYSQL_ASSOC ) )
	{
		$tempPropReq_ar[$row["propertyID"]]=$row["requirement"];
	}
	
	mysql_free_result( $reagentProp_rs );
	return $tempPropReq_ar;
}


/**
* @author John Paul Lee @version 2005
*/
function cleanArray( $toclean_ar, $constVal, $badvalues )
{
	while( list($delkey, $delval) = each( $badvalues ) )
	{
		unset( $toclean_ar[$constVal[$delval]] );
	}
	
	return $toclean_ar;
}


/**
* Fetch the type ID of the given sequence
*
* @author John Paul Lee @version 2005
*
* @param INT Sequence ID (corresponds to Sequences_tbl.seqID column)
*/
function getSeqTypeID( $toFind )
{
	global $conn;
	
	$type_rs = mysql_query( "SELECT `seqTypeID` FROM `SequenceType_tbl` WHERE `status`='ACTIVE' AND `seqTypeName`='" . $toFind 
						. "'", $conn );
	
	if( $type_ar = mysql_fetch_array( $type_rs, MYSQL_ASSOC ) )
	{
		return $type_ar["seqTypeID"];
	}
	
	return -1;
}

function getNames( $toConvert )
{
	
}

/* outputs the menu options available */
function menuTemp()
{
	echo "<FORM METHOD=POST ACTION=\"index.php\">";
	//echo "<INPUT TYPE=SUBMIT NAME=\"View Vectors\" VALUE=\"View All Vector Reagents\">";
	echo "Input Selection:";
	echo "<SELECT NAME=choice SIZE=1>\n";
	echo "<OPTION>View all Vector Reagents</OPTION>\n";
	echo "<OPTION>View all Insert Reagents</OPTION>\n";
	echo "<OPTION>View all Oligo Reagents</OPTION>\n";
	echo "<OPTION>View all CellLine Reagents</OPTION>\n";
	echo "<OPTION>View Packets Available</OPTION>\n";
	echo "</SELECT> \n";
	echo "<BR><br>\n";
	echo "<INPUT TYPE=SUBMIT NAME=\"ChoiceButton\" VALUE=\"Submit Choice\">";
	echo "</FORM>";
	
	//$_SESSION["SearchType"] = "Reagents";
	
	?> 
	<a href="Search/search.php?Advanced=False&View=1">Search</a>
	<br>
	<a href="Views/LocationPlate_View.php?Mode=0">View</a>
	<br>
	<a href="Insert_Type_seq.xls">Sequence File</a>
	<?php
	
	
	echo "<HR>";
}

/* Basic HTML header information */
function GenerateHTMLHeader( $title )
{
	echo "<html>";
	echo "<head>";
	echo "<title>" . $title . "</title>";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">";
	echo "</head>";
	echo "<body>";
}

/* Basic HTML footer information */
function GenerateHTMLFooter( )
{
	echo "</body>";
	echo "</html>";
}
?>