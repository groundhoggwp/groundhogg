<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-04
 * Time: 5:42 PM
 */

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
    public $name    = 'Role Changed';

    /**
     * Add the completion action
     *
     * WPGH_Form_Filled constructor.
     */
    public function __construct()
    {
        parent::__construct();

        add_action( 'set_user_role', array( $this, 'complete' ), 10, 3 );
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
                <th><?php echo esc_html__( 'Run this access is given:', 'groundhogg' ); ?></th>
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
    public function complete( $userId, $cur_role, $old_roles )
    {

        //todo list of possible funnel steps.
        $user_info = get_userdata( $userId );

        $contact = new WPGH_Contact( $user_info->user_email );

        if ( ! $contact->exists() ){

            /* create the contact */
            $new_contact = array(
                'first_name'    => $user_info->first_name,
                'last_name'     => $user_info->last_name,
                'email'         => $user_info->user_email,
                'user_id'       => $userId,
                'optin_status'  => WPGH_UNCONFIRMED,
                'date_created'  => current_time( 'mysql' )
            );

            $cid = WPGH()->contacts->add( $new_contact );

            $contact = new WPGH_Contact( $cid );

        }

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