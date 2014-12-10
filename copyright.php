<?php
/**
 * Copyright
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 *
 * This file is part of OpenFreezer
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

	// print header and side menu
	outputMainHeader();
	
	?>
	<table bgcolor="#FFFFE0" height="100%">
		<table width="770px" style="font-size:12pt; padding:3px; text-align:justify" cellpadding="2" cellspacing="2">
			<th style="text-align:center">OpenFreezer Terms and Conditions</th>

			<tr>
				<td style="font-weight:bold; padding-top:10px;">
					License
				</td>
			</tr>
	
			<tr>
				<td style="padding-top:10px;">
					OpenFreezer is distributed under the GNU License, Version 3; you may not use this application except in compliance with the License. You may obtain a copy of the License at <a href="www.gnu.org/licenses">www.gnu.org/licenses</a>.
				</td>
			</tr>

			<tr>
				<td style="padding-top:10px; font-weight:bold;">
					Third Party Links
				</td>
			</tr>

			<tr>
				<td style="padding-top:10px;">
					This software package may contain third-party owned content or information, and may also include links or references to other web sites maintained by third parties over whom Mount Sinai Hospital has no control. Mount Sinai Hospital does not endorse, sponsor, recommend, warrant, guarantee or otherwise accept any responsibility for such third party content, information or web sites. Access to any third-party owned content or information or web site is at your own risk, and Mount Sinai Hospital is not responsible for the accuracy or reliability of any information, data, opinions, advice or statements made in such content or information or on such sites.
				</td>
			</tr>

			<tr>
				<td style="font-weight:bold; padding-top:10px;">
					Disclaimer of Warranty; Limitation of Liability
				</td>
			</tr>

			<tr>
				<td style="padding-top:10px;">
					MOUNT SINAI HOSPITAL DISCLAIMS ALL EXPRESS AND IMPLIED WARRANTIES WITH REGARD TO THIS SOFTWARE PACKAGE, THE INFORMATION, SERVICES AND MATERIALS DESCRIBED OR CONTAINED IN THIS SOFTWARE PACKAGE, INCLUDING WITHOUT LIMITATION ANY IMPLIED WARRANTIES OF MERCHANTIBILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT.  YOUR ACCESS TO AND USE OF THIS SOFTWARE PACKAGE ARE AT YOUR OWN RISK. INFORMATION, SERVICES AND MATERIALS CONTAINED HEREIN MAY NOT BE ERROR-FREE. MOUNT SINAI HOSPITAL IS NOT LIABLE OR RESPONSIBLE FOR THE ACCURACY, CURRENCY, COMPLETENESS OR USEFULNESS OF ANY INFORMATION, SERVICES AND MATERIALS PROVIDED IN THIS SOFTWARE PACKAGE. IN ADDITION, MOUNT SINAI HOSPITAL SHALL ALSO NOT BE LIABLE FOR ANY DAMAGES, INCLUDING WITHOUT LIMITATION, DIRECT, INCIDENTAL, CONSEQUENTIAL, AND INDIRECT OR PUNITIVE DAMAGES, ARISING OUT OF ACCESS TO, USE OR INABILITY TO USE THIS SOFTWARE PACKAGE OR SERVICES DESCRIBED HEREIN, OR ANY ERRORS OR OMISSIONS IN THE CONTENT THEREOF. THIS INCLUDES DAMAGES TO, OR FOR ANY VIRUSES THAT MAY INFECT YOUR COMPUTER EQUIPMENT. WITHOUT LIMITING THE FOREGOING, EVERYTHING IN THIS SOFTWARE PACKAGE IS PROVIDED “AS IS” WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESS OR IMPLIED.
				</td>
			</tr>

			<tr>
				<td style="font-weight:bold; padding-top:10px;">
					Indemnification
				</td>
			</tr>

			<tr>
				<td style="padding-top:10px;">
					You agree to indemnify, defend and hold harmless Mount Sinai Hospital, its officers, directors, employees, agents, consultants, suppliers and third party partners from and against all losses, expenses, damages and costs, including reasonable attorneys' fees, resulting from any violation by you of these Terms and Conditions
				</td>
			</tr>

			<tr>
				<td style="font-weight:bold; padding-top:10px;">
					Applicable Laws
				</td>
			</tr>

			<tr>
				<td style="padding-top:10px;">
					These Terms and Conditions and the resolution of any dispute related to these Terms and Conditions shall be construed in accordance with the laws of the province of Ontario, Canada, without regard to its conflicts of laws principles. Any legal action or proceeding related to this Software shall be brought exclusively by the federal and provincial courts of the province of Ontario, Canada.
				</td>
			</tr>
		</table>
	</table>
	<?php

	outputMainFooter();
?>
