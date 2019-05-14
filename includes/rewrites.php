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

        // Benchmark links OLD
        add_rewrite_rule(
            '^gh/link/click/([^/]*)/?$',
            'index.php?pagenow=benchmark_link&link_id=$matches[1]',
            'top'
        );

        // Funnel Download/Export
        add_rewrite_rule(
            '^gh/funnels/export/([^/]*)/?$',
            'index.php?pagenow=funnels&action=export&enc_funnel_id=$matches[1]',
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
        $vars[] = 'action';
        $vars[] = 'superlink_id';
        $vars[] = 'funnel_id';
        $vars[] = 'enc_funnel_id';
        $vars[] = 'email_id';
        $vars[] = 'link_id';
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
        $this->map_query_var( $query, 'link_id', 'absint' );
        $this->map_query_var( $query, 'email_id', 'absint' );
        $this->map_query_var( $query, 'superlink_id', 'absint' );
//        $this->map_query_var( $query, 'enc_funnel_id', 'urldecode' );
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

                $link_id = absint( get_query_var( 'link_id' ) );
                $contact = Plugin::$instance->tracking->get_current_contact();

                $step = Plugin::$instance->utils->get_step( $link_id );

                if ( ! $contact || ! $step ) {
                    return;
                }

                $target_url = $step->get_meta( 'redirect_to' );

                do_action( 'groundhogg/rewrites/benchmark_link/clicked', $contact, $step );

                $target_url = Plugin::$instance->replacements->process( $target_url, $contact->get_id() );

                wp_redirect( wp_nonce_url( $target_url,  -1, 'key' ) );
                die();

                break;
            case 'funnels':
                // Export the funnel from special rewrite link...
                $funnel_id = absint( Plugin::$instance->utils->encrypt_decrypt( get_query_var( 'enc_funnel_id' ), 'd' ) );
                $funnel = new Funnel( $funnel_id );

//                wp_die( get_query_var( 'enc_funnel_id' ) );

                if ( ! $funnel->exists() ){
                    return;
                }

                $export_string = wp_json_encode( $funnel->get_as_array() );
                $filename = 'funnel-' . $funnel->get_title() . '-'. date("Y-m-d_H-i", time() );

                header("Content-type: text/plain");
                header( "Content-disposition: attachment; filename=".$filename.".funnel");
                $file = fopen('php://output', 'w');
                fputs( $file, $export_string );
                fclose($file);
                exit();
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
}