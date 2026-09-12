<?php

use PHPUnit\Framework\TestCase;

/**
 * Scanner aggregation + anti-spam diff + digest text.
 */
class ScannerMailerTest extends TestCase {

	private function scan( array $ctx ) {
		return HGD_Scanner::scan( $ctx );
	}

	public function test_aggregate_takes_the_worst() {
		$ctx = array(
			'php_version'   => '8.2',
			'tls_days_left' => -1,  // crit.
			'autoload_bytes' => 900 * 1024, // warn.
		);
		$scan = $this->scan( $ctx );

		$this->assertSame( HGD_Result::CRIT, $scan['status'] );
		$this->assertSame( 1, $scan['counts']['crit'] );
		$this->assertSame( 1, $scan['counts']['warn'] );
	}

	public function test_clean_site_is_ok() {
		$ctx = array(
			'php_version'    => '8.2',
			'plugins'        => array( array( 'name' => 'A', 'tested_up_to' => '7.1' ) ),
			'wp_version'     => '7.1',
			'cron_events'    => array( array( 'hook' => 'h', 'time' => time() ) ),
			'tls_days_left'  => 60,
			'autoload_bytes' => 300 * 1024,
			'disk_free_bytes' => 40 * 1024 * 1024 * 1024,
			'stale_plugins'  => array(),
		);
		$scan = $this->scan( $ctx );

		$this->assertSame( HGD_Result::OK, $scan['status'] );
		$this->assertSame( 0, $scan['counts']['crit'] + $scan['counts']['warn'] );
	}

	public function test_diff_detects_changes_and_drives_anti_spam() {
		$first  = $this->scan( array( 'php_version' => '8.2', 'tls_days_left' => 60 ) );
		$same   = $this->scan( array( 'php_version' => '8.2', 'tls_days_left' => 55 ) ); // status ok|ok, headline differs by days! Both OK -> headline "another 55 days".
		$worsen = $this->scan( array( 'php_version' => '8.2', 'tls_days_left' => 5 ) );

		// First scan: nothing to diff against => changed.
		$this->assertFalse( HGD_Scanner::diff( array(), $first['results'] )['same'] );

		// Same statuses and headlines for stable checks (php) -> only tls changes.
		$diff_same   = HGD_Scanner::diff( $this->to_arrays( $first ), $this->scan( array( 'php_version' => '8.2', 'tls_days_left' => 60 ) )['results'] );
		$diff_worsen = HGD_Scanner::diff( $this->to_arrays( $first ), $worsen['results'] );

		$this->assertSame( array(), HGD_Scanner::diff( $this->to_arrays( $first ), $this->to_arrays( $first ) )['changed'] );
		$this->assertCount( 1, $diff_worsen['changed'] );
		$this->assertSame( 'tls_expiry', $diff_worsen['changed'][0]->check );
	}

	public function test_digest_lists_problems_and_skips_ok() {
		$scan  = $this->scan( array( 'php_version' => '8.2', 'tls_days_left' => 5, 'autoload_bytes' => 900 * 1024 ) );
		$text  = HGD_Mailer::digest( $scan, 'mysite.com', true );

		$this->assertStringContainsString( 'mysite.com', $text );
		$this->assertStringContainsString( '[WARN]', $text );
		$this->assertStringContainsString( 'ok: 1, warn: 2', $text );
		$this->assertStringNotContainsString( '[OK]', $text ); // ok rows are not listed.
	}

	private function to_arrays( array $scan ) {
		return array_map(
			static function ( $r ) {
				return $r->to_array();
			},
			$scan['results']
		);
	}
}
