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
* This class sets user's session variables by generating maps of constant database column values
*
* @author John Paul Lee @version 2005
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
*
* @package All
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*
*/
class Session_Var_Class
{
	var $original_view_offset;
	var $original_High_offset;
	
	/**
	 * Constructor
	 *
	*/
	function Session_Var_Class()
	{
		$this->original_view_offset = 0;
		$this->original_high_offset = 50;
	}


	/**
	 * Main function that sets session variables by mapping database column values, such as reagent or property names to IDs
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	*/
	function checkSession_all()
	{
		// Marina: Update April 23/09 - Since the database can now be updated at addition of new reagent types and properties, the easiest solution would be to reset session variables every time a view is loaded
// 		if( !isset( $_SESSION["ReagentProp_Name_ID"] ) )
// 		{
			$_SESSION["ReagentProp_Name_ID"] = $this->getReagentPropID_const();
// 		};

// 		if( !isset( $_SESSION["ReagentProp_ID_Name"] ) )
// 		{
			$_SESSION["ReagentProp_ID_Name"] = $this->getReagentPropID_const_rev();
// 		}
		
// 		if( !isset( $_SESSION["ReagentType_Name_ID"] ) )
// 		{
			$_SESSION["ReagentType_Name_ID"] = $this->getReagentTypes_const();
// 		}
		
// 		if( !isset( $_SESSION["ReagentType_ID_Name"] ) )
// 		{
			$_SESSION["ReagentType_ID_Name"] = $this->getReagentTypes_const_rev();
// 		}

		// May 1/08, Marina
// 		if (!isset($_SESSION["ReagentProp_Alias_Name"]))
// 		{
			$_SESSION["ReagentProp_Alias_Name"] = $this->getReagentProp_Alias_Names();
// 		}

		// May 1/08, Marina
// 		if (!isset($_SESSION["ReagentProp_Name_Alias"]))
// 		{
			$_SESSION["ReagentProp_Name_Alias"] = $this->getReagentProp_Name_Aliases();
// 		}

		// Nov. 2/09
		$_SESSION["ReagentProp_ID_Alias"] = $this->getReagentProp_ID_Aliases();

		// May 2/08, Marina: Map property names to descriptions
// 		if (!isset($_SESSION["ReagentProp_Name_Desc"]))
// 		{
			$_SESSION["ReagentProp_Name_Desc"] = $this->getReagentProp_Name_Desc();
			$_SESSION["ReagentProp_Desc_Name"] = $this->getReagentProp_Desc_Name();
// 		}

		$_SESSION["ReagentProp_Desc_ID"] = $this->getReagentProp_Desc_ID();
		$_SESSION["ReagentProp_ID_Desc"] = $this->getReagentProp_ID_Desc();

		// April 30/09
		$_SESSION["ReagentProp_Alias_Desc"] = $this->getReagentProp_Alias_Desc();
		$_SESSION["ReagentProp_Desc_Alias"] = $this->getReagentProp_Desc_Alias();
		
		// May 5/08, Marina: Map association names to aliases
// 		if (!isset($_SESSION["ReagentAssoc_Name_Alias"]))
// 		{
			$_SESSION["ReagentAssoc_Name_Alias"] = $this->getReagentAssoc_Name_Alias();
// 		}

		// July 6/09
		$_SESSION["ReagentAssoc_ID_Name"] = $this->getReagentAssoc_ID_Name();
		$_SESSION["ReagentAssoc_Name_ID"] = array_flip($this->getReagentAssoc_ID_Name());

		// July 6/09
		$_SESSION["ReagentAssoc_ID_Description"] = $this->getReagentAssoc_ID_Description();

		// Aug 11/09
		$_SESSION["ReagentAssoc_ID_Alias"] = $this->getReagentAssoc_ID_Alias();

// 		if( !isset( $_SESSION["view_Offset"] ) )
// 		{
			$_SESSION["view_Offset"] = $this->original_view_offset;
// 		}
		
		if( !isset( $_SESSION["ToView_High"] ) )
		{
			$_SESSION["ToView_High"] = $this->original_high_offset;
		}
		
// 		if( !isset( $_SESSION["ReagentType_Prefix_Name"] ) )
// 		{
			$_SESSION["ReagentType_Prefix_Name"] = $this->setReagentPrefix();
// 		}
		
// 		if( !isset( $_SESSION["ReagentType_Name_Prefix"] ) )
// 		{
			$_SESSION["ReagentType_Name_Prefix"] = $this->setReagentPrefix_reverse();
// 		}
		
		if( !isset( $_SESSION["userCategoryNames"]) )
		{
			$_SESSION["userCategoryNames"] = $this->getUserCategorieByName();
		}

// 		if (!isset($_SESSION["contGroupNames"]))
// 		{
			$_SESSION["contGroupNames"] = $this->getContainerGroupNames();
// 		}

		// April 23/09
// 		if (!isset($_SESSION["ReagentPropCategory_Name_ID"]))
// 		{
			$_SESSION["ReagentPropCategory_Name_ID"] = $this->getReagentPropertyCategoryName_ID_Map();
// 		}

		$_SESSION["ReagentPropCategory_ID_Name"] = $this->getReagentPropertyCategory_ID_Name_Map();

		// May 22/09
		$_SESSION["ReagentPropCategory_Name_Alias"] = $this->getReagentPropertyCategory_Name_Alias_Map();
		$_SESSION["ReagentPropCategory_Alias_Name"] = $this->getReagentPropertyCategory_Alias_Name_Map();

		$_SESSION["ReagentPropCategory_ID_Alias"] = $this->getReagentPropertyCategory_ID_Alias_Map();
		$_SESSION["ReagentPropCategory_Alias_ID"] = $this->getReagentPropertyCategory_Alias_ID_Map();

		// April 28/09
		$_SESSION["ReagentType_ID_Prefix"] = $this->mapReagent_ID_Prefix();
		$_SESSION["ReagentType_Prefix_ID"] = $this->mapReagent_Prefix_ID();

		// May 21/09
		$_SESSION["Reagent_Feature_Name_Descriptor"]["tag type"] = "tag position";
		$_SESSION["Reagent_Feature_Name_Descriptor"]["promoter"] = "expression system";

		// Dec. 21/09: moved here from location
		$_SESSION["Container_ID_Name"] = $this->mapContainer_ID_Name();
		$_SESSION["Container_Name_ID"] = $this->mapContainer_Name_ID();

		// June 30, 2010
		$_SESSION["Lab_Name_ID"] = $this->mapLab_Name_ID();
		$_SESSION["Lab_ID_Name"] = $this->mapLab_ID_Name();

		$_SESSION["ContainerTypeID_Code"] = $this->mapContainerType_ID_Code();
	}

	/**
	 * Create maps specific to reagents in a similar fashion by mapping table column values
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	*/
	function checkSession_reagent()
	{
		if( !isset( $_SESSION["ReagentProp_Name_ID"] ) )
		{
			$_SESSION["ReagentProp_Name_ID"] = $this->getReagentPropID_const();
		};
		
		if( !isset( $_SESSION["ReagentProp_ID_Name"] ) )
		{
			$_SESSION["ReagentProp_ID_Name"] = $this->getReagentPropID_const_rev();
		}
		
		if( !isset( $_SESSION["ReagentType_Name_ID"] ) )
		{
			$_SESSION["ReagentType_Name_ID"] = $this->getReagentTypes_const();
		}
		
		if( !isset( $_SESSION["ReagentType_ID_Name"] ) )
		{
			$_SESSION["ReagentType_ID_Name"] = $this->getReagentTypes_const_rev();
		}
		
		if( !isset( $_SESSION["ReagentType_Prefix_Name"] ) )
		{
			$_SESSION["ReagentType_Prefix_Name"] = $this->setReagentPrefix();
		}
		
		if( !isset( $_SESSION["ReagentType_Name_Prefix"] ) )
		{
			$_SESSION["ReagentType_Name_Prefix"] = $this->setReagentPrefix_reverse();
		}
	}


	/**
	 * Make a dictionary out of a mysql_result_set using the values of the second column as array keys and the first column as values
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param resultset
	 * @return Array associative array (aka 'dictionary' or 'map')
	*/
	function setVectorTypeIDs($tomatch)
	{
		$vectorTypeID_arr = array();
	
// 		print_r( $tomatch );
		
		while( $row = mysql_fetch_row($tomatch) )
		{
			//foreach ($row as $col_value) {
			//echo "\t\t$col_value\n";
			//	}	
			$vectorTypeID_arr[ $row[1] ] = $row[0];
		}
		
// 		print_r( $vectorTypeID_arr );
		
		return $vectorTypeID_arr;
	}
	

	/**
	 * Map reagent type names to their prefixes
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @return Array dictionary of reagentTypeName => reagentTypePrefix tuples, e.g. 'Vector' => 'V'
	 * @see setVectorTypeIDs()
	*/
	function setReagentPrefix( )
	{
		global $conn;
		
		$prefix_rs = mysql_query("SELECT `reagentTypeName`, `reagent_prefix` FROM `ReagentType_tbl` WHERE `status`='ACTIVE'", $conn ) or die("Failure in prefix sql fetch:" . mysql_error());

		$prefix_final = $this->setVectorTypeIDs($prefix_rs);
		mysql_free_result($prefix_rs);
		
		return $prefix_final;
	}
	

	/**
	 * Map prefixes to reagent type names
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @return Array dictionary of reagentTypePrefix => reagentTypeName tuples, e.g. 'V' => 'Vector'
	 * @see setVectorTypeIDs()
	*/
	function setReagentPrefix_reverse()
	{
		global $conn;
		
		$prefix_rs = mysql_query("SELECT `reagent_prefix`, `reagentTypeName` FROM `ReagentType_tbl` WHERE `status`='ACTIVE'", $conn) or die("Failure in prefix sql fetch:" . mysql_error());
		
		$prefix_final = $this->setVectorTypeIDs($prefix_rs);
		mysql_free_result($prefix_rs);
		
		return $prefix_final;
	}


	/**
	 * Map reagent type IDs to their prefixes
	 *
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2009-04-28
	 *
	 * @return Array dictionary of reagentTypeID => reagentTypePrefix tuples, e.g. 1 => 'V'
	 * @see setVectorTypeIDs()
	*/
	function mapReagent_ID_Prefix()
	{
		global $conn;
		
		$prefix_rs = mysql_query("SELECT reagent_prefix, reagentTypeID FROM ReagentType_tbl WHERE status='ACTIVE'", $conn) or die("Failure in prefix sql fetch:" . mysql_error());
		
		$prefix_final = $this->setVectorTypeIDs($prefix_rs);
		mysql_free_result($prefix_rs);
		
		return $prefix_final;
	}


	/**
	 * Map reagent type prefixes to IDs
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1  2009-04-28
	 *
	 * @return Array dictionary of reagentTypePrefix => reagentTypeID tuples, e.g. 'V' => 1
	 * @see setVectorTypeIDs()
	*/
	function mapReagent_Prefix_ID()
	{
		global $conn;
		
		$prefix_rs = mysql_query("SELECT reagentTypeID, reagent_prefix FROM ReagentType_tbl WHERE status='ACTIVE'", $conn) or die("Failure in prefix sql fetch:" . mysql_error());
		
		$prefix_final = $this->setVectorTypeIDs($prefix_rs);
		mysql_free_result($prefix_rs);
		
		return $prefix_final;
	}
	

	/**
	 * Map reagent property names to their IDs
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1  2009-04-28
	 *
	 * @return Array dictionary of propertyName => propertyID tuples, e.g. 'name' => '1'
	 * @see setVectorTypeIDs()
	*/
	function getReagentPropID_const()
	{
		// CONST : Finds all the names of the property and their given property ID's
		// propName --> propID
		// [DEBUG] : 1
		global $conn;
		
		$reagentPropID_rs = mysql_query("SELECT propertyID, propertyName FROM `ReagentPropType_tbl` WHERE `status`='ACTIVE'", $conn) or die("Query to find property names failed!: ".mysql_error());
		
		
		$reagentPropID_const = $this->setVectorTypeIDs( $reagentPropID_rs );
		mysql_free_result( $reagentPropID_rs );
		
		return $reagentPropID_const;
	}

	/**
	 * Map association property names to aliases
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1  2008-05-05
	 *
	 * @return Array dictionary of APropName => alias tuples, e.g. 1 => 'insert_id'
	*/
	function getReagentAssoc_Name_Alias()
	{
		global $conn;
		
		$assoc_Name_Alias_Map = array();

		$assoc_rs = mysql_query("SELECT APropName, alias FROM Assoc_Prop_Type_tbl WHERE status='ACTIVE'", $conn) or die("Cannot map association names to aliases: " . mysql_error());

		while ($assoc_ar = mysql_fetch_array($assoc_rs))
		{
			$assocName = $assoc_ar["APropName"];
			$assocAlias = $assoc_ar["alias"];

			$assoc_Name_Alias_Map[$assocName] = $assocAlias;
		}

		return $assoc_Name_Alias_Map;
	}

	/**
	 * Map association property IDs to names
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array dictionary of APropertyID => APropName tuples, e.g. 1 => 'insert id'
	*/
	function getReagentAssoc_ID_Name()
	{
		global $conn;
		
		$assoc_ID_Name_Map = array();

		$assoc_rs = mysql_query("SELECT APropertyID, APropName FROM Assoc_Prop_Type_tbl WHERE status='ACTIVE'", $conn) or die("Cannot map association IDs to names: " . mysql_error());

		while ($assoc_ar = mysql_fetch_array($assoc_rs))
		{
			$assocName = $assoc_ar["APropName"];
			$assocID = $assoc_ar["APropertyID"];

			$assoc_ID_Name_Map[$assocID] = $assocName;
		}

		return $assoc_ID_Name_Map;
	}

	/**
	 * Map association property IDs to aliases
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array dictionary of APropertyID => alias tuples, e.g. 1 => 'insert_id'
	*/
	function getReagentAssoc_ID_Alias()
	{
		global $conn;

		$assoc_ID_Alias_Map = array();

		$assoc_rs = mysql_query("SELECT APropertyID, alias FROM Assoc_Prop_Type_tbl WHERE status='ACTIVE'", $conn) or die("Cannot map association IDs to alias: " . mysql_error());

		while ($assoc_ar = mysql_fetch_array($assoc_rs))
		{
			$assocID = $assoc_ar["APropertyID"];
			$assocAlias = $assoc_ar["alias"];
			
			$assoc_ID_Alias_Map[$assocID] = $assocAlias;
		}

		return $assoc_ID_Alias_Map;
	}

	/**
	 * Map association property IDs to descriptions
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1  2008-05-05
	 *
	 * @return Array dictionary of APropertyID => description tuples, e.g. 1 => 'Insert ID', or '7' => 'Insert Parent Vector ID'
	*/
	function getReagentAssoc_ID_Description()
	{
		global $conn;
		
		$assoc_ID_Desc_Map = array();

		$assoc_rs = mysql_query("SELECT APropertyID, description FROM Assoc_Prop_Type_tbl WHERE status='ACTIVE'", $conn) or die("Cannot map association IDs to description: " . mysql_error());

		while ($assoc_ar = mysql_fetch_array($assoc_rs))
		{
			$assocID = $assoc_ar["APropertyID"];
			$assocDesc = $assoc_ar["description"];
			
			$assoc_ID_Desc_Map[$assocID] = $assocDesc;
		}

		return $assoc_ID_Desc_Map;
	}


	/**
	 * Map reagent property names to their descriptions
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1  2008-05-02
	 *
	 * @return Array dictionary of propertyName => propertyDesc tuples, e.g. 'tag position' => 'Tag Position'
	*/
	function getReagentProp_Name_Desc()
	{
		global $conn;

		$prop_Name_Desc_Map = array();
	
		$prop_rs = mysql_query("SELECT propertyName, propertyDesc FROM ReagentPropType_tbl WHERE status='ACTIVE'", $conn) or die("Cannot map property names to descriptions: " . mysql_error());

		while ($prop_ar = mysql_fetch_array($prop_rs, MYSQL_ASSOC))
		{
			$propName = $prop_ar["propertyName"];
			$propDesc = $prop_ar["propertyDesc"];
			
			$prop_Name_Desc_Map[$propName] = $propDesc;
		}

		return $prop_Name_Desc_Map;
	}

	/**
	 * Equal and opposite - Map reagent property descriptions to their names
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1  2009-04-30
	 *
	 * @return Array dictionary of propertyDesc => propertyName tuples, e.g. 'Tag Position' => 'tag position'
	*/
	function getReagentProp_Desc_Name()
	{
		global $conn;

		$prop_Name_Desc_Map = array();
	
		$prop_rs = mysql_query("SELECT propertyName, propertyDesc FROM ReagentPropType_tbl WHERE status='ACTIVE'", $conn) or die("Cannot map property names to descriptions: " . mysql_error());

		while ($prop_ar = mysql_fetch_array($prop_rs, MYSQL_ASSOC))
		{
			$propName = $prop_ar["propertyName"];
			$propDesc = $prop_ar["propertyDesc"];
			
			$prop_Desc_Name_Map[$propDesc] = $propName;
		}

		return $prop_Desc_Name_Map;
	}
	

	/**
	 * Map reagent property aliases to their names
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1  2008-05-01
	 *
	 * @return Array dictionary of propertyDesc => propertyName tuples, e.g. 'Tag Position' => 'tag position'
	*/
	function getReagentProp_Alias_Names()
	{
		global $conn;

		$reagentProp_rs = mysql_query("SELECT propertyAlias, propertyName FROM ReagentPropType_tbl WHERE status='ACTIVE'", $conn) or die("Could not map property aliases to names: " . mysql_error());
		
		$propAliasNameMap = array();

		while ($reagentProp_ar = mysql_fetch_array($reagentProp_rs, MYSQL_ASSOC))
		{
			$pAlias = $reagentProp_ar["propertyAlias"];
			$pName = $reagentProp_ar["propertyName"];

			$propAliasNameMap[$pAlias] = $pName;
		}

		mysql_free_result($reagentProp_rs);

		return $propAliasNameMap;
	}


	/**
	 * Map reagent property names to their aliases
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1  2008-05-01
	 *
	 * @return Array dictionary of propertyName => propertyAlias tuples, e.g. 'tag position' => 'tag_position'
	*/
	function getReagentProp_Name_Aliases()
	{
		global $conn;

		$reagentProp_rs = mysql_query("SELECT propertyAlias, propertyName FROM ReagentPropType_tbl WHERE status='ACTIVE'", $conn) or die("Could not map property aliases to names: " . mysql_error());
		
		$propNameAliasMap = array();

		while ($reagentProp_ar = mysql_fetch_array($reagentProp_rs, MYSQL_ASSOC))
		{
			$pAlias = $reagentProp_ar["propertyAlias"];
			$pName = $reagentProp_ar["propertyName"];

			$propNameAliasMap[$pName] = $pAlias;
		}

		mysql_free_result($reagentProp_rs);

		return $propNameAliasMap;
	}

	/**
	 * Map reagent property IDs to their aliases
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array dictionary of propertyID => propertyAlias tuples, e.g. '8' => 'tag_position'
	*/
	function getReagentProp_ID_Aliases()
	{
		global $conn;

		$reagentProp_rs = mysql_query("SELECT propertyAlias, propertyID FROM ReagentPropType_tbl WHERE status='ACTIVE'", $conn) or die("Could not map property aliases to names: " . mysql_error());
		
		$propIDAliasMap = array();

		while ($reagentProp_ar = mysql_fetch_array($reagentProp_rs, MYSQL_ASSOC))
		{
			$pAlias = $reagentProp_ar["propertyAlias"];
			$pID = $reagentProp_ar["propertyID"];

			$propIDAliasMap[$pID] = $pAlias;
		}

		mysql_free_result($reagentProp_rs);

		return $propIDAliasMap;
	}


	/**
	 * Map reagent property alias to description
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2009-04-30
	 *
	 * @return Array dictionary of propertyAlias => propertyDesc tuples, e.g. 'tag_position' => 'Tag Position'
	*/
	function getReagentProp_Alias_Desc()
	{
		global $conn;
		
		$prop_rs = mysql_query("SELECT propertyAlias, propertyDesc FROM ReagentPropType_tbl WHERE status='ACTIVE'", $conn) or die("Cannot map property names to descriptions: " . mysql_error());

		while ($prop_ar = mysql_fetch_array($prop_rs, MYSQL_ASSOC))
		{
			$propAlias = $prop_ar["propertyAlias"];
			$propDesc = $prop_ar["propertyDesc"];
			
			$prop_Alias_Desc_Map[$propAlias] = $propDesc;
		}

		return $prop_Alias_Desc_Map;
	}


	/**
	 * Map reagent property description to alias
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2009-04-30
	 *
	 * @return Array dictionary of propertyDesc => propertyAlias tuples, e.g. 'Tag Position' => 'tag_position'
	*/
	function getReagentProp_Desc_Alias()
	{
		global $conn;
		
		$prop_rs = mysql_query("SELECT propertyAlias, propertyDesc FROM ReagentPropType_tbl WHERE status='ACTIVE'", $conn) or die("Cannot map property names to descriptions: " . mysql_error());

		while ($prop_ar = mysql_fetch_array($prop_rs, MYSQL_ASSOC))
		{
			$propAlias = $prop_ar["propertyAlias"];
			$propDesc = $prop_ar["propertyDesc"];
			
			$prop_Desc_Alias_Map[$propDesc] = $propAlias;
		}

		return $prop_Desc_Alias_Map;
	}

	/**
	 * Map reagent property IDs to their names
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @return Array dictionary of propertyID => propertyName tuples, e.g. '8' => 'tag position'
	 * @see setVectorTypeIDs()
	*/
	function getReagentPropID_const_rev( )
	{
		// CONST : Finds all the names of the property and their given property ID's
		// propName --> propID
		// [1] : DEBUG
		global $conn;
		
		$reagentPropID_rs = mysql_query( "SELECT propertyName, propertyID FROM `ReagentPropType_tbl` WHERE `status`='ACTIVE'", $conn ) or die("Query to find property names failed!: ".mysql_error() );
		
		$reagentPropID_const = $this->setVectorTypeIDs( $reagentPropID_rs );
		mysql_free_result( $reagentPropID_rs );
		//echo "<br> ---- reagentPropID_const ---- <br>";
		//print_r( $reagentPropID_const );
		return $reagentPropID_const;
	}
	

	/**
	 * Map reagent type names to IDs
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @return Array dictionary of reagentTypeName => reagentTypeID tuples, e.g. 'Vector' => '1'
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
		$reagentTypes_const = $this->setVectorTypeIDs( $reagentTypes_rs );
		mysql_free_result( $reagentTypes_rs );
		//echo "<br> ---- reagentTypes_const ---- <br>";
		//print_r( $reagentTypes_const );
		return $reagentTypes_const;
	}
	
	/**
	 * Map reagent type IDs to names
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @return Array dictionary of reagentTypeID => reagentTypeName tuples, e.g. '1' => 'Vector'
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
		$reagentTypes_const = $this->setVectorTypeIDs( $reagentTypes_rs );
		mysql_free_result( $reagentTypes_rs );
		//echo "<br> ---- reagentTypes_const ---- <br>";
		//print_r( $reagentTypes_const );
		return $reagentTypes_const;
	}
	
	// CONST : Finds all the vector properties associated with a given vector type.
	/**
	 * Map all properties for preps of a given reagent type to their 'required' values (i.e. array keys are property IDs, array values are 'REQ' or 'OPT' values indicating whether the property is required or optional for preps of the given reagent)
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param INT reagent type ID
	 * @param Array dictionary of reagent type ID => name tuples (cannot use $_SESSION[...] here, hasn't been set yet!)
	 *
	 * @return Array dictionary of propertyID => STRING tuples, e.g. '1' => 'REQ', '2' => 'OPT'
	*/
	function getReagentPropReq($reagentToFind, $types)
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
	 * Same as getReagentPropReq, only the argument for SQL query is reagentTypeID
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @param INT reagent type ID
	 * @param Array dictionary of reagent type ID => name tuples -- REDUNDANT
	 *
	 * @return Array dictionary of propertyID => STRING tuples, e.g. '1' => 'REQ', '2' => 'OPT'
	 * @see getReagentPropReq()
	*/
	function getReagentPropReq_num($reagentTypeID_tofind, $types)
	{
		global $conn;

		$reagentProp_rs = mysql_query("SELECT `propertyID`,`requirement` FROM `PropertyReq_tbl` WHERE reagentTypeID='" . $reagentTypeID_tofind . "' AND `status`='ACTIVE' ORDER BY `propertyID` ASC;", $conn) or die("Query to find reagent property associated failed!: ".mysql_error());
		
		$tempPropReq_ar = array ();
		
		while ($row = mysql_fetch_array($reagentProp_rs, MYSQL_ASSOC))
		{
			$tempPropReq_ar[$row["propertyID"]]=$row["requirement"];
		}
		
		mysql_free_result($reagentProp_rs);

		return $tempPropReq_ar;
	}
	
	
	// July 16/07, Marina
	/**
	 * Same as getReagentPropReq, only the argument for SQL query is reagentTypeID
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2007-07-16
	 *
	 * @return Array dictionary of user category ID => user category name tuples, e.g. '1' => 'Admin'
	*/
	function getUserCategorieByName()
	{
		global $conn;
		
		$userCategoryNames = array();
		
		$categories_rs = mysql_query("SELECT categoryID, category FROM UserCategories_tbl WHERE status='ACTIVE'", $conn) or die("Cannot fetch user categories: " . mysql_error());
		
		while ($categories_ar = mysql_fetch_array($categories_rs, MYSQL_ASSOC))
		{
			$categoryID = $categories_ar["categoryID"];
			$categoryName = $categories_ar["category"];
			
			$userCategoryNames[$categoryName] = $categoryID;
		}
		
		return $userCategoryNames;
	}

	// Jan. 30/08, Marina
	/**
	 * Map container type (group) names to IDs
	 *
	 * Redundant to mapContainer_Name_ID()
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2008-01-30
	 *
	 * @return Array dictionary of container type (historically called 'group') names => container type ID name tuples, e.g. '2' => 'Glycerol Stocks'
	*/
	function getContainerGroupNames()
	{
		global $conn;
		
		$contGroupNames = array();

		$groupNames_rs = mysql_query("SELECT contGroupID, contGroupName FROM ContainerGroup_tbl WHERE status='ACTIVE'", $conn) or die("Cannot fetch container types: " . mysql_error());
		
		while ($groupNames_ar = mysql_fetch_array($groupNames_rs, MYSQL_ASSOC))
		{
			$contGroupID = $groupNames_ar["contGroupID"];
			$contGroupName = $groupNames_ar["contGroupName"];
			
			$contGroupNames[$contGroupName] = $contGroupID;
		}
		
		return $contGroupNames;
	}

	// April 23/09
	/**
	 * Map reagent property category IDs to names
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2009-04-23
	 *
	 * @return Array dictionary of reagent property category ID => reagent property category name tuples, e.g. '1' => 'General Properties'
	*/
	function getReagentPropertyCategory_ID_Name_Map()
	{
		global $conn;

		$propCategoryMap = Array();

		$propCategories_set = mysql_query("SELECT propertyCategoryID, propertyCategoryName FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'", $conn) or die("Cannot select reagent property categories: " . mysql_error());
		
		while ($propCategories = mysql_fetch_array($propCategories_set, MYSQL_ASSOC))
		{
			$propCatID = $propCategories["propertyCategoryID"];
			$propCatName = $propCategories["propertyCategoryName"];

			$propCategoryMap[$propCatID] = $propCatName;
		}

		return $propCategoryMap;
	}


	// April 23/09
	/**
	 * Equal and opposite: Map reagent property category names to IDs
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2009-04-23
	 *
	 * @return Array dictionary of reagent property category name => reagent property category ID tuples, e.g. 'General Properties' => '1'
	*/
	function getReagentPropertyCategoryName_ID_Map()
	{
		global $conn;

		$propCategoryMap = Array();

		$propCategories_set = mysql_query("SELECT propertyCategoryID, propertyCategoryName FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'", $conn) or die("Cannot select reagent property categories: " . mysql_error());
		
		while ($propCategories = mysql_fetch_array($propCategories_set, MYSQL_ASSOC))
		{
			$propCatID = $propCategories["propertyCategoryID"];
			$propCatName = $propCategories["propertyCategoryName"];

			$propCategoryMap[$propCatName] = $propCatID;
		}

		return $propCategoryMap;
	}

	/**
	 * Map reagent property descriptions to IDs
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array dictionary of reagent property description => reagent property ID tuples, e.g. 'Tag Position' => '8'
	*/
	function getReagentProp_Desc_ID()
	{
		global $conn;

		$reagentProp_rs = mysql_query("SELECT propertyDesc, propertyID FROM ReagentPropType_tbl WHERE status='ACTIVE'", $conn) or die("Could not map property descriptor to ID: " . mysql_error());
		
		$propDescIDMap = array();

		while ($reagentProp_ar = mysql_fetch_array($reagentProp_rs, MYSQL_ASSOC))
		{
			$pDescr = $reagentProp_ar["propertyDesc"];
			$pID = $reagentProp_ar["propertyID"];

			$propDescIDMap[$pDescr] = $pID;
		}

		mysql_free_result($reagentProp_rs);

		return $propDescIDMap;
	}


	/**
	 * Map reagent property IDs to descriptions
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array dictionary of reagent property ID => reagent property description tuples, e.g. '8' => 'Tag Position'
	*/
	function getReagentProp_ID_Desc()
	{
		global $conn;

		$reagentProp_rs = mysql_query("SELECT propertyDesc, propertyID FROM ReagentPropType_tbl WHERE status='ACTIVE'", $conn) or die("Could not map property descriptor to ID: " . mysql_error());
		
		$propIDDescMap = array();

		while ($reagentProp_ar = mysql_fetch_array($reagentProp_rs, MYSQL_ASSOC))
		{
			$pDescr = $reagentProp_ar["propertyDesc"];
			$pID = $reagentProp_ar["propertyID"];

			$propIDDescMap[$pID] = $pDescr;
		}

		mysql_free_result($reagentProp_rs);

		return $propIDDescMap;
	}


	/**
	 * Map reagent property category names to aliases
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array dictionary of reagent property category name => reagent property category alias tuples, e.g. 'General Properties' => 'general_properties'
	*/
	function getReagentPropertyCategory_Name_Alias_Map()
	{
		global $conn;

		$propCategoryMap = Array();

		$propCategories_set = mysql_query("SELECT propertyCategoryName, propertyCategoryAlias FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'", $conn) or die("Cannot select reagent property categories: " . mysql_error());
		
		while ($propCategories = mysql_fetch_array($propCategories_set, MYSQL_ASSOC))
		{
			$propCatAlias = $propCategories["propertyCategoryAlias"];
			$propCatName = $propCategories["propertyCategoryName"];

			$propCategoryMap[$propCatName] = $propCatAlias;
		}

		return $propCategoryMap;
	}


	/**
	 * Equal and opposite: Map reagent property category aliases to names
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array dictionary of reagent property category alias => reagent property category name tuples, e.g.  'general_properties' => 'General Properties'
	*/
	function getReagentPropertyCategory_Alias_Name_Map()
	{
		global $conn;

		$propCategoryMap = Array();

		$propCategories_set = mysql_query("SELECT propertyCategoryName, propertyCategoryAlias FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'", $conn) or die("Cannot select reagent property categories: " . mysql_error());
		
		while ($propCategories = mysql_fetch_array($propCategories_set, MYSQL_ASSOC))
		{
			$propCatAlias = $propCategories["propertyCategoryAlias"];
			$propCatName = $propCategories["propertyCategoryName"];

			$propCategoryMap[$propCatAlias] = $propCatName;
		}

		return $propCategoryMap;
	}

	/**
	 * Map reagent property category IDs to aliases
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array dictionary of reagent property category ID => reagent property category alias tuples, e.g.  '1' => 'general_properties'
	*/
	function getReagentPropertyCategory_ID_Alias_Map()
	{
		global $conn;

		$propCategoryMap = Array();

		$propCategories_set = mysql_query("SELECT propertyCategoryID, propertyCategoryAlias FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'", $conn) or die("Cannot select reagent property categories: " . mysql_error());
		
		while ($propCategories = mysql_fetch_array($propCategories_set, MYSQL_ASSOC))
		{
			$propCatID = $propCategories["propertyCategoryID"];
			$propCatAlias = $propCategories["propertyCategoryAlias"];

			$propCategoryMap[$propCatID] = $propCatAlias;
		}

		return $propCategoryMap;
	}


	/**
	 * Equal and opposite: Map reagent property category aliases to IDs
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array dictionary of reagent property category alias => reagent property category ID tuples, e.g.  'general_properties' => '1'
	*/
	function getReagentPropertyCategory_Alias_ID_Map()
	{
		global $conn;

		$propCategoryMap = Array();

		$propCategories_set = mysql_query("SELECT propertyCategoryID, propertyCategoryAlias FROM ReagentPropTypeCategories_tbl WHERE status='ACTIVE'", $conn) or die("Cannot select reagent property categories: " . mysql_error());
		
		while ($propCategories = mysql_fetch_array($propCategories_set, MYSQL_ASSOC))
		{
			$propCatID = $propCategories["propertyCategoryID"];
			$propCatAlias = $propCategories["propertyCategoryAlias"];

			$propCategoryMap[$propCatAlias] = $propCatID;
		}

		return $propCategoryMap;
	}


	/**
	 * Map container type (group) IDs to names
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array dictionary of container type (historically called 'group') ID => container type name tuples, e.g. '2' => 'Glycerol Stocks'
	*/
	function mapContainer_ID_Name()
	{
		global $conn;
		$cont_ID_Name_ar = Array();

		$container_names_rs = mysql_query( "SELECT * FROM `ContainerGroup_tbl` WHERE `status`='ACTIVE' ORDER BY `contGroupID`" , $conn ) or die("Failure in container name sql" . mysql_error() );
		
		while( $container_names_ar = mysql_fetch_array( $container_names_rs, MYSQL_ASSOC ) )
		{
			$cont_ID_Name_ar[ $container_names_ar["contGroupID"] ] = $container_names_ar["contGroupName"];
		}
		
		return $cont_ID_Name_ar;
	}

	/**
	 * Map container type (group) IDs to names
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array dictionary of container type (historically called 'group') ID => container type name tuples, e.g.  'Glycerol Stocks' => '2'
	*/
	function mapContainer_Name_ID()
	{
		global $conn;
		$cont_Name_ID_ar = Array();

		$container_names_rs = mysql_query( "SELECT * FROM `ContainerGroup_tbl` WHERE `status`='ACTIVE' ORDER BY `contGroupID`" , $conn ) or die("Failure in container name sql" . mysql_error() );
		
		while( $container_names_ar = mysql_fetch_array( $container_names_rs, MYSQL_ASSOC ) )
		{
			$cont_Name_ID_ar[ $container_names_ar["contGroupName"] ] = $container_names_ar["contGroupID"];
		}
		
		return $cont_Name_ID_ar;
	}


	/**
	 * Map container type (group) IDs to codes (e.g. 'GS' for 'Glycerol Stocks'
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array dictionary of container type (historically called 'group') ID => container type code tuples, e.g.  'Glycerol Stocks' => 'GS'
	*/
	function mapContainerType_ID_Code()
	{
		global $conn;
		$contType_ID_Code_ar = Array();

		$container_types_rs = mysql_query("SELECT * FROM `ContainerGroup_tbl` WHERE `status`='ACTIVE'", $conn) or die("Error mapping container type name to code: " . mysql_error());
		
		while ($container_types_ar = mysql_fetch_array($container_types_rs, MYSQL_ASSOC))
		{
			$contType_ID_Code_ar[$container_types_ar["contGroupID"]] = $container_types_ar["contGroupCode"];
		}

		return $contType_ID_Code_ar;
	}

	// June 30, 2010
	/**
	 * Map laboratory names to IDs
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2010-06-30
	 *
	 * @return Array dictionary of lab name => lab ID tuples, e.g.  'Pawson Lab' => '1'
	*/
	function mapLab_Name_ID()
	{
		global $conn;

		$labName_ID_ar = Array();

		$labNames_rs = mysql_query("SELECT * FROM LabInfo_tbl WHERE status='ACTIVE'", $conn) or die("Cannot select lab info: " . mysql_error());

		while ($labNames_ar = mysql_fetch_array($labNames_rs, MYSQL_ASSOC))
		{
			$labName_ID_ar[$labNames_ar["lab_name"]] = $labNames_ar["labID"];
		}

		return $labName_ID_ar;
	}

	// June 30, 2010
	/**
	 * Equal and opposite: Map laboratory IDs to names
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2010-06-30
	 *
	 * @return Array dictionary of lab ID => lab name tuples, e.g. '1' => 'Pawson Lab'
	*/
	function mapLab_ID_Name()
	{
		global $conn;

		$labID_Name_ar = Array();

		$labNames_rs = mysql_query("SELECT * FROM LabInfo_tbl WHERE status='ACTIVE'", $conn) or die("Cannot select lab info: " . mysql_error());

		while ($labNames_ar = mysql_fetch_array($labNames_rs, MYSQL_ASSOC))
		{
			$labID_Name_ar[$labNames_ar["labID"]] = $labNames_ar["lab_name"];
		}

		return $labID_Name_ar;
	}
}
?>