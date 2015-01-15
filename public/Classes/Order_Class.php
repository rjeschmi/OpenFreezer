<?php

/**
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
* @author     Marina Olhovsky <olhosvky@lunenfeld.ca>
* @version    3.1
* @package Order
*
* @copyright  2005-2010 Pawson Laboratory
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* Include/require statements
*/

include "Reagent/Reagent_Function_Class.php";

/**
* This class represents a clone order
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1, 2008-01-23
* @package Order
*
* @copyright	2005-2010 Pawson Laboratory
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
class Order_Class
{
	/**
	 * @var INT
	 * Internal database ID of the reagent ordered (corresponds to Reagents_tbl.reagentID)
	*/
	var $reagentID;

	/**
	 * @var INT
	 * Isolate number of the prep being ordered
	*/
	var $isolateNum;

	/**
	 * @var INT
	 * Internal database ID of the container where the prep is stored
	*/
	var $containerID;

	/**
	 * @var INT
	 * Row number of the prep in the container
	*/
	var $row;

	/**
	 * @var INT
	 * Column number of the prep in the container
	*/
	var $column;

	/**
	 * @var INT
	 * Internal order ID
	*/
	var $orderKey;

	/**
	 * @var STRING
	 * Name of the reagent ordered
	*/
	var $name;

	/**
	 * @var Array
	 * List of selectable markers (resistance) of the reagent
	*/
	var $resistance;

	/**
	 * @var STRING
	 * Restrictions on use of the reagent (yes/no)
	*/
	var $restrictions;

	/**
	 * @var STRING
	 * Verification of the reagent
	*/
	var $verification;

	/**
	 * @var INT
	 * Project number of the reagent
	*/
	var $project;

	/**
	 * @var Array
	 * List of accession numbers for this reagent
	*/
	var $accession;

	/**
	 * @var STRING
	 * DNA sequence of this reagent
	*/
	var $sequence;		// June 2, 2011

	/**
	 * Constructor
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	 * @param INT
	 * @param INT
	 * @param INT
	 * @param INT
	*/
	function Order_Class($rID, $isoNum, $contID, $row, $col)
	{
		global $conn;

		$rfunc_obj = new Reagent_Function_Class();
		$bfunc_obj = new Reagent_Background_Class();	// Feb. 16, 2011

		$this->reagentID = $rID;
		$this->isolateNum = $isoNum;
		$this->containerID = $contID;
		$this->row = $row;
		$this->column = $col;

		$this->orderKey = $rID . "_" . $isoNum . "_" . $contID . "_" . $row . "_" . $col;

		// Jan. 24/08: Grab the reagent's name , resistance, verification, project and restrictions
		$namePropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

		$name = $rfunc_obj->getPropertyValue($rID, $namePropID);
		$this->name = $name;

		$selMarkerPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["selectable marker"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence Features"]);

		$resistance = $rfunc_obj->getPropertyValue($rID, $selMarkerPropID);
		$this->resistance = $resistance;

		$verificationPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["verification"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

		$verification = $rfunc_obj->getPropertyValue($rID, $verificationPropID);
		$this->verification = $verification;

		$projectPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["packet id"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

		$project = $rfunc_obj->getPropertyValue($rID, $projectPropID);
		$this->project = $project;

		$restrPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["restrictions on use"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

		$restrictions = $rfunc_obj->getPropertyValue($rID, $restrPropID);
		$this->restrictions = $restrictions;

		// Feb. 16, 2011: Anne-Claude's request (through Karen): Output accession(s) - make it a list
		$accessionPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["accession number"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]);

		// Now: accession is a property of Insert, and we're ordering Vectors.  Get the accession from the Insert.
		$insertID = $bfunc_obj->get_Insert($rID);
//		echo $insertID;

		$accession = $rfunc_obj->getPropertyValue($insertID, $accessionPropID);
//		print_r($accession);

		$this->accession = $accession;

		// June 2, 2011: include sequence
		$seqPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);

		$rSeq = $rfunc_obj->getSequenceByID($rfunc_obj->getPropertyValue($rID, $seqPropID));
		$this->sequence = $rSeq;
	}

	// Assignment methods

	/**
	 * Set the reagent ID for this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	*/
	function setReagentID($rID)
	{
		$this->reagentID = $rID;
	}

	/**
	 * Set the order key
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	*/
	function setOrderKey($oKey)
	{
		$this->orderKey = $oKey;
	}

	/**
	 * Set the isolate number for this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	*/
	function setisolateNum($isoNum)
	{
		$this->isolateNum = $isoNum;
	}

	/**
	 * Set the container ID
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	*/
	function setContainerID($contID)
	{
		$this->containerID = $contID;
	}

	/**
	 * Set the row number
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	*/
	function setRow($row)
	{
		$this->row = $row;
	}

	/**
	 * Set the column number
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	*/
	function setColumn($col)
	{
		$this->column = $col;
	}

	/**
	 * Set the name of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param STRING
	*/
	function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Set the 'restrictions on use' value of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param STRING
	*/
	function setRestrictions($restr)
	{
		$this->restrictions = $restr;
	}

	/**
	 * Set the selectable (resistance) marker of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param Array
	*/
	function setResistance($res)
	{
		$this->resistance = $res;
	}

	/**
	 * Set the project number of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT
	*/
	function setProject($project)
	{
		$this->project = $project;
	}

	/**
	 * Set the verification value of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param STRING
	*/
	function setVerification($ver)
	{
		$this->verification = $ver;
	}

	
	// Feb. 16, 2011
	/**
	 * Set the accession number(s) of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.2
	 *
	 * @param Array
	*/
	function setAccession($accession)
	{
		$this->accession = $accession;
	}


	// June 2, 2011
	/**
	 * Set the sequence of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.3
	 *
	 * @param Array
	*/
	function setSequence($seq)
	{
		$this->sequence = $seq;
	}


	// Access methods

	/**
	 * Return the ID of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return INT
	*/
	function getReagentID()
	{
		return $this->reagentID;
	}

	/**
	 * Return the isoalte number of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return INT
	*/
	function getIsolateNumber()
	{
		return $this->isolateNum;
	}

	/**
	 * Return the container ID
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return INT
	*/
	function getContainerID()
	{
		return $this->containerID;
	}

	/**
	 * Return the row number
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return INT
	*/
	function getRow()
	{
		return $this->row;
	}

	/**
	 * Return the column number
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return INT
	*/
	function getColumn()
	{
		return $this->column;
	}

	/**
	 * Return the order key
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return INT
	*/
	function getOrderKey()
	{
		$this->orderKey = $this->reagentID . "_" . $this->isolateNum . "_" . $this->containerID . "_" . $this->row . "_" . $this->column;

		return $this->orderKey;
	}

	/**
	 * Return the name of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return STRING
	*/
	function getName()
	{
		return $this->name;
	}

	/**
	 * Return the restrictions on use of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return STRIGN
	*/
	function getRestrictions()
	{
		return $this->restrictions;
	}

	/**
	 * Return the resistance markers of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return Array
	*/
	function getResistance()
	{
		return $this->resistance;
	}

	/**
	 * Return the project ID of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return INT
	*/
	function getProject()
	{
		return $this->project;
	}

	/**
	 * Return the verification vlaue of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @return STRING
	*/
	function getVerification()
	{
		return $this->verification;
	}

	/**
	 * Return the accession number(s) of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.2
	 *
	 * @return Array
	*/
	function getAccession()
	{
		return $this->accession;
	}

	
	// June 2, 2011
	/**
	 * Return the sequence of the reagent in this order
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.3
	 *
	 * @return Array
	*/
	function getSequence()
	{
		return $this->sequence;
	}

	/**
	 * Remove certain orders from a list of user's clone requests
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param Array list of order keys (INT) to remove
	*/
	function removeFromOrder($orderKeys)
	{
		$userOrders = $_SESSION["userinfo"]->getOrders();
		$remainingOrders = array();

		if (sizeof($orderKeys) > 0)
		{
			foreach ($orderKeys as $x => $orderKey)
			{
				foreach ($userOrders as $key => $order)
				{
					$oKey = $order->getOrderKey();
		
					if (strcasecmp($orderKey, $oKey) == 0)
					{
						unset($userOrders[$key]);
						$remainingOrders = $userOrders;
					}
				}
			}

//			print_r($remainingOrders);
	
			$_SESSION["userinfo"]->setOrders($remainingOrders);
		}
	}
}
?>
