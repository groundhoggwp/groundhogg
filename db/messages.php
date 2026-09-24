<?php

namespace Groundhogg\DB;

use Groundhogg\Classes\Message;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Two-way message history (sent and received) associated with an object, i.e. a contact.
 *
 * Unlike the email log this is not optional diagnostic data, it's the conversation record.
 * Outbound rows are written when we send, inbound rows will be written by whatever ingests replies.
 */
class Messages extends DB {

	public function get_db_suffix() {
		return 'gh_messages';
	}

	public function get_primary_key() {
		return 'ID';
	}

	public function get_db_version() {
		return '1.0';
	}

	public function get_object_type() {
		return 'message';
	}

	public function get_date_key() {
		return 'date_created';
	}

	public function create_object( $object ) {
		return new Message( $object );
	}

	protected function add_additional_actions() {
		parent::add_additional_actions();
		add_action( 'groundhogg/owner_deleted', [ $this, 'owner_deleted' ], 10, 2 );
		add_action( 'groundhogg/contact/merged', [ $this, 'objects_merged' ], 10, 2 );
		add_action( 'groundhogg/object_merged', [ $this, 'objects_merged' ], 10, 2 );
	}

	/**
	 * When two objects are merged, move all of "other's" messages to "orig"
	 *
	 * @param $orig  \Groundhogg\Base_Object
	 * @param $other \Groundhogg\Base_Object
	 */
	public function objects_merged( $orig, $other ) {
		$this->update( [
			'object_type' => $other->_get_object_type(),
			'object_id'   => $other->get_id()
		], [
			'object_id' => $orig->get_id()
		] );
	}

	public function owner_deleted( $prev, $new ) {
		$this->update( [
			'user_id' => $prev,
		], [
			'user_id' => $new,
		] );
	}

	public function get_columns() {
		return [
			'ID'              => '%d',
			'object_id'       => '%d',
			'object_type'     => '%s',
			'user_id'         => '%d',
			'direction'       => '%s',
			'from_address'    => '%s',
			'to_address'      => '%s',
			'subject'         => '%s',
			'content'         => '%s',
			'summary'         => '%s',
			'message_id'      => '%s',
			'in_reply_to'     => '%s',
			'thread_id'       => '%s',
			'email_id'        => '%d',
			'email_log_id'    => '%d',
			'queued_event_id' => '%d',
			'status'          => '%s',
			'date_created'    => '%s',
		];
	}

	public function get_column_defaults() {
		return [
			'ID'              => 0,
			'object_id'       => 0,
			'object_type'     => '',
			'user_id'         => 0, // the user who sent it, if outbound
			'direction'       => 'outbound',
			'from_address'    => '',
			'to_address'      => '',
			'subject'         => '',
			'content'         => '',
			'summary'         => '',
			'message_id'      => '',
			'in_reply_to'     => '',
			'thread_id'       => '',
			'email_id'        => 0,
			'email_log_id'    => 0,
			'queued_event_id' => 0,
			'status'          => 'sent',
			'date_created'    => current_time( 'mysql', true ),
		];
	}

	public function get_searchable_columns() {
		return [
			'subject',
			'content',
			'from_address',
			'to_address',
		];
	}

	public function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		object_id bigint(20) unsigned NOT NULL,
		object_type VARCHAR({$this->get_max_index_length()}) NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		direction VARCHAR(20) NOT NULL,
		from_address text NOT NULL,
		to_address text NOT NULL,
		subject mediumtext NOT NULL,
		content longtext NOT NULL,
		summary text NOT NULL,
		message_id VARCHAR({$this->get_max_index_length()}) NOT NULL,
		in_reply_to VARCHAR({$this->get_max_index_length()}) NOT NULL,
		thread_id VARCHAR({$this->get_max_index_length()}) NOT NULL,
		email_id bigint(20) unsigned NOT NULL,
		email_log_id bigint(20) unsigned NOT NULL,
		queued_event_id bigint(20) unsigned NOT NULL,
		status VARCHAR(20) NOT NULL,
		date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY (ID),
		KEY object (object_type, object_id),
		KEY thread_id (thread_id),
		KEY message_id (message_id)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
