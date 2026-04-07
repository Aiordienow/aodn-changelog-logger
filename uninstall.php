<?php
declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$aodn_cl_table_name = $wpdb->prefix . 'aodn_changelog';
$wpdb->query( "DROP TABLE IF EXISTS {$aodn_cl_table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

delete_option( 'aodn_cl_version' );
delete_option( 'aodn_cl_settings' );

wp_clear_scheduled_hook( 'aodn_cl_daily_purge' );
