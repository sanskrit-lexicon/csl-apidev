<?php
 /* This is file list-0.2s_rw.php.  ('rw' = rewrite)
  Allows /simple/ urls to be parsed.
  See .htaccess in root directory.
  12-010-2020:  Use '$dictinfowhich' to detect whether we are
  running in 'cologne' file system or 'xampp' file system.
  12-25-2020  v1.1 test version
 */
require_once('dictinfowhich.php'); // exposes $dictinfowhich
require_once('../../dbgprint.php');
require_once('parse_uri.php');
//require_once('get_parent_dirpfx.php');
$version = 'v1.1';  # 12-25-2020
$htaccess = 'simple[^/]*'; #12-25-2020
// Report all errors except E_NOTICE and E_WARNING
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
$phpvals = parse_uri($htaccess);
if (false) {
 dbgprint(true,"list-0.2s_rw.php:  phpvals returned from parse_uri\n");
 foreach($phpvals as $key=>$val) {
  dbgprint(true,"phpvals[$key] = $val \n");
 }
}
?>
<!DOCTYPE html>
<html>
<head>
<META charset="UTF-8">
<title>Cologne Sanskrit Simple Search</title>
<!-- ref=https://www.w3.org/TR/html4/struct/links.html#edef-BASE -->
<?php 
 if ($dictinfowhich == "cologne") {
  echo '<BASE href="/scans/csl-apidev/simple-search/' . $version . '/">' . "\n";
 }else {
  echo '<BASE href="http://localhost/cologne/csl-apidev/simple-search/' . $version . '/">' . "\n";
 }
?>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.css">
<!-- links to jquery, using CDNs -->
<script type="text/javascript" src="//code.jquery.com/jquery-2.1.4.min.js"></script>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<!-- jquery-ui is used -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<!-- local scripts -->
<script type="text/javascript" src="../../sample/dictnames.js"></script>
<!--
<script type="text/javascript" src="../../sample/cookieUpdate.js"></script>
-->
<script type="text/javascript" src="cookieUpdate.js"></script>

<style>
body {
 color: black; background-color:#DBE4ED;
 /*font-size: 14pt; */
}
@font-face { 
 src: url(../../fonts/siddhanta1.ttf);   /* location specific */
 font-family: siddhanta_deva;
}
.sdata { /* Sanskrit-data -- Devanagari */
 font-family: siddhanta_deva;
}

#simpleinfo {
 width: 600px;
}
#simpleinfo > ol {
 padding-left: 10px;
}
#dataframe {
 color: black; background-color: white;
 width: 600px;
 height: 600px;
 /* resize doesn't work on Firefox. 
  On Chrome, the size may be increased, but not decreased*/
 resize: both;
 /*overflow: auto; */
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

a.hwlinks:hover {
background-color:#A9C1CB;
cursor: pointer;
text-decoration: none;
}
a.hwlinks {
 background-color: white;
}

a.hwlinks:active,a.hwlinks:visisted {
color:red;
}

</style>
<script> 
 // Jquery
$(document).ready(function() {
 let cookieUpdate = CologneDisplays.dictionaries.cookieUpdate;

 $('#output,#accent').change(function(event) {
  //console.log('list-0.2s_rw.php check1');
  cookieUpdate(true);   
  $('#simpleinfo').html("");
 });
 $('#input').change(function(event) {
  cookieUpdate(true);   
  //console.log('change event for #input');
  $('#simpleinfo').html("");
  changeActions();
 });

 $('#dict').change(function(event) {
  //console.log('dict change: dict=',$('#dict').val());
  cookieUpdate(true);   
  $('#simpleinfo').html("");
  changeActions();
 });

 changeCorrectionHref = function () {
  //console.log('changeCorrectionHref: dict=',$('#dict').val());
  var dict = $('#dict').val();
  var url = "//www.sanskrit-lexicon.uni-koeln.de/php/correction_form.php?dict=" + dict;
  $('#correction').attr('href',url);
 };
 keyAutocompleteActivation = function () {
  // Either Enable or Disable the autocomplete on #key, depending on
  // circumstances
  var dictlo = $('#dict').val().toLowerCase();
  var input = $('#input').val();
  var flag;
  if (input == 'simple') {
   if (['ae','mwe','bor'].indexOf(dictlo) == -1) {
    flag = 'disable';
   }else {
    flag = 'enable';
   }
  }else {
   flag = 'enable';
  }
  $('#key').autocomplete(flag);
  //console.log('keyAutocompleteActivation:',dictlo,input,flag);
 };
 changeActions = function () {
  // DO things when the input parameters are either
  //  initialized from cookies or change  due to user input
  changeCorrectionHref();
  keyAutocompleteActivation();
  /* Display #input_simplediv IFF input == simple */
  var element = document.querySelector("#input_simplediv");
  document.querySelector('#input').addEventListener('change', function(){
  if(this.value != 'simple')
    element.style.display = 'none';
  else
    element.style.display = 'block';
  });
 };
 /* 
 ------------------------------------
 listDisplay is used when 'input' is NOT simple 
 ------------------------------------ 
 */
 listDisplay = function (keyin) {
  var key;
  if (keyin == undefined) {
   key = $('#key').val();  
  } else {
   key = keyin;
  }
  var dict = $('#dict').val();
  var input = $('#input').val();
  if (input == 'simple') {  // Needed?
   input = 'slp1';  // used when keyin is present. 
  }
  //console.log('listDisplay: keyin=',keyin,'input=',input);
  var output = $('#output').val();
  var accent = $('#accent').val();
  var urlbase="../../listview.php";
  var url =  urlbase +  
   "?key=" +escape(key) + 
   "&output=" +escape(output) +
   "&dict=" + escape(dict) +
   "&accent=" + escape(accent) +
   "&input=" + escape(input);
  jQuery("#dataframe").attr("src",url);
    
 }; // listDisplay
 
/* Jquery UI functions for selecting a dictionary from the #dict div */
$("#dict").autocomplete( { 
  source : CologneDisplays.dictionaries.dictshow,
  autoFocus: true,
  minLength: 0,
 });

var prevdictval; // GLOBAL. Used in focus and blur function below.
$("#dict").focus(function() {
 // ref: https://stackoverflow.com/questions/4132058/display-jquery-ui-auto-complete-list-on-focus-event
 // Aim to have the dict autocomplete widget behave like a
 // <select><option> menu WITH THE DIFFERENCE that a 'hidden' dictionary
 // name can be typed.
  prevdictval = $(this).val();
  $(this).val('');
  $(this).trigger(jQuery.Event("keydown"));
  }).blur(function() {
   // This check also inspired by stackoverflow suggestion.
   if($(this).val() == "") {
    $(this).val(prevdictval);
   }else {
    // fire a change event. THIS IS QUITE SUBTLE!
    // without this, the $('#dict').change function above 
    // does not run (and the cookie is not updated).
    // ref: https://stackoverflow.com/questions/4672505/why-does-the-jquery-change-event-not-trigger-when-i-set-the-value-of-a-select-us
    $(this).change();  
   }
  }).change(function(event) {
  cookieUpdate(true);   
  $('#simpleinfo').html("");
  changeActions();
 });
 /*
 -----------------------------------------------------------
 simpleDisplay  The display when input=simple.
 calls getword_list_1.0.php
 -----------------------------------------------------------
 */
 simpleDisplay = function(){
  var find_word = $('#key').val();
  find_word = find_word.trim();
  var test ={}; // Javascript object to pass as parameters
  test.key = find_word;
  test.url = "getword_list_1.0.php";  // 01-22-2021
  test.input = $('#input_simple').val();  // 
  //console.log('list-0.2s_rw: simpleDisplay: test.input=',test.input);
  test.output = $('#output').val(); //'deva';
  test.dict = $('#dict').val();; 
  // Blank out result area
  $('#simpleinfo').html(" working...")
  $.ajax({
   url: test.url,
   cache: false,
   method: "POST",
   data: {
    input:test.input, // 
    output:test.output, // not currently used
    dict:test.dict, // not currently used
    key:test.key
   }
  }) // end of $.ajax
  .done(displayOption2)
  .fail(function( jqXHR, textStatus, errorThrown){
    console.log("list-0.2s_rw.php: Failure point:",textStatus.toString())
    $('#simpleinfo').html(
     textStatus.toString()+" "+errorThrown.toString()+"<br>");
 }); // end of $.ajax 

};

displayOption2Helper = function(dicthw,index,nresults) {
 for(var i=0;i<nresults;i++) {
  var selector = '#hwlink_'+i;
  if (i == index) {
   $(selector).css('background-color','beige');
  } else {
   $(selector).css('background-color','white');
  }
 }
 listDisplay(dicthw);
}
displayOption2_error = function(json) {
 console.log('displayOption2 error: json=',json); 
 var html = `<p>Programming error in simple search at displayOption2<br/>
 Please notify programmer Funderburk of the error, with your inputs <br/>
 at the email at bottom of Sanskrit-lexicon home page.`
 $('#simpleinfo').html(html);
}
displayOption2 = function(json) {
 // console.log('list-0.2s_rw.php: displayOption2');
 // console.log('json=',json);
 // 02-05-2021. Prepare for errors (e.g. non-json results)
 var results = json['result'];
 if (! results) { // results may be undefined
  displayOption2_error();
  return;
 } 
 var dict = json['dict'];
 var nresults = results.length;
 var nfound = nresults;
 var htmlarr = [];
 var dicthwfirst = null;
 if (true) {
  htmlarr.push('<ol>')
  if (nresults == 1) {
   htmlarr.push(nresults + " result: ");
  }else if (nresults == 0) {
   htmlarr.push(nresults + " no results found ");
  }else {
   htmlarr.push(nresults + " results: ");
  }
  results.forEach(function(result,index) {
   var dicthw,dicthwoutput,dicthwFlag;
   dicthw = result['dicthw'];
   dicthwFlag = result['user_key_flag']
   if (index == 0) {
    dicthwfirst = dicthw;
   }
   dicthwoutput = result['dicthwoutput'];
   if (dicthwFlag) { 
    // 11-01-2017. Bold when this is user input
    dicthwoutput = "<strong>" + dicthwoutput + "</strong>";
   }
   var classattr = '';
   if (json['output'] == 'deva') {
    classattr = ' class="sdata"'; // control font for Devanagari
   }
   var x = "<a class='hwlinks' id='hwlink_" + 
        index + "' onclick='displayOption2Helper(" +
        '"' + dicthw + '"' + "," + index + "," + nresults + ");'>" +
    "<span" + classattr + ">" + dicthwoutput + "</span></a>";
   htmlarr.push('<li style="display:inline; padding:5px;" >' +x + '</li>');
  });
  htmlarr.push('</ol>')
 }

 var html = htmlarr.join("\n");
 $('#simpleinfo').html(html);
 if (dicthwfirst != null) {
  displayOption2Helper(dicthwfirst,0,nresults);
 }
}; // displayOption2


/* ----------------------------------------------------------
 $("#key")
*/
/* Deactivate previous keypress simple
 $('#key').keypress(function (e) {
  if(e.which == 13)  // the enter key code
   {e.preventDefault();
    listDisplay();
   }
 }); // end keypress
*/
$("#key").keypress(function (e) {
 var key = e.which;
 if(key == 13) {  // the enter key code
   var inputval = $('#input').val();
    if(inputval != 'simple') {
     //console.log(' keypress running listDisplay');
     listDisplay();
    } else {
     //otherwise, simple
     //console.log(' keypress running simpleDisplay');
     simpleDisplay();
    }
    return false; // deactivate 'normal' return key functionality in JS.
  }
});   

 //citation AUTOCOMPLETE doesn't work with 'simple'. 
   //Deactivate it completely
// This is based on the example at
// https://jqueryui.com/autocomplete/#remote-jsonp
// and is required to avoid cross-domain problems.  The server program also
// requires some code for this purpose.

$("#key").autocomplete({
  source: function(request,response) {
   $.ajax({
   url:"../../getsuggest.php",
   datatype:"jsonp",
   data: {
    //q: request.term
    term: request.term,
    dict: $('#dict').val(),
    input: $('#input').val()
   },
   success: function(data) {
    response(data);  // 'response' is passed in as source argument
   }
   }); // ajax
  },
  delay : 500, // 500 ms delay
  minLength : 2, // user must type at least 2 characters
  select: function(event,ui) {
   if (ui.item) {
   $("#key").val(ui.item.value);
    listDisplay();
   }
  },
  autoFocus: true,
 }); //key-autocomplete
cookieUpdate(false);  // for initializing cookie
changeActions();  // initialize now that #dict is set.

/* Functions to get parameters from php
*/

 phpinit_helper = function(name,val){
  if (val == ''){
   //console.log("phpinit_helper:  #",name,"is blank. Not changed");
   return;
  }
  if (name == 'accent') { //val should be yes or no. Case not important
   val = val.toLowerCase();
  }
  /* 01-03-2018 */
  if (val == 'iast') {val = 'roman';}
  $('#' + name).val(val);
  //console.log("phpinit_helper: change #",name,"to",val);
  cookieUpdate(true);  // initialize dom values from cookie values
  //changeActions();  // initialize now that #dict, etc are set.
 };
 phpinit = function() {
  var names = ['key','dict','input','input_simple','output','accent'];
  var phpvals=[ // same order as names
  "<?php echo $phpvals['key']?>",
  "<?php echo $phpvals['dict']?>",
  "<?php echo $phpvals['input']?>",
  "<?php echo $phpvals['input_simple']?>",
  "<?php echo $phpvals['output']?>",
  "<?php echo $phpvals['accent']?>"];
  var i,name,phpval;
  for(i=0;i<names.length;i++) {
   phpinit_helper(names[i],phpvals[i]);
  }
 };

/* ----------------------------------------------------------
 Preliminary functions and methods defined.  Now do startup
 actions.
*/
cookieUpdate(false);  // initialize dom values from cookie values
phpinit();

changeActions();  // initialize now that #dict, etc are set.
/* perhaps better to get $phpvals via Javascript parsing ? 
Also cf. sitepoint.com/get-url-parameters-with-javascript/
*/
/*
displayURL = function() {
 console.log("Page location is ",window.location.href);
 console.log("Page hostname is ", window.location.hostname);
 console.log("Page path is " ,window.location.pathname);

};

displayURL();
*/
  // If key is provided, generate display for it
  if($('#key').val() != '') {
   var inputval = $('#input').val();
   if (inputval != 'simple') {
    listDisplay();
   }else {
    simpleDisplay();
   }
  }

}); // end ready
</script>

</head>
<body>
 <div id="logo">
<?php
 if ($dictinfowhich == "cologne") {
     echo '<a href="/">' . "\n";
 } else { // xampp.  go back to Cologne
     echo '<a href="//sanskrit-lexicon.uni-koeln.de/">' . "\n";
 }
?>
      <img id="unilogo" src="//sanskrit-lexicon.uni-koeln.de/images/cologne_univ_seal.gif"
           alt="University of Cologne" width="60" height="60" 
           title="Cologne Sanskrit Lexicon"/>
      </a>
 </div>

<table id="preferences">
<tr><td>
 <div id="dictionary">
 <label for="dictcode">dictionary</label>
 <input type="text" name="dictcode" size="4" id="dict" value="" />
 </div>

</td><td>
 <div id="inputdiv">
  <label for="input">input</label>
  <select name="input" id="input">
   <option value='hk'>HK</option>
   <option value='slp1'>SLP1</option>
   <option value='itrans'>ITRANS</option>
   <option value='deva'>Devanagari</option>
   <option value='roman'>IAST</option>
   <option value='simple' selected='selected'>simple</option>
  </select>
 </div>
</td><td>
 <div id="outputdiv">
  <label for="output">output</label>
  <select name="output" id="output">
   <option value='deva'>Devanagari</option>
   <option value='hk'>HK</option>
   <option value='slp1'>SLP1</option>
   <option value='itrans'>ITRANS</option>
   <option value='roman' selected='selected'>IAST</option>
  </select>
 </div>
</td><td>
 <div id="accentdiv">  <!-- possibly should be per dictionary -->
  <label for="accent">accent?</label>
 <select name="accent" id="accent">
  <option value="yes">Show</option>
  <option value="no" selected="selected">Hide</option>
 </select>
 </div>
</td><td>
 <div id="input_simplediv">
  <label for="input_simple">input_simple</label>
  <select name="input_simple" id="input_simple">
   <option value='hk'>HK</option>
   <option value='slp1'>SLP1</option>
   <option value='itrans'>ITRANS</option>
   <option value='deva'>Devanagari</option>
   <option value='roman'>IAST</option>
   <option value='default' selected='selected'>default</option>
  </select>
 </div>
</td></tr>
</table> <!-- preferences -->
 <div id="citationdiv">
  citation:&nbsp;
  <input type="text" name="key" size="20" id="key" value="" style="height:1.4em;"/>
  <a id="correction" href="#" target="Corrections">Corrections</a>
 </div>
  
 <div id="simpleinfo"></div>
 <div id="disp">
  <!-- Requesting data will change the src attribute of this iframe -->
  <iframe id="dataframe">  
   <p>Your browser does not support iframes.</p>
  </iframe>
 </div>
<?php
if ($dictinfowhich == "cologne") {
 echo '<script src="/js/piwik_analytics.js"></script>' . "\n";
}
?>
</body>
</html>
