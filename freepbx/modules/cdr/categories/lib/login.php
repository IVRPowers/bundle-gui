<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title></title>
		<style type="text/css" media="screen">
			@import url("css/layout.css");
			@import url("css/content.css");
			@import url("css/docbook.css");
		</style>
  </head>
<body>
  

<style type="text/css">
body {
margin: 0;
padding: 0;
}

#global {
position:absolute;
left: 50%;
top: 50%;
width: 400px;
height: 200px;
margin-top: -100px; /* moitié de la hauteur */
margin-left: -200px; /* moitié de la largeur */
border: 1px solid grey;
padding: 20px;
background: #fff;
}
</style>
<div id="global">
<div align="center">
<h1>Asterisk CDR</h1>
<form action="index.php" method="post">
<input type="hidden" name="token" value="<?php echo $token?>" id="token" />  <table border="0" width="300">
     <tfoot>
      <tr>
        <td colspan="2" align="right">

          <input type="submit" value="  Login  " />
        </td>
      </tr>
      <tr>
        <td colspan="2"><div id="erromsg">
          <?php echo $error_message?>
          &nbsp;</div>
        </td>
      </tr>

    </tfoot>
    <tbody>
      <tr>
        <th>
          <label for="signin_username">Username</label>        </th>
        <td>
          <input type="text" name="username" value="" id="signin_username" />        </td>

      </tr>
       <tr>
        <th>
          <label for="signin_password">Password</label>        </th> 
        <td>
          <input type="password" name="password" value="" id="signin_password" />        </td>
      </tr>
  </tbody>

  </table>
</form>
</div>
</div>
  </body>
</html>

