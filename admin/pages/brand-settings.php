<?php
/**
 * Brand Settings page view.
 *
 * @package AIPB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brand = get_option( 'aipb_brand_settings', array() );

$brand_name        = isset( $brand['brand_name'] ) ? $brand['brand_name'] : '';
$brand_description = isset( $brand['brand_description'] ) ? $brand['brand_description'] : '';
$primary_color     = isset( $brand['primary_color'] ) ? $brand['primary_color'] : '#0073aa';
$secondary_color   = isset( $brand['secondary_color'] ) ? $brand['secondary_color'] : '#005177';
$accent_color      = isset( $brand['accent_color'] ) ? $brand['accent_color'] : '#00aadc';
$font_preference   = isset( $brand['font_preference'] ) ? $brand['font_preference'] : 'system';
$writing_tone      = isset( $brand['writing_tone'] ) ? $brand['writing_tone'] : 'professional';
$target_audience   = isset( $brand['target_audience'] ) ? $brand['target_audience'] : '';
$logo_id           = isset( $brand['logo_id'] ) ? absint( $brand['logo_id'] ) : 0;
$logo_url          = $logo_id ? wp_get_attachment_url( $logo_id ) : '';
$brand_instructions = isset( $brand['brand_instructions'] ) ? $brand['brand_instructions'] : '';
?>
<div class="wrap aipb-wrap">
	<h1 class="aipb-page-title">
		<span class="dashicons dashicons-admin-home"></span>
		<?php esc_html_e( 'Brand Settings', 'promptpress-ai' ); ?>
	</h1>

	<p class="aipb-subtitle"><?php esc_html_e( 'Configure your brand identity so AI-generated content matches your style.', 'promptpress-ai' ); ?></p>

	<form id="aipb-brand-form" method="post" action="">
		<div id="aipb-brand-status" class="aipb-connection-status" style="display:none;"></div>
		<?php wp_nonce_field( 'aipb_brand_settings', 'aipb_brand_nonce' ); ?>

		<div class="aipb-settings-grid">
			<div class="aipb-panel">
				<h2 class="aipb-panel__title"><?php esc_html_e( 'Brand Identity', 'promptpress-ai' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="brand_name"><?php esc_html_e( 'Brand Name', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="brand_name"
								name="brand_name"
								class="regular-text"
								value="<?php echo esc_attr( $brand_name ); ?>"
								placeholder="<?php esc_attr_e( 'Your Company Name', 'promptpress-ai' ); ?>"
							>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="brand_description"><?php esc_html_e( 'Brand Description', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<textarea
								id="brand_description"
								name="brand_description"
								class="large-text"
								rows="3"
								placeholder="<?php esc_attr_e( 'A short description of your brand and what you do...', 'promptpress-ai' ); ?>"
							><?php echo esc_textarea( $brand_description ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="logo_id"><?php esc_html_e( 'Brand Logo', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<div class="aipb-logo-upload">
								<div class="aipb-logo-preview" id="logo-preview">
									<?php if ( $logo_url ) : ?>
										<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Brand Logo', 'promptpress-ai' ); ?>" style="max-width:200px;max-height:100px;">
									<?php else : ?>
										<div class="aipb-logo-placeholder">
											<span class="dashicons dashicons-format-image"></span>
											<p><?php esc_html_e( 'No logo uploaded', 'promptpress-ai' ); ?></p>
										</div>
									<?php endif; ?>
								</div>
								<input type="hidden" id="logo_id" name="logo_id" value="<?php echo esc_attr( $logo_id ); ?>">
								<button type="button" class="button" id="aipb-upload-logo">
									<?php esc_html_e( 'Upload Logo', 'promptpress-ai' ); ?>
								</button>
								<button type="button" class="button aipb-remove-logo" id="aipb-remove-logo" <?php echo $logo_id ? '' : 'style="display:none;"'; ?>>
									<?php esc_html_e( 'Remove', 'promptpress-ai' ); ?>
								</button>
							</div>
						</td>
					</tr>
				</table>
			</div>

			<div class="aipb-panel">
				<h2 class="aipb-panel__title"><?php esc_html_e( 'Brand Colors', 'promptpress-ai' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="primary_color"><?php esc_html_e( 'Primary Color', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<div class="aipb-color-picker">
								<input
									type="text"
									id="primary_color"
									name="primary_color"
									class="aipb-color-input aipb-wp-color-picker"
									value="<?php echo esc_attr( $primary_color ); ?>"
									data-default-color="#0073aa"
								>
								<div class="aipb-color-swatch" style="background-color:<?php echo esc_attr( $primary_color ); ?>;"></div>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="secondary_color"><?php esc_html_e( 'Secondary Color', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<div class="aipb-color-picker">
								<input
									type="text"
									id="secondary_color"
									name="secondary_color"
									class="aipb-color-input aipb-wp-color-picker"
									value="<?php echo esc_attr( $secondary_color ); ?>"
									data-default-color="#005177"
								>
								<div class="aipb-color-swatch" style="background-color:<?php echo esc_attr( $secondary_color ); ?>;"></div>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="accent_color"><?php esc_html_e( 'Accent Color', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<div class="aipb-color-picker">
								<input
									type="text"
									id="accent_color"
									name="accent_color"
									class="aipb-color-input aipb-wp-color-picker"
									value="<?php echo esc_attr( $accent_color ); ?>"
									data-default-color="#00aadc"
								>
								<div class="aipb-color-swatch" style="background-color:<?php echo esc_attr( $accent_color ); ?>;"></div>
							</div>
						</td>
					</tr>
				</table>
			</div>

			<div class="aipb-panel">
				<h2 class="aipb-panel__title"><?php esc_html_e( 'Brand Voice', 'promptpress-ai' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="font_preference"><?php esc_html_e( 'Font Preference', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<select id="font_preference" name="font_preference" class="aipb-select">
								<option value="system" <?php selected( $font_preference, 'system' ); ?>><?php esc_html_e( 'System Default', 'promptpress-ai' ); ?></option>
								<option value="sans-serif" <?php selected( $font_preference, 'sans-serif' ); ?>><?php esc_html_e( 'Sans Serif', 'promptpress-ai' ); ?></option>
								<option value="serif" <?php selected( $font_preference, 'serif' ); ?>><?php esc_html_e( 'Serif', 'promptpress-ai' ); ?></option>
								<option value="mono" <?php selected( $font_preference, 'mono' ); ?>><?php esc_html_e( 'Monospace', 'promptpress-ai' ); ?></option>
								<option value="display" <?php selected( $font_preference, 'display' ); ?>><?php esc_html_e( 'Display', 'promptpress-ai' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="writing_tone"><?php esc_html_e( 'Writing Tone', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<select id="writing_tone" name="writing_tone" class="aipb-select">
								<option value="professional" <?php selected( $writing_tone, 'professional' ); ?>><?php esc_html_e( 'Professional', 'promptpress-ai' ); ?></option>
								<option value="casual" <?php selected( $writing_tone, 'casual' ); ?>><?php esc_html_e( 'Casual', 'promptpress-ai' ); ?></option>
								<option value="friendly" <?php selected( $writing_tone, 'friendly' ); ?>><?php esc_html_e( 'Friendly', 'promptpress-ai' ); ?></option>
								<option value="formal" <?php selected( $writing_tone, 'formal' ); ?>><?php esc_html_e( 'Formal', 'promptpress-ai' ); ?></option>
								<option value="persuasive" <?php selected( $writing_tone, 'persuasive' ); ?>><?php esc_html_e( 'Persuasive', 'promptpress-ai' ); ?></option>
								<option value="informative" <?php selected( $writing_tone, 'informative' ); ?>><?php esc_html_e( 'Informative', 'promptpress-ai' ); ?></option>
								<option value="enthusiastic" <?php selected( $writing_tone, 'enthusiastic' ); ?>><?php esc_html_e( 'Enthusiastic', 'promptpress-ai' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="target_audience"><?php esc_html_e( 'Target Audience', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="target_audience"
								name="target_audience"
								class="regular-text"
								value="<?php echo esc_attr( $target_audience ); ?>"
								placeholder="<?php esc_attr_e( 'e.g., Small business owners, marketing professionals', 'promptpress-ai' ); ?>"
							>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="brand_instructions"><?php esc_html_e( 'Brand Instructions', 'promptpress-ai' ); ?></label>
						</th>
						<td>
							<textarea
								id="brand_instructions"
								name="brand_instructions"
								class="large-text"
								rows="5"
								placeholder="<?php esc_attr_e( 'Additional instructions for the AI about your brand voice, terminology to use or avoid, etc.', 'promptpress-ai' ); ?>"
							><?php echo esc_textarea( $brand_instructions ); ?></textarea>
							<p class="description"><?php esc_html_e( 'These instructions will be included in every AI generation prompt.', 'promptpress-ai' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<?php submit_button( __( 'Save Brand Settings', 'promptpress-ai' ), 'primary', 'submit', false ); ?>
	</form>
</div>
