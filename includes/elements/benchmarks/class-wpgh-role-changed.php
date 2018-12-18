<?php
/**
 * Role Changed
 *
 * This will run whenever a user's role is changed to the specified role
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Role_Changed extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'role_changed';

    /**
     * @var string
     */
    public $group   = 'benchmark';

    /**
     * @var string
     */
    public $icon    = 'role-changed.png';

    /**
     * @var string
     */
    public $name    = 'Role Added/Changed';

    /**
     * Add the completion action
     *
     * WPGH_Form_Filled constructor.
     */
    public function __construct()
    {
        $this->description = __( 'Runs whenever a user\'s role is changed.', 'groundhogg' );

        parent::__construct();

        add_action( 'set_user_role', array( $this, 'complete' ), 10, 3 );
        add_action( 'add_user_role', array( $this, 'complete' ), 10, 2 );
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
                <th><?php echo esc_html__( 'Run when this access is given:', 'groundhogg' ); ?></th>
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
     * Run the benchmark for user role changes. Helpful for membership sites.
     *
     * @param $userId int the ID of a user.
     * @param $cur_role string the new role of the user
     * @param $old_roles array list of previous user roles.
     */
    public function complete( $userId, $cur_role, $old_roles=array() )
    {
        $user = get_userdata( $userId );
        $contact = wpgh_create_contact_from_user( $user );

        if ( ! is_admin() ){

            /* register front end which is technically an optin */
            $contact->update_meta( 'last_optin', time() );

        }

        $steps = WPGH()->steps->get_steps( array( 'step_type' => $this->type, 'step_group' => $this->group ) );

        foreach ( $steps as $step ) {

            $step = new WPGH_Step( $step->ID );

            $role = $step->get_meta( 'role' );

            if ( $step->can_complete( $contact ) && $cur_role === $role ){

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