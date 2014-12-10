#!/usr/local/bin/python

import Bio
from Bio.Seq import Seq
from Bio.Restriction import *

rb = RestrictionBatch(AllEnzymes)

print "Content-type:text/html"
print

result = " "

for a in AllEnzymes.elements():
	enz = rb.get(a)
	result += `enz` + " "
	
print result