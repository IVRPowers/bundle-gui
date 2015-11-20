<?php
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

if (isset($_POST['form'])) {

	if($_POST['form'] == "add") {
		$vxml = array(
				"name" => $_POST['name'],
				"url" => $_POST['url'],
				"max" => $_POST['max'],
				"dialformat" => $_POST['dialformat'],
				"mark" => $_POST['mark'],
				"speech" => $_POST['speech'],
				"speechprovider" => $_POST['speechprovider'],				
				"goto" => "app-blackhole,hangup,1" //$_POST[$_POST['goto0']."0"]
		);
		addVxml($vxml);
	} elseif ($_POST['form'] == "edit") {
		$modifiedvxml = array(
				"name" => $_POST['name'],
				"url" => $_POST['url'],
				"max" => $_POST['max'],
				"dialformat" => $_POST['dialformat'],
				"mark" => $_POST['mark'],
				"speech" => $_POST['speech'],
				"speechprovider" => $_POST['speechprovider'],
				"goto" => "app-blackhole,hangup,1" //$_POST[$_POST['goto0']."0"]
		);
		updateVxml($_POST['prevname'],$modifiedvxml);
	} elseif ($_POST['form'] == "delete") {
		deleteVxml($_POST['name']);
	}
	needreload();

} else {
	if (isset($_GET['action']) && $_GET['action'] == "edit") {
		$edit = 1;
		$vxml = getVxml($_GET['vxml']);
		$editvxml = $vxml[0];
		$speech = $editvxml['speech'];
		if ($speech == "yes") {
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
		} elseif ($speech == "no") {
			$speech_checked_yes = "";
			$speech_checked_emulation = "";
			$speech_checked_automatic = "";
			$speech_checked_debug = "";
			$speech_checked_no = "checked";
		} else {
			$speech_checked_yes = "";
			$speech_checked_emulation = "";
			$speech_checked_automatic = "";
			$speech_checked_debug = "";
			$speech_checked_no = "";
			
		}	
		needreload();
	}
}

$names = getVxmlList("name");
$nameslist = "";
foreach ($names as $name) {
	$nameslist = $nameslist." ".$name['name']; 
}

echo load_view(dirname(__FILE__) . '/views/rnav.vxml.php',array('names' => $names));
?>

<form autocomplete="off" name="general" id="general" action="config.php?display=vxml" method="post">	
	<?php if ($edit) {?>
		<input type="hidden" id="form" name="form" value="edit">
		<input type="hidden" id="prevname" name="prevname" value="<?php echo $editvxml['name']?>">
	<?php } else {?>
		<input type="hidden" id="form" name="form" value="add">
	<?php }?>
	<input type="hidden" id="nameslist" name="nameslist" value="<?php echo $nameslist ?>">
	<table style="width: 670px;">
		<tr>
			<td colspan="2"><h5><?php echo $edit ? "Edit" : "Add"?> Application<hr></h5></td>
		</tr>	
		<tr>
			<td colspan="2"><i>The fields marked with * can not be left in blank.</i><br><br></td>
		</tr>
		<tr>
			<td style="width: 25%;"><a href="#" class="info">Name*<span>Name for this application.</span></a></td>
			<td style="width: 75%;"><input type="text" name="name" id="name" style="width: 250px;" value="<?php echo $editvxml['name'] ? $editvxml['name'] : ""?>"><span id="errName" style="color: red"></span></td>
		</tr>
		<tr>
			<td style="width: 25%;"><a href="#" class="info">URL*<span>{VoiceXML URL} This function indicates the VoiceXML URL of the account.</span></a></td>
			<td style="width: 75%;"><input type="text" id="url" name="url" style="width: 250px;" value="<?php echo $editvxml['url'] ? $editvxml['url'] : ""?>"><span id="errURL" style="color: red"></span></td>
		</tr>
		<tr>
			<td style="width: 25%;"><a href="#" class="info">Max Sessions<span>
				{0...120} This indicates the maximum number of sessions allowed to this account. If there are not enough 
				sessions then the VoiceXML application will generate an error.
			</span></a></td>
			<td style="width: 75%;"><input type="text" id="max" name="max" style="width: 250px;" value="<?php echo $editvxml['max'] ? $editvxml['max'] : ""?>"><span id="errSessions" style="color: red"></span></td>
		</tr>
		<tr>
			<td style="width: 25%;"><a href="#" class="info">Dial Format<span>
				{application(]/%s[)} This is a string to specify the interface and the peer that has been chosen for the transfer. 
				The "%s" will be replaced by the string set in the <transfer> dest attribute. Remember to prefix the dest value 
				with "tel:" to generate the transfer function. Other prefixes have been added to match some of the Asterisk functions, 
				such as conference, call an application, etc. The default value is SIP/%s. This is similar to the general function, 
				but for the account only. If not set, use the general value.
			</span></a></td>
			<td style="width: 75%;"><input type="text" id="dialformat" name="dialformat" style="width: 250px;" value="<?php echo $editvxml['dialformat'] ? $editvxml['dialformat'] : ""?>"></td>
		</tr>
		<tr>
			<td style="width: 25%;"><a href="#" class="info">Mark<span>
				{string/@local/@remote/@id/@param} Set a string mark in the VoiceXML browser traces. The session ID and this string 
				will be added to the channel number column (3rd) in the traces (Example : ...|33|... &rarr; ...|33_1_user1|... ). Four redirection exist:<br>
				<br>@remote : caller number
				<br>@local : called number
				<br>@id : VoieXML id parameter value
				<br>@param : VoiceXML parameter value
			</span></a></td>
			<td style="width: 75%;"><input type="text" id="mark" name="mark" style="width: 250px;" value="<?php echo $editvxml['mark'] ? $editvxml['mark'] : ""?>"></td>
		</tr>
		<tr>
			<td style="width: 25%;"><a href="#" class="info">Speech<span>
				Speech recognition activation to connect an ASR engine.
				This speech function is as the general function, but for 
				the account only. If not set, use the general value.
			</span></a></td>
			<td style="width: 75%;">
				<table width="100%">
        			<tbody>
        				<tr>
          					<td>
								<span class="radioset ui-buttonset">
            						<input id="speech-yes" type="radio" name="speech" id="speech" <?php echo $speech_checked_yes?> value="yes" class="ui-helper-hidden-accessible">
           							<label for="speech-yes" class="ui-button ui-widget ui-button-text-only ui-corner-left" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Yes</span></label>
           							<input id="speech-emulation" type="radio" name="speech" id="speech" <?php echo $speech_checked_emulation?> value="emulation" class="ui-helper-hidden-accessible">
           							<label for="speech-emulation" class="ui-button ui-widget ui-button-text-only" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Emulation</span></label>
           							<input id="speech-automatic" type="radio" name="speech" id="speech" <?php echo $speech_checked_automatic?> value="automatic" class="ui-helper-hidden-accessible">
           							<label for="speech-automatic" class="ui-button ui-widget ui-button-text-only" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Automatic</span></label>
           							<input id="speech-debug" type="radio" name="speech" id="speech" <?php echo $speech_checked_debug?> value="debug" class="ui-helper-hidden-accessible">
           							<label for="speech-debug" class="ui-button ui-widget ui-button-text-only" role="button"><span style="padding: .1em .5em;" class="ui-button-text">Debug</span></label>
           							<input id="speech-no" type="radio" name="speech" id="speech" <?php echo $speech_checked_no?> value="no" class="ui-helper-hidden-accessible">
           							<label for="speech-no" class="ui-button ui-widget ui-button-text-only ui-corner-right" role="button"><span style="padding: .1em .5em;" class="ui-button-text">No</span></label>
								</span>
       						</td>
       					</tr>
    				</tbody>
    			</table>
			</td>
		</tr>
		<tr>
			<td style="width: 25%;"><a href="#" class="info">Speech Provider<span>
				{lumenvox/verbio} You can set which speech recognition provider to allocate to the speech resource. 
				When the default is empty, use the first option.This speech function is as the general function, 
				but for the account only. If not set, use the general value.
			</span></a></td>
			<td style="width: 75%;"><input type="text" id="speechprovider" name="speechprovider" style="width: 250px;" value="<?php echo $editvxml['speechprovider'] ? $editvxml['speechprovider'] : ""?>"><span id="errProvider" style="color: red"></span></td>
		</tr>
		<?php /*?>
		<tr>
			<td colspan="2"><h5>Destination after execution<hr></h5></td>
		</tr>
		<?php echo drawselects($editvxml['goto'],0);?>
		<tr>
			<td><span id="errGoto0" style="color: red"></span></td>
			<td></td>
		</tr>
		*/?>
		<tr>
			<td colspan="2">
				<br>
				<table>
					<tr>
						<td><input type="button" onclick="<?php echo $edit ? "modify()" : "create()"?>;" value="<?php echo $edit ? "Save Changes" : "Create"?>"></form></td>
						<td>
						<?php if ($edit) {?>
							<form autocomplete="off" name="general" id="general" action="config.php?display=vxml" method="post">
								<input type="hidden" id="form" name="form" value="delete">
								<input type="hidden" id="name" name="name" value="<?php echo $editvxml['name']?>">
								<input type="submit" value="Delete">
							</form>
						<?php }?>			
						</td>
					</tr>
				</table>			
			</td>
		</tr>
	</table>
	
<?php if ($edit) {?>
<script type="text/javascript">

	function modify() {
		
		document.getElementById("errName").innerHTML = "";
		//document.getElementById("errGoto0").innerHTML = "";
		document.getElementById("errURL").innerHTML = "";
		document.getElementById("errSessions").innerHTML = "";

		var form = document.getElementById("general");
		var name = form.elements['name'].value;
		var prevname = form.elements['prevname'].value;
		//var goto0 = form.elements['goto0'].value;
		var nameslist = form.elements['nameslist'].value;
		var url = form.elements["url"].value;
		var max = form.elements["max"].value;

		var sub_name = 0; var sub_goto0 = 1; var sub_url = 0; var sub_max = 0; 

		if (name == "" || name == null) {
			document.getElementById("errName").innerHTML = "  It must be specified";
		} else {
			if (name.length > 100) {
				document.getElementById("errName").innerHTML = "  The max length is 100 characters";
			} else if (name.indexOf(' ') >= 0) {
				document.getElementById("errName").innerHTML = "  It can not contain spaces.";
			} else {
				var listofnames = nameslist.split(" ");
				var exist = 0;
				for (var i = 0; i < listofnames.length; i++) {
					if (name == listofnames[i]) {
						exist = 1;
						break;
					}
				} 
				if (exist == 1 && name != prevname) {
					document.getElementById("errName").innerHTML = "  This name already exist";
				} else {
					sub_name = 1;
				}
			}
		}

		if (url == null || url == "") {
			document.getElementById("errURL").innerHTML = "  It must be specified.";
		} else {
			sub_url = 1;
		}

		if (max == null || max == "") {
			sub_max = 1;
		} else if (isNaN(max)) {
			document.getElementById("errSessions").innerHTML = "  It can not contain characters.";
		} else {
			sub_max = 1;
		}
		/*
		if (goto0 == "" || goto0 == null) {
			document.getElementById("errGoto0").innerHTML = "  A destination must be selected";
		} else {
			sub_goto0 = 1;
		}*/

		if (sub_name == 1 && sub_goto0 == 1 && sub_url == 1 && sub_max == 1) {
			alert("Application modified correctly");
			form.submit();
		}
	}
					
</script>	
<?php } else {?>
<script type="text/javascript">

	function create() {
		
		document.getElementById("errName").innerHTML = "";
		//document.getElementById("errGoto0").innerHTML = "";
		document.getElementById("errURL").innerHTML = "";
		document.getElementById("errSessions").innerHTML = "";

		var form = document.getElementById("general");
		var name = form.elements['name'].value;
		//var goto0 = form.elements['goto0'].value;
		var nameslist = form.elements['nameslist'].value;
		var url = form.elements["url"].value;
		var max = form.elements["max"].value;

		var sub_name = 0; var sub_goto0 = 1; var sub_url = 0; var sub_max = 0; 

		if (name == "" || name == null) {
			document.getElementById("errName").innerHTML = "  It must be specified";
		} else {
			if (name.length > 100) {
				document.getElementById("errName").innerHTML = "  The max length is 100 characters";
			} else if (name.indexOf(' ') >= 0) {
				document.getElementById("errName").innerHTML = "  It can not contain spaces.";
			} else {
				var listofnames = nameslist.split(" ");
				var exist = 0;
				for (var i = 0; i < listofnames.length; i++) {
					if (name == listofnames[i]) {
						exist = 1;
						break;
					}
				} 
				if (exist == 1) {
					document.getElementById("errName").innerHTML = "  This name already exist";
				} else {
					sub_name = 1;
				}
			}
		}

		if (url == null || url == "") {
			document.getElementById("errURL").innerHTML = "  It must be specified.";
		} else {
			sub_url = 1;
		}

		if (max == null || max == "") {
			sub_max = 1;
		} else if (isNaN(max)) {
			document.getElementById("errSessions").innerHTML = "  It can not contain characters.";
		} else {
			sub_max = 1;
		}
		/*
		if (goto0 == "" || goto0 == null) {
			document.getElementById("errGoto0").innerHTML = "  A destination must be selected";
		} else {
			sub_goto0 = 1;
		}*/

		if (sub_name == 1 && sub_goto0 == 1 && sub_url == 1 && sub_max == 1) {
			alert("Application added correctly");
			form.submit();
		}
	}
					
</script>
<?php }?> 