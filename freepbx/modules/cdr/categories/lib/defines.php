<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
define ("DB_TYPE", "mysql"); // mysql or postgres
require_once(dirname(__FILE__)."/DB-modules/phplib_".DB_TYPE.".php");

//CVS Date
$update ="$Date: 2013-05-28 09:37:16 $";

//Mysql settings
define ("DBHOST", "localhost");
//define ("DBUSER", "cdr");
//define ("DBPASS", "password");
//define ("DBNAME", "cdr");

//We point to the database asteriskcdrdb that is created by the module CDR of FreePBX 
define ("DBUSER", "asteriskuser");
define ("DBPASS", "amp109");
define ("DBNAME", "asteriskcdrdb");


//Tables Names :
define ("DB_TABLENAME", "cdr");
define ("DB_TABLEUSERS","users");

//default VAR_CHAR search form
$_VCHAR_SEARCH=array(
  "dst"         => "DESTINATION",
  "src"         => "SOURCE",
  "clid"        => "CLI",
  "userfield"   => "USERFIELD",
  "accountcode" => "ACCOUNTCODE",
);



//admin =  array(Fecha, Hora, Origen, Destino, Duración, Código Cliente, Código Empleado, Audio, Lastapp, Acción)
//Default
$_FG_TABLE_COL=array(
        array ("Date", "calldate", "15%", "center", "SORT", "19"),
        array ("Channel", "channel", "15%", "center", "", "30", "", "", "", "", "", "display_acronym"),
        array ("Source", "src", "5%", "center", "", "30"),
        array ("Clid", "clid", "5%", "center", "", "30"),
        array ("Lastapp", "lastapp", "8%", "center", "", "30"),
        array ("Lastdata", "lastdata", "5%", "center", "", "30"),
        array ("Destino", "dst", "12%", "center", "SORT", "30"),
        array ("APP", "dst", "1%", "center", "", "30","list", $appli_list),
        array ("Accion", "disposition", "9%", "center", "SORT", "30"),
      	array ("Dur.", "duration", "10%", "center", "SORT", "30", "", "", "", "", "", "$minute_function"),
      //Uncomment line above for record column
      //array ("Record", "uniqueid", "4%", "center", "", "100", "", "", "", "", "", "display_play"),
        array ("UserField", "userfield", "4%", "center", "", "100", "", "", "", "", "", ""),
        array ("Accountcode", "accountcode", "8%", "center", "", "20"),
);


 
// Regarding to the dst you can setup an application name
// Make more sense to have a text that just a number
// especially if you have a lot of extension in your dialplan
$appli_list['1711']=array("myappli_01");
$appli_list['1712']=array("myappli_02");
$appli_list['1713']=array("myappli_03");

require_once(dirname(__FILE__)."/Class.Table.php");
//require_once(dirname(__FILE__)."/auth.php");


####################################
# Editable Functions :
####################################

function DbConnect()
  {	
	$DBHandle = new DB_Sql();
	$DBHandle -> Database = DBNAME;
	$DBHandle -> Host = DBHOST;
	$DBHandle -> User = DBUSER;
	$DBHandle -> Password = DBPASS;
	$DBHandle -> connect ();
	return $DBHandle;
}

//Set REQUEST value globals :
function getpost_ifset($test_vars)
{
	if (!is_array($test_vars)) {
		$test_vars = array($test_vars);
	}
	foreach($test_vars as $test_var) {
		if (isset($_REQUEST[$test_var])) {
			global $$test_var;
			$$test_var = $_REQUEST[$test_var];
  	}
  }
}


function display_minute($sessiontime){
		global $resulttype;
		if ((!isset($resulttype)) || ($resulttype=="min")){  
				$minutes = sprintf("%02d",intval($sessiontime/60)).":".sprintf("%02d",intval($sessiontime%60));
		}else{
				$minutes = $sessiontime;
		}
		echo $minutes;
}

function display_2dec($var){		
		echo number_format($var,2);
}

function display_2bill($var){	
		$var=$var/100;
		echo '$ '.number_format($var,2);
}

function remove_prefix($phonenumber){
		
		if (substr($phonenumber,0,3) == "011"){
					echo substr($phonenumber,3);
					return 1;
		}
		echo $phonenumber;
}


function display_acronym($field){		
		echo '<acronym title="'.$field.'">'.substr($field,0,35).'...</acronym>';		
}

function display_play($uniqueid){
	$found=glob("/var/spool/asterisk/monitor/${uniqueid}-*.wav");
	if(count($found)>0){

		echo '<a  href="javascript:set(\'play.php?file='.urlencode(basename($found[0])).'\');" ><img src="images/play.png" border="0" title="'.basename($field).'"></img></a>&nbsp;';
		echo '<a  href="play.php?file='.urlencode(basename($found[0])).'"><img src="images/down.png" border="0" title="'.basename($field).'"></img></a>';
	}
}


?>
