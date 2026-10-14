<?php
/**
 * Abstract Image Provider base class.
 *
 * @package AIPB
 */

namespace AIPB;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for all image source implementations.
 *
 * Image providers can either generate new images (like DALL-E or
 * Midjourney) or fetch existing ones from stock libraries, Unsplash,
 * or the WordPress Media Library. Use supports_generation() to
 * distinguish between the two capabilities.
 */
abstract class Image_Provider {

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
	 * Retrieve or generate an image matching the given query.
	 *
	 * Implementations should return an array with at minimum:
	 *   - 'url'   (string) — the image URL.
	 *   - 'alt'   (string) — alt text or description.
	 *
	 * @param string $query Search query or generation prompt.
	 * @param array  $args  Optional provider-specific parameters.
	 *
	 * @return array|\WP_Error Image data array on success.
	 */
	abstract public function get_image( string $query, array $args = array() );

	/**
	 * Whether this provider generates new images from prompts.
	 *
	 * Return true for AI image generators (DALL-E, Midjourney, Stable
	 * Diffusion) and false for stock/library sources (Unsplash, Media
	 * Library).
	 *
	 * @return bool
	 */
	public function supports_generation(): bool {
		return false;
	}
}
