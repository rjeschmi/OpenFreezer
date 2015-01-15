<?php
/**
* Search page
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
* @package Search
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* Include/require statements
*/
	include "Classes/MemberLogin_Class.php";
	include "Classes/Member_Class.php";
	include "Classes/Session_Var_Class.php";
	include "Classes/generalFunc_Class.php";
	include "DatabaseConn.php";
	
	session_start();
	
	$loginBlock = new MemberLogin_Class( );  
	$loginBlock->loginCheck( $_POST );
	
	header("Cache-control: private"); //IE 6 Fix 

	include "HeaderFunctions.php";
	include "Project/ProjectFunctions.php";		# july 16/07 - needs to be declared before searchFunctions b/c it's used there
	
	include "Search/searchFunctions.php";
	include "Search/searchOutput_reagent.php";
	
	include "Classes/StopWatch.php";
	include "Classes/ColFunctOutputer.php";
	
	include "Reagent/Reagent_Function_Class.php";
	include "Reagent/Reagent_Background_Class.php";

	include "Location/Location_Output_Class.php";	// Feb. 16/10 - want to be able to check preps and disable Delete checkbox
	include "Location/Location_Funct_Class.php";    // April 27, 2011 

	outputMainHeader();
	
	global $Const_Table_Width;

	?>
	<!--<table width=<?php /*echo $Const_Table_Width; */?> border=0 class=nav>
		<tr>	
			<td>
				<?php
// 					outputNavBar();
			
				?>
			</td>
		</tr>
	</table>-->

	<table height="100%">
		<table border="0" width="750px">
		<?php
		
		if (isset($_SESSION["userinfo"]))
		{
			if ($loginBlock->verifyPermissions( basename( $_SERVER['PHP_SELF'] ), $_SESSION['userinfo']->getUserID( ) ) ) 
			{
				$sessionChecker = new Session_Var_Class();
				$sessionChecker->checkSession_all();
				unset( $sessionChecker );
				?>	
				<tr>
					<?php
					if( isset( $_GET["View"]  ) )
					{
						outputSubMenu($_GET["View"]);	// common to all, default
					}
					?>
				</tr>

				<tr>
					<td>
					<?php
						if ($_GET["View"] == "1")
						{
							if (isset($_POST["SearchArea"]) && isset($_POST["Keyword"]))
							{
								$searchCategory = $_POST["SearchArea"];
								$keyword = trim($_POST["Keyword"]);
								$_SESSION["modifying_view"] = "False";
								
								$clockers = new StopWatch;
								$clockers->startStopWatch();

								$numHits = searchOutputResults($searchCategory, $keyword);	// update June 5/09

								$clockers->stopStopWatch();
								$clockers->printStopWatch_verbose();
								unset( $clockers );
	
// print "category " . $_SESSION["userinfo"]->getCategory();
								// moved here March 19/10 from searchOutput_reagent.php
								echo "<BR><P>Number of hits: " . $numHits . "<BR>";

								// June 5/09
								if ($numHits > 0)
								{
									$currUserName = $_SESSION["userinfo"]->getDescription();
									
									if ($_SESSION["userinfo"]->getCategory() != $_SESSION["userCategoryNames"]['Reader'])
									{
										?><P><INPUT TYPE="BUTTON" onClick="deleteReagentsFromSearch('ReagentCheckBox');" VALUE="Delete Selected Reagents"><BR><?php
									}
// 									else
// 									{
										?>
<!-- <P><INPUT TYPE="BUTTON" DISABLED VALUE="Delete Selected Reagents"><BR> -->
<?php
// 									}
								}
							}
							else if (isset($_GET["SearchArea"]) && isset($_GET["Keyword"]))
							{
								// Added April 11/07: Allowing to link to search results from outside LIMS (particularly from MS Map)
								// Can only be done through GET method - incorporating here at Adrian's request
								// Requires exact search term match
								$searchCategory = trim($_GET["SearchArea"]);
								$keyword = trim($_GET["Keyword"]);

								$_SESSION["modifying_view"] = "False";
							
								$clockers = new StopWatch;
								$clockers->startStopWatch();
// 11/04/07							searchOutputResults();		// in searchOutput_reagent.php
								$numHits = searchOutputResults($searchCategory, $keyword);	// update June 5/09
								$clockers->stopStopWatch();
								$clockers->printStopWatch_verbose();
								unset( $clockers );

								// June 5/09
								if ($numHits > 0)
								{
									$currUserName = $_SESSION["userinfo"]->getDescription();
								
									if ($currUserName == 'Administrator')
									{
										?><P><INPUT TYPE="BUTTON" onClick="deleteReagentsFromSearch('ReagentCheckBox');" VALUE="Delete Selected Reagents"><BR><?php
									}
								}
							}
						}
						elseif( $_GET["View"] == "2" )
						{
							if( !isset( $_SESSION["modifying_view"] ) )
							{
								$_SESSION["modifying_view"] = "False";
							}
							
							if( isset( $_POST["change_state"] ) )
							{
								if( $_POST["change_state"] == "Modify" )
								{
									$_SESSION["modifying_view"] = "True";
								}
								elseif( $_POST["change_state"] == "Save" )
								{
									$_SESSION["modifying_view"] = "False";

									if (isset($_POST["reagentChanges"]) && isset($_SESSION["oldvalues_view"]))
									{
										submit_changes( $_POST["reagentChanges"], $_SESSION["oldvalues_view"], $_GET["rid"] );
									}	
								}
							}
	
							$clockers = new StopWatch;
							$clockers->startStopWatch();
							outputDetailedView_main( $_GET["rid"] );
							$clockers->stopStopWatch();
							$clockers->printStopWatch_verbose();
							
							unset( $clockers );
						}
						?>
					</td>
				</tr>
			</table>
			<?php
		
// 			outputMainFooter();
		}
		else
		{
			/* May 30/07, Marina
			echo "<tr><td>";
			echo "Restricted Access! Please log in!";
			echo "</td></tr>";
			*/
			
			// May 30/07, Marina
			echo "<tr><td class=\"warning\">";
			echo "You are not authorized to view this page.  Please contact the site administrator.";
			echo "</td></tr>";

			?></table><?php
// 			outputMainFooter();
		}
	}
	else
	{
		echo "<tr><td class=\"warning\">";
		echo "Please log in to access this page.";
		echo "</td></tr>";
		?></TABLE><?php
		mysql_close($conn);
	}
	?>
		</table>
	</table>
<?php
	outputMainFooter();
?>
