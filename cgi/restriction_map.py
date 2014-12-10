import cgi
import MySQLdb
import sys
import string
import Bio

from Bio.Seq import Seq
from Bio.Restriction import *

from general_handler import GeneralHandler
from reagent_handler import ReagentHandler, InsertHandler
from sequence_handler import SequenceHandler
#from system_set_handler import SystemSetHandler

from database_conn import DatabaseConn

from Numeric import *

# Creates a Restriction Map for reagent sequences (right now only doing for Insert - linear)
# Construct Bio.Seq object and build a restriction map

# Output restriction analysis for specific enzymes
def rMap_specific(f_in, f_out, enz_list):
	
	# Database connection parameters
	dbConn = DatabaseConn()
	db = dbConn.databaseConnect()
	cursor = db.cursor()
	
	gHandler = GeneralHandler(db, cursor)
	rHandler = ReagentHandler(db, cursor)
	seqHandler = SequenceHandler(db, cursor)

	infile = open(f_in, 'r')
	outfile = open(f_out, 'w')	

	for line in infile.readlines():
		reagentID = line.strip()	# e.g. V123
		rID = rHandler.convertReagentToDatabaseID(reagentID)
		seqID = rHandler.findDNASequenceKey(rID)

		if seqID:
			mySeq = seqHandler.findSequenceByID(seqID)
			
			# Construct a Seq object readable by BioPython:
			bioSeq = Seq(mySeq)
			fSeq = FormattedSeq(bioSeq, linear=False)		# linear = FALSE, we're dealing w/ VECTORS

			analysis = Analysis(enz_list, fSeq, linear=False)  	# linear = FALSE again for VECTORS
			result = analysis.full()

			outfile.write(reagentID + ": " + '\n')

			# calculate bandwidths
			allSites = []
			
			for enz in result.keys():
				sites = result[enz]
				
				if len(allSites) == 0:
					allSites = sites
				else:
					# concatenate
					for s in sites:
						allSites.append(s)

			allSites.sort()

			if len(allSites) > 0:
				
				# calculate distances between sites
				bandSizes = []
				
				i = 1
				
				while i < len(allSites):
					tmpLen = allSites[i] - allSites[i-1]
					bandSizes.append(tmpLen)
					i += 1
				
				# last band
				tmpLen = len(mySeq) - allSites[i-1] + allSites[0]
				bandSizes.append(tmpLen)
				
				bandSizes.sort()
				
				outfile.write(`bandSizes` + '\n')

			outfile.write('\n')
			
	infile.close()
	outfile.close()
	cursor.close()
	db.close()


# Full restriction map - REDO
# Anna's suggestion: Don't output the entire list of enzymes; set a cutoff by maximum number of cutting sites 3
# (i.e. output only enzymes that have a max. of 3 cutting sites and discard the rest)
def rMap_full(f_in, f_out):
	
	# Database connection parameters
	dbConn = DatabaseConn()
	db = dbConn.databaseConnect()
	cursor = db.cursor()

	gHandler = GeneralHandler(db, cursor)
	rHandler = ReagentHandler(db, cursor)
	seqHandler = SequenceHandler(db, cursor)

	infile = open(f_in, 'r')
	outfile = open(f_out, 'w')
	
	for line in infile.readlines():
		reagentID = line.strip()	# e.g. V123
		
		rID = rHandler.convertReagentToDatabaseID(reagentID)
		seqID = rHandler.findDNASequenceKey(rID)
		
		if seqID:
			mySeq = seqHandler.findSequenceByID(seqID)

                	# Construct a Seq object readable by BioPython:
			bioSeq = Seq(mySeq)
			fSeq = FormattedSeq(bioSeq)             # default linear = TRUE

			analysis = Analysis(AllEnzymes, fSeq)   # linear = true
			result = analysis.full()
			header = "==============================================\nRestriction analysis for " + reagentID + "\n==============================================\n"
			analysis.print_that(title=header+'\n\n')


#rMap_full('/home/olhovsky/anna.txt', '/home/olhovsky/anna_full_restriction.out')
#rMap_specific('/home/olhovsky/anna.txt', '/home/olhovsky/anna_specific_restriction.txt', ['HpaI', 'XbaI'])
rMap_specific('/home/olhovsky/anna.txt', '/home/olhovsky/anna_specific_restriction.txt', ['SpeI', 'PstI'])
