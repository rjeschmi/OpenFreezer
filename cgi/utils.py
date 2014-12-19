#!/usr/local/bin python
import types
import re
import string


#############################################################################################
# Module utils
# Contains miscellaneous utility functions utilized by various modules throughout the code
#
# Written March 5, 2007 by Marina Olhovsky
# Last modified: February 12, 2008
#############################################################################################

# Determines whether 'arg' is a scalar value or a list 
def isList(arg):
    return type(arg)==types.ListType


# Merge two **dictionaries** into one, removing duplicates
def join(dict1, dict2):
	
	keys1 = dict1.keys()
	keys2 = dict2.keys()
	keys = merge(keys1, keys2)
	
	result = {}
	
	for k in keys:
		
		if dict1.has_key(k):
			val = dict1[k]
			
		elif dict2.has_key(k):
			val = dict2[k]
			
		result[k] = val
		
	return result


# Merge two lists, removing duplicates
def merge(list1, list2):
	return unique(list1 + list2)

#####################################################################################################
# Taken from ASPN Python Cookbook
# Returns a list of dictionary elements without duplicates
#####################################################################################################
def unique(s):
    """Return a list of the elements in s, but without duplicates.

    For example, unique([1,2,3,1,2,3]) is some permutation of [1,2,3],
    unique("abcabc") some permutation of ["a", "b", "c"], and
    unique(([1, 2], [2, 3], [1, 2])) some permutation of
    [[2, 3], [1, 2]].

    For best speed, all sequence elements should be hashable.  Then
    unique() will usually work in linear time.

    If not possible, the sequence elements should enjoy a total
    ordering, and if list(s).sort() doesn't raise TypeError it's
    assumed that they do enjoy a total ordering.  Then unique() will
    usually work in O(N*log2(N)) time.

    If that's not possible either, the sequence elements must support
    equality-testing.  Then unique() will usually work in quadratic
    time.
    """

    n = len(s)
    if n == 0:
        return []

    # Try using a dict first, as that's the fastest and will usually
    # work.  If it doesn't work, it will usually fail quickly, so it
    # usually doesn't cost much to *try* it.  It requires that all the
    # sequence elements be hashable, and support equality comparison.
    u = {}
    try:
        for x in s:
            u[x] = 1
    except TypeError:
        del u  # move on to the next method
    else:
        return u.keys()

    # We can't hash all the elements.  Second fastest is to sort,
    # which brings the equal elements together; then duplicates are
    # easy to weed out in a single pass.
    # NOTE:  Python's list.sort() was designed to be efficient in the
    # presence of many duplicate elements.  This isn't true of all
    # sort functions in all languages or libraries, so this approach
    # is more effective in Python than it may be elsewhere.
    try:
        t = list(s)
        t.sort()
    except TypeError:
        del t  # move on to the next method
    else:
        assert n > 0
        last = t[0]
        lasti = i = 1
        while i < n:
            if t[i] != last:
                t[lasti] = last = t[i]
                lasti += 1
            i += 1
        return t[:lasti]

    # Brute force is all that's left.
    u = []
    for x in s:
        if x not in u:
            u.append(x)
    return u


#####################################################################################################
# Taken from ASPN Python Cookbook
# Returns True if 'aStr' is an integer, False otherwise
#####################################################################################################    
def IsInt(aStr):
   try:
      num = int(aStr)
      return 1
   except ValueError:
      return 0


#####################################################################################################
# Return a list of elements that are contained in list1 but not in list2
# Elements in both lists must be of the same datatype
#####################################################################################################
def diff(list1, list2):
	result = []
		
	for el in list1:
		if el not in list2:
			result.append(el)
			
	return result	
	

#####################################################################################################
# Split a string that contains a hyphen or slash into two parts and place these parts in an array
# If 'string' doesn't contain either separator, return whole string
#####################################################################################################
def make_array(string):

    if string.find("-") > 0:
        return re.split("-", string)

    elif string.find("/") > 0:
        return re.split("/", string)

    else:
        return string


#####################################################################################################
# Find the number of occurences of a pattern in a string
#####################################################################################################
def numOccurs(seq, pat):

    num_occurs = 0
    currIndex = seq.find(pat)

    while currIndex >= 0:
        num_occurs += 1
        currIndex = seq.find(pat, currIndex+1)

    return num_occurs


#####################################################################################################
# Redirect to the given url
#####################################################################################################
def redirect(url):
	print 'Location: ' + url
	print


#####################################################################################################
# Swap dictionary keys and values (corresponds to PHP's array_flip)
#####################################################################################################
def swap(dict):
	newDict = {}
	
	for k in dict.keys():
		val = dict[k]
		newDict[val] = k
		
	return newDict
	

# Added Oct. 28/08 - Find ALL occurrences of pat in seq
# Return: list of positions
def findall(seq, pat, fList):
	#print "Searching for " + pat
	
	if numOccurs(seq, pat) > 0:
		prev = seq.find(pat)
		
		while prev >= 0:
			
			#print prev
			fList.append(prev)
			#findall(seq[prev+len(pat):], pat, fList)
			prev = seq.find(pat, prev+len(pat))
		
	else:
		fList.append(seq.find(pat))
		
	return fList
	
	
#####################################################################################################
# Remove whitespace from sequence
#####################################################################################################
def squeeze(sequence):
	
	tmp_seq = ""
	new_seq = ""
	
	sequence = sequence.replace('\r', '\n')
	sequence = sequence.replace('\t', ' ')
	
	# First, filter newlines
	toks = sequence.split('\n')
	
	for x in toks:
		tmp_seq += x.strip()
	
	tokens = tmp_seq.split(" ")
	
	for i in tokens:
		new_seq += i
	
	return new_seq
	

#####################################################################################################
# July 15/09: Taken from http://stackoverflow.com/questions/783897/truncating-floats-in-python
# Truncates/pads a float f to n decimal places without rounding
#####################################################################################################
def trunc(f, n):
	slen = len('%.*f' % (n, f))
	
	return str(f)[:slen]


def toArray(val):
	tmp_ar = []
	tmp_ar.append(val)
	
	return tmp_ar
