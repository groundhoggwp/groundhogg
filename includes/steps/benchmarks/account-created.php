<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\array_bold;
use function Groundhogg\html;
use function Groundhogg\orList;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Account Created
 *
 * This will run proceeding actions whenever a WordPRess acount is created
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Account_Created extends Benchmark {

	/**
	 * The step type
	 */
	const TYPE = 'account_created';

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/account-created/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'User Created', 'step_name', 'groundhogg' );
	}

	public function get_sub_group() {
		return 'wordpress';
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return self::TYPE;
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Runs whenever a WordPress account with the specified role(s) is created.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
//		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/account-created.png';
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/wordpress/user-created.svg';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		html( 'p', [], 'Run when a user account with any of the following roles is created...' );

		html( html()->select2( [
			'name'        => $this->setting_name_prefix( 'role' ) . '[]',
			'multiple'    => true,
			'placeholder' => 'Any role...',
			'selected'    => $this->get_setting( 'role' ),
			'options'     => Plugin::$instance->roles->get_roles_for_select()
		] ) );

        html( 'p' );
	}

	public function generate_step_title( $step ) {

		$roles = $this->get_setting( 'role' );
		$roles = array_map( function ( $role ) {

			if ( ! wp_roles()->is_role( $role ) ) {
				return '';
			}

			return translate_user_role( wp_roles()->roles[ $role ]['name'] );
		}, $roles );

        $role = ! empty( $roles) ? orList( array_bold( $roles ) ) : 'user';

		return 'When a ' . $role . ' is created';
	}

	public function get_settings_schema() {
		return [
			'role' => [
				'default'  => [],
				'if_undefined' => [],
				'sanitize' => function ( $roles ) {
					if ( ! is_array( $roles ) ) {
						return [];
					}

					return array_filter( $roles, [ wp_roles(), 'is_role' ] );
				},
			]
		];
	}

	/**
	 * get the hook for which the benchmark will run
	 *
	 * @return int[]
	 */
	protected function get_complete_hooks() {
		return [ 'groundhogg/contact_created_from_user' => 2 ];
	}

	/**
	 * @param $user    \WP_User
	 * @param $contact Contact
	 */
	public function setup( $user, $contact ) {
		$this->add_data( 'user', $user );
		$this->add_data( 'contact', $contact );
	}

	/**
	 * Get the contact from the data set.
	 *
	 * @return Contact
	 */
	protected function get_the_contact() {
		return $this->get_data( 'contact' );
	}

	/**
	 * @return bool
	 */
	protected function can_complete_step() {
		$role       = $this->get_setting( 'role' );
		$step_roles = is_array( $role ) ? $role : [ $role ];
		$like_roles = array_intersect( $step_roles, $this->get_current_contact()->get_userdata()->roles );

		return ! empty( $like_roles );
	}
}
