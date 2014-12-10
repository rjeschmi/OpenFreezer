<?php

// DO NOT DELETE THIS CLASS - USED IN SEARCH TABLE OUTPUT!!!!!!!!!!!!!!!!!!!!!!!

/**
* PHP versions 4 and 5 http://php.net
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
* @package Search
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Canada
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* Include/require statements
*/
include "../functions.php";

class ColFunctOutputer
{
	var $setSeparator;
	var $seqIncrementor;
	var $maxLineOutput;

	function ColFunctOutputer()
	{
		$this->setSeparator = "&nbsp;";
		$this->seqIncrementor = 10;
		$this->maxLineOutput = 10;
	}
	
	function output( $rid,  $columnType, $outputType )
	{
		switch( $columnType )
		{
			case "Description":
			case "Comment":
			case "Comments":
				return $this->output_description( $rid, $columnType );
			case "Sequence":
				if( $outputType == "Preview" )
				{
					return $this->output_sequence2( $rid , 2 );
				}
				return $this->output_sequence2( $rid, 4 );
			case "Length":
				return $this->output_length( $rid );
			case "Open/Closed":
				return $this->output_hint_rev1( $rid );
			case "Packet":
				if( $outputType == "Preview" )
					{
						return $this->output_packet( $rid, 1 );
					}
				return $this->output_packet( $rid, 2 );

			// May 29/06, Marina
			case "parent vector name":
			case "parent cell line name":
				return $this->outputCellLineParents($rid, $columnType);

			// June 1/09
			case "type":
				return $this->output_Reagent_Type($rid);

			default:
				return "No matching column type was found!";
		}
	}


	// June 1/09
	function output_Reagent_Type($rid)
	{
		global $conn;
		$rfunc = new Reagent_Function_Class();

		$rType = $rfunc->getType($rid);

		$typePropName = $rType . "type";
		$propType = "";

		$query = "SELECT propertyValue FROM ReagentPropList_tbl WHERE propertyID= '" . $_SESSION["ReagentProp_Name_ID"][$typePropName]. "' AND reagentID='" . $rid . "' AND status='ACTIVE'";

		$seq_rs = mysql_query($query, $conn);

		while ($seq_ar = mysql_fetch_array($seq_rs, MYSQL_ASSOC))
		{
			$propType = $seq_ar["propertyValue"];
		}

		return $propType;
	}


	function outputCellLineParents($rid, $colType)
	{
		global $conn;

		$rfunc_obj = new Reagent_Function_Class();	// Aug. 18, 2010

		switch (strtolower($colType))
		{
			case "parent vector name":
				// Find the parent vector and get its name
				$pv_name = "N/A";

				// Update Aug. 18, 2010
				// 1. Get the ID of association called 'vector id' that represents the parent vector for cell lines
				// Aug. 18, 2010: No, the association is called 'cell line parent vector id'
				$assoc_id_rs = mysql_query("SELECT * FROM `Assoc_Prop_Type_tbl` WHERE `APropName`='cell line parent vector id'", $conn) or die("Error fetching parent vector association ID");

// echo "SELECT * FROM `Assoc_Prop_Type_tbl` WHERE `APropName`='cell line parent vector id'";

				while ($assoc_id_ar = mysql_fetch_array($assoc_id_rs, MYSQL_ASSOC))
				{
					$pv_assoc_id = $assoc_id_ar["APropertyID"];
// echo $pv_assoc_id;
				}

				// 2. Fetch the parent's ID from Associations table
				$pv_rs = mysql_query("SELECT * FROM `Association_tbl` a, `AssocProp_tbl` b WHERE a.`reagentID`='" . $rid . "' AND a.`assID`=b.`assID` AND b.`APropertyID`='" . $pv_assoc_id . "' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn) or die("Error fetching parent vector ID");

				while ($pv_ar = mysql_fetch_array($pv_rs, MYSQL_ASSOC))
				{
					$pv_prop_value = $pv_ar["propertyValue"];
				}

				// 3. Find the ID of property 'name'
				// Aug. 18, 2010: Account for category
				$name_prop_id = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);

// removed Aug. 18, 2010
// 				$name_prop_rs = mysql_query("SELECT `propertyID` FROM `ReagentPropType_tbl` WHERE `propertyName`='name' AND `status`='ACTIVE'", $conn) or die("Error fetching ID of property 'name'");
// 
// 				while ($name_prop_ar = mysql_fetch_array($name_prop_rs, MYSQL_ASSOC))
// 				{
// 					$name_prop_id = $name_prop_ar["propertyID"];
// 				}

				// 4. Find the name of the parent in ReagentPropList_tbl
				$pv_name_rs = mysql_query("SELECT `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $pv_prop_value . "' AND `propertyID`='" . $name_prop_id . "' AND `status`='ACTIVE'", $conn) or die("Error finding parent vector name");

				while ($pv_name_ar = mysql_fetch_array($pv_name_rs, MYSQL_ASSOC))
				{
					$pv_name = $pv_name_ar["propertyValue"];
				}		

				return $pv_name;

			case "parent cell line name":
				// Find the parent cell line and get its name - Update Aug. 18, 2010: the property is now called 'parent cell line id', not 'cellline id'
				$assoc_id_rs = mysql_query("SELECT  * FROM `Assoc_Prop_Type_tbl` WHERE `APropName`='parent cell line id'", $conn) or die("Error fetching parent vector association ID");

				while ($assoc_id_ar = mysql_fetch_array($assoc_id_rs, MYSQL_ASSOC))
				{
					$cl_assoc_id = $assoc_id_ar["APropertyID"];
				}

				// 2. Fetch the parent's ID from Associations table
				$cl_rs = mysql_query("SELECT * FROM `Association_tbl` a, `AssocProp_tbl` b WHERE a.`reagentID`='" . $rid . "' AND a.`assID`=b.`assID` AND b.`APropertyID`='" . $cl_assoc_id . "' AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn) or die("Error fetching parent cell line ID");

				while ($cl_ar = mysql_fetch_array($cl_rs, MYSQL_ASSOC))
				{
					$cl_prop_value = $cl_ar["propertyValue"];
				}

				// 3. Find the ID of property 'name'
				// Aug. 18, 2010: Account for category
				$name_prop_id = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["name"], $_SESSION["ReagentPropCategory_Name_ID"]["General Properties"]);
/*
				$name_prop_rs = mysql_query("SELECT `propertyID` FROM `ReagentPropType_tbl` WHERE `propertyName`='name' AND `status`='ACTIVE'", $conn) or die("Error fetching ID of property 'name'");

				while ($name_prop_ar = mysql_fetch_array($name_prop_rs, MYSQL_ASSOC))
				{
					$name_prop_id = $name_prop_ar["propertyID"];
				}*/

				// 4. Find the name of the parent in ReagentPropList_tbl
				$cl_name_rs = mysql_query("SELECT `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $cl_prop_value . "' AND `propertyID`='" . $name_prop_id . "' AND `status`='ACTIVE'", $conn) or die("Error finding parent vector name");

				while ($cl_name_ar = mysql_fetch_array($cl_name_rs, MYSQL_ASSOC))
				{
					$cl_name = $cl_name_ar["propertyValue"];
				}

				return $cl_name;
		}
	}
	
	function output_packet( $rid, $type )
	{
		global $conn;
		
		switch( $type )
		{
			case 1:
				$find_name_rs = mysql_query( "SELECT a.`packetID`, a.`lastName` FROM `PacketOwners_tbl` a INNER JOIN `Packets_tbl` b "
											. " ON a.`packetID`=b.`packetID` WHERE b.`reagentID`='" . $rid . "'" 
											. " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn );
				
				if( $find_name_ar = mysql_fetch_array( $find_name_rs, MYSQL_ASSOC ) )
				{
					$tempName = $find_name_ar["lastName"] . ":" . $find_name_ar["packetID"] ;
					return $tempName;
				}
//				break;
			case 2:
				$find_name_rs = mysql_query( "SELECT b.`packetID`, b.`firstName`, b.`lastName`, b.`packetName` FROM `Packets_tbl` a "
											. " INNER JOIN `PacketOwners_tbl` b ON a.`packetID`=b.`packetID` "
											. " WHERE a.`reagentID`='" . $rid . "'"
											. " AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'" , $conn );
				
				if( $find_name_ar = mysql_fetch_array( $find_name_rs, MYSQL_ASSOC ) )
				{
					$tempName = $find_name_ar["packetID"] . ":" . $find_name_ar["lastName"] . "-" . $find_name_ar["packetName"];
					return $tempName;
				}
//				break;
			default:
				return "";
		}
	}
	
	function output_description( $rid, $columnType )
	{
		global $conn;
		//print_r( $_SESSION["ReagentProp_Name_ID"] );
		//echo "rid: " . $rid . "<br>";
		$currentTypeID = 0;
		
		if( $columnType == "Description" )
		{
			$currentTypeID = $_SESSION["ReagentProp_Name_ID"]["Description"];
		}
		elseif( $columnType == "Comment" || $columnType == "Comments" )
		{
			$currentTypeID = $_SESSION["ReagentProp_Name_ID"]["Comments"];
		};
		
		$foundCommentID_rs = mysql_query( "SELECT `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $rid  . "' "
										. " AND `status`='ACTIVE' AND `propertyID`='" . $currentTypeID . "';" , $conn )
										or die( "[died on comment search]: " . mysql_error() );
		
		if( $foundCommentID_ar = mysql_fetch_array( $foundCommentID_rs, MYSQL_ASSOC ) )
		{
			$foundRealDesc_rs = mysql_query( "SELECT `comment` FROM `GeneralComments_tbl` " 
											. " WHERE `commentID`='" . $foundCommentID_ar["propertyValue"] . "'"
											. " AND `status`='ACTIVE';", $conn );
			
			if(  $foundRealDesc_ar = mysql_fetch_array( $foundRealDesc_rs, MYSQL_ASSOC ) )
			{
				return $foundRealDesc_ar["comment"];
			}
			
			return "ERROR: Did not find the comment id!";
		}
		
		//return "ERROR: No Description found!";
		return "";
	}
	
	function output_hint_rev0( $rid )
	{
		global $conn;
		//$assoc_hint_rs = mysql_query( "SELECT DISTINCT a.`propertyValue` AS hint FROM `AssocProp_tbl` a INNER JOIN `AssocProp_tbl` b ON a.`assID`=b.`assID` " .
									//	" WHERE b.`propertyID`='" . $_SESSION["ReagentProp_Name_ID"]["Insert ID"] . "' AND " .
									//	" b.`propertyValue`='" . $rid . "' AND " .
									//	" a.`propertyID`='" . $_SESSION["ReagentProp_Name_ID"]["Association Hint"] . "'" );
										
		$test_rs = mysql_query( "SELECT `assID` FROM `AssocProp_tbl` WHERE `propertyID`='" . $_SESSION["ReagentProp_Name_ID"]["Insert ID"] . 
								"' AND `propertyValue`='" . $rid . "' AND `status`='ACTIVE'", $conn );
		if( $test_ar = mysql_fetch_array( $test_rs, MYSQL_ASSOC ) )
		{
		$assoc_hint_rs = mysql_query( "SELECT `propertyValue` AS hint FROM `AssocProp_tbl` WHERE `propertyID`='" . 
									$_SESSION["ReagentProp_Name_ID"]["Association Hint"] . "'"
									. " AND `assID`='" . $test_ar["assID"] . "'" 
									. " AND `status`='ACTIVE'", $conn );
		
		if( $assoc_hint_ar = mysql_fetch_array( $assoc_hint_rs, MYSQL_ASSOC ) )
		{
			return $assoc_hint_ar["hint"];
		}
		}
	}
	
	function output_hint_rev1( $rid )
	{
		global $conn;
		$new_hint_rs = mysql_query( "SELECT `propertyValue` AS hint FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $rid . "'"
									. " AND `propertyID`='" . $_SESSION["ReagentProp_Name_ID"]["Association Hint"] 
									. "' AND `status`='ACTIVE'", $conn );
									
		if( $new_hint_ar = mysql_fetch_array( $new_hint_rs, MYSQL_ASSOC ) )
		{
			return $new_hint_ar["hint"];
		}
	}
	
	function output_length( $rid )
	{
		global $conn;
		//print_r($_SESSION["ReagentProp_Name_ID"]);
		$foundSeqID_rs = mysql_query( "SELECT `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $rid . "'"
									. " AND `propertyID` IN ('" . $_SESSION["ReagentProp_Name_ID"]["Sequence"] . "','"
									. $_SESSION["ReagentProp_Name_ID"]["Full CDNA Sequence"] . "','"
									. $_SESSION["ReagentProp_Name_ID"]["Subsequence CDNA"] . "')" 
									. " AND `status`='ACTIVE'", $conn );
		
		if( $foundSeqID_ar = mysql_fetch_array( $foundSeqID_rs, MYSQL_ASSOC ) )
		{
			$foundRealSeq_rs = mysql_query( "SELECT length(`sequence`) as finalLength FROM `Sequences_tbl` "
											. " WHERE `seqID`='" . $foundSeqID_ar["propertyValue"] . "'" 
											. " AND `seqTypeID`='" . getSeqTypeID( "cDNA" ) . "'" 
											. " AND `status`='ACTIVE';", $conn );
			
			if(  $foundRealSeq_ar = mysql_fetch_array( $foundRealSeq_rs, MYSQL_ASSOC ) )
			{
				return $foundRealSeq_ar["finalLength"];
			}
			
			return "";
		}
	}
	
	function output_sequence2($rid, $typeOfOutput)
	{
		global $conn;
		$cdna_sequence = "";
		$cdna_subsequence = "";
		$protein_sequence = "";
		
		// Aug. 17/09
		$seqPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["Sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["DNA Sequence"]);

		$protSeqPropID = $rfunc_obj->getPropertyIDInCategory($_SESSION["ReagentProp_Name_ID"]["Protein Sequence"], $_SESSION["ReagentPropCategory_Name_ID"]["Protein Sequence"]);

		// NOTE: If you change the types of sequences that are available, you must add it here!
		$foundSeqID_rs = mysql_query("SELECT `propertyID`, `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $rid . "' AND `propertyID` IN ('" . $seqPropID . "') AND `status`='ACTIVE'" , $conn);

		while ($foundSeqID_ar = mysql_fetch_array($foundSeqID_rs, MYSQL_ASSOC))
		{
			$foundRealSeq_rs = mysql_query("SELECT `seqTypeID`, `sequence` FROM `Sequences_tbl` WHERE `seqID` IN ('" . $foundSeqID_ar["propertyValue"] . "') AND `status`='ACTIVE' ORDER BY `seqTypeID`;", $conn);
			
			while ($foundRealSeq_ar = mysql_fetch_array($foundRealSeq_rs, MYSQL_ASSOC))
			{
				if ($foundRealSeq_ar["seqTypeID"] == getSeqTypeID("DNA") && $foundSeqID_ar["propertyID"] == $seqPropID)
				{
					$cdna_sequence = $foundRealSeq_ar["sequence"];
				}
				elseif( $foundRealSeq_ar["seqTypeID"] == getSeqTypeID("Protein") && $foundSeqID_ar["propertyID"] == $protSeqPropID)
				{
					$protein_sequence = $foundRealSeq_ar["sequence"];
				}
				else
				{
					echo "ERROR! Did not find a matching propertyID for the database ID!";
					return "";
				}
			}
		}
			//return "ERROR: Did not find the Seq id!";
			
			
		switch ($typeOfOutput)
		{
			case 1:
				// Basic output, with no frills
				echo $cdna_sequence;
			break;

			case 2:
				// Basic output with breaks inbetween every 10
				echo chunk_split($cdna_sequence, 10, " ");
			break;

			case 3:
			break;

			case 4:
				// Case where you want to output alignment between cdna and protein and you have:
				// 1. cdna sequence
				// 2. protein sequence
				// 3. 5' and 3' primers for the cdna sequence
				
				echo "Attempting detailed<br>";
				
				echo "<table border=1>";
				
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
				
				if( strlen( $cdna_subsequence ) > 0)
				{
					$output_seq = $cdna_subsequence;
					
				}
				elseif( strlen( $cdna_sequence ) > 0 )
				{
					$output_seq = $cdna_sequence;
					
					// If this is a full cdna sequence, grab the 5' and 3' cutters!
					$primers_rs = mysql_query( "SELECT `propertyID`, `propertyValue` FROM `ReagentPropList_tbl` WHERE `reagentID`='" . $rid . 
												"' AND `propertyID` IN ('" . $_SESSION["ReagentProp_Name_ID"]["5' start"] . "','" 
												. $_SESSION["ReagentProp_Name_ID"]["3' stop"] . "')" 
												. " AND `status`='ACTIVE'", $conn );
												
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
					return "";
				}
				
				if( $fivePrimer == 0 && $threePrimer == 0 )
				{
					$fivePrimer = 1;
					$threePrimer = strlen( $output_seq );
				}
				
				$cdna_length = strlen( $output_seq );
				
				$spacedSeq = chunk_split( $output_seq, 10, " " );
				//echo "Spaced seq: " . $spacedSeq . "<br>";
				$subSequence_ar = explode( " ", $spacedSeq );
				
				$startTag = "<span class=cdna>";
				$endTag = "</span>";
				
				$setSeparator = "&nbsp;";
				
				echo $startTag;
				
				
				//if( strlen( $protein_sequence ) == 0 )
				//{
				//convertToAA( "aaa" );
				//echo "cdna: " . $output_seq . "<BR>";
				//echo "5 prime: " . $fivePrimer . ": three: " . $threePrimer . "<BR>";
				$protein_sequence = $this->translate( $output_seq, $fivePrimer, $threePrimer );
				//echo "FOUND PROTEIN SEQ: " . $protein_sequence . "<BR>";
				//}
				$isDone = false;
				
				while( !$isDone )
				{
					
					//$setCount = 0;
					
					if( $setCount == 0 )
					{
						// Output the new start of the row!
						echo "";
						echo "<tr>";
						echo "<td>";
						//echo "what th: " . $lineCount . "<br>";
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
						
						if( $seqCount < $fivePrimer )
						{
							echo $subSequence_ar[ $seqArrayCount ];
							$seqArrayCount++;
							$setCount++;
						}
						else
						{
							// Finds the exact spot in the subsequence
							// subtract 1 to INCLUDE the dna seq IN IN the protein sequence
							$spotInSub = ( $fivePrimer % $seqIncrementor ) - 1 ;
							$temp1 =  substr( $subSequence_ar[ $seqArrayCount ], 0 , $spotInSub  );
							$temp2 =  substr( $subSequence_ar[ $seqArrayCount ], $spotInSub , $seqIncrementor );	
							
							$setProteinStart_tmp = $setCount;
							$proteinStartSpot_tmp = $spotInSub;
							//$setCount = $lineCount;
							
							echo $temp1;
							echo $startTag . $temp2 . $endTag;
							
							$seqArrayCount++;
							$setCount++;
							
							// Change the placement of the output is in!
							$outputPlace = 1;
						}
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
					};
					
					//echo "setcount : " . $setCount . "<BR>";
			
					if( !($seqCount < $cdna_length) )
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
					};
					
					
					
					//echo "outputpalce: " . $outputPlace . "<BR>";
					if( ( $outputPlace == 1 || $outputPlace == 2 )  && $setCount == 0 )
					{
						$proteinSpotCount = $this->outputProteinSeq($protein_sequence, $proteinStartSpot_tmp, $setProteinStart_tmp, $proteinSeqCount, $proteinSpotCount);
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
		};
	}


	function outputProteinSeq( $protein_sequence, $proteinStartSpot_tmp, $setProteinStart_tmp, $proteinSeqCount, $proteinSpotCount )
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
		echo $startTag . str_repeat( "&nbsp;", $proteinStartSpot_tmp ) . $endTag;
		echo $startTag . str_repeat( $this->setSeparator, ($setProteinStart_tmp * $this->seqIncrementor) ) . $endTag;
		
		while( !$finishedProteinOutput)
		{
			
			switch( $proteinSpotCount )
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

	function translate( $sequence, $start, $stop )
	{
		$sequence = strtolower( $sequence );
		//echo "start: " . $start . " -- stop: " . $stop . "<BR>";
		//echo "length: " . strlen( $sequence ) . "<BR>";
		if( $start <= 0 || $stop <= 0 || $stop > strlen( $sequence ) || $stop <= $start )
		{
			return "";
		}
		//echo "stage 2------------------";
		$cdna_seq = substr( $sequence, ( $start - 1 ), ( $stop - $start ) );
		//echo "trying cdna_seq : " . $cdna_seq . "<br>";
		$newProteinSeq = "";
		
		$spaced_cdna = chunk_split( $cdna_seq, 3, "|" );
		$aminoacid_ar = explode( "|", $spaced_cdna );
		
		foreach( $aminoacid_ar as $key => $value )
		{
			$newProteinSeq = $newProteinSeq . $this->convertToAA( $value );
		}
		
		//echo "new protein trans: " . $newProteinSeq . "<BR>";
		return $newProteinSeq;
	}
	
	function convertToAA( $threeCodons )
	{
		switch( $threeCodons )
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
