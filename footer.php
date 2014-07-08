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

    <div id="clear"></div>
    <div id="footer">
        <p><a href="http://waf-fle.org">WAF-FLE</a>, version <?PHP print $waffleVersion; ?>. Released under <a href="http://creativecommons.org/licenses/GPL/2.0/">GNU General Public License, version 2</a>. </p>
    </div>
   <!-- ui-dialog -->
<?PHP

if ($_SESSION['login'] AND !$hideFilter) {
    print "<div id=\"dialog\" title=\"Filter Editor\" class=\"dialog_style1\">";
    print "<p>";

    include_once "../filter.php";
    print "</p>";
    print "</div>";
}

if ($_SESSION['login'] AND !$hideFilter AND $pagId == 'events') {
	print "<div id=\"dialogDeleteByFilter\" title=\"Delete Events of Current Filter\" class=\"dialog_deleteByFilter\">";
	print "<p>";
	print "Do you really want to delete ALL (".$_SESSION['eventCount'].") events using Current Filter below?<br /><br />";
	print $showFilter;
	print "<br />";

	print "<div id=\"progressbar\"></div>";
	
	print "<div id=\"showdata\"></div>";
	print "<div id=\"showdelmsg\"></div>";
	print "<br />";
	print "</p>";
	print "</div>";


    print "<div id=\"dialogFalsePositiveByFilter\" title=\"Mark All Events of Current Filter as False Positive \" class=\"dialog_falsePositiveByFilter\">";
	print "<p>";
	print "Do you really want to mark ALL (".$_SESSION['eventCount'].") events as false positive using Current Filter below?<br /><br />";
	print $showFilter;
	print "<br />";

	print "<div id=\"FPprogressbar\"></div>";
	
	print "<div id=\"FPshowdata\"></div>";
	print "<div id=\"FPshowmsg\"></div>";
	print "<br />";
	print "</p>";
	print "</div>";
    
} elseif ($_SESSION['login'] AND !$hideFilter AND $pagId == 'management' AND $sensorToDelete) {
	$sensorName = getsensorname($sensorToDelete);
	$sensorInfo = getSensorInfo($sensorToDelete);
	print "<div id=\"dialogDeleteSensor\" title=\"Delete Sensor\" class=\"dialog_deleteByFilter\">\n";
	print "<p>\n";
	print "Do you really want to delete sensor<b> ".$sensorName['name']." </b> and ALL its ". number_format($sensorInfo['sensorEvents']) ." events?<br /><br />\n";
	print "<br />";

	print "<div id=\"progressbarDeleteSensor\"></div>\n";
	
	print "<div id=\"showdataDeleteSensor\"></div>\n";
	print "<div id=\"showdelmsgDeleteSensor\"></div>\n";
	print "<br />";
	print "</p>";
	print "</div>";
}

?>

<?PHP
if ($DEBUG) {
    $stoptime_main                  = microtime(true);
    $timespend                      = $stoptime_main - $starttime_main;
    $thisScript                     = $_SERVER['PHP_SELF'];
    $debugInfo[$thisScript][0]['time'] = $timespend;

    print "<pre class=\"printCode\">";
    foreach ($debugInfo as $stepBreak => $stepData) {
        if (is_array($stepData)) {
            foreach ($stepData as $funcQuery) {
                print "<b>Step:</b> $stepBreak, <b>Time:</b> ".$funcQuery['time'].", ";
                if (isset($funcQuery['cache'])) {
                    print "<b>Cache:</b> ".$funcQuery['cache']."\n";    
                }
                if (isset($funcQuery['query'])) {
                    if (is_array($funcQuery['query'])) {
                        foreach ($funcQuery['query'] as $funcQuery2) {
                            print " <b>Query:</b> $funcQuery2\n";
                        }
                    } else {
                        print " <b>Query:</b> ".$funcQuery['query']."\n";
                    }
                }
                print "\n";
            }
        } else {
            print "<b>Step:</b> $stepBreak, <b>Time:</b> ".$stepData['time'].", <b>Cache:</b> ".$stepData['cache']."\n";
        }        
    }
    print "</pre>";
}

// update last activity timestamp, on page processing finish
session_refresh();

?>

</body>
</html>
