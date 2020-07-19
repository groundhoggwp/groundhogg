<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use function Groundhogg\create_contact_from_user;
use Groundhogg\DB\Steps;
use function Groundhogg\get_contactdata;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;

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
		return _x( 'New WordPress User', 'step_name', 'groundhogg' );

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
		return _x( 'Runs whenever a WordPress account is created.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/account-created.png';
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$this->save_setting( 'role', array_map( 'sanitize_text_field', $this->get_posted_data( 'role', [ 'subscriber' ] ) ) );
	}

	/**
	 * @param mixed[] $context
	 * @param Step $step
	 *
	 * @return array|void
	 */
	public function context( $context, $step ) {
		$roles = $this->get_setting( 'role' );
		$context[ 'roles_display' ] = map_deep( $roles, function ( $role ){
			global $wp_roles;
			return [ 'value' => $role, 'label' => translate_user_role( $wp_roles->roles[ $role ][ 'name' ] ) ];
		} );

		return $context;
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