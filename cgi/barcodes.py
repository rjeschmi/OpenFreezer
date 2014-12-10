#!/usr/local/bin python

from location_database_handler import LocationHandler
from database_conn import DatabaseConn

import MySQLdb

dbConn = DatabaseConn()
db = dbConn.databaseConnect()
cursor = db.cursor()

lHandler = LocationHandler(db, cursor)

cursor.execute("SELECT * FROM Container_tbl WHERE status='ACTIVE'")
containers = cursor.fetchall()

for cont in containers:
	contID = int(cont[0])
	contTypeID = int(cont[1])
	contSizeID = int(cont[2])
	cLab = int(cont[6])

	bcNum = lHandler.findNextContainerBarcodeNumber(contTypeID, contSizeID, cLab)
	barcode = lHandler.generateBarcode(contTypeID, contSizeID, bcNum, cLab)
	
	cursor.execute("UPDATE Container_tbl SET barcode=" + `barcode` + " WHERE containerID=" + `contID`)

