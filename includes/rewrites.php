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
        add_managed_rewrite_rule(
            'browser-view/emails/([^/]*)/?$',
            'subpage=browser_view&email_id=$matches[1]'
        );

        // View Emails
        add_managed_rewrite_rule(
            'emails/([^/]*)/?$',
            'subpage=emails&email_id=$matches[1]'
        );

        // New tracking structure.
        add_managed_rewrite_rule(
            'superlinks/link/([^/]*)/?$',
            'subpage=superlink&superlink_id=$matches[1]'
        );

        // Benchmark links
        add_managed_rewrite_rule(
            'link/click/([^/]*)/?$',
            'subpage=benchmark_link&link_id=$matches[1]'
        );

        // Funnel Download/Export
        add_managed_rewrite_rule(
            'funnels/export/([^/]*)/?$',
            'subpage=funnels&action=export&enc_funnel_id=$matches[1]'
        );

        // File download
        add_managed_rewrite_rule(
            'files/([^/]*)/?$',
             'subpage=files&action=download&file_path=$matches[1]'
        );

        // File view with basename.
        add_managed_rewrite_rule(
            'files/([^/]*)/([^/]*)/?$',
            'subpage=files&action=download&file_path=$matches[1]'
        );

        add_managed_rewrite_rule(
            'forms/([^/]*)/submit/?$',
            'subpage=form_submit&form_id=$matches[1]'
        );

        // Forms Iframe Script
        add_managed_rewrite_rule(
            'forms/iframe/([^/]*)/?$',
            'subpage=forms_iframe&form_id=$matches[1]'
        );

        // Forms Iframe Template
        add_managed_rewrite_rule(
            'forms/([^/]*)/?$',
            'subpage=forms&form_id=$matches[1]'
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
        $vars[] = 'subpage';
        $vars[] = 'action';
        $vars[] = 'file_path';
        $vars[] = 'superlink_id';
        $vars[] = 'funnel_id';
        $vars[] = 'enc_funnel_id';
        $vars[] = 'enc_form_id';
        $vars[] = 'form_id';
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

        // form
        $this->map_query_var( $query, 'form_id', 'urldecode' );
        $this->map_query_var( $query, 'form_id', '\Groundhogg\decrypt' );
        $this->map_query_var( $query, 'form_id', 'absint' );

        $this->map_query_var( $query, 'file_path', 'urldecode' );
        $this->map_query_var( $query, 'file_path', 'base64_decode' );
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
        if ( ! is_managed_page() ){
            return $template;
        }

        $subpage = get_query_var( 'subpage' );
        $template_loader = $this->get_template_loader();

        switch ( $subpage ){
            case 'browser_view':
                $template = $template_loader->get_template_part( 'emails/browser-view', '', false );
                break;
            case 'emails':
                $template = $template_loader->get_template_part( 'emails/email', '', false );
                break;
            case 'forms':
                $template = $template_loader->get_template_part( 'form/form', '', false );
                break;
            case 'form_submit':
                $template = $template_loader->get_template_part( 'form/submit', '', false );
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

        if ( ! is_managed_page() ){
            return;
        }

        $subpage = get_query_var( 'subpage' );
        $template_loader = $this->get_template_loader();

        switch ( $subpage ){
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
                status_header( 200 );
                nocache_headers();

                $funnel_id = absint( Plugin::$instance->utils->encrypt_decrypt( get_query_var( 'enc_funnel_id' ), 'd' ) );
                $funnel = new Funnel( $funnel_id );
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
            case 'files':
                $file_path = get_query_var( 'file_path' );

                if ( ! $file_path || ! file_exists( $file_path ) ){
                    return;
                }

                $content_type = sprintf( "Content-Type: %s", mime_content_type( $file_path ) );
                $content_size = sprintf( "Content-Length: %s", filesize( $file_path ) );

                header( $content_type );
                header( $content_size );

                if ( get_request_var( 'download' ) ){
                    $content_disposition = sprintf( "Content-disposition: attachment; filename=%s", basename( $file_path ) );
                    header( $content_disposition );
                }

                status_header( 200 );
                nocache_headers();

                readfile( $file_path );
                exit();
                break;
            case 'forms_iframe':
                $template = $template_loader->get_template_part( 'form/iframe.js', '', true );
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