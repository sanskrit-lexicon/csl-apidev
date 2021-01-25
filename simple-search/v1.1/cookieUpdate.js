/* Uses jQuery.cookie */
CologneDisplays.dictionaries.cookieUpdate = function(flag) {
 // Cookie for holding input, output, accent, dict values;
 // When flag is true, update cookies from corresponding dom values
 // When flag is false, initialize dom values from cookie values,
 //  but use default values if cookie values not present.
 // Add 'input_simple'
 var cookieNames = ['input','output','accent','dict','input_simple'];
 var domids = ['#input','#output','#accent','#dict','#input_simple'];
 var cookieOptions = {expires: 365, path:'/'}; // 365 days
 var i,cookieName,cookieValue,domid;
 var cookieDefaultValues = ['hk','deva','no','mw','default'];
 if (flag) { // set values of cookies acc. to 'value' of corresponding ids
  for(i=0;i<cookieNames.length;i++) {
   cookieName=cookieNames[i];
   domid=domids[i];
   cookieValue=$(domid).val();
   //console.log('cookieUpdate: ',cookieName,domid,cookieValue);
   if((cookieValue === 'null')|| (cookieValue === null) || (cookieValue === '')) {  
    cookieValue= cookieDefaultValues[i]; // Use default value
    $.cookie(cookieName,cookieValue,cookieOptions); // and set cook
    $(domid).val(cookieValue);
    //console.log('Reseting cookie:',cookieName,domid,cookieValue);
   } else {
   // set dom value
   $.cookie(cookieName,cookieValue,cookieOptions);
   }
  }
  return;
 }
 // When flag is false. For initializing (a) cookies, and (b) dom values
 for(i=0;i<cookieNames.length;i++) {
  cookieName=cookieNames[i];
  domid=domids[i];
  cookieValue = $.cookie(cookieName); // old value of cookie
  
  // When not defined, cookieValue seems to be string 'null', not
  // JS object null.  12-10-2020
  //if(! cookieValue) {  
  if((cookieValue === 'null')|| (cookieValue === null) || (cookieValue === '')) {  
   cookieValue= cookieDefaultValues[i]; // Use default value
   $.cookie(cookieName,cookieValue,cookieOptions); // and set cook
  }
  // set dom value
  $(domid).val(cookieValue);
 }
};