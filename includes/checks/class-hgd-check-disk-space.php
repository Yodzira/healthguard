<?php
/**
 * Check: disk space.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once __DIR__ . '/../class-hgd-check.php';

class HGD_Check_DiskSpace extends HGD_Check {

	public function id() {
		return 'disk_space';
	}

	public function run( array $ctx ) {
		if ( ! isset( $ctx['disk_free_bytes'] ) || ! is_numeric( $ctx['disk_free_bytes'] ) ) {
			return new HGD_Result( $this->id(), HGD_Result::UNKNOWN, 'Disk space unknown (hosting reports nothing).' );
		}

		$free = (int) $ctx['disk_free_bytes'];
		$gb   = round( $free / ( 1024 * 1024 * 1024 ), 1 );

		if ( $free < 1024 * 1024 * 1024 ) {
			return new HGD_Result(
				$this->id(),
				HGD_Result::CRIT,
				'Only ' . $gb . ' GB of disk space left.',
				'When the disk fills up, the database stops writing, backups fail and the site can go down.',
				'Free up space (old backups, logs, unused themes) or upgrade the hosting plan.'
			);
		}
		if ( $free < 5 * 1024 * 1024 * 1024 ) {
			return new HGD_Result(
				$this->id(),
				HGD_Result::WARN,
				'Disk space is getting low: ' . $gb . ' GB left.',
				'Backups and updates need headroom.',
				'Check large folders: backups, uploads, debug logs.',
				array( 'free_gb' => $gb )
			);
		}

		return new HGD_Result( $this->id(), HGD_Result::OK, 'Disk space is fine (' . $gb . ' GB free).' );
	}
}
