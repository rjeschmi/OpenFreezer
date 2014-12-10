from sequence_feature import *

##################################################################################
# Module Sequence
#
# Contains classes that represent Sequence objects (dna or protein) in OpenFreezer
# Written October 10, 2006, by Marina Olhovsky
#
# Last modified: October 30, 2006
##################################################################################
class Sequence(object):
	"Top-level hierarchy for other sequence types"
	
	# Attributes common to all sequences
	__seq = ""
	__length = 0
	
	def __init__(self, seq):
		self.__seq = seq
		self.__length = len(seq)
	
	# Simple access methods:	
	def getSequence():
		return self.__sequence
	
	def getLength():
		return self.__length
	
	########################################################################
	# Plain access methods
	# Simply return various attributes, without going to the database
	########################################################################
	
	def getSequence(self):
		return self.__seq
	
	def getLength(self):
		return self.__length


################################################################################
# Class ProteinSequence
# This class represents a Protein sequence object in OpenFreezer
# Written October 10, 2006, by Marina Olhovsky
# Last modified: June 5/08
################################################################################
class ProteinSequence(Sequence):
	"Represents a Protein sequence object in OpenFreezer"
	
	# Initialize private vars
	__frame = 0
	__start = -1
	__end = -1
	__seq = ""
	__orientation = 'forward'	# jan. 26/09
	__mw = 0.00			# jan. 30/09

	# Nov. 19/09 - this is a list of SequenceFeature objects
	__featureNames = ["Cleavage Site", "Miscellaneous", "Tag"]
	__features = []
	
	# Constructor
	# Updated Jan. 30/09: added optional peptideMass argument
	def __init__(self, seq, frame=0, start=0, end=0, mw=0.0):
		super(ProteinSequence, self).__init__(seq)
		self.__seq = seq
		self.__frame = frame
		self.__start = start
		self.__end = end
		self.__mw = mw
		
		for f in self.__featureNames:
			tmpFeature = SequenceFeature(f)
			#tmpFeature.setFeatureType(f)
			self.__features.append(tmpFeature)

	# Return protein sequence
	def getSequence(self):
		return self.__seq
	
	# Return sequence start
	def getSeqStart(self):
		return self.__start
	
	# Return sequence end
	def getSeqEnd(self):
		return self.__end
	
	# Return frame
	def getFrame(self):
		return self.__frame
	
	# Jan. 26/09
	def getOrientation(self):
		return self.__orientation
	
	# Jan. 30/09: Return MW
	def getMW(self):
		return self.__mw
	
	# Change frame to parameter value
	def setFrame(self, frame):
		self.__frame = frame
		
	# Change sequence start to parameter value
	def setSeqStart(self, start):
		self.__start = start
		
	# Change sequence end to parameter value
	def setSeqEnd(self, end):
		self.__end = end
	
	# Jan. 30/09
	def setMW(self, mw):
		self.__mw = mw
	
	# jan. 26/09
	def setOrientation(self, orientation):
		self.__orientation = orientation
		
	def isProtein(self):
		return True
	
	# Nov. 19/09
	@classmethod
	def getProteinSequenceFeatures(ProteinSequence):
		return ProteinSequence.__features
	
	# Nov. 19/09
	@classmethod
	def getProteinSequenceFeatureNames(ProteinSequence):
		return ProteinSequence.__featureNames
	
################################################################################
# Class DNASequence
# This class represents a DNA sequence object in OpenFreezer
# Written October 27, 2006, by Marina Olhovsky
################################################################################
class DNASequence(Sequence):
	"Represents a DNA sequence object in OpenFreezer"

	# Nov. 19/09
	__featureNames = ["5' Cloning Site", "3' Cloning Site", "5' Linker", "3' Linker", "cDNA", "Cleavage Site", "Intron", "Miscellaneous", "Origin of Replication", "PolyA Tail", "Promoter", "Restriction Site", "Selectable Marker", "Tag", "Transcription Terminator"]
	
	__features = []		# Nov. 19/09 - this is a list of SequenceFeature objects
	
	# May 17, 2010: MW, Tm, translation - calculated automatically
	__dnaSeqAttributes = ["molecular weight", "protein translation", "melting temperature", "gc content"]
	
	# Constructor
	def __init__(self, seq):
		
		for f in self.__featureNames:
			tmpFeature = SequenceFeature(f)
			#tmpFeature.setFeatureType(f)
			self.__features.append(tmpFeature)

		super(DNASequence, self).__init__(seq)
		
	def isDNA(self):
		return True
	
	# Nov. 19/09
	@classmethod
	def getDNASequenceFeatures(DNASequence):
		return DNASequence.__features
	
	# Nov. 19/09
	@classmethod
	def getDNASequenceFeatureNames(DNASequence):
		return DNASequence.__featureNames
	
	# May 14, 2010
	@classmethod
	def getDNASequenceAttributes(DNASequence):
		return DNASequence.__dnaSeqAttributes
	
	
################################################################################
# Class RNASequence
# This class represents an RNA sequence object in OpenFreezer
# Written October 22, 2009, by Marina Olhovsky
################################################################################
class RNASequence(Sequence):
	
	# Initialize private vars
	__start = -1
	__end = -1
	__seq = ""
	__orientation = 'forward'
	__mw = 0.0			# Jan. 25/10
	
	__featureNames = []	# April 11, 2011: made empty
	
	__features = []		# Nov. 19/09 - this is a list of SequenceFeature objects
	
	# Constructor
	def __init__(self, seq, start=0, end=0, mw=0):
		super(RNASequence, self).__init__(seq)
		self.__seq = seq
		self.__start = start
		self.__end = end
		self.__mw = mw
		
		for f in self.__featureNames:
			tmpFeature = SequenceFeature(f)
			#tmpFeature.setFeatureType()
			self.__features.append(tmpFeature)

	# Return RNA sequence
	def getSequence(self):
		return self.__seq
	
	# Return sequence start
	def getSeqStart(self):
		return self.__start
	
	# Return sequence end
	def getSeqEnd(self):
		return self.__end
	
	# Jan. 26/09
	def getOrientation(self):
		return self.__orientation
	
	# Jan. 25/10
	def getMW(self):
		return self.__mw
		
	# Change sequence start to parameter value
	def setSeqStart(self, start):
		self.__start = start
		
	# Change sequence end to parameter value
	def setSeqEnd(self, end):
		self.__end = end
	
	# jan. 26/09
	def setOrientation(self, orientation):
		self.__orientation = orientation
		
	# Jan. 25/10
	def setMW(self, mw):
		self.__mw = mw
	
	def isRNA(self):
		return True
		
	# Nov. 19/09
	@classmethod
	def getRNASequenceFeatures(RNASequence):
		return RNASequence.__features
	
	
	# Nov. 19/09
	@classmethod
	def getRNASequenceFeatureNames(RNASequence):
		return RNASequence.__featureNames