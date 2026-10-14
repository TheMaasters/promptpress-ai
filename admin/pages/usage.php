<?php
/**
 * Usage statistics page view.
 *
 * @package AIPB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$history = new \AIPB\Generation_History();
$stats   = $history->get_stats();

if ( is_wp_error( $stats ) ) {
	$stats = array(
		'total' => 0,
		'by_status' => array(),
		'by_editor_type' => array(),
		'by_post_type' => array(),
		'estimated_tokens' => 0,
	);
}

$total = isset( $stats['total'] ) ? absint( $stats['total'] ) : 0;
$by_status = isset( $stats['by_status'] ) ? $stats['by_status'] : array();
$by_editor = isset( $stats['by_editor_type'] ) ? $stats['by_editor_type'] : array();
$by_type = isset( $stats['by_post_type'] ) ? $stats['by_post_type'] : array();
$tokens = isset( $stats['estimated_tokens'] ) ? absint( $stats['estimated_tokens'] ) : 0;
$success = isset( $by_status['success'] ) ? absint( $by_status['success'] ) : 0;
$failed = isset( $by_status['failed'] ) ? absint( $by_status['failed'] ) : 0;

$max_editor = max( array_merge( array( 1 ), array_values( $by_editor ) ) );
$max_type   = max( array_merge( array( 1 ), array_values( $by_type ) ) );
?>
<div class="wrap aipb-wrap">
	<h1 class="aipb-page-title">
		<span class="dashicons dashicons-chart-bar"></span>
		<?php esc_html_e( 'Usage Statistics', 'promptpress-ai' ); ?>
	</h1>

	<div class="aipb-stats-overview">
		<div class="aipb-stat-card">
			<div class="aipb-stat-card__number"><?php echo esc_html( number_format_i18n( $total ) ); ?></div>
			<div class="aipb-stat-card__label"><?php esc_html_e( 'Total Generations', 'promptpress-ai' ); ?></div>
		</div>
		<div class="aipb-stat-card aipb-stat-card--success">
			<div class="aipb-stat-card__number"><?php echo esc_html( number_format_i18n( $success ) ); ?></div>
			<div class="aipb-stat-card__label"><?php esc_html_e( 'Successful', 'promptpress-ai' ); ?></div>
		</div>
		<div class="aipb-stat-card aipb-stat-card--error">
			<div class="aipb-stat-card__number"><?php echo esc_html( number_format_i18n( $failed ) ); ?></div>
			<div class="aipb-stat-card__label"><?php esc_html_e( 'Failed', 'promptpress-ai' ); ?></div>
		</div>
		<div class="aipb-stat-card aipb-stat-card--tokens">
			<div class="aipb-stat-card__number"><?php echo esc_html( number_format_i18n( $tokens ) ); ?></div>
			<div class="aipb-stat-card__label"><?php esc_html_e( 'Est. Tokens Used', 'promptpress-ai' ); ?></div>
		</div>
	</div>

	<div class="aipb-stats-grid">
		<div class="aipb-panel">
			<h2 class="aipb-panel__title"><?php esc_html_e( 'Generations by Editor', 'promptpress-ai' ); ?></h2>

			<?php if ( empty( $by_editor ) ) : ?>
				<p class="aipb-description"><?php esc_html_e( 'No data available yet.', 'promptpress-ai' ); ?></p>
			<?php else : ?>
				<div class="aipb-chart aipb-chart--horizontal">
					<?php foreach ( $by_editor as $editor_name => $editor_count ) : ?>
						<?php
						$percentage = $total > 0 ? round( ( $editor_count / $total ) * 100 ) : 0;
						$bar_width  = $max_editor > 0 ? round( ( $editor_count / $max_editor ) * 100 ) : 0;
						?>
						<div class="aipb-chart__row">
							<div class="aipb-chart__label"><?php echo esc_html( ucfirst( $editor_name ) ); ?></div>
							<div class="aipb-chart__bar-container">
								<div class="aipb-chart__bar" style="width:<?php echo esc_attr( $bar_width ); ?>%;">
									<span class="aipb-chart__bar-value"><?php echo esc_html( $editor_count ); ?></span>
								</div>
							</div>
							<div class="aipb-chart__percent"><?php echo esc_html( $percentage ); ?>%</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="aipb-chart-legend aipb-chart-legend--horizontal">
					<?php
					$colors  = array( '#0073aa', '#00aadc', '#005177', '#2ea2cc', '#d54e21' );
					$color_i = 0;
					?>
					<?php foreach ( $by_editor as $editor_name => $editor_count ) : ?>
						<span class="aipb-legend-item">
							<span class="aipb-legend-dot" style="background-color:<?php echo esc_attr( $colors[ $color_i % count( $colors ) ] ); ?>;"></span>
							<?php echo esc_html( ucfirst( $editor_name ) ); ?> (<?php echo esc_html( $editor_count ); ?>)
						</span>
						<?php $color_i++; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="aipb-panel">
			<h2 class="aipb-panel__title"><?php esc_html_e( 'Generations by Status', 'promptpress-ai' ); ?></h2>

			<?php if ( 0 === $total ) : ?>
				<p class="aipb-description"><?php esc_html_e( 'No data available yet.', 'promptpress-ai' ); ?></p>
			<?php else : ?>
				<div class="aipb-donut-chart">
					<?php
					$success_pct = $total > 0 ? round( ( $success / $total ) * 360 ) : 0;
					?>
					<div class="aipb-donut" style="background: conic-gradient(
						#46b450 0deg <?php echo esc_attr( $success_pct ); ?>deg,
						#dc3232 <?php echo esc_attr( $success_pct ); ?>deg 360deg
					);">
						<div class="aipb-donut__inner">
							<span class="aipb-donut__number"><?php echo esc_html( $total ); ?></span>
							<span class="aipb-donut__label"><?php esc_html_e( 'Total', 'promptpress-ai' ); ?></span>
						</div>
					</div>
					<div class="aipb-donut-legend">
						<div class="aipb-legend-row">
							<span class="aipb-legend-dot" style="background:#46b450;"></span>
							<span class="aipb-legend-text">
								<?php
								printf(
									/* translators: 1: success count, 2: percentage */
									esc_html__( '%1$s successful (%2$s%%)', 'promptpress-ai' ),
									number_format_i18n( $success ),
									$total > 0 ? round( ( $success / $total ) * 100 ) : 0
								);
								?>
							</span>
						</div>
						<div class="aipb-legend-row">
							<span class="aipb-legend-dot" style="background:#dc3232;"></span>
							<span class="aipb-legend-text">
								<?php
								printf(
									/* translators: 1: failed count, 2: percentage */
									esc_html__( '%1$s failed (%2$s%%)', 'promptpress-ai' ),
									number_format_i18n( $failed ),
									$total > 0 ? round( ( $failed / $total ) * 100 ) : 0
								);
								?>
							</span>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! empty( $by_type ) ) : ?>
		<div class="aipb-panel">
			<h2 class="aipb-panel__title"><?php esc_html_e( 'Generations by Content Type', 'promptpress-ai' ); ?></h2>
			<div class="aipb-chart aipb-chart--horizontal">
				<?php foreach ( $by_type as $type_name => $type_count ) : ?>
					<?php
					$bar_width = $max_type > 0 ? round( ( $type_count / $max_type ) * 100 ) : 0;
					?>
					<div class="aipb-chart__row">
						<div class="aipb-chart__label"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $type_name ) ) ); ?></div>
						<div class="aipb-chart__bar-container">
							<div class="aipb-chart__bar aipb-chart__bar--type" style="width:<?php echo esc_attr( $bar_width ); ?>%;">
								<span class="aipb-chart__bar-value"><?php echo esc_html( $type_count ); ?></span>
							</div>
						</div>
						<div class="aipb-chart__percent">
							<?php echo esc_html( $total > 0 ? round( ( $type_count / $total ) * 100 ) : 0 ); ?>%
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="aipb-panel">
		<h2 class="aipb-panel__title"><?php esc_html_e( 'What Usage Means', 'promptpress-ai' ); ?></h2>
		<p class="aipb-description"><?php esc_html_e( 'Usage counts every generation request recorded by PromptPress AI. Tokens are reported by the AI provider when available. Use History to inspect individual prompts and failures.', 'promptpress-ai' ); ?></p>
		<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=aipb-history' ) ); ?>"><?php esc_html_e( 'Open Generation History', 'promptpress-ai' ); ?></a></p>
	</div>

</div>
