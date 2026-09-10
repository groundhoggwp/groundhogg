<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use function Groundhogg\get_sender_profiles;

/**
 * Lists the sender profiles configured for the site (Settings > Email, plus one
 * per contact-owner and any custom profiles) - the from name / from email
 * combinations Groundhogg considers valid to send as. See
 * \Groundhogg\get_sender_profiles().
 *
 * Use this to pick the from_email / from_name for groundhogg/send-composed-email
 * rather than guessing an address that may fail SPF/DKIM alignment or get
 * rejected by the sending service.
 *
 * The `owner` profile is dynamic: its from_name / from_email are the literal
 * merge tags {owner_name} / {owner_email}, resolved per-recipient at send time,
 * so it can't be used as a literal from address for a composed email.
 */
class List_Sender_Profiles extends Ability {

	protected const string NAME       = 'groundhogg/list-sender-profiles';
	protected const string CATEGORY   = 'groundhogg-email';
	protected const string CAPABILITY = 'send_emails';

	protected const bool READONLY   = true;
	protected const bool IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Sender Profiles', 'groundhogg' ),
			'description' => __( 'List the valid "from" name / email combinations the site is configured to send as (the site default, one per contact owner, and any custom sender profiles). Use a profile\'s from_name / from_email with groundhogg/send-composed-email.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'profiles' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id' => [
									'type'        => 'string',
									'description' => __( 'The profile key, e.g. "default", "owner", "user-5", "custom-3".', 'groundhogg' ),
								],
								'from_name' => [
									'type' => 'string',
								],
								'from_email' => [
									'type' => 'string',
								],
								'from_header' => [
									'type'        => 'string',
									'description' => __( 'The combined "Name <email>" header value.', 'groundhogg' ),
								],
								'display' => [
									'type'        => 'string',
									'description' => __( 'The human-readable label shown for this profile in the email editor.', 'groundhogg' ),
								],
								'dynamic' => [
									'type'        => 'boolean',
									'description' => __( 'True when from_name / from_email contain merge tags resolved per-recipient (the "owner" profile). A dynamic profile is not a usable literal from address for groundhogg/send-composed-email.', 'groundhogg' ),
								],
							],
							'required' => [ 'id', 'from_name', 'from_email', 'from_header', 'display', 'dynamic' ],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$profiles = [];

		foreach ( get_sender_profiles() as $id => $profile ) {

			$from_email = $profile['from_email'] ?? '';

			$profiles[] = [
				'id'          => (string) $id,
				'from_name'   => $profile['from_name'] ?? '',
				'from_email'  => $from_email,
				'from_header' => $profile['from_header'] ?? '',
				'display'     => $profile['display'] ?? '',
				// A concrete profile stores a real address; the "owner" profile
				// stores the {owner_email} merge tag, which is not a valid email.
				'dynamic'     => ! is_email( $from_email ),
			];
		}

		return [
			'profiles' => $profiles,
		];
	}
}
