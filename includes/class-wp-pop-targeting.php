<?php

/**
 * Evaluates all targeting rules and returns the active popups for the
 * current page request.
 *
 * Rules evaluated (server-side; client-side also re-checks visitor type):
 *  - Post scheduling (start/end dates)
 *  - Display scope (sitewide, specific pages, URL patterns, post types, taxonomies)
 *  - User role
 *  - Logged-in state
 *  - Device (user-agent sniffing)
 *  - WooCommerce page type (when WC is active)
 *
 * @since   0.2.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Pop_Targeting {

	/**
	 * Returns WP_Post objects for popups that should be displayed on the
	 * current page, sorted by priority (descending).
	 *
	 * @return WP_Post[]
	 */
	public function get_active_popups_for_current_page() {
		$query = new WP_Query(
			array(
				'post_type'      => 'wp_pop',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'key'     => '_wp_pop_archived',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$candidates = $query->posts;
		$active     = array();

		foreach ( $candidates as $popup ) {
			if ( $this->popup_passes_rules( $popup ) ) {
				$active[] = $popup;
			}
		}

		// Sort by priority descending (higher number = shown first).
		usort(
			$active,
			function ( $a, $b ) {
				$pa = (int) get_post_meta( $a->ID, '_wp_pop_popup_priority', true );
				$pb = (int) get_post_meta( $b->ID, '_wp_pop_popup_priority', true );
				return $pb - $pa;
			}
		);

		return $active;
	}

	// -------------------------------------------------------------------------
	// Core rule evaluator
	// -------------------------------------------------------------------------

	/**
	 * Returns true if the popup passes ALL applicable targeting rules for the
	 * current page/user/device.
	 *
	 * @param WP_Post $popup
	 * @return bool
	 */
	public function popup_passes_rules( WP_Post $popup ) {
		$id = $popup->ID;

		// Test mode: always show to admins regardless of every other rule.
		if ( get_post_meta( $id, '_wp_pop_test_mode', true ) && current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Schedule.
		if ( ! $this->check_schedule( $id ) ) {
			return false;
		}

		// Display scope.
		if ( ! $this->check_display_scope( $id ) ) {
			return false;
		}

		// Logged-in state.
		if ( ! $this->check_logged_in( $id ) ) {
			return false;
		}

		// User role (only checked when logged in).
		if ( ! $this->check_user_role( $id ) ) {
			return false;
		}

		// Device.
		if ( ! $this->check_device( $id ) ) {
			return false;
		}

		// WooCommerce page targeting.
		if ( ! $this->check_woocommerce_pages( $id ) ) {
			return false;
		}

		// Geo targeting.
		if ( ! $this->check_geo( $id ) ) {
			return false;
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// Individual rule checks
	// -------------------------------------------------------------------------

	private function check_schedule( $id ) {
		if ( ! get_post_meta( $id, '_wp_pop_scheduling_enabled', true ) ) {
			return true; // scheduling not enabled → always pass.
		}

		$today      = current_time( 'Y-m-d' );
		$start_date = get_post_meta( $id, '_wp_pop_start_date', true );
		$end_date   = get_post_meta( $id, '_wp_pop_end_date', true );

		if ( $start_date && $today < $start_date ) {
			return false;
		}
		if ( $end_date && $today > $end_date ) {
			return false;
		}

		return true;
	}

	private function check_display_scope( $id ) {
		$scope = get_post_meta( $id, '_wp_pop_display_scope', true ) ?: 'sitewide';

		if ( 'sitewide' === $scope ) {
			return true;
		}

		// Check specific page IDs.
		if ( 'specific' === $scope ) {
			$specific = (array) get_post_meta( $id, '_wp_pop_specific_pages', true );
			$queried  = get_queried_object_id();
			return in_array( $queried, $specific, true );
		}

		// URL pattern matching.
		if ( 'url_pattern' === $scope ) {
			return $this->check_url_patterns( $id );
		}

		// Post type matching.
		if ( 'post_type' === $scope ) {
			$types   = (array) get_post_meta( $id, '_wp_pop_target_post_types', true );
			$current = get_post_type();
			return empty( $types ) || in_array( $current, $types, true );
		}

		// Taxonomy/term matching.
		if ( 'taxonomy' === $scope ) {
			return $this->check_taxonomy_terms( $id );
		}

		return true;
	}

	private function check_url_patterns( $id ) {
		$patterns = (array) get_post_meta( $id, '_wp_pop_target_url_patterns', true );
		if ( empty( $patterns ) ) {
			return true;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$current_url = home_url( $request_uri );

		foreach ( $patterns as $pattern ) {
			$pattern = sanitize_text_field( $pattern );
			if ( empty( $pattern ) ) {
				continue;
			}
			// Convert glob-style pattern to a regex.
			$regex = '#^' . str_replace( '\*', '.*', preg_quote( $pattern, '#' ) ) . '$#i';
			if ( preg_match( $regex, $current_url ) ) {
				return true;
			}
			// Plain substring match against relative path.
			$path = wp_parse_url( $current_url, PHP_URL_PATH );
			if ( false !== strpos( $path, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	private function check_taxonomy_terms( $id ) {
		$raw = get_post_meta( $id, '_wp_pop_target_taxonomies', true );
		$rules = json_decode( $raw, true );
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return true;
		}

		$queried_id = get_queried_object_id();

		foreach ( $rules as $rule ) {
			$taxonomy = sanitize_key( $rule['taxonomy'] ?? '' );
			$terms    = array_map( 'absint', (array) ( $rule['terms'] ?? array() ) );
			if ( ! $taxonomy || empty( $terms ) ) {
				continue;
			}
			// Post page: check if the post has these terms.
			if ( is_singular() ) {
				if ( has_term( $terms, $taxonomy, $queried_id ) ) {
					return true;
				}
			}
			// Archive page: check if the current term is in the list.
			if ( is_tax( $taxonomy ) || is_category() || is_tag() ) {
				if ( in_array( $queried_id, $terms, true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	private function check_logged_in( $id ) {
		$setting = get_post_meta( $id, '_wp_pop_target_logged_in', true ) ?: 'all';

		if ( 'all' === $setting ) {
			return true;
		}
		if ( 'logged_in' === $setting ) {
			return is_user_logged_in();
		}
		if ( 'logged_out' === $setting ) {
			return ! is_user_logged_in();
		}

		return true;
	}

	private function check_user_role( $id ) {
		$roles = (array) get_post_meta( $id, '_wp_pop_target_user_roles', true );
		if ( empty( $roles ) || $roles === array( '' ) ) {
			return true; // No role restriction.
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user = wp_get_current_user();
		foreach ( $roles as $role ) {
			if ( in_array( $role, (array) $user->roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	private function check_device( $id ) {
		$device_setting = get_post_meta( $id, '_wp_pop_target_device', true ) ?: 'all';
		if ( 'all' === $device_setting ) {
			return true;
		}

		$ua     = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$is_mob = $this->is_mobile( $ua );
		$is_tab = $this->is_tablet( $ua );

		if ( 'mobile' === $device_setting ) {
			return $is_mob && ! $is_tab;
		}
		if ( 'tablet' === $device_setting ) {
			return $is_tab;
		}
		if ( 'desktop' === $device_setting ) {
			return ! $is_mob && ! $is_tab;
		}

		return true;
	}

	private function check_woocommerce_pages( $id ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return true; // WC not active → rule not applicable.
		}

		$wc_pages = (array) get_post_meta( $id, '_wp_pop_target_wc_pages', true );
		$wc_pages = array_filter( $wc_pages );
		if ( empty( $wc_pages ) ) {
			return true; // No WC page restriction.
		}

		foreach ( $wc_pages as $page ) {
			if ( 'shop' === $page && function_exists( 'is_shop' ) && is_shop() ) {
				return true;
			}
			if ( 'product' === $page && function_exists( 'is_product' ) && is_product() ) {
				return true;
			}
			if ( 'cart' === $page && function_exists( 'is_cart' ) && is_cart() ) {
				return true;
			}
			if ( 'checkout' === $page && function_exists( 'is_checkout' ) && is_checkout() ) {
				return true;
			}
		}

		return false;
	}

	private function check_geo( $id ) {
		// If geo targeting is not enabled for this popup, always pass.
		if ( ! get_post_meta( $id, '_wp_pop_geo_enabled', true ) ) {
			return true;
		}

		$countries = json_decode( get_post_meta( $id, '_wp_pop_geo_countries', true ) ?: '[]', true );
		$regions   = json_decode( get_post_meta( $id, '_wp_pop_geo_regions', true ) ?: '[]', true );
		$cities    = json_decode( get_post_meta( $id, '_wp_pop_geo_cities', true ) ?: '[]', true );

		// No rules configured → pass.
		if ( empty( $countries ) && empty( $regions ) && empty( $cities ) ) {
			return true;
		}

		$geo = Wp_Pop_Geo::get_visitor_geo();

		// Geo lookup failed — respect the configured fail behaviour.
		if ( null === $geo ) {
			return 'hide' !== get_option( 'wp_pop_geo_fail_behavior', 'show' );
		}

		$mode    = get_post_meta( $id, '_wp_pop_geo_mode', true ) ?: 'allow';
		$matched = false;

		// Country match (exact ISO 3166-1 alpha-2, case-insensitive).
		if ( ! empty( $countries ) && ! empty( $geo['country_code'] ) ) {
			$codes = array_map( 'strtoupper', array_filter( array_map( 'trim', (array) $countries ) ) );
			if ( in_array( strtoupper( $geo['country_code'] ), $codes, true ) ) {
				$matched = true;
			}
		}

		// Region match (partial, case-insensitive).
		if ( ! $matched && ! empty( $regions ) && ! empty( $geo['region'] ) ) {
			foreach ( $regions as $r ) {
				if ( '' !== trim( $r ) && false !== stripos( $geo['region'], trim( $r ) ) ) {
					$matched = true;
					break;
				}
			}
		}

		// City match (partial, case-insensitive).
		if ( ! $matched && ! empty( $cities ) && ! empty( $geo['city'] ) ) {
			foreach ( $cities as $c ) {
				if ( '' !== trim( $c ) && false !== stripos( $geo['city'], trim( $c ) ) ) {
					$matched = true;
					break;
				}
			}
		}

		// allow = show only to matched visitors; block = hide for matched visitors.
		return 'allow' === $mode ? $matched : ! $matched;
	}

	// -------------------------------------------------------------------------
	// UA-based device detection helpers
	// -------------------------------------------------------------------------

	private function is_mobile( $ua ) {
		return (bool) preg_match(
			'/Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i',
			$ua
		);
	}

	private function is_tablet( $ua ) {
		return (bool) preg_match( '/iPad|Tablet|Kindle|PlayBook/i', $ua );
	}
}
