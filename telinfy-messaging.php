<?php
/*
Plugin Name: Telinfy Messaging
Description: Sends out messages to customers for orders, order processing, and abandoned carts using SMS, RCS, and WhatsApp
Version: 1.0.0
Author: GreenAds Global
Author URI: https://www.greenadsglobal.com/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.0.0
Tested up to: 6.4
WC tested up to: 8.3
Requires PHP: 7.0
*/


// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 401 Unauthorized' );
	exit;
}

// Load the main controller

if ( ! defined( 'TELINFY_WOOCOMMERCE_PLUGIN_PATH' ) ) {
	define('TELINFY_WOOCOMMERCE_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
}

if( ! defined( 'TELINFY_CURRENT_TIME' )){
	define( 'TELINFY_CURRENT_TIME', current_time( 'U' ) );
}

define('TELINFY_ABANDON_VER','1.0.0');

// Files.

register_activation_hook( __FILE__,  'telinfy_plugin_activation_callback');

function telinfy_plugin_activation_callback(){
	require_once TELINFY_WOOCOMMERCE_PLUGIN_PATH . "includes/plugin.php";
	$telinfy_plugin = \TelinfyMessaging\Includes\telinfy_plugin::telinfy_get_instance();
	$telinfy_plugin->telinfy_activate();
}
require_once 'includes/telinfy_loader.php';













