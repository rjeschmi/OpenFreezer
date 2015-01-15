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
 * A class to represent reported bugs and/or feature requests
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 *
 * @package BugReport
 *
 * @copyright	2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
	class Bug_Class
	{
		var $bug_type;
		var $bug_descr;
		var $module;
		var $requested_by;	// INT representing user ID
		var $isClosed;

		/**
		 * Constructor
		 *
		 * @param STRING one of 'bug report' or 'feature request'
		 * @param STRING detailed description of the bug/feature request
		 * @param STRING module in which the bug was discovered (or feature request should be applied)
		 * @param INT userID of the user submitting the bug/feature report
		 * @param boolean is the bug/request open or closed
		 *
		 * @author Marina Olhovsky
		 * @version 3.1
		*/
		function Bug_Class($bug_type, $bug_descr, $module, $requested_by, $isClosed = false)
		{
			$this->bug_type = $bug_type;
			$this->bug_descr = $bug_descr;
			$this->module = $module;
			$this->requested_by = $requested_by;
			$this->isClosed = $isClosed;
		}

		/**
		 * Return the type of this request: bug or feature
		 *
		 * @author Marina Olhovsky
		 * @version 3.1
		 *
		 * @return STRING
		*/
		function getBugType()
		{
			return $this->bug_type;
		}


		/**
		 * Return the details of this request
		 *
		 * @author Marina Olhovsky
		 * @version 3.1
		 *
		 * @return STRING
		*/
		function getBugDescription()
		{
			return $this->bug_descr;
		}


		/**
		 * Return the module this request is associated with
		 *
		 * @author Marina Olhovsky
		 * @version 3.1
		 *
		 * @return STRING
		*/
		function getModule()
		{
			return $this->module;
		}


		/**
		 * Return the userID of the user submitting this request
		 *
		 * @author Marina Olhovsky
		 * @version 3.1
		 *
		 * @return INT
		*/
		function getRequestedBy()
		{
			return $this->requested_by;
		}


		/**
		 * Return the status of this request, whether it is open or closed
		 *
		 * @author Marina Olhovsky
		 * @version 3.1
		 *
		 * @return boolean
		*/
		function isClosed()
		{
			return $this->isClosed;
		}
	}
?>