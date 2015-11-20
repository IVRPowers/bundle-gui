<?php
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

if (isset($_POST['submited'])) {
	
	$mrcp = array(
		"ipLocal"			=>	trim($_POST['ipLocal']),
		"ipServerSIP"		=>	trim($_POST['ipServerSIP']),
		"portServerSIP"		=>	trim($_POST['portServerSIP']),
		"ipServerRTSP"		=>	trim($_POST['ipServerRTSP']),
		"portServerRTSP"	=>	trim($_POST['portServerRTSP']),
		"mrcpVersion"		=>	trim($_POST['mrcpVersion']),
		"grammarFormat"		=>	trim($_POST['grammarFormat'])
	);
	updateMRCP($mrcp);
	needreload();
	
}

$mrcp = getMRCP();

?>

<h2>ASR Resources Configuration</h2>

<br>

<form id="mrcp" action="config.php?display=asr" method="post">
<input type="hidden" name="submited" value="submited">
	<div>
		<table style="width: 630px;">
			<tr>
				<td colspan="2"><h5>General Settings<hr></h5></td>
			</tr>
			<tr>
				<td style="width: 25%;"><a href="#" class="info">Local IP <span>The private IP of the network interface.</span></a></td>
				<td style="width: 75%;">
					<input type="text" name="ipLocal" id="ipLocal" style="width: 180px; margin-left: 20px;" value="<?php echo (isset($mrcp['ipLocal']) && $mrcp['ipLocal'] != "NULL") ? $mrcp['ipLocal'] : ""?>">
					<span id="errLocalIP" style="color: red"></span>
				</td>				
			</tr>
			<tr>
				<td style="width: 25%"><a href="#" class="info">MRCP Version <span>The version of MRCP to use.</span></a></td>
				<td style="width: 75%;">
					<select name="mrcpVersion" style="width: 180px; margin-left: 20px;" onChange="toggleSettings()">
						<option value="V1" <?php echo ($mrcp['mrcpVersion'] == "V1") ? "selected" : ""?>>V1</option>
						<option value="V2" <?php echo ($mrcp['mrcpVersion'] == "V2") ? "selected" : ""?>>V2</option>
					</select>
				</td>
			</tr>
			<tr class="sip toggle">
				<td colspan="2"><h5>SIP Settings<hr></h5></td>
			</tr>
			<tr class="sip toggle">
				<td style="width: 25%;"><a href="#" class="info">Server IP <span>The IP of the server where the MRCP server is.</span></a></td>
				<td style="width: 75%;"><input type="text" name="ipServerSIP" style="width: 180px; margin-left: 20px;" value="<?php echo (isset($mrcp['ipServerSIP']) && $mrcp['ipServerSIP'] != "NULL") ? $mrcp['ipServerSIP'] : ""?>"></td>
			</tr>
			<tr class="sip toggle">
				<td style="width: 25%;"><a href="#" class="info">Server Port <span>The Port for MRCP of the server where the MRCP server is.</span></a></td>
				<td style="width: 75%;"><input type="text" name="portServerSIP" style="width: 180px; margin-left: 20px;" value="<?php echo (isset($mrcp['portServerSIP']) && $mrcp['portServerSIP'] != "NULL" && $mrcp['portServerSIP'] != "0") ? $mrcp['portServerSIP'] : ""?>"></td>
			</tr>	
			<tr class="rtsp toggle">
				<td colspan="2"><h5>RTSP Settings<hr></h5></td>
			</tr>
			<tr class="rtsp toggle">
				<td style="width: 25%;"><a href="#" class="info">Server IP <span>The IP of the server where the MRCP server is.</span></a></td>
				<td style="width: 75%;"><input type="text" name="ipServerRTSP" style="width: 180px; margin-left: 20px;" value="<?php echo (isset($mrcp['ipServerRTSP']) && $mrcp['ipServerRTSP'] != "NULL") ? $mrcp['ipServerRTSP'] : ""?>"></td>
			</tr>
			<tr class="rtsp toggle">
				<td style="width: 25%;"><a href="#" class="info">Server Port <span>The Port for MRCP of the server where the MRCP server is.</span></a></td>
				<td style="width: 75%;"><input type="text" name="portServerRTSP" style="width: 180px; margin-left: 20px;" value="<?php echo (isset($mrcp['portServerRTSP']) && $mrcp['portServerRTSP'] != "NULL" && $mrcp['portServerRTSP'] != "0") ? $mrcp['portServerRTSP'] : ""?>"></td>
			</tr>
			<tr>
				<td colspan="2"><h5>Extra ASR Configuration<hr></h5></td>
			</tr>
			<tr>
				<td style="width: 25%"><a href="#" class="info">Grammar Format <span>The grammar format that the ASR server expects.</span></a></td>
				<td style="width: 75%;">
					<select name="grammarFormat" style="width: 180px; margin-left: 20px;">
						<option value="bnf" <?php echo ($mrcp['grammarFormat'] == "bnf") ? "selected" : ""?>>bnf</option>
						<option value="gram" <?php echo ($mrcp['grammarFormat'] == "gram") ? "selected" : ""?>>gram</option>
						<option value="grm" <?php echo ($mrcp['grammarFormat'] == "grm") ? "selected" : ""?>>grm</option>
						<option value="grxml" <?php echo ($mrcp['grammarFormat'] == "grxml") ? "selected" : ""?>>grxml</option>
						<option value="srgs" <?php echo ($mrcp['srgsmarFormat'] == "srgs") ? "selected" : ""?>>srgs</option>
						<option value="txt" <?php echo ($mrcp['grammarFormat'] == "txt") ? "selected" : ""?>>txt</option>
					</select>
				</td>
			</tr>			
			<tr>
				<td colspan="2"><br><hr></td>
			</tr>
			<tr>
				<td colspan="2"><span style="font-style: italic; font-size: 0.8em;">To make this modifications effective you need to "Save" the canges, then press the "Apply Config" button and then the "Restart Interpreter".</span></td>			
			</tr>
			<tr>
				<td colspan="2"><button id="saveButton" type="button" style="width: 80px" onclick="save();">Save</button>&nbsp;&nbsp;&nbsp;<button type="reset" style="width: 80px">Discard</button></td>
			</tr>		
		</table>
	</div>
</form>

<style>

<?php echo ($mrcp['mrcpVersion'] == "V1") ? ".sip" : ".rtsp"?> {

	display: none;

}

</style>

<script type="text/javascript">



function save() {

	document.getElementById("mrcp").submit();
	
}

function toggleSettings() {	

	$(".toggle").toggle();
	
}

$("#ipLocal").on('keyup',function() {

	$("#saveButton").prop("disabled",false);
	$("#errLocalIP").html("");
	var value = $(this).val().replace(/\s+/g, '');;
	if (value == "localhost" || value == "127.0.0.1" || value == "0.0.0.0") {
		$("#errLocalIP").html("&nbsp;&nbsp;" + value + " is not allowed");
		$("#saveButton").prop("disabled",true);
	}

}); 


</script>




