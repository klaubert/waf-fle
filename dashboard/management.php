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
$pagId = 'management';
require_once "../session.php";
require_once "../header.php";

if (isset($_GET['sensorInfo'])) {  // Runtime sensor info enable/disable
	if ($_GET['sensorInfo'] == 0) {
		$_SESSION['getSensorInfo'] = false;
	} elseif ($_GET['sensorInfo'] == 1) {
		$_SESSION['getSensorInfo'] = true;
	}	
} elseif (isset($GetSensorInfo)) { // Config time sensor info enable/disable
	if (!isset($_SESSION['getSensorInfo'])) {
		if ($GetSensorInfo == false) { 
			$_SESSION['getSensorInfo'] = false;
		} else {
			$_SESSION['getSensorInfo'] = true;			
		}
	}
}
if (isset($_GET['s'])) {  // Sensors Tasks
   // Delete a sensor
   do if (isset($_GET['delete']) AND isset($_GET['sensor']) AND $sensorToDelete = @sanitize_int($_GET['sensor'], $min = '0')) {
      $_SESSION['delFilter']['src_sensor'] = $sensorToDelete;
   } elseif (isset($_POST['Edit']) AND $_POST['Edit'] == "Save" AND $_POST['Sensor'] != "") {  // Save a edited sensor
      $sensorToSave = @sanitize_int($_POST['Sensor'], $min = '0');
      $sensorName   = @sanitize_paranoid_string($_POST['Name']);

      if ($sensorName == "") {
         print "A name is needed!";
         break;
      }

        $sensorPass = @sanitize_paranoid_string($_POST['Pass'], $min = '5', $max = '20');
        if (!$sensorPass) {
            print "Password too short, or too long.";
            break;
        } elseif ( preg_match('/[^a-zA-Z0-9\.\-\_\@\s]/', $_POST['Pass'])) {
            print "Invalid caracter: use \"a-z A-Z 0-9 . - _ @ / ? = &\"";
            break;
        }
        $sensorIp = $_POST['IP'];
        if ($sensorIp == "") {
            $sensorIp = null;
        } elseif (preg_match('/^Any$/i', $sensorIp )) {
            $sensorIp = null;
        } elseif (preg_match('/^0.0.0.0$/', $sensorIp)) {
            $sensorIp = null;
        } elseif (validateIP($sensorIp)) {
            $sensorIp = $sensorIp;
        } else {
            print "Invalid IP Address";
            $sensorIp = "";
            break;
        }
        $typeList      = sensorsType();
        $sensorTypeTry = @sanitize_int($_POST['type'], $min = '0' );
        $typeCount     = count($typeList[0]);
        foreach ($typeList[0] as $key => $type) {
            if ($type['type'] == $sensorTypeTry) {
                $sensorType = $sensorTypeTry;
                break;
            }

            if ($typeCount == $key + 1) {
                print "Invalid sensor type!";
                break 2;
            }
        }
        if ($_POST['clientIPinHeader'] == "1") {
            $clientIpInHeader = true;
            $clientIpHeader = @sanitize_paranoid_string($_POST['clientIPHeader'], $min = '2', $max = '40');
        } else {
            $clientIpInHeader = false;
            $clientIpHeader = null;
        }

        $sensorDescription = @sanitize_paranoid_string($_POST['Description']);

        if ($sensorToSave) {
            $sensorSaveResult = saveSensor($sensorToSave, $sensorName, $sensorIp, $sensorDescription, $sensorType, $sensorPass, $clientIpInHeader, $clientIpHeader);
            if (!$sensorSaveResult) {
                print $sensorSaveResult;
            }
        }
    } elseif (isset($_GET['disable']) AND $sensorToDisable = @sanitize_int($_GET['sensor'], $min = '0')) {  // Disable a sensor
       disableEnableSensor($sensorToDisable, 'disable');       
    } elseif (isset($_GET['enable']) AND $sensorToEnable = @sanitize_int($_GET['sensor'], $min = '0')) {  // Enable a sensor
       disableEnableSensor($sensorToEnable, 'enable');       
    }while (false);

    // Save a new sensor
    do if (isset($_POST['New']) AND $_POST['New'] == "Save") {
        $sensorName = @sanitize_paranoid_string($_POST['Name'], $min = '5', $max = '30');
        if ($sensorName == "") {
            print "A name is needed!";
            break;
        }

        $sensorPass = @sanitize_paranoid_string($_POST['Pass'], $min = '5', $max = '20');
        if (!$sensorPass) {
            print "Password too short, or too long.";
            break;
        } elseif ( preg_match('/[^a-zA-Z0-9\.\-\_\@\s]/', $_POST['Pass'])) {
            print "Invalid caracter: use \"a-z A-Z 0-9 . - _ @ / ? = &\"";
            break;
        }

        $sensorIp = @sanitize_paranoid_string($_POST['IP']);
        if ($sensorIp == "") {
            $sensorIp = null;
        } elseif (preg_match('/^Any$/i', $sensorIp)) {
            $sensorIp = null;
        } elseif (preg_match('/^0.0.0.0$/i', $sensorIp)) {
            $sensorIp = null;
        } elseif (validateIP($sensorIp)) {
            $sensorIp = $sensorIp;
        } else {
            print "Invalid IP Address";
            break;
        }
        $typeList      = sensorsType();
        $sensorTypeTry = @sanitize_int($_POST['type'], $min = '0');
        $typeCount     = count($typeList[0]);

        foreach ($typeList[0] as $key => $type) {
            if ($type['type'] == $sensorTypeTry) {
                $sensorType = $sensorTypeTry;
                break;
            }
            if ($typeCount == $key + 1) {
                print "Invalid sensor type!";
                break 2;
            }
        }
                
        if ($_POST['clientIPinHeader'] == "1") {
            $clientIpInHeader = true;
            $clientIpHeader = @sanitize_paranoid_string($_POST['clientIPHeader'], $min = '2', $max = '40');
        } else {
            $clientIpInHeader = false;
            $clientIpHeader = null;
        }
        $sensorDescription = @sanitize_paranoid_string($_POST['Description']);

        $sensorToSave      = "new";
        if ($sensorToSave) {
            $sensorSaveResult = saveSensor($sensorToSave, $sensorName, $sensorIp, $sensorDescription, $sensorType, $sensorPass, $clientIpInHeader, $clientIpHeader);
            if (!$sensorSaveResult) {
                print $sensorSaveResult;
            }
        }
    } while (false);
} elseif (isset($_GET['u'])) {  // Users Tasks
    // Delete a user
    do if (isset($_GET['Delete']) AND isset($_GET['User']) AND $_GET['User'] > 1) {
        $userToDelete = @sanitize_int($_GET['User'], $min = '2');

        if ($userToDelete == 1 ) {
            print "User Admin cannot be deleted!";
            break;
        }

        if ($userToDelete) {
            $userDeleteResult = deleteUser($userToDelete);
            if (!$userDeleteResult) {
                print $userDeleteResult;
            }
        }
    } while (false);

    // Save a edited user
    do if (isset($_POST['Edit']) AND $_POST['Edit'] == "Save" AND $_POST['User'] != "") {
        $userToSave = @sanitize_int($_POST['User'], $min = '0');
        $userName   = @sanitize_paranoid_string($_POST['Name']);
        if ($userName == "") {
            print "A username is needed!";
            break;
        }

        if ($_POST['Pass'] != $_POST['Pass2']) {
            print "The passwords don't match!";
            break;
        }
         if (strlen($_POST['Pass']) == 0 AND strlen($_POST['Pass2'] == 0)) {
            $userPass = '';
         } else {
            $userPass = @sanitize_paranoid_string($_POST['Pass'], $min = '5', $max = '30');
           if (!$userPass) {
               print "Password too short, or too long.";
               break;
           } elseif ( preg_match('/[^a-zA-Z0-9\.\-\_\@\s]/', $_POST['Pass'])) {
               print "Invalid caracter: use \"a-z A-Z 0-9 . - _ @ / ? = &\"";
               break;
           }
        }
        $userEmail = @sanitize_paranoid_string($_POST['email']);
        if ($userEmail == "") {
            $userEmail = null;
        } elseif (!preg_match('/@/i', $userEmail)) {
            print "Invalid email address";
            break;
        }
        if ($userToSave) {

            $userSaveResult = userSave($userToSave, $userName, $userEmail, $userPass);
            if (!$userSaveResult) {
                print $userSaveResult;
            }
        }
    } while (false);

    // Save a new user
    do if (isset($_POST['New']) AND $_POST['New'] == "Save") {
        $userName = @sanitize_paranoid_string($_POST['Name'], $min = '5', $max = '30');
        if ($userName == "") {
            print "A username is needed!";
            break;
        }
        if ($_POST['Pass'] != $_POST['Pass2']) {
            print "The passwords don't match!";
            break;
        }
        $userPass = @sanitize_paranoid_string($_POST['Pass'], $min = '5', $max = '30');
        if (!$userPass) {
            print "Password too short, or too long.";
            break;
        } elseif ( preg_match('/[^a-zA-Z0-9\.\-\_\@\s]/', $_POST['Pass'])) {
            print "Invalid caracter: use \"a-z A-Z 0-9 . - _ @ / ? = &\"";
            break;
        }
        $userEmail = @sanitize_paranoid_string($_POST['email']);
        if ($userEmail == "") {
            $userEmail = null;
        } elseif (!preg_match('/@/i', $userEmail)) {
            print "Invalid email address";
            break;
        }
        $userToSave = "new";
        if ($userToSave) {
            $userSaveResult = userSave($userToSave, $userName, $userEmail, $userPass);
            if (!$userSaveResult) {
                print $userSaveResult;
            }
        }
    } while (false);
}


?>

<!-- <div id="page-wrap"> -->
<div id="page-wrap">
   <div id="main-content">

      <div id="management_menu">
         <p>

         <?PHP
         if (isset($_GET['s']) OR empty($_GET)) {         
            print "<div id=\"active\" class=\"mgtmenu\">";
         } else {
            print "<div class=\"mgtmenu\">";
         }
         print "<a href=\"management.php?s\">Sensors </a> <br />";
         print "</div>";
         
         if (isset($_GET['u'])) {         
            print "<div id=\"active\" class=\"mgtmenu\">";
         } else {
            print "<div class=\"mgtmenu\">";
         }
         print "<a href=\"management.php?u\">Users </a><br />";
         print "</div>";
         if (isset($_GET['i'])) {         
            print "<div id=\"active\" class=\"mgtmenu\">";
         } else {
            print "<div class=\"mgtmenu\">";
         }
         print "<a href=\"management.php?i\">Info </a><br />";
         print "</div>";
         
         ?>

         </p>
      </div>

   <div id="management_content_bg">   
   <div id="management_content">   

<?PHP
if (isset($_GET['u'])) {
    // Edit a user
    if (isset($_GET['Edit']) AND $_GET['User'] != "") {
        $userToEdit = @sanitize_int($_GET['User'], $min = '0');
        $user       = getUsers($userToEdit);

        print "<form method=\"POST\" action=\"management.php?u\">";
        print "<table>";
        print "<tr>";
        print "<td>ID</td><td>$userToEdit <input type=\"hidden\" name=\"User\" value=\"".$userToEdit."\"></td>";
        print "</tr><tr>";
        print "<td>Username</td><td><input type=\"text\" name=\"Name\" value=\"" . $user[0]['username'] . "\" /> (Min. 5 - Max. 30 characters)</td>";
        print "</tr><tr>";
        print "<td>e-mail</td><td><input type=\"text\" name=\"email\" value=\"" . $user[0]['email'] . "\"></td>";
        print "</tr>";
        print "<tr>&nbsp;</tr>";
        print "<tr><td>";
        print "<input type=\"submit\" name=\"Edit\" value=\"Save\">";
        print "</table>";
 } elseif (isset($_GET['New']) AND $_GET['New'] == 'User') {
         print "<form method=\"POST\" action=\"management.php?u\">";
         print "<table>";
         print "<tr>";
         print "<td>Username</td><td><input type=\"text\" name=\"Name\" value=\"\" /> (Min. 5 - Max. 30 characters)</td>";
         print "</tr><tr>";
         print "<td>Password</td><td><input type=\"password\" name=\"Pass\" value=\"\"> (Min. 5 - Max. 20 characters)</td>";
         print "</tr><tr>";
         print "<td>Password (confirmation)</td><td><input type=\"password\" name=\"Pass2\" value=\"\"> (Min. 5 - Max. 20 characters)</td>";
         print "</tr><tr>";
         print "<td>e-mail</td><td><input type=\"text\" name=\"email\" value=\"\"></td>";
         print "</tr>";
         print "<tr>&nbsp;</tr>";
         print "<tr><td>";
         print "<input type=\"submit\" name=\"New\" value=\"Save\">";
         print "</table>";
    } else {
        ?>
        <div id="events_header">
        <table width="100%" cellspacing="0" cellpadding="0" border="0">
        <tbody><tr>
           <td align="left"  class="textHeaderDark">
              </td>
              <td align="left" class="textHeaderDark">
               <div class="toolmenu">
						<ul>
							<li><a class="rigth" href="management.php?u&New=User">[+] Add New User</a></li>
						</ul>
					</div>              
              </td>
          </tr></tbody>
        </table>        
        </div>

        <?PHP

        print "<table id=\"ManagementTable\">";
        print "<tr>";
        print "<th width=\"20\" align=\"left\">ID</th>";
        print "<th width=\"150\" align=\"left\">User</th>";
        print "<th width=\"200\" align=\"left\">e-Mail</th>";
        print "<th width=\"200\" align=\"left\"></th>";
        print "</tr>";

        $users = getUsers();
        foreach ( $users as $user) {
            print "<tr>";
            print "<td>".$user['user_id']."</td>";
            print "<td>".$user['username']."</td>";
            print "<td>".$user['email']."</td>";
            print "<td>";
            print "</td>";
            print "<td>";
            
            print "<div class=\"toolmenu\">";
               print "<ul>";
               print "<li><a class=\"rigth\" href=\"management.php?u&Edit&User=".$user['user_id']."\">Edit</a></li>";
               print "<li><a class=\"rigth\" href=\"password.php?User=".$user['user_id']."\">Change Password</a></li>";
               if ($user['user_id'] != 1) {
                  print "<li><a class=\"left\" href=\"management.php?u&Delete&User=".$user['user_id']."\" onclick=\"return confirm('Are you sure you want to delete user ".$user['username']."?');\">Delete</a></li>";
               }
               print "</ul>";
               print "</div>";            
                                   
            print "</td>";
            print "</tr>";
        }
        print "</table>";
    }
} elseif (isset($_GET['i'])) {

   print "<table id=\"ManagementTable\">";
   print "<tr>";
      print "<td><span id=\"header_cap\">WAF-FLE Version:</span></td><td> $waffleVersion</td>";
   print "</tr>";
   
   print "<tr>";
      print "<td><span id=\"header_cap\">APC Cache extension:</span></td><td> ";
      if ($APC_ON) {
         print "Extension Loaded, enabled for PHP and turned On in WAF-FLE";
      } else {
         if (extension_loaded('apc')) {
            print "Extension loaded, ";
         } 
         if (ini_get('apc.enabled')) {
            print "Extension enabled, ";
         }
         print "Disabled in WAF-FLE";
      }
      print "</td>";
   print "</tr>";
   if ($APC_ON) {
   print "<tr>";
      print "<td><span id=\"header_cap\">APC Cache Timeout:</span></td><td> $CACHE_TIMEOUT seconds</td>";
   print "</tr>";
   }
   print "<tr>";
      print "<td><span id=\"header_cap\">PHP version:</span></td><td> ". phpversion() ."</td>";
   print "</tr>";   
   print "<tr>";
      print "<td><span id=\"header_cap\">PHP Zend Version:</span></td><td> ".zend_version()."</td>";
   print "</tr>";
   $dbInfo = getDbInfo();
   print "<tr>";
      print "<td><span id=\"header_cap\">MySQL Version:</span></td><td>".$dbInfo['version']."</td>";
   print "</tr>";
   print "<tr>";
      print "<td><span id=\"header_cap\">Database Name:</span></td><td>".$dbInfo['dbName']."</td>";
   print "</tr>";
   print "<tr>";
      print "<td><span id=\"header_cap\">Database Size:</span></td><td>".bytesConvert($dbInfo['size'])."</td>";
   print "</tr>";
   $sensorCount = getSensors();
   print "<tr>";
      print "<td><span id=\"header_cap\">Number of sensors:</span></td><td>".count($sensorCount[0])."</td>";
   print "</tr>";
   print "<tr>";
      print "<td><span id=\"header_cap\">Number of events on DB:</span></td><td>".number_format($dbInfo['total'])."</td>";
   print "</tr>";
   global $DEMO;
   if ($DEMO) {
       print "<tr>";
       print "<td><span id=\"header_cap\">Demo mode: </span></td><td> Enabled</td>";
       print "</tr>";                      
   }
   
   
   print "</table>";


} else {
    // Edit a sensor
    
    
    ?>
    
    <script>
        function toggleStatus(){
            if ($('#clientIPinHeader').is(':checked')) {
                $('#clientIPHeader').removeAttr('disabled','disabled');
            } else{
                $('#clientIPHeader').attr('disabled',"");            
            };
        };
    </script>
    
    <?PHP
    
    if (isset($_GET['edit']) AND $sensorToEdit = @sanitize_int($_GET['sensor'], $min = '0')) {
        $sensor       = getSensorName($sensorToEdit);
        $sensorType   = sensorsType();
        if ($sensor['IP'] == null) {
            $sensor['IP'] = "Any";
        }
        print "<form method=\"POST\" action=\"management.php?s\">";
        print "<table>";
        print "<tr>";
        print "<td width=\"100\">ID</td><td width=\"230\">$sensorToEdit <input type=\"hidden\" name=\"Sensor\" value=\"$sensorToEdit\"></td><td width=\"450\"></td>";
        print "</tr><tr>";
        print "<td>Sensor</td><td><input type=\"text\" name=\"Name\" value=\"".$sensor['name']."\" style=\"width: 195px\"></td><td> (Min. 5 - Max. 30 characters)</td>";
        print "</tr><tr>";
        print "<td>Password</td><td><input type=\"text\" name=\"Pass\" value=\"".$sensor['password']."\" style=\"width: 195px\"></td><td> (Min. 5 - Max. 20 characters)</td>";
        print "</tr><tr>";
        print "<td>IP</td><td><input type=\"text\" name=\"IP\" value=\"".$sensor['IP']."\" style=\"width: 195px\"></td><td> (empty|0.0.0.0 = Any, OR a host IP OR, a network range in CIDR)</td>";
        print "</tr><tr>";
        print "<td>Description</td><td><input type=\"text\" name=\"Description\" value=\"".$sensor['description']."\"></td><td></td>";
        print "</tr><tr>";
        if ($sensor['client_ip_via']) {
            print "<td>Use Client IP from header </td><td><input type=\"checkbox\" checked name=\"clientIPinHeader\" id=\"clientIPinHeader\" value=\"".$sensor['client_ip_via']."\" onchange=\"toggleStatus()\">
        <input type=\"text\" name=\"clientIPHeader\" id=\"clientIPHeader\" value=\"".$sensor['client_ip_header']."\" style=\"width: 180px\" ></td><td> (Check if this ModSecurity sensor is behind a reverse proxy that send original client IP in a header. Define the header in text box, ie. \"X-Forwarded-For\", \"X-Real-IP\")</td>";
        } else {
            print "<td>Use Client IP from header </td><td><input type=\"checkbox\" name=\"clientIPinHeader\" id=\"clientIPinHeader\" value=\"1\" onchange=\"toggleStatus()\">
        <input type=\"text\" name=\"clientIPHeader\" id=\"clientIPHeader\" value=\"\" style=\"width: 180px\" disabled \"></td><td> (Check if this ModSecurity sensor is behind a reverse proxy that send original client IP in a header. Define the header in text box, ie. \"X-Forwarded-For\", \"X-Real-IP\")</td>";
        }
        print "</tr><tr>";
        print "<td>Type</td><td>";
        print "<select name=\"type\">";
        foreach ($sensorType[0] as $stype) {
            if ($sensor['type'] == $stype['type']) {
                print "<option value=\"".$stype['type']."\" selected=\"selected\">".$stype['Description']."</option>";
            } else {
                print "<option value=\"".$stype['type']."\">".$stype['Description']."</option>";
            }
        }
        print "</td><td></td>";
        print "</tr>";
        print "<tr>&nbsp;</tr>";
        print "<tr><td>";
        print "<input type=\"submit\" name=\"Edit\" value=\"Save\">";
        print "</table>";
    } elseif (isset($_GET['New']) AND $_GET['New'] == 'Sensor') {
        $sensorType = sensorsType();
        print "<form method=\"POST\" action=\"management.php?s\">";
        print "<table witdh=\"200\">";
        print "<tr>";
        print "<td><b>Sensor</b></td><td><input type=\"text\" name=\"Name\" value=\"\" /> (Min. 5 - Max. 30 characters)</td>";
        print "</tr><tr>";
        print "<td><b>Password</b></td><td><input type=\"text\" name=\"Pass\" value=\"\"> (Min. 5 - Max. 20 characters)</td>";
        print "</tr><tr>";
        print "<td><b>IP</b></td><td><input type=\"text\" name=\"IP\" value=\"Any\"> (empty|0.0.0.0 = Any, OR a host IP, OR a network range in CIDR)</td>";
        print "</tr><tr>";
        print "<td>Use Client IP from header </td><td><input type=\"checkbox\" name=\"clientIPinHeader\" id=\"clientIPinHeader\" value=\"1\" onchange=\"toggleStatus()\">
        <input type=\"text\" name=\"clientIPHeader\" id=\"clientIPHeader\" value=\"\" style=\"width: 180px\" disabled \"></td><td> (Check if this ModSecurity sensor is behind a reverse proxy that send original client IP in a header. Define the header in text box, ie. \"X-Forwarded-For\", \"X-Real-IP\")</td>";
        print "</tr><tr>";
        print "<td><b>Description</b></td><td><input type=\"text\" name=\"Description\" value=\"\"></td>";
        print "</tr><tr>";
        print "<td><b>Type</b></td><td>";
        print "<select name=\"type\">";
        foreach ($sensorType[0] as $stype) {
            print "<option value=\"".$stype['type']."\">".$stype['Description']."</option>";
        }
        print "</td>";
        print "</tr>";
        print "<tr>&nbsp;</tr>";
        print "<tr><td>";
        print "<input type=\"submit\" name=\"New\" value=\"Save\">";
        print "</table>";
    } else {
        ?>
        <div id="events_header">
        <table width="100%" cellspacing="0" cellpadding="0" border="0">
        <tbody><tr>
           <td align="left"  class="textHeaderDark">
              </td>
              <td align="left" class="textHeaderDark">
               <div class="toolmenu">
						<ul>
							<li><a class="rigth" href="management.php?New=Sensor">[+] Add New Sensor</a></li>
						</ul>
					</div>              
              </td>
          </tr></tbody>
        </table>
        </div>

        <?PHP
        $sensors = getSensors();
        if (count($sensors[0]) == 0) {
            print "<br>Please, add a new sensor.";
        } else {
            print "<table id=\"ManagementTable\">";
            print "<tr>";
            print "<th width=\"350\" align=\"left\">Sensor Details</th>";
            if ($_SESSION['getSensorInfo'] == true) {
				print "<th width=\"450\" align=\"left\">Info/Stats (<a href=\"?sensorInfo=0\">disable load of this sensor info</a>)</th>";
            } else {
				print "<th width=\"450\" align=\"left\">Info/Stats (<a href=\"?sensorInfo=1\">enable load of this sensor info</a>)</th>";
			}
            print "<th width=\"200\" align=\"left\"></th>";
            print "</tr>";
            foreach ( $sensors[0] as $sensor) {
			   if ($_SESSION['getSensorInfo'] == true) {
                  $sensorInfo = getSensorInfo($sensor['sensor_id']);
			   }
               print "<tr>";
               print "<td width=\"350\" align=\"left\">";
                  print "<table id=\"sensorInfo\">";
                  print "<tr>";
                  print "<td><span id=\"header_cap\">Name:</span></td><td> ".$sensor['name']." (id: ".$sensor['sensor_id'].")</td>";
                  print "</tr><tr>";
                  if ($sensor['IP'] == "") {
                      print "<td><span id=\"header_cap\">IP: </span></td><td> Any </td>";
                  } else {
                     print "<td><span id=\"header_cap\">IP: </span></td><td>".$sensor['IP']." </td>";               
                  }
                  print "</tr><tr>";
                  print "<td><span id=\"header_cap\">Description: </span></td><td> ".$sensor['description']." </td>";
                  print "</tr><tr>";
                  print "<td><span id=\"header_cap\">Type: </span> </td><td>".$sensor['type_description']." </td>";
                  print "</tr><tr>";
                  print "<td><span id=\"header_cap\">Status: </span> </td><td>".$sensor['status']." </td>";
                  print "</tr></table>";
               print "</td>";
               print "<td width=\"450\" align=\"left\">";
                  print "<table id=\"sensorInfo\">";
                  print "<tr>";
                  print "<td><span id=\"header_cap\">Event's total:</span></td><td>";
                  if ($_SESSION['getSensorInfo'] == true) {
					  print number_format($sensorInfo['sensorEvents']);
				  }
				  print " </td>";
                  print "</tr><tr>";
                  print "<td><span id=\"header_cap\">Last event in:</span></td><td>".$sensorInfo['a_date']." </td>";
                  print "</tr><tr>";
                  print "<td><span id=\"header_cap\">Producer: </span></td><td>".$sensorInfo['h_producer']." </td>";
                  print "</tr><tr>";
                  print "<td><span id=\"header_cap\">Rule Set: </span></td><td>".$sensorInfo['h_producer_ruleset']." </td>";
                  print "</tr><tr>";
                  print "<td><span id=\"header_cap\">Server: </span></td><td>".$sensorInfo['h_server']." </td>";
                  print "</tr>";
                  print "</table>";
               
               print "</td>";
                
               print "<td width=\"200\">";
 
               print "<div class=\"toolmenu\">";
               print "<ul>";
               print "<li><a class=\"rigth\" href=\"management.php?s&edit&sensor=".$sensor['sensor_id']."\">Edit</a></li>";
               if ($sensor['status'] == 'Enabled') {
                  print "<li><a class=\"rigth\" onclick=\"return confirm('Are you sure you want to disable sensor ".$sensor['name']."?');\"  href=\"management.php?s&disable&sensor=".$sensor['sensor_id']."\">Disable</a></li>";
               } elseif ($sensor['status'] == 'Disabled') {
                  print "<li><a class=\"rigth\" onclick=\"return confirm('Are you sure you want to enable sensor ".$sensor['name']."?');\"  href=\"management.php?s&enable&sensor=".$sensor['sensor_id']."\">Enable</a></li>";
               }
               print "<li><a class=\"left\" href=\"management.php?s&delete&sensor=".$sensor['sensor_id']."\">Delete</a></li>";
               print "</ul>";
               print "<ul>";
               
               print "<li><br><a class=\"left\" href=\"wizardfeeder.php?sensor=".$sensor['sensor_id']."\">Event Feeder Wizard</a></li>";
               print "</ul>";
               
               print "</div>";

               print "</td>";
               print "</tr>";
            }
        }
        print "</table>";
    }
}
?>
               </p>
            </div>
         </div>
      </div>
   </div>

<?PHP

require_once "../footer.php";
?>
