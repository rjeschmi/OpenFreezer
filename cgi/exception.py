##################################################################################################################
# This module contains various Exception classes

# Written by: Marina Olhovsky

# Last modified: February 9, 2009
##################################################################################################################


#######################################################################
# Exceptions that occur during Vector sequence constitution
#######################################################################

class InsertSitesException(Exception):
	"Raised when Insert cloning sites are outside the standard REBASE enzyme list - most likely Gateway or LoxP"
	"This exception is raised during automated Vector sequence construction, when parents' cloning sites do not match the Vector type (e.g. using an Insert with attB sites to generate a non-gateway vector -- exception thrown by sequence generation function)"
	
	__err_code__ = 1

	def __init__(self, value="Unknown sites on Insert"):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value
	

class InsertSitesNotFoundOnParentSequenceException(Exception):
	"Raised when the sequence of one or both of the Insert's cloning site is not found in the parent vector sequence"
	
	__err_code__ = 2

	def __init__(self, value="Insert sites not found in parent vector sequence"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
	

class MultipleSiteOccurrenceException(Exception):
	"Raised when the sequence of Insert sites is found more than once in the parent vector sequence"

	__err_code__ = 3

	def __init__(self, value="Insert sites occur more than once in parent vector sequence"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
	

class HybridizationException(Exception):
	"Raised when the restriction sites provided cannot be hybridized"

	__err_code__ = 4

	def __init__(self, value="Restriction sites cannot be hybridized"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value


class FivePrimeAfterThreePrimeException(Exception):
	"Raised when the 5' restriction site occurs after the 3' site on parent sequence"

	__err_code__ = 5

	def __init__(self, value="5' site occurs after 3' on parent sequence"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value


class ReagentDoesNotExistException(Exception):
	"Raised when a reagent ID does not exist in the database"
	
	__err_code__ = 6
	
	def __init__(self, value="Reagent does not exist"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
	

# Feb. 5, 2010
class ParentVectorDoesNotExistException(Exception):
	"Raised at stable Cell Line creation, when the Parent Vector does not exist in the database"
	
	__err_code__ = 7
	
	def __init__(self, value="Parent Vector does not exist"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
	

# Feb. 5, 2010
class ParentCellLineDoesNotExistException(Exception):
	"Raised at stable Cell Line creation, when the Parent Cell Line does not exist in the database"
	
	__err_code__ = 8
	
	def __init__(self, value="Parent Cell Line does not exist"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
	
class CloningSitesNotFoundInInsertException(Exception):
	"Raised when the Insert does not contain a cloning site"
	
	__err_code__ = 13

	def __init__(self, value="Insert does not contains a portion of a hybrid site"):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value


class InsertSitePositionException(Exception):
	"Raised when a restriction site is not found on Insert sequence at the indicated positions"
	
	__err_code__ = 14

	def __init__(self, value="Restriction site is not found on Insert sequence at the indicated positions"):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value


class InvalidDonorVectorSitesNotFoundException(Exception):
	"Raised when a donor vector (IPV in Recombination Vector creation)'s sequence does not contain LoxP sites"
	
	__err_code__ = 15

	def __init__(self, value="Donor vector sequence does not contain LoxP sites"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
	

class InvalidDonorVectorSingleSiteException(Exception):
	"Raised when LoxP site is encountered only once in the sequence of a donor vector (IPV in Recombination Vector creation)"
	
	__err_code__ = 16

	def __init__(self, value="Donor vector sequence contains one occurrence of LoxP site"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
	
class InvalidDonorVectorMultipleSitesException(Exception):
	"Raised when a donor vector (IPV in Recombination Vector creation) sequence contains MORE than two occurrences of LoxP sites"
	
	__err_code__ = 17

	def __init__(self, value="Donor vector sequence contains more than two occurrences of LoxP sites"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
	
	
# Replaced June 3/08
class RecombinationVectorSequenceMissingLoxPException(Exception):
	"Raised during Recombination Vector creation, when, as a result of user modification, one or both LoxP sites in a recombination sequence gets destroyed"
	
	__err_code__ = 18
	
	def __init__(self, value="Missing LoxP sites in Recombination Vector Sequence"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value

# June 2/08
class ReagentTypeException(Exception):
	"Raised when the type of reagent selected as parent in creation is invalid (e.g. a recombination vector selected as donor or a Gateway Vector selected as parent in non-recombination)"

	__err_code__ = 19

	def __init__(self, value="Invalid type of reagent selected as parent"):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value


# Jan. 14/09
class DegenerateHybridException(Exception):
	"Raised when a hybrid sequence is attempted to be made using two degenerate restriction sites (not very common)"
	
	__err_code__ = 22

	def __init__(self, value="Attempting to make a hybrid sequence using two degenerate restriction sites"):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value

# Feb. 12/09
class EmptyCloningSitesException(Exception):
	__err_code__ = 23

	def __init__(self, value="Empty cloning sites"):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value


# Dec. 14/09
class EmptyParentVectorSequenceException(Exception):
	__err_code__ = 24

	def __init__(self, value="The sequence of the parent vector provided is empty."):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value

class EmptyParentInsertSequenceException(Exception):
	__err_code__ = 25

	def __init__(self, value="The sequence of the parent insert provided is empty."):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value
	
class EmptyInsertParentVectorSequenceException(Exception):
	__err_code__ = 26

	def __init__(self, value="The sequence of the insert parent vector provided is empty."):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value
	
	
class IncompatibleFivePrimeOverhangsException(Exception):
	"Raised when 5' overhangs on vector and insert are incompatible"
	
	__err_code__ = 27

	def __init__(self, value="Invalid type of reagent selected as parent"):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value


class IncompatibleThreePrimeOverhangsException(Exception):
	"Raised when 3' overhangs on vector and insert are incompatible"
	
	__err_code__ = 28

	def __init__(self, value="Invalid type of reagent selected as parent"):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value


class InsertFivePrimeAfterThreePrimeException(Exception):
	"Raised when 5' is found after 3' on insert sequence - likely when user inputs custom Insert sites in reverse order"
	
	__err_code__ = 29

	def __init__(self, value="5' after 3' on Insert sequence"):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value


# June 3, 2010
class GatewayParentDestinationException(Exception):
	"Raised when the Gateway Parent Destination vector does not contain a ccdB gene"
	
	__err_code__ = 30

	def __init__(self, value="ccdB not found on GW Parent Destination vector"):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value


# June 3, 2010
class SwapGatewayParentsException(Exception):
	"Raised when the Gateway Parent Destination vector does not contain a ccdB gene BUT the Gateway Entry clone does - as if the user has entered them backwards"
	
	__err_code__ = 31

	def __init__(self, value="ccdB not found on GW Parent Destination vector and found in Entry clone"):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value
	

############################################################
# User-related and Lab-related Exceptions
############################################################

class DeletedUserException(Exception):
	"Raised at User creation, when the username provided belongs to a DEP user in Users_tbl"
	
	__err_code__ = 7
	
	def __init__(self, value="Username belongs to inactive user"):
		self.value = value

	def err_code(self):
		return self.__err_code__
		
	def toString(self):
		return self.value
		
		
class DuplicateUsernameException(Exception):
	"Raised at User creation, when the username provided belongs to an ACTIVE user in Users_tbl, resulting in UNIQUE username constraint violation (produces MySQL duplicate entry error 1062)"
	
	__err_code__ = 8
	
	def __init__(self, value="Duplicate username"):
		self.value = value

	def err_code(self):
		return self.__err_code__
		
	def toString(self):
		return self.value


class DuplicateEntryException(Exception):
	"Raised everywhere a query returns multiple rows when in fact only one result is expected"
	
	__err_code__ = 9
	
	def __init__(self, value="One result expected but query returned more"):
		self.value = value
		
	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value
	

class DuplicateLabCodeException(Exception):
	"Raised when a user is trying to supply a lab code that is already taken by another lab"
	
	__err_code__ = 14
	
	def __init__(self, value="This lab ID already exists."):
		self.value = value

	def err_code(self):
		return self.__err_code__
	
	def toString(self):
		return self.value
	
############################################################
# Project-related exceptions
############################################################
class PVProjectAccessException(Exception):
	"Raised when a user is trying to create a reagent using a Parent Vector from a project s/he doesn't have Read access to"
	
	__err_code__ = 10
	
	def __init__(self, value="You do not have Write access to this project"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
	
	
class InsertProjectAccessException(Exception):
	"Raised when a user is trying to create a reagent using an Insert from a project s/he doesn't have Read access to"
	
	__err_code__ = 11
	
	def __init__(self, value="You do not have Write access to this project"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
	
	
class IPVProjectAccessException(Exception):
	"Raised when a user is trying to create a reagent using an Insert Parent Vector from a project s/he doesn't have Read access to"
	
	__err_code__ = 12
	
	def __init__(self, value="You do not have Write access to this project"):
		self.value = value
		
	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value


class SenseProjectAccessException(Exception):
	"Raised when a user is trying to create an Insert using as parent a Sense Oligo from a project s/he doesn't have Read access to"
	
	__err_code__ = 20
	
	def __init__(self, value="You do not have Write access to this project"):
		self.value = value
		
	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
	
	
class AntisenseProjectAccessException(Exception):
	"Raised when a user is trying to create an Insert using as parent an Antisense Oligo from a project s/he doesn't have Read access to"
	
	__err_code__ = 21
	
	def __init__(self, value="You do not have Write access to this project"):
		self.value = value
		
	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
	
class CLProjectAccessException(Exception):
	"Raised when a user is trying to create a reagent using a Cell Line from a project s/he doesn't have Read access to"
	
	__err_code__ = 13
	
	def __init__(self, value="You do not have Write access to this project"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value

# July 14/09
class InvalidPropertyCategoryException(Exception):
	"Raised when ................."
	
	__err_code__ = 13
	
	def __init__(self, value="You do not have Write access to this project"):	# is this the right error msg????
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value


class UnknownReagentTypeException(Exception):
	"Raised when the reagent type provided is not found in the database"
	
	__err_code = 24
	
	def __init__(self, value="Reagent type not found in the database"):
		self.value = value

	def err_code(self):
		return self.__err_code__

	def toString(self):
		return self.value
