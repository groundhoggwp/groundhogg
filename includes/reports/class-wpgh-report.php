<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */

abstract class WPGH_Report
{
    /**
     * @var int
     */
    public $start_time;

    /**
     * @var int
     */
    public $end_time;

    /**
     * @var int
     */
    public $start_range;

    /**
     * @var int
     */
    public $end_range;

    /**
     * @var string
     */
    public $range;

    /**
     * @var int;
     */
    public $points;

    /**
     * @var int
     */
    public $difference;

    /**
     * @var array
     */
    protected $data = [];

    protected $custom_start_time;
    protected $custom_end_time;

    /**
     * A list of contacts that reporting widgets use.
     *
     * @var
     */
    protected static $contacts = null;

	/**
	 * WPGH_Report constructor.
	 *
	 * @param string $range The time range to execute the report
	 * @param int $start only works if range is custom.
	 * @param int $end only works if range is custom
	 */
    public function __construct( $range = '', $start = 0, $end = 0 )
    {
	    $this->setup_range( $range );

	    if ( $this->range === 'custom' ){
		    $this->custom_start_time = $start;
		    $this->custom_end_time = $end;
	    }

	    $this->setup_reporting_time();
    }

	/**
     * Gets the report ID fro reference.
     *
	 * @return mixed string
	 */
    abstract public function get_report_id();

	/**
     * Setup the range from the supported ranges...
     *
	 * @param string $range
	 */
    protected function setup_range( $range = '' )
    {
        if ( ! in_array( $range, self::get_ranges() ) ){
            $range = 'this_week';
        }

        $this->range = $range;
    }

	/**
	 * @param bool $keys_only
	 *
	 * @return array
	 */
    protected static function get_ranges( $keys_only = true )
    {
        $ranges = [
	        'today'         => _x( 'Today', 'reporting_range', 'groundhogg' ),
	        'yesterday'     => _x( 'Yesterday', 'reporting_range', 'groundhogg' ),
	        'this_week'     => _x( 'This Week', 'reporting_range', 'groundhogg' ),
	        'last_week'     => _x( 'Last Week', 'reporting_range', 'groundhogg' ),
	        'last_30'       => _x( 'Last 30 Days', 'reporting_range', 'groundhogg' ),
	        'this_month'    => _x( 'This Month', 'reporting_range', 'groundhogg' ),
	        'last_month'    => _x( 'Last Month', 'reporting_range', 'groundhogg' ),
	        'this_quarter'  => _x( 'This Quarter', 'reporting_range', 'groundhogg' ),
	        'last_quarter'  => _x( 'Last Quarter', 'reporting_range', 'groundhogg' ),
	        'this_year'     => _x( 'This Year', 'reporting_range', 'groundhogg' ),
	        'last_year'     => _x( 'Last Year', 'reporting_range', 'groundhogg' ),
	        'custom'        => _x( 'Custom Range', 'reporting_range', 'groundhogg' ),
        ];

        return $keys_only ? array_keys( $ranges ) : $ranges;
    }

    /**
     * Determine the reporting start and end time of the graph from input from the user.
     */
    protected function setup_reporting_time()
    {

        switch ( $this->range ){
            case 'today';
                $this->start_time   = strtotime( 'today' );
                $this->end_time     = $this->start_time + DAY_IN_SECONDS;
                $this->points       = 24;
                $this->difference   = HOUR_IN_SECONDS;
                break;
            case 'yesterday';
                $this->start_time   = strtotime( 'yesterday' );
                $this->end_time     = $this->start_time + DAY_IN_SECONDS;
                $this->points       = 24;
                $this->difference   = HOUR_IN_SECONDS;
                break;
            default:
            case 'this_week';
                $this->start_time   = mktime(0, 0, 0, date("n"), date("j") - date("N") + 1);
                $this->end_time     = $this->start_time + WEEK_IN_SECONDS;
                $this->points       = 7;
                $this->difference   = DAY_IN_SECONDS;
                break;
            case 'last_week';
                $this->start_time   = mktime(0, 0, 0, date("n"), date("j") - date("N") + 1) - WEEK_IN_SECONDS;
                $this->end_time     = $this->start_time + WEEK_IN_SECONDS;
                $this->points       = 7;
                $this->difference   = DAY_IN_SECONDS;
                break;
            case 'last_30';
                $this->start_time   = wpgh_round_to_day( time() - MONTH_IN_SECONDS );
                $this->end_time     = wpgh_round_to_day( time() );
                $this->points       = ceil( MONTH_IN_SECONDS / DAY_IN_SECONDS );
                $this->difference   = DAY_IN_SECONDS;
                break;
            case 'this_month';
                $this->start_time   = strtotime( 'first day of ' . date( 'F Y' ) );
                $this->end_time     = strtotime( 'first day of ' . date( 'F Y', time() + MONTH_IN_SECONDS ) );
                $this->points       = ceil( MONTH_IN_SECONDS / DAY_IN_SECONDS );
                $this->difference   = DAY_IN_SECONDS;
                break;
            case 'last_month';
                $this->start_time   = strtotime( 'first day of ' . date( 'F Y' , time() - MONTH_IN_SECONDS ) );
                $this->end_time     = strtotime( 'last day of ' . date( 'F Y' ) );
                $this->points       = ceil( MONTH_IN_SECONDS / DAY_IN_SECONDS );
                $this->difference   = DAY_IN_SECONDS;
                break;
            case 'this_quarter';
                $quarter            = wpgh_get_dates_of_quarter();
                $this->start_time   = $quarter[ 'start' ];
                $this->end_time     = $quarter[ 'end' ];
                $this->points       = ceil( ( $quarter[ 'end' ] - $quarter[ 'start' ] ) / WEEK_IN_SECONDS );
                $this->difference   = WEEK_IN_SECONDS;
                break;
            case 'last_quarter';
                $quarter            = wpgh_get_dates_of_quarter( 'previous' );
                $this->start_time   = $quarter[ 'start' ];
                $this->end_time     = $quarter[ 'end' ];
                $this->points       = ceil( ( $quarter[ 'end' ] - $quarter[ 'start' ] ) / WEEK_IN_SECONDS );
                $this->difference   = WEEK_IN_SECONDS;
                break;
            case 'this_year';
                $this->start_time   = mktime(0, 0, 0, 1, 1, date( 'Y' ) );
                $this->end_time     = $this->start_time + YEAR_IN_SECONDS;
                $this->points       = 12;
                $this->difference   = MONTH_IN_SECONDS;
                break;
            case 'last_year';
                $this->start_time   = mktime(0, 0, 0, 1, 1, date( 'Y' , time() - YEAR_IN_SECONDS ));
                $this->end_time     = $this->start_time + YEAR_IN_SECONDS;
                $this->points       = 12;
                $this->difference   = MONTH_IN_SECONDS;
                break;
            case 'custom';
                $this->start_time   = wpgh_round_to_day( $this->get_custom_start_date() );
                $this->end_time     = wpgh_round_to_day( $this->get_custom_end_date() );
                $range = $this->end_time - $this->start_time;
                $this->points       = ceil( $range  / $this->get_time_diff( $range ) );
                $this->difference   = $this->get_time_diff( $range );
                break;
        }

        $this->start_range = $this->start_time;
        $this->end_range = $this->start_range + $this->difference;
    }

	/**
	 * @return false|int
	 */
    protected function get_custom_start_date()
    {

        if ( is_string( $this->custom_start_time ) ){
            return strtotime( $this->custom_start_time );
        }

        if ( is_numeric( $this->custom_start_time ) ){
            return absint( $this->custom_start_time );
        }

	    return strtotime( 'today' );
    }

	/**
	 * @return false|int
	 */
    protected function get_custom_end_date()
    {
	    if ( is_string( $this->custom_end_time ) ){
		    return strtotime( $this->custom_end_time );
	    }

	    if ( is_numeric( $this->custom_end_time ) ){
		    return absint( $this->custom_end_time );
	    }

	    return strtotime( 'today' ) + DAY_IN_SECONDS;
    }

    /**
     * Get the difference in time between points given a time range...
     *
     * @param $range
     * @return int
     */
    private function get_time_diff( $range )
    {

        if ( $range <= DAY_IN_SECONDS ){
            return HOUR_IN_SECONDS;
        } else if ( $range <= WEEK_IN_SECONDS ) {
            return DAY_IN_SECONDS;
        } else if ( $range <= MONTH_IN_SECONDS ){
            return WEEK_IN_SECONDS;
        } else if ( $range <= YEAR_IN_SECONDS ){
            return MONTH_IN_SECONDS;
        }

        return DAY_IN_SECONDS;

    }

    /**
     * Get contacts from within the time range of the reporting widget.
     *
     * @return array|object|null
     */
    public function get_contacts_created_within_time_range()
    {
        if ( self::$contacts !== null ){
            return self::$contacts;
        }

        global $wpdb;

        $table = WPGH()->contacts->table_name;
        $start_date = date('Y-m-d H:i:s', $this->start_time);
        $end_date = date('Y-m-d H:i:s', $this->end_time);

        self::$contacts = $wpdb->get_results(
                $wpdb->prepare(
	                "SELECT ID FROM $table WHERE %s <= date_created AND date_created <= %s"
                , $start_date, $end_date )
        );

        return self::$contacts;
    }

    /**
     * @return array Get just the IDs of the contacts
     */
    public function get_contact_ids_created_within_time_range()
    {
        return wp_parse_id_list( wp_list_pluck( $this->get_contacts_created_within_time_range() , 'ID' ) );
    }

	/**
     * Acts as a cache for reports that might perform similar meta queries
     *
	 * @var array
	 */
	public static $meta_query_results = [];

	/**
     * Queries the meta DB for distinct values of a mata key, in essence returning the various instances of meta_value
     *
	 * @param $meta_key
	 *
	 * @return array
	 */
	public function meta_query( $meta_key='' )
	{
		global $wpdb;
		$cache_key = md5( $meta_key );

		if ( key_exists( $cache_key, self::$meta_query_results ) ){
			return self::$meta_query_results[ $cache_key ];
		}

		$contact_ids = $this->get_contact_ids_created_within_time_range();
		$ids = implode( ',', $contact_ids );

		$results = [];

		if ( empty( $ids ) ){
			return $results;
		}

		$table_name = WPGH()->contact_meta->table_name;
		$results = wp_list_pluck(
		        $wpdb->get_results(
		                $wpdb->prepare(
		                        "SELECT DISTINCT meta_value FROM $table_name WHERE meta_key = %s AND contact_id IN ( $ids )",
                                $meta_key
                        )
                ), 'meta_value'
        );

		self::$meta_query_results[ $cache_key ] = $results;

		return $results;
	}

	/**
     * Retrieves the number of times a meta value for a particular meta key exists
     *
	 * @param string $meta_key
	 * @param string $meta_value
	 *
	 * @return mixed|null|string
	 */
	public function meta_query_count( $meta_key='', $meta_value='' ){

		global $wpdb;

		$cache_key = md5( implode( '|', [ $meta_key, $meta_value ] ) );

		if ( key_exists( $cache_key, self::$meta_query_results ) ){
			return self::$meta_query_results[ $cache_key ];
		}

		$table_name = WPGH()->contact_meta->table_name;
		$count = $wpdb->get_var(
		        $wpdb->prepare(
		                "SELECT COUNT(meta_id) FROM {$table_name} WHERE meta_key = %s AND meta_value = %s AND contact_id IN ( {$this->get_id_list_string()} )",
                        $meta_key, $meta_value
                )
        );

		self::$meta_query_results[ $cache_key ] = $count;

		return $count;

	}

	/**
     * The list of IDs used in query
     *
	 * @return string
	 */
	public function get_id_list_string()
    {
	    $contact_ids = $this->get_contact_ids_created_within_time_range();

	    $list = implode( ',', $contact_ids );

	    if ( empty( $list ) )
	        return '0';

	    return $list;
    }

	/**
     * Get the actual data for the report. Some sort of formatted array....
     * Implementation is left up to the user.
     *
	 * @return array
	 */
    abstract protected function get_data();
}