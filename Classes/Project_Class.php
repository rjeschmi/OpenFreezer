<?php
/**
* Classes in this package represent projects (historically called 'packets') in OpenFreezer.  Projects are used to group reagents that fit a common theme (e.g. "SH2 domains", "Kinase Project", or "John Doe's reagents"
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
* @package Project
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
 * This class represents a project (historically called 'packet') in OpenFreezer.  Projects are used to group reagents that fit a common theme (e.g. "SH2 domains", "Kinase Project", or "John Doe's reagents"
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Project
 *
 * @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 */
class Project
{
	/**
	 * @var INT
	 * Numeic identifier of this project
	*/
	var $packetID;

	/**
	 * @var STRING
	 * Name of this project
	*/
	var $packetName;

	/**
	 * @var Member_Class
	 * Person who owns this project
	*/
	var $packetOwner;	// Member instance

	/**
	 * @var STRING
	 * Description of this project
	*/
	var $packetDescription;

	/**
	 * @var boolean
	 * Is this project public?  A project can be public, namely reagents associated with this project are accessible by all users of OpenFreezer; alternatively, a project can be private, in which case only users who are given viewing privileges may view reagents in this project.
	*/
	var $isPublic;
	
	/**
	 * Constructor.  Project ID must be set, the rest of values may be blank.
	 *
	 * @param INT
	 * @param STRING
	 * @param Member_Class
	 * @param STRING
	 * @param boolean
	 *
	 * @see packetID
	 * @see packetName
	 * @see packetOwner
	 * @see packetDescription
	 * @see isPublic
	*/
	// Project ID must be set, the rest of values may be blank
	function Project($pID, $pName="", $pOwner=Null, $pDesc="", $public=true)
	{
		$this->packetID = $pID;
		$this->packetName = $pName;
		$this->packetOwner = $pOwner;
		$this->packetDescription = $pDesc;
		$this->isPublic = $public;
	}
	

	/***********************
	* Assignment methods
	***********************/

	/**
	 * Set the name of this project equal to the argument value.
	 * @param STRING
	 * @see packetName
	*/
	function setName($pName)
	{
		$this->packetName = $pName;
	}
	
	/**
	 * Set the owner of this project equal to the argument value.
	 * @param Member_Class
	 * @see packetOwner
	*/
	function setOwner($owner)
	{
		$this->packetOwner = $owner;
	}
	
	/**
	 * Set the description of this project equal to the argument value.
	 * @param STRING
	 * @see packetDescription
	*/
	function setDescription($desc)
	{
		$this->$packetDescription = $desc;
	}
	
	/**
	 * Set the value of $isPublic variable to the argument value ($public=TRUE makes this project public; $public=FALSE makes the project private)
	 * @param boolean
	 * @see isPublic
	*/
	function setPublic($public)
	{
		$this->isPublic = $public;
	}
	
	/***********************
	* Access methods
	***********************/

	/**
	 * Return the ID of this project
	 * @return INT
	 * @see packetID
	*/
	function getPacketID()
	{
		return $this->packetID;
	}
	
	/**
	 * Return the name of this project
	 * @return STRING
	 * @see packetName
	*/
	function getName()
	{
		return $this->packetName;
	}
	
	/**
	 * Return the owner of this project
	 * @return Member_Class
	 * @see packetOwner
	*/
	function getOwner()
	{
		return $this->packetOwner;
	}
	
	/**
	 * Return the description of this project
	 * @return STRING
	 * @see packetDescription
	*/
	function getDescription()
	{
		return $this->packetDescription;
	}
	
	/**
	 * Return TRUE or FALSE, depending on whether this project is public or private.
	 * @return boolean
	 * @see isPublic
	*/
	function isPublic()
	{
		return $this->isPublic;
	}
}
?>