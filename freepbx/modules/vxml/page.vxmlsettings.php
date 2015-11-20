<?php
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

//This is the function for the "Restart Interpreter" button
if (isset($_POST['action']) && $_POST['action'] == "restart") {

	manageInterpreter("restart");
	#$openvxi = shell_exec("service openvxi status");
	#$asterisk = shell_exec("service asterisk status");
	$openvxi = shell_exec("ps aux | grep '/usr/sbin/openvxi' | egrep -v 'grep|root' | wc -l");
	$asterisk = shell_exec("ps aux | grep '/usr/sbin/asterisk' | grep -v 'grep' | wc -l");
	
	$msg = "";
	
	#if (strpos($openvxi,"running") === false && strpos($openvxi,"ejecutando") === false) {
	if ($openvxi < 1) {
		$msg = $msg."There has been a problem and the interpreter has stopped working. <br>";
	}
	#if (strpos($asterisk,"running") === false && strpos($asterisk,"ejecutando") === false) {
	if ($asterisk < 1) {
		$msg = $msg."There has been a problem and the telephony has stopped working. <br>";
	}
	
	if (empty($msg)) {
		$msg = "OK";
	} else {
		$msg = $msg."Try to restart again the interpreter to check if it can be solved.";
	}
	
	echo json_encode(array('result' => $msg));
	exit();
}



$view = "general";
$generalStyle = "color: #ff9933";
$licenseStyle = "";
$cleaningStyle = "";
if (isset($_GET['view']) && $_GET['view'] == "license") {
    $view = 'license';
    $generalStyle = "";
    $licenseStyle = "color: #ff9933";
    $cleaningStyle = "";
} elseif (isset($_GET['view']) && $_GET['view'] == "cleaning") {
    $view = 'cleaning';
    $generalStyle = "";
    $licenseStyle = "";
    $cleaningStyle = "color: #ff9933";
}

?>



<h2>VoiceXML Settings</h2>
<table width="700px">
	<tr>
    	<!-- <td><br><h5>Categories: &nbsp;&nbsp;&nbsp;&nbsp;<span class="<?php echo $viewGeneralClass;?>"><a href="config.php?display=vxmlsettings&view=general">General</a></span>&nbsp;&nbsp;<span class="<?php echo $viewLicenseClass;?>"><a href="config.php?display=vxmlsettings&view=license">License</a></span></h5></td> -->
    	<!-- 
    	<td>
    		<div class="menubar ui-corner-all">
    			<a class="<?php echo $viewGeneralClass;?>" href="config.php?display=vxmlsettings&view=general">General</a>&nbsp;
    			<a class="<?php echo $viewLicenseClass;?>" href="config.php?display=vxmlsettings&view=license">License</a>&nbsp;
    			<a class="<?php echo $viewCleaningClass;?>" href="config.php?display=vxmlsettings&view=cleaning">System Cleaning</a>
    		</div>
    	</td>
    	-->
    	<tr>
			<td>
				<br>
				<h5>Categories: 
					&nbsp;&nbsp;&nbsp;&nbsp;<a style="<?php echo $generalStyle;?>" href="config.php?display=vxmlsettings&view=general">General</a>&nbsp;&nbsp;&nbsp;&nbsp;|
					&nbsp;&nbsp;&nbsp;&nbsp;<a style="<?php echo $licenseStyle;?>" href="config.php?display=vxmlsettings&view=license">License</a>&nbsp;&nbsp;&nbsp;&nbsp;|
					&nbsp;&nbsp;&nbsp;&nbsp;<a style="<?php echo $cleaningStyle;?>" href="config.php?display=vxmlsettings&view=cleaning">System Cleaning</a>
				</h5>
			</td>		
		</tr>
	</tr>
</table>
<?php
switch ($view) {
	case 'general':
		echo load_view(dirname(__FILE__) . '/views/general.vxmlsettings.php');
		break;
	case 'license':
		echo load_view(dirname(__FILE__) . '/views/license.vxmlsettings.php');
		break;
	case 'cleaning':
		echo load_view(dirname(__FILE__) . '/views/cleaning.vxmlsettings.php');
		break;
	default:
}
?>