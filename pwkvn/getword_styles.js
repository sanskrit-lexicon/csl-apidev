import { css, unsafeCSS} from './lit-element-2.3.1.js';
/* Adaptaion of csl-apidev/css/basic.css. Almost identical.
*/
/*
const urlbaseF = function () {
  let origin = window.location.origin;  
  if (origin.indexOf("sanskrit-lexicon.uni-koeln.de") >= 0)  {
   return css`https://sanskrit-lexicon.uni-koeln.de/scans`;
  }else {
   //return origin + "/cologne";
   return css`http://localhost/cologne`;
  }
 }
*/
const urlbaseF = function () {
  let origin = window.location.origin;  
   return css`https://sanskrit-lexicon.uni-koeln.de/scans`;
  if (origin.indexOf("sanskrit-lexicon.uni-koeln.de") >= 0)  {
   return css`https://sanskrit-lexicon.uni-koeln.de/scans`;
  }else {
   //return origin + "/cologne";
   return css`http://localhost/cologne`;
  }
 }
const urlbase = urlbaseF();
const url_apidev = css`${urlbase}/csl-apidev`;
const fontUrl = css`${url_apidev}/fonts`;
//console.log('getword_styles.js. fontUrl=',fontUrl);

export const getwordStyles = css`
/* Web-font locations
siddhanta : devanagari text
oldstandard: for normal text in pwg
oldstandarditalic : same as above?
charterindocapital: for 'ls' text in pwg
       Jul 18, 2015. font family for capitalization, only SNP 
NOTE:  font-faces are NOT loaded when these are in the shadow dom.
   These font-face statements must be in the regular ('light') dom.
Ref: https://medium.com/rate-engineering/winning-the-war-of-css-conflicts-through-the-shadow-dom-de6c797b5cba
@font-face { 
 src: url(${fontUrl}/siddhanta.ttf);
 font-family: siddhanta_deva;
}
@font-face { 
 src: url(${fontUrl}/oldstandard.otf);
 font-family: oldstandard;
}
@font-face { 
 src: url(${fontUrl}/oldstandarditalic.otf);
 font-family: oldstandard_i;
}
@font-face {
 src: url(${fontUrl}/CharterIndoCapital.otf);
 font-family: charterindocapital;
}
*/
/* Aug 15, 2015
  Make everything a descendant of #CologneBasic.
   Jul 20, 2020.  Adapted for web-components
*/
#CologneBasic {
	color: black; background-color: white; /*#DBE4ED; */
  	font-size: 14pt;  
}
#CologneBasic td {line-height: 130%; /*150%;*/}
#CologneBasic .greek {
 font-family: Gentium, Palatino Linotype, Arial Unicode MS;
 font-size: 12pt;
}
#CologneBasic {padding-right: 25px;}
#CologneBasic .g {font-size:smaller;font-style:italic;}
#CologneBasic .lang {font-size:smaller;font-style:italic;}
#CologneBasic .hrefdata {font-size:smaller;}
#CologneBasic .lnum {font-size:smaller;}
/*.sdata {color:black;font-size:larger;} */ /* for devanagari */

/*#CologneBasic .sdata {color:teal;}*/
/*#CologneBasic .sdatadeva {color:teal;font-size:larger;}*/
/* Added '.sdata' in next Aug 20, 2015.  
 '.sdatadeva' is not used
*/
#CologneBasic .sdata_siddhanta,.sdata { 
 color:teal;  /* was blue */
 /*font-size:larger;*/
 font-family: siddhanta_deva;
}

#CologneBasic .asdata {color:blue;font-size:larger;}
#CologneBasic .pb {font-size:smaller; }
#CologneBasic .hom {color:red;}
#CologneBasic .footnote {font-size:smaller;}
#CologneBasic .ls {color:gray; font-size:smaller;}
#CologneBasic .gram {font-weight:bold;}
#CologneBasic .divm {font-weight:bold;}
#CologneBasic .wide {font-stretch:wider;}

#CologneBasic p {
	padding-left: 15pt;
	padding-right: 5px; /*15pt; */
}

#CologneBasic table.display {
  width: 100%;
  margin-left:     5px;
  margin-right:    15px; /*5px;*/
  
  margin-top: 10px; /* 20px; Dec 5, 2013 */
  font-size: 14px;  
  padding-right: 15px ;/*15px;*/ /* 04-18-2017 For AP display, with indenting */
}
#CologneBasic td, th {
  padding-left,padding-right:         15px;
  padding-top: 5px;
  text-align:      left;
  display: block; /* added to basic.css */
}

#CologneBasic plex {
	color: black;
	background-color: #36648b;
	/*font-family: verdana,arial,helvetica,sansserif; */ /*Dec 5, 2013*/
	font-size: 12pt;
}

#CologneBasic h1 {
	font-size: 24pt;
}

#CologneBasic h2 {
	font-size: 20pt;
	padding-left: 15pt;
	padding-top: 20pt;
}

#CologneBasic h3 {
	font-size: 18pt;
	padding-left: 30pt;
	padding-top: 20pt;
}

#CologneBasic a:link {
        color: black;
	/* color: #00008b; */
}

#CologneBasic a:visited {
	color: #36648b;
}

#CologneBasic a:active {
	color: #4876ff;
}

#CologneBasic a:hover {
	background-color: #ffffcc;
	color: #000033;
}
#CologneBasic .botcap {
color:gray; 
font-family:charterindocapital;
}
#CologneBasic .cssshade {
 background-color:#EAEAEA; /* light gray */
}
#CologneBasic .sdata {
 font-family: siddhanta_deva;
 /*color:red;*/
}
#CologneBasic .displist {
 font-family: siddhanta_deva;
 /*color:red;*/
}
`;
