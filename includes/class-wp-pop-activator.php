<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Pop_Activator {

	public static function activate() {
		require_once WP_POP_PLUGIN_DIR . 'includes/class-wp-pop-post-type.php';
		Wp_Pop_Post_Type::register();
		flush_rewrite_rules();

		self::create_tables();
		self::set_default_options();
	}

	// -------------------------------------------------------------------------
	// Database tables
	// -------------------------------------------------------------------------

	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Per-day analytics events (views, clicks, dismissals, conversions).
		$events_table = $wpdb->prefix . 'wp_pop_events';
		$sql_events   = "CREATE TABLE $events_table (
			id          bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			popup_id    bigint(20) unsigned NOT NULL DEFAULT 0,
			variant_id  varchar(32)         NOT NULL DEFAULT '',
			event_type  varchar(20)         NOT NULL DEFAULT '',
			event_date  date                NOT NULL,
			count       bigint(20) unsigned NOT NULL DEFAULT 1,
			PRIMARY KEY (id),
			UNIQUE KEY popup_variant_type_date (popup_id, variant_id, event_type, event_date),
			KEY popup_id (popup_id),
			KEY event_date (event_date)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_events );
	}

	// -------------------------------------------------------------------------
	// Default options
	// -------------------------------------------------------------------------

	private static function set_default_options() {
		add_option( 'wp_pop_version',                   WP_POP_VERSION );
		add_option( 'wp_pop_default_frequency',         'session' );
		add_option( 'wp_pop_default_delay',             0 );
		add_option( 'wp_pop_default_trigger',           'time' );
		add_option( 'wp_pop_default_scroll_threshold',  50 );
		add_option( 'wp_pop_default_width_preset',      'medium' );
		add_option( 'wp_pop_default_custom_width',      40 );
		add_option( 'wp_pop_default_overlay_color',     '#000000' );
		add_option( 'wp_pop_default_overlay_opacity',   0.65 );
		add_option( 'wp_pop_default_border_radius',     8 );
		add_option( 'wp_pop_default_padding',           2 );
		add_option( 'wp_pop_default_close_color',       '#000000' );
		add_option( 'wp_pop_default_hide_title',        0 );
		add_option( 'wp_pop_default_columns_gap',       2 );
		add_option( 'wp_pop_default_blur_amount',       0 );
		add_option( 'wp_pop_default_animation',         'fade' );
		add_option( 'wp_pop_default_popup_type',        'modal' );
	}
}
