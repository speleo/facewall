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

function display_facewall_normal($fw_title, $fw_mem_status, $fw_mem_type)
{
	global $wpdb;
	$facewall_output = '';

	$query=("SELECT cust.Membership_Number, c.first_name, c.nick_name, c.last_name, CONCAT(c.last_name, c.first_name), c.image_URL, mtype.name, mstatus.name AS m_status, mem.id, cust.facewall_public_84
		from civicrm_contact AS c
		LEFT JOIN custom_value_1_BEC as cust ON c.id = cust.entity_id
		LEFT JOIN civicrm_membership AS mem  ON c.id = mem.contact_id AND mem.membership_type_id < 18
		LEFT JOIN civicrm_membership_type AS mtype ON  mem.membership_type_id = mtype.id
		LEFT JOIN civicrm_membership_status AS mstatus ON mem.status_id = mstatus.id
		WHERE mtype.id ".$fw_mem_type." AND status_id ".$fw_mem_status." AND c.is_deceased != 1
		ORDER BY last_name, first_name");

	// Prepare the query
	$result = $wpdb->get_results($query);

	if (!$result) 
	{
		$facewall_output .= '<h2>'.$fw_title.'</h2>';
		$facewall_output .= 'no members found matching this search';  
		return $facewall_output;
	}

        $column = 1;
	$max_column = 5;
	$row = 1;
	$row_div = 1;
	//$image_dir = "media/civicrm/custom/";
	$image_dir = "wp/wp-content/uploads/civicrm/custom/";
	$base_dir = "/home/bertie/public_html/";
	$member_dir = "images/members/";
	$root_dir = "https://bec-cave.org.uk/";
	$thumb_dir = "tn/";
	$thumb_width = 174;
	$thumb_height = 205;
	$result_index = 0 ;
	$result_count = count($result);

	//ob_start();
	//$joint_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/templates/incline/cache/top_compression_css.php">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/bootstrap-responsive.css">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/custom.css">';
	$facewall_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/facewall.css">';
	$facewall_output .= '<h2>'.$fw_title.' - '.$result_count.' members</h2>';

	for($result_index = 0; $result_index < $result_count ; $result_index++)
	{
		$row = $result[$result_index];
		if ( $row_div == 1 ){ 
			$facewall_output .= '<div class="items-row cols-4 row-0 row-fluid clearfix bec_members_row">';  
			$row_div++;
			} 
      
		$facewall_output .= '<div class="span2">';    
	        $facewall_output .= '<div class="item column-'.$column.'" itemprop="blogPost" itemscope="" itemtype="http://schema.org/BlogPosting">';
		$facewall_output .= '<div class="page-header">';
		$facewall_output .= '<h5 itemprop="name" max-width=250px>'.$row->first_name.' '.$row->last_name.$row->facewall_public_84.'</h4>';
 		$facewall_output .= '</div>';
		$facewall_output .= '<div class="pull-left item-image">';
		$facewall_output .= '<a>';
		if ($row->image_URL && $row->facewall_public_84 === 0) 
			{
			if (!strpos($row->image_URL,"photo=")){
				$image = substr($row->image_URL,strrpos($row->image_URL,"/")+1);
			}else{
				$image = substr($row->image_URL,strpos($row->image_URL,"photo=")+6);
			}
			// check for existance of correct thumbnail, if one doesn't exist then create it
		 	facewall_create_thumbnail($base_dir, $image_dir, $thumb_dir, $thumb_width, $image);
			$facewall_output .= '<img src=\''.$root_dir.$image_dir.$thumb_dir.$thumb_width.'/tn_'.$image.'\' height="205" width="174" alt="'.$row->first_name.'_'.$row->last_name.'">';
			}
		else	{
			$facewall_output .= '<img src=\wp\wp-content\plugins\facewall\img\unknown.jpg height="205" width="174" alt=\'No Picture\'>';
			}
		$facewall_output .= '</a>';
		$facewall_output .= '</div>';
		$facewall_output .= '</div>';
		$facewall_output .= '</div>';
	  	if ( $column < $max_column ){
			$column++;
			} 
		else {
			$column = 1;
		  	$row_div = 1;
		  	$facewall_output .= '</div>';  
			}
	}
	$facewall_output .= 'Number of members ' . $result_index ;
	//return $result;
	
	return  $facewall_output;
	
}



function display_facewall_joint_invalid($fw_title, $fw_mem_status, $fw_mem_type)
{
	// This function will identify a list of joint members which are invalid
	// for instance only one is a current member
	// 
	global $wpdb;
	$joint_output = '';

	$query=("SELECT 
			`a`.`id` 				AS `id`, 
			`near_contact_id_1`.`display_name` 	AS civicrm_contact_sort_name_a, 
			`far_contact_id_2`.`display_name` 	AS civicrm_contact_b_sort_name_b,
			`a`.`near_contact_id` 			AS `near_contact_id`,
			`a`.`far_contact_id`			AS `far_contact_id`,
			`a`.`relationship_id` 			AS `relationship_id`,
			`a`.`orientation` 			AS `orientation`,
			`a`.`start_date`			AS `start_date`,
			`a`.`end_date`				AS `end_date`,
			`a`.`is_active`				AS `is_active`,
			`a`.`relationship_type_id`		AS relationship_type_id,
			near_contact_id_1.image_URL 		AS civicrm_contact_image_URL_a,
			far_contact_id_2.image_URL 		AS civicrm_contact_b_image_URL_b
	
		FROM civicrm_relationship_cache AS a
		LEFT JOIN civicrm_contact 	AS near_contact_id_1 ON a.near_contact_id =  near_contact_id_1.id
		LEFT JOIN civicrm_contact 	AS far_contact_id_2  ON a.far_contact_id  =  far_contact_id_2.id

		WHERE ( a.relationship_type_id = 8 
			AND orientation = 'a_b' 
			AND is_active = 1
			AND (
				(end_date >= CURDATE())
		                	OR
				(end_date IS NULL)
			    )
			AND (
                            -- check if the at least one of the contacts is a current member 
			    
			    (
			    	(	
					a.near_contact_id IN (
					SELECT mem.contact_id
					FROM civicrm_membership AS mem
					WHERE mem.contact_id = a.near_contact_id
						AND mem.membership_type_id IN (1,2,3,4,5,6,7)
						AND mem.status_id = 2
						AND mem.is_override = 0
					)
       
				AND 	
					a.far_contact_id NOT IN (
					SELECT mem.contact_id
					FROM civicrm_membership AS mem
					WHERE mem.contact_id = a.far_contact_id
						AND mem.membership_type_id IN (1,2,3,4,5,6,7)
						AND mem.status_id = 2
						AND mem.is_override = 0
					)
				)
			    )
			OR
			    (

			    	(	
					a.near_contact_id NOT IN (
					SELECT mem.contact_id
					FROM civicrm_membership AS mem
					WHERE mem.contact_id = a.near_contact_id
						AND mem.membership_type_id IN (1,2,3,4,5,6,7)
						AND mem.status_id = 2
						AND mem.is_override = 0
					)
       
				AND 	
					a.far_contact_id IN (
					SELECT mem.contact_id
					FROM civicrm_membership AS mem
					WHERE mem.contact_id = a.far_contact_id
						AND mem.membership_type_id IN (1,2,3,4,5,6,7)
						AND mem.status_id = 2
						AND mem.is_override = 0
					)
				)
			    )
			)
			AND (
			    -- check that at least one of them has active joint membership
			    (
			    	(	
					a.near_contact_id IN (
					SELECT mem.contact_id
					FROM civicrm_membership AS mem
					WHERE mem.contact_id = a.near_contact_id
						AND mem.membership_type_id = 17
						AND mem.status_id = 2
						AND mem.is_override = 0
					)
       
				AND 	
					a.far_contact_id NOT IN (
					SELECT mem.contact_id
					FROM civicrm_membership AS mem
					WHERE mem.contact_id = a.far_contact_id
						AND mem.membership_type_id = 17
						AND mem.status_id = 2
						AND mem.is_override = 0
					)
				)
			    )
			OR
			    (

			    	(	
					a.near_contact_id NOT IN (
					SELECT mem.contact_id
					FROM civicrm_membership AS mem
					WHERE mem.contact_id = a.near_contact_id
						AND mem.membership_type_id = 17 
						AND mem.status_id = 2
						AND mem.is_override = 0
					)
       
				AND 	
					a.far_contact_id IN (
					SELECT mem.contact_id
					FROM civicrm_membership AS mem
					WHERE mem.contact_id = a.far_contact_id
						AND mem.membership_type_id = 17
						AND mem.status_id = 2
						AND mem.is_override = 0
					)
				)
			    )
			)

		)");  

	// Prepare the query
	$result = $wpdb->get_results($query);

	if (!$result) 
	{
		$joint_output .= '<h2>'.$fw_title.'</h2>';
		$joint_output .= 'no members found matching this search';  
		return $joint_output;
	}

        $column = 1;
	$max_column = 2;
	$row = 1;
	$row_div = 1;
	$image_dir = "wp/wp-content/uploads/civicrm/custom/";
	$base_dir = "/home/bertie/public_html/";
	$member_dir = "images/members/";
	$root_dir = "https://bec-cave.org.uk/";
	$thumb_dir = "tn/";
	$thumb_width = 174;
	$thumb_height = 205;
	$result_index = 0 ;
	$result_count = count($result);
	$member_count = 2 * $result_count;
	//$joint_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/templates/incline/cache/top_compression_css.php">';
	$joint_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/bootstrap-responsive.css">';
	$joint_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/custom.css">';
	$joint_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/facewall.css">';

	$joint_output .= '<h2>'.$fw_title.' - '.$member_count.' members</h2>';

	for($result_index = 0; $result_index < $result_count ; $result_index++)
	{
		$row = $result[$result_index];
		if ( $row_div == 1 ){ 
			$joint_output .= '<div class="items-row cols-4 row-0 row-fluid clearfix bec_members_row">';  
			$row_div++;
			} 


		$joint_output .= '<div class="span2">';    
	        $joint_output .= '<div class="item column-'.$column.'" itemprop="blogPost" itemscope="" itemtype="http://schema.org/BlogPosting">';
		$joint_output .= '<div class="page-header">';
		$joint_output .= '<h5 itemprop="name" max-width=250px>'.$row->civicrm_contact_sort_name_a.'</h4>';
 		$joint_output .= '</div>';
		$joint_output .= '<div class="pull-left item-image">';

		$memberships = get_membership_info($row->near_contact_id, 1);
		$joint_output .= '<div class="fw_middle">';
    		$joint_output .= '<div class="text">'.$memberships.'</div>';
		$joint_output .= '</div>';

		if ($row->civicrm_contact_image_URL_a)
			{
			if (!strpos($row->civicrm_contact_image_URL_a,"photo=")){
				$image = substr($row->civicrm_contact_image_URL_a,strrpos($row->civicrm_contact_image_URL_a,"/")+1);
			}else{
				$image = substr($row->civicrm_contact_image_URL_a,strpos($row->civicrm_contact_image_URL_a,"photo=")+6);
			}
			// check for existance of correct thumbnail, if one doesn't exist then create it
		 	facewall_create_thumbnail($base_dir, $image_dir, $thumb_dir, $thumb_width, $image);
			$joint_output .= '<img src=\''.$root_dir.$image_dir.$thumb_dir.$thumb_width.'/tn_'.$image.'\' class="fw_image" height="205" width="174" alt="'.$row->civicrm_contact_sort_name_a.'">';
			}
		else	{
			$joint_output .= '<img src=\wp\wp-content\plugins\facewall\img\unknown.jpg class="fw_image" height="205" width="174" alt=\'No Picture\'>';
			}

		$joint_output .= '</div>';
		$joint_output .= '</div>';
		$joint_output .= '</div>';
		//----------------------
		$joint_output .= '<div class="span2">';    
	        $joint_output .= '<div class="item column-'.$column.'" itemprop="blogPost" itemscope="" itemtype="http://schema.org/BlogPosting">';
		$joint_output .= '<div class="page-header">';
		$joint_output .= '<h5 itemprop="name" max-width=250px>'.$row->civicrm_contact_b_sort_name_b.'</h4>';
 		$joint_output .= '</div>';
		$joint_output .= '<div class="pull-left item-image">';

		$memberships = get_membership_info($row->far_contact_id, 1);
		$joint_output .= '<div class="fw_middle">';
    		$joint_output .= '<div class="text">'.$memberships.'</div>';
		$joint_output .= '</div>';

		if ($row->civicrm_contact_b_image_URL_b)
			{
			if (!strpos($row->civicrm_contact_b_image_URL_b,"photo=")){
				$image = substr($row->civicrm_contact_b_image_URL_b,strrpos($row->civicrm_contact_b_image_URL_b,"/")+1);
			}else{
				$image = substr($row->civicrm_contact_b_image_URL_b,strpos($row->civicrm_contact_b_image_URL_b,"photo=")+6);
			}
			// check for existance of correct thumbnail, if one doesn't exist then create it
		 	facewall_create_thumbnail($base_dir, $image_dir, $thumb_dir, $thumb_width, $image);
			$joint_output .= '<img src=\''.$root_dir.$image_dir.$thumb_dir.$thumb_width.'/tn_'.$image.'\' class="fw_image" height="205" width="174" alt="'.$row->civicrm_contact_b_sort_name_b.'">';
			}
		else	{
			$joint_output .= '<img src=\wp\wp-content\plugins\facewall\img\unknown.jpg class="fw_image" height="205" width="174" alt=\'No Picture\'>';
			}
		//$joint_output .= '</a>';
		$joint_output .= '</div>';
		$joint_output .= '</div>';
		$joint_output .= '</div>';

		$column++;
	  	$row_div = 1;
	  	$joint_output .= '</div>';  
			
	}
	$joint_output .= 'Number of members ' . $member_count ;
	return $joint_output;

}

function get_membership_info($mem_id, $show_status){
	$params = array(
       			'contact_id'    =>  $mem_id,
		       	//'status_id.is_current' => TRUE,
       			'membership_type_ID' => '1'
			   );
	$detail = '';   	   
	try {
       		if ( ! function_exists( 'civicrm_initialize' ) ) { return; }
       		civicrm_initialize();
	        $result = civicrm_api3('membership', 'get', $params);
	} catch (CiviCRM_API3_Exception $e) {
	       // handle error here
		$errorMessage = $e->getMessage();
       		$errorCode = $e->getErrorCode();
       		$errorData = $e->getExtraParams();
       		return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
    	}
	foreach ($result[values] as $membership_id => $mem_id)
	{
		$mem_num_lookup = $mem_id[membership_type_id];
		$mem_name = civicrm_api3('MembershipType', 'get', ['return' => ["name"], 'id' => $mem_num_lookup, 'sequential' => 1]);
		$mem_status = civicrm_api3('MembershipStatus', 'get', ['return' => ["name"], 'id' => $mem_id[status_id],'sequential' => 1]);
		$startdate = date("y",strtotime($mem_id[start_date]));
		$enddate = date("y",strtotime($mem_id[end_date]));

		$detail .= $mem_name[values][0][name]." (".$startdate."-".$enddate.") ";
		if ($show_status == 1) {
			$detail .= $mem_status[values][0][name]."<br />";
		} else {
			$detail .= "<br />";

		};
	}
	return $detail;
}

// This is the good working copy of the code - might need some tweaking
function display_facewall_joint($fw_title, $fw_mem_status, $fw_mem_type)
{
	global $wpdb;
	$joint_output = '';
	rebuild_RelationshipCache();

	$query=("SELECT 
			`a`.`id` 				AS `id`, 
			`near_contact_id_1`.`display_name` 	AS civicrm_contact_sort_name_a, 
			`far_contact_id_2`.`display_name` 	AS civicrm_contact_b_sort_name_b,
			`a`.`near_contact_id` 			AS `near_contact_id`,
			`a`.`far_contact_id`			AS `far_contact_id`,
			`a`.`relationship_id` 			AS `relationship_id`,
			`a`.`orientation` 			AS `orientation`,
			near_contact_id_1.image_URL 		AS civicrm_contact_image_URL_a,
			far_contact_id_2.image_URL 		AS civicrm_contact_b_image_URL_b,
			near_hist.summary			AS near_summary,
			far_hist.summary			AS far_summary
	
		FROM civicrm_relationship_cache AS a
		LEFT JOIN civicrm_BEC_historical_summary 	AS near_hist 		ON a.near_contact_id  = near_hist.contact_id
		LEFT JOIN civicrm_BEC_historical_summary 	AS far_hist  		ON a.far_contact_id  = far_hist.contact_id
		LEFT JOIN civicrm_contact 			AS near_contact_id_1 	ON a.near_contact_id =  near_contact_id_1.id
		LEFT JOIN civicrm_contact 			AS far_contact_id_2  	ON a.far_contact_id  =  far_contact_id_2.id

		WHERE ( relationship_type_id = 8 
			AND orientation = 'a_b' 
			AND is_active = 1

			AND a.near_contact_id IN (
				SELECT mem.contact_id
				FROM civicrm_membership AS mem
				WHERE mem.contact_id = a.near_contact_id
					AND mem.membership_type_id = 1
					AND mem.status_id IN (1,2,3)
					AND mem.is_override = 0
				)
       
		       	AND a.far_contact_id IN (
				SELECT mem.contact_id
				FROM civicrm_membership AS mem
				WHERE mem.contact_id = a.far_contact_id
					AND mem.membership_type_id = 1
					AND mem.status_id IN (1,2,3)
				AND mem.is_override = 0
				)
		)");  

	// Prepare the query
	$result = $wpdb->get_results($query);

	if (!$result) 
	{
		$joint_output .= '<h2>'.$fw_title.'</h2>';
		$joint_output .= 'no members found matching this search';  
		return $joint_output;
	}

        $column = 1;
	$max_column = 2;
	$row = 1;
	$row_div = 1;
	//$image_dir = "media/civicrm/custom/";
	$image_dir = "wp/wp-content/uploads/civicrm/custom/";
	$base_dir = "/home/bertie/public_html/";
	$member_dir = "images/members/";
	$root_dir = "https://bec-cave.org.uk/";
	$thumb_dir = "tn/";
	$thumb_width = 174;
	$thumb_height = 205;
	$result_index = 0 ;
	$result_count = count($result);
	$member_count = 2 * $result_count;
	//$joint_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/templates/incline/cache/top_compression_css.php">';
	$joint_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/bootstrap-responsive.css">';
	$joint_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/custom.css">';
	$joint_output .= '<link rel="stylesheet" href="https://bec-cave.org.uk/wp/wp-content/plugins/facewall/css/facewall.css">';

	$joint_output .= '<h2>'.$fw_title.' - '.$member_count.' members</h2>';

	for($result_index = 0; $result_index < $result_count ; $result_index++)
	{
		$row = $result[$result_index];
		if ( $row_div == 1 ){ 
			$joint_output .= '<div class="items-row cols-4 row-0 row-fluid clearfix bec_members_row">';  
			$row_div++;
			} 
      
		$joint_output .= '<div class="span2">';    
	        $joint_output .= '<div class="item column-'.$column.'" itemprop="blogPost" itemscope="" itemtype="http://schema.org/BlogPosting">';
		$joint_output .= '<div class="page-header">';
		//$joint_output .= '<h5 itemprop="name" max-width=250px>'.$row->first_name.' '.$row->last_name.'</h4>';
		$joint_output .= '<h5 itemprop="name" max-width=250px>'.$row->civicrm_contact_sort_name_a.'</h4>';
 		$joint_output .= '</div>';
		$joint_output .= '<div class="pull-left item-image">';

		//$memberships = get_membership_info($row->near_contact_id, 0);
		$joint_output .= '<div class="fw_middle">';
		//$joint_output .= '<div class="text">'.$memberships.'</div>';
    		$joint_output .= '<div class="text">'.$row->near_summary.'</div>';
		$joint_output .= '</div>';


		//$joint_output .= '<a>';
		if ($row->civicrm_contact_image_URL_a)
			{
			if (!strpos($row->civicrm_contact_image_URL_a,"photo=")){
				$image = substr($row->civicrm_contact_image_URL_a,strrpos($row->civicrm_contact_image_URL_a,"/")+1);
			}else{
				$image = substr($row->civicrm_contact_image_URL_a,strpos($row->civicrm_contact_image_URL_a,"photo=")+6);
			}
			// check for existance of correct thumbnail, if one doesn't exist then create it
		 	facewall_create_thumbnail($base_dir, $image_dir, $thumb_dir, $thumb_width, $image);
			$joint_output .= '<img src=\''.$root_dir.$image_dir.$thumb_dir.$thumb_width.'/tn_'.$image.'\' class="fw_image" height="205" width="174" alt="'.$row->civicrm_contact_sort_name_a.'">';
			}
		else	{
			$joint_output .= '<img src=\images\members\unknown.jpg class="fw_image" height="205" width="174" alt=\'No Picture\'>';
			}
		//$joint_output .= '</a>';
		$joint_output .= '</div>';
		$joint_output .= '</div>';
		$joint_output .= '</div>';
		//----------------------
		$joint_output .= '<div class="span2">';    
	        $joint_output .= '<div class="item column-'.$column.'" itemprop="blogPost" itemscope="" itemtype="http://schema.org/BlogPosting">';
		$joint_output .= '<div class="page-header">';
		$joint_output .= '<h5 itemprop="name" max-width=250px>'.$row->civicrm_contact_b_sort_name_b.'</h4>';
 		$joint_output .= '</div>';
		$joint_output .= '<div class="pull-left item-image">';

		//$memberships = get_membership_info($row->far_contact_id, 0);
		$joint_output .= '<div class="fw_middle">';
    		$joint_output .= '<div class="text">'.$row->far_summary.'</div>';
		$joint_output .= '</div>';

		//$joint_output .= '<a>';
		if ($row->civicrm_contact_b_image_URL_b)
			{
			if (!strpos($row->civicrm_contact_b_image_URL_b,"photo=")){
				$image = substr($row->civicrm_contact_b_image_URL_b,strrpos($row->civicrm_contact_b_image_URL_b,"/")+1);
			}else{
				$image = substr($row->civicrm_contact_b_image_URL_b,strpos($row->civicrm_contact_b_image_URL_b,"photo=")+6);
			}
			// check for existance of correct thumbnail, if one doesn't exist then create it
		 	facewall_create_thumbnail($base_dir, $image_dir, $thumb_dir, $thumb_width, $image);
			$joint_output .= '<img src=\''.$root_dir.$image_dir.$thumb_dir.$thumb_width.'/tn_'.$image.'\' class="fw_image" height="205" width="174" alt="'.$row->civicrm_contact_b_sort_name_b.'">';
			}
		else	{
			$joint_output .= '<img src=\images\members\unknown.jpg class="fw_image" height="205" width="174" alt=\'No Picture\'>';
			}
		//$joint_output .= '</a>';
		$joint_output .= '</div>';
		$joint_output .= '</div>';
		$joint_output .= '</div>';

		$column++;
	  	$row_div = 1;
	  	$joint_output .= '</div>';  
			
	}
	$joint_output .= 'Number of members ' . $member_count ;
	return $joint_output;

}


function facewall_create_thumbnail($base_dir, $image_dir, $thumb_dir, $thumb_width, $image)
	{
	if (
	    (!file_exists($base_dir.$image_dir.$thumb_dir.$thumb_width.'/tn_'.$image)) 
	or  (!file_exists($base_dir.$image_dir.$thumb_dir.'full_scale'.'/tn_'.$image))
	) 
		{
		// ---------------------------------------
		// Setup some parameters - such as the size and where
		$name     = "/home/bertie/public_html/wp/wp-content/uploads/civicrm/custom/".$image ;
		$filename = "/home/bertie/public_html/wp/wp-content/uploads/civicrm/custom/tn/174/tn_".$image ;
		$filename_orig_scale = "/home/bertie/public_html/wp/wp-content/uploads/civicrm/custom/tn/full_scale/tn_".$image ;
		$thumb_w = 174;
		$thumb_h = 205 ;

		if (!file_exists($name)) {
 		   return "File does not exist.";
		}
		//-------------------------------------------
		// $joint_output .= $image.' not found';

		$extension = pathinfo($name, PATHINFO_EXTENSION);

		if (preg_match("/jpg|jpeg/i",$extension)){$src_img=imagecreatefromjpeg($name);}
		if (preg_match("/png/i"     ,$extension)){$src_img=imagecreatefrompng($name);}
		
		if ($src_img) {
     		   $old_x = imagesx($src_img);
		   $old_y = imagesy($src_img);
		} else {
		   return "Failed to create image from JPEG.";
		}

		$dst_img=ImageCreateTrueColor($thumb_w,$thumb_h);

		$width_new = $old_y * $thumb_w / $thumb_h;
	    	$height_new = $old_x * $thumb_h / $thumb_w;
		//if the new width is greater than the actual width of the image, then the height is too large and the rest cut off, or vice versa
		if($width_new > $old_x)
			{
	        		//cut point by height
	        		$h_point = (($old_y - $height_new) / 2);
		        	//copy image
				imagecopyresampled($dst_img, $src_img,            0, 0, 0, $h_point, $thumb_w, $thumb_h, $old_x, $height_new);
				$dst_img_orig_scale = ImageCreateTrueColor($old_x, $height_new);
				imagecopyresampled($dst_img_orig_scale, $src_img, 0, 0, 0, $h_point, $old_x, $height_new, $old_x, $height_new);
			}else{
				//cut point by width
				$w_point = (($old_x - $width_new) / 2);
				imagecopyresampled($dst_img, $src_img,            0, 0, $w_point, 0, $thumb_w, $thumb_h, $width_new, $old_y);
				$dst_img_orig_scale = ImageCreateTrueColor($width_new, $old_y);
				imagecopyresampled($dst_img_orig_scale, $src_img, 0, 0, $w_point, 0, $width_new, $old_y, $width_new, $old_y);
			}

		if (preg_match("/png/",$system[1]))
			{
				imagepng($dst_img,$filename);
				imagepng($dst_img_orig_scale,$filename_orig_scale);
			} else {
				imagejpeg($dst_img,$filename); 
				imagejpeg($dst_img_orig_scale,$filename_orig_scale);
			}
		imagedestroy($dst_img); 
		imagedestroy($src_img); 
		}
	}

//public static function populateRelationshipCache(CRM_Queue_TaskContext $ctx, $startId, $endId) {
    	// NOTE: We duplicate CRM_Contact_BAO_RelationshipCache::$mappings in case
	// the schema evolves over multiple releases.

	//This was lifted from the file 
	//  /public_html/wp/wp-content/plugins/civicrm/civicrm/CRM/Upgrade/Incremental/php
	//  FiveTwentyNine.php

function rebuild_RelationshipCache(){
	// We are going to store the output in a new table
	// but first we are going to empty it
	global $wpdb;
	$truncate = $wpdb->get_results("TRUNCATE TABLE civicrm_relationship_cache");
	
    $mappings = [
      'a_b' => [
        'relationship_id' => 'rel.id',
        'relationship_type_id' => 'rel.relationship_type_id',
        'orientation' => '"a_b"',
        'near_contact_id' => 'rel.contact_id_a',
        'near_relation' => 'reltype.name_a_b',
        'far_contact_id' => 'rel.contact_id_b',
        'far_relation' => 'reltype.name_b_a',
        'start_date' => 'rel.start_date',
        'end_date' => 'rel.end_date',
        'is_active' => 'rel.is_active',
      ],
      'b_a' => [
        'relationship_id' => 'rel.id',
        'relationship_type_id' => 'rel.relationship_type_id',
        'orientation' => '"b_a"',
        'near_contact_id' => 'rel.contact_id_b',
        'near_relation' => 'reltype.name_b_a',
        'far_contact_id' => 'rel.contact_id_a',
        'far_relation' => 'reltype.name_a_b',
        'start_date' => 'rel.start_date',
        'end_date' => 'rel.end_date',
        'is_active' => 'rel.is_active',
      ],
    ];
    $keyFields = ['relationship_id', 'orientation'];

    foreach ($mappings as $mapping) {
      $query = CRM_Utils_SQL_Select::from('civicrm_relationship rel')
        ->join('reltype', 'INNER JOIN civicrm_relationship_type reltype ON rel.relationship_type_id = reltype.id')
	->syncInto('civicrm_relationship_cache', $keyFields, $mapping)
        ->where('rel.end_date IS NULL OR rel.end_date > CURDATE()');

      $query->execute();
    }

    return TRUE;
  }

?>
