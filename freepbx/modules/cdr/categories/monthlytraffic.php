<?php
require_once(dirname(__FILE__) . "/lib/defines.php");


getpost_ifset(array('months_compare', 'current_page', 'fromstatsday_sday', 'fromstatsmonth_sday', 'days_compare', 'min_call', 'posted',  'dsttype', 'sourcetype', 'clidtype', 'channel', 'resulttype', 'stitle', 'atmenu', 'current_page', 'order', 'sens', 'dst', 'src', 'clid', 'userfieldtype', 'userfield', 'accountcodetype', 'accountcode'));

if (!isset ($current_page) || ($current_page == "")){	
		$current_page=0; 
	}


//$link = DbConnect();
$DBHandle  = DbConnect();


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


if ($_POST['posted']==1){
	
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




if (!isset($months_compare)){		
	$months_compare=2;
}



//if (isset($fromstatsday_sday) && isset($fromstatsmonth_sday)) $date_clause.=" AND calldate <= '$fromstatsmonth_sday-$fromstatsday_sday+23' AND calldate >= SUBDATE('$fromstatsmonth_sday-$fromstatsday_sday',INTERVAL $days_compare DAY)";

if (DB_TYPE == "postgres"){	
	if (isset($fromstatsday_sday) && isset($fromstatsmonth_sday)) $date_clause.=" AND calldate < date'$fromstatsmonth_sday-$fromstatsday_sday'+ INTERVAL '1 DAY' AND calldate >= date'$fromstatsmonth_sday-$fromstatsday_sday' - INTERVAL '$days_compare DAY'";
}else{
	if (isset($fromstatsday_sday) && isset($fromstatsmonth_sday)) $date_clause.=" AND calldate < ADDDATE('$fromstatsmonth_sday-$fromstatsday_sday',INTERVAL 1 DAY) AND calldate >= SUBDATE('$fromstatsmonth_sday-$fromstatsday_sday',INTERVAL $days_compare DAY)";  
}

if ($FG_DEBUG == 3) echo "<br>$date_clause<br>";
/*
Month
fromday today
frommonth tomonth (true)
fromstatsmonth tostatsmonth

fromstatsday_sday
fromstatsmonth_sday
tostatsday_sday
tostatsmonth_sday
*/


  
if (strpos($SQLcmd, 'WHERE') > 0) { 
	$FG_TABLE_CLAUSE = substr($SQLcmd,6).$date_clause; 
}elseif (strpos($date_clause, 'AND') > 0){
	$FG_TABLE_CLAUSE = substr($date_clause,5); 
}



if ($FG_DEBUG == 3) echo "<br>Clause : $FG_TABLE_CLAUSE";
//$nb_record = $instance_table -> Table_count ($FG_TABLE_CLAUSE);
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
				<form method="post" action="config.php?display=cdr&view=monthlytraffic" enctype="application/x-www-form-urlencoded">
				<INPUT TYPE="hidden" NAME="posted" value="1">
				<fieldset>
					<legend class="title"><?php echo _("Monthly Traffic")?></legend>
					<table style="width: 100%">
						<tr>
							<th style="width: 300px;"><?php echo _("Search Fields")?></th>
							<th><?php echo _("Search conditions")?></th>
						</tr>
						<tr>
							<td><?php echo _("&nbsp;&nbsp;&nbsp;&nbsp;Select the Month:")?></td>
							<td>
								<table width="100%">
									<tr>
										<td>
							  				<b>From : </b>
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
											<b>Laps of month to compare :</b> 
										 	<select name="months_compare">
												<option value="6" <?php if ($months_compare=="6"){ echo "selected";}?>>- 6 months</option>
												<option value="5" <?php if ($months_compare=="5"){ echo "selected";}?>>- 5 months</option>
												<option value="4" <?php if ($months_compare=="4"){ echo "selected";}?>>- 4 months</option>
												<option value="3" <?php if ($months_compare=="3"){ echo "selected";}?>>- 3 months</option>
												<option value="2" <?php if (($months_compare=="2")|| !isset($months_compare)){ echo "selected";}?>>- 2 months</option>
												<option value="1" <?php if ($months_compare=="1"){ echo "selected";}?>>- 1 month</option>
											</select>
										</td>
									</tr>
								</table>
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
						</tr>						
					</table>
				</fieldset>
				</form>
			</td>
		</tr>
	</table>
</div>	

<?php if (isset($_POST['posted'])) {?>
<br><br>
<center>
	<IMG SRC="modules/cdr/categories/graph_pie.php?min_call=<?php echo $min_call?>&fromstatsday_sday=<?php echo $fromstatsday_sday?>&months_compare=<?php echo $months_compare?>&fromstatsmonth_sday=<?php echo $fromstatsmonth_sday?>&dsttype=<?php echo $dsttype?>&sourcetype=<?php echo $sourcetype?>&clidtype=<?php echo $clidtype?>&channel=<?php echo $channel?>&resulttype=<?php echo $resulttype?>&dst=<?php echo $dst?>&src=<?php echo $src?>&clid=<?php echo $clid?>&userfieldtype=<?php echo $userfieldtype?>&userfield=<?php echo $userfield?>&accountcodetype=<?php echo $accountcodetype?>&accountcode=<?php echo $accountcode?>" ALT="Stat Graph">
</center>
<?php }?>


