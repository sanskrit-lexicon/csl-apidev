# coding=utf-8
from __future__ import print_function
import os
import sys
import sqlite3
import json
import re
import xml.etree.ElementTree as ET
from flask import Flask, jsonify
from flask_restplus import Api, Resource, reqparse
from flask_cors import CORS
try:
	from HTMLParser import HTMLParser
except ImportError:
	from html.parser import HTMLParser
app = Flask(__name__)
app.config['JSON_AS_ASCII'] = False
CORS(app)
apiversion = 'v0.0.1'
api = Api(app, version=apiversion, title=u'Cologne Sanskrit-lexicon API', description='Provides APIs to Cologne Sanskrit lexica.')


def find_sqlite(dict, typ):
	if typ == 'cologne':
		intermediate = os.path.join(dict.upper() + 'Scan', '2020', 'web', 'sqlite', dict + '.sqlite')
	else:
		intermediate = dict
	sqlitepath = os.path.join('..', intermediate, 'web', 'sqlite', dict + '.sqlite')
	return sqlitepath

def parse_text_data(data):
	root = ET.fromstring(data)
	key2 = root.findall("./h/key2")[0].text
	pc = root.findall("./tail/pc")[0].text
	m = re.split('<body>(.*)</body>', data)
	text = m[1]
	return (key2, pc, text)


@api.route('/' + apiversion + '/dicts/<string:dict>/lnum/<string:lnum>')
@api.doc(params={'dict': 'Dictionary code.', 'lnum': 'L number.'})
class LnumToData(Resource):
	"""Return the JSON data regarding the given Lnum."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dict, lnum):
		sqlitepath = find_sqlite(dict, typ='local')
		con = sqlite3.connect(sqlitepath)
		ans = con.execute('SELECT * FROM ' + dict + ' WHERE lnum = ' + str(lnum))
		[headword, lnum, data] = ans.fetchall()[0]
		(key2, pc, text) = parse_text_data(data)
		result = {'headword': headword, 'lnum': lnum, 'key2': key2, 'pc': pc, 'text': text}
		return jsonify(result)
 

if __name__ == "__main__":
	app.run(debug=True)
