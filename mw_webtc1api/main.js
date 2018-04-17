// based on monier/alt-main.js
// Oct 11, 2012. Revised to use jQuery for Ajax and DOM
// Removed 'queryInputChar' function (formerly used as keydown handler)
// Removed standard_input
// Oct 15, 2012.  Modified to work with transcoderjs.
// Mar 17, 2015.  Modified to display results in an iframe
jQuery(document).ready(function(){ 
 theTranscoderField = new TranscoderField('key1',keydown_return);
 win_ls=null;
 VKI.transcoderInit();
 jQuery('#disp').html("");
});
VKI.transcoderInit = function() {
 VKI.transcoderField = theTranscoderField; // add new attribute to VKI.
 jQuery('#key1').attr('class','keyboardInput'); // needs to precede VKI.load!
 VKI.load();
 transcoderChange(); // install the in/out preferences initialized by VKI.load
 jQuery('#disp').html("");
 //jQuery('#key1').keydown(keyboard_HandleChar);
 jQuery('#preferenceBtn').click(preferenceBtnFcn);
};

function preferenceBtnFcn(event) {
    showPreferences(); // from keyboard.js
}
function transcoderChange() {
 // Get inval/outval parms from VKI.state via the cookies
 // Note that 'inputType' should be 'phonetic' for the logic to work.
 // At the moment (Oct 15, 2012) the 
 // Note of Oct 16, 2012.  This function is called by
 // (a) the 'ready' function
 // (b) the 'okBtn' function in preferences.htm
  var inputType = readCookie("inputType"); 
  var phoneticInput = readCookie("phoneticInput"); 
  var viewAs = readCookie("viewAs");
 /*
  var inputType = VKI.state.inputType;
  var phoneticInput = VKI.state.phoneticInput;
  var viewAs = VKI.state.viewAs;
 */
  var inval = phoneticInput;
  if (inval == 'it') {inval = 'itrans';}
  var outval = viewAs;
  if ((inputType == 'phonetic') && (outval == 'phonetic')) {
      outval = inval;
  }
  //console.log('transcoderChange: ',inputType,viewAs,inval,outval);
  theTranscoderField.transCoderChange(inval,outval);
  // Mar 17, 2015 : accents. createCookie is in keyboard.js
  accent = document.getElementById("accent").value
  //createCookie("accent",accent);
}

function keydown_return() {
 // console.log('dbg:keydown_return');
 //getWord_keyboard(false,false); 
    var word,inputType,unicodeInput,phoneticInput,viewAs,serverOptions,url,accent;
 //var url =  keyboard_parms(keyserver,false); //chg1
     word = document.getElementById("key1").value;
     inputType = readCookie("inputType");
     unicodeInput = readCookie("unicodeInput");
     phoneticInput = readCookie("phoneticInput");
     viewAs = readCookie("viewAs");
     serverOptions = readCookie("serverOptions");
    accent = readCookie("accent");
    url = "api_listview.php"+
   "?key=" +escape(word) + 
   "&keyboard=" +escape("yes") +
   "&inputType=" +escape(inputType) +
   "&unicodeInput=" +escape(unicodeInput) +
   "&phoneticInput=" +escape(phoneticInput) +
   "&serverOptions=" +escape(serverOptions) +
   "&accent=" + escape(accent) +
   "&viewAs=" + escape(viewAs);
    jQuery("#dataframe").attr("src",url);
   /*
    var data ="<iframe src=\"" + url +"\"" +
     "
     "><p>Your browser does not support iframes.</p></iframe>";
    jQuery("#dataframe").html(data);
*/
}


function unused_getWordAlt_keyboard(keyserver) {
 // might be a problem if view differs from server/display
//    document.getElementById("key1").value = keyserver; //chg1
// Called only by the onclick event of the words in the list
    getWord_keyboard("NO",keyserver);  //chg1
}
function unused_getWordlist_keyboard() {
    var url =   keyboard_parms(false,true);
//    alert("getWordlist_keyboard: url="+url);
    getWordlist_main(url);
}
function unused_getWordlistUp_keyboard(keyserver) {
    var url =    keyboard_parms(keyserver,true) + 
              "&direction=UP";
    getWordlist_main(url);
}
function unused_getWordlistDown_keyboard(keyserver) {
    var url =  keyboard_parms(keyserver,true) + 
              "&direction=DOWN";
    getWordlist_main(url);
}

function unused_getWord_keyboard(listFlag,keyserver) {
   // Called from index.php (js keyboard_HandleChar) with parms false, false
    var url =  keyboard_parms(keyserver,false); //chg1
    //console.log('getWord_keyboard: url=',url);
    getWord_main(url);
    if(listFlag == "NO") {
    // getlistFlag = false; //removed Mar 16, 2015. Not used
    }else {
    // getlistFlag = true; //removed Mar 16, 2015. Not used
     getWordlist_keyboard();
    }
}
function unused_getWord_main(url) {
  //console.log('getWord_main:',url);
    jQuery.ajax({
	url:url,
	type:"GET",
        success: function(data,textStatus,jqXHR) {
	    var filter = readCookie("serverOptions");
	    jQuery("#disp").html(data);
	    if (filter == 'deva') {
                modifyDeva();
	    }
	},
	error:function(jqXHR, textStatus, errorThrown) {
	    alert("Error: " + textStatus);
	}
    });
}
function unused_getWordlist_main(url) {
    jQuery.ajax({
	url:url,
	type:"GET",
        success: function(data,textStatus,jqXHR) {
	    jQuery("#displist").html(data);
	},
	error:function(jqXHR, textStatus, errorThrown) {
	    alert("Error: " + textStatus);
	}
    });
 
}

function unused_keyboard_parms(keyserver,listurlFlag) {  
    var word,inputType,unicodeInput,phoneticInput,viewAs,serverOptions;
    if (keyserver) {
     // 'keyserver' is a word passed as a parameter when the user
     //  clicks on a 'list' word.  In this case the 'viewAs' parameter
     //  has the value of the 'serverOptions' parameter
     // readCookie is in keyboard.js
     word = keyserver;
     inputType = readCookie("inputType");
     unicodeInput = readCookie("unicodeInput");
     phoneticInput = readCookie("phoneticInput");
     viewAs = readCookie("viewAs");
     serverOptions = readCookie("serverOptions");
     viewAs = serverOptions;
    }else {
     word = document.getElementById("key1").value;
     inputType = readCookie("inputType");
     unicodeInput = readCookie("unicodeInput");
     phoneticInput = readCookie("phoneticInput");
     viewAs = readCookie("viewAs");
     serverOptions = readCookie("serverOptions");
     // serverOptions = viewAs;  // Nov. 22, 2010
    }
    var url;
    if (listurlFlag) {
     var listOptions = readCookie("listOptions");
     if (listOptions == 'hierarchical') {
  	url = "listhier.php";
     }else {
	url = "list.php"; // alphabetical
     }
    }else { // should be an error condition!
	url = "disphier.php";
    }
   //var accent = document.getElementById("accent").value;
   var accent = readCookie("accent");
   var ans = 
   url + 
   "?key=" +escape(word) + 
   "&keyboard=" +escape("yes") +
   "&inputType=" +escape(inputType) +
   "&unicodeInput=" +escape(unicodeInput) +
   "&phoneticInput=" +escape(phoneticInput) +
   "&serverOptions=" +escape(serverOptions) +
   "&accent=" + escape(accent) +
   "&viewAs=" + escape(viewAs);
    return ans;
}


function unused_winls(url,anchor) {
// Called by a link made by monierdisp.php
 var url1 = '../sqlite/'+url+'#'+anchor;  //Corrected Oct 10, 2014
 win_ls = window.open(url1,
    "winls", "width=520,height=210,scrollbars=yes");
 win_ls.focus();
}
function unused_getFontClass() {
// June 25. Modify to always use siddhanta
 //var family = document.getElementById("devafont").value;
 var family = "siddhanta";
 if (family === "system") {return "sdata_system";}
 if (family === "praja") {return "sdata_praja";}
 if (family === "oldstandard") {return "sdata_oldstandard";}   
 if (family === "sanskrit2003") {return "sdata_sanskrit2003";}   
 if (family === "siddhanta") {return "sdata_siddhanta";}   
 return "sdata";
}
function unused_modifyDeva() {
    var fontclass = getFontClass();
    var useragent = navigator.userAgent;
    if (!useragent) {useragent='';}
    if ((useragent.match(/Windows/i)) || (useragent.match(/Macintosh/i))){
  jQuery(".sdata").removeClass("sdata").addClass(fontclass);
 }else {
	//alert('useragent not "Windows"=' + useragent);
 }
}
