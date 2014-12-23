<?php
/**
* Main page
*
* PHP versions 4 and 5
*
* Copyright (c) 2005-2011 Mount Sinai Hospital, Toronto, Ontario
*
* LICENSE:
*
* OpenFreezer is free software; you can redistribute it
* and/or modify it under the terms of the GNU General
* Public License as published by the Free Software Foundation;
* either version 3 of the License, or (at your option) any
* later version.
*
* OpenFreezer is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
* General Public License for more details.
*
* You should have received a copy of the GNU General Public
* License along with OpenFreezer.  If not, see <http://www.gnu.org/licenses/>.
*
* @author John Paul Lee @version 2005
*
* @author     Marina Olhovsky <olhosvky@lunenfeld.ca>
* @version    3.1
* @package All
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* Include/require statements
*/
	include "DatabaseConn.php";

	include "Classes/MemberLogin_Class.php";
	include "Classes/Member_Class.php";
	include "Classes/Session_Var_Class.php";

	session_start();

	$loginBlock = new MemberLogin_Class();
	$loginBlock->loginCheck($_POST);

	header("Cache-control: private"); //IE 6 Fix 

	include "HeaderFunctions.php";
	//include "functions.php";
	include_once "Reagent/Reagent_Function_Class.php";	// July 19, 2010
	//include "Location/LocationFunct.php";

	// Global Session Variables.
	// FIX IT: Should probably isolate this better
	$sessionChecker = new Session_Var_Class();
	$sessionChecker->checkSession_all();
	unset( $sessionChecker );

	$rfunc_obj = new Reagent_Function_Class();
	$rcount = $rfunc_obj->computeTotalReagents();

	outputMainHeader();
	
	// June 3, 2010
	global $mail_biologist;
	global $mail_programmer;

	?>
	<TABLE height="99%">
		<TABLE width="99%" style="text-align:justify; white-space:normal;">
			<!-- Marina: March 4/08: Create a resizeable border -->
			<!--<tr>
				<td rowspan="100%" style="padding-left:0; padding-right:5px;">
					<div class="resizeable" onMouseOver="resize();"></div>
				</td>
			</tr>-->

			<TR>
				<td colspan="3">
					<P style="padding-left:10px;">
						<span style="color:#000000; font-weight:bold">OpenFreezer Laboratory Reagent Tracking and Workflow Management System</span> comprises a variety of software tools designed to provide support to wetlab scientists.  At its core, the system tracks information on all reagents within the laboratory, such as types of reagents, specific properties of each reagent (including sequences), all isolates and preps of a reagent, and where the preps of reagents are stored in the laboratory.<BR><BR>Currently, the repository contains <b><?php echo number_format($rcount); ?></b> reagents.<BR><BR>

						<SPAN style="padding-top:5px; font-weight:bold; font-size:9pt;">OpenFreezer leaders:</SPAN><BR><BR>

						<SPAN style="padding-left:10px;">Programmer: <a href="mailto:olhovsky@lunenfeld.ca">Marina Olhovsky</a><BR></span>
						<SPAN style="padding-left:10px;">Biologist: <a href="mailto:colwill@lunenfeld.ca">Karen Colwill</a><BR><BR></span>

						<P style="padding-left:10px;"><SPAN style="border: 1px solid black; width:850px; padding:10px; font-weight:bold; font-size:9pt;">Dedicated in loving memory of <a href="dedication.php">Larisa Olhovsky</a></SPAN><BR><BR>
						
						<P style="padding-left:10px; padding-top:5px;">Please report any errors with the source code through the Bug Tracker at <a href="https://github.com/rjeschmi/OpenFreezer/issues">GitHub Issues Page</a>.  All other questions should be reported to the local administrator.
				</td>
			</TR>
		</TABLE>
	</TABLE>
	<?php 

	// print footer
	outputMainFooter();
?>
