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

function display_membership_list_test($fw_title, $fw_mem_status, $fw_mem_type)
{
	//get_membership_data($query);
	show_membership_data();

}

function get_membership_data($query)
{
	//gather all the data from the various tables
	global $wpdb;
	$facewall_output = '';
	$query_old=("SELECT 
			civicrm_contact.id  as cv_contact_id , 
			civicrm_contact.is_deceased as cv_is_deceased,
			civicrm_contact.deceased_date as cv_deceased_date,
			civicrm_contact.display_name  as cv_display_name , 
			membership.membership_type_id  as cv_membership_type_id , 
			membership.join_date  as cv_join_date , 
			membership.start_date  as cv_start_date , 
			membership.end_date  as cv_end_date   

		FROM civicrm_contact      
     
		  LEFT JOIN civicrm_membership as membership
			ON membership.contact_id = civicrm_contact.id    
    
		WHERE membership.membership_type_id IN (1,2,3,4,5,6,7) 
		");

	$query=("SELECT DISTINCT 
			sql_table.start_date AS cv_start_date,
			sql_table.end_date AS cv_end_date,
		    	contact.id AS cv_contact_id,
			contact.is_deceased AS cv_is_deceased,
			contact.deceased_date AS cv_deceased_date,
			contact.display_name AS cv_display_name,
			membership_type.name AS cv_membership_type_name,
			sql_table.membership_type_id AS cv_membership_type_id
    
		FROM civicrm_membership_log 	AS sql_table
		JOIN civicrm_membership_type 	AS membership_type ON membership_type.id = sql_table.membership_type_id
		JOIN civicrm_membership_status 	AS membership_status ON membership_status.id = sql_table.status_id
		JOIN civicrm_membership 		AS membership ON membership.id = sql_table.membership_id
		JOIN civicrm_contact 			AS contact ON contact.id  = membership.contact_id

		WHERE sql_table.membership_type_id IN (1,2,3,4,5,6,7)
		AND membership.is_test != 1
		AND membership.status_id NOT IN (7,8)
		ORDER BY cv_display_name
		");

/**
	1	Ordinary	
	2	Probationary	
	3	Joint	
	4	Life	
	5	Joint Members Life	
	6	Honorary Life	
	7	Joint Probationary	
	8	Special	
	9	BCA - Active	
	10	BCA - Surface	
	11	BCA - Student	
	12	BCA - Other	
	15	Student Discount	
	16	BCA - Publications Only	
	17	Joint Discount	
	18	Committee	
**/

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
	$truncate = $wpdb->get_results("TRUNCATE TABLE civicrm_BEC_historical_membership");


	//now loop through the results and inject the data into a new table
	// but we need to think about year vs club year.
	// around 1960 the club year shifted from calander year to 1st October - 30 September
	for($result_index = 0; $result_index < $result_count ; $result_index++)
		{
			$row = $result[$result_index];

			$start_date = date("Y",strtotime($row->cv_start_date));
			$end_date = date("Y",strtotime($row->cv_end_date));
			if ($row->cv_end_date === null)
			{ 
				$end_date = date("Y");
			}
			if ($row->cv_end_date === null)
			{ 
				$end_date = date("Y");
			}

		
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
				
				// loop if end date is in the next year or beyond
				if ($mem_club_year > date("Y"))
				{
					continue;
				}
				// loop if member is deceased after this date
				if (($row->cv_is_deceased == 1) && (date("Y",$row->cv_deceased_date) >= date("Y",$mem_club_year)))
				{
					continue;
				}
				/** the membership_log table does not contain join date so we are going to empty store that field.
				$insert = ("INSERT INTO civicrm_BEC_historical_membership (contact_id, display_name, club_year, join_date, start_date, end_date, membership_type)
					VALUES ('$row->cv_contact_id', '$row->cv_display_name', '$mem_club_year', '$row->cv_join_date', '$row->cv_start_date', '$row->cv_end_date', '$row->cv_membership_type_id')");
				**/
				
				$insert = ("INSERT INTO civicrm_BEC_historical_membership (contact_id, display_name, club_year, join_date, start_date, end_date, membership_type)
					VALUES ('$row->cv_contact_id', '$row->cv_display_name', '$mem_club_year', '', '$row->cv_start_date', '$row->cv_end_date', '$row->cv_membership_type_id')");
				$insert_result = $wpdb->get_results($insert);
				
			}
	}
}
//-----------------------------------------------------------------------------------------------------------------

function show_membership_data()
{
	$final_query = ("SELECT contact_id, display_name, club_year, join_date, start_date, end_date, membership_type
		FROM civicrm_BEC_historical_membership  
		ORDER BY club_year DESC
		");
	// Prepare the query
	$final = $wpdb->get_results($final_query);
	$final_count = count($final);
	// find the max year value in the data
	$club_year = max(array_column($final, 'club_year'));
	$facewall_output .= '<h2>'.$club_year. ' - '.array_count_values(array_column($final,'club_year'))[$club_year] .'</h2>';
	for($final_index = 0; $final_index < $final_count ; $final_index++)
		{
			$row2 = $final[$final_index];
			// put in a header
			if ($row2->club_year != $club_year)
			{
				$club_year--;
				//$facewall_output .= '<h2>'.$club_year.'</h2>';
				$facewall_output .= '<h2>'.$club_year. ' - '.array_count_values(array_column($final,'club_year'))[$club_year] .'</h2>';
			}		
			// setup some variables		
			$facewall_output .= '       '.$row2->display_name;
			$start_time = strtotime($row2->start_date);
			$end_time = strtotime($row2->end_date);
			if ( date("Y",$start_time) <= '1960' )
			{
				if (( date("Y",$start_time) <= '1960' ) && ( date("Y",$start_time) <= '1960' ))
				{}
			}
			else
			{
				// handle people JOINING a role part way through the year
				if (( date("d",$start_time) == '01' ) && ( date("m",$start_time) == '10' ) )
				{}
				else
				{
					if (date("Y",$start_time) == $club_year) 
					{ 
						$facewall_output .= ' - from: '.date("F j, Y ",$start_time);
					}
				}
				
				// handle people LEAVING a role part way through the year
				if (( date("d",$end_time) == '30' ) && ( date("m",$end_time) == '09' )) 
				{}
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
	return  $facewall_output;
}

?>
