<?php
namespace Groundhogg;

class Settings
{
    
    /**
     * Check if the site is global multisite enabled
     *
     * @return false
     * @deprecated
     */
    public function is_global_multisite()
    {
        if ( ! is_multisite() ){
            return false;
        }

        if ( is_multisite() && ! get_site_option( 'gh_global_db_enabled' ) ){
            return false;
        }

//        return true;
        return false;
    }

    /**
     * Prefix the option name.
     *
     * @param $option
     * @return string
     */
    protected function prefix( $option )
    {
        if ( ! preg_match( '/^gh_.*/', $option ) || preg_match( '/^wpgh_.*/', $option )  ) {
            return 'gh_' . $option;
        }

        return $option;
    }
    
    /**
     * Generic function for checking checkboxes from the Groundhogg settings.
     *
     * @param string $key
     * @return bool
     */
    public function is_option_enabled( $key = '' )
    {
        $option = $this->get_option( $key, [] );

        if ( ! is_array( $option ) && $option ){
            return true;
        }

        return is_array( $option ) && in_array( 'on', $option );
    }

    /**
     * Swicth between the main site options if on a multisite network.
     *
     * @param $key
     * @param bool $default
     *
     * @return mixed
     */
    public function get_option( $key, $default=false )
    {
        $key = $this->prefix( $key );
        if ( $this->is_global_multisite() ){
            return get_blog_option( get_network()->site_id, $key, $default );
        } else {
            return get_option( $key, $default );
        }

    }

    /**
     * update option wrapper
     *
     * @return mixed
     */
    public function update_option( $key, $value ){
        $key = $this->prefix( $key );
        if ( $this->is_global_multisite() ){
            return update_blog_option( get_network()->site_id, $key, $value );
        } else {
            return update_option( $key, $value );
        }
    }

    /**
     * delete option wrapper
     *
     * @return mixed
     */
    public function delete_option( $key ){
        $key = $this->prefix( $key );
        if ( $this->is_global_multisite() ){
            return delete_blog_option( get_network()->site_id, $key );
        } else {
            return delete_option( $key );
        }
    }

    /**
     * get_transient wrapper
     *
     * @param $key
     * @return mixed
     */
    public function get_transient( $key ){
        $key = $this->prefix( $key );
        if ( $this->is_global_multisite() ){
            return get_site_transient( $key );
        } else {
            return get_transient( $key );
        }
    }

    /**
     * delete_transient wrapper
     *
     * @param $key
     * @return mixed
     */
    public function delete_transient( $key ){
        $key = $this->prefix( $key );
        if ( $this->is_global_multisite() ){
            return delete_site_transient( $key );
        } else {
            return delete_transient( $key );
        }
    }

    /**
     * Set transient wrapper
     *
     * @param $key
     * @param $value
     * @param $exp
     * @return bool
     */
    public function set_transient( $key, $value, $exp ){
        $key = $this->prefix( $key );
        if ( $this->is_global_multisite() ){
            return set_site_transient( $key, $value, $exp );
        } else {
            return set_transient( $key, $value, $exp );
        }
    }

}