<!DOCTYPE html>
<html>
 <head>
  <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
  <title>Monier-Williams Dictionary</title>
  <link rel="stylesheet" type="text/css" href="main.css" />
  <link rel="stylesheet" type="text/css" href="keyboard.css"/>
  <script src="../js/jquery.min.js"></script>
  <script src="transcoderjs/transcoder3.js"> </script>
  <script src="transcoderjs/transcoderJson.js"> </script>

  <script src="transcoderfield_VKI.js"> </script>
  <script src="keyboard.js"></script>
  <script src="main.js"> </script>

 </head>
 <body>
 <div id="dictid">
     <a href="http://www.sanskrit-lexicon.uni-koeln.de/">
     <img id="unilogo" src="../images/cologne_univ_seal.gif"
           alt="University of Cologne" width="60" height="60"
	  title="Cologne Sanskrit-Lexicon"/>
     </a>
     <span style='font-size:larger'>Monier-Williams Dictionary</span>
 </div>
 <div id="dictnav">
 <ul class="nav"
   <li class="nav">
     <a class="nav" href="help/help.html" target="output">Help</a>
   </li>

   <li class="nav">
    &nbsp;
    <a class="nav" href="http://www.sanskrit-lexicon.uni-koeln.de/index.html" target="_top">Home</a>
   </li> 
 </ul>
 </div>


<div id="preferences">
<input type='button' id='preferenceBtn'  value='Preferences' style='position:relative; bottom: 5px;' />
&nbsp;&nbsp;

<textarea id='key1' name='TEXTAREA'  rows='1' cols='20'></textarea>

&nbsp;

<a href="/php/correction_form.php?dict=MW" target="Corrections">Corrections</a>

&nbsp; &nbsp;
<select name="accent" id="accent" onchange="transcoderChange();"> <!--2015-->
 <option value="yes">Show Accents</option>
 <option value="no" selected="selected">Ignore Accents</option>
</select>



</div>
 <!-- Requesting data will change the src attribute of this iframe -->
 <iframe id="dataframe">  
  <p>Your browser does not support iframes.</p>
 </iframe>
<script src="/js/piwik_analytics.js"></script>
</body>
</html>
