<?php
/**
 * Plugin Name: Woocommerce Support System
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
function wss_activation() {

	/*WooCommerce must be installed*/
	if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
       
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
			wp_schedule_event(time(), 'hourly', 'wss_cron_tickets_action');//temp
	    }
	}
}
add_action( 'plugins_loaded', 'wss_activation', 100);	
// register_activation_hook(__FILE__, 'wss_activation');	


/*Deactivation*/
function wss_deactivation() {
	$timestamp = wp_next_scheduled( 'wss_cron_tickets_action' );
    wp_unschedule_event( $timestamp, 'wss_cron_tickets_action' );
} 
register_deactivation_hook(__FILE__, 'wss_deactivation');
