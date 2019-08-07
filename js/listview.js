/* apidev/listview.js 
 June 2015
 Aug 17, 2015
*/

jQuery(document).ready(function(){ 
 win_ls=null;
});
function getWordAlt_keyboard(keyserver) {
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
  //console.log('getWord_main:',url);
  
     jQuery.ajax({
	url:url,
	type:"GET",
        success: function(data,textStatus,jqXHR) {
	    //jQuery("#disp").html(data);
	    jQuery("#CologneListview .dispdiv").html(data);
            /*
	    var output = readCookie("serverOptions");
	    if (output == 'deva') {
                modifyDeva();
	    }
            */
	},
	error:function(jqXHR, textStatus, errorThrown) {
	    alert("Error: " + textStatus);
	}
    });
}
function fixedEncodeURIComponent (str) {
  return encodeURIComponent(str).replace(/[!'()]/g, escape).replace(/\*/g, "%2A");
}
function getWordlist_main(url) {  
    jQuery.ajax({
	url:url,
	type:"GET",
        success: function(data,textStatus,jqXHR) {
	    //jQuery("#displist").html(data);
	    jQuery("#CologneListview .dispdivlist").html(data);
	},
	error:function(jqXHR, textStatus, errorThrown) {
	    alert("Error: " + textStatus);
	}
    });
 
}

function keyboard_parms(keyserver,listurlFlag) {  
    var word,input,output,accent,dict;
    if (keyserver) {
     // 'keyserver' is a word passed as a parameter when the user
     //  clicks on a 'list' word.  It is in slp1
     // The parameters retrieved from cookies are
     word = keyserver;
     //input = readCookie("input");  
     // By listhier logic, this use of input is always slp1
     input = 'slp1';
     output = readCookie("output");
     accent = readCookie("accent");
     dict = readCookie("dict");
    }else {
	alert('keyboard_parms ERROR: no keyserver variable');
    }
    var url;
    if (listurlFlag) {
     url = "listhier.php";
    }else { 
     url = "getword.php"; 
    }
   var accent = readCookie("accent");

   var ans = 
   url + 
   "?key=" +escape(word)+ 
   "&input=" + escape(input) +
   "&output=" + escape(output) +
   "&accent=" + escape(accent) +
   "&dict=" + escape(dict);
   return ans;
}
function listhier_lnum(lnum,link) {  
    var word,input,output,accent,dict;
     //input = readCookie("input");  
     // By listhier logic, this use of input is always slp1
     input = 'slp1';
     output = readCookie("output");
     accent = readCookie("accent");
     dict = readCookie("dict");
    var urlbase= "listhier.php";
    
   var accent = readCookie("accent");
   var url = 
   urlbase + 
   "?lnum=" +escape(lnum)+ 
   "&input=" + escape(input) +
   "&output=" + escape(output) +
   "&accent=" + escape(accent) +
   "&dict=" + escape(dict);
   //var $this =$(this);  // the link in 'disp' that was clicked
    getWordlist_link(url,link);
    //console.log('listhier_lnum url=',url);
}
function getWordlist_link(url,$link) {  
    jQuery.ajax({
	url:url,
	type:"GET",
        success: function(data,textStatus,jqXHR) {
            jQuery("#CologneListview .dispdivlist").html(data);

            
            adjust_main_links($link); // 
	},
	error:function(jqXHR, textStatus, errorThrown) {
	    alert("Error: " + textStatus);
	}
    });
}
function adjust_main_links($link) {
    jQuery('#CologneListview .listlink').removeClass('listlinkCurrent');
    jQuery($link).addClass('listlinkCurrent');
}
