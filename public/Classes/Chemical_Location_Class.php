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
* @author     Marina Olhovsky <olhosvky@lunenfeld.ca>
* @version    3.1
* @package Chemical
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
 * This class represents the storage location of a chemical
 * Examples of storage locations include cabinets, fridges, etc.
 *
 * Each location has a 'temperature' attribute (4C, -20C, room temperature) and a name ("Cabinet A", "Fridge 1", etc.)
 * Written July 28, 2008
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Chemical
 *
 * @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
class ChemicalLocation
{
	/**
	 * @var STRING
	 * Name of this location
	*/
	var $locationName;


	/**
	 * @var STRING
	 * Temperature of this location (e.g. 'Room temperature', or '-80')
	*/
	var $locationTemp;


	/**
	 * @var STRING
	 * Description of this location
	*/
	var $locationDescription;

	/**
	 * @var INT
	 * Internal database ID of this location
	*/
	var $locationID;


	/**
	 * Constructor
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING Name of the location
	 * @param STRING Temperature of the location
	 * @param STRING Description of the location
	*/
	function ChemicalLocation($locName, $locTemp, $locDescr)
	{
		$this->locationName = $locName;
		$this->locationTemp = $locTemp;
		$this->locationDescription = $locDescr;
	}


	/**
	 * Return the name of this location
	 *
	 * @return STRING
	*/
	function getName()
	{
		return $this->locationName;
	}


	/**
	 * Return the description of this location
	 *
	 * @return STRING
	*/
	function getDescription()
	{
		return $this->locationDescription;
	}


	/**
	 * Return the temperature of this location
	 *
	 * @return STRING
	*/
	function getTemperature()
	{
		return $this->locationTemp;
	}

	/**
	 * Return a string to output this location
	 *
	 * @return STRING
	*/
	function printLocation()
	{
		// updated July 31/08
// 		return $this->getName() . " " . $this->getTemperature() . " " . $this->getDescription();
		return $this->getName();
	}

	/**
	 * Set the temperature (type) of this location to the argument value
	 * 
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING Type/temperature of this location
	*/
	function setTemperature($locTemp)
	{
		$this->locationTemp = $locTemp;
	}

	/**
	 * Set the name of this location to the argument value
	 * 
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING Name of this location
	*/
	function setName($locName)
	{
		$this->locationName = $locName;
	}

	/**
	 * Set the internal database ID of this location to the argument value
	 * 
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT Internal database ID of this location
	*/
	function setLocationID($locID)
	{
		$this->locationID = $locID;
	}
}
?>
