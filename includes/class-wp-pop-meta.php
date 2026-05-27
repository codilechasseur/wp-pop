<?php

/**
 * Registers all post meta for the wp_pop post type.
 *
 * All fields use show_in_rest: true so the block-editor sidebar panels
 * can read/write them through the REST API via useEntityProp().
 *
 * @since   0.2.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Pop_Meta {

	/**
	 * Registers every meta field.  Called on the init hook.
	 */
	public static function register() {
		$auth = array( __CLASS__, 'auth_callback' );

		// -----------------------------------------------------------------
		// Display / Scope
		// -----------------------------------------------------------------

		self::register_string( '_wp_pop_display_scope', 'sitewide', $auth );

		register_post_meta(
			'wp_pop',
			'_wp_pop_specific_pages',
			array(
				'type'          => 'array',
				'single'        => true,
				'default'       => array(),
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'auth_callback'     => $auth,
				'sanitize_callback' => array( __CLASS__, 'sanitize_int_array' ),
			)
		);

		// URL pattern targeting (one pattern per entry, e.g. "/shop/*").
		// Stored as a JSON string so the block editor's useEntityProp can read/write it.
		self::register_json( '_wp_pop_url_patterns', '[]', $auth );

		// Post-type slugs targeting.
		register_post_meta(
			'wp_pop',
			'_wp_pop_target_post_types',
			array(
				'type'          => 'array',
				'single'        => true,
				'default'       => array(),
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
				),
				'auth_callback'     => $auth,
				'sanitize_callback' => array( __CLASS__, 'sanitize_string_array' ),
			)
		);

		// Taxonomy/term targeting — JSON-encoded array of {taxonomy, terms[]}.
		self::register_json( '_wp_pop_target_taxonomies', '[]', $auth );

		// User role targeting.
		register_post_meta(
			'wp_pop',
			'_wp_pop_target_user_roles',
			array(
				'type'          => 'array',
				'single'        => true,
				'default'       => array(),
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
				),
				'auth_callback'     => $auth,
				'sanitize_callback' => array( __CLASS__, 'sanitize_string_array' ),
			)
		);

		self::register_string( '_wp_pop_device',        'all', $auth );
		self::register_string( '_wp_pop_visitor_type',  'all', $auth );
		self::register_string( '_wp_pop_logged_in',     'all', $auth );

		// WooCommerce page targeting (product, cart, checkout, shop) — single value.
		self::register_string( '_wp_pop_wc_page', '', $auth );

		// -----------------------------------------------------------------
		// Scheduling
		// -----------------------------------------------------------------

		self::register_integer( '_wp_pop_scheduling_enabled', 0, $auth );
		self::register_string(  '_wp_pop_start_date',         '',  $auth, array( __CLASS__, 'sanitize_date' ) );
		self::register_string(  '_wp_pop_start_time',         '',  $auth, array( __CLASS__, 'sanitize_time' ) );
		self::register_string(  '_wp_pop_end_date',           '',  $auth, array( __CLASS__, 'sanitize_date' ) );
		self::register_string(  '_wp_pop_end_time',           '',  $auth, array( __CLASS__, 'sanitize_time' ) );

		// -----------------------------------------------------------------
		// Trigger
		// -----------------------------------------------------------------

		self::register_string(  '_wp_pop_trigger',           'time', $auth );
		self::register_integer( '_wp_pop_trigger_delay',     0,      $auth );
		self::register_integer( '_wp_pop_scroll_threshold',  50,     $auth );
		self::register_string(  '_wp_pop_click_selector',    '',     $auth, 'sanitize_text_field' );
		self::register_integer( '_wp_pop_inactivity_seconds', 30,    $auth );
		self::register_string(  '_wp_pop_element_selector',  '',     $auth, 'sanitize_text_field' );
		self::register_string(  '_wp_pop_wc_trigger',        'none', $auth );

		// -----------------------------------------------------------------
		// Frequency
		// -----------------------------------------------------------------

		self::register_string(  '_wp_pop_frequency',         'session', $auth );
		self::register_integer( '_wp_pop_retrigger_minutes', 0,         $auth );
		self::register_integer( '_wp_pop_popup_priority',    10,        $auth );
		self::register_integer( '_wp_pop_test_mode',         0,         $auth );

		// -----------------------------------------------------------------
		// Appearance
		// -----------------------------------------------------------------

		self::register_string(  '_wp_pop_popup_type',    'modal',    $auth );
		self::register_string(  '_wp_pop_width',         '600px',    $auth );
		self::register_integer( '_wp_pop_overlay',       1,          $auth );
		self::register_string(  '_wp_pop_overlay_color', '#000000',  $auth, 'sanitize_hex_color' );
		self::register_number(  '_wp_pop_overlay_opacity', 0.65,     $auth );
		self::register_integer( '_wp_pop_border_radius',       8,          $auth );
		self::register_number(  '_wp_pop_padding',             2,          $auth );
		self::register_string(  '_wp_pop_close_color',         '#000000',  $auth, 'sanitize_hex_color' );
		self::register_integer( '_wp_pop_hide_title',          0,          $auth );
		self::register_number(  '_wp_pop_columns_gap',         2,          $auth );
		self::register_integer( '_wp_pop_blur_amount',         0,          $auth );
		self::register_string(  '_wp_pop_animation',           'fade',     $auth );
		self::register_integer( '_wp_pop_close_on_outside_click', 1,       $auth );
		self::register_integer( '_wp_pop_close_on_esc',        1,          $auth );
		self::register_integer( '_wp_pop_show_close_button',   1,          $auth );
		self::register_integer( '_wp_pop_close_delay',         0,          $auth );

		// -----------------------------------------------------------------
		// A/B Testing
		// -----------------------------------------------------------------

		self::register_integer( '_wp_pop_ab_enabled',  0,   $auth );
		self::register_json(    '_wp_pop_ab_variants', '[]', $auth );

		// -----------------------------------------------------------------
		// Conversion Tracking
		// -----------------------------------------------------------------

		self::register_string( '_wp_pop_success_url', '', $auth );

		// -----------------------------------------------------------------
		// Geo Targeting
		// -----------------------------------------------------------------

		self::register_integer( '_wp_pop_geo_enabled',   0,       $auth );
		self::register_string(  '_wp_pop_geo_mode',      'allow', $auth );
		self::register_json(    '_wp_pop_geo_countries', '[]',    $auth );
		self::register_json(    '_wp_pop_geo_regions',   '[]',    $auth );
		self::register_json(    '_wp_pop_geo_cities',    '[]',    $auth );

		// -----------------------------------------------------------------
		// Analytics (server-managed, not editable from editor)
		// -----------------------------------------------------------------

		self::register_integer( '_wp_pop_view_count', 0, $auth );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private static function register_string( $key, $default, $auth, $sanitize = 'sanitize_text_field' ) {
		register_post_meta(
			'wp_pop',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => $default,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => $sanitize,
			)
		);
	}

	private static function register_integer( $key, $default, $auth ) {
		register_post_meta(
			'wp_pop',
			$key,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => $default,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => 'absint',
			)
		);
	}

	private static function register_number( $key, $default, $auth ) {
		register_post_meta(
			'wp_pop',
			$key,
			array(
				'type'              => 'number',
				'single'            => true,
				'default'           => $default,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => 'floatval',
			)
		);
	}

	/**
	 * Registers a meta field whose value is stored as a JSON string.
	 */
	private static function register_json( $key, $default, $auth ) {
		register_post_meta(
			'wp_pop',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => $default,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => array( __CLASS__, 'sanitize_json_string' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Sanitize callbacks
	// -------------------------------------------------------------------------

	public static function auth_callback( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	}

	public static function sanitize_int_array( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_map( 'absint', $value );
	}

	public static function sanitize_string_array( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_map( 'sanitize_text_field', $value );
	}

	public static function sanitize_date( $value ) {
		$date = sanitize_text_field( $value );
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';
	}

	public static function sanitize_time( $value ) {
		$time = sanitize_text_field( $value );
		return preg_match( '/^\d{2}:\d{2}$/', $time ) ? $time : '';
	}

	public static function sanitize_json_string( $value ) {
		if ( ! is_string( $value ) ) {
			return '[]';
		}
		$decoded = json_decode( wp_unslash( $value ), true );
		if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
			return '[]';
		}
		return wp_json_encode( $decoded );
	}
}
