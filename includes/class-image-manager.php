<?php
/**
 * Image Manager - handles image import, generation, and media library integration.
 *
 * @package AIPB
 */

namespace AIPB;

defined( 'ABSPATH' ) || exit;

/**
 * Class Image_Manager
 *
 * Downloads remote images, generates AI images, imports them into the
 * WordPress Media Library, and provides placeholder fallbacks.
 */
class Image_Manager {

	/**
	 * Download a remote image and attach it to a post.
	 *
	 * @param string $url       Image URL.
	 * @param int    $post_id   Post to attach to.
	 * @param string $alt_text  Alt text for the attachment.
	 * @param string $title     Attachment title.
	 *
	 * @return int|\WP_Error Attachment ID on success.
	 */
	public function import_image( string $url, int $post_id, string $alt_text = '', string $title = '' ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		if ( ! wp_http_validate_url( $url ) ) {
			return new \WP_Error(
				'aipb_invalid_image_url',
				__( 'The image URL is invalid or unsafe.', 'promptpress-ai' )
			);
		}

		$existing_id = $this->avoid_duplicate( $url );
		if ( $existing_id ) {
			if ( $alt_text ) {
				update_post_meta( $existing_id, '_wp_attachment_image_alt', $this->generate_alt_text( $alt_text ) );
			}
			if ( $post_id > 0 ) {
				$this->attach_to_post( $existing_id, $post_id );
			}
			return $existing_id;
		}

		$tmp_file = download_url( $url, 30 );

		if ( is_wp_error( $tmp_file ) ) {
			return new \WP_Error(
				'aipb_image_download_failed',
				sprintf(
					/* translators: %s: Error message */
					__( 'Failed to download image: %s', 'promptpress-ai' ),
					$tmp_file->get_error_message()
				)
			);
		}

		$filename = $this->generate_filename( $url, $alt_text );
		$file_array = array(
			'name'     => sanitize_file_name( $filename ),
			'tmp_name' => $tmp_file,
		);

		$attachment_title = ! empty( $title )
			? sanitize_text_field( $title )
			: sanitize_text_field( pathinfo( $filename, PATHINFO_FILENAME ) );

		$attachment_id = media_handle_sideload(
			$file_array,
			$post_id > 0 ? $post_id : 0,
			$attachment_title
		);

		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp_file );
			return new \WP_Error(
				'aipb_image_import_failed',
				sprintf(
					/* translators: %s: Error message */
					__( 'Could not import the image into the Media Library: %s', 'promptpress-ai' ),
					$attachment_id->get_error_message()
				)
			);
		}

		update_post_meta( $attachment_id, 'aipb_original_url', esc_url_raw( $url ) );

		if ( $alt_text ) {
			update_post_meta(
				$attachment_id,
				'_wp_attachment_image_alt',
				$this->generate_alt_text( $alt_text )
			);
		}

		@unlink( $tmp_file );

		return (int) $attachment_id;
	}

	/**
	 * Generate an AI image and import it into the Media Library.
	 *
	 * @param string $prompt   Image generation prompt.
	 * @param int    $post_id  Post to attach to.
	 * @param string $provider AI provider slug.
	 *
	 * @return int|\WP_Error Attachment ID on success.
	 */
	public function generate_and_import( string $prompt, int $post_id, string $provider = 'openai' ) {
		require_once AIPB_PLUGIN_DIR . 'includes/class-ai-manager.php';
		$ai_manager = AI_Manager::instance();

		$image_result = $ai_manager->generate_image( $prompt, array( 'provider' => $provider ) );

		if ( is_wp_error( $image_result ) ) {
			return $image_result;
		}

		$image_url  = $image_result['url'] ?? '';
		$image_b64  = $image_result['b64_json'] ?? '';
		$alt_text   = $image_result['revised_prompt'] ?? $prompt;

		if ( ! empty( $image_b64 ) ) {
			return $this->import_base64_image( $image_b64, $post_id, $alt_text, $alt_text );
		}

		if ( empty( $image_url ) ) {
			return new \WP_Error(
				'aipb_no_image_data',
				__( 'The AI provider did not return usable image data.', 'promptpress-ai' )
			);
		}

		return $this->import_image( $image_url, $post_id, $alt_text, $alt_text );
	}

	/**
	 * Import a base64-encoded PNG/JPEG image returned by an AI provider.
	 *
	 * @param string $base64_data Base64 image payload.
	 * @param int    $post_id     Parent post ID.
	 * @param string $alt_text    Attachment alt text.
	 * @param string $title       Attachment title.
	 * @return int|\WP_Error Attachment ID.
	 */
	public function import_base64_image( string $base64_data, int $post_id, string $alt_text = '', string $title = '' ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$base64_data = preg_replace( '/^data:image\/[^;]+;base64,/', '', trim( $base64_data ) );
		$binary = base64_decode( $base64_data, true );
		if ( false === $binary || strlen( $binary ) < 100 ) {
			return new \WP_Error( 'aipb_invalid_base64_image', __( 'The generated image data is invalid.', 'promptpress-ai' ) );
		}

		$tmp_file = wp_tempnam( 'promptpress-ai-image' );
		if ( ! $tmp_file ) {
			return new \WP_Error( 'aipb_temp_file_failed', __( 'Could not create a temporary image file.', 'promptpress-ai' ) );
		}

		if ( false === file_put_contents( $tmp_file, $binary ) ) {
			@unlink( $tmp_file );
			return new \WP_Error( 'aipb_image_write_failed', __( 'Could not write the generated image.', 'promptpress-ai' ) );
		}

		$filename = sanitize_file_name( ! empty( $title ) ? sanitize_title( $title ) : 'promptpress-ai-image-' . wp_generate_password( 8, false ) ) . '.png';
		$file_array = array( 'name' => $filename, 'tmp_name' => $tmp_file );
		$attachment_title = ! empty( $title ) ? sanitize_text_field( $title ) : 'PromptPress AI Image';

		$attachment_id = media_handle_sideload( $file_array, $post_id > 0 ? $post_id : 0, $attachment_title );
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp_file );
			return new \WP_Error( 'aipb_image_import_failed', $attachment_id->get_error_message() );
		}

		if ( $alt_text ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $this->generate_alt_text( $alt_text ) );
		}

		return (int) $attachment_id;
	}


	/**
	 * Return a placeholder SVG image or null.
	 *
	 * Used as a fallback when image generation or import fails.
	 *
	 * @param string $section_type Section type to tailor the placeholder.
	 *
	 * @return string|null SVG markup or null.
	 */
	public function get_placeholder_image( string $section_type ) {
		$labels = array(
			'hero'         => __( 'Hero Image', 'promptpress-ai' ),
			'features'     => __( 'Feature Image', 'promptpress-ai' ),
			'about'        => __( 'About Image', 'promptpress-ai' ),
			'testimonials' => __( 'Testimonial Avatar', 'promptpress-ai' ),
			'pricing'      => __( 'Pricing Icon', 'promptpress-ai' ),
			'gallery'      => __( 'Gallery Image', 'promptpress-ai' ),
			'contact'      => __( 'Contact Image', 'promptpress-ai' ),
			'cta'          => __( 'CTA Image', 'promptpress-ai' ),
		);

		$label = isset( $labels[ $section_type ] ) ? $labels[ $section_type ] : __( 'Placeholder Image', 'promptpress-ai' );

		$width  = 'hero' === $section_type ? 1200 : 800;
		$height = 'hero' === $section_type ? 600 : 500;

		return sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">
				<rect width="%d" height="%d" fill="#e2e8f0"/>
				<text x="50%%" y="50%%" dominant-baseline="middle" text-anchor="middle" fill="#94a3b8" font-family="sans-serif" font-size="24">%s</text>
			</svg>',
			$width,
			$height,
			$width,
			$height,
			$width,
			$height,
			esc_html( $label )
		);
	}

	/**
	 * Check if an image URL already exists in the Media Library.
	 *
	 * @param string $url Image URL.
	 *
	 * @return int|false Attachment ID or false.
	 */
	public function avoid_duplicate( string $url ) {
		global $wpdb;

		$attachment_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
				basename( wp_parse_url( $url, PHP_URL_PATH ) )
			)
		);

		if ( $attachment_id ) {
			return (int) $attachment_id;
		}

		$attachment_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'aipb_original_url' AND meta_value = %s LIMIT 1",
				$url
			)
		);

		if ( $attachment_id ) {
			return (int) $attachment_id;
		}

		return false;
	}

	/**
	 * Generate alt text from an image description.
	 *
	 * @param string $image_description Description of the image content.
	 *
	 * @return string Sanitized alt text.
	 */
	public function generate_alt_text( string $image_description ) {
		$alt = sanitize_text_field( $image_description );

		$alt = preg_replace( '/\s+/', ' ', $alt );

		$alt = trim( $alt );

		if ( strlen( $alt ) > 125 ) {
			$alt = substr( $alt, 0, 122 ) . '...';
		}

		return $alt;
	}

	/**
	 * Generate a meaningful filename from the URL and alt text.
	 *
	 * @param string $url      Image URL.
	 * @param string $alt_text Alt text to derive the name from.
	 *
	 * @return string Filename with extension.
	 */
	private function generate_filename( string $url, string $alt_text = '' ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		$ext  = pathinfo( $path, PATHINFO_EXTENSION );

		if ( empty( $ext ) || ! in_array( strtolower( $ext ), array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif' ), true ) ) {
			$content_type = '';
			$response     = wp_remote_head( $url, array( 'timeout' => 10 ) );
			if ( ! is_wp_error( $response ) ) {
				$content_type = wp_remote_retrieve_header( $response, 'content-type' );
			}

			$mime_map = array(
				'image/jpeg' => 'jpg',
				'image/png'  => 'png',
				'image/gif'  => 'gif',
				'image/webp' => 'webp',
				'image/svg+xml' => 'svg',
				'image/avif' => 'avif',
			);

			$ext = isset( $mime_map[ $content_type ] ) ? $mime_map[ $content_type ] : 'jpg';
		}

		if ( ! empty( $alt_text ) ) {
			$slug = sanitize_title( $alt_text );
		} else {
			$slug = sanitize_title( pathinfo( $path, PATHINFO_FILENAME ) );
		}

		if ( empty( $slug ) ) {
			$slug = 'aipb-image-' . wp_generate_password( 8, false );
		}

		return $slug . '.' . $ext;
	}

	/**
	 * Attach an existing attachment to a post.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $post_id       Parent post ID.
	 *
	 * @return void
	 */
	private function attach_to_post( int $attachment_id, int $post_id ) {
		$parent = wp_get_post_parent_id( $attachment_id );

		if ( $parent === $post_id ) {
			return;
		}

		if ( $parent > 0 && $parent !== $post_id ) {
			return;
		}

		wp_update_post(
			array(
				'ID'          => $attachment_id,
				'post_parent' => $post_id,
			)
		);
	}
}
