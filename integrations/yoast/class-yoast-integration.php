<?php
/**
 * Yoast SEO Integration.
 *
 * @package AI_Page_Builder
 * @subpackage Integrations
 */

namespace AIPB\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with Yoast SEO plugin.
 */
class Yoast {

	/**
	 * Primary meta key mappings for Yoast.
	 *
	 * @var array<string, string>
	 */
	private const META_KEYS = [
		'title'       => '_yoast_wpseo_title',
		'description' => '_yoast_wpseo_metadesc',
		'keyword'     => '_yoast_wpseo_focuskw',
	];

	/**
	 * OpenGraph meta key mappings for Yoast.
	 *
	 * @var array<string, string>
	 */
	private const OG_META_KEYS = [
		'title'       => '_yoast_wpseo_opengraph-title',
		'description' => '_yoast_wpseo_opengraph-description',
	];

	/**
	 * Determine whether Yoast SEO is installed and active.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'WPSEO_VERSION' )
			|| class_exists( 'WPSEO_Options' )
			|| function_exists( 'yoast' );
	}

	/**
	 * Save SEO data for a post.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $seo_data {
	 *     SEO data to save.
	 *
	 *     @type string $title       Page title.
	 *     @type string $description Meta description.
	 *     @type string $keyword     Focus keyword.
	 * }
	 * @return bool True on success, false on failure.
	 */
	public function save_seo_data( int $post_id, array $seo_data ): bool {
		if ( ! $this->is_active() ) {
			return false;
		}

		if ( ! $post_id ) {
			return false;
		}

		$saved = false;

		// Save primary Yoast meta.
		foreach ( self::META_KEYS as $key => $meta_key ) {
			if ( isset( $seo_data[ $key ] ) && '' !== $seo_data[ $key ] ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $seo_data[ $key ] ) ) );
				$saved = true;
			}
		}

		// Set OpenGraph title and description to match SEO values.
		if ( isset( $seo_data['title'] ) && '' !== $seo_data['title'] ) {
			update_post_meta( $post_id, self::OG_META_KEYS['title'], sanitize_text_field( wp_unslash( $seo_data['title'] ) ) );
		}

		if ( isset( $seo_data['description'] ) && '' !== $seo_data['description'] ) {
			update_post_meta( $post_id, self::OG_META_KEYS['description'], sanitize_text_field( wp_unslash( $seo_data['description'] ) ) );
		}

		return $saved;
	}

	/**
	 * Retrieve SEO data for a post.
	 *
	 * @param int $post_id The post ID.
	 * @return array{
	 *     title: string,
	 *     description: string,
	 *     keyword: string,
	 * } SEO data array.
	 */
	public function get_seo_data( int $post_id ): array {
		if ( ! $this->is_active() || ! $post_id ) {
			return [
				'title'       => '',
				'description' => '',
				'keyword'     => '',
			];
		}

		$data = [];

		foreach ( self::META_KEYS as $key => $meta_key ) {
			$value = get_post_meta( $post_id, $meta_key, true );
			$data[ $key ] = is_string( $value ) ? $value : '';
		}

		return $data;
	}
}
