<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-02
 * Time: 11:20 AM
 */

class WPGH_Step
{

    /**
     * The ID of the step
     *
     * @var int
     */
    public $ID;

    /**
     * The funnel this step is a child of
     *
     * @var int
     */
    public $funnel_id;

    /**
     * The step type
     *
     * @var string
     */
    public $type;

    /**
     * The step group
     *
     * @var string
     */
    public $group;

    /**
     * The step's order
     *
     * @var int
     */
    public $order;

    /**
     * The step title
     *
     * @var string
     */
    public $title;

    /**
     * The number of seconds to delay if this step is being enqueued
     *
     * @var int
     */
    public $queue_delay = 10;

    /**
     * WPGH_Step constructor.
     *
     * @param $id int ID of the step
     */
    public function __construct( $id )
    {
        $this->ID = intval( $id );

        $step = WPGH()->steps->get( $this->ID );

        if ( ! $step )
            return false;

        $this->setup_step( $step );
    }

    /**
     * Sets up the class given the DB step object
     *
     * @param $step
     * @return bool
     */
    public function setup_step( $step )
    {

        if ( ! is_object( $step ) ){

            return false;

        }

        $this->title        = $step->step_title;
        $this->funnel_id    = intval( $step->funnel_id );
        $this->order        = intval( $step->step_order );
        $this->type         = $step->step_type;
        $this->group        = $step->step_group;

        return ! empty( $this->type   ) && ! empty( $this->group  );

    }

    /**
     * Update the step with new info
     *
     * @param array $data
     * @return bool
     */
    public function update( $data = array() )
    {
        if ( empty( $data ) ) {
            return false;
        }

        //$data = $this->sanitize_columns( $data );

        do_action( 'wpgh_step_pre_update', $this->ID, $data );

        $updated = false;

        if ( WPGH()->steps->update( $this->ID, $data ) ) {

            $step = WPGH()->steps->get_step( $this->ID );
            $this->setup_step( $step );

            $updated = true;

        }

        do_action( 'wpgh_step_post_update', $updated, $this->ID, $data );

        return $updated;
    }


    /**
     * @return bool whether the step is a benchmark
     */
    public function is_benchmark()
    {
       return $this->group === 'benchmark';
    }

    /**
     * @return bool whether the step is an action
     */
    public function is_action()
    {
        return $this->group === 'action';
    }

    /**
     * Get the next step in the order
     *
     * @return WPGH_Step|false
     */
    public function get_next_step()
    {

        /* this will give an array of objects ordered by appearance in the funnel builder */

        $items = WPGH()->steps->get_steps( array(
            'funnel_id' => $this->funnel_id,
        ) );



        if (  empty( $items ) ){

            /* something went wrong or there are no more steps*/
            return false;

        }

        $i = $this->order;

        if ( $i >= count( $items ) ) {

            /* This is the last step. */
            return false;

        }

        if ( $items[ $i ]->group === 'action' ){

            /* regardless of whether the current step is an action
            or a benchmark we can run the next step if it's an action */
            return new WPGH_Step( $items[ $i ]->ID );

        }

        if ( $this->is_benchmark() ) {

            //todo verify comparison
            while ( $i < count( $items ) ) {

                if ( $items[ $i ]->group === 'action' ) {

                    return new WPGH_Step( $items[ $i ]->ID );

                }

                $i++;

            }

        }

        return false;

    }

    /**
     * Get the delay time for enqueueing the next action
     *
     * @return int
     */
    public function get_delay_time()
    {
        $time = apply_filters( 'wpgh_step_enqueue_time_' . $this->type, $this );

        if ( ! is_numeric( $time ) ) {
            $time = time() + 10;
        }

        return $time;
    }

    /**
     * Do the event when being processed from the event queue...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return bool whether it was successful or not
     */
    public function run( $contact, $event = null )
    {
        if ( ! $this->is_active() ) {
            /* Exit out, this step is inactive */
            return false;

        }

        do_action( 'wpgh_doing_funnel_step_' . $this->type . '_before', $this  );

        $result = apply_filters( 'wpgh_doing_funnel_step_' . $this->type , $contact, $event, $this );

        do_action( 'wpgh_doing_funnel_step_' . $this->type . '_after', $this  );

        //todo enqueue next step.
        $next_step = $this->get_next_step();

        if ( $next_step instanceof WPGH_Step && $next_step->is_active() ){

            $next_step->enqueue( $contact );

        }

        return $result;
    }

    /**
     * Create an event and add it to the queue
     *
     * @param $contact WPGH_Contact
     */
    public function enqueue( $contact )
    {

        //contact should NOT be present in the same funnel twice...

        WPGH()->events->mass_update(
            array(
                'status' => 'skipped'
            ),
            array(
                'funnel_id' => $this->funnel_id,
                'contact_id' => $contact->ID,
                'status' => 'waiting'
            )
        );

        $event = array(
            'time'          => $this->get_delay_time(),
            'funnel_id'     => $this->funnel_id,
            'step_id'       => $this->ID,
            'contact_id'    => $contact->ID
        );

        WPGH()->event_queue->add( $event );
    }

    /**
     * Whether this step can actually be completed
     * @param $contact WPGH_Contact
     * @return bool
     */
    public function can_complete( $contact=null )
    {
        if ( $this->type === 'action' )
            return false;

        return $this->is_active() && ( $this->is_starting() || $this->contact_in_funnel( $contact ) );
    }

    /**
     * Returns whether the contact is currently in the funnel
     *
     * @param $contact WPGH_Contact
     *
     * @return bool
     */
    public function contact_in_funnel( $contact )
    {
        return WPGH()->events->count( array( 'funnel_id' => $this->funnel_id, 'contact_id' => $contact->ID ) ) > 0;
    }


    /**
     * Return whether the step/funnel is active?
     *
     * @return bool
     */
    public function is_active()
    {

        return WPGH()->funnels->get_column_by( 'status', 'ID', $this->funnel_id ) === 'active' ;

    }

    /**
     * Whether the step starts a funnel
     *
     * @return bool
     */
    public function is_starting()
    {
        if ( $this->type === 'action' )
            return false;

        if ( $this->order === 1 )
            return true;

        $step_order = $this->order - 1;

        while ( $step_order > 0 ){

            $steps =  WPGH()->steps->get_steps( array( 'funnel_id' => $this->funnel_id, 'order' => $step_order ) );

            $step = array_shift( $steps );

            if ( $step->group === 'action' ){
                return false;
            }

            $step_order -= 1;
        }

        return true;
    }

    /**
     * Get Step meta
     *
     * @param $key
     * @return mixed
     */
    public function get_meta( $key )
    {
        return WPGH()->step_meta->get_meta( $this->ID, $key, true );
    }

    /**
     * Add step meta
     *
     * @param $key
     * @param $value
     * @return bool
     */
    public function add_meta( $key, $value )
    {
        return WPGH()->step_meta->add_meta( $this->ID, $key, $value );
    }

    /**
     * Update step meta
     *
     * @param $key
     * @param $value
     * @return bool
     */
    public function update_meta( $key, $value )
    {
        return WPGH()->step_meta->update_meta( $this->ID, $key, $value );

    }

    /**
     * Delete step meta
     *
     * @param $key
     * @return bool
     */
    public function delete_meta( $key )
    {
        return WPGH()->step_meta->delete_meta( $this->ID, $key );
    }

    /**
     * Return the name given with the ID prefixed for easy access in the $_POST variable
     *
     * @param $name
     * @return string
     */
    public function prefix( $name )
    {
        return $this->ID . '_' . esc_attr( $name );
    }

    /**
     * Get the ICON for the step.
     *
     * @see WPGH_Funnel_Step
     *
     * @return string
     */
    public function icon()
    {
        return apply_filters( 'wpgh_step_icon_' . $this->type, '' );
    }

    /**
     * Output the reporting section for the step...
     *
     * @see WPGH_Funnel_Step
     */
    public function reporting()
    {

        do_action( 'wpgh_get_step_reporting_' . $this->type, $this );

    }

    /**
     * Output the settigns section for the step...
     *
     * @see WPGH_Funnel_Step
     */
    public function settings()
    {

        do_action( 'wpgh_get_step_settings_' . $this->type, $this );

    }

    /**
     * Output the HTML of a step.
     */
    public function html()
    {

        ?>
        <div title="<?php echo $this->title ?>" id="<?php echo $this->ID; ?>" class="postbox step <?php echo $this->group; ?> <?php echo $this->type; ?>">

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
                <img class="hndle-icon" width="50" src="<?php echo $this->icon(); ?>">
                <?php $args = array(
                    'name'  => $this->prefix( 'title' ),
                    'id'    => $this->prefix( 'title' ),
                    'value' => __( $this->title, 'groundhogg' ),
                    'title' => __( 'Step Title', 'groundhogg' )
                );

                echo WPGH()->html->input( $args ); ?>

            </h2>

            <!-- INSIDE -->
            <div class="inside">

                <!-- DEFAULT ATTRIBUTES -->
                <?php $args = array(
                    'type'  => 'hidden',
                    'name'  => $this->prefix( 'order' ),
                    'id'    => $this->prefix( 'order' ),
                    'value' => $this->order,
                );
                echo WPGH()->html->input( $args ); ?>
                <input type="hidden" name="steps[]" value="<?php echo $this->ID; ?>">

                <!-- SETTINGS -->
                <div class="step-edit">

                    <div class="custom-settings">
                        <?php do_action( 'wpgh_step_settings_before', $this ); ?>
                        <?php $this->settings(); ?>
                        <?php do_action( 'wpgh_step_settings_after', $this ); ?>
                    </div>

                </div>

                <!-- REPORTING  -->
                <div class="step-reporting hidden">
                    <?php do_action( 'wpgh_step_reporting_before' ); ?>
                    <?php $this->reporting(); ?>
                    <?php do_action( 'wpgh_step_reporting_after' ); ?>
                </div>

            </div>

        </div>
        <?php

    }

    /**
     * Get the HTML of the step and return it.
     *
     * @return false|string
     */
    public function __toString()
    {

        ob_start();

        $this->html();

        $html = ob_get_clean();

        return $html;
    }


}