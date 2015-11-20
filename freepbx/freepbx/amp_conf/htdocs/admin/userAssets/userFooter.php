<?php include_once 'userAssets/userAssets.php';

$url = parse_url($GLOBALS['companyURL']);
$urlName = str_replace("www.","",$url["host"]);

?>



<table class="main_footer">
	<tr>
		<td>
			<table class="left_footer">
				<tr>
					<td>
						<img id="footer_icon_left" src="<?php echo $GLOBALS['userFooterIcon']?>">
					</td>
					<td>
						<table>
						<?php if ($GLOBALS['company'] == "ivrpowers") {?>
							<tr><td class="platform_name text_left user_font"><span>IVR</span><span style="font-weight: bold; font-size: 1.4em">Powers VoiceXML Platform</span><td></tr>
						<?php } else {?>
							<tr><td class="platform_name text_left user_font"><span class="coloredName"><?php echo $GLOBALS['companyDisplay']?></span><span style="font-weight: bold; font-size: 1.4em"> VoiceXML Platform</span><td></tr>
						<?php }?>
							<tr><td class="text_left user_font"><span class="version"><?php echo $GLOBALS['userVersion']?></span><span class="release"> / Released <?php echo $GLOBALS['userReleasedDate']?></span></td></tr>
						</table>
					</td>
				</tr>
			</table>		
		</td>
		<td>
			<table class="right_footer">
				<tr>
					<td><a href="mailto:<?php echo $GLOBALS['supportMail']?>" target="_top"><img id="support_icon" src="<?php echo $GLOBALS['userSupport']?>"></a></td>
					<td class="supp_text user_font"><a style="color: black" href="mailto:<?php echo $GLOBALS['supportMail']?>" target="_top">Support</a></td>
				<?php if ($GLOBALS['company'] == "ivrpowers") {?>
					<td class="user_font" style="padding-right: 20px"><a id="link_company" target="_blank" href="<?php echo $GLOBALS['companyURL']?>"><span class="support_link">IVR</span><span class="support_link" style="font-weight: bold; font-size: 1.4em">Powers.com</span></a></td>
				<?php } else {?>
					<td class="user_font" style="padding-right: 20px"><a id="link_company" target="_blank" href="<?php echo $GLOBALS['companyURL']?>"><span class="support_link" style="font-size: 1.4em"><?php echo $urlName?></span></a></td>
				<?php }?>
				</tr>			
			</table>
		</td>
	</tr>
</table>

<style>
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
<?php } else {?>
span.coloredName {
	font-size: 1.4em;
	color: <?php echo ($GLOBALS['company'] == "i6net") ? $GLOBALS['backgroundColor'] : $GLOBALS['colorLoginButton']?>;
}
<?php }?>
table.main_footer td.platform_name.user_font {
<?php if ($GLOBALS['company'] == "ivrpowers") {?>
	font-family: "Museo Sans Rounded";
<?php }?>	
	padding-top: 10px;
}
.text_left {
	text-align: left;
}
table.main_footer td, table.left_footer td, .table.right_footer td {
	width: auto;
}
table.main_footer td {
	padding-bottom: 0px;
<?php if ($GLOBALS['company'] == "ivrpowers") {?>
	padding-top: 0px;
<?php } else { ?>
	padding-top: 2px;
<?php }?>
}
table.main_footer {
	width: 100%;
	margin: 0;
	padding: 0;
<?php if ($GLOBALS['company'] == "ivrpowers") {?>
	font-family: "Museo Sans Rounded";
<?php }?>	
}
#footer_icon_left {
	max-height: 100px;
	max-width: 80px;
	margin-top: 11px;
	margin-left: 3px;
}
span.version {
	color: <?php echo ($GLOBALS['company'] == "ivrpowers" || $GLOBALS['company'] == "i6net") ? $GLOBALS['backgroundColor'] : $GLOBALS['colorLoginButton']?>;
	font-weight: bold;
}
span.release {
	color: grey;
}
td.platform_name {
	color: black;
}
table.left_footer {
	margin-right: auto;
	margin-left: 0px;
	font-size: 1.2em;
}
#footer_content {
	padding-top: 0px;
	width: 100%;
	margin: 0;
}
table.right_footer {
	margin-right: 0;
	margin-left: auto;
	font-size: 1.3em;
	color: black;
}
#support_icon {
	height: 70%;
	width: 70%;
}
.support_link {
	color: <?php echo ($GLOBALS['company'] == "ivrpowers" || $GLOBALS['company'] == "i6net") ? $GLOBALS['backgroundColor'] : $GLOBALS['colorLoginButton']?>;
}
td.supp_text {
	padding-left: 0;
	padding-right: 30px;
	text-align: left;
	font-size: 1.34em;
}
#link_company:hover {
    text-decoration: none;
}
</style>