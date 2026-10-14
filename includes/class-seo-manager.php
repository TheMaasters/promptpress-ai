<?php
/**
 * SEO Manager - generates and saves SEO metadata across multiple plugins.
 *
 * @package AIPB
 */

namespace AIPB;

defined( 'ABSPATH' ) || exit;

/**
 * Class SEO_Manager
 *
 * Detects active SEO plugins and stores metadata using the correct
 * meta key conventions for Rank Math, Yoast, and All in One SEO.
 */
class SEO_Manager {

	/**
	 * Return the detected active SEO plugin.
	 *
	 * @return string 'rank_math', 'yoast', 'aioseo', or 'none'.
	 */
	public function get_active_seo_plugin() {
		if ( class_exists( '\\RankMath\\Plugin' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return 'rank_math';
		}

		if ( class_exists( 'WPSEO_Options' ) || defined( 'WPSEO_VERSION' ) ) {
			return 'yoast';
		}

		if ( class_exists( 'AIOSEO\\Plugin\\AIOSEO' ) || defined( 'AIOSEO_VERSION' ) ) {
			return 'aioseo';
		}

		return 'none';
	}

	/**
	 * Check if a specific SEO plugin is active.
	 *
	 * @param string $plugin_slug Plugin identifier ('rank_math', 'yoast', 'aioseo').
	 *
	 * @return bool
	 */
	public function supports_seo_plugin( string $plugin_slug ) {
		return $plugin_slug === $this->get_active_seo_plugin();
	}

	/**
	 * Generate SEO metadata from content and title.
	 *
	 * @param string $content Page content (HTML or plain text).
	 * @param string $title   Page title.
	 *
	 * @return array {
	 *     @type string $meta_title       SEO title.
	 *     @type string $meta_description Meta description.
	 *     @type string $focus_keyword   Focus keyword.
	 *     @type string $slug            URL slug.
	 *     @type string $og_title        Open Graph title.
	 *     @type string $og_description  Open Graph description.
	 * }
	 */
	public function generate_seo_data( string $content, string $title ) {
		$plain_content = wp_strip_all_tags( $content );

		$meta_title = $title;
		if ( strlen( $meta_title ) > 60 ) {
			$meta_title = substr( $meta_title, 0, 57 ) . '...';
		}

		$meta_description = wp_trim_words( $plain_content, 30, '...' );
		if ( strlen( $meta_description ) > 160 ) {
			$meta_description = substr( $meta_description, 0, 157 ) . '...';
		}

		$words = explode( ' ', strtolower( $plain_content ) );
		$words = array_filter(
			$words,
			function ( $word ) {
				return strlen( $word ) > 3 && ! in_array( $word, array( 'this', 'that', 'with', 'from', 'have', 'been', 'were', 'they', 'their', 'about', 'which', 'would', 'could', 'should', 'there', 'these', 'those', 'will', 'your', 'more', 'also', 'into', 'than', 'them', 'then', 'what', 'when', 'some', 'very', 'just', 'only', 'over', 'such' ), true );
			}
		);

		$word_counts = array_count_values( $words );
		arsort( $word_counts );
		$focus_keyword = ! empty( $word_counts ) ? key( $word_counts ) : sanitize_title( $title );

		$slug = sanitize_title( $title );

		return array(
			'meta_title'       => $meta_title,
			'meta_description' => $meta_description,
			'focus_keyword'    => $focus_keyword,
			'slug'             => $slug,
			'og_title'         => $meta_title,
			'og_description'   => $meta_description,
		);
	}

	/**
	 * Save SEO data using the active plugin's meta keys.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $seo_data Output of generate_seo_data().
	 *
	 * @return bool True on success.
	 */
	public function save_seo_data( int $post_id, array $seo_data ) {
		$plugin = $this->get_active_seo_plugin();

		switch ( $plugin ) {
			case 'rank_math':
				$this->save_rank_math( $post_id, $seo_data );
				break;
			case 'yoast':
				$this->save_yoast( $post_id, $seo_data );
				break;
			case 'aioseo':
				$this->save_aioseo( $post_id, $seo_data );
				break;
			default:
				$this->save_default( $post_id, $seo_data );
				break;
		}

		/**
		 * Fires after SEO data has been saved for a post.
		 *
		 * @param int    $post_id The post ID.
		 * @param array  $seo_data The saved SEO data.
		 * @param string $plugin   Active SEO plugin slug.
		 */
		do_action( 'aipb_seo_data_saved', $post_id, $seo_data, $plugin );

		return true;
	}

	/**
	 * Save SEO data using Rank Math meta keys.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $seo_data SEO fields.
	 *
	 * @return void
	 */
	private function save_rank_math( int $post_id, array $seo_data ) {
		update_post_meta( $post_id, 'rank_math_title', $seo_data['meta_title'] );
		update_post_meta( $post_id, 'rank_math_description', $seo_data['meta_description'] );
		update_post_meta( $post_id, 'rank_math_focus_keyword', $seo_data['focus_keyword'] );
		update_post_meta( $post_id, 'rank_math_og_title', $seo_data['og_title'] );
		update_post_meta( $post_id, 'rank_math_og_description', $seo_data['og_description'] );

		if ( ! empty( $seo_data['slug'] ) ) {
			update_post_meta( $post_id, 'rank_math_permalink', $seo_data['slug'] );
		}
	}

	/**
	 * Save SEO data using Yoast SEO meta keys.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $seo_data SEO fields.
	 *
	 * @return void
	 */
	private function save_yoast( int $post_id, array $seo_data ) {
		update_post_meta( $post_id, '_yoast_wpseo_title', $seo_data['meta_title'] );
		update_post_meta( $post_id, '_yoast_wpseo_metadesc', $seo_data['meta_description'] );
		update_post_meta( $post_id, '_yoast_wpseo_focuskw', $seo_data['focus_keyword'] );
		update_post_meta( $post_id, '_yoast_wpseo_opengraph-title', $seo_data['og_title'] );
		update_post_meta( $post_id, '_yoast_wpseo_opengraph-description', $seo_data['og_description'] );

		if ( ! empty( $seo_data['slug'] ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_canonical', '' );
		}
	}

	/**
	 * Save SEO data using All in One SEO meta keys.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $seo_data SEO fields.
	 *
	 * @return void
	 */
	private function save_aioseo( int $post_id, array $seo_data ) {
		$aioseo_data = array(
			'title'       => $seo_data['meta_title'],
			'description' => $seo_data['meta_description'],
			'keywords'    => $seo_data['focus_keyword'],
		);
		update_post_meta( $post_id, '_aioseo_title', $seo_data['meta_title'] );
		update_post_meta( $post_id, '_aioseo_description', $seo_data['meta_description'] );
		update_post_meta( $post_id, '_aioseo_keywords', $seo_data['focus_keyword'] );
		update_post_meta( $post_id, '_aioseo_og_title', $seo_data['og_title'] );
		update_post_meta( $post_id, '_aioseo_og_description', $seo_data['og_description'] );

		if ( ! empty( $seo_data['slug'] ) ) {
			update_post_meta( $post_id, '_aioseo_link_title', $seo_data['slug'] );
		}
	}

	/**
	 * Fallback: save SEO data as generic post meta.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $seo_data SEO fields.
	 *
	 * @return void
	 */
	private function save_default( int $post_id, array $seo_data ) {
		update_post_meta( $post_id, '_aipb_meta_title', $seo_data['meta_title'] );
		update_post_meta( $post_id, '_aipb_meta_description', $seo_data['meta_description'] );
		update_post_meta( $post_id, '_aipb_focus_keyword', $seo_data['focus_keyword'] );
		update_post_meta( $post_id, '_aipb_og_title', $seo_data['og_title'] );
		update_post_meta( $post_id, '_aipb_og_description', $seo_data['og_description'] );

		if ( ! empty( $seo_data['slug'] ) ) {
			wp_update_post(
				array(
					'ID'        => $post_id,
					'post_name' => sanitize_title( $seo_data['slug'] ),
				)
			);
		}
	}
}
