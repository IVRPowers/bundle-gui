<?php
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

global $astman;
if (isset($_POST['form'])) {
	$exp = trim($_POST['expiration']);
	if (empty($exp)) {
		$license = array(
			"max" => $_POST['maxsessions'],
			"video" => ucfirst($_POST['video']),
			"texttospeech" => ucfirst($_POST['tts']),
			"speech" => ucfirst($_POST['speech']),
			"externals" => ucfirst($_POST['xtras']),
			"dialer" => ucfirst($_POST['dialer']),
			"chanh323" => ucfirst($_POST['h323']),
			"chanrtmp" => ucfirst($_POST['rtmp']),
			"key" => $_POST['key']
		);
	} else {
		$license = array(
			"max" => $_POST['maxsessions'],
			"video" => ucfirst($_POST['video']),
			"texttospeech" => ucfirst($_POST['tts']),
			"speech" => ucfirst($_POST['speech']),
			"externals" => ucfirst($_POST['xtras']),
			"dialer" => ucfirst($_POST['dialer']),
			"chanh323" => ucfirst($_POST['h323']),
			"chanrtmp" => ucfirst($_POST['rtmp']),
			"expiration" => $_POST['expiration'],
			"key" => $_POST['key']
		);
	}
	modifyLicense($license,$_POST['prevkey']);
	/*
	if ($astman) {
		$out = $astman->send_request('Command',array('Command'=>"vxml reload"));
	}
	$error = 1;
	if ($astman) {
		$out = $astman->send_request('Command',array('Command'=>"vxml show license"));
		$out = explode("\n",$out['data']);
	}
	$max_sessions_ast = trim(substr($out[12],strpos($out[12],":") + 1));
	$max_sessions_file = trim(shell_exec("sed -e '1,/\[license\]/d' /etc/asterisk/vxml.conf | grep max | head -n1 | cut -d'=' -f2"));
	if ($max_sessions_ast == $max_sessions_file) {
		$error = 0;
	}
	sleep(2);*/
}

$license = getLicense();
if ($astman) {
	$out = $astman->send_request('Command',array('Command'=>"vxml show license"));
	$out = explode("\n",$out['data']);
}
$version = substr($out[1],strpos($out[1],":") + 1);
$cvs = substr($out[3],strpos($out[3],":") + 1);
$gcc = substr($out[4],strpos($out[4],":") + 1);
$arch = substr($out[5],strpos($out[5],":") + 1);
$target = substr($out[6],strpos($out[6],":") + 1);
$asterisk = substr($out[7],strpos($out[7],":") + 1);
$optsum = substr($out[8],strpos($out[8],":") + 1);
$date = substr($out[9],strpos($out[9],":") + 1);
$code = substr($out[10],strpos($out[10],":") + 1);
$key = $license['licensekey'];
$sessions = $license['max'];
$expiration = $license['expiration'];
$video = $license['video'];
if ($video == "Yes") {
	$video_checked_yes = "checked";
	$video_checked_no = "";
} else {
	$video_checked_yes = "";
	$video_checked_no = "checked";
}
$tts = $license['texttospeech'];
if ($tts == "Yes") {
	$tts_checked_yes = "checked";
	$tts_checked_no = "";
} else {
	$tts_checked_yes = "";
	$tts_checked_no = "checked";
}
$xtras = $license['externals'];
if ($xtras == "Yes") {
	$xtras_checked_yes = "checked";
	$xtras_checked_no = "";
} else {
	$xtras_checked_yes = "";
	$xtras_checked_no = "checked";
}
$dialer = $license['dialer'];
if ($dialer == "Yes") {
	$dialer_checked_yes = "checked";
	$dialer_checked_no = "";
} else {
	$dialer_checked_yes = "";
	$dialer_checked_no = "checked";
}
$h323 = $license['chanh323'];
if ($h323 == "Yes") {
	$h323_checked_yes = "checked";
	$h323_checked_no = "";
} else {
	$h323_checked_yes = "";
	$h323_checked_no = "checked";
}
$rtmp = $license['chanrtmp'];
if ($rtmp == "Yes") {
	$rtmp_checked_yes = "checked";
	$rtmp_checked_no = "";
} else {
	$rtmp_checked_yes = "";
	$rtmp_checked_no = "checked";
}
$speech = $license['speech'];
if ($speech == "Yes") {
	$speech_checked_yes = "checked";
	$speech_checked_emulation = "";
	$speech_checked_automatic = "";
	$speech_checked_debug = "";
	$speech_checked_no = "";
} elseif ($speech == "emulation") {
	$speech_checked_yes = "";
	$speech_checked_emulation = "checked";
	$speech_checked_automatic = "";
	$speech_checked_debug = "";
	$speech_checked_no = "";
} elseif ($speech == "automatic") {
	$speech_checked_yes = "";
	$speech_checked_emulation = "";
	$speech_checked_automatic = "checked";
	$speech_checked_debug = "";
	$speech_checked_no = "";
} elseif ($speech == "debug") {
	$speech_checked_yes = "";
	$speech_checked_emulation = "";
	$speech_checked_automatic = "";
	$speech_checked_debug = "checked";
	$speech_checked_no = "";
} else {
	$speech_checked_yes = "";
	$speech_checked_emulation = "";
	$speech_checked_automatic = "";
	$speech_checked_debug = "";
	$speech_checked_no = "checked";
}


?>

<h2>VoiceXML License</h2>

<form autocomplete="off" name="license" action="config.php?display=vxmllicense" method="post">
	<input type="hidden" id="form" name="form" value="form">
	<input type="hidden" id="prevkey" name="prevkey" value="<?php echo $key?>">
	<table width="600px">
		<tbody>
			<?php if ($error == 1) {?>
			<tr>
				<td colspan="2"><span style="color: red">INVALID LICENSE!, check your parameters and try again.</span></td>
			</tr>
			<?php }?>
			<tr>
				<td colspan="2"><h5>System information<hr></h5></td>
			</tr>
			<tr>
				<td colspan="2">
					<table width="350px">
						<tr>
							<td>Version</td>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
							<td><?php echo $version?></td>
						</tr>
						<tr>
							<td>Build</td>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
							<td></td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;CVS Revision</td>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
							<td><?php echo $cvs?></td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;Gcc</td>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
							<td><?php echo $gcc?></td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;Arch</td>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
							<td><?php echo $arch?></td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;Target</td>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
							<td><?php echo $target?></td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;Asterisk</td>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
							<td><?php echo $asterisk?></td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;Option sum</td>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
							<td><?php echo $optsum?></td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;Date</td>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
							<td><?php echo $date?></td>
						</tr>
						<tr>
							<td>Code</td>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
							<td><?php echo $code?></td>
						</tr>
					</table>
				</td>
			</tr>			
			<tr>
				<td colspan="2"><h5>License <i>(The form is already filled with the existing values in the license configuration)</i><hr></h5></td>				
			</tr>
			<tr>
				<td style="width: 30%;">Key<span class="help">"?"<span style="display: none;">Introduce the key value for your license.<br>Leave this field empty if you want to try a license with just 1 port for unlimited time.</span></span></td>
				<td style="width: 70%;"><input type="text" id="key" name="key" style="width: 250px;" value="<?php echo $key?>"></td>
			</tr>
			<tr>
				<td style="width: 30%;">Max sessions<span class="help">"?"<span style="display: none;">Introduce the number of max sessions of your license.<br>Set to 1 if you want to try the free license of 1 port.</span></span></td>
				<td style="width: 70%;"><input type="text" id="maxsessions" name="maxsessions" style="width: 250px;" value="<?php echo $sessions?>"></td>
			</tr>
				<td style="width: 30%;">Expiration date<span class="help">"?"<span style="display: none;">Introduce the expiration date in the format yymmdd. For example the 2nd of January of 2015 will be 150102</span></span></td>
				<td style="width: 70%;"><input type="text" id="expiration" name="expiration" style="width: 250px;" value="<?php echo $expiration?>"></td>
			</tr>
			<tr>
				<td style="width: 30%;">Video<span class="help">"?"<span style="display: none;">Video activation for video IVR services<./span></span></td>
				<td style="width: 70%;">
					<table style="width: 100%; height: 20px;">
        				<tbody>
        					<tr>
          						<td>
									<span class="radioset ui-buttonset">
            							<input id="video-yes" type="radio" name="video" value="yes" <?php echo $video_checked_yes?> class="ui-helper-hidden-accessible">
            							<label for="video-yes" class="ui-button ui-widget ui-button-text-only ui-corner-left" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Yes</span></label>
            							<input id="video-no" type="radio" name="video" value="no" <?php echo $video_checked_no?> class="ui-helper-hidden-accessible">
            							<label for="video-no" class="ui-button ui-widget ui-button-text-only ui-corner-right" role="button"><span style="padding: .1em .5em;" class="ui-button-text">No</span></label>
									</span>
          						</td>
        					</tr>
      					</tbody>
      				</table>
				</td>
			</tr>
			<tr>
				<td style="width: 30%;">Text-to-Speech<span class="help">"?"<span style="display: none;">Text-to-speech activation to connect a TTS engine.</span></span></td>
				<td style="width: 70%;">
					<table width="100%">
        				<tbody>
        					<tr>
          						<td>
									<span class="radioset ui-buttonset">
            							<input id="tts-yes" type="radio" name="tts" value="yes" <?php echo $tts_checked_yes?> class="ui-helper-hidden-accessible">
            							<label for="tts-yes" class="ui-button ui-widget ui-button-text-only ui-corner-left" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Yes</span></label>
            							<input id="tts-no" type="radio" name="tts" value="no" <?php echo $tts_checked_no?> class="ui-helper-hidden-accessible">
            							<label for="tts-no" class="ui-button ui-widget ui-button-text-only ui-corner-right" role="button"><span style="padding: .1em .5em;" class="ui-button-text">No</span></label>
									</span>
          						</td>
        					</tr>
      					</tbody>
      				</table>
				</td>
			</tr>
			<tr>
				<td style="width: 30%;">Speech<span class="help">"?"<span style="display: none;">Speech recognition activation to connect an ASR engine.</span></span></td>
				<td style="width: 70%;">
					<table width="100%">
        				<tbody>
        					<tr>
          						<td>
									<span class="radioset ui-buttonset">
            							<input id="speech-yes" type="radio" name="speech" value="yes" <?php echo $speech_checked_yes?> class="ui-helper-hidden-accessible">
            							<label for="speech-yes" class="ui-button ui-widget ui-button-text-only ui-corner-left" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Yes</span></label>
            							<input id="speech-emulation" type="radio" name="speech" value="emulation" <?php echo $speech_checked_emulation?> class="ui-helper-hidden-accessible">
            							<label for="speech-emulation" class="ui-button ui-widget ui-button-text-only" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Emulation</span></label>
            							<input id="speech-automatic" type="radio" name="speech" value="automatic" <?php echo $speech_checked_automatic?> class="ui-helper-hidden-accessible">
            							<label for="speech-automatic" class="ui-button ui-widget ui-button-text-only" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Automatic</span></label>
            							<input id="speech-debug" type="radio" name="speech" value="debug" <?php echo $speech_checked_debug?> class="ui-helper-hidden-accessible">
            							<label for="speech-debug" class="ui-button ui-widget ui-button-text-only" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Debug</span></label>
            							<input id="speech-no" type="radio" name="speech" value="no" <?php echo $speech_checked_no?> class="ui-helper-hidden-accessible">
            							<label for="speech-no" class="ui-button ui-widget ui-button-text-only ui-corner-right" role="button"><span style="padding: .1em .5em;" class="ui-button-text">No</span></label>
									</span>
          						</td>
        					</tr>
      					</tbody>
      				</table>
				</td>
			</tr>
			<tr>
				<td style="width: 30%;">Externals<span class="help">"?"<span style="display: none;">External Xtras* modules activation.</span></span></td>
				<td style="width: 70%;">
					<table width="100%">
        				<tbody>
        					<tr>
          						<td>
									<span class="radioset ui-buttonset">
            							<input id="xtras-yes" type="radio" name="xtras" value="yes" <?php echo $xtras_checked_yes?> class="ui-helper-hidden-accessible">
            							<label for="xtras-yes" class="ui-button ui-widget ui-button-text-only ui-corner-left" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Yes</span></label>
            							<input id="xtras-no" type="radio" name="xtras" value="no" <?php echo $xtras_checked_no?> class="ui-helper-hidden-accessible">
            							<label for="xtras-no" class="ui-button ui-widget ui-button-text-only ui-corner-right" role="button"><span style="padding: .1em .5em;" class="ui-button-text">No</span></label>
									</span>
          						</td>
        					</tr>
      					</tbody>
      				</table>
				</td>
			</tr>
			<tr>
				<td style="width: 30%;">Dialer<span class="help">"?"<span style="display: none;">Dialer for outbound calls activation.</span></span></td>
				<td style="width: 70%;">
					<table width="100%">
        				<tbody>
        					<tr>
          						<td>
									<span class="radioset ui-buttonset">
            							<input id="dialer-yes" type="radio" name="dialer" value="yes" <?php echo $dialer_checked_yes?> class="ui-helper-hidden-accessible">
            							<label for="dialer-yes" class="ui-button ui-widget ui-button-text-only ui-corner-left" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Yes</span></label>
            							<input id="dialer-no" type="radio" name="dialer" value="no" <?php echo $dialer_checked_no?> class="ui-helper-hidden-accessible">
            							<label for="dialer-no" class="ui-button ui-widget ui-button-text-only ui-corner-right" role="button"><span style="padding: .1em .5em;" class="ui-button-text">No</span></label>
									</span>
          						</td>
        					</tr>
      					</tbody>
      				</table>
				</td>
			</tr>
			<tr>
				<td style="width: 30%;">Chan H323<span class="help">"?"<span style="display: none;">H323 specific module activation.</span></span></td>
				<td style="width: 70%;">
					<table width="100%">
        				<tbody>
        					<tr>
          						<td>
									<span class="radioset ui-buttonset">
            							<input id="h323-yes" type="radio" name="h323" value="yes" <?php echo $h323_checked_yes?> class="ui-helper-hidden-accessible">
            							<label for="h323-yes" class="ui-button ui-widget ui-button-text-only ui-corner-left" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Yes</span></label>
            							<input id="h323-no" type="radio" name="h323" value="no" <?php echo $h323_checked_no?> class="ui-helper-hidden-accessible">
            							<label for="h323-no" class="ui-button ui-widget ui-button-text-only ui-corner-right" role="button"><span style="padding: .1em .5em;" class="ui-button-text">No</span></label>
									</span>
          						</td>
        					</tr>
      					</tbody>
      				</table>
				</td>
			</tr>
			<tr>
				<td style="width: 30%;">Chan RTMP<span class="help">"?"<span style="display: none;">Flash/RTMP Server Channel module activation.</span></span></td>
				<td style="width: 70%;">
					<table width="100%">
        				<tbody>
        					<tr>
          						<td>
									<span class="radioset ui-buttonset">
            							<input id="rtmp-yes" type="radio" name="rtmp" value="yes" <?php echo $rtmp_checked_yes?> class="ui-helper-hidden-accessible">
            							<label for="rtmp-yes" class="ui-button ui-widget ui-button-text-only ui-corner-left" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Yes</span></label>
            							<input id="rtmp-no" type="radio" name="rtmp" value="no" <?php echo $rtmp_checked_no?> class="ui-helper-hidden-accessible">
            							<label for="rtmp-no" class="ui-button ui-widget ui-button-text-only ui-corner-right" role="button"><span style="padding: .1em .5em;" class="ui-button-text">No</span></label>
									</span>
          						</td>
        					</tr>
      					</tbody>
      				</table>
				</td>
			</tr>
			<tr>
				<td><br><input type="submit" value="Apply License"></td>
				<td></td>
			</tr>
		</tbody>	
	</table>
</form>


