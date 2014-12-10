<?php
/**
* Auxiliary functions for managing user information in OpenFreezer
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
* @author     Marina Olhovsky <olhosvky@lunenfeld.ca>
* @version    3.1
* @package User
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
	// Get the category of the user identified by $userID
	// Output: INTEGER {1,2,3,4}, representing the user's access level to OpenFreezer
	/**
	  * @name Retrieve from the database and return the given user's category (access level to OpenFreezer)
	  * @param INT
	  * @return INT
	*/
	function getUserCategory($userID)
	{
		global $conn;

		$categoryInfo_rs = mysql_query("SELECT category FROM `Users_tbl` WHERE `userID`='" . $userID . "' AND `status`='ACTIVE'", $conn) or die(mysql_error());
		
		if ($categoryInfo_ar = mysql_fetch_array($categoryInfo_rs, MYSQL_ASSOC))
		{
			$currUserCategory = $categoryInfo_ar["category"];
		}

		mysql_free_result($categoryInfo_rs);
		unset($categoryInfo_rs, $categoryInfo_ar);

		return $currUserCategory;
	}

	/**
	  * @name Retrieve from the database and return the given user's laboratory (its internal database ID, e.g. '1' => 'Pawson Lab')
	  * @param INT
	  * @return INT
	*/
	function getUserLabID($userID)
	{
		global $conn;
		
		$uLabID = 0;

		$lab_rs = mysql_query("SELECT labID FROM Users_tbl WHERE userID='" . $userID . "'", $conn) or die("Cannot select user lab ID: " . mysql_error());

		if ($lab_ar = mysql_fetch_array($lab_rs, MYSQL_ASSOC))
		{
			$uLabID = $lab_ar["labID"];
		}

		mysql_free_result($lab_rs);
		unset($lab_ar);

		return $uLabID;
	}
?>