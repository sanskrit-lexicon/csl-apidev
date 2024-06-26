/* Uses jQuery.cookie */
CologneDisplays.dictionaries.cookieUpdate = function(flag) {
 // Cookie for holding input, output, accent, dict1 values;
 // When flag is true, update cookies from corresponding dom values
 // When flag is false, initialize dom values from cookie values,
 //  but use default values if cookie values not present.
    var cookieNames = ['input','output','accent','dict1','dict2'];
 var domids = ['#input','#output','#accent','#dict1','#dict2'];
 var cookieOptions = {expires: 365, path:'/'}; // 365 days
 var i,cookieName,cookieValue,domid;
   // console.log('cookieUpdate: flag=',flag); 
 if (flag) { // set values of cookies acc. to 'value' of corresponding ids
  for(i=0;i<cookieNames.length;i++) {
   cookieName=cookieNames[i];
   domid=domids[i];
   cookieValue=$(domid).val();
   $.cookie(cookieName,cookieValue,cookieOptions);
  }
  return;
 }
 // When flag is false. For initializing (a) cookies, and (b) dom values
    var cookieDefaultValues = ['hk','deva','no','mw','mw'];
 for(i=0;i<cookieNames.length;i++) {
  cookieName=cookieNames[i];
  domid=domids[i];
  cookieValue = $.cookie(cookieName); // old value of cookie
  //console.log(i,cookieName,domid,cookieValue,cookieDefaultValues[i]);
  if(! cookieValue) { // cookie not defined. 
   cookieValue= cookieDefaultValues[i]; // Use default value
   $.cookie(cookieName,cookieValue,cookieOptions); // and set cook
  }
  // set dom value
  $(domid).val(cookieValue);
 }
};