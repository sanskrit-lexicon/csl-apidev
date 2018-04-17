/* apidev/listview.js 
 June 2015
*/

jQuery(document).ready(function(){ 
 win_ls=null;
});
function getWordAlt_keyboard(keyserver) {
    getWord_keyboard("NO",keyserver);  //chg1
}
function old_getWordAlt_keyboard(keyserver) {
 // might be a problem if view differs from server/display
//    document.getElementById("key1").value = keyserver; //chg1
// Called only by the onclick event of the words in the list
    getWord_keyboard("NO",keyserver);  //chg1
}
function getWordlist_keyboard() {
    var url =   keyboard_parms(false,true);
//    alert("getWordlist_keyboard: url="+url);
    getWordlist_main(url);
}
function getWordlistUp_keyboard(keyserver) {
    var url =    keyboard_parms(keyserver,true) + 
              "&direction=UP";
    getWordlist_main(url);
}
function getWordlistDown_keyboard(keyserver) {
    var url =  keyboard_parms(keyserver,true) + 
              "&direction=DOWN";
    getWordlist_main(url);
}
function getWord_keyboard(listFlag,keyserver) {
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
function readCookie(cname) { 
 // code from w3schools.com/js/js_cookies.asp
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
    }
    return ""; 
}
function getWord_main(url) {
  console.log('getWord_main:',url);
    jQuery.ajax({
	url:url,
	type:"GET",
        success: function(data,textStatus,jqXHR) {
	    jQuery("#disp").html(data);
            /*
	    var filter = readCookie("serverOptions");
	    if (filter == 'deva') {
                modifyDeva();
	    }
            */
	},
	error:function(jqXHR, textStatus, errorThrown) {
	    alert("Error: " + textStatus);
	}
    });
}
function getWordlist_main(url) {
  console.log('getWordlist_main:',url);
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

function keyboard_parms(keyserver,listurlFlag) {  
    var word,transLit,filter,accent,dict;
    if (keyserver) {
     // 'keyserver' is a word passed as a parameter when the user
     //  clicks on a 'list' word.  It is in slp1
     // The parameters retrieved from cookies are
     word = keyserver;
     transLit = readCookie("transLit");
     filter = readCookie("filter");
     accent = readCookie("accent");
     dict = readCookie("dict");
    }else {
	alert('keyboard_parms ERROR: no keyserver variable');
    }
    var url;
    if (listurlFlag) {
     url = "listhier.php";
    }else { 
     url = "disphier.php";
    }
   //var accent = document.getElementById("accent").value;
   var accent = readCookie("accent");
   var ans = 
   url + 
   "?key=" +escape(word) + 
   "&transLit=" + escape(transLit) +
   "&filter=" + escape(filter) +
   "&accent=" + escape(accent) +
   "&dict=" + escape(dict);
   return ans;
}


function winls(url,anchor) {
// Called by a link made by monierdisp.php
 var url1 = '../sqlite/'+url+'#'+anchor;  //Corrected Oct 10, 2014
 win_ls = window.open(url1,
    "winls", "width=520,height=210,scrollbars=yes");
 win_ls.focus();
}
function getFontClass() {
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
function modifyDeva() {
    var fontclass = getFontClass();
    var useragent = navigator.userAgent;
    if (!useragent) {useragent='';}
    if ((useragent.match(/Windows/i)) || (useragent.match(/Macintosh/i))){
  jQuery(".sdata").removeClass("sdata").addClass(fontclass);
 }else {
	//alert('useragent not "Windows"=' + useragent);
 }
}
