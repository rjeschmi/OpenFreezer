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
* @author John Paul Lee @version 2005
*
* @author     Marina Olhovsky <olhosvky@lunenfeld.ca>
* @version    3.1
* @package User
*
* @copyright  2005-2010 Pawson Laboratory
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
 * This class contains functions to manage user authorization at login.
 *
 * @author John Paul Lee @version 2005
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package User
 *
 * @copyright	2005-2010 Pawson Laboratory
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
class MemberLogin_Class
{
	/**
	 * @var Member_Class
	 * Member_Class instance, representing the user logging in
	*/
	var $currentUser;

	var $result;
	var $classerror;
	
	/**
	 * Constructor
	*/
	function MemberLogin_Class()
	{
		$this->result=1;
		$this->classerror = "MemberLogin_Class.";
	}
	
	/**
	 * Authenticate user with the username and password provided
	 *
	 * @param STRING
	 * @param STRING
	 *
	 * @return boolean
	 *
	 * @author John Paul Lee @version 2005
	*/
	function confirmUser($username, $password )
	{
		global $conn;
		
		$userInfo_rs = mysql_query("SELECT * FROM `Users_tbl` WHERE username='" . $username . "' AND password='" . md5( $password ) . "' AND `status`='ACTIVE'", $conn) or die(mysql_error());
		
		if( !$userInfo_rs || ( mysql_numrows($userInfo_rs) < 1 ) )
		{
		 	echo mysql_error( );
			return false;
		}
		
		$userInfo_ar = mysql_fetch_array( $userInfo_rs, MYSQL_ASSOC );
		
		$cookieID = md5( $userInfo_ar['username'] . "$$" . $_SERVER['REMOTE_ADDR'] . "##" );
		
		// query updated May 24/07 by Marina - Added $userInfo_ar['category'] argument
		// updated again June 19/07 - add 'labID' argument
		// and again on June 27/07 - added 'description' argument
		$this->currentUser = new Member_Class( $userInfo_ar["userID"], $userInfo_ar['username'], $userInfo_ar['description'], $userInfo_ar['email'], $userInfo_ar['category'], $userInfo_ar['labID'], $cookieID, $_SERVER['REMOTE_ADDR'], session_id( ), $userInfo_ar["password"]);
		
		return true;
	
	}
	

	/**
	 * Authenticate user via cookies
	 *
	 * @param STRING
	 * @param INT
	 * @param Array
	 *
	 * @return boolean
	 * @author John Paul Lee @version 2005
	*/
	function confirmUserViaCookies( $username, $cookieID, $postVariables )
	{
		global $conn;
		
		$resultSet = mysql_query( "SELECT * FROM `LoginRecord_tbl` WHERE `cookieID`='" . $cookieID . "' AND `user_ip`='" . $_SERVER['REMOTE_ADDR'] . "' AND `persistent`='T' AND status='ACTIVE'", $conn ) or die( mysql_error( ) );
		
		if ( !$resultSet || (mysql_numrows($resultSet) < 1) )
		{
			return false;
		} 
		
		$resultArray = mysql_fetch_array($resultSet);
		
		$userInformation = mysql_query("SELECT * FROM `Users_tbl` WHERE `userID`='" . $resultArray['userID'] . "' AND `status`='ACTIVE'") or die(mysql_error());
		
		if ( !$userInformation || (mysql_numrows($userInformation) < 1) )
		{
			return false;
		}
		
		$dbarray =  mysql_fetch_array($userInformation, MYSQL_ASSOC);
		
		$cookieID = md5($dbarray['username'] . "$$" . $_SERVER['REMOTE_ADDR'] . "##");
		
		$this->currentUser = new Member_Class( $dbarray['userID'], $dbarray['username'], $dbarray['email'], $cookieID, $_SERVER['REMOTE_ADDR'], session_id( ) );
		
		return true;
	
	}
	

	/**
	 * Check whether any user info is stored from previous logins
	 *
	 * @param Array
	 * @return boolean
	 *
	 * @author John Paul Lee @version 2005
	*/
	function loginCheck( $postVariables ) 
	{
		//print_r($postVariables);
		$this->logPageHit( $_SERVER["PHP_SELF"] );
		
		$this->result = 1;
	
		if (isset($postVariables["loginsubmit"]))
		{
//			echo "post login set\n";
			//global $conn;
			
			$username = $postVariables["loginusername_field"];
			$password = $postVariables["loginpassword_field"];

			if( $this->confirmUser( $username, $password ) ) 
			{
//				echo "confirm user";
				$this->updateLoginRecord( $postVariables );
				
				$_SESSION["userinfo"] = $this->currentUser;
				
				if( isset( $postVariables["persistentlogin_field"] ) )
				{
					setcookie("jpluserid", $_SESSION["userinfo"]->getUserID(), time()+(60*60*24*10000));
					setcookie("jplcookie", $_SESSION["userinfo"]->getCookieID(), time()+(60*60*24*10000));
				}
				
				//$phpBB = new PHPBB_Login();
				//$phpBB->login( $this->newUserInfo->getUserID( ) );
				
				$this->result = 0;
			}
			else
			{
				//echo "else";
				$this->result = 1;
			}
		}
		elseif( isset( $postVariables["logoutsubmit"] ) ) 
		{
//			echo "post logout set\n";
			$this->sessionLogout( );
			$this->result = 2;
		
		}
		else
		{
// 			echo "post not set and that's the way it should be\n";

			if( $this->verifySession( ) ) 
			{
//				echo "userinfo set\n";
				//$_SESSION["userinfo"] = $this->currentUser;
				$this->currentUser = $_SESSION["userinfo"];
				$this->result = 0;
			} 
			else 
			{
//				echo "no previous logins\n";
				$this->result = 1;
			}
		
		}
		
		if( $this->result == 1 ) 
		{
			if( isset( $_COOKIE["jpluserid"] ) && isset( $_COOKIE["jplcookie"] ) ) 
			{
//				echo "user login info stored\n";
				if ($this->confirmUserViaCookies($_COOKIE["jpluserid"], $_COOKIE["jplcookie"], $postVariables))
				{
					$this->updateLoginRecord( $postVariables );
				
					$_SESSION["userinfo"] = $this->currentUser;
					
					//$phpBB = new PHPBB_Login();
					//$phpBB->login( $this->newUserInfo->getUserID( ) );
					
					$this->result = 0;
				}			
			}

//			echo "no user login info stored\n";
		}
	}

	/**
	 * Log the visited page (web tracking)
	 *
	 * @param STRING
	 *
	 * @author John Paul Lee @version 2005
	*/
	function logPageHit($pageVisited)
	{
		global $conn;

		return mysql_query("INSERT INTO WebTracker_tbl (timestamp, userIP, userHost, url)" . " VALUES (NOW(), '" . $_SERVER["REMOTE_ADDR"] . "','" . addslashes(gethostbyaddr($_SERVER["REMOTE_ADDR"])) . "','" . $pageVisited . "')", $conn ) or die(mysql_error());	
	}

	/**
	 * Fetch the user's email address
	 *
	 * @param INT
	 * @return STRING
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	*/
	function getUserEmail($userID)
	{
		global $conn;

		$email = "";
		
		$user_rs = mysql_query("SELECT email FROM Users_tbl WHERE userID='" . $userID . "' AND status='ACTIVE'", $conn) or die("Error fetching user email: " . mysql_error());

		if ($user_ar = mysql_fetch_array($user_rs, MYSQL_ASSOC))
		{
			$email = $user_ar["email"];
		}

		return $email;
	}
	

	/**
	 * Verify that a given user is permitted to access a given page
	 *
	 * @param STRING
	 * @param INT
	 *
	 * @author John Paul Lee @version 2005
	 * @author Marina Olhovsky
	 * @version 3.1 2007-06-04
	 *
	 * @return boolean
	*/
	// Last modified: June 4/07, Marina - changed SecuredPages_tbl structure today; renamed 'securedPage' column to 'baseURL', and added a 'section' column to represent the menu section this page belongs to. 
	// Modified selection query accordingly
	function verifyPermissions($currentPage, $userID)
	{
		global $conn;

		$pageInformation_rs = mysql_query("SELECT * FROM `SecuredPages_tbl` WHERE `baseURL`='" . $currentPage . "' AND `status`='ACTIVE'" , $conn) or die(mysql_error());
		
		if( !$pageInformation_rs || ( mysql_numrows( $pageInformation_rs ) < 1 ) ) 
		{
			return false;
		}
		
		$pageInformation_ar = mysql_fetch_array( $pageInformation_rs, MYSQL_ASSOC );
		
// may 24/07	$permissionInformation_rs = mysql_query( "SELECT * FROM `UserPermission_tbl` WHERE `pageID`='" . $pageInformation_ar["pageID"] . "' AND `userID`='" . $userID . "' AND `status`='ACTIVE'" , $conn ) or die(mysql_error());


		// May 24/07
		// get current user's category
		$currUserCategory = "";
		
		$categoryInfo_rs = mysql_query("SELECT * FROM `Users_tbl` WHERE `userID`='" . $userID . "' AND `status`='ACTIVE'", $conn) or die(mysql_error());
		
		if ($categoryInfo_ar = mysql_fetch_array($categoryInfo_rs, MYSQL_ASSOC))
		{
			$currUserCategory = $categoryInfo_ar["category"];
		}
		else
		{
			return false;
		}
		
		$category = "";
		$permissionInformation_rs = mysql_query("SELECT * FROM `UserPermission_tbl` WHERE `pageID`='" . $pageInformation_ar["pageID"] . "' AND `status`='ACTIVE'" , $conn) or die(mysql_error());
		
		/* may 24/07
		if( !$permissionInformation_rs || ( mysql_numrows( $permissionInformation_rs ) < 1 ) ) 
		{
			return false;
		}*/
		
		// may 24/07
		if($permissionInformation_ar = mysql_fetch_array($permissionInformation_rs, MYSQL_ASSOC))
		{
			$category = $permissionInformation_ar["categoryID"];
			
			if ($category >= $currUserCategory)
			{
				return true;
			}
		}
		
// may 24/07	return true;

		return false;
	}

	/**
	 * Verify that a page is accessible to users with the given access level
	 *
	 * @param STRING
	 * @param INT
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @return boolean
	*/
	function verifySections($section, $userCategoryID)
	{
		global $conn;

		$pageInformation_rs = mysql_query("SELECT * FROM `SecuredPages_tbl` WHERE `section`='" . $section . "' AND `status`='ACTIVE'" , $conn) or die(mysql_error());
		
		if (!($pageInformation_ar = mysql_fetch_array($pageInformation_rs, MYSQL_ASSOC)))
		{
			return false;
		}
		
		// June 4/07, Marina
		$permissionInformation_rs = mysql_query("SELECT * FROM UserPermission_tbl WHERE pageID='" . $pageInformation_ar["pageID"] . "' AND status='ACTIVE'" , $conn) or die(mysql_error());

		if ($permissionInformation_ar = mysql_fetch_array($permissionInformation_rs, MYSQL_ASSOC))
		{
			$category = $permissionInformation_ar["categoryID"];

			if ($category >= $userCategoryID)
			{
				return true;
			}
		}

		return false;
	}

/* Marina, Aug. 25, 2010 - removed, obsolete
	function verify_User_Type( $usertype )
	{
		global $conn;
		$functionerror = "verify_User_Type(";
		
		$usertype_rs = mysql_query("SELECT * FROM `User_Types_tbl` a INNER JOIN `User_Status_tbl` b ON a.`userTypeID`=b.`userTypeID` "
									. "WHERE b.`userID`='" . $_SESSION["userinfo"]->getUserID() . "' AND a.`name`='" . strtolower( $usertype ) . "' "
									. "AND a.`status`='ACTIVE' AND b.`status`='ACTIVE'", $conn )
									or die( "Failure in " . $classerror . $functionerror . "1):" . mysql_error() );
									
		if( $usertype_ar = mysql_fetch_array( $usertype_rs, MYSQL_ASSOC ) )
		{
			return true;
		}
		
		return false;
	}
*/

	/**
	 * Make sure that the user's login info has been entered in the session
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @return boolean
	*/
	function VerifySession()
	{
	
		if (isset($_SESSION["userinfo"]))
		{
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Log out - unset session
	 *
	 * @author John Paul Lee @version 2005
	 *
	*/
	function sessionLogout()
	{
		session_unset( );
		session_destroy( );  
		
		setcookie( "jpluserid", "", time( )-(60*60*24*10000) );
		setcookie( "jplcookie", "", time( )-(60*60*24*10000) );
	
	}
	

	/**
	 * Update user's login record in the database (web tracking)
	 *
	 * @author John Paul Lee @version 2005
	 *
	 * @return boolean
	*/
	function updateLoginRecord($postVariables)
	{
		global $conn;
	
		$persistentLogin = "F";
	
		if( isset( $postVariables['persistentlogin_field'] ) ) {
			$persistentLogin = "T";
		}
	
		// Changed So that every time a user logins in, a new login record is created for that user!
		// Changed Mar 22/05
		mysql_query( "INSERT INTO `LoginRecord_tbl` (`crec_id`, `userID`, `timestamp`, `user_ip`, `user_host`, `cookieID`, `sessionID`, `persistent`) " 
						. "VALUES ( '','" . $this->currentUser->getUserID( ) . "',NOW( ),'" . $_SERVER["REMOTE_ADDR"] . "','" 
						. addslashes( gethostbyaddr( $_SERVER["REMOTE_ADDR"] ) ) . "','" . $this->currentUser->getCookieID( ) . "','" 
						. session_id( ) . "','" . $persistentLogin . "')", $conn ) or die(mysql_error());
	
		//$resultSet = mysql_query( "SELECT * FROM `LoginRecord_tbl` WHERE `userID`='" . $this->currentUser->getUserID( ) . "' AND `user_ip`='" 
								//	. $_SERVER["REMOTE_ADDR"] . "' AND `status`='ACTIVE'", $conn ) or die(mysql_error());
		
		//if(!$resultSet || (mysql_numrows($resultSet) < 1)){
		//	mysql_query( "INSERT INTO `LoginRecord_tbl` (`crec_id`, `userID`, `timestamp`, `user_ip`, `user_host`, `cookieID`, `sessionID`, `persistent`) " 
					//	. "VALUES ( '','" . $this->currentUser->getUserID( ) . "',NOW( ),'" . $_SERVER["REMOTE_ADDR"] . "','" 
					//	. addslashes( gethostbyaddr( $_SERVER["REMOTE_ADDR"] ) ) . "','" . $this->currentUser->getCookieID( ) . "','" 
					//	. session_id( ) . "','" . $persistentLogin . "')", $conn ) or die(mysql_error());
		//} else {
		//	mysql_query( "UPDATE `LoginRecord_tbl` SET `sessionID`='" . session_id( ) . "' WHERE `userID`='" . $this->currentUser->getUserID() 
						//. "' AND `user_ip`='" . $_SERVER["REMOTE_ADDR"] . "' AND `status`='ACTIVE'", $conn ) or die(mysql_error());
		//}
	}
	
	
	/**
	 * Output user's login info
	 * @param STRING
	*/
	function printLoginInfo($trailer)
	{
		global $conn;
		global $cgi_path;
		global $hostname;

		if (isset($_SESSION["userinfo"]))
		{
			$userInfo_rs = mysql_query( "SELECT * FROM `Users_tbl` WHERE `userID`='" . $this->currentUser->getUserID() . "'", $conn ) or die(mysql_error());

			if( $userInfo_ar = mysql_fetch_array( $userInfo_rs, MYSQL_ASSOC ) )
			{
				echo "<FORM name=\"login_form\" METHOD=\"POST\" ACTION=\"" . "index.php"  .  "\" style=\"float:left; background-color:white; padding-left:11px; padding-right:18px; padding-top:7px; white-space:nowrap;\">";

				echo "Welcome <span style=\"color:#FF0000\">" . $userInfo_ar["description"] . "</span>";

				echo "<P>";
				echo "<INPUT TYPE=\"SUBMIT\" NAME=\"logoutsubmit\" VALUE=\"Logout\" style=\"font-size:12px; elevation:above\">";
				echo "</P>";
				echo "</FORM>";
			}
		}
		else
		{
			?>
			<FORM name="logout_form" METHOD=POST ACTION="<?php echo $_SERVER["PHP_SELF"] . $trailer ?>">
				<P><SPAN class="login">To view additional sections <BR>of the website, please log in:</SPAN>

				<P>Username:<BR/>
				<INPUT type="text" value="" name="loginusername_field" size="13"><BR/>

				Password:<BR/>
				<INPUT type="password" value="" name="loginpassword_field" size="13">

				<P>Automatic Login <INPUT type="checkbox" value="" name="persistentlogin_field"></P>

				<INPUT TYPE="submit" NAME="loginsubmit" VALUE="Login" style="font-size:12px; elevation:above">
			</FORM>

			<!-- June 1, 2010: Automated Password Reset -->
			<form name="password_reset" METHOD="POST" ACTION="<?php echo $hostname . "User.php?View=6"; ?>">
				<INPUT type="hidden" name="reset_pw" value="ok">
			</form>

			<BR><SPAN class="linkShow" onClick="document.password_reset.submit();">Forgot your password?</span>
			<?php
		}
	}
}
?>
