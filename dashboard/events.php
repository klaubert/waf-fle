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
require_once("../functions.php");
global $DEBUG;
if ($DEBUG) {
   $starttime_main = microtime(true);
}
$pagId = 'events';
$thisPage = basename(__FILE__);
require_once("../session.php");
require_once("../header.php");
require_once("../filterprocessing.php");
?>
    <div id="page-wrap">
        <div id="main-content">
         <?PHP
         // Show the filter parameters from file filtershow.php
            require_once("../filtershow.php");
         ?>
         <div id="clear"> </div>
         <div id="events">

         <?PHP
            // Start to pagging the events
            if (isset($_GET["p"])) {
                $page = @sanitize_int($_GET["p"], $min='1' ) or $page = 1;
            } else {
                $page = 1;
            }
            if (isset($_SESSION['eventCount'])) {
               list ($event_list, $total_events, $events_count, $current_page) = eventFilter($page, $max_event_number, $_SESSION['eventCount']);
            } else {
               list ($event_list, $total_events, $events_count, $current_page) = eventFilter($page, $max_event_number, 0);
               $_SESSION['eventCount'] = $total_events;
            }
		 ?>
			<form id="eventsAction" name="eventsAction" action="events.php" method="post">
				<div id="events_header">
				<table width="100%" cellspacing="0" cellpadding="3" border="0">
				<tbody><tr bgcolor="#6d88ad">
					<td align="left"  class="textHeaderDark">
					</td>
					<td align="left" class="textHeaderDark">

					<div class="toolmenu">
						<ul>
							<li><a class="check dropdown" href="#"><input type="checkbox" onclick="selectAll(this)" title="Select all"></a>
							<li><a class="left" href="#" onClick="if(confirm('Confirm event deletion?'))
					submitformDel(); else unselectAll(this);">Delete</a></li>
							<li><a class="left" href="#" onClick="if(confirm('Confirm event preservation?'))
					submitformPreserve(); else unselectAll(this);" value="Preserve">Preserve</a></li>
                            <li><a class="rigth" href="#" onClick="if(confirm('Confirm event as False Positive?'))
					submitformMarkFP(); else unselectAll(this);" value="mark">Mark as False Positive</a></li>
							<li><a class="right dropdown" href="#">Filter Actions<span class="arrow"></span></a>
							<ul class="width-3">
								<li><a href="#" id="dialog_falsePositiveByFilter">Mark as False Positive events of current filter</a></li>
                                <li><a href="#" id="dialog_deleteByFilter">Delete events of current filter</a></li>
							</ul>
						</ul>
					</div>

				  <input type="hidden" name="action" value="1">
                  </td>
                  <td align="right" class="textHeaderDark">
                     <?PHP
                     
                     if (($total_events%$max_event_number)<>0) {
                        $pmax = floor($total_events/$max_event_number)+1;
                     } else {
                        $pmax = floor($total_events/$max_event_number);
                     }
                     if ($page > 2) {
                        print "<strong><a href=\"events.php?p=1\" id=\"linkOverDark\"  class=\"linkOverDark\"><< Start</a>&nbsp;</strong>&nbsp;";
                     }
                     if ($page > 1) {
                        print "<strong><a href=\"events.php?p=".headerprintnobr(($page-1))."\" id=\"linkOverDark\"  class=\"linkOverDark\">< Previous</a>&nbsp;</strong>&nbsp;";
                     }

                     if ( $current_page == $pmax) {
                        print "Events <strong>".headerprintnobr(number_format((($max_event_number*($current_page-1))+1)))." - ".headerprintnobr(number_format((($max_event_number*($current_page-1))+$events_count)))."</strong> of <strong>".headerprintnobr(number_format($total_events))."</strong>&nbsp;&nbsp;\n";
                     } else {
                        print "<strong>".headerprintnobr(number_format((($current_page*$events_count)-$events_count+1)))." - ".headerprintnobr(number_format(($current_page*$events_count)))."</strong> of <strong>".number_format($total_events)."</strong>&nbsp;&nbsp;\n";
                     }
                     if ($page < $pmax) {
                        print "<strong><a id=\"linkOverDark\" class=\"linkOverDark\" href=\"events.php?p=".headerprintnobr(($page+1))."\"> Next > </a>&nbsp;</strong>";
                     }
                     if ($page < ($pmax-1)) {
                        print "<strong><a id=\"linkOverDark\" class=\"linkOverDark\" href=\"events.php?p=".headerprintnobr(($pmax))."\">Last >> </a>&nbsp;</strong>";
                     }
                     ?>

                  </td>
               </tr>
            </tbody></table>
         </div>
         <div id="events_body">

         
         <?PHP
            if ($total_events == 0 ) {
               print "<tr>
               <td>
               <br />
               No events found 
               <br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
               </td></tr>";
            } else {
                print "<table class=\"flexEvents\">";
                foreach ($event_list as $event) {
                   print "<tr>";
                   print "<td><input type=\"checkbox\" value=\"".headerprintnobr($event['event_id'])."\" name=\"event[]\" id=\"event\"/></td>";
                   $severitytext = $severity[$event['h_severity']];
                   print "<td><a href=\"eventview.php?e=".headerprintnobr($event['event_id'])."\" title=\"Show events details\">Details</a></td>";
                   if ($event['h_action_status'] < 10) {
                      $h_action_text = "Blocked";
                      print "<td>
                      <a href=\"events.php?actionstatus=".headerprintnobr($event['h_action_status'])."\" title=\"Filter by action: ".headerprintnobr($h_action_text)."\">
                      <img src=\"images/block.png\" alt=\"$h_action_text (".headerprintnobr($event['h_action_status']).")\" style=\"border-style: none\" />
                      </a>
                      </td>";                    
                   } elseif ($event['h_action_status'] < 20) {
                      $h_action_text = "Allowed";
                      print "<td>
                      <a href=\"events.php?actionstatus=".headerprintnobr($event['h_action_status'])."\" title=\"Filter by action: $h_action_text\">
                      <img src=\"images/allow.png\" alt=\"$h_action_text (".headerprintnobr($event['h_action_status']).")\" style=\"border-style: none\" />
                      </a>
                      </td>";
                   } elseif ($event['h_action_status'] >= 20) {
                      $h_action_text = "Passed/Detection Only";
                      print "<td>
                      <a href=\"events.php?actionstatus=".$event['h_action_status']."\" title=\"Filter by action: $h_action_text\">
                      <img src=\"images/warning.png\" alt=\"$h_action_text (".headerprintnobr($event['h_action_status']).")\" style=\"border-style: none\" />
                      </a>
                      </td>";

                   }
                   print "<td>";
                   $sensor = getSensorName($event['sensor_id']);
                   print "<div title=\"Click to filter by sensor ".headerprintnobr($sensor['name']).": ".headerprintnobr($sensor['description'])."\"><a href=\"events.php?src_sensor=".headerprintnobr($event['sensor_id'])."\" > ".headerprintnobr($sensor['name'])." </a>  <br> </div>";
                   print "</td>";
                   print "<td><a href=\"events.php?severity=".headerprintnobr($event['h_severity'])."\">
                   <img src=\"images/".headerprintnobr($event['h_severity']).".png\" style=\"border-style: none\" title=\"Click to filter by severity: ".headerprintnobr($severitytext)."\" alt=\"Click to filter by severity: ".headerprintnobr($severitytext)."\" /></a></td>";

                   print "<td>".headerprintnobr($event['a_timestamp'])."</td>";
                   print "<td>
                   <a href=\"events.php?esrc=".headerprintnobr($event['a_client_ip'])."\" title=\"Click to filter by this IP\">
                   ".$event['a_client_ip']." </a>
                   ".$event['a_client_port']."
                   </td>";

                   if ($event['b_host'] != '') {
                      print "<td><div class=\"wordwrap\">Hostname: <a href=\"events.php?web_Hostname=".headerprintnobr($event['b_host'])."\" title=\"Click to filter by this Web Hostname\">".headerprintnobr(getWebHostName($event['b_host']))."</a>, ";
                   } else {
                      print "<td>Hostname: N/A, ";
                   }
                   print "Port: ".headerprintnobr($event['a_server_port']).", <br />
                      Method: <a href=\"events.php?method=".headerprintnobr($event['b_method'])."\" title=\"Click to filter by this method\">".$event['b_method']."</a>,
                      Path: <a href=\"events.php?path=".headerprintnobr($event['b_path'])."\" title=\"Click to filter by this Path\">".headerprintnobr($event['b_path']) ."</a>";
                   if ($event['b_path_parameter'] != "") {
                      print "?" . headerprintnobr($event['b_path_parameter']);
                   }
                   print "<br />Status Code: <a href=\"events.php?http_Status=".headerprintnobr($event['f_status'])."\" title=\"Click to filter by this HTTP Status\">".headerprintnobr($event['f_status'])."</a> ";
                   if ($event['f_msg'] != '') {
                      print "(<i>".headerprintnobr($event['f_msg'])."</i>)</div></td>";
                   }
                   print "<td>";
                   print "<div class=\"wordwrap\">";
                   if (is_array($event['h_message_ruleId'])) {
                      foreach($event['h_message_ruleId'] as $key => $value) {
                         if (preg_match('/^Access denied/i', $event['h_message_action'][$key])) {
                            print "<b>";
                         }
                         if (($event['h_message_ruleMsg'][$key] != '' OR $event['h_message_ruleData'][$key] != '')) {
                            if ($event['h_message_ruleId'][$key] != "") {
                               print "<a href=\"events.php?ruleid=".headerprintnobr($event['h_message_ruleId'][$key])."\" title=\"Add the Rule ID: ".headerprintnobr($event['h_message_ruleId'][$key])." to filter\">".headerprintnobr($event['h_message_ruleMsg'][$key])."</a>";
                            } else {
                               print headerprintnobr($event['h_message_ruleMsg'][$key]);
                            }
                            if ($event['h_message_ruleData'][$key] != "") {
                               print " (<i>".headerprintnobr($event['h_message_ruleData'][$key]) ."</i>)<br />";
                            } else {
                               print "<br />";
                            }
						} else {
                            if ($event['h_message_ruleId'][$key] != "") {
                               print "<a href=\"events.php?ruleid=".headerprintnobr($event['h_message_ruleId'][$key])."\" title=\"Add the Rule ID: ".headerprintnobr($event['h_message_ruleId'][$key])." to filter\">Rule ".headerprintnobr($event['h_message_ruleId'][$key])." (no message)</a>";
                            } else {
                               print headerprintnobr($event['h_message_ruleMsg'][$key]);
                            }
                            if ($event['h_message_ruleData'][$key] != "") {
                               print " (<i>".headerprintnobr($event['h_message_ruleData'][$key]) ."</i>)<br />";
                            } else {
                               print "<br />";
                            }						 
                         }
                         if (preg_match('/^Access denied/i', $event['h_message_action'][$key])) {
                            print "</b>";
                         }
                      }
                   }
                   print "</div>";
                   print "</td>";

                   print "</tr>";
                }
                }
            print "</table>";
            print "</form>";
            print "</div>";
         ?>
         <!-- Footer paging  -->
            <div id="events_header">
               <table width="100%" cellspacing="0" cellpadding="3" border="0">
               <tbody><tr bgcolor="#6d88ad">
                  <td align="left"  class="textHeaderDark">

                  </td>
                  <td align="center" class="textHeaderDark">
                     </td>
                  <td align="right" class="textHeaderDark">
                      <?PHP
                     if (($total_events%$max_event_number)<>0) {
                        $pmax = floor($total_events/$max_event_number)+1;
                     } else {
                        $pmax = floor($total_events/$max_event_number);
                     }
                     if ($page > 2) {
                        print "<strong><a href=\"events.php?p=1\" id=\"linkOverDark\"  class=\"linkOverDark\"><< Start</a>&nbsp;</strong>&nbsp;";
                     }
                     if ($page > 1) {
                        print "<strong><a href=\"events.php?p=".headerprintnobr(($page-1))."\" id=\"linkOverDark\"  class=\"linkOverDark\">< Previous</a>&nbsp;</strong>&nbsp;";
                     }

                     if ( $current_page == $pmax) {
                        print "Events <strong>".headerprintnobr(number_format((($max_event_number*($current_page-1))+1)))." - ".headerprintnobr(number_format((($max_event_number*($current_page-1))+$events_count)))."</strong> of <strong>".headerprintnobr(number_format($total_events))."</strong>&nbsp;&nbsp;\n";
                     } else {
                        print "<strong>".headerprintnobr(number_format((($current_page*$events_count)-$events_count+1)))." - ".headerprintnobr(number_format(($current_page*$events_count)))."</strong> of <strong>".number_format($total_events)."</strong>&nbsp;&nbsp;\n";
                     }
                     if ($page < $pmax) {
                        print "<strong><a id=\"linkOverDark\" class=\"linkOverDark\" href=\"events.php?p=".headerprintnobr(($page+1))."\"> Next > </a>&nbsp;</strong>";
                     }
                     if ($page < ($pmax-1)) {
                        print "<strong><a id=\"linkOverDark\" class=\"linkOverDark\" href=\"events.php?p=".headerprintnobr(($pmax))."\">Last >> </a>&nbsp;</strong>";
                     }
                     ?>

                  </td>
               </tr>
            </tbody></table>
         </div>
         </div>
        </div>
    </div>

<script type="text/javascript">
        $('.flexEvents').flexigrid({
            resizable: false,
            height: 'auto', //default height
         //   width: 'auto', //auto width
            showToggleBtn: false,
            nowrap: false,
            colModel : [
                {display: '', name : 'void', width : 30, sortable : false, align: 'left'},
                {display: 'Event', name : 'Event', width : 30, sortable : false, align: 'center'},
                {display: 'Action', name : 'Action', width : 40, sortable : false, align: 'center'},
                {display: 'Sensor', name : 'Sensor', width : 40, sortable : false, align: 'center'},
                {display: 'Severity', name : 'Severity', width : 40, sortable : false, align: 'center'},
                {display: 'Date/Time', name : 'Date/Time', width : 70, sortable : false, align: 'left'},
                {display: 'Source/Port', name : 'Source/Port', width : 80, sortable : false, align: 'left'},
                {display: 'Hostname/Path', name : 'Hostname/Path', width : 400, sortable : false, align: 'left'},
                {display: 'Rules Alert', name : 'Rules Alert', width : 410, sortable : false, align: 'left'},
            ],
            singleSelect: true,
        });
</script>


<?PHP
require_once("../footer.php");
?>
