<?php
/**
 * Plugin Name:       AODN Changelog Logger
 * Plugin URI:        https://aiordienow.com/plugins/changelog-logger
 * Description:       Automatically logs every WordPress core, plugin, and theme update with version history, timestamps, and user attribution. Keep a full audit trail of every change on your site.
 * Version:           1.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.2
 * Author:            AI Or Die Now
 * Author URI:        https://aiordienow.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aodn-changelog-logger
 * Domain Path:       /languages
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AODN_CL_VERSION', '1.1.0' );
define( 'AODN_CL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AODN_CL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AODN_CL_TABLE_NAME', 'aodn_changelog' );

require_once AODN_CL_PLUGIN_DIR . 'includes/class-aodn-changelog-db.php';
require_once AODN_CL_PLUGIN_DIR . 'includes/class-aodn-changelog-hooks.php';
require_once AODN_CL_PLUGIN_DIR . 'includes/class-aodn-changelog-admin.php';
require_once AODN_CL_PLUGIN_DIR . 'includes/class-aodn-changelog-export.php';

register_activation_hook( __FILE__, array( 'AODN_Changelog_DB', 'create_table' ) );
// Uninstall handled by uninstall.php

register_deactivation_hook( __FILE__, function (): void {
	wp_clear_scheduled_hook( 'aodn_cl_daily_purge' );
} );

function aodn_changelog_logger_init(): void {
	new AODN_Changelog_Hooks();
	new AODN_Changelog_Admin();
	new AODN_Changelog_Export();
}
add_action( 'plugins_loaded', 'aodn_changelog_logger_init' );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( array $links ): array {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=aodn-changelog-logger-settings' ) ) . '">' . esc_html__( 'Settings', 'aodn-changelog-logger' ) . '</a>';
	$log_link      = '<a href="' . esc_url( admin_url( 'admin.php?page=aodn-changelog-logger' ) ) . '">' . esc_html__( 'View Log', 'aodn-changelog-logger' ) . '</a>';
	array_unshift( $links, $settings_link, $log_link );
	return $links;
} );
