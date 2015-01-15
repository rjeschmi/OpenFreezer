from flask import Flask
from flask import jsonify
from flask import request

from flask.ext.sqlalchemy import SQLAlchemy


app = Flask(__name__, instance_relative_config=True)
app.config.from_object('config')
app.config.from_pyfile('config.py')

db = SQLAlchemy(app)

@app.route('/')
def hello_world():
    return 'Hello World!'

@app.route('/autocomplete/OFid',methods=['GET'])
def autocomplete_OFid():
    search = request.args.get('term')
    engine=db.engine
    conn=engine.connect()
    result=conn.execute("select * from Reagents_tbl a join  ReagentPropList_tbl b using (reagentID) join ReagentType_tbl c using (reagentTypeID)  where propertyID=2190 ")
    reagents = []
    for row in result:
        reagents.append ( { 'id': row['reagent_prefix'] + str(row['reagentID']), 'value': row['propertyValue'], 'prefix': row['reagent_prefix']})

    return jsonify(items=reagents)
