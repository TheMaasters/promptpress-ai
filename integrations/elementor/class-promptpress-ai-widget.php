<?php
/**
 * PromptPress AI Elementor widget.
 *
 * @package AIPB
 */

namespace AIPB\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Native Elementor widget that provides an AI page-generation assistant.
 */
class PromptPress_AI_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'promptpress_ai';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'PromptPress AI', 'promptpress-ai' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-magic-wand';
	}

	/**
	 * Widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'aipb' );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'ai', 'prompt', 'content', 'page', 'generator' );
	}

	/**
	 * Editor script dependencies.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'aipb-elementor-widget' );
	}

	/**
	 * Register widget controls.
	 *
	 * @param \Elementor\Controls_Manager $controls_manager Controls manager.
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => esc_html__( 'PromptPress AI', 'promptpress-ai' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'prompt',
			array(
				'label'       => esc_html__( 'What should AI create?', 'promptpress-ai' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'rows'        => 7,
				'placeholder' => esc_html__( 'Example: Create a modern hero section for a SaaS website with a headline, description and CTA.', 'promptpress-ai' ),
				'default'     => '',
			)
		);

		$this->add_control(
			'content_type',
			array(
				'label'   => esc_html__( 'Content Type', 'promptpress-ai' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'page',
				'options' => array(
					'page'          => esc_html__( 'Page', 'promptpress-ai' ),
					'landing_page'  => esc_html__( 'Landing Page', 'promptpress-ai' ),
				),
			)
		);

		$this->add_control(
			'generate_button',
			array(
				'label'     => esc_html__( 'AI Action', 'promptpress-ai' ),
				'type'      => \Elementor\Controls_Manager::RAW_HTML,
				'raw'       => '<div class="aipb-elementor-control-help"><strong>' .
					esc_html__( 'PromptPress AI', 'promptpress-ai' ) .
					'</strong><p>' .
					esc_html__( 'Use the button in the widget preview to generate a new Elementor draft from this prompt. The generated draft remains fully editable in Elementor.', 'promptpress-ai' ) .
					'</p></div>',
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}

		$settings = $this->get_settings_for_display();
		$widget_id = $this->get_id();
		?>
		<div class="aipb-elementor-widget" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
			<div class="aipb-elementor-widget__icon" aria-hidden="true">✨</div>
			<div class="aipb-elementor-widget__content">
				<strong><?php esc_html_e( 'PromptPress AI', 'promptpress-ai' ); ?></strong>
				<p><?php esc_html_e( 'Describe the page you want AI to create. A new Elementor draft will be generated and opened for editing.', 'promptpress-ai' ); ?></p>
				<textarea class="aipb-elementor-widget__prompt" rows="5" placeholder="<?php esc_attr_e( 'Describe your page...', 'promptpress-ai' ); ?>"><?php echo esc_textarea( $settings['prompt'] ?? '' ); ?></textarea>
				<button type="button" class="aipb-elementor-widget__generate">
					<?php esc_html_e( 'Generate Elementor Draft', 'promptpress-ai' ); ?>
				</button>
				<div class="aipb-elementor-widget__status" role="status" aria-live="polite"></div>
			</div>
		</div>
		<?php
	}
}
