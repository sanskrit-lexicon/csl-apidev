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


dicts = ['acc', 'ae', 'ap90', 'ben', 'bhs', 'bop', 'bor', 'bur', 'cae', 'ccs', 'gra', 'gst', 'ieg', 'inm', 'krm', 'mci', 'md', 'mw', 'mw72', 'mwe', 'pe', 'pgn', 'pui', 'pw', 'pwg', 'sch', 'shs', 'skd', 'snp', 'stc', 'vcp', 'vei', 'wil', 'yat']
def find_sqlite(dict):
	path = os.path.abspath(__file__)
	if path.startswith('/nfs/'):
		intermediate = os.path.join(dict.upper() + 'Scan', '2020', 'web', 'sqlite', dict + '.sqlite')
	else:
		intermediate = dict
	sqlitepath = os.path.join('..', intermediate, 'web', 'sqlite', dict + '.sqlite')
	return sqlitepath

def parse_text_data(data):
	root = ET.fromstring(data)
	key2 = root.findall("./h/key2")[0].text
	pc = root.findall("./tail/pc")[0].text
	lnum = root.findall("./tail/L")[0].text
	m = re.split('<body>(.*)</body>', data)
	text = m[1]
	return (key2, pc, text, lnum)


def prep_dict(data, headword):
	(key2, pc, text, lnum) = parse_text_data(data)
	result = {'headword': headword, 'lnum': lnum, 'key2': key2, 'pc': pc, 'text': text}
	return result


@api.route('/' + apiversion + '/dicts/<string:dict>/lnum/<string:lnum>')
@api.doc(params={'dict': 'Dictionary code.', 'lnum': 'L number.'})
class LnumToData(Resource):
	"""Return the JSON data regarding the given Lnum."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dict, lnum):
		sqlitepath = find_sqlite(dict)
		con = sqlite3.connect(sqlitepath)
		ans = con.execute('SELECT * FROM ' + dict + ' WHERE lnum = ' + str(lnum))
		[headword, lnum, data] = ans.fetchall()[0]
		result = prep_dict(data, headword)
		return jsonify(result)
 

@api.route('/' + apiversion + '/dicts/<string:dict>/regex/<string:reg>')
@api.doc(params={'dict': 'Dictionary code.', 'reg': 'Find the headwords matching the given regex.'})
class regexToHw(Resource):
	"""Return the headwords matching the given regex."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dict, reg):
		sqlitepath = find_sqlite(dict)
		con = sqlite3.connect(sqlitepath)
		ans = con.execute("SELECT * FROM " + dict )
		result = []
		for [headword, lnum, data] in ans.fetchall():
			if re.search(reg, headword):
				result.append(prep_dict(data, headword))
		return jsonify(result)


@api.route('/' + apiversion + '/dicts/<string:dict>/hw/<string:hw>')
@api.doc(params={'dict': 'Dictionary code.', 'hw': 'Headword to search.'})
class hwToData(Resource):
	"""Return the JSON data regarding the given headword."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dict, hw):
		sqlitepath = find_sqlite(dict)
		con = sqlite3.connect(sqlitepath)
		ans = con.execute("SELECT * FROM " + dict + " WHERE key = " + "'" + hw + "'")
		result = []
		for [headword, lnum, data] in ans.fetchall():
			result.append(prep_dict(data, headword))
			return jsonify(result)

if __name__ == "__main__":
	app.run(debug=True)
