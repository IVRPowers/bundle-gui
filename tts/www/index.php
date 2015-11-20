<html>
<frameset cols="525,*">
	<frameset rows="462,*"> 
		<frame name="tts-config" id="tts-config" src="tts-test.php<?php
if(isset($_GET['ttsname']))
	echo "?ttsname=".$_GET['ttsname'];
?>">
		<frame name="ttsplayer" id="ttsplayer" src="tts-player.php">
  	</frameset>
   <frame name="ttsexecute" id="ttsexecute" src="tts.php">
  </frameset> 
</html>


