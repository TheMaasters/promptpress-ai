<?php
/**
 * Admin interface for AI Page Builder.
 *
 * @package AIPB
 */

namespace AIPB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 *
 * Handles all admin-side functionality including menus, pages, and asset loading.
 */
class Admin {

	/**
	 * Plugin base path.
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * Plugin base URL.
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Plugin base name.
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Admin page hooks.
	 *
	 * @var array
	 */
	private $page_hooks = array();

	/**
	 * Instance.
	 *
	 * @var Admin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->plugin_path     = AIPB_PLUGIN_DIR;
		$this->plugin_url      = AIPB_PLUGIN_URL;
		$this->plugin_basename = AIPB_PLUGIN_BASENAME;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'init', array( $this, 'register_content_types' ) );
	}

	/**
	 * Register admin menu and submenus.
	 */
	public function register_menu() {
		$this->page_hooks['main'] = add_menu_page(
			__( 'PromptPress AI', 'promptpress-ai' ),
			__( 'PromptPress AI', 'promptpress-ai' ),
			'manage_options',
			'aipb-builder',
			array( $this, 'render_page_builder' ),
			'dashicons-admin-page',
			80
		);

		$this->page_hooks['builder'] = add_submenu_page(
			'aipb-builder',
			__( 'AI Builder', 'promptpress-ai' ),
			__( 'AI Builder', 'promptpress-ai' ),
			'manage_options',
			'aipb-builder',
			array( $this, 'render_page_builder' )
		);

		$this->page_hooks['generate_post'] = add_submenu_page(
			'aipb-builder',
			__( 'Generate Post', 'promptpress-ai' ),
			__( 'Generate Post', 'promptpress-ai' ),
			'manage_options',
			'aipb-generate-post',
			array( $this, 'render_generate_post' )
		);

		$this->page_hooks['generate_page'] = add_submenu_page(
			'aipb-builder',
			__( 'Generate Page', 'promptpress-ai' ),
			__( 'Generate Page', 'promptpress-ai' ),
			'manage_options',
			'aipb-generate-page',
			array( $this, 'render_generate_page' )
		);

		$this->page_hooks['history'] = add_submenu_page(
			'aipb-builder',
			__( 'History', 'promptpress-ai' ),
			__( 'History', 'promptpress-ai' ),
			'manage_options',
			'aipb-history',
			array( $this, 'render_history' )
		);

		$this->page_hooks['templates'] = add_submenu_page(
			'aipb-builder',
			__( 'Templates', 'promptpress-ai' ),
			__( 'Templates', 'promptpress-ai' ),
			'manage_options',
			'aipb-templates',
			array( $this, 'render_templates' )
		);

		$this->page_hooks['brand_settings'] = add_submenu_page(
			'aipb-builder',
			__( 'Brand Settings', 'promptpress-ai' ),
			__( 'Brand Settings', 'promptpress-ai' ),
			'manage_options',
			'aipb-brand-settings',
			array( $this, 'render_brand_settings' )
		);

		$this->page_hooks['ai_settings'] = add_submenu_page(
			'aipb-builder',
			__( 'AI Settings', 'promptpress-ai' ),
			__( 'AI Settings', 'promptpress-ai' ),
			'manage_options',
			'aipb-ai-settings',
			array( $this, 'render_ai_settings' )
		);

		$this->page_hooks['usage'] = add_submenu_page(
			'aipb-builder',
			__( 'Usage', 'promptpress-ai' ),
			__( 'Usage', 'promptpress-ai' ),
			'manage_options',
			'aipb-usage',
			array( $this, 'render_usage' )
		);
	}

	/**
	 * Enqueue admin scripts and styles on plugin pages only.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		$aipb_hooks = array_merge(
			$this->page_hooks,
			array(
				'toplevel_page_aipb-builder',
				'aipb_page_aipb-generate-post',
				'aipb_page_aipb-generate-page',
				'aipb_page_aipb-history',
				'aipb_page_aipb-templates',
				'aipb_page_aipb-brand-settings',
				'aipb_page_aipb-ai-settings',
				'aipb_page_aipb-usage',
			)
		);

		if ( ! in_array( $hook, $aipb_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'aipb-admin',
			$this->plugin_url . 'assets/css/admin.css',
			array(),
			AIPB_VERSION
		);

		wp_enqueue_script(
			'aipb-admin',
			$this->plugin_url . 'assets/js/admin.js',
			array( 'jquery', 'wp-api-fetch', 'media-upload', 'wp-color-picker' ),
			AIPB_VERSION,
			true
		);

		wp_localize_script( 'aipb-admin', 'aipbAdmin', array(
			'apiUrl'   => esc_url_raw( rest_url( 'promptpress-ai/v1' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'i18n'     => array(
				'confirmDelete'  => __( 'Are you sure you want to delete this item?', 'promptpress-ai' ),
				'generating'     => __( 'Generating content...', 'promptpress-ai' ),
				'success'        => __( 'Content generated successfully!', 'promptpress-ai' ),
				'error'          => __( 'An error occurred. Please try again.', 'promptpress-ai' ),
				'saving'         => __( 'Saving...', 'promptpress-ai' ),
				'saved'          => __( 'Settings saved.', 'promptpress-ai' ),
				'testing'        => __( 'Testing connection...', 'promptpress-ai' ),
				'connectionOk'   => __( 'Connection successful!', 'promptpress-ai' ),
				'connectionFail' => __( 'Connection failed.', 'promptpress-ai' ),
			),
		) );

		wp_enqueue_media();

		if (
			in_array(
				$hook,
				array(
					'aipb_page_aipb-builder',
					'toplevel_page_aipb-builder',
					'aipb_page_aipb-generate-page',
					'aipb_page_aipb-generate-post',
				),
				true
			)
		) {
			wp_enqueue_script(
				'aipb-builder',
				$this->plugin_url . 'assets/js/builder.js',
				array( 'aipb-admin' ),
				AIPB_VERSION,
				true
			);
		}
	}

	/**
	 * Display admin notices for setup requirements.
	 */
	public function admin_notices() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$is_aipb_page = ( 'toplevel_page_aipb-builder' === $screen->id )
			|| ( 0 === strpos( $screen->id ?? '', 'aipb_page_' ) );

		if ( ! $is_aipb_page ) {
			return;
		}

		$settings = get_option( 'aipb_settings', array() );
		if ( empty( $settings['api_key'] ) && empty( $settings['demo_mode'] ) ) {
			$settings_url = admin_url( 'admin.php?page=aipb-ai-settings' );
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p><p><a href="%s" class="button button-primary">%s</a></p></div>',
				esc_html__( 'AI Page Builder requires an API key to generate content. Please configure your API settings.', 'promptpress-ai' ),
				esc_url( $settings_url ),
				esc_html__( 'Configure API Settings', 'promptpress-ai' )
			);
		}
	}

	/**
	 * Register custom content types used by the plugin.
	 */
	public function register_content_types() {
		register_post_status( 'aipb_generated', array(
			'label'                     => _x( 'AI Generated', 'post status', 'promptpress-ai' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of posts */
			'label_count'               => _n_noop(
				'AI Generated <span class="count">(%s)</span>',
				'AI Generated <span class="count">(%s)</span>',
				'promptpress-ai'
			),
		) );
	}

	/**
	 * Create database table on plugin activation.
	 */
	public function on_activation() {
		$history = new Generation_History();
		$history->create_table();
	}

	/**
	 * Get the path to the admin pages directory.
	 *
	 * @return string
	 */
	private function get_pages_path() {
		return $this->plugin_path . 'admin/pages/';
	}

	/**
	 * Render the AI Builder page.
	 */
	public function render_page_builder() {
		$this->load_page( 'page-builder' );
	}

	/**
	 * Render the Generate Post page.
	 */
	public function render_generate_post() {
		$this->load_page( 'generate-post' );
	}

	/**
	 * Render the Generate Page page.
	 */
	public function render_generate_page() {
		$this->load_page( 'generate-page' );
	}

	/**
	 * Render the History page.
	 */
	public function render_history() {
		$this->load_page( 'history' );
	}

	/**
	 * Render the Templates page.
	 */
	public function render_templates() {
		$this->load_page( 'templates' );
	}

	/**
	 * Render the Brand Settings page.
	 */
	public function render_brand_settings() {
		$this->load_page( 'brand-settings' );
	}

	/**
	 * Render the AI Settings page.
	 */
	public function render_ai_settings() {
		$this->load_page( 'ai-settings' );
	}

	/**
	 * Render the Usage page.
	 */
	public function render_usage() {
		$this->load_page( 'usage' );
	}

	/**
	 * Load a page view file.
	 *
	 * @param string $page Page name (without .php extension).
	 */
	private function load_page( $page ) {
		$file = $this->get_pages_path() . $page . '.php';
		if ( file_exists( $file ) ) {
			include $file;
		} else {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Page file not found.', 'promptpress-ai' )
			);
		}
	}
}
