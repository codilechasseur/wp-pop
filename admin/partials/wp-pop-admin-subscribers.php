<?php
/**
 * Subscribers management partial.
 *
 * @since   0.2.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Filters.
$filter_popup  = isset( $_GET['popup_id'] ) ? absint( $_GET['popup_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$filter_status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$paged         = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification

$args = array(
	'per_page' => 50,
	'page'     => $paged,
);
if ( $filter_popup ) {
	$args['popup_id'] = $filter_popup;
}
if ( $filter_status ) {
	$args['status'] = $filter_status;
}

$result      = ( new Wp_Pop_Subscribers() )->get_subscribers( $args );
$subscribers = $result->items;
$total       = $result->total;
$pages       = ceil( $total / 50 );
?>
<div class="wrap wp-pop-subscribers">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'WP Pop! Subscribers', 'wp-pop' ); ?></h1>

	<!-- Export button -->
	<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wp_pop_export_subscribers' . ( $filter_popup ? '&popup_id=' . $filter_popup : '' ) ), 'wp_pop_export_subscribers' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'wp-pop' ); ?></a>

	<hr class="wp-header-end">

	<!-- Filters -->
	<form method="get" action="">
		<input type="hidden" name="post_type" value="wp_pop">
		<input type="hidden" name="page" value="wp-pop-subscribers">

		<label for="wp-pop-sub-popup"><?php esc_html_e( 'Popup:', 'wp-pop' ); ?></label>
		<select name="popup_id" id="wp-pop-sub-popup">
			<option value="0"><?php esc_html_e( '— All Popups —', 'wp-pop' ); ?></option>
			<?php
			$popups = get_posts( array( 'post_type' => 'wp_pop', 'posts_per_page' => 100, 'post_status' => array( 'publish', 'draft' ) ) );
			foreach ( $popups as $popup ) {
				printf(
					'<option value="%d"%s>%s</option>',
					$popup->ID,
					selected( $filter_popup, $popup->ID, false ),
					esc_html( $popup->post_title )
				);
			}
			?>
		</select>

		<label for="wp-pop-sub-status"><?php esc_html_e( 'Status:', 'wp-pop' ); ?></label>
		<select name="status" id="wp-pop-sub-status">
			<option value=""><?php esc_html_e( '— All —', 'wp-pop' ); ?></option>
			<option value="active"<?php selected( $filter_status, 'active' ); ?>><?php esc_html_e( 'Active', 'wp-pop' ); ?></option>
			<option value="unsubscribed"<?php selected( $filter_status, 'unsubscribed' ); ?>><?php esc_html_e( 'Unsubscribed', 'wp-pop' ); ?></option>
		</select>

		<?php submit_button( __( 'Filter', 'wp-pop' ), 'secondary', 'submit', false ); ?>
	</form>

	<!-- Summary -->
	<p><?php printf( /* translators: %d: subscriber count */ esc_html__( 'Showing %d subscribers', 'wp-pop' ), $total ); ?></p>

	<!-- Table -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wp_pop_bulk_subscribers">
		<?php wp_nonce_field( 'wp_pop_bulk_subscribers' ); ?>

		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<select name="bulk_action">
					<option value=""><?php esc_html_e( 'Bulk Actions', 'wp-pop' ); ?></option>
					<option value="unsubscribe"><?php esc_html_e( 'Unsubscribe', 'wp-pop' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'wp-pop' ); ?></option>
				</select>
				<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'wp-pop' ); ?>">
			</div>

			<!-- Pagination -->
			<?php if ( $pages > 1 ) : ?>
			<div class="tablenav-pages">
				<span class="displaying-num"><?php printf( esc_html__( '%d items', 'wp-pop' ), $total ); ?></span>
				<?php
				$base_url = add_query_arg(
					array(
						'post_type' => 'wp_pop',
						'page'      => 'wp-pop-subscribers',
						'popup_id'  => $filter_popup,
						'status'    => $filter_status,
					),
					admin_url( 'edit.php' )
				);
				for ( $i = 1; $i <= $pages; $i++ ) {
					printf(
						'<a href="%s" class="button%s">%d</a> ',
						esc_url( add_query_arg( 'paged', $i, $base_url ) ),
						$i === $paged ? ' button-primary' : '',
						$i
					);
				}
				?>
			</div>
			<?php endif; ?>
		</div>

		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th class="check-column"><input type="checkbox" id="wp-pop-check-all"></th>
					<th><?php esc_html_e( 'Email', 'wp-pop' ); ?></th>
					<th><?php esc_html_e( 'Name', 'wp-pop' ); ?></th>
					<th><?php esc_html_e( 'Popup', 'wp-pop' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wp-pop' ); ?></th>
					<th><?php esc_html_e( 'Subscribed', 'wp-pop' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $subscribers ) ) : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No subscribers found.', 'wp-pop' ); ?></td>
				</tr>
				<?php else : ?>
				<?php foreach ( $subscribers as $sub ) : ?>
				<tr>
					<td class="check-column">
						<input type="checkbox" name="subscriber_ids[]" value="<?php echo absint( $sub->id ); ?>">
					</td>
					<td><?php echo esc_html( $sub->email ); ?></td>
					<td><?php echo esc_html( $sub->name ?: '—' ); ?></td>
					<td>
						<?php if ( $sub->popup_id ) : ?>
						<a href="<?php echo esc_url( get_edit_post_link( $sub->popup_id ) ); ?>"><?php echo esc_html( get_the_title( $sub->popup_id ) ); ?></a>
						<?php else : ?>
						—
						<?php endif; ?>
					</td>
					<td>
						<?php if ( 'active' === $sub->status ) : ?>
						<span class="wp-pop-badge wp-pop-badge--active"><?php esc_html_e( 'Active', 'wp-pop' ); ?></span>
						<?php else : ?>
						<span class="wp-pop-badge wp-pop-badge--expired"><?php esc_html_e( 'Unsubscribed', 'wp-pop' ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $sub->subscribed_at ) ) ); ?></td>
				</tr>
				<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</form>
</div>

<script>
document.getElementById('wp-pop-check-all').addEventListener('change', function() {
	document.querySelectorAll('input[name="subscriber_ids[]"]').forEach(function(cb) {
		cb.checked = document.getElementById('wp-pop-check-all').checked;
	});
});
</script>
