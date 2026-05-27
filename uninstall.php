<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Remove all plugin options.
$options = array(
	'wp_pop_version',
	'wp_pop_default_frequency',
	'wp_pop_default_delay',
	'wp_pop_default_trigger',
	'wp_pop_default_scroll_threshold',
	'wp_pop_default_width_preset',
	'wp_pop_default_custom_width',
	'wp_pop_default_overlay_color',
	'wp_pop_default_overlay_opacity',
	'wp_pop_default_border_radius',
	'wp_pop_default_padding',
	'wp_pop_default_close_color',
	'wp_pop_default_hide_title',
	'wp_pop_default_columns_gap',
	'wp_pop_default_blur_amount',
	'wp_pop_default_animation',
	'wp_pop_default_popup_type',
	'wp_pop_geo_provider',
	'wp_pop_geo_api_key',
	'wp_pop_maxmind_license_key',
	'wp_pop_maxmind_db_path',
	'wp_pop_maxmind_db_updated',
	'wp_pop_geo_fail_behavior',
	'wp_pop_geo_cache_hours',
);
foreach ( $options as $option ) {
	delete_option( $option );
}

// Remove all geo transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '_transient_wp_pop_geo_%'
	    OR option_name LIKE '_transient_timeout_wp_pop_geo_%'"
);

// Drop custom DB tables.
// phpcs:disable WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wp_pop_events" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wp_pop_subscribers" );
// phpcs:enable

// Delete all wp_pop and wp_pop_ab_test posts and their meta.
$post_types = array( 'wp_pop', 'wp_pop_ab_test' );
foreach ( $post_types as $post_type ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$ids = $wpdb->get_col(
		$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", $post_type )
	);
	foreach ( $ids as $id ) {
		wp_delete_post( (int) $id, true );
	}
}
