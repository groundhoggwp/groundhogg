<?php
/**
 * Account Created
 *
 * This will run proceeding actions whenever a WordPRess acount is created
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Account_Created extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'account_created';

    /**
     * @var string
     */
    public $group   = 'benchmark';

    /**
     * @var string
     */
    public $icon    = 'account-created.png';

    /**
     * @var string
     */
    public $name    = 'Account Created';

    /**
     * Add the completion action
     *
     * WPGH_Form_Filled constructor.
     */
    public function __construct()
    {

        $this->name         = _x( 'New User', 'element_name', 'groundhogg' );
        $this->description  = _x( 'Runs whenever a WordPress account is created. Will create a contact if one does not exist.', 'element_description', 'groundhogg' );

        parent::__construct();

        add_action( 'wpgh_user_created', array( $this, 'complete' ), 10, 2 );
    }

    /**
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {
        $account_role = $step->get_meta( 'role' );

        if ( ! $account_role )
            $account_role = 'subscriber'

        ?>

        <table class="form-table">
            <tbody>
            <tr>
                <th><?php echo esc_html__( 'Run when the following type of account is created:', 'groundhogg' ); ?></th>
                <td>
                    <select name="<?php echo $step->prefix( 'role' ); ?>" id="<?php echo $step->prefix( 'role' ); ?>">
                        <?php wp_dropdown_roles( $account_role ); ?>
                    </select>
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
        if ( isset(  $_POST[ $step->prefix( 'role' ) ] ) ){

            $role = sanitize_text_field( $_POST[ $step->prefix( 'role' ) ] );
            $step->update_meta( 'role', $role );

        }

    }

    /**
     * Whenever a form is filled complete the benchmark.
     *
     * @param $user WP_User
     * @param $contact WPGH_Contact
     */
    public function complete( $user, $contact )
    {

        $steps = WPGH()->steps->get_steps( array( 'step_type' => $this->type, 'step_group' => $this->group ) );

        foreach ( $steps as $step ) {

            $step = wpgh_get_funnel_step( $step->ID );

            $role = $step->get_meta( 'role' );

            if ( $step->can_complete( $contact ) && in_array( $role, $user->roles ) ){

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