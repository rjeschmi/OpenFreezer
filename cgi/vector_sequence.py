#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()
	
import MySQLdb
import sys
import string

from database_conn import DatabaseConn

from mapper import ReagentPropertyMapper, ReagentAssociationMapper
from general_handler import *
from reagent_handler import ReagentHandler, InsertHandler
from sequence_handler import DNAHandler, ProteinHandler
from comment_handler import CommentHandler
#from system_set_handler import SystemSetHandler
from sequence_handler import SequenceHandler

import utils

from user_handler import UserHandler
from project_database_handler import ProjectDatabaseHandler
from session import Session
from exception import *


# Reconstruct and preload new vector sequence if its parents are changed during modification
def update():

	dbConn = DatabaseConn()
	db = dbConn.databaseConnect()
	
	cursor = db.cursor()
	hostname = dbConn.getHostname()

	form = cgi.FieldStorage(keep_blank_values="True")
	
	print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
	print					# DITTO
	#print `form`

	# Aug 29/07
	uHandler = UserHandler(db, cursor)
	
	if form.has_key("curr_username"):
		# store the user ID for use throughout the session; add to other views in addition to create in PHP
		currUname = form.getvalue("curr_username")
		currUser = uHandler.getUserByDescription(currUname)
		
		Session.setUser(currUser)
		
	#else:	# debug
		#currUname = 'Administrator'
		#currUser = uHandler.getUserByDescription(currUname)
		
		#Session.setUser(currUser)
		
	if form.has_key("cloning_method"):
		cloning_method = form.getvalue("cloning_method")
	
	#else:	# debug
		#cloning_method = '1'
		
	# Handlers and mappers
	rHandler = ReagentHandler(db, cursor)
	#sHandler = SystemSetHandler(db, cursor)
	pHandler = ReagentPropertyHandler(db, cursor)
	raHandler = ReagentAssociationHandler(db, cursor)
	aHandler = AssociationHandler(db, cursor)

	dnaHandler = DNAHandler(db, cursor)
	commHandler = CommentHandler(db, cursor)
	protHandler = ProteinHandler(db, cursor)	
	
	propMapper = ReagentPropertyMapper(db, cursor)
	assocMapper = ReagentAssociationMapper(db, cursor)

	# August 29/07: Restrict creation by user and project access
	packetHandler = ProjectDatabaseHandler(db, cursor)

	########################################################
	# Various maps
	########################################################

	prop_Alias_ID_Map = propMapper.mapPropAliasID()		# (propAlias, propID) - e.g. ('insert_type', '48') --> represents 'type of insert' property
	prop_Name_Alias_Map = propMapper.mapPropNameAlias()	# (propName, propAlias)
	prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)

	
	# Restriction sites
	fpcs_prop_id = pHandler.findPropID("5' cloning site")
	tpcs_prop_id = pHandler.findPropID("3' cloning site")

	newFivePrime = form.getvalue("fpcs")
	newThreePrime = form.getvalue("tpcs")

	gatewaySites = ['attb', 'attl', 'attp', 'attr']		# nov. 16/07
	
	# resulting sequence
	newSeq = ""

	# Fetch projects the user has AT LEAST Read access to (i.e. if he is explicitly declared a Writer on a project but not declared a Reader, include that project, plus all public projects)
	currReadProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Reader')
	currWriteProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Writer')
	publicProj = packetHandler.findAllProjects(isPrivate="FALSE")
	
	# list of Packet OBJECTS
	currUserWriteProjects = utils.unique(currReadProj + currWriteProj + publicProj)
	
	uPackets = []
	
	for p in currUserWriteProjects:
		uPackets.append(p.getNumber())

	# Get project IDs of parents
	packetPropID = pHandler.findPropID("packet id")

		
	# August 29/07: Need to verify parent project access AND (Sept. 12/07) reconstruct the sequence IFF parent values are changed
	newSeq = ""
	
	# Fetch projects the user has AT LEAST Read access to (i.e. if he is explicitly declared a Writer on a project but not declared a Reader, include that project, plus all public projects)
	currReadProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Reader')
	currWriteProj = packetHandler.findMemberProjects(currUser.getUserID(), 'Writer')
	publicProj = packetHandler.findAllProjects(isPrivate="FALSE")
	
	# list of Packet OBJECTS
	currUserWriteProjects = utils.unique(currReadProj + currWriteProj + publicProj)
	
	uPackets = []
	
	for p in currUserWriteProjects:
		uPackets.append(p.getNumber())
	
	# Get project IDs of parents
	packetPropID = pHandler.findPropID("packet id")
	
	if form.has_key("PV"):
		pvVal = form.getvalue("PV")
	
		if len(pvVal) > 0:
			pvID = rHandler.convertReagentToDatabaseID(pvVal)
	
			try:
				pvProjectID = int(rHandler.findSimplePropertyValue(pvID, packetPropID))
				pvSeqID = rHandler.findDNASequenceKey(pvID)	# get sequence for reconstitution later
			except TypeError:
				#pvProjectID = 0
				e = PVProjectAccessException("You are not authorized to use this Parent Vector, since you do not have Read access to its project.")
				print `e.err_code()`
		else:
			e = UnknownPVIDException("Unknown Parent Vector value")
			print `e.err_code()`
			return
			
	# else don't do anything, maybe want to delete parents!!!
	#else:
		#e = MissingPVException("No Parent Vector provided")
		#print `e.err_code()`
	
	
	if pvProjectID > 0 and currUser.getCategory() != 'Admin' and pvProjectID not in uPackets:
		e = PVProjectAccessException("Not authorized to access parent")
		print `e.err_code()`
		return
	
	if cloning_method == '1':
		
		# Non-recombination vector - Get the Insert
		if form.has_key("I"):
			insertVal = form.getvalue("I")
			
			if len(insertVal) > 0:
				insertID = rHandler.convertReagentToDatabaseID(insertVal)
				insertSeqID = rHandler.findDNASequenceKey(insertID)	# fetch Insert sequence for reconstitution later
				
				try:
					insertProjectID = int(rHandler.findSimplePropertyValue(insertID, packetPropID))
					
				except TypeError:
					#insertProjectID = 0
					e = InsertProjectAccessException("You are not authorized to use this Insert, since you do not have Read access to its project.")
					print `e.err_code()`
			else:
				#insertID = -1
				#insertProjectID = 0
				#print "Invalid Insert value"
				e = UnknownInsertIDException("Unknown Insert value")
				print `e.err_code()`
				return
				
		#else:	# NO!!!!!!!!
			#e = MissingInsertException("No Insert provided")
			#print `e.err_code()`
	
		if insertProjectID > 0 and currUser.getCategory() != 'Admin' and insertProjectID not in uPackets:
			e = InsertProjectAccessException("You are not authorized to use this Insert, since you do not have Read access to its project.")
			print `e.err_code()`
			return
	
		if  pvID > 0 and insertID > 0 :
			
			# try to reconstruct sequence and issue warning if unable
			if pvSeqID > 0 and insertSeqID > 0:
				
				# fetch insert cloning sites
				insertCloningSites = []
	
				fpcs_prop_id = pHandler.findPropID("5' cloning site")
				tpcs_prop_id = pHandler.findPropID("3' cloning site")
		
				fp_insert_cs = rHandler.findSimplePropertyValue(insertID, fpcs_prop_id)
				tp_insert_cs = rHandler.findSimplePropertyValue(insertID, tpcs_prop_id)
				
				# Determine if this is a Gateway clone from sites
				gwSites = False;
		
				if fp_insert_cs and tp_insert_cs and fp_insert_cs.lower() == 'attl' and tp_insert_cs.lower() == 'attl':
					gwSites = True
				elif not fp_insert_cs or not tp_insert_cs:
					gwSites = True
				# nov. 16/07: added this for check
				elif fp_insert_cs.lower() in gatewaySites or tp_insert_cs.lower() in gatewaySites:
					gwSites = True
				else:
					gwSites = False;
		
				if gwSites:
					# this is a gateway clone
					# if sites were changed to something other than gateway, clear sequence	
					if newFivePrime.lower() != 'attl' or newThreePrime.lower() != 'attl':
						e = InsertSitesNotFoundOnParentSequenceException()
						print `e.err_code()`
						return
					else:
						pvSeqKey = rHandler.findDNASequenceKey(pvID)
						
						# For Gateway clones, linkers are found from primers - so find the sense and antisense Oligos for this Insert
						insertLinkers = []
			
						# Find Sense and Antisense Oligos for this Insert
						# (not using antisense just yet - verify with Karen)
						iHandler = InsertHandler(db, cursor)
			
						senseOligoID = iHandler.findSenseOligoID(insertID)
						#antisenseOligoID = iHandler.findAntisenseOligoID(insert_db_id)
			
						# Find Oligo sequences
						seqPropID = pHandler.findPropID("sequence")
						senseOligoSeqID = rHandler.findIndexPropertyValue(senseOligoID, seqPropID)
						senseOligoSequence = dnaHandler.findSequenceByID(senseOligoSeqID)
			
						# Fetch Insert sequence and find linkers from Oligo and Insert sequences
						insertSequence = dnaHandler.findSequenceByID(insertSeqID)
			
						attB_const = "ggggacaactttgtacaaaaaagttggc"
						fwd_primer_seq = senseOligoSequence[len(attB_const):]
			
						# First, find linkers from Oligos
						fwd_linker = dnaHandler.linker_from_oligo(insertSequence, fwd_primer_seq)
						#rev_linker = sHandler.linker_from_oligo(insertSequence, rev_primer_seq)
						rev_linker = ""
			
						# Now see if the Insert had its own linkers stored and append them to the Oligo linker
						fpLinkerPropID = pHandler.findPropID("5' linker")
						tpLinkerPropID = pHandler.findPropID("3' linker")
			
						fp_insert_linker = rHandler.findSimplePropertyValue(insertID, fpLinkerPropID)
						tp_insert_linker = rHandler.findSimplePropertyValue(insertID, tpLinkerPropID)
			
						if fp_insert_linker and len(fp_insert_linker) > 0 and fp_insert_linker != 0 and fp_insert_linker != '0':
							fp_insert_linker = fwd_linker + fp_insert_linker
						else:
							fp_insert_linker = fwd_linker
			
						tp_insert_linker = rev_linker
			
						insertLinkers.append(fp_insert_linker)
						insertLinkers.append(tp_insert_linker)
			
						try:
							newSeq = dnaHandler.entryVectorSequence(pvSeqKey, insertSeqID, insertLinkers)
							print newSeq
							
						except MultipleSiteOccurrenceException:
							e = MultipleSiteOccurrenceException("Sites found more than once on parent vector sequence")
							print `e.err_code()`
							
						except FivePrimeAfterThreePrimeException:
							e = FivePrimeAfterThreePrimeException("5' after 3'")
							print `e.err_code()`

						except InsertSitesNotFoundOnParentSequenceException:
							e = InsertSitesNotFoundOnParentSequenceException("Gateway sites not found on parent vector sequence")
							print `e.err_code()`
						
				else:
					# Non-Gateway non-recombination Vector
					fp_insert_cs = newFivePrime
					tp_insert_cs = newThreePrime
					
					if  fp_insert_cs:
						insertCloningSites.append(fp_insert_cs)
					else:
						insertCloningSites.append("")
	
					if  tp_insert_cs:
						insertCloningSites.append(tp_insert_cs)
					else:
						insertCloningSites.append("")
					
					# get linkers if there are any
					insertLinkers = []
	
					fpLinkerPropID = pHandler.findPropID("5' linker")
					tpLinkerPropID = pHandler.findPropID("3' linker")
					
					fp_insert_linker = rHandler.findSimplePropertyValue(insertID, fpLinkerPropID)
					tp_insert_linker = rHandler.findSimplePropertyValue(insertID, tpLinkerPropID)
	
					# sept. 3/07
					fwd_linker = ""
	
					if fp_insert_linker and len(fp_insert_linker) > 0 and fp_insert_linker != 0 and fp_insert_linker != '0':
						fp_insert_linker = fwd_linker + fp_insert_linker
					else:
						fp_insert_linker = fwd_linker
						
					insertLinkers.append(fp_insert_linker)
					insertLinkers.append(tp_insert_linker)
					
					try:
						newSeq = dnaHandler.constructNonRecombSequence(pvSeqID, insertSeqID, insertCloningSites, insertLinkers)
						print newSeq
					
					except (InsertSitesException):
						e = InsertSitesException("Could not reconstitute sequence: Unknown sites on Insert.")
						print e.err_code()
						
					except (InsertSitesNotFoundOnParentSequenceException):
						e = InsertSitesNotFoundOnParentSequenceException("Could not reconstitute sequence: Parent vector sequence does not contain restriction sites.")
						print e.err_code()
						
					except (MultipleSiteOccurrenceException):
						e = MultipleSiteOccurrenceException("Could not reconstitute sequence: Restriction sites occur more than once on parent vector sequence")
						print e.err_code()
						
					except (HybridizationException):
						e = HybridizationException("Could not reconstitute sequence: Restriction sites cannot be hybridized.")
						print e.err_code()
						
					except (FivePrimeAfterThreePrimeException):
						e = FivePrimeAfterThreePrimeException("Could not reconstitute sequence: 5' site occurs after 3' site on parent vector sequence.")
						print e.err_code()
			else:
				e = InvalidSequenceException("Invalid parent sequence")
				print `e.err_code()`
		else:
			e = UnknownPVIDException("Unknown PV ID")
			print `e.err_code()`
	
	elif cloning_method == '2':

		# Recombination vector - check IPV
		ipvVal = form.getvalue("IPV")
	
		if len(ipvVal) > 0:
			ipvID = rHandler.convertReagentToDatabaseID(ipvVal)
			ipvProjectID = int(rHandler.findSimplePropertyValue(ipvID, packetPropID))
		else:
			e = UnknownIPVIDException("Unknown IPV ID")
			print `e.err_code()`
	
		if ipvProjectID > 0 and currUser.getCategory() != 'Admin' and ipvProjectID not in uPackets:
			e = IPVProjectAccessException("Not authorized to view IPV")
			print `e.err_code()`
		
		if ipvID > 0 and pvID > 0:
			
			# If restriction sites were modified to anything other than LoxP or gateway att sites, clear the sequence
			if not (newFivePrime == newThreePrime and (newFivePrime.lower() == 'loxp' and newThreePrime.lower() == 'loxp') or (newFivePrime.lower() == 'attb' and newThreePrime.lower() == 'attb')):
				e = InsertSitesNotFoundOnParentSequenceException("Invalid restriction sites for Non-Recombination Clone - must be LoxP only")
				print `e.err_code()`
			else:
				# get internal db IDs
				pv_db_id = rHandler.convertReagentToDatabaseID(pvVal)
				ipv_db_id = rHandler.convertReagentToDatabaseID(ipvVal)
		
				# Get the Insert that belongs to the donor vector
				ipvInsertAssocID = raHandler.findReagentAssociationID(ipv_db_id)
				insertAssocPropID = aHandler.findAssocPropID("insert id")
				insert_db_id = aHandler.findAssocPropValue(ipvInsertAssocID, insertAssocPropID)
		
				# Construct a sequence for the new vector from the sequences of its parents
				pvSeqKey = rHandler.findDNASequenceKey(pv_db_id)
				#print "pv seq " + `pvSeqKey`
				ipvSeqKey = rHandler.findDNASequenceKey(ipv_db_id)
				#print "ipv seq " + `ipvSeqKey`
				insertSeqKey = rHandler.findDNASequenceKey(insert_db_id)
				#print "i seq " + `insertSeqKey`
		
				if pvSeqKey > 0 and ipvSeqKey > 0 and insertSeqKey > 0:
	
					# See if there are linkers, although there most likely aren't any
					insertLinkers = []
			
					fpLinkerPropID = pHandler.findPropID("5' linker")
					tpLinkerPropID = pHandler.findPropID("3' linker")
			
					fp_insert_linker = rHandler.findSimplePropertyValue(insert_db_id, fpLinkerPropID)
					tp_insert_linker = rHandler.findSimplePropertyValue(insert_db_id, tpLinkerPropID)
			
					# sept. 3/07
					fwd_linker = ""
			
					if fp_insert_linker and len(fp_insert_linker) > 0 and fp_insert_linker != 0 and fp_insert_linker != '0':
						fp_insert_linker = fwd_linker + fp_insert_linker
					else:
						fp_insert_linker = fwd_linker
			
					insertLinkers.append(fp_insert_linker)
					insertLinkers.append(tp_insert_linker)
			
					# Differentiate by cloning sites whether this is a recombination vector or a Gateway Expression Vector
					if newFivePrime == 'LoxP' and newThreePrime == 'LoxP':
						# recombination
						try:
							newSeq = dnaHandler.constructRecombSequence(pvSeqKey, ipvSeqKey, insertSeqKey, insertLinkers)
							newSeqID = dnaHandler.matchSequence(newSeq)
							print newSeq
						
						except InsertSitesNotFoundOnParentSequenceException:
							e = InsertSitesNotFoundOnParentSequenceException("LOXP sites not found on parent vector sequence")
							print `e.err_code()`

						except MultipleSiteOccurrenceException:

							e = MultipleSiteOccurrenceException("LOXP found more than once on parent vector sequence")
							print `e.err_code()`

					elif newFivePrime == 'attB' and newThreePrime == 'attB':
						
						# Gateway Expression
						iHandler = InsertHandler(db, cursor)
						
						senseOligoID = iHandler.findSenseOligoID(insert_db_id)
						#antisenseOligoID = iHandler.findAntisenseOligoID(insert_db_id)
			
						# Find Oligo sequences
						seqPropID = pHandler.findPropID("sequence")
						senseOligoSeqID = rHandler.findIndexPropertyValue(senseOligoID, seqPropID)
						senseOligoSequence = dnaHandler.findSequenceByID(senseOligoSeqID)
			
						# Fetch Insert sequence and find linkers from Oligo and Insert sequences
						insertSequence = dnaHandler.findSequenceByID(insertSeqKey)
			
						attB_const = "ggggacaactttgtacaaaaaagttggc"
						fwd_primer_seq = senseOligoSequence[len(attB_const):]
			
						# First, find linkers from Oligos
						fwd_linker = dnaHandler.linker_from_oligo(insertSequence, fwd_primer_seq)
						#rev_linker = sHandler.linker_from_oligo(insertSequence, rev_primer_seq)
						rev_linker = ""
			
						# Now see if the Insert had its own linkers stored and append them to the Oligo linker
						fpLinkerPropID = pHandler.findPropID("5' linker")
						tpLinkerPropID = pHandler.findPropID("3' linker")
			
						fp_insert_linker = rHandler.findSimplePropertyValue(insert_db_id, fpLinkerPropID)
						tp_insert_linker = rHandler.findSimplePropertyValue(insert_db_id, tpLinkerPropID)
			
						if fp_insert_linker and len(fp_insert_linker) > 0 and fp_insert_linker != 0 and fp_insert_linker != '0':
							fp_insert_linker = fwd_linker + fp_insert_linker
						else:
							fp_insert_linker = fwd_linker
			
						tp_insert_linker = rev_linker
			
						insertLinkers.append(fp_insert_linker)
						insertLinkers.append(tp_insert_linker)
						
						try:
							newSeq = dnaHandler.expressionVectorSequence(pvSeqKey, insertSeqKey, insertLinkers)
							print newSeq
						
						except MultipleSiteOccurrenceException:
							e = MultipleSiteOccurrenceException("Sites found more than once on parent vector sequence")
							print `e.err_code()`
							
						except FivePrimeAfterThreePrimeException:
							e = FivePrimeAfterThreePrimeException("5' after 3'")
							print `e.err_code()`

						except InsertSitesNotFoundOnParentSequenceException:
							e = InsertSitesNotFoundOnParentSequenceException("Gateway sites not found on parent vector sequence")
							print `e.err_code()`

				else:
					e = InvalidSequenceException("Invalid parent sequence")
					print `e.err_code()`
		else:
			e = ReagentDoesNotExistException("Unknown parent values")
			print `e.err_code()`
update()