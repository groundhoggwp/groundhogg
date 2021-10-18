<?php

namespace Groundhogg;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Roles Class
 *
 * This class handles the role creation and assignment of capabilities for those roles.
 *
 * These roles let us have Sales People, Marketers, etc, each of whom can do
 * certain things within the CRM
 *
 * @since 1.4.4
 */
abstract class Roles {

	/**
	 * Roles constructor.
	 */
	public function __construct() {
		add_action( 'groundhogg/activated', [ $this, 'install_roles_and_caps' ] );
		add_filter( 'map_meta_cap', [ $this, 'map_meta_cap' ], 10, 4 );
	}

	/**
	 * Map cap to primitive
	 *
	 * @param $caps    array
	 * @param $cap     string
	 * @param $user_id int
	 * @param $args    array
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		return $caps;
	}

	public function install_roles_and_caps() {
		$this->add_roles();
		$this->add_caps();
	}

	public function remove_roles_and_caps() {
		$this->remove_caps();
		$this->remove_roles();
	}

	/**
	 * Returns an array  of role => [
	 *  'role' => '',
	 *  'name' => '',
	 *  'caps' => []
	 * ]
	 *
	 * In this case caps should just be the meta cap map for other WP related stuff.
	 *
	 * @return array[]
	 */
	abstract public function get_roles();

	/**
	 * Add new shop roles with default WP caps
	 *
	 * @return void
	 * @since 1.4.4
	 */
	public function add_roles() {

		$roles = $this->get_roles();

		if ( empty( $roles ) ) {
			return;
		}

		foreach ( $roles as $role_args ) {

			$role_args = wp_parse_args( $role_args, [
				'role' => '',
				'name' => '',
				'caps' => [],
			] );

			add_role( $role_args['role'], $role_args['name'], $role_args['caps'] );
		}

	}

	/**
	 * Remove the roles from Groundhogg
	 */
	public function remove_roles() {
		$roles = $this->get_roles();

		foreach ( $roles as $role ) {
			$this->get_wp_roles()->remove_role( $role['role'] );
		}
	}

	/**
	 * Return the WP_Roles instance.
	 *
	 * @return \WP_Roles
	 */
	public function get_wp_roles() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new \WP_Roles();
			}
		}

		return $wp_roles;
	}

	/**
	 * Add new capabilities
	 * it is advised that any custom roles have been added before calling this function.
	 *
	 * @return void
	 * @global \WP_Roles $wp_roles
	 * @since  1.4.4
	 */
	public function add_caps() {
		$wp_roles = $this->get_wp_roles();

		$roles = array_keys( $wp_roles->roles );

		foreach ( $roles as $role ) {

			if ( method_exists( $this, "get_{$role}_caps" ) ) {

				$caps = call_user_func( [ $this, "get_{$role}_caps" ] );

				foreach ( $caps as $cap ) {

					$wp_roles->add_cap( $role, $cap );

				}

			}

		}
	}

	/**
	 * Remove core post type capabilities (called on uninstall)
	 *
	 * @return void
	 * @since 1.5.2
	 */
	public function remove_caps() {

		$wp_roles = $this->get_wp_roles();

		$roles = array_keys( $wp_roles->roles );

		foreach ( $roles as $role ) {

			if ( method_exists( $this, "get_{$role}_caps" ) ) {

				$caps = call_user_func( [ $this, "get_{$role}_caps" ] );

				foreach ( $caps as $cap ) {

					$wp_roles->remove_cap( $role, $cap );

				}

			}

		}

	}

	/**
	 * Return a cap to check against the admin to ensure caps are also installed.
	 *
	 * @return mixed
	 */
	abstract protected function get_admin_cap_check();

	/**
	 * Whether the custom roles have been installed
	 *
	 * @return bool
	 */
	public function roles_are_installed() {
		$installed_roles = array_keys( wp_roles()->roles );
		$our_roles       = wp_list_pluck( $this->get_roles(), 'role' );

		// If our roles were installed this should be empty
		$missing_roles = array_diff( $our_roles, $installed_roles );

		// There are no missing roles and the admin capo is there.
		return empty( $missing_roles ) && get_role( 'administrator' )->has_cap( $this->get_admin_cap_check() );
	}

}
