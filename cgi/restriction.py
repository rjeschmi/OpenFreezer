#!/usr/local/bin/python

import cgi

import utils
from sequence_handler import DNAHandler
from database_conn import DatabaseConn

import Bio
from Bio.Seq import Seq
from Bio.Restriction import *

dbConn = DatabaseConn()
db = dbConn.databaseConnect()
cursor = db.cursor()

dnaHandler = DNAHandler(db, cursor)

form = cgi.FieldStorage()

lims_id = form.getvalue("limsID")		# always exists

print "Content-type: application/octet-stream"
print "Content-Disposition: attachment; filename=" + lims_id + ".txt"
print "Pragma: no-cache"
print "Expires: 0"
#print "Content-type:text/html"
print

# sequence may be empty
if form.has_key("vector_sequence"):
	mySeq = form.getvalue("vector_sequence")
	isLinear = False	# Do NOT call the variable 'linear' - it's a reserved keyword for function argument
	
	# Construct a Seq object readable by BioPython:
	rb = RestrictionBatch(AllEnzymes)
	
	bioSeq = Seq(mySeq)
	fSeq = FormattedSeq(bioSeq, linear=isLinear)             # default linear = TRUE
	
	analysis = Analysis(rb, fSeq)   # linear = true
	result = analysis.full()
	
	cutsDict = {}	# stores ('nCuts', 'enzyme') tuples
	nonCuts = []	# enzymes that don't cut the sequence
	
	for a in AllEnzymes.elements():
		enz = rb.get(a)
		#print result[enz]
		
		if result.has_key(enz):
			cuts = len(result[enz])
			
			if cuts > 0:
				if not cutsDict.has_key(cuts):
					cutsDict[cuts] = [enz]
				else:
					cutsDict[cuts].append(enz)
			else:
				nonCuts.append(enz)
		else:
			nonCuts.append(enz)
	
	print "Restriction map for " + lims_id + ", " + `len(mySeq)` + " nucleotides\n"
	
	nCuts = cutsDict.keys()
	nCuts.sort()
		
	for n in nCuts:
		print "Enzymes that cut the sequence " + `n` + " times:\n"
		print "Enzyme\tPositions\n"
		
		for e in cutsDict[n]:		# enzyme
			print `e` + '\t' + `result[e]`
	
		print ""
	
	#nonCuts.sort()		# do NOT use!!!!!!!!!!
	
	print "Enzymes that do not cut the sequence: \n"
	
	for e in nonCuts:
		print `e`
		
	print ""
	
	# output enzymes with their cutting positions, sorted numerically
	#analysis.print_as('number')
	#analysis.print_that(title="Restriction Map for " + lims_id + " (indicates exact cutting position on sequence):\n")
	
elif form.has_key("insert_sequence"):
	
	mySeq = form.getvalue("insert_sequence")	
	isLinear = True

	# Construct a Seq object readable by BioPython:
	bioSeq = Seq(mySeq)
	fSeq = FormattedSeq(bioSeq, linear=isLinear)             # default linear = TRUE

	# Construct a Seq object readable by BioPython:
	rb = RestrictionBatch(AllEnzymes)
	
	bioSeq = Seq(mySeq)
	fSeq = FormattedSeq(bioSeq, linear=isLinear)             # default linear = TRUE
	
	analysis = Analysis(rb, fSeq)   # linear = true
	result = analysis.full()
	
	# output enzymes with their cutting positions, sorted numerically
	cutsDict = {}	# stores ('nCuts', 'enzyme') tuples
	nonCuts = []	# enzymes that don't cut the sequence
	
	print "Restriction map for " + lims_id + ", " + `len(mySeq)` + " nucleotides\n"
	
	for a in AllEnzymes.elements():
		enz = rb.get(a)
		#print result[enz]
		
		if result.has_key(enz):
			cuts = len(result[enz])
			
			if cuts > 0:
				if not cutsDict.has_key(cuts):
					cutsDict[cuts] = [enz]
				else:
					cutsDict[cuts].append(enz)
			else:
				nonCuts.append(enz)
		else:
			nonCuts.append(enz)
		
	nCuts = cutsDict.keys()
	nCuts.sort()
		
	for n in nCuts:
		print "Enzymes that cut the sequence " + `n` + " times:\n"
		print "Enzyme\tPositions\n"
		
		for e in cutsDict[n]:		# enzyme
			print `e` + '\t' + `result[e]`
		
		print ""
	
	print "Enzymes that do not cut the sequence: \n"
	
	for e in nonCuts:
		print `e`
		
	print ""
	#analysis.print_as('number')
	#analysis.print_that(title="Restriction Map for " + lims_id + " (indicates exact cutting position on sequence):\n")
	
else:
	print("Sequence for " + lims_id + " is empty\n")