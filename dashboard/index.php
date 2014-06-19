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
$pagId = 'dashboard';
$thisPage = basename(__FILE__);
require_once("../session.php");
// Include jqplot
$jsChart = TRUE;

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

        <?PHP
        // Get total events as looked by filter
            $eventsCount = eventFilterCount();
        ?>

         <div id="dashColumn1">
         <br />

         <div class="dashchart">
            <div class="ChartTitle">Events per sensor</div>
            <div class="ChartBody" id="chartSensors"></div>
         </div>

         <script>
            <?PHP
            
            $sensorPie = statsEventSensor();
            $pieSensorData = null;

            if (is_array($sensorPie)) {
                $sensorLoop = 0;
                reset($sensorPie);
                while (list($key, $sensorData) = each($sensorPie)) {
                    $name = isset($sensorData['sensor_name']) ? $sensorData['sensor_name'] : 'Unknow';
                    $count = isset($sensorData['sensor_percent']) ? $sensorData['sensor_percent'] : 0;
                    $url = isset($sensorData['sensor_id']) ? "index.php?src_sensor=" .$sensorData['sensor_id'] : null;

                    $pieSensorData = $pieSensorData . "['$name', $count, '$url' ]";
                    if ( $sensorLoop < count($sensorPie) - 1) {
                        $pieSensorData = $pieSensorData . ",\n";
                    }
                    $sensorLoop++;
                }
            } else {
                $pieSensorData = "['none',0]";
            }

            print "var pieData =[" . $pieSensorData . "];\n";
            ?>
            $.jqplot('chartSensors', [pieData], {
               seriesColors: [ "#D43400","#006400", "#FFDA2B","#185DBC","#73CC00","#881141","#8AC3F4","#05493C"],
               seriesDefaults: {
                  renderer: jQuery.jqplot.PieRenderer,
                  pointLabels: {
                     show: true,
                     location: 'e',
                     edgeTolerance: -15
                  },
                  shadowAngle: 135,
                  rendererOptions: {
                     showDataLabels: true,
                     dataLabelThreshold: 3,
                     dataLabelPositionFactor:0.72,
                     sliceMargin: 3,
                     fill: true,
                     padding: 7,
                  }
               },
               grid:{borderWidth:0, shadow:false},
               legend: { show:true, location: 'ne' }
            });

         $('#chartSensors').bind('jqplotDataClick',
            function (ev, seriesIndex, pointIndex, data) {
                window.location.href=data[2]
            }
         );

        </script>
        <br />

         <div class="dashchart">

            <div class="ChartTitle">Events per severity</div>
            <div class="ChartBody" id="chartSeverity"></div>
         </div>

         <script>
            <?PHP

            $severityPie = statsTopSeverity();
            $pieseverityData = null;
           
            if (is_array($severityPie)) {
                $severityLoop = 0;
                reset($severityPie);
                while (list($key, $severityData) = each($severityPie)) {
                    $name = isset($severityData['severity']) ? $severity[$severityData['severity']] : 'Unknow';
                    $count = isset($severityData['severity_percent']) ? $severityData['severity_percent'] : 0;
                    $url = isset($severityData['severity']) ? "index.php?severity=" .$severityData['severity'] : null;

                    $pieseverityData = $pieseverityData . "['$name', $count, '$url' ]";
                    if ( $severityLoop < count($severityPie) - 1) {
                        $pieseverityData = $pieseverityData . ",\n";
                    }
                    $severityLoop++;
                }
            } else {
                $pieseverityData = "['none',0]";
            }
            
            print "var pieseverityData =[" . $pieseverityData . "];\n";
            ?>
            $.jqplot('chartSeverity', [pieseverityData], {
               seriesColors: [ "#D43400","#006400", "#FFDA2B","#185DBC","#73CC00","#881141","#8AC3F4","#05493C"],
               seriesDefaults: {
                  renderer: jQuery.jqplot.PieRenderer,
                  pointLabels: {
                     show: true,
                     location: 'e',
                     edgeTolerance: -15
                  },
                  shadowAngle: 135,
                  rendererOptions: {
                     showDataLabels: true,
                     dataLabelThreshold: 3,
                     dataLabelPositionFactor:0.72,
                     sliceMargin: 3,
                     fill: true,
                     padding: 7,
                  }
               },
               grid:{borderWidth:0, shadow:false},
               legend: { show:true, location: 'ne' }
            });

         $('#chartSeverity').bind('jqplotDataClick',
            function (ev, seriesIndex, pointIndex, data) {
                window.location.href=data[2]
            }
         );

         </script>
         <br />

         <div class="dashchart">

            <div class="ChartTitle">Events per status</div>

            <div class="ChartBody" id="chartStatus"></div>
         </div>

         <script>
            <?PHP

            $statusPie = statsTopStatus();
            $pieStatusData = null;
            foreach ($statusPie as $statusData) {
               $pieStatusData = $pieStatusData . "['" . $statusData["status"] . " " . $statusData["msg"] . "'," . $statusData["status_count"] . ",'" . $statusData["status"] . "'],";
            }
            print "var pieStatusData =[" . $pieStatusData . "];\n";
            ?>
            $.jqplot('chartStatus', [pieStatusData], {
               seriesColors: [ "#D43400","#006400", "#FFDA2B","#185DBC","#73CC00","#881141","#8AC3F4","#05493C"],
               seriesDefaults: {
                  renderer: jQuery.jqplot.PieRenderer,
                  pointLabels: {
                     show: true,
                     location: 'e',
                     edgeTolerance: -15
                  },
                  shadowAngle: 135,
                  rendererOptions: {
                     showDataLabels: true,
                     dataLabelThreshold: 3,
                     dataLabelPositionFactor:0.72,
                     sliceMargin: 3,
                     fill: true,
                     padding: 7,
                  }
               },
               grid:{borderWidth:0, shadow:false},
               legend: { show:true, location: 'ne' }
            });

         $('#chartStatus').bind('jqplotDataClick',
            function (ev, seriesIndex, pointIndex, data) {
                window.location.href='index.php?http_Status=' + data[2]
            }
         );

         </script>
         <br />

         <div class="dashchart">
            <div class="ChartTitle">Top Sources</div>
            <div class="ChartBody" id="chartTopSource"></div>
            <div class="ChartBottom"></div>
         </div>

         <script>
         <?PHP
            $topSource = statsTopSources();
            if (is_array($topSource)) {
               $topSourceData = null;
               $topSourceIP = null;
               foreach ($topSource as $source) {
                  $topSourceIP = $topSourceIP . "['$source[client_ip]',$source[source_count],'$source[client_ip]'],";
                  if ($maxSourceCount < $source['source_count']) {
                     $maxSourceCount = $source['source_count'];
                  }
               }
               print "bar1 = [$topSourceIP];\n";
            } else {
               print "bar1 = [[null,null,null]];\n";
            }
            ?>

            plot3b = $.jqplot('chartTopSource', [bar1], {
               seriesColors: [ "#D43400","#006400", "#FFDA2B","#185DBC","#73CC00","#881141","#8AC3F4","#05493C"],
               gridPadding:{right:5},
               axesDefaults: {
                  labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
               },
               seriesDefaults: {
                  renderer: $.jqplot.BarRenderer,
                  rendererOptions: {
                     barPadding: 6,
                     barMargin: 10,
                     varyBarColor: true,
                  },
               },
               axes: {
                  xaxis: {
                     renderer: $.jqplot.CategoryAxisRenderer,
                     tickRenderer: $.jqplot.CanvasAxisTickRenderer ,
                     tickOptions: {
                        angle: -30,
                     }
                  },
                  yaxis: {
                     min: 0,
                     <?PHP
                     print "max: $maxSourceCount*1.05,\n";
                     ?>
                     numberTicks:5,
                     label: "Events",
                     tickOptions: {formatString:'%d'},
                  },
               },
            });

            $('#chartTopSource').bind('jqplotDataClick',
               function (ev, seriesIndex, pointIndex, data) {
                  window.location.href='index.php?esrc=' +data[2]
               }
            );
         </script>
         <br />

         <div class="dashchart">
            <div class="ChartTitle">Top Targets</div>
            <div class="ChartBody" id="chartTopTargets"></div>
            <div class="ChartBottom"></div>
         </div>

         <script>
         <?PHP
            $topTargets = statsTopTargets();
            if (is_array($topTargets)) {
               $topTargetData = null;
               $topTargetName = null;
               foreach ($topTargets as $target) {
                  $topTargetData = $topTargetData . "['".getWebHostName($target['b_host'])."',$target[host_count],'$target[b_host]'],";
                  if ($maxTargetCount < $target['host_count']) {
                     $maxTargetCount = $target['host_count'];
                  }
               }
               print "targetBar1 = [$topTargetData];\n";
            } else {
               print "targetBar1 = [[null,null,null]];\n";
            }

            ?>

            $.jqplot('chartTopTargets', [targetBar1], {
               seriesColors: [ "#D43400","#006400", "#FFDA2B","#185DBC","#73CC00","#881141","#8AC3F4","#05493C"],
               gridPadding:{right:5},
               axesDefaults: {
                  labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
               },
               seriesDefaults: {
                  renderer: $.jqplot.BarRenderer,
                  rendererOptions: {
                     barPadding: 6,
                     barMargin: 10,
                     varyBarColor: true,
                  },
               },
               axes: {
                  xaxis: {
                     renderer: $.jqplot.CategoryAxisRenderer,
                     tickRenderer: $.jqplot.CanvasAxisTickRenderer ,
                     tickOptions: {
                        angle: -30,
                     }
                  },
                  yaxis: {
                     min: 0,
                     <?PHP
                     print "max: $maxTargetCount*1.05,\n";
                     ?>
                     numberTicks:5,
                     label: "Events",
                     tickOptions: {formatString:'%d'},
                  },
               },
            });

            $('#chartTopTargets').bind('jqplotDataClick',
               function (ev, seriesIndex, pointIndex, data) {
                  window.location.href='index.php?web_Hostname=' +data[2]
               }
            );

         </script>
         <br />



         </div>

         <div id="dashColumn2">
         <br />

         <div class="dashchartLarge">
            <div class="ChartTitleLarge">Events action over time (Total: <?PHP print number_format($eventsCount); ?>)</div>
            <div class="ChartBodyLarge" id="chartEvents"></div>
            <div class="ChartBottomLarge"></div>
         </div>

         <script>

            <?PHP
            list($step, $stepLabel, $legend, $statsEvents) = statsEvents();
            $minXAxis = key($statsEvents);

            reset($statsEvents);

            $maxCount = 0;
            foreach ($statsEvents as $timestamp => $eventCount) {
               $chartDataPass    = $chartDataPass . "['" . $timestamp . "'," . $eventCount['allow'] . "],";
               $chartDataBlock   = $chartDataBlock . "['" . $timestamp . "'," . $eventCount['block'] . "],";
               $chartDataWarning = $chartDataWarning . "['" . $timestamp . "'," . $eventCount['warning'] . "],";
               if ($maxCount < ($eventCount['allow'] + $eventCount['block'] + $eventCount['warning'])) {
                  $maxCount = ($eventCount['allow'] + $eventCount['block'] + $eventCount['warning']);
                  $maxXAxis = $timestamp;
               }
               $maxXAxis = $timestamp;
            }

            print "var chartDataPass    =[" . $chartDataPass . "];\n";
            print "var chartDataBlock   =[" . $chartDataBlock . "];\n";
            print "var chartDataWarning =[" . $chartDataWarning . "];\n";
            ?>

            $.jqplot.config.catchErrors = true;

            $.jqplot.config.errorMessage    = 'A Plot Error has Occurred';
            $.jqplot.config.errorBackground = '#fbeddf';
            $.jqplot.config.errorBorder     = '2px solid #aaaaaa';
            $.jqplot.config.errorFontFamily = 'Courier New';
            $.jqplot.config.errorFontSize   = '16pt';

            $.jqplot('chartEvents', [chartDataPass, chartDataWarning, chartDataBlock], {
                stackSeries: true,
                axesDefaults: {
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer
                },

                legend: {
                    show: true,
                    location: 'ne',
                },
                axes:{
                    xaxis:{
                        renderer:$.jqplot.DateAxisRenderer,
                        tickOptions:{
                            formatString:'%H:%M<br>%d/%m',
                        },
                        min:'<?PHP print $minXAxis; ?>',
                        max:'<?PHP print $maxXAxis; ?>',
                        tickInterval:'<?PHP print $stepLabel; ?>',
                        label: 'Total of events each <?PHP print $legend; ?> ',
                    },
                    yaxis: {
                        label: "Events",
                        tickOptions: {formatString:'%d'},
                        min:0,
                        <?PHP
                        if ($maxCount < 6) {
                            print "max:$maxCount,\n";
                            print "tickInterval:1,\n";
                        } else {
                            print "max: ". $maxCount*1.05 . ",\n";
                            print "tickInterval:($maxCount*1.05)/6,\n";
                        }
                        ?>
                    }
                },
                seriesDefaults: {
                    rendererOptions: {
                        highlightMouseOver: true,
                        highlightMouseDown: true,
                        highlightColor: null,
                        fill: true,
                        showMarker: false,
                        smooth: true,
                    },
                },
                series:[{
                    lineWidth:1,
                    label: 'Allow',
                    color: '#006400',
                    fillColor: "#006400",
                    fillAndStroke: true,
                    fill: true,
                    breakOnNull: true,
                    markerOptions:{
                        show: false,
                    }
                },
                {
                    lineWidth:1,
                    label: 'Warning',
                    color: '#F9EE10',
                    fillColor: "#F9EE10",
                    fillAndStroke: true,
                    fill: true,
                    markerOptions:{
                        show: false,
                    }
               },
               {
                    lineWidth:1,
                    label: 'Block',
                    color: '#d43400',
                    fillColor: "#d43400",
                    fillAndStroke: true,
                    fill: true,
                    markerOptions:{
                        show: false,
                    }
                }]
            });
            $('.jqplot-highlighter-tooltip').addClass('ui-corner-all')
            $('#chartEvents').bind('jqplotDataClick',
                function (ev, seriesIndex, pointIndex, data) {
                    if (seriesIndex == 1) {
                        window.location.href='index.php?actionstatus=warning'
                    } else if (seriesIndex == 2) {
                        window.location.href='index.php?actionstatus=block'
                    } else if (seriesIndex == 0) {
                        window.location.href='index.php?actionstatus=allow'
                    }
                }
            );

         </script>

         <br />
         <br />

         <div class="dashchartLarge">
            <div class="ChartTitleLarge">Top Rules</div>
            <div class="ChartBodyLarge" id="chartTopRules"></div>
            <div class="ChartBottom"><span>Description: </span><span id="infoRules">Put the mouse over a bar to get Rule ID and description</span></div>
         </div>


         <?PHP
            $topRulesBar = statsTopRules();

            if (is_array($topRulesBar) && !empty($topRulesBar)) {
               $topRulesData = null;
               $maxRulesCount = 0;
               foreach ($topRulesBar as $rulesData) {
                  $topRulesData = $topRulesData . "['" . $rulesData["message_ruleId"] . "'," . $rulesData["rule_count"] . ",'" . $rulesData["message_ruleMsg"] . "','" . $rulesData["message_ruleId"] . "'],";
                  if ($maxRulesCount < $rulesData['rule_count']) {
                     $maxRulesCount = $rulesData['rule_count'];
                  }
               }
               print "<script>";
               print "var barRulesData = [" . $topRulesData . "];";
            ?>

               plot3b = $.jqplot('chartTopRules', [barRulesData], {
                //  title: 'Top Rules in last 24 hours',
                  seriesColors: [ "#D43400","#006400", "#FFDA2B","#185DBC","#73CC00","#881141","#8AC3F4","#05493C"],
                  gridPadding:{right:5},
                  axesDefaults: {
                     labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                  },
                  seriesDefaults: {
                     renderer: $.jqplot.BarRenderer,
                     rendererOptions: {
                        barPadding: 6,
                        barMargin: 10,
                        varyBarColor: true,
                     },
                  },
                  axes: {
                     xaxis: {
                        renderer: $.jqplot.CategoryAxisRenderer,
                        tickRenderer: $.jqplot.CanvasAxisTickRenderer ,
                        tickOptions: {
                           angle: -30,
                        }
                     },
                     yaxis: {
                        min: 0,
                        <?PHP
                        print "max: $maxRulesCount*1.05,\n";
                        ?>
                        numberTicks:5,
                        label: "Events",
                        tickOptions: {formatString:'%d'},
                     },
                  },
               });

               $('#chartTopRules').bind('jqplotDataClick',
                  function (ev, seriesIndex, pointIndex, data) {
                     window.location.href='index.php?ruleid=' + data[3]
                  }
               );
               $('#chartTopRules').bind('jqplotDataHighlight',
                  function (ev, seriesIndex, pointIndex, data) {
                     $('#infoRules').html('<b>'+data[3]+': </b>'+data[2]);
                  }
               );
               $('#chartTopRules').bind('jqplotDataUnhighlight',
                  function (ev) {
                     $('#infoRules').html('');
                  }
               );
            </script>
            <?PHP
            } 
            ?>

         <br />

         <?PHP
         $topPath = statsTopPath();
         ?>
         
         <div class="gridDashLarge">

            <div id="PathTable">
                <table class="flexPath">
                <tbody>
                
                <?PHP
                foreach($topPath as $Path) {
                    print "<tr>";
                    if ($Path['b_path'] == "") {
                        print "<td >Unknow</td>";
                    } else {
                        $path2print = headerprintnobr($Path['b_path']);
                        print "<td ><a href=\"?path=$path2print\">$path2print</a></td>";
                    }
                    $pathPercentual = round(($Path['b_path_count'] * 100)/ $eventsCount, 2);
                    print "<td>$pathPercentual</td>";
                    print "<td>".$Path['b_path_count']."</td>";
                    print "</tr>";
                    }
                ?>
                </tbody>
                </table>
            </div>
         </div>
         <div id="clear"> </div>
         <br />

         <div class="gridDashLarge">
            <?PHP         
                $topCC = statsTopCC();
            ?>
            <div class="gridColumn">
                <div class="gridBodyLarge" id="countryTable">
                    <table class="flexCountry">
                    <tbody>
                        <?PHP
                        foreach($topCC as $CC) {
                            print "<tr>";
                            if ($CC['client_cc'] == "") {
                                print "<td>Unknow</td>";
                            } else {
                                $countryName = geoip_record_by_name($CC['client_cc']);
                                print "<td><a href=\"?ipcc=".$CC['client_cc']."\"> <img src=\"images/flags/png/".strtolower(headerprintnobr($CC['client_cc'])).".png\" alt=\"". headerprintnobr($CC['client_cc']) ."\" style=\"border-style: none\"> ". headerprintnobr($CC['client_cc']) ."</a></td>";
                            }
                            $ccPercentual = round(($CC['client_cc_count'] * 100)/ $eventsCount, 2);
                            print "<td>$ccPercentual</td>";
                            print "<td>".number_format($CC['client_cc_count'])."</td>";
                            print "</tr>";
                            }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?PHP         
                $topASN = statsTopASN();
            ?>
            <div class="gridColumn">
                <div class="gridBodyLarge" id="ASTable">
                    <table class="flexASN">
                    <tbody>
                    <?PHP
                    foreach($topASN as $ASN) {
                        print "<tr>";
                        if ($ASN['client_ASN'] == "") {
                            print "<td>Unknow</td>";
                        } else {
                            print "<td><a href=\"?ipasn=".$ASN['client_ASN']."\">".$ASN['client_ASN']."</a></td>";
                        }
                        $asnPercentual = round(($ASN['client_ASN_count'] * 100)/ $eventsCount, 2);
                        print "<td>$asnPercentual</td>";
                        print "<td>".$ASN['client_ASN_count']."</td>";
                        print "</tr>";
                        }
                    ?>
                    </tbody>
                    </table>
                </div>
            </div>
	        <div id="clear"> </div>           
         </div>

         </div>
         <div id="clear"> </div>
        <br />
        <div id="clear"> </div>

        </div>
    </div>
    
   <script type="text/javascript">
        $('.flexCountry').flexigrid({
            title : 'Top Countries Source',
            resizable: false,
            height: 'auto', //default height
            width: 'auto', //auto width
            colModel : [
                {display: 'Country Code', name : 'cc', width : 110, sortable : false, align: 'left'},
                {display: '%', name : 'ccpercent', width : 70, sortable : false, align: 'left'},
                {display: 'Events', name : 'ccevents', width : 90, sortable : false, align: 'left'},
            ],

            showToggleBtn: false,
        });
        $('.flexASN').flexigrid({
            title : 'Top AS Source',
            resizable: false,
            height: 'auto', //default height
            width: 'auto', //auto width
            colModel : [
                {display: 'Autonomous System', name : 'asn', width : 110, sortable : false, align: 'left'},
                {display: '%', name : 'asnpercent', width : 70, sortable : false, align: 'left'},
                {display: 'Events', name : 'asnevents', width : 90, sortable : false, align: 'left'},
            ],
            showToggleBtn: false,
        });
        $('.flexPath').flexigrid({
            title : 'Top Path',
            resizable: false,
            height: 'auto', //default height
            width: 'auto', //auto width
            colModel : [
                {display: 'Path', name : 'path', width : 430, sortable : false, align: 'left'},
                {display: '%', name : 'pathPercent', width : 100, sortable : false, align: 'left'},
                {display: 'Events', name : 'pathEvents', width : 100, sortable : false, align: 'left'},
            ],
            showToggleBtn: false,
            nowrap: false,
        });

        
        
    </script>    
    
<?PHP
require_once("../footer.php");
?>
