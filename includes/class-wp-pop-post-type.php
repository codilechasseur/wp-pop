<?php

/**
 * Registers the wp_pop custom post type and its meta fields.
 *
 * @since   0.1.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Pop_Post_Type {

	/**
	 * Registers the wp_pop custom post type.
	 */
	public static function register() {
		$labels = array(
			'name'               => _x( 'Popups', 'post type general name', 'wp-pop' ),
			'singular_name'      => _x( 'Popup', 'post type singular name', 'wp-pop' ),
			'menu_name'          => _x( 'WP Pop!', 'admin menu', 'wp-pop' ),
			'name_admin_bar'     => _x( 'Popup', 'add new on admin bar', 'wp-pop' ),
			'add_new'            => _x( 'Add New', 'popup', 'wp-pop' ),
			'add_new_item'       => __( 'Add New Popup', 'wp-pop' ),
			'new_item'           => __( 'New Popup', 'wp-pop' ),
			'edit_item'          => __( 'Edit Popup', 'wp-pop' ),
			'view_item'          => __( 'View Popup', 'wp-pop' ),
			'all_items'          => __( 'All Popups', 'wp-pop' ),
			'search_items'       => __( 'Search Popups', 'wp-pop' ),
			'not_found'          => __( 'No popups found.', 'wp-pop' ),
			'not_found_in_trash' => __( 'No popups found in Trash.', 'wp-pop' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Manages site popups.', 'wp-pop' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_icon'          => 'dashicons-format-chat',
			'menu_position'      => 58,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array( 'title', 'editor' ),
			'show_in_rest'       => true,
		);

		register_post_type( 'wp_pop', $args );
	}

	/**
	 * Registers the wp_pop_ab_test custom post type.
	 * Tests appear as a sub-menu under WP Pop! and are managed with classic
	 * meta boxes (no Gutenberg sidebar needed).
	 */
	public static function register_ab_test_cpt() {
		$labels = array(
			'name'               => _x( 'A/B Tests', 'post type general name', 'wp-pop' ),
			'singular_name'      => _x( 'A/B Test', 'post type singular name', 'wp-pop' ),
			'menu_name'          => _x( 'A/B Tests', 'admin menu', 'wp-pop' ),
			'add_new'            => _x( 'New A/B Test', 'post', 'wp-pop' ),
			'add_new_item'       => __( 'Add New A/B Test', 'wp-pop' ),
			'new_item'           => __( 'New A/B Test', 'wp-pop' ),
			'edit_item'          => __( 'Edit A/B Test', 'wp-pop' ),
			'all_items'          => __( 'A/B Tests', 'wp-pop' ),
			'search_items'       => __( 'Search A/B Tests', 'wp-pop' ),
			'not_found'          => __( 'No A/B tests found.', 'wp-pop' ),
			'not_found_in_trash' => __( 'No A/B tests found in Trash.', 'wp-pop' ),
		);

		register_post_type(
			'wp_pop_ab_test',
			array(
				'labels'             => $labels,
				'description'        => __( 'Manages WP Pop! A/B tests.', 'wp-pop' ),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => 'edit.php?post_type=wp_pop',
				'query_var'          => false,
				'rewrite'            => false,
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'supports'           => array( 'title' ),
				'show_in_rest'       => false,
			)
		);
	}

	/**
	 * Registers the wp_pop_archived custom post status.
	 */
	public static function register_archived_status() {
		register_post_status(
			'wp_pop_archived',
			array(
				'label'                     => _x( 'Archived', 'post status', 'wp-pop' ),
				'label_count'               => _n_noop(
					'Archived <span class="count">(%s)</span>',
					'Archived <span class="count">(%s)</span>',
					'wp-pop'
				),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
			)
		);
	}
}
