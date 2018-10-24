<?php
/**
 * Email Confirmed
 *
 * This will run whenever an email is confirmed
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Email_Confirmed extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'email_confirmed';

    /**
     * @var string
     */
    public $group   = 'benchmark';

    /**
     * @var string
     */
    public $icon    = 'email-confirmed.png';

    /**
     * @var string
     */
    public $name    = 'Email Confirmed';

    /**
     * Add the completion action
     *
     * WPGH_Tag_Applied constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action( 'wpgh_email_confirmed', array( $this, 'complete' ), 10, 2 );
    }

    /**
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {
        ?>
        <table class="form-table">
            <tbody>
            <tr>
                <td>
                    <p class="description"><?php _e( 'Runs whenever an email is confirmed while in this funnel', 'groundhogg' ); ?></p>
                </td>
            </tr>
            </tbody>
        </table>

        <?php
    }

    /**
     * Save the step settings
     *
     * @param $step WPGH_Step
     */
    public function save( $step )
    {
        //code is poetry...
    }

    /**
     * Whenever an email is confirmed complete the following actions for the benchmark.
     *
     * @param $contact WPGH_Contact
     * @param $funnel_id int
     */
    public function complete( $contact, $funnel_id )
    {

        $steps = WPGH()->steps->get_steps( array( 'step_type' => $this->type, 'step_group' => $this->group, 'funnel_id' => $funnel_id ) );

        foreach ( $steps as $step ){

            $step = new WPGH_Step( $step->ID );

            if ( $step->can_complete( $contact ) ){

                $step->enqueue( $contact );

            }

        }

    }

    /**
     * Process the tag applied step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return true
     */
    public function run( $contact, $event )
    {
        //do nothing...

        return true;
    }

}