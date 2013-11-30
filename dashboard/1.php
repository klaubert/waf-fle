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

// Force display errors to off
ini_set('display_errors', 1);
// Print errors in php log (normally apache errors log), but not notice logs
error_reporting(E_ALL);

$line     = 0;
$BODYInput =<<<EOF123
--f9b52770-A--
[29/Oct/2012:23:47:10 --0200] 9ttP238AAAEAAAv8EOwAAAAF 192.168.56.1 5501 192.168.56.110 80
--f9b52770-B--
GET /teste/?hiddenvalor0=%3Cfont+color%3D%27%230066FF%27%3E%3Cb%3E%28duzentos+e+sessenta+e+dois+reais+e+vinte+e+sete+centavos%29%3C%2Fb%3E%3C%2Ffont%3E&valor0=262%2C27 HTTP/1.1
Host: 192.168.56.110
Connection: keep-alive
Cache-Control: max-age=0
User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
Accept-Encoding: gzip,deflate,sdch
Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.6,en;q=0.4
Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3
Cookie: PHPSESSID=ejki7o9qtbbh5ren3dhk60rns5

--f9b52770-F--
HTTP/1.1 404 Not Found
Content-Length: 283
Connection: close
Content-Type: text/html; charset=iso-8859-1

--f9b52770-H--
Message: Pattern match "^[\d.:]+$" at REQUEST_HEADERS:Host. [file "/etc/httpd/modsecurity.d/modsecurity_crs_21_protocol_anomalies.conf"] [line "97"] [id "960017"] [rev "2.1.1"] [msg "Host header is a numeric IP address"] [severity "CRITICAL"] [tag "PROTOCOL_VIOLATION/IP_HOST"] [tag "WASCTC/WASC-21"] [tag "OWASP_TOP_10/A7"] [tag "PCI/6.5.10"] [tag "http://technet.microsoft.com/en-us/magazine/2005.01.hackerbasher.aspx"]
Message: Pattern match "<(a|abbr|acronym|address|applet|area|audioscope|b|base|basefront|bdo|bgsound|big|blackface|blink|blockquote|body|bq|br|button|caption|center|cite|code|col|colgroup|comment|dd|del|dfn|dir|div|dl|dt|em|embed|fieldset|fn|font|form|frame|frameset|h1|head|h ..." at ARGS:hiddenvalor0. [file "/etc/httpd/modsecurity.d/modsecurity_crs_41_xss_attacks.conf"] [line "555"] [id "973300"] [rev "2.1.1"] [msg "Possible XSS Attack Detected - HTML Tag Handler"] [data "<font "]
Message: Pattern match "(?i:["\'][ ]*(([^a-z0-9~_:\'" ])|(in)).+?\(.*?\))" at ARGS:hiddenvalor0. [file "/etc/httpd/modsecurity.d/modsecurity_crs_41_xss_attacks.conf"] [line "764"] [id "973335"] [rev "2.1.1"] [msg "IE XSS Filters - Attack Detected"] [data "'#0066FF'><b>(duzentos e sessenta e dois reais e vinte e sete centavos)"]
Message: Warning. Pattern match "(.*)" at TX:960017-POLICY/IP_HOST-REQUEST_HEADERS:Host. [file "/etc/httpd/modsecurity.d/modsecurity_crs_49_inbound_blocking.conf"] [line "18"] [msg "Inbound Anomaly Score Exceeded (Total Score: 12, SQLi=, XSS=10): Last Matched Message: IE XSS Filters - Attack Detected"] [data "Last Matched Data: 192.168.56.110'"]
Message: Warning. Pattern match "(.*)" at TX:0. [file "/etc/httpd/modsecurity.d/modsecurity_crs_49_inbound_blocking.conf"] [line "18"] [msg "Inbound Anomaly Score Exceeded (Total Score: 12, SQLi=, XSS=10): Last Matched Message: IE XSS Filters - Attack Detected"] [data "Last Matched Data: '#0066FF'><b>(duzentos e sessenta e dois reais e vinte e sete centavos)"]
Message: Warning. Pattern match "(.*)" at TX:1. [file "/etc/httpd/modsecurity.d/modsecurity_crs_49_inbound_blocking.conf"] [line "18"] [msg "Inbound Anomaly Score Exceeded (Total Score: 12, SQLi=, XSS=10): Last Matched Message: IE XSS Filters - Attack Detected"] [data "Last Matched Data: #"]
Message: Warning. Pattern match "(.*)" at TX:973300-WEB_ATTACK/XSS-ARGS:hiddenvalor0. [file "/etc/httpd/modsecurity.d/modsecurity_crs_49_inbound_blocking.conf"] [line "18"] [msg "Inbound Anomaly Score Exceeded (Total Score: 12, SQLi=, XSS=10): Last Matched Message: IE XSS Filters - Attack Detected"] [data "Last Matched Data: <font "]
Message: Warning. Pattern match "(.*)" at TX:2. [file "/etc/httpd/modsecurity.d/modsecurity_crs_49_inbound_blocking.conf"] [line "18"] [msg "Inbound Anomaly Score Exceeded (Total Score: 12, SQLi=, XSS=10): Last Matched Message: IE XSS Filters - Attack Detected"] [data "Last Matched Data: #"]
Message: Warning. Pattern match "(.*)" at TX:973335-WEB_ATTACK/XSS-ARGS:hiddenvalor0. [file "/etc/httpd/modsecurity.d/modsecurity_crs_49_inbound_blocking.conf"] [line "18"] [msg "Inbound Anomaly Score Exceeded (Total Score: 12, SQLi=, XSS=10): Last Matched Message: IE XSS Filters - Attack Detected"] [data "Last Matched Data: '#0066FF'><b>(duzentos e sessenta e dois reais e vinte e sete centavos)"]
Apache-Error: [file "/builddir/build/BUILD/httpd-2.2.3/server/core.c"] [line 3638] [level 3] File does not exist: /var/www/html/teste
Stopwatch: 1351561630142427 4053 (385 3711 -)
Producer: ModSecurity for Apache/2.5.12 (http://www.modsecurity.org/); core ruleset/2.1.1.
Server: Apache/2.2.3 (CentOS)

--f9b52770-Z--


EOF123;

$BODY = explode("\n",$BODYInput);
// Body: read and treatment

$BodySize = count($BODY);

print "<pre>";

while ( $line < $BodySize) {
    if (preg_match('/WAF\-FLE\ PROBE/i', trim($BODY[$line]))) {
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
                if (preg_match('/\[(\d{1,2})\/(\w{3})\/(\d{4})\:(\d{2}\:\d{2}\:\d{2})\s(\-\-\d{4}|\+\d{4})\]\s([a-zA-Z0-9\-\@]{24})\s([12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2})\s(\d{1,5})\s([12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2}\.[12]?[0-9]{1,2})\s(\d{1,5})/i',
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
                if (preg_match('/(GET|POST|HEAD|PUT|DELETE|TRACE|PROPFIND|OPTIONS|CONNECT|PATCH)\s(.+)\s(HTTP\/[01]\.[019])/i', trim($BODY[$line]), $matchesB)) {
                    $PhaseB['Method']        = $matchesB[1];
                    $PhaseB['pathParameter'] = parse_url($matchesB[2], PHP_URL_QUERY);
                    // $pathParsed              = parse_url($matchesB[2], PHP_URL_PATH);
                    $PhaseB['path']          = parse_url($matchesB[2], PHP_URL_PATH);
                    $PhaseB['Protocol']      = $matchesB[3];
                } elseif (preg_match('/Host:\s(.+)/i', trim($BODY[$line]), $matchesB)) {
                    $PhaseB['Host'] = $matchesB[1];
                } elseif (preg_match('/Content-Type:\s([\w\-\/]+)\;\s([\w\-\;\.\/\*\+\=\:\?\,\s\(\)]+)/i', trim($BODY[$line]), $matchesB)) {
                    $PhaseB['Content-Type'] = $matchesB[1];
                } elseif (preg_match('/Referer:\s(.+)/i', trim($BODY[$line]), $matchesB)) {
                    $PhaseB['Referer'] = $matchesB[1];
                } elseif (preg_match('/User-Agent:\s(.+)/i', trim($BODY[$line]), $matchesB)) {
                    $PhaseB['User-Agent'] = $matchesB[1];
                } elseif (isset($clientIpHeaderRegExp) AND preg_match("/$clientIpHeaderRegExp/i", trim($BODY[$line]), $matchesB)) {
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
                if (preg_match('/(HTTP\/\d\.\d)\s(\d\d\d)\s([\w\s]+)/i', trim($BODY[$line]), $matchesF)) {
                    $PhaseF['Protocol'] = $matchesF[1];
                    $PhaseF['Status']   = $matchesF[2];
                    $PhaseF['MSG']      = $matchesF[3];
                } elseif (preg_match('/Content-Length:\s(\d+)/i', trim($BODY[$line]), $matchesF)) {
                    $PhaseF['Content-Length'] = $matchesF[1];
                } elseif (preg_match('/Connection:\s([\w-]+)/i', trim($BODY[$line]), $matchesF)) {
                    $PhaseF['Connection'] = $matchesF[1];
                } elseif (preg_match('/Content-Type:\s((?:[\w\-\/]+)(?:\;)?(?:\s)?(?:[\w\-\;\.\/\*\+\=\:\?\,\s\(\)]+)?)/i', trim($BODY[$line]), $matchesF)) {
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
                if (preg_match('/^Message:\s/i', trim($BODY[$line]))) {
				
                    // look for message Action
                    $message_start = 0;
                    $message_stop  = strpos(trim($BODY[$line]), ". ", $message_start);
                    $message_length = $message_stop - $message_start;
                    $action = substr(trim($BODY[$line]), $message_start, $message_length) . "\n";
                    $message_start = $message_stop;

                    if (preg_match('/^Message:\s(Warning|Access.+)/i', $action, $matchesH)) {
					
						if (isset($matchesH[1])) {
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
						}
                    } elseif (preg_match('/^Message:\s/i', $action, $matchesH)) {
					
						$message_start  = strpos(trim($BODY[$line]), " ", 0);
					} else {
                        $PhaseH_full = $PhaseH_full . $BODY[$line];
                        $line++;
                        continue;
                    }

                    // look for Pattern
                    $message_stop  = strpos(trim($BODY[$line]), " [", $message_start);
                    $message_length = $message_stop - $message_start;
                    $pattern =  substr(trim($BODY[$line]), $message_start, $message_length);
                    $PhaseH_MSG[$hline]['Message_Pattern']    = (isset($pattern) ? trim($pattern, " .") : null);
                    $message_start = $message_stop;
								
                    // look for metadata
                    while (true){
                        $message_start = strpos(trim($BODY[$line]), " [", $message_start);
                        if ($message_start === false) {
                            break;
                         }
						
                        $message_stop = strpos(trim($BODY[$line]), "] ", $message_start);
                        if ($message_stop === false) {
                            $message_stop = strpos(trim($BODY[$line]), "]", $message_start);
                            if ($message_stop === false) {
                                $message_stop = strlen(trim($BODY[$line]));
                            }
                        }
						$message_length = $message_stop - $message_start;

                        $msg_content = substr(trim($BODY[$line], "\n\r\t\0\x0B"), $message_start, $message_length);
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
                            } elseif (preg_match('/Inbound Anomaly Score Exceeded \(Total (?:Inbound)? Score:\s?(?P<In_Total>[\d]{1,4})?,\sSQLi=(?P<In_SQLi>[\d]{1,4})?,\s?XSS=(?P<In_XSS>[\d]{1,4})?/i', $message_Msg, $score)) {
								var_dump($score);
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
                } elseif (preg_match('/Action: Intercepted\s.*(\d)/i', trim($BODY[$line]), $matchesH)) {
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
                } elseif (preg_match('/^Producer:\s(.+\.)$/i', trim($BODY[$line]), $matchesH)) {
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

}

if (is_array($PhaseH_MSG)) {

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
    }
}

print "\n\npahgesh_msg\n";
var_dump($PhaseH_MSG);
print "pahgesh_msg end\n";
	
	
// if we arrive here, we have inserted all records in database... congrats. Exiting with status 200.

?>
