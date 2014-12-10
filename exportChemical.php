<?php
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=chemicals.tsv");
	header("Pragma: no-cache");
	header("Expires: 0");
	
 	//print_r($_REQUEST);

	print $_REQUEST["outputContent"];
?>
