<?php

namespace Groundhogg\Abilities\Campaigns;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Campaign_Schema;
use Groundhogg\Campaign;
use function Groundhogg\get_db;

/**
 * Creates a new Groundhogg campaign, used to group flows, broadcasts, and emails. If a campaign
 * with the given name already exists - matched by slug, the same way Campaigns::add() matches
 * everywhere else - the existing campaign is returned (and updated with any other fields
 * provided) instead of creating a duplicate. The response's "created" field tells you which
 * happened.
 */
class Create_Campaign extends Ability {

	protected const NAME       = 'groundhogg/create-campaign';
	protected const CATEGORY   = 'groundhogg-campaigns';
	protected const CAPABILITY = 'manage_campaigns';

	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Create Campaign', 'groundhogg' ),
			'description' => __( 'Create a new Groundhogg campaign. If a campaign with the given name already exists, the existing campaign is returned (and updated with any other fields provided) instead of creating a duplicate - the response\'s "created" field tells you which happened. Use the returned id with groundhogg/associate-campaign to attach it to an existing flow/email/broadcast, or pass it directly to groundhogg/create-flow\'s/groundhogg/create-email-template\'s campaigns param.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'name' ],
				'properties'           => [
					'name'        => [
						'type'        => 'string',
						'description' => __( 'The campaign name, e.g. "Black Friday 2026". Matched against existing campaigns by slug.', 'groundhogg' ),
					],
					'description' => [
						'type' => 'string',
					],
					'visibility'  => [
						'type'        => 'string',
						'enum'        => [ 'public', 'hidden' ],
						'default'     => 'hidden',
						'description' => __( '"public" campaigns are selectable in wp-admin\'s own campaign picker UI; "hidden" ones are still fully usable by id but left out of that picker - the more sensible default for a campaign created programmatically.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => array_merge( Campaign_Schema::get_schema()['properties'], [
					'created' => [
						'type'        => 'boolean',
						'description' => __( 'True if this created a brand-new campaign; false if an existing campaign with the same name was found (and possibly updated).', 'groundhogg' ),
					],
				] ),
			],
		];
	}

	public function __invoke( $input ) {

		$db   = get_db( 'campaigns' );
		$slug = sanitize_title( $input['name'] );

		$existed = (bool) $db->exists( [ 'slug' => $slug ] );

		$data = [ 'name' => sanitize_text_field( $input['name'] ) ];

		if ( isset( $input['description'] ) ) {
			$data['description'] = sanitize_textarea_field( $input['description'] );
		}

		if ( ! empty( $input['visibility'] ) ) {
			$data['visibility'] = $input['visibility'];
		}

		$campaign_id = $db->add( $data );
		$campaign    = new Campaign( $campaign_id );

		// add() only applies name when the slug already matched an existing campaign - apply the
		// rest of what was given now.
		if ( $existed ) {
			unset( $data['name'] );

			if ( $data ) {
				$campaign->update( $data );
			}
		}

		return array_merge( Campaign_Schema::transform( $campaign ), [
			'created' => ! $existed,
		] );
	}
}
