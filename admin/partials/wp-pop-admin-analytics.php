<?php
/**
 * Analytics dashboard partial.
 *
 * @since   0.2.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$analytics = new Wp_Pop_Analytics();
$summary   = $analytics->get_summary();
$per_popup = $analytics->get_per_popup_summary( 30 );

// Date range (default last 30 days).
$range = isset( $_GET['range'] ) ? absint( $_GET['range'] ) : 30; // phpcs:ignore WordPress.Security.NonceVerification
$range = in_array( $range, array( 7, 30, 90 ), true ) ? $range : 30;

// Selected popup for chart.
$chart_popup_id = isset( $_GET['popup_id'] ) ? absint( $_GET['popup_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$chart_data     = $analytics->get_daily_data( $chart_popup_id, $range );

// Pass chart data to JS.
wp_localize_script(
	'wp-pop-admin',
	'wpPopAnalytics',
	array(
		'labels'      => $chart_data['labels'],
		'views'       => $chart_data['views'],
		'clicks'      => $chart_data['clicks'],
		'dismissals'  => $chart_data['dismissals'],
		'labelViews'  => __( 'Views', 'wp-pop' ),
		'labelClicks' => __( 'Clicks', 'wp-pop' ),
		'labelDismiss'=> __( 'Dismissals', 'wp-pop' ),
	)
);

wp_enqueue_script(
	'wp-pop-chartjs',
	WP_POP_PLUGIN_URL . 'build/analytics.js',
	array(),
	WP_POP_VERSION,
	true
);
?>
<style>
/* Scoped fallback — ensures cards render even if the admin stylesheet hasn't loaded yet */
.wp-pop-summary-cards { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
.wp-pop-card { flex: 1 1 140px; background: #fff; border: 1px solid #c3c4c7; border-top: 3px solid #c3c4c7; border-radius: 4px; padding: 1.25rem 1.5rem; text-align: center; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
.wp-pop-card--views { border-top-color: #2271b1; }
.wp-pop-card--clicks { border-top-color: #00a32a; }
.wp-pop-card--dismiss { border-top-color: #dba617; }
.wp-pop-card--ctr { border-top-color: #8c44ad; }
.wp-pop-card--convert { border-top-color: #1da1a1; }
.wp-pop-card__value { display: block; font-size: 2rem; font-weight: 700; line-height: 1.2; color: #1d2327; }
.wp-pop-card__label { display: block; font-size: .8rem; color: #646970; margin-top: .25rem; text-transform: uppercase; letter-spacing: .05em; }
.wp-pop-chart-section { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 1.5rem; margin-bottom: 2rem; }
.wp-pop-chart-controls { margin-bottom: 1rem; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.wp-pop-chart-controls form { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
</style>
<div class="wrap wp-pop-analytics">
	<h1><?php esc_html_e( 'WP Pop! Analytics', 'wp-pop' ); ?></h1>

	<!-- Summary cards -->
	<div class="wp-pop-summary-cards">
		<div class="wp-pop-card wp-pop-card--views">
			<span class="wp-pop-card__value"><?php echo esc_html( number_format_i18n( $summary['views'] ) ); ?></span>
			<span class="wp-pop-card__label"><?php esc_html_e( 'Total Views', 'wp-pop' ); ?></span>
		</div>
		<div class="wp-pop-card wp-pop-card--clicks">
			<span class="wp-pop-card__value"><?php echo esc_html( number_format_i18n( $summary['clicks'] ) ); ?></span>
			<span class="wp-pop-card__label"><?php esc_html_e( 'Total Clicks', 'wp-pop' ); ?></span>
		</div>
		<div class="wp-pop-card wp-pop-card--dismiss">
			<span class="wp-pop-card__value"><?php echo esc_html( number_format_i18n( $summary['dismissals'] ) ); ?></span>
			<span class="wp-pop-card__label"><?php esc_html_e( 'Dismissals', 'wp-pop' ); ?></span>
		</div>
		<div class="wp-pop-card wp-pop-card--ctr">
			<span class="wp-pop-card__value"><?php echo esc_html( $summary['conversion_rate'] . '%' ); ?></span>
			<span class="wp-pop-card__label"><?php esc_html_e( 'Conversion Rate', 'wp-pop' ); ?></span>
		</div>
		<div class="wp-pop-card wp-pop-card--convert">
			<span class="wp-pop-card__value"><?php echo esc_html( number_format_i18n( $summary['conversions'] ) ); ?></span>
			<span class="wp-pop-card__label"><?php esc_html_e( 'Conversions', 'wp-pop' ); ?></span>
		</div>
	</div>

	<!-- Chart -->
	<div class="wp-pop-chart-section">
		<div class="wp-pop-chart-controls">
			<form method="get" action="">
				<input type="hidden" name="post_type" value="wp_pop">
				<input type="hidden" name="page" value="wp-pop-analytics">

				<label for="wp-pop-popup-select"><?php esc_html_e( 'Popup:', 'wp-pop' ); ?></label>
				<select name="popup_id" id="wp-pop-popup-select">
					<option value="0"><?php esc_html_e( '— All Popups —', 'wp-pop' ); ?></option>
					<?php
					$popups = get_posts( array( 'post_type' => 'wp_pop', 'posts_per_page' => 100, 'post_status' => array( 'publish', 'draft' ) ) );
					foreach ( $popups as $popup ) {
						printf(
							'<option value="%d"%s>%s</option>',
							$popup->ID,
							selected( $chart_popup_id, $popup->ID, false ),
							esc_html( $popup->post_title )
						);
					}
					?>
				</select>

				<label for="wp-pop-range-select"><?php esc_html_e( 'Period:', 'wp-pop' ); ?></label>
				<select name="range" id="wp-pop-range-select">
					<option value="7"<?php selected( $range, 7 ); ?>><?php esc_html_e( 'Last 7 days', 'wp-pop' ); ?></option>
					<option value="30"<?php selected( $range, 30 ); ?>><?php esc_html_e( 'Last 30 days', 'wp-pop' ); ?></option>
					<option value="90"<?php selected( $range, 90 ); ?>><?php esc_html_e( 'Last 90 days', 'wp-pop' ); ?></option>
				</select>

				<?php submit_button( __( 'Update', 'wp-pop' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<canvas id="wp-pop-chart" height="100"></canvas>
	</div>

	<!-- Per-popup breakdown -->
	<?php if ( ! empty( $per_popup ) ) : ?>
	<h2><?php esc_html_e( 'Per-Popup Summary (Last 30 days)', 'wp-pop' ); ?></h2>
	<table class="widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Popup', 'wp-pop' ); ?></th>
				<th><?php esc_html_e( 'Views', 'wp-pop' ); ?></th>
				<th><?php esc_html_e( 'Clicks', 'wp-pop' ); ?></th>
				<th><?php esc_html_e( 'Dismissals', 'wp-pop' ); ?></th>
				<th><?php esc_html_e( 'CTR', 'wp-pop' ); ?></th>
				<th><?php esc_html_e( 'Subscribers', 'wp-pop' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $per_popup as $row ) : ?>
			<tr>
				<td>
					<a href="<?php echo esc_url( get_edit_post_link( $row['popup_id'] ) ); ?>"><?php echo esc_html( get_the_title( $row['popup_id'] ) ); ?></a>
					<?php if ( ! empty( $row['variants'] ) ) : ?>
					<details style="margin-top:4px">
						<summary style="cursor:pointer;color:#0073aa"><?php esc_html_e( 'A/B variants', 'wp-pop' ); ?></summary>
						<table style="margin-top:4px;width:100%">
							<thead><tr><th><?php esc_html_e( 'Variant', 'wp-pop' ); ?></th><th>Views</th><th>Clicks</th><th>CTR</th></tr></thead>
							<tbody>
								<?php foreach ( $row['variants'] as $v ) : ?>
								<tr>
									<td><?php echo esc_html( $v['variant_id'] ? $v['variant_id'] : __( 'Control', 'wp-pop' ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( $v['views'] ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( $v['clicks'] ) ); ?></td>
									<td><?php echo esc_html( $v['ctr'] . '%' ); ?></td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</details>
					<?php endif; ?>
				</td>
				<td><?php echo esc_html( number_format_i18n( $row['views'] ) ); ?></td>
				<td><?php echo esc_html( number_format_i18n( $row['clicks'] ) ); ?></td>
				<td><?php echo esc_html( number_format_i18n( $row['dismissals'] ) ); ?></td>
				<td><?php echo esc_html( $row['ctr'] . '%' ); ?></td>
				<td><?php echo esc_html( number_format_i18n( $row['subscribes'] ) ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
