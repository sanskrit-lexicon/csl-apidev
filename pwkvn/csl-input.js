import { html, css, LitElement,unsafeHTML } from './lit-element-2.3.1.js';

class cslInput extends LitElement {
  static get styles() {
   return [
    
   ];
  }
  
  static get properties() {
    return {
      input:  { type: String },
    };
  }

  constructor() {
    super();
    this.input="hk";
  }
  itemnames = [
  ['hk', 'Harvard Kyoto'],
  ['slp1', 'SLP1'],
  ['itrans', 'ITRANS'],
  ['deva', 'Devanagari'],
  ['iast', 'IAST'],
  ];

  itemF = function(item) {
   let value = item[0];
   let name  = item[1];
   let markup;
   if (this.input.toLowerCase() == value.toLowerCase()) {
    markup = html`<option value="${value}" selected>${name}</option>`;
   } else {
    markup = html`<option value="${value}">${name}</option>`;
   }
   return markup;
  }
  onChangeF (event) {
    //event.preventDefault();
    this.input = event.target.value;
    //console.log('csl-input: new value of this.input=',this.input);
    var new_event = new CustomEvent('new-input',
     {detail: {input:this.input}
     });
    this.dispatchEvent(new_event);  // this. is needed. Not sure why
  }

  render() {
    return html`
<div id="inputdiv" title="input selection">
  <select name="input" id="input" 
   @change=${this.onChangeF}
  >
   ${this.itemnames.map(item =>this.itemF(item))}
  </select>
 </div>      
    `;
  }
}


customElements.define('csl-input', cslInput);


