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
$pagId = 'password';
require_once "../session.php";
require_once "../header.php";

// Change the password
do if (isset($_POST['Pass']) AND $_POST['Pass'] == "Change Password" AND $_POST['UserID'] > 0) {
   $userToSave = @sanitize_int($_POST['UserID'], $min = '0');
   if ($_POST['newPass'] != $_POST['newPass2']) {
      $changeResult[] = "The passwords don't match!<br>Please try again.";
      break;
   }
   $userPass = @sanitize_paranoid_string($_POST['newPass'], $min = '5', $max = '30');
   if (!$userPass) {
      $changeResult[] = "Password too short, or too long.";
      break;
   } elseif ( preg_match('/[^a-zA-Z0-9\.\-\_\@\s]/', $_POST["Pass"])) {
      $changeResult[] = "Invalid caracter: use \"a-z A-Z 0-9 . - _ @ / ? = &\", case sensitive.";
      break;
   }

   if ($userToSave) {
      $userSaveResult = userSave($userToSave, '', '', $userPass);
      if (!$userSaveResult) {
          $changeResult[] = "An error occour with password change, please check it and try again";
      } else {
         $changeResult[] = "Password change sucessfully!";
         if (isset($_SESSION['forceChangePass']) AND $_SESSION['forceChangePass'] AND $userToSave == "1") {
            unset($_SESSION['forceChangePass']);
         }
      }
  }
} while (false);

?>
<!-- <div id="page-wrap"> -->
<div id="page-wrap">
   <div id="main-content">
   <div id="management_content">   

   <?PHP
   if (isset($_GET['User']) AND $_GET['User'] >= 0) {
      $userToEdit = @sanitize_int($_GET['User'], $min = '0');
      $user       = getUsers($userToEdit);
      if (isset($changeResult)) {
         foreach ($changeResult as $changeMsg) {
            print "<br />";
            print "<center><h2>$changeMsg</h2></center>";
         }
         
      } else {
         if (!$user) {
            print "<h2>User not exist</h2>";
         } else {
            print "<h2>Change User Password</h2><br />";
            if ($_SESSION['forceChangePass']) {
               print "<h3>Please, change the admin default password to continue...</h3><br />";
            }
            print "<form method=\"POST\" action=\"password.php?User=$userToEdit\">";
            print "<table>";
            print "<tr>";
            print "<td>ID</td><td>$userToEdit <input type=\"hidden\" name=\"UserID\" value=\"".$userToEdit."\"></td>";
            print "</tr><tr>";
            print "<td>Username: </td><td>" . $user[0]['username'] . "</td>";
            print "</tr><tr>";
            print "<td>Password</td><td><input type=\"password\" name=\"newPass\" value=\"\"> (Min. 5 - Max. 20 characters)</td>";
            print "</tr><tr>";
            print "<td>Password (confirmation)</td><td><input type=\"password\" name=\"newPass2\" value=\"\"> (Min. 5 - Max. 20 characters)</td>";
            print "</tr>";
            print "<tr><td>";
            print "<input type=\"submit\" name=\"Pass\" value=\"Change Password\">";
            print "</table>";
         }
      } 
   }

   ?>
   
   </div>
   </div>
</div>

<?PHP
   require_once "../footer.php";
?>
