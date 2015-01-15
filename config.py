import os
import ConfigParser

_basedir = os.path.abspath(os.path.dirname(__file__))

config_ini = ConfigParser.ConfigParser()
config_ini.read(os.path.join(_basedir, 'config', 'openfreezer.ini'))
dbopts = dict(config_ini.items('database'))
SQLALCHEMY_DATABASE_URI = "mysql://%(mysql_user)s:%(mysql_pass)s@%(mysql_host)s/%(mysql_db)s" % dbopts

Debug = True
