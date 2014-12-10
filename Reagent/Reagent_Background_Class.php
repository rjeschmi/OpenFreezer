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
* @author John Paul Lee @version 2005
*
* @author     Marina Olhovsky <olhosvky@lunenfeld.ca>
* @version    3.1
* @package Reagent
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
 * Contains functions for handling reagent associations (parent-child)
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

class Reagent_Background_Class
{
	/**
	 * @var Array
	 * Map of association property ID => name (reflects Assoc_Prop_Type_tbl columns)
	*/
	var $AProp_ID_Name;

	/**
	 * @var Array
	 * Map of association property name => ID (reflects Assoc_Prop_Type_tbl columns) - inverse of $AProp_ID_Name
	*/
	var $AProp_Name_ID;

	/**
	 * @var STRING
	 * Error message returned by this class in various cases (contains the name of this class to help pinpoint the source of the error)
	*/
	var $classerror;
	
	/**
	 * Zero-argument constructor
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	*/
	function Reagent_Background_Class()
	{
		global $conn;
		
		$this->AProp_ID_Name = array();
		$this->AProp_Name_ID = array();
		$this->classerror = "Reagent_Background_Class->";

		$aprop_rs = mysql_query("SELECT * FROM `Assoc_Prop_Type_tbl` WHERE `status`='ACTIVE'", $conn ) or die( "FAILURE IN Reagent_BackGround_Class.Reagent_Background_Class(1): " . mysql_error() );

		while( $aprop_ar = mysql_fetch_array( $aprop_rs, MYSQL_ASSOC ) )
		{
			$this->AProp_ID_Name[ $aprop_ar["APropertyID"] ] = $aprop_ar["APropName"];
			$this->AProp_Name_ID[ $aprop_ar["APropName"] ] = $aprop_ar["APropertyID"];
		}
	}
	
	/**
	 * Fetches from the database and returns an integer that corresponds to a particular association type identified by $name.
	 * E.g. if $name is 'CellLine Stable', the return value is '5'.
	 * Result corresponds to ATypeID column in AssocType_tbl (input argument corresponds to 'association' column).
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param STRING
	 * @return INT
	*/
	function get_Association_Type($name)
	{
		global $conn;
		$functionerror = "get_Association_Type(";
		
		$type_rs = mysql_query("SELECT * FROM `AssocType_tbl` WHERE `status`='ACTIVE' AND `association`='" . trim($name) . "'", $conn) or die( $this->classerror . $functionerror . "13)" . mysql_error());

		if( $type_ar = mysql_fetch_array( $type_rs, MYSQL_ASSOC ) )
		{
			return $type_ar["ATypeID"];
		}
		
		return -1;
	}
	

	/**
	 * Retrieves the value of APropertyID column in Assoc_Prop_Type_tbl that corresponds to APropName identified by $name
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param STRING
	 * @return INT
	*/
	function get_Association_Property_Type($name)
	{
		global $conn;

		$functionerror = "get_Association_Property_Type(";
		
		$name = strtolower( $name );
		
		// Jan 20/06, Marina
		$query = "SELECT * FROM `Assoc_Prop_Type_tbl` WHERE `APropName`='" . trim( $name ) . "' AND `status`='ACTIVE'";

		// Jan 20, Marina
		$type_rs = mysql_query($query, $conn) or die($this->classerror . $functionerror . "13)" . mysql_error());

		if ($type_ar = mysql_fetch_array($type_rs, MYSQL_ASSOC))
		{
			return $type_ar["APropertyID"];
		}

		return -1;
	}
	

	/**
	 * Retrieves the association ID for a given reagent identified by $rid.  Returns value of assID column in Association_tbl that corresponds to $rid.
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	 * @param STRING
	 * @return INT
	*/
	function get_Assocation_ID($rid, $type)
	{
		global $conn;
		$functionerror = "get_Assocation_ID(";
		
		$check_rs = mysql_query("SELECT * FROM `Association_tbl` WHERE `reagentID`='" . $rid . "' " . "AND `status`='ACTIVE' AND `ATypeID`='" . $this->get_Association_Type( $type ) . "'", $conn )or die( $this->classerror . $functionerror . "8): " . mysql_error() );
		
		if ($check_ar = mysql_fetch_array($check_rs, MYSQL_ASSOC))
		{
			return $check_ar["assID"];
		}
		
		return -1;
	}
	

	/**
	 * Return the ID of the parent of reagent identified by $rid.  Parent specification is given by $type - to determine which of the parents should be returned if there is more than one, e.g. parent vector or parent insert for non-recombination vector.  Return -1 if parent is not found.
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	 * @param STRING
	 * @return INT
	*/
	function get_Background_rid($rid, $type)
	{
		global $conn;

		$type = strtolower($type);

		if (in_array($type, $this->AProp_ID_Name))
		{
			$vpID_rs = mysql_query("SELECT * FROM `AssocProp_tbl` a, `Association_tbl` b WHERE a.`assID`=b.`assID` AND a.`status`='ACTIVE' AND b.`status`='ACTIVE' AND b.`reagentID`='" . $rid . "' AND a.`APropertyID`='" . $this->AProp_Name_ID[$type] . "' ", $conn) or die( "FAILURE IN Reagent_BackGround_Class.get_Background_rid(1): " . mysql_error());

			if( $vpID_ar = mysql_fetch_array( $vpID_rs, MYSQL_ASSOC ) )
			{
				return $vpID_ar["propertyValue"];
			}
		}
		
		return -1;
	}
	

	/**
	 * Return the potential parent types for a given reagent type (e.g. Vector and Insert can be used to generate a Vector, but a Cell Line can never be the parent of a Vector).
	 *
	 * (This is not 100% perfect, because reagent subtypes are not taken into account. I.e. an Insert is only a potential parent of Vector, but not of any Vector - only of non-recombination vectors, not recombination!  This function is used in Reagent_Output_Class->print_Detailed_Reagent_Other(), to display parents on detailed view, but there's still hard-coding to differentiate between Vector subtypes.  Needs to be refined in the future.)
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	 * @return Array
	*/
	function getAssociationTypes($rTypeID)
	{
		global $conn;
		$gfunc_obj = new generalFunc_Class();

		$assocTypes = Array();

		$resultSet = mysql_query("SELECT APropertyID, reagentTypeID, APropName FROM Assoc_Prop_Type_tbl WHERE reagentTypeID='" . $rTypeID . "' AND status='ACTIVE'", $conn) or die(mysql_error());

		while ($results = mysql_fetch_array($resultSet, MYSQL_ASSOC))
		{
			$assocTypes[] = $results["APropertyID"];
		}

		return $assocTypes;
	}

	/**
	 * Retrieve parent IDs of a given reagent.
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	 * @return Array
	*/
	function getReagentParents($reagentIDToView)
	{
		global $conn;
		$gfunc_obj = new generalFunc_Class();

		$parents = Array();

		$resultSet = mysql_query("SELECT APropertyID, propertyValue FROM Association_tbl a, AssocProp_tbl p WHERE a.reagentID='" . $reagentIDToView . "' AND a.assID=p.assID AND a.status='ACTIVE' AND p.status='ACTIVE'", $conn) or die("Error in getReagentParents() function: " . mysql_error());

		while ($results = mysql_fetch_array($resultSet, MYSQL_ASSOC))
		{
			$parentID = $results["propertyValue"];
			$assocPropID = $results["APropertyID"];

			if (!in_array($assocPropID, array_keys($parents)))
				$tmp_ar = Array();
			else
				$tmp_ar = $parents[$assocPropID];

			$tmp_ar[] = $parentID;

			$parents[$assocPropID] = $tmp_ar;
		}

		return $parents;
	}

	/**
	 * Retrieve all the children of a given reagent (their internal database IDs).
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	 * @return Array
	*/
	function getReagentChildren($reagentIDToView)
	{
		global $conn;
		$gfunc_obj = new generalFunc_Class();

		$children = Array();
		
		$resultSet = mysql_query("SELECT p.APropertyID, a.reagentID FROM AssocProp_tbl p, Association_tbl a WHERE p.propertyValue='" . $reagentIDToView . "' AND p.status='ACTIVE' AND a.assID=p.assID AND p.status='ACTIVE'", $conn) or die("Error in getReagentParents() function: " . mysql_error());

		while ($results = mysql_fetch_array($resultSet, MYSQL_ASSOC))
		{
			$aPropID = $results["APropertyID"];
			$propVal = $results["reagentID"];

			$children[$aPropID][] = $propVal;
		}

		return $children;
	}


	/**
	 * Retrieve a specific type of a reagent's children, e.g. get only the reagents for which the current reagent is a parent cell line.  Used in different reagent views (differentiate between types of reagents and their children).
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	 * @param STRING
	 * @return Array
	*/
	function get_Children($rid, $type)
	{
		global $conn;
		$children_ar = array();
		$children_set = "('";
		$setcount = 0;

		if( $type == "Vector Children" )
		{
			// Updated Feb. 5/07, Marina -- Get PV and IPV in one query
			$vcID_rs = mysql_query("SELECT * FROM `AssocProp_tbl` a1 WHERE a1.`status`='ACTIVE' AND a1.`APropertyID`='" . $this->AProp_Name_ID["vector parent id"] . "' AND a1.`propertyValue`='" . $rid . "' UNION SELECT * FROM `AssocProp_tbl` a2 WHERE a2.`status`='ACTIVE' AND a2.`APropertyID`='" . $this->AProp_Name_ID["parent insert vector"] . "' AND a2.`propertyValue`='" . $rid . "'", $conn) or die( "FAILURE IN Reagent_BackGround_Class.get_children(0): " . mysql_error());
	
			while( $vcID_ar = mysql_fetch_array( $vcID_rs, MYSQL_ASSOC ) )
			{
				$children_set = $children_set . $vcID_ar["assID"] . "','";
				$setcount++;
			}
			
			if( $setcount > 0 )
			{
				$children_set = substr( $children_set, 0, strlen( $children_set ) - 2 ) . ")";
			}
			else
			{
				$children_set = "('')";
			}
		}
		// Added April 21/06 by Marina
		elseif( $type == "Vector Insert Children")
		{
			$vcID_rs = mysql_query( "SELECT * FROM `AssocProp_tbl` "
							. "WHERE `status`='ACTIVE' "
							. "AND `APropertyID`='" . $this->AProp_Name_ID["insert parent vector id"]
							. "' AND `propertyValue`='" . $rid . "' ", $conn )
							or die( "FAILURE IN Reagent_BackGround_Class.get_children(0): " . mysql_error() );
	
			while( $vcID_ar = mysql_fetch_array( $vcID_rs, MYSQL_ASSOC ) )
			{
				$children_set = $children_set . $vcID_ar["assID"] . "','";
				$setcount++;
			}
			
			if( $setcount > 0 )
			{
				$children_set = substr( $children_set, 0, strlen( $children_set ) - 2 ) . ")";
			}
			else
			{
				$children_set = "('')";
			}
		}
		elseif( $type == "Insert Children" )
		{
			// Kept separate in case I need to extend this later on
			$vcID_rs = mysql_query( "SELECT * FROM `AssocProp_tbl` "
							. "WHERE `status`='ACTIVE' "
							. "AND `APropertyID`='" . $this->AProp_Name_ID["insert id"] . "' "
							. "AND `propertyValue`='" . $rid . "' ", $conn )
							or die( "FAILURE IN Reagent_BackGround_Class.get_children(1): " . mysql_error() );

			while( $vcID_ar = mysql_fetch_array( $vcID_rs, MYSQL_ASSOC ) )
			{
				$children_set = $children_set . $vcID_ar["assID"] . "','";
				$setcount++;
			}
			
			if( $setcount > 0 )
			{
				$children_set = substr( $children_set, 0, strlen( $children_set ) - 2 ) . ")";
			}
			else
			{
				$children_set = "('')";
			}
		}
		elseif( $type == "Cellline Children" )
		{
			// Kept separate in case I need to extend this later on
			$vcID_rs = mysql_query( "SELECT * FROM `AssocProp_tbl` "
							. "WHERE `status`='ACTIVE' "
							. "AND `APropertyID`='" . $this->AProp_Name_ID["parent cell line id"] . "' "
							. "AND `propertyValue`='" . $rid . "' ", $conn )
							or die( "FAILURE IN Reagent_BackGround_Class.get_children(2): " . mysql_error() );
		
			while( $vcID_ar = mysql_fetch_array( $vcID_rs, MYSQL_ASSOC ) )
			{
				$children_set = $children_set . $vcID_ar["assID"] . "','";
				$setcount++;
			}
			
			if( $setcount > 0 )
			{
				$children_set = substr( $children_set, 0, strlen( $children_set ) - 2 ) . ")";
			}
			else
			{
				$children_set = "('')";
			}
		}
		// Added May 2/06 by Marina
		elseif ($type == "Vector CellLine Children")
		{
			$vcID_rs = mysql_query("SELECT * FROM `AssocProp_tbl` WHERE `status`='ACTIVE' AND `APropertyID`='" . 
				   $this->AProp_Name_ID["cell line parent vector id"] . "' " . "AND `propertyValue`='" . $rid . "' ", $conn)
				   or die("FAILURE IN Reagent_BackGround_Class.get_children(\"Vector CellLine Children\"): " . mysql_error());
		
			while( $vcID_ar = mysql_fetch_array( $vcID_rs, MYSQL_ASSOC ) )
			{
				$children_set = $children_set . $vcID_ar["assID"] . "','";
				$setcount++;
			}
			
			if( $setcount > 0 )
			{
				$children_set = substr( $children_set, 0, strlen( $children_set ) - 2 ) . ")";
			}
			else
			{
				$children_set = "('')";
			}
		}
		elseif( $type == "Sense Oligo Children" )
		{
			// Kept separate incase I need to extend this later on
			$vcID_rs = mysql_query( "SELECT * FROM `AssocProp_tbl` "
						. "WHERE `status`='ACTIVE' "
						. "AND `APropertyID`='" . $this->AProp_Name_ID["sense oligo"] . "' "
						. "AND `propertyValue`='" . $rid . "' ", $conn )
						or die( "FAILURE IN Reagent_BackGround_Class.get_children(3): " . mysql_error() );
				
			while( $vcID_ar = mysql_fetch_array( $vcID_rs, MYSQL_ASSOC ) )
			{
				$children_set = $children_set . $vcID_ar["assID"] . "','";
				$setcount++;
			}
			
			if( $setcount > 0 )
			{
				$children_set = substr( $children_set, 0, strlen( $children_set ) - 2 ) . ")";
			}
			else
			{
				$children_set = "('')";
			}
		}
		elseif( $type == "AntiSense Oligo Children" )
		{
			// Kept separate incase I need to extend this later on	
			$vcID_rs = mysql_query( "SELECT * FROM `AssocProp_tbl` "
							. "WHERE `status`='ACTIVE' "
							. "AND `APropertyID`='" . $this->AProp_Name_ID["antisense oligo"] . "' "
							. "AND `propertyValue`='" . $rid . "' ", $conn )
							or die( "FAILURE IN Reagent_BackGround_Class.get_children(4): " . mysql_error() );
		
			while( $vcID_ar = mysql_fetch_array( $vcID_rs, MYSQL_ASSOC ) )
			{
				$children_set = $children_set . $vcID_ar["assID"] . "','";
				$setcount++;
			}
			
			if( $setcount > 0 )
			{
				$children_set = substr( $children_set, 0, strlen( $children_set ) - 2 ) . ")";
			}
			else
			{
				$children_set = "('')";
			}
		}
		elseif( $type == "Oligo Children" )
		{
			// Kept separate incase I need to extend this later on
			$vcID_rs = mysql_query( "SELECT * FROM `AssocProp_tbl` "
							. "WHERE `status`='ACTIVE' "
							. "AND (`APropertyID`='" . $this->AProp_Name_ID["sense oligo"] 
							. "' OR `APropertyID`='" . $this->AProp_Name_ID["antisense oligo"]
							. "') AND `propertyValue`='" . $rid . "' ", $conn )
							or die( "FAILURE IN Reagent_BackGround_Class.get_children(4): " . mysql_error() );
		
			while( $vcID_ar = mysql_fetch_array( $vcID_rs, MYSQL_ASSOC ) )
			{
				$children_set = $children_set . $vcID_ar["assID"] . "','";
				$setcount++;
			}
			
			if( $setcount > 0 )
			{
				$children_set = substr( $children_set, 0, strlen( $children_set ) - 2 ) . ")";
			}
			else
			{
				$children_set = "('')";
			}
		}
		else
		{
			echo "Abnormal Failure in: Reagent_Background_Class.get_children(6): Failure to find a matching type!";
			$children_set = "('')";
		}

		$setcount = 0;

		$query = "SELECT * FROM `Association_tbl` WHERE `status`='ACTIVE' AND `assID` IN " . $children_set;

		$rid_rs = mysql_query($query, $conn) or die("FAILURE IN Reagent_BackGround_Class.get_children(5): " . mysql_error());

		while( $rid_ar = mysql_fetch_array( $rid_rs , MYSQL_ASSOC ) )
		{
			$children_ar[] = $rid_ar["reagentID"];
		}
		
		return $children_ar;
	}

	/**
	 * Added Feb. 5/07 by Marina (originally taken from http://us3.php.net/manual/en/function.usort.php - Comparison function to sort array values in ascending order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>, source http://us3.php.net/manual/en/function.usort.php
	 * @version 3.1
	 *
	 * @param Array
	 * @param Array
	 * @return INT
	*/
	function cmp($a, $b)
	{
		if ($a == $b) 
		{
			return 0;
		}

		return ($a < $b) ? -1 : 1;
	}


	/**
	 * Print a list of children of the current reagent - prints the results returned by get_Children().
	 *
	 * Modified by Marina Feb 2/07: Add show/hide option to display Vector children and Cell Line children lists (as they are getting too long)
	 *
	 * Last update: Feb. 5/07
	 *
	 * @param INT $rid internal db ID of reagent being viewed
	 * @param STRING $type association type - 'Vector children' or 'Cell Line Children'
	 * @param STRING $subtype one-word identifier to pass to Javascript function, to indicate which list is to be shown/hidden
	 * @see get_Children()
	*/
	function print_Children($rid, $type, $subtype)
	{
		$gfunc_obj = new generalFunc_Class();

		$children = $this->get_Children( $rid, $type );
		$children_lab_ids = $gfunc_obj->getLabIDs($children);		// added Feb. 2/07, Marina

		// Sept. 6/07
		$currUserCategory = $_SESSION["userinfo"]->getCategory();

		if (sizeof($children_lab_ids) > 0)				// feb 2/07, marina
		{
			// Feb 2/07, Marina - For now just have show/hide link for vector children, use old way for Inserts
			if (($type == "Vector Children") || ($type == "Vector CellLine Children") || ($type == "Vector Insert Children"))
			{
				$cols = 0;
				$MAX_COLS = 17;
	
				?>
				<SPAN id="show_<?php echo $subtype; ?>" class="linkShow" onclick="showHideChildren(this.id, 'children_<?php echo $subtype; ?>')">Show Children</SPAN>
				<SPAN id="hide_<?php echo $subtype; ?>" class="linkHide" onclick="showHideChildren(this.id, 'children_<?php echo $subtype; ?>')">Hide Children</SPAN>
				&nbsp;&nbsp;<B>(<?php echo sizeof($children_lab_ids); ?>&nbsp; total)</B>
				<tr>
					<td id="children_<?php echo $subtype; ?>" class="children">
						<div class="children">
							<TABLE class="children">
								<TR>
								<?php
									foreach ($children_lab_ids as $key => $rid_tmp)
									{
										if ($cols == $MAX_COLS)
										{
											echo "</tr><tr>";
											$cols = 0;
										}
						
										echo "<td>";

										// August 20/07: Restrict viewing by project - if parent belongs to a project you don't have access to, don't allow linking
										$tmpProject = $rfunc_obj->getReagentProjectID($key);
										$userProjects = getAllowedUserProjectIDs($_SESSION["userinfo"]->getUserID());

										if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($tmpProject, $userProjects))
										{
											echo "<a href=\"Reagent.php?View=6&rid=" . $key . "\">" . $rid_tmp . "</a>&nbsp;";
											echo "</td>";
										}
										else
										{
											echo "<span class=\"linkDisabled\">";
											echo $rid_tmp;
											echo "</span> &nbsp;";
										}

										$cols++;
									}
								?>
								</TR>
							</TABLE>
						</div>	
					</td>
				</tr>
				<?php
			}
			else
			{
				foreach ($children_lab_ids as $key => $rid_tmp)
				{
					// August 20/07: Restrict viewing by project - if parent belongs to a project you don't have access to, don't allow linking
					$tmpProject = getReagentProjectID($key);
					$userProjects = getAllowedUserProjectIDs($_SESSION["userinfo"]->getUserID());

					if (($currUserCategory == $_SESSION["userCategoryNames"]["Admin"]) || in_array($tmpProject, $userProjects))
					{
						echo "<a href=\"Reagent.php?View=6&rid=" . $key . "\">";
						echo $rid_tmp;
						echo "</a> &nbsp;";
					}
					else
					{
						echo "<span class=\"linkDisabled\">";
						echo $rid_tmp;
						echo "</span> &nbsp;";
					}
				}	
			}
		}
		else
 		{
 			echo "&nbsp;";
 		}
	}	

	/**
	 * Return the Insert that belongs to the reagent (Vector) identified by $rid
	 *
	 * @param INT $rid internal db ID of the Vector whose Insert needs to be retrieved
	 * @return INT Internal database ID of the requested Insert
	*/
	function get_Insert($rid)
	{
		global $conn;
		$functionerror = "get_Insert(";

		// find out the assocTypeID of property "parent insert vector"
		$insert_aid =  $this->get_Association_Property_Type("insert id");

		$query = "SELECT * FROM `Association_tbl` A1 INNER JOIN `AssocProp_tbl` A2 ON A1.`assID` = A2.`assID` WHERE A1.`reagentID` = '" . $rid . " ' AND A2.`APropertyID`='" . $insert_aid . "' AND A1.`status`='ACTIVE' AND A2.`status`='ACTIVE'";

		$insert_rs = mysql_query($query, $conn) or die( $classerror . $functionerror . "2):" . mysql_error() );
		
		if ($insert_ar = mysql_fetch_array($insert_rs, MYSQL_ASSOC))
		{
			return $insert_ar["propertyValue"];
		}

		return -1;
	}
}
?>
