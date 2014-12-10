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
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

/**
 * Include/require statements
 *
*/

include_once("Bug_Class.php");

/**
 * Auxiliary class to handle bug reports and feature requests
 * Written August 2, 2010
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package BugReport
 *
 * @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
class Bug_Function_Class
{
	/**
	* Default constructor
	*/
	function Bug_Function_Class()
	{}
	

	/**
	 * Retrieve ALL the current bug/feature requests from the database
	 *
	 * @author Marina Olhovsky
	 * @version 3.1
	 *
	 * @return Array
	*/
	function fetchAllBugs()
	{
		global $conn;

		$bugs = Array();

		$active_bugs_rs = mysql_query("SELECT bug_type, bug_descr, module, requested_by FROM BugReport_tbl WHERE status='ACTIVE' AND is_closed='NO'");

		while ($active_bugs_ar = mysql_fetch_array($active_bugs_rs, MYSQL_ASSOC))
		{
			$bug_type = $active_bugs_ar["bug_type"];
			$bug_descr = $active_bugs_ar["bug_descr"];
			$module = $active_bugs_ar["module"];
			$requested_by = $active_bugs_ar["requested_by"];

			$tmpBug = new Bug_Class($bug_type, $bug_descr, $module, $requested_by);
			$bugs[] = $tmpBug;
		}

		return $bugs;
	}
}
?>
