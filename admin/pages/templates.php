<?php
/**
 * PromptPress AI template library.
 *
 * @package AIPB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_category = isset( $_GET['category'] ) ? sanitize_key( wp_unslash( $_GET['category'] ) ) : 'all';
$manager         = new \AIPB\Prompt_Manager();
$grouped         = $manager->get_templates( null );
$templates       = array();

foreach ( $grouped as $category => $items ) {
	if ( 'all' !== $active_category && $category !== $active_category ) {
		continue;
	}

	foreach ( $items as $key => $template ) {
		$template['id']       = $category . '.' . $key;
		$template['category'] = $category;
		$category_colors = array(
			'business'  => '#4f46e5',
			'ecommerce' => '#0f766e',
			'services'  => '#ea580c',
			'content'   => '#7c3aed',
		);
		$template['color'] = isset( $template['color'] ) ? $template['color'] : ( $category_colors[ $category ] ?? '#4f46e5' );
		$template['variables'] = isset( $template['variables'] ) ? $template['variables'] : array();
		$templates[]          = $template;
	}
}

$categories = array(
	'all'        => __( 'All Templates', 'promptpress-ai' ),
	'business'   => __( 'Business', 'promptpress-ai' ),
	'ecommerce'  => __( 'Ecommerce', 'promptpress-ai' ),
	'services'   => __( 'Services', 'promptpress-ai' ),
	'content'    => __( 'Content', 'promptpress-ai' ),
);

$category_icons = array(
	'business'  => 'dashicons-chart-line',
	'ecommerce' => 'dashicons-cart',
	'services'  => 'dashicons-admin-tools',
	'content'   => 'dashicons-edit-page',
);

$featured_ids = array( 'business.saas_landing', 'business.agency', 'ecommerce.product_landing' );
$brand_settings = get_option( 'aipb_brand_settings', array() );
$brand_name = ! empty( $brand_settings['brand_name'] ) ? sanitize_text_field( $brand_settings['brand_name'] ) : __( 'Your Brand', 'promptpress-ai' );
$brand_tone = ! empty( $brand_settings['writing_tone'] ) ? sanitize_text_field( $brand_settings['writing_tone'] ) : 'professional';
$brand_description = ! empty( $brand_settings['brand_description'] ) ? sanitize_text_field( $brand_settings['brand_description'] ) : __( 'your product or service', 'promptpress-ai' );
$brand_colors = trim( implode( ', ', array_filter( array(
	! empty( $brand_settings['primary_color'] ) ? sanitize_hex_color( $brand_settings['primary_color'] ) : '',
	! empty( $brand_settings['secondary_color'] ) ? sanitize_hex_color( $brand_settings['secondary_color'] ) : '',
	! empty( $brand_settings['accent_color'] ) ? sanitize_hex_color( $brand_settings['accent_color'] ) : '',
) ) ) );
?>
<div class="wrap aipb-wrap aipb-templates-page">

	<div class="aipb-template-hero">
		<div class="aipb-template-hero__content">
			<div class="aipb-template-hero__eyebrow">
				<span class="dashicons dashicons-sparkles"></span>
				<?php esc_html_e( 'Prompt Library', 'promptpress-ai' ); ?>
			</div>
			<h1><?php esc_html_e( 'Start with a proven idea.', 'promptpress-ai' ); ?></h1>
			<p><?php esc_html_e( 'Choose a template, customize the prompt, and let PromptPress AI turn it into an editable WordPress page or post.', 'promptpress-ai' ); ?></p>
			<div class="aipb-template-hero__stats">
				<span><strong><?php echo esc_html( count( $templates ) ); ?></strong> <?php esc_html_e( 'ready-made prompts', 'promptpress-ai' ); ?></span>
				<span><strong>4</strong> <?php esc_html_e( 'categories', 'promptpress-ai' ); ?></span>
				<span><strong>2</strong> <?php esc_html_e( 'editors', 'promptpress-ai' ); ?></span>
			</div>
		</div>
		<div class="aipb-template-hero__visual" aria-hidden="true">
			<div class="aipb-template-hero__glow aipb-template-hero__glow--one"></div>
			<div class="aipb-template-hero__glow aipb-template-hero__glow--two"></div>
			<div class="aipb-template-hero__window">
				<div class="aipb-template-hero__windowbar"><i></i><i></i><i></i></div>
				<div class="aipb-template-hero__windowbody">
					<div class="aipb-template-hero__mini-title"></div>
					<div class="aipb-template-hero__mini-line"></div>
					<div class="aipb-template-hero__mini-line aipb-template-hero__mini-line--short"></div>
					<div class="aipb-template-hero__mini-grid"><b></b><b></b><b></b></div>
				</div>
			</div>
		</div>
	</div>

	<div class="aipb-template-toolbar">
		<div class="aipb-template-tabs">
			<?php foreach ( $categories as $cat_key => $cat_label ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=aipb-templates&category=' . $cat_key ) ); ?>" class="aipb-template-tab <?php echo $active_category === $cat_key ? 'is-active' : ''; ?>">
					<?php if ( isset( $category_icons[ $cat_key ] ) ) : ?>
						<span class="dashicons <?php echo esc_attr( $category_icons[ $cat_key ] ); ?>"></span>
					<?php endif; ?>
					<?php echo esc_html( $cat_label ); ?>
				</a>
			<?php endforeach; ?>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=aipb-builder' ) ); ?>" class="button aipb-template-builder-link">
			<span class="dashicons dashicons-plus-alt2"></span>
			<?php esc_html_e( 'Start from Scratch', 'promptpress-ai' ); ?>
		</a>
	</div>

	<?php if ( empty( $templates ) ) : ?>
		<div class="aipb-empty-state">
			<span class="dashicons dashicons-layout"></span>
			<h2><?php esc_html_e( 'No templates found', 'promptpress-ai' ); ?></h2>
			<p><?php esc_html_e( 'Try another category.', 'promptpress-ai' ); ?></p>
		</div>
	<?php else : ?>
		<div class="aipb-template-grid aipb-template-grid--premium">
			<?php foreach ( $templates as $index => $template ) : ?>
				<?php
				$tpl_id         = $template['id'];
				$tpl_name       = isset( $template['name'] ) ? $template['name'] : '';
				$tpl_description = isset( $template['description'] ) ? $template['description'] : '';
				$tpl_prompt     = isset( $template['prompt'] ) ? $template['prompt'] : '';
				$tpl_category   = isset( $template['category'] ) ? $template['category'] : 'content';
				$color_primary  = sanitize_hex_color( $template['color'] ) ?: '#4f46e5';
				$icon           = isset( $category_icons[ $tpl_category ] ) ? $category_icons[ $tpl_category ] : 'dashicons-layout';
				$is_featured    = in_array( $tpl_id, $featured_ids, true );
				$variables      = array_map( 'sanitize_text_field', (array) $template['variables'] );
				$tpl_prompt_for_builder = strtr(
					$tpl_prompt,
					array(
						'{brand_name}'   => $brand_name,
						'{tone}'         => $brand_tone,
						'{language}'     => 'English',
						'{industry}'    => 'your industry',
						'{features}'    => 'your key services and features',
						'{pages}'       => 'Home, About, Services, Contact',
						'{color_scheme}'=> $brand_colors ?: 'a cohesive modern color palette',
						'{product_name}' => $brand_name,
						'{description}' => $brand_description,
					)
				);
				?>
				<article class="aipb-template-card aipb-template-card--<?php echo esc_attr( $tpl_category ); ?> <?php echo $is_featured ? 'is-featured' : ''; ?>" style="--template-accent:<?php echo esc_attr( $color_primary ); ?>;" data-template-id="<?php echo esc_attr( $tpl_id ); ?>">
					<div class="aipb-template-card__visual">
						<div class="aipb-template-card__visual-top">
							<span class="aipb-template-card__visual-brand"><span class="dashicons <?php echo esc_attr( $icon ); ?>"></span></span>
							<span class="aipb-template-card__visual-pill"><?php echo esc_html( ucfirst( $tpl_category ) ); ?></span>
						</div>
						<div class="aipb-template-card__mock">
							<div class="aipb-template-card__mock-hero">
								<span></span><i></i><b></b>
							</div>
							<div class="aipb-template-card__mock-grid">
								<i></i><i></i><i></i>
							</div>
							<div class="aipb-template-card__mock-footer"></div>
						</div>
						<?php if ( $is_featured ) : ?>
							<span class="aipb-template-card__featured"><span class="dashicons dashicons-star-filled"></span><?php esc_html_e( 'Popular', 'promptpress-ai' ); ?></span>
						<?php endif; ?>
					</div>

					<div class="aipb-template-card__body">
						<div class="aipb-template-card__meta">
							<span><?php echo esc_html( ucfirst( $tpl_category ) ); ?></span>
							<span>•</span>
							<span><?php echo esc_html( $tpl_category === 'content' ? 'Article' : 'Page' ); ?></span>
						</div>
						<h2><?php echo esc_html( $tpl_name ); ?></h2>
						<p><?php echo esc_html( $tpl_description ); ?></p>

						<?php if ( ! empty( $variables ) ) : ?>
							<div class="aipb-template-tags">
								<?php foreach ( array_slice( $variables, 0, 3 ) as $variable ) : ?>
									<span><?php echo esc_html( str_replace( array( '{', '}' ), '', $variable ) ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<div class="aipb-template-card__actions">
							<button type="button" class="button aipb-template-preview-btn" data-name="<?php echo esc_attr( $tpl_name ); ?>" data-category="<?php echo esc_attr( ucfirst( $tpl_category ) ); ?>" data-description="<?php echo esc_attr( $tpl_description ); ?>" data-prompt="<?php echo esc_attr( $tpl_prompt_for_builder ); ?>" data-variables="<?php echo esc_attr( wp_json_encode( $variables ) ); ?>">
								<?php esc_html_e( 'Preview', 'promptpress-ai' ); ?>
							</button>
							<button type="button" class="button button-primary aipb-use-template-btn" data-prompt="<?php echo esc_attr( $tpl_prompt_for_builder ); ?>">
								<?php esc_html_e( 'Use Template', 'promptpress-ai' ); ?>
								<span class="dashicons dashicons-arrow-right-alt2"></span>
							</button>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="aipb-template-modal" id="aipb-template-modal" hidden>
		<div class="aipb-template-modal__backdrop" data-template-close></div>
		<div class="aipb-template-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="aipb-template-modal-title">
			<button type="button" class="aipb-template-modal__close" data-template-close aria-label="<?php esc_attr_e( 'Close preview', 'promptpress-ai' ); ?>">×</button>
			<div class="aipb-template-modal__eyebrow" id="aipb-template-modal-category"></div>
			<h2 id="aipb-template-modal-title"></h2>
			<p id="aipb-template-modal-description"></p>
			<div class="aipb-template-modal__section">
				<strong><?php esc_html_e( 'Prompt preview', 'promptpress-ai' ); ?></strong>
				<pre id="aipb-template-modal-prompt"></pre>
			</div>
			<div class="aipb-template-modal__actions">
				<button type="button" class="button" data-template-close><?php esc_html_e( 'Close', 'promptpress-ai' ); ?></button>
				<button type="button" class="button button-primary" id="aipb-modal-use-template"><?php esc_html_e( 'Use This Template', 'promptpress-ai' ); ?></button>
			</div>
		</div>
	</div>
</div>
