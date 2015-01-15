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
 * Functions for handling DNA, RNA or Protein sequences
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1 2009-06-23
 * @package Sequence
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
class Sequence_Function_Class
{
	/**
	 * Retrieve the *Protein* sequence of the given reagent from the database (reagent's sequence is of type Protein, not DNA or RNA)
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT $reagentID
	 * @return ProteinSequence
	*/
	function findProteinSequence($reagentID)
	{
		global $conn;

		$protein = null;

		$result_rs = mysql_query("SELECT start, end, frame, sequence, mw FROM ReagentPropList_tbl p, ReagentPropertyCategories_tbl pc, Sequences_tbl s WHERE p.reagentID='" . $reagentID . "' AND p.propertyID=pc.propCatID AND pc.propID='" . $_SESSION["ReagentProp_Name_ID"]["protein sequence"] . "' AND s.seqID=p.propertyValue AND p.status='ACTIVE' AND s.status='ACTIVE' AND pc.status='ACTIVE'");

		if ($result_ar = mysql_fetch_array($result_rs, MYSQL_ASSOC))
		{
			$proteinSeq = $result_ar["sequence"];

			$start = $result_ar["start"];
			$end = $result_ar["end"];
			$frame = $result_ar["frame"];
			$mw = $result_ar["mw"];

			$protein = new ProteinSequence();
			$protein->setSequence($proteinSeq);
			$protein->setStart($start);
			$protein->setEnd($end);
			$protein->setFrame($frame);
			$protein->setMW($mw);
		}

		return $protein;
	}


	/**
	 * Retrieve the **translated protein sequence** of the given reagent from the database (the reagent's sequence is of type DNA, and has been also translated into protein)
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT $reagentID
	 * @return ProteinSequence
	*/
	function findProteinTranslation($reagentID)
	{
		global $conn;

		$protein = null;

		$result_rs = mysql_query("SELECT propertyValue FROM ReagentPropList_tbl p, ReagentPropertyCategories_tbl pc WHERE p.reagentID='" . $reagentID . "' AND p.propertyID=pc.propCatID AND pc.propID='" . $_SESSION["ReagentProp_Name_ID"]["protein translation"] . "' AND p.status='ACTIVE' AND pc.status='ACTIVE'");
		
		if ($result_ar = mysql_fetch_array($result_rs, MYSQL_ASSOC))
		{
			$protSeqID = $result_ar["propertyValue"];
			$result_rs_2 = mysql_query("SELECT start, end, frame, sequence, mw FROM Sequences_tbl WHERE seqID='" . $protSeqID . "' AND status='ACTIVE'", $conn) or die("Error in findProteinSequence() function: " . mysql_error());

			if ($result_ar_2 = mysql_fetch_array($result_rs_2, MYSQL_ASSOC))
			{
				$start = intval($result_ar_2["start"]);
				$end = intval($result_ar_2["end"]);
				$frame = $result_ar_2["frame"];
				$protSeq = $result_ar_2["sequence"];
				$mw = $result_ar_2["mw"];		// Jan. 22, 2010
				$protein = new ProteinSequence();

				$protein->setSequence($protSeq);
				$protein->setStart($start);
				$protein->setEnd($end);
				$protein->setFrame($frame);
				$protein->setMW($mw);		// Jan. 22, 2010
			}
		}

		return $protein;
	}

	/**
	 * Retrieve the DNA sequence of the given reagent from the database
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT $reagentID
	 * @return DNASequence
	*/
	function findDNASequence($reagentID)
	{
		global $conn;

		$dna = null;

		$result_rs = mysql_query("SELECT sequence FROM ReagentPropList_tbl p, ReagentPropertyCategories_tbl pc, Sequences_tbl s WHERE p.reagentID='" . $reagentID . "' AND p.propertyID=pc.propCatID AND pc.propID='" . $_SESSION["ReagentProp_Name_ID"]["sequence"] . "' AND s.seqID=p.propertyValue AND p.status='ACTIVE' AND s.status='ACTIVE' AND pc.status='ACTIVE'");

		if ($result_ar = mysql_fetch_array($result_rs, MYSQL_ASSOC))
		{
			$dnaSeq = $result_ar["sequence"];
			$dna = new DNASequence();
			$dna->setSequence($dnaSeq);
		}

		return $dna;
	}

	/**
	 * Retrieve the ID of the given sequence from the database (return Sequences_tbl.seqID column value; $sequence can be of any type)
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param STRING $sequence
	 * @return INT
	*/
	function findSequenceID($sequence)
	{
		global $conn;

		$seq_rs = mysql_query("SELECT seqID FROM Sequences_tbl WHERE sequence='" . $sequence . "' AND status='ACTIVE'");

		if ($seq_ar = mysql_fetch_array($seq_rs, MYSQL_ASSOC))
		{
			return $seq_ar["seqID"];
		}

		return -1;
	}

	/**
	 * Retrieve the RNA sequence of the given reagent from the database (reagent's sequence is of type RNA, not DNA or Protein)
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT $reagentID
	 * @return RNASequence
	*/
	function findRNASequence($reagentID)
	{
		global $conn;

		$rna = null;

		$result_rs = mysql_query("SELECT sequence FROM ReagentPropList_tbl p, ReagentPropertyCategories_tbl pc, Sequences_tbl s WHERE p.reagentID='" . $reagentID . "' AND p.propertyID=pc.propCatID AND pc.propID='" . $_SESSION["ReagentProp_Name_ID"]["rna sequence"] . "' AND s.seqID=p.propertyValue AND p.status='ACTIVE' AND s.status='ACTIVE' AND pc.status='ACTIVE'");

		if ($result_ar = mysql_fetch_array($result_rs, MYSQL_ASSOC))
		{
			$rnaSeq = $result_ar["sequence"];
			$rna = new RNASequence();
			$rna->setSequence($rnaSeq);
		}

		return $rna;
	}
}
?>
