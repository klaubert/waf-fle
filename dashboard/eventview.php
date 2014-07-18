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
$jsNotes = true;
require_once("../functions.php");
global $DEBUG;
if ($DEBUG) {
   $starttime_main = microtime(true);
}
require_once("../session.php");
require_once("../filterprocessing.php");
// Preserve the event
if (isset($_POST["action"]) AND $_POST["action"] == "Preserve" AND $_POST["event"][0] != "") {
  $eventPreserve = @sanitize_int($_POST["event"][0], $min='0' );
  if ($eventPreserve) {
     $preserveResult = preserveEvent($eventPreserve, 'Preserve');
     if (!$preserveResult) {
        print $preserveResult;
     }
  }
}

// UnPreserve the event
if (isset($_POST["action"]) AND $_POST["action"] == "UnPreserve" AND $_POST["event"][0] != "") {
  $eventPreserve = @sanitize_int($_POST["event"][0], $min='0' );
  if ($eventPreserve) {
     $preserveResult = preserveEvent($eventPreserve, 'NotPreserve');
     if (!$preserveResult) {
        print $preserveResult;
     }
  }
}
// Mark event as NOT false positive
if (isset($_POST["action"]) AND $_POST["action"] == "Unmark" AND $_POST["event"][0] != "") {
    $eventFalsePositive = @sanitize_int($_POST["event"][0], $min='0' );
    if ($eventFalsePositive) {
        $falsePositiveResult = falsePositiveEvent($eventFalsePositive, 'notfp');
        if (!$falsePositiveResult) {
            print $falsePositiveResult;
        }
    }
}

// Mark event as false positive
if (isset($_POST["action"]) AND $_POST["action"] == "Mark" AND $_POST["event"][0] != "") {
    $eventFalsePositive = @sanitize_int($_POST["event"][0], $min='0' );
    if ($eventFalsePositive) {
        $falsePositiveResult = falsePositiveEvent($eventFalsePositive, 'fp');
        if (!$falsePositiveResult) {
            print $falsePositiveResult;
        }
    }
}

if (isset($_GET["e"])) {
   $geteventid = @sanitize_int($_GET["e"], $min='1' );
} else {
   $geteventid = 1;
}
$event_detail  = getEvent($geteventid);

if (!$event_detail) {
   header("HTTP/1.1 302 Found");
   header("Location: events.php");
   header("Connection: close");
   header("Content-Type: text/html; charset=UTF-8");
   exit();
}

$event_navigation = getEventPag($geteventid);

require_once("../header.php");

?>
<div id="page-wrap">
   <div id="main-content">        
      
   <div id="events_header">
    <?PHP
        print "<form id=\"eventsAction\" name=\"eventsAction\" action=\"eventview.php?e=$geteventid\" method=\"post\">";
        print "<input type=\"hidden\" name=\"event[]\" value=\"$geteventid\">";
        
    ?>
      <input type="hidden" name="action" value="1">
      <table width="100%" cellspacing="0" cellpadding="3" border="0">
      <tbody><tr bgcolor="#6d88ad">
         <td align="left"  class="textHeaderDark">
         <?PHP 
         print "<div class=\"toolmenu\">";
            print "<ul>";
               if ($event_detail['preserve']){
                  print "<li><a class=\"left\">Delete</a></li>";
                  print "<li><a class=\"left\" href=\"#\" onClick=\"if(confirm('Confirm event preservation?'))
					submitformUnPreserve(); else unselectAll(this);\" value=\"UnPreserve\">UnPreserve</a></li>";
               } else {
                  print "<li><a class=\"left\" href=\"#\" onClick=\"if(confirm('Confirm event deletion?'))
					submitformDel(); else unselectAll(this);\">Delete</a></li>";                  
                  print "<li><a class=\"left\" href=\"#\" onClick=\"if(confirm('Confirm event preservation?'))
					submitformPreserve(); else unselectAll(this);\" value=\"Preserve\">Preserve</a></li>";                       
               }               
                              
               if ($event_detail['false_positive']) {
                    print "<li><a class=\"left\" href=\"#\" onClick=\"if(confirm('Confirm event as Not a False Positive?'))
					submitformUnMarkFP(); else unselectAll(this);\" value=\"Unmark\">Unmark as False Positive</a></li>";
               } else {
                    print "<li><a class=\"left\" href=\"#\" onClick=\"if(confirm('Confirm event as False Positive?'))
					submitformMarkFP(); else unselectAll(this);\" value=\"Mark\">Mark as False Positive</a></li>";   
               }                    
               
            print "</ul>";
         print "</div>";           
         ?>
         <br />
         </td>
         <td align="center" class="textHeaderDark">
         </td>
         <td align="right" class="textHeaderDark">
            <?PHP 
               if (isset($event_navigation["prev_event_id"])) {
                  print "<strong><a href=\"eventview.php?e=".($event_navigation["prev_event_id"])."\" class=\"linkOverDark\" id=\"linkOverDark\">&lt; Previous</a> </strong>&nbsp;";
               } else {
                   print "&lt; Previous&nbsp;";
               }
               print "<strong>".($event_navigation["current_event_count"])."&nbsp;of&nbsp;".($event_navigation["total_event_count"])."&nbsp;";
               if (isset($event_navigation["next_event_id"])) {
                  print "&nbsp;<strong> <a class=\"linkOverDark\" id=\"linkOverDark\" href=\"eventview.php?e=".($event_navigation["next_event_id"])."\">Next &gt;</a>&nbsp;</strong>";
               } else {
                   print "&nbsp;Next &gt;&nbsp;";
               }
            ?>
         </td>
      </tr>
   </tbody></table></form>
</div>      
      
      
   <table border="0" cellpadding="0" cellspacing="0" width="100%">
      <tr valign="top">
         <td>
         
	<table width="100%" cellpadding="0" cellspacing="0" border="0">
   <tr valign="top">
	<td>
	
<?PHP
   if (is_array($event_detail["h_message_ruleId"])) { 
?>	
	<h2>Rules Match</h2>

	<table cellpadding="0" cellspacing="0" border="0">
   <tbody>
	<tr>

	<th width="50" class="EventsTh">ID</th>
	<th width="90" class="EventsTh">Severity</th>
	<th width="660" class="EventsTh">Message</th>
	</tr>
   
   <?PHP
      foreach($event_detail["h_message_ruleId"] as $key => $value) {
         print "<tr valign=\"top\" class=\"EventsTr\">";
         print "<td class=\"EventsTr\"> <a href=\"events.php?ruleid=".$event_detail['h_message_ruleId'][$key]."\" title=\"Filter by this Event ID\">".$event_detail['h_message_ruleId'][$key]."</a></td>";
         $severitytext = $severity[$event_detail['h_message_ruleSeverity'][$key]];
         print "<td class=\"EventsTr\"><center><a href=\"events.php?severity=".$event_detail['h_message_ruleSeverity'][$key]."\" title=\"Filter by this Event ID\"><img src=\"images/".$event_detail['h_message_ruleSeverity'][$key].".png\" style=\"border-style: none\" title=\"$severitytext\" alt=\"$severitytext\" /></a> </center></td>";
         print "<td class=\"EventsTr\">";
         print "<div class=\"wordwrap eventMessage\">";
         if (preg_match('/^Access denied/i', $event_detail['h_message_action'][$key])) {
            print "<b>";
         }
         if ( $event_detail['h_message_ruleMsg'][$key] != '') {
            print "<b>Rule Message:</b> ".headerprintnobr($event_detail['h_message_ruleMsg'][$key]) . "<br />";
         }
         if ( $event_detail['h_message_pattern'][$key] != '') {
            print "<b>Event:</b> ".headerprintnobr($event_detail['h_message_pattern'][$key]) ." <br />";
         }
         if ( $event_detail['h_message_ruleData'][$key] != '') {
            print "<b>Data:</b> ".headerprintnobr($event_detail['h_message_ruleData'][$key]) ." <br />";
         }
         if ( $event_detail['h_message_tag'][$key] != '') {
            print "<div id=\"tag\">";
            if (count($event_detail['h_message_tag'][$key]) > 2) {
               print "<b>Tags:</b> ";
            } elseif ( count($event_detail['h_message_tag'][$key]) == 2) {
               print "<b>Tag:</b> ";
            }
            $tag_count = 1;

            foreach($event_detail["h_message_tag"][$key] as $tagkey => $tagvalue) {
               if ($tagvalue['tag_url'] != "" OR $tagvalue['tag_text'] != "") {
                    if ($tagvalue['tag_url'] != "") {
                        print "<a href=\"events.php?tag=".headerprintnobr($tagvalue['tag_id'])."\" title=\"Filter by tag: ".headerprintnobr($tagvalue['tag_name'])." \">".headerprintnobr($tagvalue['tag_name'])." </a><a href=\"".headerprintnobr($tagvalue['tag_url'])."\" target=\"_blank\"><img src=\"images/information-small.png\" class=\"tagTip\" title=\" <b><u>".headerprintnobr($tagvalue['tag_title'])."</u></b><br>".headerprintnobr($tagvalue['tag_text'])."<br /><b>Click for more info</b>\"></a>";
                    } else {
                        print "<a href=\"events.php?tag=".headerprintnobr($tagvalue['tag_id'])."\" title=\"Filter by tag: ".headerprintnobr($tagvalue['tag_name'])."\">".headerprintnobr($tagvalue['tag_title'])."</a><img src=\"images/information-small.png\" class=\"tagTip\" title=\"<b><u>".headerprintnobr($tagvalue['tag_title'])."</u></b><br>".headerprintnobr($tagvalue['tag_text'])."\"/>";
                    }
               } else {
                   print "<a href=\"events.php?tag=".headerprintnobr($tagvalue['tag_id'])."\" title=\"Filter by tag: ".headerprintnobr($tagvalue['tag_name'])."\">".headerprintnobr($tagvalue['tag_name'])."</a>";
               }               
               
               if ($tag_count <= count($event_detail['h_message_tag'][$key]) - 1 && count($event_detail['h_message_tag'][$key]) > 2) {
                  print " | ";
               }
               $tag_count++;
            }
            print "</div>";
         }
         if (preg_match('/^Access denied/i', $event_detail['h_message_action'][$key])) {
            print "</b>";
         }         
         print "</div>";
         print "</td>";
         print "</tr>"; 
      }
   }
   print "</tbody>";
	print "</table>";
?>
	<br />
	<h2>Request Details</h2>

	<div>
	<table cellpadding="0" cellspacing="0">
	<tr><td valign="top"><div class="verticaltext">H E A D E R</div></td>
   <td>
   
   <div class="wordwrap eventHeader">
   <?PHP 
       print "<a href=\"events.php?method=".headerprintnobr($event_detail['b_method'])."\" title=\"Filter by this Method\">".headerprintnobr($event_detail['b_method'])."</a> <a href=\"events.php?path=".headerprintnobr($event_detail['b_path'])."\" title=\"Click to filter for this Web Hostname\">".headerprintnobr($event_detail['b_path']) ."</a>";
       if (isset($event_detail['b_path_parameter'])) { 
          print "?"; 
       }
       print headerprintnobr($event_detail['b_path_parameter']);

       print " " . headerprintnobr($event_detail['b_protocol']);
   ?>  
   </div>
   
   <div class="wordwrap eventHeader">
   <?PHP
   $barr = explode("\n", trim($event_detail['b_full']));
   
   foreach($barr as $b_line) {
      if (preg_match('/^Host\:/i', $b_line)) {
         print "<span id=\"header_cap\">Host: </span>";
         print "<a href=\"events.php?web_Hostname=".headerprintnobr($event_detail['b_host'])."\" title=\"Filter by this Web Hostname\">" . headerprintnobr(getWebHostName($event_detail['b_host'])) . "</a><br />"; 
      } elseif (preg_match('/^User-Agent\:/i', $b_line)) {
         print "<span id=\"header_cap\">User-Agent: </span>";
         print headerprintnobr($event_detail['b_user_agent']) . "<br />";
      } elseif (preg_match('/^Referer:\:/i', $b_line)) {
         print "<span id=\"header_cap\">Referer: </span>";
         print headerprintnobr($event_detail['b_referer']) . "<br />";
      } elseif (preg_match('/^([\w-]+)\:\s(.*)/i', $b_line, $bmatch)) {
         print "<span id=\"header_cap\">$bmatch[1]: </span>";
         print headerprintnobr($bmatch[2]) . "<br />";
      } elseif (preg_match('/^\-\-[a-f0-9]+\-B\-\-$/i', $b_line)) {
         continue;
      } elseif (preg_match('/(GET|POST|HEAD|PUT|DELETE)\s(.+)\s(HTTP\/[01]\.[019])/i', $b_line)) {
         continue;
      } else {
         print headerprintnobr($b_line) . " <br />";
      }
   }
   ?>
   </div>
   </td></tr>
   </table>
   <?PHP
   $requestBodyPrint = bodyprint($event_detail['c_full']);
   if ($requestBodyPrint != '') {
   ?>
   	<table cellpadding="0" cellspacing="0">
      <tr><td valign="top"><div class="verticaltext">B O D Y</div></td>
      <td>
        <div class="wordwrap eventHeader">   
        <pre class="printCode">
<?PHP
print $requestBodyPrint;
?>
</pre>
        </div>
      </td></tr>
      </table>
      
   <?PHP
   }
   ?>

	<br>
	<h2>Response Details</h2>

 	<div>
	<table cellpadding="0" cellspacing="0">
	<tr>
   <td valign="top"><div class="verticaltext">H E A D E R</div></td>
   <td valign="top">
   <div class="wordwrap eventHeader">   
   <?PHP 
     print headerprintnobr($event_detail['f_protocol']) . " <b> <a href=\"events.php?http_Status=".headerprintnobr($event_detail['f_status'])."\">" . headerprintnobr($event_detail['f_status']) . "</a> </b>" . headerprintnobr($event_detail['f_msg']) . "<br />";
   ?>  
   
   <?PHP
      $farr = explode("\n", trim($event_detail['f_full']));
      foreach($farr as $f_line) {
         if (preg_match('/^Content-Length\:/i', $f_line)) {
            print "<span id=\"header_cap\">Content-Length: </span>";
            print "<span id=\"header_content\">" . headerprintnobr($event_detail['f_content_length']) . "</span><br />";
         } elseif (preg_match('/^Connection\:/i', $f_line)) {
            print "<span id=\"header_cap\">Connection: </span>";
            print "<span id=\"header_content\">" . headerprintnobr($event_detail['f_connection']) . "</span><br />";
         } elseif (preg_match('/^Content-Type\:/i', $f_line)) {
            print "<span id=\"header_cap\">Content-Type: </span>";
            print "<span id=\"header_content\">" . headerprintnobr($event_detail['f_content_type']) . "</span><br />";
         } elseif (preg_match('/(HTTP\/\d\.\d)\s(\d\d\d)\s([\w\s]+)/i', $f_line)) {
            continue;
         } elseif (preg_match('/^\-\-[a-f0-9]+\-F\-\-$/i', $f_line)) {
            continue;
         } elseif (preg_match('/^([\w-]+)\:\s(.*)/i', $f_line, $bmatch)) {
            print "<span id=\"header_cap\">$bmatch[1]: </span>";
            print "<span id=\"header_content\">" . headerprintnobr($bmatch[2]) . "</span><br />";          
         } else {
            print "<span id=\"header_content\">" . headerprintnobr($f_line) . " </span><br />";          
         }
      }
   ?>
   </div>   
   </td></tr>
	</table>
	</div>

	<br />
	<div>
   <?PHP
   
   $bodyPrint = bodyprint($event_detail['e_full']);
   if ($bodyPrint != "") {
   ?>
      <table cellpadding="0" cellspacing="0">
      <tr>
      <td valign="top"><div class="verticaltext">B O D Y</div></td>
      <td valign="top">
      <div class="wordwrap eventHeader">   
        <pre class="printCode">
<?PHP
print $bodyPrint;
?>
        </pre>
        </div>
      </td></tr>
      </table>
      </div>
      <?PHP
   }
   ?>
	</td>

	<td width="400" style="padding-left: 15px">
	<div>
	<table cellpadding="0" cellspacing="0" border="0">
	<tr>
   <td valign="top"><strong>Transaction ID&nbsp;</strong></td>
	<td><strong>
   <?PHP 
      print $event_detail['event_id'];
   ?>       
   </strong></td>
	</tr>

	<tr>
	<td valign="top">Sensor</td>
   <td>
   <?PHP
	$sensor = getSensorName($event_detail['sensor_id']);
   print "<div title=\"".$sensor['description']."\"><a href=\"events.php?src_sensor=".headerprintnobr($event_detail['sensor_id'])."\" title=\"Filter by this Sensor\">".$sensor['name']."</a></div>";
   ?>
   
   </td>
	</tr>
	
	<tr>
	<td valign="top">Unique ID</td>
	<td>
   <?PHP 
      print headerprintnobr($event_detail['a_uniqid']);
   ?>
   
   </td>
	</tr>
   <tr>
	<td valign="top">Action</td>
	<td>
   <?PHP
      
      if ($event_detail['h_action_status'] < 10) {
         $h_action_text = "Dropped";
         print "<a href=\"events.php?action=".$event_detail['h_action_status']."\" title=\"Filter by action: $h_action_text\">
               <img src=\"images/block.png\" alt=\"$h_action_text (".$event['h_action_status'].")\" style=\"border-style: none\" /></a>";    
      } elseif($event_detail['h_action'] < 20) {
         $h_action_text = "Allowed";
         print "<a href=\"events.php?action=".$event_detail['h_action_status']."\" title=\"Filter by action: $h_action_text\">
               <img src=\"images/allow.png\" alt=\"$h_action_text (".$event['h_action_status'].")\" style=\"border-style: none\" /></a>";
      } elseif($event_detail['h_action'] >= 20) {
         $h_action_text = "Passed/Detection Only";
         print "<a href=\"events.php?action=".$event_detail['h_action_status']."\" title=\"Filter by action: $h_action_text\">
               <img src=\"images/warning.png\" alt=\"$h_action_text (".$event['h_action_status'].")\" style=\"border-style: none\" /></a>";
      }
      print " " . headerprintnobr($event_detail['h_action_status_msg']);
      
   ?>
   
   </td>
	</tr>
   <?PHP
   if ($event_detail['h_engine_mode'] != '') {
   ?>
      <tr>
      <td valign="top">Engine Mode</td>
      <td>
         <a href="events.php?engineMode=<?PHP print $event_detail['h_engine_mode']; ?>" title="Filter by Engine Mode: <?PHP print $event_detail['h_engine_mode']; ?>">
         <?PHP
            print headerprintnobr($event_detail['h_engine_mode']);
         ?></a> 
         &nbsp;(<i>modsecurity 2.7+ only</i>)
      </td>
      </tr>
    <?PHP
    }
    ?>

   <tr>
 	<td valign="top">Score</td>
	<td>
   <?PHP 
      if ($event_detail['h_Score_Total'] != "") {
         print "Total: ".$event_detail['h_Score_Total'];
      } else {
         print "Total: - ";
      }
      if ($event_detail['h_Score_SQLi'] != "") {
         print ", SQLi: ".$event_detail['h_Score_SQLi'];
      } else {
         print ", SQLi: - ";
      }
      if ($event_detail['h_Score_XSS'] != "") {
         print ", XSS: ".$event_detail['h_Score_XSS'];
      } else {
         print ", XSS: - ";
      }
   ?>
   
   </td>
	</tr>
   
	<tr>
   <td valign="top">Source IP</td>
	<td>
   <?PHP 
      print "<a href=\"events.php?esrc=".headerprintnobr($event_detail['a_client_ip'])."\" title=\"Filter by this Client IP\">". headerprintnobr($event_detail['a_client_ip']) ."</a> / ".$event_detail['a_client_port'];
   ?>   
  	</td>
	</tr>
	<tr>
   <td valign="top">Source IP Detail &nbsp;</td>
	<td>
   <?PHP 
      if ($event_detail['a_client_ip_cc'] != '') {
         $countryName = geoip_record_by_name($event_detail['a_client_ip']);
         print "<a href=\"events.php?ipcc=".headerprintnobr($event_detail['a_client_ip_cc'])."\" title=\"Filter by this Client IP Country Code ".headerprintnobr($event_detail['a_client_ip_cc'])."\">";
         print "<img src=\"images/flags/png/".strtolower(headerprintnobr($event_detail['a_client_ip_cc'])).".png\" alt=\"". headerprintnobr($event_detail['a_client_ip_cc']) ."\" style=\"border-style: none\"> ".$countryName['country_name']."</a>";
         print " / ";
      } else {
            print "NA / ";
      }
      if ($event_detail['a_client_ip_asn'] != '0'){
         print "<a href=\"events.php?ipasn=".headerprintnobr($event_detail['a_client_ip_asn'])."\" title=\"Filter by this Client IP Autonomous System Number ".headerprintnobr($event_detail['a_client_ip_asn'])."\">AS". $event_detail['a_client_ip_asn'] ."</a>";
      } else {
            print "NA";
      }
   ?>   
  	</td>
	</tr>

	<tr>
	<td valign="top">Destination</td>
	<td>
   <?PHP 
      print "<a 
      
      href=\"events.php?web_Hostname=".headerprintnobr($event_detail['b_host'])."\" title=\"Filter by this Web Hostname\">".headerprintnobr(getWebHostName($event_detail['b_host'])) ."</a> / ". headerprintnobr($event_detail['a_server_port']);
   ?>          
   </td>
	</tr>

	<tr>
	<td valign="top">Web App Info</td>
	<td>
   <?PHP 
      if ($event_detail['h_wa_info_app_id'] == "") {
         print "-";
      } else {   
         print "<a href=\"events.php?webApp=".headerprintnobr($event_detail['h_wa_info_app_id'])."\" title=\"Filter by this Web App Info\">".headerprintnobr($event_detail['h_wa_info_app_id'])."</a> ";
      }
   ?>          
   </td>
	</tr>   
	<tr>
	<td valign="top">Session ID</td>
	<td>
   <?PHP 
      if ($event_detail['h_wa_info_sess_id'] == "") {
         print "-";
      } else {
         print headerprintnobr($event_detail['h_wa_info_sess_id']);
      }
   ?>          
   </td>
	</tr>   

	<tr>
	<td valign="top">User ID</td>
	<td>
   <?PHP 
      if ($event_detail['h_wa_info_user_id'] == "") {
         print "-";
      } else {   
         print "<a href=\"events.php?userId=\"".headerprintnobr($event_detail['h_wa_info_user_id'])."\" title=\"Filter by this User ID\">".headerprintnobr($event_detail['h_wa_info_user_id']);
      }
   ?>
   </td>
	</tr>      
   
	<tr>
   <td valign="top">Timestamp</td>
   <td>
   <?PHP 
      print $event_detail['a_timestamp'] . " " . $event_detail['a_timezone'];
   ?>
   <span><br />
   <?PHP 
      print "(received at ". $event_detail['received_at'].")";
   ?>       
   
   </span></td>
	</tr>

    <?PHP 
        if($event_detail['h_stopwatch2_duration'] > 0) {
        ?>
            <tr>
            <td valign="top">Performance timings</td>
            <td valign="top"><span>
            <?PHP 
                print "Duration: " . $event_detail['h_stopwatch2_duration']/$timingScale . " $timingScaleAbrv <br />";
                print "Combined: " . $event_detail['h_stopwatch2_combined']/$timingScale . " $timingScaleAbrv <br />";
                print "Phase 1: " . $event_detail['h_stopwatch2_p1']/$timingScale . " $timingScaleAbrv <br />";
                print "Phase 2: " . $event_detail['h_stopwatch2_p2']/$timingScale . " $timingScaleAbrv <br />";
                print "Phase 3: " . $event_detail['h_stopwatch2_p3']/$timingScale . " $timingScaleAbrv <br />";
                print "Phase 4: " . $event_detail['h_stopwatch2_p4']/$timingScale . " $timingScaleAbrv <br />";
                print "Phase 5: " . $event_detail['h_stopwatch2_p5']/$timingScale . " $timingScaleAbrv <br />";
                print "Storage read: " . $event_detail['h_stopwatch2_sr']/$timingScale . " $timingScaleAbrv <br />";
                print "Storage write: " . $event_detail['h_stopwatch2_sw']/$timingScale . " $timingScaleAbrv <br />";
                print "Logging: " . $event_detail['h_stopwatch2_l']/$timingScale . " $timingScaleAbrv <br />";
                print "Garbage collection: " . $event_detail['h_stopwatch2_gc']/$timingScale . " $timingScaleAbrv";
            ?>         
            </span></td>
            </tr>   

        <?PHP         
        } else {
            ?>
            <tr>
            <td valign="top">Performance timings</td>
            <td valign="top"><span>
            <?PHP 
                print "Duration: " . $event_detail['h_stopwatch_duration']/$timingScale . " $timingScaleAbrv <br />";
            ?>         
            </span></td>
            </tr>   
        <?PHP         
        } 
        ?>
            
   
	<tr>
	<td valign="top">Server</td>
   <td>
   <?PHP 
      print $event_detail['h_server'];
   ?>
   </td>
	</tr>

	<tr>
	<td valign="top">Producer</td>
	<td>
   <?PHP 
      print $event_detail['h_producer'];
   ?>       
   </td>

	</tr>
	<tr>
	<td valign="top">Rule Set</td>
	<td>
   <?PHP 
      print $event_detail['h_producer_ruleset'];
   ?>       
   </td>
   </tr>
	<tr>
    <?PHP 
    if ($event_detail['false_positive']) {
    ?>
        <td valign="top" colspan="2"><b>Marked False Positive</b></td>
        
   <?PHP 
    } else {
        ?>
        <td valign="top" colspan="2"><b>&nbsp;</b></td>
        <?PHP
    }
   ?>       


	</tr>	
	
	</table>
	</div>

	<br />

	<div>
	<table  cellpadding="0" cellspacing="0" border="0">
	<tr><td align="center">
   

   <?PHP
      print "<a href=\"getrawevent.php?e=".$event_detail['event_id']."\">";
   ?>
   RAW Transaction download</a> <img src="images/disk-black.png" border="0">
	</td></tr>

	</tr></table>
	</div>
	
	<br />
   
   </table>
   </table>

	</div>
</div>	

<?PHP
require_once("../footer.php");
?>
