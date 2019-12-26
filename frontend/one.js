

function generateTab(data) {
	x = '';
	var starter = 0;
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
	const url = 'http://127.0.0.1:5000/v0.0.1/hw/' + hw + '/' + inTran + '/' + outTran;
	const response = await fetch(url);
	const data = await response.json();
	x = await generateTab(data);
	document.getElementById("tabs").innerHTML = x;
}
