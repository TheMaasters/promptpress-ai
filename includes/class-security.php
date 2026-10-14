<?php
/**
 * Security utilities.
 *
 * @package AIPB
 */

namespace AIPB;

defined( 'ABSPATH' ) || exit;

/**
 * Class Security
 *
 * Handles nonces, capability checks, sanitization, and rate limiting.
 */
class Security {

	/**
	 * Rate limit default window in seconds.
	 *
	 * @var int
	 */
	private static $rate_limit_window = 60;

	/**
	 * Rate limit maximum requests per window.
	 *
	 * @var int
	 */
	private static $rate_limit_max = 30;

	/**
	 * Generate a nonce for an action.
	 *
	 * @param string $action The nonce action name.
	 * @return string
	 */
	public static function generate_nonce( $action ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Verify a nonce for an action.
	 *
	 * @param string $nonce    The nonce to verify.
	 * @param string $action   The nonce action name.
	 * @return bool
	 */
	public static function verify_nonce( $nonce, $action ) {
		return (bool) wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Check that the current user has admin capability.
	 *
	 * @return bool
	 */
	public static function check_admin_capability() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Sanitize a plain text string.
	 *
	 * @param string $input Raw input.
	 * @return string
	 */
	public static function sanitize_string( $input ) {
		return sanitize_text_field( $input );
	}

	/**
	 * Sanitize HTML content.
	 *
	 * @param string $input Raw HTML input.
	 * @return string
	 */
	public static function sanitize_html( $input ) {
		return wp_kses_post( $input );
	}

	/**
	 * Validate an API key format.
	 *
	 * @param string $key The API key to validate.
	 * @return bool True if the key looks valid.
	 */
	public static function validate_api_key( $key ) {
		if ( empty( $key ) || ! is_string( $key ) ) {
			return false;
		}

		$key = trim( $key );

		if ( strlen( $key ) < 8 ) {
			return false;
		}

		if ( strlen( $key ) > 512 ) {
			return false;
		}

		return true;
	}

	/**
	 * Check whether a rate limit has been exceeded.
	 *
	 * @param string $key     Unique identifier for the rate limit bucket.
	 * @param int    $max     Maximum number of allowed requests. 0 uses default.
	 * @param int    $window  Time window in seconds. 0 uses default.
	 * @return bool True if the request is allowed, false if rate limited.
	 */
	public static function check_rate_limit( $key, $max = 0, $window = 0 ) {
		$max    = $max > 0 ? $max : self::$rate_limit_max;
		$window = $window > 0 ? $window : self::$rate_limit_window;

		$transient_key = 'aipb_rl_' . md5( $key );
		$count         = (int) get_transient( $transient_key );

		if ( $count >= $max ) {
			return false;
		}

		return true;
	}

	/**
	 * Increment the rate limit counter for a bucket.
	 *
	 * @param string $key     Unique identifier for the rate limit bucket.
	 * @param int    $max     Maximum number of allowed requests. 0 uses default.
	 * @param int    $window  Time window in seconds. 0 uses default.
	 * @return void
	 */
	public static function increment_rate_limit( $key, $max = 0, $window = 0 ) {
		$max    = $max > 0 ? $max : self::$rate_limit_max;
		$window = $window > 0 ? $window : self::$rate_limit_window;

		$transient_key = 'aipb_rl_' . md5( $key );
		$count         = (int) get_transient( $transient_key );

		set_transient( $transient_key, $count + 1, $window );
	}

	/**
	 * Sanitize settings fields from the admin form.
	 *
	 * @param array $input Raw settings data.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['provider'] = self::sanitize_string( $input['provider'] ?? '' );
		$sanitized['model']    = self::sanitize_string( $input['model'] ?? '' );

		$api_key = self::sanitize_string( $input['api_key'] ?? '' );
		if ( ! empty( $api_key ) && ! self::is_masked_key( $api_key ) && self::validate_api_key( $api_key ) ) {
			$sanitized['api_key'] = $api_key;
		}

		$sanitized['image_model']      = self::sanitize_string( $input['image_model'] ?? '' );
		$sanitized['content_language'] = self::sanitize_string( $input['content_language'] ?? 'en' );
		$sanitized['tone']            = self::sanitize_string( $input['tone'] ?? 'professional' );
		$sanitized['debug_logging']   = ! empty( $input['debug_logging'] );

		$max_tokens = absint( $input['max_tokens'] ?? 2048 );
		if ( $max_tokens < 1 ) {
			$max_tokens = 1;
		} elseif ( $max_tokens > 128000 ) {
			$max_tokens = 128000;
		}
		$sanitized['max_tokens'] = $max_tokens;

		$temperature = floatval( $input['temperature'] ?? 0.7 );
		if ( $temperature < 0.0 ) {
			$temperature = 0.0;
		} elseif ( $temperature > 2.0 ) {
			$temperature = 2.0;
		}
		$sanitized['temperature'] = $temperature;

		return $sanitized;
	}

	/**
	 * Mask an API key for display in the UI.
	 *
	 * @param string $key The full API key.
	 * @return string Masked version of the key.
	 */
	public static function mask_api_key( $key ) {
		if ( empty( $key ) || ! is_string( $key ) ) {
			return '';
		}

		$key = trim( $key );

		if ( strlen( $key ) <= 8 ) {
			return str_repeat( '*', strlen( $key ) );
		}

		$visible_suffix = substr( $key, -4 );
		$masked_length  = strlen( $key ) - 4;

		return str_repeat( '•', min( $masked_length, 20 ) ) . $visible_suffix;
	}

	/**
	 * Check whether a value is a masked API key (not a real key).
	 *
	 * @param string $value The value to check.
	 * @return bool True if the value appears to be masked.
	 */
	public static function is_masked_key( $value ) {
		if ( empty( $value ) ) {
			return false;
		}
		return preg_match( '/[•*]/', $value ) === 1;
	}

	/**
	 * Sanitize brand settings fields from the admin form.
	 *
	 * @param array $input Raw brand settings data.
	 * @return array Sanitized brand settings.
	 */
	public static function sanitize_brand_settings( $input ) {
		$sanitized = array();

		$sanitized['brand_name']    = self::sanitize_string( $input['brand_name'] ?? '' );
		$sanitized['brand_voice']   = self::sanitize_string( $input['brand_voice'] ?? '' );
		$sanitized['custom_prompt_prefix'] = self::sanitize_html( $input['custom_prompt_prefix'] ?? '' );

		$colors = array();
		if ( ! empty( $input['brand_colors'] ) && is_array( $input['brand_colors'] ) ) {
			foreach ( $input['brand_colors'] as $color ) {
				$color = sanitize_hex_color( $color );
				if ( $color ) {
					$colors[] = $color;
				}
			}
		}
		$sanitized['brand_colors'] = $colors;

		return $sanitized;
	}
}
