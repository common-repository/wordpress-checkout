<?php 
/**
 * Wordpress Checkout 
 * Plugin URI: http://wordpress-checkout.com/
 *
*/

/**
 *  Load presentation based on options selection
 *  
 */ 
  
function wpckt_load_presentation (){
	
	  global $wpckt_presentation;	  
	  global $wpckt_presentation_list;
	  global $wpckt_options;	  
	  
	  $wpckt_presentation = $wpckt_options["presentation"];
	  
	  $wpckt_presentation_list = wpckt_get_units("presentation");
	  
	  if ($wpckt_presentation_list && in_array($wpckt_presentation, $wpckt_presentation_list) ) {
		  $fn_file = WPCKT_PLUGIN_DIR."/presentation/".$wpckt_presentation."/functions.php";
		  if (file_exists($fn_file)){
				  require_once ($fn_file);
		  }		  
	  }
}

/**
 *  Currency initializacion
 *  
 */ 
 
function wpckt_currency_init (){
	
    global $wpckt_currency;
	global $wpckt_options;				
	
	if (!isset($wpckt_currency)){
		$wpckt_currency = new WPCKT_currency($wpckt_options['currency']);
	}
}

/**
 *  Orders initializacion
 *  
 */ 

function wpckt_orders_init(){
	
	global $wpckt_options;
	global $wpckt_orders;	

	if (!isset($wpckt_orders)){
		$wpckt_orders = new WPCHKT_Orders_Engine();
	}
	
	if ($wpckt_orders->is_installed()){
		
		if ( version_compare($wpckt_options['version'], WPCKT_VERSION_CHECK, '<' ) ) {

			if(!$wpckt_orders->install()) {
				_e("There was a problem updating the Wordpress Checkout Database","wpckt");
			}
		}
	
	}
	
}


/**
 *  Get the plugin options based on version and reset status
 *  @param boolean
 *  @return array
 */ 
 
function wpckt_get_options ($default = false) {
   
    global $wpckt_options;					   

	$wpckt_default = array(
							'currency' => 'USD',
							'checkout_page' => '',
							'thankyou_page' => '',
							'thankyou_msg' => __('Thank you! We will start processing your order immediately.','wpckt'), 
							'thankyou_email_subject' => get_bloginfo('name').__(' - Confirmation.','wpckt'), 
							'thankyou_email_body' => __('Thank you! This email is to confirm that your transaction has been completed. We will start processing your order immediately.','wpckt'),
							'paypal_email' => '',
							'live' => 0,
							'presentation' => 'default',
							'flat_shipping' => 1,
							'use_weight' => 0,
							'weight_unit' => 'lbs',
							'notes' => '',
							'version' => WPCKT_VERSION_CURRENT
							);
	
	// Restore default
	if ($default)  {
	  update_option('wpckt_op', $wpckt_default);
	  $wpckt_options = $wpckt_default;
	  return $wpckt_default;
	} 
	
    $options = get_option('wpckt_op');    
   
	if (isset($options) && 
	   !empty($options) && 
	   !empty($options['version']) ) {
		   if ( version_compare($options['version'], WPCKT_VERSION_CHECK, '<' ) ){
		      return wpckt_array_merge($wpckt_default, $options);
		   } else {
			  return $options;
		   }
	} else {
				
		 return $wpckt_default;
	}
}

/**
 *  The Sortcode
 *  @param array
 *  @return html
 */  
 
add_shortcode('wp_checkout', 'wpckt_sc');

function wpckt_sc($atts) {
	
	global $post;
	
	$wpckt_default_sc = array(
	                    'id' => "",
						'section' => 'checkout', //default options: 'product','checkout','cart', 'small_cart'
						'name' => $post->post_title,
						'price' => "",
						'shipping' => '',
						'weight' => '',
						'quantity' => 0,
						'direct_chekout' =>0,
						'button_name' => __("Add to Cart",'wpckt'),
						'width' => '',
						'width_unit' => 'px',
						'align' => ''
						);			
		
	
	$final_atts = shortcode_atts($wpckt_default_sc, $atts);	
	
    global $wpckt_cart;	
	
	if(count($wpckt_cart->items) > 0 && isset($_REQUEST) && isset($_REQUEST['section'])){
		$final_atts['section'] = $_REQUEST['section'];
	}
	
	// To handle actions
	if(isset($_REQUEST) && isset($_REQUEST['action'])){	

		do_action( "wpckt_action_".$_REQUEST['action'], $final_atts );		
  
	}
	
	// To handle sections
	$output = "";
	$output = apply_filters("wpckt_section_".$final_atts['section'], $output, $final_atts);
	
	if ($output == ""){
		$output = apply_filters("wpckt_section_no_presentation", $output, $final_atts);
	}
	
	return $output;
	
}

/* Filter to add comment for invalid sections

add_filter('wpckt_section_no_presentation','wpckt_section_no_presentation_added', 10, 2);

function wpckt_section_no_presentation_added ($output, $atts){	
  return $output."<div class='wpckt_warning'>".__("There is not presentation associate to the","wpckt")." <strong>".$atts['section']."</strong> ".__(" section.","wpckt")."</div>"; 
}
*/


/**
 *  Verify if Checkout page includes the shortcode with checkout section and insert it
 *  this happend after saving plugin options
 *  
 */  

add_action( "wpckt_admin_after_update", "wpckt_verify_checkout_shortcode", 10, 0 );

function wpckt_verify_checkout_shortcode(){
	
	// For WP versions before 3.6
	if (!function_exists ('has_shortcode')){
		return;
	}
	
	global $wpckt_options;
	
	$post_id = $wpckt_options['checkout_page'];
	
	if ($post_id == '') {
		return;
	}
	
	$post = get_post( $post_id );	
	
	
	if ( ! has_shortcode( $post->post_content, 'wp_checkout' ) ) {

		wpckt_insert_checkout_shortcode($post);
		return;
		
	} else {		

		$sections = wpckt_shortcode_section ($post->post_content);
		
		if (count($sections) > 0 && !in_array('checkout', $sections)){
			
			wpckt_insert_checkout_shortcode($post);
			
		}
		
	}

}


/**
 *  Find shortcode sections on string
 *  @param string
 *  @return array
 */  
 
function wpckt_shortcode_section ($str){
	
	    $sections = array();
	
	  	if ( preg_match_all( '/' . get_shortcode_regex() . '/s', $str, $matches, PREG_SET_ORDER ) ) {
			
	  		foreach ( $matches as $shortcode ) {
				
	  			if ( 'wp_checkout' === $shortcode[2] ) {
										
     				  $data = shortcode_parse_atts( $shortcode[3] );
					  
  
					  if ( is_array($data) ){				  

						  if (array_key_exists('section',$data)){
							  array_push ($sections, $data['section']);
						  } else {
							  // default just in case there is no section
							  array_push ($sections, 'checkout');
						  }
						  
					  }
	  			}
	  		}
			
			return $sections;
	  	}
		
		return $sections; 
	    
}


/**
 *  Insert Checkout shortcode after content
 *  @param object
 *  @return void
 */
 
function wpckt_insert_checkout_shortcode ($post){
	
	$checkout_shortcode = '[wp_checkout section="checkout"]';
	
	$post->post_content = $post->post_content."\n".$checkout_shortcode;
	
	wp_update_post($post);
	
}


/**
 *  Load presentation
 *  @param string
 *  @return array
 */ 
 
function wpckt_get_units($type) {
	
	$units_root = WPCKT_PLUGIN_DIR."/".$type;
	$units = array();
	
	$units_dir = @ opendir( $units_root);

	if ( $units_dir ) {
		while (($file = readdir( $units_dir ) ) !== false ) {
			if ( substr($file, 0, 1) == '.' )
				continue;							
			if ( is_dir( $units_root.'/'.$file ) ) {				
				$units_subdir = @ opendir( $units_root.'/'.$file );				
				if ( $units_subdir ) {
					while (($subfile = readdir( $units_subdir ) ) !== false ) {
						if ( $subfile == 'functions.php' ) {
							$units[] = "$file"; 
							break;
					    }
					}
					closedir( $units_subdir );
				}
			} 
		}
		closedir( $units_dir );
	}
	
	if ( $units > 0 ) return $units;
	else return false; 			
	
}

/**
 *  Render message order elements
 *  
 *  @return string
 */
function wpckt_get_messages_order_attributes() {
    
	global $wpckt_orders;
	$output = __("You can include the following elements related to the buyer order into the message: ");
	$output .= "<storng>";
	foreach($wpckt_orders->get_attributes() as $attributes ){ 
	
		 $output .= "[".$attributes['name']."], ";		
	}
	$output = rtrim($output, ", ").".";
	$output .= "</storng>";
	return $output;	
	
}


/**
 *  Render message order elements before for email and thank you.
 *  @param string
 *  @return string
 */
 
function wpckt_replace_message_attributes($order, $content) {
    
	global $wpckt_orders;	
	
	foreach($wpckt_orders->get_attributes() as $attributes ){ 
	
		 $content = str_replace ("[".$attributes['name']."]", $order[$attributes['name']],$content);		
	}
	
	return $content;	
	
}

/**
 *  Define content type for emails body.
 *  @param string
 *  @return string
 */

function wpckt_set_html_content_type() {

	return 'text/html';
}


/**
 *  Merge arrays 
 *  @param arrays
 *  @return array
 */
 
function wpckt_array_merge($default, $new, $reset = false ) {
	$out = $default;
	foreach($default as $key => $value) {
		if ( array_key_exists($key, $new) ){
			$out[$key] = $new[$key];
		} else if ($reset){
		    $out[$key] = 0;
		} else {
			$out[$key] = $default[$key];
		}
	}
	return $out;
}


/**
 *  Get weight unit
 *  @param string
 *  @return string
 */
 
function wpckt_get_weight_unit($unt) {
	
	$units = array("lbs" => __("lbs","wpckt"),
	               "kgs" => __("kgs","wpckt"));
				   
	if (array_key_exists ($unt, $units)){
		return $units[$unt];
	} else {
		return false;
	}    
}

/**
 *  Echo string or array 
 *  @param string or array
 *  @return html
 */
 
function wpckt_echo( $value, $pref="", $suf="" ) {
	
	echo "<pre>$pref ";
	if (is_array($value) || is_object($value) ){	   
	  print_r($value);	
	} else {
		echo $value;
	}
	echo " $suf</pre>";
}

?>