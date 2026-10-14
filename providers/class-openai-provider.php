<?php
/**
 * OpenAI provider implementation.
 *
 * @package AIPB
 * @subpackage Providers
 */

namespace AIPB\Providers;

use AIPB\AI_Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAI provider using the current Responses API for structured content
 * and the Images API for image generation.
 */
class OpenAI extends AI_Provider {

	const RESPONSES_ENDPOINT = 'https://api.openai.com/v1/responses';
	const IMAGE_ENDPOINT     = 'https://api.openai.com/v1/images/generations';
	const MODELS_ENDPOINT    = 'https://api.openai.com/v1/models';

	public function get_name(): string {
		return 'OpenAI';
	}

	public function get_id(): string {
		return 'openai';
	}

	public function supports_images(): bool {
		return true;
	}

	/**
	 * Generate a structured WordPress page using the Responses API.
	 *
	 * @param string $prompt User prompt.
	 * @param array  $args   Optional overrides.
	 * @return array|\WP_Error
	 */
	public function generate_content( string $prompt, array $args = array() ) {
		$api_key = $this->get_api_key();
		if ( is_wp_error( $api_key ) ) {
			return $api_key;
		}

		$model = ! empty( $args['model'] ) ? sanitize_text_field( $args['model'] ) : $this->get_model();
		$max_output_tokens = ! empty( $args['max_tokens'] ) ? absint( $args['max_tokens'] ) : $this->get_max_output_tokens();
		$max_output_tokens = max( 4000, min( 128000, $max_output_tokens ) );

		$system_prompt = isset( $args['system_prompt'] )
			? (string) $args['system_prompt']
			: $this->get_system_prompt( $args );

		$payload = array(
			'model' => $model,
			'instructions' => $system_prompt,
			'input' => $prompt,
			'text' => array(
				'format' => array(
					'type' => 'json_schema',
					'name' => 'wordpress_page',
					'description' => 'A complete structured WordPress page that can be rendered as native Gutenberg blocks or Elementor elements.',
					'schema' => $this->get_page_schema(),
					'strict' => false,
				),
			),
			'max_output_tokens' => $max_output_tokens,
		);

		$result = $this->make_request(
			self::RESPONSES_ENDPOINT,
			array(
				'method' => 'POST',
				'body' => wp_json_encode( $payload ),
			),
			180
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$content = $this->extract_response_text( $result );
		if ( '' === $content ) {
			return new \WP_Error(
			'aipb_empty_response',
			__( 'OpenAI returned an empty response. Please try again with a more specific prompt.', 'promptpress-ai' )
			);
		}

		$decoded = json_decode( $content, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return new \WP_Error(
			'aipb_json_decode_error',
			sprintf(
				/* translators: %s: JSON error message */
				__( 'OpenAI returned invalid structured data: %s', 'promptpress-ai' ),
				json_last_error_msg()
			)
			);
		}

		if ( empty( $decoded['page'] ) || ! is_array( $decoded['page'] ) ) {
			return new \WP_Error(
			'aipb_invalid_structure',
			__( 'OpenAI did not return the expected WordPress page structure. Please try again.', 'promptpress-ai' )
			);
		}

		return array(
			'page' => $decoded['page'],
			'model' => $model,
			'usage' => isset( $result['usage'] ) && is_array( $result['usage'] ) ? $result['usage'] : array(),
			'raw_response' => $content,
		);
	}

	/**
	 * Generate an image using the current OpenAI image model.
	 * GPT Image models return base64 image data rather than expiring URLs,
	 * so the Image Manager can import the result directly into Media Library.
	 *
	 * @param string $prompt Image prompt.
	 * @param array  $args   Image options.
	 * @return array|\WP_Error
	 */
	public function generate_image( string $prompt, array $args = array() ) {
		$api_key = $this->get_api_key();
		if ( is_wp_error( $api_key ) ) {
			return $api_key;
		}

		$settings = get_option( 'aipb_settings', array() );
		$model = ! empty( $args['model'] )
			? sanitize_text_field( $args['model'] )
			: ( ! empty( $settings['image_model'] ) ? sanitize_text_field( $settings['image_model'] ) : 'gpt-image-1-mini' );

		$size = isset( $args['size'] ) ? sanitize_text_field( $args['size'] ) : '1536x1024';
		$quality = isset( $args['quality'] ) ? sanitize_text_field( $args['quality'] ) : 'medium';

		$valid_sizes = array( '1024x1024', '1536x1024', '1024x1536' );
		if ( ! in_array( $size, $valid_sizes, true ) ) {
			$size = '1536x1024';
		}

		$valid_quality = array( 'low', 'medium', 'high', 'auto' );
		if ( ! in_array( $quality, $valid_quality, true ) ) {
			$quality = 'medium';
		}

		$payload = array(
			'model' => $model,
			'prompt' => $prompt,
			'n' => 1,
			'size' => $size,
			'quality' => $quality,
			'output_format' => 'png',
		);

		$result = $this->make_request(
			self::IMAGE_ENDPOINT,
			array(
				'method' => 'POST',
				'body' => wp_json_encode( $payload ),
			),
			180
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$image_data = isset( $result['data'][0] ) && is_array( $result['data'][0] ) ? $result['data'][0] : array();
		if ( empty( $image_data['b64_json'] ) && empty( $image_data['url'] ) ) {
			return new \WP_Error(
			'aipb_image_generation_failed',
			__( 'OpenAI did not return an image. The selected image model may not be available for this API key.', 'promptpress-ai' )
			);
		}

		return array(
			'url' => isset( $image_data['url'] ) ? esc_url_raw( $image_data['url'] ) : '',
			'b64_json' => isset( $image_data['b64_json'] ) ? (string) $image_data['b64_json'] : '',
			'revised_prompt' => isset( $image_data['revised_prompt'] ) ? sanitize_text_field( $image_data['revised_prompt'] ) : $prompt,
			'model' => $model,
			'size' => $size,
			'quality' => $quality,
		);
	}

	/**
	 * Verify the API key and selected text model with a tiny Responses request.
	 * This catches both authentication and model-access problems.
	 *
	 * @return true|\WP_Error
	 */
	public function test_connection() {
		$api_key = $this->get_api_key();
		if ( is_wp_error( $api_key ) ) {
			return $api_key;
		}

		$model = $this->get_model();
		$payload = array(
			'model' => $model,
			'input' => 'Reply with exactly: OK',
			'max_output_tokens' => 16,
		);

		$response = wp_remote_post(
			self::RESPONSES_ENDPOINT,
			array(
				'timeout' => 45,
				'headers' => $this->build_headers(),
				'body' => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
			'aipb_connection_failed',
			sprintf( __( 'OpenAI connection failed: %s', 'promptpress-ai' ), $response->get_error_message() )
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = isset( $data['error']['message'] ) ? $data['error']['message'] : sprintf( 'HTTP %d', $code );
			return new \WP_Error(
				'aipb_api_error',
				sprintf( __( 'OpenAI API error: %s', 'promptpress-ai' ), $message ),
				array( 'status' => $code )
			);
		}

		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'aipb_invalid_api_response', __( 'OpenAI returned an invalid response.', 'promptpress-ai' ) );
		}

		return true;
	}

	private function get_api_key() {
		$key = $this->api_key;
		if ( empty( $key ) ) {
			$settings = get_option( 'aipb_settings', array() );
			$key = isset( $settings['api_key'] ) ? trim( (string) $settings['api_key'] ) : '';
		}

		if ( empty( $key ) ) {
			return new \WP_Error(
				'aipb_missing_api_key',
				__( 'No OpenAI API key configured. Please add your API key in PromptPress AI → AI Settings.', 'promptpress-ai' )
			);
		}

		return $key;
	}

	private function get_model(): string {
		if ( ! empty( $this->model ) ) {
			return $this->model;
		}

		$settings = get_option( 'aipb_settings', array() );
		return ! empty( $settings['model'] ) ? sanitize_text_field( $settings['model'] ) : 'gpt-5.6-luna';
	}

	private function get_max_output_tokens(): int {
		$settings = get_option( 'aipb_settings', array() );
		$value = isset( $settings['max_tokens'] ) ? absint( $settings['max_tokens'] ) : 12000;
		return max( 4000, min( 128000, $value ) );
	}

	/**
	 * Extract text from a raw Responses API response.
	 * Supports both output_text and the output[].content[] representation.
	 */
	private function extract_response_text( array $result ): string {
		if ( ! empty( $result['output_text'] ) && is_string( $result['output_text'] ) ) {
			return trim( $result['output_text'] );
		}

		if ( ! empty( $result['output'] ) && is_array( $result['output'] ) ) {
			$parts = array();
			foreach ( $result['output'] as $item ) {
				if ( ! is_array( $item ) || empty( $item['content'] ) || ! is_array( $item['content'] ) ) {
					continue;
				}
				foreach ( $item['content'] as $content ) {
					if ( is_array( $content ) && isset( $content['text'] ) && is_string( $content['text'] ) ) {
						$parts[] = $content['text'];
					}
				}
			}
			return trim( implode( '', $parts ) );
		}

		return '';
	}

	private function get_system_prompt( array $args = array() ): string {
		$brand_context = '';
		if ( ! empty( $args['brand'] ) && is_array( $args['brand'] ) ) {
			$brand = $args['brand'];
			if ( ! empty( $brand['brand_name'] ) ) {
				$brand_context .= 'Brand Name: ' . sanitize_text_field( $brand['brand_name'] ) . "\n";
			}
			if ( ! empty( $brand['brand_voice'] ) ) {
				$brand_context .= 'Brand Voice: ' . sanitize_text_field( $brand['brand_voice'] ) . "\n";
			}
			if ( ! empty( $brand['brand_colors'] ) && is_array( $brand['brand_colors'] ) ) {
				$brand_context .= 'Brand Colors: ' . wp_json_encode( $brand['brand_colors'] ) . "\n";
			}
		}

		return "You are an expert WordPress UX designer, conversion copywriter, and content strategist. Generate a complete real WordPress page as structured JSON. Never return markdown, HTML outside string values, code fences, lorem ipsum, fake statistics, or commentary. Use real useful copy.\n\n" .
			"The page must be renderer-friendly for both Gutenberg and Elementor. Use section types such as hero, features, about, testimonials, pricing, faq, gallery, contact, cta, divider, spacer, or content. Each section must have a unique id and should contain only the fields needed for that section.\n\n" .
			"Global styles should contain primary_color, secondary_color, background_color, text_color, accent_color, and optional font_family.\n\n" .
			"Images: for visual sections provide image_prompt when an image would materially improve the design. Do not invent image URLs. The WordPress plugin will generate/import images separately.\n\n" .
			"SEO: include a useful title, slug, meta_title, meta_description, and focus_keyword.\n\n" .
			( $brand_context ? "Brand context:\n" . $brand_context : '' );
	}

	private function get_page_schema(): array {
		return array(
			'type' => 'object',
			'properties' => array(
				'page' => array(
					'type' => 'object',
					'properties' => array(
						'title' => array( 'type' => 'string' ),
						'slug' => array( 'type' => 'string' ),
						'meta_title' => array( 'type' => 'string' ),
						'meta_description' => array( 'type' => 'string' ),
						'focus_keyword' => array( 'type' => 'string' ),
						'sections' => array(
							'type' => 'array',
							'items' => array(
								'type' => 'object',
								'additionalProperties' => true,
							),
						),
						'global_styles' => array(
							'type' => 'object',
							'additionalProperties' => true,
						),
					),
					'required' => array( 'title', 'slug', 'meta_title', 'meta_description', 'focus_keyword', 'sections', 'global_styles' ),
					'additionalProperties' => false,
				),
			),
			'required' => array( 'page' ),
			'additionalProperties' => false,
		);
	}
}
