<?php
/**
 * Check result value object + check base class.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One check outcome.
 */
class HGD_Result {

	const OK = 'ok';
	const WARN = 'warn';
	const CRIT = 'crit';
	const UNKNOWN = 'unknown';

	/** @var string Check id. */
	public $check;
	/** @var string ok|warn|crit|unknown. */
	public $status;
	/** @var string Human headline (EN). */
	public $headline;
	/** @var string Details for the report. */
	public $details;
	/** @var string How to fix. */
	public $hint;
	/** @var array Extra structured rows (label => value). */
	public $rows;

	public function __construct( $check, $status, $headline, $details = '', $hint = '', array $rows = array() ) {
		$this->check    = (string) $check;
		$this->status   = (string) $status;
		$this->headline = (string) $headline;
		$this->details  = (string) $details;
		$this->hint     = (string) $hint;
		$this->rows     = $rows;
	}

	public function to_array() {
		return array(
			'check'    => $this->check,
			'status'   => $this->status,
			'headline' => $this->headline,
			'details'  => $this->details,
			'hint'     => $this->hint,
			'rows'     => $this->rows,
		);
	}
}

/**
 * A check receives a plain data context (injected by HGD_WpInfo) so the
 * whole core stays unit-testable without WordPress.
 */
abstract class HGD_Check {

	abstract public function id();

	/**
	 * Run against the injected context. Missing data => status unknown.
	 *
	 * @param array $ctx Context (see HGD_WpInfo::context()).
	 * @return HGD_Result
	 */
	abstract public function run( array $ctx );
}
