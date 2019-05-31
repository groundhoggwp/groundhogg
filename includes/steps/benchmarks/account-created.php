<?php
namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use function Groundhogg\create_contact_from_user;
use Groundhogg\DB\Steps;
use function Groundhogg\get_contactdata;
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
	 * @return string
	 */
	public function get_help_article()
	{
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/account-created/';
	}

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'New User', 'step_name', 'groundhogg' );

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
        return _x( 'Runs whenever a WordPress account is created. Will create a contact if one does not exist.', 'step_description', 'groundhogg' );
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
                'data'  => Plugin::$instance->roles->get_roles_for_select(),
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
     * @return int[]
     */
    protected function get_complete_hooks()
    {
        return [ 'user_register' => 1 ];
    }

    /**
     * @param $user_id
     */
    public function setup( $user_id )
    {
        $this->add_data( 'user_id', $user_id );
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
     * @return bool
     */
    protected function can_complete_step()
    {
        $role = $this->get_setting( 'role' );
        $step_roles = is_array( $role )? $role : [ $role ];
        $like_roles = array_intersect( $step_roles, $this->get_current_contact()->get_userdata()->roles );
        return !empty( $like_roles );
    }
}