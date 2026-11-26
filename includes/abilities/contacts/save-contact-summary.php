<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\AI\Jobs\Contact_Summary;
use WP_Error;
use function Groundhogg\get_contactdata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keep a summary an assistant wrote about a contact on their record, where the contact editor shows it above their
 * tags and get-contact-history hands it to the next assistant. The same place the summarize button keeps its own, see
 * Contact_Summary, so there's one summary per contact, the latest.
 */
class Save_Contact_Summary extends Ability {

	protected const NAME       = 'groundhogg/save-contact-summary';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'edit_contacts';

	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Save Contact Summary', 'groundhogg' ),
			'description' => __( 'Save a summary you wrote about a contact to their record, replacing the one before. It shows on their record for the team, and get-contact-history returns it to whoever looks at them next, so nobody has to work it out again. Save one whenever you sum up a contact, like after reading their history with get-contact-history. Base it only on their record. Leave out their name and contact details: the team can see those already.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'id', 'temperature', 'reason', 'summary' ],
				'properties'           => [
					'id'          => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The contact ID.', 'groundhogg' ),
					],
					'temperature' => [
						'type'        => 'string',
						'enum'        => Contact_Summary::TEMPERATURES,
						'description' => __( 'How engaged and ready to buy they look. Recent clicks, forms, purchases, logins and replies count for more than opens; unsubscribes, bounces and spam complaints count against. Cold when there\'s little to go on.', 'groundhogg' ),
					],
					'reason'      => [
						'type'        => 'string',
						'description' => __( 'One short sentence on why that temperature.', 'groundhogg' ),
					],
					'summary'     => [
						'type'        => 'string',
						'description' => __( 'Two to four sentences: who they are to the business, what they did lately, and where they are in its flows.', 'groundhogg' ),
					],
					'interests'   => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'maxItems'    => 5,
						'description' => __( 'Up to five short topics or products they seem interested in.', 'groundhogg' ),
					],
					'next_step'   => [
						'type'        => 'string',
						'description' => __( 'One concrete thing to do next.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'id'      => [
						'type'        => 'integer',
						'description' => __( 'The contact ID.', 'groundhogg' ),
					],
					'summary' => [
						'type'        => 'object',
						'description' => __( 'The summary as it was saved, cleaned up.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$contact = get_contactdata( absint( $input['id'] ?? 0 ) );

		if ( ! $contact || ! $contact->exists() ) {
			return new WP_Error( 'groundhogg_contact_not_found', __( 'Contact not found.', 'groundhogg' ) );
		}

		if ( ! current_user_can( 'edit_contact', $contact ) ) {
			return new WP_Error( 'groundhogg_cannot_edit_contact', __( 'You do not have permission to edit this contact.', 'groundhogg' ) );
		}

		$summary = Contact_Summary::save( $contact, [
			'temperature' => $input['temperature'] ?? '',
			'reason'      => $input['reason'] ?? '',
			'summary'     => $input['summary'] ?? '',
			'interests'   => $input['interests'] ?? [],
			'next_step'   => $input['next_step'] ?? '',
		], 'assistant' );

		if ( ! $summary ) {
			return new WP_Error( 'groundhogg_empty_summary', __( 'The summary is empty.', 'groundhogg' ) );
		}

		return [
			'id'      => $contact->get_id(),
			'summary' => self::public_fields( $summary ),
		];
	}

	/**
	 * A saved summary, for an assistant: what it says, when, and by whom
	 *
	 * @param array $summary from Contact_Summary
	 *
	 * @return array
	 */
	public static function public_fields( array $summary ): array {
		return [
			'temperature' => $summary['temperature'],
			'reason'      => $summary['reason'],
			'summary'     => $summary['summary'],
			'interests'   => $summary['interests'],
			'next_step'   => $summary['next_step'],
			'date'        => gmdate( 'c', absint( $summary['date'] ) ),
			'age'         => $summary['when'],
			'by'          => $summary['by'],
			'source'      => $summary['source'] ?? 'site',
		];
	}
}
