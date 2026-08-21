<?php
/**
 * Plugin Name:       Webcasata WooCommerce Suite
 * Plugin URI:        https://webcasata.com
 * Description:       A lightweight, modular WooCommerce enhancement toolkit. Turn on only the features you need — everything else stays completely inactive, so you're not paying a performance cost for features you don't use.
 * Version:           1.1.0
 * Author:            Webcasata
 * Author URI:        https://webcasata.com
 * Text Domain:       webcasata-woocommerce-suite
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'WWCS_VERSION', '1.1.0' );
define( 'WWCS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WWCS_URL', plugin_dir_url( __FILE__ ) );
define( 'WWCS_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Boot the plugin once all plugins are loaded, so we can safely check for WooCommerce.
 */
add_action( 'plugins_loaded', 'wwcs_init_plugin' );
function wwcs_init_plugin() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wwcs_missing_wc_notice' );
		return;
	}

	require_once WWCS_PATH . 'includes/class-wwcs-settings.php';
	require_once WWCS_PATH . 'includes/class-wwcs-loader.php';

	WWCS_Settings::init();
	WWCS_Loader::init();

	if ( is_admin() ) {
		require_once WWCS_PATH . 'includes/admin/class-wwcs-admin-page.php';
		WWCS_Admin_Page::init();
	}
}

/**
 * Admin notice shown if WooCommerce is not active.
 */
function wwcs_missing_wc_notice() {
	echo '<div class="notice notice-error"><p>';
	echo '<strong>Webcasata WooCommerce Suite</strong> requires WooCommerce to be installed and active.';
	echo '</p></div>';
}

/**
 * Checks whether WooCommerce is active — on this site, or network-wide on multisite.
 * Checked directly against the active_plugins option (rather than only
 * class_exists('WooCommerce')) because this runs from the activation hook,
 * before we can fully rely on load order.
 */
function wwcs_is_woocommerce_active() {
	$active_plugins = (array) get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$active_plugins = array_merge( $active_plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
	}

	return in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) || class_exists( 'WooCommerce' );
}

/**
 * Activation: refuse to activate at all if WooCommerce isn't active, and set
 * up default (empty / all-off) settings if it is.
 */
register_activation_hook( __FILE__, 'wwcs_activate' );
function wwcs_activate() {

	if ( ! wwcs_is_woocommerce_active() ) {
		deactivate_plugins( WWCS_BASENAME );

		wp_die(
			sprintf(
				'<p>%1$s</p><p><a href="%2$s">%3$s</a></p>',
				esc_html__( 'Webcasata WooCommerce Suite requires WooCommerce to be installed and active. The plugin has been deactivated.', 'webcasata-woocommerce-suite' ),
				esc_url( admin_url( 'plugins.php' ) ),
				esc_html__( 'Return to Plugins page', 'webcasata-woocommerce-suite' )
			),
			esc_html__( 'Plugin dependency check', 'webcasata-woocommerce-suite' ),
			array( 'back_link' => true )
		);
	}

	require_once WWCS_PATH . 'includes/class-wwcs-settings.php';
	WWCS_Settings::set_defaults();
}
