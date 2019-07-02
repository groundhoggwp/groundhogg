<?php
namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use function Groundhogg\html;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Delay Timer
 *
 * This allows the adition of an event which "does nothing" but runs at the specified time according to the time provided.
 * Essentially delaying proceeding events.
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
class Advanced_Timer extends Action
{
    /**
     * @return string
     */
    public function get_help_article()
    {
        return 'https://docs.groundhogg.io/docs/builder/actions/delay-timer/';
    }

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Advanced Timer', 'step_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'advanced_timer';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Pause for the specified amount of time.', 'step_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/delay-timer.png';
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {

        $html = Plugin::$instance->utils->html;

        $html->start_form_table();

        $html->start_row();

        $html->th( __( 'Time', 'groundhogg' ) );

        $html->td( [
            // DELAY AMOUNT
            $html->input( [
                'name'          => $this->setting_name_prefix( 'delay_amount' ),
                'id'            => $this->setting_id_prefix( 'delay_amount' ),
                'value'         => $this->get_setting( 'delay_amount', '' ),
                'placeholder'   => 'next tuesday',
                'title' => __( 'Time is stored in UTC-0' ),
            ] ),
            $html->wrap( __( 'Local Time: ' ) . $html->wrap( $this->get_date_as_string(), 'b' ), 'p', [] )
        ] );

        $html->end_row();

        $html->end_form_table();
    }

    protected function get_date_as_string()
    {
        return date_i18n( get_option( 'date_format' ) . ' \@ h:i a', Plugin::$instance->utils->date_time->convert_to_local_time( $this->get_run_time() ) );
    }

    protected function get_run_time()
    {
        return strtotime( $this->get_setting( 'delay_amount' ) );
    }

    /**
     * Save the step settings
     *
     * @param $step Step
     */
    public function save( $step )
    {
        $this->save_setting( 'delay_amount', sanitize_text_field( $this->get_posted_data( 'delay_amount' ) ) );

        if ( $this->get_run_time() < time() ){
            Plugin::$instance->notices->add( new \WP_Error( 'invalid_date', __( 'A timer has a date set in the past and may cause unexpected behaviour.', 'groundhogg' ) ) );
        }
    }

    /**
     * Override the parent and set the run time of this function to the settings
     *
     * @param Step $step
     * @return int
     */
    public function enqueue( $step )
    {
        return $this->get_run_time();
    }
    /**
     * Process the apply tag step...
     *
     * @param $contact Contact
     * @param $event Event
     *
     * @return true
     */
    public function run( $contact, $event )
    {
        //do nothing
        return true;
    }
}