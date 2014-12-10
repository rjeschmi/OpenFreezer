<?php
// CHANGE THE FOLLOWING VARIABLE VALUES TO YOUR DATABASE CONNECTION PARAMETERS
$databaseip = "Your MySQL server IP";
$databasename = "my_openfreezer_db";
$name = "openfreezer_www";
$pw = "MySQL password for openfreezer_www";

// September 11, 2007, Marina:
global $hostname;
$hostname = "Your OpenFreezer URL, e.g. http://www.my_openfreezer.org/";

// added email addresses on June 3, 2010 - easy to change
global $mail_programmer;
$mail_programmer = "olhovsky@lunenfeld.ca";

global $mail_biologist;
$mail_biologist = "colwill@lunenfeld.ca";

global $conn;

/**
 * A constant used to define table width on User module views
 * @global INT $Const_Table_Width
*/
global $Const_Table_Width;

// May 5/06, Marina -- The minimum group ID for MGC clones in the system
// (i.e. Vectors and Inserts from the MGC set have LIMS IDs V5000+ and I50000+)
global $MGC_Start;

$MGC_Start = 50000;	// 5/5/06, Marina

$Const_Table_Width = "700px";

// August 20, 2007, Marina: CHANGED mysql_pconnect to mysql_connect - DON'T USE PERSISTENT CONNECTIONS, THEY OVERLOAD THE DATABASE AND SLOW DOWN SEARCH!!!!!!!!!
$conn = mysql_connect($databaseip, $name, $pw) or die("Could not connect: " . mysql_error());

mysql_select_db($databasename, $conn) or die("Could not select database: " . mysql_error());

// Added for CGI script execution
global $cgi_path;
$cgi_path = $hostname . "cgi/";		
?>
