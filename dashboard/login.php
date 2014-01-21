<?PHP
/*
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */
?>
<?PHP
require_once "../functions.php";

global $DEBUG;
if ($DEBUG) {
    $starttime_main = microtime(true);
}
ini_set("session.cookie_httponly", 1);
session_start();
 
if ($_POST['submit'] == "submit") {
    if ($_POST['user'] == "" || $_POST['pass'] == "") {
        $emptyField = true;
    } else {
        $username  = @sanitize_paranoid_string($_POST['user']);
        $password  = $_POST['pass'];
        $ref       = @sanitize_paranoid_string($_POST['ref']);
        $userlogon = checkUser($username, $password);

        if ($userlogon[0]['result']) {
            $_SESSION['login']    = true;
            $_SESSION['userName'] = ucfirst(strtolower($userlogon[0]['username']));
            $_SESSION['userID']   = $userlogon[0]['user_id'];
            $_SESSION['email']    = $userlogon[0]['email'];

            if ($userlogon[0]['changePass']) {
               $_SESSION['forceChangePass'] = true;
            } 
            header("HTTP/1.1 302 Found");
            header("Location: index.php");
            header("Connection: close");
            header("Content-Type: text/html; charset=UTF-8");
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
        	   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		   <title>WAF-FLE</title>
		   <link rel="stylesheet" type="text/css" href="css/main.css" />  
		  </head>
		<body>
		<div id="header">
		<div id="logo"> <a style="padding: 0;" href="./index.php"><img src="images/logo.png" width="126" height="60" border="0" alt="ModSecurity Dashboard"></a></div>
		<div id="clear"> </div>
		</div>
		<div id="page-wrap">
 		<h2>You are now logged in!</h2><br>Please check if your browser support http redirect (status 302). If not, <a href="index.php"> click here to access WAF-FLE</a>
		<br>Have a nice WAF-FLing<br>
		<br><br><br><br><br><br><br><br><br>
		</div>
	<?PHP
		$hideFilter = true;
		require_once "../footer.php";
		exit();
        } else {
            $authFailed = true;
        }
    }
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>WAF-FLE</title>
	<link rel="stylesheet" type="text/css" href="css/main.css" />  
    <script>
        $(document).ready(function(){ 
            $('.auto-focus:first').focus();
        }); 
    </script>
</head>
<body>
    <div id="header">
    <div id="logo"> <a style="padding: 0;" href="./index.php"><img src="images/logo.png" width="126" height="60" border="0" alt="ModSecurity Dashboard"></a></div>
    <div id="clear"> </div>
    </div>

	<div id="page-wrap">
         <div id="login">
            Please enter your authentication bellow
<?PHP
if ($emptyField) {
    print "<br /><font color=\"red\"><b>Fill both: username and password</b></font>";
} elseif ($authFailed) {
    print "<br /><font color=\"red\"><b>Invalid Username or Password</b></font>";
} elseif ($userExpired) {
    print "<br /><font color=\"red\"><b>User expired</b></font>";
}
?>
            <form action="login.php" method="POST">
            <table>
               <tr>
               <th colspan="2">LOGIN</th>
               </tr>
               <tr>
               <td>Username: </td><td><input type="text" name="user" autocomplete="off" class="auto-focus" autofocus></td></tr>
               <tr>
               <td>Password: </td><td><input type="password" name="pass" autocomplete="off"></td></tr>
               <tr><td></td></tr>
               <tr>
               <td></td><td align="right">
               <input type="hidden" name="ref" value="<?PHP print $_SERVER['HTTP_REFERER']; ?>">
               <input type="submit" name="submit" value="submit"></td>
               </tr>            
            </table>
            </form>
		</div>	
	</div>
<?PHP
$hideFilter = true;
require_once "../footer.php";
?>
