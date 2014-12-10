import MySQLdb
import getpass

def add_users():
    
    print "Please enter the following information to connect to the database: "
    
    hostname = raw_input("Hostname: (press Enter for 'localhost')")
    db_name = raw_input("Database name: ")
    username = raw_input("Username: ")
    password = getpass.getpass("Password: ")
    
    if len(hostname) == 0:
        hostname = "localhost"
        
    conn = MySQLdb.connect(host=hostname, user=username, passwd=password, db=db_name)	
    cursor = conn.cursor()

    secured_pages = []

    cursor.execute("SELECT `pageID` FROM `SecuredPages_tbl`")
    page_set = cursor.fetchall()
    
    for p in page_set:
        secured_pages.append(int(p[0]))

    go_on = 1
    
    while go_on:
        
        new_username = raw_input("Enter a username for the user you wish to add: ")
        new_passwd = getpass.getpass("Enter the password for this user: ")
        new_email = raw_input("Enter the email address of this user: ")
        new_descr = raw_input("Enter a description for this user: ")
        
        cursor.execute("INSERT INTO `Users_tbl`(`username`, `password`, `email`, `description`) VALUES(" + `new_username` + ", MD5(" + `new_passwd` + "), " + `new_email` + ", " + `new_descr` + ")")
        new_user_id = int(conn.insert_id())
        
        for page in secured_pages:
            cursor.execute("INSERT INTO `UserPermission_tbl`(`pageID`, `userID`) VALUES(" + `page` + ", " + `new_user_id` + ")")
        
        print "User " + new_username + " successfully added to the database."
        go_on = int(raw_input("Would you like to add more users? (enter 1 or 0): "))
        
    print "Thank you - goodbye!"
    
add_users()