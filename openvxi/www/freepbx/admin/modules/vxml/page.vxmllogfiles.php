<?php 
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$loglist = array(
			'client.log.apache' => trim(shell_exec("grep 'client.log.apache' /etc/vxmld.conf | awk '{ print $3 }'")),
			'client.log.filename' => trim(shell_exec("grep 'client.log.filename' /etc/vxmld.conf | awk '{ print $3 }'")),
			'client.log.voicexml' => trim(shell_exec("grep 'client.log.voicexml' /etc/vxmld.conf | awk '{ print $3 }'"))
			);

$selected = isset($_POST['file']) ? trim($_POST['file']) : $loglist['client.log.filename'];
?>

<h2>VoiceXML Log Files</h2>

<form autocomplete="off" name="logfiles" id="logfiles" method="post" action="config.php?display=vxmllogfiles"  onload="setLogName();">
	<select name="file">
		<?php 
			foreach ($loglist as $log) {
				if ($log == $selected) {
					echo "<option value='$log' selected='selected'>$log</option>";
				} else {
					echo "<option value='$log'>$log</option>";
				}
			}
		?>
	</select>
	<input type="text" name="lines" id="lines" value="<?php echo isset($_POST['lines']) ? trim($_POST['lines']) : "500"?>">
	<input type="submit" value="Show">
	
</form>
<br>

<div id="log" style="background-color: #0f192a; color: white; border-radius: 10px; font-family: 'Courier New', Courier, monospace; font-size: 0.85em; overflow: scroll; padding: 10px;">

	<?php 
		echo highlightVXMLLog(isset($_POST['file']) ? trim($_POST['file']) : $loglist['client.log.filename'],isset($_POST['lines']) ? trim($_POST['lines']) : "500");
	?>

</div>

<script type="text/javascript">

	$(document).ready(function() {
		$('#log').css('max-height',($(window).height() - 0.3*$(window).height()));

		$(window).resize(function() {
			$('#log').css('max-height',($(window).height() - 0.3*$(window).height()));
		})
	});
	
</script>
