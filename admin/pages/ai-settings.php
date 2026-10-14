<?php
/**
 * AI Settings page view.
 *
 * @package AIPB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = get_option( 'aipb_settings', array() );

$provider      = isset( $settings['provider'] ) ? $settings['provider'] : 'openai';
$api_key       = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
$masked_key    = $api_key ? AIPB\Security::mask_api_key( $api_key ) : '';
$model         = isset( $settings['model'] ) && $settings['model'] ? $settings['model'] : 'gpt-5.6-luna';
$image_model   = isset( $settings['image_model'] ) && $settings['image_model'] ? $settings['image_model'] : 'gpt-image-1-mini';
$temperature   = isset( $settings['temperature'] ) ? floatval( $settings['temperature'] ) : 0.7;
$max_tokens    = isset( $settings['max_tokens'] ) ? max( 4096, intval( $settings['max_tokens'] ) ) : 12000;
$debug_logging = ! empty( $settings['debug_logging'] );
$demo_mode     = ! empty( $settings['demo_mode'] );
?>
<div class="wrap aipb-wrap">
	<h1 class="aipb-page-title">
		<span class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e( 'AI Settings', 'promptpress-ai' ); ?>
	</h1>

	<p class="aipb-subtitle"><?php esc_html_e( 'Configure your AI provider and generation parameters.', 'promptpress-ai' ); ?></p>

	<form id="aipb-settings-form" method="post" action="">
		<?php wp_nonce_field( 'aipb_ai_settings', 'aipb_settings_nonce' ); ?>

		<div class="aipb-settings-grid">
			<div class="aipb-panel">
				<h2 class="aipb-panel__title"><?php esc_html_e( 'API Configuration', 'promptpress-ai' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="provider"><?php esc_html_e( 'API Provider', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<select id="provider" name="provider" class="aipb-select">
								<option value="openai" <?php selected( $provider, 'openai' ); ?>><?php esc_html_e( 'OpenAI', 'promptpress-ai' ); ?></option>
								<option value="anthropic" <?php selected( $provider, 'anthropic' ); ?> disabled>
									<?php esc_html_e( 'Anthropic (Coming Soon)', 'promptpress-ai' ); ?>
								</option>
								<option value="google" <?php selected( $provider, 'google' ); ?> disabled>
									<?php esc_html_e( 'Google AI (Coming Soon)', 'promptpress-ai' ); ?>
								</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="api_key"><?php esc_html_e( 'API Key', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<div class="aipb-input-group">
								<input
									type="password"
									id="api_key"
									name="api_key"
									class="regular-text aipb-api-key-input"
									value="<?php echo esc_attr( $masked_key ); ?>"
									placeholder="<?php esc_attr_e( 'sk-...', 'promptpress-ai' ); ?>"
									autocomplete="off"
								>
								<button type="button" class="button aipb-toggle-key" aria-label="<?php esc_attr_e( 'Show API key', 'promptpress-ai' ); ?>">
									&#128064;
								</button>
							</div>
							<?php if ( $api_key ) : ?>
								<p class="aipb-description aipb-description--success">
									<span class="dashicons dashicons-yes-alt"></span>
									<?php esc_html_e( 'API key is configured.', 'promptpress-ai' ); ?>
								</p>
							<?php else : ?>
								<p class="aipb-description aipb-description--warning">
									<span class="dashicons dashicons-warning"></span>
									<?php esc_html_e( 'No API key configured. Content generation will not work.', 'promptpress-ai' ); ?>
								</p>
							<?php endif; ?>
							<p class="description">
								<?php
								printf(
									wp_kses(
										/* translators: %s: link to OpenAI platform */
										__( 'Get your API key from <a href="%s" target="_blank" rel="noopener noreferrer">OpenAI Platform</a>.', 'promptpress-ai' ),
										array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
									),
									'https://platform.openai.com/api-keys'
								);
								?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="aipb-panel">
				<h2 class="aipb-panel__title"><?php esc_html_e( 'Model Settings', 'promptpress-ai' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="model"><?php esc_html_e( 'Text Model', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<select id="model" name="model" class="aipb-select">
								<option value="gpt-5.6-luna" <?php selected( $model, 'gpt-5.6-luna' ); ?>><?php esc_html_e( 'GPT-5.6 Luna (Recommended)', 'promptpress-ai' ); ?></option>
								<option value="gpt-5.4-mini" <?php selected( $model, 'gpt-5.4-mini' ); ?>><?php esc_html_e( 'GPT-5.4 Mini', 'promptpress-ai' ); ?></option>
								<option value="gpt-4o" <?php selected( $model, 'gpt-4o' ); ?>><?php esc_html_e( 'GPT-4o', 'promptpress-ai' ); ?></option>
								<option value="gpt-4o-mini" <?php selected( $model, 'gpt-4o-mini' ); ?>><?php esc_html_e( 'GPT-4o Mini', 'promptpress-ai' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'PromptPress AI uses the OpenAI Responses API for reliable structured page generation.', 'promptpress-ai' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="image_model"><?php esc_html_e( 'Image Model', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<select id="image_model" name="image_model" class="aipb-select">
								<option value="gpt-image-1-mini" <?php selected( $image_model, 'gpt-image-1-mini' ); ?>><?php esc_html_e( 'GPT Image 1 Mini (Recommended)', 'promptpress-ai' ); ?></option>
								<option value="gpt-image-1.5" <?php selected( $image_model, 'gpt-image-1.5' ); ?>><?php esc_html_e( 'GPT Image 1.5', 'promptpress-ai' ); ?></option>
								<option value="gpt-image-2" <?php selected( $image_model, 'gpt-image-2' ); ?>><?php esc_html_e( 'GPT Image 2', 'promptpress-ai' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="temperature"><?php esc_html_e( 'Temperature', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<div class="aipb-slider-field">
								<input
									type="range"
									id="temperature"
									name="temperature"
									min="0"
									max="2"
									step="0.1"
									value="<?php echo esc_attr( $temperature ); ?>"
									class="aipb-range"
								>
								<span class="aipb-slider-value" id="temperature-value"><?php echo esc_html( $temperature ); ?></span>
							</div>
							<p class="description">
								<?php esc_html_e( 'Lower values produce more focused text, higher values more creative.', 'promptpress-ai' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="max_tokens"><?php esc_html_e( 'Max Tokens', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<input
								type="number"
								id="max_tokens"
								name="max_tokens"
								class="small-text"
								value="<?php echo esc_attr( $max_tokens ); ?>"
								min="256"
								max="128000"
								step="256"
							>
							<p class="description"><?php esc_html_e( 'Maximum output tokens for generated pages. 8,000–16,000 is recommended for full landing pages.', 'promptpress-ai' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="aipb-panel aipb-demo-panel">
				<div class="aipb-demo-panel__icon"><span class="dashicons dashicons-admin-customizer"></span></div>
				<div class="aipb-demo-panel__content">
					<h2 class="aipb-panel__title"><?php esc_html_e( 'Local Demo Mode', 'promptpress-ai' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Test Gutenberg, Elementor, templates and the complete generation workflow without using an OpenAI API request.', 'promptpress-ai' ); ?></p>
					<label class="aipb-toggle">
						<input type="checkbox" id="demo_mode" name="demo_mode" value="1" <?php checked( $demo_mode ); ?>>
						<span class="aipb-toggle__slider"></span>
					</label>
					<span class="aipb-demo-label"><?php echo $demo_mode ? esc_html__( 'Enabled', 'promptpress-ai' ) : esc_html__( 'Disabled', 'promptpress-ai' ); ?></span>
					<p class="aipb-hint"><?php esc_html_e( 'Demo mode creates realistic sample pages locally. It never calls the AI provider or generates billable images.', 'promptpress-ai' ); ?></p>
				</div>
			</div>

			<div class="aipb-panel">
				<h2 class="aipb-panel__title"><?php esc_html_e( 'Debug', 'promptpress-ai' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="debug_logging"><?php esc_html_e( 'Debug Logging', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<label class="aipb-toggle">
								<input
									type="checkbox"
									id="debug_logging"
									name="debug_logging"
									value="1"
									<?php checked( $debug_logging ); ?>
								>
								<span class="aipb-toggle__slider"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Enable detailed logging for troubleshooting API issues.', 'promptpress-ai' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="aipb-settings-actions">
			<?php submit_button( __( 'Save Settings', 'promptpress-ai' ), 'primary', 'submit', false ); ?>
			<button type="button" class="button" id="aipb-test-connection">
				<span class="dashicons dashicons-update"></span>
				<?php esc_html_e( 'Test Connection', 'promptpress-ai' ); ?>
			</button>
			<div id="aipb-connection-status" class="aipb-connection-status" style="display:none;"></div>
		</div>
	</form>
</div>
