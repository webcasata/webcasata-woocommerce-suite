<?php
/**
 * Fires when the plugin is deleted from the Plugins screen (after being
 * deactivated). WordPress only includes this file directly for uninstall —
 * it is never loaded on a normal request, so the WP_UNINSTALL_PLUGIN guard
 * below is what stops it being run any other way.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * By default we DO NOT delete anything — settings are left in the database
 * so reinstalling the plugin later restores the site's previous
 * configuration. Data is only removed if the admin explicitly checked
 * "Delete all Webcasata WooCommerce Suite settings when this plugin is
 * deleted" on the settings screen before deleting the plugin.
 */
function wwcs_uninstall_cleanup() {
	delete_option( 'wwcs_settings' );
	delete_option( 'wwcs_delete_data_on_uninstall' );
}

$delete_data = get_option( 'wwcs_delete_data_on_uninstall', false );

if ( ! $delete_data ) {
	return; // Keep settings in place.
}

if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		wwcs_uninstall_cleanup();
		restore_current_blog();
	}
} else {
	wwcs_uninstall_cleanup();
}
