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
 * Contains auxiliary functions for managing reagents
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
class Reagent_Function_Class
{
	/**
	 * @var STRING
	 * Error message returned by this class in various cases (contains the name of this class to help pinpoint the source of the error)
	*/
	var $classerror;

	/**
	 * @var Array
	 * Map of nucleotide complements ("a" => "t", "c" => "g", "g" => "c", "t" => "a")
	*/
	var $complement_map;

	/**
	 * @var Array
	 * Map of codon triplets
	*/
	var $codonMap;		// Added June 4/08
	
	/**
	* Constructor
	*/
	function Reagent_Function_Class()
	{
		$this->classerror = "Reagent_Function_Class.";
		$this->complement_map = array("a" => "t", "c" => "g", "g" => "c", "t" => "a");
		// print_r($this->complement_map);

		// Added June 4/08
		$this->codonMap = Array();

		$codonMap["TTT"] = 'F';
		$codonMap["TCT"] = 'S';
		$codonMap["TAT"] = 'Y';
		$codonMap["TGT"] = 'C';
		$codonMap["TTC"] = 'F';
		$codonMap["TCC"] = 'S';
		$codonMap["TAC"] = 'Y';
		$codonMap["TGC"] = 'C';
		$codonMap["TTA"] = 'L';
		$codonMap["TCA"] = 'S';
		$codonMap["TAA"] = '*';
		$codonMap["TGA"] = '*';
		$codonMap["TTG"] = 'L';
		$codonMap["TCG"] = 'S';
		$codonMap["TAG"] = '*';
		$codonMap["TGG"] = 'W';
		$codonMap["CTT"] = 'L';
		$codonMap["CCT"] = 'P';
		$codonMap["CAT"] = 'H';
		$codonMap["CGT"] = 'R';
		$codonMap["CTC"] = 'L';
		$codonMap["CCC"] = 'P';
		$codonMap["CAC"] = 'H';
		$codonMap["CGC"] = 'R';
		$codonMap["CTA"] = 'L';
		$codonMap["CCA"] = 'P';
		$codonMap["CAA"] = 'Q';
		$codonMap["CGA"] = 'R';
		$codonMap["CTG"] = 'L';
		$codonMap["CCG"] = 'P';
		$codonMap["CAG"] = 'Q';
		$codonMap["CGG"] = 'R';
		$codonMap["ATT"] = 'I';
		$codonMap["ACT"] = 'T';
		$codonMap["AAT"] = 'N';
		$codonMap["AGT"] = 'S';
		$codonMap["ATC"] = 'I';
		$codonMap["ACC"] = 'T';
		$codonMap["AAC"] = 'N';
		$codonMap["AGC"] = 'S';
		$codonMap["ATA"] = 'I';
		$codonMap["ACA"] = 'T';
		$codonMap["AAA"] = 'K';
		$codonMap["AGA"] = 'R';
		$codonMap["ATG"] = 'M';
		$codonMap["ACG"] = 'T';
		$codonMap["AAG"] = 'K';
		$codonMap["AGG"] = 'R';
		$codonMap["GTT"] = 'V';
		$codonMap["GCT"] = 'A';
		$codonMap["GAT"] = 'D';
		$codonMap["GGT"] = 'G';
		$codonMap["GTC"] = 'V';
		$codonMap["GCC"] = 'A';
		$codonMap["GAC"] = 'D';
		$codonMap["GGC"] = 'G';
		$codonMap["GTA"] = 'V';
		$codonMap["GCA"] = 'A';
		$codonMap["GAA"] = 'E';
		$codonMap["GGA"] = 'G';
		$codonMap["GTG"] = 'V';
		$codonMap["GCG"] = 'A';
		$codonMap["GAG"] = 'E';
		$codonMap["GGG"] = 'G';
	}


	// July 16, 2010
	/**
	 * Calculate the total number of reagents in OpenFreezer
	 *
	 * @author Marina Olhovsky
	 * @version 2010-07-16
	 * @return INT
	*/
	function computeTotalReagents()
	{
		global $conn;
		$total = 0;

		$rcount_rs = mysql_query("SELECT COUNT(reagentID) as rcount FROM Reagents_tbl WHERE status='ACTIVE'");

		if ($rcount_ar = mysql_fetch_array($rcount_rs, MYSQL_ASSOC))
		{
			$total = $rcount_ar["rcount"];
		}

		return $total;
	}

	// Function: set_Session_Reagent_Types_Prefix_Name()
	// Defn: Sets the session variable for reagent types where index = Prefixs and values of index = Reagent Type Names
	// Paramaters: None
	// Returns: None
	// Throws: 
	// Created: July 26/05
	// Notes: This function does not check if the session variable is set or not. Users should check that before hand.
	/**
	 * Generates a map of reagent type prefixes to their names and stores it in a session variable (prefix=> rTypeName)
	 * @author John Paul Lee @version 2005-05-26
	*/
	function set_Session_Reagent_Types_Prefix_Name()
	{
		global $conn;
		$functionerror = "set_Session_Reagent_Types_Prefix_Name(";
		
		$prefix_rs = mysql_query("SELECT `reagentTypeName`, `reagent_prefix` FROM `ReagentType_tbl` WHERE `status`='ACTIVE'", $conn) or die($this->classerror . $functionerror . $count++ . ")" . mysql_error());

		$prefix_final = $this->set_Generic_Array( $prefix_rs );
		mysql_free_result( $prefix_rs );
		
		$_SESSION["ReagentType_Prefix_Name"] = $prefix_final;
	}
	
	// Function: set_Session_Reagent_Types_Name_Prefix()
	// Defn: Set's the session variable for reagent types where index = Reagent Type Names and values of index = Prefixs 
	// Paramaters: None
	// Returns: None
	// Throws: 
	// Created: July 26/05
	// Notes: This function does not check if the session variable is set or not. Users should check that before hand.
	/**
	 * Generates a map of reagent type prefixes to their names and stores it in a session variable (rTypeName => prefix)
	 * @author John Paul Lee @version 2005-05-26
	*/
	function set_Session_Reagent_Types_Name_Prefix()
	{
		global $conn;
		$functionerror = "set_Session_Reagent_Types_Name_Prefix(";
		
		$prefix_rs = mysql_query("SELECT `reagent_prefix`, `reagentTypeName` FROM `ReagentType_tbl` WHERE `status`='ACTIVE'", $conn) or die( $this->classerror . $functionerror . $count++ . ")" . mysql_error() );

		$prefix_final = $this->set_Generic_Array( $prefix_rs );
		mysql_free_result( $prefix_rs );
		
		$_SESSION["ReagentType_Name_Prefix"] = $prefix_final;
	}
	
	/**
	 * Generates a map of reagent type names to their IDs and stores it in a session variable (rTypeName => rTypeID)
	 * @author John Paul Lee @version 2005-05-26
	*/
	function set_Session_Reagent_Types_Name_ID()
	{
		global $conn;
		$functionerror = "set_Session_Reagent_Types_Name_ID(";
		
		$prefix_rs = mysql_query("SELECT `reagentTypeID`, `reagentTypeName` FROM `ReagentType_tbl` WHERE `status`='ACTIVE'", $conn) or die( $this->classerror . $functionerror . $count++ . ")" . mysql_error() );

		$prefix_final = $this->set_Generic_Array( $prefix_rs );
		mysql_free_result( $prefix_rs );
		
		$_SESSION["ReagentType_Name_ID"] = $prefix_final;
	}
	
	/**
	 * Generates a map of reagent type IDs to their names and stores it in a session variable (rTypeID => rTypeName)
	 * @author John Paul Lee @version 2005-05-26
	*/
	function set_Session_Reagent_Types_ID_Name()
	{
		global $conn;
		$functionerror = "set_Session_Reagent_Types_ID_Name(";
		
		$prefix_rs = mysql_query("SELECT `reagentTypeName`, `reagentTypeID`  FROM `ReagentType_tbl` WHERE `status`='ACTIVE'", $conn ) 
						or die( $this->classerror . $functionerror . $count++ . ")" . mysql_error() );
							
		$prefix_final = $this->set_Generic_Array( $prefix_rs );
		mysql_free_result( $prefix_rs );
		
		$_SESSION["ReagentType_ID_Name"] = $prefix_final;
	}
	
	/**
	 * Generates a map of reagent SUBtype names to their IDs and stores it in a session variable -- NOT USED ACTIVELY AT THE MOMENT
	 * @author John Paul Lee @version 2005-05-26
	*/
	function set_Session_Reagent_SubTypes_RType_Name_ID()
	{
		global $conn;
		$functionerror = "set_Session_Reagent_SubTypes_RType_Name_ID(";
		$rtype_count = 0;
		
		$subtype_session_arr = array();
		
		$subtype_rs = mysql_query("SELECT `reagent_SubTypeID`, `reagent_typeID`, `name`  FROM `Reagent_SubType_tbl` WHERE `status`='ACTIVE' "
						. "ORDER BY `reagent_typeID` ", $conn ) 
						or die( $this->classerror . $functionerror . $count++ . ")" . mysql_error() );
							
		//$prefix_final = $this->set_Generic_Array( $prefix_rs );
		while( $subtype_ar = mysql_fetch_array( $subtype_rs , MYSQL_ASSOC ) )
		{
			if( $subtype_ar["reagent_typeID"] > $rtype_count )
			{
				//$subtype_session_arr[ $_SESSION["ReagentType_ID_Name"][ $subtype_ar["reagent_typeID"] ] ] = array( );
			}
			
			$subtype_session_arr[ $_SESSION["ReagentType_ID_Name"][ $subtype_ar["reagent_typeID"] ] ][ $subtype_ar["name"] ] = $subtype_ar["reagent_SubTypeID"];
		}
		
		mysql_free_result( $subtype_rs );
		
		$_SESSION["Reagent_SubType_RType_Name_ID"] = $subtype_session_arr;
	}
	
	/**
	 * Generates a map of reagent SUBtype IDs to their names and stores it in a session variable -- NOT USED ACTIVELY AT THE MOMENT
	 * @author John Paul Lee @version 2005-05-26
	*/
	function set_Session_Reagent_SubTypes_RType_ID_Name()
	{
		global $conn;
		$functionerror = "set_Session_Reagent_SubTypes_RType_ID_Name(";
		$rtype_count = 0;
		
		$subtype_session_arr = array();
		
		$subtype_rs = mysql_query("SELECT `reagent_SubTypeID`, `reagent_typeID`, `name`  FROM `Reagent_SubType_tbl` WHERE `status`='ACTIVE' "
						. "ORDER BY `reagent_typeID` ", $conn ) 
						or die( $this->classerror . $functionerror . $count++ . ")" . mysql_error() );
							
		//$prefix_final = $this->set_Generic_Array( $prefix_rs );
		while( $subtype_ar = mysql_fetch_array( $subtype_rs , MYSQL_ASSOC ) )
		{
			if( $subtype_ar["reagent_typeID"] > $rtype_count )
			{
				//$subtype_session_arr[ $_SESSION["ReagentType_ID_Name"][ $subtype_ar["reagent_typeID"] ] ] = array( );
			}
			
			$subtype_session_arr[ $_SESSION["ReagentType_ID_Name"][ $subtype_ar["reagent_typeID"] ] ][ $subtype_ar["reagent_SubTypeID"] ] = $subtype_ar["name"];
		}
		
		mysql_free_result( $subtype_rs );
		
		$_SESSION["Reagent_SubType_RType_ID_Name"] = $subtype_session_arr;

	}
	
	/**
	 * Generates a simple map of reagent proerty names to their IDs and stores it in a session variable (propName => propID, e.g. 'name' -> '1')
	 * @author John Paul Lee @version 2005-05-26
	*/
	function set_Session_Reagent_Prop_ID_Name_ID()
	{
		// CONST : Finds all the names of the property and their given property ID's
		//			propName --> propID
		//           [DEBUG] : 1
		global $conn;
		
		$reagentPropID_rs = mysql_query( "SELECT propertyID, propertyName FROM `ReagentPropType_tbl` WHERE `status`='ACTIVE'", $conn) or die("Query to find property names failed!: ".mysql_error() );
		
		$reagentPropID_const = $this->set_Generic_Array($reagentPropID_rs);
		mysql_free_result($reagentPropID_rs);
		
		$_SESSION["ReagentProp_Name_ID"] = $reagentPropID_const;
	}

	/**
	 * Generates a simple map of reagent proerty IDs to their names and stores it in a session variable (propID => propName, e.g. '1' -> 'name')
	 * @author John Paul Lee @version 2005-05-26
	*/
	function set_Session_Reagent_Prop_ID_ID_Name( )
	{
		// CONST : Finds all the names of the property and their given property ID's
		//			propName --> propID
		//           [1] : DEBUG
		global $conn;
		
		$reagentPropID_rs = mysql_query( "SELECT propertyName, propertyID FROM `ReagentPropType_tbl` WHERE `status`='ACTIVE'", $conn) or die("Query to find property names failed!: ".mysql_error() );
		
		$reagentPropID_const = $this->set_Generic_Array($reagentPropID_rs);
		mysql_free_result($reagentPropID_rs);

		$_SESSION["ReagentProp_ID_Name"] = $reagentPropID_const;
	}
	
	// Function set_Generic_Array()
	// Defn: A generic set function that converts a result set and converts it into an array
	// Paramaters: Database result set to convert
	// Returns: Array of converted result set
	// Throws:
	// Created: July 26/05
	/**
	 * A generic set function that converts a result set and converts it into an array
	 * @author John Paul Lee @version 2005-05-26
	 * @param resource
	 * @return Array
	*/
	function set_Generic_Array($toset_rs)
	{
		$general_arr = array();

		while ($row = mysql_fetch_row($toset_rs))
		{
			$general_arr[$row[1]] = $row[0];
		}

		return $general_arr;
	}
	
	// Added March 20/07, Marina
	// Fetch rid's property value corresponding to propID
	// Update July 2009: $propID now corresponds to propCatID column in ReagentPropertyCategories_tbl
	/**
	 * Fetch a given property value of a given reagent.  $propID represents a property within a specific category (corresponds to propCatID column in ReagentPropertyCategories_tbl).
	 *
	 * @author Marina Olhovsky
	 * @version 2007-03-20 last update July 2009
	 *
	 * @param INT
	 * @param INT
	 * @return resource
	*/
	function getPropertyValue($rid, $propID)
	{
		global $conn;

		$result = Array();
		
		//echo "SELECT propertyValue FROM ReagentPropList_tbl WHERE reagentID='" . $rid . "' AND propertyID='" . $propID . "' AND status='ACTIVE'";

		$props_rs = mysql_query("SELECT propertyValue FROM ReagentPropList_tbl WHERE reagentID='" . $rid . "' AND propertyID='" . $propID . "' AND status='ACTIVE'", $conn) or die ("Failure in selecting reagent property value: " . $mysql_error());

		while ($props_ar = mysql_fetch_array($props_rs, MYSQL_ASSOC))
		{
			$result[] = $props_ar["propertyValue"];
		}

		if (sizeof($result) == 1)
			return $result[0];

		if (sizeof($result) == 0)
			return "";
//print_r($result);
		return $result;
	}
	

	// May 23/08: Return a map of {features => descriptors}
	/**
	 * Return a map of {features => descriptors}.
	 *
	 * @author Marina Olhovsky
	 * @version 2008-05-23
	 * @return STRING
	*/
	function getFeatureDescriptors()
	{
		$desrs = array();

		$desrs["tag"] = "tag position";
		$desrs["promoter"] = "expression system";

		return $desrs;
	}
	

	// March 17/08, Marina: For sequence features, fetch the extra descriptor (e.g. position for tag type or expr.syst. for promoter) 
	/**
	 * For sequence features, fetch the extra descriptor (e.g. position for tag type or expr.syst. for promoter) 
	 *
	 * @author Marina Olhovsky
	 * @version 2008-05-17
	 * @return resource
	*/
	function getFeatureDescriptor($rid, $fID, $fVal)
	{
		global $conn;

		$result = "";
		
		if ($fVal && ($fVal != ""))
		{
			// echo "SELECT descriptor FROM ReagentPropList_tbl WHERE reagentID='" . $rid . "' AND propertyID='" . $fID . "' AND propertyValue='" . $fVal . "' AND status='ACTIVE'";

			$props_rs = mysql_query("SELECT descriptor FROM ReagentPropList_tbl WHERE reagentID='" . $rid . "' AND propertyID='" . $fID . "' AND propertyValue='" . addslashes($fVal) . "' AND status='ACTIVE'", $conn) or die ("Could not select feature descriptor: " . $mysql_error());
			
			if ($props_ar = mysql_fetch_array($props_rs, MYSQL_ASSOC))
			{
				$result = $props_ar["descriptor"];
			}

// 			echo $result;
		}

		return $result;
	}

	/**
	 * Set session variables for reagent information
	 * @author John Paul Lee @version 2005
	*/
	function check_Reagent_Session()
	{
		if( !isset( $_SESSION["Reagent_SubType_RType_Name_ID"] ) )
		{
			
			$this->set_Session_Reagent_SubTypes_RType_Name_ID();
			
		}
		
		if( !isset( $_SESSION["Reagent_SubType_RType_ID_Name"] ) )
		{
			$this->set_Session_Reagent_SubTypes_RType_ID_Name();
		}
		
		if( !isset( $_SESSION["ReagentProp_Name_ID"] ) )
		{
			$this->set_Session_Reagent_Prop_ID_Name_ID();
		};
		
		if( !isset( $_SESSION["ReagentProp_ID_Name"] ) )
		{
			$this->set_Session_Reagent_Prop_ID_ID_Name( );
		}
		
		if( !isset( $_SESSION["ReagentType_Name_ID"] ) )
		{
			$this->set_Session_Reagent_Types_Name_ID();
		}
		
		if( !isset( $_SESSION["ReagentType_ID_Name"] ) )
		{
			$this->set_Session_Reagent_Types_ID_Name();
		}
		
		if( !isset( $_SESSION["ReagentType_Prefix_Name"] ) )
		{
			$this->set_Session_Reagent_Types_Prefix_Name();
		}
		
		if( !isset( $_SESSION["ReagentType_Name_Prefix"] ) )
		{
			$this->set_Session_Reagent_Types_Name_Prefix();
		}
	}
	
	/**
	 * Reset reagent session variables
	 * @author John Paul Lee @version 2005
	*/
	function reset_Reagent_Session()
	{
		unset( $_SESSION["Reagent_SubType_RType_Name_ID"] ); 
		$this->set_Session_Reagent_SubTypes_RType_Name_ID();

		
		unset( $_SESSION["Reagent_SubType_RType_ID_Name"] );
		$this->set_Session_Reagent_SubTypes_RType_ID_Name();
		
		unset( $_SESSION["ReagentProp_Name_ID"] );
		$this->set_Session_Reagent_Prop_ID_Name_ID();
		
		unset( $_SESSION["ReagentProp_ID_Name"] );
		$this->set_Session_Reagent_Prop_ID_ID_Name( );
		
		unset( $_SESSION["ReagentType_Name_ID"] );
		$this->set_Session_Reagent_Types_Name_ID();
		
		unset( $_SESSION["ReagentType_ID_Name"] );
		$this->set_Session_Reagent_Types_ID_Name();
		
		unset( $_SESSION["ReagentType_Prefix_Name"] );
		$this->set_Session_Reagent_Types_Prefix_Name();
		
		unset( $_SESSION["ReagentType_Name_Prefix"] );
		$this->set_Session_Reagent_Types_Name_Prefix();
	}
	
	// This is where the groupID gets set -- in a transaction (March 8/06, Marina)
	/**
	 * This is where the groupID gets set -- in a transaction
	 * @author John Paul Lee @version 2005
	 * @author Marina Olhovsky @version 2006-05-05 - set high group IDs (>5000) for MGC clones
	 * @return INT
	*/
	function set_New_Reagent_LockSafe($toinsert)
	{
		global $conn;
		global $MGC_Start;	// 5/5/06, Marina

		$functionerror = ".set_New_Reagent_LockSafe(";
		$newid = -1;
		
		$reagentTypeID = $_SESSION["ReagentType_Name_ID"][$toinsert];
		
		// Lock tables to ensure to get a consistent PK for each table insert
		mysql_query("LOCK TABLES `Reagents_tbl` WRITE", $conn );
		
		// May 5, Marina - Assign group IDs <50000 when MGC clones are uploaded
		if (($reagentTypeID == $_SESSION["ReagentType_Name_ID"]["Vector"]) || ($reagentTypeID == $_SESSION["ReagentType_Name_ID"]["Insert"]))
		{
			$reagent_rs = mysql_query("SELECT MAX(`groupID`) as maxID FROM `Reagents_tbl` WHERE `reagentTypeID`='" . $reagentTypeID . "' AND `groupID`<'" . $MGC_Start . "' AND `status`='ACTIVE'", $conn) or die("FAILURE IN creating new reagent: " . mysql_error());
		}
		else
		{
			$reagent_rs = mysql_query("SELECT max(`groupID`) as maxID FROM `Reagents_tbl` WHERE `reagentTypeID`='" . $reagentTypeID . "'  AND `status`='ACTIVE'", $conn) or die( "FAILURE IN creating new reagent: " . mysql_error());
		}

		if ($reagent_ar = mysql_fetch_array($reagent_rs , MYSQL_ASSOC))
		{
			$newreagent_rs = mysql_query( "INSERT INTO `Reagents_tbl` (`reagentID`, `reagentTypeID`, `groupID`, `status`) "
			. "VALUES ('', '" . $reagentTypeID . "', '" . ($reagent_ar["maxID"] + 1) . "', 'ACTIVE')", $conn)
			or die( "FAILURE IN Reagent_Creator_Class.process_Vector_all_input(2): " . mysql_error());

			$newid = mysql_insert_id($conn);
		}
		else
		{
			// What should happen if failure here?

			// May 5/06, Marina - For vectors and inserts, this is the case when their group ID has reached the base ID of MGC clones
			if (($reagentTypeID == $_SESSION["ReagentType_Name_ID"]["Vector"]) || ($reagentTypeID == $_SESSION["ReagentType_Name_ID"]["Insert"]))
			{
				// Retrieve the highest unique group number, without regard for MGC
				$reagent_rs = mysql_query("SELECT max(`groupID`) as maxID FROM `Reagents_tbl` WHERE `reagentTypeID`='" . $reagentTypeID . "'  AND `status`='ACTIVE'", $conn) or die( "FAILURE IN Reagent_Creator_Class.process_Vector_all_input(1): " . mysql_error());
	
				if ($reagent_ar = mysql_fetch_array($reagent_rs, MYSQL_ASSOC))
				{
					$newreagent_rs = mysql_query("INSERT INTO `Reagents_tbl` (`reagentID`, `reagentTypeID`, `groupID`, `status`) VALUES ('', '" . $reagentTypeID . "', '" . ($reagent_ar["maxID"] + 1) . "', 'ACTIVE')", $conn) or die("FAILURE IN Reagent_Creator_Class.process_Vector_all_input(2): " . mysql_error());
				}
				else
				{
					// assign groupID = '1'
					$newreagent_rs = mysql_query("INSERT INTO `Reagents_tbl` (`reagentID`, `reagentTypeID`, `groupID`, `status`) VALUES ('', '" . $reagentTypeID . "', '" . "1" . "', 'ACTIVE')", $conn)
					or die("FAILURE IN Reagent_Creator_Class.process_Vector_all_input(2): " . mysql_error());
				}
			}
			else	// unlikely to happen; mostly for error checking - (code John, comm. Marina)
			{
				// Assume that there is NO current groupID's for the given reagentType ID, so assume that groupID = 1
				$newreagent_rs = mysql_query("INSERT INTO `Reagents_tbl` (`reagentID`, `reagentTypeID`, `groupID`, `status`) VALUES ('', '" . $reagentTypeID . "', '" . "1" . "', 'ACTIVE')", $conn)
				or die("FAILURE IN Reagent_Creator_Class.process_Vector_all_input(2): " . mysql_error());
			}

			$newid = mysql_insert_id($conn);
		}

		mysql_query("UNLOCK TABLES");
		
		mysql_free_result($reagent_rs);
		unset($reagent_rs);
		
		return $newid;
		
	}
	
	/**
	 * Retrieve reagent's association type (parent-child relation type, e.g. non-recombination vector, recombination vector, stable cell line, etc.)
	 * @author John Paul Lee @version 2005
	 * @return INT
	*/
	function get_Association_Type($name)
	{
		global $conn;
		$functionerror = "get_Association_Type(";
		
		$type_rs = mysql_query("SELECT * FROM `AssocType_tbl` WHERE `status`='ACTIVE' AND `association`='" . trim( $name ) . "'", $conn )
		or die( $this->classerror . $functionerror . "13)" . mysql_error() );

		if( $type_ar = mysql_fetch_array( $type_rs, MYSQL_ASSOC ) )
		{
// 			echo "HERE " . $type_ar["ATypeID"] . "<br>";
			return $type_ar["ATypeID"];
		}
		
		return -1;
		
	}
	

	// Added May 12/06 by Marina
	/**
	 * Retrieve reagent type ID
	 *
	 * @author Marina Olhovsky
	 * @version 2006-05-12
	 * @return INT
	*/
	function getType($reagentIDToView)
	{
		global $conn;

		if (!isset($_POST["reagent_typeid_hidden"]))
		{
			$type_rs = mysql_query("SELECT `reagentTypeID` FROM `Reagents_tbl` WHERE `status`='ACTIVE' AND `reagentID`='" 
			. $reagentIDToView . "'", $conn);
			
			if ($type_ar = mysql_fetch_array($type_rs, MYSQL_ASSOC))
			{
				$type = $type_ar["reagentTypeID"];
			}
		}
		else
		{
			$type = $_POST["reagent_typeid_hidden"];
		}

		return $type;
	}


	// Aug. 11/09
	/**
	 * Retrieve association properties for the given reagent type (e.g. 'cell line parent vector', 'sense oligo', etc.)
	 *
	 * @author Marina Olhovsky
	 * @return Array
	*/
	function findReagentTypeAssociations($reagenttype_id, $hierarchy="PARENT")
	{
		global $conn;

		$assocList = Array();

		$type_rs = mysql_query("SELECT APropertyID, description FROM `Assoc_Prop_Type_tbl` WHERE `reagentTypeID`='" . $reagenttype_id . "' AND `status`='ACTIVE' AND `hierarchy`='" . $hierarchy . "' ", $conn) or die("Error in findReagentTypeAssociations() function: " . mysql_error());

		while ($type_ar = mysql_fetch_array($type_rs, MYSQL_ASSOC))
		{
			$aPropID = $type_ar["APropertyID"];
			$aPropDescr = $type_ar["description"];

			$assocList[$aPropID] = $aPropDescr;
		}
		
		return $assocList;
	}


	/**
	 * Retrieve association property ID for the given reagent type
	 *
	 * @author Marina Olhovsky
	 * @return INT
	*/
	function get_Association_Property_Type( $name , $reagenttype_id, $hierarchy )
	{
		global $conn;
		$functionerror = "get_Association_Property_Type(";
		
		$name = strtolower($name);

		$type_rs = mysql_query("SELECT * FROM `Assoc_Prop_Type_tbl` WHERE `reagentTypeID`='" . $reagenttype_id . "' AND `status`='ACTIVE' "
		. "AND `APropName`='" . trim($name) . "' AND `hierarchy`='" . $hierarchy . "' ", $conn)
		or die($this->classerror . $functionerror . "13)" . mysql_error());

		if ($type_ar = mysql_fetch_array($type_rs, MYSQL_ASSOC))
		{
			return $type_ar["APropertyID"];
		}
		
		return -1;
	}
	
	/**
	 * Not actively used in the current version
	 * @author John Paul Lee @version 2005
	*/
	function get_Reagent_2nd_Type_ID( $reagentTypeID, $name )
	{
		global $conn;
		$functionerror = "get_Reagent_2nd_Type_ID(";
		
		$type_rs = mysql_query("SELECT * FROM `Reagent_SubType_tbl` WHERE `reagent_typeID`='" . $reagentTypeID . "' AND `name`='" . $name . "' AND `status`='ACTIVE'", $conn )
						or die( $this->classerror . $functionerror . "13)" . mysql_error() );
						
		if( $type_ar = mysql_fetch_array( $type_rs, MYSQL_ASSOC ) )
		{
			return $type_ar["reagent_SubTypeID"];
		}
		
		return -1;
	}
	
	/**
	 * Not actively used in the current version
	 * @author John Paul Lee @version 2005
	*/
	function get_Reagent_3rd_Type_ID( $subtype_id, $name )
	{
		global $conn;
		$functionerror = "get_Reagent_3rd_Type_ID(";
		
		$type_rs = mysql_query("SELECT * FROM `Reagent_3rd_Typing_tbl` WHERE `subtype_id`='" . $subtype_id . "' AND `name`='" . $name . "' AND `status`='ACTIVE'", $conn )
						or die( $this->classerror . $functionerror . "13)" . mysql_error() );
						
		if( $type_ar = mysql_fetch_array( $type_rs, MYSQL_ASSOC ) )
		{
			return $type_ar["3rd_typing_id"];
		}
		
		return -1;
	}
	
	// March 27/08
	/**
	 * Retrieve the database alias of a particular property
	 *
	 * @author Marina Olhovsky
	 * @param STRING
	 * @return STRING
	*/
	function getReagentPropertyAlias($propName)
	{
		global $conn;
		
		$propAlias = "";

		$prop_rs = mysql_query("SELECT propertyAlias FROM ReagentPropType_tbl WHERE propertyName='" . mysql_real_escape_string($propName) . "' AND status='ACTIVE'", $conn) or die("Could not select property alias: " . mysql_error());

		if ($prop_ar = mysql_fetch_array($prop_rs))
		{
			$propAlias = $prop_ar["propertyAlias"];
		}

		return $propAlias;
	}

	// (Comment added May 2/08 by Marina): In plain terms - maps property names to their database alias (returns ReagentPropType_tbl.propertyName => ReagentPropType_tbl.propertyAlias map)
	/**
	 * Map property names to their database aliases
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING
	 * @param STRING
	 * @param STRING
	 *
	 * @return Array
	*/
	function get_Post_Names($reagentType, $subtype="", $extra_type = "")
	{
		if( $reagentType == "CellLine" )
		{
// 			echo "what is this: " . $subtype;
			$cl_name[ "cell line type" ] = "cell_line_type";
			$cl_name[ "name" ] = "name";
			$cl_name[ "alternate id" ] = "alternate_id";
			$cl_name[ "description" ] = "description";
			$cl_name[ "comments" ] = "comments";
			$cl_name[ "packet id" ] = "packet_id";
			$cl_name[ "status" ] = "status";
			$cl_name[ "reagent source" ] = "reagent_source";
			$cl_name[ "restrictions on use" ] = "restrictions_on_use";
			$cl_name[ "species" ] = "species";
			$cl_name[ "developmental stage" ] = "developmental_stage";
			$cl_name[ "selectable marker" ] = "selectable_marker";
			$cl_name[ "tissue type" ] = "tissue_type";
			$cl_name[ "morphology" ] = "morphology";

			// April 7/06, Marina
			$cl_name[ "vector id" ] = "vector_id";
			$cl_name[ "cell line id" ] = "cellline_id";
			
			return $cl_name;
		}
		elseif( $reagentType == "Oligo" ) 
		{
			$ol_name[ "oligo type" ] = "oligo_type";
			$ol_name[ "name" ] = "name";
			$ol_name[ "sequence" ] = "sequence";
// 			$ol_name[ "tm" ] = "tm";
			$ol_name["melting temperature"] = "melting_temperature";
// 			$ol_name[ "mw" ] = "mw";
			$ol_name["molecular weight"] = "molecular_weight";
			$ol_name[ "protocol" ] = "protocol";
			$ol_name[ "accession number" ] = "accession_number";
			$ol_name[ "reagent source" ] = "reagent_source";
			$ol_name[ "description" ] = "description";
			$ol_name[ "comments" ] = "comments";
			$ol_name[ "packet id" ] = "packet_id";		// April 2/07, Marina - changed 'owner' into 'packet id'
			
			return $ol_name;
		}
		else if ($reagentType == "Insert")
		{
			// May 14/08: Special for Primer Design - a subset of properties that have not yet been saved for this Insert
			if ($subtype == "Primer General")
			{
				// Sept. 8/09: Modified return array - instead of mapping property names to aliases map them to categories to display step 4 (Insert Intro from Primer) correctly
				$il_name[ "name" ] = "General Properties";
				$il_name[ "packet id" ] = "General Properties";
				$il_name[ "status" ] = "General Properties";
				
				$il_name[ "description" ] = "General Properties";
				$il_name[ "comments" ] = "General Properties";
				$il_name[ "verification comments" ] = "General Properties";
				$il_name[ "verification" ] = "General Properties";
	
				$il_name[ "alternate id" ] = "External Identifiers";

				$il_name["type of insert"] = "Classifiers";
				$il_name[ "open/closed" ] = "Classifiers";
				$il_name["cloning method"] = "Classifiers";

// 				$il_name[ "name" ] = "name";
// 				$il_name[ "packet id" ] = "packet_id";
// 				$il_name[ "status" ] = "status";
// 				
// 				$il_name[ "description" ] = "description";
// 				$il_name[ "comments" ] = "comments";
// 				$il_name[ "verification comments" ] = "verification_comments";
// 				$il_name[ "verification" ] = "verification";
// 	
// 				$il_name[ "alternate id" ] = "alternate_id";
// 
// 				$il_name["type of insert"] = "insert_type";
// 				$il_name[ "open/closed" ] = "open_closed";
// 				$il_name["insert cloning method"] = "cloning_method";
			}
			// Nov. 17/08
			else if (strcasecmp($subtype, "Cloning Sites") == 0)
			{
				$il_name[ "5' cloning site" ] = "5_prime_cloning_site";
				$il_name[ "3' cloning site" ] = "3_prime_cloning_site";
			}
			else
			{
// may 13/08			$il_name["insert subtype"] = "insert_subtype";
				$il_name[ "name" ] = "name";
				$il_name[ "status" ] = "status";
				$il_name[ "packet id" ] = "packet_id";
				
				$il_name[ "description" ] = "description";
				$il_name[ "comments" ] = "comments";
				$il_name[ "verification comments" ] = "verification_comments";
				$il_name[ "verification" ] = "verification";
	
				$il_name["type of insert"] = "insert_type";
				$il_name[ "open/closed" ] = "open_closed";
				$il_name["cloning method"] = "cloning_method";
				$il_name["species"] = "species";
	
				$il_name[ "accession number" ] = "accession_number";
				$il_name[ "entrez gene id" ] = "entrez_gene_id";
				$il_name[ "ensembl gene id" ] = "ensembl_gene_id";
				$il_name[ "alternate id" ] = "alternate_id";
				
				$il_name[ "official gene symbol" ] = "official_gene_symbol";

/* May 13/08: These have now become features
				$il_name[ "5' start" ] = "5_prime_start";
				$il_name[ "3' stop" ] = "3_prime_stop";
				$il_name[ "5' linker" ] = "5_prime_linker";
				$il_name[ "3' linker" ] = "3_prime_linker";
				$il_name["cdna insert"] = "cdna_insert";
				$il_name[ "sequence" ] = "sequence";
				$il_name[ "tag type" ] = "tag_type";
				$il_name[ "tag position" ] = "tag_position";
	
				// Feb. 26/08: As part of our property redesign, assigning some of the Vector properties to Insert
				$il_name["promoter"] = "promoter";
				$il_name["selectable marker"] = "selectable_marker";
				$il_name["origin"] = "origin";
				$il_name["polyA"] = "polyA";
				$il_name["expression system"] = "expression_system";
				$il_name["miscellaneous"] = "miscellaneous";	// march 18/08
*/
		
// may 13/08			$il_name[ "protocol" ] = "protocol";
			
// may 13/08			$il_name["sense id"] = "sense_id";
// may 13/08			$il_name["anti id"] = "anti_id";
// may 13/08			$il_name["ipv id"] = "ipv_id";
			}

			return $il_name;
		}
		elseif( $reagentType == "Vector" )
		{
			if( $subtype == "General" )
			{
				$vl_name[ "name" ] = "name";
				$vl_name[ "status" ] = "status";
				$vl_name[ "verification" ] = "verification";
				$vl_name[ "verification comments" ] = "verification_comments";
				$vl_name[ "description" ] = "description";
				$vl_name[ "comments" ] = "comments";
				$vl_name[ "reagent source" ] = "reagent_source";
				$vl_name[ "restrictions on use" ] = "restrictions_on_use";
				$vl_name[ "packet id" ] = "packet_id";

				$vl_name[ "vector type" ] = "vector_type";	// Apr 5 - check!

// 				$vl_name["cdna insert"] = "cdna_insert";	// feb. 25/08, marina

				return $vl_name;
			}
			// Feb 11, Marina
			elseif( $subtype == "Cloning Sites" )
			{
				$vl_name["5' cloning site"] = "5' cloning site";
				$vl_name["3' cloning site"] = "3' cloning site";
				return $vl_name;
			}
			elseif( $subtype == "Background" )
			{
				$vl_name[ "name" ] = "name";		// april 8/07, Marina
				$vl_name[ "selectable marker" ] = "selectable_marker";
				$vl_name[ "expression system" ] = "expression_system";
				$vl_name[ "promoter" ] = "promoter";
				$vl_name[ "tag" ] = "tag";
				$vl_name[ "tag position" ] = "tag_position";
// 				$vl_name[ "sequence" ] = "sequence";
				
				return $vl_name;
			}
			elseif( $subtype == "Insert" )
			{
				$il_name["insert subtype"] = "insert_subtype";
				$il_name[ "name" ] = "name";
				$il_name[ "status" ] = "status";
				$il_name[ "open/closed" ] = "open_closed";
				
				// accession number instead?
				$il_name[ "accession number" ] = "accession_number";
				$il_name[ "entrez gene id" ] = "entrez_gene_id";
				$il_name[ "ensemble id" ] = "ensemble_id";
			
				$il_name[ "5' start" ] = "5_prime_start";
				$il_name[ "3' stop" ] = "3_prime_stop";
				$il_name[ "5' cloning site" ] = "5_prime_cloning_site";
				$il_name[ "3' cloning site" ] = "3_prime_cloning_site";
				$il_name[ "5' linker" ] = "5_prime_linker";
				$il_name[ "3' linker" ] = "3_prime_linker";
				
				$il_name[ "protocol" ] = "protocol";
				$il_name[ "sequence" ] = "sequence";
				
				$il_name[ "description" ] = "description";
				$il_name[ "comments" ] = "comments";
				
				$il_name[ "tag" ] = "tag";
				$il_name[ "tag position" ] = "tag_position";
				
				$il_name["sense id"] = "sense_id";
				$il_name["anti id"] = "anti_id";
				$il_name["type of insert"] = "insert_type";

				return $il_name;
			}
			elseif( $subtype == "All" || $subtype == "" )
			{
				$vl_name[ "name" ] = "name";
				
				// special prop
				$vl_name[ "vector type" ] = "vector_type";
				
				$vl_name[ "entrez gene id" ] = "entrez_gene_id";
				$vl_name[ "5' cloning site" ] = "5_prime_cloning_site";
				$vl_name[ "3' cloning site" ] = "3_prime_cloning_site";
				$vl_name[ "comments" ] = "comments";
				$vl_name[ "description" ] = "description";
				$vl_name[ "tag" ] = "tag";
				$vl_name[ "tag position" ] = "tag_position";
				$vl_name[ "antibiotic resistance" ] = "antibiotic_resistance";
				$vl_name[ "reagent source" ] = "reagent_source";
				
				$vl_name[ "verification" ] = "verification";
				$vl_name[ "verification comments" ] = "verification_comments";
				
				$vl_name[ "restrictions on use" ] = "restrictions_on_use";
				$vl_name[ "expression system" ] = "expression_system";
				$vl_name[ "promoter" ] = "promoter";
				
				$il_name[ "protocol" ] = "protocol";
				
				$vl_name[ "status" ] = "status";
				
// july 9/07	$vl_name[ "project id" ] = "project_id";
				$vl_name[ "packet id" ] = "packet_id";

				$vl_name[ "sequence" ] = "sequence";
				
				$vl_name["cdna insert"] = "cdna_insert";

				// April 18/08: Insert features mapped onto Vector
				$vl_name["promoter"] = "promoter";
				$vl_name["selectable marker"] = "selectable_marker";
				$vl_name["origin"] = "origin";
				$vl_name["polya"] = "polyA";
				$vl_name["expression system"] = "expression_system";
				$vl_name["miscellaneous"] = "miscellaneous";

				// added Sept. 18/08
				$vl_name["cleavage site"] = "cleavage_site";
				$vl_name["restriction site"] = "restriction_site";
				$vl_name["transcription terminator"] = "transcription_terminator";

				$vl_name["intron"] = "intron";		// Dec. 10/08

				return $vl_name;
			}
		}
	}

	/**
	 * Determines if the property identified by $pName is a feature descriptor
	 * Returns TRUE if pName is 'tag position' or 'expression system'; FALSE otherwise.
	 *
	 * @author Marina Olhovsky
	 *
	 * @param STRING
	 * @return boolean
	*/
	function isDescriptor($pName)
	{
		switch ($pName)
		{
			case 'expression system':
			case 'tag position':
				return true;
			break;

			default:
				return false;
			break;
		}
	}


	/**
	 * Find the type of parent in the given parent-child association (identified by assocID).  E.g. in assocID = 1, which represents a parent-child relationship between an Insert and its resulting non-recombination vector, this function would return '2', the ID of reagent type 'Insert'.
	 *
	 * @author Marina Olhovsky
	 *
	 * @param INT
	 * @return INT
	*/
	function findAssocParentType($assocID)
	{
		global $conn;

		$assocParentType = 0;

		$assoc_rs = mysql_query("SELECT assocTypeID FROM Assoc_Prop_Type_tbl WHERE APropertyID='" . $assocID . "' AND status='ACTIVE'", $conn) or die("Cannot select assoc parent: " . mysql_error());

		if ($assoc_ar = mysql_fetch_array($assoc_rs, MYSQL_ASSOC))
		{
			$assocParentType = intval($assoc_ar["assocTypeID"]);
		}

		return $assocParentType;
	}


	/**
	 * Is a given property mandatory for a particular reagent type? (obsolete, replaced by reagentTypeAttributes_tbl)
	 *
	 * @author Marina Olhovsky
	 *
	 * @param STRING
	 * @return STRING
	*/
	function isMandatory($rType, $propName)
	{
		switch($rType)
		{
			case 'Vector':
				switch($propName)
				{
					case 'name':
					case 'status':
					case 'packet id':
					case 'vector type':
						return true;
					break;

					default:
						return false;
					break;
				}
			break;

			case 'Insert':
				switch($propName)
				{
					case 'name':
					case 'status':
					case 'packet id':
					case 'type of insert':
					case 'open/closed':
						return true;
					break;

					default:
						return false;
					break;
				}
			break;

			case 'CellLine':
				switch($propName)
				{
					case 'name':
					case 'status':
					case 'packet id':
					case 'cell line type':
						return true;
					break;

					default:
						return false;
					break;
				}
			break;

			default:
				switch($propName)
				{
					case 'name':
					case 'status':
					case 'packet id':
					case strtolower($rType) . " type":
						return true;
					break;

					default:
						return false;
					break;
				}
			break;
		}
	}

	# May 1/08: Special function to define reagent **features**
	# Returns: an associative array of (propertyName => propertyDescription) tuples
	# Update July 8/09
	/**
	 * Special function to define reagent **features**.  Returns: an associative array of (propertyName => propertyDescription) tuples
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2009-07-08
	 *
	 * @param STRING
	 * @return STRING
	*/
	function getReagentFeatures($reagentType, $subtype)
	{
		$vl_name = Array();

		// Sequence types and features are mutually exclusive
		if ($this->hasAttribute($_SESSION["ReagentType_Name_ID"][$reagentType], "protein sequence", $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]))
		{
			// get protein sequence features

			$fCatID = $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence Features"];

			$allFeatures = $this->getReagentTypeAttributesByCategory($_SESSION["ReagentType_Name_ID"][$reagentType], $fCatID);
		}
		else if ($this->hasAttribute($_SESSION["ReagentType_Name_ID"][$reagentType], "rna sequence", $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]))
		{
			$fCatID = $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence Features"];

			// get RNA sequence features
			$allFeatures = $this->getReagentTypeAttributesByCategory($_SESSION["ReagentType_Name_ID"][$reagentType], $fCatID);
		}
		else
		{
			$fCatID = $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"];

			$allFeatures = $this->getReagentTypeAttributesByCategory($_SESSION["ReagentType_Name_ID"][$reagentType], $fCatID);
		}

		foreach ($allFeatures as $key => $feature)
		{
			try
			{
				$fName = strtolower($feature->getPropertyName());
				$fID = $this->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$fName], $fCatID);
			}
			catch (Exception $e)
			{
				$fName = strtolower($feature->getFeatureType());
				$fID = $this->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][$fName], $fCatID);
			}
				
			$vl_name[$fID] = $fName;
		}

		return $vl_name;
	}


	/**
	 * Is the given property identified by $propCatID a sequence feature?
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2009-07-08
	 *
	 * @param INT
	 * @return boolean
	*/
	function isSequenceFeature($propCatID)
	{
		global $conn;
		$categoryID = $this->findPropertyCategoryID($propCatID);

		return ($categoryID == $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]) || ($categoryID == $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence Features"]) || ($categoryID == $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence Features"]);
	}

	// April 11, 2009
	// Since we now customize reagent types, sets of attributes for a given reagent type are retrieved from the database instead of being hard-coded
	/**
	 * Fetch all the attributes of the reagent type identified by $rTypeID.  Since we now customize reagent types, sets of attributes for a given reagent type are retrieved from the database instead of being hard-coded.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2009-04-11
	 *
	 * @param INT
	 * @return Array
	*/
	function getAllReagentTypeAttributes($rTypeID)
	{
		global $conn;

		$rTypeAttributes = Array();

		$query = "SELECT propertyID, propertyName, propertyDesc, propertyAlias, propertyCategoryName FROM ReagentPropType_tbl t, ReagentTypeAttributes_tbl a, ReagentPropTypeCategories_tbl c, ReagentPropertyCategories_tbl pc WHERE a.reagentTypeID='" . $rTypeID . "' AND a.propertyTypeID=t.propertyID AND pc.propID=t.propertyID AND pc.categoryID=c.propertyCategoryID AND a.status='ACTIVE' AND t.status='ACTIVE' AND c.status='ACTIVE' AND pc.status='ACTIVE' ORDER BY a.ordering";

// 		echo $query;
	
		$attributeSet =  mysql_query($query, $conn) or die("Error in getAllReagentTypeAttributes() function: Could not determine attributes for reagent type " . $rTypeID . ": " . mysql_error());

		while ($attributeList = mysql_fetch_array($attributeSet, MYSQL_ASSOC))
		{
			$pID = $attributeList["propertyID"];
			$pName = $attributeList["propertyName"];
			$pDescr = $attributeList["propertyDesc"];
			$pAlias = $attributeList["propertyAlias"];
			$pCategory = $attributeList["propertyCategoryName"];

			$tmpProp = new ReagentProperty($pName, $pAlias, $pDescr, $pCategory);
			$rTypeAttributes[$pID] = $tmpProp;
		}

		return $rTypeAttributes;
	}


	/**
	 * Retrieve all the properties in the category identified by $categoryID.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2009-04-23
	 *
	 * @param INT
	 * @return Array
	*/
	function getPropertiesByCategory($categoryID)
	{
		global $conn;

		$propList = Array();

		$query = "SELECT propertyID, pc.propCatID, propertyName, propertyDesc, propertyAlias, propertyCategoryName FROM ReagentPropType_tbl t, ReagentPropTypeCategories_tbl c, ReagentPropertyCategories_tbl pc WHERE c.propertyCategoryID='" . $categoryID . "' AND t.propertyID=pc.propID AND pc.categoryID=c.propertyCategoryID AND t.status='ACTIVE' AND c.status='ACTIVE' AND pc.status='ACTIVE' ORDER by t.ordering";

		$propSet =  mysql_query($query, $conn) or die("Could not select property categories: " . mysql_error());

		while ($props = mysql_fetch_array($propSet, MYSQL_ASSOC))
		{
			$pID = $props["propCatID"];
			$pName = $props["propertyName"];
			$pDescr = $props["propertyDesc"];
			$pAlias = $props["propertyAlias"];
			$pCategory = $props["propertyCategoryName"];
			$tmpProp = new ReagentProperty($pName, $pAlias, $pDescr, $pCategory);
			$propList[$pID] = $tmpProp;
		}

		return $propList;
	}


	/**
	 * Is this property customizable, i.e. can a user add new values to pre-defined list of property values on the fly for a particular reagent type?
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-05-11
	 *
	 * @param INT
	 * @return boolean
	*/
	function isCustomizeable($rTypeAttrID)
	{
		global $conn;
	
		$results_rs = mysql_query("SELECT is_customizeable FROM ReagentTypeAttributes_tbl WHERE reagentTypePropertyID='" . $rTypeAttrID . "' AND status='ACTIVE'");
	
		if ($results_ar = mysql_fetch_array($results_rs, MYSQL_ASSOC))
		{
			$is_customizeable = $results_ar["is_customizeable"];
	
			if (strcasecmp($is_customizeable, 'YES') == 0)
				return true;
			else
				return false;
		}
	
		return false;
	}

	/**
	 * Is this property multiple, i.e. can a user select more than one value for this property from a pre-defined list of values for a particular reagent type?
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-05-05
	 *
	 * @param INT
	 * @return boolean
	*/
	function isMultiple($rTypeAttrID)
	{
		global $conn;
	
		$results_rs = mysql_query("SELECT is_multiple FROM ReagentTypeAttributes_tbl WHERE reagentTypePropertyID='" . $rTypeAttrID . "' AND status='ACTIVE'");
	
		if ($results_ar = mysql_fetch_array($results_rs, MYSQL_ASSOC))
		{
			$is_multiple = $results_ar["is_multiple"];
	
			if (strcasecmp($is_multiple, 'YES') == 0)
				return true;
			else
				return false;
		}
	
		return false;
	}


	/**
	 * Is this property a hyperlink, i.e. should it be printed as a clickable hyperlink on a reagent detailed view?
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-05-05
	 *
	 * @param INT
	 * @return boolean
	*/
	function isHyperlink($rTypeAttrID)
	{
		global $conn;
	
		$results_rs = mysql_query("SELECT is_hyperlink FROM ReagentTypeAttributes_tbl WHERE reagentTypePropertyID='" . $rTypeAttrID . "' AND status='ACTIVE'");
	
		if ($results_ar = mysql_fetch_array($results_rs, MYSQL_ASSOC))
		{
	
			$is_hyperlink = $results_ar["is_hyperlink"];
		
			if (strcasecmp($is_hyperlink, 'YES') == 0)
				return true;
			else
				return false;
		}
	
		return false;
	}


	/**
	 * Find ALL the attributes in the given category assigned to a particular reagent **type**
	 *
	 * @author Marina Olhovsky
	 *
	 * @param INT
	 * @param INT
	 * @return Array
	*/
	function getReagentTypeAttributesByCategory($rTypeID, $categoryID)
	{
		global $conn;

		$rTypeAttributes = Array();

		$query = "SELECT reagentTypePropertyID, propCatID, propertyName, propertyDesc, propertyAlias, propertyCategoryName, t.ordering FROM ReagentPropType_tbl t, ReagentTypeAttributes_tbl a, ReagentPropTypeCategories_tbl c, ReagentPropertyCategories_tbl pc WHERE a.reagentTypeID='" . $rTypeID . "' AND  c.propertyCategoryID='" . $categoryID . "' AND pc.categoryID=c.propertyCategoryID AND a.propertyTypeID=pc.propCatID AND t.propertyID=pc.propID AND a.status='ACTIVE' AND t.status='ACTIVE' AND c.status='ACTIVE' AND pc.status='ACTIVE' ORDER BY a.ordering";

		$attributeSet = mysql_query($query, $conn) or die("Error in getReagentTypeAttributesByCategory() function: Could not determine attributes for reagent type " . $rTypeID . ": " . mysql_error());

		while ($attributeList = mysql_fetch_array($attributeSet, MYSQL_ASSOC))
		{
			$attrID = $attributeList["reagentTypePropertyID"];	// update May 5, 2010

			$pID = $attributeList["propCatID"];
			$pName = $attributeList["propertyName"];
			$pDescr = $attributeList["propertyDesc"];
			$pAlias = $attributeList["propertyAlias"];
			$pCategory = $attributeList["propertyCategoryName"];

			$tmpProp = new ReagentProperty($pName, $pAlias, $pDescr, $pCategory);

			// NO!!  Removed May 5, 2010 - don't do this, decouple database from code objects!!  Can find propCatID later if needed from propName and categoryName!!
// 			$tmpProp->setPropertyID($pID);		// Nov 3/09, this is propCatID

// rmvd May 5/10	$rTypeAttributes[$pID] = $tmpProp;
			$rTypeAttributes[$attrID] = $tmpProp;		// replaced May 5, 2010
		}

		return $rTypeAttributes;
	}


	/**
	 * Find the property ID of a particular reagent type attribute. ** Returns the value of propertyTypeID column (result of several JOINs) **
	 *
	 * @author Marina Olhovsky
	 *
	 * @param INT
	 * @param STRING
	 * @param INT
	 * @return INT
	*/
	function findReagentTypeAttributeID($reagentType, $attrName, $categoryID)
	{
		global $conn;

		$pID = 0;

		if (strpos($attrName, "'") >= 0)
			$attrName = addslashes($attrName);

		$query = "SELECT pc.propCatID FROM ReagentTypeAttributes_tbl a, ReagentPropType_tbl t, ReagentPropertyCategories_tbl pc WHERE a.reagentTypeID='" . $reagentType . "' AND t.propertyName='" . $attrName . "' AND t.propertyID=pc.propID AND pc.categoryID='" . $categoryID . "' AND a.propertyTypeID=pc.propCatID AND a.status='ACTIVE' AND t.status='ACTIVE' AND pc.status='ACTIVE'";

		$attributeSet = mysql_query($query, $conn) or die("Error in findReagentTypeAttributeID() function: " . mysql_error());

		while ($attributeList = mysql_fetch_array($attributeSet, MYSQL_ASSOC))
		{
			$pID = $attributeList["propCatID"];
		}

		return $pID;
	}


	/**
	 * Clone of findReagentTypeAttributeID, only returns the value of reagentTypePropertyID column as opposed to propertyTypeID
	 *
	 * @author Marina Olhovsky
	 *
	 * @param INT
	 * @param STRING
	 * @param INT
	 * @return INT
	*/
	function getRTypeAttributeID($reagentType, $attrName, $categoryID)
	{
		global $conn;

		$propCatID = $this->findReagentTypeAttributeID($reagentType, $attrName, $categoryID);

		$pID = 0;

		$query = "SELECT reagentTypePropertyID FROM ReagentTypeAttributes_tbl WHERE reagentTypeID='" . $reagentType . "' AND propertyTypeID='" . $propCatID . "' AND status='ACTIVE'";

		$attributeSet = mysql_query($query, $conn) or die("Error in getRTypeAttributeID() function: " . mysql_error());

		while ($attributeList = mysql_fetch_array($attributeSet, MYSQL_ASSOC))
		{
			$pID = $attributeList["reagentTypePropertyID"];
		}

		return $pID;
	}


	/**
	 * Checks whether a reagent type has a specific attribute within a certain category
	 *
	 * @author Marina Olhovsky
	 *
	 * @param INT
	 * @param STRING
	 * @param INT
	 * @return boolean
	*/
	function hasAttribute($reagentType, $attrName, $catID)
	{
		return ($this->findReagentTypeAttributeID($reagentType, $attrName, $catID) > 0);
	}


	/**
	 * Retrieve the ordering of a property for a given reagent type
	 *
	 * @author Marina Olhovsky
	 *
	 * @param INT
	 * @param INT
	 * @return INT
	*/
	function getReagentTypePropertyOrdering($rTypeID, $propCatID)
	{
		global $conn;

		$pOrder = 0;	// don't use 2147483647

		$propOrder_rs = mysql_query("SELECT ordering FROM ReagentTypeAttributes_tbl WHERE reagentTypeID='" . $rTypeID . "' AND propertyTypeID='" . $propCatID . "' AND status='ACTIVE'");

		if ($propOrder_ar = mysql_fetch_array($propOrder_rs, MYSQL_ASSOC))
		{
			$pOrder = $propOrder_ar["ordering"];
		}

		return $pOrder;
	}


	/**
	 * Find a specific reagent's properties and their values in a particular category (only returns non-empty values).
	 *
	 * @author Marina Olhovsky
	 * @version 2009-05-22
	 *
	 * @param INT
	 * @param INT
	 * @return INT
	*/
	function findReagentPropertiesByCategory($rID, $categoryID)
	{
		global $conn;

		$rTypeAttributes = Array();

		$pCategory = $_SESSION["ReagentPropCategory_ID_Name"][$categoryID];

		// Update June 23/09: 'propertyID' column in ReagentPropList_tbl no longer refers to propertyID in ReagentPropType_tbl BUT to propCatID column in ReagentPropertyCategories_tbl - from now on, properties are taken in the context of their category!!
		$query = "SELECT pc.propCatID, p.propertyValue, t.propertyName, t.propertyAlias, t.propertyDesc, pc.categoryID, p.startPos, p.endPos, p.direction, p.descriptor FROM `ReagentPropList_tbl` p, ReagentPropType_tbl t, ReagentPropertyCategories_tbl pc WHERE p.`status`='ACTIVE' AND p.`reagentID`='" . $rID . "' AND t.propertyID=pc.propID AND pc.categoryID='" . $categoryID . "' AND p.propertyID=pc.propCatID AND t.`status`='ACTIVE'";

//  echo $query;

		$attributeSet = mysql_query($query, $conn) or die("Error in findReagentPropertiesByCategory() function: Could not determine attributes for reagent type " . $rTypeID . ": " . mysql_error());

		while ($attributeList = mysql_fetch_array($attributeSet, MYSQL_ASSOC))
		{
			$propCatID = $attributeList["propCatID"];
			$pName = $attributeList["propertyName"];
			$pVal = $attributeList["propertyValue"];
			$pDescr = $attributeList["propertyDesc"];
			$pAlias = $attributeList["propertyAlias"];
			$pStart = $attributeList["startPos"];
			$pEnd = $attributeList["endPos"];
			$pDirection = $attributeList["direction"];
			$pDescriptor = $attributeList["descriptor"];
			$categoryID = $attributeList["categoryID"];

// Aug. 21/09: NO!!!!!!!!!!!!!!!!!!
// 'Origin' can become both classifier and feature for a reagent, and it's not enough to just search by name to find out if it is a feature; need category!!!!!!!
// 			if (!$this->isSequenceFeature($rID, $pName))

			if ( ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence Features"]) && ($categoryID != $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence Features"]))
			{
				$tmpProp = new ReagentProperty($pName, $pAlias, $pDescr, $pCategory, $pVal);
// print_r($tmpProp);

// $tmp_ar = split(",", $pVal);
// 
// if (count($tmp_ar) > 1)

			}
			else
			{
				$tmpProp = new SeqFeature($pName, $pVal, $pStart, $pEnd, $pDirection, $pDescriptor, $pDescr);	// category is 'features' - might need to differentiate by sequence type later
			}

			if (in_array($propCatID, array_keys($rTypeAttributes)))
				$tmp_attr = $rTypeAttributes[$propCatID];
			else
				$tmp_attr = array();

			$tmp_attr[] = $tmpProp;
			$rTypeAttributes[$propCatID] = $tmp_attr;
		}

//		print_r($rTypeAttributes);

		return $rTypeAttributes;
	}

	/**
	 * Find the pre-defined list values of a particular reagent type attribute (e.g. 'origin' for Insert in category 'classifiers').
	 *
	 * @author Marina Olhovsky
	 *
	 * @param INT
	 * @return INT
	*/
	function getAllReagentTypeAttributeSetValues($attrID)
	{
		global $conn;
		$attrSet = array();
	
		$rs_2 = mysql_query("SELECT s.entityName FROM ReagentTypeAttribute_Set_tbl r, System_Set_tbl s WHERE r.reagentTypeAttributeID='" . $attrID . "' AND s.ssetID=r.ssetID AND s.status='ACTIVE' AND r.status='ACTIVE' ORDER BY s.entityName", $conn) or die("FAILURE IN Reagent_Creator_Class.print_set(1): " . mysql_error());
	
		while ($set_ar = mysql_fetch_array($rs_2 , MYSQL_ASSOC))
		{
			$attrSet[] = $set_ar["entityName"];
		}
	
		return $attrSet;
	}


	/**
	 * Given a property and a category, find propCatID
	 *
	 * @author Marina Olhovsky
	 *
	 * @param INT
	 * @return INT
	*/
	function getPropertyIDInCategory($propID, $categoryID)
	{
		global $conn;
		$propCatID = 0;

		$query = "SELECT propCatID FROM ReagentPropertyCategories_tbl WHERE propID='" . $propID . "' AND categoryID='" . $categoryID . "' AND status='ACTIVE'";
		$prop_rs = mysql_query($query, $conn) or die("Error in getPropertyIDInCategory() function: " . mysql_error());
// echo $query;

		if ($prop_ar = mysql_fetch_array($prop_rs, MYSQL_ASSOC))
		{
			$propCatID = $prop_ar["propCatID"];
		}

		return $propCatID;
	}


	/**
	 * Given the value of propCatID column in ReagentPropertyCategories_tbl find the PROPERTY ID, i.e. the value of **propID** column
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2009-06-30
	 *
	 * @param INT
 	 * @return INT
	*/
	function findPropertyInCategoryID($propCatID)
	{
		global $conn;
		$propID = 0;

		$query = "SELECT propID FROM ReagentPropertyCategories_tbl WHERE propCatID='" . $propCatID . "' AND status='ACTIVE'";
// echo $query;
		$prop_rs = mysql_query($query, $conn) or die("Error in findPropertyInCategoryID() function: " . mysql_error());

		if ($prop_ar = mysql_fetch_array($prop_rs, MYSQL_ASSOC))
		{
			$propID = $prop_ar["propID"];
		}

		return $propID;
	}


	/**
	 * Given the value of propCatID column in ReagentPropertyCategories_tbl, find the CATEGORY ID, i.e. the value of **categoryID** column
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2009-06-30
	 *
	 * @param INT
 	 * @return INT
	*/
	function findPropertyCategoryID($propCatID)
	{
		global $conn;
		$categoryID = 0;

		$query = "SELECT categoryID FROM ReagentPropertyCategories_tbl WHERE propCatID='" . $propCatID . "' AND status='ACTIVE'";
// echo $query;
		$prop_rs = mysql_query($query, $conn) or die("Error in findPropertyInCategoryID() function: " . mysql_error());

		if ($prop_ar = mysql_fetch_array($prop_rs, MYSQL_ASSOC))
		{
			$categoryID = $prop_ar["categoryID"];
		}

		return $categoryID;

	}

	/**
	 * Find all attribute categories for a given reagent type
	 *
	 * @author Marina Olhovsky
	 *
	 * @param INT
 	 * @return Array
	*/
	function findAllReagentTypeAttributeCategories($rTypeID)
	{
		global $conn;
		$categories = array();

		$query = "select propertyCategoryID, propertyCategoryName from ReagentPropTypeCategories_tbl where propertyCategoryID in (select distinct(categoryID) from ReagentPropertyCategories_tbl where propCatID in (select distinct(propertyTypeID) from ReagentTypeAttributes_tbl where reagentTypeID='" . $rTypeID . "' AND status='ACTIVE') AND status='ACTIVE') ORDER BY propertyCategoryID";

//echo $query;

		$categories_rs = mysql_query($query, $conn) or die("Error in findAllReagentTypeAttributeCategories() function: Could not determine attributes for reagent type " . $rTypeID . ": " . mysql_error());

		while ($categories_ar = mysql_fetch_array($categories_rs, MYSQL_ASSOC))
		{
			$categoryID = $categories_ar["propertyCategoryID"];
			$categories[$categoryID] = $categories_ar["propertyCategoryName"];
		}

		return $categories;
	}


	/**
	 * Find the cloning method, i.e. the association type ID, of a given **reagent** (the value of ATypeID column in Association_tbl)
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2008-05-31
	 *
	 * @param INT
 	 * @return Array
	*/
	function getCloningMethod($reagentIDToView)
	{
		global $conn;

		$query = "SELECT * FROM `Association_tbl` WHERE `reagentID`='" . $reagentIDToView . "' AND `status`='ACTIVE'";
		$cloning_method_rs = mysql_query($query, $conn) or die("Could not determine cloning method: " . mysql_error());

		if ($cloning_method_ar = mysql_fetch_array($cloning_method_rs, MYSQL_ASSOC))
			$cloningMethod = $cloning_method_ar["ATypeID"];
		else
			$cloningMethod = 3;	// hard-code BASIC cloning method - parent vector

		mysql_free_result($cloning_method_rs);
		unset($cloning_method_ar);

		return $cloningMethod;
	}


	/**
	 * Return a map of current reagent features in the form of (featureID => Feature[]) tuples, where Feature is a SeqFeature **OBJECT**; there may be multiple values per feature ID
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2008-05-31
	 *
	 * @param INT
 	 * @return Array
	*/
	function getCurrentFeatures($rID)
	{
		global $conn;

		return $this->findReagentPropertiesByCategory($rID, $_SESSION["ReagentPropCategory_Name_ID"]["Sequence Features"]);
	}

	/**
	 * Escape single quotes by preceding them with another single quote for db insertion.  REPLACES PHP built-in function $this->addQuotes, which was storing values with backslashes
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-09-07
	 *
	 * @param STRING
 	 * @return STRING
	*/
	function addQuotes($myString)
	{
		return str_replace("'", "''", $myString);
	}

	/**
	 * Add spaces to PROTEIN sequence - every 10 characters
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-09-08
	 *
	 * @param STRING
 	 * @return STRING
	*/
	function spaces($seq)
	{
		$chunk = "";
		$seq_out = "";
		$start = 0;

// 		while ($start < strlen($seq))
// 		{
// 			$seq_out .= substr($seq, $start, 10) . " ";
// 			$start += 10;
// 		}

		if (strlen($seq) <= 100)
		{
			while ($start < strlen($seq))
			{
				$seq_out .= substr($seq, $start, 10) . " ";
				$start += 10;
			}
		}
		else
		{
			while ($start < strlen($seq))
			{
				$chunk .= substr($seq, $start, 10) . " ";
	
				if (strlen($chunk) == 110)
				{
// 					$chunk .= "<BR>";
					$chunk .= "\n";
					$seq_out .= $chunk;
					$chunk = "";
				}
	
				$start += 10;
			}

			$seq_out .= $chunk;
		}

		return $seq_out;
	}


	/**
	 * Space out DNA sequence in chunks of 3.
	 * Updated Jan. 30/08: If there is a linker, change width accordingly.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-09-08
	 *
	 * @param STRING
	 * @param STRING
	 * @param STRING
	 *
 	 * @return STRING
	*/
	function dnaSpace($dnaSeq, $fwd_linker="", $rev_linker="")
	{
		$chunk = "";
		$seq_out = $fwd_linker;
		$start = 0;

		$fwd_linkerLen = strlen($fwd_linker);	// jan. 30/08
		$rev_linkerLen = strlen($rev_linker);	// jan. 31/08

		if (strlen($dnaSeq) <= 100)
		{
			while ($start < strlen($dnaSeq))
			{
				$seq_out .= substr($dnaSeq, $start, 3) . " ";
				$start += 3;
			}
		}
		else
		{
			while ($start < strlen($dnaSeq))
			{
				$chunk .= substr($dnaSeq, $start, 3) . " ";

				if ($seq_out == $fwd_linker)
				{
					if ((strlen($chunk)+$fwd_linkerLen) == 108)
					{
						$chunk .= "\n";
						$seq_out .= $chunk;
						$chunk = "";
					}
				}
				else
				{
					if (strlen($chunk) == 108)
					{
						$chunk .= "\n";
						$seq_out .= $chunk;
						$chunk = "";
					}
				}
				$start += 3;
			}

			$seq_out .= $chunk;
		}

		$seq_out .= $rev_linker;

		return $seq_out;
	}


	/**
	 * Reverse translate protein sequence back to cDNA.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-09-08
	 *
	 * @param STRING
	 * @param INT
	 * @param INT
	 * @param INT
	 * @param INT
	 * @param INT
	 *
 	 * @return STRING
	*/
	function reverseTranslate($protSeq, $rID, $frame, $startpos, $endpos, $protLen)
	{
		global $conn;

// 		echo "prot seq: " . $protSeq . "<BR>";
// 		echo "rid: " . $rID . "<BR>";
// 		echo "frame: " . $frame . "<BR>";
//  		echo "start: " . $startpos . "<BR>";
// 		echo "length: " . $protLen . "<BR>";

// june 5/08	$dnaLen = $protLen * 3;
		$dnaSeqPropID = $this->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);

// echo $dnaSeqPropID;

		$seqRS = mysql_query("SELECT s.`sequence`, s.`start`, s.`end` FROM `Sequences_tbl` s, `ReagentPropList_tbl` r WHERE r.`reagentID`='" . $rID . "' AND r.`propertyID`='" . $dnaSeqPropID . "' AND s.`seqID`=r.`propertyValue` AND r.`status`='ACTIVE' AND s.`status`='ACTIVE'", $conn) or die("Could not select sequence: " . mysql_error());

// echo "SELECT s.`sequence`, s.`start`, s.`end` FROM `Sequences_tbl` s, `ReagentPropList_tbl` r WHERE r.`reagentID`='" . $rID . "' AND r.`propertyID`='23' AND s.`seqID`=r.`propertyValue` AND r.`status`='ACTIVE' AND s.`status`='ACTIVE'";

		if ($seqResult = mysql_fetch_array($seqRS, MYSQL_ASSOC))
		{
			$dnaSeq = strtoupper($seqResult["sequence"]);
		
// june 5/08		$cDNA = substr($dnaSeq, $startpos + $frame - 1, $dnaLen);
		}

		// June 5/08
		$cDNA = substr($dnaSeq, $startpos-1, $protLen*3);
// 		echo "cdna " . $cDNA;

		return $cDNA;
	}

	/**
	 * Calculate Tm for primers.
	 * CAUTION: substr_count IS CASE-SENSITIVE!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!  That's why Tm with linkers, where $seq arg contained mixed-case strings was returning only the number of occurrences of G and C in the upper-case PRIMER, IGNORNING THE LINKER.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-09-08
	 *
	 * @param STRING
 	 * @return FLOAT
	*/
	function calcTemp($seq)
	{
		if (strlen($seq) == 0)
		{
			return null;
		}

		$seq = strtoupper($seq);		# Sept 22/06 -- CRUCIAL, to make the string uniform case

// 		print "seq " . $seq . "<BR>";
// 		print "G content: " . floatval(substr_count($seq, 'G')) . "<BR>";
// 		print "C content: " . floatval(substr_count($seq, 'C')) . "<BR>";
// 		print "Length " . floatval(strlen($seq)) . "<BR>";

		# Modified Sept 22/06 - Borrowed Karen's code and included the length < 14 restriction
		# If length(seq) < 14, a different computation rule applies
		$seqLength = floatval(strlen($seq));

		$g_count = floatval(substr_count($seq, 'G'));
		$c_count = floatval(substr_count($seq, 'C'));
		$a_count = floatval(substr_count($seq, 'A'));
		$t_count = floatval(substr_count($seq, 'T'));

		if ($seqLength < 14.0)
		{
			$temp = 2 * ($a_count + $t_count) + 4 * ($g_count + $c_count);
		}
		else
		{
			$temp = 64.9 + 41.0 * ($g_count + $c_count - 16.4) / $seqLength;
		}

		return round($temp, 2);
	}


	/**
	 * Calculate the GC% content of a sequence.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-06-18
	 *
	 * @param STRING
 	 * @return FLOAT
	*/
	function get_gc_content($seq)
	{
		if (!$seq || (strlen(trim($seq)) == 0))
		{
			return 0;
		}

		$seq = strtoupper($seq);

		$g_count = substr_count($seq, 'G');
		$c_count = substr_count($seq, 'C');

		$gc_count = $g_count + $c_count;

		$gc_content = floatval($gc_count) / floatval(strlen($seq)) * 100;

		return round($gc_content, 2);
	}


	/**
	 * Calculate molecular weight of a DNA sequence (Marina: reused Karen's code).
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-09-22
	 *
	 * @param STRING
 	 * @return FLOAT
	*/
	function get_mw($seq)
	{
		if (!$seq || (strlen(trim($seq)) == 0))
		{
			return 0;
		}

		$seq = strtoupper($seq);

		# Constant MWs of individual bases
		$MW_A = 313.209;
		$MW_G = 329.208;
		$MW_C = 289.184;
		$MW_T = 304.196;

		$a_count = floatval(substr_count($seq, 'A'));
// 		print "A " . $a_count . ", mw " . $MW_A . "<BR>";
		$t_count = floatval(substr_count($seq, 'T'));
// 		print "T " . $t_count . ", mw " . $MW_T . "<BR>";
		$g_count = floatval(substr_count($seq, 'G'));
// 		print "G " . $g_count . ", mw " . $MW_G . "<BR>";
		$c_count = floatval(substr_count($seq, 'C'));
// 		print "C " . $c_count . ", mw " . $MW_C . "<BR>";

		$a_weight = $a_count * $MW_A;
// 		print $a_weight . "<BR>";
		$c_weight = $c_count * $MW_C;
// 		print $c_weight . "<BR>";
		$g_weight = $g_count * $MW_G;
// 		print $g_weight . "<BR>";
		$t_weight = $t_count * $MW_T;
// 		print $t_weight . "<BR>";

		$base_weight = floatval($a_weight) + floatval($c_weight) + floatval($g_weight) + floatval($t_weight);
// 		print $base_weight . "<BR>";
		$mw = $base_weight - 61.964;
// 		print "MW " . $mw . "<BR>";
		return round($mw, 2);
	}


	/**
	 * Calculate molecular weight of a RNA sequence.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010
	 *
	 * @param STRING
 	 * @return FLOAT
	*/
	function getRNA_MW($seq)
	{
		if (!$seq || (strlen(trim($seq)) == 0))
		{
			return 0;
		}

		$seq = strtoupper($seq);
// echo $seq;
		# Constant MWs of individual bases
		$MW_A = 329.21;
		$MW_G = 345.21;
		$MW_C = 305.18;
		$MW_U = 306.17;

		$a_count = floatval(substr_count($seq, 'A'));
// 		print "A " . $a_count . ", mw " . $MW_A . "<BR>";
		$u_count = floatval(substr_count($seq, 'U'));
// 		print "U " . $u_count . ", mw " . $MW_U . "<BR>";
		$g_count = floatval(substr_count($seq, 'G'));
// 		print "G " . $g_count . ", mw " . $MW_G . "<BR>";
		$c_count = floatval(substr_count($seq, 'C'));
// 		print "C " . $c_count . ", mw " . $MW_C . "<BR>";

		$a_weight = $a_count * $MW_A;
// 		print $a_weight . "<BR>";
		$c_weight = $c_count * $MW_C;
// 		print $c_weight . "<BR>";
		$g_weight = $g_count * $MW_G;
// 		print $g_weight . "<BR>";
		$u_weight = $u_count * $MW_U;
// 		print $u_weight . "<BR>";

		$base_weight = floatval($a_weight) + floatval($c_weight) + floatval($g_weight) + floatval($u_weight);
// 		print $base_weight . "<BR>";
		$mw = $base_weight + 159.0;
// 		print "MW " . $mw . "<BR>";
		return round($mw, 2);
	}


	/**
	 * Calculate molecular weight of a Protein sequence.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2010-01-22
	 *
	 * @param STRING
 	 * @return FLOAT
	*/
	function computeProteinMW($proteinSequence)
	{
		$peptideMassDict = Array('A'=>71.08, 'C'=>103.14, 'D'=>115.09, 'E'=>129.12, 'F'=>147.18, 'G'=>57.05, 'H'=>137.14, 'I'=>113.16, 'K'=>128.17, 'L'=>113.16, 'M'=>131.19, 'N'=>114.1, 'P'=>97.12, 'Q'=>128.13, 'R'=>156.19, 'S'=>87.08, 'T'=>101.11, 'V'=>99.13, 'W'=>186.21, 'Y'=>163.18);

		$mw = 0.0;
		$pm = 0.0;

		if (strlen($proteinSequence) == 0)
			return 0.0;

		else if (strpos(strtoupper($proteinSequence), 'X') >= 0)
			return 0.0;

		for ($i=0; $i < strlen($proteinSequence); $i++)
		{
			$aa = strtoupper(substr($proteinSequence, $i, 1));

			if ($aa == "*")
				break;

			else if ($aa == 'B')
			{
				$pm = $peptideMassDict['D'];
				$mw += $pm;
			}

			else if ($aa == 'Z')
			{
				$pm = $peptideMassDict['E'];
				$mw += $pm;
			}

			if (in_array($aa, array_keys($peptideMassDict)))
			{
				$pm = $peptideMassDict[$aa];
				$mw += $pm;
			}
		}

		// add water weight - ROUGHLY 18
		$mw += 18.0;

		return round($mw / 1000.0, 2);
	}


	/**
	 * Extract the desired portion of cDNA to design primers for.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-09-08
	 *
	 * @param STRING
	 * @param INT
	 * @param INT
	 *
 	 * @return STRING
	*/
	function getSeqToClone($nt_seq, $begin, $end)
	{
		$nt_seq = strtoupper($nt_seq);
		$index = 0;				// 10/05/06
// 10/05/06	$index = strpos($nt_seq, "ATG");
		$tmp_start = $index + ($begin-1)*3;
		$tmp_end = $index + $end*3;
		$tmp_len = $tmp_end - $tmp_start;
		$tar_nt_seq = substr($nt_seq, $tmp_start, $tmp_len);
// 		print "Target Nt seq to clone: " . $tar_nt_seq . "<BR>";

		return $tar_nt_seq;
	}


	/**
	 * Modified Sept 12/06 to restrict primer size first by custom length, then by Tm; begin and end are PROTEIN sequence indices.
	 * Comment added Dec. 15/08: Limiting primer size:
	 * If both primer length and Tm are specified, the algorithm stops primer assembly at 'length' AS LONG AS Tm HAS NOT BEEN REACHED.  If Tm is reached BEFORE length, primer assembly STOPS, EVEN IF 'length' is not reached at this point!!! (E.g. I85472: Choose 5' linker "V7-based w/ stop codon", limit length to 27 and leave Tm at default 55.  The final length (incl. linker) ends up being 22 BECAUSE Tm 55 is too low for a primer of length 27.  If want to limit length to 27, increase Tm to e.g. 80 - then the final primer would be 27 nt long.
	 *
	 * @author Marina Olhovsky
	 * @version 3.1 2006-09-12
	 *
	 * @param STRING
	 * @param INT
	 * @param INT
	 * @param STRING
	 * @param STRING
	 * @param INT
	 * @param INT
	 * @param INT
	 * @param INT
	 *
 	 * @return Array containing forward and reverse primers
	*/
	function getPrimers($nt_seq, $begin, $end, $fwdLinker, $revLinker, $maxFwdLen, $maxRevLen, $fwdTm, $revTm)
	{
// 		print "Input Nt seq " . $nt_seq . ", start " . $begin . ", end " . $end . "<BR>";
		$nt_seq = strtoupper($nt_seq);
//		$r_table = array();
// 		print "Linkers: 5' " . $fwdLinker . " 3' " . $revLinker . "<BR>";
		$r_table["C"] = "G";
		$r_table["G"] = "C";
		$r_table["A"] = "T";
		$r_table["T"] = "A";

		$fwd_primer = "";
		$rev_primer = "";
		$opt_primer = "";

		$temp_fwd = array();
		$temp_rev = array();
		$primers = array();
		
 		$tar_nt_seq = $this->getSeqToClone($nt_seq, $begin, $end);		// the sequence we want to clone
 		//print "tar seq " . $tar_nt_seq . "<BR>";
 		//print "fwd tm " . $fwdTm . "<BR>";
 		//print "fwd length " . $maxFwdLen . "<BR>";
// 		print "length of 5' linker " . strlen($fwdLinker) . "<Br>";
// 		print "length of 3' linker " . strlen($revLinker) . "<Br>";

		# forward primer
// 		$min_diff = 100;

		$min_len = 10;			// 21/9/06
		$go_on = true;

// 		for ($i = 10; $i <= 50; $i++)
		while ($go_on)
		{
// 21/09/06		$primer = substr($tar_nt_seq, 0, $i+1);
			$primer = substr($tar_nt_seq, 0, $min_len+1);			// 21/09/06
 			//print "start loop, primer " . $primer . "<BR>";
			$temp = $this->calcTemp($primer);
 			//echo "temp " . $temp . "<BR>";

			# CHECK LENGTH FIRST
			if (intval(strlen($primer) + strlen($fwdLinker)) <= $maxFwdLen)		// 12/09/06
			{
 				//print "Current length of primer only: " . strlen($primer) . "<BR>";
 				//print "Current length of linker only: " . strlen($fwdLinker) . "<BR>";
 				//print "Current length of primer with linker: " . intval(strlen($primer) + strlen($fwdLinker)) . "<BR>";

				if ($temp <= $fwdTm)		// 12/09/06
				{
 					//echo "temp < set; primer: " . $primer . ", temp " . $temp . "<BR>";
// 					$min_diff = abs($temp - 55.0);
					$opt_primer = $primer;
					$opt_temp = $temp;
					$min_len++;
				}
				else
				{
 					/*echo "primer: " . $primer . ", temp " . $temp . "<BR>";
 					print "Final length of primer only: " . strlen($primer) . "<BR>";
 					print "Final length of linker only: " . strlen($fwdLinker) . "<BR>";
 					print "Final length of primer with linker: " . strlen($primer) + strlen($fwdLinker) . "<BR>";*/

					# First primer at temp > 55, record it and exit
					$opt_primer = $primer;
					$opt_temp = $temp;
// 					break;
					$go_on = false;
				}
			}
			else
			{
				// Updated Nov. 9/06
				if (strlen($maxFwdLen) == 0)
				{
					//  Max length is not set, go by temp
					if ($temp <= $fwdTm)
					{
						//echo "temp " . $temp . "<BR>";
						//echo "length not set, going by temp<BR>";
// 						$min_diff = abs($temp - 55.0);
						$opt_primer = $primer;
						$opt_temp = $temp;
						$min_len++;
					}
					else
					{
						//echo "Now exceeded max temp; primer: " . $primer . ", temp " . $temp . "<BR>";
						$opt_primer = $primer;
						$opt_temp = $temp;
						$go_on = false;
					}
				}
				else
				{
					//echo "Now exceeded max length; primer: " . $primer . ", temp " . $temp . "<BR>";
					$go_on = false;
				}
			}
		}

 		//print "Opt primer selected " . $opt_primer . ", temp " . $opt_temp ."<BR>";
		$fwd_primer = chunk_split($opt_primer, 3, " ");
		$temp_fwd[$fwd_primer] = $opt_temp;
		$primers[0] = $temp_fwd;

		# reverse primer
// 		$min_diff = 100;
//		echo $revLinker . "<BR>";

		for ($i = 10; $i <= 50; $i++)
		{
			$primer = substr($tar_nt_seq, -$i);
//			print $primer . "<BR>";

			$temp = $this->calcTemp($primer);
// 			echo "temp " . $temp . "<BR>";

			if (strlen($primer) + strlen($revLinker) <= $maxRevLen)		// 12/09/06
			{
				if ($temp <= $revTm)		// 12/09/06
				{
//					print "min diff " . $min_diff . ", ";
					$opt_primer = $primer;
					$opt_temp = $temp;
				}
				else
				{
					# First primer at temp > 55, record it and exit
					$opt_primer = $primer;
					$opt_temp = $temp;
					break;
				}
			}
			else
			{
				break;
			}
		}

		if ($maxRevLen > 0)
                {
			for ($i = strlen($opt_primer); $i > 0 ; $i--)
			{
				$index = substr($opt_primer, $i-1, 1);
				$val = $r_table[$index];
				$rev_primer .= $val;
			}
		}
		else	# added Nov. 21/06
                {
			# when max length is not given, take reverse complement of ENTIRE sequence to be cloned until max temp is reached
                    for ($i = strlen($tar_nt_seq); $i > 0 ; $i--)
                    {

                    	$index = substr($tar_nt_seq, $i-1, 1);
                        $val = $r_table[$index];
                        $rev_primer .= $val;
                        $opt_temp = $this->calcTemp($rev_primer);

                        if ($opt_temp > $revTm)
                        {
                        	break;
                        }
                    }
                }
		
		$temp_rev[chunk_split($rev_primer, 3, " ")] = $opt_temp;
		$primers[1] = $temp_rev;

		return $primers;
	}


	/**
	 * Squeeze out whitespace from strings (mostly for sequences)
	 *
	 * @author Marina Olhovsky
	 * @version 2006-09-12
	 *
	 * @param STRING
	 * @return STRING
	*/
	function filterSpaces($txt)
	{
		$pieces = explode(" ", $txt);
		$result = "";

		foreach ($pieces as $key=>$val)
		{
			$result .= $val;
		}

		return $result;
	}


	/**
	 * Retrieve the sequence identified by $seqID from the database
	 *
	 * @author Marina Olhovsky
	 * @version 2008-10-20
	 *
	 * @param INT
	 * @return STRING
	*/
	function getSequenceByID($seqID)
	{
		global $conn;

		$sequence = "";

		$resultSeq = mysql_query("SELECT sequence FROM Sequences_tbl WHERE seqID='" . $seqID . "' AND status='ACTIVE'", $conn) or die("Could not select sequence " . mysql_error());

		if ($seq_ar = mysql_fetch_array($resultSeq))
		{
			$sequence = $seq_ar["sequence"];
		}

		return $sequence;
	}


	/**
	 * Retrieve the comment identified by $seqID from the database (GeneralComments_tbl)
	 *
	 * @author Marina Olhovsky
	 * @version 2008-10-20
	 *
	 * @param INT
	 * @return STRING
	*/
	function getCommentByID($commID)
	{
		global $conn;

		$comment = "";

		$comm_rs = mysql_query("SELECT comment FROM GeneralComments_tbl WHERE commentID='" . $commID . "' AND status='ACTIVE'", $conn) or die("Could not select sequence " . mysql_error());

		if ($comm_ar = mysql_fetch_array($comm_rs))
		{
			$comment = $comm_ar["comment"];
		}

		return $comment;
	}


	/**
	 * Get the start position of the given property.
	 *
	 * Updated August 13/08: When propName is a feature that has a descriptor (tag type or promoter), there's a problem when there are multiple features with the same feature value AND the same descriptor! (e.g. 2 SV40 promoters, both Mammalian exp.syst. occurring at  different positions on the sequence!!!)  Then the only method of differentiation between them is by position.  Hence, need to fetch ALL database entries and return an array instead of a single position.
	 *
	 * @author Marina Olhovsky
	 * @version 2008-01-29
	 *
	 * @param INT
	 * @param STRING
	 * @param INT
	 * @param STRING (optional, used only when there are multiple values for the same property ID, e.g. selectable marker)
	 *
	 * @return INT
	*/
	function getStartPos($rID, $propName, $propID, $propVal = "")
	{
		global $conn;

		$startPos = 0;
		$startPosList = Array();	// august 13/08

		if (strlen($rID) > 0)
		{
			if ($propVal == "")
			{
// 				echo "SELECT startPos FROM ReagentPropList_tbl where reagentID=" . $rID . " AND propertyID=" . $propID . " AND status='ACTIVE'";

				$resultSet = mysql_query("SELECT startPos FROM ReagentPropList_tbl where reagentID=" . $rID . " AND propertyID=" . $propID . " AND status='ACTIVE'", $conn) or die("Unable to get " . $propName . " start position 1: " . mysql_error());
			}
			else
			{
				$query = "SELECT startPos FROM `ReagentPropList_tbl` where `reagentID`=" . $rID . " AND `propertyID`=" . $propID . " AND `propertyValue`='" . addslashes($propVal) . "' AND `status`='ACTIVE'";
// echo $query;
				$resultSet = mysql_query($query, $conn) or die("Unable to get " . $propName . " start position 2: " . mysql_error());
				
			}
	
			while ($startPos_ar = mysql_fetch_array($resultSet, MYSQL_ASSOC))
			{
				$startPos = $startPos_ar["startPos"];
				$startPosList[] = $startPos;		// Aug. 13/08
			}
		}

		if ($propVal == "")		// Aug. 15/08
			return $startPos;
		else
			return $startPosList;	// Aug. 13/08
	}


	/**
	 * Get the end position of the given property.
	 *
	 * @author Marina Olhovsky
	 * @version 2008-01-29
	 *
	 * @param INT
	 * @param STRING
	 * @param INT
	 * @param STRING (optional, used only when there are multiple values for the same property ID, e.g. selectable marker)
	 *
	 * @return INT
	*/
	function getEndPos($rID, $propName, $propID, $propVal = "")
	{
		global $conn;

		$endPos = 0;
		$endPosList = Array();

		if (strlen($rID) > 0)
		{
			if ($propVal == "")
				$resultSet = mysql_query("SELECT endPos FROM ReagentPropList_tbl where reagentID=" . $rID . " AND propertyID=" . $propID . " AND status='ACTIVE'", $conn) or die("Unable to get " . $propName . " end position 1: " . mysql_error());
			else
				$resultSet = mysql_query("SELECT endPos FROM ReagentPropList_tbl where reagentID=" . $rID . " AND propertyID=" . $propID . " AND propertyValue='" . addslashes($propVal) . "' AND status='ACTIVE'", $conn) or die("Unable to get " . $propName . " end position 2: " . mysql_error());
				
	
			while ($endPos_ar = mysql_fetch_array($resultSet, MYSQL_ASSOC))
			{
				$endPos = $endPos_ar["endPos"];
				$endPosList[] = $endPos;
			}
		}

		if ($propVal == "")		// Aug. 15/08
			return $endPos;
		else
 			return $endPosList;
	}


	/**
	 * Get the value of the given property for a specific reagent.
	 *
	 * @author Marina Olhovsky
	 *
	 * @param INT
	 * @param INT
	 * @param STRING
	 *
	 * @return MIXED
	*/
	function getPropertyValueSpecific($rID, $propID, $propDescr)
	{
		global $conn;

		$pVal = 0;
		
		if (strlen($rID) > 0)
		{
			$query = "SELECT propertyValue FROM `ReagentPropList_tbl` where `reagentID`=" . $rID . " AND `propertyID`=" . $propID . " AND descriptor='" . $propDescr . "' AND `status`='ACTIVE'";
// echo $query;
			$resultSet = mysql_query($query, $conn) or die("Unable to get " . $propName . " start position 2: " . mysql_error());
			
			while ($prop_ar = mysql_fetch_array($resultSet, MYSQL_ASSOC))
			{
				$pVal = $prop_ar["propertyValue"];
// echo $pVal;
			}
		}

		return $pVal;
	}


	/**
	 * Fetch a feature's start position by descriptor.  Used for printing features in creation forms.
	 *
	 * @author Marina Olhovsky
	 * @version 2010-06-08
	 *
	 * @param INT
	 * @param INT
	 * @param MIXED
	 * @param STRING
	 *
	 * @return INT
	*/
	function getStartPosDescr($rID, $propID, $propVal, $propDescr)
	{
		global $conn;

		$startPos = 0;
		
		if (strlen($rID) > 0)
		{
			$query = "SELECT startPos FROM `ReagentPropList_tbl` where `reagentID`=" . $rID . " AND `propertyID`=" . $propID . " AND `propertyValue`='" . addslashes($propVal) . "' AND descriptor='" . $propDescr . "' AND `status`='ACTIVE'";
// echo $query;
			$resultSet = mysql_query($query, $conn) or die("Unable to get " . $propName . " start position 2: " . mysql_error());
			
			while ($startPos_ar = mysql_fetch_array($resultSet, MYSQL_ASSOC))
			{
				$startPos = $startPos_ar["startPos"];
			}
		}

		return $startPos;
	}


	/**
	 * Fetch a feature's end position by descriptor.  Used for printing features in creation forms.
	 *
	 * @author Marina Olhovsky
	 * @version 2010-06-08
	 *
	 * @param INT
	 * @param INT
	 * @param MIXED
	 * @param STRING
	 *
	 * @return INT
	*/
	function getEndPosDescr($rID, $propID, $propVal, $propDescr)
	{
		global $conn;

		$endPos = 0;
		
		if (strlen($rID) > 0)
		{
			$query = "SELECT endPos FROM `ReagentPropList_tbl` where `reagentID`=" . $rID . " AND `propertyID`=" . $propID . " AND `propertyValue`='" . addslashes($propVal) . "' AND descriptor='" . $propDescr . "' AND `status`='ACTIVE'";
// echo $query;
			$resultSet = mysql_query($query, $conn) or die("Unable to get " . $propName . " start position 2: " . mysql_error());
			
			while ($endPos_ar = mysql_fetch_array($resultSet, MYSQL_ASSOC))
			{
				$endPos = $endPos_ar["endPos"];
			}
		}

		return $endPos;
	}


	/**
	 * Get the linker portion of an oligo (recursive method, copied from Python).
	 *
	 * @author Marina Olhovsky
	 * @version 2008-01-30
	 *
	 * @param STRING
	 * @param STRING
	 * @param STRING
	 *
	 * @return STRING
	*/
	function linker_from_oligo($insert_seq, $primer_seq, $linker="")
	{
		$pos = stripos($insert_seq, $primer_seq);

		if ($pos !== FALSE)
		{
			$linker .= substr($primer_seq, 0, 1);
			$primer_seq = substr($primer_seq, 1);
			return $this->linker_from_oligo($insert_seq, $primer_seq, $linker);
		}
	
// 		echo "Linker " . $linker . "<BR>";
	
		return $linker;
	}


	/**
	 * Reverse complement a DNA sequence (most likely Oligo - reverse primer).  DO NOT filter spaces - preserve original spacing in linker.
	 *
	 * @author Marina Olhovsky
	 * @version 2008-01-30
	 *
	 * @param STRING
	 * @return STRING
	*/
	function reverse_complement($seq)
	{
		//echo $seq;

		// Jan. 17, 2011: convert all to lowercase!!
		$seq = strtolower($seq);

		$complement_map = $this->complement_map;
		$reverseComplement = "";

		while (strlen($seq) > 0)
		{
			$lastChar = substr($seq, -1);

			if ($lastChar == " ")
			{
				$reverseComplement .= $lastChar;
				$seq = substr($seq, 0, strlen($seq)-1);
			}
			else
			{
				$revChar = $complement_map[$lastChar];
				$reverseComplement .= $revChar;
				$seq = substr($seq, 0, strlen($seq)-1);
			}
		}

		return $reverseComplement;
	}


	/**
	 * Get the direction (orientation) of the given property (forward/reverse).  (March 17/08: Added $propVal argument for multiple features).
	 *
	 * @author Marina Olhovsky
	 * @version 2008-02-22
	 *
	 * @param INT
	 * @param INT
	 * @param STRING
	 *
	 * @return STRING
	*/
	function getPropertyDirection($rID, $propID, $propVal="")
	{
		global $conn;

		$propName = $_SESSION["ReagentProp_Name_ID"][$propID];		// for error output
		$direction = "forward";

		if (strlen(trim($propVal)) == 0)
		{
			$resultSet = mysql_query("SELECT direction FROM ReagentPropList_tbl where reagentID=" . $rID . " AND propertyID=" . $propID . " AND status='ACTIVE'", $conn) or die("Unable to get " . $propName . " orientation: " . mysql_error());
		}
		else
		{
			$resultSet = mysql_query("SELECT direction FROM ReagentPropList_tbl where reagentID=" . $rID . " AND propertyID=" . $propID . " AND propertyValue='" . addslashes($propVal) . "' AND status='ACTIVE'", $conn) or die("Unable to get " . $propName . " orientation: " . mysql_error());
		}

		if ($results = mysql_fetch_array($resultSet, MYSQL_ASSOC))
			$direction = $results["direction"];

		return $direction;
	}


	/**
	 * Separate function to select dropdown list values
	 *
	 * @author Marina Olhovsky
	 * @version 2008-03-05
	 *
	 * @param STRING
	 * @return Array
	*/
	function getSetValues($setName)
	{
		global $conn;

		$propID = $_SESSION["ReagentProp_Name_ID"][strtolower($setName)];

		$setValues = Array();

		$set_rs = mysql_query("SELECT b.entityName FROM System_Set_Groups_tbl a, System_Set_tbl b WHERE a.ssetGroupID=b.ssetGroupID AND a.propertyIDLink='" . $propID . "' AND a.status='ACTIVE' AND b.status ='ACTIVE' ORDER BY b.entityName", $conn) or die("Could not select set values (1): " . mysql_error());

		if( mysql_num_rows( $set_rs ) > 0 )
		{
			while ($set_ar = mysql_fetch_array($set_rs , MYSQL_ASSOC))
			{
				$setValues[] = $set_ar["entityName"];
			}
		}
		else
		{
			mysql_free_result($set_rs);

			$setretry_rs = mysql_query("SELECT b.entityName FROM System_Set_Groups_tbl a, System_Set_tbl b WHERE a.ssetGroupID=b.ssetGroupID AND a.groupName='" . strtolower($name) . "' AND a.status='ACTIVE' AND b.status='ACTIVE' ORDER BY b.entityName", $conn) or die("Could not select set values (2): " . mysql_error());

			if (mysql_num_rows( $setretry_rs ) > 0 )
			{
				while( $setretry_ar = mysql_fetch_array( $setretry_rs , MYSQL_ASSOC ) )
				{
					$setValues[] = $set_ar["entityName"];
				}
			}
		}
		
		return $setValues;
	}


	/**
	 * Check if this property type exists in ReagentPropType_tbl
	 *
	 * @author Marina Olhovsky
	 * @version 2009-04-06
	 *
	 * @param STRING
	 * @return boolean
	*/
	function existsReagentProperty($propType)
	{
		global $conn;

		$propID = -1;

		$prop_set_rs = mysql_query("SELECT propertyID FROM ReagentPropType_tbl WHERE propertyAlias='" . $propType . "' AND status='ACTIVE'", $conn) or die("Cannot select reagent properties: " . mysql_error());

		while ($prop_set_ar = mysql_fetch_array($prop_set_rs, MYSQL_ASSOC))
		{
			$propID = $prop_set_ar["propertyID"];
		}

		return $propID;
	}

}
?>
