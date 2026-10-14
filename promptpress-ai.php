<?php
/**
 * Plugin Name: PromptPress AI
 * Plugin URI: https://themaasters.com/plugins/promptpress-ai/
 * Description: AI-powered WordPress content and page builder
 * Version: 1.0.7
 * Author: The Maasters
 * Author URI: https://themaasters.com
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: promptpress-ai
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package AIPB
 */

defined( 'ABSPATH' ) || exit;

define( 'AIPB_VERSION', '1.0.6' );
define( 'AIPB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIPB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AIPB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Add a direct Settings link to the Plugins screen.
 *
 * @param array $links Existing plugin action links.
 * @return array
 */
function aipb_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=aipb-ai-settings' ) ),
		esc_html__( 'Settings', 'promptpress-ai' )
	);

	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'aipb_plugin_action_links' );

load_plugin_textdomain( 'promptpress-ai', false, dirname( AIPB_PLUGIN_BASENAME ) . '/languages' );

require_once AIPB_PLUGIN_DIR . 'includes/class-security.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-ai-provider.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-ai-manager.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-content-generator.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-prompt-manager.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-generation-history.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-image-manager.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-image-provider.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-seo-manager.php';
require_once AIPB_PLUGIN_DIR . 'providers/class-openai-provider.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-plugin.php';


/**
 * Add a privacy-policy suggestion explaining AI API data transfer.
 *
 * @return void
 */
function aipb_add_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$content = '<p>' . esc_html__( 'PromptPress AI can send prompts, page content, and generation instructions to the AI provider selected by the site administrator. When OpenAI is configured, this data is sent to OpenAI through its API to generate the requested content. API keys are stored in the WordPress database and are not sent to site visitors.', 'promptpress-ai' ) . '</p>';
	$content .= '<p>' . esc_html__( 'Site administrators should review the AI provider’s current privacy and data-retention policies and inform their users where required. PromptPress AI does not sell or share site data for advertising.', 'promptpress-ai' ) . '</p>';

	wp_add_privacy_policy_content(
		__( 'PromptPress AI', 'promptpress-ai' ),
		$content
	);
}

add_action( 'admin_init', 'aipb_add_privacy_policy_content' );

register_activation_hook( __FILE__, 'aipb_activate' );
register_deactivation_hook( __FILE__, 'aipb_deactivate' );

/**
 * Plugin activation handler.
 *
 * @return void
 */
function aipb_activate() {
	$defaults = array(
		'provider'          => 'openai',
		'api_key'           => '',
		'model'             => 'gpt-5.6-luna',
		'content_language'  => 'en',
		'tone'              => 'professional',
		'max_tokens'        => 12000,
		'temperature'       => 0.7,
		'enable_logging'    => false,
		'demo_mode'        => false,
	);

	if ( false === get_option( 'aipb_settings', false ) ) {
		add_option( 'aipb_settings', $defaults );
	}

	$brand_defaults = array(
		'brand_name'  => get_bloginfo( 'name' ),
		'brand_voice' => '',
		'brand_colors' => array(),
		'custom_prompt_prefix' => '',
	);

	if ( false === get_option( 'aipb_brand_settings', false ) ) {
		add_option( 'aipb_brand_settings', $brand_defaults );
	}

	// Keep the history schema in one place so fresh installs and updates use
	// exactly the same columns.
	$history = new AIPB\Generation_History();
	$history->create_table();
	update_option( 'aipb_db_version', AIPB_VERSION, false);

	flush_rewrite_rules();
}

/**
 * Plugin deactivation handler.
 *
 * @return void
 */
function aipb_deactivate() {
	flush_rewrite_rules();
}

/**
 * Migrate settings from older PromptPress AI versions without overwriting
 * values chosen by the site administrator.
 *
 * @return void
 */
function aipb_migrate_settings() {
	$settings = get_option( 'aipb_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	$changed = false;
	if ( empty( $settings['provider'] ) ) {
		$settings['provider'] = 'openai';
		$changed = true;
	}
	if ( empty( $settings['model'] ) || 'gpt-3.5-turbo' === $settings['model'] || 'gpt-4' === $settings['model'] ) {
		$settings['model'] = 'gpt-5.6-luna';
		$changed = true;
	}
	if ( empty( $settings['image_model'] ) || in_array( $settings['image_model'], array( 'dall-e-2', 'dall-e-3' ), true ) ) {
		$settings['image_model'] = 'gpt-image-1-mini';
		$changed = true;
	}
	if ( ! isset( $settings['demo_mode'] ) ) {
		$settings['demo_mode'] = false;
		$changed = true;
	}
	if ( empty( $settings['max_tokens'] ) || (int) $settings['max_tokens'] < 4096 ) {
		$settings['max_tokens'] = 12000;
		$changed = true;
	}

	if ( $changed ) {
		update_option( 'aipb_settings', $settings, false );
	}
}

add_action( 'plugins_loaded', 'aipb_migrate_settings', 5 );


/**
 * Upgrade the plugin database schema after an update.
 *
 * @return void
 */
function aipb_maybe_upgrade_database() {
	$installed = get_option( 'aipb_db_version', '' );

	if ( AIPB_VERSION === $installed ) {
		return;
	}

	$history = new AIPB\Generation_History();
	$history->create_table();
	update_option( 'aipb_db_version', AIPB_VERSION, false );
}

add_action( 'plugins_loaded', 'aipb_maybe_upgrade_database', 6 );


/**
 * Initialize the plugin.
 *
 * @return AIPB\Plugin
 */
function aipb_init() {
	return AIPB\Plugin::instance();
}

add_action( 'plugins_loaded', 'aipb_init' );
