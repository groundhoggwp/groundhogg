<?php

namespace Groundhogg\Steps\Premium\Actions;

use Groundhogg\Steps\Actions\Action;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Create_User extends Action {

	use Trait_Premium_Step;

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/create-user/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Create User', 'step_name', 'groundhogg-pro' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'create_user';
	}

	public function get_sub_group() {
		return 'wordpress';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Create a new WordPress user account.', 'step_description', 'groundhogg-pro' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/create-user.svg';
	}
}
