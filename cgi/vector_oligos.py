#!/usr/local/bin/python

import cgi
import cgitb; cgitb.enable()

import MySQLdb
import sys
import string

from database_conn import DatabaseConn

from mapper import ReagentPropertyMapper, ReagentAssociationMapper
from general_handler import *
from reagent_handler import *
from sequence_handler import DNAHandler, ProteinHandler
from comment_handler import CommentHandler
#from system_set_handler import SystemSetHandler
from sequence_handler import SequenceHandler
from reagent import *

import utils

from user_handler import UserHandler
from project_database_handler import ProjectDatabaseHandler
from session import Session
from exception import *

dbConn = DatabaseConn()
db = dbConn.databaseConnect()

cursor = db.cursor()
hostname = dbConn.getHostname()
root_path = dbConn.getRootDir()

dnaHandler = DNAHandler(db, cursor)
protHandler = ProteinHandler(db, cursor)
rHandler = ReagentHandler(db, cursor)
oHandler = OligoHandler(db, cursor)
propHandler = ReagentPropertyHandler(db, cursor)
packetHandler = ProjectDatabaseHandler(db, cursor)

propMapper = ReagentPropertyMapper(db, cursor)
prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)

prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()


# currUser is an INT user ID
def getCurrentUserProjects(currUser):
	
	#print "Content-type:text/html"
	#print
	
	# get projects user has AT LEAST Read access to (i.e. if he is explicitly declared a Writer on a project but not declared a Reader, that's allowed)
	currReadProj = packetHandler.findMemberProjects(currUser, 'Reader')
	currWriteProj = packetHandler.findMemberProjects(currUser, 'Writer')
	publicProj = packetHandler.findAllProjects(isPrivate="FALSE")
	
	# list of Packet OBJECTS
	currUserWriteProjects = utils.unique(currReadProj + currWriteProj + publicProj)
	
	if currUser == 1:
		privateProjects = packetHandler.findAllProjects("TRUE")
		currUserWriteProjects = utils.unique(privateProjects + publicProj)
		
	uPackets = []
	
	for p in currUserWriteProjects:
		uPackets.append(p.getNumber())

	return uPackets


def getOligos(rID, seq, vectorID, userID):
	
	content = "\nSequencing Primers for " + vectorID + "\n\nVector length: " + `len(seq.strip())` + " nt\n"
	
	protocolPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["protocol"], prop_Category_Name_ID_Map["Classifiers"])
	seqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])
	
	cdnaPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["cdna insert"], prop_Category_Name_ID_Map["DNA Sequence Features"])
	
	projectPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["packet id"], prop_Category_Name_ID_Map["General Properties"])
	
	namePropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["name"], prop_Category_Name_ID_Map["General Properties"])
	
	statusPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["status"], prop_Category_Name_ID_Map["General Properties"])
	
	cdnaStart = rHandler.findReagentFeatureStart(rID, cdnaPropID)
	cdnaEnd = rHandler.findReagentFeatureEnd(rID, cdnaPropID)
	
	if cdnaStart > 0:
		content += "\ncDNA: " + `cdnaStart` + "-" + `cdnaEnd` + "\n"
	
	# find all Oligos whose protocol is 'sequencing'
	cursor.execute("SELECT r.reagentID, r.groupID, s.sequence FROM Sequences_tbl s, ReagentPropList_tbl p1, ReagentPropList_tbl p2, Reagents_tbl r WHERE p1.propertyID=" + `protocolPropID` + " AND p1.propertyValue='sequencing' AND p1.reagentID=r.reagentID AND r.reagentTypeID='3' AND p1.reagentID=p2.reagentID AND p2.propertyID=" + `seqPropID` + " AND p2.propertyValue=s.seqID AND p1.status='ACTIVE' AND p2.status='ACTIVE' AND r.status='ACTIVE' AND s.status='ACTIVE'")
	results = cursor.fetchall()
	
	seq = utils.squeeze(seq).lower()
	
	uPackets = getCurrentUserProjects(userID)
	
	#print "Content-type:text/html"
	#print
	#print reagentID
	uPackets.sort()
	#print `uPackets`
	
	features = rHandler.findAllReagentFeatures(Vector(rID), rID)
	overlapping_features = []
	
	tmp_features = {}	# linear list of oligos, regular array
	
	for result in results:
		overlapping_features = []
		
		if result:
			
			#print "Content-type:text/html"
			#print
			#print len(result)
			
			oligoID = int(result[0])
			groupID = int(result[1])
			
			oligo_lims_id = "O" + `groupID`
			#print oligo_lims_id
			
			# April 26, 2010, Karen's request: Ignore Oligos whose status is 'Failed' or 'Do Not Use'
			oligoStatus = rHandler.findSimplePropertyValue(oligoID, statusPropID)

			if oligoStatus:
				#print oligoStatus
				if oligoStatus.lower() == 'failed' or oligoStatus.lower() == 'do not use':
					continue
			
			oligoPacket = int(rHandler.findSimplePropertyValue(oligoID, projectPropID))
			#print oligoPacket
			
			if oligoPacket:
				if oligoPacket not in uPackets:
					#print oligoPacket
					continue
			
			oligoName = rHandler.findSimplePropertyValue(oligoID, namePropID)
			
			#if oligoName:
				#oligoNamesDict[oligoID] = oligoName
			#else:
				#oligoNamesDict[oligoID] = ""
			
			#print `features`
			
			if len(result) == 3:
				oligoSeq = utils.squeeze(result[2].strip()).lower()
				#print oligoSeq
				
				tm = dnaHandler.calculateTm(oligoSeq)
				
				if seq.find(oligoSeq) >= 0:
					fStart = seq.find(oligoSeq) + 1
					fEnd = fStart + len(oligoSeq) - 1
					
					direction = "forward"
					
					for f_tmp in features:
						tmp_f_Start = f_tmp.getFeatureStartPos()
						tmp_f_End = f_tmp.getFeatureEndPos()
						
						# 4 cases:
						# Feature contained within ORF (tmp_fStart >= fStart and tmp_fEnd <= fEnd)
						# Feature starts inside ORF (tmp_fStart >= fStart and tmp_fStart <= fEnd)
						# Feature ends inside ORF (tmp_fEnd >= fStart and tmp_fEnd <= fEnd)
						# ORF contained within feature (tmp_fStart <= fStart and tmp_fEnd >= fEnd)
						if (tmp_f_Start >= fStart and tmp_f_End <= fEnd) or (tmp_f_End >= fStart and tmp_f_End <= fEnd) or (tmp_f_Start >= fStart and tmp_f_Start <= fEnd) or (tmp_f_Start <= fStart and tmp_f_End >= fEnd):
							overlapping_features.append(f_tmp)
					
				else:
					revSeq = dnaHandler.reverse_complement(oligoSeq)
					direction = "reverse"
					
					if seq.find(revSeq) < 0:
						continue
					
					fStart = seq.find(revSeq) + 1
					fEnd = fStart + len(revSeq)
					
					# Note: start pos > end pos, as we're talking about reverse Oligo sequence
					revStart = fEnd - 1
					revEnd = fStart
				
					for f_tmp in features:
						tmp_f_Start = f_tmp.getFeatureStartPos()
						tmp_f_End = f_tmp.getFeatureEndPos()
						
						# 4 cases: (remember, revStart > revEnd, e.g. O1100 for V2559 - start 2583, end 2559)
						# Feature contained within ORF (tmp_f_Start <= revStart and tmp_f_End >= revEnd)
						# Feature starts inside ORF (tmp_f_Start >= revStart and tmp_f_Start <= revEnd)
						# Feature ends inside ORF (tmp_f_End >= revStart and tmp_f_End <= revEnd)
						# ORF contained within feature (tmp_f_Start >= revStart and tmp_f_End <= revEnd)
						if (tmp_f_Start >= revStart and tmp_f_End <= revEnd) or (tmp_f_End >= revStart and tmp_f_End <= revEnd) or (tmp_f_Start >= revStart and tmp_f_Start <= revEnd) or (tmp_f_Start <= revStart and tmp_f_End >= revEnd):
							overlapping_features.append(f_tmp)
					
					fStart = revEnd
					fEnd = revStart

				oFeature = SequenceFeature(oligoName, oligo_lims_id, fStart, fEnd, direction, oligoSeq, tm)
				tmp_features[oFeature] = overlapping_features
	
	tmp_oligos = {}
	
	for tmpFeature in tmp_features.keys():
		
		oligoStart = tmpFeature.getFeatureStartPos()
		
		if tmp_oligos.has_key(oligoStart):
			tmp_o_list = tmp_oligos[oligoStart]
		else:
			tmp_o_list = []
			tmp_oligos[oligoStart] = tmpFeature
		
		tmp_o_list.append(tmpFeature)
		tmp_oligos[oligoStart] = tmp_o_list
		
	for oligoStart in sorted(tmp_oligos.keys()):
		oFeatures = tmp_oligos[oligoStart]
		
		for tmpFeature in oFeatures:
			
			overlapping_features = tmp_features[tmpFeature]
			
			oligoName = tmpFeature.getFeatureType()
			oligo_lims_id = tmpFeature.getFeatureName()
			oligoSeq = tmpFeature.getFeatureDescrType()
			tm = tmpFeature.getFeatureDescrName()
			
			fStart = tmpFeature.getFeatureStartPos()
			fEnd = tmpFeature.getFeatureEndPos()
			
			direction = tmpFeature.getFeatureDirection()
			
			if oligoName:
				content += '\n--------------------------------------------------\n' + oligo_lims_id + " " + oligoName + ": " + oligoSeq + ", " + direction + "\n\n"
			
			else:
				content += '\n--------------------------------------------------\n' + oligo_lims_id + ": " + oligoSeq + ", " + direction + "\n\n"
				
			content += "Start position = " + `fStart` + "\n"
			content += "Stop position = " + `fEnd` + "\n"
			
			content += "\nTm: " + tm + "ºC\n"
	
			if len(overlapping_features) > 0:
				content += "\nOverlapping features:\n"
			
				for tmp_f in overlapping_features:
					
					tmp_fStart = tmp_f.getFeatureStartPos()
					tmp_fEnd = tmp_f.getFeatureEndPos()
			
					if tmp_f.getFeatureType().lower() == 'cdna insert':
						content += "cDNA"
					#elif tmp_f.getFeatureType().lower() == 'intron':
						#content += tmp_f.getFeatureName().title() + " intron"
					elif tmp_f.getFeatureType().lower() == 'promoter':
						content += tmp_f.getFeatureName().title() + " promoter"
					elif tmp_f.getFeatureType().lower() == "5' cloning site" or tmp_f.getFeatureType().lower() == "3' cloning site":
						content += tmp_f.getFeatureName().title() + " cloning site"
					elif tmp_f.getFeatureType().lower() == 'restriction site':
						content += tmp_f.getFeatureName().title() + " restriction site"
					elif tmp_f.getFeatureType().lower() == 'intron':
						content += tmp_f.getFeatureName().title() + " intron"
					elif tmp_f.getFeatureType().lower() == 'polya':
						content += tmp_f.getFeatureName().title() + " polyA"
					elif tmp_f.getFeatureType().lower() == 'origin':
						content += tmp_f.getFeatureName().title() + " ori."
					elif tmp_f.getFeatureType().lower() == 'tag type':
						content += tmp_f.getFeatureName().title() + " tag"
					elif tmp_f.getFeatureType().lower() == 'transcription terminator':
						content += tmp_f.getFeatureName().title() + " tscn. ter."
					else:
						content += tmp_f.getFeatureName().title()
						
					content += ": " + `tmp_fStart` + "-" + `tmp_fEnd` + '\n'

	content +="\n--------------------------------------------------\n\nNOTE: Only Oligos in OpenFreezer whose protocol is 'sequencing' and whose sequence is an exact match to the vector are reported.\n"
	return content
	
	
def main():
	
	#print "Content-type:text/html"
	#print
	
	form = cgi.FieldStorage(keep_blank_values="True")
	
	# DEBUG
	#print "Content-type: text/html"
	#print
	#print `form`
	#return
	
	print "Content-type: application/octet-stream"
		
	if form.has_key("rID"):
		rID = form.getvalue("rID")
		seq = form.getvalue("vector_sequence")
	else:
		# debug
		#rID = 115818	# V4589
		rID = 115379	# V4539
		
		# update July 3/09
		seqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])
		seqID = rHandler.findIndexPropertyValue(rID, seqPropID)
		
		#seqID = rHandler.findIndexPropertyValue(rID, prop_Name_ID_Map["sequence"])		# removed July 3/09
		seq = dnaHandler.findSequenceByID(seqID)
	
	if form.has_key("user_id_hidden"):
		userID = form.getvalue("user_id_hidden")
	else:	# command-line execution
		userID = 1	# debug

	vectorID = rHandler.convertDatabaseToReagentID(rID)
	#print vectorID
	
	fname = vectorID + "_oligos.doc"
	
	print "Content-Disposition: attachment; name=" + fname
	
	content = getOligos(rID, seq, vectorID, userID)
	
	print '\n' + content
	
main()