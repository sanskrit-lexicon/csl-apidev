<?php
 /* Same as list-0.2s_xampp.html, but accepts some or all inputs as
   $_REQUEST parameters (i.e. either 'GET' or 'POST')
 */
// Report all errors except E_NOTICE  (also E_WARNING?)
error_reporting(E_ALL & ~E_NOTICE);
/* See phpinit
 $dict = $_REQUEST['dict'];
 $key = $_REQUEST['key'];
 $accent= $_REQUEST['accent'];
 $input = $_REQUEST['input'];
 $output = $_REQUEST['output'];
*/
$keys = array('key','dict','input','output','accent');
$phpvals = array();
for($i=0;$i<count($keys);$i++) {
 $key=$keys[$i];
 $phpvals[$key] = $_REQUEST[$key];
}
?>
<!DOCTYPE html> <!-- html5 -->
<html>
<head>
<META charset="UTF-8">
<title>Sanskrit simple search</title>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.css">
<!-- links to jquery, using CDNs -->
<script type="text/javascript" src="//code.jquery.com/jquery-2.1.4.min.js"></script>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<!-- jquery-ui is used -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<script type="text/javascript" src="../../sample/dictnames.js"></script>
<script type="text/javascript" src="../../sample/cookieUpdate.js"></script>

<style>
body {
 color: black; background-color:#DBE4ED;
 /*font-size: 14pt; */
}
@font-face { 
 src: url(../../fonts/siddhanta.ttf);   /* location specific */
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
/*
#hwlinksmenu {
 height: 25px;line-height:25px;
 margin-top: 10px; margin-bottom:10px;
}
*/
</style>
<script> 
 // Jquery
$(document).ready(function() {

 $('#output,#accent').change(function(event) {
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
 };

 listDisplay = function (keyin) {
  var key;
  if (keyin == undefined) {
   key = $('#key').val();  
  } else {
   key = keyin;
  }
  var dict = $('#dict').val();
  var input = $('#input').val();
  if (input == 'simple') {
   input = 'slp1';  // used when keyin is present.
  }
  //console.log('listDisplay: keyin=',keyin,'input=',input);
  var output = $('#output').val();
  var accent = $('#accent').val();
  // TODO: check for valid inputs before ajax call
  var urlbase="../../listview.php";
  var url =  urlbase +  
   "?key=" +escape(key) + 
   "&output=" +escape(output) +
   "&dict=" + escape(dict) +
   "&accent=" + escape(accent) +
   "&input=" + escape(input) +
   "&dev=yes";
    //jQuery("#disp").html(""); // clear output
  //console.log('listDisplay: url=',url);
  jQuery("#dataframe").attr("src",url);
    
 }; // listDisplay
 
cookieUpdate = CologneDisplays.dictionaries.cookieUpdate;

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
   //console.log('blur: this.val=',$(this).val());
   //console.log('blur: dict.val=',$('#dict').val());
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
  //console.log('dict change: dict=',$('#dict').val());
  cookieUpdate(true);   
  $('#simpleinfo').html("");
  changeActions();
 });

simpleFunction = function(){
 // $('#simpleinfo').html("<p>This not yet functional</p>");
  var find_word = $('#key').val();
  find_word = find_word.trim();
  var test ={}; // Javascript object to pass as parameters
  //console.log('test: find_word=',find_word);
  test.key = find_word;
  // 04-18/2018. change from v1.0d to v1.0
  // Currenlty getword_list_1.0.php same in both locations.
  test.url = "../../simple-search/v1.0/getword_list_1.0.php";
  //console.log('simpleFunction test.url=',test.url);
  test.input = 'hk';
  test.output = $('#output').val(); //'deva';
  test.dict = $('#dict').val();
  test.dev = 'yes';
  //console.log('list-0.2s.html calling url=',test.url);
  //$('#outputdiv').html("<p>Accessing server for alternates of " + test.key + "  ...</p>");
  // Blank out result area
  $('#simpleinfo').html(" working...")
  $.ajax({
   url: test.url,
   cache: false,
   method: "POST",
   data: {
    input:test.input, // not currently used
    output:test.output, // not currently used
    dict:test.dict, // not currently used
    key:test.key,
    dev:test.dev
   }
  }) // end of $.ajax
  .done(displayOption2)
  .fail(function( jqXHR, textStatus, errorThrown){
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
//console.log('displayOption2Helper=',displayOption2Helper);

displayOption2 = function(json) {
   //console.log('json = ',json);
 //console.log('outputparm =',json['output']);
 var results = json['result'];
 var dict = json['dict'];
 var nresults = results.length;
 var nfound = nresults;
 var htmlarr = [];
 var dicthwfirst = null;
 //console.log('nfound=',nfound);
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
   //console.log('dicthw:',dicthw,dicthwFlag);
   if (index == 0) {
    dicthwfirst = dicthw;
   }
   dicthwoutput = result['dicthwoutput'];
   if (dicthwFlag) { 
    // 11-01-2017. Bold when this is user input
    dicthwoutput = "<strong>" + dicthwoutput + "</strong>";
    //console.log('dicthwoutput =',dicthwoutput);
   }
   var classattr = '';
   if (json['output'] == 'deva') {
    classattr = ' class="sdata"'; // control font for Devanagari
   }
   //console.log("classattr=",classattr);
   var x = "<a class='hwlinks' id='hwlink_" + 
        index + "' onclick='displayOption2Helper(" +
        '"' + dicthw + '"' + "," + index + "," + nresults + ");'>" +
    "<span" + classattr + ">" + dicthwoutput + "</span></a>";
   //console.log('x=',x);
   htmlarr.push('<li style="display:inline; padding:5px;" >' +x + '</li>');
  });
  htmlarr.push('</ol>')
 }

 var html = htmlarr.join("\n");
 $('#simpleinfo').html(html);
 if (dicthwfirst != null) {
  //listDisplay(dicthwfirst);
  displayOption2Helper(dicthwfirst,0,nresults);
 }
}; // displayOption2
//console.log('displayOption2=',displayOption2);


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
   //console.log('inputval=',inputval);
    if(inputval != 'simple') {
     //console.log(' keypress running listDisplay');
     listDisplay();
    } else {
     //otherwise, simple
     //console.log(' keypress running simpleFunction');
     simpleFunction();
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
// new for php version
cookieUpdate(false);  // for initializing cookie
changeActions();  // initialize now that #dict is set.

/* Functions to get parameters from php
*/

 phpinit_helper = function(name,val){
  if (val == ''){return;}
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
  var names = ['key','dict','input','output','accent'];
  var phpvals=[ // same order as names
  "<?php echo $phpvals['key']?>",
  "<?php echo $phpvals['dict']?>",
  "<?php echo $phpvals['input']?>",
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
  // If key is provided, generate display for it
  if($('#key').val() != '') {
   var inputval = $('#input').val();
   if (inputval != 'simple') {
    listDisplay();
   }else {
    simpleFunction();
   }
  }

}); // end ready
 </script>
<script> // see MWScan/2014/web/webtcdev/main_webtc.js
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
 <div id="dictionary">
 <label for="dictcode">dictionary</label>
 <input type="text" name="dictcode" size="4" id="dict" value="" />
 </div>

</td><td>
 <div id="inputdiv">
  <label for="input">input</label>
  <select name="input" id="input">
   <option value='hk'>HK <!--Kyoto-Harvard--></option>
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
   <option value='hk'>HK <!--Kyoto-Harvard--></option>
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
</td></tr>
</table> <!-- preferences -->
 <div id="citationdiv">
  citation:&nbsp;
  <input type="text" name="key" size="20" id="key" value="" style="height:1.4em;"/>
  <!--<input type="button" id="correction" value="Corrections" /> -->
  <!-- href is set in change function on #dict -->
  <a id="correction" href="#" target="Corrections">Corrections</a>
 </div>
  
 <div id="simpleinfo"></div>
 <div id="disp">
  <!-- Requesting data will change the src attribute of this iframe -->
  <iframe id="dataframe">  
   <p>Your browser does not support iframes.</p>
  </iframe>
 </div>
<script>
</script>
<script src="//www.sanskrit-lexicon.uni-koeln.de/js/piwik_analytics.js"></script> 
</body>
</html>
