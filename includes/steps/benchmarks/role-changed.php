<?php
namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use function Groundhogg\create_contact_from_user;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

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
class Role_Changed extends Benchmark
{

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Role Changed', 'step_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'role_changed';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( "Runs whenever a user's role is changed.", 'step_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/role-changed.png';
    }


    /**
     * Add the completion action
     *
     * WPGH_Form_Filled constructor.
     */
    public function __construct()
    {

        parent::__construct();

        add_action( 'set_user_role', array( $this, 'complete' ), 10, 3 );
        add_action( 'add_user_role', array( $this, 'complete' ), 10, 2 );
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {
        $this->start_controls_section();

        $this->add_control( 'role', [
            'label'         => __( 'Run when this access is given:', 'groundhogg' ),
            'type'          => HTML::SELECT2,
            'default'       => 'subscriber',
            'description'   => __( 'Users with these roles will trigger this benchmark.', 'groundhogg' ),
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
        $this->save_setting( 'role', array_map( 'sanitize_text_field', $this->get_posted_data( 'role', [ 'subscriber' ] ) ) );
    }

    /**
     * get the hook for which the benchmark will run
     *
     * @return string[]
     */
    protected function get_complete_hooks()
    {
        return [ 'set_user_role' => 3, 'add_user_role' => 2 ];
    }

    /**
     * @param $userId int the ID of a user.
     * @param $cur_role string the new role of the user
     * @param $old_roles array list of previous user roles.
     */
    public function setup( $userId, $cur_role, $old_roles=array() )
    {
        $this->add_data( 'user_id', $userId );
        $this->add_data( 'role', $cur_role );
    }


    /**
     * Get the contact from the data set.
     *
     * @return Contact
     */
    protected function get_the_contact()
    {
        return create_contact_from_user( $this->get_data( 'user_id' ) );
    }

    /**
     * Based on the current step and contact,
     *
     * @return bool
     */
    protected function can_complete_step()
    {
        $role = $this->get_setting( 'role' );
        $step_roles = is_array( $role )? $role : [ $role ];
        $added_role = $this->get_data( 'role' );
        return in_array( $added_role, $step_roles );
    }
}