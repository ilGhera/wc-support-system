<?php
/**
 * Plugin Name: ilGhera Support System for WooCommerce - Premium
 * Plugin URI: https://www.ilghera.com/product/wc-support-system/
 * Description:  Give support to your WooComerce customers with this fast and easy to use ticket system.
 * Author: ilGhera
 * Version: 1.0.5
 * Author URI: https://ilghera.com
 * Requires at least: 5.0
 * Tested up to: 6.8
 * WC tested up to: 9
 * Text Domain: wc-support-system
 * Domain Path: /languages
 *
 * @package wc-support-system-premium
 */

/*Exit if accessed directly*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Admin notice for WooCommerce not installed
 *
 * @return void
 */
function wss_wc_not_installed() {
	echo '<div class="notice notice-error is-dismissible">';
		echo '<p>';
		echo wp_kses_post( __( '<b>WARNING!</b> <i>ilGhera Support System for WooCommerce</i> requires <b><a href="https://it.wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a></b> to be activated.', 'wc-support-system' ) );
		echo '</p>';
	echo '</div>';
}


/**
 * Plutin activation
 *
 * @return void
 */
function wss_premium_activation() {

	/*Deactivate the free version if present*/
	if ( function_exists( 'wss_activation' ) ) {
		deactivate_plugins( 'wc-support-system/wc-support-system.php' );
		remove_action( 'plugins_loaded', 'wss_activation' );
		wp_safe_redirect( admin_url( 'plugins.php?plugin_status=all&paged=1&s' ) );
	}

	/*WooCommerce must be installed*/
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

		add_action( 'admin_notices', 'wss_wc_not_installed' );

	} else {

		/*Internalization*/
		load_plugin_textdomain( 'wc-support-system', false, basename( dirname( __FILE__ ) ) . '/languages' );

		/*Define constants*/
		define( 'WSS_DIR', plugin_dir_path( __FILE__ ) );
		define( 'WSS_URI', plugin_dir_url( __FILE__ ) );
		define( 'WSS_INCLUDES', WSS_DIR . 'includes/' );
		define( 'WSS_VERSION', '1.0.4' );

		/*Files required*/
		require WSS_INCLUDES . 'ilghera-notice/class-ilghera-notice.php';
		require WSS_INCLUDES . 'class-wc-support-system.php';
		require WSS_INCLUDES . 'class-wss-table.php';

		wc_support_system::wss_tables();

		/*Cron*/
		if ( ! wp_next_scheduled( 'wss_cron_tickets_action' ) ) {
			wp_schedule_event( time(), 'daily', 'wss_cron_tickets_action' );
		}

		/**
		 * Deactivation
		 *
		 * @return void
		 */
		function wss_deactivation() {
			$timestamp = wp_next_scheduled( 'wss_cron_tickets_action' );
			wp_unschedule_event( $timestamp, 'wss_cron_tickets_action' );
		}
		register_deactivation_hook( __FILE__, 'wss_deactivation' );

	}
}
add_action( 'plugins_loaded', 'wss_premium_activation', 1 );


/**
 * HPOS compatibility
 */
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);


/**
 * Update checker
 *
 * @return void
 */
require plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$wss_update_checker = PucFactory::buildUpdateChecker(
	'https://www.ilghera.com/wp-update-server-2/?action=get_metadata&slug=wc-support-system-premium',
	__FILE__,
	'wc-support-system-premium'
);


$wss_update_checker->addQueryArgFilter( 'wss_secure_update_check' );

/**
 * PUC Secure update check
 *
 * @param array $query_args the parameters.
 *
 * @return array
 */
function wss_secure_update_check( $query_args ) {
	$key = base64_encode( get_option( 'wss-premium-key' ) );

	if ( $key ) {
		$query_args['premium-key'] = $key;
	}
	return $query_args;
}


/**
 * Update message
 *
 * @param array  $plugin_data the plugin metadata.
 * @param object $response    metadata about the available plugin update.
 *
 * @return void
 */
function wss_update_message( $plugin_data, $response ) {

	$message = null;
	$key     = get_option( 'wss-premium-key' );

	$message = null;

	if ( ! $key ) {

		$message = 'A <b>Premium Key</b> is required for keeping this plugin up to date. Please, add yours in the <a href="' . admin_url() . 'admin.php/?page=wss-settings">options page</a> or click <a href="https://www.ilghera.com/product/woocommerce-support-system-premium/" target="_blank">here</a> for prices and details.';

	} else {

		$decoded_key = explode( '|', base64_decode( $key ) );
		$bought_date = date( 'd-m-Y', strtotime( $decoded_key[1] ) );
		$limit       = strtotime( $bought_date . ' + 365 day' );
		$now         = strtotime( 'today' );

		if ( $limit < $now ) {
			$message = 'It seems like your <strong>Premium Key</strong> is expired. Please, click <a href="https://www.ilghera.com/product/woocommerce-support-system-premium/" target="_blank">here</a> for prices and details.';
		} elseif ( 3292 !== intval( $decoded_key[2] ) ) {
			$message = 'It seems like your <strong>Premium Key</strong> is not valid. Please, click <a href="https://www.ilghera.com/product/woocommerce-support-system-premium/" target="_blank">here</a> for prices and details.';
		}
	}

	$allowed = array(
		'b' => array(),
		'a' => array(
			'href'   => array(),
			'target' => array(),
		),
	);

	echo $message ? '<br><span class="wss-alert">' . wp_kses( $message, $allowed ) . '</span>' : '';

}
add_action( 'in_plugin_update_message-wc-support-system-premium/wc-support-system.php', 'wss_update_message', 10, 2 );

