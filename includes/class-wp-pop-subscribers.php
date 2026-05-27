<?php

/**
 * Email subscribers: AJAX form handler, admin WP_List_Table, and CSV export.
 *
 * @since   0.2.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Pop_Subscribers {

	/**
	 * Registers AJAX and export hooks.
	 */
	public function define_hooks() {
		add_action( 'wp_ajax_wp_pop_subscribe',        array( $this, 'ajax_subscribe' ) );
		add_action( 'wp_ajax_nopriv_wp_pop_subscribe', array( $this, 'ajax_subscribe' ) );
		add_action( 'admin_post_wp_pop_export_subscribers', array( $this, 'export_csv' ) );
	}

	// -------------------------------------------------------------------------
	// AJAX subscription handler
	// -------------------------------------------------------------------------

	public function ajax_subscribe() {
		check_ajax_referer( 'wp_pop_subscribe', 'nonce' );

		$popup_id = absint( wp_unslash( $_POST['popup_id'] ?? 0 ) );
		$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$name     = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );

		if ( ! $popup_id || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'wp-pop' ) ), 400 );
		}

		if ( 'publish' !== get_post_status( $popup_id ) || 'wp_pop' !== get_post_type( $popup_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid popup.', 'wp-pop' ) ), 400 );
		}

		// Basic rate limiting: max 10 subscribe attempts per IP per minute.
		$ip       = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$rate_key = 'wp_pop_rate_sub_' . md5( $ip );
		$count    = (int) get_transient( $rate_key );
		if ( $count >= 10 ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please try again later.', 'wp-pop' ) ), 429 );
		}
		set_transient( $rate_key, $count + 1, MINUTE_IN_SECONDS );

		$result = $this->subscribe( $email, $name, $popup_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 409 );
		}

		// Record subscribe event in analytics.
		$analytics = new Wp_Pop_Analytics();
		$analytics->record_event( $popup_id, '', 'subscribe' );

		$success_msg = get_post_meta( $popup_id, '_wp_pop_subscribe_success_message', true );
		if ( ! $success_msg ) {
			$success_msg = __( 'Thank you for subscribing!', 'wp-pop' );
		}

		wp_send_json_success( array( 'message' => $success_msg ) );
	}

	// -------------------------------------------------------------------------
	// Core CRUD
	// -------------------------------------------------------------------------

	/**
	 * Adds a subscriber.
	 *
	 * @param string $email
	 * @param string $name
	 * @param int    $popup_id
	 * @return int|WP_Error  New subscriber ID or WP_Error on duplicate.
	 */
	public function subscribe( $email, $name, $popup_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wp_pop_subscribers';

		// Check for existing subscriber.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM $table WHERE email = %s", $email )
		);

		if ( $existing ) {
			return new WP_Error( 'duplicate', __( 'This email is already subscribed.', 'wp-pop' ) );
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$table,
			array(
				'email'         => $email,
				'name'          => $name,
				'popup_id'      => $popup_id,
				'subscribed_at' => current_time( 'mysql' ),
				'status'        => 'active',
				'ip_address'    => $ip,
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Updates subscriber status (active|unsubscribed).
	 */
	public function update_status( $id, $status ) {
		global $wpdb;
		$allowed = array( 'active', 'unsubscribed' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->update(
			$wpdb->prefix . 'wp_pop_subscribers',
			array( 'status' => $status ),
			array( 'id'     => absint( $id ) ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Deletes a subscriber by ID.
	 */
	public function delete( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->delete(
			$wpdb->prefix . 'wp_pop_subscribers',
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);
	}

	/**
	 * Returns subscribers with optional popup filter, pagination.
	 *
	 * @param array $args { popup_id, status, per_page, page, orderby, order }
	 * @return object[] { items, total }
	 */
	public function get_subscribers( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'popup_id' => 0,
			'status'   => '',
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'subscribed_at',
			'order'    => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );

		$table     = $wpdb->prefix . 'wp_pop_subscribers';
		$where     = array( '1=1' );
		$where_fmt = array();

		if ( $args['popup_id'] ) {
			$where[]     = 'popup_id = %d';
			$where_fmt[] = absint( $args['popup_id'] );
		}
		if ( $args['status'] ) {
			$where[]     = 'status = %s';
			$where_fmt[] = sanitize_text_field( $args['status'] );
		}

		$allowed_orderby = array( 'id', 'email', 'name', 'popup_id', 'subscribed_at', 'status' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'subscribed_at';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$where_sql = implode( ' AND ', $where );
		$limit     = absint( $args['per_page'] );
		$offset    = ( absint( $args['page'] ) - 1 ) * $limit;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$total = (int) $wpdb->get_var(
			$where_fmt
				? $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE $where_sql", ...$where_fmt )
				: "SELECT COUNT(*) FROM $table WHERE $where_sql"
		);

		$items = $wpdb->get_results(
			$where_fmt
				? $wpdb->prepare( "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d", ...array_merge( $where_fmt, array( $limit, $offset ) ) )
				: $wpdb->prepare( "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d", $limit, $offset )
		);
		// phpcs:enable

		return (object) array( 'items' => $items ?: array(), 'total' => $total );
	}

	// -------------------------------------------------------------------------
	// CSV export
	// -------------------------------------------------------------------------

	public function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-pop' ) );
		}
		check_admin_referer( 'wp_pop_export_subscribers' );

		$popup_id = absint( $_GET['popup_id'] ?? 0 );
		$result   = $this->get_subscribers( array( 'popup_id' => $popup_id, 'per_page' => 99999 ) );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="wp-pop-subscribers-' . gmdate( 'Ymd' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $out, array( 'ID', 'Email', 'Name', 'Popup ID', 'Subscribed At', 'Status' ) );
		foreach ( $result->items as $row ) {
			fputcsv( $out, array( $row->id, $row->email, $row->name, $row->popup_id, $row->subscribed_at, $row->status ) );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}
