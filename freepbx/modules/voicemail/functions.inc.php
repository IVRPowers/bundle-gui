<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2006-2013 Schmooze Com Inc.
//

class vmxObject {

	private $exten;

	// contstructor
	function __construct($myexten) {
		$this->exten = $myexten;
		$this->vmx = FreePBX::Voicemail()->Vmx;
	}

	function isInitialized($mode="unavail") {
		return $this->vmx->isInitialized($this->exten,$mode);
	}
	function isEnabled($mode="unavail") {
		return $this->vmx->isEnabled($this->exten,$mode);
	}

	function disable() {
		return $this->vmx->disable($this->exten);
	}

	function getState($mode="unavail") {
		return $this->vmx->getState($this->exten,$mode);
	}

	function setState($state="enabled", $mode="unavail") {
		return $this->vmx->setState($this->exten,$mode,$state);
	}

	function getVmPlay($mode="unavail") {
		return $this->vmx->getVmPlay($this->exten,$mode);
	}

	function setVmPlay($opts=true, $mode="unavail") {
		return $this->vmx->setVmPlay($this->exten,$mode,$opts);
	}

	function hasFollowMe() {
		return $this->vmx->hasFollowMe($this->exten);
	}

	function isFollowMe($digit="1", $mode="unavail") {
		return $this->vmx->isFollowMe($this->exten,$digit,$mode);
	}

	function setFollowMe($digit="1", $mode="unavail", $context='ext-findmefollow', $priority='1') {
		return $this->vmx->setFollowMe($this->exten,$digit,$mode,$context,$priority);
	}

	function getMenuOpt($digit="0", $mode="unavail") {
		return $this->vmx->getMenuOpt($this->exten,$digit,$mode);
	}

	function setMenuOpt($opt="", $digit="0", $mode="unavail", $context="from-internal", $priority="1") {
		return $this->vmx->setMenuOpt($this->exten,$opt, $digit, $mode, $context, $priority);
	}
}

function voicemail_get_config($engine) {
	$modulename = 'voicemail';

	// This generates the dialplan
	global $ext;
	switch($engine) {
		case "asterisk":
			/*
			* vm-callme context plays voicemail over telephone for web click-to-call
			* MSG and MBOX are channel variables that must be set when originating the call
			*/

			$context = 'vm-callme';

			$ext->add($context, 's', '', new ext_answer());
			$ext->add($context, 's', '', new ext_wait(1));
			$ext->add($context, 's', 'repeat', new ext_background('${MSG}&silence/2&vm-repeat&vm-starmain'));
			$ext->add($context, 's', '', new ext_waitexten(15));

			$ext->add($context, '5', '', new ext_goto('repeat', 's'));

			$ext->add($context, '#', '', new ext_playback('vm-goodbye'));
			$ext->add($context, '#', '', new ext_hangup());

			$ext->add($context, '*', '', new ext_macro('get-vmcontext', '${MBOX}'));
			$ext->add($context, '*', '', new ext_vmmain('${MBOX}@${VMCONTEXT},s'));

			$ext->add($context, 'i', '', new ext_playback('pm-invalid-option'));
			$ext->add($context, 'i', '', new ext_goto('repeat', 's'));

			$ext->add($context, 't', '', new ext_playback('vm-goodbye'));
			$ext->add($context, 't', '', new ext_hangup());

			$ext->add($context, 'h', '', new ext_hangup());

			/* end vm-callme context  */

			if (is_array($featurelist = featurecodes_getModuleFeatures($modulename))) {
				foreach($featurelist as $item) {
					$featurename = $item['featurename'];
					$fname = $modulename.'_'.$featurename;
					if (function_exists($fname)) {
						$fcc = new featurecode($modulename, $featurename);
						$fc = $fcc->getCodeActive();
						unset($fcc);

						if ($fc != '')
							$fname($fc);
					} else {
						$ext->add('from-internal-additional', 'debug', '', new ext_noop($modulename.": No func $fname"));
						var_dump($item);
					}
				}
			}

			// Temporary Kludge Until we remove these as globals out of VMX Locater and Other Places
			// However, if we remove these, we MUST make some changes to the dialplan currently commented
			// in the vmx locater code in core
			$settings = voicemail_admin_get();
			foreach ($settings as $k => $v) {
				$ext->addGlobal($k, $v);
				out("Added to globals: $k = $v");
			}
		break;
	}
}

function voicemail_directdialvoicemail($c) {
	global $ext;

	$userlist = core_users_list();
	if (is_array($userlist)) {
		foreach($userlist as $item) {
			$exten = core_users_get($item[0]);
			$vm = ((($exten['voicemail'] == "novm") || ($exten['voicemail'] == "disabled") || ($exten['voicemail'] == "")) ? "novm" : $exten['extension']);

			if($vm != "novm") {
				$context = 'ext-local';
				$exten_num = $exten['extension'];
				// This usually gets called from macro-exten-vm but if follow-me destination need to go this route
				$ext->add($context, $c.$exten_num, '', new ext_macro('vm',$vm.',DIRECTDIAL,${IVR_RETVM}'));
				$ext->add($context, $c.$exten_num, '', new ext_goto('1','vmret'));

				$ivr_context = 'from-did-direct-ivr';
				$ext->add($ivr_context, $c.$exten_num, '', new ext_macro('blkvm-clr'));
				$ext->add($ivr_context, $c.$exten_num, '', new ext_setvar('__NODEST', ''));
				$ext->add($ivr_context, $c.$exten_num, '', new ext_macro('vm',$vm.',DIRECTDIAL,${IVR_RETVM}'));
				$ext->add($ivr_context, $c.$exten_num, '', new ext_gotoif('$["${IVR_RETVM}" = "RETURN" & "${IVR_CONTEXT}" != ""]','ext-local,vmret,playret'));
			}
		}
	}
}

function voicemail_myvoicemail($c) {
	global $ext;
	global $core_conf;

	$id = "app-vmmain"; // The context to be included

	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_answer('')); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	$ext->add($id, $c, '', new ext_macro('user-callerid')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, '', new ext_macro('get-vmcontext','${AMPUSER}'));
	$ext->add($id, $c, 'check', new ext_vmexists('${AMPUSER}@${VMCONTEXT}')); // n,VoiceMailMain(${VMCONTEXT})
	$ext->add($id, $c, '', new ext_gotoif('$["${VMBOXEXISTSSTATUS}" = "SUCCESS"]', 'mbexist'));
	$ext->add($id, $c, '', new ext_vmmain('')); // n,VoiceMailMain(${VMCONTEXT})
	$ext->add($id, $c, '', new ext_gotoif('$["${IVR_RETVM}" = "RETURN" & "${IVR_CONTEXT}" != ""]','playret'));
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, 'mbexist', new ext_gotoif('$["${DB(AMPUSER/${AMPUSER}/novmpw)}"!=""]','novmpw','vmpw'),'check',101);

	$ext->add($id, $c, 'novmpw', new ext_noop('Verifying channel ${CHANNEL} is actually ${AMPUSER}'));
	$ext->add($id, $c, '', new ext_gotoif('$["${REGEX("^${DB(DEVICE/${AMPUSER}/dial)}-[0-9a-f]+$" ${CHANNEL})}"!="1"]','vmpws'));
	$ext->add($id, $c, '', new ext_vmmain('${AMPUSER}@${VMCONTEXT},s')); // n,VoiceMailMain(${VMCONTEXT})
	$ext->add($id, $c, '', new ext_goto('vmend'));

	$ext->add($id, $c, 'vmpws', new ext_noop('Channel ${CHANNEL} is NOT ${AMPUSER} forcing VM Password'));
	$ext->add($id, $c, 'vmpw', new ext_vmmain('${AMPUSER}@${VMCONTEXT}'));
	$ext->add($id, $c, '', new ext_goto('vmend'));

	$ext->add($id, $c, 'vmend', new ext_gotoif('$["${IVR_RETVM}" = "RETURN" & "${IVR_CONTEXT}" != ""]','playret'));
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
	$ext->add($id, $c, 'playret', new ext_playback('beep&you-will-be-transfered-menu&silence/1'));
	$ext->add($id, $c, '', new ext_goto('1','return','${IVR_CONTEXT}'));

	// Now add to sip_general_addtional.conf
	//
	if (isset($core_conf) && is_a($core_conf, "core_conf")) {
		$core_conf->addSipGeneral('vmexten',$c);
	}
}

function voicemail_dialvoicemail($c) {
	global $ext,$amp_conf,$astman;

	$id = "app-dialvm"; // The context to be included

	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal

	$ext->add($id, $c, '', new ext_macro('user-callerid'));
	$ext->add($id, $c, '', new ext_answer(''));
	$ext->add($id, $c, 'start', new ext_wait('1'));
	$ext->add($id, $c, '', new ext_noop($id.': Asking for mailbox'));
	$ext->add($id, $c, '', new ext_read('MAILBOX', 'vm-login', '', '', 3, 2));
	$ext->add($id, $c, 'check', new ext_noop($id.': Got Mailbox ${MAILBOX}'));
	$ext->add($id, $c, '', new ext_macro('get-vmcontext','${MAILBOX}'));
	$ext->add($id, $c, '', new ext_vmexists('${MAILBOX}@${VMCONTEXT}'));
	$ext->add($id, $c, '', new ext_gotoif('$["${VMBOXEXISTSSTATUS}" = "SUCCESS"]', 'good', 'bad'));
	$ext->add($id, $c, '', new ext_macro('hangupcall'));
	$ext->add($id, $c, 'good', new ext_noop($id.': Good mailbox ${MAILBOX}@${VMCONTEXT}'));
	$ext->add($id, $c, '', new ext_vmmain('${MAILBOX}@${VMCONTEXT}'));
	$ext->add($id, $c, '', new ext_gotoif('$["${IVR_RETVM}" = "RETURN" & "${IVR_CONTEXT}" != ""]','playret'));
	$ext->add($id, $c, '', new ext_macro('hangupcall'));
	$ext->add($id, $c, 'bad', new ext_noop($id.': BAD mailbox ${MAILBOX}@${VMCONTEXT}'));
	$ext->add($id, $c, '', new ext_wait('1'));
	$ext->add($id, $c, '', new ext_noop($id.': Asking for password so people can\'t probe for existence of a mailbox'));
	$ext->add($id, $c, '', new ext_read('FAKEPW', 'vm-password', '', '', 3, 2));
	$ext->add($id, $c, '', new ext_noop($id.': Asking for mailbox again'));
	$ext->add($id, $c, '', new ext_read('MAILBOX', 'vm-incorrect-mailbox', '', '', 3, 2));
	$ext->add($id, $c, '', new ext_goto('check'));
 	$ext->add($id, $c, '', new ext_macro('hangupcall'));
	$ext->add($id, $c, 'playret', new ext_playback('beep&you-will-be-transfered-menu&silence/1'));
	$ext->add($id, $c, '', new ext_goto('1','return','${IVR_CONTEXT}'));

	//res_mwi_blf allows you to subscribe to voicemail hints, the following code generates the dialplan for doing so
	$resmwiblf_check = $astman->send_request('Command', array('Command' => 'module show like res_mwi_blf'));
	$resmwiblf_module = preg_match('/[1-9] modules loaded/', $resmwiblf_check['data']);

	if(!$resmwiblf_module) {
		$resmwiblf_check = $astman->send_request('Command', array('Command' => 'module show like res_mwi_devstate'));
		$resmwiblf_module = preg_match('/[1-9] modules loaded/', $resmwiblf_check['data']);
	}
	if ($resmwiblf_module && $amp_conf['USERESMWIBLF']) {
		$userlist = core_users_list();
		if (is_array($userlist)) {
			foreach($userlist as $item) {
				$exten = core_users_get($item[0]);
				$vm = ((($exten['voicemail'] == "novm") || ($exten['voicemail'] == "disabled") || ($exten['voicemail'] == "")) ? "novm" : $exten['extension']);

				if($vm != "novm") {
					$ext->add($id, $c.$vm, '', new ext_goto('1','dvm${EXTEN:'.strlen($c).'}'));
					$ext->addHint($id, $c.$vm, "MWI:$vm@".$exten['voicemail']);
				}
			}
		}
		$c = '_dvm.';
	} else {
		// Note that with this one, it has paramters. So we have to add '_' to the start and '.' to the end
		// of $c
		$c = "_$c.";
	}

	$ext->add($id, $c, '', new ext_answer('')); // $cmd,1,Answer
	$ext->add($id, $c, '', new ext_wait('1')); // $cmd,n,Wait(1)
	// How long is the command? We need to strip that off the front
	$clen = strlen($c)-2;
	$ext->add($id, $c, '', new ext_macro('get-vmcontext','${EXTEN:'.$clen.'}'));
	$ext->add($id, $c, '', new ext_vmmain('${EXTEN:'.$clen.'}@${VMCONTEXT}')); // n,VoiceMailMain(${VMCONTEXT})
	$ext->add($id, $c, '', new ext_gotoif('$["${IVR_RETVM}" = "RETURN" & "${IVR_CONTEXT}" != ""]','${IVR_CONTEXT},return,1'));
	$ext->add($id, $c, '', new ext_macro('hangupcall')); // $cmd,n,Macro(user-callerid)
}

function voicemail_configpageinit($pagename) {
	global $currentcomponent;
	global $amp_conf;

	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$extension = isset($_REQUEST['extension'])?$_REQUEST['extension']:null;
	$tech_hardware = isset($_REQUEST['tech_hardware'])?$_REQUEST['tech_hardware']:null;

       // We only want to hook 'users' or 'extensions' pages.
	if ($pagename != 'users' && $pagename != 'extensions')  {
		return true;
	}

	if ($tech_hardware != null || $extdisplay != '' || $pagename == 'users') {
		// JS function needed for checking voicemail = Enabled
		$js = 'return (theForm.vm.value == "enabled");';
		$currentcomponent->addjsfunc('isVoiceMailEnabled(notused)',$js);
		// JS for verifying an empty password is OK
		$msg = _('Voicemail is enabled but the Voicemail Password field is empty.  Are you sure you wish to continue?');
		$js = 'if (theForm.vmpwd.value == "") { if(confirm("'.$msg.'")) { return true; } else { return false; }  };';
		$currentcomponent->addjsfunc('verifyEmptyVoiceMailPassword(notused)', $js);
		$js = 'if(theForm.vmpwd.value.match(/^[0-9A-D\*#]*$/i)) {return true;}else{return false;}';
		$currentcomponent->addjsfunc('isValidVoicemailPass(notused)', $js);
		$js = "
		if (document.getElementById('vm').value == 'disabled') {
			var dval=true;
			document.getElementById('vmx_state').value='';
			if(!$('html').hasClass('firsttypeofselector')) {
				$('.radioset').buttonset('refresh');
			}
		} else {
			var dval=false;
		}
		$('#vmpwd').prop('disabled',dval);
		$('#email').prop('disabled',dval);
		$('#pager').prop('disabled',dval);
		if(!$('html').hasClass('firsttypeofselector')) {
			$('input[name=\"passlogin\"]').button( \"option\", \"disabled\", dval);
			$('input[name=\"attach\"]').button( \"option\", \"disabled\", dval);
			$('input[name=\"saycid\"]').button( \"option\", \"disabled\", dval);
			$('input[name=\"envelope\"]').button( \"option\", \"disabled\", dval);
			$('input[name=\"delete\"]').button( \"option\", \"disabled\", dval);
		} else {
			$('input[name=\"passlogin\"]').attr('disabled',dval);
			$('input[name=\"attach\"]').attr('disabled',dval);
			$('input[name=\"saycid\"]').attr('disabled',dval);
			$('input[name=\"envelope\"]').attr('disabled',dval);
			$('input[name=\"delete\"]').attr('disabled',dval);
		}
		";
		if ($amp_conf['VM_SHOW_IMAP'] || $vmops_imapuser || $vmops_imappassword) {
			$js .="
			$('#imapuser').prop('disabled',dval);
			$('#imappassword').prop('disabled',dval);
			";
		}
		$js .= "
		$('#options').prop('disabled',dval);
		$('#vmcontext').prop('disabled',dval);
		$('#vmx_state').prop('disabled',dval);
		if(!$('html').hasClass('firsttypeofselector')) {
			$('.radioset').buttonset('refresh');
		}
		return true;
		";
		$currentcomponent->addjsfunc('voicemailEnabled(notused)', $js);

		$js = "
			if (document.getElementById('vmx_state').value == 'checked') {
				var dval=false;
			} else {
				var dval=true;
			}
			document.getElementById('vmx_unavail_enabled').disabled=dval;
			document.getElementById('vmx_busy_enabled').disabled=dval;
			document.getElementById('vmx_temp_enabled').disabled=dval;
			document.getElementById('vmx_play_instructions').disabled=dval;
		";
		$vmxobj = new vmxObject($extdisplay);
		$follow_me_disabled = !$vmxobj->hasFollowMe();

		if (!$follow_me_disabled) {
		$js .= "
			document.getElementById('vmx_option_1_system_default').disabled=dval;
		";
		}
		$js .= "
			document.getElementById('vmx_option_1_number').disabled=dval;
			document.getElementById('vmx_option_2_number').disabled=dval;

			if (document.getElementById('vm').value == 'disabled') {
				document.getElementById('vmx_option_0_number').disabled = true;
				document.getElementById('vmx_option_0_system_default').disabled=true;
			} else {
				document.getElementById('vmx_option_0_system_default').disabled=false;
				if (document.getElementById('vmx_option_0_system_default').checked) {
					document.getElementById('vmx_option_0_number').disabled = true;
				} else {
					document.getElementById('vmx_option_0_number').disabled = false;
				}
			}
		";

		if (!$follow_me_disabled) {
			$js .= "
			if (document.getElementById('vmx_state').value == 'checked') {
				if (document.getElementById('vmx_option_1_system_default').checked) {
					document.getElementById('vmx_option_1_number').disabled = true;
				} else {
					document.getElementById('vmx_option_1_number').disabled = false;
				}
			}
			";
		}

		$js .=
			"
			return true;
		";
		$currentcomponent->addjsfunc('vmx_disable_fields(notused)', $js);
	}

	// On a 'new' user, 'tech_hardware' is set, and there's no extension. Hook into the page.
	if ($tech_hardware != null ) {
		voicemail_applyhooks();
	} elseif ($action=="add") {
	// We don't need to display anything on an 'add', but we do need to handle returned data.
		// ** WARNING **
		// Mailbox must be processed before adding / deleting users, therefore $sortorder = 1
		//
		// More hacky-ness from components, since this is called first, we need to determine if
		// it there is a conclict indpenendent from the user component so we know if we should
		// redisplay the or not. While we are at it, we won't add the process function if there
		// is a conflict
		//
		if ($_REQUEST['display'] == 'users') {
			$usage_arr = framework_check_extension_usage($_REQUEST['extension']);
			if (empty($usage_arr)) {
				$currentcomponent->addprocessfunc('voicemail_configprocess', 1);
			} else {
				voicemail_applyhooks();
			}
		} else {
			$currentcomponent->addprocessfunc('voicemail_configprocess', 1);
		}
	} elseif ($extdisplay != '' || $pagename == 'users') {
	// We're now viewing an extension, so we need to display _and_ process.
		voicemail_applyhooks();
		$currentcomponent->addprocessfunc('voicemail_configprocess', 1);
	}
}

function voicemail_applyhooks() {
	global $currentcomponent;

	// Setup two option lists we need
	// Enable / Disable list
	$currentcomponent->addoptlistitem('vmena', 'enabled', _('Enabled'));
	$currentcomponent->addoptlistitem('vmena', 'disabled', _('Disabled'));
	$currentcomponent->setoptlistopts('vmena', 'sort', false);
	// Enable / Disable vmx list
	$currentcomponent->addoptlistitem('vmxena', '', _('Disabled'));
	$currentcomponent->addoptlistitem('vmxena', 'checked', _('Enabled'));
	$currentcomponent->setoptlistopts('vmxena', 'sort', false);
	// Yes / No Radio button list
	$currentcomponent->addoptlistitem('vmyn', 'yes', _('yes'));
	$currentcomponent->addoptlistitem('vmyn', 'no', _('no'));
	$currentcomponent->setoptlistopts('vmyn', 'sort', false);

	// Add the 'proces' function
	$currentcomponent->addguifunc('voicemail_configpageload');
}

function voicemail_configpageload() {
	global $currentcomponent;
	global $amp_conf;
	global $astman;

	// Init vars from $_REQUEST[]
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$ext = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$extn = isset($_REQUEST['extension'])?$_REQUEST['extension']:null;
	$display = isset($_REQUEST['display'])?$_REQUEST['display']:null;

	if ($ext==='') {
		$extdisplay = $extn;
	} else {
		$extdisplay = $ext;
	}

	if ($action != 'del') {
		$vmbox = voicemail_mailbox_get($extdisplay);
		if ( $vmbox == null ) {
			$vm = false;
			$incontext = 'default';
			$vmpwd = null;
			$name = null;
			$email = null;
			$pager = null;
			$vmoptions = null;

			$vmx_state = '';
		} else {
			$incontext = isset($vmbox['vmcontext'])?$vmbox['vmcontext']:'default';
			$vmpwd = $vmbox['pwd'];
			$name = $vmbox['name'];
			$email = $vmbox['email'];
			$pager = $vmbox['pager'];
			$vmoptions = $vmbox['options'];
			$vm = true;

			$vmxobj = new vmxObject($extdisplay);
			$vmx_state = ($vmxobj->isEnabled()) ? 'checked' : '';
			unset($vmxobj);
		}

		//loop through all options
		$options="";
		if ( isset($vmoptions) && is_array($vmoptions) ) {
			$alloptions = array_keys($vmoptions);
			if (isset($alloptions)) {
				foreach ($alloptions as $option) {
					if ( ($option!="attach") && ($option!="envelope") && ($option!="passlogin") && ($option!="saycid") && ($option!="delete") && ($option!="imapuser") && ($option!="imappassword") && ($option!='') )
					    $options .= $option.'='.$vmoptions[$option].'|';
				}
				$options = rtrim($options,'|');
				// remove the = sign if there are no options set
				$options = rtrim($options,'=');

			}
			extract($vmoptions, EXTR_PREFIX_ALL, "vmops");
		} else {
			$vmops_passlogin = 'yes';
			$vmops_attach = 'no';
			$vmops_saycid = 'no';
			$vmops_envelope = 'no';
			$vmops_delete = 'no';
			$vmops_imapuser = null;
			$vmops_imappassword = null;
		}

		if (empty($vmcontext))
			$vmcontext = (isset($_REQUEST['vmcontext']) ? $_REQUEST['vmcontext'] : $incontext);
		if (empty($vmcontext))
			$vmcontext = 'default';

		if ( $vm==true ) {
			$vmselect = "enabled";
		} else {
			$vmselect = "disabled";
		}

		$fc_vm = featurecodes_getFeatureCode('voicemail', 'dialvoicemail');

		$msgInvalidVmPwd = _("Please enter a valid Voicemail Password, using digits only");
		$msgInvalidEmail = _("Please enter a valid Email Address");
		$msgInvalidPager = _("Please enter a valid Pager Email Address");
		$msgInvalidVMContext = _("VM Context cannot be blank");
		$vmops_imapuser = isset($vmops_imapuser) ? $vmops_imapuser : '';
		$vmops_imappassword = isset($vmops_imappassword) ? $vmops_imappassword : '';

		$section = _("Voicemail");
		$currentcomponent->addguielem($section, new gui_selectbox('vm', $currentcomponent->getoptlist('vmena'), $vmselect, _('Status'), '', false,"frm_${display}_voicemailEnabled() && frm_${display}_vmx_disable_fields()"));
		$disable = ($vmselect == 'disabled');
		$currentcomponent->addguielem($section, new gui_textbox('vmpwd', $vmpwd, _('Voicemail Password'), sprintf(_("This is the password used to access the Voicemail system.%sThis password can only contain numbers.%sA user can change the password you enter here after logging into the Voicemail system (%s) with a phone."),"<br /><br />","<br /><br />",$fc_vm), "frm_${display}_isVoiceMailEnabled() && !frm_${display}_verifyEmptyVoiceMailPassword() && !frm_${display}_isValidVoicemailPass()", $msgInvalidVmPwd, false,0,$disable));
		//for passwordless voicemail we need to check some settings
		//first lets see if there is an entry in the asteriskDB for this device
		//no entry in the db is the same as yes, meaning we need a voicemail password
		$passlogin = !empty($extdisplay) ? $astman->database_get("AMPUSER", $extdisplay."/novmpw") : 'yes';
		$passlogin = !empty($passlogin) ? 'no' : 'yes';
		//now lets get our featurecodes for helptext display niceties
		$mvm = new featurecode('voicemail', 'myvoicemail');
		$dvm = new featurecode('voicemail', 'dialvoicemail');
		$extword = ($display == 'extensions') ? _('Extension') : _('Device');
		$currentcomponent->addguielem($section, new gui_radio('passlogin', $currentcomponent->getoptlist('vmyn'), $passlogin, sprintf(_('Require From Same %s'),$extword), sprintf(_("If set to \"no\" then when the user dials %s to access their own voicemail, they will not be asked to enter a password. This does not apply to %s calls, which will always prompt for a password. For security reasons, this should probably be set to \"yes\" in an environment where other users will have physical access to this extension."),$mvm->getCode(),$dvm->getCode()),$disable));
		$currentcomponent->addguielem($section, new gui_textbox('email', $email, _('Email Address'), _("The email address that Voicemails are sent to."), "frm_${display}_isVoiceMailEnabled() && !isEmail()", $msgInvalidEmail, true, 0, $disable));
		$currentcomponent->addguielem($section, new gui_textbox('pager', $pager, _('Pager Email Address'), _("Pager/mobile email address that short Voicemail notifications are sent to."), "frm_${display}_isVoiceMailEnabled() && !isEmail()", $msgInvalidEmail, true, 0, $disable));
		$currentcomponent->addguielem($section, new gui_radio('attach', $currentcomponent->getoptlist('vmyn'), $vmops_attach, _('Email Attachment'), _("Option to attach Voicemails to email."),$disable));
		$currentcomponent->addguielem($section, new gui_radio('saycid', $currentcomponent->getoptlist('vmyn'), $vmops_saycid, _('Play CID'), _("Read back caller's telephone number prior to playing the incoming message, and just after announcing the date and time the message was left."), $disable));
		$currentcomponent->addguielem($section, new gui_radio('envelope', $currentcomponent->getoptlist('vmyn'), $vmops_envelope, _('Play Envelope'), _("Envelope controls whether or not the Voicemail system will play the message envelope (date/time) before playing the Voicemail message. This setting does not affect the operation of the envelope option in the advanced Voicemail menu."), $disable));
		$currentcomponent->addguielem($section, new gui_radio('delete', $currentcomponent->getoptlist('vmyn'), $vmops_delete, _('Delete Voicemail'), _("If set to \"yes\" the message will be deleted from the Voicemailbox (after having been emailed). Provides functionality that allows a user to receive their Voicemail via email alone, rather than having the Voicemail able to be retrieved from the Webinterface or the Extension handset.  CAUTION: MUST HAVE attach Voicemail to email SET TO YES OTHERWISE YOUR MESSAGES WILL BE LOST FOREVER."), $disable));
    if ($amp_conf['VM_SHOW_IMAP'] || $vmops_imapuser || $vmops_imappassword) {
		  $currentcomponent->addguielem($section, new gui_textbox('imapuser', $vmops_imapuser, _('IMAP Username'), sprintf(_("This is the IMAP username, if using IMAP storage"),"<br /><br />"),'','',true,0,$disable));
		  $currentcomponent->addguielem($section, new gui_textbox('imappassword', $vmops_imappassword, _('IMAP Password'), sprintf(_("This is the IMAP password, if using IMAP storage"),"<br /><br />"),'','',true,0,$disable));
    }
		$currentcomponent->addguielem($section, new gui_textbox('options', $options, _('VM Options'), sprintf(_("Separate options with pipe ( | )%sie: review=yes|maxmessage=60"),"<br /><br />"),'','',true,0,$disable));
		$currentcomponent->addguielem($section, new gui_textbox('vmcontext', $vmcontext, _('VM Context'), _("This is the Voicemail Context which is normally set to default. Do not change unless you understand the implications."), "frm_${display}_isVoiceMailEnabled() && isEmpty()", $msgInvalidVMContext, false,0,$disable));

		$section = _("VmX Locater");
		$currentcomponent->addguielem($section, new gui_selectbox('vmx_state', $currentcomponent->getoptlist('vmxena'), $vmx_state, _('VmX Locater&trade;'), _("Enable/Disable the VmX Locater feature for this user. When enabled all settings are controlled by the user in the User Portal (ARI). Disabling will not delete any existing user settings but will disable access to the feature"), false, "frm_{$display}_vmx_disable_fields()",$disable),5,6);

		$vmxhtml = voicemail_draw_vmxgui($extdisplay, $disable);
		$vmxhtml = '<tr class="VmXLocater"><td colspan="2"><table>'.$vmxhtml.'</table></td></tr>';

		$msgValidNumber = _("Please enter a valid phone number using number digits only");
		$vmxcustom_validate = "
		defaultEmptyOK = true;
		if (!theForm.vmx_option_0_system_default.checked && !isInteger(theForm.vmx_option_0_number.value))
			return warnInvalid(theForm.vmx_option_0_number, '$msgValidNumber');
		if (theForm.vmx_option_1_system_default != undefined && !theForm.vmx_option_1_system_default.checked && !isInteger(theForm.vmx_option_1_number.value))
			return warnInvalid(theForm.vmx_option_1_number, '$msgValidNumber');
		if (!isInteger(theForm.vmx_option_2_number.value))
			return warnInvalid(theForm.vmx_option_2_number, '$msgValidNumber');
		";

		$currentcomponent->addguielem($section, new guielement('vmxcustom', $vmxhtml, "$vmxcustom_validate"),6,6);
	}
}

function voicemail_draw_vmxgui($extdisplay, $disable) {
	global $display;

	$vmxobj = new vmxObject($extdisplay);

	$dval = $vmxobj->isEnabled() ? '' : 'disabled="true"';

	$vmx_unavail_enabled_value = $vmxobj->getState("unavail") == "enabled" ? "checked" : "";
	$vmx_unavail_enabled_text_box_options = $dval;

	$vmx_busy_enabled_value = $vmxobj->getState("busy") == "enabled" ? "checked" : "";
	$vmx_busy_enabled_text_box_options = $dval;

	$vmx_temp_enabled_value = $vmxobj->getState("temp") == "enabled" ? "checked" : "";
	$vmx_temp_enabled_text_box_options = $dval;

	$vmx_play_instructions= $vmxobj->getVmPlay() ? "checked" : "";
	$vmx_play_instructions_text_box_options = $dval;

	$follow_me_disabled = !$vmxobj->hasFollowMe();
	if (!$follow_me_disabled) {
		$vmx_option_1_system_default_text_box_options = $dval;
		if ($vmxobj->isFollowMe()) {
			$vmx_option_1_number_text_box_options = 'disabled="true"';
			$vmx_option_1_number = '';
			$vmx_option_1_system_default = 'checked';
		} else {
			$vmx_option_1_number_text_box_options = $dval;
			$vmx_option_1_number = $vmxobj->getMenuOpt(1);
			$vmx_option_1_system_default = '';
		}
	} else {
		$vmx_option_1_number_text_box_options = $dval;
		$vmx_option_1_number = $vmxobj->getMenuOpt(1);
	}

	$vmx_option_0_system_default_text_box_options = ($disable) ? 'disabled="true"' : '';
	$vmx_option_0_number = $vmxobj->getMenuOpt(0);
	if ($vmx_option_0_number == "") {
		$vmx_option_0_number_text_box_options = 'disabled="true"';
		$vmx_option_0_system_default = 'checked';
	} else {
		$vmx_option_0_number_text_box_options = ($disable) ? 'disabled="true"' : '';
		$vmx_option_0_system_default = '';
	}
	$vmx_option_2_number_text_box_options = $dval;
	$vmx_option_2_number = $vmxobj->getMenuOpt(2);

	$tabindex = guielement::gettabindex();
	$tabindex_text = "tabindex='$tabindex'";
	$set_vmx_text =
		"
			<tr>
				<td><a href='#' class='info'>" . _("Use When:") . "<span>" . _("Menu options below are available during your personal Voicemail greeting playback. <br/><br/>Check both to use at all times.") . "<br></span></a></td> <td>
					<input $tabindex_text $vmx_unavail_enabled_text_box_options $vmx_unavail_enabled_value type=checkbox name='vmx_unavail_enabled' id='vmx_unavail_enabled' value='checked'>
					<small>" . _("unavailable") . "</small>&nbsp;&nbsp;
					<input $tabindex_text $vmx_busy_enabled_text_box_options $vmx_busy_enabled_value type=checkbox name='vmx_busy_enabled' id='vmx_busy_enabled' value='checked'>
					<small>" . _("busy") . "</small>&nbsp;&nbsp;
					<input $tabindex_text $vmx_temp_enabled_text_box_options $vmx_temp_enabled_value type=checkbox name='vmx_temp_enabled' id='vmx_temp_enabled' value='checked'>
					<small>" . _("temp") . "</small>
				</td>
			</tr>
			<tr>
				<td><a href='#' class='info'>" . _("Voicemail Instructions:") ."<span>" . _("Uncheck to play a beep after your personal Voicemail greeting.") . "<br></span></a></td>
				<td>
					<input $tabindex_text $vmx_play_instructions_text_box_options $vmx_play_instructions type=checkbox name='vmx_play_instructions' id='vmx_play_instructions' value='checked'>
					<small>" . _("Standard Voicemail prompts.") . "</small>
				</td>
			</tr>
		</table>
		<br>
		<br>
		<table class='settings'>
			<tr>
				<td><a href='#' class='info'>" . _("Press 0:") . "<span>" . _("Pressing 0 during your personal Voicemail greeting goes to the Operator. Uncheck to enter another destination here. This feature can be used while still disabling VmX to allow an alternative Operator extension without requiring the VmX feature for the user.") . "<br></span></a>
				</td>
				<td>
					<input $tabindex_text $vmx_option_0_number_text_box_options name='vmx_option_0_number' id='vmx_option_0_number' type='text' size=24 value='$vmx_option_0_number'>
				</td>
				<td>
					<input $tabindex_text $vmx_option_0_system_default_text_box_options $vmx_option_0_system_default type=checkbox name='vmx_option_0_system_default' id='vmx_option_0_system_default' value='checked' OnClick=\"frm_{$display}_vmx_disable_fields();\">
					<small>" . _("Go To Operator") . "</small>
				</td>
			</tr>
			<tr>
				<td><a href='#' class='info'>" . _("Press 1:") . "<span>";

	if ($follow_me_disabled) {
		$set_vmx_text .= _("The remaining options can have internal extensions, ringgroups, queues and external numbers that may be rung. It is often used to include your cell phone. You should run a test to make sure that the number is functional any time a change is made so you don't leave a caller stranded or receiving invalid number messages.");
		} else {
		$set_vmx_text .= _("Enter an alternate number here, then change your personal Voicemail greeting to let callers know to press 1 to reach that number. <br/><br/>If you'd like to use your Follow Me List, check \"Send to Follow Me\" and disable Follow Me above.");
		}

	$set_vmx_text .=
		"			<br></span></a>
				</td>
				<td>
					<input $tabindex_text $vmx_option_1_number_text_box_options  name='vmx_option_1_number' id='vmx_option_1_number' type='text' size=24 value='$vmx_option_1_number'>
				</td>
				<td>";

	if (!$follow_me_disabled) {
		$set_vmx_text .=  "<input $tabindex_text $vmx_option_1_system_default_text_box_options $vmx_option_1_system_default type=checkbox name='vmx_option_1_system_default' id='vmx_option_1_system_default' value='checked' OnClick=\"frm_{$display}_vmx_disable_fields(); \"><small>" . _("Send to Follow-Me") . "</small>";
	}

	$set_vmx_text .=
				"
				</td>
			</tr>
			<tr>
				<td><a href='#' class='info'>" . _("Press 2:") . "<span>" . _("Use any extensions, ringgroups, queues or external numbers. <br/><br/>Remember to re-record your personal Voicemail greeting and include instructions. Run a test to make sure that the number is functional.") . "<br></span></a></td>
				<td>
					<input $tabindex_text $vmx_option_2_number_text_box_options name='vmx_option_2_number' id='vmx_option_2_number' type='text' size=24 value='$vmx_option_2_number'>
				</td>
			</tr>
		";
	return $set_vmx_text;
}

function voicemail_configprocess() {
	//create vars from the request
	extract($_REQUEST);
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;

	//if submitting form, update database
	switch ($action) {
		case "add":
			if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true) {
				$usage_arr = framework_check_extension_usage($_REQUEST['extension']);
				if (!empty($usage_arr)) {
					$GLOBALS['abort'] = true;
				} else {
					voicemail_mailbox_add($extdisplay, $_REQUEST);
					needreload();
				}
			}
		break;
		case "del":
			// call remove before del, it needs to know context info
			//
			voicemail_mailbox_remove($extdisplay);
			voicemail_mailbox_del($extdisplay);
			needreload();
		break;
		case "edit":
			if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true) {
				voicemail_mailbox_del($extdisplay);
				if ( $vm != 'disabled' )
					voicemail_mailbox_add($extdisplay, $_REQUEST);
				needreload();
			}
		break;
	}
}

function voicemail_mailbox_get($mbox) {
	$uservm = voicemail_getVoicemail();
	$vmcontexts = array_keys($uservm);

	foreach ($vmcontexts as $vmcontext) {
		if(isset($uservm[$vmcontext][$mbox])){
			$vmbox['vmcontext'] = $vmcontext;
			$vmbox['pwd'] = $uservm[$vmcontext][$mbox]['pwd'];
			$vmbox['name'] = $uservm[$vmcontext][$mbox]['name'];
			$vmbox['email'] = str_replace('|',',',$uservm[$vmcontext][$mbox]['email']);
			$vmbox['pager'] = $uservm[$vmcontext][$mbox]['pager'];
			$vmbox['options'] = $uservm[$vmcontext][$mbox]['options'];
			return $vmbox;
		}
	}

	return null;
}

function voicemail_mailbox_remove($mbox) {
	global $amp_conf;
	$uservm = voicemail_getVoicemail();
	$vmcontexts = array_keys($uservm);

	$return = true;

	foreach ($vmcontexts as $vmcontext) {
		if(isset($uservm[$vmcontext][$mbox])){

			$vm_dir = $amp_conf['ASTSPOOLDIR']."/voicemail/$vmcontext/$mbox";
			exec("rm -rf $vm_dir",$output,$ret);
			if ($ret) {
				$return = false;
				$text   = sprintf(_("Failed to delete vmbox: %s@%s"),$mbox, $vmcontext);
				$etext  = sprintf(_("failed with retcode %s while removing %s:"),$ret, $vm_dir)."<br>";
				$etext .= implode("<br>",$output);
				$nt =& notifications::create($db);
				$nt->add_error('voicemail', 'MBOXREMOVE', $text, $etext, '', true, true);
				//
				// TODO: this does not work but we should give some sort of feedback that id did not work
				//
				// echo "<script>javascript:alert('$text\n"._("See notification panel for details")."')</script>";
			}
		}
	}
	return $return;
}

function voicemail_mailbox_del($mbox) {
	$uservm = voicemail_getVoicemail();
	$vmcontexts = array_keys($uservm);

	foreach ($vmcontexts as $vmcontext) {
		if(isset($uservm[$vmcontext][$mbox])){
			unset($uservm[$vmcontext][$mbox]);
			voicemail_saveVoicemail($uservm);
			return true;
		}
	}

	return false;
}

function voicemail_mailbox_add($mbox, $mboxoptsarray) {
	global $astman;
	//check if VM box already exists
	if ( voicemail_mailbox_get($mbox) != null ) {
		trigger_error("Voicemail mailbox '$mbox' already exists, call to voicemail_mailbox_add failed");
		die_freepbx();
	}

	$uservm = voicemail_getVoicemail();
	extract($mboxoptsarray);

	if ($vm != 'disabled')
	{
		// need to check if there are any options entered in the text field
		if ($options!=''){
			$options = explode("|",$options);
			foreach($options as $option) {
				$vmoption = explode("=",$option);
				$vmoptions[$vmoption[0]] = $vmoption[1];
			}
		}
		if ($imapuser!='' && $imapuser!='') {
			$vmoptions['imapuser'] = $imapuser;
			$vmoptions['imappassword'] = $imappassword;
		}
		$vmoption = explode("=",$passlogin);
			$passlogin = $vmoption[1];
		$vmoption = explode("=",$attach);
			$vmoptions[$vmoption[0]] = $vmoption[1];
		$vmoption = explode("=",$saycid);
			$vmoptions[$vmoption[0]] = $vmoption[1];
		$vmoption = explode("=",$envelope);
			$vmoptions[$vmoption[0]] = $vmoption[1];
		$vmoption = explode("=",$delete);
			$vmoptions[$vmoption[0]] = $vmoption[1];

		$uservm[$vmcontext][$extension] = array(
			'mailbox' => $extension,
			'pwd' => $vmpwd,
			'name' => $name,
			'email' => str_replace(',','|',$email),
			'pager' => $pager,
			'options' => $vmoptions
			);
	}
	voicemail_saveVoicemail($uservm);

	if($passlogin == 'no') {
		//The value doesnt matter, could be yes no f bark
		$astman->database_put("AMPUSER", $extension."/novmpw", 'yes');
	} else {
		$astman->database_del("AMPUSER", $extension."/novmpw");
	}

	$vmxobj = new vmxObject($extension);

	// Operator extension can be set even without VmX enabled so that it can be
	// used as an alternate way to provide an operator extension for a user
	// without VmX enabled.
	//
	if (isset($vmx_option_0_system_default) && $vmx_option_0_system_default != '') {
		$vmxobj->setMenuOpt("",0,'unavail');
		$vmxobj->setMenuOpt("",0,'busy');
		$vmxobj->setMenuOpt("",0,'temp');
	} else {
    if (!isset($vmx_option_0_number)) {
		  $vmx_option_0_number = '';
    }
		$vmx_option_0_number = preg_replace("/[^0-9]/" ,"", $vmx_option_0_number);
		$vmxobj->setMenuOpt($vmx_option_0_number,0,'unavail');
		$vmxobj->setMenuOpt($vmx_option_0_number,0,'busy');
		$vmxobj->setMenuOpt($vmx_option_0_number,0,'temp');
	}

	if (isset($vmx_state) && $vmx_state) {

		if (isset($vmx_unavail_enabled) && $vmx_unavail_enabled != '') {
			$vmxobj->setState('enabled','unavail');
		} else {
			$vmxobj->setState('disabled','unavail');
		}

		if (isset($vmx_busy_enabled) && $vmx_busy_enabled != '') {
			$vmxobj->setState('enabled','busy');
		} else {
			$vmxobj->setState('disabled','busy');
		}

		if (isset($vmx_temp_enabled) && $vmx_temp_enabled != '') {
			$vmxobj->setState('enabled','temp');
		} else {
			$vmxobj->setState('disabled','temp');
		}

		if (isset($vmx_play_instructions) && $vmx_play_instructions== 'checked') {
			$vmxobj->setVmPlay(true,'unavail');
			$vmxobj->setVmPlay(true,'busy');
			$vmxobj->setVmPlay(true,'temp');
		} else {
			$vmxobj->setVmPlay(false,'unavail');
			$vmxobj->setVmPlay(false,'busy');
			$vmxobj->setVmPlay(false,'temp');
		}

		if (isset($vmx_option_1_system_default) && $vmx_option_1_system_default != '') {
			$vmxobj->setFollowMe(1,'unavail');
			$vmxobj->setFollowMe(1,'busy');
			$vmxobj->setFollowMe(1,'temp');
		} else {
			$vmx_option_1_number = preg_replace("/[^0-9]/" ,"", $vmx_option_1_number);
			$vmxobj->setMenuOpt($vmx_option_1_number,1,'unavail');
			$vmxobj->setMenuOpt($vmx_option_1_number,1,'busy');
			$vmxobj->setMenuOpt($vmx_option_1_number,1,'temp');
		}
		if (isset($vmx_option_2_number)) {
			$vmx_option_2_number = preg_replace("/[^0-9]/" ,"", $vmx_option_2_number);
			$vmxobj->setMenuOpt($vmx_option_2_number,2,'unavail');
			$vmxobj->setMenuOpt($vmx_option_2_number,2,'busy');
			$vmxobj->setMenuOpt($vmx_option_2_number,2,'temp');
		}
	} else {
		if ($vmxobj->isInitialized()) {
			$vmxobj->disable();
		}
	}
}

function voicemail_saveVoicemail($vmconf) {
	global $amp_conf;

	// just in case someone tries to be sneaky and not call getVoicemail() first..
	if ($vmconf == null) die_freepbx('Error: Trying to write null Voicemail file! I refuse to continue!');

	// yes, this is hardcoded.. is this a bad thing?
	write_voicemailconf(rtrim($amp_conf["ASTETCDIR"],"/")."/voicemail.conf", $vmconf, $section);
}

function voicemail_getVoicemail() {
	global $amp_conf;

	$vmconf = null;
	$section = null;
	// yes, this is hardcoded.. is this a bad thing?
	parse_voicemailconf(rtrim($amp_conf["ASTETCDIR"],"/")."/voicemail.conf", $vmconf, $section);

	return $vmconf;
}

//----------------------------------------------------------------------------------------------------------
// Merged from vmadmin module
//

function voicemail_get_title($action, $context="", $account="") {
	$title = "<h3>" . _("Voicemail Administration") . "<br />&nbsp;&nbsp;";
	switch ($action) {
		case "tz":
			$title .= _("Timezone Definitions");
			break;
		case "bsettings":
			if (!empty($account)) {
				$title .= _("Basic Settings For: ") . "&nbsp;&nbsp;&nbsp;$account&nbsp;&nbsp;&nbsp;($context)";
			} else {
				$title .= _("Basic settings view is for individual accounts.");
			}
			break;
		case "settings":
			if (!empty($account)) {
				$title .= _("Advanced Settings For: ") . "&nbsp;&nbsp;&nbsp;$account&nbsp;&nbsp;&nbsp;($context)";
			} else {
				$title .= _("System Settings");
			}
			break;
		case "dialplan":
			$title .= _("Dialplan Behavior");
			break;
		case "usage":
			if (!empty($account)) {
				$title .= _("Usage Statistics For: ") . "&nbsp;&nbsp;&nbsp;$account&nbsp;&nbsp;&nbsp;($context)";
			} else {
				$title .= _("System Usage Statistics");
			}
			break;
		default:
			$title .= "&nbsp;&nbsp;" . _("Invalid Action");
			break;
	}
	$title .= "</h3>";
	return $title;
}
function voicemail_get_scope($extension) {
	if (!empty($extension)) {
		return "account";
	} else {
		return "system";
	}
}
function voicemail_update_settings($action, $context="", $extension="", $args=null) {
	global $astman;
	global $tz_settings;
	global $gen_settings;
	/* Ensure we get the most up-to-date voicemail.conf data. */
	if ($action != 'dialplan') {
		$vmconf = voicemail_getVoicemail();
	} else {
		$vmconf = null;
	}
	if ($vmconf !== null) {
		switch ($action) {
			case "tz":
				/* First update all zonemessages opts that are already in vmconf */
				foreach ($vmconf["zonemessages"] as $key => $val) {
					$id = "tz__$key";
					$vmconf["zonemessages"][$key]	= isset($args[$id])?$args[$id]:$vmconf["zonemessages"][$key];
					/* Bad to have empty fields in vmconf. */
					/* And remove deleted fields, too.     */
					if (empty($vmconf["zonemessages"][$key]) || ($args["tzdel__$key"] == "true")) {
						unset($vmconf["zonemessages"][$key]);
					}
				}
					/* Add new field, if one was specified */
					if (!empty($args["tznew_name"]) && !empty($args["tznew_def"])) {
						$vmconf["zonemessages"][$args["tznew_name"]] = $args["tznew_def"];
					}
					unset($args[$id]);
				/* Next record any new zonemessages opts that were on the page but not already in vmconf. */
				foreach ($tz_settings as $key) {
					$id = "tz__$key";
					if (isset($args[$id]) && !empty($args[$id])) {
						$vmconf["zonemessages"][$key] = $args[$id];
					}
				}
				break;
			case "settings":
				if (empty($extension) && $action == "settings") {
					/* First update all general opts that are already in vmconf */
					foreach ($vmconf["general"] as $key => $val) {
						$id = "gen__$key";
						$vmconf["general"][$key] = isset($args[$id])?$args[$id]:$vmconf["general"][$key];
						/* Bad to have empty fields in vmconf. */
						/* also make sure no boolean undefined fields left in there */
						if (empty($vmconf["general"][$key]) || $vmconf["general"][$key] == 'undefined' && $gen_settings[$key]['type'] == 'flag') {
							unset($vmconf["general"][$key]);
						}
						unset($args[$id]);
					}
					/* Next record any new general opts that were on the page but not already in vmconf. */
					foreach ($gen_settings as $key => $descrip) {
						$id = "gen__$key";
						if (isset($args[$id]) && !empty($args[$id])) {
							$vmconf["general"][$key] = $args[$id];
						}
					}
				} else if (!empty($extension)) {
					global $acct_settings;			/* We need this to know the type for each option (text value or flag) */
					/* Delete user's old settings. */
					voicemail_mailbox_del($extension);
					/* Prepare values for user's new settings. 			  */
					/* Each Voicemail account has a line in voicemail.conf like this: */
					/* extension => password,name,email,pager,options		  */
					/* Take care of password, name, email and pager.                  */
					$pwd = isset($args["acct__pwd"])?$args["acct__pwd"]:"";
					unset($args["acct__pwd"]);
					if (isset($args["acct__name"]) && $args["acct__name"] != "") {
						$name = $args["acct__name"];
					} else {
						$this_exten = core_users_get($extension);
						$name = $this_exten["name"];
					}
					unset($args["acct__name"]);
					$email = isset($args["acct__email"])?$args["acct__email"]:"";
					unset($args["acct__email"]);
					$pager = isset($args["acct__pager"])?$args["acct__pager"]:"";
					unset($args["acct__pager"]);

					/* Now handle the options. */
					$options = array();
					foreach ($acct_settings as $key => $descrip) {
						$id = "acct__$key";
						if (isset($args[$id]) && !empty($args[$id]) && $args[$id] != "undefined") {
							$options[$key] = $args[$id];
						}
					}
					/* Remove call me num from options - that is set in ast db */
					unset($options["callmenum"]);
					/* New account values to vmconf */
					$vmconf[$context][$extension] = array(
										"mailbox"	=> $extension,
										"pwd" 		=> $pwd,
										"name" 		=> $name,
										"email" 	=> $email,
										"pager" 	=> $pager,
										"options" 	=> $options
									     );
					$callmenum = (isset($args["acct__callmenum"]) && !empty($args["acct__callmenum"]))?$args["acct__callmenum"]:$extension;
					// Save call me num.
					$cmd = "database put AMPUSER $extension/callmenum $callmenum";
					$astman->send_request("Command", array("Command" => $cmd));
				}
				break;
			case "bsettings":
				if (!empty($extension)) {
					/* Get user's old settings, since we are only replacing the basic settings. */
					$vmbox = voicemail_mailbox_get($extension);
					/* Delete user's old settings. */
					voicemail_mailbox_del($extension);

					/* Prepare values for user's new BASIC settings.		  */
					/* Each Voicemail account has a line in voicemail.conf like this: */
					/* extension => password,name,email,pager,options		  */
					/* Take care of password, name, email and pager.                  */
					$pwd = isset($args["acct__pwd"])?$args["acct__pwd"]:"";
					unset($args["acct__pwd"]);
					if (isset($args["acct__name"]) && $args["acct__name"] != "") {
						$name = $args["acct__name"];
					} else {
						$this_exten = core_users_get($extension);
						$name = $this_exten["name"];
					}
					unset($args["acct__name"]);
					$email = isset($args["acct__email"])?$args["acct__email"]:"";
					unset($args["acct__email"]);
					$pager = isset($args["acct__pager"])?$args["acct__pager"]:"";
					unset($args["acct__pager"]);

					/* THESE ARE COMING FROM THE USER'S OLD SETTINGS.                     */
					$options = $vmbox["options"];	/* An array */
					/* Update the four options listed on the "bsettings" page as needed. */
					$basic_opts_list = array("attach", "saycid", "envelope", "delete");
					foreach ($basic_opts_list as $basic_opt) {
						$id = "acct__" . $basic_opt;
						if (isset($args[$id]) && !empty($args[$id]) && $args[$id] != "undefined") {
							$options[$basic_opt] = $args[$id];
						} else if ($args[$id] == "undefined") {
							unset($options[$basic_opt]);
						}
					}
					/* Remove call me num from options - that is set in ast db. Should not be here anyway, since options are coming from the old settings... */
					unset($options["callmenum"]);
					/* New account values to vmconf */
					$vmconf[$context][$extension] = array(
										"mailbox"	=> $extension,
										"pwd" 		=> $pwd,
										"name" 		=> $name,
										"email" 	=> $email,
										"pager" 	=> $pager,
										"options" 	=> $options
									     );
					$callmenum = (isset($args["acct__callmenum"]) && !empty($args["acct__callmenum"]))?$args["acct__callmenum"]:$extension;
					// Save call me num.
					$cmd = "database put AMPUSER $extension/callmenum $callmenum";
					$astman->send_request("Command", array("Command" => $cmd));
				}
				break;
			default:
				return false;
		}
		voicemail_saveVoicemail($vmconf);
		$astman->send_request("Command", array("Command" => "reload app_voicemail.so"));
		return true;

		// Special Case dialplan since no voicemail.conf related configs
	} else if ($action == 'dialplan') {

		// defaults need to be set for checkboxes unless we change them to radio buttons
		//
		$cb = array('VM_OPTS', 'VMX_OPTS_LOOP', 'VMX_OPTS_DOVM');
		foreach ($cb as $cbs) {
			if (!isset($args[$cbs])) {
				$args[$cbs] = '';
			}
		}
		return voicemail_admin_update($args);
	}
	return false;
}

function voicemail_admin_update($args) {
	global $db;

	$valid_settings = array(
		'VM_OPTS',
		'VM_DDTYPE',
		'VM_GAIN',
		'OPERATOR_XTN',
		'VMX_OPTS_LOOP',
		'VMX_OPTS_DOVM',
		'VMX_TIMEOUT',
		'VMX_REPEAT',
		'VMX_LOOPS'
	);

	$update_arr = array();
	foreach ($args as $key => $value) {
		if (in_array($key, $valid_settings)) {
			$update_arr[] = array($key, $db->escapeSimple($value));
		}
	}
	if (empty($update_arr)) {
		return true;
	}

	$compiled = $db->prepare('REPLACE INTO `voicemail_admin` (`variable`, `value`) VALUES (?, ?)');
	$result = $db->executeMultiple($compiled,$update_arr);
	if(DB::IsError($result)) {
		//LOG ERROR HERE
		dbug("FAILED ON INSERT TO voicemail_admin");
		return false;
	}
	return true;
}

function voicemail_admin_get($setting = false) {
	global $db;

	if ($setting !== false) {
		return sql("SELECT `value` FROM `voicemail_admin` WHERE `variable` = '$setting'", "getOne");
	}
	$sql = "SELECT * FROM `voicemail_admin`";
	$res = $db->getAll($sql, DB_FETCHMODE_ASSOC);

	// This shouldn't happen but some install_amp installs, at least during development, can
	// result in this, better to avoid crashes in the install_amp log
	//
	if(DB::IsError($res)) {
		$res = array();
	}
	$settings = array();
	foreach ($res as $s) {
		$settings[$s['variable']] = $s['value'];
	}
	return $settings;
}

function voicemail_get_settings($vmconf, $action, $extension="") {
	$settings = array();
	switch ($action) {
		case "dialplan":
			// DEFAULTS:
			$settings['VM_OPTS'] = '';
			$settings['VM_DDTYPE'] = 'b';
			$settings['VM_GAIN'] = '12';
			$settings['OPERATOR_XTN'] = '';
			$settings['VMX_OPTS_LOOP'] = '';
			$settings['VMX_OPTS_DOVM'] = '';
			$settings['VMX_TIMEOUT'] = '2';
			$settings['VMX_REPEAT'] = '1';
			$settings['VMX_LOOPS'] = '1';

			$vmsettings = voicemail_admin_get();
			// replace defaults with any values present in the DB
			foreach ($vmsettings as $k => $v) {
				$settings[$k] = $vmsettings[$k];
			}
			break;
		case "tz":
			if (is_array($vmconf) && is_array($vmconf["zonemessages"])) {
				foreach ($vmconf["zonemessages"] as $key => $val) {
					$settings[$key] = $val;
				}
			}
			break;
		case "bsettings":
		case "settings":
			/* Settings can apply to system-wide settings OR to account-specific settings. 		       */
			/* Specifying a context and extension indicates account-specific settings are being requested. */
			if (!empty($extension)) {
				$vmbox = voicemail_mailbox_get($extension);
				if ($vmbox !== null) {
					$settings["enabled"] = true;
				} else {
					$settings["enabled"] = false;
				}
				$settings["vmcontext"] = $c = isset($vmbox["vmcontext"])?$vmbox["vmcontext"]:"default";
				$settings["pwd"] = isset($vmbox["pwd"])?$vmbox["pwd"]:"";
				$settings["name"] = (isset($vmbox["name"]) && $vmbox["name"] != "")?$vmbox["name"]:"";
				if ($settings["name"] == "") {
					$this_exten = core_users_get($extension);
					$settings["name"] = $this_exten["name"];
				}
				$settings["email"] = isset($vmbox["email"])?$vmbox["email"]:"";
				$settings["pager"] = isset($vmbox["pager"])?$vmbox["pager"]:"";
				$options = isset($vmbox["options"])?$vmbox["options"]:array();
				foreach ($options as $key => $val) {
					$settings[$key] = $val;
				}

				/* Get Call Me number */
				global $astman;
				$cmd 		= "database get AMPUSER $extension/callmenum";
				$callmenum 	= "";
				$results 	= $astman->send_request("Command", array("Command" => $cmd));
				if (is_array($results))
				{
					foreach ($results as $results_elem)
					{
						if (preg_match('/Value: [^\s]*/', $results_elem, $matches) > 0)
						{
							$parts = preg_split('/ /', trim($matches[0]));
							$callmenum = $parts[1];
							break;
						}
					}
				}
				$settings["callmenum"] = $callmenum;
				/* End - Call Me number obtained */
			} else {
				if (is_array($vmconf) && is_array($vmconf["general"])) {
					$settings = $vmconf["general"];
				}
			}
			break;
		default:
			break;
	}
	return $settings;
}
function voicemail_update_usage($vmail_info, $context="", $extension="", $args) {
	global $vmail_root;
	$take_action = false;

	if (isset($args["del_msgs"]) && $args["del_msgs"] == "true") {
		$msg = true;
		$take_action = true;
	} else {
		$msg = false;
	}
	if (isset($args["del_names"]) && $args["del_names"] == "true") {
		$name = true;
		$take_action = true;
	} else {
		$name = false;
	}
	if (isset($args["del_unavail"]) && $args["del_unavail"] == "true") {
		$unavail = true;
		$take_action = true;
	} else {
		$unavail = false;
	}
	if (isset($args["del_busy"]) && $args["del_busy"] == "true") {
		$busy = true;
		$take_action = true;
	} else {
		$busy = false;
	}
	if (isset($args["del_temp"]) && $args["del_temp"] == "true") {
		$temp = true;
		$take_action = true;
	} else {
		$temp = false;
	}
	if (isset($args["del_abandoned"]) && $args["del_abandoned"] == "true") {
		$abandoned = true;
		$take_action = true;
	} else {
		$abandoned = false;
	}
	if (!$take_action) {
		return;
	}
	$vmail_path = $vmail_root;
	$scope = "system";
	if (!empty($extension) && !empty($context)) {
		$scope = "account";
	}

	switch ($scope) {
		case "system":
			if ($msg) {
				exec ("rm -f $vmail_root/*/*/*/msg*");
			}
			foreach ($vmail_info["contexts"] as $c) {
				voicemail_del_greeting_files($vmail_root, $c, "", $name, $unavail, $busy, $temp, $abandoned);
			}
			break;
		case "account":
			if (isset($vmail_info["activated_info"][$extension]) && $vmail_info["activated_info"][$extension] == $context) {
				$vmail_path = $vmail_root . "/" . $context . "/" . $extension;
				if ($msg) {
					exec ("rm -f $vmail_path/*/msg*");
				}
				voicemail_del_greeting_files($vmail_root, $context, $extension, $name, $unavail, $busy, $temp, $abandoned);
			}
			break;
	}
}
function voicemail_del_greeting_files($vmail_root, $context="", $exten="", $name=false, $unavail=false, $busy=false, $temp=false, $abandoned=false) {
	$path = $vmail_root;
	if (!empty($context) && !empty($exten)) {
		$path .= "/" . $context . "/" . $exten;
		$ab_name_cmd    = "$path/greet.tmp.*";
		$ab_temp_cmd    = "$path/temp.tmp.*";
		$ab_busy_cmd    = "$path/busy.tmp.*";
		$ab_unavail_cmd = "$path/unavail.tmp.*";
		$name_cmd       = "$path/greet.*";
		$unavail_cmd    = "$path/unavail.*";
		$busy_cmd       = "$path/busy.*";
		$temp_cmd       = "$path/temp.*";
	} else {
		$ab_name_cmd    = "$path/*/*/greet.tmp.*";
		$ab_temp_cmd    = "$path/*/*/temp.tmp.*";
		$ab_busy_cmd    = "$path/*/*/busy.tmp.*";
		$ab_unavail_cmd = "$path/*/*/unavail.tmp.*";
		$name_cmd       = "$path/*/*/greet.*";
		$unavail_cmd    = "$path/*/*/unavail.*";
		$busy_cmd       = "$path/*/*/busy.*";
		$temp_cmd       = "$path/*/*/temp.*";
	}

	if (is_dir($path)) {
		if ($abandoned) {
			/* First handle abandoned greetings.  Delete abandoned greetings that are at least a day old. */
			$ab_names   	= voicemail_get_ab_greetings("greet", $ab_name_cmd);
			$ab_temps    	= voicemail_get_ab_greetings("temp", $ab_temp_cmd);
			$ab_busys    	= voicemail_get_ab_greetings("busy", $ab_busy_cmd);
			$ab_unavails 	= voicemail_get_ab_greetings("unavail", $ab_unavail_cmd);
			$ab_greetings   = array_merge($ab_names, $ab_temps, $ab_busys, $ab_unavails);
			$current_time	= time();
			$one_day	= 24 * 60 * 60;
			foreach ($ab_greetings as $greeting_path) {
				if (time() - filemtime($greeting_path) > $one_day) {
					exec("rm -f $greeting_path");
				}
			}
		}

		$names = ($name) ? voicemail_get_greetings("greet", $name_cmd) : array();
		$unavails = ($unavail) ? voicemail_get_greetings("unavail", $unavail_cmd) : array();
		$busys = ($busy) ? voicemail_get_greetings("busy", $busy_cmd) : array();
		$temps = ($temp) ? voicemail_get_greetings("temp", $temp_cmd) : array();

		$greetings   = array_merge($names, $temps, $busys, $unavails);
		if (!empty($greetings)) {
			foreach ($greetings as $greeting_path) {
				exec ("rm -f $greeting_path");
			}
		}
	}
}
function voicemail_get_storage($path) {
	$storage_result = array();
	$matches        = array();
	foreach (glob($path) as $filename) {
		$storage_result[] = $filename;
	}
	if (preg_match("/[0-9]*\.*[0-9]*[a-zA-Z]*/", $storage_result[0], $matches) > 0) {
		$storage = $matches[0];
		unset($matches);
		$matches = array();
		# Expecting storage value as #.#U where # = number, . = dot, and U = units (e.g. M, K, etc.)
		# Massage the string so that there is a space between the number value and character(s)
		# denoting the unit
		#
		# Extract the numeric part. /[0-9]*\.*[0-9]*[a-zA-Z]*/
		if (preg_match("/[0-9]*\.*[0-9]*/", $storage, $matches)) {
			$st_num = $matches[0];
		} else {
			$st_num = "0";
		}
		unset($matches);
		$matches = array();
		if (preg_match("/[a-zA-Z]+$/", $storage, $matches)) {
			$st_unit = $matches[0];
		} else {
			$st_unit = "";
		}
		# reset $storage to new string
		$storage = $st_num . "&nbsp;" . $st_unit;
	} else {
		$storage = "unknown";
	}
	return $storage;
}
function voicemail_get_usage($vmail_info, $scope, &$acts_total, &$acts_act, &$acts_unact, &$disabled_count,
							&$msg_total, &$msg_in, &$msg_other,&$name, &$unavail, &$busy, &$temp, &$abandoned,
							&$storage, $context="", $extension="") {
	global $vmail_root;
	$msg_total = 0;
	$msg_in    = 0;
	$msg_other = 0;
	$name      = 0;
	$unavail   = 0;
	$busy      = 0;
	$temp      = 0;
	$abandoned = 0;
	switch ($scope) {
		case "system":
			$acts_act       = sizeof($vmail_info["activated_info"]);
			$acts_unact     = sizeof($vmail_info["unactivated_info"]);
			$disabled_count = sizeof($vmail_info["disabled_list"]);
			$acts_total = $acts_act + $acts_unact + $disabled_count;
			$storage    = voicemail_get_storage($vmail_root);
			foreach ($vmail_info["contexts"] as $c) {
				$count_msg_in  = 0;
				$count_msg_oth = 0;
				$count_name    = 0;
				$count_unavail = 0;
				$count_busy    = 0;
				$count_temp    = 0;
				$count_abandon = 0;
				$vmail_path = $vmail_root . "/" . $c;
				voicemail_file_usage($vmail_path, $count_msg_in, $count_msg_oth, $count_name, $count_unavail, $count_busy, $count_temp, $count_abandon);
				$msg_in    += $count_msg_in;
				$msg_other += $count_msg_oth;
				$name      += $count_name;
				$unavail   += $count_unavail;
				$busy      += $count_busy;
				$temp      += $count_temp;
				$abandoned += $count_abandon;

			}
			$msg_total = $msg_in + $msg_other;
			break;
		case "account":
			if (isset($vmail_info["activated_info"][$extension]) && $vmail_info["activated_info"][$extension] == $context) {
				$vmail_path = $vmail_root . "/" . $context . "/" . $extension;
				voicemail_file_usage($vmail_path, $msg_in, $msg_other, $name, $unavail, $busy, $temp, $abandoned, true);
				$storage    = voicemail_get_storage($vmail_path);
				$msg_total = $msg_in + $msg_other;
				$acts_act = 1;
				$acts_unact = 0;
			} else {
				$acts_unact = 1;
			}
			break;
		default:
			break;
	}
}
function voicemail_file_usage($path, &$inmsg_cnt, &$othmsg_cnt, &$greet_cnt, &$unavail_cnt, &$busy_cnt, &$temp_cnt, &$abandoned_cnt, $acct_flag=false) {
	if ($acct_flag) { /* account-specific; account included in path passed in */
		# greetings, all
		$greet_cmd	= "$path/greet.*";
		$unavail_cmd 	= "$path/unavail.*";
		$busy_cmd	= "$path/busy.*";
		$temp_cmd	= "$path/temp.*";

		# abandoned greetings
		$agreet_cmd	= "$path/greet.tmp.*";
		$aunavail_cmd	= "$path/unavail.tmp.*";
		$abusy_cmd	= "$path/busy.tmp.*";
		$atemp_cmd	= "$path/temp.tmp.*";

		# inbox messages
		$inmsg_cmd	= "$path/INBOX/msg*.txt";

		# all messages
		$allmsg_cmd	= "$path/*/msg*.txt";
	} else { /* system-wide */
		# greetings, all
		$greet_cmd	= "$path/*/greet.*";
		$unavail_cmd 	= "$path/*/unavail.*";
		$busy_cmd	= "$path/*/busy.*";
		$temp_cmd	= "$path/*/temp.*";

		# abandoned greetings
		$agreet_cmd	= "$path/*/greet.tmp.*";
		$aunavail_cmd	= "$path/*/unavail.tmp.*";
		$abusy_cmd	= "$path/*/busy.tmp.*";
		$atemp_cmd	= "$path/*/temp.tmp.*";

		# inbox messages
		$inmsg_cmd	= "$path/*/INBOX/msg*.txt";

		# all messages
		$allmsg_cmd	= "$path/*/*/msg*.txt";
	}

	if (is_dir($path)) {
		$greet_cnt   	= voicemail_count_greetings("greet", $greet_cmd);
		$temp_cnt    	= voicemail_count_greetings("temp", $temp_cmd);
		$busy_cnt    	= voicemail_count_greetings("busy", $busy_cmd);
		$unavail_cnt 	= voicemail_count_greetings("unavail", $unavail_cmd);


		$agreet_cnt 	= voicemail_count_ab_greetings("greet", $agreet_cmd);
		$aunavail_cnt 	= voicemail_count_ab_greetings("unavail", $aunavail_cmd);
		$abusy_cnt 	= voicemail_count_ab_greetings("busy", $abusy_cmd);
		$atemp_cnt 	= voicemail_count_ab_greetings("temp", $atemp_cmd);


		$inmsg_cnt 	= voicemail_count_msg($inmsg_cmd);
		$allmsg_cnt 	= voicemail_count_msg($allmsg_cmd);

		$othmsg_cnt 	= $allmsg_cnt - $inmsg_cnt;
		$abandoned_cnt 	= $agreet_cnt + $abusy_cnt + $atemp_cnt + $aunavail_cnt;

	}

}
function voicemail_strip_exten_from_greet_path($greet_path) {
	$path_array = explode("/", $greet_path);
	$n = sizeof($path_array);
	$exten = $path_array[$n-2];
	return $exten;
}
function voicemail_count_greetings($greeting, $path) {
	/* get a list of all greeting files */
	$file_list = voicemail_get_greetings($greeting, $path);
	$greet_list = array();
	/* greeting can be in multiple formats, making file count greater than greeting */
	/* count, so make array with one entry for each extension that has the greeting */
	foreach ($file_list as $greeting_file) {
		$greet_list[voicemail_strip_exten_from_greet_path($greeting_file)] = true;
	}
	return sizeof($greet_list);
}
function voicemail_get_greetings($greeting, $path) {
	$results = array();
	$greet_list = array();

	foreach (glob($path) as $filename) {
		/* filter out abandoned greeting recordings */
		$pat = "/.*" . $greeting . "\.tmp\..+/";
		if (!preg_match($pat, $filename))
			$greet_list[] = $filename;
	}
	return $greet_list;
}
function voicemail_count_ab_greetings($greeting, $cmd) {

	$file_list = voicemail_get_ab_greetings($greeting, $cmd);
	$greet_list = array();
	/* greeting can be in multiple formats, making file count greater than greeting */
	/* count, so make array with one entry for each extension that has the greeting */
	foreach ($file_list as $greeting_file) {
		$greet_list[voicemail_strip_exten_from_greet_path($greeting_file)] = true;
	}
	return sizeof($greet_list);
}
function voicemail_get_ab_greetings($greeting, $path) {
	$results = array();
	$greet_list = array();

	foreach (glob($path) as $filename) {
		$greet_list[] = $filename;
	}
	return $greet_list;
}
function voicemail_count_msg($path) {
	$results = array();
	$msg_cnt = 0;

	/* Message can be recorded in multiple formats, but there is always one text */
	/* file for each message, so count the text files. */
	foreach (glob($path) as $r) {
		if (preg_match("/.+\/msg[0-9][0-9][0-9][0-9]\.txt\/{0,1}/", $r)) {
			$msg_cnt++;
		}
	}
	return $msg_cnt;
}
function voicemail_get_greeting_timestamps($name=0, $unavail=0, $busy=0, $temp=0, $context="", $extension="") {
	global $vmail_root;
	if ($context == "" || $extension == "") {
		return null;
	}
	$vmail_path = $vmail_root . "/$context/$extension";
	$ts["name"] = 0;
	$ts["unavail"] = 0;
	$ts["busy"] = 0;
	$ts["temp"] = 0;
	if ($name) {
		$listing = array();
		exec("ls $vmail_path/greet.*", $listing);
		foreach ($listing as $entry) {
			if (!preg_match("/greet\.tmp\..+/", $entry)) {
				$ts["name"] = date("Y-m-d", filemtime("$entry"));
				break;
			}
		}
	}
	if ($unavail) {
		$listing = array();
		exec("ls $vmail_path/unavail.*", $listing);
		foreach ($listing as $entry) {
			if (!preg_match("/unavail\.tmp\..+/", $entry)) {
				$ts["unavail"] = date("Y-m-d", filemtime("$entry"));
				break;
			}
		}
	}
	if ($busy) {
		$listing = array();
		exec("ls $vmail_path/busy.*", $listing);
		foreach ($listing as $entry) {
			if (!preg_match("/busy\.tmp\..+/", $entry)) {
				$ts["busy"] = date("Y-m-d", filemtime("$entry"));
				break;
			}
		}
	}
	if ($temp) {
		$listing = array();
		exec("ls $vmail_path/temp.*", $listing);
		foreach ($listing as $entry) {
			if (!preg_match("/temp\.tmp\..+/", $entry)) {
				$ts["temp"] = date("Y-m-d", filemtime("$entry"));
				break;
			}
		}
	}
	return $ts;
}
?>
