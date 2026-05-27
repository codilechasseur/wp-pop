<?php

/**
 * A/B Testing: popup-pair based tests.
 *
 * Each test is a `wp_pop_ab_test` post that points to two existing `wp_pop`
 * popups — Variant A and Variant B — with an optional weight split.
 *
 * How variant selection works
 * ---------------------------
 * Both popup A and popup B are rendered into the page footer.  Each carries
 * `abTestId`, `abVariant` ('a' | 'b'), and `abWeightA` (0-100) in its
 * Interactivity API context.  The view.js store reads a localStorage key
 * `wp-pop-ab-{testId}`.  On first visit it randomly picks a variant
 * (weighted), persists the choice, and only sets up triggers for the chosen
 * popup — the other dialog is left dormant and never opened.
 *
 * Analytics
 * ---------
 * Events (view / click / dismiss) are stored in `{prefix}wp_pop_events` with
 * the individual popup_id (A or B).  `get_comparison()` queries both popup IDs
 * side-by-side for the test's dashboard card.
 *
 * @since   0.3.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Pop_Ab_Testing {

	/** @var array[]|null  Cached test map (popup_id => test data). */
	private $test_map = null;

	// -------------------------------------------------------------------------
	// Active test discovery
	// -------------------------------------------------------------------------

	/**
	 * Returns all published A/B test posts.
	 *
	 * @return WP_Post[]
	 */
	public function get_active_tests() {
		return get_posts(
			array(
				'post_type'      => 'wp_pop_ab_test',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			)
		);
	}

	/**
	 * Returns a map from popup_id (int) to test data.
	 * Only Popup A IDs are used as keys — the public rendering code looks up
	 * popup A to discover whether a test is active for it.
	 *
	 * Return shape:
	 *   [
	 *     42 => [
	 *       'test_id'         => 7,
	 *       'popup_a'         => 42,
	 *       'popup_b'         => 55,
	 *       'weight_a'        => 50,
	 *       'scope'           => 'sitewide'|'home'|'singular'|'archive'|'specific'|'url_pattern',
	 *       'specific_pages'  => [],
	 *       'url_patterns'    => [],
	 *       'start_date'      => '',
	 *       'end_date'        => '',
	 *       'max_impressions' => 0,
	 *     ],
	 *     ...
	 *   ]
	 *
	 * @return array[]
	 */
	public function get_popup_test_map() {
		if ( null !== $this->test_map ) {
			return $this->test_map;
		}

		$this->test_map = array();

		foreach ( $this->get_active_tests() as $test ) {
			$popup_a  = (int) get_post_meta( $test->ID, '_wp_pop_ab_popup_a', true );
			$popup_b  = (int) get_post_meta( $test->ID, '_wp_pop_ab_popup_b', true );
			$weight_a = (int) get_post_meta( $test->ID, '_wp_pop_ab_weight_a', true );

			if ( ! $popup_a || ! $popup_b || $popup_a === $popup_b ) {
				continue;
			}

			$weight_a = max( 1, min( 99, $weight_a ?: 50 ) );

			$this->test_map[ $popup_a ] = array(
				'test_id'         => $test->ID,
				'popup_a'         => $popup_a,
				'popup_b'         => $popup_b,
				'weight_a'        => $weight_a,
				'scope'           => get_post_meta( $test->ID, '_wp_pop_ab_scope', true ) ?: 'sitewide',
				'specific_pages'  => (array) get_post_meta( $test->ID, '_wp_pop_ab_specific_pages', true ),
				'url_patterns'    => (array) get_post_meta( $test->ID, '_wp_pop_ab_url_patterns', true ),
				'start_date'      => get_post_meta( $test->ID, '_wp_pop_ab_start_date', true ),
				'end_date'        => get_post_meta( $test->ID, '_wp_pop_ab_end_date', true ),
				'max_impressions' => (int) get_post_meta( $test->ID, '_wp_pop_ab_max_impressions', true ),
				'paused'          => (bool) get_post_meta( $test->ID, '_wp_pop_ab_paused', true ),
			);
		}

		return $this->test_map;
	}

	// -------------------------------------------------------------------------
	// Constraint checks (schedule, scope, limits)
	// -------------------------------------------------------------------------

	/**
	 * Returns true if the current date falls within the test's optional schedule.
	 *
	 * @param array $test Test data from get_popup_test_map().
	 * @return bool
	 */
	private function test_within_schedule( array $test ) {
		$today = current_time( 'Y-m-d' );

		if ( ! empty( $test['start_date'] ) && $today < $test['start_date'] ) {
			return false;
		}
		if ( ! empty( $test['end_date'] ) && $today > $test['end_date'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns true if the current page matches the test's scope setting.
	 *
	 * @param array $test Test data from get_popup_test_map().
	 * @return bool
	 */
	private function test_matches_scope( array $test ) {
		$scope = $test['scope'] ?? 'sitewide';

		switch ( $scope ) {
			case 'sitewide':
				return true;

			case 'home':
				return is_front_page() || is_home();

			case 'singular':
				return is_singular();

			case 'archive':
				return is_archive() || is_home();

			case 'specific':
				$pages   = array_map( 'intval', $test['specific_pages'] );
				$queried = get_queried_object_id();
				return in_array( $queried, $pages, true );

			case 'url_pattern':
				$patterns = array_filter( $test['url_patterns'] ?? array() );
				if ( empty( $patterns ) ) {
					return true;
				}

				$request_uri = isset( $_SERVER['REQUEST_URI'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
					: '';
				$current_url = home_url( $request_uri );
				$path        = (string) wp_parse_url( $current_url, PHP_URL_PATH );

				foreach ( $patterns as $pattern ) {
					$pattern = sanitize_text_field( $pattern );
					if ( empty( $pattern ) ) {
						continue;
					}
					$regex = '#^' . str_replace( '\*', '.*', preg_quote( $pattern, '#' ) ) . '$#i';
					if ( preg_match( $regex, $current_url ) ) {
						return true;
					}
					if ( false !== strpos( $path, $pattern ) ) {
						return true;
					}
				}
				return false;
		}

		return true;
	}

	/**
	 * Returns true if the test has not exceeded its max-impressions limit.
	 * A limit of 0 means unlimited.
	 *
	 * @param array $test Test data from get_popup_test_map().
	 * @return bool
	 */
	private function test_within_limits( array $test ) {
		$limit = (int) ( $test['max_impressions'] ?? 0 );
		if ( $limit <= 0 ) {
			return true;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'wp_pop_events';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(count) FROM {$table}
				 WHERE popup_id IN (%d, %d) AND event_type = 'view'",
				$test['popup_a'],
				$test['popup_b']
			)
		);
		// phpcs:enable

		return $total < $limit;
	}

	/**
	 * Returns the test status label for display purposes.
	 * One of: 'active', 'scheduled', 'ended', 'limit_reached'.
	 *
	 * @param array $test Test data from get_popup_test_map() (must include all fields).
	 * @return string
	 */
	public function get_test_status( array $test ) {
		// Pause takes highest priority.
		if ( ! empty( $test['paused'] ) ) {
			return 'paused';
		}

		$today = current_time( 'Y-m-d' );

		if ( ! empty( $test['start_date'] ) && $today < $test['start_date'] ) {
			return 'scheduled';
		}
		if ( ! empty( $test['end_date'] ) && $today > $test['end_date'] ) {
			return 'ended';
		}
		if ( ! $this->test_within_limits( $test ) ) {
			return 'limit_reached';
		}

		return 'active';
	}

	/**
	 * Loads test data for a given test post ID, suitable for get_test_status().
	 * Used by the results page which iterates over all tests independently.
	 *
	 * @param  int $test_id
	 * @return array
	 */
	public function get_test_data( $test_id ) {
		return array(
			'test_id'         => $test_id,
			'popup_a'         => (int) get_post_meta( $test_id, '_wp_pop_ab_popup_a', true ),
			'popup_b'         => (int) get_post_meta( $test_id, '_wp_pop_ab_popup_b', true ),
			'weight_a'        => (int) get_post_meta( $test_id, '_wp_pop_ab_weight_a', true ) ?: 50,
			'scope'           => get_post_meta( $test_id, '_wp_pop_ab_scope', true ) ?: 'sitewide',
			'specific_pages'  => (array) get_post_meta( $test_id, '_wp_pop_ab_specific_pages', true ),
			'url_patterns'    => (array) get_post_meta( $test_id, '_wp_pop_ab_url_patterns', true ),
			'start_date'      => get_post_meta( $test_id, '_wp_pop_ab_start_date', true ),
			'end_date'        => get_post_meta( $test_id, '_wp_pop_ab_end_date', true ),
			'max_impressions' => (int) get_post_meta( $test_id, '_wp_pop_ab_max_impressions', true ),
			'paused'          => (bool) get_post_meta( $test_id, '_wp_pop_ab_paused', true ),
		);
	}

	// -------------------------------------------------------------------------
	// Public rendering helper
	// -------------------------------------------------------------------------

	/**
	 * Takes the list of active WP_Post popups for the current page and injects
	 * any A/B test variants, subject to schedule, scope, and impression limits.
	 *
	 * For every popup that is Variant A of an active test that passes all
	 * constraints, the matching Variant B popup is appended to the list (if it
	 * exists and is published), and both are tagged with A/B context.
	 *
	 * If a test's constraints are NOT met, popup A is included in the result
	 * with no A/B context (it shows normally via its own targeting), and
	 * popup B is not added.
	 *
	 * Returns an array of:
	 *   [
	 *     'popup'      => WP_Post,
	 *     'ab_context' => []  // empty OR keys: abTestId, abVariant, abWeightA
	 *   ]
	 *
	 * @param  WP_Post[] $popups
	 * @return array[]
	 */
	public function expand_with_test_variants( array $popups ) {
		$map          = $this->get_popup_test_map();
		$result       = array();
		$rendered_ids = array();

		foreach ( $popups as $popup ) {
			if ( ! isset( $map[ $popup->ID ] ) ) {
				// Not part of any test — include as-is.
				if ( ! in_array( $popup->ID, $rendered_ids, true ) ) {
					$rendered_ids[] = $popup->ID;
					$result[]       = array( 'popup' => $popup, 'ab_context' => array() );
				}
				continue;
			}

			$test = $map[ $popup->ID ];

			// Check all test constraints: paused, schedule, scope, limits.
			// If any fail, popup A shows normally (no test context, B not added).
			$test_active = empty( $test['paused'] )
				&& $this->test_within_schedule( $test )
				&& $this->test_matches_scope( $test )
				&& $this->test_within_limits( $test );

			if ( ! $test_active ) {
				if ( ! in_array( $popup->ID, $rendered_ids, true ) ) {
					$rendered_ids[] = $popup->ID;
					$result[]       = array( 'popup' => $popup, 'ab_context' => array() );
				}
				continue;
			}

			// Test is active — inject Variant A with test context.
			if ( ! in_array( $popup->ID, $rendered_ids, true ) ) {
				$rendered_ids[] = $popup->ID;
				$result[]       = array(
					'popup'      => $popup,
					'ab_context' => array(
						'abTestId'  => $test['test_id'],
						'abVariant' => 'a',
						'abWeightA' => $test['weight_a'],
					),
				);
			}

			// Inject Variant B.
			if ( ! in_array( $test['popup_b'], $rendered_ids, true ) ) {
				$popup_b = get_post( $test['popup_b'] );
				if ( $popup_b && 'wp_pop' === $popup_b->post_type && 'publish' === $popup_b->post_status ) {
					$rendered_ids[] = $test['popup_b'];
					$result[]       = array(
						'popup'      => $popup_b,
						'ab_context' => array(
							'abTestId'  => $test['test_id'],
							'abVariant' => 'b',
							'abWeightA' => $test['weight_a'],
						),
					);
				}
			}
		}

		return $result;
	}

	// -------------------------------------------------------------------------
	// Analytics comparison
	// -------------------------------------------------------------------------

	/**
	 * Returns side-by-side analytics for both popups in a test.
	 *
	 * @param  int $test_id  wp_pop_ab_test post ID.
	 * @param  int $days     Look-back window in days.
	 * @return array[]  [ [ popup_id, variant, label, views, clicks, dismissals, ctr ], ... ]
	 */
	public function get_comparison( $test_id, $days = 30 ) {
		global $wpdb;

		$popup_a = (int) get_post_meta( $test_id, '_wp_pop_ab_popup_a', true );
		$popup_b = (int) get_post_meta( $test_id, '_wp_pop_ab_popup_b', true );

		if ( ! $popup_a || ! $popup_b ) {
			return array();
		}

		$table = $wpdb->prefix . 'wp_pop_events';
		$from  = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT popup_id, event_type, SUM(count) AS total
				 FROM {$table}
				 WHERE popup_id IN (%d, %d) AND event_date >= %s
				 GROUP BY popup_id, event_type",
				$popup_a,
				$popup_b,
				$from
			)
		);
		// phpcs:enable

		$data = array(
			'a' => array( 'popup_id' => $popup_a, 'variant' => 'a', 'label' => get_the_title( $popup_a ), 'views' => 0, 'clicks' => 0, 'dismissals' => 0 ),
			'b' => array( 'popup_id' => $popup_b, 'variant' => 'b', 'label' => get_the_title( $popup_b ), 'views' => 0, 'clicks' => 0, 'dismissals' => 0 ),
		);

		$id_to_key = array( $popup_a => 'a', $popup_b => 'b' );

		foreach ( (array) $rows as $row ) {
			$key = $id_to_key[ (int) $row->popup_id ] ?? null;
			if ( ! $key ) {
				continue;
			}
			switch ( $row->event_type ) {
				case 'view':    $data[ $key ]['views']      += (int) $row->total; break;
				case 'click':   $data[ $key ]['clicks']     += (int) $row->total; break;
				case 'dismiss': $data[ $key ]['dismissals'] += (int) $row->total; break;
			}
		}

		foreach ( $data as &$entry ) {
			$entry['ctr'] = $entry['views']
				? round( ( $entry['clicks'] / $entry['views'] ) * 100, 1 )
				: 0.0;
		}
		unset( $entry );

		return array_values( $data );
	}
}
