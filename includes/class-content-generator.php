<?php
/**
 * Content Generator - orchestrates AI content creation and rendering.
 *
 * @package AIPB
 */

namespace AIPB;

defined( 'ABSPATH' ) || exit;

/**
 * Class Content_Generator
 *
 * Converts structured AI responses into Gutenberg or Elementor output,
 * creates WordPress posts, and coordinates the full generation pipeline.
 */
class Content_Generator {

	/**
	 * Render structured content for the chosen editor.
	 *
	 * @param array  $structured_content Parsed AI response.
	 * @param string $editor_type        'gutenberg' or 'elementor'.
	 *
	 * @return string|\WP_Error Rendered markup or data array on success.
	 */
	public function generate( array $structured_content, string $editor_type = 'gutenberg' ) {
		if ( 'elementor' === $editor_type ) {
			require_once AIPB_PLUGIN_DIR . 'integrations/elementor/class-elementor-integration.php';
			$renderer = new \AIPB\Integrations\Elementor_Integration();
			return $renderer->render_to_elementor( $structured_content );
		}

		require_once AIPB_PLUGIN_DIR . 'integrations/gutenberg/class-gutenberg-integration.php';
		$renderer = new \AIPB\Integrations\Gutenberg_Integration();
		return $renderer->render_to_blocks( $structured_content );
	}

	/**
	 * Create a WordPress post with the generated content.
	 *
	 * @param string $title     Post title.
	 * @param string $content   Post content (raw HTML or block markup).
	 * @param string $post_type Post type slug.
	 * @param string $status    Post status (draft, publish, pending).
	 * @param array  $meta      Optional meta fields to save.
	 *
	 * @return int|\WP_Error Post ID on success.
	 */
	public function create_post( string $title, string $content, string $post_type = 'page', string $status = 'draft', array $meta = array() ) {
		$post_data = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_type'    => $post_type,
			'post_status'  => $status,
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, 'aipb_generated', 'true' );

		if ( ! empty( $meta['seo'] ) && is_array( $meta['seo'] ) ) {
			$seo_manager = new SEO_Manager();
			$seo_manager->save_seo_data( $post_id, $meta['seo'] );
		}

		if ( ! empty( $meta['slug'] ) ) {
			wp_update_post(
				array(
					'ID'        => $post_id,
					'post_name' => sanitize_title( $meta['slug'] ),
				)
			);
		}

		/**
		 * Fires after an AI-generated post has been created.
		 *
		 * @param int    $post_id The new post ID.
		 * @param array  $meta    Extra metadata passed in.
		 * @param string $content The rendered content.
		 */
		do_action( 'aipb_post_created', $post_id, $meta, $content );

		return $post_id;
	}

	/**
	 * Generate a complete page from a natural-language prompt.
	 *
	 * @param string $prompt User prompt describing the desired page.
	 * @param array  $args   {
	 *     @type string $editor_type  Gutenberg or Elementor. Default 'gutenberg'.
	 *     @type string $post_type    Post type. Default 'page'.
	 *     @type string $status       Post status. Default 'draft'.
	 *     @type string $title        Override title. Auto-generated when empty.
	 *     @type string $provider     AI provider slug. Default from settings.
	 *     @type string $model        Model identifier. Default from settings.
	 *     @type int    $max_tokens   Token limit. Default from settings.
	 *     @type float  $temperature  Sampling temperature. Default from settings.
	 * }
	 *
	 * @return array|\WP_Error { post_id, content, status } on success.
	 */

	/**
	 * Create a WordPress post from already structured content.
	 *
	 * This is used by Local Demo Mode and future non-AI content sources.
	 *
	 * @param string $prompt Original prompt for history.
	 * @param array  $structured_content Structured page data.
	 * @param array  $args Generation options.
	 * @param bool   $demo Whether this is a local demo generation.
	 * @return array|\WP_Error
	 */
	public function generate_from_structured_content( string $prompt, array $structured_content, array $args = array(), bool $demo = false ) {
		$defaults = array(
			'editor_type'  => 'gutenberg',
			'post_type'    => 'page',
			'status'       => 'draft',
			'title'        => '',
			'image_source' => 'none',
			'language'     => 'en',
			'tone'         => 'professional',
			'design_style' => 'modern',
			'seo_optimize' => true,
		);

		$args = wp_parse_args( $args, $defaults );

		$generated_attachment_ids = array();

		if ( ! $demo && 'ai' === $args['image_source'] ) {
			$generated_attachment_ids = $this->prepare_ai_images( $structured_content );
		}

		$content = $this->generate( $structured_content, $args['editor_type'] );

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$is_elementor = 'elementor' === $args['editor_type'];
		$title = ! empty( $args['title'] ) ? $args['title'] : $this->extract_title( $structured_content );

		$page = isset( $structured_content['page'] ) && is_array( $structured_content['page'] )
			? $structured_content['page']
			: $structured_content;

		$seo_data = array(
			'meta_title'       => $page['meta_title'] ?? '',
			'meta_description' => $page['meta_description'] ?? '',
			'focus_keyword'    => $page['focus_keyword'] ?? '',
			'slug'             => $page['slug'] ?? '',
		);

		if ( empty( $args['seo_optimize'] ) ) {
			$seo_data = array();
		}

		$post_content = $is_elementor ? '' : $content;

		$post_id = $this->create_post(
			$title,
			$post_content,
			$args['post_type'],
			$args['status'],
			array(
				'seo'  => $seo_data,
				'slug' => $page['slug'] ?? '',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( $is_elementor && is_array( $content ) ) {
			require_once AIPB_PLUGIN_DIR . 'integrations/elementor/class-elementor-integration.php';
			$elementor = new \AIPB\Integrations\Elementor_Integration();
			$save_result = $elementor->save_elementor_document( $post_id, $content );

			if ( is_wp_error( $save_result ) ) {
				wp_delete_post( $post_id, true );
				return $save_result;
			}
		}

		foreach ( $generated_attachment_ids as $attachment_id ) {
			if ( $attachment_id > 0 ) {
				wp_update_post(
					array(
						'ID'          => $attachment_id,
						'post_parent' => $post_id,
					)
				);
			}
		}

		$provider = $demo ? 'local-demo' : AI_Manager::instance()->get_last_provider();
		$model    = $demo ? 'local-demo' : AI_Manager::instance()->get_last_model();
		$tokens   = $demo ? 0 : AI_Manager::instance()->get_last_tokens();

		$this->log_generation(
			$prompt,
			$post_id,
			$args['post_type'],
			$args['editor_type'],
			$provider,
			$model,
			$args['status'],
			$tokens
		);

		return array(
			'post_id'         => $post_id,
			'content'         => $is_elementor ? '' : $content,
			'elementor_data'  => $is_elementor ? $content : array(),
			'post_url'        => get_permalink( $post_id ),
			'post_title'      => $title,
			'content_preview' => $is_elementor
				? __( 'Elementor demo document generated.', 'promptpress-ai' )
				: wp_trim_words( wp_strip_all_tags( $content ), 55 ),
			'status'          => $args['status'],
			'demo_mode'       => $demo,
		);
	}

	public function generate_from_prompt( string $prompt, array $args = array() ) {
		$defaults = array(
			'editor_type'  => 'gutenberg',
			'post_type'    => 'page',
			'status'       => 'draft',
			'title'        => '',
			'provider'     => '',
			'model'        => '',
			'max_tokens'   => 0,
			'temperature'  => 0,
			'image_source' => 'none',
			'language'     => 'en',
			'tone'         => 'professional',
			'design_style' => 'modern',
			'seo_optimize' => true,
		);

		$args = wp_parse_args( $args, $defaults );

		$ai_manager = AI_Manager::instance();

		$brand_settings = get_option( 'aipb_brand_settings', array() );

		$full_prompt = $ai_manager->build_generation_prompt(
			$prompt,
			$args['post_type'],
			$args['editor_type'],
			$args['language'],
			$args['tone'],
			$args['design_style'],
			$brand_settings
		);

		$structured_content = $ai_manager->generate_content( $full_prompt );

		if ( is_wp_error( $structured_content ) ) {
			return $structured_content;
		}

		$generated_attachment_ids = array();

		if ( 'ai' === $args['image_source'] ) {
			$generated_attachment_ids = $this->prepare_ai_images( $structured_content );
		}

		$content = $this->generate( $structured_content, $args['editor_type'] );

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$is_elementor = 'elementor' === $args['editor_type'];

		$title = ! empty( $args['title'] )
			? $args['title']
			: $this->extract_title( $structured_content );

		$seo_data = array();

		if ( isset( $structured_content['page'] ) ) {
			$page = $structured_content['page'];
		} else {
			$page = $structured_content;
		}

		$seo_data = array(
			'meta_title'       => $page['meta_title'] ?? '',
			'meta_description' => $page['meta_description'] ?? '',
			'focus_keyword'    => $page['focus_keyword'] ?? '',
			'slug'             => $page['slug'] ?? '',
		);

		// Elementor stores the editable document in _elementor_data rather than
		// post_content. Create the post first, then persist native Elementor data.
		$post_content = $is_elementor ? '' : $content;

		if ( empty( $args['seo_optimize'] ) ) {
			$seo_data = array();
		}

		$post_id = $this->create_post(
			$title,
			$post_content,
			$args['post_type'],
			$args['status'],
			array( 'seo' => $seo_data, 'slug' => $page['slug'] ?? '' )
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( $is_elementor && is_array( $content ) ) {
			require_once AIPB_PLUGIN_DIR . 'integrations/elementor/class-elementor-integration.php';
			$elementor = new \AIPB\Integrations\Elementor_Integration();
			$save_result = $elementor->save_elementor_document( $post_id, $content );

			if ( is_wp_error( $save_result ) ) {
				wp_delete_post( $post_id, true );
				return $save_result;
			}
		}

		foreach ( $generated_attachment_ids as $attachment_id ) {
			if ( $attachment_id > 0 ) {
				wp_update_post(
					array(
						'ID'          => $attachment_id,
						'post_parent' => $post_id,
					)
				);
			}
		}

		$this->log_generation(
			$prompt,
			$post_id,
			$args['post_type'],
			$args['editor_type'],
			$ai_manager->get_last_provider(),
			$ai_manager->get_last_model(),
			$args['status'],
			$ai_manager->get_last_tokens()
		);

		return array(
			'post_id'        => $post_id,
			'content'        => $is_elementor ? '' : $content,
			'elementor_data' => $is_elementor ? $content : array(),
			'post_url'       => get_permalink( $post_id ),
			'post_title'     => $title,
			'content_preview' => $is_elementor ? __( 'Elementor document generated.', 'promptpress-ai' ) : wp_trim_words( wp_strip_all_tags( $content ), 55 ),
			'status'         => $args['status'],
		);
	}

	/**
	 * Generate and import a small number of requested section images.
	 *
	 * Images are generated only when the caller explicitly selects the AI
	 * image source and the model returned an image_prompt for a section.
	 *
	 * @param array $structured_content Structured AI response, passed by reference.
	 * @return array Attachment IDs created during generation.
	 */
	private function prepare_ai_images( array &$structured_content ) {
		$page = isset( $structured_content['page'] ) && is_array( $structured_content['page'] )
			? $structured_content['page']
			: $structured_content;

		if ( empty( $page['sections'] ) || ! is_array( $page['sections'] ) ) {
			return array();
		}

		$image_manager = new Image_Manager();
		$attachment_ids = array();
		$generated_count = 0;
		$max_images = 3;

		foreach ( $page['sections'] as $index => &$section ) {
			if ( $generated_count >= $max_images || ! is_array( $section ) ) {
				break;
			}

			$image_prompt = isset( $section['image_prompt'] ) ? trim( (string) $section['image_prompt'] ) : '';

			if ( '' === $image_prompt || ! in_array( $section['type'] ?? '', array( 'hero', 'about', 'gallery', 'cta' ), true ) ) {
				continue;
			}

			$result = $image_manager->generate_and_import( $image_prompt, 0 );

			if ( is_wp_error( $result ) ) {
				// Image failure must not prevent the page itself from being created.
				continue;
			}

			$attachment_id = (int) $result;
			$image_url = wp_get_attachment_url( $attachment_id );

			if ( ! $image_url ) {
				continue;
			}

			$section['image'] = esc_url_raw( $image_url );
			$section['image_alt'] = $image_manager->generate_alt_text( $section['heading'] ?? $image_prompt );
			$attachment_ids[] = $attachment_id;
			$generated_count++;
		}
		unset( $section );

		if ( isset( $structured_content['page'] ) ) {
			$structured_content['page']['sections'] = $page['sections'];
		} else {
			$structured_content['sections'] = $page['sections'];
		}

		return $attachment_ids;
	}

	/**
	 * Modify an existing post with new AI-generated content.
	 *
	 * @param int    $post_id     Post to edit.
	 * @param string $instructions Natural-language edit instructions.
	 * @param string $editor_type  Target editor format.
	 *
	 * @return array|\WP_Error { post_id, content, status } on success.
	 */
	public function edit_existing_post( int $post_id, string $instructions, string $editor_type = 'gutenberg' ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error(
				'aipb_post_not_found',
				__( 'The specified post could not be found.', 'promptpress-ai' )
			);
		}

		$ai_manager = AI_Manager::instance();

		$edit_prompt = sprintf(
			"Modify the following page content based on these instructions: %s\n\nCurrent content:\n%s",
			$instructions,
			wp_strip_all_tags( $post->post_content )
		);

		$structured_content = $ai_manager->generate_content( $edit_prompt );

		if ( is_wp_error( $structured_content ) ) {
			return $structured_content;
		}

		$content = $this->generate( $structured_content, $editor_type );

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$update_result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			),
			true
		);

		if ( is_wp_error( $update_result ) ) {
			return $update_result;
		}

		return array(
			'post_id'  => $post_id,
			'content'  => $content,
			'status'   => $post->post_status,
		);
	}

	/**
	 * Log a generation record to the history table.
	 *
	 * @param string $prompt      The original prompt.
	 * @param int    $post_id     Created post ID.
	 * @param string $post_type   Post type.
	 * @param string $editor_type Editor used.
	 * @param string $provider    AI provider slug.
	 * @param string $model       Model used.
	 * @param string $status      Generation status.
	 * @param int    $tokens      Tokens consumed.
	 *
	 * @return void
	 */
	private function log_generation( string $prompt, int $post_id, string $post_type, string $editor_type, string $provider, string $model, string $status, int $tokens ) {
		require_once AIPB_PLUGIN_DIR . 'includes/class-generation-history.php';
		$history = new Generation_History();
		$history->log_generation(
			array(
				'prompt'       => $prompt,
				'post_id'      => $post_id,
				'post_type'    => $post_type,
				'editor_type'  => $editor_type,
				'ai_provider'  => $provider,
				'model'        => $model,
				'status'       => $status,
				'tokens_used'  => $tokens,
			)
		);
	}

	/**
	 * Extract a page title from the structured content.
	 *
	 * @param array $structured_content AI response array.
	 *
	 * @return string Extracted or fallback title.
	 */
	private function extract_title( array $structured_content ) {
		if ( isset( $structured_content['page'] ) ) {
			$page = $structured_content['page'];
		} else {
			$page = $structured_content;
		}

		if ( ! empty( $page['title'] ) ) {
			return sanitize_text_field( $page['title'] );
		}

		if ( ! empty( $page['sections'] ) && is_array( $page['sections'] ) ) {
			foreach ( $page['sections'] as $section ) {
				if ( ! empty( $section['heading'] ) ) {
					return sanitize_text_field( $section['heading'] );
				}
				if ( ! empty( $section['title'] ) ) {
					return sanitize_text_field( $section['title'] );
				}
			}
		}

		return __( 'AI Generated Page', 'promptpress-ai' );
	}
}
