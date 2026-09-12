<?php
/**
 * Plugin Name:       Health Guard
 * Plugin URI:        https://github.com/Yodzira/healthguard
 * Description:       Weekly site checks with plain-language reports: PHP/WordPress versions, overdue cron, TLS expiry, autoload bloat, disk space, stale plugins. Email digest included.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Yodzira
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       healthguard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HGD_VERSION', '0.1.0' );
define( 'HGD_FILE', __FILE__ );
define( 'HGD_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		foreach ( array( 'HGD_Check_' => '/includes/checks/class-hgd-check-', 'HGD_' => '/includes/class-hgd-' ) as $prefix => $dir ) {
			if ( 0 !== strpos( $class, $prefix ) ) {
				continue;
			}
			$snake = strtolower( preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', substr( $class, strlen( $prefix ) ) ) );
			$snake = str_replace( '_', '-', $snake );
			$file  = HGD_DIR . $dir . $snake . '.php';
			if ( is_readable( $file ) ) {
				require $file;
				return;
			}
		}
	}
);

add_action( 'plugins_loaded', array( 'HGD_Plugin', 'boot' ), 20 );

register_activation_hook(
	__FILE__,
	static function () {
		require_once HGD_DIR . '/includes/class-hgd-store.php';
		HGD_Store::activate();
		if ( ! wp_next_scheduled( 'hgd_weekly_scan' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', 'hgd_weekly_scan' );
		}
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		wp_clear_scheduled_hook( 'hgd_weekly_scan' );
	}
);
