<?php 
   /*
   Nagios Operations Dashboard
   Pat O'Brien https://obrienlabs.net
   Description: Displays a quick glance overview of the environment. Requires MK Livestatus.
   Version: config.php -> DASHBOARD_VERSION
   */

require_once( "../functions.php" );

?>

		<div id="clock_la" class="clock_container outer">
			<div class="lbl">Los Angeles, CA</div>
			<div class="digital">
				<?php get_weather('la'); ?>
				<?php get_location_time('America/Los_Angeles'); ?>
			</div>
		</div>
		
		<div id="clock_chicago" class="clock_container inner">
			<div class="lbl">Chicago, IL</div>
			<div class="digital">
				<?php get_weather('chicago'); ?>
				<?php get_location_time('America/Chicago'); ?>
			</div>
		</div>
		
		<div id="clock_springfield" class="clock_container inner">
			<div class="lbl">Springfield, MA</div>
			<div class="digital">
				<?php get_weather('springfield'); ?>
				<?php get_location_time('America/New_York'); ?>
			</div>
		</div>
		
		<div id="clock_london" class="clock_container inner">
			<div class="lbl">London, UK</div>
			<div class="digital">
				<?php get_weather('london'); ?>
				<?php get_location_time('Europe/London'); ?>
			</div>
		</div>
		
		<div id="clock_hongkong" class="clock_container inner">
			<div class="lbl">Hong Kong, CN</div>
			<div class="digital">
				<?php get_weather('hongkong'); ?>
				<?php get_location_time('Asia/Hong_Kong'); ?>
			</div>
		</div>
		
		<div id="clock_tokyo" class="clock_container inner">
			<div class="lbl">Tokyo, Japan</div>
			<div class="digital">
				<?php get_weather('tokyo'); ?>
				<?php get_location_time('Asia/Tokyo'); ?>
			</div>
		</div>

		<div id="clock_sydney" class="clock_container inner">
			<div class="lbl">Sydney, AU</div>
			<div class="digital">
				<?php get_weather('sydney'); ?>
				<?php get_location_time('Australia/Sydney'); ?>
			</div>
		</div>