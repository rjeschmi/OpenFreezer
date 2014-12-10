<?php
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=cloneOrder.csv");
	header("Pragma: no-cache");
	header("Expires: 0");
	
// 	print_r($_POST);

	print $_POST["outputContent"];
?>