<?php

namespace Groundhogg;

class Rewrites {
	/**
	 * Rewrites constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_filter( 'request', [ $this, 'parse_query' ] );

		// Do last so precedence is given to Groundhogg
		add_filter( 'template_include', [ $this, 'template_include' ], 99 );

		// Do first so precedence is given to Groundhogg
		add_action( 'template_redirect', [ $this, 'template_redirect' ], 9 );
	}

	/**
	 * Add the rewrite rules required for the Preferences center.
	 */
	public function add_rewrite_rules() {

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

		// Benchmark links
		add_managed_rewrite_rule(
			'link/click/([^/]*)/?$',
			'subpage=benchmark_link&link_id=$matches[1]'
		);

		add_managed_rewrite_rule(
			'click/([^/]*)/?$',
			'subpage=benchmark_link&slug=$matches[1]'
		);

		// Funnel Download/Export
		add_managed_rewrite_rule(
			'funnels/export/([^/]*)/?$',
			'subpage=funnels&action=export&enc_funnel_id=$matches[1]'
		);

		// File download
		add_managed_rewrite_rule(
			'file-download/([^/]*)/?$',
			'subpage=files&action=download&file_path=$matches[1]'
		);

		// File view with basename.
		add_managed_rewrite_rule(
			'file-download/(.*)',
			'subpage=files&action=download&file_path=$matches[1]'
		);

		add_managed_rewrite_rule(
			'forms/([^/]*)/submit/?$',
			'subpage=form_submit&slug=$matches[1]'
		);

		// Forms Iframe Script
		add_managed_rewrite_rule(
			'forms/iframe/([^/]*)/?$',
			'subpage=forms_iframe&slug=$matches[1]'
		);

		// Forms Iframe Template
		add_managed_rewrite_rule(
			'forms/([^/]*)/?$',
			'subpage=forms&slug=$matches[1]'
		);

		// Forms Iframe Template
		add_managed_rewrite_rule(
			'auto-login/?$',
			'subpage=auto_login'
		);


	}

	/**
	 * Add the query vars needed to manage the request.
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'subpage';
		$vars[] = 'action';
		$vars[] = 'slug';
		$vars[] = 'file_path';
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
	 *
	 * @return mixed
	 */
	public function parse_query( $query ) {
		$this->map_query_var( $query, 'link_id', 'absint' );
		$this->map_query_var( $query, 'email_id', 'absint' );

		// form
//		$this->map_query_var( $query, 'form_id', 'urldecode' );
//		$this->map_query_var( $query, 'form_id', '\Groundhogg\decrypt' );
//		$this->map_query_var( $query, 'form_id', 'absint' );

		return $query;
	}

	/**
	 * @return Template_Loader
	 */
	public function get_template_loader() {
		return new Template_Loader();
	}

	public function get_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );

		return get_404_template();
	}

	/**
	 * Overwrite the existing template with the manage preferences template.
	 *
	 * @param $template
	 *
	 * @return string
	 */
	public function template_include( $template ) {
		if ( ! is_managed_page() ) {
			return $template;
		}

		$subpage         = get_query_var( 'subpage' );
		$template_loader = $this->get_template_loader();

		switch ( $subpage ) {
			case 'benchmark_link':
			case 'funnels':
				return $this->get_404();
			case 'browser_view':

				// No tracked contact
				if ( ! get_contactdata() ){
					break;
				}

				$template = $template_loader->get_template_part( 'emails/browser-view', '', false );
				break;
			case 'emails':
				$template = $template_loader->get_template_part( 'emails/email', '', false );
				break;
			case 'forms':
				if ( get_query_var( 'form_step' ) ) {
					$template = $template_loader->get_template_part( 'form/form', '', false );
				} else {
					return $this->get_404();
				}

				break;
			case 'form_submit':
				if ( get_query_var( 'form_step' ) ) {
					$template = $template_loader->get_template_part( 'form/submit', '', false );
				} else {
					return $this->get_404();
				}

				break;
		}

		return $template;
	}

	/**
	 * Perform Superlink/link click benchmark stuff.
	 *
	 * @param string $template
	 */
	public function template_redirect() {

		if ( ! is_managed_page() ) {
			return;
		}

		$subpage         = get_query_var( 'subpage' );
		$template_loader = $this->get_template_loader();

		switch ( $subpage ) {
			case 'forms':
			case 'form_submit':

				$step = new Step( get_query_var( 'slug' ) );

				if ( $step->exists() && ( $step->type_is( 'form_fill' ) || $step->type_is( 'web_form' ) ) ) {
					set_query_var( 'form_step', $step );
				}

				break;
			case 'benchmark_link':

				$link_id = absint( get_query_var( 'link_id' ) );

				if ( ! $link_id ) {
					$link_id = get_query_var( 'slug' );
				}

				$contact = get_contactdata();

				$step = new Step( $link_id );

				if ( ! $step->exists() || ! $step->type_is( 'link_click' ) ) {
					return;
				}

				$target_url = $step->get_meta( 'redirect_to' );

				if ( empty( $target_url ) ) {
					$target_url = home_url();
				}

				if ( $contact ) {
					$target_url = do_replacements( $target_url, $contact->get_id() );

					if ( $step->benchmark_enqueue( $contact ) ) {
						process_events( $contact );
					}
				}

				wp_redirect( $target_url );
				die();

				break;
			case 'funnels':
				// Export the funnel from special rewrite link...
				status_header( 200 );
				nocache_headers();

				$funnel_id = absint( Plugin::$instance->utils->encrypt_decrypt( get_query_var( 'enc_funnel_id' ), 'd' ) );
				$funnel    = new Funnel( $funnel_id );

				if ( ! $funnel->exists() ) {
					return;
				}

				$export_string = wp_json_encode( $funnel->export() );

				$funnel_export_name = strtolower( preg_replace( '/[^A-z0-9]/', '-', $funnel->get_title() ) );

				$filename = 'funnel-' . $funnel_export_name;

				header( "Content-type: text/plain" );
				header( "Content-disposition: attachment; filename=" . $filename . ".funnel" );
				$file = fopen( 'php://output', 'w' );
				fputs( $file, $export_string );
				fclose( $file );
				exit();
				break;

			case 'files':

				$short_path      = get_query_var( 'file_path' );
				$groundhogg_path = utils()->files->get_base_uploads_dir();
				$file_path       = wp_normalize_path( $groundhogg_path . DIRECTORY_SEPARATOR . $short_path );

				if ( ! $file_path || ! file_exists( $file_path ) || ! is_file( $file_path ) ) {
					wp_die( 'The requested file was not found.', 'File not found.', [ 'status' => 404 ] );
				}

				$unrestricted = is_option_enabled( 'gh_allow_unrestricted_file_access' );

				if ( ! $unrestricted ) {

					$request = get_request_query();

					// General admin access
					$admin_read_access = current_user_can( 'download_file', $short_path, $request, $file_path );

					// Contact read access
					$contact             = get_contactdata();
					$basename            = basename( dirname( $file_path ) );
					$contact_read_access = $contact && $contact->get_upload_folder_basename() === $basename && check_permissions_key( get_permissions_key(), $contact, 'download_files' );

					if ( ! $admin_read_access && ! $contact_read_access ) {
						wp_die( 'You do not have permission to view this file.', 'Access denied.', [ 'status' => 403 ] );
					}
				}

				$mime = wp_check_filetype( $file_path );
				$mime = $mime['type'];

				if ( ! $mime ) {
					wp_die( 'The request file type is unrecognized and has been blocked for your protection.', 'Access denied.', [ 'status' => 403 ] );
				}

				$content_type = sprintf( "Content-Type: %s", $mime );
				$content_size = sprintf( "Content-Length: %s", filesize( $file_path ) );

				header( $content_type );
				header( $content_size );

				if ( get_request_var( 'download' ) ) {
					$content_disposition = sprintf( "Content-disposition: attachment; filename=%s", basename( $file_path ) );
				} else {
					$content_disposition = sprintf( "Content-disposition: inline; filename=%s", basename( $file_path ) );
				}

				header( $content_disposition );

				status_header( 200 );
				nocache_headers();

				readfile( $file_path );
				exit();
				break;
			case 'forms_iframe':
				$template = $template_loader->get_template_part( 'form/iframe.js', '', true );
				exit();
				break;
			case 'auto_login':

				$contact         = get_contactdata( get_url_var( 'cid' ) );
				$permissions_key = get_permissions_key();

				$target_fallback_page = get_option( 'gh_auto_login_fallback_page', home_url() );
				$redirect_to          = apply_filters( 'groundhogg/auto_login/redirect_to', get_url_var( 'redirect_to', $target_fallback_page ) );

				if ( ! is_user_logged_in() ) {

					// If the contact or permissions key is not available, exit now.
					if ( ! $contact || ! $permissions_key || ! check_permissions_key( $permissions_key, $contact, 'auto_login' ) ) {
						wp_die( __( 'Unable to login. This link may have expired.', 'groundhogg' ) );
					}

					$user = $contact->get_userdata();

					// If there is no user account, send to the home page
					if ( ! $user ) {
						wp_die( __( 'Unable to login. There is no user account associated with your email address.', 'groundhogg' ) );
					}

					wp_set_current_user( $user->ID );
					wp_set_auth_cookie( $user->ID );

					/**
					 * Compat for things tracking logins
					 *
					 * @param string
					 * @param \WP_User
					 */
					do_action( 'wp_login', $user->user_login, $user );
				}

				exit( wp_redirect( $redirect_to ) );

				break;
		}
	}

	/**
	 * @param $array
	 * @param $key
	 * @param $func
	 */
	public function map_query_var( &$array, $key, $func ) {
		if ( ! function_exists( $func ) ) {
			return;
		}

		if ( isset_not_empty( $array, $key ) ) {
			$array[ $key ] = call_user_func( $func, $array[ $key ] );
		}
	}
}
