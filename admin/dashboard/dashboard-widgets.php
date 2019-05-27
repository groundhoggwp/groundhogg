<?php
namespace Groundhogg\Admin\Dashboard;

use Groundhogg\Admin\Dashboard\Widgets\Country_Widget;
use Groundhogg\Admin\Dashboard\Widgets\Dashboard_Widget;
use Groundhogg\Admin\Dashboard\Widgets\Email_Activity;
use Groundhogg\Admin\Dashboard\Widgets\Form_Activity;
use Groundhogg\Admin\Dashboard\Widgets\Funnel_Breakdown;
use Groundhogg\Admin\Dashboard\Widgets\Last_Broadcast_Widget;
use Groundhogg\Admin\Dashboard\Widgets\Lead_Sources;
use Groundhogg\Admin\Dashboard\Widgets\New_Contacts;
use Groundhogg\Admin\Dashboard\Widgets\Optin_Status_Widget;
use Groundhogg\Admin\Dashboard\Widgets\Region_Widget;
use Groundhogg\Admin\Dashboard\Widgets\Search_Engines;
use Groundhogg\Admin\Dashboard\Widgets\Social_Platforms;
use Groundhogg\Admin\Dashboard\Widgets\Source_Pages;
use Groundhogg\Admin\Dashboard\Widgets\Time_Range_Picker;
use Groundhogg\Admin\Dashboard\Widgets\UTM_Campaigns;
use function Groundhogg\get_array_var;
use function Groundhogg\get_request_var;
use Groundhogg\Reporting\Reports\Last_Broadcast;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 1:54 PM
 */

class Dashboard_Widgets
{

    /**
     * @var array an array of all the available widgets
     */
    public $widgets = array();

    /**
     * WPGH_Dashboard_Widgets constructor.
     */
    public function __construct()
    {
        add_action( 'admin_init', array( $this, 'setup_widgets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
        add_action( 'wp_dashboard_setup', array( $this, 'setup_dashboard_widgets' ) );
    }

    /**
     * Allow for use of dashboard widgets on other pages
     */
    public function setup_dashboard_widgets()
    {
       do_action( 'groundhogg/reporting/load' );
    }

    public function __set( $key, $val ){
        $this->widgets[ $key ] = $val;
    }

    public function __get( $key )
    {
        return get_array_var( $this->widgets, $key );
    }

    public function setup_widgets()
    {

        if ( ! current_user_can( 'view_reports' ) ){
            return;
        }

        $widgets = [
            new Time_Range_Picker(),
            new Country_Widget(),
            new Region_Widget(),
            new Optin_Status_Widget(),
            new Last_Broadcast_Widget(),
            new Email_Activity(),
            new New_Contacts(),
            new Form_Activity(),
            new Funnel_Breakdown(),
            new Search_Engines(),
            new Lead_Sources(),
            new Social_Platforms(),
            new Source_Pages(),
            new UTM_Campaigns(),
        ];

        /**
         * @param $widget Dashboard_Widget
         *
         */
        foreach ( $widgets as $widget ){
            $this->add_widget( $widget );
        }

        do_action( 'groundhogg/dashboard/widgets/init', $this );
    }

    public function scripts( $hook_suffix )
    {

        // Show only on dashbaord
        if ( $hook_suffix !== 'index.php' ){
            return;
        }

        if ( ! current_user_can( 'view_reports' ) ){ return; }

        wp_enqueue_style( 'groundhogg-admin-dashboard' );
        wp_enqueue_script( 'groundhogg-admin-dashboard' );

        wp_localize_script( 'groundhogg-admin-dashboard', 'GroundhoggDashboard', array(
            'date_range' => $this->range,
            'custom_date_range_start' => esc_attr( get_request_var( 'custom_date_range_start' ) ),
            'custom_date_range_end' => esc_attr( get_request_var( 'custom_date_range_end' ) )
        ) );

        wp_enqueue_script( 'jquery-flot' );
        wp_enqueue_script( 'jquery-flot-pie' );
        wp_enqueue_script( 'jquery-flot-categories' );
        wp_enqueue_script( 'jquery-flot-time' );
    }

    /**
     * @param $widget Dashboard_Widget
     *
     * @return bool whether the widget was added or not.
     */
    public function add_widget( $widget )
    {
        if ( ! current_user_can( 'view_reports' ) ){
            return false;
        }

        $this->widgets[ $widget->get_id() ] = $widget;

        return true;
    }

}