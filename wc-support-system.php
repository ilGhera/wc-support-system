<?php
/**
 * Plugin Name: Woocommerce Support System
 * Plugin URI: https://www.ilghera.com/product/wc-support-system/
 * Description:  Give support to your customers with this easy and fast ticket system, available 
 * both for logged in users and guests. 
 * Author: ilGhera
 * Version: 0.9.0
 * Author URI: https://ilghera.com 
 * Requires at least: 4.0
 * Tested up to: 4.9
 * Text Domain: wss
 * Domain Path: /languages
 */


/*Exit if accessed directly*/
if ( !defined( 'ABSPATH' ) ) exit;


/*Internalization*/
load_plugin_textdomain('wss', false, basename( dirname( __FILE__ ) ) . '/languages' );


/*Files required*/
include( plugin_dir_path( __FILE__ ) . 'includes/class-wc-support-system.php');


/*Activation*/
function wss_activation() {
	/*Cron*/
	if(!wp_next_scheduled( 'wss_cron_tickets_action' )) {
		wp_schedule_event(time(), 'hourly', 'wss_cron_tickets_action');
    }	
}
register_activation_hook(__FILE__, 'wss_activation');	


/*Deactivation*/
function wss_deactivation() {
	$timestamp = wp_next_scheduled( 'wss_cron_tickets_action' );
    wp_unschedule_event( $timestamp, 'wss_cron_tickets_action' );
} 
register_deactivation_hook(__FILE__, 'wss_deactivation');
