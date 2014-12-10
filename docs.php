<?php
/**
 * Documentation page
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * PHP versions 4 and 5
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 *
 * This file is part of OpenFreezer (TM)
 *
 * OpenFreezer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * at your option) any later version.

 * OpenFreezer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with OpenFreezer.  If not, see <http://www.gnu.org/licenses/>.
 *
*/
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

	if (!isset($_SESSION["userinfo"]))
	{
		# Disallow documentation download without login
		$docs_disabled = true; 
	}
	else
	{
	}

	outputMainHeader();
	?>
<!--	<table width="100%" border="0" class="nav">
		<tr>		
			<td>
				<?php
// 					outputNavBar();
				?>
			</td>

			<td>
				<?php 
//					$loginBlock->printLoginInfo( "" );
				?>
			</td>
		</tr>
	</table>-->
	
<!-- 	<div class="main"> -->
	
				<form name="docs" method="POST" action="save.php" ENCTYPE="multipart/form-data">
					<table width="100%">
						<!-- Marina: March 4/08: Create a resizeable border -->
						<!--<tr>
							<td rowspan="100%" style="padding-left:0; padding-right:5px;">
								<div class="resizeable" onMouseOver="resize();"></div>
							</td>
						</tr>-->
			
						<TH style="text-align:left;">
							<BR><BR>Please refer to the OpenFreezer User Manual at <a href="http://openfreezer.org">http://openfreezer.org</a><BR><HR>
						</TH>

						<TR>
							<td colspan="3">
								<a href="copyright.php">Copyright Notice and Disclaimer</a>&nbsp;&nbsp;							
									<a href="Docs/Copyright.doc"><IMG SRC="pictures/disk_small.gif" WIDTH="15" HEIGHT="15" BORDER="0" ALT="doc" style="cursor:pointer; vertical-align:bottom"></a>
								<P>
							</td>
						</tr>
					</table>

					<table width="770px">
						<tr>
							<td style="border-top:1px solid black; padding-top:5px;">
								<P style="text-align:center; font-weight:bold">OpenFreezer Contributors</P>

								<P style="text-decoration:none; font-weight:bold">Design and Implementation Team:</P>

								<a href="mailto:<?php echo $mail_programmer; ?>">Marina Olhovsky</a>, <a href="mailto:<?php echo $mail_biologist; ?>">Karen Colwill</a>, John Paul Lee, <a href="http://linding.eu">Rune Linding</a>, Clark Wells, <a href="http://www.biodesign.asu.edu/research/research-centers/personalized-diagnostics">Jin Gyoon Park</a>, <a href="mailto:pasculescu@lunenfeld.ca">Adrian Pasculescu</a><BR><BR>

								<P style="text-decoration:none; font-weight:bold">Testing Team:</P>
								<a href="mailto:williton@lunenfeld.ca">Kelly Williton</a>, <a href="mailto:anna@lunenfeld.ca">Anna Yue Dai</a>, <a href="mailto:goudreault@lunenfeld.ca">Marilyn Goudreault</a>, Kadija Hersi<BR><BR>

								<P style="text-decoration:none; font-weight:bold">Technical Support:</P>
								<a href="mailto:mohammad@lunenfeld.ca">Naveed Mohammad</a><BR><BR>

								<P style="text-decoration:none; font-weight:bold">Principal Investigator:</P>
								<a href="mailto:PAWSON@lunenfeld.ca">Tony Pawson</a><BR><BR>
							</td>
						</tr>
					</table>
				</form>
			</td>
		</tr>
	</table>
	<?php

	outputMainFooter();
?>
