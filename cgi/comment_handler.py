import string
import MySQLdb

from general_handler import *
from mapper import *

################################################################################
# Module comment_handler
# An interface to GeneralComments_tbl in OpenFreezer
#
# This module performs SQL queries to deal specifically with selecting/inserting/changing
# comments for reagents and locations
#
# Written October 30, 2006 by Marina Olhovsky
#
# Last modified: January 20, 2010
################################################################################

################################################################################
# CommentHandler class
# Subclass of GeneralHandler class
# Written October 30, 2006, by Marina Olhovsky
################################################################################
class CommentHandler(GeneralHandler):
	"This class handles insertion/selection/update of comments in OpenFreezer"
	
	__propHandler = None
	__propMapper = None
	__prop_Name_ID_Map = None
	
	def __init__(self, db, cursor):
		self.db = db
		self.cursor = cursor
		self.__propHandler = ReagentPropertyHandler(db, cursor)
		self.__propMapper = ReagentPropertyMapper(db, cursor)
		self.__prop_Name_ID_Map = self.__propMapper.mapPropNameID()
		self.__prop_Category_Name_ID_Map = self.__propMapper.mapPropCategoryNameID()
	
	# Return true if the property is 'experimental comments', 'verification comments' or 'description'
	def isComment(self, propID):
		pHandler = self.__propHandler
		
		# Update Jan. 20, 2010: Can no longer rely on property name alone, need its category!!!!!!
		#return propID == pHandler.findPropID('comments') or propID == pHandler.findPropID('verification comments') or propID == pHandler.findPropID('description')
		
		return propID == pHandler.findReagentPropertyInCategoryID(self.__prop_Name_ID_Map['comments'], self.__prop_Category_Name_ID_Map["General Properties"]) or propID == pHandler.findReagentPropertyInCategoryID(self.__prop_Name_ID_Map['verification comments'], self.__prop_Category_Name_ID_Map["General Properties"]) or propID == pHandler.findReagentPropertyInCategoryID(self.__prop_Name_ID_Map['description'], self.__prop_Category_Name_ID_Map["General Properties"])


	# Check if a comment exists in OpenFreezer
	# If yes, return its commentID stored in GeneralComments_tbl
	def existsComment(self, commLinkID, comment):
		db = self.db
		cursor = self.cursor
		
		cursor.execute("SELECT `commentID` FROM `GeneralComments_tbl` WHERE `commentLinkID`=" + `commLinkID` + " AND `comment`=" + `comment` + " AND `status`='ACTIVE'")
		
		result = cursor.fetchone()	# may be multiple, e.g. 'MGC Clone', but just get one for simplicity.
						# Technically there should only be one table entry per comment; multiple IDs for the same comment are the result of poor design and lack of verification
		
		if result:
			return int(result[0])
		
		return -1
	

	# Find the commentLinkID that corresponds to commLink
	# E.g. commLink = 'Packet', commentLinkID = '3'
	# commLink = 'Reagent', commentLinkID = '1'
	# etc.
	def findCommentLinkID(self, commLink):
		db = self.db
		cursor = self.cursor
		
		commLinkID = -1

		cursor.execute("SELECT commentLinkID FROM CommentLink_tbl WHERE link=" + `commLink`)
		result = cursor.fetchone()

		if result:
			commLinkID = int(result[0])

		return commLinkID		

	# Find the ID of a comment
	def findCommentID(self, comment, commLinkID):
		
		db = self.db
		cursor = self.cursor
		
		commentID = -1

		cursor.execute("SELECT commentID FROM GeneralComments_tbl WHERE commentLinkID=" + `commLinkID` + " AND comment=" + `comment` + " AND status='ACTIVE'")
		result = cursor.fetchone()

		if result:
			commentID = int(result[0])

		return commentID


	# Pull out the actual comment text associated with commentID (reciprocal of commentExists)
	# Parameters: commentID - int
	# Returns: text
	def findCommentByID(self, commentID):
		db = self.db
		cursor = self.cursor

		cursor.execute("SELECT `comment` FROM `GeneralComments_tbl` WHERE `commentID`=" + `commentID` + " AND `status`='ACTIVE'")
		result = cursor.fetchone()	# unique
			
		if result:
			return result[0]
		
		return None
	

	# Insert a new comment into GeneralComments_tbl and return its commentID
	# Comment may be empty
	# Parameters: comment - a TEXT field
	# Return: commentID = int
	def insertComment(self, commLinkID, comment):
		db = self.db
		cursor = self.cursor
		
		cursor.execute("INSERT INTO `GeneralComments_tbl`(`commentLinkID`, `comment`) VALUES(" + `commLinkID` + ", " + `comment` + ")")
		return int(db.insert_id())
		
		
	# Set status='DEP' of GeneralComments_tbl entry, identified by commID
	def deleteComment(self, commID):
		db = self.db
		cursor = self.cursor
		
		cursor.execute("UPDATE GeneralComments_tbl SET status='DEP' WHERE commentID=" + `commID` + " AND status='ACTIVE'")
