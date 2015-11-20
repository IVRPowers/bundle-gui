<?php
include_once("ttslib.php");

if (getorpost('filename',$filename)){
  getorpost('type',$type);
?>
<html>
    <head>
        <meta charset="utf-8">
        <script src="html5media/html5media.min.js"></script>
  <style> 
<!--
body, td, video, div.video {
  margin: 0 auto;
  font:normal normal
  align: center;
  text-align: center
}
audio, div.audio {
  margin: 30 30 30 30;
  text-align: center;
}
-->
   </style>
    </head>
<body>
<?php if ($type=="video" ): ?>
<div class="video">
  <video class="video" width="352" height="288" controls preload autoplay>
    <source src="file.php?id=<?php echo $filename?>.mp4" type='video/mp4; codecs="avc1.42E01E, mp4a.40.2"'></source>
    <source src="file.php?id=<?php echo $filename?>.ogv" type='video/ogg; codecs="theora, vorbis"'"></source>-->
  </video>
</div>
<br>
<div style="position: absolute; right: 10px; top: 100px;" >
<ul style="list-style-type: none">
<li><a href="file.php?id=<?php echo $filename?>.mp4">mp4</a></li>
<li><a href="file.php?id=<?php echo $filename?>.ogv">ogv</a></li>
<li><a href="file.php?id=<?php echo $filename?>ffmpeg.wav">wav</a></li>
<?php else : ?>
<div class="audio">
  <audio class="audio" controls preload autoplay>
    <source src="file.php?id=<?php echo $filename?>.mp3"   type="audio/mpeg"></source>
    <source src="file.php?id=<?php echo $filename?>.ogg"   type='audio/ogg; codecs="vorbis"'></source>
    <source src="file.php?id=<?php echo $filename?>ffmpeg.wav"   type='audio/wav; codecs="1"'></source>
  </audio>
</div>
<br>
<div style="position: absolute; right: 10px; top: 10px;" >
<ul style="list-style-type: none">
<li><a href="file.php?id=<?php echo $filename?>.mp3">mp3</a></li>
<li><a href="file.php?id=<?php echo $filename?>.ogg">ogg</a></li>
<li><a href="file.php?id=<?php echo $filename?>ffmpeg.wav">wav</a></li>
<?php endif; ?>
<li>&nbsp;</li>
<li><a href="?filename=<?php echo $filename?>&type=<?php echo $type?>">reload</a></li>
</ul>
</div>
</body>
</html>
<?php }else{ ?>
<html>
</html>
<?php }?>
