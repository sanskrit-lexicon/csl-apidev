<?php
// H1523: baseline headers + CSP-Report-Only
require_once(__DIR__ . '/../security_headers.php');
//error_reporting(E_ALL & (~E_NOTICE & ~E_WARNING));
//dalglob1_v03.php
?>
<?php
function init_dalglob1_parms() {
 $temp_names = ['key','input','output'];
 $default_values = ['','slp1','iast'];
 for($i=0;$i<count($temp_names);$i++) {
  $temp = $temp_names[$i];
  if (!isset($_GET[$temp])) {
   $_GET[$temp] = $default_values[$i];
  }
 }
}
init_dalglob1_parms();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>dalgob1-dev</title>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.css">
<script type="text/javascript" src="//code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
// H1523: escape helpers for sample multi-dict demo HTML construction
function cslEscHtml(s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function cslEscJsDq(s) {
  return String(s == null ? '' : s)
    .replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/'/g, "\\'")
    .replace(/\n/g, '\\n').replace(/\r/g, '\\r')
    .replace(/</g, '\\x3c').replace(/>/g, '\\x3e');
}
</script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/js-cookie/3.0.5/js.cookie.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<link rel="stylesheet" type="text/css" href="../css/basic.css">
<link rel="stylesheet" type="text/css" href="dalglob1.css">
<script>
function openHw(evt, hwName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(hwName).style.display = "block";
  evt.currentTarget.className += " active";
}

</script>
<script>
$(document).ready(function() {
 $('#key').keypress(function (e) {
  if(e.which == 13)  // the enter key code
   {e.preventDefault();
    dictlistDisplay();
   }
 }); // end keypress

 urlbaseF = function () {
  let origin = window.location.origin;  
  if (origin.indexOf("sanskrit-lexicon.uni-koeln.de") >= 0)  {
   return origin + "/scans";
  }else {
   return origin + "/cologne";
  }
 }
 dictlistDisplay = function () {
  console.clear();
  var key = $('#key').val();
  var input = $('#input').val();
  var output = $('#output').val();

  var urlbase = urlbaseF() + "/csl-apidev/api1/salt_multidict.php";
  var url = urlbase +
   "?key=" + encodeURIComponent(key) +
   "&input=" + encodeURIComponent(input) +
   "&output=" + encodeURIComponent(output) +
   "&field=csl";

  console.log('dictlistDisplay url=', url);
  $('#dictlist').html("");
  $('#disp').html("");

  $.ajax({
   url: url,
   datatype: "json",
    success: function(data) {
     console.log('returned data', data);
     if (!data || data.status != 200) {
      $('#dictlist').html(cslEscHtml(key) + " not found in any dictionary");
      return;
     }
     var dicts = data.dicts;
     var dictmeta = data.dictmeta || {};
     var dictCodes = Object.keys(dicts);
     if (dictCodes.length === 0) {
      $('#dictlist').html(cslEscHtml(key) + " not found in any dictionary");
      return;
     }

     var tabHtml = '<div class="sticky"><div class="tab">';
     var contentHtml = '';
     var firstId = '';

     for (var idx = 0; idx < dictCodes.length; idx++) {
      var dict = dictCodes[idx];
      var entries = dicts[dict];
      var dictName = (dictmeta[dict] && dictmeta[dict].name) ? dictmeta[dict].name : dict;
      var id = 'dict_' + dict;
      var btnId = 'button_' + id;
      if (idx === 0) firstId = id;

      tabHtml += '<button id="' + btnId + '" class="tablinks" onclick="openHw(event,\'' + id + '\')">' + cslEscHtml(dictName) + '</button>';

      var html = '<div id="' + id + '" class="tabcontent">';
      html += '<h3>' + cslEscHtml(dictName) + '</h3>';
      for (var ei = 0; ei < entries.length; ei++) {
       var entry = entries[ei];
       if (entry.csl && entry.csl.html) {
        html += entry.csl.html;
       }
      }
      html += '</div>';
      contentHtml += html;
     }
     tabHtml += '</div></div>';

     $('#disp').html(tabHtml + contentHtml);
     if (firstId) {
      document.getElementById('button_' + firstId).click();
     }
    },
    error: function(jqXHR, textStatus, errorThrown) {
     $('#dictlist').html('Request failed: ' + cslEscHtml(textStatus));
     console.log('dictlistDisplay error:', textStatus, errorThrown);
    }
  });
 }; // function dictlistDisplay


// Allow parameter input for key, input, and output
 phpinit_helper = function(name,val){
  if (val == ''){return;}
  if (name == 'accent') { //val should be yes or no. Case not important
   val = val.toLowerCase();
  }
  if (val == 'iast') {val = 'roman';}
  $('#' + name).val(val);
  console.log("phpinit_helper: change #",name,"to",val);
 };
 phpinit = function() {
  var names = ['key','input','output'];
  var phpvals=[ // same order as names
  // Each value is reflected into a JS string. is_string() blocks array-injection
  // (?key[]=x); htmlspecialchars() neutralises HTML metachars and is the
  // sanitizer Semgrep's echoed-request rule recognises; json_encode() supplies
  // the quoted, escaped JS string literal.
  <?php echo json_encode(htmlspecialchars(isset($_GET['key'])    && is_string($_GET['key'])    ? $_GET['key']    : '')) ?>,
  <?php echo json_encode(htmlspecialchars(isset($_GET['input'])  && is_string($_GET['input'])  ? $_GET['input']  : '')) ?>,
  <?php echo json_encode(htmlspecialchars(isset($_GET['output']) && is_string($_GET['output']) ? $_GET['output'] : '')) ?>
  ];
  console.log('phpvals=',phpvals);
  var i,name,phpval;
  for(i=0;i<names.length;i++) {
   phpinit_helper(names[i],phpvals[i]);
  }
  // If key is provided, generate display for it
  if($('#key').val() != '') {
   dictlistDisplay();
  }
 };
 phpinit();

}); // document.ready

</script>
</head>

<body>
 <div id="logo">
     <a href="//www.sanskrit-lexicon.uni-koeln.de/">
      <img id="unilogo" src="//www.sanskrit-lexicon.uni-koeln.de/images/cologne_univ_seal.gif"
           alt="University of Cologne" width="60" height="60" 
           title="Cologne Sanskrit Lexicon"/>
      </a>
 </div>

<table id="preferences">
<tr><td>
 <div id="inputdiv">
  <label for="input">input</label>
  <select name="input" id="input">
   <option value='hk' selected='selected'>KH</option>
   <option value='slp1'>SLP1</option>
   <option value='itrans'>ITRANS</option>
   <option value='deva'>Devanagari</option>
   <option value='roman'>IAST</option>
  </select>
 </div>
</td><td>
 <div id="outputdiv">
  <label for="output">output</label>
  <select name="output" id="output">
   <option value='deva'>Devanagari</option>
   <option value='hk'>KH</option>
   <option value='slp1'>SLP1</option>
   <option value='itrans'>ITRANS</option>
   <option value='roman' selected='selected'>IAST</option>
  </select>
 </div>
</td></tr>
</table>
 <div id="citationdiv">
  citation:&nbsp;
  <input type="text" name="key" size="20" id="key" value="" style="height:1.4em;"/>
 </div>
<div id="dictlist"></div>
 <div id="disp">
  <!-- Requesting data will change the src attribute of this div -->
 </div>

</body>

</html>
