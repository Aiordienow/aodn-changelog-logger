<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AODN_Changelog_Hooks {

	/** @var array<string, mixed> */
	private array $settings;

	public function __construct() {
		$this->settings = get_option( 'aodn_cl_settings', array(
			'log_core'           => true,
			'log_plugins'        => true,
			'log_themes'         => true,
			'auto_purge_enabled' => false,
			'auto_purge_days'    => 365,
		) );

		// Plugin updates
		if ( ! empty( $this->settings['log_plugins'] ) ) {
			add_action( 'upgrader_process_complete', array( $this, 'capture_upgrader_complete' ), 10, 2 );
		}

		// Core updates (also caught by upgrader_process_complete but we add dedicated hook)
		if ( ! empty( $this->settings['log_core'] ) ) {
			add_action( '_core_updated_successfully', array( $this, 'capture_core_update' ), 10, 1 );
		}

		// Theme updates also handled via upgrader_process_complete

		// Auto-update hooks
		add_action( 'auto_update_plugin', array( $this, 'mark_auto_trigger' ), 1, 2 );
		add_action( 'auto_update_theme', array( $this, 'mark_auto_trigger' ), 1, 2 );
		add_action( 'auto_update_core', array( $this, 'mark_auto_trigger_core' ), 1, 1 );

		// Scheduled purge
		add_action( 'aodn_cl_daily_purge', array( $this, 'maybe_purge_old_logs' ) );
		if ( ! wp_next_scheduled( 'aodn_cl_daily_purge' ) ) {
			wp_schedule_event( time(), 'daily', 'aodn_cl_daily_purge' );
		}
	}

	private bool $is_auto = false;

	public function mark_auto_trigger( $update, $item ) {
		$this->is_auto = true;
		return $update;
	}

	public function mark_auto_trigger_core( $update ) {
		$this->is_auto = true;
		return $update;
	}

	public function capture_core_update( $new_version ) {
		if ( empty( $this->settings['log_core'] ) ) {
			return;
		}

		global $wp_version;

		AODN_Changelog_DB::insert_log( array(
			'update_type'    => 'core',
			'item_name'      => 'WordPress Core',
			'item_slug'      => 'wordpress-core',
			'version_from'   => $wp_version, // This runs after update so it's technically the "new" ver — WP doesn't expose previous version easily here
			'version_to'     => $new_version,
			'update_trigger' => $this->is_auto ? 'auto' : 'manual',
		) );

		$this->is_auto = false;
	}

	public function capture_upgrader_complete( $upgrader, $hook_extra ) {
		if ( empty( $hook_extra['type'] ) ) {
			return;
		}

		$type = $hook_extra['type'];

		if ( $type === 'plugin' && empty( $this->settings['log_plugins'] ) ) {
			return;
		}
		if ( $type === 'theme' && empty( $this->settings['log_themes'] ) ) {
			return;
		}
		if ( $type === 'core' && empty( $this->settings['log_core'] ) ) {
			return;
		}

		$action  = isset( $hook_extra['action'] ) ? $hook_extra['action'] : '';

		// We only care about updates (not installs)
		if ( $action !== 'update' ) {
			return;
		}

		$trigger = $this->is_auto ? 'auto' : 'manual';

		if ( $type === 'plugin' ) {
			$plugins = isset( $hook_extra['plugins'] ) ? $hook_extra['plugins'] : array();
			if ( isset( $hook_extra['plugin'] ) ) {
				$plugins = array( $hook_extra['plugin'] );
			}
			foreach ( $plugins as $plugin_file ) {
				$this->log_plugin_update( $plugin_file, $upgrader, $trigger );
			}
		} elseif ( $type === 'theme' ) {
			$themes = isset( $hook_extra['themes'] ) ? $hook_extra['themes'] : array();
			if ( isset( $hook_extra['theme'] ) ) {
				$themes = array( $hook_extra['theme'] );
			}
			foreach ( $themes as $theme_slug ) {
				$this->log_theme_update( $theme_slug, $upgrader, $trigger );
			}
		}

		$this->is_auto = false;
	}

	private function log_plugin_update( $plugin_file, $upgrader, $trigger ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
		$plugin_data = array();

		if ( file_exists( $plugin_path ) ) {
			$plugin_data = get_plugin_data( $plugin_path, false, false );
		}

		$item_name  = ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : $plugin_file;
		$version_to = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : '';
		$slug       = dirname( $plugin_file );
		if ( $slug === '.' ) {
			$slug = basename( $plugin_file, '.php' );
		}

		// Try to get the "from" version from the skin result
		$version_from = '';
		if ( isset( $upgrader->skin->plugin_info['Version'] ) ) {
			$version_from = $upgrader->skin->plugin_info['Version'];
		}

		// Fallback: check result data
		if ( empty( $version_from ) && isset( $upgrader->result ) && is_array( $upgrader->result ) ) {
			$version_from = isset( $upgrader->result['Version'] ) ? $upgrader->result['Version'] : '';
		}

		AODN_Changelog_DB::insert_log( array(
			'update_type'    => 'plugin',
			'item_name'      => sanitize_text_field( $item_name ),
			'item_slug'      => sanitize_key( $slug ),
			'version_from'   => sanitize_text_field( $version_from ),
			'version_to'     => sanitize_text_field( $version_to ),
			'update_trigger' => $trigger,
		) );
	}

	private function log_theme_update( $theme_slug, $upgrader, $trigger ) {
		$theme      = wp_get_theme( $theme_slug );
		$item_name  = $theme->exists() ? $theme->get( 'Name' ) : $theme_slug;
		$version_to = $theme->exists() ? $theme->get( 'Version' ) : '';

		$version_from = '';
		if ( isset( $upgrader->skin->theme_info ) && is_object( $upgrader->skin->theme_info ) ) {
			$version_from = $upgrader->skin->theme_info->get( 'Version' );
		}

		AODN_Changelog_DB::insert_log( array(
			'update_type'    => 'theme',
			'item_name'      => sanitize_text_field( $item_name ),
			'item_slug'      => sanitize_key( $theme_slug ),
			'version_from'   => sanitize_text_field( $version_from ),
			'version_to'     => sanitize_text_field( $version_to ),
			'update_trigger' => $trigger,
		) );
	}

	public function maybe_purge_old_logs() {
		$settings = get_option( 'aodn_cl_settings', array() );
		if ( ! empty( $settings['auto_purge_enabled'] ) && ! empty( $settings['auto_purge_days'] ) ) {
			AODN_Changelog_DB::purge_older_than( absint( $settings['auto_purge_days'] ) );
		}
	}
}
