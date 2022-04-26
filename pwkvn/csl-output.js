import { html, css, LitElement,unsafeHTML } from './lit-element-2.3.1.js';

class cslOutput extends LitElement {
  static get styles() {
   return [
    
   ];
  }
  
  static get properties() {
    return {
      output:  { type: String },
    };
  }

  constructor() {
    super();
    this.output="iast";
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
   if (this.output.toLowerCase() == value.toLowerCase()) {
    markup = html`<option value="${value}" selected>${name}</option>`;
   } else {
    markup = html`<option value="${value}">${name}</option>`;
   }
   return markup;
  }
  onChangeF (event) {
    //event.preventDefault();
    this.output = event.target.value;
    //console.log('csl-output: new value of this.output=',this.output);
    var new_event = new CustomEvent('new-output',
     {detail: {output:this.output}
     });
    this.dispatchEvent(new_event);  // this. is needed. Not sure why
  }

  render() {
    return html`
<div id="outputdiv" title="output selection">
  <select name="output" id="output" 
   @change=${this.onChangeF}
  >
   ${this.itemnames.map(item =>this.itemF(item))}
  </select>
 </div>      
    `;
  }
}

customElements.define('csl-output', cslOutput);

