<?php
   /*
   Nagios Operations Dashboard
   Pat O'Brien https://obrienlabs.net
   Description: Displays a quick glance overview of the environment. Requires MK Livestatus.
   Version: config.php -> DASHBOARD_VERSION
   */

require_once( "./functions.php" );

date_default_timezone_set( $defaultTimeZone );

// Initial check if the settings have been edited.
if ( SETTINGS_EDITED == false ) {
    die( "Please edit the config.php file before continuing." );
}

//==========================================================================//
// Pretty URL handling to display the dashboard for the hostgroup requested //
// e.g. http://nagios/ops/<hostgroup name>/
//==========================================================================//

// Remove the directory path we don't want, and split into array by '/'
// Needs to be defined before the function.php call
$request  = str_replace( "/ops/", "", $_SERVER['REQUEST_URI'] );
$url_params = split( "/", $request );


// If there is a hostgroup defined in the URL:
//    Get all hostgroups and compare the URL to a valid hostgroup. If it's a match, then that's our search filter
//    If no valid hostgroup found, then show an error and show all items
if ( $url_params[0] ) {
	$all_hostgroups = get_liveStatus( "hostgroups", NULL, NULL );

	if ( in_array( strtolower( $url_params[0] ), $all_hostgroups ) ) { 
		$hostgroup = strtolower( $url_params[0] );
	} else {
		echo "<div class='alert-invalid-hostgroup'>Error: Invalid hostgroup ID <b>$url_params[0]</b>. Showing all items.</div>";
	}
}

?>

<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $title; ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
		<link href="styles.css" rel="stylesheet">
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
		<script type="text/javascript">	
		
			$.ajaxSetup ({
				// Disable caching of AJAX responses
				// Fix for IE
				cache: false
			});
			
			var templateDir = "<?php echo $templateDir; ?>";
			
			$(document).ready(function(){
				// Do an initial load of the elements
				refreshHeader();
				//$('.alert_body').load(templateDir + 'body.php');
				$.ajax({
					type: "POST",
					url: templateDir + 'body.php',
					data: { hostgroup: "<?php echo $hostgroup; ?>" },
					success: function(html){
                        $('.alert_body').html(html);
                    }
				});
			  
				// Set the intervals in which to refresh the sections
				setInterval(refreshHeader, <?php echo $refreshHeader * 1000; ?>); // Header: seconds defined in config.php
				
				// Footer countdown timer. This timer refreshes the .alert_body element
				var countdownInt = <?php echo $refreshBody; ?>,
				display = $('.footerStatus');
				footerTimer(countdownInt, display);
			});
			
			// The header & clock/weather bar
			function refreshHeader(){
				//$('.headerOuter').load(templateDir + 'header.php');
				//$('.locationBar').load(templateDir + 'locationBar.php');
			};

			// The countdown function for the footer
			function footerTimer(duration, display) {
				var timer = duration, minutes, seconds;
				setInterval(function () {
					minutes = parseInt(timer / 60, 10);
					seconds = parseInt(timer % 60, 10);

					minutes = minutes < 10 ? "0" + minutes : minutes;
					seconds = seconds < 10 ? "0" + seconds : seconds;

					display.text( "Next update in: " + minutes + ":" + seconds );

					if (--timer < 1) {
						timer = duration;
					}
					if (seconds == 1) {
						//$('.alert_body').load(templateDir + 'body.php'); // Reload status body elements
						$.ajax({
							type: "POST",
							url: templateDir + 'body.php',
							data:{ hostgroup: "<?php echo $hostgroup; ?>" },
							success: function(html){
								$('.alert_body').html(html);
							}
						});
					}
				}, 1000);
			}
		</script>
	</head>
	<body>
		<div id="content">
			<!-- Begin dynamic AJAX header content (Nagios Overview) -->
			<!--<div class="headerOuter"></div>-->
			<!-- End dynamic AJAX header content (Nagios Overview) -->
			
			<!-- Begin dynamic AJAX clock and weather content -->
			<!--<div class="locationBar"></div>-->
			<!-- End dynamic AJAX clock and weather content -->
			
			<div class="clear"></div>
			
			<!-- Begin dynamic AJAX body status content -->
			<div class="alert_body"></div>
			<!-- End dynamic AJAX body status content -->

			<div class="footer">
				<div class="footerStatus">
					<!-- Countdown clock here -->
				</div>
			</div>
			
		</div>
	</body>
</html>
