<?php
namespace Groundhogg\Steps;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\Event;
use function Groundhogg\gisset_not_empty;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Funnel Step Parent
 *
 * Provides an easy way to add new funnel steps to the funnel builder.
 * Just extend this class and overwrite the following functions
 *
 * save()
 * run()
 *
 * if it's a benchmark, make a call to __construct() and add the function
 *
 * complete()
 *
 * @see WPGH_Form_Filled for an example.
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
abstract class Funnel_Step
{

    /**
     * The current step
     *
     * @var Step
     */
    protected $current_step = null;

    /**
     * @var array
     */
    protected $posted_settings = [];

    const ACTION = 'action';
    const BENCHMARK = 'benchmark';

    /**
     * Setup all of the filters and actions to register this step and save it.
     *
     * WPGH_Funnel_Step constructor.
     */
    public function __construct()
    {

        if (is_admin()) {

            /**
             * New filters/actions for better usability and extendability
             *
             * @since 1.1
             */
            add_filter("groundhogg/steps/{$this->get_group()}s", [$this, 'register']);
            add_action("groundhogg/steps/{$this->get_type()}/html", [$this, 'pre_html'], 1 );
            add_action("groundhogg/steps/{$this->get_type()}/html", [$this, 'html']);
            add_action("groundhogg/steps/{$this->get_type()}/save", [$this, 'pre_save'], 1 );
            add_action("groundhogg/steps/{$this->get_type()}/save", [$this, 'save']);
        }

        /**
         * New filters/actions for better usability and extendability
         *
         * @since 1.1
         */
        add_action("groundhogg/steps/{$this->get_type()}/import", [$this, 'import'], 10, 2);
        add_filter("groundhogg/steps/{$this->get_type()}/export", [$this, 'export'], 10, 2);
        add_filter("groundhogg/steps/{$this->get_type()}/enqueue", [$this, 'enqueue']);
        add_filter("groundhogg/steps/{$this->get_type()}/run", [$this, 'pre_run'], 1, 2);
        add_filter("groundhogg/steps/{$this->get_type()}/run", [$this, 'run'], 10, 2);
    }

    /**
     * ] et the element name
     *
     * @return string
     */
    abstract public function get_name();

    /**
     * Get the element type
     *
     * @return string
     */
    abstract public function get_type();

    /**
     * Get the element group
     *
     * @return string
     */
    abstract public function get_group();

    /**
     * Get the description
     *
     * @return string
     */
    abstract public function get_description();

    /**
     * Get the icon URL
     *
     * @return string
     */
    abstract public function get_icon();

    /**
     * Get the delay time in seconds.
     *
     * @param Step
     * @return int
     */
    public function get_delay_time( $step ){
        return 0;
    }

    /**
     * Enqueue the step in the event queue...
     *
     * @param $step Step
     *
     * @return int
     */
    public function enqueue( $step )
    {
        return time() + $this->get_delay_time( $step );
    }

    /**
     * Get the ICON of this action/benchmark
     *
     * @return string
     */
    protected function get_default_icon()
    {
        return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/no-icon.png';
    }


    /**
     * Register the this action/benchmark with the globals...
     *
     * @param $array
     * @return mixed
     */
    public function register( $array )
    {
		$array[ $this->get_type() ] = array(
			'title' =>__( $this->get_name(), 'groundhogg' ),
			'icon'  => $this->get_icon(),
            'group' => $this->get_group(),
            'desc'  => $this->get_description(),
		);

		return $array;
    }

    /**
     * Start a control section.
     *
     * @param array $args
     */
    protected function start_controls_section( $args=[] )
    {
        Plugin::$instance->utils->html->start_form_table( $args );
    }

    /**
     * Ends a control section.
     *
     * @param array $args
     */
    protected function end_controls_section()
    {
        Plugin::$instance->utils->html->end_form_table();
    }

    /**
     * @param string $setting
     * @param array $args
     */
    protected function add_control( $setting = '', $args=[] )
    {
        $args = wp_parse_args( $args, [
            'label' => '',
            'type' => HTML::INPUT,
            'attrs' => [],
            'value' => '',
            'default' => '',
            'description' => ''
        ] );

        $args[ 'attrs' ][ 'id' ] = $this->setting_id_prefix( $setting );
        $args[ 'attrs' ][ 'name' ] = $this->setting_name_prefix( $setting );
        $args[ 'attrs' ][ 'value' ] = empty( $args[ 'value' ] ) ? $this->get_setting( $setting, $args[ 'default' ] ) : $args[ 'value' ] ;

        Plugin::$instance->utils->html->add_form_control( $args );
    }

    /**
     * @param string $setting
     * @return string
     */
    protected function setting_id_prefix( $setting='' )
    {
        if ( empty( $setting ) ){
            $setting = uniqid();
        }

        return sprintf( 'step_%d_%s', $this->get_current_step()->get_id(), $setting );
    }

    /**
     * Return the name structure for settings within the step settings
     *
     * @param string $setting
     * @return string
     */
    protected function setting_name_prefix( $setting='' )
    {
        return sprintf( 'step[%d][%s]', $this->get_current_step()->get_id(), $setting );
    }

    /**
     * Retrieves a setting from the settings array provide by the step meta.
     *
     * @param string $key
     * @param bool $default
     *
     * @return mixed
     */
    protected function get_setting( $key = '', $default=false )
    {
        $val = $this->get_current_step()->get_meta( $key );
        return $val ? $val : $default;
    }

    /**
     * Update a setting.
     *
     * @param string $setting
     * @param string $val
     */
    protected function save_setting( $setting='', $val='' )
    {
        $this->get_current_step()->update_meta( $setting, $val );
    }

    /**
     * Get a normalized array of data for saving the step.
     *
     * @return array
     */
    protected function get_posted_settings()
    {
        return $this->posted_settings;
    }

    /**
     * Retrieves a setting from the posted settings when saving.
     *
     * @param string $key
     * @param bool $default
     *
     * @return mixed
     */
    protected function get_posted_data($key = '', $default=false )
    {
        if ( gisset_not_empty( $this->posted_settings, $key ) ){
            return $this->posted_settings[ $key ];
        }

        return $default;
    }

    /**
     * @return Step
     */
    protected function get_current_step()
    {
        return $this->current_step;
    }

    /**
     * @param Step $step
     */
    protected function set_current_step( Step $step )
    {
        $this->current_step = $step;
    }

    /**
     * Gets the step order
     *
     * @return false|int|string
     */
    private function get_posted_order()
    {
        return array_search( $this->get_current_step()->get_id(), wp_parse_id_list( $_POST[ 'step_ids' ] ) );
    }

    /**
     * Display the settings based on the given ID
     *
     * @param $step Step
     */
    abstract public function settings( $step );

    /**
     * Todo Get the reporting interval.
     * Returns the reporting interval for the reporting view.
     *
     * @return array
     */
    protected function get_reporting_interval()
    {

        $times = [
            'start_time' => '',
            'end_time' => ''
        ];

        return $times;
    }

    /**
     * Get the reporting view for the STEP
     * Most steps will use the default step reporting given here...
     *
     * @param $step Step
     */
    public function reporting( $step )
    {

        $times = $this->get_reporting_interval();

        $start_time = $times[ 'start_time' ];
        $end_time = $times[ 'end_time' ];

        $cquery = new Contact_Query();

        if ( $step->is_action() ):

            $num_events_waiting = $cquery->query( [
                'count' => true,
                'report' => [
                    'step'  => $step->get_id(),
                    'funnel'=> $step->get_funnel_id(),
                    'status'=> 'waiting'
                ]
            ] );

            ?>
                <p class="report">
                    <?php _e('Waiting:', 'groundhogg') ?>
                    <a target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_contacts&view=report&status=waiting&funnel=' . $step->get_funnel_id() . '&step=' . $step->get_id() ); ?>">
                        <b><?php echo $num_events_waiting; ?></b>
                    </a>
                </p>
            <hr>
            <?php
        endif;

        $num_events_completed = $cquery->query( [
            'count' => true,
            'report' => [
                'start' => $start_time,
                'end'   => $end_time,
                'step'  => $step->get_id(),
                'funnel'=> $step->get_funnel_id(),
                'status'=> 'complete'
            ]
        ] );

        ?>
        <p class="report">
            <?php _e('Completed:', 'groundhogg') ?>
            <a target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_contacts&view=report&status=complete&funnel=' . $step->get_funnel_id() . '&step=' . $step->get_id() . '&start=' . $start_time . '&end=' . $end_time ); ?>">
                <b><?php echo $num_events_completed; ?></b>
            </a>
        </p>
        <?php
    }

    /**
     * Get similar steps which can be used by benchmarks.
     * @return Step[]
     */
    public function get_like_steps()
    {
        $raw_steps = Plugin::$instance->dbs->get_db( 'steps' )->get_steps( [ 'step_type' => $this->get_type(), 'step_group' => $this->get_group() ] );

        $steps = [];

        foreach ( $raw_steps as $raw_step ){
            $step = Plugin::$instance->utils->get_step( absint( $raw_step->ID ) );

            if ( $step ){
                $steps[] = $step;
            }
        }

        return $steps;

    }

    /**
     * @param Step $step
     */
    public function pre_html( Step $step )
    {
        $this->set_current_step( $step );
    }

    /**
     * @param $step Step
     */
    public function html( $step ){

        $closed = $step->get_meta( 'is_closed' ) ? 'closed' : '' ;

        ?>
        <div title="<?php echo $step->get_title() ?>" id="<?php echo $step->get_id(); ?>" class="postbox step <?php echo $step->get_group(); ?> <?php echo $step->get_type(); ?> <?php echo $closed; ?>">
            <button type="button" class="handlediv collapse"><span class="toggle-indicator" aria-hidden="true"></span></button>
            <input type="hidden" class="collapse-input" name="<?php echo $this->setting_name_prefix( 'closed' ); ?>" value="<?php echo $this->get_setting( 'is_closed' ); ?>">
            <input type="hidden" name="step_ids[]" value="<?php echo $step->get_id(); ?>">

            <!-- DELETE -->
            <button title="Delete" type="button" class="handlediv delete-step">
                <span class="dashicons dashicons-trash"></span>
            </button>
            <!-- DUPLICATE -->
            <button title="Duplicate" type="button" class="handlediv duplicate-step">
                <span class="dashicons dashicons-admin-page"></span>
            </button>
            <!-- HNDLE -->
            <h2 class="hndle ui-sortable-handle">
                <img class="hndle-icon" width="50" src="<?php echo $this->get_icon() ? $this->get_icon() : $this->get_default_icon(); ?>">

                <?php $args = array(
                    'id'    => $this->setting_id_prefix( 'title' ),
                    'name'  => $this->setting_name_prefix( 'title' ),
                    'value' => __( $step->get_title(), 'groundhogg' ),
                    'title' => __( 'Step Title', 'groundhogg' ),
                );

                echo Plugin::$instance->utils->html->input( $args ); ?>

                <?php if( Plugin::$instance->settings->is_global_multisite() ): ?>
                    <!-- MULTISITE BLOG OPTION -->
                    <div class="wpmu-options">
                        <label style="padding-left: 30px">
                            <?php _e( 'Run on which blog?', 'groundhogg' ); ?>
                            <?php

                            $sites = get_sites();

                            $options = array();
                            foreach ( $sites as $site ){
                                $options[ $site->blog_id ] = get_blog_details($site->blog_id)->blogname;
                            }

                            echo Plugin::$instance->utils->html->dropdown( array(
                                'id'     => $this->setting_id_prefix( 'blog_id' ),
                                'name'   => $this->setting_name_prefix( 'blog_id' ),
                                'options' => $options,
                                'selected' => $step->get_meta( 'blog_id' ),
                                'option_none' => __( 'Any blog', 'groundhogg' )
                            ) );

                            ?>
                        </label>
                    </div>
                    <!-- END MULTISITE BLOG OPTION -->
                <?php endif; ?>
            </h2>
            <!-- INSIDE -->
            <div class="inside">
                <!-- SETTINGS -->
                <?php //TODO Reporting enabled? ?>
                <div class="step-edit <?php echo Plugin::$instance->admin->get_page( 'funnels' )->is_reporting_enabled() ? 'hidden' : '' ; ?>">
                    <div class="custom-settings">
                        <?php do_action( 'groundhogg/step/settings/before', $this ); ?>
                        <?php $this->settings( $step ); ?>
                        <?php do_action( 'groundhogg/step/settings/after', $this ); ?>
                    </div>
                </div>
                <!-- REPORTING  -->
                <?php //TODO Reporting enabled? ?>
                <div class="step-reporting <?php echo Plugin::$instance->admin->get_page( 'funnels' )->is_reporting_enabled() ? '' : 'hidden' ; ?>">
                    <?php do_action( 'groundhogg/step/reporting/before' ); ?>
                    <?php $this->reporting( $step ); ?>
                    <?php do_action( 'groundhogg/step/reporting/after' ); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Initialize the posted settings array
     *
     * @param $step Step
     */
    protected function pre_save( Step $step )
    {
        $this->set_current_step( $step );
        $this->posted_settings = wp_unslash( $_POST[ 'steps' ][ $step->get_id() ] );

        $args = array(
            'step_title'     => sanitize_text_field( $this->get_posted_data( 'title' ) ),
            'step_order'     => $this->get_posted_order(),
            'step_status'    => 'ready',
        );

        $step->update( $args );

        if ( $this->get_posted_data( 'blog_id', false ) ){
            $step->update_meta( 'blog_id', absint( $this->get_posted_data( 'blog_id', false ) ) );
        } else {
            $step->delete_meta( 'blog_id' );
        }

        if (  $this->get_posted_data( 'closed', false ) ){
            $step->update_meta( 'is_closed', 1 );
        } else {
            $step->delete_meta( 'is_closed' );
        }
    }

    /**
     * Save the step based on the given ID
     *
     * @param $step Step
     */
    abstract public function save( $step );

    /**
     * Setup args before the action/benchmark is run
     *
     * @param $contact Contact
     * @param $event Event
     */
    public function pre_run( $contact, $event )
    {
        $this->set_current_step( $event->get_step() );
    }

    /**
     * Run the action/benchmark
     *
     * @param $contact Contact
     * @param $event Event
     *
     * @return bool
     */
    public function run( $contact, $event )
    {
        return true;
    }

    /**
     * @param $args array of args
     * @param $step Step
     */
    public function import( $args, $step ){
        //silence is golden
    }

    /**
     * @param $args array of args
     * @param $step Step
     *
     * @return array
     */
    public function export( $args, $step ){
        //silence is golden
        return $args;
    }
}