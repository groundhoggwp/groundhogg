<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */

class WPGH_Reporting_Widget extends WPGH_Dashboard_Widget
{

    protected static $js_flag = false;
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
     * WPGH_Reporting_Widget constructor.
     */
    public function __construct()
    {
        $this->setup_range();
        $this->setup_reporting_time();
        add_action( 'wp_dashboard_setup', array( $this, 'scripts' ) );
        add_action( 'wp_ajax_wpgh_export_' . $this->wid, array( $this, 'export' ) );
        parent::__construct();
    }

    /**
     * Reporting Widget Specific Scripts
     */
    public function scripts()
    {
        wp_enqueue_script( 'wpgh-dashboard', WPGH_ASSETS_FOLDER . 'js/admin/dashboard.min.js', array( 'jquery' ), filemtime(WPGH_PLUGIN_DIR . 'assets/js/admin/dashboard.min.js') );
        wp_enqueue_script( 'papaparse', WPGH_ASSETS_FOLDER . 'lib/papa-parse/papaparse.js' );

        if ( ! self::$js_flag ){
            wp_localize_script( 'wpgh-dashboard', 'wpghDashboard', array(
                'date_range' => $this->range,
                'custom_date_range_start' => esc_attr( $this->get_url_var( 'custom_date_range_start' ) ),
                'custom_date_range_end' => esc_attr( $this->get_url_var( 'custom_date_range_end' ) )
            ) );
            self::$js_flag = true;
        }
    }

    /**
     * Output reporting args for a form if a refresh is necessary
     */
    protected function form_reporting_inputs()
    {
        printf( '<input type="hidden" value="%s" name="%s">', esc_attr( $this->range ), 'date_range' );
        printf( '<input type="hidden" value="%s" name="%s" >', esc_attr( $this->get_url_var( 'custom_date_range_start' ) ), 'custom_date_range_start' );
        printf( '<input type="hidden" value="%s" name="%s">', esc_attr( $this->get_url_var( 'custom_date_range_end' ) ), 'custom_date_range_end' );
    }

    protected function setup_range()
    {
        $this->range = $this->get_url_var( 'date_range', 'this_week' );
    }

    /**
     * Determine the reporting start and end time of the graph from input from the user.
     */
    protected function setup_reporting_time()
    {

//        echo date_default_timezone_get();
//        date_default_timezone_set( 'UTC' );

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
//                var_dump( date( 'Y-m-d H:i:s', $this->start_time ) );
                $this->end_time     = strtotime( 'first day of ' . date( 'F Y', time() + MONTH_IN_SECONDS ) );
//                var_dump( date( 'Y-m-d H:i:s', $this->end_time ) );
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
                $this->start_time   = wpgh_round_to_day( strtotime( $this->get_url_var( 'custom_date_range_start' ) ) );
                $this->end_time     = wpgh_round_to_day( strtotime( $this->get_url_var( 'custom_date_range_end' ) ) );
                $range = $this->end_time - $this->start_time;
                $this->points       = ceil( $range  / $this->get_time_diff( $range ) );
                $this->difference   = $this->get_time_diff( $range );
                break;
        }

        $this->start_range = $this->start_time;
        $this->end_range = $this->start_range + $this->difference;

//        $this->start_time = convert_to_local_time( $this->start_time );
//        $this->end_time = convert_to_local_time( $this->end_time );
//        $this->start_range = convert_to_local_time( $this->start_range );
//        $this->end_range = convert_to_local_time( $this->end_range );
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
     * @return array
     */
    protected function get_export_data()
    {
        return array();
    }

    /**
     * Ajax function to get export data CSV format.
     */
    public function export()
    {
        if ( ! current_user_can( 'export_reports' ) ){
            $response = _x( 'You cannot export reports!', 'notice', 'groundhogg' );
            wp_die(  $response  );
        }

        $this->range = stripslashes( $_POST[ 'date_range' ] );
        $this->setup_reporting_time();

        $data = $this->get_export_data();
        $response = is_array( $data ) ? json_encode( $data ) : $data;
        wp_die( $response );
    }

    /**
     * Output an export button that will export the report
     */
    protected function export_button()
    {
        if ( ! current_user_can( 'export_reports' ) ){
            return;
        }
        ?>
        <div class="export-button">
            <hr>
            <button id="<?php printf( 'export-%s', $this->wid ); ?>" type="button" class="export button button-secondary"><?php _ex( 'Export Report', 'action', 'groundhogg' ) ?></button>
            <span class="spinner"></span>
        </div>
        <?php
    }
}