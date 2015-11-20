<?php 

$ttsList = getTTS();

$mode = "HTTP";
foreach ($ttsList as $tts) {
	if (strtoupper($tts['mode']) == "HTTP") {
		$http = $tts;
		$host = parse_url($http['url'],PHP_URL_HOST);
		if (strpos(strtolower($http['url']),"flite") !== false) $engine = "Flite";
	}
	elseif (strtoupper($tts['mode']) == "MRCP") $mrcp = $tts;
	if ($tts['selected']) {
		$mode = $tts['mode'];
	}
}

?>

<h2>TTS Engine</h2>
<div>
	<table>
		<tr>
			<td>The current TTS Engine that you are using is <b><?php echo ($mode == "HTTP") ? $engine : "MRCP"?></b> and it is located in <b><?php echo ($mode == "HTTP") ? $host : (($mrcp['remoteIP'] == "NULL") ? "" : $mrcp['remoteIP'])?></b>.</td>
		</tr>
	</table>
</div>
