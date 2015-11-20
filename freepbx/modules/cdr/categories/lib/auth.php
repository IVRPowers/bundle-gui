<?php

//This is the uniq start
session_start();

//Login is checked on any request:
if ($_REQUEST['action'] === 'logOut' )
  logOut();
else
  logIn();

function logIn() {
  if (!isset($_SESSION['username'])) {
    $error_message='';
    $uniqid=uniqid();
    $token=md5(session_id().'-#-'.$uniqid); 
    if (empty($_REQUEST['username'])) {
      //New Form, username empty
      $_SESSION['LOGIN']=$uniqid;
      include('login.php');
      exit;
    }else{
      //Check token
      if($_REQUEST['token']==md5(session_id().'-#-'.$_SESSION['LOGIN']) ) {
        //Check Auth    
        $authres=authenticate($_REQUEST['username'],$_REQUEST['password']);
        if( is_array($authres) ){
          //Authenticate set session vars        
          $_SESSION['username'] = $_REQUEST['username'];
          $_SESSION['userinfos'] = $authres["userinfos"] ? unserialize($authres["userinfos"]) : array();
          $_SESSION['userinfos']["userid"]= $authres["id"];
          $_SESSION['admin'] = $authres["admin"];
          unset($_SESSION['LOGIN']);
          return;
        }else{
          //New form with error                
          $_SESSION['LOGIN']=$uniqid;
          $error_message=$authres;
          include('login.php');
          exit;
        }          
      }else{
        //New form with error timout
        $_SESSION['LOGIN']=$uniqid;
        $error_message="Login timout";
        include('login.php');
        exit;          
      }   
    }
  }
}

function authenticate($user, $password) {
  //Using a non verbose database Object
  $db = new DB_Sql();
  $db -> Database = DBNAME;
  $db -> Host = DBHOST;
  $db -> User = DBUSER;
  $db -> Password = DBPASS;
  $db -> Halt_On_Error = "no";
  //Check connection
  if (@$db->connect() === 0)
    return 'Unable to connect to database';
  //Verifiy user/password combo :
  $query="SELECT * from ".DB_TABLEUSERS." where username='$user' and password=SHA1('".$password.'+'.SALT."')";
  $db -> query($query);
  if($db -> num_rows() === 1){
    //Read User Infos and return
    $db -> next_record();
    return $db -> Record;      
  }
  return 'The username or password you entered is incorrect';
}

function logOut() {
	
	unset($_SESSION['username']);
	unset($_SESSION['userinfos']);
	unset($_SESSION['admin']);
    unset($_SESSION['LOGIN']);
  	session_write_close;
  	//session_destroy(); If wi destroy the session it will cause to leave freepbx also, instead we unset the variables used
  	header("location: index.php");
  	exit;
}

?>
