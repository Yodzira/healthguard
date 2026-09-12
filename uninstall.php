<?php
/**
 * Uninstall cleanup.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

hgd_uninstall_cleanup();

/**
 * Remove every trace of the plugin.
 *
 * @global wpdb $wpdb
 * @return void
 */
function hgd_uninstall_cleanup() {
	global $wpdb;

	delete_option( 'hgd_settings' );
	delete_transient( 'hgd_dotorg_index' );
	wp_clear_scheduled_hook( 'hgd_weekly_scan' );

	$table = $wpdb->prefix . 'hgd_runs';
	// phpcs:ignore WordPress.DB.PreparedSQL -- identifier derived from $wpdb->prefix only.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}
