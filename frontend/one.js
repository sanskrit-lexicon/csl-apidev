function escHtml(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
  });
}

function generateTab(data) {
  var x = '';
  var starter = 0;
  for (var dict in data) {
    var block2 = data[dict];
    if (block2.length > 0) {
      if (starter == 0) {
        x += '<input type="radio" name="tabs" id="' + escHtml(dict) + '" checked="checked">';
      } else {
        x += '<input type="radio" name="tabs" id="' + escHtml(dict) + '">';
      }
      x += '<label for="' + escHtml(dict) + '">' + escHtml(String(dict).toUpperCase()) + '</label>';
      x += '<div class="tab">';
      for (var i in block2) {
        var block1 = block2[i];
        // lnum/key2/pc are metadata; modifiedtext is trusted server HTML for the entry body
        x += '<h2>' + escHtml(block1.lnum) + '</h2>';
        x += '<p>' + escHtml(block1.key2) + ' ' + escHtml(block1.pc) + '</p><br/>';
        x += block1.modifiedtext;
        x += '<hr>';
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
  var reg1 = /[.*+?]/g;

  // H1523: encodeURIComponent path segments (local-dev prototype still)
  var hwEnc = encodeURIComponent(hw);
  var inEnc = encodeURIComponent(inTran);
  var outEnc = encodeURIComponent(outTran);
  var dictEnc = encodeURIComponent(dictionary);

  if (hw.match(reg1)) {
    if (dictionary == 'all') {
      url = 'http://127.0.0.1:5000/v0.0.1/reg/' + hwEnc + '/' + inEnc + '/' + outEnc;
    } else {
      url = 'http://127.0.0.1:5000/v0.0.1/dicts/' + dictEnc + '/reg/' + hwEnc + '/' + inEnc + '/' + outEnc;
    }
  } else {
    if (dictionary == 'all') {
      url = 'http://127.0.0.1:5000/v0.0.1/hw/' + hwEnc + '/' + inEnc + '/' + outEnc;
    } else {
      url = 'http://127.0.0.1:5000/v0.0.1/dicts/' + dictEnc + '/hw/' + hwEnc + '/' + inEnc + '/' + outEnc;
    }
  }
  console.log(url);
  const response = await fetch(url);
  const data = await response.json();
  var x = generateTab(data);
  document.getElementById('tabs').innerHTML = x;
}
