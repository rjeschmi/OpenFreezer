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
propHandler = ReagentPropertyHandler(db, cursor)

propMapper = ReagentPropertyMapper(db, cursor)
prop_Name_ID_Map = propMapper.mapPropNameID()		# (prop name, prop id)

prop_Category_Name_ID_Map = propMapper.mapPropCategoryNameID()

normal_frames = []
orf_no = 1

# Jan. 23/09
# Translate sequence in 6 frames, including reverse
def getAllFrames(rID, seq, vectorID):
	global orf_no
	global normal_frames
	
	fwd_frames = {}
	rev_frames = {}
	
	# debug reverse
	#seq = dnaHandler.reverse_complement(seq)
	#print seq
	#protHandler.findVectorORFs(seq, 1)
	#return
	
	# Translate forward-oriented sequence in 3 frames
	if len(seq) > 0:
		for frame in range(1,4):
			prots = protHandler.findVectorORFs(seq, frame)
		
			if len(prots) > 0:
				for protEntry in prots:
					peptideSeq = protEntry.getSequence()
					sStart = protEntry.getSeqStart()
					sEnd = protEntry.getSeqEnd()
					
					if len(peptideSeq) > 0 and sStart > 0 and sEnd > 0:
							
						if fwd_frames.has_key(frame):
							tmp_fwd = fwd_frames[frame]
							tmp_fwd.append(protEntry)
							fwd_frames[frame] = tmp_fwd
						else:
							tmp_fwd = []
							tmp_fwd.append(protEntry)
							fwd_frames[frame] = tmp_fwd
	
			## increment loop counter
			#frame += 1
			
	
	# Translate REVERSE-oriented sequence in 3 frames
	if len(seq) > 0:
		seq = dnaHandler.reverse_complement(seq)
	
		for frame in range(1,4):
			tmp_rev = []
			
			#print "Translating F" + `frame`
			
			#protEntry = protHandler.translate(seq, frame, openClosed)
			prots = protHandler.findVectorORFs(seq, frame)
			
			if len(prots) > 0:
				for protEntry in prots:
					protEntry.setOrientation('reverse')
					peptideSeq = protEntry.getSequence()
					
					sStart = protEntry.getSeqStart()
					sEnd = protEntry.getSeqEnd()
					
					if len(peptideSeq) > 0 and sStart > 0 and sEnd > 0:
						#f_ind = frame+1	# jan. 27/09: decided not to do this, too confusing
						f_ind = frame
						#print f_ind
						
						if rev_frames.has_key(f_ind):
							tmp_rev = rev_frames[f_ind]
							tmp_rev.append(protEntry)
							rev_frames[f_ind] = tmp_rev
						else:
							tmp_rev = []
							tmp_rev.append(protEntry)
							rev_frames[f_ind] = tmp_rev
	
	# feature overlap
	tmpVector = Vector(rID)
	features = rHandler.findAllReagentFeatures(tmpVector, rID)
	
	content = ""
	
	#content += "======================================================\n"
	content += "\nOpen Reading Frames for " + vectorID + "\n"
	content += "======================================================\n\n"
	#orf_no = 1

	for f in fwd_frames.keys():
		#print "Frame " + `f`	# don't use in browser
		#print `fwd_frames`
		if fwd_frames[f]:
			fList = fwd_frames[f]
			
			if f != 1:
				content += '\n'
				
			content += "******************************************************\n5'3' frame " + `f` + "\n******************************************************\n"
			
			for fTemp in fList:
				frame = fTemp.getSequence()
				
				# Jan. 26/09
				fStart = fTemp.getSeqStart()
				fEnd = fTemp.getSeqEnd()
				#print frame			# debug - don't use in browser
				mw = fTemp.getMW()
				
				if len(frame) >= 100:
					content += "\nORF no. " + `orf_no` + '\n======================\n'
					
					normal_frames.append(frame)

					#f_out.write("Frame " + `f` + "\n")
					#f_out.write(frame + '\n'+'\n')
					
					# Jan. 26/09
					content += "Start position = " + `fStart` + "\n"
					content += "Stop position = " + `fEnd` + "\n"
					content += "Length: " + `len(frame)` + " aa\n"
					
					# add MW and feature overlap
					content += "Molecular Weight: " + str(mw) + ' kDa\n\n'
					
					# Removed Feb. 5/09 - this layout is split in Word and most users don't like it - confirm with Karen
					## Jan. 26/09: output sequence in chunks of 100
					#i = 0
					
					#while i < len(frame):
						#content += frame[i:i+100]
						#content += '\t'
						
						#if len(frame) - i >= 100:
							#content += `i+100` + '\n'
							
						#i += 100
					
					# Feb. 5/09
					#content += "\n"
					content += frame
					#content += "\n"

					# OVERLAPPING FEATURES
					content += "\n\nOverlapping features:\n---------------------\n"
					
					for tmp_f in features:
						tmp_fStart = tmp_f.getFeatureStartPos()
						tmp_fEnd = tmp_f.getFeatureEndPos()
						
						# 4 cases:
						# Feature contained within ORF (tmp_fStart >= fStart and tmp_fEnd <= fEnd)
						# Feature starts inside ORF (tmp_fStart >= fStart and tmp_fStart <= fEnd)
						# Feature ends inside ORF (tmp_fEnd >= fStart and tmp_fEnd <= fEnd)
						# ORF contained within feature (tmp_fStart <= fStart and tmp_fEnd >= fEnd)
						if (tmp_fStart >= fStart and tmp_fEnd <= fEnd) or (tmp_fEnd >= fStart and tmp_fEnd <= fEnd) or (tmp_fStart >= fStart and tmp_fStart <= fEnd) or (tmp_fStart <= fStart and tmp_fEnd >= fEnd):
							if tmp_f.getFeatureType().lower() == 'cdna insert':
								content += "cDNA"
							elif tmp_f.getFeatureType().lower() == 'promoter':
								content += tmp_f.getFeatureName().title() + " promoter"
							elif tmp_f.getFeatureType().lower() == "5' cloning site" or tmp_f.getFeatureType().lower() == "3' cloning site":
								content += tmp_f.getFeatureName().title() + " cloning site"
							elif tmp_f.getFeatureType().lower() == 'restriction site':
								content += tmp_f.getFeatureName().title() + " restriction site"
							elif tmp_f.getFeatureType().lower() == 'polya':
								content += tmp_f.getFeatureName().title() + " polyA"
							#elif tmp_f.getFeatureType().lower() == 'intron':
								#content += tmp_f.getFeatureName().title() + " intron"
							elif tmp_f.getFeatureType().lower() == 'origin':
								content += tmp_f.getFeatureName().title() + " ori."
							#elif tmp_f.getFeatureType().lower() == 'tag':
								#content += tmp_f.getFeatureName().title() + " tag"
							elif tmp_f.getFeatureType().lower() == 'transcription terminator':
								content += tmp_f.getFeatureName().title() + " tscn. ter."
							else:
								content += tmp_f.getFeatureName().title()
								
							content += ": " + `tmp_fStart` + "-" + `tmp_fEnd` + '\n'
						
					content += "\n"
				#else:
					##f_out.write("Frame " + `f` + "\n")
					##f_out.write("Shorter than 100 nt\n\n")
					
					#content += ("Frame " + `f` + "\n")
					#content += ("Shorter than 100 aa\n\n")
					
				orf_no += 1
		#else:
			#content += "Shorter than 100 aa\n\n"
			
	content += "\n"
	#orf_no = 1	# no, don't reset, let increment after 5'3'

	for f in rev_frames.keys():
		#print "Frame " + `f`	# don't use in browser, only for command-line debugging
		
		if rev_frames[f]:
			rList = rev_frames[f]
			
			if f != 1:
				content += '\n'
				
			content += "******************************************************\n3'5' frame " + `f` + "\n******************************************************\n"
			
			for rTemp in rList:
				frame = rTemp.getSequence()
				
				#print frame	# don't use in browser, only for command-line
				
				# Jan. 26/09
				fStart = rTemp.getSeqStart()
				fEnd = rTemp.getSeqEnd()
				mw = rTemp.getMW()
				
				if len(frame) >= 100:
					content += "\nORF no. " + `orf_no` + '\n======================\n'
					
					normal_frames.append(frame)
				
					#f_out.write("Frame " + `f` + "\n")
					#f_out.write(frame + '\n'+'\n')
					
					# Jan. 28/09
					revStart = len(seq) - fEnd + 1
					revEnd = revStart + len(frame)*3 - 1
					
					# Jan. 26/09
					#content += "Start position = " + `fStart` + "\n"
					#content += "Stop position = " + `fEnd` + "\n"
					
					# Jan. 28/09: Because it's in reverse orientation, swap stop and start
					content += "Start position = " + `revEnd` + "\n"
					content += "Stop position = " + `revStart` + "\n"
					
					content += "Length: " + `len(frame)` + " aa\n"
					
					# add MW and feature overlap
					content += "Molecular Weight: " + str(mw) + " kDa\n\n"
					
					# Removed Feb. 5/09 - this layout is split in Word and most users don't like it - confirm with Karen
					## Jan. 26/09: output sequence in chunks of 100
					#i = 0
					
					#while i < len(frame):
						#content += frame[i:i+100]
						#content += '\t'
						
						#if len(frame) - i >= 100:
							#content += `i+100` + '\n'
							
						#i += 100
					
					# Feb. 5/09
					#content += "\n"
					content += frame
					#content += "\n"
					
					# OVERLAPPING FEATURES
					content += "\n\nOverlapping features:\n---------------------\n"
					
					for tmp_f in features:
						tmp_fStart = tmp_f.getFeatureStartPos()
						tmp_fEnd = tmp_f.getFeatureEndPos()
						
						# 4 cases:
						# Feature contained within ORF (tmp_fStart >= revStart and tmp_fEnd <= revEnd)
						# Feature starts inside ORF (tmp_fEnd >= revStart and tmp_fEnd <= revEnd)
						# Feature ends inside ORF (tmp_fStart >= revStart and tmp_fStart <= revEnd)
						# ORF contained within feature (tmp_fStart <= revStart and tmp_fEnd >= revEnd)
						if (tmp_fStart >= revStart and tmp_fEnd <= revEnd) or (tmp_fEnd >= revStart and tmp_fEnd <= revEnd) or (tmp_fStart >= revStart and tmp_fStart <= revEnd) or (tmp_fStart <= revStart and tmp_fEnd >= revEnd):
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
							#elif tmp_f.getFeatureType().lower() == 'tag type':
								#content += tmp_f.getFeatureName().title() + " tag"
							elif tmp_f.getFeatureType().lower() == 'transcription terminator':
								content += tmp_f.getFeatureName().title() + " tscn. ter."
							else:
								content += tmp_f.getFeatureName().title()
								
							content += ": " + `tmp_fStart` + "-" + `tmp_fEnd` + '\n'
						
					content += "\n"
				#else:
					##f_out.write("Frame " + `f` + "\n")
					##f_out.write("Shorter than 100 nt\n\n")
					
					#content += "Frame " + `f` + "\n"
					#content += "Shorter than 100 aa\n\n"
					
				orf_no += 1
		#else:
			#content += "Shorter than 100 aa\n\n"
	
	#print content
	return content
	
	
def getSpliceORFs(rID, seq, vectorID):
	global orf_no
	global normal_frames
	
	content = ""
	
	# Feb. 4/09: Add SPLICE ORFs
	# Not using findReagentFeatureStart here, because it does not return multiple values as of now - needs fixing in reagent_handler, but for this purpose use 'features' list that was used earlier
	splice_donor_pos = {}
	splice_acceptor_pos = {}
	
	fwd_frames = {}
	rev_frames = {}
	
	tmpVector = Vector(rID)
	features = rHandler.findAllReagentFeatures(tmpVector, rID)
	
	#print `features`
	
	for tmp_feature in features:
		if tmp_feature.getFeatureType() == "miscellaneous":
			if tmp_feature.getFeatureName() == "splice donor":
				tmp_start_pos = tmp_feature.getFeatureStartPos()
				tmp_end_pos = tmp_feature.getFeatureEndPos()
				splice_donor_pos[tmp_start_pos] = tmp_end_pos
			elif tmp_feature.getFeatureName() == "splice acceptor":
				tmp_start_pos = tmp_feature.getFeatureStartPos()
				tmp_end_pos = tmp_feature.getFeatureEndPos()
				splice_acceptor_pos[tmp_start_pos] = tmp_end_pos
	
	#print "Donors: " + `splice_donor_pos`
	#print "Acceptors: " + `splice_acceptor_pos`
	
	minDiff = len(seq)
	tmpClosest = -1		# acceptor position (int)
	closestPairs = {}	# (donor START, acceptor END) tuples
	
	for dStart in splice_donor_pos.keys():
		for aStart in splice_acceptor_pos.keys():
			if aStart > dStart:
				tmpDiff = aStart - dStart
				
				if tmpDiff < minDiff:
					minDiff = tmpDiff
					aEnd = splice_acceptor_pos[aStart]
					tmpClosest = aEnd
		
		if tmpClosest > 0:
			closestPairs[dStart] = tmpClosest
			
		minDiff = len(seq)	# reset!!!!!!
	
	for donorStart in closestPairs.keys():
		#print donorStart
		acceptorEnd = closestPairs[donorStart]
		#print acceptorEnd
		seqPre = seq[0:donorStart-1]
		#print "pre " + seqPre
		seqPost = seq[acceptorEnd:]
		fusionSeq = seqPre + seqPost
		#print fusionSeq
	
		#content += getAllFrames(rID, fusionSeq, vectorID)
		
		# debug reverse
		#seq = dnaHandler.reverse_complement(seq)
		#print seq
		#protHandler.findVectorORFs(fusionSeq, 1)
		#return
		
		# Translate forward-oriented sequence in 3 frames
		if len(fusionSeq) > 0:
			for frame in range(1,4):
				prots = protHandler.findVectorORFs(fusionSeq, frame)
			
				if len(prots) > 0:
					for protEntry in prots:
						peptideSeq = protEntry.getSequence()
						sStart = protEntry.getSeqStart()
						sEnd = protEntry.getSeqEnd()
						
						# Change end position to include the splice portion
						origSeqEnd = sEnd + (acceptorEnd-donorStart) + 1
						protEntry.setSeqEnd(origSeqEnd)
						
						if len(peptideSeq) > 0 and sStart > 0 and sEnd > 0:
						
							if fwd_frames.has_key(frame):
								tmp_fwd = fwd_frames[frame]
								tmp_fwd.append(protEntry)
								fwd_frames[frame] = tmp_fwd
							else:
								tmp_fwd = []
								tmp_fwd.append(protEntry)
								fwd_frames[frame] = tmp_fwd
		
				## increment loop counter
				#frame += 1
				
		
		# Translate REVERSE-oriented sequence in 3 frames
		if len(fusionSeq) > 0:
			#print fusionSeq
			fusionSeq = dnaHandler.reverse_complement(fusionSeq)
			#print fusionSeq
		
			for frame in range(1,4):
				tmp_rev = []
				
				#print "Translating REVERSE F" + `frame`
				
				#protEntry = protHandler.translate(seq, frame, openClosed)
				prots = protHandler.findVectorORFs(fusionSeq, frame)
				
				if len(prots) > 0:
					for protEntry in prots:
						protEntry.setOrientation('reverse')
						peptideSeq = protEntry.getSequence()
						
						sStart = protEntry.getSeqStart()
						sEnd = protEntry.getSeqEnd()
						
						# Change end position to include the splice portion
						
						# Feb. 9/09: update splice donor/acceptor positions to be in reverse orientation too???
						rev_donor_start = acceptorEnd
						rev_acceptor_end = donorStart
						
						origSeqEnd = sEnd + (rev_donor_start-rev_acceptor_end) + 1
						
						protEntry.setSeqEnd(origSeqEnd)
						
						if len(peptideSeq) > 0 and sStart > 0 and sEnd > 0:
							#f_ind = frame+1	# jan. 27/09: decided not to do this, too confusing
							f_ind = frame
							#print f_ind
							
							if rev_frames.has_key(f_ind):
								tmp_rev = rev_frames[f_ind]
								tmp_rev.append(protEntry)
								rev_frames[f_ind] = tmp_rev
							else:
								tmp_rev = []
								tmp_rev.append(protEntry)
								rev_frames[f_ind] = tmp_rev

		content = "===============================================================\nORFs generated from potential splicing events:\n"
		content += "===============================================================\n\n"
		
		#print `normal_frames`
		#print `fwd_frames`
		#print `rev_frames`
		
		for f in fwd_frames.keys():
		
			#print "Frame " + `f`	# don't use in browser
			#print `fwd_frames`
			if fwd_frames[f]:
				
				fList = fwd_frames[f]
				#print `fList`
				#content += "******************************************************\n5'3' frame " + `f` + "\n******************************************************\n"
				
				for fTemp in fList:
					frame = fTemp.getSequence()
					#print frame
					
					# Only output new frames produced by splicing
					if frame not in normal_frames:
						if f != 1:
							content += '\n'
							
						content += "******************************************************\n5'3' frame " + `f` + "\n******************************************************\n"
				
						# Jan. 26/09
						fStart = fTemp.getSeqStart()
						fEnd = fTemp.getSeqEnd()
						#print frame			# debug - don't use in browser
						mw = fTemp.getMW()
						
						if len(frame) >= 100:
							content += "\nORF no. " + `orf_no` + '\n======================\n'
							
							#f_out.write("Frame " + `f` + "\n")
							#f_out.write(frame + '\n'+'\n')
							
							# Jan. 26/09
							content += "Start position = " + `fStart` + "\n"
							content += "Stop position = " + `fEnd` + "\n"
							content += "Length: " + `len(frame)` + " aa\n"
							
							# add MW and feature overlap
							content += "Molecular Weight: " + str(mw) + ' kDa\n\n'
							
							# Removed Feb. 5/09 - this layout is split in Word and most users don't like it - confirm with Karen
							## Jan. 26/09: output sequence in chunks of 100
							#i = 0
							
							#while i < len(frame):
								#content += frame[i:i+100]
								#content += '\t'
								
								#if len(frame) - i >= 100:
									#content += `i+100` + '\n'
									
								#i += 100
								
							# Feb. 5/09
							#content += "\n"
							content += frame
							#content += "\n"
							
							# OVERLAPPING FEATURES
							content += "\n\nOverlapping features:\n---------------------\n"
							
							for tmp_f in features:
								tmp_fStart = tmp_f.getFeatureStartPos()
								tmp_fEnd = tmp_f.getFeatureEndPos()
								
								# 4 cases:
								# Feature contained within ORF (tmp_fStart >= fStart and tmp_fEnd <= fEnd)
								# Feature starts inside ORF (tmp_fStart >= fStart and tmp_fStart <= fEnd)
								# Feature ends inside ORF (tmp_fEnd >= fStart and tmp_fEnd <= fEnd)
								# ORF contained within feature (tmp_fStart <= fStart and tmp_fEnd >= fEnd)
								if (tmp_fStart >= fStart and tmp_fEnd <= fEnd) or (tmp_fEnd >= fStart and tmp_fEnd <= fEnd) or (tmp_fStart >= fStart and tmp_fStart <= fEnd) or (tmp_fStart <= fStart and tmp_fEnd >= fEnd):
									if tmp_f.getFeatureType().lower() == 'cdna insert':
										content += "cDNA"
									elif tmp_f.getFeatureType().lower() == "5' cloning site" or tmp_f.getFeatureType().lower() == "3' cloning site":
										content += tmp_f.getFeatureName().title() + " cloning site"
									elif tmp_f.getFeatureType().lower() == 'restriction site':
										content += tmp_f.getFeatureName().title() + " restriction site"
									elif tmp_f.getFeatureType().lower() == 'origin':
										content += tmp_f.getFeatureName().title() + " ori."
									#elif tmp_f.getFeatureType().lower() == 'intron':
										#content += tmp_f.getFeatureName().title() + " intron"
									elif tmp_f.getFeatureType().lower() == 'polya':
										content += tmp_f.getFeatureName().title() + " polyA"
									elif tmp_f.getFeatureType().lower() == 'promoter':
										content += tmp_f.getFeatureName().title() + " promoter"
									#elif tmp_f.getFeatureType().lower() == 'tag type':
										#content += tmp_f.getFeatureName().title() + " tag"
									elif tmp_f.getFeatureType().lower() == 'transcription terminator':
										content += tmp_f.getFeatureName().title() + " tscn. ter."
									else:
										content += tmp_f.getFeatureName().title()
										
									content += ": " + `tmp_fStart` + "-" + `tmp_fEnd` + '\n'
									#content += "\n"
						#else:
							##f_out.write("Frame " + `f` + "\n")
							##f_out.write("Shorter than 100 nt\n\n")
							
							#content += ("Frame " + `f` + "\n")
							#content += ("Shorter than 100 aa\n\n")
							
						orf_no += 1
				#else:
					#content += "Shorter than 100 aa\n\n"
			
		content += "\n"
		
		for f in rev_frames.keys():
			
			if rev_frames[f]:
				rList = rev_frames[f]
				
				for rTemp in rList:
					frame = rTemp.getSequence()
					
					# Only output new frames produced by splicing
					if frame not in normal_frames:
						
						if f != 1:
							content += '\n'
					
						content += "******************************************************\n3'5' frame " + `f` + "\n******************************************************\n"
				
						#print "Frame " + `f`	# don't use in browser, only for command-line debugging
						#print frame	# don't use in browser, only for command-line
						
						# Jan. 26/09
						fStart = rTemp.getSeqStart()
						fEnd = rTemp.getSeqEnd()
						#print fEnd
						mw = rTemp.getMW()
						
						if len(frame) >= 100:
							content += "\nORF no. " + `orf_no` + '\n======================\n'
							
							#normal_frames.append(frame)
						
							#f_out.write("Frame " + `f` + "\n")
							#f_out.write(frame + '\n'+'\n')
							
							# Jan. 28/09
							revStart = len(seq) - fEnd + 1
							revEnd = revStart + len(frame)*3 - 1
							
							#print revStart
							#print revEnd
							
							# Jan. 26/09
							#content += "Start position = " + `fStart` + "\n"
							#content += "Stop position = " + `fEnd` + "\n"
							
							# Jan. 28/09: Because it's in reverse orientation, swap stop and start
							content += "Start position = " + `revEnd` + "\n"
							content += "Stop position = " + `revStart` + "\n"
							
							content += "Length: " + `len(frame)` + " aa\n"
							
							# add MW and feature overlap
							content += "Molecular Weight: " + str(mw) + " kDa\n\n"
							
							#content += "\n"
							
							# Removed Feb. 5/09 - this layout is split in Word and most users don't like it - confirm with Karen
							## Jan. 26/09: output sequence in chunks of 100
							#i = 0
							
							#while i < len(frame):
								#content += frame[i:i+100]
								#content += '\t'
								
								#if len(frame) - i >= 100:
									#content += `i+100` + '\n'
									
								#i += 100
							
							# Feb. 5/09
							#content += "\n"
							content += frame
							#content += "\n"

							# OVERLAPPING FEATURES
							content += "\n\nOverlapping features:\n---------------------\n"
							
							for tmp_f in features:
								#print tmp_f.getFeatureType()
								tmp_fStart = tmp_f.getFeatureStartPos()
								tmp_fEnd = tmp_f.getFeatureEndPos()
								
								# 4 cases:
								# Feature contained within ORF (tmp_fStart >= revStart and tmp_fEnd <= revEnd)
								# Feature starts inside ORF (tmp_fEnd >= revStart and tmp_fEnd <= revEnd)
								# Feature ends inside ORF (tmp_fStart >= revStart and tmp_fStart <= revEnd)
								# ORF contained within feature (tmp_fStart <= revStart and tmp_fEnd >= revEnd)
								if (tmp_fStart >= revStart and tmp_fEnd <= revEnd) or (tmp_fEnd >= revStart and tmp_fEnd <= revEnd) or (tmp_fStart >= revStart and tmp_fStart <= revEnd) or (tmp_fStart <= revStart and tmp_fEnd >= revEnd):
									if tmp_f.getFeatureType().lower() == 'cdna insert':
										content += "cDNA"
									elif tmp_f.getFeatureType().lower() == "5' cloning site" or tmp_f.getFeatureType().lower() == "3' cloning site":
										content += tmp_f.getFeatureName().title() + " cloning site"
									elif tmp_f.getFeatureType().lower() == 'restriction site':
										content += tmp_f.getFeatureName().title() + " restriction site"
									#elif tmp_f.getFeatureType().lower() == 'intron':
										#content += tmp_f.getFeatureName().title() + " intron"
									elif tmp_f.getFeatureType().lower() == 'polya':
										content += tmp_f.getFeatureName().title() + " polyA"
									elif tmp_f.getFeatureType().lower() == 'promoter':
										content += tmp_f.getFeatureName().title() + " promoter"
									elif tmp_f.getFeatureType().lower() == 'origin':
										content += tmp_f.getFeatureName().title() + " ori."
									#elif tmp_f.getFeatureType().lower() == 'tag type':
										#content += tmp_f.getFeatureName().title() + " tag"
									elif tmp_f.getFeatureType().lower() == 'transcription terminator':
										content += tmp_f.getFeatureName().title() + " tscn. ter."
									else:
										content += tmp_f.getFeatureName().title()
										
									content += ": " + `tmp_fStart` + "-" + `tmp_fEnd` + '\n'
									#content += "\n"
						#else:
							##f_out.write("Frame " + `f` + "\n")
							##f_out.write("Shorter than 100 nt\n\n")
							
							#content += "Frame " + `f` + "\n"
							#content += "Shorter than 100 aa\n\n"
							
						orf_no += 1
				#else:
					#content += "Shorter than 100 aa\n\n"
	
	# REVERSE-ORIENTED SEQUENCE
	closestPairs_rev = {}	# (acceptor start, donor end) tuples
	minDiff = len(seq)
	
	if len(closestPairs) == 0:
		# Do the opposite for reverse-oriented sequences, where donor start > acceptor end
		for aStart in splice_acceptor_pos.keys():
			for dStart in splice_donor_pos.keys():
				if dStart > aStart:
					tmpDiff = dStart - aStart
					
					if tmpDiff < minDiff:
						minDiff = tmpDiff
						dEnd = splice_donor_pos[dStart]
						tmpClosest_rev = dEnd
			
			closestPairs_rev[aStart] = tmpClosest_rev
			minDiff = len(seq)	# reset!!!!!!
		
		#print `closestPairs_rev`
		
		#acceptorStart = closestPairs_rev[donorStart]
		#donorEnd = splice_donor_pos[donorStart]
		
		for acceptorStart in closestPairs_rev.keys():
			#print acceptorEnd
			donorEnd = closestPairs_rev[acceptorStart]
			#print donorStart
			seqPre = seq[0:acceptorStart-1]
			#print "pre " + seqPre
			seqPost = seq[donorEnd:]
			fusionSeq = seqPre + seqPost
			#print fusionSeq
		
			#content += getAllFrames(rID, fusionSeq, vectorID)
			
			# debug reverse
			#seq = dnaHandler.reverse_complement(seq)
			#print seq
			#protHandler.findVectorORFs(fusionSeq, 1)
			#return
			
			# Translate forward-oriented sequence in 3 frames
			if len(fusionSeq) > 0:
				for frame in range(1,4):
					prots = protHandler.findVectorORFs(fusionSeq, frame)
				
					if len(prots) > 0:
						for protEntry in prots:
							peptideSeq = protEntry.getSequence()
							sStart = protEntry.getSeqStart()
							sEnd = protEntry.getSeqEnd()
							
							# Change end position to include the splice portion
							origSeqEnd = sEnd + (donorEnd-acceptorStart) + 1
							protEntry.setSeqEnd(origSeqEnd)
							
							if len(peptideSeq) > 0 and sStart > 0 and sEnd > 0:
							
								if fwd_frames.has_key(frame):
									tmp_fwd = fwd_frames[frame]
									tmp_fwd.append(protEntry)
									fwd_frames[frame] = tmp_fwd
								else:
									tmp_fwd = []
									tmp_fwd.append(protEntry)
									fwd_frames[frame] = tmp_fwd
			
					## increment loop counter
					#frame += 1
					
			
			# Translate REVERSE-oriented sequence in 3 frames
			if len(fusionSeq) > 0:
				#print fusionSeq
				fusionSeq = dnaHandler.reverse_complement(fusionSeq)
				#print fusionSeq
			
				for frame in range(1,4):
					tmp_rev = []
					
					#print "Translating F" + `frame`
					
					#protEntry = protHandler.translate(seq, frame, openClosed)
					prots = protHandler.findVectorORFs(fusionSeq, frame)
					
					if len(prots) > 0:
						for protEntry in prots:
							protEntry.setOrientation('reverse')
							peptideSeq = protEntry.getSequence()
							#print peptideSeq
							
							sStart = protEntry.getSeqStart()
							#print sStart
							sEnd = protEntry.getSeqEnd()
							
							
							# Change end position to include the splice portion
							# Feb. 9/09: update splice donor/acceptor positions to be in reverse orientation too
							origSeqEnd = sEnd + (donorEnd-acceptorStart) + 1
							
							protEntry.setSeqEnd(origSeqEnd)
							
							if len(peptideSeq) > 0 and sStart > 0 and sEnd > 0:
								#f_ind = frame+1	# jan. 27/09: decided not to do this, too confusing
								f_ind = frame
								#print f_ind
								
								if rev_frames.has_key(f_ind):
									tmp_rev = rev_frames[f_ind]
									tmp_rev.append(protEntry)
									rev_frames[f_ind] = tmp_rev
								else:
									tmp_rev = []
									tmp_rev.append(protEntry)
									rev_frames[f_ind] = tmp_rev

		content = "===============================================================\nORFs generated from potential splicing events:\n"
		content += "===============================================================\n\n"
		
		#print `normal_frames`
		#print `fwd_frames`
		#print `rev_frames`
		
		for f in fwd_frames.keys():
		
			#print "Frame " + `f`	# don't use in browser
			#print `fwd_frames`
			if fwd_frames[f]:
				
				fList = fwd_frames[f]
				#print `fList`
				#content += "******************************************************\n5'3' frame " + `f` + "\n******************************************************\n"
				
				for fTemp in fList:
					frame = fTemp.getSequence()
					#print frame
					
					# Only output new frames produced by splicing
					if frame not in normal_frames:
						#print "yeah???"
						if f != 1:
							content += '\n'
							
						content += "******************************************************\n5'3' frame " + `f` + "\n******************************************************\n"
				
						# Jan. 26/09
						fStart = fTemp.getSeqStart()
						fEnd = fTemp.getSeqEnd()
						#print frame			# debug - don't use in browser
						mw = fTemp.getMW()
						
						if len(frame) >= 100:
							content += "\nORF no. " + `orf_no` + '\n======================\n'
							
							#f_out.write("Frame " + `f` + "\n")
							#f_out.write(frame + '\n'+'\n')
							
							# Jan. 26/09
							content += "Start position = " + `fStart` + "\n"
							content += "Stop position = " + `fEnd` + "\n"
							content += "Length: " + `len(frame)` + " aa\n"
							
							# add MW and feature overlap
							content += "Molecular Weight: " + str(mw) + ' kDa\n\n'
							
							# Removed Feb. 5/09 - this layout is split in Word and most users don't like it - confirm with Karen
							## Jan. 26/09: output sequence in chunks of 100
							#i = 0
							
							#while i < len(frame):
								#content += frame[i:i+100]
								#content += '\t'
								
								#if len(frame) - i >= 100:
									#content += `i+100` + '\n'
									
								#i += 100
								
							# Feb. 5/09
							#content += "\n"
							content += frame
							#content += "\n"
							
							# OVERLAPPING FEATURES
							content += "\n\nOverlapping features:\n---------------------\n"
							
							for tmp_f in features:
								tmp_fStart = tmp_f.getFeatureStartPos()
								tmp_fEnd = tmp_f.getFeatureEndPos()
								
								# 4 cases:
								# Feature contained within ORF (tmp_fStart >= fStart and tmp_fEnd <= fEnd)
								# Feature starts inside ORF (tmp_fStart >= fStart and tmp_fStart <= fEnd)
								# Feature ends inside ORF (tmp_fEnd >= fStart and tmp_fEnd <= fEnd)
								# ORF contained within feature (tmp_fStart <= fStart and tmp_fEnd >= fEnd)
								if (tmp_fStart >= fStart and tmp_fEnd <= fEnd) or (tmp_fEnd >= fStart and tmp_fEnd <= fEnd) or (tmp_fStart >= fStart and tmp_fStart <= fEnd) or (tmp_fStart <= fStart and tmp_fEnd >= fEnd):
									if tmp_f.getFeatureType().lower() == 'cdna insert':
										content += "cDNA"
									elif tmp_f.getFeatureType().lower() == "5' cloning site" or tmp_f.getFeatureType().lower() == "3' cloning site":
										content += tmp_f.getFeatureName().title() + " cloning site"
									elif tmp_f.getFeatureType().lower() == 'restriction site':
										content += tmp_f.getFeatureName().title() + " restriction site"
									elif tmp_f.getFeatureType().lower() == 'origin':
										content += tmp_f.getFeatureName().title() + " ori."
									#elif tmp_f.getFeatureType().lower() == 'intron':
										#content += tmp_f.getFeatureName().title() + " intron"
									elif tmp_f.getFeatureType().lower() == 'polya':
										content += tmp_f.getFeatureName().title() + " polyA"
									elif tmp_f.getFeatureType().lower() == 'promoter':
										content += tmp_f.getFeatureName().title() + " promoter"
									#elif tmp_f.getFeatureType().lower() == 'tag type':
										#content += tmp_f.getFeatureName().title() + " tag"
									elif tmp_f.getFeatureType().lower() == 'transcription terminator':
										content += tmp_f.getFeatureName().title() + " tscn. ter."
									else:
										content += tmp_f.getFeatureName().title()
										
									content += ": " + `tmp_fStart` + "-" + `tmp_fEnd` + '\n'
									#content += "\n"
						#else:
							##f_out.write("Frame " + `f` + "\n")
							##f_out.write("Shorter than 100 nt\n\n")
							
							#content += ("Frame " + `f` + "\n")
							#content += ("Shorter than 100 aa\n\n")
							
						orf_no += 1
				#else:
					#content += "Shorter than 100 aa\n\n"
			
		content += "\n"
		
		for f in rev_frames.keys():
			
			if rev_frames[f]:
				rList = rev_frames[f]
				
				for rTemp in rList:
					frame = rTemp.getSequence()
					
					# Only output new frames produced by splicing
					if frame not in normal_frames:
						
						if f != 1:
							content += '\n'
					
						content += "******************************************************\n3'5' frame " + `f` + "\n******************************************************\n"
				
						#print "Frame " + `f`	# don't use in browser, only for command-line debugging
						#print frame	# don't use in browser, only for command-line
						
						# Jan. 26/09
						fStart = rTemp.getSeqStart()
						fEnd = rTemp.getSeqEnd()
						#print fEnd
						mw = rTemp.getMW()
						
						if len(frame) >= 100:
							content += "\nORF no. " + `orf_no` + '\n======================\n'
							
							#normal_frames.append(frame)
						
							#f_out.write("Frame " + `f` + "\n")
							#f_out.write(frame + '\n'+'\n')
							
							# Jan. 28/09
							revStart = len(seq) - fEnd + 1
							revEnd = revStart + len(frame)*3 - 1
							
							#print revStart
							#print revEnd
							
							# Jan. 26/09
							#content += "Start position = " + `fStart` + "\n"
							#content += "Stop position = " + `fEnd` + "\n"
							
							# Jan. 28/09: Because it's in reverse orientation, swap stop and start
							content += "Start position = " + `revEnd` + "\n"
							content += "Stop position = " + `revStart` + "\n"
							
							content += "Length: " + `len(frame)` + " aa\n"
							
							# add MW and feature overlap
							content += "Molecular Weight: " + str(mw) + " kDa\n\n"
							
							#content += "\n"
							
							# Removed Feb. 5/09 - this layout is split in Word and most users don't like it - confirm with Karen
							## Jan. 26/09: output sequence in chunks of 100
							#i = 0
							
							#while i < len(frame):
								#content += frame[i:i+100]
								#content += '\t'
								
								#if len(frame) - i >= 100:
									#content += `i+100` + '\n'
									
								#i += 100
							
							# Feb. 5/09
							#content += "\n"
							content += frame
							#content += "\n"

							# OVERLAPPING FEATURES
							content += "\n\nOverlapping features:\n---------------------\n"
							
							for tmp_f in features:
								tmp_fStart = tmp_f.getFeatureStartPos()
								tmp_fEnd = tmp_f.getFeatureEndPos()
								
								# 4 cases:
								# Feature contained within ORF (tmp_fStart >= revStart and tmp_fEnd <= revEnd)
								# Feature starts inside ORF (tmp_fEnd >= revStart and tmp_fEnd <= revEnd)
								# Feature ends inside ORF (tmp_fStart >= revStart and tmp_fStart <= revEnd)
								# ORF contained within feature (tmp_fStart <= revStart and tmp_fEnd >= revEnd)
								if (tmp_fStart >= revStart and tmp_fEnd <= revEnd) or (tmp_fEnd >= revStart and tmp_fEnd <= revEnd) or (tmp_fStart >= revStart and tmp_fStart <= revEnd) or (tmp_fStart <= revStart and tmp_fEnd >= revEnd):
									if tmp_f.getFeatureType().lower() == 'cdna insert':
										content += "cDNA"
									elif tmp_f.getFeatureType().lower() == "5' cloning site" or tmp_f.getFeatureType().lower() == "3' cloning site":
										content += tmp_f.getFeatureName().title() + " cloning site"
									elif tmp_f.getFeatureType().lower() == 'restriction site':
										content += tmp_f.getFeatureName().title() + " restriction site"
									#elif tmp_f.getFeatureType().lower() == 'intron':
										#content += tmp_f.getFeatureName().title() + " intron"
									elif tmp_f.getFeatureType().lower() == 'polya':
										content += tmp_f.getFeatureName().title() + " polyA"
									elif tmp_f.getFeatureType().lower() == 'promoter':
										content += tmp_f.getFeatureName().title() + " promoter"
									elif tmp_f.getFeatureType().lower() == 'origin':
										content += tmp_f.getFeatureName().title() + " ori."
									#elif tmp_f.getFeatureType().lower() == 'tag type':
										#content += tmp_f.getFeatureName().title() + " tag"
									elif tmp_f.getFeatureType().lower() == 'transcription terminator':
										content += tmp_f.getFeatureName().title() + " tscn. ter."
									else:
										content += tmp_f.getFeatureName().title()
										
									content += ": " + `tmp_fStart` + "-" + `tmp_fEnd` + '\n'
									#content += "\n"
						#else:
							##f_out.write("Frame " + `f` + "\n")
							##f_out.write("Shorter than 100 nt\n\n")
							
							#content += "Frame " + `f` + "\n"
							#content += "Shorter than 100 aa\n\n"
							
						orf_no += 1
				#else:
					#content += "Shorter than 100 aa\n\n"
					
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
		rID = 89500	# V3302 - Rick, SDM
		#rID = 115379	# V4539
		
		# update July 3/09
		seqPropID = propHandler.findReagentPropertyInCategoryID(prop_Name_ID_Map["sequence"], prop_Category_Name_ID_Map["DNA Sequence"])
		seqID = rHandler.findIndexPropertyValue(rID, seqPropID)
		
		#seqID = rHandler.findIndexPropertyValue(rID, prop_Name_ID_Map["sequence"])		# removed July 3/09
		seq = dnaHandler.findSequenceByID(seqID)
	
	vectorID = rHandler.convertDatabaseToReagentID(rID)
	#print vectorID
	
	fname = vectorID + "_frames.doc"
	
	print "Content-Disposition: attachment; name=" + fname
	
	content1 = getAllFrames(rID, seq, vectorID)
	content2 = getSpliceORFs(rID, seq, vectorID)
	
	print '\n' + content1 + '\n' + content2
	#print content1
	#print '\n' + content2
	
main()