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
* @author John Paul Lee @version 2005
*
* @author     Marina Olhovsky <olhosvky@lunenfeld.ca>
* @version    3.1
* @package All
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/

// Code for a basic start/stop timer 
// Will output the time difference in seconds
// If the time is less than a second, will output the microseconds

/**
* Code for a basic start/stop timer 
*
* Will output the time difference in seconds
*
* If the time is less than a second, will output the microseconds
*
* @author John Paul Lee @version 2005
*
* @package All
*
* @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
* @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
*/
class StopWatch
{
	var $starttime_m;
	var $starttime_s;
	var $endtime_m;
	var $endtime_s;
	
	function StopWatch()
	{
		$this->starttime_m = 0;
		$this->starttime_s = 0;
		$this->endtime_m = 0;
		$this->endtime_s = 0;
	}
	
	function startStopWatch()
	{
		list( $starttime_mt, $starttime_st ) = explode(" ", microtime());
		$this->starttime_m = $starttime_mt;
		$this->starttime_s = $starttime_st;
	}
	
	function stopStopWatch()
	{
		list( $endtime_mt, $endtime_st ) = explode(" ", microtime());
		$this->endtime_m = $endtime_mt;
		$this->endtime_s = $endtime_st;
	}
	
	function printStopWatch_verbose()
	{
		//echo "ST: " . $this->starttime_s . " - " . $this->starttime_m . "<br>";
		//echo "ET: " . $this->endtime_s . " - " .  $this->endtime_m . "<br>";
		
		$temp = $this->endtime_s - $this->starttime_s;
		if( $temp > 0 )
		{
			echo "Time (sec): " . ( ( $this->endtime_s + $this->endtime_m ) - ( $this->starttime_s + $this->starttime_m ) );
		}
		else
		{
			$temp = $this->endtime_m - $this->starttime_m;
			echo "Time (micro): " . $temp;
		}
	}
	
	function resetStopWatch()
	{
		$this->starttime_m = 0;
		$this->starttime_s = 0;
		$this->endtime_m = 0;
		$this->endtime_s = 0;
	}
}
?>