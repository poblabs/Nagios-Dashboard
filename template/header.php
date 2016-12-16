<?php 
   /*
   Nagios Operations Dashboard
   Pat O'Brien https://obrienlabs.net
   Description: Displays a quick glance overview of the environment. Requires MK Livestatus.
   Version: config.php -> DASHBOARD_VERSION
   */

require_once( "../functions.php" );

?>

        <div class="headerTitle">
            <h1><?php echo $title; ?></h1>
			<p id="header_nagios_totals"><?php echo "Monitoring <b>" . $hosts_total . " Hosts</b> and <b>" . $services_total . " Services</b>."; ?></p>
        </div>
		
		<div class="headerStats">
		</div>
		
		<div class="clear"></div>