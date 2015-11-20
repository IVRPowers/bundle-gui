<?php
if (isset($_POST['form']) && $_POST['form'] == "editsettings") {
	$settings = array (
			"recordsilence" => ucfirst($_POST['recordsilence']),
			"threshold" => trim($_POST['threshold']),
			"wavdefaultcodec" => $_POST['wavdefaultcodec'],
			"debug" => ucfirst($_POST['debug']),
			"priorityevents" => ucfirst($_POST['priorityevents']),
			"dialformataudio" => trim($_POST['dialformataudio']),
			"cachetimeout" => trim($_POST['cachetimeout']),
			"monitor" => trim($_POST['monitor'])		
	);
	modifyConfiguration($settings);
	needreload();
}

$settings = getConfiguration('*');

$tabindex = 0;
?>
<form autocomplete="off" name="settings" id="settings" action="config.php?display=vxmlsettings" method="post">
	<input type="hidden" id="form" name="form" value="editsettings">
	<table width="700px">
		<tr>
			<td><h5>General<hr></h5></td>
		</tr>
		<tr>
			<td>
				<table width="600px">
					<tr>
						<td style="width: 27%;">&nbsp;&nbsp;<a href="#" class="info">Record silence <span>Yes allows to record this time before the VAD detection. No force to record all the audio without waiting for the voice detection.</span></a></td>
						<td>
							<table style="width: 100%; height: 20px;">
		        				<tbody>
		        					<tr>
		          						<td>
											<span class="radioset">
		            							<input id="recordsilence_yes" type="radio" name="recordsilence" value="yes" <?php echo (trim($settings['recordsilence']) == "Yes") ? "checked" : ""?> tabindex="<?php echo $tabindex++?>">
		            								<label for="recordsilence_yes">Yes</label>
		            							<input id="recordsilence_no" type="radio" name="recordsilence" value="no" <?php echo (trim($settings['recordsilence']) != "Yes") ? "checked" : ""?> tabindex="<?php echo $tabindex++?>">
		            								<label for="recordsilence_no">No</label>
											</span>
		          						</td>
		        					</tr>
		      					</tbody>
	      					</table>
						</td>
					</tr>
					<tr>
						<td style="width: 27%;">&nbsp;&nbsp;<a href="#" class="info">Threshold <span>Set the default threshold for silence detection.</span></a></td>
						<td><input type="text" id="threshold" name="threshold" style="width: 140px;" value="<?php echo isset($settings['threshold']) ? $settings['threshold'] : '256' ?>" tabindex="<?php echo $tabindex++?>"></td>
						<td><span style="color: red" id="errThreshold"></span></td>
					</tr>					
					<tr>
						<td style="width: 27%;">&nbsp;&nbsp;<a href="#" class="info">WAV default codec <span>Codec use for record.</span></a></td>
						<td>
							<table style="width: 100%; height: 20px;">
		        				<tbody>
		        					<tr>
		          						<td>
											<span class="radioset">
		            							<input id="wavcodec_pcm" type="radio" name="wavdefaultcodec" value="pcm" <?php echo (trim($settings['wavdefaultcodec']) != "gsm") ? "checked" : ""?> tabindex="<?php echo $tabindex++?>">
		            								<label for="wavcodec_pcm">pcm</label>
		            							<input id="wavcodec_gsm" type="radio" name="wavdefaultcodec" value="gsm" <?php echo (trim($settings['wavdefaultcodec']) == "gsm") ? "checked" : ""?> tabindex="<?php echo $tabindex++?>">
		            								<label for="wavcodec_gsm">gsm</label>
											</span>
		          						</td>
		        					</tr>
		      					</tbody>
	      					</table>
						</td>
					</tr>
					<tr>
						<td style="width: 27%;">&nbsp;&nbsp;<a href="#" class="info">Debug <span>Enable or disable the logs from the Telephony module and the Interpreter.</span></a></td>
						<td>
							<table style="width: 100%; height: 20px;">
		        				<tbody>
		        					<tr>
		          						<td>
											<span class="radioset">
		            							<input id="debug_yes" type="radio" name="debug" value="enabled" <?php echo (trim($settings['debug']) == "Enabled") ? "checked" : ""?> tabindex="<?php echo $tabindex++?>">
		            								<label for="debug_yes">Yes</label>
		            							<input id="debug_no" type="radio" name="debug" value="disabled" <?php echo (trim($settings['debug']) != "Enabled") ? "checked" : ""?> tabindex="<?php echo $tabindex++?>">
		            								<label for="debug_no">No</label>
											</span>
		          						</td>
		        					</tr>
		      					</tbody>
	      					</table>
						</td>
					</tr>
					<tr>
						<td style="width: 27%;">&nbsp;&nbsp;<a href="#" class="info">Priority events <span>If using single DTMF events and DTMF inputs/grammars, yes force to check the first DTMF to the DTMF events as soon as it is receive, no will wait full entring or a timeout.</span></a></td>
						<td>
							<table style="width: 100%; height: 20px;">
		        				<tbody>
		        					<tr>
		          						<td>
											<span class="radioset ui-buttonset">
		            							<input id="priorityevents_yes" type="radio" name="priorityevents" value="yes" <?php echo (trim($settings['priorityevents']) != "No") ? "checked" : ""?> tabindex="<?php echo $tabindex++?>">
		            								<label for="priorityevents_yes">Yes</label>
		            							<input id="priorityevents_no" type="radio" name="priorityevents" value="no" <?php echo (trim($settings['priorityevents']) == "No") ? "checked" : ""?> tabindex="<?php echo $tabindex++?>">
		            								<label for="priorityevents_no">No</label>
											</span>
		          						</td>
		        					</tr>
		      					</tbody>
	      					</table>
						</td>
					</tr>
					<tr>
						<td style="width: 27%;">&nbsp;&nbsp;<a href="#" class="info">Dial format audio <span>Format string used to generate outgoing calls (allows to set the channel type and peer for example).</span></a></td>
						<td><input type="text" id="dialformataudio" name="dialformataudio" style="width: 140px;" value="<?php echo isset($settings['dialformataudio']) ? trim($settings['dialformataudio']) : 'SIP/%s' ?>" tabindex="<?php echo $tabindex++?>"></td>
					</tr>
					<tr>
						<td style="width: 27%;">&nbsp;&nbsp;<a href="#" class="info">Cache timeout (s) <span>Execute the cache process each x seconds.</span></a></td>
						<td><input type="text" id="cachetimeout" name="cachetimeout" style="width: 140px;" value="<?php echo isset($settings['cachetimeout']) ? $settings['cachetimeout'] : '60' ?>" tabindex="<?php echo $tabindex++?>"></td>
						<td><span style="color: red;" id="errCacheTimeout"></span></td>
					</tr>
					<tr>
						<td style="width: 27%;">&nbsp;&nbsp;<a href="#" class="info">Call Recording <span>If yes it will record the calls so they can be listened in the CDR. <br>WARNING: This will eventually fill your hard disk if running for a long time.</span></a></td>
						<td>
							<table style="width: 100%; height: 20px;">
		        				<tbody>
		        					<tr>
		          						<td>
											<span class="radioset ui-buttonset">
		            							<input id="monitor_true" type="radio" name="monitor" value="true" <?php echo ($settings['monitor']) ? "checked" : ""?> tabindex="<?php echo $tabindex++?>">
		            								<label for="monitor_true">Yes</label>
		            							<input id="monitor_false" type="radio" name="monitor" value="false" <?php echo (!$settings['monitor']) ? "checked" : ""?> tabindex="<?php echo $tabindex++?>">
		            								<label for="monitor_false">No</label>
											</span>
		          						</td>
		        					</tr>
		      					</tbody>
	      					</table>
						</td>
					</tr>					
				</table>
			</td>
		</tr>	
		<tr>
			<td><br><hr></td>
		</tr>		
		<tr>
			<td>
				<table>
					<tr>
						<td><input type="button" style="width: 80px; height: 25px;" onclick="save();" value="Save"></form></td>
						<td><input type="button" style="width: 80px; height: 25px;" onclick="location.reload();" value="Discard"></td>
					</tr>
				</table>
			</td>
		</tr>					
	</table>

<script type="text/javascript">

	function save() {		

		document.getElementById("errThreshold").innerHTML = "";
		document.getElementById("errCacheTimeout").innerHTML = "";

		var form = document.getElementById("settings");
		var threshold = form.elements["threshold"].value;
		var cachetimeout = form.elements["cachetimeout"].value;

		var sub_threshold = 0; var sub_cachetimeout = 0;

		if (isNaN(threshold)) {
			document.getElementById("errThreshold").innerHTML = "  The value must be a number.";
		} else {
			if (!isInt(threshold)) {
				document.getElementById("errThreshold").innerHTML = "  The value must be an integer.";
			} else {
				if ((threshold < 1) || (threshold > 32767)) {
					document.getElementById("errThreshold").innerHTML = "  The value is not in the correct range [1...32767]";
				} else {
					sub_threshold = 1;
				}
			}
		}

		if (isNaN(cachetimeout)) {
			document.getElementById("errCacheTimeout").innerHTML = "  The value must be a number.";
		} else {
			if (!isInt(cachetimeout)) {
				document.getElementById("errCacheTimeout").innerHTML = "  The value must be an integer.";
			} else {
				if (cachetimeout < -1) {
					document.getElementById("errCacheTimeout").innerHTML = "  The value is not in the correct range [-1,0...]";
				} else {
					sub_cachetimeout = 1;
				}
			}
		}

		if (sub_threshold == 1 && sub_cachetimeout == 1) {
			form.submit();
		}

	}

	function isInt(n) {
		return n % 1 === 0;
	}

</script>