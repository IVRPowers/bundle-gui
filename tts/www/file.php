<?php
//error_reporting ( E_ALL );
error_reporting(E_ERROR | E_WARNING | E_PARSE);
#Debug print to /tmp/file.log
#define("DEBUG", true );
$TMPDIR="/tmp";

#include_once("common.php");

function debug($message){
  if(defined('DEBUG')){
    file_put_contents("/tmp/file.log",$message."\n",FILE_APPEND);
    //echo "<pre>DEBUG: $message</pre>\n";
    //flush();
  }
}

function headerdebug($header){
  if(defined('DEBUG'))
    debug(" HEADER => '$header'");
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



if(!defined('DEBUG'))
  error_reporting(0);

debug("Mode debug enabled");

if(getorpost('id',$filename)){
  $filename="$TMPDIR/$filename";
  $format=end(explode(".", $filename));
  debug("Search for $filename");
  if(!file_exists($filename) )
    httperror(404);
}else
  httperror(400);

switch ($format) {
  case "mp3":
    headerdebug("Content-Type: audio/mpeg");
    break;
  case "ogg":
    headerdebug("Content-Type: audio/ogg");
    break;
  case "wav":
    headerdebug("Content-Type: audio/x-wav");
    break;
  case "mp4":
    headerdebug("Content-Type: video/mp4");
    break;
  case "ogv":
    headerdebug("Content-Type: video/ogg");
    break;
  default:
    httperror(400);
    break;
}

debug("USER AGENT: ".print_r($_SERVER['HTTP_USER_AGENT'],true));

$size=filesize($filename);
debug("File size is : $size bytes");
$is_resume=false;
//check if http_range is sent by browser (or download manager)
if(isset($_SERVER['HTTP_RANGE']))
{
    debug("HTTP_RANGE:".print_r($_SERVER['HTTP_RANGE'],true));
    $is_resume=true;
    list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    debug("Range are : size_unit=$size_unit range_orig=$range_orig");
    if ($size_unit == 'bytes'){
      //multiple ranges could be specified at the same time, but for simplicity only serve the first range
      //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
      list($range, $extra_ranges) = explode(',', $range_orig, 2);
      debug("Range are : range:$range, extra_ranges=$extra_ranges");
    }
    else
    {
        $range = '';
        debug("Range unit not supported : $size_unit ");
        $is_resume=false;
    }
}
else
{
  debug("No Range");
  $range = '';
}
//figure out download piece from range (if set)
@list($seek_start, $seek_end) = explode('-', $range,2);

//set start and end based on range (if set), else set defaults
//also check for invalid ranges.
$seek_end = (empty($seek_end)) ? ($size - 1) : min(abs(intval($seek_end)),($size - 1));
$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);

//add headers if resumable
if ($is_resume){
  //Only send partial content header if downloading a piece of the file (IE workaround)
  if ($seek_start > 0 || $seek_end < ($size - 1)){
    headerdebug('HTTP/1.1 206 Partial Content');
  }
  headerdebug('Accept-Ranges: bytes');
  headerdebug('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$size);
}

debug("Seeks : seek_start=$seek_start , seek_end=$seek_end");
//header('Content-Disposition: attachment; filename="' . $filename . '"');
headerdebug('Content-Length: '.($seek_end - $seek_start + 1));

//open the file
$fp = fopen($filename,'r');
//seek to start of missing part
fseek($fp, $seek_start);

//start buffered download
while(!feof($fp)){
  //reset time limit for big files
  set_time_limit(0);
  print(fread($fp, 1024*8));
  flush();
}
fclose($fp);

exit;

// vim: set filetype=php expandtab tabstop=2 shiftwidth=2 autoindent smartindent:
?>
