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
		Filters::number( 'ID', $filter, $where );
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
	public static function filter_unsubscribed( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'reasons' => []
		] );

		// Simple, use date_optin_status_changed
		if ( ! isset( $filter['funnel_id'] ) && ! isset( $filter['email_id'] ) && ! isset( $filter['step_id'] ) && empty( $filter['reasons'] ) ) {
			$where->equals( 'optin_status', Preferences::UNSUBSCRIBED );
			Filters::mysqlDateTime( 'date_optin_status_changed', $filter, $where );

			return;
		}

		$activityQuery = self::basic_activity_filter( Activity::UNSUBSCRIBED, $filter, $where );

		// Filter by reason as well
		if ( ! empty( $filter['reasons'] ) ) {
			$metaAlias = $activityQuery->joinMeta( 'reason' );
			$activityQuery->where()->in( "COALESCE($metaAlias.meta_value,'')", array_map( 'sanitize_key', $filter['reasons'] ) );
		}
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
	 * Filter by age
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_age( $filter, Where $where ) {

		$alias  = $where->query->joinMeta( 'birthday' );
		$column = "FLOOR(DATEDIFF(CURDATE(), $alias.meta_value) / 365.25)";
		$where->query->add_safe_column( $column );

		Filters::number( $column, $filter, $where );
	}

	/**
	 * This filter enables to look for the "anniversary" of a date within a time range
	 * Could be the anniversary of a contact, or maybe their birthday?
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_anniversary( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'meta_key'   => 'birthday', // You can use any meta key
			'date_range' => 'today',
			'compare'    => 'is'
		] );

		$meta_key = $filter['meta_key'];
		$alias    = $where->query->joinMeta( $meta_key );

		/**
		 * @type $before DateTimeHelper
		 * @type $after  DateTimeHelper
		 */
		[ 'before' => $before, 'after' => $after ] = Filters::get_before_and_after_from_date_range( $filter );

		if ( $filter['compare'] === 'is_not' ) {
			$where->not();
		}

		// If the dates are the same; day of, today, tomorrow, yesterday, etc...
		if ( $after->ymd() === $before->ymd() ) {

			$subWhere = $where->subWhere();

			// On feb 28 of non leap years, also include peoples whose anniversary is feb 29
			if ( ! $before->isLeapYear() && $before->format( 'm-d' ) === '02-28' ) {
				$subWhere->addCondition( "DATE_FORMAT($alias.meta_value,'%m-%d') = '02-29'" );
			}

			$subWhere->addCondition( "DATE_FORMAT($alias.meta_value,'%m-%d') = '{$before->format('m-d')}'" );

		} else {
			// Range selection
			$where->addCondition( sprintf( 'DATE_ADD(%1$s, 
			INTERVAL YEAR(\'%2$s\')-YEAR(%1$s) + IF(DAYOFYEAR(\'%2$s\') > DAYOFYEAR(%1$s),1,0) YEAR
			)  
            BETWEEN \'%2$s\' AND \'%3$s\'', "$alias.meta_value", $after->ymd(), $before->ymd() ) );
		}
	}

	/**
	 * Filter by the birthdate
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_birthday( $filter, Where $where ) {
		self::filter_anniversary( wp_parse_args( $filter, [
			'meta_key' => 'birthday'
		] ), $where );
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
	 * Filter by primary object relationships
	 * "Is Parent Of"
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_primary_related( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'object_type' => 'contact',
			'object_id'   => 0
		] );

		if ( $where->hasCondition( 'object_relationships' ) ) {
			$query = new Table_Query( 'object_relationships' );
			$query->setSelect( 'primary_object_id' )
			      ->where()
			      ->equals( 'primary_object_type', 'contact' )
			      ->equals( 'secondary_object_type', $filter['object_type'] );

			if ( $filter['object_id'] ) {
				$query->where()->equals( 'secondary_object_id', $filter['object_id'] );
			}

			$where->in( 'ID', $query );

			return;
		}

		$join = $where->query->addJoin( 'LEFT', 'object_relationships' );
		$join->onColumn( 'primary_object_id', 'ID' )->equals( 'primary_object_type', 'contact' );

		$where->equals( "$join->alias.secondary_object_type", $filter['object_type'] );
		if ( $filter['object_id'] ) {
			$where->equals( "$join->alias.secondary_object_id", $filter['object_id'] );
		}
	}

	/**
	 * Filter by secondary object relationships
	 * "Is Child Of"
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_secondary_related( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'object_type' => 'contact',
			'object_id'   => 0
		] );

		if ( $where->hasCondition( 'object_relationships' ) ) {
			$query = new Table_Query( 'object_relationships' );
			$query->setSelect( 'secondary_object_id' )
			      ->where()
			      ->equals( 'secondary_object_type', 'contact' )
			      ->equals( 'primary_object_type', $filter['object_type'] );

			if ( $filter['object_id'] ) {
				$query->where()->equals( 'primary_object_id', $filter['object_id'] );
			}

			$where->in( 'ID', $query );

			return;
		}

		$join = $where->query->addJoin( 'LEFT', 'object_relationships' );
		$join->onColumn( 'secondary_object_id', 'ID' )->equals( 'secondary_object_type', 'contact' );

		$where->equals( "$join->alias.primary_object_type", $filter['object_type'] );
		if ( $filter['object_id'] ) {
			$where->equals( "$join->alias.primary_object_id", $filter['object_id'] );
		}
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
		$where->like( "$alias.meta_value", '%"' . $where->esc_like( $role ) . '"%' );
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
			'city' => []
		] );

		$cities = wp_parse_list( $filter['city'] );

		if ( empty( $cities ) ) {
			return;
		}

		$alias = $where->query->joinMeta( 'city' );

		$where->in( "$alias.meta_value", $cities );
	}

	/**
	 * Filter by the locale
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_locale( $filter, Where $where, Contact_Query $query ) {

		$filter = wp_parse_args( $filter, [
			'locales' => [ get_locale() ]
		] );

		$alias   = $where->query->joinMeta( 'locale' );
		$default = get_locale();
		$col     = "COALESCE($alias.meta_value,'$default')";
		$query->add_safe_column( $col );

		$where->in( $col, $filter['locales'] );
	}

	/**
	 * Filter by line1 address
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_street_address_1( $filter, Where $where ) {

		Filters::meta_filter( array_merge( $filter, [
			'meta' => 'street_address_1'
		] ), $where );
	}

	/**
	 * Filter by line2 address
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_street_address_2( $filter, Where $where ) {

		Filters::meta_filter( array_merge( $filter, [
			'meta' => 'street_address_2'
		] ), $where );
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
		$filter['status']     = Event::WAITING;
		$filter['event_type'] = Event::FUNNEL;
		self::basic_event_filter( $filter, $where );
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
//		$filter['status']     = Event::COMPLETE;
		$filter['event_type'] = Event::FUNNEL;
		self::basic_event_filter( $filter, $where );
	}

	/**
	 * Handler for basic event joins or sub queries
	 *
	 * @throws \Exception
	 *
	 * @param Where $where
	 * @param       $filter
	 *
	 * @return void
	 */
	public static function basic_event_filter( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'funnel_id'  => 0,
			'step_id'    => 0,
			'email_id'   => 0,
			'event_type' => Event::FUNNEL,
			'status'     => Event::COMPLETE
		] );

		$funnel_id  = absint( $filter['funnel_id'] );
		$step_id    = absint( $filter['step_id'] );
		$email_id   = absint( $filter['email_id'] );
		$event_type = absint( $filter['event_type'] );
		$status     = $filter['status'];

		$table = $status === Event::WAITING ? 'event_queue' : 'events';

		if ( $where->hasCondition( $table ) ) {

			$eventQuery = new Table_Query( $table );
			$eventQuery->setSelect( 'contact_id' );

			$eventQuery->where()
			           ->equals( 'event_type', $event_type )
			           ->equals( 'status', $status );

			if ( $funnel_id ) {
				$eventQuery->where()->equals( 'funnel_id', $funnel_id );
			}

			if ( $step_id ) {
				$eventQuery->where()->equals( 'funnel_id', $step_id );
			}

			if ( $email_id ) {
				$eventQuery->where()->equals( 'email_id', $email_id );
			}

			$where->in( 'ID', $eventQuery );

			return;
		}

		$conditions = $where->query
			->addJoin( 'LEFT', [ $table, $table ] )
			->onColumn( 'contact_id' );

		$where->equals( "$table.status", $status );
		$where->equals( "$table.event_type", $event_type );

		Filters::timestamp( "$table.time", $filter, $where );

		if ( $funnel_id ) {
			$where->equals( "$table.funnel_id", $funnel_id );
		}

		if ( $step_id ) {
			$where->equals( "$table.step_id", $step_id );
		}

		if ( $email_id ) {
			$where->equals( "$table.email_id", $email_id );
		}

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

		$broadcast_id = absint( $filter['broadcast_id'] );

		unset( $filter['broadcast_id'] );
		$filter['funnel_id'] = Broadcast::FUNNEL_ID;

		if ( $broadcast_id ) {
			$filter['step_id'] = $broadcast_id;
		}

		self::filter_email_received( $filter, $where );
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

		$broadcast_id = absint( $filter['broadcast_id'] );

		unset( $filter['broadcast_id'] );
		$filter['funnel_id'] = Broadcast::FUNNEL_ID;

		if ( $broadcast_id ) {
			$filter['step_id'] = $broadcast_id;
		}

		self::filter_email_opened( $filter, $where );
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

		$broadcast_id = absint( $filter['broadcast_id'] );
		unset( $filter['broadcast_id'] );
		$filter['funnel_id'] = Broadcast::FUNNEL_ID;
		if ( $broadcast_id ) {
			$filter['step_id'] = $broadcast_id;
		}

		if ( isset_not_empty( $filter, 'is_sms' ) ) {
			$filter['activity_type'] = Activity::SMS_CLICKED;
			unset( $filter['is_sms'] );
		}

		self::filter_link_clicked( $filter, $where );
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
			'count_compare' => 'greater_than_or_equal_to'
		] );

		$funnel_id = absint( $filter['funnel_id'] );
		$step_id   = absint( $filter['step_id'] );
		$email_id  = absint( $filter['email_id'] );

		$eventQuery = new Table_Query( 'events' );

		$eventQuery->setSelect( 'contact_id', [ 'COUNT(ID)', 'sent' ] )
		           ->setGroupby( 'contact_id' )
		           ->where()
		           ->equals( 'event_type', $funnel_id === 1 ? Event::BROADCAST : Event::FUNNEL );

		Filters::timestamp( 'time', $filter, $eventQuery->where );

		if ( $funnel_id ) {
			$eventQuery->where->equals( 'funnel_id', $funnel_id );
		}

		if ( $email_id ) {
			$eventQuery->where->equals( 'email_id', $email_id );
		} else {
			$eventQuery->where->notEquals( 'email_id', 0 );
		}

		if ( $step_id ) {
			$eventQuery->where->equals( 'step_id', $step_id );
		}

		$alias = alias_from_filter( $filter );

		$join = $where->query->addJoin( 'LEFT', [ $eventQuery, $alias ] );
		$join->onColumn( 'contact_id' );

		$where->compare( "COALESCE($alias.sent,0)", $filter['count'], $filter['count_compare'] );
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

		$funnel_id = absint( $filter['funnel_id'] );
		$step_id   = absint( $filter['step_id'] );
		$email_id  = absint( $filter['email_id'] );

		$activityQuery = new Table_Query( 'activity' );

		$activityQuery->setSelect( 'contact_id', [ 'COUNT(ID)', 'opens' ] )
		              ->setGroupby( 'contact_id' )
		              ->where()
		              ->equals( 'activity_type', Activity::EMAIL_OPENED );

		Filters::timestamp( 'timestamp', $filter, $activityQuery->where );

		if ( $funnel_id ) {
			$activityQuery->where->equals( 'funnel_id', $funnel_id );
		} else {
			$activityQuery->where->greaterThan( 'funnel_id', Broadcast::FUNNEL_ID );
		}

		if ( $email_id ) {
			$activityQuery->where->equals( 'email_id', $email_id );
		}

		if ( $step_id ) {
			$activityQuery->where->equals( 'step_id', $step_id );
		}

		$alias = alias_from_filter( $filter );

		$join = $where->query->addJoin( 'LEFT', [ $activityQuery, $alias ] );
		$join->onColumn( 'contact_id' );

		$where->compare( "COALESCE($alias.opens,0)", $filter['count'], $filter['count_compare'] );
	}

	/**
	 * IF they opened the email
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_link_clicked( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'email_id'      => 0,
			'funnel_id'     => 0,
			'step_id'       => 0,
			'link'          => '',
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to',
			'activity_type' => Activity::EMAIL_CLICKED
		] );

		$path = sanitize_text_field( $filter['link'] );

		$funnel_id = absint( $filter['funnel_id'] );
		$step_id   = absint( $filter['step_id'] );
		$email_id  = absint( $filter['email_id'] );

		$activityQuery = new Table_Query( 'activity' );

		$activityQuery->setSelect( 'contact_id', [ 'COUNT(ID)', 'clicks' ] )
		              ->setGroupby( 'contact_id' )
		              ->where()
		              ->equals( 'activity_type', $filter['activity_type'] );

		Filters::timestamp( 'timestamp', $filter, $activityQuery->where );

		if ( $funnel_id ) {
			$activityQuery->where->equals( 'funnel_id', $funnel_id );
		} else {
			$activityQuery->where->greaterThan( 'funnel_id', Broadcast::FUNNEL_ID );
		}

		if ( $email_id ) {
			$activityQuery->where->equals( 'email_id', $email_id );
		}

		if ( $step_id ) {
			$activityQuery->where->equals( 'step_id', $step_id );
		}

		if ( $path ) {
			$activityQuery->where->like( 'referer', '%' . $where->query->db->esc_like( $path ) . '%' );
		}

		$alias = alias_from_filter( $filter );

		$join = $where->query->addJoin( 'LEFT', [ $activityQuery, $alias ] );
		$join->onColumn( 'contact_id' );

		$where->compare( "COALESCE($alias.clicks,0)", $filter['count'], $filter['count_compare'] );
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
			'activity'     => '',
			'meta_filters' => []
		] );

		$activityQuery = self::basic_activity_filter( $filter['activity'], $filter, $where );

		foreach ( $filter['meta_filters'] as $metaFilter ) {

			[ 0 => $key, 1 => $compare, 2 => $value ] = $metaFilter;

			if ( ! $key || ! $compare ) {
				continue;
			}

			$alias = $activityQuery->joinMeta( $key );
			$activityQuery->where()->compare( "$alias.meta_value", $value, $compare );
		}
	}

	/**
	 * @throws \Exception
	 *
	 * @param array        $filter
	 * @param Where        $where
	 * @param string|array $activity_type
	 *
	 * @return Table_Query
	 */
	public static function basic_activity_filter( $activity_type, $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'value'         => 0,
			'value_compare' => 'greater_than_or_equal_to',
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to',
			'funnel_id'     => 0,
			'step_id'       => 0,
			'email_id'      => 0,
		] );

		$funnel_id = absint( $filter['funnel_id'] );
		$step_id   = absint( $filter['step_id'] );
		$email_id  = absint( $filter['email_id'] );

		$activityQuery = new Table_Query( 'activity' );

		$activityQuery->setSelect( 'contact_id', [ 'COUNT(ID)', 'activities' ] )
		              ->setGroupby( 'contact_id' );

		if ( ! empty( $activity_type ) ) {
			$activityQuery->whereIn( 'activity_type', $activity_type );
		}

		Filters::timestamp( 'timestamp', $filter, $activityQuery->where );

		if ( $filter['value'] ) {
			$activityQuery->where->compare( 'value', $filter['value'], $filter['value_compare'] );
		} else {
			unset( $filter['value'] );
			unset( $filter['value_compare'] );
		}

		if ( $funnel_id ) {
			$activityQuery->where->equals( 'funnel_id', $funnel_id );
		}

		if ( $email_id ) {
			$activityQuery->where->equals( 'email_id', $email_id );
		}

		if ( $step_id ) {
			$activityQuery->where->equals( 'step_id', $step_id );
		}

		$alias = alias_from_filter( $filter );

		$join = $where->query->addJoin( 'LEFT', [ $activityQuery, $alias ] );
		$join->onColumn( 'contact_id' );

		$where->compare( "COALESCE($alias.activities,0)", $filter['count'], $filter['count_compare'] );

		return $activityQuery;
	}

	/**
	 * Filter by the loging activity
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_logged_in( $filter, Where $where ) {
		self::basic_activity_filter( Activity::LOGIN, $filter, $where );
	}

	/**
	 * Filter by logged out activity
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function filter_logged_out( $filter, Where $where ) {
		self::basic_activity_filter( Activity::LOGOUT, $filter, $where );
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

		$filter['count']         = 0;
		$filter['count_compare'] = 'equals';

		self::filter_logged_in( $filter, $where );
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
		self::basic_activity_filter( '', $filter, $where );
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
		$filter['count']         = 0;
		$filter['count_compare'] = 'equals';

		self::basic_activity_filter( '', $filter, $where );
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

		self::$filters->register_from_properties( Properties::instance()->get_fields() );

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

			$search = trim( $query_vars['search'] );
			if ( str_contains( $search, '@' ) || preg_match( '/\.[a-z]{2,5}$/', $search ) ) { // Search for an email address
				$query_vars['email_like'] = str_replace( ' ', '+', $search );
				unset( $query_vars['search'] );
			} else if ( str_contains( $search, ' ' ) ) { // Search for first and last name
				$full_name = split_name( trim( $query_vars['search'] ) );
				if ( $full_name[0] && $full_name[1] ) {
					$query_vars['first_name_like'] = $full_name[0];
					$query_vars['last_name_like']  = $full_name[1];
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
			$parts    = explode( '.', $query_vars['orderby'] );
			$meta_key = $parts[1];

			$alias = $this->joinMeta( sanitize_key( $meta_key ), $this->db->usermeta, 'user_id' );

			$query_vars['orderby'] = "$alias.meta_value";
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
						$orderby = self::cast2date( $orderby );
						break;
					case 'datetime':
						$orderby = self::cast2datetime( $orderby );
						break;
					case 'time':
						$orderby = self::cast2time( $orderby );
						break;
					case 'number':
						$orderby = self::cast2decimal( $orderby, 10, 2 );
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
					$this->setFoundRows( filter_var( $value, FILTER_VALIDATE_BOOLEAN ) );
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
				case 'includes': // Include contacts by ID
					if ( ! empty( $value ) ) {
						$where->in( 'ID', wp_parse_id_list( $value ) );
					}
					break;
				case 'exclude': // Exclude contacts by ID
				case 'excludes': // Exclude contacts by ID
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
						$where->compare( 'email', $value, get_array_var( $query_vars, 'email_compare', '=' ) );
					}
					break;
				case 'email_like': // Email search
					if ( $value ) {
						$where->contains( 'email', $value );
					}
					break;
				case 'first_name_like': // First name search
					if ( $value ) {
						$where->contains( 'first_name', $value );
					}
					break;
				case 'last_name_like': // Last name search
					if ( $value ) {
						$where->contains( 'last_name', $value );
					}
					break;
				case 'full_name_like': // Full name search
					if ( $value ) {
						$where->query->add_safe_column( 'CONCAT(first_name, " ", last_name)' );
						$where->contains( 'CONCAT(first_name, " ", last_name)', $value );
					}
					break;
				case 'tags_include':
					self::tags_include( $where, $value, isset_not_empty( $query_vars, 'tags_include_needs_all' ) );
					break;
				case 'tags_exclude':
					self::tags_exclude( $where, $value, isset_not_empty( $query_vars, 'tags_exclude_needs_all' ) );
					break;
				case 'marketable':

					if ( $value === true || $value === 'yes' || $value == 1 ) {
						self::marketable( $where );
						break;
					}

					if ( $value === false || $value === 'no' || $value == 0 ) {
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

					if ( isset( $event_query['after'] ) ) {
						$where->greaterThanEqualTo( "$alias.time", $event_query['after'] );
					} else if ( isset( $event_query['before'] ) ) {
						$where->lessThanEqualTo( "$alias.time", $event_query['before'] );
					}

					$where->query->setGroupby( 'ID' );


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

					if ( ! is_iterable( $value ) || empty( $value ) ) {
						break;
					}

					$meta_query = (array) $value;

					$relation = get_array_var( $meta_query, 'relation', 'AND' );
					unset( $meta_query['relation'] );

					$metaWhere = $where->subWhere( $relation );

					foreach ( $meta_query as $sub_meta_query ) {

						$sub_meta_query = swap_array_keys( $sub_meta_query, [
							'val'  => 'value',
							'comp' => 'compare'
						] );

						[ 'key' => $key, 'value' => $value, 'compare' => $compare ] = $sub_meta_query;

						$alias = $metaWhere->query->joinMeta( $key );

						$metaWhere->compare( "$alias.meta_value", $value, $compare );
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
				case 'filters1':
				case 'filters2':
				case 'filters3':
				case 'include_filters':
					if ( ! empty( $value ) ) {
						self::filters()->parse_filters( $value, $where );
					}
					break;
				case 'exclude_filters':
				case 'exclude_filters1':
				case 'exclude_filters2':
				case 'exclude_filters3':

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
				default:
					if ( $where->query->db_table->has_column( $query_var ) ) {
						$where->equals( $query_var, $value );
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
		if ( $where->hasCondition( 'tags' ) ) {

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
			$where->equals( "$alias.meta_value", 'yes' );
			$alias = $where->query->joinMeta( 'marketing_consent' );
			$where->equals( "$alias.meta_value", 'yes' );
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
			$where->notEquals( "$alias.meta_value", 'yes' );
			$alias = $where->query->joinMeta( 'marketing_consent' );
			$where->notEquals( "$alias.meta_value", 'yes' );
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

	public function __toString(): string {
		return $this->get_sql();
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
			$this->found_items = $this->get_found_rows();
		}

		return $items;
	}

}
