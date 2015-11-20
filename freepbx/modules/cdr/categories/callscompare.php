<?php 

require_once(dirname(__FILE__) . "/lib/defines.php");

getpost_ifset(array('s','current_page', 'fromstatsday_sday', 'fromstatsmonth_sday', 'days_compare', 'min_call', 'posted',  'dsttype', 'sourcetype', 'clidtype', 'channel', 'resulttype', 'stitle', 'atmenu', 'current_page', 'order', 'sens', 'dst', 'src', 'clid', 'userfieldtype', 'userfield', 'accountcodetype', 'accountcode'));

if (!isset ($current_page) || ($current_page == "")){
	$current_page=0;
}

// this variable specifie the debug type (0 => nothing, 1 => sql result, 2 => boucle checking, 3 other value checking)
$FG_DEBUG = 0;

// The variable FG_TABLE_NAME define the table name to use
$FG_TABLE_NAME=DB_TABLENAME;

//$link = DbConnect();
$DBHandle  = DbConnect();

// The variable Var_col would define the col that we want show in your table
// First Name of the column in the html page, second name of the field
$FG_TABLE_COL = array();


/*******
 Calldate Clid Src Dst Dcontext Channel Dstchannel Lastapp Lastdata Duration Billsec Disposition Amaflags Accountcode Uniqueid Serverid
*******/

$FG_TABLE_COL[]=array ("Calldate", "calldate", "18%", "center", "SORT", "19");
$FG_TABLE_COL[]=array ("Channel", "channel", "13%", "center", "", "30");
$FG_TABLE_COL[]=array ("Source", "src", "10%", "center", "", "30");
$FG_TABLE_COL[]=array ("Clid", "clid", "12%", "center", "", "30");
$FG_TABLE_COL[]=array ("Lastapp", "lastapp", "8%", "center", "", "30");

$FG_TABLE_COL[]=array ("Lastdata", "lastdata", "12%", "center", "", "30");
$FG_TABLE_COL[]=array ("Dst", "dst", "9%", "center", "SORT", "30");
//$FG_TABLE_COL[]=array ("Serverid", "serverid", "10%", "center", "", "30");
$FG_TABLE_COL[]=array ("Disposition", "disposition", "9%", "center", "", "30");
$FG_TABLE_COL[]=array ("Duration", "duration", "6%", "center", "SORT", "30");


$FG_TABLE_DEFAULT_ORDER = "calldate";
$FG_TABLE_DEFAULT_SENS = "DESC";

// This Variable store the argument for the SQL query
$FG_COL_QUERY='calldate, channel, src, clid, lastapp, lastdata, dst, disposition, duration';
//$FG_COL_QUERY='calldate, channel, src, clid, lastapp, lastdata, dst, serverid, disposition, duration';
$FG_COL_QUERY_GRAPH='calldate, duration';

// The variable LIMITE_DISPLAY define the limit of record to display by page
$FG_LIMITE_DISPLAY=25;

// Number of column in the html table
$FG_NB_TABLE_COL=count($FG_TABLE_COL);

// The variable $FG_EDITION define if you want process to the edition of the database record
$FG_EDITION=true;

//This variable will store the total number of column
$FG_TOTAL_TABLE_COL = $FG_NB_TABLE_COL;
if ($FG_DELETION || $FG_EDITION) $FG_TOTAL_TABLE_COL++;

//This variable define the Title of the HTML table
$FG_HTML_TABLE_TITLE=" - Call Logs - ";

//This variable define the width of the HTML table
$FG_HTML_TABLE_WIDTH="90%";




if ($FG_DEBUG == 3) echo "<br>Table : $FG_TABLE_NAME  	- 	Col_query : $FG_COL_QUERY";
$instance_table = new Table($FG_TABLE_NAME, $FG_COL_QUERY);
$instance_table_graph = new Table($FG_TABLE_NAME, $FG_COL_QUERY_GRAPH);


if ( is_null ($order) || is_null($sens) ){
	$order = $FG_TABLE_DEFAULT_ORDER;
	$sens  = $FG_TABLE_DEFAULT_SENS;
}


if ($posted==1){


	function do_field($sql,$fld){
		$fldtype = $fld.'type';
		global $$fld;
		global $$fldtype;
		if (isset($$fld) && ($$fld!='')){
			if (strpos($sql,'WHERE') > 0){
				$sql = "$sql AND ";
			}else{
				$sql = "$sql WHERE ";
			}
			$sql = "$sql $fld";
			if (isset ($$fldtype)){
				switch ($$fldtype) {
					case 1:	$sql = "$sql='".$$fld."'";  break;
					case 2: $sql = "$sql LIKE '".$$fld."%'";  break;
					case 3: $sql = "$sql LIKE '%".$$fld."%'";  break;
					case 4: $sql = "$sql LIKE '%".$$fld."'";
				}
			}else{ $sql = "$sql LIKE '%".$$fld."%'"; }
		}
		return $sql;
	}
	$SQLcmd = '';

	if ($_POST['before']) {
		if (strpos($SQLcmd, 'WHERE') > 0) { 	$SQLcmd = "$SQLcmd AND ";
		}else{     								$SQLcmd = "$SQLcmd WHERE "; }
		$SQLcmd = "$SQLcmd calldate<'".$_POST['before']."'";
	}
	if ($_POST['after']) {    if (strpos($SQLcmd, 'WHERE') > 0) {      $SQLcmd = "$SQLcmd AND ";
	} else {      $SQLcmd = "$SQLcmd WHERE ";    }
	$SQLcmd = "$SQLcmd calldate>'".$_POST['after']."'";
	}
	$SQLcmd = do_field($SQLcmd, 'clid');
	$SQLcmd = do_field($SQLcmd, 'src');
	$SQLcmd = do_field($SQLcmd, 'dst');
	$SQLcmd = do_field($SQLcmd, 'channel');

	$SQLcmd = do_field($SQLcmd, 'userfield');
	$SQLcmd = do_field($SQLcmd, 'accountcode');


}


$date_clause='';
// Period (Month-Day)


if (!isset($fromstatsday_sday)){
	$fromstatsday_sday = date("d");
	$fromstatsmonth_sday = date("Y-m");
}


if (!isset($days_compare)){
	$days_compare=2;
}


if (DB_TYPE == "postgres"){
	if (isset($fromstatsday_sday) && isset($fromstatsmonth_sday)) $date_clause.=" AND calldate < date'$fromstatsmonth_sday-$fromstatsday_sday'+ INTERVAL '1 DAY' AND calldate >= date'$fromstatsmonth_sday-$fromstatsday_sday' - INTERVAL '$days_compare DAY'";
}else{
	if (isset($fromstatsday_sday) && isset($fromstatsmonth_sday)) $date_clause.=" AND calldate < ADDDATE('$fromstatsmonth_sday-$fromstatsday_sday',INTERVAL 1 DAY) AND calldate >= SUBDATE('$fromstatsmonth_sday-$fromstatsday_sday',INTERVAL $days_compare DAY)";
}

if ($FG_DEBUG == 3) echo "<br>$date_clause<br>";


if (strpos($SQLcmd, 'WHERE') > 0) {
	$FG_TABLE_CLAUSE = substr($SQLcmd,6).$date_clause;
}elseif (strpos($date_clause, 'AND') > 0){
	$FG_TABLE_CLAUSE = substr($date_clause,5);
}

if ($_POST['posted']==1){
	$list = $instance_table -> Get_list ($FG_TABLE_CLAUSE, $order, $sens, null, null, $FG_LIMITE_DISPLAY, $current_page*$FG_LIMITE_DISPLAY);

	$list_total = $instance_table_graph -> Get_list ($FG_TABLE_CLAUSE, null, null, null, null, null, null);
}


if ($FG_DEBUG == 3) echo "<br>Clause : $FG_TABLE_CLAUSE";
$nb_record = count($list_total);
if ($FG_DEBUG >= 1) var_dump ($list);



if ($nb_record<=$FG_LIMITE_DISPLAY){
	$nb_record_max=1;
}else{
	$nb_record_max=(intval($nb_record/$FG_LIMITE_DISPLAY)+1);
}

if ($FG_DEBUG == 3) echo "<br>Nb_record : $nb_record";
if ($FG_DEBUG == 3) echo "<br>Nb_record_max : $nb_record_max";

?>

<div id="maincdr">
	<table class="cdr">
		<tr>
			<td>
				<form method="post" action="config.php?display=cdr&view=callscompare" enctype="application/x-www-form-urlencoded">
				<INPUT TYPE="hidden" NAME="posted" value="1">
				<fieldset>
					<legend class="title"><?php echo _("Calls Compare")?></legend>
					<table style="width: 100%">
						<tr>
							<th style="width: 300px;"><?php echo _("Search Fields")?></th>
							<th><?php echo _("Search conditions")?></th>
							<th style="width: 300px;">&nbsp;</th>
						</tr>
						<tr>
							<td><?php echo _("&nbsp;&nbsp;&nbsp;&nbsp;Select the Day:")?></td>
							<td>
								<table width="100%">
									<tr>
										<td>
							  				&nbsp;&nbsp;From : <select name="fromstatsday_sday">
											<?php 
												for ($i=1;$i<=31;$i++){
													if ($fromstatsday_sday==sprintf("%02d",$i)){$selected="selected";}else{$selected="";}
													echo '<option value="'.sprintf("%02d",$i)."\"$selected>".sprintf("%02d",$i).'</option>';
												}
											?>					
											</select>
										 	<select name="fromstatsmonth_sday">
											<?php	$year_actual = date("Y");  	
												for ($i=$year_actual;$i >= $year_actual-1;$i--)
												{		   
													   $monthname = array( "January", "February","March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
													   if ($year_actual==$i){
															$monthnumber = date("n")-1; // Month number without lead 0.
													   }else{
															$monthnumber=11;
													   }		   
													   for ($j=$monthnumber;$j>=0;$j--){	
																$month_formated = sprintf("%02d",$j+1);
													   			if ($fromstatsmonth_sday=="$i-$month_formated"){$selected="selected";}else{$selected="";}
																echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";				
													   }
												}								
											?>										
											</select>
										</td>
										<td>&nbsp;&nbsp;
											Laps of days to compare : 
										 	<select name="days_compare">
												<option value="4" <?php if ($days_compare=="4"){ echo "selected";}?>>- 4 days</option>
												<option value="3" <?php if ($days_compare=="3"){ echo "selected";}?>>- 3 days</option>
												<option value="2" <?php if (($days_compare=="2")|| !isset($days_compare)){ echo "selected";}?>>- 2 days</option>
												<option value="1" <?php if ($days_compare=="1"){ echo "selected";}?>>- 1 day</option>
											</select>
										</td>
									</tr>
								</table>
							</td>
							<td rowspan="5" valign='top' align='right'>
								<fieldset>
									<legend class="title"><?php echo _("Graph Layout")?></legend>
									<table>
										<tr>
											<td>
												<input type="radio" NAME="min_call" value="1" <?php if ($min_call==1){ echo "checked";}?>>&nbsp;Minutes by hours												 
											</td>
										</tr>
										<tr>
											<td>
												<input type="radio" NAME="min_call" value="0" <?php if (($min_call==0) || !isset($min_call)){ echo "checked";}?>>&nbsp;Number of calls by hours
											</td>
										</tr>
									</table>
								</fieldset>
							</td>
						</tr>
						<tr>
							<td><?php echo _("&nbsp;&nbsp;&nbsp;&nbsp;Destination:")?></td>
							<td>
								<table width="100%">
									<tr>
										<td>&nbsp;&nbsp;<INPUT TYPE="text" NAME="dst" value="<?php echo $dst?>"></td>
										<td align="center"><input type="radio" NAME="dsttype" value="1" <?php if((!isset($dsttype))||($dsttype==1)){?>checked<?php }?>>&nbsp;Exact</td>
										<td align="center"><input type="radio" NAME="dsttype" value="2" <?php if($dsttype==2){?>checked<?php }?>>&nbsp;Begins with</td>
										<td align="center"><input type="radio" NAME="dsttype" value="3" <?php if($dsttype==3){?>checked<?php }?>>&nbsp;Contains</td>
										<td align="center"><input type="radio" NAME="dsttype" value="4" <?php if($dsttype==4){?>checked<?php }?>>&nbsp;Ends with</td>
									</tr>
								</table>
							</td>
							<td></td>
						</tr>
						<tr>
							<td><?php echo _("&nbsp;&nbsp;&nbsp;&nbsp;Source:")?></td>
							<td>
								<table width="100%">
									<tr>
										<td>&nbsp;&nbsp;<INPUT TYPE="text" NAME="src" value="<?php echo "$src";?>"></td>
										<td align="center"><input type="radio" NAME="sourcetype" value="1" <?php if((!isset($sourcetype))||($sourcetype==1)){?>checked<?php }?>>&nbsp;Exact</td>
										<td align="center"><input type="radio" NAME="sourcetype" value="2" <?php if($sourcetype==2){?>checked<?php }?>>&nbsp;Begins with</td>
										<td align="center"><input type="radio" NAME="sourcetype" value="3" <?php if($sourcetype==3){?>checked<?php }?>>&nbsp;Contains</td>
										<td align="center"><input type="radio" NAME="sourcetype" value="4" <?php if($sourcetype==4){?>checked<?php }?>>&nbsp;Ends with</td>
									</tr>
								</table>
							</td>
							<td></td>
						</tr>
						<tr>
							<td><?php echo _("&nbsp;&nbsp;&nbsp;&nbsp;CLI:")?></td>
							<td>
								<table width="100%">
									<tr>
										<td>&nbsp;&nbsp;<INPUT TYPE="text" NAME="clid" value="<?php echo $clid?>"></td>
										<td align="center"><input type="radio" NAME="clidtype" value="1" <?php if((!isset($clidtype))||($clidtype==1)){?>checked<?php }?>>&nbsp;Exact</td>
										<td align="center"><input type="radio" NAME="clidtype" value="2" <?php if($clidtype==2){?>checked<?php }?>>&nbsp;Begins with</td>
										<td align="center"><input type="radio" NAME="clidtype" value="3" <?php if($clidtype==3){?>checked<?php }?>>&nbsp;Contains</td>
										<td align="center"><input type="radio" NAME="clidtype" value="4" <?php if($clidtype==4){?>checked<?php }?>>&nbsp;Ends with</td>
									</tr>
								</table>							
							</td>
							<td></td>
						</tr>
						<tr>
							<td><?php echo _("&nbsp;&nbsp;&nbsp;&nbsp;Userfield:")?></td>
							<td>
								<table width="100%">
									<tr>
										<td>&nbsp;&nbsp;<INPUT TYPE="text" NAME="userfield" value="<?php echo "$userfield";?>"></td>
										<td align="center"><input type="radio" NAME="userfieldtype" value="1" <?php if((!isset($userfieldtype))||($userfieldtype==1)){?>checked<?php }?>>&nbsp;Exact</td>
										<td align="center"><input type="radio" NAME="userfieldtype" value="2" <?php if($userfieldtype==2){?>checked<?php }?>>&nbsp;Begins with</td>
										<td align="center"><input type="radio" NAME="userfieldtype" value="3" <?php if($userfieldtype==3){?>checked<?php }?>>&nbsp;Contains</td>
										<td align="center"><input type="radio" NAME="userfieldtype" value="4" <?php if($userfieldtype==4){?>checked<?php }?>>&nbsp;Ends with</td>
									</tr>
								</table>
							</td>
							<td></td>
						</tr>
						<tr>
							<td><?php echo _("&nbsp;&nbsp;&nbsp;&nbsp;Accountcode:")?></td>
							<td>
								<table width="100%">
									<tr>
										<td>&nbsp;&nbsp;<INPUT TYPE="text" NAME="accountcode" value="<?php echo $accountcode?>"></td>
										<td align="center"><input type="radio" NAME="accountcodetype" value="1" <?php if((!isset($accountcodetype))||($accountcodetype==1)){?>checked<?php }?>>&nbsp;Exact</td>
										<td align="center"><input type="radio" NAME="accountcodetype" value="2" <?php if($accountcodetype==2){?>checked<?php }?>>&nbsp;Begins with</td>
										<td align="center"><input type="radio" NAME="accountcodetype" value="3" <?php if($accountcodetype==3){?>checked<?php }?>>&nbsp;Contains</td>
										<td align="center"><input type="radio" NAME="accountcodetype" value="4" <?php if($accountcodetype==4){?>checked<?php }?>>&nbsp;Ends with</td>
									</tr>
								</table>
							</td>
							<td></td>
						</tr>
						<tr>
							<td><?php echo _("&nbsp;&nbsp;&nbsp;&nbsp;Channel:")?></td>
							<td>
								<table width="100%">
									<tr>
										<td>&nbsp;&nbsp;<INPUT TYPE="text" NAME="channel" value="<?php echo $channel?>"></td>	
										<td align="left"><INPUT TYPE="submit" value="Search"></td>			
									</tr>
								</table>
							</td>
							<td></td>
						</tr>						
					</table>
				</fieldset>
				</form>
			</td>
		</tr>
	</table>
</div>	

<!-- ** ** ** ** ** Part to display the GRAPHIC ** ** ** ** ** -->
<br><br>

<?php
if (is_array($list) && count($list)>0){

$table_graph=array();
$table_graph_hours=array();
$numm=0;
foreach ($list_total as $recordset){
		$numm++;
		$mydate= substr($recordset[0],0,10);
		$mydate_hours= substr($recordset[0],0,13);
		//echo "$mydate<br>";
		if (is_array($table_graph_hours[$mydate_hours])){
			$table_graph_hours[$mydate_hours][0]++;
			$table_graph_hours[$mydate_hours][1]=$table_graph_hours[$mydate_hours][1]+$recordset[1];
		}else{
			$table_graph_hours[$mydate_hours][0]=1;
			$table_graph_hours[$mydate_hours][1]=$recordset[1];
		}
		
		
		if (is_array($table_graph[$mydate])){
			$table_graph[$mydate][0]++;
			$table_graph[$mydate][1]=$table_graph[$mydate][1]+$recordset[1];
		}else{
			$table_graph[$mydate][0]=1;
			$table_graph[$mydate][1]=$recordset[1];
		}
		
}

$mmax=0;
$totalcall==0;
$totalminutes=0;
foreach ($table_graph as $tkey => $data){	
	if ($mmax < $data[1]) $mmax=$data[1];
	$totalcall+=$data[0];
	$totalminutes+=$data[1];
}

?>


<!-- TITLE GLOBAL -->
<center>
 <table border="0" cellspacing="0" cellpadding="0" width="80%">
 <tbody>
 	<tr>
 		<td align="left" height="30" style="padding-left: 0; margin-left: 0">
			<table cellspacing="0" cellpadding="1" bgcolor="white" width="50%">
			<tbody>
				<tr>
					<td style="padding-left: 0; margin-left: 0">
						<table cellspacing="0" cellpadding="0" width="100%">
						<tbody>
							<tr>
								<td bgcolor="#e5edf9" align="left">
									<font face="verdana" size="1" color="black">
										<b>TOTAL</b>
									</font>
								</td>
							</tr>
						</tbody>
						</table>
					</td>
				</tr>
			</tbody>
			</table>
		</td>
	</tr>
</tbody>
</table>
		  
<!-- FIN TITLE GLOBAL MINUTES //-->
<style>
	.table_data_row {
		height: 12px;
		margin-top: 0;
		margin-bottom: 0;
		padding-top: 2px;
		padding-bottom: 2px;
	}
	.graph {
		height: 11px;
		margin: 0;
		padding: 0;
	}
</style>
				
<table border="0" cellspacing="0" cellpadding="0" width="80%">
<tbody>
	<tr>
		<td bgcolor="white" style="padding: 0">			
			<table border="0" cellspacing="1" cellpadding="2" width="100%">
			<tbody>
				<tr>	
					<td align="center" bgcolor="#e5edf9" class="table_data_row"></td>
    				<td bgcolor="#dbf1ff" align="center" colspan="4" class="table_data_row">
    					<font face="verdana" size="1" color="black"><b>ASTERISK MINUTES</b></font>
    				</td>
    			</tr>
				<tr bgcolor="#e5edf9">
					<td align="right" bgcolor="#dbf1ff" class="table_data_row">
						<font face="verdana" size="1" color="black"><b>DATE</b></font>
					</td>
			        <td align="center" class="table_data_row">
			        	<font face="verdana" size="1" color="black"><b>DURATION</b></font>
			        </td>
					<td align="center" class="table_data_row">
						<font face="verdana" size="1" color="black"><b>GRAPHIC</b></font>
					</td>
					<td align="center" class="table_data_row">
						<font face="verdana" size="1" color="black"><b>CALLS</b></font>
					</td>
					<td align="center" class="table_data_row">
						<font face="verdana" size="1" color="black"><b><acronym title="Average Connection Time">ACT</acronym></b></font>
					</td>
                			
		<!-- LOOP -->
	<?php 		
		$i=0;
		// #ffffff #cccccc
		foreach ($table_graph as $tkey => $data) {	
			$i=($i+1)%2;		
			$tmc = $data[1]/$data[0];
		
			$tmc_60 = sprintf("%02d",intval($tmc/60)).":".sprintf("%02d",intval($tmc%60));		
		
			$minutes_60 = sprintf("%02d",intval($data[1]/60)).":".sprintf("%02d",intval($data[1]%60));
			$widthbar= intval(($data[1]/$mmax)*200); 
		
		//bgcolor="#336699" 
	?>
		</tr>
		<tr>
			<td align="right" class="sidenav table_data_row" nowrap="nowrap"><font face="verdana" size="1" color="black"><?php echo $tkey?></font></td>
			<td bgcolor="white" align="right" nowrap="nowrap" class="table_data_row"><font face="verdana" color="#000000" size="1"><?php echo $minutes_60?> </font></td>
        	<td bgcolor="white" align="left" nowrap="nowrap" class="table_data_row" width="<?php echo $widthbar+60?>">
        		<table cellspacing="0" cellpadding="0">
        		<tbody>
        			<tr>
        				<td bgcolor="#aaf5d0" class="graph"><img src="modules/cdr/categories/images/spacer.gif" width="<?php echo $widthbar?>" height="6"></td>
        			</tr>
        		</tbody>
        		</table>
        	</td>
        	<td bgcolor="white" align="right" nowrap="nowrap" class="table_data_row"><font face="verdana" color="#000000" size="1"><?php echo $data[0]?></font></td>
        	<td bgcolor="white" align="right" nowrap="nowrap" class="table_data_row"><font face="verdana" color="#000000" size="1"><?php echo $tmc_60?> </font></td>
     <?php }	 
	 	$total_tmc_60 = sprintf("%02d",intval(($totalminutes/$totalcall)/60)).":".sprintf("%02d",intval(($totalminutes/$totalcall)%60));				
		$total_minutes_60 = sprintf("%02d",intval($totalminutes/60)).":".sprintf("%02d",intval($totalminutes%60));
	 
	 ?>                   	
		</tr>
	<!-- FIN DETAIL -->		
	
				
				<!-- FIN BOUCLE -->

	<!-- TOTAL -->
	<tr bgcolor="#e5edf9">
		<td align="right" nowrap="nowrap" class="table_data_row"><font face="verdana" size="1" color="black"><b>TOTAL</b></font></td>
		<td align="center" nowrap="nowrap" colspan="2" class="table_data_row"><font face="verdana" size="1" color="black"><b><?php echo $total_minutes_60?> </b></font></td>
		<td align="center" nowrap="nowrap" class="table_data_row"><font face="verdana" size="1" color="black"><b><?php echo $totalcall?></b></font></td>
		<td align="center" nowrap="nowrap" class="table_data_row"><font face="verdana" size="1" color="black"><b><?php echo $total_tmc_60?></b></font></td>                        
	</tr>
	<!-- FIN TOTAL -->

	  </tbody></table>
	  <!-- Fin Tableau Global //-->

</td></tr></tbody></table>
	<br>
 	<IMG SRC="modules/cdr/categories/graph_stat.php?min_call=<?php echo $min_call?>&fromstatsday_sday=<?php echo $fromstatsday_sday?>&days_compare=<?php echo $days_compare?>&fromstatsmonth_sday=<?php echo $fromstatsmonth_sday?>&dsttype=<?php echo $dsttype?>&sourcetype=<?php echo $sourcetype?>&clidtype=<?php echo $clidtype?>&channel=<?php echo $channel?>&resulttype=<?php echo $resulttype?>&dst=<?php echo $dst?>&src=<?php echo $src?>&clid=<?php echo $clid?>&userfieldtype=<?php echo $userfieldtype?>&userfield=<?php echo $userfield?>&accountcodetype=<?php echo $accountcodetype?>&accountcode=<?php echo $accountcode?>" ALT="Stat Graph">

<?php }else{ ?>
	<center><h3>No calls in your selection.</h3></center>
<?php } ?>

</center>
