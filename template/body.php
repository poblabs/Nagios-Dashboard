<?php 
   /*
   Nagios Operations Dashboard
   Pat O'Brien https://obrienlabs.net
   Description: Displays a quick glance overview of the environment. Requires MK Livestatus.
   Version: config.php -> DASHBOARD_VERSION
   */

require_once( "../functions.php" );

// Need to get the hostgroup from the AJAX request
$hostgroup = $_POST['hostgroup'];

// Get the content from functions.php passing the hostgroup from the POST
show_body_content( $hostgroup );

?>
