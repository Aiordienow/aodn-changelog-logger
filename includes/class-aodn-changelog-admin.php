<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AODN_Changelog_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_post_aodn_cl_save_settings', array( $this, 'save_settings' ) );
	}

	public function register_menu() {
		add_menu_page(
			__( 'Changelog Logger', 'aodn-changelog-logger' ),
			__( 'Changelog Logger', 'aodn-changelog-logger' ),
			'manage_options',
			'aodn-changelog-logger',
			array( $this, 'render_log_page' ),
			'dashicons-backup',
			80
		);

		add_submenu_page(
			'aodn-changelog-logger',
			__( 'Update Log', 'aodn-changelog-logger' ),
			__( 'Update Log', 'aodn-changelog-logger' ),
			'manage_options',
			'aodn-changelog-logger',
			array( $this, 'render_log_page' )
		);

		add_submenu_page(
			'aodn-changelog-logger',
			__( 'Settings', 'aodn-changelog-logger' ),
			__( 'Settings', 'aodn-changelog-logger' ),
			'manage_options',
			'aodn-changelog-logger-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'aodn-changelog-logger' ) === false ) {
			return;
		}
		wp_enqueue_style( 'aodn-cl-admin', AODN_CL_PLUGIN_URL . 'admin/css/admin.css', array(), AODN_CL_VERSION );
		wp_enqueue_script( 'aodn-cl-admin', AODN_CL_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery' ), AODN_CL_VERSION, true );
		wp_localize_script( 'aodn-cl-admin', 'aodnCL', array(
			'nonce'          => wp_create_nonce( 'aodn_cl_nonce' ),
			'confirmDelete'  => __( 'Delete this log entry?', 'aodn-changelog-logger' ),
			'confirmPurge'   => __( 'Purge ALL log entries? This cannot be undone.', 'aodn-changelog-logger' ),
		) );
	}

	public function handle_actions() {
		if ( ! isset( $_GET['page'] ) || strpos( $_GET['page'], 'aodn-changelog-logger' ) === false ) {
			return;
		}

		// Delete single entry
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['entry_id'] ) ) {
			check_admin_referer( 'aodn_cl_delete_' . absint( $_GET['entry_id'] ) );
			if ( current_user_can( 'manage_options' ) ) {
				AODN_Changelog_DB::delete_log( absint( $_GET['entry_id'] ) );
				wp_redirect( add_query_arg( array( 'page' => 'aodn-changelog-logger', 'message' => 'deleted' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// Purge all
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'purge_all' ) {
			check_admin_referer( 'aodn_cl_purge_all' );
			if ( current_user_can( 'manage_options' ) ) {
				AODN_Changelog_DB::purge_all();
				wp_redirect( add_query_arg( array( 'page' => 'aodn-changelog-logger', 'message' => 'purged' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}
	}

	public function save_settings() {
		check_admin_referer( 'aodn_cl_save_settings' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'aodn-changelog-logger' ) );
		}

		$settings = array(
			'log_core'           => ! empty( $_POST['log_core'] ),
			'log_plugins'        => ! empty( $_POST['log_plugins'] ),
			'log_themes'         => ! empty( $_POST['log_themes'] ),
			'auto_purge_enabled' => ! empty( $_POST['auto_purge_enabled'] ),
			'auto_purge_days'    => absint( isset( $_POST['auto_purge_days'] ) ? $_POST['auto_purge_days'] : 365 ),
		);

		update_option( 'aodn_cl_settings', $settings );
		wp_redirect( add_query_arg( array( 'page' => 'aodn-changelog-logger-settings', 'message' => 'saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function render_log_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$filter_type  = isset( $_GET['filter_type'] ) ? sanitize_key( $_GET['filter_type'] ) : '';
		$filter_from  = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
		$filter_to    = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';
		$search       = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

		$results = AODN_Changelog_DB::get_logs( array(
			'update_type' => $filter_type,
			'date_from'   => $filter_from,
			'date_to'     => $filter_to,
			'search'      => $search,
			'page'        => $current_page,
			'per_page'    => 25,
		) );

		$stats = AODN_Changelog_DB::get_stats();
		$rows  = $results['rows'];
		$total = $results['total'];
		$pages = $results['pages'];

		$purge_url  = wp_nonce_url( add_query_arg( array( 'page' => 'aodn-changelog-logger', 'action' => 'purge_all' ), admin_url( 'admin.php' ) ), 'aodn_cl_purge_all' );
		$export_url = add_query_arg( array(
			'action'      => 'aodn_cl_export',
			'filter_type' => $filter_type,
			'date_from'   => $filter_from,
			'date_to'     => $filter_to,
			'nonce'       => wp_create_nonce( 'aodn_cl_export' ),
		), admin_url( 'admin-post.php' ) );
		?>
		<div class="wrap aodn-cl-wrap">
			<div class="aodn-cl-header">
				<div class="aodn-cl-header-inner">
					<h1><?php esc_html_e( 'Changelog Logger', 'aodn-changelog-logger' ); ?></h1>
					<span class="aodn-cl-badge">by AI Or Die Now</span>
				</div>
				<div class="aodn-cl-header-actions">
					<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary"><?php esc_html_e( '⬇ Export CSV', 'aodn-changelog-logger' ); ?></a>
					<a href="<?php echo esc_url( $purge_url ); ?>" class="button aodn-cl-purge-btn" id="aodn-cl-purge"><?php esc_html_e( '🗑 Purge All', 'aodn-changelog-logger' ); ?></a>
				</div>
			</div>

			<?php if ( isset( $_GET['message'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>
				<?php
				if ( $_GET['message'] === 'deleted' ) esc_html_e( 'Log entry deleted.', 'aodn-changelog-logger' );
				if ( $_GET['message'] === 'purged' ) esc_html_e( 'All log entries purged.', 'aodn-changelog-logger' );
				?>
			</p></div>
			<?php endif; ?>

			<!-- Stats Bar -->
			<div class="aodn-cl-stats-bar">
				<div class="aodn-cl-stat">
					<span class="aodn-cl-stat-number"><?php echo esc_html( $stats['total'] ); ?></span>
					<span class="aodn-cl-stat-label"><?php esc_html_e( 'Total Updates', 'aodn-changelog-logger' ); ?></span>
				</div>
				<div class="aodn-cl-stat">
					<span class="aodn-cl-stat-number"><?php echo esc_html( $stats['plugins'] ); ?></span>
					<span class="aodn-cl-stat-label"><?php esc_html_e( 'Plugins', 'aodn-changelog-logger' ); ?></span>
				</div>
				<div class="aodn-cl-stat">
					<span class="aodn-cl-stat-number"><?php echo esc_html( $stats['themes'] ); ?></span>
					<span class="aodn-cl-stat-label"><?php esc_html_e( 'Themes', 'aodn-changelog-logger' ); ?></span>
				</div>
				<div class="aodn-cl-stat">
					<span class="aodn-cl-stat-number"><?php echo esc_html( $stats['core'] ); ?></span>
					<span class="aodn-cl-stat-label"><?php esc_html_e( 'Core', 'aodn-changelog-logger' ); ?></span>
				</div>
				<?php if ( $stats['latest'] ) : ?>
				<div class="aodn-cl-stat aodn-cl-stat-last">
					<span class="aodn-cl-stat-number"><?php echo esc_html( human_time_diff( strtotime( $stats['latest'] ), time() ) . ' ago' ); ?></span>
					<span class="aodn-cl-stat-label"><?php esc_html_e( 'Last Update', 'aodn-changelog-logger' ); ?></span>
				</div>
				<?php endif; ?>
			</div>

			<!-- Filters -->
			<form method="get" class="aodn-cl-filters">
				<input type="hidden" name="page" value="aodn-changelog-logger">
				<div class="aodn-cl-filter-row">
					<select name="filter_type">
						<option value=""><?php esc_html_e( 'All Types', 'aodn-changelog-logger' ); ?></option>
						<option value="plugin" <?php selected( $filter_type, 'plugin' ); ?>><?php esc_html_e( 'Plugins', 'aodn-changelog-logger' ); ?></option>
						<option value="theme" <?php selected( $filter_type, 'theme' ); ?>><?php esc_html_e( 'Themes', 'aodn-changelog-logger' ); ?></option>
						<option value="core" <?php selected( $filter_type, 'core' ); ?>><?php esc_html_e( 'Core', 'aodn-changelog-logger' ); ?></option>
					</select>
					<input type="date" name="date_from" value="<?php echo esc_attr( $filter_from ); ?>" placeholder="<?php esc_attr_e( 'From date', 'aodn-changelog-logger' ); ?>">
					<input type="date" name="date_to" value="<?php echo esc_attr( $filter_to ); ?>" placeholder="<?php esc_attr_e( 'To date', 'aodn-changelog-logger' ); ?>">
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search plugins/themes…', 'aodn-changelog-logger' ); ?>">
					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'aodn-changelog-logger' ); ?></button>
					<?php if ( $filter_type || $filter_from || $filter_to || $search ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=aodn-changelog-logger' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Reset', 'aodn-changelog-logger' ); ?></a>
					<?php endif; ?>
				</div>
			</form>

			<!-- Log Table -->
			<table class="wp-list-table widefat fixed striped aodn-cl-table">
				<thead>
					<tr>
						<th class="aodn-cl-col-type"><?php esc_html_e( 'Type', 'aodn-changelog-logger' ); ?></th>
						<th class="aodn-cl-col-name"><?php esc_html_e( 'Item', 'aodn-changelog-logger' ); ?></th>
						<th class="aodn-cl-col-version"><?php esc_html_e( 'Version Change', 'aodn-changelog-logger' ); ?></th>
						<th class="aodn-cl-col-trigger"><?php esc_html_e( 'Trigger', 'aodn-changelog-logger' ); ?></th>
						<th class="aodn-cl-col-user"><?php esc_html_e( 'User', 'aodn-changelog-logger' ); ?></th>
						<th class="aodn-cl-col-date"><?php esc_html_e( 'Date', 'aodn-changelog-logger' ); ?></th>
						<th class="aodn-cl-col-actions"><?php esc_html_e( 'Actions', 'aodn-changelog-logger' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
					<tr>
						<td colspan="7" class="aodn-cl-empty">
							<span class="dashicons dashicons-backup"></span>
							<p><?php esc_html_e( 'No update logs yet. Updates will be logged automatically when plugins, themes, or WordPress core are updated.', 'aodn-changelog-logger' ); ?></p>
						</td>
					</tr>
					<?php else : ?>
					<?php foreach ( $rows as $row ) :
						$delete_url = wp_nonce_url(
							add_query_arg( array( 'page' => 'aodn-changelog-logger', 'action' => 'delete', 'entry_id' => $row->id ), admin_url( 'admin.php' ) ),
							'aodn_cl_delete_' . $row->id
						);
						$type_class = 'aodn-cl-type-' . esc_attr( $row->update_type );
					?>
					<tr>
						<td><span class="aodn-cl-type-badge <?php echo esc_attr( $type_class ); ?>"><?php echo esc_html( ucfirst( $row->update_type ) ); ?></span></td>
						<td class="aodn-cl-item-name">
							<strong><?php echo esc_html( $row->item_name ); ?></strong>
							<?php if ( $row->item_slug && $row->item_slug !== $row->item_name ) : ?>
							<br><small class="aodn-cl-slug"><?php echo esc_html( $row->item_slug ); ?></small>
							<?php endif; ?>
						</td>
						<td class="aodn-cl-version-change">
							<?php if ( $row->version_from ) : ?>
							<span class="aodn-cl-version-from"><?php echo esc_html( $row->version_from ); ?></span>
							<span class="aodn-cl-version-arrow">→</span>
							<?php endif; ?>
							<span class="aodn-cl-version-to"><?php echo esc_html( $row->version_to ); ?></span>
						</td>
						<td>
							<span class="aodn-cl-trigger aodn-cl-trigger-<?php echo esc_attr( $row->update_trigger ); ?>">
								<?php echo $row->update_trigger === 'auto' ? '⚡ Auto' : '👤 Manual'; ?>
							</span>
						</td>
						<td><?php echo $row->user_name ? esc_html( $row->user_name ) : '<em>' . esc_html__( 'System', 'aodn-changelog-logger' ) . '</em>'; ?></td>
						<td>
							<span title="<?php echo esc_attr( $row->update_date ); ?>">
								<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->update_date ) ) ); ?>
							</span>
						</td>
						<td>
							<a href="<?php echo esc_url( $delete_url ); ?>" class="aodn-cl-delete-link" data-confirm="<?php esc_attr_e( 'Delete this entry?', 'aodn-changelog-logger' ); ?>">
								<?php esc_html_e( 'Delete', 'aodn-changelog-logger' ); ?>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $pages > 1 ) : ?>
			<div class="aodn-cl-pagination tablenav">
				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php printf( esc_html( _n( '%s item', '%s items', $total, 'aodn-changelog-logger' ) ), number_format_i18n( $total ) ); ?>
					</span>
					<?php
					$base = add_query_arg( array( 'page' => 'aodn-changelog-logger', 'filter_type' => $filter_type, 'date_from' => $filter_from, 'date_to' => $filter_to, 's' => $search ), admin_url( 'admin.php' ) );
					echo paginate_links( array( // phpcs:ignore
						'base'    => $base . '%_%',
						'format'  => '&paged=%#%',
						'current' => $current_page,
						'total'   => $pages,
					) );
					?>
				</div>
			</div>
			<?php endif; ?>

			<p class="aodn-cl-footer-credit">
				<?php printf( esc_html__( 'AODN Changelog Logger by %s — More free tools at %s', 'aodn-changelog-logger' ), '<a href="https://aiordienow.com" target="_blank">AI Or Die Now</a>', '<a href="https://aiordienow.com/product-category/free-plugins/" target="_blank">aiordienow.com</a>' ); ?>
			</p>
		</div>
		<?php
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = get_option( 'aodn_cl_settings', array(
			'log_core'           => true,
			'log_plugins'        => true,
			'log_themes'         => true,
			'auto_purge_enabled' => false,
			'auto_purge_days'    => 365,
		) );
		?>
		<div class="wrap aodn-cl-wrap">
			<div class="aodn-cl-header">
				<div class="aodn-cl-header-inner">
					<h1><?php esc_html_e( 'Changelog Logger — Settings', 'aodn-changelog-logger' ); ?></h1>
					<span class="aodn-cl-badge">by AI Or Die Now</span>
				</div>
			</div>

			<?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'saved' ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'aodn-changelog-logger' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="aodn-cl-settings-form">
				<?php wp_nonce_field( 'aodn_cl_save_settings' ); ?>
				<input type="hidden" name="action" value="aodn_cl_save_settings">

				<div class="aodn-cl-settings-card">
					<div class="aodn-cl-settings-card-header">
						<h2><?php esc_html_e( 'Logging', 'aodn-changelog-logger' ); ?></h2>
						<p><?php esc_html_e( 'Choose which update types to track in the changelog.', 'aodn-changelog-logger' ); ?></p>
					</div>
					<div class="aodn-cl-settings-card-body">
						<div class="aodn-cl-toggle-row">
							<div class="aodn-cl-toggle-label">
								<strong><?php esc_html_e( 'Plugin Updates', 'aodn-changelog-logger' ); ?></strong>
								<span><?php esc_html_e( 'Log when plugins are updated or auto-updated', 'aodn-changelog-logger' ); ?></span>
							</div>
							<label class="aodn-cl-switch">
								<input type="checkbox" name="log_plugins" value="1" <?php checked( ! empty( $settings['log_plugins'] ) ); ?>>
								<span class="aodn-cl-switch-slider"></span>
							</label>
						</div>
						<div class="aodn-cl-toggle-row">
							<div class="aodn-cl-toggle-label">
								<strong><?php esc_html_e( 'Theme Updates', 'aodn-changelog-logger' ); ?></strong>
								<span><?php esc_html_e( 'Log when themes are updated or auto-updated', 'aodn-changelog-logger' ); ?></span>
							</div>
							<label class="aodn-cl-switch">
								<input type="checkbox" name="log_themes" value="1" <?php checked( ! empty( $settings['log_themes'] ) ); ?>>
								<span class="aodn-cl-switch-slider"></span>
							</label>
						</div>
						<div class="aodn-cl-toggle-row">
							<div class="aodn-cl-toggle-label">
								<strong><?php esc_html_e( 'WordPress Core Updates', 'aodn-changelog-logger' ); ?></strong>
								<span><?php esc_html_e( 'Log when WordPress itself is updated', 'aodn-changelog-logger' ); ?></span>
							</div>
							<label class="aodn-cl-switch">
								<input type="checkbox" name="log_core" value="1" <?php checked( ! empty( $settings['log_core'] ) ); ?>>
								<span class="aodn-cl-switch-slider"></span>
							</label>
						</div>
					</div>
				</div>

				<div class="aodn-cl-settings-card">
					<div class="aodn-cl-settings-card-header">
						<h2><?php esc_html_e( 'Auto-Purge', 'aodn-changelog-logger' ); ?></h2>
						<p><?php esc_html_e( 'Automatically clean up old log entries to keep the database lean.', 'aodn-changelog-logger' ); ?></p>
					</div>
					<div class="aodn-cl-settings-card-body">
						<div class="aodn-cl-toggle-row">
							<div class="aodn-cl-toggle-label">
								<strong><?php esc_html_e( 'Enable Auto-Purge', 'aodn-changelog-logger' ); ?></strong>
								<span><?php esc_html_e( 'Automatically delete entries older than the retention period', 'aodn-changelog-logger' ); ?></span>
							</div>
							<label class="aodn-cl-switch">
								<input type="checkbox" name="auto_purge_enabled" value="1" <?php checked( ! empty( $settings['auto_purge_enabled'] ) ); ?>>
								<span class="aodn-cl-switch-slider"></span>
							</label>
						</div>
						<div class="aodn-cl-purge-days">
							<label><?php esc_html_e( 'Keep logs for', 'aodn-changelog-logger' ); ?></label>
							<input type="number" name="auto_purge_days" min="7" max="3650" value="<?php echo esc_attr( $settings['auto_purge_days'] ); ?>">
							<label><?php esc_html_e( 'days', 'aodn-changelog-logger' ); ?></label>
						</div>
					</div>
				</div>

				<div class="aodn-cl-save-btn">
					<?php submit_button( __( 'Save Settings', 'aodn-changelog-logger' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
		</div>
		<?php
	}
}
