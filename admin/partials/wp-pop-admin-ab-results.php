<?php

/**
 * A/B Test Results admin page.
 *
 * Displays side-by-side statistics for every published A/B test,
 * along with status badges, scope warnings, and schedule/limit info.
 *
 * @since   0.3.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$ab_testing = new Wp_Pop_Ab_Testing();
$days       = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$days       = in_array( $days, array( 7, 14, 30, 60, 90 ), true ) ? $days : 30;

$tests = $ab_testing->get_active_tests();

// Status label map.
$status_labels = array(
	'active'        => __( 'Active', 'wp-pop' ),
	'scheduled'     => __( 'Scheduled', 'wp-pop' ),
	'ended'         => __( 'Ended', 'wp-pop' ),
	'limit_reached' => __( 'Limit Reached', 'wp-pop' ),
);
$status_colors = array(
	'active'        => '#00a32a',
	'scheduled'     => '#3858e9',
	'ended'         => '#757575',
	'limit_reached' => '#dba617',
);
?>
<div class="wrap wp-pop-ab-results">
	<h1>
		<?php esc_html_e( 'A/B Test Results', 'wp-pop' ); ?>
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wp_pop_ab_test' ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Add New Test', 'wp-pop' ); ?>
		</a>
	</h1>

	<form method="get" style="margin-bottom:16px;">
		<input type="hidden" name="post_type" value="wp_pop">
		<input type="hidden" name="page" value="wp-pop-ab-results">
		<?php esc_html_e( 'Period:', 'wp-pop' ); ?>
		<select name="days" onchange="this.form.submit()">
			<?php
			foreach ( array( 7, 14, 30, 60, 90 ) as $d ) {
				printf(
					'<option value="%d"%s>%s</option>',
					$d,
					selected( $days, $d, false ),
					/* translators: %d = number of days */
					sprintf( esc_html__( 'Last %d days', 'wp-pop' ), $d )
				);
			}
			?>
		</select>
	</form>

	<?php if ( empty( $tests ) ) : ?>
		<p>
			<?php
			printf(
				/* translators: %s = link to create a new A/B test */
				wp_kses(
					__( 'No A/B tests found. <a href="%s">Create your first test</a> to get started.', 'wp-pop' ),
					array( 'a' => array( 'href' => array() ) )
				),
				esc_url( admin_url( 'post-new.php?post_type=wp_pop_ab_test' ) )
			);
			?>
		</p>
	<?php else : ?>
		<style>
			.wp-pop-ab-results .ab-test-card { background:#fff; border:1px solid #ddd; border-radius:6px; padding:20px 24px; margin-bottom:24px; max-width:900px; }
			.wp-pop-ab-results .ab-card-header { display:flex; align-items:center; gap:10px; margin-bottom:6px; }
			.wp-pop-ab-results .ab-card-header h2 { margin:0; font-size:16px; }
			.wp-pop-ab-results .ab-card-header h2 a { text-decoration:none; }
			.wp-pop-ab-results .ab-status-badge { display:inline-block; border-radius:3px; padding:2px 8px; font-size:11px; font-weight:700; color:#fff; white-space:nowrap; }
			.wp-pop-ab-results .ab-test-meta { font-size:12px; color:#757575; margin:0 0 10px; }
			.wp-pop-ab-results .ab-test-meta span { margin-right:12px; }
			.wp-pop-ab-results .ab-warn { background:#fff8e5; border-left:4px solid #dba617; padding:8px 12px; margin-bottom:10px; font-size:13px; }
			.wp-pop-ab-results .ab-compare-table { width:100%; border-collapse:collapse; margin-top:8px; }
			.wp-pop-ab-results .ab-compare-table th, .wp-pop-ab-results .ab-compare-table td { padding:8px 12px; text-align:left; border-bottom:1px solid #f0f0f0; }
			.wp-pop-ab-results .ab-compare-table th { background:#f9f9f9; font-weight:600; white-space:nowrap; }
			.wp-pop-ab-results .variant-badge { display:inline-block; background:#3858e9; color:#fff; border-radius:3px; padding:1px 7px; font-size:11px; font-weight:700; margin-right:6px; }
			.wp-pop-ab-results .variant-badge.b { background:#d63638; }
			.wp-pop-ab-results .winner { color:#00a32a; font-weight:700; }
			.wp-pop-ab-results .no-data { color:#757575; font-style:italic; font-size:13px; }
		</style>

		<?php foreach ( $tests as $test ) : ?>
			<?php
			$test_data   = $ab_testing->get_test_data( $test->ID );
			$popup_a_id  = $test_data['popup_a'];
			$popup_b_id  = $test_data['popup_b'];
			$weight_a    = $test_data['weight_a'];
			$status      = $ab_testing->get_test_status( $test_data );
			$comparison  = $ab_testing->get_comparison( $test->ID, $days );
			$edit_url    = get_edit_post_link( $test->ID );

			// Scope mismatch warning.
			$scope_a = $popup_a_id ? ( get_post_meta( $popup_a_id, '_wp_pop_display_scope', true ) ?: 'sitewide' ) : null;
			$scope_b = $popup_b_id ? ( get_post_meta( $popup_b_id, '_wp_pop_display_scope', true ) ?: 'sitewide' ) : null;
			$scope_mismatch = ( $scope_a && $scope_b && $scope_a !== $scope_b );

			// Build human-readable scope label.
			$scope_labels = array( 'sitewide' => __( 'Sitewide', 'wp-pop' ), 'specific' => __( 'Specific pages', 'wp-pop' ), 'url_pattern' => __( 'URL pattern', 'wp-pop' ) );
			$scope_label  = $scope_labels[ $test_data['scope'] ] ?? $test_data['scope'];

			$badge_color = $status_colors[ $status ] ?? '#555';
			$badge_label = $status_labels[ $status ] ?? $status;
			?>
			<div class="ab-test-card">
				<div class="ab-card-header">
					<h2>
						<a href="<?php echo esc_url( (string) $edit_url ); ?>">
							<?php echo esc_html( get_the_title( $test ) ); ?>
						</a>
					</h2>
					<span class="ab-status-badge" style="background:<?php echo esc_attr( $badge_color ); ?>;">
						<?php echo esc_html( $badge_label ); ?>
					</span>
				</div>

				<p class="ab-test-meta">
					<span>
						<strong><?php esc_html_e( 'A:', 'wp-pop' ); ?></strong>
						<?php if ( $popup_a_id ) : ?>
							<a href="<?php echo esc_url( (string) get_edit_post_link( $popup_a_id ) ); ?>"><?php echo esc_html( get_the_title( $popup_a_id ) ); ?></a>
						<?php else : ?>
							<?php esc_html_e( '(not set)', 'wp-pop' ); ?>
						<?php endif; ?>
					</span>
					<span>
						<strong><?php esc_html_e( 'B:', 'wp-pop' ); ?></strong>
						<?php if ( $popup_b_id ) : ?>
							<a href="<?php echo esc_url( (string) get_edit_post_link( $popup_b_id ) ); ?>"><?php echo esc_html( get_the_title( $popup_b_id ) ); ?></a>
						<?php else : ?>
							<?php esc_html_e( '(not set)', 'wp-pop' ); ?>
						<?php endif; ?>
					</span>
					<span><strong><?php esc_html_e( 'Split:', 'wp-pop' ); ?></strong> <?php echo esc_html( $weight_a . '% / ' . ( 100 - $weight_a ) . '%' ); ?></span>
					<span><strong><?php esc_html_e( 'Scope:', 'wp-pop' ); ?></strong> <?php echo esc_html( $scope_label ); ?></span>
					<?php if ( $test_data['start_date'] || $test_data['end_date'] ) : ?>
						<span>
							<strong><?php esc_html_e( 'Schedule:', 'wp-pop' ); ?></strong>
							<?php
							$sched = array();
							if ( $test_data['start_date'] ) {
								/* translators: %s = date */
								$sched[] = sprintf( __( 'from %s', 'wp-pop' ), esc_html( $test_data['start_date'] ) );
							}
							if ( $test_data['end_date'] ) {
								/* translators: %s = date */
								$sched[] = sprintf( __( 'to %s', 'wp-pop' ), esc_html( $test_data['end_date'] ) );
							}
							echo esc_html( implode( ' ', $sched ) );
							?>
						</span>
					<?php endif; ?>
					<?php if ( $test_data['max_impressions'] > 0 ) : ?>
						<span>
							<strong><?php esc_html_e( 'Max impressions:', 'wp-pop' ); ?></strong>
							<?php echo esc_html( number_format_i18n( $test_data['max_impressions'] ) ); ?>
						</span>
					<?php endif; ?>
				</p>

				<?php if ( $scope_mismatch ) : ?>
					<div class="ab-warn">
						&#9888; <?php
						printf(
							/* translators: 1: scope of popup A, 2: scope of popup B */
							esc_html__( 'Popup A has individual targeting set to "%1$s" and Popup B to "%2$s". The test scope above overrides both on matching pages, but on pages outside the test scope Popup A may still appear independently, which can affect its overall stats.', 'wp-pop' ),
							esc_html( $scope_labels[ $scope_a ] ?? $scope_a ),
							esc_html( $scope_labels[ $scope_b ] ?? $scope_b )
						);
						?>
						<a href="<?php echo esc_url( (string) $edit_url ); ?>"><?php esc_html_e( 'Fix targeting →', 'wp-pop' ); ?></a>
					</div>
				<?php endif; ?>

				<?php if ( 'scheduled' === $status ) : ?>
					<p class="no-data">
						<?php
						/* translators: %s = start date */
						printf( esc_html__( 'This test is scheduled to start on %s.', 'wp-pop' ), '<strong>' . esc_html( $test_data['start_date'] ) . '</strong>' );
						?>
					</p>
				<?php elseif ( empty( $comparison ) ) : ?>
					<p class="no-data"><?php esc_html_e( 'No data recorded yet for this test.', 'wp-pop' ); ?></p>
				<?php else : ?>
					<?php
					$ctrs    = array_column( $comparison, 'ctr' );
					$max_ctr = max( $ctrs );
					?>
					<table class="ab-compare-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Variant', 'wp-pop' ); ?></th>
								<th><?php esc_html_e( 'Views', 'wp-pop' ); ?></th>
								<th><?php esc_html_e( 'Clicks', 'wp-pop' ); ?></th>
								<th><?php esc_html_e( 'Dismissals', 'wp-pop' ); ?></th>
								<th><?php esc_html_e( 'CTR', 'wp-pop' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $comparison as $row ) : ?>
								<?php
								$is_winner = ( $row['views'] > 0 && (float) $row['ctr'] === $max_ctr );
								$badge_cls = 'a' === $row['variant'] ? '' : ' b';
								?>
								<tr>
									<td>
										<span class="variant-badge<?php echo esc_attr( $badge_cls ); ?>">
											<?php echo esc_html( strtoupper( $row['variant'] ) ); ?>
										</span>
										<a href="<?php echo esc_url( (string) get_edit_post_link( $row['popup_id'] ) ); ?>">
											<?php echo esc_html( $row['label'] ); ?>
										</a>
									</td>
									<td><?php echo esc_html( number_format_i18n( $row['views'] ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( $row['clicks'] ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( $row['dismissals'] ) ); ?></td>
									<td class="<?php echo $is_winner ? 'winner' : ''; ?>">
										<?php echo esc_html( $row['ctr'] . '%' ); ?>
										<?php if ( $is_winner ) : ?>&#9650;<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<?php if ( 'ended' === $status ) : ?>
						<p style="margin:10px 0 0;font-size:12px;color:#757575;">
							<?php esc_html_e( 'This test ended — data shown is from the full test period.', 'wp-pop' ); ?>
						</p>
					<?php elseif ( 'limit_reached' === $status ) : ?>
						<p style="margin:10px 0 0;font-size:12px;color:#dba617;">
							<?php esc_html_e( 'Impression limit reached — the test is no longer serving either variant.', 'wp-pop' ); ?>
						</p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

	<?php endif; ?>
</div>

$days       = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$days       = in_array( $days, array( 7, 14, 30, 60, 90 ), true ) ? $days : 30;

$tests = $ab_testing->get_active_tests();
?>
<div class="wrap wp-pop-ab-results">
	<h1><?php esc_html_e( 'A/B Test Results', 'wp-pop' ); ?></h1>

	<form method="get" style="margin-bottom:16px;">
		<input type="hidden" name="post_type" value="wp_pop">
		<input type="hidden" name="page" value="wp-pop-ab-results">
		<?php esc_html_e( 'Period:', 'wp-pop' ); ?>
		<select name="days" onchange="this.form.submit()">
			<?php
			foreach ( array( 7, 14, 30, 60, 90 ) as $d ) {
				printf(
					'<option value="%d"%s>%s</option>',
					$d,
					selected( $days, $d, false ),
					/* translators: %d = number of days */
					sprintf( esc_html__( 'Last %d days', 'wp-pop' ), $d )
				);
			}
			?>
		</select>
	</form>

	<?php if ( empty( $tests ) ) : ?>
		<p>
			<?php
			printf(
				/* translators: %s = link to create a new A/B test */
				esc_html__( 'No active A/B tests found. %s to get started.', 'wp-pop' ),
				'<a href="' . esc_url( admin_url( 'post-new.php?post_type=wp_pop_ab_test' ) ) . '">'
					. esc_html__( 'Create your first A/B test', 'wp-pop' )
				. '</a>'
			);
			?>
		</p>
	<?php else : ?>
		<style>
			.wp-pop-ab-results .ab-test-card {
				background: #fff;
				border: 1px solid #ddd;
				border-radius: 6px;
				padding: 20px 24px;
				margin-bottom: 24px;
				max-width: 860px;
			}
			.wp-pop-ab-results .ab-test-card h2 {
				margin-top: 0;
				font-size: 16px;
			}
			.wp-pop-ab-results .ab-test-card h2 a {
				text-decoration: none;
			}
			.wp-pop-ab-results .ab-compare-table {
				width: 100%;
				border-collapse: collapse;
				margin-top: 12px;
			}
			.wp-pop-ab-results .ab-compare-table th,
			.wp-pop-ab-results .ab-compare-table td {
				padding: 8px 12px;
				text-align: left;
				border-bottom: 1px solid #f0f0f0;
			}
			.wp-pop-ab-results .ab-compare-table th {
				background: #f9f9f9;
				font-weight: 600;
				white-space: nowrap;
			}
			.wp-pop-ab-results .variant-badge {
				display: inline-block;
				background: #3858e9;
				color: #fff;
				border-radius: 3px;
				padding: 1px 7px;
				font-size: 11px;
				font-weight: 700;
				margin-right: 6px;
			}
			.wp-pop-ab-results .variant-badge.b { background: #d63638; }
			.wp-pop-ab-results .winner { color: #00a32a; font-weight: 700; }
			.wp-pop-ab-results .no-data { color: #757575; font-style: italic; font-size: 13px; }
			.wp-pop-ab-results .ab-test-meta { font-size: 12px; color: #757575; margin-bottom: 8px; }
		</style>

		<?php foreach ( $tests as $test ) : ?>
			<?php
			$popup_a_id  = (int) get_post_meta( $test->ID, '_wp_pop_ab_popup_a', true );
			$popup_b_id  = (int) get_post_meta( $test->ID, '_wp_pop_ab_popup_b', true );
			$weight_a    = (int) get_post_meta( $test->ID, '_wp_pop_ab_weight_a', true ) ?: 50;
			$comparison  = $ab_testing->get_comparison( $test->ID, $days );

			$edit_url = get_edit_post_link( $test->ID );
			?>
			<div class="ab-test-card">
				<h2>
					<a href="<?php echo esc_url( $edit_url ); ?>">
						<?php echo esc_html( get_the_title( $test ) ); ?>
					</a>
				</h2>

				<p class="ab-test-meta">
					<?php
					printf(
						/* translators: 1: Popup A name, 2: popup B name, 3: split percentages */
						esc_html__( 'A: %1$s &nbsp;|&nbsp; B: %2$s &nbsp;|&nbsp; Split: %3$s', 'wp-pop' ),
						'<a href="' . esc_url( (string) get_edit_post_link( $popup_a_id ) ) . '">' . esc_html( get_the_title( $popup_a_id ) ) . '</a>',
						'<a href="' . esc_url( (string) get_edit_post_link( $popup_b_id ) ) . '">' . esc_html( get_the_title( $popup_b_id ) ) . '</a>',
						esc_html( $weight_a . '% / ' . ( 100 - $weight_a ) . '%' )
					);
					?>
				</p>

				<?php if ( empty( $comparison ) ) : ?>
					<p class="no-data"><?php esc_html_e( 'No data recorded yet for this test.', 'wp-pop' ); ?></p>
				<?php else : ?>
					<?php
					// Determine winner by CTR.
					$ctrs   = array_column( $comparison, 'ctr' );
					$max_ctr = max( $ctrs );
					?>
					<table class="ab-compare-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Variant', 'wp-pop' ); ?></th>
								<th><?php esc_html_e( 'Views', 'wp-pop' ); ?></th>
								<th><?php esc_html_e( 'Clicks', 'wp-pop' ); ?></th>
								<th><?php esc_html_e( 'Dismissals', 'wp-pop' ); ?></th>
								<th><?php esc_html_e( 'CTR', 'wp-pop' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $comparison as $row ) : ?>
								<?php
								$is_winner = ( $row['views'] > 0 && (float) $row['ctr'] === $max_ctr );
								$badge_cls = 'a' === $row['variant'] ? '' : ' b';
								?>
								<tr>
									<td>
										<span class="variant-badge<?php echo esc_attr( $badge_cls ); ?>">
											<?php echo esc_html( strtoupper( $row['variant'] ) ); ?>
										</span>
										<a href="<?php echo esc_url( (string) get_edit_post_link( $row['popup_id'] ) ); ?>">
											<?php echo esc_html( $row['label'] ); ?>
										</a>
									</td>
									<td><?php echo esc_html( number_format_i18n( $row['views'] ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( $row['clicks'] ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( $row['dismissals'] ) ); ?></td>
									<td class="<?php echo $is_winner ? 'winner' : ''; ?>">
										<?php echo esc_html( $row['ctr'] . '%' ); ?>
										<?php if ( $is_winner ) : ?>
											&#9650;
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

	<?php endif; ?>
</div>
