<?php
namespace Groundhogg\Steps\Actions;


use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

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
class Create_User extends Action
{

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Create User', 'step_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'create_user';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Create a WP User account at the specified level. Username is the contact\'s email.', 'step_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/create-user.png';
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {

        $this->start_controls_section();

        $this->add_control( 'role', [
            'label'         => __( 'User Role:', 'groundhogg' ),
            'type'          => HTML::DROPDOWN,
            'default'       => 'subscriber',
            'description'   => __( 'This role will be added to the new user. If the user already exists, the role will be added in addition to existing roles.', 'groundhogg' ),
            'field'         => [
                'options'     => Plugin::$instance->roles->get_roles_for_select(),
            ],
        ] );

        if ( Plugin::$instance->settings->is_global_multisite() ){

            $sites = get_sites();
            $options = array();

            foreach ( $sites as $site ){
                $options[ $site->blog_id ] = get_blog_details($site->blog_id)->blogname;
            }

            $this->add_control( 'add_to_blog_id', [
                'label'         => __( 'Which Site?', 'groundhogg' ),
                'type'          => HTML::DROPDOWN,
                'field'         => [
                    'options'     => $options,
                    'option_none' => __( 'Add to all sites', 'groundhogg' )
                ],
            ] );

        }

        $this->end_controls_section();

    }

    /**
     * Save the step settings
     *
     * @param $step Step
     */
    public function save( $step )
    {
        $this->save_setting( 'role', sanitize_text_field( $this->get_posted_data( 'role', 'subscriber' ) ) );

        if ( Plugin::$instance->settings->is_global_multisite() ){
            $this->save_setting( 'add_to_blog_id', absint( $this->get_posted_data( 'add_to_blog_id', get_current_blog_id() ) ) );
        }
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
	    $username = $contact->get_email();
        $email_address = $contact->get_email();

        $password = wp_generate_password();

	    $role = $this->get_setting( 'role', 'subscriber' );

	    if ( ! username_exists( $username ) && ! email_exists( $email_address ) ) {

	        $user_id = wp_create_user( $username, $password, $email_address );
		    $user = new \WP_User( $user_id );
		    $user->set_role( $role );

		    $user->first_name = $contact->get_first_name();

		    wp_update_user( $user );
		    wp_new_user_notification( $user_id, null, 'user' );
		    $contact->update( array( 'user_id' => $user_id  ) );

	    } else {

	        $user = get_user_by( 'email', $username );

	        if ( ! $user ){
	            return false;
            }

	        /**
	         * @since 1.0.19.4 update the user role if a user account already exists...
	         */
	        $user->add_role( $role );

	        $user_id = $user->ID;
	        $contact->update( [ 'user_id' => $user_id ] );
        }

        if ( Plugin::$instance->settings->is_global_multisite() ){
            if( $this->get_setting( 'add_to_blog_id' ) ){
                add_user_to_blog( absint( $this->get_setting( 'add_to_blog_id' ) ), $user_id, $role );
            } else {
                $sites = get_sites();
                foreach ( $sites as $site ){
                    add_user_to_blog( $site->blog_id, $user_id, $role );
                }
            }
        }

	    return true;
    }
}