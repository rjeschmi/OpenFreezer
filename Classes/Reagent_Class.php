<?php
/**
* Classes in this package represent reagent entities in OpenFreezer
*
* Written October 20, 2008, by Marina Olhovsky
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
* @package Reagent
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* This class represents a reagent entity in OpenFreezer
*
* Written October 20, 2008, by Marina Olhovsky
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
* @package Reagent
*
* @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
class Reagent_Class
{
	/******************************************************
	* Instance variables - Basic properties of the reagent
	******************************************************/

	/**
	* @var MIXED
	* OpenFreezer ID of the reagent - a one-letter prefix followed by a numeric identifier (e.g. V123)
	*/
	var $rID = "";

	/**
	* @var STRING
	* Type of the reagent (e.g. 'Vector', 'Protein', 'Antibody', etc.)
	*/
	var $rType = "";	// one of Vector, Insert, Oligo, Cell Line (more if added later)

	/**
	* @var STRING
	* Name of the reagent, e.g. 'MAP Kinase'
	*/
	var $rName = "";

	/**
	* @var STRING
	* Status of the reagent, e.g. 'Completed', 'Not Available', etc.
	*/
	var $rStatus = "";

	/**
	* @var INT
	* Numeric ID of this reagent's project
	*/
	var $projectID = 0;

	/**
	* @var STRING
	* Experimental verification of the reagent, e.g. 'sequence verified', 'expression verified', etc.
	*/
	var $rVerification = "";

	/**
	* @var STRING
	* (Functional) description of the reagent
	*/
	var $funcDescr = "";

	/**
	* @var STRING
	* (Experimental) comments for this reagent
	*/
	var $expComments = "";

	/**
	* @var STRING
	* Verification comments for this reagent
	*/
	var $verComments = "";

	/**
	* @var STRING
	* Reagent source, e.g. 'in-house', or 'Invitrogen'
	*/
	var $rSource = "";

	/**
	* @var STRING
	* Origin species of this reagent (differs from antibody reactivity species)
	*/
	var $rSpecies = "";

	/**
	* @var BOOLEAN
	* Are there any restrictions on the use of this reagent for future experiments?
	*/
	var $hasRestrictions = false;

	/**
	* @var STRING
	* DNA, RNA or Protein sequence of this reagent, if applicable.  Represented by a string of allowable characters (ACGTN for DNA, ACGU for RNA, and amino acids for protein)
	*/
	var $rSequence = "";
	
	/**
	* @var Array
	* List of SeqFeature objects
	*/
	var $rFeatures = array();	// list of SeqFeature objects

	/**
	* Default constructor. Generates a Reagent instance based on the OpenFreezer ID of the reagent
	* @param STRING OpenFreezer ID of this reagent (e.g. 'V123')
	*/
	function Reagent_Class($rID)
	{
		$this->rID = $rID;
	}

	/******************************************************************************
	* Assignment methods
	* (simply set the value of the given instance variable to the parameter value)
	******************************************************************************/

	/**
	 * Set the name of this reagent equal to the parameter value
	 * @param STRING
	 * @see rName
	*/
	function setReagentName($rName)
	{
		$this->rName = $rName;
	}


	/**
	 * Set the status of this reagent equal to the parameter value
	 * @param STRING
	 * @see rStatus
	*/
	function setReagentStatus($status)
	{
		$this->rStatus = $status;
	}


	/**
	 * Set the project number of this reagent equal to the parameter value
	 * @param INT
	 * @see projectID
	*/
	function setProjectID($pID)
	{
		$this->projectID = $pID;
	}


	/**
	 * Set the verification of this reagent equal to the parameter value
	 * @param STRING
	 * @see rVerification
	*/
	function setReagentVerification($verif)
	{
		$this->rVerification = $verif;
	}


	/**
	 * Set the description of this reagent equal to the parameter value
	 * @param STRING
	 * @see funcDescr
	*/
	function setFunctionalDescription($fDescr)
	{
		$this->funcDescr = $fDescr;
	}


	/**
	 * Set the experimental comments of this reagent equal to the parameter value
	 * @param STRING
	 * @see expComments
	*/
	function setExperimentalComments($expComms)
	{
		$this->expComments = $expComms;
	}


	/**
	 * Set the verification comments of this reagent equal to the parameter value
	 * @param STRING
	 * @see verComments
	*/
	function setVerificationComments($verComms)
	{
		$this->verComments = $verComms;
	}


	/**
	 * Set the reagent source equal to the parameter value
	 * @param STRING
	 * @see rSource
	*/
	function setReagentSource($rSrc)
	{
		$this->rSource = $rSrc;
	}


	/**
	 * Set the sequence of this reagent equal to the parameter value
	 * @param STRING
	 * @see rSequence
	*/
	function setReagentSequence($rSeq)
	{
		$this->rSequence = $rSeq;
	}


	/**
	 * Set the species of this reagent equal to the parameter value
	 * @param STRING
	 * @see rSpecies
	*/
	function setReagentSpecies($species)
	{
		$this->rSpecies = $species;
	}


	/**
	 * Set 'restrictions on use' for this reagent equal to true
	 * @param boolean false
	 * @see hasRestrictions
	*/
	function setRestrictionsOnUse()
	{
		$this->hasRestrictions = true;
	}

	/**
	 * Set 'restrictions on use' for this reagent equal to false
	 * @param boolean false
	 * @see hasRestrictions
	*/
	function removeRestrictionsOnUse()
	{
		$this->hasRestrictions = false;
	}

	/**
	 * Set the features of this reagent equal to parameter array values
	 * @param Array
	 * @see rFeatures
	*/
	function setReagentFeatures($features)
	{
		$this->rFeatures = $features;
	}

	/************************************************
	* Access methods
	************************************************/

	/**
	 * Return the value of the 'type' variable assigned to this reagent
	 * @return STRING
	 * @see rType
	*/
	function getReagentType()
	{
		return $this->rType;
	}

	/**
	 * Return the name of this reagent
	 * @return STRING
	 * @see rName
	*/
	function getReagentName()
	{
		return $this->rName;
	}

	/**
	 * Return the status of this reagent
	 * @return STRING
	 * @see rStatus
	*/
	function getReagentStatus()
	{
		return $this->rStatus;
	}

	/**
	 * Return the project number of this reagent
	 * @return INT
	 * @see projectID
	*/
	function getProjectID()
	{
		return $this->projectID;
	}

	/**
	 * Return the description of this reagent
	 * @return STRING
	 * @see funcDescr
	*/
	function getFunctionalDescription()
	{
		return $this->funcDescr;
	}

	/**
	 * Return the verification value of this reagent
	 * @return STRING
	 * @see rVerification
	*/
	function getVerification()
	{
		return $this->rVerification;
	}

	/**
	 * Return the experimental comments of this reagent
	 * @return STRING
	*/
	function getExperimentalComments()
	{
		return $this->expComments;
	}

	/**
	 * Return the verification comments of this reagent
	 * @return STRING
	 * @see verComments
	*/
	function getVerificationComments()
	{
		return $this->verComments;
	}

	/**
	 * Return the value of 'reagent source' variable
	 * @return STRING
	 * @see rSource
	*/
	function getReagentSource()
	{
		return $this->rSource;
	}

	/**
	 * Return the sequence of this reagent
	 * @return STRING
	 * @see rSequence
	*/
	function getReagentSequence()
	{
		return $this->rSequence;
	}

	/**
	 * Return the restrictions on use value of this reagent (true or false)
	 * @return BOOLEAN
	 * @see hasRestrictions
	*/
	function hasRestrictionsOnUse()
	{
		return $this->hasRestrictions;
	}

	/**
	 * Return an array of features of this reagent
	 * @return Array
	 * @see rFeatures
	*/
	function getReagentFeatures()
	{
		return $this->rFeatures;
	}
}

/**
* This class represents reagents of type Vector in OpenFreezer
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
* @package Reagent
*
* @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
*/
class Vector_Class extends Reagent_Class
{
	/**
	 * @var STRING
	 * Vector type (e.g. Gateway Entry Clone, Parent Destination Vector, etc.)
	*/
	var $vectorType;

	/**
	 * @var INT
	 * Novel, Recombination, Non-Recombination (INT corresponds to ATypeID column in AssocType_tbl)
	*/
	var $cloningMethod;	// Novel, Recombination, Non-Recombination (INT corresponds to ATypeID)


	/**
	 * Default constructor. Generates a Vector object (set $rType=Vector and cloingMethod=1, defaulting all Vectors to Novel)
	*/
	function Vector_Class()
	{
		$this->rType = "Vector";
		$this->vectorType = "";
		$this->cloningMethod = 1;	// default all to Novel
	}

	/*******************************************************************************************
	* Assignment methods (set the value of the given instance variable to the parameter value)
	*******************************************************************************************/

	/**
	 * Set the cloning method of this vector equal to the parameter value
	 * @param STRING
	 * @see cloningMethod
	*/
	function setCloningMethod($cm)
	{
		$this->cloningMethod = $cm;
	}

	/**
	 * Set the vector type of this vector equal to the parameter value
	 * @param STRING
	 * @see vType
	*/
	function setVectorType($vType)
	{
		$this->vType = $vType;
	}

	/**************************************************************
	* Access methods (retrieve the value of the internal variable)
	**************************************************************/

	/**
	 * Returns the cloning (preparation) method of this vector - an INT corresponding to ATypeID column value in AssocType_tbl, indicating whether the vector is Novel (no parents), non-recombination (contains an Insert in its backbone plasmid), or prepared by LoxP recombination
	 * @return INT
	 * @see cloningMethod
	*/
	function getCloningMethod()
	{
		return $this->cloningMethod;
	}

	/**
	 * Returns the vector's type as a STRING
	 * @return STRING
	 * @see vType
	*/
	function getVectorType()
	{
		return $this->vType;
	}
}


/**
* This class represents reagents of type Insert in OpenFreezer
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
* @package Reagent
*
* @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
*/
class Insert_Class extends Reagent_Class
{
	/**
	 * @var STRING
	 * Translated amino acid sequence - property of Insert
	*/
	var $protSequence;		// translated peptide sequence - property of Insert

	/**
	 * @var STRING
	 * ORF, cDNA with UTRs, ORF subsequence, Non-coding
	*/
	var $typeOfInsert;

	/**
	 * @var STRING
	 * Indicates whether the Insert's DNA sequence contains an ATG and a stop codon (e.g. 'Open with ATG' means the sequence contains an ATG, indicating start of translation, but no stop codon; conversely, 'Closed, no ATG' means the sequence contains a stop codon and no ATG).  This value is required for correctly translating the Insert sequence into protein.
	*/
	var $openClosed;

	/**
	 * Default constructor. Generates an Insert object (set $rType=Insert and assigns default blank values to the rest of the variables)
	*/
	function Insert_Class()
	{
		$this->rType = "Insert";
		$this->protSequence = "";
		$this->typeOfInsert = "";
		$this->openClosed = "";
	}


	/******************************
	* Assignment methods
	******************************/

	/**
	 * Set the protein sequence of this insert equal to the parameter value
	 * @param STRING
	 * @see protSequence
	*/
	function setProteinSequence($pSeq)
	{
		$this->protSequence = $pSeq;
	}

	/**
	 * Set the type of insert equal to the input argument
	 * @param STRING
	 * @see typeOfInsert
	*/
	function setTypeOfInsert($iType)
	{
		$this->typeOfInsert = $iType;
	}

	/**
	 * Set the open/closed value equal to the input argument
	 * @param STRING
	 * @see openClosed
	*/
	function setOpenClosed($oc)
	{
		$this->openClosed = $oc;
	}

	/**************************************************************************
	* Access methods (return the value of the corresponding instance variable)
	**************************************************************************/

	/**
	 * Return the protein sequence of this Insert
	 * @return STRING
	 * @see protSequence
	*/
	function getProteinSequence()
	{
		return $this->protSequence;
	}

	/**
	 * Return the Type of Insert value
	 * @return STRING
	 * @see typeOfInsert
	*/
	function getTypeOfInsert()
	{
		return $this->typeOfInsert;
	}
	
	/**
	 * Return the Open/Closed value of this Insert
	 * @return STRING
	 * @see openClosed
	*/
	function getOpenClosed()
	{
		return $this->openClosed;
	}
}


/**
* This class represents reagents of type Oligo in OpenFreezer
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
* @package Reagent
*
* @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
*/
class Oligo_Class extends Reagent_Class
{
	/**
	* Default constructor. Generates an Oligo object (set $rType=Oligo)
	*/
	function Oligo_Class()
	{
// 		$this->rType = "Oligo";
	}
}


/**
* This class represents reagents of type Cell Line in OpenFreezer
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
* @package Reagent
*
* @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
*/
class CellLine_Class extends Reagent_Class
{
	/**
	* Default constructor. Generates a Cell Line object (set $rType=CellLine)
	*/
	function CellLine_Class()
	{
		$this->rType = "Cell Line";
	}
}

?>