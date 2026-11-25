<?php

namespace Groundhogg\DB;

use function Groundhogg\Ymd_His;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A row for each request the site makes to an AI provider through WordPress's AI client, see Groundhogg\AI\AI::run():
 * which job, for whom, with which provider and model, how many tokens it took, and whether it worked. It's what the
 * monthly limit counts, and what the owner sees of what AI is costing them.
 */
class AI_Usage extends DB {

	public function get_db_suffix() {
		return 'gh_ai_usage';
	}

	public function get_primary_key() {
		return 'ID';
	}

	public function get_db_version() {
		return '1.0';
	}

	public function get_object_type() {
		return 'ai_usage';
	}

	public function get_date_key() {
		return 'date_created';
	}

	public function get_columns() {
		return [
			'ID'                => '%d',
			'job'               => '%s',
			'user_id'           => '%d',
			'provider'          => '%s',
			'model'             => '%s',
			'prompt_tokens'     => '%d',
			'completion_tokens' => '%d',
			'total_tokens'      => '%d',
			'status'            => '%s',
			'error_code'        => '%s',
			'date_created'      => '%s',
		];
	}

	public function get_column_defaults() {
		return [
			'ID'                => 0,
			'job'               => '',
			'user_id'           => 0,
			'provider'          => '',
			'model'             => '',
			'prompt_tokens'     => 0,
			'completion_tokens' => 0,
			'total_tokens'      => 0,
			'status'            => 'complete',
			'error_code'        => '',
			'date_created'      => Ymd_His(),
		];
	}

	public function create_table() {

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$this->table_name} (
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		job varchar(50) NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		provider varchar(50) NOT NULL,
		model varchar(100) NOT NULL,
		prompt_tokens int(10) unsigned NOT NULL,
		completion_tokens int(10) unsigned NOT NULL,
		total_tokens int(10) unsigned NOT NULL,
		status varchar(20) NOT NULL,
		error_code varchar(50) NOT NULL,
		date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY (ID),
		KEY date_created (date_created),
		KEY job (job)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
