<?php

namespace FreePBX\modules\Dashboard\Sections;

class VoiceXML {
	public $rawname = 'VoiceXML';

	public function getSections($order) {
		return array(
			array(
				"title" => _("Interpreter Statistics"),
				"group" => _("VoiceXML"),
				"width" => "550px",
				"order" => isset($order['voicexml']) ? $order['voicexml'] : '350',
				"section" => "voicexml"
			)
		);
	}

	public function getContent($section) {
		
		$statistics = $this->getVoiceXMLStats();
		return load_view(dirname(__DIR__).'/views/sections/voicexml.php',array("statistics" => $statistics));
	}
	
	public function getVoiceXMLStats() {
		
		global $astman;
		if ($astman) {
			$stats = $astman->send_request('Command',array('Command'=>"vxml show statistics"));
			$stats = explode("\n",$stats['data']);			
		}
		$statistics = array();
		foreach ($stats as $stat) {
			if (strpos($stat,"opened") !== false) $statistics['opened'] = trim(substr($stat,strpos($stat,":") + 1));
			elseif (strpos($stat,"peak") !== false) $statistics['peak'] = trim(substr($stat,strpos($stat,":") + 1));
			elseif (strpos($stat,"Sessions") !== false && strpos($stat,"error") !== false) $statistics['error'] = trim(substr($stat,strpos($stat,":") + 1));
			elseif (strpos($stat,"denied") !== false) $statistics['denied'] = trim(substr($stat,strpos($stat,":") + 1));
			elseif (strpos($stat,"refused") !== false) $statistics['refused'] = trim(substr($stat,strpos($stat,":") + 1));
			elseif (strpos($stat,"waited") !== false) $statistics['waited'] = trim(substr($stat,strpos($stat,":") + 1));
			elseif (strpos($stat,"needed") !== false) $statistics['needed'] = trim(substr($stat,strpos($stat,":") + 1));
			elseif (strpos($stat,"maxduration") !== false) $statistics['maxduration'] = trim(substr($stat,strpos($stat,":") + 1));
			elseif (strpos($stat,"Prompts") !== false) $statistics['prompts'] = trim(substr($stat,strpos($stat,":") + 1));
			elseif (strpos($stat,"Recognizes") !== false) $statistics['recognizes'] = trim(substr($stat,strpos($stat,":") + 1));
			elseif (strpos($stat,"Records") !== false) $statistics['records'] = trim(substr($stat,strpos($stat,":") + 1));
			elseif (strpos($stat,"Transfers") !== false) {
				if (strpos($stat,"alternative") !== false) $statistics['transfersAlternative'] = trim(substr($stat,strpos($stat,":") + 1));
				else $statistics['transfers'] = trim(substr($stat,strpos($stat,":") + 1));
			} 
			elseif (strpos($stat,"Speechs") !== false) {
				if (strpos($stat,"error") !== false) $statistics['speechsError'] = trim(substr($stat,strpos($stat,":") + 1));
				else $statistics['speechs'] = trim(substr($stat,strpos($stat,":") + 1));
			} 
			elseif (strpos($stat,"Originates") !== false) {
				if (strpos($stat,"error") !== false) $statistics['originatesError'] = trim(substr($stat,strpos($stat,":") + 1));
				else $statistics['originates'] = trim(substr($stat,strpos($stat,":") + 1));
			} 
			elseif (strpos($stat,"Connection") !== false) {
				if (strpos($stat,"lost") !== false) $statistics['connectionsLost'] = trim(substr($stat,strpos($stat,":") + 1));
				elseif (strpos($stat,"retrieve") !== false) $statistics['connectionsRetrieve'] = trim(substr($stat,strpos($stat,":") + 1));
				elseif (strpos($stat,"error") !== false) $statistics['connectionsError'] = trim(substr($stat,strpos($stat,":") + 1));
			}
			elseif (strpos($stat,"Average") !== false) {
				if (strpos($stat,"sessions") !== false) $statistics['avgSessions'] = trim(substr($stat,strpos($stat,":") + 1));
				if (strpos($stat,"duration") !== false) $statistics['avgDuration'] = trim(substr($stat,strpos($stat,":") + 1));
				if (strpos($stat,"response") !== false) $statistics['avgResponse'] = trim(substr($stat,strpos($stat,":") + 1));
				if (strpos($stat,"CAPS") !== false) $statistics['avgCAPS'] = trim(substr($stat,strpos($stat,":") + 1));
				if (strpos($stat,"speech") !== false) $statistics['avgSpeech'] = trim(substr($stat,strpos($stat,":") + 1));
				if (strpos($stat,"score") !== false) $statistics['avgScore'] = trim(substr($stat,strpos($stat,":") + 1));
				if (strpos($stat,"transfer") !== false) $statistics['avgTransfer'] = trim(substr($stat,strpos($stat,":") + 1));
			}
		}
		
		return $statistics;
		
	}

}
