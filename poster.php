<?php
/**
 * Create a Poster of all BEC members
 * 
 * Henry Bennett 2023 July
 * Version 1.2
 * 
 * for documentation see https://www.rubydoc.info/gems/rfpdf/1.17.1/TCPDF
 *
 * x = horizontal
 * y = vertical
 *
 */

function create_poster($fw_title, $sheet_size, $variant)
{
	global $wpdb;
	
	// Include the main TCPDF library (search for installation path).
	//require_once('/home/bertie/public_html/tmp/Facewall/TCPDF-main/examples/tcpdf_include.php');
	//require_once('/home/bertie/public_html/wp/wp-content/plugins/facewall/tcpdf/examples/tcpdf_include.php');
	require_once __DIR__ . '/includes/pdf.php';
	if ( ! facewall_load_tcpdf() ) {
    		wp_die( 'PDF generation is unavailable (TCPDF not loaded).' );
	}

	// create new PDF document
	// $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	$pdf = new TCPDF('L', PDF_UNIT, $sheet_size, true, 'UTF-8', false);

	// set document information
	$pdf->setCreator(PDF_CREATOR);
	$pdf->setAuthor('Henry Bennett');
	$pdf->setTitle('BEC Facewall');
	$pdf->setSubject('Everything to Excess ' .date("Y"));
	$pdf->setKeywords('Bristol, Exploration, Club, Facewall, poster');

	$pdf->SetPrintHeader(false);
	$pdf->SetPrintFooter(false);
	$pdf->SetAutoPageBreak(0);
	$pdf->SetFillColor(0,0,0);

	// set some language-dependent strings (optional)
	if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		require_once(dirname(__FILE__).'/lang/eng.php');
		$pdf->setLanguageArray($l);
	}

// -------------------------------------------------------------------

	/**
	 *  We want to print A1 but with a printers bleed.  
	 *  So we're going to set the page size to RA1 in the config file
	 *
	'A0'   => array( 1683.780,  2383.937), // = (  841 x 1189  ) mm  = ( 33.11 x 46.8 ) in
	'A1'   => array( 1683.780,  2383.937), // = (  594 x 841  ) mm  = ( 23.39 x 33.11 ) in
	'RA1'  => array( 1729.134,  2437.795), // = (  610 x 860  ) mm  = ( 24.02 x 33.86 ) in
	'SRA1' => array( 1814.173,  2551.181), // = (  640 x 900  ) mm  = ( 25.20 x 35.43 ) in
	**/

	// add a page
	$pdf->AddPage();

	// get page measures from format name
	// and convert it from units to mm (*25.4/72)
	$pf = TCPDF_STATIC::getPageSizeFromFormat($sheet_size);
	$page_w = max($pf[0],$pf[1]) *(25.4/72);   // x
	$page_h = min($pf[0],$pf[1]) *(25.4/72);   // y
	$bleed_x = 0;
	$bleed_y = 0;


	// find the base sheet size
	if (strpos($sheet_size, "SR") !== false || strpos($sheet_size, "R") !== false) {
		$base_size = substr($sheet_size,-2);
	} else {
		$base_size = $sheet_size;
	}

	// and populate the base sheet dimensions
	$base_page_w = max(TCPDF_STATIC::$page_formats[$base_size][0], TCPDF_STATIC::$page_formats[$base_size][1]) *(25.4/72);
	$base_page_h = min(TCPDF_STATIC::$page_formats[$base_size][0], TCPDF_STATIC::$page_formats[$base_size][1]) *(25.4/72);


	// for SRx and Rx sheets add crop marks and color bars
	If (strpos($sheet_size, "SR") !== false || strpos($sheet_size, "R") !== false) {
		$bleed_x = ($page_w - $base_page_w)/2;
		$bleed_y = ($page_h - $base_page_h)/2;
		// Crop Marks
		$pdf->cropMark($bleed_x, $bleed_y, 10, 10, 'TL');
		$pdf->cropMark($page_w - $bleed_x, $bleed_y, 10, 10, 'TR');
		$pdf->cropMark($bleed_x, $page_h - $bleed_y, 10, 10, 'BL');
		$pdf->cropMark($page_w - $bleed_x, $page_h - $bleed_y, 10, 10, 'BR');

		// color registration bars
		$color_reg_bar_1_x = ($base_page_w / 2) - 100 ;
		$color_reg_bar_1_y = $base_page_h + (2 * $bleed_y) - 7;
		$pdf->colorRegistrationBar($color_reg_bar_1_x , $color_reg_bar_1_y , 200, 5, false, true, 'A,W,R,G,B,C,M,Y,K,ALL');
		$color_reg_bar_2_x = $base_page_w + (2 * $bleed_x) - 7;
		$color_reg_bar_2_y = ($base_page_h / 2) -100 ;
		$pdf->colorRegistrationBar($color_reg_bar_2_x , $color_reg_bar_2_y , 5, 200, false, false, 'A,W,R,G,B,C,M,Y,K,ALL');
	}

	// Handling the Title & Footer 
	//
	// find top left corner of header cell
	$header_text = 'Bristol Exploration Club Membership ' .date("Y");
	$footer_text = 'Henry Bennett ' .date("F Y");

	$header_w = 0.8 * $page_w;
	$header_x = ($page_w - $header_w)/2;
	$title_position_w = $page_w * 0.8; 


	// place the footer
	// 
	// 	set font for footer (same as names)
	// 	size is dynamically set based on the size of the page ($page_h/49.5)
	$pdf->SetFont('helvetica', 'B', $base_page_h/49.5);

	// 	get the width of the footer
	$footer_width = $pdf->GetStringWidth($footer_text);

	//
	// 	position the XY for the footer

	$pdf->SetXY($page_w -$bleed_x -PDF_MARGIN_RIGHT -$footer_width, 
		    $base_page_h -($base_page_h/66) + $bleed_y);

	// 	write the footer
	// 	Cell(w, h = 0, txt = '', border = 0, ln = 0, align = '', fill = 0, link = nil, stretch = 0, ignore_min_height = false, calign = 'T', valign = 'M')
	$pdf->Cell(0, 0, $footer_text, 0, $ln=0, 'L', 0, '', 0, false, 'A', 'B');

	
	// place the header 
	//
	// 	set font
	// 	size is dynamically set based on the size of the page ($page_h/11)
	$pdf->SetFont('helvetica', 'B', $base_page_h/11);
	$pdf->SetXY($header_x, 5 + $bleed_y);

	// 	Put the header text on center
	// 	Cell(w, h = 0, txt = '', border = 0, ln = 0, align = '', fill = 0, link = nil, stretch = 0, ignore_min_height = false, calign = 'T', valign = 'M')
	// 	$pdf->Cell($page_w * 0.8, 10, $header_text, 0, $ln=0, 'C', 0, '', 0, false, 'T', 'C');
	$pdf->Cell($title_position_w, 10, $header_text, 0, $ln=2, 'C', 0, '', 0, false, 'T', 'C');

	// get the co-ordinates 
	$last_y = $pdf->getY();


	// some debug stuff
	//
	/**
	$fp = fopen('test_array_output.txt', 'w');
		fwrite($fp, 'Test file for Facewall Poster.php'.PHP_EOL);
		$test = print_r(TCPDF_STATIC::$page_formats[$sheet_size], true);

		// get page measures from format name
		//$pf = TCPDF_STATIC::getPageSizeFromFormat($format);
		$pf = TCPDF_STATIC::getPageSizeFromFormat($sheet_size);

		if (strpos($sheet_size, "SR") !== false || strpos($sheet_size, "R") !== false) {
			// Action to be performed when the sheet size contains "SR" or "R"
			//echo "Sheet size contains either 'SR' or 'R'.";
			$base_size = substr($sheet_size,-2);
			//fwrite($fp, 'Base size = ' . $base_size . PHP_EOL);
		} else {
			// Action to be performed when the sheet size does not contain "SR" or "R"
			echo "Sheet size does not contain 'SR' or 'R'.";
			$base_size = $sheet_size;
		}
		fwrite($fp, 'Base size = ' . $base_size . PHP_EOL);

		fwrite($fp, 'Width = ' . $page_w . PHP_EOL);
		fwrite($fp, 'Height = ' . $page_h . PHP_EOL);
		fwrite($fp, 'Base Width  = ' . $base_page_w . PHP_EOL);
		fwrite($fp, 'Base Height  = ' . $base_page_h . PHP_EOL);

		fwrite($fp, 'Last Y = ' . $lasty . PHP_EOL);
		fwrite($fp, '$format = ' . $format . PHP_EOL);
		fwrite($fp, '$sheet_size = ' .  $sheet_size .  PHP_EOL);
		fwrite($fp, '$test = ' . $test . PHP_EOL);
	fclose($fp);
	**/


	// set font for names 
	// size is dynamically set based on the size of the page ($page_h/49.5)
	$pdf->SetFont('helvetica', 'B', $base_page_h/49.5);
	

	// set JPEG quality
	$pdf->setJPEGQuality(100);

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	$query=("SELECT cust.Membership_Number, c.first_name, c.nick_name, c.last_name, CONCAT(c.last_name, c.first_name), c.image_URL, mtype.name, mstatus.name AS m_status, mem.id, c.id,officer.contact_id, officer.post, officer.weight
		from civicrm_contact AS c
		LEFT JOIN custom_value_1_BEC as cust ON c.id = cust.entity_id
		LEFT JOIN civicrm_membership AS mem  ON c.id = mem.contact_id AND mem.membership_type_id < 18
		LEFT JOIN civicrm_membership_type AS mtype ON  mem.membership_type_id = mtype.id
		LEFT JOIN civicrm_membership_status AS mstatus ON mem.status_id = mstatus.id
		LEFT OUTER JOIN (
		    SELECT contact_id, post, weight, club_year,
		           ROW_NUMBER() OVER (PARTITION BY contact_id ORDER BY weight ASC) as rn
			   FROM civicrm_BEC_historical_officer_membership 
			   WHERE club_year = ".date("Y")."
			) as officer ON c.id = officer.contact_id AND officer.rn = 1
		WHERE mtype.id <9 AND status_id <4 AND c.is_deceased != 1 
		ORDER BY last_name, first_name");



	// line below will place the committee members first
	// ORDER BY -officer.weight DESC, last_name, first_name");


	// Prepare the query
	$result = $wpdb->get_results($query);

	$image_dir = "wp/wp-content/uploads/civicrm/custom/";
	$base_dir = "/home/bertie/public_html/";
	$member_dir = "images/members/";
	$root_dir = "https://bec-cave.org.uk/";
	$thumb_dir = "tn/";
	$thumb_width_dir = "full_scale";
	$thumb_width = 175;
	$thumb_height = 205;
	$result_count = count($result);

//  ---------------- BEC customisations ------------------------------
//  https://www.rubydoc.info/gems/rfpdf/1.17.1/TCPDF:Image
//  Image( filename, left, top, width, height, type, link, align, resize, dpi, align, ismask, imgmask, border, fitbox, hidden, fitonpage)


	$members = count($result);
	$rows = 10;
	$columns = ceil($members / $rows);

	$x = 15 + $bleed_x;
	//$y = PDF_MARGIN_TOP+ $bleed_y;
	$y = $pdf->GetY(); // Get the current Y position

	// the actual images are sized 204 * 175 px
	// calculate the width of the images rather than hardcoding
	$min_spacer_width = 5;

	// to do this we set the minimum space size.
	$image_width = ceil(($base_page_w -PDF_MARGIN_LEFT -PDF_MARGIN_RIGHT - (($columns-1) * $min_spacer_width)) /$columns);
	// then extrapolate the height based on the aspect ratio of stored images
	$image_height = $image_width * (204/175);
	// fine tune the spacing
	$spacer_x = ($base_page_w - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT - ($image_width * $columns))/($columns - 1);
	$spacer_y = ($page_h -$bleed_y - $last_y - PDF_MARGIN_BOTTOM - 5 - ($image_height * $rows))/($rows - 1);


	$index = 0 ;	//used to loop through the SQL results
	// loop through the rows
	for ($r = 0 ; $r < $rows ; ++$r){
		// loop through the columns
		for ($c = 0 ; $c < $columns ; ++$c){
			//  $fitbox[1] = $vertical_alignments[$j];   	// this was throwing php errors and was removed 19/01/24
									// Fitbox is a variable for Method: TCPDF#Image
									// If true scale image dimensions proportionally to fit within the (:w, :h) box.
			$fitbox = 1;
			// check if we haven't reached the end of the members
			if ($index == $members) {
				break;
			}
			$row = $result[$index];
			$index++;
			// check if we haven't reached the end of the members
			if ($row == NULL) {
				break;
			}
			
			if ($row->image_URL)
				{
				if (!strpos($row->image_URL,"photo=")){
					$image = substr($row->image_URL,strrpos($row->image_URL,"/")+1);
				}else{
					$image = substr($row->image_URL,strpos($row->image_URL,"photo=")+6);
				}
				//$mugshot = $base_dir.$image_dir.$image;
				$mugshot = $base_dir.$image_dir.$thumb_dir.$thumb_width_dir.'/tn_'.$image;

				}
			else	{
				$mugshot = $base_dir.'wp/wp-content/plugins/facewall/img/unknown.jpg';
				}
				
		
			// Place the image on the page
			//
			// $pdf->Image('/home/bertie/public_html/tmp/Facewall/TCPDF-main/examples/images/image_demo.jpg', $x, $y, $image_width, $image_height, 'JPG', '', '', false, 300, '', false, false, 0, $fitbox, false, false);
			//
			//$pdf->Image($mugshot, $x, $y, $image_width, $image_height, 'jpg', '', '', false, 300, '', false, false, 0, $fitbox, false, false);
			$pdf->Image($mugshot, $x, $y, $image_width, $image_height, '', '', '', false, 300, '', false, false, 0, $fitbox, false, false);

			if ($row->post){
				// Store current cordinates
				$post_y = $pdf->getY();
				$post_x = $pdf->getX();

				// set the text font to White
				$pdf->SetTextColor(255,255,255);
				$y_pos = $y+$image_height;
				$pdf->SetXY($x, $y_pos);

				$show_post = $row->post;
				switch ($show_post) {
					case 'Belfry Bulletin Editor':
						$show_post = 'BB Editor';
						break;
					case 'Floating Committee Member':
						$show_post = 'Floating';
						break;
					case 'Membership Secretary':
						$show_post = 'Membership';
						break;

				}

				// write the post details over the mugshot
				// we scale the width by 99% to fix the differince between image() and cell() methods 
				$pdf->Cell($image_width*.99, 2,$show_post, 0, $ln=0, 'C', 1, '', 0, false, 'B', 'B');
				//$pdf->Cell($image_width, 2,$show_post, 0, $ln=0, 'C', 1, '', 0, false, 'A', 'B');

				//rest Font to Black
				$pdf->SetTextColor(0,0,0);

				//reset cordinates
				$pdf->SetXY($post_x, $post_y);

			}
			else {
				$pdf->SetTextColor(0,0,0);
			}			
			// Set the xy pointer and land the text on center of the cell, this allos for overlap if too wide.
			// Cell(w, h = 0, txt = '', border = 0, ln = 0, align = '', fill = 0, link = nil, stretch = 0, ignore_min_height = false, calign = 'T', valign = 'M') 
			//
			$y_pos = $y+$image_height+0.5;
			$pdf->SetXY($x, $y_pos);
			$pdf->Cell($image_width, 2,$row->first_name.' '.$row->last_name, 0, $ln=0, 'C', 0, '', 0, false, 'A', 'B');

			// $x += 45.352941176; // new column
			$x += $image_width + $spacer_x ;
		}
	$y += $image_height+ $spacer_y ; // new row 55
	$x = 15 + $bleed_x ; //start at the left again
	}

	if (($members % $columns) != 0 ){
		$pdf->SetXY($x + (($members % $columns)*( $image_width + $spacer_x)) ,
			$y_pos - ($image_height/2) -2 );
		$pdf->Cell($image_width, 2,$result_count . " members", 0, $ln=0, 'C', 0, '', 0, false, 'A', 'B');
	}


	// Clean any content of the output buffer
	// This kills all output which we collected with $facewall_output. 
	// If you want to show this to screen then disable this temporarily however using it kills the PDF output
	ob_end_clean();

	//Close and output PDF document
	/**
	  I : send the file inline to the browser (default). The plug-in is used if available. The name given by name is used when one selects the "Save as" option on the link generating the PDF.
	  D : send to the browser and force a file download with the name given by name.
	  F : save to a local server file with the name given by name.
	  S : return the document as a string (name is ignored).
	  FI : equivalent to F + I option
	  FD : equivalent to F + D option
	  E : return the document as base64 mime multi-part email attachment (RFC 2045)
	  **/

	$pdf->Output('/home/bertie/public_html/wp/wp-content/plugins/facewall/poster_output/'.date("Y").'-BEC_Membership_Poster_'.$sheet_size.'.pdf', 'FD');
	//$pdf->Output('/home/bertie/public_html/wp/wp-content/plugins/facewall/poster_output/'.$sheet_size.'-BEC_Membership_Poster_'.$sheet_size.'-'.date("Y").'.pdf', 'FD');
	//$pdf->Output('/home/bertie/public_html/tmp/Facewall/TCPDF-main/examples/'.$sheet_size.'-BEC_Membership_Poster_'.$sheet_size.'-'.date("Y").'.pdf', 'FD');



	return;
}

?>
