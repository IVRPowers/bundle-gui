<?php
/* FreePBX installer file
 * This file is run when the module is installed through module admin
 *
 * If this file returns false then the module will not install
 * EX:
 * return false;
 *
 */

global $db;

$sql = "CREATE TABLE IF NOT EXISTS vxml (
			id				INTEGER 	 NOT NULL PRIMARY KEY,
			name 			VARCHAR(100) NOT NULL UNIQUE,
			url 			VARCHAR(300) NOT NULL,
			maxsessions 	VARCHAR(10),
			dialformat		VARCHAR(300),
			mark			VARCHAR(300),
			speech			VARCHAR(50),
			speechprovider	VARCHAR(100),
			goto			VARCHAR(100) NOT NULL
		)";

$check = $db->query($sql);
if(DB::IsError($check)) {
	die_freepbx("Can not create vxml table");
}

$sql = "CREATE TABLE IF NOT EXISTS vxmllicense (
			max				INTEGER NOT NULL,
			video			VARCHAR(10) NOT NULL,
			texttospeech	VARCHAR(10) NOT NULL,
			speech			VARCHAR(10) NOT NULL,
			externals		VARCHAR(10) NOT NULL,
			dialer			VARCHAR(10) NOT NULL,
			chanh323		VARCHAR(10) NOT NULL,
			chanrtmp		VARCHAR(10) NOT NULL,
			expiration		VARCHAR(10),
			licensekey		VARCHAR(200) NOT NULL PRIMARY KEY
		
		)";

$check = $db->query($sql);
if(DB::IsError($check)) {
	die_freepbx("Can not create vxmllicense table");
	//die_freepbx($check->getMessage().$sql);
}

global $astman;

if ($astman) {
	$out = $astman->send_request('Command',array('Command'=>"vxml show license"));
	$out = explode("\n",$out['data']);
}

$exists = 1;
foreach ($out as $line) {
	if (strpos($line, "No such command 'vxml show license'") !== false) {
		$exists = 0;
		break;
	}
}

$sql = "SELECT * FROM vxmllicense";
$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
$results = $results[0];

if ($exists && empty($results)) {
	$license = array();
	if (strpos($out[0],"Privilege") !== false) {
		unset($out[0]);
		$out = array_values($out);
	}
	foreach ($out as $param) {
		if (empty($param)) continue;
		$name = ltrim(rtrim(substr($param,0,strpos($param,":") - 1)));
		$value = substr($param,strpos($param,":") + 1);
		$license[$name] = $value; 
	}
	$dialer = ucfirst(trim(shell_exec("grep dialer /etc/asterisk/vxml.conf | cut -d'=' -f2")));
	if (empty($dialer)) $dialer = "No";
	if (isset($license['Expiration'])) {
		$partial = split(' ', $license['Expiration']);
		$partial = split('/',trim($partial[2]));
		$expiration = substr($partial[0], -2).$partial[1].$partial[2];		
	} else {
		$expiration = "";
	}
	$sql = "INSERT INTO vxmllicense VALUES ('".
					trim($license['Max sessions'])."','".
					trim($license['Video'])."','".
					trim($license['TextToSpeech'])."','".
					trim($license['Speech'])."','".
					trim($license['Externals'])."','".
					trim($dialer)."','".
					trim($license['Channel h323'])."','".
					trim($license['Channel RTMP'])."','".
					trim($expiration)."','".
					trim($license['Key'])."')";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}

$sql = "CREATE TABLE IF NOT EXISTS vxmlconfiguration (
			id				VARCHAR(20)		NOT NULL	PRIMARY KEY,
			recordsilence	VARCHAR(10)		NOT NULL,
			threshold		INTEGER 		NOT NULL,
			wavdefaultcodec	VARCHAR(10)		NOT NULL,
			debug			VARCHAR(10)		NOT NULL,
			priorityevents	VARCHAR(10) 	NOT NULL,
			dialformataudio	VARCHAR(300)	NOT NULL,
			cachetimeout	INTEGER			NOT NULL,
			monitor			BOOLEAN			NOT NULL
		)";

$check = $db->query($sql);
if(DB::IsError($check)) {
	die_freepbx("Can not create vxmlconfiguration table");
	//die_freepbx($check->getMessage().$sql);
}

if ($astman) {
	$out = $astman->send_request('Command',array('Command'=>"vxml show configuration"));
	$out = explode("\n",$out['data']);
}

$exists = 1;
foreach ($out as $line) {
	if (strpos($line, "No such command 'vxml show configuration'") !== false) {
		$exists = 0;
		break;
	}
}

$sql = "SELECT * FROM vxmlconfiguration";
$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
$results = $results[0];

if ($exists && empty($results)) {
	
	$settings = array();
	
	if ($astman) {
		$out = $astman->send_request('Command',array('Command'=>"vxml show configuration"));
		$out = explode("\n",$out['data']);
	}
	
	if (strpos($out[0],"Privilege") !== false) {
		unset($out[0]);
		$out = array_values($out);
	}
	
	foreach ($out as $param) {
		if (empty($param)) continue;
		$name = ltrim(rtrim(substr($param,0,strpos($param,":") - 1)));
		$value = substr($param,strpos($param,":") + 1);
		$settings[$name] = $value;
	}
	$sql = "INSERT INTO vxmlconfiguration VALUES (".
				"'configuration','".
				trim($settings['Record silence'])."','".
				trim($settings['Threshold'])."','".
				trim($settings['WAV default codec'])."','".
				trim($settings['Debug'])."','".
				trim($settings['Priority events'])."','".
				trim($settings['Dial format audio'])."','".
				trim(substr($settings['Cache timeout'],0,strpos($settings['Cache timeout'],'s')))."',"
				."false)";
	
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
	
}

$sql = "CREATE TABLE IF NOT EXISTS tts (
			mode		VARCHAR(10) NOT NULL PRIMARY KEY, 
			selected	BOOLEAN NOT NULL,
			url			VARCHAR(200), 
			localIP		VARCHAR(200), 
			localPORT	INTEGER, 
			remoteIP	VARCHAR(200), 
			remotePORT	INTEGER, 
			maxage		INTEGER, 
			ssml		INTEGER,
			useInASR	BOOLEAN
		)";

$check = $db->query($sql);
if(DB::IsError($check)) {
	die_freepbx("Can not create tts table");
}

$sql = "INSERT INTO tts (mode,selected,url,maxage,ssml) values ('HTTP',true,'http://localhost/tts/flite/tts.php',-1,0)";

$result = $db->query($sql);
if(DB::IsError($result)) {
	die_freepbx($result->getMessage().$sql);
}

$sql = "INSERT INTO tts (mode,selected,localPORT,maxage,ssml,useInASR) values ('MRCP',false,5061,-1,0,false)";

$result = $db->query($sql);
if(DB::IsError($result)) {
	die_freepbx($result->getMessage().$sql);
}

$sql = "CREATE TABLE IF NOT EXISTS mrcp (
			id VARCHAR(50) NOT NULL UNIQUE, 
			ipLocal VARCHAR(300), 
			ipServerSIP VARCHAR(300), 
			portServerSIP INTEGER, 
			ipServerRTSP VARCHAR(300), 
			portServerRTSP INTEGER, 
			mrcpVersion VARCHAR(10),
			grammarFormat VARCHAR(10)
		)";

$check = $db->query($sql);
if(DB::IsError($check)) {
	die_freepbx("Can not create mrcp table");
}

$sql = "INSERT INTO mrcp (id) VALUES ('settings')";

$result = $db->query($sql);
if(DB::IsError($result)) {
	die_freepbx($result->getMessage().$sql);
}








