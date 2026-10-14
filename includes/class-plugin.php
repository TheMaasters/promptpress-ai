<?php
/**
 * Main plugin class.
 *
 * @package AIPB
 */

namespace AIPB;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * Main orchestrator for the AI Page Builder plugin.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Whether Elementor is active.
	 *
	 * @var bool
	 */
	private $elementor_active = false;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->elementor_active = class_exists( '\\Elementor\\Plugin' );
		$this->boot();
	}

	/**
	 * Initialize all plugin components.
	 *
	 * @return void
	 */
	private function boot() {
		load_plugin_textdomain( 'promptpress-ai', false, dirname( AIPB_PLUGIN_BASENAME ) . '/languages' );

		$this->register_admin();
		$this->register_rest_api();
		$this->register_gutenberg();

		if ( $this->elementor_active ) {
			// Elementor may already be loaded or may finish booting just after
			// PromptPress AI. Register against elementor/loaded so the widget
			// hooks are always installed before Elementor builds its editor.
			if ( did_action( 'elementor/loaded' ) ) {
				$this->register_elementor();
			} else {
				add_action( 'elementor/loaded', array( $this, 'register_elementor' ), 20 );
			}
		}

		/**
		 * Fires after the AI Page Builder plugin has fully initialized.
		 *
		 * @param Plugin $plugin The plugin instance.
		 */
		do_action( 'aipb_loaded', $this );
	}

	/**
	 * Register admin functionality.
	 *
	 * @return void
	 */
	private function register_admin() {
		if ( is_admin() ) {
			require_once AIPB_PLUGIN_DIR . 'admin/class-admin.php';

			$admin = \AIPB\Admin::get_instance();
		}
	}

	/**
	 * Register REST API endpoints.
	 *
	 * @return void
	 */
	private function register_rest_api() {
		// Instantiate the REST controller now so its constructor can register
		// its rest_api_init callback before WordPress fires that hook.
		require_once AIPB_PLUGIN_DIR . 'includes/class-rest-api.php';

		\AIPB\REST_API::get_instance();
	}

	/**
	 * Register Gutenberg block integration.
	 *
	 * @return void
	 */
	private function register_gutenberg() {
		add_action( 'init', function () {
			require_once AIPB_PLUGIN_DIR . 'integrations/gutenberg/class-gutenberg-integration.php';

			$gutenberg = new \AIPB\Integrations\Gutenberg_Integration();
			$gutenberg->init();
		} );
	}

	/**
	 * Register Elementor integration.
	 *
	 * @return void
	 */
	public function register_elementor() {
		require_once AIPB_PLUGIN_DIR . 'integrations/elementor/class-elementor-integration.php';

		$integration = new \AIPB\Integrations\Elementor_Integration();
		$integration->init();
	}

	/**
	 * Check if Elementor is active.
	 *
	 * @return bool
	 */
	public function is_elementor_active() {
		return $this->elementor_active;
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return AIPB_VERSION;
	}
}
