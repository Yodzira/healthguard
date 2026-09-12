<?php
/**
 * Check: overdue cron events.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once __DIR__ . '/../class-hgd-check.php';

class HGD_Check_CronOverdue extends HGD_Check {

	const GRACE_SECONDS = 86400; // 24 h.

	public function id() {
		return 'cron_overdue';
	}

	public function run( array $ctx ) {
		$events = isset( $ctx['cron_events'] ) && is_array( $ctx['cron_events'] ) ? $ctx['cron_events'] : array();
		$now    = isset( $ctx['now'] ) ? (int) $ctx['now'] : time();
		if ( ! $events ) {
			return new HGD_Result( $this->id(), HGD_Result::UNKNOWN, 'No scheduled events found (or cron data unavailable).' );
		}

		$overdue = array();
		foreach ( $events as $e ) {
			if ( isset( $e['time'] ) && $now - (int) $e['time'] > self::GRACE_SECONDS ) {
				$overdue[] = (string) $e['hook'];
			}
		}

		if ( ! $overdue ) {
			return new HGD_Result( $this->id(), HGD_Result::OK, 'All scheduled tasks run on time.' );
		}

		$rows = array();
		foreach ( array_count_values( $overdue ) as $hook => $count ) {
			$rows[ $hook ] = 'overdue x' . $count;
		}

		return new HGD_Result(
			$this->id(),
			HGD_Result::WARN,
			count( $overdue ) . ' scheduled task(s) missed their run time by more than 24 h.',
			'Background jobs (backups, cleanup, this plugin\'s own scans) are not running — often a broken cron.',
			'If this repeats every week, ask your host whether WP-Cron is working or switch to a system cron.',
			array_slice( $rows, 0, 5, true )
		);
	}
}
