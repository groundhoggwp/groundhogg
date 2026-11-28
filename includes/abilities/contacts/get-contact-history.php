<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\AI\Jobs\Contact_Summary;
use Groundhogg\Markdown\Contact_Markdown;
use Groundhogg\Markdown\Markdown;
use Groundhogg\Pii;
use WP_Error;
use function Groundhogg\get_contactdata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Everything about a contact in one call, as markdown: their details, tags, custom fields, notes, tasks, messages,
 * activity, emails, flows, forms and page visits, plus what installed add-ons add, like deals and companies. An
 * assistant gets the whole picture without searching table by table, in far fewer tokens than the JSON would be.
 *
 * The same markdown the contact summary is made from, see Contact_Markdown.
 */
class Get_Contact_History extends Ability {

	protected const NAME       = 'groundhogg/get-contact-history';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'view_contacts';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	/**
	 * The most items a list can have
	 */
	const MAX_LIMIT = 100;

	protected function get_args(): array {

		$sections = Markdown::section_options( 'contact' );

		return [
			'label'       => __( 'Get Contact History', 'groundhogg' ),
			'description' => __( 'Everything about one contact in a single call, as markdown: their details, tags, custom fields, notes, tasks, messages, activity (opens, clicks, logins, unsubscribes), the emails sent to them and whether they opened them, the flows they went through and what is waiting for them, forms, page visits, and whatever installed add-ons add, like deals, companies, lead score, appointments and orders. Use it to understand a contact, like before writing to them, judging how warm a lead they are, or finding out why something happened to them, instead of calling get-contact, list-contact-notes and query-table one by one. Lists are newest first. Every section asked for is there: "None." means it is empty, and a section that says it is hidden is one the person you are working for may not see, so it is unknown, not empty. Never conclude from a hidden section, like that they never bought anything; tell the person it is hidden from them when it matters. It also returns the latest saved summary of the contact, with its age: when it is recent enough for what you need, use it rather than working one out again. When you do sum up the contact, save it with save-contact-summary, so the team sees it on their record and the next assistant can reuse it.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'id' ],
				'properties'           => [
					'id'       => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The contact ID.', 'groundhogg' ),
					],
					'sections' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => wp_list_pluck( $sections, 'key' ),
						],
						'description' => __( 'The sections to include, in this order. Leave it out for all of them. Available: ', 'groundhogg' ) . implode( '; ', array_map( function ( $section ) {
								return $section['key'] . ' (' . $section['description'] . ')';
							}, $sections ) ),
					],
					'personal' => [
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Include their name, email address, phone numbers and address, and leave them in notes and messages. Left out anyway for someone who may not see personal details. Set false when you don\'t need to know who they are.', 'groundhogg' ),
					],
					'limit'    => [
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => self::MAX_LIMIT,
						'default'     => 20,
						'description' => __( 'The most items in each list, like notes or activity.', 'groundhogg' ),
					],
					'since'    => [
						'type'        => [ 'string', 'integer' ],
						'description' => __( 'Only what happened since then, in every list: a date like 2026-11-01, a time ago like "30 days ago", or a Unix timestamp. All time when left out.', 'groundhogg' ),
					],
					'until'    => [
						'type'        => [ 'string', 'integer' ],
						'description' => __( 'Only what happened before then, in every list. Up to now when left out.', 'groundhogg' ),
					],
					'options'  => self::options_schema( $sections ),
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'id'       => [
						'type'        => 'integer',
						'description' => __( 'The contact ID.', 'groundhogg' ),
					],
					'personal' => [
						'type'        => 'boolean',
						'description' => __( 'Whether personal details are included.', 'groundhogg' ),
					],
					'markdown' => [
						'type'        => 'string',
						'description' => __( 'The contact, a "## " heading for each section asked for: what is in it, "None." when it is empty, or that it is hidden from the person you are working for.', 'groundhogg' ),
					],
					'summary'  => [
						'type'        => [ 'object', 'null' ],
						'description' => __( 'The latest saved summary: temperature (hot, warm or cold), reason, summary, interests, next_step, date, age, who saved it, and source (site for the summarize button, assistant for an AI assistant). When it was made from information the person you are working for may not see, only hidden: true and when. Null when there is none.', 'groundhogg' ),
					],
				],
			],
		];
	}

	/**
	 * What each section can be narrowed to, by section, like only some flows in activity
	 *
	 * @param array[] $sections from Markdown::section_options()
	 *
	 * @return array
	 */
	protected static function options_schema( array $sections ): array {

		$properties = [];

		foreach ( $sections as $section ) {
			if ( ! empty( $section['options'] ) ) {
				$properties[ $section['key'] ] = Markdown::options_schema( $section['options'] );
			}
		}

		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => $properties,
			'description'          => __( 'Narrow a section to what you need, by its key, like {"activity": {"funnel_ids": [3], "since": "30 days ago"}, "messages": {"direction": ["inbound"], "limit": 1}}. A narrowed section says what it was narrowed to. Everything when left out.', 'groundhogg' ),
		];
	}

	public function __invoke( $input ) {

		$contact = get_contactdata( absint( $input['id'] ?? 0 ) );

		if ( ! $contact || ! $contact->exists() ) {
			return new WP_Error( 'groundhogg_contact_not_found', __( 'Contact not found.', 'groundhogg' ) );
		}

		// the same per-contact check as get-contact, owners only see their own when they're limited to them
		if ( ! current_user_can( 'view_contact', $contact ) ) {
			return new WP_Error( 'groundhogg_cannot_view_contact', __( 'You do not have permission to view this contact.', 'groundhogg' ) );
		}

		$personal = ( $input['personal'] ?? true ) && ! Pii::is_restricted();

		// each section asked for, all of them when none are, with what it's narrowed to
		$sections = Markdown::sections( 'contact' );
		$options  = is_array( $input['options'] ?? null ) ? $input['options'] : [];
		$keys     = array_map( 'strval', (array) ( $input['sections'] ?? [] ) ) ?: array_keys( $sections );
		$wanted   = [];
		$bad      = Markdown::unreadable_times( Markdown::list_options(), $input );

		foreach ( $keys as $key ) {

			$wanted[ $key ] = is_array( $options[ $key ] ?? null ) ? $options[ $key ] : [];

			// no more in one list than in all of them
			if ( isset( $wanted[ $key ]['limit'] ) ) {
				$wanted[ $key ]['limit'] = min( self::MAX_LIMIT, max( 1, absint( $wanted[ $key ]['limit'] ) ) );
			}

			foreach ( Markdown::unreadable_times( $sections[ $key ]['options'] ?? [], $wanted[ $key ] ) as $name ) {
				$bad[] = "options.{$key}.{$name}";
			}
		}

		// a time that can't be read would quietly mean all time
		if ( $bad ) {
			return new WP_Error( 'groundhogg_bad_time', sprintf(
			/* translators: %s: the options */
				__( 'These times can\'t be read: %s. Use a date like 2026-11-01, a time ago like "30 days ago", or a Unix timestamp.', 'groundhogg' ),
				implode( ', ', $bad )
			) );
		}

		$markdown = Contact_Markdown::render( $contact, $wanted, [
			'personal' => $personal,
			'limit'    => min( self::MAX_LIMIT, max( 1, absint( $input['limit'] ?? 20 ) ) ),
			'since'    => $input['since'] ?? 0,
			'until'    => $input['until'] ?? 0,
		] );

		$summary = Contact_Summary::get_saved( $contact );

		return [
			'id'       => $contact->get_id(),
			'personal' => $personal,
			'markdown' => $markdown,
			'summary'  => $summary ? Save_Contact_Summary::public_fields( $summary ) : null,
		];
	}
}
