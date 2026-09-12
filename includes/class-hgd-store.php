<?php
/**
 * Scan history storage.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Table {prefix}hgd_runs; one row per scan.
 */
class HGD_Store {

	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'hgd_runs';
	}

	public static function activate() {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			started_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			duration_ms int(11) NOT NULL DEFAULT 0,
			status varchar(10) NOT NULL DEFAULT '',
			payload longtext NULL,
			PRIMARY KEY  (id),
			KEY started_at (started_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function record( array $scan, $duration_ms ) {
		global $wpdb;

		$wpdb->insert(
			self::table_name(),
			array(
				'started_at'  => current_time( 'mysql' ),
				'duration_ms' => (int) $duration_ms,
				'status'      => (string) $scan['status'],
				'payload'     => wp_json_encode(
					array_map(
						static function ( $r ) {
							return $r instanceof HGD_Result ? $r->to_array() : $r;
						},
						$scan['results']
					)
				),
			),
			array( '%s', '%d', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Results payload of the previous run (for the diff).
	 *
	 * @return array[] Empty array when none.
	 */
	public static function previous_results() {
		global $wpdb;
		$table = self::table_name();

		$payload = $wpdb->get_var( "SELECT payload FROM {$table} ORDER BY id DESC LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL -- table name from prefix.
		if ( ! $payload ) {
			return array();
		}
		$decoded = json_decode( $payload, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	public static function history( $limit = 12 ) {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT id, started_at, duration_ms, status FROM {$table} ORDER BY id DESC LIMIT %d", (int) $limit ),
			ARRAY_A
		);
	}

	public static function prune( $keep = 60 ) {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id NOT IN (SELECT id FROM (SELECT id FROM {$table} ORDER BY id DESC LIMIT %d) keep_rows)", (int) $keep ) ); // phpcs:ignore WordPress.DB.PreparedSQL -- table name from prefix.
	}

	public static function erase_all() {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.
	}

	public static function drop_table() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL -- identifier derived from $wpdb->prefix.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
