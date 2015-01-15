from flask import Flask
from flask import jsonify
from flask import request

from flask.ext.sqlalchemy import SQLAlchemy


app = Flask(__name__, instance_relative_config=True)
app.config.from_object('config')
app.config.from_pyfile('config.py')

db = SQLAlchemy(app)


app = Flask(__name__)

@app.route('/')
def hello_world():
    return 'Hello World!'

@app.route('/autocomplete/OFid',methods=['GET'])
def autocomplete_OFid():
    NAMES=["abc","abcd","abcde","abcdef"]
    search = request.args.get('term')
    app.logger.debug(search)
    return jsonify(items=NAMES)

