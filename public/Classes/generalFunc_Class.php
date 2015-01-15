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
* @package All
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* Auxiliary functions used to handle general attributes of reagents and locations in OpenFreezer
* Examples include: format reagent identifier, filter whitespaces, etc.
* Written July 28, 2008
*
* @author John Paul Lee @version 2005
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
*
* @package All
* @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
class generalFunc_Class
{

	/**
	 * Default constructor (zero-argument)
	*/
	function generalFunc_Class()
	{
	}
	
	
	/**
	 * Return the converted OpenFreezer identifier of the given reagent type and groupID
	 *
	 * @author John Paul Lee @version 2005
	 * @author Marina Olhovsky
	 *
	 * @version 3.1
	 *
	 * Written by John Paul Lee; modified  April 2009 by Marina - now that we're letting users create new reagent types, prefixes are no longer limited to one character
	 *
	 * @param INT Integer column value that corresponds to a reagent Type (e.g. 1 => Vector)
	 * @param INT Numeric identifier of a particular reagent of this type
	 *
	 * @return STRING (e.g. V123)
	*/
	function getConvertedID($tempType, $tempGroup)
	{
// 		$testgroup = substr($_SESSION["ReagentType_ID_Name"][$tempType], 0, 1) . $tempGroup;	// removed April 28/09
		
		// Replaced April 28/09
		$testgroup = $_SESSION["ReagentType_ID_Prefix"][$tempType] . $tempGroup;
        error_log("testgroup: ".var_dump($_SESSION["ReagentType_ID_Prefix"]));
		return $testgroup;
	}
	

	// Returns OpenFreezer ID (V123, I456, etc.)
	/**
	 * Return the OpenFreezer ID that corresponds to the given database column value
	 *
	 * @author John Paul Lee @version 2005
	 * @author Marina Olhovsky
	 *
	 * @version 3.1
	 *
	 * @param INT Corresponds to reagentID column value
	 *
	 * @return STRING (e.g. V123)
	 *
	 * @see getConvertedID()
	*/
	function getConvertedID_rid($rid)
	{
		global $conn;
		
		$reagents_rs = mysql_query( "SELECT * FROM `Reagents_tbl` WHERE `reagentID`='" . $rid . "' AND `status`='ACTIVE'" , $conn ) 
		or die( "failed search for reagent id found!" . mysql_error() );

		if( $reagents_ar = mysql_fetch_array( $reagents_rs, MYSQL_ASSOC ) )
		{
			return $this->getConvertedID($reagents_ar["reagentTypeID"], $reagents_ar["groupID"]);
		}
		
		return "";
	}
	
	// Function: get_rid()
	// Defn: Will return the rid (internal database id) from a given Aurora ID (the visual outputted ID.
	// Paramater: $limsid = OpenFreezer ID (ie. V23, I232, C22)
	// Returns: The appropriate RID (internal database ID)
	// Error: Returns -1 if no valid rid was found for the given limsID
	/**
	 * Opposite of getConvertedID_rid($rid): Return the internal reagentID that corresponds to the given OpenFreezer ID
	 *
	 * i.e. V1 => 1 (Reagent_tbl.reagentID = 1)
	 *
	 * @author John Paul Lee @version 2005
	 * @author Marina Olhovsky
	 *
	 * @version 3.1
	 *
	 * @param STRING Corresponds to reagentID column value (e.g. V123)
	 *
	 * @return INT
	*/
	function get_rid($limsid)
	{
		global $conn;

		// Replaced Jan. 12/09: intval() returns true if the argument is '12orf29'
// NO!		if (intval($this->get_groupID($limsid)) > 0)
		if (ctype_digit($this->get_groupID($limsid)))		// Jan. 12/09
		{
			$rid_rs = mysql_query("SELECT * FROM `Reagents_tbl` WHERE `reagentTypeID`='" . $this->get_typeID($limsid) . "' " . "AND `groupID`='" . $this->get_groupID($limsid) . "' AND `status`='ACTIVE'", $conn) or die("Failure in grabbing get_rid() sql: " . mysql_error());
			
			if ($rid_ar = mysql_fetch_array($rid_rs, MYSQL_ASSOC))
			{
				return $rid_ar["reagentID"];
			}
		}

		return -1;
	}


	/**
	 * February 2, 2007, Marina: For VERY long lists of IDs, do one batch convert and return a dictionary
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param Array list of internal db ids
	 *
	 * @return Array dictionary of (db_id, LIMS_ID) tuples
	*/
	function getLabIDs($id_list)
	{
		global $conn;
		$lab_ids = array();

		foreach ($id_list as $key => $rid)
		{
			$reagents_rs = mysql_query("SELECT CONCAT(reagent_prefix, groupID) as lims_id FROM Reagents_tbl r, ReagentType_tbl t WHERE r.reagentID='" . $rid . "' AND r.reagentTypeID=t.reagentTypeID AND r.status='ACTIVE'" , $conn) or die("Failed search for reagent ID " . mysql_error());


			if ($reagents_ar = mysql_fetch_array($reagents_rs, MYSQL_ASSOC))
			{
				$lab_ids[$rid] = $reagents_ar["lims_id"];
			}
		}

		return $lab_ids;
	}

	
	/**
	 * April 16, 2007, Marina: Get the type ID of $rid == an **internal database ID**
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT internal db ID
	 *
	 * @return INT (1, 2, 3, 4, ...) for Vector, Insert, Oligo, Cell Line, etc.
	*/
	function getTypeID($rid)
	{
		global $conn;
		
		$reagents_rs = mysql_query("SELECT * FROM `Reagents_tbl` WHERE `reagentID`='" . $rid . "' AND `status`='ACTIVE'" , $conn) or die("failed search for reagent id found!" . mysql_error());

		if ($reagents_ar = mysql_fetch_array($reagents_rs, MYSQL_ASSOC))
		{
			return $reagents_ar["reagentTypeID"];
		}
		
		return -1;
	}
	

	/**
	 * Return the numeric portion of a reagent's OpenFreezer identifier
	 *
	 * Marina, June 8/09: Simple prefix matching is not going to work for all novel reagent types now, since single-letter prefixes can be contained within longer ones (e.g. 'V' for Vector and 'VI' for Virus).  Use regular expressions instead.
	 *
	 * @author John Paul Lee @version 2005
	 * @author Marina Olhovsky
	 *
	 * @version 3.1
	 *
	 * @param STRING alphanumeric string representing an OpenFreezer ID, e.g. V123, or Ab123
	 *
	 * @return INT numeric portion of the identifier
	*/
	function get_groupID($limsid)
	{
		$groupID_tmp = -1;

		// Marina, June 8/09: Simple prefix matching is not going to work for all novel reagent types now, since single-letter prefixes can be contained within longer ones (e.g. 'V' for Vector and 'VI' for Virus)
		// Use regular expressions instead:
/*
		if (!isset($_SESSION["ReagentType_Name_Prefix"]))
		{
			//return -1;
			$temp_session_var_obj = new Session_Var_Class();
			
			$temp_session_var_obj->checkSession();
			
			unset( $temp_session_var_obj );
		}*/

		// added June 8/09
		$keywords = preg_split("/[A-Z]+/", strtoupper(trim($limsid)));
		$groupID_tmp = $keywords[1];

		/*
		foreach( $_SESSION["ReagentType_Name_Prefix"] as $name => $prefix )
		{
			if (strpos(strtoupper($limsid), $prefix) === 0)
			{
				//$reagentTypeID_tmp = $_SESSION["ReagentType_Name_ID"][ $name ];
				
				$groupID_tmp = substr( $limsid, strlen( $prefix ), strlen( $limsid ) );
				
				break;
			}
		}*/
		
		return $groupID_tmp;
	}
	

	/**
	 * Equal and opposite to getGroupID: Return the database ID that corresponds to the type of the given reagent, identified by $limsid
	 *
	 * Marina, June 8/09: Simple prefix matching is not going to work for all novel reagent types now, since single-letter prefixes can be contained within longer ones (e.g. 'V' for Vector and 'VI' for Virus).  Use regular expressions instead
	 *
	 * @author John Paul Lee @version 2005
	 * @author Marina Olhovsky
	 *
	 * @version 3.1
	 *
	 * @param STRING alphanumeric string representing an OpenFreezer ID, e.g. V123, or Ab123
	 *
	 * @return INT Database identifier of the type of this reagent (1 => Vector for V123, 6 => Antibody for Ab123, etc.)
	*/
	function get_typeID($limsid)
	{
		global $conn;
		
		$reagentTypeID_tmp = -1;

/* Removed June 8/09
		if( !isset( $_SESSION["ReagentType_Name_Prefix"] ) )
		{
			//return -1;
			$temp_session_var_obj = new Session_Var_Class();
			
			$temp_session_var_obj->checkSession();
			
			unset( $temp_session_var_obj );
		}
		
		foreach( $_SESSION["ReagentType_Name_Prefix"] as $name => $prefix )
		{
			if( strpos( strtoupper( $limsid ) , $prefix ) === 0 )
			{
				$reagentTypeID_tmp = $_SESSION["ReagentType_Name_ID"][ $name ];
				
				//$groupID_tmp = substr( $insert_rid, strlen( $prefix ), strlen( $insert_rid ) );
				
				break;
			}
		}*/
		
		// added June 8/09
		$keywords = preg_split("/[0-9]+/", trim($limsid));

		// Nov. 4/09: CASE-SENSITIVITY!!!  Searching for 'v123' would not match 'v' for Vector through SESSION vars!!!
// 		$reagentTypeID_tmp = $_SESSION["ReagentType_Prefix_ID"][$keywords[0]];

		$prefix_rs = mysql_query("SELECT reagentTypeID FROM ReagentType_tbl WHERE reagent_prefix='" . $keywords[0] . "' AND status='ACTIVE'", $conn) or die ("Could not select reagent type prefix: " . mysql_error());

		if ($prefix_ar = mysql_fetch_array($prefix_rs, MYSQL_ASSOC))
			$reagentTypeID_tmp = $prefix_ar["reagentTypeID"];

		return $reagentTypeID_tmp;
	}


	/**
	 * Return the experiment ID that corresponds to this reagent (to determine if there are locations for this reagent)
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param INT Internal reagent ID (Reagents_tbl.reagentID column value)
	 *
	 * @return INT Experiment_tbl.expID column value
	*/
	function get_expID( $rid )
	{
		global $conn;
		
		$exp_rs = mysql_query("SELECT * FROM `Experiment_tbl` WHERE `reagentID`='" . $rid . "' AND `status`='ACTIVE'" , $conn) or die("Failure in get_expID() sql statement: " . mysql_error());

		if ($exp_ar = mysql_fetch_array($exp_rs, MYSQL_ASSOC))
		{
			return $exp_ar["expID"];
		}
		
		return -1;
	}

	/**
	 * Retrive the highest group ID in Reagents_tbl (to assign the next highest to a newly added reagent)
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT reagent type ID (e.g. 1 => Vector)
	 *
	 * @return INT Highest numeric identifier of reagents of this type ( = group ID)
	*/
	function get_max_groupid( $reagentTypeID )
	{
		global $conn;
		
		$final_max = -1;
		
		$max_rs = mysql_query("SELECT MAX(`groupID`) as maxid FROM `Reagents_tbl` WHERE `ReagentTypeID`='" . $reagentTypeID . "' ". "AND `status`='ACTIVE'", $conn) or die( "FAILURE IN get_max_groupid(): " . mysql_error());

		if( $max_ar = mysql_fetch_array( $max_rs, MYSQL_ASSOC ) )
		{
			$final_max = $max_ar["maxid"];
		}
		
		mysql_free_result( $max_rs );
		unset( $max_rs, $max_ar );
		
		return $final_max;
	}


	/**
	 * Remove whitespace from a string
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param STRING
	 * @return STRING
	*/
	function remove_whitespaces( $sin )
	{
		$white = array(" ", "\t", "\r", "\n", "\0", "\x0B");
		
		return str_replace( $white, "", $sin );
	}

	
	/**
	 * Auxiliary function to remove elements from a set represented by string
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param INT
	 * @param STRING
	 *
	 * @return STRING
	*/
	function reset_set($count, $set)
	{
		if ($count > 0)
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
	 * Replace special characters in a string with appropriate HTML tags
	 *
	 * Added Feb. 12/08, Marina - Source http://www.hawkee.com/snippet/445/
	 *
	 * @param STRING
	 * @return STRING
	*/
	function textarea_encode($html_code)
	{
		$from = array('<', '>');
		$to = array('#&50', '#&52');
		$html_code = str_replace($from, $to, $html_code);

		return $html_code;
	}
}
?>
