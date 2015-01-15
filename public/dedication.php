<?php
	include "Classes/MemberLogin_Class.php";
	include "Classes/Member_Class.php";
	include "Classes/Session_Var_Class.php";
	include "DatabaseConn.php";

	session_start();

	$loginBlock = new MemberLogin_Class();
	$loginBlock->loginCheck($_POST);

	header("Cache-control: private"); //IE 6 Fix 

	include "HeaderFunctions.php";

//	$link = mysql_pconnect($databaseip, $name, $pw) or die("Could not connect: " . mysql_error());
//	mysql_select_db($databasename) or die("Could not select database");

	// Global Session Variables.
	// FIX IT: Should probably isolate this better
	$sessionChecker = new Session_Var_Class();
	$sessionChecker->checkSession_all();
	unset( $sessionChecker );

	outputMainHeader();

	?>
	<P style="margin-top:3px;">
	<TABLE style="padding-left:10px; padding-right:10px; padding-top:5px; padding-bottom:20px;">
		<TH style="font-size:14pt; color:black; text-align:center; padding-right:105px;">LARISA OLHOVSKY</TH>

		<TR>
			<TD style="padding-top:10px; text-align:center; padding-right:105px;">
				<IMG SRC="pictures/Mom.jpg" width="290" height="360">
				<BR><P><span style="font-weight:bold;">August 3, 1955 -- March 18, 2006</span>
			</TD>
		</TR>

		<TR>
			<TD style="width:80%; padding-left:1%; padding-right:12px; padding-top:15px; text-align:justify;">
				Cancer claimed the life of <b>Larisa Olhovsky</b> too fast too soon.  This software is dedicated to her by her daughter, <a href="mailto:olhovsky@lunenfeld.ca">Marina Olhovsky</a>, the programmer of OpenFreezer, as a guiding light for biological researchers on their path towards winning the battle against cancer and other diseases.
			</TD>
		</TR>

		<TR>
			<TD style="padding-top:15px; padding-left:10px; margin-left:10%; text-align:justify; font-weight:bold;">
				<i>LEST WE FORGET</i>
			</TD>
		</TR>

		<TR>
			<TD style="padding-top:15px; padding-left:10px; text-align:justify; margin-left:10%; font-weight:bold;">
				<IMG SRC="pictures/roses2.gif" width="" height="">
			</TD>
		</TR>
	</TABLE>
	<?php

	outputMainFooter();
?>