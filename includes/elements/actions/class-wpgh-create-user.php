<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-04
 * Time: 5:42 PM
 */

class WPGH_Create_User extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'create_user';

    /**
     * @var string
     */
    public $group   = 'action';

    /**
     * @var string
     */
    public $icon    = 'create-user.png';

    /**
     * @var string
     */
    public $name    = 'Create User';

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
                <th><?php echo esc_html__( 'Which account level would you like to grant?', 'groundhogg' ); ?></th>
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

        $role = sanitize_text_field( $_POST[ $step->prefix( 'role' ) ] );
        $step->update_meta( 'role', $role );

    }

    /**
     * Process the apply tag step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return true
     */
    public function run( $contact, $event )
    {
	    $username = $contact->email;

	    $password = wp_generate_password();
	    $email_address = $contact->email;

	    $role = $event->step->get_meta( 'role' );

	    if ( ! username_exists( $username ) && ! email_exists( $email_address ) ) {

	        $user_id = wp_create_user( $username, $password, $email_address );
		    $user = new WP_User( $user_id );
		    $user->set_role( $role );

		    $user->first_name = $contact->first_name;

		    wp_update_user( $user );

		    wp_new_user_notification( $user_id, null, 'user' );

		    $contact->update( array( 'user_id', $user_id ) );

	    }

	    return true;
    }

}