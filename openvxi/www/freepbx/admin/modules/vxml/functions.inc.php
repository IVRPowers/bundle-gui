<?php 

function vxml_destinations() {
	
	//get the list of Accounts and Applications
	$applications = getVxmlList("*");
	
	// return an associative array with destination and description
	if (isset($applications)) {
		foreach ($applications as $app) {
			$extens[] = array('destination' => 'app-vxml-'.$app['id'].',s,1', 'description' => $app['name'], 'category' => 'VoiceXML Application', 'id' => 'vxml');
		}
	}
	
	if (isset($extens)) {
		return $extens;
	} else {
		return null;
	}
	
}

function highlightVXMLLog($file,$lines) {
	
	$html = "<span>";
	if(strpos($file,"log.txt") !== false) {
		$lines = loadLog($file,$lines);
		/*
    $html = $html.'<span style="font-style:italic;"><b>Warning</b>. The lines like:<br>
						|0|2000|Read|exiting: 0, 400<br>
						|0|3000|SBinetChannel::Read|exiting, returned <br>
						|0|3000|SBinetChannel::Read|entering: 0x0x6ce9e0, 0x0x7ffff3eff710, 400, 140737285977784, 0x0x7fffe43d2420<br>
						|0|2000|Read|entering: 0x0x6ce968, 0x0x7ffff3eff710, 400, 0x0x7ffff3eff6b8, 0x0x7fffe43a4cc0<br>
						|0|2003|Read|swi:swi:SBinet:http://example.url/yes.bnf, /tmp/cacheContent/swi_SBinet/0/8.sbc: 400 bytes, 400 requested, rc = 0<br><br>
		
						have been removed for a better reading of the log.<br><br></span><b>Here the log:</b><br><br>';
    */
		foreach ($lines as $line) {			
			if (empty($line)) continue;
			$color_section = 1;
			if (strpos($line,"|DEV|") !== false) {
				$line = "<span style='color: #00FF00; font-weight: bold;'>".$line."</span>";
				$color_section = 0;
			} elseif (strpos($line,"|CRITICAL|") !== false) {
				$line = "<span style='color: #BC1212; font-weight: bold;'>".$line."</span>";
				$color_section = 0;
			} elseif (strpos($line,"error.") !== false) {
				$line = "<span style='color: red; font-weight: bold;'>".$line."</span>";
				$color_section = 0;
			} elseif (strpos($line,"|MSG") !== false) {
				$line = "<span style='color: #00FFFF; font-weight: bold;'>".$line."</span>";
				$color_section = 0;
			} elseif (strpos($line,"|Queuing TTS") !== false) {
				$line = "<span style='color: yellow; font-weight: bold;'>".$line."</span>";
				$color_section = 0;
			} elseif (strpos($line,"|WARNING|") !== false) {
				$line = "<span style='color: orange; font-weight: bold;'>".$line."</span>";
				$color_section = 0;
			} elseif (strpos($line,"|CRITICAL|") !== false) {
				$line = "<span style='color: red; font-weight: bold;'>".$line."</span>";
				$color_section = 0;
			} elseif (strpos($line,"|Waiting CALL") !== false) {
				$line = "<span style='font-weight: bold;'>".$line."</span>";
				$color_section = 0;
			}
			if ($color_section) {
				$sections = explode('|',$line);
				if ($sections[3] >= 3000 && $sections[3] < 4000) $colored = "<span style='color: #FF0000;'>".$sections[3]."</span>";
				elseif ($sections[3] >= 4000 && $sections[3] < 5000) $colored = "<span style='color: #19B319;'>".$sections[3]."</span>";
				elseif ($sections[3] >= 5000 && $sections[3] < 8000) $colored = "<span style='color: #2288EE;'>".$sections[3]."</span>";
				elseif ($sections[3] >= 8000 && $sections[3] < 9000) $colored = "<span style='color: #FF00FF;'>".$sections[3]."</span>";
				elseif ($sections[3] >= 10000 && $sections[3] < 11000) $colored = "<span style='color: #BA8484;'>".$sections[3]."</span>";
				$line = substr($line,0,strpos($line,$sections[3])).$colored.substr($line,strpos($line,$sections[3]) + strlen($sections[3]));
			}
			$html = $html.$line."<br>";
		}
	} else {
		$lines = loadLog($file,$lines);
		foreach ($lines as $line) {
			if (empty($line)) continue;
			$html = $html.$line."<br>";
		}
	}

	$html = $html."</span>";
	return $html;
}

function loadLog($file,$lines) {
	
	$filecontent;
	if(strpos($file,"log.txt") !== false) {
		//We ignore the lines that contain the following expresions 
		$ignored_lines = ".c:
						|\|Read\|exiting:
						|\|SBinetChannel::Read\|exiting, returned
						|\|SBinetChannel::Read\|entering:
						|\|Read\|entering:
						|\|Read\|swi:swi:SBinet:";
		$filecontent = shell_exec("egrep -v '$ignored_lines' $file | tail -n $lines");
	} else {
		$filecontent = shell_exec("tail -n $lines $file");
	}
	$filecontent = explode("\n",$filecontent);
	return $filecontent;
	
}

function addVxml($vxml) {
	
	global $db;
	
	$vxmllist = getVxmlList("*");
	$id = count($vxmllist) + 1;
	$sql = 'INSERT INTO vxml VALUES ("'.$id.'","'.$vxml['name'].'","'.$vxml['url'].'","'.$vxml['max'].'","'.$vxml['dialformat'].'","'.$vxml['mark'].'","'.$vxml['speech'].'","'.$vxml['speechprovider'].'","'.$vxml['goto'].'")';
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
	
	return;
	
}

function updateVxml($updateName,$vxml) {
	
	global $db;
	
	$sql = 'UPDATE vxml SET name="'.$vxml['name'].'",url="'.$vxml['url'].'",maxsessions="'.$vxml['max'].'",dialformat="'.$vxml['dialformat'].'",mark="'.$vxml['mark'].'",speech="'.$vxml['speech'].'",speechprovider="'.$vxml['speechprovider'].'",goto="'.$vxml['goto'].'" WHERE name="'.$updateName.'"';
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
	return;
	
}

function deleteVxml($name) {
	
	global $db;
	
	$sql = 'DELETE FROM vxml WHERE name="'.$name.'"';
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
	return;
	
}

function getVxmlList($param) {
	
	global $db;
	
	$sql = "SELECT $param FROM vxml ORDER BY name";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
	
	return $results;
	
}

function getVxml($name) {

	global $db;

	$sql = "SELECT * FROM vxml where name='$name'";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	return $results;

}

function getLicense() {
	
	global $db;
	
	$sql = "SELECT * FROM vxmllicense";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
	
	$results = $results[0];
	return $results;
	
}

function modifyLicense($license,$prevkey) {
	
	global $db;
	
	$expiration = $license['expiration'] ? $license['expiration'] : "";
	$prevlicense = getLicense();	
	if (empty($prevlicense)) { 
		$sql = "INSERT INTO vxmllicense VALUES ('".
							$license['max']."','".
							$license['video']."','".
							$license['texttospeech']."','".
							$license['speech']."','".
							$license['externals']."','".
							$license['dialer']."','".
							$license['chanh323']."','".
							$license['chanrtmp']."','".
							$expiration."','".
							$license['key']."')";
	} else {
		$sql = "UPDATE vxmllicense SET ".
							"max='".$license['max']."',".
							"video='".$license['video']."',".
							"texttospeech='".$license['texttospeech']."',".
							"speech='".$license['speech']."',".
							"externals='".$license['externals']."',".
							"dialer='".$license['dialer']."',".
							"chanh323='".$license['chanh323']."',".
							"chanrtmp='".$license['chanrtmp']."',".
							"expiration='".$expiration."',".
							"licensekey='".$license['key']."'".
				" WHERE licensekey='".$prevkey."'";
	}
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
	
	return;
		
}

function getConfiguration($param) {

	global $db;
	
	$sql = "SELECT $param FROM vxmlconfiguration";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
	
	$results = $results[0];
	return $results;

}

function modifyConfiguration($settings) {
	
	global $db;
	
	$sql = "UPDATE vxmlconfiguration SET ".
					"recordsilence='".$settings['recordsilence']."',".
					"threshold='".$settings['threshold']."',".
					"wavdefaultcodec='".$settings['wavdefaultcodec']."',".
					"debug='".$settings['debug']."',".
					"priorityevents='".$settings['priorityevents']."',".
					"dialformataudio='".$settings['dialformataudio']."',".
					"cachetimeout='".$settings['cachetimeout']."'".
			" WHERE id='configuration'";

	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
	
	return;
	
}

function vxml_get_config($engine) {
	
	//Call to the function to write the file /etc/asterisk/vxml.conf
	writeVxmlConf();	
	
	//We write the dialplan
	global $ext;
	
	switch($engine) {
		case "asterisk": 
			$vxmllist = getVxmlList("*");
			foreach ($vxmllist as $vxml) {
				$ename = "app-vxml-".$vxml['id'];
				$ext->addSectionComment($ename, $vxml['name']);
				$ext->add($ename,'s','',new ext_vxml($vxml['name']));
				$ext->add($ename,'s','',new ext_goto($vxml['goto']));
			}
			break;
			
	}
	return;
	
}

//class for the generation of the function Vxml() in the dialplan
class ext_vxml {
	var $dest;
	function ext_vxml($dest) {
		$this->dest = $dest;
	}
	
	function output() {
		return "Vxml($this->dest)";
	}
}

//This function will create the content of the file /etc/asterisk/vxml.conf
function writeVxmlConf() {
	$confFile = "/etc/asterisk/vxml.conf";
	$content = "
;--------------------------------------------------------------------------------;
; Do NOT edit this file as it is auto-generated by FreePBX. All modifications to ;
; this file must be done via the web gui. There are alternative files to make    ;
; custom modifications.                                                          ;
;--------------------------------------------------------------------------------;
                                                                                  
;*********************************************************************************
; AUTO-GENERATED AND CUSTOM USER VOICEXML CONFIGURATION INCLUDED HERE            *
;*********************************************************************************
                                                                                  
;--------------------------------------------------------------------------------;
; Customizations to this configuration should be made in vxml_custom.conf        ;
;                                                                                ;
#include vxml_custom.conf														  
;--------------------------------------------------------------------------------;
";
	
	$settings = getConfiguration('*');
	$settingsArrayKeys = array_keys($settings);
	$content = $content."\n[general]\n";
	$content = $content."autoanswer=yes\n";
	$content = $content."videosilence=\n";
	$content = $content."audiosilence=\n";
	$content = $content."speechprovider=unimrcp\n";
	$content = $content."speechscore=50\n";
	
	foreach ($settingsArrayKeys as $key) {
		$value = trim($settings[$key]);
		if (!empty($value)) {
			if ($key == "id") {
				continue;
			} elseif ($key == "wavdefaulcodec") {
				$content = $content."wavcodec=".strtolower($settings[$key])."\n";
			} elseif ($key == "dialformataudio") {
				$content = $content."dialformat=".strtolower($settings[$key])."\n";
			} elseif ($key == "debug") {
				if ($settings['debug'] == "Enabled") $debug = 1;
				else $debug = 0;
				$content = $content."debug=". $debug ."\n";
			} else {
				$content = $content.$key."=".strtolower($settings[$key])."\n";
			}
		}
	}
	
	//TODO Change this part when the page Settings is finished.
	$content = $content."
[control]
forward=#
reverse=*
stop=123456789
pause=
restart=0
skipms=5000			
	";
	
	$license = getLicense();
	$licenseArrayKeys = array_keys($license);
	$content = $content."\n[license]\n";
	foreach ($licenseArrayKeys as $key) {
		$value = trim($license[$key]);
		if (!empty($value)) {
			if ($key == "licensekey") {
				$content = $content."key=".strtolower($license[$key])."\n";	
			} else {
				$content = $content.$key."=".strtolower($license[$key])."\n";
			}
		}
	}	
	
	$vxmlList = getVxmlList("*");
	$i = 0;
	foreach ($vxmlList as $vxml) {
		$content = $content."\n[account$i]\n";
		$vxmlKeyList = array_keys($vxml);
		foreach ($vxmlKeyList as $key) {
			if (!empty($vxml[$key]) && $key != "goto" && $key != "id") $content = $content.$key."=".$vxml[$key]."\n";	
		}		
		$i++;
	}
	
	file_put_contents($confFile,$content);
	
	if ($astman) {
		$out = $astman->send_request('Command',array('Command'=>"vxml reload"));
	}
	
	return;
}
?>






















