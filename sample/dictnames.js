var CologneDisplays = {};
CologneDisplays.dictionaries= {
    dictnames:
[ /*['AP' , 'Practical Sanskrit-English Dictionary'],*/
['ABCH' , 'Abhidhānacintāmaṇi of Hemacandrācārya'],
['ACC' , 'Aufrecht Catalogus Catalogorum'],	
['ACPH' , 'Abhidhānacintāmaṇipariśiṣṭa of Hemacandrācārya'],
['ACSJ' , 'Abhidhānacintāmaṇiśiloñcha of Jinadeva'],
['AP90' , 'Apte Practical Sanskrit-English Dictionary'],
['ARMH' , 'Abhidhānaratnamālā of Halāyudha'],
['BEN' , 'Benfey Sanskrit-English Dictionary'],
['BHS' , 'Edgerton Buddhist Hybrid Sanskrit Dictionary'],
['BOP' , 'Bopp Glossarium Sanscritum'],
['BOR' , 'Borooah English-Sanskrit Dictionary'],
['BUR' , 'Burnouf Dictionnaire Sanscrit-Français'],
['CAE' , 'Cappeller Sanskrit-English Dictionary'],
['CCS' , 'Cappeller Sanskrit Wörterbuch'],
['GRA' , 'Grassmann Wörterbuch zum Rig Veda'],
['GST' , 'Goldstücker Sanskrit-English Dictionary'],
['IEG' , 'Indian Epigraphical Glossary'],
['INM' , 'Index to the Names in the Mahabharata'],
['KRM' , 'Kṛdantarūpamālā'],
['LAN' , 'Lanman Sanskrit Reader Dictionary'],
['LRV' , 'Vaidya Standard Sanskrit-English Dictionary'],
['MCI' , 'Mahabharata Cultural Index'],
['MD' , 'Macdonell Sanskrit-English Dictionary'],
['MW' , 'Monier-Williams Sanskrit-English Dictionary'],
['MW72' , 'Monier-Williams Sanskrit-English Dictionary'],
['MWE' , 'Monier-Williams English-Sanskrit Dictionary'],
/*['PD' , 'An Encyclopedic Dictionary of Sanskrit on Historical Principles'],*/
['PE' , 'Puranic Encyclopedia'],
['PGN' , 'Personal and Geographical Names in the Gupta Inscriptions'],
['PUI' , 'The Purana Index'],
['PW' , 'Böhtlingk Sanskrit-Wörterbuch in kürzerer Fassung'],
['PWG' , 'Böhtlingk and Roth Grosses Petersburger Wörterbuch'],
['SCH' , 'Schmidt Nachträge zum Sanskrit-Wörterbuch'],
['SHS' , 'Shabda-Sagara Sanskrit-English Dictionary'],
['SKD' , 'Sabda-kalpadruma'],
['SNP' , 'Meulenbeld Sanskrit Names of Plants'],
['STC' , 'Stchoupak Dictionnaire Sanscrit-Français'],
['VCP' , 'Vacaspatyam'],
['VEI' , 'The Vedic Index of Names and Subjects'],
['YAT' , 'Yates Sanskrit-English Dictionary'],
['WIL' , 'Wilson Sanskrit-English Dictionary']
],
    dictshowMake: function() {
 var x = [];
        var i,y,label,value,obj;
        for(i=0;i<this.dictnames.length;i++) {
            var y = this.dictnames[i];
     obj = {label: y[0]+" " + y[1], value:y[0]};
            x.push(obj);
 }
 return x;
    },
    
};
CologneDisplays.dictionaries.dictshow = CologneDisplays.dictionaries.dictshowMake();
