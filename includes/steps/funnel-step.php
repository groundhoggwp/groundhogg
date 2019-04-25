<?php
namespace Groundhogg\Steps;

use Groundhogg\Contact;
use Groundhogg\Event;
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
    protected $cur_step = null;

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
            add_action("groundhogg/steps/{$this->get_type()}/html", [$this, 'html']);
            add_action("groundhogg/steps/{$this->get_type()}/save", [$this, 'save']);
            add_filter("groundhogg/steps/{$this->get_type()}/icon", [$this, 'icon']);
        }

        /**
         * New filters/actions for better usability and extendability
         *
         * @since 1.1
         */
        add_action("groundhogg/steps/{$this->get_type()}/import", [$this, 'import'], 10, 2);
        add_filter("groundhogg/steps/{$this->get_type()}/export", [$this, 'export'], 10, 2);
        add_filter("groundhogg/steps/{$this->get_type()}/enqueue", [$this, 'enqueue']);
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
     * Start a form table
     */
    protected function start_controls_section()
    {
        ?>
        <table class="form-table">
            <tbody>
        <?php
    }

    /**
     * End a form table
     */
    protected function end_controls_section()
    {
        ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * @param $args
     */
    protected function add_control( $args )
    {
        $args = wp_parse_args( $args, [ '' ] );
    }

    /**
     * Display the settings based on the given ID
     *
     * @param $step Step
     */
    abstract public function settings( $step );


    /**
     * Get the reporting view for the STEP
     * Most steps will use the default step reporting given here...
     *
     * @param $step
     */
    public function reporting( $step )
    {

        $start_time = WPGH()->menu->funnels_page->reporting_start_time;
        $end_time   = WPGH()->menu->funnels_page->reporting_end_time;

        $cquery = new WPGH_Contact_Query();

        if ( $this->group === 'action' ):

            $num_events_waiting = $cquery->query( array(
                'count' => true,
                'report' => array(
                    'step'  => $step->ID,
                    'funnel'=> $step->funnel_id,
                    'status'=> 'waiting'
                )
            ) );

            ?>
                <p class="report">
                    <?php _e('Waiting:', 'groundhogg') ?>
                    <a target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_contacts&view=report&status=waiting&funnel=' . $step->funnel_id . '&step=' . $step->ID ); ?>">
                        <b><?php echo $num_events_waiting; ?></b>
                    </a>
                </p>
            <hr>
            <?php
        endif;

        $num_events_completed = $cquery->query( array(
            'count' => true,
            'report' => array(
                'start' => $start_time,
                'end'   => $end_time,
                'step'  => $step->ID,
                'funnel'=> $step->funnel_id,
                'status'=> 'complete'
            )
        ) );

        ?>
        <p class="report">
            <?php _e('Completed:', 'groundhogg') ?>
            <a target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_contacts&view=report&status=complete&funnel=' . $step->funnel_id . '&step=' . $step->ID . '&start=' . $start_time . '&end=' . $end_time ); ?>">
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
     * @param $step Step
     */
    public function html( $step ){

        $closed = $step->get_meta( 'is_closed' ) ? 'closed' : '' ;

        ?>
        <div title="<?php echo $step->get_title() ?>" id="<?php echo $step->get_id(); ?>" class="postbox step <?php echo $step->get_group(); ?> <?php echo $step->get_type(); ?> <?php echo $closed; ?>">
            <button type="button" class="handlediv collapse"><span class="toggle-indicator" aria-hidden="true"></span></button>
            <input type="hidden" class="collapse-input" name="<?php echo $step->prefix( 'closed' ); ?>" value="<?php echo $step->get_meta( 'is_closed' ); ?>">
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
                    'name'  => $step->prefix( 'title' ),
                    'id'    => $step->prefix( 'title' ),
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
                                'name'   => $step->prefix( 'blog_id' ),
                                'id'     => $step->prefix( 'blog_id' ),
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
                <input type="hidden" name="steps[]" value="<?php echo $step->get_id(); ?>">
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
     * Save the step based on the given ID
     *
     * @param $step Step
     */
    abstract public function save( $step );

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