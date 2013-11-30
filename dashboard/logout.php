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
session_start();

if ($_POST['submit'] == "go") {
    logoff();
} elseif ($_POST['submit'] == "continue") {
    header("HTTP/1.1 302 Found"); 
    if ($_POST['ref'] != "") {
        header("Location: ".sanitize_paranoid_path($_POST['ref']));
    } else {
        header("Location: index.php");
    }
    header("Connection: close");
    header("Content-Type: text/html; charset=UTF-8");
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>WAF-FLE</title>
	<link rel="stylesheet" type="text/css" href="css/main.css" />  
   <script type="text/javascript" src="js/jquery.js"></script>          
   <script type="text/javascript" src="js/effect.js"></script>
</head>
<body>
    <div id="header">
            <div id="logo"> <a style="padding: 0;" href="./index.php"><img src="images/logo.png" width="126" height="60" border="0" alt="ModSecurity Dashboard"></a></div>
            <div id="clear"> </div>
    </div>

	<div id="page-wrap">
         <div id="login">
            <b>Are you sure that want to logout?</b><br />&nbsp;
            <form action="logout.php" method="POST">
            <table>
               <tr>
               <td align="left">
                  <BUTTON NAME=submit VALUE="continue">No, go back!</BUTTON>
                  &nbsp;&nbsp;&nbsp;&nbsp;
               </td>
               <td align="right">
                  <input type="hidden" name="ref" value="<?PHP print $_SERVER['HTTP_REFERER']; ?>">
                  <BUTTON NAME=submit VALUE="go">Yes, logout!</BUTTON>
               </td>
               </tr>            
            </table>
            </form>
		</div>	
	</div>
<?PHP
$hideFilter = true;
require_once "../footer.php";
?>
