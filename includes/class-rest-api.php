<?php
/**
 * REST API endpoints for AI Page Builder.
 *
 * @package AIPB
 */

namespace AIPB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class REST_API
 *
 * Handles all REST API routes for the plugin.
 */
class REST_API {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'promptpress-ai/v1';

	/**
	 * Rate limit window in seconds.
	 *
	 * @var int
	 */
	const RATE_LIMIT_WINDOW = 60;

	/**
	 * Max requests per window for generation endpoints.
	 *
	 * @var int
	 */
	const RATE_LIMIT_MAX = 10;

	/**
	 * Instance.
	 *
	 * @var REST_API|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return REST_API
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all REST routes.
	 */
	public function register_routes() {
		$generation_args = array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_generate' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
			'args'                => array(
				'prompt'          => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'content_type'    => array(
					'type'              => 'string',
					'default'           => 'page',
					'enum'              => array( 'page', 'post', 'landing_page' ),
					'sanitize_callback' => 'sanitize_key',
				),
				'editor'          => array(
					'type'              => 'string',
					'default'           => 'gutenberg',
					'enum'              => array( 'gutenberg', 'elementor' ),
					'sanitize_callback' => 'sanitize_key',
				),
				'language'        => array(
					'type'              => 'string',
					'default'           => 'en',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'tone'            => array(
					'type'              => 'string',
					'default'           => 'professional',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'design_style'    => array(
					'type'              => 'string',
					'default'           => 'modern',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'image_source'    => array(
					'type'              => 'string',
					'default'           => 'ai',
					'enum'              => array( 'ai', 'stock', 'none' ),
					'sanitize_callback' => 'sanitize_key',
				),
				'seo_optimize'    => array(
					'type'              => 'boolean',
					'default'           => false,
				),
				'publish_status'  => array(
					'type'              => 'string',
					'default'           => 'draft',
					'enum'              => array( 'draft', 'publish', 'pending' ),
					'sanitize_callback' => 'sanitize_key',
				),
				'content_only'  => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		);

		register_rest_route(
			self::NAMESPACE,
			'/generate',
			$generation_args
		);

		register_rest_route(
			self::NAMESPACE,
			'/regenerate-section',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_regenerate_section' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
				'args'                => array(
					'post_id'     => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'section_id'  => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'instructions' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'editor'      => array(
						'type'              => 'string',
						'default'           => 'gutenberg',
						'enum'              => array( 'gutenberg', 'elementor' ),
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/edit-content',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_edit_content' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
				'args'                => array(
					'post_id'      => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'instructions' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'editor'       => array(
						'type'              => 'string',
						'default'           => 'gutenberg',
						'enum'              => array( 'gutenberg', 'elementor' ),
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/generate-image',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_generate_image' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
				'args'                => array(
					'prompt'  => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'size'    => array(
						'type'              => 'string',
						'default'           => '1024x1024',
						'enum'              => array( '1024x1024', '1792x1024', '1024x1792' ),
						'sanitize_callback' => 'sanitize_key',
					),
					'post_id' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/history',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'handle_get_history' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
					'args'                => array(
						'page'        => array(
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page'    => array(
							'type'              => 'integer',
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
						'status'      => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'editor_type' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_get_history' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/history/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'handle_delete_history' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/templates',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_get_templates' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
				'args'                => array(
					'category' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/suggestions',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_get_suggestions' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/analyze-page',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_analyze_page' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'handle_get_settings' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_update_settings' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/brand-settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'handle_get_brand_settings' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_update_brand_settings' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/usage',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_get_usage' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/test-connection',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_test_connection' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);
	}

	/**
	 * Check admin permissions and verify nonce.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function check_admin_permissions( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'aipb_rest_forbidden',
				__( 'You do not have permission to perform this action.', 'promptpress-ai' ),
				array( 'status' => 403 )
			);
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error(
				'aipb_rest_nonce_invalid',
				__( 'Invalid security token. Please refresh the page.', 'promptpress-ai' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check rate limit for generation endpoints.
	 *
	 * @return bool|\WP_Error True if allowed, WP_Error if rate limited.
	 */
	private function check_rate_limit() {
		$user_id      = get_current_user_id();
		$option_key   = 'aipb_rate_limit_' . $user_id;
		$rate_data    = get_option( $option_key, array( 'count' => 0, 'window_start' => 0 ) );
		$current_time = time();

		if ( ( $current_time - $rate_data['window_start'] ) > self::RATE_LIMIT_WINDOW ) {
			$rate_data = array(
				'count'        => 0,
				'window_start' => $current_time,
			);
		}

		if ( $rate_data['count'] >= self::RATE_LIMIT_MAX ) {
			$retry_after = self::RATE_LIMIT_WINDOW - ( $current_time - $rate_data['window_start'] );
			return new \WP_Error(
				'aipb_rate_limited',
				sprintf(
					/* translators: %d: number of seconds */
					__( 'Rate limit exceeded. Please try again in %d seconds.', 'promptpress-ai' ),
					$retry_after
				),
				array(
					'status'     => 429,
					'retry_after' => $retry_after,
				)
			);
		}

		$rate_data['count']++;
		update_option( $option_key, $rate_data, false );

		return true;
	}

	/**
	 * Send a standardized error response.
	 *
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code.
	 * @return \WP_REST_Response
	 */
	private function send_error( $message, $status = 500 ) {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'data'    => array(
					'message' => $message,
				),
			),
			$status
		);
	}

	/**
	 * Send a standardized success response.
	 *
	 * @param array $data Response data.
	 * @param int   $status HTTP status code.
	 * @return \WP_REST_Response
	 */
	private function send_success( $data = array(), $status = 200 ) {
		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			$status
		);
	}

	/**
	 * Handle content generation request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */

	/**
	 * Build deterministic local demo content without calling an AI provider.
	 *
	 * @param string $prompt User prompt.
	 * @param string $content_type Content type.
	 * @param array  $brand_settings Brand context.
	 * @param string $design_style Design style.
	 * @return array Structured content compatible with both renderers.
	 */
	private function get_demo_structured_content( string $prompt, string $content_type, array $brand_settings, string $design_style ): array {
		$brand_name = ! empty( $brand_settings['brand_name'] ) ? sanitize_text_field( $brand_settings['brand_name'] ) : get_bloginfo( 'name' );
		$primary    = ! empty( $brand_settings['primary_color'] ) ? sanitize_hex_color( $brand_settings['primary_color'] ) : '#4F46E5';
		$accent     = ! empty( $brand_settings['accent_color'] ) ? sanitize_hex_color( $brand_settings['accent_color'] ) : '#06B6D4';
		$topic      = sanitize_text_field( wp_trim_words( $prompt, 10, '' ) );
		$title      = $topic ? ucfirst( $topic ) : ( 'Modern ' . ucfirst( $content_type ) );

		return array(
			'page' => array(
				'title'            => $title,
				'slug'             => sanitize_title( $title ),
				'meta_title'       => $title . ' | ' . $brand_name,
				'meta_description' => 'A polished demo page generated by PromptPress AI for ' . $brand_name . '.',
				'focus_keyword'    => $topic,
				'sections'         => array(
					array(
						'id'           => 'demo-hero',
						'type'         => 'hero',
						'heading'      => $title,
						'subheading'   => 'Turn your idea into a polished WordPress page with PromptPress AI.',
						'content'      => 'This is Local Demo Mode. It demonstrates the complete generation and editor workflow without calling an external AI service.',
						'button_text'  => 'Get Started',
						'button_url'   => '#features',
						'primary_color'=> $primary,
						'accent_color' => $accent,
					),
					array(
						'id'        => 'demo-features',
						'type'      => 'features',
						'heading'   => 'Everything you need to get started',
						'content'   => 'A responsive, editable layout created from structured content.',
						'items'     => array(
							array( 'title' => 'AI-ready workflow', 'description' => 'Describe what you want and PromptPress AI converts the request into structured content.' ),
							array( 'title' => 'Native editor output', 'description' => 'The demo uses the same Gutenberg or Elementor rendering pipeline as real generations.' ),
							array( 'title' => 'Brand-aware design', 'description' => 'Your saved brand name and colors are reflected in the generated structure.' ),
						),
					),
					array(
						'id'        => 'demo-about',
						'type'      => 'about',
						'heading'   => 'Built for real WordPress workflows',
						'content'   => 'Edit the generated content normally after creation. Change text, spacing, colors, images and sections inside your chosen editor.',
					),
					array(
						'id'        => 'demo-testimonials',
						'type'      => 'testimonials',
						'heading'   => 'Designed to be easy to continue editing',
						'items'     => array(
							array( 'quote' => 'The workflow feels simple: prompt, generate, then continue editing in WordPress.', 'author' => 'Demo Customer', 'role' => 'Product Team' ),
							array( 'quote' => 'A useful way to test the plugin before connecting a paid AI account.', 'author' => 'Demo User', 'role' => 'WordPress Admin' ),
						),
					),
					array(
						'id'        => 'demo-cta',
						'type'      => 'cta',
						'heading'   => 'Ready to create your own page?',
						'content'   => 'Turn Local Demo Mode off, configure your AI provider, and generate a real page from your own prompt.',
						'button_text'=> 'Create With AI',
						'button_url' => '#builder',
						'primary_color'=> $primary,
					),
				),
			),
		);
	}

	public function handle_generate( $request ) {
		$rate_check = $this->check_rate_limit();
		if ( is_wp_error( $rate_check ) ) {
			return $this->send_error( $rate_check->get_error_message(), $rate_check->get_error_data( 'status' ) );
		}

		$params = $request->get_json_params();

		$prompt        = isset( $params['prompt'] ) ? $params['prompt'] : '';
		$content_type  = isset( $params['content_type'] ) ? $params['content_type'] : 'page';
		$editor        = isset( $params['editor'] ) ? $params['editor'] : 'gutenberg';
		$language      = isset( $params['language'] ) ? $params['language'] : 'en';
		$tone          = isset( $params['tone'] ) ? $params['tone'] : 'professional';
		$design_style  = isset( $params['design_style'] ) ? $params['design_style'] : 'modern';
		$image_source  = isset( $params['image_source'] ) ? $params['image_source'] : 'ai';
		$seo_optimize  = ! empty( $params['seo_optimize'] );
		$publish_status = isset( $params['publish_status'] ) ? $params['publish_status'] : 'draft';
		$content_only  = ! empty( $params['content_only'] );

		if ( empty( $prompt ) ) {
			return $this->send_error(
				__( 'A prompt is required for content generation.', 'promptpress-ai' ),
				400
			);
		}

		if ( 'elementor' === $editor && ! class_exists( '\\Elementor\\Plugin' ) ) {
			return $this->send_error(
				__( 'Elementor is not active. Install and activate Elementor before generating Elementor content.', 'promptpress-ai' ),
				400
			);
		}

		if ( $content_only ) {
			$ai_manager = AI_Manager::instance();

			$brand_settings = get_option( 'aipb_brand_settings', array() );

			$full_prompt = $ai_manager->build_generation_prompt(
				$prompt,
				$content_type,
				$editor,
				$language,
				$tone,
				$design_style,
				$brand_settings
			);

			$structured_content = $ai_manager->generate_content( $full_prompt );

			if ( is_wp_error( $structured_content ) ) {
				$this->log_generation( $prompt, $content_type, $editor, 'failed', $structured_content->get_error_message() );
				return $this->send_error( $structured_content->get_error_message(), 500 );
			}

			$generator = new Content_Generator();
			$content   = $generator->generate( $structured_content, $editor );

			if ( is_wp_error( $content ) ) {
				return $this->send_error( $content->get_error_message(), 500 );
			}

			$this->log_generation( $prompt, $content_type, $editor, 'success' );

			return $this->send_success( array(
				'content' => $content,
				'title'   => $structured_content['page']['title'] ?? '',
			) );
		}

		$settings = get_option( 'aipb_settings', array() );
		$demo_mode = ! empty( $settings['demo_mode'] );

		if ( $demo_mode ) {
			$brand_settings = get_option( 'aipb_brand_settings', array() );
			$structured_content = $this->get_demo_structured_content(
				$prompt,
				$content_type,
				is_array( $brand_settings ) ? $brand_settings : array(),
				$design_style
			);

			$generator = new Content_Generator();
			$result = $generator->generate_from_structured_content(
				$prompt,
				$structured_content,
				array(
					'editor_type'  => $editor,
					'post_type'    => ( 'landing_page' === $content_type ) ? 'page' : $content_type,
					'status'       => in_array( $publish_status, array( 'draft', 'publish', 'pending' ), true ) ? $publish_status : 'draft',
					'image_source' => 'none',
					'language'     => $language,
					'tone'         => $tone,
					'design_style' => $design_style,
					'seo_optimize' => $seo_optimize,
				),
				true
			);

			if ( is_wp_error( $result ) ) {
				$this->log_generation( $prompt, $content_type, $editor, 'failed', $result->get_error_message() );
				return $this->send_error( $result->get_error_message(), 500 );
			}

			$post_id = isset( $result['post_id'] ) ? absint( $result['post_id'] ) : 0;
			$is_elementor = 'elementor' === $editor;

			return $this->send_success( array(
				'post_id'            => $post_id,
				'post_url'           => $post_id ? get_permalink( $post_id ) : '',
				'post_title'         => isset( $result['post_title'] ) ? $result['post_title'] : '',
				'edit_url'           => $post_id ? admin_url( 'post.php?post=' . $post_id . '&action=edit' ) : '',
				'elementor_edit_url' => $post_id && $is_elementor ? admin_url( 'post.php?post=' . $post_id . '&action=elementor' ) : '',
				'status'             => isset( $result['status'] ) ? $result['status'] : $publish_status,
				'content_preview'    => isset( $result['content_preview'] ) ? $result['content_preview'] : '',
				'preview_url'        => $post_id ? get_permalink( $post_id ) : '',
				'content'            => isset( $result['content'] ) ? $result['content'] : '',
				'editor'             => $editor,
				'demo_mode'          => true,
			) );
		}

		$generator = new Content_Generator();
		$result    = $generator->generate_from_prompt( $prompt, array(
			'editor_type'  => $editor,
			'post_type'    => ( 'landing_page' === $content_type ) ? 'page' : $content_type,
			'status'       => in_array( $publish_status, array( 'draft', 'publish', 'pending' ), true ) ? $publish_status : 'draft',
			'image_source' => in_array( $image_source, array( 'ai', 'stock', 'none' ), true ) ? $image_source : 'none',
			'language'     => $language,
			'tone'         => $tone,
			'design_style' => $design_style,
			'seo_optimize' => $seo_optimize,
		) );

		if ( is_wp_error( $result ) ) {
			$this->log_generation( $prompt, $content_type, $editor, 'failed', $result->get_error_message() );
			return $this->send_error( $result->get_error_message(), 500 );
		}

		$this->log_generation( $prompt, $content_type, $editor, 'success' );

		$post_id = isset( $result['post_id'] ) ? absint( $result['post_id'] ) : 0;
		$is_elementor = 'elementor' === $editor;

		return $this->send_success( array(
			'post_id'          => $post_id,
			'post_url'         => $post_id ? get_permalink( $post_id ) : '',
			'post_title'       => isset( $result['post_title'] ) ? $result['post_title'] : '',
			'edit_url'         => $post_id ? admin_url( 'post.php?post=' . $post_id . '&action=edit' ) : '',
			'elementor_edit_url' => $post_id && $is_elementor ? admin_url( 'post.php?post=' . $post_id . '&action=elementor' ) : '',
			'status'           => isset( $result['status'] ) ? $result['status'] : $publish_status,
			'content_preview'  => isset( $result['content_preview'] ) ? $result['content_preview'] : '',
			'preview_url'      => $post_id ? get_permalink( $post_id ) : '',
			'content'          => isset( $result['content'] ) ? $result['content'] : '',
			'editor'           => $editor,
		) );
	}

	/**
	 * Handle section regeneration request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_regenerate_section( $request ) {
		$rate_check = $this->check_rate_limit();
		if ( is_wp_error( $rate_check ) ) {
			return $this->send_error( $rate_check->get_error_message(), $rate_check->get_error_data( 'status' ) );
		}

		$params = $request->get_json_params();

		$post_id      = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;
		$section_id   = isset( $params['section_id'] ) ? sanitize_text_field( $params['section_id'] ) : '';
		$instructions = isset( $params['instructions'] ) ? sanitize_textarea_field( $params['instructions'] ) : '';
		$editor       = isset( $params['editor'] ) ? sanitize_key( $params['editor'] ) : 'gutenberg';

		if ( ! $post_id || ! get_post( $post_id ) ) {
			return $this->send_error(
				__( 'Invalid post ID.', 'promptpress-ai' ),
				400
			);
		}

		if ( empty( $section_id ) ) {
			return $this->send_error(
				__( 'A section ID is required.', 'promptpress-ai' ),
				400
			);
		}

		$generator = new Content_Generator();
		$result    = $generator->edit_existing_post( $post_id, $instructions, $editor );

		if ( is_wp_error( $result ) ) {
			return $this->send_error( $result->get_error_message(), 500 );
		}

		return $this->send_success( array(
			'updated_content' => isset( $result['updated_content'] ) ? $result['updated_content'] : '',
			'section_html'    => isset( $result['section_html'] ) ? $result['section_html'] : '',
		) );
	}

	/**
	 * Handle AI-powered content editing request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_edit_content( $request ) {
		$rate_check = $this->check_rate_limit();
		if ( is_wp_error( $rate_check ) ) {
			return $this->send_error( $rate_check->get_error_message(), $rate_check->get_error_data( 'status' ) );
		}

		$params = $request->get_json_params();

		$post_id      = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;
		$instructions = isset( $params['instructions'] ) ? sanitize_textarea_field( $params['instructions'] ) : '';
		$editor       = isset( $params['editor'] ) ? sanitize_key( $params['editor'] ) : 'gutenberg';

		if ( ! $post_id || ! get_post( $post_id ) ) {
			return $this->send_error(
				__( 'Invalid post ID.', 'promptpress-ai' ),
				400
			);
		}

		if ( empty( $instructions ) ) {
			return $this->send_error(
				__( 'Editing instructions are required.', 'promptpress-ai' ),
				400
			);
		}

		$generator = new Content_Generator();
		$result    = $generator->edit_existing_post( $post_id, $instructions, $editor );

		if ( is_wp_error( $result ) ) {
			return $this->send_error( $result->get_error_message(), 500 );
		}

		return $this->send_success( array(
			'post_id'          => $post_id,
			'updated_content'  => isset( $result['content'] ) ? $result['content'] : '',
		) );
	}

	/**
	 * Handle image generation request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_generate_image( $request ) {
		$rate_check = $this->check_rate_limit();
		if ( is_wp_error( $rate_check ) ) {
			return $this->send_error( $rate_check->get_error_message(), $rate_check->get_error_data( 'status' ) );
		}

		$params  = $request->get_json_params();
		$prompt  = isset( $params['prompt'] ) ? $params['prompt'] : '';
		$size    = isset( $params['size'] ) ? $params['size'] : '1024x1024';
		$post_id = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;

		if ( empty( $prompt ) ) {
			return $this->send_error(
				__( 'An image prompt is required.', 'promptpress-ai' ),
				400
			);
		}

		$image_manager = new Image_Manager();
		$result        = $image_manager->generate_and_import( $prompt, $post_id );

		if ( is_wp_error( $result ) ) {
			return $this->send_error( $result->get_error_message(), 500 );
		}

		return $this->send_success( array(
			'image_url'     => isset( $result['image_url'] ) ? $result['image_url'] : '',
			'attachment_id' => isset( $result['attachment_id'] ) ? $result['attachment_id'] : 0,
		) );
	}

	/**
	 * Handle get history request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_get_history( $request ) {
		$page        = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : $request->get_param( 'page' );
		$per_page    = isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : $request->get_param( 'per_page' );
		$status      = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : $request->get_param( 'status' );
		$editor_type = isset( $_GET['editor_type'] ) ? sanitize_key( wp_unslash( $_GET['editor_type'] ) ) : $request->get_param( 'editor_type' );

		$page     = max( 1, $page ? $page : 1 );
		$per_page = max( 1, min( 100, $per_page ? $per_page : 20 ) );

		$history = new Generation_History();
		$result  = $history->get_history( array(
			'page'        => $page,
			'per_page'    => $per_page,
			'status'      => $status,
			'editor_type' => $editor_type,
		) );

		if ( is_wp_error( $result ) ) {
			return $this->send_error( $result->get_error_message(), 500 );
		}

		return $this->send_success( array(
			'items' => $result['records'],
			'total' => $result['total'],
			'pages' => $result['pages'],
		) );
	}

	/**
	 * Handle delete history request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_delete_history( $request ) {
		$id = $request->get_param( 'id' );

		$history = new Generation_History();
		$result  = $history->delete_record( $id );

		if ( is_wp_error( $result ) ) {
			return $this->send_error( $result->get_error_message(), 500 );
		}

		return $this->send_success( array(
			'deleted' => true,
		) );
	}

	/**
	 * Handle get templates request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_get_templates( $request ) {
		$category = $request->get_param( 'category' );

		$manager   = new Prompt_Manager();
		$templates = $manager->get_templates( $category );

		return $this->send_success( array(
			'templates' => $templates,
		) );
	}

	/**
	 * Handle get suggestions request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_get_suggestions( $request ) {
		$suggestions = array(
			array(
				'text'  => __( 'Create a modern landing page for a SaaS product with hero section, features, and pricing', 'promptpress-ai' ),
				'label' => __( 'SaaS Landing Page', 'promptpress-ai' ),
			),
			array(
				'text'  => __( 'Write a blog post about the top 10 trends in digital marketing for 2024', 'promptpress-ai' ),
				'label' => __( 'Marketing Blog Post', 'promptpress-ai' ),
			),
			array(
				'text'  => __( 'Generate a professional About Us page for a web development agency', 'promptpress-ai' ),
				'label' => __( 'Agency About Page', 'promptpress-ai' ),
			),
			array(
				'text'  => __( 'Create a product showcase page for an ecommerce store selling handmade jewelry', 'promptpress-ai' ),
				'label' => __( 'Product Showcase', 'promptpress-ai' ),
			),
			array(
				'text'  => __( 'Build a service page for a consulting firm with testimonials and case studies', 'promptpress-ai' ),
				'label' => __( 'Consulting Services', 'promptpress-ai' ),
			),
			array(
				'text'  => __( 'Write a detailed comparison article between two popular project management tools', 'promptpress-ai' ),
				'label' => __( 'Comparison Article', 'promptpress-ai' ),
			),
		);

		/**
		 * Filter the prompt suggestions.
		 *
		 * @param array $suggestions Default suggestions.
		 */
		$suggestions = apply_filters( 'aipb_prompt_suggestions', $suggestions );

		return $this->send_success( array(
			'suggestions' => $suggestions,
		) );
	}

	/**
	 * Handle analyze page request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_analyze_page( $request ) {
		$post_id = $request->get_param( 'post_id' );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return $this->send_error(
				__( 'Post not found.', 'promptpress-ai' ),
				404
			);
		}

		$content    = $post->post_content;
		$structure  = $this->extract_content_structure( $content );
		$sections   = $this->extract_sections( $content );
		$ctype      = get_post_type( $post_id );

		return $this->send_success( array(
			'structure'    => $structure,
			'sections'     => $sections,
			'content_type' => $ctype,
		) );
	}

	/**
	 * Extract content structure from raw content.
	 *
	 * @param string $content Post content.
	 * @return array
	 */
	private function extract_content_structure( $content ) {
		$structure = array(
			'has_headings'   => false,
			'has_images'     => false,
			'has_lists'      => false,
			'has_quotes'     => false,
			'has_tables'     => false,
			'block_count'    => 0,
			'heading_levels' => array(),
			'word_count'            => str_word_count( wp_strip_all_tags( $content ) ),
		);

		if ( preg_match_all( '/<h([1-6])[^>]*>/', $content, $matches ) ) {
			$structure['has_headings']   = true;
			$structure['heading_levels'] = array_unique( $matches[1] );
		}

		if ( preg_match( '/<img\s/', $content ) ) {
			$structure['has_images'] = true;
		}

		if ( preg_match( '/<[ou]l>/', $content ) ) {
			$structure['has_lists'] = true;
		}

		if ( preg_match( '/<blockquote/', $content ) ) {
			$structure['has_quotes'] = true;
		}

		if ( preg_match( '/<table/', $content ) ) {
			$structure['has_tables'] = true;
		}

		$structure['block_count'] = substr_count( $content, '<!-- wp:' ) + 1;

		return $structure;
	}

	/**
	 * Extract individual sections from content.
	 *
	 * @param string $content Post content.
	 * @return array
	 */
	private function extract_sections( $content ) {
		$sections = array();
		$blocks   = parse_blocks( $content );

		foreach ( $blocks as $index => $block ) {
			if ( 'core/separator' === $block['blockName'] || 'core/spacer' === $block['blockName'] ) {
				continue;
			}

			$section = array(
				'id'      => 'section-' . ( $index + 1 ),
				'type'    => $block['blockName'] ? $block['blockName'] : 'unknown',
				'content' => render_block( $block ),
			);

			if ( isset( $block['attrs']['className'] ) ) {
				$section['class'] = $block['attrs']['className'];
			}

			$sections[] = $section;
		}

		return $sections;
	}

	/**
	 * Handle get settings request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_get_settings( $request ) {
		$settings = get_option( 'aipb_settings', array() );

		return $this->send_success( array(
			'provider'      => isset( $settings['provider'] ) ? $settings['provider'] : 'openai',
			'api_key_set'   => ! empty( $settings['api_key'] ),
			'model'         => ! empty( $settings['model'] ) ? $settings['model'] : 'gpt-5.6-luna',
			'image_model'   => ! empty( $settings['image_model'] ) ? $settings['image_model'] : 'gpt-image-1-mini',
			'temperature'   => isset( $settings['temperature'] ) ? floatval( $settings['temperature'] ) : 0.7,
			'max_tokens'    => isset( $settings['max_tokens'] ) ? intval( $settings['max_tokens'] ) : 12000,
			'debug_logging' => ! empty( $settings['debug_logging'] ),
			'demo_mode'     => ! empty( $settings['demo_mode'] ),
		) );
	}

	/**
	 * Handle update settings request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_update_settings( $request ) {
		$params = $request->get_json_params();

		$current = get_option( 'aipb_settings', array() );

		if ( isset( $params['provider'] ) ) {
			$current['provider'] = 'openai' === sanitize_key( $params['provider'] ) ? 'openai' : '';
			if ( '' === $current['provider'] ) {
				return $this->send_error( __( 'Please select a supported AI provider.', 'promptpress-ai' ), 400 );
			}
		}

		if ( isset( $params['api_key'] ) && '' !== $params['api_key'] ) {
			$api_key = sanitize_text_field( $params['api_key'] );
			if ( ! Security::is_masked_key( $api_key ) && Security::validate_api_key( $api_key ) ) {
				$current['api_key'] = $api_key;
			}
		}

		if ( isset( $params['model'] ) ) {
			$current['model'] = sanitize_text_field( $params['model'] );
		}

		if ( isset( $params['image_model'] ) ) {
			$current['image_model'] = sanitize_text_field( $params['image_model'] );
		}

		if ( isset( $params['temperature'] ) ) {
			$current['temperature'] = max( 0, min( 2, floatval( $params['temperature'] ) ) );
		}

		if ( isset( $params['max_tokens'] ) ) {
			$current['max_tokens'] = max( 4096, min( 128000, intval( $params['max_tokens'] ) ) );
		}

		// Checkbox values must be written on every save so turning a setting off persists.
		$current['debug_logging'] = ! empty( $params['debug_logging'] );
		$current['demo_mode']     = ! empty( $params['demo_mode'] );

		update_option( 'aipb_settings', $current );

		return $this->send_success( array(
			'message' => __( 'Settings saved successfully.', 'promptpress-ai' ),
			'api_key_masked' => Security::mask_api_key( $current['api_key'] ?? '' ),
		) );
	}

	/**
	 * Handle get brand settings request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_get_brand_settings( $request ) {
		$brand = get_option( 'aipb_brand_settings', array() );

		return $this->send_success( array(
			'brand_name'          => isset( $brand['brand_name'] ) ? $brand['brand_name'] : '',
			'brand_description'   => isset( $brand['brand_description'] ) ? $brand['brand_description'] : '',
			'primary_color'       => isset( $brand['primary_color'] ) ? $brand['primary_color'] : '#0073aa',
			'secondary_color'     => isset( $brand['secondary_color'] ) ? $brand['secondary_color'] : '#005177',
			'accent_color'        => isset( $brand['accent_color'] ) ? $brand['accent_color'] : '#00aadc',
			'font_preference'     => isset( $brand['font_preference'] ) ? $brand['font_preference'] : 'system',
			'writing_tone'        => isset( $brand['writing_tone'] ) ? $brand['writing_tone'] : 'professional',
			'target_audience'     => isset( $brand['target_audience'] ) ? $brand['target_audience'] : '',
			'logo_id'             => isset( $brand['logo_id'] ) ? intval( $brand['logo_id'] ) : 0,
			'logo_url'            => isset( $brand['logo_id'] ) ? wp_get_attachment_url( $brand['logo_id'] ) : '',
			'brand_instructions'  => isset( $brand['brand_instructions'] ) ? $brand['brand_instructions'] : '',
		) );
	}

	/**
	 * Handle update brand settings request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_update_brand_settings( $request ) {
		$params = $request->get_json_params();

		$primary_color   = isset( $params['primary_color'] ) ? sanitize_hex_color( $params['primary_color'] ) : '';
		$secondary_color = isset( $params['secondary_color'] ) ? sanitize_hex_color( $params['secondary_color'] ) : '';
		$accent_color    = isset( $params['accent_color'] ) ? sanitize_hex_color( $params['accent_color'] ) : '';
		$logo_id         = isset( $params['logo_id'] ) ? absint( $params['logo_id'] ) : 0;

		if ( $logo_id && ! wp_attachment_is_image( $logo_id ) ) {
			$logo_id = 0;
		}

		$brand = array(
			'brand_name'         => isset( $params['brand_name'] ) ? sanitize_text_field( $params['brand_name'] ) : '',
			'brand_description'  => isset( $params['brand_description'] ) ? sanitize_textarea_field( $params['brand_description'] ) : '',
			'primary_color'      => $primary_color ?: '#0073aa',
			'secondary_color'    => $secondary_color ?: '#005177',
			'accent_color'       => $accent_color ?: '#00aadc',
			'font_preference'    => isset( $params['font_preference'] ) ? sanitize_text_field( $params['font_preference'] ) : 'system',
			'writing_tone'       => isset( $params['writing_tone'] ) ? sanitize_text_field( $params['writing_tone'] ) : 'professional',
			'target_audience'    => isset( $params['target_audience'] ) ? sanitize_text_field( $params['target_audience'] ) : '',
			'logo_id'            => $logo_id,
			'brand_instructions' => isset( $params['brand_instructions'] ) ? sanitize_textarea_field( $params['brand_instructions'] ) : '',
		);

		update_option( 'aipb_brand_settings', $brand );

		return $this->send_success( array(
			'message' => __( 'Brand settings saved successfully.', 'promptpress-ai' ),
		) );
	}

	/**
	 * Handle get usage statistics request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_get_usage( $request ) {
		$history = new Generation_History();
		$stats   = $history->get_stats();

		if ( is_wp_error( $stats ) ) {
			return $this->send_error( $stats->get_error_message(), 500 );
		}

		return $this->send_success( $stats );
	}

	/**
	 * Log a generation attempt.
	 *
	 * @param string $prompt       The prompt used.
	 * @param string $content_type Content type.
	 * @param string $editor       Editor type.
	 * @param string $status       Generation status.
	 * @param string $error        Error message if failed.
	 */
	private function log_generation( $prompt, $content_type, $editor, $status, $error = '' ) {
		if ( ! class_exists( 'AIPB\Generation_History' ) ) {
			return;
		}

		$history = new Generation_History();
		$history->log_generation( array(
			'prompt'        => $prompt,
			'post_type'     => $content_type,
			'editor_type'   => $editor,
			'status'        => $status,
			'error_message' => $error,
		) );
	}

	/**
	 * Handle test connection request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_test_connection( $request ) {
		$settings = get_option( 'aipb_settings', array() );
		$params   = $request->get_json_params();

		if ( ! empty( $settings['demo_mode'] ) ) {
			return $this->send_success(
				array(
					'message' => __( 'Local Demo Mode is active. No OpenAI API request was made.', 'promptpress-ai' ),
					'demo_mode' => true,
				)
			);
		}

		$provider_slug = isset( $params['provider'] ) && '' !== $params['provider']
			? sanitize_key( $params['provider'] )
			: ( ! empty( $settings['provider'] ) ? sanitize_key( $settings['provider'] ) : 'openai' );

		$api_key = isset( $params['api_key'] ) ? sanitize_text_field( $params['api_key'] ) : '';
		if ( Security::is_masked_key( $api_key ) || '' === $api_key ) {
			$api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		}

		if ( empty( $api_key ) ) {
			return $this->send_error(
				__( 'No API key configured. Enter an API key or save your settings first.', 'promptpress-ai' ),
				400
			);
		}

		if ( ! Security::validate_api_key( $api_key ) ) {
			return $this->send_error(
				__( 'The API key format is invalid.', 'promptpress-ai' ),
				400
			);
		}

		if ( 'openai' === $provider_slug ) {
			$model = isset( $params['model'] ) && '' !== $params['model']
				? sanitize_text_field( $params['model'] )
				: ( $settings['model'] ?? 'gpt-5.6-luna' );

			$provider = new \AIPB\Providers\OpenAI(
				array(
					'api_key' => $api_key,
					'model'   => $model,
				)
			);
		} else {
			$ai_manager = AI_Manager::instance();
			$provider   = $ai_manager->get_provider( $provider_slug );
		}

		if ( is_wp_error( $provider ) ) {
			return $this->send_error( $provider->get_error_message(), 400 );
		}

		if ( ! method_exists( $provider, 'test_connection' ) ) {
			return $this->send_error(
				__( 'The configured provider does not support connection testing.', 'promptpress-ai' ),
				400
			);
		}

		$result = $provider->test_connection();

		if ( is_wp_error( $result ) ) {
			$status = $result->get_error_data( 'status' );
			$status = is_numeric( $status ) ? (int) $status : 502;
			return $this->send_error( $result->get_error_message(), $status );
		}

		return $this->send_success(
			array(
				'message' => __( 'Connection successful! Your API key is valid.', 'promptpress-ai' ),
				'provider' => $provider_slug,
				'model'   => isset( $model ) ? $model : ( $settings['model'] ?? '' ),
			)
		);
	}
}
