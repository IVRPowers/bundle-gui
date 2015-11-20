<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); };
?>
<style>
#current {
	text-decoration: underline;
}
ul#tabnav {
    font: bold 11px verdana, arial, sans-serif;
    list-style-type: none;
    padding-bottom: 24px;
    border-bottom: 1px solid #808080;
    margin: 0;
}
ul#tabnav li {
    float: left;
    height: 21px;
    background-color: #dbf1ff;
    margin: 2px 2px 0 2px;
    border: 1px solid #808080;
    height: 23px;
}
ul#tabnav li.active {
    border-bottom: 1px solid #fff;
    background-color: #fff;
}
ul#tabnav li.active a {
    color: #000;
}
#tabnav a {
    float: left;
    display: block;
    color: black;
    text-decoration: none;
    padding: 4px;
}
#tabnav a:hover {
    background: #fff;
}
</style>
<?php if (!(isset($_GET['action']) && $_GET['action'] == "download_audio")) {?>
	<h3><?php echo _('CDR Reports'); ?></h3>
	<table width="100%">
		<tr>
			<td>
				<br>
				<ul id="tabnav">
	     			<li <?php echo (!isset($_GET['view']) || $_GET['view'] == "cdrsearch") ? 'class="active"' : ''?>><a href="config.php?display=cdr&view=cdrsearch">CDR Search</a></li>
	     			<li <?php echo ($_GET['view'] == "callscompare") ? 'class="active"' : ''?>><a href="config.php?display=cdr&view=callscompare">Calls Compare</a></li>
	     			<li <?php echo ($_GET['view'] == "monthlytraffic") ? 'class="active"' : ''?>><a href="config.php?display=cdr&view=monthlytraffic">Monthly Traffic</a></li>
	     			<li <?php echo ($_GET['view'] == "dailyload") ? 'class="active"' : ''?>><a href="config.php?display=cdr&view=dailyload">Daily Load</a></li>
				</ul>
				<?php /*?>
				<h5>Categories: &nbsp;&nbsp;&nbsp;&nbsp;<a href="config.php?display=cdr&view=cdrsearch"><span <?php echo (!isset($_GET['view']) || $_GET['view'] == "cdrsearch") ? 'id="current"' : ''?>>CDR Search</span></a>&nbsp;&nbsp;&nbsp;&nbsp;|
			                    &nbsp;&nbsp;&nbsp;&nbsp;<a href="config.php?display=cdr&view=callscompare"><span <?php echo ($_GET['view'] == "callscompare") ? 'id="current"' : ''?>>Calls Compare</span></a>&nbsp;&nbsp;&nbsp;&nbsp;|
			                    &nbsp;&nbsp;&nbsp;&nbsp;<a href="config.php?display=cdr&view=monthlytraffic"><span <?php echo ($_GET['view'] == "monthlytraffic") ? 'id="current"' : ''?>>Monthly Traffic</span></a>&nbsp;&nbsp;&nbsp;&nbsp;|
			                    &nbsp;&nbsp;&nbsp;&nbsp;<a href="config.php?display=cdr&view=dailyload"><span <?php echo ($_GET['view'] == "dailyload") ? 'id="current"' : ''?>>Daily Load</span></a>
			    </h5>*/?>
			</td>		
		</tr>
	</table><br>
<?php }?>
<?php if (!isset($_GET['view']) || $_GET['view'] == "cdrsearch") {?>

<?php require_once 'categories/cdrsearch.php';?>		

<?php } elseif ($_GET['view'] == 'callscompare') {?>

<?php include_once 'categories/callscompare.php';?>		
		
<?php } elseif ($_GET['view'] == 'monthlytraffic') {?>

<?php include_once 'categories/monthlytraffic.php';?>	

<?php } elseif ($_GET['view'] == 'dailyload') {?>

<?php include_once 'categories/dailyload.php';?>	

<?php }?>
