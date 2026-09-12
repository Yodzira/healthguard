<?php
/**
 * Gathers the data context for checks from a live WordPress.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP-facing data collector. Everything returned must be plain scalars/arrays.
 */
class HGD_WpInfo {

	/**
	 * Build the check context.
	 *
	 * @return array
	 */
	public static function context() {
		global $wpdb;

		$ctx = array(
			'php_version'      => PHP_VERSION,
			'wp_version'       => get_bloginfo( 'version' ),
			'now'              => time(),
			'plugins'          => array(),
			'stale_plugins'    => array(),
			'autoload_bytes'   => null,
			'autoload_top'     => array(),
			'cron_events'      => array(),
			'tls_days_left'    => null,
			'disk_free_bytes'  => null,
			'php_min_required' => '',
		);

		// Plugins + min PHP + stale detection (needs wordpress.org data, cached 12 h).
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$api_index = self::dotorg_index();
		$now       = time();
		foreach ( get_plugins() as $file => $data ) {
			$name = isset( $data['Name'] ) ? $data['Name'] : $file;
			$slug = dirname( $file );
			$ctx['plugins'][] = array(
				'name'         => $name,
				'version'      => isset( $data['Version'] ) ? $data['Version'] : '',
				'tested_up_to' => isset( $api_index[ $slug ] ) ? (string) $api_index[ $slug ]['tested'] : '',
			);
			if ( ! empty( $data['RequiresPHP'] ) && version_compare( $data['RequiresPHP'], $ctx['php_min_required'] ?: '0', '>' ) ) {
				$ctx['php_min_required'] = $data['RequiresPHP'];
			}
			if ( isset( $api_index[ $slug ] ) && $api_index[ $slug ]['last_updated'] ) {
				$years = ( $now - (int) $api_index[ $slug ]['last_updated'] ) / YEAR_IN_SECONDS;
				if ( $years > 2 ) {
					$ctx['stale_plugins'][ $name ] = $years;
				}
			}
		}

		// Cron.
		$cron = _get_cron_array();
		foreach ( $cron as $time => $hooks ) {
			foreach ( (array) $hooks as $hook => $instances ) {
				$ctx['cron_events'][] = array( 'hook' => $hook, 'time' => (int) $time );
			}
		}

		// Autoload size + top 5.
		$rows = $wpdb->get_results( 'SELECT option_name, LENGTH(option_value) AS bytes FROM ' . $wpdb->options . ' WHERE autoload = \'yes\' ORDER BY bytes DESC LIMIT 5', ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL -- read-only diagnostic.
		$sum  = $wpdb->get_var( 'SELECT SUM(LENGTH(option_value)) FROM ' . $wpdb->options . ' WHERE autoload = \'yes\'' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL -- read-only diagnostic.
		if ( null !== $sum ) {
			$ctx['autoload_bytes'] = (int) $sum;
			foreach ( (array) $rows as $row ) {
				$ctx['autoload_top'][ $row['option_name'] ] = (int) $row['bytes'];
			}
		}

		// TLS.
		$ctx['tls_days_left'] = self::tls_days_left( home_url( '/' ) );

		// Disk.
		$free = @disk_free_space( untrailingslashit( WP_CONTENT_DIR ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- hosting may forbid it.
		if ( is_numeric( $free ) ) {
			$ctx['disk_free_bytes'] = (int) $free;
		}

		return $ctx;
	}

	/**
	 * Days until the site certificate expires (null when unreadable).
	 *
	 * @param string $home_url Site home.
	 * @return int|null
	 */
	public static function tls_days_left( $home_url ) {
		$parts = wp_parse_url( $home_url );
		if ( empty( $parts['host'] ) || 'https' !== ( isset( $parts['scheme'] ) ? $parts['scheme'] : '' ) ) {
			$host = isset( $parts['host'] ) ? $parts['host'] : '';
			if ( '' === $host ) {
				return null;
			}
		} else {
			$host = $parts['host'];
		}

		$ctx    = stream_context_create(
			array(
				'ssl' => array( 'capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false ),
			)
		);
		$client = @stream_socket_client( 'ssl://' . $host . ':443', $errno, $errstr, 8, STREAM_CLIENT_CONNECT, $ctx ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.
		if ( ! $client ) {
			return null;
		}
		$params = stream_context_get_params( $client );
		if ( empty( $params['options']['ssl']['peer_certificate'] ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- raw SSL socket, WP_Filesystem cannot read peer certs.
			fclose( $client );

			return null;
		}
		$cert = openssl_x509_parse( $params['options']['ssl']['peer_certificate'] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- raw SSL socket, see above.
		fclose( $client );
		if ( ! $cert || empty( $cert['validTo_time_t'] ) ) {
			return null;
		}

		return (int) floor( ( $cert['validTo_time_t'] - time() ) / DAY_IN_SECONDS );
	}

	/**
	 * wordpress.org plugin data (tested-up-to + last_updated), cached 12 h.
	 *
	 * @return array slug => {tested, last_updated}
	 */
	private static function dotorg_index() {
		$cached = get_transient( 'hgd_dotorg_index' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$index = array();
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( array_keys( get_plugins() ) as $file ) {
			$slug = dirname( $file );
			if ( '.' === $slug || isset( $index[ $slug ] ) ) {
				continue;
			}
			$response = wp_remote_get(
				'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request%5Bslug%5D=' . rawurlencode( $slug ),
				array( 'timeout' => 6 )
			);
			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				continue;
			}
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $body ) || empty( $body['slug'] ) ) {
				continue;
			}
			$index[ $slug ] = array(
				'tested'       => isset( $body['tested'] ) ? (string) $body['tested'] : '',
				'last_updated' => isset( $body['last_updated'] ) ? (int) $body['last_updated'] : 0,
			);
		}

		set_transient( 'hgd_dotorg_index', $index, 12 * HOUR_IN_SECONDS );

		return $index;
	}
}
