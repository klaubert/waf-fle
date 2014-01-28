<?PHP
require_once("../session.php");

// Filter parameters
// Reset eventCount cache if a new filter is made
if ((isset($_GET['p'])) AND (count($_GET) == 1) AND ($_GET['p'] != 1)) {
   // keep cache active
   $_SESSION['eventCount'];
} else {
   // clear event count cache
   unset($_SESSION['eventCount']);
}

// Delete events
if (isset($_GET['del'])) {
   $eventDel = @sanitize_int($_GET['del'], $min='0' );
   if ($eventDel) {
      $delResult = deleteEvent($eventDel);
      if (!$delResult) {
         print $delResult;
      }
   }
}

// Preserve the event
if (isset($_POST['action']) AND $_POST['action'] == "Preserve" AND $_POST['event'] != "") {
   foreach($_POST['event'] as $event2preserve) {
      $eventPreserve = @sanitize_int($event2preserve, $min='0' );
      if ($eventPreserve) {
         $preserveResult = preserveEvent($eventPreserve, 'Preserve');
         if (!$preserveResult) {
            print $preserveResult;
         }
      }
   }
}
// UnPreserve event
if (isset($_POST['action']) AND $_POST['action'] == "UnPreserve" AND $_POST['event'] != "") {
   foreach($_POST['event'] as $event2unpreserve) {
      $eventUnPreserve = @sanitize_int($event2unpreserve, $min='0' );
      if ($eventUnPreserve) {
         $eventUnPreserve = preserveEvent($eventUnPreserve, 'NotPreserve');
         if (!$eventUnPreserve) {
            print $eventUnPreserve;
         }
      }
   }
}

// Mark event as false positive
if (isset($_POST['action']) AND $_POST['action'] == "Mark" AND $_POST['event'] != "") {
   foreach($_POST['event'] as $event2fp) {
      $eventFalsePositive = @sanitize_int($event2fp, $min='0' );
      if ($eventFalsePositive) {
         $falsePositiveResult = falsePositiveEvent($eventFalsePositive, 'fp');
         if (!$falsePositiveResult) {
            print $falsePositiveResult;
         }
      }
   }
}

// Delete events
if (isset($_POST['action']) AND $_POST['action'] == "Delete" AND $_POST['event'] != "") {
   foreach($_POST['event'] as $event2del) {
      $eventDel = @sanitize_int($event2del, $min='0' );
      if ($eventDel) {
         $delResult = deleteEvent($eventDel);
         if (!$delResult) {
            print $delResult;
         }
      }
   }
}

// Start Filter processing
// Clear all Filter, set date for current day (at end)
if (isset($_GET['filter']) AND stristr($_GET['filter'], 'x')) {
   unset($_SESSION['filter']);
   unset($_SESSION['filterIndexHint']);
}

// filter by a especific Rule ID
if (isset($_GET['ruleid']) OR isset($_GET['Not_ruleid'])) {
    if ($_GET['ruleid'] == 'x' OR empty($_GET['ruleid'])) {
        unset($_SESSION['filter']['ruleid']);
        unset($_SESSION['filter']['Not_ruleid']);
    } else {
        if (isset($_GET['Not_ruleid']) AND $_GET['Not_ruleid'] == '1') {
            $_SESSION['filter']['Not_ruleid'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_ruleid']);
        }

        if (isset($_GET['ruleid']) AND preg_match('/^(\s|(:?sid)?[\d]{1,7})/', $_GET['ruleid'])) {
            $_SESSION['filter']['ruleid'] = @sanitize_paranoid_string($_GET['ruleid']);
        } else {
            unset($_SESSION['filter']['ruleid']);
            unset($_SESSION['filter']['Not_ruleid']);
        }
    }
}

// filter by severity
if (isset($_GET['severity']) OR isset($_GET['Not_severity'])) {
   if ($_GET['severity'] == 'x' OR preg_match('/!^\d{1,3}$/', $_GET['severity'])) {
      unset($_SESSION['filter']['severity']);
      unset($_SESSION['filter']['Not_severity']);
      if (isset($_SESSION['filterIndexHint'])) {
         unset($_SESSION['filterIndexHint'][array_search("severity", $_SESSION['filterIndexHint'])]);
      }
    } else {
        if (isset($_GET['Not_severity']) AND $_GET['Not_severity'] == '1') {
            $_SESSION['filter']['Not_severity'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_severity']);
        }
        if (isset($_GET['severity']) AND preg_match('/^\d{1,3}$/', $_GET['severity'])) {
            $_SESSION['filter']['severity'] = $_GET['severity'];
            $_SESSION['filterIndexHint'][] = "severity";
        } else {
            unset($_SESSION['filter']['severity']);
            unset($_SESSION['filter']['Not_severity']);
            if (isset($_SESSION['filterIndexHint'])) {
               unset($_SESSION['filterIndexHint'][array_search("severity", $_SESSION['filterIndexHint'])]);
            }  
        }
    }
}

// filter by Score Total
if (isset($_GET['score']) AND isset($_GET['score_interval'])) {
    if ($_GET['score'] == "x" OR empty($_GET['score'])) {
        unset($_SESSION['filter']['score']);
        unset($_SESSION['filter']['score_interval']);
    } else {
        if (isset($_GET['score_interval']) AND $_GET['score_interval'] == "le") {
            $_SESSION['filter']['score_interval'] = "le";
        } else {
            $_SESSION['filter']['score_interval'] = "ge";
        }
       if (isset($_GET['score']) AND preg_match('/^\d{1,10}$/', $_GET['score'])) {
            $_SESSION['filter']['score'] = $_GET['score'];
        } else {
            unset($_SESSION['filter']['score']);
            unset($_SESSION['filter']['score_interval']);
        }
    }
}

// filter by Score SQLi
if (isset($_GET['scoreSqli']) AND isset($_GET['scoreSqli_interval'])) {
    if ($_GET['scoreSqli'] == "x" OR empty($_GET['scoreSqli'])) {
        unset($_SESSION['filter']['scoreSqli']);
        unset($_SESSION['filter']['scoreSqli_interval']);
    } else {
        if (isset($_GET['scoreSqli_interval']) AND $_GET['scoreSqli_interval'] == "le") {
            $_SESSION['filter']['scoreSqli_interval'] = "le";
        } else {
            $_SESSION['filter']['scoreSqli_interval'] = "ge";
        }
       if (isset($_GET['scoreSqli']) AND preg_match('/^\d{1,10}$/', $_GET['scoreSqli'])) {
            $_SESSION['filter']['scoreSqli'] = $_GET['scoreSqli'];
        } else {
            unset($_SESSION['filter']['scoreSqli']);
            unset($_SESSION['filter']['scoreSqli_interval']);
        }
    }
}

// filter by Score XSS
if (isset($_GET['scoreXss']) AND isset($_GET['scoreXss_interval'])) {
    if ($_GET['scoreXss'] == "x" OR empty($_GET['scoreXss'])) {
        unset($_SESSION['filter']['scoreXss']);
        unset($_SESSION['filter']['scoreXss_interval']);
    } else {
        if (isset($_GET['scoreXss_interval']) AND $_GET['scoreXss_interval'] == "le") {
            $_SESSION['filter']['scoreXss_interval'] = "le";
        } else {
            $_SESSION['filter']['scoreXss_interval'] = "ge";
        }
       if (isset($_GET['scoreXss']) AND preg_match('/^\d{1,10}$/', $_GET['scoreXss'])) {
            $_SESSION['filter']['scoreXss'] = $_GET['scoreXss'];
        } else {
            unset($_SESSION['filter']['scoreXss']);
            unset($_SESSION['filter']['scoreXss_interval']);
        }
    }
}

// filter by False Positive
if (isset($_GET['falsePositive'])) {
    if ($_GET['falsePositive'] == "0") {
        $_SESSION['filter']['falsePositive'] = FALSE;
    } elseif ($_GET['falsePositive'] == "1") {
        $_SESSION['filter']['falsePositive'] = TRUE;
    } else {
        unset($_SESSION['filter']['falsePositive']);
    }
}

// Timing filters
// filter by Duration
if (isset($_GET['duration']) AND isset($_GET['duration_interval'])) {
    if ($_GET['duration'] == "x" OR empty($_GET['duration'])) {
        unset($_SESSION['filter']['duration']);
        unset($_SESSION['filter']['duration_interval']);
    } else {
        if (isset($_GET['duration_interval']) AND $_GET['duration_interval'] == "le") {
            $_SESSION['filter']['duration_interval'] = "le";
        } else {
            $_SESSION['filter']['duration_interval'] = "ge";
        }
       if (isset($_GET['duration']) AND preg_match('/^\d{1,10}$/', $_GET['duration'])) {
            $_SESSION['filter']['duration'] = (int) $_GET['duration'];
        } else {
            unset($_SESSION['filter']['duration']);
            unset($_SESSION['filter']['duration_interval']);
        }
    }
}

// filter by Combined
if (isset($_GET['combined']) AND isset($_GET['combined_interval'])) {
    if ($_GET['combined'] == "x" OR empty($_GET['combined'])) {
        unset($_SESSION['filter']['combined']);
        unset($_SESSION['filter']['combined_interval']);
    } else {
        if (isset($_GET['combined_interval']) AND $_GET['combined_interval'] == "le") {
            $_SESSION['filter']['combined_interval'] = "le";
        } else {
            $_SESSION['filter']['combined_interval'] = "ge";
        }
       if (isset($_GET['combined']) AND preg_match('/^\d{1,10}$/', $_GET['combined'])) {
            $_SESSION['filter']['combined'] = (int) $_GET['combined'];
        } else {
            unset($_SESSION['filter']['combined']);
            unset($_SESSION['filter']['combined_interval']);
        }
    }
}

// filter by Phase 1
if (isset($_GET['p1']) AND isset($_GET['p1_interval'])) {
    if ($_GET['p1'] == "x" OR empty($_GET['p1'])) {
        unset($_SESSION['filter']['p1']);
        unset($_SESSION['filter']['p1_interval']);
    } else {
        if (isset($_GET['p1_interval']) AND $_GET['p1_interval'] == "le") {
            $_SESSION['filter']['p1_interval'] = "le";
        } else {
            $_SESSION['filter']['p1_interval'] = "ge";
        }
       if (isset($_GET['p1']) AND preg_match('/^\d{1,10}$/', $_GET['p1'])) {
            $_SESSION['filter']['p1'] = (int) $_GET['p1'];
        } else {
            unset($_SESSION['filter']['p1']);
            unset($_SESSION['filter']['p1_interval']);
        }
    }
}

// filter by Phase 2
if (isset($_GET['p2']) AND isset($_GET['p2_interval'])) {
    if ($_GET['p2'] == "x" OR empty($_GET['p2'])) {
        unset($_SESSION['filter']['p2']);
        unset($_SESSION['filter']['p2_interval']);
    } else {
        if (isset($_GET['p2_interval']) AND $_GET['p2_interval'] == "le") {
            $_SESSION['filter']['p2_interval'] = "le";
        } else {
            $_SESSION['filter']['p2_interval'] = "ge";
        }
       if (isset($_GET['p2']) AND preg_match('/^\d{1,10}$/', $_GET['p2'])) {
            $_SESSION['filter']['p2'] = (int) $_GET['p2'];
        } else {
            unset($_SESSION['filter']['p2']);
            unset($_SESSION['filter']['p2_interval']);
        }
    }
}

// filter by Phase 3
if (isset($_GET['p3']) AND isset($_GET['p3_interval'])) {
    if ($_GET['p3'] == "x" OR empty($_GET['p3'])) {
        unset($_SESSION['filter']['p3']);
        unset($_SESSION['filter']['p3_interval']);
    } else {
        if (isset($_GET['p3_interval']) AND $_GET['p3_interval'] == "le") {
            $_SESSION['filter']['p3_interval'] = "le";
        } else {
            $_SESSION['filter']['p3_interval'] = "ge";
        }
       if (isset($_GET['p3']) AND preg_match('/^\d{1,10}$/', $_GET['p3'])) {
            $_SESSION['filter']['p3'] = (int) $_GET['p3'];
        } else {
            unset($_SESSION['filter']['p3']);
            unset($_SESSION['filter']['p3_interval']);
        }
    }
}

// filter by Phase 4
if (isset($_GET['p4']) AND isset($_GET['p4_interval'])) {
    if ($_GET['p4'] == "x" OR empty($_GET['p4'])) {
        unset($_SESSION['filter']['p4']);
        unset($_SESSION['filter']['p4_interval']);
    } else {
        if (isset($_GET['p4_interval']) AND $_GET['p4_interval'] == "le") {
            $_SESSION['filter']['p4_interval'] = "le";
        } else {
            $_SESSION['filter']['p4_interval'] = "ge";
        }
       if (isset($_GET['p4']) AND preg_match('/^\d{1,10}$/', $_GET['p4'])) {
            $_SESSION['filter']['p4'] = (int) $_GET['p4'];
        } else {
            unset($_SESSION['filter']['p4']);
            unset($_SESSION['filter']['p4_interval']);
        }
    }
}

// filter by Phase 5
if (isset($_GET['p5']) AND isset($_GET['p5_interval'])) {
    if ($_GET['p5'] == "x" OR empty($_GET['p5'])) {
        unset($_SESSION['filter']['p5']);
        unset($_SESSION['filter']['p5_interval']);
    } else {
        if (isset($_GET['p5_interval']) AND $_GET['p5_interval'] == "le") {
            $_SESSION['filter']['p5_interval'] = "le";
        } else {
            $_SESSION['filter']['p5_interval'] = "ge";
        }
       if (isset($_GET['p5']) AND preg_match('/^\d{1,10}$/', $_GET['p5'])) {
            $_SESSION['filter']['p5'] = (int) $_GET['p5'];
        } else {
            unset($_SESSION['filter']['p5']);
            unset($_SESSION['filter']['p5_interval']);
        }
    }
}

// filter by Storage Read
if (isset($_GET['sr']) AND isset($_GET['sr_interval'])) {
    if ($_GET['sr'] == "x" OR empty($_GET['sr'])) {
        unset($_SESSION['filter']['sr']);
        unset($_SESSION['filter']['sr_interval']);
    } else {
        if (isset($_GET['sr_interval']) AND $_GET['sr_interval'] == "le") {
            $_SESSION['filter']['sr_interval'] = "le";
        } else {
            $_SESSION['filter']['sr_interval'] = "ge";
        }
       if (isset($_GET['sr']) AND preg_match('/^\d{1,10}$/', $_GET['sr'])) {
            $_SESSION['filter']['sr'] = (int) $_GET['sr'];
        } else {
            unset($_SESSION['filter']['sr']);
            unset($_SESSION['filter']['sr_interval']);
        }
    }
}

// filter by Storage Write
if (isset($_GET['sw']) AND isset($_GET['sw_interval'])) {
    if ($_GET['sw'] == "x" OR empty($_GET['sw'])) {
        unset($_SESSION['filter']['sw']);
        unset($_SESSION['filter']['sw_interval']);
    } else {
        if (isset($_GET['sw_interval']) AND $_GET['sw_interval'] == "le") {
            $_SESSION['filter']['sw_interval'] = "le";
        } else {
            $_SESSION['filter']['sw_interval'] = "ge";
        }
       if (isset($_GET['sw']) AND preg_match('/^\d{1,10}$/', $_GET['sw'])) {
            $_SESSION['filter']['sw'] = (int) $_GET['sw'];
        } else {
            unset($_SESSION['filter']['sw']);
            unset($_SESSION['filter']['sw_interval']);
        }
    }
}

// filter by Logging
if (isset($_GET['log']) AND isset($_GET['log_interval'])) {
    if ($_GET['log'] == "x" OR empty($_GET['log'])) {
        unset($_SESSION['filter']['log']);
        unset($_SESSION['filter']['log_interval']);
    } else {
        if (isset($_GET['log_interval']) AND $_GET['log_interval'] == "le") {
            $_SESSION['filter']['log_interval'] = "le";
        } else {
            $_SESSION['filter']['log_interval'] = "ge";
        }
       if (isset($_GET['log']) AND preg_match('/^\d{1,10}$/', $_GET['log'])) {
            $_SESSION['filter']['log'] = (int) $_GET['log'];
        } else {
            unset($_SESSION['filter']['log']);
            unset($_SESSION['filter']['log_interval']);
        }
    }
}

// filter by Garbage Collection
if (isset($_GET['gc']) AND isset($_GET['gc_interval'])) {
    if ($_GET['gc'] == "x" OR empty($_GET['gc'])) {
        unset($_SESSION['filter']['gc']);
        unset($_SESSION['filter']['gc_interval']);
    } else {
        if (isset($_GET['gc_interval']) AND $_GET['gc_interval'] == "le") {
            $_SESSION['filter']['gc_interval'] = "le";
        } else {
            $_SESSION['filter']['gc_interval'] = "ge";
        }
       if (isset($_GET['gc']) AND preg_match('/^\d{1,10}$/', $_GET['gc'])) {
            $_SESSION['filter']['gc'] = (int) $_GET['gc'];
        } else {
            unset($_SESSION['filter']['gc']);
            unset($_SESSION['filter']['gc_interval']);
        }
    }
}

// filter by method
if (isset($_GET['method']) OR isset($_GET['Not_method'])) {
    if ($_GET['method'] == "x" OR empty($_GET['method'])) {
        unset($_SESSION['filter']['method']);
        unset($_SESSION['filter']['Not_method']);
        if (isset($_SESSION['filterIndexHint'])) {
         unset($_SESSION['filterIndexHint'][array_search("b_method", $_SESSION['filterIndexHint'])]);
        }
    } else {
        if (isset($_GET['Not_method']) AND $_GET['Not_method'] == '1') {
            $_SESSION['filter']['Not_method'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_method']);
        }
        if (isset($_GET['method']) AND preg_match('/^\w{1,20}$/', $_GET['method'])) {
            $_SESSION['filter']['method'] = @sanitize_paranoid_string($_GET['method']);
            $_SESSION['filterIndexHint'][] = "b_method";
        } else {
            unset($_SESSION['filter']['method']);
            unset($_SESSION['filter']['Not_method']);
            if (isset($_SESSION['filterIndexHint'])) {
               unset($_SESSION['filterIndexHint'][array_search("b_method", $_SESSION['filterIndexHint'])]);
            }

        }
    }
}

// filter by a PATH
if (isset($_GET['path']) OR isset($_GET['Not_path'])) {
    if ($_GET['path'] == "x" OR empty($_GET['path'])) {
        unset($_SESSION['filter']['path']);
        unset($_SESSION['filter']['Not_path']);
        unset($_SESSION['filter']['path_wc']);
        if (isset($_SESSION['filterIndexHint'])) {
            unset($_SESSION['filterIndexHint'][array_search("path", $_SESSION['filterIndexHint'])]);
         }
    } else {
        if (isset($_GET['Not_path']) AND $_GET['Not_path'] == '1') {
            $_SESSION['filter']['Not_path'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_path']);
        }
        if ((strpos($_GET['path'], '*')+1) == strlen($_GET['path'])) {
            $pathWildcard_tmp = true;
        }
        if (isset($_GET['path']) AND $parsedPath = parse_url($_GET['path'])) {
            $_SESSION['filter']['path'] = @sanitize_paranoid_path($parsedPath['path']);
            $_SESSION['filterIndexHint'][] = "path";
            if ($pathWildcard_tmp) {
                $_SESSION['filter']['path_wc'] = true;
            } else {
                unset($_SESSION['filter']['path_wc']);
            }
        } else {
            unset($_SESSION['filter']['path']);
            unset($_SESSION['filter']['Not_path']);
            unset($_SESSION['filter']['path_wc']);
            if (isset($_SESSION['filterIndexHint'])) {
               unset($_SESSION['filterIndexHint'][array_search("path", $_SESSION['filterIndexHint'])]);
            }
        }
    }
}


// filter by a Web App Info
if (isset($_GET['webApp']) OR isset($_GET['Not_webApp'])) {
    if ($_GET['webApp'] == "x" OR empty($_GET['webApp'])) {
        unset($_SESSION['filter']['webApp']);
        unset($_SESSION['filter']['Not_webApp']);
    } else {
        if (isset($_GET['Not_webApp']) AND $_GET['Not_webApp'] == '1') {
            $_SESSION['filter']['Not_webApp'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_webApp']);
        }

        if (isset($_GET['webApp']) AND preg_match('/^\w{1,20}$/', $_GET['webApp'])) {
            $_SESSION['filter']['webApp'] = @sanitize_paranoid_string($_GET['webApp']);
        } else {
            unset($_SESSION['filter']['webApp']);
            unset($_SESSION['filter']['Not_webApp']);
        }
    }
}

// filter by User ID
if (isset($_GET['userId']) OR isset($_GET['Not_userId'])) {
    if ($_GET['userId'] == "x" OR empty($_GET['userId'])) {
        unset($_SESSION['filter']['userId']);
        unset($_SESSION['filter']['Not_userId']);
    } else {
        if (isset($_GET['Not_userId']) AND $_GET['Not_userId'] == '1') {
            $_SESSION['filter']['Not_userId'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_userId']);
        }
        if (isset($_GET['userId']) AND preg_match('/^\w{1,20}$/', $_GET['userId'])) {
            $_SESSION['filter']['userId'] = @sanitize_paranoid_string($_GET['userId']);
        } else {
            unset($_SESSION['filter']['userId']);
            unset($_SESSION['filter']['Not_userId']);
        }
    }
}

// filter by a especific source IP
if (isset($_GET['esrc']) OR isset($_GET['Not_esrc'])) {
    if ($_GET['esrc'] == "x" OR empty($_GET['esrc'])) {
        unset($_SESSION['filter']['esrc']);
        unset($_SESSION['filter']['Not_esrc']);
        if (isset($_SESSION['filterIndexHint'])) {
            unset($_SESSION['filterIndexHint'][array_search("a_client_ip", $_SESSION['filterIndexHint'])]);
         }
    } else {
        if (isset($_GET['Not_esrc']) AND $_GET['Not_esrc'] == '1') {
            $_SESSION['filter']['Not_esrc'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_esrc']);
        }
        if (isset($_GET['esrc']) AND preg_match('/^(?:2[0-4]\d|25[0-5]|[01]?\d\d?)\.(?:2[0-4]\d|25[0-5]|[01]?\d\d?)\.(?:2[0-4]\d|25[0-5]|[01]?\d\d?)\.(?:2[0-4]\d|25[0-5]|[01]?\d\d?)(?P<cidr>\/\d{1,2})?$/', $_GET['esrc'], $ipSplit)) {
            $cidr = str_replace("/", "", $ipSplit['cidr']);
            if ($cidr == NULL OR (1 <= $cidr AND $cidr <= 32)) {
                $_SESSION['filter']['esrc'] = $_GET['esrc'];
                $_SESSION['filterIndexHint'][] = "a_client_ip";
            }
        } else {
            unset($_SESSION['filter']['esrc']);
            unset($_SESSION['filter']['Not_esrc']);
            if (isset($_SESSION['filterIndexHint'])) {
               unset($_SESSION['filterIndexHint'][array_search("a_client_ip", $_SESSION['filterIndexHint'])]);
            }
        }
    }
}

// filter by a especific source IP Country Code
if (isset($_GET['ipcc']) OR isset($_GET['Not_ipcc'])) {
    if ($_GET['ipcc'] == "x" OR empty($_GET['ipcc'])) {
        unset($_SESSION['filter']['ipcc']);
        unset($_SESSION['filter']['Not_ipcc']);
        if (isset($_SESSION['filterIndexHint'])) {
            unset($_SESSION['filterIndexHint'][array_search("clientIP_CC", $_SESSION['filterIndexHint'])]);
        }
    } else {
        if (isset($_GET['Not_ipcc']) AND $_GET['Not_ipcc'] == '1') {
            $_SESSION['filter']['Not_ipcc'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_ipcc']);
        }
        if (isset($_GET['ipcc']) AND preg_match('/^\w{2}$/', $_GET['ipcc'])) {
            $_SESSION['filter']['ipcc'] = strtoupper(@sanitize_paranoid_string($_GET['ipcc']));
            $_SESSION['filterIndexHint'][] = "clientIP_CC";
        } else {
            unset($_SESSION['filter']['ipcc']);
            unset($_SESSION['filter']['Not_ipcc']);
            if (isset($_SESSION['filterIndexHint'])) {
               unset($_SESSION['filterIndexHint'][array_search("clientIP_CC", $_SESSION['filterIndexHint'])]);
            }
        }
    }
}

// filter by a especific source IP AS Number
if (isset($_GET['ipasn']) OR isset($_GET['Not_ipasn'])) {
    if ($_GET['ipasn'] == "x" OR empty($_GET['ipasn'])) {
        unset($_SESSION['filter']['ipasn']);
        unset($_SESSION['filter']['Not_ipasn']);
        if (isset($_SESSION['filterIndexHint'])) {
         unset($_SESSION['filterIndexHint'][array_search("clientIP_ASN", $_SESSION['filterIndexHint'])]);
        }
    } else {
        if (isset($_GET['Not_ipasn']) AND $_GET['Not_ipasn'] == '1') {
            $_SESSION['filter']['Not_ipasn'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_ipasn']);
        }
        if (isset($_GET['ipasn']) AND preg_match('/^\d{1,5}$/', $_GET['ipasn'])) {
            $_SESSION['filter']['ipasn'] = @sanitize_int($_GET['ipasn'], $min='1' );
            $_SESSION['filterIndexHint'][] = "clientIP_ASN";
        } else {
            unset($_SESSION['filter']['ipasn']);
            unset($_SESSION['filter']['Not_ipasn']);
            if (isset($_SESSION['filterIndexHint'])) {
               unset($_SESSION['filterIndexHint'][array_search("clientIP_ASN", $_SESSION['filterIndexHint'])]);
            }
        }
    }
}

// filter by a http status code
if (isset($_GET['http_Status']) OR isset($_GET['Not_http_Status'])) {
    if ($_GET['http_Status'] == "x" OR empty($_GET['http_Status'])) {
        unset($_SESSION['filter']['http_Status']);
        unset($_SESSION['filter']['Not_http_Status']);
        if (isset($_SESSION['filterIndexHint'])) {
           unset($_SESSION['filterIndexHint'][array_search("f_status", $_SESSION['filterIndexHint'])]);
        }
    } else {
        if (isset($_GET['Not_http_Status']) AND $_GET['Not_http_Status'] == '1') {
            $_SESSION['filter']['Not_http_Status'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_http_Status']);
        }
        if (isset($_GET['http_Status']) AND preg_match('/^\d{3}$/', $_GET['http_Status'])) {
            $_SESSION['filter']['http_Status'] = $_GET['http_Status'];
            $_SESSION['filterIndexHint'][] = "f_status";
        } else {
            unset($_SESSION['filter']['http_Status']);
            unset($_SESSION['filter']['Not_http_Status']);
            if (isset($_SESSION['filterIndexHint'])) {
               unset($_SESSION['filterIndexHint'][array_search("f_status", $_SESSION['filterIndexHint'])]);
            }
        }
    }
}

// filter by action status
if (isset($_GET['actionstatus']) OR isset($_GET['Not_actionstatus'])) {
    if ($_GET['actionstatus'] == "x" OR empty($_GET['actionstatus'])) {
        unset($_SESSION['filter']['actionstatus']);
        unset($_SESSION['filter']['Not_actionstatus']);
        if (isset($_SESSION['filterIndexHint'])) {
            unset($_SESSION['filterIndexHint'][array_search("h_action", $_SESSION['filterIndexHint'])]);
        }
    } else {
        if (isset($_GET['Not_actionstatus']) AND $_GET['Not_actionstatus'] == '1') {
            $_SESSION['filter']['Not_actionstatus'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_actionstatus']);
        }
        if (isset($_GET['actionstatus']) AND preg_match('/^(\d{1,2}|allow|block|warning)$/', $_GET['actionstatus'])) {
            $_SESSION['filter']['actionstatus'] = $_GET['actionstatus'];
            $_SESSION['filterIndexHint'][] = "h_action";
        } else {
            unset($_SESSION['filter']['actionstatus']);
            unset($_SESSION['filter']['Not_actionstatus']);
            if (isset($_SESSION['filterIndexHint'])) {
               unset($_SESSION['filterIndexHint'][array_search("h_action", $_SESSION['filterIndexHint'])]);
            }
        }
    }
}

// filter by engine mode
if (isset($_GET['engineMode']) OR isset($_GET['Not_engineMode'])) {
    if ($_GET['engineMode'] == "x" OR empty($_GET['engineMode'])) {
        unset($_SESSION['filter']['engineMode']);
        unset($_SESSION['filter']['Not_engineMode']);
    } else {
        if (isset($_GET['Not_engineMode']) AND $_GET['Not_engineMode'] == '1') {
            $_SESSION['filter']['Not_engineMode'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_engineMode']);
        }
        if (isset($_GET['engineMode']) AND ($_GET['engineMode'] == "DETECTION_ONLY" OR $_GET['engineMode'] == "ENABLED")) {
            $_SESSION['filter']['engineMode'] = $_GET['engineMode'];
        } else {
            unset($_SESSION['filter']['engineMode']);
            unset($_SESSION['filter']['Not_engineMode']);
        }
    }
}

// Sensor search, all or only one
if (isset($_GET['src_sensor']) OR isset($_GET['Not_src_sensor'])) {
    if ($_GET['src_sensor'] == "x" OR $_GET['src_sensor'] == "all" OR empty($_GET['src_sensor'])) {
        unset($_SESSION['filter']['src_sensor']);
        unset($_SESSION['filter']['Not_src_sensor']);
        if (isset($_SESSION['filterIndexHint'])) {
            unset($_SESSION['filterIndexHint'][array_search("sensor_id", $_SESSION['filterIndexHint'])]);
        }
    } else {
        if (isset($_GET['Not_src_sensor']) AND $_GET['Not_src_sensor'] == '1') {
            $_SESSION['filter']['Not_src_sensor'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_src_sensor']);
        }
        if (isset($_GET['src_sensor']) AND @sanitize_int($_GET['src_sensor'], $min='0' )) {
            $_SESSION['filter']['src_sensor'] = $_GET['src_sensor'];
            $_SESSION['filterIndexHint'][] = "sensor_id";
        } else {
            unset($_SESSION['filter']['src_sensor']);
            unset($_SESSION['filter']['Not_src_sensor']);
            if (isset($_SESSION['filterIndexHint'])) {
               unset($_SESSION['filterIndexHint'][array_search("sensor_id", $_SESSION['filterIndexHint'])]);
            }
        }
    }
}

// Web Hostname search, all or only one
if (isset($_GET['web_Hostname']) OR isset($_GET['Not_web_Hostname'])) {
    if ($_GET['web_Hostname'] == "x" OR $_GET['web_Hostname'] == "all" OR empty($_GET['web_Hostname'])) {
        unset($_SESSION['filter']['web_Hostname']);
        unset($_SESSION['filter']['Not_web_Hostname']);
        if (isset($_SESSION['filterIndexHint'])) {
         unset($_SESSION['filterIndexHint'][array_search("b_host", $_SESSION['filterIndexHint'])]);
        }
    } else {
        if (isset($_GET['Not_web_Hostname']) AND $_GET['Not_web_Hostname'] == '1') {
            $_SESSION['filter']['Not_web_Hostname'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_web_Hostname']);
        }
        if (isset($_GET['web_Hostname']) AND $web_hostname_to_lookingfor = @sanitize_paranoid_string($_GET['web_Hostname'])) {
            $web_host_list = getWebHosts();
            foreach($web_host_list as $web_host) {
                if ($web_hostname_to_lookingfor == $web_host['host_id']) {
                    $_SESSION['filter']['web_Hostname'] = $web_hostname_to_lookingfor;
                    $_SESSION['filterIndexHint'][] = "b_host";
                }
            }
        } else {
            unset($_SESSION['filter']['web_Hostname']);
            unset($_SESSION['filter']['Not_web_Hostname']);
            if (isset($_SESSION['filterIndexHint'])) {
               unset($_SESSION['filterIndexHint'][array_search("b_host", $_SESSION['filterIndexHint'])]);
            }
        }
    }
}

// Filter by Tag
if (isset($_GET['tag']) OR isset($_GET['Not_tag'])) {
    if ($_GET['tag'] == 'x' OR empty($_GET['tag'])) {
        unset($_SESSION['filter']['tag']);
        unset($_SESSION['filter']['Not_tag']);
    } else {
        if (isset($_GET['Not_tag']) AND $_GET['Not_tag'] == '1') {
            $_SESSION['filter']['Not_tag'] = TRUE;
        } else {
            unset($_SESSION['filter']['Not_tag']);
        }
        if (isset($_GET['tag']) AND $tagToSet = @sanitize_int($_GET['tag'], $min='0', $max='65535' )) {
            $_SESSION['filter']['tag'] = $tagToSet;
        } else {
            unset($_SESSION['filter']['tag']);
            unset($_SESSION['filter']['Not_tag']);
        }
    }
}

// Filter by Uniq ID
// Attention, this must be after all other filters except time frame
if (isset($_GET['uniqId'])) {
    if ($_GET['uniqId'] == 'x' OR empty($_GET['uniqId'])) {
        unset($_SESSION['filter']['uniqId']);
    } else {
        if (isset($_GET['uniqId']) AND preg_match('/^[a-zA-Z0-9\-\@]{24}$/', trim($_GET['uniqId']))) {
            $_SESSION['filter']['uniqId'] = @sanitize_paranoid_string($_GET['uniqId']);
        } else {
            unset($_SESSION['filter']['uniqId']);
        }
    }
}

// filter by a specific time frame
if (isset($_GET['StDate']) AND isset($_GET['StTime']) AND isset($_GET['FnDate']) AND isset($_GET['FnTime']) ) {
   if (preg_match('/^(?:(?:20\d{2}))-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12][0-9]|3[01])$/', $_GET['StDate']) AND preg_match('/^(?:(?:[01][0-9]|[2][0-3])):(?:[0-5][0-9]):(?:[0-5][0-9])$/', $_GET['StTime']) AND preg_match('/^(?:(?:20\d{2}))-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12][0-9]|3[01])$/', $_GET['FnDate']) AND preg_match('/^(?:(?:[01][0-9]|[2][0-3])):(?:[0-5][0-9]):(?:[0-5][0-9])$/', $_GET['FnTime']) ) {
      $_SESSION['filter']['StDate'] = $_GET['StDate'];
      $_SESSION['filter']['StTime'] = $_GET['StTime'];
      $_SESSION['filter']['FnDate'] = $_GET['FnDate'];
      $_SESSION['filter']['FnTime'] = $_GET['FnTime'];
   } else {
      $_SESSION['filter']['StDate'] = date("Y-m-d");
      $_SESSION['filter']['StTime'] = "00:00:00";
      $_SESSION['filter']['FnDate'] = date("Y-m-d");
      $_SESSION['filter']['FnTime'] = "23:59:59";
   }
} elseif (!isset($_SESSION['filter']['StDate']) OR (!isset($_SESSION['filter']['StTime'])) OR (!isset($_SESSION['filter']['FnDate'])) OR (!isset($_SESSION['filter']['FnTime']))) {
   $_SESSION['filter']['StDate'] = date("Y-m-d");
   $_SESSION['filter']['StTime'] = "00:00:00";
   $_SESSION['filter']['FnDate'] = date("Y-m-d");
   $_SESSION['filter']['FnTime'] = "23:59:59";
}

if ($_SESSION['filter']['StTime'] == "00:00:00" AND $_SESSION['filter']['FnTime'] == "23:59:59") {
    $_SESSION['filter']['fullDayFilter'] = true;
} else {
    $_SESSION['filter']['fullDayFilter'] = false;
}

if (count($_SESSION['filter']) == 0) {
   unset($_SESSION['filter']);
}
   if (isset($_SESSION['filterIndexHint']) AND count($_SESSION['filterIndexHint']) > 0 AND $_SESSION['filterIndexHint'] != false) {
      $_SESSION['filterIndexHint'] = array_unique($_SESSION['filterIndexHint']);
      $filterIndexHint = " (" . implode(",", $_SESSION['filterIndexHint']) . ") ";
   }

?>
