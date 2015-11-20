<html>
<meta http-equiv="Content-Type" Content="text/html; charset=utf-8">
<head>
  <style> 
<!--
body, td { 
font:normal normal
} 

div.form {
  width: 500px;
  border: 1px dotted #333;
  padding: 5px;
  margin: 5px auto auto auto;
  float: left;
}
div.info {
  float: left;
}
div.send {
  float: right;
}

div.row {
  clear: both;
  padding: 1px;
  }

div.row span.formw {
  float: left;
  }

div.row span.button {
  float: left;
  }

div.row span.label {
  float: left;
  }


-->
   </style>
</head>
<body>
<?php
// vim: set filetype=php expandtab tabstop=2 shiftwidth=2 autoindent smartindent:

error_reporting ( E_ALL );
#define("DEBUG", true );
include("config.php");
include("ttslib.php");
include("iana-registry.php");

reset($tts);
$ttsname=key($tts);
ttssetdefault($tts,$ttsname,$lang,$voice,$iformat,$codec);

function makeselect($name,$array,$selected,$info=false){
$select = '<select name="'.$name.'" id="'.$name.'" size="1">'."\r\n";
	foreach($array as $key => $value){
    #Create voices from lang for config ...
    #echo "
    #    '$key'  => array(
    #                      'type'=>'male',
    #                      'desc'=>'$info[$key] Male',
    #                      'voptions' => array ('voice' => '$key')
    #                    ),";
    #   continue;
    //value must be a string
    if(is_int($key) && is_string($value))
      $select .= "\t".'<option value="'.$value.'"';
    else
      $select .= "\t".'<option value="'.$key.'"';

    if( $key == $selected)
      $select .= ' selected ';
    $select .= '>';
    
    if( is_array($value) ){
      if ( is_array($info) ){
        $select .= $info[$key].' ('.$key.') </option>'."\r\n";
      }elseif($info=='count'){
        $select .= $key.'('.count($value).') </option>'."\r\n";
      }
      else
        $select .= $key.' </option>'."\r\n";
    }else{
      //No description for values :
      if($info=='keyisvalue'){
        $select .= $key.' ('.$value.') </option>'."\r\n";
      }else{
	if ( !is_array($info) ){
          $select .= $value.' ('.$key.') </option>'."\r\n";
        }else{
	  //info is an array of descriptions
          $select .= $value.' ('.$info[$value].') </option>'."\r\n";
        }
      }
    }
  }
  return $select .= '</select>';
}


function getmemory($disable="",$disable2=""){
  $buff='';
  if(count($_GET) > 0 )
    foreach($_GET as $varname => $varvalue){
      if($varname!=$disable && $varname!=$disable2)
        $buff .= '<input type="hidden" name="'.$varname.'" value="'.$varvalue.'">'."\r\n";
    }
  return $buff;
}

function GetDesc($lang){
#Generate language name from iana description
debug("$lang:".strstr($lang,"_"));
if(strstr($lang,"_") !== false )
  $exlang=explode('_',$lang);
else
  $exlang=explode('-',$lang);
debug(print_r($exlang,true));
$desc="";
foreach($exlang as $key => $langtag){
  global $iana_languages,$iana_regions,$iana_variants;
  #first is language, or error
  if($key==0 && array_key_exists(strtolower($langtag),$iana_languages)){
    $langname=$iana_languages[strtolower($langtag)]['Description'];
    debug("language name found: $langname");
    $desc=$langname;
    continue;
  }

 debug("$key => $langtag");
  if(array_key_exists(strtoupper($langtag),$iana_regions)){
    $langregion=$iana_regions[strtoupper($langtag)]['Description'];
    debug("REGION name found: $langregion");
    $desc.=" from ".$langregion;
    continue;
  }

  if(array_key_exists(strtolower($langtag),$iana_variants)){
    $langvariant=$iana_variants[strtolower($langtag)]['Description'];
    debug("variant name found: $langvariant");
    $desc.=" ".$langvariant;
    continue;
  }

}
return $desc;
}


function createform($name,$label,$input,$selected=false){
  echo '<form name="$name" method="get" action="">';
  echo '<div class="row"><span class="label">'.$label.': </span><span class="formw">';
  if($selected){
    echo "<b>&nbsp;$selected&nbsp;</b>";
    echo $input;
    echo '</span><span class="button"><input type="submit" value="Unset" />'."\r\n";
  }else{
    echo $input;
    echo '</span><span class="button"><input type="submit" value="Set" />'."\r\n";
  }
     
  echo '</span></div></form>'."\r\n";
}

?>
<div class="form">
<?php
############################
# TTS Engine selector
$newttsname=getorpost('ttsname',$ttsname);
if($newttsname){
  createform('ttsname','TTS Engine',"",$ttsname);
  ttssetdefault($tts,$ttsname,$lang,$voice,$iformat,$codec);
}else{
  createform('ttsname','TTS Engine',makeselect('ttsname',$tts,$ttsname));
}
################################
# Language selector
$newlang=getorpost('language',$lang);

foreach($tts as $ttsarray)
  foreach($ttsarray['langcodes'] as $keylang => $valuelang){
    $langdescarray[$keylang]=GetDesc($keylang);
  }
asort($langdescarray);
debug("langdescarray: ".print_r($langdescarray,true));

if($newlang){
  createform('languages','Lang',getmemory('language','voice'),$lang);
}else{
  createform('languages','Lang',getmemory('language','voice').makeselect('language',$tts[$ttsname]['langcodes'],$lang,$langdescarray));
}

################################
# Voice Select
  $newvoice=getorpost('voice',$voice);
  $voices=array();

  if(!$newlang){
    $voices=$tts[$ttsname]['voices'];
  }else{
    foreach($tts[$ttsname]['langcodes'][$lang] as $tmpvoice )
      foreach($tts[$ttsname]['voices'] as $vindex => $varray )
        if ( $tmpvoice == $vindex )
          $voices[$vindex]=$varray;
  }
  #Description array
  foreach($tts[$ttsname]['voices'] as $vindex => $varray )
    $desc[$vindex]=$varray['desc'];
  debug("Voices :".print_r($voices,true));

  if($newvoice){
    createform('voices','Voices',getmemory('voice'),$voice);
  }else{
    createform('voices','Voices',getmemory('voice').makeselect('voice',$voices,$voice,$desc));
  }
  
#####################################
#format GET or POST as VXI understands
######################################
$newvxiformat=getorpost('format',$vxiformat);
  if($newvxiformat){
    $iformat=$tts[$ttsname]['vxiformats'][$vxiformat]['iformat'];
    $codec=$tts[$ttsname]['vxiformats'][$vxiformat]['codec'];
    createform('vxiformats','Format(VXI)',getmemory('format'),$vxiformat);
  }else{
    createform('vxiformats','Format(VXI)',getmemory('format').makeselect('format',$tts[$ttsname]['vxiformats'], array_search($vxiformat,$tts[$ttsname]['vxiformats'])));
  }
######################################
# Internal Format
######################################
if(!$newvxiformat){
  $newiformat=getorpost('iformat',$iformat);
  if($newiformat){
    createform('iformats','Internal Format',getmemory('iformat'),$iformat);
  }else{
    if(!$tts[$ttsname]['formatiscodec']){
      createform('iformats','Internal Format',
        getmemory('iformat').makeselect('iformat',$tts[$ttsname]['iformats'], array_search($iformat,$tts[$ttsname]['iformats'])));
    }else{
      //format is codec:
      createform('iformats','Internal Format',
        getmemory('iformat').makeselect('iformat',$tts[$ttsname]['iformats'], array_search($iformat,$tts[$ttsname]['iformats']),$tts[$ttsname]['codecs']));
    }
  }
}
######################################
# Internal Codec
######################################
if(!$tts[$ttsname]['formatiscodec'] && !$newvxiformat){
	$newcodec=getorpost('codec',$codec);
	if($newcodec){
    createform('codecs','Internal Codec', getmemory('codec','format'),$codec);
	}else{
    createform('codecs','Internal Codec', getmemory('format').makeselect('codec',$tts[$ttsname]['codecs'],$codec,'keyisvalue'));
  }
}
#################################
//echo '<form name="general4" method="get" action="">'."\r\n";
//echo '&nbsp;<input type="submit" value="Reset" />'."\r\n";
//echo '</form>';
#################################


echo '<br><hr>'."\r\n";

###############################

echo "<div class=\"info\">engine: <b>$ttsname</b>  lang: <b>$lang</b>  voice: <b>$voice</b><br>";
echo isset($vxiformat) ? "vxiformat : <b>$vxiformat</b> " : "";
echo " iformat: <b>$iformat</b>    codec: <b>$codec</b><br></div>";
echo '<form name="general5" method="post" action="tts-debug.php" target="ttsexecute">'."\r\n";
echo '<div class="send">debug<input type="checkbox" checked="1" name="debug"><input type="submit"  value="Send"style="width:auto" /></div>'."\r\n";



echo getmemory();
$samples=array(
      'en' => 'They that can give up essential liberty to purchase a little temporary safety, deserve neither liberty nor safety. Benjamin Franklin',
      'es' => 'Aquellos que cederían la libertad esencial para adquirir una pequeña seguridad temporal, no merecen ni libertad ni seguridad. Benjamin Franklin',
      'ca' => 'Els que poden donar a la llibertat essencial per adquirir una petita seguretat temporal, no mereixen ni llibertat ni seguretat. Benjamin Franklin',
      'de' => 'Sie, die aufgeben können wesentliche Freiheit, ein wenig vorübergehende Sicherheit erwerben, verdienen weder Freiheit noch Sicherheit. Benjamin Franklin',
      'nl' => 'Zij die het kunnen geven van belangrijke vrijheid om een beetje tijdelijke veiligheid te kopen, verdienen noch vrijheid noch veiligheid. Benjamin Franklin',
      'af' => 'Die wat kan gee \'n bietjie vryheid noodsaaklik tydelike veiligheid te koop, verdien nie vryheid nie veiligheid. Benjamin Franklin',
      'it' => 'Sono in grado di rinunciare alla libertà essenziali per acquistare un po \'di sicurezza temporanea, meritano né libertà né sicurezza. Benjamin Franklin',
      'fr' => 'Celui qui est prêt à sacrifier un peu de liberté pour obtenir un peu de sécurité ne mérite vraiment ni l\'une, ni l\'autre. Benjamin Franklin',
      'cs' => 'Oni, že se vzdá svobody na nákup trochu dočasného bezpečí, si zaslouží, ani svobodu, ani bezpečí. Benjamin Franklin',
      'da' => 'De, der kan give op væsentlige frihed til at købe en lille midlertidig sikkerhed, fortjener hverken frihed eller sikkerhed. Benjamin Franklin',
      'el' => 'Μπορούν που μπορεί να δώσει μέχρι απαραίτητη ελευθερία για να αγοράσει μια μικρή προσωρινή ασφάλεια, αξίζουν ούτε ελευθερία ούτε ασφάλεια. Βενιαμίν Φραγκλίνος',
      'eo' => 'Ili kiu povas forlas havendan liberecon aĉeti malgrandan intertempan sekurecon, meritas nek libereco nek sekureco. Benjamin Franklin',
      'fi' => 'Ne, jotka voivat luopua olennaista vapauden ostaa hieman väliaikainen turvallisuutta, eivät ansaitse vapautta eivätkä turvallisuutta. Benjamin Franklin',
      'hi' => 'वे कहते हैं कि ऊपर के लिए आवश्यक एक छोटे से अस्थायी सुरक्षा खरीद की छूट दे सकता है, न तो स्वतंत्रता और न ही सुरक्षा के लायक हो. बेंजामिन फ्रेंकलिन',
      'hr' => 'Oni koji mogu odustati od bitnih slobode kupiti malo privremene sigurnosti, zaslužuju ni slobode ni sigurnosti. Benjamin Franklin',
      'hu' => 'Akik tudják feladni alapvető szabadság-hoz megvásárol egy kis ideiglenes biztonsági érdemelnek sem szabadságot, sem biztonságot. Benjamin Franklin',
      'hy' => 'Նա , որ կարող եմ , հույսը կտրել բուն ազատությունից փոքր ժամանակավոր անվտանգությունից գնելու , neither ազատությանը ժխտումով բացառող դիզյուկցիայի անվտանգությանն արժանանալու համար: Benjamin Franklin',
      'ku' => 'Peyva Kurdistan anjî Kordestan, Kordistan, Koordistan hwd. tê wateya welatê Kurdan û cara yekemîn ji alîye selcûqiyan ve, di sedsala 11. de hatîye karanîn.',
      'la' => 'They ut can redono necessarius licentia ut emo aliquantulus terrenus salus , mereo mereor neither licentia neque nec salus. Benjamin Franklin',
      'lv' => 'Tie, kas var atdot būtiska brīvības iegādāties nedaudz pagaidu drošības pelnījuši ne brīvības, ne arī drošību. Benjamin Franklin',
      'no' => 'De som kan gi opp viktig frihet til å kjøpe en litt midlertidig sikkerhet, fortjener verken frihet eller sikkerhet. Benjamin Franklin',
      'pl' => 'One, że może zrezygnować z zakupu podstawowych wolności trochę tymczasowego bezpieczeństwa, nie zasługują ani wolności, ani bezpieczeństwa. Benjamin Franklin',
      'pt' => 'Eles que pode desistir da liberdade essencial para comprar um pouco de segurança temporária não merecem nem liberdade nem segurança. Benjamin Franklin',
      'ro' => 'Ei, care poate să renunţe la libertatea esenţială pentru a achiziţiona un pic de siguranţă temporare, merită nici libertate, nici siguranţă. Benjamin Franklin',
      'ru' => 'Они, что могут отказаться от свободы необходимо приобрести небольшой временной безопасности, не заслуживают ни свободы, ни безопасности. Бенджамин Франклин',
      'sk' => 'Oni, že sa vzdá slobody na nákup trochu dočasného bezpečia, si zaslúži, ani slobodu, ani bezpečie. Benjamin Franklin',
      'sq' => 'Ata që mund të heqë dorë lirinë thelbësore për të blerë një siguri të vogël të përkohshme, e meritojnë as lirinë e as sigurinë. Benjamin Franklin',
      'sr' => 'Они који могу да се суштински слободу да купи мало привремене сигурности заслужују ни слободе ни безбедности. Бенџамин Френклин',
      'sv' => 'De som kan ge upp grundläggande frihet för att köpa lite tillfällig säkerhet förtjänar varken frihet eller säkerhet. Benjamin Franklin',
      'ta' => 'ஹெய் தட் கன் கிவெ உப் எஸ்ஸென்டிஅல் லிபெர்ட்ய் டொ புர்சஸெ அ லிட்ட்லெ டெம்பொரர்ய் ஸஃபெட்ய், டெஸெர்வெ னெஇதெர் லிபெர்ட்ய் னொர் ஸஃபெட்ய். என்ஜமின் ரன்க்லின்',
      'tr' => 'Bunlar temel özgürlük biraz geçici güvenlik satın almak kadar verebilir, ne özgürlük ne de güvenlik hak ediyor. Benjamin Franklin',
      'zh' => '他们可以放弃基本自由购买一些临时安全，值得既不自由，也不安全。本杰明富兰克林',
      'id' => 'Mereka yang dapat memberikan kebebasan penting untuk membeli keselamatan sementara sedikit, tidak pantas kebebasan atau keselamatan. Benjamin Franklin',
      'ar' => 'انهم يمكن ان تتخلى عن الحرية الأساسية لشراء القليل سلامة مؤقتة، لا يستحقون الحرية ولا السلامة. بنيامين فرانكلين',

);
if(!getorpost('text',$text)){
  if( $newlang==false ){
    #try to find better lang than default
    foreach($tts[$ttsname]['langcodes'] as $tmplang => $tmpvoices ){
      if(array_search($voice,$tmpvoices)!== false){
        $currlang=$tmplang;
	      break;
      }
    }
  }else
    $currlang=$lang;

  if(array_key_exists($currlang,$samples))
    $text=$samples[$currlang];
  elseif(strstr($currlang,'-')!==false){
    #try first tag only ...
    $currlangarray=explode('-',$currlang);
    #print_r($currlangarray);
    if(array_key_exists($currlangarray[0],$samples))
      $text=$samples[$currlangarray[0]];
    else
      $text="Hello world!";
  }else{
    $text="Hello world!";
  }
}

echo '<textarea name="text" rows="5" cols="58">'.$text.'</textarea>';
echo '</form>';
echo '</div>';
?>

</body>
</html>
