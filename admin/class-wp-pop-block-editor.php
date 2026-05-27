<?php

/**
 * Enqueues the block-editor sidebar assets (React panels) on the wp_pop
 * post type edit screen.  Also provides the localised data the sidebar
 * needs: available post types, taxonomies, roles, WC status, etc.
 *
 * @since   0.2.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Pop_Block_Editor {

	/**
	 * Registers the enqueue hook.
	 */
	public function define_hooks() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Enqueues the compiled sidebar script + style only on wp_pop edit screens.
	 */
	public function enqueue_editor_assets() {
		$screen = get_current_screen();
		if ( ! $screen || 'wp_pop' !== $screen->post_type ) {
			return;
		}

		$asset_file = WP_POP_PLUGIN_DIR . 'build/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return; // Build not run yet.
		}
		$asset = include $asset_file;

		wp_enqueue_script(
			'wp-pop-editor',
			WP_POP_PLUGIN_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		if ( file_exists( WP_POP_PLUGIN_DIR . 'build/index.css' ) ) {
			wp_enqueue_style(
				'wp-pop-editor',
				WP_POP_PLUGIN_URL . 'build/index.css',
				array( 'wp-components' ),
				$asset['version']
			);
		}

		wp_localize_script( 'wp-pop-editor', 'wpPopEditor', $this->get_editor_data() );
	}

	// -------------------------------------------------------------------------
	// Localisation data
	// -------------------------------------------------------------------------

	private function get_editor_data() {
		return array(
			'postTypes'      => $this->get_post_type_options(),
			'taxonomies'     => $this->get_taxonomy_options(),
			'userRoles'      => $this->get_role_options(),
			'wooActive'      => class_exists( 'WooCommerce' ),
			'wcPageOptions'  => class_exists( 'WooCommerce' ) ? Wp_Pop_Woocommerce::get_wc_page_options() : array(),
			'wcTriggerOptions' => class_exists( 'WooCommerce' ) ? Wp_Pop_Woocommerce::get_wc_trigger_options() : array(),
			'popupTypeOptions' => $this->get_popup_type_options(),
			'triggerOptions'   => $this->get_trigger_options(),
			'frequencyOptions' => $this->get_frequency_options(),
			'animationOptions' => $this->get_animation_options(),
			'deviceOptions'    => $this->get_device_options(),
			'visitorOptions'   => $this->get_visitor_options(),
			'loggedInOptions'  => $this->get_logged_in_options(),
		);
	}

	private function get_post_type_options() {
		$types = get_post_types( array( 'public' => true ), 'objects' );
		$opts  = array();
		foreach ( $types as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}
			$opts[] = array( 'value' => $type->name, 'label' => $type->label );
		}
		return $opts;
	}

	private function get_taxonomy_options() {
		$taxes = get_taxonomies( array( 'public' => true ), 'objects' );
		$opts  = array();
		foreach ( $taxes as $tax ) {
			$opts[] = array( 'value' => $tax->name, 'label' => $tax->label );
		}
		return $opts;
	}

	private function get_role_options() {
		global $wp_roles;
		$opts = array();
		foreach ( $wp_roles->role_names as $role => $name ) {
			$opts[] = array( 'value' => $role, 'label' => $name );
		}
		return $opts;
	}

	private function get_popup_type_options() {
		return array(
			array( 'value' => 'modal',       'label' => __( 'Modal (center)',        'wp-pop' ) ),
			array( 'value' => 'top-bar',     'label' => __( 'Top Bar',               'wp-pop' ) ),
			array( 'value' => 'bottom-bar',  'label' => __( 'Bottom Bar',            'wp-pop' ) ),
			array( 'value' => 'slide-in-tl', 'label' => __( 'Slide-in Top Left',     'wp-pop' ) ),
			array( 'value' => 'slide-in-tr', 'label' => __( 'Slide-in Top Right',    'wp-pop' ) ),
			array( 'value' => 'slide-in-bl', 'label' => __( 'Slide-in Bottom Left',  'wp-pop' ) ),
			array( 'value' => 'slide-in-br', 'label' => __( 'Slide-in Bottom Right', 'wp-pop' ) ),
			array( 'value' => 'fullscreen',  'label' => __( 'Fullscreen Takeover',   'wp-pop' ) ),
			array( 'value' => 'tooltip',     'label' => __( 'Tooltip / Inline',      'wp-pop' ) ),
		);
	}

	private function get_trigger_options() {
		return array(
			array( 'value' => 'time',              'label' => __( 'Time Delay',          'wp-pop' ) ),
			array( 'value' => 'scroll',            'label' => __( 'Scroll Depth',        'wp-pop' ) ),
			array( 'value' => 'interaction',       'label' => __( 'First Interaction',   'wp-pop' ) ),
			array( 'value' => 'exit_intent',       'label' => __( 'Exit Intent',         'wp-pop' ) ),
			array( 'value' => 'click',             'label' => __( 'Element Click',       'wp-pop' ) ),
			array( 'value' => 'inactivity',        'label' => __( 'Inactivity',          'wp-pop' ) ),
			array( 'value' => 'element_visibility','label' => __( 'Element Visible',     'wp-pop' ) ),
		);
	}

	private function get_frequency_options() {
		return array(
			array( 'value' => 'always', 'label' => __( 'Every page load', 'wp-pop' ) ),
			array( 'value' => 'session','label' => __( 'Once per session', 'wp-pop' ) ),
			array( 'value' => 'daily',  'label' => __( 'Once per day',     'wp-pop' ) ),
			array( 'value' => 'weekly', 'label' => __( 'Once per week',    'wp-pop' ) ),
			array( 'value' => 'once',   'label' => __( 'Once ever',        'wp-pop' ) ),
		);
	}

	private function get_animation_options() {
		return array(
			array( 'value' => 'none',       'label' => __( 'None',       'wp-pop' ) ),
			array( 'value' => 'fade',       'label' => __( 'Fade',       'wp-pop' ) ),
			array( 'value' => 'slide-down', 'label' => __( 'Slide Down', 'wp-pop' ) ),
			array( 'value' => 'zoom',       'label' => __( 'Zoom',       'wp-pop' ) ),
		);
	}

	private function get_device_options() {
		return array(
			array( 'value' => 'all',     'label' => __( 'All Devices', 'wp-pop' ) ),
			array( 'value' => 'desktop', 'label' => __( 'Desktop Only', 'wp-pop' ) ),
			array( 'value' => 'tablet',  'label' => __( 'Tablet Only',  'wp-pop' ) ),
			array( 'value' => 'mobile',  'label' => __( 'Mobile Only',  'wp-pop' ) ),
		);
	}

	private function get_visitor_options() {
		return array(
			array( 'value' => 'all',       'label' => __( 'All Visitors',       'wp-pop' ) ),
			array( 'value' => 'new',       'label' => __( 'New Visitors',        'wp-pop' ) ),
			array( 'value' => 'returning', 'label' => __( 'Returning Visitors',  'wp-pop' ) ),
		);
	}

	private function get_logged_in_options() {
		return array(
			array( 'value' => 'all',        'label' => __( 'Everyone',        'wp-pop' ) ),
			array( 'value' => 'logged_in',  'label' => __( 'Logged-in users', 'wp-pop' ) ),
			array( 'value' => 'logged_out', 'label' => __( 'Logged-out users','wp-pop' ) ),
		);
	}
}
