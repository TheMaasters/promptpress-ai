<?php
/**
 * Abstract AI Provider base class.
 *
 * @package AIPB
 * @subpackage AI_Provider
 */

namespace AIPB;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for all AI provider implementations.
 *
 * Provides common configuration, HTTP request handling, and error
 * management that concrete providers inherit.
 */
abstract class AI_Provider {

	/**
	 * Provider API key.
	 *
	 * @var string
	 */
	protected $api_key = '';

	/**
	 * Model identifier (e.g. "gpt-4o").
	 *
	 * @var string
	 */
	protected $model = '';

	/**
	 * Sampling temperature (0.0 - 2.0).
	 *
	 * @var float
	 */
	protected $temperature = 0.7;

	/**
	 * Maximum tokens for the response.
	 *
	 * @var int
	 */
	protected $max_tokens = 4096;

	/**
	 * Constructor.
	 *
	 * @param array $args Optional configuration overrides.
	 */
	public function __construct( array $args = array() ) {
		if ( isset( $args['api_key'] ) ) {
			$this->api_key = $args['api_key'];
		}
		if ( isset( $args['model'] ) ) {
			$this->model = $args['model'];
		}
		if ( isset( $args['temperature'] ) ) {
			$this->temperature = (float) $args['temperature'];
		}
		if ( isset( $args['max_tokens'] ) ) {
			$this->max_tokens = (int) $args['max_tokens'];
		}
	}

	/**
	 * Return the human-readable provider name.
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * Return the machine-readable provider identifier.
	 *
	 * @return string
	 */
	abstract public function get_id(): string;

	/**
	 * Generate structured page content from a prompt.
	 *
	 * @param string $prompt The user prompt.
	 * @param array  $args   Optional generation parameters.
	 *
	 * @return array|\WP_Error Decoded response array on success.
	 */
	abstract public function generate_content( string $prompt, array $args = array() );

	/**
	 * Generate an image from a text prompt.
	 *
	 * @param string $prompt Image description.
	 * @param array  $args   Optional parameters (size, quality, etc.).
	 *
	 * @return array|\WP_Error Array with 'url' and 'revised_prompt' on success.
	 */
	abstract public function generate_image( string $prompt, array $args = array() );

	/**
	 * Whether this provider supports image generation.
	 *
	 * @return bool
	 */
	public function supports_images(): bool {
		return false;
	}

	/**
	 * Build common HTTP headers for API requests.
	 *
	 * @return array
	 */
	protected function build_headers(): array {
		return array(
			'Content-Type'  => 'application/json; charset=UTF-8',
			'Authorization' => 'Bearer ' . $this->api_key,
		);
	}

	/**
	 * Send an HTTP request to the provider API.
	 *
	 * Handles timeouts, transport errors, and non-200 status codes,
	 * returning a \WP_Error on any failure.
	 *
	 * @param string $url     Full API endpoint URL.
	 * @param array  $args    wp_remote_post arguments.
	 * @param int    $timeout Request timeout in seconds.
	 *
	 * @return array|\WP_Error Parsed response body on success.
	 */
	protected function make_request( string $url, array $args = array(), int $timeout = 120 ) {
		$defaults = array(
			'timeout' => $timeout,
			'headers' => $this->build_headers(),
		);

		$args = wp_parse_args( $args, $defaults );

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'aipb_request_failed',
				sprintf(
					/* translators: 1: provider name, 2: error message */
					__( 'Request to %1$s failed: %2$s', 'promptpress-ai' ),
					$this->get_name(),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			$error_data = json_decode( $body, true );
			$message    = isset( $error_data['error']['message'] )
				? $error_data['error']['message']
				: sprintf( 'HTTP %d', $code );

			return new \WP_Error(
				'aipb_api_error',
				sprintf(
					/* translators: 1: provider name, 2: API error message */
					__( '%1$s API error: %2$s', 'promptpress-ai' ),
					$this->get_name(),
					$message
				),
				array(
					'status' => $code,
					'body'   => $error_data,
				)
			);
		}

		$decoded = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'aipb_json_decode_error',
				sprintf(
					/* translators: %s: JSON error message */
					__( 'Failed to parse API response: %s', 'promptpress-ai' ),
					json_last_error_msg()
				)
			);
		}

		return $decoded;
	}
}
