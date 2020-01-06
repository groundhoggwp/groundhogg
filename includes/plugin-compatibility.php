<?php

namespace Groundhogg;

class Plugin_Compatibility
{

    public function __construct()
    {
        add_action( 'admin_init', [ $this, 'remove_unwanted_actions_and_filters_from_editors' ], 999 );
        add_action( 'admin_enqueue_scripts', [ $this, 'remove_styles_and_scripts_from_editors' ], 999 );
    }

    protected function is_editor_page()
    {
        $screen = get_current_screen();

        $action = get_request_var( 'action', 'view' );

        if ( $screen->id === 'groundhogg_page_gh_funnels' && $action === 'edit' ){
            return true;
        }

        if ( $screen->id === 'groundhogg_page_gh_emails' && $action === 'edit' && is_option_enabled('gh_use_advanced_email_editor') ){
            return true;
        }

        return false;
    }

    public function remove_unwanted_actions_and_filters_from_editors()
    {
        if ( ! $this->is_editor_page() ){
            return;
        }

        // Add actions that need to be removed here.
    }

    public function remove_styles_and_scripts_from_editors()
    {
        if ( ! $this->is_editor_page() ){
            return;
        }

        // Material WP compatibility
        if ( function_exists( 'initialize_material_wp' ) ){
            wp_dequeue_script( 'material-wp' );
            wp_dequeue_script( 'material-wp_dynamic' );
            wp_dequeue_style( 'material-wp' );
            wp_dequeue_style( 'material-wp_dynamic' );

            // Only way to prevent the loading of the parallax box in material WP is to set the vc value
            add_action( 'in_admin_header', function (){
                $_GET[ 'vc_action' ] = 'vc_inline';
            }, -201);

            // Remove it directly after to avoid conflicts
            add_action( 'in_admin_header', function (){
                unset( $_GET[ 'vc_action' ] );
            }, -199);
        }
    }

}
