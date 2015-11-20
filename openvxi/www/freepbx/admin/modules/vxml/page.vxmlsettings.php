<?php
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }?>


<h2>VoiceXML Settings</h2>
<table width="700px">
		<tr>
			<td><br><h5>Categories: &nbsp;&nbsp;&nbsp;&nbsp;<a href="config.php?display=vxmlsettings&view=general">General</a>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;<a href="config.php?display=vxmlsettings&view=license">License</a></h5></td>		
		</tr>
</table>
<?php 
	if (isset($_GET['view']) && $_GET['view'] == "license") {
		echo load_view(dirname(__FILE__) . '/views/license.vxmlsettings.php');
	} else {
		echo load_view(dirname(__FILE__) . '/views/general.vxmlsettings.php');
	}
?>

