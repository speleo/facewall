<?php

// this file is run from wordpress crons
// menu [tools] [cron events] [facewall_cron]


global $wpdb;

// include "/home/bertie/public_html/wp/wp-content/plugins/facewall/membership_list.php";

if (!function_exists('display_membership_list')) {
    	// Include the file only if the function is not defined
	include_once "/home/bertie/public_html/wp/wp-content/plugins/facewall/membership_list.php";

}


// include_once "/home/bertie/public_html/wp/wp-content/plugins/facewall/membership_list.php";

$tmpfilename = '/home/bertie/public_html/wp/wp-content/plugins/facewall/facewall_cron.txt'; // Temporary file location
$f = fopen($tmpfilename, 'w+');

$line = build_chart_data_tables();
fwrite($f, $line);
fclose($f);

?>
