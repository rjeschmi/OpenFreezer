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
* @author John Paul Lee <ninja_gara@hotmail.com>
* @version 2005
*
* @author     Marina Olhovsky <olhosvky@lunenfeld.ca>
* @version    3.1
* @package Reagent
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
/**
 * Contains functions to output specific reagent properties on forms for reagent creation, modification and viewing details
 *
 * @author John Paul Lee <ninja_gara@hotmail.com>
 * @version 2005
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Reagent
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 *
*/
class ColFunctOutputer_Class
{
	// Auxiliary internal variables used to print sequences in a specific format; their values may be changed in the code.

	/**
	* @var STRING
	* Print a sequence in chunks of 10 on reagent detail views, separated by &nbsp;
	*/
	var $setSeparator;

	/**
	* @var INT
	* Output sequence in chunks of 10
	*/
	var $seqIncrementor;

	/**
	* @var INT
	* Print 10 columns in each row for sequence output
	*/
	var $maxLineOutput;

	/**
	* @var STRING
	* Assumes the value of an error message returned during execution of mysql queries
	*/
	var $classerror = "";

	/**
	 * Zero-argument constructor: Initialize instance variables
	 *
	 * @author John Paul Lee <ninja_gara@hotmail.com>
	 * @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	*/
	function ColFunctOutputer_Class()
	{
		$this->setSeparator = "&nbsp;";
		$this->seqIncrementor = 10;
		$this->maxLineOutput = 10;
		$this->classerror = "ColFunctOutputer_Class";
	}
	
	
	/**
	* Central function that invokes specific functions for printing the value of a particular property, given by the $columnType argument
	* @param INT $rid Internal database ID of the reagent whose properties are being printed
	* @param STRING $columnType Name of the property to print
	* @param STRING $outputType Differentiates between views (e.g. reagent details or modification)
	* @param STRING $category Recent addition, indicates the property's category for proper database retrieval and output
	* @param STRING $default_val Obsolete
	*/
	function output_final($rid, $columnType, $outputType, $category="", $default_val = "")
	{
		$outputType = strtolower($outputType);

		$rfunc_obj = new Reagent_Function_Class();

		switch (strtolower($columnType))
		{
			case "description":
			case "comment":
			case "comments":
			case "verification comments":
				return $this->get_General_Reagent_Comments($columnType, $rid);

			case "sequence":

				if ($outputType == "preview")	// in Reagent_Creator_Class, but most likely not needed, can replace later
				{
					return $this->output_sequence2($rid, 2);
				}
				elseif ($outputType == "raw")		// modify view
				{
					return $this->output_sequence2($rid, 5);
				}
				// Feb. 14/08: In Modify mode, show numbered sequence
				elseif ($outputType == "modify")
				{
// Temporarily removed July 15/08
// July 15/08, to be fixed		return $this->output_sequence2($rid, 3);
					return $this->output_sequence2($rid, 2);
				}

				return $this->output_sequence($rid);
				
			case "protein sequence":	
			case "protein translation":
				return $this->outputProteinSequence($rid);
			
			// Oct. 23/09
			case "rna sequence":
				return $this->outputRNASequence($rid, $outputType);

			case "length":
				if ($outputType == 'dna')
				{
					return $this->output_length($rid);
				}
				else	// added sept 27/06
				{
					return $this->outputProteinLength($rid);
				}
			case "open/closed":
				return $this->output_hint_rev1( $rid );
			
			case "packet":

				// Updated May 25/07, Marina
				if ($outputType == "modify")
				{
					return $this->output_packet($rid, 2);
				}

				return $this->output_packet($rid, 1);
				
			case "resistance marker":	// put back March 3/09 - still used for Cell Lines
				return $this->output_Resistance_Marker($rid, $category, $outputType);

			case "alternate id":		// May 23/06, Marina
				return $this->output_Alternate_ID($rid, $outputType);
			// March 19/09: Show multiple accessions
			case "accession number":
				return $this->outputAccession($rid);	// aug. 30, 2010: removed $outputType argument
			case "promoter":
			case "tag":		// March 13/08
			case "selectable marker":	// March 17/08
			case "polya tail":			// March 17/08
			case "origin of replication":			// March 18/08
			case "miscellaneous":		// March 18/08
			case "cleavage site":		// Sept. 18/08
			case "restriction site":	// Sept. 18/08
			case "transcription terminator":	// Sept. 18/08
			case 'intron':			// Dec. 10/08
				return $this->output_Sequence_Features($rid, $columnType, $category);

			case "frame":		// sept 27/06
				return $this->getFrame($rid);

			default:
				if ($rfunc_obj->isSequenceFeature($columnType))
					return $this->output_Sequence_Features($rid, $columnType, $category);

				return "No matching column type was found!";
		}
	}
	

	/**
	 * Retrieve from the database and print project information (number+/owner+/name) for the reagent identified by $rid
	 *
	 * @author John Paul Lee <ninja_gara@hotmail.com>
	 * @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2006-01-05
	 *
	 * @param INT $rid Internal database ID of the reagent in question
	 * @param INT $type Output type (differentiates between create/modify/search views and determines the return value of the function - which projects ought to be printed, whether the project's number and/or owner and/or name should be returned by the function or printed as options in dropdown selection list)
	*/
	// Modified by Marina on March 30/07; since Packet has been made into a simple property, change retrieval query
	// Updated May 25/07: Changing table structure of Packets_tbl - modified query accordingly
	function output_packet($rid, $type)
	{
		global $conn;
		$rfunc_obj = new Reagent_Function_Class();

		$packetPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["packet id"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

		// May 25/07
		if ($type == 1)
		{
			$query = "SELECT r.propertyValue as packetID, u.lastname as lastname FROM ReagentPropList_tbl r, Packets_tbl p, Users_tbl u WHERE r.reagentID='" . $rid . "' AND r.propertyID='" . $packetPropID . "' AND r.propertyValue=p.packetID AND p.ownerID=u.userID AND p.status='ACTIVE' AND r.status='ACTIVE'";
			
			$find_name_rs = mysql_query($query, $conn) or die("Error fetching packet ID:" . mysql_error());

			if ($find_name_ar = mysql_fetch_array($find_name_rs, MYSQL_ASSOC))
			{
				$tempName = $find_name_ar["lastname"] . ":" . $find_name_ar["packetID"];
				return $tempName;
			}

			return "None";
		}
		else
		{
			// Differentiate between Create and Modify Reagent views
			$userProjects = array();

			// Modify: known reagent ID
			if ( ($rid <= 0) || (isset($_GET["mode"]) && ($_GET["mode"] == 'Create')) )
			{
				// Create: Reagent ID does not exist.  In this case ONLY display a list of projects that the user has WRITE access to
				// July 17/07: Restrict user access by project
				// get list of projects that the current user is allowed to view
				$currUserID = $_SESSION["userinfo"]->getUserID();
				
				// admin has unlimited access
				$currUserCategory = $_SESSION["userinfo"]->getCategory();

				if ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])
				{
					$userProjects = findAllProjects();
				}
				else
				{
					$userProjects = getUserProjectsByRole($currUserID, 'Writer');
				}
				
				if (sizeof($userProjects) > 0)
				{
					$userProjectList = "(" . implode(",", $userProjects) . ")";

					// Output a dropdown list, containing Packet ID : Owner : Packet Name
					$query = "SELECT p.packetID as packetID, p.packetName as packetName, u.lastname as owner FROM Packets_tbl p, Users_tbl u WHERE p.ownerID=u.userID AND p.packetID IN " . $userProjectList . " AND p.status='ACTIVE'";
					$find_name_rs = mysql_query($query, $conn) or die("Error fetching packet ID:" . mysql_error());
	
					echo "<OPTION name=\"default\" value=\"0\" selected>-- Select Project --</OPTION>";
	
					while ($find_name_ar = mysql_fetch_array($find_name_rs, MYSQL_ASSOC))
					{
						$temp_packet = $find_name_ar["packetID"] . ": " . $find_name_ar["owner"] . ": " . $find_name_ar["packetName"];
						echo "<OPTION name=\"pkt_" . $find_name_ar["packetID"] . "\" value=\"" . $find_name_ar["packetID"] . "\">" . $temp_packet . "</option>";
					}
				}
			}
			else
			{
				// current reagent project ID
				$rIDpID = getReagentProjectID($rid);

				// Output a dropdown list, containing Packet ID : Owner : Packet Name
				
				// July 13/07, Marina: Output only projects the user has WRITE access to, plus public projects if user is a Writer (which he should be, otherwise he wouldn't arrive at modification view)
				$currUserID = $_SESSION["userinfo"]->getUserID();
				
				// admin has unlimited access
				$currUserCategory = $_SESSION["userinfo"]->getCategory();

				if ($currUserCategory == $_SESSION["userCategoryNames"]["Admin"])
				{
					$userProjects = findAllProjects();
				}
				else
				{
					$userProjects = getUserProjectsByRole($currUserID, 'Writer');

/* May 11/09: NO!!!!!!!!!!!!  Do NOT let user save to a project s/he doesn't have Write access to, even if it's public!!!!!!!!!!!!!!!!
					// add public projects
					$publicProjects = getPublicProjects();

					foreach ($publicProjects as $key => $project)
					{
						$pID = $project->getPacketID();
						$userProjects[] = $pID;
					}
					
					$userProjects = array_unique($userProjects);
*/
				}
				
				// Important fix May 27/09: check user projects list is not empty - if it is, query produces error!!!!!!
				if (count($userProjects) > 0)
				{
					$userProjectList = "(" . implode(",", $userProjects) . ")";
	
					$query = "SELECT p.packetID as packetID, p.packetName as packetName, u.lastname as owner FROM Packets_tbl p, Users_tbl u WHERE p.ownerID=u.userID AND p.packetID IN " . $userProjectList . " AND p.status='ACTIVE'";
					$find_name_rs = mysql_query($query, $conn) or die("Error fetching packet ID:" . mysql_error());

					// restored May 14/08
					echo "<OPTION name=\"default\" value=\"0\" selected>-- Select Project -- </OPTION>";
	
					while ($find_name_ar = mysql_fetch_array($find_name_rs, MYSQL_ASSOC))
					{
						$temp_packet = $find_name_ar["packetID"] . ": " . $find_name_ar["owner"] . ": " . $find_name_ar["packetName"];
	
						if ($find_name_ar["packetID"] == $rIDpID)
							echo "<OPTION selected name=\"pkt_" . $find_name_ar["packetID"] . "\" value=\"" . $find_name_ar["packetID"] . "\">" . $temp_packet . "</option>";
						else
							echo "<OPTION name=\"pkt_" . $find_name_ar["packetID"] . "\" value=\"" . $find_name_ar["packetID"] . "\">" . $temp_packet . "</option>";
					}
				}
			}
		}
	}
	

	/**
	* Retrieve from the database and output comments or description value for the reagent identified by $rid (since there is a separate table for storing comments, need special SELECT query and output procedure)
	*
	* @author John Paul Lee <ninja_gara@hotmail.com>
	* @version 2005
	*
	* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	* @version 3.1 2006-01-05
	*
	* @param STRING $columnType Name of the required property - comments or description (different internal propertyID)
	* @param INT $rid Internal database ID of the reagent in question
	*
	* @return STRING
	*/
	function get_General_Reagent_Comments($columnType, $rid)
	{
		global $conn;
		$rfunc_obj = new Reagent_Function_Class();

		$currentTypeID = 0;
		
		switch( $columnType )
		{
			case "description":
				$currentTypeID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["description"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);
				break;
			case "comments":
				$currentTypeID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["comments"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);
				break;
			case "verification comments":
				$currentTypeID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["verification comments"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);
				break;
		}
		
		$foundCommentID_rs = mysql_query("SELECT `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $rid  . "' AND `status`='ACTIVE' AND `propertyID`='" . $currentTypeID . "'" , $conn ) or die( "[died on comment search]: " . mysql_error() );

		if( $foundCommentID_ar = mysql_fetch_array( $foundCommentID_rs, MYSQL_ASSOC ) )
		{
			$foundRealDesc_rs = mysql_query("SELECT `comment` FROM `GeneralComments_tbl` WHERE `commentID`='" . $foundCommentID_ar["propertyValue"] . "' AND `status`='ACTIVE'", $conn);
			
			if(  $foundRealDesc_ar = mysql_fetch_array( $foundRealDesc_rs, MYSQL_ASSOC ) )
			{
				return stripslashes($foundRealDesc_ar["comment"]);
			}
			else	# Added Oct 17/06 by Marina
			{
				return "";
			}
		}
		
		return "";
	}
	
	
	/**
	* Calculate and output the length of the DNA sequence of the reagent identified by $rid
	*
	* @author John Paul Lee <ninja_gara@hotmail.com>
	* @version 2005
	*
	* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	* @version 3.1 2006-09-27
	*
	* @param INT $rID Internal database ID of the reagent in question
	*
	* @return INT
	*
	* @deprecated (this function is no longer actively used but may be of use for future adaptations)
	*/
	# Modified Sept 26/06 by Marina
	function output_length($rid)
	{
		global $conn;

		// Update Aug. 17, 2010
		$seqPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);

		$foundSeqID_rs = mysql_query("SELECT s.`sequence` FROM `ReagentPropList_tbl` p, `Sequences_tbl` s WHERE p.`reagentID`='" . $rid . "' AND p.`propertyID` = '" . $seqPropID . "' AND s.`seqID`=p.`propertyValue` AND p.`status`='ACTIVE' AND s.`status`='ACTIVE'", $conn);

		if ($foundSeqID_ar = mysql_fetch_array($foundSeqID_rs, MYSQL_ASSOC))
		{
			# The following code segment was written by Marina on Sept 26, 2006
			$sequence = $foundSeqID_ar["sequence"];

			if (strlen($sequence) > 0)
			{
				return strlen($sequence);
			}
			else
			{
				return null;
			}
		}
	
		return null;
	}

	/**
	* Calculate and output the length of the protein sequence of the reagent identified by $rid
	*
	* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	* @version 3.1 2006-09-27
	*
	* @param INT $rID Internal database ID of the reagent in question
	*
	* @return INT
	*
	* @deprecated (this function is no longer actively used but may be of use for future adaptations)
	*/
	// Written Sept 27/06 by Marina
	function outputProteinLength($rID)
	{
		global $conn;
		
		$protSeqRS = mysql_query("SELECT s.`sequence`, s.`length` FROM `Sequences_tbl` s, `ReagentPropList_tbl` r WHERE r.`reagentID`='" . $rID . "' AND r.`propertyID`='46' AND s.`seqID`=r.`propertyValue` AND r.`status`='ACTIVE' AND s.`status`='ACTIVE'", $conn) or die("Could not select protein sequence, frame and length: " . mysql_error());

		if ($protSeq_ar = mysql_fetch_array($protSeqRS, MYSQL_ASSOC))
		{
			$sequence = $protSeq_ar["sequence"];
			$length = $protSeq_ar["length"];

			# First check if length has been stored explicitly
			if ($length != "")
			{
				return $length;
			}
			else
			{
				# if length has not been stored, take the sequence and compute length on the fly
				if (strlen($sequence) > 0)
				{
					return strlen($sequence);
				}
				else
				{
					# don't bother with frame, start and end; if there's no sequence forget about length
					return null;
				}
			}
		}

		# don't bother with the rest, just return nothing
		return null;
	}


	/**
	* Filter out whitespace from $sequence ($sequence can be of any type - DNA, RNA or Protein)
	*
	* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	* @version 3.1 2006-08-25
	*
	* @param STRING $sequence
	* @return STRING Sequence without leading, trailing and internal whitespace
	*/
	// Aug 25/06, Marina
	function filter_spaces($sequence)
	{
		$outSeq = "";
		$toks = array();

		// Aug. 18, 2010: Strip off leading and trailing whitespace too
		$sequence = trim($sequence);

		$toks = explode(" ", $sequence);

		foreach ($toks as $key=>$tok)
		{
			$outSeq .= $tok;
		}

		return $outSeq;
	}
 

	/**
	* Output plain cDNA sequence in chunks of 10 nt
	*
	* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	* @version 3.1 2006-08-27
	*
	* @param STRING $sequence
	*/
	// Written Sept 27, 2006 by Marina
	// Outputs plain cDNA sequence in chunks of 10 nt
	function output_sequence($rID)
	{
		global $conn;
		$rfunc_obj = new Reagent_Function_CLass();

		$seqPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);

		# Get nt sequence
		$foundSeqID_rs = mysql_query("SELECT s.`sequence` FROM `ReagentPropList_tbl` p, `Sequences_tbl` s WHERE p.`reagentID`='" . $rID . "' AND p.`propertyID` = '" . $seqPropID . "' AND s.`seqID`=p.`propertyValue` AND p.`status`='ACTIVE' AND s.`status`='ACTIVE'", $conn);

		if ($foundSeqID_ar = mysql_fetch_array($foundSeqID_rs, MYSQL_ASSOC))
		{
			$sequence = $foundSeqID_ar["sequence"];

			if (strlen($sequence) > 0)
			{
				$dnaSeq = $rfunc_obj->spaces($sequence);

				echo "<table cellpadding=\"2\" border=\"0\">";

				if (strlen($dnaSeq) > 0)
				{

					$tmp_dna = split("\n", $dnaSeq);
					
					foreach ($tmp_dna as $i=>$tok)
					{
						echo "<TR>";
	
						$rowIndex = $i+1;
	
						if ($i == 0)	// first row
						{
							$rowStart = 1;
						}
						else
						{
							$rowStart = $i*100 + 1;
						}
						
						// Nov. 3/06 - Output nt count on last line
						if (strlen($sequence) >= ($rowIndex*100))
						{
							$rowEnd = $rowIndex*100;
						}
						else
						{
							$rowEnd = strlen($sequence);
						}
	
						echo "<TD style=\"font-size:8pt; text-align:right; vertical-align:top; white-space:nowrap;\">" . $rowStart . "&nbsp;</TD>";
						
						$tmp_chunks = split(" ", $tok);
	
						foreach ($tmp_chunks as $j=>$chunk)
						{
							$chunk = trim($chunk);
	
							if (strlen($chunk) > 0)
							{
								echo "<TD>";
									echo "<pre font-size:8pt; text-align:justify>" . $chunk . "</pre>";
								echo "</TD>";
							}
						}
	
						echo "</TR>";
					}
				}
				echo "</table>";
			}
			else
			{
				echo "";
			}
		}
		else
		{
			echo "";
		}
	}


	/**
	* Retrieve from the database and return the longest translated frame of the current reagent's DNA sequence
	*
	* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	* @version 3.1 2006-08-27
	*
	* @param INT $rID
	* @return INT
	*/
	// Written Sept 27, 2006 by Marina
	// Output translation frame of a sequence
	function getFrame($rID)
	{
		global $conn;
		
		$rfunc_obj = new Reagent_Function_CLass();
		$gfunc_obj = new generalFunc_Class();

		$reagentType = $gfunc_obj->getTypeID($rID);

		// Modified Aug. 17/09: changed db structure
		# Hardcoding protein sequence property ID but selection trivial
		if ($rfunc_obj->hasAttribute($reagentType, $_SESSION["ReagentPropCategory_Name_ID"]["protein sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["Pprotein Sequence"]))
		{
			$propID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentPropCategory_Name_ID"]["protein sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]);
		}
		else if ($rfunc_obj->hasAttribute($reagentType, $_SESSION["ReagentPropCategory_Name_ID"]["protein translation"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]))
		{
			$propID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentPropCategory_Name_ID"]["protein translation"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);
		}

		$protSeqRS = mysql_query("SELECT s.`frame` FROM `Sequences_tbl` s, `ReagentPropList_tbl` r WHERE r.`reagentID`='" . $rID . "' AND r.`propertyID`='" . $propID . "' AND s.`seqID`=r.`propertyValue` AND r.`status`='ACTIVE' AND s.`status`='ACTIVE'", $conn) or die("Could not select frame: " . mysql_error());

		if ($seqResult = mysql_fetch_array($protSeqRS, MYSQL_ASSOC))
		{
			return $seqResult["frame"];
		}

		return null;
	}


	/**
	* Retrieve from the database and output the protein sequence of the reagent identified by $rID (print sequence in chunks of 10 aa)
	*
	* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	* @version 3.1 2006-08-27
	*
	* @param INT $rID
	* @param STRING $outputType Differentiates between views (e.g. reagent details or modification)
	*/
	function outputProteinSequence($rID, $outputType)
	{
		global $conn;

		$rfunc_obj = new Reagent_Function_CLass();
		$gfunc_obj = new generalFunc_Class();

		// Modified Aug. 17/09: Differentiate between protein translation as a feature of DNA and standalone protein sequence
		$reagentType = $gfunc_obj->getTypeID($rID);

		if ($rfunc_obj->hasAttribute($reagentType, "protein sequence", $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]))
		{
			$propID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["protein sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]);
		}
		else if ($rfunc_obj->hasAttribute($reagentType, "protein translation", $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]))
		{
			$propID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["protein translation"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);
		}

		$protSeqRS = mysql_query("SELECT s.`sequence`, s.`frame`, s.`start`, s.`end`, s.`length` FROM `Sequences_tbl` s, `ReagentPropList_tbl` r WHERE r.`reagentID`='" . $rID . "' AND r.`propertyID`='" . $propID . "' AND s.`seqID`=r.`propertyValue` AND r.`status`='ACTIVE' AND s.`status`='ACTIVE'", $conn) or die("Could not select sequence: " . mysql_error());

		if ($seqResult = mysql_fetch_array($protSeqRS, MYSQL_ASSOC))
		{
			$constProtSeq = $seqResult["sequence"];
			$protSeq = $rfunc_obj->spaces($constProtSeq);
			$frame = $seqResult["frame"];
			$startpos = $seqResult["start"];
			$endpos = $seqResult["end"];
			$length = $seqResult["length"];
		}

		if ($outputType != "modify")
		{
			echo "<table cellpadding=\"4\" border=\"0\">";
			
				$tmp_prot = split("\n", $protSeq);

				foreach ($tmp_prot as $i=>$tok)
				{
					echo "<TR>";
						$rowIndex = $i+1;
						
						if ($i == 0)	// first row
						{
							$rowStart = 1;
						}
						else
						{
							$rowStart = $i*100 + 1;
						}
						
						// Nov. 3/06 - Output nt count on last line
						if ($length >= ($rowIndex*100))
						{
							$rowEnd = $rowIndex*100;
						}
						else
						{
							$rowEnd = $length;
						}
						
						//$rowEnd = $rowIndex*100;

						// Updated July 15/08
// july 15/08					echo "<TD nowrap style=\"font-size:10px; font-weight:bold; text-align:right; vertical-align:top\">" . $rowStart . "&nbsp;-&nbsp;" . $rowEnd . "</TD>";

						// July 15/08: Show start position only
						echo "<TD nowrap style=\"font-size:10px; font-weight:bold; text-align:right; vertical-align:top\">" . $rowStart . "</TD>";
	
						$tmp_chunks = split(" ", $tok);
		
						foreach ($tmp_chunks as $j=>$chunk)
						{
							$chunk = trim($chunk);
	
							if (strlen($chunk) > 0)
							{
								echo "<TD>";
									echo "<pre font-size:11px;  text-align:justify>" . $chunk . "</pre>";
								echo "</TD>";
							}
						}

					echo "</TR>";
				}
				
			echo "</table>";
		}
		else
		{
			echo "";
		}
	}


	/**
	* Retrieve from the database and output the RNA sequence of the reagent identified by $rID
	*
	* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	* @version 3.1 2010
	*
	* @param INT $rID
	* @param STRING $outputType Obsolete, kept to be consistent with output_sequence and outputProteinSequence, but not used in this function
	*
	* @return STRING
	*/
	function outputRNASequence($rID, $outputType)
	{
		global $conn;

		$rfunc_obj = new Reagent_Function_CLass();
		$gfunc_obj = new generalFunc_Class();

		// Modified Aug. 17/09: Differentiate between protein translation as a feature of DNA and standalone protein sequence
		$reagentType = $gfunc_obj->getTypeID($rID);

		$propID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["rna sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["RNA Sequence"]);

		$rnaSeqRS = mysql_query("SELECT s.`sequence`, s.`start`, s.`end`, s.`length` FROM `Sequences_tbl` s, `ReagentPropList_tbl` r WHERE r.`reagentID`='" . $rID . "' AND r.`propertyID`='" . $propID . "' AND s.`seqID`=r.`propertyValue` AND r.`status`='ACTIVE' AND s.`status`='ACTIVE'", $conn) or die("Could not select sequence: " . mysql_error());

		if ($seqResult = mysql_fetch_array($rnaSeqRS, MYSQL_ASSOC))
		{
			$constRNASeq = $seqResult["sequence"];
			$rnaSeq = $rfunc_obj->spaces($constRNASeq);
			$startpos = $seqResult["start"];
			$endpos = $seqResult["end"];
			$length = $seqResult["length"];

			echo $rnaSeq;
		}
	}


	/**
	 * Retrieve from the database and output the DNA sequence of the reagent identified by $rID
	 *
	 * Modified by Marina on April 20, 2006 - Assuming that all sequences are cDNA and translation should begin at the first nucleotide (previously was incorrectly relying on 5' start and 3' stop values).  This fixes the problem of sequences not being translated.
	 *
	 * Last modified: Feb. 14/08
	 *
	 * @author John Paul Lee <ninja_gara@hotmail.com>
	 * @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2010
	 *
	 * @param INT $rID
	 * @param STRING $typeOfOutput Dictates output format according to view
	 *
	*/
	function output_sequence2($rid, $typeOfOutput)
	{
		global $conn;

		$cdna_sequence = "";
		$cdna_subsequence = "";
		$protein_sequence = "";

		$rfunc_obj = new Reagent_Function_Class();

		// Aug. 17/09
		$dnaSeqPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);

		// 'protein sequence' or 'protein translation'??
		$protSeqPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["protein sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]);

		// NOTE: If you change the types of sequences that are available, you must add it here!
		$foundSeqID_rs = mysql_query("SELECT `propertyID`, `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $rid . "' AND `propertyID`='" . $dnaSeqPropID . "' AND `status`='ACTIVE'", $conn) or die("Could not select sequence: " . mysql_error());

		while ($foundSeqID_ar = mysql_fetch_array($foundSeqID_rs, MYSQL_ASSOC))
		{
			$foundRealSeq_rs = mysql_query("SELECT `seqTypeID`, `sequence` FROM `Sequences_tbl` WHERE `seqID` IN ('" . $foundSeqID_ar["propertyValue"] . "') AND `status`='ACTIVE' ORDER BY `seqTypeID`", $conn);
			
			while(  $foundRealSeq_ar = mysql_fetch_array( $foundRealSeq_rs, MYSQL_ASSOC ) )
			{
				if( $foundRealSeq_ar["seqTypeID"] == $this->getSeqTypeID("DNA") && $foundSeqID_ar["propertyID"] == $dnaSeqPropID)
				{
					$cdna_sequence = $foundRealSeq_ar["sequence"];
				}
				elseif( $foundRealSeq_ar["seqTypeID"] == $this->getSeqTypeID("Protein") && $foundSeqID_ar["propertyID"] == $protSeqPropID)
				{
					$protein_sequence = $foundRealSeq_ar["sequence"];
				}
				else
				{
					// Aug. 17/09: add 'elseif RNA'

					echo "ERROR! Did not find a matching propertyID for the database ID!";
					return "";
				}
			}
		}

		switch ($typeOfOutput)
		{
			case 1:
				// Basic output, with no frills
				echo $cdna_sequence;
			break;

			case 2:
				// Basic output with breaks in between every 10
				echo chunk_split($cdna_sequence, 10, " ");
			break;

			case 3:
				$cdna_sequence = trim($cdna_sequence);	// just a precaution
				$tmp_dna = explode(" ", chunk_split($cdna_sequence, 10, " "));

				$cols = 0;		// number of 10-nt chunks displayed in current row
				$MAX_COL_CONST = 10;	// constant maximum number of 10-nt chunks per row

				$rowCount = 0;
				$colCount = 0;

				$tok = trim($tmp_dna[$colCount]);

				while ($tok)
				{
					if (strlen($tok) > 0)
					{
						$rowIndex = $rowCount+1;
	
						if ($rowCount == 0)	// first row
						{
							$rowStart = 1;
						}
						else
						{
							$rowStart = $rowCount*100 + 1;
						}
						
						// Nov. 3/06 - Output nt count on last line
						if (strlen($cdna_sequence) >= ($rowIndex*100))
						{
							$rowEnd = $rowIndex*100;
						}
						else
						{
							$rowEnd = strlen($cdna_sequence);
						}
	
						// The following formatting produces an ULTIMATE alignment. DO NOT CHANGE!!!!!!!!!!! 
						// Do not change the padding or size of the textarea either (in Reagent_Output_Class-> print_property_final->sequence->Modify - keep 120 cols)
						if ($rowStart == 1)
							echo "    ";
// may 2/08					else if ($rowStart < 900)
						else if ($rowStart <= 901)	// may 2/08
							echo "  ";
// may 2/08					else if ($rowStart > 901)
						else		// may 2/08
							echo " ";

// Changed May 2/08, Marina - Karen asked to only show the start index like NCBI
// may 2/08					echo $rowStart . "-" . $rowEnd . " ";

	echo $rowStart . " ";
						while ($cols < $MAX_COL_CONST)
						{
							echo trim($tok);
							echo " ";
							$cols++;
							$colCount++;
							$tok = trim($tmp_dna[$colCount]);
						}
					
						$cols = 0;
						echo "\n";
					}

					$rowCount++;
				}
			break;

			// Aug. 30, 2010: This is not used, property names are obsolete, but don't remove yet - will need later when redesigning detailed view and showing DNA-protein alignment!
			case 4:
				// Case where you want to output alignment between cdna and protein and you have:
				// 1. cdna sequence
				// 2. protein sequence
				// 3. 5' and 3' primers for the cdna sequence
				
				echo "<table border=1 width=100%>";
				
				$outputPlace = 0;
				
				$setCount = 0;  // Holds the number of sets currently outputted for this line
				$lineCount = 0; // Holds the number of lines that have been outputted so far for this sequence
				
				
				$maxLineOutput = 10;
				
				$fivePrimer = 0;
				$threePrimer = 0;
				
				// Generic Sequence counters
				$seqCount = 1;
				$seqArrayCount = 0;
				$seqIncrementor = 10;
				
				$proteinSeqCount = 0;
				$proteinSpotCount = 0;
				
				$proteinStartSpot_tmp = 0;
				$setProteinStart_tmp = 0;
				
				$output_seq = "";
				
				if( strlen( trim( $cdna_subsequence ) ) > 0)
				{
					$output_seq = trim( $cdna_subsequence );
					
				}
				elseif( strlen( trim( $cdna_sequence ) ) > 0 )
				{
					$output_seq = trim( $this->filter_spaces($cdna_sequence) );

					// If this is a full cdna sequence, grab the 5' and 3' cutters!
					$primers_rs = mysql_query( "SELECT `propertyID`, `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $rid . "' AND `propertyID` IN ('" . $_SESSION["ReagentProp_Name_ID"]["5' start"] . "','" . $_SESSION["ReagentProp_Name_ID"]["3' stop"] . "') AND `status`='ACTIVE'", $conn );

					while( $primers_ar = mysql_fetch_array( $primers_rs, MYSQL_ASSOC ) )
					{
						if( $primers_ar["propertyID"] == $_SESSION["ReagentProp_Name_ID"]["5' start"] )
						{
							$fivePrimer = $primers_ar["propertyValue"];
						}
						elseif( $primers_ar["propertyID"] == $_SESSION["ReagentProp_Name_ID"]["3' stop"] )
						{
							$threePrimer = $primers_ar["propertyValue"];
						}
					}
				}
				else
				{
					// If no sequence was found for this reagent, just return nothing!!
					// FIX-IT: There should be something better here
					echo "<tr>";
					echo "<td>";
					echo "";
					echo "</td>";
					echo "</tr>";
					echo "</table>";
					return "";
				}
				
				if( $fivePrimer == 0 && $threePrimer == 0 )
				{
					$fivePrimer = 1;
					$threePrimer = strlen( $output_seq );
				}

				$cdna_length = strlen( $output_seq );
				
				$spacedSeq = chunk_split($output_seq, 10, " ");
				$subSequence_ar = explode(" ", $spacedSeq);
				
				$startTag = "<span class=cdna>";
				$endTag = "</span>";
				
				$setSeparator = "&nbsp;";
				
				echo $startTag;

// aug. 30, 2010: if using this code, uncomment translate() function
				$protein_sequence = $this->translate( $output_seq, $fivePrimer, $threePrimer );

				$isDone = false;
				
				while (!$isDone)
				{
					if( $setCount == 0 )
					{
						// Output the new start of the row!
						echo "";
						echo "<tr>";
						echo "<td>";

						echo ( ( $lineCount * ( $seqIncrementor * $maxLineOutput ) ) + 1 );
						echo "-";
						echo ( ( $lineCount + 1 ) * ( $seqIncrementor * $maxLineOutput ) );
						echo "</td>";
						echo "<td>";
						echo "<tt>";
					}
				
					if( $outputPlace == 0  )
					{
						// If the output is BEFORE the protein sequence
						$seqCount += $seqIncrementor;

						$spotInSub = 0;
						$temp1 =  substr( $subSequence_ar[ $seqArrayCount ], 0 , $spotInSub  );
						$temp2 =  substr( $subSequence_ar[ $seqArrayCount ], $spotInSub , $seqIncrementor );	
						$setProteinStart_tmp = $setCount;
						$proteinStartSpot_tmp = $spotInSub;
						
						echo $temp1;
						echo $startTag . $temp2 . $endTag;
						
						$seqArrayCount++;
						$setCount++;
						
						// Change the placement of the output is in!
						$outputPlace = 1;
					}
					elseif( $outputPlace == 1 )
					{
						$seqCount += $seqIncrementor;
						
						// If the output is IN the protein sequence
						if( $seqCount < $threePrimer )
						{
							echo $startTag . $subSequence_ar[ $seqArrayCount ] . $endTag;
							
							$seqArrayCount++;
							$setCount++;
						}
						else
						{
							$spotInSub = $threePrimer % $seqIncrementor;
							
							echo $startTag . substr( $subSequence_ar[ $seqArrayCount ], 0 , $spotInSub  ) . $endTag;
							echo substr( $subSequence_ar[ $seqArrayCount ], $spotInSub , ( $seqIncrementor ) );
							
							$seqArrayCount++;
							$setCount++;
							
							// Change the placement of the output is in!
							$outputPlace = 2;
						}
					}
					elseif( $outputPlace == 2 )
					{
						// If the output is AFTER the protein sequence
						
						$seqCount += $seqIncrementor;
						echo $subSequence_ar[ $seqArrayCount ];
							
						$seqArrayCount++;
						$setCount++;
					}
			
					if( !($seqCount <= $cdna_length) )	// '=' sign added Apr 19/06 by Marina and Adrian
					{
						$isDone = true;
					}
					
					if( $setCount == $maxLineOutput || $isDone )
					{
						// Output the end of the row!
						echo "<br>";
						$lineCount++;
						$setCount = 0;
					}
					else
					{
						echo $setSeparator;
					}
					
					if( ( $outputPlace == 1 || $outputPlace == 2 )  && $setCount == 0 )
					{
						// Sept 7/06, Marina -- Passing arguments by reference is deprecated, get warnings - but removing the '&' breaks the code, get incorrect sequence translation.  Keep it for now and investigate, perhaps change later

// aug. 30, 2010: if using this code, uncomment outputProteinSeq() function
						$proteinSpotCount = $this->outputProteinSeq($protein_sequence, $proteinStartSpot_tmp, $setProteinStart_tmp, &$proteinSeqCount, $proteinSpotCount);

						$setProteinStart_tmp = 0;
						$proteinStartSpot_tmp = 0;
					}
					elseif( $setCount == 0 )
					{
						echo "<BR>";
					}
				}
				
				echo $endTag;
				
				echo "</table>";
			break;

			case 5:
				echo chunk_split($cdna_sequence, 10, " ");
			break;
		}
	}

/* Aug. 30, 2010: Not used in this version but don't delete yet, will need for outputting DNA-protein alignment view
	function outputProteinSeq($protein_sequence, $proteinStartSpot_tmp, $setProteinStart_tmp, $proteinSeqCount, $proteinSpotCount)
	{
		// Output the PROTEIN SEQUENCE
		//if( $outputPlace == 1 && $setCount == 0 )
		//echo "protein seq left: " . $protein_sequence . "<br>";
		//echo "proteinstartspot: " . $proteinStartSpot_tmp . " -- setproteinstart: " . $setProteinStart_tmp 
			//. " -- proteinSeqcount: " . $proteinSeqCount . " -- proteinSpotcount: " . $proteinSpotCount . "<BR>";
		$startTag = "<tt>";
		$endTag = "</tt>";

		// While IN the protein sequence

		$finishedProteinOutput = false;
		//echo "What is this?:" . $proteinStartSpot_tmp . "<BR>";
		echo $startTag . str_repeat( "&nbsp;", $proteinStartSpot_tmp ) . $endTag;
		echo $startTag . str_repeat( $this->setSeparator, ($setProteinStart_tmp * $this->seqIncrementor) ) . $endTag;
		
		while(!$finishedProteinOutput)
		{
			switch($proteinSpotCount)
			{
				case 0:
					//Before the first DNA codon for the Protein
					$proteinSpotCount++;
					break;
				case 1: 
					// first actual DNA codon for the Protein
					$proteinStartSpot_tmp++;
					$proteinSpotCount++;
					echo "&nbsp;";
					break;
				case 2:
					// Output DNA codon for protein
					echo "<span class=protein>" . substr( $protein_sequence, $proteinSeqCount, 1 ) . "</span>";
					
					$proteinSpotCount++;
					$proteinSeqCount++;
					$proteinStartSpot_tmp++;
					break;
				case 3:
					$proteinStartSpot_tmp++;
					
					$proteinSpotCount = 1;
					echo "&nbsp;";
					break;
			}
			
			if( $proteinStartSpot_tmp >= $this->seqIncrementor )
			{
				// Will add a space when protein current spot is greater than the max set! ie. new set needed!

				echo $this->setSeparator;

				$proteinStartSpot_tmp = 0;
				$setProteinStart_tmp++;
				
				
			}
			
			if( $setProteinStart_tmp == $this->maxLineOutput )
			{
				echo "</tt>";
				echo "</td>";
				echo "</tr>";
				
				$finishedProteinOutput = true;
				$proteinStartSpot_tmp = 0;
				$setProteinStart_tmp = 0;
			}
		}
		
		return $proteinSpotCount;
	}
*/

	/**
	* Output Insert accession number as a hyperlink to NCBI
	*
	* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	* @version 3.1
	*
	* @param INT $rid
	*
	*/
	// aug. 30, 2010: removed $output_type argument
	function outputAccession($rid)
	{
		global $conn;
		$functionerror =  ".outputAccession(";
		$rfunc_obj = new Reagent_Function_Class();

		$to_return = "<TABLE>";

		$to_return = "<ul style=\"padding-left:1.5em; padding-top:0.5em;\">";

		$accPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["accession number"], $_SESSION["ReagentPropCategory_Name_ID"]["External Identifiers"]);

		$ar_rs = mysql_query("SELECT `propertyID`, `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $rid . "' AND `propertyID`='" . $accPropID . "'" . " AND `status`='ACTIVE'", $conn) or die($this->classerror . $functionerror . "1)" . mysql_error());
	
		while ($ar_ar = mysql_fetch_array($ar_rs, MYSQL_ASSOC))
		{
			if (strcasecmp($ar_ar["propertyValue"], "in house" ) != 0)
			{
				$to_return = $to_return . "<li style=\"font-size:8pt\"><a href=\"http://www.ncbi.nlm.nih.gov/entrez/viewer.fcgi?cmd=Search&db=nuccore&val=" . $ar_ar["propertyValue"] . "&doptcmdl=GenBank&cmd=retrieve\" target=\"_blank\">" . strtoupper($ar_ar["propertyValue"]) . "</a></li>";
			}
			else
			{
				$to_return = $to_return . "<li style=\"font-size:8pt\">" . $ar_ar["propertyValue"] . "</li>";;
			}
		}

		$to_return = $to_return .  "</ul>";

		return $to_return;
	}

	/**
	 * Output Alternate ID for Insert and Cell Line.
	 *
	 * Special output format: During modification, user selects a checkbox that contains the name of the external identifier (e.g. Kasuza, IMAGE, RIKEN, etc.) and types in the numerical portion; the two are then joined together by processing code.  On the detailed view, all external ID values are printed as a bulleted list.
	 *
	 * @author John Paul Lee <ninja_gara@hotmail.com>
	 * @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT $rid
	 * @param STRING $output_type Differentiate between 'View' and 'Modify' modes
	 * @param STRING $category See explanation of reagent type attribute categories.  In the case of Alternate ID, the category will almost certainly always be 'External Identifiers' - however, for consistent function calls and added flexibility, keep category as a function argument.
	 * @param INT $rTypeID Reagent type ID - new in v.3.1, needed for retrieving external db names specific to this reagent type
	*/
	function output_Alternate_ID($rid, $output_type, $category, $rTypeID)
	{
		global $conn;
		$functionerror =  ".output_Alternate_ID(";
		$rfunc_obj = new Reagent_Function_Class();

		$to_return = "<TABLE>";

		$altIDPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["alternate id"], $_SESSION["ReagentPropCategory_Name_ID"][$category]);

		if ($output_type == "Modify")
		{
			// existing for this reagent
			$ar_rs = mysql_query("SELECT propertyValue FROM ReagentPropList_tbl WHERE reagentID='" . $rid . "' AND propertyID='" . $altIDPropID . "' AND `status`='ACTIVE'", $conn) or die($this->classerror . $functionerror . "1)" . mysql_error());

			while ($r_exist_ar = mysql_fetch_array($ar_rs, MYSQL_ASSOC))
			{
				$value = $r_exist_ar["propertyValue"];
				$ar_ar[] = $value;

				// get the actual ID portion
				$tmp_ids[substr($value, 0, strpos($value, ":"))] = substr($value, strpos($value, ":")+1, strlen($value));
			}

			// All alt. IDs
			$propID = $rfunc_obj->getRTypeAttributeID($rTypeID, "alternate id", $_SESSION["ReagentPropCategory_Name_ID"][$category]);

			$set_rs = mysql_query("SELECT b.entityName FROM System_Set_Groups_tbl a, System_Set_tbl b WHERE a.ssetGroupID=b.ssetGroupID AND a.reagentTypeAttributeID='" . $propID . "' AND a.status='ACTIVE' AND b.status ='ACTIVE' ORDER BY b.entityName", $conn) or die("FAILURE IN Reagent_Creator_Class.print_set(1): " . mysql_error());

			while($set_ar = mysql_fetch_array($set_rs, MYSQL_ASSOC))
			{
				$ids_ar[] = $set_ar["entityName"];
			}

			//print_r(array_values($ids_ar));

			foreach ($ids_ar as $num => $key)
			{
				if (in_array($key, array_keys($tmp_ids)))
				{
					$value = $tmp_ids[$key];

					$to_return .= "<TR ID=\"alt_id_row_" . $key . "\" style=\"display:table-row\">";

					$to_return .= "<TD>" . $key . ":</TD>";

					$to_return .= "<TD>";

					// Python form input
					$to_return .= "<INPUT type=\"hidden\" name=\"reagent_detailedview_alternate_id_prop\" value=\"" . $key . "\">";

					$to_return .= "<INPUT ID=\"" . $key . "_alternate_id_textbox\" name=\"alternate_id_" . $key . "_textbox_name\" size=\"15\" VALUE=\"" . $value . "\"></INPUT>";
				}
				else
				{
					$to_return .= "<TR ID=\"alt_id_row_" . $key . "\" style=\"display:none\">";

					$to_return .= "<TD>" . $key . ":</TD>";

					$to_return .= "<TD>";
				
					$to_return .= "<INPUT ID=\"" . $key . "_alternate_id_textbox\" name=\"alternate_id_" . $key . "_textbox_name\" size=\"15\" VALUE=\"\"></INPUT>";
				}

				$to_return .= "</TD>";
				$to_return .= "</TR>";
			}

				// Add 'Other' row
				$to_return .= "<TR ID=\"alt_id_row_Other\" style=\"display:none\">";
				
					$to_return .= "<TD>Other:</TD>";

					$to_return .= "<TD>";

					$to_return .= "<INPUT ID=\"Other_alternate_id_textbox\" name=\"alternate_id_name_txt\" size=\"15\" VALUE=\"\"></INPUT>";
					
					$to_return .= "</TD>";
					
				$to_return .= "</TR>";

			$to_return .= "</TABLE>";
		}
		else
		{
			$to_return = "<ul style=\"padding-left:1.5em; padding-top:0.5em;\">";

			$ar_rs = mysql_query("SELECT `propertyID`, `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $rid . "' AND `propertyID`='" . $altIDPropID . "'" . " AND `status`='ACTIVE'", $conn) or die($this->classerror . $functionerror . "1)" . mysql_error());
		
			while ($ar_ar = mysql_fetch_array($ar_rs, MYSQL_ASSOC))
			{
				$to_return = $to_return . "<li style=\"font-size:8pt\">" . strtoupper($ar_ar["propertyValue"]) . "</li>";
			}
	
			$to_return = $to_return .  "</ul>";
		}

		return $to_return;
	}


	/**
	 * Output Selectable Marker (Jan. 28/08, Marina: "Selectable Marker" replaces both Antibiotic Resistance and Resistance Marker for Vector, for Cell Line resistance marker is still used - see output_Resistance_Marker() function)
	 *
	 * @author John Paul Lee <ninja_gara@hotmail.com>
	 * @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT $rid
	 * @param STRING $output_type Differentiate between 'View' and 'Modify' modes
	*/
	function output_Selectable_Marker($rid, $output_type)
	{
		global $conn;

		$functionerror =  ".output_Selectable_Marker(";

		$rfunc_obj = new Reagent_Function_CLass();

		$to_return = "<ul style=\"padding-left:1.5em; padding-top:0.5em;\">";

		$ar_rs = mysql_query("SELECT DISTINCT(`propertyValue`) FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $rid . 
			"' AND `propertyID` IN ('" . $_SESSION["ReagentProp_Name_ID"]["selectable marker"] . "')"
			. " AND `status`='ACTIVE'", $conn) or die( $this->classerror . $functionerror . "1)" . mysql_error() );

		while( $ar_ar = mysql_fetch_array( $ar_rs, MYSQL_ASSOC ) )
		{
			$to_return .= "<li>" . $ar_ar["propertyValue"];

			$pStart = $rfunc_obj->getStartPos($rid, "selectable marker", $_SESSION["ReagentProp_Name_ID"]["selectable marker"], $ar_ar["propertyValue"]);

			$pEnd = $rfunc_obj->getEndPos($rid, "selectable marker", $_SESSION["ReagentProp_Name_ID"]["selectable marker"], $ar_ar["propertyValue"]);

			// pStart can be 0 (right at the beginning of sequence), but if pEnd is 0, that means the value does not exist
			if ($pEnd != 0)
			{
				$to_return .= "&nbsp;&nbsp;<span style=\"color:#FF7F24\">(nt " . $pStart . "&nbsp;&#45;";
				$to_return .= "&nbsp;" . $pEnd . ")</span>";
			}

			$to_return .= "</li>";
		}

		$to_return = $to_return .  "</ul>";
		
		return $to_return;

	}

	/**
	 * Output Resistance Marker for Cell Lines (restored March 3/09)
	 *
	 * @author John Paul Lee <ninja_gara@hotmail.com>
	 * @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param INT $rid
	 * @param STRING $output_type Differentiate between 'View' and 'Modify' modes
	*/
	function output_Resistance_Marker($rid, $output_type)
	{
		global $conn;
		$functionerror =  ".output_Resistance_Marker(";
		$to_return = "<ul>";

		$rm_prop_id =  $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["resistance marker"], $_SESSION["ReagentPropCategory_Name_ID"]["Classifiers"]);

		$ar_rs = mysql_query( "SELECT `propertyID`, `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $rid . "' AND `propertyID`='" . $rm_prop_id . "' AND `status`='ACTIVE'" , $conn  ) or die( $this->classerror . $functionerror . "1)" . mysql_error() );
		
		if (count($ar_ar) > 0)
		{
			while ($ar_ar = mysql_fetch_array($ar_rs, MYSQL_ASSOC))
			{
				$to_return = $to_return . "<li>" . $ar_ar["propertyValue"] . "</li>";
			}
		}
		else
		{
// 			echo "here??";
		}

		$to_return = $to_return .  "</ul>";
		return $to_return;
	}


	/**
	 * Output sequence features with multiple values
	 *
	 * Dec. 9/09: Differentiate between *units* - output 'aa' for Protein and 'nt' for DNA
	 *
	 * @author John Paul Lee <ninja_gara@hotmail.com>
	 * @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1 2008-03-06
	 *
	 * @param INT $rid
	 * @param STRING $feature_type
	 * @param STRING $category (either 'DNA Sequence Features', 'Protein Sequence Features', or 'RNA Sequence Features')
	*/
	function output_Sequence_Features($rid, $feature_type, $category)
	{
		global $conn;

		$functionerror =  ".output_Sequence_Features(";

		$rfunc_obj = new Reagent_Function_CLass();

		$fAlias = $rfunc_obj->getReagentPropertyAlias($feature_type);

		$propID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"][strtolower($feature_type)], $_SESSION["ReagentPropCategory_Name_ID"][$category]);	

		$to_return = "<ul style=\"padding-left:1.5em; padding-top:0.5em;\">";

 		// April 1/08, Marina: Sort output values by position
		$ar_rs = mysql_query("SELECT DISTINCT(propertyValue), startPos, endPos, direction FROM ReagentPropList_tbl WHERE reagentID='" . $rid . "' AND propertyID='" . $propID . "' AND propertyValue <> '' AND status='ACTIVE' ORDER BY startPos", $conn) or die("Could not select sequence feature: " . mysql_error());

		while ($ar_ar = mysql_fetch_array($ar_rs, MYSQL_ASSOC))
		{
			$to_return .= "<li>" . stripslashes($ar_ar["propertyValue"]);

			// April 1, 2009: For Cell Line just output the name of the selectable marker, w/o positions or direction
			if (((strcasecmp(strtolower($feature_type), "selectable marker") == 0) && ($rfunc_obj->getType($rid) != $_SESSION["ReagentType_Name_ID"]["CellLine"])) || (strcasecmp(strtolower($feature_type), "selectable marker") != 0))
			{
				// Positions
				$pStart = $ar_ar["startPos"];
				$pEnd = $ar_ar["endPos"];
	
				// pStart can be 0 (right at the beginning of sequence), but if pEnd is 0, that means the value does not exist
				if ($pEnd != 0)
				{
					$to_return .= "&nbsp;<span style=\"color:#006400\">(nt " . $pStart . "&nbsp;&#45;";
					$to_return .= "&nbsp;" . $pEnd . ")</span>";
				}
	
				$fDirection = $ar_ar["direction"];
	
				$to_return .= "&#44;&nbsp;" . $fDirection . " orientation";
		
				// March 17/08: Add a descriptor for selected features (tag position for tag type and expression system for promoter)
				$fDescr = $rfunc_obj->getFeatureDescriptor($rid, $_SESSION["ReagentProp_Name_ID"][$feature_type], $ar_ar["propertyValue"]);
	
				if (strlen($fDescr) > 0)
				{
					$caption = "";
	
					// April 1/08: Need to add caption for the descriptor - actual descriptor property name (tag position, expression system)
					switch (strtolower($feature_type))
					{
						case 'tag':
							$caption = "&#44;&nbsp;position";
						break;
						
						case 'promoter':
							$caption = "&#44;&nbsp;expression system";
						break;
	
						default:
						break;
					}
	
					$to_return .= $caption . "&nbsp;" . $fDescr;
				}
			}
			$to_return .= "</li>";
		}

		$to_return = $to_return .  "</ul>";

		return $to_return;
	}

/* Aug. 30, 2010: seems to only be used in output_sequence2() => 'switch' => case 4 ==> don't delete it yet
	// Modified April 20, 2006 by Marina
	// Making the assumption that every sequence is a cDNA sequence and its translation should begin at the first nucleotide
	function translate($sequence, $start, $stop)
	{
		$sequence = strtolower( $sequence );
// 		echo "start: " . $start . " -- stop: " . $stop . "<BR>";
// 		echo "length: " . strlen( $sequence ) . "<BR>";

// Removed April 20/06 by Marina
// 		if( $start <= 0 || $stop <= 0 || $stop > strlen( $sequence ) || $stop <= $start )
// 		{
// 			return "";
// 		}

		//echo "stage 2------------------";

// apr 20	$cdna_seq = substr( $sequence, ( $start - 1 ), ( $stop - $start ) );

		$cdna_seq = $sequence;	// April 20/06, Marina

// 		echo "trying cdna_seq : " . $cdna_seq . "<br>";
		$newProteinSeq = "";
		
		$spaced_cdna = chunk_split( $cdna_seq, 3, "|" );
		$aminoacid_ar = explode( "|", $spaced_cdna );
		
		foreach( $aminoacid_ar as $key => $value )
		{
			$newProteinSeq = $newProteinSeq . $this->convertToAA( $value );
		}
		
// 		echo "new protein trans: " . $newProteinSeq . "<BR>";
		return $newProteinSeq;
	}
*/
	
	/**
	 * Determine seqTypeID from seqTypeName
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param STRING $toFind Sequence type name (one of 'DNA', 'RNA' or 'Protein') - corresponds to seqTypeName column in SequenceTypes_tbl
	 * @return INT SequenceTypes_tbl.seqTypeID column value
	*/
	function getSeqTypeID($toFind)
	{
		global $conn;
		
		$type_rs = mysql_query("SELECT `seqTypeID` FROM `SequenceType_tbl` WHERE `status`='ACTIVE' AND `seqTypeName`='" . $toFind . "'", $conn);
		
		if ($type_ar = mysql_fetch_array($type_rs, MYSQL_ASSOC))
		{
			return $type_ar["seqTypeID"];
		}
		
		return -1;
	}
	

	/**
	 * Return the amino acid that corresponds to the given codon (one-character representation)
	 *
	 * @author John Paul Lee <ninja_gara@hotmail.com>
	 * @version 2005
	 *
	 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
	 * @version 3.1
	 *
	 * @param STRING Nucleotide codon triplet
	 * @return CHAR
	*/
	function convertToAA($threeCodons)
	{
		switch(strtolower($threeCodons))	// Aug. 30, 2010, Marina: just in case, add strtolower() call here to be sure
		{
			case "ttt":
			case "ttc": return "F";
				break;
			case "tta":
			case "ttg": 
			case "ctt":
			case "ctc":
			case "cta":
			case "ctg": return "L";
				break;
			case "att":
			case "atc":
			case "ata": return "I";
				break;
			case "atg": return "M";
				break;
			case "gtt":
			case "gtc":
			case "gta":
			case "gtg": return "V";
				break;
			case "tct":
			case "tcc":
			case "tca":
			case "tcg":
			case "agt":
			case "agc": return "S";
				break;
			case "aga":
			case "agg":
			case "cgt":
			case "cgc":
			case "cga":
			case "cgg": return "R";
				break;
			case "cct":
			case "ccc":
			case "cca":
			case "ccg": return "P";
				break;
			case "act":
			case "acc":
			case "aca":
			case "acg": return "T";
				break;
			case "gct":
			case "gcc":
			case "gca":
			case "gcg": return "A";
				break;
			case "tat":
			case "tac": return "Y";
				break;
			case "taa":
			case "tag":
			case "tga": return "*";
				break;
			case "tgt":
			case "tgc": return "C";
				break;
			case "tgg": return "W";
				break;
			case "ggt":
			case "ggc":
			case "gga":
			case "ggg": return "G";
				break;
			case "cat":
			case "cac": return "H";
				break;
			case "caa":
			case "cag": return "Q";
				break;
			case "aat":
			case "aac": return "N";
				break;
			case "aaa":
			case "aag": return "K";
				break;
			case "gat":
			case "gac": return "D";
				break;
			case "gaa":
			case "gag": return "E";
				break;
			
		}
	}
}

?>