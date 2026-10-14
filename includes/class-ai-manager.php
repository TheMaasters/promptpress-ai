<?php
/**
 * AI Manager — orchestrates providers, prompt building, and error handling.
 *
 * @package AIPB
 */

namespace AIPB;

defined( 'ABSPATH' ) || exit;

/**
 * Central manager for AI provider registration, selection, and content generation.
 */
class AI_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Registered provider instances.
	 *
	 * @var AI_Provider[]
	 */
	private $providers = array();

	/**
	 * Last provider ID used.
	 *
	 * @var string
	 */
	private $last_provider = '';

	/**
	 * Last model used.
	 *
	 * @var string
	 */
	private $last_model = '';

	/**
	 * Last token count.
	 *
	 * @var int
	 */
	private $last_tokens = 0;

	/**
	 * Return the singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — singleton.
	 */
	private function __construct() {
		$this->providers = array();

		$this->register_default_providers();
	}

	/**
	 * Register the built-in providers that ship with the plugin.
	 */
	private function register_default_providers() {
		$openai = new Providers\OpenAI();
		$this->register_provider( $openai->get_id(), $openai );
	}

	/**
	 * Register a provider instance.
	 *
	 * @param string      $provider_id Unique identifier (e.g. "openai").
	 * @param AI_Provider $provider    Provider instance.
	 *
	 * @return void
	 */
	public function register_provider( string $provider_id, AI_Provider $provider ): void {
		$this->providers[ $provider_id ] = $provider;
	}

	/**
	 * Retrieve a provider by ID, falling back to the active provider.
	 *
	 * @param string $provider_id Provider identifier.
	 *
	 * @return AI_Provider|\WP_Error Provider instance or error if not found.
	 */
	public function get_provider( string $provider_id ) {
		if ( isset( $this->providers[ $provider_id ] ) ) {
			return $this->providers[ $provider_id ];
		}

		$active_id = $this->get_active_provider_id();

		if ( ! empty( $active_id ) && isset( $this->providers[ $active_id ] ) ) {
			return $this->providers[ $active_id ];
		}

		if ( ! empty( $this->providers ) ) {
			return reset( $this->providers );
		}

		return new \WP_Error(
			'aipb_no_providers',
			__( 'No AI providers are registered. Please install and configure a provider.', 'promptpress-ai' )
		);
	}

	/**
	 * Get the provider instance that is currently active in settings.
	 *
	 * @return AI_Provider|\WP_Error
	 */
	public function get_active_provider() {
		$active_id = $this->get_active_provider_id();

		if ( empty( $active_id ) ) {
			return new \WP_Error(
				'aipb_no_active_provider',
				__( 'No AI provider is configured. Please select a provider in AI Page Builder settings.', 'promptpress-ai' )
			);
		}

		return $this->get_provider( $active_id );
	}

	/**
	 * Read the active provider ID from plugin settings.
	 *
	 * @return string Provider ID or empty string.
	 */
	private function get_active_provider_id(): string {
		$settings = get_option( 'aipb_settings', array() );
		return ! empty( $settings['provider'] ) ? sanitize_key( $settings['provider'] ) : 'openai';
	}

	/**
	 * Delegate content generation to the active provider.
	 *
	 * @param string $prompt User prompt.
	 * @param array  $args   Optional generation parameters.
	 *
	 * @return array|\WP_Error
	 */
	public function generate_content( string $prompt, array $args = array() ) {
		$provider = $this->get_active_provider();

		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		$start_time = microtime( true );

		$this->log_generation_request( 'content', $prompt, $provider->get_id() );

		$result = $provider->generate_content( $prompt, $args );

		$duration_ms = (int) ( ( microtime( true ) - $start_time ) * 1000 );

		$this->last_provider = $provider->get_id();
		$this->last_model    = isset( $args['model'] ) && '' !== $args['model'] ? $args['model'] : ( isset( $result['model'] ) ? (string) $result['model'] : '' );
		$this->last_tokens   = 0;

		if ( is_wp_error( $result ) ) {
			$this->log_generation_result( 'content', $result, $provider->get_id(), $duration_ms );
			return $result;
		}

		if ( is_array( $result ) && isset( $result['usage']['total_tokens'] ) ) {
			$this->last_tokens = (int) $result['usage']['total_tokens'];
		}

		$this->log_generation_result( 'content', $result, $provider->get_id(), $duration_ms );

		return $result;
	}

	/**
	 * Delegate image generation to the active provider.
	 *
	 * @param string $prompt Image description.
	 * @param array  $args   Optional parameters (size, quality, etc.).
	 *
	 * @return array|\WP_Error
	 */
	public function generate_image( string $prompt, array $args = array() ) {
		$provider = $this->get_active_provider();

		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		if ( ! $provider->supports_images() ) {
			return new \WP_Error(
				'aipb_no_image_support',
				sprintf(
					/* translators: %s: provider name */
					__( '%s does not support image generation.', 'promptpress-ai' ),
					$provider->get_name()
				)
			);
		}

		$start_time = microtime( true );

		$this->log_generation_request( 'image', $prompt, $provider->get_id() );

		$result = $provider->generate_image( $prompt, $args );

		$duration_ms = (int) ( ( microtime( true ) - $start_time ) * 1000 );

		$this->log_generation_result( 'image', $result, $provider->get_id(), $duration_ms );

		return $result;
	}

	/**
	 * Build the full generation prompt from user inputs and brand settings.
	 *
	 * Constructs a detailed, contextual prompt that gives the AI everything
	 * it needs to generate a high-quality page.
	 *
	 * @param string $user_prompt    The raw user prompt or topic description.
	 * @param string $content_type   The type of page (e.g. "landing", "about", "blog", "product").
	 * @param string $editor         Target editor: "gutenberg", "elementor", or "blocks".
	 * @param string $language       ISO 639-1 language code (e.g. "en", "es", "fr").
	 * @param string $tone           Desired tone (e.g. "professional", "casual", "formal", "playful").
	 * @param string $design_style   Visual style hint (e.g. "modern", "minimal", "bold", "corporate").
	 * @param array  $brand_settings Brand context from aipb_brand_settings option.
	 *
	 * @return string Fully constructed prompt.
	 */
	public function build_generation_prompt(
		string $user_prompt,
		string $content_type = 'landing',
		string $editor = 'gutenberg',
		string $language = 'en',
		string $tone = 'professional',
		string $design_style = 'modern',
		array $brand_settings = array()
	): string {
		$parts = array();

		$parts[] = 'Generate a complete WordPress page based on the following requirements.';
		$parts[] = '';
		$parts[] = '## Page Topic / Request';
		$parts[] = $user_prompt;
		$parts[] = '';

		$parts[] = '## Page Configuration';
		$parts[] = sprintf( '- Content Type: %s', sanitize_text_field( $content_type ) );
		$parts[] = sprintf( '- Target Editor: %s', sanitize_text_field( $editor ) );
		$parts[] = sprintf( '- Language: %s', sanitize_text_field( $language ) );
		$parts[] = sprintf( '- Tone: %s', sanitize_text_field( $tone ) );
		$parts[] = sprintf( '- Design Style: %s', sanitize_text_field( $design_style ) );
		$parts[] = '';

		if ( ! empty( $brand_settings ) && is_array( $brand_settings ) ) {
			$parts[] = '## Brand Information';

			if ( ! empty( $brand_settings['brand_name'] ) ) {
				$parts[] = sprintf( '- Brand Name: %s', sanitize_text_field( $brand_settings['brand_name'] ) );
			}
			if ( ! empty( $brand_settings['brand_description'] ) ) {
				$parts[] = sprintf( '- Brand Description: %s', sanitize_textarea_field( $brand_settings['brand_description'] ) );
			}
			if ( ! empty( $brand_settings['writing_tone'] ) ) {
				$parts[] = sprintf( '- Brand Writing Tone: %s', sanitize_text_field( $brand_settings['writing_tone'] ) );
			}
			if ( ! empty( $brand_settings['target_audience'] ) ) {
				$parts[] = sprintf( '- Target Audience: %s', sanitize_text_field( $brand_settings['target_audience'] ) );
			}
			$brand_colors = array(
				'Primary'   => $brand_settings['primary_color'] ?? '',
				'Secondary' => $brand_settings['secondary_color'] ?? '',
				'Accent'    => $brand_settings['accent_color'] ?? '',
			);
			$parts[] = '- Brand Colors:';
			foreach ( $brand_colors as $color_name => $color_value ) {
				$color = sanitize_hex_color( $color_value );
				if ( $color ) {
					$parts[] = sprintf( '  - %s: %s', $color_name, $color );
				}
			}
			if ( ! empty( $brand_settings['brand_instructions'] ) ) {
				$parts[] = '- Additional Brand Instructions: ' . sanitize_textarea_field( $brand_settings['brand_instructions'] );
			}
			$parts[] = '';
		}

		$parts[] = '## Requirements';
		$parts[] = '- Generate complete, real content — no lorem ipsum or placeholders.';
		$parts[] = '- Use benefit-focused, action-oriented copy.';
		$parts[] = '- Ensure SEO best practices for headings and meta data.';
		$parts[] = '- All button text should be compelling and specific.';
		$parts[] = '- Color palette should be cohesive and have sufficient contrast.';
		$parts[] = '- Section IDs should be descriptive and unique.';
		$parts[] = '';

		if ( 'elementor' === $editor ) {
			$parts[] = '## Editor Notes';
			$parts[] = '- Output should work well with Elementor widgets.';
			$parts[] = '- Prefer sections with clear column layouts.';
			$parts[] = '';
		}

		return implode( "\n", $parts );
	}

	/**
	 * Format a user-friendly error message from a WP_Error.
	 *
	 * Extracts the most relevant error message for display in the admin UI.
	 *
	 * @param \WP_Error $error The error object.
	 *
	 * @return string Human-readable error message.
	 */
	public function format_error( \WP_Error $error ): string {
		$message = $error->get_error_message();

		if ( empty( $message ) ) {
			$message = __( 'An unexpected error occurred while generating content.', 'promptpress-ai' );
		}

		return $message;
	}

	/**
	 * Log a generation request (without sensitive payload data).
	 *
	 * @param string $type       "content" or "image".
	 * @param string $prompt     The user prompt (truncated for safety).
	 * @param string $provider_id Provider identifier.
	 *
	 * @return void
	 */
	private function log_generation_request( string $type, string $prompt, string $provider_id ): void {
		if ( ! $this->is_logging_enabled() ) {
			return;
		}

		$truncated_prompt = wp_trim_words( $prompt, 20, '…' );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				sprintf(
					'[AIPB] Generation request: type=%s provider=%s prompt="%s"',
					$type,
					$provider_id,
					$truncated_prompt
				)
			);
		}
	}

	/**
	 * Log the result of a generation request.
	 *
	 * @param string     $type          "content" or "image".
	 * @param mixed      $result        The result or WP_Error.
	 * @param string     $provider_id   Provider identifier.
	 * @param int        $duration_ms   Request duration in milliseconds.
	 *
	 * @return void
	 */
	private function log_generation_result( string $type, $result, string $provider_id, int $duration_ms ): void {
		if ( ! $this->is_logging_enabled() ) {
			return;
		}

		$status = is_wp_error( $result ) ? 'error' : 'success';
		$detail = '';

		if ( is_wp_error( $result ) ) {
			$detail = $result->get_error_message();
		} elseif ( is_array( $result ) && isset( $result['usage'] ) ) {
			$tokens = $result['usage'];
			if ( isset( $tokens['total_tokens'] ) ) {
				$detail = sprintf( 'tokens=%d', $tokens['total_tokens'] );
			}
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				sprintf(
					'[AIPB] Generation result: type=%s provider=%s status=%s duration=%dms %s',
					$type,
					$provider_id,
					$status,
					$duration_ms,
					$detail
				)
			);
		}
	}

	/**
	 * Check whether debug logging is enabled.
	 *
	 * @return bool
	 */
	private function is_logging_enabled(): bool {
		$settings = get_option( 'aipb_settings', array() );

		if ( ! empty( $settings['debug_logging'] ) ) {
			return true;
		}

		return ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG );
	}

	/**
	 * Get the ID of the provider used in the last generation.
	 *
	 * @return string
	 */
	public function get_last_provider(): string {
		return $this->last_provider;
	}

	/**
	 * Get the model used in the last generation.
	 *
	 * @return string
	 */
	public function get_last_model(): string {
		return $this->last_model;
	}

	/**
	 * Get the token count from the last generation.
	 *
	 * @return int
	 */
	public function get_last_tokens(): int {
		return $this->last_tokens;
	}
}
