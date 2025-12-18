<?php

/**
 * Used to create a table of fees and prorata by month info
 * on the Membership Subs and Application page
 *
 * Henry Bennett 2023
 * version 1.00
 *
 **/

function calc_prorata_fees($month, $membership) {
	$fee = get_membership_fee($membership);
	$prorata_fee = round($fee/12 * (12 - $month + 1),2);
	return number_format($prorata_fee,2);
}


function get_membership_fee($type)
{
	if ( ! function_exists( 'civicrm_initialize' ) ) { 
		return; 
	}
	civicrm_initialize();
	$membershipTypes = civicrm_api3('MembershipType', 'getvalue', [
		'return' => "minimum_fee",
		'name' => $type,
		]);
	//echo '<pre>'.print_r($membershipTypes).'</pre>';
	return $membershipTypes;
}


function display_prorata_fees() {
	//table headers
	
	$output = "<table><tr><th>Membership Type</th>";
	for ($month = -2; $month <= 9; $month++) {
	    $output .= "<th>".date("M", mktime(0, 0, 0, $month, 1))."</th>";
	}
	$output .= "</tr>";

	//populate table
	$memberships = array("Ordinary", "U30 Discount", "Joint Discount", "BCA - Active", "BCA - Surface", "BCA - Student");
	// $memberships = array("Ordinary", "Student Discount", "Joint Discount", "BCA - Active", "BCA - Surface", "BCA - Student");
	foreach ($memberships as $membership) {
	    $output .= "<tr><td>".$membership."</td>";
	    for ($month = 1; $month <= 12; $month++) {
	        $output .= "<td>".calc_prorata_fees($month, $membership)."</td>";
	    }
	    $output .= "</tr>";
	}
	
	$output .= "</table>";
	
	return $output;

}

?>
