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


<form method="GET" action="<?PHP print $thisPage; ?>" id="advFilterForm">

<div class="filterColumn">

    <fieldset>
        <legend>
            &nbsp;General&nbsp;
        </legend>
        <div class="filterRow">
            <label for="dateFrom">
                <div class="filterLeft">
                    Date From
                </div>
                <div class="filterRight">
                    <?PHP
                     print "<input type=\"text\" name=\"StDate\" id=\"DateFrom\" value=\"".$_SESSION['filter']['StDate']."\" size=\"9\" class=\"text ui-widget-content ui-corner-all\" style=\"width: 110px\" autocomplete=\"off\">";
                     print " <input type=\"text\" name=\"StTime\" id=\"timeFrom\" value=\"".$_SESSION['filter']['StTime']."\" size=\"7\" class=\"text ui-widget-content ui-corner-all\" style=\"width: 90px\" autocomplete=\"off\">";
                     ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="dateTo">
                <div class="filterLeft">
                    Date To
                </div>
                <div class="filterRight">
                  <?PHP
                    print "<input type=\"text\" name=\"FnDate\" id=\"DateTo\" value=\"".$_SESSION['filter']['FnDate']."\" size=\"9\" class=\"text ui-widget-content ui-corner-all\" style=\"width: 110px\" autocomplete=\"off\">";
                    print " <input type=\"text\" name=\"FnTime\" id=\"timeTo\" value=\"".$_SESSION['filter']['FnTime']."\" size=\"7\" class=\"text ui-widget-content ui-corner-all\" style=\"width: 90px\" autocomplete=\"off\">";
                 ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Sensor">
                <div class="filterLeft">
                    Sensor
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                        if (isset($_SESSION['filter']['Not_src_sensor']) AND $_SESSION['filter']['Not_src_sensor'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_src_sensor\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_src_sensor\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                        
                    <select id="filter_select" name="src_sensor" size="1" style="width: 165px" class="text ui-widget-content ui-corner-all ">
                    <option value="all">All Sensors </option>
                    <?PHP
                        $sensorsList = getsensors();
                        foreach ( $sensorsList[0] as $sensor) {
                            if ($_SESSION['filter']['src_sensor'] == $sensor['sensor_id']) {
                                print "<option selected value=\"".$sensor['sensor_id']."\" title=\"".$sensor['description']."\">".$sensor['name']."  </option>";
                            } else {
                                print "<option value=\"".$sensor['sensor_id']."\" title=\"".$sensor['description']."\">".$sensor['name']."  </option>";
                            }
                        }
                    ?>
                    </select>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Target Hostname">
                <div class="filterLeft">
                    Target Hostname
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                        if (isset($_SESSION['filter']['Not_web_Hostname']) AND $_SESSION['filter']['Not_web_Hostname'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_web_Hostname\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_web_Hostname\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                    
                
                    <select id="filter_select" name="web_Hostname" size="1" style="width: 165px" class="text ui-widget-content ui-corner-all ">
                        <option value="x">All Web Hosts </option>
                        <?PHP
                            $hostnameList = getWebHosts();
                            foreach ( $hostnameList as $host) {
                                if ($_SESSION['filter']['web_Hostname'] == $host['host_id']) {
                                    print "<option selected value=\"".$host['host_id']."\">".headerprintnobr($host['hostname'])."  </option>";
                                } else {
                                    print "<option value=\"".$host['host_id']."\">".headerprintnobr($host['hostname']). "</option>";
                                }
                            }
                        ?>
                    </select>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
        <div class="tagTip" title="Host IP or CIDR network notation<br>Host: 10.0.0.1 <br>CIDR network: 192.168.0.0/24">
            <label for="Client IP">
                <div class="filterLeft">
                    Client IP
                    <br /><i></i>
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                        if (isset($_SESSION['filter']['Not_esrc']) AND $_SESSION['filter']['Not_esrc'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_esrc\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_esrc\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                 
                    <?PHP
                        print "<input type=\"text\" name=\"esrc\" value=\"" . $_SESSION['filter']['esrc'] ."\" size=\"20\" style=\"width: 160px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>
        </div>

        <div class="filterRow">
        <div class="tagTip" title="Country Code for the client IP address, ie. <br>US, BR, CA, CN, RU...">
            <label for="Client IP Country Code">
                <div class="filterLeft">
                    Client IP Country Code
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                        if (isset($_SESSION['filter']['Not_ipcc']) AND $_SESSION['filter']['Not_ipcc'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_ipcc\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_ipcc\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                   
                    <?PHP
                        print "<input type=\"text\" name=\"ipcc\" value=\"" . $_SESSION['filter']['ipcc'] ."\" size=\"20\" style=\"width: 160px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>
        </div>

        <div class="filterRow">
        <div class="tagTip" title="Autonomous System Number where client ip address belong">
            <label for="Client IP AS Number">
                <div class="filterLeft">
                    Client IP AS Number
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                        if (isset($_SESSION['filter']['Not_ipasn']) AND $_SESSION['filter']['Not_ipasn'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_ipasn\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_ipasn\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                   
                    <?PHP
                        print "<input type=\"text\" name=\"ipasn\" value=\"" . $_SESSION['filter']['ipasn'] ."\" size=\"20\" style=\"width: 160px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>
        </div>

        <div class="filterRow">
            <label for="Action">
                <div class="filterLeft">
                    Action
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                        if (isset($_SESSION['filter']['Not_actionstatus']) AND $_SESSION['filter']['Not_actionstatus'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_actionstatus\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_actionstatus\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                    
                    <select name="actionstatus" size="1" style="width: 165px" class="text ui-widget-content ui-corner-all ">
                    <option value="all">All Actions</option>
                    <?PHP
                        // $eventAction is defined on config.php
                        if ($_SESSION['filter']['actionstatus'] == "block") {
                            print "<option selected value=\"block\">All Block actions </option>";
                        } else {
                            print "<option value=\"block\">All Block actions </option>";
                        }
                        foreach ( $ActionStatus as $act => $ind) {
                            if ($act < 10) {
                                if ($_SESSION['filter']['actionstatus'] == "$act") {
                                    print "<option selected value=\"".$act."\">&nbsp;&nbsp;&nbsp;&nbsp;$ind </option>";
                                } else {
                                    print "<option value=\"".$act."\">&nbsp;&nbsp;&nbsp;&nbsp;$ind </option>";
                                }
                            }
                        }
                        if ($_SESSION['filter']['actionstatus'] == "allow") {
                            print "<option selected value=\"allow\">All Allow actions </option>";
                        } else {
                            print "<option value=\"allow\">All Allow actions </option>";
                        }
                        foreach ( $ActionStatus as $act => $ind) {
                            if ($act >= 10 AND $act < 20) {
                                if ($_SESSION['filter']['actionstatus'] == "$act") {
                                    print "<option selected value=\"".$act."\">&nbsp;&nbsp;&nbsp;&nbsp;$ind </option>";
                                } else {
                                    print "<option value=\"".$act."\">&nbsp;&nbsp;&nbsp;&nbsp;$ind </option>";
                                }
                            }
                        }
                        if ($_SESSION['filter']['actionstatus'] == "warning") {
                            print "<option selected value=\"warning\">All Warning actions </option>";
                        } else {
                            print "<option value=\"warning\">All Warning actions </option>";
                        }
                        foreach ( $ActionStatus as $act => $ind) {
                            if ($act >= 20) {
                                if ($_SESSION['filter']['actionstatus'] == "$act") {
                                    print "<option selected value=\"".$act."\">&nbsp;&nbsp;&nbsp;&nbsp;$ind </option>";
                                } else {
                                    print "<option value=\"".$act."\">&nbsp;&nbsp;&nbsp;&nbsp;$ind </option>";
                                }
                            }
                        }
                    ?>
                    </select>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Event Severity">
                <div class="filterLeft">
                    Event Severity
                </div>
                <div class="filterRight">
                    Not
                    <?PHP
                        if (isset($_SESSION['filter']['Not_severity']) AND $_SESSION['filter']['Not_severity'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_severity\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_severity\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>

                    <select name="severity" size="1" style="width: 165px" class="text ui-widget-content ui-corner-all ">
                    <?PHP
                    if (!isset($_SESSION['filter']['severity'])) {
                        print "<option selected value=\"x\">All Severities </option>";
                    } else {
                        print "<option value=\"x\">All Severities </option>";
                    }
                    var_dump($_SESSION['filter']['severity']);
                    foreach ( $severity as $sevInd => $sev) {
                        if (isset($_SESSION['filter']['severity']) AND $_SESSION['filter']['severity'] == $sevInd) {
                            print "<option selected value=\"".$sevInd."\">". $sev . " </option>";
                        } else {
                            print "<option value=\"".$sevInd."\">". $sev ."  </option>";
                        }
                    }
                    ?>
                    </select>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>




        <div class="filterRow">
        <div class="tagTip" title="modsecurity 2.7+ only">
            <label for="Engine Mode">
                <div class="filterLeft">
                    <div >Engine Mode</div>
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                        if (isset($_SESSION['filter']['Not_engineMode']) AND $_SESSION['filter']['Not_engineMode'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_engineMode\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_engineMode\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                 
                    <select name="engineMode" size="1" style="width: 165px" class="text ui-widget-content ui-corner-all ">
                        <?PHP
                            if ($_SESSION['filter']['engineMode'] == "ENABLED") {
                                ?>
                                <option value="x">All </option>
                                <option selected value="ENABLED">ENABLED </option>;
                                <option value="DETECTION_​​ONLY">DETECTION ​​ONLY </option>;
                                <?PHP
                            } elseif ($_SESSION['filter']['engineMode'] == "DETECTION_​​ONLY") {
                                ?>
                                <option value="x">All </option>
                                <option value="ENABLED">ENABLED </option>;
                                <option selected value="DETECTION_​​ONLY">DETECTION ​​ONLY </option>;
                                <?PHP
                            } else {
                                ?>
                                <option selected value="x">All </option>
                                <option value="ENABLED">ENABLED </option>;
                                <option value="DETECTION_​​ONLY">DETECTION ​​ONLY </option>;
                                <?PHP
                            }
                        ?>
                    </select>
                </div>
            </label>
            <div class="filterClear"></div>
            </div>
        </div>

        <div class="filterRow">
            <label for="Method">
                <div class="filterLeft">
                    HTTP Method
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                        if (isset($_SESSION['filter']['Not_method']) AND $_SESSION['filter']['Not_method'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_method\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_method\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                  
                    <select name="method" size="1" style="width: 165px" class="text ui-widget-content ui-corner-all ">
                    <option value="x">All Method</option>
                    <?PHP
                        $methodList = getMethodList();
                        foreach ( $methodList[0] as $met) {
                            if ($_SESSION['filter']['method'] == $met['b_method']) {
                                print "<option selected value=\"".$met['b_method']."\">".headerprintnobr($met['b_method'])."</option>";
                            } else {
                                print "<option value=\"".$met['b_method']."\">".headerprintnobr($met['b_method'])."</option>";
                            }
                        }
                    ?>
                    </select>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
        <div class="tagTip" title="URI Path<br>To filter for a URI Path, use the: <br /> Full address (ie. /index.html), or <br /> Wildcard (*) path (ie. /app/*)">
            <label for="Path">
                <div class="filterLeft">
                    Path
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                    
                        if ($_SESSION['filter']['path_wc']) {
                            $pathwc_tmp = '*';
                            
                        }
                    
                        if (isset($_SESSION['filter']['Not_path']) AND $_SESSION['filter']['Not_path'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_path\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_path\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                   
                    <?PHP
                        print "<input type=\"text\" name=\"path\" value=\"".headerprintnobr($_SESSION['filter']['path']).$pathwc_tmp."\" size=\"20\" style=\"width: 160px\" maxlength=\"50\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>
        </div>

        <div class="filterRow">
            <label for="HTTP Status">
                <div class="filterLeft">
                    HTTP Status
                </div>
                <div class="filterRight">
                    Not
                    <?PHP
                        if (isset($_SESSION['filter']['Not_http_Status']) AND $_SESSION['filter']['Not_http_Status'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_http_Status\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_http_Status\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>

                    <select name="http_Status" size="1" style="width: 165px" class="text ui-widget-content ui-corner-all ">
                    <option value="x">All Status </option>
                    <?PHP
                        $statusList = getStatusList();
                        foreach ( $statusList[0] as $status) {
                            if ($_SESSION['filter']['http_Status'] == $status['code']) {
                                print "<option selected value=\"".$status['code']."\">".$status['code'].": " . $status['msg'] . " </option>";
                            } else {
                                print "<option value=\"".$status['code']."\">".$status['code'].": " . $status['msg'] ."  </option>";
                            }
                        }
                    ?>
                    </select>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="User ID">
                <div class="filterLeft">
                    User ID
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                        if (isset($_SESSION['filter']['Not_userId']) AND $_SESSION['filter']['Not_userId'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_userId\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_userId\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                 
                    <?PHP
                        print "<input type=\"text\" name=\"userId\" value=\"" . headerprintnobr($_SESSION['filter']['userId']) ."\" size=\"20\" style=\"width: 160px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Rule ID">
                <div class="filterLeft">
                    Rule ID
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                        if (isset($_SESSION['filter']['Not_ruleid']) AND $_SESSION['filter']['Not_ruleid'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_ruleid\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_ruleid\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                    
                    <?PHP
                        print "<input type=\"text\" name=\"ruleid\" value=\"" . $_SESSION['filter']['ruleid'] . "\" size=\"20\" style=\"width: 160px\" title=\"To filter rules without ID use a space\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Tag">
                <div class="filterLeft">
                    Tag
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                        if (isset($_SESSION['filter']['Not_tag']) AND $_SESSION['filter']['Not_tag'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_tag\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_tag\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                 
                    <select name="tag" size="1" style="width: 165px" class="text ui-widget-content ui-corner-all ">
                    <option value="all">All Tags </option>
                    <?PHP
                        $tagsList = getTags();
                        foreach ( $tagsList as $tag) {
                            if (isset($_SESSION['filter']['tag']) AND $_SESSION['filter']['tag'] == $tag['tag_id']) {
                                print "<option selected value=\"".$tag['tag_id']."\">". $tag['tag_name'] ."  </option>";
                            } else {
                                print "<option value=\"".$tag['tag_id']."\">". $tag['tag_name'] ."  </option>";
                            }
                        }
                    ?>
                    </select>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Web App Info">
                <div class="filterLeft">
                    Web App Info
                </div>
                <div class="filterRight">
                    Not 
                    <?PHP
                        if (isset($_SESSION['filter']['Not_webApp']) AND $_SESSION['filter']['Not_webApp'] == 1){
                            print "<input type=\"checkbox\" name=\"Not_webApp\" value=\"1\" class=\"text ui-widget-content ui-corner-all\" checked>";
                        } else {
                            print "<input type=\"checkbox\" name=\"Not_webApp\" value=\"1\" class=\"text ui-widget-content ui-corner-all\">";
                        }
                    ?>                    
                    <?PHP
                        print "<input type=\"text\" name=\"webApp\" value=\"".$_SESSION['filter']['webApp']."\" size=\"20\" style=\"width: 160px\" maxlength=\"50\" title=\"Type here a Web App Info to filter\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
        <div class="tagTip" title="Event marked by you as false positive">
            <label for="False Positive">
                <div class="filterLeft">
                    Marked as False Positive
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"falsePositive\" name=\"falsePositive\" size=\"1\" style=\"width: 210px\" class=\"text ui-widget-content ui-corner-all \">";

                        if (isset($_SESSION['filter']['falsePositive']) AND $_SESSION['filter']['falsePositive'] == TRUE ){
                            print "<option value=\"x\">           </option>";
                            print "<option selected value=\"1\">Marked     </option>";
                            print "<option value=\"0\">Not Marked </option>";
                        } elseif(isset($_SESSION['filter']['falsePositive']) AND $_SESSION['filter']['falsePositive'] == FALSE){
                            print "<option value=\"x\">           </option>";
                            print "<option value=\"1\">Marked     </option>";
                            print "<option selected value=\"0\">Not Marked </option>";
                        } else {
                            print "<option selected value=\"x\">           </option>";
                            print "<option value=\"1\">Marked     </option>";
                            print "<option value=\"0\">Not Marked </option>";
                        }
                        print "</select>";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>
        </div>

        <div class="filterRow">
        <div class="tagTip" title="Preserved Events">
            <label for="Preserved Events">
                <div class="filterLeft">
                    Preserved Events
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"preserved\" name=\"preserved\" size=\"1\" style=\"width: 210px\" class=\"text ui-widget-content ui-corner-all \">";

                        if (isset($_SESSION['filter']['preserved']) AND $_SESSION['filter']['preserved'] == TRUE ){
                            print "<option value=\"x\">           </option>";
                            print "<option selected value=\"1\">Preserved     </option>";
                            print "<option value=\"0\">Not Preserved </option>";
                        } elseif(isset($_SESSION['filter']['preserved']) AND $_SESSION['filter']['preserved'] == FALSE){
                            print "<option value=\"x\">           </option>";
                            print "<option value=\"1\">Preserved     </option>";
                            print "<option selected value=\"0\">Not Preserved </option>";
                        } else {
                            print "<option selected value=\"x\">           </option>";
                            print "<option value=\"1\">Preserved     </option>";
                            print "<option value=\"0\">Not Preserved </option>";
                        }
                        print "</select>";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>
        </div>

        <div class="filterRow">
            <label for="Unique ID">
                <div class="filterLeft">
                    Unique ID
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<input type=\"text\" name=\"uniqId\" value=\"" . $_SESSION['filter']['uniqId'] ."\" size=\"20\" style=\"width: 210px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

    </fieldset>
</div>



<div class="filterColumn">
    <fieldset>
        <legend>
            &nbsp;Anomaly Scoring&nbsp;
        </legend>

        <div class="filterRow">
            <label for="Score Total">
                <div class="filterLeft">
                    Total Score
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"score_interval\" name=\"score_interval\" size=\"1\" style=\"width: 40px\" class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['score_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"score\" value=\"" . $_SESSION['filter']['score'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Score SQLi">
                <div class="filterLeft">
                    SQLi Score
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"scoreSqli_interval\" name=\"scoreSqli_interval\" size=\"1\"  style=\"width: 40px\"  class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['scoreSqli_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"scoreSqli\" value=\"" . $_SESSION['filter']['scoreSqli'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Score XSS">
                <div class="filterLeft">
                    XSS Score
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"scoreXss_interval\" name=\"scoreXss_interval\" size=\"1\"  style=\"width: 40px\"  class=\"text ui-widget-content ui-corner-all \">";
                        if ($_SESSION['filter']['scoreXss_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"scoreXss\" value=\"" . $_SESSION['filter']['scoreXss'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>


   </fieldset>

<br />
    <fieldset>
        <legend>
            &nbsp;Rule Timing (in <?PHP print $timingScaleName; ?> )&nbsp;
        </legend>

        <div class="filterRow">
            <label for="Duration">
                <div class="filterLeft">
                    Duration
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"duration_interval\" name=\"duration_interval\" size=\"1\" style=\"width: 40px\" class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['duration_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"duration\" value=\"" . $_SESSION['filter']['duration'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Combined">
                <div class="filterLeft">
                    Combined
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"combined_interval\" name=\"combined_interval\" size=\"1\" style=\"width: 40px\" class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['combined_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"combined\" value=\"" . $_SESSION['filter']['combined'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Phase 1">
                <div class="filterLeft">
                    Phase 1
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"p1_interval\" name=\"p1_interval\" size=\"1\" style=\"width: 40px\" class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['p1_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"p1\" value=\"" . $_SESSION['filter']['p1'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Phase 2">
                <div class="filterLeft">
                    Phase 2
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"p2_interval\" name=\"p2_interval\" size=\"1\" style=\"width: 40px\" class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['p2_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"p2\" value=\"" . $_SESSION['filter']['p2'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Phase 3">
                <div class="filterLeft">
                    Phase 3
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"p3_interval\" name=\"p3_interval\" size=\"1\" style=\"width: 40px\" class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['p3_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"p3\" value=\"" . $_SESSION['filter']['p3'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Phase 4">
                <div class="filterLeft">
                    Phase 4
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"p4_interval\" name=\"p4_interval\" size=\"1\" style=\"width: 40px\" class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['p4_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"p4\" value=\"" . $_SESSION['filter']['p4'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Phase 5">
                <div class="filterLeft">
                    Phase 5
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"p5_interval\" name=\"p5_interval\" size=\"1\" style=\"width: 40px\" class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['p5_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"p5\" value=\"" . $_SESSION['filter']['p5'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Storage Read">
                <div class="filterLeft">
                    Storage Read
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"sr_interval\" name=\"sr_interval\" size=\"1\" style=\"width: 40px\" class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['sr_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"sr\" value=\"" . $_SESSION['filter']['sr'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Storage Write">
                <div class="filterLeft">
                    Storage Write
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"sw_interval\" name=\"sw_interval\" size=\"1\" style=\"width: 40px\" class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['sw_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"sw\" value=\"" . $_SESSION['filter']['sw'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Logging">
                <div class="filterLeft">
                    Logging
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"log_interval\" name=\"log_interval\" size=\"1\" style=\"width: 40px\" class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['log_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"log\" value=\"" . $_SESSION['filter']['log'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>

        <div class="filterRow">
            <label for="Garbage Collection">
                <div class="filterLeft">
                    Garbage Collection
                </div>
                <div class="filterRight">
                    <?PHP
                        print "<select id=\"gc_interval\" name=\"gc_interval\" size=\"1\" style=\"width: 40px\" class=\"text ui-widget-content ui-corner-all \">";

                        if ($_SESSION['filter']['gc_interval'] == "le") {
                            print "<option selected value=\"le\">&le;</option>";
                            print "<option value=\"ge\">&ge;</option>";
                        } else {
                            print "<option value=\"le\">&le;</option>";
                            print "<option selected value=\"ge\">&ge;</option>";
                        }
                        print "</select>";
                        print "<input type=\"text\" name=\"gc\" value=\"" . $_SESSION['filter']['gc'] ."\" size=\"10\" style=\"width: 120px\" class=\"text ui-widget-content ui-corner-all\" autocomplete=\"off\">";
                    ?>
                </div>
            </label>
            <div class="filterClear"></div>
        </div>
   </fieldset>
</div>

</form>

</p>
