<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AODN_Changelog_DB {

	public static function create_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . AODN_CL_TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			update_type varchar(20) NOT NULL DEFAULT 'plugin',
			item_name varchar(255) NOT NULL DEFAULT '',
			item_slug varchar(255) NOT NULL DEFAULT '',
			version_from varchar(50) NOT NULL DEFAULT '',
			version_to varchar(50) NOT NULL DEFAULT '',
			updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
			update_trigger varchar(20) NOT NULL DEFAULT 'manual',
			update_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			notes text DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY update_type (update_type),
			KEY update_date (update_date),
			KEY item_slug (item_slug(100))
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		add_option( 'aodn_cl_version', AODN_CL_VERSION );
		add_option( 'aodn_cl_settings', array(
			'auto_purge_enabled' => false,
			'auto_purge_days'    => 365,
			'log_core'           => true,
			'log_plugins'        => true,
			'log_themes'         => true,
		) );
	}

	public static function drop_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . AODN_CL_TABLE_NAME;
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore
		delete_option( 'aodn_cl_version' );
		delete_option( 'aodn_cl_settings' );
	}

	public static function insert_log( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . AODN_CL_TABLE_NAME;

		$defaults = array(
			'update_type'    => 'plugin',
			'item_name'      => '',
			'item_slug'      => '',
			'version_from'   => '',
			'version_to'     => '',
			'updated_by'     => get_current_user_id(),
			'update_trigger' => 'manual',
			'update_date'    => current_time( 'mysql' ),
			'notes'          => null,
		);

		$data = wp_parse_args( $data, $defaults );

		$wpdb->insert(
			$table_name,
			$data,
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	public static function get_logs( $args = array() ) {
		global $wpdb;
		$table_name = $wpdb->prefix . AODN_CL_TABLE_NAME;

		$defaults = array(
			'per_page'    => 50,
			'page'        => 1,
			'update_type' => '',
			'date_from'   => '',
			'date_to'     => '',
			'search'      => '',
			'orderby'     => 'update_date',
			'order'       => 'DESC',
		);

		$args    = wp_parse_args( $args, $defaults );
		$where   = array( '1=1' );
		$values  = array();

		if ( ! empty( $args['update_type'] ) ) {
			$where[]  = 'update_type = %s';
			$values[] = $args['update_type'];
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'update_date >= %s';
			$values[] = $args['date_from'] . ' 00:00:00';
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'update_date <= %s';
			$values[] = $args['date_to'] . ' 23:59:59';
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(item_name LIKE %s OR item_slug LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$values[] = $like;
			$values[] = $like;
		}

		$where_sql = implode( ' AND ', $where );

		$allowed_orderby = array( 'update_date', 'item_name', 'update_type', 'version_to' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'update_date';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$offset  = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );
		$limit   = absint( $args['per_page'] );

		// Count query
		$count_sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";
		if ( ! empty( $values ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $values ); // phpcs:ignore
		}
		$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore

		// Data query
		$data_sql = "SELECT l.*, u.display_name as user_name FROM {$table_name} l LEFT JOIN {$wpdb->users} u ON l.updated_by = u.ID WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$all_values = array_merge( $values, array( $limit, $offset ) );
		$data_sql = $wpdb->prepare( $data_sql, $all_values ); // phpcs:ignore

		$rows = $wpdb->get_results( $data_sql ); // phpcs:ignore

		return array(
			'rows'  => $rows,
			'total' => $total,
			'pages' => $limit > 0 ? ceil( $total / $limit ) : 1,
		);
	}

	public static function get_all_for_export( $args = array() ) {
		global $wpdb;
		$table_name = $wpdb->prefix . AODN_CL_TABLE_NAME;
		$where      = array( '1=1' );
		$values     = array();

		if ( ! empty( $args['update_type'] ) ) {
			$where[]  = 'update_type = %s';
			$values[] = $args['update_type'];
		}
		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'update_date >= %s';
			$values[] = $args['date_from'] . ' 00:00:00';
		}
		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'update_date <= %s';
			$values[] = $args['date_to'] . ' 23:59:59';
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT l.*, u.display_name as user_name FROM {$table_name} l LEFT JOIN {$wpdb->users} u ON l.updated_by = u.ID WHERE {$where_sql} ORDER BY update_date DESC";
		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values ); // phpcs:ignore
		}
		return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore
	}

	public static function delete_log( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . AODN_CL_TABLE_NAME;
		return $wpdb->delete( $table_name, array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	public static function purge_all() {
		global $wpdb;
		$table_name = $wpdb->prefix . AODN_CL_TABLE_NAME;
		return $wpdb->query( "TRUNCATE TABLE {$table_name}" ); // phpcs:ignore
	}

	public static function purge_older_than( $days ) {
		global $wpdb;
		$table_name = $wpdb->prefix . AODN_CL_TABLE_NAME;
		$cutoff     = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE update_date < %s", $cutoff ) ); // phpcs:ignore
	}

	public static function get_stats() {
		global $wpdb;
		$table_name = $wpdb->prefix . AODN_CL_TABLE_NAME;
		$stats      = array();

		$stats['total']   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ); // phpcs:ignore
		$stats['plugins'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE update_type = 'plugin'" ); // phpcs:ignore
		$stats['themes']  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE update_type = 'theme'" ); // phpcs:ignore
		$stats['core']    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE update_type = 'core'" ); // phpcs:ignore
		$stats['latest']  = $wpdb->get_var( "SELECT update_date FROM {$table_name} ORDER BY update_date DESC LIMIT 1" ); // phpcs:ignore

		return $stats;
	}
}
