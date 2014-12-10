import string
import MySQLdb
import re
import sys
import math

from decimal import Decimal

from general_handler import GeneralHandler, ReagentPropertyHandler
from mapper import ReagentPropertyMapper
from sequence import *
from exception import *

import utils

import Bio
from Bio.Seq import Seq
#from Bio import Enzyme
from Bio.Restriction import *

################################################################################
# Module sequence_handler
# An interface to Sequences_tbl in OpenFreezer
#
# This module performs sequence translation, database retrieval and update
# Written October 11, 2006 by Marina Olhovsky
#
# Last modified: February 12, 2008
################################################################################

################################################################################
# SequenceHandler class
# Subclass of general Handler class, parent of other Sequence handlers
# Written October 11, 2006, by Marina Olhovsky
#
# Last modified: January 22, 2010
################################################################################
class SequenceHandler(GeneralHandler):
	"This class performs sequence translation, database retrieval and update"
	def __init__(self, db, cursor):
		self.db = db
		self.cursor = cursor
		
	# Utility function to filter whitespaces from sequence
	def filter_spaces(self, seq):
		new_seq = ""
		tokens = seq.split(" ")
			
		for i in tokens:
			new_seq += i
		
		return new_seq
	
	
	# Pull out the actual sequence (string) identified by seqID
	# Return: sequence = String
	def findSequenceByID(self, seqID):
		
		db = self.db
		cursor = self.cursor
		
		sequence = ""
		
		cursor.execute("SELECT `sequence` FROM `Sequences_tbl` WHERE `seqID`=" + `seqID` + " AND `status`='ACTIVE'")
		result = cursor.fetchone()	# must be unique
		
		if result:
			sequence = result[0].strip()
			
		return sequence
		
	
	# Set the status of the sequence identified by seqID to DEP
	def deleteSequence(self, seqID):
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE Sequences_tbl SET status='DEP' WHERE seqID=" + `seqID`)

	
	# Jan. 25, 2010
	def updateMolecularWeight(self, seqID, newMW):
		
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access

		cursor.execute("UPDATE Sequences_tbl SET mw=" + `newMW` + " WHERE seqID=" + `seqID` + " AND status='ACTIVE'")
	
################################################################################
# Class ProteinHandler
#
# Descendant of SequenceHandler, contains functions to translate DNA sequence into protein,
# locate existing protein sequences and store newly translated sequences in OpenFreezer database
#
# Written October 23, 2006, by Marina Olhovsky
################################################################################
class ProteinHandler(SequenceHandler):
	"Contains functions and attributes specific to protein sequences"
	
	def __init__(self, db, cursor):
		super(ProteinHandler, self).__init__(db, cursor)

	# Aug. 24/09: Amino acids

	# O and U are not used
	amino_acids = ['A', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'V', 'W', 'Y']
	
	# B, X, Z, J represent ambiguous amino acids
	ambiguous_aa = ['B', 'J', 'X', 'Z']
	
	peptideMassDict = {'A':71.08, 'C':103.14, 'D':115.09, 'E':129.12, 'F':147.18, 'G':57.05, 'H':137.14, 'I':113.16, 'K':128.17, 'L':113.16, 'M':131.19, 'N':114.1, 'P':97.12, 'Q':128.13, 'R':156.19, 'S':87.08, 'T':101.11, 'V':99.13, 'W':186.21, 'Y':163.18}
	
	# Construct a ProteinSequence object given its database identifier
	def createProteinSequence(self, seqID):
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT `sequence`, `frame`, `start`, `end` FROM `Sequences_tbl` WHERE `seqID`=" + `seqID` + " AND `status`='ACTIVE'")
		result = cursor.fetchone()
		
		if result:
			sequence = result[0]
			frame = int(result[1])
			start = int(result[2])
			end = int(result[3])
			
			return ProteinSequence(sequence, frame, start, end)
		
		return None
	
	
	# Aug. 24/09
	def squeeze(self, seq):
		new_seq = ""
		nextInd = 0
		
		while nextInd < len(seq):
			nextChar = seq[nextInd]
			
			if nextChar in self.amino_acids or nextChar in self.ambiguous_aa or nextChar == '*':
				new_seq += nextChar
				
			nextInd += 1
		
		return new_seq
	
	
	def translateAllFrames(self, sequence):
		
		longest = None
		max_len = 0
		
		results = []
		
		#print "Content-type:text/html"		# REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print					# DITTO
		
		triplets = DNAHandler.triplets
		
		for frame in range(1,4):
			
			protein = ""
			start = frame-1
			
			# CONVERT SEQUENCE TO UPPERCASE!!!!!!!!!!!!!!!!!!!!!!
			sequence = sequence.upper()
			seqLen = len(sequence)
			
			seqStart = -1
			seqEnd = -1
			
			# Translate the entire DNA sequence first:
			while start < seqLen:
				codon = sequence[start:start+3]
				
				if triplets.has_key(codon):
					protein += triplets[codon]
					start += 3
				else:
					start += 3
			
			#print protein
			chunks = protein.split("*")
			#print `chunks`
			
			for chunk in chunks:
				#if len(chunk) > 100:
				#print chunk
				met_start = chunk.find('M')
				#print met_start
				
				if met_start >= 0:
					#print met_start
					orf = chunk[met_start:] + '*'
					
					#if len(orf) > 100:
					#print "ORF " + orf
					
					subStart = protein.find(orf)
					subEnd = subStart + len(orf)
		
					# now map them back to dna
					seqStart = subStart*3 + frame
					#print seqStart
					seqEnd = seqStart + len(orf)*3 - 1
					#print seqEnd
					#print '\n'
					peptideMass = self.calculatePeptideMass(orf)
					#print `peptideMass`
					newProt = ProteinSequence(orf, frame, seqStart, seqEnd, peptideMass)
					
					if len(orf) > max_len:
						max_len = len(orf)
						longest = newProt
		
		#print longest.getSequence()
		return longest

	
	# Translate ALL frames and pick the longest
	# Returns: ProteinSequence OBJECT
	# Modified Feb. 26/08: Changed seqID parameter to 'seq' - an actual text value, not db ID
	#def translateAll(self, seqID, openClosed):
	def translateAll(self, seq, openClosed):
		
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		#print "Content-type:text/html"
		#print
		#print seq
		#print openClosed
		
		# Nov. 2/08: from latest backfill
		tmpLongest = None
	
		# Translate sequence in all frames and select the longest
		longest = 0		# length of longest ORF (reset to 0)
		longestORF = None	# protein sequence OBJECT
		longestFrame = -1	

		if openClosed and seq:	# added June 16/09	
			if len(seq) > 0 and len(openClosed) > 0:	# March 13/08 - if Insert is a non-coding sequence, open/closed will be blank
				
				frame = 1
			
				while frame <= 3:
					#print frame
					protEntry = self.translate(seq, frame, openClosed)
		
					# March 2, 2011
					if not protEntry:
						peptideSeq = ""
						sStart = -1
						sEnd = -1
					else:
						peptideSeq = protEntry.getSequence()
						#print peptideSeq
					
						sStart = protEntry.getSeqStart()
						sEnd = protEntry.getSeqEnd()
					
					if len(peptideSeq) > 0 and sStart > 0 and sEnd > 0:
						
						# compare length to previous ORFs
						if len(peptideSeq) > longest:
							longest = len(peptideSeq)
							longestORF = protEntry
							longestFrame = frame
					
					# increment loop counter
					frame += 1

		#print longestORF.getSequence()
		return longestORF


	def findVectorORFs(self, sequence, frame):
		triplets = {"TTT":'F', "TCT":'S', "TAT":'Y', "TGT":'C', "TTC":'F', "TCC":'S', "TAC":'Y', "TGC":'C', "TTA":'L', "TCA":'S', "TAA":'*', "TGA":'*', "TTG":'L', "TCG":'S', "TAG":'*', "TGG":'W', "CTT":'L', "CCT":'P', "CAT":'H', "CGT":'R', "CTC":'L', "CCC":'P', "CAC":'H', "CGC":'R', "CTA":'L', "CCA":'P', "CAA":'Q', "CGA":'R', "CTG":'L', "CCG":'P', "CAG":'Q', "CGG":'R', "ATT":'I', "ACT":'T', "AAT":'N', "AGT":'S', "ATC":'I', "ACC":'T', "AAC":'N', "AGC":'S', "ATA":'I', "ACA":'T', "AAA":'K', "AGA":'R', "ATG":'M', "ACG":'T', "AAG":'K', "AGG":'R', "GTT":'V', "GCT":'A', "GAT":'D', "GGT":'G', "GTC":'V', "GCC":'A', "GAC":'D', "GGC":'G', "GTA":'V', "GCA":'A', "GAA":'E', "GGA":'G', "GTG":'V', "GCG":'A', "GAG":'E', "GGG":'G'}
	
		protein = ""
		start = frame-1
		
		#print "Frame " + `frame`
		#print sequence
		results = []
		
		# CONVERT SEQUENCE TO UPPERCASE!!!!!!!!!!!!!!!!!!!!!!
		sequence = sequence.upper()
		seqLen = len(sequence)
		
		seqStart = -1
		seqEnd = -1
		
		# Translate the entire DNA sequence first:
		while start < seqLen:
			codon = sequence[start:start+3]
			
			if triplets.has_key(codon):
				protein += triplets[codon]
				start += 3
			else:
				start += 3

		#print protein
		chunks = protein.split("*")
		#print `chunks`
		
		for chunk in chunks:
			if len(chunk) > 100:
				#print chunk
				met_start = chunk.find('M')
				#print met_start
				
				if met_start >= 0:
					#print met_start
					orf = chunk[met_start:] + '*'
					
					if len(orf) > 100:
						#print "ORF " + orf
					
						subStart = protein.find(orf)
						subEnd = subStart + len(orf)
			
						# now map them back to dna
						seqStart = subStart*3 + frame
						#print seqStart
						seqEnd = seqStart + len(orf)*3 - 1
						#print seqEnd
						#print '\n'
						peptideMass = self.calculatePeptideMass(orf)
						
						results.append(ProteinSequence(orf, frame, seqStart, seqEnd, peptideMass))
		#print `results`
		#print
		return results
		

	################################################################################################
	# Written January 30, 2009
	# Return the molecular weight of a protein sequence based on its amino acid composition
	# Input:  protein: STRING
	# Return: mw: FLOAT
	#
	# Jan. 25/10: MW of ambiguous aa's is calculated based on NCBI rules as follows:
	# (quoting from http://www.ncbi.nlm.nih.gov/bookshelf/br.fcgi?book=helpentrez&part=EntrezHelp):
	# "If completely unknown amino acids (e.g., "X") are found, a molecular weight is not calculated. Ambiguous amino acids are calculated as one of their possible forms:
	# B means D or N -- molecular weight is calculated as D
	# Z means E or Q -- molecular weight is calculated as E"
	#
	################################################################################################
	def calculatePeptideMass(self, protein):
		
		mw = 0.0
		
		#print "Content-type:text/html"
		#print
		#print protein
		#print `self.peptideMassDict`
		
		protein = utils.squeeze(protein).strip()
		
		if len(protein) == 0 or protein.upper().find('X') >= 0:
			return 0.0
		
		for aa in protein:
			if aa == '*':
				break
			
			if self.peptideMassDict.has_key(aa.upper()):
				pm = self.peptideMassDict[aa.upper()]
				mw += pm
			elif aa.upper() == 'B':				# jan. 25/10
				pm = self.peptideMassDict['D']
				mw += pm
			elif aa.upper() == 'Z':				# jan. 25/10
				pm = self.peptideMassDict['E']
				mw += pm
			else:
				print "Content-type:text/html"
				print
				print "Unknown Amino Acid: " + aa
				return 0.0
	
		# add water weight - ROUGHLY 18
		mw += 18.0
	
		mw = mw/1000	# Feb. 1/10: in kDa
	
		#print "Content-type:text/html"
		#print
		#print mw
	
		#return ("%.2d", mw)[1]		# output to 2 decimal places
		return mw
		
		#TWOPLACES = Decimal(10) ** -2
		#return float(Decimal(str(mw)).quantize(TWOPLACES))


	# Compare two sequences to determine which is longest
	# Arguments: pep1, pep2 - ProteinSequence OBJECTS
	def selectLongest(self, pep1, pep2):
		
		# ignore null checks for now, assume both arguments are valid
		seq1 = pep1.getSequence()
		seq2 = pep2.getSequence()
		
		if len(seq1) >= len(seq2):
			return pep1
		else:
			return pep2
	
	
	# Translate a specific DNA frame into protein, taking into account its open/closed status
	# Return: ProteinSequence object
	def translate(self, sequence, frame, openClosed):
		
		#print "Content-type:text/html"
		#print
		
		#print "Reagent ID " + `reagentID`
		#print sequence
		#print "FRAME " + `frame`
		
		# All possible amino acid codons
		triplets = {"TTT":'F', "TCT":'S', "TAT":'Y', "TGT":'C', "TTC":'F', "TCC":'S', "TAC":'Y', "TGC":'C', "TTA":'L', "TCA":'S', "TAA":'*', "TGA":'*', "TTG":'L', "TCG":'S', "TAG":'*', "TGG":'W', "CTT":'L', "CCT":'P', "CAT":'H', "CGT":'R', "CTC":'L', "CCC":'P', "CAC":'H', "CGC":'R', "CTA":'L', "CCA":'P', "CAA":'Q', "CGA":'R', "CTG":'L', "CCG":'P', "CAG":'Q', "CGG":'R', "ATT":'I', "ACT":'T', "AAT":'N', "AGT":'S', "ATC":'I', "ACC":'T', "AAC":'N', "AGC":'S', "ATA":'I', "ACA":'T', "AAA":'K', "AGA":'R', "ATG":'M', "ACG":'T', "AAG":'K', "AGG":'R', "GTT":'V', "GCT":'A', "GAT":'D', "GGT":'G', "GTC":'V', "GCC":'A', "GAC":'D', "GGC":'G', "GTA":'V', "GCA":'A', "GAA":'E', "GGA":'G', "GTG":'V', "GCG":'A', "GAG":'E', "GGG":'G'}
	
		protein = ""
		start = frame-1
		
		# CONVERT SEQUENCE TO UPPERCASE!!!!!!!!!!!!!!!!!!!!!!
		sequence = sequence.upper()
		seqLen = len(sequence)
		
		seqStart = -1
		seqEnd = -1
		
		# Translation varies by open/closed values
		# Guidelines: open = no stop codon; closed = stop codon; ATG = self-explanatory
		
		openClosed = openClosed.lower()		# convert all to lowercase, since db values are inconsistent
	
		if openClosed == "open with atg":
			# From first ATG to end of sequence
			#seqEnd = len(sequence)		# open, hence translation ends at last nucleotide
			#print seqEnd
			#seqEnd = len(sequence) - 1
	
			while start < seqLen:
				codon = sequence[start:start+3]
				
				if triplets.has_key(codon):
					if codon != 'ATG' and len(protein) == 0:
						# ORF not found yet, keep looking
						start += 3	
					elif triplets[codon] == '*':
						# Not allowed, this is the wrong frame.  Disregard the sequence altogether
						protein = ""
						break
					else:
						if codon == 'ATG' and len(protein) == 0:
							# Record start of translation
							#seqStart = start + frame-1	# rmvd sept. 11/08
							seqStart = start + 1		# added sept. 11/08
							#print `seqStart`
							
						protein += triplets[codon]
						start += 3
				else:
					start += 3

			seqEnd = seqStart + len(protein)*3 - 1
					
		elif openClosed == "open no atg" or openClosed == "open, no atg":
			# From start to end
			seqStart = frame
			#seqStart = 0
			#seqEnd = len(sequence)
			#seqEnd = len(sequence) - 1
			
			while start < seqLen:
				codon = sequence[start:start+3]
				
				if triplets.has_key(codon):
					if triplets[codon] == '*':
						# Not allowed, this is the wrong frame.  Discared the sequence
						protein = ""
						break
					else:
						protein += triplets[codon]
						start += 3
				else:
					start += 3

			seqEnd = seqStart + len(protein)*3 - 1
	
		elif openClosed == "closed with atg":
			# From ATG to first stop codon
			while start < seqLen:
				codon = sequence[start:start+3]
				
				if triplets.has_key(codon):
					if codon != 'ATG' and len(protein) == 0:
						# ORF not found yet, keep looking
						start += 3
					elif codon == 'ATG' and len(protein) == 0:
						seqStart = start+1		# record start of translation
						#seqStart = start
						protein += triplets[codon]
						start += 3
					else:
						protein += triplets[codon]
							
						if triplets[codon] != '*':	# have not reached a stop codon
							# advance counter
							start += 3
						else:
							seqEnd = start+3	# record end of translation
							#seqEnd = start
							
							#print "Content-type:text/html"
							#print
							#print seqEnd
							break			# reached a stop codon, discard the rest of the sequence
				else:
					start += 3
			
			
		elif openClosed == "closed, no atg" or openClosed == "closed no atg":		# inconsistent db values
			
			# From start to first stop codon
			seqStart = frame
			#seqStart = 0
			
			while start < seqLen:
				codon = sequence[start:start+3]
				
				if triplets.has_key(codon):
					protein += triplets[codon]
					
					if triplets[codon] != '*':	# have not reached a stop codon
						# advance counter
						start += 3
					else:
						seqEnd = start+3	# end of translation
						#seqEnd = start
						break			# reached a stop codon, discard the rest of the sequence
				else:
					start += 3
		
		elif openClosed == "special cdna with utrs":
			
			# Translate the entire DNA sequence first:
			while start < seqLen:
				codon = sequence[start:start+3]
				
				if triplets.has_key(codon):
					protein += triplets[codon]
					start += 3
				else:
					start += 3
					
			# Find longest subsequence between 2 stop codons
			lastStopIndex = len(protein)
			longestSub = ""
			longest = -1
			
			original = protein

			# March 2, 2011: What if the frame doesn't contain a stop codon at all?? discard it!!
			if protein.find("*") < 0:
				# wrong frame
				return

			#print "ORIGINAL " + original

			while lastStopIndex > 0:
				
				tmp_end_1 = protein.rfind('*')
				sub1 = protein[0:tmp_end_1]
				
				tmp_end_2 = sub1.rfind('*')
				sub2 = sub1[tmp_end_2+1:]
				
				metIndex = sub2.find('M')
				
				if metIndex >= 0:
					tmp_orf = sub2[metIndex:]
					
					if len(tmp_orf) > longest:
						longestSub = tmp_orf
						longest = len(tmp_orf)
	
				#print "Longest subseq b/w stop codons: " + longestSub
	
				protein = sub1
				lastStopIndex = len(protein)
				
			#print "Longest ORF: " + longestSub
			
			# Start and stop index of longest ORF in original DNA sequence:
			# Modified Nov. 7/06
			
			# March 2, 2011
			if len(longestSub) > 0:
		
				# Aug. 25/08: these are the positions in the translated Protein sequence!!
				subStart = original.find(longestSub)
				subEnd = subStart + len(longestSub)
				
				# now map them back to dna
				seqStart = subStart*3 + frame
				#seqStart = subStart*3
				
				##############################################################
				# This is the optimal formula, don't change!!!
				#
				# seqEnd varies according to frame as follows:
				#
				# F3: seqEnd = seqStart + len(longestSub)*3 + frame-1
				# F2: seqEnd = seqStart + len(longestSub)*3 + frame
				# F1: seqEnd = seqStart + len(longestSub)*3 + frame + 1
				#
				# Hence, for all frames the common formula is:
				#
				# seqEnd = seqStart + len(longestSub)*3 + 2
				#
				##############################################################
				
				#seqEnd = seqStart + len(longestSub)*3 + 2
				#seqEnd = seqStart + len(longestSub)*3 + frame + 1
				
				# include last codon
				protein = original[subStart:subEnd+1]
			else:
				protein = longestSub

			seqEnd = seqStart + len(protein)*3 - 1

		# Dec. 18/09
		elif openClosed == "non-coding" or openClosed == "none":
			pass
		
		else:
			print "Content-type:text/html"
			print
			print "Unknown Insert Type/Open Closed value: " + openClosed
			print sequence


		return ProteinSequence(protein, frame, seqStart, seqEnd)
		
	
	# Parameters: seq = ProteinSequence object
	def matchSequence(self, seq):
		db = self.db			# localize for easy access
		cursor = self.cursor		# ditto
		newProtSeqID = 0
		
		if seq.isProtein():
			prot_seq = seq.getSequence()
			frame = seq.getFrame()
			start = seq.getSeqStart()
			end = seq.getSeqEnd()
			prot_len = len(prot_seq)
			
			if prot_len > 0:
					
				cursor.execute("SELECT `seqID` FROM `Sequences_tbl` WHERE `seqTypeID`='2' AND `sequence`=" + `prot_seq` + " AND `frame`=" + `frame` + " AND `start`=" + `start` + " AND `end`=" + `end` + " AND `length`=" + `prot_len` + " AND `status`='ACTIVE'")
				result = cursor.fetchone()	# technically there should only be one; if more it's an error, but just assume one for now and add error checks later

				if result:
					newProtSeqID = int(result[0])
					
		return int(newProtSeqID)
	

	# Insert a new sequence into OpenFreezer
	# Parameters: seq = ProteinSequence object
	def insertSequence(self, seq):
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		newProtSeqID = 0
		
		if seq.isProtein():
			prot_seq = seq.getSequence()
			frame = seq.getFrame()
			start = seq.getSeqStart()
			end = seq.getSeqEnd()
			mw = seq.getMW()	# jan. 22, 2010
			
			# March 3/10
			if not mw:
				mw = self.calculatePeptideMass(prot_seq)
			
			#print "Content-type:text/html"
			#print
			#print prot_seq
			#print mw
			
			prot_len = len(prot_seq)
			
			if prot_len > 0:
				cursor.execute("INSERT INTO `Sequences_tbl`(`seqTypeID`, `sequence`, `frame`, `start`, `end`, `length`, mw) VALUES('2', " + `prot_seq` + ", " + `frame` + ", " + `start` + ", " + `end` + ", " + `prot_len` + ", " + `mw` + ")")
				newProtSeqID = int(db.insert_id())
			
		return newProtSeqID
	
	
	# Return the internal database identified of the given sequence argument
	# Parameters: seq = ProteinSequence OBJECT
	# Return: seqID = int; internal database identifier
	def getSequenceID(self, seq):
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access

		# Feb. 26/08: Make sure seq is not null
		if seq:
			if seq.isProtein():
				prot_seq = seq.getSequence()
				frame = seq.getFrame()
				start = seq.getSeqStart()
				end = seq.getSeqEnd()
				prot_len = len(prot_seq)
	
				if len(prot_seq) > 0:
					# First check if this sequence is already stored in LIMS
					newProtSeqID = self.matchSequence(seq)
				
					if newProtSeqID == 0:
						newProtSeqID = self.insertSequence(seq)
						
					return newProtSeqID
		return -1

	
##################################################################################
# Class DNAHandler
# Descendant of SequenceHandler, handles DNA sequence operations
# Written October 27, 2006, by Marina Olhovsky
##################################################################################
class DNAHandler(SequenceHandler):
	"Contains functions and attributes specific to DNA sequences"

	# global variable
	sitesDict = {}
	enzDict = {}
	
	allEnzymes = Bio.Restriction.AllEnzymes
	
	for enz in allEnzymes:
		enzSeq = enz.site
		sitesDict[enz.__name__] = enzSeq
		enzDict[enz.__name__] = enz

	# Jan. 19/09: Degenerate nucleotides
	degenerateNucleotides = ['R', 'M', 'W', 'K', 'S', 'Y', 'H', 'B', 'D', 'N', 'V']
	degenerateNucleotidesDictionary = {'R':['A', 'G'], 'M':['A', 'C'], 'W':['A', 'T'], 'K':['G', 'T'], 'S':['G', 'C'], 'Y':['C', 'T'], 'H':['A', 'T', 'C'], 'B':['G', 'T', 'C'], 'D':['G', 'A', 'T'], 'N':['A', 'C', 'G', 'T'], 'V':['G', 'A', 'C']}
	
	# Aug. 10/09
	triplets = {"TTT":'F', "TCT":'S', "TAT":'Y', "TGT":'C', "TTC":'F', "TCC":'S', "TAC":'Y', "TGC":'C', "TTA":'L', "TCA":'S', "TAA":'*', "TGA":'*', "TTG":'L', "TCG":'S', "TAG":'*', "TGG":'W', "CTT":'L', "CCT":'P', "CAT":'H', "CGT":'R', "CTC":'L', "CCC":'P', "CAC":'H', "CGC":'R', "CTA":'L', "CCA":'P', "CAA":'Q', "CGA":'R', "CTG":'L', "CCG":'P', "CAG":'Q', "CGG":'R', "ATT":'I', "ACT":'T', "AAT":'N', "AGT":'S', "ATC":'I', "ACC":'T', "AAC":'N', "AGC":'S', "ATA":'I', "ACA":'T', "AAA":'K', "AGA":'R', "ATG":'M', "ACG":'T', "AAG":'K', "AGG":'R', "GTT":'V', "GCT":'A', "GAT":'D', "GGT":'G', "GTC":'V', "GCC":'A', "GAC":'D', "GGC":'G', "GTA":'V', "GCA":'A', "GAA":'E', "GGA":'G', "GTG":'V', "GCG":'A', "GAG":'E', "GGG":'G'}
	
	nucleotides = ['A', 'a', 'C', 'c', 'G', 'g', 'T', 't', 'N', 'n']

	#print "Content-type:text/html"
	#print
	#print `sitesDict`
	
	#sitesDict = {"AgeI":"A'CCGGT", "ApaI":"GGGCC'C", "AscI":"GG'CGCGCC", "AvrII":"C'CTAGG", "BamHI":"G'GATCC", "BglII":"A'GATCT", "BsrGI":"T'GTACA", "BstBI":"TT'CGAA", "ClaI":"AT'CGAT", "EcoRI":"G'AATTC", "EcoRV":"GAT'ATC", "HindIII":"A'AGCTT", "HpaI":"GTT'AAC", "KpnI":"GGTAC'C", "MfeI":"C'AATTG", "MluI":"A'CGCGT", "MscI":"TGG'CCA", "NarI":"GG'CGCC", "NcoI":"C'CATGG", "NdeI":"CA'TATG", "NheI":"G'CTAGC", "NotI":"GC'GGCCGC", "PacI":"TTAAT'TAA", "PstI":"CTGCA'G", "SacI":"GAGCT'C", "SalI":"G'TCGAC", "SbfI":"CCTGCA'GG", "SmaI":"CCC'GGG", "SrfI":"GCCC'GGGC", "XbaI":"T'CTAGA", "XhoI":"C'TCGAG"}
	
	# Modified May 29/08: Discussed with Karen and agreed on different core sequences
	gatewayDict = {'attB1':"gtacaaaaaa", 'attB2':"tttcttgtac", 'attL1':"TTTGTACAAAAAA", 'attL2':"TTTCTTGTACAAAGT", 'attP1':"TTTGTACAAAAAA", 'attP2':"TTTCTTGTACAAAGT", 'attR1':"TTTGTACAAAAAA", 'attR2':"TTTCTTGTAC"}
	
	recombDict = {'LoxP':"ATAACTTCGTATAGCATACATTATACGAAGTTAT"}
	
	# Added May 29/08: Join all sites into one dictionary
	sitesDict = utils.join(sitesDict, gatewayDict)
	sitesDict = utils.join(sitesDict, recombDict)		# add LoxP
	sitesDict['None'] = ""					# add 'None'

	def __init__(self, db, cursor):
		super(DNAHandler, self).__init__(db, cursor)	


	# Squeeze unwanted characters from DNA sequence, leaving only nucleotides - A, C, G, T
	# Update June 5/09: include N
	# Moved into DNAHandler on Aug. 24/09
	def squeeze(self, seq):
		new_seq = ""
		nextInd = 0
		
		while nextInd < len(seq):
			nextChar = seq[nextInd]
			
			if nextChar in self.nucleotides:
				new_seq += nextChar
				
			nextInd += 1
		
		return new_seq


	# Construct a DNASequence object given its database identifier
	def createDNASequence(self, seqID):
		db = self.db
		cursor = self.cursor
		
		sequence = self.findSequenceByID(seqID)
		
		return DNASequence(sequence)
		

	# Parameters: seq = STRING, not object!
	def matchSequence(self, dna_seq):
		db = self.db			# localize for easy access
		cursor = self.cursor		# ditto
		newDNASeqID = 0
		
		# no frame/start/end for DNA sequences; seqType = 1; length is not always (in fact, never should be) stored in Sequences_tbl, only match sequence by string comparison and its status should be active
		cursor.execute("SELECT `seqID` FROM `Sequences_tbl` WHERE `seqTypeID`='1' AND sequence=" + `dna_seq` + " AND `status`='ACTIVE'")
		
		# should only be one; if there's more than one it is an error, but just assume one for now and add error checking later
		result = cursor.fetchone()

		if result:
			newDNASeqID = int(result[0])
		
		return newDNASeqID
	
	
	# Insert a new DNA sequence into OpenFreezer
	# Parameters: seq = STRING (not object!)
	def insertSequence(self, seq):
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		newDNASeqID = 0
		
		if len(seq) > 0:
			cursor.execute("INSERT INTO `Sequences_tbl`(`seqTypeID`, `sequence`) VALUES('1', " + `seq` + ")")
			newDNASeqID = int(db.insert_id())
			
		return newDNASeqID
	
	
	# Return the internal database identified of the given sequence argument
	# Parameters: seq = String (not object!)
	# Return: seqID = int; internal database identifier
	def getSequenceID(self, seq):
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access

		# filter spaces
		seq = self.filter_spaces(seq)
		seq = self.squeeze(seq)
		
		if len(seq) > 0:
			# First check if this sequence is already stored in OF
			newDNASeqID = self.matchSequence(seq)
			
			if newDNASeqID <= 0:
				newDNASeqID = self.insertSequence(seq)
			
			return newDNASeqID
		
		return -1


	# June 23, 2010: Calculate GC content of DNA sequence as percentage
	def calculateGC(self, sequence):	
		sequence = utils.squeeze(sequence.upper()).strip()

		#print "Content-type:text/html"
		#print
		#print sequence
		#print utils.numOccurs(sequence, 'G')
		#print utils.numOccurs(sequence, 'C')
		#print len(sequence)

		if len(sequence) > 0:
			gc_cont = 100*(float(utils.numOccurs(sequence, 'G')) + float(utils.numOccurs(sequence, 'C'))) / float(len(sequence))
			return round(gc_cont, 2)
	
		#print gc_cont
		#print round(gc_cont, 2)

		return 0

		
	# Jan. 22, 2010: Calculate the molecular weight of a DNA sequences
	def calculateMW(self, sequence):
		
		mw = 0.0
		sequence = sequence.upper()
		
		if len(sequence) == 0:
			return -1
		
		mw_dict = {'A': 313.209, 'G':329.208, 'C':289.184, 'T':304.196}
		
		num_A = float(sequence.count('A'))
		num_C = float(sequence.count('C'))
		num_G = float(sequence.count('G'))
		num_T = float(sequence.count('T'))
		
		weight_A = num_A * mw_dict['A']
		weight_C = num_C * mw_dict['C']
		weight_G = num_G * mw_dict['G']
		weight_T = num_T * mw_dict['T']
		
		base_weight = float(weight_A) + float(weight_C) + float(weight_G) + float(weight_T)
		
		mw = base_weight - 61.964
		
		return round(mw, 2)
	
		#TWOPLACES = Decimal(10) ** -2
		#print "Content-type:text/html"
		#print
		#print Decimal(str(mw)).quantize(TWOPLACES)
		#return Decimal(str(mw)).quantize(TWOPLACES)


	# Jan. 22, 2010
	def calculateTm(self, sequence):
		tm = 0.0
		
		sequence = sequence.upper()
		
		if len(sequence) == 0:
			return -1
		
		seqLen = float(len(sequence))

		num_A = float(sequence.count('A'))
		num_C = float(sequence.count('C'))
		num_G = float(sequence.count('G'))
		num_T = float(sequence.count('T'))
		
		if seqLen < 14.0:
			tm = 2 * (num_A + num_T) + 4 * (num_G + num_C)
		else:
			tm = 64.9 + 41.0 * (num_G + num_C - 16.4) / seqLen
		
		return utils.trunc(round(tm, 2), 2)	# can I just use round(), w/o trunc??
		
		#TWOPLACES = Decimal(10) ** -2
		#return Decimal(str(tm)).quantize(TWOPLACES)


	#############################################################################################################################
	# The next functions were added on March 27/07 by Marina to construct vector sequence from the sequences of its parents
	#############################################################################################################################

	#####################################################################################################
	# Returns true if 'site' is an array, representing a hybrid restriction site
	#####################################################################################################
	def isHybrid(self, site):
	   return utils.isList(site)

	#####################################################################################################
	# Returns the first portion of a hybrid site (in Python terms, the first element of array 'h_site')
	#####################################################################################################
	def get_H_1(self, h_site):
		return h_site[0].strip()


	#####################################################################################################
	# Returns the first portion of a hybrid site (in Python terms, the first element of array 'h_site')
	#####################################################################################################
	def get_H_2(self, h_site):
		return h_site[1].strip()


	# Returns an Enzyme object matching enzName
	def makeEnzyme(self, enzName):
		for e in AllEnzymes:
			if e.__name__ == enzName:
				return e
			
		return None
		
		
	# Returns the reverse complement of a sequence
	def reverse_complement(self, seq):
		bioSeq = Bio.Seq.Seq(seq)
		return bioSeq.reverse_complement().tostring().lower()


	#####################################################################################################
	# Generates a sequence for the given hybrid restriction site
	# CHANGES MADE OCTOBER 19/07 - CURRENT ENZYME LIST REPLACED WITH BIOPYTHON RESTRICTION PACKAGE
	#####################################################################################################
	def hybridSeq(self, site):
	    hybrid_seq = None

	    # Get individual sites from hybrid
	    h1 = self.makeEnzyme(self.get_H_1(site))
	    h2 = self.makeEnzyme(self.get_H_2(site))

	    # get their individual sequences
	    h1_site = h1.site
	    h2_site = h2.site

	    # March 5, 2010: Check compatibility of 5' Insert to 5' Vector and 3' Insert to 3' vector
	    if h2 not in h1.compatible_end(): 
		raise HybridizationException("")

	    if not self.isDegenerate(h1) and not self.isDegenerate(h2):
		h1_seq = h1.elucidate().replace("_", "")
		h2_seq = h2.elucidate().replace("_", "")
	
		# The sticky ends from two molecules cut with two different restriction enzymes can be joined if the overhangs can hybridize
		# meaning, the 3' overhang is reverse complement to the 5' overhang
		# However, since the sequences are given to us as one strand (5' strand), compare the overhangs to see if they're the same
	
		# Find the cutting position (cleavage) - marked by "^"
		fp_clvg = h1_seq.find("^")
		tp_clvg = h2_seq.find("^")
		
		#print "Content-type:text/html"
		#print
		
		#print h1
		#print h1_seq
		#print h2
		
		#print fp_clvg
	
		# get the flanking ends
		# CHANGE MARCH 5, 2010: The enzyme may actually cut 3'->5'
		if fp_clvg < h1.elucidate().find("_"):
			fp_flank = h1_seq[0:fp_clvg]
		else:
			fp_flank = h1_seq[len(h1_seq)-fp_clvg:]
			
		if tp_clvg > h2.elucidate().find("_"):
			tp_flank = h2_seq[0:tp_clvg]
		else:
			tp_flank = h2_seq[len(h2_seq)-tp_clvg:]     # cleaved nts from ***end*** of seq
			
		#print "5' flank: " + fp_flank
		#print "3' flank: " + tp_flank
	
		# Find overhang - March 5, 2010: get the overhang between the "_" and "^"
		fp_oh = h1_seq[fp_clvg+1:len(h1_seq)-fp_clvg]
		tp_oh = h2_seq[tp_clvg+1:len(h2_seq)-tp_clvg]
	
		# compare the overhangs to see if they're the same
		if fp_oh == tp_oh:
			# hybridize
			hybrid_seq = fp_flank + fp_oh + tp_flank
			
	    	return hybrid_seq
	
	    else:
		raise DegenerateHybridException("")


	#########################################################################################
	# Fill in hybrid restriction sites
	# Return new vector sequence
	#########################################################################################
	def hybrid(self, five_site, three_site, pvSeq, insertSeq):
		newSeq = ""
		
		#print "Content-type:text/html"
		#print
		#print pvSeq
		#print insertSeq
		
		parentVectorSequence = Seq(pvSeq)
		insertSequence = Seq(insertSeq)
		
		if not self.isHybrid(five_site):
			five_pv_1 = self.enzDict[five_site]
			five_pv_2 = self.enzDict[five_site]
		else:
			five_pv_1 = self.enzDict[self.get_H_1(five_site)]
			five_pv_2 = self.enzDict[self.get_H_2(five_site)]
			
		#print five_pv_1
		#print five_pv_2
		
		if not self.isHybrid(three_site):
			three_pv_1 = self.enzDict[three_site]
			three_pv_2 = self.enzDict[three_site]
		else:
			three_pv_1 = self.enzDict[self.get_H_1(three_site)]
			three_pv_2 = self.enzDict[self.get_H_2(three_site)]
		
		#print three_pv_1
		#print three_pv_2
		
		# Added here Jan. 14/09, after V2229 sequence construction failed (single BamHI on PV and 5'=3'=BglII on Insert) => failed b/c 'linear' was set to 'True'
		if five_pv_2 != three_pv_1:
			linear = False
		else:
			# Do NOT use linear=False in searching Insert sequence for IDENTICAL sites!!! (e.g. EcoRI children of V2327 Topo)
			linear = True
		
		#print linear
		
		#print five_pv_1.ovhgseq
		#print five_pv_2.ovhgseq
		
		if not five_pv_1.is_ambiguous() and not five_pv_2.is_ambiguous() and not three_pv_1.is_ambiguous() and not three_pv_2.is_ambiguous():
			# Nov. 18/08: COMPARE OVERHANGS!!!!!!!
			if five_pv_1.ovhgseq != five_pv_2.ovhgseq or three_pv_1.ovhgseq != three_pv_2.ovhgseq:
				raise HybridizationException("")
		
		# still, we don't just need a check here, we need the actual degenerate sequence from parents
		#elif self.isDegenerate(five_pv_1) or self.isDegenerate(five_pv_2):
			#if five_pv_2 not in five_pv_1.compatible_end():
				#raise HybridizationException("")
		
		#elif  self.isDegenerate(three_pv_1) or self.isDegenerate(three_pv_2):
			#if three_pv_2 not in three_pv_1.compatible_end():
				#raise HybridizationException("")
		
			pv_five_prime_pos = five_pv_1.search(parentVectorSequence, linear)
			#print `pv_five_prime_pos`
			
			pv_five_prime_pos.sort()
			
			if len(pv_five_prime_pos) == 0:
				raise InsertSitesNotFoundOnParentSequenceException("")
			
			# Feb. 6/09: FIRST OF ALL, CHECK THAT BOTH SITES ACTUALLY EXIST ON THE INSERT (i.e. that search() 5' != search() 3')
			insert_five_prime_pos = five_pv_2.search(insertSequence, linear)
			insert_three_prime_pos = three_pv_1.search(insertSequence, linear)
			
			insert_five_prime_pos.sort()
			insert_three_prime_pos.sort()
			
			#print `insert_five_prime_pos`
			#print `insert_three_prime_pos`
			
			if len(insert_five_prime_pos) == 1 and len(insert_three_prime_pos) == 1:
				if insert_five_prime_pos[0] == insert_three_prime_pos[0]:
					raise CloningSitesNotFoundInInsertException("")
				
			if len(insert_five_prime_pos) == 0:
				raise CloningSitesNotFoundInInsertException("Insert sequence does not contain restriction site")
			
			if len(insert_three_prime_pos) == 0:
				raise CloningSitesNotFoundInInsertException("Insert sequence does not contain restriction site")
			
			fp_start_pv = int(pv_five_prime_pos[0]) - 1
			pvSeqPre = pvSeq[0:fp_start_pv].upper()
			
			# get the remaining portion of the cloning site from the Insert
			fp_start_insert = int(insert_five_prime_pos[0]) - 1
			
			# for the 3' site on Insert, grab its LAST cutting position
			# Updated Nov. 27/08: Add argument linear=False, only then it would consider a case where the cut occurs at the last nucleotide (e.g. ApaI)
			#if len(insert_three_prime_pos) > 0:
			tp_end_insert = int(insert_three_prime_pos[len(insert_three_prime_pos)-1]) - 1
			#print `tp_end_insert`
			
			# March 10/10: check 5' before 3' on INSERT sequence!!!!
			tp_start_insert = int(insert_three_prime_pos[0])-1
			#print tp_start_insert
			
			if fp_start_insert > tp_start_insert:
				raise InsertFivePrimeAfterThreePrimeException("")
			
			insertSeq = insertSeq[fp_start_insert:tp_end_insert].lower()
			#print insertSeq
			
			# Get the LAST cutting position of the 3' site on PV sequence
			pv_three_prime_pos = three_pv_2.search(parentVectorSequence, linear)
			#print `pv_three_prime_pos`
			pv_three_prime_pos.sort()
			
			if len(pv_three_prime_pos) == 0:
				raise InsertSitesNotFoundOnParentSequenceException("")
			
			tp_end_pv = int(pv_three_prime_pos[len(pv_three_prime_pos)-1]) - 1
			
			pvSeqPost = pvSeq[tp_end_pv:].upper()
			
			newSeq = pvSeqPre + insertSeq + pvSeqPost
			return newSeq
		else:
			# For degenerate sites, read the overhang directly from the parent sequence
			pv_five_prime_pos = five_pv_1.search(parentVectorSequence, linear)
			#print `pv_five_prime_pos`
			
			pv_five_prime_pos.sort()
			
			if len(pv_five_prime_pos) == 0:
				raise InsertSitesNotFoundOnParentSequenceException("")
			
			# Feb. 6/09: FIRST OF ALL, CHECK THAT BOTH SITES ACTUALLY EXIST ON THE INSERT (i.e. that search() 5' != search() 3')
			insert_five_prime_pos = five_pv_2.search(insertSequence, linear)
			insert_three_prime_pos = three_pv_1.search(insertSequence, linear)
			
			insert_five_prime_pos.sort()
			insert_three_prime_pos.sort()
			
			#print `insert_five_prime_pos`
			#print `insert_three_prime_pos`
			
			if len(insert_five_prime_pos) == 1 and len(insert_three_prime_pos) == 1:
				if insert_five_prime_pos[0] == insert_three_prime_pos[0]:
					raise CloningSitesNotFoundInInsertException("")
				
			if len(insert_five_prime_pos) == 0:
				raise CloningSitesNotFoundInInsertException("Insert sequence does not contain restriction site")
			
			if len(insert_three_prime_pos) == 0:
				raise CloningSitesNotFoundInInsertException("Insert sequence does not contain restriction site")
			
			fp_start_pv = int(pv_five_prime_pos[0]) - 1
			
			# March 5, 2010: Differentiate between 5' overhang and 3' overhang!!!!!!!!!!
			if five_pv_1.ovhg < five_pv_1.elucidate().find("_"):
				fp_pv_ovhg = pvSeq[fp_start_pv:fp_start_pv+len(five_pv_1.ovhgseq)]
			else:
				fp_pv_ovhg = pvSeq[fp_start_pv-len(five_pv_1.ovhgseq):fp_start_pv]
				
			#print "5' PV overhang?????? " + fp_pv_ovhg
			
			pvSeqPre = pvSeq[0:fp_start_pv].upper()
			
			#print pvSeqPre
			
			# get the remaining portion of the cloning site from the Insert
			fp_start_insert = int(insert_five_prime_pos[0]) - 1
			
			#print five_pv_2.ovhgseq
			#print int(insert_five_prime_pos[0])
			
			if five_pv_2.ovhg < five_pv_2.elucidate().find("_"):
				fp_insert_ovhg = insertSeq[fp_start_insert:fp_start_insert+len(five_pv_2.ovhgseq)]
			else:
				fp_insert_ovhg = insertSeq[fp_start_insert-len(five_pv_2.ovhgseq):fp_start_insert]
					
			#print "5' insert overhang: " + fp_insert_ovhg
			
			if fp_pv_ovhg.upper() != fp_insert_ovhg.upper():
				raise HybridizationException("")
			
			# for the 3' site on Insert, grab its LAST cutting position
			# Updated Nov. 27/08: Add argument linear=False, only then it would consider a case where the cut occurs at the last nucleotide (e.g. ApaI)
			#if len(insert_three_prime_pos) > 0:
			tp_end_insert = int(insert_three_prime_pos[len(insert_three_prime_pos)-1]) - 1
			#print `tp_end_insert`
			tp_start_insert = int(insert_three_prime_pos[0]) - 1
			tp_insert_ovhg = insertSeq[tp_start_insert:tp_start_insert+len(three_pv_1.ovhgseq)]
			#print "3' insert overhang: " + tp_insert_ovhg
			
			insertSeq = insertSeq[fp_start_insert:tp_end_insert].lower()
			#print insertSeq
			
			# Get the LAST cutting position of the 3' site on PV sequence
			pv_three_prime_pos = three_pv_2.search(parentVectorSequence, linear)
			#print `pv_three_prime_pos`
			pv_three_prime_pos.sort()
			
			tp_start_pv = int(pv_three_prime_pos[0]) - 1
			tp_pv_ovhg = pvSeq[tp_start_pv:tp_start_pv+len(three_pv_2.ovhgseq)]
			#print "3' PV overhang: " + tp_pv_ovhg
			
			if tp_pv_ovhg.upper() != tp_insert_ovhg.upper():
				raise HybridizationException("")
			
			if len(pv_three_prime_pos) == 0:
				raise InsertSitesNotFoundOnParentSequenceException("")
			
			tp_end_pv = int(pv_three_prime_pos[len(pv_three_prime_pos)-1]) - 1
			
			pvSeqPost = pvSeq[tp_end_pv:].upper()
			
			newSeq = pvSeqPre + insertSeq + pvSeqPost
			return newSeq
				
			## 3 cases: both sites hybrid; only 5' hybrid; only 3' hybrid
		
			## Case 1: Only 5' site hybrid
			#if self.isHybrid(five_site) and not self.isHybrid(three_site):
				##print "case 1: 5' hybrid, 3' not"
				
				#five_pv = self.get_H_1(five_site)
				##print "5' " + five_pv
				#three_pv = three_site
				##print "3' " + three_pv
		
				## Create a hybrid sequence for 5' site, 3' just regular
				#five_seq = self.hybridSeq(five_site)
				#three_seq = self.sitesDict[three_pv]
				
				## Find the NON-HYBRID 5' sequence
				#five_non_hybrid_seq = self.sitesDict[five_pv]
				
				## Find restriction sites on pvSeq
				#if five_non_hybrid_seq:
					
					#five_index = pvSeq.find(five_non_hybrid_seq)	# look for the non-hybrid 5' H1 part on the parent vector
					#three_index = pvSeq.find(three_seq)		# just regular lookup
					
					#if five_index >= 0 and three_index >= 0:
					
						#preSeq = pvSeq[0:five_index]		# up to start of 5' site not including
						#postSeq = pvSeq[three_index:]		# from start of 3' site including it to the end of the sequence
						
						## Now the 5' site gets replaced with HYBRID sequence
						#if five_seq:
							#newSeq = preSeq + five_seq + insertSeq + postSeq
						#else:
							#raise HybridizationException("Sites provided cannot be hybridized")
				#else:
					#raise InsertSitesNotFoundOnParentSequenceException("Sites not found on parent vector sequence")
		
			## Case 2: Both sites hybrid
			#if self.isHybrid(five_site) and self.isHybrid(three_site):
		
				## Find boundaries on pvSeq
				#five_pv = self.get_H_1(five_site)
				#three_pv = self.get_H_2(three_site)
				
				## Get non-hybrid sequence of parent sites
				#five_non_hybrid_seq = self.sitesDict[five_pv]
				#three_non_hybrid_seq = self.sitesDict[three_pv]
		
				## Create hybrid sequences for both 5' and 3' sites
				#five_seq = self.hybridSeq(five_site)
				#three_seq = self.hybridSeq(three_site)
		
				#if five_seq and three_seq:
				
					## Find sites on parent vector, replace with hybrid sequence and add insert between them
					#five_index = pvSeq.find(five_non_hybrid_seq)
					#three_index = pvSeq.find(three_non_hybrid_seq)
		
					#if five_index > 0 and three_index > 0:
						#preSeq = pvSeq[0:five_index]
						#postSeq = pvSeq[three_index:]
		
						##newSeq = preSeq + five_seq + five_linker + insertSeq + three_linker + three_seq + postSeq	# need to replace sites on parent with hybrid sequences
						#newSeq = preSeq + five_seq + insertSeq + three_seq + postSeq	# need to 
					#else:
						#raise InsertSitesNotFoundOnParentSequenceException("Sites not found on parent sequence")
				#else:
					#raise HybridizationException("Sites provided cannot be hybridized")
		
			## Case 3: Only 3' site hybrid
			#if self.isHybrid(three_site) and not self.isHybrid(five_site):
		
				## Find boundaries on pvSeq
				#three_pv = self.get_H_2(three_site)
				#five_pv = five_site
		
				## Get hybrid sequence for 3'
				#five_seq = self.sitesDict[five_site]
				#three_seq = self.hybridSeq(three_site)
		
				#if three_seq:
					## also find the non-hybrid sequence of 3'
					#three_non_hybrid_seq = self.sitesDict[three_pv]
				
					## add Insert between sites
					#five_index = pvSeq.find(five_seq)
					#three_index = pvSeq.find(three_seq)
		
					#if five_index > 0 and three_index > 0:
		
						#preSeq = pvSeq[0:five_index]					# before 5'
						#postSeq = pvSeq[three_index+len(three_non_hybrid_seq):]		# AFTER 3' b/c it will be replaced w/ hybrid seq
		
						## Replace 3' site with hybrid sequence
						#newSeq = preSeq + five_seq + insertSeq + three_seq + postSeq
					#else:
						#raise InsertSitesNotFoundOnParentSequenceException("Sites not found on parent sequence")
				#else:
					#raise HybridizationException("Sites provided cannot be hybridized")
				
			#return newSeq

	'''
	def hybrid(self, five_site, three_site, pvSeq, insertSeq, five_linker, three_linker):
		newSeq = None
		
		# May 27/08: Make pvSeq uppercase
		pvSeq = pvSeq.upper()

		# convert linker values to string - complains if they're None
		if not five_linker:
			five_linker = ""
			
		if not three_linker:
			three_linker = ""

		# 3 cases: both sites hybrid; only 5' hybrid; only 3' hybrid
		# Case 1: Only 5' site hybrid
		if self.isHybrid(five_site) and not self.isHybrid(three_site):
                	five_pv = self.get_H_1(five_site)
			three_pv = three_site
			
			# Create a hybrid sequence for 5' site, 3' just regular
			five_seq = self.hybridSeq(five_site)
                	three_seq = self.sitesDict[three_pv]

			# Find the NON-HYBRID 5' sequence
			five_non_hybrid_seq = self.sitesDict[five_pv]
			
			# Find restriction sites on pvSeq
			if five_non_hybrid_seq:
				
				five_index = pvSeq.find(five_non_hybrid_seq)	# look for the non-hybrid 5' H1 part on the parent vector
				three_index = pvSeq.find(three_seq)		# just regular lookup

				# Place the Insert between restriction sites - IF FOUND
				if five_index < 0 :
					#raise InsertSitesNotFoundOnParentSequenceException("Sites not found on parent sequence")
				
					# May 27/08: V1886 (MGC backbone vector) contains the hybrid SalI-XhoI sequence rather than non-hybrid SalI.  So try searching for the hybrid sequence and place the cDNA with linkers after it. *** CONFIRM THIS WITH KAREN ***
					five_index = pvSeq.find(five_seq)
					
					if five_index < 0:
						raise InsertSitesNotFoundOnParentSequenceException("Sites not found on parent sequence")
					else:
						preSeq = pvSeq[0:five_index]
						postSeq = pvSeq[three_index:]		# from start of 3' site including it to the end of the sequence
				
						newSeq = preSeq + five_seq + five_linker.upper() + insertSeq.lower() + three_linker.upper() + postSeq
						
				elif three_index < 0:
					raise InsertSitesNotFoundOnParentSequenceException("Sites not found on parent sequence")
				
				else:
					#if five_index > 0 and three_index > 0:
				
					preSeq = pvSeq[0:five_index]		# up to start of 5' site not including
					postSeq = pvSeq[three_index:]		# from start of 3' site including it to the end of the sequence
				
					# Now the 5' site gets replaced with HYBRID sequence
					if five_seq:
						newSeq = preSeq + five_seq + five_linker + insertSeq.lower() + three_linker + postSeq
					else:
						raise HybridizationException("Sites provided cannot be hybridized")
			else:
				raise InsertSitesException("Unknown sites")
				
	
		# Case 2: Both sites hybrid
		if self.isHybrid(five_site) and self.isHybrid(three_site):

			# Find boundaries on pvSeq
			five_pv = self.get_H_1(five_site)
			three_pv = self.get_H_2(three_site)
			
                	# Get non-hybrid sequence of parent sites
                	five_non_hybrid_seq = self.sitesDict[five_pv]
                	three_non_hybrid_seq = self.sitesDict[three_pv]

			# Create hybrid sequences for both 5' and 3' sites
			five_seq = self.hybridSeq(five_site)
			three_seq = self.hybridSeq(three_site)

			if five_seq and three_seq:
			
				# Find sites on parent vector, replace with hybrid sequence and add insert between them
				five_index = pvSeq.find(five_non_hybrid_seq)
				three_index = pvSeq.find(three_non_hybrid_seq)

				if five_index > 0 and three_index > 0:
					preSeq = pvSeq[0:five_index]
					postSeq = pvSeq[three_index:]

					newSeq = preSeq + five_seq + five_linker + insertSeq + three_linker + three_seq + postSeq	# need to replace sites on parent with hybrid sequences
				else:
					raise InsertSitesNotFoundOnParentSequenceException("Sites not found on parent sequence")
			else:
				raise HybridizationException("Sites provided cannot be hybridized")
	
		# Case 3: Only 3' site hybrid
		if self.isHybrid(three_site) and not self.isHybrid(five_site):

			# Find boundaries on pvSeq
			three_pv = self.get_H_2(three_site)
			five_pv = five_site

			# Get hybrid sequence for 3'
                	five_seq = self.sitesDict[five_site]
			three_seq = self.hybridSeq(three_site)

			if three_seq:
			
				# also find the non-hybrid sequence of 3'
				three_non_hybrid_seq = self.sitesDict[three_pv]
			
				# add Insert between sites
				five_index = pvSeq.find(five_seq)
				three_index = pvSeq.find(three_seq)

				if five_index > 0 and three_index > 0:

					preSeq = pvSeq[0:five_index]					# before 5'
					postSeq = pvSeq[three_index+len(three_non_hybrid_seq):]		# AFTER 3' b/c it will be replaced w/ hybrid seq

					# Replace 3' site with hybrid sequence
					newSeq = preSeq + five_seq + five_linker + insertSeq + three_linker + three_seq + postSeq
				else:
					raise InsertSitesNotFoundOnParentSequenceException("Sites not found on parent sequence")
			else:
				raise HybridizationException("Sites provided cannot be hybridized")
			
		return newSeq
	'''
	
	def getHybridDegenerate(self, hybridSite, parentVectorSequence, insertSequence, hybrid_type):

		#print "Content-type:text/html"
		#print
		#print hybridSite
		#print hybrid_type
		
		if hybrid_type == "5 prime":
			five_pv_1 = self.enzDict[self.get_H_1(hybridSite)]
			five_pv_2 = self.enzDict[self.get_H_2(hybridSite)]
			
		elif hybrid_type == "3 prime":
			five_pv_1 = self.enzDict[self.get_H_2(hybridSite)]
			five_pv_2 = self.enzDict[self.get_H_1(hybridSite)]
			
		#print five_pv_1
		#print five_pv_2
		
		pv_five_prime_pos = five_pv_1.search(parentVectorSequence)
		#print `pv_five_prime_pos`
		pv_five_prime_pos.sort()
		
		#if len(pv_five_prime_pos) == 0:
			#raise InsertSitesNotFoundOnParentSequenceException("")
		
		# Feb. 6/09: FIRST OF ALL, CHECK THAT BOTH SITES ACTUALLY EXIST ON THE INSERT (i.e. that search() 5' != search() 3')
		
		insert_five_prime_pos = five_pv_2.search(insertSequence)
		insert_five_prime_pos.sort()
		
		tp_start_pv = insert_five_prime_pos[0] - 1
		tp_seq = five_pv_2.elucidate()
		
		insertSeq = insertSequence.tostring()
		#print fp_insert_ovhg
		#print `insert_five_prime_pos`
		
		#if len(insert_five_prime_pos) == 0:
			#raise CloningSitesNotFoundInInsertException("Insert sequence does not contain restriction site")
		
		fp_start_pv = int(pv_five_prime_pos[0]) - 1
		
		fp_seq = five_pv_1.elucidate()
		#print fp_seq
		
		pvSeq = parentVectorSequence.tostring()
		
		if five_pv_1.is_5overhang():
			fp_pv_ovhg = pvSeq[fp_start_pv:fp_start_pv+len(five_pv_1.ovhgseq)]
			#print fp_pv_ovhg
			fp_pv_flank = fp_seq[0:fp_seq.find("^")]
			#print fp_pv_flank
			tp_pv_flank = fp_seq[fp_seq.find("_")+1:]
			#print tp_pv_flank
			tp_pv_ovhg = pvSeq[fp_start_pv:fp_start_pv+len(five_pv_1.ovhgseq)]
			#print tp_pv_ovhg
		else:
			fp_pv_ovhg = pvSeq[fp_start_pv-len(five_pv_1.ovhgseq):fp_start_pv]
			#print fp_pv_ovhg
			fp_pv_flank = fp_seq[fp_seq.find("^")+1:]
			#print fp_pv_flank
			tp_pv_flank = fp_seq[0:fp_seq.find("_")]
			#print tp_pv_flank
		
		if five_pv_2.is_5overhang():
			#print tp_seq
			fp_insert_flank = tp_seq[0:tp_seq.find("^")].lower()
			#print fp_insert_flank
			tp_insert_flank = tp_seq[tp_seq.find("_")+1:].lower()
			#print tp_insert_flank
			fp_insert_ovhg = insertSeq[tp_start_pv:tp_start_pv+len(five_pv_2.ovhgseq)]
			#print fp_insert_ovhg
		else:
			fp_insert_flank = tp_seq[tp_seq.find("_")+1:].lower()
			tp_insert_flank = tp_seq[0:tp_seq.find("^")].lower()
			tp_insert_ovhg = insertSeq[tp_start_pv:tp_start_pv+len(five_pv_2.ovhgseq)]
		
		#fp_pv_ovhg = pvSeq[fp_start_pv:fp_start_pv+len(five_pv_1.ovhgseq)]
		#print fp_pv_ovhg
		#fp_site = fp_pv_flank + fp_pv_ovhg + tp_pv_flank
		
		if hybrid_type == "5 prime":
			fp_site = fp_pv_flank + fp_insert_ovhg + tp_insert_flank
			#print fp_site
			return fp_site
		else:
			tp_site = fp_insert_flank + tp_pv_ovhg + tp_pv_flank
			#print tp_site
			return tp_site
	
	
	#######################################################################################################################
	# Special case: 5' site == 3' site
	#
	# Updated May 15/08: Since Insert sequence now contains the site, "cut out" the site and replace with Insert sequence
	#######################################################################################################################
	def identical_sites(self, siteSeq, pvSeq, insertSeq):

		# Parent vector should only have this site once
		if utils.numOccurs(pvSeq, siteSeq) == 1:

			# locate the site on pvSeq
			fp_start = pvSeq.find(siteSeq)
			
			# modified May 15/08
			#insert_start = fp_start + len(siteSeq)			# removed May 15/08
			insert_start = fp_start					# added May 15/08

			seqPre = pvSeq[0:insert_start]
			seqPost = pvSeq[insert_start:]

			#newSeq = seqPre + insertSeq + siteSeq + seqPost	# removed May 15/08
			newSeq = seqPre + insertSeq + seqPost			# added May 15/08
			
			return newSeq
		else:
			raise MultipleSiteOccurrenceException("Multiple site occurrences on parent vector sequence")


	def isDegenerate(self, enz):
		rSite = enz.site
		
		for nt in self.degenerateNucleotides:
			if rSite.find(nt) >= 0:
				return True
		
		return False
	
	
	#################################################################################################################
	# VECTOR SEQUENCE RECONSTITUTION
	#################################################################################################################
	
	# Determine Vector information and invoke the appropriate function call for sequence reconstitution
	#def constructSequence(self, rID):	
	
	#################################################################################################################
	# Reconstitute NON=RECOMBINATION vector sequence from the sequences of its Insert and parent vector
	#
	# Since this function is called at new Vector creation and no information on the new vector exists,
	# relying only on its parent information to construct the sequence (and let users change later as they please)
	#
	# Therefore: cloning sites are fetched from the Insert
	#
	# Input: parentVector ID, insertID
	# Output: existing or newly added seqID of the reconstituted sequence
	#
	# Updated May 15/08: Insert sequence contains sites and linkers
	#################################################################################################################
	#def constructNonRecombSequence(self, pvSeqID, insertSeqID, cloning_sites, linkers):
	def constructNonRecombSequence(self, pvSeqID, insertSeq, cloning_sites, reverse_insert=False):
		
		#print "Content-type:text/html"
		#print
		#print `cloning_sites`
		#print `reverse_insert`
		
		db = self.db
		cursor = self.cursor
		sitesDict = self.sitesDict
		
		pHandler = ReagentPropertyHandler(db, cursor)
		
		# Database IDs of simple reagent properties
		seqPropID = pHandler.findPropID('sequence')

		# Actual property values - Updated Oct. 31/08
		#fp_insert_cs = cloning_sites[0]
		#tp_insert_cs = cloning_sites[1]

		# oct. 31/08
		if len(cloning_sites) > 0:
			fp_insert_cs = cloning_sites[0]
			
			if len(cloning_sites) > 1:
				tp_insert_cs = cloning_sites[1]
			else:
				tp_insert_cs = 'None'
		else:
			fp_insert_cs = 'None'
			
		#print "Cloning sites " + `cloning_sites`
		#print "Linkers " + `linkers`

		newSeq = ""	# resulting sequence

		# Dec. 9/08: Added right before launch: VERY important for directional cloning - CANNOT just blindly always set Insert linear to 'false'!
		if fp_insert_cs != tp_insert_cs:
			linear = False
		else:
			# Do NOT use linear=False in searching Insert sequence for IDENTICAL sites!!! (e.g. EcoRI children of V2327 Topo)
			linear = True
			
		#print `linear`
		
		# Fetch sequences of the Insert and Parent Vector
		pvSeq = self.findSequenceByID(pvSeqID).upper()			# uniform case
		
		if reverse_insert:
			#print "reversing insert"
			insertSeq = self.reverse_complement(insertSeq)
			
			# NO!!!!!!!!  removed Feb. 24/10; modified PHP input page, sites will arrive in correct order
			## Jan. 30/09: MUST change sites order too!!!!!!!!!!!!!!!!!!!!!!!!
			#fp_insert_cs = cloning_sites[1]
			#tp_insert_cs = cloning_sites[0]
			
		#print fp_insert_cs
		#print tp_insert_cs
		
		#print insertSeq
		
		# removed May 15/08
		#insertSeq = self.findSequenceByID(insertSeqID).lower()
		
		# Check is the cloning sites are hybrid
		five_prime_site = utils.make_array(fp_insert_cs)
		three_prime_site = utils.make_array(tp_insert_cs)

		if self.isHybrid(five_prime_site) or self.isHybrid(three_prime_site):
			#print "HYBRID SITES!!"
			#print `five_prime_site`
			#newSeq = self.hybrid(five_prime_site, three_prime_site, pvSeq, insertSeq, fp_insert_linker, tp_insert_linker)
			
			# oct 31/08
			newSeq = self.hybrid(five_prime_site, three_prime_site, pvSeq, insertSeq)	# no linkers
			
			if not newSeq:
				# Reverse the **********INSERT*******!!!!!!!!!!!!!!!!!
				insertSeq = self.reverse_complement(insertSeq).upper()
				newSeq = self.hybrid(five_prime_site, three_prime_site, pvSeq, insertSeq)	

		# Update March 8, 2010: SfiI will now be treated the same as the rest of degenerate sites
		# This exception will most likely be raised if the Insert contains gateway (attB) sites - Moved here Nov, 17/08
		#elif fp_insert_cs not in self.sitesDict or tp_insert_cs not in self.sitesDict and fp_insert_cs != "SfiI" and tp_insert_cs != "SfiI":
		elif fp_insert_cs not in self.sitesDict or tp_insert_cs not in self.sitesDict:
			raise InsertSitesException()
		
		# Oct. 31/08 - Removed March 8/10: no, SfiI will now be the same as the rest of degenerates
		#elif cloning_sites[0] == "SfiI" and cloning_sites[1] == "SfiI":
			#fpSite = "GGCCATTACGGCC"
			#tpSite = "GGCCGCCTCGGCC"
			
			#fp_start_pv = pvSeq.upper().find(fpSite)
			
			#if fp_start_pv < 0:
				#fpSite = "GGCCATTATGGCC"
				#fp_start_pv = pvSeq.upper().find(fpSite)
				
				#if fp_start_pv >= 0:
					#tp_start_pv = pvSeq.upper().find(tpSite)
					
					#if fp_start_pv > tp_start_pv:
						##print "Error: 5' before 3'"
						#newSeq = ""
					
					#elif fp_start_pv > 0:
						#pvSeqPre = pvSeq[0:fp_start_pv+8]	# SfiI cleaves after 8th nt
						#pvSeqPost = pvSeq[tp_start_pv+8:]
						
						## get the remaining portion of the cloning site from the Insert
						#fp_start_insert = insertSeq.find(fpSite.lower())
						#tp_start_insert = insertSeq.find(tpSite.lower())
						
						## here, add 1 to 5' cleavage position
						#insertSeq = insertSeq[fp_start_insert+8:tp_start_insert+8]
						#newSeq = pvSeqPre + insertSeq + pvSeqPost

			#else:
				#tp_start_pv = pvSeq.upper().find(tpSite)
				
				#pvSeqPre = pvSeq[0:fp_start_pv+8]	# SfiI cleaves after 8th nt, subtract 1 get 7
				#pvSeqPost = pvSeq[tp_start_pv+8:]
				
				## get the remaining portion of the cloning site from the Insert
				#fp_start_insert = insertSeq.find(fpSite.lower())
				#tp_start_insert = insertSeq.find(tpSite.lower())
				
				## here, add 1 to 5' cleavage position
				#insertSeq = insertSeq[fp_start_insert+8:tp_start_insert+8]
				#newSeq = pvSeqPre + insertSeq + pvSeqPost
		else:
			## March 5, 2010: CHECK COMPATIBILITY
			#if self.makeEnzyme(tp_insert_cs) not in self.makeEnzyme(fp_insert_cs).compatible_end():
				#raise HybridizationException("")
			
			if self.enzDict.has_key(fp_insert_cs):
				fp_insert_cs = self.enzDict[fp_insert_cs]
				
			if self.enzDict.has_key(tp_insert_cs):
				tp_insert_cs = self.enzDict[tp_insert_cs]
			
			# Dec. 14/09: Added empty sequence exception handling
			if len(utils.squeeze(pvSeq).strip()) > 0:
				# Updated Oct. 2/08
				parentVectorSequence = Seq(pvSeq)
			else:
				raise EmptyParentVectorSequenceException("Empty parent vector sequence")
			
			if len(utils.squeeze(insertSeq).strip()) > 0:
				insertSequence = Seq(insertSeq.upper())
			else:
				raise EmptyParentInsertSequenceException("Empty parent insert sequence")
			
			# BioPython "search" returns a LIST of ALL positions where the enzyme cuts the sequence.  Get the appropriate position (for 5' on PV it's usually the first) and subtract 1 to convert from biological to computer notation
			
			pv_five_prime_pos = fp_insert_cs.search(parentVectorSequence, linear)
			pv_three_prime_pos = tp_insert_cs.search(parentVectorSequence, linear)
			
			#print "5' positions on PV sequence: " + `pv_five_prime_pos`
			#print "3' positions on PV sequence: " + `pv_three_prime_pos`
			
			pv_five_prime_pos.sort()
			pv_three_prime_pos.sort()
			
			#print `linear`
			#print `pv_five_prime_pos`
			#print `pv_three_prime_pos`
			
			# March 5, 2010: Still, for degenerates, above check wouldn't work: e.g. AscI is potentially compatible with AflIII IFF overhangs are compatible.  So for degenerate sites get actual overhang from sequence
			if tp_insert_cs.is_ambiguous() or fp_insert_cs.is_ambiguous():
				fp_start_pv = int(pv_five_prime_pos[0]) - 1
				
				# March 9, 2010: Different behaviour for identical sites
				if fp_insert_cs != tp_insert_cs:
					tp_start_pv = int(pv_three_prime_pos[0]) - 1
				else:
					tp_start_pv = int(pv_five_prime_pos[len(pv_five_prime_pos)-1]) - 1
				
				# correction March 5/10 
				# Updated March, 9/10: ovhg position comparison doesn't work with our existing SfiI: its fp_insert_cs.ovhg is 3 but it's still a 3' overhang enzyme, fp_insert_cs.elucidate().find("_") is 5, so, even though the condition holds, testing with V1889 and I50004 RC yields incorrect results!!!!!
				#if fp_insert_cs.ovhg < fp_insert_cs.elucidate().find("_"):
				if fp_insert_cs.is_5overhang():
					fp_pv_ovhg = pvSeq[fp_start_pv:fp_start_pv+len(fp_insert_cs.ovhgseq)]
				else:
					fp_pv_ovhg = pvSeq[fp_start_pv-len(fp_insert_cs.ovhgseq):fp_start_pv]
				
				#fp_pv_ovhg = pvSeq[fp_start_pv:fp_start_pv+len(fp_insert_cs.ovhgseq)]
				
				if  tp_insert_cs.is_5overhang():
				#if tp_insert_cs.ovhg < tp_insert_cs.elucidate().find("_"):
					tp_pv_ovhg = pvSeq[tp_start_pv:tp_start_pv+len(tp_insert_cs.ovhgseq)]
				else:
					tp_pv_ovhg = pvSeq[tp_start_pv-len(tp_insert_cs.ovhgseq):tp_start_pv]
				
				#tp_pv_ovhg = pvSeq[tp_start_pv:tp_start_pv+len(tp_insert_cs.ovhgseq)]
				
				#print "5' PV overhang: " + fp_pv_ovhg
				#print "3' PV overhang: " + tp_pv_ovhg
				
				insert_five_prime_pos = fp_insert_cs.search(insertSequence, linear)
				insert_three_prime_pos = tp_insert_cs.search(insertSequence, linear)
				
				#print "5' positions on Insert sequence: " + `insert_five_prime_pos`
				#print "3' positions on Insert sequence: " + `insert_three_prime_pos`
				
				fp_start_insert = int(insert_five_prime_pos[0]) - 1
				
				# March 9, 2010: identical sites
				if fp_insert_cs != tp_insert_cs:
					tp_start_insert = int(insert_three_prime_pos[0]) - 1
				else:
					tp_start_insert = int(insert_three_prime_pos[len(insert_three_prime_pos)-1]) - 1
					
				if fp_insert_cs.is_5overhang():
					fp_insert_ovhg = insertSeq[fp_start_insert:fp_start_insert+len(fp_insert_cs.ovhgseq)]
				else:
					fp_insert_ovhg = insertSeq[fp_start_insert-len(fp_insert_cs.ovhgseq):fp_start_insert]
					
				if tp_insert_cs.is_5overhang():
					tp_insert_ovhg = insertSeq[tp_start_insert:tp_start_insert+len(tp_insert_cs.ovhgseq)]
				else:
					tp_insert_ovhg = insertSeq[tp_start_insert-len(tp_insert_cs.ovhgseq):tp_start_insert]
				
				#print "5' insert overhang: " + fp_insert_ovhg
				#print "3' insert overhang: " + tp_insert_ovhg
				
				if fp_pv_ovhg.upper() != fp_insert_ovhg.upper():
					raise IncompatibleFivePrimeOverhangsException("Incompatible 5' overhangs")
				
				if tp_pv_ovhg.upper() != tp_insert_ovhg.upper():
					raise IncompatibleThreePrimeOverhangsException("Incompatible 3' overhangs")
				
			if len(pv_five_prime_pos) > 0:
				fp_start_pv = int(pv_five_prime_pos[0]) - 1
			
				#print `fp_start_pv`
			
				# Nov. 3/08: Check 5' after 3'
				
				if len(pv_three_prime_pos) > 0:
					tp_start_pv = int(pv_three_prime_pos[len(pv_three_prime_pos)-1]) - 1
					
					#print "5' pv " + `fp_start_pv`
					#print "3' pv " + `tp_start_pv`
					
					if fp_start_pv > tp_start_pv:
						#if not reverse_insert:
						raise FivePrimeAfterThreePrimeException("5' site occurs after 3' site on parent vector sequence")
					else:	
						#print "Here!" + insertSeq
						pvSeqPre = pvSeq[0:fp_start_pv]
						#print pvSeqPre
						
						# get the remaining portion of the cloning site from the Insert
						insert_five_prime_pos = fp_insert_cs.search(insertSequence, linear)
						#print `insert_five_prime_pos`
						insert_five_prime_pos.sort()
						
						if len(insert_five_prime_pos) > 0:	
							fp_start_insert = int(insert_five_prime_pos[0]) - 1
							#print `fp_start_insert`
							
							# for the 3' site on Insert, grab its LAST cutting position
							#print tp_insert_cs
							#print insertSequence
							insert_three_prime_pos = tp_insert_cs.search(insertSequence, linear)
							#print `insert_three_prime_pos`
							insert_three_prime_pos.sort()
							
							if len(insert_three_prime_pos) > 0:
								tp_end_insert = int(insert_three_prime_pos[len(insert_three_prime_pos)-1]) - 1
								#print `tp_end_insert`
								
								# March 10/10: check 5' before 3' on INSERT sequence!!!!
								tp_start_insert = int(insert_three_prime_pos[0])-1
								
								if fp_start_insert > tp_start_insert:
									raise InsertFivePrimeAfterThreePrimeException("")
							else:
								# Dec. 2/08: NO!!!!!!!! THIS IS THE WHOLE POINT, TO DISALLOW C0MBINING NON-COMPATIBLE INSERT AND VECTOR!  Do NOT do this!!!
								'''
								## Oct. 7/08: Use entire Insert		# removed Dec. 2/08
								#fp_start_insert = 0			# removed Dec. 2/08
								#tp_end_insert = len(insertSeq)		# removed Dec. 2/08
								'''
								#print "????"
								raise CloningSitesNotFoundInInsertException("")		# dec. 2/08
						else:
							# Dec. 2/08: NO!!!!!!!!!!!!!!!!!!!!! THAT'S THE WHOLE POINT, TO DISALLOW C0MBINING NON-COMPATIBLE INSERT AND VECTOR!!!!!!!!!!!!!!!!
							
							## Oct. 7/08: Use entire Insert		# removed Dec. 2/08
							#fp_start_insert = 0			# removed Dec. 2/08
							#tp_end_insert = len(insertSeq)		# removed Dec. 2/08
							#print "????"
							raise CloningSitesNotFoundInInsertException("")		# replaced Dec. 2/08
							
						insertSeq = insertSeq[fp_start_insert:tp_end_insert]
						#print insertSeq
						
						# Get the LAST cutting position of the 3' site on PV sequence
						#pv_three_prime_pos = tp_insert_cs.search(parentVectorSequence, False)
						
						#if len(pv_three_prime_pos) > 0:
						tp_end_pv = int(pv_three_prime_pos[len(pv_three_prime_pos)-1]) - 1
						#print `pv_three_prime_pos`
						#print tp_end_pv
						
						if tp_end_pv > 0:
							pvSeqPost = pvSeq[tp_end_pv:]
							#print pvSeqPost
							
							newSeq = pvSeqPre + insertSeq + pvSeqPost
							#print newSeq
				else:
					raise InsertSitesNotFoundOnParentSequenceException("5' site not found on PV sequence")
			else:
				raise InsertSitesNotFoundOnParentSequenceException("5' site not found on PV sequence")
			
		return newSeq


	############################################
	### LOXP Recombinmation Vector sequences ###
	############################################
	
	#def constructRecombSequence(self, pvSeqID, ipvSeqID, insertSeqID, insertLinkers):
	def constructRecombSequence(self, pvSeqID, ipvSeqID):
	
		#print "Content-type:text/html"
		#print
		
		db = self.db
		cursor = self.cursor
		
		loxp_seq = "ATAACTTCGTATAGCATACATTATACGAAGTTAT"
		
		# Fetch sequences of the Parent Vector and Insert Parent Vector
		pvSeq = self.findSequenceByID(pvSeqID).upper()
		ipvSeq = self.findSequenceByID(ipvSeqID).upper()
		
		# Dec. 14/09: Added empty sequence exception handling
		if len(utils.squeeze(pvSeq).strip()) == 0:
			raise EmptyParentVectorSequenceException("Empty parent vector sequence")
		
		if len(utils.squeeze(ipvSeq).strip()) == 0:
			raise EmptyInsertParentVectorSequenceException("Empty insert parent vector sequence")
		
		# resulting sequence
		newSeq = ""
	
		# Replace the single loxP sequence on PV sequence with the Insert sequence portion between two loxP sites (from start of first loxP to the end of second loxP) of the IPV sequence

		# Make sure pvSeq contains LoxP exactly once
		numLoxp_pv = utils.numOccurs(pvSeq, loxp_seq)
		
		if numLoxp_pv == 1:
			ipv_loxp_1 = ipvSeq.upper().find(loxp_seq) + len(loxp_seq)
			ipv_loxp_2 = ipvSeq.upper().rfind(loxp_seq)
			
			# October 31, 2008, Karen: We agreed that it is okay for Insert to be in lowercase and cDNA to be defined by position
			ipv_insert_seq = loxp_seq.upper() + ipvSeq[ipv_loxp_1:ipv_loxp_2].lower() + loxp_seq.upper()
			newSeq = pvSeq.replace(loxp_seq, ipv_insert_seq)

		elif numLoxp_pv == 0:
			raise InsertSitesNotFoundOnParentSequenceException("LOXP sites not found on parent vector sequence")

		elif numLoxp_pv > 1:
			raise MultipleSiteOccurrenceException("LOXP found more than once on parent vector sequence")

		return newSeq
		

	#########################
	### Gateway sequences ###
	#########################
	
	##############################################################################################
	# Find the linker portion between attB site and start of insert on primer sequence recursively
	# Input: insert_seq: string
	#	 primer_seq: string, trailing portion of oligo sequence after attB site
	# 	 linker: string
	# Output: linker: string
	##############################################################################################
	#def linker_from_oligo(insert_seq, primer_seq, linker):
	def linker_from_oligo(self, insert_seq, primer_seq, linker=""):
		
		if insert_seq.find(primer_seq) != 0:
			linker += primer_seq[0]
			primer_seq = primer_seq[1:]
			return self.linker_from_oligo(insert_seq, primer_seq, linker)
	
		return linker
		
		
	# Nov. 12/09: had an error, so took code from TEST
	def entryVectorSequence(self, pvSeqID, insertSeq):
		
		db = self.db
		cursor = self.cursor
		
		# property map
		propMapper = ReagentPropertyMapper(db, cursor)
		prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)

		parentVectorSequence = self.findSequenceByID(pvSeqID)
	
		# Dec. 14/09: Added empty sequence exception handling
		if len(utils.squeeze(parentVectorSequence).strip()) == 0:
			raise EmptyParentVectorSequenceException("Empty parent vector sequence")
		
		if len(utils.squeeze(insertSeq).strip()) == 0:
			raise EmptyParentInsertSequenceException("Empty parent insert sequence")
		
		# output value
		newSeq = ""
		
		# Initialize new sequence of entry vector to sequence of its parent vector
		entryVectorSeq = parentVectorSequence
		
		# CHANGED MAY 14/08: INSERT SEQUENCE CONTAINS SITES ALREADY; the new Insert sequence is a portion of original Insert between 5' start and 3' end
		
		# Update September 8, 2009: NO!!!  Inserts made through Primer Design contain an extension before the att site, and preload.py passes the ENTIRE sequence to this function - so still need to trim off the extra portion of the Insert.  See below (define sites first, then trim Insert)
		
		# June 8/08:
		attB1_a = "gtacaaaaaa"
		attB1_b = "gttggcacc"			# 5' GW Donor linker (stop codon not included for uniformity)

		attP1_a = "CCAACTTT"
		attP1_b = "GTACAAAAAA"

		attP1 = attP1_a + attP1_b		# CCAACTTT|GTACAAAAAA

		attL1 = attP1 + attB1_b			# CCAAC|TTT GTACAAAAAA GTTGGCACC

		# attB2 site is the reverse complement of GW Donor 3' Linker ggggacaacttt gtacaagaaa gttgggta (stop codon not included)
		attB2_a = "tacccaac"			# rev. compl. gttgggta
		attB2_b = "TTTCTTGTAC"			# rev. compl. gtacaagaaa
		
		attB2 = attB2_a + attB2_b		# tacccaacTTTCTTGTAC

		attP2_a = "TTCAGCTTT"
		attP2_b = "AAAGTTGGCATTATAA"

		attP2 = attP2_a + "CTTGTAC" + attP2_b	# TTCAGCTTT

		attL2 = attB2 + attP2_b			# tacccaacTTTCTTGTACAAAGTTggcattataa

		# Sept. 8/09: From above - trim off portion of Insert sequence to have it begin with attB1 and end with attB2
		#print "Content-type:text/html"
		#print
		
		insert_start = insertSeq.lower().find(attB1_a)
		#print insertSeq
		#print insert_start
		
		insert_end = insertSeq.lower().rfind(attB2_b.lower()) + len(attB2_b)
		#print insert_end
		
		insertSeq = insertSeq[insert_start:insert_end]
		#print insertSeq
		
		# check attP1 occurs exactly once on parent vector sequence - JUNE 8/08: CHECK CORE
		numOcc_attP1_pv = utils.numOccurs(entryVectorSeq.upper(), self.gatewayDict['attP1'])
		
		if numOcc_attP1_pv > 1:
			raise MultipleSiteOccurrenceException("attP1 found more than once on parent vector sequence")
	
		elif numOcc_attP1_pv == 0:
			raise InsertSitesNotFoundOnParentSequenceException("attP1 site not found on parent vector sequence")	
	
		# check attP2 occurs exactly once on parent vector sequence
		numOcc_attP2_pv = utils.numOccurs(entryVectorSeq.upper(), self.gatewayDict['attP2'])
		
		if numOcc_attP2_pv > 1:
			raise MultipleSiteOccurrenceException("attP2 found more than once on parent vector sequence")
	
		elif numOcc_attP2_pv == 0:
			raise InsertSitesNotFoundOnParentSequenceException("attP2 site not found on parent vector sequence")
		
		if entryVectorSeq.find(attP1) > 0 and entryVectorSeq.find(attP2):
			
			# Updated June 7/08
			#startpos = entryVectorSeq.find(attP1) + len(attP1) - len(attB1)	# removed June 7/08
			startpos = entryVectorSeq.find(attP1_a) + len(attP1_a)			# added June 7/08
			endpos = entryVectorSeq.find(attP2_b)
			
			# check attP1 occurs before attP2 on parent sequence
			if startpos > endpos:
				raise FivePrimeAfterThreePrimeException("attP1 occurs after attP2 on parent vector sequence")
			
			# Modified May 16/08
			sub_remove = entryVectorSeq[startpos:endpos]
			
			#print "Content-type:text/html"
			#print
			#print newInsertSeq
			
			# action
			newSeq = entryVectorSeq.replace(sub_remove, insertSeq)
			
		else:
			raise InsertSitesNotFoundOnParentSequenceException("attP sites not found on parent vector sequence")

		return newSeq


	###########################################
	# Gateway Expression clones
	# Modified June 8/08: Changed signature
	###########################################
	#def expressionVectorSequence(self, pvSeqID, insertSeqID, linkers):
	def expressionVectorSequence(self, pvSeqID, ipvSeq):
		db = self.db
		cursor = self.cursor
		
		#print "Content-type:text/html"
		#print

		# return value
		newSeq = ""
		
		# Fetch Parent Vector sequence
		pvSeq = self.findSequenceByID(pvSeqID).upper()
	
		# Dec. 14/09: Added empty sequence exception handling
		if len(utils.squeeze(pvSeq).strip()) == 0:
			raise EmptyParentVectorSequenceException("Empty parent vector sequence")
		
		if len(utils.squeeze(ipvSeq).strip()) == 0:
			raise EmptyInsertParentVectorSequenceException("Empty insert parent vector sequence")
			
		# Modified Oct. 28/08
		parentVectorSequence = pvSeq.upper()
	
		# Cut IPV at the appropriate places
		fpSite = "GTACAAAAAA"
		tpSite = "TTTCTTGTAC"
		
		fp_start = ipvSeq.upper().find(fpSite)
		#print `fp_start`
		
		# nov. 28/08
		if fp_start < 0:
			raise InsertSitesNotFoundOnParentSequenceException("attR1 site not found on parent vector sequence")
		
		fp_end = fp_start + len(fpSite)
	
		tp_start = ipvSeq.upper().find(tpSite)
		
		# nov. 28/08
		if tp_start < 0:
			raise InsertSitesNotFoundOnParentSequenceException("attR1 site not found on parent vector sequence")
		
		tp_end = tp_start + len(tpSite)
	
		iSeq = ipvSeq[fp_start:tp_end]
	
		# removed Oct. 28/08
		## June 8/08:
		#attR1_a = "TACAAGTTT"
		#attR2_b = "AAAGTGG"

		# check core sequence
		numAttR1_pv = utils.numOccurs(pvSeq, self.gatewayDict['attR1'])
		
		if numAttR1_pv == 0:
			raise InsertSitesNotFoundOnParentSequenceException("attR1 site not found on parent vector sequence")
		
		elif numAttR1_pv > 1:
			raise MultipleSiteOccurrenceException("attR1 found more than once on parent vector sequence")
		
		numAttR2_pv = utils.numOccurs(pvSeq, self.gatewayDict['attR2'])
		
		if numAttR2_pv == 0:
			raise InsertSitesNotFoundOnParentSequenceException("attR2 site not found on parent vector sequence")
		
		elif numAttR2_pv > 1:
			raise MultipleSiteOccurrenceException("attR2 found more than once on parent vector sequence")

		# changed oct 28/08
		#if pvSeq.find(attR1_a) > 0 and pvSeq.find(attR2_b) > 0:
			## Changed June 8/08
			#startpos = pvSeq.find(attR1_a) + len(attR1_a)
			#endpos = pvSeq.find(attR2_b)

			#if startpos > endpos:
				#raise FivePrimeAfterThreePrimeException("attR1 occurs after attR2 on parent vector sequence")
			
			#seq_remove = pvSeq[startpos:endpos]

			## action
			#newSeq = pvSeq.replace(seq_remove, ipvSeq)
		
		# oct 28/08
		if parentVectorSequence.find(self.gatewayDict['attR1']) >= 0 and parentVectorSequence.find(self.gatewayDict['attR2']) >= 0:
			
			#startpos = parentVectorSequence.find(attR1_a) + len(attR1_a)
			startpos = parentVectorSequence.find(self.gatewayDict['attR1']) + 3		# cuts after the 'TTT'
			#endpos = parentVectorSequence.find(attR2_b)
			endpos = parentVectorSequence.find(self.gatewayDict['attR2']) + len(self.gatewayDict['attR2'])
	
			if startpos > endpos:
				#raise FivePrimeAfterThreePrimeException("attR1 occurs after attR2 on parent vector sequence")
				print "attR1 occurs after attR2 on parent vector sequence"
				return newSeq
			
			seq_remove = parentVectorSequence[startpos:endpos]
	
			# action
			newSeq = parentVectorSequence.upper().replace(seq_remove, iSeq.lower())
		else:
			raise InsertSitesNotFoundOnParentSequenceException("attR sites not found on parent vector sequence")

		return newSeq


##################################################################################
# Class RNAHandler
# Descendant of SequenceHandler, handles RNA sequence operations
# Written October 22, 2009, by Marina Olhovsky
##################################################################################
class RNAHandler(SequenceHandler):
	"Contains functions and attributes specific to RNA sequences"

	# global variable
	sitesDict = {}
	enzDict = {}
	
	nucleotides = ['A', 'a', 'C', 'c', 'G', 'g', 'U', 'u', 'N', 'n']
	
	rnaSeqTypeID = 3

	# Parameters: seq = RNASequence object
	def matchSequence(self, seq):
		db = self.db			# localize for easy access
		cursor = self.cursor		# ditto
		
		newRNASeqID = 0
		rnaSeqTypeID = self.rnaSeqTypeID
		
		if seq.isRNA():
			rna_seq = seq.getSequence()
			start = seq.getSeqStart()
			end = seq.getSeqEnd()
			rna_len = len(rna_seq)
			
			if rna_len > 0:
				cursor.execute("SELECT `seqID` FROM `Sequences_tbl` WHERE `seqTypeID`=" + `rnaSeqTypeID` + " AND `sequence`=" + `rna_seq` + " AND `start`=" + `start` + " AND `end`=" + `end` + " AND `length`=" + `rna_len` + " AND `status`='ACTIVE'")
				result = cursor.fetchone()	# technically there should only be one; if more it's an error, but just assume one for now and add error checks later

				if result:
					newRNASeqID = int(result[0])
					
		return int(newRNASeqID)
	

	# Insert a new sequence into OpenFreezer
	# Parameters: seq = RNASequence object
	def insertSequence(self, seq):
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access
		
		newRNASeqID = 0
		rnaSeqTypeID = self.rnaSeqTypeID
		
		if seq.isRNA():
			rna_seq = seq.getSequence()
			start = seq.getSeqStart()
			end = seq.getSeqEnd()
			rna_len = len(rna_seq)
			rna_mw = seq.getMW()
			
			if rna_len > 0:
				cursor.execute("INSERT INTO Sequences_tbl(seqTypeID, sequence, start, end, length, mw) VALUES(" + `rnaSeqTypeID` + ", " + `rna_seq` + ", " + `start` + ", " + `end` + ", " + `rna_len` + ", " + `rna_mw` + ")")
				newRNASeqID = int(db.insert_id())
			
		return newRNASeqID
	
	
	def getSequenceID(self, seq):
		db = self.db			# for easy access
		cursor = self.cursor		# for easy access

		# Feb. 26/08: Make sure seq is not null
		if seq:
			if seq.isRNA():
				rna_seq = seq.getSequence()
				start = seq.getSeqStart()
				end = seq.getSeqEnd()
				rna_len = len(rna_seq)
	
				if len(rna_seq) > 0:
					# First check if this sequence is already stored in LIMS
					newRNASeqID = self.matchSequence(seq)
				
					if newRNASeqID == 0:
						newRNASeqID = self.insertSequence(seq)
						
					return newRNASeqID
		return -1
	
	
	def squeeze(self, seq):
		new_seq = ""
		nextInd = 0
		
		while nextInd < len(seq):
			nextChar = seq[nextInd]
			
			if nextChar in self.nucleotides:
				new_seq += nextChar
				
			nextInd += 1
		
		return new_seq


	def calculateMW_RNA(self, sequence):
		
		#print "Content-type:text/html"		# TEMPORARY, REMOVE AFTER DEBUGGING TO HAVE SCRIPT REDIRECT PROPERLY!!!!!!
		#print				
		
		mw = 0.0
		sequence = sequence.upper()
		#print sequence
		
		if len(sequence) == 0:
			return 0
		
		# Molecular Weight = (An x 329.21) + (Un x 306.17) + (Cn x 305.18) + (Gn x 345.21) + 159.0

		mw_dict = {'A': 313.209, 'G':329.208, 'C':289.184, 'U':306.17}
		
		num_A = float(sequence.count('A'))
		num_C = float(sequence.count('C'))
		num_G = float(sequence.count('G'))
		num_U = float(sequence.count('U'))
		
		weight_A = num_A * mw_dict['A']
		weight_C = num_C * mw_dict['C']
		weight_G = num_G * mw_dict['G']
		weight_U = num_U * mw_dict['U']
		
		base_weight = float(weight_A) + float(weight_C) + float(weight_G) + float(weight_U)
		#print `base_weight`
		
		mw = base_weight + 159.00
		#print round(mw, 2)
		return round(mw, 2)
