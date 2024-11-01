<?php 

/**
 * Wordpress Checkout 
 * Plugin URI: http://wordpress-checkout.com/
 *
 */
/**
 *  Init action
 *   
 */
add_action('init', 'wpckt_init');

function wpckt_init() {

    load_plugin_textdomain('wpckt', false, '/wordpress-checkout/languages');

    global $wpckt_options;

    if (!isset($wpckt_options)) {
        $wpckt_options = wpckt_get_options();
    }

    wpckt_cart_init();
    wpckt_currency_init();
    wpckt_orders_init();
    wpckt_load_presentation();
}

function wpckt_cart_init() {

    global $wpckt_cart;
    global $wpckt_session;

    if (!isset($wpckt_session)) {
        $wpckt_session = new WPCKT_Sessions();
        $wpckt_cart = new WPCKT_CART(wpckt_generate_invoice_id());
        if ($wpckt_session->sessionExists('wpckt')) {
            $wpckt_cart = $wpckt_session->getSession('wpckt');
        } else {
            $wpckt_session->setSession('wpckt', $wpckt_cart);
            if (!$wpckt_session->sessionExists('wpckt')) {
                echo "<span class='wpckt_warning'>" . __("Your php configuration doesn't support session please enable it to allow the plugin work properly", "wpckt") . "</span>";
            }
        }
    } else {
        $wpckt_cart = $wpckt_session->getSession('wpckt');
    }
}

/**
 *  Set up your cart before rendering
 *   Is the cart empty?
 *   Is Checkout page defined?
 *   Is the order completed?
 *  @param array
 *  @return boolean
 */
function wpckt_cart_ready($output) {

    global $wpckt_cart;
    global $wpckt_orders;
    global $wpckt_options;

    $final_output = $output;
    $checkout_page = wpckt_checkout_url();

    $order_status = $wpckt_orders->get_order_status($wpckt_cart->id);

    // Order is not complete	
    if ($order_status !== false && $order_status == "Completed") {
        wpckt_cart_reset();
    }

    // Cart is not empty
    if ($wpckt_cart->itemscount == 0) {
        return apply_filters("wpckt_cart_empty", $final_output);
    }


    // Checkout page exist
    if ($checkout_page === false) {
        return apply_filters("wpckt_checkout_url_error", $final_output);
    }

    if ($final_output == $output) {
        return true;
    }

    return $final_output;
}

/**
 *  Add product to cart action
 *  @param array
 *  @return boolean
 */
add_action("wpckt_action_add", "wpckt_add", 10, 1);

function wpckt_add($atts) {

    global $wpckt_cart;
    global $wpckt_session;
    global $wpckt_options;
    global $wpckt_currency;

    extract($_REQUEST);
    extract($atts);

    // Validate item form products
    /* Removing nonce to prevent cache issues
      /*if(!isset($item_id) ||
      !isset($_POST['_wpnonce_'.$item_id]) ||
      !wp_verify_nonce($_POST['_wpnonce_'.$item_id],'wpckt_add_product') )
      {
      return true;
      } */

    // Avoid action
    if (!isset($section) ||
            ( $section != "product" && $section != "checkout" ) ||
            ( $section == "product" &&
            ( $item_name != $name || $wpckt_currency->sanitize($item_price) != $wpckt_currency->sanitize($price) ) )
    ) {
        return true;
    }


    // Adding default qty
    if (!isset($item_qty))
        $item_qty = 1;

    $wpckt_cart->add_item($item_id, $item_name, $item_url, $item_qty, $wpckt_currency->sanitize($item_price), ((isset($item_shipping) && $wpckt_options['flat_shipping']) ? $wpckt_currency->sanitize($item_shipping) : 0), ((isset($item_weight) && $wpckt_options['use_weight']) ? $item_weight : 0));

    /* Default
      $itemid,
      $name = FALSE,
      $url = FALSE,
      $qty=1,
      $price = FALSE,
      $shipping = 0,
      $weight = 0
     */

    $wpckt_session->setSession('wpckt', $wpckt_cart);

    if ($section == "product") {
        add_filter('wpckt_section_product', 'wpckt_product_added', 9, 2);
    }
}

/**
 *  Delete product from cart action
 *  @param array
 *  @return boolean
 */
add_action("wpckt_action_delete", "wpckt_delete", 10, 1);

function wpckt_delete($atts) {

    global $wpckt_cart;
    global $wpckt_session;

    extract($_REQUEST);

    if (isset($_REQUEST['_wpnonce_cart_delete']) &&
            wp_verify_nonce($_REQUEST['_wpnonce_cart_delete'], 'wpckt_cart_delete')) {

        $wpckt_cart->del_item($item_id);
        $wpckt_session->setSession('wpckt', $wpckt_cart);
    }
}

/**
 *  Update products on cart action
 *  @param array
 *  @return boolean
 */
add_action("wpckt_action_update", "wpckt_update", 10, 1);

function wpckt_update($atts) {

    global $wpckt_cart;
    global $wpckt_session;

    extract($_REQUEST);

    if (isset($_REQUEST['_wpnonce_cart_update']) &&
            wp_verify_nonce($_REQUEST['_wpnonce_cart_update'], 'wpckt_cart_update')) {

        foreach ($items as $i => $id) {
            $wpckt_cart->edit_item($id, $qty[$i]);
        }
        $wpckt_session->setSession('wpckt', $wpckt_cart);
    }
}

/**
 *  Proceed to checkout action
 *  @param array
 *  @return boolean
 */
add_action("wpckt_action_proceed", "wpckt_proceed", 10, 1);

function wpckt_proceed($atts) {

    // verrify if order exist and save it as "STARTED"
    global $wpckt_cart;
    global $wpckt_orders;
    global $wpckt_currency;

    if ($wpckt_cart->itemscount > 0 &&
            isset($_REQUEST['_wpnonce_cart_proceed']) &&
            wp_verify_nonce($_REQUEST['_wpnonce_cart_proceed'], 'wpckt_cart_proceed')) {


        if (!$wpckt_orders->is_installed()) {
            $wpckt_orders->install();
        }
		
        $order = array(
            'order_id' => $wpckt_cart->id,
            'process_time' => date('Y-m-d H:i:s'),
            'items' => implode(",<br />", $wpckt_cart->itemsname),
            'quantity' => implode(",<br />", $wpckt_cart->itemsqt),
            'sub_total' => $wpckt_currency->format_full($wpckt_cart->total_plus),
            'status' => 'Started'
        );


        if ($wpckt_orders->get_order('order_id', $wpckt_cart->id) === false) {

            $wpckt_orders->insert($order);
			
        } else {

            $wpckt_orders->update($order, 'order_id', $wpckt_cart->id);

        }
    }
}

/**
 *  PayPal notify action
 *  @param array
 *  @return boolean
 */
add_action("wpckt_action_paypal_notify", "wpckt_paypal_notify", 10, 1);

function wpckt_paypal_notify($atts) {

	/**
	 * Uncomment to enable logs
	 *
	 *     
	  ini_set('log_errors', true);
	  ini_set('error_log', WPCKT_PLUGIN_DIR.'/logs/ipn_logs.log');     
	  error_log("verifying");
	  */
	 
    global $wpckt_orders;
    global $wpckt_options;
	global $wpckt_currency;

    $checkout_page = wpckt_checkout_url();

    // Checkout page exist
    if ($checkout_page === false) {
        return false;
    }

    require_once WPCKT_PLUGIN_DIR . '/includes/classes/class.wpckt-ipnlistener.php';

    $listener = new IpnListener();

    if (!$wpckt_options["live"]) {
        $listener->use_sandbox = true;
    }

    if (!function_exists('curl_version')) {
        $listener->use_curl = false;
    }

    /*
      The processIpn() method will encode the POST variables sent by PayPal and then
      POST them back to the PayPal server. An exception will be thrown if there is
      a fatal error (cannot connect, your server is not configured properly, etc.).
      Use a try/catch block to catch these fatal errors and log to the ipn_errors.log
      file we setup at the top of this file.

      The processIpn() method will send the raw data on 'php://input' to PayPal. You
      can optionally pass the data to processIpn() yourself:
      $verified = $listener->processIpn($my_post_data);
     */

    try {
        $listener->requirePostMethod();
        $verified = $listener->processIpn();
    } catch (Exception $e) {
        echo $e->getMessage();
        exit(0);
    }


    /*
      The processIpn() method returned true if the IPN was "VERIFIED" and false if it
      was "INVALID".
     */
    if ($verified) {
        /*
          Once you have a verified IPN you need to do a few more checks on the POST
          fields--typically against data you stored in your database during when the
          end user made a purchase (such as in the "success" page on a web payments
          standard button). The fields PayPal recommends checking are:

          1. Check the $_POST['payment_status'] is "Completed"
          2. Check that $_POST['txn_id'] has not been previously processed
          3. Check that $_POST['receiver_email'] is your Primary PayPal email
          4. Check that $_POST['payment_amount'] and $_POST['payment_currency']
          are correct

          Since implementations on this varies, I will leave these checks out of this
          example and just send an email using the getTextReport() method to get all
          of the details about the IPN.
         */

        $order_id = $_POST['invoice'];
        $order = $wpckt_orders->get_order('order_id', $order_id);


        if ($order !== false &&
                $_POST['receiver_email'] == $wpckt_options['paypal_email'] &&
                $_POST['payment_amount'] == $order['gross']) {


				// Update order
				$order_new = array(
					'status' => $_POST['payment_status'],
					'verify_sign' => $_POST['verify_sign'],
					'gateway' => 'PayPal',
					'firstname' => $_POST['first_name'],
					'lastname' => $_POST['last_name'],
					'email' => $_POST['payer_email'],
					'address' => $_POST['address_name'] . "<br />" .
					$_POST['address_street'] . "<br />" .
					$_POST['address_city'] . "<br />" .
					$_POST['address_state'] . ", " .
					$_POST['address_zip'] . "<br />" .
					$_POST['address_country'],
					'gross' => $wpckt_currency->format_full($_POST['mc_gross']),
					'shipping' => $wpckt_currency->format_full($_POST['mc_shipping']), 
					'tax' => $wpckt_currency->format_full($_POST['tax']),
					'details' => $listener->getOrderDetails()
				);
	
				$order = wpckt_array_merge($order[0], $order_new);		

				$wpckt_orders->update($order, 'order_id', $order_id);
				
				
				// Send Email
				if ($_POST['payment_status'] == "Completed") {
	
					// Send email to Admin
					wp_mail(get_option('admin_email'), get_option('blogname') . " - " . __('Order Completed', 'wpckt'), $listener->getTextReport());
	
					add_filter( 'wp_mail_content_type', 'wpckt_set_html_content_type' );
					// Send Confirmation email to user
					wp_mail( $_POST['payer_email'], 
							 esc_attr($wpckt_options['thankyou_email_subject']), 
							 wpckt_replace_message_attributes($order, $wpckt_options['thankyou_email_body']));
							 
					// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
                    remove_filter( 'wp_mail_content_type', 'wpckt_set_html_content_type' );		 
				}				
				
        }
		
    } else {
		
        /*
          An Invalid IPN *may* be caused by a fraudulent transaction attempt. It's
          a good idea to have a developer or sys admin manually investigate any
          invalid IPN.
         */

         wp_mail(get_option('admin_email'), get_option('blogname') . __('Invalid IPN', 'wpckt'), $listener->getTextReport());
    }
}

/**
 *  Get Checkout URL
 *  @return string
 */
function wpckt_checkout_url() {

    global $wpckt_options;

    $checkout_page = $wpckt_options['checkout_page'];

    if (isset($checkout_page) && !empty($checkout_page)) {
        return get_permalink($checkout_page);
    } else {
        return false;
    }
}

/**
 *  Generate invoice id to identify order and cart
 *  @return string
 */
function wpckt_generate_invoice_id() {
    return time() . "-" . rand(0, 9999);
}

/**
 *  Reset Cart
 *  @return boolean
 */
function wpckt_cart_reset() {

    global $wpckt_cart;
    global $wpckt_session;

    $wpckt_cart->empty_cart();
    $wpckt_cart->id = wpckt_generate_invoice_id();
    $wpckt_session->setSession('wpckt', $wpckt_cart);
}

?>