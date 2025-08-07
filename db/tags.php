<?php

namespace Groundhogg\DB;

use function Groundhogg\swap_array_keys;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tags DB
 *
 * Store tags
 *
 * @since       File available since Release 0.1
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Tags extends DB {

	/**
	 * Runtime associative array of ID => tag_object
	 *
	 * @var array
	 */
	public $tag_cache = [];

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_tags';
	}

	/**
	 * Get the DB primary key
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return 'tag_id';
	}

	/**
	 * Get the DB version
	 *
	 * @return mixed
	 */
	public function get_db_version() {
		return '2.0';
	}

	/**
	 * Get the object type we're inserting/updateing/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'tag';
	}

	protected function add_additional_actions() {

	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return array(
			'tag_id'             => '%d',
			'tag_name'           => '%s',
			'tag_slug'           => '%s',
			'tag_description'    => '%s',
			'show_as_preference' => '%d',
		);
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_column_defaults() {
		return array(
			'tag_id'             => 0,
			'tag_name'           => '',
			'tag_slug'           => '',
			'tag_description'    => '',
			'show_as_preference' => 0,
		);
	}

	/**
	 * Fix some queries that aren't working
	 *
	 * @param $data
	 * @param $ORDER_BY
	 * @param $from_cache
	 *
	 * @return array|array[]|bool|int|object|object[]|null
	 */
	public function query( $data = [], $ORDER_BY = '', $from_cache = true ) {

		$data = swap_array_keys( $data, [
			'id' => 'tag_id',
			'ID' => 'tag_id',
		] );

		return parent::query( $data, $ORDER_BY, $from_cache );
	}

	/**
	 * Given a list of tags, make sure that the tags exist, if they don't add/or remove them
	 *
	 * @param array $maybe_tags
	 *
	 * @return array $tags
	 */
	public function validate( $maybe_tags = array() ) {

		$tags = array();

		if ( ! is_array( $maybe_tags ) ) {
			$maybe_tags = array( $maybe_tags );
		}

		foreach ( $maybe_tags as $i => $tag_id_or_string ) {

			if ( is_numeric( $tag_id_or_string ) ) {

				$tag_id = intval( $tag_id_or_string );

				if ( $this->exists( $tag_id ) ) {
					$tags[] = $tag_id;
				}

			} else if ( is_string( $tag_id_or_string ) ) {

				$slug = sanitize_title( $tag_id_or_string );

				if ( $this->exists( $slug, 'tag_slug' ) ) {
					$tag    = $this->get_tag_by( 'tag_slug', $slug );
					$tags[] = $tag->tag_id;

				} else {

					// Only add if the current user is allowed to do so.
					if ( current_user_can( 'add_tags' ) ) {
						$tags[] = $this->add( array( 'tag_name' => sanitize_text_field( $tag_id_or_string ) ) );
					}
				}
			}
		}

		return $tags;
	}

	/**
	 * Add a tag
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function add( $data = array() ) {

		$args = wp_parse_args(
			$data,
			$this->get_column_defaults()
		);

		if ( empty( $args['tag_name'] ) ) {
			return false;
		}

		$args['tag_slug'] = sanitize_title( $args['tag_name'] );
		if ( $this->exists( $args['tag_slug'], 'tag_slug' ) ) {
			$tag = $this->get_tag_by( 'tag_slug', $args['tag_slug'] );

			return $tag->tag_id;
		}

		return $this->insert( $args );
	}

	/**
	 * Delete a tag
	 *
	 * @access  public
	 * @since   2.3.1
	 */
	public function delete( $id = false ) {

		if ( empty( $id ) ) {
			return false;
		}

		return parent::delete( $id );
	}

	/**
	 * Retrieves a single tag from the database
	 *
	 * @access public
	 *
	 * @since  2.3
	 *
	 * @param mixed  $value The Customer ID or email to search
	 *
	 * @param string $field id or email
	 *
	 * @return mixed          Upon success, an object of the tag. Upon failure, NULL
	 */
	public function get_tag_by( $field = 'tag_id', $value = 0 ) {
		if ( empty( $field ) || empty( $value ) ) {
			return null;
		}

		if ( 'tag_id' == $field ) {
			// Make sure the value is numeric to avoid casting objects, for example,
			// to int 1.
			if ( ! is_numeric( $value ) ) {
				return false;
			}

			$value = intval( $value );

			if ( $value < 1 ) {
				return false;
			}

		} else if ( 'tag_slug' == $field ) {
			if ( ! is_string( $value ) ) {
				return false;
			}
		}

		if ( ! $value ) {
			return false;
		}

		$results = $this->get_by( $field, $value );

		if ( empty( $results ) ) {
			return false;
		}

		return $results;
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
        tag_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        tag_slug varchar({$this->get_max_index_length()}) NOT NULL,
        tag_name mediumtext NOT NULL,
        tag_description text NOT NULL,
        show_as_preference tinyint unsigned NOT NULL,
        PRIMARY KEY (tag_id),
        UNIQUE KEY tag_slug (tag_slug)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
