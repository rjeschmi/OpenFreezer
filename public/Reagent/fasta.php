<?php
/**
 * Export sequence contents in FASTA format; differentiate between DNA, Protein and RNA sequence types
 *
 * @author Marina Olhovsky <olhovsky@lunenfeld.ca>
 * @version 3.1
 * @package Sequence
 *
 * PHP versions 4 and 5
 *
 * @copyright  2005-2010 Pawson Laboratory
 * @license    http://www.opensource.org/licenses/gpl-3.0.html GNU GPLv3
 *
 * This file is part of OpenFreezer LARISA (TM)
 *
 * OpenFreezer LARISA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * at your option) any later version.

 * OpenFreezer LARISA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with OpenFreezer LARISA.  If not, see <http://www.gnu.org/licenses/>.
 *
*/
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=" . $_REQUEST["filename"] . ".fa");
	header("Pragma: no-cache");
	header("Expires: 0");
	
	// Differentiate between protein or DNA sequence export request
	if ($_REQUEST["curr_export_sel"] == 'DNA')
	{
		$fastaContent=$_REQUEST['dnaFastaContent'];
	}
	else if ($_REQUEST["curr_export_sel"] == 'Protein')
	{
		$fastaContent=$_REQUEST['protFastaContent'];
	}
	else if ($_REQUEST["curr_export_sel"] == 'RNA')
	{
		$fastaContent=$_REQUEST['rnaFastaContent'];
	}
	
	print $fastaContent;
?>
