<?php

require("include/class.phpmailer.php");

$caller=$_POST['caller'];
$called=$_POST['called'];
$number=$_POST['number'];
 
//if (isset($_FILES['record']))
{                   
 //if (file_exists($_FILES['record']['tmp_name']) && is_uploaded_file($_FILES['record']['tmp_name']) )
 
 $infile=$_FILES['record']['tmp_name'];
 // Allow the rigths for Apache if you want to keep the recording on the server
 $outfile="/var/www/vxml/messagebox/files/".$caller.".mp3";
 @unlink($outfile);
 system("ffmpeg -i $infile -ab 8k $outfile");
 
 if (1)
 {
  // Instantiate your new class
  $mail = new PHPMailer();
  
  //$mail->SMTPDebug =true;
  
  // Now you only need to add the necessary stuff
  $mail->IsSMTP();
  //$mail->SMTPAuth   = true;                  // enable SMTP authentication
  //$mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
  //$mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
  //$mail->Port       = 465;                   // set the SMTP port for the GMAIL server
  //$mail->Username   = "bsixto@gmail.com";  // GMAIL username
  //$mail->Password   = "xxxxxx";            // GMAIL password
  //$mail->AddReplyTo("bsixto@gmail.com", "First Last");

  //$mail->Host       = "smtp.free.fr";      // sets GMAIL as the SMTP server
  //$mail->Port       = 25;                   // set the SMTP port for the GMAIL server

  $mail->From = "marketing@i6net.com";
  $mail->FromName = "i6net";
  
  //$mail->AddAddress("isabel.desa@i6net.com", "Isabel");
  $mail->AddAddress("borja.sixto@i6net.com", "Borja");
  $mail->AddAddress("marketing@i6net.com", "i6net");

  //$mail->AddAddress("pascal@davi.fr", "Pascal");
  $mail->Subject = "Message : from ".$caller." to i6net";
  $mail->Body    = "A file is attached ("."mp3".")\n";
  
  $mail->AddAttachment($outfile, $caller.".mp3");

  if(!$mail->Send())
  {
   readfile("error.vxml");
   exit;
  }  
 }
}

readfile("goodbye.vxml");
?>