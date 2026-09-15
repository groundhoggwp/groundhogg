<?php

namespace Groundhogg\Abilities\Campaigns;

use Groundhogg\Abilities\Ability;
use Groundhogg\Broadcast;
use Groundhogg\Campaign;
use Groundhogg\Email;
use Groundhogg\Funnel;
use WP_Error;

/**
 * Attaches or detaches a campaign from an existing flow, email, or broadcast.
 *
 * Complements the `campaigns` param already accepted at creation/update time by
 * groundhogg/create-flow, groundhogg/create-email-template, and groundhogg/update-email-template -
 * this is for changing a campaign association after the fact, which those can't otherwise cover:
 * there's no update-flow ability at all, and groundhogg/send-email-broadcast only ever adds
 * campaigns, never removes them.
 *
 * Both directions go through Base_Object::create_relationship()/delete_relationship() - the same
 * mechanism the abilities above already use, just called with the flow/email/broadcast as the
 * primary object and the campaign as the secondary, matching how every existing campaign
 * association in Groundhogg is stored.
 */
class Associate_Campaign extends Ability {

	protected const NAME       = 'groundhogg/associate-campaign';
	protected const CATEGORY   = 'groundhogg-campaigns';
	protected const CAPABILITY = 'manage_campaigns';

	protected const IDEMPOTENT = true;

	/**
	 * Ability-facing object_type value => the Base_Object subclass to construct.
	 * "flow" (not "funnel") to match the naming groundhogg/create-flow and groundhogg/list-flows
	 * already use for this concept - the underlying object_type stored in the relationship row
	 * is still "funnel" (Funnel::get_object_type()), which is transparent to the caller.
	 */
	const OBJECT_TYPES = [
		'flow'      => Funnel::class,
		'email'     => Email::class,
		'broadcast' => Broadcast::class,
	];

	protected function get_args(): array {

		return [
			'label'       => __( 'Associate Campaign', 'groundhogg' ),
			'description' => __( 'Attach or detach a Groundhogg campaign from an existing flow, email, or broadcast. Find campaign ids with groundhogg/list-campaigns or groundhogg/create-campaign.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'campaign_id', 'object_type', 'object_id' ],
				'properties'           => [
					'campaign_id' => [
						'type'        => 'integer',
						'description' => __( 'The campaign id - see groundhogg/list-campaigns.', 'groundhogg' ),
					],
					'object_type' => [
						'type' => 'string',
						'enum' => array_keys( self::OBJECT_TYPES ),
					],
					'object_id'   => [
						'type'        => 'integer',
						'description' => __( 'The id of the flow/email/broadcast, matching object_type.', 'groundhogg' ),
					],
					'action'      => [
						'type'        => 'string',
						'enum'        => [ 'add', 'remove' ],
						'default'     => 'add',
						'description' => __( '"add" attaches the campaign (no-op if already attached); "remove" detaches it (no-op if not attached).', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'associated'   => [
						'type'        => 'boolean',
						'description' => __( 'True after a successful "add", false after "remove".', 'groundhogg' ),
					],
					'campaign_ids' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Every campaign now associated with the object, for convenience.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$campaign = new Campaign( absint( $input['campaign_id'] ) );

		if ( ! $campaign->exists() ) {
			return new WP_Error( 'groundhogg_invalid_campaign', __( 'No campaign exists with that id.', 'groundhogg' ) );
		}

		$class  = self::OBJECT_TYPES[ $input['object_type'] ];
		$object = new $class( absint( $input['object_id'] ) );

		if ( ! $object->exists() ) {
			/* translators: %s: object type, e.g. "flow" */
			return new WP_Error( 'groundhogg_invalid_object', sprintf( __( 'No %s exists with that id.', 'groundhogg' ), $input['object_type'] ) );
		}

		$action = $input['action'] ?? 'add';

		if ( $action === 'remove' ) {
			$object->delete_relationship( $campaign );
		} else {
			$object->create_relationship( $campaign );
		}

		$campaign_ids = array_map( function ( $related ) {
			return $related->get_id();
		}, $object->get_related_objects( 'campaign' ) );

		return [
			'associated'   => $action !== 'remove',
			'campaign_ids' => array_values( array_unique( array_map( 'absint', $campaign_ids ) ) ),
		];
	}
}
