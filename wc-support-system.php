<?php
/**
 * Plugin Name: Woocommerce Support System - Premium
 * Plugin URI: https://www.ilghera.com/product/wc-support-system/
 * Description:  Give support to your WooComerce customers with this fast and easy to use ticket system.
 * Author: ilGhera
 * Version: 1.0.4
 * Author URI: https://ilghera.com 
 * Requires at least: 4.0
 * Tested up to: 6.1
 * WC tested up to: 7
 * Text Domain: wss
 * Domain Path: /languages
 */


/*Exit if accessed directly*/
if ( !defined( 'ABSPATH' ) ) exit;


/*Update database version*/
// update_option('wss-db-version', '1.0.2');


/*Admin notice for WooCommerce not installed*/
function wss_wc_not_installed() {
	echo '<div class="notice notice-error is-dismissible">';
        echo '<p>' . __( '<b>WARNING!</b> <i>WooCommerce Support System</i> requires <b><a href="https://it.wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a></b> to be activated.', 'wss' ) . '</p>';
    echo '</div>';
}


/*Activation*/
function wss_premium_activation() {

	/*Deactivate the free version if present*/
	if(function_exists('wss_activation')) {
		deactivate_plugins('wc-support-system/wc-support-system.php');
	    remove_action( 'plugins_loaded', 'wss_activation' );
	    wp_redirect(admin_url('plugins.php?plugin_status=all&paged=1&s'));
	}

	/*WooCommerce must be installed*/
	if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

		add_action( 'admin_notices', 'wss_wc_not_installed' );

    } else {

		/*Internalization*/
		load_plugin_textdomain('wss', false, basename( dirname( __FILE__ ) ) . '/languages' );


		/*Files required*/
		include( plugin_dir_path( __FILE__ ) . 'includes/ilghera-notice/class-ilghera-notice.php');
		include( plugin_dir_path( __FILE__ ) . 'includes/class-wc-support-system.php');
		include( plugin_dir_path( __FILE__ ) . 'includes/class-wss-table.php');

		wc_support_system::wss_tables();

		/*Cron*/
		if(!wp_next_scheduled( 'wss_cron_tickets_action' )) {
			wp_schedule_event(time(), 'daily', 'wss_cron_tickets_action');//temp
	    }

	    /*Deactivation*/
		function wss_deactivation() {
			$timestamp = wp_next_scheduled( 'wss_cron_tickets_action' );
		    wp_unschedule_event( $timestamp, 'wss_cron_tickets_action' );
		} 
		register_deactivation_hook(__FILE__, 'wss_deactivation');

	}
}
add_action( 'plugins_loaded', 'wss_premium_activation', 1);


/**
 * Update checker
 */
require( plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php');
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$wss_update_checker = PucFactory::buildUpdateChecker(
    'https://www.ilghera.com/wp-update-server-2/?action=get_metadata&slug=wc-support-system-premium',
    __FILE__,
    'wc-support-system-premium'
);

$wss_update_checker->addQueryArgFilter('wss_secure_update_check');
function wss_secure_update_check($queryArgs) {
    $key = base64_encode( get_option('wss-premium-key') );

    if($key) {
        $queryArgs['premium-key'] = $key;
    }
    return $queryArgs;
}


/**
 * Update message
 */
function wss_update_message($plugin_data, $response) {

	$message = null;
	$key = get_option('wss-premium-key');

	$message = null;

	if(!$key) {

		$message = 'A <b>Premium Key</b> is required for keeping this plugin up to date. Please, add yours in the <a href="' . admin_url() . 'admin.php/?page=wss-settings">options page</a> or click <a href="https://www.ilghera.com/product/woocommerce-support-system-premium/" target="_blank">here</a> for prices and details.';

	} else {

		$decoded_key = explode('|', base64_decode($key));
		$bought_date = date('d-m-Y', strtotime($decoded_key[1]));
		$limit = strtotime($bought_date . ' + 365 day');
		$now = strtotime('today');

		if($limit < $now) {
			$message = 'It seems like your <strong>Premium Key</strong> is expired. Please, click <a href="https://www.ilghera.com/product/woocommerce-support-system-premium/" target="_blank">here</a> for prices and details.';
		} elseif($decoded_key[2] != 3292) {
			$message = 'It seems like your <strong>Premium Key</strong> is not valid. Please, click <a href="https://www.ilghera.com/product/woocommerce-support-system-premium/" target="_blank">here</a> for prices and details.';
		}
	}
	echo $message ? '<br><span class="wss-alert">' . $message . '</span>' : '';

}
add_action('in_plugin_update_message-wc-support-system-premium/wc-support-system.php', 'wss_update_message', 10, 2);
