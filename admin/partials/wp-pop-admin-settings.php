<?php

/**
 * Settings page partial for WP Pop!
 *
 * @since   0.1.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wrap wp-pop-admin">
	<h1><?php esc_html_e( 'WP Pop! Settings', 'wp-pop' ); ?></h1>
	<form method="post" action="options.php">
		<?php
		settings_fields( 'wp_pop_settings_group' );
		do_settings_sections( 'wp-pop-settings' );
		submit_button();
		?>
	</form>

	<hr>

	<h2><?php esc_html_e( 'Import / Export', 'wp-pop' ); ?></h2>
	<p><?php esc_html_e( 'Export all popups to a JSON file, or import from a previously exported JSON file.', 'wp-pop' ); ?></p>

	<div class="wp-pop-import-export">
		<!-- Export -->
		<h3><?php esc_html_e( 'Export Popups', 'wp-pop' ); ?></h3>
		<?php
		$all_popups = get_posts( array(
			'post_type'      => 'wp_pop',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft', 'wp_pop_archived' ),
			'fields'         => 'ids',
		) );
		$export_base = admin_url( 'admin.php?action=wp_pop_export' );
		foreach ( (array) $all_popups as $pid ) {
			$export_base .= '&popup_ids[]=' . absint( $pid );
		}
		$export_url = wp_nonce_url( $export_base, 'wp_pop_export' );
		?>
		<a href="<?php echo esc_url( $export_url ); ?>" class="button"><?php esc_html_e( 'Export All Popups', 'wp-pop' ); ?></a>

		<h3><?php esc_html_e( 'Import Popups', 'wp-pop' ); ?></h3>
		<form id="wp-pop-import-form" enctype="multipart/form-data">
			<?php wp_nonce_field( 'wp_pop_import', 'wp_pop_import_nonce' ); ?>
			<input type="file" name="import_file" accept=".json">
			<button type="submit" class="button"><?php esc_html_e( 'Import', 'wp-pop' ); ?></button>
			<span id="wp-pop-import-status"></span>
		</form>
	</div>
</div>

<script>
document.getElementById('wp-pop-import-form').addEventListener('submit', function(e) {
	e.preventDefault();
	var form = this;
	var fd = new FormData(form);
	fd.append('action', 'wp_pop_import');
	fd.append('nonce', document.getElementById('wp_pop_import_nonce').value);
	var status = document.getElementById('wp-pop-import-status');
	status.textContent = <?php echo wp_json_encode( __( 'Importing…', 'wp-pop' ) ); ?>;
	fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
		method: 'POST',
		body: fd,
		credentials: 'same-origin',
	}).then(function(r) { return r.json(); }).then(function(d) {
		if (d.success) {
			status.textContent = <?php echo wp_json_encode( __( 'Done! %d popup(s) imported.', 'wp-pop' ) ); ?>.replace('%d', d.data.created);
		} else {
			status.textContent = <?php echo wp_json_encode( __( 'Import failed.', 'wp-pop' ) ); ?>;
		}
	});
});
</script>
