import MySQLdb

class DatabaseConn:
	
	__hostname__ = "http://your.OpenFreezer.URL/"				# CHANGE THIS to your hostname; REMEMBER THE http://
	__root_dir__ = "/your/OpenFreezer/installation/directory/"		# CHANGE THIS
	
	__mailto_clone_request__ = "clonerequest@YOU.com"			# CHANGE THIS
	__mailto_programmer__ = "olhovsky@lunenfeld.ca"				
	__mailto_biologist__ = "colwill@lunenfeld.ca"			
	
	__mail_server__ = "your.smtp.mail.server"				# CHANGE THIS
	__mail_admin__ = "YOUR LOCAL OPENFREEZER ADMIN EMAIL"			# CHANGE THIS

	def getHostname(self):
		return self.__hostname__

	def getRootDir(self):
		return self.__root_dir__
	
	def databaseConnect(self):
		
		# CHANGE THIS
		db = MySQLdb.connect(host="my.mysql.server.name", user="openfreezer_www", passwd="openfreezer_www_passwd", db="my_openfreezer_db")
		return db


	def getMailCloneRequest(self):
		return self.__mailto_clone_request__
	
	def getProgrammerEmail(self):
		return self.__mailto_programmer__
	
	
	def getBiologistEmail(self):
		return self.__mailto_biologist__

	def getMailServer(self):
		return self.__mail_server__

	def getAdminEmail(self):
		return self.__mail_admin__
