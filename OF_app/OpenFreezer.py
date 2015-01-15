from flask import Flask
from flask import jsonify
from flask import request

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

if __name__ == '__main__':
    app.debug = True
    app.run()

