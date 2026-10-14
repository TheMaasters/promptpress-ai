<?php
/**
 * Generation History - tracks AI generation requests and results.
 *
 * @package AIPB
 */

namespace AIPB;

defined( 'ABSPATH' ) || exit;

/**
 * Class Generation_History
 *
 * Manages the custom database table that logs every AI generation
 * attempt, its status, token usage, and associated post.
 */
class Generation_History {

	/**
	 * Table name (without prefix).
	 *
	 * @var string
	 */
	const TABLE_NAME = 'aipb_generation_history';

	/**
	 * Return the full table name including the WordPress prefix.
	 *
	 * @return string
	 */
	private function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Create or update the custom table via dbDelta.
	 *
	 * @return void
	 */
	public function create_table() {
		global $wpdb;

		$table_name      = $this->get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			prompt TEXT NOT NULL,
			post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			post_type VARCHAR(50) NOT NULL DEFAULT 'page',
			editor_type VARCHAR(50) NOT NULL DEFAULT 'gutenberg',
			ai_provider VARCHAR(50) NOT NULL DEFAULT '',
			model VARCHAR(100) NOT NULL DEFAULT '',
			status VARCHAR(30) NOT NULL DEFAULT 'pending',
			tokens_used INT UNSIGNED NOT NULL DEFAULT 0,
			error_message TEXT NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a generation log record.
	 *
	 * @param array $data {
	 *     @type string $prompt       The prompt used.
	 *     @type int    $post_id      Associated post ID.
	 *     @type string $post_type    Post type.
	 *     @type string $editor_type  Editor type.
	 *     @type string $ai_provider  Provider slug.
	 *     @type string $model        Model identifier.
	 *     @type string $status       Current status.
	 *     @type int    $tokens_used  Token count.
	 *     @type string $error_message Error details.
	 * }
	 *
	 * @return int|\WP_Error Inserted row ID on success.
	 */
	public function log_generation( array $data ) {
		global $wpdb;

		$defaults = array(
			'prompt'         => '',
			'post_id'        => 0,
			'post_type'      => 'page',
			'editor_type'    => 'gutenberg',
			'ai_provider'    => '',
			'model'          => '',
			'status'         => 'pending',
			'tokens_used'    => 0,
			'error_message'  => '',
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			$this->get_table_name(),
			array(
				'prompt'         => $data['prompt'],
				'post_id'        => absint( $data['post_id'] ),
				'post_type'      => sanitize_text_field( $data['post_type'] ),
				'editor_type'    => sanitize_text_field( $data['editor_type'] ),
				'ai_provider'    => sanitize_text_field( $data['ai_provider'] ),
				'model'          => sanitize_text_field( $data['model'] ),
				'status'         => sanitize_text_field( $data['status'] ),
				'tokens_used'    => absint( $data['tokens_used'] ),
				'error_message'  => sanitize_textarea_field( $data['error_message'] ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		if ( false === $result ) {
			return new \WP_Error(
				'aipb_history_insert_failed',
				__( 'Failed to save generation history record.', 'promptpress-ai' )
			);
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update the status of an existing record.
	 *
	 * @param int    $id    Record ID.
	 * @param string $status New status value.
	 * @param array  $extra  Additional columns to update.
	 *
	 * @return bool True on success.
	 */
	public function update_status( int $id, string $status, array $extra = array() ) {
		global $wpdb;

		$update = array( 'status' => sanitize_text_field( $status ) );
		$format = array( '%s' );

		if ( isset( $extra['tokens_used'] ) ) {
			$update['tokens_used'] = absint( $extra['tokens_used'] );
			$format[]              = '%d';
		}

		if ( isset( $extra['error_message'] ) ) {
			$update['error_message'] = sanitize_textarea_field( $extra['error_message'] );
			$format[]                = '%s';
		}

		if ( isset( $extra['post_id'] ) ) {
			$update['post_id'] = absint( $extra['post_id'] );
			$format[]          = '%d';
		}

		$result = $wpdb->update(
			$this->get_table_name(),
			$update,
			array( 'id' => absint( $id ) ),
			$format,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Retrieve a paginated list of generation records.
	 *
	 * @param array $args {
	 *     @type int    $page        Page number. Default 1.
	 *     @type int    $per_page    Items per page. Default 20.
	 *     @type string $status      Filter by status.
	 *     @type string $editor_type Filter by editor type.
	 *     @type string $post_type   Filter by post type.
	 *     @type string $provider    Filter by AI provider.
	 *     @type string $search      Search prompt text.
	 *     @type string $orderby     Order by column. Default 'created_at'.
	 *     @type string $order       ASC or DESC. Default 'DESC'.
	 * }
	 *
	 * @return array { records: array, total: int, pages: int }
	 */
	public function get_history( array $args = array() ) {
		global $wpdb;

		$defaults = array(
			'page'        => 1,
			'per_page'    => 20,
			'status'      => '',
			'editor_type' => '',
			'post_type'   => '',
			'provider'    => '',
			'search'      => '',
			'orderby'     => 'created_at',
			'order'       => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );
		$table = $this->get_table_name();

		$where   = array( '1=1' );
		$prepare = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]   = 'status = %s';
			$prepare[] = $args['status'];
		}

		if ( ! empty( $args['editor_type'] ) ) {
			$where[]   = 'editor_type = %s';
			$prepare[] = $args['editor_type'];
		}

		if ( ! empty( $args['post_type'] ) ) {
			$where[]   = 'post_type = %s';
			$prepare[] = $args['post_type'];
		}

		if ( ! empty( $args['provider'] ) ) {
			$where[]   = 'ai_provider = %s';
			$prepare[] = $args['provider'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]   = 'prompt LIKE %s';
			$prepare[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";

		if ( ! empty( $prepare ) ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $prepare ) );
		} else {
			$total = (int) $wpdb->get_var( $count_sql );
		}

		$allowed_orderby = array( 'created_at', 'id', 'tokens_used', 'status' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$per_page        = max( 1, absint( $args['per_page'] ) );
		$page            = max( 1, absint( $args['page'] ) );
		$offset          = ( $page - 1 ) * $per_page;
		$pages           = (int) ceil( $total / $per_page );

		$query_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$prepare[] = $per_page;
		$prepare[] = $offset;

		$records = $wpdb->get_results( $wpdb->prepare( $query_sql, $prepare ) );

		return array(
			'records' => is_array( $records ) ? $records : array(),
			'total'   => $total,
			'pages'   => $pages,
		);
	}

	/**
	 * Get a single record by ID.
	 *
	 * @param int $id Record ID.
	 *
	 * @return object|null Row object or null.
	 */
	public function get_record( int $id ) {
		global $wpdb;

		$table = $this->get_table_name();

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
		);
	}

	/**
	 * Delete a single record.
	 *
	 * @param int $id Record ID.
	 *
	 * @return bool True on success.
	 */
	public function delete_record( int $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->get_table_name(),
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete all generation history records.
	 *
	 * @return bool True on success.
	 */
	public function delete_all() {
		global $wpdb;

		$result = $wpdb->query( "TRUNCATE TABLE {$this->get_table_name()}" );

		return false !== $result;
	}

	/**
	 * Return aggregate statistics for the history table.
	 *
	 * @return array {
	 *     @type int   $total          Total generations.
	 *     @type array $by_status      Counts grouped by status.
	 *     @type array $by_editor_type Counts grouped by editor type.
	 * }
	 */
	public function get_stats() {
		global $wpdb;

		$table = $this->get_table_name();

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		$status_rows = $wpdb->get_results(
			"SELECT status, COUNT(*) as cnt FROM {$table} GROUP BY status",
			OBJECT_K
		);

		$by_status = array();
		foreach ( $status_rows as $row ) {
			$by_status[ $row->status ] = (int) $row->cnt;
		}

		$editor_rows = $wpdb->get_results(
			"SELECT editor_type, COUNT(*) as cnt FROM {$table} GROUP BY editor_type",
			OBJECT_K
		);

		$by_editor_type = array();
		foreach ( $editor_rows as $row ) {
			$by_editor_type[ $row->editor_type ] = (int) $row->cnt;
		}

		$post_type_rows = $wpdb->get_results(
			"SELECT post_type, COUNT(*) as cnt FROM {$table} GROUP BY post_type",
			OBJECT_K
		);

		$by_post_type = array();
		foreach ( $post_type_rows as $row ) {
			$by_post_type[ $row->post_type ] = (int) $row->cnt;
		}

		$estimated_tokens = (int) $wpdb->get_var( "SELECT COALESCE(SUM(tokens_used),0) FROM {$table}" );

		return array(
			'total'             => $total,
			'by_status'         => $by_status,
			'by_editor_type'    => $by_editor_type,
			'by_post_type'      => $by_post_type,
			'estimated_tokens'  => $estimated_tokens,
		);
	}
}
