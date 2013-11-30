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
global $DEMO;
if ($DEMO) {
	$result = array('Total' => 0 , 'Current' => 0, 'Percent' => 100);
	print json_encode($result);
	return;
}

$ASYNC=TRUE;
require_once("../session.php");

header("Content-type: application/json");
header("Cache-Control: no-cache");
if (isset($_GET['deleteByFilter']) AND $_GET['deleteByFilter'] == 1 AND preg_match('/\d{12,16}/', $_GET['delId'])) {
	$delId = $_GET['delId'];

	if (!isset($_SESSION['delId'][$delId]['Total'])) {
		// Create a delFilter based on filter
		$_SESSION['delFilter'] = $_SESSION['filter'];
		$totalEventDelete = eventFilterCount('delFilter');
		$_SESSION['delId'][$delId]['Total'] = $totalEventDelete;
	}
	if ($_SESSION['delId'][$delId]['Total'] > 0) {
		$eventsDeletedCount = deleteEventsByFilter();
		$_SESSION['delId'][$delId]['Deleted'] += $eventsDeletedCount;

		$deletedPercent = ($_SESSION['delId'][$delId]['Deleted']*100)/$_SESSION['delId'][$delId]['Total'];
		$result = array('Total' => $_SESSION['delId'][$delId]['Total'] , 'Current' => $_SESSION['delId'][$delId]['Deleted'], 'Percent' => $deletedPercent);
		if ($eventsDeletedCount == 0) {
			unset($_SESSION['delFilter']);
			unset($_SESSION['delId']);
		}
	} else {
		$result = array('Total' => 0 , 'Current' => 0, 'Percent' => 100);
	}
	sleep(2);
	print json_encode($result);

} elseif (isset($_GET['falsePositiveByFilter']) AND $_GET['falsePositiveByFilter'] == 1 AND preg_match('/\d{12,16}/', $_GET['fpId'])) {
	$fpId = $_GET['fpId'];

	if (!isset($_SESSION['fpId'][$fpId]['Total'])) {
		// Create a fpFilter based on current filter
		$_SESSION['fpFilter'] = $_SESSION['filter'];
		$totalEventToMark = eventFilterCount('fpFilter');
		$_SESSION['fpId'][$fpId]['Total'] = $totalEventToMark;
	}

	if ($_SESSION['fpId'][$fpId]['Total'] > 0) {
		$eventsFPCount = falsePositiveByFilter();
		$_SESSION['fpId'][$fpId]['Marked'] += $eventsFPCount;

		$fpPercent = ($_SESSION['fpId'][$fpId]['Marked']*100)/$_SESSION['fpId'][$fpId]['Total'];
		$result = array('Total' => $_SESSION['fpId'][$fpId]['Total'] , 'Current' => $_SESSION['fpId'][$fpId]['Marked'], 'Percent' => $fpPercent);
		if ($eventsFPCount == 0) {
			unset($_SESSION['fpFilter']);
			unset($_SESSION['fpId']);
		}
	} else {
		$result = array('Total' => 0 , 'Current' => 0, 'Percent' => 100);
	}
	sleep(1);
	print json_encode($result);

}elseif (isset($_GET['deleteSensorByFilter']) AND $_GET['deleteSensorByFilter'] == 1 AND preg_match('/\d{12,16}/', $_GET['delId'])) {
	$delId = $_GET['delId'];

	if (!isset($_SESSION['delId'][$delId]['Total'])) {
		// Create a delFilter
		if (is_int($_SESSION['delFilter']['src_sensor'])) {
			$totalEventDelete = eventFilterCount('delFilter');
			$_SESSION['delId'][$delId]['Total'] = $totalEventDelete;
		} else {
			return FALSE;
		}
	}
	if ($_SESSION['delId'][$delId]['Total'] > 0) {
		$eventsDeletedCount = deleteEventsByFilter();
		$_SESSION['delId'][$delId]['Deleted'] += $eventsDeletedCount;

		$deletedPercent = ($_SESSION['delId'][$delId]['Deleted']*100)/$_SESSION['delId'][$delId]['Total'];
		if (($_SESSION['delId'][$delId]['Deleted'] - $_SESSION['delId'][$delId]['Total']) == 0) {
			$deleteSensorResult = deleteSensor($_SESSION['delFilter']['src_sensor']);
			$result = array('Total' => $_SESSION['delId'][$delId]['Total'] , 'Current' => $_SESSION['delId'][$delId]['Deleted'], 'Percent' => $deletedPercent, 'SensorDelete' => $deleteSensorResult);			
			unset($_SESSION['delFilter']);
			unset($_SESSION['delId']);
		} else {
			$result = array('Total' => $_SESSION['delId'][$delId]['Total'] , 'Current' => $_SESSION['delId'][$delId]['Deleted'], 'Percent' => $deletedPercent, 'SensorDelete' => $deleteSensorResult);			
		}


		
	} else {
		$deleteSensorResult = deleteSensor($_SESSION['delFilter']['src_sensor']);
		unset($_SESSION['delFilter']);
		unset($_SESSION['delId']);
		$result = array('Total' => 0 , 'Current' => 0, 'Percent' => 100, 'SensorDelete' => $deleteSensorResult);
	}
	sleep(2);
	print json_encode($result);
} else {
	print "Error\n";
}
?>
