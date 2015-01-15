<?php
/**
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
* @author     Marina Olhovsky <olhosvky@lunenfeld.ca>
* @version    3.1
* @package BugReport
*
* @copyright  2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
/**
* Include/require statements
*/
	include "Classes/MemberLogin_Class.php";
	include "Classes/Member_Class.php";
	include "Classes/Bug_Function_Class.php";	// Aug. 4, 2010

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

	// print header
	outputMainHeader();

	// June 3, 2010
	global $mail_biologist;
	global $mail_programmer;

	$b_func_obj = new Bug_Function_Class();

	if (isset($_SESSION["userinfo"]))
	{
		$currUserName = $_SESSION["userinfo"]->getDescription();
		$currUserID = $_SESSION["userinfo"]->getUserID();

		if (isset($_GET["Req"]))
		{
			?>
			<table style="text-align:justify;" width="770px" bgcolor="#FFFFFF">

				<TR>
					<TD style="padding-top:15px; padding-left:10px; font-weight:bold; white-space:nowrap; color:blue;">
						Thank you!  Your support request has been submitted.
					</TD>
				</TR>

				<TR>
					<TD style="padding-left:10px;">
						The programmer of OpenFreezer will contact you once the issue is resolved.
					</TD>
				</TR>
			</table>
			<?php
		}
		else
		{
			?>
			<table style="text-align:justify;" width="770px" bgcolor="#FFFFFF">
				<!-- Marina: March 4/08: Create a resizeable border -->
	<!--			<tr>
					<td rowspan="100%" style="padding-left:0; padding-right:5px;">
						<div class="resizeable" onMouseOver="resize();"></div>
					</td>
				</tr>-->
	
				<th colspan="3" style="text-align:center; font-weight:bold; color:#635688; font-family: Arial;">Feature Request and Bug Report</th>
	
				<TR>
					<TD style="padding-top:15px; padding-left:5px">
						Please <a href="docs.php">read the documentation</a> for information on the features of OpenFreezer.<BR>
	
						<P>Please submit your support request using the following form:<BR>
						<span style="font-size:9pt; color:red;">Fields marked with a red asterisk (<span style="color:red;">*</span>) are mandatory</span><BR>
	
						<FORM ACTION="<?php echo $cgi_path . "user_request_handler.py"; ?>" onSubmit="return checkModule() && checkIssue();">
							<INPUT type="hidden" ID="curr_username_hidden" NAME="curr_userid" VALUE="<?php echo $currUserID; ?>">
	
							<table width="760px" style="border: 1px solid black;" cellpadding="2" cellspacing="2">
								<TR>
									<TD colspan="2" style="font-size:9pt; padding-left:10px;">Is this a bug report or a feature request?</TD>
								</tr>
	
								<TR>
									<TD style="font-size:9pt; padding-left:10px;"">
										<input type="radio" name="request_type" value="bug" checked>Bug
										<input type="radio" name="request_type" value="feature">Feature
									</TD>
								</TR>
	
								<tr><td colspan="2"></td></tr>
	
								<TR>
									<TD style="padding-left:10px;"><span style="color:red; padding-left:3px;">*</span> Module:</TD>
								</TR>
	
								<TR>
									<TD style="padding-left:12px;">
										<!-- no need to show 'Other' textbox here, keep it simple for now -->
										<select ID="modules_list" name="modules" style="font-size:9pt;">
											<option value="default" selected>-- Select --</option>
											<option value="Add Vector">Add Vector</option>
											<option value="Add Insert">Add Insert</option>
											<option value="Add Stable Cell Line">Add Stable Cell Line</option>
											<option value="Add Other Reagent">Add Other Reagent</option>
											<option value="Add Reagent Type">Add Reagent Type</option>
											<option value="Edit Reagent Type">Edit Reagent Type</option>
											<option value="Delete Reagent Type">Delete Reagent Type</option>
											<option value="Primer Design">Primer Design</option>
											<option value="Search Reagents">Search Reagents</option>
											<option value="Edit Reagent Details">Edit Reagent Details</option>
											<option value="Change Reagent Parents">Change Reagent Parents</option>
											<option value="Delete Reagents">Delete Reagents</option>
											<option value="Clone Request">Clone Request</option>
											<option value="Add Containers">Add Containers</option>
											<option value="Edit Container Details">Edit Container Details</option>
											<option value="Add Container Type">Add Container Type</option>
											<option value="Edit Container Type">Edit Container Type</option>
											<option value="Add Container Size">Add Container Size</option>
											<option value="Add Wells">Add Wells</option>
											<option value="Modify Wells">Modify Wells</option>
											<option value="Delete Wells">Delete Wells</option>
											<option value="Add Project">Add Project</option>
											<option value="Search Project">Search Project</option>
											<option value="Edit Project">Edit Project</option>
											<option value="Delete Project">Delete Project</option>
											<option value="Change Password">Change Password</option>
											<option value="Automated Password Reset">Automated Password Reset</option>
											<option value="other">Other</option>
										</select>
									</TD>
								</TR>
	
								<tr><td colspan="2">&nbsp;</td></tr>
	
								<TR>
									<TD style="padding-left:10px;">
										 <span style="color:red;">*</span> Please describe in detail your issue or feature request:
									</TD>
								</TR>
	
								<TR>
									<td style="padding-left:10px;">
										<TEXTAREA ID="bugDescription" NAME="bug_description" cols="80" rows="10" style="overflow:scroll;"></TEXTAREA>
									</td>
								</TR>
	
								<tr><td colspan="2"></td></tr>
	
								<TR>
									<TD style="padding-left:8px;">
										<INPUT TYPE="submit" NAME="bug_report" VALUE="Submit">
									</TD>
								</TR>
	
								<tr><td colspan="2"></td></tr>
							</table>
						</FORM>
					</TD>
				</TR>
	
				<TR>
					<TD style="padding-top:15px; padding-left:5px">
						<span style="font-size:9pt; margin-top:8px; font-weight:bold; color:red;">List of known bugs:</span><BR><BR>

						<TABLE border="1" frame="box" rules="all" width="100%">
							<TR>
								<TD style="text-align:center; font-weight:bold; text-decoration:none; width:auto; white-space:nowrap;">Request Type</TD>
								<TD style="text-align:center; font-weight:bold; text-decoration:none; width:auto; white-space:nowrap;">Requested By</TD>
								<TD style="text-align:center; font-weight:bold; text-decoration:none; width:auto; white-space:nowrap;">Module</TD>
								<TD style="text-align:center; font-weight:bold; text-decoration:none; width:auto; white-space:nowrap;">Description</TD>
							</TR>
							<?php
								$bugs = $b_func_obj->fetchAllBugs();
	
								foreach ($bugs as $key => $bug)
								{
									echo "<TR>";
										echo "<TD style=\"padding:5px; white-space:nowrap;\">";
											echo $bug->getBugType();
										echo "</TD>";

										echo "<TD style=\"padding:5px; white-space:nowrap;\">";
											echo $bug->getRequestedBy();
										echo "</TD>";

										echo "<TD style=\"padding:5px; white-space:nowrap;\">";
											echo $bug->getModule();
										echo "</TD>";

										echo "<TD style=\"padding:5px; white-space:nowrap;\">";
											echo $bug->getBugDescription();
										echo "</TD>";
									echo "</TR>";
								}
							?>
						</TABLE>
					</TD>
				</TR>
	
				<tr>
					<TD style="padding-top:15px; padding-left:5px">
						<HR>For additional help and support, please contact <a href="mailto:<?php echo $mail_programmer; ?>">Marina Olhovsky</a> or <a href="mailto:<?php echo $mail_biologist; ?>">Karen Colwill</a>.
					</TD>
				</tr>
			</table>
			<?php
		}
	}
	else
	{
		# Disallow documentation download without login
		$docs_disabled = true; 

		?>
		<table style="text-align:justify;" width="770px" bgcolor="#FFFFFF">
			
			<th colspan="3" style="text-align:center; font-weight:bold; color:#635688; font-family: Arial;">Feature Request and Bug Report</th>


			<tr>
				<TD style="padding-top:15px; padding-left:5px">
<!-- 					<P>Please report any bugs or feature requests through the <a href="http://pawsonlab.mshri.on.ca/index.php?option=com_flyspray&Itemid=32">Bug/Feature Tracker</a> on the <A href="http://pawsonlab.mshri.on.ca/index.php">Pawson Lab Home Page</a>. -->

					Please submit all bug reports to <a href="mailto:<?php echo $mail_programmer; ?>">Marina Olhovsky</a>.<BR><BR>

					To request additional system features, please contact <a href="mailto:<?php echo $mail_programmer; ?>">Marina Olhovsky</a> or <a href="mailto:<?php echo $mail_biologist; ?>">Karen Colwill</a>.
				</TD>
			</tr>
		</table>
		<?php
	}

	// footer
	outputMainFooter();
?>