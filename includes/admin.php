<?php
/**
 * Wordpress Checkout 
 * Plugin URI: http://wordpress-checkout.com/
 *
 */
 
/**
 * Admin Init
 *
 */
add_action('admin_init', 'wpckt_admin_init');

function wpckt_admin_init() {
	
    load_plugin_textdomain('wpckt', false, '/wordpress-checkout/languages');

    global $wpckt_options;

    if (!isset($wpckt_options)) {
        $wpckt_options = wpckt_get_options();
    }

    wpckt_load_presentation();
    wpckt_currency_init();
    wpckt_orders_init();
	
	add_editor_style( WPCKT_PLUGIN_URL . '/assets/styles/wpckt-admin-style.css' );

    // Enqueue admin script
    add_action('admin_enqueue_scripts', 'wpckt_admin_enqueue_scripts');

    // Ajax request to currency entered
    add_action('wp_ajax_wpckt_format', 'wpckt_format');

    // Ajax request to currency entered
    add_action('wp_ajax_wpckt_editor_insert_shortcode', 'wpckt_editor_insert_shortcode');

    // Adding WordPress plugin action links	
    add_filter('plugin_action_links_' . WPCKT_PLUGIN_FILE, 'wpckt_plugin_action_links');

    // Adding WordPress plugin meta links 
    add_filter('plugin_row_meta', 'wpckt_plugin_meta_links', 10, 2);
}

function wpckt_admin_enqueue_scripts($hook) {


    if ("post.php" == $hook || "post-new.php" == $hook || 'media-upload-popup' == $hook || "toplevel_page_wpckt_settings" == $hook || "wp-checkout_page_wpckt_orders" == $hook) {

        wp_enqueue_script('wpckt_admin_script', WPCKT_PLUGIN_URL . '/assets/js/wpckt-admin.js', array('jquery'));
        wp_enqueue_script('wpckt_editor_script', WPCKT_PLUGIN_URL . '/assets/js/wpckt-product-editor.js', array('jquery'));
        wp_localize_script('wpckt_admin_script', 'wpcktSettings', array('lang' => array('required' => esc_js( __( 'required', 'wpckt' ) ) ),
		                                                                'ajaxUrl' => admin_url('admin-ajax.php'),
                                                                        'wpckt_admin_nonce' => wp_create_nonce('wpckt_admin_nonce')));

        wp_register_style('wpckt_admin_style', WPCKT_PLUGIN_URL . '/assets/styles/wpckt-admin-style.css');
        wp_enqueue_style('wpckt_admin_style');
    } else {
        return;
    }
}

/**
 * Format currency or weight using ajax
 * @return json object with the formated amount
 */
function wpckt_format() {

    $nonce = $_POST['wpckt_admin_nonce'];

    // check to see if the submitted nonce matches with the
    // generated nonce we created earlier
    if (!wp_verify_nonce($nonce, 'wpckt_admin_nonce'))
        die('Busted!');

    $amount = $_POST['amount'];
    $types = array_map('trim', explode(" ", $_POST["types"]));
    $response = array("value" => $amount);

    foreach ($types as $type) {
        if ($type == "currency") {
            global $wpckt_currency;
            $response = array("value" => $wpckt_currency->format($amount));
            break;
        }
        if ($type == "weight") {
            $response = array("value" => number_format($amount, 2, ".", " "));
            break;
        }
    }

    // response output
    header("Content-Type: application/json");
    echo json_encode($response);

    // IMPORTANT: don't forget to "exit"
    exit;
}

/**
 * Ajax call for actions before inserting shortcode from editor
 *  
 * @return json object with true
 */
function wpckt_editor_insert_shortcode() {

    $nonce = $_POST['wpckt_admin_nonce'];

    // check to see if the submitted nonce matches with the
    // generated nonce we created earlier
    if (!wp_verify_nonce($nonce, 'wpckt_admin_nonce'))
        die('Busted!');

    do_action("wpckt_editor_before_insert_shortcode", $_POST);

    // generate the response
    $response = json_encode(array("success" => "true"));

    // response output
    header("Content-Type: application/json");
    echo $response;

    // IMPORTANT: don't forget to "exit"
    exit;
}

/**
 * Update checkout page with correspondent shortcode
 * @param $post object
 * 
 */
add_action("wpckt_editor_before_insert_shortcode", "wpckt_update_checkout_page", 10, 1);

function wpckt_update_checkout_page($post) {

    global $wpckt_options;

    $sections = wpckt_shortcode_section(stripslashes_deep($post['code']));

    if (count($sections) > 0 && in_array('checkout', $sections) && isset($post['post_id'])) {

        $wpckt_options['checkout_page'] = $post['post_id'];
        update_option('wpckt_op', $wpckt_options);
        return;
    }
}

/**
 * Editor Button
 * Add wp checkout button on the editor pannel
 * @param $admin
 * @return void
 */
add_action('media_buttons', 'wpckt_editor_buttons', 20);

function wpckt_editor_buttons($admin = true) {

    $wp_version = get_bloginfo('version');

    if (version_compare($wp_version, '3.6', '<')) {

        global $post_ID, $temp_ID;

        $media_upload_iframe_src = get_option('siteurl') . '/wp-admin/media-upload.php?post_id=$uploading_iframe_ID';

        $iframe_title = __('Wordpress Checkout', 'wpckt');

        $tab_name = "wpckt";

        echo '<a class="thickbox button" href="media-upload.php?post_id=' . $post_ID . '&amp;tab=' . $tab_name . '&amp;TB_iframe=true&amp;height=480&amp;width=680" title="' . $iframe_title . '"><span class="wpckt-cart-icon"></span> ' . __("WP Checkout", "wpckt") . '</a>';
    } else {

        echo '<a href="#" id="wpckt-button" class="button wpckt-add-product" data-editor="content" title="' . __("WP Checkout", "wpckt") . '"><span class="wpckt-cart-icon"></span> ' . __("WP Checkout", "wpckt") . '</a>';
    }
}

/**
 * Adding media tab
 *
 */
add_action('media_upload_wpckt', 'wpckt_tab_handle');

function wpckt_tab_handle() {
    return wp_iframe('wpckt_tab_process');
}

/**
 * Add tab content for editor panel
 * @param $admin
 * @return void
 */
function wpckt_tab_process($admin = true) {

    global $wpckt_currency;
    global $wpckt_options;
	
    ?>
    <div class="wpckt-editor-wrapper">
    
        <?php if (!isset($_REQUEST["section"]) || $_REQUEST["section"] != "product" ){ ?>
         
        <table>
            <tbody>                
                <tr valign="top">
                    <th scope="row" align="right" width="15%"><input name="wpckt_page" type="radio" id="wpckt_page" value="checkout" class="wpckt-section wpckt-sc" /></th>
                    <td> 
    <?php _e("Make this my <strong>Checkout Page</strong>", "wpckt") ?>
                        <p class="description"><?php echo __('In order to set up your store you need to create a checkout page by adding the wp_checkout shortcode with', 'wpckt').' section="checkout"'; ?></p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row" align="right" width="15%"><input name="wpckt_page" type="radio" id="wpckt_product" value="product" class="wpckt-section  product wpckt-sc"  checked="checked" /></th>
                    <td> 
                        <strong><?php _e("Add a Product", "wpckt") ?></strong>
                    </td>
                </tr> 

            </tbody>
        </table> 
        
        <?php } else { ?>         
           
           <input name="section" type="hidden" id="wpckt_page" value="product" class="wpckt-sc" />
           
         <?php }  ?> 
         
        
        <table class="product-table">
            <tbody>

            <tr valign="top">
                <th scope="row"><h2><?php _e("Product Details", "wpckt") ?></h2></th>
                <td></td>
            </tr>

            <tr valign="top">
                <th scope="row" align="right"><label><?php _e("Name", "wpckt") ?></label></th>
                <td><input name="name" type="text" id="wpckt_prod_name" value="<?php echo (isset($_REQUEST['name'])? stripslashes_deep($_REQUEST['name']) : "") ?>" class="regular-text enabled wpckt-sc" />
                    <p class="description"><?php _e("Add a name in case you want to add more than one product to this page", "wpckt") ?></p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row" align="right"><label><?php _e("Price", "wpckt") ?> (<?php echo $wpckt_currency->render_symbol() ?>) *</label></th>
                <td class="wpckt-sc-cont"><input name="price" type="text" id="wpckt_prod_price" value="<?php echo (isset($_REQUEST['price'])? $_REQUEST['price'] : "") ?>" class="medium-text format currency enabled required wpckt-sc" /></td>
            </tr>

    <?php if ($wpckt_options['flat_shipping']) { ?>                

                <tr valign="top">
                    <th scope="row" align="right"><label><?php _e("Flat Shipping and Handling", "wpckt") ?>  (<?php echo $wpckt_currency->render_symbol() ?>) *</label></th>
                    <td  class="wpckt-sc-cont"><input name="shipping" type="text" id="wpckt_prod_shipping" value="<?php echo (isset($_REQUEST['shipping'])? $_REQUEST['shipping'] : "") ?>" class="medium-text format currency enabled required wpckt-sc" /></td>
                </tr>

    <?php } ?>

    <?php if ($wpckt_options['use_weight']) { ?>
                <tr valign="top">
                    <th scope="row" align="right"><label><?php _e("Weight", "wpckt") ?> (<?php echo wpckt_get_weight_unit($wpckt_options['weight_unit']) ?>) *</label></th>
                    <td  class="wpckt-sc-cont"><input name="weight" type="text" id="wpckt_prod_weight" value="<?php echo (isset($_REQUEST['weight'])? $_REQUEST['weight'] : "") ?>" class="medium-text format weight enabled required wpckt-sc" /></td>
                </tr>                

    <?php } ?>

            <tr valign="top">
                <th scope="row" align="right"><label><?php _e("Button Text", "wpckt") ?> *</label></th>
                <td><input name="button_name" type="text" id="wpckt_prod_btn_name" value="<?php echo (isset($_REQUEST['button_name'])? stripslashes_deep($_REQUEST['button_name']) : __("Add to Cart", "wpckt")) ?>" class="regular-text enabled required wpckt-sc" /></td>
            </tr>

            <th scope="row"></th>
            <td> 
                <fieldset>
                    <label>
                        <input name="direct_chekout" type="checkbox" id="wpckt_direct_ckt" value="1" class="enabled wpckt-sc" <?php echo (isset($_REQUEST['direct_chekout'])? "checked" : "") ?> /> <?php _e("Go directly to the checkout page", "wpckt") ?></label>
                </fieldset>
                <p class="description"><?php _e("Valid if you are selling only one product", "wpckt") ?></p>
            </td>
            </tr>  


            <th scope="row"></th>
            <td> 
                <fieldset>
                    <label>
                        <input name="quantity" type="checkbox" id="wpckt_qty" value="1" class="enabled wpckt-sc" <?php echo (isset($_REQUEST['quantity'])? "checked" : "") ?> /> <?php _e("Include the quantity field", "wpckt") ?></label>
                    <br />
                    <br />
                    <p class="description"><?php _e("* required fields", "wpckt") ?></p>

            </td>
            </tr> 
            </tbody>
        </table>

        <table class="product-table">
            <tbody>

                <tr valign="top">
                    <th scope="row"><h2><?php _e("Label Settings", "wpckt") ?></h2></th>
                    <td></td>
                </tr>
    
                <tr valign="top">
                    <th scope="row" align="right"><label><?php _e("Align", "wpckt") ?></label></th>
                    <td>
                        <select name="align" class="wpckt-sc">
                            <option value="" <?php echo ( !isset($_REQUEST['align']) ? "selected" : "") ?>><?php _e("none", "wpckt") ?></option>
                            <option value="left" <?php echo ( ( isset($_REQUEST['align']) && $_REQUEST['align'] == "left") ? "selected" : "") ?>><?php _e("left", "wpckt") ?></option>
                            <option value="center"  <?php echo ( (isset($_REQUEST['align']) && $_REQUEST['align'] == "center" )? "selected" : "") ?>><?php _e("center", "wpckt") ?></option>
                            <option value="right"  <?php echo ( (isset($_REQUEST['align']) && $_REQUEST['align'] == "right" )? "selected" : "") ?>><?php _e("right", "wpckt") ?></option>
                        </select>                    
                    </td>
                </tr>
    
                <tr valign="top">
                    <th scope="row" align="right"><label><?php _e("Width", "wpckt") ?> (<?php echo $wpckt_currency->render_symbol() ?>) *</label></th>
                    <td class="wpckt-sc-cont">
                       <input name="width" type="text" id="wpckt_label_width" value="<?php echo (isset($_REQUEST['width'])? $_REQUEST['width'] : "") ?>" class="small-text wpckt-sc" />
                       <select name="width_unit" class="wpckt-sc">
                            <option value="px" <?php echo ( ( isset($_REQUEST['width_unit']) && $_REQUEST['width_unit'] == "px") ? "selected" : "") ?>>px</option>
                            <option value="%"  <?php echo ( (isset($_REQUEST['width_unit']) && $_REQUEST['width_unit'] == "%" )? "selected" : "") ?>>%</option>
                        </select>
                    </td>
                </tr>


            </tbody>
        </table>

        <table>
            <tbody>        
                <tr valign="top">
                    <th scope="row"><h2><?php _e("Shortcode", "wpckt") ?></h2></th>
            <td>
                <fieldset>
                    <textarea name="shortcode" rows="3" cols="50" id="wpckt_shortcode" class="small-text code">[wp_checkout]</textarea>
                </fieldset>
                <p class="description"><?php _e("This shortcode will be added to the post and replaced by product details when you view the page", "wpckt") ?></p>
            </td>
            </tr> 
            </tbody>
        </table>                       
        <br />
        <br />
        <?php if (!isset($_REQUEST["section"]) || $_REQUEST["section"] != "product" ){ ?> 
           <a href="#" class="button button-primary button-large wpckt-insert-product" data-editor="content" title="<?php _e("Insert Product", "wpckt") ?>"><?php _e("Insert into post", "wpckt") ?></a>
        <?php } else { ?>
           <a href="#" class="button button-primary button-large wpckt-update-product" data-editor="content" title="<?php _e("Update Product", "wpckt") ?>"><?php _e("Update", "wpckt") ?></a>
        <?php } ?>

    </div>
    <?php
}

/**
 * Add wp checkout tab on the media panel
 * @param $admin
 * @return array
 */
add_filter('media_upload_tabs', 'wpckt_media_menu');

function wpckt_media_menu($tabs) {
    $newtab = array('wpckt' => __('Wordpress Checkout', 'wpckt'));
    return array_merge($tabs, $newtab);
}

/**
 * Adding Menu
 */
add_action('admin_menu', 'wpckt_add_pages');

function wpckt_add_pages() {
    $page_hook_suffix = add_menu_page('main', __('WP Checkout', 'wpckt'), 'administrator', 'wpckt_settings', '', WPCKT_PLUGIN_URL . '/assets/images/shopping-cart-sm-icon-white.png');
    add_submenu_page('wpckt_settings', __('WP Checkout Settings', 'wpckt'), __('Settings', 'wpckt'), 'administrator', 'wpckt_settings', 'wpckt_settings');
    add_submenu_page('wpckt_settings', __('WP Checkout Orders', 'wpckt'), __('Orders', 'wpckt'), 'administrator', 'wpckt_orders', 'wpckt_orders');
}

/**
 * Admin settings.
 *
 */
function wpckt_settings() {

    global $wpckt_options;
    global $wpckt_currency;
    global $wpckt_presentation_list;
    $wpckt_settings = 1;


    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'wpckt'));
    }


    if (version_compare($wpckt_options['version'], WPCKT_VERSION_CHECK, '<')) {
        $wpckt_options = wpckt_get_options();
    }
    ?>

    <div class="wrap">   

        <h2><?php _e("WP Checkout - Settings", "wpckt") ?></h2>

    <?php echo wpckt_plugin_embed_links(); ?>

    <?php
    if (count($_POST) > 0 && isset($_POST['_wpnonce_wpckt_options']) && wp_verify_nonce($_POST['_wpnonce_wpckt_options'], 'wpckt_settings')) {

        if (isset($_POST['Restore_Default'])) {

            $wpckt_options = wpckt_get_options(true);
            update_option('wpckt_op', $wpckt_options);
        } else if (isset($_POST['Submit'])) {

            // Required Fields
            $required = array(
			    "currency",
                "checkout_page",
                "thankyou_msg",
                "thankyou_email_subject",
                "thankyou_email_body",
                "paypal_email");
				
            // Rich Editor fields
            $rich_content = array(
			    "notes",
                "thankyou_msg",
                "thankyou_email_body");				

            foreach ($required as $required_key) {
                if (!isset($_POST[$required_key]) || empty($_POST[$required_key])) {
                    echo "<div class='error'><p><strong>" . __("Please complete the required fields.", "wpckt") . "</strong></p></div>";
                    $wpckt_settings = 0;
                    break;
                }
            }
			
            if (!filter_var($_POST['paypal_email'], FILTER_VALIDATE_EMAIL)) {
                echo "<div class='error'><p><strong>" . __("Please verify your email address.", "wpckt") . "</strong></p></div>";
                $wpckt_settings = 0;
            }

            unset($_POST['Submit']);

            $newoptions = wpckt_array_merge($wpckt_options, $_POST, true);

            $wpckt_options = $newoptions;
			
			// Adding <p> tag
            foreach ($rich_content as $rich_content_key) {
			
                if (isset($wpckt_options[$rich_content_key]) && !empty($wpckt_options[$required_key])) {
                   $wpckt_options[$rich_content_key] = wpautop($wpckt_options[$rich_content_key]);
                }
				
            }
			
            if ($wpckt_settings) {
                // Adding trim to avoid invalid url
                update_option('wpckt_op', array_map('trim', $wpckt_options));
                echo "<div class='updated'><p>" . __("The Options has been updated!", "wpckt") . "</p></div>";
                do_action("wpckt_admin_after_update");
            }
        }
    }

    // removing slashes from the values.
    $wpckt_options = stripslashes_deep($wpckt_options);

    extract($wpckt_options);
    ?>  

        <form method="POST" name="options" target="_self" enctype="multipart/form-data">

        <?php wp_nonce_field('wpckt_settings', '_wpnonce_wpckt_options'); ?>	

            <input type="hidden" name="version" value="<?php echo WPCKT_VERSION_CURRENT ?>" />          
            <h3><?php _e("Shopping Cart", "wpckt") ?></h3>

            <table width="100%" cellpadding="10" class="form-table wpckt-border-bottom">

                <tr valign="top">
                    <th scope="row"><?php _e("Currency", "wpckt") ?> *</th>
                    <td align="left">
                        <select name="currency" <?php echo ((!$wpckt_settings && $currency == "") ? "class='wpckt-wrong-field'" : "") ?>> 

                            <option value="">
        <?php echo esc_attr(__('- Select Currency -', 'wpckt')); ?></option> 

        <?php foreach ($wpckt_currency->get_all() as $code => $code_arr) { ?>
                                <option value="<?php echo $code ?>" <?php if ($code == $currency) echo "selected" ?> ><?php echo $wpckt_currency->get_attr("name", $code) ?></option>  
        <?php } ?>

                        </select>      
                    </td>  	
                </tr>          

                <tr valign="top">
                    <th scope="row"><?php _e("Checkout Page", "wpckt") ?> *</th>
                    <td align="left">                              
                        <select name="checkout_page" <?php echo ((!$wpckt_settings && $checkout_page == "") ? "class='wpckt-wrong-field'" : "") ?>> 
                            <option value="">
        <?php echo esc_attr(__('- Select Page -', 'wpckt')); ?></option> 
        <?php
        $pages = get_pages();
        foreach ($pages as $page) {
            $option = '<option value="' . $page->ID . '" ' . (($checkout_page == $page->ID ) ? "selected" : "") . '>';
            $option .= $page->post_title;
            $option .= '</option>';
            echo $option;
        }
        ?>
                        </select>

                        <p class="description"><?php _e('This is a page that includes this shortcode: [wp_checkout section="checkout"]. You can also add it using the wp checkout editor button.', 'wpckt') ?></p>
                    </td>  	
                </tr>

            </table>



            <h3><?php _e("PayPal Payments Standard", "wpckt") ?></h3>

            <table width="100%" cellpadding="10" class="form-table wpckt-border-bottom">                               


                <tr valign="top">
                    <th scope="row"><?php _e("PayPal Process Url", "wpckt") ?></th>
                    <td align="left">
                        <p><input name="live" type="radio" value="1" <?php if ($live) echo "checked" ?> /> <strong><?php _e("Production", "wpckt") ?></strong> (https://www.paypal.com/cgi-bin/webscr)</p>    
                        <p><input name="live" type="radio" value="0" <?php if (!$live) echo "checked" ?> /> <strong><?php _e("Sandbox", "wpckt") ?></strong> (https://www.sandbox.paypal.com/cgi-bin/webscr)</p>
                    </td>  	
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e("PayPal E-mail", "wpckt") ?> *</th>
                    <td align="left">

                        <input name="paypal_email" type="text" value="<?php echo esc_textarea($paypal_email) ?>" size="50" <?php echo ((!$wpckt_settings && ($paypal_email == "" || !filter_var($paypal_email, FILTER_VALIDATE_EMAIL))) ? "class='wpckt-wrong-field'" : "") ?> /> 
                    </td>  	
                </tr>

            </table>


            <h3><?php _e("Shipping", "wpckt") ?></h3>


            <table width="100%" cellpadding="10" class="form-table wpckt-border-bottom"> 

                <tr valign="top">
                    <th scope="row"></th>
                    <td align="left">
                        <input name="flat_shipping" type="checkbox" value="1" <?php if ($flat_shipping) echo "checked" ?> /> <strong><?php _e("Use flat shipping and handling cost for products.", "wpckt") ?></strong>  
                    </td>  	
                </tr>

                <tr valign="top">
                    <th scope="row"></th>
                    <td align="left">
                        <input name="use_weight" type="checkbox" value="1" <?php if ($use_weight) echo "checked" ?> /> <strong><?php _e("Use weight for shipping calculation.", "wpckt") ?></strong>
                        <p class="description"><?php _e("Shipping cost can be calculated based on weight and regions using PayPal Shipping Calculations", "wpckt") ?>. <a href="http://wordpress-checkout.com/2014/02/09/how-to-apply-variable-shipping-using-paypal/?utm_source=plugin_link&utm_medium=options&utm_campaign=wpckt&utm_content=paypal_shipping_calculation" title="<?php _e("PayPal Shipping Calculations", "wpckt") ?>" target="_blank"><?php _e("Read More", "wpckt") ?></a></p>     
                    </td>  	
                </tr>


                <tr valign="top">
                    <th scope="row"><?php _e("Weight Unit", "wpckt") ?> </th>
                    <td align="left">
                        <select name="weight_unit">
                            <option value="lbs" <?php if ($weight_unit == "lbs") echo "selected" ?>><?php echo wpckt_get_weight_unit("lbs") ?></option>
                            <option value="kg" <?php if ($weight_unit == "kg") echo "selected" ?>><?php echo wpckt_get_weight_unit("kgs") ?></option>
                        </select>  
                    </td>  	
                </tr>

                <tr valign="top">
                    <th scope="row"><?php _e("Notes", "wpckt") ?> </th>
                    <td align="left">
						<p class="description"><?php _e("Add some notes to the buyer before processing the order with paypal. eg. The total ammount doesn't include tax and shipping cost. It will be added on PayPal before completing your order.", "wpckt") ?></p> 
						<?php
                        wp_editor($notes, 'notes', array('textarea_name' => 'notes',
                            'media_buttons' => false,
                            'wpautop' => true
                        ));
                        ?>                       

                    </td>  	
                </tr>            

            </table> 


            <h3><?php _e("Confirmation page", "wpckt") ?></h3>

            <table width="100%" cellpadding="10" class="form-table wpckt-border-bottom"> 

                <tr valign="top">
                    <th scope="row"><?php _e("Thank You message", "wpckt") ?> *</th>
                    <td align="left">
						<p class="description"><?php echo wpckt_get_messages_order_attributes() ?></p> 
						<?php
                        wp_editor($thankyou_msg, 'thankyou_msg', array('textarea_name' => 'thankyou_msg',
                            'editor_class' => (!$wpckt_settings && $thankyou_msg == "") ? "wpckt-wrong-field" : "",
                            'media_buttons' => false
                        ));
                        ?>                    
                    </td>  	
                </tr> 
                <tr valign="top">
                    <th scope="row"><?php _e("Custom Thank You page", "wpckt") ?></th>             
                    <td align="left">
                        <input name="thankyou_page" type="text" value="<?php echo esc_attr($thankyou_page) ?>" size="100" />
                        <p class="description"><?php _e("Use this thank you page to be shown after a successful transaction. If you don't define one the above message will be shown on the Checkout/Process Page.", "wpckt") ?></p>                </td>                  	
                </tr> 

            </table>





            <h3><?php _e("Confirmation E-mail", "wpckt") ?></h3>

            <table width="100%" cellpadding="10" class="form-table wpckt-border-bottom"> 

                <tr valign="top">
                    <th scope="row"><?php _e("Subject", "wpckt") ?> *</th>             
                    <td align="left">
                        <input name="thankyou_email_subject" type="text" value="<?php echo esc_attr($thankyou_email_subject) ?>" size="100" <?php echo ((!$wpckt_settings && $thankyou_email_subject == "") ? "class='wpckt-wrong-field'" : "") ?>/>
                    </td>                  	
                </tr>		    

                <tr valign="top">
                    <th scope="row"><?php _e("Body", "wpckt") ?> *</th>
                    <td align="left">
                        <p class="description"><?php echo wpckt_get_messages_order_attributes() ?></p>     
                        <?php
                        wp_editor($thankyou_email_body, 'thankyou_email_body', array('textarea_name' => 'thankyou_email_body',
                            'editor_class' => (!$wpckt_settings && $thankyou_email_body == "") ? "wpckt-wrong-field" : "",
                            'media_buttons' => false
                        ));
                        ?> 
                    </td>  	
                </tr> 

            </table>             



            <h3><?php _e("Presentations", "wpckt") ?></h3>

            <table width="100%" cellpadding="10" class="form-table wpckt-border-bottom"> 

                <tr valign="top">
                    <th scope="row"><?php _e("Options", "wpckt") ?></th>
                    <td align="left">
                        <select name="presentation">	           
    <?php foreach ($wpckt_presentation_list as $present) { ?>                    
                                <option value="<?php echo $present ?>" <?php if ($presentation == $present) echo "selected" ?> ><?php echo $present ?></option>  
    <?php } ?>		  	  
                        </select>
                    </td>  	
                </tr>  

            </table>   


            <p class="description"><?php _e("* required fields", "wpckt") ?></p>
            <p class="submit">
                <input type="submit" name="Submit" value="<?php _e("Update", "wpckt") ?>"  class="button-primary" />&nbsp; &nbsp;<input type="submit" name="Restore_Default" value="<?php _e("Restore Default", "wpckt") ?>" class="button" />
            </p>

        </form>
    </div>
	<?php
}

/**
 * Admin Settings - Orders.
 */
function wpckt_orders() {

    global $wpckt_options;
    global $wpckt_currency;
    global $wpckt_orders;


    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'wpckt'));
    }

    $empty_cart_msg = "<p>" . __("There are no orders to display yet.", "wpckt") . "</p>";
    ?>

    <div class="wrap">   

        <h2><?php _e("WP Checkout - Orders", "wpckt") ?></h2>	

    <?php
    if ($wpckt_orders->is_installed()) {


        //Bulk Delete

        if (isset($_POST['_wpnonce_setting_orders']) &&
                wp_verify_nonce($_POST['_wpnonce_setting_orders'], 'wpckt_setting_orders') &&
                (isset($_POST['action']) && $_POST['action'] == 'delete') ||
                (isset($_POST['action2']) && $_POST['action2'] == 'delete')) {

            $num = 0;
            foreach ($_POST['chk_ord'] as $id => $value) {

                if ($wpckt_orders->delete($id)) {
                    $num++;
                }
            }
            if ($num > 0) {
                if ($num == 1)
                    echo "<div class='updated'><p><strong>" . __('Order Deleted', 'wpckt') . "</strong></p></div>";
                else
                    echo "<div class='updated'><p><strong>" . $num . " " . __('Orders Deleted', 'wpckt') . "</strong></p></div>";
            }
        }



        //Delete order
        if (isset($_GET['_wpnonce_setting_orders_delete']) &&
                wp_verify_nonce($_GET['_wpnonce_setting_orders_delete'], 'wpckt_setting_orders_delete') &&
                isset($_GET['action']) &&
                $_GET['action'] == 'delete-ord') {

            if ($wpckt_orders->delete($_GET['id'])) {
                echo "<div class='updated'><p><strong>" . __('Order Deleted', 'wpckt') . "</strong></p></div>";
            }
        }


        $orders = $wpckt_orders->get_orders();

        if ($orders) {

            $i = 0;
            ?>

                <form method="POST" name="options" target="_self" id="wpckt_orders_form" enctype="multipart/form-data">	

                <?php wp_nonce_field('wpckt_setting_orders', '_wpnonce_setting_orders'); ?>

                    <input type="hidden" value="wpckt_orders" name="page"/>
                    <div class="tablenav">


                        <div class="alignleft actions">
                            <select name="action">
                                <option selected="selected" value=""><?php _e("Bulk Actions", "wpckt") ?></option>
                                <option value="delete"><?php _e("Delete", "wpckt") ?></option>
                            </select>
                            <input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e("Apply", "wpckt") ?>"/>
                        </div>

                        <br class="clear"/>
                    </div>

                    <table cellspacing="0" class="widefat fixed">
                        <thead>
                            <tr class="thead">
                                <th scope="col" class="check-column" ><input type="checkbox"/></th>
                                
                                <?php 
								    $column = 0;
									$columns_total = 9;
									foreach($wpckt_orders->get_attributes() as $attributes ){ ?>
									
                                        <th scope="col"><?php echo $attributes['title'] ?></th>
                                        
                                        <?php
                                         
                                        if ($column == $columns_total) {
                                                 break;
                                        } else {										
                                             $column ++;
                                        }
									
								    } ?>
                                
                                <th scope="col"><?php _e("Options", "wpckt") ?></th>
                            </tr>
                        </thead>

                        <tfoot>
                            <tr class="thead">
                                <th scope="col" class="check-column"><input type="checkbox"/></th>
                                <?php 
								    $column = 0;
									foreach($wpckt_orders->get_attributes() as $attributes ){ ?>
									
                                        <th scope="col"><?php echo $attributes['title'] ?></th>
                                        
                                        <?php
                                         
                                        if ($column == $columns_total) {
                                                 break;
                                        } else {										
                                             $column ++;
                                        }
									
								    } ?>
                                <th scope="col"><?php _e("Options", "wpckt") ?></th>
                            </tr>
                        </tfoot>

                        <tbody class="list:user user-list" id="users">	
            <?php
            foreach ($orders as $order) {
                ?>		


                                <tr class="<?php if ($i % 2 == 0) echo "alternate"; ?>" id="prod-<?php echo $i; ?>">
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" value="1" id="check_<?php echo $i; ?>" name="chk_ord[<?php echo $order['id'] ?>]"/>
                                        <input type="hidden" value="<?php echo $order['id'] ?>" name="order_id[]"/>   
                                    </th>
                                   
                                <?php 
								    $column = 0;
									foreach($wpckt_orders->get_attributes() as $attributes ){ ?>
									
                                         <td><?php echo $order[$attributes['name']] ?></td>
                                        
                                        <?php
                                         
                                        if ($column == $columns_total) {
                                                 break;
                                        } else {										
                                             $column ++;
                                        }
									
								    } ?>
                                    <td>
                                      <?php if ($order['details'] != ""){ ?>
                                        <a onclick="document.getElementById('show_details_<?php echo $i; ?>').style.display = 'block';
                                          return false;" href="#"><strong><?php _e("Show Details", "wpckt") ?></strong></a><br />
                                      <?php } ?>    
                                        <a onclick="document.getElementById('delete_alert_<?php echo $i; ?>').style.display = 'block';
                                          return false;" href="#"><?php _e("Delete", "wpckt") ?></a>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="12" align="center" width="100%">
                                        <div id="delete_alert_<?php echo $i; ?>" style="display:none;" class="wpckt-alert">
                <?php _e("You are about to delete this order.", "wpckt") ?>                       

                                            <a href="<?php echo wp_nonce_url('?page=wpckt_orders&action=delete-ord&id=' . $order['id'], 'wpckt_setting_orders_delete', '_wpnonce_setting_orders_delete') ?>"><strong><?php _e("Continue", "wpckt") ?></strong></a>&nbsp;
                                            <a onclick="this.parentNode.style.display = 'none';
                                              return false;" href="#"><?php _e("Cancel", "wpckt") ?></a>
                                        </div>

                                        <div id="show_details_<?php echo $i; ?>" style="display:none; text-align:left; padding-left:100px">
                                            <a onclick="this.parentNode.style.display = 'none';
                                           return false;" href="#"><?php _e("Hide", "wpckt") ?></a>
                                <?php echo $order['details'] ?>
                                            <br />
                                            <a onclick="this.parentNode.style.display = 'none';
                                           return false;" href="#"><?php _e("Hide", "wpckt") ?></a>
                                        </div>                 
                                    </td>
                                </tr>

                <?php
                $i++;
            }
            ?>



                    </table>

                    <div class="tablenav">


                        <div class="alignleft actions">
                            <select name="action2">
                                <option selected="selected" value=""><?php _e("Bulk Actions", "wpckt") ?></option>
                                <option value="delete"><?php _e("Delete", "wpckt") ?></option>
                            </select>
                            <input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="<?php _e("Apply", "wpckt") ?>"/>
                        </div>

                        <br class="clear"/>
                    </div>

                    <input id="save-all" class="button-primary savebutton" type="submit" value="<?php _e("Save all changes", "wpckt") ?>" name="save"/>

                </form>    


            <?php
        } else {
            echo $empty_cart_msg;
        }
    } else {
        echo $empty_cart_msg;
    }
    ?> </div> 
<?php }


 

/**
 * Add the TinyMCE VisualBlocks Plugin
 */ 
add_filter('mce_external_plugins', 'wpckt_tinymceplugin');

function wpckt_tinymceplugin () {
     $plugins = array('wpcheckout'); //Add any more plugins you want to load here
     $plugins_array = array();

     //Build the response - the key is the plugin name, value is the URL to the plugin JS
     foreach ($plugins as $plugin ) {
          $plugins_array[ $plugin ] = WPCKT_PLUGIN_URL. '/includes/tinymce/'. $plugin . '/editor_plugin.js';
     }
     return $plugins_array;
}

/*
The following filter loads external language files for TinyMCE plugins.
It takes an associative array 'plugin_name' => 'path', where path is the
include path to the file. The language file should follow the same format as
/tinymce/langs/wp-langs.php and should define a variable $strings that
holds all translated strings.
When this filter is not used, the function will try to load {mce_locale}.js.
If that is not found, en.js will be tried next.
*/
add_filter('mce_external_languages', 'wpckt_tinymce_languages');
function wpckt_tinymce_languages () {
     $plugins = array('wpcheckout'); //Add any more plugins you want to load here
     $plugins_array = array();

     //Build the response - the key is the plugin name, value is the URL to the plugin JS
     foreach ($plugins as $plugin ) {
          $plugins_array[ $plugin ] = WPCKT_PLUGIN_DIR. '/includes/tinymce/'. $plugin . '/langs/wp-langs.php';
     }
     return $plugins_array;
}



/**
 *  Add Setting link on the plugin list page
 *  @param array
 *  @return array
 */
function wpckt_plugin_action_links($links) {

    return array_merge(
            array(
        'settings' => '<a href="' . admin_url('admin.php?page=wpckt_settings') . '">' . __("Settings", "wpckt") . '</a>'
            ), $links
    );
}

/**
 *  Add Wp Checkout links on the plugin list page
 *  @param array, string
 *  @return array
 */
function wpckt_plugin_meta_links($links, $file) {

    // create link
    if ($file == WPCKT_PLUGIN_FILE) {
        return array_merge(
                $links, array('<a href="http://wordpress-checkout.com/demo?utm_source=plugin_link&utm_medium=plugins_lists&utm_campaign=wpckt&utm_content=demo" target="_blank">' . __("Live Demo", "wpckt") . '</a>',
            '<a href="http://wordpress-checkout.com/how-to-use?utm_source=plugin_link&utm_medium=plugins_list&utm_campaign=wpckt&utm_content=how_to_use" target="_blank">' . __("How To Use", "wpckt") . '</a>'
                )
        );
    }
    return $links;
}

/**
 *  Embed links on the settings page.
 *  @return html
 */
function wpckt_plugin_embed_links() {

    $links_arr = array(
        array("text" => __("How To Use", "wpckt"), "url" => "http://wordpress-checkout.com/how-to-use?utm_source=plugin_link&utm_medium=options&utm_campaign=wpckt&utm_content=how_to_use"),
        array("text" => __("Live Demo", "wpckt"), "url" => "http://wordpress-checkout.com/demo?utm_source=plugin_link&utm_medium=options&utm_campaign=wpckt&utm_content=demo"),
        array("text" => __("Support", "wpckt"), "url" => "http://wordpress-checkout.com/2014/01/23/support/?utm_source=plugin_link&utm_medium=options&utm_campaign=wpckt&utm_content=support")
    );

    $output = "<p align='center' style='font-size:14px;'>";

    foreach ($links_arr as $link) {
        $output .= "<a href=" . $link['url'] . " target='_blank'>" . $link['text'] . "</a> &nbsp; ";
    }

    $output .= "</p>";

    return $output;
}
