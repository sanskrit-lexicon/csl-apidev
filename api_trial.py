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

# Start Flask app.
app = Flask(__name__)
app.config['JSON_AS_ASCII'] = False
# Show request duration.
app.config.SWAGGER_UI_REQUEST_DURATION = True
CORS(app)
apiversion = 'v0.0.1'
api = Api(app, version=apiversion, title=u'Cologne Sanskrit-lexicon API', description='Provides APIs to Cologne Sanskrit lexica.')


# List of 34 open domain cologne dictionaries.
cologne_dicts = ['acc', 'ae', 'ap90', 'ben', 'bhs', 'bop', 'bor', 'bur', 'cae', 'ccs', 'gra', 'gst', 'ieg', 'inm', 'krm', 'mci', 'md', 'mw', 'mw72', 'mwe', 'pe', 'pgn', 'pui', 'pw', 'pwg', 'sch', 'shs', 'skd', 'snp', 'stc', 'vcp', 'vei', 'wil', 'yat']



def find_sqlite(dictionary):
	"""Return path to the sqlite file based on cologne server or local."""

	path = os.path.abspath(__file__)
	# If on Cologne server,
	if path.startswith('/nfs/'):
		intermediate = os.path.join(dict.upper() + 'Scan', '2020', 'web', 'sqlite', dictionary + '.sqlite')
	else:
		intermediate = dictionary
	sqlitepath = os.path.join('..', intermediate, 'web', 'sqlite', dictionary + '.sqlite')
	return sqlitepath


def convert_sanskrit(text, inTran, outTran):
	"""Return transliterated adjusted text."""

	text1 = ''
	counter = 0
	# Remove '<srs/>'
	text = text.replace('<srs/>', '')
	# Change the s tag to span.
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
	"""Return the longest tring from input regular expression."""
	
	splts = re.split('[.*+]+', text)
	longest = ''
	for splt in splts:
		if len(splt) > len(longest):
			longest = splt
	return longest


def block1(data, inTran='slp1', outTran='slp1'):
	"""Return a dict from input data."""

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
	"""Return a dict with dictcode as key and a list of block1 as value."""
	
	final = {}
	for dictionary in dictlist:
		sqlitepath = find_sqlite(dictionary)
		# Connect to DB.
		con = sqlite3.connect(sqlitepath)
		# Query.
		query1 = "SELECT * FROM " + dictionary + " WHERE " + query
		# Execute query.
		ans = con.execute(query1)
		# Initialize a blank list.
		result = []
		# For all results,
		for [headword, lnum, data] in ans.fetchall():
			# Append to list.
			result.append(block1(data, inTran, outTran))
		# dictionary code as key and list as value.
		final[dictionary]  = result
	return final


def block3(dictlist, reg, inTran='slp1', outTran='devanagari'):
	"""Return a dict with dictcode as key and a list of block1 as value. Regex matched."""
	
	final = {}
	for dictionary in dictlist:
		sqlitepath = find_sqlite(dictionary)
		con = sqlite3.connect(sqlitepath)
		# Read all entries.
		ans = con.execute("SELECT * FROM " + dictionary)
		# Longest string
		longestString = longest_string(reg)
		result = []
		# For all entries in a dictionary,
		for [headword, lnum, data] in ans.fetchall():
			# If the longestString in headword,
			if longestString in headword:
				# And matches the regex,
				if re.search(reg, headword):
					# Append to result.
					result.append(block1(data, inTran, outTran))
		final[dictionary] = result
	return final


@api.route('/' + apiversion + '/dicts/<string:dictionary>/lnum/<string:lnum>')
@api.doc(params={'dictionary': 'Dictionary code.', 'lnum': 'L number.'})
class DL(Resource):
	"""Return result for given dictionary and lnum."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dictionary, lnum):
		dictlist = [dictionary]
		final = block2(dictlist, "lnum = " + str(lnum))
		return jsonify(final)
 

@api.route('/' + apiversion + '/dicts/<string:dictionary>/hw/<string:hw>')
@api.doc(params={'dictionary': 'Dictionary code.', 'hw': 'Headword to search.'})
class DH(Resource):
	"""Return result for given dictionary and headword."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dictionary, hw):
		dictlist = [dictionary]
		final = block2(dictlist, "key = " + "'" + hw + "'")
		return jsonify(final)


@api.route('/' + apiversion + '/dicts/<string:dictionary>/reg/<string:reg>')
@api.doc(params={'dictionary': 'Dictionary code.', 'reg': 'Find the headwords matching the given regex.'})
class DR(Resource):
	"""Return result for given dictionary and regex."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dictionary, reg):
		dictlist = [dictionary]
		final = block3(dictlist, reg)
		return jsonify(final)


@api.route('/' + apiversion + '/hw/<string:hw>')
@api.doc(params={'hw': 'Headword to search in all dictionaries.'})
class H(Resource):
	"""Return the entries of headword from all dictionaries."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, hw):
		dictlist = cologne_dicts
		final = block2(dictlist,  "key = " + "'" + hw + "'")
		return jsonify(final)


@api.route('/' + apiversion + '/hw/<string:hw>/<string:inTran>/<string:outTran>')
@api.doc(params={'hw': 'Headword to search.', 'inTran': 'Input transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis', 'outTran': 'Output transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis'})
class HIO(Resource):
	"""Return the entries of this headword from all dictionaries with specified input and output transliteration."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, hw, inTran, outTran):
		dictlist = cologne_dicts
		final = block2(dictlist, "key = " + "'" + hw + "'", inTran, outTran)
		return jsonify(final)


@api.route('/' + apiversion + '/reg/<string:reg>')
@api.doc(params={'reg': 'Regex to search in all dictionaries.'})
class R(Resource):
	"""Return the entries of this regex from all dictionaries."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, reg):
		dictlist = cologne_dicts
		final = block3(dictlist, reg)
		return jsonify(final)


@api.route('/' + apiversion + '/dicts/<string:dictionary>/hw/<string:hw>/<string:inTran>/<string:outTran>')
@api.doc(params={'dictionary': 'Dictionary code.', 'hw': 'Headword to search.', 'inTran': 'Input transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis', 'outTran': 'Output transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis'})
class DHIO(Resource):
	"""Return the entries of this headword from specified dictionary with specified input and output transliteration."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dictionary, hw, inTran, outTran):
		hw = sanscript.transliterate(hw, inTran, 'slp1')
		dictlist = [dictionary]
		final = block2(dictlist, "key = " + "'" + hw + "'", inTran, outTran)
		return jsonify(final)



@api.route('/' + apiversion + '/reg/<string:reg>/<string:inTran>/<string:outTran>')
@api.doc(params={'reg': 'Regex to search.', 'inTran': 'Input transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis', 'outTran': 'Output transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis'})
class RIO(Resource):
	"""Return the entries of this regex from all dictionaries with specified input and output transliteration."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, reg, inTran, outTran):
		reg = sanscript.transliterate(reg, inTran, 'slp1')
		dictlist = cologne_dicts
		final = block3(dictlist, reg, inTran, outTran)
		return jsonify(final)


@api.route('/' + apiversion + '/dicts/<string:dictionary>/reg/<string:reg>/<string:inTran>/<string:outTran>')
@api.doc(params={'dictionary': 'Dictionary code.', 'reg': 'Regex to search.', 'inTran': 'Input transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis', 'outTran': 'Output transliteration. devanagari/slp1/iast/hk/wx/itrans/kolkata/velthuis'})
class DRIO(Resource):
	"""Return the entries of this regex from specified dictionary with specified input and output transliteration."""

	get_parser = reqparse.RequestParser()

	@api.expect(get_parser, validate=True)
	def get(self, dictionary, reg, inTran, outTran):
		reg = sanscript.transliterate(reg, inTran, 'slp1')
		dictlist = [dictionary]
		final = block3(dictlist, reg, inTran, outTran)
		return jsonify(final)


if __name__ == "__main__":
	app.run(debug=True)
	
