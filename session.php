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
/** Maintain user session */
session_start();

// This session expiration/regeneration code was adapted from http://stackoverflow.com/questions/520237/how-do-i-expire-a-php-session-after-30-minutes
if (isset($_SESSION['login']) && isset($_SESSION['LAST_ACTIVITY']) && isset($_SESSION['CREATED'])) {
	// check if session still valid
    if ((time() - $_SESSION['LAST_ACTIVITY']) > $SESSION_TIMEOUT) {
		logoff();
	} elseif (time() - $_SESSION['CREATED'] > $SESSION_TIMEOUT * 10) { 
		// session started more than $SESSION_TIMEOUT * 10 ago, regenerate sessionid
		session_regenerate_id(true);    // change session ID for the current session an invalidate old session ID
	}
} else {
   logoff();
}
?>
