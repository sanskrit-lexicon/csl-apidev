import { html, css, LitElement,unsafeHTML } from './lit-element-2.3.1.js';

class cslAccent extends LitElement {
  static get styles() {
   return [
    
   ];
  }
  
  static get properties() {
    return {
      accent:  { type: String },
    };
  }

  constructor() {
    super();
    this.input="yes";
  }
  itemnames = [
  ['yes', 'Show Accent'],
  ['no', 'Hide Accent'],
  ];

  itemF = function(item) {
   let value = item[0];
   let name  = item[1];
   let markup;
   if (this.accent.toLowerCase() == value.toLowerCase()) {
    markup = html`<option value="${value}" selected>${name}</option>`;
   } else {
    markup = html`<option value="${value}">${name}</option>`;
   }
   return markup;
  }
  onChangeF (event) {
    //event.preventDefault();
    this.accent = event.target.value;
    //console.log('csl-accent: new value of this.accent=',this.accent);
    var new_event = new CustomEvent('new-accent',
     {detail: {accent:this.accent}
     });
    this.dispatchEvent(new_event);  // this. is needed. Not sure why
  }

  render() {
    return html`
<div id="accentdiv" title="accent selection">
  <select name="accent" id="accent" 
   @change=${this.onChangeF}
  >
   ${this.itemnames.map(item =>this.itemF(item))}
  </select>
 </div>      
    `;
  }
}


customElements.define('csl-accent', cslAccent);


