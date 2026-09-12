<?php
/**
 * Check: plugins whose tested-up-to is below current WordPress.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once __DIR__ . '/../class-hgd-check.php';

class HGD_Check_PluginTestedUpTo extends HGD_Check {

	public function id() {
		return 'plugin_tested_up_to';
	}

	public function run( array $ctx ) {
		$plugins = isset( $ctx['plugins'] ) && is_array( $ctx['plugins'] ) ? $ctx['plugins'] : array();
		$wp      = isset( $ctx['wp_version'] ) ? (string) $ctx['wp_version'] : '';
		if ( ! $plugins || '' === $wp ) {
			return new HGD_Result( $this->id(), HGD_Result::UNKNOWN, 'Plugin compatibility data unavailable.' );
		}

		$layers = array();
		foreach ( $plugins as $p ) {
			if ( empty( $p['tested_up_to'] ) ) {
				continue;
			}
			if ( version_compare( (string) $p['tested_up_to'], $wp, '<' ) ) {
				$layers[] = array(
					'name'     => (string) $p['name'],
					'tested'   => (string) $p['tested_up_to'],
					'wp'       => $wp,
				);
			}
		}

		if ( ! $layers ) {
			return new HGD_Result( $this->id(), HGD_Result::OK, 'All plugins declare compatibility with WP ' . $wp . '.' );
		}

		$rows = array();
		foreach ( array_slice( $layers, 0, 8 ) as $l ) {
			$rows[ $l['name'] ] = 'tested to ' . $l['tested'] . ', WP is ' . $l['wp'];
		}

		return new HGD_Result(
			$this->id(),
			HGD_Result::WARN,
			count( $layers ) . ' plugin(s) not yet declared compatible with WP ' . $wp . '.',
			'Not necessarily broken — but the author has not confirmed the newer WordPress.',
			'Check plugin changelogs before updating WordPress further; delay WP updates until these catch up.',
			$rows
		);
	}
}
