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
// Header read and treatment.

foreach (getallheaders() as $header => $value) {
    if (stristr($header, 'Authorization')) {
        preg_match('/^Basic\s([0-9a-zA-Z]+\={0,2})$/', $value, $matches);
        preg_match('/^([0-9a-z\.\_\-]{5,30}):([0-9a-z\.\_\-\,\;\?\\\|\!\@\#\$\%\&\*\(\)\=\+\[\]\{\}\>\<]{5,20})/i', base64_decode($matches[1]), $matches2);
        $http_header['USER'] = strtolower($matches2[1]);
        $http_header['PASS'] = ($matches2[2]);
    }
    if ($header == "X-WAFFLE-Debug") {
        if ($value == "ON") {
            $MLOG2WAFFLE_DEBUG = TRUE;
        }
    }
}
if (!isset($MLOG2WAFFLE_DEBUG)) {
   $MLOG2WAFFLE_DEBUG = FALSE;
}
$remote_address = apache_getenv("REMOTE_ADDR");

// Validate sensor account
$login_status = sensorLogin($remote_address, $matches[1], $http_header["USER"], $http_header["PASS"]);

if ($login_status['status'] == 1) {
    $sensor_id   = $login_status['sensor_id'];
    // Tell do Apache the "username", to register on logs
    apache_setenv("REMOTE_USER", $login_status['sensor_name']);
} elseif ($login_status['status'] == 0) {
    header("HTTP/1.1 403 Forbidden");
    header("Status: 403");
    exit();  // Finish the program
} else {
    header("HTTP/1.1 500 Internal Server Error");
    header("Status: 500");
    if ($MLOG2WAFFLE_DEBUG) {
        print "Authentication Error\n";
    }
    exit();
}
if ($login_status['sensor_client_ip_header'] != "") {
    if (isset($IPHeaderUseLast) AND $IPHeaderUseLast) {
        $clientIpHeaderRegExp = "^".$login_status['sensor_client_ip_header'].":.+([12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2})$";
    } else {
        $clientIpHeaderRegExp = "^".$login_status['sensor_client_ip_header'].":\s([12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2})";
    }
}

// Body: read and treatment
$BODY     = file('php://input');
$line     = 0;
$BodySize = count($BODY);

while ( $line < $BodySize) {

    if (preg_match('/^WAF\-FLE\ PROBE/i', trim($BODY[$line]))) {
        // Probe ok, exiting now;
        header("X-WAF-FLE: READY");
        header("Status: 200");
        print "WAF-FLE: READY\n";
        exit;
    }
    // Phase A;
    if (preg_match('/^\-\-[a-f0-9]+\-A\-\-$/i', trim($BODY[$line]))) {
        $PhaseA_full = null;
        // audit log header (mandatory)
        while ( $line < $BodySize ) {
            if (preg_match('/^\-\-[a-f0-9]+\-[BCEFHIKZ]\-\-$/i', trim($BODY[$line]))) {
                break;
            } else {
                if (preg_match('/^\[(\d{1,2})\/(\w{3})\/(\d{4})\:(\d{2}\:\d{2}\:\d{2})\s(\-\-\d{4}|\+\d{4})\]\s([a-zA-Z0-9\-\@]{24})\s([12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2})\s(\d{1,5})\s([12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2})\s(\d{1,5})/i',
            trim($BODY[$line]), $matchesA)) {
                    $PhaseA['Day'] = $matchesA[1];
                    $months        = array(null, 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
                    foreach ($months as $key => $month) {
                        if ($month == $matchesA[2]) {
                            $PhaseA['Month'] = $key;
                        }
                    }
                    $PhaseA['Year']       = $matchesA[3];
                    $PhaseA['Hour']       = $matchesA[4];
                    $PhaseA['Timestamp']  = $matchesA[3]."-".$PhaseA['Month']."-".$matchesA[1]." ".$matchesA[4];
                    $PhaseA['Timezone']   = $matchesA[5];
                    $PhaseA['Date']       = $matchesA[3]."-".$PhaseA['Month']."-".$matchesA[1];
                    $PhaseA['UniqID']     = $matchesA[6];
                    $PhaseA['ClientIP']   = $matchesA[7];
                    $PhaseA['SourcePort'] = $matchesA[8];
                    $PhaseA['ServerIP']   = $matchesA[9];
                    $PhaseA['ServerPort'] = $matchesA[10];
                }
                $PhaseA_full = $PhaseA_full . $BODY[$line];
                $line++;
            }
        }
    }
    // Phase B
    if (preg_match('/^\-\-[a-f0-9]+\-B\-\-$/i', trim($BODY[$line]))) {
        $PhaseB_full = null;
        while ( $line < $BodySize ) {
            if (preg_match('/^\-\-[a-f0-9]+\-[ACEFHIKZ]\-\-$/i', trim($BODY[$line]))) {
                break;
            } else {
                if (preg_match('/^(GET|POST|HEAD|PUT|DELETE|TRACE|PROPFIND|OPTIONS|CONNECT|PATCH)\s(.+)\s(HTTP\/[01]\.[019])/i', trim($BODY[$line]), $matchesB)) {
                    $PhaseB['Method']        = $matchesB[1];
                    $PhaseB['pathParameter'] = parse_url("http://dummy.ex".$matchesB[2], PHP_URL_QUERY);
                    // $pathParsed              = parse_url($matchesB[2], PHP_URL_PATH);
                    $PhaseB['path']          = parse_url("http://dummy.ex".$matchesB[2], PHP_URL_PATH);
                    $PhaseB['Protocol']      = $matchesB[3];
                } elseif (preg_match('/^Host:\s(.+)/i', trim($BODY[$line]), $matchesB)) {
                    $PhaseB['Host'] = $matchesB[1];
                } elseif (preg_match('/^Content-Type:\s([\w\-\/]+)\;\s([\w\-\;\.\/\*\+\=\:\?\,\s\(\)]+)/i', trim($BODY[$line]), $matchesB)) {
                    $PhaseB['Content-Type'] = $matchesB[1];
                } elseif (preg_match('/^Referer:\s(.+)/i', trim($BODY[$line]), $matchesB)) {
                    $PhaseB['Referer'] = $matchesB[1];
                } elseif (preg_match('/^User-Agent:\s(.+)/i', trim($BODY[$line]), $matchesB)) {
                    $PhaseB['User-Agent'] = $matchesB[1];
                } elseif ($login_status['sensor_client_ip_header'] != "" AND preg_match("/$clientIpHeaderRegExp/i", trim($BODY[$line]), $matchesB)) {
                    $PhaseA['ClientIP'] = $matchesB[1];  // Set Client IP (to Phase A) when a HTTP Header is defined to carry real client ip, and sensor are marked to respect this
                }
                
                $PhaseB_full = $PhaseB_full . $BODY[$line];
                $line++;
            }
        }
    }

    // Phase C
    if (preg_match('/^\-\-[a-f0-9]+\-C\-\-$/i', trim($BODY[$line]))) {
        $PhaseC_full = null;
      $PhaseC_line0 = $line;
        while ( $line < $BodySize ) {
            $PhaseC_pass = $line - $PhaseC_line0;
            if (preg_match('/^\-\-[a-f0-9]+\-[ABEFHIKZ]\-\-$/i', trim($BODY[$line]))) {
                break;
            } elseif ( $PhaseC_pass > 100 ) {
                $line++;
            } else {
                $PhaseC_full = $PhaseC_full . substr($BODY[$line], 0, 4096);
                $line++;
            }
        }
    }

    // Phase E
    if (preg_match('/^\-\-[a-f0-9]+\-E\-\-$/i', trim($BODY[$line]))) {
        $PhaseE_full = null;
        while ( $line < $BodySize ) {
            if (preg_match('/^\-\-[a-f0-9]+\-[ABCFHIKZ]\-\-$/i', trim($BODY[$line]))) {
                break;
            } else {
                $PhaseE_full = $PhaseE_full . $BODY[$line];
                $line++;
            }
        }
    }

    // Phase F
    if (preg_match('/^\-\-[a-f0-9]+\-F\-\-$/i', trim($BODY[$line]))) {
        $PhaseF_full = null;
        while ( $line < $BodySize ) {
            if (preg_match('/^\-\-[a-f0-9]+\-[ABCEHIKZ]\-\-$/i', trim($BODY[$line]))) {
                break;
            } else {
                if (preg_match('/^(HTTP\/\d\.\d)\s(\d\d\d)\s([\w\s]+)/i', trim($BODY[$line]), $matchesF)) {
                    $PhaseF['Protocol'] = $matchesF[1];
                    $PhaseF['Status']   = $matchesF[2];
                    $PhaseF['MSG']      = $matchesF[3];
                } elseif (preg_match('/^Content-Length:\s(\d+)/i', trim($BODY[$line]), $matchesF)) {
                    $PhaseF['Content-Length'] = $matchesF[1];
                } elseif (preg_match('/^Connection:\s([\w-]+)/i', trim($BODY[$line]), $matchesF)) {
                    $PhaseF['Connection'] = $matchesF[1];
                } elseif (preg_match('/^Content-Type:\s((?:[\w\-\/]+)(?:\;)?(?:\s)?(?:[\w\-\;\.\/\*\+\=\:\?\,\s\(\)]+)?)/i', trim($BODY[$line]), $matchesF)) {
                    $PhaseF['Content-Type'] = $matchesF[1];
                }
                $PhaseF_full = $PhaseF_full . $BODY[$line];
                $line++;
            }
        }
    }

    // Phase H
    if (preg_match('/^\-\-[a-f0-9]+\-H\-\-$/i', trim($BODY[$line]))) {
        $PhaseH_full = null;
        $hline       = 0;
        while ( $line < $BodySize ) {
            if (preg_match('/^\-\-[a-f0-9]+\-[ABCEFIKZ]\-\-$/i', trim($BODY[$line]))) {
                break;
            } else {
                // Is a message line?
                $currentHLine = trim($BODY[$line]);
                if (preg_match('/^Message:\s/i', $currentHLine)) {
					$message_start = 0;
                    // look for message Action
					if (preg_match('/^Message:\s((Warning|Access|Paus)(.*?))\.\s/i', $currentHLine, $matchesH)) {
                        $PhaseH_MSG[$hline]['Message_Action'] = $matchesH[1];
                        foreach ($ActionStatus as $key => $statusValue) {
                            if (preg_match('/'.$statusValue.'/i', $matchesH[1])) {
                                if (isset($PhaseH['ActionStatus']) AND $PhaseH['ActionStatus'] > $key) {
                                    $PhaseH['ActionStatus']    = $key;
                                    $PhaseH['ActionStatusMsg'] = $matchesH[1];

                                } elseif (!isset($PhaseH['ActionStatus'])) {
                                    $PhaseH['ActionStatus']    = $key;
                                    $PhaseH['ActionStatusMsg'] = $matchesH[1];
                                }
                            }
                        }
                        $message_start  = strpos($currentHLine, ". ", 0) + 2;
                    } else {
						$message_start  = 9;
					}

                    //Execution error - PCRE limit exceeded handling
                    if (preg_match('/Execution\serror\s-\sPCRE\slimits\sexceeded/', trim($BODY[$line]))) {
                        $PhaseH_MSG[$hline]['Message_Msg'] = "Execution Error - PCRE limit exceeded";
                        $PhaseH_MSG[$hline]['Message_RuleId'] = $PcreErrRuleId;
                        preg_match('/id\s\"(\d+)\"/', trim($BODY[$line]), $PcreRuleId);
                        $PhaseH_MSG[$hline]['Message_Data'] = "RuleId:" . $PcreRuleId[1];
                        $PhaseH_full = $PhaseH_full . $BODY[$line];
                        $hline++;
                        $line++;
                        continue;
                    } 

                    // look for Pattern 
                    // include workaround to make compatible with libinject broken log format
                    $message_stop  = strpos($currentHLine, " [file", $message_start);
                    $message_length = $message_stop - $message_start;
                    $pattern =  substr($currentHLine, $message_start, $message_length);
                    $PhaseH_MSG[$hline]['Message_Pattern'] = (isset($pattern) ? rtrim($pattern, ".") : null);
                    $message_start = $message_stop;

                    // look for metadata
                    while (true){
                        $message_start = strpos($currentHLine, " [", $message_start);
                        if ($message_start === false) {
                            break;
                         }
                        $message_stop = strpos($currentHLine, "\"] ", $message_start);
                        if ($message_stop === false) {
                            $message_stop = strpos($currentHLine, "\"]", $message_start);
                            if ($message_stop === false) {
                                $message_stop = strlen($currentHLine);
                            }
                        }
                        $message_length = $message_stop - $message_start;
                        $msg_content = substr($currentHLine, $message_start, $message_length);

                        $message_start = $message_stop;

                        // look for File
                        $message_file = strstr_after($msg_content, '[file ', true);
                        if ($message_file) {
                            $PhaseH_MSG[$hline]['Message_File'] = $message_file;
                            continue;
                        }

                        // look for line
                        $message_line = strstr_after($msg_content, '[line ', true);
                        if ($message_line) {
                            $PhaseH_MSG[$hline]['Message_Line'] = $message_line;
                            continue;
                        }

                        // look for rev
                        $message_rev = strstr_after($msg_content, '[rev ', true);
                        if ($message_rev) {
                            $PhaseH_MSG[$hline]['Message_Rev'] = $message_rev;
                            continue;
                        }

                        // look for Rule Id
                        $message_Ruleid = strstr_after($msg_content, '[id ', true);
                        if ($message_Ruleid) {
                            $PhaseH_MSG[$hline]['Message_RuleId'] = $message_Ruleid;
                            continue;
                        }

                        // look for data
                        $message_data = strstr_after($msg_content, '[data ', true);
                        if ($message_data) {
                            $PhaseH_MSG[$hline]['Message_Data'] = $message_data;
                            continue;
                        }

                        // look for tags
                        $message_tag = strstr_after($msg_content, '[tag ', true);
                        if ($message_tag) {
                            $PhaseH_MSG[$hline]['Message_Tag'][] = getTagID($message_tag);
                            continue;
                        }

                        // look for severity
                        $message_severity = strstr_after($msg_content, '[severity ', true);
                        if ($message_severity) {
                            $PhaseH_MSG[$hline]['Message_Severity'] = array_search($message_severity, $severity);
                            continue;
                        }

                        // look for msg
                        $message_Msg = strstr_after($msg_content, '[msg ', true);
                        if ($message_Msg) {
                            $PhaseH_MSG[$hline]['Message_Msg'] = $message_Msg;

                            // Get Scores from msg
                            if (preg_match('/Inbound Anomaly Score \(Total\sInbound\sScore:\s?(?P<In_Total>[\d]{1,4})?,\sSQLi=(?P<In_SQLi>[\d]{1,4})?,\s?XSS=(?P<In_XSS>[\d]{1,4})?/i', $message_Msg, $score)) {
                                if (isset($score['In_Total']) AND $score['In_Total'] > $PhaseH['Score']['In_Total']) {
                                    $PhaseH['Score']['In_Total'] = $score['In_Total'];
                                }
                                if (isset($score['In_SQLi']) AND $score['In_SQLi'] > $PhaseH['Score']['In_SQLi']) {
                                    $PhaseH['Score']['In_SQLi'] = $score['In_SQLi'];
                                }
                                if (isset($score['In_XSS']) AND $score['In_XSS'] > $PhaseH['Score']['In_XSS']) {
                                    $PhaseH['Score']['In_XSS'] = $score['In_XSS'];
                                }
                            } elseif (preg_match('/Inbound Anomaly Score Exceeded \(Total\sScore:\s?(?P<In_Total>[\d]{1,4})?,\sSQLi=(?P<In_SQLi>[\d]{1,4})?,\s?XSS=(?P<In_XSS>[\d]{1,4})?/i', $message_Msg, $score)) {
                                if (isset($score['In_Total']) AND $score['In_Total'] > $PhaseH['Score']['In_Total']) {
                                    $PhaseH['Score']['In_Total'] = $score['In_Total'];
                                }
                                if (isset($score['In_SQLi']) AND $score['In_SQLi'] > $PhaseH['Score']['In_SQLi']) {
                                    $PhaseH['Score']['In_SQLi'] = $score['In_SQLi'];
                                }
                                if (isset($score['In_XSS']) AND $score['In_XSS'] > $PhaseH['Score']['In_XSS']) {
                                    $PhaseH['Score']['In_XSS'] = $score['In_XSS'];
                                }
                            } elseif (preg_match('/Anomaly Score Exceeded \(score (?P<In_Total>\d{1,10})\):\s?(?P<trigger>.+)/i', $message_Msg, $score)) {
                                if (isset($score['In_Total']) AND $score['In_Total'] > $PhaseH['Score']['In_Total']) {
                                    $PhaseH['Score']['In_Total'] = $score['In_Total'];
                                }
                            }
                            continue;
                        }
                    }
                    $hline++;

                } elseif (preg_match('/^Apache-Error:\s(?:\[file\s\"([\w\/\-\.]+)\"\].?)?(?:\[line\s(\d+)\].?)?(?:\[level\s(\d+)\].?)?([\w\:\/\.\-,\?\=\s]+)?/i', trim($BODY[$line]), $matchesH)) {
                    $PhaseH['Apache_error-File']    = (isset($matchesH[1]) ? $matchesH[1] : null);
                    $PhaseH['Apache_error-Line']    = (isset($matchesH[2]) ? $matchesH[2] : null);
                    $PhaseH['Apache_error-Level']   = (isset($matchesH[3]) ? $matchesH[3] : null);
                    $PhaseH['Apache_error-Message'] = (isset($matchesH[4]) ? trim($matchesH[4]) : null);
                } elseif (preg_match('/^Action: Intercepted\s.*(\d)/i', trim($BODY[$line]), $matchesH)) {
                    $PhaseH['Interception_phase'] = (isset($matchesH[1]) ? $matchesH[1] : null);
                } elseif (preg_match('/^Stopwatch:\s(\d{16})\s([\d\-]+)\s\(([\d\-\*]+)\s([\d\-]+)\s([\d\-]+)\)/i', trim($BODY[$line]), $matchesH)) {
                    $PhaseH['Stopwatch_Timestamp']         = (isset($matchesH[1]) ? $matchesH[1] : null);  // number of microseconds since 00:00:00 january 1, 1970 UTC
                    $PhaseH['Stopwatch_Duration']          = (isset($matchesH[2]) ? $matchesH[2] : null);
                    $PhaseH['Stopwatch_time_checkpoint_1'] = (isset($matchesH[3]) ? $matchesH[3] : null);
                    $PhaseH['Stopwatch_time_checkpoint_2'] = (isset($matchesH[4]) ? $matchesH[4] : null);
                    $PhaseH['Stopwatch_time_checkpoint_3'] = (isset($matchesH[5]) ? $matchesH[5] : null);
                } elseif (preg_match('/^Stopwatch2:\s(\d{16})\s([\d\-]+);\scombined=(\d+),\sp1=(\d+),\sp2=(\d+),\sp3=(\d+),\sp4=(\d+),\sp5=(\d+),\ssr=(\d+),\ssw=(\d+),\sl=(\d+),\sgc=(\d+)$/i', trim($BODY[$line]), $matchesH)) {
                    $PhaseH['Stopwatch2_Timestamp']         = (isset($matchesH[1]) ? $matchesH[1] : null);  // number of microseconds since 00:00:00 january 1, 1970 UTC
                    $PhaseH['Stopwatch2_duration']          = (isset($matchesH[2]) ? $matchesH[2] : null);
                    $PhaseH['Stopwatch2_combined'] = (isset($matchesH[3]) ? $matchesH[3] : null);  // combined processing
                    $PhaseH['Stopwatch2_p1'] = (isset($matchesH[4]) ? $matchesH[4] : null);  // phase 1 duration
                    $PhaseH['Stopwatch2_p2'] = (isset($matchesH[5]) ? $matchesH[5] : null);  // phase 2 duration
                    $PhaseH['Stopwatch2_p3'] = (isset($matchesH[6]) ? $matchesH[6] : null);  // phase 3 duration
                    $PhaseH['Stopwatch2_p4'] = (isset($matchesH[7]) ? $matchesH[7] : null);  // phase 4 duration
                    $PhaseH['Stopwatch2_p5'] = (isset($matchesH[8]) ? $matchesH[8] : null);  // phase 5 duration
                    $PhaseH['Stopwatch2_sr'] = (isset($matchesH[9]) ? $matchesH[9] : null);  // persistent storage read duration
                    $PhaseH['Stopwatch2_sw'] = (isset($matchesH[10]) ? $matchesH[10] : null); // persistent storage write duration
                    $PhaseH['Stopwatch2_l'] = (isset($matchesH[11]) ? $matchesH[11] : null);  // time spent on audit log
                    $PhaseH['Stopwatch2_gc'] = (isset($matchesH[12]) ? $matchesH[12] : null);  // time spend on garbage collection
                } elseif (preg_match('/^(?:Producer|WAF):\s(.+\.)$/i', trim($BODY[$line]), $matchesH)) {
                    if (preg_match('/(.+);\s(.+)\.$/i', $matchesH[1], $prod)) {
                        $PhaseH['Producer']         = (isset($prod[1]) ? $prod[1] : null);
                        $PhaseH['Producer_ruleset'] = (isset($prod[2]) ? $prod[2] : null);
                    } else {
                        $PhaseH['Producer']         = (isset($matchesH[1]) ? $matchesH[1] : null);
                        $PhaseH['Producer_ruleset'] = null;
                    }
                } elseif (preg_match('/^Server:\s(.+)/i', trim($BODY[$line]), $matchesH)) {
                    $PhaseH['Server'] = (isset($matchesH[1]) ? $matchesH[1] : null);
                } elseif (preg_match('/^WebApp-Info:\s\"(.+)\"\s\"(.+)\"\s\"(.+)\"/i', trim($BODY[$line]), $matchesH)) {
                    $PhaseH['WebApp-Info_Application_ID'] = (isset($matchesH[1]) ? $matchesH[1] : null);
                    $PhaseH['WebApp-Info_Session_ID']     = (isset($matchesH[2]) ? $matchesH[2] : null);
                    $PhaseH['WebApp-Info_User_ID']        = (isset($matchesH[3]) ? $matchesH[3] : null);
                } elseif (preg_match('/^Apache-Handler:\s(.+)/i', trim($BODY[$line]), $matchesH)) {
                    $PhaseH['Apache-Handler'] = (isset($matchesH[1]) ? $matchesH[1] : null);
                } elseif (preg_match('/^Response-Body-Transformed:\s(.+)/i', trim($BODY[$line]), $matchesH)) {
                    $PhaseH['Response-Body-Transformed'] = (isset($matchesH[1]) ? $matchesH[1] : null);
                } elseif (preg_match('/^Engine-Mode:\s"(\S+)"/i', trim($BODY[$line]), $matchesH)) {
                    $PhaseH['Engine_Mode'] = (isset($matchesH[1]) ? strtoupper($matchesH[1]) : null);
                }
                $PhaseH_full = $PhaseH_full . $BODY[$line];
                $line++;
            }
        }
    }

    // Phase I
    if (preg_match('/^\-\-[a-f0-9]+\-I\-\-$/i', trim($BODY[$line]))) {
        $PhaseI_full = null;
        while ( $line < $BodySize ) {
            if (preg_match('/^\-\-[a-f0-9]+\-[ABCEFHKZ]\-\-$/i', trim($BODY[$line]))) {
                break;
            } else {
                $PhaseI_full = $PhaseI_full . $BODY[$line];
                $line++;
            }
        }
    }

    // Phase K
    if (preg_match('/^\-\-[a-f0-9]+\-K\-\-$/i', trim($BODY[$line]))) {
        $PhaseK_full = null;
        while ( $line < $BodySize ) {
            if (preg_match('/^\-\-[a-f0-9]+\-[ABCEFHIZ]\-\-$/i', trim($BODY[$line]))) {
                break;
            } else {
                $PhaseK_full = $PhaseK_full . $BODY[$line];
                $line++;
            }
        }
    }


    //Phase: Z (the end)
    if (preg_match('/^\-\-[a-f0-9]+\-Z\-\-$/i', trim($BODY[$line]))) {
        $PhaseZ_full = null;
        while ( $line < $BodySize ) {
            if (preg_match('/^\-\-[a-f0-9]+\-[ABCEFHIK]\-\-$/i', trim($BODY[$line]))) {
                break;
            } else {
                $PhaseZ_full = $PhaseZ_full . $BODY[$line];
                $line++;
            }
        }
    }
    // Match phases not yet implemented
    if (preg_match('/^\-\-[a-f0-9]+\-[^ABCEFHIKZ]\-\-$/i', trim($BODY[$line]))) {
        while ( $line < $BodySize ) {
            if (preg_match('/^\-\-[a-f0-9]+\-[ABCEFHIKZ]\-\-$/i', trim($BODY[$line]))) {
                break;
            } else {
                $line++;
            }
        }
    }
}


// Set a mark in RelevantOnly events trapped by sensor but not trapped by a rule
if (!isset($PhaseH_MSG)) {
    $PhaseH['Message_Severity'] = 99;
    $PhaseH['Message_Tag']      = "TRANSACTION";
} else {
    $PhaseH_MSG = array_values(arrayUnique($PhaseH_MSG));
    foreach ($PhaseH_MSG as $msg_severity) {
        if ( !isset($PhaseH['Message_Severity'])) {
            if (isset($msg_severity['Message_Severity'])) {
                $PhaseH['Message_Severity'] = $msg_severity['Message_Severity'];
            }
        } elseif (isset($msg_severity['Message_Severity']) AND $PhaseH['Message_Severity'] > $msg_severity['Message_Severity']) {
            $PhaseH['Message_Severity'] = $msg_severity['Message_Severity'];
        }
    }
}

// Set event as Pass (99) when no Interception_phase is defined, Pass can be a Action "pass, allowed" or can be "Detection Only"
if (!isset($PhaseH['Interception_phase'])) {
    $PhaseH['Interception_phase'] = 99;
}

// Hack to avoid handle IPv6 by now

if ($PhaseA['ClientIP'] == "" OR $PhaseA['ServerIP'] == "") {
    header("HTTP/1.1 200 Ok");
    header("Status: 200");
    print "\nIPv6 not supported by now, sorry\n";

    exit();
}

// Insert event in database
$sql_event = 'INSERT INTO `events` (`event_id`, `sensor_id`, `a_timestamp`, `a_timezone`,`a_date`,`a_uniqid`, `a_client_ip`,`a_client_ip_cc`,`a_client_ip_asn`, `a_client_port`, `a_server_ip`, `a_server_port`, `b_method`, `b_path`,`b_path_parameter`,`b_protocol`, `b_host`, `b_user_agent`, `b_referer`, `f_protocol`, `f_status`, `f_msg`,`f_content_length`, `f_connection`, `f_content_type`, `h_apache_error_file`, `h_apache_error_line`,`h_apache_error_level`, `h_apache_error_message`, `h_stopwatch_timestamp`, `h_stopwatch_duration`,`h_stopwatch_time_checkpoint_1`, `h_stopwatch_time_checkpoint_2`, `h_stopwatch_time_checkpoint_3`,  `h_stopwatch2_Timestamp`, `h_stopwatch2_duration`, `h_stopwatch2_combined`, `h_stopwatch2_p1`, `h_stopwatch2_p2`, `h_stopwatch2_p3`, `h_stopwatch2_p4`, `h_stopwatch2_p5`, `h_stopwatch2_sr`, `h_stopwatch2_sw`, `h_stopwatch2_l`, `h_stopwatch2_gc`, `h_producer`,`h_producer_ruleset`, `h_server`, `h_wa_info_app_id`, `h_wa_info_sess_id`, `h_wa_info_user_id`, `h_apache_handler`,`h_response_body_transf`,`h_severity`,`h_action_status`,`h_action_status_msg`,`h_engine_mode`,`h_score_total`,`h_score_SQLi`,`h_score_XSS`,`h_Interception_phase`) VALUES (NULL, :sensorid, :PhaseATimestamp, :PhaseATimezone, :PhaseADate, :PhaseAUniqID, INET_ATON(:PhaseAClientIP),:PhaseAClientIPCC, :PhaseAClientIPASN, :PhaseASourcePort, INET_ATON(:PhaseAServerIP), :PhaseAServerPort,  :PhaseBMethod, :PhaseBPath, :PhaseBPathParameter, :PhaseBProtocol, :PhaseBHost, :PhaseBUserAgent, :PhaseBReferer, :PhaseFProtocol, :PhaseFStatus, :PhaseFMSG, :PhaseFContentLength, :PhaseFConnection, :PhaseFContentType, :PhaseHApacheerrorFile, :PhaseHApacheerrorLine, :PhaseHApacheerrorLevel, :PhaseHApacheerrorMessage, :PhaseHStopwatchTimestamp, :PhaseHStopwatchDuration, :PhaseHStopwatchtimecheckpoint1, :PhaseHStopwatchtimecheckpoint2, :PhaseHStopwatchtimecheckpoint3, :PhaseHStopwatch2_Timestamp, :PhaseHStopwatch2_duration, :PhaseHStopwatch2_combined, :PhaseHStopwatch2_p1,  :PhaseHStopwatch2_p2, :PhaseHStopwatch2_p3, :PhaseHStopwatch2_p4, :PhaseHStopwatch2_p5, :PhaseHStopwatch2_sr, :PhaseHStopwatch2_sw, :PhaseHStopwatch2_l, :PhaseHStopwatch2_gc, :PhaseHProducer, :PhaseHProducerruleset, :PhaseHServer, :PhaseHWebAppInfoApplicationID, :PhaseHWebAppInfoSessionID, :PhaseHWebAppInfoUserID, :PhaseHApacheHandler, :PhaseHResponseBodyTransformed, :PhaseHSeverity, :PhaseHActionStatus, :PhaseHActionStatusMsg, :PhaseHEngineMode, :PhaseHScoreInTotal, :PhaseHScoreInSQLi, :PhaseHScoreInXSS, :PhaseHInterception_phase)';

if (!isset($PhaseB['Method']) || is_null($PhaseB['Method'])) {
    $PhaseB['Method'] = "";
};
if (!isset($PhaseB['path']) || is_null($PhaseB['path'])) {
    $PhaseB['path'] = "";
};
if (!isset($PhaseB['pathParameter']) || is_null($PhaseB['pathParameter'])) {
    $PhaseB['pathParameter'] = "";
};
if (!isset($PhaseB['Protocol']) || is_null($PhaseB['Protocol'])) {
    $PhaseB['Protocol'] = "";
};
if (!isset($PhaseB['Host']) || is_null($PhaseB['Host'])) {
    $PhaseB['Host'] = "";
};
if (!isset($PhaseB['User-Agent']) || is_null($PhaseB['User-Agent'])) {
    $PhaseB['User-Agent'] = "";
};
if (!isset($PhaseB['Referer']) || is_null($PhaseB['Referer'])) {
    $PhaseB['Referer'] = "";
};
if (!isset($PhaseF['Content-Length']) || is_null($PhaseF['Content-Length'])) {
    $PhaseF['Content-Length'] = "";
};
if (!isset($PhaseF['Connection']) || is_null($PhaseF['Connection'])) {
    $PhaseF['Connection'] = "";
};
if (!isset($PhaseF['Content-Type']) || is_null($PhaseF['Content-Type'])) {
    $PhaseF['Content-Type'] = "";
};
if (!isset($PhaseF['MSG']) || is_null($PhaseF['MSG'])) {
    $PhaseF['MSG'] = "";
};
if (!isset($PhaseF['Protocol']) || is_null($PhaseF['Protocol'])) {
    $PhaseF['Protocol'] = "";
};
if (!isset($PhaseF['Status']) || is_null($PhaseF['Protocol'])) {
    $PhaseF['Status'] = "";
};
if (!isset($PhaseH['Apache_error-File']) || is_null($PhaseH['Apache_error-File'])) {
    $PhaseH['Apache_error-File'] = "";
};
if (!isset($PhaseH['Apache_error-Line']) || is_null($PhaseH['Apache_error-Line'])) {
    $PhaseH['Apache_error-Line'] = "";
};
if (!isset($PhaseH['Apache_error-Level']) || is_null($PhaseH['Apache_error-Level'])) {
    $PhaseH['Apache_error-Level'] = "";
};
if (!isset($PhaseH['Apache_error-Message']) || is_null($PhaseH['Apache_error-Message'])) {
    $PhaseH['Apache_error-Message'] = "";
};
if (!isset($PhaseH['WebApp-Info_Application_ID']) || is_null($PhaseH['WebApp-Info_Application_ID'])) {
    $PhaseH['WebApp-Info_Application_ID'] = "";
};
if (!isset($PhaseH['WebApp-Info_Session_ID']) || is_null($PhaseH['WebApp-Info_Session_ID'])) {
    $PhaseH['WebApp-Info_Session_ID'] = "";
};
if (!isset($PhaseH['WebApp-Info_User_ID']) || is_null($PhaseH['WebApp-Info_User_ID'])) {
    $PhaseH['WebApp-Info_User_ID'] = "";
};
if (!isset($PhaseH['Apache-Handler']) || is_null($PhaseH['Apache-Handler'])) {
    $PhaseH['Apache-Handler'] = "";
};
if (!isset($PhaseH['Response-Body-Transformed']) || is_null($PhaseH['Response-Body-Transformed'])) {
    $PhaseH['Response-Body-Transformed'] = "";
};
if (!isset($PhaseH['ActionStatus']) || is_null($PhaseH['ActionStatus'])) {
    $PhaseH['ActionStatus'] = "20"; // Action not defined, we suppose that was a warning
};
if (!isset($PhaseH['ActionStatusMsg']) || is_null($PhaseH['ActionStatusMsg'])) {
    $PhaseH['ActionStatusMsg'] = "Warning"; // Action message not defined, we suppose that was a warning
};
if (!isset($PhaseH['Engine_Mode']) || is_null($PhaseH['Engine_Mode'])) {
    $PhaseH['Engine_Mode'] = "";
};
if (!isset($PhaseH['Score']['In_Total']) || is_null($PhaseH['Score']['In_Total'])) {
    $PhaseH['Score']['In_Total'] = "";
};
if (!isset($PhaseH['Score']['In_SQLi']) || is_null($PhaseH['Score']['In_SQLi'])) {
    $PhaseH['Score']['In_SQLi'] = "";
};
if (!isset($PhaseH['Score']['In_XSS']) || is_null($PhaseH['Score']['In_XSS'])) {
    $PhaseH['Score']['In_XSS'] = "";
};
if (!isset($PhaseH['Message_Severity']) || is_null($PhaseH['Message_Severity'])) {
    $PhaseH['Message_Severity'] = "99";
};

try {

    $insert_sth = $dbconn->prepare($sql_event);
    $insert_sth->bindParam(":sensorid", $sensor_id);
    $insert_sth->bindParam(":PhaseATimestamp", $PhaseA['Timestamp']);
    $insert_sth->bindParam(":PhaseATimezone", $PhaseA['Timezone']);
    $insert_sth->bindParam(":PhaseADate", $PhaseA['Date']);
    $insert_sth->bindParam(":PhaseAUniqID", $PhaseA['UniqID']);
    $insert_sth->bindParam(":PhaseAClientIP", $PhaseA['ClientIP']);
    // Get Country Code of IP Address
    $ClientIPCC = geoip_country_code_by_name($PhaseA['ClientIP']);
    if (!$ClientIPCC) {
       $ClientIPCC = '';
    }
    // Get Country Code of IP ASN
    $ClientIPASN = str_ireplace('AS', "", strstr(geoip_isp_by_name($PhaseA['ClientIP']), ' ', true));
    if (!$ClientIPASN) {
       $ClientIPASN = '0';
    } elseif ( $ClientIPASN == "") {
       $ClientIPASN = '0';
    }
    $insert_sth->bindParam(":PhaseAClientIPCC", $ClientIPCC);
    $insert_sth->bindParam(":PhaseAClientIPASN", $ClientIPASN);
    $insert_sth->bindParam(":PhaseASourcePort", $PhaseA['SourcePort']);
    $insert_sth->bindParam(":PhaseAServerIP", $PhaseA['ServerIP']);
    $insert_sth->bindParam(":PhaseAServerPort", $PhaseA['ServerPort']);
    $insert_sth->bindParam(":PhaseBMethod", $PhaseB['Method']);
    $insert_sth->bindParam(":PhaseBPath", $PhaseB['path']);
    $insert_sth->bindParam(":PhaseBPathParameter", $PhaseB['pathParameter']);
    $webHostID = getWebHostID($PhaseB['Host']);    
    $insert_sth->bindParam(":PhaseBProtocol", $PhaseB['Protocol']);
    $insert_sth->bindParam(":PhaseBHost", $webHostID);
    $insert_sth->bindParam(":PhaseBUserAgent", $PhaseB['User-Agent']);
    $insert_sth->bindParam(":PhaseBReferer", $PhaseB['Referer']);
    $insert_sth->bindParam(":PhaseFProtocol", $PhaseF['Protocol']);
    $insert_sth->bindParam(":PhaseFStatus", $PhaseF['Status']);
    $insert_sth->bindParam(":PhaseFMSG", $PhaseF['MSG']);
    $insert_sth->bindParam(":PhaseFContentLength", $PhaseF['Content-Length']);
    $insert_sth->bindParam(":PhaseFConnection", $PhaseF['Connection']);
    $insert_sth->bindParam(":PhaseFContentType", $PhaseF['Content-Type']);
    $insert_sth->bindParam(":PhaseHApacheerrorFile", $PhaseH['Apache_error-File']);
    $insert_sth->bindParam(":PhaseHApacheerrorLine", $PhaseH['Apache_error-Line']);
    $insert_sth->bindParam(":PhaseHApacheerrorLevel", $PhaseH['Apache_error-Level']);
    $insert_sth->bindParam(":PhaseHApacheerrorMessage", $PhaseH['Apache_error-Message']);
    $insert_sth->bindParam(":PhaseHStopwatchTimestamp", $PhaseH['Stopwatch_Timestamp']);
    $insert_sth->bindParam(":PhaseHStopwatchDuration", $PhaseH['Stopwatch_Duration']);
    $insert_sth->bindParam(":PhaseHStopwatchtimecheckpoint1", $PhaseH['Stopwatch_time_checkpoint_1']);
    $insert_sth->bindParam(":PhaseHStopwatchtimecheckpoint2", $PhaseH['Stopwatch_time_checkpoint_2']);
    $insert_sth->bindParam(":PhaseHStopwatchtimecheckpoint3", $PhaseH['Stopwatch_time_checkpoint_3']);

    $insert_sth->bindParam(":PhaseHStopwatch2_Timestamp", $PhaseH['Stopwatch2_Timestamp']);
    $insert_sth->bindParam(":PhaseHStopwatch2_duration", $PhaseH['Stopwatch2_duration']);
    $insert_sth->bindParam(":PhaseHStopwatch2_combined", $PhaseH['Stopwatch2_combined']);
    $insert_sth->bindParam(":PhaseHStopwatch2_p1", $PhaseH['Stopwatch2_p1']);
    $insert_sth->bindParam(":PhaseHStopwatch2_p2", $PhaseH['Stopwatch2_p2']);
    $insert_sth->bindParam(":PhaseHStopwatch2_p3", $PhaseH['Stopwatch2_p3']);
    $insert_sth->bindParam(":PhaseHStopwatch2_p4", $PhaseH['Stopwatch2_p4']);
    $insert_sth->bindParam(":PhaseHStopwatch2_p5", $PhaseH['Stopwatch2_p5']);
    $insert_sth->bindParam(":PhaseHStopwatch2_sr", $PhaseH['Stopwatch2_sr']);
    $insert_sth->bindParam(":PhaseHStopwatch2_sw", $PhaseH['Stopwatch2_sw']);
    $insert_sth->bindParam(":PhaseHStopwatch2_l", $PhaseH['Stopwatch2_l']);
    $insert_sth->bindParam(":PhaseHStopwatch2_gc", $PhaseH['Stopwatch2_gc']);    
    
    $insert_sth->bindParam(":PhaseHProducer", $PhaseH['Producer']);
    $insert_sth->bindParam(":PhaseHProducerruleset", $PhaseH['Producer_ruleset']);
    $insert_sth->bindParam(":PhaseHServer", $PhaseH['Server']);
    $insert_sth->bindParam(":PhaseHWebAppInfoApplicationID", $PhaseH['WebApp-Info_Application_ID']);
    $insert_sth->bindParam(":PhaseHWebAppInfoSessionID", $PhaseH['WebApp-Info_Session_ID']);
    $insert_sth->bindParam(":PhaseHWebAppInfoUserID", $PhaseH['WebApp-Info_User_ID']);
    $insert_sth->bindParam(":PhaseHApacheHandler", $PhaseH['Apache-Handler']);
    $insert_sth->bindParam(":PhaseHResponseBodyTransformed", $PhaseH['Response-Body-Transformed']);
    $insert_sth->bindParam(":PhaseHSeverity", $PhaseH['Message_Severity']);
    $insert_sth->bindParam(":PhaseHActionStatus", $PhaseH['ActionStatus']);
    $insert_sth->bindParam(":PhaseHActionStatusMsg", $PhaseH['ActionStatusMsg']);
    $insert_sth->bindParam(":PhaseHEngineMode", $PhaseH['Engine_Mode']);
    $insert_sth->bindParam(":PhaseHScoreInTotal", $PhaseH['Score']['In_Total']);
    $insert_sth->bindParam(":PhaseHScoreInSQLi", $PhaseH['Score']['In_SQLi']);
    $insert_sth->bindParam(":PhaseHScoreInXSS", $PhaseH['Score']['In_XSS']);
    $insert_sth->bindParam(":PhaseHInterception_phase", $PhaseH['Interception_phase']);

    // Execute the query
    $insert_sth->execute();
    $event_id = $dbconn->lastInsertId();
} catch (PDOException $e) {
    header("HTTP/1.1 500 Internal Server Error");
    header("Status: 500");
    print "HTTP/1.1 500 Internal Server Error \n";
    if ($MLOG2WAFFLE_DEBUG) {
        print "Error (insert events) Message: " . $e->getMessage() . "\n";
        print "Error (insert events) getTraceAsString: " . $e->getTraceAsString() . "\n";
    }    
        print "Error (insert events) Message: " . $e->getMessage() . "\n";
        print "Error (insert events) getTraceAsString: " . $e->getTraceAsString() . "\n";

    exit();
}

// Insert event full section in database
$sql_eventFullSections = 'INSERT INTO `events_full_sections` (`event_id`, `a_full`, `b_full`, `c_full`, `e_full`, `f_full`, `h_full`, `i_full`, `k_full`, `z_full`, `compressed`) VALUES (:eventid, :PhaseAfull, :PhaseBfull, :PhaseCfull, :PhaseEfull, :PhaseFfull, :PhaseHfull, :PhaseIfull, :PhaseKfull, :PhaseZfull, :Compressed)';

if (!isset($PhaseC_full) || is_null($PhaseC_full)) {
    $PhaseC_full = "";
};
if (!isset($PhaseE_full) || is_null($PhaseE_full)) {
    $PhaseE_full = "";
};

if (!isset($PhaseH_full) || is_null($PhaseH_full)) {
    $PhaseH_full = "";
};
if (!isset($PhaseI_full) || is_null($PhaseI_full)) {
    $PhaseI_full = "";
};
if (!isset($PhaseK_full) || is_null($PhaseK_full)) {
    $PhaseK_full = "";
};

try {

    $PhaseA_fullCompress = $COMPRESSION ? gzcompress($PhaseA_full): $PhaseA_full;
    $PhaseB_fullCompress = $COMPRESSION ? gzcompress($PhaseB_full): $PhaseB_full;
    $PhaseC_fullCompress = $COMPRESSION ? gzcompress($PhaseC_full): $PhaseC_full;
    $PhaseE_fullCompress = $COMPRESSION ? gzcompress($PhaseE_full): $PhaseE_full;
    $PhaseF_fullCompress = $COMPRESSION ? gzcompress($PhaseF_full): $PhaseF_full;
    $PhaseH_fullCompress = $COMPRESSION ? gzcompress($PhaseH_full): $PhaseH_full;
    $PhaseI_fullCompress = $COMPRESSION ? gzcompress($PhaseI_full): $PhaseI_full;
    $PhaseK_fullCompress = $COMPRESSION ? gzcompress($PhaseK_full): $PhaseK_full;
    
    
    $insertFull_sth = $dbconn->prepare($sql_eventFullSections);
    $insertFull_sth->bindParam(":eventid", $event_id);
    $insertFull_sth->bindParam(":PhaseAfull", $PhaseA_fullCompress);
    $insertFull_sth->bindParam(":PhaseBfull", $PhaseB_fullCompress);
    $insertFull_sth->bindParam(":PhaseCfull", $PhaseC_fullCompress);
    $insertFull_sth->bindParam(":PhaseEfull", $PhaseE_fullCompress);
    $insertFull_sth->bindParam(":PhaseFfull", $PhaseF_fullCompress);
    $insertFull_sth->bindParam(":PhaseHfull", $PhaseH_fullCompress);
    $insertFull_sth->bindParam(":PhaseIfull", $PhaseI_fullCompress);
    $insertFull_sth->bindParam(":PhaseKfull", $PhaseK_fullCompress);
    $insertFull_sth->bindParam(":PhaseZfull", $PhaseZ_full);
    $insertFull_sth->bindParam(":Compressed", $COMPRESSION);

    // Execute the query
    $insertFull_sth->execute();

} catch (PDOException $e) {
    header("HTTP/1.1 500 Internal Server Error");
    header("Status: 500");
    print "HTTP/1.1 500 Internal Server Error \n";
    if ($MLOG2WAFFLE_DEBUG) {
        print "Error (insert events) Message: " . $e->getMessage() . "\n";
        print "Error (insert events) getTraceAsString: " . $e->getTraceAsString() . "\n";
    }    
    exit();
}

if (is_array($PhaseH_MSG) && $event_id != "") {

    $sql_event_message = 'INSERT INTO `events_messages` (`event_id`, `h_message_pattern`, `h_message_action`, `h_message_ruleFile`,`h_message_ruleLine`, `h_message_ruleId`, `h_message_ruleData`, `h_message_ruleSeverity`) VALUES (:eventid,  :MessagePattern, :MessageAction, :MessageFile, :MessageLine, :MessageRuleId, :MessageData, :MessageSeverity)';
    $insert_msg_sth = $dbconn->prepare($sql_event_message);

    foreach ($PhaseH_MSG as $msg) {
        if (!isset($msg['Message_Pattern']) || is_null($msg['Message_Pattern'])) {
            $msg['Message_Pattern'] = "";
        }
        if (!isset($msg['Message_Action']) || is_null($msg['Message_Action'])) {
            $msg['Message_Action'] = "";
        }
        if (!isset($msg['Message_File']) || is_null($msg['Message_File'])) {
            $msg['Message_File'] = "";
        }
        if (!isset($msg['Message_Line']) || is_null($msg['Message_Line'])) {
            $msg['Message_Line'] = "";
        }
        if (!isset($msg['Message_RuleId']) || is_null($msg['Message_RuleId'])) {
            $msg['Message_RuleId'] = "";
        }
        if (!isset($msg['Message_Msg']) || is_null($msg['Message_Msg'])) {
            $msg['Message_Msg'] = "";
        }
        if (!isset($msg['Message_Data']) || is_null($msg['Message_Data'])) {
            $msg['Message_Data'] = "";
        }
        if (!isset($msg['Message_Severity']) || is_null($msg['Message_Severity'])) {
            $msg['Message_Severity'] = 99;
        }

        try {
            $insert_msg_sth->bindParam(":eventid", $event_id);
            $insert_msg_sth->bindParam(":MessagePattern", $msg['Message_Pattern']);
            $insert_msg_sth->bindParam(":MessageAction", $msg['Message_Action']);
            $insert_msg_sth->bindParam(":MessageFile", $msg['Message_File']);
            $insert_msg_sth->bindParam(":MessageLine", $msg['Message_Line']);
            $insert_msg_sth->bindParam(":MessageRuleId", $msg['Message_RuleId']);
            $insert_msg_sth->bindParam(":MessageData", $msg['Message_Data']);
            $insert_msg_sth->bindParam(":MessageSeverity", $msg['Message_Severity']);

            // Execute the query
            $insert_msg_sth->execute();
            $msg_id = $dbconn->lastInsertId();
            $insertStatus = $insert_msg_sth->errorCode();

            // Insert message tag
            if ($insertStatus == 0) {
                if (isset($msg['Message_Tag']) && is_array($msg['Message_Tag'])) {

                    $sql_messageTag = 'INSERT INTO `events_messages_tag` (`msg_id`, `h_message_tag`) VALUES (:msg_id, :tag)';
                    $insert_msgtag_sth = $dbconn->prepare($sql_messageTag);

                    foreach ($msg['Message_Tag'] as $tag) {
                        try {
                            $insert_msgtag_sth->bindParam(":msg_id", $msg_id, PDO::PARAM_INT);
                            $insert_msgtag_sth->bindParam(":tag", $tag, PDO::PARAM_INT);
                            // Execute the query
                            $insert_msgtag_sth->execute();
                        } catch (PDOException $e) {
                            header("HTTP/1.1 500 Internal Server Error");
                            header("Status: 500");
                            print "HTTP/1.1 500 Internal Server Error \n";
                               if ($MLOG2WAFFLE_DEBUG) {
                                  print "Error (insert events) Message: " . $e->getMessage() . "\n";
                                  print "Error (insert events) getTraceAsString: " . $e->getTraceAsString() . "\n";
                               }     
                            exit();
                        }
                    }
				}
				
				$sql_ruleMessage = 'INSERT IGNORE INTO `rule_message` (`message_ruleId`, `message_ruleMsg`) VALUES (:MessageRuleId2, :MessageMsg)';
				$insert_ruleMessage_sth = $dbconn->prepare($sql_ruleMessage);

				try {
					$insert_ruleMessage_sth->bindParam(":MessageRuleId2", $msg['Message_RuleId']);
					$insert_ruleMessage_sth->bindParam(":MessageMsg", $msg['Message_Msg']);
					// Execute the query
					$insert_ruleMessage_sth->execute();
				} catch (PDOException $e) {
					header("HTTP/1.1 500 Internal Server Error");
					header("Status: 500");
					print "HTTP/1.1 500 Internal Server Error \n";
					   if ($MLOG2WAFFLE_DEBUG) {
						  print "Error (insert events) Message: " . $e->getMessage() . "\n";
						  print "Error (insert events) getTraceAsString: " . $e->getTraceAsString() . "\n";
					   }     
					exit();
				}
            }
        } catch (PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            header("Status: 500");
            print "HTTP/1.1 500 Internal Server Error \n";
            if ($MLOG2WAFFLE_DEBUG) {
                print "Error (insert events) Message: " . $e->getMessage() . "\n";
                print "Error (insert events) getTraceAsString: " . $e->getTraceAsString() . "\n";
            }     
            exit();
        }
    }
}

// if we arrive here, we have inserted all records in database... congrats. Exiting with status 200.

?>
