<?php
    	header("Content-type: application/octet-stream");
    	header("Content-Disposition: attachment; filename=" . $_REQUEST["filename"] . ".txt");
    	header("Pragma: no-cache");
    	header("Expires: 0");

	// Differentiate between protein or DNA sequence export request
	if ($_REQUEST["insert_restriction_map"])
	{
		$content=$_REQUEST['insert_restriction_map'];
	}
	elseif ($_REQUEST["vector_restriction_map"])
	{
		$content=$_REQUEST['vector_restriction_map'];
	}
	
   	print $content;
?>
