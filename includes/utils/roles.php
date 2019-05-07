<?php
namespace Groundhogg;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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
    public function __construct()
    {
        add_action( 'admin_init', [ $this,  'install_roles_and_caps' ] );
    }

    public function install_roles_and_caps()
    {
        if ( ! $this->roles_are_installed() ){
            $this->add_roles();
            $this->add_caps();
        }
    }

    public function remove_roles_and_caps()
    {
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
     * @since 1.4.4
     * @return void
     */
    public function add_roles() {

        $roles = $this->get_roles();

        if ( empty( $roles ) ){
            return;
        }

        foreach ( $roles as $role => $role_args ){

            $role_args = wp_parse_args( $role_args, [
                'role' => '',
                'name' => '',
                'caps' => [],
            ] );

            list( $role, $name, $caps ) = $role_args;

            add_role( $role, $name, $caps );
        }

    }

    /**
     * Remove the roles from WPGH
     */
    public function remove_roles()
    {
        $roles = array_keys( $this->get_roles() );

        foreach ( $roles as $role ) {
            remove_role( $role );
        }
    }

    /**
     * Return the WP_Roles instance.
     *
     * @return \WP_Roles
     */
    public function get_wp_roles()
    {
        global $wp_roles;

        if ( class_exists('WP_Roles') ) {
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
     * @since  1.4.4
     * @global \WP_Roles $wp_roles
     * @return void
     */
    public function add_caps()
    {
        $wp_roles = $this->get_wp_roles();

        $roles = array_keys( $wp_roles->roles );

        foreach ( $roles as $role ){

            if ( method_exists($this, "get_{$role}_caps" ) ){

                $caps = call_user_func( [ $this, "get_{$role}_caps" ] );

                foreach ( $caps as $cap ){

                    $wp_roles->add_cap( $role, $cap );

                }

            }

        }

    }

    /**
     * Remove core post type capabilities (called on uninstall)
     *
     * @since 1.5.2
     * @return void
     */
    public function remove_caps()
    {

        $wp_roles = $this->get_wp_roles();

        $roles = array_keys($wp_roles->roles);

        foreach ($roles as $role) {

            if (method_exists($this, "get_{$role}_caps")) {

                $caps = call_user_func([$this, "get_{$role}_caps"]);

                foreach ($caps as $cap) {

                    $wp_roles->remove_cap($role, $cap);

                }

            }

        }

    }

    /**
     * Whether the custom roles have been installed
     *
     * @return bool
     */
    public function roles_are_installed()
    {
        $installed_roles = array_keys( wp_roles()->roles );
        $our_roles = array_keys( $this->get_roles() );

        // If our roles were installed this should be empty
        $missing_roles = array_diff( $our_roles, $installed_roles );
        return empty( $missing_roles );
    }

}
