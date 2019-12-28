# coding=utf-8
from __future__ import print_function
import os
import sys
import sqlite3
import json
import re
from indic_transliteration import sanscript
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
app.config.SWAGGER_UI_REQUEST_DURATION = True
CORS(app)
apiversion = 'v0.0.1'
api = Api(app, version=apiversion, title=u'Cologne Sanskrit-lexicon API', description='Provides APIs to Cologne Sanskrit lexica.')


cologne_dicts = ['acc', 'ae', 'ap90', 'ben', 'bhs', 'bop', 'bor', 'bur', 'cae', 'ccs', 'gra', 'gst', 'ieg', 'inm', 'krm', 'mci', 'md', 'mw', 'mw72', 'mwe', 'pe', 'pgn', 'pui', 'pw', 'pwg', 'sch', 'shs', 'skd', 'snp', 'stc', 'vcp', 'vei', 'wil', 'yat']

def find_sqlite(dictionary):
	path = os.path.abspath(__file__)
	if path.startswith('/nfs/'):
		intermediate = os.path.join(dict.upper() + 'Scan', '2020', 'web', 'sqlite', dictionary + '.sqlite')
	else:
		intermediate = dictionary
	sqlitepath = os.path.join('..', intermediate, 'web', 'sqlite', dictionary + '.sqlite')
	return sqlitepath

def convert_sanskrit(text, inTran, outTran):
	text1 = ''
	counter = 0
	# Remove '<srs/>'
	text = text.replace('<srs/>', '')
	for i in re.split('<s>([^<]*)</s>', text):
		if counter % 2 == 0:
			text1 += i
		else:
			text1 += '<span class="s">' + sanscript.transliterate(i, 'slp1', outTran) + '</span>'
		counter += 1
	# PE nesting of LB tag
	text1 = text1.replace('<div n="1"/>', 'emsp;<div n="1"></div>')
	text1 = text1.replace('<div n="2"/>', 'emsp;emsp;<div n="2"></div>')
	text1 = text1.replace('<div n="3"/>', 'emsp;emsp;emsp;<div n="3"></div>')
	text1 = text1.replace('<div n="4"/>', 'emsp;emsp;emsp;emsp;<div n="4"></div>')
	text1 = text1.replace('<div n="5"/>', 'emsp;emsp;emsp;emsp;emsp;<div n="5"></div>')
	text1 = re.sub('<div n="([^"]*)"/>', '<div n="\g<1>"></div>', text1)
	text1 = text1.replace('<lb/>', '<br />')
	# AP90 compounds and meanings break
	text1 = text1.replace('<b>--', '<br /><b>--')
	text1 = text1.replace('<span class="s">--', '<br /><span class="s">--')
	# — breaks
	text1 = text1.replace('— ', '<br />— ')
	return text1


def longest_string(text):
	splts = re.split('[.*+]+', text)
	longest = ''
	for splt in splts:
		if len(splt) > len(longest):
			longest = splt
	return longest


def block1(data, inTran='slp1', outTran='slp1'):
	root = ET.fromstring(data)
	key1 = root.findall("./h/key1")[0].text
	key2 = root.findall("./h/key2")[0].text
	pc = root.findall("./tail/pc")[0].text
	lnum = root.findall("./tail/L")[0].text
	m = re.split('<body>(.*)</body>', data)
	text = m[1]
	text1 = convert_sanskrit(text, inTran, outTran)
	return {'key1': key1, 'key2': key2, 'pc': pc, 'text': text, 'modifiedtext': text1, 'lnum': lnum}


def block2(dictlist, query, inTran='slp1', outTran='devanagari'):
	final = {}
	for dictionary in dictlist:
		sqlitepath = find_sqlite(dictionary)
		con = sqlite3.connect(sqlitepath)
		query1 = "SELECT * FROM " + dictionary + " WHERE " + query
		ans = con.execute(query1)
		result = []
		for [headword, lnum, data] in ans.fetchall():
			result.append(block1(data, inTran, outTran))
		final[dictionary]  = result
	return final


def block3(dictlist, reg, inTran='slp1', outTran='devanagari'):
	final = {}
	for dictionary in dictlist:
		sqlitepath = find_sqlite(dictionary)
		con = sqlite3.connect(sqlitepath)
		ans = con.execute("SELECT * FROM " + dictionary)
		longestString = longest_string(reg)
		result = []
		for [headword, lnum, data] in ans.fetchall():
			if not longestString in headword:
				pass
			elif re.search(reg, headword):
				result.append(block1(data, inTran, outTran))
		final[dictionary] = result
	return final


@api.route('/' + apiversion + '/dicts/<string:dictionary>/lnum/<string:lnum>')
@api.doc(params={'dictionary': 'Dictionary code.', 'lnum': 'L number.'})
class LnumToData(Resource):
	"""Return the JSON data regarding the given Lnum."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dictionary, lnum):
		dictlist = [dictionary]
		final = block2(dictlist, "lnum = " + str(lnum))
		return jsonify(final)
 

@api.route('/' + apiversion + '/dicts/<string:dictionary>/hw/<string:hw>')
@api.doc(params={'dictionary': 'Dictionary code.', 'hw': 'Headword to search.'})
class hwToData(Resource):
	"""Return the JSON data regarding the given headword."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dictionary, hw):
		dictlist = [dictionary]
		final = block2(dictlist, "key = " + "'" + hw + "'")
		return jsonify(final)


@api.route('/' + apiversion + '/dicts/<string:dictionary>/reg/<string:reg>')
@api.doc(params={'dictionary': 'Dictionary code.', 'reg': 'Find the headwords matching the given regex.'})
class regexToHw(Resource):
	"""Return the headwords matching the given regex."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dictionary, reg):
		dictlist = [dictionary]
		final = block3(dictlist, reg)
		return jsonify(final)


@api.route('/' + apiversion + '/hw/<string:hw>')
@api.doc(params={'hw': 'Headword to search in all dictionaries.'})
class hwToAll(Resource):
	"""Return the entries of this headword from all dictionaries."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, hw):
		dictlist = cologne_dicts
		final = block2(dictlist,  "key = " + "'" + hw + "'")
		return jsonify(final)


@api.route('/' + apiversion + '/hw/<string:hw>/<string:inTran>/<string:outTran>')
@api.doc(params={'hw': 'Headword to search.', 'inTran': 'Input transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis', 'outTran': 'Output transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis'})
class hwToAll2(Resource):
	"""Return the entries of this headword from all dictionaries."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, hw, inTran, outTran):
		dictlist = cologne_dicts
		final = block2(dictlist, "key = " + "'" + hw + "'", inTran, outTran)
		return jsonify(final)


@api.route('/' + apiversion + '/reg/<string:reg>')
@api.doc(params={'reg': 'Regex to search in all dictionaries.'})
class regToAll(Resource):
	"""Return the entries of this headword from all dictionaries."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, reg):
		dictlist = cologne_dicts
		final = block3(dictlist, reg)
		return jsonify(final)


@api.route('/' + apiversion + '/dicts/<string:dictionary>/hw/<string:hw>/<string:inTran>/<string:outTran>')
@api.doc(params={'dictionary': 'Dictionary code.', 'hw': 'Headword to search.', 'inTran': 'Input transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis', 'outTran': 'Output transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis'})
class hwToData1(Resource):
	"""Return the JSON data regarding the given headword for given input transliteration and output transliteration."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dictionary, hw, inTran, outTran):
		hw = sanscript.transliterate(hw, inTran, 'slp1')
		dictlist = [dictionary]
		final = block2(dictlist, "key = " + "'" + hw + "'", inTran, outTran)
		return jsonify(final)



@api.route('/' + apiversion + '/reg/<string:reg>/<string:inTran>/<string:outTran>')
@api.doc(params={'reg': 'Regex to search.', 'inTran': 'Input transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis', 'outTran': 'Output transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis'})
class regToAll1(Resource):
	"""Return the entries of this headword from all dictionaries."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, reg, inTran, outTran):
		reg = sanscript.transliterate(reg, inTran, 'slp1')
		dictlist = cologne_dicts
		final = block3(dictlist, reg, inTran, outTran)
		return jsonify(final)


if __name__ == "__main__":
	print(longest_string('ram.*Ta'))
	app.run(debug=True)
	
