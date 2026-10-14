<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package AIPB
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$options = array(
	'aipb_settings',
	'aipb_brand_settings',
	'aipb_usage_data',
	'aipb_db_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Remove per-user generation rate-limit options created by PromptPress AI.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'aipb_rate_limit_%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

$table_name = $wpdb->prefix . 'aipb_generation_history';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_aipb_%' OR option_name LIKE '_transient_timeout_aipb_%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
