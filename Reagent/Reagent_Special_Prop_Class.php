<?php

class Reagent_Special_Prop_Class
{
	var $special_propID_ar = array();
	
	function Reagent_Special_Prop_Class()
	{
		$this->special_propID_ar[] = $_SESSION["ReagentProp_Name_ID"]["Sequence"];
		$this->special_propID_ar[] = $_SESSION["ReagentProp_Name_ID"]["Comments"];
		$this->special_propID_ar[] = $_SESSION["ReagentProp_Name_ID"]["Description"];
		//$special_propID_set[] = $_SESSION["ReagentProp_Name_ID"]["Owners"];
	}
	
	function isSpecialProperty( $propID_tmp )
	{
		if( in_array( $propID_tmp, $this->special_propID_ar ) )
		{
			return true;
		}
		
		return false;
	}
	
	function input_property( $rid, $pid, $value )
	{
		switch( $propertyID )
		{
			case $_SESSION["ReagentProp_Name_ID"]["Sequence"]:
					$new_value = $this->input_sequence( $value );
					
					mysql_query( "INSERT INTO `ReagentPropList_tbl` (`propListID`, `reagentID`, `propertyID`, `propertyValue`, `labID`, `status`) "
						. "VALUES ('', '" . $rid . "','" . $pid . "','" 
						. addslashes( $value ). "','" . 1 . "','ACTIVE')", $conn )
						or die( "FAILURE IN: Reagent_Creator_Class.input_property(1): " . mysql_error() );
						
					return mysql_insert_id( $conn );
				break;
			case $_SESSION["ReagentProp_Name_ID"]["Comments"]:
			case $_SESSION["ReagentProp_Name_ID"]["Description"]:
			
					$new_value = $this->input_com_desc( $value );
			
					mysql_query( "INSERT INTO `ReagentPropList_tbl` (`propListID`, `reagentID`, `propertyID`, `propertyValue`, `labID`, `status`) "
						. "VALUES ('', '" . $rid . "','" . $pid . "','" 
						. addslashes( $value ). "','" . 1 . "','ACTIVE')", $conn )
						or die( "FAILURE IN: Reagent_Creator_Class.input_property(2): " . mysql_error() );
						
					return mysql_insert_id( $conn );
				break;
			default:
				break;
		}
	}
	
	function input_sequence( $value )
	{
		global $conn;
		
		$gfunc_obj = new generalFunc_class();
		
		$sequence_type_rs = mysql_query( "SELECT `seqTypeID` FROM `SequenceType_tbl` WHERE `seqTypeName`='" . "cDNA" . "' "
									."AND `status`='ACTIVE'", $conn )
									or die( "FAILURE IN: Reagent_special_prop_class.input_sequence(1): " . mysql_error() );
		
		$sequence_type_ar = mysql_fetch_array( $sequence_type_rs, MYSQL_ASSOC );
		
		$seqtype = $sequence_type_ar["seqTypeID"];
		
		mysql_free_result( $sequence_type_rs );
		unset( $sequence_type_rs, $sequence_type_ar );
		
		$new_value = $gfunc_obj->remove_whitespaces( $value );
		
		unset( $gfunc_obj );
		
		mysql_query( "INSERT INTO `Sequences_tbl` (`seqID`,`seqTypeID`,`sequence`,`length`,`labID`,`status`) "
				. "VALUES ('','" . $seqtype . "','" . $new_value . "','" . strlen( $new_value ) . "','" . 1 . "', 'ACTIVE)", $conn )
				or die( "FAILURE IN: Reagent_special_prop_class.input_sequence(2): " . mysql_error() );
				
		return mysql_insert_id( $conn );
									
	}
	
	function input_com_desc( $value )
	{
		global $conn;
		
		$commentLink_rs = mysql_query( "SELECT `commentLinkID` FROM `CommentLink_tbl` WHERE `link`='" . "Reagent" . "' AND `status`='ACTIVE'", $conn ) or die( "FAILURE IN Reagent_Special_Prop_Class.input_com_desc(1): " . mysql_error() );

		$commentLink_ar = mysql_fetch_array( $commentLink_rs, MYSQL_ASSOC );
		
		$link = $commentLink_ar["commentLinkID"];
		
		mysql_free_result( $commentLink_rs );
		unset( $commentLink_rs, $commentLink_ar );
		
		mysql_query( "INSERT INTO `GeneralComments_tbl` (`commentID`, `commentLinkID`, `comment`, `labID`, `status`) "
				. "VALUES ('', '" . $link . "', '" . addslashes( $value ) . "', '" . 1 . "', 'ACTIVE')", $conn )
				or die( "FAILURE IN Reagent_Special_Prop_Class.input_com_desc(2): " . mysql_error() );
				
		return mysql_insert_id( $conn );
	}

// 	// Feb. 11/08, Marina: Check if the value of the special property identified by $propID is empty
// 	// In most cases, $propValue is an INT value of an ID column referencing a special property table (e.g. commentID for GeneralComments_tbl)
// 	// This function returns the text value of the property (e.g. the value of 'comment' column in GeneralComments_tbl)
// 	function isEmptyValue($propID, $propValue)
// 	{
// 		global $conn;
// 		
// 		switch ($propID)
// 		{
// 			case $_SESSION["ReagentProp_Name_ID"]["description"]:
// 				$comment_rs = mysql_query("SELECT comment FROM GeneralComments_tbl WHERE commentID='" . $propValue . "' AND `status`='ACTIVE'", $conn) or die("Could not select description value: " . mysql_error());
// 		
// 				while ($comment_ar = mysql_fetch_array($comment_rs, MYSQL_ASSOC))
// 				{
// 					$comment = $comment_ar["comment"];
// 
// 					return strlen($comment) == 0;
// 				}
// 				
// 			break;
// 		}
// 	}
}
?>