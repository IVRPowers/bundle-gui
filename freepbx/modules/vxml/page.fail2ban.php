<?php 

//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$fail2banLog = '/var/log/fail2ban';
if (isset($_POST['emptyFail2Ban'])) {

	$res = shell_exec("echo '' > $fail2banLog");	

}

$iptables = shell_exec("sudo iptables -L");
$lines = explode("\n",$iptables);

$chains = array();
$numberOfIPs = 0;
for ($i = 0; $i < count($lines); $i++) {
	$line = $lines[$i];
	if (empty($line)) continue;
	if (strpos($line,'Chain') !== false && strpos($line,'f2b-') !== false) {
		$elements = preg_split('/\s+/', $line);	
		$chain = str_replace(array('f2b-','-'),array('',' '),$elements[1]);
		$i = $i + 2; #We skip the headers line
		$ips = array();
		while (strpos($lines[$i],'RETURN') === false) {
			$elements = preg_split('/\s+/', $lines[$i]);
			$ips[] = $elements[3];
			$i++;
		} 
		$numberOfIPs = $numberOfIPs + count($ips);	
		$chains[$chain] = $ips;
	}
}

$totalBannedIPs = trim(shell_exec("grep NOTICE $fail2banLog | egrep -v 'Unban|banned' | wc -l"));
$fail2banLogSize = trim(shell_exec("du -bh $fail2banLog | cut -f1"));
$freeSpace = trim(shell_exec('df -h --total | awk \'/total/ { print $4 }\'')); 

$rulesDescriptions = array(
	"apache badbots" => "Harmfull bots accessing the site",
	"apache botsearch" => "Access attempts to specific URLs that don't exist",
	"apache fakegooglebot" => "Fake Googlebot User Agents",
	"apache nohome" => "Failures to find a home directory on the server",
	"apache noscript" => "Potential search for exploits and php vulnerabilities",
	"apache overflows" => "Apache overflow attempts",
	"asterisk" => "Any hacking attempt into Asterisk (Telephony)",
	"ssh" => "Repetitive ssh login failures",
	"ssh ddos" => "SSH exploit attempts",
	"web auth" => "Repetitive failures to log into the web panel"
);

?>

<h2>Fail2Ban Status</h2>

<style>
.chainName {
	text-transform: uppercase;
}
.chainsHead {
	text-align: center;
	min-width: 150px;
}
.fail2banLegend {
	border: 2px solid #808080;
    padding: 10px;
    display: inline;
    margin-top: 30px;
    margin-bottom: 30px;
}
.legendTitle {
	color: #777;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 0px;
    width: inherit;
    border: none;
}
.fail2banLegend td {
	font-size: 13px;
}
.ruleName {
	text-transform: capitalize;
}

.f2b-section-header {
	float: left;
}

.f2b-section-separator {
	clear: left;
	display: block;
	width: 500px;
	margin-left: 0;
}

.f2b-section-header + * {
	clear: left;
}

.f2b-paragraph {
	margin-left: 20px;
}

.f2b-italic {
	font-style: italic;
}

.f2b-good {
	color: green;
}

.f2b-bad {
	color: red;
}

.f2b-paragraph form {
	margin-left: 20px;
}
</style>

<br>
<h5 class="f2b-section-header">Summary<hr class="f2b-section-separator"></h5>
<br><br>
<p class="f2b-paragraph">There are currently <span class="f2b-italic f2b-<?php echo ($numberOfIPs > 0) ? 'bad' : 'good'?>"><?php echo $numberOfIPs?></span> banned IPs.</p>
<p class="f2b-paragraph">The number of banned IPs until now is <span class="f2b-italic f2b-<?php echo ($totalBannedIPs > 0) ? 'bad' : 'good'?>"><?php echo $totalBannedIPs?></span>.</p>
<br>

<h5 class="f2b-section-header">Currently banned IPs <span class="f2b-italic" style="font-size: ">(IPs will remain banned for 10 hours)</span><hr class="f2b-section-separator"></h5>
<br><br>
<?php if ($numberOfIPs > 0) {?>
<table>
	<thead>
		<tr>
			<th class="chainsHead">Banning Rule</th>
			<th class="chainsHead">Banned IP</th>
		</tr>
	</thead>
	<tbody>
		<?php 
			foreach ($chains as $chain => $ips) {
				if (!empty($ips)) {
					foreach ($ips as $ip) {
		?>		
		<tr>
			<td class="chainName"><?php echo $chain?></td>
			<td><?php echo $ip?></td>
		</tr>
		<?php }}}?>
	</tbody>
</table>
<?php } else {?>
<p class="f2b-paragraph f2b-italic">There are no currently banned IPs.</p>
<?php }?>
<br>
<h5 class="f2b-section-header">Disk Usage<hr class="f2b-section-separator"></h5>
<br><br>
<p class="f2b-paragraph">Fail2Ban log uses <?php echo $fail2banLogSize?>B and there are <?php echo $freeSpace?>B of free space in the disk.</p><br>
<form class="f2b-paragraph" action="config.php?display=fail2ban" method="post" >
	<input type="hidden" name="emptyFail2Ban" value="true">
	<input type="submit" value="Empty Fail2Ban Log"> <span class="f2b-italic">(This will delete any previous stats.)</span>
</form>
<br>
<fieldset class="fail2banLegend">
	<legend class="legendTitle">Banning reasons</legend>	
	<table>
		<thead>
			<tr>
				<th class="chainsHead">Rule</th>
				<th class="chainsHead">Description</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($rulesDescriptions as $rule => $description) {?>
			<tr>
				<td class="<?php echo (strpos($rule,"ssh") !== false) ? "chainName" : "ruleName" ?>"><?php echo $rule?></td>
				<td><?php echo $description?></td> 
			</tr>
			<?php }?>
		</tbody>
	</table>
</fieldset>