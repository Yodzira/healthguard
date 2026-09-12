<?php
/**
 * Standalone bootstrap: pure core, no WP needed beyond ABSPATH guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/wp/' );
}
if ( ! defined( 'HGD_DIR' ) ) {
	define( 'HGD_DIR', dirname( __DIR__ ) );
}

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
