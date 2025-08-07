<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
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

		// Archive single using primary event id
		add_managed_rewrite_rule(
			'archive/p/([0-9a-fA-F]+)/?$',
			'subpage=browser_view&event_id=$matches[1]'
		);

		// Archive single using queued ID
		add_managed_rewrite_rule(
			'archive/([0-9a-fA-F]+)/?$',
			'subpage=browser_view&use_queued=1&event_id=$matches[1]'
		);


		// Email Archive
		add_managed_rewrite_rule(
			'archive/?$',
			'subpage=archive'
		);

		// View Emails
		add_managed_rewrite_rule(
			'emails/([^/]*)/?$',
			'subpage=emails&email_id=$matches[1]'
		);

		// Campaigns Archive
		add_managed_rewrite_rule(
			'campaigns/?$',
			'subpage=campaigns'
		);

		// Campaign broadcast archive
		add_managed_rewrite_rule(
			'campaigns/([0-9a-zA-Z-]+)/?$',
			'subpage=campaigns&campaign=$matches[1]'
		);

		// Campaign Archive Single
		add_managed_rewrite_rule(
			'campaigns/([0-9a-zA-Z-]+)/b/([0-9]+)/?$',
			'subpage=campaigns&campaign=$matches[1]&broadcast=$matches[2]'
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
		$vars[] = 'event_id';
		$vars[] = 'link_id';
		$vars[] = 'use_queued';
		$vars[] = 'campaign';
		$vars[] = 'broadcast';

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

		switch ( get_array_var( $query, 'subpage' ) ){
			case 'emails':
				$this->map_query_var( $query, 'email_id', 'absint' );
				break;
			case 'benchmark_link':
				$this->map_query_var( $query, 'link_id', 'absint' );
				break;
			case 'browser_view':
				$this->map_query_var( $query, 'event_id', 'hexdec' );
				$this->map_query_var( $query, 'event_id', 'absint' );
				break;
		}

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

				$template = $template_loader->get_template_part( 'archive/event', '', false );
				break;
			case 'archive':

				// No tracked contact
				if ( ! get_contactdata() ){
					break;
				}

				$template = $template_loader->get_template_part( 'archive/events', '', false );
				break;
			case 'campaigns':

				global $wp_query;

				$campaign_slug = get_query_var( 'campaign' );

				if ( empty( $campaign_slug ) ) {
					$template = $template_loader->get_template_part( 'archive/campaigns', '', false );
					break;
				}

				$campaign = new Campaign( $campaign_slug, 'slug' );
				$GLOBALS['campaign'] = $campaign;

				if ( ! $campaign->exists() || ( ! $campaign->is_public() && ! current_user_can( 'manage_campaigns' ) ) ) {

					status_header( 404 );
					$wp_query->set_404();
					$template = get_query_template( '404' );

					break;
				}

				$broadcast_id = get_query_var( 'broadcast' );

				if ( $broadcast_id ) {
					$broadcast = new Broadcast( $broadcast_id );
					$GLOBALS['broadcast'] = $broadcast;

					if ( $broadcast->exists() ) {
						$template = $template_loader->get_template_part( 'archive/broadcast', '', false );
						break;
					}
				}

				$template = $template_loader->get_template_part( 'archive/campaign', '', false );
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

				$funnel_id = absint( decrypt( get_query_var( 'enc_funnel_id' ) ) );
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

				// guard against ../../ traversal attack
				if ( ! $file_path || ! file_exists( $file_path ) || ! is_file( $file_path ) || ! Files::is_file_within_directory( $file_path, $groundhogg_path ) ) {
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
					$contact_read_access = $contact && $contact->get_upload_folder_basename() === $basename && check_permissions_key( get_permissions_key( 'download_files' ), $contact, 'download_files' );

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
				$permissions_key = get_permissions_key( 'auto_login' );

				$target_fallback_page = get_option( 'gh_auto_login_fallback_page', home_url() );
				$redirect_to          = apply_filters( 'groundhogg/auto_login/redirect_to', get_url_var( 'redirect_to', $target_fallback_page ) );

				if ( ! is_user_logged_in() ) {

					// If the contact or permissions key is not available, exit now.
					if ( ! $contact || ! $permissions_key || ! check_permissions_key( $permissions_key, $contact, 'auto_login' ) ) {
						wp_redirect( wp_login_url( $redirect_to ) );
						die();
					}

					$user = $contact->get_userdata();

					// If there is no user account, send to the home page
					if ( ! $user ) {
						wp_redirect( wp_login_url( $redirect_to ) );
						die();
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
	 * @param $func callable
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
