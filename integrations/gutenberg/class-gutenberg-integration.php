<?php
/**
 * Gutenberg Integration - renders AI content as Gutenberg blocks.
 *
 * @package AIPB
 * @subpackage Integrations
 */

namespace AIPB\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Class Gutenberg_Integration
 *
 * Registers a block-editor plugin that adds an AI generation button
 * and converts structured JSON responses into valid Gutenberg block markup.
 */
class Gutenberg_Integration {

	/**
	 * Enqueue Gutenberg editor assets.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
	}

	/**
	 * Load JS and CSS only on post / page editor screens.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->base, array( 'post', 'site-editor' ), true ) ) {
			return;
		}

		$css_file = AIPB_PLUGIN_URL . 'assets/css/gutenberg-editor.css';
		if ( file_exists( AIPB_PLUGIN_DIR . 'assets/css/gutenberg-editor.css' ) ) {
			wp_enqueue_style(
				'aipb-gutenberg',
				$css_file,
				array(),
				AIPB_VERSION
			);
		}

		wp_enqueue_script(
			'aipb-gutenberg',
			AIPB_PLUGIN_URL . 'assets/js/gutenberg-integration.js',
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-i18n', 'wp-blocks' ),
			AIPB_VERSION,
			true
		);

		wp_localize_script(
			'aipb-gutenberg',
			'aipbGutenberg',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'aipb_gutenberg_nonce' ),
				'i18n'    => array(
					'buttonLabel'     => __( 'AI Generate', 'promptpress-ai' ),
					'generating'      => __( 'Generating…', 'promptpress-ai' ),
					'errorOccurred'   => __( 'An error occurred during generation.', 'promptpress-ai' ),
					'promptPlaceholder' => __( 'Describe the page you want to create…', 'promptpress-ai' ),
				),
			)
		);
	}

	/**
	 * Register a custom block category for AI-generated blocks.
	 *
	 * @param array         $categories Existing categories.
	 * @param \WP_Block_Editor_Context $context Editor context.
	 *
	 * @return array Modified categories.
	 */
	public function register_block_category( $categories, $context ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'aipb',
					'title' => __( 'PromptPress AI', 'promptpress-ai' ),
					'icon'  => 'format-aside',
				),
			)
		);
	}

	/**
	 * Convert structured AI content into Gutenberg block markup.
	 *
	 * @param array $structured_content Parsed AI response with 'sections' key.
	 *
	 * @return string Complete block markup.
	 */
	public function render_to_blocks( array $structured_content ) {
		if ( isset( $structured_content['page'] ) ) {
			$page = $structured_content['page'];
		} else {
			$page = $structured_content;
		}

		$sections = $page['sections'] ?? array();

		if ( ! is_array( $sections ) || empty( $sections ) ) {
			return '';
		}

		$blocks = array();

		$colors = $page['global_styles'] ?? $structured_content['global_styles'] ?? array();

		$default_primary   = $colors['primary_color'] ?? '#2563eb';
		$default_secondary = $colors['secondary_color'] ?? '#1e40af';
		$default_bg        = $colors['background_color'] ?? '#ffffff';
		$default_text      = $colors['text_color'] ?? '#1f2937';

		foreach ( $sections as $section ) {
			$type = $section['type'] ?? 'content';
			$blocks[] = $this->render_section( $type, $section, $default_primary, $default_secondary, $default_bg, $default_text );
		}

		return implode( "\n\n", array_filter( $blocks ) );
	}

	/**
	 * Dispatch a section to the appropriate block renderer.
	 *
	 * @param string $type           Section type.
	 * @param array  $section        Section data.
	 * @param string $primary_color  Primary brand color.
	 * @param string $secondary_color Secondary brand color.
	 * @param string $bg_color       Background color.
	 * @param string $text_color     Text color.
	 *
	 * @return string Block markup.
	 */
	private function render_section( string $type, array $section, string $primary_color, string $secondary_color, string $bg_color, string $text_color ) {
		switch ( $type ) {
			case 'hero':
				return $this->render_hero( $section, $primary_color, $secondary_color, $bg_color, $text_color );
			case 'features':
				return $this->render_features( $section, $primary_color, $secondary_color, $bg_color, $text_color );
			case 'about':
				return $this->render_about( $section, $primary_color, $secondary_color, $bg_color, $text_color );
			case 'testimonials':
				return $this->render_testimonials( $section, $primary_color, $secondary_color, $bg_color, $text_color );
			case 'pricing':
				return $this->render_pricing( $section, $primary_color, $secondary_color, $bg_color, $text_color );
			case 'faq':
				return $this->render_faq( $section, $primary_color, $secondary_color, $bg_color, $text_color );
			case 'cta':
				return $this->render_cta( $section, $primary_color, $secondary_color, $bg_color, $text_color );
			case 'gallery':
				return $this->render_gallery( $section );
			case 'contact':
				return $this->render_contact( $section, $primary_color, $secondary_color, $bg_color, $text_color );
			case 'divider':
				return $this->render_divider( $section );
			case 'spacer':
				return $this->render_spacer( $section );
			default:
				return $this->render_content( $section, $primary_color, $secondary_color, $bg_color, $text_color );
		}
	}

	/**
	 * Render a hero section as a Cover block.
	 *
	 * @param array  $section        Section data.
	 * @param string $primary_color  Primary color.
	 * @param string $secondary_color Secondary color.
	 * @param string $bg_color       Background color.
	 * @param string $text_color     Text color.
	 *
	 * @return string Cover block markup.
	 */
	private function render_hero( array $section, string $primary_color, string $secondary_color, string $bg_color, string $text_color ) {
		$heading    = $section['heading'] ?? $section['title'] ?? '';
		$subheading = $section['subheading'] ?? $section['description'] ?? '';
		$buttons    = $section['buttons'] ?? $section['cta'] ?? array();
		$image      = $section['image'] ?? '';
		$overlay    = $section['overlay_color'] ?? 'rgba(0,0,0,0.5)';

		$cover_attrs = array(
			'overlayColor' => $primary_color,
			'dimRatio'     => 50,
			'align'        => 'full',
		);

		if ( ! empty( $image ) ) {
			$cover_attrs['url'] = esc_url( $image );
		}

		$inner = '';

		if ( ! empty( $heading ) ) {
			$inner .= sprintf(
				'<!-- wp:heading {"level":1,"textAlign":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"color":{"text":"#ffffff"}}} -->' . "\n" .
				'<h1 class="has-text-align-center has-white-color has-text-color has-link-color">%s</h1>' . "\n" .
				'<!-- /wp:heading -->',
				esc_html( $heading )
			);
		}

		if ( ! empty( $subheading ) ) {
			$inner .= sprintf(
				'<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"color":{"text":"#ffffff"},"typography":{"fontSize":"20px"}}} -->' . "\n" .
				'<p class="has-text-align-center has-white-color has-text-color has-link-color" style="font-size:20px">%s</p>' . "\n" .
				'<!-- /wp:paragraph -->',
				esc_html( $subheading )
			);
		}

		if ( ! empty( $buttons ) && is_array( $buttons ) ) {
			$btn_markup = '';
			foreach ( $buttons as $button ) {
				$label = $button['label'] ?? $button['text'] ?? '';
				$url   = $button['url'] ?? $button['link'] ?? '#';
				if ( ! empty( $label ) ) {
					$btn_markup .= sprintf(
						'<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->' . "\n" .
						'<!-- wp:button {"backgroundColor":"white","textColor":"black","className":"is-style-fill"} -->' . "\n" .
						'<div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button" href="%s">%s</a></div>' . "\n" .
						'<!-- /wp:button -->' . "\n" .
						'<!-- /wp:buttons -->',
						esc_url( $url ),
						esc_html( $label )
					);
				}
			}
			$inner .= $btn_markup;
		}

		return sprintf(
			'<!-- wp:cover {"overlayColor":"black","dimRatio":50,"align":"full","style":{"spacing":{"padding":{"top":"120px","bottom":"120px","left":"20px","right":"20px"}}}} -->' . "\n" .
			'<div class="wp-block-cover alignfull">' . "\n" .
			'<span aria-hidden="true" class="wp-block-cover__background"></span>' . "\n" .
			'<div class="wp-block-cover__inner-container">%s</div>' . "\n" .
			'</div>' . "\n" .
			'<!-- /wp:cover -->',
			$inner
		);
	}

	/**
	 * Render a features section as a Group with Columns.
	 *
	 * @param array  $section        Section data.
	 * @param string $primary_color  Primary color.
	 * @param string $secondary_color Secondary color.
	 * @param string $bg_color       Background color.
	 * @param string $text_color     Text color.
	 *
	 * @return string Group + Columns block markup.
	 */
	private function render_features( array $section, string $primary_color, string $secondary_color, string $bg_color, string $text_color ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$items   = $section['items'] ?? $section['features'] ?? array();

		$inner = '';

		if ( ! empty( $heading ) ) {
			$inner .= sprintf(
				'<!-- wp:heading {"level":2,"textAlign":"center","style":{"spacing":{"margin":{"bottom":"40px"}}}} -->' . "\n" .
				'<h2 class="has-text-align-center">%s</h2>' . "\n" .
				'<!-- /wp:heading -->',
				esc_html( $heading )
			);
		}

		if ( ! empty( $items ) && is_array( $items ) ) {
			$column_count = min( count( $items ), 4 );
			$width        = round( 100 / $column_count, 2 );

			$inner .= sprintf(
				'<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"30px"}}}} -->' . "\n" .
				'<div class="wp-block-columns">'
			);

			$index = 0;
			foreach ( $items as $item ) {
				if ( $index >= 4 ) {
					break;
				}

				$icon  = $item['icon'] ?? '';
				$title = $item['heading'] ?? $item['title'] ?? '';
				$desc  = $item['description'] ?? $item['content'] ?? '';

				$column_inner = '';

				if ( ! empty( $icon ) ) {
					$column_inner .= sprintf(
						'<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"40px"}}} -->' . "\n" .
						'<p class="has-text-align-center" style="font-size:40px">%s</p>' . "\n" .
						'<!-- /wp:paragraph -->',
						esc_html( $icon )
					);
				}

				if ( ! empty( $title ) ) {
					$column_inner .= sprintf(
						'<!-- wp:heading {"level":3,"textAlign":"center","style":{"spacing":{"margin":{"bottom":"12px"}}}} -->' . "\n" .
						'<h3 class="has-text-align-center">%s</h3>' . "\n" .
						'<!-- /wp:heading -->',
						esc_html( $title )
					);
				}

				if ( ! empty( $desc ) ) {
					$column_inner .= sprintf(
						'<!-- wp:paragraph {"align":"center"} -->' . "\n" .
						'<p class="has-text-align-center">%s</p>' . "\n" .
						'<!-- /wp:paragraph -->',
						esc_html( $desc )
					);
				}

				$inner .= sprintf(
					'<!-- wp:column {"width":"%s%%"} -->' . "\n" .
					'<div class="wp-block-column">%s</div>' . "\n" .
					'<!-- /wp:column -->',
					$width,
					$column_inner
				);

				$index++;
			}

			$inner .= '</div>' . "\n" . '<!-- /wp:columns -->';
		}

		return sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"20px","right":"20px"}},"color":{"background":"%s"}}} -->' . "\n" .
			'<div class="wp-block-group has-%s-background-color has-background">%s</div>' . "\n" .
			'<!-- /wp:group -->',
			esc_attr( $bg_color ),
			esc_attr( $bg_color ),
			$inner
		);
	}

	/**
	 * Render an about section with image + text columns.
	 *
	 * @param array  $section        Section data.
	 * @param string $primary_color  Primary color.
	 * @param string $secondary_color Secondary color.
	 * @param string $bg_color       Background color.
	 * @param string $text_color     Text color.
	 *
	 * @return string Group + Columns block markup.
	 */
	private function render_about( array $section, string $primary_color, string $secondary_color, string $bg_color, string $text_color ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$content = $section['content'] ?? $section['description'] ?? '';
		$image   = $section['image'] ?? '';
		$reverse = ! empty( $section['image_position'] ) && 'right' === $section['image_position'];

		$inner = '';

		if ( ! empty( $heading ) ) {
			$inner .= sprintf(
				'<!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"bottom":"40px"}}}} -->' . "\n" .
				'<h2>%s</h2>' . "\n" .
				'<!-- /wp:heading -->',
				esc_html( $heading )
			);
		}

		$image_column  = '';
		$text_column   = '';

		if ( ! empty( $image ) ) {
			$image_column = sprintf(
				'<!-- wp:image {"sizeSlug":"large","style":{"border":{"radius":{"topLeft":"12px","topRight":"12px","bottomLeft":"12px","bottomRight":"12px"}}}} -->' . "\n" .
				'<figure class="wp-block-image has-large-size"><img src="%s" alt="" style="border-radius:12px"/></figure>' . "\n" .
				'<!-- /wp:image -->',
				esc_url( $image )
			);
		}

		$text_column = sprintf(
			'<!-- wp:group {"layout":{"type":"default"}} -->' . "\n" .
			'<div class="wp-block-group">%s</div>' . "\n" .
			'<!-- /wp:group -->',
			sprintf(
				'<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.8"}}} -->' . "\n" .
				'<p style="line-height:1.8">%s</p>' . "\n" .
				'<!-- /wp:paragraph -->',
				esc_html( $content )
			)
		);

		$first_column  = $reverse ? $text_column : $image_column;
		$second_column = $reverse ? $image_column : $text_column;

		$inner .= sprintf(
			'<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"60px"}}}} -->' . "\n" .
			'<div class="wp-block-columns">' .
			'<!-- wp:column {"width":"50%"} -->' . "\n" .
			'<div class="wp-block-column">%s</div>' . "\n" .
			'<!-- /wp:column -->' .
			'<!-- wp:column {"width":"50%"} -->' . "\n" .
			'<div class="wp-block-column">%s</div>' . "\n" .
			'<!-- /wp:column -->' .
			'</div>' . "\n" .
			'<!-- /wp:columns -->',
			$first_column,
			$second_column
		);

		return sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"20px","right":"20px"}}}} -->' . "\n" .
			'<div class="wp-block-group">%s</div>' . "\n" .
			'<!-- /wp:group -->',
			$inner
		);
	}

	/**
	 * Render testimonials as Columns with quote blocks.
	 *
	 * @param array  $section        Section data.
	 * @param string $primary_color  Primary color.
	 * @param string $secondary_color Secondary color.
	 * @param string $bg_color       Background color.
	 * @param string $text_color     Text color.
	 *
	 * @return string Group + Columns block markup.
	 */
	private function render_testimonials( array $section, string $primary_color, string $secondary_color, string $bg_color, string $text_color ) {
		$heading  = $section['heading'] ?? $section['title'] ?? '';
		$reviews  = $section['items'] ?? $section['testimonials'] ?? array();

		$inner = '';

		if ( ! empty( $heading ) ) {
			$inner .= sprintf(
				'<!-- wp:heading {"level":2,"textAlign":"center","style":{"spacing":{"margin":{"bottom":"40px"}}}} -->' . "\n" .
				'<h2 class="has-text-align-center">%s</h2>' . "\n" .
				'<!-- /wp:heading -->',
				esc_html( $heading )
			);
		}

		if ( ! empty( $reviews ) && is_array( $reviews ) ) {
			$count = min( count( $reviews ), 3 );
			$width = round( 100 / $count, 2 );

			$inner .= '<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"30px"}}}} -->' . "\n" . '<div class="wp-block-columns">';

			$index = 0;
			foreach ( $reviews as $review ) {
				if ( $index >= 3 ) {
					break;
				}

				$quote  = $review['quote'] ?? $review['content'] ?? $review['text'] ?? '';
				$author = $review['author'] ?? $review['name'] ?? '';
				$role   = $review['role'] ?? $review['title'] ?? '';

				$testimonial_inner = '';

				if ( ! empty( $quote ) ) {
					$testimonial_inner .= sprintf(
						'<!-- wp:quote {"style":{"color":{"text":"%s"}}} -->' . "\n" .
						'<blockquote class="wp-block-quote has-text-color" style="color:%s"><p>%s</p></blockquote>' . "\n" .
						'<!-- /wp:quote -->',
						esc_attr( $text_color ),
						esc_attr( $text_color ),
						esc_html( $quote )
					);
				}

				$author_line = '';
				if ( ! empty( $author ) ) {
					$author_line = '<strong>' . esc_html( $author ) . '</strong>';
				}
				if ( ! empty( $role ) ) {
					$author_line .= ' &mdash; ' . esc_html( $role );
				}

				if ( ! empty( $author_line ) ) {
					$testimonial_inner .= sprintf(
						'<!-- wp:paragraph {"style":{"typography":{"fontSize":"14px"}},"textColor":"%s"} -->' . "\n" .
						'<p class="has-%s-color has-text-color" style="font-size:14px">%s</p>' . "\n" .
						'<!-- /wp:paragraph -->',
						esc_attr( $primary_color ),
						esc_attr( $primary_color ),
						$author_line
					);
				}

				$inner .= sprintf(
					'<!-- wp:column {"width":"%s%%","style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":{"topLeft":"8px","topRight":"8px","bottomLeft":"8px","bottomRight":"8px"}},"color":{"background":"#f9fafb"}}} -->' . "\n" .
					'<div class="wp-block-column has-%s-background-color has-background">%s</div>' . "\n" .
					'<!-- /wp:column -->',
					$width,
					esc_attr( $bg_color ),
					$testimonial_inner
				);

				$index++;
			}

			$inner .= '</div>' . "\n" . '<!-- /wp:columns -->';
		}

		return sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"20px","right":"20px"}}}} -->' . "\n" .
			'<div class="wp-block-group">%s</div>' . "\n" .
			'<!-- /wp:group -->',
			$inner
		);
	}

	/**
	 * Render pricing cards as Columns.
	 *
	 * @param array  $section        Section data.
	 * @param string $primary_color  Primary color.
	 * @param string $secondary_color Secondary color.
	 * @param string $bg_color       Background color.
	 * @param string $text_color     Text color.
	 *
	 * @return string Group + Columns block markup.
	 */
	private function render_pricing( array $section, string $primary_color, string $secondary_color, string $bg_color, string $text_color ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$plans   = $section['items'] ?? $section['plans'] ?? array();

		$inner = '';

		if ( ! empty( $heading ) ) {
			$inner .= sprintf(
				'<!-- wp:heading {"level":2,"textAlign":"center","style":{"spacing":{"margin":{"bottom":"40px"}}}} -->' . "\n" .
				'<h2 class="has-text-align-center">%s</h2>' . "\n" .
				'<!-- /wp:heading -->',
				esc_html( $heading )
			);
		}

		if ( ! empty( $plans ) && is_array( $plans ) ) {
			$count = min( count( $plans ), 3 );
			$width = round( 100 / $count, 2 );

			$inner .= '<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"30px"}}}} -->' . "\n" . '<div class="wp-block-columns">';

			$index = 0;
			foreach ( $plans as $plan ) {
				if ( $index >= 3 ) {
					break;
				}

				$name     = $plan['name'] ?? $plan['title'] ?? '';
				$price    = $plan['price'] ?? '';
				$period   = $plan['period'] ?? '/mo';
				$features = $plan['features'] ?? array();
				$cta      = $plan['cta'] ?? $plan['button'] ?? '';
				$url      = $plan['url'] ?? $plan['link'] ?? '#';
				$featured = ! empty( $plan['featured'] ) || ! empty( $plan['highlighted'] );

				$card_inner = '';

				if ( ! empty( $name ) ) {
					$card_inner .= sprintf(
						'<!-- wp:heading {"level":3,"textAlign":"center","style":{"spacing":{"margin":{"bottom":"8px"}}}} -->' . "\n" .
						'<h3 class="has-text-align-center">%s</h3>' . "\n" .
						'<!-- /wp:heading -->',
						esc_html( $name )
					);
				}

				if ( ! empty( $price ) ) {
					$card_inner .= sprintf(
						'<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px","fontWeight":"700"}}} -->' . "\n" .
						'<p class="has-text-align-center" style="font-size:48px;font-weight:700">%s<span style="font-size:16px;font-weight:400">%s</span></p>' . "\n" .
						'<!-- /wp:paragraph -->',
						esc_html( $price ),
						esc_html( $period )
					);
				}

				if ( ! empty( $features ) && is_array( $features ) ) {
					$list_items = '';
					foreach ( $features as $feature ) {
						$list_items .= sprintf(
							'<li>%s</li>',
							esc_html( is_array( $feature ) ? ( $feature['text'] ?? $feature['name'] ?? '' ) : $feature )
						);
					}

					$card_inner .= sprintf(
						'<!-- wp:list {"style":{"spacing":{"blockGap":{"top":"12px"}}}} -->' . "\n" .
						'<ul>%s</ul>' . "\n" .
						'<!-- /wp:list -->',
						$list_items
					);
				}

				if ( ! empty( $cta ) ) {
					$btn_class = $featured ? 'is-style-fill' : '';
					$card_inner .= sprintf(
						'<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"24px"}}}} -->' . "\n" .
						'<!-- wp:button {"backgroundColor":"%s","className":"%s"} -->' . "\n" .
						'<div class="wp-block-button %s"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>' . "\n" .
						'<!-- /wp:button -->' . "\n" .
						'<!-- /wp:buttons -->',
						esc_attr( $featured ? $primary_color : 'white' ),
						esc_attr( $btn_class ),
						esc_attr( $btn_class ),
						esc_url( $url ),
						esc_html( $cta )
					);
				}

				$column_attrs = array(
					'width' => $width . '%',
					'style' => array(
						'spacing' => array(
							'padding' => array(
								'top' => '32px',
								'bottom' => '32px',
								'left' => '24px',
								'right' => '24px',
							),
						),
						'border' => array(
							'radius' => array(
								'topLeft' => '12px',
								'topRight' => '12px',
								'bottomLeft' => '12px',
								'bottomRight' => '12px',
							),
						),
						'color' => array( 'background' => '#ffffff' ),
					),
				);
				if ( $featured ) {
					$column_attrs['style']['border']['color']['top'] = $primary_color;
					$column_attrs['style']['border']['width']['top'] = '3px';
				}

				$inner .= sprintf(
					'<!-- wp:column %s -->' . "\n" .
					'<div class="wp-block-column" style="border-radius:12px">%s</div>' . "\n" .
					'<!-- /wp:column -->',
					wp_json_encode( $column_attrs ),
					$card_inner
				);

				$index++;
			}

			$inner .= '</div>' . "\n" . '<!-- /wp:columns -->';
		}

		return sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"20px","right":"20px"}},"color":{"background":"%s"}}} -->' . "\n" .
			'<div class="wp-block-group has-%s-background-color has-background">%s</div>' . "\n" .
			'<!-- /wp:group -->',
			esc_attr( $bg_color ),
			esc_attr( $bg_color ),
			$inner
		);
	}

	/**
	 * Render an FAQ section using headings and paragraphs.
	 *
	 * @param array  $section        Section data.
	 * @param string $primary_color  Primary color.
	 * @param string $secondary_color Secondary color.
	 * @param string $bg_color       Background color.
	 * @param string $text_color     Text color.
	 *
	 * @return string Group block markup with question/answer pairs.
	 */
	private function render_faq( array $section, string $primary_color, string $secondary_color, string $bg_color, string $text_color ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$items   = $section['items'] ?? $section['faq'] ?? array();

		$inner = '';

		if ( ! empty( $heading ) ) {
			$inner .= sprintf(
				'<!-- wp:heading {"level":2,"textAlign":"center","style":{"spacing":{"margin":{"bottom":"40px"}}}} -->' . "\n" .
				'<h2 class="has-text-align-center">%s</h2>' . "\n" .
				'<!-- /wp:heading -->',
				esc_html( $heading )
			);
		}

		if ( ! empty( $items ) && is_array( $items ) ) {
			foreach ( $items as $item ) {
				$question = $item['question'] ?? $item['heading'] ?? $item['title'] ?? '';
				$answer   = $item['answer'] ?? $item['content'] ?? $item['description'] ?? '';

				if ( ! empty( $question ) ) {
					$inner .= sprintf(
						'<!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"8px"}}}} -->' . "\n" .
						'<h3>%s</h3>' . "\n" .
						'<!-- /wp:heading -->',
						esc_html( $question )
					);
				}

				if ( ! empty( $answer ) ) {
					$inner .= sprintf(
						'<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"32px"}}}} -->' . "\n" .
						'<p>%s</p>' . "\n" .
						'<!-- /wp:paragraph -->',
						esc_html( $answer )
					);
				}
			}
		}

		return sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"20px","right":"20px"}},"color":{"background":"%s"}}} -->' . "\n" .
			'<div class="wp-block-group has-%s-background-color has-background">%s</div>' . "\n" .
			'<!-- /wp:group -->',
			esc_attr( $bg_color ),
			esc_attr( $bg_color ),
			$inner
		);
	}

	/**
	 * Render a call-to-action as a Cover block with buttons.
	 *
	 * @param array  $section        Section data.
	 * @param string $primary_color  Primary color.
	 * @param string $secondary_color Secondary color.
	 * @param string $bg_color       Background color.
	 * @param string $text_color     Text color.
	 *
	 * @return string Cover block markup.
	 */
	private function render_cta( array $section, string $primary_color, string $secondary_color, string $bg_color, string $text_color ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$content = $section['content'] ?? $section['description'] ?? '';
		$buttons = $section['buttons'] ?? $section['cta'] ?? array();

		$inner = '';

		if ( ! empty( $heading ) ) {
			$inner .= sprintf(
				'<!-- wp:heading {"level":2,"textAlign":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"color":{"text":"#ffffff"}}} -->' . "\n" .
				'<h2 class="has-text-align-center has-white-color has-text-color has-link-color">%s</h2>' . "\n" .
				'<!-- /wp:heading -->',
				esc_html( $heading )
			);
		}

		if ( ! empty( $content ) ) {
			$inner .= sprintf(
				'<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"color":{"text":"#ffffff"}}} -->' . "\n" .
				'<p class="has-text-align-center has-white-color has-text-color has-link-color">%s</p>' . "\n" .
				'<!-- /wp:paragraph -->',
				esc_html( $content )
			);
		}

		if ( ! empty( $buttons ) && is_array( $buttons ) ) {
			$btn_markup = '';
			foreach ( $buttons as $button ) {
				$label = $button['label'] ?? $button['text'] ?? '';
				$url   = $button['url'] ?? $button['link'] ?? '#';
				if ( ! empty( $label ) ) {
					$btn_markup .= sprintf(
						'<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->' . "\n" .
						'<!-- wp:button {"backgroundColor":"white","textColor":"black","className":"is-style-fill"} -->' . "\n" .
						'<div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button" href="%s">%s</a></div>' . "\n" .
						'<!-- /wp:button -->' . "\n" .
						'<!-- /wp:buttons -->',
						esc_url( $url ),
						esc_html( $label )
					);
				}
			}
			$inner .= $btn_markup;
		}

		return sprintf(
			'<!-- wp:cover {"overlayColor":"black","dimRatio":40,"align":"full","style":{"spacing":{"padding":{"top":"100px","bottom":"100px","left":"20px","right":"20px"}}}} -->' . "\n" .
			'<div class="wp-block-cover alignfull">' . "\n" .
			'<span aria-hidden="true" class="wp-block-cover__background"></span>' . "\n" .
			'<div class="wp-block-cover__inner-container">%s</div>' . "\n" .
			'</div>' . "\n" .
			'<!-- /wp:cover -->',
			$inner
		);
	}

	/**
	 * Render a gallery section.
	 *
	 * @param array $section Section data.
	 *
	 * @return string Gallery block markup.
	 */
	private function render_gallery( array $section ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$images  = $section['images'] ?? $section['items'] ?? array();
		$columns = $section['columns'] ?? 3;

		$inner = '';

		if ( ! empty( $heading ) ) {
			$inner .= sprintf(
				'<!-- wp:heading {"level":2,"textAlign":"center","style":{"spacing":{"margin":{"bottom":"40px"}}}} -->' . "\n" .
				'<h2 class="has-text-align-center">%s</h2>' . "\n" .
				'<!-- /wp:heading -->',
				esc_html( $heading )
			);
		}

		if ( ! empty( $images ) && is_array( $images ) ) {
			$ids   = array();
			$items_markup = '';

			foreach ( $images as $image ) {
				$url    = is_array( $image ) ? ( $image['url'] ?? $image['src'] ?? '' ) : $image;
				$alt    = is_array( $image ) ? ( $image['alt'] ?? $image['description'] ?? '' ) : '';
				$cap    = is_array( $image ) ? ( $image['caption'] ?? '' ) : '';

				if ( ! empty( $url ) ) {
					$items_markup .= sprintf(
						'<figure class="wp-block-image"><img src="%s" alt="%s"/></figure>',
						esc_url( $url ),
						esc_attr( $alt )
					);
				}
			}

			$inner .= sprintf(
				'<!-- wp:gallery {"columns":%d,"imageCrop":true,"linkTo":"none"} -->' . "\n" .
				'<figure class="wp-block-gallery has-%d-columns is-cropped">%s</figure>' . "\n" .
				'<!-- /wp:gallery -->',
				absint( $columns ),
				absint( $columns ),
				$items_markup
			);
		}

		return sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"20px","right":"20px"}}}} -->' . "\n" .
			'<div class="wp-block-group">%s</div>' . "\n" .
			'<!-- /wp:group -->',
			$inner
		);
	}

	/**
	 * Render a contact section.
	 *
	 * @param array  $section        Section data.
	 * @param string $primary_color  Primary color.
	 * @param string $secondary_color Secondary color.
	 * @param string $bg_color       Background color.
	 * @param string $text_color     Text color.
	 *
	 * @return string Group + Columns block markup.
	 */
	private function render_contact( array $section, string $primary_color, string $secondary_color, string $bg_color, string $text_color ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$content = $section['content'] ?? $section['description'] ?? '';
		$fields  = $section['fields'] ?? array();
		$email   = $section['email'] ?? '';
		$phone   = $section['phone'] ?? '';
		$address = $section['address'] ?? '';

		$inner = '';

		if ( ! empty( $heading ) ) {
			$inner .= sprintf(
				'<!-- wp:heading {"level":2,"textAlign":"center","style":{"spacing":{"margin":{"bottom":"40px"}}}} -->' . "\n" .
				'<h2 class="has-text-align-center">%s</h2>' . "\n" .
				'<!-- /wp:heading -->',
				esc_html( $heading )
			);
		}

		$info_column = '';

		if ( ! empty( $content ) ) {
			$info_column .= sprintf(
				'<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"24px"}}}} -->' . "\n" .
				'<p>%s</p>' . "\n" .
				'<!-- /wp:paragraph -->',
				esc_html( $content )
			);
		}

		if ( ! empty( $email ) ) {
			$info_column .= sprintf(
				'<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"8px"}}}} -->' . "\n" .
				'<p><strong>Email:</strong> <a href="mailto:%s">%s</a></p>' . "\n" .
				'<!-- /wp:paragraph -->',
				esc_attr( $email ),
				esc_html( $email )
			);
		}

		if ( ! empty( $phone ) ) {
			$info_column .= sprintf(
				'<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"8px"}}}} -->' . "\n" .
				'<p><strong>Phone:</strong> <a href="tel:%s">%s</a></p>' . "\n" .
				'<!-- /wp:paragraph -->',
				esc_attr( $phone ),
				esc_html( $phone )
			);
		}

		if ( ! empty( $address ) ) {
			$info_column .= sprintf(
				'<!-- wp:paragraph -->' . "\n" .
				'<p><strong>Address:</strong> %s</p>' . "\n" .
				'<!-- /wp:paragraph -->',
				esc_html( $address )
			);
		}

		$form_column = '';
		$input_fields = ! empty( $fields ) ? $fields : array( 'name', 'email', 'subject', 'message' );

		foreach ( $input_fields as $field ) {
			$field_name  = is_array( $field ) ? ( $field['name'] ?? '' ) : $field;
			$field_label = is_array( $field ) ? ( $field['label'] ?? ucfirst( $field_name ) ) : ucfirst( $field_name );
			$is_textarea = 'message' === $field_name || 'comments' === $field_name;

			if ( $is_textarea ) {
				$form_column .= sprintf(
					'<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"4px"}}}} -->' . "\n" .
					'<p>%s</p>' . "\n" .
					'<!-- /wp:paragraph -->' . "\n" .
					'<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"16px"}},"border":{"color":{"top":"#d1d5db","right":"#d1d5db","bottom":"#d1d5db","left":"#d1d5db"},"width":{"top":"1px","right":"1px","bottom":"1px","left":"1px"},"radius":{"topLeft":"6px","topRight":"6px","bottomLeft":"6px","bottomRight":"6px"}}}} -->' . "\n" .
					'<p style="padding:12px;border:1px solid #d1d5db;border-radius:6px;min-height:100px">&nbsp;</p>' . "\n" .
					'<!-- /wp:paragraph -->',
					esc_html( $field_label )
				);
			} else {
				$form_column .= sprintf(
					'<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"4px"}}}} -->' . "\n" .
					'<p>%s</p>' . "\n" .
					'<!-- /wp:paragraph -->' . "\n" .
					'<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"16px"}},"border":{"color":{"top":"#d1d5db","right":"#d1d5db","bottom":"#d1d5db","left":"#d1d5db"},"width":{"top":"1px","right":"1px","bottom":"1px","left":"1px"},"radius":{"topLeft":"6px","topRight":"6px","bottomLeft":"6px","bottomRight":"6px"}}}} -->' . "\n" .
					'<p style="padding:12px;border:1px solid #d1d5db;border-radius:6px">&nbsp;</p>' . "\n" .
					'<!-- /wp:paragraph -->',
					esc_html( $field_label )
				);
			}
		}

		$form_column .= sprintf(
			'<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"flex-start"}} -->' . "\n" .
			'<!-- wp:button {"backgroundColor":"%s","textColor":"white"} -->' . "\n" .
			'<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-%s-background-color has-text-color has-background wp-element-button">Send Message</a></div>' . "\n" .
			'<!-- /wp:button -->' . "\n" .
			'<!-- /wp:buttons -->',
			esc_attr( $primary_color ),
			esc_attr( $primary_color )
		);

		$inner .= sprintf(
			'<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"60px"}}}} -->' . "\n" .
			'<div class="wp-block-columns">' .
			'<!-- wp:column {"width":"50%"} -->' . "\n" .
			'<div class="wp-block-column">%s</div>' . "\n" .
			'<!-- /wp:column -->' .
			'<!-- wp:column {"width":"50%"} -->' . "\n" .
			'<div class="wp-block-column">%s</div>' . "\n" .
			'<!-- /wp:column -->' .
			'</div>' . "\n" .
			'<!-- /wp:columns -->',
			$info_column,
			$form_column
		);

		return sprintf(
			'<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"20px","right":"20px"}},"color":{"background":"%s"}}} -->' . "\n" .
			'<div class="wp-block-group has-%s-background-color has-background">%s</div>' . "\n" .
			'<!-- /wp:group -->',
			esc_attr( $bg_color ),
			esc_attr( $bg_color ),
			$inner
		);
	}

	/**
	 * Render a divider / separator block.
	 *
	 * @param array $section Section data.
	 *
	 * @return string Separator block markup.
	 */
	private function render_divider( array $section ) {
		$style = $section['style'] ?? 'wide';

		$separator_style = 'wide' === $style ? 'is-style-wide' : ( 'dots' === $style ? 'is-style-dots' : '' );

		return sprintf(
			'<!-- wp:separator {"className":"%s","style":{"spacing":{"margin":{"top":"40px","bottom":"40px"}}}} -->' . "\n" .
			'<hr class="wp-block-separator %s"/>' . "\n" .
			'<!-- /wp:separator -->',
			esc_attr( $separator_style ),
			esc_attr( $separator_style )
		);
	}

	/**
	 * Render a spacer block.
	 *
	 * @param array $section Section data.
	 *
	 * @return string Spacer block markup.
	 */
	private function render_spacer( array $section ) {
		$height = $section['height'] ?? 60;

		return sprintf(
			'<!-- wp:spacer {"height":"%dpx","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->' . "\n" .
			'<div style="height:%dpx" aria-hidden="true" class="wp-block-spacer"></div>' . "\n" .
			'<!-- /wp:spacer -->',
			absint( $height ),
			absint( $height )
		);
	}

	/**
	 * Render generic content as Paragraph/Heading blocks.
	 *
	 * @param array  $section        Section data.
	 * @param string $primary_color  Primary color.
	 * @param string $secondary_color Secondary color.
	 * @param string $bg_color       Background color.
	 * @param string $text_color     Text color.
	 *
	 * @return string Block markup.
	 */
	private function render_content( array $section, string $primary_color, string $secondary_color, string $bg_color, string $text_color ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$content = $section['content'] ?? $section['description'] ?? '';
		$items   = $section['items'] ?? array();
		$level   = isset( $section['level'] ) ? absint( $section['level'] ) : 2;

		$inner = '';

		if ( ! empty( $heading ) ) {
			$inner .= sprintf(
				'<!-- wp:heading {"level":%d,"style":{"spacing":{"margin":{"bottom":"20px"}}}} -->' . "\n" .
				'<h%d>%s</h%d>' . "\n" .
				'<!-- /wp:heading -->',
				$level,
				$level,
				esc_html( $heading ),
				$level
			);
		}

		if ( ! empty( $content ) ) {
			$inner .= sprintf(
				'<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"16px"}},"typography":{"lineHeight":"1.8"}}} -->' . "\n" .
				'<p style="line-height:1.8">%s</p>' . "\n" .
				'<!-- /wp:paragraph -->',
				esc_html( $content )
			);
		}

		if ( ! empty( $items ) && is_array( $items ) ) {
			$list_items = '';
			foreach ( $items as $item ) {
				$item_text = is_array( $item ) ? ( $item['text'] ?? $item['content'] ?? '' ) : $item;
				if ( ! empty( $item_text ) ) {
					$list_items .= sprintf( '<li>%s</li>', esc_html( $item_text ) );
				}
			}

			if ( ! empty( $list_items ) ) {
				$inner .= sprintf(
					'<!-- wp:list {"style":{"spacing":{"blockGap":{"top":"8px"}}}} -->' . "\n" .
					'<ul>%s</ul>' . "\n" .
					'<!-- /wp:list -->',
					$list_items
				);
			}
		}

		return $inner;
	}
}
