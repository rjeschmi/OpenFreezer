//CHANGE THIS TO YOUR OPENFREEZER URL (NOTE THE TRAILING SLASH)
var hostName = "Your OpenFreezer URL, e.g. http://www.my_openfreezer.org/";

var cgiPath = "/cgi/";
var parentsValid = true;
var currReagentType;
var wellResult = true;
var parentResult = true;
var addElementError = false;	// May 26, 2010
var goback = false;

// June 3/08
function redirect(url)
{
	window.location = url;
}

function openPage(menuOpt)
{
	if (document.getElementById("curr_user_hidden"))
	{
		userID = document.getElementById("curr_user_hidden").value;

		// remember to trim, have extra whitespace for padding!
		if (trimAll(menuOpt.text) == 'Personal page')
			redirectToCurrentUserDetailedView(userID);
		else
		{
			if (menuOpt.value != "")
			{
				redirect(menuOpt.value);
			}
		}
	}
	else
	{
		if (menuOpt.value != "")
		{
			redirect(menuOpt.value);
		}
	}
}


function cancelReagentCreation()
{
	url = window.location.toString();

	curr_username = document.getElementById('curr_username_hidden');
	
	// get reagent type from URL - cancellation should be done for Vectors and Inserts, later maybe for other types too, but because input name reagent_type_hidden is used in many places throughout the code, cannot assign an ID to it
	rid_index = url.indexOf('&rID=')+5;
	rID = url.substring(rid_index, url.indexOf('&', rid_index));

	url = cgiPath + "create.py";

	xmlhttp = createXML();
	xmlhttp.open("POST", url, false);
	xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');

	xmlhttp.send('cancel_creation=1&reagent_id_hidden=' + rID + '&curr_username=' + curr_username);

	xmlhttp.onreadystatechange = function(){};
}


// Reagent functions
function showMorphology()
{
	var list_id = "morphology_list";
	var text_id = "morphology_txt";	
	var listElement = document.getElementById(list_id);
	var txtElem = document.getElementById(text_id);
	var selectedInd = listElement.selectedIndex;
	var selectedValue = listElement[selectedInd].value;

	if (selectedValue.toLowerCase() == 'other')
	{
		// Show the textbox
		txtElem.style.display = "inline";

		// Set the focus to the new textbox (place cursor inside)
		txtElem.focus();
	}
	else
	{
		// Hide the textbox
		txtElem.style.display="none";
	}
}

function showTissueType()
{
	var list_id = "tissue_type_list";
	var text_id = "tissue_type_txt";	
	var listElement = document.getElementById(list_id);
	var txtElem = document.getElementById(text_id);
	var selectedInd = listElement.selectedIndex;
	var selectedValue = listElement[selectedInd].value;

	if (selectedValue.toLowerCase() == 'other')
	{
		// Show the textbox
		txtElem.style.display = "inline";

		// Set the focus to the new textbox (place cursor inside)
		txtElem.focus();
	}
	else
	{
		// Hide the textbox
		txtElem.style.display="none";
	}
}

function showSpeciesBox()
{
	var list_id = "species_list";
	var text_id = "species_txt";	
	var listElement = document.getElementById(list_id);
	var txtElem = document.getElementById(text_id);
	var selectedInd = listElement.selectedIndex;
	var selectedValue = listElement[selectedInd].value;

	if (selectedValue.toLowerCase() == 'other')
	{
		// Show the textbox
		txtElem.style.display = "inline";

		// Set the focus to the new textbox (place cursor inside)
		txtElem.focus();
	}
	else
	{
		// Hide the textbox
		txtElem.style.display="none";
	}
}

function showDevStage()
{
	var list_id = "dev_stage_list";
	var text_id = "dev_stage_txt";	
	var listElement = document.getElementById(list_id);
	var txtElem = document.getElementById(text_id);
	var selectedInd = listElement.selectedIndex;
	var selectedValue = listElement[selectedInd].value;

	if (selectedValue.toLowerCase() == 'other')
	{
		// Show the textbox
		txtElem.style.display = "inline";

		// Set the focus to the new textbox (place cursor inside)
		txtElem.focus();
	}
	else
	{
		// Hide the textbox
		txtElem.style.display="none";
	}
}

function showProtocolBox()
{
	var list_id = "protocol_list";
	var text_id = "protocol_txt";	
	var listElement = document.getElementById(list_id);
	var txtElem = document.getElementById(text_id);
	var selectedInd = listElement.selectedIndex;
	var selectedValue = listElement[selectedInd].value;

	if (selectedValue.toLowerCase() == 'other')
	{
		// Show the textbox
		txtElem.style.display="inline";

		// Set the focus to the new textbox (place cursor inside)
		txtElem.focus();
	}
	else
	{
		// Hide the textbox
		txtElem.style.display="none";
	}
}

// March 3/08: Changing function signature - passing list and text IDs as arguments
function showTagTypeBox(list_id, text_id)
{
	var listElement = document.getElementById(list_id);
	var txtElem = document.getElementById(text_id);
	
	var selectedInd = listElement.selectedIndex;
	
	var selectedValue = listElement[selectedInd].value;
	
	if (selectedValue.toLowerCase() == 'other')
	{
		// Show the textbox
		txtElem.style.display="inline";

		// Set the focus to the new textbox (place cursor inside)
		txtElem.focus();
	}
	else
	{
		// Hide the textbox
		txtElem.style.display="none";
	}
}

// April 23, 2010
// Taken from http://www.webtoolkit.info/javascript-url-decode-encode.html
function utf8_decode(utftext)
{
	var string = "";
	var i = 0;
	var c = c1 = c2 = 0;
 
	while (i < utftext.length)
	{
// alert(utftext.charAt(i));

		c = utftext.charCodeAt(i);

		if (c < 128)
		{
// alert(c + " < 128");
			string += String.fromCharCode(c);
			i++;
		}
		else if((c > 191) && (c < 224))
		{
// alert(c + " > 191 and < 224");

			c2 = utftext.charCodeAt(i+1);
			string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
			i += 2;
		}
		else
		{
// alert(c + " > 224");
			c2 = utftext.charCodeAt(i+1);
			c3 = utftext.charCodeAt(i+2);
			string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
			i += 3;
		}
	}

	return string;
}



// April 6/07, Marina: Same rationale for Checkbox properties: to differentiate between Insert and Cell Line Alternate ID properties, call the show() function with specific name parameter
function showSpecificOtherCheckbox(checkTextboxID)
{
	var checkTextbox = document.getElementById(checkTextboxID);

	if (checkTextbox.style.display == "none")
	{
		checkTextbox.style.display = "inline";
		checkTextbox.focus();
	}
	else
	{
		checkTextbox.style.display = "none";
	}
}

// Added April 6/07, Marina: Since the new layout may contain multiple expandable dropdown lists for different reagent types, wrote this function, which takes as input specific IDs that contain the reagent type embedded in them; then there's no confusion
function showSpecificOtherTextbox(listID, textID)
{
// alert(textID + " before");

	textID = unescape(textID);

// alert(textID + " after");
	var listElement = document.getElementById(listID);
// alert(listID);
	var txtElem = document.getElementById(textID);
// 	alert(textID);
	var selectedInd = listElement.selectedIndex;
// alert(selectedInd);
	var selectedValue = listElement[selectedInd].value;
// alert(selectedValue);
	
	if (selectedValue.toLowerCase() == 'other')
	{
		// Show the textbox
		txtElem.style.display="inline";

		// Set the focus to the new textbox (place cursor inside)
		txtElem.focus();
	}
	else
	{
		// Hide the textbox
		if (txtElem)
			txtElem.style.display="none";
	}
}

function showExprSystBox()
{
	var list_id = "expr_syst_list";
	var text_id = "expr_syst_list_txt";	
	var listElement = document.getElementById(list_id);
	var txtElem = document.getElementById(text_id);
	var selectedInd = listElement.selectedIndex;
	var selectedValue = listElement[selectedInd].value;

	if (selectedValue.toLowerCase() == 'other')
	{
		// Show the textbox
		txtElem.style.display="inline";

		// Set the focus to the new textbox (place cursor inside)
		txtElem.focus();
	}
	else
	{
		// Hide the textbox
		txtElem.style.display="none";
	}
}

function showPromoText()
{
	var list_id = "promoter_list";
	var text_id = "promo_txt";	
	var listElement = document.getElementById(list_id);

	// Check to see if the textbox exists and create it ONLY if it doesn't
	var txtElem = document.getElementById(text_id);
	var selectedInd = listElement.selectedIndex;
	var selectedValue = listElement[selectedInd].value;

	if (selectedValue.toLowerCase() == 'other')
	{
		// Show the textbox
		txtElem.style.display="inline";

		// Set the focus to the new textbox (place cursor inside)
		txtElem.focus();
	}
	else
	{
		// Hide the textbox
		txtElem.style.display="none";
	}
}

function showCMText()
{
	var list_id = "cm_list";
	var text_id = "cm_txt";	
	var listElement = document.getElementById(list_id);

	// Check to see if the textbox exists and create it ONLY if it doesn't
	var txtElem = document.getElementById(text_id);
	var selectedInd = listElement.selectedIndex;
	var selectedValue = listElement[selectedInd].value;

	if (selectedValue.toLowerCase() == 'other')
	{
		// Show the textbox
		txtElem.style.display="inline";

		// Set the focus to the new textbox (place cursor inside)
		txtElem.focus();
	}
	else
	{
		// Hide the textbox
		txtElem.style.display="none";
	}
}

// Oct. 15/07: Show/hide restriction enzyme textbox if 'Other' is selected from the dropdown list
// Last modified Feb. 11/08
function showHideFivePrimeOther(subtype)
{
	var fpList = document.getElementById("fpcs_list_1");

	// different ID for Novel Vectors and Inserts (no subtype postfix)
	if (!fpList)
	{
		var fpList = document.getElementById("fpcs_list");
		var fpText = document.getElementById("fpcs_txt");
	}
	else
		var fpText = document.getElementById("fpcs_txt_" + subtype);

	var selectedInd = fpList.selectedIndex;
	var selectedValue = fpList[selectedInd].value;

	if (selectedValue.toLowerCase() == 'other')
	{
		// Show the textbox
		fpText.style.display="inline";

		// Set the focus to the new textbox (place cursor inside)
		fpText.focus();
	}
	else
	{
		fpText.style.display="none";
	}
}


// Oct. 15/07: Show/hide restriction enzyme textbox if 'Other' is selected from the dropdown list
function showHideThreePrimeOther(subtype)
{
//	var allSelect = document.getElementsByTagName("SELECT");

//	for (a in allSelect)
//	{
//		tmpList = allSelect[a];

//		if (tmpList.id == "tpcs_list_1")
//		{
 			var tpList = document.getElementById("tpcs_list_1");
			var tpText = document.getElementById("tpcs_txt_" + subtype);
		
			var selectedInd = tpList.selectedIndex;
			var selectedValue = tpList[selectedInd].value;
		
			if (selectedValue.toLowerCase() == 'other')
			{
				// Show the textbox
				tpText.style.display="inline";
		
				// Set the focus to the new textbox (place cursor inside)
				tpText.focus();
			}
 			else
 			{
				// Hide the textbox
 				tpText.style.display="none";
 			}
//		}
//	}
}

function cancelModify(rID)
{
	window.location.href = hostName + "Reagent.php?View=6&rid=" + rID;
}

// May 13/08
// Invoked at step 2 of reagent creation, where sequence is entered
// Validation varies according to reagent type
function verifyReagentSequenceCreation(rType, subtype)
{
	if (document.getElementById("cancel_set"))
	{
		var cancelSet = document.getElementById("cancel_set").value;
	}
	else
	{
		var cancelSet = 0;
	}

	if (cancelSet != 1)
	{
		if (rType == 'Insert')
		{
			return verifyInsertCreation();
		}
		else if (rType == 'Vector')
		{
// Nov. 6/08 - verification needed for all vector types, not just novel - code is the same
// 			if (subtype.toLowerCase() == 'novel')
// 			{
				return verifyNovelVectorSequence();
// 			}
		}
	}
}


// Feb. 7/08: Called at Insert creation to make sure sequence is filled in
function verifyInsertCreation()
{
	var dna_sequence = document.getElementById("dna_sequence_Insert");
	var sequence_warning = document.getElementById("sequence_warning");
	var seq = trimAll(dna_sequence.value);

	if (seq == "")
	{
		alert("Please provide an Insert sequence");
		dna_sequence.focus();
		sequence_warning.style.display = "inline";

		return false;
	}
	else
		sequence_warning.style.display = "none";

	for (i = 0; i < seq.length; i++)
	{
		aChar = seq.charAt(i).toLowerCase();

		if ( (aChar != 'a') && (aChar != 'c') && (aChar != 'g') && (aChar != 't') && (aChar != 'n'))
		{
			var answer = confirm("Insert sequence contains characters other than A, C, G, T or N.  Saving will remove these extra characters.  Are you sure you wish to proceed?");

			if (answer == 0)
			{
				dna_sequence.focus();
				return false;
			}
			else
			{
				// Filter unwanted chars first, then save
				new_sequence = filterDNASeq(seq);
				dna_sequence.value = new_sequence;
				break;
			}
		}
	}

//	if (!verifyCDNA(true))
//		return false;

	return verifyCloningSites("insert");
}


function verifySitesAtCreation()
{
	return verifyHybridSites('fpcs', 'fpcs_list') && verifyHybridSites('tpcs', 'fpcs_list');
}


function verifySites(sitesWarningsArray)
{
// Array('pv_fpcs_list':'pv_fpcs_warning', 'pv_tpcs_list':'pv_tpcs_warning', 'insert_fpcs_list':'insert_fpcs_warning', 'insert_tpcs_list':'insert_tpcs_warning')
	radios = document.getElementsByName("warning_change_input");

	for (i = 0; i < radios.length; i++)
	{
		if (radios[i].checked)
		{
			if ((radios[i].value == "restart") || (radios[i].value == "change_parents_warning_option"))
				return true;

			else if (radios[i].value == "make_hybrid_option")
			{
				for (listID in sitesWarningsArray)
				{
			// 		alert(listID);
			// 		alert(sitesWarningsArray[listID]);
			
					if (document.getElementById(listID))
					{
						aList = document.getElementById(listID);
						aSelVal = aList[aList.selectedIndex].value;
			
						if ((aSelVal.toLowerCase() == 'other') || (aSelVal == ''))
						{
							alert("Please fill in the values for all cloning sites");
							aList.focus();
			
							aWarnID = sitesWarningsArray[listID];
							aWarning = document.getElementById(aWarnID);
			
							if (aWarning)
							{
								aWarning.style.display = "inline";
			
								// hide the rest
								allSpans = document.getElementsByTagName("SPAN");
			
								for (i=0; i < allSpans.length; i++)
								{
			aSpan = allSpans[i];
			
			// alert(aSpan);
			// alert(aSpan.id);
									if (aSpan && aSpan.id && (aSpan.id != aWarning.id))
									{
										aSpan.style.display = "none";
									}
								}
							}
			
							return false;
						}
					}
				}
			}
		}
	}

	return true;


}

// Oct. 15/07: Verify restriction enzyme input format - make sure it contains no digits or slashes
function verifyCloningSites(subtype)
{
	if (isNumeric(subtype))
	{
		var selectedFivePrime = document.getElementById("fpcs_list_" + subtype);
		var selectedThreePrime = document.getElementById("tpcs_list_" + subtype);
	}
	else
	{
		var selectedFivePrime = document.getElementById("fpcs_list_1");
		var selectedThreePrime = document.getElementById("tpcs_list_1");
	}

	// Aug. 14/09: last attempt
	if (!selectedFivePrime)
		var selectedFivePrime = document.getElementById("fpcs_list");

	if (!selectedThreePrime)
		var selectedThreePrime = document.getElementById("tpcs_list");

	// Nov. 13/08
	if (!selectedFivePrime)
	{
		alert("Please indicate 5' cloning site, including start and stop positions, or select 'None' if not known.");
		return false;
	}
	
	if (!selectedThreePrime)
	{
		alert("Please indicate 3' cloning site, including start and stop positions, or select 'None' if not known.");
		return false;
	}

	var fpSelInd = selectedFivePrime.selectedIndex;
	var fpSelVal = selectedFivePrime[fpSelInd].value;

	// Feb. 5/08
	var tpSelInd = selectedThreePrime.selectedIndex;
	var tpSelVal = selectedThreePrime[tpSelInd].value;

	var digits = "0123456789";

	// feb 5/08 - hide name warning	
	var tp_warning = document.getElementById("tp_warning");
	var fp_warning = document.getElementById("fp_warning");

	var name_warning = document.getElementById("insert_name_warning");

	if (name_warning)
		name_warning.style.display = "none";

	var sequence_warning = document.getElementById("sequence_warning");

	if (sequence_warning)
		sequence_warning.style.display = "none";

	if (subtype == "insert")
	{
 		var oc_warning = document.getElementById("oc_warning");
 		var it_warning = document.getElementById("it_warning");

		if (oc_warning)
			oc_warning.style.display = "none";

		if (it_warning)
			it_warning.style.display = "none";
	}

	// Feb. 5/08: Make sure cloning sites are never left blank
	if (fpSelVal == "")
	{
		// hide other warnings if shown
		if (tp_warning)
			tp_warning.style.display = "none";

		alert("Please provide a value for the 5' restriction site (select option 'None' from the list if you do not wish to provide a cloning site at this moment)");

		if (fp_warning)
			fp_warning.style.display = "inline";

		selectedFivePrime.focus();
		return false;
	}
	else
	{
		// hide warnings
		if (fp_warning)
			fp_warning.style.display = "none";

// 		document.getElementById("tp_warning").style.display = "none";
	}

	if (tpSelVal == "")
	{
		// hide 5' warning
		if (fp_warning)
			fp_warning.style.display = "none";

		alert("Please provide a value for the 3' restriction site (select option 'None' from the list if you do not wish to provide a cloning site at this moment)");

		if (tp_warning) 
			tp_warning.style.display = "inline";

		selectedThreePrime.focus();

		return false;
	}
	else
	{
		// hide warnings
// 		document.getElementById("fp_warning").style.display = "none";
// 		document.getElementById("tp_warning").style.display = "none";

		if (tp_warning) 
			tp_warning.style.display = "none";
	}

	// DO THE CHECK IFFFFFFFFFFFF THERE'S A HYBRID INPUT - i.e. 'Other' value selected from the list and the textbox is visible
	if (fpSelVal.toLowerCase() == 'other')
	{
		// hide empty sites warnings
		document.getElementById("fp_warning").style.display = "none";
		document.getElementById("tp_warning").style.display = "none";

		// moved here Feb. 11/08
		var fpTxt = document.getElementById("fpcs_txt_" + subtype);	
		var fpSite = fpTxt.value;

		// 5' site
		for (d in digits)
		{
			if (fpSite.indexOf(d) >= 0)
			{
				alert("Site names may contain Roman numerals only.  Please verify your input.");
				fpTxt.focus();
				return false;
			}
	
			if (fpSite.indexOf('/') >= 0)
			{
				alert("Hybrid site names must be hyphen-delimited. Please verify your input");
				fpTxt.focus();
				return false;
			}
	
			// Make sure the hybrid restriction site provided is of the form EnzI-EnzII, where EnzI and EnzII both match a REBASE enzyme value and are separated by hyphen, no spaces
			var hyphenIndex = fpSite.indexOf('-');
	
			if (hyphenIndex < 0)
			{
				alert("Hybrid restriction site must be of the form 'SiteI-SiteII', where SiteI and SiteII both match REBASE enzyme names and are separated by a hyphen.  Please verify your input for the 5' hybrid restriction site.");
				fpTxt.focus();
				return false;
			}
	
			var fp_h1 = trimAll(fpSite.substring(0, hyphenIndex));
			var fp_h2 = trimAll(fpSite.substring(hyphenIndex+1));
		
			var h1_matches = 0;
			var h2_matches = 0;
		
			for (i = 0; i < selectedFivePrime.options.length; i++)
			{
				enz = selectedFivePrime.options[i].value;
		
				if (enz == fp_h1)
				{
					h1_matches++;
					break;
				}
			}
		
			for (i = 0; i < selectedFivePrime.options.length; i++)
			{
				enz = selectedFivePrime.options[i].value;
		
				if (enz == fp_h2)
				{
					h2_matches++;
					break;
				}
			}
		
			if ((h1_matches > 0) && (h2_matches > 0))
			{
// feb 7/08			return true;	//  NO!!! won't go to 3' check if returns
				break;		// feb. 7/08
			}
			else
			{
				alert("Hybrid restriction site must be of the form 'SiteI-SiteII', where SiteI and SiteII both match REBASE enzyme names and are separated by a hyphen.  Please verify your input for the 5' hybrid restriction site.");
				fpTxt.focus();
				return false;
			}
		}
	}

	// 3' site
	var tpTxt = document.getElementById("tpcs_txt_" + subtype);	// moved here feb. 11/08

	if (tpTxt)
		var tpSite = tpTxt.value;

	if (tpSelVal.toLowerCase() == 'other')
	{
		for (d in digits)
		{
			if (tpSite.indexOf(d) >= 0)
			{
				alert("Site names may contain Roman numerals only.  Please verify your input.");
				tpTxt.focus();
				return false;
			}
	
			if (tpSite.indexOf('/') >= 0)
			{
				alert("Hybrid sites must be hyphen-delimited. Please verify your input");
				tpTxt.focus();
				return false;
			}
	
			// Make sure the hybrid restriction site provided is of the form EnzI-EnzII, where EnzI and EnzII both match a REBASE enzyme value and are separated by hyphen, no spaces
			var hyphenIndex = tpSite.indexOf('-');
	
			if (hyphenIndex < 0)
			{
				alert("Hybrid restriction site must be of the form 'SiteI-SiteII', where SiteI and SiteII both match REBASE enzyme names and are separated by a hyphen.  Please verify your input for the 3' hybrid restriction site.");
				tpTxt.focus();
				return false;
			}
	
			var tp_h1 = trimAll(tpSite.substring(0, hyphenIndex));
			var tp_h2 = trimAll(tpSite.substring(hyphenIndex+1));
		
			var h1_matches = 0;
			var h2_matches = 0;
		
			for (i = 0; i < selectedThreePrime.options.length; i++)
			{
				enz = selectedThreePrime.options[i].value;
		
				if (enz == tp_h1)
				{
					h1_matches++;
					break;
				}
			}
		
			for (i = 0; i < selectedThreePrime.options.length; i++)
			{
				enz = selectedThreePrime.options[i].value;
		
				if (enz == tp_h2)
				{
					h2_matches++;
					break;
				}
			}
		
			if ((h1_matches > 0) && (h2_matches > 0))
			{
// 				return true;	// NO!!
				break;		// feb. 7/08
			}
			else
			{
				alert("Hybrid restriction site must be of the form 'SiteI-SiteII', where SiteI and SiteII both match REBASE enzyme names and are separated by a hyphen.  Please verify your input for the 3' hybrid restriction site.");
				tpTxt.focus();
				return false;
			}
		}
	}

	return true;
}


// April 19/07, last updated June 3/08
// On Vector creation warning page: if user decides to change input parent values, enable the appropriate textbox
function enableOrDisableParents(rType, subtype)
{
	switch (rType)
	{
		case 'Vector':
			var pvID;
			var secondParent;

			if ((subtype == 'nonrecomb') || (subtype == 'gateway_entry'))
			{
				pvID = document.getElementById("nr_pv_id");
				secondParent = document.getElementById("insert");
			}

			else if ((subtype == 'recomb') || (subtype == 'gateway_expression'))
			{
				pvID = document.getElementById("rec_pv_id");
				secondParent = document.getElementById("ipv_id");
			}

			var radio = document.getElementById("change_parents_warning_option");

			if (radio.checked == true)
			{
				pvID.readOnly = false;
				secondParent.readOnly = false;

				pvID.style.color = "#FF0000";
				secondParent.style.color = "#FF0000";

				// May 14/08: Color cell white
				pvID.style.backgroundColor = "#FFFFFF";
				secondParent.style.backgroundColor = "#FFFFFF";

				pvID.focus();	// No, removed April 30/08 - what if it's the Insert that needs changing?
			}
			else
			{
				pvID.readOnly = true;
				secondParent.readOnly = true;

				pvID.style.color = "brown";
				secondParent.style.color = "brown";

				// May 14/08: Color cell gray to emphasize that it's disabled
				pvID.style.backgroundColor = "#E8E8E8";
				secondParent.style.backgroundColor = "#E8E8E8";
			}

		break;
		
		case 'Insert':
			var pvID = document.getElementById("piv_id");
			var senseID = document.getElementById("sense_id");
			var antisenseID = document.getElementById("antisense_id");

			var radio = document.getElementById("change_parents_warning_option");

			if (radio.checked == true)
			{
				pvID.readOnly = false;
				senseID.readOnly = false;
				antisenseID.readOnly = false;

				pvID.style.color = "#FF0000";
				senseID.style.color = "#FF0000";
				antisenseID.style.color = "#FF0000";

				// May 14/08: Color cell white
				pvID.style.backgroundColor = "#FFFFFF";
				senseID.style.backgroundColor = "#FFFFFF";
				antisenseID.style.backgroundColor = "#FFFFFF";

				pvID.focus();
			}
			else
			{
				pvID.readOnly = true;
				senseID.readOnly = true;
				antisenseID.readOnly = true;

				pvID.style.color = "brown";
				senseID.style.color = "brown";
				antisenseID.style.color = "brown";

				// May 14/08: Color cell gray to emphasize that it's disabled
				pvID.style.backgroundColor = "#E8E8E8";
				senseID.style.backgroundColor = "#E8E8E8";
				antisenseID.style.backgroundColor = "#E8E8E8";
			}

		break;

		case 'CellLine':
			var pvID = document.getElementById("clpv_id");
			var pclID = document.getElementById("pcl_id");

			var radio = document.getElementById("change_parents_warning_option");

			if (radio.checked == true)
			{
				pvID.readOnly = false;
				pclID.readOnly = false;

				pvID.style.color = "#FF0000";
				pclID.style.color = "#FF0000";

				// May 14/08: Color cell white
				pvID.style.backgroundColor = "#FFFFFF";
				pclID.style.backgroundColor = "#FFFFFF";

				pvID.focus();
			}
			else
			{
				pvID.readOnly = true;
				pclID.readOnly = true;

				pvID.style.color = "brown";
				pclID.style.color = "brown";

				// May 14/08: Color cell gray to emphasize that it's disabled
				pvID.style.backgroundColor = "#E8E8E8";
				pclID.style.backgroundColor = "#E8E8E8";
			}

		break;
	}
}

// August 22/07: Same as above but for recombination vector creation
function enableOrDisableRecombParents()
{
	var pvID = document.getElementById("rec_pv_id");
	var insertID = document.getElementById("ipv_id");
	var radio = document.getElementById("change_parents_warning_option");

	if (radio.checked == true)
	{
		pvID.readOnly = false;
		insertID.readOnly = false;

		pvID.style.color = "#FF0000";
		insertID.style.color = "#FF0000";

		// May 14/08: Color cell white
		pvID.style.backgroundColor = "#FFFFFF";
		insertID.style.backgroundColor = "#FFFFFF";

		pvID.focus();
	}
	else
	{
		pvID.readOnly = true;
		insertID.readOnly = true;

		pvID.style.color = "brown";
		insertID.style.color = "brown";

		// May 14/08: Color cell gray to emphasize that it's disabled
		pvID.style.backgroundColor = "#E8E8E8";
		insertID.style.backgroundColor = "#E8E8E8";
	}
}

// August 23/07: Same thing for cell lines
function enableOrDisableCellLineParents()
{
	var pvID = document.getElementById("cl_pv_id");
	var clID = document.getElementById("parent_cell_line_id");
	var radio = document.getElementById("change_parents_warning_option");

	if (radio.checked == true)
	{
		pvID.readOnly = false;
		clID.readOnly = false;

		pvID.style.color = "#FF0000";
		clID.style.color = "#FF0000";

		// May 14/08: Color cell white
		pvID.style.backgroundColor = "#FFFFFF";
		clID.style.backgroundColor = "#FFFFFF";

		pvID.focus();
	}
	else
	{
		pvID.readOnly = true;
		clID.readOnly = true;

		pvID.style.color = "brown";
		clID.style.color = "brown";

		// May 14/08: Color cell gray to emphasize that it's disabled
		pvID.style.backgroundColor = "#E8E8E8";
		clID.style.backgroundColor = "#E8E8E8";
	}
}

// Update Feb. 25/09: added 'reverseInsertCheckbox'
function showHideCustomSites()
{
	var chkbox = document.getElementById("customSitesCheckbox");
	var sitesTbl = document.getElementById("customCloningSites");

	var reverseComplementCheckbox = document.getElementById("reverseComplementCheckbox");
	var reverseInsertCheckbox = document.getElementById("reverseInserttCheckbox");

	if (chkbox)
	{
		if (chkbox.checked)
		{
			sitesTbl.style.display = "block";
		}
		else
		{
			sitesTbl.style.display = "none";
		}
	}
	else if (reverseComplementCheckbox)
	{
		if (reverseComplementCheckbox.checked)
		{
			sitesTbl.style.display = "block";
		}
		else
		{
			sitesTbl.style.display = "none";
		}
	}
	else if (reverseInsertCheckbox)
	{
		if (reverseComplementCheckbox.checked)
		{
			sitesTbl.style.display = "block";
		}
		else
		{
			sitesTbl.style.display = "none";
		}
	}
	else
	{
		var radio = document.getElementById("make_hybrid_option");

		if (radio.checked == true)
		{
			sitesTbl.style.display = "block";
		}
		else
		{
			sitesTbl.style.display = "none";
		}
	}
}

function enableOrDisableSites()
{
// 	var sitesTbl = document.getElementById("cloning_sites");
// 
// 	if (sitesTbl && (sitesTbl.style.display == "none"))
// 	{
// 		sitesTbl.style.display = "inline";
// 	}

	var radio = document.getElementById("make_hybrid_option");
	
// 	var fpcs = document.getElementById("fpcs");
// 	var tpcs = document.getElementById("tpcs");

	var insert_fpcs = document.getElementById("fpcs_list");
	var insert_tpcs = document.getElementById("tpcs_list");

	var pv_fpcs = document.getElementById("fpcs_list_1");
	var pv_tpcs = document.getElementById("tpcs_list_1");

// 	var fpcs_comm = document.getElementById("fpcs_comment");
// 	var tpcs_comm = document.getElementById("tpcs_comment");

// 	var main_fpcs_captn = document.getElementById("main_5_site_caption");
// 	var main_tpcs_captn = document.getElementById("main_3_site_caption");
		
// 	var alt_fpcs_captn = document.getElementById("alt_5_site_caption");
// 	var alt_tpcs_captn = document.getElementById("alt_3_site_caption");

	if (radio.checked == true)
	{
		pv_fpcs.disabled = false;
		pv_tpcs.disabled = false;

		insert_fpcs.disabled = false;
		insert_tpcs.disabled = false;

// 		fpcs.style.color = "#FF0000";
// 		tpcs.style.color = "#FF0000";

// 		fpcs_comm.style.display = "table-row";
// 		tpcs_comm.style.display = "table-row";

// 		main_fpcs_captn.style.display = "none";
// 		main_tpcs_captn.style.display = "none";

// 		alt_fpcs_captn.style.display = "table-row";
// 		alt_tpcs_captn.style.display = "table-row";

		// May 14/08: Color cell white
// 		fpcs.style.backgroundColor = "#FFFFFF";
// 		tpcs.style.backgroundColor = "#FFFFFF";
		
// 		fpcs.focus();
	}
	else
	{
		pv_fpcs.disabled = true;
		pv_tpcs.disabled = true;

		insert_fpcs.disabled = true;
		insert_tpcs.disabled = true;

// 		fpcs.style.color = "brown";
// 		tpcs.style.color = "brown";

// 		fpcs_comm.style.display = "none";
// 		tpcs_comm.style.display = "none";
		
// 		main_fpcs_captn.style.display = "table-row";
// 		main_tpcs_captn.style.display = "table-row";

// 		alt_fpcs_captn.style.display = "none";
// 		alt_tpcs_captn.style.display = "none";

		// May 14/08: Color cell gray to emphasize that it's disabled
// 		fpcs.style.backgroundColor = "#E8E8E8";
// 		tpcs.style.backgroundColor = "#E8E8E8";
	}
}


function getReagentType()
{
	var reagentSelectionBox = document.getElementById("reagentTypes");

 	if (reagentSelectionBox)
 	{
		var rTypeSelectedInd = reagentSelectionBox.selectedIndex;
		var reagentType = reagentSelectionBox[rTypeSelectedInd].value;

		return reagentType;
 	}

 	return null;
}



// On Reagent creation, show appropriate subtype selection box depending on the main reagent type selected
// Written March 27/07 by Marina
function showReagentSubtype()
{
	reagentType = getReagentType();
	subtype = getReagentSubtype(reagentType);

	// Subtype lists
	var vectorSubtypeBox = document.getElementById("vectorSubtypes");
	var cellLineSubtypeBox = document.getElementById("cellLineSubtypes");

	// April 11/07: Show association diagrams too
	var vectorDiagm = document.getElementById("vector_types_diagram");
	var cellLineDiagm = document.getElementById("cell_line_types_diagram");

// 	var addReagentTypeTable = document.getElementById("addReagentTypeTable");

	// and hide them initially
	if (vectorDiagm)
		vectorDiagm.style.display = "none";

	if (cellLineDiagm)
		cellLineDiagm.style.display = "none";

	// Reset all subtype lists
	if (vectorSubtypeBox)
		vectorSubtypeBox.selectedIndex = 0;

	if (cellLineSubtypeBox)
		cellLineSubtypeBox.selectedIndex = 0;
	
	// Hide parents input form
	var nonRecombParents = document.getElementById("nonrec_parents");
	var recombParents = document.getElementById("recomb_parents");	
	var cellLineParents = document.getElementById("stable_cell_line_parents");
	
	if (nonRecombParents)
		nonRecombParents.style.display = "none";

	if (recombParents)
		recombParents.style.display = "none";

	if (cellLineParents)
		cellLineParents.style.display = "none";

	// Hide Next button
// 	var preload = document.getElementById("preload");
// 	preload.style.display = "none";
	
	// General properties forms
	var insertGeneralProps = document.getElementById("insert_general_props");
// 	var oligoGeneralProps = document.getElementById("oligo_general_props");

	var vectorGeneralProps = document.getElementById("vector_general_props");
// 	var vectorBackgroundProps = document.getElementById("vector_backgroundProperty_table");

	var cellLineGeneralProps = document.getElementById("cell_line_general_props");

	// April 22/09
	var vector_heading = document.getElementById("vector_heading");
	var cell_line_heading = document.getElementById("cell_line_heading");

	var reagentTypeCreateOther = document.getElementsByName("reagentTypeCreateOther");

// 	alert(reagentType);

	switch (reagentType)
	{
		case 'Vector':
			if (cellLineGeneralProps)
			{
				cellLineGeneralProps.style.display = "none";
			}

			if (cellLineSubtypeBox)
			{
				cellLineSubtypeBox.style.display = "none";
			}

			vectorSubtypeBox.style.display = "table-row";
			vectorDiagm.style.display = "table-row";
			insertGeneralProps.style.display = "none";
// 			oligoGeneralProps.style.display = "none";
// 			addReagentTypeTable.style.display = "none";
			vector_heading.style.display = "inline";
			cell_line_heading.style.display = "none";

			for (i=0; i < reagentTypeCreateOther.length; i++)
			{
				reagentTypeCreateOther[i].style.display="none";
			}
		break;
		
		case 'CellLine':
			if (insertGeneralProps)
			{
				insertGeneralProps.style.display = "none";
			}

			if (vectorSubtypeBox)
			{
				vectorSubtypeBox.style.display = "none";
			}

			if (cellLineSubtypeBox)
			{
				cellLineSubtypeBox.style.display = "table-row";
			}

			if (cellLineDiagm)
			{
				cellLineDiagm.style.display = "table-row";
			}

			if (vectorGeneralProps)
			{
				vectorGeneralProps.style.display = "none";			
			}
			
			if (vector_heading)
			{
				vector_heading.style.display = "none";
			}

			if (cell_line_heading)
			{
				cell_line_heading.style.display = "inline";
			}

			for (i=0; i < reagentTypeCreateOther.length; i++)
			{
				reagentTypeCreateOther[i].style.display="none";
			}
		break;
		
		case 'Insert':
			if (cellLineGeneralProps)
				cellLineGeneralProps.style.display = "none";

			if (vectorSubtypeBox)
				vectorSubtypeBox.style.display = "none";

			if (cellLineSubtypeBox)
				cellLineSubtypeBox.style.display = "none";

			if (insertGeneralProps)
				insertGeneralProps.style.display = "inline";
// 			oligoGeneralProps.style.display = "none";

			if (vectorGeneralProps)
				vectorGeneralProps.style.display = "none";
// 			vectorBackgroundProps.style.display = "none";

			if (cellLineDiagm)
				cellLineDiagm.style.display = "none";

			if (vectorDiagm)
				vectorDiagm.style.display = "none";

// 			if (addReagentTypeTable)
// 				addReagentTypeTable.style.display = "none";

			if (vector_heading)
			vector_heading.style.display = "none";

			if (cell_line_heading)
				cell_line_heading.style.display = "none";

			for (i=0; i < reagentTypeCreateOther.length; i++)
			{
				reagentTypeCreateOther[i].style.display="none";
			}
		break;
		
// 		case 'Oligo':
// 			vectorSubtypeBox.style.display = "none";
// 			cellLineGeneralProps.style.display = "none";
// 			cellLineSubtypeBox.style.display = "none";
// 			oligoGeneralProps.style.display = "table-row";
// 			insertGeneralProps.style.display = "none";
// 			vectorGeneralProps.style.display = "none";
// // 			vectorBackgroundProps.style.display = "none";
// 			cellLineDiagm.style.display = "none";
// 			vectorDiagm.style.display = "none";
// 			addReagentTypeTable.style.display = "none";
// 
// 			vector_heading.style.display = "none";
// 			cell_line_heading.style.display = "none";
// 
// 			for (i=0; i < reagentTypeCreateOther.length; i++)
// 			{
// 				reagentTypeCreateOther[i].style.display="none";
// 			}
// 		break;
	
		case 'Other':
// alert("1");
			// New reagent type addition
			vectorSubtypeBox.style.display = "none";
			cellLineSubtypeBox.style.display = "none";
			cellLineGeneralProps.style.display = "none";
			vectorGeneralProps.style.display = "none";
// 			vectorBackgroundProps.style.display = "none";
			cellLineDiagm.style.display = "none";
			vectorDiagm.style.display = "none";
// 			oligoGeneralProps.style.display = "none";
			insertGeneralProps.style.display = "none";

			vector_heading.style.display = "none";
			cell_line_heading.style.display = "none";

			for (i=0; i < reagentTypeCreateOther.length; i++)
			{
				reagentTypeCreateOther[i].style.display="none";
			}

// 			addReagentTypeTable.style.display = "inline";

			if (document.getElementById("customizeReagentPropsTbl"))
				document.getElementById("customizeReagentPropsTbl").style.display = "inline";

			if (document.getElementById("propsSummaryTbl"))
				document.getElementById("propsSummaryTbl").style.display = "inline";

			document.getElementById("reagent_type_name").focus();
		break;

		default:
			if (vectorSubtypeBox)
				vectorSubtypeBox.style.display = "none";

			if (cellLineGeneralProps)
				cellLineGeneralProps.style.display = "none";

			if (cellLineSubtypeBox)
				cellLineSubtypeBox.style.display = "none";

// 			if (oligoGeneralProps)
// 				oligoGeneralProps.style.display = "none";

			if (insertGeneralProps)
				insertGeneralProps.style.display = "none";

			if (vectorGeneralProps)
				vectorGeneralProps.style.display = "none";

			if (cellLineDiagm)
				cellLineDiagm.style.display = "none";

			if (vectorDiagm)
				vectorDiagm.style.display = "none";

// 			if (addReagentTypeTable)
// 				addReagentTypeTable.style.display = "none";

			if (vector_heading)
				vector_heading.style.display = "none";

			if (cell_line_heading)
				cell_line_heading.style.display = "none";

			for (i=0; i < reagentTypeCreateOther.length; i++)
			{
				var newTypeTbl = reagentTypeCreateOther[i];

				if (newTypeTbl.id != "createReagentTbl_" + reagentType)
					newTypeTbl.style.display="none";
				else
					newTypeTbl.style.display="inline";
			}
		break;
	}
}


// June 9/09: Prevent form submission when user hits 'Enter'
// Source: http://www.arraystudio.com/as-workshop/disable-form-submit-on-enter-keypress.html
function disableEnterKey(e)
{
     var key;

     if(window.event)
          key = window.event.keyCode;     //IE
     else
          key = e.which;     //firefox

     if(key == 13)
          return false;
     else
          return true;
}

// If the type of reagent being created requires parent values to be filled in, show form to fill the apropriate parent values based on the reagent type selected
// Only applies to Vectors and Cell Lines
// Updated June 8/08: Print different headings
// Updated Feb. 11/09: Show reverse complement and customize sites options for non-recombination vectors only
function showParents(listID)
{
	var currSelected = document.getElementById(listID);
	
	var subtypeSelectedInd = currSelected.selectedIndex;
	var subtypeSelected = currSelected[subtypeSelectedInd].value;

	var nonRecombParents = document.getElementById("nonrec_parents");
	var recombParents = document.getElementById("recomb_parents");	
	var cellLineParents = document.getElementById("stable_cell_line_parents");
	
	// June 8/08
	var creatorTitle = document.getElementById("creator_title");
	var gwExpressionTitle = document.getElementById("gw_expression_title");

	// Sept. 8/08
	var recombHdr = document.getElementById("recomb_hdr");
	var gwExprHdr = document.getElementById("gw_expr_hdr");

	// Sept. 9/08
	var nonrecHdr = document.getElementById("nonrecomb_hdr");
	var gwEntryHdr = document.getElementById("gw_entry_hdr");

	// Sept. 9/08
	var nonrecTitle = document.getElementById("nonrec_title");

	var gwEntryTitle = document.getElementById("gw_entry_title");

	var nonrecVectorDiagm = document.getElementById("custom_sites_diagm");

	// Sept. 9/08
	var nonrecPV = document.getElementById("nonrec_pv");
	var gwParentDonor = document.getElementById("gw_pv");

	var creatorPV = document.getElementById("creator_acceptor_pv");
	var gwExpressionDestinationPV = document.getElementById("destination_pv");

	var creatorDonorIPV = document.getElementById("creator_donor_ipv");
	var gwDonorIPV = document.getElementById("gateway_donor_ipv");

	// Vector general properties form
	var vectorGeneralProps = document.getElementById("vector_general_props");
	
	// Cell Line general properties form
	var cellLineGeneralProps = document.getElementById("cell_line_general_props");

	// Feb, 11/09: Show reverse checkbox and custom sites for non-recombination vectors only
	var reverseComplementCheckbox = document.getElementById("reverseComplementCheckbox");
	var customSitesCheckbox = document.getElementById("customSitesCheckbox");
	var customize_sites_text = document.getElementById("customize_sites_text");
	var rc_text = document.getElementById("rc_text");
	var rc_caption = document.getElementById("rc_caption");

	/*
		Rules for showing parent types:
		
		- Non-Recombination Vector: Parent Vector ID, Insert ID
		- Recombination Vector: Parent Vector ID, Insert Parent Vector ID
		
		- Gateway ENTRY Vector: SAME AS NON-RECOMBINATION: Parent Vector ID, Insert ID
		- Gateway EXPRESSION Vector: SAME AS RECOMBINATION: Parent Vector ID, Insert Parent Vector ID
		
		- Stable Cell Line: Parent Vector ID, Parent Cell Line ID
	*/
	switch (subtypeSelected)
	{
		case 'nonrecomb':
			if (nonRecombParents)
			{
				nonRecombParents.style.display = "table-row";
			}

			if (recombParents)
			{
				recombParents.style.display = "none";
			}

			if (cellLineParents)
			{
				cellLineParents.style.display = "none";
			}

// 			preload.style.display = "table-row";

			if (vectorGeneralProps)
			{
				vectorGeneralProps.style.display = "none";
			}

// 			vectorBackgroundProps.style.display = "none";
			
			if (cellLineGeneralProps)
			{
				cellLineGeneralProps.style.display = "none";
			}

			// Sept. 9/08
			if (nonrecHdr)
			{
				nonrecHdr.style.display = "inline";
			}

			if (gwEntryHdr)
			{
				gwEntryHdr.style.display = "none";
			}

			// Sept. 9/08
			if (nonrecTitle)
			{
				nonrecTitle.style.display = "inline";	
			}

			if (gwEntryTitle)
			{
				gwEntryTitle.style.display = "none";
			}

			// Sept. 9/08
			if (nonrecPV)
			{
				nonrecPV.style.display = "inline";	
			}

			if (gwParentDonor)
			{
				gwParentDonor.style.display = "none";
			}

			// June 8/08
			if (creatorTitle)
			{
				creatorTitle.style.display = "none";
			}

			if (gwExpressionTitle)
			{
				gwExpressionTitle.style.display = "none";
			}

			if (creatorPV)
			{
				creatorPV.style.display = "none";
			}

			if (gwExpressionDestinationPV)
			{
				gwExpressionDestinationPV.style.display = "none";
			}

			if (creatorDonorIPV)
			{
				creatorDonorIPV.style.display = "none";	
			}

			if (gwDonorIPV)
			{
				gwDonorIPV.style.display = "none";
			}

			// Feb. 11/09
			if (reverseComplementCheckbox)
				reverseComplementCheckbox.style.display = "inline";

			if (customSitesCheckbox)
				customSitesCheckbox.style.display = "inline";

			if (customize_sites_text)
				customize_sites_text.style.display = "inline";

			if (rc_text)
				rc_text.style.display = "inline";

			if (rc_caption)
				rc_caption.style.display = "inline";

			// and hide them initially
			if (nonrecVectorDiagm)
				nonrecVectorDiagm.style.display = "inline";
		break;
		
		case 'recomb':
			nonRecombParents.style.display = "none";
			recombParents.style.display = "table-row";
			cellLineParents.style.display = "none";
// 			preload.style.display = "table-row";
			vectorGeneralProps.style.display = "none";
// 			vectorBackgroundProps.style.display = "none";
			cellLineGeneralProps.style.display = "none";
			
			// Sept. 8/08
			recombHdr.style.display = "inline";
			gwExprHdr.style.display = "none";

			// June 8/08
			creatorTitle.style.display = "inline";
			gwExpressionTitle.style.display = "none";

			creatorPV.style.display = "inline";
			gwExpressionDestinationPV.style.display = "none";
			
			creatorDonorIPV.style.display = "inline";
			gwDonorIPV.style.display = "none";

			// Feb. 11/09: Hide custom sites and reverse complement options
			if (reverseComplementCheckbox)
				reverseComplementCheckbox.style.display = "none";

			if (customSitesCheckbox)
				customSitesCheckbox.style.display = "none";

			if (customize_sites_text)
				customize_sites_text.style.display = "none";

			if (rc_text)
				rc_text.style.display = "none";

			if (rc_caption)
				rc_caption.style.display = "none";

			// hide non-recomb vector diagm
			if (nonrecVectorDiagm)
				nonrecVectorDiagm.style.display = "none";
		break;
		
		case 'gateway_entry':

			if (nonRecombParents)
			{
				nonRecombParents.style.display = "table-row";	
			}

			if (recombParents)
			{
				recombParents.style.display = "none";
			}

			if (cellLineParents)
			{
				cellLineParents.style.display = "none";
			}

// 			preload.style.display = "table-row";

			if (vectorGeneralProps)
			{
				vectorGeneralProps.style.display = "none";
			}

// 			vectorBackgroundProps.style.display = "none";

			if (cellLineGeneralProps)
			{
				cellLineGeneralProps.style.display = "none";
			}

			// Sept. 9/08
			if (nonrecHdr)
			{
				nonrecHdr.style.display = "none";
			}

			if (gwEntryHdr)
			{
				gwEntryHdr.style.display = "inline";
			}

			// Sept. 9/08
			if (nonrecTitle)
			{
				nonrecTitle.style.display = "none";
			}

			if (gwEntryTitle)
			{
				gwEntryTitle.style.display = "inline";
			}

			// Sept. 9/08
			if (nonrecPV)
			{
				nonrecPV.style.display = "none";
			}

			if (gwParentDonor)
			{
				gwParentDonor.style.display = "inline";
			}

			// June 8/08
			if (creatorTitle)
			{
				creatorTitle.style.display = "none";
			}

			if (gwExpressionTitle)
			{
				gwExpressionTitle.style.display = "none";
			}

			if (creatorPV)
			{
				creatorPV.style.display = "none";
			}

			if (gwExpressionDestinationPV)
			{
				gwExpressionDestinationPV.style.display = "none";
			}

			if (creatorDonorIPV)
			{
				creatorDonorIPV.style.display = "none";
			}

			if (gwDonorIPV)
			{
				gwDonorIPV.style.display = "none";
			}

			// Feb. 11/09: Hide custom sites and reverse complement options
			if (reverseComplementCheckbox)
				reverseComplementCheckbox.style.display = "none";

			if (customSitesCheckbox)
				customSitesCheckbox.style.display = "none";

			if (customize_sites_text)
				customize_sites_text.style.display = "none";

			if (rc_text)
				rc_text.style.display = "none";

			if (rc_caption)
				rc_caption.style.display = "none";

			if (nonrecVectorDiagm)
				nonrecVectorDiagm.style.display = "none";
		break;
		
		case 'gateway_expression':
			nonRecombParents.style.display = "none";
			recombParents.style.display = "table-row";
			cellLineParents.style.display = "none";
// 			preload.style.display = "table-row";
			vectorGeneralProps.style.display = "none";
// 			vectorBackgroundProps.style.display = "none";
			cellLineGeneralProps.style.display = "none";
			
			// June 8/08
			creatorTitle.style.display = "none";
			gwExpressionTitle.style.display = "inline";
			
			// Sept. 8/08
			recombHdr.style.display = "none";
			gwExprHdr.style.display = "inline";

			creatorPV.style.display = "none";
			gwExpressionDestinationPV.style.display = "inline";
			
			creatorDonorIPV.style.display = "none";
			gwDonorIPV.style.display = "inline";

			// Feb. 11/09: Hide custom sites and reverse complement options
			if (reverseComplementCheckbox)
				reverseComplementCheckbox.style.display = "none";

			if (customSitesCheckbox)
				customSitesCheckbox.style.display = "none";

			if (customize_sites_text)
				customize_sites_text.style.display = "none";

			if (rc_text)
				rc_text.style.display = "none";

			if (rc_caption)
				rc_caption.style.display = "none";

			if (nonrecVectorDiagm)
				nonrecVectorDiagm.style.display = "none";
		break;
		
		case 'novel':
			// For Novel Vector, the procedure is the same as for Inserts and Oligos: Just show general properties form, no parents:
			nonRecombParents.style.display = "none";
			recombParents.style.display = "none";
			cellLineParents.style.display = "none";
			vectorGeneralProps.style.display = "table-row";
// 			vectorBackgroundProps.style.display = "table-row";
// 			preload.style.display = "none";
			cellLineGeneralProps.style.display = "none";

			// June 8/08
			creatorTitle.style.display = "none";
			gwExpressionTitle.style.display = "none";
			
			creatorPV.style.display = "none";
			gwExpressionDestinationPV.style.display = "none";
			
			creatorDonorIPV.style.display = "none";
			gwDonorIPV.style.display = "none";

			// Feb. 11/09: Hide custom sites and reverse complement options
			if (reverseComplementCheckbox)
				reverseComplementCheckbox.style.display = "none";

			if (customSitesCheckbox)
				customSitesCheckbox.style.display = "none";

			if (customize_sites_text)
				customize_sites_text.style.display = "none";

			if (rc_text)
				rc_text.style.display = "none";

			if (rc_caption)
				rc_caption.style.display = "none";
		break;
		
		case 'stable_cell_line':
			cellLineParents.style.display = "table-row";
			nonRecombParents.style.display = "none";
			recombParents.style.display = "none";
// 			preload.style.display = "table-row";
			vectorGeneralProps.style.display = "none";
// 			vectorBackgroundProps.style.display = "none";
			cellLineGeneralProps.style.display = "none";

			// June 8/08
			creatorTitle.style.display = "none";
			gwExpressionTitle.style.display = "none";
			
			creatorPV.style.display = "none";
			gwExpressionDestinationPV.style.display = "none";
			
			creatorDonorIPV.style.display = "none";
			gwDonorIPV.style.display = "none";

		break;

		case 'parent_cell_line':
			cellLineGeneralProps.style.display = "table-row";
			nonRecombParents.style.display = "none";
			recombParents.style.display = "none";
			cellLineParents.style.display = "none";
			vectorGeneralProps.style.display = "none";
// 			vectorBackgroundProps.style.display = "none";

			// June 8/08
			creatorTitle.style.display = "none";
			gwExpressionTitle.style.display = "none";
			
			creatorPV.style.display = "none";
			gwExpressionDestinationPV.style.display = "none";
			
			creatorDonorIPV.style.display = "none";
			gwDonorIPV.style.display = "none";

			// Feb. 11/09: Hide custom sites and reverse complement options
			if (reverseComplementCheckbox)
				reverseComplementCheckbox.style.display = "none";

			if (customSitesCheckbox)
				customSitesCheckbox.style.display = "none";

			if (customize_sites_text)
				customize_sites_text.style.display = "none";

			if (rc_text)
				rc_text.style.display = "none";

			if (rc_caption)
				rc_caption.style.display = "none";
		break;
		
		default:
			nonRecombParents.style.display = "none";
			recombParents.style.display = "none";
			cellLineParents.style.display = "none";
			cellLineGeneralProps.style.display = "none";
			vectorGeneralProps.style.display = "none";
// 			vectorBackgroundProps.style.display = "none";

			// June 8/08
			creatorTitle.style.display = "none";
			gwExpressionTitle.style.display = "none";
			
			creatorPV.style.display = "none";
			gwExpressionDestinationPV.style.display = "none";
			
			creatorDonorIPV.style.display = "none";
			gwDonorIPV.style.display = "none";

			// Feb. 11/09: Hide custom sites and reverse complement options
			if (reverseComplementCheckbox)
				reverseComplementCheckbox.style.display = "none";

			if (customSitesCheckbox)
				customSitesCheckbox.style.display = "none";

			if (customize_sites_text)
				customize_sites_text.style.display = "none";

			if (rc_text)
				rc_text.style.display = "none";

			if (rc_caption)
				rc_caption.style.display = "none";
		break;
	}
}


function getReagentSubtype(reagentType)
{
	var subtypeSelectedInd = "";
	var subtype = "";
	
	switch (reagentType)
	{
		case 'Vector':
			
			var vectorSubtypeBox = document.getElementById("vectorSubtypes");

			if (vectorSubtypeBox)
			{
				subtypeSelectedInd = vectorSubtypeBox.selectedIndex;
				subtype = vectorSubtypeBox[subtypeSelectedInd].value;
	
				return subtype;
			}
		break;
		
		case 'Insert':
			return "";
		break;
		
		case 'Oligo':
			return "";
		break;
		
		case 'CellLine':
			var cellLineSubtypeBox = document.getElementById("cellLineSubtypes");

			if (cellLineSubtypeBox)
			{
				subtypeSelectedInd = cellLineSubtypeBox.selectedIndex;
				subtype = cellLineSubtypeBox[subtypeSelectedInd].value;
			}
			
			return subtype;
		break;
		
		default:
			return "";
		break;
	}
}

// Written May 11/08
function verifyReagentIntroCreation(rType, subtype)
{
	if (document.getElementById("cancel_set"))
	{
		var cancelSet = document.getElementById("cancel_set").value;
	}
	else
	{
		var cancelSet = 0;
	}

	if (cancelSet != 1)
	{
		if (rType == 'Insert')
		{
			subtype = "";
	
			// Type of Insert can never be empty
// 			var itype_list = document.getElementById("itype_list");
			var itype_list = document.getElementById("Insert_Type of insert_prop");
			var itype_selectedInd = itype_list.selectedIndex;
		
// 			var oc_list = document.getElementById("oc_list");
			var oc_list = document.getElementById("Insert_Open/Closed_prop");
			var oc_selectedInd = oc_list.selectedIndex;
		
// 			var oc_warning = document.getElementById("oc_warning");
			var oc_warning = document.getElementById("Insert_Open/Closed_warning");
// 			var it_warning = document.getElementById("it_warning");
			var it_warning = document.getElementById("Insert_Type of insert_warning");
			
			if (verifyReagentName(rType, subtype) && verifyStatus(rType) && verifyPacket(rType))
			{
				// No Insert Type selected
				if ((itype_selectedInd == 0) && (itype_list[itype_selectedInd].value == ""))
				{
					alert("You must select a Type of Insert to continue");
					itype_list.focus();
					it_warning.style.display = "inline";
					oc_warning.style.display = "none";
		
					return false;
				}
				else
				{
					it_warning.style.display = "none";
		
					// If insert type is filled in, check open/closed
					var itype_selectedValue = itype_list[itype_selectedInd].value;
		
					if ((itype_selectedValue != "cDNA with UTRs") && (itype_selectedValue != "DNA Fragment") && (itype_selectedValue != "None"))
					{
						// must have an open/closed value
						oc_sel_val = oc_list[oc_selectedInd].value;
		
						if (oc_sel_val == "")
						{
							alert("You must select an Open/Closed value to continue");
							oc_list.focus();
							oc_warning.style.display = "inline";
							return false;
						}
					}
					else 	// added May 28/07 to hide warning if showing from previous error
					{
						if (oc_warning.style.display == "inline")
						{
							oc_warning.style.display = "none";
						}
					}
		
					return checkMandatoryProps(rType);
				}
			}
	
			return false;
		}

		return (verifyReagentName(rType, subtype) && verifyStatus(rType) && verifyPacket(rType) && checkMandatoryProps(rType));
	}
}


// Verifies Parent input fields are filled in properly on Vector creation
// e.g. both are non-empty, Vector ID not given where Insert ID is expected & v.v., etc.
function verifyParents(rType, subtype)
{
	var rType = ((rType == null) || (rType == '')) ? getReagentType() : rType;
	var subtype = ((subtype == null) || (subtype == '')) ? getReagentSubtype(rType) : subtype;
	
	if (document.getElementById("process_error_restart") && document.getElementById("process_error_restart").checked)
	{
		return true;
	}

	switch (subtype)
	{
		case 'nonrecomb':

			// parent vector and insert
			parentVector = trimAll(document.getElementById("nr_pv_id").value);
			insertID = trimAll(document.getElementById("insert").value);
			
			// verify that parent Vector ID starts with V and that the group ID is a valid numerical index
			if (parentVector.length == 0)
			{
				alert("Please enter a Parent Vector OpenFreezer ID, or select the \"Novel Vector\" option to create a Vector without pre-existing parents");
				document.getElementById("nr_pv_id").focus();
				return false;	
			}
			else if (insertID.length == 0)
			{
				alert("Please enter an Insert OpenFreezer ID, or select the \"Novel Vector\" option to create a Vector without pre-existing parents");
				document.getElementById("insert").focus();
				return false;
			}
			else if (parentVector.substring(0,1).toLowerCase() != 'v')
			{
				alert("Incorrect prefix for OpenFreezer Parent Vector ID (must be \"V\" or \"v\")")
				document.getElementById("nr_pv_id").focus();
				return false;
			}
			else if (insertID.substring(0,1).toLowerCase() != 'i')
			{
				alert("Incorrect prefix for OpenFreezer Insert ID, must be \"I\" or \"i\"")
				document.getElementById("insert").focus();
				return false;
			}
			else if (!isNumeric(parentVector.substring(1)))
			{
				alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("nr_pv_id").focus();
				return false;
			}
			else if (!isNumeric(insertID.substring(1)))
			{
				alert("The identifier portion of the OpenFreezer Insert ID must be a non-zero number, please verify your input")
				document.getElementById("insert").focus();
				return false;
			}
			// July 4/08: Since function "isNumeric" includes a decimal point in the set of allowed characters, add a separate check for it
			else if (parentVector.substring(1).indexOf(".") >= 0)
			{
				alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("nr_pv_id").focus();
				return false;
			}
			// July 4/08: Separate error check for the decimal point
			else if (insertID.substring(1).indexOf(".") >= 0)
			{
				alert("The identifier portion of the OpenFreezer Insert ID must be a non-zero number, please verify your input")
				document.getElementById("insert").focus();
				return false;
			}
		break;
		
		case 'recomb':

			// parent vector and insert parent vector
			parentVector = trimAll(document.getElementById("rec_pv_id").value);
// May 30/08		insertParentVector = trimAll(document.getElementById("insert_parent_vector_id").value);

			if (document.getElementById("ipv_id"))
				insertParentVector = trimAll(document.getElementById("ipv_id").value);		// May 30/08
			else
				insertParentVector = trimAll(document.getElementById("insert_parent_vector_id").value);
			
			if (parentVector.length == 0)
			{
				alert("Please enter a Parent Vector OpenFreezer ID, or select the \"Novel Vector\" option to create a Vector without pre-existing parents");
				document.getElementById("rec_pv_id").focus();
				return false;	
			}
			else if (insertParentVector.length == 0)
			{
				alert("Please enter an Insert Parent Vector OpenFreezer ID, or select the \"Novel Vector\" option to create a Vector without pre-existing parents");
				document.getElementById("insert_parent_vector_id").focus();
				return false;
			}
			else if (parentVector.substring(0,1).toLowerCase() != 'v')
			{
				alert("Incorrect prefix for OpenFreezer Parent Vector ID (must be \"V\" or \"v\")")
				document.getElementById("rec_pv_id").focus();
				return false;
			}
			else if (insertParentVector.substring(0,1).toLowerCase() != 'v')
			{
				alert("Incorrect prefix for OpenFreezer Insert Parent Vector ID, must be \"V\" or \"v\"")
				document.getElementById("insert_parent_vector_id").focus();
				return false;
			}
			else if (!isNumeric(parentVector.substring(1)))
			{
				alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("rec_pv_id").focus();
				return false;
			}
			else if (!isNumeric(insertParentVector.substring(1)))
			{
				alert("The identifier portion of the OpenFreezer Insert Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("insert_parent_vector_id").focus();
				return false;
			}
			// July 4/08: Separate error check for the decimal point
			else if (parentVector.substring(1).indexOf(".") >= 0)
			{
				alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("rec_pv_id").focus();
				return false;
			}
			// July 4/08: Separate error check for the decimal point
			else if (insertParentVector.substring(1).indexOf(".") >= 0)
			{
				alert("The identifier portion of the OpenFreezer Insert Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("insert_parent_vector_id").focus();
				return false;
			}
		break;
		
		case 'gateway_entry':
		
			// parent vector and insert - SAME AS NON-RECOMB
			parentVector = trimAll(document.getElementById("nr_pv_id").value);
			insertID = trimAll(document.getElementById("insert").value);
			
			// verify that parent Vector ID starts with V and that the group ID is a valid numerical index
			if (parentVector.length == 0)
			{
				alert("Please enter a Parent Vector OpenFreezer ID, or select the \"Novel Vector\" option to create a Vector without pre-existing parents");
				document.getElementById("nr_pv_id").focus();
				return false;	
			}
			else if (insertID.length == 0)
			{
				alert("Please enter an Insert OpenFreezer ID, or select the \"Novel Vector\" option to create a Vector without pre-existing parents");
				document.getElementById("insert").focus();
				return false;
			}
			else if (parentVector.substring(0,1).toLowerCase() != 'v')
			{
				alert("Incorrect prefix for OpenFreezer Parent Vector ID (must be \"V\" or \"v\")")
				document.getElementById("nr_pv_id").focus();
				return false;
			}
			else if (insertID.substring(0,1).toLowerCase() != 'i')
			{
				alert("Incorrect prefix for OpenFreezer Insert ID, must be \"I\" or \"i\"")
				document.getElementById("insert").focus();
				return false;
			}
			else if (!isNumeric(parentVector.substring(1)))
			{
				alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("nr_pv_id").focus();
				return false;
			}
			else if (!isNumeric(insertID.substring(1)))
			{
				alert("The identifier portion of the OpenFreezer Insert ID must be a non-zero number, please verify your input")
				document.getElementById("insert").focus();
				return false;
			}
			// July 4/08: Since function "isNumeric" includes a decimal point in the set of allowed characters, add a separate check for it
			else if (parentVector.substring(1).indexOf(".") >= 0)
			{
				alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("nr_pv_id").focus();
				return false;
			}
			// July 4/08: Separate error check for the decimal point
			else if (insertID.substring(1).indexOf(".") >= 0)
			{
				alert("The identifier portion of the OpenFreezer Insert ID must be a non-zero number, please verify your input")
				document.getElementById("insert").focus();
				return false;
			}
		break;
		
		case 'gateway_expression':
		
			// parent vector and insert parent vector - SAME AS RECOMB
			parentVector = trimAll(document.getElementById("rec_pv_id").value);
			insertParentVector = trimAll(document.getElementById("insert_parent_vector_id").value);
			
			if (parentVector.length == 0)
			{
				alert("Please enter a Parent Vector OpenFreezer ID, or select the \"Novel Vector\" option to create a Vector without pre-existing parents");
				document.getElementById("rec_pv_id").focus();
				return false;	
			}
			else if (insertParentVector.length == 0)
			{
				alert("Please enter an Insert Parent Vector OpenFreezer ID, or select the \"Novel Vector\" option to create a Vector without pre-existing parents");
				document.getElementById("insert_parent_vector_id").focus();
				return false;
			}
			else if (parentVector.substring(0,1).toLowerCase() != 'v')
			{
				alert("Incorrect prefix for OpenFreezer Parent Vector ID (must be \"V\" or \"v\")")
				document.getElementById("rec_pv_id").focus();
				return false;
			}
			else if (insertParentVector.substring(0,1).toLowerCase() != 'v')
			{
				alert("Incorrect prefix for OpenFreezer Insert Parent Vector ID, must be \"V\" or \"v\"")
				document.getElementById("insert_parent_vector_id").focus();
				return false;
			}
			else if (!isNumeric(parentVector.substring(1)))
			{
				alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("rec_pv_id").focus();
				return false;
			}
			else if (!isNumeric(insertParentVector.substring(1)))
			{
				alert("The identifier portion of the OpenFreezer Insert Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("insert_parent_vector_id").focus();
				return false;
			}
			// July 4/08: Separate error check for the decimal point
			else if (parentVector.substring(1).indexOf(".") >= 0)
			{
				alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("rec_pv_id").focus();
				return false;
			}
			// July 4/08: Separate error check for the decimal point
			else if (insertParentVector.substring(1).indexOf(".") >= 0)
			{
				alert("The identifier portion of the OpenFreezer Insert Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("insert_parent_vector_id").focus();
				return false;
			}
		break;
		
		case 'stable_cell_line':
		
			// parent vector and parent cell line
			parentVector = trimAll(document.getElementById("cl_pv_id").value);
			parentCellLine = trimAll(document.getElementById("parent_cell_line_id").value);
			
			if (parentVector.length == 0)
			{
				alert("Please enter a Parent Vector OpenFreezer ID, or select the \"Parent Cell Line\" option to create a Cell Line without pre-existing parents");
				document.getElementById("cl_pv_id").focus();
				return false;	
			}
			else if (parentCellLine.length == 0)
			{
				alert("Please enter a Parent Cell Line OpenFreezer ID, or select the \"Parent Cell Line\" option to create a Cell Line without pre-existing parents");
				document.getElementById("parent_cell_line_id").focus();
				return false;
			}
			else if (parentVector.substring(0,1).toLowerCase() != 'v')
			{
				alert("Incorrect prefix for OpenFreezer Parent Vector ID (must be \"V\" or \"v\")")
				document.getElementById("cl_pv_id").focus();
				return false;
			}
			else if (parentCellLine.substring(0,1).toLowerCase() != 'c')
			{
				alert("Incorrect prefix for OpenFreezer Parent Cell Line ID, must be \"C\" or \"c\"")
				document.getElementById("parent_cell_line_id").focus();
				return false;
			}
			else if (!isNumeric(parentVector.substring(1)))
			{
				alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
				document.getElementById("cl_pv_id").focus();
				return false;
			}
			else if (!isNumeric(parentCellLine.substring(1)))
			{
				alert("The identifier portion of the OpenFreezer Parent Cell Line ID must be a non-zero number, please verify your input")
				document.getElementById("parent_cell_line_id").focus();
				return false;
			}
		break;
		
		default:
			// no subtype, look at type
			if (rType == 'Insert')
			{
				senseOligo = trimAll(document.getElementById("insertSenseOligo").value);
				antisenseOligo = trimAll(document.getElementById("insertAntisenseOligo").value);
				insertParentVector = trimAll(document.getElementById("insertParentVector").value);

				// can be empty, but check correct prefixes and numerical group IDs
				if (senseOligo.length > 0)
				{
					if (senseOligo.substring(0,1).toLowerCase() != 'o')
					{
						alert("Incorrect prefix for OpenFreezer Sense Oligo ID, must be \"O\" or \"o\"");
						document.getElementById("insertSenseOligo").focus();
						return false;
					}
					
					if (!isNumeric(senseOligo.substring(1)))
					{
						alert("The identifier portion of OpenFreezer Sense Oligo ID must be a non-zero number, please verify your input");
						document.getElementById("insertSenseOligo").focus();
						return false;
					}
				}

				if (antisenseOligo.length > 0)
				{
					if (antisenseOligo.substring(0,1).toLowerCase() != 'o')
					{
						alert("Incorrect prefix for OpenFreezer Antisense Oligo ID, must be \"O\" or \"o\"");
						document.getElementById("insertAntisenseOligo").focus();
						return false;
					}
					
					if (!isNumeric(antisenseOligo.substring(1)))
					{
						alert("The identifier portion of OpenFreezer Antiesnse Oligo ID must be a non-zero number, please verify your input");
						document.getElementById("insertAntisenseOligo").focus();
						return false;
					}
				}

				if (insertParentVector.length > 0)
				{
					if (insertParentVector.substring(0,1).toLowerCase() != 'v')
					{
						alert("Incorrect prefix for OpenFreezer Insert Parent Vector ID, must be \"V\" or \"v\"")
						document.getElementById("insertParentVector").focus();
						return false;
					}					
					
					if (!isNumeric(insertParentVector.substring(1)))
					{
						alert("The identifier portion of the OpenFreezer Insert Parent Vector ID must be a non-zero number, please verify your input")
						document.getElementById("insertParentVector").focus();
						return false;
					}
				}
			}
		break;
	}
}

function printSitesWarning()
{
	return confirm("If you have modified the restriction sites, the sequence will be recomputed during saving and CLEARED IF IT CANNOT BE RECONSTITUTED WITH THE CHOSEN CLONING SITES.  Are you sure you wish to proceed?");
}

function verifyOligoSequence()
{
	var dna_sequence = document.getElementById("oligo_dna_sequence");

	var sequence_warning = document.getElementById("oligo_sequence_warning");
	var seq = trimAll(dna_sequence.value);

	if (seq == "")
	{
		alert("Please provide a sequence for the Oligo");
		dna_sequence.focus();
		sequence_warning.style.display = "inline";

		return false;
	}
	else
		sequence_warning.style.display = "none";

	for (i = 0; i < seq.length; i++)
	{
		aChar = seq.charAt(i).toLowerCase();

		if ( (aChar != 'a') && (aChar != 'c') && (aChar != 'g') && (aChar != 't') && (aChar != 'n'))
		{
			var answer = confirm("Oligo sequence contains characters other than A, C, G, T or N.  Saving will remove these extra characters.  Are you sure you wish to proceed?");

			if (answer == 0)
			{
				dna_sequence.focus();
				return false;
			}
			else
			{
				// Filter unwanted chars first, then save
				new_sequence = filterDNASeq(seq);
				dna_sequence.value = new_sequence;
				break;
			}
		}
	}

	return true;
}

/* Function defined twice!!  removed Oct. 19/09
function inArray(myChar, myArray)
{
    for (var i = 0; i < myArray.length; i++)
    {
        //alert(myArray[i]);
        //alert(myChar == myArray[i]);

        if (myChar == myArray[i])
            return true;
    }

    return false;
}*/



function verifySequence(rTypeName, seqType)
{
	var seq_container = document.getElementById(seqType + "_sequence_" + rTypeName);

	if (!seq_container)
		seq_container = document.getElementById(seqType + "_sequence");

	switch (seqType)
	{
		case 'dna':
			var nucleotides = Array("A", "a", "C", "c", "G", "g", "T", "t", "N", "n");
			var conf = "Your DNA sequence contains characters other than A, C, G, T or N.  Saving will remove these extra characters.  Are you sure you wish to proceed?";
		break;

		case 'rna':
			var nucleotides = Array("A", "a", "C", "c", "G", "g", "U", "u", "N", "n");
			var conf = "Your RNA sequence contains characters other than A, C, G, U or N.  Saving will remove these extra characters.  Are you sure you wish to proceed?";
		break;

		case 'protein':		// Aug. 24/09: include ambiguous AAs
			var nucleotides = Array("A", "a", "B", "b", "C", "c", "D", "d", "E", "e", "F", "f", "G", "g", "H", "h", "I", "i", "K", "k", "L", "l", "M", "m", "N", "n", "P", "p", "Q", "q", "R", "r", "S", "s", "T", "t", "V", "v", "W", "w", "X", "x", "Y", "y", "Z", "z", "*");
			var conf = "Your Protein sequence contains characters other than A, B, C, D, E, F, G, H, I, K, L, M, N, P, Q, R, S, T, V, W, X, Y, Z or *.  Saving will remove these extra characters.  Are you sure you wish to proceed?";
		break;
	}

	// keep 'dna_sequence' variable name for consistency
	if (seq_container)
		var dna_sequence = filterSpace(seq_container.value);
	else
		var dna_sequence = "";

	if (document.pressed == 'Create')
	{
		// Only execute code on EXIT FROM CREATE VIEW

		// August 13/09: Different sequence container IDs for different reagent types
		if (!seqType || seqType == "")
		{
			// assume DNA
			seqType = "dna";
		}

		for (var i = 0; i < dna_sequence.length; i++)
		{
			var ch = dna_sequence.charAt(i);
			
			if (!inArray(ch, nucleotides))
			{
				var answer = confirm(conf);
	
				if (answer == 0)
				{
					seq_container.focus();
					return false;
				}
				else
				{
					// Filter unwanted chars first, then save
					switch (seqType)
					{
						case 'dna':
							new_sequence = filterDNASeq(dna_sequence);
						break;
	
						case 'rna':
							new_sequence = filterRNASeq(dna_sequence);
						break;
	
						case 'protein':
							new_sequence = filterProteinSeq(dna_sequence);
						break;
					}
	
					seq_container.value = new_sequence;
					return true;
				}
			}
		}
    	}
	else if(document.pressed == 'Save')
	{
		for (var i = 0; i < dna_sequence.length; i++)
		{
			var ch = dna_sequence.charAt(i);

			if (!inArray(ch, nucleotides))
			{
				if (!confirm(conf))
				{
					seq_container.focus();
					return false;
				}
				else
				{
					// Filter unwanted chars first, then save
					switch (seqType)
					{
						case 'dna':
							new_sequence = filterDNASeq(dna_sequence);
						break;
	
						case 'rna':
							new_sequence = filterRNASeq(dna_sequence);
						break;
	
						case 'protein':
							new_sequence = filterProteinSeq(dna_sequence);
						break;
					}

					seq_container.value = new_sequence;
					return true;
				}
			}
		}
	}

	return true;
}

// August 23/07, Marina: Remember the new parent value for later processing by Python
// clonMeth is identical to ATypeID field value in the database, e.g. '1'->'Insert' for non-recombination vectors, '2'->'LOXP' for recombination vectors, etc.
function changeParentValues(clonMeth)
{
//	alert('setting parents');
	switch (clonMeth)
	{
		case '1':
			// Non-recombination vector
			srcPV = document.getElementById("parent_vector_id_txt");
//			alert(srcPV.value);
			srcInsert = document.getElementById("insert_id_txt");
//			alert(srcInsert.value);
			targetPV = document.getElementById("pv_id_hidden");
			targetInsert = document.getElementById("insert_id_hidden");
			
			targetPV.value = srcPV.value;
			targetInsert.value = srcInsert.value;
		break;

		case '2':
			// Recombination vector
			srcPV = document.getElementById("parent_vector_id_txt");
			srcIPV = document.getElementById("ipv_id_txt");

			targetPV = document.getElementById("pv_id_hidden");
			targetIPV = document.getElementById("ipv_id_hidden");

			targetPV.value = srcPV.value;
			targetIPV.value = srcIPV.value;
		break;

		case '4':
	
			// Insert - check Oligos and Parent Insert Vector
			srcSense = document.getElementById("sense_oligo_id_txt");
			srcAntisense = document.getElementById("antisense_id_txt");
			srcPIV = document.getElementById("ipv_id_txt");

			targetSense = document.getElementById("sense_id_hidden");
			targetAntisense = document.getElementById("antisense_id_hidden");
			targetPIV = document.getElementById("piv_id_hidden");

			targetSense.value = srcSense.value;
			targetAntisense.value = srcAntisense.value;
			targetPIV.value = srcPIV.value;

		case '5':
			srcPV = document.getElementById("pv_id_txt");
			srcCL = document.getElementById("cl_id_txt");

			targetPV = document.getElementById("clpv_id_hidden");
			targetCL = document.getElementById("cl_id_hidden");

			targetPV.value = srcPV.value;
			targetCL.value = srcCL.value;

// 			srcChange_PV_Flag = document.getElementById("assoc_pv_change");
// 			targetChange_PV_Flag = document.getElementById("change_pv");
// 
// 			targetChange_PV_Flag.value = srcChange_PV_Flag.value;
// 
// 			srcChange_CL_Flag = document.getElementById("assoc_cl_change");
// 			targetChange_CL_Flag = document.getElementById("change_cl");
// 
// 			targetChange_CL_Flag.value = srcChange_CL_Flag.value;
	}
}


function verifyParentFormat(subtype)
{
	switch (subtype)
	{
		case '1':
			
			// parent vector and insert
			pvField = document.getElementById("parent_vector_id_txt");
			parentVector = trimAll(pvField.value);

			insertField = document.getElementById("insert_id_txt");
			insertID = trimAll(insertField.value);
			
			// verify that parent Vector ID starts with V and that the group ID is a valid numerical index
			if (parentVector.length > 0)
			{
				if (parentVector.substring(0,1).toLowerCase() != 'v')
				{
					alert("Incorrect prefix for OpenFreezer Parent Vector ID (must be \"V\" or \"v\")")
					pvField.focus();
					return false;
				}
				else if (!isNumeric(parentVector.substring(1)))
				{
					alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
					pvField.focus();
					return false;
				}
				// July 4/08: Since function "isNumeric" includes a decimal point in the set of allowed characters, add a separate check for it
				else if (parentVector.substring(1).indexOf(".") >= 0)
				{
					alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
					pvField.focus();
					return false;
				}
			}

			if (insertID.length > 0)
			{
				if (insertID.substring(0,1).toLowerCase() != 'i')
				{
					alert("Incorrect prefix for OpenFreezer Insert ID, must be \"I\" or \"i\"")
					insertField.focus();
					return false;
				}
				else if (!isNumeric(insertID.substring(1)))
				{
					alert("The identifier portion of the OpenFreezer Insert ID must be a non-zero number, please verify your input")
					insertField.focus();
					return false;
				}
				// July 4/08: Separate error check for the decimal point
				else if (insertID.substring(1).indexOf(".") >= 0)
				{
					alert("The identifier portion of the OpenFreezer Insert ID must be a non-zero number, please verify your input")
					insertField.focus();
					return false;
				}
			}
		break;
		
		case '2':
		
			// parent vector and insert parent vector
			pvField = document.getElementById("parent_vector_id_txt");
			parentVector = trimAll(pvField.value);

			ipvField = document.getElementById("ipv_id_txt");
			insertParentVector = trimAll(ipvField.value);
			
			if (parentVector.length > 0)
			{
				if (parentVector.substring(0,1).toLowerCase() != 'v')
				{
					alert("Incorrect prefix for OpenFreezer Parent Vector ID (must be \"V\" or \"v\")")
					pvField.focus();
					return false;
				}
				else if (!isNumeric(parentVector.substring(1)))
				{
					alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
					pvField.focus();
					return false;
				}
				// July 4/08: Separate error check for the decimal point
				else if (parentVector.substring(1).indexOf(".") >= 0)
				{
					alert("The identifier portion of the OpenFreezer Parent Vector ID must be a non-zero number, please verify your input")
					pvField.focus();
					return false;
				}
			}

			if (insertParentVector.length > 0)
			{
				if (insertParentVector.substring(0,1).toLowerCase() != 'v')
				{
					alert("Incorrect prefix for OpenFreezer Insert Parent Vector ID, must be \"V\" or \"v\"")
					ipvField.focus();
					return false;
				}
				else if (!isNumeric(insertParentVector.substring(1)))
				{
					alert("The identifier portion of the OpenFreezer Insert Parent Vector ID must be a non-zero number, please verify your input")
					ipvField.focus();
					return false;
				}
				// July 4/08: Separate error check for the decimal point
				else if (insertParentVector.substring(1).indexOf(".") >= 0)
				{
					alert("The identifier portion of the OpenFreezer Insert Parent Vector ID must be a non-zero number, please verify your input")
					ipvField.focus();
					return false;
				}
			}
		break;
	}

	return true;
}

// updated April 18/08
function checkSave(scriptPath)
{
//	alert('in checksave');
	var vType = document.getElementById("vector_cloning_method").value;
// alert(vType);
	if (vType != '3')
	{
		if (verifyVectorParents(vType) && verifyCloningSites(vType))
		{
			changeParentValues(vType);
			verifySequenceAndRestrictionSites(scriptPath, vType);
		}
		else
		{
	//		alert ('verification failed');
		}
	}
// Removed April 21/08
// 	else	// Nov. 27/11 - novel vectors, no parents
// 	{
// 		document.getElementById("change_state_id").value = "Save";
// 		document.vectorDetailForm.submit();
// 	}
}

function verifyVectorParents(vType)
{
// 	alert('in verify parents');

	if (document.pressed == 'Save')
	{
//		var vType = document.getElementById("vector_cloning_method").value;

		if (verifyParentFormat(vType))
		{
			var vpChange = document.getElementById("change_pv").value;
			var pvOld = document.getElementById("parent_vector_old_id").value;
			var pvNew = document.getElementById("parent_vector_id_txt").value;

			if ((pvNew != "") && (pvNew != pvOld) && (vpChange != 1) )
			{
				alert("Vector " + pvNew + " could not be saved without preloading its properties.  Press the \"Change\" button to load the properties before saving.");
				
				document.getElementById("parent_vector_id_txt").focus();

				return false;
			}
			else
				return true;
		}
	}
	
//	return true;
}

// September 12/07, Marina: When Vector parents are changed, its sequence is recomputed anew.  Get user's confirmation that they want to proceed with the changes
function confirmSequenceChange()
{
	// Determine if parents were changed (i.e. old value != new value)
	var pvOld = document.getElementById("parent_vector_old_id").value;
	//alert("pvOld " + pvOld);

	if (document.getElementById("parent_vector_id_txt"))
	{
		var pvNew = document.getElementById("parent_vector_id_txt").value;
	}
// 	alert("pvNew " + pvNew);
	var secondParentChange = false;

	// Insert or IPV?
	if (document.getElementById("insert_id_txt"))
	{
		var iOld = document.getElementById("insert_old_id").value;
		var iNew = document.getElementById("insert_id_txt").value;
		secondParentChange = (iOld != iNew) ? true : false;
	}
	else if (document.getElementById("ipv_id_txt"))
	{
		var ipvOld = document.getElementById("ipv_old_id").value;
		var ipvNew = document.getElementById("ipv_id_txt").value;
		secondParentChange = (ipvOld != ipvNew) ? true : false;
	}

	if (document.pressed == 'Save')
	{
		if ( pvNew && ((pvOld != pvNew) || secondParentChange))
			return confirm("You are changing parent values.  Sequence is going to be modified upon saving.  Are you sure you want to proceed?");
	}

	return true;
}


function verifyCellLineParents()
{
	if (document.pressed == 'Save')
	{
		var pvChange = document.getElementById("change_pv").value;
		var clChange = document.getElementById("change_cl").value;
		
		var pvOld = document.getElementById("parent_vector_old_id").value;
		var clOld = document.getElementById("parent_cellline_old_id").value;
	
		var pvNew = document.getElementById("pv_id_txt").value;
		var clNew = document.getElementById("cl_id_txt").value;

		if (((pvNew == "") && (pvOld != "")) || ((pvNew != "") && (pvOld == "")) || ((pvNew != "") && (pvOld != "") && (pvNew != pvOld) && (pvChange != 1)))
		{
			alert("Please press 'Change' before saving for Parent Vector modification to take effect");
			return false;
		}

		
// 		if ( (pvNew != "") && (pvNew != pvOld) && (pvChange != 1) )
// 		{
// 			alert("Vector " + pvNew + " could not be saved without preloading its properties.  Press the \"Change\" button to load the properties before saving.");
// 
// 			document.getElementById("pv_id_txt").focus();
// 	
// 			return false;
// 		}

		if (((clNew == "") && (clOld != "")) || ((clNew != "") && (clOld == "")) || ((clNew != "") && (clOld != "") && (clNew != clOld) && (clChange != 1)))
		{
			alert("Please press 'Change' before saving for Parent Cell Line modification to take effect");
			return false;
		}

		

// 		if ( (clNew != "") && (clNew != clOld) && (clChange != 1))
// 		{
// 			alert("Cell Line " + clNew + " could not be saved without preloading its properties.  Press the \"Change\" button to load the properties before saving.");
// 
// 			document.getElementById("cl_id_txt").focus();
// 	
// 			return false;
// 		}
	}
	
	return true;
}

function showOtherTextBox()
{
        var chkbx_id = "other_chkbx";
        var text_id = "ar_chkbx_txt";

        // May 24/06, Marina - Now that we have two groups of checkboxes, which could appear on the view simultaneously for some reagent types (e.g. cell lines), have to differentiate between the alternate ID checkbox group and antibiotic resistance/resistance marker group
        var alt_chkbx_id = "other_Alt_ID_chkbx";
        var alt_txt_id= "alternate_id_txt";

        var chkbxElement = document.getElementById(chkbx_id);
        var txtElem = document.getElementById(text_id);

        // May 24/06
        var alt_id_chkbxElement = document.getElementById(alt_chkbx_id);
        var alt_id_txtElem = document.getElementById(alt_txt_id);

        if (chkbxElement)
        {
                if (chkbxElement.checked)
                {
                        // Show the textbox and set it in focus
                        txtElem.style.display="inline";
                        txtElem.focus();
                }
                else
                {
                        // Hide the textbox
                        txtElem.style.display="none";
                }
        }

        if (alt_id_chkbxElement)
        {
                // May 24/06
                if (alt_id_chkbxElement.checked)
                {
                        // Show the textbox and set it in focus
                        alt_id_txtElem.style.display="inline";
                        alt_id_txtElem.focus();
                }
                else
                {
                        // Hide the textbox
                        alt_id_txtElem.style.display="none";
                }
        }
}

// Feb. 8/08
function showAlternateIDText()
{
	var altIDList = document.getElementById("alternate_id_list");
	var altSelInd = altIDList.selectedIndex;
	var selAltID = altIDList[altSelInd].value;
// 	alert(selAltID + "_alternate_id_textbox");

	var altIDRow = document.getElementById("alt_id_row_" + selAltID);
	altIDRow.style.display = "table-row";

	// Feb. 19/08: Set focus in corresponding textbox
	var altID_txt = document.getElementById(selAltID + "_alternate_id_textbox");
// 	altID_txt.style.display = "inline";

	// feb. 11/08
	var hiddenList = document.getElementById("alternate_ids_hidden");
	hiddenList.options[altSelInd-1].selected = true;

	altID_txt.focus();	// feb. 19/08
// 	alert(getSelectedElements("alternate_ids_hidden"));
}

// Written May 23/06 by Marina
function showAltIDTextBox(chkbx_id, text_id)
{
        var chkbxElement = document.getElementById(chkbx_id);
	var txtElem = document.getElementById(text_id);

        if (chkbxElement.checked)
        {
                // Show the textbox and set it in focus
                txtElem.style.display="inline";
                txtElem.focus();
        }
        else
        {
                // Hide the textbox
                txtElem.style.display="none";
        }
}
 
 
function verifyAlternateID()
{
    alt_id_prop_set = document.getElementsByName("reagent_detailedview_alternate_id_prop[]");
    var prefix = "alternate_id_";
    var postfix = "_textbox";

    for (i = 0; i < alt_id_prop_set.length; i++)
    {
        nextElem = alt_id_prop_set[i];
        alt_id = nextElem.value;

        if (nextElem.value.toLowerCase() != 'other')
            textBoxElem = document.getElementById(prefix + alt_id + postfix);
        else
            textBoxElem = document.getElementById("alternate_id_txt");

        if (nextElem.checked)
        {
            if (textBoxElem.value == "")
            {
                alert("Please fill in the value of '" + alt_id + "' Alternate ID field");

                textBoxElem.style.display = "inline";   // in case it's hidden
                textBoxElem.focus();

                return false;
            }
        }
        else
        {
            // Clear out the value in the corresponding textbox
            textBoxElem.value = "";
        }
    }

    return true;
}

function verifyProperties(rTypeID)
{
    if (document.pressed == 'Save')
    {
        // There is a list of properties to verify for each reagent type
        switch (rTypeID)
        {
            case 2:     // Insert

                // Properties that must always be filled in for Inserts
                mandatoryProps = [];

                // Dropdown lists where properties may be added - Make sure that if 'Other' option is selected, the new property value is also filled in
                propElemList = new Array();
                propNameList = new Array();

                propElemList["tag_list"] = "tag_txt";
                propElemList["cm_list"] = "cm_txt";
                propElemList["species_list"] = "species_txt";

                propNameList["tag_list"] = "Tag";
                propNameList["cm_list"] = "Cloning Method";
                propNameList["species_list"] = "Species";


                return (verifySequence() && verifyAlternateID() && verifyOther(propElemList, propNameList) && verifyMandatory(mandatoryProps));

            break;
        }
    }

    return true;
}


// May 28/07, Marina: Making 'Packet ID' a mandatory field on reagent creation pages
function verifyPacket(rType)
{
	// Nov. 13/08 - hack for cell lines, needs rewriting
	if (rType == 'cell_line')
		rType = 'CellLine';
	
	// Nov. 17/08 - same as above
	else if (rType == 'oligo')
		rType = 'Oligo';

	if (document.getElementById("cancel_set"))
	{
		var cancelSet = document.getElementById("cancel_set").value;
	}
	else
	{
		var cancelSet = 0;
	}

	if (cancelSet != 1)
	{
// alert(rType);
		var temp_id = "packetList_" + rType;
// alert(temp_id);
		var packetList = document.getElementById(temp_id);

		if (packetList)		// could just be another creation step, OK
			var packet_selectedInd = packetList.selectedIndex;
		
		// Warning div depends on type and sometimes subtype of reagent
		var type_tmp = "packet_warning_" + rType;
		var packet_warning = document.getElementById(type_tmp);
		
		// Common to all
		if ( (packet_selectedInd == 0) && (packetList[packet_selectedInd].value == 0))
		{
			alert ("You must select a Project ID to continue");
			packetList.focus();
			packet_warning.style.display = "inline";
			
			return false;
		}
		else 
		{
			// If everything is OK this time but warning was shown previously, hide it
			if (packet_warning.style.display == "inline")
			{
				packet_warning.style.display = "none";
			}
		}
	}
	
	return true;
}


// Feb. 5/08: Make sure reagent name is filled in
function verifyReagentName(rType, subtype)
{
// 	alert("Verify name; subtype: " + subtype);
// 	alert("Show field reagent_name_prop_" + subtype);
// 	alert("Show warning " + subtype + "_name_warning");

	if (document.getElementById("cancel_set"))
	{
		var cancelSet = document.getElementById("cancel_set").value;
	}
	else
	{
		var cancelSet = 0;
	}

	if (cancelSet != 1)
	{
// 		var nameField = document.getElementById("reagent_name_prop_" + subtype);
		var nameField = document.getElementById(rType + "_Name_prop");
// 		var name_warning = document.getElementById(subtype + "_name_warning");
		var name_warning = document.getElementById(rType + "_Name_warning");
	
		if (nameField.value == "")
		{
			// hide all other warnings - Different for each reagent type, so check first if exists
// 			if (document.getElementById("fp_warning"))
			if (document.getElementById(rType + "_5' Cloning Site_warning"))
// 				document.getElementById("fp_warning").style.display = "none";
				document.getElementById(rType + "_5' Cloning Site_warning").style.display = "none";
	
// 			if (document.getElementById("tp_warning"))
// 				document.getElementById("tp_warning").style.display = "none";
	
			if (document.getElementById(rType + "_3' Cloning Site_warning"))
// 				document.getElementById("fp_warning").style.display = "none";
				document.getElementById(rType + "_3' Cloning Site_warning").style.display = "none";
/*
			if (document.getElementById("oc_warning"))
				document.getElementById("oc_warning").style.display = "none";*/

			if (document.getElementById(rType + "_Open/Closed_warning"))
				document.getElementById(rType + "_Open/Closed_warning").style.display = "none";
		
// 			if (document.getElementById("it_warning"))
// 				document.getElementById("it_warning").style.display = "none";
	
			if (document.getElementById(rType + "_Type of Insert_warning"))
				document.getElementById(rType + "_Type of Insert_warning").style.display = "none";
		
			alert("Please provide a name for the new " + rType);
			name_warning.style.display = "inline";
			nameField.focus();
	
			return false;
		}
	
		// hide warning
		name_warning.style.display = "none";
	}

	return true;
}

// Feb. 12/08: Make Status mandatory
function verifyStatus(rType)
{
	if (document.getElementById("cancel_set"))
	{
		var cancelSet = document.getElementById("cancel_set").value;
	}
	else
	{
		var cancelSet = 0;
	}

	if (cancelSet != 1)
	{
		var statusList = document.getElementById(rType + "_Status_prop");
	
		if (statusList)		// not present on all views, e.g. absent from Insert from Primer
		{
			var status_selectedInd = statusList.selectedIndex;
			var status_warning = document.getElementById(rType + "_Status_warning");
	
			if ( (status_selectedInd == 0) && (statusList[status_selectedInd].value == 0))
			{
				alert ("Please indicate the status of the new " + rType);
				statusList.focus();
				status_warning.style.display = "inline";
				
				return false;
			}
			else 
			{
				// If everything is OK this time but warning was shown previously, hide it
				if (status_warning.style.display == "inline")
				{
					status_warning.style.display = "none";
				}
			}
		}
	}

	return true;
}


// May 11/08 - Added isLinear parameter to differentiate between circular and linear sequences - in the first case feature endPos may be < startPos (feature still cannot exceed the length of the sequence)
function verifyCDNA(isLinear)
{
	var cdnaStart = document.getElementById("cdna_insert_start");
	var cdnaEnd =  document.getElementById("cdna_insert_end");
// 	var cdnaWarning = document.getElementById("cdna_warning");	// removed May 11/08
	var cdnaUnknown = document.getElementById("cdna_unknown");

// 	var dna_sequence = document.getElementById("dna_sequence").value;
	
	// Check start and end filled in - May 14/08: Make mandatory for Insert, optional for Vectors
	if (isLinear)
	{
		if (!cdnaUnknown)	// Nov. 13/08
		{
			alert("Please indicate cDNA start and stop positions, or check 'N/A' if unknown");
			return false;
		}

		if (!cdnaUnknown.checked)
		{
			if ((trimAll(cdnaStart.value) == "") || parseInt(cdnaStart.value) == 0)
			{
				alert("Please indicate both cDNA start and end positions, or select \"N/A\" if the sequence does not contain a cDNA.");
				cdnaStart.focus();
	// 			cdnaWarning.style.display = "table-row";
		
				return false;
			}
			
		
			if ((trimAll(cdnaEnd.value) == "") || parseInt(cdnaEnd.value) == 0)
			{
				alert("Please indicate both cDNA start and end positions, or select \"N/A\" if the sequence does not contain a cDNA.");
				cdnaEnd.focus();
	// 			cdnaWarning.style.display = "table-row";
		
				return false;
			}
	
			// Check start <= end
			if (parseInt(cdnaStart.value) >= parseInt(cdnaEnd.value))
			{
				alert("cDNA end index must be greater than the start index.  Please verify your input.");
				cdnaStart.focus();
				return false;
			}
		}
	}
	
	// May 21/08: Not showing cDNA for Novel vectors - so check if the field exists
if (cdnaStart && cdnaEnd)
{

	// Check start and end numeric
	if (parseInt(trimAll(cdnaStart.value)) < 0)
	{
		alert("cDNA start position must be an integer value greater than or equal to 0.  Please verify your input.");
		cdnaStart.focus();
		return false;
	}

	if (parseInt(trimAll(cdnaEnd.value)) < 0)
	{
		alert("cDNA end position must be an integer value greater than or equal to 0.  Please verify your input.");
		cdnaEnd.focus();
		return false;
	}

/*
	// REDUNDANT!!! (a minus sign would be captured by above check)
	// 	// Check start and end > 0
	// 	if (parseInt(cdnaStart.value) < 0)
	// 	{
	// 		alert("cDNA start value must be an integer value greater than or equal to 0.  Please verify your input.");
	// 		cdnaStart.focus();
	// 		return false;
	// 	}
	// 
	// 	if (parseInt(cdnaEnd.value) < 0)
	// 	{
	// 		alert("cDNA end value must be an integer value greater than or equal to 0.  Please verify your input.");
	// 		cdnaEnd.focus();
	// 		return false;
	// 	}
*/
	// Check start and end <= sequence length
/*
	if (parseInt(cdnaEnd.value) > parseInt(dna_sequence.length))
	{
		alert("cDNA end value cannot exceed the length of the DNA sequence.  Please verify your input.");
		cdnaEnd.focus();
		return false;
	}

	if (parseInt(cdnaStart.value) > parseInt(dna_sequence.length))
	{
		alert("cDNA start value cannot exceed the length of the DNA sequence.  Please verify your input.");
		cdnaStart.focus();
		return false;
	}
*/
}
// 	cdnaWarning.style.display = "none";
	return true;

/*
	// Removed April 11/08
	// 	// cDNA positions MAY be left at 0, but it's a good idea to remind the user that they can be changed
	// 	var response = true;
	// 
	// 	if ( (parseInt(trimAll(cdnaStart.value)) == 0) && (parseInt(trimAll(cdnaEnd.value)) == 0) )
	// 	{
	// 		response = confirm("cDNA start and end positions are set to 0.  Save anyway?");
	// 	
	// 		// the response here would be the opposite of the function's return value; an affirmative response means the user does not want to save but wants to change cDNA values, in which case the function should return 'false', and vice versa
	// 		if (!response)
	// 			cdnaStart.focus();
	// 	}
	// 
	// 	return response;
*/
}

// March 18/08: Check property positions
// May 14/08: Added isLinear argument - circular Vector sequences may have end > start; also cDNA positions must be filled in for Inserts
function verifyPositions(isLinear)
{
	if (document.getElementById("cancel_set"))
	{
		var cancelSet = document.getElementById("cancel_set").value;
	}
	else
	{
		var cancelSet = 0;
	}

	if (cancelSet != 1)
	{
		// Verify positions for all feature names in the 'features' dropdown selection list, plus cloning sites
		var featureNamesList = document.getElementById("sequence_property_names");
	
		var propPrefix = "reagent_detailedview_";
		var propPostfix = "_prop";
	
		var startPrefix = "_startpos";
		var endPrefix = "_endpos";
	
		var tmpID;
		var tmpAttr;
		var tmpOptn;
	
		var allFields = document.getElementsByTagName("INPUT");
// 		var dna_sequence = filterSeq(document.getElementById("dna_sequence").value).toLowerCase();
	
		var tmpStart;
		var tmpStartInput;
	
		var tmpEnd;
		var tmpEndField;
	
		var i, j, k, l, ch, nt;
	
		var numbers = "0123456789";
		var nucleotides = "AaCcGgTt";
	
		var startInd;
		var endInd;
	
	// 	alert(allFields.length);
	
		for (i = 0; i < allFields.length; i++)
		{
// 			alert(i);
	
			tmpInput = allFields[i];
	
			if (tmpInput.type == "hidden")
			{
				tmpID = tmpInput.id;
// 				alert(tmpID);
			
				startInd = tmpID.indexOf(startPrefix + propPostfix);
				endInd = tmpID.indexOf(endPrefix + propPostfix);
	
				if (startInd > 0)
				{
	// 				alert(tmpID);
					propName = tmpID.substring(0, startInd);
// 					alert("Prop " + propName);
	
					tmpStart = tmpInput.value;
// 					alert("Start " + tmpStart);
	
					tmpStartID = propPrefix + propName + startPrefix + propPostfix;
	// 				alert(tmpStartID);
	
					// Get numerical index to distinguish b/w multiple feature values
					propCount = tmpID.substr(startInd+((startPrefix + propPostfix).length), tmpID.length);
	// 				alert(propCount);
	
					tmpStartInput = document.getElementById(propName + propCount + startPrefix + "_id");
	
					if (trimAll(propCount).length > 0)
					{
						// Try to find end input
						tmpEndID = propName + endPrefix + propPostfix + propCount;
		// 				alert(tmpEndID);
		
						tmpEndInput = document.getElementById(tmpEndID);
						tmpEnd = tmpEndInput.value;
	// 					alert("End " + tmpEnd);
	
						tmpEndField = document.getElementById(propName + propCount + endPrefix + "_id");
	// 					alert(propName + propCount + endPrefix + "_id");
					}
					else
					{
						// do anything??
					}
	
					// Check start numeric
					for (k = 0; k < trimAll(tmpStart).length; k++)
					{
						ch = trimAll(tmpStart).charAt(k);
	
						if (!inArray(ch, numbers))
						{
							alert("Feature start positions may only contain digits.  Please verify your input.");
							tmpStartInput.focus();
							return false;
						}
					}
	
					// Check end numeric
					for (k = 0; k < trimAll(tmpEnd).length; k++)
					{
						ch = trimAll(tmpEnd).charAt(k);
	
						if (!inArray(ch, numbers))
						{
							alert("Feature end positions may only contain digits.  Please verify your input.");
							tmpEndField.focus();
							return false;
						}
					}
			
	// 				alert("DNA length " + parseInt(dna_sequence.length));
	
					// Check start and end <= sequence length
/*
					if (parseInt(tmpEnd) > parseInt(dna_sequence.length))
					{
						alert("Feature end value cannot exceed the length of the DNA sequence.  Please verify your input.");
						tmpEndField.focus();
						return false;
					}
				
					if (parseInt(tmpStart) > parseInt(dna_sequence.length))
					{
						alert("Feature start value cannot exceed the length of the DNA sequence.  Please verify your input.");
						tmpStartInput.focus();
						return false;
					}
*/
			
					// Check start < end
					if (!isLinear && (parseInt(tmpStart) > parseInt(tmpEnd)))
					{
						alert("Feature start index cannot be greater than the end index.  Please verify your input.");
						tmpStartInput.focus();
						return false;
					}
				}
			}
			else if (tmpInput.type.toLowerCase() == "text")
			{
				// Single-value features don't have a numeric identifier
// 				alert(tmpInput.id);
	// 			alert(tmpInput.name);
	
				tmpID = tmpInput.id;
	//			alert(tmpID);
	//			alert(startPrefix + propPostfix);
	
				startInd = tmpID.indexOf(startPrefix + propPostfix);
				endInd = tmpID.indexOf(endPrefix + propPostfix);
	
				if (startInd > 0)
				{
					propName = tmpID.substring(0, startInd);
// 					alert("CURRENT FEATURE: " + propName);
			
					tmpStart = tmpInput.value;
	
// 					alert("Start " + tmpStart);
			
					tmpStartInput = tmpInput;
	
					// Try to find end input
					tmpEndID = propName + endPrefix + propPostfix
	// 				alert(tmpEndID);
	
					tmpEndInput = document.getElementById(tmpEndID);
	
					if (tmpEndInput)	// might not exist if this is not a feature
					{
						tmpEnd = tmpEndInput.value;
	// 					alert("End " + tmpEnd);
	
						// Check start & end numeric
						for (k = 0; k < trimAll(tmpStart).length; k++)
						{
							ch = trimAll(tmpStart).charAt(k);
		
							if (!inArray(ch, numbers))
							{
								alert("Feature start positions may only contain digits.  Please verify your input.");
								tmpStartInput.focus();
								return false;
							}
						}
	
						for (k = 0; k < trimAll(tmpEnd).length; k++)
						{
							ch = trimAll(tmpEnd).charAt(k);
		
							if (!inArray(ch, numbers))
							{
								alert("Feature end positions may only contain digits.  Please verify your input.");
								tmpEndInput.focus();
								return false;
							}
						}
			
	// 					alert("DNA length " + parseInt(dna_sequence.length));
	
						// Check start and end <= sequence length
/*
						if (parseInt(tmpStart) > parseInt(dna_sequence.length))
						{
							alert("Feature start value cannot exceed the length of the DNA sequence.  Please verify your input.");
							tmpStartInput.focus();
							return false;
						}
		
						if (parseInt(tmpEnd) > parseInt(dna_sequence.length))
						{
							alert("Feature end value cannot exceed the length of the DNA sequence.  Please verify your input.");
							tmpEndInput.focus();
							return false;
						}
*/				
			
						// Check start < end
						if (!isLinear && (parseInt(tmpStart) > parseInt(tmpEnd)))
						{
							alert("Feature start index cannot be greater than the end index.  Please verify your input.");
							tmpStartInput.focus();
							return false;
						}
		
						// For Linkers, check that the positions entered correspond to the linker input value length
						fpl_input = document.getElementById("fp_linker_prop");
						tpl_input = document.getElementById("tp_linker_prop");
		
						if (fpl_input)
							five_linker = fpl_input.value.toLowerCase();
						else
							five_linker = "";
	
						if (tpl_input)
							three_linker = tpl_input.value.toLowerCase();
						else
							three_linker = "";
		
						fpl_length = five_linker.length;
						tpl_length = three_linker.length;
		
						fpl_start_in = document.getElementById("5_prime_linker_startpos" + propPostfix);
						tpl_start_in = document.getElementById("3_prime_linker_startpos" + propPostfix);
		
						if (fpl_start_in)
							fpl_start = trimAll(fpl_start_in.value);
						else
							fpl_start = 0;
	
						if (tpl_start_in)
							tpl_start = trimAll(tpl_start_in.value);
						else
							tpl_start = 0;
		
						fpl_end_in = document.getElementById("5_prime_linker_endpos" + propPostfix);
						tpl_end_in = document.getElementById("3_prime_linker_endpos" + propPostfix);
		
						if (fpl_end_in)
							fpl_end = trimAll(fpl_end_in.value);
						else
							fpl_end = 0;
	
						if (tpl_end_in)
							tpl_end = trimAll(tpl_end_in.value);
						else
							tpl_end = 0;
		
						if ( (fpl_length > 0) && (parseInt(fpl_start) > 0) && (parseInt(fpl_end) > 0) && ((fpl_end - fpl_start + 1) != fpl_length) )
						{
							alert("The length of the 5' linker does not match the start and end indices provided for it.  Please verify your input");
							fpl_input.focus();
							return false;
						}
		
						if ( (tpl_length > 0) && (parseInt(tpl_start) > 0) && (parseInt(tpl_end) > 0) && ((tpl_end - tpl_start + 1) != tpl_length) )
						{
							alert("The length of the 3' linker does not match the start and end indices provided for it.  Please verify your input");
							tpl_input.focus();
							return false;
						}
		
						// At least one of linker, start or end is blank (but not all) - DISALLOW THIS, causes database inconsistencies.  Force the user to make corrections
		
						// For simplicity now, though, only check actual property value not empty if its positions are filled in
		
						// linker empty, positions filled in
						if ((fpl_length == 0) && (parseInt(fpl_start) > 0) && (parseInt(fpl_end) > 0))
						{
							alert("You have provided start and end positions for the 5' linker but not the actual linker value.  Please verify your input.");
							fpl_input.focus();
							return false;
						}
	
	// 				// Only linker filled in, positions empty -- OK, ALLOW
	// 				else if ( (fpl_length > 0) && (parseInt(fpl_start) == 0) && (parseInt(fpl_end) == 0) )
	// 				{
	// 					return confirm("Position values for the 5' linker value are not filled in.  Are you sure you want to continue (linker will be saved without start/stop values)");
	// 				}
	
	// 				// Only start filled in
	// 				else if ( (fpl_length == 0) && (parseInt(fpl_start) > 0) && (parseInt(fpl_end) == 0) )
	// 				{
	// 					alert("You have provided the start position for the 5' linker but not the actual linker value or its end position.  Please verify your input.");
	// 					fpl_input.focus();
	// 					return false;
	// 				}
	// 
	// 				// Only end filled in
	// 				else if ( (fpl_length == 0) && (parseInt(fpl_end) > 0) && ( (parseInt(fpl_start) == 0) || trimAll(fpl_start).length == 0) )
	// 				{
	// 					alert("You have provided the end position for the 5' linker but not the actual linker value or its start position.  Please verify your input.");
	// 					fpl_input.focus();
	// 					return false;
	// 				}
	// 
	// 				// start empty, linker and end filled in
	// 				else if ( (fpl_length > 0) && (parseInt(fpl_start) == 0) && (parseInt(fpl_end) > 0) )
	// 				{
	// 					alert("You have not provided the 5' linker start position.  Please verify your input.");
	// 					fpl_input.focus();
	// 					return false;
	// 				}
	// 
	// 				// end empty, linker and end filled in
	// 				else if ( (fpl_length > 0) && (parseInt(fpl_start) > 0) && (parseInt(fpl_end) == 0) )
	// 				{
	// 					alert("You have not provided the 5' linker end position.  Please verify your input.");
	// 					fpl_input.focus();
	// 					return false;
	// 				}
	
						// Ditto for 3' linker
						// linker empty, positions filled in
						if ((tpl_length == 0) && (parseInt(tpl_start) > 0) && (parseInt(tpl_end) > 0))
						{
							alert("You have provided start and end positions for the 3' linker but not the actual linker value.  Please verify your input.");
							tpl_input.focus();
							return false;
						}
	
	// 				// Only linker filled in, positions empty -- OK, ALLOW
	// 				else if ( (fpl_length > 0) && (parseInt(fpl_start) == 0) && (parseInt(fpl_end) == 0) )
	// 				{
	// 					return confirm("Position values for the 5' linker value are not filled in.  Are you sure you want to continue (linker will be saved without start/stop values)");
	// 				}
	
	// 				// Only start filled in
	// 				else if ( (tpl_length == 0) && (parseInt(tpl_start) > 0) && (parseInt(tpl_end) == 0) )
	// 				{
	// 					alert("You have provided the start position for the 3' linker but not the actual linker value or its end position.  Please verify your input.");
	// 					tpl_input.focus();
	// 					return false;
	// 				}
	// 
	// 				// Only end filled in
	// 				else if ( (tpl_length == 0) && (parseInt(tpl_start) == 0) && (parseInt(tpl_end) > 0) )
	// 				{
	// 					alert("You have provided the end position for the 3' linker but not the actual linker value or its start position.  Please verify your input.");
	// 					tpl_input.focus();
	// 					return false;
	// 				}
	// 
	// 				// start empty, linker and end filled in
	// 				else if ( (tpl_length > 0) && (parseInt(tpl_start) == 0) && (parseInt(tpl_end) > 0) )
	// 				{
	// 					alert("You have not provided the 3' linker start position.  Please verify your input.");
	// 					tpl_input.focus();
	// 					return false;
	// 				}
	// 
	// 				// end empty, linker and end filled in
	// 				else if ( (tpl_length > 0) && (parseInt(tpl_start) > 0) && (parseInt(tpl_end) == 0) )
	// 				{
	// 					alert("You have not provided the 3' linker end position.  Please verify your input.");
	// 					tpl_input.focus();
	// 					return false;
	// 				}
	
						// Check that linkers only contain ACGT (not a position validation but still)
						for (l = 0; l < fpl_length; l++)
						{
							nt = five_linker[l];
		
							if (!inArray(nt, nucleotides))
							{
								alert("5' linker value may not contain characters other than A,C,G,T.  Please verify your input.");
								fpl_input.focus();
								return false;
							}
						}
		
						for (l = 0; l < tpl_length; l++)
						{
							nt = three_linker[l];
		
							if (!inArray(nt, nucleotides))
							{
								alert("3' linker value may not contain characters other than A,C,G,T.  Please verify your input.");
								tpl_input.focus();
								return false;
							}
						}
		
						// Last but not least, check that the linker value provided is found on the actual sequence where indicated
/*
						if ((parseInt(fpl_start) != 0) && (parseInt(fpl_end) != 0) && (dna_sequence.substring(parseInt(fpl_start)-1, fpl_end) != five_linker) )
						{
							alert("5' linker could not be found on sequence at the specified positions.  Please verify your input.");
							fpl_input.focus();
							return false;
						}
		
						if ( (parseInt(tpl_start) != 0) && (parseInt(tpl_end) != 0) && (dna_sequence.substring(parseInt(tpl_start)-1, tpl_end) != three_linker) )
						{
							alert("3' linker could not be found on sequence at the specified positions.  Please verify your input.");
							tpl_input.focus();
							return false;
						}
*/
					}
				}
			}
		}
	
		// May 14/08: Make Insert cDNA positions mandatory UNLESS it's non-coding or DNA fragment
		// For Vectors, if not Novel, cDNA positions will automatically be pre-filled from sequence reconstitution; not mandatory for Novel
		if (isLinear)
			return verifyCDNA(isLinear) && verifyCloningSites("insert");
	}

	return true;	// keep!
}

// Last modified Feb. 12/08
function validateInsert()
{
	// Type of Insert can never be empty
	var itype_list = document.getElementById("itype_list");
	var itype_selectedInd = itype_list.selectedIndex;

	var oc_list = document.getElementById("oc_list");
	var oc_selectedInd = oc_list.selectedIndex;

	var oc_warning = document.getElementById("oc_warning");
	var it_warning = document.getElementById("it_warning");

	// Feb. 5/08: Make Name a mandatory field
	// Feb. 12/08: Make Status mandatory too
	if (verifyPacket("Insert") && verifyStatus("Insert") && verifyReagentName("Insert", "insert") && verifyCloningSites("insert") && verifySequence("Insert") && verifyPositions() && verifyCDNA(true))
	{
		// No Insert Type selected
		if ((itype_selectedInd == 0) && (itype_list[itype_selectedInd].value == ""))
		{	
			alert("You must select a Type of Insert to continue");
			itype_list.focus();
			it_warning.style.display = "inline";
			oc_warning.style.display = "none";

			return false;
		}
		else
		{
			it_warning.style.display = "none";

			// If insert type is filled in, check open/closed
			var itype_selectedValue = itype_list[itype_selectedInd].value;

			if ((itype_selectedValue != "cDNA with UTRs") && (itype_selectedValue != "DNA Fragment") && (itype_selectedValue != "None"))
			{
				// must have an open/closed value
				oc_sel_val = oc_list[oc_selectedInd].value;

				if (oc_sel_val == "")
				{
					alert("You must select an Open/Closed value to continue");
					oc_list.focus();
					oc_warning.style.display = "inline";
					return false;
				}
			}
			else 	// added May 28/07 to hide warning if showing from previous error
			{
				if (oc_warning.style.display == "inline")
				{
					oc_warning.style.display = "none";
				}
			}

			return true;
		}
	}
	else
	{
		// Hide all other warnings
		it_warning.style.display = "none";
		oc_warning.style.display = "none";
	}
	
	return false;
}


// Feb. 29/08
function verifyInsertSaveSequence()
{
	// Type of Insert can never be empty
	var itype_list = document.getElementById("itype_list");
	var itype_selectedInd = itype_list.selectedIndex;

	var oc_list = document.getElementById("oc_list");
	var oc_selectedInd = oc_list.selectedIndex;

	var oc_warning = document.getElementById("oc_warning");
	var it_warning = document.getElementById("it_warning");

	// Feb. 5/08: Make Name a mandatory field
	// Feb. 12/08: Make Status mandatory too
// 	if (verifySequence() && verifyCDNA())		// April 18/08: Don't verify cDNA here, get error on sequence deletion
	if (verifySequence())
	{
		// No Insert Type selected
		if ((itype_selectedInd == 0) && (itype_list[itype_selectedInd].value == ""))
		{
			alert("You must select a Type of Insert to continue");
			itype_list.focus();
			it_warning.style.display = "inline";
			oc_warning.style.display = "none";

			return false;
		}
		else
		{
			it_warning.style.display = "none";

			// If insert type is filled in, check open/closed
			var itype_selectedValue = itype_list[itype_selectedInd].value;

			if ((itype_selectedValue != "cDNA with UTRs") && (itype_selectedValue != "DNA Fragment") && (itype_selectedValue != "None"))
			{
				// must have an open/closed value
				oc_sel_val = oc_list[oc_selectedInd].value;

				if (oc_sel_val == "")
				{
					alert("You must select an Open/Closed value to continue");
					oc_list.focus();
					oc_warning.style.display = "inline";
					return false;
				}
			}
			else 	// added May 28/07 to hide warning if showing from previous error
			{
				if (oc_warning.style.display == "inline")
				{
					oc_warning.style.display = "none";
				}
			}

			return true;
		}
	}
	else
	{
		// Hide all other warnings
		it_warning.style.display = "none";
		oc_warning.style.display = "none";
	}

	return false;
}

// This function verifies, for dropdown lists where properties may be added, that if option 'Other' is selected, the new property value is filled in (essentially make sure that 'Other' textboxes are not left blank)
function verifyOther(propList, propNameList)
{
    var prop;
    var propName;
    var propVal;
    var pName;

    for (var propID in propList)
    {
        prop = document.getElementById(propID);

        var sel_ind = prop.selectedIndex;
	var sel_val = prop[sel_ind].value;

        if (sel_val.toLowerCase() == 'other')
        {
            propName = propList[propID];
            textElem = document.getElementById(propName);
            propVal = textElem.value;

            if (propVal == "")
            {
                pName = propNameList[propID];

                alert("Please fill in the value of 'Other' for '" + pName + "' property");
                textElem.style.display = "inline";
                textElem.focus();

                return false;
            }
        }
    }

    return true;
}



//	Various utility functions used in various sections of the website

// April 3/07, Marina: Check whether the input string is numeric
// Taken from CodeToad: http://www.codetoad.com/javascript/isnumeric.asp
// CAUTION: DO NOT USE 'char' as a variable name - It is a reserved keyword and will NOT work on Mac OS-X
function isNumeric(sText)
{
	var validChars = "0123456789.";
	var isNumber=true;
	var aChar;
 
	// remove whitespace if there is any
	sText = trimAll(sText);

 	if (sText.length == 0) 
		return false;
	
 	if (sText.substring(0,1) == '0')
		return false;
	
	for (i = 0; i < sText.length && isNumber == true; i++) 
      	{ 
		aChar = sText.charAt(i); 
		
		if (validChars.indexOf(aChar) == -1) 
         	{
			isNumber = false;
         	}
      	}

	return isNumber;
}

// Trim leading and trailing whitespace from a string
// Taken from http://www.aspdev.org/articles/javascript-trim
// April 8/07, Marina, last updated Nov. 15/07
function trimAll(sString)
{
	while ((sString.substring(0,1) == ' ') || (sString.substring(0,1) == '\t')  || (sString.substring(0,1) == '\n')  || (sString.substring(0,1) == '\r'))
	{
		sString = sString.substring(1, sString.length);
	}
	
	while ((sString.substring(sString.length-1, sString.length) == ' ')  || (sString.substring(sString.length-1, sString.length) == '\t')  || (sString.substring(sString.length-1, sString.length) == '\n') || (sString.substring(sString.length-1, sString.length) == '\r'))
	{
		sString = sString.substring(0,sString.length-1);
	}

	// Added August 13, 2010; was not properly trimming HTML &nbsp;
	sString = sString.replace(/^\s*/, '').replace(/\s*$/, ''); 

	return sString;
}


// 	Location functions

// Added May 22/07, Marina
// On Plate view: Sort table columns at the click of a mouse
function sortByColumn(colName)
{
	sortBy = document.getElementById("sortByCol_hidden");
	sortBy.value = colName;
	
	searchForm = document.printContainersOfOneTypeForm;
	searchForm.submit();
}

// Feb. 15/08
function groupByContainer()
{
	var locationOutputMode = document.getElementById("location_output_mode");
	locationOutputMode.value = "2";
	document.viewLocation.submit();
}

// Feb. 15/08
function groupByIsolate()
{
	var locationOutputMode = document.getElementById("location_output_mode");
	locationOutputMode.value = "1";
	document.viewLocation.submit();
}

// Aug. 28/08
function showPlates()
{
	var contTypeList = document.getElementById("cont_name_selection");
	var contType = contTypeList[contTypeList.selectedIndex].value;

	document.printPlatesForContainerForm.submit();
}

// 	Sequence functions

function filterSpace(txt)
{
        var txt_ar = txt.split(' ');
        var sel = "";

        for (i = 0; i < txt_ar.length; i++)
        {
                sel += txt_ar[i];
        }

        return sel;
}


function filterRNASeq(seq)
{
    // Filter out characters other than A,C,G,T from DNA sequence
    var nucleotides = "AaCcGgUuNn";
    var newSeq = "";

    for (var i = 0; i < seq.length; i++)
    {
        var myChar = seq.charAt(i);

        if (inArray(myChar, nucleotides))
            newSeq += myChar;
    }

    return newSeq;
}


function filterProteinSeq(seq)
{
// alert("yow");
    seq = seq.toUpperCase();

    // Filter out characters other than amino acids from Protein sequence
    // Update Aug. 24/09: include ambiguous amino acids - B, J, X, Z
    var nucleotides = "AaBbCcDdEeFfGgHhIiKkLlMmNnPpQqRrSsTtVvWwXxYyZz*";
    var newSeq = "";

    for (var i = 0; i < seq.length; i++)
    {
        var myChar = seq.charAt(i);

        if (inArray(myChar, nucleotides))
            newSeq += myChar;
    }

    return newSeq;
}

function filterDNASeq(seq)
{
    // Filter out characters other than A,C,G,T from DNA sequence
    var nucleotides = "AaCcGgTtNn";
    var newSeq = "";

    for (var i = 0; i < seq.length; i++)
    {
        var myChar = seq.charAt(i);

        if (inArray(myChar, nucleotides))
            newSeq += myChar;
    }

    return newSeq;
}


function showHideChildren(linkID, contentElemID)
{
	// Extract show/hide portion of linkID
	var ind1 = linkID.indexOf('_');
	var linkType = linkID.substring(0, ind1);	// show or hide
	var listType = linkID.substring(ind1+1);	// vector or cell_line
	
    	var currLink = document.getElementById(linkID);
    	var contentElem = document.getElementById(contentElemID);

	var showLink = document.getElementById("show_" + listType);
    	var hideLink = document.getElementById("hide_" + listType);

	// Show/hide contents - July 6/09: Changed 'inline' to 'table-row' - in new function children are contained within a <tr> element
	contentElem.style.display = (linkType == "show") ? "table-row" : "none";	// do not use 'inline'!
	
	// Switch between show/hide link (if currently have 'show', hide it and inhide 'hide', & v.v.)
	showLink.style.display = (linkType == "show") ? "none" : "inline";	// keep 'inline' here!
	hideLink.style.display = (linkType == "show") ? "inline" : "none";
}


// April 16, 2010: Just invoke a Python script to execute a command-line cp command
function copyTmpFile(srcFileName, toFileName)
{
	url = cgiPath + "commands.py";

	xmlhttp = createXML();
	xmlhttp.open("POST", url, false);
	xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');

	args = "cmd=cp&srcFile=" + srcFileName + "&toFile=" + toFileName;
	xmlhttp.send(args);

	xmlhttp.onreadystatechange = openFile(xmlhttp);
}

function openFile(xmlhttp)
{
	if (xmlhttp.readyState == 4)
	{
		if (xmlhttp.status == 200)
		{
// 			prompt("", xmlhttp.responseText);
		}
	}
}

// March 23/09: Output Vector information in GenBank format
function exportToGenbank(rID, limsID)
{
	url = cgiPath + "genbank.py";
	xmlhttp = createXML();
	xmlhttp.open("POST", url, false);
	xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');

	seqParams = "rID=" + rID;

	xmlhttp.send(seqParams);

	xmlhttp.onreadystatechange = outputGenBankInfo(xmlhttp);
}

function outputGenBankInfo(xmlhttp)
{
	if (xmlhttp.readyState == 4)
	{
		if (xmlhttp.status == 200)
		{
			fName = hostName + "Reagent/genbank/" + limsID + ".gbk";
// 			return popup(fName, limsID, 1200, 1200, 'yes');
		}
	}
// 	else
// 	{
// 		alert(xmlhttp.readyState);
// 	}
}

// June 5/07, Marina: Export sequence to FASTA file
function exportDNASeqToFasta(fastaCont)
{
	var fastaContent = document.getElementById("dna_fasta_content");
	var seqExportForm = document.getElementById("export_dna_sequence_form");

	fastaContent.value = fastaCont;
	seqExportForm.submit();
}


// June 5/07, Marina: Export sequence to FASTA file
function exportProteinSeqToFasta(fasCont)
{
	var fastaContent = document.getElementById("protein_fasta_content");
	var seqExportForm = document.getElementById("export_protein_sequence_form");
	
	fastaContent.value = fasCont;
	seqExportForm.submit();
}

// Oct. 23/09
function exportRNASeqToFasta(fastaCont)
{
	var fastaContent = document.getElementById("rna_fasta_content");
	var seqExportForm = document.getElementById("export_rna_sequence_form");
	
	fastaContent.value = fastaCont;
	seqExportForm.submit();
}

// Initialization - June 15, 2007, Marina
function initAll()
{
// 	document.getElementById('header_table').style.width = screen.width-40;
// 	document.getElementById('warning_div').style.width = screen.width-40;
	
	//if ((window.location.href.indexOf("Location.php?View=") >= 0) && (window.location.href.indexOf("&Mod=") >= 0))
	//{
		// make sure we're on the plate's detailed view
		if (document.getElementById("changeAttributeSelect"))
		{
			document.getElementById("changeAttributeSelect").selectedIndex = 0;
			clearAllWells(window.location.href.substr((hostName+"Location.php?View=").length+"&Mod=".length+1, window.location.href.length));
		}
	//}

	initSearch();
	prepareVectorMap(cgiPath);
	prepareOligoMap(cgiPath);
}


// function setSessionVariables()
// {
// 	enableCheckboxes();
// 	document.addReagentTypeForm.submit();
// 
// 	if (verifyNewReagentTypeName() && verifyNewReagentTypePrefix())
// 	{
// 		document.addReagentTypeForm.action = cgiPath + "reagent_type_request_handler.py";
// 		document.addReagentTypeForm.submit();	
// 	}
// }

// Jan. 3, 2010
function deleteContainer(contID)
{
	if (confirm('Are you sure you wish to delete container?  Deletion cannot be undone.'))
	{
		document.deleteContainerForm.submit();
	}
}


function initSearch()
{
	var searchField = document.getElementById("search_term_id");
	
	if (searchField != null)		// won't show up on any view other than Search
		searchField.focus();
}


// Fetch all lists of lab members 
function getLabMembersLists()
{
	var labMemLists = document.getElementsByTagName("SELECT");
	var constMemListName = "labSourceMembers_";

	var tmpList;
	var resultList = Array();

	for (i = 0; i < labMemLists.length; i++)
	{
		tmpList = labMemLists[i];

		if ((tmpList.name.substring(0, tmpList.name.length-1) == constMemListName))
			resultList.push(tmpList);
	}
	
	return resultList;
}


// 	Menu functions
// 	April 2007, Marina

function expandAll()
{
	var list = document.getElementsByTagName('ul');

	for (var i=0; i < list.length; i++)
	{
		if (list[i].className == 'submenu')
		{
			list[i].style.display = "block";
		}
	}
}

function expandCollapse(listID)
{
	var topElem = document.getElementById(listID);

	// Extract the numeric portion of the name
	var ind1 = listID.indexOf('_');
	var menuName = listID.substring(0, ind1);
	var level = parseInt(listID.substring(ind1+1));

	// Find corresponding submenu
	var nextLevel = level + 1;
	var subID = menuName + '_' + nextLevel;
	var subElem = document.getElementById(subID);

	// Switch between expand/collapse icon on top menu
	var menuExpandImg = document.getElementById(menuName + "_expand_img");
	var menuCollapseImg = document.getElementById(menuName + "_collapse_img");

	if (menuExpandImg.style.display == "inline")
	{
		menuExpandImg.style.display = "none";
		menuCollapseImg.style.display = "inline";
	}
	else
	{
		menuCollapseImg.style.display = "none";
		menuExpandImg.style.display = "inline";
	}

	// Show the appropriate submenu
	subElem.style.display = (subElem.style.display=="block")?"none":"block";	
}



// 	Project functions
// 	June 2007, Marina

function getSelectedLab()
{
	var labList = document.getElementById("labList");
	var labSelInd = labList.selectedIndex;
	
	return labList.options[labSelInd].id;
}


function showLabMembersList()
{
	var labID = getSelectedLab();
	var currLabMemListName = "lab_source_list_" + labID;
	
	var labMemList = document.getElementById(currLabMemListName);
	var constMemListName = "labSourceMembers_";

	// does not appear on all views
	if (labMemList)
		labMemList.style.display = "inline";		// use "" for browser compatibility??

	// June 29/07: Added functionality for Delete view
	var membersRow = document.getElementById("deleteMembersRow");
	
	if (membersRow) 
	{
		if (labID == 0)
			membersRow.style.display = "none";
		else
			membersRow.style.display = "table-row";
	}
	
	// Hide all other lists
	var otherLabMemLists = document.getElementsByTagName("SELECT");
	var currList;

	for (i = 0; i < otherLabMemLists.length; i++)
	{
		tmpList = otherLabMemLists[i];
		ind1 = tmpList.name.indexOf("_")+1;

		if ( (tmpList.name.substring(0, ind1) == constMemListName) && (tmpList != labMemList))
		{
			tmpList.style.display = "none";
		}
	}
}

// Return an array of selected items in a list
// Resulting array is a list of OPTION elements
function getSelectedOptions(myList)
{
// 	myList = myList.replace("'", "\\'");	// NO!!!!!!!
	myList = unescape(myList);

	var fromList = document.getElementById(myList);
	var selOpts = new Array();
	
	for (i = 0; i < fromList.options.length; i++)
	{
		currOptn = fromList.options[i];
		
		if (currOptn.selected)
		{
			selOpts.push(currOptn);
		}
	}
	
	return selOpts;
}


// Identify which 'access level' (step 2) radio button is currently selected
function getSelectedRole(step)
{
	var inputList = document.getElementsByTagName("INPUT");
	var inputTmp;
	var radioTmp;
	
	for (i = 0; i < inputList.length; i++)
	{
		inputTmp = inputList[i];
		
		if (inputTmp.type == 'radio')
		{
			rTempName = inputTmp.name;
		
			if (rTempName == "access_levels")
			{
				if (inputTmp.checked)
				{
					return inputTmp.value;
				}
			}
		}
	}
}


// Called at step 1 to move members from source list to readers, writers or admin list, depending on access level selected
function addMembers(srcListID, role)
{
	var targetListID;
	
	switch (role)
	{
		case 'read':
			targetListID = "readers_target_list";
		break;
		
		case 'write':
			targetListID = "writers_target_list";
		break;
		
		default:
			// move back to original list
			targetListID = "lab_source_list_" + getSelectedLab();
		break;
	}
	
	moveListElements(srcListID, targetListID, false);
}


// Called at step 3 to move members from readers/writers list back to the original members list
// Modified June 28/07 - move a member back to his/her own lab, which is not necessarily the lab currently selected 
function removeProjectMembers()
{
	// Move selected members from ALL target lists
	var readersListID = "readers_target_list";
	var writersListID = "writers_target_list";

	var readersList = document.getElementById(readersListID);
	var writersList = document.getElementById(writersListID);

	var targetListID = "";
	var targetLabListIDPrefix = "lab_source_list_";
	
	var selReaders = getSelectedOptions(readersListID);
	var selWriters = getSelectedOptions(writersListID);
	
	// Move readers first, then writers
	for (r = 0; r < selReaders.length; r++)
	{
		rdrOpt = selReaders[r];
		rdrOptID = rdrOpt.id;

		// extract the lab ID from the option ID - the character after "_lab_" (using ID, since 'name' is not an attribute of the OPTION tag)
		str1 = "_lab_";
		ind1 = rdrOptID.indexOf(str1) + str1.length;
		labID = rdrOptID.substring(ind1);
		targetListID = targetLabListIDPrefix + labID;
		targetList = document.getElementById(targetListID);
		
		// Not using moveListElements, since it incorrectly moves ALL values in the source list to the same target list
		if (targetList.options.length == 0)
		{
			targetList.options.add(rdrOpt);
			removeElement(readersList, rdrOpt);
			
		}
		else
		{
			addElement(targetList, targetList.options.length, rdrOpt);
			removeElement(readersList, rdrOpt);
		}
		
		clearAllElements(targetListID);
	}

	// writers
	for (w = 0; w < selWriters.length; w++)
	{
		wrtrOpt = selWriters[w];
		wrtrOptID = wrtrOpt.id;

		// extract the lab ID from the option ID - the character after "_lab_" (using ID, since 'name' is not an attribute of the OPTION tag)
		str1 = "_lab_";
		ind1 = wrtrOptID.indexOf(str1) + str1.length;
		labID = wrtrOptID.substring(ind1);
		targetListID = targetLabListIDPrefix + labID;
		targetList = document.getElementById(targetListID);

		// Not using moveListElements, since it incorrectly moves ALL values in the source list to the same target list
		if (targetList.options.length == 0)
		{
			targetList.options.add(wrtrOpt);
			removeElement(writersList, wrtrOpt);
		}
		else
		{
			addElement(targetList, targetList.options.length, wrtrOpt);
			removeElement(writersList, wrtrOpt);
		}
		
		clearAllElements(targetListID);
	}
	
	clearCheckboxes(readersListID, targetListID);	
	clearCheckboxes(writersListID, targetListID);
}

function removeElement(myList, optn)
{
	var tmpOpt;
	
	for (i = 0; i < myList.options.length; i++)
	{
		tmpOpt = myList.options[i];
		
		if (tmpOpt.text == optn.text)
			myList.options[i] = null;
	}
}

function addElementToListFromInput(inputID, listID)
{
	// ATTENTION: PHP escapes quotation marks!  The actual field name contains a slash before a quote.  Must, therefore, append a slash here, for the input name to be recognized1

// 	inputID = inputID.replace("'", "\\'");	// maybe need replaceAll??
	inputID = unescape(inputID);
// 	listID = listID.replace("'", "\\'");	// maybe need replaceAll??
	listID = unescape(listID);

	var fIn = document.getElementById(inputID);
	var toList = document.getElementById(listID);
	var tmpVal = fIn.value;

	newOptn = document.createElement("OPTION");
	newOptn.text = tmpVal;
	newOptn.value = tmpVal;

	// June 15, 2010
	if (trimAll(tmpVal).toLowerCase() == 'other')
	{
		alert("This value is not allowed.  Please verify your input.");
		return false;
	}
	
	if (trimAll(tmpVal) == '')
	{
		return false;
	}


	// Now insert it at the appropriate position in the target list to maintain alphabetical ordering!
	if (toList.options.length == 0)
	{
		toList.options.add(newOptn);
	}
	else
	{
		addElement(toList, toList.options.length, newOptn, true);
	}

	if (!addElementError)
	{
		// clear option value
		fIn.value = "";
	}

	fIn.focus();		// June 15/09
}

function removePropertyListValue(pAlias)
{
	// ATTENTION: PHP escapes quotation marks!  The actual field name contains a slash before a quote.  Must, therefore, append a slash here, for the input name to be recognized1
// 	pAlias = pAlias.replace("'", "\\'");	// KEEP!!! maybe need replaceAll??
	pAlias = unescape(pAlias);

	var pList = document.getElementById("propertyValuesInputList_" + pAlias);
	var pVal;
	var listLen = pList.options.length;
// 	alert(listLen);

	for (i = listLen-1; i >= 0; i--)
	{
		if (pList.options[i].selected == true)
		{
// 			alert(pList.options[i].value);

// 			pVal = pList[pList.selectedIndex].value;
			pVal = pList.options[i].value;
// 			alert(i);
// 			alert(pVal);
			removeListElement(pList, pVal);

			// # options has decreased by 1 - update!!
			listLen = pList.options.length;
			i = listLen;
// 			alert(listLen);
		}
	}

// 	removeListElement(pList, pVal);
}

// myList: object (e.g. SELECT)
// myElem: value (text, numeric)
function removeListElement(myList, myElem)
{
	var counter = 0;
	var tempList = myList;
	var tempElem;

	while (counter < tempList.options.length)
	{
		tempElem = tempList.options[counter].value;

		if (tempElem == myElem)
		{
			// remove element from original list
			myList.options[counter] = null;
			
			// recompute list length and reset counter
			tempList = myList;
			counter = 0;
		}
		else
		{
			counter++;
		}
	}
}

// Oct. 8, 2010: Check if a value exists in a dropdown list
// Not checking numeric or case-sensitivity here; see if needed along the way
function existsListElement(targetListID, optValue)
{
	var tList = document.getElementById(targetListID);
// alert(optValue);
	var result = false;

	for (i=0; i < tList.options.length; i++)
	{
		tmpOptn = tList.options[i];
// alert(tmpOptn.value);
		if (tmpOptn.value == optValue)
			return true;
	}

	return false;
}


// Used on Project, User and Lab Views to add/remove members to/from projects/labs, or modify member project lists
function moveListElements(fromListID, targetListID, numeric, check_case)
{
// alert("in moveListElements");
// 	fromListID = fromListID.replace("'", "\\'");	// maybe need replaceAll??
	fromListID = unescape(fromListID);
// 	targetListID = targetListID.replace("'", "\\'");	// maybe need replaceAll??
	targetListID = unescape(targetListID);
// alert(targetListID);
	var toList = document.getElementById(targetListID);
// 	var labID = getSelectedLab();
	var fromList = document.getElementById(fromListID);
	var selOpts = getSelectedOptions(fromListID);
	
	var currOptn;
	var newOptn;

	for (m = 0; m < selOpts.length; m++)
	{
		currOptn = selOpts[m];

// 	alert(currOptn.value);

		// Oct. 8, 2010: move IFF doesn't exist
		if (!existsListElement(targetListID, currOptn.value))
		{
			newOptn = document.createElement("OPTION");
			newOptn.text = currOptn.text;
			newOptn.value = currOptn.value;
			newOptn.id = currOptn.id;
	
			// Now insert it at the appropriate position in the target list to maintain alphabetical ordering!
			if (toList.options.length == 0)
			{
				toList.options.add(newOptn);
			}
			else
			{
				// traverse list recursively to find insert position
				// With projects, however, the index comparison should be numeric
				if (numeric == true)
				{
					addNumElement(toList, toList.options.length, newOptn);
				}
				else
				{
					addElement(toList, toList.options.length, newOptn, check_case);
				}
			}
		}
	}
		
	
	// Remove this element from original list
	var totalOpts = fromList.options.length;
	
	if (!addElementError)
	{
		for (j in selOpts)
		{
			optRmv = selOpts[j];
			
			for (i = 0; i < totalOpts; i++)
			{
				origOptn = fromList.options[i];
				
				if (origOptn.value == optRmv.value)
				{
					fromList.options[i] = null;
				}
		
				// RECOMPUTE LIST LENGTH!!!
				totalOpts = fromList.options.length;
			}
		}
	}
	else
	{
		// still, deselect this option
		clearAllElements(fromListID);
	}
	
	clearCheckboxes(fromListID, targetListID);
}

// May 27, 2010
// Very specific function - at reagent type creation, take the newly entered reagent type name and append to list of parent types if requested
function addReagentTypeNameToParentsList()
{
	var rTypeName = document.getElementById("reagent_type_name").value;
	var includeParent = document.getElementById("includeParent");

	if (includeParent.checked)
	{
		var parents = document.getElementById("add_reagent_type_parents");
		var newOptn = document.createElement("OPTION");

		newOptn.value = rTypeName;
		newOptn.selected = true;
		parents.options.add(newOptn);
	}
}


function addFormElements(aForm, aList)
{
	lElems = aList.options;

	for (i = 0; i < lElems.length; i++)
	{
		newElem = lElems[i].value;
		aForm.appendChild(newElem);
	}
}

function clearCheckboxes(fromListID, targetListID)
{
	var fromList = document.getElementById(fromListID);
	var toList = document.getElementById(targetListID);
	
	var chkbxFrom;
	var chkbxTo;
	
	var chkbxSource = document.getElementById("add_all_chkbx");
	var chkbxRead = document.getElementById("select_all_reader_chkbx");
	var chkbxWrite = document.getElementById("select_all_writer_chkbx");
	var chkbxAdmin = document.getElementById("select_all_admin_chkbx");
	
	var totalSelectedTo = getTotalItemsSelected(toList);

	// Checkboxes are not present on some views, in which case this function produces an error
	if (chkbxSource || chkbxRead || chkbxWrite || chkbxAdmin)
	{
		switch (targetListID)
		{
			case 'readers_target_list':
				chkbxTo = chkbxRead;
			break;	

			case 'writers_target_list':
				chkbxTo = chkbxWrite;
			break;

			case 'user_projects_write':
				chkbxTo = chkbxWrite;
			break;
			
			case 'user_projects_readonly':
				chkbxTo = chkbxRead;
			break;

			default:
				chkbxTo = chkbxSource;
			break;
		}

		switch (fromListID)
		{
			case 'readers_target_list':
				chkbxFrom = chkbxRead;
			break;

			case 'writers_target_list':
				chkbxFrom = chkbxWrite;
			break;			
			
			case 'user_projects_readonly':
				chkbxFrom = chkbxRead;
			break;

			case 'user_projects_write':
				chkbxFrom = chkbxWrite;
			break;
			
			default:
				chkbxFrom = chkbxSource;
			break;
		}
		
		// Check if no elements remain in the source list, AND no elements are selected in the target list - then clear checkboxes
		if (totalSelectedTo != toList.options.length)
		{
			chkbxTo.checked = false;
		}

		if (fromList.options.length == 0)
		{
			chkbxFrom.checked = false;
		}
	}
}


function getTotalItemsSelected(toList)
{
	var totalSelected = 0;
	
	for (i = 0; i < toList.options.length; i++)
	{
		var tempOptn = 	toList.options[i];
		
		if (tempOptn.selected)
			totalSelected++;
	}

	return totalSelected;
}


// June 3, 2010: as the name implies
function addProjectOwnerToWritersList()
{
	var ownersList = document.getElementById('projectOwnersList');
	var selInd = ownersList.selectedIndex;
	var pOwnerID = ownersList[selInd].value;
	var pOwner = ownersList[selInd].text;

	var newOptn = document.createElement('OPTION');
	newOptn.text = pOwner;
	newOptn.value = pOwnerID;

	var writersList = document.getElementById('writers_target_list');

	// make sure it doesn't exist - do duplicats check here
	writersArray = new Array();

	for (i=0; i < writersList.options.length; i++)
	{
		tmpOpt = writersList.options[i];

		if (tmpOpt)
		{
			writersArray.push(tmpOpt.value);
		}
	}

	if (!hasArrayElement(writersArray, pOwnerID))
	{
		if (writersList.options.length == 0)
			addElement(writersList, 1, newOptn, true);
		else
			addElement(writersList, writersList.options.length, newOptn, true);
	}
}


// June 3, 2010: At automated password reminder page, check that EITHER username or full name is filled, don't leave empty text fields
function checkPasswordReminder()
{
	var username = document.getElementById('uname_remind');
	var fullName = document.getElementById('udesc_remind');

	// first, check username
	if (trimAll(username.value) == '')
	{
		// if username is not filled, check description
		if (trimAll(fullName.value) == '')
		{
			alert("Please provide either your user name or your full name as recorded in OpenFreezer.\n\nIf you have not yet obtained a user account, please contact the Administrator.");

			return false;
		}
	}

	return true;
}

// Recursive method to insert an element to maintain alphabetical ordering
// mylist - array of list elements
// position - initial start position @ start of recursion
function addElement(mylist, listSize, myOptn, check_case)
{
// 	alert("List size " + listSize);
	
	myPos = listSize - 1;

	// alert(check_case);
	addElementError = false;	// reset globally

// alert(check_case);

	// May 26, 2010: Disallow duplicate values
	if (check_case)
	{
// 	alert("checking case");
// 	alert(myPos);
// 	alert(">>>" + mylist.options[myPos].text + "<<<");
// 	alert(">>>" + myOptn.text + "<<<");

		if (mylist.options[myPos])	// added June 3
		{
			if (trimAll(mylist.options[myPos].text).toLowerCase() == trimAll(myOptn.text).toLowerCase())
			{
				alert("This value already exists in the list.  Please verify your input.");
				addElementError = true;
				return;
			}
		}
	}

	if (listSize == 1)
	{
		var newOptn = document.createElement("OPTION");

		newOptn.text = myOptn.text;
		newOptn.value = myOptn.value;
		newOptn.id = myOptn.id;		// june 28/07

		// Sept. 25/08: Try to use case-insensitive comparison
// 		if (mylist.options[myPos].text < myOptn.text)
		if (mylist.options[myPos] && (mylist.options[myPos].text.toLowerCase() < myOptn.text.toLowerCase()))
		{
			//alert(mylist.options[myPos].value + " comes BEFORE " + myOptn.value);
			mylist.options.add(newOptn, myPos+1);		
		}
		else
		{
			//alert(mylist.options[myPos].value + " comes AFTER " + myOptn.value);
			mylist.options.add(newOptn, 0);
		}
	}	
	else
	{
		// Sept. 25/08: Try to use case-insensitive comparison
// 		if (mylist.options[myPos].text < myOptn.text)
		if (mylist.options[myPos].text.toLowerCase() < myOptn.text.toLowerCase())
		{
			//alert(mylist.options[myPos].value + " comes BEFORE " + myOptn.value);
			
			var newOptn = document.createElement("OPTION");

			newOptn.text = myOptn.text;
			newOptn.value = myOptn.value;
			newOptn.id = myOptn.id;		// june 28/07
			
			mylist.options.add(newOptn, myPos+1);
		}
		else
		{
			//alert(mylist.options[myPos].value + " comes AFTER " + myOptn.value);
			addElement(mylist, listSize-1, myOptn, check_case);
		}
	}
}


// Same, only compare NUMERIC indices
function addNumElement(mylist, listSize, myOptn)
{
	myPos = listSize - 1;
	
	if (listSize == 1)
	{
		var newOptn = document.createElement("OPTION");

		newOptn.text = myOptn.text;
		newOptn.value = myOptn.value;
		newOptn.id = myOptn.id;		// june 28/07

		if (parseInt(mylist.options[myPos].value) < parseInt(myOptn.value))
		{
			//alert(mylist.options[myPos].value + " comes BEFORE " + myOptn.value);
			mylist.options.add(newOptn, myPos+1);		
		}
		else
		{
			//alert(mylist.options[myPos].value + " comes AFTER " + myOptn.value);
			mylist.options.add(newOptn, 0);
		}
	}	
	else 
	{
		if (parseInt(mylist.options[myPos].value) < parseInt(myOptn.value))
		{
			//alert(mylist.options[myPos].value + " comes BEFORE " + myOptn.value);
			
			var newOptn = document.createElement("OPTION");

			newOptn.text = myOptn.text;
			newOptn.value = myOptn.value;
			newOptn.id = myOptn.id;		// june 28/07
			
			mylist.options.add(newOptn, myPos+1);
		}
		else
		{
			//alert(mylist.options[myPos].value + " comes AFTER " + myOptn.value);
			addNumElement(mylist, listSize-1, myOptn);
		}
	}
}


// Added Oct. 5, 2010
function showLabProjects(labListID)
{
// alert(labListID);
	var labsList = document.getElementById(labListID);
// 	alert(labsList.selectedIndex);
	var labSelInd = labsList.selectedIndex;
	var selLabID = labsList[labSelInd].value;
// 	alert(selLab);

	var labProjectList = document.getElementById("lab_projects_" + selLabID);

	var allSelects = document.getElementsByTagName("SELECT");

	var allSpans = document.getElementsByTagName("SPAN");

	var noLabProjects = document.getElementById("no_projects_" + selLabID);

	for (a=0; a < allSelects.length; a++)
	{
		tmpSel = allSelects[a];

		if ((tmpSel.id.indexOf("lab_projects_") == 0) && (tmpSel.id != "lab_projects_" + selLabID))
			tmpSel.style.display = "none";
	}

	for (b=0; b < allSpans.length; b++)
	{
		tmpSpan = allSpans[b];

		if ((tmpSpan.id.indexOf("no_projects_") == 0) && (tmpSpan.id != "no_projects_" + selLabID))
			tmpSpan.style.display = "none";
	}

	if (labProjectList)
	{
		document.getElementById("addReadProjects").onclick = function(){addProjects("lab_projects_" + selLabID, 'readonly')};
		labProjectList.style.display = "";
	}

	if (noLabProjects)
		noLabProjects.style.display = "inline";

	// show current checkbox and hide the rest
	var allDivs = document.getElementsByTagName("DIV");

	for (c=0; c < allDivs.length; c++)
	{
		tmpDiv = allDivs[c];

		if ((tmpDiv.id.indexOf("selectAllCaption_") == 0) && (tmpDiv.id != "selectAllCaption_" + selLabID))
			tmpDiv.style.display = "none";
	}

	document.getElementById("project_read_checkbox").style.display = "";

	// no 'select all' box for empty lab project lists
	if (document.getElementById("selectAllCaption_" + selLabID))
		document.getElementById("selectAllCaption_" + selLabID).style.display = "";

	document.getElementById("projectCaption").style.display = "";
}

function hideLabProjects()
{
	// get the selected labID and hide the corresponding project list
	var labsList = document.getElementById("projectLabID");
// 	alert(labsList.selectedIndex);
	var labSelInd = labsList.selectedIndex;
	var selLabID = labsList[labSelInd].value;
// alert(selLabID);

	var labProjectList = document.getElementById("lab_projects_" + selLabID);

	// no lab project list might be shown at this point
	if (labProjectList)
		labProjectList.style.display = "none";
	
	document.getElementById("project_read_checkbox").style.display = "none";
	document.getElementById("projectCaption").style.display = "none";
}

// Select all options in a list; takes as arguments a list name and a checkbox name - checkbox needs to be cleared when function returns
// April 3/09: Renamed function from selectAllUsers to selectAll - code used in many cases, not just users
function selectAll(cbID, uListName, includeDisabled)
{
	var checkBox = document.getElementById(cbID);
	
	if (checkBox.checked)
	{
		// select all
		selectAllElements(uListName, includeDisabled);
	}
	else
	{
		// clear all
		clearAllElements(uListName);
	}
}


function selectAllContainerProperties(cbID, uListName)
{
	var checkBox = document.getElementById(cbID);
	
	if (checkBox.checked)
	{
		// select all
		selectAllElements(uListName);
	}
	else
	{
		// clear all
		clearAllElements(uListName);
	}
}


// Select all elements in a list 
// mainly needed for passing form data to CGI, but there are other applications
function selectAllElements(listName, includeDisabled)
{
	listName = unescape(listName);

	var myList = document.getElementById(listName);
	var i;

	for (i = 0; i < myList.options.length; i++)
	{
		// Dec. 11/09, exclude disabled options if requested
// alert(includeDisabled);
		if (!includeDisabled)
		{
			if (myList.options[i].disabled != true)
				myList.options[i].selected = true;
		}
		else
			myList.options[i].selected = true;
	}
}


// Similar to getSelectedOptions but returns a list of actual element VALUES
function getSelectedElements(myList)
{
	var fromList = document.getElementById(myList);
	var selOpts = new Array();
	
	for (i = 0; i < fromList.options.length; i++)
	{
		currOptn = fromList.options[i];
		
		if (currOptn.selected)
		{
			selOpts.push(currOptn.value);
		}
	}
	
	return selOpts;
}


function clearAllElements(listName)
{
	listName = unescape(listName);

	var myList = document.getElementById(listName);
	
	for (i = 0; i < myList.options.length; i++)
	{
		myList.options[i].selected = false;
	}
}

// Removed August 24/07 - think it's deprecated
// Returns true if a value has been selected from the list identified by projectListID; returns false otherwise
// function verifyProject()
// {
// 	var projectList = document.getElementById("packetList");
// 	var packet_selectedInd = projectList.selectedIndex;
// 	var projectWarning = document.getElementById("project_warning");
// 	
// 	if (packet_selectedInd <= 0)
// 	{
// 		alert ("Please select a Project ID to view");
// 		projectList.focus();
// 		projectWarning.style.display = "inline";
// 
// 		return false;
// 	}
// 	
// 	return true;	
// }


// June 1/07, Marina: Make sure a project ID has been selected from a dropdown list
// Essentially performs the same action as verifyProject and verifyPacket, except this time the function is being invoked on Project deletion 
// It takes as arguments the ID of the Project dropdown list, not reagent type
// Returns true if a value has been selected from the list identified by projectListID; returns false otherwise
function verifyProjectDeletion(projectListID, submitID)
{
	var projectList = document.getElementById(projectListID);
	var packet_selectedInd = projectList.selectedIndex;
	var projectWarning = document.getElementById("project_warning");
	
	var submitBtn = document.getElementById(submitID);
	
	if (packet_selectedInd <= 0)
	{
		submitBtn.disabled = true;	
		return false;
	}
	else 
	{
		// If everything is OK this time but warning was shown previously, hide it
		if (projectWarning)
		{
			if (projectWarning.style.display == "inline")
			{
				projectWarning.style.display = "none";
			}
		}
		
		submitBtn.disabled = false;
	}
	
	return true;	
}

// Get the number of selected elements in a list
// aList: List object (not name!)
function getNumSelected(aList)
{
	var numSel = 0;
	
	for (i = 0; i < aList.options.length; i++)
	{
		if (aList.options[i].selected)
			numSel++;
	}
	
	return numSel;
}

// Used in project creation or in adding members to a project: Make sure the resulting list of members is not empty
// Updated June 20/07 by Marina
function verifyMembers(memListID)
{
	var membersList = document.getElementById(memListID);
//	var numSel = 0;
	var numSel = getNumSelected(membersList);	// june 29/27
	var proceed = true;
	
	
// 	for (i = 0; i < membersList.options.length; i++)
// 	{
// 		if (membersList.options[i].selected)
// 			numSel++;
// 	}
	
	var message;
	
	switch (memListID)
	{
		case 'readers_target_list':
			message = "The list of members who have read-only access to this project is empty.  Are you sure you wish to proceed?";
		break;
		
		case 'writers_target_list':
			message = "The list of members who have write access to this project is empty.  Are you sure you wish to proceed?";
		break;
		
		default:

		break;
	}
	
	if (numSel == 0)
		proceed = confirm(message);

	return proceed;
}

function verifyProjectName(pNameID)
{
	var packetNameField = document.getElementById(pNameID);
	var projectOwnerWarning = document.getElementById("projectOwnerWarning");
	var projectNameWarning = document.getElementById("projectNameWarning");
	var projectDescrWarning = document.getElementById("projectDescrWarning");

	// Hide owner warning if shown
	projectOwnerWarning.style.display="none";
	projectDescrWarning.style.display="none";

	if (trimAll(packetNameField.value).length == 0)
	{
		alert("Please specify a name for your project.");
		packetNameField.focus();
		projectNameWarning.style.display = "inline";

		return false;
	}

	return true;
}


// June  3, 2010
function verifyProjectDescr(pDescrID)
{
	var packetDescrField = document.getElementById(pDescrID);

	var projectOwnerWarning = document.getElementById("projectOwnerWarning");
	var projectNameWarning = document.getElementById("projectNameWarning");
	var projectDescrWarning = document.getElementById("projectDescrWarning");

	// Hide owner warning if shown
	projectOwnerWarning.style.display="none";
	projectNameWarning.style.display="none";

	if (trimAll(packetDescrField.value).length == 0)
	{
		alert("Please provide a project description.");
		packetDescrField.focus();
		projectDescrWarning.style.display = "inline";

		return false;
	}

	return true;
}

// Added July 25/08
function verifyProjectOwner(ownersList)
{
	var projectOwnersList = document.getElementById(ownersList);
	var projectOwnerWarning = document.getElementById("projectOwnerWarning");
	var projectNameWarning = document.getElementById("projectNameWarning");
	var projectDescrWarning = document.getElementById("projectDescrWarning");

	projectNameWarning.style.display = "none";
	projectDescrWarning.style.display = "none";

	if (projectOwnersList.selectedIndex == 0)
	{
		alert("Please select a project owner.");
		projectOwnersList.focus();
		projectOwnerWarning.style.display="inline";

		return false;0
	}

	return true;
}

function showProject0Details()
{
	var projectList = document.getElementById("packetList");
	var projectSelInd = projectList.selectedIndex;
	var projectDetails = document.getElementById("project_details");
	
	var projectSelValue = projectList[projectSelInd].text;
	var projectName_array = projectSelValue.split(":");		// Will always have 3 elements: project ID, owner, project name
	
	var packetName = document.getElementById("packet_name");
	var submitBtn = document.getElementById("submit_project_modify");
	
	if (projectSelInd == 0)
	{
		projectDetails.style.display = "none";
	}
	else
	{
		projectDetails.style.display = "inline";
		packetName.value = trimAll(projectName_array[2]);	// again, project name is the last element of projectName_array
	}
}
//	User Management functions
//	June 28, 2007, Marina


// Called at step 1 to move members from source list to readers, writers or admin list, depending on access level selected
function addProjects(srcListID, role)
{
// alert(srcListID);
	var targetListID;
	
	switch (role)
	{
		case 'read':
			targetListID = "user_projects_readonly";
		break;
		
		case 'write':
			targetListID = "user_projects_write";
		break;

		// Added Sept. 9/07: This is the case where the user is a Reader, and there is no write project list for him - only one source and one target list
		case 'readonly':
			targetListID = "user_projects_read";
		break;

		case 'rmv_read':
			targetListID = "packetListRead";
		break;

		case 'rmv_write':
			targetListID = "packetListWrite";
		break;

		default:
			// move back to original list
			targetListID = "packetList";
		break;
	}
	
	moveListElements(srcListID, targetListID, true);
}

// Oct. 7, 2010: remove user projects and return to appropriate lab projects list
function moveProjectToLabListSpecific(fromListID)
{
	var fromList = document.getElementById(fromListID);

	var selElems = getSelectedOptions(fromListID);
	var i;

	for (i = 0; i < selElems.length; i++)
	{
		tmpOptn = selElems[i];

		optID = tmpOptn.id;

		if (optID.indexOf("lab_") == 0)
		{
			projInd = optID.indexOf("_project_");
			labID = optID.substring("lab_".length, projInd);
			labListID = "lab_projects_" + labID;

			labList = document.getElementById(labListID);
	
			if (labList.options.length == 0)
			{
				labList.options.add(tmpOptn);
				removeElement(fromList, tmpOptn);
			}
			else
			{
				addNumElement(labList, labList.options.length, tmpOptn);
				removeElement(fromList, tmpOptn);
			}

			clearAllElements(labListID);
		}
	}
}

// Oct. 8, 2010
function moveProjectToUserListSpecific(fromListID)
{
	var fromList = document.getElementById(fromListID);
// 	var userSearchList = document.getElementById("searchByMembers");
// 	var selUser = userSearchList[userSearchList.selectedIndex].value;
// 	var uProjList = document.getElementById("userPackets_" + selUser);

	var selElems = getSelectedOptions(fromListID);
	var i;

	for (i = 0; i < selElems.length; i++)
	{
		tmpOptn = selElems[i];

		optID = tmpOptn.id;
// alert(optID);
		if (optID.indexOf("_user_projects_") > 0)
		{
			userInd = optID.indexOf("_user_projects_");
			userID = optID.substring(0, userInd);
			userListID = "userPackets_" + userID;

			userList = document.getElementById(userListID);
	
			if (userList.options.length == 0)
			{
				userList.options.add(tmpOptn);
				removeElement(fromList, tmpOptn);
			}
			else
			{
				addNumElement(userList, userList.options.length, tmpOptn);
				removeElement(fromList, tmpOptn);
			}

			clearAllElements(userListID);
		}
		else if (optID.indexOf("lab_") == 0)
		{
			moveProjectToLabListSpecific(fromListID);
/*
			projectInd = optID.indexOf("_project_");
			labID = optID.substring("lab_".length, projectInd);
			labListID = "lab_projects_" + labID;

			labList = document.getElementById(labListID);

			if (labList.options.length == 0)
			{
				labList.options.add(tmpOptn);
				removeElement(fromList, tmpOptn);
			}
			else
			{
				addNumElement(labList, labList.options.length, tmpOptn);
				removeElement(fromList, tmpOptn);
			}
			clearAllElements(labListID);*/
		}
	}
}


// Oct. 8, 2010
function showUserProjects(userID)
{
// 	alert(userID);

	packetDivID = userID + "_projects";
	uPackets = document.getElementById(packetDivID);

	if (uPackets)
		uPackets.style.display="table-row";

	userProjectCaption = document.getElementById("userProjectCaption_" + userID);

	if (userProjectCaption)
		userProjectCaption.style.display="inline";

	// hide the rest of user packet TRs
	allRows = document.getElementsByTagName("TR");

	for (i=0; i < allRows.length; i++)
	{
		aRow = allRows[i];

		if (aRow.id && (aRow.id.indexOf("_projects") > 0) && (aRow != uPackets))
		{
			aRow.style.display = "none";
		}
	}

	document.getElementById("addReadProjectsUser").onclick = function(){addProjects("userPackets_" + userID, 'readonly')};
}


function showProjectKeywordSearch()
{
	document.getElementById("projectKeywdSearch").style.display="table-row";
}

function hideKeywd()
{
	document.getElementById("projectKeywdSearch").style.display="none";
	document.getElementById("project_search_results").style.display="none";
}


function searchProjectByKeyword()
{
	xmlhttp = createXML();

	var keywd = document.getElementById("project_keywd").value;

	url = cgiPath + "project_request_handler.py?keyword=" + keywd + "&search_project_by_keyword";

	xmlhttp.open("GET", url, false);

	xmlhttp.send('');

 	xmlhttp.onreadystatechange = printProjectSearchResults(xmlhttp);
}

function printProjectSearchResults(xmlhttp)
{
	var projectSearchResults = document.getElementById("project_search_results");
	projectSearchResults.style.display = "table-row";

	var keywdProjectMatchList = document.getElementById("keywdProjectMatchList");

	if (xmlhttp.readyState == 4)
	{
		if (xmlhttp.status == 200)
		{
			response = trimAll(xmlhttp.responseText);	// comma-delimited

			respAr = response.split(", ");

			respAr.sort();

			for (i=0; i < respAr.length; i++)
			{
				pText = respAr[i];

				pNum = pText.substring(0, pText.indexOf(":"));

				tmpOptn = document.createElement("OPTION");

				tmpOptn.value = pNum;
				tmpOptn.text = pText;
				keywdProjectMatchList.options[i] = tmpOptn;
			}
		}
	}
}

function showAllProjects()
{
	document.getElementById("viewAllProjects").style.display="table-row";
}

function hideAllProjects()
{
	document.getElementById("viewAllProjects").style.display="none";
}


function hideUserProjects()
{
	// get the selected labID and hide the corresponding project list
	var usersList = document.getElementById("searchByMembers");
// 	alert(labsList.selectedIndex);
	var userSelInd = usersList.selectedIndex;
	var selUserID = usersList[userSelInd].value;
// alert(selLabID);

	var userProjectList = document.getElementById(selUserID+ "_projects");

	// no lab project list might be shown at this point
	if (userProjectList)
		userProjectList.style.display = "none";
	
// 	document.getElementById("project_read_checkbox").style.display = "none";
// 	document.getElementById("projectCaption").style.display = "none";
}


function removeProjects(srcListID)
{
	var srcList = document.getElementById(srcListID);
	var selElems = getSelectedOptions(srcListID);
	
	for (i = 0; i < selElems.length; i++)
	{
		rmvOptn = selElems[i].value;
		removeListElement(srcList, rmvOptn);
	}
	
}

function showHidePrivileges()
{
	var radioBtns = document.getElementsByTagName("INPUT");
	var accessLevels = document.getElementById("user_access");
	var projectAccess = document.getElementById("project_access");

	for (r=0; r < radioBtns.length; r++)
	{
		tmpInput = radioBtns[r];
		//alert(tmpInput.type);
		//alert(tmpInput.name);
		//alert(tmpInput.value);
		
		if ((tmpInput.type == 'radio') && (tmpInput.name == 'privChoiceRadio') && (tmpInput.value == 'override') && (tmpInput.checked))
		{
			accessLevels.style.display = "table-row";
			break;
		}
		else
		{
			accessLevels.style.display = "none";

			// also hide project access level section
			// Change of heart Aug 12/07 - show project list at all times, regardless of access level
//			projectAccess.style.display = "none";
		}
	}
}

function showHideWriteProjectAccess()
{
	var projectWriteAccessTable = document.getElementById("write_project_access");
	var projectReadAccessTable = document.getElementById("read_project_access");
	var readNote = document.getElementById("read_note");
	var radioBtns = document.getElementsByTagName("INPUT");
	
	for (r=0; r < radioBtns.length; r++)
	{
		tmpInput = radioBtns[r];
		
// 		alert("Type " + tmpInput.type);
// 		alert("Name " + tmpInput.name);
// 		alert("Value " + tmpInput.value);
		
		if ((tmpInput.type == 'radio') && (tmpInput.name == 'system_access_level') && (tmpInput.checked) && (tmpInput.value != 'Reader'))
		{
			projectReadAccessTable.style.display = "none";
			projectWriteAccessTable.style.display = "table-row";
			readNote.style.display = "none";
			break;		// found what we're looking for, no need to go on
		}
		else
		{
			projectReadAccessTable.style.display = "table-row";
			projectWriteAccessTable.style.display = "none";

			// BUT here, DON'T put 'break', b/c we haven't found what we need, keep looking!!!
		}
	}	
}

function verifyAddUser()
{
	return verifyLabSelected() && verifyUsername() && verifyPassword() && verifyFirstname() && verifyLastname() && verifyPosition();
}

function verifyLabSelected()
{
	var labList = document.getElementById("labList");
	var labSelInd = labList.selectedIndex;
	var labWarning = document.getElementById("lab_warning");
	
	if (labSelInd <= 0)
	{
		alert("You must select a Laboratory to continue");
		labWarning.focus();
		labWarning.style.display = "inline";
		return false;
	}
	else
	{
		labWarning.style.display = "none";
		return true;
	}
}

function verifyUsername()
{
	var username = document.getElementById("user_name");

	if (username.value.length == 0)
	{
		alert ("Please provide a username.");
		username.focus();
		return false;
	}
	
	return true;
}


// Jan. 14, 2010: Check mandatory properties at container **type** creation/mopdification
function verifyContainerType()
{
	return verifyContainerTypeName() && verifyContainerTypeCode();
}


function verifyContainerTypeName()
{
	var contTypeName = document.getElementById("containerTypeName");

	if (contTypeName)
	{
		if (contTypeName.value.length == 0)
		{
			alert("Plese provide a name for the new container type.");
			contTypeName.focus();
			return false;
		}
	}

	return true;
}


function verifyContainerTypeCode()
{
	var contCode = document.getElementById("containerCode");
	var alpha = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	

	if (contCode)
	{
		if (contCode.value.length == 0)
		{
			alert("Plese provide a two-letter unique identifier for new container type.");
			contCode.focus();
			return false;
		}
		else if (contCode.value.length != 2)
		{
			alert("Container type codes must be 2 characters in length.  Plese verify your input.");
			contCode.focus();
			return false;
		}
		else
		{
			for (i=0; i < contCode.value.length; i++)
			{
				var ch = contCode.value.charAt(i).toUpperCase();
	
				if (alpha.indexOf(ch) < 0)
				{
					alert("Container type identifiers must be Latin alphabetic characters only, other characters and numbers are not allowed.");
					contCode.focus();
					return false;
				}
			}
		}
	}

	return true;
}


// Dec. 21/09, update Jan. 13, 2010 - verify mandatory properties when adding/modifying a **container**
function confirmMandatoryLocation()
{
	return verifyContainerName() && verifyContainerType() && verifyContainerSize() && /*&& verifyContainerCode()*/ verifyStorageType() && verifyStorageName();
}


// Jan. 13, 2010
function checkLocationNumeric()
{
	// shelf, rack, row, column
	var shelfInput = document.getElementById("contShelf");
	var rackInput = document.getElementById("contRack");
	var rowInput = document.getElementById("contRow");
	var colInput = document.getElementById("contCol");

	var shelf = shelfInput.value;
	var rack = rackInput.value;
	var row = rowInput.value;
	var col = colInput.value;

	var numbers = "0123456789";
	var numbers_nozero = "123456789";

	if (shelf.length == 1)
	{
		if (numbers_nozero.indexOf(shelf) < 0)
		{
			alert("Shelf indices must be non-zero numeric values.  Please verify your input.");
			shelfInput.focus();
			return false;
		}
	}
	else
	{
		for (i=0; i < shelf.length; i++)
		{
			ch = shelf.charAt(i);

			if (numbers.indexOf(ch) < 0)
			{
				alert("Shelf indices must be non-zero numeric values.  Please verify your input.");
				shelfInput.focus();
				return false;
			}
		}
	}

	if (rack.length == 1)
	{
		if (numbers_nozero.indexOf(rack) < 0)
		{
			alert("Rack indices must be non-zero numeric values.  Please verify your input.");
			rackInput.focus();
			return false;
		}
	}
	else
	{
		for (i=0; i < rack.length; i++)
		{
			ch = rack.charAt(i);

			if (numbers.indexOf(ch) < 0)
			{
				alert("Rack indices must be non-zero numeric values.  Please verify your input.");
				rackInput.focus();
				return false;
			}
		}
	}

	if (col.length == 1)
	{
		if (numbers_nozero.indexOf(col) < 0)
		{
			alert("Column indices must be non-zero numeric values.  Please verify your input.");
			colInput.focus();
			return false;
		}
	}
	else
	{
		for (i=0; i < col.length; i++)
		{
			ch = col.charAt(i);

			if (numbers.indexOf(ch) < 0)
			{
				alert("Column indices must be non-zero numeric values.  Please verify your input.");
				colInput.focus();
				return false;
			}
		}
	}

	if (row.length == 1)
	{
		if (numbers_nozero.indexOf(row) < 0)
		{
			alert("Row indices must be non-zero numeric values.  Please verify your input.");
			rowInput.focus();
			return false;
		}
	}
	else
	{
		for (i=0; i < row.length; i++)
		{
			ch = row.charAt(i);

			if (numbers.indexOf(ch) < 0)
			{
				alert("Row indices must be non-zero numeric values.  Please verify your input.");
				rowInput.focus();
				return false;
			}
		}
	}
}


function verifyContainerName()
{
	var contName = document.getElementById("containerName");

	if (contName)
	{
		if (contName.value.length == 0)
		{
			alert("Plese provide a name for the new container");
			contName.focus();
			return false;
		}
	}

	return true;
}


// Dec. 21/09
function verifyContainerCode()
{
	var contCode = document.getElementById("containerCode");

	if (contCode)
	{
		if (contCode.value.length == 0)
		{
			alert("Plese provide a two-letter unique identifier for new container");
			contCode.focus();
			return false;
		}
	}

	return true;
}


// Jan. 13, 2010
function verifyStorageType()
{
	var storageType = document.getElementById("location_types");

	if (storageType)
	{
		if (storageType.selectedIndex == 0)
		{
			alert("Please select a storage type for the new container.");

			storageType.style.color = "#FF0000";
			storageType.focus();
			return false;
		}
	}

	storageType.style.color = "#000000";
	return true;
}

// Dec. 1, 2010
function verifyContainerType()
{
	var containerType = document.getElementById("contGroupBox");

	if (containerType)
	{
		if (containerType.selectedIndex == 0)
		{
			alert("Please select a container type from the list.\n\n  If the sought container type is not in the list, create it using the 'Add Container Type' menu option or contact an administrator if this option is not available.");

			containerType.style.color = "#FF0000";
			containerType.focus();
			return false;
		}
	}

	containerType.style.color = "#000000";
	return true;
}


// Dec. 1, 2010
function verifyContainerSize()
{
	var containerSizes = document.getElementById("contSizeList");

	if (containerSizes)
	{
		if (containerSizes.selectedIndex == 0)
		{
			alert("Please select a container size from the list.\n\n  If the sought container size is not in the list, create it using the 'Add Container Size' menu option or contact an administrator if this option is not available.");

			containerSizes.style.color = "#FF0000";
			containerSizes.focus();
			return false;
		}
	}

	containerSizes.style.color = "#000000";
	return true;
}


function verifyStorageName()
{
	var storageName = document.getElementById('locationName');

	if (storageName)
	{
		if (storageName.value.length == 0)
		{
			alert("Please provide a storage name for the new container.");
			storageName.focus();
			return false;
		}
	
	}
	return true;
}

function verifyPassword()
{
	var passwd = document.getElementById("passwd");
	
	if (passwd.value.length == 0)
	{
		alert("Plese enter a valid password for the new user");
		passwd.focus();
		return false;
	}
	
	return true;
}

function checkLab()
{
	var labName = document.getElementById("lab_name").value;

	if (labName.length == 0)
	{
		alert("Please provide a name for the laboratory.");
		document.getElementById("lab_name").focus();
		return false;
	}

	var labCodeField = document.getElementById("lab_id");
	var labCode = labCodeField.value;

	if (labCode.length == 0)
	{
		alert("Please provide a 2-character laboratory identifier.");
		document.getElementById("lab_id").focus();
		return false;
	}

	var alpha = "abcdefghijklmnopqrstuvwxyz";

	if (labCode.length > 2)
	{
		alert("Lab code must be exactly 2 characters in length.");
		labCodeField.focus();
		return false;
	}
	else
	{
		var count1 = 0;
		var count2 = 0;

		for (i = 0; i < alpha.length; i++)
		{
			aChar = alpha[i];

			if (labCode.toLowerCase().charAt(0) == aChar)
				count1++;

			if (labCode.toLowerCase().charAt(1) == aChar)
				count2++;
		}

		if ( (count1 == 0) || (count2 == 0) )
		{
			alert("Lab code must contain alpha characters only.");
			labCodeField.focus();
			return false;
		}
	}

	
	var titles = document.getElementById("titlesList");

	if (titles)
	{
		var titleIndex = titles.selectedIndex;
		var selTitle = titles[titleIndex].value;
	}

	var labHead = document.getElementById("lab_head").value;

	if (labHead.length == 0)
	{
		alert("Please enter the name of the laboratory owner or PI.");
		document.getElementById("lab_head").focus();
		return false;
	}


	var labDescr = document.getElementById("lab_descr").value;
	var labAddress = document.getElementById("lab_location").value;

	var allInputs = document.getElementsByTagName("INPUT");
	var labAccess;

	for (a in allInputs)
	{
		tmpInput = allInputs[a];

		if ( (tmpInput.type == 'radio') && (tmpInput.name == 'system_access_level') && (tmpInput.checked) )
		{
			labAccess = tmpInput.value;
			break;
		}
	}
}

function verifyPosition()
{
	var position = document.getElementById("positions_list"); 
	var posSelInd = position.selectedIndex;
	var posWarn = document.getElementById("position_warning");
	
	if (posSelInd <= 0)
	{
		alert("Please select a Position/Department to continue");
		posWarn.focus();
		posWarn.style.display = "inline";
		return false;
	}
	
	return true;
}

function enableSubmit(myListID, submitID)
{
	var myList = document.getElementById(myListID);
	var submitBtn = document.getElementById(submitID);
	
	//var numSel = getNumSelected(myList);
	
//	if ((numSel > 0) && (myList.options.length > 0))
	if (myList.options.length > 0)
	{
		submitBtn.disabled = false;
	}
	else
	{
		submitBtn.disabled = true;
	}
}

function enableParents(pListID)
{
	var parentList = document.getElementById(pListID);

	if (parentList)
	{
		for (p=0; p < parentList.options.length; p++)
		{
			tmpOpt = parentList.options[p];
			tmpOpt.disabled=false;
		}
	}
}

// Jan. 13, 2010
function enableText()
{
	var allInputs = document.getElementsByTagName("INPUT");
	var i, j;
	var tmpInput;

	for (i=0; i < allInputs.length; i++)
	{
		tmpInput = allInputs[i];
	
		if (tmpInput.type.toLowerCase() == 'text')
		{
			tmpInput.disabled = false;	// Jan. 7, 2010
		}
	}
}


// Jan. 29, 2010
function enableRadio()
{
	var allInputs = document.getElementsByTagName("INPUT");
	var i, j;
	var tmpInput;

	for (i=0; i < allInputs.length; i++)
	{
		tmpInput = allInputs[i];
	
		if (tmpInput.type.toLowerCase() == 'radio')
		{
			tmpInput.disabled = false;	// Jan. 7, 2010
		}
	}
}


function enableSelect()
{
	var allSelects = document.getElementsByTagName("SELECT");
	var i, j;
	var tmpSelect;

	for (i=0; i < allSelects.length; i++)
	{
		tmpSelect = allSelects[i];

// alert(tmpSelect.multiple);

		// April 19, 2010: Execute ONLY for MULTIPLE selection lists (at reagent type creation/modification).  Causes problems with normal lists!!!

		// May 25, 2010 - need to enable single lists too though; remove the 'if' temporarily and check
// 		if (tmpSelect.multiple)
// 		{
			tmpSelect.disabled = false;	// Jan. 7, 2010
	
			for (j = 0; j < tmpSelect.options.length; j++)
			{
				tmpOptn = tmpSelect.options[j];
				tmpOptn.disabled = false;
			}
// 		}
	}
}

function checkSelectUser(fromList, toList, actn)
{
	var srcList = document.getElementById(fromList);
	var targetList = document.getElementById(toList);
	var addDelBtn = document.getElementById(actn);
	
	var numSel = getNumSelected(srcList);
	
	if (numSel == 0)
	{
		alert("Please select at least one member from the dropdown list");
		return false;
	}
	else
	{
		moveListElements(fromList, toList, false);
		
		// if candidates list becomes empty, disable Submit button
		//if (fromList == 'deletion_candidates_list')
		//{
			enableSubmit('deletion_candidates_list', 'deleteUserBtn');
		//}
		
		return true;
	}
}

function verifyFirstname()
{
	var fName = document.getElementById("first_name");

	if (fName.value.length == 0)
	{
		alert ("Please specify the user's first name.");
		fName.focus();
		return false;
	}
	
	return true;
}

function verifyLastname()
{
	var lName = document.getElementById("last_name");

	if (lName.value.length == 0)
	{
		alert ("Please specify the user's last name.");
		lName.focus();
		return false;
	}
	
	return true;
}

function verifyDeleteUser()
{
	return confirm("Are you sure you want to delete this user?");
}


function verifyDeleteLab()
{
	return confirm("Are you sure you want to delete this laboratory?");
}

function confirmDeleteProject()
{
	return confirm("Are you sure you want to delete this project?");
}

// June 3/09
function confirmDeleteReagentType()
{
	return confirm("Are you sure you want to delete this reagent type?");
}

// Redirect to CURRENT user Detailed View through menu link
function redirectToCurrentUserDetailedView(userID)
{
	document.curr_user_form.submit();
}


// Redirect to User Detailed View from Lab Detailed View
function redirectToUserDetailedView(userID)
{
	var userInput = document.getElementById("view_user_hidden");

	if (userInput)
		userInput.value = userID;

	if (document.lab_form)
		document.lab_form.submit();

	else if (document.user_form)
		document.user_form.submit();
}


// Redirect to Lab Detailed View from User Detailed View
function redirectToLabView(labID)
{
	var labInput = document.getElementById("view_lab_hidden");
	labInput.value = labID;
	document.user_form.submit();
}


// Redirect to Project Detailed View from User Detailed View
function redirectToProjectDetailedView(packetID)
{
	var projectForm = document.getElementById("viewProjectForm");
	var projectInput = document.getElementById("view_packet_hidden");
	projectInput.value = packetID;
	projectForm.submit();
}


// Redirect to User Detailed View from Project Detailed View
function redirectToUserFromProject(userID)
{
	var userForm = document.getElementById("viewUserForm");
	var userInput = document.getElementById("view_user_hidden");
	userInput.value = userID;
	userForm.submit();
}

// Redirect to Lab view from Project Details page
function goToLabViewFromProject(labID)
{
	var labForm = document.getElementById("viewLabForm");
	var labInput = document.getElementById("view_lab_hidden");
	
	labInput.value = labID;
	labForm.submit();
}

function removeLabMembers(labListName)
{
	var labList = document.getElementById(labListName);
	var selMembers = getSelectedElements(labListName);	// list of actual member ID VALUES
	
	for (i = 0; i < selMembers.length; i++)
	{
		tmpMember = selMembers[i];
		removeListElement(labList, tmpMember);
	}
}

// March 23, 2010
// function showMultiple(cbID, listID, textID, btnID)
function showMultiple(cbID, tblID, listID)
{
// alert(cbID);
// alert(tblID);
// alert(listID);

// 	checkMult = document.getElementById(cbID);
	propList = document.getElementById(listID);

	mult_cbLabel = document.getElementById("mult_" + cbID + "_label");
// alert(mult_cbLabel );
	one_cbLabel = document.getElementById("one_" + cbID + "_label");

	attrID = cbID.substring(cbID.lastIndexOf("_")+1, cbID.length);
// alert(attrID);
	otherTxt = document.getElementById("other_text_" + attrID);

	showMoreImg = document.getElementById("expand_" + cbID);
	showLessImg = document.getElementById("collapse_" + cbID);

	// May 11, 2010
	mult_tbl = document.getElementById(tblID);

// 	if (checkMult.checked)
	if (mult_tbl.style.display == "none")
	{
		propList.style.display = "none";
		mult_tbl.style.display = "inline";

// 		cbLabel.innerHTML = "Remove Multiple";
		mult_cbLabel.style.display = "none";
		one_cbLabel.style.display = "inline";

// 		checkMult.checked = false;
		showMoreImg.style.display = "none";
		showLessImg.style.display = "inline";

		otherTxt.style.display = "none";
	}
	else
	{
		propList.style.display = "inline";
		mult_tbl.style.display = "none";

// 		cbLabel.innerHTML = "Select Multiple";
		mult_cbLabel.style.display = "inline";
		one_cbLabel.style.display = "none";

// 		checkMult.checked = false;
		showMoreImg.style.display = "inline";
		showLessImg.style.display = "none";

// 		if (otherTxt.innerHTML == "")
// 			otherTxt.style.display = "none";
// 		else
// 			otherTxt.style.display = "inline";
	}
}

// May 13/09
function searchMultipleReagentTypes()
{
	checkMult = document.getElementById("searchMultipleReagentTypesCheckbox");
	rTypesList = document.getElementById("reagentTypesFilterList");
	
	// For the search reagents view only
// 	rTypesList.name = "filter[]";
	
	if (checkMult.checked)
		rTypesList.multiple="multiple";
	else
		rTypesList.multiple="";
}

function searchMultipleProjects()
{
	checkMult = document.getElementById("searchMultiple");
	packetList = document.getElementById("packetList");
	
	// For the search reagents view only
	packetList.name = "packets[]";
	
	if (checkMult.checked)
		packetList.multiple="multiple";
	else
		packetList.multiple="";
}


// On Change Password page: make sure all 3 fields (old password, new password, confirm new) have been filled in
function checkNonEmptyPassword()
{
	var oldPasswd = document.getElementById("old_passwd");
	var newPasswd = document.getElementById("new_passwd");
	var newPasswdConfirm = document.getElementById("new_passwd_confirm");
	
	if ( (oldPasswd.value != "") && (newPasswd.value != "") && (newPasswdConfirm.value != "") )
	{
		return true;
	}
	else
	{
		alert("Please fill in all form fields");
		return false;
	}
}


// The following functions are required for User Search
function showLabList()
{
	var labList = document.getElementById("labList");
	
	if (labList.style.display == "none")
		labList.style.display = "table-row";
	else
		labList.style.display = "none";
}

function showPosition()
{
	var positions = document.getElementById("positions");
	
	if (positions.style.display == "none")
	{
		positions.style.display = "table-row";
	}
	else
	{
		positions.style.display = "none";
	}
}

function showFirstName()
{
	var firstNameField = document.getElementById("firstNameField");
	var fName = document.getElementById("fName");

	if (firstNameField.style.display == "none")
	{
		firstNameField.style.display = "table-row";
		fName.focus();
	}
	else
	{
		firstNameField.style.display = "none";
	}
}

function showLastName()
{
	var lastNameField = document.getElementById("lastNameField");
	var lName = document.getElementById("lName");

	if (lastNameField.style.display == "none")
	{
		lastNameField.style.display = "table-row";
		lName.focus();
	}
	else
	{
		lastNameField.style.display = "none";
	}
}


// Invoked in User Modify view, to remove projects from the user's read/write project list and return to the project pool
function removeUserProjects()
{
	var readProjListID = "user_projects_readonly";
	var writeProjListID = "user_projects_write";

	var readProjList = document.getElementById(readProjListID);
	var writeProjList = document.getElementById(writeProjListID);

	var targetListID = "packetList";
	var targetList = document.getElementById(targetListID);
	
	var selReadProj = getSelectedOptions(readProjListID);
	
	if (writeProjList)
		var selWriteProj = getSelectedOptions(writeProjListID);
	
	// Move read-only projects first, then move writeable projects
	for (r = 0; r < selReadProj.length; r++)
	{
		rdrOpt = selReadProj[r];
		rdrOptID = rdrOpt.id;
		
		// Not using moveListElements, since it incorrectly moves ALL values in the source list to the same target list
		if (targetList.options.length == 0)
		{
			targetList.options.add(rdrOpt);
			removeElement(readProjList, rdrOpt);
			
		}
		else
		{
			addNumElement(targetList, targetList.options.length, rdrOpt);
			removeElement(readProjList, rdrOpt);
		}
		
		clearAllElements(targetListID);
	}

	// writeable projects - IFF they exist (may be absent for readers)
	if (writeProjList)
	{
		for (w = 0; w < selWriteProj.length; w++)
		{
			wrtrOpt = selWriteProj[w];
			wrtrOptID = wrtrOpt.id;

			if (targetList.options.length == 0)
			{
				targetList.options.add(wrtrOpt);
				removeElement(writeProjList, wrtrOpt);
			}
			else
			{
				addNumElement(targetList, targetList.options.length, wrtrOpt);
				removeElement(writeProjList, wrtrOpt);
			}
			
			clearAllElements(targetListID);
		}
	}

	clearCheckboxes(readProjListID, targetListID);	
	
	if (writeProjList)
		clearCheckboxes(writeProjListID, targetListID);
}


// Called upon exit from User modification view, to check that if a user's access level is set to Reader, there are no Write projects assigned to him/her
function verifyWriteProjects()
{
	var userCategoriesList = document.getElementById("user_category");
	var selectedInd = userCategoriesList.selectedIndex;
	var selectedValue = userCategoriesList[selectedInd].value;
	var writeProjList = document.getElementById("user_projects_write");
 
	// value 1 represents 'Reader' - corresponds to actual db value
	if ( (selectedValue == '4') && writeProjList && (writeProjList.options.length > 0))
	{
		alert("You may not make this user a Reader while s/he has Write access to projects.  Please clear the user's Write Project list before changing the access level.");
		return false;
	}

	return true;
}


// Mass Spectrometry module functions
// September 2007, Marina

// In 'Input Samples and Results' view: Show/hide the appropriate dropdown list depending on the radio button selected
function showHideSamplesResultsOptions(optionID)
{
	// radio button option for 'Input Samples and Results'
	var radOptn = document.getElementById(optionID).value;

	// Corresponding dropdown lists for each option
	var dd_1 = document.getElementById("input_bio_samples_select");
	var dd_2 = document.getElementById("process_ms_results_select");
	var dd_3 = document.getElementById("input_for_quant_analysis_select");

	switch (radOptn)
	{
		case '1':
			dd_1.style.display = "inline";
			dd_2.style.display = "none";
			dd_3.style.display = "none";
		break;

		case '2':
			dd_1.style.display = "none";
			dd_2.style.display = "inline";
			dd_3.style.display = "none";
		break;

		case '3':
			dd_1.style.display = "none";
			dd_2.style.display = "none";
			dd_3.style.display = "inline";
		break;

		case '4':
			dd_1.style.display = "none";
			dd_2.style.display = "none";
			dd_3.style.display = "none";
		break;
	}
}

function verifyWellsSelected()
{
	var allInput = document.getElementsByTagName("INPUT");
	var numChecked = 0;

	for (i = 0; i < allInput.length; i++)
	{
		tmpInput = allInput[i];

		if ( (tmpInput.type == "checkbox") && (tmpInput.name == "wells_checkbox[]") && tmpInput.checked )
		{
			numChecked++;
		}
	}

	if (numChecked == 0)
	{
		alert("Please select at least one well in order to proceed");

		// June 7, 2011: Reset the attributes dropdown
		var changeAttributeSelect = document.getElementById('changeAttributeSelect');

		if (changeAttributeSelect)
		{
			changeAttributeSelect.selectedIndex = 0;
		}

		return false;
	}

	return true;
}


// Oct. 3/07: Create XMLHttpRequest object for AJAX
function createXML()
{
	var xmlhttp = false;

	if (!xmlhttp && typeof XMLHttpRequest!='undefined') 
	{
		try 
		{
			xmlhttp = new XMLHttpRequest();
		}
		catch (e) 
		{
			xmlhttp=false;
		}
	}
	if (!xmlhttp && window.createRequest) 
	{
		try
		{
			xmlhttp = window.createRequest();
		} 
		catch (e)
		{
			xmlhttp=false;
		}
	}

	return xmlhttp;
}


function searchProt()
{
	xmlhttp = createXML();

	// get form arguments to pass to Perl script
	var keywd = document.getElementById("search_keyword").value;
	var artif = document.getElementById("noArtifacts").value;
	var minProb = document.getElementById("minProb").value;
	var minScore = document.getElementById("minScore").value;
	var maxFreq = document.getElementById("maxFreq").value;

	var currUserID = document.getElementById("curr_user_hidden").value;

      	xmlhttp.open("GET", url, false);

      	xmlhttp.send('');

// 	xmlhttp.send(null);
 	xmlhttp.onreadystatechange = printSearchResults(xmlhttp);
}


function printSearchResults(xmlhttp)
{
	var resultRow = document.getElementById("result_row");
	var resultDiv = document.getElementById("prot_search_result_hidden");

// 	alert(xmlhttp.responseText);

	if (xmlhttp.readyState == 4)
	{
		resultRow.style.display = "table-row";
		resultDiv.innerHTML = xmlhttp.responseText;
	}
}

// October 17/07: Use BioPython to get a list of REBASE enzymes
// Updated Nov. 12/07: As there are distinct OpenFreezer versions (devel, test, live), specify the complete path
function getEnzymes(scriptPath)
{
	xmlhttp = createXML();
	url = scriptPath + "restriction_sites.py";

	xmlhttp.open("POST", url, false);
	xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	params = ''
	xmlhttp.send(params);

	xmlhttp.onreadystatechange = printEnzymeList(xmlhttp);
}


function verifyHybridSites(inputID, enzListID)
{
	var gatewayLoxPNoneSites = ['attB1', 'attB2', 'attP1', 'attP2', 'attL1', 'attL2', 'attR1', 'attR2', 'LoxP', 'None'];

	enzList = document.getElementById(enzListID);
// 	enzList = ['AscI','PacI', 'EcoRI', 'BamHI', 'BglII'];	// debugging
// alert(enzList.id);
// return false;
		
	var siteField = document.getElementById(inputID);
	var site = siteField.value;	// always a text field
	
	var digits = "0123456789";

	if (!inArray(site, gatewayLoxPNoneSites))
	{
		for (d in digits)
		{
			if (site.indexOf(d) >= 0)
			{
				alert("Site names may contain Roman numerals only.  Please verify your input.");
				siteField.focus();
				return false;
			}
	
			if (site.indexOf('/') >= 0)
			{
				alert("Hybrid site names must be hyphen-delimited. Please verify your input");
				siteField.focus();
				return false;
			}
		}
	}
	else
	{
		alert("You may not use Gateway or LoxP sites to create a hybrid");
		return false;
	}

	// Make sure the hybrid restriction site provided is of the form EnzI-EnzII, where EnzI and EnzII both match a REBASE enzyme value and are separated by hyphen, no spaces
	var hyphenIndex = site.indexOf('-');

	if (hyphenIndex > 0)
	{
		var h1 = trimAll(site.substring(0, hyphenIndex));
		var h2 = trimAll(site.substring(hyphenIndex+1));

		var h1_matches = 0;
		var h2_matches = 0;
	
		for (i = 0; i < enzList.options.length; i++)
		{
			enz = enzList.options[i].value;
	
			if (enz == h1)
			{
				h1_matches++;
				break;
			}
		}
	
		for (i = 0; i < enzList.options.length; i++)
		{
			enz = enzList.options[i].value;
	
			if (enz == h2)
			{
				h2_matches++;
				break;
			}
		}
	
		if ((h1_matches > 0) && (h2_matches > 0))
		{
			return true;
		}
		else
		{
			alert("Hybrid restriction site names are CASE-SENSITIVE, of the form 'SiteI-SiteII', where SiteI and SiteII both match REBASE enzyme names, SEPARATED BY A HYPHEN.  Please verify your input.");
			siteField.focus();
			return false;
		}
	}
}

function printEnzymeList(xmlhttp)
{
	var allSelects = document.getElementsByTagName("SELECT");

	var enzymes;
	var enzList = new Array();
	var enzOpt;

	var selectedFivePrime = document.getElementById("fpcs_val");
	var selectedThreePrime = document.getElementById("tpcs_val");

	// Nov. 19/08: Add another field to store Insert sites
	var selectedFivePrime_Insert = document.getElementById("fpcs_val_insert");
	var selectedThreePrime_Insert = document.getElementById("tpcs_val_insert");

// Dec. 9/09: ?????
// 	var selectedRestrictionSite = document.getElementById("restriction_sites");

	// oct 28/08
	var prefix = "reagent_detailedview_";
	var postfix = "_prop";

	if (xmlhttp.readyState == 4)
	{
		enzymes = xmlhttp.responseText;
		enzList = enzymes.split(' ');

		for (a in allSelects)
		{
			if (allSelects[a])
			{
				if (allSelects[a].id)
				{
					// 5' site
					if ( (allSelects[a].id == "fpcs_list") || (allSelects[a].id == "fpcs_list_1") || (allSelects[a].id == "fpcs_list_2") || (allSelects[a].id == "fpcs_list_3"))
					{
						var fivePrimeSelect = allSelects[a];
			
						for (i=0; i < enzList.length; i++)
						{
							var tmpEnz = trimAll(enzList[i]);
				
							enzOpt = document.createElement("OPTION");
							enzOpt.text = tmpEnz;
							enzOpt.value = tmpEnz;
		
							fivePrimeSelect.options[i] = enzOpt;
						}
		
						// Add recombination and gateway sites - attB, attL, attP, LoxP
		
						// attB1
						attB_1_optn = document.createElement("OPTION");
						attB_1_optn.value = "attB1";
						attB_1_optn.text = "attB1";
						addElement(fivePrimeSelect, fivePrimeSelect.options.length-1, attB_1_optn);
		
		// 				// attB2
		// 				attB_2_optn = document.createElement("OPTION");
		// 				attB_2_optn.value = "attB2";
		// 				attB_2_optn.text = "attB2";
		// 				addElement(fivePrimeSelect, fivePrimeSelect.options.length-1, attB_2_optn);
		
						// attL1
						attL_1_optn = document.createElement("OPTION");
						attL_1_optn.value = "attL1";
						attL_1_optn.text = "attL1";
						addElement(fivePrimeSelect, fivePrimeSelect.options.length-1, attL_1_optn);
		
		// 				// attL2
		// 				attL_2_optn = document.createElement("OPTION");
		// 				attL_2_optn.value = "attL2";
		// 				attL_2_optn.text = "attL2";
		// 				addElement(fivePrimeSelect, fivePrimeSelect.options.length-1, attL_2_optn);
		
						// attP1
						attP_1_optn = document.createElement("OPTION");
						attP_1_optn.value = "attP1";
						attP_1_optn.text = "attP1";
						addElement(fivePrimeSelect, fivePrimeSelect.options.length-1, attP_1_optn);
		
		// 				// attP2
		// 				attP_2_optn = document.createElement("OPTION");
		// 				attP_2_optn.value = "attP2";
		// 				attP_2_optn.text = "attP2";
		// 				addElement(fivePrimeSelect, fivePrimeSelect.options.length-1, attP_2_optn);
		
						// attR1
						attR_1_optn = document.createElement("OPTION");
						attR_1_optn.value = "attR1";
						attR_1_optn.text = "attR1";
						addElement(fivePrimeSelect, fivePrimeSelect.options.length-1, attR_1_optn);
		
		// 				// attR2
		// 				attR_2_optn = document.createElement("OPTION");
		// 				attR_2_optn.value = "attR2";
		// 				attR_2_optn.text = "attR2";
		// 				addElement(fivePrimeSelect, fivePrimeSelect.options.length-1, attR_2_optn);
		
						// LoxP
						loxp_optn = document.createElement("OPTION");
						loxp_optn.value = 'LoxP';
						loxp_optn.text = 'LoxP';
						addElement(fivePrimeSelect, fivePrimeSelect.options.length-1, loxp_optn);
		
						// Feb. 5/08: Add option "None"
						noneOpt = document.createElement("OPTION");
						noneOpt.text = 'None';
						noneOpt.value = 'None';
						addElement(fivePrimeSelect, fivePrimeSelect.options.length-1, noneOpt);
		
						var fpInList = false;
		
						// Finally, select the current cloning site
						for (j = 0; j < fivePrimeSelect.options.length; j++)
						{
							aOptn = fivePrimeSelect.options[j].value;
		
							// Updated Nov. 19/08
							if (selectedFivePrime_Insert && fivePrimeSelect.id == "fpcs_list")
							{
								if (selectedFivePrime_Insert.value == aOptn)
								{
									fpInList = true;
									fivePrimeSelect.selectedIndex = j;
								}
							}
		
							else if (selectedFivePrime)
							{
								if (selectedFivePrime.value == aOptn)
								{
									fpInList = true;
									fivePrimeSelect.selectedIndex = j;
								}
							}
						}
		
						if (!fpInList)
						{
							// select 'Other' and show textbox
		
							// add option 'Other' first - Moved here Dec. 1/08
							otherOpt = document.createElement("OPTION");
							otherOpt.text = 'Other';
							otherOpt.value = 'Other';
							addElement(fivePrimeSelect, fivePrimeSelect.options.length-1, otherOpt);
		
							for (k = 0; k < fivePrimeSelect.options.length; k++)
							{
								if (fivePrimeSelect.options[k].value.toLowerCase() == 'other')
								{
									fivePrimeSelect.selectedIndex = k;
		
									allText = document.getElementsByTagName("INPUT");
		
									for (at in allText)
									{
										if (allText[at].name == "5_prime_cloning_site_name_txt")
										{
											allText[at].style.display = "inline";
										}
									}
								}
							}
						}
					}
		
					// Sept. 19/08: added Restriction Sites list
					// Change Nov. 9/09: Only fill lists with REBASE enzymes for Vectors and Inserts
		// 			else if ((allSelects[a].id == "Vector_restriction_site_:_list") || (allSelects[a].id == "Insert_restriction_site_:_list"))
					else if ((allSelects[a].id == "restriction_site_:_list") || (allSelects[a].id.indexOf("restriction_site_:_list") >= 0))
					{
						var restrSiteSelect = allSelects[a];
						
						// Oct. 28/08: Use the same strategy as with other feature lists - fetch the selected value from the field name
						var rsSelectName = allSelects[a].name;
						var selectedRestrictionSite = rsSelectName.substring(prefix.length+"restriction_site_:_".length, rsSelectName.indexOf("_start_"));
			
						for (i=0; i < enzList.length; i++)
						{
							var tmpEnz = trimAll(enzList[i]);
				
							enzOpt = document.createElement("OPTION");
							enzOpt.text = tmpEnz;
							enzOpt.value = tmpEnz;
		
							restrSiteSelect.options[i] = enzOpt;
						}
		
						// Add recombination and gateway sites - attB, attL, attP, LoxP
		
						// attB1
						attB_1_optn = document.createElement("OPTION");
						attB_1_optn.value = 'attB1';
						attB_1_optn.text = 'attB1';
						addElement(restrSiteSelect, restrSiteSelect.options.length-1, attB_1_optn);
		
						// attB2
						attB_2_optn = document.createElement("OPTION");
						attB_2_optn.value = 'attB2';
						attB_2_optn.text = 'attB2';
						addElement(restrSiteSelect, restrSiteSelect.options.length-1, attB_2_optn);
		
						// attL1
						attL_1_optn = document.createElement("OPTION");
						attL_1_optn.value = 'attL1';
						attL_1_optn.text = 'attL1';
						addElement(restrSiteSelect, restrSiteSelect.options.length-1, attL_1_optn);
		
						// attL2
						attL_2_optn = document.createElement("OPTION");
						attL_2_optn.value = 'attL2';
						attL_2_optn.text = 'attL2';
						addElement(restrSiteSelect, restrSiteSelect.options.length-1, attL_2_optn);
		
						// attP1
						attP_1_optn = document.createElement("OPTION");
						attP_1_optn.value = 'attP1';
						attP_1_optn.text = 'attP1';
						addElement(restrSiteSelect, restrSiteSelect.options.length-1, attP_1_optn);
		
						// attP2
						attP_2_optn = document.createElement("OPTION");
						attP_2_optn.value = 'attP2';
						attP_2_optn.text = 'attP2';
						addElement(restrSiteSelect, restrSiteSelect.options.length-1, attP_2_optn);
		
						// attR1
						attR_1_optn = document.createElement("OPTION");
						attR_1_optn.value = 'attR1';
						attR_1_optn.text = 'attR1';
						addElement(restrSiteSelect, restrSiteSelect.options.length-1, attR_1_optn);
		
						// attR2
						attR_2_optn = document.createElement("OPTION");
						attR_2_optn.value = 'attR2';
						attR_2_optn.text = 'attR2';
						addElement(restrSiteSelect, restrSiteSelect.options.length-1, attR_2_optn);
						
						// LoxP
						loxp_optn = document.createElement("OPTION");
						loxp_optn.value = 'LoxP';
						loxp_optn.text = 'LoxP';
						addElement(restrSiteSelect, restrSiteSelect.options.length-1, loxp_optn);
		
						// March 18/09: Topo
						topo_optn = document.createElement("OPTION");
						topo_optn.value = 'TOPO';
						topo_optn.text = 'TOPO';
						addElement(restrSiteSelect, restrSiteSelect.options.length-1, topo_optn);
		
						// Feb. 5/08: Add option "None"
						noneOpt = document.createElement("OPTION");
						noneOpt.text = 'None';
						noneOpt.value = 'None';
						addElement(restrSiteSelect, restrSiteSelect.options.length-1, noneOpt);
		
						// Finally, select the current cloning site
						var rsInList = false;
		
						for (j = 0; j < restrSiteSelect.options.length; j++)
						{
							aOptn = restrSiteSelect.options[j].value;
		
							// Oct. 28/08: Use the same strategy as with other feature lists - fetch the selected value from the field name
							if (selectedRestrictionSite)
							{
		// oct 28/08					if (selectedRestrictionSite.value == aOptn)
								if (selectedRestrictionSite.toLowerCase() == aOptn.toLowerCase())
								{
									restrSiteSelect.selectedIndex = j;
									rsInList = true;
								}
							}
						}
		// Removed Jan. 16/08 b/c was interfering with 3' Cloning Site list. Should not have 'other' restriction sites for now, see if problems arise. 
		// 				if (!rsInList)
		// 				{
		// 					// Enzyme not found (prob. hybrid); select 'Other' and show textbox
		// 
		// 					// add option 'Other' first - moved here Dec. 1/08
		// 					otherOpt = document.createElement("OPTION");
		// 					otherOpt.text = 'Other';
		// 					otherOpt.value = 'Other';
		// 					addElement(restrSiteSelect, restrSiteSelect.options.length-1, otherOpt);
		// 					otherIndex = restrSiteSelect.options.length-1;
		// 	
		// 					for (k = 0; k < restrSiteSelect.options.length; k++)
		// 					{
		// 						if (restrSiteSelect.options[k].value == 'Other')
		// 						{
		// 							restrSiteSelect.selectedIndex = k;
		// 
		// 							allText = document.getElementsByTagName("INPUT");
		// 
		// 							for (at in allText)
		// 							{
		// 								if (allText[at].name == "3_prime_cloning_site_name_txt")
		// 								{
		// 									allText[at].style.display = "inline";
		// 								}
		// 							}
		// 						}
		// 					}
		// 				}
					}
					
					// 3' site
					if ( (allSelects[a].id == "tpcs_list") || (allSelects[a].id == "tpcs_list_1") || (allSelects[a].id == "tpcs_list_2") || (allSelects[a].id == "tpcs_list_3"))
					{
						var threePrimeSelect = allSelects[a];
			
						for (i=0; i < enzList.length; i++)
						{
							var tmpEnz = trimAll(enzList[i]);
				
							enzOpt = document.createElement("OPTION");
							enzOpt.text = tmpEnz;
							enzOpt.value = tmpEnz;
		
							threePrimeSelect.options[i] = enzOpt;
						}
		
						// Add recombination and gateway sites - attB, attL, attP, LoxP
		
		// 				// attB1
		// 				attB_1_optn = document.createElement("OPTION");
		// 				attB_1_optn.value = 'attB1';
		// 				attB_1_optn.text = 'attB1';
		// 				addElement(threePrimeSelect, threePrimeSelect.options.length-1, attB_1_optn);
		
						// attB2
						attB_2_optn = document.createElement("OPTION");
						attB_2_optn.value = 'attB2';
						attB_2_optn.text = 'attB2';
						addElement(threePrimeSelect, threePrimeSelect.options.length-1, attB_2_optn);
		
		// 				// attL1
		// 				attL_1_optn = document.createElement("OPTION");
		// 				attL_1_optn.value = 'attL1';
		// 				attL_1_optn.text = 'attL1';
		// 				addElement(threePrimeSelect, threePrimeSelect.options.length-1, attL_1_optn);
		
						// attL2
						attL_2_optn = document.createElement("OPTION");
						attL_2_optn.value = 'attL2';
						attL_2_optn.text = 'attL2';
						addElement(threePrimeSelect, threePrimeSelect.options.length-1, attL_2_optn);
		
		// 				// attP1
		// 				attP_1_optn = document.createElement("OPTION");
		// 				attP_1_optn.value = 'attP1';
		// 				attP_1_optn.text = 'attP1';
		// 				addElement(threePrimeSelect, threePrimeSelect.options.length-1, attP_1_optn);
		
						// attP2
						attP_2_optn = document.createElement("OPTION");
						attP_2_optn.value = 'attP2';
						attP_2_optn.text = 'attP2';
						addElement(threePrimeSelect, threePrimeSelect.options.length-1, attP_2_optn);
		
		// 				// attR1
		// 				attR_1_optn = document.createElement("OPTION");
		// 				attR_1_optn.value = 'attR1';
		// 				attR_1_optn.text = 'attR1';
		// 				addElement(threePrimeSelect, threePrimeSelect.options.length-1, attR_1_optn);
		
						// attR2
						attR_2_optn = document.createElement("OPTION");
						attR_2_optn.value = 'attR2';
						attR_2_optn.text = 'attR2';
						addElement(threePrimeSelect, threePrimeSelect.options.length-1, attR_2_optn);
						
						// LoxP
						loxp_optn = document.createElement("OPTION");
						loxp_optn.value = 'LoxP';
						loxp_optn.text = 'LoxP';
						addElement(threePrimeSelect, threePrimeSelect.options.length-1, loxp_optn);
		
						// Feb. 5/08: Add option "None"
						noneOpt = document.createElement("OPTION");
						noneOpt.text = 'None';
						noneOpt.value = 'None';
						addElement(threePrimeSelect, threePrimeSelect.options.length-1, noneOpt);
		
						// Finally, select the current cloning site
						var tpInList = false;
		
						for (j = 0; j < threePrimeSelect.options.length; j++)
						{
							aOptn = threePrimeSelect.options[j].value;
		
							// Updated Nov. 19/08
							if (selectedThreePrime_Insert && threePrimeSelect.id == "tpcs_list")
							{
								if (selectedThreePrime_Insert.value == aOptn)
								{
									tpInList = true;
									threePrimeSelect.selectedIndex = j;
								}
							}
		
							else if (selectedThreePrime)
							{
								if (selectedThreePrime.value == aOptn)
								{
									threePrimeSelect.selectedIndex = j;
									tpInList = true;
								}
							}
						}
		
						if (!tpInList)
						{
							// Enzyme not found (prob. hybrid); select 'Other' and show textbox
			
							// add option 'Other' first - Dec. 1/08
							otherOpt = document.createElement("OPTION");
							otherOpt.text = 'Other';
							otherOpt.value = 'Other';
							addElement(threePrimeSelect, threePrimeSelect.options.length-1, otherOpt);
							otherIndex = threePrimeSelect.options.length-1;
		
							for (k = 0; k < threePrimeSelect.options.length; k++)
							{
								if (threePrimeSelect.options[k].value.toLowerCase() == 'other')
								{
									threePrimeSelect.selectedIndex = k;
		
									allText = document.getElementsByTagName("INPUT");
		
									for (at in allText)
									{
										if (allText[at].name == "3_prime_cloning_site_name_txt")
										{
											allText[at].style.display = "inline";
										}
									}
								}
							}
						}
					}
				}	// end if (allSelects[a].id)
			}	// end if (allSelects[a])
		}
	}
}


function cancelVectorModification(scriptPath)
{
	document.getElementById("change_state_id").value = "Cancel";
	document.vectorDetailForm.submit();
}

function modifyVector(scriptPath)
{
	document.getElementById("change_state_id").value = "Modify";
	document.vectorDetailForm.submit();
}

// On Save, if restriction sites were modified, verify that they could be used to construct sequence
// Updated April 18/08: added vType parameter
function verifySequenceAndRestrictionSites(scriptPath, vType)
{
	document.getElementById("saveBtn").style.cursor = 'wait';

	var fpList = document.getElementById("fpcs_list_" + vType);
	var tpList = document.getElementById("tpcs_list_" + vType);

	var fpSelInd = fpList.selectedIndex;
	var tpSelInd = tpList.selectedIndex;

	var fpcs = fpList[fpSelInd].value;
	var tpcs = tpList[tpSelInd].value;

	var fpcs_orig = document.getElementById("fpcs_original").value;
	var tpcs_orig = document.getElementById("tpcs_original").value;

	var params = "";

	var vType = document.getElementById("vector_cloning_method").value;

	var parentVectorField = document.getElementById("parent_vector_id_txt");
	var srcPV = parentVectorField.value;

	var uName = document.getElementById("curr_uname").value;

	// feb 5/08: hide name warning
	if (document.getElementById(vType + "_name_warning"))
		document.getElementById(vType + "_name_warning").style.display = "none";

// 	document.getElementById("oc_warning").style.display = "none";
// 	document.getElementById("it_warning").style.display = "none";

// 	document.getElementById("change_state_id").value = "Save";

//	if ((fpcs != fpcs_orig) || (tpcs != tpcs_orig))
//	{
		url = scriptPath + "vector_sequence.py";
		xmlhttp1 = createXML();
		xmlhttp1.open("POST", url, false);
		xmlhttp1.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	
		switch (vType)
		{
			case '1':
				srcInsert = document.getElementById("insert_id_txt").value;
				seqParams = "PV=" + srcPV + "&I=" + srcInsert + "&fpcs=" + fpcs + "&tpcs=" + tpcs + "&curr_username=" + uName + "&cloning_method=" + vType + "&change_state=Save";

				xmlhttp1.send(seqParams);
				xmlhttp1.onreadystatechange = preloadSequence(xmlhttp1, scriptPath);
			break;

			case '2':
				srcIPV = document.getElementById("ipv_id_txt").value;
				seqParams = "PV=" + srcPV + "&IPV=" + srcIPV + "&fpcs=" + fpcs + "&tpcs=" + tpcs + "&curr_username=" + uName + "&cloning_method=" + vType + "&change_state=Save";
		
				xmlhttp1.send(seqParams);
				xmlhttp1.onreadystatechange = preloadSequence(xmlhttp1, scriptPath);	
			break;

			default:
			break;
		}
//	}

	document.getElementById("saveBtn").style.cursor = 'auto';
}


// Preload properties when parent values are changed
function previewVector(scriptPath)
{
	document.getElementById("changeParentsBtn").style.cursor = 'wait';

	var vType = document.getElementById("vector_cloning_method").value;
	
	if (verifyParentFormat(vType))
	{
	
		var fpList = document.getElementById("fpcs_list");
		var tpList = document.getElementById("tpcs_list");

		var fpSelInd = fpList.selectedIndex;
		var tpSelInd = tpList.selectedIndex;

		var fpcs = fpList[fpSelInd].value;
		var tpcs = tpList[tpSelInd].value;

		var uName = document.getElementById("curr_uname").value;

		var params = "";
		
		var srcPV, srcInsert, srcIPV;

		changeParentValues(vType);

		// Sequence
		// First, check if parents are filled in
		var parentVectorField = document.getElementById("parent_vector_id_txt");
		var parentVector = parentVectorField.value;

		srcPV = document.getElementById("parent_vector_id_txt").value;

		// remember PV
		document.getElementById("parent_vector_old_id").value = parentVector;

		var seqField = document.getElementById("dna_sequence");

		if (parentVector == "")
		{
			var proceed = confirm("Your Parent Vector value is empty.  Are you sure you wish to proceed?");

			if (proceed)
			{
				seqField.value = "";
				return true;
			}
			else
			{
				parentVectorField.focus();
			}
		}
		else
		{
			// Sequence
			url = scriptPath + "vector_sequence.py";
			xmlhttp1 = createXML();
			xmlhttp1.open("POST", url, false);
			xmlhttp1.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		
			switch (vType)
			{
				case '1':
				
					// Non-recombination vector
					srcInsert = document.getElementById("insert_id_txt").value;
		
					// Insert empty??
					if (srcInsert == "")
					{
						var proceed = confirm("Your Insert value is empty.  Are you sure you wish to proceed?");

						if (proceed)
						{
							document.getElementById("dna_sequence").value = "";
							return true;
						}
						else
						{
							// different parameters for different properties
							seqParams = "PV=" + srcPV + "&I=" + srcInsert + "&fpcs=" + fpcs + "&tpcs=" + tpcs + "&curr_username=" + uName + "&cloning_method=" + vType + "&change_state=Save";
							
							xmlhttp1.send(seqParams);
							xmlhttp1.onreadystatechange = preloadSequence(xmlhttp1, scriptPath);
						}
					}
					else
					{
						// different parameters for different properties
						seqParams = "PV=" + srcPV + "&I=" + srcInsert + "&fpcs=" + fpcs + "&tpcs=" + tpcs + "&curr_username=" + uName + "&cloning_method=" + vType + "&change_state=Save";
						
						xmlhttp1.send(seqParams);
						xmlhttp1.onreadystatechange = preloadSequence(xmlhttp1, scriptPath);
					}
				break;
		
				case '2':
					
					// Recombination vector
					srcIPV = document.getElementById("ipv_id_txt").value;
		
					if (srcIPV == "")
					{
						var proceed = confirm("Your Insert value is empty.  Are you sure you wish to proceed?");

						if (proceed)
						{
							document.getElementById("dna_sequence").value = "";
							return true;
						}
						else
						{
							// different parameters for different properties
							seqParams = "PV=" + srcPV + "&IPV=" + srcIPV + "&fpcs=" + fpcs + "&tpcs=" + tpcs + "&curr_username=" + uName + "&cloning_method=" + vType + "&change_state=Save";
					
							xmlhttp1.send(seqParams);
							xmlhttp1.onreadystatechange = preloadSequence(xmlhttp1, scriptPath);
						}
					}
					else
					{
						// different parameters for different properties
						seqParams = "PV=" + srcPV + "&IPV=" + srcIPV + "&fpcs=" + fpcs + "&tpcs=" + tpcs + "&curr_username=" + uName + "&cloning_method=" + vType + "&change_state=Save";
				
						xmlhttp1.send(seqParams);
						xmlhttp1.onreadystatechange = preloadSequence(xmlhttp1, scriptPath);
					}
				break;
			}
		}

		// Cloning sites - Keep latest values
		// 	fpList.options[fpSelInd].selected = true;
		// 	tpList.options[tpSelInd].selected = true;

	}
	
	document.getElementById("changeParentsBtn").style.cursor = 'auto';
}

function preloadSequence(xmlhttp, scriptPath)
{
//	alert("Loading sequence");
//	alert(xmlhttp.readyState);

	var fpList = document.getElementById("fpcs_list");
	var tpList = document.getElementById("tpcs_list");

	if (xmlhttp.readyState == 4)
	{
//		alert(xmlhttp.status);
//		alert(xmlhttp.responseText);
	
		if (xmlhttp.status == 200)
		{
			response = trimAll(xmlhttp.responseText);	// tab-separated
//			alert(response);
			if (response == 1)
			{
				alert("Unable to generate sequence: Unknown sites on Insert. Please verify your input values before saving.")

				// reset parent values
				document.getElementById("parent_vector_id_txt").value = document.getElementById("pv_original").value;
				document.getElementById("parent_vector_id_txt").style.color = "#FF0000";

				if (document.getElementById("insert_id_txt"))
				{
					document.getElementById("insert_id_txt").style.color = "#FF0000";
					document.getElementById("insert_id_txt").value = document.getElementById("insert_original").value;
				}

				else if (document.getElementById("ipv_id_txt"))
				{
					document.getElementById("ipv_id_txt").style.color = "#FF0000";
					document.getElementById("ipv_id_txt").value = document.getElementById("ipv_original").value;
				}

				fpcs_orig = document.getElementById("fpcs_original").value;
				tpcs_orig = document.getElementById("tpcs_original").value;
			
				fpList.style.color = "#FF0000";
				tpList.style.color = "#FF0000";

				matchListOption(fpList.id, fpcs_orig);
				matchListOption(tpList.id, tpcs_orig);

				// reset sequence
				seq_orig = document.getElementById("seq_original").value;
				seq_curr = document.getElementById("dna_sequence").value;

				if (filterSpace(seq_curr) != seq_orig)
				{
					document.getElementById("dna_sequence").value = seq_orig;
				}
			}
			else if (response == 2)
			{
				alert("Unable to generate sequence: Restriction sites could not be found on parent vector sequence. Please verify your input values before saving.")

				// reset parent values
				document.getElementById("parent_vector_id_txt").value = document.getElementById("pv_original").value;
				document.getElementById("parent_vector_id_txt").style.color = "#FF0000";

				if (document.getElementById("insert_id_txt"))
				{
					document.getElementById("insert_id_txt").style.color = "#FF0000";
					document.getElementById("insert_id_txt").value = document.getElementById("insert_original").value;
				}

				else if (document.getElementById("ipv_id_txt"))
				{
					document.getElementById("ipv_id_txt").style.color = "#FF0000";
					document.getElementById("ipv_id_txt").value = document.getElementById("ipv_original").value;
				}

				fpcs_orig = document.getElementById("fpcs_original").value;
				tpcs_orig = document.getElementById("tpcs_original").value;
			
				fpList.style.color = "#FF0000";
				tpList.style.color = "#FF0000";
			
				matchListOption(fpList.id, fpcs_orig);
				matchListOption(tpList.id, tpcs_orig);

				// reset sequence
				seq_orig = document.getElementById("seq_original").value;
				seq_curr = document.getElementById("dna_sequence").value;

				if (filterSpace(seq_curr) != seq_orig)
				{
					document.getElementById("dna_sequence").value = seq_orig;
				}
			}
			else if (response == 3)
			{
				alert("Unable to generate sequence: Restriction sites occur more than once on parent vector sequence. Please verify your input values before saving.")

				// reset parent values
				document.getElementById("parent_vector_id_txt").value = document.getElementById("pv_original").value;

				document.getElementById("parent_vector_id_txt").style.color = "#FF0000";

				if (document.getElementById("insert_id_txt"))
				{
					document.getElementById("insert_id_txt").style.color = "#FF0000";
					document.getElementById("insert_id_txt").value = document.getElementById("insert_original").value;
				}

				else if (document.getElementById("ipv_id_txt"))
				{
					document.getElementById("ipv_id_txt").style.color = "#FF0000";
					document.getElementById("ipv_id_txt").value = document.getElementById("ipv_original").value;
				}

				fpcs_orig = document.getElementById("fpcs_original").value;
				tpcs_orig = document.getElementById("tpcs_original").value;
			
				fpList.style.color = "#FF0000";
				tpList.style.color = "#FF0000";
			
				matchListOption(fpList.id, fpcs_orig);
				matchListOption(tpList.id, tpcs_orig);
			
				// reset sequence
				seq_orig = document.getElementById("seq_original").value;
				seq_curr = document.getElementById("dna_sequence").value;

				if (filterSpace(seq_curr) != seq_orig)
				{
					document.getElementById("dna_sequence").value = seq_orig;
				}
			}
			else if (response == 4)
			{
				alert("Unable to generate sequence: Restriction sites cannot be hybridized. Please verify your input values before saving.")

				// reset parent values
				document.getElementById("parent_vector_id_txt").value = document.getElementById("pv_original").value;
				document.getElementById("parent_vector_id_txt").style.color = "#FF0000";

				if (document.getElementById("insert_id_txt"))
				{
					document.getElementById("insert_id_txt").style.color = "#FF0000";
					document.getElementById("insert_id_txt").value = document.getElementById("insert_original").value;
				}

				else if (document.getElementById("ipv_id_txt"))
				{
					document.getElementById("ipv_id_txt").style.color = "#FF0000";
					document.getElementById("ipv_id_txt").value = document.getElementById("ipv_original").value;
				}

				fpcs_orig = document.getElementById("fpcs_original").value;
				tpcs_orig = document.getElementById("tpcs_original").value;
			
				fpList.style.color = "#FF0000";
				tpList.style.color = "#FF0000";
			
				matchListOption(fpList.id, fpcs_orig);
				matchListOption(tpList.id, tpcs_orig);
			
				// reset sequence
				seq_orig = document.getElementById("seq_original").value;
				seq_curr = document.getElementById("dna_sequence").value;

				if (filterSpace(seq_curr) != seq_orig)
				{
					document.getElementById("dna_sequence").value = seq_orig;
				}
			}
			else if (response == 5)
			{
				alert("Unable to generate sequence: 5' site occurs after 3' site on parent vector sequence. Please verify your input values before saving.");

				// reset parent values
				document.getElementById("parent_vector_id_txt").value = document.getElementById("pv_original").value;
				document.getElementById("parent_vector_id_txt").style.color = "#FF0000";

				if (document.getElementById("insert_id_txt"))
				{
					document.getElementById("insert_id_txt").style.color = "#FF0000";
					document.getElementById("insert_id_txt").value = document.getElementById("insert_original").value;
				}

				else if (document.getElementById("ipv_id_txt"))
				{
					document.getElementById("ipv_id_txt").style.color = "#FF0000";
					document.getElementById("ipv_id_txt").value = document.getElementById("ipv_original").value;
				}

				fpcs_orig = document.getElementById("fpcs_original").value;
				tpcs_orig = document.getElementById("tpcs_original").value;
			
				fpList.style.color = "#FF0000";
				tpList.style.color = "#FF0000";
			
				matchListOption(fpList.id, fpcs_orig);
				matchListOption(tpList.id, tpcs_orig);
				
				// reset sequence
				seq_orig = document.getElementById("seq_original").value;
				seq_curr = document.getElementById("dna_sequence").value;

				if (filterSpace(seq_curr) != seq_orig)
				{
					document.getElementById("dna_sequence").value = seq_orig;
				}
			}
			else if (response == 5)
			{
				alert("Unknown parent values.  Please verify your input values before saving.")

				// reset parent values
				document.getElementById("parent_vector_id_txt").value = document.getElementById("pv_original").value;
				document.getElementById("parent_vector_id_txt").style.color = "#FF0000";

				if (document.getElementById("insert_id_txt"))
				{
					document.getElementById("insert_id_txt").style.color = "#FF0000";
					document.getElementById("insert_id_txt").value = document.getElementById("insert_original").value;
				}

				else if (document.getElementById("ipv_id_txt"))
				{
					document.getElementById("ipv_id_txt").style.color = "#FF0000";
					document.getElementById("ipv_id_txt").value = document.getElementById("ipv_original").value;
				}

				fpcs_orig = document.getElementById("fpcs_original").value;
				tpcs_orig = document.getElementById("tpcs_original").value;
			
				fpList.style.color = "#FF0000";
				tpList.style.color = "#FF0000";
			
				matchListOption(fpList.id, fpcs_orig);
				matchListOption(tpList.id, tpcs_orig);
				
				// reset sequence
				seq_orig = document.getElementById("seq_original").value;
				seq_curr = document.getElementById("dna_sequence").value;

				if (filterSpace(seq_curr) != seq_orig)
				{
					document.getElementById("dna_sequence").value = seq_orig;
				}
			}
			else if (response == 10)
			{
				alert("You are not authorized to use this Parent Vector, since you do not have at least Read access to its project. Please verify your input values before saving.")

				// reset parent values
				document.getElementById("parent_vector_id_txt").value = document.getElementById("pv_original").value;
				document.getElementById("parent_vector_id_txt").style.color = "#FF0000";
				document.getElementById("parent_vector_id_txt").focus();

				fpcs_orig = document.getElementById("fpcs_original").value;
				tpcs_orig = document.getElementById("tpcs_original").value;
			
				fpList.style.color = "#FF0000";
				tpList.style.color = "#FF0000";
			
				matchListOption(fpList.id, fpcs_orig);
				matchListOption(tpList.id, tpcs_orig);
				
				// reset sequence
				seq_orig = document.getElementById("seq_original").value;
				seq_curr = document.getElementById("dna_sequence").value;

				if (filterSpace(seq_curr) != seq_orig)
				{
					document.getElementById("dna_sequence").value = seq_orig;
				}
			}
			else if (response == 11)
			{
				alert("You are not authorized to use this Insert Parent Vector, since you do not have at least Read access to its project. Please verify your input values before saving.");

				// reset parent values
				document.getElementById("ipv_id_txt").value = document.getElementById("ipv_original").value;
				document.getElementById("ipv_id_txt").style.color = "#FF0000";
				document.getElementById("ipv_id_txt").focus();

				fpcs_orig = document.getElementById("fpcs_original").value;
				tpcs_orig = document.getElementById("tpcs_original").value;
			
				fpList.style.color = "#FF0000";
				tpList.style.color = "#FF0000";
			
				matchListOption(fpList.id, fpcs_orig);
				matchListOption(tpList.id, tpcs_orig);
				
				// reset sequence
				seq_orig = document.getElementById("seq_original").value;
				seq_curr = document.getElementById("dna_sequence").value;

				if (filterSpace(seq_curr) != seq_orig)
				{
					document.getElementById("dna_sequence").value = seq_orig;
				}
			}
			else if (response == 12)
			{
				alert("Unable to generate sequence: Unknown Parent Vector ID. Please verify your input values before saving.");

				// reset parent values
				document.getElementById("parent_vector_id_txt").value = document.getElementById("pv_original").value;
				document.getElementById("parent_vector_id_txt").style.color = "#FF0000";
				document.getElementById("parent_vector_id_txt").focus();

				fpcs_orig = document.getElementById("fpcs_original").value;
				tpcs_orig = document.getElementById("tpcs_original").value;
			
				fpList.style.color = "#FF0000";
				tpList.style.color = "#FF0000";
			
				matchListOption(fpList.id, fpcs_orig);
				matchListOption(tpList.id, tpcs_orig);
				
				// reset sequence
				seq_orig = document.getElementById("seq_original").value;
				seq_curr = document.getElementById("dna_sequence").value;

				if (filterSpace(seq_curr) != seq_orig)
				{
					document.getElementById("dna_sequence").value = seq_orig;
				}
			}
			else if (response == 16)
			{
				alert("Unable to generate sequence: Unknown Insert ID. Please verify your input values before saving.");

				document.getElementById("insert_id_txt").value = document.getElementById("insert_old_id").value;
				document.getElementById("insert_id_txt").style.color = "#FF0000";
				document.getElementById("insert_id_txt").focus();

				fpcs_orig = document.getElementById("fpcs_original").value;
				tpcs_orig = document.getElementById("tpcs_original").value;
			
				fpList.style.color = "#FF0000";
				tpList.style.color = "#FF0000";
			
				matchListOption(fpList.id, fpcs_orig);
				matchListOption(tpList.id, tpcs_orig);
				
				// reset sequence
				seq_orig = document.getElementById("seq_original").value;
				seq_curr = document.getElementById("dna_sequence").value;

				if (filterSpace(seq_curr) != seq_orig)
				{
					document.getElementById("dna_sequence").value = seq_orig;
				}
			}
			else if (response == 17)
			{
				alert("Unable to generate sequence: Unknown Parent Insert Vector ID. Please verify your input values before saving.");

				document.getElementById("ipv_id_txt").style.color = "#FF0000";
				document.getElementById("ipv_id_txt").value = document.getElementById("ipv_original").value;
				document.getElementById("ipv_id_txt").focus();

				fpcs_orig = document.getElementById("fpcs_original").value;
				tpcs_orig = document.getElementById("tpcs_original").value;
			
				fpList.style.color = "#FF0000";
				tpList.style.color = "#FF0000";
			
				matchListOption(fpList.id, fpcs_orig);
				matchListOption(tpList.id, tpcs_orig);

				// reset sequence
				seq_orig = document.getElementById("seq_original").value;
				seq_curr = document.getElementById("dna_sequence").value;

				if (filterSpace(seq_curr) != seq_orig)
				{
					document.getElementById("dna_sequence").value = seq_orig;
				}
			}
			else if (response == 18)
			{
				alert("Unable to generate sequence: One or both parent sequences are invalid. Please verify your input values before saving.");

				document.getElementById("parent_vector_id_txt").value = document.getElementById("pv_original").value;
				document.getElementById("parent_vector_id_txt").style.color = "#FF0000";

				if (document.getElementById("insert_id_txt"))
				{
					document.getElementById("insert_id_txt").style.color = "#FF0000";
					document.getElementById("insert_id_txt").value = document.getElementById("insert_original").value;
				}

				else if (document.getElementById("ipv_id_txt"))
				{
					document.getElementById("ipv_id_txt").style.color = "#FF0000";
					document.getElementById("ipv_id_txt").value = document.getElementById("ipv_original").value;
				}

				fpcs_orig = document.getElementById("fpcs_original").value;
				tpcs_orig = document.getElementById("tpcs_original").value;
			
				fpList.style.color = "#FF0000";
				tpList.style.color = "#FF0000";
			
				matchListOption(fpList.id, fpcs_orig);
				matchListOption(tpList.id, tpcs_orig);

				// reset sequence
				seq_orig = document.getElementById("seq_original").value;
				seq_curr = document.getElementById("dna_sequence").value;

				if (filterSpace(seq_curr) != seq_orig)
				{
					document.getElementById("dna_sequence").value = seq_orig;
				}
			}
			else
			{
				// Status OK - show new sequence, REGARDLESS of whether it's identical to original
				document.getElementById("dna_sequence").value = response;

//				// NOW preload simple properties from PV IFF in preview mode
//				srcPV = document.getElementById("parent_vector_id_txt").value;
//
//				// prepare xmlhttprequest
//				url = scriptPath + "preview.py";
//				xmlhttp = createXML();
//				xmlhttp.open("POST", url, false);
//				xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
//			
				if (document.pressed == 'Change')
				{
					// NOW preload simple properties from PV -- IFF in preview mode
					url = scriptPath + "preview.py";
					xmlhttp = createXML();
				
					// Colour properties blue:

					// Sequence
					document.getElementById("dna_sequence").style.color = "#0000FF";

					// Restriction sites
					fpList.style.color = "#0000FF";
					tpList.style.color = "#0000FF";

					// PV
					srcPV = document.getElementById("parent_vector_id_txt").value;
					document.getElementById("parent_vector_id_txt").style.color = "#0000FF";
					
					// Insert or IPV
					if (document.getElementById("insert_id_txt"))
					{
						document.getElementById("insert_id_txt").style.color = "#0000FF";
					}
					else if (document.getElementById("ipv_id_txt"))
					{
						document.getElementById("ipv_id_txt").style.color = "#0000FF";
					}

					// Name
					document.getElementById("reagent_name_prop").style.color = "#0000FF";
				
					// Vector Type
					document.getElementById("vector_type_list").style.color = "#0000FF";

					// Preload Tag Type
					xmlhttp.open("POST", url, false);
					xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
					xmlhttp.send("PV=" + srcPV + "&propAlias=tag");
					xmlhttp.onreadystatechange = preloadTagType(xmlhttp);
					
					// Tag Position
					xmlhttp.open("POST", url, false);
					xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
					xmlhttp.send("PV=" + srcPV + "&propAlias=tag_position");
					xmlhttp.onreadystatechange = preloadTagPosition(xmlhttp);
					
					// Expression System
					xmlhttp.open("POST", url, false);
					xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
					xmlhttp.send("PV=" + srcPV + "&propAlias=expression_system");
					xmlhttp.onreadystatechange = preloadExpressionSystem(xmlhttp);
				
					// Promoter
					xmlhttp.open("POST", url, false);
					xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
					xmlhttp.send("PV=" + srcPV + "&propAlias=promoter");
					xmlhttp.onreadystatechange = preloadPromoter(xmlhttp);
				}
				else if ( (document.pressed == 'Save') || (document.pressed == 'Cancel') )
				{
					xmlhttp.open("POST", url, false);
					xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
					document.vectorDetailForm.submit();
				}
			}
		}
	}
}

function preloadTagType(xmlhttp)
{
// 	alert("Tag type");
	if (xmlhttp.readyState == 4)
	{
//		alert(xmlhttp.status);
		if (xmlhttp.status == 200)
		{
			response = trimAll(xmlhttp.responseText);	// tab-separated, MUST BE TRIMMED (has extraneous carriage return)
// 			alert(response);
	
			if (document.pressed == 'Change')
				document.getElementById("tag_list").style.color = "#0000FF";
			
			matchListOption("tag_list", response);
		}
	}
}

function preloadTagPosition(xmlhttp)
{
// 	alert("Tag position");
	if (xmlhttp.readyState == 4)
	{
		if (xmlhttp.status == 200)
		{
			response = trimAll(xmlhttp.responseText);	// tab-separated
// 			alert(response);

			if (document.pressed == 'Change')
				document.getElementById("tag_position_list").style.color = "#0000FF";
	
			matchListOption("tag_position_list", response);
		}
	}
}


function preloadExpressionSystem(xmlhttp)
{
// 	alert("Exp sys");
	if (xmlhttp.readyState == 4)
	{
// 		alert(xmlhttp.status);
// 		alert(xmlhttp.responseText);
	
		if (xmlhttp.status == 200)
		{
			response = trimAll(xmlhttp.responseText);	// tab-separated
// 			alert(response);
			
			if (document.pressed == 'Change')
				document.getElementById("expr_syst_list").style.color = "#0000FF";
			
			matchListOption("expr_syst_list", response);
		}
	}
}


function preloadPromoter(xmlhttp)
{
// 	alert("Promoter");
	if (xmlhttp.readyState == 4)
	{
		if (xmlhttp.status == 200)
		{
			response = trimAll(xmlhttp.responseText);	// tab-separated
// 			alert(response);

			if (document.pressed == 'Change')
				document.getElementById("promoter_list").style.color = "#0000FF";
	
			matchListOption("promoter_list", response);
		}
	}
}


// Nov. 15/07: In a dropdown list, show the option whose value corresponds to 'optionValue'
function matchListOption(listName, optionValue)
{
	var myList = document.getElementById(listName);

	for (i = 0; i < myList.options.length; i++)
	{
		tmpOpt = myList.options[i];
	
		if (trimAll(tmpOpt.value.toLowerCase()) == trimAll(optionValue.toLowerCase()))
		{
			tmpOpt.selected = true;
			break;
		}
	}
}


// Jan. 23/08: Assign argument values to hidden form input fields
function addToOrder(reagentID, isolateNumber, containerID, row, col)
{
	document.getElementById("order_rid").value = reagentID;
	document.getElementById("order_iso_num").value = isolateNumber;
	document.getElementById("order_cont_id").value = containerID;
	document.getElementById("order_row").value = row;
	document.getElementById("order_col").value = col;

	document.getElementById("order_placed").value = "1";
	var currMode = document.getElementById("location_output_mode").value;
	
	document.viewLocation.action = "Location.php?View=1&rid=" + reagentID + "&mode=" + currMode;
	document.viewLocation.submit();
}

function changeFormAction(form_name, new_action)
{
	docForms = document.forms;

	for (i = 0; i < docForms.length; i++)
	{
		aForm = docForms[i];

		if (aForm.name == form_name)
		{
			aForm.action = new_action;
			aForm.submit();
		}
	}
}


// Feb. 19/08: Move to DNA sequence tab on Reagent Detailed View
function showDNASequence()
{
	var dnaSeqTab = document.getElementById("dnaSequenceTab");
	var viewDnaTab = document.getElementById("viewDnaTab");

	dnaSeqTab.style.display = "inline";

	// Make tab active
	viewDnaTab.style.paddingBottom = "9px";
	viewDnaTab.style.backgroundColor = "#FFF8DC";
	viewDnaTab.style.color = "#00008B";

	// hide all other sequence view tabs (options: protein or map - coming soon)
	var protSeqTab = document.getElementById("proteinSequenceTab");
	var viewProtTab = document.getElementById("viewProteinTab");

	if (protSeqTab && (protSeqTab.style.display != "none"))
	{
		protSeqTab.style.display = "none";
		
		// make protein tab active
		viewProtTab.className = "tabLinkInactive";

		viewProtTab.style.paddingBottom = "6px";
		viewProtTab.style.backgroundColor = "#FFF8DC";
		viewProtTab.style.color = "#36648B";

		// Enable buttons
		// Edit btn - Hard-code: only DNA and RNA can have protein translation tabs
		if (document.getElementById("edit_reagent_sequence_properties") && document.getElementById("save_reagent_sequence_properties") && document.getElementById("cancel_save_sequence_properties"))
		{
			var editBtn = document.getElementById("edit_reagent_sequence_properties");
			var saveBtn = document.getElementById("save_reagent_sequence_properties");
			var cancelBtn = document.getElementById("cancel_save_sequence_properties");
		}
		else if (document.getElementById("edit_reagent_rna_sequence") && document.getElementById("save_reagent_rna_sequence") && document.getElementById("cancel_save_rna_sequence"))
		{
			var editBtn = document.getElementById("edit_reagent_rna_sequence");
			var saveBtn = document.getElementById("save_reagent_rna_sequence");
			var cancelBtn = document.getElementById("cancel_save_rna_sequence");
		}

		if (editBtn.style.display != "none")
			editBtn.disabled = false;

		if (saveBtn.style.display != "none")
			saveBtn.disabled = false;

		if (cancelBtn.style.display != "none")
			cancelBtn.disabled = false;
	}
}

// Feb. 19/08: Move to DNA sequence tab on Reagent Detailed View
function showProteinSequence(reagentType)
{
	var protSeqTab = document.getElementById("proteinSequenceTab");
	var viewProtTab = document.getElementById("viewProteinTab");
	
	protSeqTab.style.display = "inline";

	viewProtTab.style.paddingBottom = "9px";
	viewProtTab.style.backgroundColor = "#FFF8DC";
	viewProtTab.style.color = "#00008B";

	// hide all other sequence view tabs (options: DNA or map - coming soon)
	var dnaSeqTab = document.getElementById("dnaSequenceTab");
	var viewDnaTab = document.getElementById("viewDnaTab");

	if (dnaSeqTab && (dnaSeqTab.style.display != "none"))
	{
		dnaSeqTab.style.display = "none";

		viewDnaTab.style.paddingBottom = "6px";
		viewDnaTab.style.backgroundColor = "#FFF8DC";
		viewDnaTab.style.color = "#36648B";

		// Disable buttons!!

		// Hard-code: only DNA and RNA can have protein translation tabs
		if (document.getElementById("edit_reagent_sequence_properties") && document.getElementById("save_reagent_sequence_properties") && document.getElementById("cancel_save_sequence_properties"))
		{
			var editBtn = document.getElementById("edit_reagent_sequence_properties");
			var saveBtn = document.getElementById("save_reagent_sequence_properties");
			var cancelBtn = document.getElementById("cancel_save_sequence_properties");
		}
		else if (document.getElementById("edit_reagent_rna_sequence") && document.getElementById("save_reagent_rna_sequence") && document.getElementById("cancel_save_rna_sequence"))
		{
			var editBtn = document.getElementById("edit_reagent_rna_sequence");
			var saveBtn = document.getElementById("save_reagent_rna_sequence");
			var cancelBtn = document.getElementById("cancel_save_rna_sequence");
		}

		if (editBtn.style.display != "none")
			editBtn.disabled = true;

		if (saveBtn.style.display != "none")
			saveBtn.disabled = true;

		if (cancelBtn.style.display != "none")
			cancelBtn.disabled = true;
	}
}

// Taken from http://javascript.about.com/library/bladdslash.htm
// Nov. 4/08
function stripslashes(str)
{
	str=str.replace(/\\'/g,'\'');
	str=str.replace(/\\"/g,'"');
	str=str.replace(/\\\\/g,'\\');
	str=str.replace(/\\0/g,'\0');
	return str;
}

// Taken from http://javascript.about.com/library/bladdslash.htm
// Nov. 4/08
function addslashes(str)
{
	str=str.replace(/\'/g,'\\\'');
	str=str.replace(/\"/g,'\\"');
	str=str.replace(/\\/g,'\\\\');
	str=str.replace(/\0/g,'\\0');
	return str;
}

function deleteTableRow(tableID, rowID)
{
// alert(tableID);
	var aTable = document.getElementById(tableID);
// alert(rowID);

	// updated Oct. 27/08
// 	var tmpRow = document.getElementById(rowID);
// 	var tmpRow = document.getElementById(rowID.replace("\'", "'"));	// miscellaneous 5' and 3' PCMV LTR values

// alert(aTable.rows.length);
	// change Feb. 12/10
	for (i=0; i < aTable.rows.length; i++)
	{
		tmpRow = aTable.rows[i];

		if (tmpRow.id == rowID)
		{
			var rIndex = tmpRow.rowIndex;
// alert(rIndex);
			aTable.deleteRow(rIndex);
			return;
		}
	}

// 	var tmpRow = document.getElementById(stripslashes(rowID));
// // alert(tmpRow);
// 	if (tmpRow)
// 	{
// 		var rIndex = tmpRow.rowIndex;
// // 		alert(rIndex);
// 
// // if (aTable.rows.length > 1)
// // 		aTable.deleteRow(rIndex-1);
// // else
// 		aTable.deleteRow(rIndex);
// 	}
}


function getBounds(mode, rID)
{
	var start = -1;
	var stop = -1;

	var constProtSeq = document.getElementById("const_protSeq_" + rID).value;
	var constDNASeq = document.getElementById("const_dnaSeq_" + rID).value;

	var protStart = document.getElementById("aa_start_txt_" + rID);
	var protStop = document.getElementById("aa_end_txt_" + rID);

	// Updated May 30/08
	var cdnaStart = parseInt(document.getElementById("cdna_start").value);
	var cdnaStop = parseInt(document.getElementById("cdna_stop").value);

	// Sept. 11/08
	var translStart = parseInt(document.getElementById("prot_start").value);
	var translStop = parseInt(document.getElementById("prot_stop").value);

	var protOut = document.getElementById("sel_prot_div_" + rID);
	var dnaOut = document.getElementById("sel_dna_div_" + rID);

	var submitBtn = document.getElementById("get_primers_btn");	// added Sept 14/06
	var linkersTbl = document.getElementById("linkers_tbl");	// Sept 8/06

	var protSeqOut = '';
	var dnaSeqOut = '';

	//var indexWarning = document.getElementById("index_warning");	// different error-handling method, check with Karen what her preference is

	if (mode == 'protein')
	{
		start = protStart.value;
		stop = protStop.value;

		// Check that start/stop values are non-empty
		if (start.length == 0)
		{
			alert("Please provide a protein start value");
			protStart.focus();

			return false;
		}

		if (stop.length == 0)
		{
			alert("Please provide a protein stop value");
			protStop.focus();

			return false;
		}

		// First, check if the start/stop values entered are integers
		// Since parseInt could still return an int even if the value contained alpha chars but began with a digit (it would simply discard the rest), adding my own validation code:

		// check start
		for (i = 0; i < start.length; i++)
		{
			var c = start.charAt(i);

			if (c < "0" || c > "9")
			{
				alert("Protein start value must be a non-negative integer; please verify your input");

				protStop.style.color = "000000";
				protStop.style.fontWeight = "normal";

				protStart.style.color = "FF0000";
				protStart.style.fontWeight = "bold";

				protStart.focus();

				return false;
			}
		}

		// check stop
		for (i = 0; i < stop.length; i++)
		{
			var c = stop.charAt(i);

			if (c < "0" || c > "9")
			{
				alert("Protein stop value must be a non-negative integer; please verify your input");

				protStart.style.color = "000000";
				protStart.style.fontWeight = "normal";

				protStop.style.color = "FF0000";
				protStop.style.fontWeight = "bold";

				protStop.focus();

				return false;
			}
		}

		// Once we know the user has entered integers for start and stop, can convert and do numeric comparison
		// (Put parseFloat in here due to a bug in Javascript parseInt, where "012" is interpreted as "10" and not as "12")
		// (See http://www.go4expert.com/forums/showthread.php?t=857)
		start = parseInt(parseFloat(protStart.value));
		stop = parseInt(parseFloat(protStop.value));

		// Check that both are non-zero
		if (start == 0)
		{
			alert("Protein start value must be greater than 0; please verify your input");

			protStop.style.color = "000000";
			protStop.style.fontWeight = "normal";

			protStart.style.color = "FF0000";
			protStart.style.fontWeight = "bold";

			protStart.focus();

			return false;
		}
		else if  (stop == 0)
		{
			alert("Protein stop value must be greater than 0; please verify your input");

			protStart.style.color = "000000";
			protStart.style.fontWeight = "normal";

			protStop.style.color = "FF0000";
			protStop.style.fontWeight = "bold";

			// focus anyway
			protStop.focus();

			return false;
		}
		// Verify that start < stop
		else if (start > stop)
		{
			// Either:
			//indexWarning.style.display = "inline";

			// Or:
			alert("Start position cannot be greater than end position, please verify your input values");

			protStart.style.color = "FF0000";
			protStart.style.fontWeight = "bold";

			protStop.style.color = "FF0000";
			protStop.style.fontWeight = "bold";

			protStart.focus();

			return false;
		}
		// Not showing an error if stop exceeds sequence size - it's completely irrelevant for the program and less annoying to the user
		else
		{
			// Reset default format in case error message was displayed earlier

			// Either:
			//indexWarning.style.display = "none";

			// Or:
			protStart.style.color = "000000";
			protStart.style.fontWeight = "normal";

			protStop.style.color = "000000";
			protStop.style.fontWeight = "normal";

			protSeqOut = constProtSeq.substring(start-1, stop);	

			// June 5/08
			protLen = stop - start + 1;

			// changed June 5/08
			dnaSeqOut = constDNASeq.substring((start-1)*3, stop*3);
			//dnaSeqOut = constDNASeq.substring(dnaStartPos, dnaEndPos);

			// changed Sept. 11/08
			dnaStartPos = translStart - 1 + (start-1)*3;
			dnaEndPos = dnaStartPos + protLen*3;

			protOut.style.display = "inline";

			protOut.innerHTML = "<P><font size=2px face=Helvetica><b>Selected protein sequence to be cloned:\n</b></font><P>" + spaceAA(protSeqOut);

			dnaOut.innerHTML = "<font size=2px face=Helvetica><b>Corresponding DNA sequence:</b>  (" + (dnaStartPos+1) + " - " + dnaEndPos + ")\n</font><P>" + spaceNT(dnaSeqOut);
		}
	}
	else if (mode == 'nt')
	{
	}

	submitBtn.style.display="inline";	// Sept 14/06
	resetLinkers();				// Nov. 7/06
	linkersTbl.style.display="inline";	// Sept 8/06

	return true;
}


function spaceAA(seq)
{
	var outSeq = '';
	var chunk = '';

	if (seq.length > 100)
	{
		for (i = 0; i < seq.length; i += 10)
		{
			chunk += seq.substring(i, i+10) + ' ';

			if (chunk.length == 110)
			{
				chunk += '\n';
				outSeq += chunk;
				chunk = '';
			}

		}

		outSeq += chunk;
	}
	else
	{
		for (i = 0; i < seq.length; i += 10)
		{
			outSeq += seq.substring(i, i+10) + ' ';
		}

		outSeq += seq.substring(i);
	}


	return outSeq;
}


function filterSpace(txt)
{
	var txt_ar = txt.split(' ');
	var sel = "";

	for (i = 0; i < txt_ar.length; i++)
	{
		sel += txt_ar[i];
	}

	return sel;
}


function spaceNT(seq)
{
	var outSeq = '';
	var chunk = '';

	for (i = 0; i < seq.length; i += 3)
	{
		chunk += seq.substring(i, i+3) + ' ';

		if (chunk.length == 108)
		{
			chunk += '\n';
			outSeq += chunk;
			chunk = '';
		}
	}

	outSeq += chunk;

	return outSeq;
}


// Sept 8/06 - Show linkers
function showHideLinkers()
{
	linkerSelector = document.getElementById("linker_selector");
	linkers_row = document.getElementById("linkers_row");

	for (i = 0; i < linkerSelector.options.length; i++)
	{
		currOpt = linkerSelector.options[i];

		if (currOpt.selected)
		{
			if (currOpt.value == 'Yes')
			{
				linkers_row.style.display="table-row";
				selectLinkers();			// added Nov. 7/06 - if values are cleared, they need to be refilled
			}
			else
			{
				linkers_row.style.display="none";

				// Added Nov. 7/06: reset linker values too
				resetLinkerFields();
			}
		}
	}
}

// This function ONLY clears out the 
function resetLinkerFields()
{
	fwdLinker = document.getElementById("fp_linker");
	revLinker = document.getElementById("tp_linker");

	fwdLinker.value = "";
	revLinker.value = "";
}

// Added Nov. 7/06 by Marina
// The difference between this function and resetLinkerValues() is that this function resets ALL fields dealing with linkers - i.e. the 'yes/no add linkers' dropdown menu, the linker selection lists and the linker value text fields; resetLinkerFields(), on the other hand, ONLY clears out the values of the linker text fields
// This function is called on page reload, when ALL linker values need to be reset
function resetLinkers()
{
	linkerSelector = document.getElementById("linker_selector");

	fwdLinkerSelector = document.getElementById("fwd_linker_types_selector");
	revLinkerSelector = document.getElementById("rev_linker_types_selector");

	fwdLinker = document.getElementById("fp_linker");
	revLinker = document.getElementById("tp_linker");

	linkerSelector.options[0].selected = 1;
	fwdLinkerSelector.options[0].selected = 1;
	revLinkerSelector.options[0].selected = 1;

	fwdLinker.value = "";
	revLinker.value = "";
}

// Sept 11/06 - Prefill linker values based on option selected
function selectLinkers()
{
	fwdLinkerSelector = document.getElementById("fwd_linker_types_selector");
	revLinkerSelector = document.getElementById("rev_linker_types_selector");

	fwdLinker = document.getElementById("fp_linker");
	revLinker = document.getElementById("tp_linker");

	var fwdLinkersDict = new Object;
	var revLinkersDict = new Object;

	fwdLinkersDict['gw_atg'] = "gggg aca act ttg tac aaa aaa gtt ggc acc atg";
	fwdLinkersDict['gw_no_atg'] = "gggg aca act ttg tac aaa aaa gtt ggc acc";
	fwdLinkersDict['creator_v7_fusion_atg'] = "t acg aag tta tgg cgc gcc atg";
	fwdLinkersDict['creator_v7_fusion_no_atg'] = "t acg aag tta tgg cgc gcc";
	fwdLinkersDict['creator_v37_fusion_atg'] = "t ttt ccc cag ggg cgc gcc atg";
	fwdLinkersDict['creator_v37_fusion_no_atg'] = "t ttt ccc cag ggg cgc gcc";
	fwdLinkersDict['his_vector_atg'] = "ag gga tcc ggg cgc gcc atg";
	fwdLinkersDict['his_vector_no_atg'] = "ag gga tcc ggg cgc gcc";

	// April 22, 2009: T7/SP6 Promoters w/ Kozak for Nick and Jerry
	fwdLinkersDict['t7_promoter'] = "ggcgcgcc taatacgactcactataggg aacag ccacc";
	fwdLinkersDict['sp6_promoter'] = "ggcgcgcc tatttaggtgacactatag aacag accacc";

	revLinkersDict['gw_stop'] = "gggg ac aac ttt gta caa gaa agt tgg gta cta";
	revLinkersDict['gw_no_stop'] = "gggg ac aac ttt gta caa gaa agt tgg gta";
	revLinkersDict['creator_fusion_stop'] = "cta gga act tac ctg gtt aat taa cta";
	revLinkersDict['creator_fusion_no_stop'] = "cta gga act tac ctg gtt aat taa";
	revLinkersDict['his_vector_stop'] = "gtg ctc gag tca tca gaa ttc gtt aat taa cta";
	revLinkersDict['his_vector_no_stop'] = "gtg ctc gag tca tca gaa ttc gtt aat taa";

	// April 22, 2009: Flag-Tag for Nick and Jerry
	revLinkersDict['flag_tag'] = "tta cttgtcatcgtcatccttgtaatc gccgcctccgccgcc";

	// 5' Linkers
	for (i = 0; i < fwdLinkerSelector.options.length; i++)
	{
		currOpt = fwdLinkerSelector.options[i];

		if (currOpt.selected)
		{
// 			alert(currOpt.value);

			if (fwdLinkersDict[currOpt.value])
				fwdLinker.value = fwdLinkersDict[currOpt.value];

			else if (currOpt.value.toLowerCase() != 'other')	// updated Nov. 20/08 - added ' if (currOpt.value != 'other')'; otherwise lose value entered for 'Other' when the other linker list changes
				fwdLinker.value = "";		// Fix Dec. 15/08: had 'revLinker' instead of 'fwdLinker'
		}
	}


	// 3' Linkers
	for (i = 0; i < revLinkerSelector.options.length; i++)
	{
		currOpt = revLinkerSelector.options[i];

		if (currOpt.selected)
		{
			if (revLinkersDict[currOpt.value])
				revLinker.value = revLinkersDict[currOpt.value];

			else if (currOpt.value.toLowerCase() != 'other')	// updated Nov. 20/08 - added ' if (currOpt.value != 'other')'; otherwise lose value entered for 'Other' when the other linker list changes
				revLinker.value = "";
		}
	}
}

// Added Nov. 7/06 by Marina - Make sure Tm > 0 and length >= linker size + 10
function verifyTmAndLength()
{
	/*const MIN_PRIMER_LENGTH = 10;

	// length form input fields
	var fwdLengthFormField = document.getElementById("fwd_length_id");
	var revLengthFormField = document.getElementById("rev_length_id");

	// length input values
	var fwdLength = fwdLengthFormField.value;
	var revLength = revLengthFormField.value;*/

	// Tm form input fields
	var fwdTmFormField = document.getElementById("fwd_tm_id");
	var revTmFormField = document.getElementById("rev_tm_id");

	// Tm input values
	var fwdTm = fwdTmFormField.value;
	var revTm = revTmFormField.value;

	/*// linker values
	var fwdLinker = document.getElementById("fp_linker").value;
	var revLinker = document.getElementById("tp_linker").value;

	var numbers = "0123456789";

	// If 5' linker is added, check fwd_length >= fwd_linker + 10
	if (fwdLinker)
	{
		var minLength = fwdLinker.length + MIN_PRIMER_LENGTH;

		// CHECK THE LENGTH IS AN INTEGER!!!!
		if (fwdLength)
		{
			for (var x = 0; x < fwdLength.length; x++)
			{
				if (!inArray(fwdLength.charAt(x), numbers))
				{
					alert("Length of the 5' primer must be an integer value greater than 0.  Please verify your input.");

					revLengthFormField.style.color = "000000";
					revLengthFormField.style.fontWeight = "normal";

					fwdLengthFormField.style.color = "FF0000";
					fwdLengthFormField.style.fontWeight = "bold";

					fwdLengthFormField.focus();

					return false;
				}
			}

			if (fwdLength < minLength)
			{
				alert("Invalid input: length of the 5' primer must be at least 10 bp greater than the size of the 5' linker");

				revLengthFormField.style.color = "000000";
				revLengthFormField.style.fontWeight = "normal";

				fwdLengthFormField.style.color = "FF0000";
				fwdLengthFormField.style.fontWeight = "bold";

				fwdLengthFormField.focus();

				return false;
			}
		}
	}
	else
	{
		// Linker is not added; in that case, just verify that length > 10
		if (fwdLength)
		{
			// just check it's an integer first
			for (var x = 0; x < fwdLength.length; x++)
			{
				if (!inArray(fwdLength.charAt(x), numbers))
				{
					alert("Length of the 5' primer must be an integer value greater than 0.  Please verify your input.");

					revLengthFormField.style.color = "000000";
					revLengthFormField.style.fontWeight = "normal";

					fwdLengthFormField.style.color = "FF0000";
					fwdLengthFormField.style.fontWeight = "bold";

					fwdLengthFormField.focus();
					return false;
				}
			}

			if (fwdLength < MIN_PRIMER_LENGTH)
			{
				alert("Invalid input: length of the 5' primer must be at least 10 bp");

				revLengthFormField.style.color = "000000";
				revLengthFormField.style.fontWeight = "normal";

				fwdLengthFormField.style.color = "FF0000";
				fwdLengthFormField.style.fontWeight = "bold";

				fwdLengthFormField.focus();

				return false;
			}
		}
	}

	// Same for reverse length
	if (revLinker)
	{
		var minLength = revLinker.length + MIN_PRIMER_LENGTH;

		if (revLength)
		{
			// check it's an integer
			for (var x = 0; x < revLength.length; x++)
			{
				if (!inArray(revLength.charAt(x), numbers))
				{
					alert("Length of the 3' primer must be an integer value greater than 0.  Please verify your input.");

					fwdLengthFormField.style.color = "000000";
					fwdLengthFormField.style.fontWeight = "normal";

					revLengthFormField.style.color = "FF0000";
					revLengthFormField.style.fontWeight = "bold";

					revLengthFormField.focus();

					return false;
				}
			}

			if (revLength < minLength)
			{
				alert("Invalid input: length of the 3' primer must be at least 10 bp greater than the size of the 3' linker");

				fwdLengthFormField.style.color = "000000";
				fwdLengthFormField.style.fontWeight = "normal";

				revLengthFormField.style.color = "FF0000";
				revLengthFormField.style.fontWeight = "bold";

				revLengthFormField.focus();

				return false;
			}
		}
	}
	else
	{
		// Linker is not added; in that case, just verify that length > 10
		if (revLength)
		{
			// check it's an integer
			for (var x = 0; x < revLength.length; x++)
			{
				if (!inArray(revLength.charAt(x), numbers))
				{
					alert("Length of the 3' primer must be an integer value greater than 0.  Please verify your input.");

					fwdLengthFormField.style.color = "000000";
					fwdLengthFormField.style.fontWeight = "normal";

					revLengthFormField.style.color = "FF0000";
					revLengthFormField.style.fontWeight = "bold";


					revLengthFormField.focus();

					return false;
				}
			}

			if (revLength < MIN_PRIMER_LENGTH)
			{
				alert("Invalid input: length of the 3' primer must be at least 10 bp");

				fwdLengthFormField.style.color = "000000";
				fwdLengthFormField.style.fontWeight = "normal";

				revLengthFormField.style.color = "FF0000";
				revLengthFormField.style.fontWeight = "bold";

				revLengthFormField.focus();

				return false;
			}
		}
	}*/

	// Now check Tm
	// First, verify that the values are non-empty
	if (fwdTm.length == 0)
	{
		alert("Please provide a 5' primer Tm value");

		fwdTmFormField.style.color = "000000";
		fwdTmFormField.style.fontWeight = "normal";
		fwdTmFormField.focus();

		return false;
	}

	if (revTm.length == 0)
	{
		alert("Please provide a 3' primer Tm value");

		revTmFormField.style.color = "000000";
		revTmFormField.style.fontWeight = "normal";	
		revTmFormField.focus();

		return false;
	}		

	// check valid integer input
	for (i = 0; i < fwdTm.length; i++)
	{   
		var x = fwdTm.charAt(i);

		if (x < "0" || x > "9")
		{
			alert("Tm for the 5' primer must be a non-negative integer; please verify your input");

			fwdTmFormField.style.color = "FF0000";
			fwdTmFormField.style.fontWeight = "bold";
			fwdTmFormField.focus();

			return false;
		}
	}

	for (i = 0; i < revTm.length; i++)
	{   
		var x = revTm.charAt(i);

		if (x < "0" || x > "9")
		{
			alert("Tm for the 3' primer must be a non-negative integer; please verify your input");

			revTmFormField.style.color = "FF0000";
			revTmFormField.style.fontWeight = "bold";					
			revTmFormField.focus();

			return false;
		}
	}

	// and verify that Tm > 0
	if (fwdTm <= 0)
	{
		alert("Tm for the 5' primer must be greater than 0 degrees Celsius");

		if (fwdTm)
		{
			fwdTmFormField.style.color = "FF0000";
			fwdTmFormField.style.fontWeight = "bold";
		}
		else
		{
			fwdTmFormField.style.color = "000000";
			fwdTmFormField.style.fontWeight = "normal";
		}

		fwdTmFormField.focus();

		return false;
	}

	if (revTm <= 0)
	{
		alert("Tm for the 3' primer must be greater than 0 degrees Celsius");

		if (revTm)
		{
			revTmFormField.style.color = "FF0000";
			revTmFormField.style.fontWeight = "bold";
		}
		else
		{
			revTmFormField.style.color = "000000";
			revTmFormField.style.fontWeight = "normal";					
		}

		revTmFormField.focus();

		return false;
	}

	return true;
}

function inArray(myChar, myArray)
{
	for (var i = 0; i < myArray.length; i++)
	{
		//alert(myArray[i]);
		//alert(myChar == myArray[i]);

		if (myChar == myArray[i])
		return true;
	}

	return false;
}

function removeFormElements(aForm, elemList)
{
	var propsTbl = document.getElementById("modifyReagentPropsTbl");

	for (i = 0; i < elemList.length; i++)
	{
// 		alert("Removing " + elemList[i].id);
		aForm.removeChild(elemList[i]);
	}

// alert(propsTbl.rows.length);
}

// March 28/08: Identical to removeFormElements, except the parameters are not objects but IDs
function deleteElements(formID, elemIDList)
{
	var aForm = document.getElementById(formID);
// 	alert(aForm);

	for (i = 0; i < elemIDList.length; i++)
	{
		tmpElID = elemIDList[i];
// 		alert(tmpElID);
		tmpElem = document.getElementById(tmpElID);
// 		alert(tmpElem.value);

		aForm.removeChild(tmpElem);
	}
}


function popup(mylink, windowname, a_width, a_height, scrollbars)
{
	if (!window.focus)
		return true;

	var a_href;

	if (typeof(mylink) == 'string')
		a_href=mylink;
	else
		a_href=mylink.href;

// 	alert(href);

	window.open(a_href, windowname, "width=" + a_width + ", height=" + a_height + ", scrollbars=" + scrollbars);
	return false;
}

function showPropertyValues(listID, tblID)
{
	var propNamesList = document.getElementById(listID);
	var selectedInd = propNamesList.selectedIndex;
	var propName = propNamesList[selectedInd].value;

	var propList = document.getElementById("addlPropsListRow");
	
	// Modified March 3/08

	if (!tblID)
		tblID = "modifyReagentPropsTbl";

// 	var propsTbl = document.getElementById("modifyReagentPropsTbl");
	var propsTbl = document.getElementById(tblID);

	var prefix = "reagent_detailedview_";
	var postfix = "_prop";

	// March 3/08: Since there could be multiple property values (e.g. tag type or expression system), instead of unhiding the corresponding row, append another row to the table and make it identical to the hidden row (so the hidden row serves as a template)

//  	alert(propName);

	switch (propName)
	{
		case 'tag':
			var propRow = propsTbl.insertRow(propList.rowIndex - 1);
		
			// Need a few cells in the row
			var propNameCell = propRow.insertCell(0);
			var propValueCell = propRow.insertCell(1);

			// Set cell content and formatting as required by template
			propNameCell.className = "detailedView_colName";
			propNameCell.innerHTML = "Tag";
		
			propValueCell.className = "detailedView_value";
			propValueCell.colSpan = 5;
			propValueCell.setAttribute("white-space", "nowrap");
			propValueCell.style.fontSize = "9pt";

			propValueCell.style.paddingLeft = "5px";

			propValueCell.colSpan = 5;
			
			// Generate the list on the fly
			var tmpTagTypeList = document.getElementById("tag_list");

// removed March 20/08 - needed??
// 			var tagTypeSelectedInd = tmpTagTypeList.selectedIndex;
// 			var tagTypeSelectedValue = tmpTagTypeList[tagTypeSelectedInd].value;

			var newTagTypeList = document.createElement("SELECT");
			var newTagTypeTxt =  document.createElement("INPUT");

			newTagTypeList.setAttribute('name', prefix + propName + postfix);
			newTagTypeList.setAttribute('id', propName + "_proplist_" + propRow.rowIndex);

			propRow.setAttribute('id', "tag_row_" + propRow.rowIndex + "_id");

			newTagTypeTxt.type = "TEXT";
			newTagTypeTxt.id = "tag_txt_" + (propList.rowIndex - 1);
			newTagTypeTxt.style.display = "none";

			for (i = 0; i < tmpTagTypeList.options.length; i++)
			{
				tmpOptn = tmpTagTypeList.options[i];
				newOptn = document.createElement("OPTION");

				newOptn.value = tmpOptn.value;
				newOptn.name = tmpOptn.name;
				newOptn.text = tmpOptn.text;

				if (newTagTypeList.options.length == 0)
					newTagTypeList.options.add(newOptn);
				else
					addElement(newTagTypeList, newTagTypeList.options.length, newOptn);
			}

// 			newTagTypeList.onchange = function() {showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);}
// {showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id)};

			propValueCell.appendChild(newTagTypeList);
			propValueCell.appendChild(newTagTypeTxt);

			// Adjust border height to stretch to bottom of page
// 			mainBorder = document.getElementById("mainBorder");
// 			mainBorder.height = document.height;
// 			mainBorder.rowSpan = propsTbl.rows.length;
		
			// For each tag type, show tag position
			// Modified March 17/08: Instead of showing a hidden row, create a new element and append to form (as done for all other elements; won't save otherwise)
			var newTagPosRow = propsTbl.insertRow(propRow.rowIndex+1);

			newTagPosRow.setAttribute('id', "tag_position_row_" + propRow.rowIndex + "_id");

			// Need a few cells in the row
			var newTagPosNameCell = newTagPosRow.insertCell(0);
			var newTagPosValueCell = newTagPosRow.insertCell(1);

			newTagPosValueCell.className = "detailedView_value";
			newTagPosValueCell.colSpan = 5;

			newTagPosValueCell.style.paddingLeft = "5px";

			newTagPosNameCell.className = "detailedView_colName";
			newTagPosNameCell.innerHTML = "Tag Position";

			// March 17/08: Generate Tag Position option list
			var oldTagPosList = document.getElementById("tag_position_list");
			var newTagPosList = document.createElement("SELECT");

			newTagPosList.setAttribute('id', "tag_position_proplist_" + propRow.rowIndex);

			for (i = 0; i < oldTagPosList.options.length; i++)
			{
				tmpOptn = oldTagPosList.options[i];
				newOptn = document.createElement("OPTION");

				newOptn.value = tmpOptn.value;
				newOptn.name = tmpOptn.name;
				newOptn.text = tmpOptn.text;

				if (newTagPosList.options.length == 0)
					newTagPosList.options.add(newOptn);
				else
					addElement(newTagPosList, newTagPosList.options.length, newOptn);
			}

			newTagPosValueCell.setAttribute("font-size", "9pt");
			newTagPosValueCell.setAttribute("white-space", "nowrap");
			newTagPosValueCell.colSpan = 5;

			newTagPosValueCell.appendChild(newTagPosList);

			// April 2/08: Karen asked to show positions and orientation on a new line for ALL features
			var tmpTagPosRow = propsTbl.insertRow(newTagPosRow.rowIndex+1);
			tmpTagPosRow.setAttribute('id', "tag_pos_row" + tmpTagPosRow.rowIndex + "_id");
			var tmpTagPosRowID = tmpTagPosRow.id;

			var c1 = tmpTagPosRow.insertCell(0);
			var tagPosCell = tmpTagPosRow.insertCell(1);

			tagPosCell.className = "detailedView_value";
			tagPosCell.style.paddingLeft = "5px";
			tagPosCell.setAttribute("white-space", "nowrap");
			tagPosCell.colSpan = 5;

			tagPosCell.innerHTML = "Start:&nbsp;";

			var newTagTypeStart = document.createElement("INPUT");
			newTagTypeStart.setAttribute("type", 'TEXT');
			newTagTypeStart.setAttribute("size", 5);
			newTagTypeStart.setAttribute("id", propName + "_" + propRow.rowIndex + "_startpos_id");

			tagPosCell.appendChild(newTagTypeStart);

			tagPosCell.innerHTML += "&nbsp;&nbsp;End:&nbsp;";
			
			var newTagTypeEnd = document.createElement("INPUT");
			newTagTypeEnd.setAttribute("type", 'TEXT');
			newTagTypeEnd.setAttribute("size", 5);
			newTagTypeEnd.setAttribute("id", propName + "_" + propRow.rowIndex + "_endpos_id");

			tagPosCell.appendChild(newTagTypeEnd);

			// Orientation
			tagPosCell.innerHTML += "<SPAN style=\"margin-left:10px;\"></span>";

			var fwdDir = document.createElement("INPUT");
			fwdDir.setAttribute("type", "radio");
			fwdDir.setAttribute("id", propName + "_" + propRow.rowIndex + "_fwd_dir");
			fwdDir.setAttribute("name", propName + "_orientation_radio_" + propRow.rowIndex);
			fwdDir.setAttribute("checked", true);

			tagPosCell.appendChild(fwdDir);
			tagPosCell.innerHTML += "Forward&nbsp;";
 
			var revDir = document.createElement("INPUT");
			revDir.setAttribute("type", "radio");
			revDir.setAttribute("id", propName + "_" + propRow.rowIndex + "_rev_dir");
			revDir.setAttribute("name", propName + "_orientation_radio_" + propRow.rowIndex);

			tagPosCell.appendChild(revDir);
			tagPosCell.innerHTML += "Reverse";

			// Add 'Remove' link
			var removeLink = document.createElement("SPAN");
			removeLink.className = "linkShow";
			removeLink.style.fontSize = "9pt";
			removeLink.style.fontWeight = "normal";
			removeLink.style.marginLeft = "10px";
			removeLink.innerHTML = "Remove";

			tagPosCell.appendChild(removeLink);

			// Add a separator row
			var divRow = propsTbl.insertRow(tmpTagPosRow.rowIndex + 1);
			var divCell = divRow.insertCell(0);

			divRow.setAttribute('id', "div_row" + divRow.rowIndex + "_id");
			divCell.colSpan = 6;
			divCell.innerHTML = "<HR>";

			// Gather the IDs of all elements in the row to pass to 'deleteRow' function
// 			var tblID = "modifyReagentPropsTbl";
			var tagTypeRowID = propRow.id;
			var tagPosRowID = newTagPosRow.id;
			var divRowID = divRow.id;
		
			// Add list to form
			var myForm = document.reagentDetailForm;

			var hiddenTagType = document.createElement("INPUT");
			hiddenTagType.setAttribute("type", "hidden");
			hiddenTagType.setAttribute("id", "tag_type_prop" + propRow.rowIndex);
			myForm.appendChild(hiddenTagType);

			var hiddenTagTypeStart = document.createElement("INPUT");
			hiddenTagTypeStart.setAttribute("type", "hidden");
			hiddenTagTypeStart.setAttribute("id", "tag_startpos_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenTagTypeStart);

			var hiddenTagTypeEnd = document.createElement("INPUT");
			hiddenTagTypeEnd.setAttribute("type", "hidden");
			hiddenTagTypeEnd.setAttribute("id", "tag_endpos_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenTagTypeEnd);

			// Tag position
			var hiddenTagPosField = document.createElement("INPUT");
			hiddenTagPosField.setAttribute('type', 'hidden');
			hiddenTagPosField.setAttribute("id", "tag_position_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenTagPosField);

			// Orientation
			var hiddenTagTypeDir = document.createElement("INPUT");
			hiddenTagTypeDir.setAttribute('type', 'hidden');
			hiddenTagTypeDir.setAttribute("id", "tag_orientation_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenTagTypeDir);

			var elemList = new Array();
			elemList.push(hiddenTagType);
			elemList.push(hiddenTagTypeStart);
			elemList.push(hiddenTagTypeEnd);
			elemList.push(hiddenTagTypeDir);

			removeLink.onclick = function(){deleteTableRow(tblID, tagTypeRowID); deleteTableRow(tblID, tagPosRowID); deleteTableRow(tblID, tmpTagPosRowID); deleteTableRow(tblID, divRowID); removeFormElements(myForm, elemList)};
		break;

		case 'promoter':
			var propRow = propsTbl.insertRow(propList.rowIndex - 1);
		
			// Need a few cells in the row
			var propNameCell = propRow.insertCell(0);
			var propValueCell = propRow.insertCell(1);

			// Set cell content and formatting as required by template
			propNameCell.className = "detailedView_colName";
			propNameCell.innerHTML = "Promoter";
		
			propValueCell.className = "detailedView_value";
			propValueCell.setAttribute("white-space", "nowrap");
			propValueCell.style.fontSize = "9pt";
// 			propValueCell.style.fontWeight = "normal";
			propValueCell.colSpan = 5;
			
			propValueCell.style.paddingLeft = "5px";

			// Generate the list on the fly
			var tmpPromoterList = document.getElementById("promoter_list");
			var newPromoterList = document.createElement("SELECT");
			var newPromoterTxt =  document.createElement("INPUT");

			newPromoterList.setAttribute('id', propName + "_proplist_" + propRow.rowIndex);
// 			newPromoterList.setAttribute('name', "reagent_detailedview_" + propName + "_prop");

			propRow.setAttribute('id', "promoter_row_" + propRow.rowIndex + "_id");

			newPromoterTxt.type = "TEXT";
			newPromoterTxt.id = "promo_txt_" + (propList.rowIndex - 1);
			newPromoterTxt.style.display = "none";

			for (i = 0; i < tmpPromoterList.options.length; i++)
			{
				tmpOptn = tmpPromoterList.options[i];
				newOptn = document.createElement("OPTION");

				newOptn.value = tmpOptn.value;
				newOptn.name = tmpOptn.name;
				newOptn.text = tmpOptn.text;

				if (newPromoterList.options.length == 0)
					newPromoterList.options.add(newOptn);
				else
					addElement(newPromoterList, newPromoterList.options.length, newOptn);
			}

			propValueCell.appendChild(newPromoterList);
			propValueCell.appendChild(newPromoterTxt);

			// Adjust border height to stretch to bottom of page
// 			mainBorder = document.getElementById("mainBorder");
// 			mainBorder.height = document.height;
// 			mainBorder.rowSpan = propsTbl.rows.length;
		
			// Show expression system too
			var newExpSysRow = propsTbl.insertRow(propRow.rowIndex+1);

			newExpSysRow.setAttribute('id', "expression_system_row_" + propRow.rowIndex + "_id");

			// Need a few cells in the row
			var newExpSysNameCell = newExpSysRow.insertCell(0);
			var newExpSysValueCell = newExpSysRow.insertCell(1);

			newExpSysNameCell.className = "detailedView_colName";
			newExpSysValueCell.style.paddingLeft = "5px";
			
			var oldExpSystList = document.getElementById("expression_system_list");
			var newExpSystList = document.createElement("SELECT");

			newExpSystList.setAttribute('id', "expression_system_prop_list_" + propRow.rowIndex);

			for (i = 0; i < oldExpSystList.options.length; i++)
			{
				tmpOptn = oldExpSystList.options[i];
				newOptn = document.createElement("OPTION");

				newOptn.value = tmpOptn.value;
				newOptn.name = tmpOptn.name;
				newOptn.text = tmpOptn.text;

				if (newExpSystList.options.length == 0)
					newExpSystList.options.add(newOptn);
				else
					addElement(newExpSystList, newExpSystList.options.length, newOptn);
			}

			newExpSysNameCell.innerHTML = "Expression System";

			newExpSysValueCell.setAttribute("font-size", "9pt");
			newExpSysValueCell.setAttribute("font-weight", "normal");
			newExpSysValueCell.setAttribute("white-space", "nowrap");
			newExpSysValueCell.colSpan = 5;

			newExpSysValueCell.appendChild(newExpSystList);

			// April 2/08: Karen asked to show positions and orientation on a new line for ALL features
			var tmpPromPosRow = propsTbl.insertRow(newExpSysRow.rowIndex+1);
			tmpPromPosRow.setAttribute('id', "promoter_pos_row" + tmpPromPosRow.rowIndex + "_id");
			var tmpPromPosRowID = tmpPromPosRow.id;

			var c1 = tmpPromPosRow.insertCell(0);
			var promPosCell = tmpPromPosRow.insertCell(1);

			promPosCell.className = "detailedView_value";
			promPosCell.style.paddingLeft = "5px";
			promPosCell.setAttribute("white-space", "nowrap");
			promPosCell.colSpan = 5;

			promPosCell.innerHTML = "Start:&nbsp;";

// 			propValueCell.innerHTML += "<SPAN style=\"margin-left:32px;\"></span>Start:&nbsp;";

			var newPromStart = document.createElement("INPUT");
			newPromStart.setAttribute("type", 'TEXT');
			newPromStart.setAttribute("size", 5);
			newPromStart.setAttribute("id", propName + "_" + propRow.rowIndex + "_startpos_id");

			promPosCell.appendChild(newPromStart);

			promPosCell.innerHTML += "&nbsp;&nbsp;End:&nbsp;";
			
			var newPromEnd = document.createElement("INPUT");
			newPromEnd.setAttribute("type", 'TEXT');
			newPromEnd.setAttribute("size", 5);
			newPromEnd.setAttribute("id", propName + "_" + propRow.rowIndex + "_endpos_id");

			promPosCell.appendChild(newPromEnd);

			newPromoterList.onchange = function()
			{
// 				showPromoterBox(newPromoterList.id, newPromoterTxt.id);
			};

			// Orientation - March 14/08: Make 'forward' checked by default
			promPosCell.innerHTML += "<SPAN style=\"margin-left:10px;\"></span>";

			var fwdDir = document.createElement("INPUT");
			fwdDir.setAttribute("type", "radio");
			fwdDir.setAttribute("id", propName + "_" + propRow.rowIndex + "_fwd_dir");
			fwdDir.setAttribute("name", propName + "_orientation_radio_" + propRow.rowIndex);
			fwdDir.setAttribute("checked", true);

			promPosCell.appendChild(fwdDir);
			promPosCell.innerHTML += "Forward&nbsp;";
 
			var revDir = document.createElement("INPUT");
			revDir.setAttribute("type", "radio");
			revDir.setAttribute("id", propName + "_" + propRow.rowIndex + "_rev_dir");
			revDir.setAttribute("name", propName + "_orientation_radio_" + propRow.rowIndex);

			promPosCell.appendChild(revDir);
			promPosCell.innerHTML += "Reverse";

			// Add 'Remove' link
			var removeLink = document.createElement("SPAN");
			removeLink.className = "linkShow";
			removeLink.style.fontSize = "9pt";
			removeLink.style.fontWeight = "normal";
			removeLink.style.marginLeft = "10px";
			removeLink.innerHTML = "Remove";
		
			promPosCell.appendChild(removeLink);

			// Add a separator row
			var divRow = propsTbl.insertRow(tmpPromPosRow.rowIndex + 1);
			var divCell = divRow.insertCell(0);

			divRow.setAttribute('id', "div_row" + divRow.rowIndex + "_id");
			divCell.colSpan = 6;
			divCell.innerHTML = "<HR>";

			// Gather the IDs of all elements in the row to pass to 'deleteRow' function
// 			var tblID = "modifyReagentPropsTbl";
			var promRowID = propRow.id;
			var expRowID = newExpSysRow.id;
			var divRowID = divRow.id;
		
			// Add list to form
			var myForm = document.reagentDetailForm;

			var hiddenPromoter = document.createElement("INPUT");
			hiddenPromoter.setAttribute("type", "hidden");
			myForm.appendChild(hiddenPromoter);

			var hiddenPromStart = document.createElement("INPUT");
			hiddenPromStart.setAttribute("type", "hidden");
			hiddenPromStart.setAttribute("id", "promoter_startpos_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenPromStart);

			var hiddenPromEnd = document.createElement("INPUT");
			hiddenPromEnd.setAttribute("type", "hidden");
			hiddenPromEnd.setAttribute("id", "promoter_endpos_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenPromEnd);

			var hiddenExprSyst = document.createElement("INPUT");
			hiddenExprSyst.setAttribute("type", "hidden");
			hiddenExprSyst.setAttribute("id", "expression_system_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenExprSyst);

			// Orientation
			var hiddenPromoDir = document.createElement("INPUT");
			hiddenPromoDir.setAttribute('type', 'hidden');
			hiddenPromoDir.setAttribute("id", "promoter_orientation_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenPromoDir);

			var elemList = new Array();
			elemList.push(hiddenPromoter);
			elemList.push(hiddenPromStart);
			elemList.push(hiddenPromEnd);
			elemList.push(hiddenExprSyst);
			elemList.push(hiddenPromoDir);

			removeLink.onclick = function(){deleteTableRow(tblID, promRowID); deleteTableRow(tblID, expRowID); deleteTableRow(tblID, tmpPromPosRowID); deleteTableRow(tblID, divRowID); removeFormElements(myForm, elemList)};
		break;
 
		case 'selectable_marker':
			var propRow = propsTbl.insertRow(propList.rowIndex - 1);
		
			// Need a few cells in the row
			var propNameCell = propRow.insertCell(0);
			var propValueCell = propRow.insertCell(1);

			// Set cell content and formatting as required by template
			propNameCell.className = "detailedView_colName";
			propNameCell.innerHTML = "Selectable Marker";
		
			propValueCell.className = "detailedView_value";
			propValueCell.setAttribute("white-space", "nowrap");
			propValueCell.style.fontSize = "9pt";
			propValueCell.colSpan = 5;

			propValueCell.style.paddingLeft = "5px";

			// Generate the list on the fly
			var tmpMarkerList = document.getElementById("selectable_marker_list");
			var markerSelectedInd = tmpMarkerList.selectedIndex;
			var markerSelectedValue = tmpMarkerList[markerSelectedInd].value;

			var newMarkerList = document.createElement("SELECT");
			var newMarkerTxt =  document.createElement("INPUT");

			newMarkerList.setAttribute("name", prefix + propName + postfix);
			newMarkerList.setAttribute("id", propName + "_proplist_" + propRow.rowIndex);

			propRow.setAttribute('id', "selectabler_marker_row_" + propRow.rowIndex + "_id");

			newMarkerTxt.type = "TEXT";
			newMarkerTxt.id = "selectable_marker_txt_" + (propList.rowIndex - 1);
			newMarkerTxt.style.display = "none";

			for (i = 0; i < tmpMarkerList.options.length; i++)
			{
				tmpOptn = tmpMarkerList.options[i];
				newOptn = document.createElement("OPTION");

				newOptn.value = tmpOptn.value;
				newOptn.name = tmpOptn.name;
				newOptn.text = tmpOptn.text;

				if (newMarkerList.options.length == 0)
					newMarkerList.options.add(newOptn);
				else
					addElement(newMarkerList, newMarkerList.options.length, newOptn);
			}

			propValueCell.appendChild(newMarkerList);
			propValueCell.appendChild(newMarkerTxt);

			// Start & stop positions
			// April 1, 2008: Show in a new row
			var smPosRow = propsTbl.insertRow(propRow.rowIndex+1);
			smPosRow.setAttribute('id', "sm_pos_row" + smPosRow.rowIndex + "_id");
			var smPosRowID = smPosRow.id;

			var c1 = smPosRow.insertCell(0);
			var smPosCell = smPosRow.insertCell(1);

			smPosCell.style.paddingLeft = "5px";

			smPosCell.innerHTML = "Start:&nbsp;";
			smPosCell.className = "detailedView_value";
			
			var newMarkerStart = document.createElement("INPUT");
			newMarkerStart.setAttribute("type", 'TEXT');
			newMarkerStart.setAttribute("size", 5);
			newMarkerStart.setAttribute("id", propName + "_" + propRow.rowIndex + "_startpos_id");

			smPosCell.appendChild(newMarkerStart);
			smPosCell.innerHTML += "&nbsp;&nbsp;End:&nbsp;";
			
			var newMarkerEnd = document.createElement("INPUT");
			newMarkerEnd.setAttribute("type", 'TEXT');
			newMarkerEnd.setAttribute("size", 5);
			newMarkerEnd.setAttribute("id", propName + "_" + propRow.rowIndex + "_endpos_id");

			smPosCell.appendChild(newMarkerEnd);
			smPosCell.colSpan=5;
			smPosCell.setAttribute("white-space", "nowrap");

			newMarkerList.onchange = function()
			{
// 				showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);
			};

			// Orientation - March 14/08: Make 'forward' checked by default
			smPosCell.innerHTML += "<SPAN style=\"margin-left:10px;\"></span>";

			var fwdDir = document.createElement("INPUT");
			fwdDir.setAttribute("type", "radio");
			fwdDir.setAttribute("id", propName + "_" + propRow.rowIndex + "_fwd_dir");
			fwdDir.setAttribute("name", propName + "_orientation_radio_" + propRow.rowIndex);
			fwdDir.setAttribute("checked", true);

			smPosCell.appendChild(fwdDir);
			smPosCell.innerHTML += "Forward&nbsp;";
 
			var revDir = document.createElement("INPUT");
			revDir.setAttribute("type", "radio");
			revDir.setAttribute("id", propName + "_" + propRow.rowIndex + "_rev_dir");
			revDir.setAttribute("name", propName + "_orientation_radio_" + propRow.rowIndex);
			smPosCell.appendChild(revDir);
			smPosCell.innerHTML += "Reverse";

			// Add 'Remove' link
			var removeLink = document.createElement("SPAN");
			removeLink.className = "linkShow";
			removeLink.style.fontSize = "9pt";
			removeLink.style.fontWeight = "normal";
			removeLink.style.marginLeft = "10px";
			removeLink.innerHTML = "Remove";
		
			smPosCell.appendChild(removeLink);

			// Adjust border height to stretch to bottom of page
// 			mainBorder = document.getElementById("mainBorder");
// 			mainBorder.height = document.height;
// 			mainBorder.rowSpan = propsTbl.rows.length;
		
			// Add a separator row
			var divRow = propsTbl.insertRow(smPosRow.rowIndex + 1);
			var divCell = divRow.insertCell(0);

			divRow.setAttribute('id', "div_row" + divRow.rowIndex + "_id");
			divCell.colSpan = 6;
			divCell.innerHTML = "<HR>";

			// 'Remove' link
// 			var tblID = "modifyReagentPropsTbl";
			var markerRowID = propRow.id;
			var divRowID = divRow.id;
		
			// Add list to form
			var myForm = document.reagentDetailForm;

			var hiddenMarker = document.createElement("INPUT");
			hiddenMarker.setAttribute("type", "hidden");
// 			hiddenMarker.setAttribute("name", newMarkerList.name);
			myForm.appendChild(hiddenMarker);

			var hiddenMarkerStart = document.createElement("INPUT");
			hiddenMarkerStart.setAttribute("type", "hidden");
			hiddenMarkerStart.setAttribute("id", "selectable_marker_startpos_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenMarkerStart);

			var hiddenMarkerEnd = document.createElement("INPUT");
			hiddenMarkerEnd.setAttribute("type", "hidden");
			hiddenMarkerEnd.setAttribute("id", "selectable_marker_endpos_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenMarkerEnd);

			// Orientation
			var hiddenMarkerDir = document.createElement("INPUT");
			hiddenMarkerDir.setAttribute('type', 'hidden');
			hiddenMarkerDir.setAttribute("id", "selectable_marker_orientation_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenMarkerDir);

			var elemList = new Array();
			elemList.push(hiddenMarker);
			elemList.push(hiddenMarkerStart);
			elemList.push(hiddenMarkerEnd);
			elemList.push(hiddenMarkerDir);

			removeLink.onclick = function(){deleteTableRow(tblID, markerRowID); deleteTableRow(tblID, smPosRowID); deleteTableRow(tblID, divRowID); removeFormElements(myForm, elemList)};
		break;

		case 'polya':
		case 'polyA':
			propName = "polyA";
			var propRow = propsTbl.insertRow(propList.rowIndex - 1);
		
			// Need a few cells in the row
			var propNameCell = propRow.insertCell(0);
			var propValueCell = propRow.insertCell(1);

			// Set cell content and formatting as required by template
			propNameCell.className = "detailedView_colName";
			propNameCell.innerHTML = "PolyA Tail";
		
			propValueCell.className = "detailedView_value";
			propValueCell.setAttribute("white-space", "nowrap");
			propValueCell.style.fontSize = "9pt";
			propValueCell.colSpan = 5;
			propValueCell.style.paddingLeft = "5px";
			
			// Generate the list on the fly
			var tmpPolyAList = document.getElementById("polyA_list");
			var polyASelectedInd = tmpPolyAList.selectedIndex;
			var polyASelectedValue = tmpPolyAList[polyASelectedInd].value;

			var newPolyAList = document.createElement("SELECT");
			var newPolyATxt =  document.createElement("INPUT");

			newPolyAList.setAttribute("name", prefix + propName + postfix);
			newPolyAList.setAttribute("id", propName + "_proplist_" + propRow.rowIndex);

			propRow.setAttribute('id', "polyA_row_" + propRow.rowIndex + "_id");

			newPolyATxt.type = "TEXT";
			newPolyATxt.id = "polyA_txt_" + (propList.rowIndex - 1);
			newPolyATxt.style.display = "none";

			for (i = 0; i < tmpPolyAList.options.length; i++)
			{
				tmpOptn = tmpPolyAList.options[i];
				newOptn = document.createElement("OPTION");

				newOptn.value = tmpOptn.value;
				newOptn.name = tmpOptn.name;
				newOptn.text = tmpOptn.text;

				if (newPolyAList.options.length == 0)
					newPolyAList.options.add(newOptn);
				else
					addElement(newPolyAList, newPolyAList.options.length, newOptn);
			}

			propValueCell.appendChild(newPolyAList);
			propValueCell.appendChild(newPolyATxt);

			// Start & stop positions
			// April 2/08: Karen asked to show positions and orientation on a new line for ALL features
			var tmpPolyPosRow = propsTbl.insertRow(propRow.rowIndex+1);
			tmpPolyPosRow.setAttribute('id', "polyA_pos_row" + tmpPolyPosRow.rowIndex + "_id");
			var tmpPolyPosRowID = tmpPolyPosRow.id;

			var c1 = tmpPolyPosRow.insertCell(0);
			var polyPosCell = tmpPolyPosRow.insertCell(1);

			polyPosCell.className = "detailedView_value";
			polyPosCell.style.paddingLeft = "5px";
			polyPosCell.colSpan=5;
			polyPosCell.setAttribute("white-space", "nowrap");

			polyPosCell.innerHTML += "Start:&nbsp;";
			
			var newPolyAStart = document.createElement("INPUT");
			newPolyAStart.setAttribute("type", 'TEXT');
			newPolyAStart.setAttribute("size", 5);
			newPolyAStart.setAttribute("id", propName + "_" + propRow.rowIndex + "_startpos_id");

			polyPosCell.appendChild(newPolyAStart);

			polyPosCell.innerHTML += "&nbsp;&nbsp;End:&nbsp;";
			
			var newPolyAEnd = document.createElement("INPUT");
			newPolyAEnd.setAttribute("type", 'TEXT');
			newPolyAEnd.setAttribute("size", 5);
			newPolyAEnd.setAttribute("id", propName + "_" + propRow.rowIndex + "_endpos_id");

			polyPosCell.appendChild(newPolyAEnd);

			newPolyAList.onchange = function()
			{
// 				showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);
			};

			// Orientation - March 14/08: Make 'forward' checked by default
			polyPosCell.innerHTML += "<SPAN style=\"margin-left:10px;\"></span>";

			var fwdDir = document.createElement("INPUT");
			fwdDir.setAttribute("type", "radio");
			fwdDir.setAttribute("id", propName + "_" + propRow.rowIndex + "_fwd_dir");
			fwdDir.setAttribute("name", propName + "_orientation_radio_" + propRow.rowIndex);
			fwdDir.setAttribute("checked", true);

			polyPosCell.appendChild(fwdDir);
			polyPosCell.innerHTML += "Forward&nbsp;";
 
			var revDir = document.createElement("INPUT");
			revDir.setAttribute("type", "radio");
			revDir.setAttribute("id", propName + "_" + propRow.rowIndex + "_rev_dir");
			revDir.setAttribute("name", propName + "_orientation_radio_" + propRow.rowIndex);

			polyPosCell.appendChild(revDir);
			polyPosCell.innerHTML += "Reverse";

			// Add 'Remove' link
			var removeLink = document.createElement("SPAN");
			removeLink.className = "linkShow";
			removeLink.style.fontSize = "9pt";
			removeLink.style.fontWeight = "normal";
			removeLink.style.marginLeft = "10px";
			removeLink.innerHTML = "Remove";
		
			polyPosCell.appendChild(removeLink);

			// Adjust border height to stretch to bottom of page
// 			mainBorder = document.getElementById("mainBorder");
// 			mainBorder.height = document.height;
// 			mainBorder.rowSpan = propsTbl.rows.length;
		
			// Add a separator row
			var divRow = propsTbl.insertRow(tmpPolyPosRow.rowIndex + 1);
			var divCell = divRow.insertCell(0);

			divRow.setAttribute('id', "div_row" + divRow.rowIndex + "_id");
			divCell.colSpan = 6;
			divCell.innerHTML = "<HR>";

			// Gather the IDs of all elements in the row to pass to 'deleteRow' function
// 			var tblID = "modifyReagentPropsTbl";
			var polyARowID = propRow.id;
			var divRowID = divRow.id;
		
			// Add list to form
			var myForm = document.reagentDetailForm;

			var hiddenPolyA = document.createElement("INPUT");
			hiddenPolyA.setAttribute("type", "hidden");
			myForm.appendChild(hiddenPolyA);

			var hiddenPolyAStart = document.createElement("INPUT");
			hiddenPolyAStart.setAttribute("type", "hidden");
			hiddenPolyAStart.setAttribute("id", "polyA_startpos_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenPolyAStart);

			var hiddenPolyAEnd = document.createElement("INPUT");
			hiddenPolyAEnd.setAttribute("type", "hidden");
			hiddenPolyAEnd.setAttribute("id", "polyA_endpos_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenPolyAEnd);

			// Orientation
			var hiddenPolyADir = document.createElement("INPUT");
			hiddenPolyADir.setAttribute('type', 'hidden');
			hiddenPolyADir.setAttribute("id", "polyA_orientation_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenPolyADir);

			var elemList = new Array();
			elemList.push(hiddenPolyA);
			elemList.push(hiddenPolyAStart);
			elemList.push(hiddenPolyAEnd);
			elemList.push(hiddenPolyADir);

			removeLink.onclick = function(){deleteTableRow(tblID, polyARowID); deleteTableRow(tblID, tmpPolyPosRowID); deleteTableRow(tblID, divRowID); removeFormElements(myForm, elemList)};
		break;
	
		case 'origin_of_replication':
			var propRow = propsTbl.insertRow(propList.rowIndex - 1);
		
			// Need a few cells in the row
			var propNameCell = propRow.insertCell(0);
			var propValueCell = propRow.insertCell(1);

			// Set cell content and formatting as required by template
			propNameCell.className = "detailedView_colName";
			propNameCell.innerHTML = "Origin of Replication";
		
			propValueCell.className = "detailedView_value";
			propValueCell.setAttribute("white-space", "nowrap");
			propValueCell.style.fontSize = "9pt";
			propValueCell.colSpan = 5;
			propValueCell.style.paddingLeft = "5px";
			
			// Generate the list on the fly
			var tmpOriginList = document.getElementById("origin_of_replication_list");
			var originSelectedInd = tmpOriginList.selectedIndex;
			var originSelectedValue = tmpOriginList[originSelectedInd].value;

			var newOriginList = document.createElement("SELECT");
			var newOriginTxt =  document.createElement("INPUT");

// 			newOriginList.setAttribute("name", prefix + propName + postfix);
			newOriginList.setAttribute("id", propName + "_proplist_" + propRow.rowIndex);

			propRow.setAttribute('id', "origin_of_replication_row_" + propRow.rowIndex + "_id");

			newOriginTxt.type = "TEXT";
			newOriginTxt.id = "origin_of_replication_txt_" + (propList.rowIndex - 1);
			newOriginTxt.style.display = "none";

			for (i = 0; i < tmpOriginList.options.length; i++)
			{
				tmpOptn = tmpOriginList.options[i];
				newOptn = document.createElement("OPTION");

				newOptn.value = tmpOptn.value;
				newOptn.name = tmpOptn.name;
				newOptn.text = tmpOptn.text;

				if (newOriginList.options.length == 0)
					newOriginList.options.add(newOptn);
				else
					addElement(newOriginList, newOriginList.options.length, newOptn);
			}

			propValueCell.appendChild(newOriginList);
			propValueCell.appendChild(newOriginTxt);

			// Start & stop positions
			// April 2/08: Karen asked to show positions and orientation on a new line for ALL features
			var tmpOriginPosRow = propsTbl.insertRow(propRow.rowIndex+1);
			tmpOriginPosRow.setAttribute('id', "origin_of_replication_pos_row" + tmpOriginPosRow.rowIndex + "_id");
			var tmpOriginPosRowID = tmpOriginPosRow.id;

			var c1 = tmpOriginPosRow.insertCell(0);
			var originPosCell = tmpOriginPosRow.insertCell(1);

			originPosCell.className = "detailedView_value";
			originPosCell.style.paddingLeft = "5px";
			originPosCell.setAttribute("white-space", "nowrap");
			originPosCell.colSpan = 5;

			originPosCell.innerHTML += "Start:&nbsp;";

// 			propValueCell.innerHTML += "<SPAN style=\"margin-left:8px;\"></span>Start:&nbsp;";
			
			var newOriginStart = document.createElement("INPUT");
			newOriginStart.setAttribute("type", 'TEXT');
			newOriginStart.setAttribute("size", 5);
			newOriginStart.setAttribute("id", propName + "_" + propRow.rowIndex + "_startpos_id");

			originPosCell.appendChild(newOriginStart);

			originPosCell.innerHTML += "&nbsp;&nbsp;End:&nbsp;";
			
			var newOriginEnd = document.createElement("INPUT");
			newOriginEnd.setAttribute("type", 'TEXT');
			newOriginEnd.setAttribute("size", 5);
			newOriginEnd.setAttribute("id", propName + "_" + propRow.rowIndex + "_endpos_id");

			originPosCell.appendChild(newOriginEnd);

			newOriginList.onchange = function()
			{
// 				showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);
			};

			// Orientation - March 14/08: Make 'forward' checked by default
			originPosCell.innerHTML += "<SPAN style=\"margin-left:10px;\"></span>";

			var fwdDir = document.createElement("INPUT");
			fwdDir.setAttribute("type", "radio");
			fwdDir.setAttribute("id", propName + "_" + propRow.rowIndex + "_fwd_dir");
			fwdDir.setAttribute("name", propName + "_orientation_radio_" + propRow.rowIndex);
			fwdDir.setAttribute("checked", true);

			originPosCell.appendChild(fwdDir);
			originPosCell.innerHTML += "Forward&nbsp;";
 
			var revDir = document.createElement("INPUT");
			revDir.setAttribute("type", "radio");
			revDir.setAttribute("id", propName + "_" + propRow.rowIndex + "_rev_dir");
			revDir.setAttribute("name", propName + "_orientation_radio_" + propRow.rowIndex);

			originPosCell.appendChild(revDir);
			originPosCell.innerHTML += "Reverse";

			// Add 'Remove' link
			var removeLink = document.createElement("SPAN");
			removeLink.className = "linkShow";
			removeLink.style.fontSize = "9pt";
			removeLink.style.fontWeight = "normal";
			removeLink.style.marginLeft = "10px";
			removeLink.innerHTML = "Remove";
		
			originPosCell.appendChild(removeLink);

			// Adjust border height to stretch to bottom of page
// 			mainBorder = document.getElementById("mainBorder");
// 			mainBorder.height = document.height;
// 			mainBorder.rowSpan = propsTbl.rows.length;
		
			// Add a separator row
			var divRow = propsTbl.insertRow(tmpOriginPosRow.rowIndex + 1);
			var divCell = divRow.insertCell(0);

			divRow.setAttribute('id', "div_row" + divRow.rowIndex + "_id");
			divCell.colSpan = 6;
			divCell.innerHTML = "<HR>";

			// 'Remove' link
// 			var tblID = "modifyReagentPropsTbl";
			var originRowID = propRow.id;
			var divRowID = divRow.id;
		
			// Add list to form
			var myForm = document.reagentDetailForm;

			var hiddenOrigin = document.createElement("INPUT");
			hiddenOrigin.setAttribute("type", "hidden");
			myForm.appendChild(hiddenOrigin);

			var hiddenOriginStart = document.createElement("INPUT");
			hiddenOriginStart.setAttribute("type", "hidden");
			hiddenOriginStart.setAttribute("id", "origin_of_replication_startpos_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenOriginStart);

			var hiddenOriginEnd = document.createElement("INPUT");
			hiddenOriginEnd.setAttribute("type", "hidden");
			hiddenOriginEnd.setAttribute("id", "origin_of_replication_endpos_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenOriginEnd);

			// Orientation
			var hiddenOriginDir = document.createElement("INPUT");
			hiddenOriginDir.setAttribute('type', 'hidden');
			hiddenOriginDir.setAttribute("id", "origin_of_replication_orientation_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenOriginDir);

			var elemList = new Array();
			elemList.push(hiddenOrigin);
			elemList.push(hiddenOriginStart);
			elemList.push(hiddenOriginEnd);
			elemList.push(hiddenOriginDir);

			removeLink.onclick = function(){deleteTableRow(tblID, originRowID); deleteTableRow(tblID, tmpOriginPosRowID); deleteTableRow(tblID, divRowID); removeFormElements(myForm, elemList)};
		break;

		case 'miscellaneous':
			var propRow = propsTbl.insertRow(propList.rowIndex - 1);
		
			// Need a few cells in the row
			var propNameCell = propRow.insertCell(0);
			var propValueCell = propRow.insertCell(1);

			// Set cell content and formatting as required by template
			propNameCell.className = "detailedView_colName";
			propNameCell.innerHTML = "Miscellaneous";
		
			propValueCell.className = "detailedView_value";
			propValueCell.setAttribute("white-space", "nowrap");
			propValueCell.style.fontSize = "9pt";
			propValueCell.colSpan = 5;
			propValueCell.style.paddingLeft = "5px";
			
			// Generate the list on the fly
			var tmpMiscList = document.getElementById("miscellaneous_list");
			var miscSelectedInd = tmpMiscList.selectedIndex;
			var miscSelectedValue = tmpMiscList[miscSelectedInd].value;

			var newMiscList = document.createElement("SELECT");
			var newMiscTxt =  document.createElement("INPUT");

			newMiscList.setAttribute("name", prefix + propName + postfix);
			newMiscList.setAttribute("id", propName + "_proplist_" + propRow.rowIndex);

			propRow.setAttribute('id', "miscellaneous_row_" + propRow.rowIndex + "_id");

			newMiscTxt.type = "TEXT";
			newMiscTxt.id = "miscellaneous_txt_" + (propList.rowIndex - 1);
			newMiscTxt.style.display = "none";

			for (i = 0; i < tmpMiscList.options.length; i++)
			{
				tmpOptn = tmpMiscList.options[i];
				newOptn = document.createElement("OPTION");

				newOptn.value = tmpOptn.value;
				newOptn.name = tmpOptn.name;
				newOptn.text = tmpOptn.text;

				if (newMiscList.options.length == 0)
					newMiscList.options.add(newOptn);
				else
					addElement(newMiscList, newMiscList.options.length, newOptn);
			}

			propValueCell.appendChild(newMiscList);
			propValueCell.appendChild(newMiscTxt);

			// Start & stop positions
			// April 1, 2008: Show in a new row
			var miscPosRow = propsTbl.insertRow(propRow.rowIndex+1);
			miscPosRow.setAttribute('id', "miscellaneous_pos_row" + miscPosRow.rowIndex + "_id");
			var miscPosRowID = miscPosRow.id;

			var c1 = miscPosRow.insertCell(0);
			var miscPosCell = miscPosRow.insertCell(1);

			miscPosCell.className = "detailedView_value";
			miscPosCell.style.paddingLeft = "5px";
			miscPosCell.colSpan = 5;
			miscPosCell.setAttribute("white-space", "nowrap");

			miscPosCell.innerHTML = "Start:&nbsp;";

			var newMiscStart = document.createElement("INPUT");
			newMiscStart.setAttribute("type", 'TEXT');
			newMiscStart.setAttribute("size", 5);
			newMiscStart.setAttribute("id", propName + "_" + propRow.rowIndex + "_startpos_id");

			miscPosCell.appendChild(newMiscStart);

			miscPosCell.innerHTML += "&nbsp;&nbsp;End:&nbsp;";
			
			var newMiscEnd = document.createElement("INPUT");
			newMiscEnd.setAttribute("type", 'TEXT');
			newMiscEnd.setAttribute("size", 5);
			newMiscEnd.setAttribute("id", propName + "_" + propRow.rowIndex + "_endpos_id");

			miscPosCell.appendChild(newMiscEnd);
/*
			newMiscList.onchange = function()
			{
// 				showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);
			};*/

			// Orientation - March 14/08: Make 'forward' checked by default
			miscPosCell.innerHTML += "<SPAN style=\"margin-left:10px;\"></span>";

			var fwdDir = document.createElement("INPUT");
			fwdDir.setAttribute("type", "radio");
			fwdDir.setAttribute("id", propName + "_" + propRow.rowIndex + "_fwd_dir");
			fwdDir.setAttribute("name", propName + "_orientation_radio_" + propRow.rowIndex);
			fwdDir.setAttribute("checked", true);

			miscPosCell.appendChild(fwdDir);
			miscPosCell.innerHTML += "Forward&nbsp;";
 
			var revDir = document.createElement("INPUT");
			revDir.setAttribute("type", "radio");
			revDir.setAttribute("id", propName + "_" + propRow.rowIndex + "_rev_dir");
			revDir.setAttribute("name", propName + "_orientation_radio_" + propRow.rowIndex);
			miscPosCell.appendChild(revDir);
			miscPosCell.innerHTML += "Reverse";

			// Add 'Remove' link
			var removeLink = document.createElement("SPAN");
			removeLink.className = "linkShow";
			removeLink.style.fontSize = "9pt";
			removeLink.style.fontWeight = "normal";
			removeLink.style.marginLeft = "10px";
			removeLink.innerHTML = "Remove";
		
			miscPosCell.appendChild(removeLink);

			// Adjust border height to stretch to bottom of page
// 			mainBorder = document.getElementById("mainBorder");
// 			mainBorder.height = document.height;
// 			mainBorder.rowSpan = propsTbl.rows.length;
		
			// Add a separator row
			var divRow = propsTbl.insertRow(miscPosRow.rowIndex + 1);
			var divCell = divRow.insertCell(0);

			divRow.setAttribute('id', "div_row" + divRow.rowIndex + "_id");
			divCell.colSpan = 6;
			divCell.innerHTML = "<HR>";

			// 'Remove' link
// 			var tblID = "modifyReagentPropsTbl";
			var miscRowID = propRow.id;
			var divRowID = divRow.id;
		
			// Add list to form
			var myForm = document.reagentDetailForm;

			var hiddenMisc = document.createElement("INPUT");
			hiddenMisc.setAttribute("type", "hidden");
			myForm.appendChild(hiddenMisc);

			var hiddenMiscStart = document.createElement("INPUT");
			hiddenMiscStart.setAttribute("type", "hidden");
			hiddenMiscStart.setAttribute("id", "miscellaneous_startpos_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenMiscStart);

			var hiddenMiscEnd = document.createElement("INPUT");
			hiddenMiscEnd.setAttribute("type", "hidden");
			hiddenMiscEnd.setAttribute("id", "miscellaneous_endpos_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenMiscEnd);

			// Orientation
			var hiddenMiscDir = document.createElement("INPUT");
			hiddenMiscDir.setAttribute('type', 'hidden');
			hiddenMiscDir.setAttribute("id", "miscellaneous_orientation_prop_" + propRow.rowIndex);
			myForm.appendChild(hiddenMiscDir);

			var elemList = new Array();
			elemList.push(hiddenMisc);
			elemList.push(hiddenMiscStart);
			elemList.push(hiddenMiscEnd);
			elemList.push(hiddenMiscDir);

			removeLink.onclick = function(){deleteTableRow(tblID, miscRowID); deleteTableRow(tblID, miscPosRowID); deleteTableRow(tblID, divRowID); removeFormElements(myForm, elemList)};
		break;

		default:
			var propVals = document.getElementById(propName + "_attribute");

			if (propVals.style.display == "none")
			{
				propVals.style.display = "table-row";

				// Add a separator row
				var divRow = propsTbl.insertRow(propVals.rowIndex + 1);
				var divCell = divRow.insertCell(0);
	
				divCell.colSpan = 6;
				divCell.innerHTML = "<HR>";
			}
		break;
	}

	propNamesList.selectedIndex = 0;
}

// March 19/09: Capitalize the first letter of each word in a string
function titleCase(str)
{
	var toks = str.split(" ");
	
	var strout = "";

	for (i = 0; i < toks.length; i++)
	{
		strout += toks[i].substr(0,1).toUpperCase() + toks[i].substr(1) + " ";
	}

	return trimAll(strout);
}

// when adding new reagent type
function previewReagentTypePropFormat()
{
	var plainTextProps = ["5' linker", "3' linker", "accession number", "comments", "concentration", "description", "ensembl gene id", "ensembl peptide id", "entrez gene id", "official gene symbol", "image id", "length", "molecular weight", "name", "protein sequence", "reagent source", "sequence", "melting temperature", "verification comments"];

	var dropdownProps = ["5' cloning site", "3' cloning site", "antibiotic resistance", "cell line type", "developmentatl stage", "expression system", "frame", "cloning method", "intron", "miscellaneous", "morphology", "open/closed", "origin of replication", "project id", "polya", "promoter", "protocol", "restriction site", "selectable marker", "species", "status", "tag position", "tag", "tissue type", "transcription terminator", "type of insert", "vector type", "verification"];

	var checkboxProps = ["alternate id", "resistance marker"];

	var radioProps = ["restrictions on use"];

	var myForm = document.addReagentTypeForm;

	var propNamesList = document.getElementById("src_props_list");

	for (j = 0; j < propNamesList.options.length; j++)
	{
		var aOpt = propNamesList.options[j];

		if (aOpt.selected)
		{
// 			var selectedInd = i;
// 			var selectedInd = propNamesList.selectedIndex;
// 			var propName = propNamesList[selectedInd].value;

			var propName = aOpt.value;
		
			var propsTbl = document.getElementById("propInputFormatTable");
			
			var prefix = "reagent_detailedview_";
			var postfix = "_prop";
		
			var propRow = propsTbl.insertRow(propsTbl.rows.length);
			var propNameCell = propRow.insertCell(0);
			var propInputFormatCell = propRow.insertCell(1);
		// 	var propInputValuesCell = propRow.insertCell(2);
			var rmvCell = propRow.insertCell(2);
			
			// Format cells
			propNameCell.style.paddingLeft = "7px";
			propNameCell.style.fontSize = "9pt";
		
			rmvCell.style.textAlign = "left";
		
			// Set cell content and formatting as required by template
// 			propNameCell.innerHTML = titleCase(propName);
			propNameCell.innerHTML = aOpt.text;
		
			// Replicate template input values list and add to second cell
			var templateInputFormatList = document.getElementById("propInputFormat");
		
			var tmpInputFormatList = document.createElement("SELECT");
			tmpInputFormatList.style.fontSize = "9pt";
		
			for (i = 0; i < templateInputFormatList.options.length; i++)
			{
				tmpOptn = templateInputFormatList.options[i];
				newOptn = document.createElement("OPTION");
		
				newOptn.value = tmpOptn.value;
				newOptn.text = tmpOptn.text;
		
				if (tmpInputFormatList.options.length == 0)
					tmpInputFormatList.options.add(newOptn);
				else
					addElement(tmpInputFormatList, tmpInputFormatList.options.length, newOptn);
			}
		
			// Have to do this in a separate loop!
			for (i = 0; i < templateInputFormatList.options.length; i++)
			{
				tmpOptn = tmpInputFormatList.options[i];
			
				if ((tmpOptn.value == "plain_text") && inArray(propName.toLowerCase(), plainTextProps))
				{
					tmpOptn.selected = true;
				}
				else if ((tmpOptn.value == "dropdown") && inArray(propName.toLowerCase(), dropdownProps))
				{
					tmpOptn.selected = true;
				}
				else if ((tmpOptn.value == "radio_btn") && inArray(propName.toLowerCase(), radioProps))
				{
					tmpOptn.selected = true;
				}
				else if ((tmpOptn.value == "checkboxes") && inArray(propName.toLowerCase(), checkboxProps))
				{
					tmpOptn.selected = true;
				}
			}

			propInputFormatCell.appendChild(tmpInputFormatList);
		}
	}
}


function addFeatures(listID, form_name, featuresRowID, propsTableID)
{
	var hasDescriptor = false;
	var descriptor = "";
	var descrAlias = "";
	var descrListID = "";

	var propNamesList = document.getElementById(listID);
	var selectedInd = propNamesList.selectedIndex;
	var propName = propNamesList[selectedInd].value;

	switch (propName)
	{
		// don't do anything if 'default' is selected
		case 'default':
			return;
		break;

		case 'tag':
			hasDescriptor = true;
			descriptor = "Tag Position";
			descrAlias = "tag_position";
			descrListID = "tag_position_list";
		break;

		case 'promoter':
			hasDescriptor = true;
			descriptor = "Expression System";
			descrAlias = "expression_system";
			descrListID = "expression_system_list";
		break;
	}

	var myForm;

	// Add list to form
	if (form_name == "")
		myForm = document.reagentDetailForm;
	else
	{
		docForms = document.forms;
	
		for (i = 0; i < docForms.length; i++)
		{
			aForm = docForms[i];

			if (aForm.name == form_name)
			{
				myForm = aForm;
				break;
			}
		}
	}

	var propList = document.getElementById(featuresRowID);

	// Modified March 3/08
	var propsTbl = document.getElementById(propsTableID);
	var propRow = propsTbl.insertRow(propsTbl.rows.length);

	// Need a few cells in the row
	var propNameCell = propRow.insertCell(0);
	var propValueCell = propRow.insertCell(1);
	var propDescrCell = propRow.insertCell(2);
	var propStartCell = propRow.insertCell(3);
	var propEndCell = propRow.insertCell(4);
	var propDirCell = propRow.insertCell(5);
	var rmvCell = propRow.insertCell(6);

	// Format cells	
	propNameCell.style.paddingLeft = "7px";
	propNameCell.style.backgroundColor = "#F5F5DC";
	propNameCell.style.fontSize = "7pt";		// nov. 12/08
	propNameCell.style.whiteSpace = "nowrap";

	propValueCell.style.paddingLeft = "7px";
	propValueCell.style.paddingRight = "5px";
	propValueCell.style.paddingTop = "1px";
	propValueCell.style.paddingBottom = "1px";
	propValueCell.style.backgroundColor = "#F5F5DC";

	propDescrCell.style.paddingLeft = "7px";
	propDescrCell.style.backgroundColor = "#F5F5DC";

	propStartCell.style.textAlign = "left";
	propStartCell.style.paddingLeft = "7px";
	propStartCell.style.paddingRight = "2px";
	propStartCell.style.backgroundColor = "#F5F5DC";

	propEndCell.style.textAlign = "left";
	propEndCell.style.backgroundColor = "#F5F5DC";
	propEndCell.style.paddingLeft = "7px";
	propEndCell.style.paddingRight = "2px";

	propDirCell.style.textAlign = "left";
	propDirCell.style.paddingLeft = "5px";
	propDirCell.style.paddingRight = "5px";
	propDirCell.style.backgroundColor = "#F5F5DC";

	rmvCell.style.textAlign = "left";

	// Set cell content and formatting as required by template
	propNameCell.innerHTML = propNamesList[selectedInd].text;

	propValueCell.setAttribute("white-space", "nowrap");
	
	// Generate the list on the fly - KEEPING 'tag type' IN VARIABLE NAMES FOR ERROR SAKE (code copy-pasted, keeping variable names to avoid errors renaming)
	var tmpTagTypeList = document.getElementById(propName + "_list");

	var newTagTypeList = document.createElement("SELECT");
	var newTagTypeTxt =  document.createElement("INPUT");

	newTagTypeList.style.fontSize = "7pt";		// Nov. 12/08

	var rowIndex;

	if (!document.getElementById(propName + "_row_" + propRow.rowIndex + "_id"))
	{
		propRow.setAttribute('id', propName + "_row_" + propRow.rowIndex + "_id");
		rowIndex = propRow.rowIndex;
	}
	else
	{
		for (i = 0; i < propsTbl.rows.length; i++)
		{
			if (!document.getElementById(propName + "_row_" + i + "_id"))
			{
				propRow.setAttribute('id', propName + "_row_" + i + "_id");
				break;
			}
		}

		propRow.setAttribute('id', propName + "_row_" + i + "_id");
		rowIndex = i;
	}

	newTagTypeList.setAttribute('id', propName + "_proplist_" + rowIndex);

	newTagTypeTxt.type = "TEXT";
	newTagTypeTxt.id = propName + "_txt_" + rowIndex;

	newTagTypeTxt.style.display = "none";
	newTagTypeTxt.style.fontSize = "9pt";

	for (i = 0; i < tmpTagTypeList.options.length; i++)
	{
		tmpOptn = tmpTagTypeList.options[i];
		newOptn = document.createElement("OPTION");

		newOptn.value = tmpOptn.value;
		newOptn.name = tmpOptn.name;
		newOptn.text = tmpOptn.text;

		if (newTagTypeList.options.length == 0)
			newTagTypeList.options.add(newOptn);
		else
			addElement(newTagTypeList, newTagTypeList.options.length, newOptn);
	}

	newTagTypeList.onchange = function(){this.setAttribute('name', propName + "_" + this[this.selectedIndex].value + "_" + rowIndex); showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);};

	propValueCell.appendChild(newTagTypeList);
	propValueCell.appendChild(newTagTypeTxt);

	// Generate descriptor lists if applicable
	if (hasDescriptor)
	{
// alert(descrListID);
		var oldTagPosList = document.getElementById(descrListID);
		var newDescriptorList = document.createElement("SELECT");
	
		newDescriptorList.setAttribute('id', descrAlias + "_proplist_" + rowIndex);
	
		newDescriptorList.style.fontSize = "7pt";		// Nov. 12/08
	
		for (i = 0; i < oldTagPosList.options.length; i++)
		{
			tmpOptn = oldTagPosList.options[i];
			newOptn = document.createElement("OPTION");
	
			newOptn.value = tmpOptn.value;
			newOptn.name = tmpOptn.name;
			newOptn.text = tmpOptn.text;
	
			if (newDescriptorList.options.length == 0)
				newDescriptorList.options.add(newOptn);
			else
				addElement(newDescriptorList, newDescriptorList.options.length, newOptn);
		}
	
		propDescrCell.appendChild(newDescriptorList);
	}

	var newTagTypeStart = document.createElement("INPUT");
	newTagTypeStart.setAttribute("type", 'TEXT');
	newTagTypeStart.setAttribute("size", 5);
	newTagTypeStart.setAttribute("id", propName + "_" + rowIndex + "_startpos_id");
	newTagTypeStart.style.fontSize = "7pt";		// Nov. 12/08

	propStartCell.appendChild(newTagTypeStart);

	var newTagTypeEnd = document.createElement("INPUT");
	newTagTypeEnd.setAttribute("type", 'TEXT');
	newTagTypeEnd.setAttribute("size", 5);
	newTagTypeEnd.setAttribute("id", propName + "_" + rowIndex + "_endpos_id");
	newTagTypeEnd.style.fontSize = "7pt";		// Nov. 12/08

	propEndCell.appendChild(newTagTypeEnd);

	// Orientation
	propDirCell.style.fontSize = "7pt";		// Nov. 12/08
	propDirCell.style.whiteSpace = "nowrap";
	
	var fwdDir = document.createElement("INPUT");
	fwdDir.setAttribute("type", "radio");
	fwdDir.setAttribute("id", propName + "_" + rowIndex + "_fwd_dir");
	fwdDir.setAttribute("name", propName + "_orientation_prop_" + rowIndex);

	fwdDir.setAttribute("checked", true);

	propDirCell.appendChild(fwdDir);
	propDirCell.innerHTML += "Forward&nbsp;";

	var revDir = document.createElement("INPUT");
	revDir.setAttribute("type", "radio");
	revDir.setAttribute("id", propName + "_" + rowIndex + "_rev_dir");
	revDir.setAttribute("name", propName + "_orientation_prop_" + rowIndex);

	propDirCell.appendChild(revDir);
	propDirCell.innerHTML += "Reverse";

	// Add 'Remove' link
	var removeLink = document.createElement("SPAN");
	removeLink.className = "linkShow";
	removeLink.style.fontSize = "7pt";
	removeLink.style.fontWeight = "normal";
	removeLink.style.marginLeft = "5px";
	removeLink.innerHTML = "Remove";

	rmvCell.appendChild(removeLink);

	// Gather the IDs of all elements in the row to pass to 'deleteRow' function
	var tagTypeRowID = propRow.id;

	var hiddenTagType = document.createElement("INPUT");
	hiddenTagType.setAttribute("type", "hidden");
	hiddenTagType.setAttribute("id", propName + "_prop" + rowIndex);
	myForm.appendChild(hiddenTagType);

	var hiddenTagTypeStart = document.createElement("INPUT");
	hiddenTagTypeStart.setAttribute("type", "hidden");
	hiddenTagTypeStart.setAttribute("id", propName + "_startpos_prop_" + rowIndex);
	myForm.appendChild(hiddenTagTypeStart);

	var hiddenTagTypeEnd = document.createElement("INPUT");
	hiddenTagTypeEnd.setAttribute("type", "hidden");
	hiddenTagTypeEnd.setAttribute("id", propName + "_endpos_prop_" + rowIndex);
	myForm.appendChild(hiddenTagTypeEnd);

	// Tag position
	var hiddenDescriptorField = document.createElement("INPUT");
	hiddenDescriptorField.setAttribute('type', 'hidden');
	hiddenDescriptorField.setAttribute("id", descrAlias + "_prop_" + rowIndex);
	myForm.appendChild(hiddenDescriptorField);

	// Orientation
	var hiddenTagTypeDir = document.createElement("INPUT");
	hiddenTagTypeDir.setAttribute('type', 'hidden');
	hiddenTagTypeDir.setAttribute("id", propName + "_orientation_prop_" + rowIndex);
	myForm.appendChild(hiddenTagTypeDir);

	var elemList = new Array();
	elemList.push(hiddenTagType);
	elemList.push(hiddenTagTypeStart);
	elemList.push(hiddenTagTypeEnd);
	elemList.push(hiddenTagTypeDir);

	removeLink.onclick = function(){deleteTableRow(propsTableID, tagTypeRowID); removeFormElements(myForm, elemList)};

	propNamesList.selectedIndex = 0;
}


// August 5/08: Identical to showPropertyValues, only used at reagent creation (different format)
function showPropertyValues2(listID, form_name, tblID, rType, createNew)
{
	var myForm;

	// Add list to form
	if (form_name == "")
		myForm = document.reagentDetailForm;
	else
	{
		docForms = document.forms;
	
		for (i = 0; i < docForms.length; i++)
		{
			aForm = docForms[i];

			if (aForm.name == form_name)
			{
				myForm = aForm;
				break;
			}
		}
	}

// 	alert(myForm);

	if (!tblID)
		tblID = "modifyReagentPropsTbl";

	var propNamesList = document.getElementById(listID);
	var selectedInd = propNamesList.selectedIndex;
	var propName = propNamesList[selectedInd].value;
// alert(propName);

// alert(tblID);

	var propList = document.getElementById("addlPropsListRow_" + rType);
	
	// Modified March 3/08
// 	var propsTbl = document.getElementById("modifyReagentPropsTbl");
	var propsTbl = document.getElementById(tblID);

// alert(propList.rowIndex);
	
	var prefix = "reagent_detailedview_";
	var postfix = "_prop";

	// March 3/08: Since there could be multiple property values (e.g. tag type or expression system), instead of unhiding the corresponding property row, append another row to the table and make it identical to the hidden row (so the hidden row serves as a template)
	switch (propName)
	{
// 		case 'tag_type':
// 			var propRow = propsTbl.insertRow(propList.rowIndex - 2);
// 
// 			// Need a few cells in the row
// 			var propNameCell = propRow.insertCell(0);
// 			var propValueCell = propRow.insertCell(1);
// 			var propDescrCell = propRow.insertCell(2);
// 			var propStartCell = propRow.insertCell(3);
// 			var propEndCell = propRow.insertCell(4);
// 			var propDirCell = propRow.insertCell(5);
// 			var rmvCell = propRow.insertCell(6);
// 		
// 			// Format cells	
// 			propNameCell.style.paddingLeft = "7px";
// 			propNameCell.style.backgroundColor = "#F5F5DC";
// 			propNameCell.style.fontSize = "7pt";		// nov. 12/08
// 		
// 			propValueCell.style.paddingLeft = "7px";
// 			propValueCell.style.paddingRight = "5px";
// 			propValueCell.style.paddingTop = "1px";
// 			propValueCell.style.paddingBottom = "1px";
// 			propValueCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDescrCell.style.paddingLeft = "7px";
// 			propDescrCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propStartCell.style.textAlign = "left";
// 			propStartCell.style.paddingLeft = "7px";
// 			propStartCell.style.paddingRight = "2px";
// 			propStartCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propEndCell.style.textAlign = "left";
// 			propEndCell.style.backgroundColor = "#F5F5DC";
// 			propEndCell.style.paddingLeft = "7px";
// 			propEndCell.style.paddingRight = "2px";
// 
// 			propDirCell.style.textAlign = "left";
// 			propDirCell.style.paddingLeft = "5px";
// 			propDirCell.style.paddingRight = "5px";
// 			propDirCell.style.backgroundColor = "#F5F5DC";
// 		
// 			rmvCell.style.textAlign = "left";
// 
// 			// Set cell content and formatting as required by template	
// 			propNameCell.innerHTML = "Tag";
// 			propValueCell.setAttribute("white-space", "nowrap");
// 			
// 			// Generate the list on the fly
// 			if (createNew == "true")
// 				propNamePrefix = rType + "_"
// 			else
// 				propNamePrefix = ""
// 
// 			var tmpTagTypeList = document.getElementById(propNamePrefix + "tag_type_list");
// 
// 			var newTagTypeList = document.createElement("SELECT");
// 			var newTagTypeTxt =  document.createElement("INPUT");
// 
// 			newTagTypeList.style.fontSize = "7pt";		// Nov. 12/08
// 
// 			var rowIndex;
// 
// 			if (!document.getElementById(propNamePrefix + "tag_type_row_" + propRow.rowIndex + "_id"))
// 			{
// 				propRow.setAttribute('id', propNamePrefix + "tag_type_row_" + propRow.rowIndex + "_id");
// 				rowIndex = propRow.rowIndex;
// 			}
// 			else
// 			{
// 				for (i = 0; i < propsTbl.rows.length; i++)
// 				{
// 					if (!document.getElementById(propNamePrefix + "tag_type_row_" + i + "_id"))
// 					{
// 						propRow.setAttribute('id', propNamePrefix + "tag_type_row_" + i + "_id");
// 						break;
// 					}
// 				}
// 
// 				propRow.setAttribute('id', propNamePrefix + "tag_type_row_" + i + "_id");
// 				rowIndex = i;
// 			}
// 
// // 			newTagTypeList.setAttribute('name', prefix + propName + postfix);
// 			newTagTypeList.setAttribute('id', propNamePrefix + propName + "_proplist_" + rowIndex);
// 
// 			newTagTypeTxt.type = "TEXT";
// 			newTagTypeTxt.id = propNamePrefix + "tag_type_txt_" + rowIndex;
// 
// 			newTagTypeTxt.style.display = "none";
// 			newTagTypeTxt.style.fontSize = "9pt";
// 
// 			for (i = 0; i < tmpTagTypeList.options.length; i++)
// 			{
// 				tmpOptn = tmpTagTypeList.options[i];
// 				newOptn = document.createElement("OPTION");
// 
// 				newOptn.value = tmpOptn.value;
// 				newOptn.name = tmpOptn.name;
// 				newOptn.text = tmpOptn.text;
// 
// 				if (newTagTypeList.options.length == 0)
// 					newTagTypeList.options.add(newOptn);
// 				else
// 					addElement(newTagTypeList, newTagTypeList.options.length, newOptn);
// 			}
// 
// // 			newTagTypeList.onchange = showTagTypeBox;
// 
// 			newTagTypeList.onchange = function(){this.setAttribute('name', propNamePrefix + propName + "_" + this[this.selectedIndex].value + "_" + rowIndex); showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);};
// 
// 			propValueCell.appendChild(newTagTypeList);
// 			propValueCell.appendChild(newTagTypeTxt);
// 
// 			// March 17/08: Generate Tag Position option list
// 			var oldTagPosList = document.getElementById(propNamePrefix + "tag_position_list");
// // alert(oldTagPosList);
// 			if (oldTagPosList)
// 			{
// 				var newTagPosList = document.createElement("SELECT");
// 	
// 				newTagPosList.setAttribute('id', propNamePrefix + "tag_position_proplist_" + rowIndex);
// // 	alert(newTagPosList.id);
// 				newTagPosList.style.fontSize = "7pt";		// Nov. 12/08
// 	
// 				for (i = 0; i < oldTagPosList.options.length; i++)
// 				{
// 					tmpOptn = oldTagPosList.options[i];
// 					newOptn = document.createElement("OPTION");
// 	
// 					newOptn.value = tmpOptn.value;
// 					newOptn.name = tmpOptn.name;
// 					newOptn.text = tmpOptn.text;
// 	
// 					if (newTagPosList.options.length == 0)
// 						newTagPosList.options.add(newOptn);
// 					else
// 						addElement(newTagPosList, newTagPosList.options.length, newOptn);
// 				}
// 	
// 				propDescrCell.appendChild(newTagPosList);
// 			}
// 
// 			var newTagTypeStart = document.createElement("INPUT");
// 			newTagTypeStart.setAttribute("type", 'TEXT');
// 			newTagTypeStart.setAttribute("size", 5);
// 			newTagTypeStart.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_startpos_id");
// 			newTagTypeStart.style.fontSize = "7pt";		// Nov. 12/08
// 
// 			propStartCell.appendChild(newTagTypeStart);
// 
// 			var newTagTypeEnd = document.createElement("INPUT");
// 			newTagTypeEnd.setAttribute("type", 'TEXT');
// 			newTagTypeEnd.setAttribute("size", 5);
// 			newTagTypeEnd.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_endpos_id");
// 			newTagTypeEnd.style.fontSize = "7pt";		// Nov. 12/08
// 
// 			propEndCell.appendChild(newTagTypeEnd);
// 
// 			// Orientation
// 			propDirCell.style.fontSize = "7pt";		// Nov. 12/08
// 			propDirCell.style.whiteSpace = "nowrap";
// 			
// 			var fwdDir = document.createElement("INPUT");
// 			fwdDir.setAttribute("type", "radio");
// 			fwdDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_fwd_dir");
// //  			fwdDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			fwdDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// 			fwdDir.setAttribute("checked", true);
// 
// 			propDirCell.appendChild(fwdDir);
// 			propDirCell.innerHTML += "Forward&nbsp;";
// 
// 			var revDir = document.createElement("INPUT");
// 			revDir.setAttribute("type", "radio");
// 			revDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_rev_dir");
// // 			revDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
//  			revDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// 			propDirCell.appendChild(revDir);
// 			propDirCell.innerHTML += "Reverse";
// 
// 			// Add 'Remove' link
// 			var removeLink = document.createElement("SPAN");
// 			removeLink.className = "linkShow";
// 			removeLink.style.fontSize = "7pt";
// 			removeLink.style.fontWeight = "normal";
// 			removeLink.style.marginLeft = "5px";
// 			removeLink.innerHTML = "Remove";
// 
// 			rmvCell.appendChild(removeLink);
// 
// 			// Gather the IDs of all elements in the row to pass to 'deleteRow' function
// // 			var tblID = "modifyReagentPropsTbl";
// 			var tagTypeRowID = propRow.id;
// 
// 			var hiddenTagType = document.createElement("INPUT");
// 			hiddenTagType.setAttribute("type", "hidden");
// 			hiddenTagType.setAttribute("id", propNamePrefix + "tag_type_prop" + rowIndex);
// 			myForm.appendChild(hiddenTagType);
// 
// 			var hiddenTagTypeStart = document.createElement("INPUT");
// 			hiddenTagTypeStart.setAttribute("type", "hidden");
// 			hiddenTagTypeStart.setAttribute("id", propNamePrefix + "tag_type_startpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenTagTypeStart);
// 
// 			var hiddenTagTypeEnd = document.createElement("INPUT");
// 			hiddenTagTypeEnd.setAttribute("type", "hidden");
// 			hiddenTagTypeEnd.setAttribute("id", propNamePrefix + "tag_type_endpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenTagTypeEnd);
// 
// 			// Tag position
// 			var hiddenTagPosField = document.createElement("INPUT");
// 			hiddenTagPosField.setAttribute('type', 'hidden');
// 			hiddenTagPosField.setAttribute("id", propNamePrefix + "tag_position_prop_" + rowIndex);
// // alert(hiddenTagPosField.id);
// 			myForm.appendChild(hiddenTagPosField);
// 
// 			// Orientation
// 			var hiddenTagTypeDir = document.createElement("INPUT");
// 			hiddenTagTypeDir.setAttribute('type', 'hidden');
// 			hiddenTagTypeDir.setAttribute("id", propNamePrefix + "tag_type_orientation_prop_" + rowIndex);
// 			myForm.appendChild(hiddenTagTypeDir);
// 
// 			var elemList = new Array();
// 			elemList.push(hiddenTagType);
// 			elemList.push(hiddenTagTypeStart);
// 			elemList.push(hiddenTagTypeEnd);
// 			elemList.push(hiddenTagTypeDir);
// 
// 			removeLink.onclick = function(){deleteTableRow(tblID, tagTypeRowID); removeFormElements(myForm, elemList)};
// 
// 		break;
// 
// 		case 'promoter':
// 			var propRow = propsTbl.insertRow(propList.rowIndex - 2);
// 		
// 			// Need a few cells in the row
// 			var propNameCell = propRow.insertCell(0);
// 			var propValueCell = propRow.insertCell(1);
// 			var propDescrCell = propRow.insertCell(2);
// 			var propStartCell = propRow.insertCell(3);
// 			var propEndCell = propRow.insertCell(4);
// 			var propDirCell = propRow.insertCell(5);
// 			var rmvCell = propRow.insertCell(6);
// 		
// 			// Format cells
// 			propNameCell.style.paddingLeft = "7px";
// 			propNameCell.style.backgroundColor = "#F5F5DC";
// 			propNameCell.style.fontSize = "7pt";		// nov. 12/08
// 		
// 			propValueCell.style.paddingLeft = "7px";
// 			propValueCell.style.paddingRight = "5px";
// 			propValueCell.style.paddingTop = "1px";
// 			propValueCell.style.paddingBottom = "1px";
// 			propValueCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDescrCell.style.paddingLeft = "7px";
// 			propDescrCell.style.backgroundColor = "#F5F5DC";
// 			propDescrCell.style.fontSize = "7pt";
// 		
// 			propStartCell.style.textAlign = "left";
// 
// 			propStartCell.style.paddingLeft = "7px";
// 			propStartCell.style.paddingRight = "2px";
// 
// 			propStartCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propEndCell.style.textAlign = "left";
// 
// 			propEndCell.style.paddingLeft = "7px";
// 			propEndCell.style.paddingRight = "2px";
// 
// 			propEndCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDirCell.style.textAlign = "left";
// 
// 			propDirCell.style.paddingLeft = "5px";
// 			propDirCell.style.paddingRight = "5px";
// 
// 			propDirCell.style.backgroundColor = "#F5F5DC";
// 		
// 			rmvCell.style.textAlign = "left";
// 			propNameCell.innerHTML = "Promoter";
// 
// 			// Generate the list on the fly
// 			if (createNew == "true")
// 				propNamePrefix = rType + "_"
// 			else
// 				propNamePrefix = ""
// 
// 			var tmpPromoterList = document.getElementById(propNamePrefix + "promoter_list");
// 			var newPromoterList = document.createElement("SELECT");
// 			var newPromoterTxt =  document.createElement("INPUT");
// 			var newExpSysTxt =  document.createElement("INPUT");
// 
// 			var rowIndex;
// 
// 			if (!document.getElementById(propNamePrefix + "promoter_row_" + propRow.rowIndex + "_id"))
// 			{
// 				propRow.setAttribute('id', propNamePrefix + "promoter_row_" + propRow.rowIndex + "_id");
// 				rowIndex = propRow.rowIndex;
// 			}
// 			else
// 			{
// 				for (i = 0; i < propsTbl.rows.length; i++)
// 				{
// 					if (!document.getElementById(propNamePrefix + "promoter_row_" + i + "_id"))
// 					{
// 						propRow.setAttribute('id', propNamePrefix + "promoter_row_" + i + "_id");
// 						break;
// 					}
// 				}
// 
// 				propRow.setAttribute('id', propNamePrefix + "promoter_row_" + i + "_id");
// 				rowIndex = i;
// 			}
// 
// 			newPromoterList.setAttribute('id', propNamePrefix + propName + "_proplist_" + rowIndex);
// 			newPromoterList.style.fontSize = "7pt";		// Nov. 12/08
// 
// 			newPromoterTxt.type = "TEXT";
// 			newPromoterTxt.id = propNamePrefix + "promoter_txt_" + rowIndex;
// 			newPromoterTxt.style.display = "none";
// 
// 			newExpSysTxt.type = "TEXT";
// 			newExpSysTxt.id = propNamePrefix + "expression_system_txt_" + rowIndex;
// 			newExpSysTxt.style.display = "none";
// 
// 			for (i = 0; i < tmpPromoterList.options.length; i++)
// 			{
// 				tmpOptn = tmpPromoterList.options[i];
// 				newOptn = document.createElement("OPTION");
// 
// 				newOptn.value = tmpOptn.value;
// 				newOptn.name = tmpOptn.name;
// 				newOptn.text = tmpOptn.text;
// 
// 				if (newPromoterList.options.length == 0)
// 					newPromoterList.options.add(newOptn);
// 				else
// 					addElement(newPromoterList, newPromoterList.options.length, newOptn);
// 			}
// 
// 			propValueCell.appendChild(newPromoterList);
// 			propValueCell.appendChild(newPromoterTxt);
// 
// 			var oldExpSystList = document.getElementById(propNamePrefix + "expression_system_list");
// 			var newExpSystList = document.createElement("SELECT");
// 
// 			newExpSystList.setAttribute('id', propNamePrefix + "expression_system_prop_list_" + rowIndex);
// 			newExpSystList.style.fontSize = "7pt";		// Nov. 12/08
// 
// 			for (i = 0; i < oldExpSystList.options.length; i++)
// 			{
// 				tmpOptn = oldExpSystList.options[i];
// 				newOptn = document.createElement("OPTION");
// 
// 				newOptn.value = tmpOptn.value;
// 				newOptn.name = tmpOptn.name;
// 				newOptn.text = tmpOptn.text;
// 
// 				if (newExpSystList.options.length == 0)
// 					newExpSystList.options.add(newOptn);
// 				else
// 					addElement(newExpSystList, newExpSystList.options.length, newOptn);
// 			}
// 
// 			propDescrCell.appendChild(newExpSystList);
// 			propDescrCell.appendChild(newExpSysTxt);
// 
// 			var newPromStart = document.createElement("INPUT");
// 			newPromStart.setAttribute("type", 'TEXT');
// 			newPromStart.setAttribute("size", 5);
// 			newPromStart.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_startpos_id");
// 			newPromStart.style.fontSize = "7pt";		// Nov. 12/08
// 
// 			propStartCell.appendChild(newPromStart);
// 			
// 			var newPromEnd = document.createElement("INPUT");
// 			newPromEnd.setAttribute("type", 'TEXT');
// 			newPromEnd.setAttribute("size", 5);
// 			newPromEnd.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_endpos_id");
// 			newPromEnd.style.fontSize = "7pt";		// Nov. 12/08
// 
// 			propEndCell.appendChild(newPromEnd);
// 
// 			newPromoterList.onchange = function(){this.setAttribute('name', propNamePrefix + propName + "_" + this[this.selectedIndex].value + "_" + rowIndex); showSpecificOtherTextbox(newPromoterList.id, newPromoterTxt.id);};
// 
// 			newExpSystList.onchange = function(){showSpecificOtherTextbox(newExpSystList.id, newExpSysTxt.id);};
// 
// 			// Orientation - March 14/08: Make 'forward' checked by default
// 			var fwdDir = document.createElement("INPUT");
// 			fwdDir.setAttribute("type", "radio");
// 			fwdDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_fwd_dir");
// // 			fwdDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			fwdDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 			fwdDir.setAttribute("checked", true);
// 
// 			propDirCell.appendChild(fwdDir);
// 			propDirCell.innerHTML += "Forward&nbsp;";
// 			propDirCell.style.fontSize = "7pt";		// Nov. 12/08
// 			propDirCell.style.whiteSpace = "nowrap";
// 
// 			var revDir = document.createElement("INPUT");
// 			revDir.setAttribute("type", "radio");
// 			revDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_rev_dir");
// // 			revDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			revDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// 			propDirCell.appendChild(revDir);
// 			propDirCell.innerHTML += "Reverse";
// 
// 			// Add 'Remove' link
// 			var removeLink = document.createElement("SPAN");
// 			removeLink.className = "linkShow";
// 			removeLink.style.fontSize = "7pt";
// 			removeLink.style.fontWeight = "normal";
// 			removeLink.style.marginLeft = "5px";
// 			removeLink.innerHTML = "Remove";
// 		
// 			rmvCell.appendChild(removeLink);
// 
// 			// Gather the IDs of all elements in the row to pass to 'deleteRow' function
// // 			var tblID = "modifyReagentPropsTbl";
// 			var promRowID = propRow.id;
// 		
// // 			// Add list to form
// // 			if (form_name == "")
// // 				var myForm = document.reagentDetailForm;
// // 			else
// // 			{
// // 				var myForm;
// // 			
// // 				docForms = document.forms;
// // 			
// // 				for (i = 0; i < docForms.length; i++)
// // 				{
// // 					aForm = docForms[i];
// // 			
// // 					if (aForm.name == form_name)
// // 					{
// // 						myForm = aForm;
// // 						break;
// // 					}
// // 				}
// // 			}
// 
// 			var hiddenPromoter = document.createElement("INPUT");
// 			hiddenPromoter.setAttribute("type", "hidden");
// 			myForm.appendChild(hiddenPromoter);
// 
// 			var hiddenPromStart = document.createElement("INPUT");
// 			hiddenPromStart.setAttribute("type", "hidden");
// 			hiddenPromStart.setAttribute("id", propNamePrefix + "promoter_startpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenPromStart);
// 
// 			var hiddenPromEnd = document.createElement("INPUT");
// 			hiddenPromEnd.setAttribute("type", "hidden");
// 			hiddenPromEnd.setAttribute("id", propNamePrefix + "promoter_endpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenPromEnd);
// 
// 			var hiddenExprSyst = document.createElement("INPUT");
// 			hiddenExprSyst.setAttribute("type", "hidden");
// 			hiddenExprSyst.setAttribute("id", propNamePrefix + "expression_system_prop_" + rowIndex);
// 			myForm.appendChild(hiddenExprSyst);
// 
// 			// Orientation
// 			var hiddenPromoDir = document.createElement("INPUT");
// 			hiddenPromoDir.setAttribute('type', 'hidden');
// 			hiddenPromoDir.setAttribute("id", propNamePrefix + "promoter_orientation_prop_" + rowIndex);
// 			myForm.appendChild(hiddenPromoDir);
// 
// 			var elemList = new Array();
// 			elemList.push(hiddenPromoter);
// 			elemList.push(hiddenPromStart);
// 			elemList.push(hiddenPromEnd);
// 			elemList.push(hiddenExprSyst);
// 			elemList.push(hiddenPromoDir);
// 
// 			removeLink.onclick = function(){deleteTableRow(tblID, promRowID); removeFormElements(myForm, elemList)};
// 
// 		break;
//  
// 		case 'selectable_marker':
// 			var propRow = propsTbl.insertRow(propList.rowIndex - 2);
// 		
// 			// Need a few cells in the row
// 			var propNameCell = propRow.insertCell(0);
// 			var propValueCell = propRow.insertCell(1);
// 			var propDescrCell = propRow.insertCell(2);
// 			var propStartCell = propRow.insertCell(3);
// 			var propEndCell = propRow.insertCell(4);
// 			var propDirCell = propRow.insertCell(5);
// 			var rmvCell = propRow.insertCell(6);
// 		
// 			// Format cells	
// 			propNameCell.style.paddingLeft = "7px";
// 			propNameCell.style.backgroundColor = "#F5F5DC";
// 			propNameCell.style.fontSize = "7pt";
// 		
// 			propValueCell.style.paddingLeft = "7px";
// 			propValueCell.style.paddingRight = "5px";
// 			propValueCell.style.paddingTop = "1px";
// 			propValueCell.style.paddingBottom = "1px";
// 			propValueCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDescrCell.style.paddingLeft = "7px";
// 			propDescrCell.style.backgroundColor = "#F5F5DC";
// 			propDescrCell.style.fontSize = "7pt";
// 		
// 			propStartCell.style.textAlign = "left";
// 			propStartCell.style.backgroundColor = "#F5F5DC";
// 
// 			propStartCell.style.paddingLeft = "7px";
// 			propStartCell.style.paddingRight = "2px";
// 		
// 			propEndCell.style.textAlign = "left";
// 
// 			propEndCell.style.paddingLeft = "7px";
// 			propEndCell.style.paddingRight = "2px";
// 
// 			propEndCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDirCell.style.textAlign = "left";
// 			propDirCell.style.backgroundColor = "#F5F5DC";
// 
// 			propDirCell.style.paddingLeft = "5px";
// 			propDirCell.style.paddingRight = "5px";
// 		
// 			rmvCell.style.textAlign = "left";
// 			propNameCell.innerHTML = "Selectable Marker";
// 
// 			// Generate the list on the fly
// 			if (createNew == "true")
// 				propNamePrefix = rType + "_"
// 			else
// 				propNamePrefix = ""
// 
// // alert(createNew);
// // alert(propNamePrefix);
// // alert(propNamePrefix + "selectable_marker_list")
// 
// 			var tmpMarkerList = document.getElementById(propNamePrefix + "selectable_marker_list");
// 			var markerSelectedInd = tmpMarkerList.selectedIndex;
// 			var markerSelectedValue = tmpMarkerList[markerSelectedInd].value;
// 
// 			var newMarkerList = document.createElement("SELECT");
// 			var newMarkerTxt =  document.createElement("INPUT");
// 
// 			newMarkerList.style.fontSize = "7pt";
// 
// // oct 27/08		newMarkerList.setAttribute("name", prefix + propName + postfix);
// 
// 			var rowIndex;
// 
// 			if (!document.getElementById(propNamePrefix + "selectable_marker_row_" + propRow.rowIndex + "_id"))
// 			{
// 				propRow.setAttribute('id', propNamePrefix + "selectable_marker_row_" + propRow.rowIndex + "_id");
// 				rowIndex = propRow.rowIndex;
// 			}
// 			else
// 			{
// 				for (i = 0; i < propsTbl.rows.length; i++)
// 				{
// 					if (!document.getElementById(propNamePrefix + "selectable_marker_row_" + i + "_id"))
// 					{
// 						propRow.setAttribute('id', propNamePrefix + "selectable_marker_row_" + i + "_id");
// 						break;
// 					}
// 				}
// 
// 				propRow.setAttribute('id', propNamePrefix + "selectable_marker_row_" + i + "_id");
// 				rowIndex = i;
// 			}
// 
// 			newMarkerList.setAttribute("id", propNamePrefix + propName + "_proplist_" + rowIndex);
// 
// 			newMarkerTxt.type = "TEXT";
// 			newMarkerTxt.id = propNamePrefix + "selectable_marker_txt_" + rowIndex;
// 			newMarkerTxt.style.display = "none";
// 
// 			newMarkerList.onchange = function(){this.setAttribute('name', propNamePrefix + propName + "_" + this[this.selectedIndex].value + "_" + rowIndex); showSpecificOtherTextbox(newMarkerList.id, newMarkerTxt.id);};	// oct 27/08
// 
// 			for (i = 0; i < tmpMarkerList.options.length; i++)
// 			{
// 				tmpOptn = tmpMarkerList.options[i];
// 				newOptn = document.createElement("OPTION");
// 
// 				newOptn.value = tmpOptn.value;
// 				newOptn.name = tmpOptn.name;
// 				newOptn.text = tmpOptn.text;
// 
// 				if (newMarkerList.options.length == 0)
// 					newMarkerList.options.add(newOptn);
// 				else
// 					addElement(newMarkerList, newMarkerList.options.length, newOptn);
// 			}
// 
// 			propValueCell.appendChild(newMarkerList);
// 			propValueCell.appendChild(newMarkerTxt);
// 
// 			var newMarkerStart = document.createElement("INPUT");
// 			newMarkerStart.setAttribute("type", 'TEXT');
// 			newMarkerStart.setAttribute("size", 5);
// 			newMarkerStart.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_startpos_id");
// 
// 			newMarkerStart.style.fontSize = "7pt";
// 			propStartCell.appendChild(newMarkerStart);
// 			
// 			var newMarkerEnd = document.createElement("INPUT");
// 			newMarkerEnd.setAttribute("type", 'TEXT');
// 			newMarkerEnd.setAttribute("size", 5);
// 			newMarkerEnd.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_endpos_id");
// 			newMarkerEnd.style.fontSize = "7pt";
// 			propEndCell.appendChild(newMarkerEnd);
// 
// // 			newMarkerList.onchange = function()
// // 			{
// // 				showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);
// // 			};
// 
// 			// Orientation - March 14/08: Make 'forward' checked by default
// 			var fwdDir = document.createElement("INPUT");
// 			fwdDir.setAttribute("type", "radio");
// 			fwdDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_fwd_dir");
// // 			fwdDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			fwdDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 			fwdDir.setAttribute("checked", true);
// 
// 			propDirCell.appendChild(fwdDir);
// 			propDirCell.innerHTML += "Forward&nbsp;";
// 			propDirCell.style.fontSize = "7pt";
// 			propDirCell.style.whiteSpace = "nowrap";
// 
// 			var revDir = document.createElement("INPUT");
// 			revDir.setAttribute("type", "radio");
// 			revDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_rev_dir");
// // 			revDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			revDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// 			propDirCell.appendChild(revDir);
// 			propDirCell.innerHTML += "Reverse";
// 
// 			// Add 'Remove' link
// 			var removeLink = document.createElement("SPAN");
// 			removeLink.className = "linkShow";
// 			removeLink.style.fontSize = "7pt";
// 			removeLink.style.fontWeight = "normal";
// 			removeLink.style.marginLeft = "5px";
// 			removeLink.innerHTML = "Remove";
// 		
// 			rmvCell.appendChild(removeLink);
// 		
// // 			// Add list to form
// // 			if (form_name == "")
// // 				var myForm = document.reagentDetailForm;
// // 			else
// // 			{
// // 				var myForm;
// // 			
// // 				docForms = document.forms;
// // 			
// // 				for (i = 0; i < docForms.length; i++)
// // 				{
// // 					aForm = docForms[i];
// // 			
// // 					if (aForm.name == form_name)
// // 					{
// // 						myForm = aForm;
// // 						break;
// // 					}
// // 				}
// // 			}
// 
// // 			var tblID = "modifyReagentPropsTbl";
// 			var markerRowID = propRow.id;
// 
// 			var hiddenMarker = document.createElement("INPUT");
// 			hiddenMarker.setAttribute("type", "hidden");
// 			myForm.appendChild(hiddenMarker);
// 
// 			var hiddenMarkerStart = document.createElement("INPUT");
// 			hiddenMarkerStart.setAttribute("type", "hidden");
// 			hiddenMarkerStart.setAttribute("id", propNamePrefix + "selectable_marker_startpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMarkerStart);
// 
// 			var hiddenMarkerEnd = document.createElement("INPUT");
// 			hiddenMarkerEnd.setAttribute("type", "hidden");
// 			hiddenMarkerEnd.setAttribute("id", propNamePrefix + "selectable_marker_endpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMarkerEnd);
// 
// 			// Orientation
// 			var hiddenMarkerDir = document.createElement("INPUT");
// 			hiddenMarkerDir.setAttribute('type', 'hidden');
// 			hiddenMarkerDir.setAttribute("id", propNamePrefix + "selectable_marker_orientation_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMarkerDir);
// 
// 			var elemList = new Array();
// 			elemList.push(hiddenMarker);
// 			elemList.push(hiddenMarkerStart);
// 			elemList.push(hiddenMarkerEnd);
// 			elemList.push(hiddenMarkerDir);
// 
// 			removeLink.onclick = function(){deleteTableRow(tblID, markerRowID); removeFormElements(myForm, elemList)};
// 		break;
// 
// 		case 'polya':
// 		case 'polyA':
// 			var propRow = propsTbl.insertRow(propList.rowIndex - 2);
// 		
// 			// Need a few cells in the row
// 			var propNameCell = propRow.insertCell(0);
// 			var propValueCell = propRow.insertCell(1);
// 			var propDescrCell = propRow.insertCell(2);
// 			var propStartCell = propRow.insertCell(3);
// 			var propEndCell = propRow.insertCell(4);
// 			var propDirCell = propRow.insertCell(5);
// 			var rmvCell = propRow.insertCell(6);
// 		
// 			// Format cells	
// 			propNameCell.style.paddingLeft = "7px";
// 			propNameCell.style.backgroundColor = "#F5F5DC";
// 			propNameCell.style.fontSize = "7pt";
// 		
// 			propValueCell.style.paddingLeft = "7px";
// 			propValueCell.style.paddingRight = "5px";
// 			propValueCell.style.paddingTop = "1px";
// 			propValueCell.style.paddingBottom = "1px";
// 			propValueCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDescrCell.style.paddingLeft = "7px";
// 			propDescrCell.style.backgroundColor = "#F5F5DC";
// 			propDescrCell.style.fontSize = "7pt";
// 		
// 			propStartCell.style.textAlign = "left";
// 			propStartCell.style.backgroundColor = "#F5F5DC";
// 
// 			propStartCell.style.paddingLeft = "7px";
// 			propStartCell.style.paddingRight = "2px";
// 		
// 			propEndCell.style.textAlign = "left";
// 			propEndCell.style.backgroundColor = "#F5F5DC";
// 
// 			propEndCell.style.paddingLeft = "7px";
// 			propEndCell.style.paddingRight = "2px";
// 		
// 			propDirCell.style.textAlign = "left";
// 			propDirCell.style.backgroundColor = "#F5F5DC";
// 
// 			propDirCell.style.paddingLeft = "5px";
// 			propDirCell.style.paddingRight = "5px";
// 		
// 			rmvCell.style.textAlign = "left";
// 			propName = "polyA";
// 
// 			// Set cell content and formatting as required by template
// 			propNameCell.innerHTML = "PolyA Tail";
// 			
// 			// Generate the list on the fly
// 			if (createNew == "true")
// 				propNamePrefix = rType + "_"
// 			else
// 				propNamePrefix = ""
// 
// 			var tmpPolyAList = document.getElementById(propNamePrefix + "polyA_list");
// 			var polyASelectedInd = tmpPolyAList.selectedIndex;
// 			var polyASelectedValue = tmpPolyAList[polyASelectedInd].value;
// 
// 			var newPolyAList = document.createElement("SELECT");
// 			var newPolyATxt =  document.createElement("INPUT");
// 
// 			newPolyAList.style.fontSize = "7pt";
// // 			newPolyAList.setAttribute("name", prefix + propName + postfix);
// 
// 			var rowIndex;
// 
// 			if (!document.getElementById(propNamePrefix + "polyA_row_" + propRow.rowIndex + "_id"))
// 			{
// 				propRow.setAttribute('id', propNamePrefix + "polyA_row_" + propRow.rowIndex + "_id");
// 				rowIndex = propRow.rowIndex;
// 			}
// 			else
// 			{
// 				for (i = 0; i < propsTbl.rows.length; i++)
// 				{
// 					if (!document.getElementById(propNamePrefix + "polyA_row_" + i + "_id"))
// 					{
// 						propRow.setAttribute('id', propNamePrefix + "polyA_row_" + i + "_id");
// 						break;
// 					}
// 				}
// 
// 				propRow.setAttribute('id', propNamePrefix + "polyA_row_" + i + "_id");
// 				rowIndex = i;
// 			}
// 
// 			newPolyAList.setAttribute("id", propNamePrefix + propName + "_proplist_" + rowIndex);
// 
// 			newPolyATxt.type = "TEXT";
// 			newPolyATxt.id = propNamePrefix + "polyA_txt_" + rowIndex;
// 			newPolyATxt.style.display = "none";
// 
// 			for (i = 0; i < tmpPolyAList.options.length; i++)
// 			{
// 				tmpOptn = tmpPolyAList.options[i];
// 				newOptn = document.createElement("OPTION");
// 
// 				newOptn.value = tmpOptn.value;
// 				newOptn.name = tmpOptn.name;
// 				newOptn.text = tmpOptn.text;
// 
// 				if (newPolyAList.options.length == 0)
// 					newPolyAList.options.add(newOptn);
// 				else
// 					addElement(newPolyAList, newPolyAList.options.length, newOptn);
// 			}
// 
// 			propValueCell.appendChild(newPolyAList);
// 			propValueCell.appendChild(newPolyATxt);
// 
// 			// Start & stop positions
// 			var newPolyAStart = document.createElement("INPUT");
// 			newPolyAStart.setAttribute("type", 'TEXT');
// 			newPolyAStart.setAttribute("size", 5);
// 			newPolyAStart.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_startpos_id");
// 			newPolyAStart.style.fontSize = "7pt";
// 
// 			propStartCell.appendChild(newPolyAStart);
// 
// 			var newPolyAEnd = document.createElement("INPUT");
// 			newPolyAEnd.setAttribute("type", 'TEXT');
// 			newPolyAEnd.setAttribute("size", 5);
// 			newPolyAEnd.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_endpos_id");
// 			newPolyAEnd.style.fontSize = "7pt";
// 
// 			propEndCell.appendChild(newPolyAEnd);
// 
// 			newPolyAList.onchange = function(){this.setAttribute('name', propNamePrefix + propName + "_" + this[this.selectedIndex].value + "_" + rowIndex); showSpecificOtherTextbox(newPolyAList.id, newPolyATxt.id);};
// 
// 			// Orientation
// 			var fwdDir = document.createElement("INPUT");
// 			fwdDir.setAttribute("type", "radio");
// 			fwdDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_fwd_dir");
// // 			fwdDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			fwdDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// 			fwdDir.setAttribute("checked", true);
// 
// 			propDirCell.appendChild(fwdDir);
// 			propDirCell.innerHTML += "Forward&nbsp;";
// 			propDirCell.style.fontSize = "7pt";
// 
// 			var revDir = document.createElement("INPUT");
// 			revDir.setAttribute("type", "radio");
// 			revDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_rev_dir");
// // 			revDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			revDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// 			propDirCell.appendChild(revDir);
// 			propDirCell.innerHTML += "Reverse";
// 			propDirCell.style.fontSize = "7pt";
// 			propDirCell.style.whiteSpace = "nowrap";
// 
// 			// Add 'Remove' link
// 			var removeLink = document.createElement("SPAN");
// 			removeLink.className = "linkShow";
// 			removeLink.style.fontSize = "7pt";
// 			removeLink.style.fontWeight = "normal";
// 			removeLink.style.marginLeft = "5px";
// 			removeLink.innerHTML = "Remove";
// 			rmvCell.appendChild(removeLink);
// 
// 			// Adjust border height to stretch to bottom of page
// // 			mainBorder = document.getElementById("mainBorder");
// // 			mainBorder.height = document.height;
// // 			mainBorder.rowSpan = propsTbl.rows.length;
// 		
// 			// Add a separator row
// // 			var divRow = propsTbl.insertRow(tmpPolyPosRow.rowIndex + 1);
// // 			var divCell = divRow.insertCell(0);
// // 
// // 			divRow.setAttribute('id', "div_row" + divRow.rowIndex + "_id");
// // 			divCell.colSpan = 6;
// // 			divCell.innerHTML = "<HR>";
// 
// 			// Gather the IDs of all elements in the row to pass to 'deleteRow' function
// // 			var tblID = "modifyReagentPropsTbl";
// 			var polyARowID = propRow.id;
// // 			var divRowID = divRow.id;
// 		
// // 			// Add list to form
// // 			if (form_name == "")
// // 				var myForm = document.reagentDetailForm;
// // 			else
// // 			{
// // 				var myForm;
// // 			
// // 				docForms = document.forms;
// // 			
// // 				for (i = 0; i < docForms.length; i++)
// // 				{
// // 					aForm = docForms[i];
// // 			
// // 					if (aForm.name == form_name)
// // 					{
// // 						myForm = aForm;
// // 						break;
// // 					}
// // 				}
// // 			}
// 
// 			var hiddenPolyA = document.createElement("INPUT");
// 			hiddenPolyA.setAttribute("type", "hidden");
// 			myForm.appendChild(hiddenPolyA);
// 
// 			var hiddenPolyAStart = document.createElement("INPUT");
// 			hiddenPolyAStart.setAttribute("type", "hidden");
// 			hiddenPolyAStart.setAttribute("id", propNamePrefix + "polyA_startpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenPolyAStart);
// 
// 			var hiddenPolyAEnd = document.createElement("INPUT");
// 			hiddenPolyAEnd.setAttribute("type", "hidden");
// 			hiddenPolyAEnd.setAttribute("id", propNamePrefix + "polyA_endpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenPolyAEnd);
// 
// 			// Orientation
// 			var hiddenPolyADir = document.createElement("INPUT");
// 			hiddenPolyADir.setAttribute('type', 'hidden');
// 			hiddenPolyADir.setAttribute("id", propNamePrefix + "polyA_orientation_prop_" + rowIndex);
// 			myForm.appendChild(hiddenPolyADir);
// 
// 			var elemList = new Array();
// 			elemList.push(hiddenPolyA);
// 			elemList.push(hiddenPolyAStart);
// 			elemList.push(hiddenPolyAEnd);
// 			elemList.push(hiddenPolyADir);
// 
// // 			removeLink.onclick = function(){deleteRow(tblID, polyARowID); deleteRow(tblID, tmpPolyPosRowID); deleteRow(tblID, divRowID); removeFormElements(myForm, elemList)};
// 
// 			removeLink.onclick = function(){deleteTableRow(tblID, polyARowID); removeFormElements(myForm, elemList)};
// 		break;
// 	
// 		case 'origin_of_replication':
// 			var propRow = propsTbl.insertRow(propList.rowIndex - 2);
// 		
// 			// Need a few cells in the row
// 			var propNameCell = propRow.insertCell(0);
// 			var propValueCell = propRow.insertCell(1);
// 			var propDescrCell = propRow.insertCell(2);
// 			var propStartCell = propRow.insertCell(3);
// 			var propEndCell = propRow.insertCell(4);
// 			var propDirCell = propRow.insertCell(5);
// 			var rmvCell = propRow.insertCell(6);
// 		
// 			// Format cells	
// 			propNameCell.style.paddingLeft = "7px";
// 			propNameCell.style.backgroundColor = "#F5F5DC";
// 			propNameCell.style.fontSize = "7pt";
// 
// 			propValueCell.style.paddingLeft = "7px";
// 			propValueCell.style.paddingRight = "5px";
// 			propValueCell.style.paddingTop = "1px";
// 			propValueCell.style.paddingBottom = "1px";
// 			propValueCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDescrCell.style.paddingLeft = "7px";
// 			propDescrCell.style.backgroundColor = "#F5F5DC";
// 			propDescrCell.style.fontSize = "7pt";
// 		
// 			propStartCell.style.textAlign = "left";
// 
// 			propStartCell.style.paddingLeft = "7px";
// 			propStartCell.style.paddingRight = "2px";
// 
// 			propStartCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propEndCell.style.textAlign = "left";
// 
// 			propEndCell.style.paddingLeft = "7px";
// 			propEndCell.style.paddingRight = "2px";
// 
// 			propEndCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDirCell.style.textAlign = "left";
// 
// 			propDirCell.style.paddingLeft = "5px";
// 			propDirCell.style.paddingRight = "5px";
// 
// 			propDirCell.style.backgroundColor = "#F5F5DC";
// 		
// 			rmvCell.style.textAlign = "left";
// // 			var propRow = propsTbl.insertRow(propList.rowIndex - 1);
// 		
// 			// Need a few cells in the row
// // 			var propNameCell = propRow.insertCell(0);
// // 			var propValueCell = propRow.insertCell(1);
// 
// 			// Set cell content and formatting as required by template
// // 			propNameCell.className = "detailedView_colName";
// 			propNameCell.innerHTML = "Origin of Replication";
// 		
// // 			propValueCell.className = "detailedView_value";
// // 			propValueCell.setAttribute("white-space", "nowrap");
// // 			propValueCell.style.fontSize = "9pt";
// // 			propValueCell.colSpan = 5;
// // 			propValueCell.style.paddingLeft = "5px";
// 			
// 			// Generate the list on the fly
// 			if (createNew == "true")
// 				propNamePrefix = rType + "_"
// 			else
// 				propNamePrefix = ""
// 
// 			var tmpOriginList = document.getElementById(propNamePrefix + "origin_of_replication_list");
// 			var originSelectedInd = tmpOriginList.selectedIndex;
// 			var originSelectedValue = tmpOriginList[originSelectedInd].value;
// 
// 			var newOriginList = document.createElement("SELECT");
// 			var newOriginTxt =  document.createElement("INPUT");
// 			newOriginList.style.fontSize = "7pt";
// 
// // 			newOriginList.setAttribute("name", prefix + propName + postfix);
// 
// 			var rowIndex;
// 
// 			if (!document.getElementById(propNamePrefix + "origin_of_replication_row_" + propRow.rowIndex + "_id"))
// 			{
// 				propRow.setAttribute('id', propNamePrefix + "origin_of_replication_row_" + propRow.rowIndex + "_id");
// 				rowIndex = propRow.rowIndex;
// 			}
// 			else
// 			{
// 				for (i = 0; i < propsTbl.rows.length; i++)
// 				{
// 					if (!document.getElementById(propNamePrefix + "origin_of_replication_row_" + i + "_id"))
// 					{
// 						propRow.setAttribute('id', propNamePrefix + "origin_of_replication_row_" + i + "_id");
// 						break;
// 					}
// 				}
// 
// 				propRow.setAttribute('id', propNamePrefix + "origin_of_replication_row_" + i + "_id");
// 				rowIndex = i;
// 			}
// 
// 			newOriginList.setAttribute("id", propNamePrefix + propName + "_proplist_" + rowIndex);
// 
// // 			propRow.setAttribute('id', "origin_row_" + propRow.rowIndex + "_id");
// 
// 			newOriginTxt.type = "TEXT";
// 			newOriginTxt.id = propNamePrefix + "origin_of_replication_txt_" + rowIndex;
// 			newOriginTxt.style.display = "none";
// 
// 			for (i = 0; i < tmpOriginList.options.length; i++)
// 			{
// 				tmpOptn = tmpOriginList.options[i];
// 				newOptn = document.createElement("OPTION");
// 
// 				newOptn.value = tmpOptn.value;
// 				newOptn.name = tmpOptn.name;
// 				newOptn.text = tmpOptn.text;
// 
// 				if (newOriginList.options.length == 0)
// 					newOriginList.options.add(newOptn);
// 				else
// 					addElement(newOriginList, newOriginList.options.length, newOptn);
// 			}
// 
// 			propValueCell.appendChild(newOriginList);
// 			propValueCell.appendChild(newOriginTxt);
// 
// 			// Start & stop positions
// 			// April 2/08: Karen asked to show positions and orientation on a new line for ALL features
// // 			var tmpOriginPosRow = propsTbl.insertRow(propRow.rowIndex+1);
// // 			tmpOriginPosRow.setAttribute('id', "origin_pos_row" + tmpOriginPosRow.rowIndex + "_id");
// // 			var tmpOriginPosRowID = tmpOriginPosRow.id;
// // 
// // 			var c1 = tmpOriginPosRow.insertCell(0);
// // 			var originPosCell = tmpOriginPosRow.insertCell(1);
// // 
// // 			originPosCell.className = "detailedView_value";
// // 			originPosCell.style.paddingLeft = "5px";
// // 			originPosCell.setAttribute("white-space", "nowrap");
// // 			originPosCell.colSpan = 5;
// // 
// // 			originPosCell.innerHTML += "Start:&nbsp;";
// 
// // 			propValueCell.innerHTML += "<SPAN style=\"margin-left:8px;\"></span>Start:&nbsp;";
// 			
// 			var newOriginStart = document.createElement("INPUT");
// 			newOriginStart.setAttribute("type", 'TEXT');
// 			newOriginStart.setAttribute("size", 5);
// 			newOriginStart.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_startpos_id");
// 			newOriginStart.style.fontSize = "7pt";
// // 			originPosCell.appendChild(newOriginStart);
// 			propStartCell.appendChild(newOriginStart);
// 
// // 			originPosCell.innerHTML += "&nbsp;&nbsp;End:&nbsp;";
// 			
// 			var newOriginEnd = document.createElement("INPUT");
// 			newOriginEnd.setAttribute("type", 'TEXT');
// 			newOriginEnd.setAttribute("size", 5);
// 			newOriginEnd.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_endpos_id");
// 			newOriginEnd.style.fontSize = "7pt";
// // 			originPosCell.appendChild(newOriginEnd);
// 			propEndCell.appendChild(newOriginEnd);
// 
// 			newOriginList.onchange = function(){this.setAttribute('name', propNamePrefix + propName + "_" + this[this.selectedIndex].value + "_" + rowIndex); showSpecificOtherTextbox(newOriginList.id, newOriginTxt.id);};
// 			{
// // 				showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);
// 			};
// 
// 			// Orientation - March 14/08: Make 'forward' checked by default
// // 			originPosCell.innerHTML += "<SPAN style=\"margin-left:10px;\"></span>";
// 
// 			var fwdDir = document.createElement("INPUT");
// 			fwdDir.setAttribute("type", "radio");
// 			fwdDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_fwd_dir");
// 			fwdDir.setAttribute("name", propNamePrefix + propName + "_orientation_radio_" + rowIndex);
// 			fwdDir.setAttribute("checked", true);
// 
// // 			originPosCell.appendChild(fwdDir);
// // 			originPosCell.innerHTML += "Forward&nbsp;";
// 			propDirCell.appendChild(fwdDir);
// 			propDirCell.innerHTML += "Forward&nbsp;";
// 			propDirCell.style.fontSize = "7pt";
// 			propDirCell.style.whiteSpace = "nowrap";
// 
// 			var revDir = document.createElement("INPUT");
// 			revDir.setAttribute("type", "radio");
// 			revDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_rev_dir");
// 			revDir.setAttribute("name", propNamePrefix + propName + "_orientation_radio_" + rowIndex);
// 
// // 			originPosCell.appendChild(revDir);
// // 			originPosCell.innerHTML += "Reverse";
// 			propDirCell.appendChild(revDir);
// 			propDirCell.innerHTML += "Reverse";
// 
// 			// Add 'Remove' link
// 			var removeLink = document.createElement("SPAN");
// 			removeLink.className = "linkShow";
// 			removeLink.style.fontSize = "7pt";
// 			removeLink.style.fontWeight = "normal";
// 			removeLink.style.marginLeft = "5px";
// 			removeLink.innerHTML = "Remove";
// 		
// // 			originPosCell.appendChild(removeLink);
// 			rmvCell.appendChild(removeLink);
// 
// 			// Adjust border height to stretch to bottom of page
// // 			mainBorder = document.getElementById("mainBorder");
// // 			mainBorder.height = document.height;
// // 			mainBorder.rowSpan = propsTbl.rows.length;
// 		
// 			// Add a separator row
// // 			var divRow = propsTbl.insertRow(tmpOriginPosRow.rowIndex + 1);
// // 			var divCell = divRow.insertCell(0);
// 
// // 			divRow.setAttribute('id', "div_row" + divRow.rowIndex + "_id");
// // 			divCell.colSpan = 6;
// // 			divCell.innerHTML = "<HR>";
// 
// 			// 'Remove' link
// // 			var tblID = "modifyReagentPropsTbl";
// 			var originRowID = propRow.id;
// // 			var divRowID = divRow.id;
// 		
// // 			// Add list to form
// // 			if (form_name == "")
// // 				var myForm = document.reagentDetailForm;
// // 			else
// // 			{
// // 				var myForm;
// // 			
// // 				docForms = document.forms;
// // 			
// // 				for (i = 0; i < docForms.length; i++)
// // 				{
// // 					aForm = docForms[i];
// // 			
// // 					if (aForm.name == form_name)
// // 					{
// // 						myForm = aForm;
// // 						break;
// // 					}
// // 				}
// // 			}
// 
// 			var hiddenOrigin = document.createElement("INPUT");
// 			hiddenOrigin.setAttribute("type", "hidden");
// 			myForm.appendChild(hiddenOrigin);
// 
// 			var hiddenOriginStart = document.createElement("INPUT");
// 			hiddenOriginStart.setAttribute("type", "hidden");
// 			hiddenOriginStart.setAttribute("id", propNamePrefix + "origin_of_replication_startpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenOriginStart);
// 
// 			var hiddenOriginEnd = document.createElement("INPUT");
// 			hiddenOriginEnd.setAttribute("type", "hidden");
// 			hiddenOriginEnd.setAttribute("id", propNamePrefix + "origin_of_replication_endpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenOriginEnd);
// 
// 			// Orientation
// 			var hiddenOriginDir = document.createElement("INPUT");
// 			hiddenOriginDir.setAttribute('type', 'hidden');
// 			hiddenOriginDir.setAttribute("id", propNamePrefix + "origin_of_replication_orientation_prop_" + rowIndex);
// 			myForm.appendChild(hiddenOriginDir);
// 
// 			var elemList = new Array();
// 			elemList.push(hiddenOrigin);
// 			elemList.push(hiddenOriginStart);
// 			elemList.push(hiddenOriginEnd);
// 			elemList.push(hiddenOriginDir);
// 
// // 			removeLink.onclick = function(){deleteRow(tblID, originRowID); deleteRow(tblID, tmpOriginPosRowID); deleteRow(tblID, divRowID); removeFormElements(myForm, elemList)};
// 
// 			removeLink.onclick = function(){deleteTableRow(tblID, originRowID); removeFormElements(myForm, elemList)};
// 		break;
// 
// 		case 'miscellaneous':
// 			var propRow = propsTbl.insertRow(propList.rowIndex - 2);
// 		
// 			var propNameCell = propRow.insertCell(0);
// 			var propValueCell = propRow.insertCell(1);
// 			var propDescrCell = propRow.insertCell(2);
// 			var propStartCell = propRow.insertCell(3);
// 			var propEndCell = propRow.insertCell(4);
// 			var propDirCell = propRow.insertCell(5);
// 			var rmvCell = propRow.insertCell(6);
// 		
// 			// Format cells
// 			propNameCell.style.paddingLeft = "7px";
// 			propNameCell.style.backgroundColor = "#F5F5DC";
// 			propNameCell.style.fontSize = "7pt";
// 
// 			propValueCell.style.paddingLeft = "7px";
// 			propValueCell.style.paddingRight = "5px";
// 			propValueCell.style.paddingTop = "1px";
// 			propValueCell.style.paddingBottom = "1px";
// 			propValueCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDescrCell.style.paddingLeft = "7px";
// 			propDescrCell.style.backgroundColor = "#F5F5DC";
// 			propDescrCell.style.fontSize = "7pt";
// 		
// 			propStartCell.style.textAlign = "left";
// 
// 			propStartCell.style.paddingLeft = "7px";
// 			propStartCell.style.paddingRight = "2px";
// 
// 			propStartCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propEndCell.style.textAlign = "left";
// 			propEndCell.style.backgroundColor = "#F5F5DC";
// 
// 			propEndCell.style.paddingLeft = "7px";
// 			propEndCell.style.paddingRight = "2px";
// 		
// 			propDirCell.style.textAlign = "left";
// 
// 			propDirCell.style.paddingLeft = "5px";
// 			propDirCell.style.paddingRight = "5px";
// 
// 			propDirCell.style.backgroundColor = "#F5F5DC";
// 		
// 			rmvCell.style.textAlign = "left";
// 
// 			propNameCell.innerHTML = "Miscellaneous";
// 
// 			// Generate the list on the fly
// 			if (createNew == "true")
// 				propNamePrefix = rType + "_"
// 			else
// 				propNamePrefix = ""
// 
// 			var tmpMiscList = document.getElementById(propNamePrefix + "miscellaneous_list");
// 			var miscSelectedInd = tmpMiscList.selectedIndex;
// 			var miscSelectedValue = tmpMiscList[miscSelectedInd].value;
// 
// 			var newMiscList = document.createElement("SELECT");
// 			var newMiscTxt =  document.createElement("INPUT");
// 
// 			var rowIndex;
// 
// 			if (!document.getElementById(propNamePrefix+ "miscellaneous_row_" + propRow.rowIndex + "_id"))
// 			{
// 				propRow.setAttribute('id', propNamePrefix + "miscellaneous_row_" + propRow.rowIndex + "_id");
// 				rowIndex = propRow.rowIndex;
// 			}
// 			else
// 			{
// 				for (i = 0; i < propsTbl.rows.length; i++)
// 				{
// 					if (!document.getElementById(propNamePrefix + "miscellaneous_row_" + i + "_id"))
// 					{
// 						propRow.setAttribute('id', propNamePrefix + "miscellaneous_row_" + i + "_id");
// 						break;
// 					}
// 				}
// 
// 				propRow.setAttribute('id', propNamePrefix + "miscellaneous_row_" + i + "_id");
// 				rowIndex = i;
// 			}
// 
// 			newMiscList.setAttribute("id", propNamePrefix + propName + "_proplist_" + rowIndex);
// 			newMiscList.style.fontSize = "7pt";
// 
// 			newMiscTxt.type = "TEXT";
// 			newMiscTxt.id = propNamePrefix + "miscellaneous_txt_" + rowIndex;
// 			newMiscTxt.style.display = "none";
// 
// 			for (i = 0; i < tmpMiscList.options.length; i++)
// 			{
// 				tmpOptn = tmpMiscList.options[i];
// 				newOptn = document.createElement("OPTION");
// 
// 				newOptn.value = tmpOptn.value;
// 				newOptn.name = tmpOptn.name;
// 				newOptn.text = tmpOptn.text;
// 
// 				if (newMiscList.options.length == 0)
// 					newMiscList.options.add(newOptn);
// 				else
// 					addElement(newMiscList, newMiscList.options.length, newOptn);
// 			}
// 
// 			propValueCell.appendChild(newMiscList);
// 			propValueCell.appendChild(newMiscTxt);
// 
// 			var newMiscStart = document.createElement("INPUT");
// 			newMiscStart.setAttribute("type", 'TEXT');
// 			newMiscStart.setAttribute("size", 5);
// 			newMiscStart.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_startpos_id");
// 			newMiscStart.style.fontSize = "7pt";
// 			propStartCell.appendChild(newMiscStart);
// 			
// 			var newMiscEnd = document.createElement("INPUT");
// 			newMiscEnd.setAttribute("type", 'TEXT');
// 			newMiscEnd.setAttribute("size", 5);
// 			newMiscEnd.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_endpos_id");
// 			newMiscEnd.style.fontSize = "7pt";
// 			propEndCell.appendChild(newMiscEnd);
// 
// 			newMiscList.onchange = function(){this.setAttribute('name', propNamePrefix + propName + "_" + this[this.selectedIndex].value + "_" + rowIndex); showSpecificOtherTextbox(newMiscList.id, newMiscTxt.id);};
// 
// 			// Orientation
// 			var fwdDir = document.createElement("INPUT");
// 			fwdDir.setAttribute("type", "radio");
// 			fwdDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_fwd_dir");
// // 			fwdDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			fwdDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 			fwdDir.setAttribute("checked", true);
// 
// // 			miscPosCell.appendChild(fwdDir);
// 			propDirCell.appendChild(fwdDir);
// // 			miscPosCell.innerHTML += "Forward&nbsp;";
// 			propDirCell.innerHTML += "Forward&nbsp;";
// 
// 			var revDir = document.createElement("INPUT");
// 			revDir.setAttribute("type", "radio");
// 			revDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_rev_dir");
// // 			revDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			revDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// // 			miscPosCell.appendChild(revDir);
// // 			miscPosCell.innerHTML += "Reverse";
// 			propDirCell.appendChild(revDir);
// 			propDirCell.innerHTML += "Reverse";
// 			propDirCell.style.fontSize = "7pt";
// 			propDirCell.style.whiteSpace = "nowrap";
// 
// 			// Add 'Remove' link
// 			var removeLink = document.createElement("SPAN");
// 			removeLink.className = "linkShow";
// 			removeLink.style.fontSize = "7pt";
// 			removeLink.style.fontWeight = "normal";
// 			removeLink.style.marginLeft = "5px";
// 			removeLink.innerHTML = "Remove";
// 		
// // 			miscPosCell.appendChild(removeLink);
// 			rmvCell.appendChild(removeLink);
// 
// 			// Adjust border height to stretch to bottom of page
// // 			mainBorder = document.getElementById("mainBorder");
// // 			mainBorder.height = document.height;
// // 			mainBorder.rowSpan = propsTbl.rows.length;
// 		
// 			// Add a separator row
// // 			var divRow = propsTbl.insertRow(rowIndex + 1);
// // 			var divCell = divRow.insertCell(0);
// 
// // 			divRow.setAttribute('id', "div_row" + divRow.rowIndex + "_id");
// // 			divCell.colSpan = 6;
// // 			divCell.innerHTML = "<HR>";
// 
// 			// 'Remove' link
// // 			var tblID = "modifyReagentPropsTbl";
// 			var miscRowID = propRow.id;
// // 			var divRowID = divRow.id;
// 		
// // 			// Add list to form
// // 			if (form_name == "")
// // 				var myForm = document.reagentDetailForm;
// // 			else
// // 			{
// // 				var myForm;
// // 			
// // 				docForms = document.forms;
// // 			
// // 				for (i = 0; i < docForms.length; i++)
// // 				{
// // 					aForm = docForms[i];
// // 			
// // 					if (aForm.name == form_name)
// // 					{
// // 						myForm = aForm;
// // 						break;
// // 					}
// // 				}
// // 			}
// 
// 			var hiddenMisc = document.createElement("INPUT");
// 			hiddenMisc.setAttribute("type", "hidden");
// 			myForm.appendChild(hiddenMisc);
// 
// 			var hiddenMiscStart = document.createElement("INPUT");
// 			hiddenMiscStart.setAttribute("type", "hidden");
// 			hiddenMiscStart.setAttribute("id", propNamePrefix + "miscellaneous_startpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMiscStart);
// 
// 			var hiddenMiscEnd = document.createElement("INPUT");
// 			hiddenMiscEnd.setAttribute("type", "hidden");
// 			hiddenMiscEnd.setAttribute("id", propNamePrefix + "miscellaneous_endpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMiscEnd);
// 
// 			// Orientation
// 			var hiddenMiscDir = document.createElement("INPUT");
// 			hiddenMiscDir.setAttribute('type', 'hidden');
// 			hiddenMiscDir.setAttribute("id", propNamePrefix + "miscellaneous_orientation_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMiscDir);
// 
// 			var elemList = new Array();
// 			elemList.push(hiddenMisc);
// 			elemList.push(hiddenMiscStart);
// 			elemList.push(hiddenMiscEnd);
// 			elemList.push(hiddenMiscDir);
// 
// // 			removeLink.onclick = function(){deleteRow(tblID, miscRowID); deleteRow(tblID, miscPosRowID); deleteRow(tblID, divRowID); removeFormElements(myForm, elemList)};
// 			removeLink.onclick = function(){deleteTableRow(tblID, miscRowID); removeFormElements(myForm, elemList)};
// 
// 		break;
// 
// 		case 'cleavage_site':
// 			var propRow = propsTbl.insertRow(propList.rowIndex - 2);
// 		
// // 			// Need a few cells in the row
// // 			var propNameCell = propRow.insertCell(0);
// // 			var propValueCell = propRow.insertCell(1);
// // 
// // 			// Set cell content and formatting as required by template
// // 			propNameCell.className = "detailedView_colName";
// 
// 			var propNameCell = propRow.insertCell(0);
// 			var propValueCell = propRow.insertCell(1);
// 			var propDescrCell = propRow.insertCell(2);
// 			var propStartCell = propRow.insertCell(3);
// 			var propEndCell = propRow.insertCell(4);
// 			var propDirCell = propRow.insertCell(5);
// 			var rmvCell = propRow.insertCell(6);
// 		
// 			// Format cells	
// 			propNameCell.style.paddingLeft = "7px";
// 			propNameCell.style.backgroundColor = "#F5F5DC";
// 			propNameCell.style.fontSize = "7pt";
// 
// 			propValueCell.style.paddingLeft = "7px";
// 			propValueCell.style.paddingRight = "5px";
// 			propValueCell.style.paddingTop = "1px";
// 			propValueCell.style.paddingBottom = "1px";
// 			propValueCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDescrCell.style.paddingLeft = "7px";
// 			propDescrCell.style.backgroundColor = "#F5F5DC";
// 			propDescrCell.style.fontSize = "7pt";
// 			propStartCell.style.textAlign = "left";
// 
// 			propStartCell.style.paddingLeft = "7px";
// 			propStartCell.style.paddingRight = "2px";
// 
// 			propStartCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propEndCell.style.textAlign = "left";
// 			propEndCell.style.backgroundColor = "#F5F5DC";
// 
// 			propEndCell.style.paddingLeft = "7px";
// 			propEndCell.style.paddingRight = "2px";
// 		
// 			propDirCell.style.textAlign = "left";
// 
// 			propDirCell.style.paddingLeft = "5px";
// 			propDirCell.style.paddingRight = "5px";
// 
// 			propDirCell.style.backgroundColor = "#F5F5DC";
// 		
// 			rmvCell.style.textAlign = "left";
// 
// 			propNameCell.innerHTML = "Cleavage Site";
// // 		
// // 			propValueCell.className = "detailedView_value";
// // 			propValueCell.setAttribute("white-space", "nowrap");
// // 			propValueCell.style.fontSize = "9pt";
// // 			propValueCell.colSpan = 5;
// // 			propValueCell.style.paddingLeft = "5px";
// 			
// 			// Generate the list on the fly
// 			if (createNew == "true")
// 				propNamePrefix = rType + "_"
// 			else
// 				propNamePrefix = ""
// 
// 			var tmpMiscList = document.getElementById(propNamePrefix + "cleavage_site_list");
// 			var miscSelectedInd = tmpMiscList.selectedIndex;
// 			var miscSelectedValue = tmpMiscList[miscSelectedInd].value;
// 
// 			var newMiscList = document.createElement("SELECT");
// 			var newMiscTxt =  document.createElement("INPUT");
// 			
// // 			newMiscList.setAttribute("name", prefix + propName + postfix);
// 
// 			var rowIndex;
// 
// 			if (!document.getElementById(propNamePrefix + "cleavage_site_row_" + propRow.rowIndex + "_id"))
// 			{
// 				propRow.setAttribute('id', propNamePrefix + "cleavage_site_row_" + propRow.rowIndex + "_id");
// 				rowIndex = propRow.rowIndex;
// 			}
// 			else
// 			{
// 				for (i = 0; i < propsTbl.rows.length; i++)
// 				{
// 					if (!document.getElementById(propNamePrefix + "cleavage_site_row_" + i + "_id"))
// 					{
// 						propRow.setAttribute('id', propNamePrefix + "cleavage_site_row_" + i + "_id");
// 						break;
// 					}
// 				}
// 
// 				propRow.setAttribute('id', "cleavage_site_row_" + i + "_id");
// 				rowIndex = i;
// 			}
// 
// 			newMiscList.setAttribute("id", propNamePrefix + propName + "_proplist_" + rowIndex);
// 			newMiscList.style.fontSize = "7pt";
// 
// // 			propRow.setAttribute('id', "miscellaneous_row_" + propRow.rowIndex + "_id");
// 
// 			newMiscTxt.type = "TEXT";
// 			newMiscTxt.id = propNamePrefix + "cleavage_site_txt_" + (rowIndex - 1);
// 			newMiscTxt.style.display = "none";
// 
// 			for (i = 0; i < tmpMiscList.options.length; i++)
// 			{
// 				tmpOptn = tmpMiscList.options[i];
// 				newOptn = document.createElement("OPTION");
// 
// 				newOptn.value = tmpOptn.value;
// 				newOptn.name = tmpOptn.name;
// 				newOptn.text = tmpOptn.text;
// 
// 				if (newMiscList.options.length == 0)
// 					newMiscList.options.add(newOptn);
// 				else
// 					addElement(newMiscList, newMiscList.options.length, newOptn);
// 			}
// 
// 			propValueCell.appendChild(newMiscList);
// 			propValueCell.appendChild(newMiscTxt);
// 
// 			// Start & stop positions
// 			// April 1, 2008: Show in a new row
// // 			var miscPosRow = propsTbl.insertRow(rowIndex+1);
// // 			miscPosRow.setAttribute('id', "miscellaneous_pos_row" + rowIndex + "_id");
// // 			var miscPosRowID = miscPosRow.id;
// // 
// // 			var c1 = miscPosRow.insertCell(0);
// // 			var miscPosCell = miscPosRow.insertCell(1);
// // 
// // 			miscPosCell.className = "detailedView_value";
// // 			miscPosCell.style.paddingLeft = "5px";
// // 			miscPosCell.colSpan = 5;
// // 			miscPosCell.setAttribute("white-space", "nowrap");
// 
// // 			miscPosCell.innerHTML = "Start:&nbsp;";
// 
// 			var newMiscStart = document.createElement("INPUT");
// 			newMiscStart.setAttribute("type", 'TEXT');
// 			newMiscStart.setAttribute("size", 5);
// 			newMiscStart.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_startpos_id");
// 			newMiscStart.style.fontSize = "7pt";
// 
// // 			miscPosCell.appendChild(newMiscStart);
// 			propStartCell.appendChild(newMiscStart);
// 
// // 			miscPosCell.innerHTML += "&nbsp;&nbsp;End:&nbsp;";
// 			
// 			var newMiscEnd = document.createElement("INPUT");
// 			newMiscEnd.setAttribute("type", 'TEXT');
// 			newMiscEnd.setAttribute("size", 5);
// 			newMiscEnd.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_endpos_id");
// 			newMiscEnd.style.fontSize = "7pt";
// 
// // 			miscPosCell.appendChild(newMiscEnd);
// 			propEndCell.appendChild(newMiscEnd);
// 
// // 			newMiscList.onchange = function()
// // 			{
// // // 				showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);
// // 			};
// 			newMiscList.onchange = function(){this.setAttribute('name', propNamePrefix + propName + "_" + this[this.selectedIndex].value + "_" + rowIndex);};
// 
// 			// Orientation - March 14/08: Make 'forward' checked by default
// // 			miscPosCell.innerHTML += "<SPAN style=\"margin-left:10px;\"></span>";
// 
// 			var fwdDir = document.createElement("INPUT");
// 			fwdDir.setAttribute("type", "radio");
// 			fwdDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_fwd_dir");
// // 			fwdDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			fwdDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 			fwdDir.setAttribute("checked", true);
// 
// // 			miscPosCell.appendChild(fwdDir);
// 			propDirCell.appendChild(fwdDir);
// // 			miscPosCell.innerHTML += "Forward&nbsp;";
// 			propDirCell.innerHTML += "Forward&nbsp;";
// 
// 			var revDir = document.createElement("INPUT");
// 			revDir.setAttribute("type", "radio");
// 			revDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_rev_dir");
// // 			revDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			revDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// // 			miscPosCell.appendChild(revDir);
// // 			miscPosCell.innerHTML += "Reverse";
// 			propDirCell.appendChild(revDir);
// 			propDirCell.innerHTML += "Reverse";
// 			propDirCell.style.fontSize = "7pt";
// 			propDirCell.style.whiteSpace = "nowrap";
// 
// 			// Add 'Remove' link
// 			var removeLink = document.createElement("SPAN");
// 			removeLink.className = "linkShow";
// 			removeLink.style.fontSize = "7pt";
// 			removeLink.style.fontWeight = "normal";
// 			removeLink.style.marginLeft = "5px";
// 			removeLink.innerHTML = "Remove";
// 		
// // 			miscPosCell.appendChild(removeLink);
// 			rmvCell.appendChild(removeLink);
// 
// 			// Adjust border height to stretch to bottom of page
// // 			mainBorder = document.getElementById("mainBorder");
// // 			mainBorder.height = document.height;
// // 			mainBorder.rowSpan = propsTbl.rows.length;
// 		
// 			// Add a separator row
// // 			var divRow = propsTbl.insertRow(rowIndex + 1);
// // 			var divCell = divRow.insertCell(0);
// 
// // 			divRow.setAttribute('id', "div_row" + divRow.rowIndex + "_id");
// // 			divCell.colSpan = 6;
// // 			divCell.innerHTML = "<HR>";
// 
// 			// 'Remove' link
// // 			var tblID = "modifyReagentPropsTbl";
// 			var miscRowID = propRow.id;
// // 			var divRowID = divRow.id;
// 		
// // 			// Add list to form
// // 			if (form_name == "")
// // 				var myForm = document.reagentDetailForm;
// // 			else
// // 			{
// // 				var myForm;
// // 			
// // 				docForms = document.forms;
// // 			
// // 				for (i = 0; i < docForms.length; i++)
// // 				{
// // 					aForm = docForms[i];
// // 			
// // 					if (aForm.name == form_name)
// // 					{
// // 						myForm = aForm;
// // 						break;
// // 					}
// // 				}
// // 			}
// 
// 			var hiddenMisc = document.createElement("INPUT");
// 			hiddenMisc.setAttribute("type", "hidden");
// 			myForm.appendChild(hiddenMisc);
// 
// 			var hiddenMiscStart = document.createElement("INPUT");
// 			hiddenMiscStart.setAttribute("type", "hidden");
// 			hiddenMiscStart.setAttribute("id", propNamePrefix + "cleavage_site_startpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMiscStart);
// 
// 			var hiddenMiscEnd = document.createElement("INPUT");
// 			hiddenMiscEnd.setAttribute("type", "hidden");
// 			hiddenMiscEnd.setAttribute("id", propNamePrefix + "cleavage_site_endpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMiscEnd);
// 
// 			// Orientation
// 			var hiddenMiscDir = document.createElement("INPUT");
// 			hiddenMiscDir.setAttribute('type', 'hidden');
// 			hiddenMiscDir.setAttribute("id", propNamePrefix + "cleavage_site_orientation_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMiscDir);
// 
// 			var elemList = new Array();
// 			elemList.push(hiddenMisc);
// 			elemList.push(hiddenMiscStart);
// 			elemList.push(hiddenMiscEnd);
// 			elemList.push(hiddenMiscDir);
// 
// // 			removeLink.onclick = function(){deleteRow(tblID, miscRowID); deleteRow(tblID, miscPosRowID); deleteRow(tblID, divRowID); removeFormElements(myForm, elemList)};
// 
// 			removeLink.onclick = function(){deleteTableRow(tblID, miscRowID); removeFormElements(myForm, elemList)};
// 		break;
// 
// 		case 'transcription_terminator':
// 			var propRow = propsTbl.insertRow(propList.rowIndex - 2);
// 		
// // 			// Need a few cells in the row
// // 			var propNameCell = propRow.insertCell(0);
// // 			var propValueCell = propRow.insertCell(1);
// // 
// // 			// Set cell content and formatting as required by template
// // 			propNameCell.className = "detailedView_colName";
// 
// 			var propNameCell = propRow.insertCell(0);
// 			var propValueCell = propRow.insertCell(1);
// 			var propDescrCell = propRow.insertCell(2);
// 			var propStartCell = propRow.insertCell(3);
// 			var propEndCell = propRow.insertCell(4);
// 			var propDirCell = propRow.insertCell(5);
// 			var rmvCell = propRow.insertCell(6);
// 		
// 			// Format cells	
// 			propNameCell.style.paddingLeft = "7px";
// 			propNameCell.style.backgroundColor = "#F5F5DC";
// 			propNameCell.style.fontSize = "7pt";
// 		
// 			propValueCell.style.paddingLeft = "7px";
// 			propValueCell.style.paddingRight = "5px";
// 			propValueCell.style.paddingTop = "1px";
// 			propValueCell.style.paddingBottom = "1px";
// 			propValueCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDescrCell.style.paddingLeft = "7px";
// 			propDescrCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propStartCell.style.textAlign = "left";
// 
// 			propStartCell.style.paddingLeft = "7px";
// 			propStartCell.style.paddingRight = "2px";
// 
// 			propStartCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propEndCell.style.textAlign = "left";
// 			propEndCell.style.backgroundColor = "#F5F5DC";
// 
// 			propEndCell.style.paddingLeft = "7px";
// 			propEndCell.style.paddingRight = "2px";
// 		
// 			propDirCell.style.textAlign = "left";
// 
// 			propDirCell.style.paddingLeft = "5px";
// 			propDirCell.style.paddingRight = "5px";
// 
// 			propDirCell.style.backgroundColor = "#F5F5DC";
// 		
// 			rmvCell.style.textAlign = "left";
// 
// 			propNameCell.innerHTML = "Transcription Terminator";
// // 		
// // 			propValueCell.className = "detailedView_value";
// // 			propValueCell.setAttribute("white-space", "nowrap");
// // 			propValueCell.style.fontSize = "9pt";
// // 			propValueCell.colSpan = 5;
// // 			propValueCell.style.paddingLeft = "5px";
// 			
// 			// Generate the list on the fly
// 			if (createNew == "true")
// 				propNamePrefix = rType + "_"
// 			else
// 				propNamePrefix = ""
// 
// 			var tmpMiscList = document.getElementById(propNamePrefix + "transcription_terminator_list");
// 			var miscSelectedInd = tmpMiscList.selectedIndex;
// 			var miscSelectedValue = tmpMiscList[miscSelectedInd].value;
// 
// 			var newMiscList = document.createElement("SELECT");
// 			var newMiscTxt =  document.createElement("INPUT");
// 
// 			newMiscList.style.fontSize = "7pt";
// 
// // 			newMiscList.setAttribute("name", prefix + propName + postfix);
// 
// 			var rowIndex;
// 
// 			if (!document.getElementById(propNamePrefix + "transcription_terminator_row_" + propRow.rowIndex + "_id"))
// 			{
// 				propRow.setAttribute('id', propNamePrefix + "transcription_terminator_row_" + propRow.rowIndex + "_id");
// 				rowIndex = propRow.rowIndex;
// 			}
// 			else
// 			{
// 				for (i = 0; i < propsTbl.rows.length; i++)
// 				{
// 					if (!document.getElementById(propNamePrefix + "transcription_terminator_row_" + i + "_id"))
// 					{
// 						propRow.setAttribute('id', propNamePrefix + "transcription_terminator_row_" + i + "_id");
// 						break;
// 					}
// 				}
// 
// 				propRow.setAttribute('id', propNamePrefix + "transcription_terminator_row_" + i + "_id");
// 				rowIndex = i;
// 			}
// 
// 			newMiscList.setAttribute("id", propNamePrefix + propName + "_proplist_" + rowIndex);
// 
// // 			propRow.setAttribute('id', "miscellaneous_row_" + propRow.rowIndex + "_id");
// 
// 			newMiscTxt.type = "TEXT";
// 			newMiscTxt.id = propNamePrefix + "transcription_terminator_txt_" + (rowIndex - 1);
// 			newMiscTxt.style.display = "none";
// 
// 			for (i = 0; i < tmpMiscList.options.length; i++)
// 			{
// 				tmpOptn = tmpMiscList.options[i];
// 				newOptn = document.createElement("OPTION");
// 
// 				newOptn.value = tmpOptn.value;
// 				newOptn.name = tmpOptn.name;
// 				newOptn.text = tmpOptn.text;
// 
// 				if (newMiscList.options.length == 0)
// 					newMiscList.options.add(newOptn);
// 				else
// 					addElement(newMiscList, newMiscList.options.length, newOptn);
// 			}
// 
// 			propValueCell.appendChild(newMiscList);
// 			propValueCell.appendChild(newMiscTxt);
// 
// 			// Start & stop positions
// 			// April 1, 2008: Show in a new row
// // 			var miscPosRow = propsTbl.insertRow(rowIndex+1);
// // 			miscPosRow.setAttribute('id', "miscellaneous_pos_row" + rowIndex + "_id");
// // 			var miscPosRowID = miscPosRow.id;
// // 
// // 			var c1 = miscPosRow.insertCell(0);
// // 			var miscPosCell = miscPosRow.insertCell(1);
// // 
// // 			miscPosCell.className = "detailedView_value";
// // 			miscPosCell.style.paddingLeft = "5px";
// // 			miscPosCell.colSpan = 5;
// // 			miscPosCell.setAttribute("white-space", "nowrap");
// 
// // 			miscPosCell.innerHTML = "Start:&nbsp;";
// 
// 			var newMiscStart = document.createElement("INPUT");
// 			newMiscStart.setAttribute("type", 'TEXT');
// 			newMiscStart.setAttribute("size", 5);
// 			newMiscStart.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_startpos_id");
// 			newMiscStart.style.fontSize = "7pt";
// 
// // 			miscPosCell.appendChild(newMiscStart);
// 			propStartCell.appendChild(newMiscStart);
// 
// // 			miscPosCell.innerHTML += "&nbsp;&nbsp;End:&nbsp;";
// 			
// 			var newMiscEnd = document.createElement("INPUT");
// 			newMiscEnd.setAttribute("type", 'TEXT');
// 			newMiscEnd.setAttribute("size", 5);
// 			newMiscEnd.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_endpos_id");
// 			newMiscEnd.style.fontSize = "7pt";
// 
// // 			miscPosCell.appendChild(newMiscEnd);
// 			propEndCell.appendChild(newMiscEnd);
// 
// // 			newMiscList.onchange = function()
// // 			{
// // // 				showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);
// // 			};
// 			newMiscList.onchange = function(){this.setAttribute('name', propNamePrefix + propName + "_" + this[this.selectedIndex].value + "_" + rowIndex);};
// 
// 			// Orientation - March 14/08: Make 'forward' checked by default
// // 			miscPosCell.innerHTML += "<SPAN style=\"margin-left:10px;\"></span>";
// 
// 			var fwdDir = document.createElement("INPUT");
// 			fwdDir.setAttribute("type", "radio");
// 			fwdDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_fwd_dir");
// // 			fwdDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			fwdDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// 			fwdDir.setAttribute("checked", true);
// 
// // 			miscPosCell.appendChild(fwdDir);
// 			propDirCell.appendChild(fwdDir);
// // 			miscPosCell.innerHTML += "Forward&nbsp;";
// 			propDirCell.innerHTML += "Forward&nbsp;";
// 
// 			var revDir = document.createElement("INPUT");
// 			revDir.setAttribute("type", "radio");
// 			revDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_rev_dir");
// // 			revDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			revDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// // 			miscPosCell.appendChild(revDir);
// // 			miscPosCell.innerHTML += "Reverse";
// 			propDirCell.appendChild(revDir);
// 			propDirCell.innerHTML += "Reverse";
// 			propDirCell.style.fontSize = "7pt";
// 			propDirCell.style.whiteSpace = "nowrap";
// 
// 			// Add 'Remove' link
// 			var removeLink = document.createElement("SPAN");
// 			removeLink.className = "linkShow";
// 			removeLink.style.fontSize = "7pt";
// 			removeLink.style.fontWeight = "normal";
// 			removeLink.style.marginLeft = "5px";
// 			removeLink.innerHTML = "Remove";
// 		
// // 			miscPosCell.appendChild(removeLink);
// 			rmvCell.appendChild(removeLink);
// 
// 			// Adjust border height to stretch to bottom of page
// // 			mainBorder = document.getElementById("mainBorder");
// // 			mainBorder.height = document.height;
// // 			mainBorder.rowSpan = propsTbl.rows.length;
// 		
// 			// Add a separator row
// // 			var divRow = propsTbl.insertRow(rowIndex + 1);
// // 			var divCell = divRow.insertCell(0);
// 
// // 			divRow.setAttribute('id', "div_row" + divRow.rowIndex + "_id");
// // 			divCell.colSpan = 6;
// // 			divCell.innerHTML = "<HR>";
// 
// 			// 'Remove' link
// // 			var tblID = "modifyReagentPropsTbl";
// 			var miscRowID = propRow.id;
// // 			var divRowID = divRow.id;
// 		
// // 			// Add list to form
// // 			if (form_name == "")
// // 				var myForm = document.reagentDetailForm;
// // 			else
// // 			{
// // 				var myForm;
// // 			
// // 				docForms = document.forms;
// // 			
// // 				for (i = 0; i < docForms.length; i++)
// // 				{
// // 					aForm = docForms[i];
// // 			
// // 					if (aForm.name == form_name)
// // 					{
// // 						myForm = aForm;
// // 						break;
// // 					}
// // 				}
// // 			}
// 
// 			var hiddenMisc = document.createElement("INPUT");
// 			hiddenMisc.setAttribute("type", "hidden");
// 			myForm.appendChild(hiddenMisc);
// 
// 			var hiddenMiscStart = document.createElement("INPUT");
// 			hiddenMiscStart.setAttribute("type", "hidden");
// 			hiddenMiscStart.setAttribute("id", propNamePrefix + "transcription_terminator_startpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMiscStart);
// 
// 			var hiddenMiscEnd = document.createElement("INPUT");
// 			hiddenMiscEnd.setAttribute("type", "hidden");
// 			hiddenMiscEnd.setAttribute("id", propNamePrefix + "transcription_terminator_endpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMiscEnd);
// 
// 			// Orientation
// 			var hiddenMiscDir = document.createElement("INPUT");
// 			hiddenMiscDir.setAttribute('type', 'hidden');
// 			hiddenMiscDir.setAttribute("id", propNamePrefix + "transcription_terminator_orientation_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMiscDir);
// 
// 			var elemList = new Array();
// 			elemList.push(hiddenMisc);
// 			elemList.push(hiddenMiscStart);
// 			elemList.push(hiddenMiscEnd);
// 			elemList.push(hiddenMiscDir);
// 
// // 			removeLink.onclick = function(){deleteRow(tblID, miscRowID); deleteRow(tblID, miscPosRowID); deleteRow(tblID, divRowID); removeFormElements(myForm, elemList)};
// 
// 			removeLink.onclick = function(){deleteTableRow(tblID, miscRowID); removeFormElements(myForm, elemList)};
// 		break;
// 
// 		case 'restriction_site':
// 
// 			var propRow = propsTbl.insertRow(propList.rowIndex - 2);
// 		
// 			var propNameCell = propRow.insertCell(0);
// 			var propValueCell = propRow.insertCell(1);
// 			var propDescrCell = propRow.insertCell(2);
// 			var propStartCell = propRow.insertCell(3);
// 			var propEndCell = propRow.insertCell(4);
// 			var propDirCell = propRow.insertCell(5);
// 			var rmvCell = propRow.insertCell(6);
// 		
// 			// Format cells	
// 			propNameCell.style.paddingLeft = "7px";
// 			propNameCell.style.backgroundColor = "#F5F5DC";
// 			propNameCell.style.fontSize = "7pt";
// 
// 			propValueCell.style.paddingLeft = "7px";
// 			propValueCell.style.paddingRight = "5px";
// 			propValueCell.style.paddingTop = "1px";
// 			propValueCell.style.paddingBottom = "1px";
// 			propValueCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDescrCell.style.paddingLeft = "7px";
// 			propDescrCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propStartCell.style.textAlign = "left";
// 
// 			propStartCell.style.paddingLeft = "7px";
// 			propStartCell.style.paddingRight = "2px";
// 
// 			propStartCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propEndCell.style.textAlign = "left";
// 			propEndCell.style.backgroundColor = "#F5F5DC";
// 
// 			propEndCell.style.paddingLeft = "7px";
// 			propEndCell.style.paddingRight = "2px";
// 		
// 			propDirCell.style.textAlign = "left";
// 
// 			propDirCell.style.paddingLeft = "5px";
// 			propDirCell.style.paddingRight = "5px";
// 
// 			propDirCell.style.backgroundColor = "#F5F5DC";
// 		
// 			rmvCell.style.textAlign = "left";
// 
// 			propNameCell.innerHTML = "Restriction Site";
// // 		
// // 			propValueCell.className = "detailedView_value";
// // 			propValueCell.setAttribute("white-space", "nowrap");
// // 			propValueCell.style.fontSize = "9pt";
// // 			propValueCell.colSpan = 5;
// // 			propValueCell.style.paddingLeft = "5px";
// 			
// 			// Generate the list on the fly
// 			if (createNew == "true")
// 				propNamePrefix = rType + "_"
// 			else
// 				propNamePrefix = ""
// 			
// 			var tmpMiscList = document.getElementById(propNamePrefix + "restriction_site_list");
// 			var miscSelectedInd = tmpMiscList.selectedIndex;
// 			var miscSelectedValue = tmpMiscList[miscSelectedInd].value;
// 
// 			var newMiscList = document.createElement("SELECT");
// 			var newMiscTxt =  document.createElement("INPUT");
// 
// // 			newMiscList.setAttribute("name", prefix + propName + postfix);
// 
// 			var rowIndex;
// 
// 			if (!document.getElementById(propNamePrefix + "restriction_site_row_" + propRow.rowIndex + "_id"))
// 			{
// 				propRow.setAttribute('id', propNamePrefix + "restriction_site_row_" + propRow.rowIndex + "_id");
// 				rowIndex = propRow.rowIndex;
// 			}
// 			else
// 			{
// 				for (i = 0; i < propsTbl.rows.length; i++)
// 				{
// 					if (!document.getElementById(propNamePrefix + "restriction_site_row_" + i + "_id"))
// 					{
// 						propRow.setAttribute('id', propNamePrefix + "restriction_site_row_" + i + "_id");
// 						break;
// 					}
// 				}
// 
// 				propRow.setAttribute('id', propNamePrefix + "restriction_site_row_" + i + "_id");
// 				rowIndex = i;
// 			}
// 
// 			newMiscList.setAttribute("id", propNamePrefix + propName + "_proplist_" + rowIndex);
// 			newMiscList.style.fontSize = "7pt";
// 
// // 			propRow.setAttribute('id', "miscellaneous_row_" + propRow.rowIndex + "_id");
// 
// 			newMiscTxt.type = "TEXT";
// 			newMiscTxt.id = propNamePrefix + "restriction_site_txt_" + (rowIndex - 1);
// 			newMiscTxt.style.display = "none";
// 
// 			for (i = 0; i < tmpMiscList.options.length; i++)
// 			{
// 				tmpOptn = tmpMiscList.options[i];
// 				newOptn = document.createElement("OPTION");
// 
// 				newOptn.value = tmpOptn.value;
// 				newOptn.name = tmpOptn.name;
// 				newOptn.text = tmpOptn.text;
// 
// 				if (newMiscList.options.length == 0)
// 					newMiscList.options.add(newOptn);
// 				else
// 					addElement(newMiscList, newMiscList.options.length, newOptn);
// 			}
// 
// 			propValueCell.appendChild(newMiscList);
// 			propValueCell.appendChild(newMiscTxt);
// 
// 			// Start & stop positions
// 			// April 1, 2008: Show in a new row
// // 			var miscPosRow = propsTbl.insertRow(rowIndex+1);
// // 			miscPosRow.setAttribute('id', "miscellaneous_pos_row" + rowIndex + "_id");
// // 			var miscPosRowID = miscPosRow.id;
// // 
// // 			var c1 = miscPosRow.insertCell(0);
// // 			var miscPosCell = miscPosRow.insertCell(1);
// // 
// // 			miscPosCell.className = "detailedView_value";
// // 			miscPosCell.style.paddingLeft = "5px";
// // 			miscPosCell.colSpan = 5;
// // 			miscPosCell.setAttribute("white-space", "nowrap");
// 
// // 			miscPosCell.innerHTML = "Start:&nbsp;";
// 
// 			var newMiscStart = document.createElement("INPUT");
// 			newMiscStart.setAttribute("type", 'TEXT');
// 			newMiscStart.setAttribute("size", 5);
// 			newMiscStart.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_startpos_id");
// 			newMiscStart.style.fontSize = "7pt";
// 
// // 			miscPosCell.appendChild(newMiscStart);
// 			propStartCell.appendChild(newMiscStart);
// 
// // 			miscPosCell.innerHTML += "&nbsp;&nbsp;End:&nbsp;";
// 			
// 			var newMiscEnd = document.createElement("INPUT");
// 			newMiscEnd.setAttribute("type", 'TEXT');
// 			newMiscEnd.setAttribute("size", 5);
// 			newMiscEnd.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_endpos_id");
// 			newMiscEnd.style.fontSize = "7pt";
// 
// // 			miscPosCell.appendChild(newMiscEnd);
// 			propEndCell.appendChild(newMiscEnd);
// 
// // 			newMiscList.onchange = function()
// // 			{
// // // 				showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);
// // 			};
// 			newMiscList.onchange = function(){this.setAttribute('name', propNamePrefix + propName + "_" + this[this.selectedIndex].value + "_" + rowIndex);};
// 
// 			// Orientation - March 14/08: Make 'forward' checked by default
// // 			miscPosCell.innerHTML += "<SPAN style=\"margin-left:10px;\"></span>";
// 
// 			var fwdDir = document.createElement("INPUT");
// 			fwdDir.setAttribute("type", "radio");
// 			fwdDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_fwd_dir");
// // 			fwdDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			fwdDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 			fwdDir.setAttribute("checked", true);
// 
// // 			miscPosCell.appendChild(fwdDir);
// 			propDirCell.appendChild(fwdDir);
// // 			miscPosCell.innerHTML += "Forward&nbsp;";
// 			propDirCell.innerHTML += "Forward&nbsp;";
// 
// 			var revDir = document.createElement("INPUT");
// 			revDir.setAttribute("type", "radio");
// 			revDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_rev_dir");
// // 			revDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			revDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// // 			miscPosCell.appendChild(revDir);
// // 			miscPosCell.innerHTML += "Reverse";
// 			propDirCell.appendChild(revDir);
// 			propDirCell.innerHTML += "Reverse";
// 
// 			propDirCell.style.fontSize = "7pt";
// 			propDirCell.style.whiteSpace = "nowrap";
// 
// 			// Add 'Remove' link
// 			var removeLink = document.createElement("SPAN");
// 			removeLink.className = "linkShow";
// 			removeLink.style.fontSize = "7pt";
// 			removeLink.style.fontWeight = "normal";
// 			removeLink.style.marginLeft = "5px";
// 			removeLink.innerHTML = "Remove";
// 		
// // 			miscPosCell.appendChild(removeLink);
// 			rmvCell.appendChild(removeLink);
// 
// 			// Adjust border height to stretch to bottom of page
// // 			mainBorder = document.getElementById("mainBorder");
// // 			mainBorder.height = document.height;
// // 			mainBorder.rowSpan = propsTbl.rows.length;
// 		
// 			// Add a separator row
// // 			var divRow = propsTbl.insertRow(rowIndex + 1);
// // 			var divCell = divRow.insertCell(0);
// 
// // 			divRow.setAttribute('id', "div_row" + divRow.rowIndex + "_id");
// // 			divCell.colSpan = 6;
// // 			divCell.innerHTML = "<HR>";
// 
// 			// 'Remove' link
// // 			var tblID = "modifyReagentPropsTbl";
// 			var miscRowID = propRow.id;
// // 			var divRowID = divRow.id;
// 		
// // 			// Add list to form
// // 			if (form_name == "")
// // 				var myForm = document.reagentDetailForm;
// // 			else
// // 			{
// // 				var myForm;
// // 			
// // 				docForms = document.forms;
// // 			
// // 				for (i = 0; i < docForms.length; i++)
// // 				{
// // 					aForm = docForms[i];
// // 			
// // 					if (aForm.name == form_name)
// // 					{
// // 						myForm = aForm;
// // 						break;
// // 					}
// // 				}
// // 			}
// 
// 			var hiddenMisc = document.createElement("INPUT");
// 			hiddenMisc.setAttribute("type", "hidden");
// 			myForm.appendChild(hiddenMisc);
// 
// 			var hiddenMiscStart = document.createElement("INPUT");
// 			hiddenMiscStart.setAttribute("type", "hidden");
// 			hiddenMiscStart.setAttribute("id", propNamePrefix + "restriction_site_startpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMiscStart);
// 
// 			var hiddenMiscEnd = document.createElement("INPUT");
// 			hiddenMiscEnd.setAttribute("type", "hidden");
// 			hiddenMiscEnd.setAttribute("id", propNamePrefix + "restriction_site_endpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMiscEnd);
// 
// 			// Orientation
// 			var hiddenMiscDir = document.createElement("INPUT");
// 			hiddenMiscDir.setAttribute('type', 'hidden');
// 			hiddenMiscDir.setAttribute("id", propNamePrefix + "restriction_site_orientation_prop_" + rowIndex);
// 			myForm.appendChild(hiddenMiscDir);
// 
// 			var elemList = new Array();
// 			elemList.push(hiddenMisc);
// 			elemList.push(hiddenMiscStart);
// 			elemList.push(hiddenMiscEnd);
// 			elemList.push(hiddenMiscDir);
// 
// // 			removeLink.onclick = function(){deleteRow(tblID, miscRowID); deleteRow(tblID, miscPosRowID); deleteRow(tblID, divRowID); removeFormElements(myForm, elemList)};
// 
// 			removeLink.onclick = function(){deleteTableRow(tblID, miscRowID); removeFormElements(myForm, elemList)};
// 		break;
// 
// 		case 'intron':
// 			var propRow = propsTbl.insertRow(propList.rowIndex - 2);
// 		
// 			// Need a few cells in the row
// 			var propNameCell = propRow.insertCell(0);
// 			var propValueCell = propRow.insertCell(1);
// 			var propDescrCell = propRow.insertCell(2);
// 			var propStartCell = propRow.insertCell(3);
// 			var propEndCell = propRow.insertCell(4);
// 			var propDirCell = propRow.insertCell(5);
// 			var rmvCell = propRow.insertCell(6);
// 		
// 			// Format cells	
// 			propNameCell.style.paddingLeft = "7px";
// 			propNameCell.style.backgroundColor = "#F5F5DC";
// 			propNameCell.style.fontSize = "7pt";		// nov. 12/08
// 		
// 			propValueCell.style.paddingLeft = "7px";
// 			propValueCell.style.paddingRight = "5px";
// 			propValueCell.style.paddingTop = "1px";
// 			propValueCell.style.paddingBottom = "1px";
// 			propValueCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propDescrCell.style.paddingLeft = "7px";
// 			propDescrCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propStartCell.style.textAlign = "left";
// 			propStartCell.style.paddingLeft = "7px";
// 			propStartCell.style.paddingRight = "2px";
// 			propStartCell.style.backgroundColor = "#F5F5DC";
// 		
// 			propEndCell.style.textAlign = "left";
// 			propEndCell.style.backgroundColor = "#F5F5DC";
// 			propEndCell.style.paddingLeft = "7px";
// 			propEndCell.style.paddingRight = "2px";
// 
// 			propDirCell.style.textAlign = "left";
// 			propDirCell.style.paddingLeft = "5px";
// 			propDirCell.style.paddingRight = "5px";
// 			propDirCell.style.backgroundColor = "#F5F5DC";
// 		
// 			rmvCell.style.textAlign = "left";
// 
// 			// Set cell content and formatting as required by template	
// 			propNameCell.innerHTML = "Intron";
// 			propValueCell.setAttribute("white-space", "nowrap");
// 			
// 			// Generate the list on the fly
// 			if (createNew == "true")
// 				propNamePrefix = rType + "_"
// 			else
// 				propNamePrefix = ""
// 
// 			var tmpTagTypeList = document.getElementById(propNamePrefix + "intron_list");
// 
// 			var newTagTypeList = document.createElement("SELECT");
// 			var newTagTypeTxt =  document.createElement("INPUT");
// 
// 			newTagTypeList.style.fontSize = "7pt";		// Nov. 12/08
// 
// 			var rowIndex;
// 
// 			if (!document.getElementById(propNamePrefix + "intron_row_" + propRow.rowIndex + "_id"))
// 			{
// 				propRow.setAttribute('id', propNamePrefix + "intron_row_" + propRow.rowIndex + "_id");
// 				rowIndex = propRow.rowIndex;
// 			}
// 			else
// 			{
// 				for (i = 0; i < propsTbl.rows.length; i++)
// 				{
// 					if (!document.getElementById(propNamePrefix + "intron_row_" + i + "_id"))
// 					{
// 						propRow.setAttribute('id', propNamePrefix + "intron_row_" + i + "_id");
// 						break;
// 					}
// 				}
// 
// 				propRow.setAttribute('id', propNamePrefix + "intron_row_" + i + "_id");
// 				rowIndex = i;
// 			}
// 
// // 			newTagTypeList.setAttribute('name', prefix + propName + postfix);
// 			newTagTypeList.setAttribute('id', propNamePrefix + propName + "_proplist_" + rowIndex);
// 
// 			newTagTypeTxt.type = "TEXT";
// 			newTagTypeTxt.id = propNamePrefix + "intron_txt_" + rowIndex;
// 
// 			newTagTypeTxt.style.display = "none";
// 			newTagTypeTxt.style.fontSize = "9pt";
// 
// 			for (i = 0; i < tmpTagTypeList.options.length; i++)
// 			{
// 				tmpOptn = tmpTagTypeList.options[i];
// 				newOptn = document.createElement("OPTION");
// 
// 				newOptn.value = tmpOptn.value;
// 				newOptn.name = tmpOptn.name;
// 				newOptn.text = tmpOptn.text;
// 
// 				if (newTagTypeList.options.length == 0)
// 					newTagTypeList.options.add(newOptn);
// 				else
// 					addElement(newTagTypeList, newTagTypeList.options.length, newOptn);
// 			}
// 
// // 			newTagTypeList.onchange = showTagTypeBox;
// 
// 			newTagTypeList.onchange = function(){this.setAttribute('name', propNamePrefix + propName + "_" + this[this.selectedIndex].value + "_" + rowIndex); showTagTypeBox(newTagTypeList.id, newTagTypeTxt.id);};
// 
// 			propValueCell.appendChild(newTagTypeList);
// 			propValueCell.appendChild(newTagTypeTxt);
// 
// 			var newTagTypeStart = document.createElement("INPUT");
// 			newTagTypeStart.setAttribute("type", 'TEXT');
// 			newTagTypeStart.setAttribute("size", 5);
// 			newTagTypeStart.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_startpos_id");
// 			newTagTypeStart.style.fontSize = "7pt";		// Nov. 12/08
// 
// 			propStartCell.appendChild(newTagTypeStart);
// 
// 			var newTagTypeEnd = document.createElement("INPUT");
// 			newTagTypeEnd.setAttribute("type", 'TEXT');
// 			newTagTypeEnd.setAttribute("size", 5);
// 			newTagTypeEnd.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_endpos_id");
// 			newTagTypeEnd.style.fontSize = "7pt";		// Nov. 12/08
// 
// 			propEndCell.appendChild(newTagTypeEnd);
// 
// 			// Orientation
// 			propDirCell.style.fontSize = "7pt";		// Nov. 12/08
// 			propDirCell.style.whiteSpace = "nowrap";
// 			
// 			var fwdDir = document.createElement("INPUT");
// 			fwdDir.setAttribute("type", "radio");
// 			fwdDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_fwd_dir");
// //  			fwdDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
// 			fwdDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// 			fwdDir.setAttribute("checked", true);
// 
// 			propDirCell.appendChild(fwdDir);
// 			propDirCell.innerHTML += "Forward&nbsp;";
// 
// 			var revDir = document.createElement("INPUT");
// 			revDir.setAttribute("type", "radio");
// 			revDir.setAttribute("id", propNamePrefix + propName + "_" + rowIndex + "_rev_dir");
// // 			revDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
//  			revDir.setAttribute("name", propNamePrefix + propName + "_orientation_prop_" + rowIndex);
// 
// 			propDirCell.appendChild(revDir);
// 			propDirCell.innerHTML += "Reverse";
// 
// 			// Add 'Remove' link
// 			var removeLink = document.createElement("SPAN");
// 			removeLink.className = "linkShow";
// 			removeLink.style.fontSize = "7pt";
// 			removeLink.style.fontWeight = "normal";
// 			removeLink.style.marginLeft = "5px";
// 			removeLink.innerHTML = "Remove";
// 
// 			rmvCell.appendChild(removeLink);
// 
// 			// Gather the IDs of all elements in the row to pass to 'deleteRow' function
// // 			var tblID = "modifyReagentPropsTbl";
// 			var tagTypeRowID = propRow.id;
// 
// 			var hiddenTagType = document.createElement("INPUT");
// 			hiddenTagType.setAttribute("type", "hidden");
// 			hiddenTagType.setAttribute("id", propNamePrefix + "intron_prop" + rowIndex);
// 			myForm.appendChild(hiddenTagType);
// 
// 			var hiddenTagTypeStart = document.createElement("INPUT");
// 			hiddenTagTypeStart.setAttribute("type", "hidden");
// 			hiddenTagTypeStart.setAttribute("id", propNamePrefix + "intron_startpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenTagTypeStart);
// 
// 			var hiddenTagTypeEnd = document.createElement("INPUT");
// 			hiddenTagTypeEnd.setAttribute("type", "hidden");
// 			hiddenTagTypeEnd.setAttribute("id", propNamePrefix + "intron_endpos_prop_" + rowIndex);
// 			myForm.appendChild(hiddenTagTypeEnd);
// 
// 			// Orientation
// 			var hiddenTagTypeDir = document.createElement("INPUT");
// 			hiddenTagTypeDir.setAttribute('type', 'hidden');
// 			hiddenTagTypeDir.setAttribute("id", propNamePrefix + "intron_orientation_prop_" + rowIndex);
// 			myForm.appendChild(hiddenTagTypeDir);
// 
// 			var elemList = new Array();
// 			elemList.push(hiddenTagType);
// 			elemList.push(hiddenTagTypeStart);
// 			elemList.push(hiddenTagTypeEnd);
// 			elemList.push(hiddenTagTypeDir);
// 
// 			removeLink.onclick = function(){deleteTableRow(tblID, tagTypeRowID); removeFormElements(myForm, elemList)};
// 		break;

		case '':
		break;

		default:
// alert(rType);
			var propRow = propsTbl.insertRow(propList.rowIndex - 2);
		
			// Need a few cells in the row
			var propNameCell = propRow.insertCell(0);
			var propValueCell = propRow.insertCell(1);
			var propDescrCell = propRow.insertCell(2);
			var propStartCell = propRow.insertCell(3);
			var propEndCell = propRow.insertCell(4);
			var propDirCell = propRow.insertCell(5);
			var rmvCell = propRow.insertCell(6);
		
			// Format cells	
			propNameCell.style.paddingLeft = "7px";
			propNameCell.style.backgroundColor = "#F5F5DC";
			propNameCell.style.fontSize = "7pt";		// nov. 12/08
			propNameCell.style.whiteSpace = "nowrap";
		
			propValueCell.style.paddingLeft = "7px";
			propValueCell.style.paddingRight = "5px";
			propValueCell.style.paddingTop = "1px";
			propValueCell.style.paddingBottom = "1px";
			propValueCell.style.backgroundColor = "#F5F5DC";
		
			propDescrCell.style.paddingLeft = "7px";
			propDescrCell.style.backgroundColor = "#F5F5DC";
		
			propStartCell.style.textAlign = "left";
			propStartCell.style.paddingLeft = "7px";
			propStartCell.style.paddingRight = "2px";
			propStartCell.style.backgroundColor = "#F5F5DC";
		
			propEndCell.style.textAlign = "left";
			propEndCell.style.backgroundColor = "#F5F5DC";
			propEndCell.style.paddingLeft = "7px";
			propEndCell.style.paddingRight = "2px";

			propDirCell.style.textAlign = "left";
			propDirCell.style.paddingLeft = "5px";
			propDirCell.style.paddingRight = "5px";
			propDirCell.style.backgroundColor = "#F5F5DC";
		
			rmvCell.style.textAlign = "left";

			// Set cell content and formatting as required by template
			propDescrInput = document.getElementById(rType + "_" + propName + "_descr");
			
			if (propDescrInput)
				propDescr = propDescrInput.value;
			else
				propDescr = propName;

// 			propNameCell.innerHTML = propName;
			propNameCell.innerHTML = propDescr;

			propValueCell.setAttribute("white-space", "nowrap");
			
			// Generate the list on the fly
// 			if ((createNew == "true") || (rType != ""))
			if (createNew == "true")
				propNamePrefix = rType + "_"
			else
				propNamePrefix = ""

// alert(propNamePrefix + propName + "_:_list");

			var tmpTagTypeList = document.getElementById(propNamePrefix + propName + "_:_list");

			var newTagTypeList = document.createElement("SELECT");
			var newTagTypeTxt =  document.createElement("INPUT");

			newTagTypeList.style.fontSize = "7pt";		// Nov. 12/08

			var rowIndex;

			if (!document.getElementById(propNamePrefix + propName + "_:_row_" + propRow.rowIndex + "_id"))
			{
				propRow.setAttribute('id', propNamePrefix + propName + "_:_row_" + propRow.rowIndex + "_id");
				rowIndex = propRow.rowIndex;
			}
			else
			{
				for (i = 0; i < propsTbl.rows.length; i++)
				{
					if (!document.getElementById(propNamePrefix + propName + "_:_row_" + i + "_id"))
					{
						propRow.setAttribute('id', propNamePrefix + propName + "_:_row_" + i + "_id");
						break;
					}
				}

				propRow.setAttribute('id', propNamePrefix + propName + "_row_" + i + "_id");
				rowIndex = i;
			}

// 			newTagTypeList.setAttribute('name', prefix + propName + postfix);
			newTagTypeList.setAttribute('id', propNamePrefix + propName + "_:_proplist_" + rowIndex);

// alert(propNamePrefix);
			newTagTypeTxt.type = "TEXT";
			newTagTypeTxt.id = propNamePrefix + propName + "_txt_" + rowIndex;

			newTagTypeTxt.onKeyPress = function(){return disableEnterKey(event);};

// alert(newTagTypeTxt.id);

			newTagTypeTxt.style.display = "none";
			newTagTypeTxt.style.fontSize = "9pt";

			for (i = 0; i < tmpTagTypeList.options.length; i++)
			{
				tmpOptn = tmpTagTypeList.options[i];
				newOptn = document.createElement("OPTION");

				newOptn.value = tmpOptn.value;
				newOptn.name = tmpOptn.name;
				newOptn.text = tmpOptn.text;

				if (newTagTypeList.options.length == 0)
					newTagTypeList.options.add(newOptn);
				else
					addElement(newTagTypeList, newTagTypeList.options.length, newOptn);
			}

// 			newTagTypeList.onchange = showTagTypeBox;

			newTagTypeList.onchange = function(){this.setAttribute('name', propNamePrefix + propName + "_:_" + this[this.selectedIndex].value + "_" + rowIndex); showSpecificOtherTextbox(newTagTypeList.id, newTagTypeTxt.id);};

			propValueCell.appendChild(newTagTypeList);
			propValueCell.appendChild(newTagTypeTxt);

			// Descriptor - for selected features
			if ((propName == 'tag') || (propName == 'promoter'))
			{
				if (propName == "tag")
					propDescrAlias = "tag_position";
				else if (propName == "promoter")
					propDescrAlias = "expression_system";
	
				var oldTagPosList = document.getElementById(propNamePrefix + propDescrAlias + "_:_list");
// 	alert(oldTagPosList.id);
				if (oldTagPosList)
				{
					var newTagPosList = document.createElement("SELECT");
					var newTagPosTxt =  document.createElement("INPUT");

					newTagPosTxt.type = "TEXT";
					newTagPosTxt.id = propNamePrefix + propDescrAlias + "_txt_" + rowIndex;
					newTagPosTxt.onKeyPress = function(){return disableEnterKey(event);};
		
					newTagPosTxt.style.display = "none";
					newTagPosTxt.style.fontSize = "9pt";

					newTagPosList.setAttribute('id', propNamePrefix + propDescrAlias + "_:_proplist_" + rowIndex);
// 		alert(newTagPosList.id);
					newTagPosList.style.fontSize = "7pt";		// Nov. 12/08
		
					for (i = 0; i < oldTagPosList.options.length; i++)
					{
						tmpOptn = oldTagPosList.options[i];
						newOptn = document.createElement("OPTION");
		
						newOptn.value = tmpOptn.value;
						newOptn.name = tmpOptn.name;
						newOptn.text = tmpOptn.text;
		
						if (newTagPosList.options.length == 0)
							newTagPosList.options.add(newOptn);
						else
							addElement(newTagPosList, newTagPosList.options.length, newOptn);
					}

					newTagPosList.onchange = function(){this.setAttribute('name', propNamePrefix + propDescrAlias + "_:_" + this[this.selectedIndex].value + "_" + rowIndex); showSpecificOtherTextbox(newTagPosList.id, newTagPosTxt.id);};

					propDescrCell.appendChild(newTagPosList);
					propDescrCell.appendChild(newTagPosTxt);
				}

				// Tag position
				var hiddenTagPosField = document.createElement("INPUT");
				hiddenTagPosField.setAttribute('type', 'hidden');
				hiddenTagPosField.setAttribute("id", propNamePrefix + propDescrAlias  + "_:_prop_" + rowIndex);
// 	alert(hiddenTagPosField.id);
				myForm.appendChild(hiddenTagPosField);
			}

			var newTagTypeStart = document.createElement("INPUT");
			newTagTypeStart.setAttribute("type", 'TEXT');
			newTagTypeStart.setAttribute("size", 5);
			newTagTypeStart.setAttribute("id", propNamePrefix + propName + "_:_" + rowIndex + "_startpos_id");
			newTagTypeStart.style.fontSize = "7pt";		// Nov. 12/08
			newTagTypeStart.onKeyPress = function(){return disableEnterKey(event);};

			propStartCell.appendChild(newTagTypeStart);

			var newTagTypeEnd = document.createElement("INPUT");
			newTagTypeEnd.setAttribute("type", 'TEXT');
			newTagTypeEnd.setAttribute("size", 5);
			newTagTypeEnd.setAttribute("id", propNamePrefix + propName + "_:_" + rowIndex + "_endpos_id");
			newTagTypeEnd.style.fontSize = "7pt";		// Nov. 12/08
			newTagTypeEnd.onKeyPress = function(){return disableEnterKey(event);};

			propEndCell.appendChild(newTagTypeEnd);

			// Orientation
			propDirCell.style.fontSize = "7pt";		// Nov. 12/08
			propDirCell.style.whiteSpace = "nowrap";
			
			var fwdDir = document.createElement("INPUT");
			fwdDir.setAttribute("type", "radio");
			fwdDir.setAttribute("id", propNamePrefix + propName + "_:_" + rowIndex + "_fwd_dir");
//  			fwdDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
			fwdDir.setAttribute("name", propNamePrefix + propName + "_:_orientation_prop_" + rowIndex);

			fwdDir.setAttribute("checked", true);

			propDirCell.appendChild(fwdDir);
			propDirCell.innerHTML += "Forward&nbsp;";

			var revDir = document.createElement("INPUT");
			revDir.setAttribute("type", "radio");
			revDir.setAttribute("id", propNamePrefix + propName + "_:_" + rowIndex + "_rev_dir");
// 			revDir.setAttribute("name", propName + "_orientation_radio_" + rowIndex);
 			revDir.setAttribute("name", propNamePrefix + propName + "_:_orientation_prop_" + rowIndex);

			propDirCell.appendChild(revDir);
			propDirCell.innerHTML += "Reverse";

			// Add 'Remove' link
			var removeLink = document.createElement("SPAN");
			removeLink.className = "linkShow";
			removeLink.style.fontSize = "7pt";
			removeLink.style.fontWeight = "normal";
			removeLink.style.marginLeft = "5px";
			removeLink.innerHTML = "Remove";

			rmvCell.appendChild(removeLink);

			// Gather the IDs of all elements in the row to pass to 'deleteRow' function
// 			var tblID = "modifyReagentPropsTbl";
			var tagTypeRowID = propRow.id;

			var hiddenTagType = document.createElement("INPUT");
			hiddenTagType.setAttribute("type", "hidden");
			hiddenTagType.setAttribute("id", propNamePrefix + propName + "_:_prop" + rowIndex);
			myForm.appendChild(hiddenTagType);

			var hiddenTagTypeStart = document.createElement("INPUT");
			hiddenTagTypeStart.setAttribute("type", "hidden");
			hiddenTagTypeStart.setAttribute("id", propNamePrefix + propName + "_:_startpos_prop_" + rowIndex);
			myForm.appendChild(hiddenTagTypeStart);

			var hiddenTagTypeEnd = document.createElement("INPUT");
			hiddenTagTypeEnd.setAttribute("type", "hidden");
			hiddenTagTypeEnd.setAttribute("id", propNamePrefix + propName + "_:_endpos_prop_" + rowIndex);
			myForm.appendChild(hiddenTagTypeEnd);

			// Orientation
			var hiddenTagTypeDir = document.createElement("INPUT");
			hiddenTagTypeDir.setAttribute('type', 'hidden');
			hiddenTagTypeDir.setAttribute("id", propNamePrefix + propName + "_:_orientation_prop_" + rowIndex);
			myForm.appendChild(hiddenTagTypeDir);

			var elemList = new Array();
			elemList.push(hiddenTagType);
			elemList.push(hiddenTagTypeStart);
			elemList.push(hiddenTagTypeEnd);
			elemList.push(hiddenTagTypeDir);

			removeLink.onclick = function(){deleteTableRow(tblID, tagTypeRowID); removeFormElements(myForm, elemList)};
		break;
	}

// alert(propsTbl.rows.length);

// for (i=0; i < myForm.elements.length; i++)
// {
// tmpEl = myForm.elements[i];
// alert(tmpEl.id);
// alert(tmpEl.name);
// alert(tmpEl.value);
// }

	propNamesList.selectedIndex = 0;
}


// May 6/09: multiple accessions on Insert modification view
function addAccessionRow(annosTblID, inputName, rowCount, colCount)
{
	var annosTbl = document.getElementById(annosTblID);
// 	var accRow = annosTbl.insertRow(1);	// insert after the first row in annotations table - accessions
	var accRow = annosTbl.insertRow(rowCount);
	var firstPlaceholderCell = accRow.insertCell(0);
// 	var accCell = accRow.insertCell(1);
	var accCell = accRow.insertCell(colCount);

	accCell.colspan = 5;
	accCell.className ="detailedView_value";
	accCell.style.paddingLeft = "3px";

	accCell.innerHTML = "<INPUT type=text value=\"\" name=\"" + inputName + "\"><BR>";
}

function hasArrayElement(my_array, my_elem)
{
	var i;

	for (i = 0; i < my_array.length; i++)
	{
		tmpEl = my_array[i];

		if (tmpEl == my_elem)
			return true;
	}

	return false;
}

// Oct. 22/08, updated May 25/09
function changeFieldNames(form_name, tblID, rType)
{
// alert("In changeFieldNames");

	if (tblID)
		var propTbl = document.getElementById(tblID);
	else
		var propTbl = document.getElementById("modifyReagentPropsTbl");

// alert(propTbl);
// alert(rType);
	var i, j, k, z;

	var prefix = "reagent_detailedview_";
	var postfix = "_prop";

	// Modified May 26/09: Cannot assume pre-defined features anymore
	if (rType)
		var featuresList = document.getElementById("sequence_property_names_" + rType);
	else
		var featuresList = document.getElementById("sequence_property_names");

// 	alert(featuresList);

	var reagentFeaturesList = new Array();

	if (featuresList)
	{
		for (a=0; a < featuresList.options.length; a++)
		{
			fAliasTmp = featuresList.options[a].value;
	// 		alert(fAliasTmp);
			reagentFeaturesList.push(fAliasTmp);
		}
	}
// 	// debug
// 	for (x=0; x < reagentFeaturesList.length; x++)
// 		alert(reagentFeaturesList[x]);

// 	removed May 26/09
// 	var reagentFeaturesList = ["tag_type", "promoter", "selectable_marker", "origin", "polyA", "polya", "miscellaneous", "cleavage_site", "transcription_terminator", "intron", "restriction_site"];
	
	var reagentFeatureDescriptors = {"tag":"tag_position", "promoter":"expression_system"}
	
	var oldPropValue, newPropValue, oldStartPos, oldEndPos, pStartList, pEndList, pDirList, newDirName, descriptor;

	docForms = document.forms;

	if (propTbl)
	{
		for (b=1; b <= propTbl.rows.length; b++)
		{
			var aRow = propTbl.rows[b];
	
			if (aRow)
			{
// 				alert("Row " + aRow.id);
				aRowID = aRow.id;
// 				propName = aRowID.substring(0, aRowID.indexOf("_row"));
				propName = aRowID.substring(0, aRowID.indexOf("_:_"));
// alert(propName);

				if (hasArrayElement(reagentFeaturesList, propName))
				{
// 					alert("Prop name " + propName);
// 					alert(aRowID);
					oldPropValue = aRowID.substring((propName+"_:_row_").length, aRowID.indexOf("_start_"));
// 					alert("Old value " + oldPropValue);

					oldStartPos = aRowID.substring(aRowID.indexOf("_start_")+"_start_".length, aRowID.indexOf("_end_"));
// 					alert("Old start " + oldStartPos);
	
					oldEndPos = aRowID.substring(aRowID.indexOf("_end_")+"_end_".length);
// 					alert("Old end " + oldEndPos);
	
// alert(prefix + propName + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_startpos" + postfix);

					pStartList = document.getElementsByName(prefix + propName + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_startpos" + postfix);
	
// 					alert(pStartList.length);
	
					if (pStartList && pStartList.length > 0)
					{
						newStart = pStartList[0].value;
// 						alert("New start " + newStart);
					}
					else
						continue;
	
					pEndList = document.getElementsByName(prefix + propName + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_endpos" + postfix);
	
					if (pEndList && pEndList.length > 0)
					{
						newEnd = pEndList[0].value;
// 						alert("New end " + newEnd);
					}
					else
						continue;
	
				//	alert(pEndList.length);
					
					if (newStart && newEnd)
					{
// alert("New end " + newEnd);
// alert("New start " + newStart);
						oldDirName = prefix + propName + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_orientation" + postfix;
						pDirList = document.getElementsByName(oldDirName);	// fwd and rev
	
						fwd_dir = pDirList[0];
						rev_dir = pDirList[1];
	
						fieldName = prefix + propName + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + postfix;
// alert(fieldName);
						propField = document.getElementsByName(fieldName)[0];
	
						newPropValue = propField[propField.selectedIndex].value;
// 						alert(newPropValue);

						if (newPropValue.toLowerCase() == 'other')
						{
// alert("HERE");
							if (rType && (rType != ""))
							{
								if (document.getElementById(prefix + rType + "_" + propName + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_txt"))
									newPropValue = document.getElementById(prefix + rType + "_" + propName + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_txt").value;

								// June 8, 2010
								else if (document.getElementById(prefix + propName + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_txt"))
									newPropValue = document.getElementById(prefix + propName + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_txt").value;

								else
									newPropValue = document.getElementById(rType + "_" + propName + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_txt").value;
// alert(newPropValue);
							}
							else
							{
								// if-else statements added March 29, 2010
								if (document.getElementById(prefix + propName + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_txt"))
									newPropValue = document.getElementById(prefix + propName + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_txt").value;
								else
									newPropValue = document.getElementById(propName + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_txt").value;
							}

// 							alert(newPropValue);
						}

						newStartName = prefix + propName + "_:_" + newPropValue + "_start_" + newStart + "_end_" + newEnd + "_startpos" + postfix;
	
						newEndName = prefix + propName + "_:_" + newPropValue + "_start_" + newStart + "_end_" + newEnd + "_endpos" + postfix;
	
						pStartList[0].setAttribute("name", newStartName);
						pEndList[0].setAttribute("name", newEndName);
	
						propField.setAttribute("name", prefix + propName + "_:_" + newPropValue + "_start_" + newStart + "_end_" + newEnd + postfix);
	
						newDirName = prefix + propName + "_:_" + newPropValue + "_start_" + newStart + "_end_" + newEnd + "_orientation" + postfix;
						
						fwd_dir.setAttribute("name", newDirName);
						rev_dir.setAttribute("name", newDirName);
	
						// description
						descriptor = reagentFeatureDescriptors[propName];
						
						if (descriptor)
						{
							oldDescrName = prefix + descriptor + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + postfix;
// 							alert(oldDescrName);
							descrField = document.getElementsByName(oldDescrName)[0];
// descrField = document.getElementById(oldDescrName);
	
							if (descrField)
							{
// 								alert(oldDescrName);
// 								alert(descrField.value);

								newDescrName = prefix + descriptor + "_:_" + newPropValue + "_start_" + newStart + "_end_" + newEnd + postfix;
// 								alert("Setting descriptor name: " + newDescrName);

								descrField.setAttribute("name", newDescrName);
								
								if (descrField.value.toLowerCase() == 'other')
								{
									txtID = prefix + descriptor + "_:_" + oldPropValue + "_start_" + oldStartPos + "_end_" + oldEndPos + "_txt";
// 									alert("text field ID " + txtID);

									textField = document.getElementById(txtID);

									if (textField)
									{
										newTxtName = prefix + descriptor + "_:_" + newPropValue + "_start_" + newStart + "_end_" + newEnd + "_name_txt";
// alert("Setting text field name " + newTxtName);
										textField.setAttribute("name", newTxtName);

										// alert("well, why not??");
										newDescr = document.getElementById(txtID).value;
										
// 										alert("new descr " + newDescrName);
										descrField.setAttribute("value", newDescr);
									}
								}
							}
						}
						
					}
					else
						continue;
				}
			}
		}
	}

	return;
}


// fieldName - current name to be changed
function setFieldName(aField, fieldName, propName, descriptor)
{
	var reagentFeatureDescriptors = {"tag":"tag_position", "promoter":"expression_system"}

	aField.setAttribute("name", fieldName);
}


// Note: rType is actually an ID, and rTypeID is the name of a reagent type!
function addParent(rType, rTypeID, assocAlias, assocName, assocID, rIndex)
{
// alert(rType);
// alert(rTypeID);

	var tableID = "category_assoc_section_" + rType;
// alert(tableID);
// 	alert("category_assoc_section_" + rTypeID);

	var rowID = rType + "_" + assocAlias + "_assoc_row_" + assocID + "_" + rIndex;
// alert(rowID);
	var parentTable = document.getElementById(tableID);
	var assocRow = document.getElementById(rowID);
// alert(assocRow);
// 	var newRow = parentTable.insertRow(assocRow.rowIndex+1);
	var newRow = parentTable.insertRow(parentTable.rows.length);

	// just generate the next integer in sequence to assign distinct row IDs
// 	newRow.id = rowID + "_" + parentTable.rowCount+1;
	var rCount = parentTable.rows.length-1;
// alert(newRow.rowIndex);
// alert(parentTable.rows.length-1);

	newRow.id = rType + "_" + assocAlias + "_assoc_row_" + assocID + "_" + rCount;

	var newInput = document.createElement("INPUT");

	newInput.type = "text";
// 	newInput.setAttribute("name", "assoc_" + assocAlias + "_prop");		// NO!!!!!!!!!!!!!!

	// replaced Feb. 12/10
	newInput.setAttribute("name", rTypeID + "_assoc_" + assocID + "_prop");
// alert(newInput.name);

	newInput.onKeyPress=function(){return disableEnterKey(event)};

	// Update Feb. 17/10 - replaced rTypeID with rType
	newInput.id = rTypeID + "_assoc_" + assocAlias + "_input";

	var cell_1 = newRow.insertCell(0);
	var cell_2 = newRow.insertCell(1);


	// Different formatting for creation and modification views
	if (document.getElementById("createReagentForm" + rType))
	{
		newRow.style.backgroundColor = "#F5F5DC;";
	
		cell_1.style.paddingLeft = "15px";
		cell_1.style.fontSize = "8pt";
		cell_1.style.fontWeight = "bold";

		cell_2.style.paddingLeft="10px";
	}
	else
	{
		cell_1.style.paddingLeft = "40px";
		cell_2.style.paddingLeft="5px";	
	}

	cell_1.innerHTML = assocName;
	cell_2.appendChild(newInput);

	// 'Add' and 'Remove' links
	var removeLink = document.createElement("SPAN");

	removeLink.className = "linkExportSequence";
	removeLink.style.fontWeight = "normal";
	removeLink.style.marginLeft = "18px";
	removeLink.innerHTML = "Remove";

	removeLink.onclick = function(){deleteTableRow(tableID, newRow.id)};

	cell_2.appendChild(removeLink);
	newInput.focus();

	// Don't put an 'Add' link here - absolutely no point
}


function setFeaturePositions(rType)
{
// alert(rType);

// debugging (VERY long)
// 	var myForm = document.reagentDetailForm;
// 
// 	for (i = 0; i < myForm.elements.length; i++)
// 	{
// 		alert(myForm.elements[i].name);
// 		alert(myForm.elements[i].value);
// 	}

// 	alert("In setFeaturePositions");

	var allFields = document.getElementsByTagName("INPUT");
	var tmpInput;

	var prefix = "reagent_detailedview_";
	var postfix = "_prop";

	var propName = "";

	for (i = 0; i < allFields.length; i++)
	{
		tmpInput = allFields[i];

// 		alert(tmpInput.name);

		tmpStart = "";
		tmpEnd = "";

		if (tmpInput.type == "hidden")
		{
			tmpID = tmpInput.id;
// 			alert("Set positions " + tmpID);

			startInd = tmpID.indexOf("_:_startpos_prop");
			endInd = tmpID.indexOf("_:_endpos_prop");

			if (startInd > 0)
			{
// 				alert(tmpID);
				propName = tmpID.substring(0, startInd);
// 				alert("Prop name " + propName);

				// Oct. 28/09
				if (rType != "")
				{
					if (propName.indexOf(rType + "_") == 0)
					{
						rType_str = rType + "_";
						propName = tmpID.substring(rType_str.length, propName.length);
					}
				}

				propCount = tmpID.substr(startInd+("_:_startpos_prop_".length), tmpID.length);
// 				alert(propCount);

				if (rType && (rType != ""))
				{
					tmpList = document.getElementById(rType + "_" + propName + "_:_proplist_" + propCount);
				}
				else
				{
					// get the selected value from the corresponding list
					tmpList = document.getElementById(propName + "_:_proplist_" + propCount);
				}

// 				alert(tmpList.name);

				if (tmpList)
					tmpPropVal = tmpList[tmpList.selectedIndex].value;
				else
					tmpPropVal = "";
// 				alert(tmpPropVal);
	
				if (tmpPropVal.toLowerCase() == 'other')	// this is ok
				{
					if (rType && (rType != ""))
					{
						tmpPropVal = document.getElementById(rType + "_" + propName + "_txt_" + propCount).value;
					}
					else
					{
						tmpPropVal = document.getElementById(propName + "_txt_" + propCount).value;
					}

					// come back to this with 'Other'
// 					tmpPropVal = document.getElementById(propName + "_txt_" + propCount).value;
// 					alert(tmpPropVal);
				}

				if (rType && (rType != ""))
				{
					var tmpPropStart = document.getElementById(rType + "_" + propName + "_:_" + propCount + "_startpos_id").value;
				}
				else
				{
					var tmpPropStart = document.getElementById(propName + "_:_" + propCount + "_startpos_id").value;
				}

// 				alert("Start " + tmpPropStart);

				if (rType && (rType != ""))
				{
					var hiddenStart = document.getElementById(rType + "_" + propName + "_:_startpos_prop_" + propCount);
				}
				else
				{
					var hiddenStart = document.getElementById(propName + "_:_startpos_prop_" + propCount);
				}

				if (rType && (rType != ""))
				{
					var tmpPropEnd = document.getElementById(rType + "_" + propName + "_:_" + propCount + "_endpos_id").value;	// added Nov. 7/08
				}
				else
				{
					var tmpPropEnd = document.getElementById(propName + "_:_" + propCount + "_endpos_id").value;	// added Nov. 7/08
				}

				// Changed Nov. 7/08 - did not work on modification
// rmvd Nov. 7/08		var hiddenEnd = document.getElementById(propName + "_endpos_prop_" + propCount);
// rmvd Nov. 7/08		var tmpEnd = hiddenEnd.value;
// rmvd Nov. 7/08		hiddenStart.setAttribute("name", prefix + propName + "_" + tmpPropVal + "_start_" + tmpPropStart + "_end_" + tmpEnd + "_startpos_prop");

				if (rType && (rType != ""))
				{
					hiddenStart.setAttribute("name", prefix + rType + "_" + propName + "_:_" + tmpPropVal + "_start_" + tmpPropStart + "_end_" + tmpPropEnd + "_startpos_prop");		// added Nov. 7/08
				}
				else
				{
					hiddenStart.setAttribute("name", prefix + propName + "_:_" + tmpPropVal + "_start_" + tmpPropStart + "_end_" + tmpPropEnd + "_startpos_prop");		// added Nov. 7/08
				}

// 				alert("Hidden start field: " + hiddenStart.name);

				hiddenStart.setAttribute("value", tmpPropStart);
			}

			if (endInd > 0)
			{
// 				alert(tmpID);
				propName = tmpID.substring(0, endInd);
// 				alert(propName);

				// Oct. 28/09
				if (rType && (rType != ""))
				{
                                	if (propName.indexOf(rType + "_") == 0)
					{
						rType_str = rType + "_";
                                        	propName = tmpID.substring(rType_str.length, propName.length);
					}
				}

				propCount = tmpID.substr(endInd+("_:_endpos_prop_".length), tmpID.length);

// 				alert(propCount);

				// get the selected value from the corresponding list
				if (rType && (rType != ""))
				{
					tmpList = document.getElementById(rType + "_" + propName + "_:_proplist_" + propCount);
				}
				else
				{
					tmpList = document.getElementById(propName + "_:_proplist_" + propCount);
				}

				tmpPropVal = tmpList[tmpList.selectedIndex].value;
// 				alert("VAlue " + tmpPropVal);

				if (tmpPropVal.toLowerCase() == 'other')
				{
					if (rType && (rType != ""))
					{
						tmpPropVal = document.getElementById(rType + "_" + propName + "_txt_" + propCount).value;
					}
					else
					{
						tmpPropVal = document.getElementById(propName + "_txt_" + propCount).value;
					}

// 					alert(tmpPropVal);
				}


				if (rType && (rType != ""))
				{
					var tmpPropEnd = document.getElementById(rType + "_" + propName + "_:_" + propCount + "_endpos_id").value;
				}
				else
				{
					var tmpPropEnd = document.getElementById(propName + "_:_" + propCount + "_endpos_id").value;
				}

// 				alert("End " + tmpPropEnd);

				if (rType && (rType != ""))
				{
					var hiddenEnd = document.getElementById(rType + "_" + propName + "_:_endpos_prop_" + propCount);
				}
				else
				{
					var hiddenEnd = document.getElementById(propName + "_:_endpos_prop_" + propCount);
				}

// 				alert("Hidden end field " + hiddenEnd.name);

				if (rType && (rType != ""))
				{
					var hiddenStart = document.getElementById(rType + "_" + propName + "_:_startpos_prop_" + propCount);
				}
				else
				{
					var hiddenStart = document.getElementById(propName + "_:_startpos_prop_" + propCount);
				}

				var tmpStart = hiddenStart.value;

				if (rType && (rType != ""))
				{
					hiddenEnd.setAttribute("name", prefix + rType + "_" + propName + "_:_" + tmpPropVal + "_start_" + tmpStart + "_end_" + tmpPropEnd + "_endpos_prop");
				}
				else
				{
					hiddenEnd.setAttribute("name", prefix + propName + "_:_" + tmpPropVal + "_start_" + tmpStart + "_end_" + tmpPropEnd + "_endpos_prop");
				}

				hiddenEnd.setAttribute("value", tmpPropEnd);

				// Special cases: Descriptors - Tag Type property comes in conjunction with Tag Position; Promoter - with Expression System
				if (propName == "tag")
				{
					if (rType && (rType != ""))
					{
						var hiddenTagPosition = document.getElementById(rType + "_" + "tag_position_:_prop_" + propCount);
					}
					else
					{
						// set the hidden form field's value to the selected value of the visible list
						var hiddenTagPosition = document.getElementById("tag_position_:_prop_" + propCount);
					}

// alert("Old tag position name: " + hiddenTagPosition.name);

					if (rType && (rType != ""))
					{
						var origTagPosList = document.getElementById(rType + "_" + "tag_position_:_proplist_" + propCount);
					}
					else
					{
						var origTagPosList = document.getElementById("tag_position_:_proplist_" + propCount);
					}
	
					if (hiddenTagPosition)
					{
						if (rType && (rType != ""))
						{
							hiddenTagPosition.setAttribute("name", prefix + rType + "_tag_position_:_" + tmpPropVal + "_start_" + tmpPropStart + "_end_" + tmpPropEnd + postfix);
						}
						else
						{
							hiddenTagPosition.setAttribute("name", prefix + "tag_position_:_" + tmpPropVal + "_start_" + tmpPropStart + "_end_" + tmpPropEnd + postfix);
						}
					}

// alert("New tag position name: " + hiddenTagPosition.name);

					if (origTagPosList)
					{
						if (origTagPosList[origTagPosList.selectedIndex].value.toLowerCase() == 'other')
						{
							// grab the value in the textbox
							if (rType && (rType != ""))
							{
								var tagPosTxt = document.getElementById(rType + "_" + "tag_position" + "_txt_" + propCount);
							}
							else
							{
								var tagPosTxt = document.getElementById("tag_position" + "_txt_" + propCount);
							}
						
							hiddenTagPosition.setAttribute("value", tagPosTxt.value);
						}
						else
							hiddenTagPosition.setAttribute("value", origTagPosList[origTagPosList.selectedIndex].value);
					}

// alert("New tag position value: " + hiddenTagPosition.value);
				}
				else if (propName == "promoter")
				{
					if (rType && (rType != ""))
					{
						var hiddenExprSyst = document.getElementById(rType + "_" + "expression_system_:_prop_" + propCount);
					}
					else
					{
						var hiddenExprSyst = document.getElementById("expression_system_:_prop_" + propCount);
					}

					if (rType && (rType != ""))
					{
						var origExprSystList = document.getElementById(rType + "_" + "expression_system_:_proplist_" + propCount);
					}
					else
					{
						var origExprSystList = document.getElementById("expression_system_:_proplist_" + propCount);
					}

					if (hiddenExprSyst)
					{
						if (rType && (rType != ""))
						{
							hiddenExprSyst.setAttribute("name", prefix + rType + "_expression_system_:_" + tmpPropVal + "_start_" + tmpPropStart + "_end_" + tmpPropEnd + postfix);
						}
						else
						{
							hiddenExprSyst.setAttribute("name", prefix + "expression_system_:_" + tmpPropVal + "_start_" + tmpPropStart + "_end_" + tmpPropEnd + postfix);
						}
					}
// alert(hiddenExprSyst.name);
	
					if (origExprSystList)
					{
						if (origExprSystList[origExprSystList.selectedIndex].value.toLowerCase() == 'other')
						{
							// grab the value in the textbox
							if (rType && (rType != ""))
							{
								var expSystTxt = document.getElementById(rType + "_" + "expression_system" + "_txt_" + propCount);
							}
							else
							{
								var expSystTxt = document.getElementById("expression_system" + "_txt_" + propCount);
							}
						
							hiddenExprSyst.setAttribute("value", expSystTxt.value);
						}
						else
							hiddenExprSyst.setAttribute("value", origExprSystList[origExprSystList.selectedIndex].value);
					}
				}
			}

			// March 17/08: Orientation:
// alert(tmpID);
			orientInd = tmpID.indexOf("_:_orientation_prop_");

			if (orientInd > 0)
			{
				if (rType && (rType != ""))
					propName = tmpID.substring((rType + "_").length, orientInd);
				else
				{
// alert(tmpID);
					propName = tmpID.substring(0, orientInd);
				}
// alert(propName);
				propCount = tmpID.substr(orientInd+("_:_orientation_prop_".length), tmpID.length);

				if (rType && (rType != ""))
				{
					var tmpFwd = document.getElementById(rType + "_" + propName + "_:_" + propCount + "_fwd_dir");
					var tmpRev = document.getElementById(rType + "_" + propName + "_:_" + propCount + "_rev_dir");
				}
				else
				{
					var tmpFwd = document.getElementById(propName + "_:_" + propCount + "_fwd_dir");
					var tmpRev = document.getElementById(propName + "_:_" + propCount + "_rev_dir");
				}

				if (tmpFwd.checked)		// orientation <= forward - FAILS ON REMOVAL???
				{
					// get the selected value from the corresponding list
					if (rType && (rType != ""))
						tmpList = document.getElementById(rType + "_" + propName + "_:_proplist_" + propCount);
					else
						tmpList = document.getElementById(propName + "_:_proplist_" + propCount);

					tmpPropVal = tmpList[tmpList.selectedIndex].value;
// alert(tmpPropVal);
					if (tmpPropVal.toLowerCase() == 'other')
					{
						if (rType && (rType != ""))
						{
							tmpPropVal = document.getElementById(rType + "_" + propName + "_txt_" + propCount).value;
						}
						else
						{
							tmpPropVal = document.getElementById(propName + "_txt_" + propCount).value;
						}

// 						alert(tmpPropVal);
					}

// oct 21/08				tmpInput.setAttribute("name", prefix + propName + "_" + tmpPropVal + "_orientation_prop");

					if (rType && (rType != ""))
						tmpInput.setAttribute("name", prefix + rType + "_" + propName + "_:_" + tmpPropVal + "_start_" + tmpPropStart + "_end_" + tmpPropEnd + "_orientation_prop");
					else
						tmpInput.setAttribute("name", prefix + propName + "_:_" + tmpPropVal + "_start_" + tmpPropStart + "_end_" + tmpPropEnd + "_orientation_prop");	// oct 21/08

					tmpInput.setAttribute("value", "forward");
				}
				else if (tmpRev.checked)	// orientation <= reverse
				{
					// get the selected value from the corresponding list
					if (rType && (rType != ""))
						tmpList = document.getElementById(rType + "_" + propName + "_:_proplist_" + propCount);
					else
						tmpList = document.getElementById(propName + "_:_proplist_" + propCount);

					tmpPropVal = tmpList[tmpList.selectedIndex].value;
					
					if (tmpPropVal.toLowerCase() == 'other')
					{
						if (rType && (rType != ""))
						{
							tmpPropVal = document.getElementById(rType + "_" + propName + "_txt_" + propCount).value;
						}
						else
						{
							tmpPropVal = document.getElementById(propName + "_txt_" + propCount).value;
						}

// 						alert(tmpPropVal);
					}

// oct 24/08				tmpInput.setAttribute("name", prefix + propName + "_" + tmpPropVal + "_orientation_prop");
					if (rType && (rType != ""))
						tmpInput.setAttribute("name", prefix + rType + "_" + propName + "_:_" + tmpPropVal + "_start_" + tmpPropStart + "_end_" + tmpPropEnd + "_orientation_prop");
					
					else
						tmpInput.setAttribute("name", prefix + propName + "_:_" + tmpPropVal + "_start_" + tmpPropStart + "_end_" + tmpPropEnd + "_orientation_prop");	// oct 21/08

					tmpInput.setAttribute("value", "reverse");
				}
			}/*
			else
			{
				orientationInd = tmpID.indexOf("_orientation_radio_");
			}*/
		}
	}
}

// Aug. 11/08: 'Select' elements don't have a 'readonly' attribute, but 'disabled' prevents the element from being submitted with the form.  Need to enable this element when form is submitted - concerns cloning sites.
function enableSites()
{
	//var fpcs;
	//var tpcs;

	fpcsList = document.getElementById("fpcs_list");
	tpcsList = document.getElementById("tpcs_list");

	if (fpcsList)
	{
		fpcsList.disabled = false;
		//fpcs = fpcsList[fpcsList.selectedIndex].value;
	}

	if (tpcsList)
	{
		tpcsList.disabled = false;
		//tpcs = tpcsList[tpcsList.selectedIndex].value;
	}

	// Different list IDs for Vector and Insert
	fpcsList_1 = document.getElementById("fpcs_list_1");
	tpcsList_1 = document.getElementById("tpcs_list_1");

	if (fpcsList_1)
	{
		fpcsList_1.disabled = false;
	//	fpcs = fpcsList[fpcsList.selectedIndex].value;
	}

	if (tpcsList_1)
	{
		tpcsList_1.disabled = false;
	//	tpcs = tpcsList[tpcsList.selectedIndex].value;
	}

	/*
	var fpcs_value = document.getElementById("fpcs_val");
	var tpcs_value = document.getElementById("tpcs_val");
	
	if (fpcs_value)
	{
		fpcs_value.value = fpcs;
	}
	
	if (tpcs_value)
	{
		tpcs_value.value = tpcs;
	}*/

}

// July 20/09: try to make common to all reagent types
function toggleReagentEditModify(actn, categoryAlias, rType)
{
// alert(rType);
// alert(categoryAlias);

// 	var actnBtn = document.getElementById("update_reagent_" + categoryAlias);
// 	var actn = actnBtn.value;

	var viewSection = document.getElementById(categoryAlias + "_tbl_view");
	var modifySection = document.getElementById(categoryAlias + "_tbl_modify");

	if (actn == 'edit')
	{
		// just show Modify section
		modifySection.style.display = "inline";
		viewSection.style.display = "none";

		if ((categoryAlias == "sequence_properties") || (categoryAlias == "protein_sequence_properties") || (categoryAlias == "rna_sequence_properties"))
		{
			var editSeqBtn = document.getElementById("edit_reagent_" + categoryAlias);
			editSeqBtn.style.display = "none";

			var seqBtnDiv = document.getElementById("sequenceButtons");
			seqBtnDiv.style.display = "inline";
	
			// type of insert and open/closed
			if (document.getElementById("itype_modify"))
			{
				var typeOfInsertModify = document.getElementById("itype_modify");
				typeOfInsertModify.style.display = "table-row";
			}

			if (document.getElementById("modifyOpenClosed"))
			{
				var openClosedModify = document.getElementById("modifyOpenClosed");
				openClosedModify.style.display = "table-row";
			}

			if (document.getElementById("rna_tm_view") && document.getElementById("rna_tm_modify"))
			{
				var rna_tm_view_div = document.getElementById("rna_tm_view");
				var rna_tm_edit_div = document.getElementById("rna_tm_modify");

				rna_tm_view_div.style.display = "none";
				rna_tm_edit_div.style.display = "inline";
			}
		}

		// disable the rest of Edit buttons!
		var inputs = document.getElementsByTagName("INPUT");

		for (i=0; i < inputs.length; i++)
		{
			var tmpInput = inputs[i];

			if ((tmpInput.type == "button") && (tmpInput.id != "save_reagent_" + categoryAlias) && (tmpInput.id != "cancel_save_" + categoryAlias) && (tmpInput.id != "update_reagent_" + categoryAlias) && (tmpInput.name != "addBtn[]") && (tmpInput.name != "rmvBtn[]"))
				tmpInput.disabled = true;
		}
	}
	else if (actn == 'save')
	{
//		alert(categoryAlias);

		// Need to add start/stop validation here
		if ((categoryAlias == "dna_sequence_features") || (categoryAlias == "protein_sequence_features") || (categoryAlias == "rna_sequence_features"))
		{
			isLinear = document.getElementById("is_linear").value;

			if (verifyPositions(isLinear))
			{
				// July 20/09: document.reagentDetailForm.submit() does NOT work for some reason; use getElementById instead
				var myForm = document.getElementById("editReagentForm_" + categoryAlias);
		
				var tmpAction = document.createElement("INPUT");
				tmpAction.type = "hidden";
				tmpAction.name = "change_state";
				tmpAction.value='Save';
				myForm.appendChild(tmpAction);
			
				var sectionToSave = document.createElement("INPUT");
				sectionToSave.type = "hidden";
				sectionToSave.name = "save_section";
				sectionToSave.value = categoryAlias;
				myForm.appendChild(sectionToSave);

				enableSites();
				changeFieldNames(myForm, "modifyReagentPropsTbl_" + categoryAlias, rType);
				setFeaturePositions();

				myForm.submit();
			}
		}
		else if ((categoryAlias == "sequence_properties") || (categoryAlias == "protein_sequence_properties") || (categoryAlias == "rna_sequence_properties"))
		{
			switch(categoryAlias)
			{
				case 'sequence_properties':
					seqType = "dna";
				break;

				case 'protein_sequence_properties':
					seqType = "protein";
				break;

				case 'rna_sequence_properties':
					seqType = "rna";
				break;
			}

// 			switch (rType)
// 			{
// 				case 'Vector':
// 					switchVectorSequenceModify();
// 				break;
// 				
// 				case 'Insert':
// 					var viewInsertSeq = document.getElementById("insert_seq_div_view");
// 					var modifyInsertSeq = document.getElementById("insert_seq_div_modify");
// 					var typeOfInsertModify = document.getElementById("itype_modify");
// 					var openClosedModify = document.getElementById("modifyOpenClosed");
// 				
// 					viewInsertSeq.style.display = "none";
// 					modifyInsertSeq.style.display = "table-row";
// 					typeOfInsertModify.style.display = "table-row";
// 					openClosedModify.style.display = "table-row";
// 				
// 				break;
// 
// 				default:
// 				break;

				if (verifySequence(rType, seqType))
				{
					// July 20/09: document.reagentDetailForm.submit() does NOT work for some reason; use getElementById instead
					var myForm = document.getElementById("editReagentForm_" + categoryAlias);
					
					/*
					alert(myForm.name);
					
		for (i=0; i < myForm.elements.length; i++)
		{
			alert(myForm.elements[i].name);
			alert(myForm.elements[i].value);
		}*/
					var tmpAction = document.createElement("INPUT");
					tmpAction.type = "hidden";
					tmpAction.name = "change_state";
					tmpAction.value='Save';
					myForm.appendChild(tmpAction);
				
					var sectionToSave = document.createElement("INPUT");
					sectionToSave.type = "hidden";
					sectionToSave.name = "save_section";
					sectionToSave.value = categoryAlias;
					myForm.appendChild(sectionToSave);
		
		
		/*for (i=0; i < myForm.elements.length; i++)
		{
			alert(myForm.elements[i].name);
			alert(myForm.elements[i].value);
		}*/
					myForm.submit();
				}
// 			}
		}
		else
		{
			// July 20/09: document.reagentDetailForm.submit() does NOT work for some reason; use getElementById instead
			var myForm = document.getElementById("editReagentForm_" + categoryAlias);
	
			var tmpAction = document.createElement("INPUT");
			tmpAction.type = "hidden";
			tmpAction.name = "change_state";
			tmpAction.value='Save';
			myForm.appendChild(tmpAction);
		
			var sectionToSave = document.createElement("INPUT");
			sectionToSave.type = "hidden";
			sectionToSave.name = "save_section";
			sectionToSave.value = categoryAlias;
			myForm.appendChild(sectionToSave);

			// May 10, 2010: Allowing multiple values - select list elements!
			allLists = document.getElementsByTagName("SELECT");
			
			for (a = 0; a < allLists.length; a++)
			{
				if ((allLists[a].id.indexOf("targetList_") == 0) && (allLists[a].style.display != "none"))
					selectAllElements(allLists[a].id);
			}

			myForm.submit();
		}
	}
	else
	{
		// July 20/09: document.reagentDetailForm.submit() does NOT work for some reason; use getElementById instead
		var myForm = document.getElementById("editReagentForm_" + categoryAlias);

		var tmpAction = document.createElement("INPUT");
		tmpAction.type = "hidden";
		tmpAction.name = "change_state";
		tmpAction.value='Cancel';
		myForm.appendChild(tmpAction);
	
		var sectionToSave = document.createElement("INPUT");
		sectionToSave.type = "hidden";
		sectionToSave.name = "save_section";
		sectionToSave.value = categoryAlias;
		myForm.appendChild(sectionToSave);

		myForm.submit();
	}
}

// May 7/09 - Edit procedure for reagent types other than VICO
function switchOtherIntroModify()
{
	var viewOtherReagentIntro = document.getElementById("other_reagent_intro_tbl_view");
	var modifyOtherReagentIntro = document.getElementById("other_reagent_intro_tbl_modify");

	// June 8/08: Disable Edit buttons for other sections
	var seqEditBtn = document.getElementById("editOtherReagentSequence");
	var featuresEditBtn = document.getElementById("editOtherReagentFeatures");
	var editAnnosBtn = document.getElementById("editOtherReagentExternalIDs");	// formerly 'annotations' - renamed to 'External Identifiers'
	var editClassifiersBtn = document.getElementById("editOtherReagentClassifiers");
	var editParentsBtn = document.getElementById("editInsertParents");

	if (seqEditBtn)
		seqEditBtn.disabled = true;

	if (featuresEditBtn)	// Jan. 8/09
		featuresEditBtn.disabled = true;

	if (editAnnosBtn)	// Jan. 8/09
		editAnnosBtn.disabled = true;

	if (editClassifiersBtn)	// Jan. 8/09
		editClassifiersBtn.disabled = true;

	if (editParentsBtn)	// Jan. 8/09
		editParentsBtn.disabled = true;
	
	viewOtherReagentIntro.style.display = "none";
	modifyOtherReagentIntro.style.display = "table-row";

	// May 25/09: Custom categories
	var customCategories = document.getElementsByName("save_other_property_category");
	var tmpCatAlias;
	var tmpCatEditBtn;

	for (i = 0; i < customCategories.length; i++)
	{
		tmpCatAlias = customCategories[i].value;
// 		alert(tmpCatAlias);
		tmpCatEditBtn = document.getElementById("editOtherReagent_" + tmpCatAlias + "_btn");

		if (tmpCatEditBtn)
			tmpCatEditBtn.disabled = true;
	}

	document.getElementById("changeStateIntro").value = "Modify";	// must set for Python
}


// May 7/09
function saveOtherReagentGeneralProps()
{
	document.getElementById("changeStateIntro").value = "Save";	// remember to set this for Python
	document.otherReagentIntroForm.submit();
}


// Feb. 28/08: Hide Insert View Intro section and make Modify Intro section visible
function switchInsertIntroModify()
{
	var viewInsertIntro = document.getElementById("insert_intro_tbl_view");
	var modifyInsertIntro = document.getElementById("insert_intro_tbl_modify");

	// June 8/08: Disable Edit buttons for other sections
	var seqEditBtn = document.getElementById("editInsertSeq");
	var featuresEditBtn = document.getElementById("editInsertSeqFeatures");
	var editAnnosBtn = document.getElementById("editInsertAnnotations");
	var editClassifiersBtn = document.getElementById("editInsertClassifiers");
	var editParentsBtn = document.getElementById("editInsertParents");

	if (seqEditBtn)
		seqEditBtn.disabled = true;

	if (featuresEditBtn)	// Jan. 8/09
		featuresEditBtn.disabled = true;

	if (editAnnosBtn)	// Jan. 8/09
		editAnnosBtn.disabled = true;

	if (editClassifiersBtn)	// Jan. 8/09
		editClassifiersBtn.disabled = true;

	if (editParentsBtn)	// Jan. 8/09
		editParentsBtn.disabled = true;
	
	viewInsertIntro.style.display = "none";
	modifyInsertIntro.style.display = "table-row";

	document.getElementById("changeStateIntro").value = "Modify";	// must set for Python
}

function switchVectorIntroModify()
{
	var viewVectorIntro = document.getElementById("vector_intro_tbl_view");
	var modifyVectorIntro = document.getElementById("vector_intro_tbl_modify");

	// June 8/08: Disable Edit buttons for other sections
	var seqEditBtn = document.getElementById("editVectorSeq");
	var featuresEditBtn = document.getElementById("editVectorFeatures");
	var editParentsBtn = document.getElementById("editVectorParents");
	
	// added check Jan. 8/09
	if (seqEditBtn)
		seqEditBtn.disabled = true;

	// added check Jan. 8/09
	if (featuresEditBtn)
		featuresEditBtn.disabled = true;

	if (editParentsBtn)	// Jan. 8/09 - if this button doesn't exist on the view, as is the case with Novel Vectors, e.g. V4492, won't enter Edit mode
		editParentsBtn.disabled = true;

	viewVectorIntro.style.display = "none";
	modifyVectorIntro.style.display = "table-row";

	document.getElementById("changeStateVectorIntro").value = "Modify";	// must set for Python
}


// Feb. 28/08: JUST SUBMIT THE FORM!!!!!!!!!!!!!!!!!!!!  DO ***NOT*** CHANGE DISPLAY - Python will redirect back to Detailed view, which would take care of visibility
function saveGeneralInsertDetails()
{
// NO!!!!!!
// 	var viewInsertIntro = document.getElementById("insert_intro_tbl_view");
// 	var modifyInsertIntro = document.getElementById("insert_intro_tbl_modify");

// 	var editBtn = document.getElementById("editInsertIntro");
// 	var saveBtn = document.getElementById("saveInsertIntro");

// 	viewInsertIntro.style.display = "inline";
// 	modifyInsertIntro.style.display = "none";
// 
// 	editBtn.style.display = "inline";
// 	saveBtn.style.display = "none";

	document.getElementById("changeStateIntro").value = "Save";	// remember to set this for Python
	document.insertIntroForm.submit();
}

function saveGeneralVectorDetails()
{
	document.getElementById("changeStateVectorIntro").value = "Save";	// remember to set this for Python
	document.vectorIntroForm.submit();
}

function cancelReagentParentsModification()
{
	document.getElementById("changeStateParents").value = "Cancel";		// remember to set this for Python
	document.reagentParentsForm.submit();
}


function cancelInsertIntroModification()
{
// Changed again April 10/08 - refreshing the page is not always a good idea; sometimes get a 'POSTDATA' warning
// 	window.location.reload();	// april 2/08

	// April 10/08 - FINAL, optimal solution
	document.getElementById("changeStateIntro").value = "Cancel";	// remember to set this for Python
	document.insertIntroForm.submit();
}

function cancelGeneralVectorModification()
{
	document.getElementById("changeStateVectorIntro").value = "Cancel";	// remember to set this for Python
	document.vectorIntroForm.submit();
}

function cancelOtherReagentIntroModification()
{
	document.getElementById("changeStateIntro").value = "Cancel";	// remember to set this for Python
	document.otherReagentIntroForm.submit();
}


function switchOtherReagentSequenceModify(rType)
{
	switch (rType)
	{
		case 'Vector':
			switchVectorSequenceModify();
		break;
		
		case 'Insert':
			switchInsertSequenceModify();
		break;

	}

// 	var viewSequence = document.getElementById("insert_seq_div_view");
}

// Feb. 28/08: Insert Sequence modification - show in modify mode
function switchInsertSequenceModify()
{
	var viewInsertSeq = document.getElementById("insert_seq_div_view");
	var modifyInsertSeq = document.getElementById("insert_seq_div_modify");

	var typeOfInsertView = document.getElementById("type_of_insert");
	var typeOfInsertModify = document.getElementById("itype_modify");

	var openClosedView = document.getElementById("viewOpenClosed");
	var openClosedModify = document.getElementById("modifyOpenClosed");

	var dnaLengthView = document.getElementById("dnaLengthView");
	var cDNALengthView = document.getElementById("cDNALengthView");

	var dnaLengthModify = document.getElementById("dnaLengthModify");
	var cDNALengthModify = document.getElementById("cDNALengthModify");

	var editBtn = document.getElementById("editInsertSeq");
	var saveBtn = document.getElementById("saveInsertSeq");
	var cancelBtn = document.getElementById("cancelChangeInsertSeq");

	// Show cDNA start/stop input fields
// 	cdnaModify.style.display = "table-row";

	// Hide feature descriptions and show form fields
	viewInsertSeq.style.display = "none";
	modifyInsertSeq.style.display = "table-row";

	if (typeOfInsertView)
		typeOfInsertView.style.display = "none";

	typeOfInsertModify.style.display = "table-row";

	if (openClosedView)
		openClosedView.style.display = "none";

	openClosedModify.style.display = "table-row";

	// Hide length fields??
	if (dnaLengthView)
		dnaLengthView.style.display = "none";

	if (cDNALengthView)
		cDNALengthView.style.display = "none";

	if (dnaLengthModify)
		dnaLengthModify.style.display = "inline";

	if (cDNALengthModify)
		cDNALengthModify.style.display = "table-row";

// 	exportFasta.style.display = "none";
// 	viewRestrMap.style.display = "none";

	editBtn.style.display = "none";
	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	// June 8/08: Disable Edit buttons for other sections
	var introEditBtn = document.getElementById("editInsertIntro");
	var featuresEditBtn = document.getElementById("editInsertSeqFeatures");
	var editAnnosBtn = document.getElementById("editInsertAnnotations");
	var editClassifiersBtn = document.getElementById("editInsertClassifiers");
	var editParentsBtn = document.getElementById("editInsertParents");

	introEditBtn.disabled = true;

	// added check Jan. 8/09
	if (featuresEditBtn)
		featuresEditBtn.disabled = true;

	// added check Jan. 8/09
	if (editAnnosBtn)
		editAnnosBtn.disabled = true;

	// added check Jan. 8/09
	if (editClassifiersBtn)
		editClassifiersBtn.disabled = true;

	// Most important: Insert may have no parents; if so, clicking 'Edit' would produce an error (Jan. 8/09)
	if (editParentsBtn)
		editParentsBtn.disabled = true;

	document.getElementById("changeStateSeq").value = "Modify";	// must set for Python
}


function switchVectorSequenceModify()
{
	var viewVectorSeq = document.getElementById("vector_seq_div_view");
	var modifyVectorSeq = document.getElementById("vector_seq_div_modify");

	var editBtn = document.getElementById("editVectorSeq");
	var saveBtn = document.getElementById("saveVectorSeq");
	var cancelBtn = document.getElementById("cancelChangeVectorSeq");

	viewVectorSeq.style.display = "none";
	modifyVectorSeq.style.display = "table-row";
	
	editBtn.style.display = "none";
	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	var introEditBtn = document.getElementById("editVectorIntro");
	var featuresEditBtn = document.getElementById("editVectorFeatures");
	var editParentsBtn = document.getElementById("editVectorParents");

	introEditBtn.disabled = true;

	if (featuresEditBtn)	// Jan. 8/09
		featuresEditBtn.disabled = true;

	if (editParentsBtn)	// Jan. 8/09
		editParentsBtn.disabled = true;

	document.getElementById("changeStateSeq").value = "Modify";	// must set for Python
}

// Feb. 28/08: JUST SUBMIT THE FORM!!!!!!!!!!!!!!!!!!!!  DO ***NOT*** CHANGE DISPLAY - Python will redirect back to Detailed view, which would take care of visibility
function saveInsertSequence()
{
// 	var exportLinks = document.getElementById("exportLinks");

	if (verifyInsertSaveSequence())
	{
		// NO!!!!!!
		// 	var viewInsertIntro = document.getElementById("insert_intro_tbl_view");
		// 	var modifyInsertIntro = document.getElementById("insert_intro_tbl_modify");
		
		// 	var editBtn = document.getElementById("editInsertIntro");
		// 	var saveBtn = document.getElementById("saveInsertIntro");
		
		// 	viewInsertIntro.style.display = "inline";
		// 	modifyInsertIntro.style.display = "none";
		// 
		// 	editBtn.style.display = "inline";
		// 	saveBtn.style.display = "none";

		result = confirm("Please note:\n\nAll sequence feature positions will be adjusted upon saving.  You can edit them manually later using the 'Edit Features' function.\n\nAny features directly affected by the change in sequence will be automatically deleted.\n\nAre you sure you wish to proceed?");

// 		exportLinks.style.display = "table-row";

		if (result)
		{
			document.getElementById("changeStateSeq").value = "Save";	// remember to set this for Python
			document.insertSequenceForm.submit();
		}
	}
}


function showHidePropertyInputValues(cellID)
{
	var inputFormatSelect = document.getElementById("propInputFormat");
	var inputFormatSelectedIndex = inputFormatSelect.selectedIndex;
	var inputFormat = inputFormatSelect[inputFormatSelectedIndex].value;

	var propValsCell = document.getElementById(cellID);
	var newEl;

	switch (inputFormat)
	{
		case "plain_text":
// 			newEl = document.createElement("INPUT");
// 			newEl.setAttribute("type", "text");
		break;

		case "dropdown":

		break;

		case "radio_btn":

		break;

		case "checkboxes":

		break;
	}

	propValsCell.appendChild(newEl);
}


// May 20/08: Make sure Novel vector sequence is filled in and does not contain characters other than ACGT
function verifyNovelVectorSequence()
{
	var dna_sequence = document.getElementById("dna_sequence_");
	var sequence_warning = document.getElementById("vector_sequence_warning");
	var seq = trimAll(dna_sequence.value);

// Removed Dec. 15/08 at Karen's request - do not make Novel vector sequence mandatory
// 	if (seq == "")
// 	{
// 		alert("Please provide a sequence for the Novel Vector");
// 		dna_sequence.focus();
// 		sequence_warning.style.display = "inline";
// 
// 		return false;
// 	}
// 	else
// 		sequence_warning.style.display = "none";

	for (i = 0; i < seq.length; i++)
	{
		aChar = seq.charAt(i).toLowerCase();

		if ( (aChar != 'a') && (aChar != 'c') && (aChar != 'g') && (aChar != 't') && (aChar != 'n'))
		{
			var answer = confirm("Vector sequence contains characters other than A, C, G, T or N.  Saving will remove these extra characters.  Are you sure you wish to proceed?");

			if (answer == 0)
			{
				dna_sequence.focus();
				return false;
			}
			else
			{
				// Filter unwanted chars first, then save
				new_sequence = filterDNASeq(seq);
				dna_sequence.value = new_sequence;
				break;
			}
		}
	}
	
	return true;
}

function saveVectorSequence()
{
	if (verifySequence())
	{
		result = confirm("Please note:\n\nAll sequence feature positions will be adjusted upon saving.  You can edit them manually later using the 'Edit Features' function.\n\nAny features directly affected by the change in sequence will be automatically deleted.\n\nAre you sure you wish to proceed?");

		if (result)
		{
			document.getElementById("changeStateSeq").value = "Save";	// remember to set this for Python
			document.vectorSequenceForm.submit();
		}
	}
}

function cancelInsertSequenceModification()
{
// Changed again April 10/08 - refreshing the page is not always a good idea; sometimes get a 'POSTDATA' warning
// 	window.location.reload();	// april 2/08

// 	var viewInsertSeq = document.getElementById("insert_seq_div_view");
// 	var modifyInsertSeq = document.getElementById("insert_seq_div_modify");
// 
// 	var typeOfInsertView = document.getElementById("type_of_insert");
// 	var typeOfInsertModify = document.getElementById("itype_modify");
// 
// 	var openClosedView = document.getElementById("viewOpenClosed");
// 	var openClosedModify = document.getElementById("modifyOpenClosed");
// 
// 	var cdnaModify = document.getElementById("cdnaModify");
// 
// 	var dnaLength = document.getElementById("dnaLength");
// 	var cDNALength = document.getElementById("cDNALength");
// 
// 	var editBtn = document.getElementById("editInsertSeq");
// 	var saveBtn = document.getElementById("saveInsertSeq");
// 	var cancelBtn = document.getElementById("cancelChangeInsertSeq");
// 
// 	viewInsertSeq.style.display = "table-row";
// 	modifyInsertSeq.style.display = "none";
// 
// 	cdnaModify.style.display = "none";
// 
// 	dnaLength.style.display = "table-row";
// 	cDNALength.style.display = "table-row";
// 
// 	typeOfInsertView.style.display = "inline";
// 	typeOfInsertModify.style.display = "none";
// 
// 	if (openClosedView)
// 		openClosedView.style.display = "table-row";
// 
// 	openClosedModify.style.display = "none";
// 
// 	editBtn.style.display = "inline";
// 	saveBtn.style.display = "none";
// 	cancelBtn.style.display = "none";

	// April 10/08: Final, optimal solution
	document.getElementById("changeStateFeatures").value = "Cancel";	// must set for Python
	document.reagentDetailForm.submit();
}


function saveOtherReagentFeatures()
{
	enableSites();
	changeFieldNames('reagentDetailForm', 'featuresTableModify');
	setFeaturePositions();
	verifyPositions();

	document.getElementById("changeStateFeatures").value = "Save";	// remember to set this for Python
	document.otherReagentFeaturesForm.submit();
}

function cancelOtherReagentFeaturesModification()
{
	document.getElementById("changeStateFeatures").value = "Cancel";	// remember to set this for Python
	document.otherReagentFeaturesForm.submit();
}


function cancelOtherReagentClassifiersModification()
{
	document.getElementById("changeStateClassifiers").value = "Cancel";	// remember to set this for Python
	document.otherReagentClassifiersForm.submit();
}

// External IDs - formerly 'annotations'
function switchOtherReagentExternalIDsModify()
{
	var viewExternalIDs = document.getElementById("other_reagent_external_ids_tbl_view");
	var modifyExternalIDs = document.getElementById("other_reagent_external_ids_tbl_modify");

	var editBtn = document.getElementById("editOtherReagentExternalIDs");
	var saveBtn = document.getElementById("saveOtherReagentExternalIDsBtn");
	var cancelBtn = document.getElementById("cancelChangeOtherReagentExternalIDs");

	var seqEditBtn = document.getElementById("editOtherReagentSequence");
	var introEditBtn = document.getElementById("editOtherReagentIntro");
	var featuresEditBtn = document.getElementById("editOtherReagentFeatures");
	var classifiersEditBtn = document.getElementById("editOtherReagentClassifiers");
	var editParentsBtn = document.getElementById("editOtherReagentParents");

	viewExternalIDs.style.display = "none";
	modifyExternalIDs.style.display = "table-row";

	editBtn.style.display = "none";
	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	if (introEditBtn)	// Jan. 8/09
		introEditBtn.disabled = true;

	if (seqEditBtn)	// Jan. 8/09
		seqEditBtn.disabled = true;

	if (featuresEditBtn)	// Jan. 8/09
		featuresEditBtn.disabled = true;

	if (classifiersEditBtn)	// Jan. 8/09
		classifiersEditBtn.disabled = true;

	if (editParentsBtn)	// Jan. 8/09
		editParentsBtn.disabled = true;

	// May 25/09: Custom categories
	var customCategories = document.getElementsByName("save_other_property_category");
	var tmpCatAlias;
	var tmpCatEditBtn;

	for (i = 0; i < customCategories.length; i++)
	{
		tmpCatAlias = customCategories[i].value;
// 		alert(tmpCatAlias);
		tmpCatEditBtn = document.getElementById("editOtherReagent_" + tmpCatAlias + "_btn");

		if (tmpCatEditBtn)
			tmpCatEditBtn.disabled = true;
	}

	document.getElementById("changeStateExternalIDs").value = "Modify";	// must set for Python
}


function cancelOtherReagentModification(propCategoryAlias)
{
	// Find the appropriate form
	var form_name = "otherReagentProps_" + propCategoryAlias + "_form";

	docForms = document.forms;

	for (i = 0; i < docForms.length; i++)
	{
		aForm = docForms[i];

		if (aForm.name == form_name)
		{
			document.getElementById('changeState_' + propCategoryAlias).value='Cancel';
			aForm.submit();
		}
	}
}


// May 22/09 - On novel reagent types, edit custom categories
function switchOtherReagentPropsModify(propCategoryAlias)
{
	var viewTblID = "other_reagent_" + propCategoryAlias + "_tbl_view";
	var modifyTblID = "other_reagent_" + propCategoryAlias + "_tbl_modify";

	var viewTable = document.getElementById(viewTblID);
	var modifyTable = document.getElementById(modifyTblID);

	var editBtn = document.getElementById("editOtherReagent_" + propCategoryAlias + "_btn");
	var saveBtn = document.getElementById("saveOtherReagent_" + propCategoryAlias + "_btn");
	var cancelBtn = document.getElementById("cancelChangeOtherReagent_" + propCategoryAlias + "_btn");

	viewTable.style.display = "none";
	modifyTable.style.display = "table-row";

	editBtn.style.display = "none";
	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	// June 8/08: Disable Edit buttons for other sections
	var introEditBtn = document.getElementById("editOtherReagentIntro");
	var seqEditBtn = document.getElementById("editOtherReagentSequence");
	var editFeaturesBtn = document.getElementById("editOtherReagentFeatures");
	var editAnnosBtn = document.getElementById("editOtherReagentAnnotations");
	var editClassifiersBtn = document.getElementById("editOtherReagentClassifiers");
	var editParentsBtn = document.getElementById("editOtherReagentParents");
	var externalIDsEditBtn = document.getElementById("editOtherReagentExternalIDs");
	
	// Need to disable other custom properties 'edit' sections too! think about this next week

	introEditBtn.disabled = true;

	if (seqEditBtn)
		seqEditBtn.disabled = true;

	if (editFeaturesBtn)
		editFeaturesBtn.disabled = true;

	if (externalIDsEditBtn)
		externalIDsEditBtn.disabled = true;

	if (editAnnosBtn)
		editAnnosBtn.disabled = true;

	if (editClassifiersBtn)
		editClassifiersBtn.disabled = true;

	if (editParentsBtn)
		editParentsBtn.disabled = true;

	// May 25/09: Other custom categories (except the one currently being edited)
	var customCategories = document.getElementsByName("save_other_property_category");
	var tmpCatAlias;
	var tmpCatEditBtn;

	for (i = 0; i < customCategories.length; i++)
	{
		tmpCatAlias = customCategories[i].value;
// 		alert(tmpCatAlias);

		if (tmpCatAlias != propCategoryAlias)
		{
			tmpCatEditBtn = document.getElementById("editOtherReagent_" + tmpCatAlias + "_btn");
	
			if (tmpCatEditBtn)
				tmpCatEditBtn.disabled = true;
		}
	}

	document.getElementById("changeState_" + propCategoryAlias).value = "Modify";
}


function switchOtherReagentFeaturesModify()
{
	var viewFeatures = document.getElementById("other_reagent_features_tbl_view");
	var modifyFeatures = document.getElementById("other_reagent_features_tbl_modify");

	var editBtn = document.getElementById("editOtherReagentFeatures");
	var saveBtn = document.getElementById("saveOtherReagentFeaturesBtn");
	var cancelBtn = document.getElementById("cancelChangeOtherReagentFeatures");

	// Hide feature descriptions and show form fields
	viewFeatures.style.display = "none";
	modifyFeatures.style.display = "table-row";

	editBtn.style.display = "none";
	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	// June 8/08: Disable Edit buttons for other sections
	var introEditBtn = document.getElementById("editOtherReagentIntro");
	var seqEditBtn = document.getElementById("editOtherReagentSequence");
	var editAnnosBtn = document.getElementById("editOtherReagentAnnotations");
	var editClassifiersBtn = document.getElementById("editOtherReagentClassifiers");
	var editParentsBtn = document.getElementById("editOtherReagentParents");
	var externalIDsEditBtn = document.getElementById("editOtherReagentExternalIDs");
	
	introEditBtn.disabled = true;

	if (seqEditBtn)
		seqEditBtn.disabled = true;

	if (externalIDsEditBtn)
		externalIDsEditBtn.disabled = true;

	if (editAnnosBtn)	// Jan. 8/09
		editAnnosBtn.disabled = true;

	if (editClassifiersBtn)	// Jan. 8/09
		editClassifiersBtn.disabled = true;

	if (editParentsBtn)	// Jan. 8/09
		editParentsBtn.disabled = true;

	// May 25/09: Custom categories
	var customCategories = document.getElementsByName("save_other_property_category");
	var tmpCatAlias;
	var tmpCatEditBtn;

	for (i = 0; i < customCategories.length; i++)
	{
		tmpCatAlias = customCategories[i].value;
// 		alert(tmpCatAlias);
		tmpCatEditBtn = document.getElementById("editOtherReagent_" + tmpCatAlias + "_btn");

		if (tmpCatEditBtn)
			tmpCatEditBtn.disabled = true;
	}

	document.getElementById("changeStateFeatures").value = "Modify";	// must set for Python
}

// Feb. 28/08: Sequence Features modification - show in modify mode
function switchInsertSequenceFeaturesModify()
{
	var viewInsertSeqFeatures = document.getElementById("insert_features_view");
//	var modifyInsertSeqFeatures = document.getElementById("modifyReagentPropsTbl");
	var modifyInsertSeqFeatures = document.getElementById("editFeaturesTbl");

	var editBtn = document.getElementById("editInsertSeqFeatures");
	var saveBtn = document.getElementById("saveInsertSeqFeatures");
	var cancelBtn = document.getElementById("cancelChangeInsertSeqFeatures");

	// Hide feature descriptions and show form fields
	viewInsertSeqFeatures.style.display = "none";
	modifyInsertSeqFeatures.style.display = "table-row";

	editBtn.style.display = "none";
	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	// June 8/08: Disable Edit buttons for other sections
	var introEditBtn = document.getElementById("editInsertIntro");
	var seqEditBtn = document.getElementById("editInsertSeq");
	var editAnnosBtn = document.getElementById("editInsertAnnotations");
	var editClassifiersBtn = document.getElementById("editInsertClassifiers");
	var editParentsBtn = document.getElementById("editInsertParents");

	introEditBtn.disabled = true;

	if (seqEditBtn)
		seqEditBtn.disabled = true;

	if (editAnnosBtn)	// Jan. 8/09
		editAnnosBtn.disabled = true;

	if (editClassifiersBtn)	// Jan. 8/09
		editClassifiersBtn.disabled = true;

	if (editParentsBtn)	// Jan. 8/09
		editParentsBtn.disabled = true;

	document.getElementById("changeStateFeatures").value = "Modify";	// must set for Python
}


// May 25/09: Save custom properties for novel reagent types.
// Since there are infinite possibilities for the new properties, code differentiates between them by property alias, which is the function argument
function saveOtherReagentProps(propAlias)
{
	// Find the appropriate form
	var form_name = "otherReagentProps_" + propAlias + "_form";

	docForms = document.forms;

	for (i = 0; i < docForms.length; i++)
	{
		aForm = docForms[i];

		if (aForm.name == form_name)
		{
			document.getElementById('changeState_' + propAlias).value='Save';
			aForm.submit();
		}
	}
}


function saveInsertSequenceFeatures(form_name)
{
	var myForm = document.reagentDetailForm;

	// Need to add start/stop validation here
	if (verifyPositions(true))
	{
		setFeaturePositions();
		changeFieldNames(form_name);

		// Nov. 18/08 - Enable 'sites' textboxes
		fpcs = document.getElementById("fpcs_txt_");
		tpcs = document.getElementById("tpcs_txt_");
	
		fpcs.readonly = false;
		tpcs.readonly = false;

		document.getElementById('changeStateFeatures').value='Save';
		document.reagentDetailForm.submit();
	}
}

function cancelInsertSequenceFeaturesModification()
{
	document.getElementById("changeStateFeatures").value = "Cancel";	// remember to set this for Python
	document.reagentDetailForm.submit();
}


// May 15/09 - edit 'Classifiers' section for custom reagent types
function switchOtherReagentClassifiersModify()
{
	// Show all classifiers EXCEPT type of insert
	var viewClassifiers = document.getElementById("other_reagent_classifiers_view");
	var modifyClassifiers = document.getElementById("other_reagent_classifiers_modify");

	var editBtn = document.getElementById("editOtherReagentClassifiers");
	var saveBtn = document.getElementById("saveOtherReagentClassifiersBtn");
	var cancelBtn = document.getElementById("cancelChangeOtherReagentClassifiers");

	// Hide feature descriptions and show form fields
	viewClassifiers.style.display = "none";
	modifyClassifiers.style.display = "table-row";

	editBtn.style.display = "none";
	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	// June 8/08: Disable Edit buttons for other sections
	var introEditBtn = document.getElementById("editOtherReagentIntro");
	var seqEditBtn = document.getElementById("editOtherReagentSequence");
	var featuresEditBtn = document.getElementById("editOtherReagentFeatures");
	var editAnnosBtn = document.getElementById("editOtherReagentExternalIDs");	// formerly 'annotations' - renamed to 'External Identifiers'
	var editParentsBtn = document.getElementById("editInsertParents");

	if (seqEditBtn)
		seqEditBtn.disabled = true;

	if (introEditBtn)
		introEditBtn.disabled = true;

	if (editAnnosBtn)
		editAnnosBtn.disabled = true;

	if (featuresEditBtn)
		featuresEditBtn.disabled = true;

	if (editParentsBtn)	// Jan. 8/09
		editParentsBtn.disabled = true;

	// May 25/09: Custom categories
	var customCategories = document.getElementsByName("save_other_property_category");
	var tmpCatAlias;
	var tmpCatEditBtn;

	for (i = 0; i < customCategories.length; i++)
	{
		tmpCatAlias = customCategories[i].value;
// 		alert(tmpCatAlias);
		tmpCatEditBtn = document.getElementById("editOtherReagent_" + tmpCatAlias + "_btn");

		if (tmpCatEditBtn)
			tmpCatEditBtn.disabled = true;
	}

	document.getElementById("changeStateClassifiers").value = "Modify";	// must set for Python
}

function switchInsertClassifiersModify()
{
	// Show all classifiers EXCEPT type of insert
	var viewInsertClassifiers = document.getElementById("insert_classifiers_view");
	var modifyInsertClassifiers = document.getElementById("insert_classifiers_modify");

	var editBtn = document.getElementById("editInsertClassifiers");
	var saveBtn = document.getElementById("saveInsertClassifiers");
	var cancelBtn = document.getElementById("cancelChangeInsertClassifiers");

	// Hide feature descriptions and show form fields
	viewInsertClassifiers.style.display = "none";
	modifyInsertClassifiers.style.display = "inline";

	editBtn.style.display = "none";
	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	// Show form - it was hidden initially to save space
	document.insertClassifiersForm.style.display = "inline";

	// June 8/08: Disable Edit buttons for other sections
	var introEditBtn = document.getElementById("editInsertIntro");
	var seqEditBtn = document.getElementById("editInsertSeq");
	var editAnnosBtn = document.getElementById("editInsertAnnotations");
	var editFeaturesBtn = document.getElementById("editInsertSeqFeatures");
	var editParentsBtn = document.getElementById("editInsertParents");

	introEditBtn.disabled = true;

	if (seqEditBtn)
		seqEditBtn.disabled = true;

	if (editAnnosBtn)	// Jan. 8/09
		editAnnosBtn.disabled = true;

	if (editFeaturesBtn)	// Jan. 8/09
		editFeaturesBtn.disabled = true;

	if (editParentsBtn)	// Jan. 8/09
		editParentsBtn.disabled = true;

	document.getElementById("changeStateClassifiers").value = "Modify";	// must set for Python
}


function saveInsertClassifiers()
{
	if (verifyInsertClassifiers())
	{
		document.getElementById("changeStateClassifiers").value = "Save";	// remember to set this for Python
		document.insertClassifiersForm.submit();
	}
}

function saveOtherReagentExternalIDs()
{
	document.getElementById("changeStateExternalIDs").value = "Save";	// remember to set this for Python
	document.otherReagentExternalIDsForm.submit();
}


function saveOtherReagentClassifiers()
{
	document.getElementById("changeStateClassifiers").value = "Save";	// remember to set this for Python
	document.otherReagentClassifiersForm.submit();
}

// March 10/08: At the moment only includes a type of insert - open/closed check; maybe add more afterwards
function verifyInsertClassifiers()
{
	// Type of Insert can never be empty
	var itype_list = document.getElementById("itype_list_class");
	var itype_selectedInd = itype_list.selectedIndex;

	var oc_list = document.getElementById("oc_list_class");
	var oc_selectedInd = oc_list.selectedIndex;

	var oc_warning = document.getElementById("oc_warning_class");
	var it_warning = document.getElementById("it_warning_class");

	// No Insert Type selected
	if ((itype_selectedInd == 0) && (itype_list[itype_selectedInd].value == ""))
	{
		alert("You must select a Type of Insert to continue");
		itype_list.focus();
		it_warning.style.display = "inline";
		oc_warning.style.display = "none";

		return false;
	}
	else
	{
		it_warning.style.display = "none";

		// If insert type is filled in, check open/closed
		var itype_selectedValue = itype_list[itype_selectedInd].value;

		if ((itype_selectedValue != "cDNA with UTRs") && (itype_selectedValue != "DNA Fragment") && (itype_selectedValue != "None"))
		{
			// must have an open/closed value
			oc_sel_val = oc_list[oc_selectedInd].value;

			if (oc_sel_val == "")
			{
				alert("You must select an Open/Closed value to continue");
				oc_list.focus();
				oc_warning.style.display = "inline";
				return false;
			}
		}
		else 	// added May 28/07 to hide warning if showing from previous error
		{
			if (oc_warning.style.display == "inline")
			{
				oc_warning.style.display = "none";
			}
		}

		return true;
	}


	return false;
}


function cancelOtherReagentExternalIDsModification()
{
	// April 10/08: Final, optimal solution
	document.getElementById("changeStateExternalIDs").value = "Cancel";	// must set for Python
	document.otherReagentExternalIDsForm.submit();
}


function cancelInsertClassifiersModification()
{
	// April 10/08: Final, optimal solution
	document.getElementById("changeStateClassifiers").value = "Cancel";	// must set for Python
	document.insertClassifiersForm.submit();	
}


// Annotations
function switchInsertAnnotationsModify()
{
	// Show all classifiers EXCEPT type of insert
	var viewInsertAnnotations = document.getElementById("insert_annotations_view");
	var modifyInsertAnnotations = document.getElementById("insert_annotations_modify");

	var editBtn = document.getElementById("editInsertAnnotations");
	var saveBtn = document.getElementById("saveInsertAnnotations");
	var cancelBtn = document.getElementById("cancelChangeInsertAnnotations");

	// Hide feature descriptions and show form fields
	viewInsertAnnotations.style.display = "none";
	modifyInsertAnnotations.style.display = "inline";

	editBtn.style.display = "none";
	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	// Show form - it was hidden initially to save space
	document.insertAnnotationsForm.style.display = "inline";

	// June 8/08: Disable Edit buttons for other sections
	var introEditBtn = document.getElementById("editInsertIntro");
	var seqEditBtn = document.getElementById("editInsertSeq");
	var editClassifiersBtn = document.getElementById("editInsertClassifiers");
	var editFeaturesBtn = document.getElementById("editInsertSeqFeatures");
	var editParentsBtn = document.getElementById("editInsertParents");

	introEditBtn.disabled = true;

	if (seqEditBtn)
		seqEditBtn.disabled = true;

	if (editClassifiersBtn)	// Jan. 8/09
		editClassifiersBtn.disabled = true;

	if (editFeaturesBtn)	// Jan. 8/09
		editFeaturesBtn.disabled = true;

	if (editParentsBtn)	// Jan. 8/09
		editParentsBtn.disabled = true;

	document.getElementById("changeStateAnnotations").value = "Modify";	// must set for Python
}


function saveInsertAnnotations()
{
	if (verifyInsertAnnotations())
	{
		document.getElementById("changeStateAnnotations").value = "Save";	// remember to set this for Python
		document.insertAnnotationsForm.submit();
	}
}


function verifyInsertAnnotations()
{
	return true;
}



function cancelInsertAnnotationsModification()
{
	document.getElementById("changeStateAnnotations").value = "Cancel";
	document.insertAnnotationsForm.submit();
}

// July 3/08
function switchVectorParentsModify()
{
	var viewVectorParents = document.getElementById("showVectorAssoc");
	var modifyVectorParents = document.getElementById("editVectorAssoc");

	var editBtn = document.getElementById("editReagentParentsBtn");
	var saveBtn = document.getElementById("saveReagentParentsBtn");
	var cancelBtn = document.getElementById("cancelChangeReagentParents");

	viewVectorParents.style.display = "none";
	modifyVectorParents.style.display = "inline";

	editBtn.style.display = "none";
	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	// Disable Edit buttons for other sections
// 	var introEditBtn = document.getElementById("editVectorIntro");
// 	var seqEditBtn = document.getElementById("editVectorSeq");
// 	var editFeaturesBtn = document.getElementById("editVectorFeatures");
// 
// 	introEditBtn.disabled = true;
// 
// 	if (seqEditBtn)
// 		seqEditBtn.disabled = true;
// 
// 	editFeaturesBtn.disabled = true;

	// disable the rest of Edit buttons!
	var inputs = document.getElementsByTagName("INPUT");

	for (i=0; i < inputs.length; i++)
	{
		var tmpInput = inputs[i];

		if ((tmpInput.type == "button") && (tmpInput.id != "saveReagentParentsBtn") && (tmpInput.id != "cancelChangeReagentParents") && (tmpInput.id != "editReagentParentsBtn"))
			tmpInput.disabled = true;
	}

	document.getElementById("changeStateParents").value = "Modify";		// must set for Python
}


function switchCellLineParentsModify()
{
	var viewCLParents = document.getElementById("showCellLineAssoc");
	var modifyCLParents = document.getElementById("editCellLineAssoc");

	var editBtn = document.getElementById("editReagentParentsBtn");
// 	var saveBtn = document.getElementById("saveReagentParentsBtn");
	var cancelBtn = document.getElementById("cancelChangeReagentParents");

	viewCLParents.style.display = "none";
	modifyCLParents.style.display = "inline";

	editBtn.style.display = "none";
// 	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	// Disable Edit buttons for other sections
// 	var introEditBtn = document.getElementById("editVectorIntro");
// 	var seqEditBtn = document.getElementById("editVectorSeq");
// 	var editFeaturesBtn = document.getElementById("editVectorFeatures");
// 
// 	introEditBtn.disabled = true;
// 
// 	if (seqEditBtn)
// 		seqEditBtn.disabled = true;
// 
// 	editFeaturesBtn.disabled = true;

	// disable the rest of Edit buttons!
	var inputs = document.getElementsByTagName("INPUT");

	for (i=0; i < inputs.length; i++)
	{
		var tmpInput = inputs[i];

		if ((tmpInput.type == "button") && (tmpInput.id != "saveReagentParentsBtn") && (tmpInput.id != "cancelChangeReagentParents") && (tmpInput.id != "editReagentParentsBtn"))
			tmpInput.disabled = true;
	}

	document.getElementById("changeStateParents").value = "Modify";		// must set for Python
}


// Jan. 30/09: Several verification cases when user selects the 'custom sites' option during creation or modification
function verifyCustomSites()
{
	// 1. Check to ensure custom cloning sites are selected when the user chooses to reverse complement the Insert
	var reverse_checkbox = document.getElementById("reverseComplementCheckbox");
	var customSitesOptn = document.getElementById("customSitesCheckbox");

	var insert_fpcs_list =  document.getElementById("fpcs_list");
	var insert_tpcs_list =  document.getElementById("tpcs_list");

	var pv_fpcs_list = document.getElementById("fpcs_list_1");
	var pv_tpcs_list = document.getElementById("tpcs_list_1");

	if (insert_fpcs_list)
	{
		var insert_fpcs_list_sel_ind = insert_fpcs_list.selectedIndex;
		var insert_fpcs = insert_fpcs_list[insert_fpcs_list_sel_ind].value;
	}
	else
	{
		return true;
	}
	
	if (insert_tpcs_list)
	{
		var insert_tpcs_list_sel_ind = insert_tpcs_list.selectedIndex;
		var insert_tpcs = insert_tpcs_list[insert_tpcs_list_sel_ind].value;
	}
	else
		return true;

	if (pv_fpcs_list)
	{
		var pv_fpcs_list_sel_ind = pv_fpcs_list.selectedIndex;
		var pv_fpcs = pv_fpcs_list[pv_fpcs_list_sel_ind].value;
	}
	else
	{
		return true;
	}

	if (pv_tpcs_list)
	{
		var pv_tpcs_list_sel_ind = pv_tpcs_list.selectedIndex;
		var pv_tpcs = pv_tpcs_list[pv_tpcs_list_sel_ind].value;
	}
	else
	{
		return true;
	}

	if (reverse_checkbox)
	{
		if (reverse_checkbox.checked)
		{
			if (customSitesOptn)
			{
				if (!customSitesOptn.checked)
				{
					// confirm text with Karen on Monday
					alert("Please customize the cloning sites if you wish to reverse complement the Insert");
					return false;
				}
				else
				{
					if (insert_fpcs.toLowerCase() == 'other')
					{
						alert("Please specify the 5' Insert cloning site.");
						return false;
					}
	
					if (insert_tpcs.toLowerCase() == 'other')
					{
						alert("Please specify the 3' Insert cloning site.");
						return false;
					}

					if (pv_fpcs.toLowerCase() == 'other')
					{
						alert("Please specify the 5' Parent Vector cloning site.");
						return false;
					}
	
					if (pv_tpcs.toLowerCase() == 'other')
					{
						alert("Please specify the 3' Parent Vector cloning site.");
						return false;
					}
				}
			}
		}
	}
	else if (customSitesOptn)
	{
		if (insert_fpcs.toLowerCase() == 'other')
		{
			alert("Please specify the 5' Insert cloning site.");
			return false;
		}

		if (insert_tpcs.toLowerCase() == 'other')
		{
			alert("Please specify the 3' Insert cloning site.");
			return false;
		}

		if (pv_fpcs.toLowerCase() == 'other')
		{
			alert("Please specify the 5' Parent Vector cloning site.");
			return false;
		}
	
		if (pv_tpcs.toLowerCase() == 'other')
		{
			alert("Please specify the 3' Parent Vector cloning site.");
			return false;
		}
	}

	return true;
}

// Feb. 2/09: On Err=5 during NR Vector creation, if user wishes to RC Insert, s/he needs to also provide custom sites - this function sets their values to pass to CGI
function setCustomSites()
{
	var pv_fpcs_list = document.getElementById('fpcs_list_1');
	var pv_fpcs = document.getElementById('fpcs_val');

	if (!pv_fpcs_list.disabled)
	{
		var pv_fpcs_list_sel_ind = pv_fpcs_list.selectedIndex;
		var pv_fpcs_list_sel_val = pv_fpcs_list[pv_fpcs_list_sel_ind].value;

		pv_fpcs.value = pv_fpcs_list_sel_val;
	}


	var pv_tpcs_list = document.getElementById('tpcs_list_1');
	var pv_tpcs = document.getElementById('tpcs_val');

	if (!pv_tpcs_list.disabled)
	{
		var pv_tpcs_list_sel_ind = pv_tpcs_list.selectedIndex;
		var pv_tpcs_list_sel_val = pv_tpcs_list[pv_tpcs_list_sel_ind].value;

		pv_tpcs.value = pv_tpcs_list_sel_val;
	}
}

function changeVectorParents(scriptPath)
{
// alert("Changing parents, reverse insert " + reverseInsert);

	var rID = document.getElementById("rID_hidden").value;

	var vType = document.getElementById("vector_cloning_method").value;

	xmlhttp1 = createXML();
	url = scriptPath + "update.py";
	xmlhttp1.open("POST", url, false);
	xmlhttp1.setRequestHeader('Content-Type','application/x-www-form-urlencoded');

	var url = hostName + "Reagent.php?View=6&rid=" + rID;

	// Jan. 19/09: Add ability to customize sites (mainly for hybrids)
	var customSitesBox = document.getElementById("customSitesCheckbox");

	var customSites;

	if (customSitesBox)
	{
		if (customSitesBox.checked)
		{
			customSites = true;
	
			insert_custom_five_prime = document.getElementsByName("insert_custom_five_prime")[0].value;
			insert_custom_three_prime = document.getElementsByName("insert_custom_three_prime")[0].value;
	
			pv_custom_five_prime = document.getElementById("fpcs_list_1").value;
			pv_custom_three_prime = document.getElementById("tpcs_list_1").value;
	
		}
		else
		{
			customSites = false;
	
			insert_custom_five_prime = "";
			insert_custom_three_prime = "";
	
			pv_custom_five_prime = "";
			pv_custom_three_prime = "";
		}
	}
	else
	{
		customSites = false;

		insert_custom_five_prime = "";
		insert_custom_three_prime = "";

		pv_custom_five_prime = "";
		pv_custom_three_prime = "";
	}

	// Jan. 20/09: Add ability to reverse complement the Insert before constructing a sequence
	reverseInsert = false;

	var reverseInsertBox = document.getElementById("reverseInsertCheckbox");

	if (reverseInsertBox)
	{
		if (reverseInsertBox.checked)
			reverseInsert = true;
	}

	if (verifyParentFormat(vType))
	{
		// July 8/08: general info
		var newName = document.getElementById("new_vector_name").value;
	
		var newVectorTypesList = document.getElementById("new_vector_type");
		var newVectorTypeIndex = newVectorTypesList.selectedIndex;
		var newVectorType = newVectorTypesList[newVectorTypeIndex].value;
	
		var newProjectList = document.getElementById("new_packet_id");
		var newProjectIndex = newProjectList.selectedIndex;
		var newProjectID = newProjectList[newProjectIndex].value;
	
		var newDescription = document.getElementById("new_description").value;
	
		var newVerificationList = document.getElementById("new_verification");
		var newVerIndex = newVerificationList.selectedIndex;
		var newVerification = newVerificationList[newVerIndex].value;
	
		if (newName.length == 0)
		{
			alert("Please specify a Vector name.");
			document.getElementById("new_vector_name").focus();

			// show warning

			return false;
		}
	
		if ((newProjectIndex == 0) && (newProjectList[newProjectIndex].value == 0))
		{
			alert("Please select a project ID from the list to continue.");
			return false;
		}

		document.getElementById("saveReagentParentsBtn").style.cursor = 'wait';

		var uName = document.getElementById("curr_uname").value;

		var params = "";
		
		var srcPV, srcInsert, srcIPV;

		changeParentValues(vType);

		// Check if parents are filled in
		var parentVectorField = document.getElementById("parent_vector_id_txt");
		var parentVector = parentVectorField.value;

		srcPV = document.getElementById("parent_vector_id_txt").value;

		// remember PV
		document.getElementById("parent_vector_old_id").value = parentVector;

		if (parentVector == "")
		{
			var proceed = confirm("Your Parent Vector value is empty.  Are you sure you wish to proceed?");

			if (proceed)
				return true;
			else
				parentVectorField.focus();
		}
		else
		{
			switch (vType)
			{
				case '1':
				
					// Non-recombination vector
					srcInsert = document.getElementById("insert_id_txt").value;
		
					// Insert empty??
					if (srcInsert == "")
					{
						var proceed = confirm("Your Insert value is empty.  Are you sure you wish to proceed?");

						if (proceed)
							return true;
						else
						{
							// different parameters for different properties
							seqParams = "reagent_id_hidden=" + rID + "&reagent_typeid_hidden=1&PV=" + srcPV + "&I=" + srcInsert + "&curr_username=" + uName + "&cloning_method_hidden=" + vType + "&change_state=Save&save_parents=1&newVectorName=" + newName + "&newVectorType=" + newVectorType + "&newProjectID=" + newProjectID + "&newDescription=" + newDescription + "&newVerification=" + newVerification;
							
							xmlhttp1.send(seqParams);
							xmlhttp1.onreadystatechange = confirmChangeVectorParents(xmlhttp1, url);
						}
					}
					else
					{
						// different parameters for different properties
						seqParams = "reagent_id_hidden=" + rID + "&reagent_typeid_hidden=1&PV=" + srcPV + "&I=" + srcInsert + "&curr_username=" + uName + "&cloning_method_hidden=" + vType + "&change_state=Save&save_parents=1&newVectorName=" + newName + "&newVectorType=" + newVectorType + "&newProjectID=" + newProjectID + "&newDescription=" + newDescription + "&newVerification=" + newVerification + "&custom_sites=" + customSites + "&insert_custom_five_prime=" + insert_custom_five_prime + "&insert_custom_three_prime=" + insert_custom_three_prime + "&pv_custom_five_prime=" + pv_custom_five_prime + "&pv_custom_three_prime=" + pv_custom_three_prime + "&reverse_insert=" + reverseInsert;

// 						prompt("", seqParams);
						
						xmlhttp1.send(seqParams);
						xmlhttp1.onreadystatechange = confirmChangeVectorParents(xmlhttp1, url);
					}
				break;
		
				case '2':
					
					// Recombination vector
					srcIPV = document.getElementById("ipv_id_txt").value;
		
					if (srcIPV == "")
					{
						var proceed = confirm("Your Insert Parent Vector value is empty.  Are you sure you wish to proceed?");

						if (proceed)
							return true;
						else
						{
							// different parameters for different properties
							seqParams = "reagent_id_hidden=" + rID + "&reagent_typeid_hidden=1&PV=" + srcPV + "&IPV=" + srcIPV + "&curr_username=" + uName + "&cloning_method_hidden=" + vType + "&change_state=Save&save_parents=1&newVectorName=" + newName + "&newVectorType=" + newVectorType + "&newProjectID=" + newProjectID + "&newDescription=" + newDescription + "&newVerification=" + newVerification;
					
							xmlhttp1.send(seqParams);
							xmlhttp1.onreadystatechange = confirmChangeVectorParents(xmlhttp1, url);
						}
					}
					else
					{
						// different parameters for different properties
						seqParams = "reagent_id_hidden=" + rID + "&reagent_typeid_hidden=1&PV=" + srcPV + "&IPV=" + srcIPV + "&curr_username=" + uName + "&cloning_method_hidden=" + vType + "&change_state=Save&save_parents=1&newVectorName=" + newName + "&newVectorType=" + newVectorType + "&newProjectID=" + newProjectID + "&newDescription=" + newDescription + "&newVerification=" + newVerification;
				
						xmlhttp1.send(seqParams);
						xmlhttp1.onreadystatechange = confirmChangeVectorParents(xmlhttp1, url);
					}
				break;
			}
		}
	}
	
	document.getElementById("saveReagentParentsBtn").style.cursor = 'auto';
	return true;
}

function confirmChangeVectorParents(xmlhttp, url)
{
	if (xmlhttp.readyState == 4)
	{
// 		alert(xmlhttp.status);
// 		alert(xmlhttp.responseText);

		if (xmlhttp.status == 200)
		{
			result = xmlhttp.responseText;

// 			prompt("", xmlhttp.responseText);
// 			toks = xmlhttp.responseText.split("&");

// 			resTok = "Result=";
// 			ind1 = toks[1].indexOf(resTok)+resTok.length;
// 			result = toks[1].substring(ind1);

// 			alert(result);

			switch (parseInt(result))
			{
				case 0:
					if (confirm("Vector sequence and features will change permanently upon saving.\nPlease note: You must select the 'Reverse Complement' option if you want the Insert to appear in reverse orientation in the new Vector sequence.  Otherwise, the default forward orientation will be used.\nAre you sure you wish to proceed?"))
					{
						redirect(url);
					}
				break;

				case 1:
					alert("Error: Cannot generate Vector sequence using the parent values provided: Unknown sites on Insert.  Please verify your input.")
				break;

				case 2:
					alert("Error: Cannot generate Vector sequence using the parent values provided: Insert sites are not found in the parent vector sequence.")
				break;

				case 3:
					alert("Error: Cannot generate Vector sequence using the parent values provided: Insert sites occur more than once in the parent vector sequence.  Please verify your input.")
				break;

				case 4:
					alert("Error: Cannot generate Vector sequence using the parent values provided: Cloning sites cannot be hybridized.  Please verify your input.")
				break;

				case 5:
					alert("Error: Cannot generate Vector sequence using the parent values provided: 5' cloning site occurs after the 3' site on parent sequence.  Please verify your input.")
				break;

				case 6:
					alert("Error: Invalid reagent ID.\n\nOne or both of the parent IDs provided cannot be found in the database.  Please verify your input.")
				break;

				case 10:
					// Get exact ID from Python - do it later
					alert("Error: You do not have Write access to the project of the Parent Vector provided.  Please contact the project owner to obtain permission.")

					// focus on the field
				break;

				case 11:
					// Get exact ID from Python - do it later
					alert("Error: You do not have Write access to the project of the Insert provided.  Please contact the project owner to obtain permission.")

					// focus on the field
				break;

				case 12:
					// Get exact ID from Python - do it later
					alert("Error: You do not have Write access to the project of the Parent Insert Vector provided.  Please contact the project owner to obtain permission.")

					// focus on the field
				break;

				// Updated Feb. 12/09: this is the wrong error; code 13 corresponds to sites not found in Insert; added err code 23 for empty sites (see below)
				case 13:
					// Get exact ID from Python - do it later
					alert("Error: Cannot generate Vector sequence using the parent values provided.\n\nInsert sequence does not contain the restricton sites provided.\n\nPlease verify your input before proceeding.")

					// focus on the field
				break;

				case 14:
					// Get exact ID from Python - do it later
					alert("Error: Cannot generate Vector sequence using the parent values provided.\n\nRestricton sites do not match Insert sequence at the specified positions.\n\nPlease verify your input before proceeding.")

					// focus on the field
				break;

				case 15:
					// Get exact ID from Python - do it later
					alert("Error: Cannot generate Vector sequence using the parent values provided: Donor Vector sequence does not contain LoxP sites.  Please verify your input.")

					// focus on the field
				break;

				case 16:
					// Get exact ID from Python - do it later
					alert("Error: Cannot generate Vector sequence using the parent values provided: Donor Vector sequence only contains one occurrence of LoxP site.  Please verify your input.")

					// focus on the field
				break;

				case 17:
					// Get exact ID from Python - do it later
					alert("Error: Cannot generate Vector sequence using the parent values provided: Donor Vector sequence contains MORE than two occurrences of LoxP sites.  Please verify your input.")

					// focus on the field
				break;

				case 19:
					// Get exact ID from Python - do it later
					alert("Error: Cannot generate Vector sequence using the parent values provided: Invalid type of reagent selected as parent.  Please verify your input.")

					// focus on the field
				break;

				// Added Feb. 12/09
				case 23:
					alert("Error: Cannot generate Vector sequence using the parent values provided.\n\nInsert restricton site values are empty.\n\nPlease verify your input before proceeding.")
				break;

				default:
					alert(result);
				break;
			}
		}

// 		return true;
	}

// 	return false;
}

function redirect(url)
{
	// Redirect back to detailed view
	window.location.href = url
}


function switchReagentParentsModify(rTypeID)
{
	switch(rTypeID)
	{
		case 'Vector':
			switchVectorParentsModify();
		break;

// 		case 'Insert':
// 			switchInsertParentsModify();
// 		break;

		case 'CellLine':
			// Update Feb. 3/10: show full form after clicking 'Change', not when enetering 'Edit parents' mode
// 			rID = document.getElementById("rID_hidden").value;
// 			window.location.href= hostName + "Reagent.php?View=6&rid=" + rID + "&mode=Modify";
			switchCellLineParentsModify();
		break;

		default:

			var viewParentsTbl = document.getElementById("viewReagentParents");
			var editParentsTbl = document.getElementById("category_assoc_section_" + rTypeID);
		
			var editBtn = document.getElementById("editReagentParentsBtn");
			var saveBtn = document.getElementById("saveReagentParentsBtn");
			var cancelBtn = document.getElementById("cancelChangeReagentParents");
		
			viewParentsTbl.style.display = "none";
			editParentsTbl.style.display = "inline";
		
			editBtn.style.display = "none";
			saveBtn.style.display = "inline";
			cancelBtn.style.display = "inline";
		
			// disable the rest of Edit buttons!
			var inputs = document.getElementsByTagName("INPUT");
		
			for (i=0; i < inputs.length; i++)
			{
				var tmpInput = inputs[i];
		
				if ((tmpInput.type == "button") && (tmpInput.id != "saveReagentParentsBtn") && (tmpInput.id != "cancelChangeReagentParents") && (tmpInput.id != "editReagentParentsBtn"))
					tmpInput.disabled = true;
			}
		
			document.getElementById("changeStateParents").value = "Modify";		// must set for Python	

		break;
	}
}


function switchInsertParentsModify()
{
	// Show parents
// 	var viewInsertParents = document.getElementById("insert_parents_view");
	var viewInsertParents = document.getElementById("viewReagentParents");		// update 11/09/09 - common output function
// 	var modifyInsertParents = document.getElementById("insert_parents_modify");
	var modifyInsertParents = document.getElementById("category_assoc_section_Insert");	// update 11/09/09

// 	var editBtn = document.getElementById("editInsertParents");
	var editBtn = document.getElementById("editReagentParentsBtn");
// 	var saveBtn = document.getElementById("saveInsertParentsBtn");
	var saveBtn = document.getElementById("saveReagentParentsBtn");
// 	var cancelBtn = document.getElementById("cancelChangeInsertParents");
	var cancelBtn = document.getElementById("cancelChangeReagentParents");

	// Hide view parents form and show modification form
	viewInsertParents.style.display = "none";
	modifyInsertParents.style.display = "inline";

	editBtn.style.display = "none";
	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	// Show form - it was hidden initially to save space
// 	document.insertParentsForm.style.display = "inline";
	document.reagentParentsForm.style.display = "inline";

	// June 8/08: Disable Edit buttons for other sections
	var introEditBtn = document.getElementById("editInsertIntro");
	var seqEditBtn = document.getElementById("editInsertSeq");
	var editClassifiersBtn = document.getElementById("editInsertClassifiers");
	var editFeaturesBtn = document.getElementById("editInsertSeqFeatures");
	var editAnnosBtn = document.getElementById("editInsertAnnotations");

	introEditBtn.disabled = true;

	if (seqEditBtn)
		seqEditBtn.disabled = true;

	editClassifiersBtn.disabled = true;
	editFeaturesBtn.disabled = true;
	editAnnosBtn.disabled = true;

	document.getElementById("changeStateParents").value = "Modify";	// must set for Python
}


function saveReagentParents(rType)
{
// alert(rType);
// alert(parentsValid);

	if (parentsValid)
	{
		switch (rType)
		{
			case 'Vector':
				changeVectorParents(cgiPath);
			break;
	
	// 		case 'Insert':
	// 			saveInsertParents();
	// 		break;
	
			case 'CellLine':
				if (verifyCellLineParents())
				{
					changeParentValues(5);
					document.reagentParentsForm.submit();
				}
			break;
	
			default:
			// 	if (verifyReagentParents())
			// 	{
					document.getElementById("changeStateParents").value = "Save";	// remember to set this for Python
					document.reagentParentsForm.submit();
			// 	}
			break;
		}
	}
	else
	{
		return false;
	}
}

function saveInsertParents()
{
	if (verifyInsertParents())
	{
		document.getElementById("changeStateParents").value = "Save";	// remember to set this for Python
		document.insertParentsForm.submit();
	}
}

// maybe update later
function verifyInsertParents()
{
	return true;
}


function cancelInsertParentsModification()
{
	// As before, set change_state to 'Cancel' and submit form
	document.getElementById("changeStateParents").value = "Cancel";
	document.insertParentsForm.submit();
}

function cancelVectorParentsModification()
{
	// As before, set change_state to 'Cancel' and submit form
	document.getElementById("changeStateParents").value = "Cancel";
	document.vectorParentsForm.submit();
}

function cancelVectorSequenceModification()
{
	document.getElementById("changeStateSeq").value = "Cancel";
	document.vectorSequenceForm.submit();
}

function cancelVectorFeaturesModification()
{
	document.getElementById("changeStateFeatures").value = "Cancel";
	document.reagentDetailForm.submit();
}

// JUST UNHIDE THE HIDDEN EDIT SECTION, **NO** SERVER-SIDE PROCESSING!!!
function switchVectorFeaturesModify()
{
	viewPropsTbl = document.getElementById("viewReagentPropsTbl");
// 	modifyPropsTbl = document.getElementById("modifyReagentPropsTbl");
	modifyPropsTbl = document.getElementById("editFeaturesTbl");

	saveBtn = document.getElementById("saveVectorFeatures");
	editBtn = document.getElementById("editVectorFeatures");
	cancelBtn = document.getElementById("cancelChangeVectorFeatures");

	viewPropsTbl.style.display = "none";
	modifyPropsTbl.style.display = "inline";

	editBtn.style.display = "none";
	saveBtn.style.display = "inline";
	cancelBtn.style.display = "inline";

	// June 3/08: To ensure only one section is edited at a time, disable all other buttons until 'features' section is saved
	editIntroBtn = document.getElementById("editVectorIntro");
	editSeqBtn = document.getElementById("editVectorSeq");
	editParentsBtn = document.getElementById("editVectorParents");

	editIntroBtn.disabled = true;
	editSeqBtn.disabled = true;

	if (editParentsBtn)	// Jan. 8/09
		editParentsBtn.disabled = true;
}

function saveVectorSeqFeatures(form_name)
{
// 	var myForm = document.reagentDetailForm;

	// Need to add start/stop validation here
	if (verifyPositions(false))
	{
		enableSites();
		setFeaturePositions();
		changeFieldNames(form_name);

		// Nov. 18/08 - Enable 'sites' textboxes
		reagentType = getReagentType();
		subtype = getReagentSubtype(reagentType);

		fpcs = document.getElementById("fpcs_txt_"+subtype);
		tpcs = document.getElementById("tpcs_txt_"+subtype);

		fpcs.readonly = false;
		tpcs.readonly = false;

		fpcs.style.backgroundColor = "#FFFFFF";
		tpcs.style.backgroundColor = "#FFFFFF";

		document.getElementById("changeStateFeatures").value = "Save";

		docForms = document.forms;
	
		for (i = 0; i < docForms.length; i++)
		{
			aForm = docForms[i];
	
			if (aForm.name == form_name)
			{
				aForm.submit();
			}
		}
	}
}

// add mandatory fields check - name, status, etc. filled in, maybe more
function validateVector()
{
	document.reagentDetailForm.submit();
}

// Updated July 14/08
// This would generate a PDF file
function prepareVectorMap(scriptPath)
{
// 	return popup("/tmp/vector_maps/V3863_map.pdf", "V3863_map.pdf", 1200, 1200, 'yes');	// test

	reagentID = document.getElementById("hidden_vector_id");

	if (reagentID)
	{
		rID = reagentID.value;
// 		alert(rID);

		url = scriptPath + "vector_map.py";
		xmlhttp1 = createXML();
		xmlhttp1.open("POST", url, false);
		xmlhttp1.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	
		seqParams = "rID=" + rID;
	
		xmlhttp1.send(seqParams);
		xmlhttp1.onreadystatechange = showVectorMap(xmlhttp1, scriptPath);
	}

// 	document.show_vector_map_form.submit();		// NO
}

function showVectorMap(xmlhttp, scriptPath)
{
	limsID = document.getElementById("lims_id").value;

	if (xmlhttp.readyState == 4)
	{
// 		alert(xmlhttp.status);
// 		alert(xmlhttp.responseText);

		mapTab = document.getElementById("vector_map_tab");
	
		if (xmlhttp.status == 200)
		{
			fName = trimAll(xmlhttp.responseText);
// 			mapTab.innerHTML = "<IMG SRC=\"" + fName + "\">";
// 			popup(fName, limsID, 1200, 1200, 'yes');
// 			return;
		}
	}
}

function prepareOligoMap(scriptPath)
{
	reagentID = document.getElementById("hidden_vector_id");

	if (reagentID)
	{
		rID = reagentID.value;
// 		alert(rID);

		url = scriptPath + "oligos_vector_map.py";
		xmlhttp1 = createXML();
		xmlhttp1.open("POST", url, false);
		xmlhttp1.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	
		seqParams = "rID=" + rID;
	
		xmlhttp1.send(seqParams);
		xmlhttp1.onreadystatechange = showOligoMap(xmlhttp1, scriptPath);
	}
}

function showOligoMap(xmlhttp, scriptPath)
{
	limsID = document.getElementById("lims_id").value;

	if (xmlhttp.readyState == 4)
	{
// 		alert(xmlhttp.status);
// 		alert(xmlhttp.responseText);

		mapTab = document.getElementById("vector_map_tab");
	
		if (xmlhttp.status == 200)
		{
			fName = trimAll(xmlhttp.responseText);
// 			mapTab.innerHTML = "<IMG SRC=\"" + fName + "\">";
// 			popup(fName, limsID, 1200, 1200, 'yes');
// 			return;
		}
	}
}

function showMapMenu(oligoMap, limsID, url)
{

	mapMenu = document.getElementById("mapMenu");

	if (mapMenu)
	{
		currOptn = mapMenu[mapMenu.selectedIndex];

		switch (currOptn.id)
		{
			case 'viewOligoMap':
				mapMenu.selectedIndex = 0;
				mapMenu[mapMenu.selectedIndex].selected = true;
				return popup(oligoMap, limsID, 1200, 1200, 'yes');
			break;

			case 'viewVectorMap':
				mapMenu.selectedIndex = 0;
				mapMenu[mapMenu.selectedIndex].selected = true;
 				return popup(url, limsID, 1200, 1200, 'yes');
			break;
		}
	}
}

function hideMenu()
{
	var menuDiv = document.getElementById("mainMenu");

	if (menuDiv.style.display == "none")
		menuDiv.style.display = "inline";
	else
		menuDiv.style.display = "none";
}


function resize()
{
	document.onmouseup = function()
	{
		var mainDiv = document.getElementById("mainDiv");
		var menuDiv = document.getElementById("mainMenu");
		var bodyWidth = document.body.offsetWidth;
		var menuWidth;

		var e = arguments[0] || event;
		// alert('X: ' + e.clientX + '\nY: ' + e.clientY);

		// resize menu
		menuDiv.style.width = e.clientX;

		// resize main div
		menuWidth = menuDiv.offsetWidth;
		mainDiv.style.width = bodyWidth - menuWidth;
	}
}


// Dec. 22, 2010
function showHideAddLocation()
{
	var chemLocsList = document.getElementById("chemical_locations");
	var newChemLocDiv = document.getElementById("add_chem_loc");
	var createLocation = document.getElementById("addLocName");
	var addLocTypeList = document.getElementById("chem_loc_type_list");

	if (chemLocsList[chemLocsList.selectedIndex].value == "addChemLoc")
	{
		newChemLocDiv.style.display = "inline";
		createLocation.value = "1";
		addLocTypeList.disabled = '';
	}
	else
	{
		newChemLocDiv.style.display = "none";
		createLocation.value = "0";
		addLocTypeList.disabled = 'disabled';
	}
}

// Dec. 22, 2010
function showHideAddLocType()
{
	var chemLocTypeList = document.getElementById("chem_loc_type_list");
	var newChemLocTypeDiv = document.getElementById("add_chem_loc_type");
	var createLocationType = document.getElementById("addLocType");

	if (chemLocTypeList[chemLocTypeList.selectedIndex].value == "addChemLocType")
	{
		newChemLocTypeDiv.style.display = "inline";
		createLocationType.value = "1";
	}
	else
	{
		newChemLocTypeDiv.style.display = "none";
		createLocationType.value = "0";
	}
}


// Dec. 20, 2010
function checkChemicalCreation()
{
	// Mandatory fields: chemical name, location, location type
	var chemName = document.getElementById("chemical_name");
	var chemLocsList = document.getElementById("chemical_locations");
	var newChemLocType = document.getElementById("new_chem_loc_type");
	var newChemLocName = document.getElementById("new_chem_loc_name");
	var chemLocTypesList = document.getElementById("chem_loc_type_list");

	var addLocName = document.getElementById("addLocName").value;
	var addLocType = document.getElementById("addLocType").value;

	// jan. 22, 2011
	var existingLocationNamesList = document.getElementById("existingLocationNamesList");

	if (chemName)
	{
		if (trimAll(chemName.value).length == 0)
		{
			alert("Please provide a chemical name.");
			chemName.focus();
			return false;
		}
	}

	if ((addLocType == '1') && newChemLocType)
	{
		if (trimAll(newChemLocType.value).length == 0)
		{
			alert("Please provide a location type.");
			newChemLocType.focus();
			return false;
		}
	}

	if ((addLocName == '1') && newChemLocName)
	{
		//alert(existingLocationNamesList.selectedIndex);

		if ( (existingLocationNamesList.selectedIndex == 0) && (trimAll(newChemLocName.value).length == 0) )
		{
			alert("Please provide a storage name or room number.");
			newChemLocName.focus();
			return false;
		}
	}


	if (chemLocsList)
	{
		if (chemLocsList.selectedIndex == 0)
		{
			alert("Please select a location from the list.");
			chemLocsList.focus();
			return false;
		}
	}

	if (chemLocTypesList)
	{
		if (!chemLocTypesList.disabled)
		{
			if (chemLocTypesList.selectedIndex == 0)
			{
				alert("Please select a location type from the list.");
				chemLocTypesList.focus();
				return false;
			}
		}
	}

	return true;
}


function showChemicalLocationList()
{
	var searchCriteriaList = document.getElementById("search_criteria_list");
	var chemLocsList = document.getElementById("chemical_locations");
	var searchTerm = document.getElementById("chem_search_keyword");

	var i = searchCriteriaList.selectedIndex;
	var selElement = searchCriteriaList[i].text;

	var chem_search_caption = document.getElementById("chem_search_caption");

	if (selElement == "Location")
	{
		searchTerm.style.display = "none";
		chemLocsList.style.display = "inline";

		chem_search_caption.innerHTML = "Select a Location from the list:";
	}
	else
	{
		// hide locations
		searchTerm.style.display = "inline";
		chemLocsList.style.display = "none";
	
		chem_search_caption.innerHTML = "Enter search keyword:";
	}
}


/**
	April-June 2009: Functions for adding new reagent types
*/

// August 4/09: Disallow submitting a form whose action depends on a selected dropdown list value if the value is 'default'
function checkSelected(listID)
{
	var dropdown = document.getElementById(listID);

	if ((dropdown.selectedIndex == 0) || (dropdown[dropdown.selectedIndex].value == "default"))
		return false;

	return true;
}


// Aug. 5/09: Delete property at step 2 reagent type creation
function deleteReagentTypeAttribute(rowID, formID)
{
	catAlias = rowID.substr(0, rowID.indexOf("_:_"));
// alert(catAlias);
	str_1 = catAlias + "_:_";
// alert(str_1.length);
// alert(rowID.indexOf("_prop_row"));
	propAlias = rowID.substr(str_1.length, rowID.indexOf("_prop_row")-(str_1.length));
// alert(propAlias);
// alert(rowID);
	rowID = unescape(rowID);
// 	rowID = replaceAll(rowID, "'", "\\'");		// keep this!! - No, removed Nov. 6/09
// alert(rowID);
// alert(formID);
	categoryList = document.getElementsByName(catAlias + "[]");
// alert(categoryList);

	var cond;

// 	if (rowID.indexOf("tag_position") >= 0)
	if ((propAlias == "tag_position") || (propAlias == "expression_system"))
	{
// alert("removing tag pos");
		cond = true;
	}
	else
		cond = confirm("Are you sure you want to remove this attribute?");

	if (cond)
	{
		var myForm = document.getElementById(formID);
		var propRow = document.getElementById(rowID);
// alert(propRow);
		var propTable = propRow.parentNode;
// alert(propTable);
		var rIndex = propRow.rowIndex;
// alert("Row index before: " + rIndex);

		// remove form elements
		tmp_val = replaceAll((catAlias + "_:_" + propAlias), "'", "\\'");	// keep this
// 		alert(tmp_val);

		var textInputFormatRadio = document.getElementById("input_format_radio_list_" + tmp_val);
		var listInputFormatRadio = document.getElementById("input_format_radio_text_" + tmp_val);
		var dropdownList = document.getElementById("propertyValues_" + tmp_val);
		var propInput = document.getElementById(tmp_val + "_input");

		// Sept. 4/09: Actually, DON'T delete this element from the form, BUT set flag that it's deleted for Python to process
		// Nov. 6/09: Do the replacement here!!!!!		
// 		if (toRemove)
// 			toRemove.value = "1";
// 		else
// {
// alert("Row index after: " + rIndex);

// 			alert("????????");
// }
// 		alert(rIndex);
		propTable.deleteRow(rIndex);

		// Dec. 9/09: With DNA Sequence, remove the rest of its attributes
		if ((propAlias == "sequence") || (propAlias == "protein_sequence") || (propAlias == "rna_sequence"))
		{
			i = 0;

			while (i >= 0)
			{
// alert(i);
				propTable.deleteRow(i);
				i = propTable.rows.length - 1;
			}
		}

		// Ditto if Tag Type or Promoter are removed - remove their descriptors as well
		else if (propAlias == "tag")
		{
// alert("removing tag");
			deleteReagentTypeAttribute(rowID.replace("tag", "tag_position"), formID);
		}
		else if (propAlias == "promoter")
		{
			deleteReagentTypeAttribute(rowID.replace("promoter", "expression_system"), formID);
		}

		// Feb. 1/10: generate the flag on the fly and append to form -- Feb 8/10: might not need this after all
		var toRemove = document.createElement("INPUT");
		toRemove.setAttribute("type", "hidden");
		toRemove.setAttribute("name", "remove_" + catAlias + "_:_" + propAlias + "_prop");
		toRemove.setAttribute("value", "1");
		// alert("remove_" + catAlias + "_:_" + propAlias + "_prop");
		myForm.appendChild(toRemove);
	}
}


function removeArrayElement(myArray, myEl)
{
	var i;
	var tmp_ar = new Array();

	for (i = 0; i < myArray.length; i++)
	{
		if (myArray[i] != myEl)
		{
			tmp_ar.push(myArray[i]);
		}
	}

	return tmp_ar;
}


// Feb. 11/10: changed function name from checkExistingReagent to checkExistingPrepID - makes more sense for Location side
function checkExistingPrepID()
{
	// do AJAX here
	url = cgiPath + "location_request_handler.py";
	xmlhttp1 = createXML();
	xmlhttp1.open("POST", url, false);
	xmlhttp1.setRequestHeader('Content-Type','application/x-www-form-urlencoded');

	var allInputs = document.getElementsByTagName("INPUT");
	var seqParams = "validate_reagent_id";
	var tmpInput;

	var c = 0;
	var inputs_ar = new Array();

	for (i=0; i < allInputs.length; i++)
	{
		tmpEl = allInputs[i];

		if ((allInputs[i].type == "text") && (allInputs[i].name == "well_LIMSID_field[]"))
		{
			tmp_lims_id = allInputs[i].value;
			tmp_input = allInputs[i];

			inputs_ar[tmp_lims_id] = tmp_input;

			// Empty wells are allowed
			if (tmp_lims_id.length > 0)
			{
				seqParams += "&rID=" + tmp_lims_id;
			}
		}
	}

	xmlhttp1.send(seqParams);
	xmlhttp1.onreadystatechange = checkReagentID(xmlhttp1, inputs_ar);

	return wellResult;
}

function checkReagentID(xmlhttp, inputs_ar)
{
// alert("in checkParents");
// 	alert(xmlhttp1.readyState);
// 	prompt("", xmlhttp.responseText);
	var result;

	if (xmlhttp.readyState == 4)
	{
// alert("ready " + xmlhttp1.readyState);
// alert("status " + xmlhttp.status);
// alert("response " + xmlhttp.responseText);

		if (xmlhttp.status == 200)
		{
// alert("status " + xmlhttp.status);
			if (xmlhttp.responseText.indexOf("ErrCode") == 0)
			{
// 				tmp_ar = xmlhttp.responseText.split("&");
// 
// 				for (i=0; i < tmp_ar.length; i++)
// 				{
					tmp_set = xmlhttp.responseText.split("=");
// alert(tmp_set);
// 					if (tmp_set[0] == 'ErrCode')
// 					{
// alert("Setting parents to false");
						rID = trimAll(tmp_set[tmp_set.length-1]);
						rInput = inputs_ar[rID];
						alert("Reagent " + rID + " does not exist in the database.  Please verify your input.");
						
						// Tip gotten from http://geekswithblogs.net/shahedul/archive/2006/08/14/87910.aspx
						// Turn autocomplete off before calling focus(); otherwise get the following error:
						// [Exception... "'Permission denied to set property XULElement.selectedIndex' when calling method: [nsIAutoCompletePopup::selectedIndex]" nsresult: "0x8057001e (NS_ERROR_XPC_JS_THREW_STRING)
						rInput.setAttribute('autocomplete','off');
						rInput.focus();
						
						wellResult = false;
// 						break;
// 					}
// 				}
			}
			else
			{
// alert("Setting parents to true");
				wellResult = true;
			}
		}
	}
}

function checkPrepType()
{
	var allInputs = document.getElementsByTagName("INPUT");
	var i;
	var all_LIMS_IDs = new Array();
	var allowed_prefixes = new Array();
	var all_prefixes = new Array();
	var lims_id_inputs = new Array();
	var prefix;

	var reg = /\w+\d+/;
	var reg_alpha = /\w+/;
	var reg_num = /\d+/;

	// find all prefixes
	for (i = 0; i < allInputs.length; i++)
	{
		if ((allInputs[i].type == "hidden") && (allInputs[i].name == "allowed_prep_prefixes[]"))
		{
			allowed_prefixes.push(allInputs[i].value);
		}
		else if ((allInputs[i].type == "hidden") && (allInputs[i].name == "all_prefixes[]"))
		{
			all_prefixes.push(allInputs[i].value);
		}
	}


	for (i = 0; i < allInputs.length; i++)
	{
		if ((allInputs[i].type == "text") && (allInputs[i].name == "well_LIMSID_field[]"))
		{
			// Empty wells are allowed
			if (allInputs[i].value.length > 0)
			{

				if (allInputs[i].value.search(reg) < 0)
				{
					alert("\"" + allInputs[i].value + "\" is not the correct input format for prep ID.  Please verify your input.");
					allInputs[i].focus();
					return false;
				}
				else
				{
					alpha_prefix = allInputs[i].value.search(reg_alpha);
					num_prefix = allInputs[i].value.search(reg_num);
	
					prefix = allInputs[i].value.substr(0, num_prefix);
					// if no prefixes have been defined anything is allowed!
					if (allowed_prefixes.length > 0)
					{
						// don't let lettercase get in the way!
						if (inArray(prefix, all_prefixes) || inArray(prefix.toUpperCase(), all_prefixes) || inArray(prefix.toLowerCase(), all_prefixes))
						{
							if (!inArray(prefix, allowed_prefixes) && !inArray(prefix.toUpperCase(), allowed_prefixes) && !inArray(prefix.toLowerCase(), allowed_prefixes))
							{
								alert("This container does not support reagents of type \"" + prefix + "\".  Please refer to the list of allowed reagent types and their prefixes at the bottom of the table.");
								allInputs[i].focus();
								return false;
							}
						}
						else
						{
							alert("Unrecognized reagent type prefix: \"" + prefix + "\".  Please verify your input.");
							allInputs[i].focus();
							return false;
						}
					}
				}
			}
		}
	}

	return true;
}

// May 30, 2011
function highlightWell(cbID, wellID)
{
	var checkbox = document.getElementById(cbID);
	var wellTD = document.getElementById(wellID);
	
	if (checkbox.checked)
		wellTD.style.backgroundColor='yellow';
	else
		wellTD.style.backgroundColor='white';
}

// May 30, 2011: Response to NM review - mimic Excel; instead of having 'select all/deselect all' buttons click on the top left corner cell
function selectOrDeselectAllWells(plateID, numWells)
{
	var allInput = document.getElementsByTagName("INPUT");
	var	numChecked = 0;

	var restrictedWells = document.getElementsByName("plate_" + plateID + "_wells_restricted[]");
	var numRestr = restrictedWells.length;

	for (i=0; i < allInput.length; i++)
	{
		tmpInput = allInput[i];		

		if ((tmpInput.type == "checkbox") && (tmpInput.id.indexOf("plate_" + plateID) == 0))
		{
			if (tmpInput.checked)
				numChecked++;

			 if (tmpInput.disabled)
				 numRestr++;
		}
	}

	var numFree = numWells - numRestr;

	if (numChecked < numFree)
		selectAllWells(plateID);
	else
		clearAllWells(plateID);
}

function selectAllWells(plateID)
{
	wellsForm = document.wells_form;
	formElem = wellsForm.elements

	for (i = 0; i <= formElem.length; i++)
	{
		element = formElem[i]

		if (element)
		{
			if ((element.name == "wells_checkbox[]") && (!element.disabled))
			{
				element.checked = true;
				parentElement = element.parentNode;

				if (parentElement.tagName == "TD")
				{
					parentTD = parentElement;

					if (parentTD.id.indexOf("well_plate_" + plateID) == 0)
					{
						parentTD.style.backgroundColor = "yellow";
					}
				}
				else if (parentElement.tagName == "DIV")
				{					
					// go one more level up
					divParent = parentElement;
					parentTD = divParent.parentNode;
		
					if (parentTD.id.indexOf("well_plate_" + plateID) == 0)
					{
						parentTD.style.backgroundColor = "yellow";
					}
				}
			}
		}
	}
/*
	// May 30, 2011: Colour well yellow
	var allTDs = document.getElementsByTagName("TD");

	for (n = 0; n < allTDs.length; n++)
	{
		tmpTD = allTDs[n];

		if (tmpTD.id.indexOf("well_plate_" + plateID) == 0)
		{
			tmpTD.style.backgroundColor = "yellow";
		}
	}
	*/
}

function clearAllWells(plateID)
{
	wellsForm = document.wells_form;
	formElem = wellsForm.elements;

	for (i = 0; i <= formElem.length; i++)
	{
		element = formElem[i];

		if (element)
		{
			if ((element.name == "wells_checkbox[]") && (!element.disabled))
			{
				element.checked = false;
				parentElement= element.parentNode;

				if (parentElement.tagName == "TD")
				{
					parentTD = parentElement;
								
					if (parentTD.id.indexOf("well_plate_" + plateID) == 0)
					{
						parentTD.style.backgroundColor = "white";
					}
				}
				else if (parentElement.tagName == "DIV")
				{
					// go one more level up
					divParent = parentElement;
					parentTD = divParent.parentNode;
					
					if (parentTD.id.indexOf("well_plate_" + plateID) == 0)
					{
						parentTD.style.backgroundColor = "white";
					}
				}
			}
		}
	}

/*
	// May 30, 2011: As we're colouring selected wells now, uncolour them
	var allTDs = document.getElementsByTagName("TD");

	for (n = 0; n < allTDs.length; n++)
	{
		tmpTD = allTDs[n];

		if (tmpTD.style.backgroundColor == "yellow")
		{
			tmpTD.style.backgroundColor = "#FFFFFF";
		}
	}
	*/
}

// Aug. 5/09: Disallow submitting empty dropdowns at reagent type creation
function checkEmptyDropdowns(formID)
{
	var myForm = document.getElementById(formID);
	var formChildren = myForm.elements;
	var i;
	var error = false;

	if (document.pressed != "Cancel")
	{
		for (i = 0; i < formChildren.length; i++)
		{
			tmpChild = formChildren[i];
	
			if ((tmpChild.type == "select") || (tmpChild.type == "select-multiple"))
			{
				if (tmpChild.name.indexOf("propertyValues_") == 0)
				{
					tmpName = tmpChild.name.substr(tmpChild.name.indexOf("propertyValues_") + ("propertyValues_".length), tmpChild.name.length);
					tmpRadio = document.getElementById("input_format_radio_list_" + tmpName);
		
					var customizable = document.getElementById("customize_cb_" + tmpName);
// alert("mult_cb_" + tmpName);
					// DON'T use 'inline' here!!!!!!!!!!
					if (!tmpRadio || (tmpRadio && tmpRadio.checked && tmpChild.style.display != "none"))
					{
						if (tmpName.indexOf("packet_id") < 0)
						{
							// alert(customizable.checked);
							
							if (!customizable || (customizable && (!customizable.checked)))
							{
								if (tmpChild.options.length == 0)
								{
		// alert(tmpName);
									error = true;
									break;
								}
								else if ((tmpChild.options.length == 1) && (tmpChild.options[0].value == "default"))
								{
									error = true;
									break;
								}
							}
						}
					}
				}
			}
		}
	
		if (error)
		{
			for (i = 0; i < formChildren.length; i++)
			{
				tmpElem = formChildren[i];
	
				if (tmpElem.type == "select-multiple")
					clearAllElements(tmpElem.id);
			}
	
	// 		allSpans = document.getElementsByTagName("SPAN");
	
			alert("Please provide a set of values for all empty dropdown lists, or select 'Free Text' as input format, if applicable.");
			tmpWarn = document.getElementById(tmpName + "_warning");
			tmpWarn.style.display = "inline";
	
			expandAllCategories();
	
	// 		for (j = 0; j < allSpans.length; j++)
	// 		{
	// 			tmpSpan = allSpans[j];
	// 
	// 			// hide rest of warnings
	// 			if ((tmpSpan.id.substr("_warning") > 0) && (tmpSpan != tmpWarn))
	// 				tmpSpan.style.display = "none";
	// 		}
	
			tmpTxt = document.getElementById("addPropertyValue_" + tmpName + "_txt");
			tmpTxt.focus();
	
			return false;
		}
	}

	return true;
}


function expandAllCategories()
{
	var categories = document.getElementsByName("categoryID[]");

	for (i = 0; i < categories.length; i++)
	{
		categoryID = categories[i].value;
// 		alert(categoryID);

		expandImg = document.getElementById(categoryID + "_expand_img");
		collapseImg = document.getElementById(categoryID + "_collapse_img");
		section = document.getElementById("category_" + categoryID + "_section");

		if (expandImg)
			expandImg.style.display = "inline";

		if (collapseImg)
			collapseImg.style.display = "none";

		if (section)
			section.style.display = "";
	}
}



// Disallow duplicates within one category.  Disallow entering the same property name in different case
function verifyAddNewProperty(newCBVal, checkboxListID, textInput)
{
	var usedProps = getAllUsed(checkboxListID);

	if (document.getElementById("reagent_type_modify"))
	{
		var a = document.getElementsByName(checkboxListID + "[]");

		// add newly added (dynamic) checkboxes
		var b = document.getElementsByName("createReagentType_" + checkboxListID + "[]");

		var t = new Array();
		var checkboxList;

		for (i=0; i < a.length; i++)
		{
			tmp_cb = a[i];
// 			alert(tmp_cb.value);
			t.push(tmp_cb);
		}

		for (i=0; i < b.length; i++)
		{
			tmp_cb = b[i];
// 			alert(tmp_cb.value);
			t.push(tmp_cb);

			if (tmp_cb.checked)
			{
// alert(tmp_cb.value + " is used");
// alert(tmp_cb.id);
				// tmp_cb.value is an alias.  Get the description
				propDescrField = document.getElementById(checkboxListID + "_:_" + tmp_cb.value + "_checkbox_desc_hidden");
// 	alert(propDescrFields);
// 	alert(tmp_cb.id);
	
				if (propDescrField)
				{
// alert(propDescrField.value);
					propDescr = propDescrField.value;

					usedProps.push(propDescr);

				}
}		
		}

		checkboxList = t;

// alert(checkboxList);

	}
	else
	{
		var checkboxList = document.getElementsByName("createReagentType_" + checkboxListID + "[]");
// 		var checkDescrList = document.getElementsByName("propCheckboxes[]");
	}

	var rType = document.getElementById("reagent_type_name");
	var rTypeName = rType.value;

	var rTypePrefixInput = document.getElementById("reagent_type_prefix");
	var rTypePrefix = rTypePrefixInput.value;

	var reagentTypeNames = document.getElementsByName("reagent_type_names[]");
	var rTypeNames = new Array();

	for (k=0; k < reagentTypeNames.length; k++)
	{
		var tmpName = reagentTypeNames[k].value.toLowerCase();
		rTypeNames.push(tmpName);
	}

	var reagentTypePrefixes = document.getElementsByName("reagent_type_prefixes[]");
	var rTypePrefixes = new Array();

	for (l = 0; l < reagentTypePrefixes.length; l++)
	{
		var tmpPrefix = reagentTypePrefixes[l];
		rTypePrefixes.push(tmpPrefix);
	}

	if ( (inArray(newCBVal.toLowerCase(), rTypeNames)) || (inArray(newCBVal.toLowerCase(), rTypePrefixes)))
	{
		alert("Property names may not be the same as reagent type names or prefixes.  Please verify your input.");
		textInput.focus();
		return false;
	}

	// Now: The words 'DNA Sequence', 'Protein Sequence', 'RNA Sequence' are allowed in the appropriate categories.  BUT 'DNA', 'RNA', 'Protein', or 'Sequence' are not allowed anywhere!!
	else if ((newCBVal.toLowerCase() == "sequence") || (newCBVal.toLowerCase() == "dna sequence") || (newCBVal.toLowerCase() == "protein sequence") || (newCBVal.toLowerCase() == "rna sequence") || (newCBVal.toLowerCase() == "dna") || (newCBVal.toLowerCase() == "rna") || (newCBVal.toLowerCase() == "protein"))
	{
		alert("You may not use 'Sequence', 'DNA Sequence', 'RNA Sequence', 'Protein Sequence',  'Protein', 'DNA' or 'RNA' as reagent type attribute names.   Please verify your input.");
		textInput.focus();
		return false;
	}

	// test 'packet id'
	var reservedPropNames = ["project id", "alternate id"];
	var reservedPropCatAlias = {"project id":"general_properties", "alternate id":"externalIDs"};
	var reservedPropCats = {"project id":"General Properties", "alternate id":"External Identifiers"};
	
	var isInUseNewCBVal = false;

	for (n=0; n < usedProps.length; n++)
	{
		tmpUsed = usedProps[n];

		if (tmpUsed.toLowerCase() == newCBVal.toLowerCase())
		{
			isInUseNewCBVal = true;
			break;
		}
	}

// alert(usedProps);
// alert(checkboxList.length);

	// June 14, 2010: Newly added categories
	if (checkboxList.length == 0)
	{
		// just give an error msg for reserved props here
		if (inArray(newCBVal.toLowerCase(), reservedPropNames))
		{
			alert("\"" + newCBVal + "\" is a reserved property name and may only appear once in category " + reservedPropCats[newCBVal.toLowerCase()] + " .  Please verify your input.");
			textInput.focus();
			return false;
		}
	}

	for (i=0; i < checkboxList.length; i++)
	{
// alert("Category " + checkboxListID);

		tmpElem = checkboxList[i];

		// Dec. 3/09
// 		tmpAlias = tmpElem.value.toLowerCase();
		tmpAlias = tmpElem.value;

// alert(tmpAlias);
// alert(newCBVal.toLowerCase());

// alert(checkboxListID + "_:_" + tmpAlias + "_checkbox_desc_hidden");

		// Get the actual description next to the checkbox
		var tmpDescHidden = document.getElementById(checkboxListID + "_:_" + tmpAlias + "_checkbox_desc_hidden");

// 		alert(tmpDescHidden);
// alert(inArray(usedProps, newCBVal));

		if (tmpDescHidden)
		{
// 			alert(tmpDescHidden.value);

			if (inArray(newCBVal.toLowerCase(), reservedPropNames))
			{
// alert(checkboxListID);
// alert(reservedPropCatAlias[newCBVal.toLowerCase()]);
// alert(checkboxListID == reservedPropCatAlias[newCBVal.toLowerCase()]);

				// For reserved props, check 2 things: whether it's a duplicate and whether it's in the correct category.  Basically, addition of these properties is prohibited, the logic here is just to issue the correct error message.

				// Duplicate in own category??  Project ID is mandatory, but alternate id is not - may be unchecked
				if (checkboxListID == reservedPropCatAlias[newCBVal.toLowerCase()])
				{
					// this is its own category.  Check if it's already been checked
					if ((tmpElem.checked) && (tmpDescHidden.value.toLowerCase() == newCBVal.toLowerCase()))
					{
						alert("You may not add '" + newCBVal + "', since a property with the same name already exists in this category.  Names entered in different LeTtErCaSe are NOT considered distinct.  If you wish to modify a recent entry, uncheck its checkbox first.\n\nPlease note that properties which already exist in OpenFreezer will not be updated.");
						textInput.focus();
						return false;
					}

					// otherwise let it through, why not
				}
				else
				{
					// If this is a different category, disallow adding this property, period
					alert("\"" + newCBVal + "\" is a reserved property name and may only appear once in category " + reservedPropCats[newCBVal.toLowerCase()] + " .  Please verify your input.");
					textInput.focus();
					return false;
				}
			}
			else
			{
				// This is not a reserved property.  Do a regular duplicate check.
				if ((tmpElem.checked) && (tmpDescHidden.value.toLowerCase() == newCBVal.toLowerCase()))
				{
					alert("You may not add '" + newCBVal + "', since a property named '" + tmpDescHidden.value + "' already exists in this category.  Names entered in different LeTtErCaSe are NOT considered distinct.  If you wish to modify a recent entry, uncheck its checkbox first.\n\nPlease note that properties which already exist in OpenFreezer will not be updated.");
					textInput.focus();
					return false;
				}
			}
		}
		// modification view
		else
		{
			if (isInUseNewCBVal)
			{
// alert("??");
				if (inArray(newCBVal.toLowerCase(), reservedPropNames))
				{
					if (checkboxListID != reservedPropCatAlias[newCBVal.toLowerCase()])
					{
						alert("\"" + newCBVal + "\" is a reserved property name and may only appear once in category " + reservedPropCats[newCBVal.toLowerCase()] + ".  Please verify your input.");
						textInput.focus();
						return false;
					}
					else
					{
						alert("You may not add '" + newCBVal + "', since a property with the same name already exists in this category.  Names entered in different LeTtErCaSe are NOT considered distinct.  If you wish to modify a recent entry, uncheck its checkbox first.\n\nPlease note that properties which already exist in OpenFreezer will not be updated.");
						textInput.focus();
						return false;
					}
				}
				else
				{
					alert("You may not add '" + newCBVal + "', since a property with the same name already exists in this category.  Names entered in different LeTtErCaSe are NOT considered distinct.  If you wish to modify a recent entry, uncheck its checkbox first.\n\nPlease note that properties which already exist in OpenFreezer will not be updated.");
					textInput.focus();
					return false;
				}
			}
			else
			{
// alert(newCBVal);
				// reserved property, e.g. Alternate ID
				if (inArray(newCBVal.toLowerCase(), reservedPropNames))
				{
					// should not go into a foreign category, period
					if (checkboxListID != reservedPropCatAlias[newCBVal.toLowerCase()])
					{
						alert("\"" + newCBVal + "\" is a reserved property name and may only appear once in category " + reservedPropCats[newCBVal.toLowerCase()] + ".  Please verify your input.");
						textInput.focus();
						return false;
					}
					else
					{
						// Check if there is a checked property with the same name in the category
						// it is not in use obviously, so again have to iterate through all the checkboxes in this category, comparing each to the new value; if a duplicate is found and it's checked issue error msg
						var tmp_cbName_ar = newCBVal.split(" ");
						var tmp_cbID = tmp_cbName_ar.join("_");

// alert(checkboxList.length);

						// first, check all EXISTING checkboxes in this category
						for (i=0; i < checkboxList.length; i++)
						{
							tmpElem = checkboxList[i];
							tmpAlias = tmpElem.value;
// 					alert(tmpElem.name);
// 					alert(tmpElem.id);
							// Get the actual description next to the checkbox
							var tmpDescHidden = document.getElementById(checkboxListID + "_:_" + tmpAlias + "_desc_hidden");
						
							// alert(tmpDescHidden);
							// alert(inArray(usedProps, newCBVal));
					
							if (tmpDescHidden)
							{
// 								alert(tmpElem.id);
// 	 							alert(tmpDescHidden.innerHTML);

								if ((tmpElem.checked) && (tmpDescHidden.innerHTML.toLowerCase() == newCBVal.toLowerCase()))
								{
									alert("You may not add '" + newCBVal + "', since a property named '" + tmpDescHidden.value + "' already exists in this category.  Names entered in different LeTtErCaSe are NOT considered distinct.  If you wish to modify a recent entry, uncheck its checkbox first.\n\nPlease note that properties which already exist in OpenFreezer will not be updated.");
									textInput.focus();
									return false;
								}
							}
						}

						// NOW check newly added ones
						if (document.getElementById(checkboxListID + "_:_" + tmp_cbID + "_checkbox_desc_hidden"))
						{
							new_cb = document.getElementById("createReagentType_" + checkboxListID + "_:_" + tmp_cbID + "_checkbox_id");

							newDescr = document.getElementById(checkboxListID + "_:_" + tmp_cbID + "_checkbox_desc_hidden").value;

							if (new_cb && newDescr && new_cb.checked)
							{
// 								alert("Duplicate " + newDescr);
								alert("You may not add '" + newCBVal + "', since a property named '" + newDescr + "' already exists in this category.  Names entered in different LeTtErCaSe are NOT considered distinct.  If you wish to modify a recent entry, uncheck its checkbox first.\n\nPlease note that properties which already exist in OpenFreezer will not be updated.");
								textInput.focus();
								return false;
							}
						}
					}
				}
				else
				{
					// Check if there is a checked property with the same name in the category
					// it is not in use obviously, so again have to iterate through all the checkboxes in this category, comparing each to the new value; if a duplicate is found and it's checked issue error msg
					var tmp_cbName_ar = newCBVal.split(" ");
					var tmp_cbID = tmp_cbName_ar.join("_");

					// first, check all EXISTING checkboxes in this category
					for (i=0; i < checkboxList.length; i++)
					{
						tmpElem = checkboxList[i];
						tmpAlias = tmpElem.value;
				
						// Get the actual description next to the checkbox
						var tmpDescHidden = document.getElementById(checkboxListID + "_:_" + tmpAlias + "_desc_hidden");
					
						// alert(tmpDescHidden);
						// alert(inArray(usedProps, newCBVal));
				
						if (tmpDescHidden)
						{
							// alert(tmpElem.id);
							// alert(tmpDescHidden.innerHTML);

							if ((tmpElem.checked) && (tmpDescHidden.innerHTML.toLowerCase() == newCBVal.toLowerCase()))
							{
								alert("You may not add '" + newCBVal + "', since a property named '" + tmpDescHidden.value + "' already exists in this category.  Names entered in different LeTtErCaSe are NOT considered distinct.  If you wish to modify a recent entry, uncheck its checkbox first.\n\nPlease note that properties which already exist in OpenFreezer will not be updated.");
								textInput.focus();
								return false;
							}
						}
					}

					// NOW check newly added ones
					if (document.getElementById(checkboxListID + "_:_" + tmp_cbID + "_checkbox_desc_hidden"))
					{
						new_cb = document.getElementById("createReagentType_" + checkboxListID + "_:_" + tmp_cbID + "_checkbox_id");

						newDescr = document.getElementById(checkboxListID + "_:_" + tmp_cbID + "_checkbox_desc_hidden").value;

						if (new_cb && newDescr && new_cb.checked)
						{
							// alert("Duplicate " + newDescr);
							alert("You may not add '" + newCBVal + "', since a property named '" + newDescr + "' already exists in this category.  Names entered in different LeTtErCaSe are NOT considered distinct.  If you wish to modify a recent entry, uncheck its checkbox first.\n\nPlease note that properties which already exist in OpenFreezer will not be updated.");
							textInput.focus();
							return false;
						}
					}
				}
			}
		}
	}

	return true;
}


// June 10, 2010: Same idea as in checkAllExclude, only don't physically check the checkboxes.  This is needed for duplicate property names check at modification
function getAllUsed(categoryAlias)
{
	categoryAlias = unescape(categoryAlias);
// alert(categoryAlias);
	var checkBoxList = document.getElementsByName(categoryAlias + "[]");
// alert(categoryAlias + "[]");
// for (c=0; c < checkBoxList.length; c++)
// 	alert(checkBoxList[c].value);
// alert(checkBoxList.length);

	var toExclude = document.getElementsByName(categoryAlias + "_exclude[]");	// hidden inputs
// alert(categoryAlias + "_exclude[]");
// alert(toExclude);
// alert(toExclude.length);

	var exclList = Array();
	var i, j;

	// get names of all unused properties (those that should not be checked)
	for (i=0; i < toExclude.length; i++)
	{
		tmpPropAlias = toExclude[i].value;
// alert(tmpPropAlias);
		excl_cb = categoryAlias + "_:_" + tmpPropAlias;

		if (document.getElementById(excl_cb))
		{
// alert("Excluding " + categoryAlias + "_:_" + tmpPropAlias);
			exclList.push(excl_cb);
		}
	}

	var usedList = Array();

	// now iterate through checboxes and check those that should not be excluded
	for (j=0; j < checkBoxList.length; j++)
	{
		tmpCB = checkBoxList[j];
// alert(tmpCB);
		// not checking isDisabled here, b/c this would MOST LIKELY be used to select all assigned properties for ORDERING - hence, need the disabled properties too
		if (!inArray(tmpCB.id, exclList))
		{
			propAlias = tmpCB.id.substring(tmpCB.id.indexOf("_:_")+3);
			propDescrField = document.getElementById(categoryAlias + "_:_" + propAlias + "_desc_hidden");
// alert(propDescrField);
// alert(categoryAlias + "_:_" + propAlias + "_desc_hidden");

			if (propDescrField)
			{
// alert(categoryAlias + "_:_" + propAlias + "_desc_hidden");
				propDescr = propDescrField.innerHTML;
// alert(propDescr);
// alert(tmpCB.id + " is used");
// 			tmpCB.checked = true;
				usedList.push(propDescr);
			}
		}
	}

	return usedList;
}

// March 30/09: For addition of new reagent types - add values from text input to checkbox groups
// checkboxListID == categoryAlias
// Update June 18/09: Don't convert to title case
function updateCheckboxListFromInput(inputID, checkboxListID, tableID, formID)
{
	inputID = unescape(inputID);
	checkboxListID = unescape(checkboxListID);

	// Groups of checkbox values contain [] in their name
	var checkboxList = document.getElementsByName("createReagentType_" + checkboxListID + "[]");

	var textInput = document.getElementById(inputID);
	var propsTable = document.getElementById(tableID);

	var newCBVal = trimAll(textInput.value);
	var tmp_cb_ar = newCBVal.toLowerCase().split(" ");
	var newCBAlias = tmp_cb_ar.join("_");

// 	var myForm = document.getElementById(formID);

	// Oct. 6/09: Hidden description field
	var tmpDescrHidden = document.createElement("INPUT");
	tmpDescrHidden.setAttribute("type", "hidden");
// 	tmpDescrHidden.setAttribute("name", "propCheckboxes[]");

	if (newCBVal.indexOf(checkboxListID + "_:_") == 0)
	{
		newCBVal = newCBVal.substring((checkboxListID + "_:_").length, newCBVal.length);
		newCBAlias = newCBAlias.substring((checkboxListID + "_:_").length, newCBAlias.length);
	}

	tmpDescrHidden.setAttribute("id", checkboxListID + "_:_" + newCBAlias + "_checkbox_desc_hidden");
	tmpDescrHidden.setAttribute("value", newCBVal);

	var newCBVal_ar = newCBVal.toLowerCase().split(" ");
	var tmpCBVal = newCBVal_ar.join("_");

	// Dec. 3/09: Do the same only not in lowercase
	var tmp_cbName_ar = newCBVal.split(" ");
	var tmp_cbID = tmp_cbName_ar.join("_");
	
	// Oct. 29/09: set flag 'new'
	var tmp_flag = document.createElement("INPUT");
	tmp_flag.setAttribute("type", "hidden");
// 	tmp_flag.setAttribute("name", "is_new_" + checkboxListID + "_:_" + tmpCBVal);
	tmp_flag.setAttribute("name", "is_new_" + checkboxListID + "_:_" + tmp_cbID);

// 	tmp_flag.setAttribute("id", "isNew_" + checkboxListID + "_:_" + tmpCBVal);
	tmp_flag.setAttribute("id", "isNew_" + checkboxListID + "_:_" + tmp_cbID);

	tmp_flag.setAttribute("value", true);
	document.body.appendChild(tmp_flag);

	var newCB = document.createElement("INPUT");
	newCB.type = "checkbox";
	
	var myForm = document.getElementById(formID);

	if (verifyAddNewProperty(newCBVal, checkboxListID, textInput))
	{
		newCB.setAttribute("name", "createReagentType_" + checkboxListID + "[]");
		
		// Dec. 3/09: NO, don't use lowercase alias, confuses 'PROMOTER' and 'Promoter'
// 		newCB.setAttribute("id", "createReagentType_" + checkboxListID + "_:_" + tmpCBVal + "_checkbox_id");
		newCB.setAttribute("id", "createReagentType_" + checkboxListID + "_:_" + tmp_cbID + "_checkbox_id");
// alert("createReagentType_" + checkboxListID + "_:_" + tmp_cbID + "_checkbox_id");
// 		newCB.value = tmpCBVal;
		newCB.value = tmp_cbID;

		newCB.setAttribute("checked", "true");

		if (tableID.indexOf("addReagentType") == 0)
			var tRows = propsTable.rows.length;		// last row is the 'add' button
		else
			var tRows = propsTable.rows.length-1;		// last row is the 'add' button
	
		if (checkboxList.length%4 == 0)
		{
			var lastRow = propsTable.insertRow(tRows);
			var tmpCell = lastRow.insertCell(0);
		}
		else
		{
			// try-catch block added Aug. 11/09: For dynamically generated categories it's the last table row
			try
			{
				var lastRow = propsTable.rows[tRows-1];
			}
			catch(e)	// for dynamically generated categories it's the last table row
			{
				var lastRow = propsTable.rows[tRows];
			}
		
			var tmpCell = lastRow.insertCell(lastRow.cells.length);
		}
	
		tmpCell.setAttribute("white-space", "nowrap");
		tmpCell.setAttribute("padding-left", "25px");
		tmpCell.setAttribute("padding-right", "10px");
	
		if (tableID.indexOf("category_") == 0)
			tmpCell.innerHTML += "&nbsp;&nbsp;&nbsp;&nbsp;";	// July 31/09: only way to fix alignment right now, keep!!

		tmpCell.appendChild(newCB);

		// June 18/09: Don't convert to title case
		tmpCell.innerHTML += newCBVal;

		// Dec. 3/09: try to avoid using lowercase
// 		tmpDescrHidden.setAttribute("id", checkboxListID + "_:_" + tmpCBVal + "_checkbox_desc_hidden");
		tmpDescrHidden.setAttribute("id", checkboxListID + "_:_" + tmp_cbID + "_checkbox_desc_hidden");
// alert(checkboxListID + "_:_" + tmp_cbID + "_checkbox_desc_hidden");
// 		tmpDescrHidden.setAttribute("name", "createReagentType_" + checkboxListID + "_:_" + tmpCBVal);
		tmpDescrHidden.setAttribute("name", "createReagentType_" + checkboxListID + "_:_" + tmp_cbID);

		document.body.appendChild(tmpDescrHidden);

		textInput.value = "";	
		textInput.focus();
	}
}


function collectReagentTypeProperties(formID)
{
	var myForm = document.getElementById(formID);
	var props = document.getElementsByTagName("INPUT");

	var i, j;
	var categoryAlias;
	var pDescrHidden;
	var propAlias;

	for (j=0; j < props.length; j++)
	{
		var tmpProp = props[j];		// a checkbox

		if (tmpProp.type == "checkbox")
		{
			if (tmpProp.checked == true)
			{
// alert("Checkbox name " + tmpProp.id);
				tmpName = tmpProp.id;	// e.g. "createReagentType_general_properties_:_notes_checkbox_id"
// 		alert(tmpName);
				// Extract the category
				categoryAlias = tmpName.substring("createReagentType_".length, tmpName.indexOf("_:_"));
	
				propAlias = tmpName.substring(("createReagentType_" + categoryAlias + "_:_").length, tmpName.indexOf("_checkbox_id"));
// 		alert(propAlias);
				// grab the description
				// Dec. 3/09: DON'T use getElementById - properties that are unchecked and re-entered in different lettercase both receive the same ID (their name is converted to lowercase to generate the alias, e.g. 'promoter' and 'Promoter') - and then get the problem Karen described with 'larger' and 'LARGER' - IDs are assigned at checkbox creation, so when these properties are entered one at a time (one is unchecked and the second entered), their identical IDs remain, one of them is passed to Python, which often ends up being the wrong one (in later versions of Firefox the LAST value is saved while in earlier versions the first one is saved)
				tmpDescr = document.getElementById(categoryAlias + "_:_" + propAlias + "_checkbox_desc_hidden");

// 				tmpDescr = document.getElementsByName("createReagentType_" + categoryAlias + "_:_" + propAlias)

				/*
				for (i=0; i < tmpDescrs.length; i++)
				{
					tmpDescr = tmpDescrs[i];*/

					if (tmpDescr)
					{
						if (tmpDescr.value != "")
						{
// 	alert("Saving property " + tmpDescr.value);
							myForm.appendChild(tmpDescr);
	
							// 'isNew' flag
							tmp_flag = document.getElementById("isNew_" + categoryAlias + "_:_" + propAlias);
							myForm.appendChild(tmp_flag);
	
							// DO **NOT**, UNDER ANY CIRCUMSTANCES, DO THIS!!!!!!!  IT WILL RESULT IN THE VERY LAST CHECKBOX NOT BEING SAVED ISSUE!!!!!!!!!
	// NO!!!!!!!					myForm.appendChild(tmpProp);
						}
					}
// 				}
			}
		}
	}
}

// June 17/09 - At new reagent type creation, give option to exclude sequence
function hideSequenceSections()
{
	var dnaHeadingRow = document.getElementById("dna_sequence_heading_row");
	var proteinHeadingRow = document.getElementById("protein_sequence_heading_row");
	var rnaHeadingRow = document.getElementById("rna_sequence_heading_row");

	var dnaPropsRow = document.getElementById("dna_sequence_props_row");
	var proteinPropsRow = document.getElementById("protein_sequence_props_row");
	var rnaPropsRow = document.getElementById("rna_sequence_props_row");

	var dnaAddNewPropsRow = document.getElementById("dna_sequence_add_new_props_row");
	var proteinAddNewPropsRow = document.getElementById("protein_sequence_add_new_props_row");
	var rnaAddNewPropsRow = document.getElementById("rna_sequence_add_new_props_row");

	var dnaFeaturesHeadingRow = document.getElementById("dna_sequence_features_heading_row");
	var dnaFeaturesRow = document.getElementById("dna_sequence_features_row");
	var dnaAddNewFeaturesRow = document.getElementById("dna_sequence_add_new_features_row");

	var proteinFeaturesHeadingRow = document.getElementById("protein_sequence_features_heading_row");
	var proteinFeaturesRow = document.getElementById("protein_sequence_features_row");
	var proteinAddNewFeaturesRow = document.getElementById("protein_sequence_add_new_features_row");

	var rnaFeaturesHeadingRow = document.getElementById("rna_sequence_features_heading_row");
	var rnaFeaturesRow = document.getElementById("rna_sequence_features_row");
	var rnaAddNewFeaturesRow = document.getElementById("rna_sequence_add_new_features_row");

	// hide all
	dnaHeadingRow.style.display = "none";
	dnaPropsRow.style.display = "none";
	dnaAddNewPropsRow.style.display = "none";
	dnaFeaturesHeadingRow.style.display = "none";
	dnaFeaturesRow.style.display = "none";
	dnaAddNewFeaturesRow.style.display = "none";

	proteinHeadingRow.style.display = "none";
	proteinPropsRow.style.display = "none";
	proteinAddNewPropsRow.style.display = "none";

	proteinFeaturesHeadingRow.style.display = "none";
	proteinFeaturesRow.style.display = "none";
	proteinAddNewFeaturesRow.style.display = "none";

	rnaHeadingRow.style.display = "none";
	rnaPropsRow.style.display = "none";
	rnaAddNewPropsRow.style.display = "none";

	rnaFeaturesHeadingRow.style.display = "none";
	rnaFeaturesRow.style.display = "none";
	rnaAddNewFeaturesRow.style.display = "none";
}


// June 10/09: Different sections for each of the 3 sequence types - DNA, RNA, Protein
function showDNASequenceSection()
{
	var dnaHeadingRow = document.getElementById("dna_sequence_heading_row");
	var proteinHeadingRow = document.getElementById("protein_sequence_heading_row");
	var rnaHeadingRow = document.getElementById("rna_sequence_heading_row");

	var dnaPropsRow = document.getElementById("dna_sequence_props_row");
	var proteinPropsRow = document.getElementById("protein_sequence_props_row");
	var rnaPropsRow = document.getElementById("rna_sequence_props_row");

	var dnaAddNewPropsRow = document.getElementById("dna_sequence_add_new_props_row");
	var proteinAddNewPropsRow = document.getElementById("protein_sequence_add_new_props_row");
	var rnaAddNewPropsRow = document.getElementById("rna_sequence_add_new_props_row");

	var dnaFeaturesHeadingRow = document.getElementById("dna_sequence_features_heading_row");
	var dnaFeaturesRow = document.getElementById("dna_sequence_features_row");
	var dnaAddNewFeaturesRow = document.getElementById("dna_sequence_add_new_features_row");

	var proteinFeaturesHeadingRow = document.getElementById("protein_sequence_features_heading_row");
	var proteinFeaturesRow = document.getElementById("protein_sequence_features_row");
	var proteinAddNewFeaturesRow = document.getElementById("protein_sequence_add_new_features_row");

	var rnaFeaturesHeadingRow = document.getElementById("rna_sequence_features_heading_row");
	var rnaFeaturesRow = document.getElementById("rna_sequence_features_row");
	var rnaAddNewFeaturesRow = document.getElementById("rna_sequence_add_new_features_row");

	document.getElementById("addReagentTypeSequenceProperties").style.display = "table-row";
	document.getElementById("addReagentTypeProteinSequenceProperties").style.display = "none";
	document.getElementById("addReagentTypeRNASequenceProperties").style.display = "none";
	
	document.getElementById("addReagentTypeFeatures").style.display = "table-row";
	document.getElementById("addReagentTypeProteinFeatures").style.display = "none";
	document.getElementById("addReagentTypeRNAFeatures").style.display = "none";

	// show DNA and hide the rest
	dnaHeadingRow.style.display = "table-row";
	dnaPropsRow.style.display = "table-row";
	dnaAddNewPropsRow.style.display = "table-row";
	dnaFeaturesHeadingRow.style.display = "table-row";
	dnaFeaturesRow.style.display = "table-row";
	dnaAddNewFeaturesRow.style.display = "table-row";

	proteinHeadingRow.style.display = "none";
	proteinPropsRow.style.display = "none";
	proteinAddNewPropsRow.style.display = "none";

	proteinFeaturesHeadingRow.style.display = "none";
	proteinFeaturesRow.style.display = "none";
	proteinAddNewFeaturesRow.style.display = "none";

	rnaHeadingRow.style.display = "none";
	rnaPropsRow.style.display = "none";
	rnaAddNewPropsRow.style.display = "none";

	rnaFeaturesHeadingRow.style.display = "none";
	rnaFeaturesRow.style.display = "none";
	rnaAddNewFeaturesRow.style.display = "none";
}


function showHideCategory(categoryID, rTypeID)
{
	if (rTypeID && (rTypeID != ""))
	{
		expandImg = document.getElementById(categoryID + "_expand_img_" + rTypeID);
		collapseImg = document.getElementById(categoryID + "_collapse_img_" + rTypeID);
		section = document.getElementById("category_" + categoryID + "_section_" + rTypeID);
	}
	else
	{
		expandImg = document.getElementById(categoryID + "_expand_img");
		collapseImg = document.getElementById(categoryID + "_collapse_img");
		section = document.getElementById("category_" + categoryID + "_section");	
	}

	if (expandImg.style.display == "inline")	// want to collapse section
	{
		expandImg.style.display = "none";
		collapseImg.style.display = "inline";
		section.style.display = "none";

		if (categoryID == "sequence")
		{
			hideDNASequenceSection();
			hideRNASequenceSection();
			hideProteinSequenceSection();
		}
	}
	else
	{
		expandImg.style.display = "inline";
		collapseImg.style.display = "none";
		section.style.display = "";

		if (categoryID == "sequence")
		{
			// show the proper sequence section
			noneRadio = document.getElementById("sequenceRadioNone");
			dnaRadio = document.getElementById("sequenceRadioDNA");
			rnaRadio = document.getElementById("sequenceRadioRNA");
			proteinRadio = document.getElementById("sequenceRadioProtein");

			if (dnaRadio.checked)
				showDNASequenceSection();
			
			if (proteinRadio.checked)
				showProteinSequenceSection();
			
			if (rnaRadio.checked)
				showRNASequenceSection();
		}
	}
}

function hideDNASequenceSection()
{
	document.getElementById("dna_sequence_props_row").style.display = "none";
	document.getElementById("dna_sequence_heading_row").style.display = "none";
	document.getElementById("addReagentTypeSequenceProperties").style.display = "none";
	document.getElementById("dna_sequence_add_new_props_row").style.display = "none";
	document.getElementById("dna_sequence_features_heading_row").style.display = "none";
	document.getElementById("addReagentTypeFeatures").style.display = "none";
	document.getElementById("dna_sequence_add_new_features_row").style.display = "none";
}


function hideRNASequenceSection()
{
	document.getElementById("rna_sequence_props_row").style.display = "none";
	document.getElementById("rna_sequence_heading_row").style.display = "none";
	document.getElementById("addReagentTypeRNASequenceProperties").style.display = "none";
	document.getElementById("rna_sequence_add_new_props_row").style.display = "none";
	document.getElementById("rna_sequence_features_heading_row").style.display = "none";
	document.getElementById("addReagentTypeRNAFeatures").style.display = "none";
	document.getElementById("rna_sequence_add_new_features_row").style.display = "none";
}


function hideProteinSequenceSection()
{	
	document.getElementById("protein_sequence_props_row").style.display = "none";
	document.getElementById("protein_sequence_heading_row").style.display = "none";
	document.getElementById("addReagentTypeProteinSequenceProperties").style.display = "none";
	document.getElementById("protein_sequence_add_new_props_row").style.display = "none";
	document.getElementById("protein_sequence_features_heading_row").style.display = "none";
	document.getElementById("addReagentTypeProteinFeatures").style.display = "none";
	document.getElementById("protein_sequence_add_new_features_row").style.display = "none";
}

// June 12/09
function cgiFileUpload(formID, fileID, fInputID)
{
	var myForm = document.getElementById(formID);
	var fileName = document.getElementById(fileID);
	var fileInput = document.getElementById(fInputID);

	if (fileName)
	{
		var fName = fileName.value;

		if (fileInput)
		{
			fileInput.value = fName;

			if (myForm)
			{
				myForm.submit();
			}
		}
	}
}


// June 10/09: Different sections for each of the 3 sequence types - DNA, RNA, Protein
function showProteinSequenceSection()
{
	var dnaHeadingRow = document.getElementById("dna_sequence_heading_row");
	var proteinHeadingRow = document.getElementById("protein_sequence_heading_row");
	var rnaHeadingRow = document.getElementById("rna_sequence_heading_row");

	var dnaPropsRow = document.getElementById("dna_sequence_props_row");
	var proteinPropsRow = document.getElementById("protein_sequence_props_row");
	var rnaPropsRow = document.getElementById("rna_sequence_props_row");

	var dnaAddNewPropsRow = document.getElementById("dna_sequence_add_new_props_row");
	var proteinAddNewPropsRow = document.getElementById("protein_sequence_add_new_props_row");
	var rnaAddNewPropsRow = document.getElementById("rna_sequence_add_new_props_row");

	var dnaFeaturesHeadingRow = document.getElementById("dna_sequence_features_heading_row");
	var dnaFeaturesRow = document.getElementById("dna_sequence_features_row");
	var dnaAddNewFeaturesRow = document.getElementById("dna_sequence_add_new_features_row");

	var proteinFeaturesHeadingRow = document.getElementById("protein_sequence_features_heading_row");
	var proteinFeaturesRow = document.getElementById("protein_sequence_features_row");
	var proteinAddNewFeaturesRow = document.getElementById("protein_sequence_add_new_features_row");

	var rnaFeaturesHeadingRow = document.getElementById("rna_sequence_features_heading_row");
	var rnaFeaturesRow = document.getElementById("rna_sequence_features_row");
	var rnaAddNewFeaturesRow = document.getElementById("rna_sequence_add_new_features_row");

	document.getElementById("addReagentTypeSequenceProperties").style.display = "none";
	document.getElementById("addReagentTypeProteinSequenceProperties").style.display = "table-row";
	document.getElementById("addReagentTypeRNASequenceProperties").style.display = "none";
	
	document.getElementById("addReagentTypeFeatures").style.display = "none";
	document.getElementById("addReagentTypeProteinFeatures").style.display = "table-row";
	document.getElementById("addReagentTypeRNAFeatures").style.display = "none";

	// show Protein and hide the rest
	proteinHeadingRow.style.display = "table-row";
	proteinPropsRow.style.display = "table-row";
	proteinAddNewPropsRow.style.display = "table-row";

	proteinFeaturesHeadingRow.style.display = "table-row";
	proteinFeaturesRow.style.display = "table-row";
	proteinAddNewFeaturesRow.style.display = "table-row";

	dnaHeadingRow.style.display = "none";
	dnaPropsRow.style.display = "none";
	dnaAddNewPropsRow.style.display = "none";

	dnaFeaturesHeadingRow.style.display = "none";
	dnaFeaturesRow.style.display = "none";
	dnaAddNewFeaturesRow.style.display = "none";

	rnaHeadingRow.style.display = "none";
	rnaPropsRow.style.display = "none";
	rnaAddNewPropsRow.style.display = "none";

	rnaFeaturesHeadingRow.style.display = "none";
	rnaFeaturesRow.style.display = "none";
	rnaAddNewFeaturesRow.style.display = "none";

}


// June 10/09: Different sections for each of the 3 sequence types - DNA, RNA, Protein
function showRNASequenceSection()
{
	var dnaHeadingRow = document.getElementById("dna_sequence_heading_row");
	var proteinHeadingRow = document.getElementById("protein_sequence_heading_row");
	var rnaHeadingRow = document.getElementById("rna_sequence_heading_row");

	var dnaPropsRow = document.getElementById("dna_sequence_props_row");
	var proteinPropsRow = document.getElementById("protein_sequence_props_row");
	var rnaPropsRow = document.getElementById("rna_sequence_props_row");

	var dnaAddNewPropsRow = document.getElementById("dna_sequence_add_new_props_row");
	var proteinAddNewPropsRow = document.getElementById("protein_sequence_add_new_props_row");
	var rnaAddNewPropsRow = document.getElementById("rna_sequence_add_new_props_row");

	var dnaFeaturesHeadingRow = document.getElementById("dna_sequence_features_heading_row");
	var dnaFeaturesRow = document.getElementById("dna_sequence_features_row");
	var dnaAddNewFeaturesRow = document.getElementById("dna_sequence_add_new_features_row");

	var proteinFeaturesHeadingRow = document.getElementById("protein_sequence_features_heading_row");
	var proteinFeaturesRow = document.getElementById("protein_sequence_features_row");
	var proteinAddNewFeaturesRow = document.getElementById("protein_sequence_add_new_features_row");

	var rnaFeaturesHeadingRow = document.getElementById("rna_sequence_features_heading_row");
	var rnaFeaturesRow = document.getElementById("rna_sequence_features_row");
	var rnaAddNewFeaturesRow = document.getElementById("rna_sequence_add_new_features_row");

	document.getElementById("addReagentTypeSequenceProperties").style.display = "none";
	document.getElementById("addReagentTypeProteinSequenceProperties").style.display = "none";
	document.getElementById("addReagentTypeRNASequenceProperties").style.display = "table-row";
	
	document.getElementById("addReagentTypeFeatures").style.display = "none";
	document.getElementById("addReagentTypeProteinFeatures").style.display = "none";
	document.getElementById("addReagentTypeRNAFeatures").style.display = "table-row";

	// show Protein and hide the rest
	proteinHeadingRow.style.display = "none";
	proteinPropsRow.style.display = "none";
	proteinAddNewPropsRow.style.display = "none";

	proteinFeaturesHeadingRow.style.display = "none";
	proteinFeaturesRow.style.display = "none";
	proteinAddNewFeaturesRow.style.display = "none";

	dnaHeadingRow.style.display = "none";
	dnaPropsRow.style.display = "none";
	dnaAddNewPropsRow.style.display = "none";
	dnaFeaturesHeadingRow.style.display = "none";
	dnaFeaturesRow.style.display = "none";
	dnaAddNewFeaturesRow.style.display = "none";

	rnaHeadingRow.style.display = "table-row";
	rnaPropsRow.style.display = "table-row";
	rnaAddNewPropsRow.style.display = "table-row";

	rnaFeaturesHeadingRow.style.display = "table-row";
	rnaFeaturesRow.style.display = "table-row";
	rnaAddNewFeaturesRow.style.display = "table-row";
}


function verifyAddNewCategory(newCategory)
{
	return true;
}


function addPropertiesCategory(inputID, tableID, rowID, formID)
{
	var textInput = document.getElementById(inputID);
	var propsTable = document.getElementById(tableID);
	var propsRow = document.getElementById(rowID);

// 	alert(propsRow);

	var newCategoryRow = propsTable.insertRow(propsRow.rowIndex);
	var newCategory = textInput.value;

// 	if (formID == "")
// 		var myForm = document.addReagentTypeForm;
// 		var myForm = document.getElementById("add_reagent_type_form");
// 	else
		var myForm = document.getElementById(formID);

	var rTypeID = document.getElementById("reagent_type_name");

	// Add warning if this category exists

	if (verifyAddNewCategory(newCategory))
	{
		// watch out for names consijoin them with a '_'
		var tmpStr_ar = newCategory.split(' ');
		var tmpCatName = "";
	
		if (tmpStr_ar.length > 1)
			tmpCatName = tmpStr_ar.join('_');
		else
			tmpCatName = newCategory;
	
		var newCollapseImg = document.createElement("IMG");		// down-facing arrow, section collapses when clicked
		var newExpandImg = document.createElement("IMG");	// right arrow, section expands when clicked

		if (rTypeID && (rTypeID != ""))
		{
			newCollapseImg.setAttribute("id", newCategory + "_collapse_img_" + rTypeID);
			newExpandImg.setAttribute("id", newCategory + "_expand_img_" + rTypeID);
		}
		else
		{
			newCollapseImg.setAttribute("id", newCategory + "_collapse_img");
			newExpandImg.setAttribute("id", newCategory + "_expand_img");
		}

		// expanded arrow image attributes
		newCollapseImg.setAttribute("src", hostName + "pictures/arrow_collapse.gif");
		newCollapseImg.setAttribute("width", "20");
		newCollapseImg.setAttribute("height", "15");
		newCollapseImg.setAttribute("border", "0");
		newCollapseImg.setAttribute("alt", "bullet");
		newCollapseImg.style.paddingRight = "8px";
		newCollapseImg.style.verticalAlign = "middle";
		newCollapseImg.style.paddingBottom = "2px";
		newCollapseImg.onclick = function(){showHideCategory(tmpCatName);};

		// ditto collapsed image
		newExpandImg.setAttribute("src", hostName + "pictures/arrow_expand.gif");
		newExpandImg.setAttribute("width", "20");
		newExpandImg.setAttribute("height", "15");
		newExpandImg.setAttribute("border", "0");
		newExpandImg.setAttribute("alt", "bullet");
		newExpandImg.style.paddingRight = "8px";
		newExpandImg.style.verticalAlign = "middle";
		newExpandImg.style.paddingBottom = "2px";
		newExpandImg.style.display = "none";
		newExpandImg.onclick = function(){showHideCategory(tmpCatName);};
	
		var tmpCell = newCategoryRow.insertCell(0);
		tmpCell.style.paddingLeft = "5px";
		tmpCell.style.paddingTop = "10px";
		tmpCell.style.fontWeight = "bold";
		tmpCell.style.fontSize = "10pt";
		tmpCell.style.color = "#0000D2";
	
		tmpCell.innerHTML = "<BR>";
		tmpCell.appendChild(newCollapseImg);
		tmpCell.appendChild(newExpandImg);
		tmpCell.innerHTML += /*titleCase(*/newCategory/*)*/;	// no, don't change case here
	
		var newCatCB_row = propsTable.insertRow(propsRow.rowIndex);
		var newCatCB_cell = newCatCB_row.insertCell(0);
		newCatCB_cell.style.paddingLeft = "36px";
		var tmpNewCatCB_tbl = document.createElement("TABLE");
		tmpNewCatCB_tbl.style.cellSpacing = "2";
	
		// Add 'check all' and 'clear all' links
		var checkLink = document.createElement("SPAN");
		checkLink.setAttribute('id', "checkAll");
		checkLink.className = "linkShow";
		checkLink.innerHTML = "Check All";
		checkLink.style.marginLeft = "10px";
		checkLink.style.fontSize = "8pt";
		checkLink.style.fontWeight = "normal";
		checkLink.onclick = function(){checkAll(tmpCatName);};
		tmpCell.appendChild(checkLink);
	
		// Add 'check all' and 'clear all' links
		var uncheckLink = document.createElement("SPAN");
		uncheckLink.setAttribute('id', "uncheckAll");
		uncheckLink.className = "linkShow";
		uncheckLink.innerHTML = "Uncheck All";
		uncheckLink.style.marginLeft = "10px";
		uncheckLink.style.fontSize = "8pt";
		uncheckLink.style.fontWeight = "normal";
		uncheckLink.onclick = function(){uncheckAll(tmpCatName);};
		tmpCell.appendChild(uncheckLink);
	
		var tmpNewCatCB_tbl_id = "addReagentType" + tmpCatName;
		tmpNewCatCB_tbl.setAttribute("id", tmpNewCatCB_tbl_id);
		newCatCB_cell.appendChild(tmpNewCatCB_tbl);
	
		var tmpAddOtherTxt = document.createElement("INPUT");
	// 	tmpAddOtherTxt.type = "text";
	
		var tmpCatID = tmpCatName.toLowerCase() + "_other";
		tmpAddOtherTxt.setAttribute("id", tmpCatID);
	
	// 	tmpAddOtherTxt.onKeyPress = function(){disableEnterKey("keypress")};
	
		var tmpAddOtherBtn = document.createElement("INPUT");
		tmpAddOtherBtn.type = "button";
		tmpAddOtherBtn.value = "Add";
	
		var addOtherRow = propsTable.insertRow(propsRow.rowIndex);
		var addOtherCell = addOtherRow.insertCell(0);
	
		addOtherCell.style.paddingLeft = "36px";
		addOtherCell.style.fontWeight = "bold";
		addOtherCell.setAttribute("padding-left", "36px");
	
		addOtherCell.innerHTML = "Add new " + newCategory + " value: ";
		addOtherCell.appendChild(tmpAddOtherTxt);
	
		addOtherCell.innerHTML += "&nbsp;";
		addOtherCell.appendChild(tmpAddOtherBtn);
	
		var newCatNameInputHidden = document.createElement("INPUT");
		newCatNameInputHidden.setAttribute("type", "hidden");
	
		// April 6/09 - don't think so, see if causes problems later
	// 	newCatNameInputHidden.setAttribute("name", tmpCatName);
		newCatNameInputHidden.setAttribute("name", "category[]");
		newCatNameInputHidden.setAttribute("value", tmpCatName);
		myForm.appendChild(newCatNameInputHidden);
	
		// New April 15/09: Add category descriptor as hidden input
		var newCatDescrInputHidden = document.createElement("INPUT");
		newCatDescrInputHidden.setAttribute("type", "hidden");
	
		newCatDescrInputHidden.setAttribute("name", "category_descriptor_" + tmpCatName);
		newCatDescrInputHidden.setAttribute("value", newCategory);
		myForm.appendChild(newCatDescrInputHidden);
	
	// 	tmpAddOtherTxt.onKeyDown = function(){disableEnterKey(new Event())};
	// 	myForm.appendChild(tmpAddOtherTxt);
	
		tmpAddOtherBtn.onclick = function(){updateCheckboxListFromInput(tmpCatID, tmpCatName, tmpNewCatCB_tbl_id, formID)}
		textInput.value = "";
	
		document.getElementById(tmpCatID).focus();
	}
}


// function checkParentFormat(rType, assocID, assocAlias)
function checkParentFormat(rType)
{
// 	alert("in checkParentFormat " + rType);

	// call AJAX script
	url = cgiPath + "validate_parents.py";
	xmlhttp1 = createXML();
	xmlhttp1.open("POST", url, false);
	xmlhttp1.setRequestHeader('Content-Type','application/x-www-form-urlencoded');

	// alert(rType);
	// alert(rType + "_assoc_" + assocID + "_prop");

	var allInputs = document.getElementsByTagName("INPUT");
	var i;
	var seqParams;
	var inputs_ar = new Array();

	curr_username = document.getElementById("curr_username_hidden").value;

	seqParams = "rType=" + rType + "&curr_username=" + curr_username;

	switch(rType)
	{
		case 'Vector':
			parent_vector_id_txt = document.getElementById("parent_vector_id_txt");
			parent_vector_id = parent_vector_id_txt.value;

			pv_assocID = document.getElementById("Vector_assoc_parent_vector_input").value;
			
			if (parent_vector_id.length > 0)
			{
				inputs_ar[parent_vector_id] = parent_vector_id_txt;
				seqParams += "&assocID=" + pv_assocID + "&parent_vector=" + parent_vector_id;
			}

			insert_id_txt = document.getElementById("insert_id_txt");
			ipv_id_txt = document.getElementById("ipv_id_txt");

			if (insert_id_txt)
			{
				insert_id = insert_id_txt.value;
				insert_assocID = document.getElementById("Vector_assoc_insert_id_input").value;

				if (insert_id.length > 0)
				{
					inputs_ar[insert_id] = insert_id_txt;
					seqParams += "&assocID=" + insert_assocID + "&insert_id=" + insert_id;
				}
			}
			else
			{
				ipv_id = ipv_id_txt.value;
				ipv_assocID = document.getElementById("Vector_assoc_parent_insert_vector_input").value;
// alert(ipv_assocID);
				if (ipv_id.length > 0)
				{
					inputs_ar[ipv_id] = ipv_id_txt;
					seqParams += "&assocID=" + ipv_assocID + "&parent_insert_vector=" + ipv_id;
				}
			}

		break;

// 		case 'Insert':
// 		break;

// 		case 'Oligo':
// 		break;

		case 'CellLine':
			parent_cell_line_txt = document.getElementById("cl_id_txt");
			parent_cell_line_id = parent_cell_line_txt.value;

			parent_vector_txt = document.getElementById("pv_id_txt");
			parent_vector_id = parent_vector_txt.value;

			pcl_assocID = document.getElementById("CellLine_assoc_parent_cell_line_input").value;
			pv_assocID = document.getElementById("CellLine_assoc_cell_line_parent_vector_input").value;

			if (parent_cell_line_id.length > 0)
			{
				inputs_ar[parent_cell_line_id] = parent_cell_line_txt;
			}

			if (parent_vector_id.length > 0)
			{
				inputs_ar[parent_vector_id] = parent_vector_txt;
			}

			seqParams += "&assocID=" + pcl_assocID + "&parent_cell_line=" + parent_cell_line_id;
			seqParams += "&assocID=" + pv_assocID + "&cell_line_parent_vector=" + parent_vector_id;
		break;

		default:

			for (i=0; i < allInputs.length; i++)
			{
				tmpEl = allInputs[i];
				// alert(tmpEl.type);
		
				if (tmpEl.type.toLowerCase() == "text")
				{
					// debug
		// 			if (tmpEl.name.indexOf("assoc") >= 0)
		// 				alert(tmpEl.name);
		
					if ((tmpEl.name.indexOf(rType + "_assoc_") == 0) && (tmpEl.name.indexOf("_prop") == tmpEl.name.length-"_prop".length))
					{
// 		alert(tmpEl.id);
						assocID = tmpEl.name.substring((rType + "_assoc_").length, tmpEl.name.indexOf("_prop"));
// 		alert(assocID);
						assocAlias = tmpEl.id.substring((rType + "_assoc_").length, tmpEl.id.indexOf("_input"))
// 		alert(assocAlias);
						tmpParent = tmpEl.value;
// 		alert(tmpParent);
		
						if (tmpParent.length > 0)
						{
							inputs_ar[tmpParent] = allInputs[i];
							seqParams += "&assocID=" + assocID + "&" + assocAlias + "=" + tmpParent;
						}
					}
				}
			}

		break;
	}

// alert(seqParams);

	xmlhttp1.send(seqParams);
	xmlhttp1.onreadystatechange = checkParents(xmlhttp1, rType, inputs_ar);

	return parentsValid;
}

function checkParents(xmlhttp, rType, inputs_ar)
{
	if (xmlhttp.readyState == 4)
	{
// 		alert("ready " + xmlhttp1.readyState);

		if (xmlhttp.status == 200)
		{
// 			alert("status " + xmlhttp.status);
// 			prompt("", xmlhttp.responseText);

			if (xmlhttp.responseText.indexOf("ErrCode") == 0)
			{
				tmp_ar = xmlhttp.responseText.split("&");

				tmp_errcode_ar = tmp_ar[0].split("=");

				if (tmp_errcode_ar[0] == 'ErrCode')
				{
					err_code = tmp_errcode_ar[1];

					switch (err_code)
					{
						case '1':
							// non-existing prefix, not found in database (e.g. 'D' when the only available prefixes are 'V', 'I', 'C', 'O')

							parent_type_id_err_set = tmp_ar[1];
							parent_type_id_err_ar = parent_type_id_err_set.split("=");
							parentID = trimAll(parent_type_id_err_ar[1]);

							alert(parentID + " does not match an existing reagent ID in OpenFreezer.  Please verify your input.");
							
							fName = inputs_ar[parentID];
							fName.setAttribute('autocomplete','off');
							fName.focus();

							parentsValid = false;

						break;
// 						
						case '2':
							// reagent ID exists but the association is wrong, e.g. user entered an Insert ID where a Vector was expected

							// Error format: "ErrCode=2&&assoc=" + assocName + "&rID=" + parent_id

							assoc_err_set = tmp_ar[1];
							assoc_err_ar = assoc_err_set.split("=");
							assocName = assoc_err_ar[1];
// alert(assocName);
							parent_type_id_err_set = tmp_ar[2];
							parent_type_id_err_ar = parent_type_id_err_set.split("=");
// alert(parent_type_id_err_ar[1]);
							parentID = trimAll(parent_type_id_err_ar[1]);

							alert(parentID + " is not a valid " + assocName + ".  Please verify your input.");
							
							fName = inputs_ar[parentID];
							fName.setAttribute('autocomplete','off');
							fName.focus();

							parentsValid = false;

						break;
						
						case '3':
							// user doesn't have read access to parent project
// print "ErrCode=2&assoc=" + assocName + "&rID=" + parent_id
							assoc_err_set = tmp_ar[1];
							assoc_err_ar = assoc_err_set.split("=");
							assocName = assoc_err_ar[1];
// alert(assocName);
							parent_type_id_err_set = tmp_ar[2];
							parent_type_id_err_ar = parent_type_id_err_set.split("=");
// alert(parent_type_id_err_ar[1]);
							parentID = trimAll(parent_type_id_err_ar[1]);

							alert("You may not use " + parentID + " as your " + assocName + ", since you do not have Read access to its project.  Please contact the project owner to obtain access.");
							
							fName = inputs_ar[parentID];
							fName.setAttribute('autocomplete','off');
							fName.focus();

							parentsValid = false;
							return
						
						break;
					}
				}

// 				for (i=0; i < tmp_ar.length; i++)
// 				{
// 					tmp_set = tmp_ar[i].split("=");
// // alert(tmp_set);
// 
// 					if (tmp_set[0] == 'ErrCode')
// 					{
// // alert("Setting parents to false");
// // alert(xmlhttp.responseText);
// 						err_code = tmp_set[1];
// 
// 						switch (err_code)
// 						{
// 							case '1':
// 								// non-existing reagent ID (prefix was found in table but numeric portion does not match an existing ID in database)
// 
// 								// Error format: ErrCode=1&assoc=" + assocName + "&assocAlias=" + assocAlias + "&" + `assocID` + "=" + parent_id
// 							break;
// 							
// 							case '2':
// 								// reagent ID exists but the association is wrong, e.g. user entered an Insert ID where a Vector was expected
// 
// 								// Error format: ErrCode=2&prefix=" + parent_prefix + "&assoc=" + assocName + "&assocAlias=" + assocAlias
// 
// 							break;
// 							
// 							case '3':
// 								// non-existing prefix, not found in database (e.g. 'D' when the only available prefixes are 'V', 'I', 'C', 'O')
// 
// 								// Error format: ErrCode=3&parent=" + parent_id
// 							break;
// 						}
// 
// 						parent_alias = trimAll(tmp_set[0]);
// 						parent_id = trimAll(tmp_set[1]);
// 
// 						alert("Parent " + parent_id + " does not exist in the database.  Please verify your input.");
// 
// 						parentInput = document.getElementById(currReagentType +  "_assoc_" + parent_alias + "_input");
// 
// 						// Tip gotten from http://geekswithblogs.net/shahedul/archive/2006/08/14/87910.aspx
// 						// Turn autocomplete off before calling focus(); otherwise get the following error:
// 						// [Exception... "'Permission denied to set property XULElement.selectedIndex' when calling method: [nsIAutoCompletePopup::selectedIndex]" nsresult: "0x8057001e (NS_ERROR_XPC_JS_THREW_STRING)
// 						parentInput.setAttribute('autocomplete','off');
// 
// 						parentInput.focus();
// 	
// 						parentsValid = false;
// 						break;
// 					}
// 				}
			}
			else
			{
// alert("Setting parents to true");
				parentsValid = true;
			}
		}
	}

// 	alert(parentsValid);
}


/**
	Oct. 6/09, Marina: Property name verification at creation of new reagent type:

	A property name CANNOT:

		- be the same as its containing category name (e.g. CANNOT have 'miscellaneous' property under 'miscellaneous' category; OK across different categories - i.e. CAN have 'miscellaneous' sequence feature, a 'Miscellaneous' category and a 'miscellaneous' classifier)

		- be the same as a reagent type name (current or other existing type) - i.e. cannot have a property or category called 'Vector'

		- be the same as a reagent type prefix (current or other existing prefix)

		- be called 'sequence', 'dna', 'protein', 'rna', 'DNA sequence', 'protein sequence', 'rna sequence' (even though these words may be **contained within** the property's name - e.g. 'DNA Sequence Features')

	Some of the above restrictions also apply to category names (if indicated).

// REMOVED COMPLETELY DEC. 2/09: MOVED CHECK TO verifyAddNewProperty()
function verifyPropertyAndCategoryNames()
{
	var rType = document.getElementById("reagent_type_name");
	var rTypeName = rType.value;

	var rTypePrefixInput = document.getElementById("reagent_type_prefix");
	var rTypePrefix = rTypePrefixInput.value;

	var reagentTypeNames = document.getElementsByName("reagent_type_names[]");
	var rTypeNames = new Array();

	for (k=0; k < reagentTypeNames.length; k++)
	{
		var tmpName = reagentTypeNames[k].value.toLowerCase();
		rTypeNames.push(tmpName);
	}

	var reagentTypePrefixes = document.getElementsByName("reagent_type_prefixes[]");
	var rTypePrefixes = new Array();

	for (l = 0; l < reagentTypePrefixes.length; l++)
	{
		var tmpPrefix = reagentTypePrefixes[l];
		rTypePrefixes.push(tmpPrefix);
	}

	// traverse over properties and check each, do sequence separately
	var allInputs = document.getElementsByTagName("INPUT");

	for (i=0; i < allInputs.length; i++)
	{
		var tmpInput = allInputs[i];

		if (tmpInput.type.toLowerCase() == "checkbox")
		{
			if (tmpInput.checked == true)
			{
				tmpName = tmpInput.id;	// e.g. "createReagentType_general_properties_:_notes_checkbox_id"
		
				// Extract the category
				categoryAlias = tmpName.substring("createReagentType_".length, tmpName.indexOf("_:_"));
	
				propAlias = tmpName.substring(("createReagentType_" + categoryAlias + "_:_").length, tmpName.indexOf("_checkbox_id"));
		
				// grab the description
				tmpDescr = document.getElementById(categoryAlias + "_:_" + propAlias + "_checkbox_desc_hidden");
				
				if (tmpDescr)
				{
					if (tmpDescr.value != "")
					{
// 						alert(tmpDescr.value);
// 						alert(tmpDescr.name);

						if ( (inArray(tmpDescr.value.toLowerCase(), rTypeNames)) || (inArray(tmpDescr.value.toLowerCase(), rTypePrefixes)))
						{
							alert("Property names may not be the same as reagent type names or prefixes.  Please verify your input.");
							tmpDescr.focus();
							return false;
						}

						// Now: The words 'DNA Sequence', 'Protein Sequence', 'RNA Sequence' are allowed in the appropriate categories.  BUT 'DNA', 'RNA', 'Protein', or 'Sequence' are not allowed anywhere!!
						else if ((categoryAlias == "sequence_properties") || (categoryAlias == "protein_sequence_properties") || (categoryAlias == "rna_sequence_properties"))
						{
							// Allow 'DNA Sequence', 'RNA Sequence', 'Protein Sequence', disallow the rest
							if ((tmpDescr.value.toLowerCase() == "dna") || (tmpDescr.value.toLowerCase() == "rna") || (tmpDescr.value.toLowerCase() == "protein") || (tmpDescr.value.toLowerCase() == "sequence"))
							{
								alert("'Sequence', 'Protein', 'DNA' or 'RNA' may not be used as reagent type attribute names.  Please verify your input.");
								tmpDescr.focus();
								return false;
							}
						}
						else
						{
							// Disallow 'DNA Sequence', 'RNA Sequence', 'Protein Sequence', 'DNA', 'RNA', 'Protein', 'Sequence'
							if ((tmpDescr.value.toLowerCase() == "sequence") || (tmpDescr.value.toLowerCase() == "dna sequence") || (tmpDescr.value.toLowerCase() == "protein sequence") || (tmpDescr.value.toLowerCase() == "rna sequence") || (tmpDescr.value.toLowerCase() == "dna") || (tmpDescr.value.toLowerCase() == "rna") || (tmpDescr.value.toLowerCase() == "protein"))
							{
								alert("'Sequence', 'DNA Sequence', 'RNA Sequence', 'Protein Sequence',  'Protein', 'DNA' or 'RNA' may not be used as reagent type attribute names.   Please verify your input.");
								tmpDescr.focus();
								return false;
							}
						}
						
					}
				}
			}
		}
	}	
}
*/

function verifyUniqueReagentTypeName()
{
	var allInputs = document.getElementsByTagName("INPUT");

	var rType = document.getElementById("reagent_type_name");
	var rTypeName = rType.value;

	var uniqueWarning = document.getElementById("reagent_type_name_warning");

	if (rTypeName.length <= 3)
	{
		alert("Reagent type names must be over 3 characters in length.  Please verify your input.");
		rType.focus();
		uniqueWarning.style.display = "inline";
		return false;
	}

	if ( (rTypeName.toLowerCase() == 'sequence') || (rTypeName.toLowerCase() == 'dna sequence') || (rTypeName.toLowerCase() == 'rna sequence') || (rTypeName.toLowerCase() == 'protein sequence'))
	{
		alert("You may NOT use 'sequence', 'DNA sequence', 'RNA sequence', 'Protein sequence' as the reagent type name.  Please select a different name for the new reagent type.");
		rType.focus();
		uniqueWarning.style.display = "inline";
		return false;
	}

	for (i=0; i < allInputs.length; i++)
	{
		var tmpInput = allInputs[i];

		if (tmpInput.name == "reagent_type_names[]")
		{
			if (rTypeName.toLowerCase() == tmpInput.value.toLowerCase())
			{
				alert("The name provided for the new reagent type already exists.  Please choose a unique name for the new reagent type.");
				rType.focus();
				uniqueWarning.style.display = "inline";
				return false;
			}
		}
	}

	return true;
}


function verifyReagentTypePrefix()
{
	var allInputs = document.getElementsByTagName("INPUT");

	var rTypePrefixInput = document.getElementById("reagent_type_prefix");
	var rTypePrefix = rTypePrefixInput.value;

	var reagentTypeNameInput = document.getElementById("reagent_type_name");
	var reagentTypeName = reagentTypeNameInput.value;

	var prefixWarning = document.getElementById("reagent_type_prefix_warning");

	for (i=0; i < allInputs.length; i++)
	{
		var tmpInput = allInputs[i];

		if (tmpInput.name == "reagent_type_prefixes[]")
		{
			if (rTypePrefix.toLowerCase() == tmpInput.value.toLowerCase())
			{
				alert("The prefix provided for the new reagent type already exists.  Please choose a unique prefix for the new reagent type.");
				rTypePrefixInput.focus();
				prefixWarning.style.display = "inline";
				return false;
			}
		}
	}

	// prefixes must be under 3 characters in length
	if (rTypePrefix.length > 3)
	{
		alert("Prefixes must be 3 characters in length or less and may not be the same as the reagent type name.  Please verify your input.");
		rTypePrefixInput.focus();
		prefixWarning.style.display = "inline";
		return false;
	}

	// name may not be the same as prefix (lowercase to make comparison case-insensitive)
	if (reagentTypeName.toLowerCase() == rTypePrefix.toLowerCase())
	{
		alert("Prefixes may not be the same as the reagent type name.  Please verify your input.");
		rTypePrefixInput.focus();
		prefixWarning.style.display = "inline";
		return false;
	}

	// disallow numbers in prefixes
	numstr = "0123456789";

	for (i=0; i < rTypePrefix.length; i++)
	{
		ch = rTypePrefix.charAt(i);

		if (numstr.indexOf(ch) >= 0)
		{
			alert("Prefixes may not contain digits.  Please verify your input.");
			rTypePrefixInput.focus();
			prefixWarning.style.display = "inline";
			return false;
		}
	}

	return true;
}


// April 3/09
function verifyNewReagentTypeName()
{
	var rTypeName = document.getElementById("reagent_type_name");
	var rTypeNameWarning = document.getElementById("reagent_type_name_warning");

	if (rTypeName.value.length == 0)
	{
		alert("Please provide a name for the new reagent type");
		rTypeNameWarning.style.display = "inline";
		rTypeName.focus();
		return false;
	}
	else
	{
		rTypeNameWarning.style.display = "none";
		return true;
	}
}


// April 3/09
function verifyNewReagentTypePrefix()
{
	var rTypePrefix = document.getElementById("reagent_type_prefix");
	var rTypePrefixWarning = document.getElementById("reagent_type_prefix_warning");

	if (rTypePrefix.value.length == 0)
	{
		alert("Please specify a unique prefix for the new reagent type");
		rTypePrefixWarning.style.display = "inline";
		rTypePrefix.focus();
		return false;
	}
	else
	{
		rTypePrefixWarning.style.display = "none";
		return true;
	}
}

// April 3/09 - Check all checkboxes at the click of a button
function checkAll(cbID)
{
	cbID = unescape(cbID);

	var checkBoxList = document.getElementsByName(cbID+"[]");
	var tmpCB;

	for (i = 0; i < checkBoxList.length; i++)
	{
		tmpCB = checkBoxList[i];

		if (!tmpCB.disabled)
			tmpCB.checked = true;
	}
}

// June 7, 2010: check all except <arg>.  Used in reagent type modification, when want to check only all properties assigned to the reagent type, e.g. for ordering
function checkAllExclude(categoryAlias)
{
	categoryAlias = unescape(categoryAlias);
// alert(categoryAlias);
	var checkBoxList = document.getElementsByName(categoryAlias + "[]");
// alert(categoryAlias + "[]");
// alert(checkBoxList);
// alert(checkBoxList.length);

	var toExclude = document.getElementsByName(categoryAlias + "_exclude[]");	// hidden inputs
// alert(categoryAlias + "_exclude[]");
// alert(toExclude);
// alert(toExclude.length);

	var exclList = Array();
	var i, j;

	// get names of all unused properties (those that should not be checked)
	for (i=0; i < toExclude.length; i++)
	{
		tmpPropAlias = toExclude[i].value;
// alert(tmpPropAlias);
		excl_cb = categoryAlias + "_:_" + tmpPropAlias;

		if (document.getElementById(excl_cb))
		{
// alert("Excluding " + categoryAlias + "_:_" + tmpPropAlias);
			exclList.push(excl_cb);
		}
	}

	// now iterate through checboxes and check those that should not be excluded
	for (j=0; j < checkBoxList.length; j++)
	{
		tmpCB = checkBoxList[j];
// alert(tmpCB);
		// not checking isDisabled here, b/c this would MOST LIKELY be used to select all assigned properties for ORDERING - hence, need the disabled properties too
		if (!inArray(tmpCB.id, exclList))
		{
// alert("not in use");
			tmpCB.checked = true;
		}
	}
}

function enableCheckboxes()
{
// alert("here");
	var checkBoxList = document.getElementsByTagName("INPUT");

	for (i = 0; i < checkBoxList.length; i++)
	{
		tmpCB = checkBoxList[i];

		if ((tmpCB.type == "checkbox") && (tmpCB.disabled == true))
		{
// alert(tmpCB.value);
			tmpCB.disabled = false;
		}
	}
}


// June 9/09: 'Delete' button on detailed view
function deleteReagentFromDetailedView(rID)
{
	if (confirm("Are you sure you wish to delete this reagent?"))
	{
		// call AJAX script
		url = cgiPath + "delete.py";
		xmlhttp1 = createXML();
		xmlhttp1.open("POST", url, false);
		xmlhttp1.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	
		seqParams = "rID=" + rID;
		
		xmlhttp1.send(seqParams);
		// alert(xmlhttp1.readyState);
// 		alert(xmlhttp1.responseText);
// 		xmlhttp1.onreadystatechange = deleteReagent(xmlhttp1);

		str_rid = "rID=";
		str_status = "&Status=";

		limsID = xmlhttp1.responseText.substring(str_rid.length, xmlhttp1.responseText.indexOf(str_status));

		result = xmlhttp1.responseText.substring(xmlhttp1.responseText.indexOf(str_status)+str_status.length);

		if (result == 1)
		{
			document.body.className = "cursor_auto";

			alert("Deletion completed.");
			window.location.href = hostName + "search.php?View=1";
		}
		else
		{
			document.body.className = "cursor_auto";
			alert("You may not delete " + limsID + ", since there are preps associated with it.");
		}
	}
}


// June 5/09
function deleteReagentsFromSearch(rSetName)
{
// alert(rSetName);
	var xmlhttp1;
	var url;

	// Make sure at least one checkbox is checked before prompting - user could click the Delete button w/o selecting anything!
	var actn = false;
	var checkBoxList = document.getElementsByName(rSetName + "[]");

	for (i = 0; i < checkBoxList.length; i++)
	{
		if (checkBoxList[i].checked)
		{
			actn = true;
			break;
		}
	}

	var err = false;

	if (actn)
	{
		if (confirm("Are you sure you wish to delete selected reagents?"))
		{
			document.body.className = "cursor_wait";

			var checkBoxList = document.getElementsByName(rSetName + "[]");
		
	// 		alert(checkBoxList.length);
	
			for (i = 0; i < checkBoxList.length; i++)
			{
				xmlhttp1 = createXML();
				url = cgiPath + "delete.py";
			// 	xmlhttp1 = createXML();
				xmlhttp1.open("POST", url, false);
	
				if (checkBoxList[i].checked)
				{
					rID = checkBoxList[i].value;
	// 	alert(rID);
					if (document.getElementById("has_children_" + rID))
					{
						tmp_rid = document.getElementById("has_children_" + rID).value;
	
						alert("You may not delete " + tmp_rid + ", since it is a parent of other reagents.  You should delete these children first before attempting to delete " + tmp_rid);
	
						return false;
					}
					else if (document.getElementById("write_access_" + rID))
					{
						tmp_rid = document.getElementById("write_access_" + rID).value;
		
						alert("You may not delete " + tmp_rid + ", since you do not have Write access to its project.  Please contact the project owner to obtain permission to delete.");
		
						return false;
					}

					// call AJAX script
					xmlhttp1.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
					seqParams = "rID=" + rID;
					xmlhttp1.send(seqParams);

					// alert(xmlhttp1.readyState);
					// alert(xmlhttp1.responseText);

					// process AJAX response
					str_rid = "rID=";
					str_status = "&Status=";

					rID = xmlhttp1.responseText.substring(str_rid.length, xmlhttp1.responseText.indexOf(str_status));

					result = xmlhttp1.responseText.substring(xmlhttp1.responseText.indexOf(str_status)+str_status.length);

					if (result != 1)
					{
						document.body.className = "cursor_auto";
						alert("You may not delete " + rID + ", since there are preps associated with it.");
						err = true;
					}
					else
					{
						err = false;
					}
				}
			}

			if (!err)
			{
				document.body.className = "cursor_auto";
				alert("Deletion completed.");
				window.location.href = hostName + "search.php?View=1";
			}
		}
	}
}

// function deleteReagent(xmlhttp1)
// {
// 	if (xmlhttp1.readyState == 4)
// 	{
// 		if (xmlhttp1.status == 200)
// 		{
// 			str_rid = "rID=";
// 			str_status = "&Status=";
// 
// 			rID = xmlhttp1.responseText.substring(str_rid.length, xmlhttp1.responseText.indexOf(str_status));
// // 			alert(rID);
// 
// 			result = xmlhttp1.responseText.substring(xmlhttp1.responseText.indexOf(str_status)+str_status.length);
// // 			alert(result);
// 
// 			document.getElementById("del_status_rid_" + rID).setAttribute("value", result);
// 		}
// 	}
// }


// July 1, 2010: for bug report page
function checkModule()
{
	var modules = document.getElementById("modules_list");

	if ((modules.selectedIndex == 0) || (modules[modules.selectedIndex].value == 'default'))
	{
		alert("Please select a module from the list.");
		modules.focus();
		modules.style.color = "red";
		return false;
	}

	return true;
}


// July 1, 2010: for bug report page
function checkIssue()
{
	var bugDescr = document.getElementById("bugDescription");
	var modules = document.getElementById("modules_list");

	if (trimAll(bugDescr.value) == "")
	{
		alert("Please describe in detail your issue or feature request.");
		bugDescr.focus();
		modules.style.color = "black";
		return false;
	}

	return true;
}


// Modified May 20, 2010: passing ATTRIBUTE IDs, not property names
function checkMandatoryProps(rType, categoryAlias)
{
	var mandatoryProps = document.getElementsByName(rType + "_mandatoryProps[]");

//alert(categoryAlias);

//alert(rType + "_mandatoryProps[]");
	var allWarnings = document.getElementsByName("warnings[]");

	if ((document.pressed == 'Save') || (document.pressed == 'Create'))
	{
		for (i=0; i < mandatoryProps.length; i++)
		{
			attrID = mandatoryProps[i].value;
		
	//alert("Mandatory: values_" + attrID);

			propField = document.getElementById("values_" + attrID);

			if (propField)
			{
				//alert(propField.id);

				pfName = propField.name;

				// May 11, 2011: Differentiate between creation and modification views!!!!

				url = window.location.href;
				url_pre = hostName + "/Reagent.php?View=";

				view_number = url.substr(url_pre.length-1, 1);

				if ((view_number != '6') || document.getElementById('preload_cell_line'))
				{
					// Extract prop alias and category alias from field name
					//alert(pfName);

					p1 = pfName.substr(("reagent_detailedview_" + rType + "_").length, pfName.length);
					catAlias = p1.substr(0, p1.indexOf("_:_"));
					//alert(catAlias);

					prefix = "reagent_detailedview_" + rType + "_" + catAlias + "_:_";
					//alert(prefix);

					// alert(pfName);
					// alert(pfName.length);

					p2 = pfName.substr(prefix.length, pfName.lastIndexOf("_prop"));
					pAlias = p2.substr(0, p2.length-"_prop".length);

		// 			alert(pAlias);

					if (propField.type == "text")
					{
						if (trimAll(propField.value).length == 0)
						{
							alert("Please provide values for all mandatory properties.");
							propField.focus();

							// show err msg box
							errMsg = document.getElementById("warn_" + attrID);

							if (errMsg)
							{
								errMsg.style.display = "inline";

								// hide the rest of the error msgs
								for (i=0; i < allWarnings.length; i++)
								{
									if (allWarnings[i].id != "warn_" + attrID)
										allWarnings[i].style.display = "none";
								}

								propField.focus();
							}

							return false;
						}
					}
					else if ((propField.type == "select-one") || (propField.type == "select-multiple"))
					{
						// April 15, 2011: prevent error on empty project lists
						if ((pAlias == "packet_id") && (propField.options.length == 0))
						{
							alert("Project list is empty.  Please obtain write access to at least one project before creating reagents.");
							return false;
						}

						if (propField.selectedIndex == 0)
						{			
							// For empty open/closed, check type of insert, and return true if type of insert is 'cDNA w/ UTRs' or 'DNA Fragment'
							if (pAlias == "open_closed")
							{
								var iTypeAttrID = document.getElementById("type_of_insert_attr_id_hidden").value;
			 //alert(iTypeAttrID);
								var itype_list = document.getElementById("values_" + iTypeAttrID);
			 //alert(itype_list);
								
								// if open/closed is empty, check type of insert
								if ((itype_list[itype_list.selectedIndex].value.toLowerCase() != 'cdna with utrs') && (itype_list[itype_list.selectedIndex].value.toLowerCase() != 'dna fragment'))
								{
									alert("Please provide values for all mandatory properties.");
									propField.focus();

									// show err msg box
									//errMsg = document.getElementById("warn_" + attrID);
									for (i=0; i < allWarnings.length; i++)
									{
										if (allWarnings[i].id != "warn_" + attrID)
											allWarnings[i].style.display = "none";
										else
											allWarnings[i].style.display = "inline";
									}

									return false;
								}
							}
							else
							{
								alert("Please provide values for all mandatory properties.");
								propField.focus();

								// show err msg box
								//errMsg = document.getElementById("warn_" + attrID);
								for (i=0; i < allWarnings.length; i++)
								{
									if (allWarnings[i].id != "warn_" + attrID)
										allWarnings[i].style.display = "none";
									else
										allWarnings[i].style.display = "inline";
								}

								return false;
							}
						}
					}
				}
				else
				{					
					// find out which category we're editing!  e.g. for Inserts mandatory props are in different categories, general name, status, project, and classifier/sequence type of insert and open/closed
					// ALSO for Inserts: open/closed and type of insert are under Classifiers AND Sequence!!!!!!!!!!!!!!!!

					p1 = pfName.substr("reagent_detailedview_".length, pfName.length);
//					alert(p1);

					prefix = "reagent_detailedview_";
					//alert(prefix);
					postfix = "_prop";

					// alert(pfName);
					// alert(pfName.length);

					pAlias_prop = pfName.substr(prefix.length, pfName.lastIndexOf(postfix)-1);
					pAlias = pAlias_prop.substr(0, pAlias_prop.lastIndexOf(postfix));
					//alert(pAlias);
			//	}

					// In case mandatory properties are in different categories
					var currTable = document.getElementById(categoryAlias + "_tbl_modify");

	//alert("current category " + categoryAlias);
	//alert(propField.parentNode.parentNode.id);
	//alert(pAlias);
					if ((propField.parentNode.parentNode.id == categoryAlias) && (currTable.style.display != "none"))
					{					
						if (currTable.style.display != "none")
						{
							if (propField.type == "text")
							{
								if (trimAll(propField.value).length == 0)
								{
									alert("Please provide values for all mandatory properties.");
									propField.focus();

									// show err msg box
									errMsg = document.getElementById("warn_" + attrID);

									if (errMsg)
									{
										errMsg.style.display = "inline";

										// hide the rest of the error msgs
										for (i=0; i < allWarnings.length; i++)
										{
											if (allWarnings[i].id != "warn_" + attrID)
												allWarnings[i].style.display = "none";
										}

										propField.focus();
									}

									return false;
								}
							}
							else if ((propField.type == "select-one") || (propField.type == "select-multiple"))
							{
								// April 15, 2011: prevent error on empty project lists
								if ((pAlias == "packet_id") && (propField.options.length == 0))
								{
									alert("Project list is empty.  Please obtain write access to at least one project before creating reagents.");
									return false;
								}

								if (propField.selectedIndex == 0)
								{			
									// For empty open/closed, check type of insert, and return true if type of insert is 'cDNA w/ UTRs' or 'DNA Fragment'
									if (pAlias == "open_closed")
									{
										var iTypeAttrID = document.getElementById("type_of_insert_attr_id_hidden").value;
					 //alert(iTypeAttrID);
										var itype_list = document.getElementById("values_" + iTypeAttrID);
					 //alert(itype_list);
										
										// if open/closed is empty, check type of insert
										if ((itype_list[itype_list.selectedIndex].value.toLowerCase() != 'cdna with utrs') && (itype_list[itype_list.selectedIndex].value.toLowerCase() != 'dna fragment'))
										{
											alert("Please provide values for all mandatory properties.");
											propField.focus();

											// show err msg box
											//errMsg = document.getElementById("warn_" + attrID);
											for (i=0; i < allWarnings.length; i++)
											{
												if (allWarnings[i].id != "warn_" + attrID)
													allWarnings[i].style.display = "none";
												else
													allWarnings[i].style.display = "inline";
											}

											return false;
										}
									}
									else
									{
										alert("Please provide values for all mandatory properties.");
										propField.focus();

										// show err msg box
										//errMsg = document.getElementById("warn_" + attrID);
										for (i=0; i < allWarnings.length; i++)
										{
											if (allWarnings[i].id != "warn_" + attrID)
												allWarnings[i].style.display = "none";
											else
												allWarnings[i].style.display = "inline";
										}

										return false;
									}
								}
							}
						}
					}
				}

				/*
					// grab all the properties under this specific category!!!!!!!!!!
					
					allWarnings = document.getElementsByName("warnings[]");

					if (propField.type == "text")
					{
						if (trimAll(propField.value).length == 0)
						{
							alert("Please provide values for all mandatory properties.");
							propField.focus();

							// show err msg box
							errMsg = document.getElementById("warn_" + attrID);

							if (errMsg)
							{
								errMsg.style.display = "inline";

								// hide the rest of the error msgs
								for (i=0; i < allWarnings.length; i++)
								{
									if (allWarnings[i].id != "warn_" + attrID)
										allWarnings[i].style.display = "none";
								}

								propField.focus();
							}

							return false;
						}
					}
					else if ((propField.type == "select-one") || (propField.type == "select-multiple"))
					{				
						// April 15, 2011: prevent error on empty project lists
						if ((pAlias == "packet_id") && (propField.options.length == 0))
						{
							alert("Project list is empty.  Please obtain write access to at least one project before creating reagents.");
							return false;
						}

						//alert(pAlias);

						// Update June 23, 2010: allow a blank open/closed value for certain types of insert (change code)
						if ((propField[propField.selectedIndex].value == 'default') || (propField[propField.selectedIndex].value == '') || (propField[propField.selectedIndex].value.indexOf("-- Select ") == 0))
						//if (propField.selectedIndex == 0)
						{
							//alert(propField.id);
							//alert(pAlias);
							//alert(categoryAlias);
							//alert(thisCategory);

							if (propField.id == categoryAlias + "_open_closed")
							{
								// check insert type - can leave open/closed empty for certain types of insert
								if (document.getElementById("type_of_insert_attr_id_hidden"))
								{
	//		  alert("type of insert");
									var iTypeAttrID = document.getElementById("type_of_insert_attr_id_hidden").value;
	//		 alert(iTypeAttrID);
									var itype_list = document.getElementById("values_" + iTypeAttrID);
	//		 alert(itype_list);
			
									if ((itype_list[itype_list.selectedIndex].value != "cDNA with UTRs") && (itype_list[itype_list.selectedIndex].value != "DNA Fragment") && (itype_list[itype_list.selectedIndex].value != "None"))
									{
										alert("Please provide values for all mandatory properties.");
										propField.focus();
			// show err msg box
									
										// hide the rest of the error msgs
										for (i=0; i < allWarnings.length; i++)
										{
											if (allWarnings[i].id != "warn_" + attrID)
												allWarnings[i].style.display = "none";
											else
												allWarnings[i].style.display = "inline";
										}
										
										return false;
									}
									else
										return true;
								}
							}
							// YEAH, BUT CANNOT change type of insert if we're editing a different category!!!!
							else
							{
								alert(propField.id);
								alert(propField[propField.selectedIndex].value);

								alert("Please provide values for all mandatory properties.");
								propField.focus();

								// show err msg box
								//errMsg = document.getElementById("warn_" + attrID);
								for (i=0; i < allWarnings.length; i++)
								{
									if (allWarnings[i].id != "warn_" + attrID)
										allWarnings[i].style.display = "none";
									else
										allWarnings[i].style.display = "inline";
								}

								return false;
							}
						}
						else if ((pAlias == "packet_id") && (propField.selectedIndex == 0))
						{
							alert("Please provide values for all mandatory properties.");
							propField.focus();

							errMsg = document.getElementById("warn_" + attrID);
				
							if (errMsg)
							{
								errMsg.style.display = "inline";

								// hide the rest of the error msgs
								for (i=0; i < allWarnings.length; i++)
								{
									if (allWarnings[i].id != "warn_" + attrID)
										allWarnings[i].style.display = "none";
								}

								propField.focus();
							}

							return false;
						}
					}
					*/
			}
			else
			{
				propField = document.getElementById("targetList_" + attrID);

				if (propField)
				{
					//alert(propField.id);

					pfName = propField.name;

					// May 11, 2011: Differentiate between creation and modification views!!!!

					url = window.location.href;
					url_pre = hostName + "/Reagent.php?View=";

					view_number = url.substr(url_pre.length-1, 1);

					if ((view_number != '6') || document.getElementById('preload_cell_line'))
					{
						// Extract prop alias and category alias from field name
						//alert(pfName);

						p1 = pfName.substr(("reagent_detailedview_" + rType + "_").length, pfName.length);
						catAlias = p1.substr(0, p1.indexOf("_:_"));
						//alert(catAlias);

						prefix = "reagent_detailedview_" + rType + "_" + catAlias + "_:_";
						//alert(prefix);

						// alert(pfName);
						// alert(pfName.length);

						p2 = pfName.substr(prefix.length, pfName.lastIndexOf("_prop"));
						pAlias = p2.substr(0, p2.length-"_prop".length);

						if (propField.options.length == 0)
						{
							alert("Please provide values for all mandatory properties.");
							propField.focus();

							// show err msg box
							//errMsg = document.getElementById("warn_" + attrID);
							for (i=0; i < allWarnings.length; i++)
							{
								if (allWarnings[i].id != "warn_" + attrID)
									allWarnings[i].style.display = "none";
								else
									allWarnings[i].style.display = "inline";
							}

							return false;	
						}
					}
					else
					{
						// find out which category we're editing!  e.g. for Inserts mandatory props are in different categories, general name, status, project, and classifier/sequence type of insert and open/closed
						// ALSO for Inserts: open/closed and type of insert are under Classifiers AND Sequence!!!!!!!!!!!!!!!!

						p1 = pfName.substr("reagent_detailedview_".length, pfName.length);
	//					alert(p1);

						prefix = "reagent_detailedview_";
						//alert(prefix);
						postfix = "_prop";

						// alert(pfName);
						// alert(pfName.length);

						pAlias_prop = pfName.substr(prefix.length, pfName.lastIndexOf(postfix)-1);
						pAlias = pAlias_prop.substr(0, pAlias_prop.lastIndexOf(postfix));
						//alert(pAlias);
				//	}

						// In case mandatory properties are in different categories
						var currTable = document.getElementById(categoryAlias + "_tbl_modify");

		//alert("current category " + categoryAlias);
		//alert(propField.parentNode.parentNode.id);
		//alert(pAlias);
						if ((propField.parentNode.parentNode.id == categoryAlias) && (currTable.style.display != "none"))
						{					
							if (currTable.style.display != "none")
							{
								if (propField.options.length == 0)
								{
									alert("Please provide values for all mandatory properties.");
									propField.focus();

									// show err msg box
									//errMsg = document.getElementById("warn_" + attrID);
									for (i=0; i < allWarnings.length; i++)
									{
										if (allWarnings[i].id != "warn_" + attrID)
											allWarnings[i].style.display = "none";
										else
											allWarnings[i].style.display = "inline";
									}

									return false;	
								}
							}
						}
					}
				}
			}
		}
	}
	enableSelect();
	return true;
}


// April 3/09 - Uncheck all checkboxes at the click of a button
// Update June 1/09: Some properties are mandatory - added 'exclude' parameter so these boxes are never unchecked
function uncheckAll(cbID, exclude)
{
	cbID = unescape(cbID);

	var checkBoxList = document.getElementsByName(cbID+"[]");
	var tmpCB;

// 	alert(exclude);

	for (i = 0; i < checkBoxList.length; i++)
	{
		tmpCB = checkBoxList[i];
// alert(tmpCB.value);
		if (/*(exclude.length > 0) && */!inArray(tmpCB.value, exclude))
			tmpCB.checked = false;
	}
}

// April 3/09: Dynamically substitute new reagent type name in different sections on creation page
function fillReagentTypeNames()
{
	var rTypeName = document.getElementById("reagent_type_name").value;
	var rTypeNames = document.getElementsByName('newReagentType');
	var tmpTypeName;

	for (i = 0; i < rTypeNames.length; i++)
	{
		tmpTypeName = rTypeNames[i];
		tmpTypeName.value = rTypeName;
		tmpTypeName.innerHTML = rTypeName;
	}
}


// Oct. 20/09
function replaceAll(str, lookFor, replaceWith)
{
	var i;
	var newString = "";

	for (i=0; i < str.length; i++)
	{
		if (i == 0)
		{
			if(str.charAt(i) == "'")
			{
				newString += "\\" + str.charAt(i);
			}
			else
			{
				newString += str.charAt(i);
			}
		}
		else
		{
			if ((str.charAt(i) == "'") && (str.charAt(i-1) != "\\"))
			{
				newString += "\\" + str.charAt(i);
			}
			else
			{
				newString += str.charAt(i);
			}
		}

	}

	return newString;
}


function formatInput(formID)
{
	var myForm = document.getElementById(formID);
	var elems = myForm.elements;
	var elemLength = elems.length;
	var i;

	for (i=0; i < elemLength; i++)
	{
		var tmpElem = elems[i];

		if (tmpElem.value.indexOf('%') >= 0)
		{
			alert("Before: " + tmpElem.value);
// 			tmpElem.value = tmpElem.value.replace(/%/g,'%25')
// 			tmpElem.value = escape(tmpElem.value);
tmpElem.value = tmpElem.value.replace('%', '%25');
			alert("After: " + tmpElem.value);
		}
	}
}
// April 6/09: Show/hide property values input table when defining list of property values for a new reagent type
function showHideAddPropertyValuesInput(propAlias)
{
// alert(propAlias);

	// ATTENTION: PHP escapes quotation marks!  The actual field name contains a slash before a quote.  Must, therefore, append a slash here, for the input name to be recognized1
// 	propAlias = replaceAll(propAlias, "'", "\\'");		// KEEP replaceAll - DON'T use addslashes, won't work!!!!!!
	propAlias = unescape(propAlias);

	var inputDiv = document.getElementById("propertyValuesInputDiv_" + propAlias);
	var hyperlinkDiv = document.getElementById("make_hl_" + propAlias);
	var multipleDiv = document.getElementById("make_mult_" + propAlias);

	// May 11, 2010
	var otherDiv = document.getElementById("allow_other_" + propAlias);

	var noHL = document.getElementById("no_hl_" + propAlias);
	var noMult = document.getElementById("no_mult_" + propAlias);

// 	var propsList = document.getElementById("propertyValuesInputList_" + propAlias);

// alert(inputDiv);
// alert("input_format_radio_list_" + propAlias);

	var inputTypeRadioList = document.getElementById("input_format_radio_list_" + propAlias);
	var inputTypeRadioText = document.getElementById("input_format_radio_text_" + propAlias);

	if (inputTypeRadioList.checked == true)
	{
		inputDiv.style.display = "inline";

// 		if (hyperlinkDiv && (hyperlinkDiv.style.display == "inline"))

		if (hyperlinkDiv)
			hyperlinkDiv.style.display = "none";

// 		if (multipleDiv && (multipleDiv.style.display == "none"))

		if (multipleDiv)
		{
			if (!noMult)
				multipleDiv.style.display = "inline";
			else
				multipleDiv.style.display = "none";
		}

		// May 11, 2010
		if (otherDiv)
			otherDiv.style.display = "inline";
	}
	else
	{
		inputDiv.style.display = "none";

		if (hyperlinkDiv)
		{
			if (!noHL)
				hyperlinkDiv.style.display = "inline";
			else
				hyperlinkDiv.style.display = "none";
		}

		if (multipleDiv)
			multipleDiv.style.display = "none";

		if (otherDiv)
			otherDiv.style.display = "none";
	}
}


// Aug. 4/09
function clearAllPropertyValues()
{
	var selectLists = document.getElementsByTagName("SELECT");

	for (i = 0; i < selectLists.length; i++)
	{
		tmpInput = selectLists[i];

		if (tmpInput.style.display != "none")
		{
			clearAllElements(tmpInput.id);
		}
	}
}


function verifyCellLineCreation()
{
	return verifyBlankTextInput('CellLine_Name_prop', "Please provide a Name for the new Cell Line") && verifyDropdown('CellLine_Status_prop', "Please select a Status value from the dropdown list") && verifyDropdown('4_Project ID_prop', "Please select a Project ID from the dropdown list");
}


// Dec. 22/09: can I use a one-function-fits-all approach instead of 20 functions w/ different names that do the same thing??
function verifyDropdown(listID, errorMsg)
{
	var ddList = document.getElementById(listID);

	if (ddList.selectedIndex == 0)
	{
		alert(errorMsg);
		ddList.focus();
		return false;
	}
	else if ((ddList[ddList.selectedIndex].value == "") || (ddList[ddList.selectedIndex].value.toLowerCase() == 'default'))
	{
		alert(errorMsg);
		ddList.focus();
		return false;
	}
	else
	{
		return true;
	}
}


function verifyBlankTextInput(textID, errorMsg)
{
	var textField = document.getElementById(textID);

	if (!textField.value)
	{
		alert(errorMsg);
		textField.focus();
		return false;
	}
	else if (textField.value.length == 0)
	{
		alert(errorMsg);
		textField.focus();
		return false;
	}
	else
	{
		return true;
	}
}


function selectAllPropertyValues(includeDisabled)
{
// 	var newPropNames = document.getElementsByName("newPropName");
	var selectLists = document.getElementsByTagName("SELECT");

// alert(selectLists.length);

// // debug
// for (t=0; t < selectLists.length; t++)
// {
// 	alert(selectLists[t].name);
// 	alert(selectLists[t].id);
// }

	var i;

	for (i = 0; i < selectLists.length; i++)
	{
		tmpInput = selectLists[i];

	if (tmpInput)
	{
		if (tmpInput.style.display != "none")
		{
// 		tmpPropAlias = newPropNames[i].value;
// 		tmpListID = "propertyValuesInputList_" + tmpPropAlias;
// 		tmpList = document.getElementById(tmpListID);

// 		alert(tmpInput.name);

		// April 19, 2010: think this is also for select-multiples only
		if (tmpInput.multiple)
		{
			for (ind=0; ind < tmpInput.options.length; ind++)
			{
				tmpInput.options[ind].selected = true;
			}
		}
// 		if (tmpList && (tmpList.style.display != "none"))
// 			selectAllElements(tmpListID);
// 			selectAllElements(tmpInput.id, includeDisabled);
		}
	}
	}
}

function showTooltip(ttID)
{
	document.getElementById(ttID).style.display='block';
}

function hideTooltip(ttID)
{
	document.getElementById(ttID).style.display='none';
}

// Sept. 9, 2010: moved here from searchFunctions.php
function checkOutputType()
{
	searchBoxElem = document.getElementById("search_by");
	showSearchElem = document.getElementById("show_search");
	showProteinElem = document.getElementById("show_protein");
	showDNAElem = document.getElementById("show_dna");
	searchTermElem = document.getElementById("search_term_id");

	var myindex  = searchBoxElem.selectedIndex
	var selValue = searchBoxElem.options[myindex].value

	if (selValue == "protein sequence")
	{
		showProteinElem.style.display="inline";
		showSearchElem.style.display="none";
		showDNAElem.style.display="none";
	}
	else if (selValue == "sequence")
	{
		showDNAElem.style.display="inline";
		showSearchElem.style.display="none";
		showProteinElem.style.display="none";
	}
	else
	{
		showSearchElem.style.display="inline";
		showProteinElem.style.display="none";
		showDNAElem.style.display="none";
	}

	searchTermElem.focus();
}


// May 27, 2011: Enable selection of entire rows/columns on a plate (written to address Nature Methods reviewers' comments)
function selectPlateColumn(plateID, colNum)
{
	var allInput = document.getElementsByTagName("INPUT");
	var	allCBs = Array();

	for (i=0; i < allInput.length; i++)
	{
		tmpInput = allInput[i];		

		if (tmpInput.type == "checkbox")
		{
			allCBs.push(tmpInput);
		}
	}

	var colCBs = Array();

	for (j=0; j < allCBs.length; j++)
	{
		tmpCB = allCBs[j];

		if ((tmpCB.id.indexOf("plate_" + plateID) == 0) && (tmpCB.id.lastIndexOf("_Col_" + colNum) + ("_Col_" + colNum).length == tmpCB.id.length) && (!tmpCB.disabled))
		{
			colCBs.push(tmpCB);
		}
	}

	// Now: It may be that some cells are already checked, and user wants to either check or uncheck by clicking column heading - the key is to do the action on ALL column cells
	var numChecked = 0;
	var numUnchecked = 0;

	for (k=0; k < colCBs.length; k++)
	{
		if (colCBs[k].checked)
			numChecked++;
		else
			numUnchecked++;
	}

	// Deselect all IFF all are checked; select all otherwise
	var uncheckAll = false;
	var checkAll = false;

	if (numChecked == colCBs.length)
	{
		// all cells are checked, uncheck
		uncheckAll = true;

		for (k=0; k < colCBs.length; k++)
		{
			colCBs[k].checked = false;
					
			// Now: For empty wells, parent element is TD; BUT for occupied ones the parent is DIV
			parentElement = colCBs[k].parentNode;

			if (parentElement.tagName == "TD")
			{
				tdParent = parentElement;

				if ((tdParent.id.indexOf("well_plate_" + plateID) == 0) && tdParent.id.lastIndexOf("_Col_" + colNum) + ("_Col_" + colNum).length == tdParent.id.length)
					tdParent.style.backgroundColor = "white";
			}
			else if (parentElement.tagName == "DIV")
			{
				// go one more level up
				divParent = parentElement;
				tdParent = divParent.parentNode;
	
				if ((tdParent.id.indexOf("well_plate_" + plateID) == 0) && tdParent.id.lastIndexOf("_Col_" + colNum) + ("_Col_" + colNum).length == tdParent.id.length)
					tdParent.style.backgroundColor = "white";
			}
		}
	}
	else
	{
		// some are unchecked; therefore, check entire column
		checkAll = true;

		for (k=0; k < colCBs.length; k++)
		{
			colCBs[k].checked = true;

			// colour well			
			parentElement = colCBs[k].parentNode;

			if (parentElement.tagName == "TD")
			{
				tdParent = parentElement;

				if ((tdParent.id.indexOf("well_plate_" + plateID) == 0) && tdParent.id.lastIndexOf("_Col_" + colNum) + ("_Col_" + colNum).length == tdParent.id.length)
					tdParent.style.backgroundColor = "yellow";
			}
			else if (parentElement.tagName == "DIV")
			{
				// go one more level up
				divParent = parentElement;
				tdParent = divParent.parentNode;
			
				if ((tdParent.id.indexOf("well_plate_" + plateID) == 0) && tdParent.id.lastIndexOf("_Col_" + colNum) + ("_Col_" + colNum).length == tdParent.id.length)
					tdParent.style.backgroundColor = "yellow";
			}
		}
	}
/*
	// Colour the entire well
	var allTDs = document.getElementsByTagName("TD");

	for (n = 0; n < allTDs.length; n++)
	{
		tmpTD = allTDs[n];

		if ((tmpTD.id.indexOf("well_plate_" + plateID) == 0) && tmpTD.id.lastIndexOf("_Col_" + colNum) + ("_Col_" + colNum).length == tmpTD.id.length)
		{
			if (checkAll)
			{
				tmpTD.style.backgroundColor = "yellow";
			}

			else 
				tmpTD.style.backgroundColor = "#FFFFFF";
		}
	}
	*/
}

function selectPlateRow(plateID, rowNum)
{
	//alert(rowNum);

	var allInput = document.getElementsByTagName("INPUT");
	var	allCBs = Array();
	
	for (i=0; i < allInput.length; i++)
	{
		tmpInput = allInput[i];		

		if (tmpInput.type == "checkbox")
		{
			allCBs.push(tmpInput);
		}
	}

	var rowCBs = Array();

	for (j=0; j < allCBs.length; j++)
	{
		tmpCB = allCBs[j];
		
	//	alert(tmpCB.id);

		if ((tmpCB.id.indexOf("plate_" + plateID) == 0) && (tmpCB.id.indexOf("_Row_" + rowNum + "_Col_") > 0) && (!tmpCB.disabled))
		{
			rowCBs.push(tmpCB);
		}
	}

	// Now: It may be that some cells are already checked, and user wants to either check or uncheck by clicking row heading - the key is to do the action on ALL row cells
	var numChecked = 0;
	var numUnchecked = 0;

	for (k=0; k < rowCBs.length; k++)
	{
		if (rowCBs[k].checked)
		{
			numChecked++;
		}
		else
			numUnchecked++;
	}
	
	// Deselect all IFF all are checked; select all otherwise
	var uncheckAll = false;
	var checkAll = false;

	if (numChecked == rowCBs.length)
	{
		// all cells are checked, uncheck
		uncheckAll = true;

		for (k=0; k < rowCBs.length; k++)
		{
			rowCBs[k].checked = false;
						
			// Now: For empty wells, parent element is TD; BUT for occupied ones the parent is DIV
			parentElement = rowCBs[k].parentNode;

			if (parentElement.tagName == "TD")
			{
				tdParent = parentElement;

				if ((tdParent.id.indexOf("well_plate_" + plateID) == 0) && (tdParent.id.indexOf("_Row_" + rowNum) == ("well_plate_" + plateID).length))
					tdParent.style.backgroundColor = "white";
			}
			else if (parentElement.tagName == "DIV")
			{
				// go one more level up
				divParent = parentElement;
				tdParent = divParent.parentNode;
	
				if ((tdParent.id.indexOf("well_plate_" + plateID) == 0) && (tdParent.id.indexOf("_Row_" + rowNum) == ("well_plate_" + plateID).length))
					tdParent.style.backgroundColor = "white";			
			}
		}
	}
	else
	{
		// some are unchecked; therefore, check entire row
		checkAll = true;

		for (k=0; k < rowCBs.length; k++)
		{
			rowCBs[k].checked = true;
			
			// Now: For empty wells, parent element is TD; BUT for occupied ones the parent is DIV
			parentElement = rowCBs[k].parentNode;

			if (parentElement.tagName == "TD")
			{
				tdParent = parentElement;

				if ((tdParent.id.indexOf("well_plate_" + plateID) == 0) && (tdParent.id.indexOf("_Row_" + rowNum) == ("well_plate_" + plateID).length))
					tdParent.style.backgroundColor = "yellow";				
			}
			else if (parentElement.tagName == "DIV")
			{
				// go one more level up
				divParent = parentElement;
				tdParent = divParent.parentNode;
				
				if ((tdParent.id.indexOf("well_plate_" + plateID) == 0) && (tdParent.id.indexOf("_Row_" + rowNum) == ("well_plate_" + plateID).length))
					tdParent.style.backgroundColor = "yellow";
			}
		}
	}

/*
	// Colour the entire well
	var allTDs = document.getElementsByTagName("TD");

	for (n = 0; n < allTDs.length; n++)
	{
		tmpTD = allTDs[n];

		if ((tmpTD.id.indexOf("well_plate_" + plateID) == 0) && (tmpTD.id.indexOf("_Row_" + rowNum) == ("well_plate_" + plateID).length))
		{
			if (checkAll)
				tmpTD.style.backgroundColor = "yellow";

			else 
			{
				tmpTD.style.backgroundColor = "#FFFFFF";
			}
		}
	}
	*/
}

/*
function selectColumn(pname)
{
	aCol = document.getElementsByName(pname + "_td[]");

	for (a=0; a < aCol.length; a++)
	{
		aTD = aCol[a];
		aTD.style.backgroundColor = "yellow";
	}
}*/


// June 3, 2011: VERY IMPORTANT - batch update an attribute column value for all selected wells
function popupWellAttrUpdateForm(propName)
{
	if (propName == 'OpenFreezer ID')
		var tdnodes = document.getElementsByName("limsID_td[]");

	else if (propName == 'Isolate Number')
		var tdnodes = document.getElementsByName("isoNum_td[]");
	
	else
		var tdnodes = document.getElementsByName(propName + "_td[]");

	if (tdnodes && (tdnodes.length > 0))
	{
		var attrVal = prompt(propName + ": ", "");

		for (i = 0; i < tdnodes.length; i++)
		{
			tdnode = tdnodes[i];

			txtfield = tdnode.children[0];

			// In FF4, pressing 'Cancel' returns null
			if (attrVal != null)
			{
				txtfield.value = attrVal;
			}
		}	
		
	}
}


function updateWellAttribute(propName, contID)
{
	//alert(propName);
	//alert(propID);
	//alert(contID);

	if (propName != "default")
	{
		var attrVal = prompt(propName + ": ", "");
		
		if (attrVal != null)
		{
			// let's try AJAX
			var allWellsCB = document.getElementsByName("wells_checkbox[]");
			var checkedWells = new Array();

			for (i=0; i < allWellsCB.length; i++)
			{		
				if (allWellsCB[i].checked)
				{
					checkedWells.push(allWellsCB[i].value);
				}
			}

			wells = checkedWells.join(",");

			url = cgiPath + "location_request_handler.py";

			xmlhttp = createXML();
			xmlhttp.open("POST", url, false);
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');

			xmlhttp.send('update_attribute=1&contID=' + contID + "&propName=" + propName + "&propVal=" + attrVal + "&wells=" + wells);

			xmlhttp.onreadystatechange = printUpdatedPlate(xmlhttp, contID);			
		}
	}
}

function printUpdatedPlate(xmlhttp, contID)
{
	//alert(xmlhttp.readyState);

	if (xmlhttp.readyState == 4)
	{
		if (xmlhttp.status == 200)
		{
 			//prompt("", xmlhttp.responseText);
			document.getElementById("changeAttributeSelect").selectedIndex = 0;
			window.location.href = hostName + "Location.php?View=2&Mod=" + contID;
		}
	}
}


function checkUncheckAllIsolates()
{
	var allInput = document.getElementsByTagName("INPUT");
	var	allCBs = Array();
	var numChecked = 0;
	
	for (i=0; i < allInput.length; i++)
	{
		tmpInput = allInput[i];		

		if (tmpInput.type == "checkbox")
		{
			// on modification the name is well_mod_beingUsed<key>_checkbox; on creation it is simply well_beingUsed_checkbox
			if ((tmpInput.name.indexOf("beingUsed") > 0) && (tmpInput.name.lastIndexOf("_checkbox") + "_checkbox".length == tmpInput.name.length))
			{
				allCBs.push(tmpInput);

				if (tmpInput.checked)
				{
					numChecked++;
				}
			}
		}
	}

	if (numChecked == allCBs.length)
	{
		// all checked, uncheck
		for (i=0; i < allCBs.length; i++)
		{
			allCBs[i].checked = false;
		}
	}
	else
	{
		// some are not checked, check all
		for (i=0; i < allCBs.length; i++)
		{
			allCBs[i].checked = true;
		}	
	}
}


function checkUncheckAllFlags()
{
	var allInput = document.getElementsByTagName("INPUT");
	var	allCBs = Array();
	var numChecked = 0;
	
	for (i=0; i < allInput.length; i++)
	{
		tmpInput = allInput[i];		

		if (tmpInput.type == "checkbox")
		{
			// on modification the name is well_mod_flag<key>_checkbox; on creation it is simply well_flag_checkbox
			if ((tmpInput.name.indexOf("flag") > 0) && (tmpInput.name.lastIndexOf("_checkbox") + "_checkbox".length == tmpInput.name.length))
			{
				allCBs.push(tmpInput);

				if (tmpInput.checked)
				{
					numChecked++;
				}
			}
		}
	}

	if (numChecked == allCBs.length)
	{
		// all checked, uncheck
		for (i=0; i < allCBs.length; i++)
		{
			allCBs[i].checked = false;
		}
	}
	else
	{
		// some are not checked, check all
		for (i=0; i < allCBs.length; i++)
		{
			allCBs[i].checked = true;
		}	
	}
}


function showSafetySearchForm()
{
	var chemSearchList = document.getElementById("search_criteria_list");
	var selInd = chemSearchList.selectedIndex;
	var chemSearchCriterion = chemSearchList[selInd];
	var chem_search_caption = document.getElementById("chem_search_caption");

	if (chemSearchCriterion.text == "Safety")
	{
		document.getElementById("safety_search").style.display = "inline";
		document.getElementById("chemical_locations").style.display = "none";
		document.getElementById("chem_search_keyword").style.display = "none";
		
		chem_search_caption.innerHTML = "Safety:";
	}
	else
	{
		document.getElementById("safety_search").style.display = "none";
	}
}
