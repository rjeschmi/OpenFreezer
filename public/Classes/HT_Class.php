<?php
/**
 * @package Location
 *
 * PHP versions 4 and 5
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
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

/**
 * Used to create hashtables for fetching and printing container properties in the Location module
 *
 * @author John Paul Lee
 * @version 2005
 * @package Location
 *
 * @copyright  2005-2011 Mount Sinai Hospital, Toronto, Ontario
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 */
class HT_Class
{
	
	var $keys;
	var $values;
	var $size;
	
	function HT_Class()
	{
		$this->keys = array();
		$this->values = array();
		$this->size = 0;
	}
	
	function add( $key, $value )
	{
		if( in_array( $key, $this->keys, true ) )
		{
			return false;
		}
		
		$this->keys[ $this->size ] = $key; 
		
		$this->values[ $this->size ] = $value;
		
		$this->size++;
		
		return true;
		
	}
	
	function update( $key, $value )
	{
		if( in_array( $key, $this->keys, true ) )
		{
			//$this->keys[ $this->size ] = $key; 
		
			$realkey = array_search( $key, $this->keys, false );
		
			unset( $this->values[ $realkey ] );
		
			$this->values[ $realkey ] = $value;
		
			return true;
		}
		
		return false;
		
	}
	
	function get( $key )
	{
		if( in_array( $key, $this->keys, false ) )
		{
			$foundkey = array_search( $key, $this->keys, false );
			
			return $this->values[ $foundkey ];
		}
		
		return -1;
	}
	
	function printAll()
	{
		echo "----------------<BR>";
		echo "Hashtable print (size=" . $this->size . ":<BR>Keys=";
		print_r( $this->keys );
		echo "<BR>-----------------<BR>Values=";
		print_r( $this->values );
		echo "<BR>";
	}
	
	
}

?>