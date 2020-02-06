<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>dalgob1-dev</title>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.css">
<!-- links to jquery, using CDNs -->
<script type="text/javascript" src="//code.jquery.com/jquery-2.1.4.min.js"></script>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<!-- jquery-ui is used -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<!-- this stylesheet is NOT portable, but does work when used
 either at Cologne, or in a local installation
-->
<link rel="stylesheet" type="text/css" href="../css/basic.css">
<style>
body {
 color: black; background-color:#DBE4ED;
 /*font-size: 14pt; */
}

#dataframe,#disp {
 color: black; background-color: white;
 padding-left:5px;
 padding-right:2.0em;
 width: 600px;
 height: 600px;
 /* resize doesn't work on Firefox. 
  On Chrome, the size may be increased, but not decreased*/
 /*resize: both; */
overflow: auto;
}

#accentdiv,#outputdiv,#inputdiv,#dictionary input,label {display:block;}

#preferences {
 position:absolute;
 left:100px;
 top: 15px;
}
#preferences td {
 /*border:1px solid black;*/
 background-color:white;
 padding-left:5px;
 padding-right:5px;
 text-align:center;
}
#citationdiv{
 padding-bottom:5px;
}
#correction {

 padding-left: 130px;
}
#dictlist {
 margin-bottom:10px;
 padding-left:5px;
 color: black; background-color: white;
 width: 600px;
 height:200px;
 /* resize doesn't work on Firefox. 
  On Chrome, the size may be increased, but not decreased*/
 /*resize: both; */
 overflow: auto; 
 
}
</style>

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
  var key = $('#key').val();
  //var dict = $('#dict').val();
  var input = $('#input').val();
  var output = $('#output').val();
  var accent = 'no'; //$('#accent').val();
  //console.log('listDisplay: accent value=',accent);

  // TODO: check for valid inputs before ajax call
  
  //var urlbase="//www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/listview.php";
  var urlbase = urlbaseF() + "/csl-apidev/dalglob.php";
  var url =  urlbase +  
   "?key=" +escape(key) + 
   "&output=" +escape(output) +
   //"&dict=" + escape(dict) +
   "&accent=" + escape(accent) +
   "&input=" + escape(input) + 
   "&dbglob=keydoc_glob1" +
   "&dev=yes";
    //jQuery("#disp").html(""); // clear output
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
       let button = "<button onclick='getdataForkeyDict(\"" +dict + "\")'>" + dict + "</button>";
       let dockeystr = dockeys.join(" ");
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
getdataForkeyDict = function(dict) {
  console.log('getdataForkeyDict: dict=',dict);

  var key = $('#key').val();
  var input = $('#input').val();
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
  console.log('getdataForkeyDict url=',url);
  $('#disp').html("");
  $.ajax({
   url:url,
   datatype:"json",   
   success: function(data0) {
    // data0 is a string (of html)
    //console.log('returned data',data0);
    //let thetype=typeof data0;
    //console.log('data is of type',thetype);
    //$('#disp').html('got some data of type ' + thetype + ' for dict '+dict);
    let html = "<h3>"+dict+"</h3>";
    html = html + data0;
    $('#disp').html(html);
  }
 });
}; // function getdataForkeyDict

});

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
   <option value='hk' selected='selected'>KH <!--Kyoto-Harvard--></option>
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
   <option value='hk'>KH <!--Kyoto-Harvard--></option>
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
  <!--<input type="button" id="correction" value="Corrections" /> -->
  <!-- href is set in change function on #dict -->
 <!--
  <a id="correction" href="#" target="Corrections">Corrections</a>
 -->
 </div>
<div id="dictlist"></div>
 <div id="disp">
  <!-- Requesting data will change the src attribute of this iframe -->
 <!--
  <iframe id="dataframe">  
   <p>Your browser does not support iframes.</p>
  </iframe>
 -->
 </div>

</body>

</html>
