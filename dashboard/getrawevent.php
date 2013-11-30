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
require_once("../session.php");

if (isset($_GET["e"])) {
   $geteventid = @sanitize_int($_GET["e"], $min='1' );
} else {
   $geteventid = 1;
}
$event = getrawevent($geteventid);

if ($event) {
   header("Cache-Control:");
   header("Cache-Control: public");
   header("Content-Type: application/force-download");
   header("Content-Disposition: attachment; filename=\"event_$geteventid.txt\"");

   foreach ($event as $event_line) {
      print $event_line;
   }
   print "\n";
}
?>
