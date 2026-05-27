<?php

/**
 * Analytics: stores per-day event counts and provides data for the dashboard.
 *
 * Events tracked: 'view', 'click', 'dismiss', 'convert'.
 * Each row in {prefix}wp_pop_events is an upsert on the UNIQUE key
 * (popup_id, variant_id, event_type, event_date).
 *
 * @since   0.2.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Pop_Analytics {

	/**
	 * Registers AJAX hooks (both logged-in and non-logged-in users can track).
	 */
	public function define_hooks() {
		add_action( 'wp_ajax_wp_pop_track_event',        array( $this, 'ajax_track_event' ) );
		add_action( 'wp_ajax_nopriv_wp_pop_track_event', array( $this, 'ajax_track_event' ) );
	}

	// -------------------------------------------------------------------------
	// AJAX handler
	// -------------------------------------------------------------------------

	public function ajax_track_event() {
		check_ajax_referer( 'wp_pop_track', 'nonce' );

		$popup_id   = absint( wp_unslash( $_POST['popup_id']   ?? 0 ) );
		$variant_id = sanitize_text_field( wp_unslash( $_POST['variant_id'] ?? '' ) );
		$event_type = sanitize_key( wp_unslash( $_POST['event_type']  ?? '' ) );

		$allowed_events = array( 'view', 'click', 'dismiss', 'convert' );
		if ( ! $popup_id || ! in_array( $event_type, $allowed_events, true ) ) {
			wp_send_json_error( 'invalid_params', 400 );
		}

		if ( 'publish' !== get_post_status( $popup_id ) || 'wp_pop' !== get_post_type( $popup_id ) ) {
			wp_send_json_error( 'invalid_popup', 400 );
		}

		$this->record_event( $popup_id, $variant_id, $event_type );
		wp_send_json_success();
	}

	// -------------------------------------------------------------------------
	// Core data methods
	// -------------------------------------------------------------------------

	/**
	 * Inserts or increments the event counter for today.
	 *
	 * @param int    $popup_id
	 * @param string $variant_id
	 * @param string $event_type  view|click|dismiss|convert
	 */
	public function record_event( $popup_id, $variant_id, $event_type ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wp_pop_events';
		$today = current_time( 'Y-m-d' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table WHERE popup_id=%d AND variant_id=%s AND event_type=%s AND event_date=%s",
				$popup_id,
				$variant_id,
				$event_type,
				$today
			)
		);

		if ( $existing ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $table SET count = count + 1 WHERE id=%d",
					(int) $existing
				)
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'popup_id'   => $popup_id,
					'variant_id' => $variant_id,
					'event_type' => $event_type,
					'event_date' => $today,
					'count'      => 1,
				),
				array( '%d', '%s', '%s', '%s', '%d' )
			);
		}
		// phpcs:enable
	}

	/**
	 * Returns summary totals for all popups or a specific popup.
	 *
	 * @param int|null $popup_id  Omit for site-wide summary.
	 * @return array { views, clicks, dismissals, conversions, conversion_rate }
	 */
	public function get_summary( $popup_id = null ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wp_pop_events';
		$where = '';
		$args  = array();

		if ( $popup_id ) {
			$where = 'WHERE popup_id = %d';
			$args  = array( (int) $popup_id );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$rows = $wpdb->get_results(
			$args
				? $wpdb->prepare( "SELECT event_type, SUM(count) AS total FROM $table $where GROUP BY event_type", ...$args )
				: "SELECT event_type, SUM(count) AS total FROM $table GROUP BY event_type"
		);
		// phpcs:enable

		$data = array( 'views' => 0, 'clicks' => 0, 'dismissals' => 0, 'conversions' => 0 );
		foreach ( (array) $rows as $row ) {
			switch ( $row->event_type ) {
				case 'view':    $data['views']       = (int) $row->total; break;
				case 'click':   $data['clicks']      = (int) $row->total; break;
				case 'dismiss': $data['dismissals']  = (int) $row->total; break;
				case 'convert': $data['conversions'] = (int) $row->total; break;
			}
		}

		$data['conversion_rate'] = $data['views']
			? round( ( $data['clicks'] / $data['views'] ) * 100, 1 )
			: 0;

		return $data;
	}

	/**
	 * Returns daily totals for a popup over the last N days, for Chart.js.
	 *
	 * @param int $popup_id
	 * @param int $days
	 * @return array { labels: string[], views: int[], clicks: int[], dismissals: int[] }
	 */
	public function get_daily_data( $popup_id, $days = 30 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wp_pop_events';
		$from  = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_date, event_type, SUM(count) AS total
				 FROM $table
				 WHERE popup_id = %d AND event_date >= %s
				 GROUP BY event_date, event_type
				 ORDER BY event_date ASC",
				$popup_id,
				$from
			)
		);
		// phpcs:enable

		// Build labelled arrays for each day.
		$index = array();
		foreach ( (array) $rows as $row ) {
			$d = $row->event_date;
			if ( ! isset( $index[ $d ] ) ) {
				$index[ $d ] = array( 'views' => 0, 'clicks' => 0, 'dismissals' => 0 );
			}
			switch ( $row->event_type ) {
				case 'view':    $index[ $d ]['views']      += (int) $row->total; break;
				case 'click':   $index[ $d ]['clicks']     += (int) $row->total; break;
				case 'dismiss': $index[ $d ]['dismissals'] += (int) $row->total; break;
			}
		}

		$labels     = array();
		$views      = array();
		$clicks     = array();
		$dismissals = array();

		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$date = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
			$labels[]     = gmdate( 'M j', strtotime( $date ) );
			$views[]      = $index[ $date ]['views']      ?? 0;
			$clicks[]     = $index[ $date ]['clicks']     ?? 0;
			$dismissals[] = $index[ $date ]['dismissals'] ?? 0;
		}

		return compact( 'labels', 'views', 'clicks', 'dismissals' );
	}

	/**
	 * Per-popup summary table data including A/B variant breakdown.
	 *
	 * @param int $days
	 * @return array[]  Array of popup rows.
	 */
	public function get_per_popup_summary( $days = 30 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wp_pop_events';
		$from  = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT popup_id, variant_id, event_type, SUM(count) AS total
				 FROM $table
				 WHERE event_date >= %s
				 GROUP BY popup_id, variant_id, event_type",
				$from
			)
		);
		// phpcs:enable

		$popups = array();
		foreach ( (array) $rows as $row ) {
			$pid = (int) $row->popup_id;
			$vid = $row->variant_id;
			if ( ! isset( $popups[ $pid ] ) ) {
				$post              = get_post( $pid );
				$popups[ $pid ]    = array(
					'id'       => $pid,
					'title'    => $post ? $post->post_title : "#{$pid}",
					'variants' => array(),
					'totals'   => array( 'views' => 0, 'clicks' => 0, 'dismissals' => 0 ),
				);
			}
			if ( ! isset( $popups[ $pid ]['variants'][ $vid ] ) ) {
				$popups[ $pid ]['variants'][ $vid ] = array( 'views' => 0, 'clicks' => 0, 'dismissals' => 0 );
			}
			switch ( $row->event_type ) {
				case 'view':    $popups[ $pid ]['variants'][ $vid ]['views']++; break;
				case 'click':   $popups[ $pid ]['variants'][ $vid ]['clicks']++; break;
				case 'dismiss': $popups[ $pid ]['variants'][ $vid ]['dismissals']++; break;
			}
			// Rollup into totals.
			$total = (int) $row->total;
			switch ( $row->event_type ) {
				case 'view':    $popups[ $pid ]['totals']['views']      += $total; break;
				case 'click':   $popups[ $pid ]['totals']['clicks']     += $total; break;
				case 'dismiss': $popups[ $pid ]['totals']['dismissals'] += $total; break;
			}
		}

		return array_values( $popups );
	}
}
