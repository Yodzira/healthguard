<?php
/**
 * Scanner: run all checks, aggregate status, diff against previous snapshot.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pure aggregation logic — unit-testable.
 */
class HGD_Scanner {

	/**
	 * All checks in report order.
	 *
	 * @return HGD_Check[]
	 */
	public static function default_checks() {
		$checks = array(
			new HGD_Check_PhpVersion(),
			new HGD_Check_PluginTestedUpTo(),
			new HGD_Check_CronOverdue(),
			new HGD_Check_TlsExpiry(),
			new HGD_Check_AutoloadSize(),
			new HGD_Check_DiskSpace(),
			new HGD_Check_StalePlugins(),
		);

		/**
		 * Extra checks (Health Guard Pro or hosting-specific integrations).
		 *
		 * @param HGD_Check[] $checks Check instances.
		 */
		return apply_filters( 'siteguard_checks', $checks );
	}

	/**
	 * Run all checks over a context.
	 *
	 * @param array $ctx Data context.
	 * @return array {results: HGD_Result[], status: string, counts: array}
	 */
	public static function scan( array $ctx ) {
		$results = array();
		foreach ( self::default_checks() as $check ) {
			$results[] = $check->run( $ctx );
		}

		return array(
			'results' => $results,
			'status'  => self::aggregate( $results ),
			'counts'  => self::counts( $results ),
		);
	}

	/**
	 * Overall status = worst result (unknown counts as ok for the flag,
	 * but is shown in the report).
	 *
	 * @param HGD_Result[] $results Results.
	 * @return string crit|warn|ok
	 */
	public static function aggregate( array $results ) {
		$status = HGD_Result::OK;
		foreach ( $results as $r ) {
			if ( HGD_Result::CRIT === $r->status ) {
				return HGD_Result::CRIT;
			}
			if ( HGD_Result::WARN === $r->status ) {
				$status = HGD_Result::WARN;
			}
		}

		return $status;
	}

	/**
	 * Counts by status.
	 *
	 * @param HGD_Result[] $results Results.
	 * @return array
	 */
	public static function counts( array $results ) {
		$counts = array( 'ok' => 0, 'warn' => 0, 'crit' => 0, 'unknown' => 0 );
		foreach ( $results as $r ) {
			if ( isset( $counts[ $r->status ] ) ) {
				$counts[ $r->status ]++;
			}
		}

		return $counts;
	}

	/**
	 * Diff two scan payloads: which results changed status or headline.
	 * Drives the anti-spam digest (no change => no email).
	 *
	 * @param array $prev Results of previous scan (arrays from to_array()).
	 * @param array $next Current results.
	 * @return array {changed: HGD_Result[], same: bool}
	 */
	public static function diff( array $prev, array $next ) {
		$prev_map = array();
		foreach ( $prev as $r ) {
			$prev_map[ $r['check'] ] = $r['status'] . '|' . $r['headline'];
		}

		$changed = array();
		foreach ( $next as $r ) {
			$arr = is_array( $r ) ? $r : $r->to_array();
			$key = $arr['check'];
			if ( ! isset( $prev_map[ $key ] ) || $prev_map[ $key ] !== $arr['status'] . '|' . $arr['headline'] ) {
				$changed[] = is_array( $r ) ? new HGD_Result( $arr['check'], $arr['status'], $arr['headline'] ) : $r;
			}
		}

		return array(
			'changed' => $changed,
			'same'    => array() === $changed,
		);
	}
}
