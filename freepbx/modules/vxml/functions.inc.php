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
		$html = $html.'<span style="font-style:italic;"><b>Warning</b>. The lines like:<br>
						|0|2000|Read|exiting: 0, 400<br>
						|0|3000|SBinetChannel::Read|exiting, returned <br>
						|0|3000|SBinetChannel::Read|entering: 0x0x6ce9e0, 0x0x7ffff3eff710, 400, 140737285977784, 0x0x7fffe43d2420<br>
						|0|2000|Read|entering: 0x0x6ce968, 0x0x7ffff3eff710, 400, 0x0x7ffff3eff6b8, 0x0x7fffe43a4cc0<br>
						|0|2003|Read|swi:swi:SBinet:http://example.url/yes.bnf, /tmp/cacheContent/swi_SBinet/0/8.sbc: 400 bytes, 400 requested, rc = 0<br><br>
		
						have been removed for a better reading of the log.<br><br></span><b>Here the log:</b><br><br>';
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
					"cachetimeout='".$settings['cachetimeout']."',".
					"monitor=".$settings['monitor'].
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
	
	//Call to the function to change the TTS engine
	changeTTSEngine();
	
	//Call to the function to change the ASR engine
	changeMRCPEngine();
	
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

function changeTTSEngine() {
	
	global $db;
	
	$sql = "SELECT * FROM tts WHERE selected=true";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	//First we edit the configuration faile for Openvxi /etc/openvxi/client.cfg
	
	$maxage = $results[0]['maxage'];
	$ssml = $results[0]['ssml'];
	
	$confFile = "/etc/openvxi/client.cfg";
	$tmpFile = "/tmp/client.cfg.tmp";
		
	$fr = fopen($confFile,'r');
	$fw = fopen($tmpFile,'w');
	
	if ($results[0]['mode'] == "HTTP") {

		$url = $results[0]['url'];
		
		while(!feof($fr)) {
			$line = fgets($fr);
			$tline = trim($line);
			
			if (strpos($line,"client.prompt.resource") !== false) {
				
				$tmpLine = preg_replace("/[\s]+/", " ", $line);
				$explodeLine = explode(" ",$tmpLine);
					
				if (trim($explodeLine[0]) == "client.prompt.resource.0.uri") $explodeLine[2] = $url;
				elseif (trim($explodeLine[0]) == "client.prompt.resource.0.method") $explodeLine[2] = "POST";
				elseif (trim($explodeLine[0]) == "client.prompt.resource.0.maxage") $explodeLine[2] = $maxage;
				elseif (trim($explodeLine[0]) == "client.prompt.resource.0.ssml") $explodeLine[2] = $ssml;
				elseif (strpos($tline,"#") == 0) $a; //Does nothing just for formating code
				
				if (strlen($explodeLine[0]) <= 32) $line = $explodeLine[0]."\t\t\t".$explodeLine[1]."\t".$explodeLine[2]."\n";
				else $line = $explodeLine[0]."\t\t".$explodeLine[1]."\t".$explodeLine[2]."\n";	
				
			}
			
			fwrite($fw,$line);
			
		}
		
	} elseif ($results[0]['mode'] == "MRCP") {
		
		while(!feof($fr)) {
			$line = fgets($fr);
			$tline = trim($line);
				
			if (strpos($line,"client.prompt.resource") !== false) {
		
				$tmpLine = preg_replace("/[\s]+/", " ", $line);
				$explodeLine = explode(" ",$tmpLine);
					
				if (trim($explodeLine[0]) == "client.prompt.resource.0.method") $explodeLine[2] = "ASTERISK";
				elseif (trim($explodeLine[0]) == "client.prompt.resource.0.maxage") $explodeLine[2] = $maxage;
				elseif (trim($explodeLine[0]) == "client.prompt.resource.0.ssml") $explodeLine[2] = $ssml;
				elseif (strpos($tline,"#") == 0) $a; //Does nothing just for formating code
		
				if (strlen($explodeLine[0]) <= 32) $line = $explodeLine[0]."\t\t\t".$explodeLine[1]."\t".$explodeLine[2]."\n";
				else $line = $explodeLine[0]."\t\t".$explodeLine[1]."\t".$explodeLine[2]."\n";
		
			}
				
			fwrite($fw,$line);
				
		}
		
		
	}	
		
	fclose($fr);
	fclose($fw);
	
	$out = shell_exec('sudo mv /tmp/client.cfg.tmp /etc/openvxi/client.cfg');
	
	if ($results[0]['mode'] == "MRCP") {
		//Then we update the file /etc/asterisk/mrcp.conf
		$mrcpFile = "/etc/asterisk/mrcp.conf";
		
		$localIP = $results[0]['localIP'];
		$localPORT = $results[0]['localPORT'];
		$remoteIP = $results[0]['remoteIP'];
		$remotePORT = $results[0]['remotePORT'];
		//$useInASR = $results[0]['useInASR'];
	
		$content = "
;--------------------------------------------------------------------------------;
; Do NOT edit this file as it is auto-generated by FreePBX. All modifications to ;
; this file must be done via the web gui.                                        ;
;--------------------------------------------------------------------------------;
                                                                                  
;*********************************************************************************
; AUTO-GENERATED TTS CONFIGURATION INCLUDED HERE                                 *
;*********************************************************************************
			
[general]

default-tts-profile = speech-local-mrcp1
default-asr-profile = speech-local-mrcp1

log-level = DEBUG ; EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG
max-connection-count = 100
offer-new-connection = 1
			
[speech-local-mrcp1]
version = 1
server-ip = $remoteIP
server-port = $remotePORT
client-ip = $localIP
client-port = $localPORT
resource-location = media
speechsynth = speechsynthesizer
speechrecog = speechrecognizer
rtp-port-min = 10000
rtp-port-max = 11000
playout-delay = 50
;min-playout-delay = 20
max-playout-delay = 200
ptime = 20
codecs = PCMU PCMA L16/96/8000
rtcp = 1
rtcp-bye = 2
rtcp-tx-interval = 5000
rtcp-rx-resolution = 1000
			
";
		file_put_contents($mrcpFile,$content);
	}
	
	return;
	
}

//$action = start/stop/restart
function manageInterpreter($action) {
	
	exec(dirname(__FILE__) . "/scripts/manage_software.sh $action >/dev/null 2>&1 &");
	sleep(4);
	return;
	
}

function getTTS() {
	
	global $db;
	
	$sql = "SELECT * FROM tts";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
	
	return $results;
	
}

function updateTTS($query) {
	
	global $db;
	
	$result = $db->query($query);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$query);
	}
	
	return;
	
}

function getMRCP() {
	
	global $db;
	
	$sql = "SELECT * from mrcp";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
	
	return $results[0];
	
}

function updateMRCP($mrcp) {
	
	global $db;
	
	$sql = "UPDATE mrcp SET
				ipLocal='".$mrcp['ipLocal']."',
				ipServerSIP='".$mrcp['ipServerSIP']."',
				portServerSIP='".$mrcp['portServerSIP']."',
				ipServerRTSP='".$mrcp['ipServerRTSP']."',
				portServerRTSP='".$mrcp['portServerRTSP']."',
				mrcpVersion='".$mrcp['mrcpVersion']."',
				grammarFormat='".$mrcp['grammarFormat']."' WHERE id='settings'";
	
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
	
	return;
	
	
}

function xmlMRCP() {
	
	$mrcp = getMRCP();
	
	$xml = '<?xml version="1.0" encoding="UTF-8"?>
<!-- UniMRCP client document -->
<unimrcpclient xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xsi:noNamespaceSchemaLocation="..\unimrcpclient.xsd"
               version="1.0">
  <settings>
    <!-- SIP MRCPv2 settings -->
    <sip-settings id="Client-SIP-Settings">
      <!-- Server address should be explicitly specified, it defaults to "ip" address set in the properties. -->
      <server-ip>'.$mrcp['ipServerSIP'].'</server-ip>
      <server-port>'.$mrcp['portServerSIP'].'</server-port>
      <!-- <force-destination>true</force-destination> -->
    </sip-settings>

    <!-- RTSP MRCPv1 settings -->
    <rtsp-settings id="Client-RTSP-Settings">
      <!-- Server address should be explicitly specified, it defaults to "ip" address set in the properties. -->
      <server-ip>'.$mrcp['ipServerRTSP'].'</server-ip>
      <server-port>'.$mrcp['portServerRTSP'].'</server-port>
      <!-- <force-destination>true</force-destination> -->
      <resource-location>media</resource-location>
      <resource-map>
        <param name="speechsynth" value="speechsynthesizer"/>
        <param name="speechrecog" value="speechrecognizer"/>
      </resource-map>
    </rtsp-settings>

    <!-- RTP/RTCP settings -->
    <rtp-settings id="Client-RTP-Settings">
      <jitter-buffer>
        <playout-delay>50</playout-delay>
        <max-playout-delay>200</max-playout-delay>
      </jitter-buffer>
      <ptime>20</ptime>
      <codecs>PCMU PCMA L16/96/8000 telephone-event/101/8000</codecs>
      <!-- enable/disable RTCP support -->
      <rtcp enable="true">
        <!-- RTCP BYE policies (RTCP must be enabled first)
              0 - disable RTCP BYE
              1 - send RTCP BYE at the end of session
              2 - send RTCP BYE also at the end of each talkspurt (input)
        -->
        <rtcp-bye>1</rtcp-bye>
        <!-- rtcp transmission interval in msec (set 0 to disable) -->
        <tx-interval>5000</tx-interval>
        <!-- period (timeout) to check for new rtcp messages in msec (set 0 to disable) -->
        <rx-resolution>1000</rx-resolution>
      </rtcp>
    </rtp-settings>

  </settings>

  <profiles>
    <!-- Client MRCPv2 profile -->
    <mrcpv2-profile id="clientv2">
      <sip-uac>SIP-Agent-1</sip-uac>
      <mrcpv2-uac>MRCPv2-Agent-1</mrcpv2-uac>
      <media-engine>Media-Engine-1</media-engine>
      <rtp-factory>RTP-Factory-1</rtp-factory>
      <sip-settings>Client-SIP-Settings</sip-settings>
      <rtp-settings>Client-RTP-Settings</rtp-settings>
    </mrcpv2-profile>

    <!-- Client MRCPv1 profile -->
    <mrcpv1-profile id="clientv1">
      <rtsp-uac>RTSP-Agent-1</rtsp-uac>
      <media-engine>Media-Engine-1</media-engine>
      <rtp-factory>RTP-Factory-1</rtp-factory>
      <rtsp-settings>Client-RTSP-Settings</rtsp-settings>
      <rtp-settings>Client-RTP-Settings</rtp-settings>
    </mrcpv1-profile>

    <!-- more profiles might be added here -->
  </profiles>
</unimrcpclient>
	';
	
	$unimrcpclient = new SimpleXMLElement($xml);	
		
	//We write the file
	$dom = new DOMDocument();
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = true;
	$dom->loadXML($unimrcpclient->asXML());
	$dom->save('/tmp/client.xml');
	shell_exec("sudo mv /tmp/client.xml /usr/local/unimrcp/conf/client-profiles/");
	
	
}

function changeMRCPEngine() {
	
	$mrcp = getMRCP();
	$license = getLicense();
	$speech = strtolower($license['speech']);
	
	//Call to the function to create the xml client file
	xmlMRCP();
	
	//We create the file /etc/asterisk/unimrcp.conf
	$confFile = "/etc/asterisk/unimrcp.conf";
	$content = "
;--------------------------------------------------------------------------------;
; Do NOT edit this file as it is auto-generated by FreePBX. All modifications to ;
; this file must be done via the web gui.                                        ;
;--------------------------------------------------------------------------------;
                                                                                  
;*********************************************************************************
; AUTO-GENERATED MRCP CONFIGURATION INCLUDED HERE                                 *
;*********************************************************************************
			
[general]
unimrcp-profile = ".(($mrcp['mrcpVersion'] == "V1") ? "clientv1" : "clientv2")."		

log-level = DEBUG ;EMERGENCY
log-output = ".(($speech == "debug") ? "3" : "0")."
		
[grammars]
;grammar-name = path-to-grammar-file

[mrcpv2-properties]
Start-Input-Timers = TRUE
Recognition-Timeout = 20000
No-Input-Timeout = 60000
Speech-Language = en-US

[mrcpv1-properties]
Recognizer-Start-Timers = TRUE
Recognition-Timeout = 20000
No-Input-Timeout = 60000
Speech-Language = en-US		
";			

	file_put_contents($confFile,$content);
	
	//Now we set the proper values in the /etc/openvxi/client.cfg file
	$confFile = "/etc/openvxi/client.cfg";
	$tmpFile = "/tmp/client.cfg.tmp";
	
	$fr = fopen($confFile,'r');
	$fw = fopen($tmpFile,'w');
	
	while(!feof($fr)) {
		$line = fgets($fr);
		$tline = trim($line);
	
		if (strpos($line,"client.rec.resource") !== false) {
	
			$tmpLine = preg_replace("/[\s]+/", " ", $line);
			$explodeLine = explode(" ",$tmpLine);
				
			
			if (trim($explodeLine[0]) == "client.rec.resource.0.format") $explodeLine[2] = $mrcp['grammarFormat'];
			elseif (trim($explodeLine[0]) == "client.rec.resource.0.sendProperties" || trim($explodeLine[0]) == "#client.rec.resource.0.sendProperties") {
				$explodeLine[0] = "client.rec.resource.0.sendProperties";
				if ($speech == "yes" || $speech == "automatic") $explodeLine[2] = "yes";
				else $explodeLine[2] = "no";
			} elseif (strpos($tline,"#") == 0) $a; //Does nothing just for formating code
	
			if (strlen($explodeLine[0]) <= 31) $line = $explodeLine[0]."\t\t\t".$explodeLine[1]."\t".$explodeLine[2]."\n";
			else $line = $explodeLine[0]."\t\t".$explodeLine[1]."\t".$explodeLine[2]."\n";
	
		}
	
		fwrite($fw,$line);
	
	}
	
	fclose($fr);
	fclose($fw);
	
	$out = shell_exec('sudo mv /tmp/client.cfg.tmp /etc/openvxi/client.cfg');
	
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
	
	global $astman;
	
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
	$content = $content."monitor=".(($settings["monitor"]) ? "yes" : "no")."\n";
	
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
			} elseif ($key == "monitor") {
				continue;
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






















