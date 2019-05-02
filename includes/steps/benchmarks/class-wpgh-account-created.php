<?php
namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\DB\Steps;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

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
class Account_Created extends Benchmark
{

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'New User', 'element_name', 'groundhogg' );

    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'account_created';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Runs whenever a WordPress account is created. Will create a contact if one does not exist.', 'element_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/account-created.png';
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {
        $this->start_controls_section();

        $this->add_control( 'role', [
            'label'         => __( 'User Role(s):', 'groundhogg' ),
            'type'          => HTML::SELECT2,
            'default'       => 'subscriber',
            'description'   => __( 'New users with these roles will trigger this benchmark.', 'groundhogg' ),
            'field'         => [
                'multiple' => true,
                'options'  => Plugin::$instance->roles->get_roles_for_select(),
            ],
        ] );

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
    }

    /**
     * Here you must define the action to listen for.
     *
     * For example, add_action( 'action_to_listen_for', [ $this, 'complete' ], 10, 2 );
     *
     * @return void
     */
    protected function add_complete_action()
    {
        add_action( 'user_register', array( $this, 'complete' ), 10, 1 );
    }

    protected function condition($step, $contact, $args)
    {
        // TODO: Implement condition() method.
    }

    /**
     * Whenever a form is filled complete the benchmark.
     *
     * @param $user \WP_User
     * @param $contact t
     */
    public function complete( $user_id )
    {

        $steps = $this->get_like_steps();

        foreach ( $steps as $step ) {

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