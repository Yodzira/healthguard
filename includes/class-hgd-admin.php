<?php
/**
 * Admin report page.
 *
 * @package HealthGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HGD_Admin {

	public static function menu() {
		add_menu_page( 'Health Guard', 'Health Guard', 'manage_options', 'healthguard', array( __CLASS__, 'render' ), 'dashicons-shield-alt' );
	}

	public static function handle_run_scan() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'healthguard' ) );
		}
		check_admin_referer( 'hgd_run_scan' );
		HGD_Plugin::run_scan();
		wp_safe_redirect( admin_url( 'admin.php?page=healthguard&scanned=1' ) );
		exit;
	}

	public static function render() {
		$just_scanned = isset( $_GET['scanned'] ) ? sanitize_key( wp_unslash( $_GET['scanned'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display flag.
		$last_payload = HGD_Store::previous_results();
		?>
		<div class="wrap">
			<h1>Health Guard</h1>

			<?php if ( '1' === $just_scanned ) : ?>
				<div class="notice notice-success is-dismissible"><p>Scan finished.</p></div>
			<?php endif; ?>

			<p>
				<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=hgd_run_scan' ), 'hgd_run_scan' ) ); ?>">Run scan now</a>
				<span class="description" style="margin-left:12px">Weekly automatic check: cron <code>hgd_weekly_scan</code></span>
			</p>

			<?php if ( ! $last_payload ) : ?>
				<p><em>Not scanned yet — press “Run scan now”.</em></p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:1000px">
					<thead><tr><th style="width:70px">Status</th><th style="width:180px">Check</th><th>Finding</th><th style="width:32%">What to do</th></tr></thead>
					<tbody>
					<?php foreach ( $last_payload as $row ) : ?>
						<tr>
							<td><strong><?php
								$colors = array( 'ok' => '#00a32a', 'warn' => '#dba617', 'crit' => '#b32d2e', 'unknown' => '#8c8f94' );
								echo '<span style="color:' . esc_attr( isset( $colors[ $row['status'] ] ) ? $colors[ $row['status'] ] : '#8c8f94' ) . '">' . esc_html( strtoupper( $row['status'] ) ) . '</span>';
							?></strong></td>
							<td><code><?php echo esc_html( $row['check'] ); ?></code></td>
							<td>
								<?php echo esc_html( $row['headline'] ); ?>
								<?php if ( ! empty( $row['details'] ) ) : ?><br><span class="description"><?php echo esc_html( $row['details'] ); ?></span><?php endif; ?>
								<?php if ( ! empty( $row['rows'] ) && is_array( $row['rows'] ) ) : ?>
									<ul style="margin:6px 0 0 18px">
										<?php foreach ( array_slice( $row['rows'], 0, 8, true ) as $k => $v ) : ?>
											<li><code><?php echo esc_html( $k ); ?></code> — <?php echo esc_html( $v ); ?></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( isset( $row['hint'] ) ? $row['hint'] : '' ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<h2>History</h2>
				<table class="widefat striped" style="max-width:600px">
					<thead><tr><th>When</th><th>Status</th><th>Duration</th></tr></thead>
					<tbody>
					<?php foreach ( HGD_Store::history() as $h ) : ?>
						<tr>
							<td><?php echo esc_html( $h['started_at'] ); ?></td>
							<td><?php echo esc_html( strtoupper( $h['status'] ) ); ?></td>
							<td><?php echo esc_html( $h['duration_ms'] ); ?> ms</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
