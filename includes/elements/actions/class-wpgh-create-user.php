<?php
/**
 * Create User
 *
 * Creates a WordPress user account for the contact, or assigns one to the contact if one exists.
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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
     * @var string
     */
    public $description = 'Create a WP User account at the specified level. Username is the contact\'s email.';

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

	    } else {

	        $user = get_user_by_email( $username );

	        $contact->update( array( 'user_id' => $user->ID ) );

        }

	    return true;
    }

}