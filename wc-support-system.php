<?php
/**
 * Plugin Name: Woocommerce Support System - Premium
 * Plugin URI: https://www.ilghera.com/product/wc-support-system/
 * Description:  Give support to your WooComerce customers with this fast and easy to use ticket system.
 * Author: ilGhera
 * Version: 0.9.0
 * Author URI: https://ilghera.com 
 * Requires at least: 4.0
 * Tested up to: 4.9
 * WC tested up to: 3
 * Text Domain: wss
 * Domain Path: /languages
 */


/*Exit if accessed directly*/
if ( !defined( 'ABSPATH' ) ) exit;


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

		/*Deactivate the plugin*/
		deactivate_plugins(__FILE__);
       
        wp_die( __('<b>WARNING!</b> <i>WooCommerce Support System</i> requires <b><a href="https://it.wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a></b> to be activated.') );	

    } else {

		/*Internalization*/
		load_plugin_textdomain('wss', false, basename( dirname( __FILE__ ) ) . '/languages' );


		/*Files required*/
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


/*Update checker*/
require( plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php');
$wss_update_checker = Puc_v4_Factory::buildUpdateChecker(
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