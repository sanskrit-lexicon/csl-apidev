import { html, css, LitElement,unsafeHTML } from '../js/lit-element-2.3.1.js';

class cslDict extends LitElement {
  static get styles() {
   return [
    
   ];
  }
  
  static get properties() {
    return {
      dict:  { type: String },
    };
  }

  constructor() {
    super();
    this.dict="mw";
  }
  dictnames = 
[['WIL' , 'Wilson Sanskrit-English'],
['YAT' , 'Yates Sanskrit-English'],
['GST' , 'Goldstücker Sanskrit-English'],
['BEN' , 'Benfey Sanskrit-English'],
['MW72' , 'Monier-Williams 1872 Sanskrit-English'],
['AP90' , 'Apte Practical Sanskrit-English'],
['CAE' , 'Cappeller Sanskrit-English'],
['MD' , 'Macdonell Sanskrit-English'],
['MW' , 'Monier-Williams Sanskrit-English'],
['SHS' , 'Shabda-Sagara Sanskrit-English'],
['BHS' , 'Edgerton Buddhist Hybrid Sanskrit'],
['AP' , 'Practical Sanskrit-English, revised'],
['PD' , 'An Encyclopedic Dictionary of Sanskrit'], // on Historical Principles'],
['MWE' , 'Monier-Williams English-Sanskrit'],
['BOR' , 'Borooah English-Sanskrit'],
['AE' , 'Apte Student English-Sanskrit'],
['BUR' , 'Burnouf D. Sanscrit-Français'], //'Burnouf Dictionnaire Sanscrit-Français'],
['STC' , 'Stchoupak D. Sanscrit-Français'], //'Stchoupak Dictionnaire Sanscrit-Français'],
['PWG' , 'Grosses Petersburger Wörterbuch'], //'Böhtlingk and Roth Grosses Petersburger Wörterbuch'],
['GRA' , 'Grassman Wörterbuch zum Rig Veda'],
['PW' , 'Böhtlingk Sanskrit-Wörterbuch'], // in kürzerer Fassung'],
['PWKVN' , 'PW, Nachträge und Verbesserungen'], 
['CCS' , 'Cappeller Sanskrit Wörterbuch'],
['SCH' , 'Schmidt Nachträge'], // zum Sanskrit-Wörterbuch'],
['BOP' , 'Bopp Glossarium Sanscritum'],
['SKD' , 'Sabda-kalpadruma'],
['VCP' , 'Vacaspatyam'],
['INM' , 'Names in the Mahabharata'],//'Index to the Names in the Mahabharata'],
['VEI' , 'The Vedic Index'], // of Names and Subjects'],
['PUI' , 'The Purana Index'],
['ACC' , 'Aufrecht Catalogus Catalogorum'],
['KRM' , 'Kṛdantarūpamālā'],
['IEG' , 'Indian Epigraphical Glossary'],
['SNP' , 'Meulenbeld Sanskrit Names of Plants'],
['PE' , 'Puranic Encyclopedia'],
['PGN' , 'Names in the Gupta Inscriptions'], //'Personal and Geographical Names in the Gupta Inscriptions'],
['MCI' , 'Mahabharata Cultural Index']
];
  dictitemF = function(item) {
   let value = item[0];
   let name  = item[1];
   let markup;
   if (this.dict.toLowerCase() == value.toLowerCase()) {
    markup = html`<option value="${value}" selected>${name}</option>`;
   } else {
    markup = html`<option value="${value}">${name}</option>`;
   }
   return markup;
  }
  onChangeF (event) {
    //event.preventDefault();
    this.dict = event.target.value;
    //console.log('csl-dict: new value of this.dict=',this.dict);
    var new_event = new CustomEvent('new-dict',
     {detail: {dict:this.dict}
     });
    this.dispatchEvent(new_event);  // this. is needed. Not sure why
  }

  render() {
    return html`
<div id="dictdiv">
  <select name="input" id="input" 
   @change=${this.onChangeF}>
   ${this.dictnames.map(item =>this.dictitemF(item))}
  </select>
 </div>      
    `;
  }
}

if (!customElements.get('csl-dict')) {
customElements.define('csl-dict', cslDict);
}

/*
    
  <!-- <label for="input">input</label> -->

  
    }
<!--
   <option value='hk' selected='selected'>KH </option>
   <option value='slp1'>SLP1</option>
   <option value='itrans'>ITRANS</option>
   <option value='deva'>Devanagari</option>
   <option value='roman'>IAST</option>
-->
*/
