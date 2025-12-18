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
 *
 * function display_facewall_normal_new($fw_title, $fw_mem_status, $fw_mem_type)
 * function display_facewall_under_30($fw_title, $fw_mem_status, $fw_mem_type)
 * function display_facewall_probationary($fw_title, $fw_mem_status, $fw_mem_type)
 * function display_all_membership()
 * function consolidateArray($array) - row 514
 * function membership_details($row, $memberships)
 * function display_officer_list($fw_title, $fw_mem_status, $fw_mem_type)
 * function display_officer_grid_year($year)
 * function facewall_pad_grid($column, $max_column, $html_output)
 * function build_officer_historical_table($a)
 * function build_BEC_historical_summary_table($a)
 */

global $wpdb;

include_once 'current_list.php';

function display_facewall_normal_new($fw_title, $fw_mem_status, $fw_mem_type)
{
	global $wpdb;
	$facewall_output = '';

	$query=("SELECT cust.Membership_Number, c.first_name, c.nick_name, c.last_name, CONCAT(c.last_name, c.first_name),  mtype.name, mstatus.name AS m_status, mem.id, hist.image_URL, hist.summary, cust.facewall_public_84
		from civicrm_contact AS c
		LEFT JOIN custom_value_1_BEC as cust ON c.id = cust.entity_id
		LEFT JOIN civicrm_BEC_historical_summary as hist ON c.id  = hist.contact_id
		LEFT JOIN civicrm_membership AS mem  ON c.id = mem.contact_id AND mem.membership_type_id < 18
		LEFT JOIN civicrm_membership_type AS mtype ON  mem.membership_type_id = mtype.id
		LEFT JOIN civicrm_membership_status AS mstatus ON mem.status_id = mstatus.id
		WHERE mtype.id ".$fw_mem_type." AND status_id ".$fw_mem_status." AND c.is_deceased != 1 
		ORDER BY last_name, first_name");

	// Prepare the query
	$final = $wpdb->get_results($query);
	$final_count = count($final);
	if ($final_count == 0) {
		$facewall_output = 'There are none';
		return $facewall_output;
	}

	// find the highest membership number in the data
	$current_mem_num = max(array_column($final, 'Membership_Number'))+1;

	// setup some variables
        $column = 1;
	$max_column = 5;
	$display_row = 1;
	$display_row_div = 1;
	$image_dir = "wp/wp-content/uploads/civicrm/custom/";
	$base_dir = "/home/bertie/public_html/";
	$member_dir = "images/members/";
	$root_dir = "https://bec-cave.org.uk/";
	$thumb_dir = "tn/";
	$thumb_width = 174;
	$thumb_height = 205;
	//$memberships = '';

	// include CSS to format it properly
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/bootstrap-responsive.css">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/custom.css">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/facewall.css">';
	$facewall_output .= '<h2>'.$fw_title.' - '.$final_count.' members</h2>';

	// then loop thru the rest of the records 
	for($final_index = 0; $final_index < $final_count ; $final_index++)
		{
			$row = $final[$final_index];

			// now place the output for each member
			if ( $display_row_div == 1 ){ 
				$facewall_output .= '<div class="items-row cols-4 row-'.$display_row.' row-fluid clearfix bec_members_row">';  
				$display_row++;
				$display_row_div++;
			} 
      
			$facewall_output .= '<div class="span2">';    
		        $facewall_output .= '<div class="item column-'.$column.'" itemprop="blogPost" itemscope="" itemtype="http://schema.org/BlogPosting">';
			$facewall_output .= '<div class="page-header">';
			$facewall_output .= '<h5 itemprop="name" max-width=250px>'.$row->first_name.' '.$row->last_name.'</h5>';
			
			// and then the name
			//$facewall_output .= $row->display_name;
			$facewall_output .= '</div>';
			$facewall_output .= '<div class="pull-left item-image">';
			$facewall_output .= '<div class="fw_middle">';

			// membership summary
			$facewall_output .= '<div class="text">'.$row->summary.'</div>';
			$facewall_output .= '</div>';
			
			// now place their picture on the page
			if ($row->image_URL && $row->facewall_public_84 == 1) 
				{
				if (!strpos($row->image_URL,"photo=")){
					//$image = substr($row->image_URL,strrpos($row->image_URL,"/")+1);   //removed the +1 as it removed the first char of the filename
					$image = substr($row->image_URL,strrpos($row->image_URL,"tn_")+3);   //as the source imageURL includes tn_xxxx we strip the first 3 char

				}else{
					$image = substr($row->image_URL,strpos($row->image_URL,"photo=")+6);
				}
				// check for existance of correct thumbnail, if one doesn't exist then create it
			 	facewall_create_thumbnail($base_dir, $image_dir, $thumb_dir, $thumb_width, $image);
				$facewall_output .= '<img src=\wp\wp-content\uploads\civicrm\custom\tn\174'.'\\' .$row->image_URL. ' class="fw_image" height="205" width="174" alt='.$row->last_name.'>';
				}
			else	{
				$facewall_output .= '<img src=\wp\wp-content\plugins\facewall\img\unknown.jpg class="fw_image" height="205" width="174" alt=\'No Picture\'>';
				}
			//
			//$facewall_output .= $image_URL;
			// cleanup the divs and increment counters

			$facewall_output .= '</div>';
			$facewall_output .= '</div>';
			$facewall_output .= '</div>';
	  		if ( $column < $max_column ){
				$column++;
			} 
			else {
				$column = 1;
		  		$display_row_div = 1;
		  		$facewall_output .= '</div>';  
			}
		// and loop back around
		}
		

	// finally clean up the remaining divs
	$facewall_output .= facewall_pad_grid($column, $max_column,'');
	$facewall_output .= '</div>';
	$facewall_output .= '</br>Number of records ' . $final_index ;
	
	return  $facewall_output;
	
}


function display_facewall_under_30($fw_title, $fw_mem_status, $fw_mem_type)
{
	global $wpdb;
	$facewall_output = '';

	$lastAGM = date("Y-m-d",strtotime("first Saturday of October ".date("Y", strtotime("-1 year"))));
	//$lastAGM = strtotime("first Saturday of October ".date("Y", strtotime("-1 year")));

	$ageLimit = 30;
	//
	//$facewall_output .= '<h2>'.$lastAGM.' '.date('d-m-y',$lastAGM).' </h2>';
	//$facewall_output .= '<h2>'.$lastAGM.'</h2>';


	//$sql = "SELECT * FROM members WHERE birthdate <= '".date("Y-m-d", $lastAGM)."' AND (DATEDIFF('".date("Y-m-d", $lastAGM)."', birthdate) / 365.25) < $ageLimit";


	$query=("SELECT cust.Membership_Number, c.first_name, c.nick_name, c.last_name, c.birth_date,  mtype.name, mstatus.name AS m_status, mem.id, hist.image_URL, hist.summary, DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),c.birth_date)), '%Y') + 0 AS age
		from civicrm_contact AS c
		LEFT JOIN custom_value_1_BEC as cust ON c.id = cust.entity_id
		LEFT JOIN civicrm_BEC_historical_summary as hist ON c.id  = hist.contact_id
		LEFT JOIN civicrm_membership AS mem  ON c.id = mem.contact_id AND mem.membership_type_id < 18
		LEFT JOIN civicrm_membership_type AS mtype ON  mem.membership_type_id = mtype.id
		LEFT JOIN civicrm_membership_status AS mstatus ON mem.status_id = mstatus.id
		WHERE mtype.id <9 
			AND status_id <4 
			AND c.is_deceased != 1 
			AND DATE_SUB(\"".$lastAGM."\", INTERVAL 30 YEAR) <= birth_date
		ORDER BY last_name, first_name");

	// Prepare the query
	$final = $wpdb->get_results($query);
	$final_count = count($final);

	// find the highest membership number in the data
	$current_mem_num = max(array_column($final, 'Membership_Number'))+1;

	// setup some variables
        $column = 1;
	$max_column = 5;
	$display_row = 1;
	$display_row_div = 1;
	$image_dir = "wp/wp-content/uploads/civicrm/custom/";
	$base_dir = "/home/bertie/public_html/";
	$member_dir = "images/members/";
	$root_dir = "https://bec-cave.org.uk/";
	$thumb_dir = "tn/";
	$thumb_width = 174;
	$thumb_height = 205;
	//$memberships = '';

	// include CSS to format it properly
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/bootstrap-responsive.css">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/custom.css">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/facewall.css">';
	$facewall_output .= '<h2>'.$fw_title.' at the last AGM - '.$lastAGM.'<br>'.$final_count.' members</h2>';

	// then loop thru the rest of the records 
	for($final_index = 0; $final_index < $final_count ; $final_index++)
		{
			$row = $final[$final_index];

			// now place the output for each member
			if ( $display_row_div == 1 ){ 
				$facewall_output .= '<div class="items-row cols-4 row-'.$display_row.' row-fluid clearfix bec_members_row">';  
				$display_row++;
				$display_row_div++;
			} 
      
			$facewall_output .= '<div class="span2">';    
		        $facewall_output .= '<div class="item column-'.$column.'" itemprop="blogPost" itemscope="" itemtype="http://schema.org/BlogPosting">';
			$facewall_output .= '<div class="page-header">';
			$facewall_output .= '<h5 itemprop="name" max-width=250px>'.$row->first_name.' '.$row->last_name.'</h5>';
			
			// and then the name
			//$facewall_output .= $row->display_name;
			$facewall_output .= '</div>';
			$facewall_output .= '<div class="pull-left item-image">';
			$facewall_output .= '<div class="fw_middle">';

			// membership summary
			$facewall_output .= '<div class="text">'.$row->summary.'</div>';
			$facewall_output .= '</div>';
			
			// now place their picture on the page
			if ($row->image_URL)
				{
				$facewall_output .= '<img src=\wp\wp-content\uploads\civicrm\custom\tn\174'.'\\' .$row->image_URL. ' class="fw_image" height="205" width="174" alt='.$row->last_name.'>';
				}
			else	{
				$facewall_output .= '<img src=\wp\wp-content\plugins\facewall\img\unknown.jpg class="fw_image" height="205" width="174" alt=\'No Picture\'>';
				}
				//
			// $facewall_output .= $image_URL;
			// cleanup the divs and increment counters

			$facewall_output .= '</div>';
			$facewall_output .= '</div>';
			$facewall_output .= '</div>';
	  		if ( $column < $max_column ){
				$column++;
			} 
			else {
				$column = 1;
		  		$display_row_div = 1;
		  		$facewall_output .= '</div>';  
			}
		// and loop back around
		}
		

	// finally clean up the remaining divs
	$facewall_output .= facewall_pad_grid($column, $max_column,'');
	$facewall_output .= '</div>';
	$facewall_output .= '</br>Number of records ' . $final_index ;
	
	return  $facewall_output;
	
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//
function display_facewall_probationary($fw_title, $fw_mem_status, $fw_mem_type)
{
	global $wpdb;
	$facewall_output = '';

	$query=("SELECT cust.Membership_Number, c.first_name, c.nick_name, c.last_name, CONCAT(c.last_name, c.first_name),  mtype.name, mstatus.name AS m_status, mem.id, mem.join_date, hist.image_URL, hist.summary
		from civicrm_contact AS c
		LEFT JOIN custom_value_1_BEC as cust ON c.id = cust.entity_id
		LEFT JOIN civicrm_BEC_historical_summary as hist ON c.id  = hist.contact_id
		LEFT JOIN civicrm_membership AS mem  ON c.id = mem.contact_id AND mem.membership_type_id < 18
		LEFT JOIN civicrm_membership_type AS mtype ON  mem.membership_type_id = mtype.id
		LEFT JOIN civicrm_membership_status AS mstatus ON mem.status_id = mstatus.id
		WHERE mtype.id ".$fw_mem_type." AND status_id ".$fw_mem_status." AND c.is_deceased != 1
		ORDER BY mem.join_date, last_name, first_name");

	// Prepare the query
	$final = $wpdb->get_results($query);
	$final_count = count($final);

	// find the highest membership number in the data
	$current_mem_num = max(array_column($final, 'Membership_Number'))+1;

	// setup some variables
        $column = 1;
	$max_column = 5;
	$display_row = 1;
	$display_row_div = 1;
	$image_dir = "wp/wp-content/uploads/civicrm/custom/";
	$base_dir = "/home/bertie/public_html/";
	$member_dir = "images/members/";
	$root_dir = "https://bec-cave.org.uk/";
	$thumb_dir = "tn/";
	$thumb_width = 174;
	$thumb_height = 205;
	//$memberships = '';

	// include CSS to format it properly
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/bootstrap-responsive.css">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/custom.css">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/facewall.css">';
	$facewall_output .= '<h2>'.$fw_title.' - '.$final_count.' members</h2>';

	// then loop thru the rest of the records 
	for($final_index = 0; $final_index < $final_count ; $final_index++)
		{
			$row = $final[$final_index];

			// now place the output for each member
			if ( $display_row_div == 1 ){ 
				$facewall_output .= '<div class="items-row cols-4 row-'.$display_row.' row-fluid clearfix bec_members_row">';  
				$display_row++;
				$display_row_div++;
			} 

			// calculate years & months from today
			/**
			$start_date = strtotime($row->join_date);
			$today = strtotime("now");
			$diff = $today - $start_date;
			$num_years = floor($diff / (365 * 60 * 60 * 24));
			$remaining_seconds = $diff % (365 * 60 * 60 * 24);
			$num_months = floor($remaining_seconds / (30 * 60 * 60 * 24));

			**/


			$start = new DateTime($row->join_date);
			$today = new DateTime();
			$interval = date_diff($start, $today);

			$years = $interval->y;
			$months = $interval->m;

			if ($years == 1) {
			    $yearText = "1 year";
			} elseif ($years > 1) {
			    $yearText = "{$years} years";
			} else {
			    $yearText = "";
			}

			if ($months == 1) {
			    $monthText = "1 month";
			} elseif ($months > 1) {
			    $monthText = "{$months} months";
			} else {
			    $monthText = "";
			}

			if ($years > 0 && $months > 0) {
			    $result = "{$yearText} and {$monthText}";
			} else {
			    $result = "{$yearText}{$monthText}";
			}

//echo $result;


//echo "The difference is " . $num_years . " years and " . $num_months . " months.";

			$facewall_output .= '<div class="span2">';    
		        $facewall_output .= '<div class="item column-'.$column.'" itemprop="blogPost" itemscope="" itemtype="http://schema.org/BlogPosting">';
			$facewall_output .= '<div class="page-header">';
			$facewall_output .= '<h5 itemprop="name" max-width=250px>'.$result.'<br>'.date("Y, F",strtotime($row->join_date)).'<br>'.$row->first_name.' '.$row->last_name.'</h5>';
			//$facewall_output .= '<h5 itemprop="name" max-width=250px>'.$num_years.' years '.$num_months.' months<br>'.date("Y, F",strtotime($row->join_date)).'<br>'.$row->first_name.' '.$row->last_name.'</h5>';
			//$facewall_output .= '<h5 itemprop="name" max-width=250px>'.$row->join_date.'<br>'.$row->first_name.' '.$row->last_name.'</h5>';

			
			// and then the name
			//$facewall_output .= $row->display_name;
			$facewall_output .= '</div>';
			$facewall_output .= '<div class="pull-left item-image">';
			$facewall_output .= '<div class="fw_middle">';

			// membership summary
			$facewall_output .= '<div class="text">'.$row->summary.'</div>';
			$facewall_output .= '</div>';
			
			// now place their picture on the page
			if ($row->image_URL)
				{
				$facewall_output .= '<img src=\wp\wp-content\uploads\civicrm\custom\tn\174'.'\\' .$row->image_URL. ' class="fw_image" height="205" width="174" alt='.$row->last_name.'>';
				}
			else	{
				$facewall_output .= '<img src=\wp\wp-content\plugins\facewall\img\unknown.jpg class="fw_image" height="205" width="174" alt=\'No Picture\'>';
				}
				//
			//$facewall_output .= $image_URL;
			// cleanup the divs and increment counters

			$facewall_output .= '</div>';
			$facewall_output .= '</div>';
			$facewall_output .= '</div>';
	  		if ( $column < $max_column ){
				$column++;
			} 
			else {
				$column = 1;
		  		$display_row_div = 1;
		  		$facewall_output .= '</div>';  
			}
		// and loop back around
		}
		

	// finally clean up the remaining divs
	$facewall_output .= facewall_pad_grid($column, $max_column,'');
	$facewall_output .= '</div>';
	$facewall_output .= '</br>Number of records ' . $final_index ;
	
	return  $facewall_output;
	
}


//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

function display_all_membership()
{
	// displays a photo grid of every member, ever

	// This funcation is now run in a wordpress cron called via a hook
	// build_BEC_historical_summary_table($a);

	
	global $wpdb;
	$facewall_output = '';
	$final_query = ("SELECT * FROM civicrm_BEC_historical_summary
		ORDER BY id DESC");

	// Prepare the query
	$final = $wpdb->get_results($final_query);
	$final_count = count($final);

	// find the highest membership number in the data
	// $current_mem_num = max(array_column($final, 'mem_num'))+1;
	$current_mem_num = max(array_column($final, 'id'))+1;


	// setup some variables
        $column = 1;
	$max_column = 5;
	$display_row = 1;
	$display_row_div = 1;
	$image_dir = "wp/wp-content/uploads/civicrm/custom/";
	$base_dir = "/home/bertie/public_html/";
	$member_dir = "images/members/";
	$root_dir = "https://bec-cave.org.uk/";
	$thumb_dir = "tn/";
	$thumb_width = 174;
	$thumb_height = 205;
	//$memberships = '';

	// include CSS to format it properly
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/bootstrap-responsive.css">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/custom.css">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/facewall.css">';

	// then loop thru the rest of the records 
	for($final_index = 0; $final_index < $final_count ; $final_index++)
		{
			$row = $final[$final_index];

			// now place the output for each member
			if ( $display_row_div == 1 ){ 
				$facewall_output .= '<div class="items-row cols-4 row-'.$display_row.' row-fluid clearfix bec_members_row">';  
				$display_row++;
				$display_row_div++;
			} 
      
			$facewall_output .= '<div class="span2">';    
		        $facewall_output .= '<div class="item column-'.$column.'" itemprop="blogPost" itemscope="" itemtype="http://schema.org/BlogPosting">';
			$facewall_output .= '<div class="page-header">';
			$facewall_output .= '<h5 itemprop="name" max-width=250px>'.$row->id.'</h5>';
			
			// and then the name
			$facewall_output .= $row->display_name;
			$facewall_output .= '</div>';
			$facewall_output .= '<div class="pull-left item-image">';
			$facewall_output .= '<div class="fw_middle">';

			// membership summary
			$facewall_output .= '<div class="text">'.$row->summary.'</div>';
			$facewall_output .= '</div>';
			
			// now place their picture on the page
			if ($row->image_URL)
				{
				$facewall_output .= '<img src=\wp\wp-content\uploads\civicrm\custom\tn\174'.'\\' .$row->image_URL. ' class="fw_image" loading=lazy height="205" width="174" alt='.$row->display_name.'>';
				}
			else	{
				$facewall_output .= '<img src=\wp\wp-content\plugins\facewall\img\unknown.jpg class="fw_image" height="205" width="174" alt=\'No Picture\'>';
				}
				//
			//$facewall_output .= $image_URL;
			// cleanup the divs and increment counters

			$facewall_output .= '</div>';
			$facewall_output .= '</div>';
			$facewall_output .= '</div>';
	  		if ( $column < $max_column ){
				$column++;
			} 
			else {
				$column = 1;
		  		$display_row_div = 1;
		  		$facewall_output .= '</div>';  
			}
		// and loop back around
		}
		

	// finally clean up the remaining divs
	$facewall_output .= facewall_pad_grid($column, $max_column,'');
	$facewall_output .= '</div>';
	$facewall_output .= '</br>Number of records ' . $final_index ;
	
	return  $facewall_output;
	
}



//pppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppp


function consolidateArray($array) {
    $grouped = [];
    foreach ($array as $element) {
        $title = $element['title'];
        if (!isset($grouped[$title])) {
            $grouped[$title] = [];
        }
        $grouped[$title][] = $element;
    }

    $consolidated = [];
    foreach ($grouped as $title => $group) {
        usort($group, function ($a, $b) {
            return $a['start'] - $b['start'];
        });

        $rangeStart = $group[0]['start'];
        $rangeEnd = $group[0]['end'];
        for ($i = 1; $i < count($group); $i++) {
            $current = $group[$i];
            if ($current['start'] <= $rangeEnd) {
                $rangeEnd = max($rangeEnd, $current['end']);
            } else {
                $consolidated[] = [
                    'title' => $title,
                    'start' => $rangeStart,
                    'end' => $rangeEnd,
                ];
                $rangeStart = $current['start'];
                $rangeEnd = $current['end'];
            }
        }

        $consolidated[] = [
            'title' => $title,
            'start' => $rangeStart,
            'end' => $rangeEnd,
        ];
    }

    return $consolidated;
}

function membership_details($row, $memberships)
{
	$mems = $memberships;
	if ($row->name == 'Committee')
	{
		$post = $row->source;
	} else {
		$post = $row->name;
	}
	switch ($post) {
		case "Assistant Caving Secretary":
			$post = "Asst. Cave Sec";
			break;
		case "Belfry Bulletin Editor":
			$post = "BB Editor";
			break;
		case "Ladies Representative":
			$post = "Ladies Rep";
			break;
		case "London Representative":
			$post = "London Rep";
			break;
		case "Assistant Hut Warden":
			$post = "Asst, Hut Warden";
			break;
		case "Membership Secretary":
			$post = "Membership Sec";
			break;
		case "Assistant Secretary":
			$post = "Assistant Sec";
			break;
		case "Climbing Secretary":
			$post = "Climbing Sec";
			break;
		case "Committee Chairman":
			$post = "Committee Chair";
			break;
		default:
			break;
	}
	$mems[] = ['title'=> $post, 'start'=>$row->start, 'end'=>$row->end];
	//echo 'mems <pre>'.print_r($mems,1).'</pre>';
	return $mems;
}



//###############################################################################################################

function display_officer_list($fw_title, $fw_mem_status, $fw_mem_type)
{
	// displays a list of officers grouped by year
	
	
	// gather all the data from the various tables
	// this is run on a cron.  
	// if you want to see updates live then uncomment this line
	//
	//build_officer_historical_table($a);
	
	global $wpdb;
	$facewall_output = '';
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

//==========================================================================================================================================

function display_officer_grid_year($year)
{
	// displays a photo grid of officers grouped by year
	
	// gather all the data from the various tables
	// this is run on a cron.  
	// if you want to see updates live then uncomment this line
	//
	// build_officer_historical_table();

	global $wpdb;
	$facewall_output = '';
	if ($year == 'current'){
		$fw_year = '=';
	}
	else {
		$fw_year = '<=';
	}
	
	/**
	if (date("m") >=9 ){
		$search_year = date("Y")+1;}
		// If we are in October show next years committee
	else{
		$search_year = date("Y");}
	**/
	// Determine when the committee changes over (first Saturday in October)
	$year_now = date("Y");
	$first_saturday_oct = strtotime("first saturday of october $year_now");

	// If today is before the first Saturday in October, use this year.
	// Otherwise, use next year.
	if (time() < $first_saturday_oct) {
	    $search_year = $year_now;
	} else {
	    $search_year = $year_now +1;
	}


	$final_query = ("SELECT contact_id, display_name, post, club_year, join_date, start_date, end_date, weight, image_URL
		FROM civicrm_BEC_historical_officer_membership  
		WHERE `club_year` ".$fw_year." ".$search_year."
		ORDER BY club_year DESC, weight ASC;
		");

	// Prepare the query
	$final = $wpdb->get_results($final_query);
	$final_count = count($final);

	// find the max year value in the data
	$club_year = max(array_column($final, 'club_year'));

	// setup some variables
        $column = 1;
	$max_column = 5;
	$display_row = 1;
	$display_row_div = 1;
	$image_dir = "wp/wp-content/uploads/civicrm/custom/";
	$base_dir = "/home/bertie/public_html/";
	$member_dir = "images/members/";
	$root_dir = "https://bec-cave.org.uk/";
	$thumb_dir = "tn/";
	$thumb_width = 174;
	$thumb_height = 205;

	// include CSS to format it properly
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/bootstrap-responsive.css">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/custom.css">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/facewall.css">';


	// handle the first row which contains the current year
	$facewall_output .= '<div class="items-row cols-4 row-'.$display_row.' row-fluid clearfix bec_year_row">';  
	$facewall_output .= '<h2>'.$club_year.'</h2></div>';
	$display_row++;
	// we need to pad the divs so it flows correctly since we've only taken up the first cell the rest are filled with blanks
	$facewall_output .= facewall_pad_grid($column, $max_column, '');
	// then reset the column to 1
	$column = 1;

	// then loop thru the rest of the records 
	for($final_index = 0; $final_index < $final_count ; $final_index++)
		{
			$row2 = $final[$final_index];

			// handle the year first
			if ($row2->club_year != $club_year)
			{
				$club_year--;

				// once again we need to pad out any missing divss in the row
				$facewall_output .= facewall_pad_grid($column, $max_column, '');
				// then place the year on the page
				$facewall_output .= '<div class="items-row cols-4 row-'.$display_row.' row-fluid clearfix bec_year_row">';  
				$facewall_output .= '<h2>'.$club_year.'</h2></div>';
				$column = 1;
				$display_row_div = 1;
				$display_row++;
			} 


			//
			// now place the output for each post 
			if ( $display_row_div == 1 ){ 
				$facewall_output .= '<div class="items-row cols-4 row-'.$display_row.' row-fluid clearfix bec_members_row">';  
				$display_row++;
				$display_row_div++;
			} 
      
			$facewall_output .= '<div class="span2">';    
		        $facewall_output .= '<div class="item column-'.$column.'" itemprop="blogPost" itemscope="" itemtype="http://schema.org/BlogPosting">';
			$facewall_output .= '<div class="page-header">';

			// fix posts which are too long for the div
			$display_post = $row2->post ;
			switch ($display_post) {
				case 'Belfry Bulletin Editor':
					$display_post = 'BB Editor';
					break;
				case 'Floating Committee Member':
					$display_post = 'Floating';
					break;
			}

			$facewall_output .= '<h5 itemprop="name" max-width=250px>'.$display_post.'</h5>';
			
			// and then the name
			$facewall_output .= '       '.$row2->display_name;
			$start_time = strtotime($row2->start_date);
			$end_time = strtotime($row2->end_date);

			// deal with people joing or leaving part way thru a year
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

 			$facewall_output .= '</div>';
			$facewall_output .= '<div class="pull-left item-image">';
			$facewall_output .= '<a>';

			
			// now place their picture on the page
			if ($row2->image_URL)
				{
				if (!strpos($row2->image_URL,"photo=")){
					$image = substr($row2->image_URL,strrpos($row2->image_URL,"/")+1);
				}else{
					$image = substr($row2->image_URL,strpos($row2->image_URL,"photo=")+6);
				}
				// check for existance of correct thumbnail, if one doesn't exist then create it
			 	facewall_create_thumbnail($base_dir, $image_dir, $thumb_dir, $thumb_width, $image);
					$facewall_output .= '<img src=\''.$root_dir.$image_dir.$thumb_dir.$thumb_width.'/tn_'.$image.'\' height="205" width="174" alt="'.$row2->display_name.'">';
				}
			else	{
				$facewall_output .= '<img src=\wp\wp-content\plugins\facewall\img\unknown.jpg height="205" width="174" alt=\'No Picture\'>';
				}
			// cleanup the divs and increment counters

			$facewall_output .= '</a>';
			$facewall_output .= '</div>';
			$facewall_output .= '</div>';
			$facewall_output .= '</div>';
	  		if ( $column < $max_column ){
				$column++;
			} 
			else {
				$column = 1;
		  		$display_row_div = 1;
		  		$facewall_output .= '</div>';  
			}
		// and loop back around
		}


	// finally clean up the remaining divs
	$facewall_output .= facewall_pad_grid($column, $max_column,'');
	$facewall_output .= '</div>';
	$facewall_output .= '</br>Number of records ' . $final_index ;
	
	return  $facewall_output;
	
}

function facewall_pad_grid($column, $max_column, $html_output)
{
	while ($column <= $max_column) 	{
		if ($column == 1) 
		{
			break;
		}
		$html_output .= '<div class="span2">';    
	        $html_output .= '<div class="item column-'.$column.'" itemprop="blogPost" itemscope="" itemtype="http://schema.org/BlogPosting">';
		$html_output .= '<div class="page-header">';
		$html_output .= '</div>';
		$html_output .= '</div>';
		$html_output .= '</div>';
		$column++;
	} 
	// and then if we are at the end of the columns reset it to the first div again
	if ($column == 6) {
		$html_output .= '</div>';
	}
	return $html_output;
}


function build_officer_historical_table()
{
	//gather all the data from the various tables
	global $wpdb;
	$facewall_output = '';
	$query=("SELECT SQL_CALC_FOUND_ROWS 
			civicrm_contact.id  as cv_contact_id , 
			civicrm_contact.display_name  as cv_display_name , 
			civicrm_contact.image_URL as cv_image_URL ,
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
				$insert = ("INSERT INTO civicrm_BEC_historical_officer_membership (contact_id, display_name, post, club_year, join_date, start_date, end_date, weight, image_URL)
					VALUES ('$row->cv_contact_id', '$row->cv_display_name', '$row->cv_post', '$mem_club_year', '$row->cv_join_date', '$row->cv_start_date', '$row->cv_end_date', '$row->cv_weight', '$row->cv_image_URL')");
				$insert_result = $wpdb->get_results($insert);
				
			}
	}
	$msg = "build_officer_historical_table - completed";
	return $msg;
}

//))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))))

function build_BEC_historical_summary_table()
{
	//gather all the data from the various tables
	
	global $wpdb;
	//  use mtype to filter out BCA and discounts
	$query=("SELECT cust.Membership_Number AS mem_num, c.id AS contact_id, c.first_name, c.nick_name, c.last_name, CONCAT(c.first_name, ' ', c.last_name) AS display_name, c.image_URL, mtype.name, mstatus.name AS m_status, mem.id, mem.source,
		YEAR(mem.start_date) AS 'start', 
		YEAR(mem.end_date) AS 'end'
		from civicrm_contact AS c
		LEFT JOIN custom_value_1_BEC 		AS cust 	ON c.id = cust.entity_id
		LEFT JOIN civicrm_membership 		AS mem  	ON c.id = mem.contact_id AND mem.membership_type_id < 19
		LEFT JOIN civicrm_membership_type 	AS mtype 	ON mem.membership_type_id = mtype.id
		LEFT JOIN civicrm_membership_status 	AS mstatus 	ON mem.status_id = mstatus.id
		WHERE mtype.id IN (1,2,3,4,5,6,7,18) AND status_id <90 AND is_test = 0  AND cust.Membership_Number < 5000
		ORDER BY cust.Membership_Number DESC, mtype.id
		");
	//  inject the data back into a new table adding a row for every club year of membership.
	$result = $wpdb->get_results($query);
	if (!$result) 	{
		$facewall_output .= '<h2>'.$fw_title.'</h2>';
		$facewall_output .= 'no members found matching this search';  
		return $facewall_output;
	}

	// setup some variables
        $column = 1;
	$max_column = 5;
	$display_row = 1;
	$display_row_div = 1;
	$image_dir = "wp/wp-content/uploads/civicrm/custom/";
	$base_dir = "/home/bertie/public_html/";
	$member_dir = "images/members/";
	$root_dir = "https://bec-cave.org.uk/";
	$thumb_dir = "tn/";
	$thumb_width = 174;
	$thumb_height = 205;
	$result_count = count($result);

	// We are going to store the output in a new table
	// but first we are going to empty it
	$truncate = $wpdb->get_results("TRUNCATE TABLE civicrm_BEC_historical_summary");
	//---------------------------------------------------------------------------------
	//now loop through the results and inject the data into a new table
	for($result_index = 0; $result_index < $result_count ; $result_index++)
		{
			$row  = $result[$result_index];
			if ($result_index != $result_count-1){
				$row2 = $result[$result_index+1];
			}
			if (!isset($memberships)){
				$memberships = [];	//set the array variable otherwise it is flagged as unset variable in php logs. 
			}

			// handle the year first
			if ($result_index != $result_count-1){
				if ($row->mem_num == $row2->mem_num){
					$memberships = membership_details($row, $memberships);
					continue;
					} 
			}
			$memberships = membership_details($row, $memberships);
			$memberships = consolidateArray($memberships);


			// fix the details of the image_URL
			if ($row->image_URL)
				{
				if (!strpos($row->image_URL,"photo=")){
					$image = substr($row->image_URL,strrpos($row->image_URL,"/")+1);
				}else{
					$image = substr($row->image_URL,strpos($row->image_URL,"photo=")+6);
				}
				// check for existance of correct thumbnail, if one doesn't exist then create it
			 	facewall_create_thumbnail($base_dir, $image_dir, $thumb_dir, $thumb_width, $image);
				$new_image_URL = 'tn_'.$image;
			}
			else{
				$new_image_URL = '';
			}


			// work out the summary info
			$summary = '';
			$i = 0;
			while($i < count($memberships))
				{
				$summary .= $memberships[$i]['title'].' ('. $memberships[$i]['start'].'-'. $memberships[$i]['end'].')'.'<br>';
				$i++;
				}	
			// clear out the membership info or it will flow to the next.
			unset($memberships);
			//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			/**
			$insert = ("INSERT INTO civicrm_BEC_historical_summary (id, contact_id, display_name, summary, image_URL)
				VALUES ('$row->mem_num','$row->contact_id', '$row->display_name', '$summary', '$new_image_URL')");
			$insert_result = $wpdb->get_results($insert);
			**/

			// Assuming $wpdb is your WordPress database object

			$insert = $wpdb->prepare(
			    "INSERT INTO civicrm_BEC_historical_summary (id, contact_id, display_name, summary, image_URL) VALUES (%d, %d, %s, %s, %s)",
			    $row->mem_num,
			    $row->contact_id,
			    $row->display_name,
			    $summary,
			    $new_image_URL
			);

			$insert_result = $wpdb->get_results($insert);


	}
	$msg = "build_BEC_historical_summary_table - completed";
	return $msg;

}

?>
