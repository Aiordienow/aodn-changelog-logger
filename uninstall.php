<?php
declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'aodn_changelog';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore

delete_option( 'aodn_cl_version' );
delete_option( 'aodn_cl_settings' );

wp_clear_scheduled_hook( 'aodn_cl_daily_purge' );
