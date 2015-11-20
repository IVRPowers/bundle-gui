<?php 
if (isset($_POST['cleanAction'])) {
	
	switch ($_POST['cleanAction']) {
		case 'callRecording':
			$res = shell_exec("rm -rf /var/spool/asterisk/monitor/*");
			break;
		case 'interpreterCache':
			$res = shell_exec("rm -rf /tmp/cacheContent/* /tmp/logContent/*");
			break;
		default:
	}
	
} 
	

$callRecording = trim(shell_exec('du -hs /var/spool/asterisk/monitor | awk \'{ print $1 }\''));
$interpreterCache = trim(shell_exec('du -csh /tmp/cacheContent /tmp/logContent | awk \'/total/ { print $1}\''));
$diskSize = trim(shell_exec('df -h --total | awk \'/total/ { print $2 }\''));
$diskUsage = trim(shell_exec('df -h --total | awk \'/total/ { print $3 }\''));
$freeDisk = trim(shell_exec('df -h --total | awk \'/total/ { print $4 }\'')); 

?>

<style TYPE = "text/css">                                     
.showtable, .showtable TD, .showtable TH {                                                             
	font-family: verdana;                                         
	font-size: 10pt;                                              
	line-height: 0.5;                                             
}                                                             
</style> 
<table width="700px">
	<tr>
		<td colspan="2"><h5>Space Usage<hr></h5></td>
	</tr>
	<tr>
		<td colspan="2">
			<table class="showtable">
				<tr>
					<td>Call Recording</td>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td><?php echo $callRecording?></td>
				</tr>
				<tr>
					<td>Interpreter Cache</td>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td><?php echo $interpreterCache?></td>
				</tr>
				<tr>
					<td>Total Disk</td>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td><?php echo $diskUsage?></td>
				</tr>
				<tr>
					<td>Reaining Free Disk Space</td>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td><?php echo $freeDisk?></td>
				</tr>
				<tr>
					<td>Total Disk Size</td>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td><?php echo $diskSize?></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="2"><h5>Actions<hr></h5></td>
	</tr>
	<tr>
		<td>
			<table>
				<tr>
					<td>
						<form action="config.php?display=vxmlsettings&view=cleaning" method="post">
							<input type="hidden" name="cleanAction" value="callRecording">
							<input type="submit" value="Clean Call Recording">
						</form>
					</td>
					<td>
						<form action="config.php?display=vxmlsettings&view=cleaning" method="post">
							<input type="hidden" name="cleanAction" value="interpreterCache">
							<input type="submit" value="Clean Interpreter Cache">
						</form>
					</td>
				</tr>			
			</table>
		</td>
	</tr>
</table>




