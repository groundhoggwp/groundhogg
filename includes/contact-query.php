<?php

namespace Groundhogg;

// Exit if accessed directly
use Groundhogg\Classes\Activity;
use Groundhogg\DB\Contacts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact query class
 *
 * This class should be used for querying contacts.
 *
 * @since       0.9
 * @copyright   Copyright (c) 2018, Groundhogg Inc. (modified from EDD)
 * @license     http://opensource.org/licenses/gpl-3.0 GNU Public License
 * @package     Includes
 */
class Contact_Query {

	/**
	 * SQL for database query.
	 *
	 * @access public
	 * @since  2.8
	 * @var    string
	 */
	public $request;

	/**
	 * Date query container.
	 *
	 * @access public
	 * @since  2.8
	 * @var    object \WP_Date_Query
	 */
	public $date_query = false;

	/**
	 * Meta query container.
	 *
	 * @access public
	 * @since  2.8
	 * @var    object \WP_Meta_Query
	 */
	public $meta_query = false;

	/**
	 * @var Tag_Query
	 */
	public $tag_query = null;

	/**
	 * Query vars set by the user.
	 *
	 * @access public
	 * @since  2.8
	 * @var    array
	 */
	public $query_vars;

	/**
	 * Default values for query vars.
	 *
	 * @access public
	 * @since  2.8
	 * @var    array
	 */
	public $query_var_defaults;

	/**
	 * List of contacts located by the query.
	 *
	 * @access public
	 * @since  2.8
	 * @var    array
	 */
	public $items;

	/**
	 * The amount of found contacts for the current query.
	 *
	 * @access public
	 * @since  2.8
	 * @var    int
	 */
	public $found_items = 0;

	/**
	 * The number of pages.
	 *
	 * @access public
	 * @since  2.8
	 * @var    int
	 */
	public $max_num_pages = 0;

	/**
	 * SQL query clauses.
	 *
	 * @access protected
	 * @since  2.8
	 * @var    array
	 */
	protected $sql_clauses = [
		'select'  => '',
		'from'    => '',
		'where'   => [],
		'groupby' => '',
		'orderby' => '',
		'limits'  => '',
	];

	/**
	 * Metadata query clauses.
	 *
	 * @access protected
	 * @since  2.8
	 * @var array
	 */
	protected $meta_query_clauses = array();

	/**
	 * Tag query clauses
	 *
	 * @var array
	 */
	protected $tag_query_clauses = array();

	/**
	 * WPGH_DB_Contacts instance.
	 *
	 * @access protected
	 * @since  2.8
	 * @var Contacts
	 */
	protected $gh_db_contacts;

	/**
	 * The name of our database table.
	 *
	 * @access protected
	 * @since  2.8
	 * @var    string
	 */
	protected $table_name;

	/**
	 * The meta type.
	 *
	 * @access protected
	 * @since  2.8
	 * @var    string
	 */
	protected $meta_type;

	/**
	 * The name of the primary column.
	 *
	 * @access protected
	 * @since  2.8
	 * @var    string
	 */
	protected $primary_key;

	/**
	 * The name of the date column.
	 *
	 * @access protected
	 * @since  2.8
	 * @var    string
	 */
	protected $date_key;

	/**
	 * The name of the cache group.
	 *
	 * @access protected
	 * @since  2.8
	 * @var    string
	 */
	protected $cache_group;

	/**
	 * Constructor.
	 *
	 * Sets up the contact query defaults and optionally runs a query.
	 *
	 * @access public
	 *
	 * @param string|array $query          {
	 *                                     Optional. Array or query string of contact query parameters. Default empty.
	 *
	 * @type int           $number         Maximum number of contacts to retrieve. Default 20.
	 * @type int           $offset         Number of contacts to offset the query. Default 0.
	 * @type string|array  $orderby        Customer status or array of statuses. To use 'meta_value'
	 *                                        or 'meta_value_num', `$meta_key` must also be provided.
	 *                                        To sort by a specific `$meta_query` clause, use that
	 *                                        clause's array key. Accepts 'ID', 'user_id', 'first_name',
	 *                                        'last_name', 'optin_status',
	 *                                        'notes', 'date_created', 'meta_value', 'meta_value_num',
	 *                                        the value of `$meta_key`, and the array keys of `$meta_query`.
	 *                                        Also accepts false, an empty array, or 'none' to disable the
	 *                                        `ORDER BY` clause. Default 'ID'.
	 * @type string        $order          How to order retrieved contacts. Accepts 'ASC', 'DESC'.
	 *                                        Default 'DESC'.
	 * @type string|array  $include        String or array of contact IDs to include. Default empty.
	 * @type string|array  $exclude        String or array of contact IDs to exclude. Default empty.
	 * @type string|array  $users_include  String or array of contact user IDs to include. Default
	 *                                        empty.
	 * @type string|array  $users_exclude  String or array of contact user IDs to exclude. Default
	 *                                        empty.
	 * @type string|array  $tags_include   String or array of tags the contact should have
	 * @type string|array  $tags_exclude   String or array of tags the contact should not have
	 * @type string|array  $email          Limit results to those contacts affiliated with one of
	 *                                        the given emails. Default empty.
	 * @type string|array  $report         array of args for an activity report.
	 * @type string        $search         Search term(s) to retrieve matching contacts for. Searches
	 *                                        through contact names. Default empty.
	 * @type string|array  $search_columns Columns to search using the value of `$search`. Default 'first_name'.
	 * @type string        $meta_key       Include contacts with a matching contact meta key.
	 *                                        Default empty.
	 * @type string        $meta_value     Include contacts with a matching contact meta value.
	 *                                        Requires `$meta_key` to be set. Default empty.
	 * @type array         $meta_query     Meta query clauses to limit retrieved contacts by.
	 *                                        See `WP_Meta_Query`. Default empty.
	 * @type array         $date_query     Date query clauses to limit retrieved contacts by.
	 *                                        See `WP_Date_Query`. Default empty.
	 * @type bool          $count          Whether to return a count (true) instead of an array of
	 *                                        contact objects. Default false.
	 * @type bool          $no_found_rows  Whether to disable the `SQL_CALC_FOUND_ROWS` query.
	 *                                        Default true.
	 * }
	 * @since  2.8
	 *
	 */
	public function __construct( $query = '', $gh_db_contacts = null ) {
		if ( $gh_db_contacts ) {
			$this->gh_db_contacts = $gh_db_contacts;
		} else {
			$this->gh_db_contacts = Plugin::$instance->dbs->get_db( 'contacts' );
		}

		$this->table_name  = $this->gh_db_contacts->get_table_name();
		$this->meta_type   = $this->gh_db_contacts->get_object_type();
		$this->primary_key = $this->gh_db_contacts->get_primary_key();
		$this->date_key    = $this->gh_db_contacts->get_date_key();
		$this->cache_group = $this->gh_db_contacts->get_cache_group();

		$defaults = array(
			'select'                 => '*',
			'number'                 => - 1,
			'limit'                  => false,
			'offset'                 => 0,
			'orderby'                => 'ID',
			'order'                  => 'DESC',
			'include'                => '',
			'exclude'                => '',
			'users_include'          => '',
			'users_exclude'          => '',
			'has_user'               => false,
			'tags_include'           => 0,
			'tags_include_needs_all' => false,
			'tags_exclude'           => 0,
			'tags_exclude_needs_all' => false,
			'tags_relation'          => 'AND',
			'tag_query'              => [],
			'optin_status'           => 'any',
			'optin_status_exclude'   => false,
			'marketable'             => 'any',
			'owner'                  => 0,
			'report'                 => false,
			'activity'               => false,
			'email'                  => '',
			'email_compare'          => '',
			'search'                 => '',
			'first_name'             => '',
			'first_name_compare'     => '',
			'last_name'              => '',
			'last_name_compare'      => '',
			'search_columns'         => array(),
			'meta_key'               => '',
			'meta_value'             => '',
			'meta_compare'           => '=',
			'meta_query'             => '',
			'date_query'             => null,
			'count'                  => false,
			'no_found_rows'          => true,
			'filters'                => [],
			'exclude_filters'        => []
		);

		/**
		 * Filter the query var defaults
		 *
		 * @param $query_var_defaults array
		 */
		$this->query_var_defaults = apply_filters( 'groundhogg/contact_query/query_var_defaults', $defaults );

		if ( ! empty( $query ) ) {
			$this->query( $query );
		}
	}

	public function __get( $name ) {
		return $this->$name;
	}

	/**
	 * Sets up the query for retrieving contacts.
	 *
	 * @access public
	 *
	 * @param string|array $query Array or query string of parameters. See WPGH_Contact_Query::__construct().
	 *
	 * @return Object[]|Contact[]|int List of contacts, or number of contacts when 'count' is passed as a query var.
	 * @since  2.8
	 *
	 * @see    WPGH_Contact_Query::__construct()
	 *
	 */
	public function query( $query, $as_contact_object = false ) {
		$this->query_vars = wp_parse_args( $query );
		$items            = $this->get_items();

		if ( $as_contact_object ) {
			$items = array_map( function ( $contact ) {
				return new Contact( $contact );
			}, $items );
		}

		return $items;
	}

	/**
	 * Retreive the SQL statement instead of the actual items
	 *
	 * @param $query
	 *
	 * @return string
	 */
	public function get_sql( $query ) {
		$this->query_vars = wp_parse_args( $query );
		$this->parse_query();
		$this->generate_request();

		return $this->request;
	}

	/**
	 * Auto set count vars
	 *
	 * @param $query
	 *
	 * @return array|int
	 */
	public function count( $query ) {

		$query['count']  = true;
		$query['offset'] = 0;
		$query['number'] = - 1;

		return $this->query( $query );
	}

	/**
	 * Set the date key
	 *
	 * @param $key
	 */
	public function set_date_key( $key ) {
		$this->date_key = $key;
	}

	/**
	 * Parses arguments passed to the contact query with default query parameters.
	 *
	 * @access protected
	 * @since  2.8
	 */
	protected function parse_query() {

		if ( isset_not_empty( $this->query_vars, 'saved_search' ) ) {
			$saved_search     = Saved_Searches::instance()->get( $this->query_vars['saved_search'] );
			$this->query_vars = wp_parse_args( $saved_search['query'], $this->query_vars );
		}

		$this->query_vars = wp_parse_args( $this->query_vars, $this->query_var_defaults );

		if ( strlen( $this->query_vars['search'] ) ) {
			$full_name = split_name( $this->query_vars['search'] );

			if ( $full_name[0] && $full_name[1] ) {
				$this->query_vars['first_name']         = $full_name[0];
				$this->query_vars['first_name_compare'] = 'starts_with';
				$this->query_vars['last_name']          = $full_name[1];
				$this->query_vars['last_name_compare']  = 'starts_with';
				unset( $this->query_vars['search'] );
			}
		}

		// Map "limit" to "number"
		if ( isset_not_empty( $this->query_vars, 'limit' ) ) {
			$this->query_vars['number'] = $this->query_vars['limit'];
			unset( $this->query_vars['limit'] );
		}

		// Only show contacts associated with the current owner...
		if ( current_user_can( 'view_contacts' ) && ! current_user_can( 'view_others_contacts' ) ) {
			$this->query_vars['owner'] = get_current_user_id();
		}

		// Fix number
		if ( intval( $this->query_vars['number'] ) < 1 ) {
			$this->query_vars['number'] = false;
		}

		$this->query_vars['offset'] = absint( $this->query_vars['offset'] );

		if ( ! empty( $this->query_vars['date_query'] ) && is_array( $this->query_vars['date_query'] ) ) {
			$this->date_query = new \WP_Date_Query( $this->query_vars['date_query'], $this->table_name . '.' . $this->date_key );
		}

		if ( $this->query_vars['meta_compare'] ) {
			$map = [
				'gt'    => '>',
				'gt_eq' => '>=',
				'lt'    => '<',
				'lt_eq' => '<=',
			];

			if ( isset_not_empty( $map, $this->query_vars['meta_compare'] ) ) {
				$this->query_vars['meta_compare'] = $map[ $this->query_vars['meta_compare'] ];
			}
		}

		$this->meta_query = new \WP_Meta_Query();
		$this->meta_query->parse_query_vars( $this->query_vars );

		if ( ! empty( $this->meta_query->queries ) ) {
			$this->meta_query_clauses = $this->meta_query->get_sql( $this->meta_type, $this->table_name, $this->primary_key, $this );
		}

		if ( ! empty( $this->query_vars['tags_include'] ) || ! empty( $this->query_vars['tags_exclude'] ) || ! empty( $this->query_vars['tag_query'] ) ) {

			$this->query_vars['tags_include'] = validate_tags( $this->query_vars['tags_include'] );
			$this->query_vars['tags_exclude'] = validate_tags( $this->query_vars['tags_exclude'] );

			$backup_query = [
				'relation' => $this->query_vars['tags_relation'],
			];

			if ( ! empty( $this->query_vars['tags_include'] ) ) {

				if ( ! empty( $this->query_vars['tags_include_needs_all'] ) ) {

					if ( ! is_array( $this->query_vars['tags_include'] ) ) {
						$this->query_vars['tags_include'] = explode( ',', $this->query_vars['tags_include'] );
					}

					foreach ( $this->query_vars['tags_include'] as $tag ) {
						$backup_query[] = [
							'tags'     => $tag,
							'field'    => 'tag_id',
							'operator' => 'IN',
						];
					}

				} else {
					$backup_query[] = [
						'tags'     => $this->query_vars['tags_include'],
						'field'    => 'tag_id',
						'operator' => 'IN',
					];
				}
			}

			if ( ! empty( $this->query_vars['tags_exclude'] ) ) {

				if ( ! empty( $this->query_vars['tags_exclude_needs_all'] ) ) {

					if ( ! is_array( $this->query_vars['tags_exclude'] ) ) {
						$this->query_vars['tags_exclude'] = explode( ',', $this->query_vars['tags_exclude'] );
					}

					foreach ( $this->query_vars['tags_exclude'] as $tag ) {
						$backup_query[] = [
							'tags'     => $tag,
							'field'    => 'tag_id',
							'operator' => 'NOT IN',
						];
					}

				} else {
					$backup_query[] = [
						'tags'     => $this->query_vars['tags_exclude'],
						'field'    => 'tag_id',
						'operator' => 'NOT IN',
					];
				}
			}

			$query = ( ! empty( $this->query_vars['tag_query'] ) ) ? $this->query_vars['tag_query'] : $backup_query;

			$this->tag_query = new Tag_Query( $query );

			if ( ! empty( $this->tag_query->queries ) ) {
				$this->tag_query_clauses = $this->tag_query->get_sql( $this->table_name, $this->primary_key );
			}
		}

		/**
		 * Fires after the contact query vars have been parsed.
		 *
		 * @param Contact_Query &$this The WPGH_Contact_Query instance (passed by reference).
		 *
		 * @since 2.8
		 *
		 */
		do_action_ref_array( 'gh_parse_contact_query', [ &$this ] );
		do_action_ref_array( 'groundhogg/contact_query/parse_query', [ &$this ] );

	}

	/**
	 * Retrieves a list of contacts matching the query vars.
	 *
	 * Tries to use a cached value and otherwise uses `WPGH_Contact_Query::query_items()`.
	 *
	 * @access protected
	 * @return array|int List of contacts, or number of contacts when 'count' is passed as a query var.
	 * @since  2.8
	 *
	 */
	protected function get_items() {
		$this->parse_query();

		/**
		 * Fires before contacts are retrieved.
		 *
		 * @param Contact_Query &$this Current instance of Contact_Query, passed by reference.
		 *
		 * @deprecated
		 */
		do_action_ref_array( 'gh_pre_get_contacts', [ &$this ] );
		do_action_ref_array( 'groundhogg/contact_query/pre_get_contacts', [ &$this ] );

		// $args can include anything. Only use the args defined in the query_var_defaults to compute the key.
		$key = md5( serialize( wp_array_slice_assoc( $this->query_vars, array_keys( $this->query_var_defaults ) ) ) );

		$last_changed = $this->gh_db_contacts->cache_get_last_changed();

		$cache_key   = "query:$key:$last_changed:$this->date_key";
		$cache_value = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $cache_value ) {
			$items = $this->query_items();

			if ( $items ) {
				$this->set_found_items();
			}

			$cache_value = array(
				'items'       => $items,
				'found_items' => $this->found_items,
			);
			wp_cache_add( $cache_key, $cache_value, $this->cache_group );
		} else {
			$items             = $cache_value['items'];
			$this->found_items = $cache_value['found_items'];
		}

		if ( $this->found_items && $this->query_vars['number'] ) {
			$this->max_num_pages = ceil( $this->found_items / $this->query_vars['number'] );
		}

		// If querying for a count only, there's nothing more to do.
		if ( $this->query_vars['count'] ) {

			// Count items will be an array of counts, so return the number of counts.
			if ( ! empty( $this->sql_clauses['groupby'] ) ) {
				return count( $items );
			}

			// $items is actually a count in this case.
			return intval( $items[0]->count );
		}

		$this->items = $items;

		return $this->items;
	}

	protected function generate_request() {
		global $wpdb;

		$fields = $this->construct_request_fields();
		$join   = $this->construct_request_join();

		$this->sql_clauses['where'] = $this->construct_request_where();

		$orderby = $this->construct_request_orderby();
		$limits  = $this->construct_request_limits();
		$groupby = $this->construct_request_groupby();

		$found_rows = ! $this->query_vars['no_found_rows'] ? 'SQL_CALC_FOUND_ROWS' : '';

		$this->sql_clauses['where'] = implode( ' AND ', $this->sql_clauses['where'] );

		if ( $this->sql_clauses['where'] ) {
			$this->sql_clauses['where'] = "WHERE {$this->sql_clauses['where']}";
		}

		if ( $orderby ) {
			$orderby = "ORDER BY $orderby";
		}

		if ( $groupby ) {
			$groupby = "GROUP BY $groupby";
		}

		$this->sql_clauses['select'] = "SELECT $found_rows $fields";
		$this->sql_clauses['from']   = "FROM $this->table_name $join";

		// No need for this in count.
		$this->sql_clauses['groupby'] = $groupby;

		if ( ! $this->query_vars['count'] ) {
			$this->sql_clauses['orderby'] = $orderby;
		}

		$this->sql_clauses['limits'] = $limits;

		/**
		 * Filter the sql clauses before they are used in building the request.
		 *
		 * @param $sql_clauses array
		 * @param $query_vars  array
		 * @param $query       Contact_Query
		 */
		$this->sql_clauses = apply_filters( 'groundhogg/contact_query/query_items/sql_clauses', $this->sql_clauses, $this->query_vars, $this );

		$this->request = "{$this->sql_clauses['select']} {$this->sql_clauses['from']} {$this->sql_clauses['where']} {$this->sql_clauses['groupby']} {$this->sql_clauses['orderby']} {$this->sql_clauses['limits']}";
	}

	/**
	 * Runs a database query to retrieve contacts.
	 *
	 * @access protected
	 * @return array|int List of contacts, or number of contacts when 'count' is passed as a query var.
	 * @since  2.8
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 *
	 */
	protected function query_items() {

		global $wpdb;

		$this->generate_request();

		return $wpdb->get_results( $this->request );
	}

	/**
	 * Populates the found_items property for the current query if the limit clause was used.
	 *
	 * @access protected
	 * @since  2.8
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	protected function set_found_items() {
		global $wpdb;

		if ( $this->query_vars['number'] && ! $this->query_vars['no_found_rows'] ) {
			/**
			 * Filters the query used to retrieve the count of found contacts.
			 *
			 * @param Contact_Query $contact_query        The `WPGH_Contact_Query` instance.
			 * @param string        $found_contacts_query SQL query. Default 'SELECT FOUND_ROWS()'.
			 *
			 * @since 2.8
			 *
			 */
			$found_items_query = apply_filters( 'gh_found_contacts_query', 'SELECT FOUND_ROWS()', $this );

			$this->found_items = (int) $wpdb->get_var( $found_items_query );
		}
	}

	/**
	 * Constructs the fields segment of the SQL request.
	 *
	 * @access protected
	 * @return string SQL fields segment.
	 * @since  2.8
	 *
	 */
	protected function construct_request_fields() {
		if ( $this->query_vars['count'] ) {
			return "COUNT($this->table_name.$this->primary_key) AS count";
		}

		return "$this->table_name.{$this->query_vars['select']}";
	}

	/**
	 * Constructs the join segment of the SQL request.
	 *
	 * @access protected
	 * @return string SQL join segment.
	 * @since  2.8
	 *
	 */
	protected function construct_request_join() {
		$join = '';

		if ( ! empty( $this->meta_query_clauses['join'] ) ) {
			$join .= $this->meta_query_clauses['join'];
		}

		if ( ! empty( $this->tag_query_clauses['join'] ) ) {
			$join .= $this->tag_query_clauses['join'];
		}

		if ( ! empty( $this->query_vars['email'] ) && ! is_array( $this->query_vars['email'] ) ) {
			$meta_table = _get_meta_table( $this->meta_type );

			$join_type = false !== strpos( $join, 'INNER JOIN' ) ? 'INNER JOIN' : 'LEFT JOIN';

			$join .= " $join_type $meta_table AS email_mt ON $this->table_name.$this->primary_key = email_mt.{$this->meta_type}_id";
		}

		return $join;
	}

	/**
	 * Constructs the where segment of the SQL request.
	 *
	 * @access protected
	 * @return array SQL where segment.
	 * @since  2.8
	 *
	 */
	protected function construct_request_where() {
		global $wpdb;

		$where = array();

		if ( ! empty( $this->query_vars['include'] ) ) {
			$include_ids      = implode( ',', wp_parse_id_list( $this->query_vars['include'] ) );
			$where['include'] = "$this->table_name.$this->primary_key IN ( $include_ids )";
		}

		if ( ! empty( $this->query_vars['exclude'] ) ) {
			$exclude_ids      = implode( ',', wp_parse_id_list( $this->query_vars['exclude'] ) );
			$where['exclude'] = "$this->table_name.$this->primary_key NOT IN ( $exclude_ids )";
		}

		if ( ! empty( $this->query_vars['users_include'] ) ) {
			$users_include_ids      = implode( ',', wp_parse_id_list( $this->query_vars['users_include'] ) );
			$where['users_include'] = "$this->table_name.user_id IN ( $users_include_ids )";
		}

		if ( $this->query_vars['marketable'] !== 'any' ) {
			$where['marketable'] = self::filter_marketability( [
				'marketable' => $this->query_vars['marketable'] ? 'yes' : 'no'
			], $this );
		}

		if ( ! empty( $this->query_vars['users_exclude'] ) ) {
			$users_exclude_ids      = implode( ',', wp_parse_id_list( $this->query_vars['users_exclude'] ) );
			$where['users_exclude'] = "$this->table_name.user_id NOT IN ( $users_exclude_ids )";
		}

		if ( ! empty( $this->query_vars['has_user'] ) ) {
			$where['has_user'] = "$this->table_name.user_id > 0";
		}

		if ( $this->query_vars['optin_status'] !== 'any' ) {

			if ( is_array( $this->query_vars['optin_status'] ) ) {
				$this->query_vars['optin_status'] = implode( ',', wp_parse_id_list( $this->query_vars['optin_status'] ) );
			} else {
				$this->query_vars['optin_status'] = absint( $this->query_vars['optin_status'] );
			}

			$where['optin_status'] = "$this->table_name.optin_status IN ( {$this->query_vars['optin_status']} )";
		}

		if ( $this->query_vars['optin_status_exclude'] !== false ) {

			if ( is_array( $this->query_vars['optin_status_exclude'] ) ) {
				$this->query_vars['optin_status_exclude'] = implode( ',', wp_parse_id_list( $this->query_vars['optin_status_exclude'] ) );
			} else {
				$this->query_vars['optin_status_exclude'] = absint( $this->query_vars['optin_status_exclude'] );
			}

			$where['optin_status_exclude'] = "$this->table_name.optin_status NOT IN ( {$this->query_vars['optin_status_exclude']} )";
		}

		if ( $this->query_vars['owner'] ) {
			$owner_clause   = implode( ',', wp_parse_id_list( $this->query_vars['owner'] ) );
			$where['owner'] = "$this->table_name.owner_id IN ( {$owner_clause} )";
		}

		if ( $this->query_vars['report'] && is_array( $this->query_vars['report'] ) ) {

			$map = [
				'step'   => 'step_id',
				'funnel' => 'funnel_id',
				'start'  => 'after',
				'end'    => 'before',
				'type'   => 'event_type',
			];

			foreach ( $map as $old_key => $new_key ) {
				if ( $val = get_array_var( $this->query_vars['report'], $old_key ) ) {
					$this->query_vars['report'][ $new_key ] = $val;
				}
			}

			$subwhere = [ 'relationship' => 'AND' ];

			foreach ( $this->query_vars['report'] as $col => $val ) {

				if ( ! empty( $val ) ) {
					switch ( $col ) {
						default:
							$compare = '=';
							break;
						case 'before':
							$compare = '<=';
							$col     = 'time';
							break;
						case 'after':
							$compare = '>=';
							$col     = 'time';
							break;
					}

					$subwhere[] = [ 'col' => $col, 'val' => $val, 'compare' => $compare ];
				}

			}

			$table = get_array_var( $this->query_vars['report'], 'status' ) === Event::WAITING ? 'event_queue' : 'events';

			$sql = get_db( $table )->get_sql( [
				'where'   => $subwhere,
				'select'  => 'contact_id',
				'orderby' => false,
				'order'   => ''
			] );

			$in = isset_not_empty( $this->query_vars['report'], 'exclude' ) ? 'NOT IN' : 'IN';

			$where['report'] = "$this->table_name.$this->primary_key $in ( $sql )";
		}

		if ( $this->query_vars['activity'] && is_array( $this->query_vars['activity'] ) ) {

			$map = [
				'step'   => 'step_id',
				'funnel' => 'funnel_id',
				'start'  => 'after',
				'end'    => 'before',
			];

			foreach ( $map as $old_key => $new_key ) {
				if ( $val = get_array_var( $this->query_vars['activity'], $old_key ) ) {
					$this->query_vars['activity'][ $new_key ] = $val;
				}
			}

			$subwhere = [ 'relationship' => 'AND' ];

			foreach ( $this->query_vars['activity'] as $col => $val ) {

				if ( ! empty( $val ) ) {
					switch ( $col ) {
						default:
							$compare = '=';
							break;
						case 'before':
							$compare = '<=';
							$col     = 'timestamp';
							break;
						case 'after':
							$compare = '>=';
							$col     = 'timestamp';
							break;
					}

					$subwhere[] = [ 'col' => $col, 'val' => $val, 'compare' => $compare ];
				}

			}

			$sql = get_db( 'activity' )->get_sql( [
				'where'   => $subwhere,
				'select'  => 'contact_id',
				'orderby' => false,
				'order'   => ''
			] );

			$in = isset_not_empty( $this->query_vars['activity'], 'exclude' ) ? 'NOT IN' : 'IN';

			$where['activity'] = "$this->table_name.$this->primary_key $in ( $sql )";
		}

		if ( strlen( $this->query_vars['search'] ) ) {

			if ( ! empty( $this->query_vars['search_columns'] ) ) {
				$search_columns = array_map( 'sanitize_key', (array) $this->query_vars['search_columns'] );
			} else {
				$search_columns = array( 'first_name', 'last_name', 'email' );
			}

			$where['search'] = $this->get_search_sql( $this->query_vars['search'], $search_columns );
		}

		if ( strlen( $this->query_vars['first_name'] ) || strlen( $this->query_vars['first_name_compare'] ) ) {

			$where['first_name'] = self::generic_text_compare( "{$this->table_name}.first_name", $this->query_vars['first_name_compare'], $this->query_vars['first_name'] );
		}

		if ( strlen( $this->query_vars['last_name'] ) || strlen( $this->query_vars['last_name_compare'] ) ) {

			$where['last_name'] = self::generic_text_compare( "{$this->table_name}.last_name", $this->query_vars['last_name_compare'], $this->query_vars['last_name'] );
		}

		if ( strlen( $this->query_vars['email'] ) || strlen( $this->query_vars['email_compare'] ) ) {
			$where['email'] = self::generic_text_compare( "{$this->table_name}.email",
				$this->query_vars['email_compare'],
				str_replace( ' ', '+', $this->query_vars['email'] ) );
		}


		if ( $this->date_query ) {
			$where['date_query'] = preg_replace( '/^\s*AND\s*/', '', $this->date_query->get_sql() );
		}

		if ( ! empty( $this->meta_query_clauses['where'] ) ) {
			$where['meta_query'] = preg_replace( '/^\s*AND\s*/', '', $this->meta_query_clauses['where'] );
		}

		if ( ! empty( $this->tag_query_clauses['where'] ) ) {
			$where['tax_query'] = preg_replace( '/^\s*AND\s*/', '', $this->tag_query_clauses['where'] );
		}

		if ( ! empty( $this->query_vars['filters'] ) ) {
			$filters = $this->parse_filters( $this->query_vars['filters'] );
			if ( ! empty( $filters ) ) {
				$where['filters'] = $filters;
			}
		}

		if ( ! empty( $this->query_vars['exclude_filters'] ) ) {

			$exclude_filters  = $this->query_vars['exclude_filters'];

			if ( ! is_array( $exclude_filters ) ) {
				$exclude_filters = base64_json_decode( $this->query_vars['exclude_filters'] );
			}

			if ( ! empty( $exclude_filters ) ) {
				$query = new Contact_Query();
				$sql   = $query->get_sql( [
					'filters' => $exclude_filters,
					'select'  => 'ID',
					'orderby' => 'none'
				] );

				$where['exclude_filters'] = "{$this->table_name}.ID NOT IN ( $sql )";
			}
		}

		if ( ! empty( $this->query_vars['date_optin_status_changed'] ) && is_array( $this->query_vars['date_optin_status_changed'] ) ) {
			$date_optin_status_changed_query    = new \WP_Date_Query( $this->query_vars['date_optin_status_changed'], $this->table_name . '.date_optin_status_changed' );
			$where['date_optin_status_changed'] = $date_optin_status_changed_query->get_sql();
		}

		/**
		 * Filter the where clauses
		 *
		 * @param $where array
		 * @param $query Contact_Query
		 */
		return apply_filters( 'groundhogg/contact_query/where_clauses', $where, $this );
	}

	/**
	 * Constructs the orderby segment of the SQL request.
	 *
	 * @access protected
	 * @return string SQL orderby segment.
	 * @since  2.8
	 *
	 */
	protected function construct_request_orderby() {
		if ( in_array( $this->query_vars['orderby'], array( 'none', array(), false ), true ) ) {
			return '';
		}

		if ( empty( $this->query_vars['orderby'] ) ) {
			return $this->primary_key . ' ' . $this->parse_order_string( $this->query_vars['order'], $this->query_vars['orderby'] );
		}

		if ( is_string( $this->query_vars['orderby'] ) ) {
			$ordersby = array( $this->query_vars['orderby'] => $this->query_vars['order'] );
		} else {
			$ordersby = $this->query_vars['orderby'];
		}

		$orderby_array = array();

		foreach ( $ordersby as $orderby => $order ) {
			$parsed_orderby = $this->parse_orderby_string( $orderby );
			if ( ! $parsed_orderby ) {
				continue;
			}

			$parsed_order = $this->parse_order_string( $order, $orderby );

			if ( $parsed_order ) {
				$orderby_array[] = $parsed_orderby . ' ' . $parsed_order;
			} else {
				$orderby_array[] = $parsed_orderby;
			}
		}

		return implode( ', ', $orderby_array );
	}

	/**
	 * Constructs the limits segment of the SQL request.
	 *
	 * @access protected
	 * @return string SQL limits segment.
	 * @since  2.8
	 *
	 */
	protected function construct_request_limits() {
		if ( $this->query_vars['number'] ) {
			if ( $this->query_vars['offset'] ) {
				return "LIMIT {$this->query_vars['offset']},{$this->query_vars['number']}";
			}

			return "LIMIT {$this->query_vars['number']}";
		}

		return '';
	}

	/**
	 * Constructs the groupby segment of the SQL request.
	 *
	 * @access protected
	 * @return string SQL groupby segment.
	 * @since  2.8
	 *
	 */
	protected function construct_request_groupby() {
		if ( ! empty( $this->meta_query_clauses['join'] )
		     || ! empty( $this->tag_query_clauses['join'] )
		     || ! empty( $this->query_vars['report'] )
		     || ! empty( $this->query_vars['activity'] )
		     || ( ! empty( $this->query_vars['email'] ) && ! is_array( $this->query_vars['email'] ) )
		) {
			return "$this->table_name.$this->primary_key";
		}

		return '';
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns.
	 *
	 * @access protected
	 *
	 * @param array  $columns Columns to search.
	 * @param string $string  Search string.
	 *
	 * @return string Search SQL.
	 * @since  2.8
	 *
	 * @global \wpdb $wpdb    WordPress database abstraction object.
	 *
	 */
	protected function get_search_sql( $string, $columns ) {
		global $wpdb;

		$string = maybe_change_space_to_plus_in_email( $string );

		if ( false !== strpos( $string, '**' ) ) {
			$like = str_replace( '**', '%', $string );
		} else if ( false !== strpos( $string, '*' ) ) {
			$like = '%' . implode( '%', array_map( array( $wpdb, 'esc_like' ), explode( '*', $string ) ) ) . '%';
		} else {
			$like = '%' . $wpdb->esc_like( $string ) . '%';
		}

		$searches = array();
		foreach ( $columns as $column ) {
			$searches[] = $wpdb->prepare( "{$this->table_name}.$column LIKE %s", $like );
		}

		return '(' . implode( ' OR ', $searches ) . ')';
	}

	/**
	 * @param $val
	 * @param $compare_type
	 *
	 * @return string
	 */
	protected function compare_string( $val, $compare_type ) {
		switch ( $compare_type ) {
			case '':
			case 'equals':
				break;
			case 'contains':
				$val = '**' . $val . '**';
				break;
			case 'starts_with':
				$val = $val . '**';
				break;
			case 'ends_with':
				$val = '**' . $val;
				break;
		}

		return $val;
	}

	/**
	 * Parses a single orderby string.
	 *
	 * @access protected
	 *
	 * @param string $orderby Orderby string.
	 *
	 * @return string Parsed orderby string to use in the SQL request, or an empty string.
	 * @since  2.8
	 *
	 */
	protected function parse_orderby_string( $orderby ) {
		if ( 'include' === $orderby ) {
			if ( empty( $this->query_vars['include'] ) ) {
				return '';
			}

			$ids = implode( ',', wp_parse_id_list( $this->query_vars['include'] ) );

			return "FIELD( $this->table_name.$this->primary_key, $ids )";
		}

		if ( ! empty( $this->meta_query_clauses['where'] ) ) {
			$meta_table = _get_meta_table( $this->meta_type );

			if ( $this->query_vars['meta_key'] === $orderby || 'meta_value' === $orderby ) {
				return "$meta_table.meta_value";
			}

			if ( 'meta_value_num' === $orderby ) {
				return "$meta_table.meta_value+0";
			}

			$meta_query_clauses = $this->meta_query->get_clauses();

			if ( isset( $meta_query_clauses[ $orderby ] ) ) {
				return sprintf( "CAST(%s.meta_value AS %s)", esc_sql( $meta_query_clauses[ $orderby ]['alias'] ), esc_sql( $meta_query_clauses[ $orderby ]['cast'] ) );
			}
		}

		$allowed_keys = $this->get_allowed_orderby_keys();

		if ( in_array( $orderby, $allowed_keys, true ) ) {
			/* This column needs special handling here. */

			return "$this->table_name.$orderby";
		}

		return empty( $orderby ) ? '' : $orderby;
	}

	/**
	 * Parses a single order string.
	 *
	 * @access protected
	 *
	 * @param string $orderby Order string.
	 *
	 * @return string Parsed order string to use in the SQL request, or an empty string.
	 * @since  2.8
	 *
	 */
	protected function parse_order_string( $order, $orderby ) {
		if ( 'include' === $orderby ) {
			return '';
		}

		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}

	/**
	 * Returns the basic allowed keys to use for the orderby clause.
	 *
	 * @access protected
	 * @return array Allowed keys.
	 * @since  2.8
	 *
	 */
	protected function get_allowed_orderby_keys() {
		return apply_filters( 'groundhogg/contact_query/allowed_orderby_keys', array_keys( $this->gh_db_contacts->get_columns() ) );
	}

	/**
	 * Register the filters
	 */
	protected function register_filters() {

		if ( ! empty( self::$filters ) ) {
			return;
		}

		self::setup_default_filters();

		do_action( 'groundhogg/contact_query/register_filters', $this );
	}

	/**
	 * Parse the provided filters to form a where clause
	 *
	 * @param $filters
	 *
	 * @return string
	 */
	protected function parse_filters( $filters, $exclude = false ): string {

		if ( ! is_array( $filters ) ) {
			$filters = base64_json_decode( $filters );
		}

		$or_clauses = [];

		if ( ! $filters ) {
			return false;
		}

		$this->register_filters();

		// Or Group
		foreach ( $filters as $filter_group ) {

			$and_clauses = [];

			// And Group
			foreach ( $filter_group as $filter ) {
				$clause = $this->parse_filter( $filter );
				if ( $clause !== false ) {
					$and_clauses[] = $clause;
				}
			}

			if ( empty( $and_clauses ) ) {
				continue;
			}

			$or_clauses[] = '(' . implode( ' AND ', $and_clauses ) . ')';
		}

		if ( empty( $or_clauses ) ) {
			return false;
		}

		return '( ' . implode( " OR ", $or_clauses ) . ' )';
	}

	/**
	 * Parse a single filter
	 *
	 * @param $filter
	 *
	 * @return false|string
	 */
	protected function parse_filter( $filter ) {

		$filter = wp_parse_args( $filter, [
			'type' => ''
		] );

		$type = $filter['type'];

		$handler = get_array_var( static::$filters, $type );

		// No filter handler available
		if ( ! $handler || ! is_callable( $handler['filter_callback'] ) ) {
			return false;
		}

		return call_user_func( $handler['filter_callback'], $filter, $this );
	}

	/**
	 * Registered filters
	 *
	 * @var array[]
	 */
	protected static $filters = [];

	/**
	 * Register a filter callback which will return an SQL statement
	 *
	 * @param string   $type
	 * @param callable $filter_callback
	 *
	 * @return bool
	 */
	public static function register_filter( string $type, callable $filter_callback ): bool {
		if ( ! $type || ! is_callable( $filter_callback ) ) {
			return false;
		}

		self::$filters[ $type ] = [
			'type'            => $type,
			'filter_callback' => $filter_callback,
		];

		return true;
	}

	/**
	 * Setup some initial filters
	 */
	public static function setup_default_filters() {

		self::register_filter(
			'first_name',
			[ self::class, 'contact_generic_text_filter_compare' ]
		);

		self::register_filter(
			'last_name',
			[ self::class, 'contact_generic_text_filter_compare' ]
		);

		self::register_filter(
			'email',
			[ self::class, 'contact_generic_text_filter_compare' ]
		);

		self::register_filter(
			'date_created',
			[ self::class, 'filter_date_created' ]
		);

		self::register_filter(
			'tags',
			[ self::class, 'filter_tags' ]
		);

		self::register_filter(
			'optin_status',
			[ self::class, 'filter_optin_status' ]
		);

		self::register_filter(
			'is_marketable',
			[ self::class, 'filter_marketability' ]
		);

		self::register_filter(
			'owner',
			[ self::class, 'filter_owner' ]
		);

		self::register_filter(
			'meta',
			[ self::class, 'filter_meta' ]
		);

		self::register_filter(
			'phone',
			[ self::class, 'filter_phone' ]
		);

		self::register_filter(
			'country',
			[ self::class, 'filter_country' ]
		);

		self::register_filter(
			'region',
			[ self::class, 'filter_region' ]
		);

		self::register_filter(
			'city',
			[ self::class, 'filter_city' ]
		);

		self::register_filter(
			'street_address_1',
			[ self::class, 'filter_street_address_1' ]
		);

		self::register_filter(
			'street_address_2',
			[ self::class, 'filter_street_address_2' ]
		);

		self::register_filter(
			'postal_zip',
			[ self::class, 'filter_postal_zip' ]
		);

		self::register_filter(
			'company_name',
			[ self::class, 'filter_company_name' ]
		);

		self::register_filter(
			'job_title',
			[ self::class, 'filter_job_title' ]
		);

		self::register_filter(
			'funnel_history',
			[ self::class, 'filter_funnel' ]
		);

		self::register_filter(
			'broadcast_received',
			[ self::class, 'filter_broadcast_received' ]
		);

		self::register_filter(
			'broadcast_opened',
			[ self::class, 'filter_broadcast_opened' ]
		);

		self::register_filter(
			'broadcast_link_clicked',
			[ self::class, 'filter_broadcast_link_clicked' ]
		);

		self::register_filter(
			'email_received',
			[ self::class, 'filter_email_received' ]
		);

		self::register_filter(
			'email_opened',
			[ self::class, 'filter_email_opened' ]
		);

		self::register_filter(
			'email_link_clicked',
			[ self::class, 'filter_email_link_clicked' ]
		);

		self::register_filter(
			'logged_in',
			[ self::class, 'filter_logged_in' ]
		);

		self::register_filter(
			'logged_out',
			[ self::class, 'filter_logged_out' ]
		);

		self::register_filter(
			'not_logged_in',
			[ self::class, 'filter_not_logged_in' ]
		);

		self::register_filter(
			'was_active',
			[ self::class, 'filter_was_active' ]
		);

		self::register_filter(
			'was_not_active',
			[ self::class, 'filter_was_not_active' ]
		);

		self::register_filter(
			'is_user',
			[ self::class, 'filter_is_user' ]
		);

		self::register_filter(
			'user_role_is',
			[ self::class, 'filter_user_role_is' ]
		);

		self::register_filter(
			'user_meta',
			[ self::class, 'filter_user_meta' ]
		);

		self::register_filter(
			'page_visited',
			[ self::class, 'filter_page_visited' ]
		);

	}

	/**
	 * Filter by page visit history
	 *
	 * @param $filter
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_page_visited( $filter, $query ) {
		$filter_vars = wp_parse_args( $filter, [
			'link'          => '',
			'count'         => 1,
			'count_compare' => ''
		] );

		$path = parse_url( $filter_vars['link'], PHP_URL_PATH );

		$ba = self::get_before_and_after_from_filter_date_range( $filter_vars );

		if ( ! empty( $path ) ) {
			$ba['path'] = $path;
		}

		$ba['count']         = $filter_vars['count'];
		$ba['count_compare'] = $filter_vars['count_compare'];

		return self::filter_by_page_visits( $ba, $query );
	}

	/**
	 * Filter by user meta
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_user_meta( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'meta'    => '',
			'compare' => '',
			'value'   => ''
		] );

		global $wpdb;

		$meta_table_name = $wpdb->usermeta;
		$clause1         = self::generic_text_compare( $meta_table_name . '.meta_key', '=', $filter_vars['meta'] );
		$clause2         = self::generic_text_compare( $meta_table_name . '.meta_value', $filter_vars['compare'], $filter_vars['value'] );

		return "{$query->table_name}.user_id IN ( select {$meta_table_name}.user_id FROM {$meta_table_name} WHERE {$clause1} AND {$clause2} ) ";
	}

	/**
	 * Filter by user role
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_user_role_is( $filter_vars, $query ) {

		global $wpdb;

		$filter_vars = wp_parse_args( $filter_vars, [
			'role' => ''
		] );

		$role = sanitize_text_field( $filter_vars['role'] );

		return "$query->table_name.user_id IN ( SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'wp_capabilities' AND meta_value RLIKE '\"$role\"' )";
	}

	/**
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @return string
	 */
	public static function filter_is_user( $filter_vars, $query ) {
		return "$query->table_name.user_id > 0";
	}

	/**
	 * Was inactive
	 *
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @return string
	 */
	public static function filter_was_not_active( $filter_vars, $query ) {
		$before_and_after = self::get_before_and_after_from_filter_date_range( $filter_vars );

		$event_query = array_filter( array_merge( [
			'exclude'       => true,
			'count'         => absint( get_array_var( $filter_vars, 'count', 1 ) ),
			'count_compare' => get_array_var( $filter_vars, 'count_compare', 'greater_than_or_equal_to' ),
		], $before_and_after ) );

		return self::filter_by_activity( $event_query, $query );
	}

	/**
	 * Was active
	 *
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @return string
	 */
	public static function filter_was_active( $filter_vars, $query ) {
		$before_and_after = self::get_before_and_after_from_filter_date_range( $filter_vars );

		$event_query = array_filter( $before_and_after );

		$event_query['count']         = absint( get_array_var( $filter_vars, 'count', 1 ) );
		$event_query['count_compare'] = get_array_var( $filter_vars, 'count_compare', 'greater_than_or_equal_to' );

		return self::filter_by_activity( $event_query, $query );
	}

	/**
	 * has not logged in
	 *
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @return string
	 */
	public static function filter_not_logged_in( $filter_vars, $query ) {
		$before_and_after = self::get_before_and_after_from_filter_date_range( $filter_vars );

		$event_query = array_filter( array_merge( [
			'activity_type' => 'wp_login',
			'exclude'       => true,
			'count'         => absint( get_array_var( $filter_vars, 'count', 1 ) ),
			'count_compare' => get_array_var( $filter_vars, 'count_compare', 'greater_than_or_equal_to' ),
		], $before_and_after ) );

		return self::filter_by_activity( $event_query, $query ) . " AND {$query->table_name}.user_id > 0";
	}

	/**
	 * Logged out
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_logged_out( $filter_vars, $query ) {
		$before_and_after = self::get_before_and_after_from_filter_date_range( $filter_vars );

		$event_query = array_filter( array_merge( [
			'activity_type' => 'wp_logout',
			'count'         => absint( get_array_var( $filter_vars, 'count', 1 ) ),
			'count_compare' => get_array_var( $filter_vars, 'count_compare', 'greater_than_or_equal_to' ),
		], $before_and_after ) );

		return self::filter_by_activity( $event_query, $query );
	}

	/**
	 * Logged in
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_logged_in( $filter_vars, $query ) {

		$before_and_after = self::get_before_and_after_from_filter_date_range( $filter_vars );

		$event_query = array_filter( array_merge( [
			'activity_type' => 'wp_login',
			'count'         => absint( get_array_var( $filter_vars, 'count', 1 ) ),
			'count_compare' => get_array_var( $filter_vars, 'count_compare', 'greater_than_or_equal_to' ),
		], $before_and_after ) );

		return self::filter_by_activity( $event_query, $query );
	}

	/**
	 * Filter by email recieved
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_email_received( $filter_vars, $query ) {
		$filter_vars = wp_parse_args( $filter_vars, [
			'email_id' => 0,
		] );

		$before_and_after = self::get_before_and_after_from_filter_date_range( $filter_vars, true );

		$event_query = array_filter( array_merge( [
			'email_id'      => $filter_vars['email_id'],
			'status'        => Event::COMPLETE,
			'count'         => absint( get_array_var( $filter_vars, 'count', 1 ) ),
			'count_compare' => get_array_var( $filter_vars, 'count_compare', 'greater_than_or_equal_to' ),
		], $before_and_after ) );

		return self::filter_by_events( $event_query, $query );
	}

	/**
	 * Email opened
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_email_opened( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'email_id' => 0,
		] );

		$before_and_after = self::get_before_and_after_from_filter_date_range( $filter_vars, true );

		$event_query = array_filter( array_merge( [
			'activity_type' => Activity::EMAIL_OPENED,
			'email_id'      => $filter_vars['email_id'],
			'count'         => absint( get_array_var( $filter_vars, 'count', 1 ) ),
			'count_compare' => get_array_var( $filter_vars, 'count_compare', 'greater_than_or_equal_to' ),
		], $before_and_after ) );

		return self::filter_by_activity( $event_query, $query );
	}

	/**
	 * Broadcast link clicked
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_email_link_clicked( $filter_vars, $query ) {
		$filter_vars = wp_parse_args( $filter_vars, [
			'email_id' => 0,
			'link'     => ''
		] );

		$before_and_after = self::get_before_and_after_from_filter_date_range( $filter_vars, true );

		$event_query = array_filter( array_merge( [
			'activity_type' => Activity::EMAIL_OPENED,
			'email_id'      => $filter_vars['email_id'],
			'referer'       => $filter_vars['link'],
			'count'         => absint( get_array_var( $filter_vars, 'count', 1 ) ),
			'count_compare' => get_array_var( $filter_vars, 'count_compare', 'greater_than_or_equal_to' ),
		], $before_and_after ) );

		return self::filter_by_activity( $event_query, $query );
	}

	/**
	 * Broadcast link clicked
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_broadcast_link_clicked( $filter_vars, $query ) {
		$filter_vars = wp_parse_args( $filter_vars, [
			'broadcast_id' => 0,
			'link'         => ''
		] );

		$event_query = array_filter( [
			'activity_type' => Activity::EMAIL_CLICKED,
			'funnel_id'     => Broadcast::FUNNEL_ID,
			'step_id'       => $filter_vars['broadcast_id'],
			'referer'       => $filter_vars['link'],
			'count'         => absint( get_array_var( $filter_vars, 'count', 1 ) ),
			'count_compare' => get_array_var( $filter_vars, 'count_compare', 'greater_than_or_equal_to' ),
		] );


		return self::filter_by_activity( $event_query, $query );
	}

	/**
	 * Broadcast opened
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_broadcast_opened( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'broadcast_id' => 0,
		] );

		$event_query = array_filter( [
			'activity_type' => Activity::EMAIL_OPENED,
			'funnel_id'     => Broadcast::FUNNEL_ID,
			'step_id'       => $filter_vars['broadcast_id'],
			'count'         => absint( get_array_var( $filter_vars, 'count', 1 ) ),
			'count_compare' => get_array_var( $filter_vars, 'count_compare', 'greater_than_or_equal_to' ),
		] );

		return self::filter_by_activity( $event_query, $query );
	}

	/**
	 * Filter by broadcast events
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_broadcast_received( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'broadcast_id' => 0,
			'status'       => 'complete'
		] );

		$event_query = array_filter( [
			'event_type'    => Event::BROADCAST,
			'funnel_id'     => Broadcast::FUNNEL_ID,
			'step_id'       => $filter_vars['broadcast_id'],
			'status'        => $filter_vars['status'],
			'count'         => absint( get_array_var( $filter_vars, 'count', 1 ) ),
			'count_compare' => get_array_var( $filter_vars, 'count_compare', 'greater_than_or_equal_to' ),
		] );

		return self::filter_by_events( $event_query, $query );
	}

	/**
	 * Filter by funnel events
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_funnel( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'funnel_id' => 0,
			'step_id'   => 0,
			'status'    => 'complete'
		] );

		$before_and_after = self::get_before_and_after_from_filter_date_range( $filter_vars );

		$event_query = array_filter( [
			'event_type' => Event::FUNNEL,
			'funnel_id'  => $filter_vars['funnel_id'],
			'step_id'    => $filter_vars['step_id'],
			'status'     => $filter_vars['status'],
			'before'     => $before_and_after['before'],
			'after'      => $before_and_after['after'],
		] );

		return self::filter_by_events( $event_query, $query );
	}

	/**
	 * Filter by owner
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_owner( $filter_vars, $query ) {
		$filter_vars = wp_parse_args( $filter_vars, [
			'compare' => 'in',
			'value'   => []
		] );

		$owners = wp_parse_id_list( $filter_vars['value'] );

		switch ( $filter_vars['compare'] ) {
			default:
			case 'in':
				return sprintf( "{$query->table_name}.owner_id IN ( %s )", implode( ',', $owners ) );
			case 'not_in':
				return sprintf( "{$query->table_name}.owner_id NOT IN ( %s )", implode( ',', $owners ) );
		}
	}

	/**
	 * Whether a contact is marketable or not
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_marketability( $filter_vars, $query ) {

		switch ( $filter_vars['marketable'] ) {
			default:
			case 'yes':

				if ( Plugin::instance()->preferences->is_confirmation_strict() ) {
					$clause = sprintf( "( $query->table_name.optin_status = %s OR ( $query->table_name.optin_status = %s AND $query->table_name.date_created >= '%s') )", Preferences::CONFIRMED, Preferences::UNCONFIRMED, Ymd_His( time() - ( Plugin::instance()->preferences->get_grace_period() * DAY_IN_SECONDS ) ) );
				} else {
					$clause = sprintf( "$query->table_name.optin_status IN (%s)", implode( ',', [
						Preferences::CONFIRMED,
						Preferences::UNCONFIRMED,
					] ) );
				}

				if ( Plugin::instance()->preferences->is_gdpr_strict() ) {
					$clause .= " AND " . self::filter_meta( [
							'meta'    => 'gdpr_consent',
							'compare' => '=',
							'value'   => 'yes'
						], $query );

					$clause .= " AND " . self::filter_meta( [
							'meta'    => 'marketing_consent',
							'compare' => '=',
							'value'   => 'yes'
						], $query );
				}

				return $clause;

			case 'no':

				$clause = sprintf( "$query->table_name.optin_status IN (%s)", implode( ',', [
					Preferences::COMPLAINED,
					Preferences::UNSUBSCRIBED,
					Preferences::SPAM,
					Preferences::HARD_BOUNCE,
				] ) );

				if ( Plugin::instance()->preferences->is_confirmation_strict() ) {
					$clause .= sprintf( " OR $query->table_name.optin_status = %s AND $query->table_name.date_created < '%s') )", Preferences::UNCONFIRMED, Ymd_His( time() - ( Plugin::instance()->preferences->get_grace_period() * DAY_IN_SECONDS ) ) );
				}

				if ( Plugin::instance()->preferences->is_gdpr_strict() ) {
					$clause .= " OR " . str_replace( "{$query->table_name}.ID IN", "{$query->table_name}.ID NOT IN", self::filter_meta( [
							'meta'    => 'gdpr_consent',
							'compare' => '=',
							'value'   => 'yes'
						], $query ) );

					$clause .= " OR " . str_replace( "{$query->table_name}.ID IN", "{$query->table_name}.ID NOT IN", self::filter_meta( [
							'meta'    => 'marketing_consent',
							'compare' => '=',
							'value'   => 'yes'
						], $query ) );
				}

				return $clause;
		}
	}


	/**
	 * Filter by optin status
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_optin_status( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'compare' => 'in',
			'value'   => []
		] );

		$optin_statuses = array_filter( $filter_vars['value'], function ( $status ) {
			return Preferences::is_valid( $status );
		} );

		switch ( $filter_vars['compare'] ) {
			default:
			case 'in':
				return sprintf( "{$query->table_name}.optin_status IN ( %s )", implode( ',', $optin_statuses ) );
			case 'not_in':
				return sprintf( "{$query->table_name}.optin_status NOT IN ( %s )", implode( ',', $optin_statuses ) );
		}

	}

	/**
	 * Filter by tags
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_tags( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'compare'  => 'includes',
			'compare2' => 'any',
			'tags'     => []
		] );

		switch ( $filter_vars['compare'] ) {
			default:
			case 'includes':
				switch ( $filter_vars['compare2'] ) {
					default:
					case 'any':
						$tag_query = get_db( 'tag_relationships' )->get_sql( [
							'select'  => 'contact_id',
							'where'   => [
								[ 'tag_id', 'IN', wp_parse_id_list( $filter_vars['tags'] ) ]
							],
							'orderby' => false,
							'order'   => false,
						] );

						return "{$query->table_name}.ID IN ( $tag_query )";

					case 'all':
						return '(' . implode( ' AND ', array_map( function ( $tag ) use ( $query ) {

								$tag_query = get_db( 'tag_relationships' )->get_sql( [
									'select'  => 'contact_id',
									'where'   => [
										[ 'tag_id', '=', absint( $tag ) ]
									],
									'orderby' => false,
									'order'   => false,
								] );

								return "{$query->table_name}.ID IN ( $tag_query )";

							}, $filter_vars['tags'] ) ) . ')';
				}
			case 'excludes':
				switch ( $filter_vars['compare2'] ) {
					default:
					case 'any':
						return '(' . implode( ' OR ', array_map( function ( $tag ) use ( $query ) {

								$tag_query = get_db( 'tag_relationships' )->get_sql( [
									'select'  => 'contact_id',
									'where'   => [
										[ 'tag_id', '=', absint( $tag ) ]
									],
									'orderby' => false,
									'order'   => false,
								] );

								return "{$query->table_name}.ID NOT IN ( $tag_query )";

							}, $filter_vars['tags'] ) ) . ')';

					case 'all':
						$tag_query = get_db( 'tag_relationships' )->get_sql( [
							'select'  => 'contact_id',
							'where'   => [
								[ 'tag_id', 'IN', wp_parse_id_list( $filter_vars['tags'] ) ]
							],
							'orderby' => false,
							'order'   => false,
						] );

						return "{$query->table_name}.ID NOT IN ( $tag_query )";
				}
		}
	}

	/**
	 * Wrapper function to filter by events easily
	 *
	 * @param $event_query
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_by_events( $event_query, $query ) {

		$subwhere = [ 'relationship' => 'AND' ];

		$event_query = wp_parse_args( $event_query, [
			'event_type'    => Event::FUNNEL,
			'status'        => Event::COMPLETE,
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to'
		] );

		foreach ( $event_query as $col => $val ) {

			if ( in_array( $col, [ 'count', 'count_compare' ] ) ) {
				continue;
			}

			if ( ! empty( $val ) ) {
				switch ( $col ) {
					default:
						$compare = '=';
						break;
					case 'before':
						$compare = '<=';
						$col     = 'time';
						break;
					case 'after':
						$compare = '>=';
						$col     = 'time';
						break;
				}

				$subwhere[] = [ 'col' => $col, 'val' => $val, 'compare' => $compare ];
			}

		}

		$table = get_array_var( $event_query, 'status' ) === Event::WAITING ? 'event_queue' : 'events';

		$sql = get_db( $table )->get_sql( [
			'where'   => $subwhere,
			'select'  => 'contact_id, COUNT(*) as total_events',
			'groupby' => 'contact_id',
			'orderby' => false,
			'order'   => ''
		] );

		$clause = self::generic_number_compare( 'events.total_events', $event_query['count_compare'], $event_query['count'] );
		$sql    = "SELECT contact_id FROM ( $sql ) AS events WHERE $clause";

		$in = isset_not_empty( $event_query, 'exclude' ) ? 'NOT IN' : 'IN';

		return "$query->table_name.$query->primary_key $in ( $sql )";
	}

	/**
	 * Wrapper function to filter by events easily
	 *
	 * @param $activity_query
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_by_activity( $activity_query, $query ) {

		$subwhere = [ 'relationship' => 'AND' ];

		$activity_query = wp_parse_args( $activity_query, [
			'activity_type' => '',
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to'
		] );

		foreach ( $activity_query as $col => $val ) {

			if ( ! empty( $val ) ) {
				switch ( $col ) {
					default:
						$subwhere[] = [ 'col' => $col, 'compare' => '=', 'val' => $val ];
						break;
					case 'before':
						$subwhere[] = [ 'col' => 'timestamp', 'compare' => '<=', 'val' => $val ];
						break;
					case 'after':
						$subwhere[] = [ 'col' => 'timestamp', 'compare' => '>=', 'val' => $val ];
						break;
					case 'referer':
						$subwhere[] = [ 'col' => 'referer', 'compare' => 'RLIKE', 'val' => $val ];
						break;
					case 'exclude':
					case 'count_compare':
					case 'count':
						break;
				}
			}
		}

		$sql = get_db( 'activity' )->get_sql( [
			'where'   => $subwhere,
			'select'  => 'contact_id, COUNT(*) as total_events',
			'groupby' => 'contact_id',
			'orderby' => false,
			'order'   => ''
		] );

		$clause = self::generic_number_compare( 'total_activities.total_events', $activity_query['count_compare'], $activity_query['count'] );
		$sql    = "SELECT contact_id FROM ( $sql ) AS total_activities WHERE $clause";

		$in = isset_not_empty( $activity_query, 'exclude' ) ? 'NOT IN' : 'IN';

		return "$query->table_name.$query->primary_key $in ( $sql )";
	}

	/**
	 * Wrapper function to filter by events easily
	 *
	 * @param $activity_query
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_by_page_visits( $activity_query, $query ) {

		$subwhere = [ 'relationship' => 'AND' ];

		$activity_query = wp_parse_args( $activity_query, [
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to'
		] );

		foreach ( $activity_query as $col => $val ) {

			if ( ! empty( $val ) ) {
				switch ( $col ) {
					default:
						$subwhere[] = [ 'col' => $col, 'compare' => '=', 'val' => $val ];
						break;
					case 'before':
						$subwhere[] = [ 'col' => 'timestamp', 'compare' => '<=', 'val' => $val ];
						break;
					case 'after':
						$subwhere[] = [ 'col' => 'timestamp', 'compare' => '>=', 'val' => $val ];
						break;
					case 'exclude':
					case 'count':
					case 'count_compare':
						break;
				}
			}
		}

		$sql = get_db( 'page_visits' )->get_sql( [
			'where'   => $subwhere,
			'select'  => 'contact_id, COUNT(*) as total_visits',
			'groupby' => 'contact_id',
			'orderby' => false,
			'order'   => ''
		] );

		$clause = self::generic_number_compare( 'total_page_visits.total_visits', $activity_query['count_compare'], $activity_query['count'] );
		$sql    = "SELECT contact_id FROM ( $sql ) AS total_page_visits WHERE $clause";

		$in = isset_not_empty( $activity_query, 'exclude' ) ? 'NOT IN' : 'IN';

		return "$query->table_name.$query->primary_key $in ( $sql )";
	}

	/**
	 * Build a standard date filter clause
	 *
	 * @param       $filter_vars
	 * @param false $as_int
	 *
	 * @return string
	 */
	public static function standard_activity_filter_clause( $filter_vars, $as_int = false, $future = false ) {

		if ( $future ) {
			return self::future_standard_activity_filter_clause( $filter_vars, $as_int );
		}

		$filter_vars = wp_parse_args( $filter_vars, [
			'date_range' => '24_hours',
		] );

		$before_and_after = self::get_before_and_after_from_filter_date_range( $filter_vars, true );

		$before = $before_and_after['before'];
		$after  = $before_and_after['after'];

		switch ( $filter_vars['date_range'] ) {
			default:
			case '24_hours':
			case '7_days':
			case '30_days':
			case '60_days':
			case '90_days':
			case '365_days':
			case 'after':
				$clause = $as_int ? sprintf( "> %d", $after ) : sprintf( "> '%s'", Ymd_His( $after ) );
				break;
			case 'before':
				$clause = $as_int ? sprintf( "< %d", $before ) : sprintf( "< '%s'", Ymd_His( $before ) );
				break;
			case 'between':
				$clause = $as_int
					? sprintf( "BETWEEN %d AND %d", $after, $before )
					: sprintf( "BETWEEN '%s' AND '%s'", Ymd_His( $after ), Ymd_His( $before ) );
				break;
		}

		return $clause;
	}

	/**
	 * Build a standard date filter clause
	 *
	 * @param       $filter_vars
	 * @param false $as_int
	 *
	 * @return string
	 */
	public static function future_standard_activity_filter_clause( $filter_vars, $as_int = false, $future = false ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'date_range' => '24_hours',
		] );

		$before_and_after = self::get_future_before_and_after_from_filter_date_range( $filter_vars, true );

		$before = $before_and_after['before'];
		$after  = $before_and_after['after'];

		switch ( $filter_vars['date_range'] ) {
			default:
			case 'after':
				$clause = $as_int ? sprintf( "> %d", $after ) : sprintf( "> '%s'", Ymd_His( $after ) );
				break;
			case '24_hours':
			case '7_days':
			case '30_days':
			case '60_days':
			case '90_days':
			case '365_days':
			case 'before':
				$clause = $as_int ? sprintf( "< %d", $before ) : sprintf( "< '%s'", Ymd_His( $before ) );
				break;
			case 'between':
				$clause = $as_int
					? sprintf( "BETWEEN %d AND %d", $after, $before )
					: sprintf( "BETWEEN '%s' AND '%s'", Ymd_His( $after ), Ymd_His( $before ) );
				break;
		}

		return $clause;
	}

	/**
	 * Build a standard date filter clause
	 *
	 * @param array $filter_vars
	 * @param bool  $as_int
	 *
	 * @return array
	 */
	public static function get_before_and_after_from_filter_date_range( $filter_vars, $as_int = true, $future = false ) {

		if ( $future ) {
			return self::get_future_before_and_after_from_filter_date_range( $filter_vars, $as_int );
		}

		$filter_vars = wp_parse_args( $filter_vars, [
			'date_range' => 'any',
			'after'      => 1,
			'before'     => time(),
		] );

		$after  = date_as_int( $filter_vars['after'] );
		$before = date_as_int( $filter_vars['before'] );

		switch ( $filter_vars['date_range'] ) {
			default:
			case 'any':
				$after  = 1;
				$before = time();
				break;
			case '24_hours':
				$after  = time() - DAY_IN_SECONDS;
				$before = time();
				break;
			case '7_days':
				$after  = time() - ( 7 * DAY_IN_SECONDS );
				$before = time();
				break;
			case '30_days':
				$after  = time() - ( 30 * DAY_IN_SECONDS );
				$before = time();
				break;
			case '60_days':
				$after  = time() - ( 60 * DAY_IN_SECONDS );
				$before = time();
				break;
			case '90_days':
				$after  = time() - ( 90 * DAY_IN_SECONDS );
				$before = time();
				break;
			case '365_days':
				$after  = time() - ( 365 * DAY_IN_SECONDS );
				$before = time();
				break;
			case 'before':
				$before = date_as_int( $before ) + ( DAY_IN_SECONDS - 1 );
				$after  = 1;
				break;
			case 'after':
				$before = time();
				$after  = date_as_int( $after );
				break;
			case 'between':
				compare_dates( $before, $after );
				$after  = date_as_int( $after );
				$before = date_as_int( $before ) + ( DAY_IN_SECONDS - 1 );
				break;
		}

		return [
			'before' => $as_int ? $before : Ymd_His( $before ),
			'after'  => $as_int ? $after : Ymd_His( $after )
		];
	}

	/**
	 * Build a standard date filter clause
	 *
	 * @param array $filter_vars
	 * @param bool  $as_int
	 *
	 * @return array
	 */
	public static function get_future_before_and_after_from_filter_date_range( $filter_vars, $as_int = true ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'date_range' => 'any',
			'after'      => 1,
			'before'     => time(),
		] );

		$after  = date_as_int( $filter_vars['after'] );
		$before = date_as_int( $filter_vars['before'] );

		switch ( $filter_vars['date_range'] ) {
			default:
			case 'any':
				$after  = 1;
				$before = time() * 2;
				break;
			case '24_hours':
				$after  = time();
				$before = time() + DAY_IN_SECONDS;
				break;
			case '7_days':
				$after  = time();
				$before = time() + ( 7 * DAY_IN_SECONDS );
				break;
			case '30_days':
				$after  = time();
				$before = time() + ( 30 * DAY_IN_SECONDS );
				break;
			case '60_days':
				$after  = time();
				$before = time() + ( 60 * DAY_IN_SECONDS );
				break;
			case '90_days':
				$after  = time();
				$before = time() + ( 90 * DAY_IN_SECONDS );
				break;
			case '365_days':
				$after  = time();
				$before = time() + ( 365 * DAY_IN_SECONDS );
				break;
			case 'before':
				$before = date_as_int( $before ) + ( DAY_IN_SECONDS - 1 );
				$after  = 1;
				break;
			case 'after':
				$before = time() * 2;
				$after  = date_as_int( $after );
				break;
			case 'between':
				compare_dates( $before, $after );
				$after  = date_as_int( $after );
				$before = date_as_int( $before ) + ( DAY_IN_SECONDS - 1 );
				break;
		}

		return [
			'before' => $as_int ? $before : Ymd_His( $before ),
			'after'  => $as_int ? $after : Ymd_His( $after )
		];
	}

	/**
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @return string
	 */
	public static function filter_date_created( $filter_vars, $query ) {

		$clause = self::standard_activity_filter_clause( $filter_vars );

		return "{$query->table_name}.date_created $clause";
	}

	/**
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @return string
	 */
	public static function filter_meta( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'meta'    => '',
			'compare' => '',
			'value'   => ''
		] );

		$meta_table_name = get_db( 'contactmeta' )->table_name;
		$clause1         = self::generic_text_compare( $meta_table_name . '.meta_key', '=', $filter_vars['meta'] );
		$clause2         = self::generic_text_compare( $meta_table_name . '.meta_value', $filter_vars['compare'], $filter_vars['value'] );

		return "{$query->table_name}.ID IN ( select {$meta_table_name}.contact_id FROM {$meta_table_name} WHERE {$clause1} AND {$clause2} ) ";
	}

	/**
	 * Filter by the phone number
	 *
	 * todo replace the phone number
	 *
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @return string
	 */
	public static function filter_phone( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'phone_type' => 'primary',
			'compare'    => '',
			'value'      => ''
		] );

		return self::filter_meta( [
			'meta'    => $filter_vars['phone_type'] . '_phone',
			'value'   => $filter_vars['value'],
			'compare' => $filter_vars['compare']
		], $query );
	}

	/**
	 * Filter by country
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_country( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'country' => ''
		] );

		return self::filter_meta( [
			'meta'    => 'country',
			'value'   => $filter_vars['country'],
			'compare' => 'equals'
		], $query );
	}

	/**
	 * Filter by region
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_region( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'region' => ''
		] );

		return self::filter_meta( [
			'meta'    => 'region',
			'value'   => $filter_vars['region'],
			'compare' => 'equals'
		], $query );
	}

	/**
	 * Filter by city
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_city( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'city' => ''
		] );

		return self::filter_meta( [
			'meta'    => 'city',
			'value'   => $filter_vars['city'],
			'compare' => 'equals'
		], $query );
	}

	/**
	 * Filter by city
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_street_address_1( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return self::filter_meta( array_merge( $filter_vars, [
			'meta' => 'street_address_1'
		] ), $query );
	}

	/**
	 * Filter by city
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_street_address_2( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return self::filter_meta( array_merge( $filter_vars, [
			'meta' => 'street_address_2'
		] ), $query );
	}

	/**
	 * Filter by city
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_postal_zip( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return self::filter_meta( array_merge( $filter_vars, [
			'meta' => 'postal_zip'
		] ), $query );
	}

	/**
	 * Filter by city
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_company_name( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return self::filter_meta( array_merge( $filter_vars, [
			'meta' => 'company_name'
		] ), $query );
	}

	/**
	 * Filter by city
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_job_title( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return self::filter_meta( array_merge( $filter_vars, [
			'meta' => 'job_title'
		] ), $query );
	}

	/**
	 * Generic filter for text comparison
	 *
	 * @param $filter_vars array
	 * @param $column_key  string
	 *
	 * @return string
	 */
	public static function generic_text_compare( $column, $compare, $value ) {

		global $wpdb;

		$value = sanitize_text_field( $value );

		switch ( $compare ) {
			default:
			case 'equals':
			case '=':
				return sprintf( "%s = '%s'", $column, $value );
			case '!=':
			case 'not_equals':
				return sprintf( "%s != '%s'", $column, $value );
			case 'contains':
				return sprintf( "%s LIKE '%s'", $column, '%' . $wpdb->esc_like( $value ) . '%' );
			case 'not_contains':
				return sprintf( "%s NOT LIKE '%s'", $column, '%' . $wpdb->esc_like( $value ) . '%' );
			case 'begins_with':
			case 'starts_with':
				return sprintf( "%s LIKE '%s'", $column, $wpdb->esc_like( $value ) . '%' );
			case 'ends_with':
				return sprintf( "%s LIKE '%s'", $column, '%' . $wpdb->esc_like( $value ) );
			case 'empty':
				return sprintf( "%s = ''", $column );
			case 'not_empty':
				return sprintf( "%s != ''", $column );
			case 'regex':
				return sprintf( "%s REGEXP BINARY '%s'", $column, $value );
			case 'less_than':
				return sprintf( "%s < %s", $column, is_numeric( $value ) ? $value : "'$value'" );
			case 'greater_than':
				return sprintf( "%s > %s", $column, is_numeric( $value ) ? $value : "'$value'" );
		}
	}

	/**
	 * Generic filter for text comparison
	 *
	 * @param $filter_vars array
	 * @param $column_key  string
	 *
	 * @return string
	 */
	public static function generic_number_compare( $column, $compare, $value ) {

		switch ( $compare ) {
			default:
			case 'equals':
				return sprintf( "%s = %d", $column, $value );
			case 'not_equals':
				return sprintf( "%s != %d", $column, $value );
			case 'greater_than':
				return sprintf( "%s > %d", $column, $value );
			case 'less_than':
				return sprintf( "%s < %d", $column, $value );
			case 'greater_than_or_equal_to':
				return sprintf( "%s >= %d", $column, $value );
			case 'less_than_or_equal_to':
				return sprintf( "%s <= %d", $column, $value );
			case 'between_inclusive':
				return sprintf( "%s <> %d", $column, $value );
			case 'between_exclusive':
				return sprintf( "%s <= %d", $column, $value );
		}
	}

	/**
	 * Handle the first name filter args
	 *
	 * @param $filter_vars array
	 *
	 * @return string
	 */
	public static function contact_generic_text_filter_compare( array $filter_vars, $query ): string {

		if ( $filter_vars['type'] === 'email' ) {
			$filter_vars['value'] = str_replace( ' ', '+', $filter_vars['value'] );
		}

		return self::generic_text_compare( $query->table_name . '.' . $filter_vars['type'], $filter_vars['compare'], $filter_vars['value'] );
	}

	/**
	 * Handle the first name filter args
	 *
	 * @param $filter_vars array
	 *
	 * @return string
	 */
	public static function contact_generic_number_filter_compare( array $filter_vars ): string {
		return self::generic_number_filter_compare( $filter_vars, $filter_vars['type'] );
	}
}