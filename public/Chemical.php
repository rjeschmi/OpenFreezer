<?php
/**
* PHP versions 4 and 5
*
* Copyright (c) 2005-2010 Pawson Laboratory, All Rights Reserved
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
* @package Chemical
*
* @copyright  2005-2010 Pawson Laboratory
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
* Include/require statements
*/
	include_once "Classes/MemberLogin_Class.php";
	include_once "Classes/Chemical_Class.php";

	include_once "Project/ProjectFunctions.php";	// Aug 10/07; it includes Member_Class so don't need to redeclare it here

	include_once "Classes/Session_Var_Class.php";
	include_once "Classes/generalFunc_Class.php";
	
	include "Classes/StopWatch.php";

	// Jan. 23/08: Order reagents
	include "Location/Location_Funct_Class.php";
	include "Classes/Order_Class.php";
	
	include_once "DatabaseConn.php";
	include "HeaderFunctions.php";

	/**
	 * A constant used to define table width on User module views
	 * @global INT $Const_Table_Width
	*/
	global $Const_Table_Width;
	$colspan_const = 0;

	header("Cache-control: private"); //IE 6 Fix 

	session_start();

	$loginBlock = new MemberLogin_Class( );  
	$loginBlock->loginCheck( $_POST );

	// print header	
	outputMainHeader();

	?>
	<div class="main">
		<table border="0" width="100%">
			<?php
			if (isset($_SESSION["userinfo"]))
			{
				if( $loginBlock->verifyPermissions( basename( $_SERVER["PHP_SELF"] ), $_SESSION["userinfo"]->getUserID( ) ) ) 
				{
					$sessionChecker = new Session_Var_Class();
					$sessionChecker->checkSession_all(); 	// july 17/07
					unset($sessionChecker);

					$currUserID = $_SESSION["userinfo"]->getUserID();
					$currUserName = $_SESSION["userinfo"]->getDescription();
					?>
					<tr>
						<td>
							<?php
							if ($_GET["View"] == "1")
							{
								if (isset($_POST["search_chemical"]))
								{
									$results = findChemical();
									printChemicalSearchResults($results);
								}
								else
								{
									printSearchForm();
								}
							}
							else if ($_GET["View"] == "2")
							{
								// print_r($_POST);
								if (isset($_POST["add_chemical"]))
								{
									//if (addChemical())
										//echo "<SPAN style=\"color:#0000FF; font-weight:bold; font-family:Times; font-size:11pt;\">Chemical added successfully.</SPAN>";

									$chemID = addChemical();
//echo $chemID;
								//	print_r($_POST);

									if ($chemID > 0)
										printChemicalDetails($chemID);
										//print_r($_POST);
								}
								else
								{
									printCreationForm();
								}
							}
							else if ($_GET["View"] == "3")
							{
								if (isset($_GET["chemicalID"]))
								{
									printChemicalDetails($_GET["chemicalID"]);
								}
							}
							?>
						</td>
					</tr>
					<?php
				}
				else
				{
					echo "<tr><td class=\"warning\">";
					echo "Please log in to access this page.";
					echo "</td></tr>";
				}
			}
			else
			{
				echo "<tr><td class=\"warning\">";
				echo "Please log in to access this page.";
				echo "</td></tr>";
			}
		?>
		</table>
	</div>
	<?php

	outputMainFooter();
	
	

/*********************************************************************
	FUNCTIONS
*********************************************************************/

/**
* Output the search form
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
*/
function printSearchForm()
{
	global $cgi_path;
	global $conn;

	// name	
	if (isset($_POST["name_criteria"]))
	{
		$name_display = "table-row";
		$name_checked = "checked";
	}
	else
	{
		$name_display = "none";
		$name_checked = "";
	}
	
	// supplier
	if (isset($_POST["supplier_criteria"]))
	{
		$supplier_display = "table-row";
		$supplier_checked = "checked";
		$post_supplier = $_POST["supplier"];
	}
	else
	{
		$supplier_display = "none";
		$supplier_checked = "";
		$post_supplier = "";
	}
	
	
	// CAS no.
	if (isset($_POST["cas_criteria"]))
	{
		$cas_display = "table-row";
		$cas_checked = "checked";
		$post_cas = $_POST["cas_no"];
	}
	else
	{
		$cas_display = "none";
		$cas_checked = "";
		$post_cas = "";
	}

	?>
		<FORM NAME="search_chemicals_form" METHOD="POST" ACTION="<?php echo $_SERVER["PHP_SELF"] . "?View=1"; ?>">

			<TABLE width="100%" cellpadding="5" cellspacing="5" border="0">
			
				<TH colspan="3" style="color:#0000FF; border-top:1px groove black; border-bottom: 1px groove black; padding-top: 10px; padding-top:5px;">
					SEARCH CHEMICALS
				</TH>

				<TR>
					<TD colspan="3">
						<P><P>Search chemical by:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
						<SELECT ID="search_criteria_list" NAME="search_criteria" onChange="showChemicalLocationList(); showSafetySearchForm();">
							<OPTION NAME="default" VALUE="default">All</OPTION>
							<OPTION NAME="name_criteria">Chemical Name</OPTION>
							<OPTION NAME="cas_criteria">CAS Number</OPTION>
							<OPTION NAME="supplier_criteria">Supplier</OPTION>
							<OPTION NAME="location_criteria">Location</OPTION>
							<OPTION NAME="safety_criteria">Safety</OPTION>
						</SELECT>
					</TD>
				</TR>


				<TR>
					<TD ID="chem_search_caption" style="width:120px; white-space:nowrap; vertical-align:top;">
						Enter search keyword:
					</TD>

					<TD>					
						<INPUT TYPE="TEXT" SIZE="40" ID="chem_search_keyword" NAME="chemSearchKeyword" VALUE="<?php echo $_POST["chemSearchKeyword"]; ?>">

						<SELECT ID="chemical_locations" NAME="chemLocType" style="font-size:10pt; display:none">
							<OPTION VALUE="default"> -- Select Location -- </OPTION>
						<?php
							$locations_rs = mysql_query("SELECT chemLocNameID, chemicalLocationName FROM ChemicalLocationNames_tbl ORDER BY chemicalLocationName", $conn) or die("Could not select chemical location: " . mysql_error());
	
							while ($locations_ar = mysql_fetch_array($locations_rs, MYSQL_ASSOC))
							{
								$chemLocID = $locations_ar["chemLocNameID"];
								$chemLocName = $locations_ar["chemicalLocationName"];
							
								echo "<OPTION VALUE=\"" . $chemLocID . "\">" . $chemLocName . "</OPTION>";
							}
						?>
						</SELECT>
					
						<SELECT ID="safety_search" name="safety[]" SIZE="15" style="display:none;" MULTIPLE>
							<OPTION VALUE="A - Compressed Gas">A - Compressed Gas</OPTION>
							<OPTION VALUE="B1 - Flammable Gas">B1 - Flammable Gas</OPTION>
							<OPTION VALUE="B2 - Flammable Liquid">B2 - Flammable Liquid</OPTION>
							<OPTION VALUE="B3 - Combustible Liquid">B3 - Combustible Liquid</OPTION>
							<OPTION VALUE="B4 - Flammable Solid">B4 - Flammable Solid</OPTION>
							<OPTION VALUE="B5 - Flammable Aerosol">B5 - Flammable Aerosol</OPTION>
							<OPTION VALUE="B6 - Reactive Flammable">B6 - Reactive Flammable</OPTION>
							<OPTION VALUE="C - Oxidizing">C - Oxidizing</OPTION>
							<OPTION VALUE="D1A - Immediate Very Toxic">D1A - Immediate, Very Toxic</OPTION>
							<OPTION VALUE="D1B - Immediate Toxic">D1B - Immediate, Toxic</OPTION>
							<OPTION VALUE="D2A - Other Very Toxic">D2A - Other, Very Toxic</OPTION>
							<OPTION VALUE="D2B - Other Toxic">D2B - Other, Toxic</OPTION>
							<OPTION VALUE="E - Corrosive">E - Corrosive</OPTION>
							<OPTION VALUE="F - Dangerously Reactive">F - Dangerously Reactive</OPTION>
							<OPTION VALUE="Non-Controlled">Non-Controlled</OPTION>
						</SELECT>
					</TD>
				</TR>

				<TR>
					<TD colspan="3">
						<INPUT TYPE="SUBMIT" NAME="search_chemical" VALUE="Search">
					</TD>
				</TR>
			</TABLE>
		</FORM>
	<?php
}

/**
* Perform the search function based on the search form input (search criteria selection and keyword/s)
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
*
* @return resultset
*/
function findChemical()
{
	global $conn;
	
	$chemResults = array();
	$chemical = null;
	
	$keyword = $_POST["chemSearchKeyword"];

	$chemDescription = "";
	$locationDescr = "";

	// Determine if any search attributes have been set
	switch ($_POST["search_criteria"])
	{
		case 'Safety':
		break;

		case 'Chemical Name':
			$keyword = $_POST["chemSearchKeyword"];
			
			$query = "SELECT c.chemListID, cn.chemicalName, cn.CAS_No, c.Quantity, g.comment as supplier, g2.comment as Comment, c.Safety as Safety, c.MSDS, g4.comment as locationComments, cln.chemicalLocationName as chemLocName, t.chemLocTypeName FROM ChemicalNames_tbl cn, Chemicals_tbl c, ChemicalLocations_tbl l, ChemicalLocationNames_tbl cln, ChemicalLocationTypes_tbl t, GeneralComments_tbl g, GeneralComments_tbl g2, GeneralComments_tbl g4 WHERE  l.chemLocName=cln.chemLocNameID AND cn.chemicalName LIKE '%" . $keyword . "%' AND c.chemicalID=cn.chemicalID AND g2.commentID=c.Comments AND g4.commentID=l.chemLocComments AND c.chemicalLocation=l.chemLocID AND l.chemLocType=t.chemLocTypeID AND c.Supplier=g.commentID AND c.status='ACTIVE'";
		break;

		case 'CAS Number':
			$keyword = $_POST["chemSearchKeyword"];
			
			$query = "SELECT c.chemListID, cn.chemicalName, cn.CAS_No, c.Quantity, g.comment as supplier, g2.comment as Comment, c.Safety as Safety, c.MSDS, g4.comment as locationComments, cln.chemicalLocationName as chemLocName, t.chemLocTypeName FROM ChemicalNames_tbl cn, Chemicals_tbl c, ChemicalLocations_tbl l, ChemicalLocationNames_tbl cln, ChemicalLocationTypes_tbl t, GeneralComments_tbl g, GeneralComments_tbl g2, GeneralComments_tbl g4 WHERE l.chemLocName=cln.chemLocNameID AND cn.CAS_No LIKE '%" . $keyword . "%' AND c.chemicalID=cn.chemicalID AND g2.commentID=c.Comments AND g4.commentID=l.chemLocComments AND c.chemicalLocation=l.chemLocID AND l.chemLocType=t.chemLocTypeID AND c.Supplier=g.commentID AND c.status='ACTIVE'";

		break;

		case 'Supplier':
			$keyword = $_POST["chemSearchKeyword"];
			
			$query = "SELECT c.chemListID, cn.chemicalName, cn.CAS_No, c.Quantity, g.comment as supplier, g2.comment as Comment, c.Safety as Safety, c.MSDS, g4.comment as locationComments, cln.chemicalLocationName as chemLocName, t.chemLocTypeName FROM ChemicalNames_tbl cn, Chemicals_tbl c, ChemicalLocations_tbl l, ChemicalLocationTypes_tbl t, ChemicalLocationNames_tbl cln, GeneralComments_tbl g, GeneralComments_tbl g2, GeneralComments_tbl g4 WHERE l.chemLocName=cln.chemLocNameID AND g.comment LIKE '%" . $keyword . "%' AND c.chemicalID=cn.chemicalID AND g2.commentID=c.Comments AND g4.commentID=l.chemLocComments AND c.chemicalLocation=l.chemLocID AND l.chemLocType=t.chemLocTypeID AND c.Supplier=g.commentID AND c.status='ACTIVE'";

		break;

		case 'Location':

			$chemLocID = $_POST["chemLocType"];
			
			$query = "SELECT c.chemListID, cn.chemicalName, cn.CAS_No, c.Quantity, g.comment as supplier, g2.comment as Comment, c.Safety as Safety, c.MSDS, g4.comment as locationComments, cln.chemicalLocationName as chemLocName, t.chemLocTypeName FROM ChemicalNames_tbl cn, Chemicals_tbl c, ChemicalLocations_tbl l, ChemicalLocationTypes_tbl t, ChemicalLocationNames_tbl cln, GeneralComments_tbl g, GeneralComments_tbl g2, GeneralComments_tbl g4 WHERE l.chemLocName=cln.chemLocNameID AND cln.chemLocNameID='" . $chemLocID . "' AND c.chemicalID=cn.chemicalID AND g2.commentID=c.Comments AND g4.commentID=l.chemLocComments AND c.chemicalLocation=l.chemLocID AND l.chemLocType=t.chemLocTypeID AND c.Supplier=g.commentID AND c.status='ACTIVE'";

		break;

		default:
			$keyword = $_POST["chemSearchKeyword"];

			$query = "(SELECT c.chemListID, cn.chemicalName, cn.CAS_No, c.Quantity, g.comment as supplier, g2.comment as Comment, c.Safety as Safety, c.MSDS, g4.comment as locationComments, cln.chemicalLocationName as chemLocName, t.chemLocTypeName FROM ChemicalNames_tbl cn, Chemicals_tbl c, ChemicalLocations_tbl l, ChemicalLocationNames_tbl cln, ChemicalLocationTypes_tbl t, GeneralComments_tbl g, GeneralComments_tbl g2, GeneralComments_tbl g4 WHERE l.chemLocName=cln.chemLocNameID AND cn.`chemicalName` LIKE '%" . $keyword . "%' AND c.chemicalID=cn.chemicalID AND g2.commentID=c.Comments AND g4.commentID=l.chemLocComments AND c.chemicalLocation=l.chemLocID AND l.chemLocType=t.chemLocTypeID AND c.Supplier=g.commentID AND c.status='ACTIVE' AND cn.status='ACTIVE' AND l.status='ACTIVE' AND t.status='ACTIVE') UNION (SELECT c.chemListID, cn.chemicalName, cn.CAS_No, c.Quantity, g.comment as supplier, g2.comment as Comment, c.Safety as Safety, c.MSDS, g4.comment as locationComments, cln.chemicalLocationName as chemLocName, t.chemLocTypeName FROM ChemicalNames_tbl cn, Chemicals_tbl c, ChemicalLocationNames_tbl cln, ChemicalLocations_tbl l, ChemicalLocationTypes_tbl t, GeneralComments_tbl g, GeneralComments_tbl g2, GeneralComments_tbl g4 WHERE l.chemLocName=cln.chemLocNameID AND g.`comment` LIKE '%" . $keyword . "%' AND c.chemicalID=cn.chemicalID AND g2.commentID=c.Comments AND g4.commentID=l.chemLocComments AND c.chemicalLocation=l.chemLocID AND l.chemLocType=t.chemLocTypeID AND c.Supplier=g.commentID AND c.status='ACTIVE') UNION (SELECT c.chemListID, cn.chemicalName, cn.CAS_No, c.Quantity, g.comment as supplier, g2.comment as Comment, c.Safety as Safety, c.MSDS, g4.comment as locationComments, cln.chemicalLocationName as chemLocName, t.chemLocTypeName FROM ChemicalNames_tbl cn, Chemicals_tbl c, ChemicalLocations_tbl l, ChemicalLocationTypes_tbl t, ChemicalLocationNames_tbl cln, GeneralComments_tbl g, GeneralComments_tbl g2, GeneralComments_tbl g4 WHERE cln.chemLocNameID=l.chemLocName AND cn.`CAS_No` LIKE '%" . $keyword . "%' AND c.chemicalID=cn.chemicalID AND g2.commentID=c.Comments AND g4.commentID=l.chemLocComments AND c.chemicalLocation=l.chemLocID AND l.chemLocType=t.chemLocTypeID AND c.Supplier=g.commentID AND c.status='ACTIVE') UNION (SELECT c.chemListID, cn.chemicalName, cn.CAS_No, c.Quantity, g.comment as supplier, g2.comment as Comment, c.Safety as Safety, c.MSDS, g4.comment as locationComments, cln.chemicalLocationName as chemLocName, t.chemLocTypeName FROM ChemicalLocationNames_tbl cln, ChemicalNames_tbl cn, Chemicals_tbl c, ChemicalLocations_tbl l, ChemicalLocationTypes_tbl t, GeneralComments_tbl g, GeneralComments_tbl g2, GeneralComments_tbl g4 WHERE cln.chemLocNameID=l.chemLocName AND g2.`comment` LIKE '%" . $keyword . "%' AND c.chemicalID=cn.chemicalID AND g2.commentID=c.Comments AND g4.commentID=l.chemLocComments AND c.chemicalLocation=l.chemLocID AND l.chemLocType=t.chemLocTypeID AND c.Supplier=g.commentID AND c.status='ACTIVE' AND cn.status='ACTIVE' AND l.status='ACTIVE' AND t.status='ACTIVE') ORDER BY chemicalName";

		break;
	}

	if ($_POST["search_criteria"] != "Safety")
	{
		$searchResultSet = mysql_query($query, $conn) or die("Could not find chemicals: " . mysql_error());

		while ($results_ar = mysql_fetch_array($searchResultSet, MYSQL_ASSOC))
		{
			//print_r($results_ar);

			$chemName = $results_ar["chemicalName"];
			//print $chemName . " ";

			$chemListID = $results_ar["chemListID"];	// Jan. 19, 2011
			$casNo = $results_ar["CAS_No"];
			$quantity = $results_ar["Quantity"];
			$supplier = $results_ar["supplier"];
			$safety = $results_ar["Safety"];
			$msds = $results_ar["MSDS"];
			//print $safety . "<BR>";

			$comments = $results_ar["Comment"];

			$locationDescr = $results_ar["locationComments"];	// Jan. 20, 2011

			$locationType = $results_ar["chemLocName"];	// flammable, acid, organic, etc.
			$locationTemp = $results_ar["chemLocTypeName"];	// 4C, -20C, room temp.

			$tmpLocation = new ChemicalLocation($locationType, $locationTemp, $locationDescr);

			$chemical = new Chemical($chemName, $casNo, $tmpLocation, $supplier, $quantity, $safety, $chemDescription, $comments, $msds, $chemListID);

			$chemResults[] = $chemical;
		}
	}
	else
	{
		$keyword = $_POST["safety"];		// array, because list is multiple
		
		$kwd = "";

		foreach ($keyword as $k => $val)
		{
			$kwd = "'" . $val . "'";

			$query = "SELECT c.chemListID, cn.chemicalName, cn.CAS_No, c.Quantity, g.comment as supplier, g2.comment as Comment, c.Safety as Safety, c.MSDS, g4.comment as locationComments, cln.chemicalLocationName as chemLocName, t.chemLocTypeName FROM ChemicalNames_tbl cn, Chemicals_tbl c, ChemicalLocations_tbl l, ChemicalLocationTypes_tbl t, ChemicalLocationNames_tbl cln, GeneralComments_tbl g, GeneralComments_tbl g2, GeneralComments_tbl g4 WHERE l.chemLocName=cln.chemLocNameID AND FIND_IN_SET(" . $kwd . ", c.Safety) > 0 AND c.chemicalID=cn.chemicalID AND g2.commentID=c.Comments AND g4.commentID=l.chemLocComments AND c.chemicalLocation=l.chemLocID AND l.chemLocType=t.chemLocTypeID AND c.Supplier=g.commentID AND c.status='ACTIVE'";

			$searchResultSet = mysql_query($query, $conn) or die("Could not find chemicals: " . mysql_error());

			while ($results_ar = mysql_fetch_array($searchResultSet, MYSQL_ASSOC))
			{
				//print_r($results_ar);

				$chemName = $results_ar["chemicalName"];
				//print $chemName . " ";

				$chemListID = $results_ar["chemListID"];	// Jan. 19, 2011
				$casNo = $results_ar["CAS_No"];
				$quantity = $results_ar["Quantity"];
				$supplier = $results_ar["supplier"];
				$safety = $results_ar["Safety"];
				//print $safety . "<BR>";
				$msds = $results_ar["MSDS"];

				$comments = $results_ar["Comment"];

				$locationDescr = $results_ar["locationComments"];	// Jan. 20, 2011

				$locationType = $results_ar["chemLocName"];	// flammable, acid, organic, etc.
				$locationTemp = $results_ar["chemLocTypeName"];	// 4C, -20C, room temp.

				$tmpLocation = new ChemicalLocation($locationType, $locationTemp, $locationDescr);

				$chemical = new Chemical($chemName, $casNo, $tmpLocation, $supplier, $quantity, $safety, $chemDescription, $comments, $msds, $chemListID);

				$chemResults[$chemListID] = $chemical;
			}

			// remove duplicates, so that a chemical that's both Toxic and Biohazard is not printed twice
			$chemResults = array_unique($chemResults);
		}
	}

	mysql_free_result($searchResultSet);

	return $chemResults;
}


/**
* Output search results
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
*
* @param $resultset
*/
function printChemicalSearchResults($results)
{
	echo "Found " . sizeof($results) . " chemicals matching your search query:";
	
	if (sizeof($results) > 0)
		echo "<span class=\"linkShow\" style=\"margin-left:10px; font-size:9pt; font-weight:bold;\" onclick=\"document.exportChemSearchResults.submit();\">Download</span>";
	
	$outputContent = "";

	echo "<BR>";
	
	if (sizeof($results) > 0)
	{
		echo "<P><TABLE width=\"100%\" class=\"preview\">";
	
		// print header row
		echo "<th class=\"searchHeader\">Chemical Name</th>";
		echo "<th class=\"searchHeader\" style=\"width:80px;\">CAS Number</th>";
		echo "<th class=\"searchHeader\" style=\"width:80px;\">Location Name/<BR>Room Number</th>";
		echo "<th class=\"searchHeader\" style=\"width:80px;\">Storage Type/<BR>Temperature</th>";
//		echo "<th class=\"searchHeader\" style=\"width:80px;\">Location Comments</th>";
		echo "<th class=\"searchHeader\">Supplier</th>";
		echo "<th class=\"searchHeader\" style=\"width:40px;\">WHMIS</th>";
		echo "<th class=\"searchHeader\" style=\"width:40px;\">MSDS</th>";
		echo "<th class=\"searchHeader\">Comments</th>";
		echo "<th class=\"searchHeader\" style=\"width:50px;\">Quantity</th>";

		$outputContent = "Chemical Name\tCAS Number\tLocation Name/Room Number\tStorage Type/Temperature\tWHMIS\tMSDS\tComments\tQuantity\r";
	
		foreach ($results as $key => $tmpChemical)
		{
			$tmpChemName = $tmpChemical->getName();
			$tmpCAS_No = $tmpChemical->getCAS_No();
			$tmpSafety = $tmpChemical->getSafety();
			$tmpQty = $tmpChemical->getQuantity();
			$tmpDescr = $tmpChemical->getDescription();
			$tmpSupplier = $tmpChemical->getSupplier();
			$tmpLocation = $tmpChemical->getLocation();			
			$tmpComments = $tmpChemical->getComments();
			$tmpLocComms = $tmpLocation->getDescription();
			$tmpChemID = $tmpChemical->getChemicalID();
			$msds = $tmpChemical->getMSDS();

			// June 17, 2011: Final change to safety (for now) :)
			$tmpSafety_ar = explode(",", $tmpSafety);
//			print_r($tmpSafety_ar);
			$safetyClassifier = "";
			$safetyVal = "";

			foreach ($tmpSafety_ar as $sKey => $sVal)
			{
				// get the classification letter
				$tmp_class_ar = explode(" - ", $sVal);
				$safetyClassifier = $tmp_class_ar[0];
//				$safetyVal .= $safetyClassifier . "<BR>";
				$safetyVal .= $safetyClassifier . ", ";
			}

			// remove last comma
			$safetyVal = rtrim($safetyVal, ", ");

			?>
			<TR>
				<TD class="preview">
				<?php
					 echo $tmpChemName;
				?>
				</TD>


				<TD class="preview">
				<?php
					 echo $tmpCAS_No;
				?>
				</TD>

				<TD class="preview">
				<?php
					 echo $tmpLocation->printLocation();
				?>
				</TD>

				<!-- Storage type (temperature) -->
				<TD class="preview">
				<?php
					 echo $tmpLocation->getTemperature();
				?>
				</TD>

				<!-- <TD class="preview">
				<?php
					 echo $tmpLocComms;
				?>
				</TD> -->

				<TD class="preview">
				<?php
					 echo $tmpSupplier;
				?>
				</TD>

				<TD class="preview" style="padding-left:2px;">
				<?php
					 //echo $tmpSafety;
					echo $safetyVal;
				?>
				</TD>

				<TD class="preview" style="padding-left:5px; padding-right:5px;">
				<?php
					if ($msds && ($msds != ""))
						echo "<a target=\"new\" href=\"" . $msds . "\">MSDS</a>";
					else
						echo "";
				?>
				</TD>

				<TD class="preview">
				<?php
					 echo $tmpComments;
				?>
				</TD>

				<TD class="preview">
				<?php
					 echo $tmpQty;
				?>
				</TD>
				
				<td>
					<a class="search" href="Chemical.php?View=3&chemicalID=<?php echo $tmpChemID; ?>">Details</a></td>
				</td>

			</TR>
			<?php

			$tmpLocName = $tmpLocation->getName();
			$tmpLocType = $tmpLocation->getTemperature();

			$outputContent .= $tmpChemName . "\t" . $tmpCAS_No . "\t" . $tmpLocName ."\t" . $tmpLocType . "\t" . $safetyVal . "\t" . $msds . "\t" . $tmpComments . "\t" . $tmpQty . "\n";
		}

//	echo $outputContent;

	?>
	</TABLE>
	<?php

		echo "<FORM style=\"display:none;\" NAME=\"exportChemSearchResults\" METHOD=\"POST\" ACTION=\"exportChemical.php\">";
		echo "<INPUT TYPE=\"hidden\" NAME=\"outputContent\" VALUE=\"" . $outputContent . "\">";
		echo "</FORM>";
	}
}

/**
* Output form to input chemicals
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
*/
function printCreationForm()
{
	global $conn;

	?>
	<FORM NAME="search_chemicals_form" METHOD="POST" ACTION="<?php echo $_SERVER["PHP_SELF"] . "?View=2"; ?>" onSubmit="return checkChemicalCreation();">
	
		<TABLE frame="box" rules="all" style="margin-left:5px" width="99%">
			<TH colspan="2" style="padding-top:5px; text-align:center;">
				<span style="color:#0000FF; margin-top:5px;">ADD CHEMICALS</span>
				<P style="color:#FF0000; font-weight:normal; font-size:8pt; margin-top:5px;">Fields in red marked with an asterisk (<span style="font-size:9pt; color:#FF0000;">*</span>) are mandatory</P>
			</TH>

			<TR>
				<TD style="white-space:nowrap; font-weight:bold; padding-left:5px; padding-top:2px; padding-bottom:2px;">
					Chemical Name:&nbsp;<sup style="font-size:10pt; color:#FF0000;">*</sup>
				</TD>

				<TD style="padding-left:10px;" colspan="2">
					<INPUT TYPE="TEXT" SIZE="35" ID="chemical_name" NAME="chemName">&nbsp;&nbsp;
				</TD>
			</TR>

			<TR>
				<TD style="font-weight:bold; padding-left:5px;">
					CAS Number:
				</TD>

				<TD style="padding-left:10px; padding-top:2px; padding-bottom:2px;" colspan="2">
					<INPUT TYPE="TEXT" SIZE="10" NAME="cas_no">&nbsp;&nbsp;
				</TD>
			</TR>
			<?php
				// Jan. 22, 2011: changing database structure, unlinking chemical location names from temperature
				$locationNames_rs = mysql_query("SELECT chemLocNameID, chemicalLocationName FROM ChemicalLocationNames_tbl ORDER BY chemicalLocationName", $conn) or die("Could not select chemical location: " . mysql_error());
				
				// change Jan. 22, 2011
				//$locations_rs = mysql_query("SELECT chemLocID, chemLocName FROM ChemicalLocations_tbl ORDER BY chemLocName", $conn) or die("Could not select chemical location: " . mysql_error());

				// replaced Jan. 22, 2011:
				$locations_rs = mysql_query("SELECT cl.chemLocID, cn.chemLocNameID, cn.chemicalLocationName AS chemLocName, ct.chemLocTypeName FROM ChemicalLocations_tbl cl, ChemicalLocationTypes_tbl ct, ChemicalLocationNames_tbl cn WHERE cn.status='ACTIVE' AND ct.status='ACTIVE' AND cl.status='ACTIVE' AND cl.chemLocType=ct.chemLocTypeID AND cl.chemLocName=cn.chemLocNameID ORDER BY chemLocName", $conn) or die("Could not select chemical location: " . mysql_error());
			
				$locNames = Array();
			?>
			<TR>
				<TD style="font-weight:bold; padding-left:5px;">
					Chemical Location:&nbsp;<sup style="font-size:10pt; color:#FF0000;">*</sup>
				</TD>

				<TD style="padding-left:10px;" colspan="2">
					<SELECT ID="chemical_locations" NAME="chemLocType" style="font-size:10pt;" onChange="showHideAddLocation();">
						<OPTION VALUE="default"> -- Select Location Name -- </OPTION><?php

						while ($locations_ar = mysql_fetch_array($locations_rs, MYSQL_ASSOC))
						{
							$chemLocID = $locations_ar["chemLocID"];
//							$chemLocName = $locations_ar["chemLocName"];
							$chemLocName = $locations_ar["chemLocName"] . " " . $locations_ar["chemLocTypeName"];
							
							$chemLocNameID = $locations_ar["chemLocNameID"];
							$locNames[$chemLocNameID] = $locations_ar["chemLocName"];

							echo "<OPTION VALUE=\"" . $chemLocID . "\">" . $chemLocName . "</OPTION>";
						}

						?>
						<OPTION VALUE="addChemLoc">or, ADD NEW LOCATION</OPTION>
					</SELECT>

					<DIV ID="add_chem_loc" style="display:none; font-size:9pt; font-weight:bold; padding-top:5px; width:100%;">
						<table border="0" style="padding-top:10px;">
							<!-- <th style="text-align:left;">Select an existing location name from the list, or add your own:</th> -->
							<tr>
								<td style="font-weight:bold; padding-left:3px;">Storage Name/Room Number:&nbsp;<sup style="font-size:10pt; color:#FF0000;">*</sup></td>
								<td style="padding-left:10px;">
									<SELECT ID="existingLocationNamesList" NAME="newChemLocNameList" style="font-size:10pt;" onChange="if (this.options[options.selectedIndex].value == 'Other') document.getElementById('new_chem_loc_name').style.display=''; else document.getElementById('new_chem_loc_name').style.display='none';">
										<OPTION VALUE="default"> -- Select -- </OPTION><?php

										foreach ($locNames as $key => $value)
										{
											echo "<OPTION VALUE=\"" . $value . "\">" . $value . "</OPTION>";
										}

										?>
										<OPTION VALUE="Other">ADD NEW</OPTION>
									</SELECT>
										
									<!-- , or add new storage name / room number:&nbsp; -->
									<BR><INPUT TYPE="TEXT" ID="new_chem_loc_name" NAME="newChemLocName" SIZE="35" style="display:none;">
									<input type="hidden" id="addLocName">
								</td>
							</tr>

							<TR>
								<TD style="font-weight:bold; padding-left:3px;">
									Storage Type/Temperature:&nbsp;<sup style="font-size:10pt; color:#FF0000;">*</sup>
								</TD>

								<TD style="padding-left:10px;">
									<input type="hidden" id="addLocType">
										<!-- do a selection again and output list, if available -->
									<?php

									$locTypes_rs = mysql_query("SELECT chemLocTypeID, chemLocTypeName FROM ChemicalLocationTypes_tbl WHERE status='ACTIVE'", $conn) or die("Could not select chemical location types: " . mysql_error());

									?><SELECT NAME="newChemLocTypeList" ID="chem_loc_type_list" style="font-size:10pt;" onChange="showHideAddLocType();" DISABLED>
										<OPTION VALUE="default"> -- Select Storage Type/Temperature -- </OPTION><?php

											while ($locTypes_ar = mysql_fetch_array($locTypes_rs, MYSQL_ASSOC))
											{
												$chemLocTypeID = $locTypes_ar["chemLocTypeID"];
												$chemLocTypeName = $locTypes_ar["chemLocTypeName"];
						
												echo "<OPTION VALUE=\"" . $chemLocTypeID . "\">" . $chemLocTypeName . "</OPTION>";
											}
										?>
										<OPTION VALUE="addChemLocType">or, ADD NEW STORAGE TYPE/TEMPERATURE</OPTION>
									</SELECT>

									<DIV ID="add_chem_loc_type" style="display:none; font-size:9pt; font-weight:bold; padding-top:5px;">
										<BR>Location Type:&nbsp;&nbsp;
										<INPUT TYPE="TEXT" ID="new_chem_loc_type" NAME="newChemLocType" SIZE="35">
										<input type="hidden" id="addLocType">
									</DIV>
								</TD>
							</TR>

							<TR>
								<TD style="white-space:nowrap; font-weight:bold; padding-left:5px;">
									Location Comments:
								</TD>

								<TD style="padding-left:10px;">
									<INPUT TYPE="TEXT" SIZE="75" NAME="locComms">&nbsp;&nbsp;
								</TD>
							</TR>
						</table>
					</DIV>
				</TD>
			</TR>

			<TR>
				<TD style="font-weight:bold; padding-left:5px;">
					Supplier:
				</TD>

				<TD style="padding-left:10px; padding-top:2px; padding-bottom:2px;" colspan="2">
					<INPUT TYPE="TEXT" SIZE="10" NAME="supplier">&nbsp;&nbsp;
				</TD>
			</TR>

			<TR>
				<TD style="font-weight:bold; padding-left:5px;">
					Comments:
				</TD>

				<TD style="padding-left:10px; padding-top:2px; padding-bottom:2px;" colspan="2">
					<INPUT TYPE="TEXT" SIZE="28" NAME="comments">&nbsp;&nbsp;
				</TD>
			</TR>

			<TR>
				<TD style="font-weight:bold; padding-left:5px;">
					MSDS/Supplier Link:
				</TD>

				<TD style="padding-left:10px; padding-top:2px; padding-bottom:2px;" colspan="2">
					<INPUT TYPE="TEXT" SIZE="28" NAME="msds">&nbsp;&nbsp;
					<IMG SRC="pictures/link5.png" WIDTH="18" HEIGHT="8" onmouseover="return overlib('Please enter a complete URL in the textbox, e.g. http://www.mysite.com', CAPTION, 'MSDS/Supplier Link', STICKY);">
				</TD>
			</TR>

			<TR>
				<TD style="font-weight:bold; padding-left:7px; text-align:left; width:175px;">
					Safety - WHMIS Classification:<BR><P>
					<a href="pictures/whmis/classificiation_system.pdf" style="margin-left:2px;" target="new">What's this?</a>
				</TD>

				<TD style="padding-left:10px; padding-right:10px; border-right:0px; padding-top:5px; padding-bottom:5px;">
					<!-- <INPUT TYPE="TEXT" SIZE="75" NAME="safety">&nbsp;&nbsp; -->

					<SELECT name="safety_add[]" SIZE="15" MULTIPLE style="margin-top:10px; margin-bottom:10px;">
						<OPTION VALUE="A - Compressed Gas">A - Compressed Gas</OPTION>
						<OPTION VALUE="B1 - Flammable Gas">B1 - Flammable Gas</OPTION>
						<OPTION VALUE="B2 - Flammable Liquid">B2 - Flammable Liquid</OPTION>
						<OPTION VALUE="B3 - Combustible Liquid">B3 - Combustible Liquid</OPTION>
						<OPTION VALUE="B4 - Flammable Solid">B4 - Flammable Solid</OPTION>
						<OPTION VALUE="B5 - Flammable Aerosol">B5 - Flammable Aerosol</OPTION>
						<OPTION VALUE="B6 - Reactive Flammable">B6 - Reactive Flammable</OPTION>
						<OPTION VALUE="C - Oxidizing">C - Oxidizing</OPTION>
						<OPTION VALUE="D1A - Immediate Very Toxic">D1A - Immediate, Very Toxic</OPTION>
						<OPTION VALUE="D1B - Immediate Toxic">D1B - Immediate, Toxic</OPTION>
						<OPTION VALUE="D2A - Other Very Toxic">D2A - Other, Very Toxic</OPTION>
						<OPTION VALUE="D2B - Other Toxic">D2B - Other, Toxic</OPTION>
						<OPTION VALUE="E - Corrosive">E - Corrosive</OPTION>
						<OPTION VALUE="F - Dangerously Reactive">F - Dangerously Reactive</OPTION>
						<OPTION VALUE="Non-Controlled">Non-Controlled</OPTION>
					</SELECT>
				</TD>
			</TR>

			<TR>
				<TD style="font-weight:bold; padding-left:5px;">
					Quantity:
				</TD>

				<TD style="padding-left:10px; padding-top:2px; padding-bottom:2px;" colspan="2">
					<INPUT TYPE="TEXT" SIZE="10" NAME="quantity">&nbsp;&nbsp;
				</TD>
			</TR>
		</TABLE>

		<P><P><INPUT TYPE="SUBMIT" NAME="add_chemical" VALUE="Submit" style="margin-left:3px;">
	</FORM>
	<?php
}


/**
* Process request to add chemicals
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
*
* @return resultset
*/
function addChemical()
{
	global $conn;

	// mandatory fields
	$chemName = "";
	$chemLocType = "";

	$casNo = "";
	$locComms = "";
	$supplier = "";
	$safety = "";
	$chemComms = "";
	$qty = "";

	// 'Chemical' comment link ID
	$chemCommLink_rs = mysql_query("SELECT commentLinkID FROM CommentLink_tbl WHERE link='Chemical'", $conn) or die ("Error selecting chemical comment link: " . mysql_error());
	$chemComm_row = mysql_fetch_row($chemCommLink_rs);

	$chemCommLinkID = $chemComm_row[0];

//	echo $chemCommLinkID;
//	print_r($_POST);

	if (isset($_POST["chemName"]))		// better be set
		$chemName = $_POST["chemName"];

	// June 16, 2011
	if (isset($_POST["msds"]))
		$msds = $_POST["msds"];

	if (isset($_POST["chemLocType"]))
	{
		if ($_POST["chemLocType"] == "addChemLoc")
		{		
			// Adding new location; check type first
			if ($_POST["newChemLocTypeList"] == "addChemLocType")
			{
				if (isset($_POST["newChemLocType"]))
				{
					$chemLocTypeName = $_POST["newChemLocType"];
			
					// CHECK EXISTENCE
					$chemLocTypes = mysql_query("SELECT chemLocTypeID FROM ChemicalLocationTypes_tbl WHERE chemLocTypeName='" . $chemLocTypeName . "' AND status='ACTIVE'", $conn) or die("Cannot select chemical location type: " . mysql_error());
			
					if ($chemLocTypeRow = mysql_fetch_row($chemLocTypes))
					{
						$chemLocTypeID = $chemLocTypeRow[0];
					}
					else
					{
						mysql_query("INSERT INTO ChemicalLocationTypes_tbl(chemLocTypeName) VALUES('" . $chemLocTypeName . "')");
						$chemLocTypeID = mysql_insert_id();
					}
				}
			}
			else
			{
				$chemLocTypeID = $_POST["newChemLocTypeList"];
			}

			// create new ChemicalLocations_tbl entry
			$newChemLocName = null;

			if (isset($_POST["newChemLocName"]) && ($_POST["newChemLocName"] != ""))
			{
				$newChemLocName = $_POST["newChemLocName"];
			}
			else if (isset($_POST["newChemLocNameList"]) && ($_POST["newChemLocNameList"] != ""))
			{
				$newChemLocName = $_POST["newChemLocNameList"];
			}

			if ($newChemLocName != "")
			{
				// Jan. 21, 2011: grab location comments
			//	print_r($_POST);
				$newLocComms = $_POST["locComms"];
				
				// find their ID
				$comms_rs = mysql_query("SELECT commentID FROM GeneralComments_tbl WHERE commentLinKID='" . $chemCommLinkID . "' AND comment='" . $newLocComms . "' AND status='ACTIVE'", $conn) or die("Cannot select location comments: " . mysql_error());

				$comms_ar = mysql_fetch_row($comms_rs);

				if ($comms_ar)
					$locCommID = $comms_ar[0];
				else
				{
					// if not found, create an empty comment with 'Chemical' identifier
					mysql_query("INSERT INTO GeneralComments_tbl(commentLinkID, comment) VALUES('" . $chemCommLinkID . "', '" . $newLocComms . "')");
					$locCommID = mysql_insert_id();
				}	

				// check if location exists; user might unknowingly enter 'MS shelf' again
				// Jan. 22, 2011: changed database structure
				$chemLocNameSet = mysql_query("SELECT chemLocNameID FROM ChemicalLocationNames_tbl WHERE chemicalLocationName='" . $newChemLocName . "' AND status='ACTIVE'", $conn) or die("Could not select chemical location: " . mysql_error());

				if ($chemLocName_row = mysql_fetch_row($chemLocNameSet))
				{
					$chemLocNameID = $chemLocName_row[0];

					$chemLocSet = mysql_query("SELECT chemLocID FROM ChemicalLocations_tbl WHERE chemLocName='" . $chemLocNameID . "' AND chemLocType='" . $chemLocTypeID . "' AND status='ACTIVE'", $conn) or die("Could not select chemical location: " . mysql_error());
	
					if ($chemLoc_row = mysql_fetch_row($chemLocSet))
					{
						$chemLocID = $chemLoc_row[0];
					}
					else
					{
						mysql_query("INSERT INTO ChemicalLocations_tbl(chemLocName, chemLocType) VALUES('" . $chemLocNameID . "', '" . $chemLocTypeID . "')", $conn) or die("Cannot insert chemical: " . mysql_error());
						$chemLocID = mysql_insert_id();
					}
				}
				else
				{
					// create new entry
					mysql_query("INSERT INTO ChemicalLocationNames_tbl(chemicalLocationName) VALUES('" . $newChemLocName . "')");
					$chemLocNameID = mysql_insert_id();

					mysql_query("INSERT INTO ChemicalLocations_tbl(chemLocName, chemLocType) VALUES('" . $chemLocNameID . "', '" . $chemLocTypeID . "')", $conn) or die("Cannot insert chemical: " . mysql_error());
					$chemLocID = mysql_insert_id();
				}

				// Just update its comments, period:
				mysql_query("UPDATE ChemicalLocations_tbl SET chemLocComments='" . $locCommID . "' WHERE chemLocID='" . $chemLocID . "'");
			}
		}
		else
		{
			// existing location - THIS IS WHERE IT FAILS
			$chemLocID = $_POST["chemLocType"];		// correction Jan. 4, 2011

			// Look in the database to see if the selected location has comments (it better)
			$locComm_rs = mysql_query("SELECT commentID FROM GeneralComments_tbl WHERE commentLinkID='" . $chemCommLinkID . "' AND comment='" . $locComms . "' AND status='ACTIVE'", $conn) or die("Cannot select location comment: " . mysql_error());
		
			if ($row = mysql_fetch_row($locComm_rs))
			{
				$locCommID = $row[0];
			}
			else
			{
				echo "ever here??";
			}
		}
	}

	if (isset($_POST["cas_no"]))
		$casNo = $_POST["cas_no"];

	if (isset($_POST["locComms"]))
		$locComms = $_POST["locComms"];

	if (isset($_POST["supplier"]))
		$supplier = $_POST["supplier"];

	if (isset($_POST["safety_add"]))
		$safety = $_POST["safety_add"];

//	print_r($safety);
	
	if (isset($_POST["comments"]))
		$chemComms = $_POST["comments"];

	if (isset($_POST["quantity"]))
		$qty = $_POST["quantity"];

	// Find out whether this chemical exists in ChemicalNames_tbl -- Jan. 21, 2011: BUT do a FULL query!!
	$chem_rs = mysql_query("SELECT chemicalID FROM ChemicalNames_tbl WHERE chemicalName='" . $chemName . "' AND CAS_No='" . $casNo . "' AND status='ACTIVE'", $conn) or die("Cannot select chemical : " . mysql_error());

	if ($chemRow = mysql_fetch_row($chem_rs))
	{
		$chemID = $chemRow[0];
	}
	else
	{
		// create new entry
		mysql_query("INSERT INTO ChemicalNames_tbl(chemicalName, CAS_No) VALUES('" . $chemName . "', '" . $casNo . "')", $conn) or die("Cannot insert chemical: " . mysql_error());
		$chemID = mysql_insert_id();
	}

	// Create GeneralComments_tbl entries for chemical comments, location comments, supplier and safety IFF non-empty
	if (sizeof(trim($chemComms)) > 0)
	{
		$chemComm_rs = mysql_query("SELECT commentID FROM GeneralComments_tbl WHERE commentLinkID='" . $chemCommLinkID . "' AND comment='" . $chemComms . "'", $conn) or die("Cannot select chemical comment: " . mysql_error());
	
		if ($row = mysql_fetch_row($chemComm_rs))
		{
			$chemCommID = $row[0];
		}
		else
		{
			$q1 = "INSERT INTO GeneralComments_tbl(commentLinkID, comment) VALUES('" . $chemCommLinkID . "', '" . $chemComms . "')";
			mysql_query($q1, $conn) or die("Cannot insert chemical comments: " . mysql_error());
			$chemCommID = mysql_insert_id();
		}
	}

/*	if (sizeof(trim($locComms)) > 0)
	{
		$locComm_rs = mysql_query("SELECT commentID FROM GeneralComments_tbl WHERE commentLinkID='" . $chemCommLinkID . "' AND comment='" . $locComms . "'", $conn) or die("Cannot select location comment: " . mysql_error());
	
		if ($row = mysql_fetch_row($locComm_rs))
		{
			$locCommID = $row[0];
		}
		else
		{
			$q2 = "INSERT INTO GeneralComments_tbl(commentLinkID, comment) VALUES('" . $chemCommLinkID . "', '" . $locComms . "')";
			mysql_query($q2, $conn) or die("Cannot insert location comments: " . mysql_error());
			$locCommID = mysql_insert_id();
		}
	}*/
	

	// Update location comments!
//	mysql_query("UPDATE ChemicalLocations_tbl SET chemLocComments='" . $locCommID . "' WHERE chemLocID='" . $chemLocID . "'");

	if (sizeof(trim($supplier)) > 0)
	{
		$supplierComm_rs = mysql_query("SELECT commentID FROM GeneralComments_tbl WHERE commentLinkID='" . $chemCommLinkID . "' AND comment='" . $supplier . "'", $conn) or die("Cannot select location comment: " . mysql_error());
	
		if ($row = mysql_fetch_row($supplierComm_rs))
		{
			$supplierCommID = $row[0];
		}
		else
		{
			$q3 = "INSERT INTO GeneralComments_tbl(commentLinkID, comment) VALUES('" . $chemCommLinkID . "', '" . $supplier . "')";
			mysql_query($q3, $conn) or die("Cannot insert supplier comments: " . mysql_error());
			$supplierCommID = mysql_insert_id();
		}
	}


	if (sizeof($safety) > 0)	// array now
	{
		/*
		$safetyComm_rs = mysql_query("SELECT commentID FROM GeneralComments_tbl WHERE commentLinkID='" . $chemCommLinkID . "' AND comment='" . $safety . "'", $conn) or die("Cannot select location comment: " . mysql_error());
	
		if ($row = mysql_fetch_row($safetyComm_rs))
		{
			$safetyCommID = $row[0];
		}
		else
		{
			$q3 = "INSERT INTO GeneralComments_tbl(commentLinkID, comment) VALUES('" . $chemCommLinkID . "', '" . $safety . "')";
			mysql_query($q3, $conn) or die("Cannot insert safety comments: " . mysql_error());
			$safetyCommID = mysql_insert_id();
		}
		*/

		// June 15, 2011: No, not anymore
		//$safety = $_POST["safety_add"];		// array, because list is multiple
		$whmis = $_POST["whmis_class"];
		
		$safety = implode(",", $safety);
		$whmis = implode(",", $whmis);
//print_r($safety);
	}

	// Jan. 19, 2011: Still, check for existence of chemical with all these credentials (don't want refreshing and having a chemical inserted 10x)
	$chemSelect_rs = mysql_query("SELECT chemListID FROM Chemicals_tbl WHERE chemicalID='" . $chemID . "' AND chemicalLocation='" . $chemLocID . "' AND Comments='" . $chemCommID . "' AND Quantity='" . $qty . "' AND Supplier='" . $supplierCommID . "' AND Safety='" . $safety . "' AND MSDS='" . $msds . "' AND status='ACTIVE'", $conn) or die("Cannot select chemical: " . mysql_error());

	//echo "SELECT chemListID FROM Chemicals_tbl WHERE chemicalID='" . $chemID . "' AND chemicalLocation='" . $chemLocID . "' AND Comments='" . $chemCommID . "' AND Quantity='" . $qty . "' AND Supplier='" . $supplierCommID . "' AND Safety='" . $safety . "' AND status='ACTIVE'<BR><BR>";

	if ($chem_set = mysql_fetch_row($chemSelect_rs))
	{
		return $chem_set[0];
	}
	else
	{
		$query = "INSERT INTO Chemicals_tbl(chemicalID, chemicalLocation, Comments, Quantity, Supplier, Safety, MSDS) VALUES('" . $chemID . "', '" . $chemLocID . "', '" . $chemCommID . "', '" . $qty . "', '" . $supplierCommID . "', ('" . $safety . "'), '" . $msds . "')";

	//	echo $query;

		$chemInsertResult = mysql_query($query, $conn) or die("Cannot insert chemical: " . mysql_error());
		$chemListID = intval(mysql_insert_id());

		return $chemListID;
	}
}

/**
* Print details for a selected chemical (output detailed view)
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
*
* @param INT Database ID of the sought chemical
*/
function printChemicalDetails($chemListID)
{
	$chemical = createChemical($chemListID);

//	print_r($chemical);

	$chemSymbols = Array();
	$chemClasses = Array();

	$chemClasses["A - Compressed Gas"] = "Compressed Gas";
	$chemClasses["B1 - Flammable Gas"] = "Flammable";
	$chemClasses["B2 - Flammable Liquid"] = "Flammable";
	$chemClasses["B3 - Combustible Liquid"] = "Flammable";
	$chemClasses["B4 - Flammable Solid"] = "Flammable";
	$chemClasses["B5 - Flammable Aerosol"] = "Flammable";
	$chemClasses["B6 - Reactive Flammable"] = "Flammable";
	$chemClasses["C - Oxidizing"] = "Oxidizing";
	$chemClasses["D1A - Immediate Very Toxic"] = "Highly Toxic";
	$chemClasses["D1B - Immediate Toxic"] = "Highly Toxic";
	$chemClasses["D2A - Other Very Toxic"] = "Toxic";
	$chemClasses["D2B - Other Toxic"] = "Toxic";
	$chemClasses["E - Corrosive"] = "Corrosive";
	$chemClasses["F - Dangerously Reactive"] = "Reactive";

//	$chemSymbols["Biohazard"] = "pictures/whmis/new_images/biohazard.jpg";		// not for us
	$chemSymbols["Corrosive"] = "pictures/whmis/new_images/corrosive.jpg";
	$chemSymbols["Compressed Gas"] = "pictures/whmis/new_images/compressed_gas.jpg";
	$chemSymbols["Flammable"] = "pictures/whmis/new_images/flammable.jpg";
	$chemSymbols["Oxidizing"] = "pictures/whmis/new_images/oxidizing.jpg";
	$chemSymbols["Highly Toxic"] = "pictures/whmis/new_images/poisonous.jpg";
	$chemSymbols["Reactive"] = "pictures/whmis/new_images/reactive.jpg";
	$chemSymbols["Toxic"] = "pictures/whmis/new_images/toxic.jpg";

	?>
	<TABLE width="760px" cellpadding="5" cellspacing="5" border="1" frame="box" rules="none" style="padding-left:5px; vertical-align:middle" name="chem_props" class="detailedView_tbl">
		<tr>
			<td class="detailedView_heading" style="white-space:nowrap; color:blue; color:#0000DF; text-align:center;" colspan="2">
				<?php echo $chemical->getName(); ?> Details Page&nbsp;&nbsp;
				<INPUT TYPE="hidden" name="chemicalName" value="<?php echo $chemical->getName(); ?>">
			</td>
		</tr>

		<tr>
			<td style="text-align:center; vertical-align:bottom; white-space:nowrap;" colspan="100%">
				<?php 
					$chems = $chemical->getSafety();

					$chemArray = explode(",", $chems);
					//print_r($chemArray);

					$symbols_uniq = Array();

					foreach ($chemArray as $cKey => $cVal)
					{
						$chemClass = $chemClasses[$cVal];
						$fName = $chemSymbols[$chemClass];

						$symbols_uniq[] = $fName;
					}
	
					$symbols_uniq = array_unique($symbols_uniq);

					foreach ($symbols_uniq as $key => $fSrc)
					{
						?><IMG SRC="<?php echo $fSrc; ?>" height="35px" style="cursor:auto;">&nbsp;<?php
					}
				?>
			</td>
		</tr>

		<!-- Chemical Name -->
		<TR>
			<TD class="detailedView_colName">Chemical Name</TD>
			<TD class="detailedView_value"><?php echo $chemical->getName(); ?></TD>
		</TR>

		<!-- Description - MO: not available -->
		<!-- <TR>
			<TD class="detailedView_colName">Description</TD>
			<TD class="detailedView_value"><?php echo $chemical->getDescription(); ?></TD>
		</TR>-->
	
		<!-- CAS No. -->
		<TR>
			<TD class="detailedView_colName">CAS Number</TD>
			<TD class="detailedView_value"><?php echo $chemical->getCAS_No(); ?></TD>
		</TR>

		<!-- Supplier -->
		<TR>
			<TD class="detailedView_colName">Supplier</TD>
			<TD class="detailedView_value"><?php echo $chemical->getSupplier(); ?></TD>
		</TR>

		<!-- Comments -->
		<TR>
			<TD class="detailedView_colName">Comments</TD>
			<TD class="detailedView_value"><?php echo $chemical->getComments(); ?></TD>
		</TR>
		
		<!-- Safety - CHANGING, JUNE 15, 2011 -->
<!--		<TR>
			<TD class="detailedView_colName">Safety</TD>
			<TD class="detailedView_value"><?php echo $chemical->getSafety(); ?></TD>
		</TR> -->

		<!-- Quantity -->
		<TR>
			<TD class="detailedView_colName">Quantity</TD>
			<TD class="detailedView_value"><?php echo $chemical->getQuantity(); ?></TD>
		</TR>

		<!-- June 15, 2011 -->
		<TR>
			<TD colspan="4" class="detailedView_heading" style="text-align:left; padding-right:6px; padding-top:10px; color:#0000DF; font-size:9pt; font-family:Helvetica; white-space:nowrap;">Safety Information</TD>
		</TR>

		<TR>
			<TD class="detailedView_colName" style="padding-left:10px;">MSDS / Supplier Link</TD>
			<TD style="font-weight:bold;">
				<?php
					$msds = $chemical->getMSDS();

					echo "<a target=\"new\" href=\"" . $msds . "\">" . $msds . "</a>";
				?>
			</TD>
		</TR>

		<TR>
			<TD class="detailedView_colName" style="padding-left:10px;">Safety - WHMIS Classification:</TD>
			<TD style="color:red;"><?php 
				echo "<UL style=\"padding-left:10px; font-weight:bold; font-size:9pt;\">";

				$chem_ar = explode(",", $chemical->getSafety());
				//echo str_replace(",", ", ", $chemical->getSafety());
		
				foreach ($chem_ar as $cName => $cVal)
				{
					switch ($cVal)
					{
						case 'D1A - Immediate Very Toxic':
							echo "<LI>D1A - Immediate, Very Toxic</LI>";
						break;

						case 'D1B - Immediate Toxic':
							echo "<LI>D1B - Immediate, Toxic</LI>";
						break;

						case 'D2A - Other Very Toxic':
							echo "<LI>D2A - Other, Very Toxic</LI>";
						break;

						case 'D2B - Other Toxic':
							echo "<LI>D2B - Other, Toxic</LI>";
						break;

						default:
							echo "<LI>" . $cVal . "</LI>";
						break;
					}
				}

				echo "</UL>";
			?></TD>
		</TR> 

		<!-- Location Info -->
		<TR>
			<TD colspan="4" class="detailedView_heading" style="text-align:left; padding-right:6px; padding-top:10px; color:#0000DF; font-size:9pt; font-family:Helvetica; white-space:nowrap;">Location Details</TD>
		</TR>
		<?php 
			$chemLoc = $chemical->getLocation();
		?>
		<!-- Location Name -->
		<TR>
			<TD class="detailedView_colName">Storage Name / Room Number</TD>
			<TD class="detailedView_value"><?php echo $chemLoc->getName(); ?></TD>
		</TR>

		
		<!-- Location Type -->
		<TR>
			<TD class="detailedView_colName">Storage Type/Temperature</TD>
			<TD class="detailedView_value"><?php echo $chemLoc->getTemperature(); ?></TD>
		</TR>

		
		<!-- Location comments -->
		<TR>
			<TD class="detailedView_colName">Location Comments</TD>
			<TD class="detailedView_value"><?php echo $chemLoc->getDescription(); ?></TD>
		</TR>
	</table>
	<?php	
}


/**
* Select all information on the chemical identified by $chemID from the database and create a Chemical object with this information
*
* @author Marina Olhovsky <olhovsky@lunenfeld.ca>
* @version 3.1
*
* @param INT Database ID of the sought chemical
*/
function createChemical($chemListID)
{
	global $conn;

	$newChem = null;

	$chem_rs = mysql_query("SELECT cn.chemicalName, cn.CAS_No, gc1.comment as Comments, gc2.comment as Supplier, gc3.comment as chemLocComments, c.Quantity, c.Safety as Safety, c.MSDS as MSDS, clt.chemLocTypeName, cln.chemicalLocationName FROM ChemicalNames_tbl cn, Chemicals_tbl c, GeneralComments_tbl gc1, GeneralComments_tbl gc2, GeneralComments_tbl gc3, CommentLink_tbl gcl, ChemicalLocationTypes_tbl clt, ChemicalLocations_tbl cl, ChemicalLocationNames_tbl cln WHERE cn.chemicalID=c.chemicalID AND c.chemicalLocation=cl.chemLocID AND gc1.commentID = c.Comments AND gc1.commentLinkID=gcl.commentLinkID AND gcl.link='Chemical' AND gc2.commentLinkID=gcl.commentLinkID AND c.Supplier=gc2.commentID AND cl.chemLocComments=gc3.commentID AND gc3.commentLinkID=gcl.commentLinkID AND cl.chemLocType=clt.chemLocTypeID AND cl.chemLocName=cln.chemLocNameID AND cln.status='ACTIVE' AND cn.status='ACTIVE' AND c.status='ACTIVE' AND cl.status='ACTIVE' AND clt.status='ACTIVE' AND c.chemListID='" . $chemListID . "'", $conn) or die("Failure in creating chemical: " . mysql_error());

	if ($chem_ar = mysql_fetch_array($chem_rs, MYSQL_ASSOC))
	{
		$chemicalName = $chem_ar["chemicalName"];
		$cas_NO = $chem_ar["CAS_No"];
		$supplier = $chem_ar["Supplier"];
		$safety = $chem_ar["Safety"];
		$quantity = $chem_ar["Quantity"];
		$msds = $chem_ar["MSDS"];
		$chemComms = $chem_ar["Comments"];
		$chemLocName = $chem_ar["chemicalLocationName"];
		$chemLocTypeName = $chem_ar["chemLocTypeName"];
		$chemLocComms = $chem_ar["chemLocComments"];

		$tmpChemLoc = new ChemicalLocation($chemLocName, $chemLocTypeName, $chemLocComms);
		$newChem = new Chemical($chemicalName, $cas_NO, $tmpChemLoc, $supplier, $quantity, $safety, "", $chemComms, $msds);
	}

	return $newChem;
}

mysql_close($conn);
?>
