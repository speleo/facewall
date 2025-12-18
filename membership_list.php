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

/**
 *   this file contains
 *   	function display_membership_list($fw_title, $fw_mem_status, $fw_mem_type)
 *   	function build_membership_list()
 *   	function get_membership_data($query)
 *   	function show_membership_data()
 *   	function build_chart_data_tables()
**/

global $wpdb;

function display_membership_list($fw_title, $fw_mem_status, $fw_mem_type)
{
	//build_membership_list();
	return show_membership_data();
	//create_membership_csv_files();
}

function build_membership_list()
{
	// this function is called from a cron job and is run weekly
	// half the data comes from the membership_log but old records aren't in that
	// so we grab those from the membership table and merge it in.
	global $wpdb;
	$query_old=("SELECT 
			civicrm_contact.id  as cv_contact_id , 
			civicrm_contact.is_deceased as cv_is_deceased,
			civicrm_contact.deceased_date as cv_deceased_date,
			civicrm_contact.display_name  as cv_display_name , 
			membership.membership_type_id  as cv_membership_type_id , 
			membership.join_date  as cv_join_date , 
			membership.start_date  as cv_start_date , 
			membership.end_date  as cv_end_date,  
			membership.membership_type_id AS cv_membership_type_id

		FROM civicrm_contact      
     
		  LEFT JOIN civicrm_membership as membership
			ON membership.contact_id = civicrm_contact.id    
    
		WHERE membership.membership_type_id IN (1,2,3,4,5,6,7) 
		AND membership.is_test != 1
		AND membership.status_id NOT IN (7,8)
		");

	$query_log=("SELECT DISTINCT 
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

	// We are going to store the output in a new table
	// but first we are going to empty it
	$truncate = $wpdb->get_results("TRUNCATE TABLE civicrm_BEC_historical_membership");
	get_membership_data($query_log);
	get_membership_data($query_old);
	//return show_membership_data();
	//
	//chain on some other stuff
	//build_chart_data_tables();

	$msg = "build_membership_list - completed";
	return $msg;
}



function get_membership_data($query)
{
	//gather all the data from the various tables
	global $wpdb;

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


	// now loop through the results and inject the data into a new table
	// but we need to think about year vs club year.
	// around 1960 the club year shifted from calander year to 1st October - 30 September
	for($result_index = 0; $result_index < $result_count ; $result_index++)
		{
			$facewall_output = '';
			$row = $result[$result_index];

			$start_date = date("Y",strtotime($row->cv_start_date));

			// if the end date isn't set then set it to current year xxxx
			if ($row->cv_end_date === null)
				{
				$end_date = date("Y");
			}
			else{
				$end_date = date("Y",strtotime($row->cv_end_date));
			}
		
			// loop thru the years from start_date to end_date
			for($x = $start_date; $x <= $end_date; $x++)
			{
				$start_time = strtotime($row->cv_start_date);
				if (is_null($row->cv_deceased_date))
					{
						//$death_time = strtotime($row->cv_deceased_date); 
						$death_time = date("Y") +1;

					}
				else
					{
						//$death_time = strtotime($row->cv_deceased_date); 
						//$death_time = date("Y",$row->cv_deceased_date);
						$zzz = new DateTime($row->cv_deceased_date);  
						$death_time = $zzz->format('Y'); 

					//$death_time = date("Y") +1;
					}

				
				//  debug
				//
				/**
				$tmpfilename = '/home/bertie/public_html/wp/wp-content/plugins/facewall/facewall_debug_members_list.txt'; // Temporary file location
				$f = fopen($tmpfilename, 'a+');
				//fwrite($f,date("d/m : H:i :").$line.' ('.$execution_time.' seconds)'.PHP_EOL);
				if ($death_time != 2025)
				{
					fwrite($f,$row->cv_display_name.' '.$death_time.PHP_EOL);
				}
				fclose($f);
				**/
				//  end debug

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
				if (
					// loop if deceased and no deceased date)
					($row->cv_is_deceased == 1) && is_null($row->cv_deceased_date)
					
					// date("Y",$row->cv_deceased_date) >= date("Y",$mem_club_year))
					)
				{
					continue;
				}
				//elseif (date("Y",$death_time) >= date("Y",$mem_club_year))
				//elseif (date("Y",$death_time) >= date("Y",$mem_club_year))
				elseif ($death_time <= $mem_club_year)
				{
					continue;
				}



				// we can use real-escape-string
				// See http://php.net/manual/en/mysqli.real-escape-string.php for the
				// list of characters mysqli_real_escape_string escapes.
				// needed for names like Bob O'Malley White
				$search = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
				$replace = ["\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z"];
				$fixed_name = str_replace($search, $replace, $row->cv_display_name);

				// Check if the record already exists
				$check_query = "SELECT * FROM civicrm_BEC_historical_membership WHERE club_year = '$mem_club_year' AND contact_id = '$row->cv_contact_id'";
				$existing_record = $wpdb->get_results($check_query);

				if (empty($existing_record)) {
 					// Record does not exist, proceed with the INSERT query
					$insert_query = ("INSERT INTO civicrm_BEC_historical_membership (contact_id, display_name, club_year, join_date, start_date, end_date, membership_type)
						VALUES ('$row->cv_contact_id', '$fixed_name', '$mem_club_year', '', '$row->cv_start_date', '$row->cv_end_date', '$row->cv_membership_type_id')");
					$insert_result = $wpdb->query($insert_query);

					if ($insert_result) {
						echo "Record inserted successfully.";
					} else {
						echo "Error inserting record: " . $wpdb->last_error;
					}
				} else {
					echo "Record already exists.";
				}

				/**
				$insert = ("INSERT INTO civicrm_BEC_historical_membership (contact_id, display_name, club_year, join_date, start_date, end_date, membership_type)
					VALUES ('$row->cv_contact_id', '$fixed_name', '$mem_club_year', '', '$row->cv_start_date', '$row->cv_end_date', '$row->cv_membership_type_id')");
				$insert_result = $wpdb->get_results($insert);
				**/
				
			}
	}
}
//-----------------------------------------------------------------------------------------------------------------

function show_membership_data()
{
	global $wpdb;
	$facewall_output = '';
	$final_query = ("SELECT contact_id, display_name, club_year, join_date, start_date, end_date, membership_type
		FROM civicrm_BEC_historical_membership  
		ORDER BY club_year DESC, display_name ASC 
		");
	// Prepare the query
	$final = $wpdb->get_results($final_query);
	$final_count = count($final);
	// find the max year value in the data
	$club_year = max(array_column($final, 'club_year'));
	$facewall_output .= '<h2>Club Year '.$club_year. ' - '.array_count_values(array_column($final,'club_year'))[$club_year] .' members</h2>';
	for($final_index = 0; $final_index < $final_count ; $final_index++)
		{
			$row2 = $final[$final_index];
			// put in a header
			if ($row2->club_year != $club_year)
			{
				$club_year--;
				//$facewall_output .= '<h2>'.$club_year.'</h2>';
				$facewall_output .= '<h2>Club Year '.$club_year. ' - '.array_count_values(array_column($final,'club_year'))[$club_year] .' members</h2>';
			}		
			// setup some variables		
			$facewall_output .= '       '.$row2->display_name;
			$start_time = strtotime($row2->start_date);
			$end_time = strtotime($row2->end_date);
			if ( date("Y",$start_time) <= '1960' )
			{
				// ???
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

function build_chart_data_tables()
{
	global $wpdb;
	// stuff data into table for the length of tenure charts
	$truncate_query = ("TRUNCATE TABLE civicrm_BEC_current_year");
	$truncate = $wpdb->get_results($truncate_query);

	$final_query = ("
		INSERT INTO civicrm_BEC_current_year
		SELECT COUNT(*) AS mem_years, h.display_name, TIMESTAMPDIFF(YEAR, c.birth_date, CURDATE()) AS age
			FROM `civicrm_BEC_historical_membership` as h
        		LEFT JOIN civicrm_contact as c ON h.contact_id =c.id
				WHERE `contact_id` IN (
    					SELECT `contact_id` 
					FROM `civicrm_BEC_historical_membership` 
    					WHERE `club_year` = YEAR(CURDATE()))
			GROUP BY `display_name`
			ORDER BY COUNT(*) DESC
		");
	// Prepare the query
	$final = $wpdb->get_results($final_query);
	$msg = "build_chart_data_tables - completed";
	return $msg;

}


?>
