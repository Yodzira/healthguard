<?php
/**
 * Plain-text digest builder.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the email/Telegram digest text. Pure.
 */
class HGD_Mailer {

	/**
	 * Digest text for a scan.
	 *
	 * @param array  $scan    Scan payload (from HGD_Scanner::scan()).
	 * @param string $site    Site name/home.
	 * @param bool   $changed Whether anything changed since last scan.
	 * @return string
	 */
	public static function digest( array $scan, $site, $changed ) {
		$icon = array(
			HGD_Result::CRIT => '🔴',
			HGD_Result::WARN => '🟡',
			HGD_Result::OK   => '🟢',
		);
		$status = isset( $scan['status'] ) ? $scan['status'] : HGD_Result::OK;
		$lines  = array();
		$lines[] = ( $icon[ $status ] ?? '⚪' ) . ' Health Guard — ' . $site;
		$lines[] = '';
		foreach ( (array) ( isset( $scan['results'] ) ? $scan['results'] : array() ) as $r ) {
			if ( ! $r instanceof HGD_Result ) {
				$r = new HGD_Result( $r['check'], $r['status'], $r['headline'] );
			}
			if ( HGD_Result::OK === $r->status || HGD_Result::UNKNOWN === $r->status ) {
				continue; // Digest lists problems only — ok/unknown stay in the report.
			}
			$lines[] = '• [' . strtoupper( $r->status ) . '] ' . $r->headline;
		}
		$lines[] = '';
		if ( ! $changed ) {
			$lines[] = 'Nothing changed since the previous check.';
		}
		$c = isset( $scan['counts'] ) ? $scan['counts'] : array();
		$lines[] = sprintf( 'ok: %d, warn: %d, crit: %d', isset( $c['ok'] ) ? $c['ok'] : 0, isset( $c['warn'] ) ? $c['warn'] : 0, isset( $c['crit'] ) ? $c['crit'] : 0 );

		return implode( "\n", $lines );
	}
}
