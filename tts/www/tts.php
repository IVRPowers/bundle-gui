<?php

error_reporting ( E_ALL );

include_once("config.php");
include_once("ttslib.php");

if(!defined('DEBUG'))
  error_reporting(0);

#default is first in tts array
if(isset($ttsdefault) && array_key_exists($tts[$ttsdefault]) ){
  $ttsname=$ttsdefault;
  debug("Default tts is forced to $ttsname");
}else{
  #default is first in tts array
  reset($tts);
  $ttsname=key($tts);
  debug("Default tts is first in tts array:$ttsname");

}

#Set defaults :
ttssetdefault($tts,$ttsname,$lang,$voice,$iformat,$codec);

debug("Mode debug enabled");

if( getorpost('text',$text)){
  #only text var is a prerequist
  #Override tts default ?
  if( getorpost('ttsname',$newttsname)) 
    if(isset($tts[$newttsname]) ){
      $ttsname=$newttsname;
      #reload defaults
      ttssetdefault($tts,$ttsname,$lang,$voice,$iformat,$codec);
      debug("TTS engine changed to $newttsname");

    }else
      debug("TTS engine not found $newttsname, back to default:$ttsname");


  #Override lang?
  if( getorpost('language',$newlang)){
    #Language tag is correct or we have found a similar tag :
    if(array_key_exists($newlang,$tts[$ttsname]['langcodes']) || trylang($tts[$ttsname]['langcodes'],$newlang) ){
      $lang=$newlang;
      debug("Lang changed to $lang");
      $voice=$tts[$ttsname]['langcodes'][$lang][0];
      debug("Voice changed to $voice");
    }else{
      debug("Not supported lang : '$newlang', back to default : '$lang'");
      unset($newlang);
    }
  }
    
  #Override default voice?
  if( getorpost('voice',$newvoice)){
    #Speaker exists ?
    if( array_key_exists($newvoice,$tts[$ttsname]['voices'])!== false){
      $voice=$newvoice;
      debug("Voice changed to $voice");
    }else{
      debug("Voice $newvoice not valid name, or language keep $voice");
    }
  }

  #VXI format always : gsm, alaw, wav, ulaw ...
  if(getorpost('format',$newvxiformat)){
    #avoid WAV, wav problems...lowerize input
    $newvxiformat=strtolower($newvxiformat);
    if(array_key_exists($newvxiformat,$tts[$ttsname]['vxiformats']) ){
      $vxiformat=$newvxiformat;
      $codec=$tts[$ttsname]['codecs'][$tts[$ttsname]['vxiformats'][$vxiformat]['codec']];
      $iformat=$tts[$ttsname]['vxiformats'][$vxiformat]['iformat'];
       debug("Internal format changed to $iformat");
       debug("Codec  changed to $codec");
       debug("VXI format is set to $vxiformat"); 
    }else{
      debug("Not supported vxiformat : '$newvxiformat', back to defaults format '$iformat' and codec '$codec'");
    }
  }else{
    #Override internal format?
    #More for test purpose than for real world
    if( getorpost('iformat',$newiformat)){
      if(in_array($newiformat,$tts[$ttsname]['iformats']) ){
        $iformat=$newiformat;
        if($tts[$ttsname]['formatiscodec'])
          $codec=$tts[$ttsname]['codecs'][$iformat];
        debug("Internal format changed to $iformat");
      }else
        debug("Not supported iformat : '$newiformat', back to default : '$iformat'");
    }

    #Override internal codec?
    #More for test purpose than for real world
    if( getorpost('codec',$newcodec)){
      if(array_key_exists($newcodec,$tts[$ttsname]['codecs'])){
        $codec=$tts[$ttsname]['codecs'][$newcodec];
        debug("Codec changed to $newcodec ($codec)");
        if($tts[$ttsname]['formatiscodec']){
          debug("Format is codec flag setted, so format is $codec");
          $iformat=$codec;
        }
      }else{
        debug("Not supported codec : '$newcodec', back to default : '$codec'");
        debug("Supported codec are: ".implode(", ",array_keys($tts[$ttsname]['codecs'])));
      }
    }
  }



}else
  httperror();

debug("Engine  : $ttsname");
debug("Lang    : $lang");
debug("Voice   : $voice");
debug("Iformat : $iformat");
debug("Codec   : $codec");

#debug("_SERVER: ".print_r($_SERVER,true));

switch($iformat){
  case 'gif':
    headerdebug("Content-Type: image/gif");
    break;
  case 'ulaw':
  case 'alaw':
  case 'pcm':
  case 'raw':
     headerdebug("Content-Type: audio/raw");
     break;
  case 'wav':
    headerdebug("Content-Type: audio/wav");
    break;
  default:
    headerdebug("Content-Type: application/octet-stream");
}

headerdebug('Content-Disposition: attachment; filename="file.'.$iformat.'"');

#Some TTS only support special charset, default is UTF8.
$text=stripslashes($text);
if(function_exists('mb_convert_encoding')){
  #UTF8 must always be tested first... 
  mb_detect_order('UTF-8,'.implode(',',$tts[$ttsname]['charsets']));
  debug("Detect order: ".print_r(mb_detect_order(),true));
  $currentcharset=mb_detect_encoding($text);
  debug("Current charset : $currentcharset");
  if(array_search($currentcharset,$tts[$ttsname]['charsets']) === false ){
    debug("Current charset not supported, convert to ".$tts[$ttsname]['charsets'][0]);
    $text=mb_convert_encoding($text,$tts[$ttsname]['charsets'][0], 'auto');
  }else{
    debug("No need to convert");
  }
}else{
  debug("function mb_convert_encoding not found, no charset correction");
}

#add ssml wrap ?
if (array_key_exists('ssmlwrap',$tts[$ttsname]) && $tts[$ttsname]['ssmlwrap'] == true){
  file_put_contents("$filename.lst",$filename);
  debug("Adding SSML wrap to text ");
  $text="<?xml version=\"1.0\"?>\n<speak version=\"1.0\" xml:lang=\"$lang\">$text</speak>";
}

#basic checks :
if (defined('DEBUG') && array_key_exists('checks',$tts[$ttsname]) ){
  foreach( $tts[$ttsname]['voices'][$voice]['voptions'] as $vname => $vvalue)
    $$vname=$vvalue;
  foreach($tts[$ttsname]['checks'] as $checkthis){
    eval('$tmp="ARG: \'$checkthis\'"; $tmpchk="'.$checkthis.'";');
    debug("$tmp -> $checkthis");
    if(!file_exists($tmpchk) && !function_exists($tmpchk)){
      debug("Basic checks failed: $tmpchk , function or file do not exists");
      exit;
    }
  }
}


#File creation
$filename=tempnam("/tmp",$ttsname."TMP");
register_shutdown_function('cleanup',"$filename");


debug(" Temp file = $filename ");

if (array_key_exists('createlistfile',$tts[$ttsname]) && $tts[$ttsname]['createlistfile'] == true){
  file_put_contents("$filename.lst",$filename);
  register_shutdown_function('cleanup',"$filename.lst");
  if(defined('DEBUG')){
    debug("LIST_FILE: $filename.lst :'".print_r(file("$filename.lst"),true)."'");
  }
}

$time_start = microtime(true);

register_shutdown_function('cleanup',"$filename.$iformat");

#Limits simultaneous calls ?
if (array_key_exists('simexeclimit',$tts[$ttsname]) && $tts[$ttsname]['simexeclimit'] > 0 ){
  $simexeclimit=$tts[$ttsname]['simexeclimit'];
  debug("Exec limits activated to : $simexeclimit");
  //11259375 is a long id:  0x00abcdef (ipcs)
  //Semaphore is relased at shutdown of current process
  $seg = sem_get( 11259375 , $simexeclimit, 0666, 1);
  sem_acquire($seg);
}

if (array_key_exists('directphpfunction',$tts[$ttsname]) && $tts[$ttsname]['directphpfunction'] == true){
  # Direct PHP call
  if(function_exists($tts[$ttsname]['call'])){
    
    foreach( $tts[$ttsname]['voices'][$voice]['voptions'] as $vname => $vvalue)
      $$vname=$vvalue;

    $funcall='$out='.$tts[$ttsname]['call'].'('.implode(',',$tts[$ttsname]['options']).');';
    debug("Function call : $funcall");
    $status=eval($funcall);

    if(!file_exists("$filename.$iformat"))
      file_put_contents("$filename.$iformat",$out);
        
  }else{
    debug("Function PHP ".$tts[$ttsname]['call']." do not exists");
    $status="PHP Function not defined";
  }
}else{
  #Command Line Call
  $file=fopen($filename, "w");
  fwrite($file,$text."\n");
  fclose($file);

  if(defined('DEBUG')){
    debug("TEXT: '<XMP>".print_r(file($filename),true)."</XMP>'");
    debug("TEXT BIN: '".bin2hex(implode('',file($filename)))."'");
    debug("FILE ANALYSE: '".print_r(execdebug("/usr/bin/file $filename",$tmpout,$tmpstatus),true));
  }

  $arguments='';
  { //keep this declartions locales for voice specials variables
    foreach( $tts[$ttsname]['voices'][$voice]['voptions'] as $vname => $vvalue)
        $$vname=$vvalue;
    foreach( $tts[$ttsname]['options'] as $key => $argument){
      eval('$tmp="ARG$key: \'$argument\'"; $evalargument="'.$argument.'";');
      debug("$tmp -> $evalargument");
      $arguments .= ' '.$evalargument;
    }
  }
  if(defined('DEBUG')){
    $arguments .= ' '.$tts[$ttsname]['debugargs'].' 2>&1';
    debug("Added debug arguments : '".$tts[$ttsname]['debugargs']."  2>&1'");
  }
  $execline=$tts[$ttsname]['call'].$arguments;
  execdebug($execline,$return, $status);
}
$time_end = microtime(true);
$texec = $time_end - $time_start;
$outsize=filesize("$filename.$iformat");

debug(" STATUS: $status");

if(!defined('DEBUG'))
  readfile("$filename.$iformat");
else{
  debug(" File size = $outsize");
}
if($enable_record_to_cdr)
  register_shutdown_function('cdrrecord',$ttsname,$lang,$status,$texec,$text,$outsize);

register_shutdown_function('garbage',$ttsname);

// vim: set filetype=php expandtab tabstop=2 shiftwidth=2 autoindent smartindent:
?>
