<?php
/**
* PHP versions 4 and 5 http://php.net
*
* Copyright (c) 2005-2010 Mount Sinai Hospital, Toronto, Canada
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
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Canada
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* Include/require statements
*/
include_once "Chemical_Location_Class.php";

/**
 * This class represents a chemical
 * Written July 28, 2008
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Chemical
 *
 * @copyright	2005-2011 Mount Sinai Hospital, Toronto, Canada
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
class Chemical
{
	/**
	 * @var STRING
	 * Name of the chemical
	*/
	var $chemicalName;


	/**
	 * @var STRING
	 * CAS no. of the chemical (a mixture of alphanumeric characters)
	*/
	var $casNo;


	/**
	 * @var STRING
	 * Location of the chemical (e.g. '-40C Freezer')
	*/
	var $chemicalLocation;


	/**
	 * @var STRING
	 * Description of the chemical
	*/
	var $chemicalDescription;


	/**
	 * @var STRING
	 * Comments regarding the chemical
	*/
	var $comments;


	/**
	 * @var STRING
	 * Supplier of the chemical (e.g. 'Invitrogen')
	*/
	var $supplier;


	/**
	 * @var STRING
	 * Quantity of the chemical (e.g. '50 ml')
	*/
	var $quantity;


	/**
	 * @var STRING
	 * Safety information on the chemical
	*/
	var $safety;

	/**
	 * @var INT
	 * Internal database identifier of the chemical
	*/
	var $chemicalID;

	/**
	 * @var STRING
	 * Hyperlink to MSDS for this chemical
	*/
	var $msds;

	// Constructor
	/**
	 * Constructor
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING Name of the chemical
	 * @param STRING CAS no. of the chemical
	 * @param STRING Location of the chemical
	 * @param STRING Supplier of the chemical
	 * @param STRING Quantity of the chemical
	 * @param STRING Safety information of the chemical
	 * @param STRING Description of the chemical
	 * @param STRING Comments on the chemical
	 * @param INT Internal database ID of the chemical (optional)
	 *
	 * @see $chemicalName
	 * @see $casNo
	 * @see $chemicalLocation
	 * @see $supplier
	 * @see $quantity
	 * @see $safety
	 * @see $chemicalDescription
	 * @see $comments
	 * @see $chemicalID
	*/
	function Chemical($chemName, $casNo, $chemLocation, $supplier, $quantity, $safety, $chemDescription, $chemComments, $msds, $chemID=0)
	{
		$this->chemicalName = $chemName;
		$this->casNo = $casNo;
		$this->chemicalLocation = $chemLocation;
		$this->chemicalDescription = $chemDescription;
		$this->supplier = $supplier;
		$this->quantity = $quantity;
		$this->safety = $safety;
		$this->comments = $chemComments;
		$this->msds = $msds;
		$this->chemicalID = $chemID;
	}


	/**
	 * Return the name of this chemical
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @return STRING
	*/
	function getName()
	{
		return $this->chemicalName;
	}


	/**
	 * Return the CAS no. of this chemical
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @return STRING
	*/
	function getCAS_No()
	{
		return $this->casNo;
	}


	/**
	 * Return the description of this chemical
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @return STRING
	*/
	function getDescription()
	{
		return $this->chemicalDescription;
	}


	/**
	 * Return the location of this chemical
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @return STRING
	*/
	function getLocation()
	{
		return $this->chemicalLocation;
	}


	/**
	 * Return the supplier of this chemical
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @return STRING
	*/
	function getSupplier()
	{
		return $this->supplier;
	}


	/**
	 * Return the quantity of this chemical
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @return STRING
	*/
	function getQuantity()
	{
		return $this->quantity;
	}


	/**
	 * Return the safety information on this chemical
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @return STRING
	*/
	function getSafety()
	{
		return $this->safety;
	}


	/**
	 * Return the comments on this chemical
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @return STRING
	*/
	function getComments()
	{
		return $this->comments;
	}

	
	/**
	 * Return the database ID of this chemical
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @return INT
	*/
	function getChemicalID()
	{
		return $this->chemicalID;
	}

	/**
	 * Return the URL to the MSDS for this chemical
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @return INT
	*/
	function getMSDS()
	{
		return $this->msds;
	}

	
	/**
	 * Set the database ID of this chemical to the argument value
	 * 
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param INT chemID The internal database identifier of this chemial (corresponds to chemicalID column in Chemicals_tbl) 
	*/
	function setChemicalID($chemID)
	{
		$this->chemicalID = $chemID;
	}


	/**
	 * Set the location of this chemical to the argument value
	 * 
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param ChemicalLocation Location of this chemical (object)
	*/
	function setChemicalLocation($chemicalLocation)
	{
		$this->chemicalLocation = $chemicalLocation;
	}


	/**
	 * Set the MSDS hyperlink of this chemical to the argument value
	 * 
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @param STRING Hyperlink to the MSDS of this chemical
	*/
	function setMSDS($msds)
	{
		$this->msds = $msds;
	}
}
?>