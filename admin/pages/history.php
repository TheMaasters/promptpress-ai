<?php
/**
 * History page view.
 *
 * @package AIPB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page     = 20;
$filter_status = isset( $_GET['filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_status'] ) ) : '';
$filter_editor = isset( $_GET['filter_editor'] ) ? sanitize_key( wp_unslash( $_GET['filter_editor'] ) ) : '';

$history = new \AIPB\Generation_History();
$result  = $history->get_history( array(
	'page'        => $current_page,
	'per_page'    => $per_page,
	'status'      => $filter_status,
	'editor_type' => $filter_editor,
) );

$items = isset( $result['records'] ) ? $result['records'] : array();
$total = isset( $result['total'] ) ? $result['total'] : 0;
$pages = isset( $result['pages'] ) ? $result['pages'] : 0;
?>
<div class="wrap aipb-wrap">
	<h1 class="aipb-page-title">
		<span class="dashicons dashicons-backup"></span>
		<?php esc_html_e( 'Generation History', 'promptpress-ai' ); ?>
	</h1>

	<div class="aipb-filters">
		<form method="get" action="" class="aipb-filters__form">
			<input type="hidden" name="page" value="aipb-history">

			<label for="filter-status" class="aipb-label"><?php esc_html_e( 'Status:', 'promptpress-ai' ); ?></label>
			<select id="filter-status" name="filter_status" class="aipb-select aipb-select--small">
				<option value=""><?php esc_html_e( 'All Statuses', 'promptpress-ai' ); ?></option>
				<option value="success" <?php selected( $filter_status, 'success' ); ?>><?php esc_html_e( 'Success', 'promptpress-ai' ); ?></option>
				<option value="failed" <?php selected( $filter_status, 'failed' ); ?>><?php esc_html_e( 'Failed', 'promptpress-ai' ); ?></option>
			</select>

			<label for="filter-editor" class="aipb-label"><?php esc_html_e( 'Editor:', 'promptpress-ai' ); ?></label>
			<select id="filter-editor" name="filter_editor" class="aipb-select aipb-select--small">
				<option value=""><?php esc_html_e( 'All Editors', 'promptpress-ai' ); ?></option>
				<option value="gutenberg" <?php selected( $filter_editor, 'gutenberg' ); ?>><?php esc_html_e( 'Gutenberg', 'promptpress-ai' ); ?></option>
				<option value="elementor" <?php selected( $filter_editor, 'elementor' ); ?>><?php esc_html_e( 'Elementor', 'promptpress-ai' ); ?></option>
			</select>

			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'promptpress-ai' ); ?></button>
		</form>
	</div>

	<?php if ( empty( $items ) ) : ?>
		<div class="aipb-empty-state">
			<span class="dashicons dashicons-backup"></span>
			<h2><?php esc_html_e( 'No history yet', 'promptpress-ai' ); ?></h2>
			<p><?php esc_html_e( 'Your content generation history will appear here.', 'promptpress-ai' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aipb-builder' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Start Generating', 'promptpress-ai' ); ?>
			</a>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped aipb-history-table">
			<thead>
				<tr>
					<th class="aipb-col-prompt" style="width:30%"><?php esc_html_e( 'Prompt', 'promptpress-ai' ); ?></th>
					<th class="aipb-col-type"><?php esc_html_e( 'Type', 'promptpress-ai' ); ?></th>
					<th class="aipb-col-editor"><?php esc_html_e( 'Editor', 'promptpress-ai' ); ?></th>
					<th class="aipb-col-status"><?php esc_html_e( 'Status', 'promptpress-ai' ); ?></th>
					<th class="aipb-col-date"><?php esc_html_e( 'Date', 'promptpress-ai' ); ?></th>
					<th class="aipb-col-actions"><?php esc_html_e( 'Actions', 'promptpress-ai' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $items as $item ) : ?>
					<?php
					$item_id    = isset( $item->id ) ? absint( $item->id ) : 0;
					$prompt     = isset( $item->prompt ) ? $item->prompt : '';
					$ctype      = isset( $item->post_type ) ? $item->post_type : '';
					$editor     = isset( $item->editor_type ) ? $item->editor_type : '';
					$status     = isset( $item->status ) ? $item->status : '';
					$created    = isset( $item->created_at ) ? $item->created_at : '';
					$post_id    = isset( $item->post_id ) ? absint( $item->post_id ) : 0;
					$short_prompt = mb_strlen( $prompt ) > 80 ? mb_substr( $prompt, 0, 80 ) . '...' : $prompt;
					?>
					<tr data-id="<?php echo esc_attr( $item_id ); ?>">
						<td class="aipb-col-prompt" title="<?php echo esc_attr( $prompt ); ?>">
							<?php echo esc_html( $short_prompt ); ?>
						</td>
						<td class="aipb-col-type">
							<span class="aipb-badge aipb-badge--type">
								<?php echo esc_html( ucfirst( str_replace( '_', ' ', $ctype ) ) ); ?>
							</span>
						</td>
						<td class="aipb-col-editor">
							<span class="aipb-badge aipb-badge--editor">
								<?php echo esc_html( ucfirst( $editor ) ); ?>
							</span>
						</td>
						<td class="aipb-col-status">
							<?php if ( 'success' === $status ) : ?>
								<span class="aipb-badge aipb-badge--success"><?php esc_html_e( 'Success', 'promptpress-ai' ); ?></span>
							<?php else : ?>
								<span class="aipb-badge aipb-badge--error"><?php esc_html_e( 'Failed', 'promptpress-ai' ); ?></span>
							<?php endif; ?>
						</td>
						<td class="aipb-col-date">
							<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $created ) ) ); ?>
						</td>
						<td class="aipb-col-actions">
							<?php if ( $post_id ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" class="button button-small" title="<?php esc_attr_e( 'Edit', 'promptpress-ai' ); ?>">
									<span class="dashicons dashicons-edit"></span>
								</a>
								<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="button button-small" target="_blank" title="<?php esc_attr_e( 'View', 'promptpress-ai' ); ?>">
									<span class="dashicons dashicons-visibility"></span>
								</a>
							<?php endif; ?>
							<button type="button" class="button button-small aipb-regenerate-btn" data-id="<?php echo esc_attr( $item_id ); ?>" data-prompt="<?php echo esc_attr( $prompt ); ?>" title="<?php esc_attr_e( 'Regenerate', 'promptpress-ai' ); ?>">
								<span class="dashicons dashicons-update"></span>
							</button>
							<button type="button" class="button button-small aipb-delete-btn aipb-delete-btn--danger" data-id="<?php echo esc_attr( $item_id ); ?>" title="<?php esc_attr_e( 'Delete', 'promptpress-ai' ); ?>">
								<span class="dashicons dashicons-trash"></span>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $pages > 1 ) : ?>
			<div class="aipb-pagination">
				<?php
				$pagination_args = array(
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'current'   => $current_page,
					'total'     => $pages,
					'prev_text' => '&laquo; ' . esc_html__( 'Previous', 'promptpress-ai' ),
					'next_text' => esc_html__( 'Next', 'promptpress-ai' ) . ' &raquo;',
				);

				if ( $filter_status ) {
					$pagination_args['base'] = add_query_arg( 'filter_status', $filter_status, $pagination_args['base'] );
				}
				if ( $filter_editor ) {
					$pagination_args['base'] = add_query_arg( 'filter_editor', $filter_editor, $pagination_args['base'] );
				}

				echo wp_kses_post( paginate_links( $pagination_args ) );
				?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
