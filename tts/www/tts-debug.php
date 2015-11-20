<?php
// vim: set filetype=php expandtab tabstop=2 shiftwidth=2 autoindent smartindent:

error_reporting ( E_ALL );

include("config.php");
include("ttslib.php");

if(getorpost('debug',$debug)){
  if(!defined('DEBUG'))
    define("DEBUG", true );
  include("tts.php");
}else{
  #No debug
  include("tts.php");
  exit();
}

$type="audio";
if(array_key_exists('video',$tts[$ttsname]) && $tts[$ttsname]['video']==true )
  $type="video";



debug("FILENAME: $filename.$iformat");
execdebug("/usr/bin/file \"$filename.$iformat\"  2>&1",$tmpout,$tmpstatus);

debug('Context : '.$context=implode('_',array($ttsname,$iformat,$codec)));

#Check ffmpeg if present on local, add directory
$ffmpegdir="/usr/local/bin/";
if(! file_exists($ffmpegdir."ffmpeg")){
  $ffmpegdir="";
}else{
  debug("ffmpeg called from $ffmpegdir");
}


//Common command line end
// this $filename ffmpeg.wav can be usefull for debug
$fileend=" $filename.$iformat ${filename}ffmpeg.wav 2>&1";



switch($context){
  case 'acapela_raw_8k':
  case 'loquendo_raw_l':
  case 'loquendo_raw_linear':
  case 'verbio_pcm_LIN16':
  case 'google_raw_pcm':
  case 'neospeech_pcm_2':
    debug('Special: raw 8KHz linear 16 bits');
    execdebug($ffmpegdir."ffmpeg -acodec pcm_s16le -ar 8000 -ac 1 -f s16le -i ".$fileend,$tmpout,$tmpstatus);
   break;

  case 'baratinoo_headerless_A-law 8000Hz':
  case 'baratinoo7_headerless_A':
  case 'ivona_raw_alaw':
  case 'acapela_raw_8ka':   
  case 'verbio_alaw_ALAW':
  case 'google_raw_alaw':
  case 'loquendo_raw_a':
    debug('Special: raw 8KHz A-LAW 8 bits');
    execdebug($ffmpegdir."ffmpeg -acodec pcm_alaw -ar 8000 -ac 1 -f alaw -i ".$fileend,$tmpout,$tmpstatus);
    break;
  case 'baratinoo_headerless_mu-law 8000Hz':
  case 'baratinoo7_headerless_mu':
  case 'ivona_raw_ulaw':
  case 'acapela_raw_8kmu':
  case 'loquendo_raw_u':
  case 'verbio_ulaw_ULAW':
  case 'url_raw_ulaw':
    debug('Special: raw 8KHz &micro;-LAW 8 bits');
    execdebug($ffmpegdir."ffmpeg -acodec pcm_mulaw -ar 8000 -ac 1 -f mulaw -i ".$fileend,$tmpout,$tmpstatus);
    break;
  case 'ttsgoogle_mp3_mpeg':
    execdebug($ffmpegdir."ffmpeg -i ".$fileend,$tmpout,$tmpstatus);
    //execdebug("rm $filename.$iformat",$tmpout,$tmpstatus);
    break;
  default:
    #check wav ... anyway
    execdebug($ffmpegdir."ffmpeg -i ".$fileend,$tmpout,$tmpstatus);
}
if( $type=="audio" ){
  //Generate mp3 and ogg
  execdebug($ffmpegdir."ffmpeg -i ${filename}ffmpeg.wav -ac 2 -ar 44100 -ab 128000 $filename.mp3 2>&1",$tmpout,$tmpstatus);
  execdebug($ffmpegdir."ffmpeg -strict experimental -i ${filename}ffmpeg.wav -ac 2 -ar 44100 -aq 10 -acodec libvorbis $filename.ogg 2>&1",$tmpout,$tmpstatus);
}elseif ( $type == "video"){
  //Generate mp4 and ogv
  execdebug($ffmpegdir."ffmpeg -i ${filename}.$iformat -ar 11025 -vcodec libx264 -vpre faster $filename.mp4 2>&1",$tmpout,$tmpstatus);
  execdebug($ffmpegdir."ffmpeg -strict experimental  -i ${filename}.$iformat -ac 2 -ar 44100 -aq 10 -acodec libvorbis $filename.ogv 2>&1",$tmpout,$tmpstatus);
}




#Garbage more ofen (80%), mp3 are not cleaned   by default
register_shutdown_function('garbage',$ttsname,90);

?>
<script type="text/javascript">
<!--
	parent.ttsplayer.location.href = "tts-player.php?filename=<?php echo basename($filename)?>&type=<?php echo $type?>"

//-->
</script>


