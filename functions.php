<?php
   /*
   Nagios Operations Dashboard
   Pat O'Brien https://obrienlabs.net
   Description: Displays a quick glance overview of the environment. Requires MK Livestatus.
   Version: config.php -> DASHBOARD_VERSION
   */

require_once dirname(__FILE__) . '/config.php';

// Use MK LiveStatus to get instant results
function get_liveStatus( $type, $ackd, $notif ) {
	global $liveStatusSocket, $hostgroup;
	
	$data = array();
	
	// Create the socket
	$socket = fsockopen( "unix://$liveStatusSocket", NULL, $errno, $errstr, 30 );
	
	// Verify we can connect
	if ( !$socket ) {
		die( "Critical Error: Cannot communicate the the UNIX Socket for MK LiveStatus. $errstr ( $errno )" );
	} 
	
	if ( $type == "host_total" ) {
		$command = "GET hosts\n";
		$command .= "Columns: name\n";
		if ( $hostgroup ) { $command .= "Filter: groups >= $hostgroup\n"; } // Groups return a list. >= is livestatus' way of meaning "contains"
		$command .= "\n"; // The final newline is required
	
	} elseif ( $type == "service_total" ) {
		$command = "GET services\n";
		$command .= "Columns: host_name\n";
		if ( $hostgroup ) { $command .= "Filter: host_groups >= $hostgroup\n"; } // Groups return a list. >= is livestatus' way of meaning "contains"
		$command .= "\n"; // The final newline is required
	
	} elseif ( $type == "hostgroups" ) {
		$command = "GET hostgroups\n";
		$command .= "Columns: name\n";
		$command .= "\n"; // The final newline is required
	
	} else {
		$command = "GET $type\n"; // Valid $type options: hosts or services
		
		if ( $type == "hosts" ) {
			$command .= "Columns: name state plugin_output last_state_change acknowledged comments_with_info\n";
		} elseif ( $type == "services" ) {
			$command .= "Columns: host_name state plugin_output last_state_change acknowledged comments_with_info description\n";			
		}
		
		// Hostgroup filter. Used to show only items relevant to the team
		if ( $hostgroup ) {
			if ( $type == "hosts" ) {
				$command .= "Filter: groups >= $hostgroup\n"; # Groups return a list. >= is livestatus' way of meaning "contains"
			} elseif ( $type == "services" ) {
				$command .= "Filter: host_groups >= $hostgroup\n"; # Groups return a list. >= is livestatus' way of meaning "contains"
			}
		}

		$command .= "Filter: state != 0\n"; // Not OK
		$command .= "Filter: state_type = 1\n"; // HARD state only
		$command .= "Filter: notifications_enabled = $notif\n";
		$command .= "Filter: acknowledged = $ackd\n";
		$command .= "\n"; // The final newline is required
	}
	
	// Write the command
	fwrite( $socket, $command );

	// Read the data while the connection is open, omitting any empty booleans for false values
	while( ( $buffer = fgets( $socket ) ) !== false ){ 
		$data[] = $buffer;
	}

	// Close the socket
	fclose( $socket );
	
	// Remove the whitespace from the beginning and end of the array values
	$clean_data = array_map( 'trim', $data );
	return $clean_data;
}

// Get the alert data from LiveStatus
function get_alerts( $type, $ackd, $notif ) {
	global $extinfo_url, $hostgroup;
	$alertArray = array();
	$count = 0;
	
	$status = get_liveStatus( $type, $ackd, $notif );
		
	foreach ( $status as $s ) {
		$info = explode( ";", $s );

		$alertArray["$count"]["host_name"] = $info[0];
		$alertArray["$count"]["plugin_output"] = $info[2];
		$alertArray["$count"]["last_state_change"] = $info[3];
		
		if ( $type == "hosts" ) {
			$alertArray["$count"]["type"] = "Host";
			$alertArray["$count"]["service_name"] = "Ping Check";
			$alertArray["$count"]["url"] = $extinfo_url . "?type=1&host=" . str_replace( " ", "+", $info[0] );
			switch ( $info[1] ) {
				case "0":
					$alertArray["$count"]["current_state"] = "UP";
				break;
				case "1":
					$alertArray["$count"]["current_state"] = "DOWN";
				break;
				case "2":
					$alertArray["$count"]["current_state"] = "UNKNOWN";
				break;
				default:
					$alertArray["$count"]["current_state"] = "Unknown Error!";
				break;
			}	
		} elseif ( $type == "services" ) {
			$alertArray["$count"]["type"] = "Service";
			$alertArray["$count"]["service_name"] = $info[6];
			$alertArray["$count"]["url"] = $extinfo_url . "?type=2&host=" . str_replace( " ", "+", $info[0] ) . "&service=" . str_replace( " ", "+", $info[6] );
			switch ( $info[1] ) {
				case "0":
					$alertArray["$count"]["current_state"] = "OK";
				break;
				case "1":
					$alertArray["$count"]["current_state"] = "WARNING";
				break;
				case "2":
					$alertArray["$count"]["current_state"] = "CRITICAL";
				break;					
				case "3":
					$alertArray["$count"]["current_state"] = "UNKNOWN";
				break;
				default:
					$alertArray["$count"]["current_state"] = "Unknown Error";
				break;
			}	
		}
		
		// Acknowledged section:
		if ( $ackd ) {
			$alertArray["$count"]["acknowledged"] = $info[4];
			$commentBlob = explode( "|",$info[5] );
			$alertArray["$count"]["author"] = $commentBlob[1];
			$alertArray["$count"]["comment_data"] = $commentBlob[2];
		
			// Sort Acknowledges
			if ( array_filter( $alertArray ) ) {
				$sortArray = array(); 
				foreach( $alertArray as $alert ){ 
					foreach( $alert as $key=>$value ){ 
						if( !isset( $sortArray[$key] ) ) { 
							$sortArray[$key] = array(); 
						} 
						$sortArray[$key][] = $value; 
					} 
				}
				if ( $type == "hosts" ) {
					$orderby = "host_name"; // Sort by hostname
					array_multisort( $sortArray[$orderby], SORT_ASC,$alertArray );
				} elseif ( $type == "services" ) {
					$orderby1 = "current_state"; // The primary sort by
					$orderby2 = "host_name"; // The seconday sort by
					array_multisort( $sortArray[$orderby1], SORT_ASC,$sortArray[$orderby2], SORT_ASC,$alertArray ); 
				}

			}
		}		
		$count += 1;
	}
	return $alertArray;
}

// Show the data on the main page for the alerts. This combines both hosts and services in 1 output.
function show_alertData( $hostData, $serviceData, $ackd ) {
	$alertCount = 1;
	$emptyArray = array(); 
	$emptyArray[1]["current_state"] = "blank"; // Expand a blank area between hosts and services
	// Show active hosts
	?>
	<table>
		<tbody>
			<tr class="alert table_header">
				<th>Number</th>
				<th>Type</th>
				<th>Host</th>
				<th>Service</th>
				<th>Status</th>
				<th>Alert Duration</th>
				<th>Status Information</th>
				<?php if ( $ackd == 1 ) {
					echo "<th>Owner</th>";
					echo "<th>Comment</th>";
				} ?>
			</tr>
			<?php
			foreach ( array_merge( $hostData, $emptyArray, $serviceData ) as $key => $card ) {
				echo "<tr class='alert alert-".strtolower( $card['current_state'] )."'>";
					if ( $card['current_state'] != "blank" ) { 
						echo "<td class='number'>#" . $alertCount . "</td>";
						echo "<td class='type'><b>" . $card['type'] . "</b></td>";
						echo "<td class='hostname'><a href='" . $card["url"] . "' target='_blank'>" . $card["host_name"] . "</a></td>";
						echo "<td class='service'><a href='" . $card["url"] . "' target='_blank'>" . $card["service_name"] . "</a></td>";
						echo "<td class='status'>" . $card['current_state'] . "</td>";
						echo "<td class='duration'>" . duration( $card['last_state_change'] ) . "</td>";
						echo "<td class='output'>" . $card['plugin_output'] . "</td>";
						if ( $ackd == 1 ) {
							echo "<td class='comment'>" . $card['author'] . "</td>";
							echo "<td class='comment'>" . $card['comment_data'] . "</td>";
						}
					$alertCount += 1;
					}
				echo "</tr>";
			}
			?>
		</tbody>
	</table>
	<?php
}

//=========================================//
// Dynamic Body Content Reloaded From AJAX //
//=========================================//

function show_body_content( $hostgroup ) {
	global $hosts_total, $service_total;
	
	// Totals for hosts and services. Used for the quick stats in the header
	$hosts_total = count( get_liveStatus( "host_total", NULL, NULL ) );
	$services_total = count( get_liveStatus( "service_total", NULL, NULL ) );

	// Find open host issues, ack'd host issues and get the count of each
	$host_openIssues = get_alerts( "hosts", 0, 1 ); // acknowledged no, notify yes
	$host_openIssues_count = count( $host_openIssues ); 
	$hostAckd = get_alerts( "hosts", 1, 1 ); // acknowledged yes, notify yes
	$hostAckd_count = count( $hostAckd );

	// Find open service issues, ack'd service issues and get the count of each
	$service_openIssues = get_alerts( "services", 0, 1 ); // acknowledged no, notify yes
	$service_openIssues_count = count( $service_openIssues );
	$serviceAckd = get_alerts( "services", 1, 1 ); // acknowledged yes, notify yes
	$serviceAckd_count = count( $serviceAckd );

	?>
	
	<div class="headerStatusOuter">
        <div class="headerStatus" style="text-align:center;">
            <?php
			// Header Status Bar
			if ( ( $hosts_total || $service_total ) == 0 ) { // No data in the arrays.
				?>			
				<div class="alert alert-down">
					<h3>There was a problem fetching data from Nagios MK LiveStatus.</h3>
				</div>
				<?php
			} elseif ( $service_openIssues_count > 0 && $host_openIssues_count > 0 ) { 
                // Both hosts and services with problems
                echo '<div class="alert alert-down"><h2>';
					echo $host_openIssues_count . ( $host_openIssues_count > 1 ? " Host Problems and " : " Host Problem and " );
					echo $service_openIssues_count . ( $service_openIssues_count > 1 ? " Service Problems!" : " Service Problem!" );
				echo "</h2></div>";
			} elseif ( $host_openIssues_count > 0 ) { 
                // Hosts only are down
				echo '<div class="alert alert-down"><h2>';
					echo $host_openIssues_count . ( $host_openIssues_count > 1 ? " Host Problems" : " Host Problem" );
				echo "</h2></div>";
			} elseif ( $service_openIssues_count > 0 ) { 
                // Services only
				echo '<div class="alert alert-down"><h2>';
					echo $service_openIssues_count . ( $service_openIssues_count > 1 ? " Service Problems" : " Service Problem" );
				echo "</h2></div>";
			} elseif ( ( $hostAckd_count || $serviceAckd_count ) > 0 ) { 
                // We have problems, but are aware of it, so we are OK
				echo '<div class="alert alert-success"><h2>All Hosts and Services are OK or Acknowledged!</h2></div>';
			} else { 
                // Everything is online
				echo "<div class='alert alert-success'><h2>All Hosts and Services are OK!</h2></div>";
			}
			?>
		</div>
    </div>
	
	<?php 
	if ( $host_openIssues_count || $service_openIssues_count ) {
		// Only display this section if we have issues to show
		?>
		<div class="activeStatusOuter">
			<div class="activeStatus">
			<H1>ACTIVE ALERTS</H1>
				<?php
				show_alertData( $host_openIssues, $service_openIssues, 0 );
				?>
			</div>
		</div>
		<?php
	}
	
	if ( $hostAckd_count || $serviceAckd_count ) {
		// Only display this section if we have issues to show
		?>
		<div class="ackdStatusOuter">
			<div class="ackdStatus">
			<H1>ACKNOWLEDGED ALERTS</H1>
				<?php
				show_alertData( $hostAckd, $serviceAckd, 1 );
				?>
			</div>
		</div>
		<?php
	}

	?>
	<div class="footer_stats">
		<?php
		if ( $hostgroup ) { 
			echo $hostgroup . " has " . $hosts_total . " Hosts and " . $services_total . " Services."; 
		} else {			
			echo "All Nagios items: Monitoring " . $hosts_total . " Hosts and " . $services_total . " Services."; 
		}
		?>
	</div>
	<?php
	
}

// Used to display the "alert duration" time in the alert cards
function duration( $timeAgo ) {
	$DAY = 86400;
	$HOUR = 3600;

	$now = time();
	$diff = $now - $timeAgo;
	$days = floor( $diff / $DAY );
	$hours = floor( ( $diff % $DAY ) / $HOUR );
	$minutes = floor( ( ( $diff % $DAY ) % $HOUR ) / 60 );
	$secs = $diff % 60;
	
	if ( ( $days < "1" ) && ( $hours < "1" ) && ( $minutes < "1" ) ) {
		return sprintf( "%d seconds", $secs );
	} elseif ( ( $days < "1" ) && ( $hours < "1" ) ) {
		return sprintf( "%d minutes, %d seconds", $minutes, $secs );
	} elseif ( $days < "1" ) {
		return sprintf( "%d hours, %d minutes, %d seconds", $hours, $minutes, $secs );
	} else {
		return sprintf( "%d days, %d hours, %d minutes, %d seconds", $days, $hours, $minutes, $secs );
	}
}

// Header Clocks and Weather //
function get_location_time( $tzone = '' ) {
	// Find timezones at http://www.php.net/manual/en/timezones.php
	$date = new DateTime( null, new DateTimeZone( $tzone ) );
	echo $date->format( 'g:i A' );
}

function get_weather( $city = '' ) {
    if ( !$city ) die( 'City missing' );
    $wx_json = file_get_contents( "/var/www/html/ops/plugins/weather.json" );
    $wx = json_decode( $wx_json, true );
    $temp_f = $wx[$city]["temp_f"];
    //$icon = $wx[$city]["icon"];
    //$iconurl = $wx[$city]["iconurl"];
    //echo '<span id="weather"><img src="'.$iconurl.'"> '.$temp_f.'&deg;F</span>';
    echo '<span id="weather">' . $temp_f . '&deg;F</span>';
}

?>
