<?php
/**
 * Plugin boot + scheduled scan runner.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HGD_Plugin {

	/** @var array Settings (small option). */
	const OPTION = 'hgd_settings';

	public static function settings() {
		$stored = get_option( self::OPTION, array() );

		return wp_parse_args(
			is_array( $stored ) ? $stored : array(),
			array(
				'email_enabled' => true,
				'email_to'      => '',
			)
		);
	}

	public static function boot() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( 'HGD_Admin', 'menu' ) );
			add_action( 'admin_post_hgd_run_scan', array( 'HGD_Admin', 'handle_run_scan' ) );
		}
		add_action( 'hgd_weekly_scan', array( __CLASS__, 'run_scheduled' ) );
	}

	/**
	 * Run a scan, store it, send the digest when something changed.
	 *
	 * @return array {scan, changed}
	 */
	public static function run_scan() {
		$t0       = microtime( true );
		$ctx      = HGD_WpInfo::context();
		$scan     = HGD_Scanner::scan( $ctx );
		$duration = (int) round( ( microtime( true ) - $t0 ) * 1000 );

		$previous = HGD_Store::previous_results();
		$diff     = HGD_Scanner::diff( $previous, $scan['results'] );

		HGD_Store::record( $scan, $duration );
		HGD_Store::prune( 60 );

		$settings = self::settings();
		if ( $settings['email_enabled'] && ( ! $diff['same'] || ! $previous ) ) {
			$to = '' !== trim( $settings['email_to'] ) ? $settings['email_to'] : get_option( 'admin_email' );
			wp_mail(
				$to,
				( HGD_Result::CRIT === $scan['status'] ? '🔴 ' : ( HGD_Result::WARN === $scan['status'] ? '🟡 ' : '🟢 ' ) ) . 'Health Guard — ' . get_bloginfo( 'name' ),
				HGD_Mailer::digest( $scan, get_bloginfo( 'name' ), ! $diff['same'] )
			);
		}

		return array(
			'scan'    => $scan,
			'changed' => ! $diff['same'],
		);
	}

	/**
	 * Cron entry.
	 *
	 * @return void
	 */
	public static function run_scheduled() {
		self::run_scan();
	}
}
