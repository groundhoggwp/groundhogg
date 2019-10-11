<?php
namespace Groundhogg\Admin;

use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use Groundhogg\Plugin;
use function Groundhogg\isset_not_empty;

/**
 * Abstract Admin Page
 *
 * This is a base class for all admin pages
 *
 * @package     Admin
 * @subpackage  Admin
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

abstract class Tabbed_Admin_Page extends Admin_Page
{
    /**
     * array of [ 'name', 'slug' ]
     *
     * @return array[]
     */
    abstract protected function get_tabs();

    /**
     * Get the current tab.
     *
     * @return mixed
     */
    protected function get_current_tab()
    {
        $tabs = $this->get_tabs();
        return get_request_var( 'tab', $tabs[ 0 ][ 'slug' ] );
    }

    /**
     * Output HTML for the page tabs
     */
    protected function do_page_tabs()
    {
        ?>
        <!-- BEGIN TABS -->
        <h2 class="nav-tab-wrapper">
            <?php foreach ( $this->get_tabs() as $id => $tab ): ?>
                <a href="?page=<?php echo $this->get_slug(); ?>&tab=<?php echo $tab[ 'slug' ]; ?>" class="nav-tab <?php echo $this->get_current_tab() ==  $tab[ 'slug' ] ? 'nav-tab-active' : ''; ?>"><?php _e(  $tab[ 'name' ], 'groundhogg'); ?></a>
            <?php endforeach; ?>
        </h2>
        <?php
    }

    /**
     * Process the given action
     */
    public function process_action()
    {

        if ( !$this->get_current_action() || !$this->verify_action() )
            return;

        $base_url = remove_query_arg( [ '_wpnonce', 'action' ], wp_get_referer() );

        $func = sprintf( "process_%s_%s", $this->get_current_tab(), $this->get_current_action() );
        $backup_func = sprintf( "process_%s", $this->get_current_action() );

        // Check for tab method
        if ( method_exists( $this, $func ) ){
            $exitCode = call_user_func( [ $this, $func ] );
        // check for global method
        } else if ( method_exists( $this, $backup_func ) ){
            $exitCode = call_user_func( [ $this, $backup_func ] );
        }

        set_transient('groundhogg_last_action', $this->get_current_action(), 30 );

        if ( is_wp_error( $exitCode ) ){
            $this->add_notice( $exitCode );
            return;
        }

        if (is_string($exitCode) && esc_url_raw($exitCode)) {
            wp_safe_redirect( $exitCode );
            die();
        }

        // Return to self if true response.
        if ( $exitCode === true ){
            return;
        }

        // IF NULL return to main table
        $base_url = add_query_arg('ids', urlencode(implode(',', $this->get_items())), $base_url);

        wp_safe_redirect( $base_url );
        die();
    }

    /**
     * Modified Admin page to support tabbing.
     */
    public function page()
    {

        do_action( "groundhogg/admin/{$this->get_slug()}", $this );
        do_action( "groundhogg/admin/{$this->get_slug()}/{$this->get_current_tab()}", $this );

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo $this->get_title(); ?></h1>
            <?php $this->do_title_actions(); ?>
            <?php $this->notices(); ?>
            <hr class="wp-header-end">
            <?php $this->do_page_tabs(); ?>
            <?php

            $method = sprintf( '%s_%s', $this->get_current_tab(), $this->get_current_action() );
            $backup_method = sprintf( '%s_%s', $this->get_current_tab(), 'view' );

            if ( method_exists( $this, $method ) ){
                call_user_func( [ $this, $method ] );
            } else if ( has_action( "groundhogg/admin/{$this->get_slug()}/display/{$method}" ) ){
                do_action( "groundhogg/admin/{$this->get_slug()}/display/{$method}", $this );
            } else if ( method_exists( $this, $backup_method ) ) {
                call_user_func( [ $this, $backup_method ] );
            }

            ?>
        </div>
        <?php
    }

    public function view()
    {
        return false;
    }
}