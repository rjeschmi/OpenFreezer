<?php
/**
 * Standard 'Contact Us' page
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license		http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
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

	// June 3, 2010
	global $mail_programmer;
	global $mail_biologist;

	// print header
	outputMainHeader();
	
	?>	
<!--	<table width="100%" border="0" class="nav">
		<tr>		
			<td>
				<?php
// 					outputNavBar();
				?>
			</td>
		</tr>
	</table>
	
	<div class="main">-->
	<center>
		<table style="text-align:justify; padding:10px" width="100%">
			<!-- Marina: March 4/08: Create a resizeable border -->
			<!--<tr>
				<td rowspan="100%" style="padding-left:0; padding-right:5px;">
					<div class="resizeable" onMouseOver="resize();"></div>
				</td>
			</tr>-->

			<th colspan="3" style="text-align:center; font-weight:bold; color:#0000ff">OpenFreezer Team Leads</th>

			<TR><TD>&nbsp;</td></tr>

			<tr>
				<td width="135px">Programmer:</td>
				<td width="180px">Marina Olhovsky</td>
				<td><a href="mailto:<?php echo $mail_programmer; ?>"><?php echo $mail_programmer; ?></a></td>
			</tr>

			<tr>
				<td>Biologist:</td>
				<td>Karen Colwill</td>
				<td><a href="mailto:<?php echo $mail_biologist; ?>"><?php echo $mail_biologist; ?></a></td>
			</tr>
		</table>
	</center>
	<?php

	// footer
	outputMainFooter();	
?>
