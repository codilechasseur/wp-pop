<?php

/**
 * The admin-specific functionality of WP Pop!
 *
 * @since   0.1.0
 * @package Wp_Pop
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Pop_Admin {

	protected $plugin_name;

	protected $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	// -------------------------------------------------------------------------
	// Assets
	// -------------------------------------------------------------------------

	public function enqueue_styles( $hook_suffix ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$allowed = array(
			'wp_pop',
			'wp-pop_page_wp-pop-settings',
			'wp-pop_page_wp-pop-analytics',
			'wp-pop_page_wp-pop-subscribers',
		);
		if ( ! in_array( $screen->id, $allowed, true ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-admin',
			WP_POP_PLUGIN_URL . 'admin/css/wp-pop-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	public function enqueue_scripts( $hook_suffix ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$allowed = array(
			'wp_pop',
			'wp-pop_page_wp-pop-settings',
			'wp-pop_page_wp-pop-analytics',
			'wp-pop_page_wp-pop-subscribers',
		);
		if ( ! in_array( $screen->id, $allowed, true ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-admin',
			WP_POP_PLUGIN_URL . 'admin/js/wp-pop-admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);
	}

	// -------------------------------------------------------------------------
	// Admin menu
	// -------------------------------------------------------------------------

	public function add_plugin_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=wp_pop',
			__( 'Settings', 'wp-pop' ),
			__( 'Settings', 'wp-pop' ),
			'manage_options',
			'wp-pop-settings',
			array( $this, 'display_settings_page' )
		);

		add_submenu_page(
			'edit.php?post_type=wp_pop',
			__( 'Analytics', 'wp-pop' ),
			__( 'Analytics', 'wp-pop' ),
			'manage_options',
			'wp-pop-analytics',
			array( $this, 'display_analytics_page' )
		);

		add_submenu_page(
			'edit.php?post_type=wp_pop',
			__( 'Subscribers', 'wp-pop' ),
			__( 'Subscribers', 'wp-pop' ),
			'manage_options',
			'wp-pop-subscribers',
			array( $this, 'display_subscribers_page' )
		);
	}

	public function display_analytics_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once WP_POP_PLUGIN_DIR . 'admin/partials/wp-pop-admin-analytics.php';
	}

	public function display_subscribers_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once WP_POP_PLUGIN_DIR . 'admin/partials/wp-pop-admin-subscribers.php';
	}

	public function display_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once WP_POP_PLUGIN_DIR . 'admin/partials/wp-pop-admin-settings.php';
	}

	// -------------------------------------------------------------------------
	// Settings (wp_pop_settings_group / wp-pop-settings page)
	// -------------------------------------------------------------------------

	public function init_settings() {
		register_setting(
'wp_pop_settings_group',
'wp_pop_default_frequency',
array(
'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_frequency' ),
				'default'           => 'session',
			)
		);

		register_setting(
'wp_pop_settings_group',
'wp_pop_default_delay',
array(
'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		add_settings_section(
'wp_pop_general_section',
__( 'Popup Defaults', 'wp-pop' ),
array( $this, 'general_section_callback' ),
'wp-pop-settings'
);

		add_settings_field(
'wp_pop_default_frequency',
__( 'Default Frequency', 'wp-pop' ),
array( $this, 'default_frequency_field_callback' ),
'wp-pop-settings',
'wp_pop_general_section'
);

		add_settings_field(
'wp_pop_default_delay',
__( 'Default Delay', 'wp-pop' ),
array( $this, 'default_delay_field_callback' ),
'wp-pop-settings',
'wp_pop_general_section'
);

		register_setting(
'wp_pop_settings_group',
'wp_pop_default_trigger',
array(
'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_trigger' ),
				'default'           => 'time',
			)
		);

		register_setting(
'wp_pop_settings_group',
'wp_pop_default_scroll_threshold',
array(
'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_scroll_threshold' ),
				'default'           => 50,
			)
		);

		add_settings_field(
'wp_pop_default_trigger',
__( 'Default Trigger', 'wp-pop' ),
array( $this, 'default_trigger_field_callback' ),
'wp-pop-settings',
'wp_pop_general_section'
);

		add_settings_field(
'wp_pop_default_scroll_threshold',
__( 'Default Scroll Threshold', 'wp-pop' ),
array( $this, 'default_scroll_threshold_field_callback' ),
'wp-pop-settings',
'wp_pop_general_section'
);

		// ---- Appearance defaults -------------------------------------------

		register_setting(
			'wp_pop_settings_group',
			'wp_pop_default_width_preset',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_width_preset' ),
				'default'           => 'medium',
			)
		);

		register_setting(
			'wp_pop_settings_group',
			'wp_pop_default_custom_width',
			array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_custom_width' ),
				'default'           => 40,
			)
		);

		register_setting(
'wp_pop_settings_group',
'wp_pop_default_overlay_color',
array(
'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#000000',
			)
		);

		register_setting(
'wp_pop_settings_group',
'wp_pop_default_overlay_opacity',
array(
'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_opacity' ),
				'default'           => 0.65,
			)
		);

		register_setting(
'wp_pop_settings_group',
'wp_pop_default_border_radius',
array(
'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_border_radius' ),
				'default'           => 8,
			)
		);

		register_setting(
'wp_pop_settings_group',
'wp_pop_default_padding',
array(
'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_padding' ),
				'default'           => 2,
			)
		);

		add_settings_section(
'wp_pop_appearance_section',
__( 'Appearance Defaults', 'wp-pop' ),
array( $this, 'appearance_section_callback' ),
'wp-pop-settings'
);

		register_setting( 'wp_pop_settings_group', 'wp_pop_default_popup_type',
			array( 'type' => 'string', 'sanitize_callback' => array( $this, 'sanitize_popup_type' ), 'default' => 'modal' ) );
		register_setting( 'wp_pop_settings_group', 'wp_pop_default_animation',
			array( 'type' => 'string', 'sanitize_callback' => array( $this, 'sanitize_animation' ), 'default' => 'fade' ) );

		add_settings_field( 'wp_pop_default_popup_type', __( 'Popup Type', 'wp-pop' ),
			array( $this, 'default_popup_type_field_callback' ), 'wp-pop-settings', 'wp_pop_appearance_section' );
		add_settings_field( 'wp_pop_default_animation', __( 'Animation', 'wp-pop' ),
			array( $this, 'default_animation_field_callback' ), 'wp-pop-settings', 'wp_pop_appearance_section' );

		add_settings_field(
			'wp_pop_default_width',
			__( 'Popup Width', 'wp-pop' ),
			array( $this, 'default_width_field_callback' ),
			'wp-pop-settings',
			'wp_pop_appearance_section'
		);

		add_settings_field(
'wp_pop_default_overlay_color',
__( 'Overlay Color', 'wp-pop' ),
array( $this, 'default_overlay_color_field_callback' ),
'wp-pop-settings',
'wp_pop_appearance_section'
);

		add_settings_field(
'wp_pop_default_overlay_opacity',
__( 'Overlay Opacity', 'wp-pop' ),
array( $this, 'default_overlay_opacity_field_callback' ),
'wp-pop-settings',
'wp_pop_appearance_section'
);

		add_settings_field(
'wp_pop_default_border_radius',
__( 'Corner Radius', 'wp-pop' ),
array( $this, 'default_border_radius_field_callback' ),
'wp-pop-settings',
'wp_pop_appearance_section'
);

		add_settings_field(
'wp_pop_default_padding',
__( 'Dialog Padding', 'wp-pop' ),
array( $this, 'default_padding_field_callback' ),
'wp-pop-settings',
'wp_pop_appearance_section'
);

		register_setting(
'wp_pop_settings_group',
'wp_pop_default_close_color',
array(
'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#000000',
			)
		);

		register_setting(
'wp_pop_settings_group',
'wp_pop_default_hide_title',
array(
'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		add_settings_field(
'wp_pop_default_close_color',
__( 'Close Icon Color', 'wp-pop' ),
array( $this, 'default_close_color_field_callback' ),
'wp-pop-settings',
'wp_pop_appearance_section'
);

		add_settings_field(
'wp_pop_default_hide_title',
__( 'Hide Title', 'wp-pop' ),
array( $this, 'default_hide_title_field_callback' ),
'wp-pop-settings',
'wp_pop_appearance_section'
);

		register_setting(
'wp_pop_settings_group',
'wp_pop_default_columns_gap',
array(
'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_columns_gap' ),
				'default'           => 2,
			)
		);

		add_settings_field(
'wp_pop_default_columns_gap',
__( 'Column Gap', 'wp-pop' ),
array( $this, 'default_columns_gap_field_callback' ),
'wp-pop-settings',
'wp_pop_appearance_section'
);

		register_setting(
			'wp_pop_settings_group',
			'wp_pop_default_blur_amount',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_blur_amount' ),
				'default'           => 0,
			)
		);

		add_settings_field(
			'wp_pop_default_blur_amount',
			__( 'Background Blur', 'wp-pop' ),
			array( $this, 'default_blur_amount_field_callback' ),
			'wp-pop-settings',
			'wp_pop_appearance_section'
		);
	}

	public function sanitize_frequency( $value ) {
		$allowed = array( 'always', 'session', 'daily', 'weekly', 'once' );
		return in_array( $value, $allowed, true ) ? $value : 'session';
	}

	public function sanitize_popup_type( $value ) {
		$allowed = array( 'modal', 'top-bar', 'bottom-bar', 'slide-in-tl', 'slide-in-tr', 'slide-in-bl', 'slide-in-br', 'fullscreen', 'tooltip' );
		return in_array( $value, $allowed, true ) ? $value : 'modal';
	}

	public function sanitize_animation( $value ) {
		$allowed = array( 'none', 'fade', 'slide-down', 'zoom' );
		return in_array( $value, $allowed, true ) ? $value : 'fade';
	}

	public function sanitize_trigger( $value ) {
		$allowed = array( 'time', 'scroll', 'interaction', 'exit_intent', 'click', 'inactivity', 'element_visibility' );
		return in_array( $value, $allowed, true ) ? $value : 'time';
	}

	public function sanitize_scroll_threshold( $value ) {
		return min( 100, max( 0, absint( $value ) ) );
	}

	public function sanitize_opacity( $value ) {
		return max( 0.0, min( 1.0, round( (float) $value, 2 ) ) );
	}

	public function sanitize_border_radius( $value ) {
		return min( absint( $value ), 100 );
	}

	public function sanitize_padding( $value ) {
		return max( 0.0, min( 10.0, round( (float) $value, 2 ) ) );
	}

	public function sanitize_columns_gap( $value ) {
		return max( 0.0, min( 10.0, round( (float) $value, 2 ) ) );
	}

	public function sanitize_blur_amount( $value ) {
		return min( 50, max( 0, absint( $value ) ) );
	}

	public function sanitize_width_preset( $value ) {
		$allowed = array_merge( array_keys( $this->width_presets() ), array( 'custom' ) );
		return in_array( $value, $allowed, true ) ? $value : 'medium';
	}

	public function sanitize_custom_width( $value ) {
		return max( 10.0, min( 200.0, round( (float) $value, 1 ) ) );
	}

	private function width_presets() {
		return array(
			'small'  => array( 'label' => __( 'Small (~420px)', 'wp-pop' ),  'css' => '26rem' ),
			'medium' => array( 'label' => __( 'Medium (~640px)', 'wp-pop' ), 'css' => '40rem' ),
			'large'  => array( 'label' => __( 'Large (~900px)', 'wp-pop' ),  'css' => '56rem' ),
			'full'   => array( 'label' => __( 'Full Width', 'wp-pop' ),       'css' => 'none'  ),
		);
	}

	public function default_width_field_callback() {
		$preset       = get_option( 'wp_pop_default_width_preset', 'medium' );
		$custom_width = get_option( 'wp_pop_default_custom_width', 40 );
		$this->render_width_select(
			'wp_pop_default_width_preset',
			'wp_pop_default_custom_width',
			'wp-pop-default-custom-width-wrap',
			$preset,
			$custom_width
		);
	}

	private function render_width_select( $preset_name, $custom_name, $custom_wrap_id, $current_preset, $current_custom, $include_inherit = false ) {
		?>
		<select name="<?php echo esc_attr( $preset_name ); ?>" id="<?php echo esc_attr( $preset_name ); ?>">
			<?php if ( $include_inherit ) : ?>
			<option value=""><?php esc_html_e( '— Use site default —', 'wp-pop' ); ?></option>
			<?php endif; ?>
			<?php foreach ( $this->width_presets() as $key => $info ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $current_preset, $key ); ?>><?php echo esc_html( $info['label'] ); ?></option>
			<?php endforeach; ?>
			<option value="custom"<?php selected( $current_preset, 'custom' ); ?>><?php esc_html_e( 'Custom&hellip;', 'wp-pop' ); ?></option>
		</select>
		<div id="<?php echo esc_attr( $custom_wrap_id ); ?>"<?php echo 'custom' !== $current_preset ? ' style="display:none;margin-top:6px"' : ' style="margin-top:6px"'; ?>>
			<input type="number" name="<?php echo esc_attr( $custom_name ); ?>" value="<?php echo esc_attr( $current_custom ); ?>" min="10" max="200" step="1" class="small-text"> <?php esc_html_e( 'rem', 'wp-pop' ); ?>
			<p class="description"><?php esc_html_e( '1 rem ≈ 16 px. A value of 40 gives ~640 px wide.', 'wp-pop' ); ?></p>
		</div>
		<?php
	}

	public function general_section_callback() {
		echo '<p>' . esc_html__( 'These defaults apply to new popups. Each popup can override them individually.', 'wp-pop' ) . '</p>';
	}

	public function appearance_section_callback() {
		echo '<p>' . esc_html__( 'Default visual style for all popups. Each popup can override these individually.', 'wp-pop' ) . '</p>';
	}

	public function default_popup_type_field_callback() {
		$value = get_option( 'wp_pop_default_popup_type', 'modal' );
		echo '<select name="wp_pop_default_popup_type">';
		foreach ( $this->popup_type_labels() as $key => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $value, $key, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	public function default_animation_field_callback() {
		$value = get_option( 'wp_pop_default_animation', 'fade' );
		echo '<select name="wp_pop_default_animation">';
		foreach ( $this->animation_labels() as $key => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $value, $key, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	public function default_overlay_color_field_callback() {
		$value = get_option( 'wp_pop_default_overlay_color', '#000000' );
		printf(
			'<input type="color" name="wp_pop_default_overlay_color" value="%s">',
			esc_attr( $value )
		);
	}

	public function default_overlay_opacity_field_callback() {
		$value = get_option( 'wp_pop_default_overlay_opacity', 0.65 );
		printf(
			'<input type="number" name="wp_pop_default_overlay_opacity" value="%s" min="0" max="1" step="0.05" class="small-text">',
			esc_attr( $value )
		);
		echo '<p class="description">' . esc_html__( '0 = fully transparent, 1 = fully opaque.', 'wp-pop' ) . '</p>';
	}

	public function default_border_radius_field_callback() {
		$value = absint( get_option( 'wp_pop_default_border_radius', 8 ) );
		printf(
			'<input type="number" name="wp_pop_default_border_radius" value="%d" min="0" max="100" step="1" class="small-text"> %s',
			$value,
			esc_html__( 'px', 'wp-pop' )
		);
	}

	public function default_padding_field_callback() {
		$value = get_option( 'wp_pop_default_padding', 2 );
		printf(
			'<input type="number" name="wp_pop_default_padding" value="%s" min="0" max="10" step="0.5" class="small-text"> %s',
			esc_attr( $value ),
			esc_html__( 'rem', 'wp-pop' )
		);
		echo '<p class="description">' . esc_html__( 'Inner spacing of the dialog box.', 'wp-pop' ) . '</p>';
	}

	public function default_close_color_field_callback() {
		$value = get_option( 'wp_pop_default_close_color', '#000000' );
		printf(
			'<input type="color" name="wp_pop_default_close_color" value="%s">',
			esc_attr( $value )
		);
	}

	public function default_hide_title_field_callback() {
		$value = absint( get_option( 'wp_pop_default_hide_title', 0 ) );
		printf(
			'<label><input type="checkbox" name="wp_pop_default_hide_title" value="1"%s> %s</label>',
			checked( 1, $value, false ),
			esc_html__( 'Hide the popup title by default', 'wp-pop' )
		);
	}

	public function default_columns_gap_field_callback() {
		$value = get_option( 'wp_pop_default_columns_gap', 2 );
		printf(
			'<input type="number" name="wp_pop_default_columns_gap" value="%s" min="0" max="10" step="0.5" class="small-text"> %s',
			esc_attr( $value ),
			esc_html__( 'rem', 'wp-pop' )
		);
		echo '<p class="description">' . esc_html__( 'Gap between columns. 0 = no gap. Can also be tuned per-column using the Gutenberg Spacing panel.', 'wp-pop' ) . '</p>';
	}

	public function default_blur_amount_field_callback() {
		$value = absint( get_option( 'wp_pop_default_blur_amount', 0 ) );
		printf(
			'<input type="number" name="wp_pop_default_blur_amount" value="%d" min="0" max="50" step="1" class="small-text"> %s',
			$value,
			esc_html__( 'px', 'wp-pop' )
		);
		echo '<p class="description">' . esc_html__( 'Blur radius applied to the page behind the popup overlay. 0 = no blur.', 'wp-pop' ) . '</p>';
	}

	public function default_frequency_field_callback() {
		$value = get_option( 'wp_pop_default_frequency', 'session' );
		foreach ( $this->frequency_labels() as $key => $label ) {
			printf(
'<label style="display:block;margin-bottom:6px"><input type="radio" name="wp_pop_default_frequency" value="%s"%s> %s</label>',
esc_attr( $key ),
checked( $value, $key, false ),
esc_html( $label )
);
		}
	}

	public function default_delay_field_callback() {
		$value = absint( get_option( 'wp_pop_default_delay', 0 ) );
		printf(
'<input type="number" name="wp_pop_default_delay" value="%d" min="0" max="60" step="1" class="small-text"> %s',
$value,
esc_html__( 'seconds', 'wp-pop' )
);
		echo '<p class="description">' . esc_html__( 'How long to wait before showing the popup. 0 = immediately.', 'wp-pop' ) . '</p>';
	}

	public function default_trigger_field_callback() {
		$value = get_option( 'wp_pop_default_trigger', 'time' );
		foreach ( $this->trigger_labels() as $key => $label ) {
			printf(
'<label style="display:block;margin-bottom:6px"><input type="radio" name="wp_pop_default_trigger" value="%s"%s> %s</label>',
esc_attr( $key ),
checked( $value, $key, false ),
esc_html( $label )
);
		}
	}

	public function default_scroll_threshold_field_callback() {
		$value = absint( get_option( 'wp_pop_default_scroll_threshold', 50 ) );
		printf(
'<div id="wp-pop-default-scroll-threshold-wrap"%s><input type="number" name="wp_pop_default_scroll_threshold" value="%d" min="0" max="100" step="5" class="small-text"> %s<p class="description">%s</p></div>',
'time' === get_option( 'wp_pop_default_trigger', 'time' ) ? ' style="display:none"' : '',
$value,
esc_html__( '%', 'wp-pop' ),
esc_html__( 'Show the popup after the visitor has scrolled this percentage of the page.', 'wp-pop' )
);
	}

	}

	public function render_frequency_meta_box( $post ) {
		if ( ! is_array( $specific_pages ) ) {
			$specific_pages = array();
		}
		?>
		<div class="wp-pop-meta-box">
			<p>
				<label>
					<input type="radio" name="wp_pop_display_scope" value="sitewide"<?php checked( $scope, 'sitewide' ); ?>>
					<?php esc_html_e( 'Sitewide', 'wp-pop' ); ?>
				</label>
			</p>
			<p>
				<label>
					<input type="radio" name="wp_pop_display_scope" value="specific"<?php checked( $scope, 'specific' ); ?>>
					<?php esc_html_e( 'Specific pages / posts', 'wp-pop' ); ?>
				</label>
			</p>
			<div id="wp-pop-specific-pages"<?php echo 'specific' !== $scope ? ' style="display:none"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Check each page or post where this popup should appear.', 'wp-pop' ); ?></p>
				<?php
				$pages = get_posts(
array(
'post_type'      => array( 'page', 'post' ),
						'post_status'    => 'publish',
						'posts_per_page' => 200,
						'orderby'        => 'title',
						'order'          => 'ASC',
					)
				);

				foreach ( $pages as $page ) {
					printf(
'<label><input type="checkbox" name="wp_pop_specific_pages[]" value="%d"%s> %s</label><br>',
absint( $page->ID ),
						in_array( $page->ID, $specific_pages, false ) ? ' checked' : '', // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						esc_html( get_the_title( $page ) )
					);
				}
				?>
			</div>
		</div>
		<?php
	}

	public function render_frequency_meta_box( $post ) {
		$frequency = get_post_meta( $post->ID, '_wp_pop_frequency', true );
		if ( ! $frequency ) {
			$frequency = get_option( 'wp_pop_default_frequency', 'session' );
		}

		$delay = get_post_meta( $post->ID, '_wp_pop_delay', true );
		if ( '' === $delay ) {
			$delay = absint( get_option( 'wp_pop_default_delay', 0 ) );
		}

		$trigger = get_post_meta( $post->ID, '_wp_pop_trigger', true );
		if ( ! $trigger ) {
			$trigger = get_option( 'wp_pop_default_trigger', 'time' );
		}

		$scroll_threshold = get_post_meta( $post->ID, '_wp_pop_scroll_threshold', true );
		if ( '' === $scroll_threshold ) {
			$scroll_threshold = absint( get_option( 'wp_pop_default_scroll_threshold', 50 ) );
		}
		?>
		<div class="wp-pop-meta-box">
			<p><strong><?php esc_html_e( 'Show this popup:', 'wp-pop' ); ?></strong></p>
			<?php foreach ( $this->frequency_labels() as $key => $label ) : ?>
				<p>
					<label>
						<input type="radio" name="wp_pop_frequency" value="<?php echo esc_attr( $key ); ?>"<?php checked( $frequency, $key ); ?>>
						<?php echo esc_html( $label ); ?>
					</label>
				</p>
			<?php endforeach; ?>
			<hr>
			<p><strong><?php esc_html_e( 'Trigger:', 'wp-pop' ); ?></strong></p>
			<?php foreach ( $this->trigger_labels() as $key => $label ) : ?>
				<p>
					<label>
						<input type="radio" name="wp_pop_trigger" value="<?php echo esc_attr( $key ); ?>"<?php checked( $trigger, $key ); ?>>
						<?php echo esc_html( $label ); ?>
					</label>
				</p>
			<?php endforeach; ?>
			<div id="wp-pop-scroll-threshold-wrap"<?php echo 'scroll' !== $trigger ? ' style="display:none"' : ''; ?>>
				<p>
					<label for="wp_pop_scroll_threshold">
						<strong><?php esc_html_e( 'Scroll amount:', 'wp-pop' ); ?></strong>
						<input type="number" id="wp_pop_scroll_threshold" name="wp_pop_scroll_threshold" value="<?php echo esc_attr( absint( $scroll_threshold ) ); ?>" min="0" max="100" step="5" class="small-text">
						<?php esc_html_e( '%', 'wp-pop' ); ?>
					</label>
				</p>
				<p class="description"><?php esc_html_e( 'Show after visitor scrolls this percentage of the page.', 'wp-pop' ); ?></p>
			</div>
			<hr>
			<p>
				<label for="wp_pop_delay">
					<strong><?php esc_html_e( 'Additional delay:', 'wp-pop' ); ?></strong>
					<input type="number" id="wp_pop_delay" name="wp_pop_delay" value="<?php echo esc_attr( absint( $delay ) ); ?>" min="0" max="60" step="1" class="small-text">
					<?php esc_html_e( 'seconds', 'wp-pop' ); ?>
				</label>
			</p>
			<p class="description"><?php esc_html_e( 'Extra wait after the trigger condition is met. 0 = show immediately.', 'wp-pop' ); ?></p>
		</div>
		<?php
	}

	public function render_appearance_meta_box( $post ) {
		$overlay_color   = get_post_meta( $post->ID, '_wp_pop_overlay_color', true );
		$overlay_opacity = get_post_meta( $post->ID, '_wp_pop_overlay_opacity', true );
		$border_radius   = get_post_meta( $post->ID, '_wp_pop_border_radius', true );
		$padding         = get_post_meta( $post->ID, '_wp_pop_padding', true );
		$close_color     = get_post_meta( $post->ID, '_wp_pop_close_color', true );
		$hide_title      = get_post_meta( $post->ID, '_wp_pop_hide_title', true );
		$columns_gap     = get_post_meta( $post->ID, '_wp_pop_columns_gap', true );
		$width_preset    = get_post_meta( $post->ID, '_wp_pop_width_preset', true );
		$custom_width    = get_post_meta( $post->ID, '_wp_pop_custom_width', true );
		$blur_amount     = get_post_meta( $post->ID, '_wp_pop_blur_amount', true );

		$default_color             = get_option( 'wp_pop_default_overlay_color', '#000000' );
		$default_opacity           = get_option( 'wp_pop_default_overlay_opacity', 0.65 );
		$default_radius            = absint( get_option( 'wp_pop_default_border_radius', 8 ) );
		$default_padding           = get_option( 'wp_pop_default_padding', 2 );
		$default_close_color       = get_option( 'wp_pop_default_close_color', '#000000' );
		$default_hide_title        = absint( get_option( 'wp_pop_default_hide_title', 0 ) );
		$default_columns_gap       = get_option( 'wp_pop_default_columns_gap', 2 );
		$default_width_preset      = get_option( 'wp_pop_default_width_preset', 'medium' );
		$default_custom_width      = get_option( 'wp_pop_default_custom_width', 40 );
		$default_blur_amount       = absint( get_option( 'wp_pop_default_blur_amount', 0 ) );

		$presets        = $this->width_presets();
		$default_width_label = isset( $presets[ $default_width_preset ] )
			? $presets[ $default_width_preset ]['label']
			: $default_width_preset;

		// Resolve effective values (meta overrides default).
		$effective_close_color  = $close_color ? $close_color : $default_close_color;
		$effective_hide_title   = '' !== $hide_title ? absint( $hide_title ) : $default_hide_title;
		$effective_custom_width = '' !== $custom_width ? $custom_width : $default_custom_width;
		?>
		<div class="wp-pop-meta-box">
			<p>
				<label for="wp_pop_width_preset"><strong><?php esc_html_e( 'Width', 'wp-pop' ); ?></strong></label><br>
				<?php $this->render_width_select( 'wp_pop_width_preset', 'wp_pop_custom_width', 'wp-pop-custom-width-wrap', $width_preset, $effective_custom_width, true ); ?>
				<span class="description"><?php printf( /* translators: %s: default size label */ esc_html__( 'Site default: %s', 'wp-pop' ), esc_html( $default_width_label ) ); ?></span>
			</p>
			<hr>
			<p>
				<label for="wp_pop_overlay_color"><strong><?php esc_html_e( 'Overlay Color', 'wp-pop' ); ?></strong></label><br>
				<input type="color" id="wp_pop_overlay_color" name="wp_pop_overlay_color"
					value="<?php echo esc_attr( $overlay_color ? $overlay_color : $default_color ); ?>">
			</p>
			<p>
				<label for="wp_pop_overlay_opacity"><strong><?php esc_html_e( 'Overlay Opacity', 'wp-pop' ); ?></strong></label><br>
				<input type="number" id="wp_pop_overlay_opacity" name="wp_pop_overlay_opacity"
					value="<?php echo esc_attr( '' !== $overlay_opacity ? $overlay_opacity : $default_opacity ); ?>"
					min="0" max="1" step="0.05" class="small-text">
				<span class="description"><?php printf( /* translators: %s: default value */ esc_html__( 'Site default: %s', 'wp-pop' ), esc_html( $default_opacity ) ); ?></span>
			</p>
			<p>
				<label for="wp_pop_border_radius"><strong><?php esc_html_e( 'Corner Radius', 'wp-pop' ); ?></strong></label><br>
				<input type="number" id="wp_pop_border_radius" name="wp_pop_border_radius"
					value="<?php echo esc_attr( '' !== $border_radius ? $border_radius : $default_radius ); ?>"
					min="0" max="100" step="1" class="small-text"> <?php esc_html_e( 'px', 'wp-pop' ); ?>
				<span class="description"><?php printf( /* translators: %dpx: default value */ esc_html__( 'Site default: %dpx', 'wp-pop' ), $default_radius ); ?></span>
			</p>
			<p>
				<label for="wp_pop_padding"><strong><?php esc_html_e( 'Dialog Padding', 'wp-pop' ); ?></strong></label><br>
				<input type="number" id="wp_pop_padding" name="wp_pop_padding"
					value="<?php echo esc_attr( '' !== $padding ? $padding : $default_padding ); ?>"
					min="0" max="10" step="0.5" class="small-text"> <?php esc_html_e( 'rem', 'wp-pop' ); ?>
				<span class="description"><?php printf( /* translators: %s: default value */ esc_html__( 'Site default: %srem', 'wp-pop' ), esc_html( $default_padding ) ); ?></span>
			</p>
			<p>
				<label for="wp_pop_close_color"><strong><?php esc_html_e( 'Close Icon Color', 'wp-pop' ); ?></strong></label><br>
				<input type="color" id="wp_pop_close_color" name="wp_pop_close_color"
					value="<?php echo esc_attr( $effective_close_color ); ?>">
				<span class="description"><?php printf( /* translators: %s: default value */ esc_html__( 'Site default: %s', 'wp-pop' ), esc_html( $default_close_color ) ); ?></span>
			</p>
			<p>
				<label>
					<input type="checkbox" name="wp_pop_hide_title" value="1"<?php checked( 1, $effective_hide_title ); ?>>
					<strong><?php esc_html_e( 'Hide Title', 'wp-pop' ); ?></strong>
				</label>
				<span class="description"><?php echo $default_hide_title ? esc_html__( 'Site default: hidden', 'wp-pop' ) : esc_html__( 'Site default: shown', 'wp-pop' ); ?></span>
			</p>
			<p>
				<label for="wp_pop_columns_gap"><strong><?php esc_html_e( 'Column Gap', 'wp-pop' ); ?></strong></label><br>
				<input type="number" id="wp_pop_columns_gap" name="wp_pop_columns_gap"
					value="<?php echo esc_attr( '' !== $columns_gap ? $columns_gap : $default_columns_gap ); ?>"
					min="0" max="10" step="0.5" class="small-text"> <?php esc_html_e( 'rem', 'wp-pop' ); ?>
				<span class="description"><?php printf( /* translators: %s: default value */ esc_html__( 'Site default: %srem', 'wp-pop' ), esc_html( $default_columns_gap ) ); ?></span>
			</p>
			<p>
				<label for="wp_pop_blur_amount"><strong><?php esc_html_e( 'Background Blur', 'wp-pop' ); ?></strong></label><br>
				<input type="number" id="wp_pop_blur_amount" name="wp_pop_blur_amount"
					value="<?php echo esc_attr( '' !== $blur_amount ? $blur_amount : $default_blur_amount ); ?>"
					min="0" max="50" step="1" class="small-text"> <?php esc_html_e( 'px', 'wp-pop' ); ?>
				<span class="description"><?php printf( /* translators: %dpx: default value */ esc_html__( 'Site default: %dpx. 0 = no blur.', 'wp-pop' ), $default_blur_amount ); ?></span>
			</p>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Save meta boxes
	// -------------------------------------------------------------------------

	public function save_meta_boxes( $post_id, $post ) {
		if ( ! isset( $_POST['wp_pop_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_pop_meta_nonce'] ) ), 'wp_pop_save_meta_boxes' ) ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Display scope.
		$scope = 'sitewide';
		if ( isset( $_POST['wp_pop_display_scope'] ) ) {
			$raw   = sanitize_text_field( wp_unslash( $_POST['wp_pop_display_scope'] ) );
			$scope = in_array( $raw, array( 'sitewide', 'specific' ), true ) ? $raw : 'sitewide';
		}
		update_post_meta( $post_id, '_wp_pop_display_scope', $scope );

		// Specific pages.
		if ( 'specific' === $scope ) {
			$specific_pages = array();
			if ( isset( $_POST['wp_pop_specific_pages'] ) && is_array( $_POST['wp_pop_specific_pages'] ) ) {
				$specific_pages = array_map( 'absint', $_POST['wp_pop_specific_pages'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
			update_post_meta( $post_id, '_wp_pop_specific_pages', $specific_pages );
		} else {
			delete_post_meta( $post_id, '_wp_pop_specific_pages' );
		}

		// Frequency.
		if ( isset( $_POST['wp_pop_frequency'] ) ) {
			$freq = sanitize_text_field( wp_unslash( $_POST['wp_pop_frequency'] ) );
			update_post_meta( $post_id, '_wp_pop_frequency', $this->sanitize_frequency( $freq ) );
		}

		// Trigger.
		if ( isset( $_POST['wp_pop_trigger'] ) ) {
			$trigger = sanitize_text_field( wp_unslash( $_POST['wp_pop_trigger'] ) );
			update_post_meta( $post_id, '_wp_pop_trigger', $this->sanitize_trigger( $trigger ) );
		}

		// Scroll threshold.
		if ( isset( $_POST['wp_pop_scroll_threshold'] ) ) {
			$threshold = $this->sanitize_scroll_threshold( wp_unslash( $_POST['wp_pop_scroll_threshold'] ) );
			update_post_meta( $post_id, '_wp_pop_scroll_threshold', $threshold );
		}

		// Delay.
		if ( isset( $_POST['wp_pop_delay'] ) ) {
			$delay = min( absint( wp_unslash( $_POST['wp_pop_delay'] ) ), 60 );
			update_post_meta( $post_id, '_wp_pop_delay', $delay );
		}

		// Overlay color.
		if ( isset( $_POST['wp_pop_overlay_color'] ) ) {
			$color = sanitize_hex_color( wp_unslash( $_POST['wp_pop_overlay_color'] ) );
			if ( $color ) {
				update_post_meta( $post_id, '_wp_pop_overlay_color', $color );
			}
		}

		// Overlay opacity.
		if ( isset( $_POST['wp_pop_overlay_opacity'] ) ) {
			$opacity = $this->sanitize_opacity( wp_unslash( $_POST['wp_pop_overlay_opacity'] ) );
			update_post_meta( $post_id, '_wp_pop_overlay_opacity', $opacity );
		}

		// Corner radius.
		if ( isset( $_POST['wp_pop_border_radius'] ) ) {
			$radius = $this->sanitize_border_radius( wp_unslash( $_POST['wp_pop_border_radius'] ) );
			update_post_meta( $post_id, '_wp_pop_border_radius', $radius );
		}

		// Dialog padding.
		if ( isset( $_POST['wp_pop_padding'] ) ) {
			$padding = $this->sanitize_padding( wp_unslash( $_POST['wp_pop_padding'] ) );
			update_post_meta( $post_id, '_wp_pop_padding', $padding );
		}

		// Close icon color.
		if ( isset( $_POST['wp_pop_close_color'] ) ) {
			$color = sanitize_hex_color( wp_unslash( $_POST['wp_pop_close_color'] ) );
			if ( $color ) {
				update_post_meta( $post_id, '_wp_pop_close_color', $color );
			}
		}

		// Hide title.
		$hide_title = isset( $_POST['wp_pop_hide_title'] ) ? 1 : 0;
		update_post_meta( $post_id, '_wp_pop_hide_title', $hide_title );

		// Column gap.
		if ( isset( $_POST['wp_pop_columns_gap'] ) ) {
			$gap = $this->sanitize_columns_gap( wp_unslash( $_POST['wp_pop_columns_gap'] ) );
			update_post_meta( $post_id, '_wp_pop_columns_gap', $gap );
		}

		// Background blur.
		if ( isset( $_POST['wp_pop_blur_amount'] ) ) {
			$blur = $this->sanitize_blur_amount( wp_unslash( $_POST['wp_pop_blur_amount'] ) );
			update_post_meta( $post_id, '_wp_pop_blur_amount', $blur );
		}

		// Width preset (empty string = use site default).
		$width_preset_raw = isset( $_POST['wp_pop_width_preset'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_pop_width_preset'] ) ) : '';
		$allowed_presets  = array_merge( array( '' ), array_keys( $this->width_presets() ), array( 'custom' ) );
		$width_preset_val = in_array( $width_preset_raw, $allowed_presets, true ) ? $width_preset_raw : '';
		update_post_meta( $post_id, '_wp_pop_width_preset', $width_preset_val );
		if ( 'custom' === $width_preset_val && isset( $_POST['wp_pop_custom_width'] ) ) {
			$custom_w = $this->sanitize_custom_width( wp_unslash( $_POST['wp_pop_custom_width'] ) );
			update_post_meta( $post_id, '_wp_pop_custom_width', $custom_w );
		}

		// Schedule dates.
		$scheduling_enabled = isset( $_POST['wp_pop_scheduling_enabled'] ) ? 1 : 0;
		update_post_meta( $post_id, '_wp_pop_scheduling_enabled', $scheduling_enabled );

		if ( $scheduling_enabled ) {
			$start = isset( $_POST['wp_pop_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_pop_start_date'] ) ) : '';
			$start = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) ? $start : '';
			update_post_meta( $post_id, '_wp_pop_start_date', $start );

			$end = isset( $_POST['wp_pop_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_pop_end_date'] ) ) : '';
			$end = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end ) ? $end : '';
			update_post_meta( $post_id, '_wp_pop_end_date', $end );
		} else {
			// Scheduling disabled — clear any saved dates so the frontend ignores them.
			update_post_meta( $post_id, '_wp_pop_start_date', '' );
			update_post_meta( $post_id, '_wp_pop_end_date', '' );
		}
	}

	// -------------------------------------------------------------------------
	// Archive / Unarchive
	// -------------------------------------------------------------------------

	public function add_popup_row_actions( $actions, $post ) {
		if ( 'wp_pop' !== $post->post_type ) {
			return $actions;
		}
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		if ( 'wp_pop_archived' === $post->post_status ) {
			$url = wp_nonce_url(
				admin_url( 'admin.php?action=wp_pop_unarchive&post=' . absint( $post->ID ) ),
				'wp_pop_unarchive_' . $post->ID
			);
			$actions['unarchive'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Unarchive', 'wp-pop' ) . '</a>';
		} elseif ( in_array( $post->post_status, array( 'publish', 'draft' ), true ) ) {
			$url = wp_nonce_url(
				admin_url( 'admin.php?action=wp_pop_archive&post=' . absint( $post->ID ) ),
				'wp_pop_archive_' . $post->ID
			);
			$actions['archive'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Archive', 'wp-pop' ) . '</a>';
		}

		if ( current_user_can( 'publish_posts' ) ) {
			$url = wp_nonce_url(
				admin_url( 'admin.php?action=wp_pop_duplicate&post=' . absint( $post->ID ) ),
				'wp_pop_duplicate_' . $post->ID
			);
			$actions['duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'wp-pop' ) . '</a>';
		}

		return $actions;
	}

	public function handle_archive_action() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! $post_id ) {
			wp_die( esc_html__( 'Invalid popup ID.', 'wp-pop' ) );
		}

		check_admin_referer( 'wp_pop_archive_' . $post_id );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to archive this popup.', 'wp-pop' ) );
		}

		if ( 'wp_pop' !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'Invalid popup.', 'wp-pop' ) );
		}

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'wp_pop_archived',
			)
		);

		wp_safe_redirect( admin_url( 'edit.php?post_type=wp_pop' ) );
		exit;
	}

	public function handle_unarchive_action() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! $post_id ) {
			wp_die( esc_html__( 'Invalid popup ID.', 'wp-pop' ) );
		}

		check_admin_referer( 'wp_pop_unarchive_' . $post_id );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to unarchive this popup.', 'wp-pop' ) );
		}

		if ( 'wp_pop' !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'Invalid popup.', 'wp-pop' ) );
		}

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);

		wp_safe_redirect( admin_url( 'edit.php?post_type=wp_pop&post_status=wp_pop_archived' ) );
		exit;
	}

	public function add_archived_view( $views ) {
		$count          = wp_count_posts( 'wp_pop' );
		$archived_count = isset( $count->wp_pop_archived ) ? (int) $count->wp_pop_archived : 0;

		if ( $archived_count > 0 ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$class                    = ( isset( $_GET['post_status'] ) && 'wp_pop_archived' === $_GET['post_status'] ) ? 'current' : '';
			$url                      = admin_url( 'edit.php?post_type=wp_pop&post_status=wp_pop_archived' );
			$views['wp_pop_archived'] = sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
				esc_url( $url ),
				esc_attr( $class ),
				esc_html__( 'Archived', 'wp-pop' ),
				esc_html( number_format_i18n( $archived_count ) )
			);
		}

		return $views;
	}

	public function handle_duplicate_action() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		if ( ! $post_id ) {
			wp_die( esc_html__( 'Invalid popup ID.', 'wp-pop' ) );
		}
		check_admin_referer( 'wp_pop_duplicate_' . $post_id );
		if ( ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'publish_posts' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-pop' ) );
		}
		$original = get_post( $post_id );
		if ( ! $original || 'wp_pop' !== $original->post_type ) {
			wp_die( esc_html__( 'Invalid popup.', 'wp-pop' ) );
		}
		$new_id = wp_insert_post(
			array(
				'post_title'   => $original->post_title . ' ' . __( '(Copy)', 'wp-pop' ),
				'post_content' => $original->post_content,
				'post_status'  => 'draft',
				'post_type'    => 'wp_pop',
				'post_author'  => get_current_user_id(),
			)
		);
		if ( is_wp_error( $new_id ) ) {
			wp_die( esc_html( $new_id->get_error_message() ) );
		}
		$meta_keys = get_post_meta( $post_id );
		foreach ( $meta_keys as $key => $values ) {
			if ( in_array( $key, array( '_edit_lock', '_edit_last' ), true ) ) {
				continue;
			}
			foreach ( $values as $value ) {
				add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
			}
		}
		update_post_meta( $new_id, '_wp_pop_view_count', 0 );
		wp_safe_redirect( admin_url( 'post.php?post=' . $new_id . '&action=edit' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Import / Export
	// -------------------------------------------------------------------------

	public function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-pop' ) );
		}
		check_admin_referer( 'wp_pop_export' );
		$popup_ids = isset( $_GET['popup_ids'] ) ? array_map( 'absint', (array) $_GET['popup_ids'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$data      = array();
		foreach ( $popup_ids as $id ) {
			if ( 'wp_pop' !== get_post_type( $id ) ) {
				continue;
			}
			$post  = get_post( $id );
			$meta  = get_post_meta( $id );
			$clean = array();
			foreach ( $meta as $key => $values ) {
				if ( 0 === strpos( $key, '_wp_pop_' ) ) {
					$clean[ $key ] = maybe_unserialize( $values[0] );
				}
			}
			$data[] = array( 'title' => $post->post_title, 'content' => $post->post_content, 'meta' => $clean );
		}
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="wp-pop-export-' . gmdate( 'Ymd' ) . '.json"' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT ); // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'permission_denied', 403 );
		}
		check_ajax_referer( 'wp_pop_import', 'nonce' );
		if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
			wp_send_json_error( 'no_file', 400 );
		}
		$file    = sanitize_text_field( wp_unslash( $_FILES['import_file']['tmp_name'] ) );
		$content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $content ) {
			wp_send_json_error( 'empty_file', 400 );
		}
		$popups = json_decode( $content, true );
		if ( ! is_array( $popups ) ) {
			wp_send_json_error( 'invalid_json', 400 );
		}
		$created = 0;
		foreach ( $popups as $popup ) {
			$new_id = wp_insert_post(
				array(
					'post_title'   => sanitize_text_field( $popup['title'] ?? '' ),
					'post_content' => wp_kses_post( $popup['content'] ?? '' ),
					'post_status'  => 'draft',
					'post_type'    => 'wp_pop',
					'post_author'  => get_current_user_id(),
				)
			);
			if ( is_wp_error( $new_id ) ) {
				continue;
			}
			foreach ( (array) ( $popup['meta'] ?? array() ) as $key => $value ) {
				if ( 0 === strpos( $key, '_wp_pop_' ) ) {
					update_post_meta( $new_id, sanitize_key( $key ), $value );
				}
			}
			$created++;
		}
		wp_send_json_success( array( 'created' => $created ) );
	}

	// -------------------------------------------------------------------------
	// List table columns
	// -------------------------------------------------------------------------

	public function set_popup_columns( $columns ) {
		return array(
			'cb'        => $columns['cb'],
			'title'     => $columns['title'],
			'type'      => __( 'Type', 'wp-pop' ),
			'scope'     => __( 'Display', 'wp-pop' ),
			'frequency' => __( 'Frequency', 'wp-pop' ),
			'schedule'  => __( 'Schedule', 'wp-pop' ),
			'views'     => __( 'Views', 'wp-pop' ),
			'date'      => $columns['date'],
		);
	}

	public function set_popup_sortable_columns( $columns ) {
		$columns['views'] = 'views';
		return $columns;
	}

	public function sort_by_views( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'wp_pop' !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( 'views' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', '_wp_pop_view_count' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	public function render_popup_column( $column, $post_id ) {
		switch ( $column ) {
			case 'type':
				$type   = get_post_meta( $post_id, '_wp_pop_popup_type', true ) ?: 'modal';
				$labels = $this->popup_type_labels();
				echo esc_html( $labels[ $type ] ?? $type );
				break;

			case 'scope':
				$scope = get_post_meta( $post_id, '_wp_pop_display_scope', true );
				if ( 'specific' === $scope ) {
					$pages = get_post_meta( $post_id, '_wp_pop_specific_pages', true );
					$count = is_array( $pages ) ? count( $pages ) : 0;
					printf(
'%s <span class="description">(%d %s)</span>',
esc_html__( 'Specific', 'wp-pop' ),
$count,
esc_html( _n( 'page', 'pages', $count, 'wp-pop' ) )
);
				} else {
					esc_html_e( 'Sitewide', 'wp-pop' );
				}
				break;

			case 'frequency':
				$frequency = get_post_meta( $post_id, '_wp_pop_frequency', true );
				$labels    = $this->frequency_labels();
				$label     = isset( $labels[ $frequency ] ) ? $labels[ $frequency ] : __( 'Once per session', 'wp-pop' );
				echo esc_html( $label );
				break;

			case 'views':
				$count = absint( get_post_meta( $post_id, '_wp_pop_view_count', true ) );
				echo esc_html( number_format_i18n( $count ) );
				break;

			case 'schedule':
				$start = get_post_meta( $post_id, '_wp_pop_start_date', true );
				$end   = get_post_meta( $post_id, '_wp_pop_end_date', true );
				$today = current_time( 'Y-m-d' );

				if ( ! $start && ! $end ) {
					echo '<span class="wp-pop-badge wp-pop-badge--active">' . esc_html__( 'Always', 'wp-pop' ) . '</span>';
					break;
				}

				$date_format = get_option( 'date_format' );
				$start_label = $start ? esc_html( date_i18n( $date_format, strtotime( $start ) ) ) : esc_html__( 'Now', 'wp-pop' );
				$end_label   = $end ? esc_html( date_i18n( $date_format, strtotime( $end ) ) ) : esc_html__( '&infin;', 'wp-pop' );

				echo $start_label . ' &rarr; ' . $end_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				if ( $start && $today < $start ) {
					echo ' <span class="wp-pop-badge wp-pop-badge--scheduled">' . esc_html__( 'Scheduled', 'wp-pop' ) . '</span>';
				} elseif ( $end && $today > $end ) {
					echo ' <span class="wp-pop-badge wp-pop-badge--expired">' . esc_html__( 'Expired', 'wp-pop' ) . '</span>';
				} else {
					echo ' <span class="wp-pop-badge wp-pop-badge--active">' . esc_html__( 'Active', 'wp-pop' ) . '</span>';
				}
				break;
		}
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function frequency_labels() {
		return array(
'always'  => __( 'Always (every page load)', 'wp-pop' ),
			'session' => __( 'Once per session', 'wp-pop' ),
			'daily'   => __( 'Once per day', 'wp-pop' ),
			'weekly'  => __( 'Once per week', 'wp-pop' ),
			'once'    => __( 'Once ever', 'wp-pop' ),
		);
	}

	private function trigger_labels() {
		return array(
			'time'               => __( 'Time delay (on page load)', 'wp-pop' ),
			'scroll'             => __( 'Scroll depth', 'wp-pop' ),
			'interaction'        => __( 'First interaction (click, key, or touch)', 'wp-pop' ),
			'exit_intent'        => __( 'Exit intent (mouse leaves viewport)', 'wp-pop' ),
			'click'              => __( 'Element click (CSS selector)', 'wp-pop' ),
			'inactivity'         => __( 'User inactivity', 'wp-pop' ),
			'element_visibility' => __( 'Element becomes visible', 'wp-pop' ),
		);
	}

	private function popup_type_labels() {
		return array(
			'modal'       => __( 'Modal (center)', 'wp-pop' ),
			'top-bar'     => __( 'Top Bar', 'wp-pop' ),
			'bottom-bar'  => __( 'Bottom Bar', 'wp-pop' ),
			'slide-in-tl' => __( 'Slide-in TL', 'wp-pop' ),
			'slide-in-tr' => __( 'Slide-in TR', 'wp-pop' ),
			'slide-in-bl' => __( 'Slide-in BL', 'wp-pop' ),
			'slide-in-br' => __( 'Slide-in BR', 'wp-pop' ),
			'fullscreen'  => __( 'Fullscreen', 'wp-pop' ),
			'tooltip'     => __( 'Tooltip / Inline', 'wp-pop' ),
		);
	}

	private function animation_labels() {
		return array(
			'none'       => __( 'None', 'wp-pop' ),
			'fade'       => __( 'Fade', 'wp-pop' ),
			'slide-down' => __( 'Slide Down', 'wp-pop' ),
			'zoom'       => __( 'Zoom', 'wp-pop' ),
		);
	}
}
