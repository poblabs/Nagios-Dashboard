<?php
   /*
   Nagios Operations Dashboard
   Pat O'Brien https://obrienlabs.net
   Description: Displays a quick glance overview of the environment. Requires MK Livestatus.
   Version: config.php -> DASHBOARD_VERSION
   */

DEFINE( "DASHBOARD_VERSION", "1.3" );
   
error_reporting(0); // 0 to show no errors in the content, or 1 to show errors within content for debugging.

$liveStatusSocket = "/usr/lib/nagios/mk-livestatus/live"; // The location of the MK LiveStatus UNIX socket file

$title = "Nagios Status Dashboard";

$templateDir = "template/";

$defaultTimeZone = "America/New_York";

$refreshHeader = "30"; // Seconds for the header refresh
$refreshBody = "5"; // Seconds for the body refresh

$nagios_url = "http://nagios/nagios3/";
$extinfo_url = "http://nagios/nagios3/cgi-bin/extinfo.cgi";

// When you're done editing the settings above, change this to true.
DEFINE( "SETTINGS_EDITED", true );

?>
