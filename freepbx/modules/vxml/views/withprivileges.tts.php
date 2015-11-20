<?php 

if (isset($_POST['ttsMode'])) {

	if ($_POST['ttsMode'] == "HTTP") {
		$updateQuery = "UPDATE tts SET selected=true, url='".$_POST['url']."', maxage='".$_POST['maxage']."', ssml='".$_POST['ssml']."' WHERE mode='HTTP'";
		updateTTS($updateQuery);
		$updateQuery = "UPDATE tts SET selected=false, maxage='".$_POST['maxage']."', ssml='".$_POST['ssml']."' WHERE mode='MRCP'";
		updateTTS($updateQuery);
	} elseif ($_POST['ttsMode'] == "MRCP") {
		$updateQuery = "UPDATE tts SET selected=true, localIP='".$_POST['ipLocal']."', localPORT='".$_POST['portLocal']."', remoteIP='".$_POST['ipRemote']."', remotePORT='".$_POST['portRemote']."', maxage='".$_POST['maxage']."', ssml='".$_POST['ssml']."'".(isset($_POST['sameIP']) ? ", useInASR=true" : ", useInASR=false")." WHERE mode='MRCP'";
		updateTTS($updateQuery);
		$updateQuery = "UPDATE tts SET selected=false, maxage='".$_POST['maxage']."', ssml='".$_POST['ssml']."' WHERE mode='HTTP'";
		updateTTS($updateQuery);
		if (isset($_POST['sameIP'])) {
			$asrmrcp = getMRCP();
			$asrmrcp['ipLocal'] = $_POST['ipLocal'];
			updateMRCP($asrmrcp);
		}
	}
	changeTTSEngine();
	needreload();

}

$ttsList = getTTS();

$mode = "HTTP";
foreach ($ttsList as $tts) {
	if (strtoupper($tts['mode']) == "HTTP") $http = $tts;
	elseif (strtoupper($tts['mode']) == "MRCP") $mrcp = $tts; 
	if ($tts['selected']) {
		$mode = $tts['mode'];
	}
}

?>


<h2>TTS Engine Configuration</h2>

<br>
	
<form id="tts" action="config.php?display=tts" method="post">
	<div>
		<table style="width: 600px;">
			<tr>
				<td style="width: 20%;">TTS Mode: </td>
				<td style="width: 80%;">
					<select name="ttsMode" id="ttsMode" style="width: 180px; margin-left: 21px;">
						<option value="HTTP" <?php if (strtoupper($mode) == "HTTP") echo "selected" ?>>HTTP</option>
						<option value="MRCP" <?php if (strtoupper($mode) == "MRCP") echo "selected" ?>>MRCP</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2"><hr></td>
			</tr>
			<tr class="optionsHTTP" style="display: <?php echo ((strtoupper($mode) == "HTTP") ? "table-row" : "none")?>">
				<td style="width: 20%;"><a href="#" class="info">URL <span>The URL to the tts script.</span></a></td>
				<td style="width: 80%;"><input type="text" name="url" style="width: 180px; margin-left: 20px;" value="<?php echo (isset($http['url']) && $http['url'] != "NULL") ? $http['url'] : ""?>"> <span id="errURL" style="color: red; font-size: 0.8em;"></span></td>
			</tr>
			<tr class="optionsMRCP" style="display: <?php echo ((strtoupper($mode) == "MRCP") ? "table-row" : "none")?>">
				<td style="width: 20%;"><a href="#" class="info">Local IP <span>The private IP of the network interface.</span></a></td>
				<td style="width: 80%;"><input type="text" name="ipLocal" style="width: 180px; margin-left: 20px;" value="<?php echo (isset($mrcp['localIP']) && $mrcp['localIP'] != "NULL") ? $mrcp['localIP'] : ""?>">&nbsp;&nbsp;<input type="checkbox" name="sameIP" value="yes" <?php echo (isset($mrcp['useInASR']) && $mrcp['useInASR']) ? "checked" : ""?>><a href="#" class="info"><span>Check this if you want to use this IP as the Local IP for the ASR as well.</span></a> <span id="errLocalIP" style="color: red; font-size: 0.8em;"></span></td>
			</tr>
			<tr class="optionsMRCP" style="display: <?php echo ((strtoupper($mode) == "MRCP") ? "table-row" : "none")?>">
				<td style="width: 20%;">Local Port </td>
				<td style="width: 80%;"><input type="text" name="portLocal" style="width: 180px; margin-left: 20px;" readonly="readonly" value="5061"></td>
			</tr>
			<tr class="optionsMRCP" style="display: <?php echo ((strtoupper($mode) == "MRCP") ? "table-row" : "none")?>">
				<td style="width: 20%;">Remote IP </td>
				<td style="width: 80%;"><input type="text" name="ipRemote" style="width: 180px; margin-left: 20px;" value="<?php echo (isset($mrcp['remoteIP']) && $mrcp['remoteIP'] != "NULL") ? $mrcp['remoteIP'] : ""?>"> <span id="errRemoteIP" style="color: red; font-size: 0.8em;"></span></td>
			</tr>
			<tr class="optionsMRCP" style="display: <?php echo ((strtoupper($mode) == "MRCP") ? "table-row" : "none")?>">
				<td style="width: 20%;">Remote Port </td>
				<td style="width: 80%;"><input type="text" name="portRemote" style="width: 180px; margin-left: 20px;" value="<?php echo (isset($mrcp['remotePORT']) && $mrcp['remotePORT'] != "NULL") ? $mrcp['remotePORT'] : ""?>"> <span id="errRemotePort" style="color: red; font-size: 0.8em;"></span></td>
			</tr>			
			<tr>
				<td style="width: 20%;"><a href="#" class="info">Maxage <span>The parameter 'maxage' force to refresh the cache after sometime. The value 0, disable the caching, the HTTP request will be use for each prompt. The value -1 define infinite age. If the file exist in the cache, it will be always used from the cache directly.</span></a></td>
				<td style="width: 80%;"><input type="text" name="maxage" style="width: 180px; margin-left: 20px;" value="<?php echo isset($http['maxage']) ? $http['maxage'] : ""?>"> <span id="errMaxage" style="color: red; font-size: 0.8em;"></span></td>
			</tr>
			<tr>
				<td style="width: 20%;"><a href="#" class="info">SSML <span>The option 'ssml' for to send the text as a SSML/XML well formated content (with &lt;&quest;xml&gt; and &lt;ssml&gt; roots tags)</span></a></td>
				<td style="width: 80%;"><input type="text" name="ssml" style="width: 180px; margin-left: 20px;" value="<?php echo isset($http['ssml']) ? $http['ssml'] : ""?>"> <span id="errSSML" style="color: red; font-size: 0.8em;"></span></td>
			</tr>
			<tr>
				<td colspan="2"><hr></td>
			</tr>
			<tr>
				<td colspan="2"><span style="font-style: italic; font-size: 0.8em;">To make this modifications effective you need to "Save" the canges, then press the "Apply Config" button and then the "Restart Interpreter".</span></td>			
			</tr>
			<tr>
				<td colspan="2"><button type="button" style="width: 80px" onclick="save();">Save</button>&nbsp;&nbsp;&nbsp;<button type="reset" style="width: 80px">Discard</button></td>
			</tr>
		</table>
	</div>
</form>
<script type="text/javascript">

$("#ttsMode").change(function() {
	$(".optionsHTTP").hide();
	$(".optionsMRCP").hide();
	$(".options" + $(this).val().toUpperCase()).show();	
});

function save() {

	document.getElementById("errRemotePort").innerHTML = "";
	document.getElementById("errRemoteIP").innerHTML = "";
	document.getElementById("errLocalIP").innerHTML = "";
	document.getElementById("errURL").innerHTML = "";
	document.getElementById("errMaxage").innerHTML = "";
	document.getElementById("errSSML").innerHTML = "";
	
	var form = document.getElementById("tts");
	var mode = $('#ttsMode').val();
	
	var maxage = form.elements['maxage'].value;
	var ssml = form.elements['ssml'].value;
	
	var errMaxage = 0;
	var errSSML = 0;

	if (maxage == "" || maxage == null) {

		document.getElementById("errMaxage").innerHTML = "You must specify a value";
		errMaxage = 1;
		
	} else {

		if (isNaN(maxage)) {

			document.getElementById("errMaxage").innerHTML = "The value must be an integer";
			errMaxage = 1;

		} else {

			if (maxage < -1) {

				document.getElementById("errMaxage").innerHTML = "The value must be equal or greater than -1";
				errMaxage = 1;

			}
						
		}
		
	}

	if (ssml == "" || ssml == null) {

		document.getElementById("errSSML").innerHTML = "You must specify a value";
		errSSML = 1;
		
	} else {

		if (isNaN(ssml)) {

			document.getElementById("errSSML").innerHTML = "The value must be an integer";
			errSSML = 1;

		} else {

			if (ssml < 0) {

				document.getElementById("errSSML").innerHTML = "The value must be equal or greater than 0";
				errSSML = 1;

			}
						
		}
		
	}	
	
	if (mode == "HTTP") {

		var url = form.elements['url'].value;
		var errURL = 0;

		if (url == "" || url == null) {

			document.getElementById("errURL").innerHTML = "You must specify an URL";
			errURL = 1;
			
		}
		
		if (errMaxage == 0 && errSSML == 0 && errURL == 0) form.submit();

	} else if (mode == "MRCP") {

		var remotePORT = form.elements['portRemote'].value;
		var remoteIP = form.elements['ipRemote'].value;
		var localIP = form.elements['ipLocal'].value;
		var errRemotePort = 0;
		var errRemoteIP = 0;
		var errLocalIP = 0;

		if (remotePORT == "" || remotePORT == null) {

			document.getElementById("errRemotePort").innerHTML = "You must specify a value";
			errRemotePort = 1;
			
		} else {

			if (isNaN(remotePORT)) {

				document.getElementById("errRemotePort").innerHTML = "The value must be an integer";
				errRemotePort = 1;

			} else {

				if (remotePORT < 0) {

					document.getElementById("errRemotePort").innerHTML = "The value must be equal or greater than 0";
					errRemotePort = 1;

				}
							
			}
			
		}

		if (remoteIP == "" || remoteIP == null) {

			document.getElementById("errRemoteIP").innerHTML = "You must specify an IP";
			errRemoteIP = 1;

		}

		if (localIP == "" || localIP == null) {

			document.getElementById("errLocalIP").innerHTML = "You must specify an IP";
			errLocalIP = 1;

		}

		if (errMaxage == 0 && errRemotePort == 0 && errSSML == 0 && errRemoteIP == 0 && errLocalIP == 0) form.submit();

	}
	
}

</script>







