<?php
    	header("Content-type: application/octet-stream");
    	header("Content-Disposition: attachment; filename=" . $_REQUEST["filename"] . ".gb");
    	header("Pragma: no-cache");
    	header("Expires: 0");

	$gbkContent = $_REQUEST['gbkContent'];	
   	print $gbkContent;
?>
