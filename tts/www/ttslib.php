<?php

if (!function_exists('file_put_contents')) {
    function file_put_contents($filename, $data) {
        $f = @fopen($filename, 'w');
        if (!$f) {
            return false;
        } else {
            $bytes = fwrite($f, $data);
            fclose($f);
            return $bytes;
        }
    }
}

function debug($message){
  if(defined('DEBUG')){
    echo "<pre>DEBUG: $message</pre>\n";
    flush();
  }
}

function headerdebug($header){
  if(defined('DEBUG'))
    debug(" HEADER => '$header'");
  else
    header($header);
}

function getorpost($varname,&$var){
  if(isset($_POST[$varname])){
    $var=$_POST[$varname];
    return true;
  }elseif(isset($_GET[$varname])){
    $var=$_GET[$varname];
    return true;
  }else
    return false;
}

function httperror($num=400){
  switch($num){
    case 404:
      headerdebug("HTTP/1.1 404 Not Foundt");
      break;
    case 400:
    default: 
      headerdebug("HTTP/1.1 400 Bad Request");
  }
  exit();
}

function garbage($ttsname,$prop=1){
	#Execute 1% of time
	if ( (rand(0,99)+$prop) >= 100 ){
		#Remove files older than 60 minutes
		exec("find  /tmp -name '".$ttsname."TMP*' -amin +60 -exec rm  \{\} \;");	
	}
}

function cleanup($filename){
	#sleep(10);
	@unlink($filename);
	
}

function cdrrecord($ttsname,$lang,$status,$texec,$text,$outsize){
	$headers = apache_request_headers();
	#var_dump ($headers);
	$link = mysql_connect("localhost", "cdr", "password") or die('Cannot connect to mysql');
	mysql_select_db('cdr', $link) or die('Could not select database.');
	if( $status == "0" && $outsize > "0" )
		$disposition="ANSWERED";
	else
		$disposition="ERROR(err:$status/${outsize}bytes)";

	$query  =  "insert into cdr values(now(),";       //calldate
	$query .=  "'".apache_getenv("SERVER_NAME")."',"; //clid
	$query .=  "'".apache_getenv("REMOTE_ADDR")."',"; //src
	$query .=  "'".apache_getenv("SERVER_ADDR")."',"; //dst
	$query .=  "'',";                                  //dcontext
	$query .=  "'$ttsname',";                          //channel
	$query .=  "'',";                                  //dstchannel
	$query .=  "'Vxml',";                              //lastapp
	@$query .=  "'".$headers["Referer"]."',";       //lastdata
	$query .=  floor($texec).","; //duration int()
	$query .=  floor($texec*1000).","; //billsec int()
	$query .=  "'".$disposition."',"; //disposition
	$query .=  "0,"; //amaflags int ()
	$query .=  "'".$lang."',"; //accountcode
	$query .=  "'".substr($text,0,30)."(".strlen($text)."chars:${outsize}bytes:MD5=".md5($text).")"."'"; //userfield
	$query .=  ")";


	mysql_query( $query ,$link);
	debug("Query :$query");
	//exit;
}

#Returns  
function execdebug($command,&$out,&$status){
  $out=array();
  exec($command,$out,$status);
  debug("EXEC: '$command'\n".print_r($out,true)."\n"."return: '$status'");
  array_unshift($out,$command,$status);
  return($out);
}

#Set defaults for a given ttsname:
function ttssetdefault($tts,$ttsname,&$lang,&$voice,&$iformat,&$codec){
  reset($tts[$ttsname]['langcodes']);
  $lang=key($tts[$ttsname]['langcodes']);
  $voice=reset($tts[$ttsname]['langcodes'][$lang]);
  $iformat=reset($tts[$ttsname]['iformats']);
  if($tts[$ttsname]['formatiscodec'])
    $codec=$tts[$ttsname]['codecs'][$iformat];
  else
    $codec=reset($tts[$ttsname]['codecs']);
  debug("Default TTS values commited for engine $ttsname");
}

# Try to find a language from erroned code
function trylang($ttslangcode,&$newlang){
  debug("$newlang:".strstr($newlang,"_"));
  if(strstr($newlang,"_") !== false )
    $exlang=explode('_',$newlang);
  else
    $exlang=explode('-',$newlang);
  debug(print_r($exlang,true));
  

  if(!array_key_exists($newlang, $ttslangcode)){
    # 1, 2 or 3 tags, try some combinations
    $funcarray=array('strtolower','strtoupper','ucfirst');
    #combi order from complex to simpler ( 3 2 1 tags)
    if( array_key_exists(1,$exlang)  && array_key_exists(2,$exlang) ){
      foreach($funcarray as $function)
        foreach($funcarray as $function2)
          $combis[]=strtolower($exlang[0]).'-'.$function($exlang[1]).'-'.$function2($exlang[2]);
        debug('Tree words... nine combinations');
    }
    if( array_key_exists(1,$exlang) ){
      foreach($funcarray as $function)
        $combis[]=strtolower($exlang[0]).'-'.$function($exlang[1]);
    }
    #simplest
    $combis[]=strtolower($exlang[0]);
      
    debug(print_r($combis,true));
    foreach($combis as $acombi){
     if(array_key_exists($acombi, $ttslangcode)){
       debug("tag '$newlang' not found, most similar '$acombi' is used");
       $newlang=$acombi;
       return true;
      }
    }
  }
  return false;
}

function sentences($text){
  $separe="__-__";
  //Google limit
  $maxlenght=100;
  $punctuation=".,:()!?><{}\"";
  $text=trim(preg_replace('/\s+/'," ",strtr($text,"\r\n\t","   ")));
  $wtext=wordwrap($text,$maxlenght,$separe,true);
  //echo " ORIG : $wtext\n";
  $start=0;
  $pos=strpos($wtext,$separe);
  //Less than maxlenght
  if($pos===false)
    return array($text);
  $cnt=0;$oldstart=0;
  while( ($chunk=substr($wtext,$start,$pos)) && $cnt<200){
    //Reset Wrapping ...
    //echo "Chunk:'$chunk'\n";
    //echo "WTEXT $pos $start: '$wtext' \n";
    $stats=array();
    foreach(str_split($punctuation) as $char){
      $pucpos=strpos($chunk,$char);
      if( $pucpos != false){
        switch($char){
          case ',':
          case '.':
          case ':':
            if(is_numeric(substr($chunk,$pucpos,1)) && is_numeric(substr($chunk,$pucpos,-1)))
              break;
          default:
            $stats["$char"]=$pucpos;
            break;
        }
      }
    }
    if(count($stats)>0){
      arsort($stats,SORT_NUMERIC);
      //print_r($stats);
      $start=array_shift($stats)+1;
      $oldstart+=$start;
    }else{
      $start=$pos+strlen($separe);
      $oldstart+=$pos+1;
    }
      $chunks[]=trim(substr($chunk,0,$start));
      //Reset Wrapping ...
      $wtext=wordwrap(substr($text,$oldstart),$maxlenght,$separe,true);
      //echo "RESET: '$wtext' \n";
      $start=0;
      $pos=strpos($wtext,$separe);
      if($pos===false){
        $chunks[]=trim(substr($text,$oldstart));
        break;
      }
    
    $cnt++;
    //print_r($chunks);
    //echo "New pos $pos ,start=$start \n";

  }
  //print_r($chunks);
  return($chunks);
}


##############################################
#Curl Function for directphpfunction options :

function ttsurl($url,$text,$voiceid,$rate,$pitch){
  $urlapi	= $url;	// TTS Service API 

  //options
  $filetype= 3;					// Default file type : raw ulaw 8Khz
  $volume	= 100;					// Default volume
  //$rate=0;					// Default rate
  //$pitch=0;					// Default pitch

  //POSTFields:
  $postfields="t=$text&af=$filetype&vl=$volume&v=$voiceid&r=$rate&p=$pitch";
  
  $curl = curl_init();

  curl_setopt($curl, CURLOPT_URL, $urlapi);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_POST, 1);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
  debug("CURL calling: '$urlapi'");
  debug("CURL Post args: ".print_r(explode('&',$postfields),true));
  if( !($buffer = curl_exec($curl)) ) {
    debug("CURL return an error or empty: '$buffer'");
	  curl_close($curl);
	  return false;
  }
  curl_close($curl);
  return base64_decode($buffer);
}

function ttsgoogle($url,$text,$lang,$filename,$iformat){
  //Google voices are realy slow, use something less than 8Hz as work arround
  $echfreq=7800;
  $chknb=0;
  $sentences=sentences($text);
  foreach( $sentences as $chunk ) {
    debug("Chunk$chknb: '$chunk'");
    $chknb++;
    $urltext=rawurlencode($chunk);
    $urlapi	= "$url?tl=$lang&q=$urltext";	// TTS Service API 
    $curl = curl_init();
    debug("URL: '$urlapi'");
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6");
    curl_setopt($curl, CURLOPT_URL, $urlapi);
  //curl_setopt($curl, CURLOPT_HEADER, true); // Display headers
  //curl_setopt($curl, CURLOPT_VERBOSE, true); // Display communication with server
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    if( !($buffer = curl_exec($curl)) ) {
      if(curl_errno($curl))
        debug('Erreur Curl : ' . curl_error($curl));
	    curl_close($curl);
      debug("CURL return an error or empty: '$buffer'");
	    return false;
    }
    debug("BUFFER size :".strlen($buffer));
    
    register_shutdown_function('cleanup',"$filename.$chknb");
    
    file_put_contents("$filename.$chknb",$buffer);
    execdebug("/usr/local/bin/ffmpeg  -i $filename.$chknb -ar $echfreq -ac 1 -acodec pcm_alaw -f alaw  - 2>>$filename.err.ffmpeg >>$filename.raw",$tmpout,$tmpstatus);
    curl_close($curl);    
  }
  if($iformat=='wav'){
    register_shutdown_function('cleanup',"$filename.raw");
    execdebug("/usr/local/bin/ffmpeg -ar 8000 -ac 1 -acodec pcm_alaw -f alaw  -i $filename.raw -ar 8000 -ac 1 -acodec pcm_s16le -f wav  $filename.wav 2>&1 ",$tmpout,$tmpstatus);
  }
  register_shutdown_function('cleanup',"$filename.err.ffmpeg");  
  return true;
}

function ttvvinter($url,
                   $user,$password,$codecs,$ext,
                   $text,$filename,$resolution,$fps,$voice,$face,$iformat){
    $urltext=rawurlencode($text);
    //?user=demo&password=123456&resolution=176x144&codecs=FLV_AAC&ext=flv&face=Brad&voice=vinicius&text=teste%20ao%20servidor&fps=7
    $urlapi	= "$url";	// TTS Service API 
    //POST vars:   
    $postfields= array (
      'user'=>$user,
      'password'=>$password,
      'codecs'=>$codecs,
      'ext'=>$ext,
      'resolution'=>$resolution,
      'face'=>$face,
      'voice'=>$voice,
      'fps'=>$fps,
      'text'=>$urltext,
    );
    $curl = curl_init();
    debug("URL: '$urlapi'");
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6");
    curl_setopt($curl, CURLOPT_URL, $urlapi);
  //curl_setopt($curl, CURLOPT_HEADER, true); // Display headers
  //curl_setopt($curl, CURLOPT_VERBOSE, true); // Display communication with server
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
    debug("CURL calling: '$urlapi'");
    debug("CURL Post args: ".print_r($postfields,true));
 
    if( !($buffer = curl_exec($curl)) || curl_getinfo($curl, CURLINFO_HTTP_CODE ) != 200 ) {
      debug("CURL return HTTP_CODE : ".curl_getinfo($curl, CURLINFO_HTTP_CODE ));
      if(curl_errno($curl))
        debug('Erreur Curl : ' . curl_error($curl));
	    curl_close($curl);
      debug("CURL return an http_error or empty: '$buffer'");
	    return false;
    }
 
    debug("BUFFER size :".strlen($buffer));

    #flv is the original supported format
    register_shutdown_function('cleanup',"$filename.flv");
    debug("File Creation: '$filename.flv'");
    file_put_contents("$filename.flv",$buffer);
    curl_close($curl);

    #Conversion?
    if($iformat=="3gp"){
      debug("3gp format conversion ...");
      register_shutdown_function('cleanup',$filename.".3gp");
	  register_shutdown_function('cleanup',$filename."-0.log");


      $ffmpeg="/usr/local/bin/ffmpeg";
      $mp4creator="/usr/local/bin/mp4creator";

       foreach( array(1,2) as $pass){
         execdebug("$ffmpeg -y -i $filename.flv -s qcif -r 7 -vcodec h263 ".
                   "-b 40000 -bt 10000 -ar 8000 -acodec libopencore_amrnb ".
                   "-ac 1 -ab 12200 -pass $pass -passlogfile $filename ".
                   "$filename.3gp 2>&1",$tmpout,$tmpstatus);
	   }
       foreach( array(1,2) as $pass){
         execdebug("$mp4creator -hint=$pass $filename.3gp 2>&1",$tmpout,$tmpstatus);
	   }
    }
    
  return true;
}


##############################################






// vim: set filetype=php expandtab tabstop=2 shiftwidth=2 autoindent smartindent:
?>
