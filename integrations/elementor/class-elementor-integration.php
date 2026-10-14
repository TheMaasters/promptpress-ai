<?php
/**
 * Elementor Integration - renders AI content as Elementor document data.
 *
 * @package AIPB
 * @subpackage Integrations
 */

namespace AIPB\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Class Elementor_Integration
 *
 * Registers an Elementor panel tab for AI actions and converts
 * structured JSON responses into Elementor's native data format
 * using the container system.
 */
class Elementor_Integration {

	/**
	 * Elementor internal element ID counter.
	 *
	 * @var int
	 */
	private $element_id_counter = 0;

	/**
	 * Initialize the integration.
	 *
	 * @param \Elementor\Controls_Registry $controls_manager Controls manager.
	 *
	 * @return void
	 */
	public function init( $controls_manager = null ) {
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'register_widget_script' ), 5 );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_widget_script' ), 20 );
		add_action( 'elementor/documents/prepare_save', array( $this, 'on_document_save' ) );
	}

	/**
	 * Register the PromptPress AI widget in the Elementor widget panel.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		$widget_file = AIPB_PLUGIN_DIR . 'integrations/elementor/class-promptpress-ai-widget.php';

		if ( file_exists( $widget_file ) ) {
			require_once $widget_file;
			$widgets_manager->register( new \AIPB\Integrations\Elementor\PromptPress_AI_Widget() );
		}
	}

	/**
	 * Register the PromptPress AI Elementor category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'aipb',
			array(
				'title' => esc_html__( 'PromptPress AI', 'promptpress-ai' ),
				'icon'  => 'eicon-magic-wand',
			)
		);
	}

	/**
	 * Register the widget script. Elementor loads it only when the widget is used.
	 *
	 * @return void
	 */
	public function register_widget_script() {
		if ( ! wp_script_is( 'aipb-elementor-widget', 'registered' ) ) {
			wp_register_script(
				'aipb-elementor-widget',
				AIPB_PLUGIN_URL . 'assets/js/elementor-widget.js',
				array( 'jquery' ),
				AIPB_VERSION,
				true
			);
		}
	}

	/**
	 * Enqueue admin JS for the Elementor AI panel.
	 *
	 * @return void
	 */
	public function enqueue_editor_scripts() {
		wp_enqueue_script(
			'aipb-elementor',
			AIPB_PLUGIN_URL . 'assets/js/elementor-integration.js',
			array( 'elementor-editor', 'jquery' ),
			AIPB_VERSION,
			true
		);

		wp_localize_script(
			'aipb-elementor',
			'aipbElementor',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'apiUrl'  => rest_url( 'promptpress-ai/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'tabTitle'      => __( 'PromptPress AI', 'promptpress-ai' ),
					'generateBtn'   => __( 'Generate Page', 'promptpress-ai' ),
					'generating'    => __( 'Generating…', 'promptpress-ai' ),
					'placeholder'   => __( 'Describe the page you want to create…', 'promptpress-ai' ),
				),
			)
		);
	}

	/**
	 * Enqueue minimal frontend styles for AI-generated Elementor pages.
	 *
	 * @return void
	 */
	public function enqueue_frontend_styles() {
		$css_file = AIPB_PLUGIN_URL . 'assets/css/elementor-frontend.css';
		if ( file_exists( AIPB_PLUGIN_DIR . 'assets/css/elementor-frontend.css' ) ) {
			wp_enqueue_style(
				'aipb-elementor-frontend',
				$css_file,
				array(),
				AIPB_VERSION
			);
		}
	}

	/**
	 * Hook into Elementor document save to inject AI content.
	 *
	 * @param \Elementor\Document $document The document being saved.
	 *
	 * @return void
	 */
	public function on_document_save( $document ) {
		$post_id = $document->get_main_id();

		if ( ! $post_id ) {
			return;
		}

		$ai_content = get_post_meta( $post_id, 'aipb_pending_elementor_data', true );

		if ( ! empty( $ai_content ) && is_array( $ai_content ) ) {
			$this->save_elementor_document( $post_id, $ai_content );
			delete_post_meta( $post_id, 'aipb_pending_elementor_data' );
		}
	}

	/**
	 * Convert structured AI content into an Elementor document data array.
	 *
	 * @param array $structured_content Parsed AI response with 'sections' key.
	 *
	 * @return array Elementor-compatible document data.
	 */
	public function render_to_elementor( array $structured_content ) {
		$this->element_id_counter = 0;

		if ( isset( $structured_content['page'] ) ) {
			$page = $structured_content['page'];
		} else {
			$page = $structured_content;
		}

		$sections = $page['sections'] ?? array();

		if ( ! is_array( $sections ) ) {
			$sections = array();
		}

		$colors   = $page['global_styles'] ?? $structured_content['global_styles'] ?? array();
		$elements = array();

		foreach ( $sections as $section ) {
			$type     = $section['type'] ?? 'content';
			$element  = $this->render_section( $type, $section, $colors );
			if ( ! empty( $element ) ) {
				$elements[] = $element;
			}
		}

		return $elements;
	}

	/**
	 * Get a unique element ID for Elementor elements.
	 *
	 * @return string
	 */
	private function get_unique_id() {
		$this->element_id_counter++;
		return 'aipb' . str_pad( $this->element_id_counter, 4, '0', STR_PAD_LEFT ) . wp_generate_password( 6, false );
	}

	/**
	 * Dispatch a section to the appropriate Elementor element renderer.
	 *
	 * @param string $type    Section type.
	 * @param array  $section Section data.
	 * @param array  $colors  Global color palette.
	 *
	 * @return array|null Elementor element data.
	 */
	private function render_section( string $type, array $section, array $colors ) {
		switch ( $type ) {
			case 'hero':
				return $this->render_hero( $section, $colors );
			case 'features':
				return $this->render_features( $section, $colors );
			case 'about':
				return $this->render_about( $section, $colors );
			case 'testimonials':
				return $this->render_testimonials( $section, $colors );
			case 'pricing':
				return $this->render_pricing( $section, $colors );
			case 'faq':
				return $this->render_faq( $section, $colors );
			case 'cta':
				return $this->render_cta( $section, $colors );
			case 'gallery':
				return $this->render_gallery( $section, $colors );
			case 'contact':
				return $this->render_contact( $section, $colors );
			case 'divider':
				return $this->render_divider( $section, $colors );
			case 'spacer':
				return $this->render_spacer( $section, $colors );
			default:
				return $this->render_content( $section, $colors );
		}
	}

	/**
	 * Build a top-level Elementor container element.
	 *
	 * @param array $children Child elements.
	 * @param array $settings Container settings.
	 *
	 * @return array Elementor container element.
	 */
	private function build_container( array $children = array(), array $settings = array() ) {
		$default_settings = array(
			'layout'          => 'boxed',
			'content_width'   => 1140,
			'flex_direction'  => 'column',
			'padding'         => array(
				'top'    => '80',
				'right'  => '20',
				'bottom' => '80',
				'left'   => '20',
				'unit'   => 'px',
			),
		);

		$settings = wp_parse_args( $settings, $default_settings );

		return array(
			'id'                  => $this->get_unique_id(),
			'elType'              => 'container',
			'widgetType'          => '',
			'settings'            => $settings,
			'elements'            => $children,
			'isInner'             => false,
		);
	}

	/**
	 * Build a nested Elementor container element (inner container).
	 *
	 * @param array $children Child elements.
	 * @param array $settings Container settings.
	 *
	 * @return array Inner container element.
	 */
	private function build_inner_container( array $children = array(), array $settings = array() ) {
		$default_settings = array(
			'layout'         => 'boxed',
			'content_width'  => 'full',
			'flex_direction' => 'column',
		);

		$settings = wp_parse_args( $settings, $default_settings );

		return array(
			'id'         => $this->get_unique_id(),
			'elType'     => 'container',
			'widgetType' => '',
			'settings'   => $settings,
			'elements'   => $children,
			'isInner'    => true,
		);
	}

	/**
	 * Build a Heading widget element.
	 *
	 * @param string $text     Heading text.
	 * @param string $tag      HTML tag.
	 * @param array  $settings Additional settings overrides.
	 *
	 * @return array Elementor widget element.
	 */
	private function build_heading( string $text, string $tag = 'h2', array $settings = array() ) {
		$default_settings = array(
			'title'    => $text,
			'header_size' => $tag,
			'align'    => 'center',
			'typography_typography' => 'custom',
			'typography_font_size' => array(
				'unit'  => 'px',
				'size'  => $this->get_default_font_size( $tag ),
			),
		);

		$settings = wp_parse_args( $settings, $default_settings );

		return array(
			'id'         => $this->get_unique_id(),
			'elType'     => 'widget',
			'widgetType' => 'heading',
			'settings'   => $settings,
			'elements'   => array(),
			'isInner'    => false,
		);
	}

	/**
	 * Build a Text Editor widget element.
	 *
	 * @param string $text     Content text.
	 * @param array  $settings Additional settings overrides.
	 *
	 * @return array Elementor widget element.
	 */
	private function build_text_editor( string $text, array $settings = array() ) {
		$default_settings = array(
			'editor' => $text,
			'align'  => 'center',
		);

		$settings = wp_parse_args( $settings, $default_settings );

		return array(
			'id'         => $this->get_unique_id(),
			'elType'     => 'widget',
			'widgetType' => 'text-editor',
			'settings'   => $settings,
			'elements'   => array(),
			'isInner'    => false,
		);
	}

	/**
	 * Build a Button widget element.
	 *
	 * @param string $label    Button text.
	 * @param string $url      Button URL.
	 * @param array  $settings Additional settings overrides.
	 *
	 * @return array Elementor widget element.
	 */
	private function build_button( string $label, string $url = '#', array $settings = array() ) {
		$default_settings = array(
			'text'             => $label,
			'link'             => array( 'url' => $url ),
			'align'            => 'center',
			'button_type'      => 'info',
			'typography_typography' => 'custom',
			'border_radius'    => array(
				'unit'  => 'px',
				'top'   => '6',
				'right' => '6',
				'bottom'=> '6',
				'left'  => '6',
				'isLinked' => true,
			),
		);

		$settings = wp_parse_args( $settings, $default_settings );

		return array(
			'id'         => $this->get_unique_id(),
			'elType'     => 'widget',
			'widgetType' => 'button',
			'settings'   => $settings,
			'elements'   => array(),
			'isInner'    => false,
		);
	}

	/**
	 * Get a default font size for an HTML heading tag.
	 *
	 * @param string $tag Heading tag.
	 *
	 * @return int Font size in pixels.
	 */
	private function get_default_font_size( string $tag ) {
		$sizes = array(
			'h1' => 48,
			'h2' => 36,
			'h3' => 28,
			'h4' => 24,
			'h5' => 20,
			'h6' => 18,
		);

		return isset( $sizes[ $tag ] ) ? $sizes[ $tag ] : 36;
	}

	/**
	 * Render a hero section.
	 *
	 * @param array $section Section data.
		* @param array $colors  Global color palette.
	 *
	 * @return array Elementor container element.
	 */
	private function render_hero( array $section, array $colors ) {
		$heading    = $section['heading'] ?? $section['title'] ?? '';
		$subheading = $section['subheading'] ?? $section['description'] ?? '';
		$buttons    = $section['buttons'] ?? $section['cta'] ?? array();
		$image      = $section['image'] ?? '';
		$overlay    = $section['overlay_color'] ?? 'rgba(0,0,0,0.5)';

		$settings = array(
			'layout'          => 'boxed',
			'content_width'   => 1140,
			'flex_direction'  => 'column',
			'justify_content' => 'center',
			'align_items'     => 'center',
			'padding'         => array(
				'top'    => '150',
				'right'  => '20',
				'bottom' => '150',
				'left'   => '20',
				'unit'   => 'px',
			),
			'background_background'    => 'classic',
			'background_color'          => $colors['primary'] ?? '#2563eb',
		);

		if ( ! empty( $image ) ) {
			$settings['background_background'] = 'classic';
			$settings['background_image']      = array( 'url' => $image );
			$settings['background_overlay_background'] = 'classic';
			$settings['background_overlay_color'] = $overlay;
		}

		$children = array();

		if ( ! empty( $heading ) ) {
			$children[] = $this->build_heading( $heading, 'h1', array(
				'align'                           => 'center',
				'title_color'                     => '#ffffff',
				'typography_font_size'            => array( 'unit' => 'px', 'size' => 56 ),
				'typography_font_size_tablet'     => array( 'unit' => 'px', 'size' => 40 ),
				'_margin_bottom'                  => array( 'unit' => 'px', 'size' => 16, 'unit' => 'px' ),
			) );
		}

		if ( ! empty( $subheading ) ) {
			$children[] = $this->build_text_editor( $subheading, array(
				'align'            => 'center',
				'text_color'       => '#ffffff',
				'typography_font_size' => array( 'unit' => 'px', 'size' => 20 ),
			) );
		}

		if ( ! empty( $buttons ) && is_array( $buttons ) ) {
			$btn_widgets = array();
			foreach ( $buttons as $button ) {
				$label = $button['label'] ?? $button['text'] ?? '';
				$url   = $button['url'] ?? $button['link'] ?? '#';
				if ( ! empty( $label ) ) {
					$btn_widgets[] = $this->build_button( $label, $url, array(
						'align'              => 'center',
						'button_text_color'  => '#000000',
						'background_color'   => '#ffffff',
					) );
				}
			}

			if ( ! empty( $btn_widgets ) ) {
				$children[] = $this->build_inner_container( $btn_widgets, array(
					'layout'         => 'full',
					'flex_direction' => 'row',
					'justify_content' => 'center',
					'flex_gap'       => array( 'unit' => 'px', 'size' => 16 ),
				) );
			}
		}

		return $this->build_container( $children, $settings );
	}

	/**
	 * Render a features section.
	 *
	 * @param array $section Section data.
	 * @param array $colors  Global color palette.
	 *
	 * @return array Elementor container element.
	 */
	private function render_features( array $section, array $colors ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$items   = $section['items'] ?? $section['features'] ?? array();

		$children = array();

		if ( ! empty( $heading ) ) {
			$children[] = $this->build_heading( $heading, 'h2', array(
				'_margin_bottom' => array( 'unit' => 'px', 'size' => 40 ),
			) );
		}

		if ( ! empty( $items ) && is_array( $items ) ) {
			$columns_container = $this->build_inner_container( array(), array(
				'layout'          => 'full',
				'flex_direction'  => 'row',
				'flex_wrap'       => 'wrap',
				'justify_content' => 'center',
				'flex_gap'        => array( 'unit' => 'px', 'size' => 30 ),
			) );

			$count = min( count( $items ), 4 );
			$width = round( 100 / $count, 1 );

			$index = 0;
			foreach ( $items as $item ) {
				if ( $index >= 4 ) {
					break;
				}

				$icon  = $item['icon'] ?? '';
				$title = $item['heading'] ?? $item['title'] ?? '';
				$desc  = $item['description'] ?? $item['content'] ?? '';

				$feature_elements = array();

				if ( ! empty( $icon ) ) {
					$feature_elements[] = array(
						'id'         => $this->get_unique_id(),
						'elType'     => 'widget',
						'widgetType' => 'icon',
						'settings'   => array(
							'selected_icon' => $icon,
							'align'         => 'center',
							'primary_color' => $colors['primary'] ?? '#2563eb',
							'icon_size'     => array( 'unit' => 'px', 'size' => 48 ),
						),
						'elements'   => array(),
						'isInner'    => false,
					);
				}

				if ( ! empty( $title ) ) {
					$feature_elements[] = $this->build_heading( $title, 'h3', array(
						'align' => 'center',
						'_margin_top' => array( 'unit' => 'px', 'size' => 12 ),
						'_margin_bottom' => array( 'unit' => 'px', 'size' => 8 ),
					) );
				}

				if ( ! empty( $desc ) ) {
					$feature_elements[] = $this->build_text_editor( $desc, array(
						'align' => 'center',
					) );
				}

				$feature_container = $this->build_inner_container( $feature_elements, array(
					'layout'         => 'boxed',
					'content_width'  => 'full',
					'flex_direction' => 'column',
					'align_items'    => 'center',
					'padding'        => array(
						'top'    => '24',
						'right'  => '16',
						'bottom' => '24',
						'left'   => '16',
						'unit'   => 'px',
					),
				) );

				$columns_container['elements'][] = $feature_container;
				$index++;
			}

			$children[] = $columns_container;
		}

		return $this->build_container( $children, array(
			'background_background' => 'classic',
			'background_color'      => $colors['background'] ?? '#ffffff',
		) );
	}

	/**
	 * Render an about section with image and text side by side.
	 *
	 * @param array $section Section data.
	 * @param array $colors  Global color palette.
	 *
	 * @return array Elementor container element.
	 */
	private function render_about( array $section, array $colors ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$content = $section['content'] ?? $section['description'] ?? '';
		$image   = $section['image'] ?? '';
		$reverse = ! empty( $section['image_position'] ) && 'right' === $section['image_position'];

		$children = array();

		if ( ! empty( $heading ) ) {
			$children[] = $this->build_heading( $heading, 'h2', array(
				'_margin_bottom' => array( 'unit' => 'px', 'size' => 40 ),
			) );
		}

		$row = $this->build_inner_container( array(), array(
			'layout'         => 'full',
			'flex_direction' => 'row',
			'flex_wrap'      => 'wrap',
			'flex_gap'       => array( 'unit' => 'px', 'size' => 60 ),
			'align_items'    => 'center',
		) );

		$image_elements = array();
		if ( ! empty( $image ) ) {
			$image_elements[] = array(
				'id'         => $this->get_unique_id(),
				'elType'     => 'widget',
				'widgetType' => 'image',
				'settings'   => array(
					'image'       => array( 'url' => $image ),
					'image_size'  => 'large',
					'align'       => 'center',
					'border_radius' => array(
						'unit'     => 'px',
						'top'      => '12',
						'right'    => '12',
						'bottom'   => '12',
						'left'     => '12',
						'isLinked' => true,
					),
				),
				'elements'   => array(),
				'isInner'    => false,
			);
		}

		$text_elements = array();
		if ( ! empty( $content ) ) {
			$text_elements[] = $this->build_text_editor( $content, array(
				'align'           => 'left',
				'typography_font_size' => array( 'unit' => 'px', 'size' => 16 ),
				'typography_line_height' => array( 'unit' => 'em', 'size' => 1.8 ),
			) );
		}

		$image_column = $this->build_inner_container( $image_elements, array(
			'layout'         => 'boxed',
			'content_width'  => 'full',
			'flex_direction' => 'column',
		) );

		$text_column = $this->build_inner_container( $text_elements, array(
			'layout'         => 'boxed',
			'content_width'  => 'full',
			'flex_direction' => 'column',
		) );

		$row['elements'] = $reverse
			? array( $text_column, $image_column )
			: array( $image_column, $text_column );

		$children[] = $row;

		return $this->build_container( $children );
	}

	/**
	 * Render testimonials section.
	 *
	 * @param array $section Section data.
	 * @param array $colors  Global color palette.
	 *
	 * @return array Elementor container element.
	 */
	private function render_testimonials( array $section, array $colors ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$reviews = $section['items'] ?? $section['testimonials'] ?? array();

		$children = array();

		if ( ! empty( $heading ) ) {
			$children[] = $this->build_heading( $heading, 'h2', array(
				'_margin_bottom' => array( 'unit' => 'px', 'size' => 40 ),
			) );
		}

		if ( ! empty( $reviews ) && is_array( $reviews ) ) {
			$row = $this->build_inner_container( array(), array(
				'layout'         => 'full',
				'flex_direction' => 'row',
				'flex_wrap'      => 'wrap',
				'flex_gap'       => array( 'unit' => 'px', 'size' => 30 ),
			) );

			$count = min( count( $reviews ), 3 );

			$index = 0;
			foreach ( $reviews as $review ) {
				if ( $index >= 3 ) {
					break;
				}

				$quote  = $review['quote'] ?? $review['content'] ?? $review['text'] ?? '';
				$author = $review['author'] ?? $review['name'] ?? '';
				$role   = $review['role'] ?? $review['title'] ?? '';

				$testimonial_elements = array();

				if ( ! empty( $quote ) ) {
					$testimonial_elements[] = $this->build_text_editor(
						sprintf( '<em>"%s"</em>', esc_html( $quote ) ),
						array(
							'align'           => 'left',
							'typography_font_size' => array( 'unit' => 'px', 'size' => 15 ),
							'typography_line_height' => array( 'unit' => 'em', 'size' => 1.6 ),
						)
					);
				}

				$author_text = '';
				if ( ! empty( $author ) ) {
					$author_text = '<strong>' . esc_html( $author ) . '</strong>';
				}
				if ( ! empty( $role ) ) {
					$author_text .= ' &mdash; ' . esc_html( $role );
				}

				if ( ! empty( $author_text ) ) {
					$testimonial_elements[] = $this->build_text_editor( $author_text, array(
						'align'           => 'left',
						'text_color'      => $colors['primary'] ?? '#2563eb',
						'typography_font_size' => array( 'unit' => 'px', 'size' => 14 ),
					) );
				}

				$testimonial_container = $this->build_inner_container( $testimonial_elements, array(
					'layout'         => 'boxed',
					'content_width'  => 'full',
					'flex_direction' => 'column',
					'padding'        => array(
						'top'    => '24',
						'right'  => '24',
						'bottom' => '24',
						'left'   => '24',
						'unit'   => 'px',
					),
					'background_background' => 'classic',
					'background_color'      => '#f9fafb',
					'border_radius'         => array(
						'unit'     => 'px',
						'top'      => '8',
						'right'    => '8',
						'bottom'   => '8',
						'left'     => '8',
						'isLinked' => true,
					),
				) );

				$row['elements'][] = $testimonial_container;
				$index++;
			}

			$children[] = $row;
		}

		return $this->build_container( $children );
	}

	/**
	 * Render pricing section.
	 *
	 * @param array $section Section data.
	 * @param array $colors  Global color palette.
	 *
	 * @return array Elementor container element.
	 */
	private function render_pricing( array $section, array $colors ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$plans   = $section['items'] ?? $section['plans'] ?? array();

		$children = array();

		if ( ! empty( $heading ) ) {
			$children[] = $this->build_heading( $heading, 'h2', array(
				'_margin_bottom' => array( 'unit' => 'px', 'size' => 40 ),
			) );
		}

		if ( ! empty( $plans ) && is_array( $plans ) ) {
			$row = $this->build_inner_container( array(), array(
				'layout'         => 'full',
				'flex_direction' => 'row',
				'flex_wrap'      => 'wrap',
				'flex_gap'       => array( 'unit' => 'px', 'size' => 30 ),
				'justify_content' => 'center',
			) );

			$count = min( count( $plans ), 3 );

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

				$card_elements = array();

				if ( ! empty( $name ) ) {
					$card_elements[] = $this->build_heading( $name, 'h3', array(
						'align' => 'center',
						'_margin_bottom' => array( 'unit' => 'px', 'size' => 8 ),
					) );
				}

				if ( ! empty( $price ) ) {
					$card_elements[] = array(
						'id'         => $this->get_unique_id(),
						'elType'     => 'widget',
						'widgetType' => 'heading',
						'settings'   => array(
							'title'         => sprintf( '%s<span style="font-size:16px;font-weight:400">%s</span>', esc_html( $price ), esc_html( $period ) ),
							'header_size'   => 'h1',
							'align'         => 'center',
							'typography_font_size' => array( 'unit' => 'px', 'size' => 48 ),
							'_margin_bottom' => array( 'unit' => 'px', 'size' => 16 ),
						),
						'elements'   => array(),
						'isInner'    => false,
					);
				}

				if ( ! empty( $features ) && is_array( $features ) ) {
					$list_html = '<ul style="list-style:none;padding:0;margin:0">';
					foreach ( $features as $feature ) {
						$feature_text = is_array( $feature ) ? ( $feature['text'] ?? $feature['name'] ?? '' ) : $feature;
						$list_html .= sprintf( '<li style="padding:6px 0">&#10003; %s</li>', esc_html( $feature_text ) );
					}
					$list_html .= '</ul>';

					$card_elements[] = $this->build_text_editor( $list_html, array(
						'align' => 'center',
					) );
				}

				if ( ! empty( $cta ) ) {
					$card_elements[] = $this->build_button( $cta, $url, array(
						'align'              => 'center',
						'button_type'        => $featured ? 'danger' : 'info',
						'_margin_top'        => array( 'unit' => 'px', 'size' => 24 ),
					) );
				}

				$card_container = $this->build_inner_container( $card_elements, array(
					'layout'         => 'boxed',
					'content_width'  => 'full',
					'flex_direction' => 'column',
					'align_items'    => 'center',
					'padding'        => array(
						'top'    => '32',
						'right'  => '24',
						'bottom' => '32',
						'left'   => '24',
						'unit'   => 'px',
					),
					'background_background' => 'classic',
					'background_color'      => '#ffffff',
					'border_radius'         => array(
						'unit'     => 'px',
						'top'      => '12',
						'right'    => '12',
						'bottom'   => '12',
						'left'     => '12',
						'isLinked' => true,
					),
					'border_width' => $featured ? array(
						'unit'     => 'px',
						'top'      => '3',
						'right'    => '1',
						'bottom'   => '1',
						'left'     => '1',
						'isLinked' => false,
					) : array(),
					'border_color' => $featured ? array( 'top' => $colors['primary'] ?? '#2563eb' ) : array(),
				) );

				$row['elements'][] = $card_container;
				$index++;
			}

			$children[] = $row;
		}

		return $this->build_container( $children, array(
			'background_background' => 'classic',
			'background_color'      => $colors['background'] ?? '#f9fafb',
		) );
	}

	/**
	 * Render FAQ section using Elementor Accordion widget.
	 *
	 * @param array $section Section data.
	 * @param array $colors  Global color palette.
	 *
	 * @return array Elementor container element.
	 */
	private function render_faq( array $section, array $colors ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$items   = $section['items'] ?? $section['faq'] ?? array();

		$children = array();

		if ( ! empty( $heading ) ) {
			$children[] = $this->build_heading( $heading, 'h2', array(
				'_margin_bottom' => array( 'unit' => 'px', 'size' => 40 ),
			) );
		}

		if ( ! empty( $items ) && is_array( $items ) ) {
			$accordion_items = array();
			$tab_index       = 0;

			foreach ( $items as $item ) {
				$question = $item['question'] ?? $item['heading'] ?? $item['title'] ?? '';
				$answer   = $item['answer'] ?? $item['content'] ?? $item['description'] ?? '';

				if ( ! empty( $question ) ) {
					$accordion_items[] = array(
						'tab_title'   => $question,
						'tab_content' => $answer,
						'_id'         => $this->get_unique_id(),
					);
					$tab_index++;
				}
			}

			if ( ! empty( $accordion_items ) ) {
				$children[] = array(
					'id'         => $this->get_unique_id(),
					'elType'     => 'widget',
					'widgetType' => 'accordion',
					'settings'   => array(
						'tabs'              => $accordion_items,
						'title_typography_typography' => 'custom',
						'title_typography_font_size' => array( 'unit' => 'px', 'size' => 18 ),
						'active_border_color' => $colors['primary'] ?? '#2563eb',
						'border_width' => array(
							'unit'     => 'px',
							'top'      => '0',
							'right'    => '0',
							'bottom'   => '1',
							'left'     => '0',
							'isLinked' => false,
						),
					),
					'elements'   => array(),
					'isInner'    => false,
				);
			}
		}

		return $this->build_container( $children, array(
			'background_background' => 'classic',
			'background_color'      => $colors['background'] ?? '#ffffff',
		) );
	}

	/**
	 * Render a call-to-action section.
	 *
	 * @param array $section Section data.
	 * @param array $colors  Global color palette.
	 *
	 * @return array Elementor container element.
	 */
	private function render_cta( array $section, array $colors ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$content = $section['content'] ?? $section['description'] ?? '';
		$buttons = $section['buttons'] ?? $section['cta'] ?? array();

		$children = array();

		if ( ! empty( $heading ) ) {
			$children[] = $this->build_heading( $heading, 'h2', array(
				'align'        => 'center',
				'title_color'  => '#ffffff',
				'_margin_bottom' => array( 'unit' => 'px', 'size' => 16 ),
			) );
		}

		if ( ! empty( $content ) ) {
			$children[] = $this->build_text_editor( $content, array(
				'align'            => 'center',
				'text_color'       => '#ffffff',
				'typography_font_size' => array( 'unit' => 'px', 'size' => 18 ),
			) );
		}

		if ( ! empty( $buttons ) && is_array( $buttons ) ) {
			$btn_widgets = array();
			foreach ( $buttons as $button ) {
				$label = $button['label'] ?? $button['text'] ?? '';
				$url   = $button['url'] ?? $button['link'] ?? '#';
				if ( ! empty( $label ) ) {
					$btn_widgets[] = $this->build_button( $label, $url, array(
						'align'             => 'center',
						'button_text_color' => '#000000',
						'background_color'  => '#ffffff',
						'_margin_top'       => array( 'unit' => 'px', 'size' => 24 ),
					) );
				}
			}

			if ( ! empty( $btn_widgets ) ) {
				$children[] = $this->build_inner_container( $btn_widgets, array(
					'layout'         => 'full',
					'flex_direction' => 'row',
					'justify_content' => 'center',
					'flex_gap'       => array( 'unit' => 'px', 'size' => 16 ),
				) );
			}
		}

		return $this->build_container( $children, array(
			'layout'               => 'boxed',
			'content_width'        => 1140,
			'flex_direction'       => 'column',
			'align_items'          => 'center',
			'justify_content'      => 'center',
			'background_background'=> 'classic',
			'background_color'     => $colors['primary'] ?? '#1e40af',
			'padding'              => array(
				'top'    => '100',
				'right'  => '20',
				'bottom' => '100',
				'left'   => '20',
				'unit'   => 'px',
			),
		) );
	}

	/**
	 * Render a gallery section.
	 *
	 * @param array $section Section data.
	 * @param array $colors  Global color palette.
	 *
	 * @return array Elementor container element.
	 */
	private function render_gallery( array $section, array $colors ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$images  = $section['images'] ?? $section['items'] ?? array();
		$columns = $section['columns'] ?? 3;

		$children = array();

		if ( ! empty( $heading ) ) {
			$children[] = $this->build_heading( $heading, 'h2', array(
				'_margin_bottom' => array( 'unit' => 'px', 'size' => 40 ),
			) );
		}

		if ( ! empty( $images ) && is_array( $images ) ) {
			$gallery_images = array();
			foreach ( $images as $image ) {
				$url = is_array( $image ) ? ( $image['url'] ?? $image['src'] ?? '' ) : $image;
				if ( ! empty( $url ) ) {
					$gallery_images[] = array(
						'id'  => 0,
						'url' => $url,
					);
				}
			}

			if ( ! empty( $gallery_images ) ) {
				$children[] = array(
					'id'         => $this->get_unique_id(),
					'elType'     => 'widget',
					'widgetType' => 'gallery',
					'settings'   => array(
						'gallery'      => $gallery_images,
						'columns'      => absint( $columns ),
						'columns_tablet' => 2,
						'columns_mobile' => 1,
						'link_to'      => 'none',
						'aspect_ratio' => '16:9',
					),
					'elements'   => array(),
					'isInner'    => false,
				);
			}
		}

		return $this->build_container( $children );
	}

	/**
	 * Render a contact section.
	 *
	 * @param array $section Section data.
	 * @param array $colors  Global color palette.
	 *
	 * @return array Elementor container element.
	 */
	private function render_contact( array $section, array $colors ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$content = $section['content'] ?? $section['description'] ?? '';
		$email   = $section['email'] ?? '';
		$phone   = $section['phone'] ?? '';
		$address = $section['address'] ?? '';
		$fields  = $section['fields'] ?? array( 'name', 'email', 'subject', 'message' );

		$children = array();

		if ( ! empty( $heading ) ) {
			$children[] = $this->build_heading( $heading, 'h2', array(
				'_margin_bottom' => array( 'unit' => 'px', 'size' => 40 ),
			) );
		}

		$row = $this->build_inner_container( array(), array(
			'layout'         => 'full',
			'flex_direction' => 'row',
			'flex_wrap'      => 'wrap',
			'flex_gap'       => array( 'unit' => 'px', 'size' => 60 ),
		) );

		$info_elements = array();

		if ( ! empty( $content ) ) {
			$info_elements[] = $this->build_text_editor( $content, array(
				'align'           => 'left',
				'_margin_bottom'  => array( 'unit' => 'px', 'size' => 24 ),
			) );
		}

		$contact_lines = '';
		if ( ! empty( $email ) ) {
			$contact_lines .= sprintf( '<p><strong>Email:</strong> <a href="mailto:%s">%s</a></p>', esc_attr( $email ), esc_html( $email ) );
		}
		if ( ! empty( $phone ) ) {
			$contact_lines .= sprintf( '<p><strong>Phone:</strong> <a href="tel:%s">%s</a></p>', esc_attr( $phone ), esc_html( $phone ) );
		}
		if ( ! empty( $address ) ) {
			$contact_lines .= sprintf( '<p><strong>Address:</strong> %s</p>', esc_html( $address ) );
		}

		if ( ! empty( $contact_lines ) ) {
			$info_elements[] = $this->build_text_editor( $contact_lines, array(
				'align' => 'left',
			) );
		}

		$info_column = $this->build_inner_container( $info_elements, array(
			'layout'         => 'boxed',
			'content_width'  => 'full',
			'flex_direction' => 'column',
		) );

		$form_fields = array();
		foreach ( $fields as $field ) {
			$field_name  = is_array( $field ) ? ( $field['name'] ?? '' ) : $field;
			$field_label = is_array( $field ) ? ( $field['label'] ?? ucfirst( $field_name ) ) : ucfirst( $field_name );
			$is_textarea = 'message' === $field_name || 'comments' === $field_name;

			$form_fields[] = array(
				'tab_title'   => $field_label,
				'tab_content' => '',
				'_id'         => $this->get_unique_id(),
			);
		}

		$form_elements = array();
		if ( ! empty( $form_fields ) ) {
			$form_elements[] = array(
				'id'         => $this->get_unique_id(),
				'elType'     => 'widget',
				'widgetType' => 'form',
				'settings'   => array(
					'form_name'   => 'aipb-contact',
					'fields'      => array_map(
						function ( $field ) {
							$name  = is_array( $field ) ? ( $field['name'] ?? '' ) : $field;
							$label = is_array( $field ) ? ( $field['label'] ?? ucfirst( $name ) ) : ucfirst( $name );
							$is_textarea = 'message' === $name || 'comments' === $name;

							return array(
								'field_type' => $is_textarea ? 'textarea' : 'text',
								'field_label' => $label,
								'field_id'   => sanitize_key( $name ),
								'field_required' => 'yes',
							);
						},
						$fields
					),
					'submit_button_text' => __( 'Send Message', 'promptpress-ai' ),
					'button_align'       => 'left',
				),
				'elements'   => array(),
				'isInner'    => false,
			);
		}

		$form_column = $this->build_inner_container( $form_elements, array(
			'layout'         => 'boxed',
			'content_width'  => 'full',
			'flex_direction' => 'column',
		) );

		$row['elements'] = array( $info_column, $form_column );
		$children[]      = $row;

		return $this->build_container( $children, array(
			'background_background' => 'classic',
			'background_color'      => $colors['background'] ?? '#ffffff',
		) );
	}

	/**
	 * Render a divider element.
	 *
	 * @param array $section Section data.
	 * @param array $colors  Global color palette.
	 *
	 * @return array Elementor container with divider widget.
	 */
	private function render_divider( array $section, array $colors ) {
		$style  = $section['style'] ?? 'wide';
		$weight = 'dots' === $style ? 1 : 2;

		return $this->build_container(
			array(
				array(
					'id'         => $this->get_unique_id(),
					'elType'     => 'widget',
					'widgetType' => 'divider',
					'settings'   => array(
						'weight'         => $weight,
						'style'          => $style === 'wide' ? 'solid' : $style,
						'width'          => array( 'unit' => '%', 'size' => 50 ),
						'align'          => 'center',
						'color'          => '#e5e7eb',
					),
					'elements'   => array(),
					'isInner'    => false,
				),
			),
			array(
				'padding' => array(
					'top'    => '40',
					'right'  => '20',
					'bottom' => '40',
					'left'   => '20',
					'unit'   => 'px',
				),
			)
		);
	}

	/**
	 * Render a spacer element.
	 *
	 * @param array $section Section data.
	 * @param array $colors  Global color palette.
	 *
	 * @return array Elementor container with spacer widget.
	 */
	private function render_spacer( array $section, $colors ) {
		$height = $section['height'] ?? 60;

		return $this->build_container(
			array(
				array(
					'id'         => $this->get_unique_id(),
					'elType'     => 'widget',
					'widgetType' => 'spacer',
					'settings'   => array(
						'space' => array(
							'unit' => 'px',
							'size' => absint( $height ),
						),
					),
					'elements'   => array(),
					'isInner'    => false,
				),
			),
			array(
				'padding' => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0' ),
			)
		);
	}

	/**
	 * Render generic content as heading + text + optional list.
	 *
	 * @param array $section Section data.
	 * @param array $colors  Global color palette.
	 *
	 * @return array Elementor container element.
	 */
	private function render_content( array $section, array $colors ) {
		$heading = $section['heading'] ?? $section['title'] ?? '';
		$content = $section['content'] ?? $section['description'] ?? '';
		$items   = $section['items'] ?? array();
		$level   = isset( $section['level'] ) ? 'h' . absint( $section['level'] ) : 'h2';

		$children = array();

		if ( ! empty( $heading ) ) {
			$children[] = $this->build_heading( $heading, $level, array(
				'align'         => 'left',
				'_margin_bottom' => array( 'unit' => 'px', 'size' => 20 ),
			) );
		}

		if ( ! empty( $content ) ) {
			$children[] = $this->build_text_editor( $content, array(
				'align'                => 'left',
				'typography_font_size' => array( 'unit' => 'px', 'size' => 16 ),
				'typography_line_height' => array( 'unit' => 'em', 'size' => 1.8 ),
				'_margin_bottom'       => array( 'unit' => 'px', 'size' => 16 ),
			) );
		}

		if ( ! empty( $items ) && is_array( $items ) ) {
			$list_html = '<ul>';
			foreach ( $items as $item ) {
				$item_text = is_array( $item ) ? ( $item['text'] ?? $item['content'] ?? '' ) : $item;
				if ( ! empty( $item_text ) ) {
					$list_html .= '<li>' . esc_html( $item_text ) . '</li>';
				}
			}
			$list_html .= '</ul>';

			$children[] = $this->build_text_editor( $list_html, array(
				'align' => 'left',
			) );
		}

		return $this->build_container( $children );
	}

	/**
	 * Save Elementor document data to post meta.
	 *
	 * @param int   $post_id       Post ID.
	 * @param array $elementor_data Full Elementor document data.
	 *
	 * @return bool True on success.
	 */
	public function save_elementor_document( int $post_id, array $elementor_data ) {
		if ( ! defined( 'ELEMENTOR_VERSION' ) || ! class_exists( '\\Elementor\\Plugin' ) ) {
			return new \WP_Error(
				'aipb_elementor_unavailable',
				__( 'Elementor is not available. Please activate Elementor and try again.', 'promptpress-ai' )
			);
		}

		// _elementor_data must contain the native element array directly.
		// Wrapping it in {version,elements} produces an invalid Elementor document.
		update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
		update_post_meta( $post_id, '_wp_page_template', 'elementor_header_footer' );

		update_post_meta(
			$post_id,
			'aipb_elementor_template',
			array(
				'version'  => ELEMENTOR_VERSION,
				'elements' => $elementor_data,
			)
		);

		// Ask Elementor to invalidate generated CSS/cache for this document.
		if (
			isset( \Elementor\Plugin::$instance->files_manager )
			&& method_exists( \Elementor\Plugin::$instance->files_manager, 'clear_cache' )
		) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		/**
		 * Fires after an Elementor document has been saved by PromptPress AI.
		 *
		 * @param int   $post_id       The post ID.
		 * @param array $elementor_data The saved Elementor elements.
		 */
		do_action( 'aipb_elementor_document_saved', $post_id, $elementor_data );

		return true;
	}

	/**
	 * Build a complete Elementor template data structure.
	 *
	 * @param array $structured_content AI-generated structured content.
	 *
	 * @return array Full Elementor template data for meta storage.
	 */
	public function get_elementor_template_data( array $structured_content ) {
		$this->element_id_counter = 0;

		$sections = $structured_content['sections'] ?? $structured_content;

		if ( ! is_array( $sections ) ) {
			$sections = array();
		}

		$colors  = $structured_content['colors'] ?? array();
		$elements = array();

		foreach ( $sections as $section ) {
			$type    = $section['type'] ?? 'content';
			$element = $this->render_section( $type, $section, $colors );
			if ( ! empty( $element ) ) {
				$elements[] = $element;
			}
		}

		return array(
			'version'  => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '1.0.0',
			'settings' => array(
				'page_title_selector' => 'h1.entry-title',
				'page'                => array(),
			),
			'elements' => $elements,
		);
	}
}
