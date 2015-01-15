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
* @package Sequence
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* Include/require statements
*/
//include "generalFunc_Class.php";

/**
 * Top-level class for Sequence objects
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Sequence
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 *
*/
class Sequence
{
	/**
	 * @var STRING
	 * String representation of the sequence
	*/
	var $sequence = "";

	/**
	 * @var INT
	 * Length of the sequence
	*/
	var $seqLength = 0;

	// Moved start, end and mw here (to parent class) on Aug. 24, 2010

	/**
	 * @var INT
	 * Start position of this sequence
	*/
	var $start;

	/**
	 * @var INT
	 * End position of this sequence
	*/
	var $end;

	/**
	 * @var FLOAT
	 * Molecular weight of this sequence
	*/
	var $mw;

	/**
	 * @var generalFunc_Class
	 * Auxiliary object to invoke functions
	*/
	var $gfunc_obj;

	/**
	 * Default constructor. Generates a Sequence object based on the given sequence, its length is calculated too.
	 * @param STRING
	*/
	function Sequence($sequence="")
	{
		$gfunc_obj = new generalFunc_Class();

		$this->sequence = $sequence;
		$this->seqLength = strlen($gfunc_obj->remove_whitespaces($sequence));
	}

	/**
	 * Set the sequence equal to the argument value
	 * @param STRING Sequence
	 * @see sequence
	*/
	function setSequence($sequence)
	{
		$this->sequence = $sequence;
	}

	// Moved start, end and mw assignment methods up here (to parent class) on Aug. 24, 2010

	/**
	 * Set the start position of the sequence equal to the input value.
	 * @param INT
	 * @see start
	*/
	function setStart($start)
	{
		$this->start = $start;
	}

	/**
	 * Set the end position of the sequence equal to the input value.
	 * @param INT
	 * @see end
	*/
	function setEnd($end)
	{
		$this->end = $end;
	}

	/**
	 * Set the molecular weight of the sequence equal to the input value.
	 * @param FLOAT
	 * @see mw
	*/
	// Jan. 25, 2010
	function setMW($mw)
	{
		$this->mw = $mw;
	}

	/**
	 * Return the sequence as a string
	 * @return STRING Sequence
	 * @see sequence
	*/
	function getSequence()
	{
		return $this->sequence;
	}

	/**
	 * Return the length of this sequence
	 * @return INT
	 * @see sequLength
	*/
	function getSequenceLength()
	{
		return $this->seqLength;
	}

	// moved start, end and mw retrieval methods up here (to parent class) on Aug. 24, 2010
	/**
	 * Return the start position of the sequence.
	 * @return INT
	 * @see start
	*/
	function getStart()
	{
		return $this->start;
	}

	/**
	 * Return the end position of the sequence.
	 * @return INT
	 * @see end
	*/
	function getEnd()
	{
		return $this->end;
	}

	/**
	 * Return the molecular weight of the sequence.
	 * @return FLOAT
	 * @see mw
	*/
	function getMW()
	{
		return $this->mw;
	}
}


/**
 * This class represents a protein sequence
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1 2009-06-23
 * @package Sequence
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 *
*/
class ProteinSequence extends Sequence
{
	// Instance variables

	/**
	 * @var INT
	 * Translation frame of the protein sequence
	*/
	var $frame;

	/**
	 * Default constructor. Generates a Protein Sequence object based on the input parameters.
	 * @param STRING
	 * @param INT
	 * @param INT
	 * @param INT
	 * @param FLOAT
	 * @see sequence
	 * @see frame
	 * @see start
	 * @see end
	 * @see mw
	*/
	function ProteinSequence($seq="", $frame=0, $start=0, $end=0, $mw=0)
	{
		$this->sequence = $seq;		// added Aug. 24, 2010
		$this->frame = $frame;
		$this->start = $start;
		$this->end = $end;
		$this->mw = $mw;	// jan. 22, 2010
	}

	/*****************************
	* Assignment methods
	*****************************/
	
	/**
	 * Set the frame of the sequence equal to the input value.
	 * @param INT
	 * @see frame
	*/
	function setFrame($frame)
	{
		$this->frame = $frame;
	}
	
	/*****************************
	* Access methods
	*****************************/

	/**
	 * Return the frame of the sequence.
	 * @return INT
	 * @see frame
	*/
	function getFrame()
	{
		return $this->frame;
	}
}

/**
 * This class represents a DNA sequence
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Sequence
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 *
*/
class DNASequence extends Sequence
{
	// Instance variables

	/**
	 * @var boolean
	 * Is there an ATG codon present in this sequence?
	*/
	var $hasATG;

	/**
	 * @var boolean
	 * Is there a stop codon present in this sequence?
	*/
	var $hasStopCodon;

	/**
	 * @var FLOAT
	 * Melting temperature of this sequence
	*/
	var $tm;	// Jan. 25, 2010

	/**
	 * @var FLOAT
	 * GC content (%) of this sequence
	*/
	var $gc;	// June 25, 2010

	/**
	 * Default constructor. Generates a DNA Sequence object based on the input parameters.
	 * @param STRING
	 * @param INT
	 * @param INT
	 * @param FLOAT
	 * @param FLOAT
	 * @param FLOAT
	 * @see sequence
	 * @see start
	 * @see end
	 * @see mw
	 * @see tm
	 * @see gc
	*/
	function DNASequence($seq="", $start=0, $end=0, $mw=0, $tm=0, $gc=0)
	{
		$this->sequence = $seq;		// Aug. 24, 2010
		$this->start = $start;
		$this->end = $end;
		$this->mw = $mw;	// jan. 25, 2010
		$this->tm = $tm;	// jan. 25, 2010
		$this->gc = $gc;	// june 25, 2010
	}

	/***************************
	* Assignment methods
	***************************/

	/**
	 * Set the melting temperature of the sequence equal to the input value.
	 * @param FLOAT
	 * @see tm
	*/
	// Jan. 25, 2010
	function setTM($tm)
	{
		$this->tm = $tm;
	}

	/**
	 * Set the GC content of the sequence equal to the input value.
	 * @param FLOAT
	 * @see gc
	*/
	function setGC($gc)
	{
		$this->gc = $gc;
	}

	/***************************
	* Access methods
	***************************/
	
	/**
	 * Return the melting temperature of the sequence.
	 * @return FLOAT
	 * @see tm
	*/
	// Jan. 25, 2010
	function getTM()
	{
		return $this->tm;
	}

	/**
	 * Return the GC content of the sequence.
	 * @return FLOAT
	 * @see gc
	*/
	function getGC()
	{
		return $this->gc;
	}
}


/**
 * This class represents a RNA sequence
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1 2009-06-23
 * @package Sequence
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
class RNASequence extends Sequence
{
	/*****************************
	* Instance variables
	*****************************/

	/**
	 * @var FLOAT
	 * Melting temperature of this sequence
	*/
	var $tm;	// Jan. 25, 2010

	/**
	 * Default constructor. Generates a RNA Sequence object based on the input parameters.
	 * @param STRING
	 * @param INT
	 * @param INT
	 * @param FLOAT
	 * @param FLOAT
	 * @see sequence
	 * @see start
	 * @see end
	 * @see mw
	 * @see tm
	*/
	function DNASequence($seq="", $start=0, $end=0, $mw=0, $tm=0)
	{
		$this->start = $start;
		$this->end = $end;
		$this->mw = $mw;	// jan. 25, 2010
		$this->tm = $tm;	// jan. 25, 2010
	}

	// Assignment methods - inherited from parent class

	/**
	 * Return the melting temperature of the sequence.
	 * @return FLOAT
	 * @see tm
	*/
	// Jan. 25, 2010
	function getTM()
	{
		return $this->tm;
	}
}
?>
