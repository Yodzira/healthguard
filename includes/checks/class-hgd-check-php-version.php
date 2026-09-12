<?php
/**
 * Check: PHP version vs requirements.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once __DIR__ . '/../class-hgd-check.php';

class HGD_Check_PhpVersion extends HGD_Check {

	public function id() {
		return 'php_version';
	}

	public function run( array $ctx ) {
		$php    = isset( $ctx['php_version'] ) ? (string) $ctx['php_version'] : '';
		$min    = isset( $ctx['php_min_required'] ) ? (string) $ctx['php_min_required'] : '';
		$warnAt = isset( $ctx['php_warn_below'] ) ? (string) $ctx['php_warn_below'] : '8.0';

		if ( '' === $php ) {
			return new HGD_Result( $this->id(), HGD_Result::UNKNOWN, 'PHP version unknown.' );
		}

		if ( '' !== $min && version_compare( $php, $min, '<' ) ) {
			return new HGD_Result(
				$this->id(),
				HGD_Result::CRIT,
				'PHP ' . $php . ' is below the minimum required (' . $min . ') by installed plugins.',
				'Some plugins may fatal on this PHP version.',
				'Upgrade PHP in your hosting control panel to at least ' . $min . '.'
			);
		}

		if ( version_compare( $php, $warnAt, '<' ) ) {
			return new HGD_Result(
				$this->id(),
				HGD_Result::WARN,
				'PHP ' . $php . ' is an aging branch.',
				'Plugins are dropping support for it; the next major WP release may require a newer PHP.',
				'Plan an upgrade to PHP ' . $warnAt . '+ in your hosting control panel.',
				array( 'current' => $php, 'recommended' => $warnAt . '+' )
			);
		}

		return new HGD_Result( $this->id(), HGD_Result::OK, 'PHP ' . $php . ' is fine.' );
	}
}
