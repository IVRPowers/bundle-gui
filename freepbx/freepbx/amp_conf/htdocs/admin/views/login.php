<?php

include_once 'userAssets/userAssets.php';
$loginIcon = $GLOBALS['userLoginIcon'];

$html = '';
//$html .= heading(_('Welcome!'), 3) . '<hr class="backup-hr"/>';
/*if ($errors) {
	$html .= '<span class="obe_error">';
	$html .= _('Please correct the following errors:');
	$html .= ul($errors);
	$html .= '</span>';
}*/
if ($errors) {
	$html .= '<div id="login_error" style="display: none">';
	$html .= '<span class="obe_error">';
	$html .= _('Please correct the following errors:');
	$html .= br(1);
	$html .= ul($errors);
	$html .= '</span>';
	$html .= '</div>';
	
}
$html .= '<div id="login_form">';
$html .= form_open('config.php', 'id="loginform"');
$html .= _('To get started, please enter your credentials:');
$html .= br(2);
$data = array(
			'name' => 'username',
			'placeholder' => _('username')
		);
$html .= form_input($data);
$html .= br(2);
$data = array(
			'name' => 'password',
			'type' => 'password',
			'placeholder' => _('password')
		);
$html .= form_input($data);
$html .= br(2);
//$html .= form_submit('submit', _('Login'));
//$html .= br(2);
$html .= form_close();
$html .= '</div>';
$html .= '<div id="login_icon_holder">';
//$html .= '<div class="login_item_title"><a href="#" class="login_item" id="login_admin" style="background-image: url(assets/images/sys-admin.png);"/>&nbsp</a><span class="login_item_text" style="display: block;width: 160px;text-align: center;">' . _('FreePBX Administration') . '</span></div>';
//$html .= '<div class="login_item_title"><a href="#" class="login_item" id="login_admin" style="background-image: url(assets/images/sys-admin.png);"/>&nbsp</a><span class="login_item_text" style="display: block;width: 160px;text-align: center;">' . _('VoiceXML IVR Administration') . '</span></div>';
$html .= '<div class="login_item_title"><span class="login_item" style="background-image: url('.$loginIcon.');"/>&nbsp</span><div class="mylogin" id="login_admin" style="text-align: center;">' . _('LOGIN') . '</div></div>';
//$html .= '<div class="login_item_title"><a href="/ucp" '
//                . 'class="login_item" id="login_ari" style="background-image: url(assets/images/user-control.png);"/>&nbsp</a><span class="login_item_text" style="display: block;width: 160px;text-align: center;">' . _('User Control Panel') . '</span></div>';
//				  . 'class="login_item" id="login_ari" style="background-image: url(assets/images/user-control.png);"/>&nbsp</a><span class="login_item_text" style="display: block;width: 230px;text-align: center;">' . _('User Control Panel') . '</span></div>';
//if ($panel) {
//    $html .= '<div class="login_item_title"><a href="' . $panel . '" '
//		    . 'class="login_item" id="login_fop" style="background-image: url(assets/images/operator-panel.png);"/>&nbsp</a><span class="login_item_text" style="display: block;width: 160px;text-align: center;">' . _('Operator Panel') . '</span></div>';
//    		. 'class="login_item" id="login_fop" style="background-image: url(assets/images/operator-panel.png);"/>&nbsp</a><span class="login_item_text" style="display: block;width: 190px;text-align: center;">' . _('Operator Panel') . '</span></div>';
//}
//$html .= '<div class="login_item_title"><a href="http://www.schmoozecom.com/oss.php" '
//		. 'class="login_item" id="login_support" style="background-image: url(assets/images/support.png);"/>&nbsp</a><span class="login_item_text" style="display: block;width: 160px;text-align: center;">' . _('Get Support') . '</span></div>';
//		. 'class="login_item" id="login_support" style="background-image: url(assets/images/support.png);"/>&nbsp</a><span class="login_item_text" style="display: block;width: 230px;text-align: center;">' . _('Get Support') . '</span></div>';
$html .= '<div></div>';
$html .= '</div>';
/*
$html .= br(5) . '<div id="key" style="color: white;font-size:small">'
	  . session_id()
	  . '</div>';
*/
/*$html .= '<script type="text/javascript">';
$html .= '$(document).ready(function(){
		$("#key").click(function(){
			dest = "ssh://" + window.location.hostname + " \"/usr/sbin/amportal a u ' . session_id() . '\"";
			console.log(dest)
			window.open(dest).close(); setTimeout(\'window.location.reload()\', 3000);
		});
})';
$html .= '</script>';*/

$html .= '<script type="text/javascript" src="assets/js/views/login.js"></script>';

echo $html;

?>
<!-- This will pop the possible login errors -->
<script type="text/javascript">
$(document).ready(function() {
	if ($('#login_error').length) {
		var box = $("<div id='myLoginError'></div>")
								.html($('#login_error').html())
								.dialog({
									title: "Login Error",
									resizable: false,
									modal: true,
									minWidth: 300,
									position: [ "center", "center" ],
									close: function (e) {
										$(e.target).dialog("destroy").remove();
									},
									buttons: [
										{
											text: fpbx.msg.framework.retry,
											click: function() {
												$(this).dialog("destroy").remove();												
											}
										}
									]
								});	
	}
});
</script>


<!-- Added this to fix the style of the new login page" -->
<?php 
if (strpos($GLOBALS['backgroundColor'],'#fff') !== false || strpos($GLOBALS['backgroundColor'],'#FFF') !== false || $GLOBALS['backgroundColor'] == "white" || $GLOBALS['backgroundColor'] == "WHITE") $backgroundcolor = $GLOBALS['colorLoginButton'];
else $backgroundcolor = $GLOBALS['backgroundColor'];
?>
<style>
.ui-state-default, .ui-widget-content .ui-state-default, .ui-widget-header .ui-state-default {
  border: 1px solid <?php echo $backgroundcolor?>;
  background: <?php echo $backgroundcolor?>;
  font-weight: bold;
  color: <?php echo $GLOBALS['fontColorLoginButton']?>;
}
.ui-widget-header {
  	border: 1px solid <?php echo $backgroundcolor?>;
  	background: <?php echo $backgroundcolor?>;
  	color: <?php echo $GLOBALS['fontColorLoginButton']?>;
  	font-weight: bold;
}
.ui-widget-content {
  	border: 1px solid <?php echo $backgroundcolor?>;
  	background: <?php echo $GLOBALS['fontColorLoginButton']?>;
  	color: <?php echo $backgroundcolor?>;
  	font-weight: bold;
}
.ui-state-default .ui-icon, .ui-state-hover .ui-icon {
	background-image: url(userAssets/ui-icons-white.png);
}
<?php if ($GLOBALS['company'] == "ivrpowers") {?>
@font-face { 
	font-family: "Museo Sans Rounded"; 
	font-weight: normal;
	font-style: normal;
	src: local('Museo Sans Rounded'),
		 url('userAssets/ivr-powers/MuseoSans/MuseoSansRounded-500.otf') format('opentype'),
	     url('userAssets/ivr-powers/MuseoSans/MuseoSansRounded-500.svg') format('svg'),
	     url('userAssets/ivr-powers/MuseoSans/MuseoSansRounded-500.ttf') format('truetype'),
	     url('userAssets/ivr-powers/MuseoSans/MuseoSansRounded-500.woff') format('woff'),
	     url('userAssets/ivr-powers/MuseoSans/MuseoSansRounded-500.eot') format('embedded-opentype'); 
}
@font-face { 
	font-family: "Museo Sans Rounded"; 
	font-weight: bold;
	font-style: normal; 
	src: local('Museo Sans Rounded Bold'),
		 url('userAssets/ivr-powers/MuseoSans/MuseoSansRounded-900.otf') format('opentype'),
		 url('userAssets/ivr-powers/MuseoSans/MuseoSansRounded-900.svg') format('svg'),
		 url('userAssets/ivr-powers/MuseoSans/MuseoSansRounded-900.ttf') format('truetype'),
		 url('userAssets/ivr-powers/MuseoSans/MuseoSansRounded-900.woff') format('woff'),
	     url('userAssets/ivr-powers/MuseoSans/MuseoSansRounded-900.eot') format('embedded-opentype'); 
}
<?php }?>
#login_icon_holder {
	margin-top: 0;
	display: table-cell;
    vertical-align: middle;
}
div #page {
	heigh: 100% !important; 
}
#page_body {
	background-color: <?php echo $GLOBALS['backgroundColor']?>;
	width: 100%;
	margin: 0;
	height: 100%;
}
.login_item {
	background-size: <?php echo $GLOBALS['loginImageWidth']?>px <?php echo $GLOBALS['loginImageHeight']?>px; 
	height: <?php echo ($GLOBALS['loginImageHeight'] + 50)?>px;
	width: <?php echo ($GLOBALS['loginImageWidth'] + 200)?>px;
}
.login_item_title {
	width: <?php echo ($GLOBALS['loginImageWidth'] + 250)?>px;
}
#header {
	display: none;
}
div.mylogin {
	background-color: <?php echo $GLOBALS['colorLoginButton']?>;
  	color: <?php echo $GLOBALS['fontColorLoginButton']?>;
 	width: 100px;
  	height: 40px;
  	border-radius: 20px;
  	display: inline-block;
  	font-size: 17px;
  	font-weight: bold;
  	padding-top: 11px;
  	cursor: pointer;
<?php if ($GLOBALS['company'] == "ivrpowers") {?>
	font-family: "Museo Sans Rounded";
<?php }?>
}
</style>

<!-- We are fixing the page height -->
<script type="text/javascript">

$(document).ready(function() {
	$("#page").css("height","100%");
});

</script>





