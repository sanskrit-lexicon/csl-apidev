import { html, css, LitElement,unsafeHTML } from './lit-element-2.3.1.js';
import {getwordStyles} from './getword_styles.js';

class cslGetword02 extends LitElement {
  static get styles() {
   return [
    getwordStyles
   ];
  }
  
  static get properties() {
    return {
      dict: { type: String },
      key:  { type: String },
      input: { type: String },
      output: { type: String },
      accent: { type: String},
      result: { type: String }
    };
  }

  constructor() {
    super();
    this.dict = 'md';
    this.key  = 'guru';
    this.input = 'slp1';
    this.output = 'deva';  // fixed
    this.accent = 'yes';
    this.result = '... working ...';
  }
  urlbaseF = function () {
  return css`https://sanskrit-lexicon.uni-koeln.de/scans`;
  let origin = window.location.origin;  
  if (origin.indexOf("sanskrit-lexicon.uni-koeln.de") >= 0)  {
   return css`https://sanskrit-lexicon.uni-koeln.de/scans`;
  }else {
   //return origin + "/cologne";
   return css`http://localhost/cologne`;
  }
 }

  // Don't use connectedCallback() since it can't be async
  //async firstUpdated() {
  async updated() {
  const urlbase = this.urlbaseF();
  //console.log('csl-getword02: urlbase=',urlbase);
  let url_apidev = `${urlbase}/csl-apidev`;
  url_apidev = '../' ;  // when running app in subfolder of csl-apidev
  //const baseurl = 'https://sanskrit-lexicon.uni-koeln.de/scans/csl-apidev/getword.php';
  const baseurl = `${url_apidev}/getword.php`;
    const url = `${baseurl}?dict=${this.dict}&key=${this.key}&input=${this.input}&output=${this.output}&dispopt=3&accent=${this.accent}`
    //console.log('updated. url=',url);
    await fetch(url)
      .then(r => r.text())
      .then(async data => {
        //console.log('csl-getword02: updated result=','found'); //data);
        this.result = data;
      });
  }

  render() {
   
    const result=`${this.result}`;
    return html`
      <div id="CologneBasic">
        ${unsafeHTML(result)}
        
      </div>
    `;
  }
}

customElements.define('csl-getword', cslGetword02);
