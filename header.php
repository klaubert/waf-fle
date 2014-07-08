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
if ($_SESSION['forceChangePass'] AND $pagId != 'password') {
	header('HTTP/1.1 302 Found');
	header('Location: password.php?User='.$_SESSION['userID']);
	header('Connection: close');
	header('Content-Type: text/html; charset=UTF-8');
}

list ($stDate, $stTime, $fnDate, $fnTime) = getEventDateRange();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
   <title>WAF-FLE</title>
   <link rel="stylesheet" type="text/css" href="css/main.css" />
   <link type="text/css" href="css/waffle-theme/jquery-ui-1.8.16.custom.css" rel="stylesheet" />
   <link type="text/css" href="css/menu.css" rel="stylesheet" />
   <link type="text/css" rel="stylesheet"  href="css/flexigrid.pack.css" />
   <link type="text/css" rel="stylesheet"  href="css/tipTip.css" />
<?PHP
if (isset($jsChart) AND $jsChart) {
    print "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/jquery.jqplot.custom.css\" />";
}
?>
   <script language="javascript" type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
   <script language="javascript" type="text/javascript" src="js/jquery-ui-1.8.16.custom.min.js"></script>
   <script language="javascript" type="text/javascript" src="js/effect.js"></script>
   <script language="javascript" type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
   <script language="javascript" type="text/javascript" src="js/flexigrid.pack.js"></script>
   <script language="javascript" type="text/javascript" src="js/jquery.tipTip.minified.js"></script>
<?PHP
if ($jsChart) {
    ?>
      <!--[if lt IE 9]>
         <script language="javascript" type="text/javascript" src="js/excanvas.js"></script>
      <![endif]-->
      <script language="javascript" type="text/javascript" src="js/jquery.jqplot.min.js"></script>
      <script language="javascript" type="text/javascript" src="js/jqplot.logAxisRenderer.min.js"></script>
      <script language="javascript" type="text/javascript" src="js/jqplot.barRenderer.min.js"></script>
      <script language="javascript" type="text/javascript" src="js/jqplot.canvasAxisLabelRenderer.min.js"></script>
      <script language="javascript" type="text/javascript" src="js/jqplot.canvasAxisTickRenderer.min.js"></script>
      <script language="javascript" type="text/javascript" src="js/jqplot.pieRenderer.min.js"></script>
      <script language="javascript" type="text/javascript" src="js/jqplot.pointLabels.min.js"></script>
      <script language="javascript" type="text/javascript" src="js/jqplot.categoryAxisRenderer.min.js"></script>
      <script language="javascript" type="text/javascript" src="js/jqplot.dateAxisRenderer.min.js"></script>
      <script language="javascript" type="text/javascript" src="js/jqplot.cursor.min.js"></script>
      <script language="javascript" type="text/javascript" src="js/jqplot.canvasTextRenderer.min.js"></script>


    <?PHP
}
?>
   <script type="text/javascript">

		$(function(){

			$('#dialogDeleteSensor').dialog({
				autoOpen: true,
				modal: true,
				minHeight: 300,
				width: 550,
				position: ['center',60],
				buttons: {
					"Yes, Delete this sensor": function() {
						$(":button:contains('Yes, Delete this sensor')").attr("disabled", true).addClass("ui-state-disabled");
						deleteSensor();
					},
					"Cancel": function() {
						//$(this).dialog('close');
						window.location.href = 'management.php';
					},
				},
				dialogClass:'dialog_style1',
				close: function() {
					  window.location.href = 'management.php';
				}
			});
			$('.ui-dialog-buttonpane button:contains(Cancel)').attr("id", "clearLog-message _delete-button");

			// Accordion
			$("#accordion").accordion({ header: "h3" });
			// Tabs
			$('#tabs').tabs({
				fx: { opacity: 'toggle' }
			});

			// Dialog Link
			$('#dialog_link').click(function(){
				$('#dialog').dialog('open');
				return false;
			});

			// Filter Dialog
			$('#dialog').dialog({
				autoOpen: false,
				modal: true,
				minHeight: 450,
				width: 960,
				position: ['center',60],
				buttons: {
					"Apply Filter": function() {
						$("#advFilterForm").submit();
					},
					"Cancel": function() {
						$(this).dialog('close');
					},
					"Clear Filter": function() {
						location.href = './events.php?filter=x';
					},
				},
				dialogClass:'dialog_style1',
				close: function() {
					$(this).find('form')[0].reset();
				}
			});

			// Dialog DeleteByFilter Link
			$('#dialog_deleteByFilter').click(function(){
				$('#dialogDeleteByFilter').dialog('open');
				return false;
			});

			// Dialog DeleteByFilter
			$('#dialogDeleteByFilter').dialog({
				autoOpen: false,
				modal: true,
				minHeight: 350,
				width: 650,
				position: ['center',60],
				buttons: {
					"Yes, Delete these events": function() {
						$(":button:contains('Yes, Delete these events')").attr("disabled", true).addClass("ui-state-disabled");
						deleteEvents();
					},
					"Cancel": function() {
						//$(this).dialog('close');
						window.location.href = 'events.php';
					},
				},
				dialogClass:'dialog_style1',
				close: function() {
					  window.location.href = 'events.php';
				}
			});

			// Dialog DeleteByFilter Link
			function OpenDeleteDialog(){
				$('#dialogDeleteByFilter').dialog('open');
				return false;
			}
            
			// Dialog FalsePositiveByFilter Link
			$('#dialog_falsePositiveByFilter').click(function(){
				$('#dialogFalsePositiveByFilter').dialog('open');
				return false;
			});

			// Dialog dialogFalsePositiveByFilter
			$('#dialogFalsePositiveByFilter').dialog({
				autoOpen: false,
				modal: true,
				minHeight: 350,
				width: 650,
				position: ['center',60],
				buttons: {
					"Yes, Mark these events": function() {
						$(":button:contains('Yes, Mark these events')").attr("disabled", true).addClass("ui-state-disabled");
						falsePositiveEvents();
					},
					"Cancel": function() {
						//$(this).dialog('close');
						window.location.href = 'events.php';
					},
				},
				dialogClass:'dialog_style1',
				close: function() {
					  window.location.href = 'events.php';
				}
			});

			// Dialog FalsePositiveByFilter Link
			function OpenFalsePositiveDialog(){
				$('#dialogFalsePositiveByFilter').dialog('open');
				return false;
			}

		function falsePositiveEvents() {
			$("#FPprogressbar").progressbar({
				value: 0
			});
			timestamp = Number(new Date());
			$('#FPshowdata').html("<p>Starting to mark events, please wait...</p>");
			$('#FPshowSmsg').html("<br /><br />Don't close this dialog or refresh this page, if you do so the mark process will stop.<br /><br />");

			falsePositiveProgress();
		}

		function falsePositiveProgress() {
			$.getJSON("ajax.php?falsePositiveByFilter=1&fpId="+timestamp, function(data) {
				barValue   = parseInt(data.Current);
				barTotal   = parseInt(data.Total);
				barPercent = parseInt(data.Percent);

				$("#FPprogressbar").progressbar({
					value: barPercent
				});
				//}).children('.ui-progressbar-value').html(barPercent.toPrecision(3) + '%');

				$('#FPshowdata').html("<p>"+barValue+" of "+barTotal+" ("+barPercent+"%) events marked!</p>");
				if (barPercent < 100) {
					setTimeout("falsePositiveProgress()", 3000);
				} else {
					$('#FPshowmsg').html("<p><br /><center>All events was marked!<br />Please close this dialog and reload the page.</center></p>");
					$("#dialogFalsePositiveByFilter:button:contains('Cancel')").text("Close");
				}
			});
		}

			var dates = $( "#DateFrom, #DateTo" ).datepicker({

				<?PHP
					$pastdays = floor((time() - strtotime($stDate))/86400);
					print "minDate: -$pastdays,\n";
				?>

				maxDate: 0,
				changeMonth: true,
				hideIfNoPrevNext: true,
				autoSize: true,
				dateFormat: 'yy-mm-dd',
				onSelect: function( selectedDate ) {
					var option = this.id == "DateFrom" ? "minDate" : "maxDate",
					instance = $( this ).data( "datepicker" ),
					date = $.datepicker.parseDate(
						instance.settings.dateFormat ||
						$.datepicker._defaults.dateFormat,
						selectedDate, instance.settings
					);
					dates.not( this ).datepicker( "option", option, date );
				}
			});

			$("#timeFrom, #timeTo").timepicker({
				hourGrid: 4,
				minuteGrid: 10,
				timeFormat: 'hh:mm:ss',
				showSecond: false,
			});

			// Slider
			$('#slider').slider({
				range: true,
				values: [17, 67]
			});
			
			$( "#webhost" ).autocomplete({
				source: function(request, response) {
					$.getJSON("ajax.php", { getWebHostsPartial: request.term }, response); 
				},
				delay: 400,
				select: function(event, ui) {
					$('#hiddenWebHostName').val(ui.item.id);
				},
				minLength: 3,
			});
		});

		var progress_key = '';
		var timestamp;
		// this sets up the progress bar

		function deleteEvents() {
			$("#progressbar").progressbar({
				value: 0
			});
			timestamp = Number(new Date());
			$('#showdata').html("<p>Starting to delete events, please wait...</p>");
			$('#showdelmsg').html("<br /><br />Don't close this dialog or refresh this page, if you do so the deletion process will stop.<br /><br />");

			deleteProgress();
		}

		function deleteProgress() {
			$.getJSON("ajax.php?deleteByFilter=1&delId="+timestamp, function(data) {
				barValue   = parseInt(data.Current);
				barTotal   = parseInt(data.Total);
				barPercent = parseInt(data.Percent);

				$("#progressbar").progressbar({
					value: barPercent
				});
				//}).children('.ui-progressbar-value').html(barPercent.toPrecision(3) + '%');

				$('#showdata').html("<p>"+barValue+" of "+barTotal+" ("+barPercent+"%) events deleted!</p>");
				if (barPercent < 100) {
					setTimeout("deleteProgress()", 3000);
				} else {
					$('#showdelmsg').html("<p><br /><center>All events was deleted!<br />Please close this dialog and reload the page.</center></p>");
					$("#dialogDeleteSensor:button:contains('Cancel')").text("Close");
				}
			});
		}
        
		function deleteSensor() {
			$("#progressbarDeleteSensor").progressbar({
				value: 0
			});
			timestamp = Number(new Date());
			$('#showdataDeleteSensor').html("<p>Starting to delete events of sensor, please wait...</p>");
			$('#showdelmsgDeleteSensor').html("<br /><br />Don't close this dialog or refresh this page, if you do so the deletion process will stop.<br /><br />");

			deleteSensorProgress();
		}

		function deleteSensorProgress() {
			$.getJSON("ajax.php?deleteSensorByFilter=1&delId="+timestamp, function(data) {
				barValue   = parseInt(data.Current);
				barTotal   = parseInt(data.Total);
				barPercent = parseInt(data.Percent);
				SensorDeleteStatus = data.SensorDelete;

				$("#progressbarDeleteSensor").progressbar({
					value: barPercent
				});
				//}).children('.ui-progressbar-value').html(barPercent.toPrecision(3) + '%');

				$('#showdataDeleteSensor').html("<p>"+barValue+" of "+barTotal+" ("+barPercent+"%) events deleted!</p>");
				if (barPercent < 100) {
					setTimeout("deleteSensorProgress()", 3000);
				} else {
					if (SensorDeleteStatus == true) {
						$('#showdelmsgDeleteSensor').html("<p><br /><center>All events was deleted! Sensor deleted sucessfully!<br />Please close this dialog and reload the page.</center></p>");
					} else {
						$('#showdelmsgDeleteSensor').html("<p><br /><center>All events was deleted!<br />An error happen in sensor delete! Please close this dialog, reload the page and try again.</center></p>");
					}
				}
			});
		}
        
        $(function(){
            $(".tagTip").tipTip({maxWidth: "400px",});
        });
	</script>
</head>

<body>
   <div id="header">
      <div id="logo"> <a style="padding: 0;" href="./index.php"><img src="images/logot.png" width="126" height="60" border="0" alt="WAF-FLE"></a></div>
      <div id="user"><p>Logged User: <b><?PHP print $_SESSION['userName']; ?></b> | <a href="./logout.php">Logout</a></p></div>
      <div id="clear"> </div>
      <div id="menu"><p><b> <a href="./index.php">HOME</a>   |    <a href="./events.php">EVENTS</a>   |    <a href="#" id="dialog_link">FILTER</a>    |    <a href="./management.php">MANAGEMENT</a> </b></p></div>
   </div>
