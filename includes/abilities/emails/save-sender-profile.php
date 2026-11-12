<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Plugin;
use WP_Error;
use function Groundhogg\get_owners;

/**
 * Creates or changes a sender profile - a from name and email emails can be sent as. See
 * \Groundhogg\get_sender_profiles() for where each kind lives:
 *
 * - "default": the override_from_name / override_from_email settings (Settings › Email › Sender Profiles)
 * - "user-<id>": a contact owner's own from name and email, in their user meta (gh_from_name, gh_from_email), set
 *   on their profile's Groundhogg section
 * - "custom-<id>": extra profiles, a JSON list of [name, email, id] in gh_custom_sender_profiles, the repeater on
 *   Settings › Email › Sender Profiles. The id is 8 hex characters, like the repeater makes.
 * - "owner" is the contact's owner at send time, so there's nothing to save
 *
 * The site-wide ones need manage_options. A user's own needs edit_user for that user, so an agent connected as a
 * sales rep can set their own.
 */
class Save_Sender_Profile extends Ability {

	protected const NAME     = 'groundhogg/save-sender-profile';
	protected const CATEGORY = 'groundhogg-email';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Save Sender Profile', 'groundhogg' ),
			'description' => __( 'Create a sender profile, a from name and email emails can be sent as, or change one. Leave out id to add a custom profile. Use "default" for the site\'s default sender, or "user-<user id>" for a contact owner\'s own from name and email, used when emails are sent as the contact\'s owner. See groundhogg/list-sender-profiles for what exists. Send from an address on a domain the email service is set up for, or the email lands in spam.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'from_name', 'from_email' ],
				'properties'           => [
					'id'         => [
						'type'        => 'string',
						'pattern'     => '^(default|user-\d+|custom-[a-zA-Z0-9]+)$',
						'description' => __( 'The profile to change: "default", "user-<user id>" or "custom-<id>" from groundhogg/list-sender-profiles. Leave out to create a custom profile.', 'groundhogg' ),
					],
					'from_name'  => [ 'type' => 'string' ],
					'from_email' => [
						'type'   => 'string',
						'format' => 'email',
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'id'          => [
						'type'        => 'string',
						'description' => __( 'Use this as an email\'s from profile.', 'groundhogg' ),
					],
					'from_name'   => [ 'type' => 'string' ],
					'from_email'  => [ 'type' => 'string' ],
					'from_header' => [ 'type' => 'string' ],
					'created'     => [ 'type' => 'boolean' ],
				],
			],
		];
	}

	/**
	 * Who the profile belongs to decides who can change it
	 *
	 * @param mixed $input
	 *
	 * @return bool
	 */
	public function can_execute( $input = null ) {

		if ( preg_match( '/^user-(\d+)$/', (string) ( $input['id'] ?? '' ), $matches ) ) {
			return current_user_can( 'edit_user', absint( $matches[1] ) );
		}

		return current_user_can( 'manage_options' );
	}

	public function __invoke( $input ) {

		$name  = sanitize_text_field( $input['from_name'] ?? '' );
		$email = sanitize_email( $input['from_email'] ?? '' );
		$id    = (string) ( $input['id'] ?? '' );

		if ( $name === '' ) {
			return new WP_Error( 'groundhogg_invalid_input', __( '`from_name` is required.', 'groundhogg' ) );
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'groundhogg_invalid_input', __( '`from_email` must be an email address.', 'groundhogg' ) );
		}

		$created = false;

		if ( $id === 'default' ) {

			Plugin::instance()->settings->update_option( 'override_from_name', $name );
			Plugin::instance()->settings->update_option( 'override_from_email', $email );

		} else if ( preg_match( '/^user-(\d+)$/', $id, $matches ) ) {

			$user_id = absint( $matches[1] );

			if ( ! in_array( $user_id, array_map( 'absint', wp_list_pluck( get_owners(), 'ID' ) ), true ) ) {
				return new WP_Error( 'groundhogg_not_an_owner', __( 'That user can\'t own contacts, so they have no sender profile. See groundhogg/list-owners.', 'groundhogg' ) );
			}

			update_user_meta( $user_id, 'gh_from_name', $name );
			update_user_meta( $user_id, 'gh_from_email', $email );

		} else {

			$profiles = self::get_custom_profiles();

			if ( $id === '' ) {
				$key        = strtolower( wp_generate_password( 8, false ) );
				$profiles[] = [ $name, $email, $key ];
				$id         = "custom-$key";
				$created    = true;
			} else {

				$key   = substr( $id, strlen( 'custom-' ) );
				$found = false;

				foreach ( $profiles as $i => $profile ) {
					if ( ( $profile[2] ?? '' ) === $key ) {
						$profiles[ $i ] = [ $name, $email, $key ];
						$found          = true;
					}
				}

				if ( ! $found ) {
					return new WP_Error( 'groundhogg_profile_not_found', __( 'There is no custom sender profile with that id. See groundhogg/list-sender-profiles.', 'groundhogg' ) );
				}
			}

			self::save_custom_profiles( $profiles );
		}

		return [
			'id'          => $id,
			'from_name'   => $name,
			'from_email'  => $email,
			'from_header' => "$name <$email>",
			'created'     => $created,
		];
	}

	/**
	 * The custom profiles as [name, email, id] lists, like the settings repeater stores them
	 *
	 * @return array[]
	 */
	public static function get_custom_profiles(): array {

		$profiles = json_decode( (string) get_option( 'gh_custom_sender_profiles' ), true );

		return is_array( $profiles ) ? array_values( array_filter( $profiles, 'is_array' ) ) : [];
	}

	/**
	 * @param array[] $profiles [name, email, id] lists
	 *
	 * @return void
	 */
	public static function save_custom_profiles( array $profiles ) {
		update_option( 'gh_custom_sender_profiles', wp_json_encode( array_values( $profiles ) ) );
	}
}
