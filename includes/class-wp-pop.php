<?php

require_once WP_POP_PLUGIN_DIR . 'includes/class-wp-pop-loader.php';
require_once WP_POP_PLUGIN_DIR . 'includes/class-wp-pop-i18n.php';
require_once WP_POP_PLUGIN_DIR . 'includes/class-wp-pop-post-type.php';
require_once WP_POP_PLUGIN_DIR . 'includes/class-wp-pop-meta.php';
require_once WP_POP_PLUGIN_DIR . 'includes/class-wp-pop-geo.php';
require_once WP_POP_PLUGIN_DIR . 'includes/class-wp-pop-targeting.php';
require_once WP_POP_PLUGIN_DIR . 'includes/class-wp-pop-analytics.php';
require_once WP_POP_PLUGIN_DIR . 'includes/class-wp-pop-ab-testing.php';
require_once WP_POP_PLUGIN_DIR . 'admin/class-wp-pop-admin-v2.php';
require_once WP_POP_PLUGIN_DIR . 'admin/class-wp-pop-block-editor.php';
require_once WP_POP_PLUGIN_DIR . 'public/class-wp-pop-public.php';

class Wp_Pop {

	protected $loader;

	protected $plugin_name;

	protected $version;

	public function __construct() {
		$this->plugin_name = 'wp-pop';
		$this->version     = defined( 'WP_POP_VERSION' ) ? WP_POP_VERSION : '0.2.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_post_type_hooks();
		$this->define_meta_hooks();
		$this->define_admin_hooks();
		$this->define_analytics_hooks();
		$this->define_public_hooks();

		if ( class_exists( 'WooCommerce' ) ) {
			$this->define_woocommerce_hooks();
		}
	}

	protected function load_dependencies() {
		$this->loader = new Wp_Pop_Loader();
	}

	protected function set_locale() {
		$plugin_i18n = new Wp_Pop_I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	protected function define_post_type_hooks() {
		$this->loader->add_action( 'init', 'Wp_Pop_Post_Type', 'register' );
		$this->loader->add_action( 'init', 'Wp_Pop_Post_Type', 'register_ab_test_cpt' );
		$this->loader->add_action( 'init', 'Wp_Pop_Post_Type', 'register_archived_status' );
	}

	protected function define_meta_hooks() {
		$this->loader->add_action( 'init', 'Wp_Pop_Meta', 'register' );
	}

	protected function define_admin_hooks() {
		$plugin_admin  = new Wp_Pop_Admin( $this->plugin_name, $this->version );
		$block_editor  = new Wp_Pop_Block_Editor();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'init_settings' );

		// Block-editor sidebar panels replace the classic meta boxes.
		$this->loader->add_action( 'enqueue_block_editor_assets', $block_editor, 'enqueue_editor_assets' );

		// List table columns + views.
		$this->loader->add_filter( 'manage_wp_pop_posts_columns',            $plugin_admin, 'set_popup_columns' );
		$this->loader->add_action( 'manage_wp_pop_posts_custom_column',      $plugin_admin, 'render_popup_column', 10, 2 );
		$this->loader->add_filter( 'manage_edit-wp_pop_sortable_columns',    $plugin_admin, 'set_popup_sortable_columns' );
		$this->loader->add_action( 'pre_get_posts',                          $plugin_admin, 'sort_by_views' );
		$this->loader->add_filter( 'post_row_actions',                       $plugin_admin, 'add_popup_row_actions', 10, 2 );
		$this->loader->add_action( 'admin_action_wp_pop_archive',            $plugin_admin, 'handle_archive_action' );
		$this->loader->add_action( 'admin_action_wp_pop_unarchive',          $plugin_admin, 'handle_unarchive_action' );
		$this->loader->add_action( 'admin_action_wp_pop_duplicate',          $plugin_admin, 'handle_duplicate_action' );
		$this->loader->add_filter( 'views_edit-wp_pop',                      $plugin_admin, 'add_archived_view' );

		// Import/export.
		$this->loader->add_action( 'admin_post_wp_pop_export',               $plugin_admin, 'handle_export' );
		$this->loader->add_action( 'wp_ajax_wp_pop_import',                  $plugin_admin, 'handle_import' );

		// MaxMind database download.
		$this->loader->add_action( 'wp_ajax_wp_pop_download_maxmind_db', 'Wp_Pop_Geo', 'handle_download_maxmind_db' );

		// A/B test meta boxes.
		$this->loader->add_action( 'add_meta_boxes',                         $plugin_admin, 'add_ab_test_meta_boxes' );
		$this->loader->add_action( 'save_post_wp_pop_ab_test',               $plugin_admin, 'save_ab_test_meta' );
	}

	protected function define_analytics_hooks() {
		$analytics = new Wp_Pop_Analytics();
		$analytics->define_hooks();
	}



	protected function define_woocommerce_hooks() {
		require_once WP_POP_PLUGIN_DIR . 'includes/class-wp-pop-woocommerce.php';
		$wc = new Wp_Pop_Woocommerce();
		$wc->define_hooks();
	}

	protected function define_public_hooks() {
		$plugin_public = new Wp_Pop_Public( $this->plugin_name, $this->version );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_footer',          $plugin_public, 'render_popups' );
		add_shortcode( 'wp_pop', array( $plugin_public, 'render_shortcode' ) );
	}

	public function run() {
		$this->loader->run();
	}
}
