<?php
/**
 * Check: autoloaded options size.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once __DIR__ . '/../class-hgd-check.php';

class HGD_Check_AutoloadSize extends HGD_Check {

	public function id() {
		return 'autoload_size';
	}

	public function run( array $ctx ) {
		if ( ! isset( $ctx['autoload_bytes'] ) || ! is_numeric( $ctx['autoload_bytes'] ) ) {
			return new HGD_Result( $this->id(), HGD_Result::UNKNOWN, 'Autoload size unknown.' );
		}

		$bytes = (int) $ctx['autoload_bytes'];
		$kb    = (int) round( $bytes / 1024 );
		$rows  = array();
		foreach ( (array) ( isset( $ctx['autoload_top'] ) ? $ctx['autoload_top'] : array() ) as $name => $size ) {
			$rows[ $name ] = round( $size / 1024 ) . ' KB';
		}

		if ( $bytes > 3 * 1024 * 1024 ) {
			return new HGD_Result(
				$this->id(),
				HGD_Result::CRIT,
				'Autoloaded options are ' . $kb . ' KB — every page load carries this.',
				'This slows every single request including the admin.',
				'Review the largest options below; leftover data from deleted plugins can usually be deleted.',
				$rows
			);
		}
		if ( $bytes > 800 * 1024 ) {
			return new HGD_Result(
				$this->id(),
				HGD_Result::WARN,
				'Autoloaded options are ' . $kb . ' KB (healthy sites are usually under 800 KB).',
				'Grows invisibly when plugins store data with autoload on.',
				'Check the largest options below; delete leftovers of removed plugins.',
				$rows
			);
		}

		return new HGD_Result( $this->id(), HGD_Result::OK, 'Autoloaded options are ' . $kb . ' KB — fine.' );
	}
}
