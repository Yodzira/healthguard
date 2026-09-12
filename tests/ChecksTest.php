<?php

use PHPUnit\Framework\TestCase;

/**
 * Every check: positive (violation), negative (fine), edge (missing data).
 */
class ChecksTest extends TestCase {

	public function test_php_version_branches() {
		$c = new HGD_Check_PhpVersion();
		$this->assertSame( HGD_Result::CRIT, $c->run( array( 'php_version' => '7.2', 'php_min_required' => '7.4' ) )->status );
		$this->assertSame( HGD_Result::WARN, $c->run( array( 'php_version' => '7.4' ) )->status );
		$this->assertSame( HGD_Result::OK, $c->run( array( 'php_version' => '8.2' ) )->status );
		$this->assertSame( HGD_Result::UNKNOWN, $c->run( array() )->status );
	}

	public function test_plugin_tested_up_to() {
		$c = new HGD_Check_PluginTestedUpTo();
		$plugins = array(
			array( 'name' => 'A', 'tested_up_to' => '7.1' ),
			array( 'name' => 'B', 'tested_up_to' => '6.5' ),
		);
		$r = $c->run( array( 'plugins' => $plugins, 'wp_version' => '7.1' ) );
		$this->assertSame( HGD_Result::WARN, $r->status );
		$this->assertSame( 1, count( $r->rows ) );
		$this->assertArrayHasKey( 'B', $r->rows );

		$this->assertSame( HGD_Result::OK, $c->run( array( 'plugins' => array( array( 'name' => 'A', 'tested_up_to' => '7.1' ) ), 'wp_version' => '7.1' ) )->status );
		$this->assertSame( HGD_Result::UNKNOWN, $c->run( array( 'plugins' => $plugins ) )->status );
	}

	public function test_cron_overdue() {
		$c = new HGD_Check_CronOverdue();
		$now = 1000000;
		$events = array(
			array( 'hook' => 'fresh_hook', 'time' => $now - 100 ),
			array( 'hook' => 'stale_hook', 'time' => $now - 3 * 86400 ),
			array( 'hook' => 'stale_hook', 'time' => $now - 2 * 86400 ),
		);
		$r = $c->run( array( 'cron_events' => $events, 'now' => $now ) );
		$this->assertSame( HGD_Result::WARN, $r->status );
		$this->assertArrayHasKey( 'stale_hook', $r->rows );
		$this->assertStringContainsString( 'x2', implode( ' ', $r->rows ) );
		$this->assertArrayNotHasKey( 'fresh_hook', $r->rows );

		$this->assertSame( HGD_Result::OK, $c->run( array( 'cron_events' => array( array( 'hook' => 'a', 'time' => $now - 100 ) ), 'now' => $now ) )->status );
		$this->assertSame( HGD_Result::UNKNOWN, $c->run( array( 'cron_events' => array(), 'now' => $now ) )->status );
	}

	public function test_tls_expiry() {
		$c = new HGD_Check_TlsExpiry();
		$this->assertSame( HGD_Result::CRIT, $c->run( array( 'tls_days_left' => -1 ) )->status );
		$this->assertSame( HGD_Result::WARN, $c->run( array( 'tls_days_left' => 7 ) )->status );
		$this->assertSame( HGD_Result::OK, $c->run( array( 'tls_days_left' => 90 ) )->status );
		$this->assertSame( HGD_Result::UNKNOWN, $c->run( array( 'tls_days_left' => null ) )->status );
	}

	public function test_autoload_size() {
		$c = new HGD_Check_AutoloadSize();
		$this->assertSame( HGD_Result::CRIT, $c->run( array( 'autoload_bytes' => 4 * 1024 * 1024 ) )->status );
		$this->assertSame( HGD_Result::WARN, $c->run( array( 'autoload_bytes' => 900 * 1024 ) )->status );
		$this->assertSame( HGD_Result::OK, $c->run( array( 'autoload_bytes' => 300 * 1024 ) )->status );
		$this->assertSame( HGD_Result::UNKNOWN, $c->run( array() )->status );

		$r = $c->run( array( 'autoload_bytes' => 900 * 1024, 'autoload_top' => array( 'bad_option' => 500 * 1024 ) ) );
		$this->assertArrayHasKey( 'bad_option', $r->rows );
	}

	public function test_disk_space() {
		$c = new HGD_Check_DiskSpace();
		$gb = 1024 * 1024 * 1024;
		$this->assertSame( HGD_Result::CRIT, $c->run( array( 'disk_free_bytes' => (int) ( 0.5 * $gb ) ) )->status );
		$this->assertSame( HGD_Result::WARN, $c->run( array( 'disk_free_bytes' => 3 * $gb ) )->status );
		$this->assertSame( HGD_Result::OK, $c->run( array( 'disk_free_bytes' => 50 * $gb ) )->status );
		$this->assertSame( HGD_Result::UNKNOWN, $c->run( array() )->status );
	}

	public function test_stale_plugins() {
		$c = new HGD_Check_StalePlugins();
		$r = $c->run( array( 'stale_plugins' => array( 'Old Slider' => 3.4, 'Older Widget' => 5.0 ) ) );
		$this->assertSame( HGD_Result::WARN, $r->status );
		$this->assertCount( 2, $r->rows );

		$this->assertSame( HGD_Result::OK, $c->run( array( 'stale_plugins' => array() ) )->status );
	}

	public function test_every_check_is_unknown_without_data() {
		foreach ( HGD_Scanner::default_checks() as $check ) {
			$r = $check->run( array() );
			$this->assertSame( HGD_Result::UNKNOWN, $r->status, $check->id() );
			$this->assertNotSame( '', $r->headline, $check->id() );
		}
	}
}
