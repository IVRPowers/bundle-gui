<?php
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

if(isset($_POST['tts'])) {
	
	updateTTS($_POST['tts']);
	needreload();
}

$listTTS = getTTS();

foreach ($listTTS as $tts) {
	if ($tts['current'] == 1) {
		$currentTTS = array(
				"name"	=>	$tts['name'],
				"url"	=>	$tts['url']
		);
	}
	break;
}
?>

<?php if ($_SESSION['AMP_user']->username == "admin") {

	echo load_view(dirname(__FILE__) . '/views/withprivileges.tts.php',array("listTTS" => $listTTS));

} else {

	echo load_view(dirname(__FILE__) . '/views/withoutprivileges.tts.php',array("currentTTS" => $currentTTS));

}?>