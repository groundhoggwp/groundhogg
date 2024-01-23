<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email DB
 *
 * Store user emails
 *
 * @since       File available since Release 0.1
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Emails extends DB {

	/**
	 * The metadata type.
	 *
	 * @access public
	 * @since  2.8
	 * @var string
	 */
	public $meta_type = 'email';

	/**
	 * The name of the cache group.
	 *
	 * @access public
	 * @since  2.8
	 * @var string
	 */
	public $cache_group = 'emails';

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_emails';
	}

	/**
	 * Get the DB primary key
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return 'ID';
	}

	/**
	 * Get the DB version
	 *
	 * @return mixed
	 */
	public function get_db_version() {
		return '2.1';
	}

	protected function maybe_register_filters() {
		parent::maybe_register_filters();

		// From user filter
		$this->query_filters->register( 'from_user', function ( $filter, Where $where ) {
			$filter = wp_parse_args( $filter, [
				'users' => [],
			] );

			$where->in( 'from_user', wp_parse_id_list( $filter['users'] ) );
		} );

		// Author
		$this->query_filters->register( 'author', function ( $filter, Where $where ) {
			$filter = wp_parse_args( $filter, [
				'users' => [],
			] );

			$where->in( 'author', wp_parse_id_list( $filter['users'] ) );
		} );

		// Funnel filter
		$this->query_filters->register( 'funnel', function ( $filter, Where $where ) {
			$filter = wp_parse_args( $filter, [
				'funnel_id' => 0,
			] );

			$funnel_id = absint( $filter['funnel_id'] );

			$stepQuery  = new Query( 'steps' );
			$meta_alias = $stepQuery->joinMeta( 'email_id' );
			$stepQuery->where( 'step_type', 'send_email' );
			$stepQuery->setSelect( 'step_type', [ "$meta_alias.meta_value", 'email_id' ], 'funnel_id' );

			$alias = $funnel_id ? 'funnel_' . $funnel_id : 'in_funnel';

			$join = $where->query->addJoin( 'LEFT', [ $stepQuery, $alias ] );
			$join->onColumn( "email_id" );

			if ( $funnel_id ){
				$where->equals( "$alias.funnel_id", $funnel_id );
			} else {
				$where->isNotNull( "$alias.funnel_id" );
			}

			$where->query->setGroupby( 'ID' );

		} );
	}

	/**
	 * Get the object type we're inserting/updateing/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'email';
	}

	public function create_object( $object ) {
		return new Email( $object );
	}

	protected function add_additional_actions() {
		parent::add_additional_actions();
		add_action( 'groundhogg/owner_deleted', [ $this, 'owner_deleted' ], 10, 2 );
	}

	public function owner_deleted( $prev, $new ) {
		$this->update( [
			'author' => $prev,
		], [
			'author' => $new,
		] );

		$this->update( [
			'from_user' => $prev,
		], [
			'from_user' => $new,
		] );
	}


	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return [
			'ID'           => '%d',
			'subject'      => '%s',
			'title'        => '%s',
			'pre_header'   => '%s',
			'content'      => '%s',
			'plain_text'   => '%s',
			'message_type' => '%s',
			'author'       => '%d',
			'from_user'    => '%d',
			'status'       => '%s',
			'is_template'  => '%d',
			'last_updated' => '%s',
			'date_created' => '%s',
		];
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_column_defaults() {
		return [
			'ID'           => 0,
			'subject'      => '',
			'title'        => '',
			'pre_header'   => '',
			'content'      => '',
			'plain_text'   => '',
			'message_type' => 'marketing',
			'author'       => get_current_user_id(),
			'from_user'    => 0,
			'is_template'  => 0,
			'status'       => 'draft',
			'last_updated' => current_time( 'mysql' ),
			'date_created' => current_time( 'mysql' ),
		];
	}

	public function get_searchable_columns() {
		return [
			'title',
			'subject',
			'pre_header',
			'content',
			'plain_text',
		];
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        content longtext NOT NULL,
        plain_text longtext NOT NULL,
        subject text NOT NULL,
        title text NOT NULL,
        pre_header text NOT NULL,
        from_user bigint(20) unsigned NOT NULL,
        author bigint(20) unsigned NOT NULL,   
        is_template tinyint unsigned NOT NULL,   
        status VARCHAR(20) NOT NULL,
        message_type VARCHAR(20) NOT NULL,
        last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (ID)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
