<?php
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

global $astman;
if ($astman) {
	$stats = $astman->send_request('Command',array('Command'=>"vxml show statistics"));
	$stats = explode("\n",$stats['data']);
	$top = $astman->send_request('Command',array('Command'=>"vxml show top"));
	$top = explode("\n",$top['data']);	
}

?>
<style TYPE = "text/css">                                     
.showtable, .showtable TD, .showtable TH {                                                             
	font-family: verdana;                                         
	font-size: 10pt;                                              
	line-height: 0.5;                                             
}                                                             
</style>

<h2>VoiceXML Statistics</h2>

<table>
	<tbody>
		<tr>
			<td style="width: 450px"><h5>Top<hr></h5></td><td style="width: 250px"></td><td style="width: 400px"><h5>Statistics<hr></td>
		</tr>
		<tr>
			<td valign="top">
				<table class="showtable">					
				<?php 
					for ($i = 1; $i < count($top); $i++) {
						$line = $top[$i];
						if (empty($line)) continue;
						$elements = explode(":",$line);
						if (strpos($line, "Asterisk PID") !== false || strpos($line, "Interpreter PID") !== false || strpos($line, "Queue ID") !== false) {
							echo "<tr><td>$elements[0]</td><td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td><td>$elements[1]</td><tr>";
						} else {
							echo "<tr><td>&nbsp;&nbsp;$elements[0]</td><td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td><td>$elements[1]</td><tr>";
						}
				 	}
				 ?>
				</table>
			</td>
			<td></td>
			<td valign="top">
				<table class="showtable">
				<?php 
					for ($i = 1; $i < count($stats); $i++) {
						if (empty($stats[$i])) continue;
						$elements = explode(":",$stats[$i]);
						echo "<tr><td>$elements[0]</td><td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td><td>$elements[1]</td><tr>";
					}
				?>
				</table>			
			</td>
		</tr>
		<tr>
			<td>
				<form action="">
					<br>
					<input type="button" onclick="history.go(0)" value="Refresh">
				</form>
			</td>
			<td></td><td></td>
		</tr>
	</tbody>
</table>