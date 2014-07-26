<?PHP

require_once("../session.php");
if (isset($_SESSION['filter'])) {
    $showFilter = "<div>";
    $showFilter .= "<div class=\"ui-widget\">";
    $showFilter .= "<div class=\"ui-state-highlight ui-corner-all\" style=\"margin-top: 1px; margin-bottom: 1px; padding: 0 .7em; padding-top: 3px; padding-bottom: 3px;\">";
    $showFilter .= "<span class=\"ui-icon ui-icon-search\" style=\"float: left; margin-right: .3em;\"></span>";

    $showFilter .= "<strong>Current Filter: { </strong>";

    if (isset($_SESSION['filter']['StDate']) AND isset($_SESSION['filter']['StTime']) AND isset($_SESSION['filter']['FnDate']) AND   isset($_SESSION['filter']['FnTime'])) {
        $showFilter .= "Date: ".$_SESSION['filter']['StDate'] ." ".$_SESSION['filter']['StTime'] ." Until ".$_SESSION['filter']['FnDate'] ." ".$_SESSION['filter']['FnTime'];
        $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?StDate=X&StTime=X&FnDate=X&FnTime=X\" title=\"Reset Filter Date\" class=\"filter_control\">Reset for Today</a>)</span> ";
    }

    if (isset($_SESSION['filter']['ruleid'])) {
        if ($_SESSION['filter']['ruleid'] == " ") {
            $eventID = "Space";
        } else {
            $eventID = $_SESSION['filter']['ruleid'];
        }
        if ($_SESSION['filter']['Not_ruleid']) {
            $showFilter .= "| Rule ID: <span class=\"Negate\">".$eventID ."</span>";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?ruleid=$eventID&Not_ruleid=0\" title=\"Negate the Rule ID from Filter\" class=\"filter_control\">Not</a>)";
        } else {
            $showFilter .= "| Rule ID: ".$eventID ;
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?ruleid=$eventID&Not_ruleid=1\" title=\"Negate the Rule ID from Filter\" class=\"filter_control\">Not</a>)";
        }
        $showFilter .= " (<a href=\"$thisPage?ruleid=x\" title=\"Clear Rule ID Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['esrc'])) {
        if (isset($_SESSION['filter']['Not_esrc']) AND $_SESSION['filter']['Not_esrc']) {
            $showFilter .= "| Client IP: <span class=\"Negate\">".$_SESSION['filter']['esrc'] . "</span>";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?esrc=".$_SESSION['filter']['esrc']."&Not_esrc=0\" title=\"Exclude the Client IP from Filter\" class=\"filter_control\">Not</a>)";
        } else {
            $showFilter .= "| Client IP: ".$_SESSION['filter']['esrc'] ;
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?esrc=".$_SESSION['filter']['esrc']."&Not_esrc=1\" title=\"Exclude the Client IP from Filter\" class=\"filter_control\">Not</a>)";
        }
        $showFilter .= " (<a href=\"$thisPage?esrc=x\" title=\"Clear Client IP Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['ipcc'])) {
        if (isset($_SESSION['filter']['Not_ipcc']) AND $_SESSION['filter']['Not_ipcc']) {
            $showFilter .= "| Client IP Country Code: <span class=\"Negate\">".$_SESSION['filter']['ipcc'] . "</span>";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?ipcc=".$_SESSION['filter']['ipcc']."&Not_ipcc=0\" title=\"Exclude the Client IP Country Code from Filter\" class=\"filter_control\">Not</a>)";
        } else {
            $showFilter .= "| Client IP Country Code: ".$_SESSION['filter']['ipcc'] ;
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?ipcc=".$_SESSION['filter']['ipcc']."&Not_ipcc=1\" title=\"Exclude the Client IP Country Code from Filter\" class=\"filter_control\">Not</a>)";
            
        }
        $showFilter .= " (<a href=\"$thisPage?ipcc=x\" title=\"Clear Client IP Country Code Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['ipasn'])) {
        if (isset($_SESSION['filter']['Not_ipasn']) AND $_SESSION['filter']['Not_ipasn']) {
          $showFilter .= "| Client IP Autonomous System Number: <span class=\"Negate\">".$_SESSION['filter']['ipasn'] . "</span>";
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?ipasn=".$_SESSION['filter']['ipasn']."&Not_ipasn=0\" title=\"Exclude the Client IP Autonomous System Number from Filter\" class=\"filter_control\">Not</a>)";
       } else {
          $showFilter .= "| Client IP Autonomous System Number: ".$_SESSION['filter']['ipasn'] ;
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?ipasn=".$_SESSION['filter']['ipasn']."&Not_ipasn=1\" title=\"Exclude the Client IP Autonomous System Number from Filter\" class=\"filter_control\">Not</a>)";
       }
       $showFilter .= " (<a href=\"$thisPage?ipasn=x\" title=\"Clear Client IP Country Code Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['web_Hostname'])) {
        if (isset($_SESSION['filter']['Not_web_Hostname']) AND $_SESSION['filter']['Not_web_Hostname']) {
            $showFilter .= "| Web Hostname: <span class=\"Negate\">".headerprintnobr(getWebHostName($_SESSION['filter']['web_Hostname'])) . "</span>";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?web_Hostname=".$_SESSION['filter']['web_Hostname']."&Not_web_Hostname=0\" title=\"Exclude the Web Hostname from Search\" class=\"filter_control\">Not</a>)";
        } else {
            $showFilter .= "| Web Hostname: ".headerprintnobr(getWebHostName($_SESSION['filter']['web_Hostname'])) ;
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?web_Hostname=".$_SESSION['filter']['web_Hostname']."&Not_web_Hostname=1\" title=\"Exclude the Web Hostname from Search\" class=\"filter_control\">Not</a>)";
        }
        $showFilter .= " (<a href=\"$thisPage?web_Hostname=x\" title=\"Clear Web Hostname Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['src_sensor'])) {
       $sensor = getsensorname($_SESSION['filter']['src_sensor']);
       if (isset($_SESSION['filter']['Not_src_sensor']) AND $_SESSION['filter']['Not_src_sensor']) {
          $showFilter .= "| Sensor: <span class=\"Negate\">".$sensor['name']."</span>";
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?src_sensor=".$_SESSION['filter']['src_sensor']."&Not_src_sensor=0\" title=\"Exclude the Sensor from Filter\" class=\"filter_control\">Not</a>)";
       } else {
          $showFilter .= "| Sensor: ".$sensor['name'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?src_sensor=".$_SESSION['filter']['src_sensor']."&Not_src_sensor=1\" title=\"Exclude the Sensor from Filter\" class=\"filter_control\">Not</a>)";
       }
       $showFilter .= " (<a href=\"$thisPage?src_sensor=x\" title=\"Clear Sensor Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['method'])) {
        if (isset($_SESSION['filter']['Not_method']) AND $_SESSION['filter']['Not_method']) {
            $showFilter .= "| Method: <span class=\"Negate\">".headerprintnobr($_SESSION['filter']['method'])."</span>";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?method=".headerprintnobr($_SESSION['filter']['method'])."&Not_method=0\" title=\"Exclude the Method from Filter\" class=\"filter_control\">Not</a>)";
        } else {
            $showFilter .= "| Method: ".headerprintnobr($_SESSION['filter']['method']);
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?method=".headerprintnobr($_SESSION['filter']['method'])."&Not_method=1\" title=\"Exclude the Method from Filter\" class=\"filter_control\">Not</a>)";
        }
        $showFilter .= " (<a href=\"$thisPage?method=x\" title=\"Clear Method Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['http_Status'])) {
        if (isset($_SESSION['filter']['Not_http_Status']) AND $_SESSION['filter']['Not_http_Status']) {
            $showFilter .= "| HTTP Status: <span class=\"Negate\">".$_SESSION['filter']['http_Status'] . "</span>";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?http_Status=".$_SESSION['filter']['http_Status']."&Not_http_Status=0\" title=\"Exclude this HTTP Status from Filter\" class=\"filter_control\">Not</a>)" ;
        } else {
            $showFilter .= "| HTTP Status: ".$_SESSION['filter']['http_Status'];
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?http_Status=".$_SESSION['filter']['http_Status']."&Not_http_Status=1\" title=\"Exclude this HTTP Status from Filter\" class=\"filter_control\">Not</a>)" ;
        }
        $showFilter .= " (<a href=\"$thisPage?http_Status=x\" title=\"Clear HTTP Status Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['actionstatus'])) {
        if (isset($_SESSION['filter']['Not_actionstatus']) AND $_SESSION['filter']['Not_actionstatus']) {
            if (preg_match('/^\d{1,2}$/',$_SESSION['filter']['actionstatus'])) {
                $showFilter .= "| Action: <span class=\"Negate\">".$ActionStatus[$_SESSION['filter']['actionstatus']] . "</span>";
            } else {
                $showFilter .= "| Action: <span class=\"Negate\">".$_SESSION['filter']['actionstatus'] . "</span>";
            }
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?actionstatus=".$_SESSION['filter']['actionstatus']."&Not_actionstatus=0\" title=\"Exclude this Action from Filter\" class=\"filter_control\">Not</a>)";
            
       } else {
            if (preg_match('/^\d{1,2}$/',$_SESSION['filter']['actionstatus'])) {
                $showFilter .= "| Action: ".$ActionStatus[$_SESSION['filter']['actionstatus']];
            } else {
                $showFilter .= "| Action: ".$_SESSION['filter']['actionstatus'];
            }
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?actionstatus=".$_SESSION['filter']['actionstatus']."&Not_actionstatus=1\" title=\"Exclude this Action from Filter\" class=\"filter_control\">Not</a>)";
       }
       $showFilter .= " (<a href=\"$thisPage?actionstatus=x\" title=\"Clear Action Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['engineMode'])) {
        if (isset($_SESSION['filter']['Not_engineMode']) AND $_SESSION['filter']['Not_engineMode']) {
            $showFilter .= "| Engine Mode: <span class=\"Negate\">".$_SESSION['filter']['engineMode'] . "</span>";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?engineMode=".$_SESSION['filter']['engineMode']."&Not_engineMode=0\" title=\"Exclude this Engine Mode from Filter\" class=\"filter_control\">Not</a>)";
        } else {
            $showFilter .= "| Engine Mode: ".$_SESSION['filter']['engineMode'];
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?engineMode=".$_SESSION['filter']['engineMode']."&Not_engineMode=1\" title=\"Exclude this Engine Mode from Filter\" class=\"filter_control\">Not</a>)";
        }
        $showFilter .= " (<a href=\"$thisPage?engineMode=x\" title=\"Clear Engine Mode Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['severity'])) {
        if (isset($_SESSION['filter']['Not_severity']) AND $_SESSION['filter']['Not_severity']) {
            $showFilter .= "| Severity: <span class=\"Negate\">".$severity[$_SESSION['filter']['severity']] . "</span>";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?severity=".$_SESSION['filter']['severity']."&Not_severity=0\" title=\"Exclude this Severity from Filter\" class=\"filter_control\">Not</a>)";
        } else {
            $showFilter .= "| Severity: ".$severity[$_SESSION['filter']['severity']];
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?severity=".$_SESSION['filter']['severity']."&Not_severity=1\" title=\"Exclude this Severity from Filter\" class=\"filter_control\">Not</a>)";
        }
        $showFilter .= " (<a href=\"$thisPage?severity=x\" title=\"Clear Severity Filter\" class=\"filter_control\">Del</a>)</span> ";
    }
    if (isset($_SESSION['filter']['path'])) {
        if ($_SESSION['filter']['path_wc']) {
            $pathwc_tmp = '*';
        }
        if (isset($_SESSION['filter']['Not_path']) AND $_SESSION['filter']['Not_path']) {
            $showFilter .= "| Path: <span class=\"Negate\">".headerprintnobr($_SESSION['filter']['path']) . $pathwc_tmp . "</span>";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?path=".headerprintnobr($_SESSION['filter']['path']) . $pathwc_tmp . "&Not_path=0\" title=\"Exclude this Path from Filter\" class=\"filter_control\">Not</a>)";
        } else {
            $showFilter .= "| Path: ".headerprintnobr($_SESSION['filter']['path']) . $pathwc_tmp;
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?path=".headerprintnobr($_SESSION['filter']['path']) . $pathwc_tmp . "&Not_path=1\" title=\"Exclude this Path from Filter\" class=\"filter_control\">Not</a>)";
        }
        $showFilter .= " (<a href=\"$thisPage?path=x\" title=\"Clear Path Filter\" class=\"filter_control\">Del</a>)</span> ";
    }
    if (isset($_SESSION['filter']['uniqId'])) {
       $showFilter .= "| Unique ID: ".$_SESSION['filter']['uniqId'];
       $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?uniqId=x\" title=\"Clear Unique ID Filter\" class=\"filter_control\">Del</a>)</span> ";
    }
    if (isset($_SESSION['filter']['webApp'])) {
        if (isset($_SESSION['filter']['Not_webApp']) AND $_SESSION['filter']['Not_webApp']) {
            $showFilter .= "| Web App Info: <span class=\"Negate\">".$_SESSION['filter']['webApp'] . "</span>";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?webApp=".$_SESSION['filter']['webApp']."&Not_webApp=0\" title=\"Exclude this Web App Infor from Filter\" class=\"filter_control\">Not</a>)";
        } else {
            $showFilter .= "| Web App Info: ".$_SESSION['filter']['webApp'];
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?webApp=".$_SESSION['filter']['webApp']."&Not_webApp=1\" title=\"Exclude this Web App Infor from Filter\" class=\"filter_control\">Not</a>)";
        }
        $showFilter .= " (<a href=\"$thisPage?webApp=x\" title=\"Clear Web App Info Filter\" class=\"filter_control\">Del</a>)</span> ";
    }
    if (isset($_SESSION['filter']['userId'])) {
        if (isset($_SESSION['filter']['Not_userId']) AND $_SESSION['filter']['Not_userId']) {
            $showFilter .= "| User ID: <span class=\"Negate\">".headerprintnobr($_SESSION['filter']['userId']) . "</span>";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?userId=".headerprintnobr($_SESSION['filter']['userId'])."&Not_userId=0\" title=\"Exclude this User ID from Filter\" class=\"filter_control\">Not</a>)";
        } else {
            $showFilter .= "| User ID: ".headerprintnobr($_SESSION['filter']['userId']);
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?userId=".headerprintnobr($_SESSION['filter']['userId'])."&Not_userId=1\" title=\"Exclude this User ID from Filter\" class=\"filter_control\">Not</a>)";
        }
        $showFilter .= " (<a href=\"$thisPage?userId=x\" title=\"Clear User ID Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    // Tag
    if (isset($_SESSION['filter']['tag'])) {
        $tag_name = getTagName($_SESSION['filter']['tag']);
        if (isset($_SESSION['filter']['Not_tag']) AND $_SESSION['filter']['Not_tag']) {
            $showFilter .= "| Tag: <span class=\"Negate\">$tag_name</span>";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?tag=".$_SESSION['filter']['tag']."&Not_tag=0\" title=\"Exclude the Tag from Filter\" class=\"filter_control\">Not</a>)";
        } else {
            $showFilter .= "| Tag: $tag_name";
            $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?tag=".$_SESSION['filter']['tag']."&Not_tag=1\" title=\"Exclude the Tag from Filter\" class=\"filter_control\">Not</a>)";
        }
        $showFilter .= " (<a href=\"$thisPage?tag=x\" title=\"Clear Tag Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['score'])) {
       if ($_SESSION['filter']['score_interval'] == "le") {
          $showFilter .= "| Score Total: &le; ".$_SESSION['filter']['score'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?score=".$_SESSION['filter']['score']."&score_interval=ge\" title=\"Invert Score Total Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Score Total: &ge; ".$_SESSION['filter']['score'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?score=".$_SESSION['filter']['score']."&score_interval=le\" title=\"Invert Score Total Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?score=x&score_interval\" title=\"Clear Score Total Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['scoreSqli'])) {
       if ($_SESSION['filter']['scoreSqli_interval'] == "le") {
          $showFilter .= "| Score SQLi: &le; ".$_SESSION['filter']['scoreSqli'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?scoreSqli=".$_SESSION['filter']['scoreSqli']."&scoreSqli_interval=ge\" title=\"Invert Score SQLi Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Score SQLi: &ge; ".$_SESSION['filter']['scoreSqli'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?scoreSqli=".$_SESSION['filter']['scoreSqli']."&scoreSqli_interval=le\" title=\"Invert Score SQLi Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?scoreSqli=x&scoreSqli_interval\" title=\"Clear Score SQLi Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    if (isset($_SESSION['filter']['scoreXss'])) {
       if ($_SESSION['filter']['scoreXss_interval'] == "le") {
          $showFilter .= "| Score XSS: &le; ".$_SESSION['filter']['scoreXss'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?scoreXss=".$_SESSION['filter']['scoreXss']."&scoreXss_interval=ge\" title=\"Invert Score XSS Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Score XSS: &ge; ".$_SESSION['filter']['scoreXss'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?scoreXss=".$_SESSION['filter']['scoreXss']."&scoreXss_interval=le\" title=\"Invert Score XSS Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?scoreXss=x&scoreXss_interval\" title=\"Clear Score XSS Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    // Timing filter
    // Duration
    if (isset($_SESSION['filter']['duration'])) {
       if ($_SESSION['filter']['duration_interval'] == "le") {
          $showFilter .= "| Duration &le; ".$_SESSION['filter']['duration'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?duration=".$_SESSION['filter']['duration']."&duration_interval=ge\" title=\"Invert Duration Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Duration: &ge; ".$_SESSION['filter']['duration'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?duration=".$_SESSION['filter']['duration']."&duration_interval=le\" title=\"Invert Duration Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?duration=x&duration_interval\" title=\"Clear Duration Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    // Combined
    if (isset($_SESSION['filter']['combined'])) {
       if ($_SESSION['filter']['combined_interval'] == "le") {
          $showFilter .= "| Combined: &le; ".$_SESSION['filter']['combined'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?combined=".$_SESSION['filter']['combined']."&combined_interval=ge\" title=\"Invert Combined Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Combined: &ge; ".$_SESSION['filter']['combined'];
         $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?combined=".$_SESSION['filter']['combined']."&combined_interval=le\" title=\"Invert Combined Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?combined=x&combined_interval\" title=\"Clear Combined Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    // Phase 1
    if (isset($_SESSION['filter']['p1'])) {
       if ($_SESSION['filter']['p1_interval'] == "le") {
          $showFilter .= "| Phase 1: &le; ".$_SESSION['filter']['p1'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?p1=".$_SESSION['filter']['p1']."&p1_interval=ge\" title=\"Invert Phase 1 Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Phase 1: &ge; ".$_SESSION['filter']['p1'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?p1=".$_SESSION['filter']['p1']."&p1_interval=le\" title=\"Invert Phase 1 Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?p1=x&p1_interval\" title=\"Clear Phase 1 Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    // Phase 2
    if (isset($_SESSION['filter']['p2'])) {
       if ($_SESSION['filter']['p2_interval'] == "le") {
          $showFilter .= "| Phase 2: &le; ".$_SESSION['filter']['p2'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?p2=".$_SESSION['filter']['p2']."&p2_interval=ge\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Phase 2: &ge; ".$_SESSION['filter']['p2'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?p2=".$_SESSION['filter']['p2']."&p2_interval=le\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?p2=x&p2_interval\" title=\"Clear Phase 2 Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    // Phase 3
    if (isset($_SESSION['filter']['p3'])) {
       if ($_SESSION['filter']['p3_interval'] == "le") {
          $showFilter .= "| Phase 3: &le; ".$_SESSION['filter']['p3'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?p3=".$_SESSION['filter']['p3']."&p3_interval=ge\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Phase 3: &ge; ".$_SESSION['filter']['p3'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?p3=".$_SESSION['filter']['p3']."&p3_interval=le\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?p3=x&p3_interval\" title=\"Clear Phase 3 Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    // Phase 4
    if (isset($_SESSION['filter']['p4'])) {
       if ($_SESSION['filter']['p4_interval'] == "le") {
          $showFilter .= "| Phase 4: &le; ".$_SESSION['filter']['p4'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?p4=".$_SESSION['filter']['p4']."&p4_interval=ge\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Phase 4: &ge; ".$_SESSION['filter']['p4'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?p4=".$_SESSION['filter']['p4']."&p4_interval=le\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?p4=x&p4_interval\" title=\"Clear Phase 4 Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    // Phase 5
    if (isset($_SESSION['filter']['p5'])) {
       if ($_SESSION['filter']['p5_interval'] == "le") {
          $showFilter .= "| Phase 5: &le; ".$_SESSION['filter']['p5'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?p5=".$_SESSION['filter']['p5']."&p5_interval=ge\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Phase 5: &ge; ".$_SESSION['filter']['p5'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?p5=".$_SESSION['filter']['p5']."&p5_interval=le\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?p5=x&p5_interval\" title=\"Clear Phase 5 Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    // Storage Read
    if (isset($_SESSION['filter']['sr'])) {
       if ($_SESSION['filter']['sr_interval'] == "le") {
          $showFilter .= "| Storage Read: &le; ".$_SESSION['filter']['sr'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?sr=".$_SESSION['filter']['sr']."&sr_interval=ge\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Storage Read: &ge; ".$_SESSION['filter']['sr'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?sr=".$_SESSION['filter']['sr']."&sr_interval=le\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?sr=x&sr_interval\" title=\"Clear Storage Read Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    // Storage Write
    if (isset($_SESSION['filter']['sw'])) {
       if ($_SESSION['filter']['sw_interval'] == "le") {
          $showFilter .= "| Storage Write: &le; ".$_SESSION['filter']['sw'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?sw=".$_SESSION['filter']['sw']."&sw_interval=ge\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Storage Write: &ge; ".$_SESSION['filter']['sw'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?sw=".$_SESSION['filter']['sw']."&sw_interval=le\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?sw=x&sw_interval\" title=\"Clear Storage Write Filter\" class=\"filter_control\">Del</a>)</span> ";
    }
    // Logging
    if (isset($_SESSION['filter']['log'])) {
       if ($_SESSION['filter']['log_interval'] == "le") {
          $showFilter .= "| Logging: &le; ".$_SESSION['filter']['log'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?log=".$_SESSION['filter']['log']."&log_interval=ge\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Logging: &ge; ".$_SESSION['filter']['log'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?log=".$_SESSION['filter']['log']."&log_interval=le\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?log=x&log_interval\" title=\"Clear Logging Filter\" class=\"filter_control\">Del</a>)</span> ";
    }
    // Garbage Collection
    if (isset($_SESSION['filter']['gc'])) {
       if ($_SESSION['filter']['gc_interval'] == "le") {
          $showFilter .= "| Garbage Collection: &le; ".$_SESSION['filter']['gc'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?gc=".$_SESSION['filter']['gc']."&gc_interval=ge\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &ge;</a>)";
       } else {
          $showFilter .= "| Garbage Collection: &ge; ".$_SESSION['filter']['gc'];
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?gc=".$_SESSION['filter']['gc']."&gc_interval=le\" title=\"Invert Phase 2 Filter\" class=\"filter_control\">Invert to &le;</a>)";
       }
       $showFilter .= "(<a href=\"$thisPage?gc=x&gc_interval\" title=\"Clear Garbage Collection Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    // False Positive
    if (isset($_SESSION['filter']['falsePositive'])) {
       if ($_SESSION['filter']['falsePositive'] == FALSE) {
          $showFilter .= "| <span class=\"Negate\">Marked as False Positive</span>";
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?falsePositive=1\" title=\"Change to Events Marked as False Positive\" class=\"filter_control\">Not</a>)";
       } else {
          $showFilter .= "| Marked as False Positive ";
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?falsePositive=0\" title=\"Change to Events Not Marked as False Positive\" class=\"filter_control\">Not</a>)";          
       }
       $showFilter .= "  (<a href=\"$thisPage?falsePositive=x\" title=\"Clear False Positive Filter\" class=\"filter_control\">Del</a>)</span> ";
    }
    
    // Preserved
    if (isset($_SESSION['filter']['preserved'])) {
       if ($_SESSION['filter']['preserved'] == FALSE) {
          $showFilter .= "| <span class=\"Negate\">Preserved</span>";
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?preserved=1\" title=\"Change to Preserved Events\" class=\"filter_control\">Not</a>)";
       } else {
          $showFilter .= "| Preserved ";
          $showFilter .= "<span class=\"filter_control\" > (<a href=\"$thisPage?preserved=0\" title=\"Change to Not Preserved Events\" class=\"filter_control\">Not</a>)";
       }
       $showFilter .= "  (<a href=\"$thisPage?preserved=x\" title=\"Clear False Positive Filter\" class=\"filter_control\">Del</a>)</span> ";
    }

    
    $showFilter .= "}";
    $showFilter .= " <a href=\"$thisPage?filter=x\"><b>Clear Filter</b></a> ";
    //$showFilter .= " </td>";
    // $showFilter .= "<input type=\"button\" name=\"delByFilter\" onClick=\"if(confirm('Confirm deletion of ALL events using current filter?')) submitformDelByFilter(); else unselectAll(this);\" value=\"DeleteByFilter\">";
    $showFilter .= "</div>";
    $showFilter .= "</div>";
    $showFilter .= "</div>";
    print $showFilter;
}

?>
