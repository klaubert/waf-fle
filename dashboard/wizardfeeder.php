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
global $DEBUG;
if ($DEBUG) {
    $starttime_main = microtime(true);
}
$pagId = 'wizardfeeder';
require_once "../session.php";
require_once "../header.php";

?>
<div id="page-wrap">
   <div id="main-content">

    <?PHP

    if (isset($_GET['sensor']) AND @sanitize_int($_GET['sensor'], $min = '0') AND $sensorDetail = getSensorName($_GET['sensor'])) {  // Sensors Tasks
        $sensor = $_GET['sensor'];

        if (isset($_GET['wiz']) AND $_GET['wiz'] == "Next" AND isset($_GET['sensor']) AND isset($_GET['feeder']) AND 
        (
            ($_GET['feeder'] == "mlogc") 
            OR 
            ($_GET['feeder'] = "mlog2waffle")
        ) AND isset($_GET['usage']) AND 
        (
            $_GET['usage']=="piped" 
            OR 
                ($_GET['usage'] == "scheduled" AND isset($_GET['logfile']))
            OR 
                ($_GET['usage'] == "service" AND isset($_GET['logfile']))
        ) AND isset($_GET['address']) AND isset($_GET['logdir'])) {  // Show config template
            $feeder = $_GET['feeder'];
            $usage = $_GET['usage'];
        ?>
            
            <div id="management_menu">
             <p>
             
             <h3>Templates generated for:</h3><br>
             <?PHP
             print "<b>Sensor:</b> ".headerprintnobr($sensorDetail['name'])." <br />";
             print "<b>Feeder:</b> ".headerprintnobr($feeder)." <br />";
             print "<b>Usage:</b> ".headerprintnobr($usage)." <br />";
             print "<b>Controller:</b> ".headerprintnobr($_GET['address'])."<br />";
             if (isset($_GET['logfile'])) {
                print "<b>Log file:</b> ".headerprintnobr($_GET['logfile'])."<br />";
             }
             print "<b>Audit directory:</b> ".headerprintnobr($_GET['logdir'])."<br />";
             
             ?>

             </p>
          </div>        
            
        
        
        <div id="management_content_bg">
        <div id="management_content">
            <script>
            $(function() {
                $('#tabs').tabs({ fx: [{opacity:'toggle', duration:'fast'}] }); // show option

            });
            </script>
            <div id="tabs">

                <ul>
                    <li><a href="#tabs-1">ModSecurity/Apache config<br>modsecurity.conf</a></li>
                    <?PHP
                    if ($_GET['feeder'] == "mlogc") {
                        print "<li><a href=\"#tabs-2\">mlogc config<br>/etc/mlogc.conf</a></li>";
                    } elseif ($_GET['feeder'] == "mlog2waffle") {
                        print "<li><a href=\"#tabs-2\">mlog2waffle config<br>/etc/mlog2waffle.conf</a></li>";
                    }
                    print "<li><a href=\"#tabs-4\">Log Directory<br> &nbsp;</a></li>";
                    if ($_GET['usage'] == "scheduled") {
                        print "<li><a href=\"#tabs-3\">Crontab<br />contrab -e</a></li>";
                       // print "<li><a href=\"#tabs-5\">Log Rotate<br />/etc/logrotate.d/modsecurity</a></li>";
                    } elseif ($_GET['usage'] == "service") {
                        print "<li><a href=\"#tabs-3\">Config service run<br />/etc/init.d/</a></li>";
                    }
                    ?>
                    
                </ul>
                <div id="tabs-1">
                
                    <p><b>This is a portion of modsecurity.conf (you should have a complete one on your configuration, or you can get one on ModSecurity source package. Edit it to include the section below, but check if it make sense to your needs).</b><br /><br />
                <?PHP
                if ($_GET['feeder'] == "mlogc") {
                    if ($_GET['usage'] == "piped") {
                ?>
<pre>
                    
# ...
# -- Audit log configuration -------------------------------------------------

# Log the transactions that are marked by a rule, as well as those that
# trigger a server error (determined by a 5xx or 4xx, excluding 404,
# level response status codes).
#
SecAuditEngine RelevantOnly
SecAuditLogRelevantStatus "^(?:5|4(?!04))"

# Log everything we know about a transaction.
SecAuditLogParts ABIDEFGHZ

SecAuditLogType Concurrent

SecAuditLog "|/usr/local/bin/mlogc /etc/mlogc.conf"

# Specify the path for concurrent audit logging.
SecAuditLogStorageDir <?PHP print headerprintnobr($_GET['logdir']) ;?>

# ... Continue with your current modsecurity.conf

</pre>
<?PHP
                    } elseif ($_GET['usage'] == "scheduled") {
                    ?>
<pre>
                    
# ...
# -- Audit log configuration -------------------------------------------------

# Log the transactions that are marked by a rule, as well as those that
# trigger a server error (determined by a 5xx or 4xx, excluding 404,
# level response status codes).
#
SecAuditEngine RelevantOnly
SecAuditLogRelevantStatus "^(?:5|4(?!04))"

# Log everything we know about a transaction.
SecAuditLogParts ABIDEFGHZ

# Use a single file for logging. This is much easier to look at, but
# assumes that you will use the audit log only ocassionally.
#
SecAuditLogType Concurrent

SecAuditLog <?PHP print headerprintnobr($_GET['logfile']) . "\n"; ?>

# Specify the path for concurrent audit logging.
SecAuditLogStorageDir <?PHP print headerprintnobr($_GET['logdir']) ."\n"; ?>

# ... Continue with your current modsecurity.conf
</pre>                
                <?PHP
                    }
                } elseif ($_GET['feeder'] == "mlog2waffle") {
?>
    <pre>

# ...
# -- Audit log configuration -------------------------------------------------

# Log the transactions that are marked by a rule, as well as those that
# trigger a server error (determined by a 5xx or 4xx, excluding 404,
# level response status codes).
#
SecAuditEngine RelevantOnly
SecAuditLogRelevantStatus "^(?:5|4(?!04))"

# Log everything we know about a transaction.
SecAuditLogParts ABIDEFGHZ

# Use a single file for logging. This is much easier to look at, but
# assumes that you will use the audit log only ocassionally.
#
SecAuditLogType Concurrent

SecAuditLog <?PHP print headerprintnobr($_GET['logfile']) ?>

# Specify the path for concurrent audit logging.
SecAuditLogStorageDir <?PHP print headerprintnobr($_GET['logdir']) ?>

# ... Continue with your current modsecurity.conf

</pre>
<?PHP
} 
    
?>

                    </p>
                </div>
                <div id="tabs-2">
                <?PHP
                if ($_GET['feeder'] == "mlogc") {
                    print "<p><b>This is mlogc.conf file (check if it make sense to your needs).</b><br /><br />";
                ?>
<pre>

# Points to the root of the installation. All relative
# paths will be resolved with the help of this path.
CollectorRoot       "<?PHP print headerprintnobr(dirname($_GET['logdir'])); ?>"

# ModSecurity Console receiving URI. You can change the host
# and the port parts but leave everything else as is.
ConsoleURI          "<?PHP print headerprintnobr($_GET['address']) ?>"

# Sensor credentials
SensorUsername      "<?PHP print headerprintnobr($sensorDetail['name']) ?>"
SensorPassword      "<?PHP print headerprintnobr($sensorDetail['password']) ?>"

# Base directory where the audit logs are stored.  This can be specified
# as a path relative to the CollectorRoot, or a full path.
LogStorageDir       "<?PHP print headerprintnobr(basename($_GET['logdir'])); ?>"

# Transaction log will contain the information on all log collector
# activities that happen between checkpoints. The transaction log
# is used to recover data in case of a crash (or if Apache kills
# the process).
TransactionLog      "mlogc-transaction.log"

# The file where the pending audit log entry data is kept. This file
# is updated on every checkpoint.
QueuePath           "mlogc-queue.log"

# The location of the error log.
ErrorLog            "mlogc-error.log"

# The location of the lock file.
LockFile            "mlogc.lck"

# Keep audit log entries after sending? (0=false 1=true)
# NOTE: This is required to be set in SecAuditLog mlogc config if you
# are going to use a secondary console via SecAuditLog2.
KeepEntries         0

##########################################################################
# Optional configuration
##########################################################################

# The error log level controls how much detail there
# will be in the error log. The levels are as follows:
#   0 - NONE
#   1 - ERROR
#   2 - WARNING
#   3 - NOTICE
#   4 - DEBUG
#   5 - DEBUG2
#
ErrorLogLevel       3

# How many concurrent connections to the server
# are we allowed to open at the same time? Log collector uses
# multiple connections in order to speed up audit log transfer.
# This is especially needed when the communication takes place
# over a slow link (e.g. not over a LAN).
MaxConnections      10

# How many requests a worker will process before recycling itself.
# This is to help prevent problems due to any memory leaks that may
# exists.  If this is set to 0, then no maximum is imposed. The default
# is 1000 requests per worker (the number of workers is controlled by the
# MaxConnections limit).
MaxWorkerRequests   1000

# The time each connection will sit idle before being reused,
# in milliseconds. Increase if you don't want ModSecurity Console
# to be hit with too many log collector requests.
TransactionDelay    50

# The time to wait before initialization on startup in milliseconds.
# Increase if mlogc is starting faster then termination when the
# sensor is reloaded.
StartupDelay        5000

# How often is the pending audit log entry data going to be written
# to a file. The default is 15 seconds.
CheckpointInterval  15

# If the server fails all threads will back down until the
# problem is sorted. The management thread will periodically
# launch a thread to test the server. The default is to test
# once in 60 seconds.
ServerErrorTimeout  60

# The following two parameters are not used yet, but
# reserved for future expansion.
# KeepAlive         150
# KeepAliveTimeout  300



</pre>
                        
                <?PHP
                } elseif ($_GET['feeder'] == "mlog2waffle") {
                    print "<p><b>This is mlog2waffle.conf file (check if it make sense to your needs)</b>.<br /><br />";
?>
    <pre>
# Configuration file for mlog2waffle
# modsecurity need to be configured to log in concurrent mode, example, 
# in Modsecurity config use some thing like this:
#
#   SecAuditLogParts ABIJDEFGHZ
#   SecAuditLogType Concurrent
#   SecAuditLog "/var/log/mlogc/mlogc-index"
#   SecAuditLogStorageDir /var/log/mlogc/data
#
# In this way you can set mlog2waffle to tail mode (see below) and 
# check file continuously, sending events in real time to WAF-FLE, or
# run a scheduled "batch" mode.
# 
# Requirements: File::Tail perl module, use your own or the provided 
# with WAF-FLE package (you may need to ajust the path in mlogc-waffle).


# Define the complete URI of WAF-FLE controller, http or https
$CONSOLE_URI = "<?PHP print headerprintnobr($_GET['address']); ?>";

# Define username used to put events on WAF-FLE for this sensor
$CONSOLE_USERNAME = "<?PHP print headerprintnobr($sensorDetail['name']); ?>";

# Define password used to put events on WAF-FLE for this sensor
$CONSOLE_PASSWORD = "<?PHP print headerprintnobr($sensorDetail['password']); ?>";

# $MODSEC_DIRECTORY is where the concurrent audit logs are stored. 
# In modsecurity configuration is defined by SecAuditLogStorageDir directive
$MODSEC_DIRECTORY = "<?PHP print headerprintnobr($_GET['logdir']); ?>/";

# $INDEX_FILE is defined by SecAuditLog modsecurity directive, it is a index
# file of events generated by concurrent log type
$INDEX_FILE = "<?PHP print headerprintnobr($_GET['logfile']); ?>";

# $ERROR_LOG is a mlogc-waffle error log, write permission is needed.
$ERROR_LOG = "<?PHP print headerprintnobr(dirname($_GET['logdir'])); ?>/mlogc-error.log";

# Define the execution mode:
#  - "tail": for run continuously, waiting for new entries on log file; 
#  - "batch": for run and exit at end, but recording (offset file) the 
#     position in the last run, speeding up next execution. You can schedule
#     the mlogc-waffle in crontab to run periocally (for example, each 5min).
$MODE = "<?PHP 
if ($_GET['usage'] == "scheduled") {
    print "batch";
}elseif($_GET['usage'] == "service") {
    print "tail";
}
?>";

# Set $FULL_TAIL = "TRUE" to make tail mode read full file at start, set to 
# "FALSE" to start to read at end of file.
$FULL_TAIL = "FALSE";

# $PIDFILE set the file used to store process id when running in tail mode, forked as a daemon
$PIDFILE = "/var/run/mlog2waffle.pid";

# Define offset file, used as a checkpoint for batch mode, it need permission
# to write in this file.
$OFFSET_FILE = "<?PHP print headerprintnobr(dirname($_GET['logdir'])); ?>/offset";

# Set the max number of threads used to send parallel events do WAF-FLE, 
# if you need more performance to push events to WAF-FLE, try to increase
# to a higher value. Remember, higher number of threads, higher CPU usage.
$THREADMAX = 2;
 
# Set $CHECK_CONNECTIVITY to "TRUE" to check connectivity with WAF-FLE before
# send any event to it. Set to "FALSE" to avoid the check, or for use with 
# another console.
$CHECK_CONNECTIVITY = "TRUE";

# If $DEBUG is set to "TRUE" it will write in $DEBUG_FILE the request and response
# between mlogc-waffle and WAF-FLE
$DEBUG = "FALSE";

# $DEBUG_FILE
$DEBUG_FILE = "<?PHP print headerprintnobr(dirname($_GET['logdir'])); ?>/mlog2waffle.debug";


</pre>
<?PHP
} 
    
?>

                    </p>
                </div>
                <div id="tabs-3">
                <?PHP
                    if ($_GET['usage'] == "scheduled") {
                        if ($_GET['feeder'] == "mlogc") {
                    ?>
                    To use mlogc scheduled, you need: <br>
                    <b>1.</b> mlogc-batch-load.pl, a script available in ModSecurity sources, used to process Audit Log, and create a event index, used by mlogc the send events.<br>
                    <br>
                    <b>2.</b> Use the a script like below to prepare and feed events:<br>
                    <br>
                    <b>/usr/local/sbin/push-mlogc.sh</b>
<pre>
#!/bin/bash

# Check if a old execution still running, and kill it
Status=0;
while [ $Status -eq 0 ]; do
  PmlogcBatch=`/sbin/pidof -x /usr/local/modsecurity/bin/mlogc-batch-load.pl`
  PplStatus=$?
  Pmlogc=`/sbin/pidof -x /usr/sbin/mlogc`
  PmlogcStatus=$?

  if [ $PplStatus -eq 0 ]; then
     kill -9 $PmlogcBatch
     echo "Killing $PmlogcBatch"
  fi
  if [ $PmlogcStatus -eq 0 ]; then
     kill -9 $Pmlogc
     echo "Killing $Pmlogc"
  fi

  if [ $PplStatus -ne 0 -a $PmlogcStatus -ne 0 ]; then
     Status=1;
  fi
done

# Start mlogc push
echo "Sending logs to WAF-FLE";
date
/usr/local/modsecurity/bin/mlogc-batch-load.pl <?PHP print headerprintnobr($_GET['logdir']); ?> \ 
/usr/local/modsecurity/bin/mlogc /etc/mlogc.conf

find  <?PHP print headerprintnobr($_GET['logdir']); ?> -type d -empty -delete
</pre>                    
                    <br>
                    
                    <b>3.</b> A crontab entry to run the script, each 5 minutes (or other periodic time, as you need)<br>
                    <pre>
*/5 * * * * /usr/local/sbin/push-mlogc.sh  > /tmp/mlog.log 2>&1    
                    </pre>
                        
                    <?PHP
                        
                        } elseif ($_GET['feeder'] == "mlog2waffle") {
                            ?>
                            
                            To use mlog2waffle scheduled, you need:<br><br />
                            
                            <b>1. </b> You need to copy mlog2waffle you your sensor box (you can found the script in WAF-FLE_DIR/extra/mlog2waffle/), in "/usr/sbin/" (or other directory that you wish).<br />
                            <br />                            
                            <b>2.</b> A crontab entry to run the it, each 5 minutes (or other periodic time, as you need)<br>
<pre>
*/5 * * * * /usr/sbin/mlog2waffle
</pre>   
                        
                        <?PHP
                        }
                    } elseif ($_GET['usage'] == "service") {
                    ?>
                    <b>How to start mlog2waffle</b><br />
                    To make mlog2waffle start automatically on boot, you need to copy the startup script (mlog2waffle.rhel or mlog2waffle.ubuntu) existing in WAF-FLE distribution package in WAF-FLE_DIR/extra/mlog2waffle/ directory to /etc/init.d/ of sensor machine. This script read /etc/mlog2waffe.conf described in the other tab.<br>
                     <p>&nbsp;</p>
                    <p><b>ATTENTION: read the &lt;WAF-FLE_DIR&gt;/extra/mlog2waffle/README to get more information on get mlog2waffle dependencies, options and modes of operation. Do this before run it for the first time.</b></p>
                    <p>&nbsp;</p>
                    
                    To make it startup, run <pre>
                    
                    # update-rc.d mlog2waffle defaults 99
                    # service mlog2waffle start
                    </pre>
                    


                      
                    <?PHP      
                    }
                
                    ?>
                </div>
                <div id="tabs-4">
                    <p><b>Create the directories to hold <?PHP print headerprintnobr(($_GET['feeder'])); ?> logs, and create the directories to store ModSecurity audit log files. </b></p><br />
                    <p><pre># mkdir -p <?PHP print headerprintnobr($_GET['logdir']); ?></pre></p>
                    <br>Remember: You will need to give ownership to user that run Apache server (ie. nobody, www-data etc) in <?PHP print headerprintnobr($_GET['logdir']); ?>. ie. <br />
                    <p><pre># chown nobody <?PHP print headerprintnobr($_GET['logdir']); ?></pre></p>
                </div>

            </div>

        <?PHP
        } else {
        ?>
        <div id="management_content_bg">
        <div id="management_content">
                
        <br />
        <p class="title">Choose the options bellow to create the event feeder configuration template.</p>
        <br />
         <p>

         <script>
            function toggleStatus(){
                if ($('#mlogc').is(':checked')) {
                        $('#usage_service').attr('disabled',"");
                        $('#usage_service').prop('checked', false);
                        $("#logdir").val("/var/log/mlogc/data");                        
                    } else{
                        $('#usage_service').removeAttr('disabled','disabled');
                        $('#logfile').removeAttr('disabled','disabled');
                };
                if ($('#mlogc2waffle').is(':checked')) {
                        $('#usage_piped').attr('disabled',"");
                        $('#usage_piped').prop('checked', false);
                        $('#logfile').removeAttr('disabled',"");  
                        $("#logfile").val("/var/log/mlog2waffle/modsec_audit.log");                             
                        $("#logdir").val("/var/log/mlog2waffle/data");                             
                    } else{
                        $('#usage_piped').removeAttr('disabled','disabled');
                };
                
                if ($('#usage_piped').is(':checked')) {
                        $('#logfile').attr('disabled',"");
                        $("#logfile").val("");  
                    } else{
                        $('#logfile').removeAttr('disabled','disabled');
                };
                if ($('#usage_scheduled').is(':checked')) {
                    if ($('#mlogc').is(':checked')){
                        $("#logfile").val("/var/log/mlogc/modsec_audit.log");  
                    } else if ($('#mlogc2waffle').is(':checked')){
                        $("#logfile").val("/var/log/mlog2waffle/modsec_audit.log");  
                    }
                };
                if ($('#usage_service').is(':checked')) {
                    if ($('#mlogc').is(':checked')){
                        $("#logfile").val("/var/log/mlogc/modsec_audit.log");  
                    } else if ($('#mlogc2waffle').is(':checked')){
                        $("#logfile").val("/var/log/mlog2waffle/modsec_audit.log");  
                    }
                };
            };
         </script>

         <form action="wizardfeeder.php" method="GET">
            <b></b>
            <input type="hidden" name="sensor" value="<?PHP print $sensor ?>">

            <div class="wizardRow">
                <div class="wizardLeft">
                    <b>Choice your event feeder:</b>
                </div>
                <div class="wizardRight">
                    <label class="tagTip" title="mlogc is a log feeder shipped with modsecurity, normally used piped with apache/modsecurity logs, but can be used too in scheduled way." ><input type="radio" id="mlogc" name="feeder" value="mlogc" onchange="toggleStatus()"> mlogc </label> <br />
                    <label class="tagTip" title="mlog2waffle is a new log feeder, distributed with WAF-FLE, writen to be used as a service, or scheduled, but not piped."><input type="radio" name="feeder" value="mlogc2waffle" id="mlogc2waffle"  onchange="toggleStatus()"> mlog2waffle</label>
                </div>
                <div class="filterClear"></div>
            </div>
            <br>
            <div class="wizardRow">
                <div class="wizardLeft">
                    <b>Choice usage:</b>
                </div>
                <div class="wizardRight">
                    <label class="tagTip" title="<b>Piped mode</b>: means that the ModSecurity log file will feed mlogc directly and will not be written to disk. Audit log keep stored on disk, until the program process each entry and send to WAF-FLE. <br>This make logs be sent as soon as it is generated, in real time." ><input type="radio" id="usage_piped" name="usage" value="piped" onchange="toggleStatus()"> Piped with Apache/Modsecurity logs </label> <br />
                    <label class="tagTip" title="<b>Scheduled in crontab or batch mode</b>: means that the ModSecurity log file will be written to disk, and a scheduled task on crontab will read and process the log file. Audit log is stored on disk until the task process each entry, and send they to WAF-FLE. This make logs be sent periodically (depending upon the frequency of crontab entry), but not immediately. <br> Typically, the logs are sent each 5 minutes."><input type="radio" id="usage_scheduled" name="usage" value="scheduled" onchange="toggleStatus()"> Scheduled in crontab </label><br />
                    <label class="tagTip" title="<b>Service daemon or tail mode</b>: means that ModSecurity's log file will be written to disk, and a service running on sensor box will read the log file, as soon as it is generated, processing all entries. Audit log is stored on disk, until each entry has been processed and sent to the WAF-FLE. <br>This make the logs be sent in real time.<br>"><input type="radio" class="mlog2waffe" id="usage_service" name="usage" value="service" onchange="toggleStatus()" > Service deamon   </label>
                </div>
                <div class="filterClear"></div>
            </div>
            <br />
            <div class="wizardRow">
                <div class="wizardLeft">
                    <b>WAF-FLE controller URL:</b>
                </div>
                <div class="wizardRight">
                    <label class="tagTip" title="Confirm/Correct the controle URL, this should reflect WAF-FLE instalation, including protocol and port (if diferent of 80 and 443). HTTPS is prefered by making event traffic secure, consider to use it.<br>An example: https://waf-fle.example.org:8443/controller/" ><input type="text" name="address" id="address" title="Check if sensor machine can reach WAF-FLE using this address" value="<?PHP
                    if ($_SERVER['HTTPS']) {
                        $proto = "https";
                        if ($_SERVER['SERVER_PORT'] != "443") {
                            $urlPort = $_SERVER['SERVER_PORT'];
                        }
                    } else {
                        $proto = "http";
                        if ($_SERVER['SERVER_PORT'] != "80") {
                            $urlPort = $_SERVER['SERVER_PORT'];
                        }
                    }
                    if (isset($urlPort)) {
                        print "$proto://".$_SERVER['SERVER_NAME'].":$urlPort/controller/";
                    } else {
                        print "$proto://".$_SERVER['SERVER_NAME']."/controller/";
                    }
                    ?>" size="20" style="width: 350px" autocomplete="off" > <br /></label>
                </div>
                <div class="filterClear"></div>
            </div>
            <br />

            <div class="wizardRow">
                <div class="wizardLeft">
                    <b>ModSecurity log file, on sensor machine:</b>
                </div>
                <div class="wizardRight">
                    <label class="tagTip" title="This field will fill the SecAuditLog configuration on ModSecurity, that hold the main log file. This is used as a index to each audit log file stored on SecAuditLogStorageDir (see next field), this is need by both mlogc and mlog2waffle." >
                    <input type="text" name="logfile" id="logfile"  value="" size="150" style="width: 350px" autocomplete="off" ></label>
                </div>
                <div class="filterClear"></div>
            </div>
            <br>

            <div class="wizardRow">
                <div class="wizardLeft">
                    <b>Path of ModSecurity events directory, on sensor machine:</b>
                </div>
                <div class="wizardRight">
                    <label class="tagTip" title="Use this field to define where audit logs will be stored, this events are read by mlogc or mlog2waffle to be send to WAF-FLE, after that (depending of mlogc/mlog2waffle configuration) will be deleted." >
                    <input type="text" name="logdir" id="logdir"  value="" size="150" style="width: 350px" autocomplete="off" ></label>
                </div>
                <div class="filterClear"></div>
            </div>
            <br>

            <div class="wizardRow">
                <div class="wizardLeft">
                    <input type="submit" name="wiz" id="wiz" title="Click to create a template for this sensor" value="Next" >
                </div>
                <div class="wizardRight">

                </div>
                <div class="filterClear"></div>
            </div>
            <br>



            </form>
            <br />
         </p>
         <p class="disclaimer">Attention: The template produced by this wizard are just this, templates. Make a carefully revision on all files before overwrite your production files.</p><br /><br />


        <?PHP
        }
        ?>
        </div>
        </div>

<?PHP

} else {
    print "<div class=\"disclaimer\">Wrong request, go back to managment interface.</div>";
}
?>
    </div>
</div>


<?PHP


require_once "../footer.php";
?>
