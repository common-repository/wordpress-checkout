<?php
/**
 * Wordpress Checkout 
 * Presentation: Default 
 * Plugin URI: http://wordpress-checkout.com/
 *
*/

// Enqueue scripts
add_action( 'wp_enqueue_scripts', 'wpckt_enqueue_scripts' );

function wpckt_enqueue_scripts(){
	wp_enqueue_style( 'wpckt_style', WPCKT_PLUGIN_URL.'/presentation/default/styles/wpckt-default-style.css');
}


/**
 * Rendering product
 * @shortcode output, shortcode attrs
 * @return string
 */

add_filter('wpckt_section_product','wpckt_render_product',10,2);

function wpckt_render_product($output,$atts){
	
	global $wpckt_currency;
	
	extract($atts);
    
	if (isset($price) && !empty($price)){
	
		global $post;
		
		if(!$post) {
			return true;
		}
			
		$checkout_page = wpckt_checkout_url();
		
		if (!$checkout_page){
			return apply_filters("wpckt_checkout_url_error", $output);
		}
	
		$url =  get_page_link($post->ID);
		
		// Defining item ID: ( post id - price - name )
		if (!isset($id) || empty($id)){
			$post_id = $post->ID;
		    $item_id = $post_id."-".sanitize_title($price)."-".sanitize_title($name);
		} else {
			$item_id = $id;
		}
		
        $item_id = str_replace('-', '_', $item_id);
	
	    
		$final_output = $output.'<div class="wpckt_product ';
				
		if (isset($align)){	
		        switch ($align) {
					case "left":		
				      $final_output .= 'alignleft';
					  break;
					case "right":		
				      $final_output .= 'alignright';
					  break;
					case "center":		
				      $final_output .= 'aligncenter';
					  break;					  					  
					;
				}
			}
		
		$final_output .= '" ';
		
		if (isset($width) && isset($width_unit) && $width != ''){
			$final_output .= 'style="width:'.$width.$width_unit.'"';
		}
		
		$final_output .= '>';
		
		$post_url = $checkout_page;
		if(!$direct_chekout) {
		$post_url = "#"; 
		}
		
		$final_output .= 
		'<form method="post" id="wpckt_product_'.$item_id.'" action="'.$post_url.'">';		
		
		/* Removing nonce to prevent cache issues		
		   $final_output .= wp_nonce_field( 'wpckt_add_product', '_wpnonce_'.$item_id, false, false);
		*/
		
		$final_output .=
		 '<input type="hidden" value="add" name="action" />
		  <input type="hidden" class="item_url" value="'.$url.'" name="item_url"  />
		  <input type="hidden" class="item_id" value="'.$item_id.'" name="item_id"  />
		  <input type="hidden" class="item_name" value="'.$name.'" name="item_name" />
		  <input type="hidden" class="item_name" value="'.$weight.'" name="item_weight" />
		  <input type="hidden" class="item_price" value="'.$wpckt_currency->sanitize($price).'" name="item_price">
		  <input type="hidden" class="item_shipping" value="'.$wpckt_currency->sanitize($shipping).'" name="item_shipping" />
		
		<table border="0" cellspacing="0" cellpadding="5">
		  <tbody>';
		  
		
		  
		if ($name != $post->post_title){ 
		$final_output .= '<tr>
							<td colspan="2"><strong>'.$name.'</strong></td>
						 </tr>';			 
		} 
		 
		$final_output .= 
		'<tr>
		 <td>'. __("Price","wpckt").'</td>
		 <td>'.$wpckt_currency->format_full($price).'</td>
		 </tr>';
		 
		if ($quantity){ 	 
		$final_output .= 
		'<tr>
		<td>'.__("Qty","wpckt").'</td>
		<td><select class="wpckt_item_qty" name="item_qty">
		<option value="1">1</option>
		<option value="2">2</option>
		<option value="3">3</option>
		<option value="4">4</option>
		<option value="5">5</option>
		<option value="6">6</option>
		<option value="7">7</option>
		<option value="8">8</option>
		<option value="9">9</option>
		<option value="10">10</option>
		<option value="11">11</option>
		<option value="12">12</option>
		<option value="13">13</option>
		<option value="14">14</option>
		<option value="15">15</option>
		<option value="16">16</option>
		<option value="17">17</option>
		<option value="18">18</option>
		<option value="19">19</option>
		<option value="20">20</option>
		</select></td>
		</tr>';
		} 
		$final_output .= 
		'<tr>
		<td colspan="2" align="center">
		  <input type="submit" class="add" name="add" value="'.$button_name.'" />
		</td>
		</tr>
		</tbody>
		</table>
		</form>
		</div>';
		
	} else {
		$final_output .= "<span class='wpckt_warning'>".__("Please include the price of the product.","wpckt")."</span>";
	}
	
	return $final_output;
	
}

/**
 * Rendering shopping cart
 * @shortcode output, shortcode attrs
 * @return string
 */
 
add_filter('wpckt_section_checkout','wpckt_render_checkout',5,2);

function wpckt_render_checkout($output, $atts){

    $cart_ready = wpckt_cart_ready($output);
	
	if ($cart_ready !== true){
		return $cart_ready;
	}

	global $wpckt_cart;
	global $wpckt_currency;
			
	$final_output = $output.'
	<form action="" method="post">';		
		
	$final_output .= wp_nonce_field( 'wpckt_cart_update', '_wpnonce_cart_update', false, false);
	
	$final_output .=
	 '<input type="hidden" value="update" name="action" />
	  <table>
		<thead>
		  <tr>				  
			<th>'.__("Item","wpckt").'</th>
			<th>'.__("Qty","wpckt").'</th>
			<th>'.__("Price","wpckt").'</th>
		  </tr>
		</thead>
		<tbody>';

	$i=0;
	foreach($wpckt_cart->get_content() as $item) {
	/*
	$item keys :id, qty, price, shipping, name, url, subtotal
	*/		

	   $final_output .= '
		<tr>
		  <td><a href="'.$item["url"].'"><strong>'.$item["name"].'</strong></a></td>
		  <td>				  
			<input type="hidden" name="items[]" value="'.$item["id"].'" />
			<input type="text" class="wpckt_item_qty" name="qty[]" size="2" value="'.$item["qty"].'" />
		  </td>				
		  <td>'.$wpckt_currency->format_full($item["price"]).'&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="'.wp_nonce_url( add_query_arg(array("action"=>"delete","item_id"=>$item['id'])),'wpckt_cart_delete','_wpnonce_cart_delete' ).'" class ="remove_item">'.
			  __("Remove","wpckt").
		   '</a>
		 </td>
		</tr>';
		$i ++;
	}
	
	
	$final_output .= '
		  <tr>
			<td colspan="2">'.__("Sub-total","wpckt").'</td>
			<td>'.$wpckt_currency->format_full($wpckt_cart->total).'</td>
		  </tr>
		  <tr>
			<td colspan="3"><input type="submit" name="update" value="'.__("Update Cart","wpckt").'" /></td>
		  </tr>
		<tbody>
	  </table>
	  <hr /> 
	  <p>
		<a href="'.wp_nonce_url(add_query_arg(array("section"=>"verify", "action"=>"proceed")),'wpckt_cart_proceed','_wpnonce_cart_proceed' ).'">'.
		__("Proceed to Checkout >","wpckt").'
		</a> 
	   </p>
	</form>';
	 
	 return $final_output;

}



/**
 * Rendering verification
 * @shortcode output, shortcode attrs
 * @return string
 */

add_filter('wpckt_section_verify','wpckt_render_verify',5,2);


function wpckt_render_verify($output, $atts){
	
    $cart_ready = wpckt_cart_ready($output);
	
	if ($cart_ready !== true){
		return $cart_ready;
	}

	global $wpckt_cart;
	global $wpckt_currency;
	global $wpckt_options;				
	
	extract($wpckt_options);
		
	$checkout_page = wpckt_checkout_url();
    
	$final_output = $output.'
    <h2>'.
      __("Verify your Order","wpckt").'
    </h2>
    <form action="'.($live ? "https://www.paypal.com/cgi-bin/webscr" : "https://www.sandbox.paypal.com/cgi-bin/webscr").'" method="post" name="wpckt_verify" >
    
      <input type="hidden" name="charset" value="utf-8">
      
      <input type="hidden" name="return" value="'.add_query_arg(array("section"=>"confirm"),$checkout_page).'"  />   
      <input type="hidden" name="cancel_return" value="'.$checkout_page.'" /> 
      <input type="hidden" name="notify_url" value="'.add_query_arg(array("action"=>"paypal_notify"),$checkout_page).'" />     
      
      <input type="hidden" name="business" value="'.$paypal_email.'"  />       
      <input type="hidden" name="cmd" value="_cart" /> 
      <input type="hidden" name="upload" value="1" /> 
      <input type="hidden" name="currency_code" value="'.$currency.'" />
      <input type="hidden" name="address_override" value="1" />
      <input type="hidden" name="invoice" value="'.$wpckt_cart->id.'" />';
	  
	  if ($use_weight){
		  $final_output .='
		  <input type="hidden" name="weight_cart" value="'.$wpckt_cart->total_weight.'" />
		  <input type="hidden" name="weight_unit" value="'.$weight_unit.'" />';
	  }
	  
			
		  $i = 1;
		  foreach($wpckt_cart->get_content() as $item) {
			  
			  $final_output .='
			  <input type="hidden" name="item_number_'.$i.'" value="'.$item["id"].'" />       
			  <input type="hidden" name="item_name_'.$i.'" value="'.$item["name"].'" />
			  <input type="hidden" name="amount_'.$i.'" value="'.$wpckt_currency->sanitize($item["price"]).'" />';			  
			  if($flat_shipping){
				  $final_output .='
				  <input type="hidden" name="shipping_'.$i.'" value="'.$wpckt_currency->sanitize($item["qty"]*$item["shipping"]).'" />';
			  }
			  $final_output .='
			  <input type="hidden" name="quantity_'.$i.'" value="'.$item["qty"].'" />';
	  
			  $i ++;			
		  }
   
	      $final_output .= wpckt_render_order();

		  $final_output .='
		    <p><input name="send_order" type="submit" value="'.__("Process with PayPal","wpckt").'" /></p>
			<hr /> 
			<p>
			  <a href="'.$checkout_page.'">'.
			  __("< Back to Shopping Cart","wpckt").'
			  </a> 
			 </p>	
    </form>';
	

  return $final_output;
}


/**
 * Rendering confirmation
 * @shortcode output, shortcode attrs
 * @return string
 */

add_filter('wpckt_section_confirm','wpckt_render_confirm',5,2);

function wpckt_render_confirm($output, $atts){
	
	$final_output = $output.'
	<h2>'.
	  __("Confirmation","wpckt").'
	</h2>';
	
	$final_output .= wpckt_render_order();
	
	// adding js to check order status using ajax
	if ( !wp_script_is( 'wpckt_script' ) ) {
	  wp_enqueue_script( 'wpckt_script', WPCKT_PLUGIN_URL.'/presentation/default/js/wpckt.js', array( 'jquery' ) );
	  wp_localize_script( 'wpckt_script', 'wpcktSettings', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 
															      'wpckt_nonce' =>  wp_create_nonce('wpckt_nonce') ) );
    }
																
    $final_output .="<div class='wpckt_warning wpckt_verifying'><div class='wpckt-spinner' style='background:url(".WPCKT_PLUGIN_URL."/assets/images/spinner.gif) no-repeat;'></div>".__("Verifying your order status...","wpckt")."</div>";
																
	return $final_output;															
																
}

// if both logged in and not logged in users can send this AJAX request,
// add both of these actions, otherwise add only the appropriate one
add_action( 'wp_ajax_nopriv_wpckt_verify_order_status', 'wpckt_ajax_verify_order_status' );
add_action( 'wp_ajax_wpckt_verify_order_status', 'wpckt_ajax_verify_order_status' );

function wpckt_ajax_verify_order_status(){

	$nonce = $_POST['wpckt_nonce'];
	
	// check to see if the submitted nonce matches with the
	// generated nonce we created earlier
	if ( !wp_verify_nonce( $nonce, 'wpckt_nonce' ) )
       die ( 'Busted!');
	
	global $wpckt_cart;
	global $wpckt_orders;
	global $wpckt_options;	
	
	$response_array = array( 
	                        "status" => "Invalid",
			                "thankyou_page" => $wpckt_options['thankyou_page'],
		                    "thankyou_msg" => $wpckt_options['thankyou_msg'],
							"invalid_msg" => __("Sorry, we are not able to verify the status of your order.","wpckt"),
		                    "not_completed_msg" => __("Thanks. Your order payment can't be verified. We will contact you as soon as we review your order.","wpckt")	
	                       );	
	
	$order_query = $wpckt_orders->get_order('order_id', $wpckt_cart->id );
	
	if ( $order_query !== false ) {
		  
	     $order = $order_query[0];
		 
		 if ( $order['status'] == "Completed" ){
		         wpckt_cart_reset();
		         $response_array['status'] = "Completed";
		         $response_array['thankyou_msg'] = wpckt_replace_message_attributes($order, $wpckt_options['thankyou_msg']);
		 } else {
			 $response_array['status'] = $order['status'];
		 }
 		
	}
	
	
	// generate the response
	$response = json_encode($response_array);

	// response output
	header( "Content-Type: application/json" );
	echo $response;


	// IMPORTANT: don't forget to "exit"
	exit;
		
}


// if both logged in and not logged in users can send this AJAX request,
// add both of these actions, otherwise add only the appropriate one
add_action( 'wp_ajax_nopriv_wpckt_get_cart_content', 'wpckt_get_cart_content' );
add_action( 'wp_ajax_wpckt_get_cart_content', 'wpckt_get_cart_content' );


function wpckt_get_cart_content(){

	//$nonce = $_POST['wpckt_nonce'];
	
	// check to see if the submitted nonce matches with the
	// generated nonce we created earlier
	/*if ( !wp_verify_nonce( $nonce, 'wpckt_nonce' ) )
       die ( 'Busted!');*/
	
	global $wpckt_cart;
	
	// generate the response
	$response = json_encode($wpckt_cart);

	// response output
	header( "Content-Type: application/json" );
	echo $response;


	// IMPORTANT: don't forget to "exit"
	exit;
		
}
	
	
/**
 * Rendering order deatils
 * @return string
 */

function wpckt_render_order(){
	
	global $wpckt_cart;
	global $wpckt_currency;
	global $wpckt_options;
	
	
	$final_output = '
	  <table>
		<thead>
		  <tr>
			<th>'.__("Item","wpckt").'</th>
			<th>'.__("Qty","wpckt").'</th>
			<th>'.__("Price","wpckt").'</th>
		  </tr>
		</thead>
		<tbody>';
  
			  foreach($wpckt_cart->get_content() as $item) {
			  /*
			  $item keys :id, qty, price, shipping, name, url, subtotal
			  */  
						
			  $final_output .='
			  <tr>				
				<td><span><strong>'.$item["name"].'</strong></span></td>
				<td><span>'.$item["qty"].'</span></td>
				<td><span>'.$wpckt_currency->format_full($item["price"]).'</span></td>
			  </tr>';
		  }
		  
		  if ($wpckt_options['flat_shipping']) { 
		  
			  $final_output .='
			  <tr>
				<td colspan="2">'.__("Shipping and handling","wpckt").'</td>
				<td>'.$wpckt_currency->format_full($wpckt_cart->total_shipping).'</td>
			  </tr>';
		  }
		  
		  $final_output .='
		  <tr>
			<td colspan="2">
			  <strong>'.__("Sub-Total","wpckt").'</strong>
			</td>
			<td><strong>'.$wpckt_currency->format_full($wpckt_cart->total_plus).'</strong></td>
		  </tr>		  
		</tbody>
		
	  </table>';

      return $final_output;
}

/**
 * Warning filters
 */
 
add_filter('wpckt_cart_empty','wpckt_warning_empty_cart',10,1);
function wpckt_warning_empty_cart($output){
	return $output."<div class='wpckt_warning'>".__("There are no items in your cart","wpckt")."</div>";
}

add_filter('wpckt_checkout_url_error','wpckt_warning_no_checkout_page',10,1);
function wpckt_warning_no_checkout_page($output){
	return  $output."<div class='wpckt_warning'>".__("The Checkout page is not defined. Please, include the url of your checkout and processing page on the plugin settings.","wpckt")."</div>";
}


function wpckt_product_added($output,$atts){
	global $wpckt_cart;
    global $wpckt_session;
	global $wpckt_currency;
	
	extract($_REQUEST);
	extract($atts);
    
	// Avoid action
	if ( $item_name == $name && $wpckt_currency->sanitize($item_price) == $wpckt_currency->sanitize($price)) {
        return $output."<div class='wpckt_warning'>".__("Product has been added to","wpckt")." <a href='".wpckt_checkout_url()."'>".__("Shopping Cart","wpckt")."</a>.</div>";
	}
}

add_filter('wpckt_section_verify','wpckt_render_paypal_logo', 10, 2);
//add_filter('wpckt_section_checkout','wpckt_render_paypal_logo', 10, 2);

function wpckt_render_paypal_logo($output,$atts){
	
   return $output.'<!-- PayPal Logo --><a href="https://www.paypal.com/webapps/mpp/paypal-popup" title="How PayPal Works" onclick="javascript:window.open(\'https://www.paypal.com/webapps/mpp/paypal-popup\',\'WIPaypal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;"><img src="https://www.paypalobjects.com/webstatic/mktg/logo/AM_SbyPP_mc_vs_dc_ae.jpg" border="0" alt="PayPal Acceptance Mark"></a><!-- PayPal Logo -->';
	
}

add_filter('wpckt_section_verify','wpckt_render_sandbox_warning', 8, 2);
//add_filter('wpckt_section_checkout','wpckt_render_sandbox_warning', 8, 2);
add_filter('wpckt_section_confirm','wpckt_render_sandbox_warning', 8, 2);


function wpckt_render_sandbox_warning($output,$atts){
	
	global $wpckt_options;
	
	if (!$wpckt_options['live']){
        return $output."<div class='wpckt_warning'>".__("The payment process will be executed in test mode through the PayPal Sandbox; no real transaction will be completed.","wpckt")."</a></div>";
	} else {
		return $output;
	}
	
}

add_filter('wpckt_section_verify','wpckt_render_pre_payment_notes', 7, 3);
function wpckt_render_pre_payment_notes($output,$atts){
	
	global $wpckt_options;
	
	if (!empty ($wpckt_options['notes'])){
        return $output."<div class='wpckt_notes'>".$wpckt_options['notes']."</div>";
	} else {
		return $output;
	}	
}

?>
