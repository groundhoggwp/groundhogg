<?php

namespace Groundhogg;

use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\FilterException;
use Groundhogg\DB\Query\Filters;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\DB\Query\Where;
use Groundhogg\Utils\DateTimeHelper;

class Contact_Query extends Table_Query {

	/**
	 * Query vars set by the user.
	 *
	 * @access public
	 * @since  2.8
	 * @var    array
	 */
	public $query_vars;

	/**
	 * List of found items
	 *
	 * @var Contact[]|\stdClass[]
	 */
	public $items = [];

	/**
	 * Number of items found
	 *
	 * @var int
	 */
	public $found_items = 0;

	/**
	 * @var Filters
	 */
	protected static $filters;

	protected $date_key = 'date_created';

	/**
	 * We'll also keep the legacy query on hand in the event there is an error
	 *
	 * @var Legacy_Contact_Query
	 */
	protected $legacy_query;

	public function __construct( $query_vars = [] ) {
		$this->query_vars = $query_vars;
		parent::__construct( 'contacts' );

		// Nice...
		$this->legacy_query = new Legacy_Contact_Query( $query_vars );
	}

	public static function filters() {

		if ( ! is_a( self::$filters, Filters::class ) ) {
			self::$filters = new Filters();
			self::register_filters();
		}

		return self::$filters;
	}

	/**
	 * Filter by the contact ID
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_contact_id( $filter, Where $where ) {
		Filters::number( 'contact_id', $filter, $where );
	}

	/**
	 * Filter by the first name
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_first_name( $filter, Where $where ) {
		Filters::string( 'first_name', $filter, $where );
	}

	/**
	 * Filter by the last name
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_last_name( $filter, Where $where ) {
		Filters::string( 'last_name', $filter, $where );
	}

	/**
	 * Filter by the email
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_email( $filter, Where $where ) {
		$filter['value'] = str_replace( ' ', '+', $filter['value'] );
		Filters::string( 'email', $filter, $where );
	}

	/**
	 * Filter by the date created
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_date_created( $filter, Where $where ) {
		Filters::mysqlDateTime( 'date_created', $filter, $where );
	}

	/**
	 * Filter by opt-in status
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_optin_status( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'compare' => 'in',
			'value'   => []
		] );

		$optin_statuses = array_filter( $filter['value'], function ( $status ) {
			return Preferences::is_valid( $status );
		} );

		if ( empty( $optin_statuses ) ) {
			return;
		}

		switch ( $filter['compare'] ) {
			default:
			case 'in':
				$where->in( 'optin_status', $optin_statuses );
				break;
			case 'not_in':
				$where->notIn( 'optin_status', $optin_statuses );
				break;
		}
	}

	/**
	 * Filter by the owner ID
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_owner( $filter, Where $where ) {
		$filter = wp_parse_args( $filter, [
			'compare' => 'in',
			'value'   => []
		] );

		$owners = wp_parse_id_list( $filter['value'] );

		if ( empty( $owners ) ) {
			return;
		}

		switch ( $filter['compare'] ) {
			default:
			case 'in':
				$where->in( 'owner_id', $owners );
				break;
			case 'not_in':
				$where->notIn( 'owner_id', $owners );
				break;
		}
	}

	/**
	 * Filter by the date when a contact confirmed their email
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_confirmed_email( $filter, Where $where ) {
		$where->equals( 'optin_status', Preferences::CONFIRMED );
		Filters::mysqlDateTime( 'date_optin_status_changed', $filter, $where );
	}

	/**
	 * Filter by the date when a contact unsubscribed
	 *
	 * @param $filter
	 * @param $where
	 *
	 * @return void
	 */
	public static function filter_unsubscribed( $filter, $where ) {
		$where->equals( 'optin_status', Preferences::UNSUBSCRIBED );
		Filters::mysqlDateTime( 'date_optin_status_changed', $filter, $where );
	}

	/**
	 * Filter by the date the opt-in status changed
	 *
	 * @param $filter
	 * @param $where
	 *
	 * @return void
	 */
	public static function filter_optin_status_changed( $filter, $where ) {
		$filter = wp_parse_args( $filter, [
			'value' => []
		] );

		$where->in( 'optin_status', wp_parse_id_list( $filter['value'] ) );
		Filters::mysqlDateTime( 'date_optin_status_changed', $filter, $where );
	}

	/**
	 * Filter by the birthdate
	 *
	 * @param $filter
	 * @param $where
	 *
	 * @return void
	 */
	public static function filter_birthday( $filter, $where ) {
		$alias = $where->query->joinMeta( 'birthday' );
		Filters::mysqlDate( "$alias.meta_value", $filter, $where );
	}

	/**
	 * Filter by tags
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_tags( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'compare'  => 'includes',
			'compare2' => 'any',
			'tags'     => []
		] );

		switch ( $filter['compare'] ) {
			default:
			case 'includes':
				self::tags_include( $where, wp_parse_id_list( $filter['tags'] ), $filter['compare2'] === 'all' );
				break;
			case 'excludes':
				self::tags_exclude( $where, wp_parse_id_list( $filter['tags'] ), $filter['compare2'] === 'all' );
				break;
		}

	}

	/**
	 * Filter by whether the contact is marketable or not
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_is_marketable( $filter, Where $where ) {
		$filter = wp_parse_args( $filter, [
			'marketable' => 'yes'
		] );

		if ( $filter['marketable'] === 'yes' ) {
			Contact_Query::marketable( $where );
		} else {
			Contact_Query::not_marketable( $where );
		}
	}

	/**
	 * Filter by contact custom meta
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_meta( $filter, Where $where ) {
		Filters::meta_filter( $filter, $where );
	}

	/**
	 * Filter by the user ID
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_user_id( $filter, Where $where ) {
		Filters::number( 'user_id', $filter, $where );
	}

	/**
	 * Filter by whether the contact has an attached user account
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_is_user( $filter, Where $where ) {
		$where->greaterThan( 'user_id', 0 );
	}

	/**
	 * Filter by the user role
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_user_role_is( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'role' => ''
		] );

		$role = sanitize_text_field( $filter['role'] );

		$capability_key = $where->query->db->prefix . 'capabilities';
		$alias          = $where->query->joinMeta( $capability_key, $where->query->db->usermeta, 'user_id' );
		$where->like( "$alias.meta_value", '%' . $where->esc_like( $role ) . '%' );
	}

	/**
	 * Filter by user meta
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_user_meta( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'meta' => '',
		] );

		if ( empty( $filter['meta'] ) ) {
			return;
		}

		$alias = $where->query->joinMeta( sanitize_key( $filter['meta'] ), $where->query->db->usermeta, 'user_id' );

		Filters::string( "$alias.meta_value", $filter, $where );
	}

	/**
	 * Filter by the phone number
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_phone( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'phone_type' => 'primary',
			'meta'       => '',
		] );

		$filter['meta'] = $filter['phone_type'] . '_phone';

		Filters::meta_filter( $filter, $where );
	}

	/**
	 * Filter by the country
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_country( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'country' => ''
		] );

		$countries = wp_parse_list( $filter['country'] );

		if ( empty( $countries ) ) {
			return;
		}

		$alias = $where->query->joinMeta( 'country' );

		$where->in( "$alias.meta_value", $countries );
	}

	/**
	 * Filter by the region
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_region( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'region' => ''
		] );

		$regions = wp_parse_list( $filter['region'] );

		if ( empty( $regions ) ) {
			return;
		}

		$alias = $where->query->joinMeta( 'region' );

		$where->in( "$alias.meta_value", $regions );
	}

	/**
	 * Filter by the city
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_city( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'city' => ''
		] );

		$cities = wp_parse_list( $filter['city'] );

		if ( empty( $cities ) ) {
			return;
		}

		$alias = $where->query->joinMeta( 'city' );

		$where->in( "$alias.meta_value", $cities );
	}

	/**
	 * Filter by zip code
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_zip_code( $filter, Where $where ) {
		Filters::meta_filter( array_merge( $filter, [
			'meta' => 'postal_zip'
		] ), $where );
	}

	/**
	 * Filter by a saved search
	 *
	 * @throws \Exception|FilterException
	 *
	 * @param Where $where
	 * @param       $filter
	 *
	 * @return void
	 */
	public static function filter_saved_search( $filter, Where $where ) {
		$filter = wp_parse_args( $filter, [
			'compare' => 'in',
			'search'  => ''
		] );

		// Make sure the search exists
		$search = Saved_Searches::instance()->get( $filter['search'] );

		// Search does not exist, return a 0 result
		if ( ! $search ) {
			return;
		}

		if ( $filter['compare'] === 'not_in' ) {

			$subQuery = new Contact_Query( $search['query'] );
			$subQuery->set_query_var( 'select', 'ID' );
			$subQuery->setSelect( 'ID' );

			$where->notIn( 'ID', $subQuery->get_sql() );
		} else {
			self::set_where_conditions( $search['query'], $where );
		}
	}

	/**
	 * Filter by a sub query search
	 *
	 * @throws \Exception|FilterException
	 *
	 * @param Where $where
	 * @param       $filter
	 *
	 * @return void
	 */
	public static function filter_sub_query( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'include_filters' => [],
			'exclude_filters' => [],
		] );

		if ( empty( $filter['include_filters'] ) && empty( $filter['exclude_filters'] ) ) {
			return;
		}

		self::set_where_conditions( [
			'include_filters' => $filter['include_filters'],
			'exclude_filters' => $filter['exclude_filters'],
		], $where );
	}

	/**
	 * Filter by pending funnel activity
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_funnel_pending( $filter, Where $where ) {
		$filter = wp_parse_args( $filter, [
			'funnel_id' => 0,
			'step_id'   => 0,
		] );

		$funnel_id = absint( $filter['funnel_id'] );
		$step_id   = absint( $filter['step_id'] );

		$alias = 'waiting_funnel_events';

		if ( $funnel_id ) {
			$alias .= '_' . $funnel_id;
		}

		if ( $step_id ) {
			$alias .= '_' . $step_id;
		}

		$join = $where->query->addJoin( 'LEFT', [ 'event_queue', $alias ] );

		$join->onColumn( 'contact_id' )
		     ->equals( "$alias.event_type", Event::FUNNEL );

		$where->equals( "$alias.status", Event::WAITING );

		if ( $funnel_id ) {
			$where->equals( "$alias.funnel_id", $funnel_id );
		} else {
			$where->isNotNull( "$alias.funnel_id" );
		}

		if ( $step_id ) {
			$where->equals( "$alias.step_id", $step_id );
		}

		Filters::timestamp( "$alias.time", $filter, $where );

		$where->query->setGroupby( 'ID' );
	}

	/**
	 * Filter by event history
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_funnel_history( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'funnel_id' => 0,
			'step_id'   => 0,
		] );

		$funnel_id = absint( $filter['funnel_id'] );
		$step_id   = absint( $filter['step_id'] );

		$alias = 'complete_funnel_events';

		if ( $funnel_id ) {
			$alias .= '_' . $funnel_id;
		}

		if ( $step_id ) {
			$alias .= '_' . $step_id;
		}

		$join = $where->query->addJoin( 'LEFT', [ 'events', $alias ] );

		$join->onColumn( 'contact_id' )
		     ->equals( "$alias.event_type", Event::FUNNEL );

		$where->equals( "$alias.status", Event::COMPLETE );

		if ( $funnel_id ) {
			$where->equals( "$alias.funnel_id", $funnel_id );
		} else {
			$where->isNotNull( "$alias.funnel_id" );
		}

		if ( $step_id ) {
			$where->equals( "$alias.step_id", $step_id );
		}

		Filters::timestamp( "$alias.time", $filter, $where );

		$where->query->setGroupby( 'ID' );
	}

	/**
	 * Filter by if the contact received a broadcast
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_broadcast_received( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'broadcast_id' => 0,
		] );

		$broadcast_id = absint( $filter['broadcast_id'] );

		$alias = $broadcast_id ? 'broadcast_received_' . $broadcast_id : 'broadcast_received';

		$join = $where->query->addJoin( 'LEFT', [ 'events', $alias ] );
		$join->onColumn( 'contact_id' )
		     ->equals( "$alias.event_type", Event::BROADCAST );

		$where->equals( "$alias.status", Event::COMPLETE );

		if ( $broadcast_id ) {
			$where->equals( "$alias.step_id", $broadcast_id );
		} else {
			$where->isNotNull( "$alias.step_id" );
		}

		$where->query->setGroupby( 'ID' );
	}

	/**
	 * If they opened the broadcast
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_broadcast_opened( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'broadcast_id'  => 0,
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to'
		] );

		$alias = $where->query->joinActivityTotal( Activity::EMAIL_OPENED, $filter, [ 'funnel_id', 'step_id' ] );

		$where->equals( "$alias.funnel_id", Broadcast::FUNNEL_ID );

		if ( isset_not_empty( $filter, 'broadcast_id' ) ) {
			$where->equals( "$alias.step_id", $filter['broadcast_id'] );
		}

		$where->compare( "COALESCE($alias.total_events,0)", $filter['count'], $filter['count_compare'] );
	}

	/**
	 * If they clicked a link in the broadcast
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_broadcast_link_clicked( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'broadcast_id'  => 0,
			'link'          => '',
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to'
		] );

		$alias = $where->query->joinActivityTotal( Activity::EMAIL_CLICKED, $filter, [
			'funnel_id',
			'step_id',
			'referer'
		] );

		$where->equals( "$alias.funnel_id", Broadcast::FUNNEL_ID );

		if ( isset_not_empty( $filter, 'broadcast_id' ) ) {
			$where->equals( "$alias.step_id", $filter['broadcast_id'] );
		}

		$where->like( "$alias.referer", '%' . $where->esc_like( $filter['link'] ) . '%' );

		$where->compare( "COALESCE($alias.total_events,0)", $filter['count'], $filter['count_compare'] );
	}

	/**
	 * If they received a specific email
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_email_received( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'email_id'      => 0,
			'funnel_id'     => 0,
			'step_id'       => 0,
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to',
		] );

		$funnel_id = absint( $filter['funnel_id'] );
		$email_id  = absint( $filter['email_id'] );
		$step_id   = absint( $filter['step_id'] );

		$alias = 'received_email';

		$select = [
			'contact_id'
		];

		if ( $email_id ) {
			$alias    .= '_' . $email_id;
			$select[] = 'email_id';
		}

		if ( $funnel_id ) {
			$alias    .= '_' . $funnel_id;
			$select[] = 'funnel_id';
		}

		if ( $step_id ) {
			$alias    .= '_' . $step_id;
			$select[] = 'step_id';
		}

		$eventQuery = new Table_Query( 'events' );
		$eventQuery->setSelect( [ 'COUNT(ID)', 'total_events' ], ...$select );
		$eventQuery->setGroupby( ...$select );
		$eventQuery->where->equals( 'status', Event::COMPLETE )
		                  ->equals( 'event_type', Event::FUNNEL )
		                  ->notEquals( 'email_id', 0 );

		Filters::timestamp( 'time', $filter, $eventQuery->where );

		$join = $where->query->addJoin( 'LEFT', [ $eventQuery, $alias ] );

		$join->onColumn( 'contact_id' );

		if ( $email_id ) {
			$where->equals( "$alias.email_id", $email_id );
		}

		if ( $funnel_id ) {
			$where->equals( "$alias.funnel_id", $funnel_id );
		}

		if ( $step_id ) {
			$where->equals( "$alias.step_id", $step_id );
		}

		$where->compare( "COALESCE($alias.total_events,0)", $filter['count'], $filter['count_compare'] );

		$where->query->setGroupby( 'ID' );
	}

	/**
	 * IF they opened the email
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_email_opened( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'email_id'      => 0,
			'funnel_id'     => 0,
			'step_id'       => 0,
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to'
		] );

		$alias = $where->query->joinActivityTotal( Activity::EMAIL_OPENED, $filter, [
			'funnel_id',
			'step_id',
			'email_id'
		] );

		if ( isset_not_empty( $filter, 'funnel_id' ) ) {
			$where->equals( "$alias.funnel_id", $filter['funnel_id'] );
		}
		if ( isset_not_empty( $filter, 'email_id' ) ) {
			$where->equals( "$alias.email_id", $filter['email_id'] );
		}
		if ( isset_not_empty( $filter, 'step_id' ) ) {
			$where->equals( "$alias.step_id", $filter['step_id'] );
		}

		$where->compare( "COALESCE($alias.total_events,0)", $filter['count'], $filter['count_compare'] );
	}

	/**
	 * IF they opened the email
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_email_link_clicked( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'email_id'      => 0,
			'funnel_id'     => 0,
			'step_id'       => 0,
			'link'          => '',
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to'
		] );

		$path = $filter['link'];

		$select = [
			'funnel_id',
			'step_id',
			'email_id'
		];

		if ( ! empty( $path ) ) {
			$select[] = 'path';
		}

		$alias = $where->query->joinActivityTotal( Activity::EMAIL_CLICKED, $filter, $select );

		if ( isset_not_empty( $filter, 'funnel_id' ) ) {
			$where->equals( "$alias.funnel_id", $filter['funnel_id'] );
		}
		if ( isset_not_empty( $filter, 'email_id' ) ) {
			$where->equals( "$alias.email_id", $filter['email_id'] );
		}
		if ( isset_not_empty( $filter, 'step_id' ) ) {
			$where->equals( "$alias.step_id", $filter['step_id'] );
		}

		if ( ! empty( $path ) ) {
			$where->like( "$alias.referer", '%' . $where->esc_like( $filter['link'] ) . '%' );
		}

		$where->compare( "COALESCE($alias.total_events,0)", $filter['count'], $filter['count_compare'] );
	}

	/**
	 * Filter by the custom activity
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_custom_activity( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'activity'      => '',
			'value'         => 0,
			'value_compare' => 'greater_than_or_equal_to',
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to'
		] );

		$alias = $where->query->joinActivityTotal( sanitize_key( $filter['activity'] ), $filter, [
			'funnel_id',
			'step_id',
			'value',
		] );

		if ( $filter['value'] ) {
			$where->compare( "COALESCE($alias.value,0)", $filter['value'], $filter['value_compare'] );
		}

		$where->compare( "COALESCE($alias.total_events,0)", $filter['count'], $filter['count_compare'] );
	}

	/**
	 * Filter by the custom activity
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_logged_in( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to'
		] );

		$filter['activity_type'] = 'wp_login';

		$alias = $where->query->joinActivityTotal( 'wp_login', $filter );
		$where->compare( "COALESCE($alias.total_events,0)", $filter['count'], $filter['count_compare'] );
	}

	/**
	 * Filter by the custom activity
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_logged_out( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to'
		] );

		$filter['activity_type'] = 'wp_logout';

		$alias = $where->query->joinActivityTotal( 'wp_logout', $filter );
		$where->compare( "COALESCE($alias.total_events,0)", $filter['count'], $filter['count_compare'] );
	}

	/**
	 * Filter by the custom activity
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_not_logged_in( $filter, Where $where ) {

		$where->greaterThan( 'user_id', 0 );
		$alias = $where->query->joinActivityTotal( 'wp_login', $filter );
		$where->equals( "COALESCE($alias.total_events,0)", 0 );
	}

	/**
	 * If a contact has activity in the time range
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_was_active( $filter, Where $where ) {
		$alias = $where->query->joinActivityTotal( '', $filter );
		$where->greaterThan( "COALESCE($alias.total_events,0)", 0 );
	}

	/**
	 * IF a contact has no activity in the time range
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_was_not_active( $filter, Where $where ) {
		$alias = $where->query->joinActivityTotal( '', $filter );
		$where->equals( "COALESCE($alias.total_events,0)", 0 );
	}

	/**
	 * Filter by page visits
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_page_visited( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'link'          => '',
			'compare'       => 'starts_with',
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to'
		] );

		$path = parse_url( $filter['link'], PHP_URL_PATH );

		if ( $path ) {
			$alias = $where->query->joinPageVisits( $filter, [ 'path' ] );

			Filters::string( "$alias.path", [
				'value'   => $path,
				'compare' => $filter['compare']
			], $where );
		} else {
			$alias = $where->query->joinPageVisits( $filter );
		}

		$where->compare( "COALESCE($alias.total_visits,0)", $filter['count'], $filter['count_compare'] );
	}

	/**
	 * Filter by page viewed
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_page_viewed( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'link'          => '',
			'compare'       => 'starts_with',
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to'
		] );

		$path = parse_url( $filter['link'], PHP_URL_PATH );

		if ( $path ) {
			$alias = $where->query->joinPageVisits( $filter, [ 'path' ] );

			Filters::string( "$alias.path", [
				'value'   => $path,
				'compare' => $filter['compare']
			], $where );
		} else {
			$alias = $where->query->joinPageVisits( $filter );
		}

		$where->compare( "COALESCE($alias.total_views,0)", $filter['count'], $filter['count_compare'] );
	}


	/**
	 * Handle the filter for a custom field
	 *
	 * @param       $filter
	 * @param Where $where
	 * @param       $field
	 *
	 * @return void
	 */
	public static function custom_field_filter_handler( $filter, Where $where, $field ) {
		// Use most recent available key?
		$meta_key       = $field['name'];
		$filter['meta'] = $meta_key;

		$alias             = $where->query->joinMeta( $meta_key );
		$meta_value_column = "$alias.meta_value";

		switch ( $field['type'] ) {
			default:
			case 'text':
			case 'textarea':
			case 'url':
			case 'tel':
			case 'custom_email':
			case 'html':
				Filters::string( $meta_value_column, $filter, $where );
				break;
			case 'number':
				Filters::number( "CAST($meta_value_column as UNSIGNED)", $filter, $where );
				break;
			case 'date':
				Filters::mysqlDate( "CAST($meta_value_column as DATE)", $filter, $where );
				break;
			case 'datetime':
				Filters::mysqlDateTime( "CAST($meta_value_column as DATETIME)", $filter, $where );
				break;
			case 'time':
				// todo this is wrong
				Filters::mysqlDateTime( "CAST($meta_value_column as TIME)", $filter, $where );
				break;
			case 'radio':
				Filters::is_one_of_filter( $meta_value_column, $filter, $where );
				break;
			case 'checkboxes':
				Filters::custom_field_has_all_selected( $meta_value_column, $filter, $where );
				break;
			case 'dropdown':
				if ( isset_not_empty( $field, 'multiple' ) ) {
					Filters::custom_field_has_all_selected( $meta_value_column, $filter, $where );
				} else {
					Filters::is_one_of_filter( $meta_value_column, $filter, $where );
				}
				break;
		}
	}

	/**
	 * Registers the standard filters
	 *
	 * @return void
	 */
	protected static function register_filters() {

		// automatically register methods that start with 'filter_' as filters
		$reflection = new \ReflectionClass( __CLASS__ );
		$methods    = $reflection->getMethods( \ReflectionMethod::IS_STATIC );

		// Get all the filter methods
		$filter_methods = array_map_to_method( array_filter( $methods, function ( \ReflectionMethod $method ) {
			return str_starts_with( $method->getName(), 'filter_' );
		} ), 'getName' );

		// Register them as filters
		foreach ( $filter_methods as $filter_method ) {
			self::$filters->register( str_replace( 'filter_', '', $filter_method ), [ self::class, $filter_method ] );
		}

		// Register custom field filters
		$fields = Properties::instance()->get_fields();

		foreach ( $fields as $field ) {
			self::$filters->register( $field['id'], function ( $filter, Where $where ) use ( $field ) {
				self::custom_field_filter_handler( $filter, $where, $field );
			} );
		}

		/**
		 * Enable registering more filters
		 *
		 * @param $filters Filters
		 */
		do_action( 'groundhogg/contact_query/filters/register', self::$filters );

	}

	/**
	 * Parse the query vars
	 *
	 * @param $query_vars
	 *
	 * @return void
	 */
	protected function parse_query_vars( $query_vars = [] ) {

		// Query vars are not overwritten
		if ( empty( $query_vars ) && ! empty( $this->query_vars ) ) {
			$query_vars = $this->query_vars;
		}

		// Parse default query vars
		$query_vars = wp_parse_args( $query_vars, [
			'date_key' => $this->date_key
		] );

		// Merge saved search query filters. They take priority and override anything previously set
		if ( isset_not_empty( $query_vars, 'saved_search' ) ) {
			$saved_search = Saved_Searches::instance()->get( $query_vars['saved_search'] );
			if ( $saved_search ) {
				$query_vars = array_merge( $query_vars, $saved_search['query'] );
			}
		}

		// Map 'search' to more specific columns depending on the term format
		if ( isset_not_empty( $query_vars, 'search' ) ) {
			$search = $query_vars['search'];
			if ( str_contains( $search, '@' ) ) { // Search for an email address
				$query_vars['email'] = str_replace( ' ', '+', $search );
				unset( $query_vars['search'] );
			} else if ( str_contains( $search, ' ' ) ) { // Search for first and last name
				$full_name = split_name( trim( $query_vars['search'] ) );
				if ( $full_name[0] && $full_name[1] ) {
					$query_vars['first_name'] = $full_name[0];
					$query_vars['last_name']  = $full_name[1];
					unset( $query_vars['search'] );
				}
			} else if ( is_numeric( $search ) ) {

				unset( $query_vars['search'] );
			}

			// Todo add a check for phone number and search by phone
		}

		// Map 'no_found_rows' to the 'found_rows'
		if ( isset( $query_vars['no_found_rows'] ) ) {
			$query_vars['found_rows'] = ! $query_vars['no_found_rows'];
			unset( $query_vars['no_found_rows'] );
		}

		// Map "number" to "limit"
		if ( isset_not_empty( $query_vars, 'number' ) ) {
			$query_vars['limit'] = $query_vars['number'];
			unset( $query_vars['number'] );
		}

		// Only show contacts associated with the current owner...
		if ( current_user_can( 'view_contacts' ) && ! current_user_can( 'view_others_contacts' ) ) {
			$query_vars['owner'] = get_current_user_id();
		}

		// Map 'ID' to 'Include'
		if ( isset_not_empty( $query_vars, 'ID' ) ) {
			$query_vars['include'] = wp_parse_id_list( $query_vars['ID'] );
			unset( $query_vars['ID'] );
		}

		// Make sure user meta orderby works
		if ( isset( $query_vars['orderby'] ) && str_starts_with( $query_vars['orderby'], 'um.' ) && $query_vars['orderby'] !== 'um.meta_value' ) {
			$parts                 = explode( '.', $query_vars['orderby'] );
			$meta_key = $parts[1];

			$join = $this->addJoin( 'LEFT', [ $this->db->usermeta, 'um' ] );
			$join->onColumn( 'user_id', 'user_id' )->equals( 'meta_key', $meta_key );

			$query_vars['orderby'] = "$join->alias.meta_value";
		}

		// Make sure meta orderby works
		if ( isset( $query_vars['orderby'] ) && str_starts_with( $query_vars['orderby'], 'cm.' ) ) {
			$parts    = explode( '.', $query_vars['orderby'] );
			$meta_key = $parts[1];
			$alias    = $this->joinMeta( $meta_key );
			$orderby  = "$alias.meta_value";

			$customField = Properties::instance()->get_field( $meta_key );

			// Ensure appropriate type casting
			if ( $customField ) {
				switch ( $customField['type'] ) {
					case 'date':
						$orderby = "CAST($orderby as DATE)";
						break;
					case 'datetime':
						$orderby = "CAST($orderby as DATETIME)";
						break;
					case 'time':
						$orderby = "CAST($orderby as TIME)";
						break;
					case 'number':
						$orderby = "CAST($orderby as SIGNED)";
						break;
				}
			}

			$query_vars['orderby'] = $orderby;
		}

		// Make sure tag count orderby works
		if ( isset( $query_vars['orderby'] ) && $query_vars['orderby'] === 'tc.tag_count' ) {

			$tagQuery = new Table_Query( 'tag_relationships' );
			$tagQuery
				->setSelect( 'contact_id', [ 'COUNT(tag_id)', 'tag_count' ] )
				->setGroupby( 'contact_id' );

			$join = $this->addJoin( 'LEFT', [ $tagQuery, 'tc' ] );
			$join->onColumn( 'contact_id' );
		}

		$this->query_vars = $query_vars;
	}

	protected $setup_flag = false;

	/**
	 * Setup the query vars
	 *
	 * @throws FilterException
	 * @return void
	 */
	protected function maybe_setup_query() {

		if ( $this->setup_flag ) {
			return;
		}

		$this->setup_flag = true;

		$this->parse_query_vars();

		self::set_where_conditions( $this->query_vars, $this->where );

		foreach ( $this->query_vars as $query_var => $value ) {
			switch ( $query_var ) {
				case 'select':
					if ( is_array( $value ) ) {
						$this->setSelect( ...$value );
					} else {
						$this->setSelect( $value );
					}
					break;
				case 'limit':
					$this->setLimit( $value );
					break;
				case 'offset':
					$this->setOffset( $value );
					break;
				case 'orderby':
					$this->setOrderby( $value );
					break;
				case 'order':
					$this->setOrder( $value );
					break;
				case 'found_rows':
					$this->setFoundRows( $value );
					break;
				case 'search':
					if ( $value ) {
						$this->search( $value );
					}
					break;
			}
		}
	}

	/**
	 * Parse query vars for the where statement of the contact query
	 *
	 * @throws \Exception|FilterException
	 * @return void
	 */
	protected static function set_where_conditions( $query_vars, Where $where ) {

		foreach ( $query_vars as $query_var => $value ) {
			switch ( $query_var ) {
				case 'include': // Include contacts by ID
					if ( ! empty( $value ) ) {
						$where->in( 'ID', wp_parse_id_list( $value ) );
					}
					break;
				case 'exclude': // Exclude contacts by ID
					if ( ! empty( $value ) ) {
						$where->notIn( 'ID', wp_parse_id_list( $value ) );
					}
					break;
				case 'users_include': // Include contacts by user_id
					if ( ! empty( $value ) ) {
						$where->in( 'user_id', wp_parse_id_list( $value ) );
					}
					break;
				case 'users_exclude': // Exclude contacts bny user_id
					if ( ! empty( $value ) ) {
						$where->notIn( 'user_id', wp_parse_id_list( $value ) );
					}
					break;
				case 'has_user': // If the contact has a user account
					if ( $value ) {
						$where->greaterThan( 'user_id', 1 );
					}
					break;
				case 'no_user': // If the contact does not have a user account
					if ( $value ) {
						$where->equals( 'user_id', 0, '=' );
					}
					break;
				case 'optin_status': // Include by opt-in status
					if ( ! empty( $value ) ) {
						$optin_stati = wp_parse_id_list( $value );
						if ( count( $optin_stati ) === 1 ) {
							$where->equals( 'optin_status', $optin_stati[0] );
						} else {
							$where->in( 'optin_status', $optin_stati );
						}
					}
					break;
				case 'optin_status_exclude': // Exclude by opt-in status
					if ( ! empty( $value ) ) {
						$optin_stati = wp_parse_id_list( $value );
						if ( count( $optin_stati ) === 1 ) {
							$where->notEquals( 'optin_status', $optin_stati[0] );
						} else {
							$where->notIn( 'optin_status', $optin_stati );
						}
					}
					break;
				case 'before': // Date before
					if ( $value ) {
						$date     = new DateTimeHelper( $value );
						$date_key = get_array_var( $query_vars, 'date_key', 'date_created' );
						$where->lessThan( $date_key, $date->ymdhis() );
					}
					break;
				case 'after': // Date after
					if ( $value ) {
						$date     = new DateTimeHelper( $value );
						$date_key = get_array_var( $query_vars, 'date_key', 'date_created' );
						$where->greaterThan( $date_key, $date->ymdhis() );
					}
					break;
				case 'owner': // filter by owber
					if ( ! empty( $value ) ) {
						$owner_ids = wp_parse_id_list( $value );
						if ( count( $owner_ids ) === 1 ) {
							$where->equals( 'owner_id', $owner_ids[0] );
						} else {
							$where->in( 'owner_id', $owner_ids );
						}
					}
					break;
				case 'email': // Email search
					if ( $value ) {
						$where->like( 'email', '%' . $where->query->db->esc_like( $value ) . '%' );
					}
					break;
				case 'first_name': // First name search
					if ( $value ) {
						$where->like( 'first_name', $where->query->db->esc_like( $value ) . '%' );
					}
					break;
				case 'last_name': // Last name search
					if ( $value ) {
						$where->like( 'last_name', $where->query->db->esc_like( $value ) . '%' );
					}
					break;
				case 'tags_include':
					self::tags_include( $where, $value, isset_not_empty( $query_vars, 'tags_include_needs_all' ) );
					break;
				case 'tags_exclude':
					self::tags_exclude( $where, $value, isset_not_empty( $query_vars, 'tags_exclude_needs_all' ) );
					break;
				case 'marketable':
					switch ( $value ) {
						default:
						case 'any':
							break;
						case $value === true:
						case 'yes':
							self::marketable( $where );
							break;
						case $value === false:
						case 'no':
							self::not_marketable( $where );
							break;
					}
					break;
				case 'report':

					if ( ! is_array( $value ) || empty( $value ) ) {
						break;
					}

					$event_query = wp_parse_args( $value, [
						'status'     => Event::COMPLETE,
						'exclude'    => false,
						'event_type' => Event::FUNNEL
					] );

					$event_query = swap_array_keys( $event_query, [
						// From => To
						'step'   => 'step_id',
						'funnel' => 'funnel_id',
						'start'  => 'after',
						'end'    => 'before',
						'type'   => 'event_type',
					] );

					$eventWhere = $event_query['exclude'] ? $where->subWhere( 'AND', true ) : $where;
					$eventJoin  = $where->query->addJoin( 'LEFT', $event_query['status'] === Event::WAITING ? 'event_queue' : 'events' );
					$eventJoin->onColumn( 'contact_id' );
					$eventJoin->conditions->equals( 'status', $event_query['status'] );
					$eventJoin->conditions->equals( 'event_type', $event_query['event_type'] );
					$alias = $eventJoin->alias;

					if ( isset( $event_query['funnel_id'] ) ) {
						$eventWhere->equals( "$alias.funnel_id", $event_query['funnel_id'] );
					}

					if ( isset( $event_query['step_id'] ) ) {
						$eventWhere->in( "$alias.step_id", $event_query['step_id'] );
					}

					if ( isset( $event_query['before'] ) && isset( $event_query['after'] ) ) {
						Filters::timestamp( "$alias.time", array_merge( $event_query, [
							'date_range' => 'between'
						] ), $eventWhere );
					} else if ( isset( $event_query['after'] ) ) {
						Filters::timestamp( "$alias.time", array_merge( $event_query, [
							'date_range' => 'after'
						] ), $eventWhere );
					} else if ( isset( $event_query['before'] ) ) {
						Filters::timestamp( "$alias.time", array_merge( $event_query, [
							'date_range' => 'before'
						] ), $eventWhere );
					}


					break;
				case 'activity':

					if ( ! is_array( $value ) || empty( $value ) ) {
						break;
					}

					$activity_query = wp_parse_args( $value, [
						'exclude'    => false,
						'type'       => 'activity_type',
						'date_range' => 'between'
					] );

					$activity_query = swap_array_keys( $activity_query, [
						// From => To
						'step'   => 'step_id',
						'funnel' => 'funnel_id',
						'start'  => 'after',
						'end'    => 'before',
						'type'   => 'activity_type',
					] );

					$eventWhere = $activity_query['exclude'] ? $where->subWhere( 'AND', true ) : $where;

					$alias = $eventWhere->query->joinActivity( $activity_query['type'] );

					if ( isset( $activity_query['funnel_id'] ) ) {
						$eventWhere->equals( "$alias.funnel_id", $activity_query['funnel_id'] );
					}

					if ( isset( $activity_query['step_id'] ) ) {
						$eventWhere->equals( "$alias.step_id", $activity_query['step_id'] );
					}

					Filters::timestamp( "$alias.timestamp", $activity_query, $eventWhere );

					break;
				case 'meta_key':
//				case 'meta_value': // Handled by meta_key
//				case 'meta_compare': // Handled by meta_key

					$meta_key = $value;

					if ( ! $meta_key ) {
						break;
					}

					$meta_value   = get_array_var( $query_vars, 'meta_value' );
					$meta_compare = get_array_var( $query_vars, 'meta_compare', '=' );

					$alias = $where->query->joinMeta( $meta_key );

					if ( $meta_value && $meta_compare ) {
						$where->compare( "$alias.meta_value", $meta_value, $meta_compare );
					}

					break;
				case 'user_meta_key':
//				case 'user_meta_value': // Handled by user_meta_key
//				case 'user_meta_compare': // Handled by user_meta_key

					$meta_key = $value;

					if ( ! $meta_key ) {
						break;
					}

					$meta_value   = get_array_var( $query_vars, 'user_meta_value' );
					$meta_compare = get_array_var( $query_vars, 'user_meta_compare', '=' );

					$alias = $where->query->joinMeta( $meta_key, $where->query->db->usermeta, 'user_id' );

					if ( $meta_value && $meta_compare ) {
						$where->compare( "$alias.meta_value", $meta_value, $meta_compare );
					}

					break;
				case 'meta_query':

					if ( ! is_array( $value ) || empty( $meta_query ) ) {
						break;
					}

					foreach ( $value as $meta_query ) {

						$meta_query = swap_array_keys( $meta_query, [
							'val'  => 'value',
							'comp' => 'compare'
						] );

						[ 'key' => $key, 'value' => $value, 'compare' => $compare ] = $meta_query;

						$alias = $where->query->joinMeta( $key );

						$where->compare( "$alias.meta_value", $value, $compare );
					}
					break;
				case 'date_query':

					$date_query = wp_parse_args( $value, [
						'before'   => '',
						'after'    => '',
						'date_key' => get_array_var( $query_vars, 'date_key', $where->query->db_table->get_date_key() ),
					] );

					if ( $date_query['after'] ) {
						$afterDate = new DateTimeHelper( $date_query['after'] );
						$where->greaterThanEqualTo( $date_query['date_key'], $afterDate->ymdhis() );
					}

					if ( $date_query['before'] ) {
						$beforeDate = new DateTimeHelper( $date_query['before'] );
						$where->lessThanEqualTo( $date_query['date_key'], $beforeDate->ymdhis() );
					}

					break;
				case 'filters':
				case 'include_filters':
					if ( ! empty( $value ) ) {
						self::filters()->parse_filters( $value, $where );
					}
					break;
				case 'exclude_filters':

					if ( ! empty( $value ) ) {

						$exclude_query = new Contact_Query( [
							'select'  => 'ID',
							'filters' => $value,
							'order'   => false,
							'orderby' => false,
							'limit'   => 0
						] );

						$exclude_query->maybe_setup_query();

						if ( ! $exclude_query->where->isEmpty() ) {
							$where->notIn( 'ID', "$exclude_query" );
						}
					}

					break;
			}
		}

	}

	/**
	 * Join the activity table
	 *
	 * @param $activity_type
	 * @param $after
	 * @param $before
	 *
	 * @return string
	 */
	public function joinEvents( $event_type, $table = 'events' ) {

		$events_table_alias = $table . '_' . $event_type;

		// only join once per key
		if ( key_exists( $events_table_alias, $this->joins ) ) {
			return $events_table_alias;
		}

		$events_table = get_db( $table );

		$join = $this->db->prepare( "LEFT JOIN $events_table->table_name $events_table_alias 
		ON {$this->alias}.ID = $events_table_alias.contact_id 
		AND $events_table_alias.event_type = %d
		AND $events_table_alias.status = %s
		", $event_type, Event::COMPLETE );

		$this->joins[ $events_table_alias ] = $join;

		$this->setGroupby( 'ID' );

		return $events_table_alias;
	}

	/**
	 * Join the activity table
	 *
	 * @param $activity_type
	 * @param $after
	 * @param $before
	 *
	 * @return string
	 */
	public function joinActivity( $activity_type = '' ) {

		$activity_table_alias = 'activity_' . $activity_type;

		// only join once per key
		if ( key_exists( $activity_table_alias, $this->joins ) ) {
			return $activity_table_alias;
		}

		$activity_table = get_db( 'activity' );

		$join = $this->db->prepare( "LEFT JOIN $activity_table->table_name $activity_table_alias 
		ON {$this->alias}.ID = $activity_table_alias.contact_id 
		AND $activity_table_alias.activity_type = %s", $activity_type );

		$this->joins[ $activity_table_alias ] = $join;

		$this->setGroupby( 'ID' );

		return $activity_table_alias;
	}

	/**
	 * Join the activity table
	 *
	 * @param $activity_type
	 * @param $after
	 * @param $before
	 *
	 * @return string
	 */
	public function joinActivityTotal( $activity_type, $filter, $select = [] ) {

		$activity_table_alias = 'activity_' . $activity_type . '_' . md5serialize( $filter );

		// only join once per key
		if ( key_exists( $activity_table_alias, $this->joins ) ) {
			return $this->joins[ $activity_table_alias ];
		}

		$activity_query = new Table_Query( 'activity' );
		$activity_query->setSelect( 'contact_id', [
			'COUNT(*)',
			'total_events'
		], ...$select );

		if ( $activity_type ) {
			$activity_query->where( 'activity_type', $activity_type );
		}

		Filters::timestamp( 'timestamp', $filter, $activity_query->where );
		$activity_query->setGroupby( 'contact_id', ...$select );

		$join = $this->addJoin( 'LEFT', [ $activity_query, $activity_table_alias ] );
		$join->onColumn( 'contact_id' );
		$this->setGroupby( 'ID' );

		return $join->alias;
	}

	/**
	 * Join the page visits table
	 *
	 * @throws \Exception
	 *
	 * @param array $select
	 * @param       $filter
	 *
	 * @return string
	 */
	public function joinPageVisits( $filter, $select = [] ) {

		$page_visits_alias = 'page_visits_' . md5serialize( $filter );

		// only join once per key
		if ( key_exists( $page_visits_alias, $this->joins ) ) {
			return $page_visits_alias;
		}

		$page_visit_query = new Table_Query( 'page_visits' );
		$page_visit_query->setSelect(
			'contact_id',
			[ 'COUNT(*)', 'total_visits', ],
			[ 'SUM(views)', 'total_views' ],
			...$select
		);

		Filters::timestamp( 'timestamp', $filter, $page_visit_query->where );
		$page_visit_query->setGroupby( 'contact_id', ...$select );

		$join = $this->addJoin( 'LEFT', [ $page_visit_query, $page_visits_alias ] );
		$join->onColumn( 'contact_id' );

		$this->setGroupby( 'ID' );

		return $page_visits_alias;
	}

	/**
	 * Join the tags table
	 *
	 * @param $tag_id
	 *
	 * @return string
	 */
	public function joinTags( $tag_id = 0 ) {

		$alias = $tag_id ? 'tag_' . $tag_id : 'tags';

		$join = $this->addJoin( 'LEFT', [
			get_db( 'tag_relationships' )->table_name,
			$alias
		] );

		$join->onColumn( 'contact_id' );

		if ( $tag_id ) {
			$join->conditions->equals( "$alias.tag_id", $tag_id );
		}

		return $alias;
	}

	/**
	 * Include contacts that have the given tags
	 *
	 * @param Where $where
	 * @param int[] $tags
	 * @param bool  $all
	 *
	 * @return void
	 */
	protected static function tags_include( Where $where, $tags, bool $all = false ) {

		$tags = wp_parse_id_list( $tags );

		if ( empty( $tags ) ) {
			return;
		}

		$where->query->setGroupby( 'ID' );

		if ( count( $tags ) === 1 ) {

			$alias = $where->query->joinTags( $tags[0] );
			$where->equals( "$alias.tag_id", $tags[0] );

			return;
		}

		if ( $all ) {

			foreach ( $tags as $tag ) {
				$alias = $where->query->joinTags( $tag );
				$where->equals( "$alias.tag_id", $tag );
			}

			return;
		}

		// Already joined tags, so use a sub query
		if ( $where->hasCondition( 'tags' ) ){

			$tagQuery = new Table_Query( 'tag_relationships' );
			$tagQuery->setSelect( 'contact_id' );
			$tagQuery->whereIn( 'tag_id', $tags );

			$where->in( 'ID', $tagQuery );

			return;
		}

		$alias = $where->query->joinTags();

		$where->in( "$alias.tag_id", $tags );
	}

	/**
	 * Exclude contacts that have the given tags
	 *
	 * @param Where $where
	 * @param int[] $tags
	 * @param bool  $all
	 *
	 * @return void
	 */
	protected static function tags_exclude( Where $where, $tags, bool $all = false ) {

		if ( empty( $tags ) ) {
			return;
		}

		$tags = wp_parse_id_list( $tags );

		if ( count( $tags ) === 1 ) {
			$where->notIn( 'ID', get_db( 'tag_relationships' )->get_sql( [
				'select'  => 'contact_id',
				'tag_id'  => $tags[0],
				'orderby' => false,
				'order'   => false,
			] ) );

			return;
		}

		if ( $all ) {
			$where->notIn( 'ID', get_db( 'tag_relationships' )->get_sql( [
				'select'  => 'contact_id',
				'tag_id'  => $tags,
				'orderby' => false,
				'order'   => false,
			] ) );

			return;
		}

		$subWhere = $where->subWhere();

		foreach ( $tags as $tag ) {
			$subWhere->notIn( 'ID', get_db( 'tag_relationships' )->get_sql( [
				'select'  => 'contact_id',
				'tag_id'  => $tag,
				'orderby' => false,
				'order'   => false,
			] ) );
		}
	}

	/**
	 * Include contacts that are marketable
	 *
	 * @param Where $where
	 *
	 * @return void
	 */
	protected static function marketable( Where $where ) {

		if ( Plugin::instance()->preferences->is_confirmation_strict() ) {

			$subWhere = $where->subWhere();

			$subWhere->equals( 'optin_status', Preferences::CONFIRMED );
			$subSubWhere = $subWhere->subWhere( 'AND' );

			$subSubWhere->equals( 'optin_status', Preferences::UNCONFIRMED );
			$subSubWhere->greaterThan( 'date_optin_status_changed', Plugin::instance()->preferences->get_grace_period_cutoff_date( 'Y-m-d H:i:s' ) );

		} else {

			// Optin status MUST be confirmed or unconfirmed
			$where->in( 'optin_status', [
				Preferences::CONFIRMED,
				Preferences::UNCONFIRMED,
				Preferences::WEEKLY,
				Preferences::MONTHLY
			] );

		}

		if ( Plugin::instance()->preferences->is_gdpr_strict() ) {
			$alias = $where->query->joinMeta( 'gdpr_consent' );
			$where->equals( "$alias.gdpr_consent", 'yes' );
			$alias = $where->query->joinMeta( 'marketing_consent' );
			$where->equals( "$alias.marketing_consent", 'yes' );
		}

	}

	/**
	 * Include contacts that are not marketable
	 *
	 * @param Where $where
	 *
	 * @return void
	 */
	protected static function not_marketable( Where $where ) {

		$where = $where->subWhere();

		$where->in( 'optin_status', [
			Preferences::COMPLAINED,
			Preferences::UNSUBSCRIBED,
			Preferences::SPAM,
			Preferences::HARD_BOUNCE,
		] );

		if ( Plugin::instance()->preferences->is_confirmation_strict() ) {
			$subWhere = $where->subWhere( 'AND' );
			$subWhere->equals( 'optin_status', Preferences::UNCONFIRMED );
			$subWhere->lessThan( 'date_optin_status_changed', Plugin::instance()->preferences->get_grace_period_cutoff_date( 'Y-m-d H:i:s' ) );
		}

		if ( Plugin::instance()->preferences->is_gdpr_strict() ) {
			$alias = $where->query->joinMeta( 'gdpr_consent' );
			$where->notEquals( "$alias.gdpr_consent", 'yes' );
			$alias = $where->query->joinMeta( 'marketing_consent' );
			$where->notEquals( "$alias.marketing_consent", 'yes' );
		}
	}

	/**
	 * Updates the date key
	 *
	 * @depreacted 3.2
	 *
	 * @param string $string
	 *
	 * @return void
	 */
	public function set_date_key( string $string ) {
		_deprecated_function( 'Contact_Query::set_date_key()', '3.2' );

		$this->date_key = $string;
		$this->legacy_query->set_date_key( $string );
	}

	/**
	 * Backwards compat for Legacy Contact Query
	 *
	 * @deprecated use the Contact_Query::$filters->register() instead
	 *
	 * @param ...$args
	 *
	 * @return void
	 */
	public static function register_filter( ...$args ) {
		_deprecated_function( 'Contact_Query::register_filter()', '3.2', 'Contact_Query::filters()->register()' );
		Legacy_Contact_Query::register_filter( ...$args );
	}

	/**
	 * Set a query var
	 *
	 * @param string $var
	 * @param        $value
	 */
	public function set_query_var( string $var, $value ) {
		$this->query_vars[ $var ] = $value;
		$this->legacy_query->set_query_var( $var, $value );
	}

	/**
	 * Retrieve the SQL statement instead of the actual items
	 *
	 * @param $query
	 *
	 * @return string
	 */
	public function get_sql( $query = [] ) {

		if ( ! empty( $query ) ) {
			$this->query_vars = wp_parse_args( $query );
		}
		try {
			$this->maybe_setup_query();
		} catch ( \Exception|FilterException $exception ) {
			return $this->legacy_query->get_sql( $query );
		}

		return $this->get_select_sql();
	}

	/**
	 * Get the contacts
	 *
	 * @param $query_vars
	 * @param $as_objects
	 *
	 * @return Contact[]|mixed[]|int
	 */
	public function query( $query_vars = [], $as_objects = false ) {

		if ( ! empty( $query_vars ) ) {
			$this->query_vars = $query_vars;
		}

		// Might be doing a count instead
		if ( isset_not_empty( $this->query_vars, 'count' ) ) {
			return $this->count( $query_vars );
		}

		try {
			$items = $this->get_results();
		} catch ( FilterException|\Exception $exception ) {
			$items             = $this->legacy_query->query( $query_vars );
			$this->found_items = $this->legacy_query->found_items;
		}

		if ( $as_objects ) {
			$items = array_map_to_contacts( $items );
		}

		$this->items = $items;

		return $items;
	}

	/**
	 * Number of contacts that match the query
	 *
	 * @param $query_vars
	 *
	 * @return int
	 */
	public function count( $query_vars = [] ) {

		if ( ! empty( $query_vars ) ) {
			$this->query_vars = wp_parse_args( $query_vars );
		}

		try {
			$this->maybe_setup_query();
		} catch ( FilterException|\Exception $exception ) {
			return $this->legacy_query->count( $query_vars );
		}

		if ( $this->groupby ) {
			$this->setLimit( 1 );
			$this->setOffset( 0 );
			$this->setFoundRows( true );
			$this->get_results();

			return $this->found_items;
		}

		return parent::count();
	}

	/**
	 * Get var after query setup
	 *
	 * @throws FilterException
	 *
	 * @param int $y * @param int $x
	 *
	 * @return false|mixed|string|null
	 */
	public function get_var( $x = 0, $y = 0 ) {
		$this->maybe_setup_query();

		return parent::get_var( $x, $y );
	}

	/**
	 * @throws FilterException
	 * @return object[]
	 */
	public function get_results(): array {

		$this->maybe_setup_query();

		/**
		 * Before getting the results of the query
		 */
		do_action_ref_array( 'groundhogg/contact_query/pre_get_contacts', [ &$this ] );

		$items = parent::get_results();

		if ( isset_not_empty( $this->query_vars, 'found_rows' ) || $this->found_rows ) {
			$this->found_items = $this->db_table->found_rows();
		}

		return $items;
	}

}
