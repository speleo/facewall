<?php
/**
 * Helper class for BEC Facewall! module
 * 
 * @subpackage Modules
 * @link http://docs.joomla.org/J3.x:Creating_a_simple_module/Developing_a_Basic_Module
 * @license        GNU/GPL, see LICENSE.php
 * free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

global $wpdb;

function display_officer_list2($fw_title, $fw_mem_status, $fw_mem_type)
{
	//gather all the data from the various tables
	global $wpdb;
	$facewall_output = 'Boo';
	$query=("SELECT SQL_CALC_FOUND_ROWS 
			civicrm_contact.id  as cv_contact_id , 
			civicrm_contact.display_name  as cv_display_name , 
			membership.membership_type_id  as cv_membership_type_id , 
			membership.join_date  as cv_join_date , 
			membership.start_date  as cv_start_date , 
			membership.end_date  as cv_end_date , 
			civicrm_value_committee_14.post_33  as cv_post ,
			civicrm_option_value.weight as cv_weight
   

		FROM civicrm_contact      
     
		  LEFT JOIN civicrm_membership as membership
			ON membership.contact_id = civicrm_contact.id    
    
		  LEFT JOIN civicrm_value_committee_14 civicrm_value_committee_14 
		      ON civicrm_value_committee_14.entity_id = membership.id 

		  LEFT JOIN civicrm_option_value
		      ON civicrm_option_value.label = civicrm_value_committee_14.post_33 
		      AND civicrm_option_value.option_group_id = 149

		WHERE membership.membership_type_id = 18     
		");

	//  inject the data back into a new table adding a row for every club year of membership.
	$result = $wpdb->get_results($query);

	if (!$result) 
	{
		$facewall_output .= '<h2>'.$fw_title.'</h2>';
		$facewall_output .= 'no members found matching this search';  
		return $facewall_output;
	}
	$result_count = count($result);

	// We are going to store the output in a new table
	// but first we are going to empty it
	$truncate = $wpdb->get_results("TRUNCATE TABLE civicrm_BEC_historical_officer_membership");


	//now loop through the rults and inject the data into a new table
	// but we need to think about year vs club year.
	// around 1960 the club year shifted from calander year to 1st October - 30 September
	for($result_index = 0; $result_index < $result_count ; $result_index++)
		{
			$row = $result[$result_index];

			$start_date = date("Y",strtotime($row->cv_start_date));
			$end_date = date("Y",strtotime($row->cv_end_date));
		
			for($x = $start_date; $x <= $end_date; $x++)
			{
				$start_time = strtotime($row->cv_start_date);
				//if ( date("d",$start_time) == '01' && date("d",$start_time) == '10' )

				if ( date("Y",$start_time) <= '1960' )
					{
					$mem_club_year = $x;
					}
				else
					{
						// since the club year starts in October the year before we need to skip that year 
						// thereby just recording the major part of the year.
						if (( date("d",$start_time) == '01' ) && ( date("m",$start_time) == '10' ) && ( date("Y",$start_time) == $x )  )
						{
							continue;
						}
						else
						{
							$mem_club_year = $x;
						}
					}
				$insert = ("INSERT INTO civicrm_BEC_historical_officer_membership (contact_id, display_name, post, club_year, join_date, start_date, end_date, weight)
					VALUES ('$row->cv_contact_id', '$row->cv_display_name', '$row->cv_post', '$mem_club_year', '$row->cv_join_date', '$row->cv_start_date', '$row->cv_end_date', '$row->cv_weight')");
				$insert_result = $wpdb->get_results($insert);
				
			}
		}
//-----------------------------------------------------------------------------------------------------------------
	$final_query = ("SELECT contact_id, display_name, post, club_year, join_date, start_date, end_date, weight
		FROM civicrm_BEC_historical_officer_membership  
		WHERE `club_year` <= YEAR(CURDATE())
		ORDER BY club_year DESC, weight ASC;
		");

	// Prepare the query
	$final = $wpdb->get_results($final_query);
	$final_count = count($final);

	// find the max year value in the data
	$club_year = max(array_column($final, 'club_year'));
	$facewall_output .= '<h2>'.$club_year.'</h2>';

	for($final_index = 0; $final_index < $final_count ; $final_index++)
		{
			$row2 = $final[$final_index];
			
			if ($row2->club_year != $club_year)
			{
				$club_year--;
				$facewall_output .= '<h2>'.$club_year.'</h2>';

			} 
			//$facewall_output .= $row2->club_year. ' ' ;		
			
			$facewall_output .= '       '.$row2->display_name. ' - '.$row2->post;
			$start_time = strtotime($row2->start_date);
			$end_time = strtotime($row2->end_date);


			if ( date("Y",$start_time) <= '1960' )
			{
				if (( date("Y",$start_time) <= '1960' ) && ( date("Y",$start_time) <= '1960' ))
				{}


				//$mem_club_year = $x;
			}
			else
			{
				// handle people JOINING a role part way through the year
				if (( date("d",$start_time) == '01' ) && ( date("m",$start_time) == '10' ) )
				{
					//continue;
				}
				else
				{
					if (date("Y",$start_time) == $club_year) 
					{
						$facewall_output .= ' - from: '.date("F j, Y ",$start_time);
					}
				}
				
				// handle people LEAVING a role part way through the year
				if (( date("d",$end_time) == '30' ) && ( date("m",$end_time) == '09' )) 
				{
					//continue;
				}
				else
				{
					if (date("Y",$end_time) == $club_year) 
					{
						$facewall_output .= ' - until: '.date("F j, Y ",$end_time);
					}
				}

			}

			$facewall_output .= '</br>';

		}


	$facewall_output .= '</br>Number of records ' . $final_index ;
	//return $result;
	//
	//$facewall_output = $result;
	//print("<pre>".print_r($final,true)."</pre>");

	
	return  $facewall_output;
	
}

?>
