import { html, css, LitElement,unsafeHTML } from '../js/lit-element-2.3.1.js';
import  './csl-input.js';
import  './my-app.js';
import './csl-citation.js';
import './csl-accent.js';

// (setq js-indent-level 1)

class myApp1 extends LitElement {
  static get properties() {
    return {
     input: { type: String },
     key: { type: String },
     output: { type: String },
     accent: { type: String }
     }
  }
  constructor() {
    super();
   this.input = 'slp1';
   this.output = 'deva';
   this.key = '';
   this.accent = 'yes';
  }
  static get styles() {
    return [
    css`
.grid-container {
  display: grid;
  grid-gap: 10px;
  grid-template-columns: auto auto;
}
.grid-item1 { /* pwg */
  grid-row-start: 1;
  grid-row-end: 2;
  grid-column-start: 1;
  grid-column-end: 2;
  width:400px;
  padding: 10px;
}
.grid-item2 { /* pw */
  grid-row-start: 2;
  grid-row-end: 3;
  grid-column-start: 1;
  grid-column-end: 2;
  width:400px;
  padding: 10px;
}
.grid-item3 { /* pwkvn */
  grid-row-start: 1;
  grid-row-end: 2;
  grid-column-start: 2;
  grid-column-end: 3;
  width:400px;
  padding: 10px;
}
.grid-item4 { /* sch */
  grid-row-start: 2;
  grid-row-end: 3;
  grid-column-start: 2;
  grid-column-end: 3;
  width:400px;
  padding: 10px;
}
    `
    ];
  } // styles

  render() {
   //console.log('my-app1: input=',this.input);
   return html`
  <csl-input 
   input="${this.input}" style="display:inline-block; padding-left:10px;"
   @new-input="${(e) => {this.input=e.detail.input;}}" 
  ></csl-input>
  <csl-citation suggest="yes" dict="pwkvn" input="${this.input}"
   key="${this.key}" style="display:inline-block; padding-left:10px;"
   @new-citation="${(e) => {this.key=e.detail.key;}}" 

  ></csl-citation>

  <csl-output 
   output="${this.output}" style="display:inline-block; padding-left:10px;"
   @new-output="${(e) => {this.output=e.detail.output;}}" 
  ></csl-output>

  <csl-accent 
   accent="${this.accent}" style="display:inline-block; padding-left:10px;"
   @new-accent="${(e) => {this.accent=e.detail.accent;}}" 
  ></csl-accent>

  <div class="grid-container" >
   <div class="grid-item1" >
    <my-app id="apppwg"  suggest="yes" dict="pwg"
    input="${this.input}"
    output="${this.output}"
    accent="${this.accent}"
    key="${this.key}"
    height="250px" width="400px"
    > </my-app>
   </div>
   <div class="grid-item2" >
    <my-app id="apppw"  suggest="yes" dict="pw"
    input="${this.input}"
    output="${this.output}"
    accent="${this.accent}"
    key="${this.key}"
    height="250px" width="400px"
    > </my-app>
   </div>
   <div class="grid-item3">
    <my-app id="apppwkvn" suggest="yes" dict="pwkvn"
    input="${this.input}"
    output="${this.output}"
    accent="${this.accent}"
    key="${this.key}"
    height="250px" width="400px"
    > </my-app>
   </div>
   <div class="grid-item4">
    <my-app id="appsch" suggest="yes" dict="sch"
    input="${this.input}"
    output="${this.output}"
    accent="${this.accent}"
    key="${this.key}"
    height="250px"  width="400px"
    > </my-app>
   </div>
  </div> 
  `;
  }

} // myApp1

if (!customElements.get('my-app1')) {
 customElements.define('my-app1', myApp1);
}
