<?php
/**
 * Check: TLS certificate expiry.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once __DIR__ . '/../class-hgd-check.php';

class HGD_Check_TlsExpiry extends HGD_Check {

	public function id() {
		return 'tls_expiry';
	}

	public function run( array $ctx ) {
		if ( ! isset( $ctx['tls_days_left'] ) || ! is_numeric( $ctx['tls_days_left'] ) ) {
			return new HGD_Result( $this->id(), HGD_Result::UNKNOWN, 'Certificate expiry unknown (could not read it).' );
		}

		$days = (int) $ctx['tls_days_left'];
		if ( $days < 0 ) {
			return new HGD_Result(
				$this->id(),
				HGD_Result::CRIT,
				'The site certificate has EXPIRED.',
				'Visitors see a browser security warning; payments and logins are affected.',
				'Renew the certificate with your host/CDN immediately.'
			);
		}
		if ( $days <= 21 ) {
			return new HGD_Result(
				$this->id(),
				HGD_Result::WARN,
				'The site certificate expires in ' . $days . ' day(s).',
				'After expiry, browsers show a security warning to every visitor.',
				'Renew the certificate now (hosting panel, Let\'s Encrypt auto-renewal, or CDN).',
				array( 'days_left' => $days )
			);
		}

		return new HGD_Result( $this->id(), HGD_Result::OK, 'Certificate is valid for another ' . $days . ' days.' );
	}
}
