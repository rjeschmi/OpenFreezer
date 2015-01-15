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
* @package User
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* This class represents users of OpenFreezer (members of biological research laboratories)
*
* @author John Paul Lee @version 2005
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
* @package User
*
* @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
class Member_Class
{
	/**
	 * @var INT
	 * Internal database identifier of the user (corresponds to 'userID' column in Users_tbl)
	*/
	var $userID;

	/**
	 * @var STRING
	 * Username - a User's login alias (one-word lowercase; corresponds to 'username' column in Users_tbl)
	*/
	var $username;

	/**
	 * @var INT
	 * A number that represents the user's OpenFreezer access level as follows: 1 - Admin, 2 - Creator, 3 - Writer, 4 - Reader.  Corresponds to 'category' column in Users_tbl.
	*/
	var $category;	// May 24/07 - the category of the current user - Admin, Creator, Writer, Reader

	/**
	 * @var INT
	 * Laboratory identifier (corresponds to 'labID' column in Users_tbl, foreign key references LabInfo_tbl; e.g. '1' - 'Pawson Lab')
	*/
	var $labID;	// June 19/07

	/**
	 * @var STRING
	 * MD5-encoded user password
	*/
	var $password;

	/**
	 * @var STRING
	 * Full name of the current user - 'Firstname Lastname' (e.g. 'John Doe'). Corresponds to 'description' column in Users_tbl
	*/
	var $description;	// June 27/07, Marina - Full name of the user: 'Firstname Lastname'

	/**
	 * @var STRING
	 * User's email address
	*/
	var $email;

	/**
	 * @var STRING
	 * User's client IP address
	*/
	var $ip;

	/**
	 * @var INT
	 * Cookie ID
	*/
	var $cookieID;

	/**
	 * @var INT
	 * User's session ID
	*/
	var $sessionID;
	
	// the following 2 attributes were added August 7/07 by Marina

	/**
	 * @var STRING
	 * User's first name
	*/
	var $firstName;

	/**
	 * @var STRING
	 * User's last name
	*/
	var $lastName;	
	
	/**
	 * @var STRING
	 * User's position at the lab (optional)
	*/
	var $position;		// Aug 8/07, Marina: actual work title
	
	/**
	 * @var Array
	 * List of user's clone orders.
	 * @see setOrders(), getOrders()
	*/
	// Jan. 23/08
	var $orders;

	/**
	 * Constructor. Generates a User representation with the given parameters (user ID is mandatory, rest can be blank)
	 *
	 * @param INT
	 * @param STRING
	 * @param STRING
	 * @param STRING
	 * @param INT
	 * @param INT
	 * @param STRING
	 * @param INT
	 * @param INT
	 * @param STRING
	 *
	 * @see userID
	 * @see username
	 * @see description
	 * @see email
	 * @see category
	 * @see labID
	 * @see ip
	 * @see cookieID
	 * @see sessionID
	 * @see password
	*/
	// Modified August 10/07 - Set default values
	function Member_Class($newUserID, $newUsername="", $newDescr="", $newEmail="", $newCategory=0, $newLabID=0, $newIP="", $newCookieID=0, $newSessionID=0, $newPassword="")
	{
		$this->userID = $newUserID;
		$this->username = $newUsername;
		$this->description = $newDescr;		// June 27/07, Marina
		$this->password = $newPassword;
		$this->email = $newEmail;
		$this->ip = $newIP;
		$this->cookieID = $newCookieID;
		$this->sessionID = $newSessionID;
		$this->category = $newCategory;		// May 24/07, Marina
		$this->labID = $newLabID;		// June 19/07, Marina
	}
	
	/********************************
	* Access methods
	*******************************/

	/**
	 * Return the user's ID
	 * @return INT
	 * @see userID
	*/
	function getUserID()
	{
		return $this->userID;
	}
	
	/**
	 * Return the user's username
	 * @return STRING
	 * @see username
	*/
	function getUsername()
	{
		return $this->username;
	}
	
	/**
	 * Return the user's description
	 * @return STRING
	 * @see description
	*/
	function getDescription()
	{
		return $this->description;
	}
	
	/**
	 * Return the user's password (MD5-encrypted)
	 * @return STRING
	 * @see password
	*/
	function getPassword()
	{
		return $this->password;
	}
	
	/**
	 * Return the user's email address
	 * @return STRING
	 * @see email
	*/
	function getEmail()
	{
		return $this->email;
	}
	
	/**
	 * Return the user's client IP address
	 * @return STRING
	 * @see ip
	*/
	function getIP()
	{
		return $this->ip;
	}
	
	/**
	 * Return the user's cookie ID
	 * @return INT
	 * @see cookieID
	*/
	function getCookieID()
	{
		return $this->cookieID;
	}
	
	/**
	 * Return the user's session ID
	 * @return INT
	 * @see sessionID
	*/
	function getSessionID()
	{
		return $this->sessionID;
	}
	
	/**
	 * Return the user's category ID
	 * @return INT
	 * @see category
	*/
	// May 24/07, Marina
	function getCategory()
	{
		return $this->category;
	}
	
	/**
	 * Return the user's laboratory ID
	 * @return INT
	 * @see labID
	*/
	// June 19/07, Marina
	function getLabID()
	{
		return $this->labID;
	}

	/**
	 * Return the user's first name
	 * @return STRING
	 * @see firstName
	*/
	function getFirstName()
	{
		return $this->firstName;
	}
	
	/**
	 * Return the user's last name
	 * @return STRING
	 * @see lastName
	*/
	function getLastName()
	{
		return $this->lastName;
	}

	/**
	 * Return the user's position
	 * @return STRING
	 * @see position
	*/
	function getPosition()
	{
		return $this->position;
	}

	/**
	 * Return the user's clone requests
	 * @return ARRAY
	 * @see orders
	*/
	function getOrders()
	{
		return $this->orders;
	}
	
	/************************************************
	* Assignment methods - Added August 7/07, Marina
	************************************************/

	/**
	 * Set the user's first name equal to the parameter value
	 * @param STRING
	 * @see firstName
	*/
	function setFirstName($fName)
	{
		$this->firstName = $fName;
	}
	
	/**
	 * Set the user's last name equal to the parameter value
	 * @param STRING
	 * @see lastName
	*/
	function setLastName($lName)
	{
		$this->lastName = $lName;
	}
	
	/**
	 * Set the user's position equal to the parameter value
	 * @param STRING
	 * @see position
	*/
	// August 8/07: New attribute: position
	function setPosition($pos)
	{
		$this->position = $pos;
	}

	// Jan. 23/08
	/**
	 * Assign user's orders
	 * @param Array
	 * @see orders
	*/
	function setOrders($orders)
	{
		$this->orders = $orders;
	}
}
?>