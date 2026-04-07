<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AODN_Changelog_Export {

	public function __construct() {
		add_action( 'admin_post_aodn_cl_export', array( $this, 'export_csv' ) );
	}

	public function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'aodn-changelog-logger' ) );
		}

		check_admin_referer( 'aodn_cl_export', 'nonce' );

		$args = array(
			'update_type' => isset( $_GET['filter_type'] ) ? sanitize_key( wp_unslash( $_GET['filter_type'] ) ) : '',
			'date_from'   => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
			'date_to'     => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
		);

		$rows = AODN_Changelog_DB::get_all_for_export( $args );

		$filename = 'changelog-log-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore

		// BOM for Excel UTF-8
		fputs( $output, "\xEF\xBB\xBF" ); // phpcs:ignore

		fputcsv( $output, array(
			'ID',
			'Type',
			'Item Name',
			'Slug',
			'Version From',
			'Version To',
			'Trigger',
			'User',
			'Date',
			'Notes',
		) );

		foreach ( $rows as $row ) {
			fputcsv( $output, array(
				$row['id'],
				$row['update_type'],
				$row['item_name'],
				$row['item_slug'],
				$row['version_from'],
				$row['version_to'],
				$row['update_trigger'],
				isset( $row['user_name'] ) ? $row['user_name'] : '',
				$row['update_date'],
				$row['notes'],
			) );
		}

		fclose( $output ); // phpcs:ignore
		exit;
	}
}
