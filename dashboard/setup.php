<html>
<head><title>WAF-FLE Setup</title></head>
<body>
<h1>WAF-FLE 0.6 Setup</h1>
<p>

<?PHP
$waffleVersion = '0.6.0';

// variable init
$databaseSchema="/usr/local/waf-fle/extra/waffle.mysql";
$configphpError=false;
$extensionError=false;
$databaseError=false;

// Check if config.php exist 
if (file_exists("../config.php")) {
   require_once "../config.php";
   
   if (!isset($SETUP) OR $SETUP == false){
      print "\$SETUP not true or existent on config.php file. To run WAF-FLE Setup, make \$SETUP variable true, and run setup again!<br>";
      exit();
   }   
   // Create database...
   if (isset($_GET['createDB'])) {
      if ($_POST['go'] == 'create') {
         $handle = fopen($databaseSchema, "r");
         $createTable_events = fread($handle, filesize($databaseSchema));

         $user=$_POST['user']; 
         $pass=$_POST['pass']; 
         try {
            $dbh = null;
            $dbh = new PDO("mysql:host=$DB_HOST", $user, $pass);

            // create database
            $dbh->exec("CREATE DATABASE `".$DATABASE."`;");

            // Create waf-fle user
            $dbh->exec("CREATE USER '".$DB_USER."'@localhost IDENTIFIED BY '".$DB_PASS."';
               GRANT SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES ON `".$DATABASE."`.* TO '".$DB_USER."'@localhost;                        
               FLUSH PRIVILEGES;");

         } catch (PDOException $e) {
            die("DB ERROR: ". $e->getMessage());
         }

         // Create tables
         try {
            $dbh = null;
            $dbh = new PDO('mysql:host='.$DB_HOST.';dbname='.$DATABASE, $user, $pass);

            // create database
            $dbh->exec($createTable_events);
               
         } catch (PDOException $e) {
            die("DB ERROR: ". $e->getMessage());
         }
         print "Database created successfully. <br><br><font color=\"red\"><b>Now edit config.php and turn \$SETUP false</b></font>. <br /><br />After that, access waf-fle using <a href=\"login.php\">the login page</a>:<br> <b>&nbsp;&nbsp;username:</b><i> admin</i><br /><b>&nbsp;&nbsp;password:</b> <i>admin</i>. <br /><br />You will be prompted to change the password to continue.<br ><br>Good Waf-fling!";
            
         exit();
      } else {
         print "<h3>Create WAF-FLE database</h3> <br />";
         print "Please, follow the steps below to create the database schema. <br /> <br />Please provide, below, a MySQL account with permission to create a database and user. Normally this is a MySQL admin account, and not the username used by WAF-FLE to connect to database.<br /><br />";  
         print "<form action=\"setup.php?createDB\" method=\"POST\">";
         print "<table>";
         print "<tr>";
         print "<td>Username: </td> <td><input type=\"text\" name=\"user\" size=\"30\"> <br /> </td>";
         print "</tr><tr>";
         print "<td>Password:</td><td><input type=\"password\" name=\"pass\" size=\"30\"> </td>";
         print "</tr>";
         print "<tr>";
         print "<td>Delete an old database and user account if they exists:</td><td><input type=\"checkbox\" name=\"del\"> </td>";
         print "</tr>";
         print "<td></td><td><button name=\"go\" value=\"create\" type=\"submit\">Create Database</button> </td>";
         print "</tr>";         
         print "</table>";
         print "</form>";
      }
      exit;
   } elseif (isset($_GET['upgradeDB'])) {  // Upgrade database
      if (isset($_POST['go']) AND $_POST['go'] == "upgrade") {

         $user=$_POST['user']; 
         $pass=$_POST['pass']; 
         try {
            $dbh = null;
            $dbh = new PDO("mysql:host=$DB_HOST", $user, $pass);
            $DATABASE_OLD = $DATABASE . "_old";
            // create old database.
            $dbh->exec("CREATE DATABASE `".$DATABASE_OLD."`;");
          
            // move old tables to old database.
            $dbh->exec("RENAME TABLE `".$DATABASE."`.events TO `".$DATABASE_OLD."`.events;") ;
            $dbh->exec("RENAME TABLE `".$DATABASE."`.events_messages TO `".$DATABASE_OLD."`.events_messages;");
            $dbh->exec("RENAME TABLE `".$DATABASE."`.events_messages_tag TO `".$DATABASE_OLD."`.events_messages_tag;");
            $dbh->exec("RENAME TABLE `".$DATABASE."`.events_notes TO `".$DATABASE_OLD."`.events_notes;");
            $dbh->exec("RENAME TABLE `".$DATABASE."`.events_stats TO `".$DATABASE_OLD."`.events_stats;");
            $dbh->exec("RENAME TABLE `".$DATABASE."`.sensors TO `".$DATABASE_OLD."`.sensors;") ;
            $dbh->exec("RENAME TABLE `".$DATABASE."`.sensors_type TO `".$DATABASE_OLD."`.sensors_type;");
            $dbh->exec("RENAME TABLE `".$DATABASE."`.severity TO `".$DATABASE_OLD."`.severity;");
            $dbh->exec("RENAME TABLE `".$DATABASE."`.tags TO `".$DATABASE_OLD."`.tags;");
            $dbh->exec("RENAME TABLE `".$DATABASE."`.users TO `".$DATABASE_OLD."`.users;") ;
            $dbh->exec("RENAME TABLE `".$DATABASE."`.user_permissions TO `".$DATABASE_OLD."`.user_permissions;") ;
            $dbh->exec("DROP DATABASE `".$DATABASE."`;") ;
            
            // create new database
            $handle = fopen($databaseSchema, "r");
            $createTable_events = fread($handle, filesize($databaseSchema));

            $user=$_POST['user']; 
            $pass=$_POST['pass']; 
            try {
               $dbh = null;
               $dbh = new PDO("mysql:host=$DB_HOST", $user, $pass);

               // create database
               $dbh->exec("CREATE DATABASE `".$DATABASE."`;");

               // Update waf-fle user
               $dbh->exec("DROP USER '".$DB_USER."'@'localhost';
                  CREATE USER '".$DB_USER."'@localhost IDENTIFIED BY '".$DB_PASS."';
                  GRANT SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES ON `".$DATABASE."`.* TO '".$DB_USER."'@localhost;                        
                  FLUSH PRIVILEGES;");

            } catch (PDOException $e) {
               die("DB ERROR: ". $e->getMessage());
            }

            // Create tables
            try {
               $dbh = null;
               $dbh = new PDO('mysql:host='.$DB_HOST.';dbname='.$DATABASE, $user, $pass);

               // create database
               $dbh->exec($createTable_events);
                  
            } catch (PDOException $e) {
               die("DB ERROR: ". $e->getMessage());
            }
            print "Database created successfully.<br>";
            print "Starting to import old data. Please be patient...";
            
            $dbh = null;
            $dbh = new PDO("mysql:host=$DB_HOST", $user, $pass);
            $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
          //  $dbh->setAttribute( PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true );
            
            // Check how many events are in old database
            $eventsNumberQuery = "SELECT count(*) as n_events FROM $DATABASE_OLD.events";
            try {
               $query_sth = $dbh->prepare($eventsNumberQuery);

               // Execute the query
               $query_sth->execute();
               $eventsNumber = $query_sth->fetch(PDO::FETCH_ASSOC);
               $queryStatus = $query_sth->errorCode();
               print "<br>There are $eventsNumber[n_events] events in old database.";
               if ($eventsNumber['n_events'] > 200000) {
                  print "<br />You have a lot of events, please be patient, this can take a long time...";
               }
               ob_flush();
               flush();
               
            } catch (PDOException $e) {
               print "<br>"; 
               $error = $e->getMessage();
               print "Error: $error";
               exit();
            }

            print "<ul>";
            // wait 5 seconds to mysql commit changes... some race condition happen without this trick
           // sleep(5);
            
            // Import events Full messages to new table
            $importEvents_full_sections  = "INSERT INTO `$DATABASE`.events_full_sections(event_id, a_full, b_full, c_full, e_full, f_full, h_full, i_full, k_full, z_full) SELECT event_id, a_full, b_full, c_full, e_full, f_full, h_full, i_full, k_full, z_full FROM `$DATABASE_OLD`.events";
            try {
               $query_sthFull = $dbh->prepare($importEvents_full_sections);

               // Execute the query
               $query_sthFull->execute();
               $count = $query_sthFull->rowCount();
               $queryStatus = $query_sthFull->errorCode();
               print "<li>$count Events Full Messages imported";
            } catch (PDOException $e) {
               print "<br>"; 
               $error = $e->getMessage();
               print $error;
               exit();
            }
            ob_flush();
            flush();
            // Import events_messages to new table
            $importEventsMessages  = "INSERT INTO `$DATABASE`.events_messages(event_id, msg_id, h_message_pattern, h_message_action, h_message_ruleFile, h_message_ruleLine, h_message_ruleId, h_message_ruleData, h_message_ruleSeverity) SELECT event_id, msg_id, h_message, h_message_status, h_message_ruleFile, h_message_ruleLine, h_message_ruleId, h_message_ruleData, h_message_ruleSeverity FROM `$DATABASE_OLD`.events_messages";
            try {
               $query_sthEventsMessages = $dbh->prepare($importEventsMessages);

               // Execute the query
               $query_sthEventsMessages->execute();
               $count = $query_sthEventsMessages->rowCount();
               $queryStatus = $query_sthEventsMessages->errorCode();
               print "<li>$count Events messages imported";
            } catch (PDOException $e) {
               print "<br>"; 
               $error = $e->getMessage();
               print $error;
               exit();
            }
            ob_flush();
            flush();

            // Import rule_message to new table
            $importRuleMessage  = "INSERT INTO `$DATABASE`.rule_message(message_ruleId, message_ruleMsg) SELECT h_message_ruleId, h_message_ruleMsg FROM `$DATABASE_OLD`.events_messages GROUP BY h_message_ruleId";
            try {
               $query_sthRuleMessage = $dbh->prepare($importRuleMessage);

               // Execute the query
               $query_sthRuleMessage->execute();
               $count = $query_sthRuleMessage->rowCount();
               $queryStatus = $query_sthRuleMessage->errorCode();
               print "<li>$count Rule messages imported";
            } catch (PDOException $e) {
               print "<br>"; 
               $error = $e->getMessage();
               print $error;
               exit();
            }
            ob_flush();
            flush();

            // Import events_messages_tag to new table
            $importEventsMessagesTag  = "INSERT INTO `$DATABASE`.events_messages_tag(msg_id, h_message_tag) SELECT msg_id, h_message_tag FROM `$DATABASE_OLD`.events_messages_tag";
            try {
               $query_sthRuleMessageTag = $dbh->prepare($importEventsMessagesTag);

               // Execute the query
               $query_sthRuleMessageTag->execute();
               $count = $query_sthRuleMessageTag->rowCount();
               $queryStatus = $query_sthRuleMessageTag->errorCode();
               print "<li>$count Events messages tag imported";
            } catch (PDOException $e) {
               print "<br>"; 
               $error = $e->getMessage();
               print $error;
               exit();
            }
            ob_flush();
            flush();

            // Import events_hostname to new table
            $importEventsHost  = "INSERT INTO `$DATABASE`.events_hostname(hostname) SELECT distinct b_host FROM `$DATABASE_OLD`.events";
            try {
               $query_sthEventsHost = $dbh->prepare($importEventsHost);

               // Execute the query
               $query_sthEventsHost->execute();
               $count = $query_sthEventsHost->rowCount();
               $queryStatus = $query_sthEventsHost->errorCode();
               print "<li>$count Events hostname imported";
            } catch (PDOException $e) {
               print "<br>"; 
               $error = $e->getMessage();
               print $error;
               exit();
            }
            ob_flush();
            flush();

            // Import sensors to new table
            $importSensors  = "INSERT INTO `$DATABASE`.sensors(sensor_id, name, password, IP, description, type) SELECT sensor_id, name, password, IP, description, '1' FROM `$DATABASE_OLD`.sensors";
            try {
               $query_sthSensor = $dbh->prepare($importSensors);

               // Execute the query
               $query_sthSensor->execute();
               $count = $query_sthSensor->rowCount();
               $queryStatus = $query_sthSensor->errorCode();
               print "<li>$count Sensors imported";
            } catch (PDOException $e) {
               print "<br>"; 
               $error = $e->getMessage();
               print $error;
               exit();
            }
            ob_flush();
            flush();

            // Import users to new table
            $delUsers  = "DELETE FROM `$DATABASE`.users WHERE users.user_id = 1";
            try {
               $query_sthDelUsers = $dbh->prepare($delUsers);

               // Execute the query
               $query_sthDelUsers->execute();
               $count = $query_sthDelUsers->rowCount();
               $queryStatus = $query_sthDelUsers->errorCode();
            } catch (PDOException $e) {
               print "<br>"; 
               $error = $e->getMessage();
               print $error;
               exit();
            }
            ob_flush();
            flush();

            $importUsers  = "INSERT INTO `$DATABASE`.users(user_id, username, password, email) SELECT user_id, username, password, email FROM `$DATABASE_OLD`.users";
            try {
               $query_sthUsers = $dbh->prepare($importUsers);

               // Execute the query
               $query_sthUsers->execute();
               $count = $query_sthUsers->rowCount();
               $queryStatus = $query_sthUsers->errorCode();
               print "<li>$count Users imported";
            } catch (PDOException $e) {
               print "<br>"; 
               $error = $e->getMessage();
               print $error;
               exit();
            }
            ob_flush();
            flush();

            // Import events to new table
            $importEvents  = "INSERT INTO `$DATABASE`.events (`event_id`, `sensor_id`, `received_at`, `a_timestamp`, `a_timezone`,`a_date`,`a_uniqid`, `a_client_ip`, `a_client_port`, `a_server_ip`, `a_server_port`, `b_method`, `b_path`,`b_path_parameter`,`b_protocol`, `b_host`, `b_user_agent`, `b_referer`, `f_protocol`, `f_status`, `f_msg`,`f_content_length`, `f_connection`, `f_content_type`, `h_apache_error_file`, `h_apache_error_line`,`h_apache_error_level`, `h_apache_error_message`, `h_stopwatch_timestamp`, `h_stopwatch_duration`, `h_stopwatch_time_checkpoint_1`, `h_stopwatch_time_checkpoint_2`, `h_stopwatch_time_checkpoint_3`, `h_producer`,`h_producer_ruleset`, `h_server`, `h_wa_info_app_id`, `h_wa_info_sess_id`, `h_wa_info_user_id`, `h_apache_handler`, `h_response_body_transf`, `h_severity`, `h_action_status`, `h_action_status_msg`, `h_score_total`, `h_score_SQLi`, `h_score_XSS`) SELECT `event_id`, `sensor_id`, `received_at`, `a_timestamp`, `a_timezone`,DATE(`a_timestamp`) as a_date,`a_uniqid`, `a_client_ip`, `a_client_port`, `a_server_ip`, `a_server_port`, `b_method`, SUBSTRING_INDEX(b_URI, '?', 1), SUBSTRING(b_URI, LOCATE('?', b_URI), LENGTH(b_URI)), `b_protocol`, t2.host_id, `b_user_agent`, `b_referer`, `f_protocol`, `f_status`, `f_msg`,`f_content_length`, `f_connection`, `f_content_type`, `h_apache_error_file`, `h_apache_error_line`,`h_apache_error_level`, `h_apache_error_message`, `h_stopwatch_timestamp`, `h_stopwatch_duration`, `h_stopwatch_time_checkpoint_1`, `h_stopwatch_time_checkpoint_2`, `h_stopwatch_time_checkpoint_3`, `h_producer`,`h_producer_ruleset`, `h_server`, `h_wa_info_app_id`, `h_wa_info_sess_id`, `h_wa_info_user_id`, `h_apache_handler`, `h_response_body_transf`, `h_severity`, `h_action`, `h_action_status_msg`, `h_score_total`, `h_score_SQLi`, `h_score_XSS`  FROM `$DATABASE_OLD`.events t1 JOIN `$DATABASE`.events_hostname t2 ON t1.b_host = t2.hostname ORDER BY t1.event_id";
            try {
               $query_sthEvents = $dbh->prepare($importEvents);

               // Execute the query
               $query_sthEvents->execute();
               $count = $query_sthEvents->rowCount();
               $queryStatus = $query_sthEvents->errorCode();
               print "<li>$count Events imported";
            } catch (PDOException $e) {
               $error = $e->getMessage();
               print $error;
               exit();
            }            
            ob_flush();
            flush();
            
            print "</lu>";
            
            print "<br><br>The database seeing to be correctly imported, an old database was keep named <i>$DATABASE_OLD</i>, after check if WAF-FLE has imported the things correctly you can drop the old database. <br />
            Now edit config.php and turn \$SETUP false. After that, access waf-fle using <a href=\"login.php\">the login page</a>, using the same username and password used before. <br /><br ><br>Good Waf-fling!"; 

            exit();
         } catch (PDOException $e) {
             die("DB ERROR: ". $e->getMessage());
         }
         /*
         // Create tables
         $handle = fopen($databaseSchema, "r");
         $createTable_events = fread($handle, filesize($databaseSchema));
         try {
            $dbh = new PDO('mysql:host='.$DB_HOST.';dbname='.$DATABASE, $user, $pass);

            // create database
            $dbh->exec($createTable_events) 
               or die(print_r($dbh->errorInfo(), true));

         } catch (PDOException $e) {
                die("DB ERROR: ". $e->getMessage());
         } 
      */         
         exit();
      } else {
         print "<h3>Upgrade WAF-FLE database</h3> <br />";
         print "Please, follow the steps below to upgrade the database schema. <br /> <br />Please provide, below, a MySQL account with permission to upgrade the database schema. Normally this is a MySQL admin account, and not the account used by WAF-FLE to connect to database.<br /><br />";  
         print "<form action=\"setup.php?upgradeDB\" method=\"POST\">";
         print "<table>";
         print "<tr>";
         print "<td>Username: </td> <td><input type=\"text\" name=\"user\" size=\"30\"> <br /> </td>";
         print "</tr><tr>";
         print "<td>Password:</td><td><input type=\"password\" name=\"pass\" size=\"30\"> </td>";
         print "</tr>";
         print "<tr>";
         print "<td></td><td><button name=\"go\" value=\"upgrade\" type=\"submit\">Upgrade Database</button> </td>";
         print "</tr>";         
         print "</table>";
         print "</form>";
            
         print "<p>";
         print "<b>Note: After the upgrade process, your current database will be saved as ".$DATABASE."_old</b>";
         exit;
      }
   }   

   print "<b>Checking config.php settings...</b><br>";

    print "&nbsp;&nbsp; config.php present...<br />";
    require_once "../config.php";

    if (!isset($DB_HOST)){
        print "&nbsp;&nbsp; config.php \$DB_HOST not defined, go back and define it!<br>";
        $configphpError = true;
    }
    if (!isset($DB_USER)){
        print "&nbsp;&nbsp; config.php \$DB_USER not defined, go back and define it!<br>";
        $configphpError = true;
    }
    if (!isset($DB_PASS)){
        print "&nbsp;&nbsp; config.php \$DB_PASS not defined, go back and define it!<br>";
        $configphpError = true;
    }
    if (!isset($DATABASE)){
        print "&nbsp;&nbsp; config.php \$DATABASE not defined, go back and define it!<br>";
        $configphpError = true;
    }
    if (!isset($COMPRESSION)){
        print "&nbsp;&nbsp; config.php \$COMPRESSION not defined, go back and define it!<br>";
        $configphpError = true;
    }
    if (!isset($APC_ON)){
        print "&nbsp;&nbsp; config.php \$APC_ON not defined, go back and define it!<br>";
        $configphpError = true;
    }
    // in case of some error, exit
    if ($configphpError) {
        print "<b>Configuration file \"config.php\" has errors, check above what's wrong. After solved, run setup again!</b><br>";
        break;
    } else {
        print "&nbsp;&nbsp; Config looks correct.<br>";
    }
    
    print "<br><br>";
    print "<b>Checking PHP Version...</b><br>";
    
    if (is_int(PHP_MAJOR_VERSION) AND is_int(PHP_MINOR_VERSION)) {
        if ((PHP_MAJOR_VERSION >= 5) AND (PHP_MINOR_VERSION >= 3)) {
            print "&nbsp;&nbsp; PHP version: ".PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION.", version satisfied.<br>";
        } else {
            print "&nbsp;&nbsp; PHP version must be 5.3 or higher.<br>";
        }
    } else {
        print "&nbsp;&nbsp; PHP version must be 5.3 or higher.<br>";
    }
    
    print "<br><br>";
    print "<b>Checking php extensions...</b><br>";
    
    // Check if php is compiled with all necessary modules
    if (extension_loaded('apc')) {
        print "&nbsp;&nbsp; APC Extension: present,";
        if (ini_get('apc.enabled')) {
            print " enabled; <br />";
        } else {
            if ($APC_ON) {
                print "Not enabled and required by config.php; <br />";
                $extensionError = true;
            } else {
                print "Not enabled and not required by config.php; <br />";
            }            
        }
    } else {
        if ($APC_ON) {
            print "&nbsp;&nbsp; APC Extension: missing. Install to run WAF-FLE with APC or disable in config.php<br>";
            $extensionError = true;
        } else {
            print "&nbsp;&nbsp; APC Extension: missing. Not required by config.php<br>";
        }        
    }

    if (extension_loaded('geoip')) {
        print "&nbsp;&nbsp; GeoIP Extension: present;<br/>";
    } else {
        print "&nbsp;&nbsp; GeoIP Extension: missing.<br/>";
        $extensionError = true;
    }
    if (extension_loaded('PDO')) {
        print "&nbsp;&nbsp; PDO Extension: present;<br/>";
    } else {
        print "&nbsp;&nbsp; PDO Extension: missing.<br/>";
        $extensionError = true;
    }
    if (extension_loaded('pdo_mysql')) {
        print "&nbsp;&nbsp; MySQL PDO Driver: present;<br/>";
    } else {
        print "&nbsp;&nbsp; MySQL PDO Driver: missing.<br/>";
        $extensionError = true;
    }
    if (extension_loaded('json')) {
        print "&nbsp;&nbsp; json Extension: present;<br/>";
    } else {
        print "&nbsp;&nbsp; json Extension: missing.<br/>";
        $extensionError = true;
    }
    if (extension_loaded('pcre')) {
        print "&nbsp;&nbsp; pcre Extension: present;<br/>";
    } else {
        print "&nbsp;&nbsp; pcre Extension: missing.<br/>";
        $extensionError = true;
    }
    if (extension_loaded('zlib')) {
        print "&nbsp;&nbsp; zlib Extension: present;<br/>";
    } else {
        print "&nbsp;&nbsp; zlib Extension: missing.<br/>";
        $extensionError = true;
    }
    if (extension_loaded('date')) {
        print "&nbsp;&nbsp; date Extension: present;<br/>";
    } else {
        print "&nbsp;&nbsp; date Extension: missing.<br/>";
        $extensionError = true;
    }
    if (extension_loaded('session')) {
        print "&nbsp;&nbsp; session Extension: present;<br/>";
    } else {
        print "&nbsp;&nbsp; session Extension: missing.<br/>";
        $extensionError = true;
    }
    if (apache_getenv("REMOTE_ADDR")) {
        print "&nbsp;&nbsp; Running on Apache: Ok;<br/>";
    } else {
        print "&nbsp;&nbsp; Not running on Apache: missing.<br/>";
        $extensionError = true;
    }
    if ($extensionError) {
        print "<b>Erro in PHP Extensions, check above what's wrong. After dependency solved, run setup again!</b><br>";
        break;
    }
    print "<br />";


    // Checking MYSQL connectivity (if configured)
        
    // Database connection using PDO
    try {
         $dbh = null;
        $dbconn = new PDO('mysql:host='.$DB_HOST.';'.$DATABASE, $DB_USER, $DB_PASS);
        $dbconn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $dbconn->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
   } catch (PDOException $e) {
      $databaseError = $e->getCode();
      $databaseErrorMsg = $e->getMessage();
      if ($databaseError == 0) {     // workaround with old version of php-pdo
         if(stristr($databaseErrorMsg, '[1049]')) {
            $databaseError = '1049';
         } elseif(stristr($databaseErrorMsg, '[2003]')) {
            $databaseError = '2003';
         } elseif(stristr($databaseErrorMsg, '[1045]')) {
            $databaseError = '1045';
         } else {
            $databaseError = '1';
         }         
      }

      if ($databaseError == '1049') {
         $createDatabase = true;
         $createUser = false;
      } elseif ($databaseError == '2003') {
         print "<b>Connection Timeout, check database hostname in config.php and try again...</b>";
         die();
      } elseif ($databaseError == '1045') {
         $createDatabase = true;
         $createUser = true;
      } else {
         $createDatabase = true;
         $createUser = true;
      } 
   }
   if ($databaseError == false) {
  
        // Check WAF-FLE database are created
        $sqlDatabase = 'show databases like \''.$DATABASE.'\'';
        try {
            $checkDatabase_sth = $dbconn->prepare($sqlDatabase);

            // Execute the query
            $checkDatabase_sth->execute();
            $databaseCount = count($checkDatabase_sth->fetchAll());
            if ($databaseCount != 1) {
                $createDatabase = true;
                $createUser = false;
            } else {
                $createDatabase = false;
                $createUser = true;            
                print "Database exist, checking version...<br>"; 
               
                  try {
                     $dbh = null;
                     $dbconn = new PDO('mysql:host='.$DB_HOST.';dbname='.$DATABASE, $DB_USER, $DB_PASS);
                     $dbconn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
                     $dbconn->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
                  } catch (PDOException $e) {
                     $databaseError = $e->getCode();
                     $databaseErrorMsg = $e->getMessage();
                  }
                 
                // Check WAF-FLE vs. Database version
                $sqlDatabaseVersion = 'SELECT `waffle_version` FROM `version` LIMIT 1';
                try {
                    $checkVersion_sth = $dbconn->prepare($sqlDatabaseVersion);

                    // Execute the query
                    $checkVersion_sth->execute();
                    $dbSchema = $checkVersion_sth->fetch(PDO::FETCH_ASSOC);
                    if ($dbSchema['waffle_version'] != $waffleVersion) {
                        $upgradeDatabase = true;
                    } else {
                        $upgradeDatabase = false;                    
                    }

                    $queryStatus = $checkVersion_sth->errorCode();
                } catch (PDOException $e) {
                   $upgradeDatabase = true;
                   $databaseError = $e->getCode();
                   $databaseErrorMsg = $e->getMessage();
                }
            }
        } catch (PDOException $checkDatabase_sth) {
            die("Error in database query!");
        }
    }
    
    if ($createDatabase == true) {
        if ($createUser == true) {
            print "<b>Database not exist! (or credentials on config.php are yet valid) </b><br />
            <a href=\"setup.php?createDB&createDBUser\">Go! Create the database and user...</a>";            
        } else {
            print "<b>Database not exist! </b><br />
            <a href=\"setup.php?createDB\">Go! Create the database...</a>";            
        }
        
    } elseif ($upgradeDatabase == true) {
        print "<b>A old database scheme found. To start the upgrade process click the link bellow...</b><br>";
        print "<a href=\"setup.php?upgradeDB\">Go! Upgrade database schema...</a>";         
    } else {
        print "<b>Database schema already in last version ($waffleVersion), nothing to do. The WAF-FLE seen already configured. <br />Make \$SETUP=false in config.php to start. Good Waf-fling.</b><br><br><br> Exiting...<br>";
        exit();        
    }
} else {
    print "config.php missing. Check it, and run setup again...<br />";
    print "Exiting.";
    exit();
}

?>
...
</p>

</body>
</html>
