<?php
/**
 * Rank Math SEO Integration.
 *
 * @package AI_Page_Builder
 * @subpackage Integrations
 */

namespace AIPB\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with Rank Math SEO plugin.
 */
class RankMath {

	/**
	 * Meta key mappings for Rank Math.
	 *
	 * @var array<string, string>
	 */
	private const META_KEYS = [
		'title'       => 'rank_math_title',
		'description' => 'rank_math_description',
		'keyword'     => 'rank_math_focus_keyword',
	];

	/**
	 * Determine whether Rank Math is installed and active.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return function_exists( 'rank_math' )
			|| class_exists( 'RankMath' )
			|| defined( 'RANK_MATH_VERSION' );
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

		$mapping = [
			'title'       => self::META_KEYS['title'],
			'description' => self::META_KEYS['description'],
			'keyword'     => self::META_KEYS['keyword'],
		];

		$saved = false;

		foreach ( $mapping as $key => $meta_key ) {
			if ( isset( $seo_data[ $key ] ) && '' !== $seo_data[ $key ] ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $seo_data[ $key ] ) ) );
				$saved = true;
			}
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
