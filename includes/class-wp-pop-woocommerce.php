<?php

/**
 * WooCommerce integration: page detection helpers, add-to-cart popup trigger,
 * and cart abandonment trigger.
 *
 * This class is only instantiated when WooCommerce is active.
 *
 * @since   0.2.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Pop_Woocommerce {

	/**
	 * Registers WooCommerce-specific hooks.
	 */
	public function define_hooks() {
		// Allow frontend JS to retrieve popup IDs for the add-to-cart trigger.
		add_action( 'wp_ajax_wp_pop_wc_add_to_cart_popups',        array( $this, 'ajax_add_to_cart_popups' ) );
		add_action( 'wp_ajax_nopriv_wp_pop_wc_add_to_cart_popups', array( $this, 'ajax_add_to_cart_popups' ) );
	}

	// -------------------------------------------------------------------------
	// AJAX: return popup IDs that have wc_trigger = add_to_cart
	// -------------------------------------------------------------------------

	public function ajax_add_to_cart_popups() {
		check_ajax_referer( 'wp_pop_track', 'nonce' );

		$popups = $this->get_popups_by_wc_trigger( 'add_to_cart' );
		wp_send_json_success( array_map( 'absint', $popups ) );
	}

	// -------------------------------------------------------------------------
	// Helpers called from the public class
	// -------------------------------------------------------------------------

	/**
	 * Returns IDs of published popups with a given WC trigger type.
	 *
	 * @param string $trigger  add_to_cart|cart_abandonment
	 * @return int[]
	 */
	public function get_popups_by_wc_trigger( $trigger ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'wp_pop',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'key'     => '_wp_pop_wc_trigger',
						'value'   => $trigger,
						'compare' => '=',
					),
				),
			)
		);

		return (array) $query->posts;
	}

	/**
	 * Returns the WC trigger type for a given popup, or 'none'.
	 *
	 * @param int $popup_id
	 * @return string
	 */
	public function get_wc_trigger( $popup_id ) {
		return get_post_meta( $popup_id, '_wp_pop_wc_trigger', true ) ?: 'none';
	}

	// -------------------------------------------------------------------------
	// Context data for the frontend Interactivity API store
	// -------------------------------------------------------------------------

	/**
	 * Returns WC-specific context flags for data-wp-context.
	 *
	 * @param int $popup_id
	 * @return array
	 */
	public function get_context_data( $popup_id ) {
		return array(
			'wcTrigger'   => $this->get_wc_trigger( $popup_id ),
			'wcActive'    => true,
			'isCartPage'  => function_exists( 'is_cart' ) && is_cart(),
			'isCheckout'  => function_exists( 'is_checkout' ) && is_checkout(),
		);
	}

	// -------------------------------------------------------------------------
	// Admin helpers: available WC page options for the sidebar panel
	// -------------------------------------------------------------------------

	public static function get_wc_page_options() {
		return array(
			array( 'value' => 'shop',     'label' => __( 'Shop Page',     'wp-pop' ) ),
			array( 'value' => 'product',  'label' => __( 'Product Pages', 'wp-pop' ) ),
			array( 'value' => 'cart',     'label' => __( 'Cart Page',     'wp-pop' ) ),
			array( 'value' => 'checkout', 'label' => __( 'Checkout Page', 'wp-pop' ) ),
		);
	}

	public static function get_wc_trigger_options() {
		return array(
			array( 'value' => 'none',             'label' => __( 'None',                        'wp-pop' ) ),
			array( 'value' => 'add_to_cart',      'label' => __( 'After Add to Cart',           'wp-pop' ) ),
			array( 'value' => 'cart_abandonment',  'label' => __( 'Cart Abandonment (Exit Intent)', 'wp-pop' ) ),
		);
	}
}
