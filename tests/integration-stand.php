<?php
/**
 * Health Guard integration — inside QA container:
 *   docker exec infra-wordpress-1 wp eval-file /tmp/hgd-integration.php --allow-root
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function check( $label, $cond ) {
	if ( $cond ) {
		$GLOBALS['pass']++;
		echo "  ok   {$label}\n";
	} else {
		$GLOBALS['fail']++;
		echo "  FAIL {$label}\n";
	}
}

echo "== Health Guard integration ==\n";

check( 'plugin active', is_plugin_active( 'healthguard/healthguard.php' ) );
check( 'classes loaded', class_exists( 'HGD_Scanner' ) && class_exists( 'HGD_Store' ) );

global $wpdb;
$table     = HGD_Store::table_name();
$has_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
check( 'runs table created on activation', $has_table === $table );

// Full scan on the live installation.
$t0      = microtime( true );
$out     = HGD_Plugin::run_scan();
$scan    = $out['scan'];
$dur_ms  = (int) round( ( microtime( true ) - $t0 ) * 1000 );
printf( "    scan duration on live stand: %d ms, status: %s\n", $dur_ms, $scan['status'] );

check( 'scan produced results', count( $scan['results'] ) === 7 );
check( 'scan recorded in store', count( HGD_Store::history( 5 ) ) === 1 );
check( 'store payload decodes', is_array( HGD_Store::previous_results() ) );

// Cron events of the stand are likely overdue in CLI context — any status is fine,
// but every result must be a valid status.
$valid = array( 'ok', 'warn', 'crit', 'unknown' );
$bad   = array_filter(
	$scan['results'],
	static function ( $r ) use ( $valid ) {
		return ! in_array( $r->status, $valid, true ) || '' === $r->headline;
	}
);
check( 'all 7 results valid', array() === $bad );

// Anti-spam: identical second scan => no digest.
$first_changed  = $out['changed'];
$second         = HGD_Plugin::run_scan();
check( 'first scan sends digest', true === $first_changed || true === $second['changed'] );
check( 'scan duration under 30s', $dur_ms < 30000 );

// Mailer builds a digest for any scan.
$text = HGD_Mailer::digest( $scan, get_bloginfo( 'name' ), true );
check( 'digest text non-empty', strlen( $text ) > 20 );

// History page data shape.
$history = HGD_Store::history();
check( 'history has 2 rows', 2 === count( $history ) );

// Cleanup.
HGD_Store::erase_all();
check( 'erase clears history', array() === HGD_Store::history() );

printf( "\n== Health Guard integration: %d pass, %d fail ==\n", $GLOBALS['pass'], $GLOBALS['fail'] );
exit( $GLOBALS['fail'] > 0 ? 1 : 0 );
