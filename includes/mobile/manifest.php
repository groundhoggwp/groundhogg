<?php

namespace Groundhogg\Mobile;

use Groundhogg\Main_Roles;
use function Groundhogg\get_team_ids;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the JSON object the shell boots from, already filtered for one user.
 *
 * `caps` is a map of booleans for the capabilities the registrations mention plus a small core set,
 * never the user's full capability list, so the page reveals no more than the client needs.
 */
class Manifest {

	/**
	 * Capabilities the core screens ask `can()` about, whether or not a registration names them.
	 */
	const CORE_CAPS = [
		'view_contacts',
		'add_contacts',
		'edit_contacts',
		'view_others_contacts',
		'send_emails',
		'manage_tags',
		'view_emails',
		'view_notes',
		'add_notes',
		'view_tasks',
		'add_tasks',
		'edit_tasks',
		'view_others_tasks',
	];

	/**
	 * @param int $user_id defaults to the current user
	 *
	 * @return array
	 */
	public static function build( int $user_id = 0 ) {

		$user_id = $user_id ?: get_current_user_id();
		$user    = get_userdata( $user_id );
		$visible = Features::instance()->for_user( $user_id );

		$caps = [];

		foreach ( array_unique( array_merge( self::CORE_CAPS, $visible['caps'] ) ) as $cap ) {
			$caps[ $cap ] = user_can( $user_id, $cap );
		}

		$manifest = [
			'apiVersion' => Features::API_VERSION,
			'app'        => [
				'name'      => App::name(),
				'url'       => App::url(),
				'homeUrl'   => home_url( '/' ),
				'adminUrl'  => admin_url(),
				'logoutUrl' => wp_logout_url( App::url() ),
			],
			'user'       => [
				'id'      => $user_id,
				'name'    => $user ? $user->display_name : '',
				'roles'   => $user ? array_values( $user->roles ) : [],
				'isRep'   => Main_Roles::is_sales_representative( $user_id ),
				'isMgr'   => Main_Roles::is_sales_manager( $user_id ),
				'teamIds' => array_map( 'absint', get_team_ids( $user_id ) ),
			],
			'caps'       => $caps,
			'rest'       => [
				'root'     => rest_url( 'gh/v4/' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'nonceUrl' => admin_url( 'admin-ajax.php?action=rest-nonce' ),
			],
			'locale'     => str_replace( '_', '-', get_user_locale( $user_id ) ),
			'timezone'   => wp_timezone_string(),
			'features'   => $visible['features'],
			'recent'     => Recent::get( $user_id ),
		];

		/**
		 * Filter the mobile app manifest before it is sent to the client.
		 *
		 * @param array $manifest
		 * @param int   $user_id
		 */
		return apply_filters( 'groundhogg/mobile/manifest', $manifest, $user_id );
	}
}
