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
			'wp_pop_ab_test',
			'wp-pop_page_wp-pop-settings',
			'wp-pop_page_wp-pop-analytics',
			'wp_pop_page_wp-pop-settings',
			'wp_pop_page_wp-pop-analytics',
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
			'wp_pop_ab_test',
			'wp-pop_page_wp-pop-settings',
			'wp-pop_page_wp-pop-analytics',
			'wp_pop_page_wp-pop-settings',
			'wp_pop_page_wp-pop-analytics',
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

	}

	public function display_analytics_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once WP_POP_PLUGIN_DIR . 'admin/partials/wp-pop-admin-analytics.php';
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

		// ---- General defaults -----------------------------------------------

		add_settings_section(
			'wp_pop_general_section',
			__( 'Popup Defaults', 'wp-pop' ),
			array( $this, 'general_section_callback' ),
			'wp-pop-settings'
		);

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

		// ---- Appearance defaults --------------------------------------------

		add_settings_section(
			'wp_pop_appearance_section',
			__( 'Appearance Defaults', 'wp-pop' ),
			array( $this, 'appearance_section_callback' ),
			'wp-pop-settings'
		);

		register_setting(
			'wp_pop_settings_group',
			'wp_pop_default_popup_type',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_popup_type' ),
				'default'           => 'modal',
			)
		);

		register_setting(
			'wp_pop_settings_group',
			'wp_pop_default_animation',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_animation' ),
				'default'           => 'fade',
			)
		);

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

		register_setting(
			'wp_pop_settings_group',
			'wp_pop_default_columns_gap',
			array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_columns_gap' ),
				'default'           => 2,
			)
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
			'wp_pop_default_popup_type',
			__( 'Popup Type', 'wp-pop' ),
			array( $this, 'default_popup_type_field_callback' ),
			'wp-pop-settings',
			'wp_pop_appearance_section'
		);

		add_settings_field(
			'wp_pop_default_animation',
			__( 'Animation', 'wp-pop' ),
			array( $this, 'default_animation_field_callback' ),
			'wp-pop-settings',
			'wp_pop_appearance_section'
		);

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

		add_settings_field(
			'wp_pop_default_columns_gap',
			__( 'Column Gap', 'wp-pop' ),
			array( $this, 'default_columns_gap_field_callback' ),
			'wp-pop-settings',
			'wp_pop_appearance_section'
		);

		add_settings_field(
			'wp_pop_default_blur_amount',
			__( 'Background Blur', 'wp-pop' ),
			array( $this, 'default_blur_amount_field_callback' ),
			'wp-pop-settings',
			'wp_pop_appearance_section'
		);

		// ---- Geo Targeting --------------------------------------------------

		add_settings_section(
			'wp_pop_geo_section',
			__( 'Geo Targeting', 'wp-pop' ),
			array( $this, 'geo_section_callback' ),
			'wp-pop-settings'
		);

		register_setting(
			'wp_pop_settings_group',
			'wp_pop_geo_provider',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_geo_provider' ),
				'default'           => 'ip_api',
			)
		);

		register_setting(
			'wp_pop_settings_group',
			'wp_pop_geo_api_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'wp_pop_settings_group',
			'wp_pop_maxmind_license_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'wp_pop_settings_group',
			'wp_pop_geo_fail_behavior',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_geo_fail_behavior' ),
				'default'           => 'show',
			)
		);

		register_setting(
			'wp_pop_settings_group',
			'wp_pop_geo_cache_hours',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_geo_cache_hours' ),
				'default'           => 24,
			)
		);

		add_settings_field(
			'wp_pop_geo_provider',
			__( 'Provider', 'wp-pop' ),
			array( $this, 'geo_provider_field_callback' ),
			'wp-pop-settings',
			'wp_pop_geo_section'
		);

		add_settings_field(
			'wp_pop_geo_api_key',
			__( 'ipgeolocation.io API Key', 'wp-pop' ),
			array( $this, 'geo_api_key_field_callback' ),
			'wp-pop-settings',
			'wp_pop_geo_section',
			array( 'class' => 'wp-pop-geo-row-ipgeolocation' )
		);

		add_settings_field(
			'wp_pop_maxmind_license_key',
			__( 'MaxMind License Key', 'wp-pop' ),
			array( $this, 'maxmind_license_key_field_callback' ),
			'wp-pop-settings',
			'wp_pop_geo_section',
			array( 'class' => 'wp-pop-geo-row-maxmind' )
		);

		add_settings_field(
			'wp_pop_geo_fail_behavior',
			__( 'When geo is unavailable', 'wp-pop' ),
			array( $this, 'geo_fail_behavior_field_callback' ),
			'wp-pop-settings',
			'wp_pop_geo_section'
		);

		add_settings_field(
			'wp_pop_geo_cache_hours',
			__( 'Cache duration', 'wp-pop' ),
			array( $this, 'geo_cache_hours_field_callback' ),
			'wp-pop-settings',
			'wp_pop_geo_section'
		);
	}

	// -------------------------------------------------------------------------
	// Sanitize callbacks
	// -------------------------------------------------------------------------

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

	public function sanitize_geo_provider( $value ) {
		$allowed = array( 'ip_api', 'ipgeolocation', 'maxmind' );
		return in_array( $value, $allowed, true ) ? $value : 'ip_api';
	}

	public function sanitize_geo_fail_behavior( $value ) {
		return 'hide' === $value ? 'hide' : 'show';
	}

	public function sanitize_geo_cache_hours( $value ) {
		return max( 1, min( 168, absint( $value ) ) );
	}

	// -------------------------------------------------------------------------
	// Width preset helpers
	// -------------------------------------------------------------------------

	private function width_presets() {
		return array(
			'small'  => array( 'label' => __( 'Small (~420px)', 'wp-pop' ),  'css' => '26rem' ),
			'medium' => array( 'label' => __( 'Medium (~640px)', 'wp-pop' ), 'css' => '40rem' ),
			'large'  => array( 'label' => __( 'Large (~900px)', 'wp-pop' ),  'css' => '56rem' ),
			'full'   => array( 'label' => __( 'Full Width', 'wp-pop' ),      'css' => 'none'  ),
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

	// -------------------------------------------------------------------------
	// Settings section + field callbacks
	// -------------------------------------------------------------------------

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
		echo '<p class="description">' . esc_html__( 'Gap between columns. 0 = no gap.', 'wp-pop' ) . '</p>';
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

	// -------------------------------------------------------------------------
	// Geo Targeting section + field callbacks
	// -------------------------------------------------------------------------

	public function geo_section_callback() {
		echo '<p>' . esc_html__( 'Show or hide popups based on the visitor\'s country, region, or city. Per-popup rules are configured in the block editor sidebar.', 'wp-pop' ) . '</p>';
		echo '<table class="wp-pop-geo-provider-info" style="border-collapse:collapse;margin-bottom:6px">';
		echo '<tr><td style="padding:2px 8px 2px 0"><strong>ip&#8209;api.com</strong></td><td>' . esc_html__( 'Free, no key needed. Non-commercial use only. Country + region + city.', 'wp-pop' ) . '</td></tr>';
		echo '<tr><td style="padding:2px 8px 2px 0"><strong>ipgeolocation.io</strong></td><td>';
		printf(
			/* translators: %s: link to sign up */
			esc_html__( '1,000 req/day free; unlimited on paid plans. Country + region + city. %s', 'wp-pop' ),
			'<a href="https://ipgeolocation.io/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Sign up →', 'wp-pop' ) . '</a>'
		);
		echo '</td></tr>';
		echo '<tr><td style="padding:2px 8px 2px 0"><strong>MaxMind GeoLite2</strong></td><td>' . esc_html__( 'Local database, no per-request fees. Requires a free MaxMind account + license key to download.', 'wp-pop' ) . '</td></tr>';
		echo '</table>';
		// Inline JS to show/hide provider-specific fields.
		?>
		<script>
		(function() {
			function wpPopGeoToggle() {
				var prov = document.querySelector('[name="wp_pop_geo_provider"]');
				if ( ! prov ) return;
				var val = prov.value;
				document.querySelectorAll('.wp-pop-geo-row-ipgeolocation').forEach(function(el) {
					el.style.display = 'ipgeolocation' === val ? '' : 'none';
				});
				document.querySelectorAll('.wp-pop-geo-row-maxmind').forEach(function(el) {
					el.style.display = 'maxmind' === val ? '' : 'none';
				});
			}
			document.addEventListener('DOMContentLoaded', function() {
				var prov = document.querySelector('[name="wp_pop_geo_provider"]');
				if ( prov ) { wpPopGeoToggle(); prov.addEventListener('change', wpPopGeoToggle); }
			});
		})();
		</script>
		<?php
	}

	public function geo_provider_field_callback() {
		$value    = get_option( 'wp_pop_geo_provider', 'ip_api' );
		$options  = array(
			'ip_api'       => __( 'ip-api.com (free, non-commercial)', 'wp-pop' ),
			'ipgeolocation' => __( 'ipgeolocation.io (API key required)', 'wp-pop' ),
			'maxmind'      => __( 'MaxMind GeoLite2 (local database)', 'wp-pop' ),
		);
		echo '<select name="wp_pop_geo_provider">';
		foreach ( $options as $key => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $value, $key, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	public function geo_api_key_field_callback() {
		$value = get_option( 'wp_pop_geo_api_key', '' );
		printf(
			'<input type="text" name="wp_pop_geo_api_key" value="%s" class="regular-text" autocomplete="off">',
			esc_attr( $value )
		);
		echo '<p class="description">';
		echo wp_kses(
			sprintf(
				/* translators: %s: sign-up link */
				__( 'Get a free key (1,000 req/day) at <a href="%s" target="_blank" rel="noopener noreferrer">ipgeolocation.io</a>.', 'wp-pop' ),
				'https://ipgeolocation.io/'
			),
			array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
		);
		echo '</p>';
	}

	public function maxmind_license_key_field_callback() {
		$license  = get_option( 'wp_pop_maxmind_license_key', '' );
		$db_path  = get_option( 'wp_pop_maxmind_db_path', '' );
		$updated  = get_option( 'wp_pop_maxmind_db_updated', '' );
		$nonce    = wp_create_nonce( 'wp_pop_download_maxmind_db' );
		$ajax_url = admin_url( 'admin-ajax.php' );
		?>
		<input type="text" name="wp_pop_maxmind_license_key" value="<?php echo esc_attr( $license ); ?>" class="regular-text" autocomplete="off">
		<p class="description">
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: MaxMind signup link */
					__( 'Create a free MaxMind account and generate a license key at <a href="%s" target="_blank" rel="noopener noreferrer">maxmind.com</a>.', 'wp-pop' ),
					'https://www.maxmind.com/en/geolite2/signup'
				),
				array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
			);
			?>
		</p>
		<p>
			<button type="button" id="wp-pop-download-maxmind" class="button">
				<?php esc_html_e( 'Save settings first, then Download / Update Database', 'wp-pop' ); ?>
			</button>
			<span id="wp-pop-maxmind-status" style="margin-left:8px"></span>
		</p>
		<?php if ( $db_path && file_exists( $db_path ) ) : ?>
		<p class="description">
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: file name, 2: date */
					__( 'Database: %1$s (downloaded %2$s)', 'wp-pop' ),
					basename( $db_path ),
					$updated ?: __( 'unknown', 'wp-pop' )
				)
			);
			?>
		</p>
		<?php endif; ?>
		<script>
		(function() {
			document.addEventListener('DOMContentLoaded', function() {
				var btn = document.getElementById('wp-pop-download-maxmind');
				if ( ! btn ) return;
				btn.addEventListener('click', function() {
					var status = document.getElementById('wp-pop-maxmind-status');
					btn.disabled = true;
					status.textContent = <?php echo wp_json_encode( __( 'Downloading…', 'wp-pop' ) ); ?>;
					var fd = new FormData();
					fd.append('action', 'wp_pop_download_maxmind_db');
					fd.append('nonce', <?php echo wp_json_encode( $nonce ); ?>);
					fetch(<?php echo wp_json_encode( $ajax_url ); ?>, { method: 'POST', body: fd, credentials: 'same-origin' })
						.then(function(r) { return r.json(); })
						.then(function(data) {
							btn.disabled = false;
							status.textContent = data.data && data.data.message ? data.data.message : (data.success ? <?php echo wp_json_encode( __( 'Done!', 'wp-pop' ) ); ?> : <?php echo wp_json_encode( __( 'Error.', 'wp-pop' ) ); ?>);
						})
						.catch(function() {
							btn.disabled = false;
							status.textContent = <?php echo wp_json_encode( __( 'Request failed.', 'wp-pop' ) ); ?>;
						});
				});
			});
		})();
		</script>
		<?php
	}

	public function geo_fail_behavior_field_callback() {
		$value = get_option( 'wp_pop_geo_fail_behavior', 'show' );
		foreach ( array(
			'show' => __( 'Show the popup (fail open — recommended)', 'wp-pop' ),
			'hide' => __( 'Hide the popup (fail closed — use for compliance)', 'wp-pop' ),
		) as $key => $label ) {
			printf(
				'<label style="display:block;margin-bottom:4px"><input type="radio" name="wp_pop_geo_fail_behavior" value="%s"%s> %s</label>',
				esc_attr( $key ),
				checked( $value, $key, false ),
				esc_html( $label )
			);
		}
		echo '<p class="description">' . esc_html__( 'What to do when geo data cannot be obtained (rate limit, API down, private IP, etc.).', 'wp-pop' ) . '</p>';
	}

	public function geo_cache_hours_field_callback() {
		$value = absint( get_option( 'wp_pop_geo_cache_hours', 24 ) );
		printf(
			'<input type="number" name="wp_pop_geo_cache_hours" value="%d" min="1" max="168" step="1" class="small-text"> %s',
			$value,
			esc_html__( 'hours', 'wp-pop' )
		);
		echo '<p class="description">' . esc_html__( 'How long to cache the geo result per visitor IP. 24 hours is recommended.', 'wp-pop' ) . '</p>';
	}

	// -------------------------------------------------------------------------
	// Archive / Unarchive / Duplicate row actions
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
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'wp_pop_archived' ) );
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
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
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

		// Validate file size (max 2 MB).
		if ( isset( $_FILES['import_file']['size'] ) && (int) $_FILES['import_file']['size'] > 2 * MB_IN_BYTES ) {
			wp_send_json_error( 'file_too_large', 400 );
		}

		// Validate file extension.
		$file_name = isset( $_FILES['import_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['import_file']['name'] ) ) : '';
		if ( 'json' !== strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) ) ) {
			wp_send_json_error( 'invalid_file_type', 400 );
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
				// Only import keys that belong to this plugin.
				if ( 0 === strpos( $key, '_wp_pop_' ) ) {
					// Sanitize based on expected value type.
					$sanitized = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : sanitize_textarea_field( (string) $value );
					update_post_meta( $new_id, sanitize_key( $key ), $sanitized );
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
	// Label helpers
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

	// -------------------------------------------------------------------------
	// A/B Test meta boxes
	// -------------------------------------------------------------------------

	/**
	 * Registers the meta boxes for the wp_pop_ab_test CPT.
	 */
	public function add_ab_test_meta_boxes() {
		add_meta_box(
			'wp_pop_ab_test_settings',
			__( 'A/B Test Setup', 'wp-pop' ),
			array( $this, 'render_ab_test_meta_box' ),
			'wp_pop_ab_test',
			'normal',
			'high'
		);

		add_meta_box(
			'wp_pop_ab_test_results',
			__( 'Results', 'wp-pop' ),
			array( $this, 'render_ab_results_meta_box' ),
			'wp_pop_ab_test',
			'normal',
			'default'
		);
	}

	/**
	 * Renders the A/B Test meta box content.
	 *
	 * @param WP_Post $post
	 */
	public function render_ab_test_meta_box( $post ) {
		wp_nonce_field( 'wp_pop_ab_test_meta', 'wp_pop_ab_test_nonce' );

		$popup_a          = (int) get_post_meta( $post->ID, '_wp_pop_ab_popup_a', true );
		$popup_b          = (int) get_post_meta( $post->ID, '_wp_pop_ab_popup_b', true );
		$weight_a         = (int) get_post_meta( $post->ID, '_wp_pop_ab_weight_a', true ) ?: 50;
		$scope            = get_post_meta( $post->ID, '_wp_pop_ab_scope', true ) ?: 'sitewide';
		$specific_pages   = (array) get_post_meta( $post->ID, '_wp_pop_ab_specific_pages', true );
		$url_patterns     = (array) get_post_meta( $post->ID, '_wp_pop_ab_url_patterns', true );
		$paused           = (bool) get_post_meta( $post->ID, '_wp_pop_ab_paused', true );
		$start_date       = get_post_meta( $post->ID, '_wp_pop_ab_start_date', true );
		$end_date         = get_post_meta( $post->ID, '_wp_pop_ab_end_date', true );
		$max_impressions  = (int) get_post_meta( $post->ID, '_wp_pop_ab_max_impressions', true );

		// Fetch all published popups for the variant selectors.
		$all_popups = get_posts(
			array(
				'post_type'      => 'wp_pop',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		// Check if the two selected popups have different individual display scopes.
		$scope_warning = false;
		if ( $popup_a && $popup_b ) {
			$scope_a = get_post_meta( $popup_a, '_wp_pop_display_scope', true ) ?: 'sitewide';
			$scope_b = get_post_meta( $popup_b, '_wp_pop_display_scope', true ) ?: 'sitewide';
			if ( $scope_a !== $scope_b ) {
				$scope_warning = true;
			}
		}

		// Published WP pages for the specific-pages selector.
		$all_pages = get_pages( array( 'post_status' => 'publish', 'sort_column' => 'post_title' ) );
		?>
		<style>
			.wp-pop-ab-section { margin-bottom: 20px; }
			.wp-pop-ab-section h4 { margin: 0 0 10px; padding: 6px 0; border-bottom: 1px solid #eee; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: #555; }
			.wp-pop-ab-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px; }
			.wp-pop-ab-grid label, .wp-pop-ab-label { display: block; font-weight: 600; margin-bottom: 4px; }
			.wp-pop-ab-grid select, .wp-pop-ab-full select, .wp-pop-ab-full input[type=text], .wp-pop-ab-full input[type=date], .wp-pop-ab-full input[type=number] { width: 100%; max-width: 420px; }
			.wp-pop-ab-scope-fields > div { margin-top: 8px; }
			.wp-pop-ab-schedule { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; max-width: 640px; }
			.wp-pop-ab-schedule label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 13px; }
			.wp-pop-ab-schedule input { width: 100%; }
			.wp-pop-ab-weight .weight-row { display: flex; align-items: center; gap: 12px; margin-top: 8px; max-width: 420px; }
			.wp-pop-ab-weight input[type=range] { flex: 1; accent-color: #2271b1; }
			.wp-pop-ab-pause { margin-top: 12px; display: flex; align-items: center; gap: 8px; }
			.wp-pop-ab-pause label { font-weight: 600; }
			.wp-pop-ab-note { background: #f0f6fc; border-left: 4px solid #3858e9; padding: 10px 14px; margin-top: 12px; font-size: 13px; }
			.wp-pop-ab-warn { background: #fff8e5; border-left: 4px solid #dba617; padding: 10px 14px; margin: 10px 0; font-size: 13px; }
			#wp-pop-ab-specific-pages { height: 120px; width: 100%; max-width: 420px; }
			.wp-pop-ab-scope-radios label { font-weight: normal; margin-right: 16px; }
		</style>

		<?php /* ── Section 1: Variant selection ────────────────────────────── */ ?>
		<div class="wp-pop-ab-section">
			<h4><?php esc_html_e( 'Variants', 'wp-pop' ); ?></h4>

			<div class="wp-pop-ab-grid">
				<div>
					<label for="wp_pop_ab_popup_a"><?php esc_html_e( 'Variant A (original popup)', 'wp-pop' ); ?></label>
					<select name="wp_pop_ab_popup_a" id="wp_pop_ab_popup_a">
						<option value=""><?php esc_html_e( '— Select a popup —', 'wp-pop' ); ?></option>
						<?php foreach ( $all_popups as $p ) : ?>
							<option value="<?php echo esc_attr( $p->ID ); ?>"<?php selected( $popup_a, $p->ID ); ?>>
								<?php echo esc_html( get_the_title( $p ) . ' (ID ' . $p->ID . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="wp_pop_ab_popup_b"><?php esc_html_e( 'Variant B (challenger popup)', 'wp-pop' ); ?></label>
					<select name="wp_pop_ab_popup_b" id="wp_pop_ab_popup_b">
						<option value=""><?php esc_html_e( '— Select a popup —', 'wp-pop' ); ?></option>
						<?php foreach ( $all_popups as $p ) : ?>
							<option value="<?php echo esc_attr( $p->ID ); ?>"<?php selected( $popup_b, $p->ID ); ?>>
								<?php echo esc_html( get_the_title( $p ) . ' (ID ' . $p->ID . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<?php if ( $scope_warning ) : ?>
				<div class="wp-pop-ab-warn">
					&#9888; <?php esc_html_e( 'Popup A and Popup B have different individual targeting settings. The test scope below will override both — visitors will see whichever variant they\'re assigned on any page that matches the test scope. On pages outside the test scope, Popup A may still appear independently.', 'wp-pop' ); ?>
				</div>
			<?php endif; ?>

			<div class="wp-pop-ab-weight">
				<span class="wp-pop-ab-label"><?php esc_html_e( 'Traffic split', 'wp-pop' ); ?></span>
				<div class="weight-row">
					<span><?php esc_html_e( 'A', 'wp-pop' ); ?></span>
					<input
						type="range"
						name="wp_pop_ab_weight_a"
						id="wp_pop_ab_weight_a"
						min="1" max="99"
						value="<?php echo esc_attr( $weight_a ); ?>"
						oninput="document.getElementById('wp_pop_ab_weight_a_val').textContent = this.value + '% / ' + (100 - this.value) + '%';"
					/>
					<span><?php esc_html_e( 'B', 'wp-pop' ); ?></span>
					<strong id="wp_pop_ab_weight_a_val"><?php echo esc_html( $weight_a . '% / ' . ( 100 - $weight_a ) . '%' ); ?></strong>
				</div>
			</div>

			<div class="wp-pop-ab-pause">
				<input type="checkbox" name="wp_pop_ab_paused" id="wp_pop_ab_paused" value="1"<?php checked( $paused ); ?> />
				<label for="wp_pop_ab_paused"><?php esc_html_e( 'Pause this test (stops serving variants; data is preserved)', 'wp-pop' ); ?></label>
			</div>
		</div>

		<?php /* ── Section 2: Test scope ──────────────────────────────────── */ ?>
		<div class="wp-pop-ab-section">
			<h4><?php esc_html_e( 'Test Scope', 'wp-pop' ); ?></h4>
			<p style="margin:0 0 8px;font-size:13px;color:#555;"><?php esc_html_e( 'Where should the test run? This overrides each popup\'s individual display settings on matching pages, ensuring a fair comparison.', 'wp-pop' ); ?></p>

			<div class="wp-pop-ab-scope-radios">
				<?php
				$scope_options = array(
					'sitewide'    => __( 'All pages', 'wp-pop' ),
					'home'        => __( 'Homepage only', 'wp-pop' ),
					'singular'    => __( 'Singular posts/pages', 'wp-pop' ),
					'archive'     => __( 'Archive pages', 'wp-pop' ),
					'specific'    => __( 'Specific pages', 'wp-pop' ),
					'url_pattern' => __( 'URL patterns', 'wp-pop' ),
				);
				foreach ( $scope_options as $val => $label ) :
				?>
					<label>
						<input
							type="radio"
							name="wp_pop_ab_scope"
							value="<?php echo esc_attr( $val ); ?>"
							<?php checked( $scope, $val ); ?>
							onchange="wpPopAbToggleScope();"
						/>
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>

			<div class="wp-pop-ab-scope-fields">
				<div id="wp-pop-ab-scope-specific" style="<?php echo 'specific' === $scope ? '' : 'display:none;'; ?>">
					<label for="wp-pop-ab-specific-pages" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Select pages', 'wp-pop' ); ?></label>
					<select name="wp_pop_ab_specific_pages[]" id="wp-pop-ab-specific-pages" multiple>
						<?php foreach ( $all_pages as $pg ) : ?>
							<option value="<?php echo esc_attr( $pg->ID ); ?>"<?php echo in_array( (string) $pg->ID, array_map( 'strval', $specific_pages ), true ) ? ' selected' : ''; ?>>
								<?php echo esc_html( $pg->post_title . ' (ID ' . $pg->ID . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p style="margin:4px 0 0;font-size:12px;color:#757575;"><?php esc_html_e( 'Hold Ctrl / Cmd to select multiple pages.', 'wp-pop' ); ?></p>
				</div>

				<div id="wp-pop-ab-scope-url" style="<?php echo 'url_pattern' === $scope ? '' : 'display:none;'; ?>">
					<label for="wp_pop_ab_url_patterns" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'URL patterns (one per line)', 'wp-pop' ); ?></label>
					<textarea name="wp_pop_ab_url_patterns" id="wp_pop_ab_url_patterns" rows="4" style="width:100%;max-width:420px;font-family:monospace;" placeholder="https://example.com/landing-*"><?php echo esc_textarea( implode( "\n", $url_patterns ) ); ?></textarea>
					<p style="margin:4px 0 0;font-size:12px;color:#757575;"><?php esc_html_e( 'Wildcards (*) supported. One URL pattern per line. Example: https://example.com/shop/*', 'wp-pop' ); ?></p>
				</div>
			</div>
		</div>

		<?php /* ── Section 3: Schedule & limits ──────────────────────────── */ ?>
		<div class="wp-pop-ab-section">
			<h4><?php esc_html_e( 'Schedule &amp; Limits', 'wp-pop' ); ?></h4>

			<div class="wp-pop-ab-schedule">
				<div>
					<label for="wp_pop_ab_start_date"><?php esc_html_e( 'Start date', 'wp-pop' ); ?></label>
					<input type="date" name="wp_pop_ab_start_date" id="wp_pop_ab_start_date" value="<?php echo esc_attr( $start_date ); ?>" />
					<p style="margin:3px 0 0;font-size:11px;color:#757575;"><?php esc_html_e( 'Leave blank to start immediately.', 'wp-pop' ); ?></p>
				</div>
				<div>
					<label for="wp_pop_ab_end_date"><?php esc_html_e( 'End date', 'wp-pop' ); ?></label>
					<input type="date" name="wp_pop_ab_end_date" id="wp_pop_ab_end_date" value="<?php echo esc_attr( $end_date ); ?>" />
					<p style="margin:3px 0 0;font-size:11px;color:#757575;"><?php esc_html_e( 'Leave blank to run indefinitely.', 'wp-pop' ); ?></p>
				</div>
				<div>
					<label for="wp_pop_ab_max_impressions"><?php esc_html_e( 'Max impressions', 'wp-pop' ); ?></label>
					<input type="number" name="wp_pop_ab_max_impressions" id="wp_pop_ab_max_impressions" value="<?php echo esc_attr( $max_impressions ); ?>" min="0" step="100" style="max-width:130px;" />
					<p style="margin:3px 0 0;font-size:11px;color:#757575;"><?php esc_html_e( 'Total views across both variants before the test pauses (0 = unlimited).', 'wp-pop' ); ?></p>
				</div>
			</div>
		</div>

		<p class="wp-pop-ab-note">
			<?php esc_html_e( 'Publish to activate. Each visitor is assigned a variant on first view and sees the same popup on every return visit. Results appear in the meta box below.', 'wp-pop' ); ?>
		</p>

		<script>
		function wpPopAbToggleScope() {
			var scope = document.querySelector('input[name="wp_pop_ab_scope"]:checked')?.value || 'sitewide';
			document.getElementById('wp-pop-ab-scope-specific').style.display = scope === 'specific' ? '' : 'none';
			document.getElementById('wp-pop-ab-scope-url').style.display = scope === 'url_pattern' ? '' : 'none';
		}
		</script>
		<?php
	}

	/**
	 * Saves the A/B test meta when the post is saved.
	 *
	 * @param int $post_id
	 */
	public function save_ab_test_meta( $post_id ) {
		if (
			! isset( $_POST['wp_pop_ab_test_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_pop_ab_test_nonce'] ) ), 'wp_pop_ab_test_meta' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Variants.
		$popup_a  = isset( $_POST['wp_pop_ab_popup_a'] )  ? absint( $_POST['wp_pop_ab_popup_a'] )  : 0;
		$popup_b  = isset( $_POST['wp_pop_ab_popup_b'] )  ? absint( $_POST['wp_pop_ab_popup_b'] )  : 0;
		$weight_a = isset( $_POST['wp_pop_ab_weight_a'] ) ? absint( $_POST['wp_pop_ab_weight_a'] ) : 50;
		$weight_a = max( 1, min( 99, $weight_a ) );

		update_post_meta( $post_id, '_wp_pop_ab_popup_a',  $popup_a );
		update_post_meta( $post_id, '_wp_pop_ab_popup_b',  $popup_b );
		update_post_meta( $post_id, '_wp_pop_ab_weight_a', $weight_a );

		// Test scope.
		$allowed_scopes = array( 'sitewide', 'home', 'singular', 'archive', 'specific', 'url_pattern' );
		$scope = isset( $_POST['wp_pop_ab_scope'] ) ? sanitize_key( wp_unslash( $_POST['wp_pop_ab_scope'] ) ) : 'sitewide';
		if ( ! in_array( $scope, $allowed_scopes, true ) ) {
			$scope = 'sitewide';
		}
		update_post_meta( $post_id, '_wp_pop_ab_scope', $scope );

		if ( 'specific' === $scope && isset( $_POST['wp_pop_ab_specific_pages'] ) ) {
			$pages = array_map( 'absint', (array) wp_unslash( $_POST['wp_pop_ab_specific_pages'] ) );
			$pages = array_filter( $pages );
			update_post_meta( $post_id, '_wp_pop_ab_specific_pages', $pages );
		} else {
			delete_post_meta( $post_id, '_wp_pop_ab_specific_pages' );
		}

		$url_patterns_raw = isset( $_POST['wp_pop_ab_url_patterns'] )
			? sanitize_textarea_field( wp_unslash( $_POST['wp_pop_ab_url_patterns'] ) )
			: '';
		$url_patterns = array_values(
			array_filter( array_map( 'sanitize_text_field', explode( "\n", $url_patterns_raw ) ) )
		);
		update_post_meta( $post_id, '_wp_pop_ab_url_patterns', $url_patterns );

		// Pause flag.
		$paused = isset( $_POST['wp_pop_ab_paused'] ) ? 1 : 0;
		update_post_meta( $post_id, '_wp_pop_ab_paused', $paused );

		// Schedule.
		$start_date = isset( $_POST['wp_pop_ab_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_pop_ab_start_date'] ) ) : '';
		$end_date   = isset( $_POST['wp_pop_ab_end_date'] )   ? sanitize_text_field( wp_unslash( $_POST['wp_pop_ab_end_date'] ) )   : '';

		// Validate date format yyyy-mm-dd.
		$date_regex = '/^\d{4}-\d{2}-\d{2}$/';
		if ( $start_date && ! preg_match( $date_regex, $start_date ) ) {
			$start_date = '';
		}
		if ( $end_date && ! preg_match( $date_regex, $end_date ) ) {
			$end_date = '';
		}

		update_post_meta( $post_id, '_wp_pop_ab_start_date', $start_date );
		update_post_meta( $post_id, '_wp_pop_ab_end_date',   $end_date );

		// Limits.
		$max_impressions = isset( $_POST['wp_pop_ab_max_impressions'] ) ? absint( $_POST['wp_pop_ab_max_impressions'] ) : 0;
		update_post_meta( $post_id, '_wp_pop_ab_max_impressions', $max_impressions );
	}

	// -------------------------------------------------------------------------
	// A/B Results meta box (inline on the test edit screen)
	// -------------------------------------------------------------------------

	/**
	 * Renders the Results meta box inline on the wp_pop_ab_test edit screen.
	 *
	 * @param WP_Post $post
	 */
	public function render_ab_results_meta_box( $post ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}

		$ab_testing = new Wp_Pop_Ab_Testing();
		$days       = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$days       = in_array( $days, array( 7, 14, 30, 60, 90 ), true ) ? $days : 30;

		$test_data  = $ab_testing->get_test_data( $post->ID );
		$comparison = $ab_testing->get_comparison( $post->ID, $days );
		$status     = $ab_testing->get_test_status( $test_data );

		$status_labels = array(
			'active'        => __( 'Active', 'wp-pop' ),
			'scheduled'     => __( 'Scheduled', 'wp-pop' ),
			'ended'         => __( 'Ended', 'wp-pop' ),
			'limit_reached' => __( 'Limit Reached', 'wp-pop' ),
			'paused'        => __( 'Paused', 'wp-pop' ),
		);
		$status_colors = array(
			'active'        => '#00a32a',
			'scheduled'     => '#3858e9',
			'ended'         => '#757575',
			'limit_reached' => '#dba617',
			'paused'        => '#646970',
		);
		$badge_color = $status_colors[ $status ] ?? '#555';
		$badge_label = $status_labels[ $status ] ?? $status;
		$edit_url    = get_edit_post_link( $post->ID, 'raw' );
		?>
		<style>
			.wp-pop-results-period { margin-bottom: 12px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
			.wp-pop-results-period a { padding: 3px 10px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 3px; text-decoration: none; font-size: 12px; color: #1d2327; }
			.wp-pop-results-period a.current { background: #2271b1; color: #fff; border-color: #2271b1; }
			.wp-pop-ab-compare-table { width: 100%; border-collapse: collapse; }
			.wp-pop-ab-compare-table th, .wp-pop-ab-compare-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
			.wp-pop-ab-compare-table th { background: #f9f9f9; font-weight: 600; }
			.wp-pop-ab-variant-badge { display: inline-block; background: #2271b1; color: #fff; border-radius: 3px; padding: 1px 7px; font-size: 11px; font-weight: 700; margin-right: 4px; }
			.wp-pop-ab-variant-badge.b { background: #d63638; }
			.wp-pop-ab-winner { color: #00a32a; font-weight: 700; }
			.wp-pop-ab-no-data { color: #757575; font-style: italic; }
			.wp-pop-ab-inline-badge { display: inline-block; border-radius: 3px; padding: 2px 8px; font-size: 11px; font-weight: 700; color: #fff; margin-bottom: 12px; }
		</style>

		<span class="wp-pop-ab-inline-badge" style="background:<?php echo esc_attr( $badge_color ); ?>;">
			<?php echo esc_html( $badge_label ); ?>
		</span>

		<div class="wp-pop-results-period">
			<span><?php esc_html_e( 'Period:', 'wp-pop' ); ?></span>
			<?php
			$period_labels = array( 7 => '7d', 14 => '14d', 30 => '30d', 60 => '60d', 90 => '90d' );
			foreach ( $period_labels as $d => $label ) :
			?>
				<a href="<?php echo esc_url( add_query_arg( 'days', $d, $edit_url ) ); ?>" class="<?php echo $days === $d ? 'current' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</div>

		<?php if ( 'paused' === $status ) : ?>
			<p class="wp-pop-ab-no-data"><?php esc_html_e( 'This test is paused. Uncheck the Pause option above and save to resume.', 'wp-pop' ); ?></p>
		<?php elseif ( 'scheduled' === $status ) : ?>
			<p class="wp-pop-ab-no-data">
				<?php
				printf(
					/* translators: %s = start date */
					esc_html__( 'Scheduled to start on %s.', 'wp-pop' ),
					'<strong>' . esc_html( $test_data['start_date'] ) . '</strong>'
				);
				?>
			</p>
		<?php elseif ( empty( $comparison ) ) : ?>
			<p class="wp-pop-ab-no-data"><?php esc_html_e( 'No data yet. Publish the test to start collecting results.', 'wp-pop' ); ?></p>
		<?php else : ?>
			<?php
			$ctrs    = array_column( $comparison, 'ctr' );
			$max_ctr = max( $ctrs );
			?>
			<table class="wp-pop-ab-compare-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Variant', 'wp-pop' ); ?></th>
						<th><?php esc_html_e( 'Views', 'wp-pop' ); ?></th>
						<th><?php esc_html_e( 'Clicks', 'wp-pop' ); ?></th>
						<th><?php esc_html_e( 'Dismissals', 'wp-pop' ); ?></th>
						<th><?php esc_html_e( 'CTR', 'wp-pop' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $comparison as $row ) : ?>
						<?php
						$is_winner = ( $row['views'] > 0 && (float) $row['ctr'] === $max_ctr );
						$badge_cls = 'a' === $row['variant'] ? '' : ' b';
						?>
						<tr>
							<td>
								<span class="wp-pop-ab-variant-badge<?php echo esc_attr( $badge_cls ); ?>">
									<?php echo esc_html( strtoupper( $row['variant'] ) ); ?>
								</span>
								<a href="<?php echo esc_url( (string) get_edit_post_link( $row['popup_id'] ) ); ?>">
									<?php echo esc_html( $row['label'] ); ?>
								</a>
							</td>
							<td><?php echo esc_html( number_format_i18n( $row['views'] ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $row['clicks'] ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $row['dismissals'] ) ); ?></td>
							<td class="<?php echo $is_winner ? 'wp-pop-ab-winner' : ''; ?>">
								<?php echo esc_html( $row['ctr'] . '%' ); ?>
								<?php if ( $is_winner ) : ?>&#9650;<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( 'ended' === $status ) : ?>
				<p style="margin:10px 0 0;font-size:12px;color:#757575;"><?php esc_html_e( 'This test has ended — results are final.', 'wp-pop' ); ?></p>
			<?php elseif ( 'limit_reached' === $status ) : ?>
				<p style="margin:10px 0 0;font-size:12px;color:#dba617;"><?php esc_html_e( 'Impression limit reached — the test has stopped serving variants.', 'wp-pop' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}

	// -------------------------------------------------------------------------
	// allowed_screens helper used by enqueue methods
	// -------------------------------------------------------------------------

	protected function is_allowed_screen() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}
		$allowed = array(
			'wp_pop',
			'wp_pop_ab_test',
			'wp-pop_page_wp-pop-settings',
			'wp-pop_page_wp-pop-analytics',
			'wp_pop_page_wp-pop-settings',
			'wp_pop_page_wp-pop-analytics',
		);
		return in_array( $screen->id, $allowed, true );
	}}
