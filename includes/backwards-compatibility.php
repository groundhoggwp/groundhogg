<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *
 * Things that need backward compatibility
 *
 * Email Link tracking
 * Email Open tracking
 * Benchmark Link Click Tracking
 *
 *
 * Class Backwards_Compatibility
 *
 * @package Groundhogg
 */
class Backwards_Compatibility {

	public function __construct() {
		add_action( 'init', [ $this, 'add_old_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'add_old_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'back_compat_redirect' ] );
		add_action( 'edd_graph_load_scripts', [ $this, 'fix_edd_graph' ] );
	}

	public function fix_edd_graph() {
		wp_enqueue_script( 'jquery-flot-time' );
	}

	public function add_old_rewrite_rules() {

		add_rewrite_rule( 'gh-tracking/([^/]*)/([^/]*)', 'index.php?tracking=true&tracking_via=$matches[1]&tracking_action=$matches[2]', 'top' );
		add_rewrite_rule( 'gh-confirmation/[^/]*/([^/]*)', 'index.php?confirmation=true&confirmation_via=$matches[1]', 'top' );
		add_rewrite_rule( 'superlinks/link/([^/]*)', 'index.php?superlink=true&superlink_id=$matches[1]', 'top' );

	}

	/**
	 * Add the query vars.
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	public function add_old_query_vars( $vars ) {
		// Tracking vars
		$vars[] = 'tracking';
		$vars[] = 'tracking_via';
		$vars[] = 'tracking_action';

		// Confirmation vars
		$vars[] = 'confirmation';
		$vars[] = 'confirmation_via';

		// Superlinks
		$vars[] = 'superlink';
		$vars[] = 'superlink_id';

		return $vars;
	}


	public function back_compat_redirect( $template = '' ) {

		$tracking = get_query_var( 'tracking' );

		if ( $tracking ) {
			$tracking_via    = get_query_var( 'tracking_via' );
			$tracking_action = get_query_var( 'tracking_action' );

			switch ( $tracking_via ) {
				case 'link':

					wp_safe_redirect( managed_page_url( sprintf( 'link/click/%s/', get_request_var( 'id' ) ) ) );
					die();

					break;
				case 'email':

					switch ( $tracking_action ) {
						case 'open':

							$url = managed_page_url(
								sprintf(
									'tracking/email/open/u/%s/e/%s/i/%s/',
									get_request_var( 'u' ),
									get_request_var( 'e' ),
									get_request_var( 'i' )
								)
							);

							wp_safe_redirect( $url );
							die();

							break;
						case 'click':

							$url = managed_page_url(
								sprintf(
									'tracking/email/click/u/%s/e/%s/i/%s/ref/%s/',
									get_request_var( 'u' ),
									get_request_var( 'e' ),
									get_request_var( 'i' ),
									urlencode( base64_encode( urldecode( get_request_var( 'ref' ) ) ) )
								)
							);

							wp_safe_redirect( $url );
							die();

							break;
					}

					break;
			}
		}

		$confirmation = get_query_var( 'confirmation' );

		if ( $confirmation ) {
			wp_safe_redirect( managed_page_url( 'preferences/confirm/' ) );
			die();
		}

		$superlink = get_query_var( 'superlink' );

		if ( $superlink ) {
			wp_safe_redirect( sprintf( managed_page_url( 'superlinks/link/%s/' ), absint( get_query_var( 'superlink_id' ) ) ) );
			die();
		}

	}

}
