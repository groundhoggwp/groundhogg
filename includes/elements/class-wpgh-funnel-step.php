<?php
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

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class WPGH_Funnel_Step
{

    /**
     * @var $type string the type of action
     */
    public $type = '';

    /**
     * @var $name string the Display name of the action
     */
    public $name = '';

    /**
     * @var $icon string url to icon.
     */
    public $icon = '';

    /**
     * @var string the group this element belongs to
     */
    public $group = '';

    /**
     * Description of the step
     *
     * @var string
     */
    public $description = '';

    /**
     * The current step
     *
     * @var WPGH_Step
     */
    protected $cur_step = null;

    /**
     * Delay time for enqueue the event.
     *
     * @var int
     */
    protected $delay_time = 0;

    /**
     * Setup all of the filters and actions to register this step and save it.
     *
     * WPGH_Funnel_Step constructor.
     */
    public function __construct()
    {

        if (is_admin()) {
//            add_filter( 'wpgh_funnel_' . $this->group . 's', array( $this, 'register' ) );
//            add_action( 'wpgh_get_step_settings_' . $this->type, array( $this, 'settings' ) );
//            add_action( 'wpgh_get_step_reporting_' . $this->type, array( $this, 'reporting' ) );
//            add_action( 'wpgh_save_step_' . $this->type, array( $this, 'save' ) );
//            add_filter( 'wpgh_step_icon_' . $this->type, array( $this, 'icon' ) );

            /**
             * New filters/actions for better usability and extendability
             *
             * @since 1.1
             */
            add_filter("groundhogg/elements/{$this->get_group()}s", array($this, 'register'));
            add_action("groundhogg/elements/{$this->get_type()}/settings", array($this, 'settings'));
            add_action("groundhogg/elements/{$this->get_type()}/reporting", array($this, 'reporting'));
            add_action("groundhogg/elements/{$this->get_type()}/save", array($this, 'save'));
            add_filter("groundhogg/elements/{$this->get_type()}/icon", array($this, 'icon'));
        }

//        add_action( 'wpgh_import_step_' . $this->type, array( $this, 'import' ), 10, 2 );
//        add_filter( 'wpgh_export_step_' . $this->type, array( $this, 'export' ), 10, 2 );
//        add_filter( 'wpgh_step_enqueue_time_' . $this->type, array( $this, 'enqueue' ) );
//        add_filter( 'wpgh_doing_funnel_step_' . $this->type, array( $this, 'run' ), 10, 2 );

        /**
         * New filters/actions for better usability and extendability
         *
         * @since 1.1
         */
        add_action("groundhogg/elements/{$this->get_type()}/import", array($this, 'import'), 10, 2);
        add_filter("groundhogg/elements/{$this->get_type()}/export", array($this, 'export'), 10, 2);
        add_filter("groundhogg/elements/{$this->get_type()}/enqueue", array($this, 'enqueue'));
        add_filter("groundhogg/elements/{$this->get_type()}/run", array($this, 'run'), 10, 2);
    }

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     * Get the element group
     *
     * @return string
     */
    public function get_group()
    {
        return $this->group;
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return $this->description;
    }

    /**
     * Get the icon
     *
     * @param string $default
     * @return string
     */
    public function get_icon( $default='' )
    {
        return $this->icon( $default='' );
    }

    /**
     * Enqueue the step in the event queue...
     *
     * @param $step WPGH_Step
     *
     * @return int
     */
    public function enqueue( $step )
    {
        return time() + $this->delay_time;
    }

    /**
     * Get the ICON of this action/benchmark
     *
     * @param $default string ICON
     * @return string
     */
    public function icon( $default='' )
    {
        if ( strpos( $this->icon, '/' ) === false ){
            $this->icon = WPGH_PLUGIN_URL . '/assets/images/funnel-icons/' . $this->icon;
        }

        return $this->icon;
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
     * Sets the step that's being processed...
     *
     * @param $step WPGH_Step
     * @return false|WPGH_Step
     */
    public function set_current_step( $step )
    {
        if ( ! $step instanceof WPGH_Step )
            return false;

        $this->cur_step = $step;

        return $this->cur_step;
    }

    /**
     * Display the settings based on the given ID
     *
     * @param $step WPGH_Step
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
     * Save the step based on the given ID
     *
     * @param $step WPGH_Step
     */
    abstract public function save( $step );

    /**
     * Run the action/benchmark
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return bool
     */
    public function run( $contact, $event )
    {
        return true;
    }

    /**
     * @param $args array of args
     * @param $step WPGH_Step
     */
    public function import( $args, $step ){
        //silence is golden
    }

    /**
     * @param $args array of args
     * @param $step WPGH_Step
     *
     * @return array
     */
    public function export( $args, $step ){
        //silence is golden
        return $args;
    }
}