<?php
/**
 * Check: plugins not updated for years.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once __DIR__ . '/../class-hgd-check.php';

class HGD_Check_StalePlugins extends HGD_Check {

	public function id() {
		return 'stale_plugins';
	}

	public function run( array $ctx ) {
		if ( ! isset( $ctx['stale_plugins'] ) || ! is_array( $ctx['stale_plugins'] ) ) {
			return new HGD_Result( $this->id(), HGD_Result::UNKNOWN, 'Plugin update dates unavailable.' );
		}
		$stale = $ctx['stale_plugins'];
		if ( ! $stale ) {
			return new HGD_Result( $this->id(), HGD_Result::OK, 'No long-abandoned plugins detected.' );
		}

		arsort( $stale );
		$rows = array();
		foreach ( array_slice( $stale, 0, 8, true ) as $name => $years ) {
			$rows[ $name ] = 'last update ' . round( (float) $years, 1 ) . ' year(s) ago';
		}

		return new HGD_Result(
			$this->id(),
			HGD_Result::WARN,
			count( $stale ) . ' plugin(s) have not been updated by their authors for over 2 years.',
			'Abandoned plugins stop receiving security fixes and break on new WordPress/PHP versions.',
			'Look for maintained alternatives, or confirm each one is still safe to keep.',
			$rows
		);
	}
}
