<?php
/*
Plugin Name: Wordpress Checkout
Plugin URI: http://wordpress-checkout.com/
Description: Simple and flexible e-commerce solution. Allows to transform your post and pages into products.
Version: 1.2.3.0
Author: Alain Gonzalez
Author URI: http://wordpress-checkout.com/?utm_source=plugin_link&utm_medium=plugins_list&utm_campaign=wpckt&utm_content=plugin_page
*/

/*
    Copyright (C) 2014 Alain Gonzalez (support@web-argument.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'WPCKT' ) ) define('WPCKT','wp-checkout');
if ( ! defined( 'WPCKT_PLUGIN_FILE' ) ) define('WPCKT_PLUGIN_FILE', plugin_basename( __FILE__ ));
if ( ! defined( 'WPCKT_PLUGIN_DIR' ) ) 	define( 'WPCKT_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) );
if ( ! defined( 'WPCKT_PLUGIN_URL' ) )  define( 'WPCKT_PLUGIN_URL', WP_PLUGIN_URL . '/wordpress-checkout');
if ( ! defined( 'WPCKT_VERSION_CURRENT' ) )  define('WPCKT_VERSION_CURRENT','1.2.3.0');
if ( ! defined( 'WPCKT_VERSION_CHECK' ) )  define('WPCKT_VERSION_CHECK','1.2.1.1');

require_once WPCKT_PLUGIN_DIR. '/includes/classes/class.wpckt-cart.php';
require_once WPCKT_PLUGIN_DIR. '/includes/classes/class.wpckt-sessions.php';
require_once WPCKT_PLUGIN_DIR. '/includes/classes/class.wpckt-currency.php';
require_once WPCKT_PLUGIN_DIR. '/includes/classes/class.wpckt-orders-engine.php';
require_once WPCKT_PLUGIN_DIR. '/includes/classes/class.wpckt-widget.php';

if ( is_admin() ){
	
  require_once WPCKT_PLUGIN_DIR. '/includes/admin.php';	
  
} 
require_once WPCKT_PLUGIN_DIR. '/includes/cart.php';
require_once WPCKT_PLUGIN_DIR. '/includes/functions.php';

?>