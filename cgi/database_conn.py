import MySQLdb

class DatabaseConn:
    
    __hostname__ = "http://192.168.59.103:81/"
    __root_dir__ = "/var/www"
    
    __mailto_clone_request__ = "clonerequest@YOU.com"           # CHANGE THIS
    __mailto_programmer__ = "olhovsky@lunenfeld.ca"             
    __mailto_biologist__ = "colwill@lunenfeld.ca"           
    
    __mail_server__ = "your.smtp.mail.server"               # CHANGE THIS
    __mail_admin__ = "YOUR LOCAL OPENFREEZER ADMIN EMAIL"           # CHANGE THIS

    def getHostname(self):
        return self.__hostname__

    def getRootDir(self):
        return self.__root_dir__
    
    def databaseConnect(self):
        
        # CHANGE THIS
        db = MySQLdb.connect(host="mariadb", user="openfreezer_www", passwd="UrojVifreg", db="my_openfreezer_db")
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
