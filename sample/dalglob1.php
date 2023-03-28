<?php
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
<script type="text/javascript" src="//code.jquery.com/jquery-2.1.4.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
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
  console.clear();  // So console.log messages don't pile up.
  var key = $('#key').val();
  var input = $('#input').val();
  var output = $('#output').val();
  var accent = 'no'; //$('#accent').val();
  // TODO: check for valid inputs before ajax call
  
  var urlbase = urlbaseF() + "/csl-apidev/dalglob.php";
  var url =  urlbase +  
   "?key=" +escape(key) + 
   "&output=" +escape(output) +
   "&accent=" + escape(accent) +
   "&input=" + escape(input) + 
   "&dbglob=keydoc_glob1" +
   "&dev=no";
  console.log('dictlistDisplay url=',url);
  $('#dictlist').html("");
  $('#disp').html("");
  $.ajax({
   url:url,
   datatype:"json",   
   success: function(data0) {
    console.log('returned data',data0);
    console.log('data is of type',typeof data0);
    data = JSON.parse(data0);
    console.log('parsed data',data);
    let html;
    if(data.status == 200) {
     let dictdata = data.dicts;
     
     let f = function(dictrec) {
       let dict = dictrec.dict;
       let dockeys = dictrec.dockeys;
       let dockeystr = dockeys.join(" ");
       let parm = dict + " " + dockeystr;
       let button = "<button onclick='getdataForkeyDict(\"" +parm + "\")'>" + dict + "</button>";
       let ans = button + "  " + dockeystr + "<br/>";
       return ans;
     };
     a = dictdata.map(f);
     html = a.join("  ");
    }else {
     html = key + " not found in any dictionary: status=" + data.status;
    }
    $('#dictlist').html(html);
  }
 });
}; // function dictlistDisplay

getdataForkeyDict_url = function(key,dict) {
  var input = $('#input').val();
  input = 'slp1';
  var output = $('#output').val();
  var accent = 'no'; //$('#accent').val();
  var urlbase = urlbaseF() + "/csl-apidev/getword.php";
  var url =  urlbase +  
   "?key=" +escape(key) + 
   "&output=" +escape(output) +
   "&dict=" + escape(dict) +
   "&accent=" + escape(accent) +
   "&input=" + escape(input) + 
   "&dev=yes";
  return url;
}
getdataForkeyDict = function(parmstr) {
 let parmarr = parmstr.split(" ");
 let dict = parmarr[0];
 let dockeys = parmarr.slice(1);
 console.log('getdataForkeyDict: dict=',dict,'dockeys=',dockeys);
 var totalRequestCount = dockeys.length;  // 1 url for each dockey
 var resultarr= []; 
 for(var i=0;i<dockeys.length;i++) {
  let key = dockeys[i];
  let result = {
   key:key,
   url:getdataForkeyDict_url(key,dict),
   status: 404,  // initially unavailable
   result: `<p>Result not available for ${key}</p>`
  }; // result
  resultarr[i] = result;
 }

 var showResults_tabs = function(resultarr,dict) {
  let html = "";
  html = html + `<div class="sticky">`;
  html = html + `<div class="tab">`;
  console.log('showResults_tabs. resultarr length=',resultarr.length)
  for(var i=0;i<resultarr.length;i++) {
   let resultval = resultarr[i];
   let key = resultval.key;
   let id = `disp_${key}`;
   let btnid = `button_${id}`;
   html = html + `<button id="${btnid}" class="tablinks" onclick="openHw(event,'${id}')">${key}</button>`;
  }
  html = html + "</div>";  // close class tab
  html = html + "</div>";  // close class sticky
  console.log('showResults_tabs returns',html);
  return html;
 };
 var showResults = function(resultarr) {
  let html = "";
  html = html + showResults_tabs(resultarr,dict);
  html = html + `<h3>${dict}</h3>`;
  let id0 = "";
  for(var i=0;i<resultarr.length;i++) {
   let resultval = resultarr[i];
   let input = resultval.input;
   let serverval = resultval.result;
   let key = resultval.key;
   let id = `disp_${key}`;
   let html0 = `<div id="${id}" class="tabcontent">`;
   if (i == 0) {id0 = id;}
   html0 = html0 + serverval + "</div>";
   html = html + html0;
  }
  $('#disp').html(html);
  let btnid0 = `button_${id0}`;
  document.getElementById(btnid0).click(); // To show first result
 };
 
 makeRequest = function(i){  
  var input = $('#key' + i).val();
  var resultarrElt = resultarr[i-1]; // 1<=i<=totalRequestCount
  var reqUrl = resultarrElt.url;
  console.log('reqUrl=',reqUrl);
  fetch(reqUrl)  
   .then(response => response.text())  // convert to text
   .then(text => {
    console.log('makeRequest for',i,reqUrl);
    resultarrElt.status = 200;
    // correct a reference to basic.css
    let text1 = text.replace("css/basic.css","../css/basic.css");
    resultarrElt.result = text1;
    // console.log('   result text=',text1);
    executedRequest++;  
    if (totalRequestCount == executedRequest) {
     console.log("All Requests are executed");  
     showResults(resultarr);
    }
   }); // .then text => ...
 }; // makeRequest

 var totalRequestCount = dockeys.length;  // 1 url for each dockey
 var executedRequest = 0;
 // Start async call inside loops  
 for(var i=totalRequestCount;i>=1;i--) {
  makeRequest(i);  
 } // for i


}; // getdataForkeyDict


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
  "<?php echo $_GET['key']?>",
  "<?php echo $_GET['input']?>",
  "<?php echo $_GET['output']?>"
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
