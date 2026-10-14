<?php
/**
 * All in One SEO Integration.
 *
 * @package AI_Page_Builder
 * @subpackage Integrations
 */

namespace AIPB\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with All in One SEO (AIOSEO) plugin.
 */
class AIOSEO {

	/**
	 * Meta key mappings for AIOSEO.
	 *
	 * @var array<string, string>
	 */
	private const META_KEYS = [
		'title'       => '_aioseo_title',
		'description' => '_aioseo_description',
		'keywords'    => '_aioseo_keywords',
	];

	/**
	 * Determine whether All in One SEO is installed and active.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'AIOSEO_VERSION' )
			|| class_exists( 'AIOSEO\\Plugin\\AIOSEO' )
			|| function_exists( 'aioseo' );
	}

	/**
	 * Save SEO data for a post.
	 *
	 * Attempts to use the AIOSEO object API when available for data integrity,
	 * and falls back to direct post meta updates when necessary.
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

		// Try the AIOSEO object-based approach first.
		if ( class_exists( 'AIOSEO\\Plugin\\AIOSEO\\Pro\\Api\\PostMeta' ) || class_exists( 'AIOSEO\\Plugin\\AIOSEO\\Api\\PostMeta' ) ) {
			$saved = $this->save_via_object( $post_id, $seo_data );
		}

		// Fall back to direct meta updates if the object approach failed or is unavailable.
		if ( ! $saved ) {
			$saved = $this->save_via_meta( $post_id, $seo_data );
		}

		return $saved;
	}

	/**
	 * Save SEO data using the AIOSEO object API.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $seo_data SEO data array.
	 * @return bool
	 */
	private function save_via_object( int $post_id, array $seo_data ): bool {
		try {
			$object = \AIOSEO\Helpers\Meta::getPostMetaObject( $post_id );

			if ( ! $object ) {
				return false;
			}

			if ( isset( $seo_data['title'] ) && '' !== $seo_data['title'] ) {
				$object->title = sanitize_text_field( wp_unslash( $seo_data['title'] ) );
			}

			if ( isset( $seo_data['description'] ) && '' !== $seo_data['description'] ) {
				$object->description = sanitize_text_field( wp_unslash( $seo_data['description'] ) );
			}

			if ( isset( $seo_data['keyword'] ) && '' !== $seo_data['keyword'] ) {
				$object->keywords = sanitize_text_field( wp_unslash( $seo_data['keyword'] ) );
			}

			$object->save();

			return true;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Save SEO data via direct post meta updates.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $seo_data SEO data array.
	 * @return bool
	 */
	private function save_via_meta( int $post_id, array $seo_data ): bool {
		$mapping = [
			'title'       => self::META_KEYS['title'],
			'description' => self::META_KEYS['description'],
			'keywords'    => self::META_KEYS['keywords'],
		];

		// Map the standard 'keyword' key to AIOSEO's 'keywords' meta key.
		$data = $seo_data;
		if ( isset( $data['keyword'] ) && ! isset( $data['keywords'] ) ) {
			$data['keywords'] = $data['keyword'];
		}

		$saved = false;

		foreach ( $mapping as $key => $meta_key ) {
			if ( isset( $data[ $key ] ) && '' !== $data[ $key ] ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $data[ $key ] ) ) );
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

		$title       = (string) get_post_meta( $post_id, self::META_KEYS['title'], true );
		$description = (string) get_post_meta( $post_id, self::META_KEYS['description'], true );
		$keyword     = (string) get_post_meta( $post_id, self::META_KEYS['keywords'], true );

		return [
			'title'       => $title,
			'description' => $description,
			'keyword'     => $keyword,
		];
	}
}
