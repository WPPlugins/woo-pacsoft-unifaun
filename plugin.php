<?php

/**
 * Plugin Name: Pacsoft/Unifaun integration for WooCommerce
 * Description: Ship your WooCommerce orders with over 150 major freight companies around the world. Book transport and print packing slips with Pacsoft/Unifaun.
 * Version: 2.1.4
 * Author: Advanced WP-Plugs
 * Author URI: http://wp-plugs.com
 * License: GPL2
 */

require_once "autoload.php";

use Wetail\Pacsoft\Plugin;
use Wetail\Pacsoft\AJAX;
use Wetail\Pacsoft\API\Request;

/**
 * Setup
 */
add_action( 'plugins_loaded', function() {
	Plugin::loadTextdomain();
} );

/**
 * admin_init
 */
add_action( 'admin_init', function() {
	Plugin::addSettings();
	Plugin::addAdminScripts();
} );

/**
 * Add settings page
 */
add_action( 'admin_menu', function() {
	Plugin::addSettingsPage();
} );

/**
 * Check API
 */
add_filter( 'pacsoft_check_license', function() {
	return Plugin::checkLicense();
} );

/**
 * Edit view
 */
add_action( 'load-edit.php', function() {
	Plugin::onPostEditView();
} );

/**
 * Auto sync
 */
if( get_option( 'pacsoft_on_order_status' ) && ! get_option( 'pacsoft_sync_with_options' ) ) {
	add_action( 'woocommerce_order_status_' . get_option( 'pacsoft_on_order_status' ), function( $orderId ) {
		try {// Its here!!!!! When you click 'sync order' in Woocommerce
			Request::syncOrder( $orderId );
		}
		catch( Exception $error ) {
			error_log($error->getMessage());
		}
	} );
}

/**
 * AJAX interface
 */
add_action( 'wp_ajax_pacsoft_unifaun', function() {
	AJAX::process();
} );

/**
 * AJAX sync
 */
add_action( 'wp_ajax_pacsoft_sync_order', function() {
	AJAX::syncOrder();
} );

/**
 * AJAX print
 */
add_action( 'wp_ajax_pacsoft_print_order', function() {
	AJAX::printOrder();
} );

/**
 * Activate
 */
register_activation_hook( __FILE__, function() {
	Plugin::activate();
} );

/**
 * Deactivate
 */
register_deactivation_hook( __FILE__, function() {
	Plugin::deactivate();
} );

add_filter( 'pre_update_option_pacsoft_services', [ "Wetail\Pacsoft\Plugin", "filterServicesSettings" ], 10, 2 );

/**
 * Older code (the rest)
 */
include "woocommerce-pacsoft-admin.php";