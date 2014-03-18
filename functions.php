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

/**
 * General functions file
 */

// Force display errors to off
ini_set('display_errors', 0);
// Print errors in php log (normally apache errors log), but not notice logs
error_reporting(E_ALL ^ E_NOTICE);

if (is_readable("../config.php")) {
    require_once "config.php";
} else {
    echo '<h1>Error!</h1><br /><p>The <b>config.php</b> file is missing or have wrong permission. Please check and run WAF-FLE again!</p>';
    exit;
}


if (isset($SETUP) AND $SETUP == true ){
    header("Location: setup.php");
    exit;
}

$waffleVersion = '0.6.3';
// Set PHP default timezone as system timezone, need to avoid warning messages in PHP 5.3+
date_default_timezone_set(@date_default_timezone_get());
/* Constants   */
// Modsecurity severity levels
$severity[0]  = "EMERGENCY";
$severity[1]  = "ALERT";
$severity[2]  = "CRITICAL";
$severity[3]  = "ERROR";
$severity[4]  = "WARNING";
$severity[5]  = "NOTICE";
$severity[6]  = "INFO";
$severity[7]  = "DEBUG";
$severity[99] = "TRANSACTION";  // WAF-FLE way to classify events with no severity

// ModSecurity Rules Status (related to Alert Action)
// See http://sourceforge.net/apps/mediawiki/mod-security/index.php?title=Data_Format#Alert_Action_Description
$ActionStatus[0]  = "Access denied with connection close"; // action: Drop
$ActionStatus[1]  = "Access denied with code";  // action: Deny
$ActionStatus[2]  = "Access denied with redirection"; // action: Redirect
$ActionStatus[3]  = "Access denied using proxy to"; // action: Proxy
$ActionStatus[10] = "Access allowed";  // action: Allow
$ActionStatus[11] = "Access to phase allowed";
$ActionStatus[12] = "Access to request allowed";
$ActionStatus[20] = "Warning";  // action: Pass or Detection Only


// timing scale definition
if ($timePreference == 'mili') {
    $timingScale = 1000;
    $timingScaleName = 'miliseconds';
    $timingScaleAbrv = 'msec';
} else {
    $timingScale = 1;
    $timingScaleName = 'microseconds';
    $timingScaleAbrv = 'µsec';
}

// Double check to APC
if ($APC_ON) {
    $haveAPC = (extension_loaded('apc') && 1 ? true : false);
    if ($haveAPC && ini_get('apc.enabled')) {
        $APC_ON = true;
    } else {
        $APC_ON = false;
    }
}

// Database connection using PDO
try {
    $dbconn = new PDO('mysql:host='.$DB_HOST.';dbname='.$DATABASE, $DB_USER, $DB_PASS);
    $dbconn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $dbconn->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );

} catch (PDOException $e) {
    header("HTTP/1.1 500 Internal Server Error");
    header("Status: 500");
    print "HTTP/1.1 500 Internal Server Error <br />Error in database 
    connection!\n";
    if ($MLOG2WAFFLE_DEBUG OR $DEBUG) {
        print "Error (insert events) Message: " . $e->getMessage() . "\n";
        print "Error (insert events) getTraceAsString: " . $e->getTraceAsString() . "\n";
    }
    die();
}


// Check WAF-FLE vs. Database schema version
$sqlDatabaseVersion = 'SELECT `waffle_version` FROM `version` LIMIT 1';
try {
   $checkVersion_sth = $dbconn->prepare($sqlDatabaseVersion);

   // Execute the query
   $checkVersion_sth->execute();
   $dbSchema = $checkVersion_sth->fetch(PDO::FETCH_ASSOC);
   $checkVersion_sth->closeCursor();
   if ($dbSchema['waffle_version'] == '0.6.0') {
       $dbSchema['waffle_version'] = '0.6.3';
   }
   if ($dbSchema['waffle_version'] != $waffleVersion) {
      header ("Location: upgrade.php");
      exit;
   }
} catch (PDOException $e) {
    header("HTTP/1.1 500 Internal Server Error");
    header("Status: 500");
    print "HTTP/1.1 500 Internal Server Error \n";
    if ($DEBUG) {
        print "Error (DatabaseVersion) Message: " . $e->getMessage() . "\n";
        print "Error (DatabaseVersion) getTraceAsString: " . $e->getTraceAsString() . "\n";
    }
   die("Error in database query!");
}

// statistics functions
// Get events per sensor in a timeframe
function statsEventSensor()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    global $filterIndexHint;
    
    $filterType = 'filter';

    // Query for events
    $selector = 'SELECT COUNT(events.sensor_id) AS sensor_count, events.sensor_id as sensor_id FROM date_range, events ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }

    // SQL Query trailer
    $trailer = ' GROUP BY events.sensor_id ORDER BY sensor_count DESC';

    // Call superFilter to count filtered events
    $statsEventSensor = superFilter($selector, $trailer, $filterType, FALSE);

    $eventCount = count($statsEventSensor);
    for ($a = 0; $a < $eventCount; ++$a) {
        $sensor_name = getSensorName($statsEventSensor[$a]['sensor_id']);
        $statsEventSensor[$a]['sensor_name'] = $sensor_name['name'];
        $statsEventSensor[$a]['result']      = true ;
    }

    foreach ($statsEventSensor as $f_statusSensor) {
        $f_statsEventSensorTotal = $f_statsEventSensorTotal + $f_statusSensor['sensor_count'];
    }

    $nextElement = count($statsEventSensor);

    foreach ($statsEventSensor as $key => $value) {
        $f_eventSensorPercent = round($value['sensor_count'] * 100 / $f_statsEventSensorTotal, 2);
        $statsEventSensor[$key]['sensor_percent'] = $f_eventSensorPercent;
        $value['sensor_percent'] = $f_eventSensorPercent;

        if ($f_eventSensorPercent < 5 AND $nextElement > 7) { // make status less then 5% aggregated on 'others' when more that 7 sensors are in result
            $others = $others + $value['sensor_count'];

            $statsEventSensor[$nextElement]['sensor_count']   = $statsEventSensor[$nextElement]['sensor_count'] + $value['sensor_count'];
            $statsEventSensor[$nextElement]['sensor_name']    = 'Others';
            $statsEventSensor[$nextElement]['result']         = true;
            $statsEventSensor[$nextElement]['sensor_percent'] = $statsEventSensor[$nextElement]['sensor_percent'] + $value['sensor_percent'];

            unset($statsEventSensor[$key]);
        }
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $statsEventSensor;
}

/**
 *  Get events in last $timeframe hours, grouping/counting in 15 min. steps.
 */
function statsEvents()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    global $filterIndexHint;
    
    $filterType = 'filter';

    $startdate = $_SESSION[$filterType]['StDate'] . " " . $_SESSION[$filterType]['StTime'];
    $enddate   = $_SESSION[$filterType]['FnDate'] . " " . $_SESSION[$filterType]['FnTime'];
    $startFilterTime = date("U", strtotime($startdate));
    $endFilterTime   = date("U", strtotime($enddate));

    $deltaTime = ($endFilterTime - $startFilterTime + 1);
    $labelStep = round($deltaTime / 10) . ' seconds';
    if ($deltaTime <= 3600) { // <=1h
        $step     = 30;   // 30 sec
        $legend   = '30 seconds';
        $stepRate = floor($deltaTime / $step);  // 1 point each 30 sec
    } elseif ($deltaTime <= 7200) { // <=2h < 1h
        $step     = 60;
        $legend   = '1 minute';
        $stepRate = floor($deltaTime / $step);  // 1 point each 60 sec
    } elseif ($deltaTime <= 21600) { // <=6h < 2h
        $step     = 120;
        $legend   = '2 minutes';
        $stepRate = floor($deltaTime / $step);  // 1 point each 120 sec
    } elseif ($deltaTime <= 86400) { // <=24h < 6h
        $step     = 300;
        $legend   = '5 minutes';
        $stepRate = floor($deltaTime / $step);  // 1 point each 300 sec
    } else { // > 24h
        $stepRate = 288; // 288 points, not matter how many seconds
        $step     = floor($deltaTime / $stepRate);
        $legend   = round($step/60) . ' minutes';
    }
    //  generate a syntethic array of values
    for ( $a = 0; $a < $stepRate; ++$a) {
        $statTime = date("Y-m-d H:i:s",floor(($startFilterTime + ($step * $a))/$step)*$step);
        $statEvent_synthetic[$statTime]['block']   = 0;
        $statEvent_synthetic[$statTime]['allow']   = 0;
        $statEvent_synthetic[$statTime]['warning'] = 0;
    }
    // Query for events
    $selector = 'SELECT events.a_timestamp as a_timestamp, COUNT(events.h_action_status = 20 OR NULL) AS warning, COUNT(events.h_action_status < 10 OR NULL) AS block, COUNT(events.h_action_status >= 10 AND events.h_action_status < 20 OR NULL) AS allow FROM date_range, events ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }
    // SQL Query trailer
    $trailer = ' GROUP BY (UNIX_TIMESTAMP(events.a_timestamp)) DIV :step';
    $extraField = array('step' => $step);

    // Call superFilter to count filtered events
    $statsEvents = superFilter($selector, $trailer, $filterType, FALSE, $extraField);

    foreach($statsEvents as $key => $stat) {
        $timestampFloored  = floor(strtotime($stat['a_timestamp'])/$step)*$step;

        $statsEventsTime[date("Y-m-d H:i:s", $timestampFloored)]['block']   = $stat['block'];
        $statsEventsTime[date("Y-m-d H:i:s", $timestampFloored)]['allow']   = $stat['allow'];
        $statsEventsTime[date("Y-m-d H:i:s", $timestampFloored)]['warning'] = $stat['warning'];
    }

    $statEventsComplete = array_multimerge($statEvent_synthetic, $statsEventsTime);

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }
    return array($step, $labelStep, $legend, $statEventsComplete);
}

function statsTopRules()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    $filterType = 'filter';
    global $filterIndexHint;
    
    // Check if join table are needed
    if ((isset($_SESSION[$filterType]['ruleid']) AND !isset($_SESSION[$filterType]['Not_ruleid'])) OR isset($_SESSION[$filterType]['tag'])) {
        $selector = 'SELECT COUNT( events_messages.h_message_ruleId ) AS rule_count, events_messages.h_message_ruleId AS message_ruleId FROM date_range, events ';
        if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
            $selector = $selector . 'USE INDEX '.$filterIndexHint;
        }        
    } else {
        $selector = 'SELECT COUNT( events_messages.h_message_ruleId ) AS rule_count, events_messages.h_message_ruleId AS message_ruleId FROM date_range, events JOIN events_messages ON events.event_id = events_messages.event_id  ';
    }

    // SQL Query trailer
    $trailer = ' GROUP BY events_messages.h_message_ruleId ORDER BY rule_count DESC LIMIT 0 , 10';

    // Call superFilter to count filtered events
    $statsTopRules = superFilter($selector, $trailer, $filterType, FALSE);
    
    $ruleCount = count($statsTopRules);
   
    for ($a = 0; $a < $ruleCount; ++$a) {
        $ruleName = getRuleName($statsTopRules[$a]['message_ruleId']);
        $statsTopRules[$a]['message_ruleMsg'] = $ruleName['message_ruleMsg'];
    }
    
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }
    return $statsTopRules;
}


function statsTopSources()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    $filterType = 'filter';
    global $filterIndexHint;
    
    // Query for events
    $selector = 'SELECT COUNT( events.a_client_ip ) AS source_count, INET_NTOA(events.a_client_ip) AS client_ip FROM date_range, events ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }
    // SQL Query trailer
    $trailer = ' GROUP BY events.a_client_ip ORDER BY source_count DESC LIMIT 0 , 10';

    // Call superFilter to count filtered events
    $statsTopSources = superFilter($selector, $trailer, $filterType, FALSE, $extraField);

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $statsTopSources;
}

// Get the top web host (as in Host header)
function statsTopTargets()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    $filterType = 'filter';
    global $filterIndexHint;
    
    // Query for events
    $selector = 'SELECT COUNT( events.b_host ) AS host_count, events.b_host FROM date_range, events ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }
    // SQL Query trailer
    $trailer = ' GROUP BY events.b_host ORDER BY host_count DESC LIMIT 0 , 10';
    // Call superFilter to count filtered events
    $statsTopTargets = superFilter($selector, $trailer, $filterType, FALSE, $extraField);

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $statsTopTargets;
}


// statsTopStatus
function statsTopStatus()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    $filterType = 'filter';
    global $filterIndexHint;
    
    // Query for events
    $selector = 'SELECT COUNT( events.f_status ) AS status_count, events.f_status AS status, http_code.msg AS msg FROM date_range, events ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }    
    $selector = $selector . 'JOIN http_code ON http_code.code=events.f_status ';

    // SQL Query trailer
    $trailer = ' GROUP BY events.f_status ORDER BY status_count DESC LIMIT 0 , 10';
    $extraField = array('step' => $step);

    // Call superFilter to count filtered events
    $statsTopStatus = superFilter($selector, $trailer, $filterType, FALSE, $extraField);

    foreach ($statsTopStatus as $f_status) {
        $f_statusTotal = $f_statusTotal + $f_status['status_count'];
    }
    // make status less then 5% aggregated on 'others'
    $nextElement = count($statsTopStatus);
    foreach ($statsTopStatus as $key => $value) {
        $f_statusPercent = round($value['status_count'] * 100 / $f_statusTotal, 2);
        $statsTopStatus[$key]['status_percent'] = $f_statusPercent;
        $value['status_percent'] = $f_eventSensorPercent;

        if ($f_statusPercent < 10) {
            $others = $others + $value['status_count'];

            $statsTopStatus[$nextElement]['status_count'] = $statsTopStatus[$nextElement]['status_count'] + $value['status_count'];
            $statsTopStatus[$nextElement]['status'] = '';
            $statsTopStatus[$nextElement]['msg'] = 'Others';
            $statsTopStatus[$nextElement]['status_percent'] = $statsTopStatus[$nextElement]['status_percent'] + $value['status_percent'];

            unset($statsTopStatus[$key]);
        }
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $statsTopStatus;
}


// statsTopSeverity

function statsTopSeverity()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    $filterType = 'filter';
    global $filterIndexHint;
    
    $selector = 'SELECT COUNT( events.h_severity ) AS severity_count, events.h_severity AS severity FROM date_range, events ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }  
    $selector = $selector . ' JOIN http_code ON http_code.code=events.f_status ';

    // SQL Query trailer
    $trailer = ' GROUP BY events.h_severity ORDER BY severity_count DESC LIMIT 0 , 10';
    $extraField = array('step' => $step);

    // Call superFilter to count filtered events
    $statsTopSeverity = superFilter($selector, $trailer, $filterType, FALSE, $extraField);

    foreach ($statsTopSeverity as $h_severity) {
        $h_severityTotal = $h_severityTotal + $h_severity['severity_count'];
    }
    // make status less then 5% aggregated on 'others'
    $nextElement = count($statsTopSeverity);
    foreach ($statsTopSeverity as $key => $value) {
        $h_severityPercent = round($value['severity_count'] * 100 / $h_severityTotal, 2);
        $statsTopSeverity[$key]['severity_percent'] = $h_severityPercent;
        $value['severity_percent'] = $h_severityPercent;
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $statsTopSeverity;
}


function statsTopPath()
{
    global $DEBUG;
    global $filterIndexHint;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    $filterType = 'filter';
    global $filterIndexHint;
    
    // Query for events
    $selector = 'SELECT COUNT( events.b_path ) AS b_path_count, b_path FROM date_range, events ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }      

    // SQL Query trailer
    $trailer = ' GROUP BY events.b_path ORDER BY b_path_count DESC LIMIT 0 , 10';

    // Call superFilter to count filtered events
    $statsTopPath = superFilter($selector, $trailer, $filterType, FALSE, $extraField);

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $statsTopPath;
}


function statsTopCC()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    $filterType = 'filter';
    global $filterIndexHint;
    
    // Query for events
    $selector = 'SELECT COUNT( events.a_client_ip_cc ) AS client_cc_count, a_client_ip_cc AS client_cc FROM date_range, events ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }  
    // SQL Query trailer
    $trailer = ' GROUP BY events.a_client_ip_cc ORDER BY client_cc_count DESC LIMIT 0 , 10';

    // Call superFilter to count filtered events
    $statsTopSources = superFilter($selector, $trailer, $filterType, FALSE, $extraField);

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $statsTopSources;
}

function statsTopASN()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    $filterType = 'filter';
    global $filterIndexHint;
    
    // Query for events
    $selector = 'SELECT COUNT( events.a_client_ip_asn ) AS client_ASN_count, a_client_ip_asn AS client_ASN  FROM date_range, events  ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }  
    // SQL Query trailer
    $trailer = ' GROUP BY events.a_client_ip_asn ORDER BY client_ASN_count DESC LIMIT 0 , 10';

    // Call superFilter to count filtered events
    $statsTopSources = superFilter($selector, $trailer, $filterType, FALSE, $extraField);

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $statsTopSources;
}


function array_multimerge ($array1, $array2)
{
    if (is_array($array2) && count($array2)) {
        foreach ($array2 as $k => $v) {
            if (is_array($v) && count($v)) {
                $array1[$k] = array_multimerge($array1[$k], $v);
            } else {
                $array1[$k] = $v;
            }
        }
    }
    return $array1;
}

// checkUser: validate user against userbase or external userbase
function checkUser($username, $password)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    /* prepare statement using PDO*/
    global $dbconn;

    $sha1Password = sha1($password);

    $sqlcheckUser = 'SELECT `user_id`, `username`, `email` FROM users WHERE username = :username AND password = :password';
    if ($DEBUG) {
        $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlcheckUser;
    }
    try {
        $query_sth = $dbconn->prepare($sqlcheckUser);
        $query_sth->bindParam(":username", $username);
        $query_sth->bindParam(":password", $sha1Password);

        // Execute the query
        $query_sth->execute();
        $checkUser = $query_sth->fetchAll(PDO::FETCH_ASSOC);
        $userCount = count($checkUser);
        if ($userCount == 1) {
            $checkUser[0]['result'] = TRUE;
        } else {
            $checkUser[0]['result'] = FALSE;
        }
        if ($checkUser[0]['user_id'] == "1" AND $password == "admin") {
           $checkUser[0]['changePass'] = true;
        }
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }
    return $checkUser;
}

// getTagID Get Tag ID based on tag string
function getTagID($tag_string)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    /* prepare statement using PDO*/
    global $dbconn;
    global $APC_ON;
    global $CACHE_TIMEOUT;

    if ($APC_ON AND ($tag_id = apc_fetch('tag_'.sha1($tag_string)))) {
        $tag_id = $tag_id;
    } else {
        $sqlGetTagId = '
                        SELECT `tag_id`, `tag_name` FROM `tags`
                        UNION
                        SELECT `tag_id`, `tag_name` FROM `tags_custom`
                            ';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlGetTagId;
        }
        try {
            $query_sth = $dbconn->prepare($sqlGetTagId);

            // Execute the query
            $query_sth->execute();
            $GetTagId = $query_sth->fetchAll(PDO::FETCH_ASSOC);
            $tagCount = $query_sth->rowCount();
            $queryStatus = $query_sth->errorCode();
            if ($queryStatus != 0 OR $tagCount == 0) {
                header("HTTP/1.1 500 Internal Server Error");
                header("Status: 500");
                global $MLOG2WAFFLE_DEBUG;
                if ($MLOG2WAFFLE_DEBUG) {
                    $arr = $query_sth->errorInfo();
                    print_r($arr);
                }
                exit();
            } else {
                foreach ($GetTagId as $Tag) {
                    if ($tag_string == $Tag['tag_name']) {
                        $tag_id = $Tag['tag_id'];
                    }
                    if ($APC_ON) {
                        apc_store('tag_'.sha1($Tag['tag_name']), $Tag['tag_id'], $CACHE_TIMEOUT);
                    }
                }
                // if no tag found, a custom tag will be created on tags_custom table
                if (!isset($tag_id) OR $tag_id == "") {

                    $sqlNewTagId = 'INSERT INTO `tags_custom` (`tag_name`, `tag_title`) VALUES(:TagName, :TagTitle)';
                    if ($DEBUG) {
                            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlNewTagId;
                    }
                    try {
                        $queryNewTag_sth = $dbconn->prepare($sqlNewTagId);
                        $queryNewTag_sth->bindParam(":TagName", $tag_string);
                        $queryNewTag_sth->bindParam(":TagTitle", $tag_string);
                        $queryNewTag_sth->execute();
                        $tag_id = $dbconn->lastInsertId();
                        $insertStatus = $queryNewTag_sth->errorCode();
                        $arr = $queryNewTag_sth->errorInfo();
                        if ($APC_ON) {
                            apc_store('tag_'.sha1($tag_name), $tag_id, $CACHE_TIMEOUT);
                        }
                    } catch (PDOException $e) {
                       header("HTTP/1.1 500 Internal Server Error");
                       header("Status: 500");
                       print "HTTP/1.1 500 Internal Server Error \n";
                       global $MLOG2WAFFLE_DEBUG;
                       if ($MLOG2WAFFLE_DEBUG OR $DEBUG) {
                           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
                           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
                       }
                       exit();
                    }
                }
            }
        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           global $MLOG2WAFFLE_DEBUG;
           if ($MLOG2WAFFLE_DEBUG OR $DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $tag_id;
}


// getTagName Get Tag Name based on tag id
function getTagName($tag_id)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    /* prepare statement using PDO*/
    global $dbconn;
    global $APC_ON;
    global $CACHE_TIMEOUT;

    if ($APC_ON AND ($tag_name = apc_fetch('tag_'.$tag_id))) {
        $tag_name = $tag_name;
    } else {
        $sqlGetTagName = '
                        SELECT `tag_id`, `tag_name` FROM `tags`
                        UNION
                        SELECT `tag_id`, `tag_name` FROM `tags_custom`
                        ';
        if ($DEBUG) {
                $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlGetTagName;
        }
        try {
            $query_sth = $dbconn->prepare($sqlGetTagName);
            $query_sth->bindParam(":tag_id", $tag_id);
            $query_sth->bindParam(":tag_idCustom", $tag_id);
            // Execute the query
            $query_sth->execute();
            $GetTagName = $query_sth->fetchAll(PDO::FETCH_ASSOC);
            foreach ($GetTagName as $Tag) {
                if ($tag_id == $Tag['tag_id']) {
                    $tag_name = $Tag['tag_name'];
                }
                if ($APC_ON) {
                //    apc_store('tag_'.$tag_id, $Tag['tag_name'], $CACHE_TIMEOUT);
                print "";
                }
            }
        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $tag_name;
}

// getTags Fetch all Tag
function getTags()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    /* prepare statement using PDO*/
    global $dbconn;
    global $APC_ON;
    global $CACHE_TIMEOUT;

    if ($APC_ON AND ($tags = apc_fetch('tags'))) {
        $tags = $tags;
    } else {
        $sqlGetTags = ' SELECT `tag_id`, `tag_name`  FROM `tags`
                        UNION
                        SELECT `tag_id`, `tag_name`  FROM `tags_custom`
                        ORDER BY tag_name';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlGetTags;
        }
        try {
            $query_sth = $dbconn->prepare($sqlGetTags);

            // Execute the query
            $query_sth->execute();
            $tagsList = $query_sth->fetchAll(PDO::FETCH_ASSOC);
            $queryStatus = $query_sth->errorCode();
            /*
            if ($APC_ON) {
           //     apc_store('tags', $tagsList, $CACHE_TIMEOUT);
                print "";
            }
            */

        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $tagsList;
}


// process all filter operation, receive a sql selector and trailer, the type of filter (filter, delete, fp), if should count events, and some extra fields
function superFilter($selector, $trailer, $filterType, $count = FALSE, $extraField = array())
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;

    // Start filter composition
    $sql = $selector;

    if (isset($_SESSION[$filterType]['StDate']) AND isset($_SESSION[$filterType]['StTime']) AND isset($_SESSION[$filterType]['FnDate']) AND isset($_SESSION[$filterType]['FnTime'])) {
       
        global $tempDate;
        if (!$tempDate) {
            // Create a temp table with date range
            $sqlCreateDateRangeTempTable='CREATE TEMPORARY TABLE IF NOT EXISTS `date_range` (`a_date` DATE NOT NULL)';

            try {
                $sthTempTable = $dbconn->prepare($sqlCreateDateRangeTempTable);
                $sthTempTable->execute();

                $insertTempDateRange = 'INSERT INTO `date_range` 
                (`a_date`) SELECT DISTINCT a_date FROM events WHERE a_date BETWEEN :startDate AND :stopDate ORDER BY a_date DESC';
                $st_dateRange = $dbconn->prepare($insertTempDateRange);

                try {
                    $st_dateRange->bindParam(":startDate", $_SESSION[$filterType]['StDate']);
                    $st_dateRange->bindParam(":stopDate",  $_SESSION[$filterType]['FnDate']);
                    $st_dateRange->execute();
                } catch (PDOException $e) {
                    header("HTTP/1.1 500 Internal Server Error");
                    header("Status: 500");
                    print "HTTP/1.1 500 Internal Server Error \n";
                    if ($DEBUG) {
                        print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
                        print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
                    }
                    exit();
                }
                $tempDate = true;
            } catch (PDOException $e) {
                header("HTTP/1.1 500 Internal Server Error");
                header("Status: 500");
                print "HTTP/1.1 500 Internal Server Error \n";
                if ($DEBUG) {
                    print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
                    print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
                }
                exit();
            }
        }
        // workaround for mysql limitation of only one access to temp table per query;
        global $tempDatetag;
        if (!$tempDatetag) {
            // Create a temp table with date range
            $sqlCreateDateRangeTagTempTable='CREATE TEMPORARY TABLE IF NOT EXISTS `date_rangeMsg` (`a_date` DATE NOT NULL)';

            try {
                $sthTempTableTag = $dbconn->prepare($sqlCreateDateRangeTagTempTable);
                $sthTempTableTag->execute();

                $insertTempDateRangeTag = 'INSERT INTO `date_rangeMsg` (`a_date`) SELECT DISTINCT a_date FROM events WHERE a_date BETWEEN :startDate AND :stopDate ORDER BY a_date DESC';
                $st_dateRangeTag = $dbconn->prepare($insertTempDateRangeTag);

                try {
                    $st_dateRangeTag->bindParam(":startDate", $_SESSION[$filterType]['StDate']);
                    $st_dateRangeTag->bindParam(":stopDate",  $_SESSION[$filterType]['FnDate']);
                    $st_dateRangeTag->execute();
                } catch (PDOException $e) {
                    header("HTTP/1.1 500 Internal Server Error");
                    header("Status: 500");
                    print "HTTP/1.1 500 Internal Server Error \n";
                    if ($DEBUG) {
                        print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
                        print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
                    }
                    exit();
                }
                $tempDatetag = true;
            } catch (PDOException $e) {
               header("HTTP/1.1 500 Internal Server Error");
               header("Status: 500");
               print "HTTP/1.1 500 Internal Server Error \n";
               if ($DEBUG) {
                   print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
                   print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
               }
               exit();
            }
        }
    
        $startdate = $_SESSION[$filterType]['StDate'] . " " . $_SESSION[$filterType]['StTime'];
        $enddate   = $_SESSION[$filterType]['FnDate'] . " " . $_SESSION[$filterType]['FnTime'];
    } else {
        return;
    }

    // Check if join table are needed
    if (isset($_SESSION[$filterType]['ruleid']) AND !isset($_SESSION[$filterType]['tag'])) {
        if (!isset($_SESSION[$filterType]['Not_ruleid'])) {
            $sql = $sql . ' JOIN events_messages ON events.event_id = events_messages.event_id ';
        }
    }

    if (isset($_SESSION[$filterType]['tag']) AND !isset($_SESSION[$filterType]['ruleid'])) {
        if (!isset($_SESSION[$filterType]['Not_tag'])) {
            $sql = $sql . ' JOIN events_messages ON events.event_id = events_messages.event_id JOIN events_messages_tag ON events_messages_tag.msg_id = events_messages.msg_id ';
        }
    }

    if (isset($_SESSION[$filterType]['tag']) AND isset($_SESSION[$filterType]['ruleid'])) {
        if (isset($_SESSION[$filterType]['Not_tag'])) {
            $sql = $sql . ' JOIN events_messages ON events.event_id = events_messages.event_id ';        
        } else {
            $sql = $sql . ' JOIN events_messages ON events.event_id = events_messages.event_id JOIN events_messages_tag ON events_messages_tag.msg_id = events_messages.msg_id ';           
        }
    }
    
    $sql = $sql . ' WHERE 1 ';


    // Start conditions 
    // Define the time range
     $sql = $sql . ' AND (events.a_date = date_range.a_date) ';
    if ($_SESSION[$filterType]['fullDayFilter'] != true ) {
        $sql = $sql . ' AND (events.a_timestamp BETWEEN :startdate AND :enddate) ';
    }

    // looking for ruleId AND/OR tag
    
    if (isset($_SESSION[$filterType]['ruleid']) AND !isset($_SESSION[$filterType]['tag'])) {
        if (isset($_SESSION[$filterType]['Not_ruleid'])) {
            $sql = $sql . ' AND ( NOT EXISTS (SELECT events_messages.event_id FROM events_messages WHERE events.event_id = events_messages.event_id AND events_messages.h_message_ruleId = :ruleid ))'; 
        } else {
            $sql = $sql . ' AND (events_messages.h_message_ruleId = :ruleid )';
        }
    }

    if (isset($_SESSION[$filterType]['tag']) AND !isset($_SESSION[$filterType]['ruleid'])) {
        if (isset($_SESSION[$filterType]['Not_tag'])) {
            $sql = $sql . ' AND NOT EXISTS (SELECT events_messages.event_id FROM events_messages JOIN events_messages_tag ON events_messages.msg_id = events_messages_tag.msg_id WHERE events.event_id = events_messages.event_id AND  events_messages_tag.h_message_tag = :tagid )'; 
        } else {
            $sql = $sql . ' AND (events_messages_tag.h_message_tag = :tagid )';
        }
    }

    if (isset($_SESSION[$filterType]['tag']) AND isset($_SESSION[$filterType]['ruleid'])) {
        if (isset($_SESSION[$filterType]['Not_ruleid']) AND isset($_SESSION[$filterType]['Not_tag'])) {
            $sql = $sql . ' AND (NOT EXISTS (SELECT events_messages.event_id FROM events_messages JOIN events_messages_tag ON events_messages.msg_id = events_messages_tag.msg_id WHERE events_messages.event_id = events.event_id AND events_messages.h_message_ruleId = :ruleid AND events_messages_tag.h_message_tag = :tagid ))'; 
        } elseif (isset($_SESSION[$filterType]['Not_ruleid'])) {
            $sql = $sql . ' AND (events_messages_tag.h_message_tag = :tagid ) AND (NOT EXISTS (SELECT events_messages.event_id FROM events_messages WHERE events_messages.event_id = events.event_id AND events_messages.h_message_ruleId = :ruleid ))';             
        } elseif (isset($_SESSION[$filterType]['Not_tag'])) {
            $sql = $sql . ' AND (events_messages.h_message_ruleId = :ruleid ) AND (NOT EXISTS (SELECT events_messages.event_id FROM events_messages JOIN events_messages_tag ON events_messages.msg_id = events_messages_tag.msg_id WHERE events_messages.event_id = events.event_id AND events_messages_tag.h_message_tag = :tagid ))';             
        } else {
            $sql = $sql . ' AND (events_messages.h_message_ruleId = :ruleid ) AND (events_messages_tag.h_message_tag = :tagid )';
        }
    }


    if (isset($_SESSION[$filterType]['esrc'])) {
        $client_ip = networkRange($_SESSION[$filterType]['esrc']);
        if ($client_ip['cidr'] < 32) {
            if ($_SESSION[$filterType]['Not_esrc']) {
                $sql = $sql . ' AND (events.a_client_ip < :sourceIPStart OR events.a_client_ip > :sourceIPEnd) ';
            } else {
                $sql = $sql . ' AND (events.a_client_ip >= :sourceIPStart AND events.a_client_ip <= :sourceIPEnd) ';
            }
        } else {
            if ($_SESSION[$filterType]['Not_esrc']) {
                $sql = $sql . ' AND (events.a_client_ip != :sourceIP) ';
            } else {
                $sql = $sql . ' AND (events.a_client_ip = :sourceIP) ';
            }
        }
    }

    // Looking for IP client Country Code
    if (isset($_SESSION[$filterType]['ipcc'])) {
        if (isset($_SESSION[$filterType]['Not_ipcc'])) {
            $sql = $sql . ' AND (events.a_client_ip_cc != :sourceIPCC) ';
        } else {
            $sql = $sql . ' AND (events.a_client_ip_cc = :sourceIPCC) ';
        }
    }
    // Looking for IP client AS Number
    if (isset($_SESSION[$filterType]['ipasn'])) {
        if (isset($_SESSION[$filterType]['Not_ipasn'])) {
            $sql = $sql . ' AND (events.a_client_ip_asn != :sourceIPASN) ';
        } else {
            $sql = $sql . ' AND (events.a_client_ip_asn = :sourceIPASN) ';
        }
    }
    // Looking for Web Hostname
    if (isset($_SESSION[$filterType]['web_Hostname'])) {
        if (isset($_SESSION[$filterType]['Not_web_Hostname'])) {
            $sql = $sql . ' AND (events.b_host != :webhostname) ';
        } else {
            $sql = $sql . ' AND (events.b_host = :webhostname) ';
        }
    }
    // Looking for Web Hostname
    if (isset($_SESSION[$filterType]['method'])) {
        if (isset($_SESSION[$filterType]['Not_method'])) {
            $sql = $sql . ' AND (events.b_method != :method) ';
        } else {
            $sql = $sql . ' AND (events.b_method = :method) ';
        }
    }

    // Looking for Web App Info
    if (isset($_SESSION[$filterType]['webApp'])) {
        if (isset($_SESSION[$filterType]['Not_webApp'])) {
            $sql = $sql . ' AND (events.h_wa_info_app_id != :webApp) ';
        } else {
            $sql = $sql . ' AND (events.h_wa_info_app_id = :webApp) ';
        }
    }
    // Looking for a specific sensor
    if (isset($_SESSION[$filterType]['src_sensor'])) {
        if ($_SESSION[$filterType]['Not_src_sensor']) {
            $sql = $sql . ' AND (events.sensor_id != :sensorid) ';
        } else {
            $sql = $sql . ' AND (events.sensor_id = :sensorid) ';
        }
    }
    // Looking for http status code
    if (isset($_SESSION[$filterType]['http_Status'])) {
        if ($_SESSION[$filterType]['Not_http_Status']) {
            $sql = $sql . ' AND (events.f_status != :httpstatus) ';
        } else {
            $sql = $sql . ' AND (events.f_status = :httpstatus) ';
        }
    }

    // Looking for action code
    if (isset($_SESSION[$filterType]['actionstatus'])) {
        if ($_SESSION[$filterType]['actionstatus'] == 'block') {
            if ($_SESSION[$filterType]['Not_actionstatus']) {
                $sql = $sql . ' AND (events.h_action_status NOT IN (0,1,2,3,4,5,6,7,8,9)) ';
            } else {
                $sql = $sql . ' AND (events.h_action_status IN (0,1,2,3,4,5,6,7,8,9)) ';
            }
        } elseif ($_SESSION[$filterType]['actionstatus'] == 'allow') {
            if ($_SESSION[$filterType]['Not_actionstatus']) {
                $sql = $sql . ' AND (events.h_action_status NOT IN (10,11,12,13,14,15,16,17,18,19)) ';
            } else {
                $sql = $sql . ' AND (events.h_action_status IN (10,11,12,13,14,15,16,17,18,19)) ';
            }
        } elseif ($_SESSION[$filterType]['actionstatus'] == 'warning') {
            if ($_SESSION[$filterType]['Not_actionstatus']) {
                $sql = $sql . ' AND (events.h_action_status != 20) ';
            } else {
                $sql = $sql . ' AND (events.h_action_status = 20) ';
            }
        } else {
            if ($_SESSION[$filterType]['Not_actionstatus']) {
                $sql = $sql . ' AND (events.h_action_status != :actionstatus) ';
            } else {
                $sql = $sql . ' AND (events.h_action_status = :actionstatus) ';
            }
        }
    }

    // Looking for severity code
    if (isset($_SESSION[$filterType]['severity'])) {
        if ($_SESSION[$filterType]['Not_severity']) {
            $sql = $sql . ' AND (events.h_severity != :severity) ';
        } else {
            $sql = $sql . ' AND (events.h_severity = :severity) ';
        }
    }

    // Looking for Total Score
    if (isset($_SESSION[$filterType]['score'])) {
        if ($_SESSION[$filterType]['score_interval'] == "le") {
            $sql = $sql . ' AND (events.h_score_total <= :score) ';
        } else {
            $sql = $sql . ' AND (events.h_score_total >= :score) ';
        }
    }
    // Looking for Total SQLi
    if (isset($_SESSION[$filterType]['scoreSqli'])) {
        if ($_SESSION[$filterType]['scoreSqli_interval'] == "le") {
            $sql = $sql . ' AND (events.h_score_SQLi <= :scoreSqli) ';
        } else {
            $sql = $sql . ' AND (events.h_score_SQLi >= :scoreSqli) ';
        }
    }
    // Looking for Total XSS
    if (isset($_SESSION[$filterType]['scoreXss'])) {
        if ($_SESSION[$filterType]['scoreXss_interval'] == "le") {
            $sql = $sql . ' AND (events.h_score_XSS <= :scoreXss) ';
        } else {
            $sql = $sql . ' AND (events.h_score_XSS >= :scoreXss) ';
        }
    }

    // looking for Path
    if (isset($_SESSION[$filterType]['path'])) {
        if ($_SESSION[$filterType]['Not_path']) {
            if ($_SESSION[$filterType]['path_wc']) {
                $sql = $sql . ' AND (events.b_path NOT LIKE :path) ';
            } else {
                $sql = $sql . ' AND (events.b_path != :path) ';
            }
        } else {
            if ($_SESSION[$filterType]['path_wc']) {
                $sql = $sql . ' AND (events.b_path LIKE :path) ';
            } else {
                $sql = $sql . ' AND (events.b_path = :path) ';
            }
        }
    }
    // looking for User ID
    if (isset($_SESSION[$filterType]['userId'])) {
        if ($_SESSION[$filterType]['Not_userId']) {
            $sql = $sql . ' AND (events.h_wa_info_user_id NOT LIKE :userId) ';
        } else {
            $sql = $sql . ' AND (events.h_wa_info_user_id LIKE :userId) ';
        }
    }
    // looking for Engine Mode
    if (isset($_SESSION[$filterType]['engineMode'])) {
        if ($_SESSION[$filterType]['Not_engineMode']) {
            $sql = $sql . ' AND (events.h_engine_mode != :engineMode) ';
        } else {
            $sql = $sql . ' AND (events.h_engine_mode = :engineMode) ';
        }
    }

    // looking for Marked as False Positive
    if (isset($_SESSION[$filterType]['falsePositive'])) {
        if ($_SESSION[$filterType]['falsePositive'] == FALSE) {
            $sql = $sql . ' AND (events.false_positive = FALSE) ';
        } elseif ($_SESSION[$filterType]['falsePositive'] == TRUE) {
            $sql = $sql . ' AND (events.false_positive = TRUE) ';
        }
    }

   // looking for Preserved
    if (isset($_SESSION[$filterType]['preserved'])) {
        if ($_SESSION[$filterType]['preserved'] == FALSE) {
            $sql = $sql . ' AND (events.preserve = FALSE) ';
        } elseif ($_SESSION[$filterType]['preserved'] == TRUE) {
            $sql = $sql . ' AND (events.preserve = TRUE) ';
        }
    }

    // Looking for duration
    if (isset($_SESSION[$filterType]['duration'])) {
        if ($_SESSION[$filterType]['duration_interval'] == "le") {
            $sql = $sql . ' AND (events.h_stopwatch2_duration <= :duration) ';
        } else {
            $sql = $sql . ' AND (events.h_stopwatch2_duration >= :duration) ';
        }
    }
    // Looking for combined
    if (isset($_SESSION[$filterType]['combined'])) {
        if ($_SESSION[$filterType]['combined_interval'] == "le") {
            $sql = $sql . ' AND (events.h_stopwatch2_combined <= :combined) ';
        } else {
            $sql = $sql . ' AND (events.h_stopwatch2_combined >= :combined) ';
        }
    }
    // Looking for p1
    if (isset($_SESSION[$filterType]['p1'])) {
        if ($_SESSION[$filterType]['p1_interval'] == "le") {
            $sql = $sql . ' AND (events.h_stopwatch2_p1 <= :p1) ';
        } else {
            $sql = $sql . ' AND (events.h_stopwatch2_p1 >= :p1) ';
        }
    }
    // Looking for p2
    if (isset($_SESSION[$filterType]['p2'])) {
        if ($_SESSION[$filterType]['p2_interval'] == "le") {
            $sql = $sql . ' AND (events.h_stopwatch2_p2 <= :p2) ';
        } else {
            $sql = $sql . ' AND (events.h_stopwatch2_p2 >= :p2) ';
        }
    }
    // Looking for p3
    if (isset($_SESSION[$filterType]['p3'])) {
        if ($_SESSION[$filterType]['p3_interval'] == "le") {
            $sql = $sql . ' AND (events.h_stopwatch2_p3 <= :p3) ';
        } else {
            $sql = $sql . ' AND (events.h_stopwatch2_p3 >= :p3) ';
        }
    }
    // Looking for p4
    if (isset($_SESSION[$filterType]['p4'])) {
        if ($_SESSION[$filterType]['p4_interval'] == "le") {
            $sql = $sql . ' AND (events.h_stopwatch2_p4 <= :p4) ';
        } else {
            $sql = $sql . ' AND (events.h_stopwatch2_p4 >= :p4) ';
        }
    }
    // Looking for p5
    if (isset($_SESSION[$filterType]['p5'])) {
        if ($_SESSION[$filterType]['p5_interval'] == "le") {
            $sql = $sql . ' AND (events.h_stopwatch2_p5 <= :p5) ';
        } else {
            $sql = $sql . ' AND (events.h_stopwatch2_p5 >= :p5) ';
        }
    }
    // Looking for sr
    if (isset($_SESSION[$filterType]['sr'])) {
        if ($_SESSION[$filterType]['sr_interval'] == "le") {
            $sql = $sql . ' AND (events.h_stopwatch2_sr <= :sr) ';
        } else {
            $sql = $sql . ' AND (events.h_stopwatch2_sr >= :sr) ';
        }
    }
    // Looking for sw
    if (isset($_SESSION[$filterType]['sw'])) {
        if ($_SESSION[$filterType]['sw_interval'] == "le") {
            $sql = $sql . ' AND (events.h_stopwatch2_sw <= :sw) ';
        } else {
            $sql = $sql . ' AND (events.h_stopwatch2_sw >= :sw) ';
        }
    }
    // Looking for l
    if (isset($_SESSION[$filterType]['log'])) {
        if ($_SESSION[$filterType]['log_interval'] == "le") {
            $sql = $sql . ' AND (events.h_stopwatch2_l <= :log) ';
        } else {
            $sql = $sql . ' AND (events.h_stopwatch2_l >= :log) ';
        }
    }
    // Looking for gc
    if (isset($_SESSION[$filterType]['gc'])) {
        if ($_SESSION[$filterType]['gc_interval'] == "le") {
            $sql = $sql . ' AND (events.h_stopwatch2_gc <= :gc) ';
        } else {
            $sql = $sql . ' AND (events.h_stopwatch2_gc >= :gc) ';
        }
    }

    // looking for Unique ID
    if (isset($_SESSION[$filterType]['uniqId'])) {
        $sql = $sql . ' AND (events.a_uniqid LIKE :uniqId) ';
    }

    // Concatenate selector and trailer
    $sql = $sql . $trailer;

    //print "$sql <br />";

    if ($DEBUG) {
        $debugInfo[__FUNCTION__][$debugCount]['query'] = $sql;
    }
    try {
        $sth = $dbconn->prepare($sql);

        if (isset($_SESSION[$filterType]['esrc'])) {
            if ($client_ip['cidr'] < 32) {
                $sth->bindParam(":sourceIPStart", $client_ip['networklong']);
                $sth->bindParam(":sourceIPEnd", $client_ip['broadcastlong']);
            } else {
                $sth->bindParam(":sourceIP", $client_ip['iplong']);
            }
        }
        if (isset($_SESSION[$filterType]['ipcc'])) {
            $sth->bindParam(":sourceIPCC", $_SESSION[$filterType]['ipcc']);
        }
        if (isset($_SESSION[$filterType]['ipasn'])) {
            $sth->bindParam(":sourceIPASN", $_SESSION[$filterType]['ipasn']);
        }
        if (isset($_SESSION[$filterType]['ruleid'])) {
            $sth->bindParam(":ruleid", $_SESSION[$filterType]['ruleid']);
        }
        if (isset($_SESSION[$filterType]['path'])) {
            if ($_SESSION[$filterType]['path_wc']) {
                $pathLike = $_SESSION[$filterType]['path'].'%';
            } else {
                $pathLike = $_SESSION[$filterType]['path'];
            }
            $sth->bindParam(":path", $pathLike);
        }
        if (isset($_SESSION[$filterType]['web_Hostname'])) {
            $sth->bindParam(":webhostname", $_SESSION[$filterType]['web_Hostname']);
        }
        if (isset($_SESSION[$filterType]['webApp'])) {
            $sth->bindParam(":webApp", $_SESSION[$filterType]['webApp']);
        }
        if (isset($_SESSION[$filterType]['method'])) {
            $sth->bindParam(":method", $_SESSION[$filterType]['method']);
        }
        if (isset($_SESSION[$filterType]['src_sensor'])) {
            $sth->bindParam(":sensorid", $_SESSION[$filterType]['src_sensor']);
        }
        if (isset($_SESSION[$filterType]['http_Status'])) {
            $sth->bindParam(":httpstatus", $_SESSION[$filterType]['http_Status']);
        }
        if (isset($_SESSION[$filterType]['actionstatus']) AND preg_match('/^\d{1,2}$/', $_SESSION[$filterType]['actionstatus'])) {
            $sth->bindParam(":actionstatus", $_SESSION[$filterType]['actionstatus']);
        }
        if (isset($_SESSION[$filterType]['severity'])) {
            $sth->bindParam(":severity", $_SESSION[$filterType]['severity']);
        }
        if (isset($_SESSION[$filterType]['score'])) {
            $sth->bindParam(":score", $_SESSION[$filterType]['score']);
        }
        if (isset($_SESSION[$filterType]['scoreSqli'])) {
            $sth->bindParam(":scoreSqli", $_SESSION[$filterType]['scoreSqli']);
        }
        if (isset($_SESSION[$filterType]['scoreXss'])) {
            $sth->bindParam(":scoreXss", $_SESSION[$filterType]['scoreXss']);
        }
        if (isset($_SESSION[$filterType]['userId'])) {
            $sth->bindParam(":userId", $_SESSION[$filterType]['userId']);
        }
        if (isset($_SESSION[$filterType]['engineMode'])) {
            $sth->bindParam(":engineMode", $_SESSION[$filterType]['engineMode']);
        }
        global $timingScale;
        if (isset($_SESSION[$filterType]['duration'])) {
            $duration = ($_SESSION[$filterType]['duration'] * 
            $timingScale);
            $sth->bindParam(":duration", $duration);
        }
        if (isset($_SESSION[$filterType]['combined'])) {
            $combined = $_SESSION[$filterType]['combined'] * $timingScale;
            $sth->bindParam(":combined", $combined);
        }
        if (isset($_SESSION[$filterType]['p1'])) {
            $p1 = $_SESSION[$filterType]['p1'] * $timingScale;
            $sth->bindParam(":p1", $p1);
        }
        if (isset($_SESSION[$filterType]['p2'])) {
            $p2 = $_SESSION[$filterType]['p2'] * $timingScale;
            $sth->bindParam(":p2", $p2);
        }
        if (isset($_SESSION[$filterType]['p3'])) {
            $p3 = $_SESSION[$filterType]['p3'] * $timingScale;
            $sth->bindParam(":p3", $p3);
        }
        if (isset($_SESSION[$filterType]['p4'])) {
            $p4 = $_SESSION[$filterType]['p4'] * $timingScale;
            $sth->bindParam(":p4", $p4);
        }
        if (isset($_SESSION[$filterType]['p5'])) {
            $p5 = $_SESSION[$filterType]['p5'] * $timingScale;
            $sth->bindParam(":p5", $p5);
        }
        if (isset($_SESSION[$filterType]['sr'])) {
            $sr = $_SESSION[$filterType]['sr'] * $timingScale;
            $sth->bindParam(":sr", $sr);
        }
        if (isset($_SESSION[$filterType]['sw'])) {
            $sw = $_SESSION[$filterType]['sw'] * $timingScale;
            $sth->bindParam(":sw", $sw);
        }
        if (isset($_SESSION[$filterType]['log'])) {
            $log = $_SESSION[$filterType]['log'] * $timingScale;
            $sth->bindParam(":log", $log);
        }
        if (isset($_SESSION[$filterType]['gc'])) {
            $gc = $_SESSION[$filterType]['gc'] * $timingScale;
            $sth->bindParam(":gc", $gc);
        }
        if (isset($_SESSION[$filterType]['uniqId'])) {
            $sth->bindParam(":uniqId", $_SESSION[$filterType]['uniqId']);
        }
        // Date and time processing
        if ($_SESSION[$filterType]['fullDayFilter'] == false) {
            if (isset($_SESSION[$filterType]['StDate']) AND isset($_SESSION[$filterType]['StTime']) AND isset($_SESSION[$filterType]['FnDate']) AND isset($_SESSION[$filterType]['FnTime'])) {

                $sth->bindParam(":startdate", $startdate);
                $sth->bindParam(":enddate", $enddate);

                if (isset($_SESSION[$filterType]['ruleid'])) {
                //    $sth->bindParam(":startdateMsg", $startdate);
                 //   $sth->bindParam(":enddateMsg", $enddate);
                }
                if (isset($_SESSION[$filterType]['tag'])) {
                    $sth->bindParam(":startdateMsgTag", $startdate);
                    $sth->bindParam(":enddateMsgTag", $enddate);
                    $sth->bindParam(":tagid", $_SESSION[$filterType]['tag']);
                }
            }
        } else {
           if (isset($_SESSION[$filterType]['tag'])) {
                $sth->bindParam(":tagid", $_SESSION[$filterType]['tag']);
            }
        }

        // Use a variable set of fields
        if (isset($extraField['evendtid'])) {
            $sth->bindParam(":evendtid", $extraField['evendtid']);
        }
        if (isset($extraField['step'])) {
            $sth->bindParam(":step", $extraField['step'], PDO::PARAM_INT);
        }

        // Execute the query
        $sth->execute();
        $sth->bindColumn(1, $name);
        if ($count == TRUE) {
            $result = $sth->rowCount();
        } else {
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $result;
}


function eventFilter($offset, $maxnumber, $eventCount)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    global $filterIndexHint;
    
    /* prepare statement using PDO*/
    global $dbconn;
    $filterType = 'filter';

    if ($eventCount == 0) {
        $totalevents = eventFilterCount($filterType);
    } else {
        $totalevents = $eventCount;
    }


    $sqlCreateTempTable='CREATE TEMPORARY TABLE IF NOT EXISTS `list_events` (`event_id` int(10) unsigned NOT NULL)';

    try {
        $sthTempTable = $dbconn->prepare($sqlCreateTempTable);

        // Execute the query
        $sthTempTable->execute();
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    // Query for events
    $selector = 'INSERT INTO list_events(event_id) SELECT DISTINCT events.event_id FROM date_range, events ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }
    // SQL Query trailer
    $trailer = ' ORDER BY events.a_timestamp DESC LIMIT '.(($offset-1)*$maxnumber).", $maxnumber";

    // Call superFilter to filter and get the events
    $eventCount = superFilter($selector, $trailer, $filterType, TRUE);
    if ($eventCount > 0) {
        $sqlFetchEvents = 'SELECT events.event_id, events.sensor_id, events.a_timestamp, events.a_uniqid, INET_NTOA(events.a_client_ip) AS a_client_ip,  events.a_client_port, INET_NTOA(events.a_server_ip) AS a_server_ip, events.a_server_port,  events.b_method, events.b_path, events.b_path_parameter, events.b_protocol, events.b_host, events.f_protocol, events.f_status, events.f_msg, events.f_content_length, events.f_connection, events.f_content_type, events.h_severity, events.h_Interception_phase, events.h_action_status, events.h_action_status_msg FROM list_events, events WHERE events.event_id = list_events.event_id';

        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlFetchEvents;
        }

        $sthFechEvent = $dbconn->prepare($sqlFetchEvents);

        try {
            $sthFechEvent->execute();
            $event_list = $sthFechEvent->fetchAll(PDO::FETCH_ASSOC);
            // Execute the query

        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "<br>\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "<br>\n";
           }
           exit();
        }

        $event_loop = 0;

        // Get the events messages
        $sql = 'SELECT DISTINCT h_message_ruleId, rule_message.message_ruleMsg AS h_message_ruleMsg, h_message_ruleData, h_message_ruleSeverity, h_message_action FROM events_messages JOIN rule_message ON events_messages.h_message_ruleId = rule_message.message_ruleid WHERE events_messages.event_id = :ruleid ORDER BY h_message_ruleSeverity ASC, h_message_ruleId ASC';

        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sql;
        }
        $st_msg = $dbconn->prepare($sql);

        foreach ($event_list as $event_row) {
            try {
                $eventToFetch = $event_row['event_id'];
                $st_msg->bindParam(":ruleid", $eventToFetch);
                $st_msg->execute();

                $msgs_event = $st_msg->fetchAll(PDO::FETCH_ASSOC);
                $msg_loop   = 0;

                foreach ($msgs_event as $msg) {
                    $event_list[$event_loop]['h_message_ruleId'][$msg_loop]       = $msg['h_message_ruleId'];
                    $event_list[$event_loop]['h_message_ruleMsg'][$msg_loop]      = $msg['h_message_ruleMsg'];
                    $event_list[$event_loop]['h_message_ruleData'][$msg_loop]     = $msg['h_message_ruleData'];
                    $event_list[$event_loop]['h_message_ruleSeverity'][$msg_loop] = $msg['h_message_ruleSeverity'];
                    $event_list[$event_loop]['h_message_action'][$msg_loop]       = $msg['h_message_action'];
                    ++$msg_loop;
                }
            } catch (PDOException $e) {
               header("HTTP/1.1 500 Internal Server Error");
               header("Status: 500");
               print "HTTP/1.1 500 Internal Server Error \n";
               if ($DEBUG) {
                   print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
                   print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
               }
               exit();
            }
            $event_loop++;
        }
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return array($event_list, $totalevents, $eventCount, $offset);
}

function eventFilterCount($filterType = 'filter')
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    global $filterIndexHint;
    
    // Query for events
    if ((isset($_SESSION[$filterType]['ruleid']) AND !isset($_SESSION[$filterType]['Not_ruleid'])) OR isset($_SESSION[$filterType]['tag'])) {
        $selector = 'SELECT count(distinct events.event_id) as eventsCount FROM date_range, events ';
    } else {
        $selector = 'SELECT count(events.event_id) as eventsCount FROM date_range, events ';
    }
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }
    $numargs = func_num_args();
    if ( $numargs == 2) {
        $trailer = ' AND events.event_id >= :evendtid ';
        $eventid = func_get_arg(1);
        $extraField = array('evendtid' => $eventid);
    } else {
        $extraField = NULL;
    }

    // SQL Query trailer
    $trailer = $trailer . '';  // no more trailer this time

    // Call superFilter to count filtered events
    $result = superFilter($selector, $trailer, $filterType, FALSE, $extraField);
    $totalEvents = (int) $result[0]['eventsCount'];

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $totalEvents;
}



function eventFilterAround()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    global $filterIndexHint;
    
    $filterType = 'filter';

    $numargs = func_num_args();
    if ( $numargs != 2) {
        return false;
    }

    // Query for events
    $selector = 'SELECT events.event_id FROM date_range, events ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }

    if (func_get_arg(1) == "next") {
        $trailer = $trailer . ' AND events.event_id < :evendtid ';
    } elseif (func_get_arg(1) == "prev") {
        $trailer = $trailer . ' AND events.event_id > :evendtid ';
    } else {
        return false;
    }

    // SQL Query trailer
    if (func_get_arg(1) == "next") {
        $trailer = $trailer . ' ORDER BY events.event_id desc LIMIT 1 ';
    } elseif (func_get_arg(1) == "prev") {
        $trailer = $trailer . ' ORDER BY events.event_id asc LIMIT 1 ';
    } else {
        return false;
    }

    $eventid = func_get_arg(0);
    $extraField = array('evendtid' => $eventid);
    // Call superFilter to count filtered events
    $result = superFilter($selector, $trailer, $filterType, FALSE, $extraField);
    foreach ($result as $event) {
            $askedEvent = $event['event_id'];
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $askedEvent;
}


// Use the current filter to delete events (used by Delete Sensor too)
function deleteEventsByFilter()
{
    global $DEBUG;
    global $DEMO;
    if ($DEMO) {
        return;
    }    
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    global $filterIndexHint;
    global $deleteLimit;
    
    /* prepare statement using PDO*/
    global $dbconn;
    global $filterIndexHint;
    
    $filterType = 'delFilter';
    // Create Temporary table to hold event_id's to delete
    $sqlCreateTempTable='CREATE TEMPORARY TABLE delete_event (event_id2delete int(10) unsigned NOT NULL,PRIMARY KEY (event_id2delete))';

    try {
        $sthTempTable = $dbconn->prepare($sqlCreateTempTable);

        // Execute the query
        $sthTempTable->execute();
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    // Query for delete events
    $selector = 'INSERT INTO delete_event(event_id2delete) SELECT events.event_id as event_id FROM date_range, events ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }

    // SQL Query trailer
    // Make a limit to avoid lock de table for long time and to allow response to browser
    $trailer =  ' AND events.preserve = 0 LIMIT ' . $deleteLimit;

    $extraField = NULL;
    // Call superFilter to count filtered events
    $eventsToDeleteCount = superFilter($selector, $trailer, $filterType, TRUE, $extraField);
    

    if ($eventsToDeleteCount > 0) {

        // Start to delete event filled in Temporay table 'delete_event'
        $sqlDeleteEvents = 'DELETE events, events_messages, events_messages_tag, events_full_sections FROM delete_event, events LEFT JOIN events_messages ON events.event_id = events_messages.event_id LEFT JOIN events_messages_tag ON events_messages.msg_id = events_messages_tag.msg_id LEFT JOIN events_full_sections ON events.event_id  = events_full_sections.event_id WHERE  events.event_id = event_id2delete';

        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlDeleteEvents;
        }

        try {
            $sthDelete = $dbconn->prepare($sqlDeleteEvents);

            // Execute the query
            $sthDelete->execute();
        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }

    if ($DEBUG) {
        $stoptime                         = microtime(true);
        $timespend                        = $stoptime - $starttime;
        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $eventsToDeleteCount;
}

// Use the current filter to mark events as false positive
function falsePositiveByFilter()
{
    global $DEBUG;
    global $DEMO;
    if ($DEMO) {
        return;
    }    
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;
    global $filterIndexHint;
    global $deleteLimit;
    
    $filterType = 'fpFilter';
    // Create Temporary table to hold event_id's to mark as false positive
    $sqlCreateTempTable='CREATE TEMPORARY TABLE fp_event (event_id2fp int(10) unsigned NOT NULL,PRIMARY KEY (event_id2fp))';
    try {
        $sthTempTable = $dbconn->prepare($sqlCreateTempTable);

        // Execute the query
        $sthTempTable->execute();
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    // Query to mark false positive events
    $selector = 'INSERT INTO fp_event(event_id2fp) SELECT events.event_id as event_id FROM date_range, events ';
    if (count($_SESSION['filterIndexHint']) > 0 and $_SESSION['filterIndexHint'] != false) {
        $selector = $selector . 'USE INDEX '.$filterIndexHint;
    }
    // SQL Query trailer
    // Make a limit to avoid lock de table for long time and to allow response to browser
    $trailer =  ' LIMIT ' . $deleteLimit;

    $extraField = NULL;
    // Call superFilter to count filtered events
    $eventsToFPCount = superFilter($selector, $trailer, $filterType, TRUE, $extraField);

    if ($eventsToFPCount > 0) {

        // Start to delete event filled in Temporay table 'delete_event'
        $sqlFalsePositive  = 'UPDATE `events` LEFT JOIN fp_event ON events.event_id = fp_event.event_id2fp SET `events`.`false_positive` = 1  WHERE `events`.`event_id` = `fp_event`.`event_id2fp`';

        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlFalsePositive;
        }
        try {
            $sthFP = $dbconn->prepare($sqlFalsePositive);

            // Execute the query
            $sthFP->execute();
        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }

    if ($DEBUG) {
        $stoptime                         = microtime(true);
        $timespend                        = $stoptime - $starttime;
        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $eventsToFPCount;
}

function lookupUser($user)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    /* prepare statement using PDO*/
    global $dbconn;

    $sqlLookupUser = 'SELECT `username` FROM users WHERE user_id = :username LIMIT 1';
    if ($DEBUG) {
        $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlLookupUser;
    }
    try {
        $query_sth = $dbconn->prepare($sqlLookupUser);
        $query_sth->bindParam(":username", $user);

        // Execute the query
        $query_sth->execute();
        $lookupUserResult = $query_sth->fetchAll(PDO::FETCH_ASSOC);
        $queryStatus = $query_sth->errorCode();
        if ($queryStatus != 0) {
            $lookupUser = false;
        } else {
            $lookupUser = $lookupUserResult[0]['username'];
        }
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $lookupUser;
}

function getUsers()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    /* prepare statement using PDO*/
    global $dbconn;

    try {
        if (func_num_args() == 1 ) {
            $user = func_get_arg(0);
            $sqlGetUsers  = 'SELECT `user_id`, `username`, `email` FROM users WHERE user_id = :userid LIMIT 1';
            $query_sth = $dbconn->prepare($sqlGetUsers);
            $query_sth->bindParam(":userid", $user);
        } else {
            $sqlGetUsers = 'SELECT `user_id`, `username`, `email` FROM users';
            $query_sth = $dbconn->prepare($sqlGetUsers);
        }
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlGetUsers;
        }
        // Execute the query
        $query_sth->execute();
        $getUsers = $query_sth->fetchAll(PDO::FETCH_ASSOC);
        $userCount = count($getUsers);
        if ($userCount < 1) {
           return false;
        }
        $queryStatus = $query_sth->errorCode();
        if ($queryStatus != 0) {
            $getUsers = false;
        }
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $getUsers;
}

function getEvent($getevent_id)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    // prepare statement using PDO
    global $dbconn;

    try {
        $sqlGetEvent  = 'SELECT events.event_id as event_id, events.sensor_id as sensor_id, events.received_at as received_at, events.a_timestamp as a_timestamp, events.a_timezone as a_timezone, events.a_uniqid as a_uniqid, INET_NTOA(events.a_client_ip) AS a_client_ip, events.a_client_ip_cc AS a_client_ip_cc, events.a_client_ip_asn AS a_client_ip_asn, events.a_client_port AS a_client_port, INET_NTOA(events.a_server_ip) AS a_server_ip, events.a_server_port AS a_server_port, events.b_method AS b_method, events.b_path AS b_path, events.b_path_parameter AS b_path_parameter, events.b_protocol AS b_protocol, events.b_host AS b_host, events.b_user_agent AS b_user_agent, events.b_referer AS b_referer, events_full_sections.b_full AS b_full, events_full_sections.c_full AS c_full, events_full_sections.e_full AS e_full, events.f_protocol AS f_protocol, events.f_status AS f_status, events.f_msg AS f_msg, events.f_content_length AS f_content_length, events.f_connection AS f_connection, events.f_content_type AS f_content_type, events_full_sections.f_full AS f_full, events.h_apache_error_file AS h_apache_error_file, events.h_apache_error_line AS h_apache_error_line, events.h_apache_error_level AS h_apache_error_level, events.h_apache_error_message AS h_apache_error_message, events.h_stopwatch_timestamp AS h_stopwatch_timestamp, events.h_stopwatch_duration AS h_stopwatch_duration, events.h_stopwatch_time_checkpoint_1 AS h_stopwatch_time_checkpoint_1, events.h_stopwatch_time_checkpoint_2 AS h_stopwatch_time_checkpoint_2, events.h_stopwatch_time_checkpoint_3 AS h_stopwatch_time_checkpoint_3, events.h_stopwatch2_Timestamp AS h_stopwatch2_Timestamp, events.h_stopwatch2_duration AS h_stopwatch2_duration, events.h_stopwatch2_combined AS h_stopwatch2_combined, events.h_stopwatch2_p1 AS h_stopwatch2_p1, events.h_stopwatch2_p2 AS h_stopwatch2_p2, events.h_stopwatch2_p3 AS h_stopwatch2_p3, events.h_stopwatch2_p4 AS h_stopwatch2_p4, events.h_stopwatch2_p5 AS h_stopwatch2_p5, events.h_stopwatch2_sr AS h_stopwatch2_sr, events.h_stopwatch2_sw AS h_stopwatch2_sw, events.h_stopwatch2_l AS h_stopwatch2_l, events.h_stopwatch2_gc AS h_stopwatch2_gc,events.h_producer AS h_producer, events.h_producer_ruleset AS h_producer_ruleset, events.h_server AS h_server, events.h_wa_info_app_id AS h_wa_info_app_id, events.h_wa_info_sess_id AS h_wa_info_sess_id, events.h_wa_info_user_id AS h_wa_info_user_id, events.h_apache_handler AS h_apache_handler, events.h_response_body_transf AS h_response_body_transf, events.h_severity AS h_severity, events.h_Score_Total AS h_Score_Total,  events.h_Score_SQLi AS h_Score_SQLi, events.h_Score_XSS AS h_Score_XSS, events.h_action_status AS h_action_status, events.h_action_status_msg AS h_action_status_msg, events.preserve AS preserve, events.false_positive AS false_positive, events.h_engine_mode as h_engine_mode, events_full_sections.compressed AS compressed FROM events JOIN events_full_sections ON events.event_id = events_full_sections.event_id WHERE events.event_id = :event_id';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'][] = $sqlGetEvent;
        }

        $query_sth = $dbconn->prepare($sqlGetEvent);
        $query_sth->bindParam(":event_id", $getevent_id);

        // Execute the query
        $query_sth->execute();
        $eventCount = $query_sth->rowCount();
        if ($eventCount == 0) {
            return false;
        }
        $event_detail = $query_sth->fetch(PDO::FETCH_ASSOC);
        $queryStatus = $query_sth->errorCode();
        $query_sth->closeCursor();
        if ($queryStatus != 0) {
            return false;
        }

        if ($event_detail['compressed']) {
            $event_detail['b_full'] = $event_detail['b_full'] != "" ? gzuncompress($event_detail['b_full']) : null;
            $event_detail['c_full'] = $event_detail['c_full'] != "" ? gzuncompress($event_detail['c_full']) : null;
            $event_detail['e_full'] = $event_detail['e_full'] != "" ? gzuncompress($event_detail['e_full']) : null;
            $event_detail['f_full'] = $event_detail['f_full'] != "" ? gzuncompress($event_detail['f_full']) : null;
        }

    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    try {
        $sqlGetEventMessage = 'SELECT msg_id, h_message_ruleId, h_message_pattern, h_message_action, rule_message.message_ruleMsg AS h_message_ruleMsg, h_message_ruleData, h_message_ruleSeverity FROM events_messages JOIN rule_message ON events_messages.h_message_ruleId = rule_message.message_ruleId WHERE event_id = :event_id ORDER BY h_message_ruleSeverity ASC, h_message_ruleId ASC';

        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'][] = $sqlGetEventMessage;
        }
        $messageQuery_sth = $dbconn->prepare($sqlGetEventMessage);
        $messageQuery_sth->bindParam(":event_id", $getevent_id);

        // Execute the query
        $messageQuery_sth->execute();
        $event_detail_messages = $messageQuery_sth->fetchAll(PDO::FETCH_ASSOC);
        $queryStatus = $messageQuery_sth->errorCode();
        if ($queryStatus != 0) {
            return false;
        }

        // get event tag information
        $sqlGetEventTag = 'SELECT `tags`.`tag_id` AS tag_id, `tags`.`tag_name` AS tag_name, `tags`.`tag_title` AS tag_title, `tags`.`url` AS tag_url, `tags`.`tag_text` AS tag_text FROM events_messages_tag   INNER JOIN `tags` ON `events_messages_tag`.`h_message_tag`=`tags`.`tag_id` WHERE (`events_messages_tag`.`msg_id` = :msg_idTag)
        UNION
        SELECT `tags_custom`.`tag_id` AS tag_id, `tags_custom`.`tag_name` AS tag_name,`tags_custom`.`tag_title` AS tag_title, `tags_custom`.`url` AS tag_url, `tags_custom`.`tag_text` AS tag_text FROM events_messages_tag INNER JOIN `tags_custom` ON `events_messages_tag`.`h_message_tag`=`tags_custom`.`tag_id` WHERE (`events_messages_tag`.`msg_id` = :msg_idTagCustom)
        ORDER BY tag_id ASC';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'][] = $sqlGetEventTag;
        }

        $tagQuery_sth = $dbconn->prepare($sqlGetEventTag);

        foreach($event_detail_messages as $key => $msgArray) {
            foreach($msgArray as $msgkey => $msgData) {
                $event_detail[$msgkey][$key] = $msgData;

                if ($msgkey == 'msg_id') {
                    try {
                        $tagQuery_sth->bindParam(":msg_idTag", $msgData);
                        $tagQuery_sth->bindParam(":msg_idTagCustom", $msgData);

                        // Execute the query
                        $tagQuery_sth->execute();
                        $event_detail_messages_tag = $tagQuery_sth->fetchAll(PDO::FETCH_ASSOC);
                        $tagQueryStatus = $tagQuery_sth->errorCode();
                        if ($tagQueryStatus != 0) {
                            return false;
                        }
                        foreach($event_detail_messages_tag as $tagkey => $msgTagArray) {
                            foreach($msgTagArray as $taglabel => $tagData) {
                                $event_detail['h_message_tag'][$key][$tagkey][$taglabel] = $tagData;
                            }
                        }
                    } catch (PDOException $e) {
                       header("HTTP/1.1 500 Internal Server Error");
                       header("Status: 500");
                       print "HTTP/1.1 500 Internal Server Error \n";
                       if ($DEBUG) {
                           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
                           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
                       }
                       exit();
                    }
                }

            }
        }
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $event_detail;
}

function getrawevent($getevent_id)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    // prepare statement using PDO
    global $dbconn;

    try {
        $sqlGetRawEvent  = 'SELECT `a_full`, `b_full`, `c_full`, `e_full`, `f_full`, `h_full`, `i_full`, `k_full`, `z_full`, `compressed` FROM events_full_sections WHERE event_id = :event_id';

        $query_sth = $dbconn->prepare($sqlGetRawEvent);
        $query_sth->bindParam(":event_id", $getevent_id);

        // Execute the query
        $query_sth->execute();
        $eventCount = $query_sth->rowCount();
        if ($eventCount == 0) {
            return false;
        }
        $raw_event = $query_sth->fetch(PDO::FETCH_ASSOC);
        $queryStatus = $query_sth->errorCode();
        $query_sth->closeCursor();
        if ($queryStatus != 0) {
            return false;
        }

        if ($raw_event['compressed']) {
            $raw_event['a_full'] = $raw_event['a_full'] != "" ? gzuncompress($raw_event['a_full']) : null;
            $raw_event['b_full'] = $raw_event['b_full'] != "" ? gzuncompress($raw_event['b_full']) : null;
            $raw_event['c_full'] = $raw_event['c_full'] != "" ? gzuncompress($raw_event['c_full']) : null;
            $raw_event['e_full'] = $raw_event['e_full'] != "" ? gzuncompress($raw_event['e_full']) : null;
            $raw_event['f_full'] = $raw_event['f_full'] != "" ? gzuncompress($raw_event['f_full']) : null;
            $raw_event['h_full'] = $raw_event['h_full'] != "" ? gzuncompress($raw_event['h_full']) : null;
            $raw_event['i_full'] = $raw_event['i_full'] != "" ? gzuncompress($raw_event['i_full']) : null;
            $raw_event['k_full'] = $raw_event['k_full'] != "" ? gzuncompress($raw_event['k_full']) : null;
        }
        unset($raw_event['compressed']);

    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $raw_event;
}

// Mark an event as False Positive
function falsePositiveEvent($event_id, $fpOrNotFP)
{
    global $DEBUG;
    global $DEMO;
    if ($DEMO) {
        return;
    }    
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    // prepare statement using PDO
    global $dbconn;
    if ($fpOrNotFP == 'notfp') {
        $fpmode = 0;
    } else {
        $fpmode = 1;
    }

    try {
        $sqlFalsePositive  = 'UPDATE `events` SET `false_positive` = :FPMODE WHERE `event_id` = :event_id';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlFalsePositive;
        }
        $query_sth = $dbconn->prepare($sqlFalsePositive);
        $query_sth->bindParam(":event_id", $event_id);
        $query_sth->bindParam(":FPMODE", $fpmode);

        // Execute the query
        $query_sth->execute();
        $eventCount = $query_sth->rowCount();
        if ($eventCount == 0) {
            $fp = false;
        }
        $queryStatus = $query_sth->errorCode();
        if ($queryStatus != 0) {
            $fp = false;
        }
        $fp = TRUE;
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }
    return $fp;
}

function preserveEvent($event_id, $preserveOrNot)
{
    global $DEBUG;
    global $DEMO;
    if ($DEMO) {
        return;
    }    
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    // prepare statement using PDO
    global $dbconn;

    if ($preserveOrNot == 'NotPreserve') {
        $preservmode = 0;
    } else {
        $preservmode = 1;
    }

    try {
        $sqlPreserveEvent  = 'UPDATE `events` SET `preserve` = :preserveMode WHERE `event_id` = :event_id';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlPreserveEvent;
        }
        $query_sth = $dbconn->prepare($sqlPreserveEvent);
        $query_sth->bindParam(":event_id", $event_id);
        $query_sth->bindParam(":preserveMode", $preservmode);

        // Execute the query
        $query_sth->execute();
        $eventCount = $query_sth->rowCount();
        if ($eventCount == 0) {
            $preserv = false;
        }
        $queryStatus = $query_sth->errorCode();
        if ($queryStatus != 0) {
            $preserv = false;
        }
        $preserv = TRUE;
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }
    return $preserv;
}

function deleteSensor($sensor_id)
{
    global $DEBUG;
    global $DEMO;
    if ($DEMO) {
        return;
    }   
    if ($DEBUG) {
      global $debugInfo;
      $debugCount = count($debugInfo[__FUNCTION__]);
      $starttime = microtime(true);
    }

   global $APC_ON;
   /* prepare statement using PDO*/
   global $dbconn;

   // Query for delete sensor
   // Make sure that no events are 'left behind', even with deleteEventsByFilter called in ajax.php
   $sqlDeleteSensor = 'DELETE sensors, events, events_messages, events_messages_tag FROM sensors LEFT JOIN events ON sensors.sensor_id = events.sensor_id LEFT JOIN events_messages ON events.event_id = events_messages.event_id LEFT JOIN events_messages_tag ON events_messages.msg_id = events_messages_tag.msg_id WHERE  sensors.sensor_id = :sensor_id';

   if ($DEBUG) {
     $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlDeleteSensor;
   }

   try {
      $sthDelete = $dbconn->prepare($sqlDeleteSensor);

      $sthDelete->bindParam(":sensor_id", $sensor_id);

      // Execute the query
      $sthDelete->execute();
      $queryStatus = $sthDelete->errorCode();
      if ($queryStatus != 0) {
         print "Error! $queryStatus <br/>";
         print_r($sthDelete->errorInfo());
         return FALSE;
      }
   } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

   if ($APC_ON) {
      apc_clear_cache('user');
   }

   if ($DEBUG) {
      $stoptime                         = microtime(true);
      $timespend                        = $stoptime - $starttime;
      $debugInfo[__FUNCTION__]['time'] = $timespend;
   }
   return TRUE;
}


// Save the Sensors details
function saveSensor($sensorToSave, $sensorName, $sensorIp, $sensorDescription, $sensorType, $sensorPass, $clientIpInHeader, $clientIpHeader)
{
    global $DEBUG;
    global $DEMO;
    if ($DEMO) {
        return;
    }
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;
    global $APC_ON;

    if ($sensorToSave == "new") {
        $sql = 'INSERT `sensors` SET `name` = :name,`password` = :pass, `IP` = :ip, `description` = :description, `type` = :type, `client_ip_via` = :clientIpInHeader, `client_ip_header` = :clientIpHeader ';        
    } else {
        if ($sensorPass != "") {
            $sql = 'UPDATE `sensors` SET `name` = :name,`password` = :pass, `IP` = :ip, `description` = :description, `type` = :type, `client_ip_via` = :clientIpInHeader, `client_ip_header` = :clientIpHeader  WHERE `sensors`.`sensor_id` = :id';
        } else {
            $sql = 'UPDATE `sensors` SET `name` = :name, `IP` = :ip,`description` = :description, `type` = :type, `client_ip_via` = :clientIpInHeader, `client_ip_header` = :clientIpHeader WHERE `sensors`.`sensor_id` = :id';
        }
    }
    if ($DEBUG) {
        $debugInfo[__FUNCTION__][$debugCount]['query'] = $sql;
    }
    try {
        $sth = $dbconn->prepare($sql);
        if ($sensorToSave != "new") {
            $sth->bindParam(":id", $sensorToSave);
        }
        $sth->bindParam(":name", $sensorName);
        if ($sensorPass != "") {
            $sth->bindParam(":pass", $sensorPass);
        }
        $sth->bindParam(":ip", $sensorIp);
        $sth->bindParam(":type", $sensorType);
        $sth->bindParam(":description", $sensorDescription);
        $sth->bindParam(":clientIpInHeader", $clientIpInHeader);
        $sth->bindParam(":clientIpHeader", $clientIpHeader);

        // Execute the query
        $sth->execute();
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }
    if ($APC_ON) {
        apc_clear_cache('user');
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return true;
}

// Save the Sensors details
function disableEnableSensor($sensorToDisable, $status)
{
    global $DEBUG;
    global $DEMO;
    if ($DEMO) {
        return;
    }    
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;
    global $APC_ON;

    if ($status == 'enable') {
        $sql = 'UPDATE `sensors` SET `status` = \'Enabled\' WHERE `sensor_id` = :sensor_id';
    } elseif ($status == 'disable') {
        $sql = 'UPDATE `sensors` SET `status` = \'Disabled\' WHERE `sensor_id` = :sensor_id';
    } else {
       return FALSE;
    }
    if ($DEBUG) {
        $debugInfo[__FUNCTION__][$debugCount]['query'] = $sql;
    }
    try {
        $sth = $dbconn->prepare($sql);
        $sth->bindParam(":sensor_id", $sensorToDisable);

        // Execute the query
        $sth->execute();
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }
    if ($APC_ON) {
        apc_clear_cache('user');
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return true;
}



function deleteUser($user_id)
{
    global $DEBUG;
    global $DEMO;
    if ($DEMO) {
        return;
    }    
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

   // prepare statement using PDO
    global $dbconn;

    try {
        $sqlDeleteUser  = 'DELETE from `users` WHERE `user_id` = :user_id';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlDeleteUser;
        }
        $query_sth = $dbconn->prepare($sqlDeleteUser);
        $query_sth->bindParam(":user_id", $user_id);

        // Execute the query
        $query_sth->execute();
        $eventCount = $query_sth->rowCount();
        if ($eventCount == 0) {
            $deluser = FALSE;
        }
        $queryStatus = $query_sth->errorCode();
        if ($queryStatus != 0) {
            $deluser = FALSE;
        }
        $deluser = TRUE;
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }
    return $deluser;
}

function userSave($userToSave, $userName, $userEmail, $userPassword)
{
    global $DEBUG;
    global $DEMO;
    if ($DEMO) {
        return;
    }    
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;

    if ($userToSave == "new") {
        $sql = 'INSERT `users` SET `username` = :username,`password` = :pass, `email` = :email ';
    } else {
        if ($userPassword != '' AND $userName == '' AND $userEmail == '') {
            $sql = 'UPDATE `users` SET `password` = :pass WHERE `users`.`user_id` = :id';
        } else {
            $sql = 'UPDATE `users` SET `username` = :username, `email` = :email WHERE `users`.`user_id` = :id';
        }
    }
    if ($DEBUG) {
        $debugInfo[__FUNCTION__][$debugCount]['query'] = $sql;
    }
    try {
        $sth = $dbconn->prepare($sql);
        if ($userToSave == "new") {
            $userPassword = sha1($userPassword);
            $sth->bindParam(":pass", $userPassword);
            $sth->bindParam(":username", $userName);
            $sth->bindParam(":email", $userEmail);
        } else {
           $sth->bindParam(":id", $userToSave);
           if ($userPassword != '' AND $userName == '' AND $userEmail == '') {
               $userPassword = sha1($userPassword);
               $sth->bindParam(":pass", $userPassword);
           } else {
               $sth->bindParam(":username", $userName);
               $sth->bindParam(":email", $userEmail);
           }
         }
        // Execute the query
        $sth->execute();
        $queryStatus = $sth->errorCode();
        if ($queryStatus != 0) {
            return FALSE;
        }

    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return true;
}

function deleteEvent($event_id)
{
    global $DEBUG;
    global $DEMO;
    if ($DEMO) {
        return;
    }    
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    // prepare statement using PDO
    global $dbconn;

    $sqlDeleteEvent = 'DELETE events, events_messages, events_messages_tag, events_full_sections FROM events LEFT JOIN events_messages ON events.event_id = events_messages.event_id LEFT JOIN events_messages_tag ON events_messages.msg_id = events_messages_tag.msg_id LEFT JOIN events_full_sections ON events.event_id  = events_full_sections.event_id WHERE 1 AND events.preserve = 0 AND events.event_id = :event_id';
    if ($DEBUG) {
        $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlDeleteEvent;
    }
    try {
        $query_sth = $dbconn->prepare($sqlDeleteEvent);
        $query_sth->bindParam(":event_id", $event_id);

        // Execute the query
        $query_sth->execute();
        $eventCount = $query_sth->rowCount();

        $queryStatus = $query_sth->errorCode();
        if ($queryStatus != 0) {
            return FALSE;
        }
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return TRUE;
}

function getSensorName($sensor_id)
{
    global $DEBUG;
    global $DEMO;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    global $APC_ON;
    global $CACHE_TIMEOUT;

    if ($APC_ON AND ($sensor = apc_fetch('sensorName_'.$sensor_id))) {
        if ($DEBUG) {
            $debugInfo[__FUNCTION__]['cache'] = 1;
        }
    } else {
        global $dbconn;
        try {
            if ($DEMO) {
                $sqlSensorName  = 'SELECT `name`,`IP`,\'No Password in Demo Mode\' AS password, `description`,`type`, `status`, `client_ip_via`, `client_ip_header` FROM sensors WHERE sensor_id = :sensor_id LIMIT 1';
            } else {
                $sqlSensorName  = 'SELECT `name`,`IP`,`password`,`description`,`type`, `status`, `client_ip_via`, `client_ip_header` FROM sensors WHERE sensor_id = :sensor_id LIMIT 1';
            }
            if ($DEBUG) {
                $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlSensorName;
            }
            $query_sth = $dbconn->prepare($sqlSensorName);
            $query_sth->bindParam(":sensor_id", $sensor_id);

            // Execute the query
            $query_sth->execute();
            $eventCount = $query_sth->rowCount();
            if ($eventCount == 0) {
                return FALSE;
            }
            $sensor = $query_sth->fetch(PDO::FETCH_ASSOC);
            $queryStatus = $query_sth->errorCode();
            $query_sth->closeCursor();
            if ($queryStatus != 0) {
                return FALSE;
            } elseif ($APC_ON) {
                apc_store('sensorName_'.$sensor_id, $sensor, $CACHE_TIMEOUT);
                if ($DEBUG) {
                    $debugInfo[__FUNCTION__]['cache'] = 0;
                }
            }
        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $sensor;
}

function getSensorInfo($sensor_id)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    global $dbconn;
    global $APC_ON;
    global $CACHE_TIMEOUT;

    if ( $APC_ON AND $sensorInfo = apc_fetch('sensorInfo_'.$sensor_id)) {
        if ($DEBUG) {
            $debugInfo[__FUNCTION__]['cache'] = 1;
        }
    } else {

      // Get sensor events count
       try {
           $sqlSensorEventsCount  = 'SELECT count(`sensor_id`) as sensorEvents FROM `events` WHERE `sensor_id` =  :sensor_id';
            if ($DEBUG) {
                $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlSensorEventsCount;
            }
           $queryCount_sth = $dbconn->prepare($sqlSensorEventsCount);
           $queryCount_sth->bindParam(":sensor_id", $sensor_id);

           // Execute the query
           $queryCount_sth->execute();
           $sensorEventsCount = $queryCount_sth->fetch(PDO::FETCH_ASSOC);
           $queryStatus = $queryCount_sth->errorCode();
           $queryCount_sth->closeCursor();
           if ($queryStatus != 0) {
               return FALSE;
           }
       } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
      // Get sensor info
      if ($sensorEventsCount['sensorEvents'] > 0) {
         try {
           $sqlSensorInfo  = 'SELECT `a_date`, `h_producer`, `h_producer_ruleset`, `h_server` FROM `events` WHERE `sensor_id` = :sensor_id  order by `a_date` desc limit 1';
            if ($DEBUG) {
                $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlSensorInfo;
            }

           $queryInfo_sth = $dbconn->prepare($sqlSensorInfo);
           $queryInfo_sth->bindParam(":sensor_id", $sensor_id);

           // Execute the query
           $queryInfo_sth->execute();
           $sensorInfoResult = $queryInfo_sth->fetch(PDO::FETCH_ASSOC);
           $queryStatus = $queryInfo_sth->errorCode();
           $queryInfo_sth->closeCursor();
           if ($queryStatus != 0) {
               return FALSE;
           }
         } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
      } else {
         $sensorInfoResult = array('a_date' => '', 'h_producer' => '', 'h_producer_ruleset' => '', 'h_server' => '');
      }
      $sensorInfo = array_merge($sensorEventsCount, $sensorInfoResult);
       if ($APC_ON) {
          apc_store('sensorInfo_'.$sensor_id, $sensorInfo, $CACHE_TIMEOUT*3);
       }
      }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $sensorInfo;
}



// Check a sensor login
function sensorLogin($ip, $loginpass64, $login, $pass) {
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(TRUE);
    }
    global $dbconn;
    global $APC_ON;
    global $CACHE_TIMEOUT;

    // convert remote address to long
    $remoteAddrLong = sprintf("%u", ip2long($ip));

    // if the sensor credential are cache use it
    if ( $APC_ON AND apc_fetch($login.'_'.$pass) AND ($remoteAddrLong >= apc_fetch($login.'_'.$pass.'_ipstart') AND $remoteAddrLong <= apc_fetch($login.'_'.$pass.'_ipend')) OR (is_null(apc_fetch($login.'_'.$pass.'_ipstart')) AND is_null(apc_fetch($login.'_'.$pass.'_ipend'))) ) {
        $login_result['status'] = 1;
        $login_result['sensor_id'] = apc_fetch($login.'_'.$pass);
        $login_result['sensor_client_ip_header'] = apc_fetch($login.'_'.$pass.'_ip_header');
        $login_result['sensor_name'] = $login;
        if ($DEBUG) {
            $debugInfo[__FUNCTION__]['cache'] = 1;
        }
    } else {
        $sql = "SELECT sensor_id, name, IP, client_ip_via, client_ip_header FROM sensors WHERE status = 'Enabled' AND name LIKE :loginname AND password LIKE :password LIMIT 0 , 1";
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query']  = $sql;
        }
        try {
            $st_sensorlogin = $dbconn->prepare($sql);
            $st_sensorlogin->bindParam(":loginname", $login);
            $st_sensorlogin->bindParam(":password", $pass);
            $st_sensorlogin->execute();

            $sensorData = $st_sensorlogin->fetchAll(PDO::FETCH_ASSOC);
            $count      = count($sensorData);

            $iprange                 = networkRange($sensorData[0]['IP']);
            $sensor_id               = $sensorData[0]['sensor_id'];
            $sensor_name             = $sensorData[0]['name'];
            $sensor_client_ip_header = $sensorData[0]['client_ip_header'];


            if (($count > 0) AND (($remoteAddrLong >= $iprange['networklong']) AND ($remoteAddrLong <= $iprange['broadcastlong']) OR is_null($sensorData[0]['IP']) ) ) {
                if ($APC_ON) {
                    apc_store($login.'_'.$pass, $sensor_id, $CACHE_TIMEOUT);
                    apc_store($login.'_'.$pass.'_ip_header', $sensor_client_ip_header, $CACHE_TIMEOUT);
                    if (is_null($sensorData[0]['IP'])) {
                        apc_store($login.'_'.$pass.'_ipstart', $sensorData[0]['IP'], $CACHE_TIMEOUT);
                        apc_store($login.'_'.$pass.'_ipend', $sensorData[0]['IP'], $CACHE_TIMEOUT);
                    } else {
                        apc_store($login.'_'.$pass.'_ipstart', $iprange['networklong'], $CACHE_TIMEOUT);
                        apc_store($login.'_'.$pass.'_ipend', $iprange['broadcastlong'], $CACHE_TIMEOUT);
                    }
                }
                $login_result['status']      = 1;
                $login_result['sensor_id']   = $sensor_id;
                $login_result['sensor_name'] = $sensor_name;
                $login_result['sensor_client_ip_header'] = $sensor_client_ip_header;
            } else {
                $login_result['status'] = 0;
                $login_result['msg'] = "User, IP or Password don't match: $login, $pass, $ip, $count";
            }
        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           global $MLOG2WAFFLE_DEBUG;
           if ($MLOG2WAFFLE_DEBUG OR $DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }
    return $login_result;
}

// Get the existing Sensors on database
function getSensors()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;
    global $APC_ON;
    global $CACHE_TIMEOUT;

    if ($APC_ON AND ($sensors = apc_fetch('sensors'))) {
        $sensors = $sensors;
    } else {
        $sql = 'SELECT `sensors`.`sensor_id` as sensor_id, `sensors`.`name` as name, `sensors`.`IP` as IP, `sensors`.`description` as description, `sensors`.`type` as type, `sensors_type`.`description` as type_description, `sensors`.`status` as status FROM `sensors` JOIN `sensors_type` ON `sensors`.`type` = `sensors_type`.`type` ORDER BY name';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sql;
        }
        try {
            $sth = $dbconn->prepare($sql);
            // Execute the query
            $sth->execute();
            $sensors = $sth->fetchAll(PDO::FETCH_ASSOC);
            if ($APC_ON) {
                apc_store('sensors', $sensors, $CACHE_TIMEOUT);
            }
        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return array($sensors);
}

// Get the Sensors types
function sensorsType()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;

    $sql = 'SELECT `type`, `Description` FROM `sensors_type`';
    if ($DEBUG) {
        $debugInfo[__FUNCTION__][$debugCount]['query'] = $sql;
    }
    try {
        $sth = $dbconn->prepare($sql);
        // Execute the query
        $sth->execute();
        $sensorsType = $sth->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return array($sensorsType);
}

function getRuleName($rule_id)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }
    global $APC_ON;
    global $CACHE_TIMEOUT;

    if ($APC_ON AND ($ruleName = apc_fetch('ruleId_'.$rule_id))) {
        if ($DEBUG) {
            $debugInfo[__FUNCTION__]['cache'] = 1;
        }
    } else {
        global $dbconn;
        try {
            $sqlRuleName  = 'SELECT `message_ruleMsg` FROM rule_message WHERE message_ruleId = :rule_id LIMIT 1';
            if ($DEBUG) {
                $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlRuleName;
            }
            $query_sth = $dbconn->prepare($sqlRuleName);
            $query_sth->bindParam(":rule_id", $rule_id);

            // Execute the query
            $query_sth->execute();
            $ruleCount = $query_sth->rowCount();
            if ($ruleCount == 0) {
                return FALSE;
            }
            $ruleName = $query_sth->fetch(PDO::FETCH_ASSOC);
            $queryStatus = $query_sth->errorCode();
            $query_sth->closeCursor();
            if ($queryStatus != 0) {
                return FALSE;
            } elseif ($APC_ON) {
                apc_store('ruleId_'.$rule_id, $ruleName, $CACHE_TIMEOUT);
                if ($DEBUG) {
                    $debugInfo[__FUNCTION__]['cache'] = 0;
                }
            }
        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $ruleName;
}


// Get the Events Date Range on database
function getEventDateRange()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;

    $sqlDateRange = 'SELECT min(`a_date`), max(`a_date`) FROM `events`';
    if ($DEBUG) {
        $debugInfo[__FUNCTION__][$debugCount]['query'] = $sqlDateRange;
    }
    try {
        $sthmin = $dbconn->prepare($sqlDateRange);

        // Execute the query
        $sthmin->execute();

        $dateRange = $sthmin->fetchAll(PDO::FETCH_NUM);
        $stDate = $dateRange[0][0];
        $fnDate = $dateRange[0][1];

    } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return array($stDate, '00:00:00', $fnDate, '23:59:59');
}

// Get/Set the Web Hostname ID from/on database
function getWebHostID($host)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;
    global $APC_ON;
    global $CACHE_TIMEOUT;
    $host = strtolower($host);
    
    if ($APC_ON AND ($webHostID = apc_fetch('webhosts_'.$host))) {
        $webHostID = $webHostID;
    } else {
        $sql = 'SELECT `host_id` FROM `events_hostname` WHERE `hostname` = :host';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sql;
        }
        try {
            $sth = $dbconn->prepare($sql);
            $sth->bindParam(":host", $host);

            // Execute the query
            $sth->execute();
            $webHostID = $sth->fetch(PDO::FETCH_ASSOC);
            $webHostID = $webHostID['host_id'];
            $sth->closeCursor();
            if (!$webHostID) {
                $sqlInsert = 'INSERT IGNORE INTO `events_hostname` (`hostname`) VALUES(:hostname)';
                try {
                    $sth = $dbconn->prepare($sqlInsert);
                    $sth->bindParam(":hostname", $host);

                    // Execute the query
                    $sth->execute();
                    $webHostID = $dbconn->lastInsertId();
                } catch (PDOException $e) {
                   header("HTTP/1.1 500 Internal Server Error");
                   header("Status: 500");
                   print "HTTP/1.1 500 Internal Server Error \n";
                   if ($DEBUG) {
                       print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
                       print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
                   }
                   exit();
                }
            }
            
            if ($APC_ON) {
                apc_store('webhosts_'.$host, $webHostID, $CACHE_TIMEOUT);
            }

        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $webHostID;
}

// Get the Web Hostname on database
function getWebHostName($hostId)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;
    global $APC_ON;
    global $CACHE_TIMEOUT;
    if (!is_numeric($hostId)) {
        return FALSE;
    }
    if ($APC_ON AND ($webHostName = apc_fetch('webHostName_'.$hostId))) {
        $webHostName = $webHostName;
    } else {
        $sql = 'SELECT `hostname` FROM `events_hostname` WHERE `host_id` = :hostid LIMIT 1';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sql;
        }
        try {
            $sth = $dbconn->prepare($sql);
            $sth->bindParam(":hostid", $hostId);
            // Execute the query
            $sth->execute();
            $webHostName = $sth->fetch(PDO::FETCH_ASSOC);
            $webHostName = $webHostName['hostname'];
            $sth->closeCursor();
            if ($APC_ON) {
                apc_store('webHostName_'.$hostId, $webHostName, $CACHE_TIMEOUT);
            }
        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $webHostName;
}

// Get the Web Hostname on database
function getWebHosts()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;
    global $APC_ON;
    global $CACHE_TIMEOUT;

    if ($APC_ON AND ($webhosts = apc_fetch('webhosts'))) {
        $webhosts = $webhosts;
    } else {
        $sql = 'SELECT `host_id`, `hostname` FROM `events_hostname` ORDER BY `hostname`';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sql;
        }
        try {
            $sth = $dbconn->prepare($sql);

            // Execute the query
            $sth->execute();
            $webhosts = $sth->fetchAll(PDO::FETCH_ASSOC);
            if ($APC_ON) {
                apc_store('webhosts', $webhosts, $CACHE_TIMEOUT);
            }
        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $webhosts;
}

// Get the Database Info
function getDbInfo()
{
   global $DEBUG;
   if ($DEBUG) {
      global $debugInfo;
      $debugCount = count($debugInfo[__FUNCTION__]);
      $starttime = microtime(true);
   }

   /* prepare statement using PDO*/
   global $dbconn;
   global $DATABASE;
   global $DB_HOST1;
   global $DB_USER;
   global $DB_PASS;
   global $dbconn;


   $sql = 'SELECT version() as version';
    if ($DEBUG) {
        $debugInfo[__FUNCTION__][$debugCount]['query'][] = $sql;
    }
   try {
      $sth = $dbconn->prepare($sql);

      // Execute the query
      $sth->execute();
      $version = $sth->fetchAll(PDO::FETCH_ASSOC);
   } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

   $sql = 'SELECT count(*) as total from events';
    if ($DEBUG) {
        $debugInfo[__FUNCTION__][$debugCount]['query'][] = $sql;
    }
   try {
      $sth = $dbconn->prepare($sql);

      // Execute the query
      $sth->execute();
      $eventsTotal = $sth->fetchAll(PDO::FETCH_ASSOC);
   } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }

   // Database connection using PDO, to access information_schema database
   try {
       $dbconn2Info = new PDO('mysql:host='.$DB_HOST.';dbname='.$DATABASE, $DB_USER, $DB_PASS);
   } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }
   $sql = 'SELECT table_schema "dbName", SUM( data_length + index_length) "size" FROM information_schema.tables WHERE tables.table_schema = :dbname GROUP BY table_schema ;';
    if ($DEBUG) {
        $debugInfo[__FUNCTION__][$debugCount]['query'][] = $sql;
    }
   try {
      $sth = $dbconn2Info->prepare($sql);
      $sth->bindParam(":dbname", $DATABASE);
      // Execute the query
      $sth->execute();
      $size = $sth->fetchAll(PDO::FETCH_ASSOC);
   } catch (PDOException $e) {
       header("HTTP/1.1 500 Internal Server Error");
       header("Status: 500");
       print "HTTP/1.1 500 Internal Server Error \n";
       if ($DEBUG) {
           print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
           print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
       }
       exit();
    }
   $info = array_merge($version[0], $size[0], $eventsTotal[0]);

    if ($DEBUG) {
      $stoptime                         = microtime(true);
      $timespend                        = $stoptime - $starttime;
      $debugInfo[__FUNCTION__]['time'] = $timespend;
    }

    return $info;
}

function bytesConvert($bytes)
{
    $ext = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $unitCount = 0;
    for(; $bytes > 1024; ++$unitCount) $bytes /= 1024;
    $bytes = round($bytes, 2);
    return $bytes ." ". $ext[$unitCount];
}


// Get the Web Transaction Status on database
function getStatusList()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;
    global $APC_ON;
    global $CACHE_TIMEOUT;

    if ($APC_ON AND ($httpStatus = apc_fetch('httpStatus'))) {
        $httpStatus = $httpStatus;
    } else {
        $sql = 'SELECT http_code.code AS code, http_code.msg AS msg FROM http_code';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sql;
        }
        try {
            $sth = $dbconn->prepare($sql);

            // Execute the query
            $sth->execute();

            $httpStatus = $sth->fetchAll(PDO::FETCH_ASSOC);
            if ($APC_ON) {
                apc_store('httpStatus', $httpStatus, $CACHE_TIMEOUT);
            }
        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return array($httpStatus);
}

// Get the Web Transaction Status on database
function getMethodList()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    /* prepare statement using PDO*/
    global $dbconn;
    global $APC_ON;
    global $CACHE_TIMEOUT;

    if ($APC_ON AND ($httpMethod = apc_fetch('httpMethod'))) {
        $httpMethod = $httpMethod;
        if ($DEBUG) {
            $debugInfo[__FUNCTION__]['cache'] = 1;
        }
    } else {
        $sql = 'SELECT DISTINCT `b_method` FROM `events`';
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query'] = $sql;
        }
        if ($DEBUG) {
            $debugInfo[__FUNCTION__][$debugCount]['query']  = $sql;
        }
        try {
            $sth = $dbconn->prepare($sql);

            // Execute the query
            $sth->execute();

            $httpMethod = $sth->fetchAll(PDO::FETCH_ASSOC);
            if ($APC_ON) {
                apc_store('httpMethod', $httpMethod, $CACHE_TIMEOUT);
            }
        } catch (PDOException $e) {
           header("HTTP/1.1 500 Internal Server Error");
           header("Status: 500");
           print "HTTP/1.1 500 Internal Server Error \n";
           if ($DEBUG) {
               print "Error (".__FUNCTION__.") Message: " . $e->getMessage() . "\n";
               print "Error (".__FUNCTION__.") getTraceAsString: " . $e->getTraceAsString() . "\n";
           }
           exit();
        }
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return array($httpMethod);
}

function getEventPag($getevent_id)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    $next_event_id       = eventFilterAround($getevent_id, "next");
    $prev_event_id       = eventFilterAround($getevent_id, "prev");
    $current_event_count = eventFilterCount('filter', $getevent_id); // Call eventFilterCount that use current filter to count
    $total_event_count   = eventFilterCount('filter'); // Call eventFilterCount that use current filter to count

    $event_nav = array ( 'next_event_id'    => $next_event_id,
                         'prev_event_id'    => $prev_event_id,
                         'current_event_count' => $current_event_count,
                         'total_event_count' => $total_event_count);
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return $event_nav;
}


function bodyprint($content)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    $sliced_content = explode("\n", $content);
    foreach ($sliced_content as $content_line) {
        if (preg_match('/^\-\-[a-f0-9]+\-.\-\-/i', $content_line)) {
            continue;
        } else {
            $body_result .= htmlentities($content_line, ENT_QUOTES, 'UTF-8') . "\n" ;
        }
    }
    $body_result = trim($body_result);

    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }
    return $body_result;
}


function headerprintnobr($content)
{
    $content_result = NULL;
    $content_result .= htmlentities($content, ENT_QUOTES, 'UTF-8');

    return $content_result;
}

//  The Sanitization functions bellow are derived of (http://www.owasp.org/index.php/OWASP_PHP_Filters) OWASP PHP Filters

/*
 * Copyright (c) 2002,2003 Free Software Foundation
 * developed under the custody of the
 * Open Web Application Security Project
 * (http://www.owasp.org)
 *
 * This file is part of the PHP Filters.
 * PHP Filters is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * PHP Filters is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * If you are not able to view the LICENSE, which should
 * always be possible within a valid and working PHP Filters release,
 * please write to the Free Software Foundation, Inc.,
 * 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * to get a copy of the GNU General Public License or to report a
 * possible license violation.
 */
///////////////////////////////////////
// sanitize.inc.php
// Sanitization functions for PHP
// by: Gavin Zuchlinski, Jamie Pratt, Hokkaido
// webpage: http://libox.net
// Last modified: December 21, 2003
//
// Many thanks to those on the webappsec list for helping me improve these functions
///////////////////////////////////////
// Function list:
// sanitize_paranoid_string($string) -- input string, returns string stripped of all non
//           alphanumeric
// sanitize_system_string($string) -- input string, returns string stripped of special
//           characters
// sanitize_sql_string($string) -- input string, returns string with slashed out quotes
// sanitize_html_string($string) -- input string, returns string with html replacements
//           for special characters
// sanitize_int($integer) -- input integer, returns ONLY the integer (no extraneous
//           characters
// sanitize_float($float) -- input float, returns ONLY the float (no extraneous
//           characters)
// sanitize($input, $flags) -- input any variable, performs sanitization
//           functions specified in flags. flags can be bitwise
//           combination of PARANOID, SQL, SYSTEM, HTML, INT, FLOAT, LDAP,
//           UTF8
//
//
///////////////////////////////////////
//
// 20031121 jp - added defines for magic_quotes and register_globals, added ; to replacements
//               in sanitize_sql_string() function, created rudimentary testing pages
// 20031221 gz - added nice_addslashes and changed sanitize_sql_string to use it
//
/////////////////////////////////////////

define("PARANOID", 1);
define("SQL", 2);
define("SYSTEM", 4);
define("HTML", 8);
define("INT", 16);
define("FLOAT", 32);
define("LDAP", 64);
define("UTF8", 128);

// get register_globals ini setting - jp
$register_globals = (bool) ini_get('register_gobals');
if ($register_globals == true) { define("REGISTER_GLOBALS", 1); } else { define("REGISTER_GLOBALS", 0); }

// get magic_quotes_gpc ini setting - jp
$magic_quotes = (bool) ini_get('magic_quotes_gpc');
if ($magic_quotes == true) { define("MAGIC_QUOTES", 1); } else { define("MAGIC_QUOTES", 0); }

// addslashes wrapper to check for gpc_magic_quotes - gz
function nice_addslashes($string)
{
    // if magic quotes is on the string is already quoted, just return it
    if(MAGIC_QUOTES)
        return $string;
    else
        return addslashes($string);
}

// internal function for utf8 decoding
// thanks to Hokkaido for noticing that PHP's utf8_decode function is a little
// screwy, and to jamie for the code
function my_utf8_decode($string)
{
    return strtr($string,
        "???????¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ",
        "SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
}

// paranoid sanitization -- only let the alphanumeric set through
function sanitize_paranoid_string($string, $min = '', $max = '')
{
    $string = preg_replace("/[^a-zA-Z0-9\.\-\_\@\s]/", "", $string);
    $len    = strlen($string);
    if((($min != '') && ($len < $min)) || (($max != '') && ($len > $max))) {
        return false;
    }
    return $string;
}
// paranoid sanitization for URL-- only let the alphanumeric set through
function sanitize_paranoid_path($string, $min = '', $max = '')
{
    $string = preg_replace("/[^a-zA-Z0-9\.\-\_\@\/\?=\&\:]/", "", $string);
    $len    = strlen($string);
    if((($min != '') && ($len < $min)) || (($max != '') && ($len > $max))) {
        return false;
    }
    return $string;
}

// sanitize a string in prep for passing a single argument to system() (or similar)
function sanitize_system_string($string, $min = '', $max = '')
{
    $pattern = '/(;|\||`|>|<|&|^|"|'."\n|\r|'".'|{|}|[|]|\)|\()/i'; // no piping, passing possible environment variables ($),
                           // seperate commands, nested execution, file redirection,
                           // background processing, special commands (backspace, etc.), quotes
                           // newlines, or some other special characters
    $string  = preg_replace($pattern, '', $string);
    $string  = '"'.preg_replace('/\$/', '\\\$', $string).'"'; //make sure this is only interpretted as ONE argument
    $len     = strlen($string);
    if((($min != '') && ($len < $min)) || (($max != '') && ($len > $max))) {
        return false;
    }
    return $string;
}

// sanitize a string for SQL input (simple slash out quotes and slashes)
function sanitize_sql_string($string, $min = '', $max = '')
{
    $string      = nice_addslashes($string); //gz
    $pattern     = "/;/"; // jp
    $replacement = "";
    $len         = strlen($string);
    if((($min != '') && ($len < $min)) || (($max != '') && ($len > $max))) {
        return false;
    }
    return preg_replace($pattern, $replacement, $string);
}

// sanitize a string for SQL input (simple slash out quotes and slashes)
function sanitize_ldap_string($string, $min = '', $max = '')
{
    $pattern = '/(\)|\(|\||&)/';
    $len     = strlen($string);
    if((($min != '') && ($len < $min)) || (($max != '') && ($len > $max))) {
        return false;
    }
    return preg_replace($pattern, '', $string);
}


// sanitize a string for HTML (make sure nothing gets interpretted!)
function sanitize_html_string($string)
{
    $pattern[0]      = '/\&/';
    $pattern[1]      = '/</';
    $pattern[2]      = "/>/";
    $pattern[3]      = '/\n/';
    $pattern[4]      = '/"/';
    $pattern[5]      = "/'/";
    $pattern[6]      = "/%/";
    $pattern[7]      = '/\(/';
    $pattern[8]      = '/\)/';
    $pattern[9]      = '/\+/';
    $pattern[10]     = '/-/';
    $replacement[0]  = '&amp;';
    $replacement[1]  = '&lt;';
    $replacement[2]  = '&gt;';
    $replacement[3]  = '<br>';
    $replacement[4]  = '&quot;';
    $replacement[5]  = '&#39;';
    $replacement[6]  = '&#37;';
    $replacement[7]  = '&#40;';
    $replacement[8]  = '&#41;';
    $replacement[9]  = '&#43;';
    $replacement[10] = '&#45;';
    return preg_replace($pattern, $replacement, $string);
}

// make int int!
function sanitize_int($integer, $min = '', $max = '')
{
    $int = intval($integer);
    if((($min != '') && ($int < $min)) || (($max != '') && ($int > $max))) {
        return false;
    }
    return $int;
}

// make float float!
function sanitize_float($float, $min = '', $max = '')
{
    $float = floatval($float);
    if((($min != '') && ($float < $min)) || (($max != '') && ($float > $max))) {
        return false;
    }
    return $float;
}

// glue together all the other functions
function sanitize($input, $flags, $min = '', $max = '')
{
    if($flags & UTF8) $input     = my_utf8_decode($input);
    if($flags & PARANOID) $input = sanitize_paranoid_string($input, $min, $max);
    if($flags & INT) $input      = sanitize_int($input, $min, $max);
    if($flags & FLOAT) $input    = sanitize_float($input, $min, $max);
    if($flags & HTML) $input     = sanitize_html_string($input, $min, $max);
    if($flags & SQL) $input      = sanitize_sql_string($input, $min, $max);
    if($flags & LDAP) $input     = sanitize_ldap_string($input, $min, $max);
    if($flags & SYSTEM) $input   = sanitize_system_string($input, $min, $max);
    return $input;
}

function check_paranoid_string($input, $min = '', $max = '')
{
    if($input != sanitize_paranoid_string($input, $min, $max)) {
        return false;
    }
    return true;
}

function check_int($input, $min = '', $max = '')
{
    if($input != sanitize_int($input, $min, $max)) {
        return false;
    }
    return true;
}

function check_float($input, $min = '', $max = '')
{
    if($input != sanitize_float($input, $min, $max)) {
        return false;
    }
    return true;
}

function check_html_string($input, $min = '', $max = '')
{
    if($input != sanitize_html_string($input, $min, $max)) {
        return false;
    }
    return true;
}

function check_sql_string($input, $min = '', $max = '')
{
    if($input != sanitize_sql_string($input, $min, $max)) {
        return false;
    }
    return true;
}

function check_ldap_string($input, $min = '', $max = '')
{
    if($input != sanitize_string($input, $min, $max)) {
        return false;
    }
    return true;
}

function check_system_string($input, $min = '', $max = '')
{
    if($input != sanitize_system_string($input, $min, $max, true)) {
        return false;
    }
    return true;
}

// glue together all the other functions
function check($input, $flags, $min = '', $max = '')
{
    $oldput = $input;
    if($flags & UTF8) $input     = my_utf8_decode($input);
    if($flags & PARANOID) $input = sanitize_paranoid_string($input, $min, $max);
    if($flags & INT) $input      = sanitize_int($input, $min, $max);
    if($flags & FLOAT) $input    = sanitize_float($input, $min, $max);
    if($flags & HTML) $input     = sanitize_html_string($input, $min, $max);
    if($flags & SQL) $input      = sanitize_sql_string($input, $min, $max);
    if($flags & LDAP) $input     = sanitize_ldap_string($input, $min, $max);
    if($flags & SYSTEM) $input   = sanitize_system_string($input, $min, $max, true);
    if($input != $oldput) {
        return false;
    }
    return true;
}

/**
 * Performs the same function as array_search except that it is case
 * insensitive
 * @param mixed $needle
 * @param array $haystack
 * @return mixed
 */

function array_nsearch($needle, array $haystack)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    $it = new IteratorIterator(new ArrayIterator($haystack));
    foreach($it as $key => $val) {
        if(strcasecmp($val,$needle) === 0) {
            return $key;
        }
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    return false;
}

function logoff()
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(true);
    }

    // last request was more than $SESSION_TIMEOUT minates ago
    $_SESSION = array();

    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"]);
    }

    session_destroy();   // destroy session data in storage

    header("HTTP/1.1 302 Found");
    header("Location: login.php");
    header("Connection: close");
    header("Content-Type: text/html; charset=UTF-8");
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }

    exit();
}

function validateIP($ip)
{
    global $DEBUG;
    if ($DEBUG) {
        global $debugInfo;
        $debugCount = count($debugInfo[__FUNCTION__]);
        $starttime = microtime(TRUE);
    }

    //if (preg_match('/([12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2})/', $ip)) {
    if (preg_match('/([12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2})(\/\d{1,2})?/', $ip)) {
        if (ip2long($ip)) {
            $valid = TRUE;
        } elseif (networkRange($ip)) {
            $valid = TRUE;
        } else {
            $valid = FALSE;
        }
    } else {
        $valid = FALSE;
    }
    if ($DEBUG) {
        $stoptime = microtime(true);
        $timespend = $stoptime - $starttime;

        $debugInfo[__FUNCTION__][$debugCount]['time'] = $timespend;
    }
    return $valid;
}

function networkRange($ipAddRange)
{
    if (preg_match('/([12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2})(\/\d{1,2})?/', $ipAddRange, $ip_result)) {
        $ip_addr = $ip_result[1];
        $cidr = str_replace("/", "", $ip_result[2]);
        if ($cidr == null) {
            $cidr = 32;
        }
        if (validateIP($ip_addr) AND sanitize_int($cidr, $min = '1', $max = '32')) {
            $subnet_mask = long2ip(-1 << (32 - (int)$cidr));
            $ip          = ip2long($ip_addr);
            $nm          = ip2long($subnet_mask);
            $nw          = ($ip & $nm);
            $bc          = $nw | (~$nm);

            $ipRange['ip']            = long2ip($ip);
            $ipRange['iplong']        = sprintf("%u", $ip);
            $ipRange['cidr']          = $cidr;
            $ipRange['netmask']       = long2ip($nm);
            $ipRange['network']       = long2ip($nw);
            $ipRange['networklong']   = sprintf("%u", $nw);
            $ipRange['broadcast']     = long2ip($bc);
            $ipRange['broadcastlong'] = sprintf("%u", $bc);
            $ipRange['hosts']         = ($bc - $nw + 1);
            $ipRange['range']         = long2ip($nw) . " -> " . long2ip($bc);

            return $ipRange;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function strstr_after($haystack, $needle, $case_insensitive = false) {
    $strpos = ($case_insensitive) ? 'stripos' : 'strpos';
    $pos = $strpos($haystack, $needle);
    if (is_int($pos)) {
        return trim(trim(substr($haystack, $pos + strlen($needle))), "\"");
    }
    // Most likely false or null
    return $pos;
}

// Make array_unique go multidimensional... from http://www.php.net/manual/en/function.array-unique.php#84750
function arrayUnique($myArray)
{
    if(!is_array($myArray))
           return $myArray;

    foreach ($myArray as &$myvalue){
        $myvalue=serialize($myvalue);
    }

    $myArray=array_unique($myArray);

    foreach ($myArray as &$myvalue){
        $myvalue=unserialize($myvalue);
    }

    return $myArray;
}

?>
