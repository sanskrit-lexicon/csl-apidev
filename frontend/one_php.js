

function generateTab(data) {
	x = '';
	var starter = 0;
    console.log('generateTab: data=',data);
	for (dict in data){
	block2 = data[dict];
	if(block2.length > 0) {
		if (starter == 0){
			x += '<input type="radio" name="tabs" id="' + dict + '" checked="checked">';
		}
		else{
			x += '<input type="radio" name="tabs" id="' + dict + '">';
		}
		x += '<label for="'+ dict + '">' + dict.toUpperCase() + '</label>';
		x += '<div class="tab">';
		for (i in block2) {
			block1 = block2[i];
			x += '<h2>' + block1.lnum + '</h2>';
			x += '<p>' + block1.key2 + ' ' + block1.pc + '<p><br/>';
			x += block1.modifiedtext;
			x += '<hr></hr>'
			}
		x += '</div>';
		starter = 1;
		}
	}
	return x;
}

async function getApi() {
	var hw = document.getElementById('headword').value;
	var inTran = document.getElementById('inTran').value;
	var outTran = document.getElementById('outTran').value;
	var dictionary = document.getElementById('dictionary').value;
	var url = '';
	//var reg1 = /[.*+?]/g;
 	var reg1 = /[*?]/g;  // detector for whether this is regex search 
       //var url0 = 'http://127.0.0.1:5000/v0.0.1';
        //var url0 = 'http://localhost/cologne/api';
       // ref: https://stackoverflow.com/questions/2255689/how-to-get-the-file-path-of-the-currently-executing-javascript-code
        //var script = document.currentScript;  
        //var fullUrl = script.src;  // This is empty!
    //  Are we running at Cologne or locally?
    // src = http://localhost/cologne/csl-apidev/frontend/one_php.js
    // at cologne, it will be src = https://sanskrit-lexicon.uni-koeln.de/api/...
    var scripts = document.getElementsByTagName("script"),
    src = scripts[scripts.length-1].src;
    var url0='';
    if (src.indexOf("sanskrit-lexicon.uni-koeln.de") >= 0) {
	url0 = 'https://sanskrit-lexicon.uni-koeln.de/api';
    }else { // xampp
     url0 = 'http://localhost/cologne/api';
    }
    console.log('one_php.js. src =',src);
    console.log('url0=',url0);
	if (hw.match(reg1)){
		if (dictionary == 'all'){
			url = url0 + '/reg/' + hw + '/' + inTran + '/' + outTran;
		}
		else {
			url = url0 + '/dicts/' + dictionary + '/reg/' + hw + '/' + inTran + '/' + outTran;
		}
	}
	else{
		if (dictionary == 'all'){
			url = url0 + '/hw/' + hw + '/' + inTran + '/' + outTran;
		}
		else {
			url = url0 + '/dicts/' + dictionary + '/hw/' + hw + '/' + inTran + '/' + outTran;
		}
	}
    console.log("one_php.js. url=",url);
	const response = await fetch(url);
	const data = await response.json();
	x = await generateTab(data);
	document.getElementById("tabs").innerHTML = x;
}
