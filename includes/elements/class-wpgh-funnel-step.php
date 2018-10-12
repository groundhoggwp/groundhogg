<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-20
 * Time: 12:32 PM
 */

class WPGH_Funnel_Step
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
    protected $delay_time = 10;

    /**
     * Setup all of the filters and actions to register this step and save it.
     *
     * WPGH_Funnel_Step constructor.
     */
    public function __construct()
    {


        if( is_admin() ){
            add_filter( 'wpgh_funnel_' . $this->group . 's', array( $this, 'register' ) );
            add_action( 'wpgh_get_step_settings_' . $this->type, array( $this, 'settings' ) );
            add_action( 'wpgh_get_step_reporting_' . $this->type, array( $this, 'reporting' ) );
            add_action( 'wpgh_import_step_' . $this->type, array( $this, 'import' ), 10, 2 );
            add_filter( 'wpgh_export_step_' . $this->type, array( $this, 'export' ), 10, 2 );
            add_action( 'wpgh_save_step_' . $this->type, array( $this, 'save' ) );
            add_filter( 'wpgh_step_icon_' . $this->type, array( $this, 'icon' ) );
        }

        add_filter( 'wpgh_step_enqueue_time_' . $this->type, array( $this, 'enqueue' ) );
        add_filter( 'wpgh_doing_funnel_step_' . $this->type, array( $this, 'run' ), 10, 2 );
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

        return $this->delay_time;

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
		$array[ $this->type ] = array(
			'title' =>__( $this->name, 'groundhogg' ),
			'icon'  => $this->icon(),
            'group' => $this->group
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
    public function settings( $step )
    {
        _doing_it_wrong( __FUNCTION__, __( 'You should not be calling the SETTINGS method of the parent class. You should be overriding it with a child method.', 'groundhogg' ), '1.0' );
    }

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
    public function save( $step )
    {
        _doing_it_wrong( __FUNCTION__, __( 'You should not be calling the SAVE method of the parent class. You should be overriding it with a child method.', 'groundhogg' ), '1.0' );
    }

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
        _doing_it_wrong( __FUNCTION__, __( 'You should not be calling the RUN method of the parent class. You should be overriding it with a child method.', 'groundhogg' ), '1.0' );

        return false;
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