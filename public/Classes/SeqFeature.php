<?php
/**
* This module represents properties (attributes) of reagents in OpenFreezer.  Properties can either be 'basic' properties that apply to the Reagent as a whole (e.g. name, type), or they can be Sequence Features, which characterize specific segments of the reagent's sequence.
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
* @package Sequence
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
 * This class represents basic properties of reagents in OpenFreezer.
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Sequence
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
class ReagentProperty
{
	/**
	 * Instance variables - Basic attributes of a Property object
	*/

	/**
	 * @var STRING
	 * Property name (corresponds to 'propertyName' column in ReagentPropType_tbl, always in lowercase, e.g. 'cloning method')
	 * @see setPropertyName(), getPropertyName()
	*/
	var $pName;	// e.g. name, status, etc.

	/**
	 * @var STRING
	 * Database alias of the property (corresponds to 'propertyAlias' column in ReagentPropType_tbl, a one-word lowercase alias for propertyName, with whitespaces in multi-word names replaced by underscores, e.g. 'cloning_method')
	 * @see setPropertyAlias(), getPropertyAlias()
	*/
	var $pAlias;

	/**
	 * @var STRING
	 * Full-length description of the property (corresponds to 'propertyDesc' column in ReagentPropType_tbl, preserves the original case in which the property's name was entered, e.g. 'Cloning Method')
	 * @see setPropertyDescription(), getPropertyDescription()
	*/
	var $pDescr;

	/**
	 * @var STRING
	 * Name of this property's category in the context of a given reagent (e.g. 'Name' is always under 'General Properties' for all reagent types, whereas 'Origin' might be a classifier for Insert and a DNA Sequence Feature for Vector)
	 * @see setPropertyCategory(), getPropertyCategory()
	*/
	var $pCategory;

	/**
	 * @var mixed
	 * Actual value of this property; type varies ('status' is a string and project ID is an INT).  Corresponds to 'propertyValue' column in ReagentPropType_tbl.
	 * @see setPropertyValue(), getPropertyValue()
	*/
	var $pValue;	// actual feature value, e.g. SH2, 'In Progress', etc.

	/**
	 * @var INT
	 * Internal database identifier of this property, corresponds to 'propertyID' column in ReagentPropType_tbl.
	 * @see setPropertyID(), getPropertyID()
	*/
	var $propID;	// Nov. 3/09

	/**
	 * @var boolean
	 * A boolean value to indicate whether this property is a sequence feature.  Set to 'false' in this class and set to 'true' in descendant SequenceFeature class.  Sequence features are used to describe specific DNA/RNA/Protein sequence segments and always have 'startPos', 'endPos' and 'direction' values.
	*/
	var $isFeature = false;


	/***********************************************
	* Methods
	************************************************/

	/**
	 * Default constructor. Generates a ReagentProperty instance with variable values set to function parameters.
	 *
	 * @param STRING Name of the property in lowercase (e.g. 'selectable marker' - corresponds to propertyName column in ReagentPropertyType_tbl)
	 * @param STRING Internal database alias of the property (one word lowercase, whitespace replaced with underscore, e.g. 'selectable_marker' - corresponds to propertyAlias column in ReagentPropertyType_tbl)
	 * @param STRING Full property name (e.g. 'Selectable Marker' - corresponds to propertyDesc column in ReagentPropertyType_tbl)
	 * @param STRING Category of this property in the current context (e.g. 'Classifiers')
	 * @param MIXED Actual property value (e.g. 'Ampicillin', corresponds to propertyValue column in ReagentPropList_tbl) - default null
	 *
	 * @see pName
	 * @see pAlias
	 * @see pDescr
	 * @see pCategory
	 * @see pValue
	*/
	function ReagentProperty($pName, $pAlias, $pDescr, $pCategory, $pValue=null)
	{
		$this->pName = $pName;
		$this->pAlias = $pAlias;
		$this->pDescr = $pDescr;
		$this->pCategory = $pCategory;
		$this->pValue = $pValue;
	}


	/**
	 * Is the property a sequence feature?  TRUE for objects of type SeqFeature; FALSE for all other ReagentProperty type objects
	 * @return boolean
	 * @see SeqFeature
	*/
	function isSequenceFeature()
	{
		return $this->isFeature;
	}


	/***********************************************
	* Assignment methods
	***********************************************/

	/**
	 * Set the property ID of this property equal to $pID
	 * @param INT Corresponds to propertyID column in ReagentPropType_tbl
	 * @see propID
	*/
	function setPropertyID($pID)
	{
		$this->propID = $pID;
	}

	/**
	 * Set the name of this property equal to $pName
	 * @param STRING Corresponds to propertyName column in ReagentPropType_tbl
	*/
	function setPropertyName($pName)
	{
		$this->pName = $pName;
	}

	/**
	 * Set the value of this property equal to $pValue
	 * @param MIXED Corresponds to propertyValue column in ReagentPropType_tbl
	 * @see pValue
	*/
	function setPropertyValue($pValue)
	{
		$this->pValue = $pValue;
	}

	/**
	 * Set the alias of this property equal to $pAlias
	 * @param STRING Corresponds to propertyAlias column in ReagentPropType_tbl (most often a one-word lowercase string, with whitespaces replaced by underscores "_")
	 * @see pAlias
	*/
	function setPropertyAlias($pAlias)
	{
		$this->pAlias = $pAlias;
	}

	/**
	 * Set the description of this property equal to $pDescr
	 * @param STRING Corresponds to propertyDesc column in ReagentPropType_tbl
	 * @see pDescr
	*/
	function setPropertyDescription($pDescr)
	{
		$this->pDescr = $pDescr;
	}

	/**
	 * Set the category of this property equal to $pCategory
	 * @param STRING Corresponds to propertyCategory column in ReagentPropType_tbl
	 * @see pCategory
	*/
	function setPropertyCategory($pCategory)
	{
		$this->pCategory = $pCategory;
	}


	/********************************
	* Access Methods
	********************************/

	/**
	 * Return the name of this property
	 * @return STRING
	 * @see pName
	 * @throws Exception if this is a sequence feature as opposed to simple property (SequenceFeature type objects do not have a pName attribute; they have a featureType attribute instead)
	*/
	function getPropertyName()
	{
		if (!$this->pName || strlen($this->pName) == 0)
			throw new Exception("Wrong reagent property type");

		return $this->pName;
	}

	/**
	* Return the alias of this property
	* @return STRING
	* @see pAlias
	*/
	function getPropertyAlias()
	{
		return $this->pAlias;
	}
	
	/**
	* Return the description of this property
	* @return STRING
	* @see pDescr
	*/
	function getPropertyDescription()
	{
		return $this->pDescr;
	}

	/**
	* Return the category of this property
	* @return STRING
	* @see pCategory
	*/
	function getPropertyCategory()
	{
		return $this->pCategory;
	}

	/**
	* Return the value of this property
	* @return MIXED
	* @see pValue
	*/
	function getPropertyValue()
	{
		return $this->pValue;
	}

	/**
	* Return the ID of this property
	* @return MIXED
	* @see propID
	*/
	function getPropertyID()
	{
		return $this->propID;
	}
}

/**
* This class represents reagent sequence features (e.g. tag type, promoter, selectable marker, origin of replication, etc.)
*
* The main characteristic of a feature is the 1-m type/vaue relationship (i.e. a feature can have multiple values; a reagent can have a BAP tag on its sequence at pos. 23-65 on the N-terminus and a reverse oriented His tag at the C-terminus, at positions 345-386)
*
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1 2008-05-20
* @package Sequence
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3 
*/
class SeqFeature extends ReagentProperty
{
	/*************************************
	* Instance variables
	*************************************/

	/**
	 * @var STRING
	 * Name of the feature, e.g. promoter, tag type, origin, etc. Corresponds to 'propertyName' column in ReagentPropType_tbl.
	*/
	var $fType = "";		// e.g. promoter, tag type, origin, etc.

	/**
	 * @var STRING
	 * Actual feature value, e.g. BGH, CMV, Blasticidin, etc. Corresponds to 'propertyValue' column in ReagentPropList_tbl.
	*/
	var $fValue = "";		// actual feature value, e.g. BGH, CMV, Blasticidin, etc.

	/**
	 * @var INT
	 * Start position of the feature on the DNA/RNA/Protein sequence of the reagent. Corresponds to 'startPos' column in ReagentPropList_tbl.
	*/
	var $fStart = 0;

	/**
	 * @var INT
	 * End position of the feature on the DNA/RNA/Protein sequence of the reagent. Corresponds to 'endPos' column in ReagentPropList_tbl.
	*/
	var $fEnd = 0;

	/**
	 * @var STRING
	 * Orientation of the feature on the DNA/RNA/Protein sequence - either 'forward' or 'reverse'. Corresponds to 'direction' column in ReagentPropList_tbl.
	*/
	var $fDir = "forward";		// orientation: forward or reverse

	/**
	 * @var STRING
	 * Actual feature descriptor value. Only applies to selected features - currently only applies to features of type 'tag' and 'promoter' (the descriptor for tag type is 'tag position', and the descriptor for promoter is 'expression system') E.g., if $fType = 'tag', $fDescr may be 'Internal', 'N-terminus', 'C-terminus' or 'None'.  If $fType is 'promoter', $fDescr may assume any of the Expression System values defined in OpenFreezer, such as 'Mammalian', 'Bacteria', and more.  Corresponds to 'descriptor' column in ReagentPropList_tbl.
	 * @see getFeatureDescriptorType(), hasFeatureDescriptor()
	*/
	var $fDescr = null;		// descriptor; only applies to selected features - currently only applies to tag type and promoter (the descriptor for tag type is 'tag position', and the descriptor for promoter is 'expression system')

	/**
	 * @var STRING
	 * Actual full-length description of the feature, e.g. "cDNA" or "5' Cloning Site"
	*/
	var $description = "";

	/**
	 * @var boolean
	 * Always set to TRUE for all objects of this class
	*/
	var $isFeature = true;

	/**
	 * @var INT
	 * Database ID of the feature (corresponds to propertyID column in ReagentPropType_tbl)
	*/
	var $featureID = -1;		// Nov 3/09


	/*************************************
	* Methods
	*************************************/

	/**
	 * Default constructor. Generates a SeqFeature object with variable values set to function parameters.
	 *
	 * @param STRING Lowercase name of the feature, e.g. 'cleavage site' - corresponds to 'propertyName' column in ReagentPropType_tbl
	 * @param MIXED Actual value of the feature, e.g. 'enterokinase' - corresponds to 'propertyValue' column in ReagentPropList_tbl
	 * @param INT Start position of the feature on the sequence (on the 5' strand for DNA sequences) - corresponds to 'endPos' column in ReagentPropList_tbl
	 * @param INT Start position of the feature on the sequence (on the 5' strand for DNA sequences) - corresponds to 'startPos' column in ReagentPropList_tbl
	 * @param STRING Direction (orientation) of the feature on the sequence (on the 5' strand for DNA sequences) - either 'forward' or 'reverse' - corresponds to 'direction' column in ReagentPropList_tbl
	 * @param STRING Descriptor value (only applies to selected features; 'expression system' for Promoter and 'tag position' for Tag) - e.g. 'Mammalian' (expression system) or 'Internal' (tag position) - corresponds to 'descriptor' column in ReagentPropList_tbl
	 * @param STRING Full name of the feature in the correct lettercase (e.g. 'Cleavage Site' - corresponds to propertyDesc column in ReagentPropType_tbl)
	 *
	 * @see fType
	 * @see fValue
	 * @see fStart
	 * @see fEnd
	 * @see fDir
	 * @see fDescr
	 * @see description
	*/
	function SeqFeature($fType="", $fValue="", $fStart=0, $fEnd=0, $fDir="", $fDescr="", $description="")
	{
		$this->fType = $fType;
		$this->fValue = $fValue;
		$this->fStart = $fStart;
		$this->fEnd = $fEnd;
		$this->fDir = $fDir;
		$this->fDescr = $fDescr;
		$this->description = $description;
	}

	/**
	 * Is this property a sequence feature?
	 * @return boolean Always TRUE for all objects of this class
	*/
	function isSequenceFeature()
	{
		return $this->isFeature;
	}


	/********************************
	 * Assignment Methods
	********************************/

	/**
	 * Set the type of this feature equal to $ft (e.g. 'Tag', 'Promoter', 'Selectable Marker', etc.)
	 * @param STRING
	 * @see fType
	*/
	function setFeatureType($ft)
	{
		$this->fType = $ft;
	}

	/**
	 * Set the ID of this feature equal to $fID
	 * @param INT
	 * @see featureID
	*/
	function setFeatureID($fID)
	{
		$this->featureID = $fID;
	}

	/**
	 * Set the value of this feature equal to $fv
	 * @param MIXED
	 * @see fValue
	*/
	function setFeatureValue($fv)
	{
		$this->fValue = $fv;
	}

	/**
	 * Set the start position of this feature equal to $fs
	 * @param INT
	 * @see fStart
	*/
	function setFeatureStart($fs)
	{
		$this->fStart = $fs;
	}

	/**
	 * Set the end position of this feature equal to $fe
	 * @param INT
	 * @see fEnd
	*/
	function setFeatureEnd($fe)
	{
		$this->fEnd = $fe;
	}

	/**
	 * Set the orientation of this feature equal to $fd
	 * @param STRING 'forward' or 'reverse'
	 * @see fDir
	*/
	function setFeatureDirection($fd)
	{
		$this->fDir = $fd;
	}

	/**
	 * Set the value of this feature's descriptor equal to $f_desc.  Currently applies only to Tag and Promoter, whose descriptors are Tag Position and Expression System respectively.  This function may set the value of Tag Position to 'Internal' or set Expression System to be 'Mammalian'; for any other features descriptor would be blank.
	 * @param STRING
	 * @see fDescr
	*/
	function setFeatureDescriptor($f_desc)
	{
		$this->fDescr = $f_desc;
	}

	/**
	 * Set the full description of this feature (the text that appears on the web interface) equal to $description (e.g. 'Open/Closed', 'cDNA', etc.)
	 * @param STRING
	 * @see description
	*/
	function setFeatureDecription($description)
	{
		$this->description = $description;
	}


	/***************************
	 * Access Methods
	****************************/

	/**
	 * Return the type of this feature (e.g. 'promoter', 'selectable marker', etc.)
	 * @return STRING
	 * @see fType
	*/
	function getFeatureType()
	{
		return $this->fType;
	}

	/**
	 * Return the value of this feature (e.g. 'BAP', 'Cerulean', etc.)
	 * @return MIXED
	 * @see fValue
	*/
	function getFeatureValue()
	{
		return $this->fValue;
	}

	/**
	 * Return the start position of this feature
	 * @return INT
	 * @see fStart
	*/
	function getFeatureStart()
	{
		return $this->fStart;
	}

	/**
	 * Return the end position of this feature
	 * @return INT
	 * @see fEnd
	*/
	function getFeatureEnd()
	{
		return $this->fEnd;
	}

	/**
	 * Return the orientation of this feature
	 * @return STRING 'forward' or 'reverse'
	 * @see fDir
	*/
	function getFeatureDirection()
	{
		return $this->fDir;
	}


	/**
	 * Return the value of this feature's descriptor, if available (would only return actual values for tag and promoter)
	 * @return STRING
	 * @see fDescr
	*/
	// Returns actual value
	function getFeatureDescriptor()
	{
		return $this->fDescr;
	}


	/**
	 * Return the name of this feature's descriptor - 'tag position' if the feature is 'tag' and 'expression system' if the feature is 'promoter'; returns the empty string otherwise
	 * @return STRING
	*/
	function getFeatureDescriptorType()
	{
		switch ($this->fType)
		{
			case 'tag':
				return "tag position";
			break;
			
			case 'promoter':
				return "expression system";
			break;
			
			default:
				return "";
			break;
		}
	}


	/**
	 * Boolean method to determine if this feature has a descriptor.
	 * @return boolean TRUE if this feature's type is 'tag' or 'promoter'; FALSE otherwise
	*/
	// June 11/08: Boolean method to determine if this feature has a descriptor
	function hasFeatureDescriptor()
	{
		if ( ($this->fType == 'tag') || ($this->fType == 'promoter'))
			return true;
		
		return false;
	}


	/**
	 * Return the full description of this feature as opposed to name or alias (e.g. "cDNA" or "Open/Closed")
	 * @return STRING
	 * @see description
	*/
	function getFeatureDescription()
	{
		return $this->description;
	}


	/**
	 * Return the database ID of this feature, corresponds to propertyID column value in ReagentPropType_tbl
	 * @return INT
	 * @see featureID
	*/
	function getFeatureID()
	{
		return $this->featureID;
	}
}
?>