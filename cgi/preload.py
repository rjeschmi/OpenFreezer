#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()

import MySQLdb
import sys
import os
import string
import re

from database_conn import DatabaseConn	# april 20/07 
from exception import *
import utils

from mapper import ReagentPropertyMapper, ReagentAssociationMapper, ReagentTypeMapper

# updated April 3/09 - added ReagentTypeHandler
from general_handler import *
from reagent_handler import ReagentHandler, InsertHandler
from comment_handler import CommentHandler
#from system_set_handler import SystemSetHandler
from sequence_handler import DNAHandler, ProteinHandler

from sequence_feature import SequenceFeature

# User and Project info
from user_handler import UserHandler
from project_database_handler import ProjectDatabaseHandler
from session import Session

import Bio
from Bio.Seq import Seq
#from Bio import Enzyme
from Bio.Restriction import *

# make global??
dbConn = DatabaseConn()
db = dbConn.databaseConnect()
hostname = dbConn.getHostname()
cursor = db.cursor()

# Handlers and Mappers
aHandler = AssociationHandler(db, cursor)
rHandler = ReagentHandler(db, cursor)
iHandler = InsertHandler(db, cursor)
raHandler = ReagentAssociationHandler(db, cursor)
rtPropHandler = ReagentTypePropertyHandler(db, cursor)	# Aug. 31/09
rtHandler = ReagentTypeHandler(db, cursor)		# April 3/09
sHandler = DNAHandler(db, cursor)
pHandler = ReagentPropertyHandler(db, cursor)
packetHandler = ProjectDatabaseHandler(db, cursor)
uHandler = UserHandler(db, cursor)
sysHandler = SystemSetHandler(db, cursor)		# Dec. 1/08

propMapper = ReagentPropertyMapper(db, cursor)
aMapper = ReagentAssociationMapper(db, cursor)
rMapper = ReagentTypeMapper(db, cursor)

# Various maps
reagentType_Name_ID_Map =  rMapper.mapTypeNameID()
reagentType_ID_Name_Map = rMapper.mapTypeIDName()

assoc_Type_Name_Map = aMapper.mapAssocTypeNameID()
assoc_Name_Alias_Map = aMapper.mapAssocNameAlias()	# april 30/08

# removed March 31, 2011: notice the variable assoc_Type_Name_Map two lines above - assigning the same function value to different variables???
#assoc_Name_Type_Map = aMapper.mapAssocTypeNameID()	# june 2/08

prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)
prop_ID_Name_Map = propMapper.mapPropIDName()		# Added March 13/08 - (prop id, prop name)
prop_Name_Alias_Map = propMapper.mapPropNameAlias()	# (propName, propAlias)
prop_Alias_Name_Map = propMapper.mapPropAliasName()	# March 18/08 - (propAlias, propName)
prop_Alias_ID_Map = propMapper.mapPropAliasID()		# (propAlias, propID) - e.g. ('insert_type', '48')

prop_Alias_Descr_Map = propMapper.mapPropAliasDescription()

prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()
prop_Category_ID_Name_Map = propMapper.mapPropCategoryNameID()

prop_Category_Alias_Name_Map = propMapper.mapPropCategoryAliasName()

# Get enzymes list for mapping sequence features
enzDict = utils.join(sHandler.sitesDict, sHandler.gatewayDict)
enzDict = utils.join(enzDict, sHandler.recombDict)		# add LoxP
enzDict['None'] = ""						# add 'None'

###############################################################################################################################################

# May 13/08: Action reused multiple times - make a separate function
def getCurrentUserProjects(currUser):
	
	#print "Content-type:text/html"
	#print
	
	# get projects user has AT LEAST Read access to (i.e. if he is explicitly declared a Writer on a project but not declared a Reader, that's allowed)
	currReadProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Reader')
	currWriteProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Writer')
	publicProj = packetHandler.findAllProjects(isPrivate="FALSE")
	
	# list of Packet OBJECTS
	currUserWriteProjects = utils.unique(currReadProj + currWriteProj + publicProj)
	
	uPackets = []
	
	for p in currUserWriteProjects:
		uPackets.append(p.getNumber())

	return uPackets
	
	
def previewCellLineProperties(rID, rType, subtype, parents, currUser, db, cursor):
	#print "Content-type:text/html"
	#print
	
	if subtype == "stable_cell_line":
		parentVectorID = parents[0]
		parentCellLine = parents[1]
		
		# Get project IDs of parents
		#packetPropID = pHandler.findPropID("packet id")
		packetPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["packet id"], prop_Category_Name_ID_Map["General Properties"])
		
		# May 13/08
		uPackets = getCurrentUserProjects(currUser)
		
		# parent vector
		try:
			pv_db_id = rHandler.convertReagentToDatabaseID(parentVectorID)
			pvProjectID = int(rHandler.findSimplePropertyValue(pv_db_id, packetPropID))	# need to cast
			
			if currUser.getCategory() != 'Admin' and pvProjectID not in uPackets:
			
				i = PVProjectAccessException("You do not have read access to this project")
		
				clpvAssocProp = aHandler.findAssocPropID("cell line parent vector id")

				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clpvAssocProp`)
				
			else:
				# parent vector ok, check cell line
				try:
					cl_db_id = rHandler.convertReagentToDatabaseID(parentCellLine)
					clProjectID = rHandler.findSimplePropertyValue(cl_db_id, packetPropID)
					
					if isinstance(clProjectID, (int)) and int(clProjectID) > 0:
					#if clProjectID > 0:
						clProjectID = int(clProjectID)
						
						if currUser.getCategory() != 'Admin' and clProjectID > 0 and clProjectID not in uPackets:
							i = CLProjectAccessException("You do not have read access to this project")
					
							clAssocProp = aHandler.findAssocPropID("parent cell line id")
			
							utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clAssocProp`)
						
						else:
							# user may view project, proceed
							utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&CL=" + parentCellLine)

					# disable access if project ID not set - will allow us to catch errors
					#elif not isinstance(clProjectID, (int)) and currUser.getCategory() != 'Admin':
					elif (clProjectID == None or clProjectID <= 0) and currUser.getCategory() != 'Admin':
						i = CLProjectAccessException("You do not have read access to this project")
				
						clAssocProp = aHandler.findAssocPropID("parent cell line id")
		
						utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clAssocProp`)
						
					else:
						# everything ok, proceed
						utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&CL=" + parentCellLine)
		
				except ReagentDoesNotExistException:
					i = ReagentDoesNotExistException("Parent Cell Line ID not found in database")
					
					# get "parent cell line" association type
					clAssocProp = aHandler.findAssocPropID("parent cell line id")
					
					# "AP" stands for "Association Property"
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clAssocProp`)

		except ReagentDoesNotExistException:
			i = ReagentDoesNotExistException("Parent Vector ID not found in database")
			
			# get "parent vector" association type
			clpvAssocProp = aHandler.findAssocPropID("cell line parent vector id")
			
			# "AP" stands for "Association Property"
			utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clpvAssocProp`)


# Specifically for Vectors: since this code is reused many times, make it a distinct function
# Preload Vector properties based on its parent values, including newly generated sequence 
# Written April 19, 2007; last modified June 1/08 by Marina Olhovsky
# Updated Oct. 30/08: Added 'reverse_insert' parameter, default False to reverse complement the Insert sequence if necessary (most often if 5' occurs after 3' on PV)
#
# Update Jan. 21/09: Added 'reverse_complement' argument; Note the difference between 'reverse_insert' and 'reverse_complement': 'reverse_insert' is set to 'true' ***in response to an exception being thrown***, likely a "5' after 3'" exception, when returning from an error page.  'reverse_complement', on the other hand, is ***voluntary*** user input, e.g. in case of ** directional cloning ** - ** program does not report an error **, user chooses to put the Insert into vector in reverse orientation in cases like two EcoRI sites (V2323+I92062).  CANNOT use reverse_insert where actually meaning reverse_complement, b/c the Insert would be reversed TWICE - once in this function, and then again, in the sequence construction function! - and would end up with the original sequence, whereas, in fact, needed the reverse complement.
def previewVectorSequence(rID, reagent, parents, currUser, db, cursor, sites=[], reverse_insert=False, reverse_complement=False):
	
	global aHandler
	#global pHandler

	#print "Content-type:text/html"
	#print
	#print "Reverse insert solution " + `reverse_insert`
	#print "Reverse complement request " + `reverse_complement`
	#print `sites`
	
	reverse = False		# Jan. 22/09
	
	# May 13/08
	uPackets = getCurrentUserProjects(currUser)
	#print `uPackets`
	
	# April 14/08: Create a new Reagent instance
	rType = reagent.getType()
	subtype = reagent.getSubtype()

	# site property IDs - update July 2/09
	fpcs_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	tpcs_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
	#fpcs_prop_id = prop_Name_ID_Map["5' cloning site"]	# removed July 2/09
	#tpcs_prop_id = prop_Name_ID_Map["3' cloning site"]	# removed July 2/09

	if subtype == 'nonrecomb' or subtype =='gateway_entry':
		
		pv_db_id = int(parents["vector parent id"])
		insert_db_id = int(parents["insert id"])
		
		# still need the human-readable values for error handling
		parentVectorID = rHandler.convertDatabaseToReagentID(pv_db_id)
		insertID = rHandler.convertDatabaseToReagentID(insert_db_id)
	
		# April 14/08: Save association - May 5/08: IFF does not exist yet!!!!!!!!!!!!
		assocID = raHandler.findReagentAssociationID(rID)
		
		if assocID <= 0:
			aTypeID = assoc_Type_Name_Map["INSERT"]
			assocID = rHandler.createReagentAssociation(rID, aTypeID)
		
		reagent.setCloningMethod(assocID)

		pvAssocProp = aHandler.findAssocPropID("vector parent id")
		insertAssocProp = aHandler.findAssocPropID("insert id")
		
		# August 21/07: Restrict parent selection by project - if a parent belongs to a project the creator does not have read access to, disallow creation
		#packetPropID = pHandler.findPropID("packet id")
		packetPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["packet id"], prop_Category_Name_ID_Map["General Properties"])
		pvProjectID = int(rHandler.findSimplePropertyValue(pv_db_id, packetPropID))	# need to cast
		
		if currUser.getCategory() != 'Admin' and pvProjectID not in uPackets:
			
			i = PVProjectAccessException("You do not have read access to this project")
	
			# "AP" stands for "Association Property"
			utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&AP=" + `pvAssocProp`)
		
		# Insert
		# August 21/07: Restrict parent selection by project - if a parent belongs to a project the creator does not have read access to, disallow creation
		#packetPropID = pHandler.findPropID("packet id")
		
		try:
			insertProjectID = int(rHandler.findSimplePropertyValue(insert_db_id, packetPropID))	# need to cast
		except (TypeError, ValueError, IndexError):
			insertProjectID = 0
			
		if currUser.getCategory() != 'Admin' and insertProjectID not in uPackets:
			i = InsertProjectAccessException("You do not have read access to this project")
	
			# "AP" stands for "Association Property"
			utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&AP=" + `insertAssocProp`)
			
		# if projects OK save parents - DELETE ALL PREVIOUS INFORMATION!!!!!!!!!!!!
		rHandler.deleteReagentAssociationProp(rID, pvAssocProp)
		rHandler.addAssociationValue(rID, pvAssocProp, pv_db_id, assocID)
		
		rHandler.deleteReagentAssociationProp(rID, insertAssocProp)
		rHandler.addAssociationValue(rID, insertAssocProp, insert_db_id, assocID)
		
		# AND, last but not least, the reason this is all being done - Construct a sequence for the new vector from the sequences of its parents
		pvSeqKey = rHandler.findDNASequenceKey(pv_db_id)
		pvSeq = sHandler.findSequenceByID(pvSeqKey)			# May 27/08
		
		insertSeqKey = rHandler.findDNASequenceKey(insert_db_id)
		insertSeq = sHandler.findSequenceByID(insertSeqKey)		# May 14/08
		
		# Jan. 21/09
		if reverse_complement:
			#print "REVERSING INSERT!!!"
			insertSeq = sHandler.reverse_complement(insertSeq)
		
		#print insertSeq
		
	# May 28/08
	elif subtype == 'recomb' or subtype == 'gateway_expression':
		
		# Updated May 8/08
		pv_db_id = int(parents["vector parent id"])
		ipv_db_id = int(parents["parent insert vector"])
		
		# human-readable values for error handling
		parentVector = rHandler.convertDatabaseToReagentID(pv_db_id)
		insertParentVector = rHandler.convertDatabaseToReagentID(ipv_db_id)
		
		parentVectorID = parentVector
		insertParentVectorID = insertParentVector
		
		# Associations
		assocID = raHandler.findReagentAssociationID(rID)
		
		if assocID <= 0:
			aTypeID = assoc_Type_Name_Map["LOXP"]
			assocID = rHandler.createReagentAssociation(rID, aTypeID)
		
		reagent.setCloningMethod(assocID)

		pvAssocProp = aHandler.findAssocPropID("vector parent id")
		ipvAssocProp = aHandler.findAssocPropID("parent insert vector")
		
		# get internal db IDs
		pv_db_id = rHandler.convertReagentToDatabaseID(parentVector)
		ipv_db_id = rHandler.convertReagentToDatabaseID(insertParentVector)

		# Restrict by packet
		#packetPropID = pHandler.findPropID("packet id")
		packetPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["packet id"], prop_Category_Name_ID_Map["General Properties"])
		
		# June 10/08: Add error catching for empty project IDs
		try:
			pvProjectID = int(rHandler.findSimplePropertyValue(pv_db_id, packetPropID))	# need to cast
		except TypeError:
			i = PVProjectAccessException("Invalid Parent Vector project ID")
			
			# get "parent vector" association type
			aHandler = AssociationHandler(db, cursor)
			pvAssocProp = aHandler.findAssocPropID("vector parent id")
			
			# "AP" stands for "Association Property"
			utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `pvAssocProp`)
		
		try:
			ipvProjectID = int(rHandler.findSimplePropertyValue(ipv_db_id, packetPropID))	# need to cast
			
		except TypeError:
			i = PVProjectAccessException("Invalid Insert Parent Vector project ID")
		
			# get "parent vector" association type
			aHandler = AssociationHandler(db, cursor)
			ipvAssocProp = aHandler.findAssocPropID("parent insert vector")
			
			# "AP" stands for "Association Property"
			utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `ipvAssocProp`)
			
			
		if currUser.getCategory() != 'Admin' and pvProjectID not in uPackets:
			i = PVProjectAccessException("You do not have read access to this project")
	
			# get "parent vector" association type
			aHandler = AssociationHandler(db, cursor)
			pvAssocProp = aHandler.findAssocPropID("vector parent id")
			
			# "AP" stands for "Association Property"
			utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `pvAssocProp`)
		
		if currUser.getCategory() != 'Admin' and ipvProjectID not in uPackets:
			i = IPVProjectAccessException("You do not have read access to this project")
	
			# get "parent vector" association type
			aHandler = AssociationHandler(db, cursor)
			ipvAssocProp = aHandler.findAssocPropID("parent insert vector")
			
			# "AP" stands for "Association Property"
			utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `ipvAssocProp`)
		
		# if projects OK save parents - DELETE ALL PREVIOUS INFORMATION!!!!!!!!!!!!
		rHandler.deleteReagentAssociationProp(rID, pvAssocProp)
		rHandler.addAssociationValue(rID, pvAssocProp, pv_db_id, assocID)
		
		rHandler.deleteReagentAssociationProp(rID, ipvAssocProp)
		rHandler.addAssociationValue(rID, ipvAssocProp, ipv_db_id, assocID)	
		
		# Get the Insert that belongs to the donor vector
		ipvInsertAssocID = raHandler.findReagentAssociationID(ipv_db_id)
		insertAssocPropID = aHandler.findAssocPropID("insert id")
		insert_db_id = aHandler.findAssocPropValue(ipvInsertAssocID, insertAssocPropID)
	
		######################################################################################################################
		# Sept. 11/08: This may occur if the IPV is of the wrong type - e.g. if user tries to input a GTW Expression Vector in place of a GTW Entry or a Creator Expression Vector instead of a Creator Donor.  Talked to Karen today and decided to disallow this.
		
		# June 3, 2010: Talked to Karen, Sunqu/Oliver, David Lye and Anthony Chiu about this extensively.  Final decision: It is NOT mandatory to have an Insert here.  Sunqu was using 2 recombination vectors to generate a Creator Expression Vector via site-directed mutagenesis; the IPV did not have an Insert directly associated with it.  David and Anthony used 2 Novel vectors to create a Gateway Expression vector.  Karen tested creating GW Expression vector by swapping the GW Entry and Parent Destination (i.e. making the PV an IPV and v.v.) - and it worked as long as the sites were correct.  So, bottom line: If Insert is not found during LoxP creation, it's **OK** - if sequence is generated correctly then there would just be no hyperlink to the cDNA Insert ID.  For GW expression adding a new validation strategy - see below.
		
		######################################################################################################################
		
		# removed the 3 lines below on June 3, 2010 (don't throw exception and don't redirect to error page!)
		#if insert_db_id < 0:
			#i = ReagentTypeException("Wrong parent type")
			#utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `pvAssocProp`)
			
			#############################################
			# VERIFIED WITH KAREN AND REMOVED SEPT. 11/08
			#############################################
			
			## June 1/08: Even though this shouldn't happen, the IPV provided may itself be a recombination vector; thus, an Insert is not explicitly stored for it, in which case need to go back an additional step and present the Insert from the IPV of this reagent (equivalent to cDNA Insert on detailed view)
			#parent_ipv = aHandler.findAssocPropValue(ipvInsertAssocID, ipvAssocProp)
			
			#if parent_ipv > 0:
				#parent_ipv_assoc_id = raHandler.findReagentAssociationID(parent_ipv)

				#if parent_ipv_assoc_id > 0:
					#ipv_insert_id = aHandler.findAssocPropValue(parent_ipv_assoc_id, insertAssocPropID)

					#if ipv_insert_id > 0:
						#insert_db_id = ipv_insert_id
						#insertID = rHandler.convertDatabaseToReagentID(ipv_insert_id)
					#else:
						#utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=14&AP=" + `pvAssocProp`)
				#else:
					#utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=14&AP=" + `pvAssocProp`)
			
		#else:	# removed June 3, 2010
			#insertID = rHandler.convertDatabaseToReagentID(insert_db_id)	# removed June 3, 2010
			
		# June 3, 2010: Instead:
		if insert_db_id > 0:
			insertID = rHandler.convertDatabaseToReagentID(insert_db_id)
			
			# moved this up here - see comment below
			insertSeqKey = rHandler.findDNASequenceKey(insert_db_id)
			insertSeq = sHandler.findSequenceByID(insertSeqKey)
			
		# AND, last but not least, the reason this is all being done - Construct a sequence for the new vector from the sequences of its parents
		pvSeqKey = rHandler.findDNASequenceKey(pv_db_id)
		ipvSeqKey = rHandler.findDNASequenceKey(ipv_db_id)
		
		# moving up June 3, 2010
		#insertSeqKey = rHandler.findDNASequenceKey(insert_db_id)	# rmvd June 3, 2010
		#insertSeq = sHandler.findSequenceByID(insertSeqKey)		# rmvd June 3, 2010
		
	# May 28/08: Common to all
	# Fetch the Insert's cloning sites
	insertCloningSites = []
	
	# Moved up here Feb. 11/09
	fp_insert_cs = rHandler.findSimplePropertyValue(insert_db_id, fpcs_prop_id)
	tp_insert_cs = rHandler.findSimplePropertyValue(insert_db_id, tpcs_prop_id)

	if len(sites) > 0 and sites[0] and sites[1]:
		insertCloningSites = sites
		#print `insertCloningSites`
	else:
		# depends on vector type
		# May 29/08: Need to add another round of validation to make sure sites match subtype (i.e. using an Insert with gateway sites to create a non-recomb Vector) - BUT it's low-priority; errors will be caught, sequence will not be generated, can worry about extra error output pages later
		if subtype == 'nonrecomb':
			
			# Moved up Feb. 11/09
			## get from Insert
			#fp_insert_cs = rHandler.findSimplePropertyValue(insert_db_id, fpcs_prop_id)
			#tp_insert_cs = rHandler.findSimplePropertyValue(insert_db_id, tpcs_prop_id)
		
			if fp_insert_cs:
				insertCloningSites.append(fp_insert_cs)
			else:
				insertCloningSites.append("")
				
			if  tp_insert_cs:
				insertCloningSites.append(tp_insert_cs)
			else:
				insertCloningSites.append("")
				
		elif subtype == 'recomb':
			insertCloningSites.append("LoxP")
			insertCloningSites.append("LoxP")
			
		elif subtype == 'gateway_entry':
			insertCloningSites.append("attB1")
			insertCloningSites.append("attB2")
			
		elif subtype == 'gateway_expression':
			insertCloningSites.append("attL1")
			insertCloningSites.append("attL2")
	
	#print `insertCloningSites`
	
	# get linkers if there are any
	insertLinkers = []

	# Update July 2/09
	fpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	tpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
	#fpLinkerPropID = prop_Name_ID_Map["5' linker"]		# july 2/09
	#tpLinkerPropID = prop_Name_ID_Map["3' linker"]		# july 2/09
	
	fp_insert_linker = rHandler.findSimplePropertyValue(insert_db_id, fpLinkerPropID)
	tp_insert_linker = rHandler.findSimplePropertyValue(insert_db_id, tpLinkerPropID)
	
	# sept. 3/07 - needed to cast to string
	fwd_linker = ""

	if fp_insert_linker and len(fp_insert_linker) > 0 and fp_insert_linker != 0 and fp_insert_linker != '0':
		fp_insert_linker = fwd_linker + fp_insert_linker
	else:
		fp_insert_linker = fwd_linker
		
	# April 24/08
	if not tp_insert_linker or len(tp_insert_linker) == 0 or tp_insert_linker == 0 or tp_insert_linker == '0':
		tp_insert_linker = ""
		
	insertLinkers.append(fp_insert_linker)
	insertLinkers.append(tp_insert_linker)
	
	fp_linker_start = rHandler.findReagentFeatureStart(insert_db_id, fpLinkerPropID)
	fp_linker_end = rHandler.findReagentFeatureEnd(insert_db_id, fpLinkerPropID)
	
	tp_linker_start = rHandler.findReagentFeatureStart(insert_db_id, tpLinkerPropID)
	tp_linker_end = rHandler.findReagentFeatureEnd(insert_db_id, tpLinkerPropID)
	
	try:
		# Call different sequence construction methods depending on vector subtype
		if subtype == 'nonrecomb':
			# Updated June 8/08
			# First lookup by string matching; then compare to stored data and report inconsistencies
			
			# Check if sites are empty
			if len(insertCloningSites[0]) == 0 or len(insertCloningSites[1]) == 0:
				# Modified Feb. 12/09
				i = EmptyCloningSitesException("")
				err = i.err_code()
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `err` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])	

			# Nov. 17/08: Added 'if' statements to catch hybrid sites
			if enzDict.has_key(insertCloningSites[0]):			# nov. 17/08
				fpSite = enzDict[insertCloningSites[0]].lower()
				
				fp_start = insertSeq.lower().find(fpSite) + 1
				fp_end = fp_start + len(fpSite)
				
				# Dec. 17/08, Marina: Test tomorrow with degenerate sequences
				orig_fp_start = rHandler.findReagentFeatureStart(insert_db_id, fpcs_prop_id)
				orig_fp_end = rHandler.findReagentFeatureEnd(insert_db_id, fpcs_prop_id)
				
				#print orig_fp_end
			else:
				fpSite = insertCloningSites[0]
				
				fp_start = None
				fp_end = None
				
				orig_fp_start = None
				orig_fp_end = None
				
			if enzDict.has_key(insertCloningSites[1]):			# nov. 17/08
				tpSite = enzDict[insertCloningSites[1]].lower()
				
				tp_start = insertSeq.lower().find(tpSite) + 1
				tp_end = tp_start + len(tpSite) - 1
				
				orig_tp_start = rHandler.findReagentFeatureStart(insert_db_id, tpcs_prop_id)
				orig_tp_end = rHandler.findReagentFeatureEnd(insert_db_id, tpcs_prop_id)

			else:
				tpSite = insertCloningSites[1]
				
				tp_start = None
				tp_end = None
				
				orig_tp_start = None
				orig_tp_end = None
				
			# Feb. 11/09 update: this check is valid IFF sites are not customized!
			# Compare to site positions stored for this Insert
			# Jan. 23/09: Positions WILL change if you reverse the Insert!
			if not reverse_complement and not reverse_insert and fp_insert_cs == fpSite and tp_insert_cs == tpSite:
				if fp_start and fp_end and tp_start and tp_end and orig_fp_start and orig_fp_end and orig_tp_start and orig_tp_end and orig_fp_start != fp_start and orig_fp_end != fp_end and orig_tp_start != tp_start and orig_tp_end != tp_end:
					i = InsertSitePositionException("Insert site positions don't match")
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])

			cdnaStart = iHandler.findCDNAStart(insert_db_id)
			cdnaEnd = iHandler.findCDNAEnd(insert_db_id)

			#if fp_start > 0 and tp_end > 0 and tp_end <= len(insertSeq):		# no, don't use this - will not redirect properly!
			
			# nov. 6/08
			if sHandler.gatewayDict.has_key(insertCloningSites[0]) or sHandler.gatewayDict.has_key(insertCloningSites[1]) or sHandler.recombDict.has_key(insertCloningSites[0]) or sHandler.recombDict.has_key(insertCloningSites[1]):
				# Non-recombination vector attempted with gateway or LoxP Insert - disallow
				i = InsertSitesException("Wrong sites on Insert")
				
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
			
			try:
				#print "HERE " + `reverse_insert`
				#print insertSeq
				
				# Oct. 30/08
				newSeq = sHandler.constructNonRecombSequence(pvSeqKey, insertSeq, insertCloningSites, reverse_insert)
				newSeqID = int(sHandler.matchSequence(newSeq))
	
				if newSeqID <= 0:
					newSeqID = int(sHandler.insertSequence(newSeq))
		
				# Jan. 21/09: Pass 'reverse' parameter to feature mapping function for correct remapping of features, particularly cDNA
				if reverse_complement or reverse_insert:
					reverse = True
				
				# Updated April 28/08: Implement multi-step creation process
				# Updated Nov. 18/08: Added sites to URL to keep track of hybrids for feature mapping
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Step=1&Type=" + rType + "&Sub=" + subtype + "&PV=" + `pv_db_id` + "&I=" + `insert_db_id` + "&Seq=" + `newSeqID` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1] + "&Rev=" + `reverse`)
				
			except CloningSitesNotFoundInInsertException:
				i = CloningSitesNotFoundInInsertException("Sites not found on Insert sequence")
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
				
			except InsertSitesException:
				i = InsertSitesException("Wrong sites on Insert")
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
			
			except InsertSitesNotFoundOnParentSequenceException:
				i = InsertSitesNotFoundOnParentSequenceException("Insert sites not found on parent vector sequence")
				
				if subtype == 'nonrecomb' or subtype == 'gateway_entry':
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
				else:				# added June 2/08
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
				
			except MultipleSiteOccurrenceException:
				i = MultipleSiteOccurrenceException("Site found more than once on parent vector sequence")
				
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
				
			except HybridizationException:
				i = HybridizationException("Sites cannot be hybridized")
				
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
			
			# Dec. 14/09
			except EmptyParentVectorSequenceException:
				i = EmptyParentVectorSequenceException("Empty parent Vector sequence exception")
			
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
				
			except EmptyParentInsertSequenceException:
				i = EmptyParentInsertSequenceException("Empty parent Insert sequence exception")
			
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
				
			except FivePrimeAfterThreePrimeException:
				i = FivePrimeAfterThreePrimeException("5' site occurs after 3' site on parent vector sequence")
				
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1] + "&Rev=" + `reverse_complement`)
			
			# March 10/10
			except InsertFivePrimeAfterThreePrimeException:
				i = InsertFivePrimeAfterThreePrimeException("5' site occurs after 3' site on Insert sequence")
				
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1] + "&Rev=" + `reverse_complement`)
				
			# June 2/08
			except InvalidDonorVectorSitesNotFoundException:
				i = InvalidDonorVectorSitesNotFoundException("LoxP sites not found on donor sequence")
		
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
				
			except InvalidDonorVectorMultipleSitesException:
				i = InvalidDonorVectorMultipleSitesException("LoxP sites occur more than twice on donor sequence")
			
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
			
			except InvalidDonorVectorSingleSiteException:
				i = InvalidDonorVectorSingleSiteException("Donor vector sequence contains a singe LoxP site")
				
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
			
			except IncompatibleFivePrimeOverhangsException:
				i = IncompatibleFivePrimeOverhangsException("")
				
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
			
			except IncompatibleThreePrimeOverhangsException:
				i = IncompatibleThreePrimeOverhangsException("")
				
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
			
			#else:
				## error
				#utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=13&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
			
		
		elif subtype == 'gateway_entry':
		
			fpSite = "gtacaaaaaa"
			
			# Changed attB2 sequence Sept. 15/08
			#tpSite = "tttcttgtaca"
			tpSite = "tttcttgtac"

			fp_start = insertSeq.lower().find(fpSite)
			fp_end = fp_start + len(fpSite)
			
			tp_start = insertSeq.lower().rfind(tpSite)	# edited Sept. 15/08: look for 3' site from END of sequence??
			#print tpSite
			#print tp_start
			
			# updated Sept. 15/08: Don't subtract 1
			#tp_end = tp_start + len(tpSite) - 1
			tp_end = tp_start + len(tpSite)
			
			# Compare to site positions stored for this Insert
			orig_fp_start = rHandler.findReagentFeatureStart(insert_db_id, fpcs_prop_id)
			orig_fp_end = rHandler.findReagentFeatureEnd(insert_db_id, fpcs_prop_id)
			
			orig_tp_start = rHandler.findReagentFeatureStart(insert_db_id, tpcs_prop_id)
			orig_tp_end = rHandler.findReagentFeatureEnd(insert_db_id, tpcs_prop_id)
			
			if orig_fp_start+1 != fp_start and orig_fp_end != fp_end and orig_tp_start != tp_start and orig_tp_end != tp_end:
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=14&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])

			# Removed June 8/08
			'''
			fp_start = rHandler.findReagentFeatureStart(insert_db_id, fpcs_prop_id)
			fp_end = rHandler.findReagentFeatureEnd(insert_db_id, fpcs_prop_id)
			
			tp_start = rHandler.findReagentFeatureStart(insert_db_id, tpcs_prop_id)
			tp_end = rHandler.findReagentFeatureEnd(insert_db_id, tpcs_prop_id)
			'''

			# Deleted Sept. 15/08: VECTOR-INSERT RECONSTITUTION IS ***INDEPENDENT*** OF ALL OTHER FEATURES EXCEPT CLONING SITES!!!
			#cdnaStart = iHandler.findCDNAStart(insert_db_id)
			#cdnaEnd = iHandler.findCDNAEnd(insert_db_id)

			if fp_start >= 0 and tp_end > 0 and tp_end <= len(insertSeq):
				newSeq = sHandler.entryVectorSequence(pvSeqKey, insertSeq)	# corrected Oct. 31/08 - pass the ENTIRE Insert sequence
				newSeqID = int(sHandler.matchSequence(newSeq))
		
				if newSeqID <= 0:
					newSeqID = int(sHandler.insertSequence(newSeq))
		
				# Updated April 28/08: Implement multi-step creation process
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Step=1&Type=" + rType + "&Sub=" + subtype + "&PV=" + `pv_db_id` + "&I=" + `insert_db_id` + "&Seq=" + `newSeqID`)
			
			else:
				# error
				# Modified Feb. 12/09
				i = EmptyCloningSitesException("")
				err = i.err_code()

				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `err` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
		
		# May 28/08
		elif subtype == 'recomb':
			
			# June 2/08: Check that LoxP occurs exactly twice on IPV sequence
			loxp_seq = enzDict["LoxP"]
			ipvSeq = sHandler.findSequenceByID(ipvSeqKey)
			numLoxp = utils.numOccurs(ipvSeq.lower(), loxp_seq.lower())
			
			if numLoxp == 0:
				raise InvalidDonorVectorSitesNotFoundException("LoxP sites not found on donor sequence")
			
			elif numLoxp == 1:
				raise InvalidDonorVectorSingleSiteException("Donor vector sequence contains one occurrence of LoxP sites")
			
			elif numLoxp > 2:
				raise InvalidDonorVectorMultipleSitesException("LoxP sites occur more than twice on donor sequence")
			
			try:
				# updated Oct. 31/08 - don't pass Insert sequence or linkers to sequence reconstitution function
				#newSeq = sHandler.constructRecombSequence(pvSeqKey, ipvSeqKey, insertSeqKey, insertLinkers)		# removed Oct. 31/08
				newSeq = sHandler.constructRecombSequence(pvSeqKey, ipvSeqKey)	# added Oct. 31/08
				newSeqID = int(sHandler.matchSequence(newSeq))
				
				if newSeqID <= 0:
					newSeqID = int(sHandler.insertSequence(newSeq))
		
				# Updated April 28/08: Implement multi-step creation process
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Step=1&Type=" + rType + "&Sub=" + subtype + "&PV=" + `pv_db_id` + "&IPV=" + `ipv_db_id` + "&Seq=" + `newSeqID`)
				
			except MultipleSiteOccurrenceException:
				i = MultipleSiteOccurrenceException("")
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Step=1&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()`)
				
			except InsertSitesNotFoundOnParentSequenceException:
				i = InsertSitesNotFoundOnParentSequenceException("")
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Step=1&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()`)

		elif subtype == 'gateway_expression':
			
			ipvSeq = sHandler.findSequenceByID(ipvSeqKey)

			# June 3, 2010: Discussed with Karen and decided on the following validation strategy:
			
			# Check whether ccdB is recorded on PV but not on IPV; if that's not the case raise an error
			# (lack of sites will be discovered in the sequence construction function)
			
			# check ccdB (selectable marker)
			selMarkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["selectable marker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
			
			ccdb_in_entry_vector = rHandler.existsPropertyValue(ipv_db_id, selMarkerPropID, "ccdB")

			if not rHandler.existsPropertyValue(pv_db_id, selMarkerPropID, "ccdB"):
				# here, issue an error
				if not ccdb_in_entry_vector:
					i = GatewayParentDestinationException("Invalid parent destination vector")
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `pvAssocProp`)
				else:
					i = SwapGatewayParentsException("ccdB in Gateway Entry clone and not in Parent Destination")
					
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `pvAssocProp`)
				
			'''
			# June 3, 2010: Thought of putting this in, but there's no point.  E.g. V1899, a GW Parent Destination, CANNOT and SHOULD NOT have its cloning sites set to attR, they occur on its sequence but that's not how the vector was prepared.
			pv_fpcs = rHandler.findSimplePropertyValue(pv_db_id, fpcs_prop_id)
			pv_tpcs = rHandler.findSimplePropertyValue(pv_db_id, tpcs_prop_id)
			
			ipv_fpcs = rHandler.findSimplePropertyValue(ipv_db_id, fpcs_prop_id)
			ipv_tpcs = rHandler.findSimplePropertyValue(ipv_db_id, tpcs_prop_id)
			
			#print "Content-type:text/html"
			#print
			
			#print pv_fpcs
			#print pv_tpcs
			
			#print ipv_fpcs
			#print ipv_tpcs
			
			if pv_fpcs != 'attR1' or pv_tpcs != 'attR2' or ipv_fpcs != 'attL1' or ipv_tpcs != 'attL2':
			'''
			
			newSeq = sHandler.expressionVectorSequence(pvSeqKey, ipvSeq)
			newSeqID = int(sHandler.matchSequence(newSeq))
			
			if newSeqID <= 0:
				newSeqID = int(sHandler.insertSequence(newSeq))
	
			utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Step=1&Type=" + rType + "&Sub=" + subtype + "&PV=" + `pv_db_id` + "&IPV=" + `ipv_db_id` + "&Seq=" + `newSeqID`)
			
	except InsertSitesException:
		i = InsertSitesException("Wrong sites on Insert")
		
		utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
	
	except InsertSitesNotFoundOnParentSequenceException:
		i = InsertSitesNotFoundOnParentSequenceException("Insert sites not found on parent vector sequence")
		
		if subtype == 'nonrecomb' or subtype == 'gateway_entry':
			utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
		else:				# added June 2/08
			utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
		
	except MultipleSiteOccurrenceException:
		i = MultipleSiteOccurrenceException("Site found more than once on parent vector sequence")
		
		utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
		
	except HybridizationException:
		i = HybridizationException("Sites cannot be hybridized")
		
		utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
	
	except FivePrimeAfterThreePrimeException:
		i = FivePrimeAfterThreePrimeException("5' site occurs after 3' site on parent vector sequence")
		
		utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
		
	# June 2/08
	except InvalidDonorVectorSitesNotFoundException:
		i = InvalidDonorVectorSitesNotFoundException("LoxP sites not found on donor sequence")

		utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
		
	except InvalidDonorVectorMultipleSitesException:
		i = InvalidDonorVectorMultipleSitesException("LoxP sites occur more than twice on donor sequence")
	
		utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
	
	except InvalidDonorVectorSingleSiteException:
		i = InvalidDonorVectorSingleSiteException("Donor vector sequence contains a singe LoxP site")
		
		utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
	
	# Dec. 14/09
	except EmptyParentVectorSequenceException:
		i = EmptyParentVectorSequenceException("Empty parent Vector sequence exception")
	
		utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])
	
	# Dec. 14/09	
	except EmptyParentInsertSequenceException:
		i = EmptyParentInsertSequenceException("Empty parent Insert sequence exception")
	
		utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&R1=" + insertCloningSites[0] + "&R2=" + insertCloningSites[1])


# Updated Jan. 21/09: Added 'reverse' parameter - if True, reverse complement the Insert sequence before mapping features
def previewVectorFeatures(rID, reagent, parents, oldSeqID, newSeqID, newSeq, currUser, db, cursor, insertCloningSites={}, reverse=False):
	
	#print "Content-type:text/html"
	#print
	#print "rev " + `reverse`
	#print `insertCloningSites`
	
	# Get parent values depending on subtype
	url = ""	# the URL to redirect to, will have varying parent name parameters
	
	rType = reagent.getType()
	subtype = reagent.getSubtype()
	
	# June 6/08: Delete features before remapping
	rHandler.deleteReagentFeatures(rID)

	# Nov. 6/08 - Delete linkers separately, as they're not included in features list
	# Update July 9/09: different property ID selection mechanism!
	fpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	tpLinkerPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
	fpcs_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	tpcs_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])

	seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])
	
	rHandler.deleteReagentProperty(rID, fpLinkerPropID)
	rHandler.deleteReagentProperty(rID, tpLinkerPropID)
	
	insert_fp_linker = ""
	insert_tp_linker = ""
	
	# june 4, 2010
	pv_db_id = 0
	insert_db_id = 0
	ipv_db_id = 0
	
	if subtype == 'nonrecomb' or subtype == 'gateway_entry':
		pv_db_id = parents["vector parent id"]
		insert_db_id = parents["insert id"]
		
		parentVectorID = rHandler.convertDatabaseToReagentID(pv_db_id)
		insertID = rHandler.convertDatabaseToReagentID(insert_db_id)
		
		insertSeqKey = rHandler.findDNASequenceKey(insert_db_id)
		pvSeqKey = rHandler.findDNASequenceKey(pv_db_id)	# added May 22/08
		
		insertSequence = sHandler.findSequenceByID(insertSeqKey).strip().lower()
		pvSequence = sHandler.findSequenceByID(pvSeqKey).strip().upper()
	
		# Nov. 6/08: Find Insert linkers separately
		insert_fp_linker = rHandler.findSimplePropertyValue(insert_db_id, fpLinkerPropID)
		insert_tp_linker = rHandler.findSimplePropertyValue(insert_db_id, tpLinkerPropID)
		
		if insertCloningSites.has_key("5' cloning site"):
			r1 = insertCloningSites["5' cloning site"]
		else:
			r1 = ""
			
		if insertCloningSites.has_key("3' cloning site"):
			r2 = insertCloningSites["3' cloning site"]
		else:
			r2 = ""

		# Updated Nov. 18/08: Include sites in URL in case of hybrid
		url = hostname + "Reagent.php?View=2&Step=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + `pv_db_id` + "&I=" + `insert_db_id` + "&Seq=" + `newSeqID` + "&R1=" + r1 + "&R2=" + r2 + "&Rev=" + `reverse`
		
		#print url

	elif subtype == 'recomb' or subtype == 'gateway_expression':
		pv_db_id = parents["vector parent id"]
		ipv_db_id = parents["parent insert vector"]
		
		parentVectorID = rHandler.convertDatabaseToReagentID(pv_db_id)
		ipvID = rHandler.convertDatabaseToReagentID(ipv_db_id)
		
		ipvSeqKey = rHandler.findDNASequenceKey(ipv_db_id)
		pvSeqKey = rHandler.findDNASequenceKey(pv_db_id)	# added May 22/08
		
		ipvSequence = sHandler.findSequenceByID(ipvSeqKey).strip().lower()
		pvSequence = sHandler.findSequenceByID(pvSeqKey).strip().upper()
		
		# Insert from IPV?
		if parents.has_key("insert id"):
			insert_db_id = parents["insert id"]
			insertID = rHandler.convertDatabaseToReagentID(insert_db_id)
			insertSeqKey = rHandler.findDNASequenceKey(insert_db_id)
			insertSequence = sHandler.findSequenceByID(insertSeqKey).strip().lower()
			
			# Nov. 6/08: Find Insert linkers separately
			insert_fp_linker = rHandler.findSimplePropertyValue(insert_db_id, fpLinkerPropID)
			insert_tp_linker = rHandler.findSimplePropertyValue(insert_db_id, tpLinkerPropID)
			
		url = hostname + "Reagent.php?View=2&Step=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + `pv_db_id` + "&IPV=" + `ipv_db_id` + "&Seq=" + `newSeqID`
		
	else:
		# Novel vector - no parents, nothing to remap, BUT NEED TO SAVE SEQUENCE
		
		# Still delete any previous entries for this reagent (in case got back to this view from a later stage after some feature values have been entered)
		rHandler.deleteReagentProperty(rID, seqPropID)
		rHandler.addReagentProperty(rID, seqPropID, newSeqID)

		# June 3/08: delete the rest of the features
		rHandler.deleteReagentProperty(rID, fpcs_prop_id)
		rHandler.deleteReagentProperty(rID, tpcs_prop_id)
		
		# Nov. 6/08 - Delete linkers separately, as they're not included in features list (if they don't exist no harm done)
		rHandler.deleteReagentProperty(rID, fpLinkerPropID)
		rHandler.deleteReagentProperty(rID, tpLinkerPropID)
		
		rHandler.deleteReagentFeatures(rID)

		utils.redirect(hostname + "Reagent.php?View=2&Step=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&Seq=" + `newSeqID`)

	# For non-Novel reagents, remap features
	newSeq = utils.squeeze(newSeq).lower()

	# Avoid duplicate values - may already have ReagentPropList_tbl entries for this reagent, clear them before saving (for consistency)
	rHandler.deleteReagentProperty(rID, seqPropID)
	rHandler.addReagentProperty(rID, seqPropID, newSeqID)
	
	# April 17/08: FIND OUT WHERE ***cDNA*** starts on the new sequence
	# Correction May 15/08: need the actual cDNA portion of the Insert, w/o sites or linkers
	
	# find cDNA portion on **ORIGINAL** Insert sequence
	# CHANGE JUNE 4, 2010: Now that we are allowing IPV to be of any type and it may not necessarily include an Insert, GRAB CDNA FROM THE **VECTOR** SEQUENCE.
	# added if-else June 4, 2010, modified again Dec. 3, 2010: checking if insert exists is not sufficient, add check for vector type again!!
	if subtype == 'nonrecomb' or subtype == 'gateway_entry':	# Dec. 3, 2010
		#if insert_db_id > 0:	# removed Dec 3, 2010, replaced with 'try-assert' below
		try:
			assert insert_db_id > 0
		
			insert_cdnaStart = iHandler.findCDNAStart(insert_db_id)
			insert_cdnaEnd = iHandler.findCDNAEnd(insert_db_id)
			
			# Updated June 1/08: added 'if-else'
			if insert_cdnaStart > 0 and insert_cdnaEnd > 0:
				cdnaSeq = insertSequence[insert_cdnaStart-1:insert_cdnaEnd]
			else:
				cdnaSeq = insertSequence
				
		except AssertionError:
			raise ReagentDoesNotExistException("Insert does not exist")

	elif subtype == 'recomb' or subtype == 'gateway_expression':	# dec 3/10
	#elif ipv_db_id > 0:	# removed Dec 3, 2010, replaced with 'try-assert' below
		try:
			assert ipv_db_id > 0
			
			# keep variable names for code consistency
			cdnaStart = iHandler.findCDNAStart(ipv_db_id)
			cdnaEnd = iHandler.findCDNAEnd(ipv_db_id)
			
			# Updated June 1/08: added 'if-else'
			if cdnaStart > 0 and cdnaEnd > 0:
				cdnaSeq = ipvSequence[cdnaStart-1:cdnaEnd]
			else:
				cdnaSeq = ipvSequence
		except AssertionError:
			raise ReagentDoesNotExistException("Insert Parent Vector does not exist")
	else:
		cdnaSeq = ""
		
	# Jan. 21/09
	if reverse:
		cdnaSeq = sHandler.reverse_complement(cdnaSeq)
		#print cdnaSeq
		
		# Update July 2/09: pass cdnaPropID to setPropertyDirection() function in combination with its category
		cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
		rHandler.setPropertyDirection(rID, cdnaPropID, 'reverse')

	# Get sites - removed July 9/09
	#fpcs_prop_id = prop_Name_ID_Map["5' cloning site"]
	#tpcs_prop_id = prop_Name_ID_Map["3' cloning site"]
	
	# May 28/08: Sites depend on vector subtype - LOWERCASE!!!!
	if subtype == 'nonrecomb':
		#print r1
		
		# Nov. 18/08: Check if either site on the **NEW VECTOR** is hybrid
		fp_insert_cs = utils.make_array(r1)
		tp_insert_cs = utils.make_array(r2)
		
		#print `fp_insert_cs`
		pvSeq = Seq(pvSequence)
		insertSeq = Seq(insertSequence)
		
		if sHandler.isHybrid(fp_insert_cs):
			
			try:
				#print fp_insert_cs
				fpSite = sHandler.hybridSeq(fp_insert_cs)
				#print fpSite

			except DegenerateHybridException:
				fpSite = sHandler.getHybridDegenerate(fp_insert_cs, pvSeq, insertSeq, "5 prime")
				#print fpSite
			
			# Jan. 16/09: DON'T EVER DO THIS!!!!!!!!!!!!!!!!
			#fp_search_start = rHandler.findReagentFeatureStart(pv_db_id, prop_Name_ID_Map["5' cloning site"]) - 1
			
			fp_h1 = sHandler.get_H_1(fp_insert_cs)
			
			if sHandler.enzDict.has_key(fp_h1):
				fp_h1_enz = sHandler.enzDict[fp_h1]
				#print fp_h1_enz
				
				# added Dec. 18/08
				fp_h1_seq = fp_h1_enz.elucidate().replace("_", "")
				fp_h1_clvg = fp_h1_seq.find("^")
				fp_h1_flank = fp_h1_seq[0:fp_h1_clvg]
				
				pv_fp_h1_pos = fp_h1_enz.search(pvSeq, False)
				#print `pv_fp_h1_pos`
				pv_fp_h1_pos.sort()
				
				if len(pv_fp_h1_pos) > 0:
					fpStartPos = newSeq.lower().find(fpSite.lower()) + 1	# replaced March 15, 2010
					#print fpStartPos
					# NO.  Replaced March 15, 2010 - this would return wrong results if the hybrid site is BamHI/BglII, and the sequence contains an additional BamHI non-hybrid site.  Then the result would be the position of BamHI, not the BamHI/BglII hybrid.
					#fpStartPos = pv_fp_h1_pos[0] - len(fp_h1_flank)
					fpEndPos = fpStartPos + len(fpSite)

		else:
			# NO!!!! Removed Feb. 11/09 - what if the site is not hybrid but customized and is not the same as Insert cloning site even though found in Insert sequence!!
			#fp_insert_cs = rHandler.findSimplePropertyValue(insert_db_id, fpcs_prop_id)
			fp_insert_cs = r1		# use whatever arrives as input!
			
			# 5' site
			#five_prime_site = utils.make_array(fp_insert_cs)
			#print `five_prime_site`
			
			#if not sHandler.isHybrid(five_prime_site):		# Removed Jan. 13/09: Why check again? we're in else, meaning 5' is not hybrid
			
			# Sept. 30/08: SfiI sequences from V1889
			#if fp_insert_cs == 'SfiI':			# removed March 8/10
				#fpSite = "GGCCATTACGGCC"
			#else:
			if enzDict.has_key(fp_insert_cs):
				fpSite = enzDict[fp_insert_cs]
			else:
				print "Content-type:text/html"
				print
				print "Unknown 5' site for reagent " + `rID` + ": " + fp_insert_cs
				return
			#else:
				#fpSite = sHandler.hybridSeq(five_prime_site)
		
		# 3' site
		if sHandler.isHybrid(tp_insert_cs):
			
			#print `tp_insert_cs`
			
			try:
				tpSite = sHandler.hybridSeq(tp_insert_cs)
			except DegenerateHybridException:
				tpSite = sHandler.getHybridDegenerate(tp_insert_cs, pvSeq, insertSeq, "3 prime")
			
			#print tpSite
			
			# NO!!!!!!!!!!!!!!!!!!!!!!  DON'T EVER DO THIS!!!!!!!!!!!!!!
			#tp_search_end = rHandler.findReagentFeatureEnd(pv_db_id, prop_Name_ID_Map["3' cloning site"]) - 1
			
			tp_h2 = sHandler.get_H_2(tp_insert_cs)
			#pvSeq = Seq(pvSequence)
			
			if sHandler.enzDict.has_key(tp_h2):
				tp_h2_enz = sHandler.enzDict[tp_h2]
				#print tp_h2_enz
				
				tp_h2_seq = tp_h2_enz.elucidate().replace("_", "")
				#print tp_h2_seq
				tp_h2_clvg = tp_h2_seq.find("^")	# ok
				tp_h2_flank = tp_h2_seq[0:tp_h2_clvg]	# ok
				#print tp_h2_flank
				
				pv_tp_h2_pos = tp_h2_enz.search(pvSeq, False)
				#print `tp_h2_enz`
				
				#print `pv_tp_h2_pos`
				pv_tp_h2_pos.sort()
				
				if len(pv_tp_h2_pos) > 0:
					# NO.  Replaced March 15, 2010 - this would return wrong results if the hybrid site is BamHI/BglII, and the sequence contains an additional BamHI non-hybrid site.  Then the result would be the position of BamHI, not the BamHI/BglII hybrid.
					
					'''
					pv_tp_start = pv_tp_h2_pos[len(pv_tp_h2_pos)-1] - len(tp_h2_flank)	# ok
					#print pv_tp_start
					
					pv_tp_end = pv_tp_start + len(tpSite)
					#print pv_tp_end
					
					#if tp_end >= len(pvSequence):
					pv_post = pvSequence[pv_tp_end:].lower()
					#print pv_post
					
					# Feb. 6/09: Added 'if' to account for an extreme case where the 3' site is at the very end of the PV sequence
					if len(pv_post) > 0:
						tpStartPos = newSeq.lower().find(pv_post.lower()) - len(tpSite)
						#print tpStartPos
						tpEndPos = tpStartPos + len(tpSite)
						#print tpEndPos
					else:
						# check, really not sure what would come out of this - just set it to be at the end of the new sequence
						tpStartPos = len(newSeq) - len(tpSite) + 1
						tpEndPos = tpStartPos + len(tpSite)
					'''
					
					#print tpSite.lower()
					
					tpStartPos = newSeq.lower().rfind(tpSite.lower()) + 1
					tpEndPos = tpStartPos + len(tpSite)
		else:
			# NO!!!! Removed Feb. 11/09 - what if the site is not hybrid but customized and is not the same as Insert cloning site even though found in Insert sequence!!
			#tp_insert_cs = rHandler.findSimplePropertyValue(insert_db_id, tpcs_prop_id)
			
			tp_insert_cs = r2		# use actual input value!
			
			#three_prime_site = utils.make_array(tp_insert_cs)
	
			#if not sHandler.isHybrid(three_prime_site):
				
			# Sept. 30/08: Special case: SfiI from V1889
			#if tp_insert_cs == 'SfiI':
				#tpSite = "GGCCGCCTCGGCC"
			#else:
			if enzDict.has_key(tp_insert_cs):	# March 8/10
				tpSite = enzDict[tp_insert_cs]
			else:
				print "Content-type:text/html"
				print
				print "Unknown 3' site for reagent " + `rID` + ": " + tp_insert_cs
				return
			
		#print fpSite
		#print tpSite
		
	elif subtype == 'recomb':
		fp_insert_cs = 'LoxP'
		tp_insert_cs = 'LoxP'
	
		fpSite = enzDict[fp_insert_cs].lower()
		tpSite = enzDict[tp_insert_cs].lower()
	
	elif subtype == 'gateway_entry':
		fp_insert_cs = 'attL1'
		tp_insert_cs = 'attL2'
		
		# Special sequences - Changed May 29/08 after discussion with Karen
		fpSite = 'tttgtacaaaaaa'
		tpSite = 'tttcttgtacaaagtt'
		
	elif subtype == 'gateway_expression':
		fp_insert_cs = 'attB1'
		tp_insert_cs = 'attB2'
		
		fpSite = 'gtacaaaaaa'
		tpSite = 'tttcttgtac'
		
	# 5' cloning site (sequence in lowercase, search for site in lowercase too)
	#print fp_search_start
	#print fpSite
	
	if not sHandler.isHybrid(fp_insert_cs):
		if newSeq.find(fpSite.lower()) >= 0:
			fpStartPos = newSeq.find(fpSite.lower()) + 1
			fpEndPos = fpStartPos + len(fpSite)
		else:
			# Dec. 17/08: reason could be that the site is a degenerate - try one more time with BioPython
			# ** Jan. 14/09: Ignoring the case of a hybrid degenerate (e.g. RsrII-Rsr2I) for now **
			tmpSeq = Bio.Seq.Seq(newSeq)
			
			# Jan. 6/09: Gateway sites not in enzDict
			if sHandler.enzDict.has_key(fp_insert_cs):
				fp_cs = sHandler.enzDict[fp_insert_cs]
				
				# added Dec. 18/08
				fp_seq = fp_cs.elucidate().replace("_", "")
				fp_clvg = fp_seq.find("^")
				fp_flank = fp_seq[0:fp_clvg]
				
				tmp_fp_pos = fp_cs.search(tmpSeq)
				tmp_fp_pos.sort()
				
				if len(tmp_fp_pos) > 0:
					fpStartPos = tmp_fp_pos[0] - len(fp_flank)
					fpEndPos = fpStartPos + len(fpSite)
					
					if fpStartPos == 0:
						fpStartPos = 0
						fpEndPos = 0
				else:
					fpStartPos = 0
					fpEndPos = 0
			# Jan. 6/09
			else:
				fpStartPos = 0
				fpEndPos = 0
		
	# 3' cloning site
	if not sHandler.isHybrid(tp_insert_cs):
		if newSeq.rfind(tpSite.lower()) >= 0:
			tpStartPos = newSeq.rfind(tpSite.lower()) + 1		# look from END of sequence?????
			tpEndPos = tpStartPos + len(tpSite)
		else:	
			# Dec. 17/08
			# reason could be that the site is a degenerate - try one more time with BioPython
			tmpSeq = Bio.Seq.Seq(newSeq)
			
			# Jan. 6/09: Gateway sites are not in enzDict
			if sHandler.enzDict.has_key(tp_insert_cs):
				tp_cs = sHandler.enzDict[tp_insert_cs]
				
				# dec. 18/08
				tp_seq = tp_cs.elucidate().replace("_", "")
				tp_clvg = tp_seq.find("^")
				tp_flank = tp_seq[0:tp_clvg]
				
				tmp_tp_pos = tp_cs.search(tmpSeq)
				tmp_tp_pos.sort()
				
				if len(tmp_tp_pos) > 0:
					tpStartPos = tmp_tp_pos[len(tmp_tp_pos)-1] - len(tp_flank)
					tpEndPos = tpStartPos + len(tpSite)	# do NOT subtract 1 here, done below at db insertion!
					
					if tpStartPos == 0:
						tpStartPos = 0
						tpEndPos = 0
				else:
					tpStartPos = 0
					tpEndPos = 0
			# Jan. 6/09
			else:
				tpStartPos = 0
				tpEndPos = 0
				
	#print "Content-type:text/html"
	#print
	#print "5' " + `fpStartPos` + " 3' " + `tpStartPos`
	
	# Nov. 18/08: Pass site positions in URL (mostly done for Hybrids)
	url += "&FPS=" + `fpStartPos`
	url += "&FPE=" + `fpEndPos-1`
	url += "&TPS=" + `tpStartPos`
	url += "&TPE=" + `tpEndPos-1`
	
	# Nov. 6/08: Map linkers in the same fashion
	if insert_fp_linker and len(insert_fp_linker) >= 10:
		fpLinkerStartPos = newSeq.find(insert_fp_linker.lower()) + 1
		fpLinkerEndPos = fpLinkerStartPos + len(insert_fp_linker)
		rHandler.addReagentProperty(rID, fpLinkerPropID, insert_fp_linker, fpLinkerStartPos, fpLinkerEndPos-1)
	else:
		fpLinkerStartPos = 0
		fpLinkerEndPos = 0
		
	# 3' linker
	if insert_tp_linker and len(insert_tp_linker) >= 10:
		tpLinkerStartPos = newSeq.find(insert_tp_linker.lower()) + 1
		tpLinkerEndPos = tpLinkerStartPos + len(insert_tp_linker)
		rHandler.addReagentProperty(rID, tpLinkerPropID, insert_tp_linker, tpLinkerStartPos, tpLinkerEndPos-1)
	else:
		tpLinkerStartPos = 0
		tpLinkerEndPos = 0
	
	# Nov. 3/08 - check if 5' site is after 3' - if yes, look for the reverse sequence
	cdna_dir = 'forward'
	
	if fpStartPos > tpStartPos:
		if not reverse:		# if haven't reversed it already
			cdnaSeq = sHandler.reverse_complement(cdnaSeq)
			
			# Update July 2/09: pass cdnaPropID to setPropertyDirection() function in combination with its category
			cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
			rHandler.setPropertyDirection(rID, cdnaPropID, 'reverse')
		
	# cDNA (in lowercase already)
	if newSeq.find(cdnaSeq) >= 0:
		
		cdnaStart = newSeq.find(cdnaSeq) + 1		# may 22/08
		cdnaEnd = cdnaStart + len(cdnaSeq) - 1		# may 22/08
	else:
		cdnaStart = 0
		cdnaEnd = 0

	# June 3/08: Karen's request: Issue a warning if user destroys one or both of the LoxP sites in a recomb. sequence
	if subtype == 'recomb':
		
		if (fpStartPos == 0 and fpEndPos == 0) or (tpStartPos == 0 and tpEndPos == 0) or (fpStartPos == tpStartPos and fpEndPos == tpEndPos):
			i = RecombinationVectorSequenceMissingLoxPException("Missing LoxP sites from recombination vector sequence")
		
			utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + ipvID + "&Err=" + `i.err_code()` + "&Seq=" + `oldSeqID`)
	
	# July 2/09
	cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])

	rHandler.setPropertyPosition(rID, cdnaPropID, "startPos", cdnaStart)
	rHandler.setPropertyPosition(rID, cdnaPropID, "endPos", cdnaEnd)
	
	# Store new values - DELETE BEFORE INSERTING
	
	# removed July 9/09
	#fpcs_prop_id = prop_Name_ID_Map["5' cloning site"]
	#tpcs_prop_id = prop_Name_ID_Map["3' cloning site"]

	rHandler.addReagentProperty(rID, fpcs_prop_id, fp_insert_cs, fpStartPos, fpEndPos-1)
	rHandler.addReagentProperty(rID, tpcs_prop_id, tp_insert_cs, tpStartPos, tpEndPos-1)
	
	if subtype == 'gateway_entry':
		
		iFeatures = rHandler.findReagentSequenceFeatures(insert_db_id)
		pvFeatures = rHandler.findReagentSequenceFeatures(pv_db_id)
		
		# Parent Vector features are found before cDNA start and after cDNA end on the new sequence
		for f in pvFeatures:
			fType = f.getFeatureType()
			
			if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
			
				# original feature positions on PV sequence
				pv_fStart = f.getFeatureStartPos()
				pv_fEnd = f.getFeatureEndPos()
			
				fVal = f.getFeatureName()
				pv_fDir = f.getFeatureDirection()
				
				if pv_fStart > 0 and pv_fEnd > 0:
					
					# Features from PV are inherited IFF they are located **entirely** before the 5' cloning site or after 3' cloning site
					# Hence: May 22/08 - Find 5' start and 3' end on **original** PV sequence
					pv_fpcs_start = pvSequence.lower().find(fpSite)
					pv_tpcs_end = pvSequence.lower().find(tpSite) + len(tpSite)
					
					fSeq = pvSequence[pv_fStart-1:pv_fEnd].lower()
	
					# June 4/08 - Search for the feature IFF > 10 nts
					if len(fSeq) >= 10:
	
						# Look for this feature on the NEWly reconstituted Vector
						fIndex = newSeq.lower().find(fSeq)
	
						if fIndex >= 0:
							fpStart = fIndex + 1
							fpEnd = fpStart + len(fSeq) - 1
							
							# If found, make sure this feature occurs either before or after the INSERT - i.e. before 5' cloning site start OR after 3' cloning site end
							if (fpStart < fpStartPos and fpEnd < fpStartPos) or (fpStart >= tpEndPos and fpEnd > tpEndPos):
								fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
								rHandler.addReagentProperty(rID, fID, fVal, fpStart, fpEnd, pv_fDir)
					
								if f.getFeatureDescrType():
									fDescr = f.getFeatureDescrName()
									
									# Updated July 2/09, moved up July 9/09
									#fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
									rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fpStart, fpEnd, fDescr)
				
		# Search for each Insert feature on the new sequence
		# June 6/08: Added 'if' statement - needs testing
		for f in iFeatures:
			fType = f.getFeatureType()
			#print fType
			
			if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
				# feature positions on the Insert sequence - Modified May 21/08: Account for the fact that NOT the entire Insert sequence is used, only a subsequence
				fiStart = f.getFeatureStartPos()
				fiEnd = f.getFeatureEndPos()
				fVal = f.getFeatureName()
				#print fVal
				fSeq = insertSequence[fiStart:fiEnd].lower()
			
				if len(fSeq) >= 10:
					fStart = newSeq.lower().find(fSeq)
					fEnd = fStart + len(fSeq)
				
					if fStart >= 1:
	
						# May 22/08: NO!!! This is precisely what we decided we're NOT going to do!!!
						'''
						## recompute based on cDNA start
						#fiStart = fiStart - insert_cdnaStart
						#fiEnd = fiEnd - insert_cdnaStart
						
						# feature positions on the new Vector sequence
						#fStart = cdnaStart + fiStart
						#fEnd = fStart + len(fSeq)
						'''
						
						# Insert features, on the other hand, must occur entirely WITHIN the Insert - i.e. between the cloning sites (Oct. 30/08)
						if fStart >= fpStartPos and fEnd <= tpEndPos:
							# Orientation
							fiDir = f.getFeatureDirection()
							
							fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
							rHandler.addReagentProperty(rID, fID, fVal, fStart, fEnd, fiDir)
						
							if f.getFeatureDescrType():
								fDescr = f.getFeatureDescrType()
								
								# Updated July 2/09
								rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fStart, fEnd, fDescr)
	elif subtype == 'nonrecomb':
		iFeatures = rHandler.findReagentSequenceFeatures(insert_db_id)
		pvFeatures = rHandler.findReagentSequenceFeatures(pv_db_id)
		
		#print "Content-type:text/html"
		#print
		
		# changes made Oct. 14/08
		tmp_dict = {}
		
		# Parent Vector features are found before 5' start and after 3' end on the new sequence
		for f in pvFeatures:
			fType = f.getFeatureType()
			#print fType
			
			if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
			
				# original feature positions on PV sequence
				pv_fStart = f.getFeatureStartPos()
				#print `pv_fStart`
				pv_fEnd = f.getFeatureEndPos()
				
				fSeq = pvSequence[pv_fStart-1:pv_fEnd].lower()
				tmp_dict[fSeq] = f
		
		for fSeq in tmp_dict.keys():
			f_tmp = tmp_dict[fSeq]
			
			if len(fSeq) >= 10:
				fList = utils.findall(newSeq.lower(), fSeq, [])
				
				for fIndex in fList:
					fpStart = fIndex + 1
					fpEnd = fpStart + len(fSeq) - 1
					
					if fpStart > 0 and fpEnd > 0:
						# removed Feb. 23/09 - why same 'if' twice??!!!!
						## If found, make sure this feature occurs either before or after the CLONING SITES!!!!!
						#if (fpStart < fpStartPos and fpEnd < fpStartPos) or (fpStart > fpEndPos and fpEnd > fpEndPos):
						
						#If found, make sure this feature occurs either before or after the CLONING SITES
						# Nov. 4/08: fpStart >= tpEndPos, since tpEndPos is one greater than the actual site end value
						if (fpStart < fpStartPos and fpEnd < fpStartPos) or (fpStart >= tpEndPos and fpEnd > tpEndPos):
							fType = f_tmp.getFeatureType()
							fVal =  f_tmp.getFeatureName()
							pv_fDir = f_tmp.getFeatureDirection()
				
							#print fType + ": " + fVal + ": " + `fpStart` + "-" + `fpEnd`
							
							fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
							rHandler.addReagentProperty(rID, fID, fVal, fpStart, fpEnd, pv_fDir)
					
							if f_tmp.getFeatureDescrType():
								fDescr = f_tmp.getFeatureDescrName()
								#print "?? " + fDescr
								
								# Updated July 2/09
								rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fpStart, fpEnd, fDescr)

		# Search for each Insert feature on the new sequence
		# June 6/08: Added 'if' statement - needs testing
		
		tmp_i_dict = {}
		
		for f in iFeatures:
			fType = f.getFeatureType()
			#print fType
			
			if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
				# feature positions on the Insert sequence - Modified May 21/08: Account for the fact that NOT the entire Insert sequence is used, only a subsequence
				fiStart = f.getFeatureStartPos()
				fiEnd = f.getFeatureEndPos()
				#fVal = f.getFeatureName()
				#print fVal
				fSeq = insertSequence[fiStart:fiEnd].lower()
				tmp_i_dict[fSeq] = f
			
			
		for fSeq in tmp_i_dict.keys():
			f_tmp = tmp_i_dict[fSeq]
			
			if len(fSeq) >= 10:
				
				# Jan. 21/09: Look for reverse features if sequence reverse complemented???
				if reverse:
					fSeq = sHandler.reverse_complement(fSeq)

				fList = utils.findall(newSeq.lower(), fSeq, [])
				
				for fIndex in fList:
					fStart = newSeq.lower().find(fSeq)
					fEnd = fStart + len(fSeq)
					
					f_tmp.getFeatureName()
					
					# still, double check to make sure this feature occurs between the CLONING SITES on the resulting Vector sequence!!!!
					if fStart >= fpStartPos and fEnd <= tpEndPos:
						fType = f_tmp.getFeatureType()
						fVal =  f_tmp.getFeatureName()
						fiDir = f_tmp.getFeatureDirection()
						
						# Jan. 22/09
						if reverse:
							fiDir = 'reverse'
						
						fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
						rHandler.addReagentProperty(rID, fID, fVal, fStart, fEnd, fiDir)
					
						if f_tmp.getFeatureDescrType():
							fDescr = f_tmp.getFeatureDescrType()
							
							# Updated July 2/09
							rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fStart, fEnd, fDescr)
	
	elif subtype == 'recomb':
		
		# April 17/08: Map the rest of the features
		ipvFeatures = rHandler.findReagentSequenceFeatures(ipv_db_id)
		pvFeatures = rHandler.findReagentSequenceFeatures(pv_db_id)
		
		# changes made Oct. 14/08
		tmp_dict = {}
		
		# Parent Vector features are found before cDNA start and after cDNA end on the new sequence
		for f in pvFeatures:
			fType = f.getFeatureType()
			
			if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
			
				# original feature positions on PV sequence
				pv_fStart = f.getFeatureStartPos()
				pv_fEnd = f.getFeatureEndPos()
				
				fSeq = pvSequence[pv_fStart-1:pv_fEnd].lower()
				tmp_dict[fSeq] = f
			
		for fSeq in tmp_dict.keys():
			f_tmp = tmp_dict[fSeq]
			
			if len(fSeq) >= 10:
				#print f_tmp.getFeatureName()

				fList = utils.findall(newSeq.lower(), fSeq, [])
				#print `fList`
				
				for fIndex in fList:
					fpStart = fIndex + 1
					fpEnd = fpStart + len(fSeq) - 1
					
					if fpStart > 0 and fpEnd > 0:
						
						fType = f_tmp.getFeatureType()
						fVal =  f_tmp.getFeatureName()
						#print fVal
						pv_fDir = f_tmp.getFeatureDirection()
				
						# Nov. 4/08: In recombination vectors there's only one LoxP occurrence on the parent and it should be transferred onto the child as a restriction site; rest of features are all inherited.  So remove the site position check; don't replace it with check for a single LoxP site occurrence yet, see how it goes.
						
						# removed Nov. 6/08
						##If found, make sure this feature occurs either before or after the CLONING SITES!!!!!!!!
						#if (fpStart < fpStartPos and fpEnd < fpStartPos) or (fpStart >= tpEndPos and fpEnd > tpEndPos):
							#print fType + ": " + fVal + ": " + `fpStart` + "-" + `fpEnd`
						
						fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
						rHandler.addReagentProperty(rID, fID, fVal, fpStart, fpEnd, pv_fDir)
				
						if f_tmp.getFeatureDescrType():
							fDescr = f_tmp.getFeatureDescrName()
							#print fDescr
					
							# Updated July 2/09
							rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fpStart, fpEnd, fDescr)

		# Search for each IPV feature on the new sequence
		# June 6/08: Added 'if' statement - needs testing
		
		tmp_ipv_dict = {}
		
		for f in ipvFeatures:
			fType = f.getFeatureType()
			#print fType
			
			if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
				# feature positions on the Insert sequence - Modified May 21/08: Account for the fact that NOT the entire Insert sequence is used, only a subsequence
				fiStart = f.getFeatureStartPos()
				fiEnd = f.getFeatureEndPos()
				#fVal = f.getFeatureName()
				#print fVal
				fSeq = ipvSequence[fiStart:fiEnd].lower()
				tmp_ipv_dict[fSeq] = f
			
		for fSeq in tmp_ipv_dict.keys():
			f_tmp = tmp_ipv_dict[fSeq]
			#print f_tmp.getFeatureName()

			if len(fSeq) >= 10:
				fList = utils.findall(newSeq.lower(), fSeq, [])
				#print `fList`

				for fIndex in fList:
					fStart = newSeq.lower().find(fSeq)
					#print "start " + `fStart`
					fEnd = fStart + len(fSeq)
					#print "end  " + `fEnd`
					
					# still, double check to make sure this feature occurs between Insert cDNA start and end on resulting Vector sequence
					#print "cdna start " + `cdnaStart`
					#print "cdna end " + `cdnaEnd`

					# features must be between the cloning sites - this check remains!!!
					if fStart > fpEndPos and fEnd < tpEndPos:
						fType = f_tmp.getFeatureType()
						fVal =  f_tmp.getFeatureName()
						fiDir = f_tmp.getFeatureDirection()
						#print fType + ": " + fVal + ": " + `fStart` + "-" + `fEnd`
					
						fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
						rHandler.addReagentProperty(rID, fID, fVal, fStart, fEnd, fiDir)
					
						if f_tmp.getFeatureDescrType():
							fDescr = f_tmp.getFeatureDescrType()
							#print fDescr
							# Updated Sept. 2/08
							
							# Updated July 2/09
							# why twice?? removed july 2/09
							#rHandler.setReagentFeatureDescriptor(rID, prop_Name_ID_Map[fType], fVal, fDescr)
							rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fStart, fEnd, fDescr)

	elif subtype == 'gateway_expression':
		ipvFeatures = rHandler.findReagentSequenceFeatures(ipv_db_id)
		pvFeatures = rHandler.findReagentSequenceFeatures(pv_db_id)
			
		# Parent Vector features are found before cDNA start and after cDNA end on the new sequence
		for f in pvFeatures:
			fType = f.getFeatureType()
			#print fType
			
			if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
			
				# original feature positions on PV sequence
				pv_fStart = f.getFeatureStartPos()
				pv_fEnd = f.getFeatureEndPos()
			
				fVal = f.getFeatureName()
				#print fVal
				pv_fDir = f.getFeatureDirection()
				
				#print pv_fDir
				
				if pv_fStart > 0 and pv_fEnd > 0:
					
					# Features from PV are inherited IFF they are located **entirely** before the 5' cloning site or after 3' cloning site
					# Hence: May 22/08 - Find 5' start and 3' end on **original** PV sequence
					pv_fpcs_start = pvSequence.lower().find(fpSite)
					pv_tpcs_end = pvSequence.lower().find(tpSite) + len(tpSite)
					
					fSeq = pvSequence[pv_fStart-1:pv_fEnd].lower()
	
					# June 4/08 - Search for the feature IFF > 10 nts
					if len(fSeq) >= 10:
	
						# Look for this feature on the NEWly reconstituted Vector
						fIndex = newSeq.lower().find(fSeq)
	
						if fIndex >= 0:
							fpStart = fIndex + 1
							fpEnd = fpStart + len(fSeq) - 1
							
							#print "start " + `fpStart`
							#print "end " + `fpEnd`
	
							# If found, make sure this feature occurs either before or after the cDNA
							if (fpStart < fpStartPos and fpEnd < fpStartPos) or (fpStart >= tpEndPos and fpEnd > tpEndPos):
								
								# June 9/08: Orientation
								fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
								rHandler.addReagentProperty(rID, fID, fVal, fpStart, fpEnd, pv_fDir)
					
								if f.getFeatureDescrType():
									fDescr = f.getFeatureDescrName()
									#print fVal
									#print fDescr
									
									# Updated July 2/09
									rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fpStart, fpEnd, fDescr)
				
		# Search for each Insert feature on the new sequence
		# June 6/08: Added 'if' statement - needs testing
		for f in ipvFeatures:
			fType = f.getFeatureType()
			#print fType
			
			if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
				# feature positions on the Insert sequence - Modified May 21/08: Account for the fact that NOT the entire Insert sequence is used, only a subsequence
				fiStart = f.getFeatureStartPos()
				fiEnd = f.getFeatureEndPos()
				fVal = f.getFeatureName()
				#print fVal
				
				#fSeq = cdnaSeq[fiStart:fiEnd].lower()	# NO!!!!
				
				fSeq = ipvSequence[fiStart:fiEnd].lower()
			
				if len(fSeq) >= 10:
					fStart = newSeq.lower().find(fSeq)
					fEnd = fStart + len(fSeq)
				
					if fStart >= 1:
	
						# May 22/08: NO!!! This is precisely what we decided we're NOT going to do!!!
						'''
						## recompute based on cDNA start
						#fiStart = fiStart - insert_cdnaStart
						#fiEnd = fiEnd - insert_cdnaStart
						
						# feature positions on the new Vector sequence
						#fStart = cdnaStart + fiStart
						#fEnd = fStart + len(fSeq)
						'''
						
						# still, double check to make sure this feature occurs between cloning sites on resulting Vector sequence
						if fStart >= fpEndPos and fEnd <= tpStartPos:
							
							# June 9/08: Orientation
							fiDir = f.getFeatureDirection()
							
							fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fType], prop_Category_Name_ID_Map["DNA Sequence Features"])
							rHandler.addReagentProperty(rID, fID, fVal, fStart, fEnd, fiDir)
						
							if f.getFeatureDescrType():
								fDescr = f.getFeatureDescrType()
								
								# Updated July 2/09
								rHandler.setReagentFeatureDescriptor(rID, fID, fVal, fStart, fEnd, fDescr)
	
	utils.redirect(url)
	
	
def previewInsertFeatures(insertID, five_prime_site, three_prime_site, parents, sequence, seqID):
	
	#print "Content-type:text/html"
	#print
	#print sequence

	# Delete old properties before inserting new values - Updated July 9/09
	seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])
	rHandler.deleteReagentProperty(insertID, seqPropID)
	
	fpcs_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	tpcs_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
	rHandler.deleteReagentProperty(insertID, fpcs_prop_id)
	rHandler.deleteReagentProperty(insertID, tpcs_prop_id)
	
	# Sept. 8/08: Delete linkers too
	fp_linker_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	tp_linker_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
	rHandler.deleteReagentProperty(insertID, fp_linker_prop_id)
	rHandler.deleteReagentProperty(insertID, tp_linker_prop_id)
	
	# features too
	rHandler.deleteReagentFeatures(insertID)
	
	# Nov. 6/08 - Delete linkers separately, as they're not included in features list
	rHandler.deleteReagentProperty(insertID, fp_linker_prop_id)
	rHandler.deleteReagentProperty(insertID, tp_linker_prop_id)
	
	# Save sequence
	rHandler.addReagentProperty(insertID, seqPropID, seqID)
	
	# May 26/08: Check hybrid sites
	fpcs = utils.make_array(five_prime_site)
	tpcs = utils.make_array(three_prime_site)
	
	if sHandler.isHybrid(fpcs):
		fp_seq = sHandler.hybridSeq(fpcs)
	else:
		if len(five_prime_site) > 0:
			fp_seq = enzDict[five_prime_site].lower()
		else:
			fp_seq = ""

	if sHandler.isHybrid(tpcs):
		tp_seq = sHandler.hybridSeq(tpcs)
		
	else:
		if len(three_prime_site) > 0:
			tp_seq = enzDict[three_prime_site].lower()
		else:
			tp_seq = ""
			
	#print fp_seq
	
	# Removed March 8/10
	# Start positions from 1 to make them human-readable
	# Nov. 4/08: Use old strategy for SfiI
	#if five_prime_site == "SfiI" and three_prime_site == "SfiI":
		#m = re.findall("GGCC.....GGCC", sequence.upper())	# m is a LIST
		
		#if len(m) > 0:
			#fp_seq = m[0].lower()
			#tp_seq = m[1].lower()
			
			#if len(fp_seq) > 0 and sequence.find(fp_seq) >= 0:
				#five_start_pos = sequence.index(fp_seq) + 1
				#five_end_pos = five_start_pos + len(fp_seq) - 1
			#else:
				#five_start_pos = 0
				#five_end_pos = 0
			
			#if len(tp_seq) > 0 and sequence.rfind(tp_seq) >= 0:
				#three_start_pos = sequence.rfind(tp_seq) + 1
				#three_end_pos = three_start_pos + len(tp_seq) - 1
			#else:
				#three_start_pos = 0
				#three_end_pos = 0
		#else:
			#five_start_pos = 0
			#five_end_pos = 0
			
			#three_start_pos = 0
			#three_end_pos = 0
	#else:
	if len(fp_seq) > 0 and sequence.find(fp_seq) >= 0:
		five_start_pos = sequence.index(fp_seq) + 1
		five_end_pos = five_start_pos + len(fp_seq) - 1
	else:
		# Dec. 17/08
		# reason could be that the site is a degenerate - try one more time with BioPython
		tmpSeq = Bio.Seq.Seq(sequence)
		
		# Jan. 6/09: Gateway sites are not in enzDict
		if sHandler.enzDict.has_key(five_prime_site):
			fp_cs = sHandler.enzDict[five_prime_site]
			
			# dec. 18/08
			fp_seq = fp_cs.elucidate().replace("_", "")
			fp_clvg = fp_seq.find("^")
			fp_flank = fp_seq[0:fp_clvg]
			
			tmp_fp_pos = fp_cs.search(tmpSeq)
			tmp_fp_pos.sort()
			
			if len(tmp_fp_pos) > 0:	# Jan. 7/09: added > 0
				five_start_pos = tmp_fp_pos[0] - len(fp_flank)
				five_end_pos = five_start_pos + len(fp_cs.site) - 1	# Jan. 7/09: DO NOT use fp_seq; it contains the extra ^ char!!!
				
				if five_start_pos == 0:
					five_start_pos = 0
					five_end_pos = 0
			else:
				five_start_pos = 0
				five_end_pos = 0
		# Jan. 6/09: maybe a gateway site is not found
		else:
			five_start_pos = 0
			five_end_pos = 0
	
	# 3' site: Regular search, no need to reverse complement
	#print tp_seq
	# Nov. 4/08: BUT search from END of sequence!!!!
	if len(tp_seq) > 0 and sequence.rfind(tp_seq) >= 0:
		three_start_pos = sequence.rfind(tp_seq) + 1
		#print three_start_pos
		three_end_pos = three_start_pos + len(tp_seq) - 1
		#print three_end_pos
	else:
		# Dec. 17/08
		# reason could be that the site is a degenerate - try one more time with BioPython
		tmpSeq = Bio.Seq.Seq(sequence)
		
		# Jan. 6/09: Gateway sites are not in enzDict
		if sHandler.enzDict.has_key(three_prime_site):
			tp_cs = sHandler.enzDict[three_prime_site]
			
			# dec. 18/08
			tp_seq = tp_cs.elucidate().replace("_", "")
			tp_clvg = tp_seq.find("^")
			tp_flank = tp_seq[0:tp_clvg]

			tmp_tp_pos = tp_cs.search(tmpSeq)
			tmp_tp_pos.sort()

			if len(tmp_tp_pos) > 0:
				three_start_pos = tmp_tp_pos[len(tmp_tp_pos)-1] - len(tp_flank)
				three_end_pos = three_start_pos + len(tp_cs.site) - 1 	# Jan. 7/09: DO NOT use fp_seq; it contains the extra ^ char!!!
				
				if three_start_pos == 0:
					three_start_pos = 0
					three_end_pos = 0
			else:
				three_start_pos = 0
				three_end_pos = 0
		else:
			# Jan. 6/09: Could simply be that a gateway site is not found in the sequence
			three_start_pos = 0
			three_end_pos = 0	
			
	#print five_start_pos
	#print five_end_pos
	
	#print three_start_pos
	#print three_end_pos
	
	# Insert new values for sites
	rHandler.addReagentProperty(insertID, fpcs_prop_id, five_prime_site, five_start_pos, five_end_pos)
	rHandler.addReagentProperty(insertID, tpcs_prop_id, three_prime_site, three_start_pos, three_end_pos)
	
	insertParentVector = parents["insert parent vector id"]
	senseOligo = parents["sense oligo"]
	antisenseOligo = parents["antisense oligo"]
	
	utils.redirect(hostname + "Reagent.php?View=2&Step=2&Type=Insert&rID=" + `insertID` + "&Seq=" + `seqID` + "&PIV=" + insertParentVector + "&SO=" + senseOligo + "&AS=" + antisenseOligo)
	

# Updated Sept. 8/08: Restrict parent access by project
#def previewInsertSequence(insertID, parents):
def previewInsertSequence(insertID, parents, currUser, uPackets):
	
	#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
	#print					# DITTO
	#print `uPackets`
	
	# Delete any previous information for this reagent - for Inserts it's sequence and cloning sites - update July 9/09
	seqPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])
	rHandler.deleteReagentProperty(insertID, seqPropID)
	
	# July 9/09
	fpcs_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	tpcs_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' cloning site"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
	# July 9/09
	fp_linker_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	tp_linker_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
	# update July 9/09
	rHandler.deleteReagentProperty(insertID, fpcs_prop_id)
	rHandler.deleteReagentProperty(insertID, tpcs_prop_id)
	
	# Get parent values
	senseOligo = parents["sense oligo"]
	antisenseOligo = parents["antisense oligo"]
	insertParentVector = parents["insert parent vector id"]
	
	# Store associations - Delete previous entries first!!!
	assocID = raHandler.findReagentAssociationID(insertID)
	
	if assocID <= 0:
		assocID = rHandler.createReagentAssociation(insertID, reagentType_Name_ID_Map["Insert"])
	
	senseAssocProp = aHandler.findAssocPropID("sense oligo")
	antisenseAssocProp = aHandler.findAssocPropID("antisense oligo")
	ipvAssocProp = aHandler.findAssocPropID("insert parent vector id")
	
	#packetPropID = pHandler.findPropID("packet id")
	packetPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["packet id"], prop_Category_Name_ID_Map["General Properties"])
	
	# Sept. 8/08: Check each of the parent projects separately
	# If a parent belongs to a project the creator does not have *read* access to, disallow creation
	try:
		# sense oligo
		if (len(senseOligo) > 0):
			try:
				senseID = rHandler.convertReagentToDatabaseID(senseOligo)
				senseProjectID = int(rHandler.findSimplePropertyValue(senseID, packetPropID))		# need to cast
				#print "Sense " + `senseProjectID`
				
				if currUser.getCategory() != 'Admin' and senseProjectID not in uPackets:
					i = SenseProjectAccessException("You do not have read access to this project")
					
					# Navigate to error page ("AP" stands for "Association Property")
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `insertID` + "&Type=Insert&SO=" + senseOligo + "&AS=" + antisenseOligo + "&PIV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `senseAssocProp`)
					
			except ReagentDoesNotExistException:
					i = ReagentDoesNotExistException("Non-existing parent")

					# Navigate to error page ("AP" stands for "Association Property")
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `insertID` + "&Type=Insert&SO=" + senseOligo + "&AS=" + antisenseOligo + "&PIV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `senseAssocProp`)
		
		# antisense oligo
		if (len(antisenseOligo) > 0):
			
			try:
				antisenseID = rHandler.convertReagentToDatabaseID(antisenseOligo)
				antisenseProjectID = int(rHandler.findSimplePropertyValue(antisenseID, packetPropID))	# need to cast
				#print "Antiense " + `antisenseProjectID`
				
				if currUser.getCategory() != 'Admin' and antisenseProjectID not in uPackets:
					i = AntisenseProjectAccessException("You do not have read access to this project")
					
					# Navigate to error page ("AP" stands for "Association Property")
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `insertID` + "&Type=Insert&SO=" + senseOligo + "&AS=" + antisenseOligo + "&PIV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `senseAssocProp`)
			
			except ReagentDoesNotExistException:
				i = ReagentDoesNotExistException("Non-existing parent")
	
				# Navigate to error page ("AP" stands for "Association Property")
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `insertID` + "&Type=Insert&SO=" + senseOligo + "&AS=" + antisenseOligo + "&PIV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `senseAssocProp`)
			
		# IPV
		if (len(insertParentVector) > 0):
			
			try:
				ipvID = rHandler.convertReagentToDatabaseID(insertParentVector)
				ipvProjectID = int(rHandler.findSimplePropertyValue(ipvID, packetPropID))		# need to cast
				#print "IPV " + `ipvProjectID`
					
				if currUser.getCategory() != 'Admin' and ipvProjectID not in uPackets:
					i = IPVProjectAccessException("You do not have read access to this project")
					
					# Navigate to error page ("AP" stands for "Association Property")
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `insertID` + "&Type=Insert&SO=" + senseOligo + "&AS=" + antisenseOligo + "&PIV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `senseAssocProp`)
					
			except ReagentDoesNotExistException:
				i = ReagentDoesNotExistException("Non-existing parent")
				
				# Navigate to error page ("AP" stands for "Association Property")
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `insertID` + "&Type=Insert&SO=" + senseOligo + "&AS=" + antisenseOligo + "&PIV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `senseAssocProp`)	
		
	except (TypeError, ValueError, IndexError):
		# what to do here? throw an Invalid parent project exception?
		parentProjectID = 0
	
	
	# otherwise store parent values
	for assocName in parents:
		try:
			parentValue = rHandler.convertReagentToDatabaseID(parents[assocName])
			assocPropID = aHandler.findAssocPropID(assocName)
				
			# first delete, then insert
			rHandler.deleteReagentAssociationProp(insertID, assocPropID)
			rHandler.addAssociationValue(insertID, assocPropID, parentValue, assocID)

		except ReagentDoesNotExistException:
			i = ReagentDoesNotExistException("Parent does not exist")
			utils.redirect(hostname + "Reagent.php?View=2&rID=" + `insertID` + "&Step=1&Type=Insert&PIV=" + insertParentVector + "&SO=" + senseOligo + "&AS=" + antisenseOligo + "&Err=" + `i.err_code()`)
			
	utils.redirect(hostname + "Reagent.php?View=2&rID=" + `insertID` + "&Step=1&Type=Insert&PIV=" + insertParentVector + "&SO=" + senseOligo + "&AS=" + antisenseOligo)
	
	
# Upload selected properties at Vector or Cell Line creation
def preload():
	
	form = cgi.FieldStorage()
	
	#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
	#print					# DITTO
	#print `form`
	
	if form.has_key("curr_username"):
		# store the user ID for use throughout the session; add to other views in addition to create in PHP
		currUname = form.getvalue("curr_username")
		currUser = uHandler.getUserByDescription(currUname)
		Session.setUser(currUser)
	
	uPackets = getCurrentUserProjects(currUser)
	
	parents = {}
	
	# Initialize them both to False, but remember the difference! (see note on previewVectorSequence() function)
	reverse_complement = False	# Jan. 21/09
	reverse_insert = False		# Jan. 21/09

	if form.has_key("preload"):
		rType = form.getvalue("reagentType")
		
		# April 14/08: Create a new Reagent instance - IFF not returning to this view from a later page to update parents!!!
		if form.has_key("reagent_id_hidden"):
			rID = int(form.getvalue("reagent_id_hidden"))
		else:
			rID = rHandler.createNewReagent(rType)
		
		#print `rID`

		reagent = rHandler.createReagent(rID)
		
		if rType == 'Vector':
			subtype = form.getvalue("vectorSubtype")
			reagent.setSubtype(subtype)
			
			# select parents according to subtype
			if subtype == 'nonrecomb' or subtype == 'gateway_entry':
				
				pvAssocProp = aHandler.findAssocPropID("vector parent id")
				insertAssocProp = aHandler.findAssocPropID("insert id")
				
				parentVectorID = form.getvalue("nr_parent_vector").strip()	# human-readable (e.g. V123)
				insertID = form.getvalue("insert_id").strip()
				
				# April 28/08: Assign associations - on db level only, no benefit in creating Reagent objects for parents
				try:
					pv_db_id = rHandler.convertReagentToDatabaseID(parentVectorID)		# internal db ID
				
				except ReagentDoesNotExistException:
					i = ReagentDoesNotExistException("Parent Vector ID not found in database")
					
					# "AP" stands for "Association Property"
					# April 30/08: Added rID parameter
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&AP=" + `pvAssocProp`)
				
				try:
					insert_db_id = rHandler.convertReagentToDatabaseID(insertID)
		
					parents["vector parent id"] = pv_db_id
					parents["insert id"] = insert_db_id
	
					# Nov. 19/08
					sites = []
					
					fpcs = ""
					tpcs = ""
					
					if form.has_key("custom_sites"):
						insert_fp_custom = form.getvalue("insert_custom_five_prime")
						insert_tp_custom = form.getvalue("insert_custom_three_prime")
						
						pv_fp_custom = form.getvalue("pv_custom_five_prime")
						pv_tp_custom = form.getvalue("pv_custom_three_prime")
						
						if insert_fp_custom == pv_fp_custom:
							fpcs = pv_fp_custom
						else:
							fpcs = pv_fp_custom + "-" + insert_fp_custom
					
						if insert_tp_custom == pv_tp_custom:
							tpcs = pv_tp_custom
						else:
							tpcs = insert_tp_custom + "-" + pv_tp_custom 
					
						#print fpcs
						#print tpcs
					
						sites.append(fpcs)
						sites.append(tpcs)
					
					# Jan. 21/09
					if form.has_key("reverse_complement"):
						reverse_complement = True
					
					#print `reverse_complement`
					#print "????????" + `sites`
					previewVectorSequence(rID, reagent, parents, currUser, db, cursor, sites, False, reverse_complement)
					
				except ReagentDoesNotExistException:
					i = ReagentDoesNotExistException("Insert ID not found in database")
					
					#"AP" stands for "Association Property"
					# April 30/08: Added rID parameter
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&AP=" + `insertAssocProp`)
			
			elif subtype == 'recomb' or subtype == 'gateway_expression':
				
				# Association-related db field names - declare here to be visible by exception handling
				pvAssocProp = aHandler.findAssocPropID("vector parent id")
				ipvAssocProp = aHandler.findAssocPropID("parent insert vector")
				insertAssocProp = aHandler.findAssocPropID("insert id")
				
				parentVectorID = form.getvalue("rec_parent_vector").strip()	# human-readable (e.g. V123)
				insertParentVectorID = form.getvalue("insert_parent_vector").strip()
				
				# April 28/08: Assign associations - on db level only, no benefit in creating Reagent objects for parents
				try:
					pv_db_id = rHandler.convertReagentToDatabaseID(parentVectorID)		# internal db ID
					
				except ReagentDoesNotExistException:
					i = ReagentDoesNotExistException("Parent Vector ID not found in database")
					
					# "AP" stands for "Association Property"
					# April 30/08: Added rID parameter
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + insertParentVectorID + "&Err=" + `i.err_code()` + "&AP=" + `pvAssocProp`)
				
				try:
					ipv_db_id = rHandler.convertReagentToDatabaseID(insertParentVectorID)
					
					parents["vector parent id"] = pv_db_id
					parents["parent insert vector"] = ipv_db_id
	
					# May 8/08: Find the Insert that belongs to IPV and pass it to previewSequence along with PV and IPV
					ipvInsertAssocID = raHandler.findReagentAssociationID(ipv_db_id)
					ipvAssocPropID = aHandler.findAssocPropID("parent insert vector")
					insertAssocPropID = aHandler.findAssocPropID("insert id")
					
					try:
						insert_db_id = aHandler.findAssocPropValue(ipvInsertAssocID, insertAssocPropID)
						
						if insert_db_id > 0:
							parents["insert id"] = insert_db_id
	
						previewVectorSequence(rID, reagent, parents, currUser, db, cursor)
					
					except ReagentDoesNotExistException:
						i = ReagentDoesNotExistException("Insert Parent Vector ID not found in database")
						
						#"AP" stands for "Association Property"
						# April 30/08: Added rID parameter
						utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&AP=" + `insertAssocProp`)
				
				except ReagentDoesNotExistException:
					i = ReagentDoesNotExistException("Insert Parent Vector ID not found in database")
					
					#"AP" stands for "Association Property"
					# April 30/08: Added rID parameter
					# May 30/08: For simplicity. redirect to a common error page for non-existing IPV or lack of Insert for that IPV
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&IPV=" + insertParentVectorID + "&Err=" + `i.err_code()` + "&AP=" + `ipvAssocProp`)
				
		elif rType == 'CellLine':
			#print "Content-type:text/html"
			#print
			
			subtype = form.getvalue('cellLineSubtype')
			parents = []

			if subtype == 'stable_cell_line':
				
				# load properties from cell line and vector parents
				parentVector = form.getvalue('cell_line_parent_vector').strip()
				parentCellLine = form.getvalue('parent_cell_line').strip()
				
				parents.append(parentVector)
				parents.append(parentCellLine)
				
				previewCellLineProperties(rID, rType, subtype, parents, currUser, db, cursor)
			
				redirect(hostname + "Reagent.php?View=2&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&CL=" + parentCellLine)
		
		elif rType == 'Insert':
			# Removed Jan. 6/09: Was generating a new insert ID twice
			## April 14/08: Create a new Reagent instance - IFF not returning to this view from a later page to update parents!!!
			#if form.has_key("reagent_id_hidden"):
				#insertID = int(form.getvalue("reagent_id_hidden"))
			#else:
				#insertID = rHandler.createNewReagent("Insert")
				
			parents = {}
			
			if form.has_key("insert_parent_vector"):
				insertParentVector = form.getvalue("insert_parent_vector")
			else:
				insertParentVector = ""
			
			if form.has_key("sense_oligo"):
				senseOligo = form.getvalue("sense_oligo")
			else:
				senseOligo = ""
				
			if form.has_key("antisense_oligo"):
				antisenseOligo = form.getvalue("antisense_oligo")
			else:
				antisenseOligo = ""
			
			parents["insert parent vector id"] = insertParentVector
			parents["sense oligo"] = senseOligo
			parents["antisense oligo"] = antisenseOligo
			
			# Updated Jan. 6/09: Was generating a new insert ID twice
			#previewInsertSequence(insertID, parents, currUser, uPackets)
			previewInsertSequence(rID, parents, currUser, uPackets)

	# Case 2: Press "Change Parents" button
	elif form.has_key("change_parents"):
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		rType = form.getvalue("reagent_type_hidden")
		subtype = form.getvalue("subtype_hidden")
		
		prefix = "reagent_detailedview_"
		postfix = "_prop"
	
		# Nov. 11/08: Create a new Reagent instance - IFF not returning to this view from a later page to update parents!!!
		if form.has_key("reagent_id_hidden"):
			rID = int(form.getvalue("reagent_id_hidden"))
		else:
			rID = rHandler.createNewReagent(rType)
		
		if rType == 'Vector':
		
			# get parent values depending on subtype
			parents = {}
			
			if subtype == 'nonrecomb' or subtype == 'gateway_entry':

				# get parent vector and insert
				parentVectorID = form.getvalue(prefix + "parent_vector_id" + postfix).strip()
				insertID = form.getvalue(prefix + "insert_id" + postfix).strip()

				pvAssocProp = aHandler.findAssocPropID("vector parent id")
				insertAssocProp = aHandler.findAssocPropID("insert id")
			
				#parents.append(parentVectorID)
				#parents.append(insertID)
				
				try:
					pv_db_id = rHandler.convertReagentToDatabaseID(parentVectorID)		# internal db ID
				
				except ReagentDoesNotExistException:
					i = ReagentDoesNotExistException("Parent Vector ID not found in database")
					
					# "AP" stands for "Association Property"
					# April 30/08: Added rID parameter
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&AP=" + `pvAssocProp`)
				
				try:
					insert_db_id = rHandler.convertReagentToDatabaseID(insertID)
			
					# If Insert is ok, update the reagent's existing association values and proceed to preview
					assocDict = {}
					
					pv_assoc_alias = assoc_Name_Alias_Map["vector parent id"]
					insert_assoc_alias = assoc_Name_Alias_Map["insert id"]
					
					assocDict[pv_assoc_alias] = pv_db_id
					assocDict[insert_assoc_alias] = insert_db_id
				
					rHandler.updateReagentAssociations(rID, assocDict)
					
					parents["vector parent id"] = pv_db_id
					parents["insert id"] = insert_db_id
					
				except ReagentDoesNotExistException:
					i = ReagentDoesNotExistException("Insert ID not found in database")
					
					#"AP" stands for "Association Property"
					# April 30/08: Added rID parameter
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&AP=" + `insertAssocProp`)
					
			elif subtype == 'recomb' or subtype == 'gateway_expression':
				
				parentVector = form.getvalue(prefix + "parent_vector_id" + postfix).strip()
				insertParentVector = form.getvalue(prefix + "insert_parent_vector" + postfix).strip()

				# change ASAP
				#parents.append(parentVector)
				#parents.append(insertParentVector)

			else:
				raise Exception("Unknown Vector subtype")

			previewVectorSequence(rID, reagent, parents, currUser, db, cursor)
			
		# Added Sept. 8/08
		elif rType == "Insert":
			#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
			#print					# DITTO
			#print "1"
			
			if form.has_key("reagent_id_hidden"):
				insertID = int(form.getvalue("reagent_id_hidden"))
			else:
				insertID = rHandler.createNewReagent("Insert")
				
			parents = {}
			
			if form.has_key("insert_parent_vector"):
				insertParentVector = form.getvalue("insert_parent_vector")
			else:
				insertParentVector = ""
			
			if form.has_key("sense_oligo"):
				senseOligo = form.getvalue("sense_oligo")
			else:
				senseOligo = ""
				
			if form.has_key("antisense_oligo"):
				antisenseOligo = form.getvalue("antisense_oligo")
			else:
				antisenseOligo = ""
			
			parents["insert parent vector id"] = insertParentVector
			parents["sense oligo"] = senseOligo
			parents["antisense oligo"] = antisenseOligo
			
			previewInsertSequence(insertID, parents, currUser, uPackets)

		elif rType == 'CellLine':
			prefix = "INPUT_CELLLINE_info_"
			postfix = "_prop"

			# load properties from cell line and vector parents
			parentVector = form.getvalue(prefix + "vector_id" + postfix).strip()
			parentCellLine = form.getvalue(prefix + "cellline_id" + postfix).strip()
		
			currReadProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Reader')
			currWriteProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Writer')
			publicProj = packetHandler.findAllProjects(isPrivate="FALSE")
			
			# list of Packet OBJECTS
			currUserWriteProjects = utils.unique(currReadProj + currWriteProj + publicProj)
			
			uPackets = []
			
			for p in currUserWriteProjects:
				uPackets.append(p.getNumber())
				
			# Get project IDs of parents
			#packetPropID = pHandler.findPropID("packet id")
			packetPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["packet id"], prop_Category_Name_ID_Map["General Properties"])
			
			# parent vector
			try:
				pv_db_id = rHandler.convertReagentToDatabaseID(parentVector)
				pvProjectID = int(rHandler.findSimplePropertyValue(pv_db_id, packetPropID))	# need to cast
				
				if currUser.getCategory() != 'Admin' and pvProjectID not in uPackets:
				
					i = PVProjectAccessException("You do not have read access to this project")
			
					clpvAssocProp = aHandler.findAssocPropID("cell line parent vector id")
	
					utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clpvAssocProp`)
					
				else:
					
					# parent vector ok, check cell line
					try:
						cl_db_id = rHandler.convertReagentToDatabaseID(parentCellLine)
						
						# Use 'try' again to capture the project ID
						try:
							clProjectID = int(rHandler.findSimplePropertyValue(cl_db_id, packetPropID))
							
							if currUser.getCategory() != 'Admin' and clProjectID > 0 and clProjectID not in uPackets:
								i = CLProjectAccessException("You do not have read access to this project")
						
								clAssocProp = aHandler.findAssocPropID("parent cell line id")
				
								utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clAssocProp`)
							
							else:
								# user may view project, proceed
								utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&CL=" + parentCellLine)
						
						except (TypeError, ValueError, IndexError):
							
						#elif (clProjectID == None or clProjectID <= 0) and currUser.getCategory() != 'Admin':
							
							i = CLProjectAccessException("You do not have read access to this project")
					
							clAssocProp = aHandler.findAssocPropID("parent cell line id")
			
							utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clAssocProp`)
							
						#else:
							# everything ok, proceed
							#redirect(hostname + "Reagent.php?View=2&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&CL=" + parentCellLine)
			
					except ReagentDoesNotExistException:
						i = ReagentDoesNotExistException("Parent Cell Line ID not found in database")
						
						# get "parent cell line" association type
						clAssocProp = aHandler.findAssocPropID("parent cell line id")
						
						# "AP" stands for "Association Property"
						utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clAssocProp`)
					
			except ReagentDoesNotExistException:
				i = ReagentDoesNotExistException("Parent Vector ID not found in database")
				
				# get "parent vector" association type
				clpvAssocProp = aHandler.findAssocPropID("cell line parent vector id")
				
				# "AP" stands for "Association Property"
				utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clpvAssocProp`)

	# added April 28/08
	elif form.has_key("confirm_features"):
		
		rType = form.getvalue("reagent_type_hidden")

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`

		reverse = False				# Jan. 21/09

		# April 14/08: Create a new Reagent instance - IFF not returning to this view from a later page to update parents!!!
		if form.has_key("reagent_id_hidden"):
			rID = int(form.getvalue("reagent_id_hidden"))
		else:
			rID = rHandler.createNewReagent(rType)
		
		reagent = rHandler.createReagent(rID)
		
		prefix = "reagent_detailedview_"
		postfix = "_prop"
	
		parents = {}
		
		seqID = -1
		sequence = ""
		
		# Fetch the old sequence of the reagent for feature remapping - non-Novel vectors
		if form.has_key(prefix + "sequence" + postfix):
			sequence = utils.squeeze(form.getvalue(prefix + "sequence" + postfix).strip())
			
			# Jan. 6/09: Store Insert in lowercase
			if rType == "Insert":
				sequence = sequence.lower()

			seqID = sHandler.matchSequence(sequence)
			
			if seqID <= 0:
				seqID = sHandler.insertSequence(sequence)
				
		if form.has_key("seq_id_hidden"):
			oldSeqID = int(form.getvalue("seq_id_hidden"))
			
		else:
			oldSeqID = seqID
			
		if rType == "Vector":
			
			sites = {}	# nov. 18/08

			subtype = form.getvalue("subtype_hidden")
			reagent.setSubtype(subtype)

			if subtype == 'nonrecomb' or subtype == 'gateway_entry':
			
				if form.has_key(prefix + assoc_Name_Alias_Map["vector parent id"] + postfix) and form.has_key(prefix + assoc_Name_Alias_Map["insert id"] + postfix):
					
					pv_db_id = int(form.getvalue(prefix + assoc_Name_Alias_Map["vector parent id"] + postfix).strip())
					insert_db_id = int(form.getvalue(prefix + assoc_Name_Alias_Map["insert id"] + postfix).strip())
					
					parents["vector parent id"] = pv_db_id
					parents["insert id"] = insert_db_id
					
				# Nov. 18/08
				if form.has_key(prefix + prop_Name_Alias_Map["5' cloning site"] + postfix):
					fpcs = form.getvalue(prefix + prop_Name_Alias_Map["5' cloning site"] + postfix)
					sites["5' cloning site"] = fpcs
					
				if form.has_key(prefix + prop_Name_Alias_Map["3' cloning site"] + postfix):
					tpcs = form.getvalue(prefix + prop_Name_Alias_Map["3' cloning site"] + postfix)
					sites["3' cloning site"] = tpcs
					
			elif subtype == 'recomb' or subtype == 'gateway_expression':

				if form.has_key(prefix + assoc_Name_Alias_Map["vector parent id"] + postfix) and form.has_key(prefix + assoc_Name_Alias_Map["parent insert vector"] + postfix):
					
					pv_db_id = int(form.getvalue(prefix + assoc_Name_Alias_Map["vector parent id"] + postfix).strip())
					ipv_db_id = int(form.getvalue(prefix + assoc_Name_Alias_Map["parent insert vector"] + postfix).strip())
					
					parents["vector parent id"] = pv_db_id
					parents["parent insert vector"] = ipv_db_id
					
					# May 8/08: Find the Insert that belongs to IPV and pass it to previewSequence along with PV and IPV
					ipvInsertAssocID = raHandler.findReagentAssociationID(ipv_db_id)
					insertAssocPropID = aHandler.findAssocPropID("insert id")
					insert_db_id = aHandler.findAssocPropValue(ipvInsertAssocID, insertAssocPropID)
					
					if insert_db_id > 0:
						parents["insert id"] = insert_db_id
			else:
				parents = []
			
			if form.has_key("reverse_complement") and form.getvalue("reverse_complement") == '1':
				reverse = True
				
			#print form.getvalue("reverse_complement")
			#print "rev " + `reverse`
			
			previewVectorFeatures(rID, reagent, parents, oldSeqID, seqID, sequence, currUser, db, cursor, sites, reverse)
			
		elif rType == 'Insert':
			parents = {}
			
			if form.has_key(prefix + assoc_Name_Alias_Map["insert parent vector id"] + postfix):
				insertParentVector = form.getvalue(prefix + assoc_Name_Alias_Map["insert parent vector id"] + postfix)
			else:
				insertParentVector = ""
				
				#ipv_db_id = rHandler.convertReagentToDatabaseID(insertParentVector)
				
			if form.has_key(prefix + assoc_Name_Alias_Map["sense oligo"] + postfix):
				senseOligo = form.getvalue(prefix + assoc_Name_Alias_Map["sense oligo"] + postfix)
			else:
				senseOligo = ""
				#sense_id = rHandler.convertReagentToDatabaseID(senseOligo)
				
			if form.has_key(prefix + assoc_Name_Alias_Map["antisense oligo"] + postfix):
				antisenseOligo = form.getvalue(prefix + assoc_Name_Alias_Map["antisense oligo"] + postfix)
			else:
				antisenseOligo = ""
				#antisense_id = rHandler.convertReagentToDatabaseID(antisenseOligo)
			
			parents["insert parent vector id"] = insertParentVector
			parents["sense oligo"] = senseOligo
			parents["antisense oligo"] = antisenseOligo
			
			# Find or assign an ID to the sequence provided
			if form.has_key(prefix + prop_Name_Alias_Map["sequence"] + postfix):
				sequence = utils.squeeze(form.getvalue(prefix + prop_Name_Alias_Map["sequence"] + postfix).strip().lower())	# Aug. 17/09: filter whitespaces
			else:
				sequence = ""
			
			seqID = sHandler.getSequenceID(sequence)
			
			# Grab cloning sites
			five_prime_site = form.getvalue(prefix + prop_Name_Alias_Map["5' cloning site"] + postfix)
			
			if five_prime_site == "Other":
				five_prime_site = form.getvalue("5_prime_cloning_site_name_txt")
			
			three_prime_site = form.getvalue(prefix + prop_Name_Alias_Map["3' cloning site"] + postfix)
			
			if three_prime_site == "Other":
				three_prime_site = form.getvalue("3_prime_cloning_site_name_txt")

			previewInsertFeatures(rID, five_prime_site, three_prime_site, parents, sequence, seqID)
	
	elif form.has_key("confirm_intro"):
		
		# Save all incoming features and redirect to final creation step
		rType = form.getvalue("reagent_type_hidden")
		rTypeID = reagentType_Name_ID_Map[rType]
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `prop_Alias_Name_Map`
		#print `form`
		
		prefix = "reagent_detailedview_"
		postfix = "_prop"
		
		rID = int(form.getvalue("reagent_id_hidden"))
		reagent = rHandler.createReagent(rID)
		
		# get sequence for redirection
		seqID = rHandler.findDNASequenceKey(rID)
		
		reverse = False		# Jan. 22/09

		if rType == 'Vector':
			
			subtype = form.getvalue("subtype_hidden")
			reagent.setSubtype(subtype)

			if subtype == 'nonrecomb' or subtype == 'gateway_entry':
				
				# Jan. 22/09
				if form.has_key("reverse_complement") and form.getvalue("reverse_complement") == '1':
					reverse = True
			
				# define here for the sake of error handling
				pvAssocAlias = assoc_Name_Alias_Map["vector parent id"]
				insertAssocAlias = assoc_Name_Alias_Map["insert id"]
				
				#print prefix + pvAssocAlias + postfix
				#print prefix + insertAssocAlias + postfix
				
				if form.has_key(prefix + pvAssocAlias + postfix) and form.has_key(prefix + insertAssocAlias + postfix):
					
					parentVectorID = form.getvalue(prefix + pvAssocAlias + postfix).strip()
					#print `parentVectorID`
					insertID = form.getvalue(prefix + insertAssocAlias + postfix).strip()
					
					pv_db_id = int(rHandler.convertReagentToDatabaseID(parentVectorID))
					insert_db_id = int(rHandler.convertReagentToDatabaseID(insertID))
				
				url = hostname + "Reagent.php?View=2&Step=3&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + `pv_db_id` + "&I=" + `insert_db_id` + "&Seq=" + `seqID` + "&Rev=" + `reverse`
				
			elif subtype == 'recomb' or subtype == 'gateway_expression':
				# define here for the sake of error handling
				pvAssocAlias = assoc_Name_Alias_Map["vector parent id"]
				ipvAssocAlias = assoc_Name_Alias_Map["parent insert vector"]

				#ipvInsertAssocID = raHandler.findReagentAssociationID(ipv_db_id)
				
				if form.has_key(prefix + pvAssocAlias + postfix) and form.has_key(prefix + ipvAssocAlias + postfix):
					
					parentVectorID = form.getvalue(prefix + pvAssocAlias + postfix).strip()
					ipvID = form.getvalue(prefix + ipvAssocAlias + postfix).strip()
					
					pv_db_id = int(rHandler.convertReagentToDatabaseID(parentVectorID))
					ipv_db_id = int(rHandler.convertReagentToDatabaseID(ipvID))
				
				url = hostname + "Reagent.php?View=2&Step=3&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + `pv_db_id` + "&IPV=" + `ipv_db_id` + "&Seq=" + `seqID`
			
			else:
				# Novel
				url = hostname + "Reagent.php?View=2&Step=3&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&Seq=" + `seqID`
				
		elif rType == 'Insert':
			
			pivAssocAlias = assoc_Name_Alias_Map["insert parent vector id"]
			senseAssocAlias = assoc_Name_Alias_Map["sense oligo"]
			antisenseAssocAlias = assoc_Name_Alias_Map["antisense oligo"]
			
			insertParentVector = ""
			senseOligo = ""
			antisenseOligo = ""
			
			if form.has_key("from_primer") and form.getvalue("from_primer") == 'True':
				insertAssoc = reagent.getAssociations()
				
				senseOligo = rHandler.convertDatabaseToReagentID(insertAssoc["sense oligo"])
				antisenseOligo = rHandler.convertDatabaseToReagentID(insertAssoc["antisense oligo"])
				
				# PIV is optional at primer design
				insertParentVector = rHandler.convertDatabaseToReagentID(insertAssoc["insert parent vector id"])
				
				if insertParentVector == "":
					#print prefix + pivAssocAlias + postfix
					
					if form.has_key(prefix + pivAssocAlias + postfix):
						insertParentVector = form.getvalue(prefix + pivAssocAlias + postfix)
						ipv_db_id = rHandler.convertReagentToDatabaseID(insertParentVector)
						
						pivAssocProp = aHandler.findAssocPropID("insert parent vector id")
					
						assocID = raHandler.findReagentAssociationID(rID)
						
						rHandler.deleteReagentAssociationProp(rID, pivAssocProp)
						rHandler.addAssociationValue(rID, pivAssocProp, ipv_db_id, assocID)
		
				url = hostname + "Reagent.php?View=2&Step=5&rID=" + `rID` + "&Type=" + rType + "&SO=" + senseOligo + "&AS=" + antisenseOligo + "&PIV=" + insertParentVector + "&Seq=" + `seqID`
		
			else:
				if form.has_key(prefix + pivAssocAlias + postfix):
					insertParentVector = form.getvalue(prefix + pivAssocAlias + postfix)
					
				if form.has_key(prefix + senseAssocAlias + postfix):
					senseOligo = form.getvalue(prefix + senseAssocAlias + postfix)
				
				if form.has_key(prefix + antisenseAssocAlias + postfix):
					antisenseOligo = form.getvalue(prefix + antisenseAssocAlias + postfix)
					
				url = hostname + "Reagent.php?View=2&Step=3&rID=" + `rID` + "&Type=" + rType + "&PIV=" + insertParentVector + "&SO=" + senseOligo + "&AS=" + antisenseOligo + "&Seq=" + `seqID`
		
		
		# Save features - taken from 'update.py'
		# (deletion is performed in updateFeatures function, no need to call here)
		newPropsDict_name = {}			# e.g. ('status', 'Completed')
		newPropsDict_id = {}			# e.g. ('3', 'Completed') - db ID instead of property name
		
		startPosDict = {}			# (propID, startpos)
		endPosDict = {}				# (propID, endpos)
		
		# Store orientation
		orientationDict = {}			# (propID, orientation)
		
		# March 12/08: Treat properties with multiple values and start/end positions (a.k.a. features) as objects
		seqFeatures = []

		# March 17/08: Fetch Insert feature descriptors - tag position and expression system
		# Use built-in Insert function instead of 'if-else' blocks with hardcoded values
		featureDescriptors = reagent.getFeatureDescriptors()

		# Update Sept. 10/09: No longer can hardcode feature names - must retrieve from database!!
		# Update Oct. 26/09: Keep "DNA Sequence Features" as category, since this code is executed only for Vector and Insert.  But replace "Insert" with 'rType', b/c features differ for Vector and Insert
		sequenceFeatures = rtPropHandler.findReagentTypeAttributeNamesByCategory(reagentType_Name_ID_Map[rType], prop_Category_Name_ID_Map["DNA Sequence Features"])

		#sequenceFeatures = reagent.getSequenceFeatures()	# removed Sept. 10/09
		#print `sequenceFeatures`

		# Removed May 16/08 - not a very good idea to delete all features, including sites and linkers - linkers may hinder modification but are necessary for correct feature remapping during Vector sequence reconstitution.  See what happens
		#singleValueFeatures = reagent.getSingleFeatures()	# March 18/08 - Differentiate between features such as promoter or tag type, which could have multiple values, and cloning sites and linkers, which only have one value and one position
		
		#featureNames = sequenceFeatures + singleValueFeatures
		#if fType.lower() != "5' cloning site" and fType.lower() != "3' cloning site" and fType.lower() != "cdna insert":
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		#print prefix
		
		
		rHandler.deleteReagentFeatures(rID)
		
		featureNames = sequenceFeatures
		
		featureAliases = {}
		features = {}
		
		for f in featureNames:
			fAlias = prop_Name_Alias_Map[f]
			
			# July 9/09
			#fPropID = prop_Name_ID_Map[f]
			fPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[f], prop_Category_Name_ID_Map["DNA Sequence Features"])
			
			featureAliases[fAlias] = f
			features[fPropID] = f
		
		#print `featureAliases`
		#print `prop_Alias_Name_Map`
		
		for fAlias in featureAliases:
			#print fAlias
			
			# Special case: cDNA start and stop positions
			if prop_Alias_Name_Map[fAlias].lower() == 'cdna insert':
				
				# removed Oct. 31/08 - done deletion already!!
				## May 22/08: NOT FOR NOVEL VECTORS!!!!!!!
				#if rType == 'Vector' and subtype == 'novel':
					# delete any previously (incorrectly!) stored cDNA value
					#rHandler.deleteReagentProperty(rID, prop_Name_ID_Map["cdna insert"])
					#continue
				
				# No value, positions only
				## Still need to create a new Feature instance
				#fTemp = SequenceFeature(SequenceFeature)
				
				# changed July 9/09 - alias map is also affected by property sharing!
				fName = prop_Alias_Name_Map[fAlias].lower()
				fID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[fName], prop_Category_Name_ID_Map["DNA Sequence Features"])
				#fID = prop_Alias_ID_Map[fAlias]
				
				#fType = prop_ID_Name_Map[fID]
				#fTemp.setFeatureType(fType)
				
				startFieldName = prefix + fAlias + "_startpos" + postfix
				endFieldName = prefix + fAlias + "_endpos" + postfix
				
				if form.has_key(startFieldName):
					tmpStartPos = form.getvalue(startFieldName)
					#print `tmpStartPos`
					#fTemp.setFeatureStartPos(tmpStartPos)
				else:
					tmpStartPos = 0

				if form.has_key(endFieldName):
					tmpEndPos = form.getvalue(endFieldName)
					#fTemp.setFeatureEndPos(tmpEndPos)
				else:
					tmpEndPos = 0
					
				#print tmpStartPos
				#seqFeatures.append(fTemp)
				
				rHandler.addReagentProperty(rID, fID, "", tmpStartPos, tmpEndPos)
				
				# Jan. 22/09: Store cDNA orientation if Insert is reverse complemented
				orientationFieldName = prefix + fAlias + "_orientation" + postfix
				
				if form.has_key(orientationFieldName):
					tmpDir = form.getvalue(orientationFieldName)
					#print tmpDir
				else:
					tmpDir = 'forward'

				# Update July 2/09: pass cdnaPropID to setPropertyDirection() function in combination with its category
				cdnaPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
				rHandler.setPropertyDirection(rID, cdnaPropID, tmpDir)
				
			# May 27/08: Cloning sites and Linkers (code is somewhat redundant and could be optimized but keep as is for now)
			elif prop_Alias_Name_Map[fAlias].lower() == "5' cloning site":
				five_prime_site = form.getvalue(prefix + prop_Name_Alias_Map["5' cloning site"] + postfix)
				
				#print "5' site " + five_prime_site
				
				# May 28/08: Allow blank sites for Novel vectors
				if five_prime_site:
					if five_prime_site == "Other":
						fVal = form.getvalue("5_prime_cloning_site_name_txt")
					else:
						fVal = five_prime_site
				else:
					# just ignore site altogether
					continue
				
				#fTemp = SequenceFeature(SequenceFeature)
				#fID = prop_Alias_ID_Map[fAlias]		# removed July 20/09
				
				# update July 21/09
				fid_tmp = prop_Alias_ID_Map[fAlias]
				fID = pHandler.findReagentPropertyInCategoryID(fid_tmp, prop_Category_Name_ID_Map["DNA Sequence Features"])
				#print fID
				
				# June 3/08: No! Cloning sites have only one value, so their position field names don't contain actual site!
				#startFieldName = prefix + fAlias + "_" + fVal + "_startpos" + postfix
				#endFieldName = prefix + fAlias + "_" + fVal + "_endpos" + postfix
				
				startFieldName = prefix + fAlias + "_startpos" + postfix
				endFieldName = prefix + fAlias + "_endpos" + postfix
				
				if form.has_key(startFieldName):
					fStartPos = form.getvalue(startFieldName)
				else:
					fStartPos = 0
				
				#print "start " + `fStartPos`
				
				if form.has_key(endFieldName):
					fEndPos = form.getvalue(endFieldName)
				else:
					fEndPos = 0
				
				#print "end " + `fEndPos`
				
				#fTemp.setFeatureStartPos(fStartPos)
				#fTemp.setFeatureEndPos(fEndPos)
				
				# Sept. 3/08: Store orientation
				orientationFieldName = prefix + fAlias + "_orientation" + postfix
				
				if form.has_key(orientationFieldName):
					tmpDir = form.getvalue(orientationFieldName)
					#print tmpDir
				else:
					tmpDir = 'forward'
				
				#fTemp.setFeatureDirection(tmpDir)
				
				#seqFeatures.append(fTemp)
				
				rHandler.addReagentProperty(rID, fID, fVal, fStartPos, fEndPos, tmpDir)
		
			elif prop_Alias_Name_Map[fAlias].lower() == "3' cloning site":
				
				three_prime_site = form.getvalue(prefix + prop_Name_Alias_Map["3' cloning site"] + postfix)
				
				# May 28/08: Allow blank sites for Novel vectors
				if three_prime_site:
					if three_prime_site == "Other":
						fVal = form.getvalue("3_prime_cloning_site_name_txt")
					else:
						fVal = three_prime_site
				else:
					# just ignore site altogether
					continue
					
				#fTemp = SequenceFeature(SequenceFeature)
				
				# change July 20/09
				#fID = prop_Alias_ID_Map[fAlias]
				
				fid_tmp = prop_Alias_ID_Map[fAlias]
				fID = pHandler.findReagentPropertyInCategoryID(fid_tmp, prop_Category_Name_ID_Map["DNA Sequence Features"])
				
				#fType = prop_ID_Name_Map[fID]
				#fTemp.setFeatureType(fType)
				
				#fTemp.setFeatureName(fVal)
				
				# June 3/08: Cloning sites have only one value, so their position field names don't contain actual site!
				#startFieldName = prefix + fAlias + "_" + fVal + "_startpos" + postfix
				#endFieldName = prefix + fAlias + "_" + fVal + "_endpos" + postfix
				
				startFieldName = prefix + fAlias + "_startpos" + postfix
				endFieldName = prefix + fAlias + "_endpos" + postfix
				
				if form.has_key(startFieldName):
					fStartPos = form.getvalue(startFieldName)
				else:
					fStartPos = 0
					
				if form.has_key(endFieldName):
					fEndPos = form.getvalue(endFieldName)
				else:
					fEndPos = 0
					
				#fTemp.setFeatureStartPos(fStartPos)
				#fTemp.setFeatureEndPos(fEndPos)
		
				# Sept. 3/08: Store orientation
				orientationFieldName = prefix + fAlias + "_orientation" + postfix
				
				if form.has_key(orientationFieldName):
					tmpDir = form.getvalue(orientationFieldName)
					#print tmpDir
				else:
					tmpDir = 'forward'
					
				#fTemp.setFeatureDirection(tmpDir)
				
				#seqFeatures.append(fTemp)
				
				rHandler.addReagentProperty(rID, fID, fVal, fStartPos, fEndPos, tmpDir)
			
			else:
				tmpStart = -1
				tmpEnd = -1
				#print prefix
				
				featureType = "dna_sequence_features"
				
				for tmpPropName in form.keys():
					#print tmpPropName
					#print fAlias
					
					# Updated July 2/09
					fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map["DNA Sequence Features"])
					#print tmpPropName
					#print fID
						
					if tmpPropName.find(prefix + fAlias + "_:_") >= 0:
						pValStartInd = len(prefix + fAlias)+3
						#print pValStartInd
						#pValStopInd = tmpPropName.find("_", pValStartInd)	# rmvd nov 1/09
						pValStopInd = tmpPropName.rfind("_start_", pValStartInd)
						#print pValStopInd
						
						# actual feature value - BUT before saving this value, check if it has been changed to Other
						tmpPropValue = tmpPropName[pValStartInd:pValStopInd]
						#print " feature " + tmpPropName
						#print ", value " + tmpPropValue
						
						if tmpPropValue and len(tmpPropValue) > 0:
							#print tmpPropValue
							# get positions - changed oct. 25/08
							
							# Update Nov 3/09
							#start_ind1 = tmpPropName.find(tmpPropValue) + len(tmpPropValue) + len("_start_")
							
							start_ind1 = tmpPropName.rfind("_start_") + len("_start_")
							start_ind2 = tmpPropName.find("_end_")
							#print start_ind2
	
							tmpStart = tmpPropName[start_ind1:start_ind2]
							#print "start " + tmpStart
							
							if tmpStart and len(tmpStart) > 0 and int(tmpStart) > 0:
								end_ind_1 = start_ind2 + len("_end_")
								#print end_ind_1
								end_ind_2 = tmpPropName.find("_", end_ind_1)
								#print end_ind_2
		
								tmpEnd = tmpPropName[end_ind_1:end_ind_2]
								#print "end " + tmpEnd
								
								if tmpEnd and len(tmpEnd) > 0 and int(tmpEnd) > 0:
									tmpDirName = prefix + fAlias + "_:_" + tmpPropValue + "_start_" + `int(tmpStart)` + "_end_" + `int(tmpEnd)` + "_orientation" + postfix
									#print tmpDirName
									
									if form.has_key(tmpDirName):
										tmpDir = form.getvalue(tmpDirName)
										#print "FOUND DIRECTION " + tmpDirName
							
										# Nov. 13/08: If there are duplicates, select one
										if utils.isList(tmpDir):
											#print "Descriptor " + tmpDescr
											tmpDir = tmpDir[0]
							
										#fID = prop_Alias_ID_Map[fAlias]
										fID = pHandler.findReagentPropertyInCategoryID(prop_Alias_ID_Map[fAlias], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
										
										#print tmpPropValue
										#print form[tmpPropName].value
										
										# Jan. 28/10: add 'Other' values to list
										# With features, since their type/name/start/end/direction are encoded in the field name, 'Other' is the form value
										#if tmpPropValue.lower() == 'other':
											#print "SAVING OTHER!!!!!" + tmpPropName
											
											#tmpPropOther_text = rType + "_"  + featureType + "_:_" + fAlias + "_name_txt"
										
											##print tmpDescr_text
											#tmpPropValue = form.getvalue(tmpPropOther_text)
								
											##print "SAVING OTHER " + `tmpPropValue`
											#newSetEntry = tmpPropValue
											
											#print newSetEntry
			
										# invoke handler to add textbox value to dropdown list
										rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, fID)
									
										#print fAlias
										#print rTypeAttributeID
										
										# update Nov. 18/09
										setGroupID = sysHandler.findPropSetGroupID(fID)	# it must exist
									
										#sHandler.updateSet(rTypeAttributeID, rType + " " + propName, newSetEntry)
										ssetID = sysHandler.findSetValueID(setGroupID, tmpPropValue)
										
										if ssetID <= 0:
											ssetID = sysHandler.addSetValue(setGroupID, tmpPropValue)
									
										#print ssetID
										#print rTypeAttributeID
										
										if not sysHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
											sysHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
										
										# descriptor
										if featureDescriptors.has_key(prop_Alias_Name_Map[fAlias]):
											tmpDescrName = featureDescriptors[prop_Alias_Name_Map[fAlias]]
											tmpDescrAlias = prop_Name_Alias_Map[tmpDescrName]
											#print "DESCRIPTOR " + tmpDescrAlias
										
											tmpDescrID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map[tmpDescrName], prop_Category_Name_ID_Map[prop_Category_Alias_Name_Map[featureType]])
										
											#print tmpDescrID
									
											tmpDescrField = prefix + tmpDescrAlias + "_:_" + tmpPropValue + "_start_" + tmpStart + "_end_" + tmpEnd + postfix

											#print tmpDescrField
											
											if form.has_key(tmpDescrField):
												tmpDescr = form.getvalue(tmpDescrField)
												
												# Nov. 13/08: If there are duplicates, select one
												if utils.isList(tmpDescr):
													#print "Descriptor " + tmpDescr
													tmpDescr = tmpDescr[0]
										
												#print "??" + tmpDescr + "!!"
										
												# jan. 28/10: save 'other'
												# June 8, 2010: For descriptor have to go to textbox to fetch Other values
												if tmpDescr.lower() == 'other':
													
													if fAlias == "tag":
														descrAlias = "tag_position"
													elif fAlias == "promoter":
														descrAlias = "expression_system"
										
													tmpDescr_text = prefix + tmpDescrAlias + "_:_" + tmpPropValue + "_start_" + tmpStart + "_end_" + tmpEnd + "_name_txt"
										
													#print tmpDescr_text
										
													tmpDescr = form.getvalue(tmpDescr_text)
										
													#print "SAVING OTHER " + `tmpDescr`
										
												if utils.isList(tmpDescr):
													tmpDescr = tmpDescr[0]
													#print tmpDescr
									
												newSetEntry = tmpDescr
										
												#print newSetEntry
		
												# invoke handler to add textbox value to dropdown list
												rTypeAttributeID = rtPropHandler.findReagentTypeAttributeID(rTypeID, tmpDescrID)
										
												#print rTypeAttributeID
												
												# update Nov. 18/09
												setGroupID = sysHandler.findPropSetGroupID(tmpDescrID)
										
												#print setGroupID
												
												ssetID = sysHandler.findSetValueID(setGroupID, newSetEntry)
												
												if ssetID <= 0:
													ssetID = sysHandler.addSetValue(setGroupID, newSetEntry)
										
												#print ssetID
												
												if not sysHandler.existsReagentTypeAttributeSetValue(rTypeAttributeID, ssetID):
													sysHandler.addReagentTypeAttributeSetEntry(rTypeAttributeID, ssetID)
											else:
												tmpDescr = ""
										else:
											tmpDescr = ""
									
										if not rHandler.existsPropertyValue(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDescr, tmpDir):
											rHandler.addReagentProperty(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDir)
											
											rHandler.setReagentFeatureDescriptor(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDescr)
									else:
										pass
								else:
									pass
							else:
								pass
						else:
							pass
					else:
						pass
					
					## convert to lowercase b/c of PolyA
					#if tmpPropName.lower().find(prefix + fAlias.lower()) >= 0:
						##print tmpPropName

						#pValStartInd = len(prefix + fAlias.lower())+1
						##print pValStartInd
						#pValStopInd = tmpPropName.lower().find("_", pValStartInd)
						
						## actual feature value
						#tmpPropValue = tmpPropName[pValStartInd:pValStopInd]
						##print tmpPropValue
						
						#if tmpPropValue and len(tmpPropValue) > 0:
							## get positions - changed oct. 25/08
							#start_ind1 = tmpPropName.find(tmpPropValue) + len(tmpPropValue) + len("_start_")
							#start_ind2 = tmpPropName.find("_end_")
	
							#tmpStart = tmpPropName[start_ind1:start_ind2]
							##print "start " + `tmpStart`
							
							#if tmpStart and len(tmpStart) > 0 and int(tmpStart) > 0:
								#end_ind_1 = start_ind2 + len("_end_")
								##print end_ind_1
								#end_ind_2 = tmpPropName.find("_", end_ind_1)
								##print end_ind_2
		
								#tmpEnd = tmpPropName[end_ind_1:end_ind_2]
								##print "; end " + `tmpEnd`
								
								#if tmpEnd and len(tmpEnd) > 0 and int(tmpEnd) > 0:
									#tmpDirName = prefix + fAlias + "_" + tmpPropValue + "_start_" + `int(tmpStart)` + "_end_" + `int(tmpEnd)` + "_orientation" + postfix
									##print tmpDirName
									
									#if form.has_key(tmpDirName):
										#tmpDir = form.getvalue(tmpDirName)
										##print "FOUND DIRECTION " + tmpDirName
										
										## Nov. 13/08: If there are duplicates, select one
										#if utils.isList(tmpDir):
											##print "Descriptor " + tmpDescr
											#tmpDir = tmpDir[0]
										
										## descriptor
										#if featureDescriptors.has_key(prop_Alias_Name_Map[fAlias]):
											#tmpDescrName = featureDescriptors[prop_Alias_Name_Map[fAlias]]
											#tmpDescrAlias = prop_Name_Alias_Map[tmpDescrName]
											#tmpDescrField = prefix + tmpDescrAlias + "_" + tmpPropValue + "_start_" + tmpStart + "_end_" + tmpEnd + postfix
										
											#if form.has_key(tmpDescrField):
												#tmpDescr = form.getvalue(tmpDescrField)
										
												## Nov. 13/08: If there are duplicates, select one
												#if utils.isList(tmpDescr):
													##print "Descriptor " + tmpDescr
													#tmpDescr = tmpDescr[0]
											#else:
												#tmpDescr = ""
										#else:
											#tmpDescr = ""
										
										##print tmpPropValue
										
										## Dec. 1/08: Add 'Other' values
										##if tmpPropValue == "Other":
											##tmpPropValue = form.getvalue(prefix + fAlias + "_name_start_" + tmpStart + "_end_" + tmpEnd + postfix)
											##print tmpPropValue
										
											##print "ever here??" + tmpPropValue
							
										#if not rHandler.existsPropertyValue(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDescr, tmpDir):
											#rHandler.addReagentProperty(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDir)
											
											#rHandler.setReagentFeatureDescriptor(rID, fID, tmpPropValue, tmpStart, tmpEnd, tmpDescr)
										
											#rTypeID = reagentType_Name_ID_Map[rType]
										
											#rTypeAttrID = rtPropHandler.findReagentTypeAttributeID(rTypeID, fID)
										
											#comments = rType + " " + prop_Alias_Descr_Map[fAlias]
										
											##sysHandler.updateSet(fAlias, tmpPropValue)
											##sysHandler.updateSet(rTypeAttrID, comments, tmpPropValue)
								#else:
									#pass
							#else:
								#pass
						#else:
							#pass
					#else:
						#pass

		# Sept. 3/08: Add linkers (not included in sequenceFeatures list, do separately)
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print
	
		# delete old values, because linkers are not included in features list that gets deleted
		
		# update July 20/09
		fp_linker_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["5' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])
		tp_linker_prop_id = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["3' linker"], prop_Category_Name_ID_Map["DNA Sequence Features"])

		rHandler.deleteReagentProperty(rID, fp_linker_prop_id)
		rHandler.deleteReagentProperty(rID, tp_linker_prop_id)
		
		fAlias = prop_Name_Alias_Map["5' linker"]
		#print fAlias
		fp_linker_prop = prefix + fAlias + postfix
		#print fp_linker_prop
		
		if form.has_key(fp_linker_prop):
			fVal = form.getvalue(fp_linker_prop)
			#print fVal
			
			#fTemp = SequenceFeature(SequenceFeature)
			
			# change July 20/09
			#fID = prop_Alias_ID_Map[fAlias]
			fid_tmp = prop_Alias_ID_Map[fAlias]
			fID = pHandler.findReagentPropertyInCategoryID(fid_tmp, prop_Category_Name_ID_Map["DNA Sequence Features"])

			#fType = prop_ID_Name_Map[fID]
			#fTemp.setFeatureType(fType)
			
			#fTemp.setFeatureName(fVal)
			
			startFieldName = prefix + fAlias + "_startpos" + postfix
			endFieldName = prefix + fAlias + "_endpos" + postfix
			
			fStartPos = form.getvalue(startFieldName)
			fEndPos = form.getvalue(endFieldName)
			
			#fTemp.setFeatureStartPos(fStartPos)
			#fTemp.setFeatureEndPos(fEndPos)
			
			# Sept. 3/08: Store orientation
			orientationFieldName = prefix + fAlias + "_orientation" + postfix
			tmpDir = form.getvalue(orientationFieldName)
			#print tmpDir
			#fTemp.setFeatureDirection(tmpDir)
			
			#seqFeatures.append(fTemp)
			
			if not rHandler.existsPropertyValue(rID, fID, fVal, fStartPos, fEndPos, tmpDir):
				rHandler.addReagentProperty(rID, fID, fVal, fStartPos, fEndPos, tmpDir)
		
		# 3' linker
		fAlias = prop_Name_Alias_Map["3' linker"]
		#print fAlias
		tp_linker_prop = prefix + fAlias + postfix
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print tp_linker_prop
		
		if form.has_key(tp_linker_prop):
			fVal = form.getvalue(tp_linker_prop)
			#print fVal
			
			#fTemp = SequenceFeature(SequenceFeature)
			
			# change July 20/09
			fid_tmp = prop_Alias_ID_Map[fAlias]
			fID = pHandler.findReagentPropertyInCategoryID(fid_tmp, prop_Category_Name_ID_Map["DNA Sequence Features"])

			#fID = prop_Alias_ID_Map[fAlias]
			
			#fType = prop_ID_Name_Map[fID]
			#fTemp.setFeatureType(fType)
			
			#fTemp.setFeatureName(fVal)
			
			startFieldName = prefix + fAlias + "_startpos" + postfix
			endFieldName = prefix + fAlias + "_endpos" + postfix
			
			fStartPos = form.getvalue(startFieldName)
			fEndPos = form.getvalue(endFieldName)
			
			#fTemp.setFeatureStartPos(fStartPos)
			#fTemp.setFeatureEndPos(fEndPos)
			
			# Sept. 3/08: Store orientation
			orientationFieldName = prefix + fAlias + "_orientation" + postfix
			tmpDir = form.getvalue(orientationFieldName)
			#print tmpDir
			#fTemp.setFeatureDirection(tmpDir)
			
			#seqFeatures.append(fTemp)
				
			if not rHandler.existsPropertyValue(rID, fID, fVal, fStartPos, fEndPos, tmpDir):
				rHandler.addReagentProperty(rID, fID, fVal, fStartPos, fEndPos, tmpDir)
		
		utils.redirect(url)
	
	elif form.has_key("process_warning"):

		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `form`
		
		# take the appropriate action
		action = form["warning_change_input"].value
		
		if action == 'restart':
			# Nov. 17/08: Delete all previous information
			# Delete (deprecate) the reagent itself, its properties, protein/DNA sequence and associations
			rID = int(form.getvalue("reagent_id_hidden"))
			rHandler.deleteReagent(rID)
			utils.redirect(hostname  + "Reagent.php?View=2")
		
		elif action == 'next':
			newSeqID = sHandler.matchSequence("")
			utils.redirect(url + "&Seq=" + `newSeqID`)
		
		else:
			# get new input values
			
			rType = form.getvalue("reagent_type_hidden")
			subtype = form.getvalue("subtype_hidden")
			
			# April 30/08
			rID = int(form.getvalue("reagent_id_hidden"))
			reagent = rHandler.createReagent(rID)
			
			if rType == "Vector":
				reagent.setSubtype(subtype)

				# get parent values depending on subtype
				parents = {}
				url = ""	# the URL to redirect to, will have varying parent name parameters
				
				# Redundant code, but keep separate, as these are two separate actions
				if subtype == 'nonrecomb' or subtype == 'gateway_entry':

					# define here for the sake of error handling
					pvAssocProp = aHandler.findAssocPropID("vector parent id")
					insertAssocProp = aHandler.findAssocPropID("insert id")
	
					if form.has_key("new_nr_parent_vector") and form.has_key("new_insert_id"):
						
						parentVectorID = form.getvalue("new_nr_parent_vector").strip()
						insertID = form.getvalue("new_insert_id").strip()
						
						try:
							pv_db_id = rHandler.convertReagentToDatabaseID(parentVectorID)		# internal db ID
							
							try:
								insert_db_id = rHandler.convertReagentToDatabaseID(insertID)

							except ReagentDoesNotExistException:
								i = ReagentDoesNotExistException("Insert ID not found in database")
								
								# "AP" stands for "Association Property"
								# April 30/08: Added rID parameter
								utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&AP=" + `insertAssocProp`)
			
						except ReagentDoesNotExistException:
							i = ReagentDoesNotExistException("Parent Vector ID not found in database")
							
							# "AP" stands for "Association Property"
							# April 30/08: Added rID parameter
							utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID + "&Err=" + `i.err_code()` + "&AP=" + `pvAssocProp`)
						
						# if all ok save parents
						parents["vector parent id"] = pv_db_id
						parents["insert id"] = insert_db_id
		
						url = hostname + "Reagent.php?View=2&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&I=" + insertID

						#print `sites`
						if action == 'reverse_insert':
							reverse_insert = True
							
							#print "reverse insert??" + `reverse_insert`
						
							# Feb. 23/10: why in the world would we reverse the order here????????
							
							## Feb. 2/09: Get custom sites from page AND REVERSE THEIR ORDER!!
							#sites = []
							
							#if form.has_key("pv_custom_five_prime"):
						
								#pv_fpcs = form.getvalue("pv_custom_five_prime")
								#pv_tpcs = form.getvalue("pv_custom_three_prime")
								
								#insert_fpcs = form.getvalue("insert_custom_five_prime")
								#insert_tpcs = form.getvalue("insert_custom_three_prime")
								
								#if pv_fpcs == insert_fpcs:
									#fpcs = pv_fpcs
								#else:
									#fpcs = pv_fpcs + "-" + insert_fpcs
									
								#if pv_tpcs == insert_tpcs:
									#tpcs = pv_tpcs
								#else:
									#tpcs = insert_tpcs + "-" + pv_tpcs
									
							#else:
								#fpcs = form.getvalue("5_prime_cloning_site")
								#tpcs = form.getvalue("3_prime_cloning_site")
							
							##print "5' site " + fpcs
							##print "3' site " + tpcs
							
							#sites.append(tpcs)
							#sites.append(fpcs)
						else:
							reverse_insert = False
							
						# Moved this code segment here on Feb. 24/10. CANNOT hybridize sites w/o checking if the used actually wants to RC Insert and no hybridization is required!
						
						## Modified May 27/08: NO!!!!!  Fetch sites too in case they're hybrid and call previewSequence function again
						## and take the appropriate action - either change sites or parents
						##if action == 'change_parents':
							## Only change parents, don't touch cloning sites
							##previewVectorSequence(rID, reagent, parents, currUser, db, cursor)
						##else:
							## Here, explicitly changing sites, making one or both hybrid - Parents unchanged
						sites = []
						
						if form.has_key("pv_custom_five_prime"):
						
							pv_fpcs = form.getvalue("pv_custom_five_prime")
							pv_tpcs = form.getvalue("pv_custom_three_prime")
							
							insert_fpcs = form.getvalue("insert_custom_five_prime")
							insert_tpcs = form.getvalue("insert_custom_three_prime")
							
							if not reverse_insert:
								if pv_fpcs == insert_fpcs:
									fpcs = pv_fpcs
								else:
									fpcs = pv_fpcs + "-" + insert_fpcs
									
								if pv_tpcs == insert_tpcs:
									tpcs = pv_tpcs
								else:
									tpcs = insert_tpcs + "-" + pv_tpcs
							else:
								fpcs = insert_fpcs
								tpcs = insert_tpcs
						else:
							fpcs = form.getvalue("5_prime_cloning_site")
							tpcs = form.getvalue("3_prime_cloning_site")
					
						sites.append(fpcs)
						sites.append(tpcs)
						
						previewVectorSequence(rID, reagent, parents, currUser, db, cursor, sites, reverse_insert)
					else:
						print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
						print					# DITTO
						print "Unknown request"
						
				elif subtype == 'recomb' or subtype == 'gateway_expression':	
					pvAssocProp = aHandler.findAssocPropID("vector parent id")
					pivAssocProp = aHandler.findAssocPropID("parent insert vector")
					insertAssocPropID = aHandler.findAssocPropID("insert id")

					if form.has_key("rec_parent_vector") and form.has_key("insert_parent_vector"):
						parentVector = form.getvalue("rec_parent_vector").strip()
						insertParentVector = form.getvalue("insert_parent_vector").strip()
					
						try:
							pv_db_id = rHandler.convertReagentToDatabaseID(parentVector)	# internal db ID
							
							try:
								ipv_db_id = rHandler.convertReagentToDatabaseID(insertParentVector)
								
								# June 4, 2010: This restriction was lifted yesterday.  From now on, ALLOW to use non-recombination, even novel vectors as donors - AS LONG AS SEQUENCE CAN BE RECOMBINED.  See previewVectorSequence() function above.
								
								'''
								# June 2/08: Restrict reagent types here: Check explicitly IPV's ATypeID to make sure it's 1 - i.e. only allow the usage of non-recombination vectors as Donors
								donorATypeID = raHandler.findReagentAssociationType(ipv_db_id)
									
								if donorATypeID != assoc_Name_Type_Map["INSERT"]:
									i = ReagentTypeException("Invalid Insert Parent Vector type")
									
									utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `pivAssocProp`)
								else:
									# Once you know this is a non-recomb. vector continue
									ipvAssocID = raHandler.findReagentAssociationID(ipv_db_id)
	
									if ipvAssocID:
										insert_db_id = aHandler.findAssocPropValue(ipvAssocID, insertAssocPropID)
										
										if insert_db_id > 0:
											parents["insert id"] = insert_db_id
										else:
											i = ReagentDoesNotExistException("Invalid Insert Parent Vector ID")
									
											# "AP" stands for "Association Property"
											# April 30/08: Added rID parameter
											utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `pivAssocProp`)
						
									else:
										i = ReagentDoesNotExistException("Invalid Insert Parent Vector ID")
									
										# "AP" stands for "Association Property"
										# April 30/08: Added rID parameter
										utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `pivAssocProp`)
								'''
								
							except ReagentDoesNotExistException:
								i = ReagentDoesNotExistException("Insert Parent Vector ID not found in database")
								
								# "AP" stands for "Association Property"
								# April 30/08: Added rID parameter
								utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `pivAssocProp`)
			
						except ReagentDoesNotExistException:
							i = ReagentDoesNotExistException("Parent Vector ID not found in database")
							
							# "AP" stands for "Association Property"
							# April 30/08: Added rID parameter
							utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector + "&Err=" + `i.err_code()` + "&AP=" + `pvAssocProp`)
						
						# if all ok save parents
						parents["vector parent id"] = pv_db_id
						parents["parent insert vector"] = ipv_db_id
		
						url = hostname + "Reagent.php?View=2&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&IPV=" + insertParentVector
						
						# and take the appropriate action - either change sites or parents
						if action == 'change_parents':
							# Only change parents, don't touch cloning sites
							previewVectorSequence(rID, reagent, parents, currUser, db, cursor)
						else:
							# Here, explicitly changing sites, making one or both hybrid - Parents unchanged
							sites = []
							
							fpcs = form.getvalue("5_prime_cloning_site")
							tpcs = form.getvalue("3_prime_cloning_site")
					
							sites.append(fpcs)
							sites.append(tpcs)
				
							previewVectorSequence(rID, reagent, parents, currUser, db, cursor, sites)
					
				else:
					raise Exception("Unknown Vector subtype")
			
			elif rType == "Insert":
				#utils.redirect(hostname + "Reagent.php?View=2")	# removed Sept. 8/08
				
				# Added Sept. 8/08
				if form.has_key("reagent_id_hidden"):
					insertID = int(form.getvalue("reagent_id_hidden"))
				else:
					insertID = rHandler.createNewReagent("Insert")
					
				parents = {}
				
				if form.has_key("insert_parent_vector"):
					insertParentVector = form.getvalue("insert_parent_vector")
				else:
					insertParentVector = ""
				
				if form.has_key("sense_oligo"):
					senseOligo = form.getvalue("sense_oligo")
				else:
					senseOligo = ""
					
				if form.has_key("antisense_oligo"):
					antisenseOligo = form.getvalue("antisense_oligo")
				else:
					antisenseOligo = ""
				
				parents["insert parent vector id"] = insertParentVector
				parents["sense oligo"] = senseOligo
				parents["antisense oligo"] = antisenseOligo
				
				previewInsertSequence(insertID, parents, currUser, uPackets)

			
			elif rType == "CellLine":
				
				if subtype == "stable_cell_line":
					parents = []
					
					parentVector = form.getvalue('cell_line_parent_vector').strip()
					parentCellLine = form.getvalue('parent_cell_line').strip()
					
					# Verify parent packets!!!
					currReadProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Reader')
					currWriteProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Writer')
					publicProj = packetHandler.findAllProjects(isPrivate="FALSE")
					
					# list of Packet OBJECTS
					currUserWriteProjects = utils.unique(currReadProj + currWriteProj + publicProj)
					
					uPackets = []
					
					for p in currUserWriteProjects:
						uPackets.append(p.getNumber())
						
					# Get project IDs of parents
					#packetPropID = pHandler.findPropID("packet id")
					packetPropID = pHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["packet id"], prop_Category_Name_ID_Map["General Properties"])
					
					# parent vector
					try:
						pv_db_id = rHandler.convertReagentToDatabaseID(parentVector)
						pvProjectID = int(rHandler.findSimplePropertyValue(pv_db_id, packetPropID))	# need to cast
						
						if currUser.getCategory() != 'Admin' and pvProjectID not in uPackets:
						
							i = PVProjectAccessException("You do not have read access to this project")
					
							clpvAssocProp = aHandler.findAssocPropID("cell line parent vector id")
			
							utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clpvAssocProp`)
							
						else:
							
							# parent vector ok, check cell line
							try:
								cl_db_id = rHandler.convertReagentToDatabaseID(parentCellLine)
								
								# Use 'try' again to capture the project ID
								try:
									clProjectID = int(rHandler.findSimplePropertyValue(cl_db_id, packetPropID))
									
									if currUser.getCategory() != 'Admin' and clProjectID > 0 and clProjectID not in uPackets:
										i = CLProjectAccessException("You do not have read access to this project")
								
										clAssocProp = aHandler.findAssocPropID("parent cell line id")
						
										utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clAssocProp`)
									
									else:
										# user may view project, proceed
										utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&CL=" + parentCellLine)
								
								except (TypeError, ValueError, IndexError):
									
								#elif (clProjectID == None or clProjectID <= 0) and currUser.getCategory() != 'Admin':
									
									i = CLProjectAccessException("You do not have read access to this project")
							
									clAssocProp = aHandler.findAssocPropID("parent cell line id")
					
									utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clAssocProp`)
									
								#else:
									# everything ok, proceed
									#redirect(hostname + "Reagent.php?View=2&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVector + "&CL=" + parentCellLine)
					
							except ReagentDoesNotExistException:
								i = ReagentDoesNotExistException("Parent Cell Line ID not found in database")
								
								# get "parent cell line" association type
								clAssocProp = aHandler.findAssocPropID("parent cell line id")
								
								# "AP" stands for "Association Property"
								utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clAssocProp`)
							
					except ReagentDoesNotExistException:
						i = ReagentDoesNotExistException("Parent Vector ID not found in database")
						
						# get "parent vector" association type
						clpvAssocProp = aHandler.findAssocPropID("cell line parent vector id")
						
						# "AP" stands for "Association Property"
						utils.redirect(hostname + "Reagent.php?View=2&rID=" + `rID` + "&Type=" + rType + "&Sub=" + subtype + "&PV=" + parentVectorID + "&CL=" + parentCellLine + "&Err=" + `i.err_code()` + "&AP=" + `clpvAssocProp`)
	
					parents.append(parentVector)
					parents.append(parentCellLine)
					
					# take the appropriate action
					action = form["warning_change_input"].value
					
					if action == 'restart':
						utils.redirect(hostname  + "Reagent.php?View=2&rID=" + `rID`)
				
					elif action == 'change_parents':
						# Only change parents, don't touch cloning sites
						previewCellLineProperties(rID, rType, subtype, parents, currUser, db, cursor)
	
	# Nov. 5/08: Add 'Cancel' function
	elif form.has_key("cancel_creation"):
		
		# Delete (deprecate) the reagent itself, its properties, protein/DNA sequence and associations
		rType = form.getvalue("reagent_type_hidden")
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		#print `prop_Alias_Name_Map`
		#print `form`
		
		prefix = "reagent_detailedview_"
		postfix = "_prop"
		
		rID = int(form.getvalue("reagent_id_hidden"))
		
		# if this is an Insert made from Primer, delete the newly created Oligos too
		if form.has_key("from_primer") and form.getvalue("from_primer") == 'True':
			senseOligoID = iHandler.findSenseOligoID(rID)
			antisenseOligoID = iHandler.findAntisenseOligoID(rID)
			
			rHandler.deleteReagent(senseOligoID)
			rHandler.deleteReagent(antisenseOligoID)
			rHandler.deleteReagent(rID)
		else:
			rHandler.deleteReagent(rID)
		
		utils.redirect(hostname + "Reagent.php?View=1")

	cursor.close()
	db.close()
	
preload()
