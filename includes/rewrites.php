<?php
namespace Groundhogg;

class Rewrites
{
    /**
     * Rewrites constructor.
     */
    public function __construct()
    {
        add_action( 'init', [ $this, 'add_rewrite_rules' ] );
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
        add_filter( 'request', [ $this, 'parse_query' ] );
        add_filter( 'template_include', [ $this, 'template_include' ] );
        add_action( 'template_redirect', [ $this, 'template_redirect' ] );
    }

    /**
     * Add the rewrite rules required for the Preferences center.
     */
    public function add_rewrite_rules()
    {
        // View Emails
        add_rewrite_rule(
            '^gh/browser-view/emails/([^/]*)/?$',
            'index.php?pagenow=browser_view&email_id=$matches[1]',
            'top'
        );

        // View Emails
        add_rewrite_rule(
            '^gh/emails/([^/]*)/?$',
            'index.php?pagenow=emails&email_id=$matches[1]',
            'top'
        );

        // Superlink Rewrite
        // http://localhost/wp1/gh/superlinks/link/1
        add_rewrite_rule(
            '^superlinks/link/([^/]*)/?$',
            'index.php?pagenow=superlink&superlink_id=$matches[1]',
            'top'
        );

        // New tracking structure.
        add_rewrite_rule(
            '^gh/superlinks/link/([^/]*)/?$',
            'index.php?pagenow=superlink&superlink_id=$matches[1]',
            'top'
        );
    }

    /**
     * Add the query vars needed to manage the request.
     *
     * @param $vars
     * @return array
     */
    public function add_query_vars( $vars )
    {
        $vars[] = 'pagenow';
        $vars[] = 'superlink_id';
        $vars[] = 'email_id';
        return $vars;
    }

    /**
     * Maps a function to a specific query var.
     *
     * @param $query
     * @return mixed
     */
    public function parse_query( $query )
    {
        $this->map_query_var( $query, 'email_id', 'absint' );
        $this->map_query_var( $query, 'superlink_id', 'absint' );
        return $query;
    }

    /**
     * @return Template_Loader
     */
    public function get_template_loader()
    {
        return new Template_Loader();
    }

    /**
     * Overwrite the existing template with the manage preferences template.
     *
     * @param $template
     * @return string
     */
    public function template_include( $template )
    {
        $pagenow = get_query_var( 'pagenow' );

        $template_loader = $this->get_template_loader();

        switch ( $pagenow ){

            case 'browser_view':
                $template = $template_loader->get_template_part( 'emails/browser-view', '', false );
                break;
            case 'emails':
                $template = $template_loader->get_template_part( 'emails/email', '', false );
                break;

        }

        return $template;
    }

    /**
     * Perform Superlink/link click benchmark stuff.
     *
     * @param string $template
     */
    public function template_redirect( $template='' )
    {
        $pagenow = get_query_var( 'pagenow' );

        switch ( $pagenow ){
            case 'superlink':

                $superlink_id = absint( get_query_var( 'superlink_id' ) );
                $superlink = new Superlink( $superlink_id );

                if ( $superlink->exists() ){
                    $superlink->process( Plugin::$instance->tracking->get_current_contact() );
                }

                break;
            case 'benchmark_link':
                break;

        }
    }

    /**
     * @param $array
     * @param $key
     * @param $func
     */
    public function map_query_var(&$array, $key, $func )
    {
        if ( ! function_exists( $func ) ){
            return;
        }

        if ( isset_not_empty( $array, $key ) ){
            $array[ $key ] = call_user_func( $func, $array[ $key ] );
        }
    }


    /**
     * Tracking for the link click benchmark.
     */
    public function link_clicked()
    {


        if ( ! $step )
            return;

        if ( $this->get_contact() ){
            do_action( 'wpgh_link_clicked', $step, $this->get_contact() );
            do_action( 'groundhogg/tracking/benchmark_link/click', $step, $this->get_contact() );
            $redirect_to = WPGH()->replacements->process( $step->get_meta( 'redirect_to' ), $this->get_contact()->ID );

            if ( wpgh_is_global_multisite() ){
                switch_to_blog( get_site()->site_id );
            }

            /* Check unsub page */
            $unsub_page = get_permalink( wpgh_get_option( 'gh_unsubscribe_page' ) );
            if ( $redirect_to === $unsub_page ){
                $redirect_to = sprintf( '%s?u=%s', $unsub_page, dechex( $this->contact->ID ) );
            }

            if ( is_multisite() && ms_is_switched() ){
                restore_current_blog();
            }


        } else {
            $redirect_to = $step->get_meta( 'redirect_to' );
        }

        wp_redirect( $redirect_to );
        die();
    }
}