const url = 'http://127.0.0.1:5000/v0.0.1/hw/rameSa';

async function getGithub() {
	const response = await fetch(url);
	const data = await response.json();
	var i, j, x;
	for (dict in data) {
		block2 = data[dict];
		if(block2.length > 0) {
			x += '<div id="dict"><h1>' + dict + '</h1>';
			for (i in block2) {
				block1 = block2[i];
				x += '<h2>' + block1.lnum + '</h2>';
				x += '<table><tr><th>Attribute</th><th>Value</th></tr>';
				for (j in block1){
					x+= '<tr><td>' + j + '</td>' + '<td>' + block1[j] + '</td></tr>';
				}
				x += '</table></div>';
			}
		}
	}
	document.getElementById("demo").innerHTML = x;
}
getGithub();

