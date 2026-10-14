<?php
/**
 * AI Builder page view.
 *
 * @package AIPB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings    = get_option( 'aipb_settings', array() );
$brand       = get_option( 'aipb_brand_settings', array() );
$has_api_key = ! empty( $settings['api_key'] );
$demo_mode   = ! empty( $settings['demo_mode'] );
?>
<div class="wrap aipb-wrap" id="aipb-app">
	<h1 class="aipb-page-title">
		<span class="dashicons dashicons-admin-page"></span>
		<?php esc_html_e( 'AI Page Builder', 'promptpress-ai' ); ?>
	</h1>

	<?php if ( ! $has_api_key && ! $demo_mode ) : ?>
		<div class="aipb-notice aipb-notice--warning">
			<span class="dashicons dashicons-warning"></span>
			<p>
				<?php
				printf(
					wp_kses(
						/* translators: %s: link to settings page */
						__( 'Please <a href="%s">configure your API key</a> before generating content.', 'promptpress-ai' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( admin_url( 'admin.php?page=aipb-ai-settings' ) )
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $demo_mode ) : ?>
		<div class="aipb-demo-banner">
			<span class="dashicons dashicons-admin-customizer"></span>
			<div><strong><?php esc_html_e( 'Local Demo Mode is active', 'promptpress-ai' ); ?></strong><span><?php esc_html_e( 'Generation is simulated locally. No OpenAI credits are used.', 'promptpress-ai' ); ?></span></div>
		</div>
	<?php endif; ?>

	<div class="aipb-builder">
		<div class="aipb-builder__sidebar">
			<div class="aipb-panel">
				<h2 class="aipb-panel__title"><?php esc_html_e( 'Generation Settings', 'promptpress-ai' ); ?></h2>

				<div class="aipb-form-group">
					<label for="aipb-content-type" class="aipb-label">
						<?php esc_html_e( 'Content Type', 'promptpress-ai' ); ?>
					</label>
					<select id="aipb-content-type" class="aipb-select" name="content_type">
						<option value="page"><?php esc_html_e( 'Page', 'promptpress-ai' ); ?></option>
						<option value="post"><?php esc_html_e( 'Post', 'promptpress-ai' ); ?></option>
						<option value="landing_page"><?php esc_html_e( 'Landing Page', 'promptpress-ai' ); ?></option>
					</select>
				</div>

				<div class="aipb-form-group">
					<label for="aipb-editor" class="aipb-label">
						<?php esc_html_e( 'Editor', 'promptpress-ai' ); ?>
					</label>
					<select id="aipb-editor" class="aipb-select" name="editor">
						<option value="gutenberg"><?php esc_html_e( 'Gutenberg', 'promptpress-ai' ); ?></option>
						<option value="elementor" <?php echo class_exists( '\Elementor\Plugin' ) ? '' : 'disabled'; ?>>
							<?php esc_html_e( 'Elementor', 'promptpress-ai' ); ?>
						</option>
					</select>
					<?php if ( ! class_exists( '\Elementor\Plugin' ) ) : ?>
						<p class="aipb-hint"><?php esc_html_e( 'Elementor not detected.', 'promptpress-ai' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="aipb-form-group">
					<label for="aipb-language" class="aipb-label">
						<?php esc_html_e( 'Language', 'promptpress-ai' ); ?>
					</label>
					<select id="aipb-language" class="aipb-select" name="language">
						<option value="en"><?php esc_html_e( 'English', 'promptpress-ai' ); ?></option>
						<option value="es"><?php esc_html_e( 'Spanish', 'promptpress-ai' ); ?></option>
						<option value="fr"><?php esc_html_e( 'French', 'promptpress-ai' ); ?></option>
						<option value="de"><?php esc_html_e( 'German', 'promptpress-ai' ); ?></option>
						<option value="pt"><?php esc_html_e( 'Portuguese', 'promptpress-ai' ); ?></option>
						<option value="it"><?php esc_html_e( 'Italian', 'promptpress-ai' ); ?></option>
						<option value="nl"><?php esc_html_e( 'Dutch', 'promptpress-ai' ); ?></option>
						<option value="ja"><?php esc_html_e( 'Japanese', 'promptpress-ai' ); ?></option>
						<option value="zh"><?php esc_html_e( 'Chinese', 'promptpress-ai' ); ?></option>
						<option value="ar"><?php esc_html_e( 'Arabic', 'promptpress-ai' ); ?></option>
					</select>
				</div>

				<div class="aipb-form-group">
					<label for="aipb-tone" class="aipb-label">
						<?php esc_html_e( 'Tone', 'promptpress-ai' ); ?>
					</label>
					<select id="aipb-tone" class="aipb-select" name="tone">
						<option value="professional"><?php esc_html_e( 'Professional', 'promptpress-ai' ); ?></option>
						<option value="casual"><?php esc_html_e( 'Casual', 'promptpress-ai' ); ?></option>
						<option value="friendly"><?php esc_html_e( 'Friendly', 'promptpress-ai' ); ?></option>
						<option value="formal"><?php esc_html_e( 'Formal', 'promptpress-ai' ); ?></option>
						<option value="persuasive"><?php esc_html_e( 'Persuasive', 'promptpress-ai' ); ?></option>
						<option value="informative"><?php esc_html_e( 'Informative', 'promptpress-ai' ); ?></option>
						<option value="enthusiastic"><?php esc_html_e( 'Enthusiastic', 'promptpress-ai' ); ?></option>
					</select>
				</div>

				<div class="aipb-form-group">
					<label for="aipb-design-style" class="aipb-label">
						<?php esc_html_e( 'Design Style', 'promptpress-ai' ); ?>
					</label>
					<select id="aipb-design-style" class="aipb-select" name="design_style">
						<option value="modern"><?php esc_html_e( 'Modern', 'promptpress-ai' ); ?></option>
						<option value="classic"><?php esc_html_e( 'Classic', 'promptpress-ai' ); ?></option>
						<option value="minimal"><?php esc_html_e( 'Minimal', 'promptpress-ai' ); ?></option>
						<option value="bold"><?php esc_html_e( 'Bold', 'promptpress-ai' ); ?></option>
						<option value="elegant"><?php esc_html_e( 'Elegant', 'promptpress-ai' ); ?></option>
					</select>
				</div>

				<div class="aipb-form-group">
					<label for="aipb-image-source" class="aipb-label">
						<?php esc_html_e( 'Image Source', 'promptpress-ai' ); ?>
					</label>
					<select id="aipb-image-source" class="aipb-select" name="image_source">
						<option value="ai"><?php esc_html_e( 'AI Generated', 'promptpress-ai' ); ?></option>
						<option value="stock" disabled><?php esc_html_e( 'Stock Photos (Coming Soon)', 'promptpress-ai' ); ?></option>
						<option value="none"><?php esc_html_e( 'No Images', 'promptpress-ai' ); ?></option>
					</select>
				</div>

				<div class="aipb-form-group aipb-form-group--inline">
					<label for="aipb-seo-optimize" class="aipb-label">
						<?php esc_html_e( 'SEO Optimize', 'promptpress-ai' ); ?>
					</label>
					<label class="aipb-toggle">
						<input type="checkbox" id="aipb-seo-optimize" name="seo_optimize" value="1">
						<span class="aipb-toggle__slider"></span>
					</label>
				</div>

				<div class="aipb-form-group">
					<label for="aipb-publish-status" class="aipb-label">
						<?php esc_html_e( 'Publish Status', 'promptpress-ai' ); ?>
					</label>
					<select id="aipb-publish-status" class="aipb-select" name="publish_status">
						<option value="draft"><?php esc_html_e( 'Draft', 'promptpress-ai' ); ?></option>
						<option value="publish"><?php esc_html_e( 'Publish', 'promptpress-ai' ); ?></option>
						<option value="pending"><?php esc_html_e( 'Pending Review', 'promptpress-ai' ); ?></option>
					</select>
				</div>
			</div>

			<div class="aipb-panel aipb-panel--brand">
				<h2 class="aipb-panel__title"><?php esc_html_e( 'Brand', 'promptpress-ai' ); ?></h2>
				<?php if ( ! empty( $brand['brand_name'] ) ) : ?>
					<p class="aipb-hint">
						<strong><?php esc_html_e( 'Active Brand:', 'promptpress-ai' ); ?></strong>
						<?php echo esc_html( $brand['brand_name'] ); ?>
					</p>
				<?php else : ?>
					<p class="aipb-hint"><?php esc_html_e( 'No brand configured.', 'promptpress-ai' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=aipb-brand-settings' ) ); ?>" class="button button-small">
						<?php esc_html_e( 'Set Up Brand', 'promptpress-ai' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="aipb-builder__main">
			<div class="aipb-panel aipb-prompt-panel">
				<label for="aipb-prompt" class="aipb-label aipb-label--large">
					<?php esc_html_e( 'Describe what you want to create', 'promptpress-ai' ); ?>
				</label>
				<textarea
					data-initial-prompt="<?php echo esc_attr( isset( $_GET['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_GET['prompt'] ) ) : '' ); ?>" id="aipb-prompt"
					class="aipb-textarea aipb-textarea--large"
					rows="6"
					placeholder="<?php esc_attr_e( 'Example: Create a modern landing page for a fitness app with hero section, feature highlights, testimonials, and a download call-to-action...', 'promptpress-ai' ); ?>"
				></textarea>

				<div class="aipb-prompt-actions">
					<button type="button" id="aipb-suggestions-btn" class="button button-link aipb-link-btn">
						<span class="dashicons dashicons-lightbulb"></span>
						<?php esc_html_e( 'Get Suggestions', 'promptpress-ai' ); ?>
					</button>
					<button type="button" id="aipb-generate-btn" class="button button-primary button-hero aipb-generate-btn" <?php echo ( $has_api_key || $demo_mode ) ? '' : 'disabled'; ?>>
						<span class="dashicons dashicons-performance"></span>
						<?php esc_html_e( 'Generate Content', 'promptpress-ai' ); ?>
					</button>
				</div>
			</div>

			<div id="aipb-suggestions" class="aipb-suggestions" style="display:none;">
				<h3><?php esc_html_e( 'Suggestions', 'promptpress-ai' ); ?></h3>
				<div class="aipb-suggestions__list"></div>
			</div>

			<div id="aipb-progress" class="aipb-progress" style="display:none;">
				<div class="aipb-progress__bar">
					<div class="aipb-progress__fill"></div>
				</div>
				<p class="aipb-progress__text">
					<span class="aipb-spinner"></span>
					<?php esc_html_e( 'Generating your content...', 'promptpress-ai' ); ?>
				</p>
			</div>

			<div id="aipb-preview" class="aipb-preview" style="display:none;">
				<div class="aipb-preview__header">
					<h2><?php esc_html_e( 'Preview', 'promptpress-ai' ); ?></h2>
					<div class="aipb-preview__actions">
						<a id="aipb-preview-edit" href="#" class="button" target="_blank">
							<?php esc_html_e( 'Edit in Editor', 'promptpress-ai' ); ?>
						</a>
						<a id="aipb-preview-view" href="#" class="button" target="_blank">
							<?php esc_html_e( 'View Page', 'promptpress-ai' ); ?>
						</a>
					</div>
				</div>
				<div class="aipb-preview__frame" id="aipb-preview-content"></div>
			</div>
		</div>
	</div>
</div>
